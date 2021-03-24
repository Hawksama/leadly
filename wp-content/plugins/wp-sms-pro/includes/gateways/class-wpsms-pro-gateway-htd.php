<?php

namespace WP_SMS\Gateway;

class htd extends \WP_SMS\Gateway
{
	private $wsdl_link = "https://sms.htd.ps/API/";
	public $tariff = "https://www.htd.ps/";
	public $unitrial = false;
	public $unit;
	public $flash = "false";
	public $isflash = false;

	public function __construct()
	{
		parent::__construct();
        $this->has_key = true;
		$this->bulk_send = true;
		$this->validateNumber = "e.g. 970500000000";
	}

	public function SendSMS()
	{

		/**
		 * Modify sender number
		 *
		 * @since 3.4
		 *
		 * @param string $this ->from sender number.
		 */
		$this->from = apply_filters('wp_sms_from', $this->from);

		/**
		 * Modify Receiver number
		 *
		 * @since 3.4
		 *
		 * @param array $this ->to receiver number
		 */
		$this->to = apply_filters('wp_sms_to', $this->to);

		/**
		 * Modify text message
		 *
		 * @since 3.4
		 *
		 * @param string $this ->msg text message.
		 */
		$this->msg = apply_filters('wp_sms_msg', $this->msg);

		// Get the credit.
		$credit = $this->GetCredit();

		// Check gateway credit
		if (is_wp_error($credit)) {
			// Log the result
			$this->log($this->from, $this->msg, $this->to, $credit->get_error_message(), 'error');

			return $credit;
		}

		$api_key = urlencode($this->has_key);
		$senderid = urlencode($this->from);
		$mobile = implode(',', $this->to);
		$msg = urlencode($this->msg);

		$response = wp_remote_get(add_query_arg([
			'id' => $api_key,
			'sender' => $senderid,
			'to' => $mobile,
			'msg ' => $msg,
			'mode' => '1',
		], sprintf('%sSendSMS.aspx', $this->wsdl_link)));

		// Check gateway credit
		if (is_wp_error($response)) {
			// Log the result
			$this->log($this->from, $this->msg, $this->to, $response->get_error_message(), 'error');

			return new \WP_Error('send-sms', $response->get_error_message());
		}

		$response_code = wp_remote_retrieve_response_code($response);

		if ($response_code == '200') {

			$result = $response['body'];

			// Log the result
			$this->log($this->from, $this->msg, $this->to, $result);

			/**
			 * Run hook after send sms.
			 *
			 * @since 2.4
			 *
			 * @param string $result result output.
			 */
			do_action('wp_sms_send', $result);

			return $result;
		} else {
			// Log the result
			$this->log($this->from, $this->msg, $this->to, $response['body'], 'error');

			return new \WP_Error('send-sms', $response['body']);
		}
	}

	public function GetCredit()
	{
		// Check username and password
		if (!$this->has_key) {
			return new \WP_Error('account-credit', __('API Key does not set for this gateway', 'wp-sms-pro'));
		}

		$response = wp_remote_get(add_query_arg([
			'id' => $this->has_key,
		], sprintf('%sGetCredit.aspx', $this->wsdl_link, $this->has_key)));

		if (is_wp_error($response)) {
			return new \WP_Error('account-credit', $response->get_error_message());
		}

		$response_code = wp_remote_retrieve_response_code($response);

		if ($response_code == '200') {
			return $response['body'];
		} else {
			return new \WP_Error('account-credit', $response['body']);
		}
	}
}
