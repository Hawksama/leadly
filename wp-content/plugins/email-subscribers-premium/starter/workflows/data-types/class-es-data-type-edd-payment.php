<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to Easy Digital Download Payment data
 *
 * @class ES_Data_Type_EDD_Payment
 */
class ES_Data_Type_EDD_Payment extends ES_Workflow_Data_Type {

	/**
	 * Validate data
	 *
	 * @param $item
	 * @return bool
	 */
	public function validate( $item ) {
		return $item instanceof EDD_Payment;
	}


	/**
	 * Return id from given data item object
	 * 
	 * @param \EDD_Payment $item
	 * @return mixed
	 */
	public function compress( $item ) {
		return $item->ID;
	}


	/**
	 * Return data item object from given id
	 *
	 * @param $compressed_item
	 * @param $compressed_data_layer
	 * @return mixed
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {
		$id = ES_Clean::id( $compressed_item );

		if ( ! $id ) {
			return false;
		}

		$edd_payment = edd_get_payment( $compressed_item );

		if ( ! $edd_payment || ! $edd_payment instanceof EDD_Payment ) {
			return false;
		}

		return $edd_payment;
	}

	/**
	 * Return data item object from given data.
	 *
	 * @param \EDD_Payment $item
	 * @return array
	 */
	public function get_data( $edd_payment ) {

		$data = array();

		if ( $edd_payment instanceof EDD_Payment ) {
			$email = $edd_payment->email;

			if ( ! empty( $email ) ) {

				$user_info  = $edd_payment->user_info;
				$first_name = '';
				$last_name  = '';

				if ( ! empty( $user_info['first_name'] ) ) {
					$first_name = $user_info['first_name'];
				}

				if ( ! empty( $user_info['last_name'] ) ) {
					$last_name = $user_info['last_name'];
				}
				//Prepare data
				$data = array(
					'first_name' => $first_name,
					'last_name'  => $last_name,
					'source'     => 'edd',
					'email'      => $email
				);
			}
		}

		return $data;
	}

}
