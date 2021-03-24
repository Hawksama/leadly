<?php

namespace WP_SMS\Gateway;

class cmtelecom extends \WP_SMS\Gateway {
	private $wsdl_link = 'https://secure.cm.nl/smssgateway/cm/gateway.ashx';
	private $client = null;
	private $http;
	public $tariff = "http://www.cmtelecom.com/";
	public $unitrial = true;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "this value should be in international format. A single mobile number per request. Example: '0098xxxxxxxxxx'";
		$this->has_key        = true;
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

		$msg = urlencode( $this->msg );

		foreach ( $this->to as $to ) {
			$result = file_get_contents( $this->wsdl_link . "?producttoken=" . $this->has_key . "&body=" . $msg . "&to=" . $to . "&from=" . $this->from . "&reference=" . bloginfo( 'name' ) );
		}

		if ( strstr( $result, 'ERROR' ) ) {

			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $result, 'error' );

			return new \WP_Error( 'send-sms', $result );
		} else {
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
		}
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username or ! $this->password ) {
			return new \WP_Error( 'account-credit', __( 'Username/Password does not set for this gateway', 'wp-sms-pro' ) );
		}

		return true;
	}
}