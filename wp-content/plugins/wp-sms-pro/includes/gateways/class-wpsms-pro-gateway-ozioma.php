<?php

namespace WP_SMS\Gateway;

class ozioma extends \WP_SMS\Gateway {
	public $tariff = "http://ozioma.net/";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "include zip code in every number";

		require "libraries/ozioma/OziomaApiImpl.php";
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

		$this->client = new \OziomaApiImpl( $this->username, $this->password );

		$this->client->set_message( $this->msg );
		$this->client->set_recipient( implode( ',', $this->to ) );
		$this->client->set_sender( $this->from );

		$this->client->send();

		if ( $this->client->get_status() == 'OK' ) {
			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $this->client );

			/**
			 * Run hook after send sms.
			 *
			 * @since 2.4
			 *
			 * @param string $result result output.
			 */
			do_action( 'wp_sms_send', $this->client );

			return $this->client;
		} else {
			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $this->client, 'error' );

			return new \WP_Error( 'send-sms', $this->client );
		}
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username or ! $this->password ) {
			return new \WP_Error( 'account-credit', __( 'Username/Password does not set for this gateway', 'wp-sms-pro' ) );
		}

		$this->client = new \OziomaApiImpl( $this->username, $this->password );

		return $this->client->get_balance();
	}
}