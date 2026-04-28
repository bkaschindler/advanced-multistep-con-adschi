<?php

/**
 * Fired during plugin activation
 *
 * @package    Smart_Multistep_Lead_Forms
 * @subpackage Smart_Multistep_Lead_Forms/includes
 */
class SMLF_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		self::create_tables();
		update_option( 'smlf_version', defined( 'SMLF_VERSION' ) ? SMLF_VERSION : '1.0.0' );
	}

	private static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// Forms table
		$table_forms = $wpdb->prefix . 'smlf_forms';
		$sql_forms = "CREATE TABLE $table_forms (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			form_data longtext NOT NULL,
			settings longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
			status varchar(50) DEFAULT 'publish' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// Leads table
		$table_leads = $wpdb->prefix . 'smlf_leads';
		$sql_leads = "CREATE TABLE $table_leads (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			form_id bigint(20) NOT NULL,
			lead_data longtext NOT NULL,
			status varchar(50) DEFAULT 'started' NOT NULL,
			lead_status varchar(50) DEFAULT 'new' NOT NULL,
			email varchar(255),
			phone varchar(50),
			ip_address varchar(50),
			user_agent text,
			referrer text,
			utm_source varchar(255),
			utm_medium varchar(255),
			utm_campaign varchar(255),
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
			completed_at datetime,
			admin_notes text,
			PRIMARY KEY  (id),
			KEY form_id (form_id),
			KEY status (status),
			KEY lead_status (lead_status)
		) $charset_collate;";

		// Email Logs table
		$table_emails = $wpdb->prefix . 'smlf_email_logs';
		$sql_emails = "CREATE TABLE $table_emails (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			lead_id bigint(20),
			recipient_email varchar(255) NOT NULL,
			subject varchar(255) NOT NULL,
			body longtext NOT NULL,
			status varchar(50) DEFAULT 'sent' NOT NULL,
			sent_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY lead_id (lead_id)
		) $charset_collate;";

		dbDelta( $sql_forms );
		dbDelta( $sql_leads );
		dbDelta( $sql_emails );
	}

	private static function create_default_consultation_form() {
		global $wpdb;

		$table_forms = $wpdb->prefix . 'smlf_forms';
		$template    = self::get_default_consultation_template();
		$total_forms = $wpdb->get_var( "SELECT COUNT(id) FROM {$table_forms}" );
		$existing    = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_forms} WHERE title = %s", $template['title'] ) );

		if ( $existing || $total_forms ) {
			return;
		}

		$form_data = array(
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

		$wpdb->insert(
			$table_forms,
			array(
				'title'     => $template['title'],
				'form_data' => wp_json_encode( $form_data ),
				'status'    => 'publish',
			),
			array( '%s', '%s', '%s' )
		);
	}

	public static function get_default_consultation_template() {
		return array(
			'title'        => __( 'Template: Free Consultation', 'smart-multistep-lead-forms' ),
			'step_start'   => __( 'Start consultation', 'smart-multistep-lead-forms' ),
			'step_basics'  => __( 'Basic details', 'smart-multistep-lead-forms' ),
			'step_details' => __( 'More details', 'smart-multistep-lead-forms' ),
			'step_decline' => __( 'Whenever you are ready', 'smart-multistep-lead-forms' ),
			'question'     => __( 'Do you need a free consultation?', 'smart-multistep-lead-forms' ),
			'choice'       => __( 'Your choice', 'smart-multistep-lead-forms' ),
			'yes'          => __( 'Yes', 'smart-multistep-lead-forms' ),
			'no'           => __( 'No', 'smart-multistep-lead-forms' ),
			'category'     => __( 'What type of work do you do?', 'smart-multistep-lead-forms' ),
			'categories'   => array(
				__( 'Online store', 'smart-multistep-lead-forms' ),
				__( 'Local services', 'smart-multistep-lead-forms' ),
				__( 'Education', 'smart-multistep-lead-forms' ),
				__( 'Medical and beauty', 'smart-multistep-lead-forms' ),
				__( 'Company or startup', 'smart-multistep-lead-forms' ),
				__( 'Other', 'smart-multistep-lead-forms' ),
			),
			'email'        => __( 'Your email address', 'smart-multistep-lead-forms' ),
			'name'         => __( 'Full name', 'smart-multistep-lead-forms' ),
			'phone'        => __( 'Phone number', 'smart-multistep-lead-forms' ),
			'details'      => __( 'More information', 'smart-multistep-lead-forms' ),
			'files'        => __( 'Relevant files', 'smart-multistep-lead-forms' ),
			'decline'      => __( 'No problem. If you need help later, you can start again here anytime.', 'smart-multistep-lead-forms' ),
		);
	}

}
