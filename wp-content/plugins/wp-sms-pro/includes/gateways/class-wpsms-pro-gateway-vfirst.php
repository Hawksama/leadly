<?php

namespace WP_SMS\Gateway;

class vfirst extends \WP_SMS\Gateway {
	private $wsdl_link = "https://http.myvfirst.com/smpp/sendsms";
	public $tariff = "http://vfirst.com";
	public $unitrial = false;
	public $unit;
	public $flash = "disable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->bulk_send      = true;
		$this->validateNumber = "e.g. +10000001, +10000002";
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

		$to  = implode( $this->to, "," );
		$msg = urlencode( $this->msg );

		$result = file_get_contents( $this->wsdl_link . "?username=" . $this->username . "&password=" . md5( $this->password ) . "&from=" . $this->from . "&to=" . $to . "&text=" . $msg . "&category=bulk" );
		parse_str( $result, $value );
		$result = $this->sendErrorCheck( $value );

		if ( ! is_wp_error( $result ) ) {

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

			return true;
		} else {
			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $result->get_error_message(), 'error' );

			return new \WP_Error( 'send-sms', $result->get_error_message() );
		}

	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username && ! $this->password ) {
			return new \WP_Error( 'account-credit', __( 'Username/Password does not set for this gateway', 'wp-sms' ) );
		}

		return 1;
	}

	/**
	 * @param $result
	 *
	 * @return string|\WP_Error
	 */
	private function sendErrorCheck( $result ) {

		if ( ! isset( $result['errorcode'] ) ) {

			return new \WP_Error( 'send-sms', sprintf( 'Unknow error: %s', $result ) );
		}

		switch ( $result['errorcode'] ) {
			case '0':
				$error = '';
				break;
			case '1':
				$error = 'This error code generates if message(s) receiver’s mobile number: Is invalid or Greater than 16 digits';
				break;
			case '2':
				$error = 'This error code generates if message sender: Uses wrong alphanumeric/numeric sender ID or Uses sender ID of greater than 16 digits';
				break;
			case '3':
				$error = 'This error code generates if: Blank message is sent or UDH header section does not encapsulate binary content or Message template does not match (In case of transactional messages)';
				break;
			case '4':
				$error = 'This error code generates if: Operator’s service is down or Server side services are down';
				break;
			case '5':
				$error = 'This error code generates if server side authentication fails owing to: Wrong user name or Wrong password or Wrong user name and password';
				break;
			case '6':
				$error = 'This error code generates if service usage contract expires.';
				break;
			case '7':
				$error = 'This error code generates if message(s) sender’s credit account balance is zero.';
				break;
			case '8':
				$error = 'This error code generates if message recipient’s number is not mentioned in the HTTP API‟s parameters.';
				break;
			case '14':
				$error = 'Message was not submitted due to a verification failure in the submitted signature.';
				break;
			default:
				$error = sprintf( 'Unknow error: %s', $result );
				break;
		}

		if ( $error ) {
			return new \WP_Error( 'send-sms', 'Error Code: ' . $result['errorcode'] . '. ' . $error );
		}

		return $result;
	}
}