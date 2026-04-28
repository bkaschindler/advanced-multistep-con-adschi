<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://adschi.com
 * @since             1.0.0
 * @package           Smart_Multistep_Lead_Forms
 *
 * @wordpress-plugin
 * Plugin Name:       Smart MultiStep Lead Forms
 * Plugin URI:        https://adschi.com
 * Description:       A modern, fast, visual drag-and-drop multi-step form builder with AJAX submission and partial lead auto-saving.
 * Version:           1.3.1
 * Author:            Mohammad Babaei
 * Author URI:        https://adschi.com
 * Text Domain:       smart-multistep-lead-forms
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SMLF_VERSION', '1.3.1' );
define( 'SMLF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SMLF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SMLF_AUTHOR_NAME', 'Mohammad Babaei' );
define( 'SMLF_AUTHOR_URL', 'https://adschi.com' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-smlf-activator.php
 */
function activate_smart_multistep_lead_forms() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-smlf-activator.php';
	SMLF_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-smlf-deactivator.php
 */
function deactivate_smart_multistep_lead_forms() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-smlf-deactivator.php';
	SMLF_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_smart_multistep_lead_forms' );
register_deactivation_hook( __FILE__, 'deactivate_smart_multistep_lead_forms' );

if ( get_option( 'smlf_version' ) !== SMLF_VERSION ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-smlf-activator.php';
	SMLF_Activator::activate();
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-smart-multistep-lead-forms.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_smart_multistep_lead_forms() {

	$plugin = new Smart_Multistep_Lead_Forms();
	$plugin->run();

}
run_smart_multistep_lead_forms();
