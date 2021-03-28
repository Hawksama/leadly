<?php

namespace WP_SMS\Gateway;


class textmagic extends \WP_SMS\Gateway {
	private $wsdl_link = "";
	public $tariff = "";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();

		$this->has_key        = false;
		$this->help           = "Use  Password for 'APIV2_KEY'.";
		$this->validateNumber = "e.g. 447860021130,34911061252,491771781422";
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

		$to   = implode( ',', $this->to );
		$text = $this->msg;

		$client = new \Textmagic\Services\TextmagicRestClient( $this->username, $this->password );
		try {
			$result = $client->messages->create(
				array(
					'text'   => $text,
					'phones' => $to,
					'from'   => $this->from
				)
			);

			if ( ! $result->code ) {
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
				$this->log( $this->from, $this->msg, $this->to, $result, 'error' );

				return new \WP_Error( 'send-sms', $result );
			}
		} catch ( \Exception $e ) {
			if ( $e instanceof \RestException ) {
				// Log the result
				$this->log( $this->from, $this->msg, $this->to, $e->getMessage(), 'error' );

				return new \WP_Error( 'send-sms', $e->getMessage() );
			} else {
				// Log the result
				$this->log( $this->from, $this->msg, $this->to, $e->getMessage(), 'error' );

				return new \WP_Error( 'send-sms', $e->getMessage() );
			}
			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $e->getMessage(), 'error' );

			return new \WP_Error( 'send-sms', $e->getMessage() );
		}

	}

	public function GetCredit() {

		// Check username and password
		if ( ! $this->username && ! $this->password ) {
			return new \WP_Error( 'account-credit', __( 'Username/Password was not set for this gateway', 'wp-sms' ) );
		}

		$client = new \Textmagic\Services\TextmagicRestClient( $this->username, $this->password );
		try {
			$result = $client->User->get();
			$result = json_decode( $result );

			if ( ! $result->balance >= 0 ) {

				return $result->balance;
			} else {
				return new \WP_Error( 'account-credit', print_r( $result, 1 ) );
			}
		} catch ( \Exception $e ) {
			if ( $e instanceof \RestException ) {
				return new \WP_Error( 'account-credit', print_r( $e->getMessage(), 1 ) );
			} else {
				return new \WP_Error( 'account-credit', print_r( $e->getMessage(), 1 ) );
			}

			return new \WP_Error( 'account-credit', print_r( $e->getMessage(), 1 ) );
		}
	}
}