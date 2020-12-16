<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to Comments Data Type 
 *
 * @class Data_Type_Comment
 */
class ES_Data_Type_Comment extends ES_Workflow_Data_Type {

	/**
	 * Validate data
	 *
	 * @param $item
	 * @return bool
	 */
	public function validate( $item ) {
		return $item instanceof WP_Comment;
	}


	/**
	 *  Return id from given data item object
	 * 
	 * @param $item
	 * @return mixed
	 */
	public function compress( $item ) {
		return $item->comment_ID;
	}


	/**
	 * Return data item object from given data.
	 *
	 * @param $compressed_item
	 * @param $compressed_data_layer
	 * @return \WP_Comment|false
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {
		if ( ! $compressed_item ) {
			return false;
		}

		return get_comment( $compressed_item );
	}

	/**
	 * Return data item object from given data.
	 *
	 * @param WP_Comment $comment
	 * @return array
	 */
	public function get_data( $comment ) {
		
		$data = array();

		if ( $comment instanceof WP_Comment ) {
			$email = ! empty( $comment->comment_author_email ) ? $comment->comment_author_email : '';
			// Found email?
			if ( ! empty( $email ) ) {
				$comment_author = ! empty( $comment->comment_author ) ? $comment->comment_author : '';
				$data           = array(
					'name'   => $comment_author,
					'email'  => $email,
					'source' => 'comment'
				);
			}
		}

		return $data;
	}
}
