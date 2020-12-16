<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to Give Data
 *
 * @class ES_Data_Type_Give_Data
 */
class ES_Data_Type_Give_Data extends ES_Workflow_Data_Type {

	/**
	 * Validate data
	 *
	 * @param $item
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
	 * @return \WP_Comment|false
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
			$data['source'] = 'give'; // Add source to give saved data.
		}

		return $data;
	}
}
