<?php

namespace WP_SMS\Gateway;

class ismartsms extends \WP_SMS\Gateway {
	private $wsdl_link = "https://ismartsms.net/iBulkSMS/webservice/IBulkSMS.asmx?WSDL";
	public $tariff = "https://www.ismartsms.net/";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "All numbers must start with 968. Please do not start number with + or 00.";
		$this->bulk_send      = true;

		@ini_set( "soap.wsdl_cache_enabled", "0" );
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

		$lang = 0;
		if ( isset( $this->options['send_unicode'] ) && $this->options['send_unicode'] ) {
			$lang = 64;
		}

		$flash = 1;
		if ( $this->isflash == true ) {
			$flash = 9;
		}
		try {
			$to                          = implode( $this->to, "," );
			$client                      = new \SoapClient( $this->wsdl_link );
			$parameters['UserID']        = $this->username;
			$parameters['Password']      = $this->password;
			$parameters['Message']       = $this->msg;
			$parameters['Recipients']    = $to;
			$parameters['RecipientType'] = $flash;
			$parameters['Language']      = $lang;
			$parameters['ScheddateTime'] = date( 'Y-m-d' ) . 'T' . date( 'H:i:s' );

			$result = $client->PushMessage( $parameters );

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
		} catch ( \SoapFault $ex ) {
			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $ex->faultstring, 'error' );

			return new \WP_Error( 'send-sms', $ex->faultstring );
		}
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username && ! $this->password ) {
			return new \WP_Error( 'account-credit', __( 'Username/Password does not set for this gateway', 'wp-sms' ) );
		}

		if ( ! class_exists( 'SoapClient' ) ) {
			return new \WP_Error( 'required-class', __( 'Class SoapClient not found. please enable php_soap in your php.', 'wp-sms' ) );
		}

		try {
			$client                      = new \SoapClient( $this->wsdl_link );
			$parameters['UserID']        = $this->username;
			$parameters['Password']      = $this->password;
			$parameters['Language']      = 0;
			$parameters['ScheddateTime'] = date( 'Y-m-d' ) . 'T' . date( 'H:i:s' );
			$parameters['RecipientType'] = 1;
			$parameters['Message']       = 'test';

			$result = $client->PushMessage( $parameters );
			if ( $result->PushMessageResult != 4 OR $result->PushMessageResult != 14 ) {
				return 1;
			}else{
				return new \WP_Error( 'account-credit', 'PushMessageResult: '.$result->PushMessageResult );
			}
		} catch ( \SoapFault $ex ) {
			return new \WP_Error( 'account-credit', $ex->faultstring );
		}
	}
}