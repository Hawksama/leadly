<?php

namespace WP_SMS\Gateway;

class esms extends \WP_SMS\Gateway {
	private $wsdl_link = "http://api.esms.vn/MainService.svc/xml/";
	public $tariff = "http://esms.vn/";
	public $unitrial = true;
	public $unit;
	public $flash = "disable";
	public $isflash = false;

	public function __construct() {
		$this->validateNumber = "09xxxxxxxx";
		parent::__construct();
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

		foreach ( $this->to as $to ) {
			$number = "<CUSTOMER><PHONE>" . $to . "</PHONE></CUSTOMER>";
		}

		$ch        = curl_init();
		$SampleXml = "<RQST>"
		             . "<APIKEY>" . $this->username . "</APIKEY>"
		             . "<SECRETKEY>" . $this->password . "</SECRETKEY>"
		             . "<ISFLASH>0</ISFLASH>"
		             . "<SMSTYPE>7</SMSTYPE>"
		             . "<CONTENT>" . 'Welcome to SMS - from PHP http://esms.vn' . "</CONTENT>"
		             . "<CONTACTS>" . $number . "</CONTACTS>"
		             . "</RQST>";

		curl_setopt( $ch, CURLOPT_URL, $this->wsdl_link . "SendMultipleMessage_V2/" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $SampleXml );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: text/plain' ) );

		$result = curl_exec( $ch );
		$xml    = simplexml_load_string( $result );

		if ( $xml === false ) {
			// Log th result
			$this->log( $this->from, $this->msg, $this->to, $xml, 'error' );

			return false;
		}

		if ( $xml->CodeResult == 100 ) {
			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $xml );

			/**
			 * Run hook after send sms.
			 *
			 * @since 2.4
			 *
			 * @param string $result result output.
			 */
			do_action( 'wp_sms_send', $xml );

			return $xml;
		} else {
			// Log th result
			$this->log( $this->from, $this->msg, $this->to, $xml, 'error' );
			return new \WP_Error( 'send-sms', $xml );
		}

	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username or ! $this->password ) {
			return new \WP_Error( 'account-credit', __( 'Username/Password does not set for this gateway', 'wp-sms-pro' ) );
		}

		if ( ! function_exists( 'simplexml_load_string' ) ) {
			return new \WP_Error( 'required-function', __( 'Function simplexml_load_string not found in your php.', 'wp-sms' ) );
		}

		$result = file_get_contents( $this->wsdl_link . "GetBalance/" . $this->username . "/" . $this->password );

		try {
			$xml = simplexml_load_string( $result );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'required-function', $e->getMessage() );
		}

		if ( $xml->Balance ) {
			return (string) $xml->Balance;
		} else {
			return new \WP_Error( 'account-credit', $result );
		}
	}
}