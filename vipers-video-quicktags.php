<?php /*

Plugin Name: Viper's Video Quicktags
Plugin URI: http://www.viper007bond.com/wordpress-plugins/vipers-video-quicktags/
Version: 5.4.4
Description: Allows you to embed various video types, including those hosted at <a href="http://www.youtube.com/">YouTube</a> and <a href="http://video.google.com/">Google Video</a> as well as videos you host yourself, into WordPress. <strong>Credits:</strong> <a href="http://asymptomatic.net">Owen Winkler</a> for <a href="http://redalt.com/wiki/ButtonSnap">ButtonSnap</a> and <a href="http://an-archos.com/">An-archos</a> for help with WP 2.1+ button code.
Author: Viper007Bond
Author URI: http://www.viper007bond.com/

*/

# Nothing to see here! Please use the plugin's options page. You can configure everything there.

class VipersVideoQuicktags {
	var $version = '5.4.4';
	var $folder = '/wp-content/plugins/vipers-video-quicktags'; // You shouldn't need to change this ;)
	var $fullfolderurl;
	var $settings = array();
	var $defaultsettings = array();
	var $wpversion;
	var $jsoutput;
	var $searchpatterns = array();


	// Don't start this plugin until all other plugins have started up
	function VipersVideoQuicktags() {
		add_action('plugins_loaded', array(&$this, 'Initalization'));
	}

	
	// Initialization stuff
	function Initalization() {
		$this->fullfolderurl = get_bloginfo('wpurl') . $this->folder . '/';

		$this->defaultsettings = array(
			'youtube' => array(
				'button'         => 'on',
				'width'          => '425',
				'height'         => '355',
			),
			'googlevideo' => array(
				'button'         => 'on',
				'width'          => '400',
				'height'         => '326',
			),
			'ifilm' => array(
				'button'         => NULL,
				'width'          => '448',
				'height'         => '365',
			),
			'metacafe' => array(
				'button'         => NULL,
				'width'          => '400',
				'height'         => '345',
			),
			'myspace' => array(
				'button'         => NULL,
				'width'          => '430',
				'height'         => '346',
			),
			'vimeo' => array(
				'button'         => NULL,
				'width'          => '400',
				'height'         => '300',
			),
			'quicktime' => array(
				'button'         => NULL,
				'width'          => '400',
				'height'         => '300',
			),
			'videofile' => array(
				'button'         => NULL,
				'width'          => '400',
				'height'         => '300',
			),
			'flv' => array(
				'button'         => NULL,
				'width'          => '400',
				'height'         => '300',
			),
			'tinymce_linenumber' => 1,
			'usewmp'             => 'on',
			'alignment'          => 'center',
			'promptforwh'        => NULL,
		);

		$this->settings = get_option('vvq_options');
		if ( !is_array($this->settings['youtube']) ) {
			$this->settings = $this->defaultsettings;
		}

		// Set required options added in later versions for people upgrading
		if ( empty($this->settings['alignment']) )           $this->settings['alignment']           = $this->defaultsettings['alignment'];
		if ( empty($this->settings['promptforwh']) )         $this->settings['promptforwh']         = $this->defaultsettings['promptforwh'];
		if ( empty($this->settings['vimeo']['button']) )     $this->settings['vimeo']['button']     = $this->defaultsettings['vimeo']['button'];
		if ( empty($this->settings['vimeo']['width']) )      $this->settings['vimeo']['width']      = $this->defaultsettings['vimeo']['width'];
		if ( empty($this->settings['vimeo']['height']) )     $this->settings['vimeo']['height']     = $this->defaultsettings['vimeo']['height'];
		if ( empty($this->settings['quicktime']['width']) )  $this->settings['quicktime']['width']  = $this->defaultsettings['quicktime']['width'];
		if ( empty($this->settings['quicktime']['height']) ) $this->settings['quicktime']['height'] = $this->defaultsettings['quicktime']['height'];
		if ( empty($this->settings['videofile']['width']) )  $this->settings['videofile']['width']  = $this->defaultsettings['videofile']['width'];
		if ( empty($this->settings['videofile']['height']) ) $this->settings['videofile']['height'] = $this->defaultsettings['videofile']['height'];
		if ( empty($this->settings['flv']['width']) )        $this->settings['flv']['width']        = $this->defaultsettings['flv']['width'];
		if ( empty($this->settings['flv']['height']) )       $this->settings['flv']['height']       = $this->defaultsettings['flv']['height'];

		// Load up the localization file if we're using WordPress in a different language
		// Place it in the "localization" folder and name it "vvq-[value in wp-config].mo"
		// A link to some localization files is location at the homepage of this plugin
		load_plugin_textdomain('vvq', $this->folder . '/localization');

		// No sense in running the addbuttons() function if no buttons are to be displayed
		if ( TRUE === $this->anybuttons() ) add_action('init', array(&$this, 'addbuttons'));

		// Figure out the WordPress version
		global $wp_db_version;
		if ( $wp_db_version > 6124 ) // add_meta_box() isn't defined at this point, so db_version works well here
			$this->wpversion = 2.5;
		elseif ( class_exists('WP_Scripts') )
			$this->wpversion = 2.1;
		else
			$this->wpversion = 2.0;

		// Loads the needed Javascript file
		if ( $this->wpversion >= 2.1 ) wp_enqueue_script('vvq', $this->folder . '/vipers-video-quicktags.js', FALSE, $this->version);

		# Register our hooks and filter
		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_action('admin_head', array(&$this, 'admin_head'));
		add_action('edit_form_advanced', array(&$this, 'edit_form'));
		add_action('edit_page_form', array(&$this, 'edit_form'));
		add_action('wp_head', array(&$this, 'wp_head'));
		add_filter('the_content', array(&$this, 'replacebbcode'), 1);
		add_filter('get_the_excerpt', array(&$this, 'replacebbcode'), 1);
		add_filter('the_content', array(&$this, 'addinlinejs'), 11);
		add_filter('get_the_excerpt', array(&$this, 'addinlinejs'), 11);
		// Add support for the text widget
		add_filter('widget_text', array(&$this, 'replacebbcode'), 1);
		add_filter('widget_text', array(&$this, 'addinlinejs'), 11);


		// This is the regex we use to search and then what order the data will come out in
		// Format is: 'match regex' => array('type' => 'videotype', results => array( ... ))
		// The type is used internally and the results array is the order in which the data will be returned (width, height, url, + anything else you want)
		$this->searchpatterns = array (
			'#\[youtube\]http://(www.youtube|youtube|[A-Za-z]{2}.youtube)\.com/(watch\?v=|w/\?v=)([\w-]+)(.*?)\[/youtube\]#i' => array('type' => 'youtube', 'results' => array('', '', 'videoid')),
			'#\[youtube\]([\w-]+)\[/youtube\]#i' => array('type' => 'youtube', 'results' => array('videoid')),
			'#\[youtube width="(\d+)" height="(\d+)"]http://(www.youtube|youtube|[A-Za-z]{2}.youtube)\.com/(watch\?v=|w/\?v=)([\w-]+)(.*?)\[\/youtube]#i' => array('type' => 'youtube', 'results' => array('width', 'height', '', '', 'videoid')),
			'#\[youtube width="(\d+)" height="(\d+)"]([\w-]+)\[\/youtube]#i' => array('type' => 'youtube', 'results' => array('width', 'height', 'videoid')),

			'#\[googlevideo]http://video\.google\.([A-Za-z.]{2,5})/videoplay\?docid=([\d-]+)(.*?)\[\/googlevideo]#i' => array('type' => 'googlevideo', 'results' => array('', 'videoid')),
			'#\[googlevideo]([\d-]+)\[\/googlevideo]#i' => array('type' => 'googlevideo', 'results' => array('videoid')),
			'#\[googlevideo width="(\d+)" height="(\d+)"]http://video\.google\.([A-Za-z.]{2,5})/videoplay\?docid=([\d-]+)(.*?)\[\/googlevideo]#i'
						=> array('type' => 'googlevideo', 'results' => array('width', 'height', '', 'videoid')),
			'#\[googlevideo width="(\d+)" height="(\d+)"]([\d-]+)\[\/googlevideo]#i' => array('type' => 'googlevideo', 'results' => array('width', 'height', 'videoid')),

			'#\[stage6]http://(www.stage6.com|stage6.com|stage6.divx.com)/(.*?)/video/([0-9]+)(.*?)\[\/stage6]#i' => array('type' => 'stage6', 'results' => array('', '', 'videoid')),
			'#\[stage6 width="(\d+)" height="(\d+)"]http://(www.stage6.com|stage6.com|stage6.divx.com)/(.*?)/video/([0-9]+)(.*?)\[\/stage6]#i' => array('type' => 'stage6', 'results' => array('width', 'height', '', '', 'videoid')),

			'#\[ifilm]http://(www.ifilm|ifilm)\.com/video/([\d-]+)(.*?)\[\/ifilm]#i' => array('type' => 'ifilm', 'results' => array('', 'videoid')),
			'#\[ifilm]([\d-]+)\[\/ifilm]#i' => array('type' => 'ifilm', 'results' => array('videoid')),
			'#\[ifilm width="(\d+)" height="(\d+)"]http://(www.ifilm|ifilm)\.com/video/([\d-]+)(.*?)\[\/ifilm]#i' => array('type' => 'ifilm', 'results' => array('width', 'height', '', 'videoid')),
			'#\[ifilm width="(\d+)" height="(\d+)"]([\d-]+)\[\/ifilm]#i' => array('type' => 'ifilm', 'results' => array('width', 'height', 'videoid')),

			'#\[metacafe]http://(www.metacafe|metacafe)\.com/watch/([\d-]+)/(.*?)/\[\/metacafe]#i' => array('type' => 'metacafe', 'results' => array('', 'videoid', 'videoname')),
			'#\[metacafe width="(\d+)" height="(\d+)"]http://(www.metacafe|metacafe)\.com/watch/([\d-]+)/([\d-]+)/\[\/metacafe]#i'
						=> array('type' => 'metacafe', 'results' => array('width', 'height', '', 'videoid', 'videoname')),

			'#\[myspace]http://vids\.myspace\.com/index\.cfm\?fuseaction=vids\.individual(&|&amp;)videoid=(\d+)\[\/myspace]#i' => array('type' => 'myspace', 'results' => array('', 'videoid')),
			'#\[myspace width="(\d+)" height="(\d+)"]http://vids\.myspace\.com/index\.cfm\?fuseaction=vids\.individual(&|&amp;)videoid=(\d+)\[\/myspace]#i'
						=> array('type' => 'ifilm', 'results' => array('width', 'height', '', 'videoid')),

			'#\[vimeo]http://(www.vimeo|vimeo)\.com(/|/clip:)([\d-]+)(.*?)\[\/vimeo]#i' => array('type' => 'vimeo', 'results' => array('', '', 'videoid')),
			'#\[vimeo]([\d-]+)\[\/vimeo]#i' => array('type' => 'vimeo', 'results' => array('videoid')),
			'#\[vimeo width="(\d+)" height="(\d+)"]http://(www.vimeo|vimeo)\.com(/|/clip:)([\d-]+)(.*?)\[\/vimeo]#i' => array('type' => 'vimeo', 'results' => array('width', 'height', '', '', 'videoid')),
			'#\[vimeo width="(\d+)" height="(\d+)"]([\d-]+)\[\/vimeo]#i' => array('type' => 'vimeo', 'results' => array('width', 'height', 'videoid')),

			'#\[flv](.*?)\[\/flv]#i' => array('type' => 'flv', 'results' => array('videoid')),
			'#\[flv width="(\d+)" height="(\d+)"](.*?)\[\/flv]#i' => array('type' => 'flv', 'results' => array('width', 'height', 'videoid')),

			// VERY old (v2.x) placeholder handling
			'#\<!--youtubevideo--><span style="display: none">([\w-]+)</span><!--youtubevideoend-->#i' => array('type' => 'youtube', 'results' => array('videoid')),
			'#\<!--googlevideovideo--><span style="display: none">([\w-]+)</span><!--googlevideovideoend-->#i' => array('type' => 'googlevideo', 'results' => array('videoid')),
		);
		$this->searchpatterns = apply_filters( 'vvq_searchpatterns', $this->searchpatterns );
	}


	// Checks to see if any buttons at all are to be displayed
	function anybuttons() {
		if ('on' == $this->settings['youtube']['button'] ||
			'on' == $this->settings['googlevideo']['button'] ||
			'on' == $this->settings['ifilm']['button'] ||
			'on' == $this->settings['metacafe']['button'] ||
			'on' == $this->settings['myspace']['button'] ||
			'on' == $this->settings['vimeo']['button'] ||
			'on' == $this->settings['quicktime']['button'] ||
			'on' == $this->settings['videofile']['button'] ||
			'on' == $this->settings['flv']['button']
		)
			return TRUE;
		else
			return FALSE;
	}


	// Make our buttons on the write screens
	function addbuttons() {
		// Don't bother doing this stuff if the current user lacks permissions as they'll never see the pages
		if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) return;

		// Create the buttons based on the WP version number
		if ( 'true' == get_user_option('rich_editing') && $this->wpversion >= 2.1 ) {
			// WordPress 2.5+ (TinyMCE 3.x)
			if ( $this->wpversion >= 2.5 ) {
				add_filter( 'mce_external_plugins', array(&$this, 'mce_external_plugins') );
				add_filter( 'mce_buttons_3', array(&$this, 'mce_buttons') );
				add_action( 'admin_head', array(&$this, 'buttonhider') );
			}

			// WordPress 2.1+ (TinyMCE 2.x)
			else {
				add_filter('mce_plugins', array(&$this, 'mce_plugins'));
				if ( 1 != $this->settings['tinymce_linenumber'] ) {
					add_filter('mce_buttons_' . $this->settings['tinymce_linenumber'], array(&$this, 'mce_buttons'));
				} else {
					add_filter('mce_buttons', array(&$this, 'mce_buttons'));
				}
				add_action('tinymce_before_init', array(&$this, 'tinymce_before_init'));
				add_action('admin_head', array(&$this, 'buttonhider'));
			}
		} else {
			buttonsnap_separator();
			if ( 'on' == $this->settings['youtube']['button'] )
				buttonsnap_jsbutton($this->fullfolderurl . 'images/youtube.png', __('YouTube', 'vvq'), 'VVQInsertVideoSite("' . __('YouTube', 'vvq') . '", "http://www.youtube.com/watch?v=JzqumbhfxRo", "youtube");');
			if ( 'on' == $this->settings['googlevideo']['button'] )
				buttonsnap_jsbutton($this->fullfolderurl . 'images/googlevideo.png', __('GVideo', 'vvq'), 'VVQInsertVideoSite("' . __('Google Video', 'vvq') . '", "http://video.google.com/videoplay?docid=3688185030664621355", "googlevideo");');
			if ( 'on' == $this->settings['ifilm']['button'] )
				buttonsnap_jsbutton($this->fullfolderurl . 'images/ifilm.png', __('IFILM', 'vvq'), 'VVQInsertVideoSite("' . __('IFILM', 'vvq') . '", "http://www.ifilm.com/video/2710582", "ifilm");');
			if ( 'on' == $this->settings['metacafe']['button'] )
				buttonsnap_jsbutton($this->fullfolderurl . 'images/metacafe.png', __('Metacafe', 'vvq'), 'VVQInsertVideoSite("' . __('Metacafe', 'vvq') . '", "http://www.metacafe.com/watch/299980/italian_police_lamborghini/", "metacafe");');
			if ( 'on' == $this->settings['myspace']['button'] )
				buttonsnap_jsbutton($this->fullfolderurl . 'images/myspace.png', __('MySpace', 'vvq'), 'VVQInsertVideoSite("' . __('MySpace', 'vvq') . '", "http://vids.myspace.com/index.cfm?fuseaction=vids.individual&videoid=1387215221", "myspace");');
			if ( 'on' == $this->settings['vimeo']['button'] )
				buttonsnap_jsbutton($this->fullfolderurl . 'images/vimeo.png', __('Vimeo', 'vvq'), 'VVQInsertVideoSite("' . __('Vimeo', 'vvq') . '", "http://www.vimeo.com/27810", "vimeo");');
			if ( 'on' == $this->settings['quicktime']['button'] )
				buttonsnap_jsbutton($this->fullfolderurl . 'images/quicktime.png', __('QT', 'vvq'), 'VVQInsertVideoFile("' . __('Quicktime', 'vvq') . '", "mov", "quicktime");');
			if ( 'on' == $this->settings['videofile']['button'] )
				buttonsnap_jsbutton($this->fullfolderurl . 'images/videofile.png', __('Video File', 'vvq'), 'VVQInsertVideoFile("", "avi", "video");');
			if ( 'on' == $this->settings['flv']['button'] )
				buttonsnap_jsbutton($this->fullfolderurl . 'images/flv.png', __('FLV', 'vvq'), 'VVQInsertVideoFile("' . __('FLV', 'vvq') . '", "flv", "flv");');
		}
	}


	// TinyMCE integration hooks
	function mce_external_plugins( $plugins ) {
		// WordPress 2.5
		$plugins['vipersvideoquicktags'] = get_bloginfo('wpurl') . $this->folder . '/resources/tinymce3/editor_plugin.js';
		return $plugins;
	}
	function mce_plugins($plugins) {
		// WordPress 2.1
		array_push($plugins, 'vipersvideoquicktags');
		return $plugins;
	}
	function mce_buttons($buttons) {
		if ( $this->wpversion < 2.5 ) {
			if ( 1 == $this->settings['tinymce_linenumber'] ) array_push($buttons, 'separator');
			array_push( $buttons, 'vipersvideoquicktags' );
		} else {
			array_push( $buttons, 'vvqYouTube', 'vvqGoogleVideo', 'vvqIFILM', 'vvqMetaCafe', 'vvqMySpace', 'vvqVimeo', 'vvqQuicktime', 'vvqVideoFile', 'vvqFLV' );
		}

		return $buttons;
	}
	function tinymce_before_init() {
		// WordPress 2.1
		echo 'tinyMCE.loadPlugin("vipersvideoquicktags", "' . $this->fullfolderurl . 'resources/tinymce2/");';
	}


	// Hide TinyMCE buttons the user doesn't want to see in WP v2.1+
	function buttonhider() {
		echo "<style type='text/css'>\n";

		if ( $this->wpversion < 2.5 ) {
			if ( 'on' != $this->settings['youtube']['button'] )     echo "	#mce_editor_0_vvq_youtube     { display: none; }\n";
			if ( 'on' != $this->settings['googlevideo']['button'] ) echo "	#mce_editor_0_vvq_googlevideo { display: none; }\n";
			if ( 'on' != $this->settings['ifilm']['button'] )       echo "	#mce_editor_0_vvq_ifilm       { display: none; }\n";
			if ( 'on' != $this->settings['metacafe']['button'] )    echo "	#mce_editor_0_vvq_metacafe    { display: none; }\n";
			if ( 'on' != $this->settings['myspace']['button'] )     echo "	#mce_editor_0_vvq_myspace     { display: none; }\n";
			if ( 'on' != $this->settings['vimeo']['button'] )       echo "	#mce_editor_0_vvq_vimeo       { display: none; }\n";
			if ( 'on' != $this->settings['quicktime']['button'] )   echo "	#mce_editor_0_vvq_quicktime   { display: none; }\n";
			if ( 'on' != $this->settings['videofile']['button'] )   echo "	#mce_editor_0_vvq_videofile   { display: none; }\n";
			if ( 'on' != $this->settings['flv']['button'] )         echo "	#mce_editor_0_vvq_flv         { display: none; }\n";
		} else {
			if ( 'on' != $this->settings['youtube']['button'] )     echo "	.mce_vvqYouTube     { display: none !important; }\n";
			if ( 'on' != $this->settings['googlevideo']['button'] ) echo "	.mce_vvqGoogleVideo { display: none !important; }\n";
			if ( 'on' != $this->settings['ifilm']['button'] )       echo "	.mce_vvqIFILM       { display: none !important; }\n";
			if ( 'on' != $this->settings['metacafe']['button'] )    echo "	.mce_vvqMetaCafe    { display: none !important; }\n";
			if ( 'on' != $this->settings['myspace']['button'] )     echo "	.mce_vvqMySpace     { display: none !important; }\n";
			if ( 'on' != $this->settings['vimeo']['button'] )       echo "	.mce_vvqVimeo       { display: none !important; }\n";
			if ( 'on' != $this->settings['quicktime']['button'] )   echo "	.mce_vvqQuicktime   { display: none !important; }\n";
			if ( 'on' != $this->settings['videofile']['button'] )   echo "	.mce_vvqVideoFile   { display: none !important; }\n";
			if ( 'on' != $this->settings['flv']['button'] )         echo "	.mce_vvqFLV         { display: none !important; }\n";
		}

		echo "</style>\n";
	}


	function admin_menu() {
		add_options_page(__("Viper's Video Quicktags Configuration", 'vvq'), __('Video Quicktags', 'vvq'), 'manage_options', basename(__FILE__), array(&$this, 'optionspage'));
	}


	// Handle form submit and add the Javascript file
	function admin_head() {
		// Handle options page submits
		if ( $_POST && basename(__FILE__) == $_GET['page'] ) {
			if ( $_POST['defaults'] ) {
				$this->settings = $this->defaultsettings;
			} else {
				$this->settings = array(
					'youtube' => array(
						'button'         => $_POST['youtube']['button'],
						'width'          => (int) $_POST['youtube']['width'],
						'height'         => (int) $_POST['youtube']['height'],
					),
					'googlevideo' => array(
						'button'         => $_POST['googlevideo']['button'],
						'width'          => (int) $_POST['googlevideo']['width'],
						'height'         => (int) $_POST['googlevideo']['height'],
					),
					'ifilm' => array(
						'button'         => $_POST['ifilm']['button'],
						'width'          => (int) $_POST['ifilm']['width'],
						'height'         => (int) $_POST['ifilm']['height'],
					),
					'metacafe' => array(
						'button'         => $_POST['metacafe']['button'],
						'width'          => (int) $_POST['metacafe']['width'],
						'height'         => (int) $_POST['metacafe']['height'],
					),
					'myspace' => array(
						'button'         => $_POST['myspace']['button'],
						'width'          => (int) $_POST['myspace']['width'],
						'height'         => (int) $_POST['myspace']['height'],
					),
					'vimeo' => array(
						'button'         => $_POST['vimeo']['button'],
						'width'          => (int) $_POST['vimeo']['width'],
						'height'         => (int) $_POST['vimeo']['height'],
					),
					'quicktime' => array(
						'button'         => $_POST['quicktime']['button'],
						'width'          => (int) $_POST['quicktime']['width'],
						'height'         => (int) $_POST['quicktime']['height'],
					),
					'videofile' => array(
						'button'         => $_POST['videofile']['button'],
						'width'          => (int) $_POST['videofile']['width'],
						'height'         => (int) $_POST['videofile']['height'],
					),
					'flv' => array(
						'button'         => $_POST['flv']['button'],
						'width'          => (int) $_POST['flv']['width'],
						'height'         => (int) $_POST['flv']['height'],
					),
					'tinymce_linenumber' => (int) $_POST['tinymce_linenumber'],
					'usewmp'             => $_POST['usewmp'],
					'alignment'          => $_POST['alignment'],
					'promptforwh'        => $_POST['promptforwh'],
					
				);
			}
			update_option('vvq_options', $this->settings);
		}
	}

	
	// Outputs the needed Javascript (not in a .js file as it's dynamic and just easier this way)
	function edit_form() { ?>

<!-- Start Viper's Video Quicktags Javascript -->
<script type="text/javascript">
/* <![CDATA[ */
<?php if ( 'on' == $this->settings['promptforwh'] ) : ?>
	// Default widths
	widths = {
		youtube: <?php echo $this->settings['youtube']['width']; ?>,
		googlevideo: <?php echo $this->settings['googlevideo']['width']; ?>,
		ifilm: <?php echo $this->settings['ifilm']['width']; ?>,
		metacafe: <?php echo $this->settings['metacafe']['width']; ?>,
		myspace: <?php echo $this->settings['myspace']['width']; ?>,
		vimeo: <?php echo $this->settings['vimeo']['width']; ?>,
		quicktime: <?php echo $this->settings['quicktime']['width']; ?>,
		video: <?php echo $this->settings['videofile']['width']; ?>,
		flv: <?php echo $this->settings['flv']['width']; ?>

	}

	// Default heights
	heights = {
		youtube: <?php echo $this->settings['youtube']['height']; ?>,
		googlevideo: <?php echo $this->settings['googlevideo']['height']; ?>,
		ifilm: <?php echo $this->settings['ifilm']['height']; ?>,
		metacafe: <?php echo $this->settings['metacafe']['height']; ?>,
		myspace: <?php echo $this->settings['myspace']['height']; ?>,
		vimeo: <?php echo $this->settings['vimeo']['height']; ?>,
		quicktime: <?php echo $this->settings['quicktime']['height']; ?>,
		video: <?php echo $this->settings['videofile']['height']; ?>,
		flv: <?php echo $this->settings['flv']['height']; ?>

	}
<?php endif; ?>

	// Function for video websites
	function VVQInsertVideoSite(sitename, example, tag) {
		var videoURL = prompt('<?php echo str_replace(array("'", '%sitename%'), array("\'", "' + sitename + '"), __('Please enter the URL that the %sitename% video is located at.\n\nExample:', 'vvq')) . " ' + example"; ?>);

		if ( !videoURL ) { return; }

<?php if ( 'on' == $this->settings['promptforwh'] ) : ?>
		var width = prompt('<?php echo str_replace("'", "\'", __('How many pixels WIDE would you like to display this video?\n\nLeave this box blank or press Cancel to use the default width.', 'vvq')); ?>', widths[tag]);
		width = Number(width);

		if ( !width ) {
			buttonsnap_settext('[' + tag + ']' + videoURL + '[/' + tag + ']');
			return;
		}

		var suggestedheight = Math.round(width*(heights[tag]/widths[tag]));

		var height = prompt('<?php echo str_replace("'", "\'", __('How many pixels TALL would you like to display this video?', 'vvq')); ?>', suggestedheight);
		height = Number(height);

		if ( !height ) {
			buttonsnap_settext('[' + tag + ']' + videoURL + '[/' + tag + ']');
			return;
		}

		buttonsnap_settext('[' + tag + ' width="' + width + '" height="' + height + '"]' + videoURL  + '[/' + tag + ']');
<?php else : ?>
		buttonsnap_settext('[' + tag + ']' + videoURL + '[/' + tag + ']');
<?php endif; ?>
	}

	// Function for video files
	function VVQInsertVideoFile(nicename, extension, tag) {
		if ( '' != nicename ) nicename = nicename + ' ';

		var videoURL = prompt('<?php	echo str_replace(array("'", '%videotype% '), array("\'", "' + nicename + '"), __('Please enter the FULL URL to the %videotype% video file:\n\nExample:', 'vvq')); ?> http://www.yoursite.com/myvideo.' + extension);

		if ( !videoURL ) { return; }

<?php if ( 'on' == $this->settings['promptforwh'] ) : ?>
		var width = prompt('<?php echo str_replace("'", "\'", __('How many pixels WIDE would you like to display this video?\n\nLeave this box blank or press Cancel to use the default width.', 'vvq')); ?>', widths[tag]);
		width = Number(width);

		if ( !width ) {
			buttonsnap_settext('[' + tag + ']' + videoURL + '[/' + tag + ']');
			return;
		}

		var suggestedheight = Math.round(width*(heights[tag]/widths[tag]));

		var height = prompt('<?php echo str_replace("'", "\'", __('How many pixels TALL would you like to display this video?', 'vvq')); ?>', suggestedheight);
		height = Number(height);

		if ( !height ) {
			buttonsnap_settext('[' + tag + ']' + videoURL + '[/' + tag + ']');
			return;
		}

		buttonsnap_settext('[' + tag + ' width="' + width + '" height="' + height + '"]' + videoURL  + '[/' + tag + ']');
<?php else : ?>
		buttonsnap_settext('[' + tag + ']' + videoURL + '[/' + tag + ']');
<?php endif; ?>
	}
/* ]]> */
</script>
<!-- End Viper's Video Quicktags Javascript -->

<?php
	} // edit_form()


	// The contents of the options page
	function optionspage() {
		if ( !empty($_POST['defaults']) )
			echo "\n" . '<div id="message" class="updated fade"><p><strong>' . __('Settings reset to defaults.', 'vvq') . '</strong></p></div>' . "\n";
		elseif ( !empty($_POST) )
			echo "\n" . '<div id="message" class="updated fade"><p><strong>' . __('Settings saved.') . '</strong></p></div>' . "\n";

	?>

<script type="text/javascript">
/* <![CDATA[ */
	function updateCustomHeight(inputField, outputField, defaultWidth, defaultHeight) {
		var ratio = defaultWidth/defaultHeight;
		var width = inputField.value;

		document.getElementById(outputField).value = Math.round(width/ratio);
	}
/* ]]> */
</script>

<div class="wrap">

	<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
		<input type="hidden" name="cmd" value="_donations" />
		<input type="hidden" name="business" value="paypal@viper007bond.com" />
		<input type="hidden" name="item_name" value="Viper's Video Quicktags" />
		<input type="hidden" name="no_shipping" value="1" />
		<input type="hidden" name="return" value="http://www.viper007bond.com/donation-thanks/" />
		<input type="hidden" name="cancel_return" value="http://www.viper007bond.com/wordpress-plugins/vipers-video-quicktags/" />
		<input type="hidden" name="cn" value="Optional Comment" />
		<input type="hidden" name="currency_code" value="USD" />
		<input type="hidden" name="tax" value="0" />
		<input type="hidden" name="lc" value="US" />
		<input type="hidden" name="bn" value="PP-DonationsBF" />

		<h2>
			<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" name="submit" alt="Make payments with PayPal - it's fast, free and secure!" title="Donate to Viper007Bond for this plugin via PayPal" style="float:right" />
			<?php _e("Viper's Video Quicktags Configuration", 'vvq'); ?>
		</h2>

		<img alt="" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />
	</form>

	<form name="vvq_config" method="post" action="">

<?php if ( $this->wpversion < 2.5 ) echo '	<fieldset class="options">'; ?>

		<p><?php _e('Please note that even if you hide a button, the BBCode for that video type will still continue to work. The buttons are only there to make your life easier.', 'vvq'); ?></p>

		<table <?php echo ( $this->wpversion >= 2.1 ) ? 'class="widefat"' : 'width="100%" cellpadding="3" cellspacing="3"'; ?> style="text-align: center"> 
			<thead>
				<tr>
					<th scope="col" style="text-align: center"><?php _e('Media Type', 'vvq'); ?></th>
					<th scope="col" style="text-align: center"><?php _e('Show Button?', 'vvq'); ?></th>
					<th scope="col" style="text-align: center"><?php _e('Default Width', 'vvq'); ?></th>
					<th scope="col" style="text-align: center"><?php _e('Default Height', 'vvq'); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr class="alternate">
					<td><a href="http://www.youtube.com/"><?php _e('YouTube', 'vvq'); ?></a></td>
					<td><input name="youtube[button]" type="checkbox"<?php checked($this->settings['youtube']['button'], 'on'); ?> /></td>
					<td><input name="youtube[width]" type="text" size="5" value="<?php echo $this->settings['youtube']['width']; ?>" onchange="updateCustomHeight(this, 'youtubeheight', '<?php echo $this->defaultsettings['youtube']['width']; ?>', '<?php echo $this->defaultsettings['youtube']['height']; ?>')" /></td>
					<td><input name="youtube[height]" id="youtubeheight" type="text" size="5" value="<?php echo $this->settings['youtube']['height']; ?>" /></td>
				</tr>
				<tr>
					<td><a href="http://video.google.com/"><?php _e('Google Video', 'vvq'); ?></a></td>
					<td><input name="googlevideo[button]" type="checkbox"<?php checked($this->settings['googlevideo']['button'], 'on'); ?> /></td>
					<td><input name="googlevideo[width]" type="text" size="5" value="<?php echo $this->settings['googlevideo']['width']; ?>" onchange="updateCustomHeight(this, 'googlevideoheight', '<?php echo $this->defaultsettings['googlevideo']['width']; ?>', '<?php echo $this->defaultsettings['googlevideo']['height']; ?>')" /></td>
					<td><input name="googlevideo[height]" id="googlevideoheight" type="text" size="5" value="<?php echo $this->settings['googlevideo']['height']; ?>" /></td>
				</tr>
				<tr class="alternate">
					<td><a href="http://www.ifilm.com/"><?php _e('IFILM', 'vvq'); ?></a></td>
					<td><input name="ifilm[button]" type="checkbox"<?php checked($this->settings['ifilm']['button'], 'on'); ?> /></td>
					<td><input name="ifilm[width]" type="text" size="5" value="<?php echo $this->settings['ifilm']['width']; ?>" onchange="updateCustomHeight(this, 'ifilmheight', '<?php echo $this->defaultsettings['ifilm']['width']; ?>', '<?php echo $this->defaultsettings['ifilm']['height']; ?>')" /></td>
					<td><input name="ifilm[height]" id="ifilmheight" type="text" size="5" value="<?php echo $this->settings['ifilm']['height']; ?>" /></td>
				</tr>
				<tr>
					<td><a href="http://www.metacafe.com/"><?php _e('Metacafe', 'vvq'); ?></a></td>
					<td><input name="metacafe[button]" type="checkbox"<?php checked($this->settings['metacafe']['button'], 'on'); ?> /></td>
					<td><input name="metacafe[width]" type="text" size="5" value="<?php echo $this->settings['metacafe']['width']; ?>" onchange="updateCustomHeight(this, 'metacafeheight', '<?php echo $this->defaultsettings['metacafe']['width']; ?>', '<?php echo $this->defaultsettings['metacafe']['height']; ?>')" /></td>
					<td><input name="metacafe[height]" id="metacafeheight" type="text" size="5" value="<?php echo $this->settings['metacafe']['height']; ?>" /></td>
				</tr>
				<tr class="alternate">
					<td><a href="http://www.myspace.com/"><?php _e('MySpace', 'vvq'); ?></a></td>
					<td><input name="myspace[button]" type="checkbox"<?php checked($this->settings['myspace']['button'], 'on'); ?> /></td>
					<td><input name="myspace[width]" type="text" size="5" value="<?php echo $this->settings['myspace']['width']; ?>" onchange="updateCustomHeight(this, 'myspaceheight', '<?php echo $this->defaultsettings['myspace']['width']; ?>', '<?php echo $this->defaultsettings['myspace']['height']; ?>')" /></td>
					<td><input name="myspace[height]" id="myspaceheight" type="text" size="5" value="<?php echo $this->settings['myspace']['height']; ?>" /></td>
				</tr>
				<tr>
					<td><a href="http://www.vimeo.com/"><?php _e('Vimeo', 'vvq'); ?></a></td>
					<td><input name="vimeo[button]" type="checkbox"<?php checked($this->settings['vimeo']['button'], 'on'); ?> /></td>
					<td><input name="vimeo[width]" type="text" size="5" value="<?php echo $this->settings['vimeo']['width']; ?>" onchange="updateCustomHeight(this, 'vimeoheight', '<?php echo $this->defaultsettings['vimeo']['width']; ?>', '<?php echo $this->defaultsettings['vimeo']['height']; ?>')" /></td>
					<td><input name="vimeo[height]" id="vimeoheight" type="text" size="5" value="<?php echo $this->settings['vimeo']['height']; ?>" /></td>
				</tr>
				<tr class="alternate">
					<td><a href="http://www.apple.com/quicktime/"><?php _e('Quicktime', 'vvq'); ?></a></td>
					<td><input name="quicktime[button]" type="checkbox"<?php checked($this->settings['quicktime']['button'], 'on'); ?> /></td>
					<td><input name="quicktime[width]" type="text" size="5" value="<?php echo $this->settings['quicktime']['width']; ?>" onchange="updateCustomHeight(this, 'quicktimeheight', 4, 3)" /></td>
					<td><input name="quicktime[height]" id="quicktimeheight" type="text" size="5" value="<?php echo $this->settings['quicktime']['height']; ?>" /></td>
				</tr>
				<tr>
					<td>
						<?php _e('Generic Video File', 'vvq'); ?><br />
						<small>Implementation of this feature isn't<br />perfect and may need some work</small>
					</td>
					<td><input name="videofile[button]" type="checkbox"<?php checked($this->settings['videofile']['button'], 'on'); ?> /></td>
					<td><input name="videofile[width]" type="text" size="5" value="<?php echo $this->settings['videofile']['width']; ?>" onchange="updateCustomHeight(this, 'videofileheight', 4, 3)" /></td>
					<td><input name="videofile[height]" id="videofileheight" type="text" size="5" value="<?php echo $this->settings['videofile']['height']; ?>" /></td>
				</tr>
				<tr class="alternate">
					<td><?php _e('Flash Video File (FLV)', 'vvq'); ?></td>
					<td><input name="flv[button]" type="checkbox"<?php checked($this->settings['flv']['button'], 'on'); ?> /></td>
					<td><input name="flv[width]" type="text" size="5" value="<?php echo $this->settings['flv']['width']; ?>" onchange="updateCustomHeight(this, 'flvheight', 4, 3)" /></td>
					<td><input name="flv[height]" id="flvheight" type="text" size="5" value="<?php echo $this->settings['flv']['height']; ?>" /></td>
				</tr>
			</tbody>
		</table>
<?php if ( $this->wpversion < 2.5 ) echo '	</fieldset>'; ?>


<?php if ( $this->wpversion < 2.5 ) echo '	<fieldset class="options">'; ?>

<?php echo ( $this->wpversion < 2.5 ) ? '<legend>' . __('Other Options', 'vvq') . '</legend>' : '<h3>' . __('Other Options', 'vvq') . '</h3>'; ?>

		<table class="<?php echo ( $this->wpversion < 2.5 ) ? 'optiontable' : 'form-table'; ?>">
			<tr valign="top">
				<th scope="row"><?php _e('Video Alignment', 'vvq'); ?></th>
				<td>
					<select name="alignment">
						<option value="left"<?php selected($this->settings['alignment'], 'left'); ?>><?php _e('Left', 'vvq'); ?></option>
						<option value="center"<?php selected($this->settings['alignment'], 'center'); ?>><?php _e('Center', 'vvq'); ?></option>
						<option value="right"<?php selected($this->settings['alignment'], 'right'); ?>><?php _e('Right', 'vvq'); ?></option>
					</select>
				</td>
			</tr>
<?php if ( $this->wpversion < 2.5 ) : ?>
			<tr valign="top">
				<th scope="row">
					<?php _e('TinyMCE Buttons', 'vvq'); ?><br />
				</th>
				<td>
					<label><input name="tinymce_linenumber"  type="radio" value="1"<?php checked($this->settings['tinymce_linenumber'], 1); ?> /> <?php _e('Line #1', 'vvq'); ?></label><br />
					<label><input name="tinymce_linenumber" type="radio" value="2"<?php checked($this->settings['tinymce_linenumber'], 2); ?> /> <?php _e('Line #2', 'vvq'); ?></label><br />
					<?php _e("You may need to clear your browser's cache after changing this value.", 'vvq'); ?>
				</td>
			</tr>
<?php endif; ?>
			<tr valign="top">
				<th scope="row"><?php _e('Hosted Video Files', 'vvq'); ?></th>
				<td>
					<label>
						<input name="usewmp" type="checkbox"<?php checked($this->settings['usewmp'], 'on'); ?> />
						<?php _e("Use Windows Media Player for Windows users. May or may not work for all users.", 'vvq'); ?>
					</label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Prompt for width and height?', 'vvq'); ?></th>
				<td>
					<label>
						<input name="promptforwh" type="checkbox"<?php checked($this->settings['promptforwh'], 'on'); ?> />
						<?php _e('Yes, please ask me every time for the width and height of the video.', 'vvq'); ?>
					</label>
				</td>
			</tr>
		</table>
<?php if ( $this->wpversion < 2.5 ) echo '	</fieldset>'; ?>


	<p class="submit">
		<input type="submit" name="saveplaceholder" value="<?php _e('Save Changes'); ?>" style="display:none" /><!-- This is so that pressing enter in an input doesn't reset to defaults -->
		<input type="submit" name="defaults" value="<?php _e('Reset to Defaults', 'vvq'); ?>" style="float:right" />
		<input type="submit" name="save" value="<?php _e('Save Changes'); ?>" />
	</p>

	</form>
</div>

<?php
	}


	// Stuff in the <head> for normal users
	function wp_head() {
		// Align the video based on the user's preference
		$margins = '5px ';
		if ( 'center' == $this->settings['alignment'] ) $margins .= 'auto 0 auto';
		elseif ( 'right' == $this->settings['alignment'] ) $margins .= '0 0 auto';
		else $margins .= 'auto 0 0';

		?>
	<!-- Viper's Video Quicktags CSS -->
	<style type="text/css">
		.vvqbox { margin: <?php echo $margins; ?>; text-align: center; }

		/* hides the second object from all versions of IE */
		* html object.mov {
			display: none;
		}

		/* displays the second object in all versions of IE apart from 5 on PC */
		* html object.mov/**/ {
			display: inline;
		}

		/* hides the second object from all versions of IE >= 5.5 */
		* html object.mov {
			display/**/: none;
		}
	</style>
<?php

		if ( $this->wpversion < 2.1 ) echo '	<script type="text/javascript" src="' . $this->fullfolderurl . 'vipers-video-quicktags.js?ver=' . $this->version . '"></script>' . "\n";
	}


	// Do the actual regex and replace all BBCode for Flash powered video with HTML
	function replacebbcode($content) {
		$this->jsoutput = ''; // Clear it out

		// Flash based videos
		if ( is_array($this->searchpatterns) && !empty($this->searchpatterns) ) {
			foreach ( $this->searchpatterns as $regex => $params ) {
				preg_match_all($regex, $content, $matches, PREG_SET_ORDER);

				if ( $matches ) {
					// Loop through each result for this regex pattern
					foreach ( $matches as $match) {

						// Save the string that matched the regex
						$matchstring = $match[0];

						// Loop through each of the output data
						$count = 0;
						unset($data);
						foreach ( $params['results'] as $name ) {
							$count++;
							$data[$name] = addslashes($match[$count]);
						}
						unset($data['']); // Remove any blank data

						// If the BBCode didn't have a width or height in it, fill it in with the default value
						if ( !$data['width'] )  $data['width']  = $this->settings[$params['type']]['width'];
						if ( !$data['height'] ) $data['height'] = $this->settings[$params['type']]['height'];

						// Create a unique ID for use as the div ID
						$objectid = uniqid('vvq');

						// Do some stuff for each video type
						switch ( $params['type'] ) {
							case 'youtube':
								$url = $linktext = 'http://www.youtube.com/watch?v=' . $data['videoid'];
								$this->jsoutput .= '	vvq_youtube("' . $objectid . '", "' . $data['width'] . '", "' . $data['height'] . '", "' . $data['videoid'] . '");' . "\n";
								break;

							case 'googlevideo':
								$url = $linktext = 'http://video.google.com/videoplay?docid=' . $data['videoid'];
								$this->jsoutput .= '	vvq_googlevideo("' . $objectid . '", "' . $data['width'] . '", "' . $data['height'] . '", "' . $data['videoid'] . '");' . "\n";
								break;

							case 'stage6':
								$url = 'http://www.stage6.com/';
								$linktext = '<em>Stage6 has shutdown, therefore this video cannot be shown.</em>';
								break;

							case 'ifilm':
								$url = $linktext = 'http://www.ifilm.com/video/' . $data['videoid'];
								$this->jsoutput .= '	vvq_ifilm("' . $objectid . '", "' . $data['width'] . '", "' . $data['height'] . '", "' . $data['videoid'] . '");' . "\n";
								break;

							case 'metacafe':
								$url = $linktext = 'http://www.metacafe.com/watch/' . $data['videoid'] . '/' . $data['videoname'] . '/';
								$this->jsoutput .= '	vvq_metacafe("' . $objectid . '", "' . $data['width'] . '", "' . $data['height'] . '", "' . $data['videoid'] . '", "' . $data['videoname'] . '");' . "\n";
								break;

							case 'myspace':
								$url = $linktext = 'http://vids.myspace.com/index.cfm?fuseaction=vids.individual&videoid=' . $data['videoid'];
								$this->jsoutput .= '	vvq_myspace("' . $objectid . '", "' . $data['width'] . '", "' . $data['height'] . '", "' . $data['videoid'] . '");' . "\n";
								break;

							case 'vimeo':
								$url = $linktext = 'http://www.vimeo.com/' . $data['videoid'];
								$this->jsoutput .= '	vvq_vimeo("' . $objectid . '", "' . $data['width'] . '", "' . $data['height'] . '", "' . $data['videoid'] . '");' . "\n";
								break;

							case 'flv':
								$data['height'] = $data['height'] + 20; // Account for the player controls
								$url = get_bloginfo('wpurl') . $this->folder . '/resources/flvplayer.swf?file=' . urlencode($data['videoid']);
								$linktext = $data['videoid'];
								$previewimage = substr( $data['videoid'], 0, strlen( $data['videoid'] ) - 3 ) . 'jpg';
								$this->jsoutput .= '	vvq_flv("' . $objectid . '", "' . $data['width'] . '", "' . $data['height'] . '", "' . get_bloginfo('wpurl') . $this->folder . '/resources/flvplayer.swf' . '", "' . $data['videoid'] . '", "' . $previewimage . '");' . "\n";
								break;
						}

						do_action( 'vvq_flashprereplace' );

						// Replace the first occurance of the $matchstring with some HTML
						$content = preg_replace('/' . preg_quote($matchstring, '/') . '/', '<div class="vvqbox vvq' . $params['type'] . '" style="width:' . $data['width'] . 'px;height:' . $data['height'] . 'px;"><p id="' . $objectid . '"><a href="' . $url . '">' . $linktext . '</a></p></div>', $content, 1);
					}
				}
			}
		}


		# Process all Quicktime videos
		preg_match_all('#\[quicktime width="(\d+)" height="(\d+)"](.*?)\[\/quicktime]#i', $content, $matches1, PREG_SET_ORDER);
		preg_match_all('#\[quicktime](.*?)\[\/quicktime]#i', $content, $matches2, PREG_SET_ORDER);
		$matches = array_merge($matches1, $matches2); // Merge the two result arrays so we can handle them all at once
		if ( $matches ) {
			foreach ( $matches as $match ) {
				list($matchstring, $width, $height, $url) = $match;

				// Account for the no width/height BBCode
				if ( empty($url) ) {
					$url = $width;
					$width = $this->settings['quicktime']['width'];
					$height = $this->settings['quicktime']['height'];
				}

				// Create a unique ID for use as the div ID
				$objectid = uniqid('vvq');

				$content = str_replace($matchstring, '<div id="' . $objectid . '" class="vvqbox vvqquicktime" style="width:' . $width . 'px;height:' . $height . 'px;"><a href="' . $url . '">' . $url . '</a></div>', $content);

				$this->jsoutput .= '	vvq_quicktime("' . $objectid . '", "' . $width . '", "' . $height . '", "' . $url . '");' . "\n";
			}
		}


		# Process generic video types
		preg_match_all('#\[(video|avi|mpeg|wmv) width="(\d+)" height="(\d+)"](.*?)\[\/(video|avi|mpeg|wmv)]#i', $content, $matches1, PREG_SET_ORDER);
		preg_match_all('#\[(video|avi|mpeg|wmv)](.*?)\[\/(video|avi|mpeg|wmv)]#i', $content, $matches2, PREG_SET_ORDER);
		$matches = array_merge($matches1, $matches2); // Merge the two result arrays so we can handle them all at once
		if ( $matches ) {
			foreach ( $matches as $match ) {
				list($matchstring, , $width, $height, $url) = $match;

				// Account for the no width/height BBCode
				if ( empty($url) ) {
					$url = $width;
					$width = $this->settings['videofile']['width'];
					$height = $this->settings['videofile']['height'];
				}

				// Create a unique ID for use as the div ID
				$objectid = uniqid('vvq');

				// This part needs work, please feel free to suggest any changes
				if ( 'on' == $this->settings['usewmp'] && FALSE !== strpos($_SERVER['HTTP_USER_AGENT'], 'Windows') ) {
					$height = $height + 64; // Compensate for the player controls

					$content = str_replace($matchstring, '<div class="vvqbox vvqvideo" style="width:' . $width . 'px;height:' . $height . 'px;"><p id="' . $objectid . '"><a href="' . $url . '">' . $url . '</a></p></div>', $content);

					$this->jsoutput .= '	vvq_videoWMP("' . $objectid . '", "' . $width . '", "' . $height . '", "' . $url . '");' . "\n";
				} else {
					// MPEG is not listed here as we'll use it as the default
					$mimetypes = array(
						'wmv' => 'video/x-ms-wmv',
						'avi' => 'video/x-msvideo',
						'asf' => 'video/x-ms-asf',
						'asx' => 'video/x-ms-asf',
					);

					// Find out what type of video this is, based on the extension
					$mimetype = $mimetypes[array_pop(explode('.', $url))];
					if ( empty($mimetype) ) $mimetype = 'video/mpeg'; // If we don't know the MIME type, just pick something (MPEG)

					$content = str_replace($matchstring, '<div class="vvqbox vvqvideo" style="width:' . $width . 'px;height:' . $height . 'px;"><p id="' . $objectid . '"><a href="' . $url . '">' . $url . '</a></p></div>', $content);

					$this->jsoutput .= '	vvq_videoNoWMP("' . $objectid . '", "' . $width . '", "' . $height . '", "' . $url . '");' . "\n";
				}
			}
		}

		return $content;
	}


	// Add in the needed Javascript to the end of the post. Do it after wpautop() due to a bug in WP 2.0.x
	function addinlinejs($content) {
		if ( !empty($this->jsoutput) && !is_feed() ) {
			$content .= "\n<script type=\"text/javascript\">\n<!--\n" . $this->jsoutput . "-->\n</script>\n";
		}

		return $content;
	}
}

# This plugin seems to have random, hard to reproduce bugs when used with WordPress 2.5, so what follows is max compatiblity code

global $VipersVideoQuicktags;

$VipersVideoQuicktags = new VipersVideoQuicktags();

global $VipersVideoQuicktags;

// ButtonSnap needs to be loaded outside the class in order to work right
if ( !class_exists('buttonsnap') ) @include_once( ABSPATH . '/wp-content/plugins/vipers-video-quicktags/resources/buttonsnap.php' );

?>