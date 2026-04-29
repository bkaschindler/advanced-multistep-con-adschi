<?php

class SMLF_Admin {
	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function enqueue_styles( $hook ) {
		if ( strpos( $hook, 'smlf' ) === false ) {
			return;
		}

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/smlf-admin.css', array(), $this->version, 'all' );

		if ( is_rtl() ) {
			wp_enqueue_style( $this->plugin_name . '-rtl', plugin_dir_url( __FILE__ ) . 'css/smlf-admin-rtl.css', array(), $this->version, 'all' );
		}
	}

	public function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'smlf' ) === false ) {
			return;
		}

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/smlf-admin.js', array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-sortable' ), $this->version, true );

		wp_localize_script( $this->plugin_name, 'smlf_admin_obj', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'smlf_admin_nonce' ),
			'i18n'     => $this->get_builder_i18n(),
			'pages'    => $this->get_page_choices(),
			'template' => $this->get_consultation_template(),
			'templates' => array(
				'consultation' => $this->get_consultation_template(),
				'hvac'         => $this->get_hvac_template(),
			),
			'templates_by_language' => $this->get_templates_by_language(),
		) );
	}

	public function add_plugin_admin_menu() {
		global $wpdb;
		$last_viewed_lead = intval( get_option( 'smlf_last_viewed_lead_id', 0 ) );
		$table_name = $wpdb->prefix . 'smlf_leads';
		$new_leads_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $table_name WHERE id > %d AND status='completed'", $last_viewed_lead ) );

		$menu_title = __( 'Smart Forms', 'smart-multistep-lead-forms' );
		$leads_title = __( 'Prospects', 'smart-multistep-lead-forms' );

		if ( $new_leads_count > 0 ) {
			$badge = ' <span class="update-plugins count-' . esc_attr($new_leads_count) . '"><span class="plugin-count">' . esc_html($new_leads_count) . '</span></span>';
			$menu_title .= $badge;
			$leads_title .= $badge;
		}

		add_menu_page(
			__( 'Smart Forms', 'smart-multistep-lead-forms' ),
			$menu_title,
			'manage_options',
			'smlf-forms',
			array( $this, 'display_forms_page' ),
			'dashicons-feedback',
			25
		);

		add_submenu_page(
			'smlf-forms',
			__( 'Forms', 'smart-multistep-lead-forms' ),
			__( 'Forms', 'smart-multistep-lead-forms' ),
			'manage_options',
			'smlf-forms',
			array( $this, 'display_forms_page' )
		);

		add_submenu_page(
			'smlf-forms',
			__( 'Add New Form', 'smart-multistep-lead-forms' ),
			__( 'Add New Form', 'smart-multistep-lead-forms' ),
			'manage_options',
			'smlf-add-form',
			array( $this, 'display_add_form_page' )
		);

		add_submenu_page(
			'smlf-forms',
			__( 'Prospects', 'smart-multistep-lead-forms' ),
			$leads_title,
			'manage_options',
			'smlf-leads',
			array( $this, 'display_leads_page' )
		);

		add_submenu_page(
			'smlf-forms',
			__( 'Email Logs', 'smart-multistep-lead-forms' ),
			__( 'Email Logs', 'smart-multistep-lead-forms' ),
			'manage_options',
			'smlf-email-logs',
			array( $this, 'display_email_logs_page' )
		);

		add_submenu_page(
			'smlf-forms',
			__( 'Settings', 'smart-multistep-lead-forms' ),
			__( 'Settings', 'smart-multistep-lead-forms' ),
			'manage_options',
			'smlf-settings',
			array( $this, 'display_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'smlf_options_group', 'smlf_admin_email', array( 'sanitize_callback' => array( $this, 'sanitize_email_option' ) ) );
		register_setting( 'smlf_options_group', 'smlf_email_admin_subject', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'smlf_options_group', 'smlf_email_admin_intro', array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
		register_setting( 'smlf_options_group', 'smlf_email_user_subject', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'smlf_options_group', 'smlf_email_user_intro', array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
		register_setting( 'smlf_options_group', 'smlf_email_footer_text', array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
		register_setting( 'smlf_options_group', 'smlf_enable_partial', array( 'sanitize_callback' => array( $this, 'sanitize_checkbox_option' ) ) );
		register_setting( 'smlf_options_group', 'smlf_webhook_url', array( 'sanitize_callback' => 'esc_url_raw' ) );
		register_setting( 'smlf_options_group', 'smlf_captcha_method', array( 'sanitize_callback' => array( $this, 'sanitize_captcha_method' ) ) );
		register_setting( 'smlf_options_group', 'smlf_captcha_site_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'smlf_options_group', 'smlf_captcha_secret_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'smlf_options_group', 'smlf_allowed_file_extensions', array( 'sanitize_callback' => array( $this, 'sanitize_file_extensions_option' ) ) );
		register_setting( 'smlf_options_group', 'smlf_max_file_count', array( 'sanitize_callback' => 'absint' ) );
		register_setting( 'smlf_options_group', 'smlf_max_file_size_mb', array( 'sanitize_callback' => 'absint' ) );
		register_setting( 'smlf_options_group', 'smlf_lead_statuses', array( 'sanitize_callback' => array( $this, 'sanitize_lead_statuses_option' ) ) );
		register_setting( 'smlf_options_group', 'smlf_uninstall_data_action', array( 'sanitize_callback' => array( $this, 'sanitize_uninstall_data_action' ) ) );
	}

	public function sanitize_email_option( $value ) {
		$email = sanitize_email( $value );
		return is_email( $email ) ? $email : get_option( 'admin_email' );
	}

	public function sanitize_checkbox_option( $value ) {
		return ! empty( $value ) ? 1 : 0;
	}

	public function sanitize_captcha_method( $value ) {
		$value   = sanitize_key( $value );
		$allowed = array( 'none', 'custom', 'recaptcha_v2', 'recaptcha_v3', 'turnstile' );
		return in_array( $value, $allowed, true ) ? $value : 'custom';
	}

	public function sanitize_uninstall_data_action( $value ) {
		$value   = sanitize_key( $value );
		$allowed = array( 'keep', 'delete' );
		return in_array( $value, $allowed, true ) ? $value : 'keep';
	}

	public function sanitize_file_extensions_option( $value ) {
		$raw        = is_array( $value ) ? $value : explode( ',', (string) $value );
		$extensions = array_unique( array_filter( array_map( 'sanitize_key', array_map( 'trim', $raw ) ) ) );
		return ! empty( $extensions ) ? implode( ',', $extensions ) : 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip';
	}

	public function sanitize_lead_statuses_option( $value ) {
		$lines    = preg_split( '/\r\n|\r|\n/', (string) $value );
		$statuses = array();

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}

			$parts = array_map( 'trim', explode( ':', $line, 2 ) );
			$key   = sanitize_key( $parts[0] );
			$label = isset( $parts[1] ) && '' !== $parts[1] ? sanitize_text_field( $parts[1] ) : sanitize_text_field( $parts[0] );

			if ( '' !== $key && '' !== $label ) {
				$statuses[ $key ] = $label;
			}
		}

		if ( empty( $statuses ) ) {
			return "new:New\ncontacted:Contacted\nqualified:Qualified\nwon:Won\nlost:Lost";
		}

		$output = array();
		foreach ( $statuses as $key => $label ) {
			$output[] = $key . ':' . $label;
		}

		return implode( "\n", $output );
	}

	public function get_builder_i18n() {
		return array(
			'blocks'                  => __( 'Blocks', 'smart-multistep-lead-forms' ),
			'builder_title'           => __( 'Form Builder', 'smart-multistep-lead-forms' ),
			'text_input'              => __( 'Text Input', 'smart-multistep-lead-forms' ),
			'email_input'             => __( 'Email Input', 'smart-multistep-lead-forms' ),
			'phone_input'             => __( 'Phone Input', 'smart-multistep-lead-forms' ),
			'long_text'               => __( 'Long Text', 'smart-multistep-lead-forms' ),
			'file_upload'             => __( 'File Upload', 'smart-multistep-lead-forms' ),
			'message_text'            => __( 'Message Text', 'smart-multistep-lead-forms' ),
			'consent_checkbox'        => __( 'Consent Checkbox', 'smart-multistep-lead-forms' ),
			'clickable_cards'         => __( 'Clickable Cards', 'smart-multistep-lead-forms' ),
			'radio_buttons'           => __( 'Radio Buttons', 'smart-multistep-lead-forms' ),
			'add_step'                => __( '+ Add Step', 'smart-multistep-lead-forms' ),
			'load_template'           => __( 'Load consultation template', 'smart-multistep-lead-forms' ),
			'load_hvac_template'      => __( 'Load HVAC template', 'smart-multistep-lead-forms' ),
			'template_language'       => __( 'Template language', 'smart-multistep-lead-forms' ),
			'save_form'               => __( 'Save Form', 'smart-multistep-lead-forms' ),
			'form_title'              => __( 'Form Title', 'smart-multistep-lead-forms' ),
			'captcha_method'          => __( 'Captcha', 'smart-multistep-lead-forms' ),
			'captcha_inherit'         => __( 'Use global setting', 'smart-multistep-lead-forms' ),
			'captcha_none'            => __( 'Disabled for this form', 'smart-multistep-lead-forms' ),
			'captcha_custom'          => __( 'Custom checkbox', 'smart-multistep-lead-forms' ),
			'captcha_recaptcha_v2'    => __( 'Google reCAPTCHA v2', 'smart-multistep-lead-forms' ),
			'captcha_recaptcha_v3'    => __( 'Google reCAPTCHA v3', 'smart-multistep-lead-forms' ),
			'captcha_turnstile'       => __( 'Cloudflare Turnstile', 'smart-multistep-lead-forms' ),
			'captcha_gate'            => __( 'Show captcha', 'smart-multistep-lead-forms' ),
			'captcha_before_form'     => __( 'Before the form starts', 'smart-multistep-lead-forms' ),
			'captcha_before_submit'   => __( 'Before final submit', 'smart-multistep-lead-forms' ),
			'captcha_on_step'         => __( 'Before a specific step', 'smart-multistep-lead-forms' ),
			'captcha_step'            => __( 'Captcha step number', 'smart-multistep-lead-forms' ),
			'appearance'              => __( 'Appearance', 'smart-multistep-lead-forms' ),
			'form_language'           => __( 'Form language', 'smart-multistep-lead-forms' ),
			'language_auto'           => __( 'Auto detect', 'smart-multistep-lead-forms' ),
			'language_english'        => __( 'English', 'smart-multistep-lead-forms' ),
			'language_german'         => __( 'German', 'smart-multistep-lead-forms' ),
			'language_persian'        => __( 'Persian', 'smart-multistep-lead-forms' ),
			'theme'                   => __( 'Theme', 'smart-multistep-lead-forms' ),
			'theme_consult'           => __( 'Consult Pro', 'smart-multistep-lead-forms' ),
			'theme_hvac'              => __( 'HVAC 3D', 'smart-multistep-lead-forms' ),
			'font_family'             => __( 'Font family', 'smart-multistep-lead-forms' ),
			'primary_color'           => __( 'Primary color', 'smart-multistep-lead-forms' ),
			'accent_color'            => __( 'Accent color', 'smart-multistep-lead-forms' ),
			'background_color'        => __( 'Background color', 'smart-multistep-lead-forms' ),
			'text_color'              => __( 'Text color', 'smart-multistep-lead-forms' ),
			'allowed_file_extensions' => __( 'Allowed file extensions', 'smart-multistep-lead-forms' ),
			'max_file_count'          => __( 'Maximum file count', 'smart-multistep-lead-forms' ),
			'max_file_size_mb'        => __( 'Maximum file size (MB)', 'smart-multistep-lead-forms' ),
			'upload_limits'           => __( 'Upload limits', 'smart-multistep-lead-forms' ),
			'preview_title'           => __( 'Live preview', 'smart-multistep-lead-forms' ),
			'preview_note'            => __( 'This preview updates while you edit. Save the form to publish changes.', 'smart-multistep-lead-forms' ),
			'step'                    => __( 'Step', 'smart-multistep-lead-forms' ),
			'remove'                  => __( 'Remove', 'smart-multistep-lead-forms' ),
			'condition_prefix'        => __( 'Condition: Go to Step', 'smart-multistep-lead-forms' ),
			'condition_middle'        => __( 'if answer equals', 'smart-multistep-lead-forms' ),
			'condition_placeholder'   => __( 'Option name', 'smart-multistep-lead-forms' ),
			'conditional_logic'       => __( 'Conditional logic', 'smart-multistep-lead-forms' ),
			'default_next_step'       => __( 'Default next step', 'smart-multistep-lead-forms' ),
			'add_logic_rule'          => __( 'Add condition', 'smart-multistep-lead-forms' ),
			'if_answer_equals'        => __( 'If answer equals', 'smart-multistep-lead-forms' ),
			'go_to_step'              => __( 'go to step', 'smart-multistep-lead-forms' ),
			'terminal_reset'          => __( 'End step with reset button', 'smart-multistep-lead-forms' ),
			'label'                   => __( 'Label', 'smart-multistep-lead-forms' ),
			'required'                => __( 'Required', 'smart-multistep-lead-forms' ),
			'options'                 => __( 'Options (comma separated)', 'smart-multistep-lead-forms' ),
			'field_width'             => __( 'Field width', 'smart-multistep-lead-forms' ),
			'width_full'              => __( 'Full width', 'smart-multistep-lead-forms' ),
			'width_half'              => __( 'Half width', 'smart-multistep-lead-forms' ),
			'width_third'             => __( 'One third', 'smart-multistep-lead-forms' ),
			'display_mode'            => __( 'Display mode', 'smart-multistep-lead-forms' ),
			'display_default'         => __( 'Default', 'smart-multistep-lead-forms' ),
			'display_cards'           => __( 'Tap cards', 'smart-multistep-lead-forms' ),
			'display_dropdown'        => __( 'Dropdown', 'smart-multistep-lead-forms' ),
			'display_list'            => __( 'List', 'smart-multistep-lead-forms' ),
			'label_color'             => __( 'Label color', 'smart-multistep-lead-forms' ),
			'input_background'        => __( 'Input background', 'smart-multistep-lead-forms' ),
			'input_text_color'        => __( 'Input text color', 'smart-multistep-lead-forms' ),
			'consent_text'            => __( 'Consent text', 'smart-multistep-lead-forms' ),
			'linked_text'             => __( 'Linked text', 'smart-multistep-lead-forms' ),
			'link_url'                => __( 'Link URL', 'smart-multistep-lead-forms' ),
			'link_behavior'           => __( 'Link behavior', 'smart-multistep-lead-forms' ),
			'open_new_tab'            => __( 'Open in new tab', 'smart-multistep-lead-forms' ),
			'popup_wordpress_page'    => __( 'Popup WordPress page', 'smart-multistep-lead-forms' ),
			'popup_custom_text'       => __( 'Popup custom text', 'smart-multistep-lead-forms' ),
			'wordpress_page'          => __( 'WordPress page', 'smart-multistep-lead-forms' ),
			'popup_text'              => __( 'Popup text', 'smart-multistep-lead-forms' ),
			'checked_by_default'      => __( 'Checked by default', 'smart-multistep-lead-forms' ),
			'consent_default_text'    => __( 'I agree to the privacy policy and data processing.', 'smart-multistep-lead-forms' ),
			'option_1'                => __( 'Option 1', 'smart-multistep-lead-forms' ),
			'option_2'                => __( 'Option 2', 'smart-multistep-lead-forms' ),
			'drag_files'              => __( 'Drag files here or click to upload', 'smart-multistep-lead-forms' ),
			'file_note'               => __( 'PDF, images, documents and ZIP files up to 10MB each.', 'smart-multistep-lead-forms' ),
			'back'                    => __( 'Back', 'smart-multistep-lead-forms' ),
			'next'                    => __( 'Next', 'smart-multistep-lead-forms' ),
			'submit'                  => __( 'Submit', 'smart-multistep-lead-forms' ),
			'reset'                   => __( 'Start again', 'smart-multistep-lead-forms' ),
			'save_success'            => __( 'Saved successfully!', 'smart-multistep-lead-forms' ),
			'save_notes'              => __( 'Save Notes', 'smart-multistep-lead-forms' ),
			'save_error'              => __( 'Error saving.', 'smart-multistep-lead-forms' ),
			'confirm_delete_form'     => __( 'Delete this form? Existing leads will be kept.', 'smart-multistep-lead-forms' ),
			'confirm_delete_leads'    => __( 'Delete the selected requests?', 'smart-multistep-lead-forms' ),
			'confirm_delete_all_leads' => __( 'Delete all requests? This cannot be undone.', 'smart-multistep-lead-forms' ),
			'select_leads_to_delete'  => __( 'Select at least one request to delete.', 'smart-multistep-lead-forms' ),
			'selected_requests_count' => __( '%d selected', 'smart-multistep-lead-forms' ),
		);
	}

	public function get_consultation_template() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-smlf-activator.php';
		$template = SMLF_Activator::get_default_consultation_template();

		return array(
			'title' => $template['title'],
			'steps' => array(
				array(
					'step_id'      => 1,
					'title'        => $template['step_start'],
					'logic_target' => 4,
					'logic_value'  => $template['no'],
					'fields'       => array(
						array(
							'field_id' => 'need_consultation',
							'type'     => 'message',
							'label'    => $template['question'],
							'options'  => '',
							'required' => 0,
						),
						array(
							'field_id' => 'consultation_answer',
							'type'     => 'cards',
							'label'    => $template['choice'],
							'options'  => $template['yes'] . ', ' . $template['no'],
							'required' => 1,
						),
					),
				),
				array(
					'step_id'      => 2,
					'title'        => $template['step_basics'],
					'logic_target' => 0,
					'logic_value'  => '',
					'fields'       => array(
						array(
							'field_id' => 'business_category',
							'type'     => 'cards',
							'label'    => $template['category'],
							'options'  => implode( ', ', $template['categories'] ),
							'required' => 1,
						),
						array(
							'field_id' => 'email',
							'type'     => 'email',
							'label'    => $template['email'],
							'options'  => '',
							'required' => 1,
						),
					),
				),
				array(
					'step_id'      => 3,
					'title'        => $template['step_details'],
					'logic_target' => 0,
					'logic_value'  => '',
					'fields'       => array(
						array(
							'field_id' => 'full_name',
							'type'     => 'text',
							'label'    => $template['name'],
							'options'  => '',
							'required' => 0,
						),
						array(
							'field_id' => 'phone',
							'type'     => 'phone',
							'label'    => $template['phone'],
							'options'  => '',
							'required' => 0,
						),
						array(
							'field_id' => 'project_details',
							'type'     => 'textarea',
							'label'    => $template['details'],
							'options'  => '',
							'required' => 0,
						),
						array(
							'field_id' => 'attachments',
							'type'     => 'file',
							'label'    => $template['files'],
							'options'  => '',
							'required' => 0,
						),
					),
				),
				array(
					'step_id'      => 4,
					'title'        => $template['step_decline'],
					'terminal'     => 'reset',
					'logic_target' => 0,
					'logic_value'  => '',
					'fields'       => array(
						array(
							'field_id' => 'decline_message',
							'type'     => 'message',
							'label'    => $template['decline'],
							'options'  => '',
							'required' => 0,
						),
					),
				),
			),
		);
	}

	private function get_templates_by_language() {
		$languages = array(
			'auto' => '',
			'en'   => 'en_US',
			'de'   => 'de_DE',
			'fa'   => 'fa_IR',
		);
		$templates = array();

		foreach ( $languages as $language => $locale ) {
			if ( $locale && function_exists( 'switch_to_locale' ) ) {
				switch_to_locale( $locale );
			}

			$templates[ $language ] = array(
				'consultation' => $this->get_consultation_template(),
				'hvac'         => $this->get_hvac_template(),
			);

			if ( $locale && function_exists( 'restore_previous_locale' ) ) {
				restore_previous_locale();
			}
		}

		return $templates;
	}

	public function get_hvac_template() {
		$service_new         = __( 'New installation', 'smart-multistep-lead-forms' );
		$service_repair      = __( 'Repair', 'smart-multistep-lead-forms' );
		$service_maintenance = __( 'Maintenance', 'smart-multistep-lead-forms' );
		$service_upgrade     = __( 'Energy upgrade', 'smart-multistep-lead-forms' );

		return array(
			'title'    => __( 'Template: Heating and Cooling Consultation', 'smart-multistep-lead-forms' ),
			'settings' => array(
				'form_language'           => 'auto',
				'theme'                   => 'hvac_3d',
				'font_family'             => 'Inter, Arial, sans-serif',
				'primary_color'           => '#0891b2',
				'accent_color'            => '#f97316',
				'background_color'        => '#08111f',
				'text_color'              => '#e5f7ff',
			),
			'steps'    => array(
				array(
					'step_id' => 1,
					'title'   => __( 'System Type', 'smart-multistep-lead-forms' ),
					'fields'  => array(
						array(
							'field_id'     => 'hvac_intro',
							'type'         => 'message',
							'label'        => __( 'Tell us what kind of heating or cooling support you need.', 'smart-multistep-lead-forms' ),
							'required'     => 0,
							'field_width'  => 'full',
						),
						array(
							'field_id'     => 'hvac_service',
							'type'         => 'cards',
							'label'        => __( 'Service request', 'smart-multistep-lead-forms' ),
							'options'      => implode( ', ', array( $service_new, $service_repair, $service_maintenance, $service_upgrade ) ),
							'required'     => 1,
							'field_width'  => 'full',
							'display_mode' => 'cards',
						),
					),
					'logic_rules' => array(
						array(
							'target' => 2,
							'value'  => $service_new,
						),
						array(
							'target' => 3,
							'value'  => $service_repair,
						),
						array(
							'target' => 4,
							'value'  => $service_maintenance,
						),
						array(
							'target' => 5,
							'value'  => $service_upgrade,
						),
					),
				),
				array(
					'step_id'   => 2,
					'title'     => __( 'New Installation Details', 'smart-multistep-lead-forms' ),
					'next_step' => 6,
					'fields'    => array(
						array(
							'field_id'     => 'property_type',
							'type'         => 'radio',
							'label'        => __( 'Property type', 'smart-multistep-lead-forms' ),
							'options'      => __( 'Apartment, House, Office, Retail, Industrial', 'smart-multistep-lead-forms' ),
							'required'     => 1,
							'field_width'  => 'half',
							'display_mode' => 'dropdown',
						),
						array(
							'field_id'     => 'installation_type',
							'type'         => 'radio',
							'label'        => __( 'Preferred system', 'smart-multistep-lead-forms' ),
							'options'      => __( 'Heat pump, Air conditioning, Gas heating, Floor heating, Not sure', 'smart-multistep-lead-forms' ),
							'required'     => 0,
							'field_width'  => 'half',
							'display_mode' => 'dropdown',
						),
						array(
							'field_id'    => 'property_size',
							'type'        => 'text',
							'label'       => __( 'Approximate area', 'smart-multistep-lead-forms' ),
							'required'    => 0,
							'field_width' => 'half',
						),
						array(
							'field_id'     => 'installation_timeline',
							'type'         => 'radio',
							'label'        => __( 'Desired installation time', 'smart-multistep-lead-forms' ),
							'options'      => __( 'As soon as possible, This month, Next 3 months, Planning only', 'smart-multistep-lead-forms' ),
							'required'     => 0,
							'field_width'  => 'full',
							'display_mode' => 'cards',
						),
					),
				),
				array(
					'step_id'   => 3,
					'title'     => __( 'Repair Details', 'smart-multistep-lead-forms' ),
					'next_step' => 6,
					'fields'  => array(
						array(
							'field_id'     => 'repair_issue',
							'type'         => 'radio',
							'label'        => __( 'What is the issue?', 'smart-multistep-lead-forms' ),
							'options'      => __( 'No heating, No cooling, Strange noise, Water leak, Error code, Other', 'smart-multistep-lead-forms' ),
							'required'     => 1,
							'field_width'  => 'full',
							'display_mode' => 'cards',
						),
						array(
							'field_id'    => 'system_model',
							'type'        => 'text',
							'label'       => __( 'System model or brand', 'smart-multistep-lead-forms' ),
							'required'    => 0,
							'field_width' => 'half',
						),
						array(
							'field_id'     => 'repair_urgency',
							'type'         => 'radio',
							'label'        => __( 'Urgency', 'smart-multistep-lead-forms' ),
							'options'      => __( 'Emergency, This week, Flexible appointment', 'smart-multistep-lead-forms' ),
							'required'     => 0,
							'field_width'  => 'half',
							'display_mode' => 'dropdown',
						),
					),
				),
				array(
					'step_id'   => 4,
					'title'     => __( 'Maintenance Details', 'smart-multistep-lead-forms' ),
					'next_step' => 6,
					'fields'    => array(
						array(
							'field_id'     => 'maintenance_type',
							'type'         => 'radio',
							'label'        => __( 'Maintenance type', 'smart-multistep-lead-forms' ),
							'options'      => __( 'Annual service, Filter replacement, Performance check, Safety inspection', 'smart-multistep-lead-forms' ),
							'required'     => 1,
							'field_width'  => 'full',
							'display_mode' => 'cards',
						),
						array(
							'field_id'    => 'last_service',
							'type'        => 'text',
							'label'       => __( 'Last service date', 'smart-multistep-lead-forms' ),
							'required'    => 0,
							'field_width' => 'half',
						),
						array(
							'field_id'    => 'system_count',
							'type'        => 'text',
							'label'       => __( 'Number of systems', 'smart-multistep-lead-forms' ),
							'required'    => 0,
							'field_width' => 'half',
						),
					),
				),
				array(
					'step_id'   => 5,
					'title'     => __( 'Energy Upgrade Details', 'smart-multistep-lead-forms' ),
					'next_step' => 6,
					'fields'    => array(
						array(
							'field_id'     => 'upgrade_goal',
							'type'         => 'radio',
							'label'        => __( 'Main goal', 'smart-multistep-lead-forms' ),
							'options'      => __( 'Lower energy costs, Better comfort, Replace old system, More climate control', 'smart-multistep-lead-forms' ),
							'required'     => 1,
							'field_width'  => 'full',
							'display_mode' => 'cards',
						),
						array(
							'field_id'    => 'monthly_energy_cost',
							'type'        => 'text',
							'label'       => __( 'Monthly energy cost', 'smart-multistep-lead-forms' ),
							'required'    => 0,
							'field_width' => 'half',
						),
						array(
							'field_id'     => 'upgrade_interest',
							'type'         => 'radio',
							'label'        => __( 'Interested equipment', 'smart-multistep-lead-forms' ),
							'options'      => __( 'Heat pump, Smart thermostat, Solar-ready system, Ventilation, Not sure', 'smart-multistep-lead-forms' ),
							'required'     => 0,
							'field_width'  => 'half',
							'display_mode' => 'dropdown',
						),
					),
				),
				array(
					'step_id' => 6,
					'title'   => __( 'Contact and Notes', 'smart-multistep-lead-forms' ),
					'fields'  => array(
						array(
							'field_id'    => 'full_name',
							'type'        => 'text',
							'label'       => __( 'Full name', 'smart-multistep-lead-forms' ),
							'required'    => 0,
							'field_width' => 'half',
						),
						array(
							'field_id'    => 'email',
							'type'        => 'email',
							'label'       => __( 'Email address', 'smart-multistep-lead-forms' ),
							'required'    => 1,
							'field_width' => 'half',
						),
						array(
							'field_id'    => 'phone',
							'type'        => 'phone',
							'label'       => __( 'Phone number', 'smart-multistep-lead-forms' ),
							'required'    => 0,
							'field_width' => 'half',
						),
						array(
							'field_id'    => 'preferred_time',
							'type'        => 'text',
							'label'       => __( 'Preferred visit time', 'smart-multistep-lead-forms' ),
							'required'    => 0,
							'field_width' => 'half',
						),
						array(
							'field_id'    => 'project_details',
							'type'        => 'textarea',
							'label'       => __( 'Describe the issue or goal', 'smart-multistep-lead-forms' ),
							'required'    => 0,
							'field_width' => 'full',
						),
						array(
							'field_id'    => 'attachments',
							'type'        => 'file',
							'label'       => __( 'Photos or documents', 'smart-multistep-lead-forms' ),
							'required'    => 0,
							'field_width' => 'full',
						),
					),
				),
			),
		);
	}

	private function get_page_choices() {
		$pages = get_pages( array(
			'post_status' => 'publish',
			'sort_column' => 'post_title',
			'sort_order'  => 'ASC',
		) );

		return array_map(
			static function( $page ) {
				return array(
					'id'    => absint( $page->ID ),
					'title' => html_entity_decode( get_the_title( $page ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
				);
			},
			$pages
		);
	}

	public function display_forms_page() {
		require_once plugin_dir_path( __FILE__ ) . 'views/smlf-forms-list.php';
		$this->render_footer();
	}

	public function display_add_form_page() {
		require_once plugin_dir_path( __FILE__ ) . 'views/smlf-form-builder.php';
		$this->render_footer();
	}

	public function display_leads_page() {
		require_once plugin_dir_path( __FILE__ ) . 'views/smlf-leads-list.php';
		$this->render_footer();
	}

	public function display_email_logs_page() {
		require_once plugin_dir_path( __FILE__ ) . 'views/smlf-email-logs.php';
		$this->render_footer();
	}

	public function display_settings_page() {
		require_once plugin_dir_path( __FILE__ ) . 'views/smlf-settings.php';
		$this->render_footer();
	}

	private function render_footer() {
		$author_name = defined('SMLF_AUTHOR_NAME') ? SMLF_AUTHOR_NAME : 'Mohammad Babaei';
		$author_url  = defined('SMLF_AUTHOR_URL') ? SMLF_AUTHOR_URL : 'https://adschi.com';
		$version     = defined('SMLF_VERSION') ? SMLF_VERSION : '1.0.0';

		echo '<div class="smlf-admin-footer" style="margin-top: 40px; text-align: center; color: #666; font-size: 13px; padding: 20px 0; border-top: 1px solid #ddd;">';
		echo sprintf(
			/* translators: 1: Author name, 2: Author URL, 3: Plugin version */
			__( 'Developed by <a href="%2$s" target="_blank">%1$s</a> | Version %3$s', 'smart-multistep-lead-forms' ),
			esc_html( $author_name ),
			esc_url( $author_url ),
			esc_html( $version )
		);
		echo '</div>';
	}
}
