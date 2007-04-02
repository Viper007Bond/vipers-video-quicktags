<?php /*

Plugin Name: Viper's Video Quicktags
Plugin URI: http://www.viper007bond.com/wordpress-plugins/vipers-video-quicktags/
Version: 5.0.0
Description: Allows you to embed various video types, including those hosted at <a href="http://www.youtube.com/">YouTube</a> and <a href="http://video.google.com/">Google Video</a> as well as videos you host yourself, into WordPress. <strong>Credits:</strong> <a href="http://asymptomatic.net">Owen Winkler</a> for <a href="http://redalt.com/wiki/ButtonSnap">ButtonSnap</a> and <a href="http://an-archos.com/">An-archos</a> for help with WP 2.1+ button code.
Author: Viper007Bond
Author URI: http://www.viper007bond.com/

*/

# Nothing to see here! Please use the plugin's options page. You can configure everything there.

class VipersVideoQuicktags {
	var $version = '5.0.0';
	var $folder = '/wp-content/plugins/vipers-video-quicktags'; // You shouldn't need to change this ;)
	var $fullfolderurl;

	var $settings = array();
	var $defaultsettings = array();

	var $twopointoneplus;

	// Initialization stuff
	function VipersVideoQuicktags() {
		global $wp_version;

		$this->fullfolderurl = get_bloginfo('wpurl') . $this->folder . '/';

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
				'button'         => NULL,
			),
			'videofile' => array(
				'button'         => NULL,
			),
			'flv' => array(
				'button'         => NULL,
			),
			'tinymce_linenumber' => 1,
			'usewmp'             => 'on',
			'alignment'          => 'center',
		);

		$this->settings = get_option('vvq_options');
		if ( !is_array($this->settings['youtube']) ) {
			$this->settings = $this->defaultsettings;
		}

		// Set required options added in later versions for people upgrading
		if ( empty($this->settings['alignment']) ) $this->settings['alignment'] = $this->defaultsettings['alignment'];

		// Load up the localization file if we're using WordPress in a different language
		// Place it in the "localization" folder and name it "vvq-[value in wp-config].mo"
		// A link to some localization files is location at the homepage of this plugin
		load_plugin_textdomain('vvq', $this->folder . '/localization');

		// No sense in running the addbuttons() function if no buttons are to be displayed
		if ( TRUE === $this->anybuttons() ) add_action('init', array(&$this, 'addbuttons'));

		// Are we running at least WordPress 2.1?
		$this->twopointoneplus = version_compare($wp_version, '2.1.0', '>=');

		// Loads the needed Javascript file
		if ( !is_admin() && TRUE == $this->twopointoneplus ) wp_enqueue_script('vvq', $this->folder . '/vipers_videoquicktags.js', FALSE, $this->version);

		// And lastly, register our hooks and filter
		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_action('admin_head', array(&$this, 'admin_head'));
		add_action('edit_form_advanced', array(&$this, 'edit_form'));
		add_action('edit_page_form', array(&$this, 'edit_form'));
		add_action('wp_head', array(&$this, 'wp_head'));
		add_filter('the_content', array(&$this, 'replacebbcode'), 1);
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
		// Don't bother doing this stuff if the current user lacks permissions as they'll never see the pages
		if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) return;

		// If WordPress 2.1+ and using TinyMCE, we need to insert the buttons differently
		if ( TRUE == $this->twopointoneplus && 'true' == get_user_option('rich_editing') ) {
			// Load and append our TinyMCE external plugin
			add_filter('mce_plugins', array(&$this, 'mce_plugins'));
			if ( 1 != $this->settings['tinymce_linenumber'] ) {
				add_filter('mce_buttons_' . $this->settings['tinymce_linenumber'], array(&$this, 'mce_buttons'));
			} else {
				add_filter('mce_buttons', array(&$this, 'mce_buttons'));
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
	function tinymce_before_init() {
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
		// The spaces to &nbsp; is done so that the menu tab doesn't ever end up on two lines
		add_options_page(__("Viper's Video Quicktags Configuration", 'vvq'), str_replace(' ', '&nbsp;', __('Video Quicktags', 'vvq')), 'manage_options', basename(__FILE__), array(&$this, 'optionspage'));
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
					'alignment'          => $_POST['alignment'],
				);
			}
			update_option('vvq_options', $this->settings);
		}
	}

	
	// Outputs the needed Javascript (not in a .js file as it's dynamic and just easier this way)
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
			var goodheight = Math.round((width/4)*3);
			var height = prompt('<?php echo str_replace(array("'", '%%goodheight%%', '%%width%%'), array("\'", "' + goodheight + '", "' + width + '"), __('How many pixels TALL would you like to display this video?\nIf you don\'t know, then %%goodheight%% works well with %%width%%.', 'vvq')); ?>', goodheight);
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
	} // edit_form()


	// The contents of the options page
	function optionspage() {
		global $wp_db_version;

		if ($_POST['defaults'])
			echo "\n" . '<div id="message" class="updated fade"><p><strong>' . __('Options reset to defaults.', 'vvq') . '</strong></p></div>' . "\n";
		elseif ($_POST)
			echo "\n" . '<div id="message" class="updated fade"><p><strong>' . __('Options saved.') . '</strong></p></div>' . "\n";

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
	<h2><?php _e("Viper's Video Quicktags Configuration", 'vvq'); ?></h2>

	<form name="vvq_config" method="post" action="">

	<fieldset class="options">
		<p><?php _e('Please note that even if you hide a button, the BBCode for that video type will still continue to work. The buttons are only there to make your life easier.', 'vvq'); ?></p>

		<table <?php echo ( TRUE == $this->twopointoneplus ) ? 'class="widefat"' : 'width="100%" cellpadding="3" cellspacing="3"'; ?> style="text-align: center"> 
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
					<td><input name="youtube[width]" type="text" size="10" value="<?php echo $this->settings['youtube']['width']; ?>" onchange="updateCustomHeight(this, 'youtubeheight', '<?php echo $this->defaultsettings['youtube']['width']; ?>', '<?php echo $this->defaultsettings['youtube']['height']; ?>')" /></td>
					<td><input name="youtube[height]" id="youtubeheight" type="text" size="10" value="<?php echo $this->settings['youtube']['height']; ?>" /></td>
				</tr>
				<tr>
					<td><a href="http://video.google.com/"><?php _e('Google Video', 'vvq'); ?></a></td>
					<td><input name="googlevideo[button]" type="checkbox"<?php checked($this->settings['googlevideo']['button'], 'on'); ?> /></td>
					<td><input name="googlevideo[width]" type="text" size="10" value="<?php echo $this->settings['googlevideo']['width']; ?>" onchange="updateCustomHeight(this, 'googlevideoheight', '<?php echo $this->defaultsettings['googlevideo']['width']; ?>', '<?php echo $this->defaultsettings['googlevideo']['height']; ?>')" /></td>
					<td><input name="googlevideo[height]" id="googlevideoheight" type="text" size="10" value="<?php echo $this->settings['googlevideo']['height']; ?>" /></td>
				</tr>
				<tr class="alternate">
					<td><a href="http://www.ifilm.com/"><?php _e('IFILM', 'vvq'); ?></a></td>
					<td><input name="ifilm[button]" type="checkbox"<?php checked($this->settings['ifilm']['button'], 'on'); ?> /></td>
					<td><input name="ifilm[width]" type="text" size="10" value="<?php echo $this->settings['ifilm']['width']; ?>" onchange="updateCustomHeight(this, 'ifilmheight', '<?php echo $this->defaultsettings['ifilm']['width']; ?>', '<?php echo $this->defaultsettings['ifilm']['height']; ?>')" /></td>
					<td><input name="ifilm[height]" id="ifilmheight" type="text" size="10" value="<?php echo $this->settings['ifilm']['height']; ?>" /></td>
				</tr>
				<tr>
					<td><a href="http://www.metacafe.com/"><?php _e('Metacafe', 'vvq'); ?></a></td>
					<td><input name="metacafe[button]" type="checkbox"<?php checked($this->settings['metacafe']['button'], 'on'); ?> /></td>
					<td><input name="metacafe[width]" type="text" size="10" value="<?php echo $this->settings['metacafe']['width']; ?>" onchange="updateCustomHeight(this, 'metacafeheight', '<?php echo $this->defaultsettings['metacafe']['width']; ?>', '<?php echo $this->defaultsettings['metacafe']['height']; ?>')" /></td>
					<td><input name="metacafe[height]" id="metacafeheight" type="text" size="10" value="<?php echo $this->settings['metacafe']['height']; ?>" /></td>
				</tr>
				<tr class="alternate">
					<td><a href="http://www.myspace.com/"><?php _e('MySpace', 'vvq'); ?></a></td>
					<td><input name="myspace[button]" type="checkbox"<?php checked($this->settings['myspace']['button'], 'on'); ?> /></td>
					<td><input name="myspace[width]" type="text" size="10" value="<?php echo $this->settings['myspace']['width']; ?>" onchange="updateCustomHeight(this, 'myspaceheight', '<?php echo $this->defaultsettings['myspace']['width']; ?>', '<?php echo $this->defaultsettings['myspace']['height']; ?>')" /></td>
					<td><input name="myspace[height]" id="myspaceheight" type="text" size="10" value="<?php echo $this->settings['myspace']['height']; ?>" /></td>
				</tr>
				<tr>
					<td><a href="http://www.apple.com/quicktime/"><?php _e('Quicktime', 'vvq'); ?></a></td>
					<td><input name="quicktime[button]" type="checkbox"<?php checked($this->settings['quicktime']['button'], 'on'); ?> /></td>
					<td><?php _e('N/A', 'vvq'); ?></td>
					<td><?php _e('N/A', 'vvq'); ?></td>
				</tr>
				<tr class="alternate">
					<td>
						<?php _e('Generic Video File', 'vvq'); ?><br />
						<small>Implementation of this feature isn't<br />perfect and may need some work</small>
					</td>
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

		<table class="optiontable">
			<tr valign="top">
				<th scope="row"><?php _e('Align videos to the:', 'vvq'); ?></th>
				<td>
					<select name="alignment">
						<option value="left"<?php selected($this->settings['alignment'], 'left'); ?>><?php _e('Left', 'vvq'); ?></option>
						<option value="center"<?php selected($this->settings['alignment'], 'center'); ?>><?php _e('Center', 'vvq'); ?></option>
						<option value="right"<?php selected($this->settings['alignment'], 'right'); ?>><?php _e('Right', 'vvq'); ?></option>
					</select>
					<?php _e('part of the post', 'vvq'); ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<?php _e('Show the buttons in the WYSIWYG editor on:', 'vvq'); ?><br />
					<small style="font-size:11px"><?php _e("(You may need to clear your browser's cache after changing this value)", 'vvq'); ?></small>
				</th>
				<td>
					<label><input name="tinymce_linenumber"  type="radio" value="1"<?php checked($this->settings['tinymce_linenumber'], 1); ?> /> <?php _e('Line #1', 'vvq'); ?></label><br />
					<label><input name="tinymce_linenumber" type="radio" value="2"<?php checked($this->settings['tinymce_linenumber'], 2); ?> /> <?php _e('Line #2', 'vvq'); ?></label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Hosted Video Files:', 'vvq'); ?></th>
				<td>
					<label for="usewmp">
						<input name="usewmp" type="checkbox" id="usewmp"<?php checked($this->settings['usewmp'], 'on'); ?> />
						<?php _e("Use Windows Media Player for Windows users. May or may not work for all users.", 'vvq'); ?>
					</label>
				</td>
			</tr>
		</table>
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


	// Stuff in the <head> for normal users
	function wp_head() {
		// Align the video based on the user's preference
		$margins = '5px ';
		if ( 'center' == $this->settings['alignment'] ) $margins .= 'auto 0 auto';
		elseif ( 'right' == $this->settings['alignment'] ) $margins .= '0 0 auto';
		else $margins .= 'auto 0 0';

		?>
	<!-- Quicktime hacks for Viper's Video Quicktags plugin -->
	<style type="text/css">
		.vvqbox {
			margin: <?php echo $margins; ?>;
		}

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

		if ( FALSE == $this->twopointoneplus ) {
			echo '	<script type="text/javascript" src="' . $this->fullfolderurl . 'vipers_videoquicktags.js?ver=' . $this->version . '"></script>' . "\n";
		}
	}


	// Do the actual regex and replace all BBCode for Flash powered video with HTML
	function replacebbcode($content) {
		# First we handle all Flash based videos
		$jsoutput = ''; // Clear it out and set it

		// This is the regex we use to search and then what order the data will come out in
		// Format is: 'match regex' => array('type' => 'videotype', results => array( ... ))
		// The type is used internally and the results array is the order in which the data will be returned (width, height, url, + anything else you want)
		$searchpatterns = array (
			'#\[youtube\]http://(www.youtube|youtube)\.com/watch\?v=([\w-]+)(.*?)\[/youtube\]#i' => array('type' => 'youtube', 'results' => array('', 'videoid')),
			'#\[youtube\]([\w-]+)\[/youtube\]#i' => array('type' => 'youtube', 'results' => array('videoid')),
			'#\[youtube width="(\d+)" height="(\d+)"]http://(www.youtube|youtube)\.com/watch\?v=([\w-]+)(.*?)\[\/youtube]#i' => array('type' => 'youtube', 'results' => array('width', 'height', '', 'videoid')),
			'#\[youtube width="(\d+)" height="(\d+)"]([\w-]+)\[\/youtube]#i' => array('type' => 'youtube', 'results' => array('width', 'height', 'videoid')),

			'#\[googlevideo]http://video\.google\.([A-Za-z.]{2,5})/videoplay\?docid=([\d-]+)(.*?)\[\/googlevideo]#i' => array('type' => 'googlevideo', 'results' => array('', 'videoid')),
			'#\[googlevideo]([\d-]+)\[\/googlevideo]#i' => array('type' => 'googlevideo', 'results' => array('videoid')),
			'#\[googlevideo width="(\d+)" height="(\d+)"]http://video\.google\.([A-Za-z.]{2,5})/videoplay\?docid=([\d-]+)(.*?)\[\/googlevideo]#i'
						=> array('type' => 'googlevideo', 'results' => array('width', 'height', '', 'videoid')),
			'#\[googlevideo width="(\d+)" height="(\d+)"]([\d-]+)\[\/googlevideo]#i' => array('type' => 'googlevideo', 'results' => array('width', 'height', 'videoid')),

			'#\[ifilm]http://(www.ifilm|ifilm)\.com/video/([\d-]+)(.*?)\[\/ifilm]#i' => array('type' => 'ifilm', 'results' => array('', 'videoid')),
			'#\[ifilm]([\d-]+)\[\/ifilm]#i' => array('type' => 'ifilm', 'results' => array('videoid')),
			'#\[ifilm width="(\d+)" height="(\d+)"]http://(www.ifilm|ifilm)\.com/video/([\d-]+)(.*?)\[\/ifilm]#i' => array('type' => 'ifilm', 'results' => array('width', 'height', '', 'videoid')),
			'#\[ifilm width="(\d+)" height="(\d+)"]([\d-]+)\[\/ifilm]#i' => array('type' => 'ifilm', 'results' => array('width', 'height', 'videoid')),

			'#\[metacafe]http://(www.metacafe|metacafe)\.com/watch/([\d-]+)/([a-z_]+)/\[\/metacafe]#i' => array('type' => 'metacafe', 'results' => array('', 'videoid', 'videoname')),
			'#\[metacafe width="(\d+)" height="(\d+)"]http://(www.metacafe|metacafe)\.com/watch/([\d-]+)/([a-z_]+)/\[\/metacafe]#i'
						=> array('type' => 'metacafe', 'results' => array('width', 'height', '', 'videoid', 'videoname')),

			'#\[myspace]http://vids\.myspace\.com/index\.cfm\?fuseaction=vids\.individual(&|&amp;)videoid=(\d+)\[\/myspace]#i' => array('type' => 'myspace', 'results' => array('', 'videoid')),
			'#\[myspace width="(\d+)" height="(\d+)"]http://vids\.myspace\.com/index\.cfm\?fuseaction=vids\.individual(&|&amp;)videoid=(\d+)\[\/myspace]#i'
						=> array('type' => 'ifilm', 'results' => array('width', 'height', '', 'videoid')),
		);

		// Now we loop through each search item and look for matches. If we find a match, we replace it using the replacement pattern.
		foreach ( $searchpatterns as $regex => $params ) {
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
					if ( !$data['width'] )  $data['width']  = $this->defaultsettings[$params['type']]['width'];
					if ( !$data['height'] ) $data['height'] = $this->defaultsettings[$params['type']]['height'];

					// Create a unique ID for use as the div ID
					$objectid = uniqid('vvq');

					// Do some stuff for each video type
					if ( 'youtube' == $params['type'] ) {
						$url = 'http://www.youtube.com/watch?v=' . $data['videoid'];
						$jsoutput .= '	vvq_youtube("' . $objectid . '", "' . $data['width'] . '", "' . $data['height'] . '", "' . $data['videoid'] . '");' . "\n";
					} elseif ( 'googlevideo' == $params['type'] ) {
						$url = 'http://video.google.com/videoplay?docid=' . $data['videoid'];
						$jsoutput .= '	vvq_googlevideo("' . $objectid . '", "' . $data['width'] . '", "' . $data['height'] . '", "' . $data['videoid'] . '");' . "\n";
					} elseif ( 'ifilm' == $params['type'] ) {
						$url = 'http://www.ifilm.com/video/' . $data['videoid'];
						$jsoutput .= '	vvq_ifilm("' . $objectid . '", "' . $data['width'] . '", "' . $data['height'] . '", "' . $data['videoid'] . '");' . "\n";
					} elseif ( 'metacafe' == $params['type'] ) {
						$url = 'http://www.metacafe.com/watch/' . $data['videoid'] . '/' . $data['videoname'] . '/';
						$jsoutput .= '	vvq_metacafe("' . $objectid . '", "' . $data['width'] . '", "' . $data['height'] . '", "' . $data['videoid'] . '", "' . $data['videoname'] . '");' . "\n";
					} elseif ( 'myspace' == $params['type'] ) {
						$url = 'http://vids.myspace.com/index.cfm?fuseaction=vids.individual&videoid=' . $data['videoid'];
						$jsoutput .= '	vvq_myspace("' . $objectid . '", "' . $data['width'] . '", "' . $data['height'] . '", "' . $data['videoid'] . '");' . "\n";
					}

					// Replace the first occurance of the $matchstring with some HTML
					$content = preg_replace('/' . preg_quote($matchstring, '/') . '/', '<div id="' . $objectid . '" class="vvqbox vvq' . $params['type'] . '" style="width:' . $data['width'] . 'px;height:' . $data['height'] . 'px;"><p><a href="' . $url . '">' . $url . '</a></p></div>', $content, 1);
				}
			}
		}


		# Process all Quicktime videos
		preg_match_all('#\[quicktime width="(\d+)" height="(\d+)"](.*?)\[\/quicktime]#i', $content, $matches, PREG_SET_ORDER);
		if ( $matches ) {
			foreach ( $matches as $match ) {
				list($matchstring, $width, $height, $url) = $match;
				$content = str_replace($matchstring, '<div class="vvqbox vvqquicktime" style="width:' . $width . 'px;height:' . $height . 'px;"><object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" codebase="http://www.apple.com/qtactivex/qtplugin.cab" width="' . $width . '" height="' . $height . '"><param name="src" value="' . $url . '" /><param name="controller" value="true" /><param name="autoplay" value="false" /><param name="wmode" value="transparent" /><object type="video/quicktime" data="' . $url . '" width="' . $width . '" height="' . $height . '" class="mov"><param name="controller" value="true" /><param name="autoplay" value="false" /><param name="wmode" value="transparent" /><p><a href="' . $url . '">' . $url . '</a></p></object></object></div>', $content);
			}
		}


		# Process generic video types
		preg_match_all('#\[(video|avi|mpeg|wmv) width="(\d+)" height="(\d+)"](.*?)\[\/(video|avi|mpeg|wmv)]#i', $content, $matches, PREG_SET_ORDER);
		if ( $matches ) {
			// MPEG is not listed here as we'll use it as the default
			$mimetypes = array(
				'wmv' => 'video/x-ms-wmv',
				'avi' => 'video/x-msvideo',
				'asf' => 'video/x-ms-asf',
				'asx' => 'video/x-ms-asf',
			);

			foreach ( $matches as $match ) {
				list($matchstring, , $width, $height, $url) = $match;

				// Compensate for the player controls
				$height = $height + 64;

				// Find out what type of video this is, based on the extension
				$mimetype = $mimetypes[array_pop(explode('.', $url))];
				if ( empty($mimetype) ) $mimetype = 'video/mpeg'; // If we don't know the MIME type, just pick something (MPEG)

				// This part needs work, please feel free to suggest any changes
				if ( 'on' == $this->settings['usewmp'] && FALSE !== strpos($_SERVER['HTTP_USER_AGENT'], 'Windows') && FALSE === strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') ) {
					$content = str_replace($matchstring, '<div class="vvqbox vvqvideo" style="width:' . $width . 'px;height:' . $height . 'px;"><object classid="CLSID:6BF52A52-394A-11d3-B153-00C04F79FAA6" width="' . $width . '" height="' . $height . '"><param name="url" value="' . $url . '" /><param name="src" value="' . $url . '" /><param name="allowchangedisplaysize" value="true" /><param name="autosize" value="true" /><param name="displaysize" value="1" /><param name="showcontrols" value="true" /><param name="showstatusbar" value="true" /><param name="autorewind" value="true" /><param name="autostart" value="false" /><param name="volume" value="100" /></object></div>', $content);
				} else {
					$content = str_replace($matchstring, '<div class="vvqbox vvqvideo" style="width:' . $width . 'px;height:' . $height . 'px;"><object type="' . $mimetype . '" data="' . $url . '" width="' . $width . '" height="' . $height . '" class="vvqbox vvqvideo"><param name="src" value="' . $url . '" /><param name="allowchangedisplaysize" value="true" /><param name="autosize" value="true" /><param name="displaysize" value="1" /><param name="showcontrols" value="true" /><param name="showstatusbar" value="true" /><param name="autorewind" value="true" /><param name="autostart" value="false" /><param name="volume" value="100" /></object></object></div>', $content);
				}
			}
		}


		# Lastly, we add in the Javascript for the Flash based videos (adding it in last saves a little CPU power on preg_match_all() runs)
		if ( !empty($jsoutput) && !is_feed() ) {
			$content .= "\n<script type=\"text/javascript\">\n<!--\n" . $jsoutput . "-->\n</script>\n";
		}

		return $content;
	}
}

$VipersVideoQuicktags = new VipersVideoQuicktags();

// ButtonSnap needs to be loaded outside the class in order to work right
require(ABSPATH . $VipersVideoQuicktags->folder . '/resources/buttonsnap.php');

?>