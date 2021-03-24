<?php

namespace WP_SMS\Pro;

use WP_SMS\Option;
use WP_SMS\Pro\WooCommerce\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class WooCommerce {

	public $sms;
	public $options;

	public function __construct() {
		global $sms;

		$this->sms     = $sms;
		$this->options = Option::getOptions( true );

		// Check for add mobile field.
		if ( isset( $this->options['wc_mobile_field'] ) and $this->options['wc_mobile_field'] == 'add_new_field' ) {
			add_action( 'woocommerce_after_order_notes', array( $this, 'checkout_field' ) );
			add_action( 'woocommerce_checkout_process', array( $this, 'checkout_handler' ) );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta' ) );

			// Add the custom field "Mobile" to edit Billing form
			add_action( 'woocommerce_after_edit_address_form_billing', array( $this, 'add_mobile_field_billing' ), 10, 0 );
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'update_user_meta' ) );
			add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'show_extra_details' ), 10, 1 );
		}
		// Edit billing_phone field if wp-sms-input intel is enabled
		if ( isset( $this->options['wc_mobile_field'] ) and $this->options['wc_mobile_field'] == 'used_current_field' and Option::getOption( 'international_mobile' ) ) {
			add_filter( 'woocommerce_checkout_fields', array( $this, 'edit_billing_phone' ) );
		}

		if ( isset( $this->options['wc_notify_product_enable'] ) ) {
			add_action( 'publish_product', array( $this, 'notification_new_product' ) );
		}

		if ( isset( $this->options['wc_notify_order_enable'] ) ) {
			add_action( 'woocommerce_new_order', array( $this, 'admin_notification_order' ) );
		}

		if ( isset( $this->options['wc_notify_customer_enable'] ) ) {
			add_action( 'woocommerce_new_order', array( $this, 'customer_notification_order' ) );
		}

		if ( isset( $this->options['wc_notify_stock_enable'] ) ) {
			add_action( 'woocommerce_low_stock', array( $this, 'admin_notification_low_stock' ) );
			add_action( 'woocommerce_no_stock', array( $this, 'admin_notification_low_stock' ) );
		}

		if ( isset( $this->options['wc_notify_status_enable'] ) ) {
			add_action( 'woocommerce_order_edit_status', array( $this, 'notification_change_order_status' ), 10, 2 );
		}

		if ( isset( $this->options['wc_notify_by_status_enable'] ) ) {
			add_action( 'woocommerce_order_status_changed', array( $this, 'notification_by_order_status' ), 10, 3 );
		}
	}

	/**
	 * WooCommerce Features
	 * Add the field to the checkout page
	 *
	 * @param $checkout
	 */
	public function checkout_field( $checkout ) {

		if ( Option::getOption( 'international_mobile' ) ) {
			$wp_sms_input_mobile = "wp-sms-input-mobile";
		} else {
			$wp_sms_input_mobile = "";
		}

		woocommerce_form_field( 'mobile', array(
			'type'        => 'text',
			'id'          => $wp_sms_input_mobile,
			'class'       => array( 'input-text' ),
			'label'       => __( 'Mobile Number', 'wp-sms-pro' ),
			'placeholder' => __( 'Enter your mobile number to get any notifications about your order', 'wp-sms-pro' ),
			'required'    => true,
		),
			$checkout->get_value( 'mobile' ) );
	}

	/**
	 * WooCommerce Features
	 * Process the checkout
	 */
	public function checkout_handler() {
		// Check if the field is set, if not then show an error message.
		if ( ! $_POST['mobile'] ) {
			wc_add_notice( __( 'Please enter mobile number.', 'wp-sms-pro' ), 'error' );
		}
	}

	/**
	 * WooCommerce Features
	 * Update the order meta with field value
	 *
	 * @param $order_id
	 */
	public function update_order_meta( $order_id ) {
		if ( ! empty( $_POST['mobile'] ) ) {
			update_post_meta( $order_id, 'mobile', sanitize_text_field( $_POST['mobile'] ) );
		}
	}

	/**
	 * WooCommerce notification new product
	 *
	 * @param $post_ID
	 */
	public function notification_new_product( $post_ID ) {
		global $wpdb;

		if ( $this->options['wc_notify_product_receiver'] == 'subscriber' ) {

			if ( $this->options['wc_notify_product_cat'] ) {
				$this->sms->to = $wpdb->get_col( "SELECT mobile FROM {$wpdb->prefix}sms_subscribes WHERE group_ID = '" . $this->options['wc_notify_product_cat'] . "'" );
			} else {
				$this->sms->to = $wpdb->get_col( "SELECT mobile FROM {$wpdb->prefix}sms_subscribes WHERE status = 1" );
			}

		} else if ( $this->options['wc_notify_product_receiver'] == 'users' ) {
			$customers_numbers = Helper::getCustomersNumbers();
			if ( ! $customers_numbers ) {
				return;
			}
			$this->sms->to = $customers_numbers;
		}
		$template_vars  = array(
			'%product_title%' => get_the_title( $post_ID ),
			'%product_url%'   => wp_get_shortlink( $post_ID ),
			'%product_date%'  => get_post_time( 'Y-m-d', true, $post_ID, true ),
			'%product_price%' => isset( $_REQUEST['_regular_price'] ) ? $_REQUEST['_regular_price'] : ''
		);
		$message        = str_replace( array_keys( $template_vars ), array_values( $template_vars ), $this->options['wc_notify_product_message'] );
		$this->sms->msg = $message;
		$this->sms->SendSMS();
	}

	/**
	 * WooCommerce admin notification order
	 *
	 * @param $order_id
	 */
	public function admin_notification_order( $order_id ) {
		$order          = new \WC_Order( $order_id );
		$this->sms->to  = explode( ',', $this->options['wc_notify_order_receiver'] );
		$template_vars  = array(
			'%billing_first_name%' => $order->get_billing_first_name(),
			'%billing_company%'    => $order->get_billing_company(),
			'%billing_address%'    => ( $order->get_billing_address_1() == "" ? $order->get_billing_address_2() : $order->get_billing_address_1() ),
			'%order_edit_url%'     => $order->get_edit_order_url(),
			'%billing_phone%'      => wp_strip_all_tags( $order->get_billing_phone() ),
			'%order_number%'       => $order->get_order_number(),
			'%order_total%'        => wp_strip_all_tags( $order->get_formatted_order_total() ),
			'%order_id%'           => $order_id,
			'%status%'             => wc_get_order_status_name( $order->get_status() ),
		);
		$message        = str_replace( array_keys( $template_vars ), array_values( $template_vars ), $this->options['wc_notify_order_message'] );
		$this->sms->msg = $message;
		$this->sms->SendSMS();
	}

	/**
	 * WooCommerce customer notification order
	 *
	 * @param $order_id
	 */
	public function customer_notification_order( $order_id ) {
		// Get mobile number from
		$to = $this->get_customer_mobile_number( $order_id );

		// Check mobile number has exist
		if ( ! $to ) {
			return;
		}

		$order          = new \WC_Order( $order_id );
		$this->sms->to  = array( $to );
		$template_vars  = array(
			'%billing_first_name%' => $_POST['billing_first_name'],
			'%billing_last_name%'  => $_POST['billing_last_name'],
			'%order_view_url%'     => $order->get_view_order_url(),
			'%order_pay_url%'      => $order->get_checkout_payment_url(),
			'%order_number%'       => $order->get_order_number(),
			'%order_total%'        => wp_strip_all_tags( $order->get_formatted_order_total() ),
			'%order_id%'           => $order_id,
			'%status%'             => wc_get_order_status_name( $order->get_status() ),
		);
		$message        = str_replace( array_keys( $template_vars ), array_values( $template_vars ), $this->options['wc_notify_customer_message'] );
		$this->sms->msg = $message;
		$this->sms->SendSMS();
	}

	/**
	 * WooCommerce notification low stock
	 *
	 * @param $stock
	 */
	public function admin_notification_low_stock( $stock ) {
		$this->sms->to  = explode( ',', $this->options['wc_notify_stock_receiver'] );
		$template_vars  = array(
			'%product_id%'   => $stock->id,
			'%product_name%' => $stock->post->post_title
		);
		$message        = str_replace( array_keys( $template_vars ), array_values( $template_vars ), $this->options['wc_notify_stock_message'] );
		$this->sms->msg = $message;
		$this->sms->SendSMS();
	}

	/**
	 * WooCommerce notification change status
	 *
	 * @param $order_id
	 */
	public function notification_change_order_status( $order_id, $new_status ) {
		$order = new \WC_Order( $order_id );

		// Check Before Status Order Send SMS
        $last_status = get_post_meta($order_id, '_wp_sms_customer_order_status', true);
        if (!empty($last_status) and $last_status == $new_status) {
            return;
        }

		// Get mobile number from
		$to = $this->get_customer_mobile_number( $order_id );

		// Check mobile number has exist
		if ( ! $to ) {
			return;
		}

		$this->sms->to  = array( $to );
		$template_vars  = array(
			'%customer_first_name%' => $order->get_billing_first_name(),
			'%customer_last_name%'  => $order->get_billing_last_name(),
			'%order_view_url%'      => $order->get_view_order_url(),
			'%order_pay_url%'       => $order->get_checkout_payment_url(),
			'%order_number%'        => $order->get_order_number(),
			'%status%'              => wc_get_order_status_name( $new_status ),
		);
		$message        = str_replace( array_keys( $template_vars ), array_values( $template_vars ), $this->options['wc_notify_status_message'] );
		$this->sms->msg = $message;
		$this->sms->SendSMS();

		// Update Post Meta
        update_post_meta($order_id, '_wp_sms_customer_order_status', $new_status);
	}

	/**
	 * WooCommerce notification by order status
	 *
	 * @param $order_id
	 */
	public function notification_by_order_status( $order_id = 0, $old_status = '', $new_status = '' ) {
		$order = new \WC_Order( $order_id );

		// Get mobile number from
		$to = $this->get_customer_mobile_number( $order_id );

		// Check mobile number has exist
		if ( ! $to ) {
			return;
		}

		$sms_message = false;

		$content = $this->options['wc_notify_by_status_content'];

		foreach ( $content as $key => $value ) {
			if ( $value['notify_status'] == '1' && $value['order_status'] == $new_status ) {
				$sms_message = $value['message'];
			}
		}

		if ( ! $sms_message ) {
			return;
		}

		$this->sms->to  = array( $to );
		$template_vars  = array(
			'%customer_first_name%' => $order->get_billing_first_name(),
			'%customer_last_name%'  => $order->get_billing_last_name(),
			'%order_view_url%'      => $order->get_view_order_url(),
			'%order_pay_url%'       => $order->get_checkout_payment_url(),
			'%order_number%'        => $order->get_order_number(),
			'%status%'              => wc_get_order_status_name( $order->get_status() ),
		);
		$message        = str_replace( array_keys( $template_vars ), array_values( $template_vars ), $sms_message );
		$this->sms->msg = $message;
		$this->sms->SendSMS();
	}

	/**
	 * Get customer mobile number by order id
	 *
	 * @param $order_id
	 *
	 * @return string|void
	 */
	private function get_customer_mobile_number( $order_id ) {
		if ( empty( $this->options['wc_mobile_field'] ) ) {
			return;
		}

		switch ( $this->options['wc_mobile_field'] ) {
			case 'add_new_field';
				if ( isset( $_POST['mobile'] ) ) {
					$mobile = $_POST['mobile'];
				} else {
					$mobile = get_post_meta( $order_id, 'mobile', true );
				}
				break;

			case 'used_current_field';
			    $order = new \WC_Order( $order_id );
			    $billing_phone = $order->get_billing_phone();
                $user_id = $order->get_customer_id();

                if (isset($_POST['billing_phone'])) {
                    $mobile = $_POST['billing_phone'];
                } elseif (!empty($billing_phone)) {
                    $mobile = $billing_phone;
                } else {
                    if (!empty($user_id)) {
                        $mobile = get_user_meta($user_id, 'billing_phone', true);
                    }
                }
				break;

			default;
				$mobile = '';
				break;
		}

		return $mobile;
	}

	/**
	 * Add Mobile field to edit billing form
	 */
	function add_mobile_field_billing() {

		// Get User Mobile Number
		$user_mobile = get_user_meta( get_current_user_id(), 'mobile', true );

		$html = '
		<p class="form-row form-row-wide" id="mobile_field">
			<label for="mobile">Mobile Number</label>
			<span class="woocommerce-input-wrapper">
				<input type="text" class="input-text" id="mobile" value="' . $user_mobile . '" disabled="disabled" /> 
			</span>
		</p>
		';
		echo $html;
	}

	/**
	 * Update user Phone and Verify if need
	 *
	 * @param $order_id
	 */
	function update_user_meta() {
		$user_id = get_current_user_id();
		if ( $user_id and $user_id != 0 ) {
			$user_mobile = isset( $_POST['mobile'] ) ? $_POST['mobile'] : '';

			if ( $user_mobile ) {
				update_user_meta( $user_id, 'mobile', $user_mobile );
			}

			if ( Option::getOption( 'wc_otp_enable', true ) ) {
				update_user_meta( $user_id, 'mobile_verified', '1' );
			}
		}
	}


	/**
	 * Showing the meta mobile number field on each orders admin panel
	 *
	 * @param $order
	 */
	function show_extra_details( $order ) {
		$mobile = get_post_meta( $order->get_id(), 'mobile', true );
		if ( $mobile ) {
			$html = '
		<div class="order_data_column">
		<h4><strong>' . __( 'Mobile:', 'wp-sms-pro' ) . '</strong></h4>
		<a href="tel:' . $mobile . '">' . $mobile . '</a>
		</div>
		';
			echo $html;
		}
	}

	/**
	 * @param $fields
	 *
	 * @return mixed
	 */
	function edit_billing_phone( $fields ) {
		$fields['billing']['billing_phone']['id'] = 'wp-sms-input-mobile';

		return $fields;
	}
}

new WooCommerce();