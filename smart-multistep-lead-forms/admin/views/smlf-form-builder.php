<?php
global $wpdb;

$builder_i18n       = $this->get_builder_i18n();
$form_id            = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
$existing_form_data = array(
	'id'    => 0,
	'title' => __( 'New Form', 'smart-multistep-lead-forms' ),
	'settings' => array(
		'captcha_method'           => 'inherit',
		'captcha_gate'             => 'before_form',
		'captcha_step'             => 1,
		'allowed_file_extensions'  => 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip',
		'max_file_count'           => 5,
		'max_file_size_mb'         => 10,
		'theme'                    => 'consult_pro',
		'font_family'              => 'inherit',
		'primary_color'            => '#0ea5e9',
		'accent_color'             => '#14b8a6',
		'background_color'         => '#ffffff',
		'text_color'               => '#111827',
	),
	'steps' => array(),
);

if ( $form_id ) {
	$table_name = $wpdb->prefix . 'smlf_forms';
	$form       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $form_id ) );

	if ( $form ) {
		$decoded = json_decode( $form->form_data, true );
		$decoded = is_array( $decoded ) ? $decoded : array();

		$existing_form_data = array(
			'id'       => absint( $form->id ),
			'title'    => sanitize_text_field( $form->title ),
			'settings' => isset( $decoded['settings'] ) && is_array( $decoded['settings'] ) ? $decoded['settings'] : $existing_form_data['settings'],
			'steps'    => isset( $decoded['steps'] ) && is_array( $decoded['steps'] ) ? $decoded['steps'] : array(),
		);
	}
}
?>
<div class="wrap smlf-builder-wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html( $builder_i18n['builder_title'] ); ?></h1>
	<hr class="wp-header-end">

	<div id="smlf-builder-app">
		<div class="smlf-builder-sidebar">
			<h3><?php echo esc_html( $builder_i18n['blocks'] ); ?></h3>
			<ul class="smlf-draggable-blocks">
				<li data-type="text"><?php echo esc_html( $builder_i18n['text_input'] ); ?></li>
				<li data-type="email"><?php echo esc_html( $builder_i18n['email_input'] ); ?></li>
				<li data-type="phone"><?php echo esc_html( $builder_i18n['phone_input'] ); ?></li>
				<li data-type="textarea"><?php echo esc_html( $builder_i18n['long_text'] ); ?></li>
				<li data-type="file"><?php echo esc_html( $builder_i18n['file_upload'] ); ?></li>
				<li data-type="message"><?php echo esc_html( $builder_i18n['message_text'] ); ?></li>
				<li data-type="consent"><?php echo esc_html( $builder_i18n['consent_checkbox'] ); ?></li>
				<li data-type="cards"><?php echo esc_html( $builder_i18n['clickable_cards'] ); ?></li>
				<li data-type="radio"><?php echo esc_html( $builder_i18n['radio_buttons'] ); ?></li>
			</ul>
			<button class="button button-primary" id="smlf-add-step"><?php echo esc_html( $builder_i18n['add_step'] ); ?></button>
			<button class="button" id="smlf-load-template" type="button"><?php echo esc_html( $builder_i18n['load_template'] ); ?></button>
			<button class="button" id="smlf-load-hvac-template" type="button"><?php echo esc_html( $builder_i18n['load_hvac_template'] ); ?></button>
		</div>

		<div class="smlf-builder-canvas">
			<div class="smlf-form-settings">
				<input type="text" id="smlf-form-title" placeholder="<?php echo esc_attr( $builder_i18n['form_title'] ); ?>" value="<?php echo esc_attr( $existing_form_data['title'] ); ?>" />
				<button class="button button-primary" id="smlf-save-form"><?php echo esc_html( $builder_i18n['save_form'] ); ?></button>
			</div>
			<div class="smlf-form-advanced-settings">
				<div class="smlf-builder-settings-section">
					<strong><?php echo esc_html( $builder_i18n['appearance'] ); ?></strong>
				</div>
				<label>
					<span><?php echo esc_html( $builder_i18n['theme'] ); ?></span>
					<select id="smlf-theme">
						<option value="consult_pro"><?php echo esc_html( $builder_i18n['theme_consult'] ); ?></option>
						<option value="hvac_3d"><?php echo esc_html( $builder_i18n['theme_hvac'] ); ?></option>
					</select>
				</label>
				<label>
					<span><?php echo esc_html( $builder_i18n['font_family'] ); ?></span>
					<input type="text" id="smlf-font-family" placeholder="Inter, Arial, sans-serif">
				</label>
				<label>
					<span><?php echo esc_html( $builder_i18n['primary_color'] ); ?></span>
					<input type="color" id="smlf-primary-color" value="#0ea5e9">
				</label>
				<label>
					<span><?php echo esc_html( $builder_i18n['accent_color'] ); ?></span>
					<input type="color" id="smlf-accent-color" value="#14b8a6">
				</label>
				<label>
					<span><?php echo esc_html( $builder_i18n['background_color'] ); ?></span>
					<input type="color" id="smlf-background-color" value="#ffffff">
				</label>
				<label>
					<span><?php echo esc_html( $builder_i18n['text_color'] ); ?></span>
					<input type="color" id="smlf-text-color" value="#111827">
				</label>
				<div class="smlf-builder-settings-section">
					<strong><?php echo esc_html( $builder_i18n['captcha_method'] ); ?></strong>
				</div>
				<label>
					<span><?php echo esc_html( $builder_i18n['captcha_method'] ); ?></span>
					<select id="smlf-captcha-method">
						<option value="inherit"><?php echo esc_html( $builder_i18n['captcha_inherit'] ); ?></option>
						<option value="none"><?php echo esc_html( $builder_i18n['captcha_none'] ); ?></option>
						<option value="custom"><?php echo esc_html( $builder_i18n['captcha_custom'] ); ?></option>
						<option value="recaptcha_v2"><?php echo esc_html( $builder_i18n['captcha_recaptcha_v2'] ); ?></option>
						<option value="recaptcha_v3"><?php echo esc_html( $builder_i18n['captcha_recaptcha_v3'] ); ?></option>
						<option value="turnstile"><?php echo esc_html( $builder_i18n['captcha_turnstile'] ); ?></option>
					</select>
				</label>
				<label>
					<span><?php echo esc_html( $builder_i18n['captcha_gate'] ); ?></span>
					<select id="smlf-captcha-gate">
						<option value="before_form"><?php echo esc_html( $builder_i18n['captcha_before_form'] ); ?></option>
						<option value="before_submit"><?php echo esc_html( $builder_i18n['captcha_before_submit'] ); ?></option>
						<option value="on_step"><?php echo esc_html( $builder_i18n['captcha_on_step'] ); ?></option>
					</select>
				</label>
				<label>
					<span><?php echo esc_html( $builder_i18n['captcha_step'] ); ?></span>
					<input type="number" id="smlf-captcha-step" min="1" value="1">
				</label>
				<div class="smlf-builder-settings-section">
					<strong><?php echo esc_html( $builder_i18n['upload_limits'] ); ?></strong>
				</div>
				<label>
					<span><?php echo esc_html( $builder_i18n['allowed_file_extensions'] ); ?></span>
					<input type="text" id="smlf-allowed-file-extensions" placeholder="jpg,png,pdf,zip">
				</label>
				<label>
					<span><?php echo esc_html( $builder_i18n['max_file_count'] ); ?></span>
					<input type="number" id="smlf-max-file-count" min="1" value="5">
				</label>
				<label>
					<span><?php echo esc_html( $builder_i18n['max_file_size_mb'] ); ?></span>
					<input type="number" id="smlf-max-file-size-mb" min="1" value="10">
				</label>
			</div>

			<div id="smlf-steps-container">
				<!-- Steps will be rendered here via JS -->
			</div>

			<div class="smlf-live-preview-panel">
				<div class="smlf-live-preview-header">
					<h2><?php echo esc_html( $builder_i18n['preview_title'] ); ?></h2>
					<p><?php echo esc_html( $builder_i18n['preview_note'] ); ?></p>
				</div>
				<div id="smlf-builder-preview" class="smlf-builder-preview"></div>
			</div>
		</div>
	</div>
</div>
<script>
	window.smlf_existing_form_data = <?php echo wp_json_encode( $existing_form_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
</script>
