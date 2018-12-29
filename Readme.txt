=== Balfolk Tickets ===
Contributors: filipbe
Tags: balfolk, ticketing, tickets, event, bal folk
Requires at least: 4.7
Tested up to: 5.0.2
Stable tag: 5.0.2
Requires PHP: 5.6.32
License: GNU General Public License v3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html

Wordpress ticketing plugin for balfolk events.

== Description ==

Balfolk ticketing plugin.

Provided additions:
*	Create event with multiple products
*	Include unique order hash code included with the order confirmation
*	Ability to download separate tickets as the PDF file
*	Ability to scan & update the tickets statuses - this requires the usage of the "FolkTickets" mobile application (available in the Goolge Play store)

Required plugins:
*	WooCommerce
*	Polylang

Additional supported plugins:
*	Product Open Pricing (Name Your Price) for WooCommerce (not working together with Hyyan WooCommerce Polylang Integration)
*	Hyyan WooCommerce Polylang Integration (not working together with Product Open Pricing (Name Your Price) for WooCommerce). WARNING: Hyyan WooCommerce Polylang Integration Stock Sync must be disabled!

== Installation ==

1. Download & activate required dependencies
2. Download & activate the plugin
3. Update the string translations if required (Languages -> Strings translations)

== Changelog ==

= 1.2.2 =
* Additional filtering and summary for the "View tickets" page

= 1.2.1 =
* Ordering event tickets by product publish date

= 1.2.0 =
* Custom stock management (WARNING: Hyyan WooCommerce Polylang Integration Stock Sync must be disabled)

= 1.1.10 =
* Tested with WP 5.0.1
* Bug fix: Fix errors for REST requests (page update failure in Gutenberg previews)

= 1.1.9 =
* Added admin view to manage tickets

= 1.1.8 =
* Remove product link from cart

= 1.1.7 =
* Remove product link from order summary table

= 1.1.6 =
* Bugfix: find default language slug

= 1.1.5 =
* Removed dependency for Hyyan WooCommerce Polylang Integration
* Custom text for "add to card" button
* Support for additonal plugin added: Product Open Pricing (Name Your Price) for WooCommerce

= 1.1.4 =
* Updated readme

= 1.1.3 =
* Added plugin to the WP repository

== Upgrade Notice ==

= 1.1.3 =
Valid plugin in the wordpress repository