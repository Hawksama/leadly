<?php

namespace WP_SMS\Gateway;

class clockworksms extends \WP_SMS\Gateway {
	private $wsdl_link = "";
	public $tariff = "http://www.clockworksms.com/";
	public $unitrial = true;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "44xxxxxxxxxx";
		$this->has_key        = true;
		$this->help           = "Please just enter your API Key.";

		require 'libraries/clockworksms/class-Clockwork.php';
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

		$clockwork      = new \Clockwork( $this->has_key );
		$clockwork->ssl = false;

		foreach ( $this->to as $items ) {
			$to[] = array( 'to' => $items, 'message' => $this->msg );
		}

		try {
			$result = $clockwork->send( $to );

			if ( $result['success'] ) {
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
			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $e->getMessage(), 'error' );

			return new \WP_Error( 'send-sms', $e->getMessage() );
		}
	}

	public function GetCredit() {
		// Check has_key
		if ( ! $this->has_key ) {
			return new \WP_Error( 'account-credit', __( 'Username/Password does not set for this gateway', 'wp-sms-pro' ) );
		}

		if ( ! class_exists( 'DOMDocument' ) ) {
			return new \WP_Error( 'required-class', __( 'Class DOMDocument not found in your php.', 'wp-sms' ) );
		}

		try {
			$clockwork      = new \Clockwork( $this->has_key );
			$clockwork->ssl = false;
			$result         = $clockwork->checkBalance();

			return $result['balance'];
		} catch ( \Exception $e ) {
			return new \WP_Error( 'account-credit', $e->getMessage() );
		}
	}
}

?>
