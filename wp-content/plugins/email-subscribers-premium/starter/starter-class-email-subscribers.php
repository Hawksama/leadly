<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Email_Subscribers_Starter' ) ) {

	class Email_Subscribers_Starter {

		public $starter_plugin_url;
		public $starter_plugin_path;

		public function __construct() {

			$this->starter_plugin_url  = untrailingslashit( plugins_url( '/', __FILE__ ) ) . '/';
			$this->starter_plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				$php_version = phpversion();
				if ( version_compare( $php_version, '5.4.0', '<' ) ) {
					if ( session_id() === '' && ! headers_sent() ) {
						session_start();
					}
				} else {
					if ( session_status() === PHP_SESSION_NONE && session_id() === '' && ! headers_sent() ) {
						session_start();
					}
				}
			}

			$last_updated_blocked_domains = get_option( 'ig_es_last_updated_blocked_domains', 0 );
			if ( 0 == $last_updated_blocked_domains || ( ( time() - $last_updated_blocked_domains ) > ( 7 * DAY_IN_SECONDS ) ) ) {
				Email_Subscribers_Utils::get_managed_domains_from_ig();
			}

			// ES integration include
			if ( is_file( $this->starter_plugin_path . '/starter-class-es-integrations.php' ) ) {
				include_once  $this->starter_plugin_path . '/starter-class-es-integrations.php' ;
				new ES_Integrations();
			}

			add_action( 'wp_enqueue_scripts', array( &$this, 'es_starter_load_scripts_styles' ), 12, 2 );
			add_action( 'admin_menu', array( &$this, 'remove_submenus' ) );

			add_action( 'ig_es_after_form_fields', array( &$this, 'es_embed_captcha' ), 10, 1 );
			//Intermediate unsubscribe page
			add_action( 'ig_es_update_subscriber', array( &$this, 'ig_es_intermediate_unsubscribe_page' ), 10, 1 );

			add_filter( 'ig_es_validate_subscribers_data', array( &$this, 'es_validate_captcha' ) );
			add_filter( 'ig_es_show_wp_cron_notice', array( &$this, 'ig_es_show_wp_cron_notice' ) );

			//Add Mailers
			add_filter( 'ig_es_mailers', array( &$this, 'starter_mailers' ), 10 );
			add_filter( 'ig_es_accessible_sub_menus', array( &$this, 'get_accessible_submenus' ), 10, 1 );
			add_filter( 'ig_es_can_access', array( &$this, 'can_access_menu' ), 10, 2 );

			//add_filter( 'ig_es_email_sending_limit', array( &$this, 'es_get_email_sending_limit' ), 10, 2 );
			//add_filter( 'ig_es_email_sending_limit', array( &$this, 'es_bypass_cron_request' ), 10, 2 );

			//add_action( 'ig_es_after_settings_save', array( &$this, 'es_send_cron_data' ), 10, 1 );

			add_filter( 'ig_es_registered_settings', array( &$this, 'es_add_settings_fields' ), 20, 2 );
			add_filter( 'ig_es_before_save_settings', array( &$this, 'es_modify_settings' ), 10, 2 );

			//add_filter( 'ig_es_util_data', array( &$this, 'es_add_starter_task' ), 10, 2 );
			add_filter( 'ig_es_template_thumbnail', array( &$this, 'es_add_starter_badge' ), 10, 2 );

			//added user permission tab
			add_filter( 'ig_es_settings_tabs', array( &$this, 'es_user_permission_tab' ) );

			add_filter( 'ig_es_email_templates', array( &$this, 'es_get_starter_templates' ), 10, 1 );

			// Filter to add Starter Workflow Triggers
			add_filter( 'ig_es_workflow_triggers', array( &$this, 'register_workflow_triggers' ) );

			// Filter to add Starter Workflow Required Data Types
			add_filter( 'ig_es_data_types_includes', array( &$this, 'register_workflow_data_types' ) );

			add_action( 'ig_es_add_additional_options', array( &$this, 'display_captcha' ), 10, 1 );

			// Filter to register services offered in starter version.
			add_filter( 'ig_es_services', array( &$this, 'register_services' ) );
			
			// Filter to get plugin plan
			add_filter( 'ig_es_plan', array( &$this, 'get_plan' ), 11 );
		}

		/**
		 * Show cron notice?
		 *
		 * @param $show_notice
		 *
		 * @return bool
		 *
		 * @sinc 4.0.0
		 */
		public function ig_es_show_wp_cron_notice( $show_notice ) {
			$show_notice = false;

			return $show_notice;
		}

		/**
		 * Get Email Templates
		 *
		 * @param $templates
		 *
		 * @return mixed
		 *
		 * @since 4.0.0
		 *
		 * @modify 4.3.1
		 */
		public function es_get_starter_templates( $templates = array() ) {
			$template_files = glob( untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/*.php' );

			if ( is_array( $template_files ) && count( $template_files ) ) {

				foreach ( $template_files as $file ) {
					if ( is_file( $file ) && is_admin() ) {
						$file_name = basename( $file, '.php' );

						$templates[ $file_name ] = include $file;
					}
				}
			}

			return $templates;
		}

		/**
		 * Embed Captcha
		 *
		 * @since 4.0.0
		 *
		 * @modified 4.4.7 Enable Captcha with the form level
		 */
		public function es_embed_captcha( $data = array() ) {

			$form_html = '';

			$captcha = ! empty( $data['captcha'] ) ? $data['captcha'] : 'no';

			if ( 'yes' === $captcha ) {
				$form_html .= $this->get_captcha();
			}

			$allowed_html_tags = ig_es_allowed_html_tags_in_esc();

			echo wp_kses( $form_html , $allowed_html_tags );
		}

		/**
		 * Get Captcha
		 *
		 * @return string
		 *
		 * @since 4.0.0
		 */
		public function get_captcha() {
			if ( empty( $_SESSION['captcha'] ) ) {
				$_SESSION['captcha'] = array();
			}
			$captcha_html = '';
			$random_a     = rand( 1, 9 );
			$random_b     = rand( 1, 9 );

			//Create Unique key for every captcha created for a form, using WP generate password function.
			$es_captcha_key = wp_generate_password( 10, false );

			$_SESSION['captcha'][ $es_captcha_key ] = $random_a + $random_b;

			$captcha_html .= "<div class='es_captcha es-field-wrap'><label>" . __( 'Are You a Human?', 'email-subscribers' ) . $random_a . ' + ' . $random_b . ' = ';
			$captcha_html .= "<br /><input class='es_form_field es_captcha_input' name='es_captcha' type='number'>";
			$captcha_html .= "<input class='es_form_field es_captcha_key' value='" . $es_captcha_key . "' name='es_captcha_key' type='hidden'></label>";
			$captcha_html .= '</div>';

			return $captcha_html;
		}

		/**
		 * Validate Captcha
		 *
		 * @param $data
		 *
		 * @return array
		 *
		 * @since 4.0.0
		 */
		public function es_validate_captcha( $data ) {

			$response = array( 'status' => 'SUCCESS' );

			$form_id   = ! empty( $data['esfpx_form_id'] ) ? $data['esfpx_form_id'] : 0;
			$form_type = ! empty( $data['form_type'] ) ? $data['form_type'] : '';

			$enable_captcha = ES_Common::get_captcha_setting( $form_id );

			// Check if captcha validation is enabled and it is not an external subscription form.
			if ( 'yes' === $enable_captcha && 'external' !== $form_type ) {

				$es_captcha_key = ! empty( $data['esfpx_es_captcha_key'] ) ? $data['esfpx_es_captcha_key'] : '';

				if ( ! empty( $es_captcha_key ) && ! empty( $_SESSION['captcha'][ $es_captcha_key ] ) && $_SESSION['captcha'][ $es_captcha_key ] == $data['esfpx_es_captcha'] ) {
					$response['status'] = 'SUCCESS';
				} else {
					$response = array( 'status' => 'ERROR', 'message' => 'es_invalid_captcha' );

					$response['captchaHtml'] = $this->get_captcha();
				}
			}

			return $response;
		}

		/**
		 * Load JS/ CSS
		 *
		 * @since 3.5.x
		 */
		public function es_starter_load_scripts_styles() {
			wp_register_script( 'es_starter_main_js', $this->starter_plugin_url . 'assets/js/starter-main.js', array( 'jquery' ), ES_PLUGIN_VERSION, true );
			wp_enqueue_script( 'es_starter_main_js' );
			wp_register_style( 'es_starter_main_css', $this->starter_plugin_url . 'assets/css/starter-main.css', array(), ES_PLUGIN_VERSION, 'all' );
			wp_enqueue_style( 'es_starter_main_css' );
		}

		/**
		 * Bypass Cron Request
		 *
		 * @param $es_process_request
		 *
		 * @return bool
		 *
		 * @since 4.0.0
		 */
		public function es_bypass_cron_request( $es_process_request ) {
			if ( ! empty( $_GET['es_pro'] ) && 'true' !== $_GET['es_pro'] ) {
				$es_process_request = false;
			}

			return $es_process_request;
		}

		/**
		 * Get Email Sending Limit
		 *
		 * @param $es_email_limit
		 *
		 * @return mixed
		 *
		 * @since 4.0.0
		 */
		public function es_get_email_sending_limit( $es_email_limit ) {
			$es_email_limit = ! empty( $_SERVER['HTTP_X_ES_EMAIL_SENDING_LIMIT'] ) ? sanitize_text_field( $_SERVER['HTTP_X_ES_EMAIL_SENDING_LIMIT'] ) : $es_email_limit;

			return $es_email_limit;
		}

		/**
		 * Add Starter settings
		 *
		 * @param $fields
		 *
		 * @return mixed
		 *
		 * @sinc 4.0.0
		 */
		public function es_add_settings_fields( $fields ) {

			$fields['email_sending']['ig_es_cronurl']['desc'] = sprintf( __( '<span class="es-send-success es-icon"></span> We will take care of it. You don\'t need to visit this URL manually.', 'email-subscribers' ) );

			// Add SMTP Settings
			$smtp_sub_fields = array(
				'ig_es_smtp_host' => array(
					'type'         => 'text',
					'options'      => false,
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => '',
					'id'           => 'ig_es_mailer_settings[smtp][smtp_host]',
					'name'         => __( 'SMTP Host', 'email-subscribers' ),
					'desc'         => '',
					'class'        => 'smtp'
				),

				'ig_es_smtp_encryption' => array(
					'type'         => 'select',
					'options'      => array( 'none' => __( 'None', 'email-subscribers' ), 'ssl' => __( 'SSL', 'email-subscribers' ), 'tls' => __( 'TLS', 'email-subscribers' ) ),
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => '',
					'id'           => 'ig_es_mailer_settings[smtp][smtp_encryption]',
					'name'         => __( 'Encryption', 'email-subscribers' ),
					'desc'         => '',
					'class'        => 'smtp'
				),

				'ig_es_smtp_port' => array(
					'type'         => 'text',
					'options'      => false,
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => '',
					'id'           => 'ig_es_mailer_settings[smtp][smtp_port]',
					'name'         => __( 'SMTP Port', 'email-subscribers' ),
					'desc'         => '',
					'class'        => 'smtp'
				),

				'ig_es_smtp_authentication' => array(
					'type'         => 'select',
					'options'      => array( 'no' => __( 'No', 'email-subscribers' ), 'yes' => __( 'Yes', 'email-subscribers' ) ),
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => '',
					'id'           => 'ig_es_mailer_settings[smtp][smtp_authentication]',
					'name'         => __( 'Authentication', 'email-subscribers' ),
					'desc'         => '',
					'class'        => 'smtp'
				),

				'ig_es_smtp_username' => array(
					'type'         => 'text',
					'options'      => false,
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => '',
					'id'           => 'ig_es_mailer_settings[smtp][smtp_username]',
					'name'         => __( 'SMTP Username', 'email-subscribers' ),
					'desc'         => '',
					'class'        => 'smtp'
				),

				'ig_es_smtp_password' => array(
					'type'         => 'password',
					'options'      => false,
					'placeholder'  => '',
					'supplemental' => '',
					'default'      => '',
					'id'           => 'ig_es_mailer_settings[smtp][smtp_password]',
					'name'         => __( 'SMTP Password', 'email-subscribers' ),
					'desc'         => '',
					'class'        => 'smtp'
				),

			);
			$fields['email_sending']['ig_es_mailer_settings']['sub_fields'] = array_merge( $fields['email_sending']['ig_es_mailer_settings']['sub_fields'], $smtp_sub_fields );

			$fake_domains['ig_es_enable_known_attackers_domains'] = array(
				'id'      => 'ig_es_enable_known_attackers_domains',
				'name'    => __( 'Block known attackers', 'email-subscribers' ),
				'info'    => __( 'Stop known spam bot attacker domains from signing up. Keeps this list up-to-date with Icegram servers.', 'email-subscribers' ),
				'type'    => 'checkbox',
				'default' => 'yes'
			);

			$managed_blocked_domains['ig_es_enable_disposable_domains'] = array(
				'id'      => 'ig_es_enable_disposable_domains',
				'name'    => __( 'Block temporary / fake emails', 'email-subscribers' ),
				'info'    => __( 'Plenty of sites provide disposable / fake / temporary email addresses. People use them when they don\'t want to give you their real email. Block these to keep your list clean. Automatically updated.', 'email-subscribers' ),
				'type'    => 'checkbox',
				'default' => 'yes'
			);

			//add captcha setting
			$field_captcha['enable_captcha'] = array(
				'id'      => 'ig_es_enable_captcha',
				'name'    => __( 'Enable Captcha', 'email-subscribers' ),
				'info'    => __( 'Set default captcha option for new forms', 'email-subscribers' ),
				'type'    => 'checkbox',
				'default' => 'no'
			);

			$fields['security_settings'] = array_merge( $fields['security_settings'], $fake_domains, $managed_blocked_domains, $field_captcha );

			$fields['user_roles'] = array(
				'ig_es_user_roles' => array(
					'id'   => 'ig_es_user_roles',
					'name' => '',
					'type' => 'html',
					'html' => $this->render_user_permissions_settings_fields()
				)
			);

			$field_comment_consent                         = array();
			$field_comment_consent['ig_es_opt_in_consent'] = array(
				'id'         => 'ig_es_opt_in_consent',
				'name'       => __( 'Comment opt-in consent', 'email-subscribers' ),
				'info'       => __( 'This will show up at comment form next to consent checkbox.', 'email-subscribers' ),
				'sub_fields' => array(
					'ig_es_show_opt_in_consent' => array(
						'id'      => 'ig_es_show_opt_in_consent',
						'name'    => __( 'Comment opt-in Consent', 'email-subscribers' ),
						'type'    => 'checkbox',
						'default' => 'yes'
					),
					'ig_es_opt_in_consent_text' => array(
						'type'         => 'textarea',
						'options'      => false,
						'placeholder'  => __( 'Opt-in consent text', 'email-subscribers' ),
						'supplemental' => '',
						'default'      => '',
						'id'           => 'ig_es_opt_in_consent_text',
						'name'         => __( 'Opt-in consent text', 'email-subscribers' ),
					),
				)
			);

			$fields['general'] = ig_es_array_insert_after( $fields['general'], 'ig_es_track_utm', $field_comment_consent );

			//option to show integrmediate unsubscribe page
			$field_unsub['ig_es_intermediate_unsubscribe_page'] = array(
				'id'      => 'ig_es_intermediate_unsubscribe_page',
				'name'    => __( 'Allow user to select list(s) while unsubscribe', 'email-subscribers' ),
				'info'    => '',
				'type'    => 'checkbox',
				'default' => 'no'
			);

			$fields['general'] = array_slice( $fields['general'], 0, 7, true ) + $field_unsub + array_slice( $fields['general'], 7, count( $fields['general'] ) - 7, true );

			return $fields;
		}

		/**
		 * Modify User Roles settings
		 *
		 * @param $options
		 *
		 * @return mixed
		 *
		 * @since 4.2.0
		 */
		public function es_modify_settings( $options ) {
			$option_fields = array(
				'ig_es_enable_captcha',
				'ig_es_enable_known_attackers_domains',
				'ig_es_enable_disposable_domains',
				'ig_es_intermediate_unsubscribe_page',
				'ig_es_show_opt_in_consent'
			);

			foreach ( $option_fields as $option ) {
				if ( ! isset( $options[ $option ] ) ) {
					$options[ $option ] = 'no';
				}
			}

			//save admin disabled settings
			$options['ig_es_user_roles']['audience']['administrator']  = 'yes';
			$options['ig_es_user_roles']['forms']['administrator']     = 'yes';
			$options['ig_es_user_roles']['reports']['administrator']   = 'yes';
			$options['ig_es_user_roles']['sequences']['administrator'] = 'yes';
			$options['ig_es_user_roles']['campaigns']['administrator'] = 'yes';
			$options['ig_es_user_roles']['workflows']['administrator'] = 'yes';

			return $options;
		}

		/**
		 * Add Tasks to run on server
		 *
		 * @param $data
		 *
		 * @return mixed
		 *
		 * @since 3.5.0
		 */
		public function es_add_starter_task( $data ) {
			$meta            = ! empty( $data['campaign_id'] ) ? ES()->campaigns_db->get_campaign_meta_by_id( $data['campaign_id'] ) : '';
			$data['html']    = $data['content'];
			$data['css']     = ! empty( $meta['es_custom_css'] ) ? $meta['es_custom_css'] : get_post_meta( $data['tmpl_id'], 'es_custom_css', true );
			$data['tasks'][] = 'css-inliner';

			return $data;
		}

		/**
		 * Add Starter badge
		 *
		 * @param $es_template_thumbnail
		 *
		 * @return string
		 *
		 * @sinc 4.1.0
		 */
		public function es_add_starter_badge( $es_template_thumbnail ) {
			$es_template_thumbnail = ( $es_template_thumbnail ) ? $es_template_thumbnail . '<img style="vertical-align:top;margin-left:-2em;height:25px;width:25px" src="' . $this->starter_plugin_url . 'assets/images/ribbon.png"/>' : $es_template_thumbnail;

			return $es_template_thumbnail;
		}

		/**
		 * Add User Roles tab in settings
		 *
		 * @param $es_settings_tabs
		 *
		 * @return mixed
		 *
		 * @since 4.2.0
		 */
		public function es_user_permission_tab( $es_settings_tabs ) {
			$es_settings_tabs['user_roles'] = array( 'icon' => '<svg class="w-6 h-6 inline -mt-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>', 'name' => __( 'User Roles', 'email-subscribers' ) );

			return $es_settings_tabs;
		}

		/**
		 * Render User Permission Settings
		 *
		 * @return false|string
		 *
		 * @since 4.2.0
		 */
		public function render_user_permissions_settings_fields() {
			$wp_roles   = new WP_Roles();
			$roles      = $wp_roles->get_names();
			$user_roles = get_option( 'ig_es_user_roles' );
			ob_start();
			?>
			<table class="min-w-full rounded-lg">
				<thead>
				<tr class="bg-gray-100 leading-4 text-gray-500 tracking-wider">
					<th class="pl-10 py-4 text-left font-semibold text-sm"><?php echo esc_html__( 'Roles', 'email-subscribers' ); ?></th>
					<th class="px-2 py-4 text-center font-semibold text-sm"><?php echo esc_html__( 'Audience', 'email-subscribers' ); ?></th>
					<th class="px-2 py-4 text-center font-semibold text-sm"><?php echo esc_html__( 'Forms', 'email-subscribers' ); ?></th>
					<th class="px-2 py-4 text-center font-semibold text-sm"><?php echo esc_html__( 'Campaigns', 'email-subscribers' ); ?></th>
					<th class="px-2 py-4 text-center font-semibold text-sm"><?php echo esc_html__( 'Reports', 'email-subscribers' ); ?></th>
					<th class="px-2 py-4 text-center font-semibold text-sm"><?php echo esc_html__( 'Sequences', 'email-subscribers' ); ?></th>
					<th class="px-2 py-4 text-center font-semibold text-sm"><?php echo esc_html__( 'Workflows', 'email-subscribers' ); ?></th>
				</tr>
				</thead>
				<tbody class="bg-white">
				<?php 
				foreach ( $roles as $key => $value ) {
					$disabled = ( 'administrator' == $key ) ? 'disabled' : '';
					?>
					<tr class="border-b border-gray-200">
						<td class="pl-8 py-4 ">
							<div class="flex items-center">
								<div class="flex-shrink-0">
									<span class="text-sm leading-5 font-medium text-center text-gray-800"><?php echo esc_html( $value ); ?></span>
								</div>
							</div>
						</td>
						<td class="whitespace-no-wrap text-center">
							<input type="checkbox" name="ig_es_user_roles[audience][<?php echo esc_attr( $key ); ?>]" <?php echo esc_attr( $disabled ); ?><?php ! empty( $user_roles['audience'][ $key ] ) ? checked( 'yes', $user_roles['audience'][ $key ] ) : ''; ?> value="yes" class=" form-checkbox text-indigo-600">
						</td>
						<td class="whitespace-no-wrap text-center">
							<input type="checkbox" name="ig_es_user_roles[forms][<?php echo esc_attr( $key ); ?>]" <?php echo esc_attr( $disabled ); ?><?php ! empty( $user_roles['forms'][ $key ] ) ? checked( 'yes', $user_roles['forms'][ $key ] ) : ''; ?> value="yes" class=" form-checkbox text-indigo-600">
						</td>
						<td class="whitespace-no-wrap text-center">
							<input type="checkbox" name="ig_es_user_roles[campaigns][<?php echo esc_attr( $key ); ?>]" <?php echo esc_attr( $disabled ); ?><?php ! empty( $user_roles['campaigns'][ $key ] ) ? checked( 'yes', $user_roles['campaigns'][ $key ] ) : ''; ?> value="yes" class=" form-checkbox text-indigo-600">
						</td>
						<td class="whitespace-no-wrap text-center">
							<input type="checkbox" name="ig_es_user_roles[reports][<?php echo esc_attr( $key ); ?>]" <?php echo esc_attr( $disabled ); ?><?php ! empty( $user_roles['reports'][ $key ] ) ? checked( 'yes', $user_roles['reports'][ $key ] ) : ''; ?> value="yes" class=" form-checkbox text-indigo-600">
						</td>
						<td class="whitespace-no-wrap text-center">
							<input type="checkbox" name="ig_es_user_roles[sequences][<?php echo esc_attr( $key ); ?>]" <?php echo esc_attr( $disabled ); ?><?php ! empty( $user_roles['sequences'][ $key ] ) ? checked( 'yes', $user_roles['sequences'][ $key ] ) : ''; ?> value="yes" class=" form-checkbox text-indigo-600">
						</td>
						<td class="whitespace-no-wrap text-center">
							<input type="checkbox" name="ig_es_user_roles[workflows][<?php echo esc_attr( $key ); ?>]" <?php echo esc_attr( $disabled ); ?><?php ! empty( $user_roles['workflows'][ $key ] ) ? checked( 'yes', $user_roles['workflows'][ $key ] ) : ''; ?> value="yes" class=" form-checkbox text-indigo-600">
						</td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>

			<?php
			$html = ob_get_clean();

			return $html;
		}

		/**
		 * Remove ES Submenus based on permission
		 *
		 * @since 4.2.0
		 */
		public function remove_submenus() {
			$user_roles         = get_option( 'ig_es_user_roles', array() );
			$current_user       = wp_get_current_user();
			$current_user_roles = $current_user->roles;

			if ( empty( $user_roles ) ) {
				return;
			}

			// Is Administrator? Enable everything.
			if ( in_array( 'administrator', $current_user_roles ) ) {
				return;
			} else {

				// If not administrator? Remove settings menu
				// Currently, we are showing settings only to administrator
				remove_submenu_page( 'es_dashboard', 'es_settings' );
			}

			$enable_es_menu = false;
			$sub_menus      = array(

				'campaigns' => array(
					array( 'menu_slug' => 'es_dashboard', 'submenu_slug' => 'es_campaigns' ),
					array( 'menu_slug' => 'es_dashboard', 'submenu_slug' => 'es_template_preview' ),
					array( 'menu_slug' => 'es_dashboard', 'submenu_slug' => 'es_notifications' ),
					array( 'menu_slug' => 'es_dashboard', 'submenu_slug' => 'es_newsletters' )
				),

				'forms' => array(
					array( 'menu_slug' => 'es_dashboard', 'submenu_slug' => 'es_forms' )
				),

				'audience' => array(
					array( 'menu_slug' => 'es_dashboard', 'submenu_slug' => 'es_subscribers' ),
					array( 'menu_slug' => 'es_dashboard', 'submenu_slug' => 'es_lists' )
				),

				'reports'   => array(
					array( 'menu_slug' => 'es_dashboard', 'submenu_slug' => 'es_reports' )
				),
				'sequences' => array(
					array( 'menu_slug' => 'es_dashboard', 'submenu_slug' => 'es_sequences' )
				)
			);

			foreach ( $sub_menus as $key => $sub_menu ) {
				if ( ! empty( $user_roles[ $key ] ) && count( array_intersect( $current_user_roles, array_keys( $user_roles[ $key ] ) ) ) <= 0 ) {
					if ( is_array( $sub_menu ) && count( $sub_menu ) > 0 ) {
						foreach ( $sub_menu as $data ) {
							remove_submenu_page( $data['menu_slug'], $data['submenu_slug'] );
						}
					}
				} else {
					$enable_es_menu = true;
				}
			}

			// Don't have access to even a single submenu?
			// Remove ES main menu as well.
			if ( false == $enable_es_menu ) {
				remove_menu_page( 'es_dashboard' );
			}

		}

		/**
		 * Allow user to select the list from which they want to unsubscribe
		 *
		 * @since 4.2.0
		 */
		public function ig_es_intermediate_unsubscribe_page( $contact_id = 0 ) {
			$ig_es_intermediate_unsubscribe_page = get_option( 'ig_es_intermediate_unsubscribe_page', 'no' );

			// Enable?
			if ( 'yes' === $ig_es_intermediate_unsubscribe_page && ! empty( $contact_id ) ) {
				global $wp;
				$get    = ig_es_get_request_data();
				$action = home_url( add_query_arg( $get, $wp->request ) );
				$action = add_query_arg( 'list_selected', 1, $action );

				$lists = ES()->lists_contacts_db->get_list_ids_by_contact( $contact_id );

				$list_contact_status_map = ES()->lists_contacts_db->get_list_contact_status_map( $contact_id );
				if ( ! empty( $lists ) && ! empty( $list_contact_status_map ) && in_array( 'subscribed', $list_contact_status_map ) ) {
					?>
					<script type="text/javascript">
						function validateForm() {
							var checkboxs = document.getElementsByClassName("ig_es_list_checkbox");
							var okay = false;
							for (var i = 0, l = checkboxs.length; i < l; i++) {
								if (checkboxs[i].checked) {
									okay = true;
									break;
								}
							}
							if (!okay) {
								alert("Please check a checkbox");
								return false;
							}
						}

						function checkAll(ele) {
							var checkboxes = document.getElementsByTagName('input');
							if (ele.checked) {
								for (var i = 0; i < checkboxes.length; i++) {
									if (checkboxes[i].type == 'checkbox') {
										checkboxes[i].checked = true;
									}
								}
							} else {
								for (var i = 0; i < checkboxes.length; i++) {
									console.log(i)
									if (checkboxes[i].type == 'checkbox') {
										checkboxes[i].checked = false;
									}
								}
							}
						}

					</script>
					<style type="text/css">
						.ig_es_form_wrapper {
							width: 30%;
							margin: 0 auto;
							border: 2px #e8e3e3 solid;
							padding: 0.9em;
							border-radius: 5px;
						}

						.ig_es_form_heading {
							font-size: 1.3em;
							line-height: 1.5em;
							margin-bottom: 0.5em;
						}

						.ig_es_list_checkbox {
							margin-right: 0.5em;
						}

						.ig_es_submit {
							color: #FFFFFF !important;
							border-color: #03a025 !important;
							background: #03a025 !important;
							box-shadow: 0 1px 0 #03a025;
							font-weight: bold;
							height: 2.4em;
							line-height: 1em;
							cursor: pointer;
							border-width: 1px;
							border-style: solid;
							-webkit-appearance: none;
							border-radius: 3px;
							white-space: nowrap;
							box-sizing: border-box;
							font-size: 1em;
							padding: 0 2em;
							margin-top: 1em;
						}

						.ig_es_submit:hover {
							color: #FFF !important;
							background: #0AAB2E !important;
							border-color: #0AAB2E !important;
						}

						.ig_es_form_wrapper hr {
							display: block;
							height: 1px;
							border: 0;
							border-top: 1px solid #ccc;
							margin: 1em 0;
							padding: 0;
						}

					</style>
					<div class="ig_es_form_wrapper">
						<form action="<?php echo esc_attr( $action ); ?>" method="post" id="ig_es_unsubscribe_list_form" onsubmit="return validateForm()">
							<?php wp_nonce_field( 'ig-es-unsubscribe-nonce', 'ig_es_unsubscribe_nonce' ); ?>
							<div class="ig_es_form_heading"><?php echo esc_html__( 'Select your preference', 'email-subscribers' ); ?></div>
							<label><input class="ig_es_list_checkbox" type="checkbox" onchange="checkAll(this)"><?php echo esc_html__( 'Unsubscribe from all the lists', 'email-subscribers' ); ?></input></label><br/>
							<hr>
							<?php
							foreach ( $lists as $list ) {
								if ( array_key_exists( $list, $list_contact_status_map ) && 'subscribed' == $list_contact_status_map[ $list ] ) {
									$list_name = ES()->lists_db->get_list_name_by_id( $list );
									echo '<label><input class="ig_es_list_checkbox" type="checkbox" name="unsubscribe_lists[]" value="' . esc_attr( $list ) . '">' . esc_html( $list_name ) . '</input></label><br/>';
								}
							}
							?>
							<input type="hidden" name="submitted" value="submitted">
							<input class="ig_es_submit" type="submit">
						</form>
					</div>
					<?php
					die();
				} else {
					return;
				}
			}
		}

		/**
		 * Add Support For SMTP
		 *
		 * @param $mailers
		 *
		 * @return mixed
		 *
		 * @since 4.2.0
		 */
		public function starter_mailers( $mailers ) {
			$mailers['smtp'] = array( 'name' => 'SMTP', 'logo' => ES_PLUGIN_URL . 'lite/admin/images/smtp.png' );

			return $mailers;
		}

		/**
		 * Check whether user hae permission to access
		 *
		 * @param $can_access
		 * @param $page
		 *
		 * @return bool
		 *
		 * @since 4.2.2
		 */
		public function can_access_menu( $can_access, $page ) {
			$accessible_menus = $this->get_accessible_submenus();

			if ( in_array( $page, $accessible_menus ) ) {
				$can_access = true;
			}

			return $can_access;
		}

		/**
		 * Get accessible submenus
		 *
		 * @param $menus
		 *
		 * @return array
		 *
		 * @since 4.2.2
		 */
		public function get_accessible_submenus( $menus = array() ) {
			$permissions        = get_option( 'ig_es_user_roles', array() );
			$current_user       = wp_get_current_user();
			$current_user_roles = $current_user->roles;

			if ( empty( $permissions ) ) {
				return $menus;
			}

			if ( count( $permissions ) > 0 ) {
				foreach ( $permissions as $menu => $permission ) {
					if ( count( array_intersect( $current_user_roles, array_keys( $permission ) ) ) > 0 ) {
						$menus[] = $menu;
					}
				}

				$menus = array_unique( $menus );
			}

			return $menus;
		}

		/**
		 * Register workflow triggers
		 *
		 * @param array $workflow_triggers
		 *
		 * @return array $workflow_triggers
		 *
		 * @since 4.4.1
		 */
		public function register_workflow_triggers( $workflow_triggers = array() ) {

			global $ig_es_tracker;

			if ( empty( $workflow_triggers ) || ! is_array( $workflow_triggers ) ) {
				$workflow_triggers = array();
			}

			$workflow_triggers['ig_es_comment_added'] = 'ES_Trigger_Comment_Added';

			$active_plugins = $ig_es_tracker::get_active_plugins();

			// Show only if WooCommerce is installed & activated
			$woocommerce_plugin = 'woocommerce/woocommerce.php';
			if ( in_array( $woocommerce_plugin, $active_plugins, true ) ) {
				$workflow_triggers['ig_es_wc_order_completed'] = 'ES_Trigger_WC_Order_Completed';
				$workflow_triggers['ig_es_wc_order_created']   = 'ES_Trigger_WC_Order_Created';
			}

			// Show only if EDD is installed & activated
			$edd_plugin = 'easy-digital-downloads/easy-digital-downloads.php';
			if ( in_array( $edd_plugin, $active_plugins, true ) ) {
				$workflow_triggers['ig_es_edd_complete_purchase'] = 'ES_Trigger_EDD_Purchase_Completed';
			}

			// Show only if Contact Form 7 is installed & activated
			$cf7_plugin = 'contact-form-7/wp-contact-form-7.php';
			if ( in_array( $cf7_plugin, $active_plugins, true ) ) {
				$workflow_triggers['ig_es_cf7_submitted'] = 'ES_Trigger_CF7_Submitted';
			}

			// Show only if WP Forms plugin is installed & activated
			$wpforms_lite_plugin = 'wpforms-lite/wpforms.php';
			$wpforms_plugin      = 'wpforms/wpforms.php';
			if ( in_array( $wpforms_lite_plugin, $active_plugins, true ) || in_array( $wpforms_plugin, $active_plugins, true ) ) {
				$workflow_triggers['ig_es_wpforms_submitted'] = 'ES_Trigger_WPForms_Submitted';
			}

			// Show only if Give is installed & activated
			$give_plugin = 'give/give.php';
			if ( in_array( $give_plugin, $active_plugins, true ) ) {
				$workflow_triggers['ig_es_give_donation_made'] = 'ES_Trigger_Give_Donation_Made';
			}

			// Show only if Ninja Forms is installed & activated
			$ninja_forms_plugin = 'ninja-forms/ninja-forms.php';
			if ( in_array( $ninja_forms_plugin, $active_plugins, true ) ) {
				$workflow_triggers['ig_es_ninja_forms_submitted'] = 'ES_Trigger_Ninja_Forms_Submitted';
			}

			//Show only if Gravity Form is installed & activated
			$gravity_forms_plugin = 'gravityforms/gravityforms.php';
			if ( in_array( $gravity_forms_plugin, $active_plugins, true ) ) {
				$workflow_triggers['ig_es_gravity_forms_submitted'] = 'ES_Trigger_Gravity_Forms_Submitted';
			}

			//Show only if Forminator Plugin is installed & activated
			$forminator_plugin = 'forminator/forminator.php';
			if ( in_array( $forminator_plugin, $active_plugins, true ) ) {
				$workflow_triggers['ig_es_forminator_forms_submitted'] = 'ES_Trigger_Forminator_Forms_Submitted';
			}

			return $workflow_triggers;
		}

		/**
		 * Register workflow data types
		 *
		 * @param array $data_types
		 *
		 * @return array $data_types
		 *
		 * @since 4.4.1
		 */
		public function register_workflow_data_types( $data_types = array() ) {

			global $ig_es_tracker;

			if ( empty( $data_types ) || ! is_array( $data_types ) ) {
				$data_types = array();
			}

			$active_plugins = $ig_es_tracker::get_active_plugins();

			$data_types['comment'] = 'ES_Data_Type_Comment';

			// Add only if WooCommerce is installed & activated
			$woocommerce_plugin = 'woocommerce/woocommerce.php';
			if ( in_array( $woocommerce_plugin, $active_plugins, true ) ) {
				$data_types['wc_order'] = 'ES_Data_Type_WC_Order';
			}

			// Add only if EDD is installed & activated
			$edd_plugin = 'easy-digital-downloads/easy-digital-downloads.php';
			if ( in_array( $edd_plugin, $active_plugins, true ) ) {
				$data_types['edd_payment'] = 'ES_Data_Type_EDD_Payment';
			}

			// Add only if Contact Form 7 is installed & activated
			$cf7_plugin = 'contact-form-7/wp-contact-form-7.php';
			if ( in_array( $cf7_plugin, $active_plugins, true ) ) {
				$data_types['cf7_data'] = 'ES_Data_Type_CF7_Data';
			}

			// Add only if WP Forms is installed & activated
			$wpforms_lite_plugin = 'wpforms-lite/wpforms.php';
			$wpforms_plugin      = 'wpforms/wpforms.php';
			if ( in_array( $wpforms_lite_plugin, $active_plugins, true ) || in_array( $wpforms_plugin, $active_plugins, true ) ) {
				$data_types['wpforms_data'] = 'ES_Data_Type_WPForms_Data';
			}

			// Add only if Ninja Forms is installed & activated
			$ninja_forms_plugin = 'ninja-forms/ninja-forms.php';
			if ( in_array( $ninja_forms_plugin, $active_plugins, true ) ) {
				$data_types['ninja_forms_data'] = 'ES_Data_Type_Ninja_Forms_Data';
			}

			// Add only if Give is installed & activated
			$give_plugin = 'give/give.php';
			if ( in_array( $give_plugin, $active_plugins, true ) ) {
				$data_types['give_data'] = 'ES_Data_Type_Give_Data';
			}

			// Add only if Gravity Form is installed & activated
			$gravity_forms_plugin = 'gravityforms/gravityforms.php';
			if ( in_array( $gravity_forms_plugin, $active_plugins, true ) ) {
				$data_types['gravity_forms_data'] = 'ES_Data_Type_Gravity_Forms_Data';
			}

			// Add only if Forminator plugin is installed & activated
			$forminator_plugin = 'forminator/forminator.php';
			if ( in_array( $forminator_plugin, $active_plugins, true ) ) {
				$data_types['forminator_forms_data'] = 'ES_Data_Type_Forminator_Forms_Data';
			}

			return $data_types;
		}

		public function display_captcha( $form_data ) {
			?>
			<div class="flex border-b border-gray-100 ">
				<div class="w-2/5 mr-16">
					<div class="flex flex-row w-full">
						<div class="flex w-2/4">
							<div class="ml-4 mr-8 mr-4 pt-4 mb-2">
								<label for="tag-link"><span class="block ml-4 pr-4 text-sm font-medium text-gray-600 pb-2"><?php echo esc_html__( 'Enable Captcha' ); ?></span></label>
								<p class="italic text-xs text-gray-400 mt-2 ml-4 leading-snug pb-4"><?php echo esc_html__( 'Show a captcha to protect from bot signups.', 'email-subscribers' ); ?></p>
							</div>
						</div>
						<div class="flex">
							<div class="ml-16 mb-4 mr-4 mt-12">
								<label for="captcha" class=" inline-flex items-center cursor-pointer">
									<span class="relative">
										<input id="captcha" type="checkbox" class=" absolute es-check-toggle opacity-0 w-0 h-0" name="form_data[captcha]" value="yes" 
										<?php 
										if ( isset( $form_data['captcha'] ) && 'yes' === $form_data['captcha'] ) {
											echo 'checked="checked"';
										}

										?>
										 />

										<span class="es-mail-toggle-line"></span>
										<span class="es-mail-toggle-dot"></span>
									</span>
								</label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php 
		}

		/**
		 * Register services in the starter version
		 * 
		 * @param array $services
		 * 
		 * @return array $services
		 * 
		 * @since 4.6.1
		 */
		public function register_services( $services = array() ) {

			$starter_services = array(
				'css_inliner',
				'es_cron',
			);
			
			$services = array_merge( $services, $starter_services );

			return $services;
		}

		/**
		 * Method to get plugin plan
		 * 
		 * @param string $plan
		 * 
		 * @return string $plan
		 * 
		 * @since 4.6.1
		 */
		public function get_plan( $plan = '' ) {

			$plan = 'starter';
			
			return $plan;
		}
	}

	new Email_Subscribers_Starter();
}
