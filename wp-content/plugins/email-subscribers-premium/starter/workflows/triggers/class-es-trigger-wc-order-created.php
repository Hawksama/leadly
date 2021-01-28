<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/***
 * ES_Trigger_WC_Order_Created class.
 *
 * @since 4.6.4
 */
class ES_Trigger_WC_Order_Created extends ES_Workflow_Trigger {

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 * 
	 * @since 4.6.4 
	 */
	public $supplied_data_items = array( 'wc_order' );

	/**
	 * Load trigger admin props.
	 * 
	 * @since 4.6.4
	 */
	public function load_admin_details() {
		$this->title       = __( 'WooCommerce Order Created', 'email-subscribers' );
		$this->description = __( 'This trigger fires after an order is created in the database. At checkout this happens before payment is confirmed.', 'email-subscribers' );
		$this->group 	   = __( 'Order', 'email-subscribers' );
	}

	/**
	 * Register trigger hooks.
	 * 
	 * @since 4.6.4
	 */
	public function register_hooks() {
		add_action( 'woocommerce_new_order', array( $this, 'handle_order_created_event' ), 10, 2 );
	}

	/**
	 * Catch WooCommerce order created hook
	 *
	 * @param $order_id
	 * @param WC_Order $wc_order WooCommerce order object
	 * 
	 * @since 4.6.4
	 */
	public function handle_order_created_event( $order_id, $wc_order = null ) {

		// Session tracking is enabled untill user optout from the checkout page.
		$session_tracking_enabled = IG_ES_WC_Session_Tracker::session_tracking_enabled();

		$show_opt_in_consent = get_site_option( 'ig_es_show_opt_in_consent', 'no' );
		
		// By default set consent status to default so when consent is enabled from settings we can get it from the consent field when placing order.
		$consent_status = 'not_required';

		// Check consent only when consent is enabled and user is not in the admin interface.
		if ( 'yes' === $show_opt_in_consent && ! is_admin() ) {
			$consent_status = ( $session_tracking_enabled ) ? 'given' : 'not_given';
		}

		// If user haven't given consent when consent field present then return.
		if ( 'not_given' === $consent_status ) {
			return;
		}

		if ( is_null( $wc_order) ) {
			$wc_order = wc_get_order( $order_id );
		}
		
		if ( $wc_order instanceof WC_Order ) {

			$data = array(
				'wc_order' => $wc_order,
			);

			$this->maybe_run( $data );
		}
	}

	/**
	 * Validate a workflow.
	 *
	 * @param ES_Workflow $workflow
	 *
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {

		$wc_order = $workflow->data_layer()->get_item( 'wc_order' );

		if ( ! $wc_order || ! $wc_order instanceof WC_Order ) {
			return false;
		}

		return true;
	}

}
