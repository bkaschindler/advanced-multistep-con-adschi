<?php

class SMLF_Emails {

	public function __construct() {
		add_action( 'smlf_form_submitted', array( $this, 'send_notifications' ), 10, 3 );
	}

	public function send_notifications( $lead_id, $form_id, $data ) {
		$admin_email = sanitize_email( get_option( 'smlf_admin_email', get_option( 'admin_email' ) ) );
		$user_email  = '';

		// Find user email in data
		foreach ( $data as $key => $val ) {
			if ( strpos( strtolower( $key ), 'email' ) !== false && is_email( $val ) ) {
				$user_email = $val;
				break;
			}
		}

		// Admin Email
		$admin_subject = __( 'New Lead Submitted', 'smart-multistep-lead-forms' ) . ' - Form ID: ' . $form_id;
		$admin_body    = $this->get_email_template( __( 'You have a new completed lead.', 'smart-multistep-lead-forms' ), $data );
		if ( is_email( $admin_email ) ) {
			$this->send_and_log( $lead_id, $admin_email, $admin_subject, $admin_body );
		}

		// User Email
		if ( ! empty( $user_email ) ) {
			$user_subject = __( 'We received your submission!', 'smart-multistep-lead-forms' );
			$user_body    = $this->get_email_template( __( 'Thank you for your submission. We have received your request and will be in touch soon.', 'smart-multistep-lead-forms' ), $data );
			$this->send_and_log( $lead_id, $user_email, $user_subject, $user_body );
		}
	}

	private function get_email_template( $message, $data ) {
		$author_name = defined('SMLF_AUTHOR_NAME') ? SMLF_AUTHOR_NAME : 'Mohammad Babaei';
		$author_url  = defined('SMLF_AUTHOR_URL') ? SMLF_AUTHOR_URL : 'https://adschi.com';

		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; background: #f9f9f9; padding: 20px;">
			<div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
				<h2 style="color: #333; border-bottom: 2px solid #0073aa; padding-bottom: 10px;"><?php esc_html_e('Submission Details', 'smart-multistep-lead-forms'); ?></h2>
				<p style="font-size: 16px; color: #555;"><?php echo esc_html( $message ); ?></p>

				<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
					<?php foreach ( $data as $key => $val ) : ?>
					<tr>
						<td style="padding: 10px; border-bottom: 1px solid #eee; font-weight: bold; width: 40%;"><?php echo esc_html( $key ); ?></td>
						<td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo esc_html( $this->format_email_value( $val ) ); ?></td>
					</tr>
					<?php endforeach; ?>
				</table>

				<div style="margin-top: 40px; text-align: center; color: #888; font-size: 12px; border-top: 1px solid #eee; padding-top: 20px;">
					<?php
					echo sprintf(
						/* translators: 1: Author name, 2: Author URL */
						__( 'Powered by <a href="%2$s" target="_blank" style="color: #0073aa; text-decoration: none;">%1$s</a>', 'smart-multistep-lead-forms' ),
						esc_html( $author_name ),
						esc_url( $author_url )
					);
					?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	private function send_and_log( $lead_id, $to, $subject, $body ) {
		$headers = array('Content-Type: text/html; charset=UTF-8');
		$sent = wp_mail( $to, $subject, $body, $headers );

		global $wpdb;
		$table_name = $wpdb->prefix . 'smlf_email_logs';

		$wpdb->insert(
			$table_name,
			array(
				'lead_id'         => $lead_id,
				'recipient_email' => sanitize_email( $to ),
				'subject'         => sanitize_text_field( $subject ),
				'body'            => $body,
				'status'          => $sent ? 'sent' : 'failed'
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);
	}

	private function format_email_value( $value ) {
		if ( is_scalar( $value ) ) {
			return (string) $value;
		}

		return wp_json_encode( $value );
	}
}

// Instantiate to hook actions
new SMLF_Emails();
