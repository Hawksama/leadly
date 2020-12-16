<?php

defined( 'ABSPATH' ) || exit;

/***
 * ES_Trigger_WPForms_Submitted class.
 *
 * @since 4.4.1
 */
class ES_Trigger_WPForms_Submitted extends ES_Workflow_Trigger {

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = array( 'wpforms_data' );

	/**
	 * Load trigger admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'WP Form Submitted', 'email-subscribers' );
		$this->description = __( 'Fires someone fill up WPForms.', 'email-subscribers' );
		$this->group 	   = __( 'Form', 'email-subscribers' );
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'wpforms_process', array( $this, 'handle_wpform_submission' ), 20, 3 );
	}

	/**
	 * Catch WP Forms submission hook.
	 *
	 * @param $fields
	 * @param $entry
	 * @param $form_data
	 */
	public function handle_wpform_submission( $fields, $entry, $form_data ) {

		if ( ! empty( $fields ) ) {

			$email = '';
			$name  = '';
			foreach ( $fields as $field ) {
				if ( 'email' === $field['type'] ) {
					$email = $field['value'];
				} elseif ( 'name' === $field['type'] ) {
					$name = $field['value'];
				}
			}

			if ( ! empty( $email ) ) {

				$data = array(
					'wpforms_data' => array(
						'email' => $email,
						'name'  => $name,
					),
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

		$wpforms_data = $workflow->data_layer()->get_item( 'wpforms_data' );

		if ( ! is_array( $wpforms_data ) || empty( $wpforms_data['email'] ) ) {
			return false;
		}

		return true;
	}

}
