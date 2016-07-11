<?php /*

**************************************************************************

Plugin Name:  Viper's Video Quicktags
Plugin URI:   http://www.viper007bond.com/wordpress-plugins/vipers-video-quicktags/
Description:  Embeds thingies.
Version:      7.0.0 Super Alpha
Author:       Viper007Bond
Author URI:   http://www.viper007bond.com/

**************************************************************************

Copyright (C) 2006-2010 Viper007Bond

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

**************************************************************************

This plugin is currently a bit of a mess as it's still in proof of concept
form while I knock out the basic functionality and test it.

Mind your step. ;)

**************************************************************************/

class VipersVideoQuicktags {
	var $version = '7.0.0';
	var $shortcodes = array();
	var $usedids = array();
	var $content_width = 2000; // Set really high so it's not used in min() results

	// Class initialization
	function VipersVideoQuicktags() {
		global $content_width;

		// This version of VVQ requires WordPress 2.9+
		// Deactivate this plugin for old versions of WordPress
		if ( !class_exists('WP_Embed') ) {
			load_plugin_textdomain( 'vipers-video-quicktags', '/wp-content/plugins/vipers-video-quicktags/localization' ); // Old format
			if ( isset( $_GET['activate'] ) ) {
				wp_redirect( 'plugins.php?deactivate=true' );
				exit();
			} else {
				// Replicate deactivate_plugins()
				$current = get_option('active_plugins');
				$plugins = array( 'vipers-video-quicktags/vipers-video-quicktags.php', 'vipers-video-quicktags.php' );
				foreach ( $plugins as $plugin ) {
					if ( !in_array( $plugin, $current ) )
						continue;
					array_splice( $current, array_search( $plugin, $current ), 1 );
				}
				update_option('active_plugins', $current);

				add_action( 'admin_notices', array(&$this, 'wp_version_too_old_notice') );

				return;
			}
		}

		// For debugging (this is limited to localhost installs since it's not nonced)
		if ( !empty($_GET['resetalloptions']) && 'localhost' == $_SERVER['HTTP_HOST'] && is_admin() && 'vipers-video-quicktags' == $_GET['page'] ) {
			update_option( 'vvq_options', array() );
			wp_redirect( admin_url( 'options-general.php?page=vipers-video-quicktags&defaults=true' ) );
			exit();
		}

		// Load up the localization file if we're using WordPress in a different language
		// Place it in this plugin's "localization" folder and name it "vipers-video-quicktags-[value in wp-config].mo"
		load_plugin_textdomain( 'vipers-video-quicktags', false, '/vipers-video-quicktags/localization' );

		// Figure out a content width for non-oEmbed embeds
		if ( !empty($content_width) )
			$this->content_width = $content_width;



		wp_register_script( 'flowplayer', plugins_url( 'flowplayer/flowplayer-3.2.2.min.js', __FILE__ ), array(), '3.2.2' );


		add_filter( 'embed_oembed_html', array(&$this, 'catch_supported_urls'), 7, 3 );
		add_filter( 'embed_googlevideo', array(&$this, 'googlevideo'), 10, 5 );

		add_filter( 'the_content', array(&$this, 'run_shortcode'), 8 );
		add_filter( 'the_content', array(&$this, 'fix_ie_conditional'), 11 );

		add_action( 'wp_head', array(&$this, 'head_css') );
		add_action( 'wp_footer', array(&$this, 'swfobject_calls') );
		add_action( 'admin_menu', array(&$this, 'register_settings_page') );

		wp_oembed_add_provider( 'http://vids.myspace.com/*', 'http://vids.myspace.com/index.cfm?fuseaction=oembed' );

		wp_embed_register_handler( 'vvq_veoh_new', '#http://(www\.)?veoh\.com/(.*?)/watch/([0-9a-zA-Z]+).*#i', array(&$this, 'veoh_new') );
		wp_embed_register_handler( 'vvq_veoh_old', '#http://(www\.)?veoh\.com/videos/([0-9a-zA-Z]+).*#i', array(&$this, 'veoh_old') );
		wp_embed_register_handler( 'vvq_metacafe', '#http://(www\.)?metacafe\.com/watch/([0-9]+)/.*#i', array(&$this, 'metacafe') );
		wp_embed_register_handler( 'vvq_spike', '#http://(www.ifilm|ifilm|www.spike|spike)\.com/(.+)/(\d+).*#i', array(&$this, 'spike') );
		wp_embed_register_handler( 'vvq_flv', '#http(s)?://(.+)\.(flv|mp4|m4v|mp3)#i', array(&$this, 'flv'), 100 );

		if ( is_admin() ) {
			// Settings page only
			if ( isset($_GET['page']) && 'vipers-video-quicktags' == $_GET['page'] ) {
				add_action( 'admin_head', array(&$this, 'settings_page_css' ) );
				wp_enqueue_script( 'swfobject' );
				wp_enqueue_script( 'farbtastic' );
				wp_enqueue_style( 'farbtastic' );
			}
		}



		$shortcodes = array(
			'bliptv'       => 'shortcode_bliptv',
			'blip.tv'      => 'shortcode_bliptv',
			'dailymotion'  => false,
			'flickr video' => false,
			'flickrvideo'  => false,
			'googlevideo'  => false,
			'gvideo'       => false,
			'metacafe'     => false,
			'myspace'      => false,
			'stage6'       => 'shortcode_stage6', // Long dead, but better than showing the shortcodes
			'veoh'         => false,
			'viddler'      => false,
			'vimeo'        => false,
			'youtube'      => false,
			'flv'          => false,
		);

		// VideoPress support but only if the official plugin isn't installed
		if ( !function_exists('videopress_shortcode') && !isset($shortcode_tags['wpvideo']) )
			$shortcodes['wpvideo'] = 'shortcode_videopress';

		$this->shortcodes = (array) apply_filters( 'vvq_shortcodes', $shortcodes );


		// This is strictly here so that strip_shortcodes() works
		// run_shortcode() does the actual shortcode processing
		foreach ( $this->shortcodes as $shortcode => $callback )
			add_shortcode( $shortcode, array(&$this, 'dummy_shortcode_callback') );


		// Create default settings array
		$this->defaultsettings = (array) apply_filters( 'vvq_defaultsettings', array(
			'youtube' => array(
				'width'           => '',
				'height'          => '',
				'rel'             => 0,
				'autoplay'        => 0,
				'loop'            => 0,
				'border'          => 0,
				'color1'          => '#666666',
				'color2'          => '#EFEFEF',
				'fs'              => 1,
				'hd'              => 0,
				'showsearch'      => 0,
				'showinfo'        => 1,
				'iv_load_policy'  => 3,
				'cc_load_policy'  => 0,
				'previewurl'      => 'http://www.youtube.com/watch?v=bQgmdHzqtqA',
			),
			'googlevideo' => array(
				'width'           => 425,
				'height'          => 344,
				'autoplay'        => 0,
				'fs'              => 1,
				'previewurl'      => 'http://video.google.com/videoplay?docid=6264146989300141373',
			),
			'vimeo' => array(
				'width'           => '',
				'height'          => '',
				'color'           => '#00ADEF',
				'portrait'        => 1,
				'title'           => 1,
				'byline'          => 1,
				'fullscreen'      => 1,
				'previewurl'      => 'http://www.vimeo.com/4238052',
			),
			'dailymotion' => array(
				'width'           => 480,
				'height'          => 275,
				'backgroundcolor' => '#DEDEDE',
				'glowcolor'       => '#FFFFFF',
				'foregroundcolor' => '#333333',
				'seekbarcolor'    => '#FFC300',
				'autoplay'        => 0,
				'related'         => 0,
				'previewurl'      => 'http://www.dailymotion.com/video/x4cqyl_ferrari-p45-owner-exclusive-intervi_auto',
			),
			'viddler' => array(
				'width'           => '',
				'height'          => '',
			),
			'veoh' => array(
				'width'           => 410,
				'height'          => 341,
				'autoplay'        => 0,
			),
			'metacafe' => array(
				'width'           => 400,
				'height'          => 345,
			),
			'bliptv' => array(
				'width'           => '',
				'height'          => '',
			),
			'wpvideo' => array(
				'width'           => min( 640, $this->content_width ),
				'height'          => 360,
			),
			'flickr' => array(
				'width'           => '',
				'height'          => '',
				'showinfobox'     => 1,
			),
			'spike' => array(
				'width'           => 320,
				'height'          => 240,
			),
			'myspace' => array(
				'width'           => '',
				'height'          => '',
			),
			'flv' => array(
				'width'           => 400,
				'height'          => 300,
			),
			'feedwidth' => 500,
		) );

		// Grab the user's settings
		$usersettings = (array) get_option( 'vvq_options' );


		// For my blog until the settings page is working
		if ( 'http://www.viper007bond.com' == get_option( 'home' ) ) {
			$usersettings = $this->defaultsettings;
			//$usersettings['youtube']['color1'] = '#C2DC15';
			//$usersettings['youtube']['color2'] = '#C2DC15';
			//$usersettings['vimeo']['color'] = '#C2DC15';
		}


		/*
		// Upgrade settings
		$upgrade = false;
		if ( empty($usersettings['version']) )
			$usersettings['version'] = '1.0.0';
		if ( -1 == version_compare( $usersettings['version'], '6.1.0' ) ) {
			// Custom FLV colors
			$colors = array( 'backcolor', 'frontcolor', 'lightcolor', 'screencolor' );
			foreach ( $colors as $color ) {
				if ( !empty($usersettings['flv'][$color]) && $usersettings['flv'][$color] != $this->defaultsettings['flv'][$color] )
					$usersettings['flv']['customcolors'] = 1;
			}
			$upgrade = true;
		}
		if ( -1 == version_compare( $usersettings['version'], '6.1.23' ) ) {
			// Change default YouTube preview video to one supporting HD (rather than only HQ)
			if ( !empty($usersettings['youtube']) && !empty($usersettings['youtube']['previewurl']) && 'http://www.youtube.com/watch?v=stdJd598Dtg' === $usersettings['youtube']['previewurl'] )
				$usersettings['youtube']['previewurl'] = $this->defaultsettings['youtube']['previewurl'];
			$upgrade = true;
		}
		if ( -1 == version_compare( $usersettings['version'], '6.2.10' ) ) {
			if ( false !== strpos( $usersettings['customfeedtext'], '<p>' ) || false !== strpos( $usersettings['customfeedtext'], '</p>' ) )
				$usersettings['customfeedtext'] = str_replace( array( '<p>', '</p>' ), '', $usersettings['customfeedtext'] );
			$upgrade = true;
		}
		if ( -1 == version_compare( $usersettings['version'], '7.0.0' ) ) {
			// Reset width/heights
			foreach ( $usersettings as $type => $type_settings ) {
				if ( !is_array($type_settings) || ( !empty()
					continue;

				foreach ( $type_settings as $type_settings_key => $type_settings_value ) {

				}
			}
			$upgrade = true;
		}
		if ( $upgrade ) {
			$usersettings['version'] = $this->version;
			update_option( 'vvq_options', $usersettings );
		}
		**/

		// Merge the two using the defaults to fill in any missing values
		$this->settings = wp_parse_args( $usersettings, $this->defaultsettings );
	}


	// This function gets called when the minimum WordPress version isn't met
	function wp_version_too_old_notice() {
		echo '<div class="error"><p>' . sprintf( __( '<strong>Viper\'s Video Quicktags</strong> requires WordPress 2.9 or newer. Please <a href="%1$s">upgrade</a>! By not upgrading, your blog <a href="%2$s">may not be secure</a>!', 'vipers-video-quicktags' ), 'http://codex.wordpress.org/Upgrading_WordPress', 'http://wordpress.org/development/2009/09/keep-wordpress-secure/' ) . "</p></div>\n";
	}


	function register_settings_page() {
		add_options_page( __("Viper's Video Quicktags Configuration", 'vipers-video-quicktags'), __('Video Quicktags', 'vipers-video-quicktags'), 'manage_options', 'vipers-video-quicktags', array(&$this, 'settings_page_new') );
	}


	function head_css() {
		echo "	<style type='text/css'>.vvqbox { display: block; margin: 0 auto; }</style>\n";
	}


	function dummy_shortcode_callback( $atts, $content ) {
		return $content;
	}


	function run_shortcode( $content ) {
		global $shortcode_tags;

		// Backup current registered shortcodes and clear them all out
		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();

		foreach ( $this->shortcodes as $shortcode => $callback ) {
			if ( !$callback )
				$callback = 'shortcode_wrapper';
			add_shortcode( $shortcode, array(&$this, $callback) );
		}

		// Do the shortcode (only the [embed] one is registered)
		$content = do_shortcode( $content );

		// Put the original shortcodes back
		$shortcode_tags = $orig_shortcode_tags;

		return $content;
	}


	// Generate a placeholder ID
	function videoid( $type ) {
		global $post;

		$type = esc_attr( $type );

		if ( empty($post) || empty($post->ID) ) {
			$objectid = uniqid("vvq-$type-");
		} else {
			$count = 1;
			$objectid = 'vvq-' . $post->ID . '-' . $type . '-' . $count;

			while ( !empty($this->usedids[$objectid]) ) {
				$count++;
				$objectid = 'vvq-' . $post->ID . '-' . $type . '-' . $count;
			}

			$this->usedids[$objectid] = true;
		}

		return $objectid;
	}


	// Conditionally output debug error text
	function error( $error ) {
		global $post;

		// If the user can't edit this post, then just silently fail
		if ( empty($post->ID) || !current_user_can( 'edit_' . $post->post_type, $post->ID ) )
			return '';

		// But if this user is an admin, then display some helpful text
		return '<em>[' . sprintf( __('<strong>ERROR:</strong> %s', 'vipers-video-quicktags'), $error ) . ']</em>';
	}


	// parse_str() but allow periods in the keys
	// Also returns instead of setting a variable
	function parse_str_periods( $string ) {
		$string = str_replace( '.', '{{vvqperiod}}', $string );
		parse_str( $string, $result_raw );

		// Reset placeholders
		$result = array();
		foreach ( $result_raw as $key => $value ) {
			$key = str_replace( '{{vvqperiod}}', '.', $key );
			$result[$key] = str_replace( '{{vvqperiod}}', '.', $value );
		}

		return $result;
	}


	function extract_width_height( $html ) {
		if ( ! preg_match( '#width=("|\')([0-9]+)("|\')#i', $html, $width ) )
			return false;

		if ( ! preg_match( '#height=("|\')([0-9]+)("|\')#i', $html, $height ) )
			return false;

		return array(
			'width'  => esc_attr( intval( $width[2] ) ),
			'height' => esc_attr( intval( $height[2] ) ),
		);
	}


	function calculate_dims( $html, $atts ) {
		if ( !empty($atts['forcewidth']) && !empty($atts['forceheight']) ) {
			$dims = array(
				'width'  => $atts['forcewidth'],
				'height' => $atts['forceheight'],
			);
		} elseif ( !$dims = $this->extract_width_height( $html ) ) {
			return $html;
		}

		$dims = array_map( 'intval', $dims );

		return $dims;
	}


	function fix_ie_conditional( $content ) {
		$content = str_replace( '<!--[if !IE]>&#8211;>', '<!--[if !IE]>-->', $content );

		return $content;
	}


	function shortcode_wrapper( $atts, $url ) {
		global $wp_embed;

		if ( !empty($atts['width']) ) {
			$atts['forcewidth'] = (int) $atts['width'];
			unset($atts['width']);
		}

		if ( !empty($atts['height']) ) {
			$atts['forceheight'] = (int) $atts['height'];
			unset($atts['height']);
		}

		return $wp_embed->shortcode( $atts, $url );
	}


	function catch_supported_urls( $html, $url, $attr ) {
		$urlformats = array(
			'#http://(www\.)?youtube.com/watch.*#i' => 'youtube',
			'#http://(www\.)?vimeo\.com/.*#i'       => 'vimeo',
			//'#http://(www\.)?dailymotion\.com/.*#i' => 'dailymotion',
			'#http://(www\.)?viddler\.com/.*#i'     => 'viddler',
			'#http://blip.tv/file/.*#i'             => 'bliptv',
			'#http://(www\.)?flickr\.com/.*#i'      => 'flickr',
			'#http://vids\.myspace\.com/.*#i'       => 'myspace',

		);

		foreach ( $urlformats as $regex => $callback ) {
			if ( preg_match( $regex, $url ) )
				return call_user_func( array(&$this, $callback), $html, $url, $attr );
		}

		// Unsupported URL structure
		return $html;
	}


	function object_html( $swfurl, $dims, $type, $params = array() ) {

		// Generate an ID
		$objectid = $this->videoid( $type );

		$params['allowfullscreen'] = 'true';

		$paramsstring = '';
		foreach ( $params as $param_name => $param_value )
			$paramsstring .= '<param name="' . esc_attr( $param_name ) . '" value="' . esc_attr( $param_value ) . '" />';

		// In a feed, contrain the videos to a user-defined width
		if ( is_feed() && $dims['width'] > $this->settings['feedwidth'] )
			list( $dims['width'], $dims['height'] ) = wp_constrain_dimensions( $dims['width'], $dims['height'], $this->settings['feedwidth'] );

		$dims = array_map( 'intval', $dims ); // Super safe
		$swfurl = esc_url( $swfurl ); // Will be used multiple times, so validate it just once

		$html = '<span class="vvqbox vvq' . esc_attr( $type ) . '" style="width:' . $dims['width'] . 'px;height:' . $dims['height'] . 'px;"><object id="' . $objectid . '" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="' . $dims['width'] . '" height="' . $dims['height'] . '"><param name="movie" value="' . $swfurl . '" />' . $paramsstring . '<!--[if !IE]>--><object type="application/x-shockwave-flash" data="' . $swfurl . '" width="' . $dims['width'] . '" height="' . $dims['height'] . '">' . $paramsstring . '<!--<![endif]-->';
		
		//. esc_html( __( "You don't appear to have Flash installed or are using an out of date version. A recent version of Flash is required to view this video." ) ) . 
		
		$html .= '<!--[if !IE]>--></object><!--<![endif]--></object></span>';

		return $html;
	}


	function iframe_html( $iframeurl, $dims, $type, $fallbackurl = '' ) {

		// Generate an ID
		$objectid = $this->videoid( $type );

		// In a feed, contrain the videos to a user-defined width
		if ( is_feed() && $dims['width'] > $this->settings['feedwidth'] )
			list( $dims['width'], $dims['height'] ) = wp_constrain_dimensions( $dims['width'], $dims['height'], $this->settings['feedwidth'] );

		$dims = array_map( 'intval', $dims ); // Super safe

		$fallback = '';
		if ( ! empty( $fallbackurl ) )
			$fallback = '<a href="' . esc_url( $fallbackurl ) . '">' . esc_html( $fallbackurl ) . '</a>';

		$html = '<span class="vvqbox vvq' . esc_attr( $type ) . '" style="width:' . $dims['width'] . 'px;height:' . $dims['height'] . 'px;"><iframe src="' . esc_url( $iframeurl ) . '" width="' . $dims['width'] . '" height="' . $dims['height'] . '" frameborder="0">' . $fallback . '</iframe></span>';

		return $html;
	}


	function youtube( $html, $url, $atts ) {
		$origatts = $atts;

		// Set any missing $atts items to the defaults
		$atts = shortcode_atts( array(
			'forcewidth'     => $this->settings['youtube']['width'],
			'forceheight'    => $this->settings['youtube']['height'],
			'rel'            => $this->settings['youtube']['rel'],
			'autoplay'       => $this->settings['youtube']['autoplay'],
			'loop'           => $this->settings['youtube']['loop'],
			'border'         => $this->settings['youtube']['border'],
			'color1'         => $this->settings['youtube']['color1'],
			'color2'         => $this->settings['youtube']['color2'],
			'start'          => 0,
			'fs'             => $this->settings['youtube']['fs'],
			'hd'             => $this->settings['youtube']['hd'],
			'showsearch'     => $this->settings['youtube']['showsearch'],
			'showinfo'       => $this->settings['youtube']['showinfo'],
			'iv_load_policy' => $this->settings['youtube']['iv_load_policy'],
			'cc_load_policy' => $this->settings['youtube']['cc_load_policy'],
		), $atts );

		// Allow other plugins to modify these values (for example based on conditionals)
		$atts = apply_filters( 'vvq_shortcodeatts', $atts, 'youtube', $origatts );

		// Determine the width/height
		$dims = $this->calculate_dims( $html, $atts );

		// Get the YouTube video ID out of the HTML as we're going to completely redo it
		if ( ! preg_match( '#(http|https)://www.youtube.com/embed/([^?&"\']+)#i', $html, $videoid ) )
			return $html;

		// Start constructing the embed URL
		$embedurl = $videoid[1] . '://www.youtube.com/embed/' . $videoid[2];

		// Add user preferences as query args to the SWF URL
		// http://code.google.com/apis/youtube/player_parameters.html
		$embedurl = add_query_arg( 'rel',            $atts['rel'],            $embedurl );
		$embedurl = add_query_arg( 'autoplay',       $atts['autoplay'],       $embedurl );
		$embedurl = add_query_arg( 'loop',           $atts['loop'],           $embedurl );
		$embedurl = add_query_arg( 'border',         $atts['border'],         $embedurl );
		$embedurl = add_query_arg( 'start',          $atts['start'],          $embedurl );
		$embedurl = add_query_arg( 'fs',             $atts['fs'],             $embedurl );
		$embedurl = add_query_arg( 'hd',             $atts['hd'],             $embedurl );
		$embedurl = add_query_arg( 'showsearch',     $atts['showsearch'],     $embedurl );
		$embedurl = add_query_arg( 'showinfo',       $atts['showinfo'],       $embedurl );
		$embedurl = add_query_arg( 'iv_load_policy', $atts['iv_load_policy'], $embedurl );
		$embedurl = add_query_arg( 'cc_load_policy', $atts['cc_load_policy'], $embedurl );

		if ( '#666666' != $atts['color1'] )
			$embedurl = add_query_arg( 'color1', '0x' . str_replace( '#', '', $atts['color1'] ), $embedurl );

		if ( '#EFEFEF' != $atts['color2'] )
			$embedurl = add_query_arg( 'color2', '0x' . str_replace( '#', '', $atts['color2'] ), $embedurl );

		return $this->iframe_html( $embedurl, $dims, 'youtube', "http://www.youtube.com/watch?v={$videoid[1]}" );
	}


	function vimeo( $html, $url, $atts ) {
		global $wp_embed, $post;

		$origatts = $atts;

		// Set any missing $atts items to the defaults
		$atts = shortcode_atts( array(
			'forcewidth'  => $this->settings['vimeo']['width'],
			'forceheight' => $this->settings['vimeo']['height'],
			'color'       => $this->settings['vimeo']['color'],
			'portrait'    => $this->settings['vimeo']['portrait'],
			'title'       => $this->settings['vimeo']['title'],
			'byline'      => $this->settings['vimeo']['byline'],
			'fullscreen'  => $this->settings['vimeo']['fullscreen'],
		), $atts );

		// Allow other plugins to modify these values (for example based on conditionals)
		$atts = apply_filters( 'vvq_shortcodeatts', $atts, 'vimeo', $origatts );

		// If the old style <object> HTML is in the cache, flush it out and replace it with the better iframe HTML
		// Make sure there's no iframe just incase (to avoid continual cache flushes due to unforeseen circumstances)
		if ( !empty( $post->ID ) && false !== strpos( $html, '<object ' ) && false === strpos( $html, '<iframe ' ) ) {
			$wp_embed->delete_oembed_caches( $post->ID );
			$html = $wp_embed->shortcode( $origatts, $url ); // $origatts is important so we get the same MD5 hash
		}

		// Determine the width/height
		$dims = $this->calculate_dims( $html, $atts );

		// Get the SWf URL and Flashvars out of the HTML
		if ( ! preg_match( '#iframe([^"]+)? src="([^"]+)#i', $html, $parsed ) )
			return $html;
		$iframeurl = $parsed[2];

		// Setup the parameters
		$portrait   = ( 1 == $atts['portrait'] )   ? '1' : '0';
		$title      = ( 1 == $atts['title'] )      ? '1' : '0';
		$byline     = ( 1 == $atts['byline'] )     ? '1' : '0';
		$fullscreen = ( 1 == $atts['fullscreen'] ) ? '1' : '0';

		foreach ( array( 'title', 'byline', 'portrait', 'fullscreen' ) as $attribute ) {
			$iframeurl = add_query_arg( $attribute, $$attribute, $iframeurl );
		}

		if ( '' != $atts['color'] && $this->defaultsettings['vimeo']['color'] != $atts['color'] )
			$iframeurl = add_query_arg( 'color', str_replace( '#', '', $atts['color'] ), $iframeurl );

		return $this->iframe_html( $iframeurl, $dims, 'vimeo', "http://www.vimeo.com/{$videoid}" );
	}


	function dailymotion( $html, $url, $atts ) {
		$origatts = $atts;

		// Set any missing $atts items to the defaults
		$atts = shortcode_atts( array(
			'forcewidth'      => false,
			'forceheight'     => false,
			'width'           => $this->settings['dailymotion']['width'],
			'height'          => $this->settings['dailymotion']['height'],
			'backgroundcolor' => $this->settings['dailymotion']['backgroundcolor'],
			'glowcolor'       => $this->settings['dailymotion']['glowcolor'],
			'foregroundcolor' => $this->settings['dailymotion']['foregroundcolor'],
			'seekbarcolor'    => $this->settings['dailymotion']['seekbarcolor'],
			'autoplay'        => $this->settings['dailymotion']['autoplay'],
			'related'         => $this->settings['dailymotion']['related'],
		), $atts );

		// Allow other plugins to modify these values (for example based on conditionals)
		$atts = apply_filters( 'vvq_shortcodeatts', $atts, 'dailymotion', $origatts );

		// Determine the width/height
		if ( !empty($atts['forcewidth']) && !empty($atts['forceheight']) ) {
			$dims = array(
				'width'  => $atts['forcewidth'],
				'height' => $atts['forceheight'],
			);
		} elseif ( $dims = $this->extract_width_height( $html ) ) {
			// Scale the oEmbed response to actually fit our maxwidth/maxheight (DailyMotion ignores the values)
			list( $width, $height ) = wp_expand_dimensions( $dims['width'], $dims['height'], $atts['width'], $atts['height'] );
			$dims = array(
				'width'  => $width,
				'height' => $height,
			);
		} else {
			return $html;
		}
		$dims = array_map( 'intval', $dims );

		// Get the SWf URL out of the HTML
		if ( ! preg_match( '# src="([^"]+)#i', $html, $swfurl ) )
			return $html;
		$swfurl = $swfurl[1];

		// Add user preferences as query args to the SWF URL

		return $this->object_html( $swfurl, $dims, 'dailymotion' );
	}


	function viddler( $html, $url, $atts ) {
		$origatts = $atts;

		// Set any missing $atts items to the defaults
		$atts = shortcode_atts( array(
			'forcewidth'  => $this->settings['viddler']['width'],
			'forceheight' => $this->settings['viddler']['height'],
		), $atts );

		// Allow other plugins to modify these values (for example based on conditionals)
		$atts = apply_filters( 'vvq_shortcodeatts', $atts, 'viddler', $origatts );

		// Determine the width/height
		$dims = $this->calculate_dims( $html, $atts );

		// Get the SWf URL out of the HTML
		if ( ! preg_match( '# src="([^"]+)#i', $html, $swfurl ) )
			return $html;
		$swfurl = $swfurl[1];

		return $this->object_html( $swfurl, $dims, 'viddler' );
	}


	function shortcode_bliptv ( $atts, $url ) {
		global $content_width;

		if ( !empty($atts[0]) ) {
			$params = $this->parse_str_periods( $atts[0] );

			if ( !empty($params['?posts_id']) ) {
				$params['?posts_id'] = (int) $params['?posts_id'];
			}
		}

		if ( empty($params['?posts_id']) )
			return $this->error( __('Sorry, but the only Blip.tv short format that is supported is the WordPress.com-style format. You can find it on Blip.tv at Share -&gt; Embed -&gt; WordPress.com.', 'vipers-video-quicktags') );

		$atts['swfurl'] = 'http://blip.tv/scripts/flash/showplayer.swf?file=http://blip.tv/rss/flash/' . $params['?posts_id'];

		if ( !empty($atts['width']) ) {
			$atts['forcewidth'] = (int) $atts['width'];
			unset($atts['width']);
		} else {
			$atts['forcewidth'] = ( !empty($content_width) ) ? min( $content_width, 480 ) : 480;
		}

		if ( !empty($atts['height']) ) {
			$atts['forceheight'] = (int) $atts['height'];
			unset($atts['height']);
		} else {
			$atts['forceheight'] = 378;
		}

		return $this->bliptv( false, $url, $atts );
	}


	function bliptv( $html, $url, $atts ) {
		$origatts = $atts;

		// Set any missing $atts items to the defaults
		$atts = shortcode_atts( array(
			'forcewidth'  => $this->settings['bliptv']['width'],
			'forceheight' => $this->settings['bliptv']['height'],
			'swfurl'      => false,
		), $atts );

		// Allow other plugins to modify these values (for example based on conditionals)
		$atts = apply_filters( 'vvq_shortcodeatts', $atts, 'bliptv', $origatts );

		// Determine the width/height
		$dims = $this->calculate_dims( $html, $atts );

		if ( $atts['swfurl'] ) {
			$swfurl = $atts['swfurl'];
		} else {
			// Get the SWf URL out of the HTML
			if ( ! preg_match( '# src="([^"]+)#i', $html, $swfurl ) )
				return $html;
			$swfurl = $swfurl[1];
		}

		return $this->object_html( $swfurl, $dims, 'bliptv' );
	}


	function flickr( $html, $url, $atts ) {

		// Make sure this is a video and not an image
		if ( false === strpos( $html, '<object' ) )
			return $html;

		$origatts = $atts;

		// Set any missing $atts items to the defaults
		$atts = shortcode_atts( array(
			'forcewidth'  => $this->settings['flickr']['width'],
			'forceheight' => $this->settings['flickr']['height'],
			'showinfobox' => $this->settings['flickr']['showinfobox'],
		), $atts );

		// Allow other plugins to modify these values (for example based on conditionals)
		$atts = apply_filters( 'vvq_shortcodeatts', $atts, 'flickr', $origatts );

		// Determine the width/height
		$dims = $this->calculate_dims( $html, $atts );

		// Get the SWf URL out of the HTML
		if ( ! preg_match( '# src="([^"]+)#i', $html, $swfurl ) )
			return $html;
		$swfurl = $swfurl[1];

		// Get the flashvars out of the HTML
		if ( ! preg_match( '# flashvars="([^"]+)#i', $html, $flashvars ) )
			return $html;
		$flashvars = $flashvars[1];

		if ( !$atts['showinfobox'] )
			$flashvars = remove_query_arg( 'flickr_show_info_box', $flashvars );

		return $this->object_html( $swfurl, $dims, 'flickr', array( 'flashvars' => $flashvars ) );
	}


	function myspace( $html, $url, $atts ) {
		$origatts = $atts;

		// Set any missing $atts items to the defaults
		$atts = shortcode_atts( array(
			'forcewidth'  => $this->settings['myspace']['width'],
			'forceheight' => $this->settings['myspace']['height'],
		), $atts );

		// Allow other plugins to modify these values (for example based on conditionals)
		$atts = apply_filters( 'vvq_shortcodeatts', $atts, 'myspace', $origatts );

		// Determine the width/height
		$dims = $this->calculate_dims( $html, $atts );

		// Get the SWf URL out of the HTML
		if ( ! preg_match( '# src="([^"]+)#i', $html, $swfurl ) )
			return $html;
		$swfurl = $swfurl[1];

		return $this->object_html( $swfurl, $dims, 'myspace' );
	}


	function googlevideo( $html, $matches, $atts, $url, $origatts ) {

		// Set any missing $atts items to the defaults
		$atts = shortcode_atts( array(
			'forcewidth'  => false,
			'forceheight' => false,
			'width'       => $this->settings['googlevideo']['width'],
			'height'      => $this->settings['googlevideo']['height'],
			'fs'          => $this->settings['googlevideo']['fs'],
			'autoplay'    => $this->settings['googlevideo']['autoplay'],
		), $atts );

		// Allow other plugins to modify these values (for example based on conditionals)
		$atts = apply_filters( 'vvq_shortcodeatts', $atts, 'googlevideo', $origatts );

		// Determine the width/height
		if ( !empty($atts['forcewidth']) && !empty($atts['forceheight']) ) {
			$dims = array(
				'width'  => (int) $atts['forcewidth'],
				'height' => (int) $atts['forceheight'],
			);
		} else {
			$atts['width']  = (int) $atts['width'];
			$atts['height'] = (int) $atts['height'];
			list( $width, $height ) = wp_expand_dimensions( $this->settings['googlevideo']['width'], $this->settings['googlevideo']['height'], $atts['width'], $atts['height'] );
			$dims = array(
				'width'  => $width,
				'height' => $height,
			);
		}

		$swfurl = 'http://video.google.com/googleplayer.swf?docid=' . esc_attr( $matches[2] ) . '';

		if ( $atts['fs'] )
			$swfurl = add_query_arg( 'fs',       'true',            $swfurl );
		if ( $atts['autoplay'] )
			$swfurl = add_query_arg( 'autoplay', $atts['autoplay'], $swfurl );

		return $this->object_html( $swfurl, $dims, 'googlevideo' );
	}


	function veoh_old( $matches, $atts, $url, $origatts ) {
		if ( empty($matches[2]) )
			return $url;

		return $this->veoh( $matches[2], $atts, $url, $origatts );
	}

	
	function veoh_new( $matches, $atts, $url, $origatts ) {
		if ( empty($matches[3]) )
			return $url;

		return $this->veoh( $matches[3], $atts, $url, $origatts );
	}


	function veoh( $videoid, $atts, $url, $origatts ) {

		// Set any missing $atts items to the defaults
		$atts = shortcode_atts( array(
			'forcewidth'  => false,
			'forceheight' => false,
			'width'       => $this->settings['veoh']['width'],
			'height'      => $this->settings['veoh']['height'],
			'autoplay'    => $this->settings['veoh']['autoplay'],
		), $atts );

		// Allow other plugins to modify these values (for example based on conditionals)
		$atts = apply_filters( 'vvq_shortcodeatts', $atts, 'veoh', $origatts );

		// Determine the width/height
		if ( !empty($atts['forcewidth']) && !empty($atts['forceheight']) ) {
			$dims = array(
				'width'  => (int) $atts['forcewidth'],
				'height' => (int) $atts['forceheight'],
			);
		} else {
			$atts['width']  = (int) $atts['width'];
			$atts['height'] = (int) $atts['height'];
			list( $width, $height ) = wp_expand_dimensions( $this->settings['veoh']['width'], $this->settings['veoh']['height'], $atts['width'], $atts['height'] );
			$dims = array(
				'width'  => $width,
				'height' => $height,
			);
		}

		$swfurl = 'http://www.veoh.com/static/swf/webplayer/WebPlayer.swf?version=AFrontend.5.4.9.1004&player=videodetailsembedded&id=anonymous';
		$swfurl = add_query_arg( 'permalinkId', $videoid, $swfurl );
		$swfurl = add_query_arg( 'videoAutoPlay', $atts['autoplay'], $swfurl );

		return $this->object_html( $swfurl, $dims, 'veoh' );
	}


	function metacafe( $matches, $atts, $url, $origatts ) {
		if ( empty($matches[2]) )
			return $url;

		// Set any missing $atts items to the defaults
		$atts = shortcode_atts( array(
			'forcewidth'  => false,
			'forceheight' => false,
			'width'       => $this->settings['metacafe']['width'],
			'height'      => $this->settings['metacafe']['height'],
		), $atts );

		// Allow other plugins to modify these values (for example based on conditionals)
		$atts = apply_filters( 'vvq_shortcodeatts', $atts, 'metacafe', $origatts );

		// Determine the width/height
		if ( !empty($atts['forcewidth']) && !empty($atts['forceheight']) ) {
			$dims = array(
				'width'  => (int) $atts['forcewidth'],
				'height' => (int) $atts['forceheight'],
			);
		} else {
			$atts['width']  = (int) $atts['width'];
			$atts['height'] = (int) $atts['height'];
			list( $width, $height ) = wp_expand_dimensions( $this->settings['metacafe']['width'], $this->settings['metacafe']['height'], $atts['width'], $atts['height'] );
			$dims = array(
				'width'  => $width,
				'height' => $height,
			);
		}

		return $this->object_html( 'http://www.metacafe.com/fplayer/' . $matches[2] . '/vipers_video_quicktags.swf', $dims, 'metacafe' );
	}


	function spike( $matches, $atts, $url, $origatts ) {
		if ( empty($matches[3]) )
			return $url;

		// Set any missing $atts items to the defaults
		$atts = shortcode_atts( array(
			'forcewidth'  => false,
			'forceheight' => false,
			'width'       => $this->settings['spike']['width'],
			'height'      => $this->settings['spike']['height'],
		), $atts );

		// Allow other plugins to modify these values (for example based on conditionals)
		$atts = apply_filters( 'vvq_shortcodeatts', $atts, 'spike', $origatts );

		// Determine the width/height
		if ( !empty($atts['forcewidth']) && !empty($atts['forceheight']) ) {
			$dims = array(
				'width'  => (int) $atts['forcewidth'],
				'height' => (int) $atts['forceheight'],
			);
		} else {
			$atts['width']  = (int) $atts['width'];
			$atts['height'] = (int) $atts['height'];
			list( $width, $height ) = wp_expand_dimensions( $this->settings['spike']['width'], $this->settings['spike']['height'], $atts['width'], $atts['height'] );
			$dims = array(
				'width'  => $width,
				'height' => $height,
			);
		}

		return $this->object_html( 'http://www.spike.com/efp', $dims, 'spike', array( 'flashvars' => 'flvbaseclip=' . $matches[3] ) );
	}


	function shortcode_videopress( $atts ) {
		$origatts = $atts;

		// Set any missing $atts items to the defaults
		$atts = shortcode_atts(array(
			0        => '',
			'w'      => false,
			'width'  => false,
			'h'      => false,
			'height' => false,
		), $atts);

		// Allow other plugins to modify these values (for example based on conditionals)
		$atts = apply_filters( 'vvq_shortcodeatts', $atts, 'videopress', $origatts );

		if ( empty($atts[0]) )
			return $this->error( __('An invalid VideoPress shortcode format was used. Are you sure you copy/pasted it correctly?', 'vipers-video-quicktags') );

		$atts['w']      = (int) $atts['w'];
		$atts['h']      = (int) $atts['h'];

		$dims = array();
		$dims['width']  = (int) $atts['width'];
		$dims['height'] = (int) $atts['height'];

		if ( $atts['w'] )
			$dims['width'] = $atts['w'];

		if ( !$dims['width'] )
			$dims['width'] = $this->settings['wpvideo']['width'];

		if ( $atts['h'] )
			$dims['height'] = $atts['h'];

		if ( !$dims['height'] )
			$dims['height'] = round( ( $dims['width'] / $this->settings['wpvideo']['width'] ) * $this->settings['wpvideo']['height'] );

		return $this->object_html( 'http://v.wordpress.com/' . $atts[0], $dims, 'wpvideo' );
	}


	function flv( $matches, $atts, $url, $origatts ) {
		// Set any missing $atts items to the defaults
		$atts = shortcode_atts( array(
			'forcewidth'  => false,
			'forceheight' => false,
			'width'       => $this->settings['flv']['width'],
			'height'      => $this->settings['flv']['height'],
			'image'       => false,
		), $atts );

		// Allow other plugins to modify these values (for example based on conditionals)
		$atts = apply_filters( 'vvq_shortcodeatts', $atts, 'flv', $origatts );

		// Determine the width/height
		if ( !empty($atts['forcewidth']) && !empty($atts['forceheight']) ) {
			$dims = array(
				'width'  => (int) $atts['forcewidth'],
				'height' => (int) $atts['forceheight'],
			);
		} else {
			$atts['width']  = (int) $atts['width'];
			$atts['height'] = (int) $atts['height'];
			list( $width, $height ) = wp_expand_dimensions( $this->settings['flv']['width'], $this->settings['flv']['height'], $atts['width'], $atts['height'] );
			$dims = array(
				'width'  => $width,
				'height' => $height,
			);
		}

		// WordPress is smart enough to only output this script once
		wp_print_scripts( array( 'flowplayer' ) );

		$videoid = $this->videoid( 'flv' );

		$html = '<span class="vvqbox vvqflv"><a href="' . esc_attr( $url ) . '" id="' . $videoid . '" style="display:block;width:' . $dims['width'] . 'px;height:' . $dims['height'] . 'px">' . esc_html( $url ) . '</a></span><script type="text/javascript">
document.getElementById("' . $videoid . '").innerHTML = "";
flowplayer("' . $videoid . '", "' . plugins_url( 'flowplayer/flowplayer-3.2.2.swf', __FILE__ ) . '", {
	clip: {
		autoPlay: true,
		accelerated: true,
		scaling: "fit"
	},
	playlist: [
';
		if ( ! empty( $atts['image'] ) )
			$html .= '		{url: "' . esc_js( $atts['image'] ) . '", scaling: "orig"},' . "\n";

		$html .= '		{url: "' . esc_js( $url ) . '", autoPlay: false}
	]
});</script>';

		return $html;
	}


	// Show an error for Stage6 shortcodes
	function shortcode_stage6() {
		return '<em>[' . __( 'Stage6.com shut down a long time ago, so this Stage6-hosted video cannot be displayed.', 'vipers-video-quicktags' ) . ']</em>';
	}


	function swfobject_calls() {
		if ( empty($this->usedids) )
			return;

		wp_print_scripts( array( 'swfobject' ) );

		echo "<script type='text/javascript'>\n";
		foreach ( $this->usedids as $id => $unused )
			echo "	swfobject.registerObject( '$id', '9.0.115' );\n";
		echo "</script>\n";
	}


	function settings_page_new() {
		$tab = ( !empty($_GET['tab']) ) ? $_GET['tab'] : 'general';

		if ( !empty($_GET['defaults']) ) : ?>
<div id="message" class="updated fade"><p><strong><?php _e('Settings for this section have been reset to the defaults.', 'vipers-video-quicktags'); ?></strong></p></div>
<?php endif; ?>

<div class="wrap">

<?php screen_icon(); ?>
	<h2><?php _e( "Viper's Video Quicktags", 'vipers-video-quicktags' ); ?></h2>

	<ul class="subsubsub">
<?php
		$tabs = array(
			//'additional'  => __('Additional Settings', 'vipers-video-quicktags'),
			'youtube'     => __('YouTube', 'vipers-video-quicktags'),
			'googlevideo' => __('Google Video', 'vipers-video-quicktags'),
			'dailymotion' => __('DailyMotion', 'vipers-video-quicktags'),
			'vimeo'       => __('Vimeo', 'vipers-video-quicktags'),
			'flv'         => __('Flash Video (FLV)', 'vipers-video-quicktags'),
			'help'        => __('Additional Help', 'vipers-video-quicktags'),
			'credits'     => __('Credits', 'vipers-video-quicktags'),
		);
		$tabhtml = array();

		// If someone wants to remove a tab (for example on a WPMU intall)
		$tabs = apply_filters( 'vvq_tabs', $tabs );

		$class = ( 'general' == $tab ) ? ' class="current"' : '';
		$tabhtml[] = '		<li><a href="' . admin_url( 'options-general.php?page=vipers-video-quicktags' ) . '"' . $class . '>' . __('Overview', 'vipers-video-quicktags') . '</a>';

		foreach ( $tabs as $stub => $title ) {
			$class = ( $stub == $tab ) ? ' class="current"' : '';
			$tabhtml[] = '		<li><a href="' . admin_url( 'options-general.php?page=vipers-video-quicktags&amp;tab=' . $stub ) . '"' . $class . '>' . esc_html( $title ) . '</a>';
		}

		echo implode( " |</li>\n", $tabhtml ) . '</li>';
?>

	</ul>

	<form id="vvqsettingsform" method="post" action="" style="margin-top:50px">

	<?php wp_nonce_field('vipers-video-quicktags'); ?>

	<input type="hidden" name="action" value="vvqsettings" />

	<script type="text/javascript">
	// <![CDATA[
		jQuery(document).ready(function($) {
			// Confirm pressing of the "reset tab to defaults" button
			$("#vvq-defaults").click(function(){
				var areyousure = confirm("<?php echo esc_js( __("Are you sure you want to reset this tab's settings to the defaults?", 'vipers-video-quicktags') ); ?>");
				if ( true != areyousure ) return false;
			});
		});
	// ]]>
	</script>

<?php

	// Figure out which tab to output
	switch ( $tab ) :

		case 'youtube':
?>
		<h3>YouTube Defaults</h3>

		<p>These are only the defaults for YouTube embeds. All of these parameters can be overridden on a per-embed basis. See the <a href="<?php echo esc_attr( admin_url( 'options-general.php?page=vipers-video-quicktags&tab=help' ) ); ?>">Additional Help</a> section for more details.</p>




<?php
			break;

		case 'general':
		default;
?>

		<h3>Welcome!</h3>

		<p>Thanks for using Viper's Video Quicktags! This page will give you an overview on how to use this plugin.</p>

		<p>This plugin builds on top of the <a href="http://codex.wordpress.org/Embeds">native WordPress embeds feature</a> which allows you to embed multimedia (video, images, and more) from various websites. All you have to do is paste the URL to an item (such as <code>http://www.youtube.com/watch?v=rs-jAImScms</code>) on it's own line in a post or a page. It needs to not be hyperlinked (clickable). Once that is done, the URL will be converted into an embed if that website is supported by WordPress.</p>

		<p>You may be asking at this point what the point of my plugin is now that WordPress natively supports video embedding. Simple &#8212; my plugin allows you to customize that embed. Examples are things like autoplay or even just simple things such as the colors of the player (customization ability depends entirely on what the embed player itself supports). My plugin also makes all of the embed types it supports XHTML valid (most standard embeds will not validate).</p>

		<p>You can use this plugin right out of the box with no customizations and it will affect all previous embeds made using the above mentioned WordPress method or previous versions of my plugin. Note however that any embeds you created by manually copying and pasting embed HTML will not be affected.</p>

		<p>If you want to customize your video embeds though, check out the various sections listed above. Those video sites support customizing their player to better match your preferences. If you'd like something easy to start with, consider making the YouTube player match your site's color scheme using the <a href="<?php echo esc_attr( admin_url( 'options-general.php?page=vipers-video-quicktags&tab=youtube' ) ); ?>">YouTube</a> section.</p>

		<p>If you need more help, please see the <a href="<?php echo esc_attr( admin_url( 'options-general.php?page=vipers-video-quicktags&tab=help' ) ); ?>">Additional Help</a> section or post a thread on the <a href="http://wordpress.org/tags/vipers-video-quicktags?forum_id=10#postform">WordPress.org forums</a> (the official forums for support of my plugins).</p>
<?php

	endswitch; // $tab

?>

	</form>
</div>

<?php
	}


	/*
	 * This is largely just copy/pasted from v6.x of the plugin. As such, it may suck and likely needs a recode.
	 * I just wanted to get the plugin out the door though, so it'll do for now.
	 * I'll probably eventually make the live previews AJAX based or something
	 */
	function settings_page() {
		$tab = ( !empty($_GET['tab']) ) ? $_GET['tab'] : 'general';

		if ( !empty($_GET['defaults']) ) : ?>
<div id="message" class="updated fade"><p><strong><?php _e('Settings for this section have been reset to the defaults.', 'vipers-video-quicktags'); ?></strong></p></div>
<?php endif; ?>

<div class="wrap">

<?php screen_icon(); ?>
	<h2><?php _e( "Viper's Video Quicktags", 'vipers-video-quicktags' ); ?></h2>

	<ul class="subsubsub">
<?php
		$tabs = array(
			//'additional'  => __('Additional Settings', 'vipers-video-quicktags'),
			'youtube'     => __('YouTube', 'vipers-video-quicktags'),
			//'googlevideo' => __('Google Video', 'vipers-video-quicktags'),
			//'dailymotion' => __('DailyMotion', 'vipers-video-quicktags'),
			//'vimeo'       => __('Vimeo', 'vipers-video-quicktags'),
			//'flv'         => __('Flash Video (FLV)', 'vipers-video-quicktags'),
			//'help'        => __('Additional Help', 'vipers-video-quicktags'),
			//'credits'     => __('Credits', 'vipers-video-quicktags'),
		);
		$tabhtml = array();

		// If someone wants to remove a tab (for example on a multi-site intall)
		$tabs = apply_filters( 'vvq_tabs', $tabs );

		$class = ( 'general' == $tab ) ? ' class="current"' : '';
		$tabhtml[] = '		<li><a href="' . admin_url( 'options-general.php?page=vipers-video-quicktags' ) . '"' . $class . '>' . __('Overview', 'vipers-video-quicktags') . '</a>';

		foreach ( $tabs as $stub => $title ) {
			$class = ( $stub == $tab ) ? ' class="current"' : '';
			$tabhtml[] = '		<li><a href="' . admin_url( 'options-general.php?page=vipers-video-quicktags&amp;tab=' . $stub ) . '"' . $class . '>' . esc_html( $title ) . '</a>';
		}

		echo implode( " |</li>\n", $tabhtml ) . '</li>';
?>

	</ul>

	<form id="vvqsettingsform" method="post" action="">

	<?php wp_nonce_field('vipers-video-quicktags'); ?>

	<input type="hidden" name="action" value="vvqsettings" />

	<script type="text/javascript">
	// <![CDATA[
		jQuery(document).ready(function() {
			// Confirm pressing of the "reset tab to defaults" button
			jQuery("#vvq-defaults").click(function(){
				var areyousure = confirm("<?php echo esc_js( __("Are you sure you want to reset this tab's settings to the defaults?", 'vipers-video-quicktags') ); ?>");
				if ( true != areyousure ) return false;
			});
		});
	// ]]>
	</script>

<?php
	// For the video configuration tabs, output the common Javascript
	if ( !in_array( $tab, array( 'general', 'additional', 'help', 'credits' ) ) ) :
?>
	<p><?php printf(
		__('Set the defaults for this video type here. All of these settings can be overridden on individual embeds. See the <a href="%s">Help section</a> for details.', 'vipers-video-quicktags'),
		admin_url( 'options-general.php?page=vipers-video-quicktags&amp;tab=help#vvq-parameters' )
	); ?></p>

<?php if ( false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') ) : ?>
	<p><?php printf( __('Please consider using a browser other than Internet Explorer though. Due to limitations with your browser, these configuration pages won\'t be as full featured as if you were using a brower such as <a href="%1$s">Firefox</a> or <a href="%2$s">Opera</a>. If you switch, you\'ll be able to see the video preview update live as you change <strong>any</strong> option (rather than just a very limited number options) and more.', 'vipers-video-quicktags'), 'http://www.mozilla.com/firefox/', 'http://www.opera.com/' ); ?></p>

<?php endif; // endif for MSIE ?>
	<script type="text/javascript">
	// <![CDATA[
		jQuery(document).ready(function() {
			var vvqflashvars = {};
			var vvqparams = { wmode: "transparent", allowfullscreen: "true", allowscriptaccess: "always" };
			var vvqattributes = {};
			var vvqexpressinstall = "<?php echo plugins_url('/vipers-video-quicktags/resources/expressinstall.swf'); ?>";


			/* Color picker code based on code stolen with permission from Ozh's "Liz Comment Counter" */

			// Add a color picker to every .vvq-picker
			jQuery(".vvq-picker").each(function(){
				var id = jQuery(this).attr("id");
				var target = id.replace(/-picker/, "");
				jQuery(this).farbtastic("#"+target);
			});

			// Add the toggling behavior to .vvq-swatch
			jQuery(".vvq-swatch").click(function(){
				var id = jQuery(this).attr("id");
				var target = id.replace(/swatch/, "picker-wrap");
				VVQHideOtherColorPickers(target);
				var display = jQuery("#"+target).css("display");
				(display == "block") ? jQuery("#"+target).fadeOut(100) : jQuery("#"+target).fadeIn(100);
				var bg = (display == "block") ? "0px 0px" : "0px -24px";
				jQuery(this).css("background-position", bg);
			});

			// Use pretty tooltips if available
			if ( typeof jQuery.tTips != "undefined" ) {
				jQuery(".vvq-swatch").tTips();
			}

			// Close color pickers when click on the document. This function is hijacked by Farbtastic's event when a color picker is open.
			// If the color swatch was the thing that was clicked, don't do anything and let it close.
			var colorswatch = false;
			jQuery(".vvq-swatch").mousedown(function(){
				// Oh, the swatch was clicked. Tell the document clicker to abort.
				colorswatch = true;
				// If we used the swatch to close the color picker, update the preview
				var id = jQuery(this).attr("id");
				var picker = id.replace(/swatch/, "picker-wrap");
				var display = jQuery("#"+picker).css("display");
				if (display == "block") {
					VVQUpdatePreview();
				}
			});
			jQuery(document).mousedown(function(){
				// Was the swatch clicked? If so, abort.
				if ( true == colorswatch ) return;
				VVQHideOtherColorPickers();
			});
			jQuery(document).mouseup(function(){
				// Reset everything
				colorswatch = false;
			});

			// Close color pickers except "what"
			function VVQHideOtherColorPickers(what) {
				jQuery(".vvq-picker-wrap").each(function(){
					var id = jQuery(this).attr("id");
					var display = jQuery(this).css("display");
					if (id == what) {
						return;
					}
					if ("block" == display) {
						VVQUpdatePreview();
						jQuery(this).fadeOut(100);
						var swatch = id.replace(/picker-wrap/, "swatch");
						jQuery("#"+swatch).css("background-position", "0px 0px");
					}
				});
			}

			// rgb(1, 2, 3) -> #010203
			// Stolen from Ozh's "Liz Comment Counter"
			function VVQRGBtoHex(color) {
				var color = color.replace(/rgb\(|\)| /g,"").split(","); // ["1","2","3"]
				return "#" + VVQ_array_RGBtoHex(color[0],color[1],color[2]);
			}

			// From: http://www.linuxtopia.org/online_books/javascript_guides/javascript_faq/RGBtoHex.htm
			function VVQ_array_RGBtoHex(R,G,B) {return VVQ_toHex(R)+VVQ_toHex(G)+VVQ_toHex(B)}
			function VVQ_toHex(N) {
				if (N==null) return "00";
				N=parseInt(N);
				if (N==0 || isNaN(N)) return "00";
				N=Math.max(0,N);
				N=Math.min(N,255);
				N=Math.round(N);
				return "0123456789ABCDEF".charAt((N-N%16)/16) + "0123456789ABCDEF".charAt(N%16);
			}


			/* Set up the video preview */

			// Setup the preview on page load
			VVQUpdatePreview();

			// Call update preview function when form is changed
			jQuery("#vvqsettingsform input, #vvqsettingsform select").change(function(){
				if (jQuery.browser.msie) return; // IE sucks and doesn't work right
				VVQUpdatePreview();
			});

			// Handle keeping the dimensions in the correct ratio
			jQuery("#vvq-width").change(function(){
				if ( true != jQuery("#vvq-aspectratio").attr("checked") ) return;
				var width = jQuery("#vvq-width").val();
				var widthdefault = jQuery("#vvq-width-default").val();
				if ( '' == width || 0 == width ) {
					width = widthdefault;
					jQuery("#vvq-width").val(widthdefault);
				}
				jQuery("#vvq-height").val( Math.round( width * ( jQuery("#vvq-height-default").val() / widthdefault ) ) );
				VVQUpdatePreview();
			});
			jQuery("#vvq-height").change(function(){
				if ( true != jQuery("#vvq-aspectratio").attr("checked") ) return;
				var height = jQuery("#vvq-height").val();
				var heightdefault = jQuery("#vvq-height-default").val();
				if ( '' == height || 0 == height ) {
					height = heightdefault;
					jQuery("#vvq-height").val(heightdefault);
				}
				jQuery("#vvq-width").val( Math.round( height * ( jQuery("#vvq-width-default").val() / heightdefault ) ) );
				VVQUpdatePreview();
			});

			// When called, updates the preview
			function VVQUpdatePreview() {
<?php
	// </script><?php // For my stupid text editor

	endif; // Endif for multiple tab JS


	// Figure out which tab to output
	switch ( $tab ) {

		case 'youtube':
?>
				// <script> // For my stupid text editor

				// Get the colors, transform to uppercase, and then set the inputs with the uppercase value
				var Color1Val = jQuery("#vvq-youtube-color1").val().toUpperCase();
				var Color2Val = jQuery("#vvq-youtube-color2").val().toUpperCase();
				jQuery("#vvq-youtube-color1").val(Color1Val);
				jQuery("#vvq-youtube-color2").val(Color2Val);

				// Parse the URL
				var PreviewID = jQuery("#vvq-previewurl").val().match(/http:\/\/www\.(www.youtube|youtube|[A-Za-z]{2}.youtube)\.com\/(watch\?v=|w\/\?v=)([\w-]+)(.*?)/);
				if ( !PreviewID ) {
					jQuery("#vvqvideopreview-container").html('<div id="vvqvideopreview"><?php echo $this->esc_js( __("Unable to parse preview URL. Please make sure it's the <strong>full</strong> URL and a valid one at that.", 'vipers-video-quicktags') ); ?></div>');
					return;
				}
				var PreviewID = PreviewID[3];

				// Generate the URL and do the preview
				var Color1 = "";
				var Color2 = "";
				var FS = "";
				var Border = "";
				var Autoplay = "";
				var Loop = "";
				var ShowSearch = "";
				var ShowInfo = "";
				var HD = "";
				if ( "" != Color1Val && "<?php echo $this->defaultsettings['youtube']['color1']; ?>" != Color1Val ) var Color1 = "&color1=0x" + Color1Val.replace(/#/, "");
				if ( "" != Color2Val && "<?php echo $this->defaultsettings['youtube']['color2']; ?>" != Color2Val ) var Color2 = "&color2=0x" + Color2Val.replace(/#/, "");
				if ( true == jQuery("#vvq-youtube-border").attr("checked") ) { var Border = "&border=1"; }
				if ( true == jQuery("#vvq-youtube-rel").attr("checked") ) { var Rel = "1"; } else { var Rel = "0"; }
				if ( true == jQuery("#vvq-youtube-fs").attr("checked") ) { var FS = "&fs=1"; }
				if ( true == jQuery("#vvq-youtube-hd").attr("checked") ) { var HD = "&hd=1"; }
				if ( true == jQuery("#vvq-youtube-autoplay").attr("checked") ) { var Autoplay = "&autoplay=1"; }
				if ( true == jQuery("#vvq-youtube-loop").attr("checked") ) { var Loop = "&loop=1"; }
				if ( true == jQuery("#vvq-youtube-showsearch").attr("checked") ) { var ShowSearch = "1"; } else { var ShowSearch = "0"; }
				if ( true == jQuery("#vvq-youtube-showinfo").attr("checked") ) { var ShowInfo = "1"; } else { var ShowInfo = "0"; }
				swfobject.embedSWF(
					"http://www.youtube.com/v/" + PreviewID + Color1 + Color2 + Autoplay + Loop + Border + "&rel=" + Rel + "&showsearch=" + ShowSearch + "&showinfo=" + ShowInfo + FS + HD,
					"vvqvideopreview",
					"640",
					"385",
					"9",
					vvqexpressinstall,
					vvqflashvars,
					vvqparams,
					vvqattributes
				);
			}


			/* Color presets which is also based on code stolen from Ozh's "Liz Comment Counter" */

			// Make the presets
			VVQMakeYouTubePresets();
			function VVQMakeYouTubePresets() {
				var presets = {
					"<?php echo esc_js( __('Default', 'vipers-video-quicktags') ); ?>": ["<?php echo $this->defaultsettings['youtube']['color1']; ?>", "<?php echo $this->defaultsettings['youtube']['color2']; ?>"],
					"<?php echo esc_js( __('Dark Grey', 'vipers-video-quicktags') ); ?>": ["#3A3A3A", "#999999"],
					"<?php echo esc_js( __('Dark Blue', 'vipers-video-quicktags') ); ?>": ["#2B405B", "#6B8AB6"],
					"<?php echo esc_js( __('Light Blue', 'vipers-video-quicktags') ); ?>": ["#006699", "#54ABD6"],
					"<?php echo esc_js( __('Green', 'vipers-video-quicktags') ); ?>": ["#234900", "#4E9E00"],
					"<?php echo esc_js( __('Orange', 'vipers-video-quicktags') ); ?>": ["#E1600F", "#FEBD01"],
					"<?php echo esc_js( __('Pink', 'vipers-video-quicktags') ); ?>": ["#CC2550", "#E87A9F"],
					"<?php echo esc_js( __('Purple', 'vipers-video-quicktags') ); ?>": ["#402061", "#9461CA"],
					"<?php echo esc_js( __('Ruby Red', 'vipers-video-quicktags') ); ?>": ["#5D1719", "#CD311B"]
				};
				jQuery("#vvq-youtube-presets").html("");
				for (var i in presets) {
					var fg = presets[i][0];
					var bg = presets[i][1];
					jQuery("#vvq-youtube-presets").append('<div class="vvq-preset" style="color:'+fg+';background:'+bg+';border-top:10px solid '+fg+';border-right:10px solid '+bg+';border-bottom:10px solid '+bg+';border-left:10px solid '+fg+';" title="'+i+'"></div> ');
				}
			}

			// Update the color inputs when a preset is clicked
			jQuery(".vvq-preset").click(function(){
				var color1 = jQuery(this).css('color');
				var color2 = jQuery(this).css('backgroundColor');

				// Opera and IE return hex already, but we need to convert RGB to hex for Firefox, Safari, etc.
				if ( -1 == color1.search(/#/) ) {
					var color1 = VVQRGBtoHex( color1 );
					var color2 = VVQRGBtoHex( color2 );
				}

				if (color1 != undefined) { jQuery('#vvq-youtube-color1').val(color1).keyup(); }
				if (color2 != undefined) { jQuery('#vvq-youtube-color2').val(color2).keyup(); }

				VVQUpdatePreview();
			});
		});
	// ]]>
	</script>

	<input type="hidden" name="vvq-tab" value="youtube" />

	<table class="form-table">
		<tr valign="top" class="hide-if-no-js">
			<th scope="row"><?php _e('Preview', 'vipers-video-quicktags'); ?></th>
			<td>
				<div id="vvqvideopreview-container" style="min-height:385px">
					<div id="vvqvideopreview"><?php _e('Loading...', 'vipers-video-quicktags'); ?></div>
				</div>
			</td>
		</tr>
		<tr valign="top" class="hide-if-no-js">
			<th scope="row"><label for="vvq-previewurl"><?php _e('Preview URL', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<input type="text" name="vvq-youtube-previewurl" id="vvq-previewurl" value="<?php echo esc_attr($this->settings['youtube']['previewurl']); ?>" class="vvqwide" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="vvq-youtube-color1"><?php _e('Border Color', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<input type="text" name="vvq-youtube-color1" id="vvq-youtube-color1" value="<?php echo esc_attr($this->settings['youtube']['color1']); ?>" maxlength="7" size="7" class="vvqnarrow" />
				&nbsp;<span class="vvq-swatch hide-if-no-js" id="vvq-youtube-color1-swatch" title="<?php _e('Pick a color', 'vipers-video-quicktags'); ?>">&nbsp;</span>
				<div class="vvq-picker-wrap hide-if-no-js" id="vvq-youtube-color1-picker-wrap"><div class="vvq-picker" id="vvq-youtube-color1-picker"></div></div>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="vvq-youtube-color2"><?php _e('Fill Color', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<input type="text" name="vvq-youtube-color2" id="vvq-youtube-color2" value="<?php echo esc_attr($this->settings['youtube']['color2']); ?>" maxlength="7" size="7" class="vvqnarrow" />
				&nbsp;<span class="vvq-swatch hide-if-no-js" id="vvq-youtube-color2-swatch" title="<?php _e('Pick a color', 'vipers-video-quicktags'); ?>">&nbsp;</span>
				<div class="vvq-picker-wrap hide-if-no-js" id="vvq-youtube-color2-picker-wrap"><div class="vvq-picker" id="vvq-youtube-color2-picker"></div></div>
			</td>
		</tr>
		<tr valign="top" class="hide-if-no-js">
			<th scope="row"><?php _e('Color Presets', 'vipers-video-quicktags'); ?></th>
			<td id="vvq-youtube-presets">&nbsp;</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Miscellaneous', 'vipers-video-quicktags'); ?></th>
			<td>
				<label><input type="checkbox" name="vvq-youtube-hd" id="vvq-youtube-hd" value="1"<?php checked($this->settings['youtube']['hd'], 1); ?> /> <?php _e("Enable 720p/1080p quality by default (480p quality cannot be enabled by default and not all videos are avilable in HD)", 'vipers-video-quicktags'); ?></label><br />
				<label><input type="checkbox" name="vvq-youtube-fs" id="vvq-youtube-fs" value="1"<?php checked($this->settings['youtube']['fs'], 1); ?> /> <?php _e('Show fullscreen button', 'vipers-video-quicktags'); ?></label><br />
				<label><input type="checkbox" name="vvq-youtube-border" id="vvq-youtube-border" value="1"<?php checked($this->settings['youtube']['border'], 1); ?> /> <?php _e('Show border', 'vipers-video-quicktags'); ?></label><br />
				<label><input type="checkbox" name="vvq-youtube-showinfo" id="vvq-youtube-showinfo" value="1"<?php checked($this->settings['youtube']['showinfo'], 1); ?> /> <?php _e('Show the video title and rating', 'vipers-video-quicktags'); ?></label><br />
				<label><input type="checkbox" name="vvq-youtube-rel" id="vvq-youtube-rel" value="1"<?php checked($this->settings['youtube']['rel'], 1); ?> /> <?php _e('Show related videos at the end of playback', 'vipers-video-quicktags'); ?></label><br />
				<label><input type="checkbox" name="vvq-youtube-showsearch" id="vvq-youtube-showsearch" value="1"<?php checked($this->settings['youtube']['showsearch'], 1); ?> /> <?php _e('Show the search box', 'vipers-video-quicktags'); ?></label><br />
				<label><input type="checkbox" name="vvq-youtube-autoplay" id="vvq-youtube-autoplay" value="1"<?php checked($this->settings['youtube']['autoplay'], 1); ?> /> <?php _e('Autoplay video (not recommended)', 'vipers-video-quicktags'); ?></label><br />
				<label><input type="checkbox" name="vvq-youtube-loop" id="vvq-youtube-loop" value="1"<?php checked($this->settings['youtube']['loop'], 1); ?> /> <?php _e('Loop video playback', 'vipers-video-quicktags'); ?></label>
			</td>
		</tr>
	</table>
<?php
			break; // End YouTube

		case 'googlevideo': ?>
				// <script>
				jQuery("#vvqvideopreview-container").css( "min-height", jQuery("#vvq-height").val() + "px" );

				// Parse the URL
				var PreviewID = jQuery("#vvq-previewurl").val().match(/http:\/\/video\.google\.([A-Za-z.]{2,5})\/videoplay\?docid=([\d-]+)(.*?)/);
				if ( !PreviewID ) {
					jQuery("#vvqvideopreview-container").html('<div id="vvqvideopreview"><?php echo $this->esc_js( __("Unable to parse preview URL. Please make sure it's the <strong>full</strong> URL and a valid one at that.", 'vipers-video-quicktags') ); ?></div>');
					return;
				}
				var PreviewID = PreviewID[2];

				// Generate the URL and do the preview
				var Autoplay = "";
				var FS = "";
				if ( true == jQuery("#vvq-googlevideo-autoplay").attr("checked") ) { var Autoplay = "&autoplay=1"; }
				if ( true == jQuery("#vvq-googlevideo-fs").attr("checked") ) { var FS = "&fs=true"; }
				swfobject.embedSWF(
					"http://video.google.com/googleplayer.swf?docid=" + PreviewID + Autoplay + FS,
					"vvqvideopreview",
					jQuery("#vvq-width").val(),
					jQuery("#vvq-height").val(),
					"9",
					vvqexpressinstall,
					vvqflashvars,
					vvqparams,
					vvqattributes
				);
			}
		});
	// ]]>
	</script>

	<input type="hidden" name="vvq-tab" value="googlevideo" />

	<table class="form-table">
		<tr valign="top" class="hide-if-no-js">
			<th scope="row"><?php _e('Preview', 'vipers-video-quicktags'); ?></th>
			<td>
				<div id="vvqvideopreview-container" style="min-height:<?php echo $this->settings['googlevideo']['height']; ?>px">
					<div id="vvqvideopreview"><?php _e('Loading...', 'vipers-video-quicktags'); ?></div>
				</div>
			</td>
		</tr>
		<tr valign="top" class="hide-if-no-js">
			<th scope="row"><label for="vvq-previewurl"><?php _e('Preview URL', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<input type="text" name="vvq-googlevideo-previewurl" id="vvq-previewurl" value="<?php echo esc_attr($this->settings['googlevideo']['previewurl']); ?>" class="vvqwide" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Dimensions', 'vipers-video-quicktags'); ?></th>
			<td>
				<input type="text" name="vvq-googlevideo-width" id="vvq-width" size="3" value="<?php echo esc_attr($this->settings['googlevideo']['width']); ?>" /> &#215;
				<input type="text" name="vvq-googlevideo-height" id="vvq-height" size="3" value="<?php echo esc_attr($this->settings['googlevideo']['height']); ?>" /> <?php _e('pixels', 'vipers-video-quicktags'); ?> 
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label><input type="checkbox" name="vvq-googlevideo-aspectratio" id="vvq-aspectratio" value="1"<?php checked($this->settings['googlevideo']['aspectratio'], 1); ?> /> <?php _e('Maintain aspect ratio', 'vipers-video-quicktags'); ?></label>
				<input type="hidden" id="vvq-width-default" value="<?php echo esc_attr($this->defaultsettings['googlevideo']['width']); ?>" />
				<input type="hidden" id="vvq-height-default" value="<?php echo esc_attr($this->defaultsettings['googlevideo']['height']); ?>" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Other', 'vipers-video-quicktags'); ?></th>
			<td>
				<label><input type="checkbox" name="vvq-googlevideo-fs" id="vvq-googlevideo-fs" value="1"<?php checked($this->settings['googlevideo']['fs'], 1); ?> /> <?php _e('Show fullscreen button', 'vipers-video-quicktags'); ?></label><br />
				<label><input type="checkbox" name="vvq-googlevideo-autoplay" id="vvq-googlevideo-autoplay" value="1"<?php checked($this->settings['googlevideo']['autoplay'], 1); ?> /> <?php _e('Autoplay video (not recommended)', 'vipers-video-quicktags'); ?></label>
			</td>
		</tr>
	</table>
<?php
			break; // End Google Video

		case 'dailymotion': ?>
				// <script>
				jQuery("#vvqvideopreview-container").css( "min-height", jQuery("#vvq-height").val() + "px" );

				// Get the colors, transform to uppercase, and then set the inputs with the uppercase value
				var BackgroundColorVal   = jQuery("#vvq-dailymotion-backgroundcolor").val().toUpperCase();
				var GlowColorVal         = jQuery("#vvq-dailymotion-glowcolor").val().toUpperCase();
				var ForegroundColorVal   = jQuery("#vvq-dailymotion-foregroundcolor").val().toUpperCase();
				var SeekbarColorVal      = jQuery("#vvq-dailymotion-seekbarcolor").val().toUpperCase();
				jQuery("#vvq-dailymotion-backgroundcolor").val(BackgroundColorVal);
				jQuery("#vvq-dailymotion-glowcolor").val(GlowColorVal);
				jQuery("#vvq-dailymotion-foregroundcolor").val(ForegroundColorVal);
				jQuery("#vvq-dailymotion-seekbarcolor").val(SeekbarColorVal);

				// Parse the URL
				var PreviewID = jQuery("#vvq-previewurl").val().match(/http:\/\/(www.dailymotion|dailymotion)\.com\/(.+)\/([0-9a-zA-Z]+)\_(.*?)/);
				if ( !PreviewID ) {
					jQuery("#vvqvideopreview-container").html('<div id="vvqvideopreview"><?php echo $this->esc_js( __("Unable to parse preview URL. Please make sure it's the <strong>full</strong> URL and a valid one at that.", 'vipers-video-quicktags') ); ?></div>');
					return;
				}
				var PreviewID = PreviewID[3];

				// Generate the URL and do the preview
				var BackgroundColor = "";
				var GlowColor = "";
				var ForegroundColor = "";
				var SeekbarColor = "";
				if ( "" != BackgroundColorVal && "<?php echo $this->defaultsettings['dailymotion']['backgroundcolor']; ?>" != BackgroundColorVal ) var BackgroundColor = "background:" + BackgroundColorVal.replace(/#/, "") + ";";
				if ( "" != GlowColorVal && "<?php echo $this->defaultsettings['dailymotion']['glowcolor']; ?>" != GlowColorVal ) var GlowColor = "glow:" + GlowColorVal.replace(/#/, "") + ";";
				if ( "" != ForegroundColorVal && "<?php echo $this->defaultsettings['dailymotion']['foregroundcolor']; ?>" != ForegroundColorVal ) var ForegroundColor = "foreground:" + ForegroundColorVal.replace(/#/, "") + ";";
				if ( "" != SeekbarColorVal && "<?php echo $this->defaultsettings['dailymotion']['seekbarcolor']; ?>" != SeekbarColorVal ) var SeekbarColor = "special:" + SeekbarColorVal.replace(/#/, "") + ";";
				if ( true == jQuery("#vvq-dailymotion-autoplay").attr("checked") ) { var Autoplay = "1"; } else { var Autoplay = "0"; }
				if ( true == jQuery("#vvq-dailymotion-related").attr("checked") ) { var Related = "1"; } else { var Related = "0"; }
				swfobject.embedSWF(
					"http://www.dailymotion.com/swf/" + PreviewID + "&colors=" + BackgroundColor + GlowColor + ForegroundColor + SeekbarColor + "&autoPlay=" + Autoplay + "&related=" + Related,
					"vvqvideopreview",
					jQuery("#vvq-width").val(),
					jQuery("#vvq-height").val(),
					"9",
					vvqexpressinstall,
					vvqflashvars,
					vvqparams,
					vvqattributes
				);
			}
		});
	// ]]>
	</script>

	<input type="hidden" name="vvq-tab" value="dailymotion" />

	<table class="form-table">
		<tr valign="top" class="hide-if-no-js">
			<th scope="row"><?php _e('Preview', 'vipers-video-quicktags'); ?></th>
			<td>
				<div id="vvqvideopreview-container" style="min-height:<?php echo $this->settings['dailymotion']['height']; ?>px">
					<div id="vvqvideopreview"><?php _e('Loading...', 'vipers-video-quicktags'); ?></div>
				</div>
			</td>
		</tr>
		<tr valign="top" class="hide-if-no-js">
			<th scope="row"><label for="vvq-previewurl"><?php _e('Preview URL', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<input type="text" name="vvq-dailymotion-previewurl" id="vvq-previewurl" value="<?php echo esc_attr($this->settings['dailymotion']['previewurl']); ?>" class="vvqwide" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Dimensions', 'vipers-video-quicktags'); ?></th>
			<td>
				<input type="text" name="vvq-dailymotion-width" id="vvq-width" size="3" value="<?php echo esc_attr($this->settings['dailymotion']['width']); ?>" /> &#215;
				<input type="text" name="vvq-dailymotion-height" id="vvq-height" size="3" value="<?php echo esc_attr($this->settings['dailymotion']['height']); ?>" /> <?php _e('pixels', 'vipers-video-quicktags'); ?> 
				<input type="hidden" id="vvq-aspectratio" value="0" />
				<input type="hidden" id="vvq-width-default" value="<?php echo esc_attr($this->defaultsettings['dailymotion']['width']); ?>" />
				<input type="hidden" id="vvq-height-default" value="<?php echo esc_attr($this->defaultsettings['dailymotion']['height']); ?>" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="vvq-dailymotion-backgroundcolor"><?php _e('Toolbar Background Color', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<input type="text" name="vvq-dailymotion-backgroundcolor" id="vvq-dailymotion-backgroundcolor" value="<?php echo esc_attr($this->settings['dailymotion']['backgroundcolor']); ?>" maxlength="7" size="7" class="vvqnarrow" />
				&nbsp;<span class="vvq-swatch hide-if-no-js" id="vvq-dailymotion-backgroundcolor-swatch" title="<?php _e('Pick a color', 'vipers-video-quicktags'); ?>">&nbsp;</span>
				<div class="vvq-picker-wrap hide-if-no-js" id="vvq-dailymotion-backgroundcolor-picker-wrap"><div class="vvq-picker" id="vvq-dailymotion-backgroundcolor-picker"></div></div>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="vvq-dailymotion-glowcolor"><?php _e('Toolbar Glow Color', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<input type="text" name="vvq-dailymotion-glowcolor" id="vvq-dailymotion-glowcolor" value="<?php echo esc_attr($this->settings['dailymotion']['glowcolor']); ?>" maxlength="7" size="7" class="vvqnarrow" />
				&nbsp;<span class="vvq-swatch hide-if-no-js" id="vvq-dailymotion-glowcolor-swatch" title="<?php _e('Pick a color', 'vipers-video-quicktags'); ?>">&nbsp;</span>
				<div class="vvq-picker-wrap hide-if-no-js" id="vvq-dailymotion-glowcolor-picker-wrap"><div class="vvq-picker" id="vvq-dailymotion-glowcolor-picker"></div></div>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="vvq-dailymotion-foregroundcolor"><?php _e('Button/Text Color', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<input type="text" name="vvq-dailymotion-foregroundcolor" id="vvq-dailymotion-foregroundcolor" value="<?php echo esc_attr($this->settings['dailymotion']['foregroundcolor']); ?>" maxlength="7" size="7" class="vvqnarrow" />
				&nbsp;<span class="vvq-swatch hide-if-no-js" id="vvq-dailymotion-foregroundcolor-swatch" title="<?php _e('Pick a color', 'vipers-video-quicktags'); ?>">&nbsp;</span>
				<div class="vvq-picker-wrap hide-if-no-js" id="vvq-dailymotion-foregroundcolor-picker-wrap"><div class="vvq-picker" id="vvq-dailymotion-foregroundcolor-picker"></div></div>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="vvq-dailymotion-seekbarcolor"><?php _e('Seekbar Color', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<input type="text" name="vvq-dailymotion-seekbarcolor" id="vvq-dailymotion-seekbarcolor" value="<?php echo esc_attr($this->settings['dailymotion']['seekbarcolor']); ?>" maxlength="7" size="7" class="vvqnarrow" />
				&nbsp;<span class="vvq-swatch hide-if-no-js" id="vvq-dailymotion-seekbarcolor-swatch" title="<?php _e('Pick a color', 'vipers-video-quicktags'); ?>">&nbsp;</span>
				<div class="vvq-picker-wrap hide-if-no-js" id="vvq-dailymotion-seekbarcolor-picker-wrap"><div class="vvq-picker" id="vvq-dailymotion-seekbarcolor-picker"></div></div>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Other', 'vipers-video-quicktags'); ?></th>
			<td>
				<label><input type="checkbox" name="vvq-dailymotion-autoplay" id="vvq-dailymotion-autoplay" value="1"<?php checked($this->settings['dailymotion']['autoplay'], 1); ?> /> <?php _e('Autoplay video (not recommended)', 'vipers-video-quicktags'); ?></label><br />
				<label><input type="checkbox" name="vvq-dailymotion-related" id="vvq-dailymotion-related" value="1"<?php checked($this->settings['dailymotion']['related'], 1); ?> /> <?php _e('Show related videos', 'vipers-video-quicktags'); ?></label>
			</td>
		</tr>
	</table>
<?php
			break; // End DailyMotion

		case 'vimeo': ?>
				// <script>
				jQuery("#vvqvideopreview-container").css( "min-height", jQuery("#vvq-height").val() + "px" );

				// Get the color, transform to uppercase, and then set the input with the uppercase value
				var ColorVal = jQuery("#vvq-vimeo-color").val().toUpperCase();
				jQuery("#vvq-vimeo-color").val(ColorVal);

				// Parse the URL
				var PreviewID = jQuery("#vvq-previewurl").val().match(/http:\/\/(www.vimeo|vimeo)\.com(\/|\/clip:)(\d+)(.*?)/);
				if ( !PreviewID ) {
					jQuery("#vvqvideopreview-container").html('<div id="vvqvideopreview"><?php echo $this->esc_js( __("Unable to parse preview URL. Please make sure it's the <strong>full</strong> URL and a valid one at that.", 'vipers-video-quicktags') ); ?></div>');
					return;
				}
				var PreviewID = PreviewID[3];

				// Generate the URL and do the preview
				var Color = "";
				if ( "" != ColorVal && "<?php echo $this->defaultsettings['vimeo']['color']; ?>" != ColorVal ) var Color = "&color=" + ColorVal.replace(/#/, "");
				if ( true == jQuery("#vvq-vimeo-portrait").attr("checked") ) { var Portrait = "1"; } else { var Portrait = "0"; }
				if ( true == jQuery("#vvq-vimeo-title").attr("checked") ) { var Title = "1"; } else { var Title = "0"; }
				if ( true == jQuery("#vvq-vimeo-byline").attr("checked") ) { var Byline = "1"; } else { var Byline = "0"; }
				if ( true == jQuery("#vvq-vimeo-fullscreen").attr("checked") ) { var Fullscreen = "1"; } else { var Fullscreen = "0"; }
				swfobject.embedSWF(
					"http://www.vimeo.com/moogaloop.swf?server=www.vimeo.com&clip_id=" + PreviewID + Color + "&show_portrait=" + Portrait + "&show_title=" + Title + "&show_byline=" + Byline + "&fullscreen=" + Fullscreen,
					"vvqvideopreview",
					jQuery("#vvq-width").val(),
					jQuery("#vvq-height").val(),
					"9",
					vvqexpressinstall,
					vvqflashvars,
					vvqparams,
					vvqattributes
				);
			}


			/* Color presets which is also based on code stolen from Ozh's "Liz Comment Counter" */

			// Make the presets
			VVQMakeVimeoPresets();
			function VVQMakeVimeoPresets() {
				var presets = {
					"<?php echo esc_js( __('Default (Blue)', 'vipers-video-quicktags') ); ?>": "<?php echo $this->defaultsettings['vimeo']['color']; ?>",
					"<?php echo esc_js( __('Orange', 'vipers-video-quicktags') ); ?>": "#FF9933",
					"<?php echo esc_js( __('Lime', 'vipers-video-quicktags') ); ?>": "#C9FF23",
					"<?php echo esc_js( __('Fuschia', 'vipers-video-quicktags') ); ?>": "#FF0179",
					"<?php echo esc_js( __('White', 'vipers-video-quicktags') ); ?>": "#FFFFFF"
				};
				jQuery("#vvq-vimeo-presets").html("");
				for (var i in presets) {
					var color = presets[i];
					jQuery("#vvq-vimeo-presets").append('<div class="vvq-preset" style="background:'+color+';border:10px solid '+color+';" title="'+i+'"></div> ');
				}
			}

			// Update the color inputs when a preset is clicked
			jQuery(".vvq-preset").click(function(){
				var color = jQuery(this).css('backgroundColor');

				// Opera and IE return hex already, but we need to convert RGB to hex for Firefox, Safari, etc.
				if ( -1 == color.search(/#/) ) {
					var color = VVQRGBtoHex( color );
				}

				if (color != undefined) { jQuery('#vvq-vimeo-color').val(color).keyup(); }

				VVQUpdatePreview();
			}).tTips();
		});
	// ]]>
	</script>

	<input type="hidden" name="vvq-tab" value="vimeo" />

	<table class="form-table">
		<tr valign="top" class="hide-if-no-js">
			<th scope="row"><?php _e('Preview', 'vipers-video-quicktags'); ?></th>
			<td>
				<div id="vvqvideopreview-container" style="min-height:<?php echo $this->settings['vimeo']['height']; ?>px">
					<div id="vvqvideopreview"><?php _e('Loading...', 'vipers-video-quicktags'); ?></div>
				</div>
			</td>
		</tr>
		<tr valign="top" class="hide-if-no-js">
			<th scope="row"><label for="vvq-previewurl"><?php _e('Preview URL', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<input type="text" name="vvq-vimeo-previewurl" id="vvq-previewurl" value="<?php echo esc_attr($this->settings['vimeo']['previewurl']); ?>" class="vvqwide" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Dimensions', 'vipers-video-quicktags'); ?></th>
			<td>
				<input type="text" name="vvq-vimeo-width" id="vvq-width" size="3" value="<?php echo esc_attr($this->settings['vimeo']['width']); ?>" /> &#215;
				<input type="text" name="vvq-vimeo-height" id="vvq-height" size="3" value="<?php echo esc_attr($this->settings['vimeo']['height']); ?>" /> <?php _e('pixels', 'vipers-video-quicktags'); ?> 
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label><input type="checkbox" name="vvq-vimeo-aspectratio" id="vvq-aspectratio" value="1"<?php checked($this->settings['vimeo']['aspectratio'], 1); ?> /> <?php _e('Maintain aspect ratio', 'vipers-video-quicktags'); ?></label>
				<input type="hidden" id="vvq-width-default" value="<?php echo esc_attr($this->defaultsettings['vimeo']['width']); ?>" />
				<input type="hidden" id="vvq-height-default" value="<?php echo esc_attr($this->defaultsettings['vimeo']['height']); ?>" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="vvq-vimeo-color"><?php _e('Color', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<input type="text" name="vvq-vimeo-color" id="vvq-vimeo-color" value="<?php echo esc_attr($this->settings['vimeo']['color']); ?>" maxlength="7" size="7" class="vvqnarrow" />
				&nbsp;<span class="vvq-swatch hide-if-no-js" id="vvq-vimeo-color-swatch" title="<?php _e('Pick a color', 'vipers-video-quicktags'); ?>">&nbsp;</span>
				<div class="vvq-picker-wrap hide-if-no-js" id="vvq-vimeo-color-picker-wrap"><div class="vvq-picker" id="vvq-vimeo-color-picker"></div></div>
			</td>
		</tr>
		<tr valign="top" class="hide-if-no-js">
			<th scope="row"><?php _e('Color Presets', 'vipers-video-quicktags'); ?></th>
			<td id="vvq-vimeo-presets">&nbsp;</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('On-Screen Info', 'vipers-video-quicktags'); ?></th>
			<td>
				<label><input type="checkbox" name="vvq-vimeo-portrait" id="vvq-vimeo-portrait" value="1"<?php checked($this->settings['vimeo']['portrait'], 1); ?> /> <?php _e('Portrait', 'vipers-video-quicktags'); ?></label><br />
				<label><input type="checkbox" name="vvq-vimeo-title" id="vvq-vimeo-title" value="1"<?php checked($this->settings['vimeo']['title'], 1); ?> /> <?php _e('Title', 'vipers-video-quicktags'); ?></label><br />
				<label><input type="checkbox" name="vvq-vimeo-byline" id="vvq-vimeo-byline" value="1"<?php checked($this->settings['vimeo']['byline'], 1); ?> /> <?php _e('Byline', 'vipers-video-quicktags'); ?></label><br />
				<label><input type="checkbox" name="vvq-vimeo-fullscreen" id="vvq-vimeo-fullscreen" value="1"<?php checked($this->settings['vimeo']['fullscreen'], 1); ?> /> <?php _e('Allow fullscreen', 'vipers-video-quicktags'); ?></label><br />
			</td>
		</tr>
	</table>
<?php
			break; // End Vimeo

		case 'flv': ?>
				// <script>
				jQuery("#vvqvideopreview-container").css( "min-height", jQuery("#vvq-height").val() + "px" );

				// Get the colors, transform to uppercase, and then set the inputs with the uppercase value
				var BackColorVal   = jQuery("#vvq-flv-backcolor").val().toUpperCase();
				var FrontColorVal  = jQuery("#vvq-flv-frontcolor").val().toUpperCase();
				var LightColorVal  = jQuery("#vvq-flv-lightcolor").val().toUpperCase();
				var ScreenColorVal = jQuery("#vvq-flv-screencolor").val().toUpperCase();
				jQuery("#vvq-flv-backcolor").val(BackColorVal);
				jQuery("#vvq-flv-frontcolor").val(FrontColorVal);
				jQuery("#vvq-flv-lightcolor").val(LightColorVal);
				jQuery("#vvq-flv-screencolor").val(ScreenColorVal);

				// Generate the URL and do the preview
				var vvqflvparams = new Array();
				vvqflvparams["file"] = jQuery("#vvq-previewurl").val();
				vvqflvparams["image"] = jQuery("#vvq-previewurl").val().replace(/\.flv/, ".jpg");
				if ( true == jQuery("#vvq-flv-customcolors").attr("checked") ) {
					vvqflvparams["backcolor"] = BackColorVal.replace(/#/, "");
					vvqflvparams["frontcolor"] = FrontColorVal.replace(/#/, "");
					vvqflvparams["lightcolor"] = LightColorVal.replace(/#/, "");
					vvqflvparams["screencolor"] = ScreenColorVal.replace(/#/, "");
				}
				vvqflvparams["volume"] = "100";
				vvqflvparams["bufferlength"] = "15";
				vvqflvparams["skin"] = "<?php echo plugins_url('/vipers-video-quicktags/resources/jw-flv-player/skins/'); ?>" + jQuery("#vvq-flv-skin").val() + ".swf";
				vvqflvparams["wmode"] = "transparent";
				vvqflvparams["allowfullscreen"] = "true";
<?php
					// Handle the advanced parameters (these require a page reload to be updated)
					if ( !empty($this->settings['flv']['flashvars']) ) {
						$flashvars = $this->parse_str_periods( $this->settings['flv']['flashvars'] );
						foreach ( (array) $flashvars as $key => $value )
							echo '				vvqflvparams["' . $key . '"] = "' . $value . '";' . "\n";
					}
?>

				swfobject.embedSWF(
					"<?php echo plugins_url('/vipers-video-quicktags/resources/jw-flv-player/player.swf'); ?>",
					"vvqvideopreview",
					jQuery("#vvq-width").val(),
					jQuery("#vvq-height").val(),
					"9",
					vvqexpressinstall,
					vvqflvparams,
					vvqparams,
					vvqattributes
				);
			}

			jQuery("#vvq-flv-customcolors").change(function(){
				if ( true != jQuery(this).attr("checked") ) {
					jQuery(".vvq-flv-customcolor").hide();
				} else {
					jQuery(".vvq-flv-customcolor").show();
				}
			});

			if ( true != jQuery("#vvq-flv-customcolors").attr("checked") ) {
				jQuery(".vvq-flv-customcolor").hide();
			}
		});
	// ]]>
	</script>

	<input type="hidden" name="vvq-tab" value="flv" />

	<table class="form-table">
		<tr valign="top" class="hide-if-no-js">
			<th scope="row"><?php _e('Preview', 'vipers-video-quicktags'); ?></th>
			<td>
				<div id="vvqvideopreview-container" style="min-height:<?php echo $this->settings['flv']['height']; ?>px">
					<div id="vvqvideopreview"><?php _e('Loading...', 'vipers-video-quicktags'); ?></div>
				</div>
			</td>
		</tr>
		<tr valign="top" class="hide-if-no-js">
			<th scope="row"><label for="vvq-previewurl"><?php _e('Preview URL', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<input type="text" name="vvq-flv-previewurl" id="vvq-previewurl" value="<?php echo esc_attr($this->settings['flv']['previewurl']); ?>" size="50" class="vvqwide" /><br />
				<?php _e('The default preview video is the most recent featured video on YouTube. You can paste in the URL to a FLV file of your own if you wish.', 'vipers-video-quicktags'); ?>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Dimensions', 'vipers-video-quicktags'); ?></th>
			<td>
				<input type="text" name="vvq-flv-width" id="vvq-width" size="3" value="<?php echo esc_attr($this->settings['flv']['width']); ?>" /> &#215;
				<input type="text" name="vvq-flv-height" id="vvq-height" size="3" value="<?php echo esc_attr($this->settings['flv']['height']); ?>" />
				<?php _e("pixels (if you're using the default skin, add 20 to the height for the control bar)", 'vipers-video-quicktags'); ?> 
				<input type="hidden" id="vvq-aspectratio" value="0" />
				<input type="hidden" id="vvq-width-default" value="<?php echo esc_attr($this->defaultsettings['flv']['width']); ?>" />
				<input type="hidden" id="vvq-height-default" value="<?php echo esc_attr($this->defaultsettings['flv']['height']); ?>" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="vvq-flv-skin"><?php _e('Skin', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<select name="vvq-flv-skin" id="vvq-flv-skin">
<?php
					foreach ( $this->flvskins as $skin => $name ) {
						echo '					<option value="' . $skin . '"';
						selected( $this->settings['flv']['skin'], $skin );
						echo '>' . htmlspecialchars( $name ) . "</option>\n";
					}
?>
				</select>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="vvq-flv-customcolors"><?php _e('Use Custom Colors', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<label><input type="checkbox" name="vvq-flv-customcolors" id="vvq-flv-customcolors" value="1"<?php checked($this->settings['flv']['customcolors'], 1); ?> /></label>
			</td>
		</tr>
		<tr valign="top" class="vvq-flv-customcolor">
			<th scope="row"><label for="vvq-flv-backcolor"><?php _e('Control Bar Background Color', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<input type="text" name="vvq-flv-backcolor" id="vvq-flv-backcolor" value="<?php echo esc_attr($this->settings['flv']['backcolor']); ?>" maxlength="7" size="7" class="vvqnarrow" />
				&nbsp;<span class="vvq-swatch hide-if-no-js" id="vvq-flv-backcolor-swatch" title="<?php _e('Pick a color', 'vipers-video-quicktags'); ?>">&nbsp;</span>
				<div class="vvq-picker-wrap hide-if-no-js" id="vvq-flv-backcolor-picker-wrap"><div class="vvq-picker" id="vvq-flv-backcolor-picker"></div></div>
			</td>
		</tr>
		<tr valign="top" class="vvq-flv-customcolor">
			<th scope="row"><label for="vvq-flv-frontcolor"><?php _e('Icon/Text Color', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<input type="text" name="vvq-flv-frontcolor" id="vvq-flv-frontcolor" value="<?php echo esc_attr($this->settings['flv']['frontcolor']); ?>" maxlength="7" size="7" class="vvqnarrow" />
				&nbsp;<span class="vvq-swatch hide-if-no-js" id="vvq-flv-frontcolor-swatch" title="<?php _e('Pick a color', 'vipers-video-quicktags'); ?>">&nbsp;</span>
				<div class="vvq-picker-wrap hide-if-no-js" id="vvq-flv-frontcolor-picker-wrap"><div class="vvq-picker" id="vvq-flv-frontcolor-picker"></div></div>
			</td>
		</tr>
		<tr valign="top" class="vvq-flv-customcolor">
			<th scope="row"><label for="vvq-flv-lightcolor"><?php _e('Icon/Text Hover Color', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<input type="text" name="vvq-flv-lightcolor" id="vvq-flv-lightcolor" value="<?php echo esc_attr($this->settings['flv']['lightcolor']); ?>" maxlength="7" size="7" class="vvqnarrow" />
				&nbsp;<span class="vvq-swatch hide-if-no-js" id="vvq-flv-lightcolor-swatch" title="<?php _e('Pick a color', 'vipers-video-quicktags'); ?>">&nbsp;</span>
				<div class="vvq-picker-wrap hide-if-no-js" id="vvq-flv-lightcolor-picker-wrap"><div class="vvq-picker" id="vvq-flv-lightcolor-picker"></div></div>
			</td>
		</tr>
		<tr valign="top" class="vvq-flv-customcolor">
			<th scope="row"><label for="vvq-flv-screencolor"><?php _e('Video Background Color', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<input type="text" name="vvq-flv-screencolor" id="vvq-flv-screencolor" value="<?php echo esc_attr($this->settings['flv']['screencolor']); ?>" maxlength="7" size="7" class="vvqnarrow" />
				&nbsp;<span class="vvq-swatch hide-if-no-js" id="vvq-flv-screencolor-swatch" title="<?php _e('Pick a color', 'vipers-video-quicktags'); ?>">&nbsp;</span>
				<div class="vvq-picker-wrap hide-if-no-js" id="vvq-flv-screencolor-picker-wrap"><div class="vvq-picker" id="vvq-flv-screencolor-picker"></div></div>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="vvq-flv-flashvars"><?php _e('Advanced Parameters', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<input type="text" name="vvq-flv-flashvars" id="vvq-flv-flashvars" value="<?php echo esc_attr($this->settings['flv']['flashvars']); ?>" size="50" class="vvqwide" /><br />
				<?php printf( __('A <a href="%1$s">query-string style</a> list of <a href="%2$s">additional parameters</a> to pass to the player. Example: %3$s', 'vipers-video-quicktags'), 'http://codex.wordpress.org/Template_Tags/How_to_Pass_Tag_Parameters#Tags_with_query-string-style_parameters', 'http://code.jeroenwijering.com/trac/wiki/FlashVars', '<code>autostart=true&amp;playlist=bottom&amp;bufferlength=15</code>' ); ?><br />
				<?php _e('You will need to press &quot;Save Changes&quot; for these parameters to take effect due to my moderate Javascript skills.', 'vipers-video-quicktags'); ?>
			</td>
		</tr>
	</table>
<?php
			break; // End FLV

		case 'additional': ?>
	<script type="text/javascript">
	// <![CDATA[
		jQuery(document).ready(function(){
			jQuery("#vvq-alignment").change(function(){
				var alignments = {
					<?php
					$alignments = array();
					foreach ( $this->cssalignments as $value => $css )
						$alignments[] = '"' . $value . '": "' . $css . '"';
					echo implode( ",\n\t\t\t\t\t", $alignments );
?>

				};
				jQuery("#vvq-css-align").html(alignments[jQuery(this).val()]);
			});
			jQuery("#vvq-customcss-wrap").hide();
			jQuery("#vvq-customcss-toggle").css({ display:"block", cursor:"pointer" }).click(function(){
				jQuery(this).slideUp();
				jQuery("#vvq-customcss-wrap").slideDown();
			});
		});
	// ]]>
	</script>

	<input type="hidden" name="vvq-tab" value="additional" />

	<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="vvq-alignment"><?php _e('Video Alignment', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<select name="vvq-alignment" id="vvq-alignment">
<?php
					$alignments = array(
						'left'       => __('Left', 'vipers-video-quicktags'),
						'center'     => __('Center', 'vipers-video-quicktags'),
						'right'      => __('Right', 'vipers-video-quicktags'),
						'floatleft'  => __('Float Left', 'vipers-video-quicktags'),
						'floatright' => __('Float Right', 'vipers-video-quicktags'),
					);
					foreach ( $alignments as $alignment => $name ) {
						echo '					<option value="' . $alignment . '"';
						selected( $this->settings['alignment'], $alignment );
						echo '>' . $name . "</option>\n";
					}
?>
				</select>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="vvq-tinymceline"><?php _e('Show Buttons In Editor On Line Number', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<select name="vvq-tinymceline" id="vvq-tinymceline">
<?php
					$alignments = array(
						1 => __('1', 'vipers-video-quicktags'),
						2 => __('2 (Kitchen Sink Toolbar)', 'vipers-video-quicktags'),
						3 => __('3 (Default)', 'vipers-video-quicktags'),
					);
					foreach ( $alignments as $alignment => $name ) {
						echo '					<option value="' . $alignment . '"';
						selected( $this->settings['tinymceline'], $alignment );
						echo '>' . $name . "</option>\n";
					}
?>
				</select>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="vvq-customfeedtext"><?php _e('Feed Text', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<input type="text" name="vvq-customfeedtext" id="vvq-customfeedtext" value="<?php echo esc_attr($this->settings['customfeedtext']); ?>" size="50" class="vvqwide" /><br />
				<?php printf( __("Optionally enter some custom text to show in your feed in place of videos (as you can't embed videos in feeds). If left blank, it will default to:<br />%s", 'vipers-video-quicktags'), '<code>' . htmlspecialchars($this->customfeedtext) . '</code>' ); ?>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="vvq-videofile-usewmp"><?php _e('Windows Media Player', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<label><input type="checkbox" name="vvq-videofile-usewmp" id="vvq-videofile-usewmp" value="1"<?php checked($this->settings['videofile']['usewmp'], 1); ?> /> <?php _e('Attempt to use Windows Media Player for regular video file playback for Windows users', 'vipers-video-quicktags'); ?></label>
			</td>
		</tr>
	</table>

	<h3><?php _e('Advanced Settings', 'vipers-video-quicktags'); ?></h3>

	<p><?php _e("If you don't know what you're doing, you can safely ignore this section.", 'vipers-video-quicktags'); ?></p>

	<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="vvq-quicktime-dynamicload"><?php _e('Dynamic QTObject Loading', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<label><input type="checkbox" name="vvq-quicktime-dynamicload" id="vvq-quicktime-dynamicload" value="1"<?php checked($this->settings['quicktime']['dynamicload'], 1); ?> /> <?php _e("Only load the Javascript file if it's needed. Disable this to post Quicktime videos in the sidebar text widget.", 'vipers-video-quicktags'); ?></label>
			</td>
		</tr>
<?php if ( empty($wpmu_version) ) : ?>
		<tr valign="top">
			<th scope="row"><label for="vvq-customcss"><?php _e('Custom CSS', 'vipers-video-quicktags'); ?></label></th>
			<td>
				<span id="vvq-customcss-toggle" class="hide-if-no-js"><?php _e('Want to easily set some custom CSS to control the display of the videos? Then just click this text to expand this option.', 'vipers-video-quicktags'); ?></span>
				<div id="vvq-customcss-wrap">
					<?php printf( __('You can enter custom CSS in the box below. It will be outputted after the default CSS you see listed which can be overridden by using %1$s. For help and examples, see the <a href="%2$s">Help</a> tab.', 'vipers-video-quicktags'), '<code>!important</code>', admin_url('options-general.php?page=vipers-video-quicktags&amp;tab=help#vvq-customcss') ); ?>
					<pre><?php
						$aligncss = str_replace( '\n', "\n", $this->cssalignments[$this->settings['alignment']] );
						echo str_replace( '/* alignment CSS placeholder */', "<span id='vvq-css-align'>$aligncss</span>", $this->standardcss );
					?></pre>
					<textarea name="vvq-customcss" id="vvq-customcss" cols="60" rows="10" style="font-size: 12px;" class="vvqwide code"><?php echo esc_attr( $this->settings['customcss'] ); ?></textarea>
				</div>
			</td>
		</tr>
<?php endif; ?>
	</table>

<?php
			break; // End additional

		case 'help': ?>
	<script type="text/javascript">
	// <![CDATA[
		jQuery(document).ready(function(){
			jQuery("#vvq-help").find("div").hide();
			jQuery(".vvq-help-title").css('cursor', 's-resize').click(function(){
				jQuery(this).parent("li").children("div").slideToggle();
			});
			jQuery("#vvq-showall").css('cursor', 'pointer').click(function(){
				jQuery("#vvq-help").children("li").children("div").slideDown();
			});

			// Look for HTML anchor in URL and expand if found
			var anchor = self.document.location.hash.substring(1);
			if ( anchor ) {
				jQuery("#"+anchor).children("div").show();
				location.href = "#"+anchor; // Rescroll
			}

			jQuery(".expandolink").click(function(){
				var id = jQuery(this).attr("href").replace(/#/, "");
				jQuery("#"+id).children("div").show();
				location.href = "#"+anchor; // Rescroll
			});
		});
	// ]]>
	</script>

	<p id="vvq-showall" class="hide-if-no-js"><?php _e('Click on a question to see the answer or click this text to expand all answers.', 'vipers-video-quicktags'); ?></p>

	<ul id="vvq-help">
		<li>
			<p class="vvq-help-title"><?php _e("Videos aren't showing up on my blog, only links to the videos are instead. What gives?", 'vipers-video-quicktags'); ?></p>
			<div>
<?php if ( empty($wpmu_version) ) : ?>
				<p><?php _e('Here are five common causes:', 'vipers-video-quicktags'); ?></p>
				<ol>
					<li><?php printf( __('Are you running Firefox and AdBlock? AdBlock and certain block rules can prevent some videos, namely YouTube-hosted ones, from loading. Disable AdBlock or switch to <a href="%s">AdBlock Plus</a>.', 'vipers-video-quicktags'), 'https://addons.mozilla.org/en-US/firefox/addon/1865' ); ?></li>
					<li><?php _e("Your theme could be missing <code>&lt;?php wp_head();?&gt;</code> inside of it's <code>&lt;head&gt;</code> which means the required Javascript file can't automatically be added. If this is the case, you may be get an alert window popping when you attempt to view a post with a video in it (assuming your problem is not also #3). Edit your theme's <code>header.php</code> file and add it right before <code>&lt;/head&gt;</code>", 'vipers-video-quicktags'); ?></li>
					<li><?php printf( __('You may have Javascript disabled. This plugin embeds videos via Javascript to ensure the best experience. Please <a href="%s">enable it</a>.', 'vipers-video-quicktags'), 'http://www.google.com/support/bin/answer.py?answer=23852' ); ?></li>
					<li><?php printf( __('You may not have the latest version of Flash installed. Please <a href="%s">install it</a>.', 'vipers-video-quicktags'), 'http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash' ); ?></li>
				</ol>
<?php else : ?>
				<ul>
					<li><?php printf( __('Are you running Firefox and AdBlock? AdBlock and certain block rules can result in the videos, namely YouTube-hosted ones, not loading. Disable AdBlock or switch to <a href="%s">AdBlock Plus</a>.', 'vipers-video-quicktags'), 'https://addons.mozilla.org/en-US/firefox/addon/1865' ); ?></li>
					<li><?php printf( __('You may have Javascript disabled. This plugin embeds videos via Javascript to ensure the best experience. Please <a href="%s">enable it</a>.', 'vipers-video-quicktags'), 'http://www.google.com/support/bin/answer.py?answer=23852' ); ?></li>
					<li><?php printf( __('You may not have the latest version of Flash installed. Please <a href="%s">install it</a>.', 'vipers-video-quicktags'), 'http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash' ); ?></li>
				</ul>
<?php endif; ?>
			</div>
		</li>
		<li id="vvq-viddlerhelp">
			<p class="vvq-help-title"><?php _e('Where do I get the code from to embed a Viddler video?', 'vipers-video-quicktags'); ?></p>
			<div>
				<p><?php _e('Since the URL to a video on Viddler has nothing in common with the embed URL, you must use WordPress.com-style format. Go to the video on Viddler, click the &quot;Embed This&quot; button below the video, and then select the WordPress.com format. You can paste that code directly into a post or Page and it will embed the video.', 'vipers-video-quicktags'); ?></p>
				<p><img src="<?php echo plugins_url('/vipers-video-quicktags/resources/images/help_viddler.png'); ?>" alt="<?php echo esc_attr( __('Viddler', 'vipers-video-quicktags') ); ?>" width="572" height="543" /></p>
			</div>
		</li>
		<li id="vvq-bliptvhelp">
			<p class="vvq-help-title"><?php _e('Where do I get the code from to embed a Blip.tv video?', 'vipers-video-quicktags'); ?></p>
			<div>
				<p><?php _e('Since the URL to a video on Blip.tv has nothing in common with the embed URL, you must use WordPress.com-style format. Go to the video on Blip.tv, click on the yellow &quot;Share&quot; dropdown to the right of the video and select &quot;Embed&quot;. Next select &quot;WordPress.com&quot; from the &quot;Show Player&quot; dropdown. Finally press &quot;Go&quot;. You can paste that code directly into a post or Page and it will embed the video.', 'vipers-video-quicktags'); ?></p>
				<p><img src="<?php echo plugins_url('/vipers-video-quicktags/resources/images/help_bliptv.png'); ?>" alt="<?php echo esc_attr( __('Blip.tv', 'vipers-video-quicktags') ); ?>" width="317" height="240" /></p>
				<p><?php _e('<strong>NOTE:</strong> Ignore the warning message. This plugin adds support for the WordPress.com so it <strong>will</strong> work on your blog.', 'vipers-video-quicktags'); ?></p>
			</div>
		</li>
		<li>
			<p class="vvq-help-title"><?php _e("Why doesn't this plugin support a site I want to embed videos from?", 'vipers-video-quicktags'); ?></p>
			<div>
				<p><?php _e("There's a couple likely reasons:", 'vipers-video-quicktags'); ?></p>
				<ul>
					<li><?php _e("The website may use an embed URL that has nothing in common with the URL in your address bar. This means that even if you give this plugin the URL to the video, it has no easy way of figuring out the embed URL.", 'vipers-video-quicktags'); ?></li>
					<li><?php _e("The website may be too small, fringe case, etc. to be worth supporting. There's no real point in this plugin adding support for a video site that only one or two people will use.", 'vipers-video-quicktags'); ?></li>
					<li><?php printf( __("I may have just never heard of the site. Please make a thread on <a href='%s'>my forums</a> with an example link to a video on the site and I'll take a look at it.", 'vipers-video-quicktags'), 'http://www.viper007bond.com/wordpress-plugins/forums/viewforum.php?id=23' ); ?></li>
				</ul>
				<p><?php printf( __('This plugin does have the ability to embed any Flash file though. See the <a href="%s" class="expandolink">Flash shortcode question</a> for details on that.', 'vipers-video-quicktags'), '#vvq-flashcodehelp'); ?></p>
			</div>
		</li>
		<li>
			<p class="vvq-help-title"><?php _e("There are still red bits (hovering over buttons) in my YouTube embed. What gives?", 'vipers-video-quicktags'); ?></p>
			<div>
				<p><?php _e('YouTube does not provide a method to change that color.', 'vipers-video-quicktags'); ?></p>
			</div>
		</li>
		<li id="vvq-parameters">
			<p class="vvq-help-title"><?php _e('How can I change the size, colors, etc. for a specific video?', 'vipers-video-quicktags'); ?></p>
			<div>
				<p><?php printf( __('You can control many thing via the WordPress shortcode system that you use to embed videos in your posts. Shortcodes are similiar to <a href="%s">BBCode</a>. Here are some example shortcodes:', 'vipers-video-quicktags'), 'http://en.wikipedia.org/wiki/BBCode' ); ?></p>
				<ul>
					<li><code>[youtube color1=&quot;FF0000&quot; color2=&quot;00FF00&quot; autoplay=&quot;1&quot;]http://www.youtube.com/watch?v=stdJd598Dtg[/youtube]</code></li>
					<li><code>[googlevideo width=&quot;400&quot; height=&quot;300&quot;]http://video.google.com/videoplay?docid=-6006084025483872237[/youtube]</code></li>
					<li><code>[vimeo color=&quot;FFFF00&quot;]http://www.vimeo.com/240975[/youtube]</code></li>
				</ul>
				<p><?php _e('Any value that is not entered will fall back to the default.', 'vipers-video-quicktags'); ?></p>
			</div>
		</li>
		<li>
			<p class="vvq-help-title"><?php printf( __('What are the available parameters for the %s shortcode?', 'vipers-video-quicktags'), __('YouTube', 'vipers-video-quicktags') ); ?></p>
			<div>
				<ul>
					<li><?php printf( __('%s &#8212; width in pixels', 'vipers-video-quicktags'), '<code>width</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; height in pixels', 'vipers-video-quicktags'), '<code>height</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; player border color in hex', 'vipers-video-quicktags'), '<code>color1</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; player fill color in hex', 'vipers-video-quicktags'), '<code>color2</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; show a border or not (<code>0</code> or <code>1</code>)', 'vipers-video-quicktags'), '<code>border</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; show related videos, URL, embed details, etc. at the end of playback (<code>0</code> or <code>1</code>)', 'vipers-video-quicktags'), '<code>rel</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; show fullscreen button (<code>0</code> or <code>1</code>)', 'vipers-video-quicktags'), '<code>fs</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; automatically start playing (<code>0</code> or <code>1</code>)', 'vipers-video-quicktags'), '<code>autoplay</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; loop playback (<code>0</code> or <code>1</code>)', 'vipers-video-quicktags'), '<code>loop</code>' ); ?></li>
				</ul>
			</div>
		</li>
		<li>
			<p class="vvq-help-title"><?php printf( __('What are the available parameters for the %s shortcode?', 'vipers-video-quicktags'), __('Google Video', 'vipers-video-quicktags') ); ?></p>
			<div>
				<ul>
					<li><?php printf( __('%s &#8212; width in pixels', 'vipers-video-quicktags'), '<code>width</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; height in pixels', 'vipers-video-quicktags'), '<code>height</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; automatically start playing (<code>0</code> or <code>1</code>)', 'vipers-video-quicktags'), '<code>autoplay</code>' ); ?></li>
				</ul>
			</div>
		</li>
		<li>
			<p class="vvq-help-title"><?php printf( __('What are the available parameters for the %s shortcode?', 'vipers-video-quicktags'), __('DailyMotion', 'vipers-video-quicktags') ); ?></p>
			<div>
				<ul>
					<li><?php printf( __('%s &#8212; width in pixels', 'vipers-video-quicktags'), '<code>width</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; height in pixels', 'vipers-video-quicktags'), '<code>height</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; toolbar background color in hex', 'vipers-video-quicktags'), '<code>backgroundcolor</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; toolbar glow color in hex', 'vipers-video-quicktags'), '<code>glowcolor</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; button/text color in hex', 'vipers-video-quicktags'), '<code>foregroundcolor</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; seekbar color in hex', 'vipers-video-quicktags'), '<code>seekbarcolor</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; automatically start playing (<code>0</code> or <code>1</code>)', 'vipers-video-quicktags'), '<code>autoplay</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; show related video (<code>0</code> or <code>1</code>)', 'vipers-video-quicktags'), '<code>related</code>' ); ?></li>
				</ul>
			</div>
		</li>
		<li>
			<p class="vvq-help-title"><?php printf( __('What are the available parameters for the %s shortcode?', 'vipers-video-quicktags'), __('Vimeo', 'vipers-video-quicktags') ); ?></p>
			<div>
				<ul>
					<li><?php printf( __('%s &#8212; width in pixels', 'vipers-video-quicktags'), '<code>width</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; height in pixels', 'vipers-video-quicktags'), '<code>height</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; player color in hex', 'vipers-video-quicktags'), '<code>color</code>' ); ?></li>
					<li><?php printf( __("%s &#8212; show uploader's picture (<code>0</code> or <code>1</code>)", 'vipers-video-quicktags'), '<code>portrait</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; show video title (<code>0</code> or <code>1</code>)', 'vipers-video-quicktags'), '<code>title</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; show video author (<code>0</code> or <code>1</code>)', 'vipers-video-quicktags'), '<code>byline</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; show fullscreen button (<code>0</code> or <code>1</code>)', 'vipers-video-quicktags'), '<code>fullscreen</code>' ); ?></li>
				</ul>
			</div>
		</li>
		<li>
			<p class="vvq-help-title"><?php printf( __('What are the available parameters for the %s shortcode?', 'vipers-video-quicktags'), __('Veoh', 'vipers-video-quicktags') ); ?></p>
			<div>
				<ul>
					<li><?php printf( __('%s &#8212; width in pixels', 'vipers-video-quicktags'), '<code>width</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; height in pixels', 'vipers-video-quicktags'), '<code>height</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; automatically start playing (<code>0</code> or <code>1</code>)', 'vipers-video-quicktags'), '<code>autoplay</code>' ); ?></li>
				</ul>
			</div>
		</li>
		<li>
			<p class="vvq-help-title"><?php printf( __('What are the available parameters for the %s shortcode?', 'vipers-video-quicktags'), __('Viddler', 'vipers-video-quicktags') ); ?></p>
			<div>
				<p><?php _e("Since the WordPress.com shortcode format is used for embedding Viddler videos, there are no customizable parameters for the Viddler shortcode. The WordPress.com shortcode must be used as the URL of the video and the video's embed URL share nothing in common.", 'vipers-video-quicktags'); ?></p>
			</div>
		</li>
		<li>
			<p class="vvq-help-title"><?php printf( __('What are the available parameters for the %s shortcode?', 'vipers-video-quicktags'), __('Flickr Video', 'vipers-video-quicktags') ); ?></p>
			<div>
				<ul>
					<li><?php printf( __('%s &#8212; width in pixels', 'vipers-video-quicktags'), '<code>width</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; height in pixels', 'vipers-video-quicktags'), '<code>height</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; show video details (<code>0</code> or <code>1</code>), defaults to 1', 'vipers-video-quicktags'), '<code>showinfobox</code>' ); ?></li>
				</ul>
			</div>
		</li>
		<li>
			<p class="vvq-help-title"><?php _e('What are the available parameters for the Metacafe, Blip.tv, IFILM/Spike, and MySpace shortcodes?', 'vipers-video-quicktags'); ?></p>
			<div>
				<p><?php _e('All of these video formats only support the following:', 'vipers-video-quicktags'); ?></p>
				<ul>
					<li><?php printf( __('%s &#8212; width in pixels', 'vipers-video-quicktags'), '<code>width</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; height in pixels', 'vipers-video-quicktags'), '<code>height</code>' ); ?></li>
				</ul>
			</div>
		</li>
		<li>
			<p class="vvq-help-title"><?php printf( __('What are the available parameters for the %s shortcode?', 'vipers-video-quicktags'), __('Flash Video (FLV)', 'vipers-video-quicktags') ); ?></p>
			<div>
				<ul>
					<li><?php printf( __('%s &#8212; width in pixels', 'vipers-video-quicktags'), '<code>width</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; height in pixels', 'vipers-video-quicktags'), '<code>height</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; the URL to the preview image, defaults to the same URL as the video file but <code>.jpg</code> instead of <code>.flv</code>', 'vipers-video-quicktags'), '<code>image</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; player control bar background color in hex', 'vipers-video-quicktags'), '<code>backcolor</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; player icon/text color in hex', 'vipers-video-quicktags'), '<code>frontcolor</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; player icon/text hover color in hex', 'vipers-video-quicktags'), '<code>lightcolor</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; player video background color in hex', 'vipers-video-quicktags'), '<code>screencolor</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; volume percentage, defaults to <code>100</code>', 'vipers-video-quicktags'), '<code>volume</code>' ); ?></li>
					<li><?php printf( __('%1$s &#8212; %2$s', 'vipers-video-quicktags'), '<code>flashvars</code>', sprintf( __('A <a href="%1$s">query-string style</a> list of <a href="%2$s">additional parameters</a> to pass to the player. Example: %3$s', 'vipers-video-quicktags'), 'http://codex.wordpress.org/Template_Tags/How_to_Pass_Tag_Parameters#Tags_with_query-string-style_parameters', 'http://code.jeroenwijering.com/trac/wiki/FlashVars', '<code>autostart=true&amp;playlist=bottom&amp;bufferlength=15</code>' ) ); ?></li>
				</ul>
			</div>
		</li>
		<li>
			<p class="vvq-help-title"><?php printf( __('What are the available parameters for the %s shortcode?', 'vipers-video-quicktags'), __('Quicktime', 'vipers-video-quicktags') ); ?></p>
			<div>
				<p><?php _e("The results of embedding a Quicktime video can very widely depending on the user's computer and what software they have installed, but if you must embed a Quicktime video here are the parameters:", 'vipers-video-quicktags'); ?></p>
				<ul>
					<li><?php printf( __('%s &#8212; width in pixels', 'vipers-video-quicktags'), '<code>width</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; height in pixels', 'vipers-video-quicktags'), '<code>height</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; automatically start playing (<code>0</code> or <code>1</code>), defaults to 0', 'vipers-video-quicktags'), '<code>autostart</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; use click-to-play placeholder image (<code>0</code> or <code>1</code>), defaults to 0', 'vipers-video-quicktags'), '<code>useplaceholder</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; the URL to the placeholder image, defaults to the same URL as the video file but <code>.jpg</code> instead of <code>.mov</code>', 'vipers-video-quicktags'), '<code>placeholder</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; automatically start playing (<code>0</code> or <code>1</code>), defaults to 0', 'vipers-video-quicktags'), '<code>controller</code>' ); ?></li>
				</ul>
			</div>
		</li>
		<li>
			<p class="vvq-help-title"><?php printf( __('What are the available parameters for the %s shortcode?', 'vipers-video-quicktags'), __('video file', 'vipers-video-quicktags') ); ?></p>
			<div>
				<p><?php _e("The results of embedding a generic video can very widely depending on the user's computer and what software they have installed, but if you must embed a generic video here are the parameters:", 'vipers-video-quicktags'); ?></p>
				<ul>
					<li><?php printf( __('%s &#8212; width in pixels', 'vipers-video-quicktags'), '<code>width</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; height in pixels', 'vipers-video-quicktags'), '<code>height</code>' ); ?></li>
					<li><?php printf( __('%s &#8212; attempt to use Windows Media Player for users of Windows (<code>0</code> or <code>1</code>)', 'vipers-video-quicktags'), '<code>usewmp</code>' ); ?></li>
				</ul>
			</div>
		</li>
<?php if ( empty($wpmu_version) ) : ?>
		<li id="vvq-customcss">
			<p class="vvq-help-title"><?php _e("What's this &quot;Custom CSS&quot; thing on the &quot;Additional Settings&quot; tab for?", 'vipers-video-quicktags'); ?></p>
			<div>
				<p><?php _e("It's a quick and easy way to control the look of videos posted on your site without having to go in and edit the <code>style.css</code> file. Just enter some CSS of your own into the box and it will be outputted in the header of your theme.", 'vipers-video-quicktags'); ?></p>
				<p><?php _e('Some examples:', 'vipers-video-quicktags'); ?></p>
				<ul>
					<li><?php printf( __('Give a red border to all videos: %s', 'vipers-video-quicktags'), '<code>.vvqbox { border: 5px solid red; padding: 5px; }</code>' ); ?></li>
					<li><?php printf( __('Float only YouTube videos to the left: %s', 'vipers-video-quicktags'), '<code>.vvqyoutube { float: left; margin: 10px 10px 10px 0; }</code>' ); ?></li>
				</ul>
			</div>
		</li>
<?php endif; ?>
		<li id="vvq-flashcodehelp">
			<p class="vvq-help-title"><?php _e("How can I embed a generic Flash file or a video from a website this plugin doesn't support?", 'vipers-video-quicktags'); ?></p>
			<div>
				<p><?php printf( __('This plugin has a %s shortcode that can be used to embed <strong>any</strong> Flash file. Here is the format:', 'vipers-video-quicktags'), '<code>[flash]</code>' ); ?></p>
				<p><code>[flash width=&quot;123&quot; height=&quot;456&quot; flashvars=&quot;name1=value1&name2=value2&quot;]http://site.com/path/to/file.swf[/flash]</code></p>
			</div>
		</li>
		<li id="vvq-videofilepoor">
			<p class="vvq-help-title"><?php _e("Why does your plugin embed Quicktime and other regular video files so poorly? Can't you do a better job?", 'vipers-video-quicktags'); ?></p>
			<div>
				<p><?php printf( __('Embedding Quiktime and other regular video files is a major pain in the ass and it\'s incredibly hard to get it to work in all browsers and operating systems. I <strong>strongly</strong> suggest converting the video to <a href="%1$s">Flash Video</a> format or even <a href="%2$s">H.264</a> format and then using the Flash Video (FLV) embed type. There are free converters out there for both formats and doing so will create a much better experience for your visitors.', 'vipers-video-quicktags'), 'http://en.wikipedia.org/wiki/Flash_Video', 'http://en.wikipedia.org/wiki/H.264/MPEG-4_AVC' ); ?></p>
			</div>
		</li>
	</ul>

<?php
			break; // End help

		case 'credits': ?>
	<p><?php _e('This plugin uses many scripts and packages written by others. They deserve credit too, so here they are in no particular order:', 'vipers-video-quicktags'); ?></p>

	<ul>
		<li><?php printf( __('The authors of and contributors to <a href="%s">SWFObject</a> which is used to embed the Flash-based videos.', 'vipers-video-quicktags'), 'http://code.google.com/p/swfobject/' ); ?></li>
		<li><?php printf( __('<strong><a href="%1$s">Jeroen Wijering</a></strong> for writing the <a href="%2$s">JW FLV Media Player</a> which is used for FLV playback.', 'vipers-video-quicktags'), 'http://www.jeroenwijering.com/', 'http://www.jeroenwijering.com/?item=JW_FLV_Media_Player' ); ?></li>
		<li><?php printf( __('<strong><a href="%1$s">Steven Wittens</a></strong> for writing <a href="%2$s">Farbtastic</a>, the fantastic Javascript color picker used in this plugin.', 'vipers-video-quicktags'), 'http://acko.net/', 'http://acko.net/dev/farbtastic' ); ?></li>
		<li><?php printf( __('<strong><a href="%1$s">Ozh</a></strong> for writing his <a href="%2$s">Liz Comment Counter</a> plugin which introduced me to Farbtastic and provided me with some Javascript to base my color picker and color preset code on.', 'vipers-video-quicktags'), 'http://planetozh.com/', 'http://planetozh.com/blog/my-projects/liz-strauss-comment-count-badge-widget-wordpress/' ); ?></li>
		<li><?php printf( __('<strong><a href="%s">Andrew Ozz</a></strong> for helping me out with some TinyMCE-related Javascript and in turn saving me a ton of time.', 'vipers-video-quicktags'), 'http://www.laptoptips.ca/' ); ?></li>
		<li><?php printf( __('<strong><a href="%1$s">Geoff Stearns</a></strong> for writing <a href="%2$s">QTObject</a> which is used to embed Quicktime videos.', 'vipers-video-quicktags'), 'http://www.deconcept.com/', 'http://blog.deconcept.com/2005/01/26/web-standards-compliant-javascript-quicktime-detect-and-embed/' ); ?></li>
		<li><?php printf( __('<strong><a href="%1$s">Mark James</a></strong> for creating the <a href="%2$s">Silk icon pack</a>. This plugin uses at least one of the icons from that pack.', 'vipers-video-quicktags'), 'http://www.famfamfam.com/', 'http://www.famfamfam.com/lab/icons/silk/' ); ?></li>
		<li><?php printf( __('The authors of and contributors to <a href="%s">jQuery</a>, the awesome Javascript package used by WordPress.', 'vipers-video-quicktags'), 'http://jquery.com/' ); ?></li>
		<li><?php printf( __("Everyone who's helped create <a href='%s'>WordPress</a> as without it and it's excellent API, this plugin obviously wouldn't exist.", 'vipers-video-quicktags'), 'http://jquery.com/' ); ?></li>
		<li><?php _e('Everyone who has provided bug reports and feature suggestions for this plugin.', 'vipers-video-quicktags'); ?></li>
	</ul>

	<p><?php _e('The following people have been nice enough to translate this plugin into other languages:', 'vipers-video-quicktags'); ?></p>

	<ul>
		<li><?php printf( __('<strong>Belorussian:</strong> %s', 'vipers-video-quicktags'), 'Fat Cow' ); ?></li>
		<li><?php printf( __('<strong>Brazilian Portuguese:</strong> %s', 'vipers-video-quicktags'), 'Ricardo Martins' ); ?></li>
		<li><?php printf( __('<strong>Chinese:</strong> %s', 'vipers-video-quicktags'), '<a href="http://dreamcolor.net/">Dreamcolor</a>' ); ?></li>
		<li><?php printf( __('<strong>Danish:</strong> %s', 'vipers-video-quicktags'), '<a href="http://wordpress.blogos.dk/">Dr. Georg S. Adamsen</a>' ); ?></li>
		<li><?php printf( __('<strong>Dutch:</strong> %s', 'vipers-video-quicktags'), 'Sypie' ); ?></li>
		<li><?php printf( __('<strong>French:</strong> %s', 'vipers-video-quicktags'), '<a href="http://www.duretz.net/">Laurent Duretz</a>' ); ?></li>
		<li><?php printf( __('<strong>Hungarian:</strong> %s', 'vipers-video-quicktags'), '<a href="http://filmhirek.com/">jamesb</a>' ); ?></li>
		<li><?php printf( __('<strong>Italian:</strong> %s', 'vipers-video-quicktags'), '<a href="http://gidibao.net/">Gianni Diurno</a>' ); ?></li>
		<!--<li><?php printf( __('<strong>Polish:</strong> %s', 'vipers-video-quicktags'), '<a href="http://www.brt12.eu/">Bartosz Sobczyk</a>' ); ?></li>-->
		<li><?php printf( __('<strong>Russian:</strong> %s', 'vipers-video-quicktags'), '<a href="http://handynotes.ru/">Dennis Bri</a>' ); ?></li>
		<li><?php printf( __('<strong>Spanish:</strong> %s', 'vipers-video-quicktags'), '<a href="http://equipajedemano.info/">Omi</a>' ); ?></li>
	</ul>

	<p><?php printf( __('If you\'d like to use this plugin in another language and have your name listed here, just translate the strings in the provided <a href="%1$s">template file</a> located in this plugin\'s &quot;<code>localization</code>&quot; folder and then <a href="%2$s">send it to me</a>. For help, see the <a href="%3$s">WordPress Codex</a>.', 'vipers-video-quicktags'), plugins_url('/vipers-video-quicktags/localization/_vipers-video-quicktags-template.po'), 'http://www.viper007bond.com/contact/', 'http://codex.wordpress.org/Translating_WordPress' ); ?></p>

<?php
			break; // End credits

		case 'general':
		default;
?>
		<h3>Welcome!</h3>

		<p>Thanks for using Viper's Video Quicktags! This page will give you an overview on how to use this plugin.</p>

		<p>This plugin builds on top of the <a href="http://codex.wordpress.org/Embeds">native WordPress embeds feature</a> which allows you to embed multimedia (video, images, and more) from various websites. All you have to do is paste the URL to an item (such as <code>http://www.youtube.com/watch?v=rs-jAImScms</code>) on it's own line in a post or a page. It needs to not be hyperlinked (clickable). Once that is done, the URL will be converted into an embed if that website is supported by WordPress.</p>

		<p>You may be asking at this point what the point of my plugin is now that WordPress natively supports video embedding. Simple &#8212; my plugin allows you to customize that embed. Examples are things like autoplay or even just simple things such as the colors of the player (customization ability depends entirely on what the embed player itself supports). My plugin also makes all of the embed types it supports XHTML valid (most standard embeds will not validate).</p>

		<p>You can use this plugin right out of the box with no customizations and it will affect all previous embeds made using the above mentioned WordPress method or previous versions of my plugin. Note however that any embeds you created by manually copying and pasting embed HTML will not be affected.</p>

		<p>If you want to customize your video embeds though, check out the various sections listed above. Those video sites support customizing their player to better match your preferences. If you'd like something easy to start with, consider making the YouTube player match your site's color scheme using the <a href="<?php echo esc_attr( admin_url( 'options-general.php?page=vipers-video-quicktags&tab=youtube' ) ); ?>">YouTube</a> section.</p>

		<p>If you need more help, please see the <a href="<?php echo esc_attr( admin_url( 'options-general.php?page=vipers-video-quicktags&tab=help' ) ); ?>">Additional Help</a> section or post a thread on the <a href="http://wordpress.org/tags/vipers-video-quicktags?forum_id=10#postform">WordPress.org forums</a> (the official forums for support of my plugins).</p>
<?php
			// End General tab
	}
?>

<?php if ( !in_array( $tab, array( 'general', 'help', 'credits' ) ) ) : ?>
	<p class="submit">
		<input type="submit" name="vvq-submit" value="<?php _e('Save Changes', 'vipers-video-quicktags'); ?>" />
		<input type="submit" name="vvq-defaults" id="vvq-defaults" value="<?php _e('Reset Tab To Defaults', 'vipers-video-quicktags'); ?>" />
	</p>
<?php endif; ?>

	</form>
</div>

<?php
		/*
		echo '<pre>';
		print_r( get_option('vvq_options') );
		echo '</pre>';
		*/
	}


	// Some style tweaks for the settings page
	function settings_page_css() { ?>
<style type="text/css">
	.widefat td { vertical-align: middle; }
	#vvqsettingsform { margin-top: 50px; }
	#vvqsettingsform ul li {
		margin-left: 20px;
		list-style: disc;
	}
	.vvqwide { width: 98%; }
	.vvqnarrow { width: 75px; }
	.vvq-picker-wrap {
		position: absolute;
		display: none;
		background: #fff;
		border: 3px solid #ccc;
		padding: 3px;
		z-index: 1000;
	}
	.vvq-swatch {
		padding: 2px 10px;
		cursor: pointer;
		background: transparent url('<?php echo plugins_url('/vipers-video-quicktags/resources/images/color_wheel.png'); ?>') top left no-repeat;
	}
	.vvq-preset {
		float: left;
		margin: 2px 4px;
		padding: 0px;
		width: 0;
		height: 0;
		line-height: 0;
		cursor: pointer;
	}
	#vvq-help .vvq-help-title {
		font-weight: bold;
		color: #2583ad;
	}
</style>
<?php
	}


	// WordPress' esc_js() won't allow <, >, or " -- instead it converts it to an HTML entity. This is a "fixed" function that's used when needed.
	function esc_js($text) {
		$safe_text = wp_check_invalid_utf8( $text );
		$safe_text = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", stripslashes( $safe_text ) );
		$safe_text = str_replace( "\n", '\\n', addslashes( $safe_text ) );
		$safe_text = str_replace('\\\n', '\n', $safe_text);
		return apply_filters( 'js_escape', $safe_text, $text );

	}
}

// Wait until early in "init" to start up this plugin
add_action( 'init', 'VipersVideoQuicktags', 7 );
function VipersVideoQuicktags() {
	global $VipersVideoQuicktags;
	$VipersVideoQuicktags = new VipersVideoQuicktags();
}

?>