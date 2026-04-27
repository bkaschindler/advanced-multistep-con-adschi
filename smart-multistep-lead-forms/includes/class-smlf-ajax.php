<?php

class SMLF_Ajax {

	private $allowed_field_types = array( 'text', 'email', 'phone', 'cards', 'radio', 'textarea', 'file', 'message' );
	private $allowed_captcha_methods = array( 'none', 'custom', 'recaptcha_v2', 'recaptcha_v3', 'turnstile' );

	public function save_form_admin() {
		check_ajax_referer( 'smlf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'smart-multistep-lead-forms' ) ), 403 );
		}

		$form_id       = isset( $_POST['form_id'] ) ? absint( wp_unslash( $_POST['form_id'] ) ) : 0;
		$form_data_raw = isset( $_POST['form_data'] ) ? wp_unslash( $_POST['form_data'] ) : '';
		$form_data     = json_decode( $form_data_raw, true );

		if ( ! is_array( $form_data ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form data.', 'smart-multistep-lead-forms' ) ), 400 );
		}

		$sanitized_form_data = $this->sanitize_form_data( $form_data );
		$title               = $sanitized_form_data['title'];

		global $wpdb;
		$table_name = $wpdb->prefix . 'smlf_forms';

		if ( $form_id ) {
			$existing = $this->get_form( $form_id, false );
			if ( ! $existing ) {
				wp_send_json_error( array( 'message' => __( 'Form not found.', 'smart-multistep-lead-forms' ) ), 404 );
			}

			$result = $wpdb->update(
				$table_name,
				array(
					'title'     => $title,
					'form_data' => wp_json_encode( $sanitized_form_data ),
				),
				array( 'id' => $form_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);

			if ( false === $result ) {
				wp_send_json_error( array( 'message' => __( 'Could not update form.', 'smart-multistep-lead-forms' ) ), 500 );
			}

			wp_send_json_success( array( 'form_id' => $form_id ) );
		}

		$result = $wpdb->insert(
			$table_name,
			array(
				'title'     => $title,
				'form_data' => wp_json_encode( $sanitized_form_data ),
				'status'    => 'publish',
			),
			array( '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Could not create form.', 'smart-multistep-lead-forms' ) ), 500 );
		}

		wp_send_json_success( array( 'form_id' => absint( $wpdb->insert_id ) ) );
	}

	public function verify_bot() {
		$token           = $this->get_post_text( 'token' );
		$custom_verified = $this->get_post_text( 'custom_verified' );
		$result          = $this->verify_captcha_token( $token, $custom_verified, true );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success( array( 'captcha_token' => $this->create_captcha_pass_token() ) );
	}

	public function save_partial_lead() {
		if ( ! get_option( 'smlf_enable_partial', 1 ) ) {
			wp_send_json_success();
		}

		$form_id = isset( $_POST['form_id'] ) ? absint( wp_unslash( $_POST['form_id'] ) ) : 0;
		$form    = $this->get_form( $form_id );

		if ( ! $form ) {
			wp_send_json_error( array( 'message' => __( 'Form not found.', 'smart-multistep-lead-forms' ) ), 404 );
		}

		$lead_id = isset( $_POST['lead_id'] ) ? absint( wp_unslash( $_POST['lead_id'] ) ) : 0;
		if ( $lead_id && ! $this->lead_belongs_to_form( $lead_id, $form_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Lead not found for this form.', 'smart-multistep-lead-forms' ) ), 404 );
		}

		$structured_data = $this->sanitize_submission_data( isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array() );
		$email           = $this->extract_email( $structured_data );
		$phone           = $this->extract_phone( $structured_data );

		if ( '' === $email && '' === $phone ) {
			wp_send_json_success();
		}

		$uploaded_files = $this->handle_uploaded_files();
		if ( ! empty( $uploaded_files ) ) {
			$structured_data['uploaded_files'] = $uploaded_files;
		}

		global $wpdb;
		$table_name     = $wpdb->prefix . 'smlf_leads';
		$lead_data_json = wp_json_encode( $structured_data );

		if ( $lead_id ) {
			$result = $wpdb->update(
				$table_name,
				array(
					'lead_data' => $lead_data_json,
					'email'     => $email,
					'phone'     => $phone,
					'status'    => 'partial',
				),
				array( 'id' => $lead_id ),
				array( '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			$result = $wpdb->insert(
				$table_name,
				array(
					'form_id'    => $form_id,
					'lead_data'  => $lead_data_json,
					'email'      => $email,
					'phone'      => $phone,
					'status'     => 'started',
					'ip_address' => $this->get_remote_ip(),
					'user_agent' => $this->get_user_agent(),
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
			$lead_id = absint( $wpdb->insert_id );
		}

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Could not save lead.', 'smart-multistep-lead-forms' ) ), 500 );
		}

		$this->trigger_webhook( 'partial', $lead_id, $form_id, $structured_data );

		wp_send_json_success( array( 'lead_id' => $lead_id ) );
	}

	public function submit_form() {
		$form_id = isset( $_POST['form_id'] ) ? absint( wp_unslash( $_POST['form_id'] ) ) : 0;
		$form    = $this->get_form( $form_id );

		if ( ! $form ) {
			wp_send_json_error( array( 'message' => __( 'Form not found.', 'smart-multistep-lead-forms' ) ), 404 );
		}

		$captcha_result = $this->verify_captcha_token( $this->get_post_text( 'captcha_token' ), $this->get_post_text( 'custom_verified' ) );
		if ( is_wp_error( $captcha_result ) ) {
			wp_send_json_error( array( 'message' => $captcha_result->get_error_message() ), 400 );
		}

		$lead_id = isset( $_POST['lead_id'] ) ? absint( wp_unslash( $_POST['lead_id'] ) ) : 0;
		if ( $lead_id && ! $this->lead_belongs_to_form( $lead_id, $form_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Lead not found for this form.', 'smart-multistep-lead-forms' ) ), 404 );
		}

		$structured_data = $this->sanitize_submission_data( isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array() );
		$uploaded_files  = $this->handle_uploaded_files();
		if ( ! empty( $uploaded_files ) ) {
			$structured_data['uploaded_files'] = $uploaded_files;
		}
		$email           = $this->extract_email( $structured_data );
		$phone           = $this->extract_phone( $structured_data );

		global $wpdb;
		$table_name     = $wpdb->prefix . 'smlf_leads';
		$lead_data_json = wp_json_encode( $structured_data );

		if ( $lead_id ) {
			$result = $wpdb->update(
				$table_name,
				array(
					'lead_data'    => $lead_data_json,
					'email'        => $email,
					'phone'        => $phone,
					'status'       => 'completed',
					'completed_at' => current_time( 'mysql' ),
				),
				array( 'id' => $lead_id ),
				array( '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			$result = $wpdb->insert(
				$table_name,
				array(
					'form_id'      => $form_id,
					'lead_data'    => $lead_data_json,
					'email'        => $email,
					'phone'        => $phone,
					'status'       => 'completed',
					'ip_address'   => $this->get_remote_ip(),
					'user_agent'   => $this->get_user_agent(),
					'completed_at' => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
			$lead_id = absint( $wpdb->insert_id );
		}

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Could not submit form.', 'smart-multistep-lead-forms' ) ), 500 );
		}

		do_action( 'smlf_form_submitted', $lead_id, $form_id, $structured_data );
		$this->trigger_webhook( 'completed', $lead_id, $form_id, $structured_data );

		wp_send_json_success( array( 'lead_id' => $lead_id ) );
	}

	public function export_leads_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'smart-multistep-lead-forms' ) );
		}

		check_admin_referer( 'smlf_export_leads_csv' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'smlf_leads';
		$leads      = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY id DESC", ARRAY_A );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="smlf-leads-export.csv"' );

		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			wp_die( esc_html__( 'Could not open CSV output.', 'smart-multistep-lead-forms' ) );
		}

		fputcsv( $output, array( 'ID', 'Form ID', 'Status', 'Email', 'Phone', 'Data', 'Created At' ) );

		foreach ( $leads as $lead ) {
			fputcsv(
				$output,
				array(
					$lead['id'],
					$lead['form_id'],
					$lead['status'],
					$lead['email'],
					$lead['phone'],
					$lead['lead_data'],
					$lead['created_at'],
				)
			);
		}

		fclose( $output );
		exit;
	}

	private function sanitize_form_data( $form_data ) {
		$title = isset( $form_data['title'] ) ? sanitize_text_field( $form_data['title'] ) : '';
		$title = '' !== $title ? $title : __( 'New Form', 'smart-multistep-lead-forms' );

		$sanitized = array(
			'title' => $title,
			'steps' => array(),
		);

		if ( empty( $form_data['steps'] ) || ! is_array( $form_data['steps'] ) ) {
			return $sanitized;
		}

		foreach ( $form_data['steps'] as $step_index => $step ) {
			if ( ! is_array( $step ) ) {
				continue;
			}

			$step_id = isset( $step['step_id'] ) ? absint( $step['step_id'] ) : absint( $step_index + 1 );

			$sanitized_step = array(
				'step_id'      => $step_id ? $step_id : absint( $step_index + 1 ),
				'title'        => isset( $step['title'] ) ? sanitize_text_field( $step['title'] ) : '',
				'logic_target' => isset( $step['logic_target'] ) ? absint( $step['logic_target'] ) : 0,
				'logic_value'  => isset( $step['logic_value'] ) ? sanitize_text_field( $step['logic_value'] ) : '',
				'terminal'     => isset( $step['terminal'] ) && 'reset' === sanitize_key( $step['terminal'] ) ? 'reset' : '',
				'fields'       => array(),
			);

			if ( empty( $step['fields'] ) || ! is_array( $step['fields'] ) ) {
				$sanitized['steps'][] = $sanitized_step;
				continue;
			}

			foreach ( $step['fields'] as $field_index => $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}

				$type = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text';
				if ( ! in_array( $type, $this->allowed_field_types, true ) ) {
					$type = 'text';
				}

				$label    = isset( $field['label'] ) ? sanitize_text_field( $field['label'] ) : '';
				$field_id = isset( $field['field_id'] ) ? sanitize_key( $field['field_id'] ) : '';
				if ( '' === $field_id ) {
					$field_id = 'field_' . $sanitized_step['step_id'] . '_' . absint( $field_index + 1 );
				}

				$sanitized_step['fields'][] = array(
					'field_id' => $field_id,
					'type'     => $type,
					'label'    => $label,
					'options'  => isset( $field['options'] ) ? sanitize_textarea_field( $field['options'] ) : '',
					'required' => ! empty( $field['required'] ) ? 1 : 0,
				);
			}

			$sanitized['steps'][] = $sanitized_step;
		}

		return $sanitized;
	}

	private function sanitize_submission_data( $data_raw ) {
		$structured_data = array();

		if ( ! is_array( $data_raw ) ) {
			return $structured_data;
		}

		foreach ( $data_raw as $field ) {
			if ( ! is_array( $field ) || ! isset( $field['name'] ) ) {
				continue;
			}

			$name = sanitize_key( $field['name'] );
			if ( '' === $name ) {
				continue;
			}

			$value = isset( $field['value'] ) ? sanitize_text_field( $field['value'] ) : '';
			$structured_data[ $name ] = $value;
		}

		return $structured_data;
	}

	private function handle_uploaded_files() {
		if ( empty( $_FILES['smlf_files'] ) || empty( $_FILES['smlf_files']['name'] ) || ! is_array( $_FILES['smlf_files']['name'] ) ) {
			return array();
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$uploaded = array();
		$allowed_mimes = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'gif'          => 'image/gif',
			'webp'         => 'image/webp',
			'pdf'          => 'application/pdf',
			'doc'          => 'application/msword',
			'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xls'          => 'application/vnd.ms-excel',
			'xlsx'         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'zip'          => 'application/zip',
		);

		foreach ( $_FILES['smlf_files']['name'] as $index => $name ) {
			if ( empty( $name ) || ! empty( $_FILES['smlf_files']['error'][ $index ] ) ) {
				continue;
			}

			$file = array(
				'name'     => sanitize_file_name( wp_unslash( $name ) ),
				'type'     => isset( $_FILES['smlf_files']['type'][ $index ] ) ? sanitize_mime_type( wp_unslash( $_FILES['smlf_files']['type'][ $index ] ) ) : '',
				'tmp_name' => isset( $_FILES['smlf_files']['tmp_name'][ $index ] ) ? $_FILES['smlf_files']['tmp_name'][ $index ] : '',
				'error'    => isset( $_FILES['smlf_files']['error'][ $index ] ) ? absint( $_FILES['smlf_files']['error'][ $index ] ) : 0,
				'size'     => isset( $_FILES['smlf_files']['size'][ $index ] ) ? absint( $_FILES['smlf_files']['size'][ $index ] ) : 0,
			);

			if ( $file['size'] > 10 * MB_IN_BYTES ) {
				continue;
			}

			$result = wp_handle_upload(
				$file,
				array(
					'test_form' => false,
					'mimes'     => $allowed_mimes,
				)
			);

			if ( empty( $result['error'] ) && ! empty( $result['url'] ) ) {
				$uploaded[] = array(
					'name' => $file['name'],
					'url'  => esc_url_raw( $result['url'] ),
					'type' => isset( $result['type'] ) ? sanitize_mime_type( $result['type'] ) : '',
				);
			}
		}

		return $uploaded;
	}

	private function verify_captcha_token( $token, $custom_verified = '', $is_verify_step = false ) {
		$captcha_method = $this->get_captcha_method();
		$secret_key     = get_option( 'smlf_captcha_secret_key', '' );

		if ( 'none' === $captcha_method ) {
			return true;
		}

		if ( 'custom' === $captcha_method ) {
			if ( $is_verify_step ) {
				return '1' === (string) $custom_verified || 'true' === (string) $custom_verified
					? true
					: new WP_Error( 'smlf_custom_captcha_failed', __( 'Please verify you are human.', 'smart-multistep-lead-forms' ) );
			}

			return $this->validate_captcha_pass_token( $token )
				? true
				: new WP_Error( 'smlf_custom_captcha_failed', __( 'Please verify you are human.', 'smart-multistep-lead-forms' ) );
		}

		if ( $this->validate_captcha_pass_token( $token ) ) {
			return true;
		}

		if ( '' === $secret_key || '' === $token ) {
			return new WP_Error( 'smlf_captcha_missing', __( 'Captcha verification is incomplete.', 'smart-multistep-lead-forms' ) );
		}

		$endpoint = '';
		if ( 'recaptcha_v2' === $captcha_method || 'recaptcha_v3' === $captcha_method ) {
			$endpoint = 'https://www.google.com/recaptcha/api/siteverify';
		} elseif ( 'turnstile' === $captcha_method ) {
			$endpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
		}

		$response = wp_remote_post(
			$endpoint,
			array(
				'body'    => array(
					'secret'   => $secret_key,
					'response' => $token,
					'remoteip' => $this->get_remote_ip(),
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'smlf_captcha_request_failed', __( 'Captcha verification could not be reached.', 'smart-multistep-lead-forms' ) );
		}

		$result = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! $result || empty( $result->success ) ) {
			return new WP_Error( 'smlf_captcha_failed', __( 'Captcha verification failed.', 'smart-multistep-lead-forms' ) );
		}

		return true;
	}

	private function get_form( $form_id, $published_only = true ) {
		if ( ! $form_id ) {
			return null;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'smlf_forms';

		if ( $published_only ) {
			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d AND status = %s", $form_id, 'publish' ) );
		}

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $form_id ) );
	}

	private function lead_belongs_to_form( $lead_id, $form_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'smlf_leads';
		$count      = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_name} WHERE id = %d AND form_id = %d", $lead_id, $form_id ) );

		return (int) $count > 0;
	}

	private function trigger_webhook( $type, $lead_id, $form_id, $data ) {
		$webhook_url = esc_url_raw( get_option( 'smlf_webhook_url', '' ) );
		if ( empty( $webhook_url ) ) {
			return;
		}

		$payload = array(
			'event'   => 'smlf_lead_' . sanitize_key( $type ),
			'lead_id' => absint( $lead_id ),
			'form_id' => absint( $form_id ),
			'data'    => $data,
		);

		wp_remote_post(
			$webhook_url,
			array(
				'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'        => wp_json_encode( $payload ),
				'method'      => 'POST',
				'data_format' => 'body',
				'timeout'     => 5,
			)
		);
	}

	private function get_captcha_method() {
		$method = sanitize_key( get_option( 'smlf_captcha_method', 'custom' ) );
		return in_array( $method, $this->allowed_captcha_methods, true ) ? $method : 'custom';
	}

	private function get_post_text( $key ) {
		return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}

	private function get_remote_ip() {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}

	private function get_user_agent() {
		return isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	}

	private function create_captcha_pass_token() {
		$timestamp = time();
		$signature = wp_hash( $timestamp . '|smlf_captcha_pass' );

		return $timestamp . ':' . $signature;
	}

	private function validate_captcha_pass_token( $token ) {
		$parts = explode( ':', $token, 2 );
		if ( 2 !== count( $parts ) ) {
			return false;
		}

		$timestamp = absint( $parts[0] );
		if ( ! $timestamp || time() - $timestamp > HOUR_IN_SECONDS ) {
			return false;
		}

		$expected = wp_hash( $timestamp . '|smlf_captcha_pass' );
		return hash_equals( $expected, $parts[1] );
	}

	private function extract_email( $data ) {
		foreach ( $data as $key => $value ) {
			if ( false !== strpos( strtolower( $key ), 'email' ) && is_email( $value ) ) {
				return sanitize_email( $value );
			}
		}

		return '';
	}

	private function extract_phone( $data ) {
		foreach ( $data as $key => $value ) {
			if ( false !== strpos( strtolower( $key ), 'phone' ) ) {
				return sanitize_text_field( $value );
			}
		}

		return '';
	}
}
