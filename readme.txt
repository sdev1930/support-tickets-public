=== Chimney Rock Support Tickets ===

Tags:  helpdesk, ticket system, support, tickets, support ticket, support desk, help, knowledgebase, faq
Requires at least: 4.7
Tested up to: 6.0.1
Stable tag: 1.3.2
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Author: Chimney Rock Software
Author URI: https://chimneyrockmgt.com
Text Domain: crm-support-tickets


== Description ==
Create and manage support tickets for your customers or subscribers with ease.

== How Does It Work? ==
* The plugin adds a support ticketing system on the frontend of your site, you just need to add one shortcode to any frontend page.
* Customers or subscribers can create support tickets.  Each ticket is managed on the backend of your site with an intuitive dashboard.
* Setup in just a few minutes without any complicated processes

http://www.youtube.com/watch?v=d0rtZYRBXaY

== Features ==
* Assign unlimited agents.
* Assign unlimited agent roles.
* Unlimited number of tickets.
* Export tickets to CSV
* Admin and customer notifications by email.
* Prevent spam by enabling Google ReCaptcha.

== Pro Features ==
* Export tickets and all replies to CSV
* Auto close inactive tickets after the number of days that you specify
* Unlimited Canned Responses that can be inserted with one click

== Screenshots ==
 
1. Export Tickets to a CSV file
2. Filter Tickets by status
3. Admin View of tickets and related replies
4. Reports page

= Shortcodes : =

`[crm_support_tickets]` - Place this shortcode on any page so that your customers can add tickets and post replies



== Frequently Asked Questions ==
= How Do I get support? =
If you have any issues, you can submit a support ticket on Wordpress.org  We'll get back to you quickly.

= How Do I Suggest A Feature? =
You can contact us with a feature request here: https://chimneyrockmgt.com/contact/

== Installation ==
Starting with Chimney Rock Support Tickets consists of just two steps: installing and setting up the plugin. Chimney Rock Support Tickets is designed to work with your site’s specific needs, so don’t forget to go through the Settings configuration as explained in the ‘after activation’ step

### INSTALL FROM WITHIN WORDPRESS

1. Visit the plugins page within your dashboard and select ‘Add New’;
1. Search for ‘Chimney Rock Support Tickets’;
1. Activate Chimney Rock Support Tickets from your Plugins page;
1. Go to ‘after activation’ below.

### INSTALL MANUALLY

1. Upload the ‘Chimney Rock Support Tickets’ folder to the /wp-content/plugins/ directory;
1. Activate the Chimney Rock Support Tickets plugin through the ‘Plugins’ menu in WordPress;
1. Go to ‘after activation’ below.

### AFTER ACTIVATION

1. You should see Support Tickets menu item
1. Go through the settings and configure
1. Install the shortcode on a frontend page
1. You’re done

== Requirements ==
* Wordpress 4.7
* PHP 7.0


== Changelog ==

= 1.3.2 =
* tested to current 6.0.1 #72

= 1.3.1 =
* Fix - form resubmission when pressing return on settings page #41

= 1.3.0 =

* Fix escape htlm stripping required css class on admin replies #52
* Add canned replies if Pro plugin installed
* Add reports tab
* tested up to Wp 6.0

= 1.2.3 =
* tested to WP 5.9.2

= 1.2.2 =
* Check license status if Pro Version is active #56
* Disable posting an admin reply unless the customer is set
* Add link to pro on the plugin page if not active
* Strip any visible slashes resulting from escaped content
* Make frontend table mobile responsive


= 1.2.1 =
* Fix css display issue for the replies in the admin ticket view.
* Update the database queries.


= 1.1.0 =
* Enhance - add export tickets bulk action.  Go to Tickets->Bulk Actions->Export to CSV
* Enhance - add filter by status dropdown
* Add translations template file

= 1.0.1 =
* Setup code for pro plugin integration
* Remove replies when the ticket is deleted
* register deactivation hook #40

= 1.0.0 =
* Initial release