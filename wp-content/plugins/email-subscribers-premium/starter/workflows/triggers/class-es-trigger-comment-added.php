<?php

defined( 'ABSPATH' ) || exit;

/***
 * ES_Trigger_Comment_Added class.
 *
 * @since 4.4.1
 */
class ES_Trigger_Comment_Added extends ES_Workflow_Trigger {

	/**
	 * Declares data items available in trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = array( 'comment' );

	/**
	 * Load trigger admin props.
	 */
	public function load_admin_details() {
		$this->title  = __( 'Comment Added', 'email-subscribers' );
		$settings_url = admin_url( 'admin.php?page=es_settings' );
		/* translators: %s: ES settings URL */
		$this->description = sprintf( __( 'Fires when someone make a comment. Do you want to add Opt-In consent box? You can enable/ disable it from <a href="%s" class="text-indigo-600" target="_blank">here</a>', 'email-subscribers' ), $settings_url );
		$this->group 	   = __( 'Comment', 'email-subscribers' );
	}

	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'wp_insert_comment', array( $this, 'handle_insert_comment' ), 10, 2 );
	}


	/**
	 * Catch comment creation hook.
	 *
	 * @param int         $comment_id
	 * @param \WP_Comment $comment
	 */
	public function handle_insert_comment( $comment_id, $comment ) {

		$comment_consent     = ig_es_get_request_data( 'ig-es-comment-es-consent', 'no' );
		$show_opt_in_consent = get_site_option( 'ig_es_show_opt_in_consent', 'no' );

		$process_trigger = true;
		
		// Check consent only when user is not logged in since our consent field is shown in the comment form only when user is not logged in.
		// TODO: Remove ! is_user_logged_in() condition check when issue get fixed in the WordPress core.
		// Reference: https://core.trac.wordpress.org/ticket/16576
		if ( 'yes' === $show_opt_in_consent && ! is_user_logged_in() ) {
			$process_trigger = ( 'yes' === $comment_consent );
		}

		// subscriber contact to list only if consent given
		if ( ! $process_trigger ) {
			return;
		}

		$data = array(
			'comment' => $comment
		);

		$this->maybe_run( $data	);
	}


	/**
	 * Validate a workflow.
	 *
	 * @param ES_Workflow $workflow
	 *
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {

		$comment = $workflow->data_layer()->get_item( 'comment' );

		if ( ! $comment ) {
			return false;
		}

		$email = ! empty( $comment->comment_author_email ) ? $comment->comment_author_email : '';
		if ( empty( $email ) ) {
			return false;
		}

		return true;
	}

}
