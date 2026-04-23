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
		</table>
		<?php submit_button(); ?>
	</form>
</div>
