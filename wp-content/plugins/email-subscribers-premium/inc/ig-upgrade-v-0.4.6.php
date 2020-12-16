<?php

class Icegram_Upgrade_v_0_4_6 {

	var $base_name;
	var $check_update_timeout;
	var $last_checked;
	var $plugin_data;
	var $sku;
	var $installed_version;
	var $live_version;
	var $slug;
	var $name;
	var $documentation_link;
	var $prefix;
	var $text_domain;

	function __construct( $file, $sku = '', $prefix, $plugin_name, $text_domain, $documentation_link, $pricing_link, $plugin_dashboard_url ) {
		global $ig_installed_addons;
		$this->check_update_timeout = ( 24 * 60 * 60 ); // 24 hours

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$this->plugin_data          = get_plugin_data( $file );
		$this->base_name            = plugin_basename( $file );
		$this->slug                 = dirname( $this->base_name );
		$this->name                 = $plugin_name;
		$this->sku                  = ( is_dir( untrailingslashit( plugin_dir_path( $file ) ) . '/max' ) ) ? $sku . '-MAX' : ( ( is_dir( untrailingslashit( plugin_dir_path( $file ) ) . '/pro' ) ) ? $sku . '-PRO' : ( ( is_dir( untrailingslashit( plugin_dir_path( $file ) ) . '/starter' ) ) ? $sku . '-STARTER' : $sku . '-PLUS' ) );
		$this->documentation_link   = $documentation_link;
		$this->pricing_link         = $pricing_link;
		$this->plugin_dashboard_url = $plugin_dashboard_url;
		$this->prefix               = $prefix;
		$this->text_domain          = $text_domain;
		$this->client_id            = 'pclmX42WIYvaBOeUzuExHsCf0iHh2HKEA3wff0KZ';
		$this->client_secret        = 'dVUzMUJKYBdi7AVrr4gV6duN12lR17ztOA98HkIS';

		$ig_installed_addons[ $this->slug ] = $sku;

		add_site_option( $this->prefix . '_last_checked', '' );
		add_site_option( $this->prefix . '_download_url', '' );
		add_site_option( $this->prefix . '_installed_version', '' );
		add_site_option( $this->prefix . '_live_version', '' );

		if ( empty( $this->last_checked ) ) {
			$this->last_checked = (int) get_site_option( $this->prefix . '_last_checked' );
		}

		if ( get_site_option( $this->prefix . '_installed_version' ) != $this->plugin_data ['Version'] ) {
			update_site_option( $this->prefix . '_installed_version', $this->plugin_data ['Version'] );
		}

		if ( ( get_site_option( $this->prefix . '_live_version' ) == '' ) || version_compare( get_site_option( $this->prefix . '_live_version' ), get_site_option( $this->prefix . '_installed_version' ), '<' ) ) {
			update_site_option( $this->prefix . '_live_version', $this->plugin_data['Version'] );
		}


		add_filter( 'plugins_api', array( $this, 'overwrite_wp_plugin_api_for_plugin' ), 10, 3 );
		add_filter( 'site_transient_update_plugins', array( $this, 'overwrite_site_transient' ), 10, 3 );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'overwrite_site_transient' ), 10, 3 );

		add_action( 'admin_notices', array( $this, 'connect_icegram_notification' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
		add_action( 'wp_ajax_' . $this->prefix . '_get_authorization_code', array( $this, 'get_authorization_code' ) );
		add_action( 'wp_ajax_' . $this->prefix . '_disconnect_icegram', array( $this, 'disconnect_icegram' ) );
		add_action( 'admin_footer', array( $this, 'add_plugin_style_script' ) );
		if ( has_action( 'wp_ajax_get_icegram_updates', array( $this, 'get_icegram_updates' ) ) === false ) {
			add_action( 'wp_ajax_get_icegram_updates', array( $this, 'get_icegram_updates' ) );
		}
		//ajax method for get data
		add_action( 'admin_footer', array( $this, 'request_icegram_data_js' ) );
		add_action( 'wp_ajax_' . $this->prefix . '_save_data', array( $this, 'save_data' ) );
		add_action( 'wp_ajax_' . $this->prefix . '_save_token', array( $this, 'save_token' ) );
		//add action to manual request data
		add_filter( 'plugin_action_links_' . plugin_basename( $file ), array( $this, 'add_plugin_extra_link' ) );
		add_action( 'admin_init', array( &$this, 'request_data' ) );
	}


	function overwrite_site_transient( $plugin_info, $transient = 'update_plugins', $force_check_updates = false ) {

		if ( empty( $plugin_info->checked ) ) {
			return $plugin_info;
		}

		$time_not_changed = isset( $this->last_checked ) && $this->check_update_timeout > ( time() - $this->last_checked );

		if ( $force_check_updates || ! $time_not_changed ) {
			// $this->check_for_updates();
			$this->request_icegram_data();
			$this->last_checked = time();
			update_site_option( $this->prefix . '_last_checked', $this->last_checked );
		}

		$plugin_base_file  = $this->base_name;
		$live_version      = get_site_option( $this->prefix . '_live_version' );
		$installed_version = get_site_option( $this->prefix . '_installed_version' );

		if ( version_compare( $live_version, $installed_version, '>' ) ) {
			$plugin_info->response [ $plugin_base_file ]              = new stdClass();
			$plugin_info->response [ $plugin_base_file ]->slug        = substr( $plugin_base_file, 0, strpos( $plugin_base_file, '/' ) );
			$plugin_info->response [ $plugin_base_file ]->new_version = $live_version;
			$plugin_info->response [ $plugin_base_file ]->url         = 'https://www.icegram.com';
			$plugin_info->response [ $plugin_base_file ]->package     = get_site_option( $this->prefix . '_download_url' );
		}

		return $plugin_info;
	}

	function overwrite_wp_plugin_api_for_plugin( $api = false, $action = '', $args = '' ) {

		if ( empty( $args->slug ) || $args->slug != $this->slug ) {
			return $api;
		}

		if ( 'plugin_information' == $action || false === $api || $_REQUEST ['plugin'] == $args->slug ) {
			$api                = new stdClass();
			$api->name          = $this->name;
			$api->version       = get_site_option( $this->prefix . '_live_version' );
			$api->download_link = get_site_option( $this->prefix . '_download_url' );
		}

		return $api;
	}

	// IG storeconnector
	function enqueue_scripts_styles() {
		if ( ! wp_script_is( 'jquery' ) ) {
			wp_enqueue_script( 'jquery' );
		}

		add_thickbox();
	}

	function connect_icegram_notification() {
		if ( did_action( 'connect_icegram_com_notification' ) > 0 ) {
			return;
		}

		global $wpdb, $pagenow;

		$ig_is_page_for_notifications = apply_filters( $this->prefix . '_is_page_for_notifications', false, $this );

		if ( $ig_is_page_for_notifications || $pagenow == 'plugins.php' ) {

			?>
			<script type="text/javascript">
				jQuery(function () {
					jQuery(window).on('load', function () {
						var has_class = jQuery('body').hasClass('plugins-php');
						if (!has_class) {
							jQuery('body').addClass('plugins-php');
						}
					});
				});
			</script>
			<?php

			$license_key  = $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE '%_license_key%' AND option_value != '' LIMIT 1" );
			$access_token = get_option( '_icegram_connector_access_token' );
			$token_expiry = get_option( '_icegram_connector_token_expiry' );
			$is_connected = get_option( '_icegram_connected', 'no' );
			$auto_connect = get_option( '_icegram_auto_connected', 'no' );

			$protocol = 'https';

			$url = $protocol . '://www.icegram.com/oauth/authorize?response_type=code&client_id=' . $this->client_id . '&redirect_uri=' . add_query_arg( array( 'action' => $this->prefix . '_get_authorization_code' ), admin_url( 'admin-ajax.php' ) );

			if ( empty( $token_expiry ) || time() > $token_expiry ) {

				?>
				<div id="connect_icegram_com" style="display: none;">
					<div style="width: 100% !important; height: 80% !important;" class="connect_icegram_child">
						<div id="connect_icegram_com_step_1" style="background: #FFFFFF;
																		padding: 10px 10px 0 25px;
																		position: absolute;
																		top: 42%;
																		left: 50%;
																		transform: translate(-50%, -50%);
																		width: inherit;
																		display: flex">
							<div style="width:35%" class="ig-onboarding-image">
								<img class="ig-onboarding-connect-account-image" width="160" src="<?php echo esc_url( plugins_url( 'images/ig-account-connect.svg', __FILE__ ) ); ?>"/>
							</div>
							<div  style="width:65%">
								<h2 class="connect_icegram_message"><?php esc_html_e( 'You are one step away from using ', $this->text_domain ); ?></h2>
								<h1 class="connect_icegram_message" style="color: #4c51bf;"><?php echo sprintf( __( '%s', $this->text_domain ), $this->name ); ?></h1>
								
								<div class="ig-onboarding">
									<div class="ig-onboarding-content">
										<h3 class="ig-why-connect"><?php echo __( 'Connect with <b style="color:#3653A7;">Icegram.com account</b> now.', $this->text_domain ); ?></h3>
										<ol style="line-height: 1.4rem;padding-left: 2rem;padding-top: 1rem;">
											<li>
												<span class="dashicons dashicons-yes"></span>
												<span class="ig-connect-features-text"><?php esc_html_e( 'Unlock all plugin features', $this->text_domain ); ?>
												</span>
          									</li>
											<li>
												<span class="dashicons dashicons-yes"></span>
												<span class="ig-connect-features-text"><?php esc_html_e( 'Automatic Updates', $this->text_domain ); ?>
												</span>
											</li>
											<li>
												<span class="dashicons dashicons-yes"></span>
												<span class="ig-connect-features-text"><?php esc_html_e( 'Priority support', $this->text_domain ); ?>
												</span>
											</li>
											<li>
												<span class="dashicons dashicons-yes"></span>
												<span class="ig-connect-features-text">
													<?php esc_html_e( 'Easy one time setup. Takes less than one minute', $this->text_domain ); ?>
												</span>
											</li>
										</ol>
									</div>
									<div class="ig-onboarding-privacy">
										<p style="color: #4a5568;"><input type="checkbox" id="ig_connector_privacy" name="ig_connector_privacy" value="yes" checked="checked">&nbsp;
										<?php 
										echo esc_html__( 'I agree to the',
													$this->text_domain ) . '&nbsp;<a style="color: #4c51bf;" class="ig-onboarding-terms-privacy" href="https://www.icegram.com/privacy-policy/?utm_source=ig-upgrade&utm_medium=in_app_banner&utm_campaign=privacy-policy" target="_blank">' . esc_html__( 'terms & privacy policy', $this->text_domain ) . '</a>' . esc_html__( '*', $this->text_domain ); 
										?>
													</p>

										<button class="ig-connect-flat-button"><?php esc_html_e( 'Connect ', $this->text_domain ); ?><span class="dashicons dashicons-arrow-right-alt"></span></button>
									</div>
								</div>
							</div>
						</div>
						<div id="connect_icegram_com_step_2" style="display: none; width: 100%; height: 100%;">
							<iframe src="" style="width: 100%; height: 100%;padding:0 1.7rem 0 1.7rem"></iframe>
						</div>
						<style type="text/css" media="screen">
							#connect_icegram_com{
								font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
							}
							#TB_ajaxContent {
								position: relative;
								width:100% !important;
								padding: 0 1.9em 0 1.9em;
								height:470px !important;
							}

							.connect_icegram_child {
								position: absolute;
								top: 45%;
								left: 50%;
								transform: translate(-50%, -50%);
							}

							#connect_icegram_com_step_1 .dashicons-yes {
								color: #27ae60;
								font-size: 2.2em;
								margin-right: 5px;
								vertical-align: text-bottom;
							}

							#connect_icegram_com_step_1 a {
								display: inline-block;
								cursor: pointer;
								margin: 1em 0em 0em 0em;
								text-decoration: underline;
							}

							#connect_icegram_com_step_1 ol {
								width: auto;
								margin-left: -3em !important;
								display: inline-block;
								list-style: none;
							}

							#connect_icegram_com_step_1 ol li {
								text-align: left;
							}

							#connect_icegram_com_step_1 .ig-connect-flat-button {
								position: relative;
								vertical-align: top;
								height: 2.2em;
								padding: 0 1em;
								font-size: 1.5em;
								margin-top: 2em;
								color: white;
								text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
								background: #27ae60;
								border: 0;
								border-radius: 5px;
								border-bottom: 2px solid #219d55;
								cursor: pointer;
								-webkit-box-shadow: inset 0 -2px #219d55;
								box-shadow: inset 0 -2px #219d55;
							}
							#connect_icegram_com_step_1 .ig-connect-flat-button:hover{
								background-color: #48bb78;
							}

							#connect_icegram_com_step_1 .ig-connect-flat-button:active {
								top: 1px;
								outline: none;/
								box-shadow: 0 0 0 3px rgba(72, 187, 120, 0.5);
							}


							.ig-onboarding {
								width: 100%;
								height: 18em;
								padding: 1em 0em;
							}

							.ig-onboarding-image {
								width: 30%;
								float: left;
								padding-left: 5px;
							}

							.ig-onboarding-connect-account-image {
								/*margin-top: 4.5em;*/
								background-size: contain;
								width:85%;
								height:100%;
							}

							.ig-why-connect {
								text-align: left;
								/*margin-left: 0.5em;*/
								margin-top: 0em;
								font-size: 0.9rem;
								color: #4a5568;
								font-weight: 400;

							}

							.ig-disabled {
								cursor: not-allowed !important;
							}

							.tb-close-icon{
								outline: none !important;
								overflow: hidden;
								border: none !important;
								right:1.2px;
								top:1px;
								border-radius: 0.5rem;
							}

							.tb-close-icon:hover {
								background-color: #edf2f7;
								border-radius: 0.5rem;
							}

							#TB_title{
								background: none;
								border:none;
								/*overflow: hidden;*/
							}
							#TB_window{
								margin-left: -305px !important;
							    width: 610px !important;
							    margin-top: -235px !important;
							    border-radius: 0.375rem;
							    height: 470px !important;
							    top: 50% !important;
							}
							.connect_icegram_message{
								line-height: 1.5rem;
								font-size: 1.1rem;
								color: #718096;
								font-weight: 600;
							}
							.ig-connect-features{
								height: 1.2rem;
								width: 1.2rem;
								color: #38a169;
								display: inline;
							}
							.ig-connect-features-text{
								font-size:0.9rem;
								color: #4a5568;
								font-weight: 500;
								padding-left: 0.2rem;

							}
							.ig-onboarding-privacy{
								padding-left: 0.2rem;
							}

						</style>
						<script type="text/javascript">
							var jQuery = parent.jQuery;
							jQuery('#connect_icegram_com_step_1').on('click', 'button', function () {
								jQuery('#connect_icegram_com_step_2 iframe').attr('src', '<?php echo $url; ?>');
								jQuery('#connect_icegram_com_step_1').fadeOut();
								jQuery('#connect_icegram_com_step_2').fadeIn();
							});
							jQuery('#ig_connector_privacy').on('change', function () {
								if (jQuery(this).is(':checked')) {
									jQuery('#connect_icegram_com_step_1').find('button').removeClass('ig-disabled').attr('disabled', false);
								} else {
									jQuery('#connect_icegram_com_step_1').find('button').addClass('ig-disabled').attr('disabled', true);
								}
							});
						</script>
					</div>
				</div>
				<?php
				do_action( 'connect_icegram_com_notification' );
			}

			if ( $is_connected === 'yes' && $auto_connect != 'yes' ) {
				update_option( '_icegram_connected', 'no' );
			}
		}
	}

	function get_authorization_code() {
		if ( empty( $_REQUEST['code'] ) ) {
			die( __( 'Code not received', $this->text_domain ) );
		}
		$args    = array(
			'grant_type'   => 'authorization_code',
			'code'         => $_REQUEST['code'],
			'redirect_uri' => add_query_arg( array( 'action' => $this->prefix . '_get_authorization_code' ), admin_url( 'admin-ajax.php' ) )
		);
		$success = $this->get_tokens( $args );

		$parsed_site_url = wp_parse_url( site_url() );
		$parsed_domain   = ( false !== $parsed_site_url ) ? $parsed_site_url['host'] : 'localhost';
		$nonce           = md5( $parsed_domain . $this->prefix . substr( $this->client_id, 6, 10 ) . substr( $args['code'], 11, 20 ) . substr( $this->client_secret, 16, 10 ) );
		$protocol        = 'https';

		$url = $protocol . '://www.icegram.com/oauth/token';
		?>
		<style type="text/css" media="screen">
			.ig-onboarding {
				position: relative;
				text-align: center;
				width: 100%;
				height: 100%;
			}

			.ig-onboarding-success {
				position: absolute;
				top: 50%;
				left: 50%;
				transform: translate(-50%, -50%);
				width:80%;
			}

			.ig-onboarding-success-image {
				width: 37%;
			}

			.ig-onboarding-success-message-1 {
				font-size: 2em;
				color: green;
			}

			.ig-onboarding-success-message-2 {
				font-size: 1.3em;
				color: #5a67d8;
			}
		</style>
		<div class="ig-onboarding">
			<div class="ig-onboarding-success">
				<img class="ig-onboarding-success-image" src="<?php echo esc_url( plugins_url( 'images/ig-account-connect-success.svg', __FILE__ ) ); ?>"/>
				<h3 class="ig-onboarding-success-message-1"><?php esc_html_e( 'Congratulations!', $this->text_domain ); // phpcs:ignore ?></h3>
				<p class="ig-onboarding-success-message-2"><?php esc_html_e( 'Account connected successfully.', $this->text_domain ); // phpcs:ignore ?></p>
			</div>
		</div>
		<script type="text/javascript">
			var jQuery = parent.jQuery;
			<?php if ( false === $success ) { ?>

			jQuery.ajax({
				url: '<?php echo esc_url( $url ); ?>',
				method: 'POST',
				dataType: 'json',
				crossDomain: true,
				xhrFields: {
					withCredentials: true
				},
				headers: {
					'Authorization': 'Basic <?php echo base64_encode( $this->client_id . ':' . $this->client_secret ); // WPCS: XSS ok. ?>'
				},
				data: {
					grant_type: '<?php echo $args['grant_type']; // WPCS: XSS ok. ?>',
					code: '<?php echo $args['code']; // WPCS: XSS ok. ?>',
					redirect_uri: '<?php echo $args['redirect_uri']; // WPCS: XSS ok. ?>',
					security: '<?php echo $nonce; // WPCS: XSS ok. ?>'
				},
				success: function (response) {
					if (response != undefined && response != '') {
						if (response.access_token != undefined && response.access_token != '' && response.expires_in != undefined && response.expires_in != '') {
							jQuery.ajax({
								url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
								method: 'POST',
								dataType: 'json',
								data: {
									action: '<?php echo $this->prefix; // WPCS: XSS ok. ?>_save_token',
									access_token: response.access_token,
									expires_in: response.expires_in,
									security: '<?php echo wp_create_nonce( $this->prefix . '-save-token' ); // WPCS: XSS ok. ?>'
								},
								success: function (res) {
									if (res != undefined && res != '' && res.success != undefined && res.success == 'yes') {
										var iframe_dom = jQuery('#connect_icegram_com_step_2 iframe').contents();
										iframe_dom.find('.ig-onboarding').show();
										jQuery('#TB_window').addClass('ig-connector-window');
										jQuery('#TB_window').removeClass('thickbox-loading');
										setTimeout(function () {
											parent.tb_remove();
											parent.location.reload(true);
										}, 5000);
									}
								}
							});
						}
					}
				}
			});
			<?php } else { ?>
			var iframe_dom = jQuery('#connect_icegram_com_step_2 iframe').contents();
			iframe_dom.find('.ig-onboarding').show();
			jQuery('#TB_window').addClass('ig-connector-window');
			jQuery('#TB_window').removeClass('thickbox-loading');
			setTimeout(function () {
				parent.tb_remove();
				parent.location.reload(true);
			}, 5000);
			<?php } ?>
		</script>

		<?php
		die();
	}

	function get_tokens( $args = array() ) {

		if ( empty( $args ) ) {
			return;
		}

		$protocol = 'https';

		$url      = $protocol . '://www.icegram.com/oauth/token';
		$response = wp_remote_post( $url,
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
				),
				'body'    => $args,
			)
		);
		if ( ! is_wp_error( $response ) ) {
			$code    = wp_remote_retrieve_response_code( $response );
			$message = wp_remote_retrieve_response_message( $response );

			if ( $code = 200 && $message = 'OK' ) {
				$body   = wp_remote_retrieve_body( $response );
				$tokens = json_decode( $body );

				if ( ! empty( $tokens ) ) {
					$present      = time();
					$offset       = ( ! empty( $tokens->expires_in ) ) ? $tokens->expires_in : 0;
					$access_token = ( ! empty( $tokens->access_token ) ) ? $tokens->access_token : '';
					$token_expiry = ( ! empty( $offset ) ) ? $present + $offset : $present;
					if ( ! empty( $access_token ) ) {
						update_option( '_icegram_connector_access_token', $access_token );
						update_option( '_icegram_connected', 'yes' );
					}
					if ( ! empty( $token_expiry ) ) {

						update_option( '_icegram_connector_token_expiry', $token_expiry );
					}
				}
			}
		} else {
			//$this->log( 'error', print_r( $response->get_error_messages(), true ) . ' ' . __FILE__ . ' ' . __LINE__ ); // phpcs:ignore
			return false;
		}

		return true;

	}

	/**
	 * Save token received via ajax
	 */
	public function save_token() {
		check_ajax_referer( $this->prefix . '-save-token', 'security' );

		$access_token = ( ! empty( $_POST['access_token'] ) ) ? wp_unslash( $_POST['access_token'] ) : ''; // WPCS: sanitization ok. CSRF ok, input var ok.
		$expires_in   = ( ! empty( $_POST['expires_in'] ) ) ? wp_unslash( $_POST['expires_in'] ) : 0; // WPCS: sanitization ok. CSRF ok, input var ok.

		$present      = time();
		$offset       = $expires_in;
		$token_expiry = ( ! empty( $offset ) ) ? $present + $offset : $present;
		if ( ! empty( $access_token ) ) {
			update_option( '_icegram_connector_access_token', $access_token, 'no' );
			update_option( '_icegram_connected', 'yes', 'no' );
		} else {
			//$this->log( 'error', __( 'Empty access token', $this->text_domain ) . ' ' . __FILE__ . ' ' . __LINE__ ); // phpcs:ignore
		}
		if ( ! empty( $token_expiry ) ) {
			update_option( '_icegram_connector_token_expiry', $token_expiry, 'no' );
		} else {
			//$this->log( 'error', __( 'Empty token expiry', $this->text_domain ) . ' ' . __FILE__ . ' ' . __LINE__ ); // phpcs:ignore
		}

		wp_send_json( array( 'success' => 'yes' ) );

	}

	function get_icegram_updates() {

		check_ajax_referer( 'icegram-update', 'security' );

		if ( empty( $this->last_checked ) ) {
			$icegram_data       = $this->get_icegram_data();
			$this->last_checked = ( ! empty( $icegram_data['last_checked'] ) ) ? $icegram_data['last_checked'] : null;
			if ( empty( $this->last_checked ) ) {
				$this->last_checked           = strtotime( '-1435 minutes' );
				$icegram_data['last_checked'] = $this->last_checked;
				$this->set_icegram_data( $icegram_data );
			}
		}

		$time_not_changed = isset( $this->last_checked ) && $this->check_update_timeout > ( time() - $this->last_checked );

		if ( ! $time_not_changed ) {
			// if ( true ) {
			$this->request_icegram_data();
		}

		wp_send_json( array( 'success' => 'yes' ) );

	}

	function request_icegram_data() {
		$data                        = array();
		$icegram_deactivated_plugins = array();
		$icegram_activated_plugins   = array();
		$icegram_connector_data      = get_option( '_icegram_connector_data' );
		$access_token                = get_option( '_icegram_connector_access_token' );
		if ( empty( $access_token ) ) {
			return;
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		//Check update code merge
		$this->live_version      = get_site_option( $this->prefix . '_live_version' );
		$this->installed_version = get_site_option( $this->prefix . '_installed_version' );
		if ( version_compare( $this->installed_version, $this->live_version, '<=' ) ) {

			$protocol = 'https';
			// $url = $protocol . '://www.icegram.com/wp-json/woocommerce-serial-key/v1/serial-keys';
			// $url      = $protocol . '://www.icegram.com/wp-admin/admin-ajax.php?action=get_access_data';
			$url      = $protocol . '://www.icegram.com/wp-json/custom/v1/get_access_data';
			$args     = array(
				'plugins' => $this->get_environment_details(),
				'sku'     => $this->sku
			);
			$response = wp_remote_get( $url,
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
						'Referer'       => base64_encode( $this->sku . ':' . $this->installed_version . ':' . $this->client_id . ':' . $this->client_secret )
					),
					'body'    => $args,
				)
			);
			if ( ! is_wp_error( $response ) ) {
				$code    = wp_remote_retrieve_response_code( $response );
				$message = wp_remote_retrieve_response_message( $response );

				if ( $code = 200 && $message = 'OK' ) {
					$body          = wp_remote_retrieve_body( $response );
					$response_data = json_decode( $body, true );

					if ( ! empty( $response_data['downloads'] ) ) {
						// $response_data['skus']['last_checked'] = time();
						foreach ( $response_data['downloads'] as $sku => $download ) {
							$live_version = $download['version'];
							$download_url = add_query_arg( 'version', $live_version, $download['download_url'] );
							$sku          = strtolower( str_replace( 'IGA', 'IG', $sku ) );
							update_site_option( $sku . '_download_url', $download_url );
							update_site_option( $sku . '_live_version', $live_version );
						}
						$this->set_icegram_data( $response_data );
					}
				}
			} else {
				update_option( 'ajax_request_icegram_data', 'yes', 'no' );
				// //$this->log( 'error', print_r( $response->get_error_messages(), true ) . ' ' . __FILE__ . ' ' . __LINE__ ); // phpcs:ignore
			}
		}
		//
	}

	function disconnect_icegram() {

		check_ajax_referer( 'disconnect-icegram', 'security' );

		delete_option( '_icegram_connector_data' );
		delete_option( '_icegram_connector_access_token' );
		delete_option( '_icegram_connector_token_expiry' );
		delete_option( '_icegram_connected' );
		delete_option( '_icegram_auto_connected' );

		echo json_encode( array( 'success' => 'yes', 'message' => 'success' ) );
		die();

	}

	public function get_icegram_data() {

		$data = get_option( '_icegram_connector_data', array() );

		$update = false;

		if ( empty( $data[ $this->sku ] ) ) {
			$data[ $this->sku ] = array(
				'installed_version'         => '0',
				'live_version'              => '0',
				'license_key'               => '',
				'changelog'                 => '',
				'due_date'                  => '',
				'download_url'              => '',
				'next_update_check'         => false,
				'upgrade_notices'           => array(),
				'saved_changes'             => 'no',
				'hide_renewal_notification' => 'no',
				'hide_license_notification' => 'no'
			);
			$update             = true;
		}

		if ( empty( $data['last_checked'] ) ) {
			$data['last_checked'] = 0;
			$update               = true;
		}

		if ( empty( $data['login_link'] ) ) {
			$protocol           = 'https';
			$data['login_link'] = $protocol . '://www.icegram.com/my-account';
			$update             = true;
		}

		if ( $update ) {
			update_option( '_icegram_connector_data', $data );
		}

		return $data;

	}

	public function set_icegram_data( $data = array(), $force = false ) {
		if ( $force || ! empty( $data ) ) {
			update_option( '_icegram_connector_data', $data );
		}

	}

	function add_plugin_style_script() {

		global $pagenow;

		$this->add_plugin_style();
		$this->last_checked = ! empty( $this->last_checked ) ? $this->last_checked : strtotime( '-1435 minutes' );
		$time_not_changed   = isset( $this->last_checked ) && $this->check_update_timeout > ( time() - $this->last_checked );
		?>

		<script type="text/javascript">
			jQuery(function () {
				jQuery('a#<?php echo $this->prefix; ?>_disconnect_icegram').on('click', function () {
					var trigger_element = jQuery(this);
					var status_element = jQuery(this).closest('tr');
					status_element.css('opacity', '0.4');
					jQuery.ajax({
						url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
						type: 'post',
						dataType: 'json',
						data: {
							action: '<?php echo $this->prefix; ?>_disconnect_icegram',
							prefix: '<?php echo $this->prefix; ?>',
							security: '<?php echo wp_create_nonce( 'disconnect-icegram' ); ?>'
						},
						success: function (response) {
							status_element.css('opacity', '1');
							trigger_element.text('<?php echo __( 'Disconnected', $this->text_domain ); ?>');
							trigger_element.css({
								'background-color': '#46b450',
								'color': 'white'
							});
							setTimeout(function () {
								location.reload();
							}, 1500);
						}
					});
				});
				<?php if ( ! $time_not_changed ) { ?>
				jQuery(window).on('load', function () {
					jQuery.ajax({
						url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
						type: 'POST',
						dataType: 'json',
						data: {
							'action': 'get_icegram_updates',
							'security': '<?php echo wp_create_nonce( 'icegram-update' ); ?>'
						},
						success: function (response) {
							if (response != undefined && response != '') {
								if (response.success != 'yes') {
									console.log('<?php echo sprintf( __( 'Error at %s', $this->text_domain ), plugin_basename( __FILE__ ) . ':' . __LINE__ ); ?>', response);
								}
							}
						}
					});

				});
				<?php } ?>
				jQuery(window).on('load', function () {
					var iframe_content = jQuery('#connect_icegram_com_div').text();
					iframe_content = (iframe_content != undefined) ? iframe_content.trim() : iframe_content;
					var div_content = jQuery('#connect_icegram_com').html();
					var is_iframe_empty = iframe_content == undefined || iframe_content == '';
					var is_div_empty = div_content == undefined || div_content == '';
					if (iframe_content == 'no_user' || (is_iframe_empty && !is_div_empty)) {
						<?php if ( $pagenow != 'plugins.php' ) { ?>
						tb_show('', "#TB_inline?inlineId=connect_icegram_com&height=550&width=600");
						<?php } ?>
					}
				});

			});
		</script>
		<?php
	}

	function add_plugin_style() {
		?>
		<style type="text/css">
			div#TB_ajaxContent {
				/*overflow: hidden;*/
				/*position: initial;*/
			}

			<?php if ( version_compare( get_bloginfo( 'version' ), '3.7.1', '>' ) ) { ?>
			tr.<?php echo $this->prefix; ?>_license_key .key-icon-column:before {
				content: "\f112";
				display: inline-block;
				-webkit-font-smoothing: antialiased;
				font: normal 1.5em/1 'dashicons';
			}

			tr.<?php echo $this->prefix; ?>_due_date .renew-icon-column:before {
				content: "\f463";
				display: inline-block;
				-webkit-font-smoothing: antialiased;
				font: normal 1.5em/1 'dashicons';
			}

			<?php } ?>
			a#<?php echo $this->prefix; ?>_reset_license,
			a#<?php echo $this->prefix; ?>_disconnect_icegram {
				cursor: pointer;
			}

			a#<?php echo $this->prefix; ?>_disconnect_icegram:hover {
				color: #fff;
				background-color: #dc3232;
			}

			span#<?php echo $this->prefix; ?>_hide_renewal_notification,
			span#<?php echo $this->prefix; ?>_hide_license_notification {
				cursor: pointer;
				float: right;
				opacity: 0.2;
			}
		</style>
		<?php
	}

	/**
	 * Request data via ajax
	 */
	public function request_icegram_data_js() {
		$is_ajax          = get_option( 'ajax_request_icegram_data', 'yes' );
		$time_not_changed = isset( $this->last_checked ) && $this->check_update_timeout > ( time() - $this->last_checked );
		if ( ! empty( $is_ajax ) && 'yes' === $is_ajax && ! $time_not_changed ) {
			if ( ! wp_script_is( 'jquery' ) ) {
				wp_enqueue_script( 'jquery' );
			}

			$access_token = get_option( '_icegram_connector_access_token' );
			if ( empty( $access_token ) ) {
				return;
			}

			$protocol = 'https';
			// $url = $protocol . '://www.icegram.com/wp-json/woocommerce-serial-key/v1/serial-keys';
			// $url      = $protocol . '://www.icegram.com/wp-admin/admin-ajax.php?action=get_access_data';
			$url     = $protocol . '://www.icegram.com/wp-json/custom/v1/get_access_data';
			$plugins = $this->get_environment_details();
			?>
			<script type="text/javascript">
				jQuery(function () {
					jQuery(document).ready(function () {
						jQuery.ajax({
							url: '<?php echo esc_url( $url ); ?>',
							method: 'GET',
							// async: false,
							dataType: 'json',
							crossDomain: true,
							xhrFields: {
								withCredentials: true
							},
							headers: {
								'Authorization': 'Bearer <?php echo $access_token; // WPCS: XSS ok. ?>',
							},
							data: {
								'plugins': '<?php echo wp_json_encode( $plugins ); ?>'
							},
							success: function (response) {
								if (response != undefined && response != '') {
									if (response.downloads != undefined && response.downloads != '') {
										jQuery.ajax({
											url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
											method: 'POST',
											dataType: 'json',
											data: {
												action: '<?php echo $this->prefix; // WPCS: XSS ok. ?>_save_data',
												downloads: response,
												security: '<?php echo wp_create_nonce( $this->prefix . '-save-data' ); // WPCS: XSS ok. ?>'
											},
											success: function (res) {
												if (res != undefined && res != '' && res.success != undefined && res.success == 'yes') {
													// All done.
												}
											}
										});
									}
								}
							}
						});
					});
				});
			</script>
			<?php
		}
	}

	function get_environment_details() {
		$all_plugins                 = get_plugins();
		$all_activated_plugins       = get_option( 'active_plugins' );
		$icegram_deactivated_plugins = $icegram_activated_plugins = array();
		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			$author  = ( ! empty( $plugin_data['Author'] ) ) ? strtolower( $plugin_data['Author'] ) : null;
			$version = ( ! empty( $plugin_data['Version'] ) ) ? $plugin_data['Version'] : '';
			if ( empty( $author ) ) {
				continue;
			}
			if ( in_array( $author, array( 'icegram' ) ) ) {
				if ( in_array( $plugin_file, $all_activated_plugins ) ) {
					$icegram_activated_plugins[ $plugin_file ] = $version;
				} else {
					$icegram_deactivated_plugins[ $plugin_file ] = $version;
				}
			}
		}
		$data = array(
			'activated'   => $icegram_activated_plugins,
			'deactivated' => $icegram_deactivated_plugins
		);

		return $data;
	}

	/**
	 * Save data received via ajax
	 */
	public function save_data() {
		check_ajax_referer( $this->prefix . '-save-data', 'security' );
		$response_data = ( ! empty( $_POST['downloads'] ) ) ? wp_unslash( $_POST['downloads'] ) : ''; // WPCS: sanitization ok. CSRF ok, input var ok.
		if ( ! empty( $response_data['downloads'] ) ) {
			// $response_data['skus']['last_checked'] = time();
			foreach ( $response_data['downloads'] as $sku => $download ) {

				if ( ! empty( $sku ) && ! empty($download['version'])) {
					$live_version = $download['version'];
					$download_url = add_query_arg( 'version', $live_version, $download['download_url'] );
					$sku          = strtolower( str_replace( 'IGA', 'IG', $sku ) );
					update_site_option( $sku . '_download_url', $download_url );
					update_site_option( $sku . '_live_version', $live_version );
				}
			}
			$this->set_icegram_data( $response_data );
			update_option( 'ajax_request_icegram_data', 'no', 'no' );
		}
		wp_send_json( array( 'success' => 'yes' ) );

	}

	// add link for manual update
	function add_plugin_extra_link( $links ) {
		$url          = admin_url( 'plugins.php' ) . '?' . $this->prefix . '_action=request_data';
		$access_token = get_option( '_icegram_connector_access_token' );
		$token_expiry = get_option( '_icegram_connector_token_expiry' );
		$links[]      = '<a href="' . $url . '" >' . __( 'Check for updates', 'email-subscribers' ) . '</a>';
		$links[]      = '<a href="' . $this->documentation_link . '" target="_blank">' . __( 'Docs', 'email-subscribers' ) . '</a>';
		$links[]      = '<a style="color:green;font-weight: bold;" href="' . $this->pricing_link . '" target="_blank">' . __( 'Go Pro', 'email-subscribers' ) . '</a>';
		//if connected
		if ( ! empty( $access_token ) && ! empty( $token_expiry ) && time() <= $token_expiry ) {
			$links[] = '<a id="' . $this->prefix . '_disconnect_icegram' . '">' . __( 'Disconnect from Icegram', 'email-subscribers' ) . '</a>';
		} else {
			$links[] = '<a id="' . $this->prefix . '_connect_icegram' . '" href="' . $this->plugin_dashboard_url . '">' . __( 'Connect to Icegram', 'email-subscribers' ) . '</a>';

		}

		return $links;
	}

	//request data
	function request_data() {
		$option = $this->prefix . '_action';
		if ( isset( $_GET[ $option ] ) && $_GET[ $option ] == 'request_data' ) {
			$this->request_icegram_data();
			//redirect to plugin
			$redirect_url = admin_url( 'plugins.php' );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

}
