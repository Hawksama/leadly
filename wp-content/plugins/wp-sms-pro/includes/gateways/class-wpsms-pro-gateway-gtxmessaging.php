<?php

namespace WP_SMS\Gateway;

class gtxmessaging extends \WP_SMS\Gateway {
	private $wsdl_link = "http://http.gtx-messaging.net/";
	public $tariff = "https://www.gtx-messaging.com/";
	public $unitrial = false;
	public $unit;
	public $flash = "false";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->bulk_send = true;
		$this->validateNumber = "e.g. +41780000000, +4170000001";
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

		foreach ( $this->to as $number ) {
			$result = file_get_contents( "{$this->wsdl_link}smsc.php?user={$this->username}&pass={$this->password}&method=sms&to={$number}&text={$msg}&type=1&from={$this->from}" );
		}

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

			return $result;
		} else {
			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $result, 'error' );

			return new \WP_Error( 'send-sms', $result );
		}

	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username or !$this->password ) {
			return new \WP_Error( 'account-credit', __( 'API Key does not set for this gateway', 'wp-sms-pro' ) );
		}
		
		return true;
	}
}