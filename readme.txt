=== oobar ===
Contributors: bobbingwide
Donate link: http://www.oik-plugins.com/oik/oik-donate/
Tags: test, OO, dynamic API, documentation
Requires at least: 3.7
Tested up to: 4.5-RC1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: oobar
Domain Path: /languages/

== Description ==
A sample Object Oriented plugin used to test oik-shortcodes dynamic API documentation

There are multiple files in this plugin but only some of them are loaded when the plugin is activated

* oobar.php IS loaded
* b.php IS NOT loaded


== Installation ==
1. Upload the contents of the oobar plugin to the `/wp-content/plugins/oobar' directory
1. Don't activate the oobar plugin through the 'Plugins' menu in WordPress
1. Use it to test the oik-shortcode plugin

== Frequently Asked Questions ==
= What is this plugin for? =
It contains some sample code that tests the logic of the oik-shortcodes plugin and theme parser.

= How are the APIs loaded? =

During normal execution we would expect the Reflection Function for the APIs to be created using the methods listed below

tbc


== Screenshots ==
1. None

== Upgrade Notice ==
= 1.0.1 = 
Added some other files that broke oik-shortcodes with PHP 7.0

= 1.0 =
Only required for testing oik-shortcodes

== Changelog ==
= 1.0.1 = 
* Added: Couple of files that broke oik-shortcodes for regression testing of oik-shortcodes: edit-tags.php 
* Added: not-wp-config.php but ... Really not sure about this file. Perhaps it was renamed from wp-config.php
* Changed: Added a note about checking status of TRAC 28727
* Changed: To test oik-shortcodes being run locally
* Fixed: Set correct symantic version
 
= 1.0 =
* Added: New plugin


