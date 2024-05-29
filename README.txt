=== Additional Order Costs for WooCommerce ===
Contributors: theritesites
Donate link: https://www.theritesites.com
Tags: Order costs, WooCommerce costs, Additional costs, Reporting
Requires at least: 4.0
Requires PHP: 5.6
Tested up to: 6.5
Stable tag: trunk
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Whether it's an extra invoice, or a credit from a merchant related to an order. Dynamically add as many costs to your order as you would like!

== Description ==

Whether it's an extra invoice, or a credit from a merchant related to an order. Sometimes you just need a couple extra cost fields for your store's reporting. Dynamically add as many costs to your order as you would like!

After installing this plugin you’ll be able to manually add or adjust and label additional costs for each order on the WooCommerce Edit Order page. This starts with 5 additional cost fields that can be changed in the settings page with the plugin. 5 fields too much? Set it to 2! Not enough, set it to 9!
In the future we will be looking at making these a repeatable field rather than a set number, but we wanted to get users thinking of other expansions as well!

Once you start tracking the additional order costs you associate, you’ll want to use our [WooCommerce Net Profit](https://www.theritesites.com/plugins/woocommerce-net-profit) plugin that will give you beautiful, functional reports to fully analyze your profitability.
This also pairs well with our other cost tracking plugin, [WooCommerce Cost of Shipping](https://wordpress.org/plugins/woo-cost-of-shipping/).
While using the [WooCommerce Net Profit](https://www.theritesites.com/plugins/woocommerce-net-profit/) plugin, you will individually be able to track Additional Costs, Cost of Shipping, and Cost of Goods. The Net Profit plugin has recently been expanded to allow for custom costs to be associated from any other third party plugin as well, though coding is necessary.

This plugin also serves as the first ever example of how to integrate with WooCommerce Net Profit in a dynamic fashion.
WooCommerce Net Profit as of version 1.5 now has an active filter
`
add_filter( 'trs_wc_np_order_cost_extension', 'callback_function', 10, 1 )
`
How the filter is implemented can be found in additional-oprder-costs-for-woocommerce/includes/class-aoc-wc.php 
The filter allows for a PHP array of objects to be modified. Each object in the array represents a plugin that needs to extend to a cost calculation. The key in the array should be the meta_key found in the database. The rest of the object should be structured as follows:
`
$array[$meta_key] = new StdClass();
$array[$meta_key]->key = $meta_key;
$array[$meta_key]->category = 'additional_costs'; // could be cost_of_goods, additional_costs, cost_of_shipping
$array[$meta_key]->function = 'aoc_wc_calculate_addition_costs_on_order'; // This should be a callable non-class protected function.
`

The function listed above should be found in a file of similar structure to functions.php in themes. This function is called when doing data/reporting queries. The function is only applied to the individual meta keys value.
For example: this plugin stores multiple order cost lines with associated labels. The "function" portion of the filter plucks out the important values and returns a singular non-scalar (non-complex) value. This value can then be subtracted from any other simple (float, integer, double) data format.


== Installation ==

1. Upload `additional-order-costs-for-woocommerce.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Place `<?php do_action('plugin_name_hook'); ?>` in your templates

== Frequently Asked Questions ==


== Screenshots ==
1. A new field is added underneath the order totals area, underneath the "Paid By Customer" row, along with an edit button.
2. Shows the edit inputs. The number that appear relates to the settings page with the plugin.
3. After the Additional Order Costs inputs have been saved.
4. If fields are left blank, they are validated and require input before they can be saved.
5. Bundled settings page for Additional Order Costs. Can be found at Settings -> The Rite Plugins Settings -> Additional Costs Options.
6. Available Order List View if our reporting plugin - WooCommerce Net Profit - is installed.


== Changelog ==

= 1.0.5 =

* Fixed: Retrieving saved values sometimes was not working on new WordPress versions

= 1.0.4 =

* Added: WooCommerce High Performance Order Storage Indicator (HPOS)
* Fixed: Enqueueing assets on new WooCommerce Admin pages

= 1.0.3 =

* Tweak: Change CMB2 source from github to WordPress
* New: Added in error highlighting and javascript validation for additional order costs on the WC Order page
* Tweak: Added assets for wordpress.org plugin directory

= 1.0.2 =

* Repository tweak: removed the updater file from git

= 1.0.1 =

* New: Added is_woocommerce_active check for plugin functionality
* Change: Instead of writing to debug.log we are now utilizing WooCommerce logging to save to a more accessible area
* Tweak: Removed unused code
* Tweak: Completed sanitization of passed in data
* Tweak: Completed escaping of passed in data
* Tweak: Completed the i18n display text translation

= 1.0.0 =

* Initial release


== Upgrade Notice ==
