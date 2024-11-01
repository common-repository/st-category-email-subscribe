=== St Category Email Subscribe ===
Contributors: dharashah
Tags: subscribe, email, category
Donate link: http://sanskruti.net/wordpress-plugins/st-category-email-subscribe/
Requires at least: 5.0
Tested up to: 5.8
Requires PHP: 5.6.x
Stable tag: 1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plugin to allow visitors to subscribe based on category of posts

== Description ==
Surprisingly there is no plugin available to allow users to subscribe for posts on a WordPress website based on category.

 A subscriber for one category might not want to receive posts of another category. This plugin will help you to do that.

 Once a subscriber is added for a particular category, he/she will receive emails as soon as a post is published in that category.





** Features **

1. Add Subscribers for their desired category

2. Use Widget or Short Code to display Subscriber Form

3. Add the Subscribers manually, or upload in batch from a CSV file.

4. Email will be sent to all subscribers as soon as a post is published in that category



== Installation ==
1. Download the Plugin using the Install Plugins 

   OR 

   Upload folder `st-category-email-subscribe` to the `/wp-content/plugins/` directory

2. Activate the plugin through the 'Plugins' menu in WordPress

3. Add Subscribers in  St Category Email Subscribe > Subscribers (See How to use in Other Notes)

3. Place [st_category_subscribe_form] in your page/post where you want to display the subscriber form

4. You may also use the Widget : Category Email Subscribe Form to display subscriber form



== How To Use ==

1. Go To **St Category Email Subscribe** In Side Menu

2. Enter the Send Email from Email and Name in Settings

3. Add **Subscribers** by :

a. Allow users to Subscribe using Subscription Form

   You can either use the widget to display the Subscription from

   Or use shortcode [st_category_subscribe_form] to display subscription form.

b. Upload a Subscriber Manually

   Go to **St Category Email Subscribe > Subscriber **

   Go to **Add a Subscriber**

   Enter the details and press button *Subscribe*

c. Upload using CSV File

   The Format of CSV File must be as below :

     *The First line must be headers as it is ignored while uploading.*

     From the second line, the data should begin in following order :

		**Name,Email,Category ID**

         *Category ID* : 0 for all categories, Category ID for a particular category.

4.  The Added Subscribers will be shown in the table 

5. 	You can Unsubscribe the Subscriber by select the emails and using the **Unsubscribe** button 



== Changelog ==
= 1.4 =
* Secury Fixes
= 1.3 =
* Added filter for email in Subscribers
= 1.2 =
* Compatible with latest WordPress
= 1.1 =
* Increased length of categories to include more categories
= 1.0 =
* Compatible with latest WordPress version

= 0.9 =

* Added Export Subscribers Option

= 0.8 =

* Corrected Errors

= 0.6 =

* Allow multiple categories selection

= 0.5 =

* French Translation Added

= 0.4 =

* Bug Fix in Admin Panel

= 0.3 =

* Bug Fix in Widget

= 0.2 =

* Translate ready



== Upgrade Notice ==
= 1.3 =
* Added filter for email in Subscribers
= 1.2 =
* Compatible with latest WordPress

= 1.1 =

* Increased length of categories to include more categories

= 1.0 =

* Compatible with latest WordPress version

= 0.9 =

* Added Export Subscribers Option

= 0.8 =

* Corrected Errors

= 0.6 =

* Now visitors can opt into multiple categories.

= 0.5 =

* Addition of French Language

  Special thanks to Guillaume de Bure for the translation

= 0.4 =

* Problem in inserting Subscriber Solved

= 0.3 =

* Problem of Widget merging with other widget solved

= 0.2 =

* Now the plugin is translate ready. Sample translation in Spanish is included.


