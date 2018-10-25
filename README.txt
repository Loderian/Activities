=== Activities ===
Contributors: loderian
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=4WWGL363GNRGQ&lc=NO&item_name=Activities%20WordPress%20Plugin&item_number=Development&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: activities, events, courses, classes, reports
Requires at least: 4.6
Tested up to: 4.9.8
Stable tag: 1.0.2
Requires PHP: 7.0.32
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0-standalone.html

A plugin for managing activities, printing reports and exporting user data.

== Description ==

Activities is a free, light-weight plugin for managing activities.
It provides an easy to use interface to manage your sites activities and who is participating.
This plugin supports multisite installations. Each site has their own activities, locations, activity archive and their own plugin settings.

The heart of the plugin is the Activity Report. It is designed for printing, but through the printing driver the reports can easily be saved as PDFs.
When using the Activity Report it is easy to manage different recurring activities like yoga courses, football training and a lot of similar group oriented activities.
The plugin integrates beautifully with WooCommerce Products.
For example, when a customer is buying participation on a yoga retreat the customer are at the same time assigned the Activity list for this particular retreat.

### Activities ###

Activities has name, start/end dates and two description of different sizes.
They can also be assigned a location, they are created separately for easy reuse.
In addition each activity can have a responsible user who can be granted access to view and/or edit their assigned activities.
Activities can be archived to get a 'history' and reduce the amount of activities on the 'main' list.

#### Activity Report ####

This plugin comes with a report page where you can get an overview of the activity and its participants.
The report is a static structure, but has a lot of customization options for its content.
In addition to specific report settings, the 'Activities > Options > Activity Report' page you can set a generic setting for all reports.
Reports are currently designed for printing, but mobile use will be supported in the future.

#### Shortcodes ####

A simple shortcode is provided to display activity information in blog-posts, products or other types of posts.
Also comes with an option to display a join/unjoin button on posts.

### Locations ###

Locations has the common location information in addition to a description.

### Responsible Users ###

Usually users would be granted permission to use a plugin based on their role.
In this plugin you can grant users permission to only view and/or edit their assigned activities.
This will naturally not restrict access for other users who have permission based on role.

### WooCommerce ###

Activities has a light integration with WooCommerce.
WooCommerce products can be assigned with any number of activities,
when a user buys the product and the order is set to *Completed* they will be added to the activities assigned to products bough.

#### Guest Customers ####

Since activities uses WordPress users, it comes with the option to automatically create users from guest customers.
It can also convert existing guest customers with a single press of a button in the options section.

### Import and Export ###

#### Import ####

Activities comes with a simple import feature. The import system only takes CSV files and expect them to be semicolon separated.
Both activities and participants can be imported.

#### Export ####

The export page allows you to export data about participants for an activity. It currently supports exporting email, phone and names.
Other export features are not yet added, but the existing version is very useful for communication with participants.

### Activity Archive ###

Activities can be archived when they are no longer active.
This allows you to get a history of previous activities without an ever growing list of activities on the main page.

### Users ###

On every user profile in the admin area there is added an overview of the current active activities and the archived ones.
This plugin also includes and option to get a better user search in the admin user list, which allows you to search by first and last name.

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

= How do i create an activity? =

1. Go to *Activities* page in the admin area.
1. Click the *Create new activity* button at the top of the page.
1. Give the activity a name and whatever else you want.
1. Click the *Create* button.

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
  * `res_` + `user_meta_key` = Advanced! Custom user data, `***` if the `user_meta_key` is protected or " " if nothing was found

== Screenshots ==

1. Activity Report
2. Activity Report with custom fields
3. Exporting participant emails for Yoga Starters
4. An activity connected to a product
5. The Activities general options screen

== Changelog ==

= 1.0.2

* Select fields should now be useable if selectize does not load
* Shorcode join button now filters users that has no roles that are allowed to be participants
* Fixed a javascript compile bug that occurred in Internet Explorer
* Fixed FAQ

= 1.0.1 =

* Updated security

= 1.0.0 =

* Release

== Upgrade Notice ==

= 1.0.2 =

* Sites are more usable for IE users

= 1.0.1 =

* Should be a lot safer to use

= 1.0.0 =

* Lets you create activities!

== Arbitrary section ==

This plugin was made for a specific purpose, the Activity Report.
After we did a lot of searching for plugins with a similar feature, but with no results, I was set on the task of creating this plugin.
Mainly the features in this extension was made as they where needed or as a part of the foundation for the Activity Report.
And I made some things that I thought where good to have, like the use of responsibility as a sudo role.
As my first PHP and released project I hope you enjoy it even though it might not be a very polished thing.
