<?php

/**
 * The file that defines the server side functionality during AJAX requests
 *
 * @since      1.1.2
 * @link       www.theritesites.com
 * @package    Enhanced_Ajax_Add_To_Cart_Wc
 * @subpackage Enhanced_Ajax_Add_To_Cart_Wc/includes
 * @author     TheRiteSites <contact@theritesites.com>
 */

defined('ABSPATH') || exit;

class AOC_WC_AJAX {

    public static function init() {

        add_action( 'init', array( __CLASS__, 'aoc_wc_define_ajax' ), 0 );
        // add_action( 'template_redirect', array( __CLASS__, 'do_aoc_wc_ajax' ), 0 );
        self::add_aoc_wc_ajax_events();
        
    }

    public static function aoc_wc_define_ajax() {
        if ( ! empty( $_POST['aoc_wc_action'] ) ) {
            if ( ! defined( 'DOING_AJAX' ) ) {
                define( 'DOING_AJAX', true );
            }
            if ( ! defined( 'WC_DOING_AJAX' ) ) {
                define( 'WC_DOING_AJAX', true );
            }
            if ( ! defined( 'AOC_WC_DOING_AJAX' ) ) {
                define( 'AOC_WC_DOING_AJAX', true );
            }
        }
    }

    public static function add_aoc_wc_ajax_events() {
        add_action( 'wp_ajax_aoc_wc_set_costs', array( __CLASS__, 'aoc_wc_set_costs_callback' ) );

    }

    /**
     * The server side callback when the save button is pressed to vverify and save "Additional Cost" fields to WC Orders.
     * 
     * @since 1.0.0
     */
    public static function aoc_wc_set_costs_callback() {

        ob_start();
        $data = array();

        if ( isset( $_POST['post_id'] ) && isset( $_POST['aoc'] ) && isset( $_POST['security'] ) ) {
            try {
                if ( wp_verify_nonce( $_POST['security'], 'wp_rest' ) ) {
                    $new_cost_data = array();
                    $order_id 	   = intval( sanitize_text_field( $_POST['post_id'] ) );
                    $method        = 'manual'; // Only manual for now, no automated ways of setting this data. Potentially filters?

                    // This is reading in an array, sanitize later.
                    $cost_data_agg = $_POST['aoc'];
                    
                    foreach ( $cost_data_agg as $key => $cost_data ) {
                        if ( isset( $cost_data['label'] ) && isset( $cost_data['cost'] ) ) {
                            $label = sanitize_text_field( $cost_data['label'] );
                            $cost = floatval( sanitize_text_field( $cost_data['cost'] ) );
                            if ( is_string( $label ) && is_float( $cost ) ) {
                                $new_cost_data[] = array(
                                        'label' => $label,
                                        'cost'	=> $cost,
                                );
                            }
                        }
                    }
                    
                    if ( true === is_int( $order_id ) && 0 < $order_id ) {
                        $order = wc_get_order( $order_id );
                        if ( AOC_WC_DEBUG || WP_DEBUG ) {
                            AOC_WC_Logger::add_debug( wc_print_r( $new_cost_data, true ) );
                        }

                        do_action( 'aoc_wc_before_cost_data_stored', $order_id, $new_cost_data, $method );

                        $order->update_meta_data( '_aoc_wc_additional_costs', $new_cost_data );
                        $order_ret = $order->save();

                        do_action( 'aoc_wc_after_cost_data_stored', $order_id, $new_cost_data, $method );

                        $data['payload'] = array( 'order' => $order_ret, 'cost_data' => $new_cost_data );
                    }
                }
                else {
                    return new WP_Error( 'save_additional_costs_error', 'Validation failed.', array( 'status' => 500 ) );
                }

            } catch ( Exception $e ) {
                return new WP_Error('add_additional_costs_error', $e->getMessage(), array( 'status' => 500 ) );
            }
        }
        else {
            if ( true === WP_DEBUG || true === AOC_WC_DEBUG ) {
				if ( isset( $_POST['post_id'] ) ) {
					AOC_WC_Logger::add_debug( 'order id: ' . sanitize_text_field( $_POST['post_id'] ) );
				}
				if ( isset( $_POST['aoc'] ) ) {
					AOC_WC_Logger::add_debug( 'additional order costs: ' . wc_print_r( array_map( 'wp_kses_data', $_POST['aoc'] ), true ) );
				}
            }
            $data['error'] = __( 'no orders with additional cost fields received', 'additional-order-costs-for-woocommerce' );
        }
        wc_get_notices( array() );
        wc_print_notices();
        $html = ob_get_contents();
        ob_end_clean();
        $data['html'] = $html;
        wp_send_json( $data );

        wp_die();
    }
}

AOC_WC_AJAX::init();