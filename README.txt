=== Activities ===
Contributors: loderian
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=4WWGL363GNRGQ&lc=NO&item_name=Activities%20WordPress%20Plugin&item_number=Development&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: activities, events, courses, classes, report
Requires at least: 4.6
Tested up to: 5.0.3
Stable tag: trunk
Requires PHP: 7.0.32
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0-standalone.html

A plugin for managing activities, activity reports and communication with participants. Comes with WooCommerce integration.

== Description ==

Activities is a free, light-weight plugin for managing activities.
It provides an easy to use interface to manage your sites activities and who is participating.
This plugin supports multisite installations. Each site has their own activities, locations, activity archive and plugin settings.

The heart of the plugin is the Activity Report.
When using the Activity Report it is easy to manage different recurring activities like yoga courses, football training and a lot of similar group oriented activities.
The plugin integrates beautifully with WooCommerce Products.
For example, when a customer is buying participation on a yoga retreat the customer are at the same time assigned the Activity list for this particular retreat.

### Activities ###

Activities has name, start/end dates and two description of different sizes.
They can also be assigned a location, they are created separately for easy reuse.
If you have plans for the activity, they can be created separately and be reused on unlimited activities. The plans can be viewed on the activity report page.
In addition each activity can have a responsible user who can be granted access to view and/or edit their assigned activities.
Activities can be archived to get a 'history' and reduce the amount of activities on the 'main' list.

### Activity Report ###

This plugin comes with a report page where you can get an overview of the activity and its participants.
The report is a static structure, but has a lot of customization options for its content.
In addition to specific report settings, the 'Activities > Options > Activity Report' page you can set a generic setting for all reports.

### Export and Participant Communication ###

The export page allows you to copy participant information from an activity. It currently supports exporting email, phone and names.
For example it can be used to send emails to all participants by copying the email list it provides and pasting it into a email program or webpage.

### Shortcodes ###

A simple shortcode is provided to display activity information in blog-posts, products or other types of posts.
Also comes with an option to display a join/unjoin button on posts. Check the FAQ on how to use it.

### Responsible Users ###

Usually users would be granted permission to use a plugin based on their role.
With this plugin you can grant users permission to only view and/or edit their assigned activities.
This will naturally not restrict access for other users who have higher permissions based on role.

### WooCommerce ###

Activities has a good integration with WooCommerce. Products can be assigned with any number of activities,
when a user buys the product and the order is set to *Completed* they will be added to the activities assigned to products bought.
Guets customers who buy any product can be converted into a WordPress user and then be assigned to activities.

### Importing ###

Activities comes with a simple import feature. The import system only takes CSV files and expect them to be semicolon separated.
Both activities and participants can be imported.

== Installation ==

#### From Your Site ####

1. Go to 'Plugins' on the left hand side
1. Click the 'Add New' button on top of the page
1. Search for 'Activities' and click 'Install Now'
1. Click 'Activate' on the same screen or in the 'Plugins' screen

#### From Your Multisite ####

1. Visit 'My Sites > Network Admin'
1. Go to 'Plugins' on the left hand side
1. Click the 'Add New' button on top of the page
1. Search for 'Activities' and click 'Install Now'
1. Click 'Activate' on the same screen or in the 'Plugins' screen

#### From WordPress.org ####

1. Download Activities
1. Extract the archive file (.zip) to the */wp-content/plugins/* folder.
1. Activate the plugin on the 'Plugins' screen on your site

== Frequently Asked Questions ==

= How do i send an email to participants? =

1. Go to *Activities* page in the admin area.
1. Find the activity with the participants you want to send emails to
1. Click the export button under the activity name
1. Select email from the *Select User Data* dropdown
1. Click *Export*
1. Click the box containing the emails as text
1. Make a new email in your email webpage or program
1. Paste the text in the *To* input
1. If it doesn't separate the emails correctly, try exporting with `;` as delimiter instead

= How do I use the shortcode? =

Posts

1. Type `[acts name="" data=""]` where you want the activity information
1. Write the name of the activity you want information from between the quotes (example: `[acts name="Yoga Starters" data=""]`)
1. Add the data you want to print (example: `[acts name="Yoga Starters" data="button"]`)

Activity Report Header

1. Type `[acts data=""]` where in the header field of the Activity Report control panel
1. Add the data you want to print (example: `[acts data="loc_address"]`)
1. Click the save button to see the results

Notes:

- The generic settings for Activity Report will not show any results from shortcodes
- Not all options are available Activity Report, for example the `button` option

List of data options

- Activity:
  * `name` = Name
  * `short_desc` = Short Description
  * `long_desc` = Long Description
  * `start` = Start Date
  * `end` = End Date
  * `members` = How many users who are currently participating/listed
  * `button` = A join/unjoin button for posted activities. Lets users join an activity by simply clicking it
  * `archive` = 'Archived' if the activity is archived (users cannot join) or 'Active' if not (users can join)
- Location:
  * `loc_name` or `loc` = Name
  * `loc_address` = Address
  * `loc_description` = Description
  * `loc_postcode` = Postcode
  * `loc_city` = City
  * `loc_country` = Country
- Responsible:
  * `res_name` or `res` = Name
  * `res_name_email` = Name (email)
  * `res_` + `user_meta_key` = Advanced! Custom user data, `***` if the `user_meta_key` is protected or `" "` if nothing was found

== Screenshots ==

1. Activity Report
2. Activity Report with custom fields
3. Exporting participant emails for Yoga Starters
4. An activity connected to a product
5. The Activities general options screen

== Changelog ==

= 1.1.0 =

* Feature: You can now create plans for your activities
* Feature: Plans can be seen on the report page when added to an activity
* Feature: Each report can have their own text for each session without changing the existing plan
* Feature: Plans can be updated or added as a new plan with the changes you've made on a report
* Enhancement: Added additional save buttons on the report page when the settings box is hidden
* Enhancement: Filters are now collapsed on small devices and can be expanded
* Fix: Changed the default value of datetime columns to NULL to avoid errors in the db
* Fix: Activities on products can now be fully removed
* Fix: Handling past orders with variable products should now work correctly
* Fix: Browser tab title should now be correct

= 1.0.5 =

* Feature: Added categories to activities
* Feature: You can now duplicate activities
* Enhancement: Added activity settings to variable products in WooCommerce

= 1.0.4 =

* Feature: Added option to set textarea or country-select as input fields on the quick user edit form
* Enhancement: The report should now be usable for all devices
* Tweak: Improved the look of custom field editing on report settings
* Tweak: Improved layout for all devices
* Fix: Replaced use of Sets in JavaScript with objects to improve compatibility

= 1.0.3 =

* Feature: Clicking names in the activity report will now show a box where you can edit user info
* Feature: Added a *Make default* button to the report settings box
* Feature: Added buttons to check and uncheck session boxes of a selected number
* Enhancement: Report checkboxes are now saved
* Fix: The reload info button on default report settings page should now work

= 1.0.2 =

* Enhancement: Select fields should now be useable if selectize does not load
* Enhancement: Shortcode join button now filters users that has no roles that are allowed to be participants
* Fix: Javascript compile bug that occurred in Internet Explorer

= 1.0.1 =

* Updated security

= 1.0.0 =

* Release

== Upgrade Notice ==

= 1.1.0 =

You can now create plans for your activities!

= 1.0.5 =

Added activity duplication and categories.

= 1.0.4 =

Allows you to use reports on all devices!

= 1.0.3 =

Makes editing participant info on reports easier.
Report checkmarks can be used digitally (not adapted to phone use yet).
Easier to make default report settings.

= 1.0.2 =

Sites are more usable for IE users.

= 1.0.1 =

Should be a lot safer to use.

= 1.0.0 =

Lets you create activities!
