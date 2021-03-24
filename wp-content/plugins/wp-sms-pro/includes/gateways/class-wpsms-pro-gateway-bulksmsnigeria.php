<?php

namespace WP_SMS\Gateway;

class bulksmsnigeria extends \WP_SMS\Gateway {
	private $wsdl_link = "https://www.bulksmsnigeria.com/api/v1/";
	public $tariff = "https://www.bulksmsnigeria.com";
	public $unitrial = false;
	public $unit;
	public $flash = "disabled";
	public $isflash = false;

	public function __construct() {
		parent::__construct();

		$this->has_key        = true;
		$this->validateNumber = "e.g: 07037770033, 2347037770033, +2347037770033, +23407037770033";
		$this->help           = "Just fill the API/Key field with your API key and leave empty Username/Password fields.";
	}

	public function SendSMS() {

		/**
		 * Modify sender number
		 *
		 * @param string $this ->from sender number.
		 *
		 * @since 3.4
		 *
		 */
		$this->from = apply_filters( 'wp_sms_from', $this->from );

		/**
		 * Modify Receiver number
		 *
		 * @param array $this ->to receiver number
		 *
		 * @since 3.4
		 *
		 */
		$this->to = apply_filters( 'wp_sms_to', $this->to );

		/**
		 * Modify text message
		 *
		 * @param string $this ->msg text message.
		 *
		 * @since 3.4
		 *
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

		try {

			$to   = implode( ',', $this->to );
			$msg  = $this->msg;
			$args = array(
				'body' => array(
					'api_token' => $this->has_key,
					'to'        => $to,
					'body'      => $msg,
					'from'      => $this->from
				) );

			$response = wp_remote_post( $this->wsdl_link . "sms/create", $args );

			if ( is_wp_error( $response ) ) {
				// Log the result
				$this->log( $this->from, $this->msg, $this->to, $response->get_error_message(), 'error' );

				return new \WP_Error( 'account-credit', $response->get_error_message() );
			}

			$code = wp_remote_retrieve_response_code( $response );

			$result = json_decode( $response['body'] );

			if ( is_object( $result ) ) {

				if ( isset( $result->data->status ) AND $result->data->status == 'success' AND $code == 200 ) {
					// Log the result
					$this->log( $this->from, $this->msg, $this->to, $result );

					/**
					 * Run hook after send sms.
					 *
					 * @param string $result result output.
					 *
					 * @since 2.4
					 *
					 */
					do_action( 'wp_sms_send', $result );

					return $result;
				} else {
					// Log the result
					$this->log( $this->from, $this->msg, $this->to, $result->error->message, 'error' );

					return new \WP_Error( 'send-sms', $result->error->message );
				}
			} else {
				// Log the result
				$this->log( $this->from, $this->msg, $this->to, 'Empty or Wrong API/Key.', 'error' );

				return new \WP_Error( 'send-sms', 'Empty or Wrong API/Key.' );
			}
		} catch ( \Exception $e ) {
			// Log th result
			$this->log( $this->from, $this->msg, $this->to, $e->getMessage(), 'error' );

			return new \WP_Error( 'send-sms', $e->getMessage() );
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