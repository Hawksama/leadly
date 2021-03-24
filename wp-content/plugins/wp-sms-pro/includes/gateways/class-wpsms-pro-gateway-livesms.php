<?php

namespace WP_SMS\Gateway;

class livesms extends \WP_SMS\Gateway {
	private $wsdl_link = "http://panel.livesms.eu/sms.do";
	public $tariff = "http://www.livesms.eu/en";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "";
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

		$to     = implode( $this->to, "," );
		$msg    = urlencode( $this->msg );
		$result = file_get_contents( $this->wsdl_link . "?username=" . $this->username . "&password=" . md5( $this->password ) . "&from=" . $this->from . "&to=" . $to . "&message=" . $msg );

		if ( $result ) {
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

			return true;
		} else {
			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $result, 'error' );

			return new \WP_Error( 'send-sms', $result );
		}

	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username or ! $this->password ) {
			return new \WP_Error( 'account-credit', __( 'Username/Password does not set for this gateway', 'wp-sms-pro' ) );
		}

		$result = file_get_contents( $this->wsdl_link . "?username=" . $this->username . "&password=" . md5( $this->password ) . "&credits=1" );

		if ( strchr( $result, 'ERROR' ) ) {
			return new \WP_Error( 'account-credit', $result );
		}

		$result = explode( " ", $result );

		return $result[1];
	}
}