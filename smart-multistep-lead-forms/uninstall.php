<?php
/**
 * Uninstall cleanup for Smart MultiStep Lead Forms.
 *
 * @package Smart_Multistep_Lead_Forms
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$action = get_option( 'smlf_uninstall_data_action', 'keep' );

if ( 'delete' !== $action ) {
	return;
}

global $wpdb;

$tables = array(
	$wpdb->prefix . 'smlf_email_logs',
	$wpdb->prefix . 'smlf_leads',
	$wpdb->prefix . 'smlf_forms',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

$options = array(
	'smlf_admin_email',
	'smlf_enable_partial',
	'smlf_webhook_url',
	'smlf_captcha_method',
	'smlf_captcha_site_key',
	'smlf_captcha_secret_key',
	'smlf_allowed_file_extensions',
	'smlf_max_file_count',
	'smlf_max_file_size_mb',
	'smlf_uninstall_data_action',
	'smlf_last_viewed_lead_id',
	'smlf_version',
);

foreach ( $options as $option ) {
	delete_option( $option );
}
