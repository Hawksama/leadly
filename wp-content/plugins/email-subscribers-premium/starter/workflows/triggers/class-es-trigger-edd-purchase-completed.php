<?php

defined( 'ABSPATH' ) || exit;

/***
 * ES_Trigger_EDD_Purchase_Completed class.
 *
 * @since 4.4.1
 */
class ES_Trigger_EDD_Purchase_Completed extends ES_Workflow_Trigger {

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = array( 'edd_payment' );

	/**
	 * Load trigger admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'EDD purchase completed', 'email-subscribers' );
		$this->description = __( 'Fires whenever EDD purchase gets completed.', 'email-subscribers' );
		$this->group 	   = __( 'Order', 'email-subscribers' );
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'edd_complete_purchase', array( $this, 'handle_edd_complete_purchase' ) );
	}

	/**
	 * Catch EDD purchase completed hoook
	 *
	 * @param int $payment_id
	 *
	 * @since 4.4.1
	 */
	public function handle_edd_complete_purchase( $payment_id = 0 ) {

		if ( ! empty( $payment_id ) ) {

			$edd_payment = edd_get_payment( $payment_id );
			if ( $edd_payment instanceof EDD_Payment ) {
				$data = array(
					'edd_payment' => $edd_payment,
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

		$edd_payment = $workflow->data_layer()->get_item( 'edd_payment' );

		if ( ! ( $edd_payment instanceof EDD_Payment )  ) {
			return false;
		}

		return true;
	}

}
