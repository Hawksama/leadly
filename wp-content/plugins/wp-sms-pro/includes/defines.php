<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


/**
 * Check get_plugin_data function exist
 */
if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}


// Set Plugin path and url defines.
define( 'WP_SMS_PRO_URL', plugin_dir_url( dirname( __FILE__ ) ) );
define( 'WP_SMS_PRO_DIR', plugin_dir_path( dirname( __FILE__ ) ) );

// Get plugin Data.
$plugin_data = get_plugin_data( WP_SMS_PRO_DIR . 'wp-sms-pro.php' );

// Set another useful Plugin defines.
define( 'WP_SMS_PRO_VERSION', $plugin_data['Version'] );