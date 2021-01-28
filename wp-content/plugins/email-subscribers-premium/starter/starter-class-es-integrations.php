<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Integrations' ) ) {

	class ES_Integrations {

		public function __construct() {
			// Add Tab in Sync section
			add_filter( 'ig_es_sync_users_tabs', array( $this, 'add_sync_tabs' ), 20, 1 );

			// Sync Comment User
			add_filter( 'comment_form_fields', array( $this, 'show_comment_consent_opt_in' ), 20, 1 );
			
			//add_action( 'wp_insert_comment', array( $this, 'es_handle_insert_comment' ), 10, 2 );
			//add_action( 'wp_set_comment_status', array( $this, 'es_handle_comment_status' ), 10, 2 );

			// Sync WooCommerce user
			//add_action( 'woocommerce_order_status_changed', array( $this, 'subscribe_woocommerce_user' ), 10, 4 );

			// Sync EDD users
			//add_action( 'edd_complete_purchase', array( $this, 'subscribe_edd_user' ), 50 );

			// Sync CF7 users
			//add_action( 'wpcf7_before_send_mail', array( $this, 'es_cf7_sync' ) );

			// Ninja Forms Integration
			//add_action( 'ninja_forms_after_submission', array( $this, 'subscribe_from_ninja_forms' ), 20, 1 );

			// WPForms Integration
			//add_action( 'wpforms_process', array( $this, 'subscribe_from_wpforms' ), 20, 3 );

			// Give Integration
			//add_action( 'give_checkout_before_gateway', array( $this, 'subscribe_from_give' ), 90, 2 );

			// Gravity Forms Integration
			//add_action( 'gform_after_submission', array( $this, 'subscribe_from_gravity_forms' ), 20, 2 );

			// Memberpress Integration
			//add_action( 'starter_ig_es_sync_users_tabs_memberpress', array( $this, 'memberpress_tab_settings' ) );
			//add_action( 'mepr_signup', array( $this, 'subscribe_from_memberpress' ), 5 );

			// Events Manager Integration
			//add_action( 'starter_ig_es_sync_users_tabs_em', array( $this, 'em_tab_settings' ) );

		}

		/**
		 * Add additional tabs into sync
		 * hook to 'ig_es_sync_users_tabs' filter
		 *
		 * @param $tabs
		 *
		 * @return mixed
		 */
		public function add_sync_tabs( $tabs ) {
			global $ig_es_tracker;

			$tabs['comments'] = array(
				'name' => __( 'Comments', 'email-subscribers' ),
				'from' => 'starter'
			);

			$active_plugins = $ig_es_tracker::get_active_plugins();

			// Show only if WooCommerce is installed & activated
			$woocommerce_plugin = 'woocommerce/woocommerce.php';
			if ( in_array( $woocommerce_plugin, $active_plugins ) ) {
				$tabs['woocommerce'] = array(
					'name' => __( 'WooCommerce', 'email-subscribers' ),
					'from' => 'starter'
				);
			}

			// Show only if EDD is installed & activated
			$edd_plugin = 'easy-digital-downloads/easy-digital-downloads.php';
			if ( in_array( $edd_plugin, $active_plugins ) ) {
				$tabs['edd'] = array(
					'name' => __( 'EDD', 'email-subscribers' ),
					'from' => 'starter'
				);
			}

			// Show only if Contact Form 7 is installed & activated
			$cf7_plugin = 'contact-form-7/wp-contact-form-7.php';
			if ( in_array( $cf7_plugin, $active_plugins ) ) {
				$tabs['cf7'] = array(
					'name' => __( 'Contact Form 7', 'email-subscribers' ),
					'from' => 'starter'
				);
			}

			// Show only if EventManager is installed & activated
			$wpforms_plugin = 'wpforms-lite/wpforms.php';
			if ( in_array( $wpforms_plugin, $active_plugins ) ) {
				$tabs['wpforms'] = array(
					'name' => __( 'WPForms', 'email-subscribers' ),
					'from' => 'starter'
				);
			}

			// Show only if Give is installed & activated
			$give_plugin = 'give/give.php';
			if ( in_array( $give_plugin, $active_plugins ) ) {
				$tabs['give'] = array(
					'name' => __( 'Give', 'email-subscribers' ),
					'from' => 'starter'
				);
			}

			// Show only if Ninja Forms is installed & activated
			$ninja_forms_plugin = 'ninja-forms/ninja-forms.php';
			if ( in_array( $ninja_forms_plugin, $active_plugins ) ) {
				$tabs['ninja_forms'] = array(
					'name' => __( 'Ninja Forms', 'email-subscribers' ),
					'from' => 'starter'
				);
			}

			//Show only if Gravity Form is installed & activated
			$gravity_forms_plugin = 'gravityforms/gravityforms.php';
			if ( in_array( $gravity_forms_plugin, $active_plugins ) ) {
				$tabs['gravity_forms'] = array(
					'name' => __( 'Gravity Forms', 'email-subscribers' ),
					'from' => 'starter'
				);
			}

			// Show only if Memberpress is installed & activated
			$memberpress_plugin = '';
			if ( in_array( $memberpress_plugin, $active_plugins ) ) {
				$tabs['memberpress'] = array(
					'name' => __( 'MemberPress', 'email-subscribers' ),
					'from' => 'starter'
				);
			}

			// Show only if Events Manager is installed & activated
			$event_manager_plugin = '';
			if ( in_array( $event_manager_plugin, $active_plugins ) ) {
				$tabs['em'] = array(
					'name' => __( 'Events Manger', 'email-subscribers' ),
					'from' => 'starter'
				);
			}


			return $tabs;
		}

		/**
		 * Add contact data to given list
		 *
		 * @param int $list_id
		 * @param array $data
		 */
		public function add_contact_to_es( $list_id = 0, $data = array() ) {

			// Don't know where to add contact? please find it first
			if ( empty( $list_id ) ) {
				return;
			}

			// Email not found? Say good bye.
			if ( empty( $data['email'] ) || ! filter_var( $data['email'], FILTER_VALIDATE_EMAIL ) ) {
				return;
			}

			// Source not set? Say bye.
			if ( empty( $data['source'] ) ) {
				return;
			}

			$email      = trim( $data['email'] );
			$source     = trim( $data['source'] );
			$status     = ! empty( $data['status'] ) ? trim( $data['status'] ) : 'verified';
			$wp_user_id = ! empty( $data['wp_user_id'] ) ? trim( $data['wp_user_id'] ) : 0;

			// If first name is set, get the first name and last name from $data.
			// Else prepare the first name and last name from $data['name'] field or $data['email'] field
			if ( ! empty( $data['first_name'] ) ) {
				$first_name = $data['first_name'];
				$last_name  = ! empty( $data['last_name'] ) ? $data['last_name'] : '';
			} else {
				$name = ! empty( $data['name'] ) ? trim( $data['name'] ) : '';

				$last_name = '';
				if ( ! empty( $name ) ) {
					$name_parts = ES_Common::prepare_first_name_last_name( $name );
					$first_name = $name_parts['first_name'];
					$last_name  = $name_parts['last_name'];
				} else {
					$first_name = ES_Common::get_name_from_email( $email );
				}
			}

			$guid = ES_Common::generate_guid();

			$contact_data = array(
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'email'      => $email,
				'source'     => $source,
				'status'     => $status,
				'hash'       => $guid,
				'created_at' => ig_get_current_date_time(),
				'wp_user_id' => $wp_user_id
			);

			do_action( 'ig_es_add_contact', $contact_data, $list_id );
		}

		/**
		 * Check whether sync is enable or not
		 *
		 * @param $option
		 *
		 * @return bool
		 */
		public function is_sync_enable( $option ) {
			$settings = get_option( $option, array() );

			$enable  = ! empty( $settings['enable'] ) ? $settings['enable'] : 'no';
			$list_id = ! empty( $settings['list_id'] ) ? $settings['list_id'] : 0;

			if ( 'yes' === $enable && ! empty( $list_id ) ) {
				return $list_id;
			}

			return false;
		}

		/**
		 * Hooked to 'comment_form_fields' filter. Add aditional consent for Email Subscribers
		 *
		 * @param $comment_fields
		 *
		 * @return mixed
		 */
		public function show_comment_consent_opt_in( $comment_fields ) {

			$show_opt_in_consent = get_site_option( 'ig_es_show_opt_in_consent', array() );

			if ( ! empty( $show_opt_in_consent ) ) {

				// If comment consent enable, show consent checkbox on comment form.
				if ( empty( $comment_fields['ig_es_consent'] ) && ! empty( $show_opt_in_consent ) && 'yes' === $show_opt_in_consent ) {

					$commenter    = wp_get_current_commenter();
					$consent      = empty( $commenter['comment_author_email'] ) ? '' : ' checked="checked"';
					$consent_text = get_site_option( 'ig_es_opt_in_consent_text', '' );

					// Show consent checkbox only if admin has setup consent text.
					$comment_fields['ig_es_consent'] = '<p class="ig-es-comment-form-es-consent"><input id="ig-es-comment-form-es-consent" name="ig-es-comment-es-consent" type="checkbox" value="yes"' . $consent . ' />' .
														   '<label for="ig-es-comment-es-consent">' . $consent_text . '</label></p>';
				}
			}

			return $comment_fields;

		}

		/**
		 * Linked to 'wp_insert_comment' action.
		 *
		 * Whenever new comment is insert, this hook will be triggered
		 *
		 * Check the comment status, if "approved" subscribe user into the list if Email exists
		 *
		 * If status is unapproved, do nothing
		 *
		 * @param $comment_id
		 * @param $comment
		 */
		public function es_handle_insert_comment( $comment_id, $comment ) {

			$comment_consent = ig_es_get_request_data( 'ig-es-comment-es-consent', 'no' );

			// subscriber contact to list only if consent given
			if ( 'yes' === $comment_consent ) {

				$email = ! empty( $comment->comment_author_email ) ? $comment->comment_author_email : '';

				// Found email?
				if ( ! empty( $email ) ) {

					$option = 'ig_es_sync_comment_users';

					$list_id = $this->is_sync_enable( $option );

					if ( $list_id ) {

						$comment_author = ! empty( $comment->comment_author ) ? $comment->comment_author : '';

						$data = array(
							'name'   => $comment_author,
							'email'  => $email,
							'source' => 'comment'
						);

						$this->add_contact_to_es( $list_id, $data );
					}
				}
			}

		}

		/**
		 * Sync Customers
		 *
		 * @param $oder_id
		 * @param $old_status
		 * @param $new_status
		 * @param null $wc_order
		 */
		public function subscribe_woocommerce_user( $oder_id, $old_status, $new_status, $wc_order = null ) {

			if ( 'completed' !== $old_status && 'completed' === $new_status ) {

				$option = 'ig_es_sync_woocommerce_users';

				$list_id = $this->is_sync_enable( $option );

				if ( $list_id ) {

					if ( $wc_order instanceof WC_Order ) {

						$email      = $wc_order->get_billing_email();
						$first_name = $wc_order->get_billing_first_name();
						$last_name  = $wc_order->get_billing_last_name();

						if ( ! empty( $email ) ) {

							//Prepare data
							$data = array(
								'first_name' => $first_name,
								'last_name'  => $last_name,
								'source'     => 'woocommerce',
								'email'      => $email
							);

							$this->add_contact_to_es( $list_id, $data );
						}

					}
				}
			}
		}

		public function es_cf7_sync( $contact_form ) {

			$option = 'ig_es_sync_cf7_users';

			$list_id = $this->is_sync_enable( $option );

			if ( $list_id ) {

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
							$data['email']  = $posted_data[ $email_field_name ];
							$data['name']   = ! empty( $posted_data['your-name'] ) ? $posted_data['your-name'] : '';
							$data['source'] = 'cf7';

							// Add CF7 contact to ES
							$this->add_contact_to_es( $list_id, $data );
						}
					}
				}
			}
		}

		public function email_field_filter( $v ) {
			if ( ! empty( $v['basetype'] ) && 'email' === $v['basetype'] ) {
				return $v['name'];
			}
		}

		/**
		 * Subscribe EDD user to ES
		 *
		 * @param $payment_id
		 *
		 * @since 4.1.4
		 */
		public function subscribe_edd_user( $payment_id ) {

			if ( ! empty( $payment_id ) ) {

				$option = 'ig_es_sync_edd_users';

				$list_id = $this->is_sync_enable( $option );

				if ( $list_id ) {

					// Get Email
					$email = (string) edd_get_payment_user_email( $payment_id );

					if ( ! empty( $email ) ) {

						// Get Name
						$user_info = (array) edd_get_payment_meta_user_info( $payment_id );

						$first_name = '';
						$last_name  = '';
						if ( ! empty( $user_info['first_name'] ) ) {
							$first_name = $user_info['first_name'];
						}

						if ( ! empty( $user_info['last_name'] ) ) {
							$last_name = $user_info['last_name'];
						}

						//Prepare data
						$data = array(
							'first_name' => $first_name,
							'last_name'  => $last_name,
							'source'     => 'edd',
							'email'      => $email
						);

						$this->add_contact_to_es( $list_id, $data );
					}

				}
			}

		}

		/**
		 * Subscribe EDD user to ES
		 *
		 * @param $payment_id
		 *
		 * @since 4.1.4
		 */
		public function subscribe_from_memberpress( $txn ) {

			if ( ! empty( $payment_id ) ) {

				$option = 'ig_es_sync_memberpress_users';

				$list_id = $this->is_sync_enable( $option );

				if ( $list_id ) {

					$user = get_userdata( $txn->user_id );

					if ( ! empty( $user->user_email ) ) {

						//Prepare data
						$data = array(
							'first_name' => $user->first_name,
							'last_name'  => $user->last_name,
							'source'     => 'memberpress',
							'email'      => $user->user_email,
						);

						$this->add_contact_to_es( $list_id, $data );
					}

				}
			}

		}

		public function subscribe_from_give( $posted, $user ) {

			$option = 'ig_es_sync_give_users';

			$list_id = $this->is_sync_enable( $option );

			if ( $list_id ) {

				$data = array(
					'email' => $user['email'],
				);

				if ( ! empty( $user['first_name'] ) ) {
					$data['first_name'] = $user['first_name'];
				}

				if ( ! empty( $user['last_name'] ) ) {
					$data['last_name'] = $user['last_name'];
				}

				$data['source'] = 'give';

				$this->add_contact_to_es( $list_id, $data );
			}
		}

		/**
		 * Subscribe from Wp forms
		 *
		 * @param $fields
		 * @param $entry
		 * @param $form_data
		 */
		public function subscribe_from_wpforms( $fields, $entry, $form_data ) {

			$option = 'ig_es_sync_wpforms_users';

			$list_id = $this->is_sync_enable( $option );

			if ( ! empty( $list_id ) ) {

				foreach ( $fields as $field ) {
					if ( 'email' === $field['type'] ) {
						$email = $field['value'];
					}
				}

				if ( ! empty( $email ) ) {
					$data = array(
						'email'  => $email,
						'source' => 'wpforms'
					);

					$this->add_contact_to_es( $list_id, $data );
				}

			}
		}


		public function subscribe_from_gravity_forms( $submission, $form ) {

			$option = 'ig_es_sync_gravity_forms_users';

			$list_id = $this->is_sync_enable( $option );

			if ( ! empty( $list_id ) ) {

				$email = '';
				// find email field & checkbox value
				foreach ( $form['fields'] as $field ) {
					if ( 'email' === $field->type && ! empty( $submission[ $field->id ] ) ) {
						$email = $submission[ $field->id ];
					}
				}

				if ( ! empty( $email ) ) {
					$data = array(
						'email'  => $email,
						'source' => 'gravity_forms'
					);

					$this->add_contact_to_es( $list_id, $data );
				}

			}
		}

		public function subscribe_from_ninja_forms( $data ) {

			$option = 'ig_es_sync_ninja_forms_users';

			$list_id = $this->is_sync_enable( $option );

			if ( ! empty( $list_id ) ) {

				$fields = (array) $data['fields'];

				if ( count( $fields ) > 0 ) {

					$name  = '';
					$email = '';
					foreach ( $fields as $field ) {
						if ( ! empty( $field['type'] ) && 'email' === $field['type'] ) {
							$email = ! empty( $field['value'] ) ? sanitize_email( $field['value'] ) : '';
						}

						// By default, we are checking this field.
						if ( ! empty( $field['type'] ) && 'textbox' === $field['type'] && 'Name' === $field['label'] ) {
							$name = ! empty( $field['value'] ) ? sanitize_text_field( $field['value'] ) : '';
						}
					}

					if ( ! empty( $email ) ) {
						$data = array(
							'name'   => $name,
							'email'  => $email,
							'source' => 'ninja_forms'
						);

						$this->add_contact_to_es( $list_id, $data );
					}
				}

			}
		}

	}
}
