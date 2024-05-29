<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.theritesites.com
 * @since             1.0.0
 * @package           AOC_WC
 *
 * @wordpress-plugin
 * Plugin Name:       Additional Order Costs for WooCommerce
 * Plugin URI:        https://www.theritesites.com/plugins/additional-order-costs
 * Description:       Whether it's an extra invoice, or a credit from a merchant related to an order. Sometimes you just need a couple extra cost fields for your reporting.
 * Version:           1.0.5
 * Author:            TheRiteSites
 * Author URI:        https://www.theritesites.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       additional-order-costs-for-woocommerce
 * Domain Path:       /languages
 * WC tested up to: 8.9
 * WC requires at least: 2.6
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'AOC_WC_VERSION', '1.0.5' );

define( 'AOC_WC_LICENSE_PAGE', 'the_rite_plugins_settings' );

if ( file_exists( __DIR__ . '/cmb2/init.php' ) ) {
	require_once __DIR__ . '/cmb2/init.php';
}

if ( file_exists( __DIR__ . '/includes/class-logger.php' ) ) {
	require_once __DIR__ . '/includes/class-logger.php';
}

if ( ! function_exists( 'is_woocommerce_active' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-aoc-wc-activator.php
 */
function activate_aoc_wc() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aoc-wc-activator.php';
	AOC_WC_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-aoc-wc-deactivator.php
 */
function deactivate_aoc_wc() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aoc-wc-deactivator.php';
	AOC_WC_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_aoc_wc' );
register_deactivation_hook( __FILE__, 'deactivate_aoc_wc' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-aoc-wc.php';

/**
 * Add action links like settings and documentation
 * 
 * @since 1.0.0
 * @param $links array	Passed in links from WordPress plugin list
 */
function trs_aoc_wc_add_plugin_action_links( $links ) {
	$new_links = array(
	'<a href="' . admin_url( 'admin.php?page=aoc_wc_options' ) . '">Settings</a>',
	// '<a href="https://www.theritesites.com/docs/category/woocommerce-net-profit/">Docs</a>',
	'<a href="https://www.theritesites.com/plugin-support/">Support</a>',
	);
	return array_merge($new_links, $links);
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'trs_aoc_wc_add_plugin_action_links' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_aoc_wc() {

	$plugin = new AOC_WC();
	$plugin->run();

}
run_aoc_wc();
