<?php
function aoc_wc_calculate_addition_costs_on_order( $somecosts ) {

	if ( AOC_WC_DEBUG || WP_DEBUG ) {
		AOC_WC_Logger::add_debug( "summing aoc costs for external plugin" );
		AOC_WC_Logger::add_debug( wc_print_r( $somecosts, true ) );
	}
	if ( gettype( $somecosts ) == 'string' ) {

		if ( AOC_WC_DEBUG || WP_DEBUG ) {
			AOC_WC_Logger::add_debug( "its a string" );
		}
		$somecosts = json_decode( $somecosts );

		if ( AOC_WC_DEBUG || WP_DEBUG ) {
			AOC_WC_Logger::add_debug( wc_print_r( $somecosts, true ) );
			AOC_WC_Logger::add_debug( wc_print_r( wp_list_pluck(  $somecosts , 'cost' ), true ) );
		}
	}
	$sum = (float) 0.00;
	$sum = floatval( array_sum( wp_list_pluck(  $somecosts , 'cost' ) ) );

	if ( AOC_WC_DEBUG || WP_DEBUG ) {
		AOC_WC_Logger::add_debug( "final additional costs:" );
		AOC_WC_Logger::add_debug( wc_print_r( $sum, true ) );
	}
	return $sum;
}

function aoc_wc_get_key_value( $key = '', $value = '', $default = false ) {
	if ( function_exists( 'cmb2_get_option' ) ) {

		// Use cmb2_get_option as it passes through some key filters.
		return cmb2_get_option( $key, $value, $default );
	}

	// Fallback to get_option if CMB2 is not loaded yet.
	$opts = get_option( $key, $default );

	$val = $default;

	if ( 'all' == $value ) {
		$val = $opts;
	} elseif ( is_array( $opts ) && array_key_exists( $value, $opts ) && false !== $opts[ $value ] ) {
		$val = $opts[ $value ];
	}

	return $val;
}

?>