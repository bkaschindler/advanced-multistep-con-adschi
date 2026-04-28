<?php

class SMLF_Public {
	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/smlf-public.css', array(), $this->version, 'all' );

		if ( is_rtl() ) {
			wp_enqueue_style( $this->plugin_name . '-rtl', plugin_dir_url( __FILE__ ) . 'css/smlf-public-rtl.css', array(), $this->version, 'all' );
		}
	}

	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/smlf-public.js', array( 'jquery' ), $this->version, true );

		$allowed_methods = array( 'none', 'custom', 'recaptcha_v2', 'recaptcha_v3', 'turnstile' );
		$captcha_method  = sanitize_key( get_option( 'smlf_captcha_method', 'custom' ) );
		$captcha_method  = in_array( $captcha_method, $allowed_methods, true ) ? $captcha_method : 'custom';
		$site_key        = sanitize_text_field( get_option( 'smlf_captcha_site_key', '' ) );

		if ($captcha_method === 'recaptcha_v2' || $captcha_method === 'recaptcha_v3') {
			$recaptcha_url = 'https://www.google.com/recaptcha/api.js';
			if ($captcha_method === 'recaptcha_v3') {
				$recaptcha_url = add_query_arg( 'render', $site_key, $recaptcha_url );
			}
			wp_enqueue_script( 'smlf-recaptcha', esc_url_raw( $recaptcha_url ), array(), null, true );
		}

		if ($captcha_method === 'turnstile') {
			wp_enqueue_script( 'smlf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true );
		}

		wp_localize_script( $this->plugin_name, 'smlf_public_obj', array(
			'ajax_url'       => admin_url( 'admin-ajax.php' ),
			'captcha_method' => $captcha_method,
			'site_key'       => $site_key,
			'i18n'           => array(
				'please_verify' => __( 'Please verify you are human.', 'smart-multistep-lead-forms' ),
				'required'      => __( 'Please complete the required fields.', 'smart-multistep-lead-forms' ),
				'invalid_email' => __( 'Please enter a valid email address.', 'smart-multistep-lead-forms' ),
				'too_many_files' => __( 'You can upload up to %d files.', 'smart-multistep-lead-forms' ),
				'file_type'     => __( 'This file type is not allowed: %s', 'smart-multistep-lead-forms' ),
				'file_size'     => __( 'This file is larger than %1$dMB: %2$s', 'smart-multistep-lead-forms' ),
				'submitting'    => __( 'Submitting...', 'smart-multistep-lead-forms' ),
				'submit'        => __( 'Submit', 'smart-multistep-lead-forms' ),
				'error'         => __( 'Something went wrong. Please try again.', 'smart-multistep-lead-forms' ),
			),
		) );
	}

	public function register_shortcodes() {
		add_shortcode( 'smlf_form', array( $this, 'render_form' ) );
	}

	public function render_form( $atts ) {
		$atts = shortcode_atts( array(
			'id' => 0,
		), $atts, 'smlf_form' );

		$form_id = intval( $atts['id'] );
		if ( ! $form_id ) {
			return '<p>' . esc_html__( 'Invalid Form ID.', 'smart-multistep-lead-forms' ) . '</p>';
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'smlf_forms';
		$form = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d AND status = %s", $form_id, 'publish' ) );

		if ( ! $form ) {
			return '<p>' . esc_html__( 'Form not found.', 'smart-multistep-lead-forms' ) . '</p>';
		}

		$form_data = json_decode( $form->form_data, true );
		$form_data = is_array( $form_data ) ? $form_data : array();
		$steps     = isset( $form_data['steps'] ) && is_array( $form_data['steps'] ) ? $form_data['steps'] : array();
		$settings  = isset( $form_data['settings'] ) && is_array( $form_data['settings'] ) ? $form_data['settings'] : array();
		$settings  = $this->resolve_form_settings( $settings );
		$this->enqueue_form_captcha_script( $settings );

		ob_start();
		require plugin_dir_path( dirname( __FILE__ ) ) . 'templates/form-template.php';
		return ob_get_clean();
	}

	private function resolve_form_settings( $settings ) {
		$global_method = sanitize_key( get_option( 'smlf_captcha_method', 'custom' ) );
		$allowed       = array( 'none', 'custom', 'recaptcha_v2', 'recaptcha_v3', 'turnstile' );
		$method        = isset( $settings['captcha_method'] ) ? sanitize_key( $settings['captcha_method'] ) : 'inherit';
		$gate          = isset( $settings['captcha_gate'] ) ? sanitize_key( $settings['captcha_gate'] ) : 'before_form';
		$step          = isset( $settings['captcha_step'] ) ? absint( $settings['captcha_step'] ) : 1;
		$extensions    = $this->sanitize_file_extensions( get_option( 'smlf_allowed_file_extensions', 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip' ) );
		$max_count     = absint( get_option( 'smlf_max_file_count', 5 ) );
		$max_size      = absint( get_option( 'smlf_max_file_size_mb', 10 ) );
		$theme         = $this->sanitize_choice( isset( $settings['theme'] ) ? $settings['theme'] : 'consult_pro', array( 'consult_pro', 'hvac_3d' ), 'consult_pro' );
		$font_family   = isset( $settings['font_family'] ) ? sanitize_text_field( $settings['font_family'] ) : 'inherit';

		if ( 'inherit' === $method ) {
			$method = in_array( $global_method, $allowed, true ) ? $global_method : 'custom';
		}

		if ( ! in_array( $method, $allowed, true ) ) {
			$method = 'custom';
		}

		if ( ! in_array( $gate, array( 'before_form', 'before_submit', 'on_step' ), true ) ) {
			$gate = 'before_form';
		}

		return array(
			'captcha_method'          => $method,
			'captcha_gate'            => $gate,
			'captcha_step'            => max( 1, $step ),
			'allowed_file_extensions' => '' !== $extensions ? $extensions : 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip',
			'max_file_count'          => max( 1, $max_count ),
			'max_file_size_mb'        => max( 1, $max_size ),
			'theme'                   => $theme,
			'font_family'             => '' !== $font_family ? $font_family : 'inherit',
			'primary_color'           => isset( $settings['primary_color'] ) ? sanitize_hex_color( $settings['primary_color'] ) : '#0ea5e9',
			'accent_color'            => isset( $settings['accent_color'] ) ? sanitize_hex_color( $settings['accent_color'] ) : '#14b8a6',
			'background_color'        => isset( $settings['background_color'] ) ? sanitize_hex_color( $settings['background_color'] ) : '#ffffff',
			'text_color'              => isset( $settings['text_color'] ) ? sanitize_hex_color( $settings['text_color'] ) : '#111827',
		);
	}

	private function sanitize_choice( $value, $allowed, $fallback ) {
		$value = sanitize_key( $value );
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	private function sanitize_file_extensions( $extensions ) {
		$extensions = is_array( $extensions ) ? $extensions : explode( ',', (string) $extensions );
		$extensions = array_unique( array_filter( array_map( 'sanitize_key', array_map( 'trim', $extensions ) ) ) );

		return implode( ',', $extensions );
	}

	private function enqueue_form_captcha_script( $settings ) {
		$method   = isset( $settings['captcha_method'] ) ? sanitize_key( $settings['captcha_method'] ) : 'none';
		$site_key = sanitize_text_field( get_option( 'smlf_captcha_site_key', '' ) );

		if ( 'recaptcha_v2' === $method || 'recaptcha_v3' === $method ) {
			$recaptcha_url = 'https://www.google.com/recaptcha/api.js';
			if ( 'recaptcha_v3' === $method ) {
				$recaptcha_url = add_query_arg( 'render', $site_key, $recaptcha_url );
			}
			wp_enqueue_script( 'smlf-recaptcha', esc_url_raw( $recaptcha_url ), array(), null, true );
		}

		if ( 'turnstile' === $method ) {
			wp_enqueue_script( 'smlf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true );
		}
	}
}
