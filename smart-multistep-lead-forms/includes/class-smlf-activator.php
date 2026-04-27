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
		self::create_default_consultation_form();
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
			KEY status (status)
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
		$existing    = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_forms} WHERE title = %s", $template['title'] ) );

		if ( $existing ) {
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

	private static function get_default_consultation_template() {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();

		if ( 0 === strpos( $locale, 'de_' ) ) {
			return array(
				'title'        => 'Vorlage: Kostenlose Beratung',
				'step_start'   => 'Beratung starten',
				'step_basics'  => 'Erste Angaben',
				'step_details' => 'Weitere Details',
				'step_decline' => 'Jederzeit bereit',
				'question'     => 'Moechten Sie eine kostenlose Beratung?',
				'choice'       => 'Ihre Auswahl',
				'yes'          => 'Ja',
				'no'           => 'Nein',
				'category'     => 'In welchem Bereich arbeiten Sie?',
				'categories'   => array( 'Online-Shop', 'Lokale Dienstleistungen', 'Bildung', 'Medizin und Beauty', 'Unternehmen oder Startup', 'Sonstiges' ),
				'email'        => 'Ihre E-Mail-Adresse',
				'name'         => 'Vollstaendiger Name',
				'phone'        => 'Telefonnummer',
				'details'      => 'Weitere Informationen',
				'files'        => 'Relevante Dateien',
				'decline'      => 'Kein Problem. Wenn Sie spaeter Beratung brauchen, koennen Sie hier jederzeit neu starten.',
			);
		}

		if ( 0 === strpos( $locale, 'fa_' ) ) {
			return array(
				'title'        => 'قالب آماده مشاوره رایگان',
				'step_start'   => 'شروع مشاوره',
				'step_basics'  => 'اطلاعات اولیه',
				'step_details' => 'جزئیات بیشتر',
				'step_decline' => 'هر زمان آماده بودید',
				'question'     => 'آیا نیاز به مشاوره رایگان دارید؟',
				'choice'       => 'انتخاب شما',
				'yes'          => 'بله',
				'no'           => 'خیر',
				'category'     => 'دسته کاری شما چیست؟',
				'categories'   => array( 'فروشگاه آنلاین', 'خدمات محلی', 'آموزشی', 'پزشکی و زیبایی', 'شرکت یا استارتاپ', 'سایر' ),
				'email'        => 'ایمیل شما',
				'name'         => 'نام و نام خانوادگی',
				'phone'        => 'شماره تماس',
				'details'      => 'توضیحات کامل‌تر',
				'files'        => 'فایل‌های مرتبط',
				'decline'      => 'هر زمان خواستید، همین‌جا برگردید. یک تصمیم خوب همیشه از زمان درست شروع می‌شود.',
			);
		}

		return array(
			'title'        => 'Template: Free Consultation',
			'step_start'   => 'Start consultation',
			'step_basics'  => 'Basic details',
			'step_details' => 'More details',
			'step_decline' => 'Whenever you are ready',
			'question'     => 'Do you need a free consultation?',
			'choice'       => 'Your choice',
			'yes'          => 'Yes',
			'no'           => 'No',
			'category'     => 'What type of work do you do?',
			'categories'   => array( 'Online store', 'Local services', 'Education', 'Medical and beauty', 'Company or startup', 'Other' ),
			'email'        => 'Your email address',
			'name'         => 'Full name',
			'phone'        => 'Phone number',
			'details'      => 'More information',
			'files'        => 'Relevant files',
			'decline'      => 'No problem. If you need help later, you can start again here anytime.',
		);
	}

}
