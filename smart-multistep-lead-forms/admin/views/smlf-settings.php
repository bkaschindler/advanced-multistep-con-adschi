<?php
$default_admin_subject = __( 'New lead #{lead_id} from {form_title}', 'smart-multistep-lead-forms' );
$default_admin_intro   = __( "A new completed request has been submitted from {form_title}.\n\nLead ID: {lead_id}\nSource page: {page_url}\n\nCustomer details and all submitted answers are listed below.\n\nQuick summary:\n{summary}", 'smart-multistep-lead-forms' );
$default_user_subject  = __( 'We received your request, {customer_name}', 'smart-multistep-lead-forms' );
$default_user_intro    = __( "Hello {customer_name},\n\nThank you for your request. We received your information and will contact you soon.\n\nHere is a copy of the details you submitted:\n{summary}", 'smart-multistep-lead-forms' );
$default_footer_text   = __( 'This email was sent automatically by {site_name}. Please keep it for your records.', 'smart-multistep-lead-forms' );
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Settings', 'smart-multistep-lead-forms' ); ?></h1>
	<hr class="wp-header-end">

	<form method="post" action="options.php">
		<?php
		settings_fields( 'smlf_options_group' );
		do_settings_sections( 'smlf-settings' );
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Admin Notification Email', 'smart-multistep-lead-forms' ); ?></th>
				<td>
					<input type="email" name="smlf_admin_email" value="<?php echo esc_attr( get_option('smlf_admin_email', get_option('admin_email')) ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Where should lead notifications be sent?', 'smart-multistep-lead-forms' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Email Templates', 'smart-multistep-lead-forms' ); ?></th>
				<td>
					<div class="smlf-email-template-grid">
						<section>
							<h3><?php esc_html_e( 'Admin email', 'smart-multistep-lead-forms' ); ?></h3>
							<label>
								<span><?php esc_html_e( 'Subject', 'smart-multistep-lead-forms' ); ?></span>
								<input type="text" name="smlf_email_admin_subject" value="<?php echo esc_attr( get_option( 'smlf_email_admin_subject', $default_admin_subject ) ); ?>" class="large-text">
							</label>
							<label>
								<span><?php esc_html_e( 'Intro text', 'smart-multistep-lead-forms' ); ?></span>
								<textarea name="smlf_email_admin_intro" rows="7" class="large-text"><?php echo esc_textarea( get_option( 'smlf_email_admin_intro', $default_admin_intro ) ); ?></textarea>
							</label>
						</section>
						<section>
							<h3><?php esc_html_e( 'Customer email', 'smart-multistep-lead-forms' ); ?></h3>
							<label>
								<span><?php esc_html_e( 'Subject', 'smart-multistep-lead-forms' ); ?></span>
								<input type="text" name="smlf_email_user_subject" value="<?php echo esc_attr( get_option( 'smlf_email_user_subject', $default_user_subject ) ); ?>" class="large-text">
							</label>
							<label>
								<span><?php esc_html_e( 'Intro text', 'smart-multistep-lead-forms' ); ?></span>
								<textarea name="smlf_email_user_intro" rows="7" class="large-text"><?php echo esc_textarea( get_option( 'smlf_email_user_intro', $default_user_intro ) ); ?></textarea>
							</label>
						</section>
					</div>
					<label>
						<span><?php esc_html_e( 'Footer text', 'smart-multistep-lead-forms' ); ?></span>
						<textarea name="smlf_email_footer_text" rows="3" class="large-text"><?php echo esc_textarea( get_option( 'smlf_email_footer_text', $default_footer_text ) ); ?></textarea>
					</label>
					<p class="description"><?php esc_html_e( 'Available placeholders: {site_name}, {form_title}, {lead_id}, {page_url}, {customer_name}, {summary}', 'smart-multistep-lead-forms' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Enable Partial Lead Saving', 'smart-multistep-lead-forms' ); ?></th>
				<td>
					<input type="checkbox" name="smlf_enable_partial" value="1" <?php checked( 1, get_option( 'smlf_enable_partial', 1 ), true ); ?> />
					<p class="description"><?php esc_html_e( 'Save leads instantly when email/phone is typed.', 'smart-multistep-lead-forms' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Webhook URL', 'smart-multistep-lead-forms' ); ?></th>
				<td>
					<input type="url" name="smlf_webhook_url" value="<?php echo esc_attr( get_option('smlf_webhook_url', '') ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Send lead data to Zapier, Make, etc.', 'smart-multistep-lead-forms' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Anti-bot / Captcha Method', 'smart-multistep-lead-forms' ); ?></th>
				<td>
					<?php $captcha_method = get_option('smlf_captcha_method', 'custom'); ?>
					<select name="smlf_captcha_method">
						<option value="none" <?php selected($captcha_method, 'none'); ?>><?php esc_html_e('None', 'smart-multistep-lead-forms'); ?></option>
						<option value="custom" <?php selected($captcha_method, 'custom'); ?>><?php esc_html_e('Custom Checkbox (Default)', 'smart-multistep-lead-forms'); ?></option>
						<option value="recaptcha_v2" <?php selected($captcha_method, 'recaptcha_v2'); ?>><?php esc_html_e('Google reCAPTCHA v2', 'smart-multistep-lead-forms'); ?></option>
						<option value="recaptcha_v3" <?php selected($captcha_method, 'recaptcha_v3'); ?>><?php esc_html_e('Google reCAPTCHA v3', 'smart-multistep-lead-forms'); ?></option>
						<option value="turnstile" <?php selected($captcha_method, 'turnstile'); ?>><?php esc_html_e('Cloudflare Turnstile', 'smart-multistep-lead-forms'); ?></option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Captcha Site Key', 'smart-multistep-lead-forms' ); ?></th>
				<td>
					<input type="text" name="smlf_captcha_site_key" value="<?php echo esc_attr( get_option('smlf_captcha_site_key', '') ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Captcha Secret Key', 'smart-multistep-lead-forms' ); ?></th>
				<td>
					<input type="password" name="smlf_captcha_secret_key" value="<?php echo esc_attr( get_option('smlf_captcha_secret_key', '') ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Upload Limits', 'smart-multistep-lead-forms' ); ?></th>
				<td class="smlf-settings-inline-fields">
					<div class="smlf-extension-picker">
						<span><?php esc_html_e( 'Allowed file extensions', 'smart-multistep-lead-forms' ); ?></span>
						<?php
						$selected_extensions = array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', get_option( 'smlf_allowed_file_extensions', 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip' ) ) ) ) );
						$extension_groups    = array(
							'images'    => array( 'label' => __( 'Images', 'smart-multistep-lead-forms' ), 'items' => array( 'jpg', 'jpeg', 'png', 'gif', 'webp' ) ),
							'documents' => array( 'label' => __( 'Documents', 'smart-multistep-lead-forms' ), 'items' => array( 'pdf', 'doc', 'docx' ) ),
							'sheets'    => array( 'label' => __( 'Spreadsheets', 'smart-multistep-lead-forms' ), 'items' => array( 'xls', 'xlsx' ) ),
							'archives'  => array( 'label' => __( 'Archives', 'smart-multistep-lead-forms' ), 'items' => array( 'zip' ) ),
						);
						?>
						<div class="smlf-extension-groups">
							<?php foreach ( $extension_groups as $group ) : ?>
								<div class="smlf-extension-group">
									<strong><?php echo esc_html( $group['label'] ); ?></strong>
									<div class="smlf-extension-buttons">
										<?php foreach ( $group['items'] as $extension ) : ?>
											<label class="smlf-extension-button">
												<input type="checkbox" name="smlf_allowed_file_extensions[]" value="<?php echo esc_attr( $extension ); ?>" <?php checked( in_array( $extension, $selected_extensions, true ) ); ?>>
												<span><?php echo esc_html( strtoupper( $extension ) ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
					<label>
						<span><?php esc_html_e( 'Maximum file count', 'smart-multistep-lead-forms' ); ?></span>
						<input type="number" name="smlf_max_file_count" min="1" value="<?php echo esc_attr( get_option( 'smlf_max_file_count', 5 ) ); ?>" class="small-text" />
					</label>
					<label>
						<span><?php esc_html_e( 'Maximum file size (MB)', 'smart-multistep-lead-forms' ); ?></span>
						<input type="number" name="smlf_max_file_size_mb" min="1" value="<?php echo esc_attr( get_option( 'smlf_max_file_size_mb', 10 ) ); ?>" class="small-text" />
					</label>
					<p class="description"><?php esc_html_e( 'These limits apply to all file upload fields unless an older saved form already has its own limits.', 'smart-multistep-lead-forms' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Data on Uninstall', 'smart-multistep-lead-forms' ); ?></th>
				<td>
					<?php $uninstall_action = get_option( 'smlf_uninstall_data_action', 'keep' ); ?>
					<label>
						<input type="radio" name="smlf_uninstall_data_action" value="keep" <?php checked( $uninstall_action, 'keep' ); ?>>
						<?php esc_html_e( 'Keep forms, leads, email logs, and settings in the database.', 'smart-multistep-lead-forms' ); ?>
					</label>
					<br>
					<label>
						<input type="radio" name="smlf_uninstall_data_action" value="delete" <?php checked( $uninstall_action, 'delete' ); ?>>
						<?php esc_html_e( 'Delete all plugin data from the database when the plugin is uninstalled.', 'smart-multistep-lead-forms' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'This only runs when the plugin is deleted from WordPress, not when it is deactivated.', 'smart-multistep-lead-forms' ); ?></p>
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
