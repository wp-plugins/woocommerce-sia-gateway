<?php
/*
Plugin Name: SIA - WooCommerce Gateway
Description: Extends WooCommerce by Adding the SIA Gateway.
Version: 1.0
Author: Infoway
*/

if(!defined('SIA_PLUGIN_PATH'))
    define('SIA_PLUGIN_PATH', dirname(__FILE__));
if(!defined('SIA_PLUGIN_URL'))
    define('SIA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'ps_sia_init', 0 );
function ps_sia_init() {
    // If the parent WC_Payment_Gateway class doesn't exist
    // it means WooCommerce is not installed on the site
    // so do nothing
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
     
    // If we made it this far, then include our Gateway Class
    include_once( SIA_PLUGIN_PATH . '/sia.php' );
    include_once( SIA_PLUGIN_PATH . '/sia_class.php' );
 
    // Now that we have successfully included our class,
    // Lets add it too WooCommerce
    add_filter( 'woocommerce_payment_gateways', 'sia_gateway' );
    function sia_gateway( $methods ) {
        $methods[] = 'SIA';
        return $methods;
    }
} 

// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ps_sia_action_links' );
function ps_sia_action_links( $links ) {
    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'sia' ) . '</a>',
    );
 
    // Merge our new link with the default ones
    return array_merge( $plugin_links, $links );    
}
