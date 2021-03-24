<?php

namespace WP_SMS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class Pro {

	public function __construct() {
		// Load plugin
		add_action( 'plugins_loaded', array( $this, 'plugin_setup' ), 20 );

		/**
		 * Install And Upgrade plugin
		 */
		require_once WP_SMS_PRO_DIR . 'includes/class-wpsms-pro-install.php';

		register_activation_hook( WP_SMS_PRO_DIR . 'wp-sms-pro.php', array( '\WP_SMS\Pro\Install', 'install' ) );
	}

	/**
	 * Used for regular plugin work.
	 */
	public function plugin_setup() {
		if ( defined( 'WP_SMS_URL' ) ) {
			// Load Language
			$this->load_language( 'wp-sms-pro' );

			// Include Classes
			$this->includes();
		} else {
			require_once WP_SMS_PRO_DIR . 'includes/admin/class-wpsms-pro-admin.php';
		}
	}

	/**
	 * Loads translation file.
	 *
	 * Accessible to other classes to load different language files
	 *
	 * @wp-hook init
	 *
	 * @param string $domain
	 *
	 * @return  void
	 * @since   2.2.0
	 */
	public function load_language( $domain ) {
		load_plugin_textdomain( $domain, false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Includes plugin files
	 */
	public function includes() {
		// Helper classes
		require_once WP_SMS_PRO_DIR . 'includes/integrations/woocommerce/class-wpsms-pro-woocommerce-helper.php';

		// Admin classes.
		if ( is_admin() ) {
			require_once WP_SMS_PRO_DIR . 'includes/admin/class-wpsms-pro-update.php';
			require_once WP_SMS_PRO_DIR . 'includes/admin/class-wpsms-pro-admin.php';

			// Scheduled class.
			require_once WP_SMS_PRO_DIR . 'includes/admin/scheduled/class-wpsms-scheduled.php';

			// Woocommerce admin classes.
			require_once WP_SMS_PRO_DIR . 'includes/integrations/woocommerce/class-wpsms-pro-woocommerce-metabox.php';
		}

		// Utility classes.
		require_once WP_SMS_PRO_DIR . 'vendor/autoload.php';
		require_once WP_SMS_PRO_DIR . 'includes/class-wpsms-scheduled.php';
		require_once WP_SMS_PRO_DIR . 'includes/class-wpsms-pro-gateways.php';
		require_once WP_SMS_PRO_DIR . 'includes/integrations/class-wpsms-pro-wordpress.php';
		require_once WP_SMS_PRO_DIR . 'includes/integrations/class-wpsms-pro-buddypress.php';
		require_once WP_SMS_PRO_DIR . 'includes/integrations/woocommerce/class-wpsms-pro-woocommerce.php';
		require_once WP_SMS_PRO_DIR . 'includes/integrations/woocommerce/class-wpsms-pro-woocommerce-otp.php';
		require_once WP_SMS_PRO_DIR . 'includes/integrations/class-wpsms-pro-gravityforms.php';
		require_once WP_SMS_PRO_DIR . 'includes/integrations/class-wpsms-pro-quform.php';
		require_once WP_SMS_PRO_DIR . 'includes/integrations/class-wpsms-pro-easy-digital-downloads.php';
		require_once WP_SMS_PRO_DIR . 'includes/integrations/class-wpsms-pro-wp-job-manager.php';
		require_once WP_SMS_PRO_DIR . 'includes/integrations/class-wpsms-pro-awesome-support.php';
		require_once WP_SMS_PRO_DIR . 'includes/integrations/class-wpsms-pro-ultimate-members.php';
	}
}