Plugin Name	: Email Subscribers Pro
Plugin URI	: https://www.icegram.com/
Author		: Icegram
Author URI	: https://www.icegram.com/
Requires at least: 3.9
Tested up to: 5.5.3
Requires PHP: 5.6
Stable tag: 4.6.4

************************************************************Version 4.6.4************************************************************

* New: Added {{POSTMORETAG}} keyword for Post Notification
* New: New workflow trigger for WooCommerce order creation [PRO]
* New: Added option to select multiple lists while importing subscribers [PRO]
* Fix: Import not working for existing subscribers
* Fix: Duplicate email issue in few edge cases

************************************************************Version 4.6.3************************************************************

* New: Duplicate Broadcasts and Sequences [PRO]
* New: Added IP address of contacts on audience
* New: Show total contacts subscribed from a specific form
* Update: Improve email sending queue

************************************************************Version 4.6.2************************************************************

* Fix: UTM tracking related issue [PRO]

************************************************************Version 4.6.1************************************************************

* New: UI improvements
* Fix: Status change issue after sending broadcast

************************************************************Version 4.6.0************************************************************

* New: Added unconfirmed contacts KPI on audience page
* New: Integrate email delivery check system
* Update: Improved onboarding
* Update: User subscription on selected lists only in double opt-in (instead of all list)
* Update: Added option to remove "Powered By Icegram" link
* Fix: Deactivation feedback popup issue
* Fix: Migration issue from email subscribers 3.1.3
* Fix: Bulk actions issue for large number of list items

************************************************************Version 4.5.6************************************************************

* Update: Improved UI
* Update: Improved securities
* Update: Improved Import contacts functionality
* Update: Now, pagination also works with search parameter

************************************************************Version 4.5.5************************************************************

* Update: Improve onboarding
* New: Sync name field value from WP Form [PRO]
* Fix: WP Form workflow related issue [PRO]

************************************************************Version 4.5.4************************************************************

* Update: Compatible with WordPress 5.5
* Fix: SMTP mailer warning with WordPress 5.5
* Fix: Import Contact issue

************************************************************Version 4.5.3************************************************************

* New: Add Reply-To Email Address field for Broadcast
* New: Improve WooCommerce integration by adding more WooCommerce specific workflows [PRO]
* Update: Change Sequence Message status on change of parent status
* Fix: Load PRO email templates when upgrading from Free to PRO

************************************************************Version 4.5.2************************************************************

* New: Added Seqeuence/ Autoresponder Reports
* Fix: Sequence Open/ Click tracking issue
* Fix: Empty Post Digest send issue if no Posts published

************************************************************Version 4.5.1************************************************************

* New: Added Advance Campaign Reports like Country info, Browser Info, Device Info, Mail Client Info [PRO]
* Fix: Post Digest issues [PRO]
* Update: Performance Improvements.

************************************************************Version 4.5.0.1************************************************************

* Fix: Duplicate campaign creation
* Fix: Post Digest Keywords issue [PRO]

************************************************************Version 4.5.0************************************************************

* New: Advance Campaign Reports [PRO]

************************************************************Version 4.4.10.1************************************************************

* Fix: Call to undefined method ES_Install::get_441_schema()

************************************************************Version 4.4.10************************************************************

* New: Added {{POSTCATS}} keyword for Post Notification
* New: Added option to select multiple lists while sending Broadcast, creating Post Notification and Post Digest [PRO]
* New: Added new email templates for Broadcast, Post Notifications & Post Digst [PRO]

************************************************************Version 4.4.9************************************************************

* Update: Redirect to forms list page after creating form
* Update: YouTube video embedding issue
* Update: Action Scheduler Library to 3.1.6
* Fix: {{POSTLINK-ONLY}} keyword issue
* Fix: Post Digest issue [PRO]

************************************************************Version 4.4.8************************************************************

* New: Filter Campaigns by type
* New: Filter Campaigns by status
* New: Added Report link for each campaign
* Update: UI improvements

************************************************************Version 4.4.7************************************************************

* New: Improved Broadcast UI
* New: Now, able to draft broadcast and send later
* New: Added campaign level open/ view tracking
* New: Form level captcha is available (**PRO**)
* New: Added campaign level Link tracking (**PRO**)
* New: Added campaign level UTM tracking (**PRO**)
* Update: Use date formate which set in WordPress
* Update: UI improvements
* Fix: Import/ Export issues

************************************************************Version 4.4.6************************************************************

* New: Integrate [Forminator](https://wordpress.org/plugins/forminator/) form plugin (**Premium**)
* Update: Improved Import/ Export feature
* Update: Improved manage lists UI
* Update: Improved Export contacts UI
* Update: Show date based on format set in WordPress settings
* Fix: Import contact issue
* Fix: "Continu Reading" link doesn't work in Post Notifications
* Fix: Audience lists stick to bottom

************************************************************Version 4.4.5************************************************************

* Update: Improve Import & Export contacts UI
* Fix: Importing contacts progress stays at 0%
* Fix: Test email send acknowledgement was not showing

************************************************************Version 4.4.4************************************************************

* Update: Improve settings screen UI
* Update: Improve Import/ Export contacts

************************************************************Version 4.4.3************************************************************

* Update: Improve Workflows
* Update: Improve UI/ UX
* Update: Compatibility check with WordPress 5.4
* Fix: Fatal Error: Cannot Redeclare ig_es_may_activate_on_blog
* Fix: Email Templates scroll issue

************************************************************Version 4.4.2************************************************************

* Update: Improve Help & Info page
* Update: Improve Active Contacts Growth based on cumulative contacts
* Fix: Migration issue
* Fix: Campaign was sent while previewing campaign
* Fix: Empty campaign body if any error occur
* Fix: Optimize images
* Fix: Campaigns list stick to bottom of the page
* Fix: PHP Warning during installation on multisite

************************************************************Version 4.4.1.1************************************************************

* Update: Show warning message for minimum PHP compatibility version

************************************************************Version 4.4.1************************************************************
* New: Added Email Subscribers Workflows
* Fix: Export contacts issue
* Fix: Subscribe people without list selection

************************************************************Version 4.4.0************************************************************
* Update: Improve dashboard. Added active growth, last 60 days KPI and Campaigns reports (PRO)

************************************************************Version 4.3.13************************************************************
* Update: Added custom style for consent text
* Fix: **{{LIST}}** keyword did not work in the welcome email
************************************************************Version 4.3.12************************************************************
* Update: Added support for HTML in GDPR consent text
* Update: Now, contact will be deleted upon the deletion of WordPress user if WordPress sync is on.
* Update: Removed.SVG images from templates as it's blocked by Gmail
* Fix: Confirmation email did not go out if the subject is empty
* Fix: Email Sending options was not saved properly
* Fix: Link tracking issue

************************************************************Version 4.3.11************************************************************
* New: Now, one can add consent checkbox in subscription form
* Fix: PHP Notices

************************************************************Version 4.3.10************************************************************
* Fix: Invalid email adding issue from Rainmaker

************************************************************Version 4.3.9************************************************************
* Update: Added compatibility with Outlook mailer of WP Mail SMTP plugin. (Thanks to [@kinderkeuken](https://profiles.wordpress.org/kinderkeuken/) for help us debugging)
* Fix: Duplicate email import issue.

************************************************************Version 4.3.8************************************************************
* Fix: Include Javascript issue with localised WordPress
* Fix: Duplicate entries of contacts

************************************************************Version 4.3.7************************************************************
* Update: Improved import contacts functionality. Now, we are able to import ".CSV" file which contains only emails
* Fix: Multiple emails to contacts.

************************************************************Version 4.3.6************************************************************
* New: Fetch only latest posts since last post digest sent time
* Update: Improve on boarding
* Fix: Load new email templates issue
* Fix: Count shows zero (0) even if contacts available in list
* Fix: File with ".CSV" (uppercase) extension was not working with import contacts.
* Fix: Incorrect unsubscribed contacts count

************************************************************Version 4.3.5.1************************************************************
* Update: Improved edit contact
* Fix: Contact are being removed from the list when new one subscribed
* Fix: WordPress contact sync issue

************************************************************Version 4.3.5************************************************************
* New: Added setting to set cron interval
* New: Added setting to set maximum emails to send on every cron request
* Fix: Illegal string offset ‘es_registered’ warning

************************************************************Version 4.3.4.1************************************************************
* Fix: Delete Campaigns Permanently issue
* Fix: Security issues
* Update: Considered HTTP_X_REAL_IP while getting user IP address

************************************************************Version 4.3.4************************************************************
* Update: Delete Campaigns Permanently
* Fix: Import issue
* Fix: Multiple email sending issue

************************************************************Version 4.3.3************************************************************
* Fix: Cron Lock issue
* Fix: Honeypot issue with caching plugin

************************************************************Version 4.3.2************************************************************
* New: Added Black Friday & Cyber Monday email templates
* New: Added basic reporting like total subscribed, unsubscribed, open & click (PRO) in last 60 days
* Update: Disable Server cron on plugin deactivation

************************************************************Version 4.3.1************************************************************
* Fix: Health Check Issue

************************************************************Version 4.3.0************************************************************
* Fix: Link Tracking issue
* Fix: Email sending issue with SMTP

************************************************************Version 4.2.4************************************************************
* New: Added link tracking support in campaigns
* Update: Compatibility with ES Free 4.2.4

************************************************************Version 4.2.3************************************************************
* New: Added Halloween templates

************************************************************Version 4.2.2************************************************************
* Update: Compatibility with ES Free 4.2.3

************************************************************Version 4.2.1************************************************************
* New: Added new Sequence(Autoresponders) campaign type

************************************************************Version 4.2.0************************************************************
* New: Added new Post Digest campaign
* New: Added provision to select list while unsubscribe

************************************************************Version 4.1.7************************************************************
* New: Added user roles permissions for each menu and submenus of Email Subscribers
* New: Added Gravity Forms integration

************************************************************Version 4.1.6************************************************************
* New: Added EDD integration
* Update: WP Forms, Ninja Forms & WP Forms description

************************************************************Version 4.1.5************************************************************
* Fix: Set correct source for ninja form integration

************************************************************Version 4.1.4************************************************************
* New: Scheduler feature added for broadcast
* New: Sync WP forms leads 
* New: Sync Ninja forms leads 
* New: Sync Give doners

************************************************************Version 4.1.3************************************************************
* New: Sync Contact Form 7 users.

************************************************************Version 4.1.2************************************************************
* Fix: Duplicate email sending issue

************************************************************Version 4.1.1************************************************************
* New: Sync WooCommerce users.
* Fix: UTF-8 encoding issue

************************************************************Version 4.1************************************************************
* New: Sync comment users.
* Update: Store Connector SDK update to 0.4.3

************************************************************Version 4.0.5************************************************************
* Fix: Set multiple "to" addresses.

************************************************************Version 4.0.4************************************************************
*Fix: "Invalid Captcha" error even if captcha is disable.

************************************************************Version 4.0.3************************************************************
*New: Launch Starter plan

************************************************************Version 4.0.2************************************************************
*Fix: Bug fix : Captcha settings does not save properly

************************************************************Version 4.0.1************************************************************

*Fix: Update notice related issues

************************************************************Version 4.0.0************************************************************

* New: Compatibility with Email Subscribes 4.0 (https://www.icegram.com/email-subscribers-plugin-redesign/)

************************************************************Version 0.13************************************************************

* Fix: Improve in captcha for security

************************************************************Version 0.12************************************************************

* Fix: "Invalid email" while importing subscribers list
* Update: Now can cleanup you list once in a month

************************************************************Version 0.1************************************************************

First version.

Premium Features for Email Subscribers - Readymade templates, List cleanup, UTM tracking, Spam score, Test send newsletters and many more