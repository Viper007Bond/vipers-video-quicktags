=== Viper's Video Quicktags ===
Contributors: Viper007Bond
Donate link: http://www.viper007bond.com/donate/
Tags: video, quicktags, wysiwyg, tinymce, youtube, google video
Requires at least: 2.0
Tested up to: 2.1.2
Stable tag: trunk

Allows easy and XHTML valid posting of YouTube, Google Video, IFILM, Metacafe, MySpace, FLV, Quicktime, and generic video files into posts.

== Description ==

Tired of copying and pasting the embed HTML from sites like YouTube into posts on your site? Well then this plugin is for you.

Just simply click one of the [new buttons](http://wordpress.org/extend/plugins/vipers-video-quicktags/screenshots/) that this plugin adds to the write screen (rich editor included) and then paste the URL that the video is located at into the prompt box -- easy as that. You can fully configure how the videos are displayed (width, height, alignment on the page) and much more. Best of all, it won't break your page's (X)HTML validation.

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
            | vipers_videoquicktags.js
            | vipers_videoquicktags.php
            - images
                | flv.png
                | googlevideo.png
                | ifilm.png
                | metacafe.png
                | myspace.png
                | quicktime.png
                | videofile.png
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
                        | en_us.js`

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