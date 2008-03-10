/* Import plugin specific language pack */
tinyMCE.importPluginLanguagePack('vipersvideoquicktags');

var TinyMCE_VipersVideoQuicktags = {
	getInfo : function() {
		return {
			longname : "Viper's Video Quicktags",
			author : "Viper007Bond",
			authorurl : "http://www.viper007bond.com/",
			infourl : "http://www.viper007bond.com/wordpress-plugins/vipers-video-quicktags/",
			version : tinyMCE.majorVersion + "." + tinyMCE.minorVersion
		};
	},

	getControlHTML : function(cn) {
		switch (cn) {
			case 'vipersvideoquicktags':
				buttons =           tinyMCE.getButtonHTML('vvq_youtube', 'lang_vipersvideoquicktags_youtube', '{$pluginurl}/../../images/youtube.png', 'vvq_youtube');
				buttons = buttons + tinyMCE.getButtonHTML('vvq_googlevideo', 'lang_vipersvideoquicktags_googlevideo', '{$pluginurl}/../../images/googlevideo.png', 'vvq_googlevideo');
				buttons = buttons + tinyMCE.getButtonHTML('vvq_ifilm', 'lang_vipersvideoquicktags_ifilm', '{$pluginurl}/../../images/ifilm.png', 'vvq_ifilm');
				buttons = buttons + tinyMCE.getButtonHTML('vvq_metacafe', 'lang_vipersvideoquicktags_metacafe', '{$pluginurl}/../../images/metacafe.png', 'vvq_metacafe');
				buttons = buttons + tinyMCE.getButtonHTML('vvq_myspace', 'lang_vipersvideoquicktags_myspace', '{$pluginurl}/../../images/myspace.png', 'vvq_myspace');
				buttons = buttons + tinyMCE.getButtonHTML('vvq_vimeo', 'lang_vipersvideoquicktags_vimeo', '{$pluginurl}/../../images/vimeo.png', 'vvq_vimeo');
				buttons = buttons + tinyMCE.getButtonHTML('vvq_quicktime', 'lang_vipersvideoquicktags_quicktime', '{$pluginurl}/../../images/quicktime.png', 'vvq_quicktime');
				buttons = buttons + tinyMCE.getButtonHTML('vvq_videofile', 'lang_vipersvideoquicktags_videofile', '{$pluginurl}/../../images/videofile.png', 'vvq_videofile');
				buttons = buttons + tinyMCE.getButtonHTML('vvq_flv', 'lang_vipersvideoquicktags_flv', '{$pluginurl}/../../images/flv.png', 'vvq_flv');
				return buttons;
		}

		return '';
	},

	execCommand : function(editor_id, element, command, user_interface, value) {
		switch (command) {
			case 'vvq_youtube':
				VVQInsertVideoSite('YouTube', 'http://www.youtube.com/watch?v=JzqumbhfxRo', 'youtube');
				return true;
			case 'vvq_googlevideo':
				VVQInsertVideoSite('Google Video', 'http://video.google.com/videoplay?docid=3688185030664621355', 'googlevideo');
				return true;
			case 'vvq_ifilm':
				VVQInsertVideoSite('IFILM', 'http://www.ifilm.com/video/2710582', 'ifilm');
				return true;
			case 'vvq_metacafe':
				VVQInsertVideoSite('Metacafe', 'http://www.metacafe.com/watch/299980/italian_police_lamborghini/', 'metacafe');
				return true;
			case 'vvq_myspace':
				VVQInsertVideoSite('MySpace', 'http://vids.myspace.com/index.cfm?fuseaction=vids.individual&videoid=1387215221', 'myspace');
				return true;
			case 'vvq_vimeo':
				VVQInsertVideoSite('Vimeo', 'http://www.vimeo.com/clip:27810', 'vimeo');
				return true;
			case 'vvq_quicktime':
				VVQInsertVideoFile('Quicktime', 'mov', 'quicktime');
				return true;
			case 'vvq_videofile':
				VVQInsertVideoFile('', 'avi', 'video');
				return true;
			case 'vvq_flv':
				VVQInsertVideoFile('FLV', 'flv', 'flv');
				return true;
		}

		return false;
	}
};

tinyMCE.addPlugin('vipersvideoquicktags', TinyMCE_VipersVideoQuicktags);