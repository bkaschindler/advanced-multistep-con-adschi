<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package    Smart_Multistep_Lead_Forms
 * @subpackage Smart_Multistep_Lead_Forms/includes
 */
class Smart_Multistep_Lead_Forms {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		if ( defined( 'SMLF_VERSION' ) ) {
			$this->version = SMLF_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'smart-multistep-lead-forms';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-smlf-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-smlf-i18n.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-smlf-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-smlf-public.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-smlf-ajax.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-smlf-emails.php';

		$this->loader = new SMLF_Loader();
	}

	private function set_locale() {
		$plugin_i18n = new SMLF_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	private function define_admin_hooks() {
		$plugin_admin = new SMLF_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
	}

	private function define_public_hooks() {
		$plugin_public = new SMLF_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// Register shortcode
		$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );

		// Setup AJAX endpoints
		$plugin_ajax = new SMLF_Ajax();
		$this->loader->add_action( 'wp_ajax_smlf_save_partial', $plugin_ajax, 'save_partial_lead' );
		$this->loader->add_action( 'wp_ajax_nopriv_smlf_save_partial', $plugin_ajax, 'save_partial_lead' );

		$this->loader->add_action( 'wp_ajax_smlf_submit_form', $plugin_ajax, 'submit_form' );
		$this->loader->add_action( 'wp_ajax_nopriv_smlf_submit_form', $plugin_ajax, 'submit_form' );

		$this->loader->add_action( 'wp_ajax_smlf_verify_bot', $plugin_ajax, 'verify_bot' );
		$this->loader->add_action( 'wp_ajax_nopriv_smlf_verify_bot', $plugin_ajax, 'verify_bot' );

		$this->loader->add_action( 'wp_ajax_smlf_save_form_admin', $plugin_ajax, 'save_form_admin' );
		$this->loader->add_action( 'wp_ajax_smlf_delete_form_admin', $plugin_ajax, 'delete_form_admin' );
		$this->loader->add_action( 'wp_ajax_smlf_export_leads_csv', $plugin_ajax, 'export_leads_csv' );
		$this->loader->add_action( 'wp_ajax_smlf_update_lead_status', $plugin_ajax, 'update_lead_status' );
	}

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_loader() {
		return $this->loader;
	}

	public function get_version() {
		return $this->version;
	}
}
