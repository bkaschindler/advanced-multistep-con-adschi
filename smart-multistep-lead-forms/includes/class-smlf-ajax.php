<?php

class SMLF_Ajax {

	public function save_form_admin() {
		check_ajax_referer( 'smlf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'smlf_forms';

		$form_id   = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;
		// Normally we sanitize deep, but for complex JSON structure in this demo we use wp_unslash
		$form_data_raw = isset( $_POST['form_data'] ) ? wp_unslash( $_POST['form_data'] ) : '';

		$form_data = json_decode( $form_data_raw, true );
		$title = isset( $form_data['title'] ) ? sanitize_text_field( $form_data['title'] ) : 'New Form';

		$sanitized_form_data = array(
			'title' => $title,
			'steps' => array()
		);
		if ( isset( $form_data['steps'] ) && is_array( $form_data['steps'] ) ) {
			foreach ( $form_data['steps'] as $step ) {
				$sanitized_step = array(
					'step_id'      => isset( $step['step_id'] ) ? intval( $step['step_id'] ) : 0,
					'title'        => isset( $step['title'] ) ? sanitize_text_field( $step['title'] ) : '',
					'logic_target' => isset( $step['logic_target'] ) ? sanitize_text_field( $step['logic_target'] ) : '',
					'logic_value'  => isset( $step['logic_value'] ) ? sanitize_text_field( $step['logic_value'] ) : '',
					'fields'       => array()
				);
				if ( isset( $step['fields'] ) && is_array( $step['fields'] ) ) {
					foreach ( $step['fields'] as $field ) {
						$sanitized_step['fields'][] = array(
							'type'  => sanitize_text_field( $field['type'] ),
							'label' => sanitize_text_field( $field['label'] ),
							'options' => isset( $field['options'] ) ? sanitize_text_field( $field['options'] ) : ''
						);
					}
				}
				$sanitized_form_data['steps'][] = $sanitized_step;
			}
		}

		if ( $form_id ) {
			$wpdb->update(
				$table_name,
				array(
					'title'     => $title,
					'form_data' => wp_json_encode( $sanitized_form_data ),
				),
				array( 'id' => $form_id )
			);
			wp_send_json_success( array( 'form_id' => $form_id ) );
		} else {
			$wpdb->insert(
				$table_name,
				array(
					'title'     => $title,
					'form_data' => wp_json_encode( $sanitized_form_data ),
				)
			);
			wp_send_json_success( array( 'form_id' => $wpdb->insert_id ) );
		}
	}

	public function verify_bot() {
		$captcha_method = get_option('smlf_captcha_method', 'custom');
		$secret_key = get_option('smlf_captcha_secret_key', '');
		$token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';

		if ($captcha_method === 'none' || $captcha_method === 'custom') {
			wp_send_json_success();
		}

		if ($captcha_method === 'recaptcha_v2' || $captcha_method === 'recaptcha_v3') {
			$response = wp_remote_post("https://www.google.com/recaptcha/api/siteverify", array(
				'body' => array(
					'secret' => $secret_key,
					'response' => $token,
					'remoteip' => $_SERVER['REMOTE_ADDR']
				)
			));
			$body = wp_remote_retrieve_body($response);
			$result = json_decode($body);
			if (!$result || !$result->success) {
				wp_send_json_error('reCAPTCHA verification failed.');
			}
		} elseif ($captcha_method === 'turnstile') {
			$response = wp_remote_post("https://challenges.cloudflare.com/turnstile/v0/siteverify", array(
				'body' => array(
					'secret' => $secret_key,
					'response' => $token,
					'remoteip' => $_SERVER['REMOTE_ADDR']
				)
			));
			$body = wp_remote_retrieve_body($response);
			$result = json_decode($body);
			if (!$result || !$result->success) {
				wp_send_json_error('Turnstile verification failed.');
			}
		}

		wp_send_json_success();
	}

	public function save_partial_lead() {
		// Nonce check removed to ensure compatibility with caching plugins

		$enable_partial = get_option( 'smlf_enable_partial', 1 );
		if ( ! $enable_partial ) {
			wp_send_json_success(); // Fake success if disabled
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'smlf_leads';

		$form_id = intval( $_POST['form_id'] );
		$lead_id = isset( $_POST['lead_id'] ) ? intval( $_POST['lead_id'] ) : 0;
		$data_raw = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array();

		// Extract email or phone if present
		$email = '';
		$phone = '';
		$structured_data = array();

		if ( is_array( $data_raw ) ) {
			foreach ( $data_raw as $field ) {
				$name = sanitize_text_field( $field['name'] );
				$val  = sanitize_text_field( $field['value'] );
				$structured_data[ $name ] = $val;

				if ( strpos( strtolower( $name ), 'email' ) !== false && is_email( $val ) ) {
					$email = $val;
				}
				if ( strpos( strtolower( $name ), 'phone' ) !== false ) {
					$phone = $val;
				}
			}
		}

		$lead_data_json = wp_json_encode( $structured_data );

		if ( $lead_id ) {
			$wpdb->update(
				$table_name,
				array(
					'lead_data' => $lead_data_json,
					'email'     => $email,
					'phone'     => $phone,
					'status'    => 'partial lead'
				),
				array( 'id' => $lead_id )
			);
		} else {
			$wpdb->insert(
				$table_name,
				array(
					'form_id'   => $form_id,
					'lead_data' => $lead_data_json,
					'email'     => $email,
					'phone'     => $phone,
					'status'    => 'started',
					'ip_address'=> $_SERVER['REMOTE_ADDR'],
					'user_agent'=> $_SERVER['HTTP_USER_AGENT']
				)
			);
			$lead_id = $wpdb->insert_id;
		}

		$this->trigger_webhook('partial', $lead_id, $form_id, $structured_data);

		wp_send_json_success( array( 'lead_id' => $lead_id ) );
	}

	public function submit_form() {
		// Nonce check removed to ensure compatibility with caching plugins

		global $wpdb;
		$table_name = $wpdb->prefix . 'smlf_leads';

		$form_id = intval( $_POST['form_id'] );
		$lead_id = isset( $_POST['lead_id'] ) ? intval( $_POST['lead_id'] ) : 0;
		$data_raw = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array();

		$email = '';
		$phone = '';
		$structured_data = array();

		if ( is_array( $data_raw ) ) {
			foreach ( $data_raw as $field ) {
				$name = sanitize_text_field( $field['name'] );
				$val  = sanitize_text_field( $field['value'] );
				$structured_data[ $name ] = $val;

				if ( strpos( strtolower( $name ), 'email' ) !== false && is_email( $val ) ) {
					$email = $val;
				}
				if ( strpos( strtolower( $name ), 'phone' ) !== false ) {
					$phone = $val;
				}
			}
		}

		$lead_data_json = wp_json_encode( $structured_data );

		if ( $lead_id ) {
			$wpdb->update(
				$table_name,
				array(
					'lead_data'    => $lead_data_json,
					'email'        => $email,
					'phone'        => $phone,
					'status'       => 'completed',
					'completed_at' => current_time( 'mysql' )
				),
				array( 'id' => $lead_id )
			);
		} else {
			$wpdb->insert(
				$table_name,
				array(
					'form_id'      => $form_id,
					'lead_data'    => $lead_data_json,
					'email'        => $email,
					'phone'        => $phone,
					'status'       => 'completed',
					'ip_address'   => $_SERVER['REMOTE_ADDR'],
					'user_agent'   => $_SERVER['HTTP_USER_AGENT'],
					'completed_at' => current_time( 'mysql' )
				)
			);
			$lead_id = $wpdb->insert_id;
		}

		// Trigger emails
		do_action( 'smlf_form_submitted', $lead_id, $form_id, $structured_data );

		$this->trigger_webhook('completed', $lead_id, $form_id, $structured_data);

		wp_send_json_success( array( 'lead_id' => $lead_id ) );
	}

	private function trigger_webhook($type, $lead_id, $form_id, $data) {
		$webhook_url = get_option('smlf_webhook_url', '');
		if ( empty($webhook_url) ) {
			return;
		}

		$payload = array(
			'event'   => 'smlf_lead_' . $type,
			'lead_id' => $lead_id,
			'form_id' => $form_id,
			'data'    => $data
		);

		wp_remote_post($webhook_url, array(
			'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
			'body'        => wp_json_encode($payload),
			'method'      => 'POST',
			'data_format' => 'body',
			'timeout'     => 5
		));
	}

	public function export_leads_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Permission denied.' );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'smlf_leads';
		$leads = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC", ARRAY_A );

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="smlf-leads-export.csv"');

		$output = fopen('php://output', 'w');
		fputcsv($output, array('ID', 'Form ID', 'Status', 'Email', 'Phone', 'Data', 'Created At'));

		if ( !empty($leads) ) {
			foreach ($leads as $lead) {
				fputcsv($output, array(
					$lead['id'],
					$lead['form_id'],
					$lead['status'],
					$lead['email'],
					$lead['phone'],
					$lead['lead_data'],
					$lead['created_at']
				));
			}
		}

		fclose($output);
		exit;
	}
}
