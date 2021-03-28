<?php

namespace WP_SMS\Pro;

use WP_SMS\Option;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class UltimateMembers {
	/**
	 * UltimateMembers constructor.
	 */
	public function __construct() {
		if ( Option::getOption( 'um_field', true ) ) {
			add_action( 'um_before_update_profile', array( $this, 'save_custom_field' ), 10, 2 );
			add_filter( 'wp_sms_from_notify_user_register', array( $this, 'set_value' ), 10, 1 );
		}
	}

	/**
	 * Save custom mobile field
	 *
	 * @param $changes
	 * @param $user_id
	 *
	 * @return mixed
	 */
	function save_custom_field( $changes, $user_id ) {
		update_user_meta( $user_id, 'mobile', $changes['mobile_number'] );

		return $changes;
	}

	/**
	 * Set filter value
	 *
	 * @param $value
	 *
	 * @return string
	 */
	function set_value( $value ) {
		$value = isset( $value[ 'mobile_number-' . $value['form_id'] ] ) ? $value[ 'mobile_number-' . $value['form_id'] ] : '';

		return $value;
	}
}

new UltimateMembers();