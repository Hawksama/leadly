=== Aurora Heatmap ===
Contributors: R3098
Donate link: https://seous.info/
Tags: analytics,analyze,click,heatmap,Japanese,statistics,ヒートマップ
Requires at least: 4.9
Tested up to: 5.6
Stable tag: 1.4.10
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 5.6

Beautiful like an aurora! A simple WordPress heatmap that can be completed with just a plugin.

== Description ==

Goddess Aurora is said to give light to the user world.
The name "Aurora Heatmap" visualizes user behavior with a beautiful heatmap.
Bringing light to the activation and optimization of your website.

= The most important thing in site management. =

That is, *Is the user satisfied?*

* Where do users see and move through the content?
* Whether the user is not confused?

Aurora Heatmap is the **strongest tool** for visualizing it.

1. Are you guiding users well?
2. Conversion rate
3. Are you missing out on prospects and readers?
4. How is it evaluated by Google?

You will be able to see the points of improvement.

= Plugin features =

***No Coding***
***No Setting***

You just install and activate the plugin.
No troublesome user registration or setup is required.
It works as default in most WordPress environments.
And Aurora Heatmap is **complete with just plugin**.

The free version can check the click heat map of PC and mobile, and can be used on any number of sites.
Even if it is free, there is no limit due to the number of PV and analysis pages.

= Special notes =

If it does not work well when used with a cache plugin, turn off JavaScript-related optimization, or exclude jQuery and Aurora Heatmap measurement script (reporter.js) from optimization.
For more details, please refer to [official site description page](https://market.seous.info/en/aurora-heatmap#oc-1).

Aurora Heatmap can be used with the following cache plugins.

* WP Rocket
* W3 Total Cache
* WP Super Cache

= Usage and support =

More detailed usage and FAQs are provided on the [Aurora Heatmap official site](https://market.seous.info/en/aurora-heatmap).
If you can't find the answer to your question in those documents, use the WordPress.org [support forum](https://wordpress.org/support/plugin/aurora-heatmap/).
The premium version has priority email support.

= About privacy =

This plugin **does not** perform the following operations.

* User tracking
* Send recorded data to external server
* Use of cookies
* Record of personally identifiable data including IP address

= Aurora Heatmap Free version 90 seconds demo =

[youtube https://www.youtube.com/watch?v=3W17Gg_vbHg]

== Installation ==

1. Login to your WordPress admin.
2. Click on the plugins tab.
3. Click Add New and search for “Aurora Heatmap”.
4. Click Install Now, then Activate Plugin.
5. The analysis will start, and after a while you will be able to see the actions of the visitors.

Since 1.2.0, the translation data for both free and premium versions has been unified into GlotPress on WordPress.org.
If you do not want to switch to Japanese notation, please update the translation from "Update WordPress" on the dashboard.

== Frequently asked questions ==

= Is account registration or contract required to use Heatmap?

Unlike many heatmap services, Aurora Heatmap works completely within your WordPress.
No account registration or contract is required.
However, a monthly or annual license agreement is required to use the premium version.

= Is there a limit to the number of pages or PV that can be analyzed? =

There are no restrictions on the paid version or the free version.

= When viewing a mobile heatmap from a PC, the PC page is displayed. =

If you switch using User-Agent instead of responsive design, view the heatmap from your mobile.

= Which should be used for accuracy setting? =

The recommendation is *high accuracy*, which selects the data to be recorded for an accurate heatmap.
If the recording is not enough, try *Standardi*.

Please note the following points when changing the accuracy setting.

1. High accuracy - the previous standard data will be deleted.
2. Standard - from that point, recording of standard data begins.

= How long can I see the heatmap after installing the plugin? =

Depending on the number of visits to the site, data will accumulate in a few hours to two days.

= Is the heat map displayed in real time? =

User behavior is recorded via Ajax communication as often as necessary.
The recorded data will be displayed on the screen after reloading.

= About 3 days have passed, but there seems to be no data. =

Counting may not occur for sites with extremely low access.
Make sure that there is an inflow to the site.
Also, if you are using a cache plugin, the analysis script may not be output on the page, so delete the cache once.

= It is not counted even if you access it yourself. =

In order to ensure the reliability of the analysis data, the data to be acquired with a specific algorithm is selected.
Administrator access is also excluded.

= There should be access to various pages, but only the top page is recorded. =

In case of default permalink ( `?p=123` format ), it will be a parameter format URL.
If the URL parameter of the setting is integration, it will be regarded as the top page, so please set it to individual display.

= It seems that the number of records recorded in the heatmap is smaller than the number of accesses. =

The purpose of heatmaps is not to count absolute numbers, but to understand trends in user behavior.
In order to improve the reliability of data, the data to be recorded is selected with a specific algorithm.
The number of records is also affected by the accuracy setting.

= If the permalink is changed, will the heatmap data be inherited? =

In the current specification, the heatmap data is stored in association with the URL, so if you change the URL by changing the permalink or post slug, it will be treated as a separate page.

= Can I see a heatmap on my smartphone? =

From 1.0.2, it is now possible to check from the dashboard on a mobile device.

= What is a count bar? =

It shows how many data are recorded at which position on the vertical axis.
Should help with behavior analysis.

= Will the recorded data not leak to the outside? =

In both the free version and paid version, there is no transmission of heat map data to external servers.

== Screenshots ==

1. List View
2. Settings
3. Help
4. Add Plugins screen
5. Click heatmap sample

== Changelog ==

= 1.4.11 =

* Fixed 503 error when displaying heatmap with serialize_precision=100 due to old php.ini.

= 1.4.10 =

* Fixed some case where the title and URL were incorrect in the heatmap list.
* Update the Freemius SDK in the premium version.

= 1.4.9 =

* Fixed the heatmap drawing shifting depending on the style of the html and body elements.
* Reduced errors associated with JavaScript combine.
* Update the Freemius SDK in the premium version. Some screens now support Japanese.

= 1.4.8 =

* Reduced errors related to async attribute jQuery.
* Fixed an error associated with JavaScript combining.

= 1.4.7 =

* Add the files that were missing in the free version 1.4.6.

= 1.4.6 =

* Fixed some warnigs about browser detection and improved cache compatibility.
* Fixed the data of unread detection not deleted by bulk deletion or individual deletion.

= 1.4.5 =

* Fixed DB error during install.
* Added setting of Ajax delay time.

= 1.4.4 ( for premium version ) =

* Fixed the unread detection graph not displaying correctly.

= 1.4.3 =

* Fixed unexpected cache in some environments.

= 1.4.2 =

* Fixed syntax error in PHP 5.6.

= 1.4.1 =

* Tested up to WordPress 5.4.

= 1.4.0 =

**New features**

* "Unread detection" has been implemented in the premium version.
* Added setting to report non-singular pages.
* The page that overlays the heatmap display is now a logged-out page.

**Others**

* Support WordPress - General settings - Timezone.
* Improved daily cron process.
* Optimized database and SQL.

= 1.3.4 =

* Fixed a bug that CGI version PHP could not save settings, bulk data deletion, and delete data for each page.
* Fixed a bug where the data migration process from 1.2.x was incorrectly executed after migration.

= 1.3.3 =

* Fixed DB migration from old version not being performed properly.
* Fixed that data was not deleted when the premium version was uninstalled.

= 1.3.2 =

* Fixed an error in the List View that occurred in some environments.

= 1.3.1 =

* Fixed an error that occurred during a new installation.

= 1.3.0 =

**New features**

* "URL Optimization" is now available in the free version.
* "Advanced URL Optimization" has been implemented in the premium version.
* URL parameters for WordPress are always displayed individually.

**Others**

* Improved search in list view.
* Fixed XSS vulnerability in list view title.

= 1.2.5 =

* Fixed an SQL error that occurred during automatic deletion of old data.
* Adjusted the algorithm for judging the reading position. (Premium version only)

= 1.2.4 ( for free version ) =

* Fixed that the legend image file name change in 1.1.0 was not reflected in the free version.

= 1.2.3 =

* Fixed a syntax error that occurred in some JavaScript minify.
* Supported mobile drawing with wp_is_mobile() function when displaying mobile heatmap on PC.
* Other minor bug fixes and improvements.

= 1.2.2 ( for premium version beta release ) =

* Fixed a syntax error that occurred in some JavaScript minify.

= 1.2.1 ( for premium version ) =

* Reduced the output processing of breakaway and attention heatmap.

= 1.2.0 =

* Added number of drawing points to settings for performance control.
* Fixed click heatmap drawing being interrupted under certain conditions.
* Improved list view usabiilty on mobile.

= 1.1.1 =

* Adjust list order.

= 1.1.0 =

* The interface has been redesigned so that all types of heatmaps can be listed. As a result, previous tabs for PC and mobile have been abolished.
* The search function by title and URL was implemented.
* In order to reduce the database capacity, the data to be saved is narrowed down by the accuracy setting.

= 1.0.3 =

* Fixed a 503 error when displaying the breakaway / attention heatmap under certain conditions.

= 1.0.2 =

* Fix side effect when combining JavaScript.

It turns out that in some cases, the combination of the optimization plugin that combines JavaScript and Aurora Heatmap has side effects on other scripts.
We addressed this issue.

= 1.0.1 =

* The heatmap can be viewed from mobile.
* Add description and legend above list.

= 1.0.0 =

* Initial release

== Upgrade Notice ==

* Initial release

