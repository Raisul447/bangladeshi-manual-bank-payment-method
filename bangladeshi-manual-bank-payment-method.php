<?php
/*
Plugin Name: Bangladeshi Manual Bank Payment Method
Plugin URI:  https://rshagor.com/
Description: A custom WooCommerce payment gateway designed to securely accept direct bank transfers from customers in Bangladesh, making local transactions simple and reliable.
Version:     1.0.2
Author:      Raisul Islam Shagor
Author URI:  https://shagor.dev
Requires at least: 4.8
Tested up to: 6.8
Requires PHP: 7.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Contributors: shagor447
Text Domain: bangladeshi-manual-bank-payment-method
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    // Function to enqueue styles only on the checkout page - HIGHLY UNIQUE FUNCTION NAME
    if ( ! function_exists( 'bm_gwp_enqueue_styles' ) ) {
        function bm_gwp_enqueue_styles() {
            if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) {
                // CSS file name must be bm_gwp-styles.css
                wp_enqueue_style( 'bm_gwp-style', plugins_url( 'assets/bm_gwp-styles.css', __FILE__ ), array(), '1.0.1' );
            }
        }
        add_action( 'wp_enqueue_scripts', 'bm_gwp_enqueue_styles' );
    }
    
    /**
     * Includes the Bank Payment Gateway class
     */
    // HIGHLY UNIQUE FUNCTION NAME
    if ( ! function_exists( 'bm_gwp_woocommerce_bank_gateway_init' ) ) {
        function bm_gwp_woocommerce_bank_gateway_init() {
            if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
                return;
            }

            // Include the class file
            $plugin_path = dirname( __FILE__ );
            require_once $plugin_path . '/includes/class-wc-gateway-bangladeshi-manual-bank-payment.php';

            /**
             * Adds the custom gateway to WooCommerce
             */
            // HIGHLY UNIQUE FUNCTION NAME
            if ( ! function_exists( 'bm_gwp_add_gateway_class' ) ) {
                function bm_gwp_add_gateway_class( $methods ) {
                    // CLASS NAME CHANGED
                    $methods[] = 'WC_Gateway_Manual_Bangladeshi_Bank_Payment'; 
                    return $methods;
                }
                add_filter( 'woocommerce_payment_gateways', 'bm_gwp_add_gateway_class' );
            }
        }
        

/**
 * Enqueue plugin assets (styles + JS) only on WooCommerce checkout page.
 * Uses a unique namespace to avoid conflicts with other payment plugins.
 */
function bm_gwp_enqueue_assets() {
    if ( function_exists( 'is_checkout' ) && is_checkout() ) {
        wp_enqueue_style( 'bm-gwp-styles', plugin_dir_url( __FILE__ ) . 'assets/wcbmpg-styles.css', array(), '1.0.0' );
        wp_enqueue_script( 'bm-gwp-js', plugin_dir_url( __FILE__ ) . 'assets/bm-gwp.js', array( 'jquery', 'wc-checkout' ), '1.0.0', true );

        // Pass gateway id to the script so it's robust if the id changes.
        wp_localize_script( 'bm-gwp-js', 'bm_gwp_vars', array(
            'gateway_id' => 'manual_bangladeshi_bank_payment',
            'nonce'      => wp_create_nonce( 'bm_gwp_front_nonce' ),
            'notice_text' => __('Please enter the Transaction ID.', 'bangladeshi-manual-bank-payment-method'),
        ) );
    }
}
add_action( 'wp_enqueue_scripts', 'bm_gwp_enqueue_assets', 20 );

add_action( 'plugins_loaded', 'bm_gwp_woocommerce_bank_gateway_init', 11 );
    }
}