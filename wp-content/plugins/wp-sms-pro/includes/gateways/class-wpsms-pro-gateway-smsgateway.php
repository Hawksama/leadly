<?php

namespace WP_SMS\Gateway;

class smsgateway extends \WP_SMS\Gateway {
	private $wsdl_link = "https://smsgateway.me/api/v4/";
	public $tariff = "https://smsgateway.me/";
	public $unitrial = false;
	public $unit;
	public $flash = "disable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->has_key        = true;
		$this->bulk_send      = true;
		$this->help           = "Use username field as 'device_id'.";
		$this->validateNumber = "e.g. 077xxxxxxxx";
	}

	public function SendSMS() {

		/**
		 * Modify sender number
		 *
		 * @since 3.4
		 *
		 * @param string $this ->from sender number.
		 */
		$this->from = apply_filters( 'wp_sms_from', $this->from );

		/**
		 * Modify Receiver number
		 *
		 * @since 3.4
		 *
		 * @param array $this ->to receiver number
		 */
		$this->to = apply_filters( 'wp_sms_to', $this->to );

		/**
		 * Modify text message
		 *
		 * @since 3.4
		 *
		 * @param string $this ->msg text message.
		 */
		$this->msg = apply_filters( 'wp_sms_msg', $this->msg );

		// Get the credit.
		$credit = $this->GetCredit();

		// Check gateway credit
		if ( is_wp_error( $credit ) ) {
			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $credit->get_error_message(), 'error' );

			return $credit;
		}
		foreach ( $this->to as $recipient ) {

			$body = array(
				'phone_number' => trim( $recipient ),
				'message'      => $this->msg,
				'device_id'    => $this->username
			);

			if ( $this->from ) {
				$body['from'] = $this->from;
			}

			$args = array(
				'headers' => array(
					'Authorization' => $this->has_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => json_encode( $body ),
			);

			$response = wp_remote_post( $this->wsdl_link . 'message/send', $args );

			// Check gateway credit
			if ( is_wp_error( $response ) ) {
				// Log the result
				$this->log( $this->from, $this->msg, $this->to, $response->get_error_message(), 'error' );

				return new \WP_Error( 'send-sms', $response->get_error_message() );
			}

			$response_code = wp_remote_retrieve_response_code( $response );

			if ( $response_code == '200' ) {
				$result = $response['body'];

				// Log the result
				$this->log( $this->from, $this->msg, $this->to, $result );

				/**
				 * Run hook after send sms.
				 *
				 * @since 2.4
				 *
				 * @param string $result result output.
				 */
				do_action( 'wp_sms_send', $result );

				return $result;

			} else {
				// Log the result
				$this->log( $this->from, $this->msg, $this->to, 'Error: ' . $response['body'], 'error' );

				return new \WP_Error( 'send-sms', 'Error: ' . $response['body'] );
			}
		}
	}

	public function GetCredit() {
		// Check api key
		if ( ! $this->has_key ) {
			return new \WP_Error( 'account-credit', __( 'API/Key does not set for this gateway', 'wp-sms-pro' ) );
		}

		return 1;
	}
}