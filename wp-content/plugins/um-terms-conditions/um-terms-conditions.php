<?php
/*
Plugin Name: Ultimate Member - Terms & Conditions
Plugin URI: https://ultimatemember.com/extensions/terms-conditions/
Description: Add a terms and condition checkbox to your registration forms & require users to agree to your T&Cs before registering on your site.
Version: 2.1.2
Author: Ultimate Member
Author URI: https://ultimatemember.com/
Text Domain: um-terms-conditions
Domain Path: /languages
UM version: 2.0.56
*/

if ( ! defined( 'ABSPATH' ) ) exit;

require_once( ABSPATH.'wp-admin/includes/plugin.php' );

$plugin_data = get_plugin_data( __FILE__ );

define( 'um_terms_conditions_url', plugin_dir_url( __FILE__  ) );
define( 'um_terms_conditions_path', plugin_dir_path( __FILE__ ) );
define( 'um_terms_conditions_plugin', plugin_basename( __FILE__ ) );
define( 'um_terms_conditions_extension', $plugin_data['Name'] );
define( 'um_terms_conditions_version', $plugin_data['Version'] );
define( 'um_terms_conditions_textdomain', 'um-terms-conditions' );

define( 'um_terms_conditions_requires', '2.0.56' );

function um_terms_conditions_plugins_loaded() {
	$locale = ( get_locale() != '' ) ? get_locale() : 'en_US';
	load_textdomain( um_terms_conditions_textdomain, WP_LANG_DIR . '/plugins/' . um_terms_conditions_textdomain . '-' . $locale . '.mo' );
	load_plugin_textdomain( um_terms_conditions_textdomain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'um_terms_conditions_plugins_loaded', 0 );

add_action( 'plugins_loaded', 'um_terms_conditions_check_dependencies', -20 );

if ( ! function_exists( 'um_terms_conditions_check_dependencies' ) ) {
	function um_terms_conditions_check_dependencies() {
		if ( ! defined( 'um_path' ) || ! file_exists( um_path  . 'includes/class-dependencies.php' ) ) {
			//UM is not installed
			function um_terms_conditions_dependencies() {
				echo '<div class="error"><p>' . sprintf( __( 'The <strong>%s</strong> extension requires the Ultimate Member plugin to be activated to work properly. You can download it <a href="https://wordpress.org/plugins/ultimate-member">here</a>', 'um-terms-conditions' ), um_terms_conditions_extension ) . '</p></div>';
			}

			add_action( 'admin_notices', 'um_terms_conditions_dependencies' );
		} else {

			if ( ! function_exists( 'UM' ) ) {
				require_once um_path . 'includes/class-dependencies.php';
				$is_um_active = um\is_um_active();
			} else {
				$is_um_active = UM()->dependencies()->ultimatemember_active_check();
			}

			if ( ! $is_um_active ) {
				//UM is not active
				function um_terms_conditions_dependencies() {
					echo '<div class="error"><p>' . sprintf( __( 'The <strong>%s</strong> extension requires the Ultimate Member plugin to be activated to work properly. You can download it <a href="https://wordpress.org/plugins/ultimate-member">here</a>', 'um-terms-conditions' ), um_terms_conditions_extension ) . '</p></div>';
				}

				add_action( 'admin_notices', 'um_terms_conditions_dependencies' );

			} elseif ( true !== UM()->dependencies()->compare_versions( um_terms_conditions_requires, um_terms_conditions_version, 'terms-conditions', um_terms_conditions_extension ) ) {
				//UM old version is active
				function um_terms_conditions_dependencies() {
					echo '<div class="error"><p>' . UM()->dependencies()->compare_versions( um_terms_conditions_requires, um_terms_conditions_version, 'terms-conditions', um_terms_conditions_extension ) . '</p></div>';
				}

				add_action( 'admin_notices', 'um_terms_conditions_dependencies' );

			} else {
				require_once um_terms_conditions_path . 'includes/core/um-terms-conditions-init.php';
			}
		}
	}
}


if ( ! function_exists( 'um_terms_conditions_activation_hook' ) ) {
	function um_terms_conditions_activation_hook() {
		//first install
		$version = get_option( 'um_terms_conditions_version' );
		if ( ! $version ) {
			update_option( 'um_terms_conditions_last_version_upgrade', um_terms_conditions_version );
		}

		if ( $version != um_terms_conditions_version ) {
			update_option( 'um_terms_conditions_version', um_terms_conditions_version );
		}
	}
}
register_activation_hook( um_terms_conditions_plugin, 'um_terms_conditions_activation_hook' );