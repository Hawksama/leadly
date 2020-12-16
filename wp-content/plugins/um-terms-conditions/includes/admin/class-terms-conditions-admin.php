<?php
namespace um_ext\um_terms_conditions\admin;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Terms_Conditions_Admin
 * @package um_ext\um_terms_conditions\admin
 */
class Terms_Conditions_Admin {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'um_admin_custom_register_metaboxes', array( &$this, 'add_metabox_register' ) );
	}


	/**
	 * @param $action
	 */
	function add_metabox_register( $action ) {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_meta_box(
			"um-admin-form-register_terms-conditions{" . um_terms_conditions_path . "}",
			__( 'Terms & Conditions', 'um-terms-conditions' ),
			array( UM()->metabox(), 'load_metabox_form' ),
			'um_form',
			'side',
			'default'
		);
	}

}
