=== Viper's Video Quicktags ===
Contributors: Viper007Bond
Donate link: http://www.viper007bond.com/donate/
Tags: video, quicktags, wysiwyg, tinymce, youtube, google video, dailymotion, vimeo, veoh, viddler, metacafe, blip.tv, flickr, ifilm, myspace, flv, quicktime
Requires at least: 2.7.1
Tested up to: 2.8
Stable tag: trunk

Allows easy and XHTML valid posting of videos from various websites such as YouTube, DailyMotion, Vimeo, and more.

== Description ==

Tired of copying and pasting the embed HTML from sites like YouTube? Then this plugin is for you.

Just simply click one of the [new buttons](http://wordpress.org/extend/plugins/vipers-video-quicktags/screenshots/) that this plugin adds to the write screen (rich editor included) and then paste the URL that the video is located at into the prompt box -- easy as that. You can fully configure how the videos are displayed (width, height, colors, alignment on the page) and much more. Your site will even stay (X)HTML valid unlike with the code provided by most video sites.

Currently supports these video sites:

* [YouTube](http://www.youtube.com/) (including playlists)
* [Google Video](http://video.google.com/)
* [DailyMotion](http://www.dailymotion.com/)
* [Vimeo](http://www.vimeo.com/)
* [Veoh](http://www.veoh.com/)
* [Viddler](http://www.viddler.com/)
* [Metacafe](http://www.metacafe.com/)
* [Blip.tv](http://blip.tv/)
* [Flickr](http://www.flickr.com/) videos
* [Spike.com/IFILM](http://www.spike.com/)
* [MySpaceTV](http://vids.myspace.com/)

As well as these file types:

* Flash Video Files (FLV)
* QuickTime (MOV, etc.)
* Generic video files (AVI, MPEG, WMV, etc.)

You can also use the `[flash]` shortcode to Flash-based video from **any** website (see Help section after installing for details).

If your favorite video site is not supported, please see [the FAQ](http://wordpress.org/extend/plugins/vipers-video-quicktags/faq/) for details on how to get me to include it.

== Installation ==

###Updgrading From A Previous Version###

To upgrade from a previous version of this plugin, delete the entire folder and files from the previous version of the plugin and then follow the installation instructions below.

###Installing The Plugin###

Extract all files from the ZIP file, **making sure to keep the file structure intact**, and then upload it to `/wp-content/plugins/`. This should result in multiple subfolders and files.

Then just visit your admin area and activate the plugin.

**See Also:** ["Installing Plugins" article on the WP Codex](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins)

###Installing For [WordPress MU](http://mu.wordpress.org/)###

Install as stated above to `plugins`, but place `vipers-video-quicktags.php` in the `mu-plugins` folder. Just that file, nothing else.

###Plugin Configuration###

To configure this plugin, visit it's settings page. It can be found under the "Settings" tab in your admin area, titled "Video Quicktags".

== Frequently Asked Questions ==

= The videos won't show up. Only a YouTube image or a link to the video does. =

Your theme lacks the `<?php wp_head(); ?>` hook. Please add it.

= I have the plugin running, but I have some questions about how to use it. =

A help section is now included with this plugin. Please visit your admin area -> Settings -> Video Quicktags -> Help.

= Why doesn't this plugin support such-and-such site? =

There are few possible reasons for this:

* I may have never heard of the site and simply linking it to me on [my WordPress plugin forums](http://www.viper007bond.com/wordpress-plugins/forums/viewforum.php?id=23) may make me include it in a future release
* The URL at which the video can be viewed has nothing in common with the embed URL. This means my plugin can't do anything with the URL you give it. Support for fetching the emded URL from the website may be added in a future version though, we'll see.
* I have deemed the site not popular enough to warrant being added to my plugin. I don't wish to bloat my plugin with tiny little sites that only one or two people will use. However, I like supporting as many sites as I can as you can hide sites you don't use via the options page, so this is rarely the case.

= Does this plugin support other languages? =

Yes, it does. Included in the `localization` folder is the translation template you can use to translate the plugin. See the [WordPress Codex](http://codex.wordpress.org/Translating_WordPress) for details. When you're done translating it, please [send me](http://www.viper007bond.com/contact/) the translation file so I can include it with the plugin.

= Where can I get additional support for this plugin? =

I provide support via [my WordPress plugin forums](http://www.viper007bond.com/wordpress-plugins/forums/viewforum.php?id=23), although just because you post a request for help doesn't mean I will have time to answer your question. This is a free plugin and as such, you aren't guaranteed support. Then again, I'll help as much as I can and have time for.

= I love your plugin! Can I donate to you? =

Sure! I do this in my free time and I appreciate all donations that I get. It makes me want to continue to update this plugin. You can find more details on [my donate page](http://www.viper007bond.com/donate/).

== Screenshots ==

1. TinyMCE, the plugin's buttons, and the plugin's dialog window.
2. YouTube configuration page.
2. DailyMotion configuration page with Farbtastic color picker showing.

== ChangeLog ==

**Version 6.1.24**

* **General:** Improvements to avoid object ID collisions.
* **Dailymotion:** Update preview video as old one was removed.

**Version 6.1.23**

* **YouTube:** Add the ability to enable "HD" by default. This does not affect the "HQ" button as I don't know of a way to enable that by default. Also remember that not all videos support HD (few do actually, most only support nothing or HQ).
* **YouTube:** Changed the default preview video to one that supports HD.
* **General:** Remove many bundled jQuery UI libraries, Farbtastic, and other items that are now bundled with WordPress.
* **General:** Code improvements and bugfixes.

**Version 6.1.22**

* **General:** Wrap the default feed placeholder text in paragraph tags (the vast majority of people place videos on their own line).

**Version 6.1.21**

* **General:** Use a predictable ID for the placeholders and videos rather than a randomly generated one.
* **General:** PHP notice fixes.

**Version 6.1.20**

* **Translations:** Added Danish transation thanks to Georg.
* **Translations:** Updated Italian translation thanks to Gianni Diurno.

**Version 6.1.19**

* **Quicktime:** Added "scale=aspect" setting as apparently it's best to have.

**Version 6.1.18**

* **YouTube:** Added support for the URL format used in the YouTube RSS feed: http://youtube.com/?v=XXXXXXXXXX

**Version 6.1.17**

* **YouTube:** Removed all quality related features/options. YouTube now natively supports a high quality toggle in it's embed allowing the user to toggle (if the video supports it). Haven't found a way to make high quality the default yet though.

**Version 6.1.16**

* **YouTube:** Add option to disable the video title and ratings display.
* **Veoh:** Add support for the new URL format.
* **General:** Additional styling updates for WordPress 2.7.

**Version 6.1.15**

* **FLV:** Support (and detect) RTMP streams. Props axelseaa.
* **General:** Tweak the redirect that occurs after saving the settings.

**Version 6.1.14**

* **Google Video:** Show the fullscreen button by default, add option to disable it.

**Version 6.1.13**

* **YouTube:** Remove the new search box by default. Option to enable it is on the settings page.

**Version 6.1.12**

* **General:** Fix a PHP parse error that slipped into 6.1.11. Whoops!

**Version 6.1.11**

* **General:** Don't hijack the `kml_flashembed` shortcode if it's already being processed by other plugin.

**Version 6.1.10**

* **General:** Icon for WordPress 2.7.
* **General:** Translation and notice bugfixes from Laurent Duretz.
* **Translations:** French translation thanks to Laurent Duretz.
* **Translations:** Dutch translation thanks to Sypie.

**Version 6.1.9**

* **YouTube:** Add support for YouTube's new experimental HD-ish video.
* **General:** Don't right-position the PayPal button as it covers up the "Help" tab in WordPress 2.7.

**Version 6.1.8**

* **Metacafe:** Update regex to match new URL format. Props penalty.

**Version 6.1.7**

* **General:** CSS tweak for WordPress 2.7. Probably will need more updating, but I'll wait for 2.7 to be done first.
* **YouTube:** Remove MP4 option from settings page (you can't seek properly with it it seems), plus it's meant for the iPhone.

**Version 6.1.6**

* **YouTube:** Default to low quality videos (what YouTube's standard embed code does). The high quality video "hack" can result in "This video is not available" on certain videos.

**Version 6.1.5**

* **Veoh:** Support for a default image in the `[flv]` shortcode when using a `.mp4` video file.

**Version 6.1.4**

* **Veoh:** Fix broken embeds.

**Version 6.1.3**

* **General:** Actually remove the `wp_head()` check (I failed to do it properly in 6.1.2).
* **General:** Don't show the binary FTP warning for WordPress 2.7 (the bug should be fixed).

**Version 6.1.2**

* **General:** Remove `wp_head()` warning for admins. Doesn't work in themes like K2. Plugin's FAQ should cover this.
* **General:** Add a filter to the shortcode attributes. This means plugins/themes can adjust things like the width automatically.
* **Translations:** Russian translation thanks to [Dennis Bri](http://handynotes.ru/)
* **General:** Properly hide some images in the admin that are there for pre-loading.

**Version 6.1.1**

* **Vimeo:** Fixed embeds. Vimeo apparently doesn't like having `&amp;`s in it's embed URLs, so I've switched to using Flashvars.
* **Viddler:** Decode TinyMCE's `&` to `&amp;` conversions which were breaking the embeds.
* **Flash:** Decode TinyMCE's `&` to `&amp;` conversions which were breaking the embeds.

**Version 6.1.0**

* **YouTube:** Can now choose between high quality FLV and high quality MP4 formats.
* **FLV:** Bundled skins.
* **FLV:** Improvements on how custom colors are set.
* **TinyMCE:** Can now choose what line number to display the buttons on.
* **TinyMCE:** Automatic browser cache breaking when the plugin is (de)activated or the line number is changed.
* **General:** SWFObject calls moved to bottom of posts rather than theme footer.
* **General:** Admin notice warning about automatic plugin upgrade breaking SWF files, etc. (ASCII vs. binary).
* **General:** Ability to set custom feed text via settings page.
* **General:** Image pre-cache URL fix.
* **General:** Settings page improvements for users without Javascript.
* **General:** More translations and translators added to credits page.
* **General:** Redid admin warning message for users without the head hook.
* **Flash:** Aliased "kml_flashembed" shortcode and "movie" parameter now used if it's there. This is to support Anarchy Media Player.
* Other various bug fixes.

**Version 6.0.3**

* Undo formatting applied by `wptexturize()` to the URLs of videos. Props to [nukerojo](http://freddiemercury.com.ar/) for reporting.

**Version 6.0.2**

* Fix Write -> Page (forgot to hook in)
* Remove FLV notice from WPMU.
* Add help item about the red in YouTube (hovering over icons).

**Version 6.0.1**

* Fixed a PHP error.

**Version 6.0.0**

Complete recode literally from scratch (all new code):

* Support for new video sites.
* Settings page greatly expanded.
* Video configuration abilities greatly expanded (colors, etc.)
* YouTube playlists
* And so very, very much more.

**Version 5.4.4**

* Add the Quicktime and generic video buttons back to TinyMCE for users who prefer them over the native TinyMCE embedder.

**Version 5.4.3**

* More code changes to try and fix hard-to-reproduce bugs under WordPress 2.5. Thanks to everyone that helped me debug including [Maciek](http://ibex.pl).

**Version 5.4.2**

* Some code to hopefully fix some seemingly random bugs under WordPress 2.5.
* Other minor code improvements.

**Version 5.4.1**

* Video alignment wasn't working due to the switch to SWFObject. This has been fixed. Props to [zerocrash](http://www.zerocrash.dk/) for the bug report.

**Version 5.4.0**

This is a hotfix version to address WordPress 2.5 plus some bugfixes and such. A minor recode of this plugin is planned to improve it, mainly the video file support.

* Updated to support WordPress 2.5 and it's TinyMCE 3 (required a whole new TinyMCE plugin to be written).
* Switched from UFO to SWFObject for the embedding of Flash video (YouTube, etc.) since UFO is deprecated.
* Update of FLV player SWF file.
* Removed Stage6 due to site shutdown. BBCode usage now displays an error message.

**Version 5.3.1**

* Replace BBCode with the video in the excerpt.

**Version 5.3.0**

* Manjor and multiple Stage6 improvements. Props Randy A. for pointing out that it wasn't working in some cases.
* The regex can now be filtered via `vvq_searchpatterns`. This means plugins can add in new BBCodes without having to edit the plugin. See plugin source for format.
* Other minor improvements.

**Version 5.2.3**

* When a custom width is entered into the prompt, use math to suggest a matching height value.

**Version 5.2.2**

* Support for the `http://www.youtube.com/w/?v=JzqumbhfxRo` URL format for YouTube due to popular request.

**Version 5.2.1**

* Support for new Vimeo URL format (no `/clip:XXX`). Thanks to texasellis.

**Version 5.2.0**

* [Stage6](http://stage6.divx.com/) support.
* Regex fix for Metacafe.

**Version 5.1.6**

* The default height for YouTube videos has changed, so plugin updated to match.

**Version 5.1.5**

* Plugin now parses the code inside text widges, i.e. you can embed videos in your sidebar!

**Version 5.1.4**

* Missed a regex expression for the international YouTube handling, whoops!

**Version 5.1.3**

* YouTube.com regional support (uk.youtube.com, etc.)
* WPMU support hopefully
* Support for v2.x (old!) style placeholders
* Updated FLV player file to latest version
* Another attempt at stopping autoplaying self-hosted videos
* Other minor fixes

**Version 5.1.2**

* Spelling mistake ("video" instead of "vimeo") made the Vimeo button in the rich editor never be hidden. Thanks to [giuseppe](http://www.soveratonews.com/) for [pointing it out](http://www.viper007bond.com/wordpress-plugins/forums/viewtopic.php?id=527).

**Version 5.1.1**

* Buttons weren't working in the rich editor due to a stupid mistake on my part (I forgot to replace some debug code with the correct code). Fixed with thanks to [nhdriver4](http://onovia.com/) for [pointing it out](http://www.viper007bond.com/wordpress-plugins/forums/viewtopic.php?id=526).

**Version 5.1.0**

* Renamed the plugin file and Javascript file to match the plugin's folder name.
* Forgot to code in FLV file BBCode->HTML (whoops!). Thanks to [Jack](http://jackcorto.dyndns.org/) for pointing this out.
* Due to using the wrong variable on my part, you were unable to change the default width and height of videos. Now fixed and the boxes on the options page actually work.
* Quicktime and generic video files are now inserted via Javascript in order to get around the annoying IE click-to-activate thing.
* Default width/heights for non-Flash files can now be set. Plugin will now not prompt you for width/height by default for those. Admin area Javascript completely recoded as a result.
* You can now opt to have the plugin always prompt you for a width and height. Option configuration via the options page.
* Added support for [Vimeo](http://www.vimeo.com/)
* Fixed the layering issue with Flash for things like Lightbox. Thanks to [timjohns](http://timdotnet.net/wiggumdaily/) for pointing out that I forgot to handle this. I can't figure out a way to fix it for non-Flash videos though. :(
* Fixed buttons in TinyMCE in Internet Explorer. Issue was caused by tiny Javascript issue. Man I **HATE** that browser!
* Due to WP 2.0.x being old and crappy, it'd add `<br />`'s inside `<script>` tags. Worked around it by adding the Javascript after `wpautop()` runs.
* Fixed title text for buttons in TinyMCE.
* Updated Flash Video Player (FLV player) to v3.7.
* Other various bug fixes that I can't remember.

**Version 5.0.0**

* Complete recode once again featuring UFO for Flash objects and lots of other stuff. Basically v4.0.0 without all the bugs.

**Version 4.0.0**

* Once again, completely recoded from the ground up. Too many changes to even begin to list.

**Version 3.0.0**

* Completely recoded again. This time added a bunch more buttons and switched to regex replacement rather than the stupid method I was using before (woo hoo!).

**Version 2.0.0**

* Plugin completely recoded in order to make buttons for the WYSIWYG editor.

**Version 1.0.1**

* Transparency mode parameter set on the Flash. This makes it so that other items (such as menus or positioned items) appear in front of the videos rather than behind them.

**Version 1.0.0**

* Inital release.