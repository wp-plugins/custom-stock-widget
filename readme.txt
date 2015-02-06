=== Custom Stock Widget ===
Contributors: Relevad
Tags: stock table, stocks, quotes, stock market, stock price, share prices, market changes, trading, finance, financial, stock widget
Requires at least: 3.8.0
Tested up to: 4.1
Stable tag: 1.3.5b
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The Custom Stock Widget plugin allows you to place stock widgets onto your site using shortcodes.


== Description ==

The Custom Stock Widget plugin creates customizable stock widgets that can be placed anywhere on a site using shortcodes. You can choose from multiple themes and customize the colors, size, text, and symbols displayed. 

Features:

 * Choice of stocks
 * Pre-built skins/themes
 * Appearance customizations: width, height, background colors, number of stocks displayed at one time, text color, font size, and font family
 * Display features: vertical lines, horizontal lines, different colors to display changes in price
 * CSS input for entire widget (allows for alignment, borders, margins, padding, etc.)
 * Custom stocks for specific categories
 * Preview of custom stock widget after saving on settings page

Requirements:

 * PHP version >= 5.3.0 (experimental support for lower versions)
 * Jquery version 1.6 or higher (wordpress 4.1 ships with 1.11.1)
 * Ability to execute wordpress shortcodes in the location(s) you want to place stocks. (see installation)

This plugin was developed by Relevad Corporation. Authors: Artem Skorokhodov, Matthew Hively, and Boris Kletser.

== Installation ==

1. Upload the 'custom-stock-widget' folder to the '/wp-content/plugins/' directory

1. Activate the Custom Stock Widget plugin through the Plugins menu in WordPress

1. Configure appearance and Stocks symbols in "Relevad Plugins"->StockWidget

1. Place Shortcodes
 * Pages / Posts: 
  Add the shortcode `[stock-widget]` to where you want the widget shown in your post/page.
 * Themes: 
  Add the PHP code `<?php echo do_shortcode('[stock-widget]'); ?>` where you want the widget to show up.
 * Widgets: 
  Add `[stock-widget]` inside a Shortcode Widget or add `<?php echo do_shortcode('[stock-widget]'); ?>` inside a PHP Code Widget
 
  There are many plugins that enable shortcode or PHP in widgets. 
  Here are two great ones: [Shortcode Widget](http://wordpress.org/plugins/shortcode-widget/) and [PHP Code Widget](http://wordpress.org/plugins/php-code-widget/)


== Frequently Asked Questions ==

= Can I get data for any company? =
The current version of the plugin supports almost any stock on NASDAQ or NYSE.

= How do I add stocks to the stock table? =

All stocks can be added in the Stock Widget settings page (Settings -> StockWidget). 

Go to Settings -> StockWidget
Type in your stock list separated by commas in the Stocks input box.

= How do I place the shortcode into a widget? =
You need a plugin that enables shortcode or PHP widgets.

There are plenty of such plugins on the WordPress.org. 
These worked well for us: [Shortcode Widget](http://wordpress.org/plugins/shortcode-widget/), [PHP Code Widget](http://wordpress.org/plugins/php-code-widget/)

Install and activate the such your desired shortcode or PHP widget plugin and add it to the desired sidebar/section (Appearance->Widgets)

If you added a shortcode widget, type in `[stock-widget]` inside it.

If you added a PHP widget, type in `<?php echo do_shortcode('[stock-widget]'); ?>` inside it.

That will display the widget in the appropriate space.

= What is the Customize Categories section all about? =

If you want to display a different set of stocks for specific categories on your page, you can specify them for each category. If you leave a field next to a category blank, the default list will be loaded.

= Can I place two widgets with different formatting on one page? =

----Depricated support-----
Yes, however if you want to place stock tables with different formatting on a single page, you must give each stock table its own ID in the shortcode.

For example: `[stock-widget id="example_id_01" display="4" width="300" height="200" background_color1="#133735" background_color2="grey" text_color="yellow"]`


= The widget is too big! Is there some way to shrink it? =
Yes. Put in a smaller number in the width/height in Stock Widget Settings (Settings->StockWidget). Both are in pixels. 



= Something's not working or I found a bug. What do I do? =

Email us at stock-widget AT relevad DOT com or go to the support section of this plugin on wordpress.org.


== Screenshots ==

1. Example of the custom stock widget on live site

2. Another example of the custom stock widget on live site

3. More examples of stock table widget themes

4. This is what the back-end looks like

5. Here's how to place the Custom Stock Widget on the site using a PHP Code Widget

6. Here's how to place the Custom Stock Widget inside a page using shortcode


== Changelog ==

= 1.3.5b =

* Bugfix with UI

= 1.3.5a =

* Revised class names to reduce css conflicts

= 1.3.5 =

* Aded persistent UI state on admin panel

= 1.3.4 =

* Changed several HTML input fields in the admin pannel to different types if the browser supports them

= 1.3.3 =

* minor UI bugfix for 1.3.2

= 1.3.2 =

* Added plugin database revision support

= 1.3.1 =

* Added timeout to retreive stock data
* Code reorganization

= 1.3 = 

* Increased stocks in widget up to 100 per widget
* Added toggleable header fields for widgets
* Added alternative csv parser for php < 5.3
* Depricated shortcode parameters
* Added warnings if text sizes + width would lead to overlap
* Cleaned up data storage for plugin options
* Misc minor code cleanups

= 1.1 =

* Code clean up and optimization
* Numerous minor bug fixes

= 1.0 =
Plugin released.

== Upgrade Notice ==

= 1.3 =

Upgrade to get the latest fixes and feature improvements
