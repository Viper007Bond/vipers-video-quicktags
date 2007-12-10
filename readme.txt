=== Viper's Video Quicktags ===
Contributors: Viper007Bond
Donate link: http://www.viper007bond.com/donate/
Tags: video, quicktags, wysiwyg, tinymce, youtube, google video, stage6, ifilm, metacafe, myspace, vimeo, quicktime
Requires at least: 2.0
Stable tag: trunk

Allows easy and XHTML valid posting of YouTube, Google Video, Stage6, IFILM, Metacafe, MySpace, FLV, Quicktime, and generic video files into posts.

== Description ==

Tired of copying and pasting the embed HTML from sites like YouTube into posts on your site? Well then this plugin is for you.

Just simply click one of the [new buttons](http://wordpress.org/extend/plugins/vipers-video-quicktags/screenshots/) that this plugin adds to the write screen (rich editor included) and then paste the URL that the video is located at into the prompt box -- easy as that. You can fully configure how the videos are displayed (width, height, alignment on the page) and much more. Best of all, it won't break your page's (X)HTML validation.

Currently supports these video sites:

* [YouTube](http://www.youtube.com/)
* [Google Video](http://video.google.com/)
* [Stage6](http://stage6.divx.com/)
* [IFILM](http://www.ifilm.com/)
* [Metacafe](http://www.metacafe.com/)
* [MySpace](http://www.myspace.com/)
* [Vimeo](http://www.vimeo.com/)

As well as these file types:

* QuickTime (MOV, etc.)
* Generic video files (AVI, MPEG, WMV, etc.)
* Flash Video Files (FLV)

If your favorite video site is not supported, please see [the FAQ](http://wordpress.org/extend/plugins/vipers-video-quicktags/faq/) for details on how to get me to include it.

== Installation ==

###Updgrading From A Previous Version###

To upgrade from a previous version of this plugin, delete the entire folder and files from the previous version of the plugin and then follow the installation instructions below.

###Installing The Plugin###

Extract all files from the ZIP file, making sure to keep the file structure intact, and then upload it to `/wp-content/plugins/`.

This should result in the following file structure:

`- wp-content
    - plugins
        - vipers-video-quicktags
            | readme.txt
            | screenshot-1.png
            | screenshot-2.png
            | screenshot-3.png
            | vipers-video-quicktags.js
            | vipers-video-quicktags.php
            - images
                | flv.png
                | googlevideo.png
                | ifilm.png
                | metacafe.png
                | myspace.png
                | stage6.png
                | quicktime.png
                | videofile.png
                | vimeo.png
                | youtube.png
            - localization
                | get_translations.txt
                | template.po
            - resources
                | buttonsnap.php
                | flvplayer.swf
                - tinymce
                    | editor_plugin.js
                    - langs
                        | en.js`

Then just visit your admin area and activate the plugin.

**See Also:** ["Installing Plugins" article on the WP Codex](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins)

###Plugin Configuration###

To configure this plugin, visit it's options page. It can be found under the "Options" tab in your admin area, titled "Video Quicktags".

== Frequently Asked Questions ==

= Where can I get support for this plugin? =

I provide support via [my WordPress plugin forums](http://www.viper007bond.com/wordpress-plugins/forums/viewforum.php?id=23), although just because you post a request for help doesn't mean I will have time to answer your question. This is a free plugin and as such, you aren't guaranteed support. Then again, I'll help as much as I can and have time for.

= The playback of generic video files is buggy / won't work in my browser / etc. =

Oh I'm not surprised. Little documentation exists on how to properly embed a video into a page while staying XHTML valid and having it work in all browsers. I've done the best that I can, but it may still need some work. Use that part of your plugin at your own risk.

= Why doesn't this plugin support such-and-such site? =

There are few possible reasons for this:

* I may have never heard of the site and simply linking it to me on [my WordPress plugin forums](http://www.viper007bond.com/wordpress-plugins/forums/viewforum.php?id=23) may make me include it in a future release
* The URL at which the video can be viewed has nothing in common with the embed URL (i.e. giving the plugin the URL in your address bar won't help it). If this is the case, you might as well just use the HTML code that they give you as you'd have to dig through it either way.
* I have deemed the site not popular enough to warrant being added to my plugin. I don't wish to bloat my plugin with tiny little sites that only one or two people will use. However, I like supporting as many sites as I can as you can hide sites you don't use via the options page, so this is rarely the case.

= Does this plugin support other languages? =

Yes, it does. Included in the `localization` folder is the translation template you can use to translate the plugin. See the [WordPress Codex](http://codex.wordpress.org/Translating_WordPress) for details. You may also be able to find some translations listed at [this plugin's homepage](http://www.viper007bond.com/wordpress-plugins/vipers-video-quicktags/#translations) if any exist.

= I love your plugin! Can I donate to you? =

Sure! I do this in my free time and I appreciate all donations that I get. It makes me want to continue to update this plugin. You can find more details on [my donate page](http://www.viper007bond.com/donate/).

== Screenshots ==

1. Plugin prompt with plain editor buttons in the background
2. Plugin prompt with rich editor (TinyMCE) buttons in the background
3. Plugin configuration page

== Custom Video Resolutions ==

If you would like to override the width and height values specifed on the options page of this plugin on a video-by-video basis, you can do so via the BBCode. Just use this format to specify the width and height that you would like to use.

`[youtube width="320" height="240"]http://www.youtube.com/watch?v=JzqumbhfxRo[/youtube]`

== Styling The Videos ==

All videos are wrapped in a `<div>` with the CSS class `vvqbox` as well as a class for the type of video it is (such as `vvqyoutube` or `vvqgooglevideo`).

If you'd like to add a border for example, try adding something like this to your theme's stylesheet:

`.vvqbox {
	border: 3px solid red;
	padding: 5px;
}`

== ChangeLog ==

**Version 5.3.1**

* Replace BBCode with the video in the excerpt.

**Version 5.3.0**

* Manjor and multiple Stage6 improvements. Props Randy A. for pointing out that it wasn't working in some cases.
* The regex can now be filtered via `vvq_searchpatterns`. This means plugins can add in new BBCodes without having to edit the plugin. See plugin source for format.
* Other minor improvements.

**Version 5.2.3**

* When a custom width is entered into the prompt, use math to suggest a matching height value.

**Version 5.2.2**

* Support for the `http://www,youtube.com/w/?v=JzqumbhfxRo` URL format for YouTube due to popular request.

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