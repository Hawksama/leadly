<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to Contact Form 7 data
 *
 * @class ES_Data_Type_CF7_Data
 */
class ES_Data_Type_CF7_Data extends ES_Workflow_Data_Type {

	/**
	 * Validate data
	 *
	 * @param array $item
	 * @return bool
	 */
	public function validate( $item ) {
		// Check if we have an array with email field not being empty.
		if ( ! is_array( $item ) ||  empty( $item['email'] )  ) {
			return false;
		}
		return true;
	}


	/**
	 * Return id from given data item object
	 * 
	 * @param $item
	 * @return mixed
	 */
	public function compress( $item ) {
		// Return the same $item as submitted contact form aren't saved in DB for later user.
		return $item;
	}

	/**
	 * Return data item object from given id
	 *
	 * @param $compressed_item
	 * @param $compressed_data_layer
	 * @return WP_Comment|false
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {
		if ( ! $compressed_item ) {
			return false;
		}

		return $compressed_item;
	}

	/**
	 * Return data item object from given data.
	 *
	 * @param array
	 * @return array
	 */
	public function get_data( $form_saved_data = array() ) {
		
		$data = array();
		if ( is_array( $form_saved_data ) && ! empty( $form_saved_data['email'] ) ) {
			$data           = $form_saved_data;
			$data['source'] = 'cf7'; // Add source to cf7 saved data.
		}

		return $data;
	}
}
