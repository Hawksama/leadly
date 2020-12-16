<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Smtp_Mailer' ) ) {
	/**
	 * Send Mail using SMTP
	 *
	 * Class ES_Smtp_Mailer
	 *
	 * @since 4.2.1
	 */
	class ES_Smtp_Mailer extends ES_Base_Mailer {
		/**
		 * ES_Smtp_Mailer constructor.
		 *
		 * @since 4.2.1
		 */
		public function __construct() {
			parent::__construct();
		}

		/**
		 * Send Mail using SMTP
		 *
		 * @param ES_Message $message
		 *
		 * @return bool|WP_Error
		 *
		 * @since 4.2.1
		 * @since 4.3.2 Modified arguments.
		 */
		public function send( ES_Message $message ) {

			global $wp_version;

			ES()->logger->info( 'Start Sending Email Using SMTP', $this->logger_context );

			if ( version_compare( $wp_version, '5.5', '<' ) ) {
				require_once ABSPATH . WPINC . '/class-phpmailer.php';
				require_once ABSPATH . WPINC . '/class-smtp.php';
			} else {
				require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
				require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
				require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			
				// Check if PHPMailer class already exists before creating an alias for it.
				if ( ! class_exists( 'PHPMailer' ) ) {
					class_alias( PHPMailer\PHPMailer\PHPMailer::class, 'PHPMailer' );
				}

				// Check if phpmailerException class already exists before creating an alias for it.
				if ( ! class_exists( 'phpmailerException' ) ) {
					class_alias( PHPMailer\PHPMailer\Exception::class, 'phpmailerException' );
				}

				// Check if SMTP class already exists before creating an alias for it.
				if ( ! class_exists( 'SMTP' ) ) {
					class_alias( PHPMailer\PHPMailer\SMTP::class, 'SMTP' );
				}
			}

			$phpmailer = new PHPMailer( true );
			

			$mailer_settings = get_option( 'ig_es_mailer_settings', array() );

			if ( ! empty( $mailer_settings['smtp'] ) ) {

				$smtp_settings = $mailer_settings['smtp'];

				$smtp_host       = $smtp_settings['smtp_host'];
				$smtp_port       = $smtp_settings['smtp_port'];
				$smtp_encryption = $smtp_settings['smtp_encryption'];
				$smtp_auth       = $smtp_settings['smtp_authentication'];
				$smtp_username   = $smtp_settings['smtp_username'];
				$smtp_password   = $smtp_settings['smtp_password'];

				$smtp_auth = ( 'yes' === $smtp_auth ) ? true : false;

				$phpmailer->isSMTP();
				$phpmailer->SMTPKeepAlive = true;
				$phpmailer->Host          = $smtp_host;
				$phpmailer->SMTPAuth      = $smtp_auth;
				$phpmailer->Username      = $smtp_username;
				$phpmailer->Password      = $smtp_password;
				$phpmailer->SMTPSecure    = $smtp_encryption;
				$phpmailer->Port          = $smtp_port;
				$phpmailer->From          = $message->from;
				$phpmailer->FromName      = $message->from_name;
				$phpmailer->CharSet       = 'UTF-8';

				$phpmailer->ClearAllRecipients();
				$phpmailer->clearAttachments();
				$phpmailer->clearCustomHeaders();
				$phpmailer->clearReplyTos();

				$phpmailer->addAddress( $message->to );
				$phpmailer->addReplyTo( $message->from, $message->from_name );

				$phpmailer->WordWrap = 50;
				$phpmailer->isHTML( true );

				$phpmailer->Subject = $message->subject;
				$phpmailer->Body    = $message->body;
				$phpmailer->AltBody = $message->body_text; //Text Email Body for non html email client

				try {
					if ( ! $phpmailer->send() ) {
						ES()->logger->error( '[Error in Email Sending] : ' . $message->to . ' Error: ' . $phpmailer->ErrorInfo, $this->logger_context );

						return $this->do_response( 'error', $phpmailer->ErrorInfo );
					}
				} catch ( Exception $e ) {
					ES()->logger->error( '[Error in Email Sending] : ' . $message->to . ' Error: ' . $e->getMessage(), $this->logger_context );

					return $this->do_response( 'error', $e->getMessage() );
				}
			} else {
				ES()->logger->error( '[Error in Email Sending] : ' . $message->to . ' Error: SMTP settings not found', $this->logger_context );

				return $this->do_response( 'error', 'SMTP settings not found' );
			}

			ES()->logger->info( 'Email sent successfully using SMTP', $this->logger_context );

			return $this->do_response( 'success' );
		}
	}
}
