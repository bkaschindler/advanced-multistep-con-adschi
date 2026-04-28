<?php

class SMLF_Emails {

	public function __construct() {
		add_action( 'smlf_form_submitted', array( $this, 'send_notifications' ), 10, 3 );
	}

	public function send_notifications( $lead_id, $form_id, $data ) {
		$form_context = $this->get_form_context( $form_id );
		$switched     = $this->switch_to_form_locale( $form_context['language'] );
		$admin_email  = sanitize_email( get_option( 'smlf_admin_email', get_option( 'admin_email' ) ) );
		$user_email   = $this->extract_user_email( $data );
		$user_name    = $this->extract_user_name( $data );

		$context = array(
			'lead_id'    => absint( $lead_id ),
			'form_id'    => absint( $form_id ),
			'form_title' => $form_context['title'],
			'page_url'   => $this->get_lead_referrer( $lead_id ),
			'site_name'  => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'customer_name' => $user_name,
			'logo_url'   => $this->get_logo_url(),
			'labels'     => $form_context['labels'],
		);

		if ( is_email( $admin_email ) ) {
			$admin_subject = $this->replace_placeholders(
				get_option( 'smlf_email_admin_subject', $this->get_default_email_text( 'admin_subject' ) ),
				$data,
				$context
			);
			$admin_intro   = $this->replace_placeholders(
				get_option( 'smlf_email_admin_intro', $this->get_default_email_text( 'admin_intro' ) ),
				$data,
				$context
			);
			$admin_body    = $this->get_email_template( $admin_intro, $data, $context, 'admin' );
			$this->send_and_log( $lead_id, $admin_email, $admin_subject, $admin_body );
		}

		if ( ! empty( $user_email ) ) {
			$user_subject = $this->replace_placeholders(
				get_option( 'smlf_email_user_subject', $this->get_default_email_text( 'user_subject' ) ),
				$data,
				$context
			);
			$user_intro   = $this->replace_placeholders(
				get_option( 'smlf_email_user_intro', $this->get_default_email_text( 'user_intro' ) ),
				$data,
				$context
			);
			$user_body    = $this->get_email_template( $user_intro, $data, $context, 'customer' );
			$this->send_and_log( $lead_id, $user_email, $user_subject, $user_body );
		}

		if ( $switched && function_exists( 'restore_previous_locale' ) ) {
			restore_previous_locale();
		}
	}

	private function get_email_template( $intro, $data, $context, $audience ) {
		$footer = $this->replace_placeholders(
			get_option( 'smlf_email_footer_text', $this->get_default_email_text( 'footer' ) ),
			$data,
			$context
		);
		$title  = 'admin' === $audience ? __( 'New request received', 'smart-multistep-lead-forms' ) : __( 'Thank you for your request', 'smart-multistep-lead-forms' );

		ob_start();
		?>
		<div style="margin:0;padding:0;background:#eef3f8;font-family:Arial,Helvetica,sans-serif;color:#172033;">
			<div style="max-width:720px;margin:0 auto;padding:28px 16px;">
				<div style="overflow:hidden;border-radius:22px;background:#ffffff;box-shadow:0 24px 70px rgba(15,23,42,.14);">
					<div style="padding:26px 30px;background:linear-gradient(135deg,#0f172a,#0e7490);color:#ffffff;">
						<?php if ( ! empty( $context['logo_url'] ) ) : ?>
							<img src="<?php echo esc_url( $context['logo_url'] ); ?>" alt="<?php echo esc_attr( $context['site_name'] ); ?>" style="display:block;max-width:170px;max-height:70px;margin-bottom:18px;background:#ffffff;border-radius:14px;padding:8px;">
						<?php else : ?>
							<div style="margin-bottom:18px;font-size:20px;font-weight:800;"><?php echo esc_html( $context['site_name'] ); ?></div>
						<?php endif; ?>
						<h1 style="margin:0;font-size:28px;line-height:1.25;"><?php echo esc_html( $title ); ?></h1>
						<p style="margin:10px 0 0;color:#dff6ff;font-size:15px;"><?php echo esc_html( $context['form_title'] ); ?></p>
					</div>
					<div style="padding:30px;">
						<p style="margin:0 0 22px;font-size:16px;line-height:1.7;color:#334155;"><?php echo nl2br( esc_html( $intro ) ); ?></p>
						<?php if ( ! empty( $context['page_url'] ) ) : ?>
							<p style="margin:0 0 18px;font-size:13px;color:#64748b;">
								<strong><?php esc_html_e( 'Source page:', 'smart-multistep-lead-forms' ); ?></strong>
								<a href="<?php echo esc_url( $context['page_url'] ); ?>" style="color:#0e7490;text-decoration:none;"><?php echo esc_html( $context['page_url'] ); ?></a>
							</p>
						<?php endif; ?>
						<table style="width:100%;border-collapse:separate;border-spacing:0 10px;">
							<?php foreach ( $data as $key => $value ) : ?>
								<tr>
									<td style="width:38%;padding:14px 16px;background:#f8fafc;border-radius:14px 0 0 14px;color:#475569;font-weight:800;vertical-align:top;"><?php echo esc_html( $this->get_field_label( $key, $context['labels'] ) ); ?></td>
									<td style="padding:14px 16px;background:#f8fafc;border-radius:0 14px 14px 0;color:#111827;vertical-align:top;"><?php echo wp_kses_post( $this->format_email_value( $value ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</table>
					</div>
					<div style="padding:18px 30px;border-top:1px solid #e2e8f0;background:#f8fafc;color:#64748b;font-size:12px;line-height:1.6;">
						<?php echo nl2br( esc_html( $footer ) ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	private function send_and_log( $lead_id, $to, $subject, $body ) {
		$from_email = sanitize_email( get_option( 'smlf_admin_email', get_option( 'admin_email' ) ) );
		$from_name  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$headers    = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $from_name . ' <' . $from_email . '>',
		);
		$sent       = wp_mail( $to, $subject, $body, $headers );

		global $wpdb;
		$table_name = $wpdb->prefix . 'smlf_email_logs';

		$wpdb->insert(
			$table_name,
			array(
				'lead_id'         => $lead_id,
				'recipient_email' => sanitize_email( $to ),
				'subject'         => sanitize_text_field( $subject ),
				'body'            => $body,
				'status'          => $sent ? 'sent' : 'failed',
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);
	}

	private function replace_placeholders( $text, $data, $context ) {
		$replacements = array(
			'{site_name}'  => $context['site_name'],
			'{form_title}' => $context['form_title'],
			'{lead_id}'    => $context['lead_id'],
			'{page_url}'   => $context['page_url'],
			'{customer_name}' => $context['customer_name'],
			'{summary}'    => wp_strip_all_tags( $this->build_text_summary( $data, $context['labels'] ) ),
		);

		return strtr( (string) $text, $replacements );
	}

	private function build_text_summary( $data, $labels ) {
		$lines = array();
		foreach ( $data as $key => $value ) {
			$lines[] = $this->get_field_label( $key, $labels ) . ': ' . wp_strip_all_tags( $this->format_email_value( $value ) );
		}

		return implode( "\n", $lines );
	}

	private function get_form_context( $form_id ) {
		global $wpdb;
		$form = $wpdb->get_row( $wpdb->prepare( "SELECT title, form_data FROM {$wpdb->prefix}smlf_forms WHERE id = %d", absint( $form_id ) ) );
		$data = $form ? json_decode( $form->form_data, true ) : array();

		return array(
			'title'    => $form ? sanitize_text_field( $form->title ) : sprintf( __( 'Form #%d', 'smart-multistep-lead-forms' ), absint( $form_id ) ),
			'language' => isset( $data['settings']['form_language'] ) ? sanitize_key( $data['settings']['form_language'] ) : 'auto',
			'labels'   => $this->extract_field_labels( $data ),
		);
	}

	private function extract_field_labels( $form_data ) {
		$labels = array();
		$steps  = isset( $form_data['steps'] ) && is_array( $form_data['steps'] ) ? $form_data['steps'] : array();

		foreach ( $steps as $step ) {
			$fields = isset( $step['fields'] ) && is_array( $step['fields'] ) ? $step['fields'] : array();
			foreach ( $fields as $field ) {
				if ( empty( $field['field_id'] ) ) {
					continue;
				}
				$labels[ 'smlf_field_' . sanitize_key( $field['field_id'] ) ] = isset( $field['label'] ) ? sanitize_text_field( $field['label'] ) : sanitize_key( $field['field_id'] );
			}
		}

		return $labels;
	}

	private function switch_to_form_locale( $language ) {
		if ( 'auto' === $language || ! function_exists( 'switch_to_locale' ) ) {
			return false;
		}

		$map = array(
			'en' => 'en_US',
			'de' => 'de_DE',
			'fa' => 'fa_IR',
		);

		if ( empty( $map[ $language ] ) ) {
			return false;
		}

		return switch_to_locale( $map[ $language ] );
	}

	private function get_logo_url() {
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id ) {
			$logo = wp_get_attachment_image_url( $custom_logo_id, 'full' );
			if ( $logo ) {
				return $logo;
			}
		}

		return function_exists( 'get_site_icon_url' ) ? get_site_icon_url( 192 ) : '';
	}

	private function get_lead_referrer( $lead_id ) {
		global $wpdb;
		$referrer = $wpdb->get_var( $wpdb->prepare( "SELECT referrer FROM {$wpdb->prefix}smlf_leads WHERE id = %d", absint( $lead_id ) ) );
		return $referrer ? esc_url_raw( $referrer ) : '';
	}

	private function extract_user_email( $data ) {
		foreach ( $data as $key => $value ) {
			if ( false !== strpos( strtolower( $key ), 'email' ) && is_email( $value ) ) {
				return sanitize_email( $value );
			}
		}

		return '';
	}

	private function extract_user_name( $data ) {
		foreach ( $data as $key => $value ) {
			$key = strtolower( (string) $key );
			if ( is_scalar( $value ) && '' !== trim( (string) $value ) && ( false !== strpos( $key, 'name' ) || false !== strpos( $key, 'fullname' ) ) ) {
				return sanitize_text_field( $value );
			}
		}

		return __( 'there', 'smart-multistep-lead-forms' );
	}

	private function get_default_email_text( $key ) {
		$defaults = array(
			'admin_subject' => __( 'New lead #{lead_id} from {form_title}', 'smart-multistep-lead-forms' ),
			'admin_intro'   => __( "A new completed request has been submitted from {form_title}.\n\nLead ID: {lead_id}\nSource page: {page_url}\n\nCustomer details and all submitted answers are listed below.\n\nQuick summary:\n{summary}", 'smart-multistep-lead-forms' ),
			'user_subject'  => __( 'We received your request, {customer_name}', 'smart-multistep-lead-forms' ),
			'user_intro'    => __( "Hello {customer_name},\n\nThank you for your request. We received your information and will contact you soon.\n\nHere is a copy of the details you submitted:\n{summary}", 'smart-multistep-lead-forms' ),
			'footer'        => __( 'This email was sent automatically by {site_name}. Please keep it for your records.', 'smart-multistep-lead-forms' ),
		);

		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	}

	private function get_field_label( $key, $labels ) {
		return isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
	}

	private function format_email_value( $value ) {
		if ( is_array( $value ) && isset( $value[0] ) && is_array( $value[0] ) && isset( $value[0]['url'] ) ) {
			$links = array();
			foreach ( $value as $file ) {
				if ( empty( $file['url'] ) ) {
					continue;
				}
				$name    = isset( $file['name'] ) ? $file['name'] : basename( $file['url'] );
				$links[] = '<a href="' . esc_url( $file['url'] ) . '" style="color:#0e7490;text-decoration:none;">' . esc_html( $name ) . '</a>';
			}
			return implode( '<br>', $links );
		}

		if ( is_scalar( $value ) ) {
			return esc_html( (string) $value );
		}

		return esc_html( wp_json_encode( $value ) );
	}
}

new SMLF_Emails();
