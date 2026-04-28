<?php

class SMLF_Ajax {

	private $allowed_field_types = array( 'text', 'email', 'phone', 'cards', 'radio', 'textarea', 'file', 'message', 'consent' );
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

	public function delete_form_admin() {
		check_ajax_referer( 'smlf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'smart-multistep-lead-forms' ) ), 403 );
		}

		$form_id = isset( $_POST['form_id'] ) ? absint( wp_unslash( $_POST['form_id'] ) ) : 0;
		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Form not found.', 'smart-multistep-lead-forms' ) ), 404 );
		}

		$existing = $this->get_form( $form_id, false );
		if ( ! $existing ) {
			wp_send_json_error( array( 'message' => __( 'Form not found.', 'smart-multistep-lead-forms' ) ), 404 );
		}

		global $wpdb;
		$result = $wpdb->delete(
			$wpdb->prefix . 'smlf_forms',
			array( 'id' => $form_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Could not delete form.', 'smart-multistep-lead-forms' ) ), 500 );
		}

		wp_send_json_success( array( 'form_id' => $form_id ) );
	}

	public function verify_bot() {
		$form_id         = isset( $_POST['form_id'] ) ? absint( wp_unslash( $_POST['form_id'] ) ) : 0;
		$form            = $this->get_form( $form_id );
		$token           = $this->get_post_text( 'token' );
		$custom_verified = $this->get_post_text( 'custom_verified' );
		$result          = $this->verify_captcha_token( $token, $custom_verified, true, $form );

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
		$referrer        = $this->get_request_referrer();

		if ( '' === $email && '' === $phone ) {
			wp_send_json_success();
		}

		$uploaded_files = $this->handle_uploaded_files( $form );
		if ( is_wp_error( $uploaded_files ) ) {
			wp_send_json_error( array( 'message' => $uploaded_files->get_error_message() ), 400 );
		}
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
					'referrer'  => $referrer,
				),
				array( 'id' => $lead_id ),
				array( '%s', '%s', '%s', '%s', '%s' ),
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
					'lead_status' => 'new',
					'ip_address' => $this->get_remote_ip(),
					'user_agent' => $this->get_user_agent(),
					'referrer'   => $referrer,
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
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

		$captcha_result = $this->verify_captcha_token( $this->get_post_text( 'captcha_token' ), $this->get_post_text( 'custom_verified' ), false, $form );
		if ( is_wp_error( $captcha_result ) ) {
			wp_send_json_error( array( 'message' => $captcha_result->get_error_message() ), 400 );
		}

		$lead_id = isset( $_POST['lead_id'] ) ? absint( wp_unslash( $_POST['lead_id'] ) ) : 0;
		if ( $lead_id && ! $this->lead_belongs_to_form( $lead_id, $form_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Lead not found for this form.', 'smart-multistep-lead-forms' ) ), 404 );
		}

		$structured_data = $this->sanitize_submission_data( isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array() );
		$referrer        = $this->get_request_referrer();
		$uploaded_files  = $this->handle_uploaded_files( $form );
		if ( is_wp_error( $uploaded_files ) ) {
			wp_send_json_error( array( 'message' => $uploaded_files->get_error_message() ), 400 );
		}
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
					'referrer'     => $referrer,
				),
				array( 'id' => $lead_id ),
				array( '%s', '%s', '%s', '%s', '%s', '%s' ),
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
					'lead_status'  => 'new',
					'ip_address'   => $this->get_remote_ip(),
					'user_agent'   => $this->get_user_agent(),
					'referrer'     => $referrer,
					'completed_at' => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
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

	public function update_lead_status() {
		check_ajax_referer( 'smlf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'smart-multistep-lead-forms' ) ), 403 );
		}

		$lead_id = isset( $_POST['lead_id'] ) ? absint( wp_unslash( $_POST['lead_id'] ) ) : 0;
		$status  = $this->sanitize_choice(
			isset( $_POST['lead_status'] ) ? wp_unslash( $_POST['lead_status'] ) : 'new',
			array( 'new', 'contacted', 'qualified', 'won', 'lost' ),
			'new'
		);

		if ( ! $lead_id ) {
			wp_send_json_error( array( 'message' => __( 'Lead not found.', 'smart-multistep-lead-forms' ) ), 404 );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'smlf_leads';
		$result     = $wpdb->update(
			$table_name,
			array( 'lead_status' => $status ),
			array( 'id' => $lead_id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Could not update lead status.', 'smart-multistep-lead-forms' ) ), 500 );
		}

		wp_send_json_success( array( 'lead_status' => $status ) );
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

		fputcsv( $output, array( 'ID', 'Form ID', 'Status', 'Lead Status', 'Email', 'Phone', 'Data', 'Created At' ) );

		foreach ( $leads as $lead ) {
			fputcsv(
				$output,
				array(
					$lead['id'],
					$lead['form_id'],
					$lead['status'],
					isset( $lead['lead_status'] ) ? $lead['lead_status'] : 'new',
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
			'title'    => $title,
			'settings' => $this->sanitize_form_settings( isset( $form_data['settings'] ) && is_array( $form_data['settings'] ) ? $form_data['settings'] : array() ),
			'steps'    => array(),
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
				'next_step'    => isset( $step['next_step'] ) ? absint( $step['next_step'] ) : 0,
				'logic_rules'  => array(),
				'logic_target' => isset( $step['logic_target'] ) ? absint( $step['logic_target'] ) : 0,
				'logic_value'  => isset( $step['logic_value'] ) ? sanitize_text_field( $step['logic_value'] ) : '',
				'terminal'     => isset( $step['terminal'] ) && 'reset' === sanitize_key( $step['terminal'] ) ? 'reset' : '',
				'fields'       => array(),
			);

			if ( ! empty( $step['logic_rules'] ) && is_array( $step['logic_rules'] ) ) {
				foreach ( $step['logic_rules'] as $rule ) {
					if ( ! is_array( $rule ) ) {
						continue;
					}

					$target = isset( $rule['target'] ) ? absint( $rule['target'] ) : 0;
					$value  = isset( $rule['value'] ) ? sanitize_text_field( $rule['value'] ) : '';
					if ( $target && '' !== $value ) {
						$sanitized_step['logic_rules'][] = array(
							'target' => $target,
							'value'  => $value,
						);
					}
				}
			}

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
					'field_id'         => $field_id,
					'type'             => $type,
					'label'            => $label,
					'options'          => isset( $field['options'] ) ? sanitize_textarea_field( $field['options'] ) : '',
					'required'         => ! empty( $field['required'] ) ? 1 : 0,
					'field_width'      => $this->sanitize_choice( isset( $field['field_width'] ) ? $field['field_width'] : 'full', array( 'full', 'half', 'third' ), 'full' ),
					'display_mode'     => $this->sanitize_choice( isset( $field['display_mode'] ) ? $field['display_mode'] : 'default', array( 'default', 'cards', 'dropdown', 'list' ), 'default' ),
					'label_color'      => isset( $field['label_color'] ) ? sanitize_hex_color( $field['label_color'] ) : '',
					'input_background' => isset( $field['input_background'] ) ? sanitize_hex_color( $field['input_background'] ) : '',
					'input_text_color' => isset( $field['input_text_color'] ) ? sanitize_hex_color( $field['input_text_color'] ) : '',
					'consent_text'     => isset( $field['consent_text'] ) ? sanitize_textarea_field( $field['consent_text'] ) : '',
					'link_text'        => isset( $field['link_text'] ) ? sanitize_text_field( $field['link_text'] ) : '',
					'link_url'         => isset( $field['link_url'] ) ? esc_url_raw( $field['link_url'] ) : '',
					'link_behavior'    => $this->sanitize_choice( isset( $field['link_behavior'] ) ? $field['link_behavior'] : 'new_tab', array( 'new_tab', 'popup_page', 'popup_text' ), 'new_tab' ),
					'link_page_id'     => isset( $field['link_page_id'] ) ? absint( $field['link_page_id'] ) : 0,
					'popup_text'       => isset( $field['popup_text'] ) ? wp_kses_post( $field['popup_text'] ) : '',
					'checked_default'  => ! empty( $field['checked_default'] ) ? 1 : 0,
				);
			}

			$sanitized['steps'][] = $sanitized_step;
		}

		return $sanitized;
	}

	private function sanitize_form_settings( $settings ) {
		$allowed_methods = array( 'inherit', 'none', 'custom', 'recaptcha_v2', 'recaptcha_v3', 'turnstile' );
		$allowed_gates   = array( 'before_form', 'before_submit', 'on_step' );

		$captcha_method = isset( $settings['captcha_method'] ) ? sanitize_key( $settings['captcha_method'] ) : 'inherit';
		$captcha_gate   = isset( $settings['captcha_gate'] ) ? sanitize_key( $settings['captcha_gate'] ) : 'before_form';
		$captcha_step   = isset( $settings['captcha_step'] ) ? absint( $settings['captcha_step'] ) : 1;
		$form_language  = $this->sanitize_choice( isset( $settings['form_language'] ) ? $settings['form_language'] : 'auto', array( 'auto', 'en', 'de', 'fa' ), 'auto' );
		$theme          = $this->sanitize_choice( isset( $settings['theme'] ) ? $settings['theme'] : 'consult_pro', array( 'consult_pro', 'hvac_3d' ), 'consult_pro' );
		$font_family    = isset( $settings['font_family'] ) ? sanitize_text_field( $settings['font_family'] ) : 'inherit';

		$sanitized = array(
			'captcha_method'          => in_array( $captcha_method, $allowed_methods, true ) ? $captcha_method : 'inherit',
			'captcha_gate'            => in_array( $captcha_gate, $allowed_gates, true ) ? $captcha_gate : 'before_form',
			'captcha_step'            => max( 1, $captcha_step ),
			'form_language'           => $form_language,
			'theme'                   => $theme,
			'font_family'             => '' !== $font_family ? $font_family : 'inherit',
			'primary_color'           => isset( $settings['primary_color'] ) ? sanitize_hex_color( $settings['primary_color'] ) : '#0ea5e9',
			'accent_color'            => isset( $settings['accent_color'] ) ? sanitize_hex_color( $settings['accent_color'] ) : '#14b8a6',
			'background_color'        => isset( $settings['background_color'] ) ? sanitize_hex_color( $settings['background_color'] ) : '#ffffff',
			'text_color'              => isset( $settings['text_color'] ) ? sanitize_hex_color( $settings['text_color'] ) : '#111827',
		);

		return $sanitized;
	}

	private function sanitize_choice( $value, $allowed, $fallback ) {
		$value = sanitize_key( $value );
		return in_array( $value, $allowed, true ) ? $value : $fallback;
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

	private function handle_uploaded_files( $form = null ) {
		if ( empty( $_FILES['smlf_files'] ) || empty( $_FILES['smlf_files']['name'] ) || ! is_array( $_FILES['smlf_files']['name'] ) ) {
			return array();
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$upload_settings = $this->get_upload_settings( $form );
		$file_names      = array_filter( array_map( 'trim', $_FILES['smlf_files']['name'] ) );

		if ( count( $file_names ) > $upload_settings['max_file_count'] ) {
			return new WP_Error( 'smlf_too_many_files', sprintf(
				/* translators: %d: max file count */
				__( 'You can upload up to %d files.', 'smart-multistep-lead-forms' ),
				$upload_settings['max_file_count']
			) );
		}

		$uploaded            = array();
		$allowed_mimes       = array(
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
		$allowed_mimes       = $this->filter_mimes_by_extensions( $allowed_mimes, $upload_settings['allowed_file_extensions'] );
		$max_file_size_bytes = $upload_settings['max_file_size_mb'] * MB_IN_BYTES;

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

			$extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
			if ( ! in_array( $extension, $upload_settings['allowed_file_extensions'], true ) ) {
				return new WP_Error( 'smlf_file_type_not_allowed', sprintf(
					/* translators: %s: file name */
					__( 'This file type is not allowed: %s', 'smart-multistep-lead-forms' ),
					$file['name']
				) );
			}

			if ( $file['size'] > $max_file_size_bytes ) {
				return new WP_Error( 'smlf_file_too_large', sprintf(
					/* translators: 1: file name, 2: max size in MB */
					__( '%1$s is larger than %2$dMB.', 'smart-multistep-lead-forms' ),
					$file['name'],
					$upload_settings['max_file_size_mb']
				) );
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

	private function get_upload_settings( $form = null ) {
		$settings = array(
			'allowed_file_extensions' => array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', get_option( 'smlf_allowed_file_extensions', 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip' ) ) ) ) ),
			'max_file_count'          => max( 1, absint( get_option( 'smlf_max_file_count', 5 ) ) ),
			'max_file_size_mb'        => max( 1, absint( get_option( 'smlf_max_file_size_mb', 10 ) ) ),
		);

		if ( empty( $settings['allowed_file_extensions'] ) ) {
			$settings['allowed_file_extensions'] = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip' );
		}

		return $settings;
	}

	private function sanitize_file_extensions( $extensions ) {
		$extensions = is_array( $extensions ) ? $extensions : explode( ',', (string) $extensions );
		$extensions = array_unique( array_filter( array_map( 'sanitize_key', array_map( 'trim', $extensions ) ) ) );

		return implode( ',', $extensions );
	}

	private function filter_mimes_by_extensions( $allowed_mimes, $allowed_extensions ) {
		$filtered = array();

		foreach ( $allowed_mimes as $extension_group => $mime ) {
			$group_extensions = explode( '|', $extension_group );
			if ( array_intersect( $group_extensions, $allowed_extensions ) ) {
				$filtered[ $extension_group ] = $mime;
			}
		}

		return $filtered;
	}

	private function verify_captcha_token( $token, $custom_verified = '', $is_verify_step = false, $form = null ) {
		$captcha_method = $this->get_captcha_method( $form );
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

	private function get_captcha_method( $form = null ) {
		$method = 'inherit';

		if ( $form && ! empty( $form->form_data ) ) {
			$form_data = json_decode( $form->form_data, true );
			if ( is_array( $form_data ) && isset( $form_data['settings']['captcha_method'] ) ) {
				$method = sanitize_key( $form_data['settings']['captcha_method'] );
			}
		}

		if ( 'inherit' === $method ) {
			$method = sanitize_key( get_option( 'smlf_captcha_method', 'custom' ) );
		}

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

	private function get_request_referrer() {
		if ( isset( $_POST['page_url'] ) ) {
			return esc_url_raw( wp_unslash( $_POST['page_url'] ) );
		}

		return isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
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
