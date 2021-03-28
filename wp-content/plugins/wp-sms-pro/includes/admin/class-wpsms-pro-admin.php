<?php

namespace WP_SMS\Pro;

use WP_SMS\Option;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class Admin {

	public function __construct() {

		// Check loaded WordPress Plugins API
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		// Check required plugin is enabled or not?
		if ( ! is_plugin_active( 'wp-sms/wp-sms.php' ) or ! defined( 'WP_SMS_URL' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			return;
		}

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		// Load and check new version of the plugin
		$this->check_new_version();
	}

	/**
	 * Administrator admin_menu
	 */
	public function admin_menu() {
		add_submenu_page( 'wp-sms', __( 'Scheduled', 'wp-sms' ), __( 'Scheduled', 'wp-sms' ), 'manage_options', 'wp-sms-scheduled', array( $this, 'scheduled_callback' ) );
	}

	/**
	 * Scheduled page.
	 */
	public function scheduled_callback() {
		$page = new Admin\Scheduled();
		$page->render_page();
	}


	/**
	 * Admin notices
	 */
	public function admin_notices() {
		$get_bloginfo_url = 'plugin-install.php?tab=plugin-information&plugin=wp-sms&TB_iframe=true&width=600&height=550';
		echo '<br><div class="update-nag">' . sprintf( __( 'Please Install/Active or Update <a href="%s" class="thickbox">WP-SMS</a> to run the WP-SMS-Pro plugin.', 'wp-sms-pro' ), $get_bloginfo_url ) . '</div>';
	}

	/**
	 * Check new version of plugin
	 */
	public function check_new_version() {
		$license_key = ( defined( 'WP_SMS_LICENSE' ) && ! empty( WP_SMS_LICENSE ) ) ? WP_SMS_LICENSE : Option::getOption( 'license_key', true );
		new Update( array(
			'plugin_slug'  => 'wp-sms-pro',
			'website_url'  => 'https://wp-sms-pro.com',
			'license_key'  => $license_key,
			'plugin_path'  => wp_normalize_path( WP_SMS_PRO_DIR ) . 'wp-sms-pro.php',
			'setting_page' => admin_url( 'admin.php?page=wp-sms-pro' )
		) );
	}
}

new Admin();