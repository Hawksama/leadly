<?php

defined( 'ABSPATH' ) || exit;

/***
 * ES_Trigger_Give_Donation_Made class.
 *
 * @since 4.4.1
 */
class ES_Trigger_Give_Donation_Made extends ES_Workflow_Trigger {

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = array( 'give_data' );

	/**
	 * Load trigger admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Give donation', 'email-subscribers' );
		$this->description = __( 'Fires whenever someone make a donation using Give.', 'email-subscribers' );
		$this->group 	   = __( 'Order', 'email-subscribers' );
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'give_checkout_before_gateway', array( $this, 'handle_give_donation' ), 10, 2 );
	}

	/**
	 * Catch Give donation made hook
	 *
	 * @param $posted
	 * @param $user
	 */
	public function handle_give_donation( $posted, $user ) {

		if ( ! empty( $user ) && is_array( $user ) ) {

			$give_data = array(
				'email' => $user['email'],
			);

			if ( ! empty( $user['first_name'] ) ) {
				$give_data['first_name'] = $user['first_name'];
			}

			if ( ! empty( $user['last_name'] ) ) {
				$give_data['last_name'] = $user['last_name'];
			}

			$data = array(
				'give_data' => $give_data,
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

		$give_data = $workflow->data_layer()->get_item( 'give_data' );

		if ( empty( $give_data ) || empty( $give_data['email'] ) ) {
			return false;
		}

		return true;
	}

}
