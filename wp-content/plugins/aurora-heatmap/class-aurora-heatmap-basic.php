<?php
/**
 * Aurora Heatmap Basic Class
 *
 * Main class for the Free Version.
 *
 * @package aurora-heatmap
 * @copyright 2019-2020 R3098 <info@seous.info>
 * @version 1.4.11
 */

/**
 * Aurora_Heatmap_Basic
 */
class Aurora_Heatmap_Basic {

	const SLUG = 'aurora-heatmap';

	const VERSION = '1.4.11';

	const PLAN = 'basic';

	const FROM_PC     = 0;
	const FROM_MOBILE = 1;

	const VIEW_WIDTH = array(
		self::FROM_PC     => 1250,
		self::FROM_MOBILE => 375,
	);

	const CLICK_PC     = 0x10;
	const CLICK_MOBILE = 0x11;

	const EVENT_NAMES = array(
		'click_pc'     => self::CLICK_PC,
		'click_mobile' => self::CLICK_MOBILE,
	);

	const LIST_PER_PAGE = 25;

	/**
	 * Options
	 *
	 * @var array|object
	 */
	protected $options = array();

	/**
	 * Accuracy Ranges
	 *
	 * @var array
	 */
	protected $ar = array();

	/**
	 * Init user
	 *
	 * @var WP_User
	 */
	protected $user = null;

	/**
	 * Is debug
	 *
	 * @var bool
	 */
	public $is_debug = false;

	/**
	 * Singleton object
	 *
	 * @var object
	 */
	private static $self;

	/**
	 * Get instance
	 */
	public static function get_instance() {
		if ( ! self::$self ) {
			self::$self = new self();
		}

		return self::$self;
	}

	/**
	 * Constructor
	 */
	protected function __construct() {

		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_scripts' ) );
		add_action( 'aurora_heatmap_cron_daily', array( &$this, 'aurora_heatmap_cron_daily' ) );

		add_action( 'wp_ajax_aurora_heatmap', array( &$this, 'ajax_aurora_heatmap' ) );
		add_action( 'wp_ajax_nopriv_aurora_heatmap', array( &$this, 'ajax_aurora_heatmap' ) );
		add_action( 'wp_is_mobile', array( &$this, 'wp_is_mobile' ) );

		$this->is_debug = (bool) apply_filters( 'aurora_heatmap_debug', false );

		$this->options = new Aurora_Heatmap_Options( array( &$this, 'option_checker' ) );

		// Set accuracy ranges.
		$this->ar = $this->get_ar();

		if ( ! is_admin() && isset( $_GET['aurora-heatmap'] ) && $this->can_view() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->user = wp_get_current_user();
			add_action(
				'wp',
				function() {
					wp_set_current_user( 0 );
				}
			);
			add_action(
				'shutdown',
				function() {
					wp_set_current_user( $this->user->ID );
				},
				0
			);
		}
	}

	/**
	 * Callback for admin_init.
	 *
	 * For register_script, register_style, redirect list table.
	 */
	public function admin_init() {
		// If activated old version or another plan, do setup.
		if ( version_compare( $this->options['activated_ver'], $this::VERSION, '<' ) || $this::PLAN !== $this->options['activated_plan'] ) {
			$this->setup();
		}

		wp_register_script( 'aurora-heatmap-admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), $this::VERSION, false );
		wp_register_style( 'aurora-heatmap', plugins_url( 'style.css', __FILE__ ), array(), $this::VERSION );

		if ( ! isset( $_SERVER['REQUEST_URI'] ) || ! strpos( $_SERVER['REQUEST_URI'], $this::SLUG ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return;
		}

		$uri      = wp_unslash( $_SERVER['REQUEST_URI'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$p_url    = wp_parse_url( $uri );
		$basename = basename( $p_url['path'] );
		if ( ! isset( $p_url['query'] ) ) {
			$p_url['query'] = array( 'page' => null );
		}
		$query = wp_parse_args( $p_url['query'], array( 'page' => null ) );
		$tab   = filter_input( INPUT_GET, 'tab' );
		if ( ! $tab ) {
			$tab = 'view';
		}

		if ( 'options-general.php' !== $basename ||
			$this::SLUG !== $query['page'] ||
			! isset( $_SERVER['REQUEST_METHOD'] ) ||
			'POST' !== $_SERVER['REQUEST_METHOD'] ||
			! ( 'view' === $tab || 'unread' === $tab ) ||
			! $this->can_view()
		) {
			return;
		}

		// Build url.
		$url = set_url_scheme( 'http://' . ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '' ) . $uri ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		$remove_query = array();
		$add_query    = array();

		$post_s = filter_input( INPUT_POST, 's' );
		if ( null !== $post_s ) {
			$remove_query[] = 's';
			$add_query['s'] = rawurlencode( $post_s );
		}

		$post_paged = filter_input( INPUT_POST, 'paged', FILTER_VALIDATE_INT );
		if ( null !== $post_paged ) {
			$remove_query[]     = 'paged';
			$add_query['paged'] = $post_paged;
		}

		$url = remove_query_arg( $remove_query, $url );
		$url = add_query_arg( $add_query, $url );

		// For bulk delete action.
		$id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY );
		while ( true ) {
			$action = false;
			if ( filter_input( INPUT_POST, 'filter_action' ) ) {
				break;
			}
			$action = filter_input( INPUT_POST, 'action' );
			if ( null !== $action && -1 !== (int) $action ) {
				break;
			}
			$action = filter_input( INPUT_POST, 'action2' );
			if ( null !== $action && -1 !== (int) $action ) {
				break;
			}
			$action = false;
			break;
		}
		if ( $id && 'delete' === $action ) {
			$this->delete_data( $id );
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Get accuracy ranges
	 *
	 * @return array
	 */
	public function get_ar() {
		$r = 0xF;
		$s = 0xA;
		$t = function( $m, $d ) {
			return array( $m - $d, $m + $d );
		};

		$q = function( $m, $d, $a, $b ) use ( $r, $s, $t ) {
			return array(
				1 => $t( $m - $d, $r * $a ),
				2 => $t( $m + $d, $s * $b ),
			);
		};

		return array(
			$q( 0x500, 0x14, $s * 2, $s / 2 ),
			$q( 0x172, 0x05, 3, 1 ),
		);
	}

	/**
	 * Callback for register_activation_hook
	 */
	public static function activation() {
		self::get_instance()->setup();
	}

	/**
	 * Setup Aurora Heatmap
	 */
	public function setup() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset_collate  = $wpdb->get_charset_collate();
		$max_index_length = 191;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery

		// From Aurora Heatmap (Installer) 0.1.1 or earlier.
		// From Aurora Heatmap 1.0.0 or earlier.
		if ( ! isset( $this->options['activated_ver'] ) &&
			$wpdb->get_row( $wpdb->prepare( 'SHOW TABLES LIKE %s', "{$wpdb->prefix}ahm_events" ) ) &&
			$wpdb->get_row( $wpdb->prepare( 'SHOW TABLES LIKE %s', "{$wpdb->prefix}ahm_pages" ) )
		) {
			// If exists some data, treat as 1.0.0, else drop table.
			if ( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ahm_events" ) ||
				$wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ahm_pages" ) ) {
				$this->options['activated_ver'] = '1.0.0';
			} else {
				$wpdb->query( "DROP TABLE {$wpdb->prefix}ahm_events" );
				$wpdb->query( "DROP TABLE {$wpdb->prefix}ahm_pages" );
			}
		}

		// Migration.
		if ( isset( $this->options['activated_ver'] ) ) {
			// To 1.1.0.
			if ( version_compare( $this->options['activated_ver'], '1.1.0', '<' ) ) {
				if ( ! isset( $this->options['accuracy'] ) || 2 === $this->options['accuracy'] ) {
					$this->delete_low_accuracy_data( 1 );
				}
			}
			// To 1.3.0.
			if ( version_compare( $this->options['activated_ver'], '1.3.0', '<' ) &&
				$wpdb->get_var( "DESCRIBE {$wpdb->prefix}ahm_events original" )
			) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}ahm_events CHANGE COLUMN original page_id int(8) UNSIGNED NOT NULL, ADD COLUMN page_id2 int(8) UNSIGNED NOT NULL AFTER page_id, DROP COLUMN keep_query, DROP COLUMN keep_hash, DROP COLUMN united, ADD KEY page_id2 (page_id2)" );
				$wpdb->query( "UPDATE {$wpdb->prefix}ahm_events SET page_id2 = page_id" );
				$wpdb->query( $wpdb->prepare( "ALTER TABLE {$wpdb->prefix}ahm_pages ADD COLUMN url2 text NOT NULL AFTER url, ADD KEY url2 (url2(%d))", $max_index_length ) );
			}
			// To 1.4.0.
			if ( version_compare( $this->options['activated_ver'], '1.4.0', '<' ) ) {
				if ( $wpdb->get_var( "DESCRIBE {$wpdb->prefix}ahm_events accuracy" ) ) {
					$wpdb->query( "ALTER TABLE {$wpdb->prefix}ahm_events DROP COLUMN accuracy" );
				}
				if ( $wpdb->get_var( "SHOW INDEX FROM {$wpdb->prefix}ahm_events WHERE KEY_NAME = 'event_accuracy'" ) ) {
					$wpdb->query( "DROP INDEX event_accuracy ON {$wpdb->prefix}ahm_events" );
				}
			}
		}

		dbDelta(
			"CREATE TABLE {$wpdb->prefix}ahm_events (
			id           bigint(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
			page_id      int(8)       UNSIGNED NOT NULL,
			page_id2     int(8)       UNSIGNED NOT NULL,
			event        tinyint(3)   UNSIGNED NOT NULL,
			x            mediumint(5) UNSIGNED NOT NULL,
			y            mediumint(7) UNSIGNED NOT NULL,
			width        mediumint(5) UNSIGNED NOT NULL,
			height       mediumint(7) UNSIGNED NOT NULL,
			insert_at    datetime              NOT NULL,
			PRIMARY KEY  (id),
			KEY event (event),
			KEY page_id2 (page_id2),
			KEY insert_at (insert_at)
			) {$charset_collate}"
		);

		dbDelta(
			"CREATE TABLE {$wpdb->prefix}ahm_pages (
			id           int(8)   UNSIGNED NOT NULL AUTO_INCREMENT,
			url          text              NOT NULL,
			url2         text              NOT NULL,
			title        text              NOT NULL,
			insert_at    datetime          NOT NULL,
			update_at    datetime          NOT NULL,
			PRIMARY KEY  (id),
			KEY url (url({$max_index_length})),
			KEY url2 (url2({$max_index_length}))
			) {$charset_collate}"
		);

		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		$this->options['activated_ver']  = $this::VERSION;
		$this->options['activated_plan'] = $this::PLAN;

		$this->setup_daily_cron();
		$this->update_url2();
	}

	/**
	 * Callback for register_deactivation_hook
	 */
	public static function deactivation() {
		wp_clear_scheduled_hook( 'aurora_heatmap_cron_daily' );
	}

	/**
	 * Retrives the timezone from site settings as a `DateTimeZone` object.
	 *
	 * @return DateTimeZone Timezone object.
	 */
	protected static function wp_timezone() {
		// Since WordPress 5.3.0.
		if ( function_exists( 'wp_timezone' ) ) {
			return wp_timezone();
		}

		// Polyfill for wp_timezone() function.
		$timezone_string = get_option( 'timezone_string' );

		if ( $timezone_string ) {
			return new DateTimeZone( $timezone_string );
		}

		$offset  = (float) get_option( 'gmt_offset' );
		$hours   = (int) $offset;
		$minutes = ( $offset - $hours );

		$sign      = ( $offset < 0 ) ? '-' : '+';
		$abs_hour  = abs( $hours );
		$abs_mins  = abs( $minutes * 60 );
		$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

		return new DateTimeZone( $tz_offset );
	}

	/**
	 * Setup daily cron
	 */
	protected function setup_daily_cron() {
		$tz  = self::wp_timezone();
		$now = new DateTime( '', $tz );
		$am4 = new DateTime( 'T0400', $tz );
		if ( $am4 < $now ) {
			$am4->add( new DateInterval( 'P1D' ) );
		}
		$am4 = $am4->getTimestamp();

		$next = wp_next_scheduled( 'aurora_heatmap_cron_daily' );
		if ( ! $next ) {
			wp_schedule_event( $am4, 'daily', 'aurora_heatmap_cron_daily' );
		} elseif ( $next < $am4 || $am4 + 3600 < $next ) {
			wp_clear_scheduled_hook( 'aurora_heatmap_cron_daily' );
			wp_schedule_event( $am4, 'daily', 'aurora_heatmap_cron_daily' );
		}
	}

	/**
	 * Action hook for admin_menu
	 */
	public function admin_menu() {
		if ( $this->can_view() || $this->can_settings() ) {
			$page = add_options_page(
				__( 'Aurora Heatmap', 'aurora-heatmap' ),
				__( 'Aurora Heatmap', 'aurora-heatmap' ),
				'read',
				$this::SLUG,
				array( &$this, 'print_admin_options_page' )
			);
			add_action(
				'admin_print_styles-' . $page,
				function() {
					wp_enqueue_style( 'aurora-heatmap' );
				}
			);
			add_action(
				'admin_print_scripts-' . $page,
				function() {
					$tabs       = $this->get_admin_tabs();
					$active_tab = filter_input( INPUT_GET, 'tab' );
					if ( ! $active_tab || ! isset( $tabs[ $active_tab ] ) ) {
						$active_tab = key( $tabs );
					}
					wp_localize_script(
						'aurora-heatmap-admin',
						'aurora_heatmap_admin',
						array(
							'active_tab'        => $active_tab,
							'click_heatmap'     => __( 'Click Heatmap', 'aurora-heatmap' ),
							'breakaway_heatmap' => __( 'Breakaway Heatmap', 'aurora-heatmap' ),
							'attention_heatmap' => __( 'Attention Heatmap', 'aurora-heatmap' ),
						)
					);
					wp_enqueue_script( 'aurora-heatmap-admin' );
				}
			);
		}
	}

	/**
	 * Get admin tabs
	 *
	 * @return array
	 */
	protected function get_admin_tabs() {
		return array(
			'view'     => array(
				'name'      => __( 'Heatmap List', 'aurora-heatmap' ),
				'dashicons' => 'dashicons-list-view',
				'can_use'   => $this->can_view(),
			),
			'unread'   => array(
				'name'      => __( 'Unread Detection', 'aurora-heatmap' ),
				'dashicons' => 'dashicons-flag',
				'can_use'   => false,
			),
			'settings' => array(
				'name'      => __( 'Settings', 'aurora-heatmap' ),
				'dashicons' => 'dashicons-admin-generic',
				'can_use'   => $this->can_settings(),
			),
			'help'     => array(
				'name'      => __( 'Premium Version Information', 'aurora-heatmap' ),
				'dashicons' => 'dashicons-info',
				'can_use'   => true,
			),
		);
	}

	/**
	 * Print checkbox
	 *
	 * @param string $label     Label of input checkbox elements.
	 * @param string $name      Name of input checkbox elements.
	 * @param bool   $can_use   Can use or not.
	 * @param mixed  $overwrite Overwrite option value or null.
	 */
	protected function print_checkbox( $label, $name, $can_use = true, $overwrite = null ) {
		$value   = ( null === $overwrite ) ? $this->options[ $name ] : $overwrite;
		$checked = $value ? ' checked' : '';
		if ( $can_use ) {
			echo '<input type="hidden" value="0" name="' . esc_attr( $name ) . '"><label><input type="checkbox" value="1" name="' . esc_attr( $name ) . '"' . esc_attr( $checked ) . '>';
		} else {
			echo '<label><input type="checkbox"' . esc_attr( $checked ) . ' disabled>';
		}
		echo esc_html( $label ) . '</label>';
	}

	/**
	 * Print checklist
	 *
	 * @param string $name  Name of input checkbox elements.
	 * @param array  $list  Array of objects.
	 * @param string $id    ID property name.
	 * @param string $label Label property name.
	 */
	protected function print_checklist( $name, $list, $id, $label ) {
		$checked = $this->options[ $name ];
		echo '<input type="hidden" name="' . esc_attr( $name ) . '[]" value="">';
		if ( empty( $list ) ) {
			esc_html_e( '( empty )' );
			return;
		}
		foreach ( $list as $e ) {
			echo '<label class="ahm-inline-block"><input type="checkbox" name="' . esc_attr( $name ) . '[]" value="' . esc_attr( $e->{$id} ) . '"' . ( in_array( $e->{$id}, $checked, true ) ? ' checked' : '' ) . '>' . esc_html( $e->{$label} ) . '</label> ';
		}
	}

	/**
	 * Print radio options
	 *
	 * @param array  $options   Array of $value => $label.
	 * @param string $name      Name of input ratio elements.
	 * @param bool   $can_use   Can use or not.
	 * @param mixed  $overwrite Overwrite option value or null.
	 */
	protected function print_radio_options( $options, $name, $can_use, $overwrite = null ) {
		$current_value = ( null === $overwrite ) ? $this->options[ $name ] : $overwrite;
		foreach ( $options as $value => $label ) {
			$value_string = is_bool( $value ) ? ( $value ? 'true' : 'false' ) : strval( $value );
			if ( is_string( $label ) ) {
				echo '<label>';
				$attrs = array(
					'type'  => 'radio',
					'name'  => $name,
					'value' => $value_string,
				);
				if ( ! $can_use ) {
					$attrs['disabled'] = 'disabled';
				}
				if ( $value === $current_value ) {
					$attrs['checked'] = 'checked';
				}
				$this->open_tag( 'input', $attrs );
				echo '<span>' . esc_html( $label ) . '</span>';
				echo '</label><br>';
			} else {
				$radio_label = $label[0];
				$text_name   = $label[1];
				$text_label  = $label[2];
				$text_value  = $label[3];
				$input_id    = $name . '_' . $value_string;

				$attrs = array(
					'type'  => 'radio',
					'name'  => $name,
					'id'    => $input_id,
					'value' => $value_string,
					'class' => 'ahm-radio-group',
				);
				if ( $value === $current_value ) {
					$attrs['checked'] = 'checked';
				}
				$this->open_tag( 'input', $attrs );
				echo '<label for="' . esc_attr( $input_id ) . '"><span class="inner-label">' . esc_html( $radio_label ) . '</span>';
				if ( $can_use ) {
					echo '<div>';
				} else {
					echo '<div class="disabled">';
				}
				echo '<span class="premium-options">Premium</span> <span class="inner-label">' . esc_html( $text_label ) . '</span>';

				$attrs = array(
					'type'  => 'text',
					'name'  => $text_name,
					'value' => $text_value,
				);
				if ( ! $can_use ) {
					$attrs['disabled'] = 'disabled';
				}
				$this->open_tag( 'input', $attrs );
				echo '</div></label>';
			}
		}
	}

	/**
	 * Print plugin notices
	 */
	protected function print_plugin_notices() {
		// Do nothing.
	}

	/**
	 * Open tag
	 *
	 * @param string $element_name HTML element name.
	 * @param array  $attributes   Attributes.
	 * @param bool   $return       Get or echo.
	 */
	protected function open_tag( $element_name, $attributes, $return = false ) {
		$out = '<' . esc_attr( $element_name );
		foreach ( $attributes as $attr => $val ) {
			$out .= ' ' . esc_attr( $attr ) . '="' . esc_attr( $val ) . '"';
		}
		$out .= '>';
		if ( $return ) {
			return $out;
		}
		echo $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Print admin_options_page
	 */
	public function print_admin_options_page() {
		$tabs       = $this->get_admin_tabs();
		$pagenum    = filter_input( INPUT_GET, 'pagenum' );
		$active_tab = filter_input( INPUT_GET, 'tab' );

		if ( ! $pagenum ) {
			$pagenum = 1;
		}
		if ( ! $active_tab || ! isset( $tabs[ $active_tab ] ) ) {
			$active_tab = key( $tabs );
		}

		echo '<div class="wrap"><h2>' . esc_html__( 'Aurora Heatmap', 'aurora-heatmap' ) . '</h2>';

		// Print plugin notices.
		$this->print_plugin_notices();

		if ( isset( $_SERVER['REQUEST_METHOD'] ) &&
			'POST' === $_SERVER['REQUEST_METHOD'] &&
			filter_input( INPUT_POST, 'mode' ) &&
			check_admin_referer( 'aurora_heatmap_action', 'aurora_heatmap_nonce' ) &&
			$this->can_settings()
		) {
			switch ( filter_input( INPUT_POST, 'mode' ) ) {
				case 'save':
					$this->save_options();
					add_settings_error( $this::SLUG, '', __( 'Updated options.', 'aurora-heatmap' ), 'updated' );
					break;
				case 'delete':
					$this->delete_all();
					add_settings_error( $this::SLUG, '', __( 'Deleted heatmap data.', 'aurora-heatmap' ), 'updated' );
					break;
			}
		}

		settings_errors( $this::SLUG );

		echo '<h2 class="nav-tab-wrapper ahm-nav">';
		foreach ( $tabs as $key => $value ) {
			if ( ! $value ) {
				continue;
			}

			if ( $value['can_use'] ) {
				$this->open_tag(
					'a',
					array(
						'href'  => '?page=' . esc_attr( $this::SLUG ) . '&amp;tab=' . esc_attr( $key ),
						'class' => 'nav-tab' . ( $key === $active_tab ? ' nav-tab-active' : '' ),
					)
				);
			} else {
				echo '<a href="javascript:void(0)" class="nav-tab nav-tab-disabled" tabindex="-1" disabled>';
			}

			echo '<span class="dashicons ' . esc_attr( $value['dashicons'] ) . '"></span>&nbsp;' . esc_html( $value['name'] ) . '</a>';
		}
		echo '</h2>';
		$this->print_tab_content( $active_tab );
		echo '<div class="ahm-footer"><hr>' . esc_html( 'Aurora Heatmap' ) . '</div></div>';
	}

	/**
	 * Print tab content
	 *
	 * @param string $active_tab Active tab.
	 */
	protected function print_tab_content( $active_tab ) {
		if ( 'view' === $active_tab && $this->can_view() ) {
			include_once __DIR__ . '/class-aurora-heatmap-list.php';
			$table = new Aurora_Heatmap_List( $this );
			$page  = filter_input( INPUT_GET, 'page' );
			if ( ! $page ) {
				$page = filter_input( INPUT_POST, 'page' );
			}
			echo '<fieldset id="ahm-description">';
			echo '<legend id="ahm-legend">' . esc_html__( 'Click Heatmap', 'aurora-heatmap' ) . '</legend>';
			echo '<div class="outer"><div class="inner">';

			$descriptions = array(
				'click'     => array(
					'legend_image' => array(
						'1x' => 'img/legend_click@1x.png',
						'2x' => 'img/legend_click@2x.png',
					),
					'description'  => array(
						__( 'Click heatmap shows where a user clicks the mouse, or where it is tapped on mobile.', 'aurora-heatmap' ),
						__( 'You can find interesting places, text selection, lead wire functions, and false clicks that impair the user experience.', 'aurora-heatmap' ),
					),
				),
				'breakaway' => array(
					'legend_image' => array(
						'1x' => 'img/legend_breakaway@1x.png',
						'2x' => 'img/legend_breakaway@2x.png',
					),
					'description'  => array(
						__( 'Breakaway heatmap determines how far the page has been read.', 'aurora-heatmap' ),
						__( 'At the 90 display position, 10% of users have left.', 'aurora-heatmap' ),
					),
				),
				'attention' => array(
					'legend_image' => array(
						'1x' => 'img/legend_attention@1x.png',
						'2x' => 'img/legend_attention@2x.png',
					),
					'description'  => array(
						__( 'Attention heatmap determines where the user is watching.', 'aurora-heatmap' ),
						__( 'Yellow indicates the strongest reaction.', 'aurora-heatmap' ),
					),
				),
			);

			foreach ( $descriptions as $desc ) {
				echo '<div class="description">';
				echo '<img src="' . esc_attr( plugins_url( $desc['legend_image']['1x'], __FILE__ ) ) . '" srcset="' . esc_attr( plugins_url( $desc['legend_image']['1x'], __FILE__ ) ) . ' 1x, ' . esc_attr( plugins_url( $desc['legend_image']['2x'], __FILE__ ) ) . ' 2x" width="336" height="44">';
				foreach ( $desc['description'] as $p ) {
					echo '<p>' . esc_html( $p ) . '</p>';
				}
				echo '</div>';
			}
			echo '</div></div>';
			echo '</fieldset>';
			?>
<ul class="subsubsub">
	<li><span class="dashicons dashicons-laptop"></span> <?php esc_html_e( 'PC', 'aurora-heatmap' ); ?> |</li>
	<li><span class="dashicons dashicons-smartphone"></span> <?php esc_html_e( 'Mobile', 'aurora-heatmap' ); ?> |</li>
	<li><span class="dashicons dashicons-location"></span> <?php esc_html_e( 'Click', 'aurora-heatmap' ); ?> |</li>
	<li><span class="dashicons dashicons-migrate"></span> <?php esc_html_e( 'Breakaway', 'aurora-heatmap' ); ?> |</li>
	<li><span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Attention', 'aurora-heatmap' ); ?></li>
</ul>
			<?php
			echo '<form method="POST" id="ahm-view-form">';
			echo '<input type="hidden" name="page" value="' . esc_attr( $page ) . '">';
			echo '<input type="hidden" name="tab" value="' . esc_attr( $active_tab ) . '">';
			$table->prepare_items();
			$table->search_box( __( 'Search page', 'aurora-heatmap' ), 'search_page' );
			$table->display();
			echo '</form>';
		} elseif ( 'help' === $active_tab && $this->can_settings() ) {
			$this->print_help();
		} elseif ( 'settings' === $active_tab && $this->can_settings() ) {
			$this->print_settings();
		}
	}

	/**
	 * Print help
	 */
	protected function print_help() {
		$vs = function( $feature, $now, ...$args ) {
			echo '<tr><th>' . esc_html( $feature ) . '</th>';
			foreach ( $args as $index => $arg ) {
				$col  = $index === $now ? 'current' : '';
				$icon = $arg ? 'dashicons-yes-alt' : 'dashicons-no-alt';

				echo '<td class="' . esc_attr( $col ) . '"><span class="dashicons ' . esc_attr( $icon ) . '"></span></td>';
			}
			echo '</tr>';
		};

		if ( 'basic' === $this::PLAN ) {
			echo '<p>';
			_e( '<b>Aurora Heatmap</b> has a premium version with extended features.', 'aurora-heatmap' ); // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction
			echo '</p><p>';
			esc_html_e( 'If you like this plugin, please consider upgrading!', 'aurora-heatmap' );
			echo '</p>';
		}
		?>

		<h3><?php esc_html_e( 'Features comparison', 'aurora-heatmap' ); ?></h3>
		<table class="widefat" id="ahm-vs">
		<thead>
			<tr>
				<th rowspan="2"></th>
				<th rowspan="2"><?php esc_html_e( 'Free Version', 'aurora-heatmap' ); ?></th>
				<th colspan="2"><?php esc_html_e( 'Premium Version', 'aurora-heatmap' ); ?></th>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Free Plan', 'aurora-heatmap' ); ?></th>
				<th><?php esc_html_e( 'Standard Plan', 'aurora-heatmap' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		$plans = array(
			'basic'    => 0,
			'free'     => 1,
			'standard' => 2,
		);
		$now   = $plans[ $this::PLAN ];

		$vs( __( 'Priority Email Support', 'aurora-heatmap' ), $now, 0, 0, 1 );
		$vs( __( 'Click Heatmap', 'aurora-heatmap' ), $now, 1, 1, 1 );
		$vs( __( 'Breakaway Heatmap', 'aurora-heatmap' ), $now, 0, 0, 1 );
		$vs( __( 'Attention Heatmap', 'aurora-heatmap' ), $now, 0, 0, 1 );
		$vs( __( 'Unread Detection', 'aurora-heatmap' ), $now, 0, 0, 1 );
		$vs( __( 'URL Optimization', 'aurora-heatmap' ), $now, 1, 1, 1 );
		$vs( __( 'Advanced URL Optimization', 'aurora-heatmap' ), $now, 0, 0, 1 );
		$vs( __( 'Extended Retension Period (6 months)', 'aurora-heatmap' ), $now, 0, 0, 1 );
		$vs( __( 'Update to the Latest Version', 'aurora-heatmap' ), $now, 1, 0, 1 );

		echo '</tbody><tfoot><tr><th></th>';

		foreach ( $plans as $i ) {
			echo ( $i === $now ) ? '<th>Now</th>' : '<th></th>';
		}

		echo '</tr></tfoot></table><p>';
		_e( '<b>*</b> The free plan is a premium version with no license or expired.', 'aurora-heatmap' ); // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction
		echo '</p>';

		$this->print_additional_help();
	}

	/**
	 * Print additional help
	 */
	protected function print_additional_help() {
		// phpcs:disable WordPress.Security.EscapeOutput.UnsafePrintingFunction
		?>
		<h3><?php esc_html_e( 'Migrate data to the premium version', 'aurora-heatmap' ); ?></h3>
		<ol>
		<li><?php _e( '<a href="https://market.seous.info/aurora-heatmap/premium">Get the installer</a> of the premium version plugin.', 'aurora-heatmap' ); ?></li>
		<li><?php _e( '<b>Stop</b> the free version plugin.', 'aurora-heatmap' ); ?></li>
		<li><?php _e( '<b>Install</b> / <b>activate</b> the installer.', 'aurora-heatmap' ); ?></li>
		<li><?php _e( '<b>User registration</b> / <b>license agreement</b> / <b>update plugin</b>.', 'aurora-heatmap' ); ?></li>
		<li><?php _e( '<b>Delete</b> the free version plugin.', 'aurora-heatmap' ); ?></li>
		</ol>
		<?php
		// phpcs:enable WordPress.Security.EscapeOutput.UnsafePrintingFunction
	}

	/**
	 * Print settings
	 */
	protected function print_settings() {
		global $wp;

		$wp_public_query_vars = $wp->public_query_vars;
		sort( $wp_public_query_vars );

		$options_period = array(
			1 => __( '1 month', 'aurora-heatmap' ),
			3 => __( '3 months', 'aurora-heatmap' ),
			6 => __( '6 months', 'aurora-heatmap' ),
		);

		$options_accuracy = array(
			2 => __( 'High accuracy', 'aurora-heatmap' ),
			1 => __( 'Standard', 'aurora-heatmap' ),
		);

		$options_report_non_singular = array(
			1 => __( 'Report', 'aurora-heatmap' ),
			0 => __( 'Do not report', 'aurora-heatmap' ),
		);

		$options_drawing_points = array(
			0     => __( 'Unlimited', 'aurora-heatmap' ),
			10000 => __( '10000', 'aurora-heatmap' ),
			3000  => __( '3000', 'aurora-heatmap' ),
			1000  => __( '1000', 'aurora-heatmap' ),
		);

		$options_count_bar = array(
			1 => __( 'Show', 'aurora-heatmap' ),
			0 => __( 'Hide', 'aurora-heatmap' ),
		);

		$options_keep_url_hash = array(
			false => __( 'Integrated display', 'aurora-heatmap' ),
			true  => __( 'Individual display', 'aurora-heatmap' ),
		);

		$options_keep_url_query = array(
			false => array(
				__( 'Integrated display', 'aurora-heatmap' ),
				'url_query_include',
				__( 'Except for the following comma-separated parameters:', 'aurora-heatmap' ),
				implode( ', ', $this->options['url_query_include'] ),
			),
			true  => array(
				__( 'Individual display', 'aurora-heatmap' ),
				'url_query_exclude',
				__( 'Except for the following comma-separated parameters:', 'aurora-heatmap' ),
				implode( ', ', $this->options['url_query_exclude'] ),
			),
		);

		$options_content_end_marker = array(
			1 => __( 'Output', 'aurora-heatmap' ),
			0 => __( 'Do not output', 'aurora-heatmap' ),
		);

		$is_premium = 'basic' !== $this::PLAN && 'free' !== $this::PLAN;

		$disabled_in_free = $is_premium ? '' : 'disabled';
		?>
		<form id="ahm-options-form" method="post">
			<h2><?php esc_html_e( 'Data settings', 'aurora-heatmap' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Retension period', 'aurora-heatmap' ); ?><br>
						<span class="premium-options">Premium</span>
					</th>
					<td>
						<?php $this->print_radio_options( $options_period, 'period', $is_premium ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Accuracy', 'aurora-heatmap' ); ?>
						<span class="ahm-tooltip" tabindex="0">
							<span class="dashicons dashicons-editor-help"></span>
							<div>
								<p><?php esc_html_e( 'The default is high accuracy. If the count is not enough, try the standard mode.', 'aurora-heatmap' ); ?></p>
								<p><?php esc_html_e( 'For high accuracy, data of conditions narrowed down from the standard is saved.', 'aurora-heatmap' ); ?></p>
							</div>
						</span>
					</th>
					<td>
						<?php $this->print_radio_options( $options_accuracy, 'accuracy', true ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Non-singular pages', 'aurora-heatmap' ); ?>
						<span class="ahm-tooltip" tabindex="0">
							<span class="dashicons dashicons-editor-help"></span>
							<div>
								<p><?php esc_html_e( 'Control reporting for archive pages, error 404 pages, search result pages, etc.', 'aurora-heatmap' ); ?></p>
								<p><?php esc_html_e( 'The top page (first page of the front page) is always reported regardless of the setting.', 'aurora-heatmap' ); ?></p>
							</div>
						</span>
					</th>
					<td>
						<?php $this->print_radio_options( $options_report_non_singular, 'report_non_singular', true ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Ajax delay time', 'aurora-heatmap' ); ?>
						<span class="ahm-tooltip" tabindex="0">
							<span class="dashicons dashicons-editor-help"></span>
							<div>
								<p><?php esc_html_e( 'Milliseconds to delay Ajax communication from page load. The default is 3000.', 'aurora-heatmap' ); ?></p>
								<p><?php esc_html_e( 'For avoiding the record of breakaway at the automatic transfer source such as JavaScript redirect.', 'aurora-heatmap' ); ?></p>
							</div>
						</span>
					</th>
					<td>
						<input type="number" min="0" value="<?php echo esc_attr( $this->options['ajax_delay_time'] ); ?>" name="ajax_delay_time">
					</td>
				</tr>
			</table>
			<button type="submit" name="mode" class="button submit button-secondary" value="delete" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete all the heatmap data?', 'aurora-heatmap' ); ?>');"><?php esc_html_e( 'Bulk data deletion', 'aurora-heatmap' ); ?></button>
			<hr>

			<h2><?php esc_html_e( 'Display settings', 'aurora-heatmap' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Number of drawing points', 'aurora-heatmap' ); ?>
						<span class="ahm-tooltip" tabindex="0">
							<span class="dashicons dashicons-editor-help"></span>
							<div>
								<p><?php esc_html_e( 'The default is the latest 3,000 points. Adjust according to server performance.', 'aurora-heatmap' ); ?></p>
							</div>
						</span>
					</th>
					<td>
						<?php $this->print_radio_options( $options_drawing_points, 'drawing_points', true ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Count bar', 'aurora-heatmap' ); ?>
					</th>
					<td>
						<?php $this->print_radio_options( $options_count_bar, 'count_bar', true ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'URL hash', 'aurora-heatmap' ); ?>
						<span class="ahm-tooltip" tabindex="0">
							<span class="dashicons dashicons-editor-help"></span>
							<div>
								<p><?php _e( 'Whether to distinguish <code>#top</code> from <code>https://example.com/?p=123#top</code>.', 'aurora-heatmap' ); // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></p>
							</div>
						</span>
					</th>
					<td>
						<?php $this->print_radio_options( $options_keep_url_hash, 'keep_url_hash', true ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'URL parameter', 'aurora-heatmap' ); ?>
						<span class="ahm-tooltip" tabindex="0">
							<span class="dashicons dashicons-editor-help"></span>
							<div>
								<p><?php _e( 'Whether to distinguish <code>utm_source=google</code>, <code>utm_medium=organic</code>, etc. from <code>https://example.com/?p=123&utm_source=google&utm_medium=organic</code>.', 'aurora-heatmap' ); // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></p>
								<p><?php _e( 'In the premium version, you can specify exception parameters separated by commas. For example, <code>utm_source, utm_medium</code>.', 'aurora-heatmap' ); // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></p>
								<hr>
								<p><?php _e( 'The following parameters for WordPress are always displayed individually.', 'aurora-heatmap' ); // phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></p>
								<pre style="white-space: pre-wrap"><?php echo esc_html( implode( ', ', $wp_public_query_vars ) ); ?></pre>
							</div>
						</span>
					</th>
					<td>
						<?php $this->print_radio_options( $options_keep_url_query, 'keep_url_query', $is_premium ); ?>
					</td>
				</tr>
			</table>
			<?php if ( ! $is_premium ) { ?>
			<button type="submit" name="mode" class="button submit button-primary" value="save" id="ahm-options-save" onclick="return ( (this.form.accuracy[0].checked && ! this.form.accuracy[0].defaultChecked ) ? confirm( '<?php echo esc_js( __( 'Counted High accuracy mode data will remain as set, but standard mode data will be deleted. This cannot be restored.', 'aurora-heatmap' ) ); ?>' ) : true );"><?php esc_html_e( 'Save', 'aurora-heatmap' ); ?></button>
			<?php } ?>
			<hr>

			<h2><?php esc_html_e( 'Unread detection', 'aurora-heatmap' ); ?> <span style="font-size: 14px"><span class="premium-options">Premium</span></span></h2>
			<table class="form-table">
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Content end marker', 'aurora-heatmap' ); ?>
						<span class="ahm-tooltip" tabindex="0">
							<span class="dashicons dashicons-editor-help"></span>
							<div>
								<p><?php esc_html_e( 'Whether to output the content end marker to post / page. Long footers can be ignored.', 'aurora-heatmap' ); ?></p>
								<p><?php esc_html_e( 'The marker is a zero px element whose coordinates can be obtained.', 'aurora-heatmap' ); ?></p>
								<hr>
								<p><?php esc_html_e( 'If also required in the index / archive page, add the following HTML to the end of content block in the template.', 'aurora-heatmap' ); ?></p>
								<pre>&lt;div class="ahm-content-end-marker"&gt;&lt;/div&gt;</pre>
							</div>
						</span>
					</th>
					<td>
						<?php $this->print_radio_options( $options_content_end_marker, 'content_end_marker', $is_premium, $is_premium ? null : 0 ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Unread threshold', 'aurora-heatmap' ); ?>
						<span class="ahm-tooltip" tabindex="0">
							<span class="dashicons dashicons-editor-help"></span>
							<div>
								<p><?php esc_html_e( 'Specify the breakaway position that is considered unread as a percentage of the content height.', 'aurora-heatmap' ); ?></p>
								<p>
								<?php
									esc_html_e( 'For example, 25% count visits where only 25% of the content has been read.', 'aurora-heatmap' );
								?>
								</p>
								<p>
								<?php
									// translators: %s: default value.
									printf( esc_html__( 'The default is %s.', 'aurora-heatmap' ), '25%' );
									echo ' ';
									esc_html_e( 'Recommended is from 20% to 50%.', 'aurora-heatmap' );
								?>
								</p>
							</div>
						</span>
					</th>
					<td>
						<input type="range" min="0" max="100" value="<?php echo esc_attr( $this->options['unread_threshold'] ); ?>" name="unread_threshold" onchange="this.nextElementSibling.innerText = this.value + '%'" oninput="this.nextElementSibling.innerText = this.value + '%'" <?php echo esc_attr( $disabled_in_free ); ?>> <span><?php echo esc_attr( $this->options['unread_threshold'] ); ?>%</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Minimum number of accesses', 'aurora-heatmap' ); ?>
						<span class="ahm-tooltip" tabindex="0">
							<span class="dashicons dashicons-editor-help"></span>
							<div>
								<p><?php esc_html_e( 'Specify the minimum number of accesses per week per page to prevent false detection.', 'aurora-heatmap' ); ?></p>
								<p>
								<?php
									// translators: %s: default value.
									printf( esc_html__( 'The default is %s.', 'aurora-heatmap' ), '5' );
								?>
								</p>
							</div>
						</span>
					</th>
					<td>
						<input type="number" min="0" value="<?php echo esc_attr( $this->options['unread_minimum'] ); ?>" name="unread_minimum" <?php echo esc_attr( $disabled_in_free ); ?>>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Warning unread rate', 'aurora-heatmap' ); ?>
						<span class="ahm-tooltip" tabindex="0">
							<span class="dashicons dashicons-editor-help"></span>
							<div>
								<p><?php esc_html_e( 'Specify the unread rate to warn.', 'aurora-heatmap' ); ?></p>
								<p>
								<?php
									// translators: %s: default value.
									printf( esc_html__( 'The default is %s.', 'aurora-heatmap' ), '60%' );
								?>
								</p>
							</div>
						</span>
					</th>
					<td>
						<input type="number" min="0" max="100" value="<?php echo esc_attr( $this->options['unread_warning'] ); ?>" name="unread_warning" <?php echo esc_attr( $disabled_in_free ); ?>> %
					</td>
				</tr>
			</table>
			<?php if ( $is_premium ) { ?>
			<button type="submit" name="mode" class="button submit button-primary" value="save" id="ahm-options-save" onclick="return ( (this.form.accuracy[0].checked && ! this.form.accuracy[0].defaultChecked ) ? confirm( '<?php echo esc_js( __( 'Counted High accuracy mode data will remain as set, but standard mode data will be deleted. This cannot be restored.', 'aurora-heatmap' ) ); ?>' ) : true );"><?php esc_html_e( 'Save', 'aurora-heatmap' ); ?></button>
			<?php } ?>

			<?php wp_nonce_field( 'aurora_heatmap_action', 'aurora_heatmap_nonce' ); ?>
		</form>
		<?php
	}

	/**
	 * Get list items for Aurora_Heatmap_List
	 *
	 * @param array $param parameters. keys are event, search, pagenum, orderby, order.
	 */
	public function get_list_items( $param ) {
		global $wpdb;

		$param['order']   = strtolower( $param['order'] );
		$param['orderby'] = strtolower( $param['orderby'] );

		if ( ! in_array( $param['order'], array( 'desc', 'asc' ), true ) ) {
			$param['order'] = 'asc';
		}
		if ( ! in_array( $param['orderby'], array( 'page', 'click_pc', 'breakaway_pc', 'attention_pc', 'click_mobile', 'breakaway_mobile', 'attention_mobile' ), true ) ) {
			$param['orderby'] = 'page';
		}

		$param['orderby'] = str_replace( 'page', 'p.url2', $param['orderby'] );

		$where = '';
		if ( $param['search'] ) {
			$where = " WHERE page_id2 IN ( SELECT DISTINCT page_id2 FROM {$wpdb->prefix}ahm_events WHERE page_id IN ( SELECT id FROM {$wpdb->prefix}ahm_pages WHERE title LIKE %s OR url LIKE %s ) ) ";
		}

		$sql = "SELECT
					s.page_id2 AS page_id,
					p.url2 AS url,
					p.title,
					s.click_pc,
					s.breakaway_pc,
					s.attention_pc,
					s.click_mobile,
					s.breakaway_mobile,
					s.attention_mobile
				FROM (SELECT
						page_id2,
						COUNT( event = 16 OR NULL ) AS click_pc,
						COUNT( event = 32 OR NULL ) AS breakaway_pc,
						COUNT( event = 48 OR NULL ) AS attention_pc,
						COUNT( event = 17 OR NULL ) AS click_mobile,
						COUNT( event = 33 OR NULL ) AS breakaway_mobile,
						COUNT( event = 49 OR NULL ) AS attention_mobile
						FROM {$wpdb->prefix}ahm_events
						{$where}
						GROUP BY page_id2
					) as s
				LEFT JOIN {$wpdb->prefix}ahm_pages AS p ON p.id = s.page_id2
				WHERE (click_pc OR breakaway_pc OR attention_pc OR click_mobile OR breakaway_mobile OR attention_mobile)
				ORDER BY {$param['orderby']} {$param['order']}, p.url ASC
				LIMIT %d OFFSET %d";

		$args = array( $sql );
		if ( $param['search'] ) {
			$args[] = $param['search_title'];
			$args[] = $param['search_url'];
		}
		$args[] = $this::LIST_PER_PAGE;
		$args[] = $this::LIST_PER_PAGE * ( max( $param['pagenum'], 1 ) - 1 );

		$rows = $wpdb->get_results( $wpdb->prepare( ...$args ) ); // phpcs:ignore WordPress.DB

		if ( ! $rows ) {
			return array();
		}

		return $rows;
	}

	/**
	 * Get list total items for Aurora_Heatmap_List
	 *
	 * @param array $param parameters. keys are event, search, pagenum.
	 * @return int
	 */
	public function get_list_total_items( $param ) {
		global $wpdb;

		if ( ! $param['search'] ) {
			$query = "SELECT COUNT( DISTINCT page_id2 ) FROM {$wpdb->prefix}ahm_events";
		} else {
			$query = $wpdb->prepare(
				"SELECT COUNT( DISTINCT page_id2 ) FROM {$wpdb->prefix}ahm_events AS e INNER JOIN ( SELECT id FROM {$wpdb->prefix}ahm_pages WHERE title LIKE %s OR url LIKE %s ) AS p ON ( e.page_id2 = p.id )",
				$param['search_title'],
				$param['search_url']
			);
		}
		return (int) $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Filter Hook for wp_is_mobile
	 *
	 * @param bool $is_mobile Whether the request is from a mobile device or not.
	 * @return bool
	 */
	public function wp_is_mobile( $is_mobile ) {
		$ahm = filter_input( INPUT_GET, 'aurora-heatmap' );
		if ( $ahm && preg_match( '/^\\w+_mobile-\\d+$/', $ahm ) ) {
			return true;
		}
		return $is_mobile;
	}

	/**
	 * Save heatmap from Ajax
	 */
	public function ajax_aurora_heatmap() {
		global $wpdb;
		header( 'Content-Type: application/json; charset=UTF-8' );
		if ( ! $this->can_report() ) {
			echo wp_json_encode( array( 'error' => 'cannot report' ) );
			die();
		}

		$dataset = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( ! $dataset ) {
			echo wp_json_encode( array( 'error' => 'no data' ) );
			die();
		}

		$url_filter = $this->make_url_filter( false );
		$url        = filter_input( INPUT_POST, 'url', FILTER_VALIDATE_URL );
		if ( $url ) {
			$url = $url_filter( $url );
		}
		if ( ! $url ) {
			echo wp_json_encode( array( 'error' => 'no url' ) );
			die();
		}

		$title = filter_input( INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS );

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ahm_pages WHERE url = %s", $url ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		$tz = self::wp_timezone();

		if ( ! $row ) {
			$url2_filter = $this->make_url_filter( true );
			$url2        = $url2_filter( $url );

			$now  = ( new DateTime( '', $tz ) )->format( 'Y-m-d H:i:s' );
			$data = array(
				'url'       => $url,
				'url2'      => $url2,
				'title'     => $title,
				'insert_at' => $now,
				'update_at' => $now,
			);
			$wpdb->insert( "{$wpdb->prefix}ahm_pages", $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$data['id'] = $wpdb->insert_id;
			$row        = (object) $data;
		} elseif ( $row->title !== $title ) {
			$wpdb->update( "{$wpdb->prefix}ahm_pages", array( 'title' => $title ), array( 'id' => $row->id ), array( '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		$page_id2 = $wpdb->get_var( $wpdb->prepare( "SELECT page_id2 FROM {$wpdb->prefix}ahm_events AS e INNER JOIN (SELECT id FROM {$wpdb->prefix}ahm_pages WHERE url2 = %s) AS p ON e.page_id2 = p.id LIMIT 1", $row->url2 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$page_id  = $row->id;
		$page_id2 = $page_id2 ? $page_id2 : $page_id;

		$default = array(
			'x'      => 0,
			'y'      => 0,
			'width'  => 0,
			'height' => 0,
		);

		$events = $this::EVENT_NAMES;
		$to_int = function( $var ) {
			$var = filter_var( $var, FILTER_VALIDATE_INT );
			return $var ? $var : 0;
		};

		$count = 0;
		foreach ( $dataset as $data ) {
			if ( ! isset( $data['event'] ) || ! isset( $events[ $data['event'] ] ) ) {
				continue;
			}

			$event_id = $events[ $data['event'] ];

			$data = array_merge( $default, $data );
			$add  = array(
				'event'     => $event_id,
				'page_id'   => $page_id,
				'page_id2'  => $page_id2,
				'x'         => $to_int( $data['x'] ),
				'y'         => $to_int( $data['y'] ),
				'width'     => $to_int( $data['width'] ),
				'height'    => $to_int( $data['height'] ),
				'insert_at' => ( new DateTime( sprintf( '%d second', $to_int( $data['time'] ) ), $tz ) )->format( 'Y-m-d H:i:s' ),
			);

			// Skip if low accuracy.
			if ( $this->get_accuracy( $add['width'], $event_id ) < $this->options['accuracy'] ) {
				continue;
			}

			$wpdb->insert( "{$wpdb->prefix}ahm_events", $add ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$count++;
		}
		echo wp_json_encode( array( 'count' => $count ), JSON_NUMERIC_CHECK );
		die();
	}

	/**
	 * Whether user can settings
	 */
	protected function can_settings() {
		static $memo;
		if ( ! isset( $memo ) ) {
			$memo = current_user_can( 'manage_options' );
		}
		return $memo;
	}

	/**
	 * Whether user can report
	 *
	 * @return bool
	 */
	protected function can_report() {
		static $memo;
		if ( ! isset( $memo ) ) {
			$memo = ! current_user_can( 'manage_options' );
		}
		return $memo;
	}

	/**
	 * Whether user can view
	 *
	 * @return bool
	 */
	protected function can_view() {
		static $memo;
		if ( ! isset( $memo ) ) {
			$memo = current_user_can( 'manage_options' );
		}
		return $memo;
	}

	/**
	 * Action hook for wp_enqueue_scripts
	 */
	public function wp_enqueue_scripts() {
		if ( $this->user ) {
			$user = wp_get_current_user();
			wp_set_current_user( $this->user->ID );
		}

		wp_enqueue_style( 'aurora-heatmap', plugins_url( 'style.css', __FILE__ ), array(), $this::VERSION );
		$this->enqueue_report();
		$this->enqueue_view();

		if ( $this->user ) {
			wp_set_current_user( $user->ID );
		}
	}

	/**
	 * Is reporting page
	 *
	 * @return bool
	 */
	protected function is_reporting_page() {
		if ( ! $this->options['report_non_singular'] && ! is_singular() ) {
			if ( is_front_page() && ! is_paged() ) {
				return true;
			}
			return false;
		}

		return true;
	}

	/**
	 * In descendant category
	 *
	 * @param array  $cats  Array of category IDs.
	 * @param object $_post Post object.
	 * @return bool
	 */
	protected function in_descendant_category( $cats, $_post = null ) {
		if ( in_category( $cats, $_post ) ) {
			return true;
		}

		foreach ( (array) $cats as $cat ) {
			$descendants = get_term_children( (int) $cat, 'category' );
			if ( $descendants && in_category( $descendants, $_post ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Euqueue Aurora Heatmap reporter
	 *
	 * @param  bool $is_insert For ahm_scripts filter/shortcode.
	 * @return string
	 */
	protected function enqueue_report( $is_insert = false ) {
		$q = '';
		if ( ! $this->can_report() ) {
			return $q;
		}

		if ( ! apply_filters( 'aurora_heatmap_is_reporting_page', $this->is_reporting_page() ) ) {
			return $q;
		}

		$aurora_heatmap_reporter = array(
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'action'          => 'aurora_heatmap',
			'interval'        => 10,
			'stacks'          => 10,
			'reports'         => implode( ',', array_keys( $this::EVENT_NAMES ) ),
			'debug'           => (int) $this->is_debug,
			'ajax_delay_time' => (int) $this->options['ajax_delay_time'],
		);

		$src_mobile_detect = plugins_url( 'js/mobile-detect.min.js', __FILE__ );
		$src_reporter      = plugins_url( 'js/reporter.js', __FILE__ );

		if ( ! $is_insert ) {
			wp_register_script( 'mobile-detect', $src_mobile_detect, array(), '1.4.4', true );
			wp_enqueue_script(
				'aurora-heatmap-reporter',
				$src_reporter,
				array( 'jquery', 'mobile-detect' ),
				$this::VERSION,
				false
			);
			wp_localize_script( 'aurora-heatmap-reporter', 'aurora_heatmap_reporter', $aurora_heatmap_reporter );
		} else {
			// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
			$q = '<script><!--' . PHP_EOL .
				'var aurora_heatmap_reporter=' . wp_json_encode( $aurora_heatmap_reporter ) . ';' . PHP_EOL .
				'// --></script>' . PHP_EOL .
				'<script src="' . esc_attr( $src_mobile_detect ) . '"></script>' . PHP_EOL .
				'<script src="' . esc_attr( $src_reporter ) . '"></script>' . PHP_EOL;
			// phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedScript
		}

		return $q;
	}

	/**
	 * Enqueue Aurora Heatmap viewer
	 *
	 * @param  bool $is_insert For ahm_scripts filter/shortcode.
	 * @return string
	 */
	protected function enqueue_view( $is_insert = false ) {
		$q = '';
		if ( ! $this->can_view() ) {
			return $q;
		}

		$param = explode( '-', filter_input( INPUT_GET, 'aurora-heatmap' ), 2 );
		if ( 2 !== count( $param ) || ! array_key_exists( $param[0], $this::EVENT_NAMES ) ) {
			return $q;
		}

		$event   = $param[0];
		$page_id = (int) $param[1];

		if ( ! $page_id || ! array_key_exists( $event, $this::EVENT_NAMES ) ) {
			return $q;
		}

		$view_width  = $this::VIEW_WIDTH;
		$events      = $this::EVENT_NAMES;
		$event_id    = $events[ $event ];
		$access_from = $event_id & 15;

		$data = $this->get_heatmap_data( $page_id, $event_id );

		if ( ! $data ) {
			$data = array();
		}

		$serialize_precision = ini_get( 'serialize_precision' );
		ini_set( 'serialize_precision', '-1' ); // phpcs:ignore WordPress.PHP.IniSet.Risky

		$aurora_heatmap_viewer = array(
			'event'     => $event,
			'width'     => $view_width[ $access_from ],
			'count_bar' => $this->options['count_bar'],
			'data'      => wp_json_encode( $data, JSON_NUMERIC_CHECK ),
		);

		$src_chroma = plugins_url( 'js/chroma.min.js', __FILE__ );
		$src_h337   = plugins_url( 'js/h337.js', __FILE__ );
		$src_viewer = plugins_url( 'js/viewer.js', __FILE__ );

		if ( $is_insert ) {
			// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
			$q = '<script><!--' . PHP_EOL .
				'var aurora_heatmap_viewer=' . wp_json_encode( $aurora_heatmap_viewer ) . ';' . PHP_EOL .
				'// --></script>' . PHP_EOL .
				'<script src="' . esc_attr( $src_chroma ) . '"></script>' . PHP_EOL .
				'<script src="' . esc_attr( $src_h337 ) . '"></script>' . PHP_EOL .
				'<script src="' . esc_attr( $src_viewer ) . '"></script>' . PHP_EOL;
			// phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedScript
		} else {
			wp_enqueue_script(
				'chroma',
				$src_chroma,
				array(),
				$this::VERSION,
				false
			);
			wp_enqueue_script(
				'aurora-heatmap-drawer',
				$src_h337,
				array(),
				$this::VERSION,
				false
			);
			wp_enqueue_script(
				'aurora-heatmap-viewer',
				$src_viewer,
				array( 'jquery' ),
				$this::VERSION,
				false
			);
			wp_localize_script( 'aurora-heatmap-viewer', 'aurora_heatmap_viewer', $aurora_heatmap_viewer );
		}

		ini_set( 'serialize_precision', $serialize_precision ); // phpcs:ignore WordPress.PHP.IniSet.Risky

		return $q;
	}

	/**
	 * Daily cron
	 */
	public function aurora_heatmap_cron_daily() {
		$this->setup_daily_cron();
		$this->delete_old_data( $this->options['period'] );
	}

	/**
	 * Option checker
	 *
	 * @param array $new_options New options.
	 * @param array $old         Old options, otherwise skip the trigger on change.
	 */
	public function option_checker( $new_options, $old = null ) {
		$default   = array(
			'activated_ver'       => null,
			'period'              => 1,
			'accuracy'            => 2,
			'report_non_singular' => 1,
			'drawing_points'      => 3000,
			'count_bar'           => 1,
			'keep_url_query'      => 0,
			'keep_url_hash'       => 0,
			'ajax_delay_time'     => 3000,
		);
		$overwrite = array(
			'url_query_include' => array(),
			'url_query_exclude' => array(),
			'unread_threshold'  => 25,
			'unread_minimum'    => 5,
			'unread_warning'    => 60,
		);

		// Merge.
		$options = array_merge( $default, $new_options, $overwrite );

		// Validate.
		if ( $options['activated_ver'] ) {
			$options['activated_ver'] = (string) $options['activated_ver'];
		} elseif ( isset( $old['activated_ver'] ) ) {
			$options['activated_ver'] = $old['activated_ver'];
		}

		$options['period'] = min( 6, max( 1, intval( $options['period'] ) ) );

		$options['accuracy'] = min( 2, max( 1, intval( $options['accuracy'] ) ) );

		$options['report_non_singular'] = intval( (bool) $options['report_non_singular'] );

		$options['drawing_points'] = max( 0, intval( $options['drawing_points'] ) );

		$options['count_bar'] = intval( (bool) $options['count_bar'] );

		$options['keep_url_query'] = intval( (bool) $options['keep_url_query'] );

		$options['keep_url_hash'] = intval( (bool) $options['keep_url_hash'] );

		$options['ajax_delay_time'] = max( 0, intval( $options['ajax_delay_time'] ) );

		// Trigger on change.
		if ( $old ) {
			$backup_options = $this->options;
			$this->options  = $options;
			$old            = array_merge( $default, $old, $overwrite );
			if ( 2 === $options['accuracy'] && isset( $old['accuracy'] ) && intval( $old['accuracy'] ) <= 1 ) {
				$this->delete_low_accuracy_data( 1 );
			}
			if (
				$options['keep_url_query'] !== $old['keep_url_query'] ||
				$options['keep_url_hash'] !== $old['keep_url_hash'] ||
				$options['url_query_include'] !== $old['url_query_include'] ||
				$options['url_query_exclude'] !== $old['url_query_exclude']
			) {
				$this->update_url2();
			}
			$this->options = $backup_options;
		}

		// Checked options.
		return $options;
	}

	/**
	 * Save options
	 */
	protected function save_options() {
		$options = array(
			'accuracy'            => filter_input( INPUT_POST, 'accuracy', FILTER_VALIDATE_INT ),
			'report_non_singular' => filter_input( INPUT_POST, 'report_non_singular', FILTER_VALIDATE_INT ),
			'drawing_points'      => filter_input( INPUT_POST, 'drawing_points', FILTER_VALIDATE_INT ),
			'count_bar'           => filter_input( INPUT_POST, 'count_bar', FILTER_VALIDATE_INT ),
			'keep_url_query'      => filter_input( INPUT_POST, 'keep_url_query', FILTER_VALIDATE_BOOLEAN ),
			'keep_url_hash'       => filter_input( INPUT_POST, 'keep_url_hash', FILTER_VALIDATE_BOOLEAN ),
			'ajax_delay_time'     => filter_input( INPUT_POST, 'ajax_delay_time', FILTER_VALIDATE_INT ),
		);

		$this->options->save( $options );
	}

	/**
	 * Update url2
	 */
	protected function update_url2() {
		global $wpdb;
		$id   = array();
		$id2  = array();
		$url  = array();
		$dict = array();

		$url2_filter = $this->make_url_filter( true );
		foreach ( $wpdb->get_results( "SELECT id, url FROM {$wpdb->prefix}ahm_pages" ) as $row ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$id[]  = $row->id;
			$url2  = $url2_filter( $row->url );
			$url[] = $url2;
			if ( ! isset( $dict[ $url2 ] ) ) {
				$dict[ $url2 ] = $row->id;
			}
			$id2[] = $dict[ $url2 ];
		}
		$count = count( $id );
		if ( ! $count ) {
			return;
		}

		$d = implode( ',', array_fill( 0, $count, '%d' ) );
		$s = implode( ',', array_fill( 0, $count, '%s' ) );
		$v = array_merge( $id, $url );
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}ahm_pages SET url2 = ELT( FIELD( id, {$d} ), {$s} )", $v ) ); // phpcs:ignore WordPress.DB
		$v = array_merge( $id, $id2 );
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}ahm_events SET page_id2 = ELT( FIELD( page_id, {$d} ), {$d} )", $v ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Get accuracy
	 *
	 * @param int $width    Width of browser.
	 * @param int $event_id Event id.
	 */
	protected function get_accuracy( $width, $event_id ) {
		$ranges = $this->ar[ $event_id & 15 ];
		$keys   = array_keys( $ranges );
		rsort( $keys );
		foreach ( $keys as $key ) {
			if ( $ranges[ $key ][0] <= $width && $width <= $ranges[ $key ][1] ) {
				return $key;
			}
		}
		return 0;
	}

	/**
	 * Make URL filter
	 *
	 * @param bool $use_options Use filtering options or not.
	 * @return callable
	 */
	protected function make_url_filter( $use_options = false ) {
		global $wp;
		$query_filter    = null;
		$fragment_filter = null;
		if ( $use_options ) {
			if ( ! $this->options['keep_url_query'] ) {
				$include      = array_flip( $wp->public_query_vars );
				$query_filter = function( $query ) use ( $include ) {
					return array_intersect_key( $query, $include );
				};
			}
			if ( ! $this->options['keep_url_hash'] ) {
				$fragment_filter = function() {
					return '';
				};
			}
		}
		return function( $url ) use ( $query_filter, $fragment_filter ) {
			return $this->rebuild_url( $url, $query_filter, $fragment_filter );
		};
	}

	/**
	 * Rebuild URL
	 *
	 * @param string   $url             URL.
	 * @param callable $query_filter    Filter callback for query string.
	 * @param callable $fragment_filter Fitter callback for fragment.
	 * @return string
	 */
	protected function rebuild_url( $url, $query_filter = null, $fragment_filter = null ) {
		$p = wp_parse_url( $url );
		if ( ! $p ) {
			return false;
		}

		/**
		 * From PHP Manual comment.
		 *
		 * @link https://www.php.net/manual/ja/function.parse-url.php#106731i
		 */
		$scheme = isset( $p['scheme'] ) ? $p['scheme'] . '://' : '';
		$host   = isset( $p['host'] ) ? $p['host'] : '';
		$port   = isset( $p['port'] ) ? ':' . $p['port'] : '';
		$user   = isset( $p['user'] ) ? $p['user'] : '';
		$pass   = isset( $p['pass'] ) ? ':' . $p['pass'] : '';
		$pass   = ( $user || $pass ) ? $pass . '@' : '';
		$path   = isset( $p['path'] ) ? $p['path'] : '';

		// Query string.
		if ( isset( $p['query'] ) ) {
			parse_str( $p['query'], $query );
			if ( $query_filter ) {
				$query = $query_filter( $query );
			}
			$query = $query ? '?' . http_build_query( $query ) : '';
		} else {
			$query = '';
		}

		// Fragment.
		$fragment = isset( $p['fragment'] ) ? $p['fragment'] : '';
		if ( $fragment && $fragment_filter ) {
			$fragment = $fragment_filter( $fragment );
		}
		if ( $fragment ) {
			$fragment = '#' . $fragment;
		}

		return "$scheme$user$pass$host$port$path$query$fragment";
	}

	/**
	 * Get heatmap data
	 *
	 * @param int $page_id  Target URL id.
	 * @param int $event_id Event_id.
	 * @return array
	 */
	protected function get_heatmap_data( $page_id, $event_id ) {
		switch ( $event_id ) {
			case $this::CLICK_PC:
			case $this::CLICK_MOBILE:
				return $this->get_click_heatmap( $page_id, $event_id );
		}
		return array();
	}

	/**
	 * Get click heatmap
	 *
	 * @param int $page_id  Target URL id.
	 * @param int $event_id Event_id.
	 * @return array
	 */
	protected function get_click_heatmap( $page_id, $event_id ) {
		global $wpdb;

		$sql  = "SELECT x, y FROM {$wpdb->prefix}ahm_events WHERE event = %d AND page_id2 = %d";
		$args = array( $sql, $event_id, $page_id );

		if ( 0 < $this->options['drawing_points'] ) {
			$args[0] .= ' ORDER BY insert_at DESC LIMIT %d';
			$args[]   = $this->options['drawing_points'];
		}

		$rows = $wpdb->get_results( $wpdb->prepare( ...$args ) ); // phpcs:ignore WordPress.DB

		return array(
			'points' => $rows,
			'counts' => $this->get_vertical_statistics( $page_id, $event_id, 40 ),
		);
	}

	/**
	 * Get page height
	 *
	 * @param int $page_id  Target URL id.
	 * @param int $event_id Event_id.
	 * @return int
	 */
	protected function get_page_height( $page_id, $event_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$height = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX( height ) FROM {$wpdb->prefix}ahm_events
				WHERE event = %d AND page_id2 = %d AND height <= ( SELECT AVG( height ) + 2 * STD( height ) FROM {$wpdb->prefix}ahm_events WHERE event = %d AND page_id2 = %d )",
				$event_id,
				$page_id,
				$event_id,
				$page_id
			)
		);

		if ( ! $height ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$height = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX( height ) FROM {$wpdb->prefix}ahm_events WHERE event = %d AND page_id2 = %d",
					$event_id,
					$page_id
				)
			);
		}

		return $height;
	}

	/**
	 * Get vertical statistics
	 *
	 * @param int $page_id   Target URL id.
	 * @param int $event_id  Event_id.
	 * @param int $bandwidth Bandwidth of histogram.
	 * @return array
	 */
	protected function get_vertical_statistics( $page_id, $event_id, $bandwidth ) {
		global $wpdb;

		$page_height = $this->get_page_height( $page_id, $event_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT COUNT(*) AS count, y DIV %d AS iy
				   FROM {$wpdb->prefix}ahm_events
				  WHERE event = %d
				    AND page_id2 = %d
				  GROUP BY iy",
				$bandwidth,
				$event_id,
				$page_id
			)
		);

		if ( ! $rows ) {
			return array();
		}

		$count  = ceil( $page_height / $bandwidth );
		$counts = array_fill( 0, $count, 0 );
		foreach ( $rows as $row ) {
			if ( $row->iy < $count ) {
				$counts[ $row->iy ] = $row->count;
			}
		}
		return $counts;
	}

	/**
	 * Delete data for Aurora_Heatmap_List
	 *
	 * @param array $pageid   Array of pageid.
	 */
	public function delete_data( $pageid ) {
		global $wpdb;
		$in = implode( ',', array_fill( 0, count( $pageid ), '%d' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ahm_events WHERE page_id2 IN ({$in})", $pageid ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Delete low accuracy data
	 *
	 * @param int $accuracy delete accuracy.
	 */
	protected function delete_low_accuracy_data( $accuracy ) {
		global $wpdb;
		$ar = $this->ar;
		$i  = $accuracy + 1;
		if ( $i < 1 || 2 < $i ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}ahm_events
				WHERE ( MOD( event, 16 ) = 0 AND ( width < %d OR %d < width ) ) OR
					  ( MOD( event, 16 ) = 1 AND ( width < %d OR %d < width ) )",
				$ar[0][ $i ][0],
				$ar[0][ $i ][1],
				$ar[1][ $i ][0],
				$ar[1][ $i ][1]
			)
		);

		$this->cleanup_pages();
	}

	/**
	 * Delete old events and not used pages.
	 *
	 * @param int $month keep months.
	 */
	protected function delete_old_data( $month ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ahm_events WHERE insert_at < ADDDATE( NOW(), INTERVAL - %d MONTH )", $month ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$this->cleanup_pages();
	}

	/**
	 * Clean up pages
	 */
	protected function cleanup_pages() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}ahm_pages WHERE NOT EXISTS ( SELECT * FROM {$wpdb->prefix}ahm_events AS e WHERE {$wpdb->prefix}ahm_pages.id = e.page_id )" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$this->update_url2();
	}

	/**
	 * Delete all data
	 */
	protected function delete_all() {
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}ahm_events" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}ahm_pages" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

}

/* vim: set ts=4 sw=4 sts=4 noet: */
