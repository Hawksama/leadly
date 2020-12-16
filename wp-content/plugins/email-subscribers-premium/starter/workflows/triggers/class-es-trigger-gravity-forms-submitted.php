<?php

defined( 'ABSPATH' ) || exit;

/***
 * ES_Trigger_Gravity_Forms_Submitted class.
 *
 * @since 4.4.1
 */
class ES_Trigger_Gravity_Forms_Submitted extends ES_Workflow_Trigger {

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = array( 'gravity_forms_data' );

	/**
	 * Load trigger admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Gravity Form Submitted', 'email-subscribers' );
		$this->description = __( 'Fires whenever someone fill up Gravity Forms.', 'email-subscribers' );
		$this->group 	   = __( 'Form', 'email-subscribers' );
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'gform_after_submission', array( $this, 'handle_gravity_form_submission' ), 20, 2 );
	}

	/**
	 * Catch Gravity Forms submission hook.
	 *
	 * @param $submission
	 * @param $form
	 */
	public function handle_gravity_form_submission( $submission, $form ) {

		if ( ! empty( $form['fields'] ) ) {

			$email = '';
			// find email field & checkbox value
			foreach ( $form['fields'] as $field ) {
				if ( 'email' === $field->type && ! empty( $submission[ $field->id ] ) ) {
					$email = $submission[ $field->id ];
					break;
				}
			}

			if ( ! empty( $email ) ) {

				$data = array(
					'gravity_forms_data' => array(
						'email'  => $email,
					)
				);

				$this->maybe_run( $data	);

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

		$gravity_forms_data = $workflow->data_layer()->get_item( 'gravity_forms_data' );

		if ( ! is_array( $gravity_forms_data ) || empty( $gravity_forms_data['email'] ) ) {
			return false;
		}

		return true;
	}

}
