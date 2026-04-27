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

		ob_start();
		require plugin_dir_path( dirname( __FILE__ ) ) . 'templates/form-template.php';
		return ob_get_clean();
	}
}
