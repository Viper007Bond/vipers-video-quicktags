(function() {
	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('vipersvideoquicktags');

	tinymce.create('tinymce.plugins.VipersVideoQuicktags', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			ed.addButton('vvqYouTube', {
				title : 'vipersvideoquicktags.youtube',
				image : url + '/../../images/youtube.png',
				onclick : function() {
					VVQInsertVideoSite('YouTube', 'http://www.youtube.com/watch?v=JzqumbhfxRo', 'youtube');
				}
			});
			ed.addButton('vvqGoogleVideo', {
				title : 'vipersvideoquicktags.googlevideo',
				image : url + '/../../images/googlevideo.png',
				onclick : function() {
					VVQInsertVideoSite('Google Video', 'http://video.google.com/videoplay?docid=3688185030664621355', 'googlevideo');
				}
			});
			ed.addButton('vvqIFILM', {
				title : 'vipersvideoquicktags.ifilm',
				image : url + '/../../images/ifilm.png',
				onclick : function() {
					VVQInsertVideoSite('IFILM', 'http://www.ifilm.com/video/2710582', 'ifilm');
				}
			});
			ed.addButton('vvqMetaCafe', {
				title : 'vipersvideoquicktags.metacafe',
				image : url + '/../../images/metacafe.png',
				onclick : function() {
					VVQInsertVideoSite('Metacafe', 'http://www.metacafe.com/watch/299980/italian_police_lamborghini/', 'metacafe');
				}
			});
			ed.addButton('vvqMySpace', {
				title : 'vipersvideoquicktags.myspace',
				image : url + '/../../images/myspace.png',
				onclick : function() {
					VVQInsertVideoSite('MySpace', 'http://vids.myspace.com/index.cfm?fuseaction=vids.individual&videoid=1387215221', 'myspace');
				}
			});
			ed.addButton('vvqVimeo', {
				title : 'vipersvideoquicktags.vimeo',
				image : url + '/../../images/vimeo.png',
				onclick : function() {
					VVQInsertVideoSite('Vimeo', 'http://www.vimeo.com/clip:27810', 'vimeo');
				}
			});
			ed.addButton('vvqQuicktime', {
				title : 'vipersvideoquicktags.quicktime',
				image : url + '/../../images/quicktime.png',
				onclick : function() {
					VVQInsertVideoFile('Quicktime', 'mov', 'quicktime');
				}
			});
			ed.addButton('vvqVideoFile', {
				title : 'vipersvideoquicktags.videofile',
				image : url + '/../../images/videofile.png',
				onclick : function() {
					VVQInsertVideoFile('', 'avi', 'video');
				}
			});
			ed.addButton('vvqFLV', {
				title : 'vipersvideoquicktags.flv',
				image : url + '/../../images/flv.png',
				onclick : function() {
					VVQInsertVideoFile('FLV', 'flv', 'flv');
				}
			});
		},

		/**
		 * Creates control instances based in the incomming name. This method is normally not
		 * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
		 * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
		 * method can be used to create those.
		 *
		 * @param {String} n Name of the control to create.
		 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
		 * @return {tinymce.ui.Control} New control instance or null if no control was created.
		 */
		createControl : function(n, cm) {
			return null;
		},

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : "Viper's Video Quicktags",
				author : 'Viper007Bond',
				authorurl : 'http://www.viper007bond.com/',
				infourl : 'http://www.viper007bond.com/wordpress-plugins/vipers-video-quicktags/',
				version : "5.4.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('vipersvideoquicktags', tinymce.plugins.VipersVideoQuicktags);
})();