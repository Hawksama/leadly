<?php
/**
 * Triggers when forminator forms submitted
 *
 * @since       4.4.1
 * @version     1.0
 * @package     Email Subscribers
 */

defined( 'ABSPATH' ) || exit;

/***
 * ES_Trigger_Forminator_Forms_Submitted class.
 *
 * @since 4.4.6
 */
class ES_Trigger_Forminator_Forms_Submitted extends ES_Trigger_Form_Submitted {

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = array( 'forminator_forms_data' );

	/**
	 * Load trigger admin props.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Forminator Form Submitted', 'email-subscribers' );
		$this->description = __( 'Fires whenever someone fill Forminator Plugin\'s form.', 'email-subscribers' );
		$this->group       = __( 'Form', 'email-subscribers' );
	}

	/**
	 * Load trigger option fields
	 */
	public function load_fields() {

		if ( function_exists( 'forminator_cform_modules' ) ) {
			
			$forms = array(
				0 => __( 'Any Form', 'email-subscribers' ),
			);

			$custom_forms = forminator_cform_modules( - 1, 'publish' );

			if ( ! empty( $custom_forms ) ) {

				foreach ( $custom_forms as $form ) {
					$form_id = $form['id'];
					if ( class_exists( 'Forminator_Custom_Form_Model' ) ) {
						$custom_form        = Forminator_Custom_Form_Model::model()->load( $form_id );
						$form_type          = ! empty( $custom_form->settings['form-type'] ) ? $custom_form->settings['form-type'] : '';
						$ignored_form_types = apply_filters( 'ig_es_forminator_ignored_form_types', array( 'login' ) );
						if ( ! in_array( $form_type, $ignored_form_types, true ) ) {
							$title = forminator_get_form_name( $form_id, 'custom_form' );
							if ( mb_strlen( $title ) > 25 ) {
								$title = mb_substr( $title, 0, 25 ) . '...';
							}
							/* translators: %s: Forminator form ID. */
							$title = $title . ' ' . sprintf( esc_html__( '(ID: %s)', 'email-subscribers' ), $form_id );

							$forms[ $form_id ] = $title;
						}
					}
				}

			}

			$form_list_field = new ES_Select();
			$form_list_field->set_name( 'ig-es-forminator-form-id' );
			$form_list_field->set_title( __( 'Select Form', 'email-subscribers' ) );
			$form_list_field->set_options( $forms );
			$form_list_field->set_description( __( 'Supported Form Types: Custom, Contact, Quote Request, Newsletter, Registration Forms', 'email-subscribers' ) );
			$form_list_field->set_required();
			$this->add_field( $form_list_field );
		}
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		// Hook for normal form submission.
		add_action( 'forminator_custom_form_after_handle_submit', array( $this, 'handle_forminator_form_submission' ), 10, 2 );
		// Hook for ajax form submission.
		add_action( 'forminator_custom_form_after_save_entry', array( $this, 'handle_forminator_form_submission' ), 10, 2 );
	}

	/**
	 * Catch Forminator form submission hook.
	 *
	 * @param int $form_id Forminator form ID.
	 * @param array $response Processed response for form submission.
	 */
	public function handle_forminator_form_submission( $form_id, $response ) {

		// Return if forminator's nonce field is not present or fails nonce verification.
		if ( ! isset( $_POST['forminator_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['forminator_nonce'] ), 'forminator_submit_form' ) ) {
			return;
		}

		if ( ! empty( $response['success'] ) && ! empty( $form_id ) ) {
			if ( class_exists( 'Forminator_Custom_Form_Model' ) ) {
				$custom_form = Forminator_Custom_Form_Model::model()->load( $form_id );
				if ( is_object( $custom_form ) ) {

					$form_type          = ! empty( $custom_form->settings['form-type'] ) ? $custom_form->settings['form-type'] : '';
					$ignored_form_types = apply_filters( 'ig_es_forminator_ignored_form_types', array( 'login' ) );

					// Check if form is not in ignored form types.
					if ( ! in_array( $form_type, $ignored_form_types, true ) ) {

						$posted_data = $_POST; // phpcs:ignore

						$ignored_form_actions = apply_filters( 'ig_es_forminator_ignored_form_actions', array( 'forminator_submit_preview_form_custom-forms' ) );
						$form_action          = ! empty( $posted_data['action'] ) ? $posted_data['action'] : '';

						// Check if form action is not in ignored form actions.
						if ( ! empty( $form_action ) && ! in_array( $form_action, $ignored_form_actions, true ) ) {


							$fields = $custom_form->get_fields();

							$ignored_field_types = array();
							if ( class_exists( 'Forminator_Form_Entry_Model' ) ) {
								// Get ingored field types like hidden, recaptcha, line break fields.
								$ignored_field_types = Forminator_Form_Entry_Model::ignored_fields();
							}

							$email_field_keys = array();
							$name_field_keys  = array();

							foreach ( $fields as $field ) {
								$field_array = $field->to_formatted_array();
								$field_type  = $field_array['type'];
								if ( in_array( $field_type, $ignored_field_types, true ) ) {
									continue;
								}
								if ( 'email' === $field_type ) {
									$email_field_keys[] = Forminator_Field::get_property( 'element_id', $field_array );
								} elseif ( 'name' === $field_type ) {
									$name_field_keys[] = Forminator_Field::get_property( 'element_id', $field_array );
								}
							}

							$email_field_name = '';
							if ( ! empty( $email_field_keys ) ) {
								$email_field_name = array_shift( $email_field_keys );
							}


							$name         = '';
							$field_suffix = Forminator_Form_Entry_Model::field_suffix();
							if ( ! empty( $name_field_keys ) ) {
								foreach ( $name_field_keys as $name_key ) {
									// Check if we have data by field id.
									if ( isset( $posted_data[ $name_key ] ) ) {
										$name .= ' ' . sanitize_text_field( $posted_data[ $name_key ] );
									} else {
										/**
										 * If data is not found by $name_key then it may be possible it is group field.
										 * e.g. Name field can have first name, middle name and last name keys in it.
										 */
										foreach ( $field_suffix as $suffix ) {
											$mod_name_key = $name_key . '-' . $suffix;
											if ( isset( $posted_data[ $mod_name_key ] ) ) {
												$name .= ' ' . sanitize_text_field( $posted_data[ $mod_name_key ] );
											}
										}
									}
								}
							}

							if ( ! empty( $email_field_name ) && ! empty( $posted_data[ $email_field_name ] ) ) {

								$email = $posted_data[ $email_field_name ];

								$data = array(
									'forminator_forms_data' => array(
										'email'   => $email,
										'name'    => $name,
										'form_id' => $form_id,
									),
								);

								$this->maybe_run( $data );
							}
						}
					}
				}
			}
		}
	}


	/**
	 * Validate a workflow.
	 *
	 * @param ES_Workflow $workflow Workflow object.
	 *
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {

		$forminator_forms_data = $workflow->data_layer()->get_item( 'forminator_forms_data' );
		if ( ! is_array( $forminator_forms_data ) || empty( $forminator_forms_data['email'] ) ) {
			return false;
		}

		$selected_form_id = $workflow->get_trigger_option( 'ig-es-forminator-form-id' );

		// Check if we need to process only selected form.
		if ( ! empty( $selected_form_id ) && $selected_form_id !== $forminator_forms_data['form_id'] ) {
			return false;
		}

		return true;
	}

}
