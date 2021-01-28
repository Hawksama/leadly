<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'Email_Subscribers_Utils' ) ) {
	class Email_Subscribers_Utils {
		public static $es_services;

		public function __construct() {
			// Start-IG-Code.
			// Include load file.
			if ( ! class_exists( 'Icegram_Upgrade_v_0_4_7' ) ) {
				require_once ES_PLUGIN_DIR . 'inc/ig-upgrade-v-0.4.7.php';
			}

			$file                 = ES_PLUGIN_FILE;
			$sku                  = 'IGA-EMAIL-SUBSCRIBERS';
			$prefix               = 'ig-email-subscribers';
			$plugin_name          = 'Email Subscribers Pro';
			$text_domain          = 'email-subscribers';
			$documentation_link   = 'https://www.icegram.com/knowledgebase_category/email-subscribers-pro/';
			$pricing_link         = 'https://www.icegram.com/email-subscribers-pricing/';
			$plugin_dashboard_url = admin_url( 'admin.php?page=es_dashboard' );

			new Icegram_Upgrade_v_0_4_7( $file, $sku, $prefix, $plugin_name, $text_domain, $documentation_link, $pricing_link, $plugin_dashboard_url );
			// End-IG-Code.
			if ( ! defined( 'ES_API_URL' ) ) {
				define( 'ES_API_URL', 'https://api.icegram.com/' );
			}

			$es_data = get_option( '_icegram_connector_data' );

			self::$es_services = ( ! empty( $es_data['es_services'] ) ) ? $es_data['es_services'] : '';
			add_filter( 'ig-email-subscribers_is_page_for_notifications', array( $this, 'es_show_notification' ), 10, 2 );
			//add_filter( 'es_after_process_template_body', array( $this, 'es_send_request' ), 10 );
			add_filter( 'ig_es_blocked_domains', array( $this, 'get_blocked_domains' ), 10, 1 );
		}

		public function es_show_notification( $is_page, $upgrader ) {

			if ( ES()->is_es_admin_screen() ) {

				$current_page = ig_es_get_request_data( 'page' );

				// Show account connection notification only if onboarding is completed or the user is not on the dashboard page.
				if ( 'es_dashboard' === $current_page && ! IG_ES_Onboarding::is_onboarding_completed() ) {
					return $is_page;
				}

				return true;
			}

			return $is_page;
		}

		public function es_send_request( $data ) {
			if ( ! empty( self::$es_services ) && ! in_array( 'inliner', self::$es_services ) ) {
				return $data;
			}

			$data = apply_filters( 'ig_es_util_data', $data );

			if ( ! empty( $data['content'] ) ) {
				$options  = array(
					'timeout' => 15,
					'method'  => 'POST',
					'body'    => $data
				);
				$url      = ES_API_URL . 'email/process/';
				$response = wp_remote_post( $url, $options );
				if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
					$data = $response['body'];
					if ( 'error' != $data ) {
						$data = json_decode( $data, true );
					}
				}

			}

			return $data;
		}

		public static function es_list_cleanup( $subscribers ) {
			if ( ! empty( self::$es_services ) && ! in_array( 'list_cleanup', self::$es_services ) ) {
				return $subscribers;
			}
			$url = 'https://ves.putler.com/email/batch/verify/';

			//TODO :: Timeout
			foreach ( $subscribers as $email ) {
				$data[] = array( 'email' => $email );

			}
			$data_to_send['lists']       = json_encode( $data );
			$data_to_send['mode']        = 'full';
			$data_to_send['autoCorrect'] = 0;

			$options  = array(
				'timeout' => 50,
				'method'  => 'POST',
				'body'    => $data_to_send
			);
			$response = wp_remote_post( $url, $options );
			if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
				$data = $response['body'];
				if ( 'error' != $data ) {
					$data = json_decode( $data, true );

					return $data;
				}

			}

		}

		public static function es_get_spam_score( $data ) {
			$result = array();
			if ( ! empty( self::$es_services ) && ! in_array( 'list_cleanup', self::$es_services ) ) {
				return $data;
			}
			$data['options'] = 'full';
			$url             = ES_API_URL . 'email/process/';
			$options         = array(
				'timeout' => 50,
				'method'  => 'POST',
				'body'    => $data
			);
			$response        = wp_remote_post( $url, $options );
			if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
				$data = $response['body'];
				if ( 'error' != $data ) {
					$data = json_decode( $data, true );

					return $data;
				}

			}

			return $result;
		}

		public static function es_send_cron_data( $es_cron_url_data ) {
			$es_cron_url_data['tasks'][0] = 'store-cron';
			$url                          = ES_API_URL . 'store/cron/';

			$options = array(
				'timeout'   => 15,
				'method'    => 'POST',
				'body'      => $es_cron_url_data,
				'sslverify' => false
			);

			$response = wp_remote_post( $url, $options );

			if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
				$data = $response['body'];
				if ( 'error' != $data ) {
					$data = json_decode( $data, true );

					return $data['status'];
				}
			}
		}


		public function get_blocked_domains( $domains ) {

			$blocked_domains = get_option( 'ig_es_managed_blocked_domains', array() );

			$domains = array_merge( $domains, $blocked_domains );

			return $domains;

		}

		public static function get_managed_domains_from_ig() {

			$url = 'https://ves.putler.com/domains/blocked/:type';

			$is_enable_known_attackers    = get_option( 'ig_es_enable_known_attackers_domains', 'yes' );
			$is_enable_disposable_domains = get_option( 'ig_es_enable_disposable_domains', 'yes' );

			$type = '';
			if ( 'yes' === $is_enable_known_attackers && 'yes' === $is_enable_disposable_domains ) {
				$type = 'fake_disposable';
			} elseif ( 'yes' === $is_enable_known_attackers ) {
				$type = 'fake';
			} elseif ( 'yes' === $is_enable_disposable_domains ) {
				$type = 'disposable';
			}


			if ( ! empty( $type ) ) {

				$url      = str_replace( ':type', $type, $url );
				$options  = array(
					'timeout' => 50,
					'method'  => 'GET'
				);
				$response = wp_remote_get( $url, $options );

				if ( 200 == wp_remote_retrieve_response_code( $response ) ) {

					$data = json_decode( $response['body'], true );

					if ( 'SUCCESS' == $data['status'] ) {

						$domains = $data['data'];

						update_option( 'ig_es_managed_blocked_domains', $domains );
						update_option( 'ig_es_last_updated_blocked_domains', time() );
					}
				}
			}

		}

	}
}
new Email_Subscribers_Utils();
