=== AppMakr Comments Plugin ===
Contributors: mdedev
Tags: appmakr
Requires at least: 2.8
Tested up to: 3.0.1
Stable tag: 0.0.2

Makes it easier to integrate comments from AppMakr into your WordPress site.

== Description ==

This plugin was created so people creating iPhone apps through AppMakr could easily integrate the comments functionality with their WordPress installs.

The admin functionality allows you to do the following things:

* Specify the author
* Specify the max width and height for images

If there are any improvements or modifications you would like to see in the plugin please feel free to contact me at (mike AT mde DASH dev.com) and 
I will see if I can get them into the plugin for you.  

Please note for images to work we do modify the allowed tags during a request to allow the img tag and break tag.  We then remove them right after the 
request.  

== Installation ==

1. Upload the `appmakr-comment` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. To use this plugin with AppMakr in the message tab url field use the format http://<YOUR_BLOG_URL>/?comment_post_ID=<BLOG_OR_PAGE_ID> 

== Frequently Asked Questions ==

= Why can't this plugin do X? =

Good question, maybe I didn't think about having this feature or didn't feel anyone would use it.  Contact me at mike AT mde DASH dev.com and 
I will see if I can get it added for you.  

== Changelog ==

= 0.0.2 = 
* Added in a check to see if the server referrer is set and if not add one in to get around template spam functions
* Added in the ability to turn on debugging emails from the settings area

= 0.0.1 =
* Initial release
