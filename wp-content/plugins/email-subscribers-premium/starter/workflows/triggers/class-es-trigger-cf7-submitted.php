<?php

defined( 'ABSPATH' ) || exit;

/***
 * ES_Trigger_CF7_Submitted class.
 *
 * @since 4.4.1
 */
class ES_Trigger_CF7_Submitted extends ES_Workflow_Trigger {

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = array( 'cf7_data' );

	/**
	 * Load trigger admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Contact Form 7 Submitted', 'email-subscribers' );
		$this->description = __( 'Fires whenever someone fill the Contact Form 7 form.', 'email-subscribers' );
		$this->group 	   = __( 'Form', 'email-subscribers' );
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'wpcf7_before_send_mail', array( $this, 'handle_cf7_submission' ) );
	}

	/**
	 * Catch CF7 submission hook.
	 *
	 * @param WPCF7_ContactForm  $contact_form
	 */
	public function handle_cf7_submission( $contact_form ) {

		if ( class_exists( 'WPCF7_Submission' ) ) {

			$submission  = WPCF7_Submission::get_instance();
			$posted_data = $submission->get_posted_data();

			$email_field_name = '';

			// If we are getting default email field 'your-email' use it.
			// Don't check for other field.
			if ( isset( $posted_data['your-email'] ) ) {
				$email_field_name = 'your-email';
			} else {
				$form_tags    = $contact_form->scan_form_tags();
				$email_fields = array_filter( $form_tags, array( 'ES_Integrations', 'email_field_filter' ) );
				if ( ! empty( $email_fields ) ) {
					// We may get multiple email fields. Take first one
					$email_field = array_shift( $email_fields );

					// Find the name of the email field. will be useful to get the email address from get_posted_data
					$email_field_name = $email_field['name'];
				}
			}

			if ( ! empty( $email_field_name ) && ! empty( $posted_data[ $email_field_name ] ) ) {

				if ( ! empty( $posted_data[ $email_field_name ] ) ) {

					$data = array(
						'cf7_data' => array(
							'email' => $posted_data[ $email_field_name ],
							'name'  => ! empty( $posted_data['your-name'] ) ? $posted_data['your-name'] : ''
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

		$cf7_data = $workflow->data_layer()->get_item( 'cf7_data' );

		if ( ! is_array( $cf7_data ) || empty( $cf7_data['email'] ) ) {
			return false;
		}

		return true;
	}

}
