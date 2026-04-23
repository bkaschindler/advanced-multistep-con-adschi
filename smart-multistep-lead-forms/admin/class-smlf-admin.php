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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/smlf-admin.js', array( 'jquery', 'jquery-ui-sortable' ), $this->version, true );

		wp_localize_script( $this->plugin_name, 'smlf_admin_obj', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'smlf_admin_nonce' ),
			'i18n'     => array(
				'save_success' => __( 'Saved successfully!', 'smart-multistep-lead-forms' ),
				'save_error'   => __( 'Error saving.', 'smart-multistep-lead-forms' ),
			)
		) );
	}

	public function add_plugin_admin_menu() {
		global $wpdb;
		$last_viewed_lead = intval( get_option( 'smlf_last_viewed_lead_id', 0 ) );
		$table_name = $wpdb->prefix . 'smlf_leads';
		$new_leads_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $table_name WHERE id > %d AND status='completed'", $last_viewed_lead ) );

		$menu_title = __( 'Smart Forms', 'smart-multistep-lead-forms' );
		$leads_title = __( 'Leads', 'smart-multistep-lead-forms' );

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
			__( 'Leads', 'smart-multistep-lead-forms' ),
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
