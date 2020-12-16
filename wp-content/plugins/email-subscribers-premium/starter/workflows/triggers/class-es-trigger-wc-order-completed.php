<?php

defined( 'ABSPATH' ) || exit;

/***
 * ES_Trigger_WC_Order_Completed class.
 *
 * @since 4.4.1
 */
class ES_Trigger_WC_Order_Completed extends ES_Workflow_Trigger {

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = array( 'wc_order' );

	/**
	 * Load trigger admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'WooCommerce Order Completed', 'email-subscribers' );
		$this->description = __( 'Fires whenever WooCommerce order gets completed.', 'email-subscribers' );
		$this->group 	   = __( 'Order', 'email-subscribers' );
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'woocommerce_order_status_changed', array( $this, 'handle_order_status_changed' ), 10, 4 );
	}

	/**
	 * Catch WooCommerce order status changed hook
	 *
	 * @param $order_id
	 * @param $old_status
	 * @param $new_status
	 * @param null $wc_order
	 */
	public function handle_order_status_changed( $order_id, $old_status, $new_status, $wc_order = null ) {

		if ( 'completed' !== $old_status && 'completed' === $new_status ) {

			if ( $wc_order instanceof WC_Order ) {

				$data = array(
					'wc_order' => $wc_order,
				);

				$this->maybe_run( $data );
			}
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
