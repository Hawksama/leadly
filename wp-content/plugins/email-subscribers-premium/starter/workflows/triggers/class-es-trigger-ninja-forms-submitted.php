<?php

defined( 'ABSPATH' ) || exit;

/***
 * ES_Trigger_Ninja_Forms_Submitted class.
 *
 * @since 4.4.1
 */
class ES_Trigger_Ninja_Forms_Submitted extends ES_Workflow_Trigger {

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = array( 'ninja_forms_data' );

	/**
	 * Load trigger admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Ninja Form Submitted', 'email-subscribers' );
		$this->description = __( 'Fires whenever someone fill up Ninja Forms.', 'email-subscribers' );
		$this->group 	   = __( 'Form', 'email-subscribers' );
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'ninja_forms_after_submission', array( $this, 'handle_ninja_form_submission' ), 20, 1 );
	}

	/**
	 * Catch Ninja Forms submission hook.
	 *
	 * @param $data
	 */
	public function handle_ninja_form_submission( $data ) {

		if ( ! empty( $data ) ) {

			$fields = (array) $data['fields'];

			if ( count( $fields ) > 0 ) {
				
				$name  = '';
				$email = '';
				foreach ( $fields as $field ) {
					if ( ! empty( $field['type'] ) && 'email' === $field['type']  ) {
						$email = ! empty( $field['value'] ) ? sanitize_email( $field['value'] ) : '';
					}

					// By default, we are checking this field.
					if ( ! empty( $field['type'] ) && 'textbox' === $field['type'] && 'Name' === $field['label'] ) {
						$name = ! empty( $field['value'] ) ? sanitize_text_field( $field['value'] ) : '';
					}
				}

				if ( ! empty( $email ) ) {

					$data = array(
						'ninja_forms_data' => array(
							'name'   => $name,
							'email'  => $email,
						)
					);

					$this->maybe_run( $data	);
				}
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

		$ninja_forms_data = $workflow->data_layer()->get_item( 'ninja_forms_data' );

		if ( ! is_array( $ninja_forms_data ) || empty( $ninja_forms_data['email'] ) ) {
			return false;
		}

		return true;
	}

}
