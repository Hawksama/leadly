<?php

namespace WP_SMS\Gateway;

class twilio extends \WP_SMS\Gateway {
	public $tariff = "http://twilio.com/";
	public $unitrial = true;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "The destination phone number. Format with a '+' and country code e.g., +16175551212 (E.164 format).";
		$this->help           = "For configuration gateway, please use ACCOUNT SID and AUTH TOKEN instead username and password on the following fields.";
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

		$client = new \Twilio\Rest\Client( $this->username, $this->password );

		try {

			$result = array();
			$errors = array();

			foreach ( $this->to as $number ) {
				try {
					$request = $client->messages->create(
						$number,
						array(
							'from' => $this->from,
							'body' => $this->msg,
						)
					);

					if ( $request->to ) {
						$result[ $number ]['to'] = $request->to;
					}
					if ( $request->status ) {
						$result[ $number ]['status'] = $request->status;
					}
					if ( $request->errorMessage ) {
						$result[ $number ]['errorMessage'] = $request->errorMessage;
						$errors[]                          = $number;
					}
					if ( $request->errorCode ) {
						$result[ $number ]['errorCode'] = $request->errorCode;
						$errors[]                       = $number;
					}
				} catch ( \Exception $e ) {
					// Log the result
					$result[ $number ]['Exception'] = $e->getMessage();
					$errors[]                       = $number;
				}
			}
			if ( empty( $errors ) ) {

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
				$this->log( $this->from, $this->msg, $this->to, $result, 'error' );

				return new \WP_Error( 'send-sms', $e->getMessage() );
			}
		} catch ( \Exception $e ) {
			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $e->getMessage(), 'error' );

			return new \WP_Error( 'send-sms', $e->getMessage() );
		}
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username or ! $this->password ) {
			return new \WP_Error( 'account-credit', __( 'Username/Password does not set for this gateway', 'wp-sms-pro' ) );
		}

		if ( ! function_exists( 'curl_version' ) ) {
			return new \WP_Error( 'required-function', __( 'CURL extension not found in your server. please enable curl extension.', 'wp-sms' ) );
		}

		$client = new \Twilio\Rest\Client( $this->username, $this->password );

		try {
			$account = $client->api->accounts( $this->username )->fetch();
			if ( $account->dateCreated->format( 'Y-m-d H:i:s' ) ) {
				return $account->status;
			}
		} catch ( \Exception $e ) {
			return new \WP_Error( 'account-credit', $e->getMessage() );
		}
	}
}