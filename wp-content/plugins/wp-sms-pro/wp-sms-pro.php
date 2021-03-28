<?php
/**
 * Plugin Name: WP SMS - Professional Package
 * Plugin URI: https://wp-sms-pro.com/
 * Description: Complementary package for add new capability to WP SMS Plugin.
 * Version: 3.2.4
 * Author: VeronaLabs
 * Author URI: https://veronalabs.com/
 * Text Domain: wp-sms-pro
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/*
 * Load Defines
 */
require_once 'includes/defines.php';

// Get options
$wpsms_pro_option = get_option( 'wps_pp_settings' );

/*
 * Load Plugin
 */
include_once 'includes/class-wpsms-pro.php';

new WP_SMS\Pro();
