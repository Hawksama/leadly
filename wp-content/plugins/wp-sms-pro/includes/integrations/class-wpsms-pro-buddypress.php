<?php

namespace WP_SMS\Pro;

use WP_SMS\Option;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class BuddyPress {

	public $sms;
	public $options;

	public function __construct() {
		global $sms;

		$this->sms     = $sms;
		$this->options = Option::getOptions( true );

		if ( isset( $this->options['bp_mobile_field'] ) ) {
			global $wpdb;
			$result = $wpdb->query( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bp_xprofile_fields WHERE name = %s", 'Mobile' ) );

			if ( ! $result ) {
				add_action( 'bp_init', array( $this, 'add_field' ) );
			}

			// Enable international intel input if enabled
			if ( Option::getOption( 'international_mobile' ) ) {
				add_filter( 'bp_xprofile_field_edit_html_elements', array( $this, 'add_attribute' ), 11 );
			}
		}

		if ( isset( $this->options['bp_mention_enable'] ) ) {
			add_action( 'bp_activity_sent_mention_email', array( $this, 'mention_notification' ), 10, 5 );
		}

		if ( isset( $this->options['bp_comments_reply_enable'] ) ) {
			add_action( 'bp_activity_sent_reply_to_reply_notification', array(
				$this,
				'comments_reply_notification'
			), 10, 3 );
		}

		if ( isset( $this->options['bp_comments_activity_enable'] ) ) {
			add_action( 'bp_activity_sent_reply_to_update_notification', array(
				$this,
				'comments_activity_notification'
			), 10, 3 );
		}
	}

	// Mobile field
	public function add_field() {
		global $bp;
		$xfield_args = array(
			'field_group_id' => 1,
			'name'           => 'Mobile',
			'description'    => __( 'Your mobile number to receive SMS updates', 'wp-sms-pro' ),
			'can_delete'     => true,
			'field_order'    => 1,
			'is_required'    => false,
			'type'           => 'textbox'
		);

		xprofile_insert_field( $xfield_args );
	}

	// Buddypress mention
	public function mention_notification( $activity, $subject, $message, $content, $receiver_user_id ) {
		// Get user mobile
		$user_mobile = $this->get_mobile( $receiver_user_id );

		// Check the mobile
		if ( ! $user_mobile ) {
			return;
		}

		$user_posted    = get_userdata( $activity->user_id );
		$user_receiver  = get_userdata( $receiver_user_id );
		$template_vars  = array(
			'%posted_user_display_name%'   => $user_posted->display_name,
			'%primary_link%'               => $activity->primary_link,
			'%time%'                       => $activity->date_recorded,
			'%message%'                    => $content,
			'%receiver_user_display_name%' => $user_receiver->display_name,
		);
		$message        = str_replace( array_keys( $template_vars ), array_values( $template_vars ), $this->options['bp_mention_message'] );
		$this->sms->to  = array( $user_mobile );
		$this->sms->msg = $message;
		$this->sms->SendSMS();
	}

	// BuddyPress comments on reply
	public function comments_reply_notification( $activity_comment, $comment_id, $commenter_id ) {

		// Load comment
		$comment = new \BP_Activity_Activity( $comment_id );

		// Get user mobile
		$user_mobile = $this->get_mobile( $activity_comment->user_id );

		// Check the mobile
		if ( ! $user_mobile ) {
			return;
		}

		$user_posted    = get_userdata( $commenter_id );
		$user_receiver  = get_userdata( $activity_comment->user_id );
		$template_vars  = array(
			'%posted_user_display_name%'   => $user_posted->display_name,
			'%comment%'                    => $comment->content,
			'%receiver_user_display_name%' => $user_receiver->display_name,
		);
		$message        = str_replace( array_keys( $template_vars ), array_values( $template_vars ), $this->options['bp_comments_reply_message'] );
		$this->sms->to  = array( $user_mobile );
		$this->sms->msg = $message;
		$this->sms->SendSMS();
	}

	// BuddyPress comments on activity
	public function comments_activity_notification( $activity, $comment_id, $commenter_id ) {

		// Load comment
		$comment = new \BP_Activity_Activity( $comment_id );

		// Get user mobile
		$user_mobile = $this->get_mobile( $activity->user_id );

		// Check the mobile
		if ( ! $user_mobile ) {
			return;
		}

		$user_posted    = get_userdata( $commenter_id );
		$user_receiver  = get_userdata( $activity->user_id );
		$template_vars  = array(
			'%posted_user_display_name%'   => $user_posted->display_name,
			'%comment%'                    => $comment->content,
			'%receiver_user_display_name%' => $user_receiver->display_name,
		);
		$message        = str_replace( array_keys( $template_vars ), array_values( $template_vars ), $this->options['bp_comments_activity_message'] );
		$this->sms->to  = array( $user_mobile );
		$this->sms->msg = $message;
		$this->sms->SendSMS();
	}

	// Get Buddypress mobile value
	private function get_mobile( $user_id ) {
		global $wpdb;
		$field = $wpdb->get_row( $wpdb->prepare( "SELECT `id` FROM {$wpdb->prefix}bp_xprofile_fields WHERE name = %s", 'Mobile' ) );

		if ( $field ) {
			$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bp_xprofile_data WHERE field_id = %d AND user_id = %d", $field->id, $user_id ) );

			if ( ! $result ) {
				return;
			}

			return $result->value;
		}
	}

	/**
	 * Add class to mobile attribute
	 *
	 * @param $r
	 *
	 * @return array
	 */
	function add_attribute( $r ) {
		$field_name = bp_get_the_profile_field_name();

		if ( $field_name == 'Mobile' ) {
			$new_attribute['class'] = 'wp-sms-input-mobile';
			$attributes             = array_merge( $new_attribute, $r );

		} else {

			$attributes = $r;
		}

		return $attributes;
	}
}

new BuddyPress();