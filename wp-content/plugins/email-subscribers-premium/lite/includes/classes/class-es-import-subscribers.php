<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Import_Subscribers {
	/**
	 * ES_Import_Subscribers constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		add_action( 'init', array( &$this, 'init' ) );
	}

	/**
	 * Method to hook ajax handler for import process
	 */
	public function init() {
		if ( is_admin() ) {
			add_action( 'wp_ajax_ig_es_import_subscribers_upload_handler', array( &$this, 'ajax_import_subscribers_upload_handler' ) );
			add_action( 'wp_ajax_ig_es_get_import_data', array( &$this, 'ajax_get_import_data' ) );
			add_action( 'wp_ajax_ig_es_do_import', array( &$this, 'ajax_do_import' ) );
		}
	}

	/**
	 * Import Contacts
	 *
	 * @since 4.0,0
	 *
	 * @modify 4.3.1
	 *
	 * @modfiy 4.4.4 Moved importing code section to maybe_start_import method.
	 */
	public function import_callback() {

		$this->prepare_import_subscriber_form();
	}

	public function prepare_import_subscriber_form() {
		
		global $is_IE, $is_opera;
		
		if ( is_multisite() && ! is_upload_space_available() ) {
			return;
		}

		$max_upload_size = $this->get_max_upload_size();
		$post_params     = array(
			'action'   => 'ig_es_import_subscribers_upload_handler',
			'security' => wp_create_nonce( 'ig-es-admin-ajax-nonce' ),
		);
		
		$upload_action_url = admin_url( 'admin-ajax.php' );
		$plupload_init = array(
			'browse_button'    => 'plupload-browse-button',
			'container'        => 'plupload-upload-ui',
			'drop_element'     => 'drag-drop-area',
			'file_data_name'   => 'async-upload',
			'url'              => $upload_action_url,
			'filters'          => array( 
				'max_file_size' => $max_upload_size . 'b',
				'mime_types'    => array( array( 'extensions' => 'csv' ) ),
			),
			'multipart_params' => $post_params,
		);

		$allowedtags = ig_es_allowed_html_tags_in_esc();
		?>
		<script type="text/javascript">
			let wpUploaderInit = <?php echo wp_json_encode( $plupload_init ); ?>;
		</script>
		<div class="tool-box">
			<div class="meta-box-sortables ui-sortable bg-white shadow-md mt-8 rounded-lg">
				<form class="ml-7 mr-4 text-left py-4 my-2 item-center" method="post" name="form_import_subscribers" id="form_import_subscribers" action="#" enctype="multipart/form-data">
					<div class="step1 flex flex-row">
						<div class="es-import-processing flex w-1/4">
							<div class="ml-6 pt-6">
								<label for="select_csv">
									<span class="block pr-4 text-sm font-medium text-gray-600 pb-1">
										<?php esc_html_e( 'Select CSV file', 'email-subscribers' ); ?>
									</span>
									<p class="italic text-xs font-normal text-gray-400 mt-2 leading-snug">
										<?php 
										/* translators: %s: Max upload size */
										echo sprintf( esc_html__( 'File size should be less than %s', 'email-subscribers' ), esc_html( size_format( $max_upload_size ) ) ); 
										?>
									</p>
									<p class="italic text-xs font-normal text-gray-400 mt-2 leading-snug">
										<?php esc_html_e( 'Check CSV structure', 'email-subscribers' ); ?>
										<a class="font-medium" target="_blank" href="<?php echo esc_attr( plugin_dir_url( __FILE__ ) ) . '../../admin/partials/sample.csv'; ?>"><?php esc_html_e( 'from here', 'email-subscribers' ); ?></a>
									</p>
								</label>
							</div>
						</div>
						<div class="w-3/4 ml-12 my-6 mr-4">			
							<div class="step1-body w-10/12">
								<div class="upload-method">
									<div id="media-upload-error"></div>
									<div id="plupload-upload-ui" class="hide-if-no-js">
										<div id="drag-drop-area">
											<div class="drag-drop-inside">
												<p class="drag-drop-info"><?php esc_html_e( 'Drop your CSV here', 'email-subscribers' ); ?></p>
												<p><?php echo esc_html_x( 'or', 'Uploader: Drop files here - or - Select Files', 'email-subscribers' ); ?></p>
												<p class="drag-drop-buttons"><input id="plupload-browse-button" type="button" value="<?php esc_attr_e( 'Select File', 'email-subscribers' ); ?>" class="button" /></p>
											</div>
										</div>
									</div>

								</div>
							</div>
							<p class="import-status pt-4 pb-1 text-base font-medium text-gray-600 tracking-wide hidden">&nbsp;</p>
							<div id="progress" class="progress hidden w-10/12"><span class="bar" style="width:0%"><span></span></span></div>
						</div>
					</div>
					<div class="step2 w-full overflow-auto mb-6 mr-4 mt-4 border-b border-gray-100">
						<h2 class="import-status text-base font-medium text-gray-600 tracking-wide"></h2>
						<div class="step2-body overflow-auto pb-4"></div>
						<p class="import-instruction text-base font-medium text-yellow-600 tracking-wide"></p>
						<div id="importing-progress" class="importing-progress hidden mb-4 mr-2 text-center"><span class="bar" style="width:0%"><p class="block import_percentage text-white font-medium text-sm"></p></span></div>
					</div>
					<div class="step2-status">
						<div class="step2-status flex flex-row border-b border-gray-100">
							<div class="flex w-1/4">
								<div class="ml-6 pt-6">
									<label for="import_contact_list_status"><span class="block pr-4 text-sm font-medium text-gray-600 pb-2">
										<?php esc_html_e( 'Select status', 'email-subscribers' ); ?> </span>
									</label>
								</div>
							</div>
							<div class="w-3/4 mb-6 mr-4 mt-4">
								<select class="relative form-select shadow-sm border border-gray-400 sm:w-32 lg:w-48 ml-4" name="es_email_status" id="es_email_status">
									<?php 
									$statuses_dropdown 	= ES_Common::prepare_statuses_dropdown_options();
									echo wp_kses( $statuses_dropdown , $allowedtags );
									?>
								</select>
							</div>
						</div>
					</div>
					<div class="step2-list">
						<div class="step2-list flex flex-row border-b border-gray-100">
							<div class="flex w-1/4">
								<div class="ml-6 pt-6">
									<label for="tag-email-group"><span class="block pr-4 text-sm font-medium text-gray-600 pb-2">
										<?php esc_html_e( 'Select list', 'email-subscribers' ); ?></span>
									</label>
								</div>
							</div>
							<div class="w-3/4 mb-6 mr-4 mt-4">
								<?php
								// Allow multiselect for lists field in the pro version by changing list field's class,name and adding multiple attribute.
								if ( ES()->is_pro() ) {
									$select_list_attr  = 'multiple="multiple"';
									$select_list_name  = 'list_id[]';
									$select_list_class = 'ig-es-form-multiselect';
								} else {
									$select_list_attr  = '';
									$select_list_name  = 'list_id';
									$select_list_class = 'form-select';
								}
								?>
								<div class="ml-4">
									<select name="<?php echo esc_attr( $select_list_name ); ?>" id="list_id" class="relative shadow-sm border border-gray-400 sm:w-32 lg:w-48 <?php echo esc_attr( $select_list_class ); ?>" <?php echo esc_attr( $select_list_attr ); ?>>
										<?php 
										$lists_dropdown 	= ES_Common::prepare_list_dropdown_options();
										echo wp_kses( $lists_dropdown , $allowedtags );
										?>
									</select>
								</div>
							</div>
						</div>
						<p style="padding-top:10px;">
						<?php wp_nonce_field( 'import-contacts', 'import_contacts' ); ?>
						<input type="submit" name="submit" class="start-import cursor-pointer ig-es-primary-button px-4 py-2 ml-6 mr-2 my-4" value="<?php esc_html_e( 'Import', 'email-subscribers' ); ?>" />
					</p>
					</div>
					

				</form>
			</div>
			<div class="import-progress">
			</div>
			<!-- <div id="progress" class="progress hidden"><span class="bar" style="width:0%"><span></span></span></div> -->
		</div>
		<?php
	}

	/**
	 * Show import contacts
	 *
	 * @since 4.0.0
	 */
	public function import_subscribers_page() {

		$audience_tab_main_navigation = array();
		$active_tab                   = 'import';
		$audience_tab_main_navigation = apply_filters( 'ig_es_audience_tab_main_navigation', $active_tab, $audience_tab_main_navigation );

		?>

		<div class="max-w-full -mt-3 font-sans">
			<header class="wp-heading-inline">
				<div class="flex">
					<div>
						<nav class="text-gray-400 my-0" aria-label="Breadcrumb">
							<ol class="list-none p-0 inline-flex">
								<li class="flex items-center text-sm tracking-wide">
									<a class="hover:underline " href="admin.php?page=es_subscribers"><?php esc_html_e( 'Audience ', 'email-subscribers' ); ?></a>
									<svg class="fill-current w-2.5 h-2.5 mx-2 mt-mx" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"></path></svg>
								</li>
							</ol>
						</nav>
						<h2 class="-mt-1.5 text-2xl font-medium text-gray-700 sm:leading-7 sm:truncate">
							<?php esc_html_e( 'Import Contacts', 'email-subscribers' ); ?>
						</h2>
					</div>

					<div class="mt-4 ml-2">
						<?php
						ES_Common::prepare_main_header_navigation( $audience_tab_main_navigation );
						?>
					</div>
				</div>
			</header>

			<div><hr class="wp-header-end"></div>
			<?php $this->import_callback(); ?>
		</div>

		<?php
	}

	/**
	 * Get CSV file delimiter
	 *
	 * @param $file
	 * @param int  $check_lines
	 *
	 * @return mixed
	 *
	 * @since 4.3.1
	 */
	public function get_delimiter( $file, $check_lines = 2 ) {

		$file = new SplFileObject( $file );

		$delimiters = array( ',', '\t', ';', '|', ':' );
		$results    = array();
		$i          = 0;
		while ( $file->valid() && $i <= $check_lines ) {
			$line = $file->fgets();
			foreach ( $delimiters as $delimiter ) {
				$regExp = '/[' . $delimiter . ']/';
				$fields = preg_split( $regExp, $line );
				if ( count( $fields ) > 1 ) {
					if ( ! empty( $results[ $delimiter ] ) ) {
						$results[ $delimiter ] ++;
					} else {
						$results[ $delimiter ] = 1;
					}
				}
			}
			$i ++;
		}

		if ( count( $results ) > 0 ) {

			$results = array_keys( $results, max( $results ) );

			return $results[0];
		}

		return ',';

	}

	/**
	 * Method to get max upload size
	 *
	 * @return int $max_upload_size
	 *
	 * @since 4.4.6
	 */
	public function get_max_upload_size() {

		$max_upload_size    = 5242880; // 5MB.
		$wp_max_upload_size = wp_max_upload_size();
		$max_upload_size    = min( $max_upload_size, $wp_max_upload_size );

		return apply_filters( 'ig_es_max_upload_size', $max_upload_size );
	}

	/**
	 * Ajax handler to insert import CSV data into temporary table.
	 * 
	 * @since 4.6.6
	 */
	public function ajax_import_subscribers_upload_handler() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$response = array(
			'success' => false,
		);

		global $wpdb;

		$memory_limit       = @ini_get( 'memory_limit' );
		$max_execution_time = @ini_get( 'max_execution_time' );

		@set_time_limit( 0 );

		if ( (int) $max_execution_time < 300 ) {
			@set_time_limit( 300 );
		}
		if ( (int) $memory_limit < 256 ) {
			// Add filter to increase memory limit
			add_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );

			wp_raise_memory_limit( 'ig_es' );

			// Remove the added filter function so that it won't be called again if wp_raise_memory_limit called later on.
			remove_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );
		}

		if ( isset( $_FILES['async-upload'] ) ) {

			if ( isset( $_FILES['async-upload']['tmp_name'] ) && is_uploaded_file( sanitize_text_field( $_FILES['async-upload']['tmp_name'] ) ) ) {
				$tmp_file = sanitize_text_field( $_FILES['async-upload']['tmp_name'] );
				$raw_data = file_get_contents( $tmp_file );
				$seperator =  $this->get_delimiter( $tmp_file );

				$handle = fopen( $tmp_file, 'r' );
				// Get Headers.
				$headers = array_map( 'trim', fgetcsv( $handle, 0, $seperator ) );

				// Remove BOM characters from the first item.
				if ( isset( $headers[0] ) ) {
					$headers[0] = ig_es_remove_utf8_bom( $headers[0] );
				}

				$contain_headers = true;
				if ( ! empty( $headers ) ) {
					foreach ( $headers as $header ) {
						if ( ! empty( $header ) && is_email( $header ) ) {
							$contain_headers = false;
							break;
						}
					}
				}
				fclose( $handle );

				if ( function_exists( 'mb_convert_encoding' ) ) {
					$raw_data = mb_convert_encoding( $raw_data, 'UTF-8', mb_detect_encoding( $raw_data, 'UTF-8, ISO-8859-1', true ) );
				}
			}
		}

		if ( empty( $raw_data ) ) {
			wp_send_json( $response );
		}

		$raw_data = ( trim( str_replace( array( "\r", "\r\n", "\n\n" ), "\n", $raw_data ) ) );

		if ( function_exists( 'mb_convert_encoding' ) ) {
			$encoding = mb_detect_encoding( $raw_data, 'auto' );
		} else {
			$encoding = 'UTF-8';
		}
		
		$lines      = explode( "\n", $raw_data );
		if ( $contain_headers ) {
			array_shift( $lines );
		}

		$batch_size = min( 500, max( 200, round( count( $lines ) / 200 ) ) ); // Each entry in temporary import table will have this much of subscribers data
		$parts      = array_chunk( $lines, $batch_size );
		$partcount  = count( $parts );

		$bulkimport = array(
			'imported'        => 0,
			'errors'          => 0,
			'encoding'        => $encoding,
			'parts'           => $partcount,
			'lines'           => count( $lines ),
			'separator'       => $seperator,
			'contain_headers' => $contain_headers,
		);
		
		if ( $contain_headers ) {
			$bulkimport['headers'] = $headers;
		}

		$this->do_cleanup();

		$identifier = uniqid();
		$response['identifier'] = $identifier;
		for ( $i = 0; $i < $partcount; $i++ ) {

			$part = $parts[ $i ];

			$new_value = base64_encode( serialize( $part ) );

			$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}ig_temp_import (data, identifier) VALUES (%s, %s)", $new_value, $identifier ) );
		}

		$response['success']     = true;
		$response['memoryusage'] = size_format( memory_get_peak_usage( true ), 2 );
		update_option( 'ig_es_bulk_import', $bulkimport, 'no' );

		wp_send_json( $response );
	}

	/**
	 * Ajax handler to get import data from temporary table.
	 * 
	 * @since 4.6.6
	 */
	public function ajax_get_import_data() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		$response = array(
			'success' => false,
		);

		global $wpdb;

		$identifier = '';
		if ( isset( $_POST['identifier'] ) ) {
			$identifier =  sanitize_text_field( $_POST['identifier'] );
		}

		if ( ! empty( $identifier ) ) {
			
			$response['identifier'] = $identifier;
			$response['data'] = get_option( 'ig_es_bulk_import' );

			// get first and last entry
			$entries = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
					(SELECT data FROM {$wpdb->prefix}ig_temp_import WHERE identifier = %s ORDER BY ID ASC LIMIT 1) AS first, (SELECT data FROM {$wpdb->prefix}ig_temp_import WHERE identifier = %s ORDER BY ID DESC LIMIT 1) AS last",
					$identifier,
					$identifier
				)
			);

			$first = unserialize( base64_decode( $entries->first ) );
			$last  = unserialize( base64_decode( $entries->last ) );

			$data         = str_getcsv( $first[0], $response['data']['separator'], '"' );
			$cols         = count( $data );
			$contactcount = $response['data']['lines'];

			$fields     = array(
				'email'      => esc_html__( 'Email', 'email-subscribers' ),
				'first_name' => esc_html__( 'First Name', 'email-subscribers' ),
				'last_name'  => esc_html__( 'Last Name', 'email-subscribers' ),
				'first_last' => esc_html__( '(First Name) (Last Name)', 'email-subscribers' ),
				'last_first' => esc_html__( '(Last Name) (First Name)', 'email-subscribers' ),
			);

			$html      = '<div class="flex flex-row mb-6">
			<div class="es-import-processing flex w-1/4">
			<div class="ml-6 mr-2 pt-6">
			<label for="select_csv">
			<span class="block pr-4 text-sm font-medium text-gray-600 pb-1">'
			. esc_html__( 'Select columns for mapping', 'email-subscribers' ) .
			'</span>
			<p class="italic text-xs font-normal text-gray-400 mt-2 leading-snug">'
			. esc_html__( 'Define which column represents which field', 'email-subscribers' ) . '

			</p>

			</label>
			</div>
			</div>' ;
			$html      .= '<div class="w-3/4 mx-4 border-b border-gray-200 shadow rounded-lg"><table class="w-full bg-white rounded-lg shadow overflow-hidden ">';
			$html      .= '<thead><tr class="border-b border-gray-200 bg-gray-50 text-left text-sm leading-4 font-medium text-gray-500 tracking-wider"><th class="pl-3 py-4" style="width:20px;">#</th>';
			$emailfield = false;
			$headers = array();
			if ( $response['data']['contain_headers'] ) {
				$headers = $response['data']['headers'];
			}
			for ( $i = 0; $i < $cols; $i++ ) {
				$is_email  = is_email( trim( $data[ $i ] ) );
				$select  = '<select class="form-select font-normal text-gray-600 h-8 shadow-sm" name="mapping_order[]">';
				$select .= '<option value="-1">' . esc_html__( 'Ignore column', 'email-subscribers' ) . '</option>';
				foreach ( $fields as $key => $value ) {
					$is_selected = false;
					if ( $is_email && 'email' === $key ) {
						$is_selected = true;
					} else if ( ! empty( $headers[ $i ] ) ) {
						if ( strip_tags( $headers[ $i ] ) === $fields[ $key ] ) {
							$is_selected = ( 'first_name' === $key ) || ( 'last_name'  === $key );
						}
					}
					$select     .= '<option value="' . $key . '" ' . ( $is_selected ? 'selected' : '' ) . '>' . $value . '</option>';
				}
				$select .= '</select>';
				$html   .= '<th class="pl-3 py-4 font-medium">' . $select . '</th>';
			}
			$html .= '</tr>';
			if ( ! empty( $headers ) ) {
				$html .= '<tr class="border-b border-gray-200 text-left text-sm leading-4 font-medium text-gray-500 tracking-wider rounded-md"><th></th>';
				foreach ( $headers as $header ) {
					$html .= '<th class="pl-3 py-3 font-medium">' . $header . '</th>';
				}
				$html .= '</tr>';
			}
			$html .= '</thead><tbody>';
			for ( $i = 0; $i < min( 3, $contactcount ); $i++ ) {
				$data  = str_getcsv(  ( $first[ $i ] ), $response['data']['separator'], '"' );
				$html .= '<tr class="border-b border-gray-200 text-left text-sm leading-4 text-gray-500 tracking-wide"><td class="pl-3">' . number_format_i18n( $i + 1 ) . '</td>';
				foreach ( $data as $cell ) {
					if ( ! empty( $cell ) && is_email( $cell ) ) {
						$cell = sanitize_email( strtolower( $cell ) );
					}
					$html .= '<td class="pl-3 py-3" title="' . strip_tags( $cell ) . '">' . ( $cell ) . '</td>';
				}
				$html .= '<tr>';
			}
			if ( $contactcount > 3 ) {
				$hidden_contacts_count = $contactcount - 4;
				if ( $hidden_contacts_count > 0 ) {
					/* translators: %s: Hidden contacts count */
					$html .= '<tr class="alternate bg-gray-50 pl-3 py-3 border-b border-gray-200 text-gray-500"><td class="pl-2 py-3">&nbsp;</td><td colspan="' . ( $cols ) . '"><span class="description">&hellip;' . sprintf( esc_html__( '%s contacts are hidden', 'email-subscribers' ), number_format_i18n( $contactcount - 4 ) ) . '&hellip;</span></td>';
				}

				$data  = str_getcsv( array_pop( $last ), $response['data']['separator'], '"' );
				$html .= '<tr class="border-b border-gray-200 text-left text-sm leading-4 text-gray-500 tracking-wider"><td class="pl-3 py-3">' . number_format_i18n( $contactcount ) . '</td>';
				foreach ( $data as $cell ) {
					$html .= '<td class="pl-3 py-3 " title="' . strip_tags( $cell ) . '">' . ( $cell ) . '</td>';
				}
				$html .= '<tr>';
			}
			$html .= '</tbody>';

			$html .= '</table>';
			$html .= '<input type="hidden" id="identifier" value="' . $identifier . '">';
			$html .= '</div></div>';

			$response['html']    = $html;
			$response['success'] =  true;
		}

		wp_send_json( $response );
	}

	/**
	 * Ajax handler to import subscirbers from temporary table
	 * 
	 * @since 4.6.6
	 */
	public function ajax_do_import() {

		check_ajax_referer( 'ig-es-admin-ajax-nonce', 'security' );

		global $wpdb;

		$memory_limit       = @ini_get( 'memory_limit' );
		$max_execution_time = @ini_get( 'max_execution_time' );

		@set_time_limit( 0 );

		if ( (int) $max_execution_time < 300 ) {
			@set_time_limit( 300 );
		}

		if ( (int) $memory_limit < 256 ) {
			// Add filter to increase memory limit
			add_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );

			wp_raise_memory_limit( 'ig_es' );

			// Remove the added filter function so that it won't be called again if wp_raise_memory_limit called later on.
			remove_filter( 'ig_es_memory_limit', 'ig_es_increase_memory_limit' );
		}

		$return['success'] = false;

		$bulkdata = array();
		if ( isset( $_POST['options'] ) ) {
			$bulkdata = ig_es_get_data( $_POST, 'options', array() );
		}
		
		$bulkdata      = wp_parse_args( $bulkdata, get_option( 'ig_es_bulk_import' ) );
		$erroremails   = get_option( 'ig_es_bulk_import_errors', array() );
		$order         = isset( $bulkdata['mapping_order'] ) ? $bulkdata['mapping_order']: array();
		$list_id       = isset( $bulkdata['list_id'] ) ? $bulkdata['list_id']            : array();
		$parts_at_once = 10;
		$status        = $bulkdata['status'];
		$error_codes   = array(
			'invalid'   => __( 'Email address is invalid.', 'email-subscribers' ),
			'empty'     => __( 'Email address is empty.', 'email-subscribers' ),
			'duplicate' => __( 'Duplicate email in the CSV file. Only the first record imported.', 'email-subscribers' ),
		);

		if ( ! empty( $list_id ) && ! is_array( $list_id ) ) {
			$list_id = array( $list_id );
		}

		if ( isset( $_POST['id'] ) ) {

			$bulkdata['current'] = (int) sanitize_text_field( $_POST['id'] );
			$raw_list_data = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT data FROM {$wpdb->prefix}ig_temp_import 
					WHERE identifier = %s ORDER BY ID ASC LIMIT %d, %d",
					$bulkdata['identifier'],
					$bulkdata['current'] * $parts_at_once,
					$parts_at_once
				)
			);
			if ( $raw_list_data ) {

				$contacts_data     = array();
				$current_date_time = ig_get_current_date_time();
				$contact_emails    = array();
				$processed_emails  = array();
				foreach ( $raw_list_data as $raw_list ) {
					$raw_list = unserialize( base64_decode( $raw_list ) );
					// each entry
					foreach ( $raw_list as $line ) {
						if ( ! trim( $line ) ) {
							$bulkdata['lines']--;
							continue;
						}
						$data       = str_getcsv( $line, $bulkdata['separator'], '"' );
						$cols_count = count( $data );
						$insert     = array();
						for ( $col = 0; $col < $cols_count; $col++ ) {
							$d = trim( $data[ $col ] );
							if ( ! isset( $order[ $col ] ) ) {
								continue;
							}
							switch ( $order[ $col ] ) {
								case 'first_last':
									$name = explode( ' ', $d );
									if ( ! empty( $name[0] ) ) {
										$insert['first_name'] = $name[0];
									}
									if ( ! empty( $name[1] ) ) {
										$insert['last_name']  = $name[1];
									}
									break;
								case 'last_first':
									$name = explode( ' ', $d );
									if ( ! empty( $name[1] ) ) {
										$insert['first_name'] = $name[1];
									}
									if ( ! empty( $name[0] ) ) {
										$insert['last_name']  = $name[0];
									}
									break;
								case '-1':
									// ignored column
									break;
								default:
									$insert[ $order[ $col ] ] = $d;
							}
						}
						
						if ( empty( $insert['email'] ) || ! is_email( $insert['email'] ) ) {
							$error_data = array();
							if ( empty( $insert['email'] ) ) {
								$error_data['error_code'] = 'empty';
							} else if ( ! is_email( $insert['email'] ) ) {
								$error_data['error_code'] = 'invalid';
								$error_data['email'] = $insert['email'];
							}
							if ( ! empty( $insert['first_name'] ) ) {
								$error_data['first_name'] = $insert['first_name'];
							}
							if ( ! empty( $insert['last_name'] ) ) {
								$error_data['last_name'] = $insert['last_name'];
							}
							$bulkdata['errors']++;
							$erroremails[] = $error_data;
							continue;
						}

						$email = sanitize_email( strtolower( $insert['email'] ) );

						if ( ! in_array( $email, $processed_emails, true ) ) {
							$first_name = isset( $insert['first_name'] ) ? ES_Common::handle_emoji_characters( sanitize_text_field( trim( $insert['first_name'] ) ) ) : '';
							$last_name  = isset( $insert['last_name'] ) ? ES_Common::handle_emoji_characters( sanitize_text_field( trim( $insert['last_name'] ) ) ) : '';

							// If name empty, get the name from Email.
							if ( empty( $first_name ) && empty( $last_name ) ) {
								$name       = ES_Common::get_name_from_email( $email );
								$names      = ES_Common::prepare_first_name_last_name( $name );
								$first_name = sanitize_text_field( $names['first_name'] );
								$last_name  = sanitize_text_field( $names['last_name'] );
							}

							$guid = ES_Common::generate_guid();

							$contacts_data[$email]['first_name'] = $first_name;
							$contacts_data[$email]['last_name']  = $last_name;
							$contacts_data[$email]['email']      = $email;
							$contacts_data[$email]['source']     = 'import';
							$contacts_data[$email]['status']     = 'verified';
							$contacts_data[$email]['hash']       = $guid;
							$contacts_data[$email]['created_at'] = $current_date_time;

							$processed_emails[] = $email;
							$bulkdata['imported']++;
						} else {
							$error_data = array(
								'email'      => $email,
								'error_code' => 'duplicate',
							);
							if ( ! empty( $insert['first_name'] ) ) {
								$error_data['first_name'] = $insert['first_name'];
							}
							if ( ! empty( $insert['last_name'] ) ) {
								$error_data['last_name'] = $insert['last_name'];
							}
							$erroremails[] = $error_data;
							$bulkdata['errors']++;
						}
						$contact_emails[] = $email;
					}
				}
				
				if ( count( $contact_emails ) > 0 ) {

					$contact_emails = array_unique( $contact_emails );

					$existing_contacts_email_id_map = ES()->contacts_db->get_email_id_map( $processed_emails );
					$existing_contacts_count        = count( $existing_contacts_email_id_map );
					if ( ! empty( $existing_contacts_email_id_map ) ) {
						$contacts_data = array_diff_key( $contacts_data, $existing_contacts_email_id_map ); 
					}

					if ( ! empty( $contacts_data ) ) {
						ES()->contacts_db->bulk_insert( $contacts_data );
					}

					$contact_ids = ES()->contacts_db->get_contact_ids_by_emails( $contact_emails );
					if ( count( $contact_ids ) > 0 ) {
						ES()->lists_contacts_db->remove_contacts_from_lists( $contact_ids, $list_id );
						ES()->lists_contacts_db->do_import_contacts_into_list( $list_id, $contact_ids, $status, 1, $current_date_time );
					}
				}
			}

			$return['memoryusage'] = size_format( memory_get_peak_usage( true ), 2 );
			$return['errors']      = isset( $bulkdata['errors'] ) ? $bulkdata['errors'] : 0;
			$return['imported']    = ( $bulkdata['imported'] );
			$return['total']       = ( $bulkdata['lines'] );
			$return['f_errors']    = number_format_i18n( $bulkdata['errors'] );
			$return['f_imported']  = number_format_i18n( $bulkdata['imported'] );
			$return['f_total']     = number_format_i18n( $bulkdata['lines'] );

			$return['html'] = '';

			if ( $bulkdata['imported'] + $bulkdata['errors'] >= $bulkdata['lines'] ) {
				/* translators: 1. Total imported contacts 2. Total contacts */
				$return['html'] .= '<p class="text-base text-gray-600 pt-2 pb-1.5">' . sprintf( esc_html__( '%1$s of %2$s contacts imported', 'email-subscribers' ), '<span class="font-medium">' . number_format_i18n( $bulkdata['imported'] ) . '</span>', '<span class="font-medium">' . number_format_i18n( $bulkdata['lines'] ) . '</span>' ) . '<p>';
				
				if ( $bulkdata['errors'] ) {
					$i      = 0;
					$table  = '<p class="text-sm text-gray-600 pt-2 pb-1.5">' . esc_html__( 'The following contacts were skipped', 'email-subscribers' ) . ':</p>';
					$table .= '<table class="w-full bg-white rounded-lg shadow overflow-hidden mt-1.5">';
					$table .= '<thead class="rounded-md"><tr class="border-b border-gray-200 bg-gray-50 text-left text-sm leading-4 font-medium text-gray-500 tracking-wider"><th class="pl-4 py-4" width="5%">#</th>';

					$first_name_column_choosen = in_array( 'first_name', $order, true );
					if ( $first_name_column_choosen ) {
						$table .= '<th class="pl-3 py-3 font-medium">' . esc_html__( 'First Name', 'email-subscribers' ) . '</th>';
					}

					$last_name_column_choosen = in_array( 'last_name', $order, true );
					if ( $last_name_column_choosen ) {
						$table .= '<th class="pl-3 py-3 font-medium">' . esc_html__( 'Last Name', 'email-subscribers' ) . '</th>';
					}
					
					$table .= '<th class="pl-3 py-3 font-medium">' . esc_html__( 'Email', 'email-subscribers' ) . '</th>';
					$table .= '<th class="pl-3 pr-1 py-3 font-medium">' . esc_html__( 'Reason', 'email-subscribers' ) . '</th>';
					$table .= '</tr></thead><tbody>';
					foreach ( $erroremails as $error_data ) {
						$table .= '<tr class="border-b border-gray-200 text-left leading-4 text-gray-800 tracking-wide">';
						$table .= '<td class="pl-4">' . ( ++$i ) . '</td>';
						$email  = ! empty( $error_data['email'] ) ? $error_data['email'] : '-';
						if ( $first_name_column_choosen ) {
							$first_name = ! empty( $error_data['first_name'] ) ? $error_data['first_name'] : '-';
							$table .= '<td class="pl-3 py-3">' . esc_html( $first_name ) . '</td>';
						}
						if ( $last_name_column_choosen ) {
							$last_name = ! empty( $error_data['last_name'] ) ? $error_data['last_name'] : '-';
							$table .= '<td class="pl-3 py-3">' . esc_html( $last_name ) . '</td>';
						}
						$error_code = ! empty( $error_data['error_code'] ) ? $error_data['error_code'] : '-';
						$reason     = ! empty( $error_codes[$error_code] ) ? $error_codes[$error_code] : '-';
						$table .= '<td class="pl-3 py-3">' . esc_html( $email ) . '</td><td class="pl-3 py-3">' . esc_html( $reason ) . '</td></tr>';
					}
					$table          .= '</tbody></table>';
					$return['html'] .= $table;
				}
				$this->do_cleanup();
			} else {
				update_option( 'ig_es_bulk_import', $bulkdata );
				update_option( 'ig_es_bulk_import_errors', $erroremails );
			}
			$return['success'] = true;
		}

		wp_send_json( $return );
	}

	/**
	 * Method to create temporary table if not already exists
	 * 
	 * @since 4.6.6
	 */
	public function maybe_create_temporary_import_table() {

		global $wpdb;
		
		require_once  ABSPATH . 'wp-admin/includes/upgrade.php';
		
		$charset_collate    = $wpdb->get_charset_collate();
		$create_table_query = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ig_temp_import (
			ID bigint(20) NOT NULL AUTO_INCREMENT,
			data longtext NOT NULL,
			identifier char(13) NOT NULL,
			PRIMARY KEY (ID)
		) $charset_collate";

		dbDelta( $create_table_query );
	}

	/**
	 * Method to truncate table and options used during import process
	 * 
	 * @since 4.6.6
	 */
	public function do_cleanup() {

		global $wpdb;

		// Delete options used during import.
		delete_option( 'ig_es_bulk_import' );
		delete_option( 'ig_es_bulk_import_errors' );

		// We are trancating table so that primary key is reset to 1 otherwise ID column's value will increase on every insert and at some point ID column's data type may not be able to accomodate its value resulting in insert to fail. 
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}ig_temp_import" );
	}
}
