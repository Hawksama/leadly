<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to Woocommerce Order data
 *
 * @class ES_Data_Type_WC_Order
 */
class ES_Data_Type_WC_Order extends ES_Workflow_Data_Type {

	/**
	 * Validate data
	 *
	 * @param $item
	 * @return bool
	 */
	public function validate( $item ) {
		return is_subclass_of( $item, 'WC_Abstract_Order' );
	}


	/**
	 * Return id from given data item object
	 *
	 * @param \WC_Order $item
	 * @return mixed
	 */
	public function compress( $item ) {
		return $item->get_id();
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

		$order = wc_get_order( $id );

		if ( ! $order || 'trash' === $order->get_status() ) {
			return false;
		}

		return $order;
	}

	/**
	 * Return data item object from given data.
	 *
	 * @param \WC_Order $item
	 * @return array
	 */
	public function get_data( $wc_order ) {

		$data = array();

		if ( is_subclass_of( $wc_order, 'WC_Abstract_Order' ) ) {
			$email      = $wc_order->get_billing_email();
			$first_name = $wc_order->get_billing_first_name();
			$last_name  = $wc_order->get_billing_last_name();

			if ( ! empty( $email ) ) {

				//Prepare data
				$data = array(
					'first_name' => $first_name,
					'last_name'  => $last_name,
					'source'     => 'woocommerce',
					'email'      => $email
				);
			}
		}

		return $data;
	}

}
