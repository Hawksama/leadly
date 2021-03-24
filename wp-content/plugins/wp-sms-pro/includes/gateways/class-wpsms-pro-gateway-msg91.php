<?php

namespace WP_SMS\Gateway;

class msg91 extends \WP_SMS\Gateway {
	private $wsdl_link = "https://control.msg91.com/api/";
	public $tariff = "http://www.msg91.com";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();

		$this->has_key = true;
		$this->help    = "For use Routes, you can set from Api key Like this : '3463nernuyh457y|4', after '|' you can set the route number to use.";
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

		$to  = implode( ',', $this->to );
		$msg = urlencode( $this->msg );

		if ( strpos( $this->has_key, '|' ) !== false ) {
			$key_type = explode( '|', $this->has_key );
		} else {
			$key_type = array( $this->has_key, 4 );
		}

		$response = wp_remote_get( $this->wsdl_link . "sendhttp.php?authkey=" . $key_type[0] . "&mobiles=" . $to . "&message=" . $msg . "&sender=" . $this->from . "&route=" . $key_type[1] );
		$code     = wp_remote_retrieve_response_code( $response );
		$result   = wp_remote_retrieve_body( $response );

		if ( $code == 200 ) {
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

	}

	public function GetCredit() {

		if ( strpos( $this->has_key, '|' ) !== false ) {
			$key_type = explode( '|', $this->has_key );
		} else {
			$key_type = array( $this->has_key, 4 );
		}

		$response = wp_remote_get( $this->wsdl_link . "balance.php?authkey=" . $key_type[0] . "&type=" . $key_type[1] );
		$result   = wp_remote_retrieve_body( $response );
		if ( $result ) {
			$result = json_decode( $result );
			if ( is_object( $result ) AND $result->msgType == 'error' ) {
				return new \WP_Error( 'account-credit', print_r( $result, 1 ) );
			}
		}

		return $result;
	}
}