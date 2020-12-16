<?php
/**
 * Workflow data type forminator forms data
 *
 * @since       4.4.6
 * @version     1.0
 * @package     Email Subscribers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to forminator forms data
 *
 * @class ES_Data_Type_Forminator_Forms_Data
 * 
 * @since 4.4.6
 */
class ES_Data_Type_Forminator_Forms_Data extends ES_Data_Type_Form_Data {

	/**
	 * Return data item object from given data.
	 *
	 * @param array $form_saved_data Forminator forms saved data.
	 * 
	 * @return array $data Processed data
	 * 
	 * @since 4.4.6
	 */
	public function get_data( $form_saved_data = array() ) {

		$data = array();
		if ( is_array( $form_saved_data ) && ! empty( $form_saved_data['email'] ) ) {
			$data           = $form_saved_data;
			$data['source'] = 'forminator'; // Add source to forminator saved data.
		}

		return $data;
	}
}
