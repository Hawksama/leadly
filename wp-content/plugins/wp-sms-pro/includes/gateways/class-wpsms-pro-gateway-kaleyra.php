<?php

namespace WP_SMS\Gateway;

class kaleyra extends \WP_SMS\Gateway {
	private $wsdl_link = "https://api-alerts.kaleyra.com/v4/";
	public $tariff = "https://www.kaleyra.com/";
	public $unitrial = false;
	public $unit;
	public $flash = "false";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
        $this->has_key = true;
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

        $msg      = urlencode($this->msg);
        $response = [];

		foreach ( $this->to as $number ) {
			$response = file_get_contents( "{$this->wsdl_link}?api_key={$this->has_key}&method=sms&to={$number}&message={$msg}&type=1&sender={$this->from}" );
		}

		$response = json_decode($response, true);

		if (isset($response['status']) && $response['status'] == 'OK') {

			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $response );

			/**
			 * Run hook after send sms.
			 *
			 * @since 2.4
			 *
			 * @param string $response result output.
			 */
			do_action( 'wp_sms_send', $response );

			return $response;
		} else {
			// Log the result
			$this->log( $this->from, $this->msg, $this->to, $response['message'], 'error' );

			return new \WP_Error( 'send-sms', $response['message'] );
		}
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->has_key ) {
			return new \WP_Error( 'account-credit', __( 'API Key does not set for this gateway', 'wp-sms-pro' ) );
		}

        $response = file_get_contents("{$this->wsdl_link}?api_key={$this->has_key}&method=account.credits");
        if ($response && !empty($response)) {
            $response = json_decode($response, true);
            if (isset($response['status']) && $response['status'] == 'OK') {
                return $response['data']['credits'];
            } elseif (isset($response['status'])) {
                return new \WP_Error('account-credit', $response['message']);
            }
        }

		return true;
	}
}