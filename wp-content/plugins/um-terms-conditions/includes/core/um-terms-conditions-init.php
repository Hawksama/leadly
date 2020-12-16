<?php if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class UM_Terms_Conditions
 */
class UM_Terms_Conditions {


	/**
	 * @var
	 */
	private static $instance;


	/**
	 * @return UM_Terms_Conditions
	 */
	static public function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// Global for backwards compatibility.
		$GLOBALS['um_terms_conditions'] = $this;
		add_filter( 'um_call_object_Terms_Conditions', array( &$this, 'get_this' ) );

		$this->includes();
	}


	/**
	 * @return $this
	 */
	function get_this() {
		return $this;
	}


	/**
	 * @return um_ext\um_terms_conditions\admin\Terms_Conditions_Admin()
	 */
	function admin_handlers() {
		if ( empty( UM()->classes['um_terms_conditions_admin'] ) ) {
			UM()->classes['um_terms_conditions_admin'] = new um_ext\um_terms_conditions\admin\Terms_Conditions_Admin();
		}
		return UM()->classes['um_terms_conditions_admin'];
	}


	/**
	 * @return um_ext\um_terms_conditions\core\Terms_Conditions_Public()
	 */
	function public_handlers() {
		if ( empty( UM()->classes['um_terms_conditions_public'] ) ) {
			UM()->classes['um_terms_conditions_public'] = new um_ext\um_terms_conditions\core\Terms_Conditions_Public();
		}
		return UM()->classes['um_terms_conditions_public'];
	}


	/**
	 * Load the required dependencies for this plugin.
	 *
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function includes() {
		if ( UM()->is_request( 'admin' ) ) {
			$this->admin_handlers();
		}

		$this->public_handlers();
	}
}

//create class var
add_action( 'plugins_loaded', 'um_init_terms_conditions', -10, 1 );
function um_init_terms_conditions() {
	if ( function_exists( 'UM' ) ) {
		UM()->set_class( 'Terms_Conditions', true );
	}
}