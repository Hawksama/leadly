<?php
/**
 * Plugin Name: Aurora Heatmap
 * Plugin URI:  https://market.seous.info/aurora-heatmap
 * Description: Beautiful like an aurora! A simple WordPress heatmap that can be completed with just a plugin.
 * Version:     1.4.11
 * Author:      R3098
 * Author URI:  https://seous.info/
 * License:     GPLv2
 * Text Domain: aurora-heatmap
 *
 * @package aurora-heatmap
 * @copyright 2019-2020 R3098 <info@seous.info>
 * @version 1.4.11
  */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'aurora_heatmap_conflict' ) ) {
	/**
	 * Aurora Heatmap Conflict
	 */
	function aurora_heatmap_conflict() {
		die( esc_html__( 'Fail to activate. Another version of Aurora Heatmap is already active.', 'aurora-heatmap' ) );
	}
}

// Check conflict.
if ( defined( 'AURORA_HEATMAP' ) ) {
	if ( is_admin() ) {
		register_activation_hook( __FILE__, 'aurora_heatmap_conflict' );
	}
	return;
} else {
	// Set constant to check conflict.
	define( 'AURORA_HEATMAP', __FILE__ );
}

/**
 * For GlotPress
 */
function() {
	// For plugin headers.
	__( 'Aurora Heatmap', 'aurora-heatmap' );
	__( 'Beautiful like an aurora! A simple WordPress heatmap that can be completed with just a plugin.', 'aurora-heatmap' );
	// For premium version.
	// Translators: JSON Object format to freemius override_i18n.
	// Translators: See https://github.com/Freemius/wordpress-sdk/blob/master/includes/i18n.php
	// Translators: Example for '{"hey-x": "Hello %s,"}'.
	_x( '{}', 'freemius-override-i18n', 'aurora-heatmap' );
	__( 'Help', 'aurora-heatmap' );
	_x( 'Due to unlicensed or expired licenses, the premium version features, including <b>premium version plugin updates</b>, are limited.', 'Free Plan', 'aurora-heatmap' );
	// translators: %1$s: URL of Upgrade in this plugin.
	// translators: %2$s: URL of Aurora Heatmap Plugin on the WordPress.org.
	// translators: %3$s: URL of Help in this plugin.
	_x( 'To use the latest version, we recommend the <a href="%1$s">lisense agreement</a> or the <a href="%2$s">free version</a>. See the <a href="%3$s">help</a> for details.', 'Free Plan', 'aurora-heatmap' );
	_x( 'About the free plan', 'Free Plan', 'aurora-heatmap' );
	_x( 'The license has expired, but the free plan can still be used.', 'Free Plan', 'aurora-heatmap' );
	_x( 'However, it is not possible to update to the latest version of the premium version, so we recommend that you replace the WordPress.org plugin directory with the free version.', 'Free Plan', 'aurora-heatmap' );
	_x( 'It is also possible to take over the current data.', 'Free Plan', 'aurora-heatmap' );

	_x( 'Migrate data to the free version', 'Free Plan', 'aurora-heatmap' );
	_x( '<b>Stop</b> the premium version plugin.', 'Free Plan', 'aurora-heatmap' );
	_x( '<b>Install</b> / <b>activate</b> <a href="plugin-install.php?s=aurora-heatmap&amp;tab=search&amp;type=term">the free version</a> plugin.', 'Free Plan', 'aurora-heatmap' );
	_x( '<b>Delete</b> the premium version plugin.', 'Free Plan', 'aurora-heatmap' );

	_x( 'If you can\'t receive authentication email', 'Free Plan', 'aurora-heatmap' );
	// translators: %s: Your Profile.
	_x( 'Change the email address of <a href="profile.php">%s</a> and restart the user registration with the following button.', 'Free Plan', 'aurora-heatmap' );
	_x( 'Restart the user registration', 'Free Plan', 'aurora-heatmap' );
	// For standard plan.
	__( 'Unread detection', 'aurora-heatmap' );
	__( 'Detect pages that are not read enough (pages with a high unread rate). It can be used as a clue for content improvement.', 'aurora-heatmap' );
	__( 'Unread ratio for 6 weeks up to last week. This week is calculated on the next Monday.', 'aurora-heatmap' );
	__( 'Rate where only 75% of the content was read. (Yellow-green)', 'aurora-heatmap' );
	__( 'Rate where only 50% of the content was read. (Orange)', 'aurora-heatmap' );
	__( 'Rate where only 25% of the content was read. (Red)', 'aurora-heatmap' );
	_x( 'M jS, Y', 'Date Format', 'aurora-heatmap' );
	_x( 'M jS', 'Date Format', 'aurora-heatmap' );
	// translate: %1$s: from %2$s: to %3$d: percentage %4$.1f: about %5$d total %6$d: height.
	__( "From %1\$s to %2\$s.\n%3\$d%% (about %4\$.1f of %5\$d, average height is %6\$d px)", 'aurora-heatmap' );
};

load_plugin_textdomain( 'aurora-heatmap' );

if ( ! function_exists( 'aurora_heatmap_uninstall' ) ) {
	/**
	 * Aurora Heatmap Uninstall
	 */
	function aurora_heatmap_uninstall() {
		// If installed two or more, keep tables and options.
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$installed = 0;
		foreach ( array_keys( get_plugins() ) as $plugin ) {
			if ( 'aurora-heatmap.php' !== basename( $plugin ) ) {
				continue;
			}
			$installed++;
			if ( 1 < $installed ) {
				return;
			}
		}

		// Delete option, cron, DB tables.
		global $wpdb;
		delete_option( 'aurora_heatmap_option' );
		wp_clear_scheduled_hook( 'aurora_heatmap_cron_daily' );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ahm_events" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ahm_pages" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ahm_norm" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ahm_unread" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}

require_once __DIR__ . '/class-aurora-heatmap-options.php';
require_once __DIR__ . '/class-aurora-heatmap-basic.php';

if ( is_file( __DIR__ . '/aurora-heatmap__premium_only.php' ) ) {
	require_once __DIR__ . '/aurora-heatmap__premium_only.php';
} else {
	register_activation_hook( __FILE__, 'Aurora_Heatmap_Basic::activation' );
	register_deactivation_hook( __FILE__, 'Aurora_Heatmap_Basic::deactivation' );
	register_uninstall_hook( __FILE__, 'aurora_heatmap_uninstall' );

	add_action(
		'init',
		function() {
			Aurora_Heatmap_Basic::get_instance();
		}
	);
}

/* vim: set ts=4 sw=4 sts=4 noet: */
