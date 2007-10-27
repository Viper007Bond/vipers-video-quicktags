<?php /*

Plugin Name: Viper's Video Quicktags
Plugin URI: http://www.viper007bond.com/wordpress-plugins/vipers-video-quicktags/
Version: 4.0.0
Description: Allows you to embed various video types, including those hosted at <a href="http://www.youtube.com/">YouTube</a> and <a href="http://video.google.com/">Google Video</a> as well as videos you host yourself, into WordPress. <strong>Credits:</strong> <a href="http://asymptomatic.net">Owen Winkler</a> for <a href="http://redalt.com/wiki/ButtonSnap">ButtonSnap</a> and <a href="http://an-archos.com/">An-archos</a> for help with WP 2.1+ & WPMU RTE button code
Author: Viper007Bond
Author URI: http://www.viper007bond.com/

*/

# Nothing to see here! Please use the plugin's options page.

class VipersVideoQuicktags {
	var $version = '4.0.0';
	var $folder = 'wp-content/plugins/vipers_videoquicktags/'; // You shouldn't need to change this ;)
	var $fullfolderurl;

	var $settings = array();
	var $defaultsettings = array();

	// Initialization stuff
	function VipersVideoQuicktags() {
		$this->fullfolderurl = get_bloginfo('wpurl') . '/' . $this->folder;

		$this->defaultsettings = array(
			'youtube' => array(
				'button'         => 'on',
				'width'          => '425',
				'height'         => '335',
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
			'quicktime' => array(
				'button'         => 'on',
			),
			'videofile' => array(
				'button'         => 'on',
			),
			'flv' => array(
				'button'         => NULL,
			),
			'tinymce_linenumber' => 1,
			'usewmp'             => 'on',
		);

		$this->settings = get_option('vvq_options');
		if ( !is_array($this->settings['youtube']) ) {
			$this->settings = $this->defaultsettings;
		}

		// Load up the localization file if we're using WordPress in a different language
		// Place it in the "localization" folder and name it "vvq-[value in wp-config].mo"
		// A link to some localization files is location at the homepage of this plugin
		load_plugin_textdomain('vvq', $this->folder . 'localization');

		// No sense in running the addbuttons() function if no buttons are to be displayed
		if ( TRUE === $this->anybuttons() ) add_action('init', array(&$this, 'addbuttons'));

		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_action('admin_head', array(&$this, 'admin_head'));
		add_action('edit_form_advanced', array(&$this, 'edit_form'));
		add_action('edit_page_form', array(&$this, 'edit_form'));
		add_action('wp_head', array(&$this, 'wp_head'));

		add_filter('the_content', array(&$this, 'searchreplace'), 1);
	}


	// Checks to see if any buttons at all are to be displayed
	function anybuttons() {
		if ('on' == $this->settings['youtube']['button'] ||
			'on' == $this->settings['googlevideo']['button'] ||
			'on' == $this->settings['ifilm']['button'] ||
			'on' == $this->settings['metacafe']['button'] ||
			'on' == $this->settings['myspace']['button'] ||
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
		global $wp_db_version;

		// Don't bother doing this stuff if the current user lacks permissions
		if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) return;

		// If WordPress 2.1+ (or WPMU?) and using TinyMCE, we need to insert the buttons differently
		if ( 3664 <= $wp_db_version && 'true' == get_user_option('rich_editing') ) {
			// Load and append TinyMCE external plugins
			add_filter('mce_plugins', array(&$this, 'mce_plugins'));
			if ( 1 >= $this->settings['tinymce_linenumber'] ) {
				add_filter('mce_buttons', array(&$this, 'mce_buttons'));
			} else {
				add_filter('mce_buttons_' . $this->settings['tinymce_linenumber'], array(&$this, 'mce_buttons'));
			}
			add_action('tinymce_before_init', array(&$this, 'tinymce_before_init'));
			add_action('admin_head', array(&$this, 'buttonhider'));
		} else {
			buttonsnap_separator();
			if ( 'on' == $this->settings['youtube']['button'] )
				buttonsnap_jsbutton($this->fullfolderurl . 'images/youtube.png', __('YouTube', 'vvq'), "VVQInsertVideoSite('" . __('YouTube', 'vvq') . "', 'http://www.youtube.com/watch?v=JzqumbhfxRo', 'youtube');");
			if ( 'on' == $this->settings['googlevideo']['button'] )
				buttonsnap_jsbutton($this->fullfolderurl . 'images/googlevideo.png', __('GVideo', 'vvq'), "VVQInsertVideoSite('" . __('Google Video', 'vvq') . "', 'http://video.google.com/videoplay?docid=3688185030664621355', 'googlevideo');");
			if ( 'on' == $this->settings['ifilm']['button'] )
				buttonsnap_jsbutton($this->fullfolderurl . 'images/ifilm.png', __('IFILM', 'vvq'), "VVQInsertVideoSite('" . __('IFILM', 'vvq') . "', 'http://www.ifilm.com/video/2710582', 'ifilm');");
			if ( 'on' == $this->settings['metacafe']['button'] )
				buttonsnap_jsbutton($this->fullfolderurl . 'images/metacafe.png', __('Metacafe', 'vvq'), "VVQInsertVideoSite('" . __('Metacafe', 'vvq') . "', 'http://www.metacafe.com/watch/299980/italian_police_lamborghini/', 'metacafe');");
			if ( 'on' == $this->settings['myspace']['button'] )
				buttonsnap_jsbutton($this->fullfolderurl . 'images/myspace.png', __('MySpace', 'vvq'), "VVQInsertVideoSite('" . __('MySpace', 'vvq') . "', 'http://vids.myspace.com/index.cfm?fuseaction=vids.individual&videoid=1387215221', 'myspace');");
			if ( 'on' == $this->settings['quicktime']['button'] )
				buttonsnap_jsbutton($this->fullfolderurl . 'images/quicktime.png', __('QT', 'vvq'), "VVQInsertVideoFile('" . __('Quicktime', 'vvq') . "', 'mov', 'quicktime');");
			if ( 'on' == $this->settings['videofile']['button'] )
				buttonsnap_jsbutton($this->fullfolderurl . 'images/videofile.png', __('Video File', 'vvq'), 'VVQInsertVideoFile();');
			if ( 'on' == $this->settings['flv']['button'] )
				buttonsnap_jsbutton($this->fullfolderurl . 'images/flv.png', __('FLV', 'vvq'), "VVQInsertVideoFile('" . __('FLV', 'vvq') . "', 'flv', 'flv');");
		}
	}


	// Add buttons in WordPress v2.1+, thanks to An-archos
	function mce_plugins($plugins) {
		array_push($plugins, '-vipersvideoquicktags');
		return $plugins;
	}
	function mce_buttons($buttons) {
		if ( 1 == $this->settings['tinymce_linenumber'] ) array_push($buttons, 'separator');

		array_push($buttons, 'vipersvideoquicktags');
		return $buttons;
	}
	function tinymce_before_init()
	{
		echo "tinyMCE.loadPlugin('vipersvideoquicktags', '" . $this->fullfolderurl . "resources/tinymce/');\n"; 
	}


	// Hide buttons the user doesn't want to see in WP v2.1+
	function buttonhider() {
		echo "<style type='text/css'>\n";

		if ( 'on' != $this->settings['youtube']['button'] )     echo "	#mce_editor_0_vvq_youtube     { display: none; }\n";
		if ( 'on' != $this->settings['googlevideo']['button'] ) echo "	#mce_editor_0_vvq_googlevideo { display: none; }\n";
		if ( 'on' != $this->settings['ifilm']['button'] )       echo "	#mce_editor_0_vvq_ifilm       { display: none; }\n";
		if ( 'on' != $this->settings['metacafe']['button'] )    echo "	#mce_editor_0_vvq_metacafe    { display: none; }\n";
		if ( 'on' != $this->settings['myspace']['button'] )     echo "	#mce_editor_0_vvq_myspace     { display: none; }\n";
		if ( 'on' != $this->settings['quicktime']['button'] )   echo "	#mce_editor_0_vvq_quicktime   { display: none; }\n";
		if ( 'on' != $this->settings['videofile']['button'] )   echo "	#mce_editor_0_vvq_videofile   { display: none; }\n";
		if ( 'on' != $this->settings['flv']['button'] )         echo "	#mce_editor_0_vvq_flv         { display: none; }\n";

		echo "</style>\n";
	}


	function admin_menu() {
		add_options_page(__("Viper's Video Quicktags Configuration", 'vvq'), str_replace(' ', '&nbsp;', __('Video Quicktags', 'vvq')), 'manage_options', basename(__FILE__), array(&$this, 'optionspage'));
	}


	// Handle form submit and add the Javascript file
	function admin_head() {
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
					'quicktime' => array(
						'button'         => $_POST['quicktime']['button'],
					),
					'videofile' => array(
						'button'         => $_POST['videofile']['button'],
					),
					'flv' => array(
						'button'         => $_POST['flv']['button'],
					),
					'tinymce_linenumber' => (int) $_POST['tinymce_linenumber'],
					'usewmp'             => $_POST['usewmp'],
				);
			}
			update_option('vvq_options', $this->settings);
		}
	}

	
	// Outputs the needed Javascript (not in a .js file as it's dynamic)
	function edit_form() { ?>

<!-- Start Viper's Video Quicktags Javascript -->
<script type="text/javascript">
//<![CDATA[
function VVQInsertVideoSite(sitename, example, tag) {
	var VideoID = prompt('<?php echo str_replace(array("'", '%%sitename%%'), array("\'", "' + sitename + '"), __('Please enter the URL that the %%sitename%% video is located at.\n\nExample:', 'vvq')) . " ' + example"; ?>);

	if (VideoID) {
		buttonsnap_settext('[' + tag + ']' + VideoID + '[/' + tag + ']');
	}
}

function VVQInsertVideoFile(nicename, extension, tag) {
	if (!tag)       var tag = 'video';
	if (!extension) var extension = 'avi';

	var URL = prompt('<?php	echo str_replace(array("'", '%%videotype%%'), array("\'", "' + nicename + '"), __('Please enter the FULL URL to the %%videotype%% video file:\n\nExample:', 'vvq')); ?> http://www.yoursite.com/myvideo.' + extension);

	if (URL) {
		var width = prompt('<?php echo str_replace("'", "\'", __('How many pixels WIDE would you like to display this video?\nIf you don\'t know, then 320 and 640 are nice numbers.', 'vvq')); ?>');
		width = Number(width);

		if (width) {
			var goodnumber = Math.round((width/4)*3);
			var height = prompt('<?php
				echo str_replace(array("'", '%%goodwidth%%'), array("\'", "' + goodnumber + '"), __('How many pixels TALL would you like to display this video?\nIf you don\'t know, then %%goodwidth%% works well with', 'vvq')); ?> ' + width + '.', goodnumber);
			height = Number(height);

			if (height) {
				buttonsnap_settext('[' + tag + ' width="' + width + '" height="' + height + '"]' + URL  + '[/' + tag + ']');
			}
		}
	}
}
//]]>
</script>
<!-- End Viper's Video Quicktags Javascript -->

<?php
	} // admin_js()


	// The contents of the options page
	function optionspage() {
		global $wp_db_version;

		if ($_POST['defaults'])
			echo "\n" . '<div id="message" class="updated fade"><p><strong>' . __('Options reset to defaults.', 'vvq') . '</strong></p></div>' . "\n";
		elseif ($_POST)
			echo "\n" . '<div id="message" class="updated fade"><p><strong>' . __('Options saved.') . '</strong></p></div>' . "\n";

	?>

<div class="wrap">
	<h2><?php _e("Viper's Video Quicktags Configuration", 'vvq'); ?></h2>

	<form name="vvq_config" method="post" action="">

	<fieldset class="options">
		<p><?php _e('Please note that even if you hide a button, the BBCode for that video type will still continue to work. The buttons are only there to make your life easier.', 'vvq'); ?></p>

		<table <?php echo ( 3664 <= $wp_db_version ) ? 'class="widefat"' : 'width="100%" cellpadding="3" cellspacing="3"'; ?> style="text-align: center"> 
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
					<td><input name="youtube[width]" type="text" size="10" value="<?php echo $this->settings['youtube']['width']; ?>" /></td>
					<td><input name="youtube[height]" type="text" size="10" value="<?php echo $this->settings['youtube']['height']; ?>" /></td>
				</tr>
				<tr>
					<td><a href="http://video.google.com/"><?php _e('Google Video', 'vvq'); ?></a></td>
					<td><input name="googlevideo[button]" type="checkbox"<?php checked($this->settings['googlevideo']['button'], 'on'); ?> /></td>
					<td><input name="googlevideo[width]" type="text" size="10" value="<?php echo $this->settings['googlevideo']['width']; ?>" /></td>
					<td><input name="googlevideo[height]" type="text" size="10" value="<?php echo $this->settings['googlevideo']['height']; ?>" /></td>
				</tr>
				<tr class="alternate">
					<td><a href="http://www.ifilm.com/"><?php _e('IFILM', 'vvq'); ?></a></td>
					<td><input name="ifilm[button]" type="checkbox"<?php checked($this->settings['ifilm']['button'], 'on'); ?> /></td>
					<td><input name="ifilm[width]" type="text" size="10" value="<?php echo $this->settings['ifilm']['width']; ?>" /></td>
					<td><input name="ifilm[height]" type="text" size="10" value="<?php echo $this->settings['ifilm']['height']; ?>" /></td>
				</tr>
				<tr>
					<td><a href="http://www.metacafe.com/"><?php _e('Metacafe', 'vvq'); ?></a></td>
					<td><input name="metacafe[button]" type="checkbox"<?php checked($this->settings['metacafe']['button'], 'on'); ?> /></td>
					<td><input name="metacafe[width]" type="text" size="10" value="<?php echo $this->settings['metacafe']['width']; ?>" /></td>
					<td><input name="metacafe[height]" type="text" size="10" value="<?php echo $this->settings['metacafe']['height']; ?>" /></td>
				</tr>
				<tr class="alternate">
					<td><a href="http://www.myspace.com/"><?php _e('MySpace', 'vvq'); ?></a></td>
					<td><input name="myspace[button]" type="checkbox"<?php checked($this->settings['myspace']['button'], 'on'); ?> /></td>
					<td><input name="myspace[width]" type="text" size="10" value="<?php echo $this->settings['myspace']['width']; ?>" /></td>
					<td><input name="myspace[height]" type="text" size="10" value="<?php echo $this->settings['myspace']['height']; ?>" /></td>
				</tr>
				<tr>
					<td><?php _e('Quicktime', 'vvq'); ?></td>
					<td><input name="quicktime[button]" type="checkbox"<?php checked($this->settings['quicktime']['button'], 'on'); ?> /></td>
					<td><?php _e('N/A', 'vvq'); ?></td>
					<td><?php _e('N/A', 'vvq'); ?></td>
				</tr>
				<tr class="alternate">
					<td><?php _e('Generic Video File', 'vvq'); ?><br />(currently somewhat buggy)</td>
					<td><input name="videofile[button]" type="checkbox"<?php checked($this->settings['videofile']['button'], 'on'); ?> /></td>
					<td><?php _e('N/A', 'vvq'); ?></td>
					<td><?php _e('N/A', 'vvq'); ?></td>
				</tr>
				<tr>
					<td><?php _e('Flash Video File (FLV)', 'vvq'); ?></td>
					<td><input name="flv[button]" type="checkbox"<?php checked($this->settings['flv']['button'], 'on'); ?> /></td>
					<td><?php _e('N/A', 'vvq'); ?></td>
					<td><?php _e('N/A', 'vvq'); ?></td>
				</tr>
			</tbody>
		</table>
	</fieldset>
	<fieldset class="options">
		<legend><?php _e('Other Options', 'vvq'); ?></legend>

		<p>
			<?php _e('Show the buttons in the WYSIWYG editor on:', 'vvq'); ?>
			<label for="tinymce_linenumber_1">
				<input name="tinymce_linenumber" id="tinymce_linenumber_1" type="radio" value="1"<?php checked($this->settings['tinymce_linenumber'], 1); ?> />
				<?php _e('Line', 'vvq'); ?> 1
			</label>
			<label for="tinymce_linenumber_2">
				<input name="tinymce_linenumber" id="tinymce_linenumber_2" type="radio" value="2"<?php checked($this->settings['tinymce_linenumber'], 2); ?> />
				<?php _e('Line', 'vvq'); ?> 2
			</label>
		</p>
		<p>
			<label for="usewmp">
				<input name="usewmp" type="checkbox" id="usewmp"<?php checked($this->settings['usewmp'], 'on'); ?> />
				<?php _e("Use Windows Media Player for generic video files for Windows users. May or may not work for all users.", 'vvq'); ?>
			</label>
		</p>
	</fieldset>

	<p class="submit">
		<input type="submit" name="saveplaceholder" value="<?php _e('Update Options'); ?> &raquo;" style="display:none" /><!-- This is so that pressing enter in an input doesn't reset to defaults -->
		<input type="submit" name="defaults" value="&laquo; <?php _e('Reset to Defaults', 'vvq'); ?>" style="float:left" />
		<input type="submit" name="save" value="<?php _e('Update Options'); ?> &raquo;" />
	</p>

	</form>
</div>

<?php
	}


	// Add the CSS stylesheet
	function wp_head() {
		echo '	<link rel="stylesheet" href="' . $this->fullfolderurl . 'vipers_videoquicktags.css?version=' . $this->version . '" type="text/css" />' . "\n";
	}


	// Do the actual regex and replace all BBCode with HTML
	function searchreplace($content) {
		// I suck at regex, so I'll gladly take any suggestions for improvement of these ;)
		$searchstrings = array(
			'#\[youtube\]http://(www.youtube|youtube)\.com/watch\?v=([\w-]+)(.*?)\[/youtube\]#i',
			'#\[youtube\]([\w-]+)\[/youtube\]#i',
			'#\[youtube width="(\d+)" height="(\d+)"]http://(www.youtube|youtube)\.com/watch\?v=([\w-]+)(.*?)\[\/youtube]#i',
			'#\[youtube width="(\d+)" height="(\d+)"]([\w-]+)\[\/youtube]#i',

			'#\[googlevideo]http://video\.google\.([A-Za-z.]{2,5})/videoplay\?docid=([\d-]+)(.*?)\[\/googlevideo]#i',
			'#\[googlevideo]([\d-]+)\[\/googlevideo]#i',
			'#\[googlevideo width="(\d+)" height="(\d+)"]http://video\.google\.([A-Za-z.]{2,5})/videoplay\?docid=([\d-]+)(.*?)\[\/googlevideo]#i',
			'#\[googlevideo width="(\d+)" height="(\d+)"]([\d-]+)\[\/googlevideo]#i',

			'#\[ifilm]http://(www.ifilm|ifilm)\.com/video/([\d-]+)(.*?)\[\/ifilm]#i',
			'#\[ifilm]([\d-]+)\[\/ifilm]#i',
			'#\[ifilm width="(\d+)" height="(\d+)"]http://(www.ifilm|ifilm)\.com/video/([\d-]+)(.*?)\[\/ifilm]#i',
			'#\[ifilm width="(\d+)" height="(\d+)"]([\d-]+)\[\/ifilm]#i',

			'#\[metacafe]http://(www.metacafe|metacafe)\.com/watch/([\d-]+)/([a-z_]+)/\[\/metacafe]#i',
			'#\[metacafe width="(\d+)" height="(\d+)"]http://(www.metacafe|metacafe)\.com/watch/([\d-]+)/([a-z_]+)/\[\/metacafe]#i',

			'#\[myspace]http://vids\.myspace\.com/index\.cfm\?fuseaction=vids\.individual(&|&amp;)videoid=(\d+)\[\/myspace]#i',
			'#\[myspace width="(\d+)" height="(\d+)"]http://vids\.myspace\.com/index\.cfm\?fuseaction=vids\.individual(&|&amp;)videoid=(\d+)\[\/myspace]#i',

			'#\[quicktime width="(\d+)" height="(\d+)"](.*?)\[\/quicktime]#i',

			'#\[flv width="(\d+)" height="(\d+)"](.*?)\[\/flv]#i',

			// For v1.x compatibility
			'#<!--youtubevideo--><span style="display: none">([\w-]+)<\/span>\<!--youtubevideoend-->#i',
			'#<!--googlevideovideo--><span style="display: none">([\d-]+)<\/span>\<!--googlevideovideoend-->#i',

			// Generic video files
			'#\[(video|avi|mpeg|wmv) width="(\d+)" height="(\d+)"](.*?)\[\/(video|avi|mpeg|wmv)]#i',
		);

		$blogname = htmlspecialchars(get_bloginfo('name'));
		$blogurl = htmlspecialchars(get_bloginfo('url'));
		$blogwpurl = htmlspecialchars(get_bloginfo('wpurl')) . '/';

		// If we're in a feed, just make links to the video
		if ( is_feed() ) {
			$replacestrings = array(
				'<a href="http://www.youtube.com/watch?v=$2">http://www.youtube.com/watch?v=$2</a>',
				'<a href="http://www.youtube.com/watch?v=$1">http://www.youtube.com/watch?v=$1</a>',
				'<a href="http://www.youtube.com/watch?v=$4">http://www.youtube.com/watch?v=$4</a>',
				'<a href="http://www.youtube.com/watch?v=$3">http://www.youtube.com/watch?v=$3</a>',

				'<a href="http://video.google.com/videoplay?docid=$2">http://video.google.com/videoplay?docid=$2</a>',
				'<a href="http://video.google.com/videoplay?docid=$1">http://video.google.com/videoplay?docid=$1</a>',
				'<a href="http://video.google.com/videoplay?docid=$4">http://video.google.com/videoplay?docid=$4</a>',
				'<a href="http://video.google.com/videoplay?docid=$3">http://video.google.com/videoplay?docid=$3</a>',

				'<a href="http://www.ifilm.com/video/$2">http://www.ifilm.com/video/$2</a>',
				'<a href="http://www.ifilm.com/video/$1">http://www.ifilm.com/video/$1</a>',
				'<a href="http://www.ifilm.com/video/$4">http://www.ifilm.com/video/$4</a>',
				'<a href="http://www.ifilm.com/video/$3">http://www.ifilm.com/video/$3</a>',

				'<a href="http://www.metacafe.com/watch/$2/$3/">http://www.metacafe.com/watch/$2/$3/</a>',
				'<a href="http://www.metacafe.com/watch/$4/$5/">http://www.metacafe.com/watch/$4/$5/</a>',
				
				'<a href="http://vids.myspace.com/index.cfm?fuseaction=vids.individual&amp;videoid=$2">http://vids.myspace.com/index.cfm?fuseaction=vids.individual&amp;videoid=$2</a>',
				'<a href="http://vids.myspace.com/index.cfm?fuseaction=vids.individual&amp;videoid=$4">http://vids.myspace.com/index.cfm?fuseaction=vids.individual&amp;videoid=$4</a>',

				// Quicktime
				'<a href="$3">$3</a>',

				// FLV
				'<a href="' . $blogwpurl . $this->folder . 'resources/flvplayer.swf?file=$3">$3</a>',

				// For v1.x compatibility
				'<a href="http://www.youtube.com/watch?v=$1">http://www.youtube.com/watch?v=$1</a>',
				'<a href="http://video.google.com/videoplay?docid=$1">http://video.google.com/videoplay?docid=$1</a>',

				// Generic video files
				'<a href="$4">$4</a>',
			);
		}

		// Otherwise make normal embed code
		else {
			$beforestring = '<div class="vvqbox vvq' . str_replace('.', '', $this->version) . ' ';
			$afterstring = '</div>';

			$replacestrings = array(
				// YouTube
				$beforestring . 'vvqyoutube"><object width="' . $this->settings['youtube']['width'] . '" height="' . $this->settings['youtube']['height'] . '" type="application/x-shockwave-flash" data="http://www.youtube.com/v/$2"><param name="movie" value="http://www.youtube.com/v/$2" /><param name="wmode" value="transparent" /></object>' . $afterstring,
				$beforestring . 'vvqyoutube"><object width="' . $this->settings['youtube']['width'] . '" height="' . $this->settings['youtube']['height'] . '" type="application/x-shockwave-flash" data="http://www.youtube.com/v/$1"><param name="movie" value="http://www.youtube.com/v/$1" /><param name="wmode" value="transparent" /></object>' . $afterstring,
				$beforestring . 'vvqyoutube"><object width="$1" height="$2" type="application/x-shockwave-flash" data="http://www.youtube.com/v/$4"><param name="movie" value="http://www.youtube.com/v/$4" /><param name="wmode" value="transparent" /></object>' . $afterstring,
				$beforestring . 'vvqyoutube"><object width="$1" height="$2" type="application/x-shockwave-flash" data="http://www.youtube.com/v/$3"><param name="movie" value="http://www.youtube.com/v/$3" /><param name="wmode" value="transparent" /></object>' . $afterstring,

				// Google Video
				$beforestring . 'vvqgvideo"><object width="' . $this->settings['googlevideo']['width'] . '" height="' . $this->settings['googlevideo']['height'] . '" type="application/x-shockwave-flash" data="http://video.google.com/googleplayer.swf?docId=$2"><param name="wmode" value="transparent" /></object>' . $afterstring,
				$beforestring . 'vvqgvideo"><object width="' . $this->settings['googlevideo']['width'] . '" height="' . $this->settings['googlevideo']['height'] . '" type="application/x-shockwave-flash" data="http://video.google.com/googleplayer.swf?docId=$1"><param name="wmode" value="transparent" /></object>' . $afterstring,
				$beforestring . 'vvqgvideo"><object width="$1" height="$2" type="application/x-shockwave-flash" data="http://video.google.com/googleplayer.swf?docId=$4"><param name="wmode" value="transparent" /></object>' . $afterstring,
				$beforestring . 'vvqgvideo"><object width="$1" height="$2" type="application/x-shockwave-flash" data="http://video.google.com/googleplayer.swf?docId=$3"><param name="wmode" value="transparent" /></object>' . $afterstring,

				// IFILM
				$beforestring . 'vvqifilm"><object width="' . $this->settings['ifilm']['width'] . '" height="' . $this->settings['ifilm']['height'] . '" type="application/x-shockwave-flash" data="http://www.ifilm.com/efp"><param name="flashvars" value="flvbaseclip=$2" /><param name="wmode" value="transparent" /></object>' . $afterstring,
				$beforestring . 'vvqifilm"><object width="' . $this->settings['ifilm']['width'] . '" height="' . $this->settings['ifilm']['height'] . '" type="application/x-shockwave-flash" data="http://www.ifilm.com/efp"><param name="flashvars" value="flvbaseclip=$1" /><param name="wmode" value="transparent" /></object>' . $afterstring,
				$beforestring . 'vvqifilm"><object width="$1" height="$2" type="application/x-shockwave-flash" data="http://www.ifilm.com/efp"><param name="flashvars" value="flvbaseclip=$4" /><param name="wmode" value="transparent" /></object>' . $afterstring,
				$beforestring . 'vvqifilm"><object width="$1" height="$2" type="application/x-shockwave-flash" data="http://www.ifilm.com/efp"><param name="flashvars" value="flvbaseclip=$3" /><param name="wmode" value="transparent" /></object>' . $afterstring,

				// Metacafe
				$beforestring . 'vvqmetacafe"><object width="' . $this->settings['metacafe']['width'] . '" height="' . $this->settings['metacafe']['height'] . '" type="application/x-shockwave-flash" data="http://www.metacafe.com/fplayer/$2/$3.swf"><param name="flashVars" value="playerVars=showStats=yes|autoPlay=no|blogName=' . $blogname . '|blogURL=' . $blogurl . '/" /><param name="wmode" value="transparent" /></object>' . $afterstring,
				$beforestring . 'vvqmetacafe"><object width="$1" height="$2" type="application/x-shockwave-flash" data="http://www.metacafe.com/fplayer/$4/$5.swf"><param name="flashVars" value="playerVars=showStats=yes|autoPlay=no|blogName=' . $blogname . '|blogURL=' . $blogurl . '/" /><param name="wmode" value="transparent" /></object>' . $afterstring,

				// MySpace
				$beforestring . 'vvqmyspace"><object width="' . $this->settings['myspace']['width'] . '" height="' . $this->settings['myspace']['height'] . '" type="application/x-shockwave-flash" data="http://lads.myspace.com/videos/vplayer.swf"><param name="flashvars" value="m=$2&type=video" /><param name="wmode" value="transparent" /></object>' . $afterstring,
				$beforestring . 'vvqmyspace"><object width="$1" height="$2" type="application/x-shockwave-flash" data="http://lads.myspace.com/videos/vplayer.swf"><param name="flashvars" value="m=$4&type=video" /><param name="wmode" value="transparent" /></object>' . $afterstring,

				// Quicktime
				$beforestring . 'vvqquicktime"><object width="$1" height="$2" classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" codebase="http://www.apple.com/qtactivex/qtplugin.cab"><param name="src" value="$3" /><param name="controller" value="true" /><param name="autoplay" value="false" /><param name="wmode" value="transparent" /></object>' . $afterstring,

				// FLV
				$beforestring . 'vvqflv"><object width="$1" height="$2" type="application/x-shockwave-flash" data="' . $blogwpurl . $this->folder . 'resources/flvplayer.swf?file=$3"><param name="movie" value="' . $blogwpurl . $this->folder . 'resources/flvplayer.swf?file=$3" /><param name="wmode" value="transparent" /></object>' . $afterstring,

				// For v1.x compatibility
				$beforestring . 'vvqyoutube"><object width="' . $this->settings['youtube']['width'] . '" height="' . $this->settings['youtube']['height'] . '" type="application/x-shockwave-flash" data="http://www.youtube.com/v/$1"><param name="movie" value="http://www.youtube.com/v/$1" /><param name="wmode" value="transparent" /></object>' . $afterstring,
				$beforestring . 'vvqgvideo"><object width="' . $this->settings['googlevideo']['width'] . '" height="' . $this->settings['googlevideo']['height'] . '" type="application/x-shockwave-flash" data="http://video.google.com/googleplayer.swf?docId=$1"><param name="wmode" value="transparent" /></object>' . $afterstring,
			);

			// Generate the replacement HTML for generic video files
			if ( FALSE !== strpos($_SERVER['HTTP_USER_AGENT'], 'Windows') && 'on' == $this->settings['usewmp'] ) {
				$replacestrings[] = $beforestring . 'vvqgeneric"><object width="$2" height="$3" classid="CLSID:6BF52A52-394A-11d3-B153-00C04F79FAA6" standby="Loading Video..." type="application/x-oleobject"><param name="url" value="$4" /><param name="allowchangedisplaysize" value="true" /><param name="autosize" value="true" /><param name="displaysize" value="1" /><param name="showcontrols" value="true" /><param name="showstatusbar" value="true" /><param name="autorewind" value="true" /><param name="autostart" value="false" /><param name="volume" value="100" /></object>' . $afterstring;
			} else {
				$replacestrings[] = $beforestring . 'vvqgeneric"><object width="$2" height="$3" type="video/x-ms-wmv" data="$4"><param name="src" value="$4" /><param name="autostart" value="false" /><param name="controller" value="true" /><param name="wmode" value="transparent" /></object>' . $afterstring;
			}
		}

		return preg_replace($searchstrings, $replacestrings, $content);
	}
}

$VipersVideoQuicktags = new VipersVideoQuicktags();

// ButtonSnap needs to be loaded outside the class in order to work right
require(ABSPATH . $VipersVideoQuicktags->folder . 'resources/buttonsnap.php');

?>