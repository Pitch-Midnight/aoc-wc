<?php
/**
 * AOC_WC test bootstrapper.
 *
 * The AJAX handler under test (AOC_WC_AJAX::aoc_wc_set_costs_callback())
 * calls wc_get_order() directly, so nothing here can be exercised without
 * WooCommerce loaded first. Same pattern as wc-net-profit/tests/bootstrap.php -
 * copy that file's own comment for the fuller reasoning; only the plugin
 * being loaded differs.
 *
 * @package AOC_WC
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : '/tmp/wordpress-tests-lib';
$_core_dir  = getenv( 'WP_CORE_DIR' ) ? getenv( 'WP_CORE_DIR' ) : '/tmp/wordpress';

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find the WordPress test library at {$_tests_dir}.\n";
	echo "Run: bin/setup-tests.sh <db-name> <db-user> [db-pass]\n";
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

$GLOBALS['_aoc_wc_wc_plugin'] = $_core_dir . '/wp-content/plugins/woocommerce/woocommerce.php';

if ( ! file_exists( $GLOBALS['_aoc_wc_wc_plugin'] ) ) {
	echo "Could not find WooCommerce at {$GLOBALS['_aoc_wc_wc_plugin']}.\n";
	echo "Run: bin/setup-tests.sh <db-name> <db-user> [db-pass]\n";
	exit( 1 );
}

/**
 * Tell WordPress that WooCommerce is active.
 *
 * The test harness loads plugins by require(), so nothing is ever
 * "activated" and the active_plugins option is empty. Some of this suite's
 * code paths check is_woocommerce_active(); filtering the option rather than
 * writing it means no database state and it applies before anything caches
 * the list.
 */
function _aoc_wc_declare_woocommerce_active() {
	return array( 'woocommerce/woocommerce.php' );
}
tests_add_filter( 'pre_option_active_plugins', '_aoc_wc_declare_woocommerce_active' );

/**
 * Load WooCommerce, then this plugin.
 */
function _aoc_wc_manually_load_plugins() {
	require_once $GLOBALS['_aoc_wc_wc_plugin'];
	require dirname( __DIR__ ) . '/additional-order-costs-for-woocommerce.php';
}
tests_add_filter( 'muplugins_loaded', '_aoc_wc_manually_load_plugins' );

/**
 * Run WooCommerce's installer so its schema (and the order tables
 * wc_get_order() needs) exist.
 *
 * WP_UnitTestCase wraps each test in a transaction and rolls it back, but
 * CREATE TABLE is not transactional in MySQL - so the tables must be in
 * place before the first test rather than created inside one.
 */
function _aoc_wc_install_woocommerce() {
	if ( ! class_exists( 'WC_Install' ) ) {
		echo "WooCommerce loaded but WC_Install is missing - cannot create the schema.\n";
		exit( 1 );
	}

	WC_Install::install();

	// Re-read the roles WC_Install just wrote.
	$GLOBALS['wp_roles'] = null;
	wp_roles();
}
tests_add_filter( 'setup_theme', '_aoc_wc_install_woocommerce' );

require $_tests_dir . '/includes/bootstrap.php';
