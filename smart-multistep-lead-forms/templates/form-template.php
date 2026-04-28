<?php
$settings = wp_parse_args(
	isset( $settings ) && is_array( $settings ) ? $settings : array(),
	array(
		'captcha_method'           => 'none',
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
		'form_language'            => 'auto',
	)
);

$allowed_captcha_methods = array( 'none', 'custom', 'recaptcha_v2', 'recaptcha_v3', 'turnstile' );
$captcha_method          = in_array( $settings['captcha_method'], $allowed_captcha_methods, true ) ? $settings['captcha_method'] : 'none';
$captcha_gate            = in_array( $settings['captcha_gate'], array( 'before_form', 'before_submit', 'on_step' ), true ) ? $settings['captcha_gate'] : 'before_form';
$captcha_step            = max( 1, absint( $settings['captcha_step'] ) );
$allowed_extensions      = array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', $settings['allowed_file_extensions'] ) ) ) );
$allowed_extensions_text = implode( ', ', $allowed_extensions );
$accept_extensions       = implode( ',', array_map( static function( $extension ) { return '.' . $extension; }, $allowed_extensions ) );
$max_file_count          = max( 1, absint( $settings['max_file_count'] ) );
$max_file_size_mb        = max( 1, absint( $settings['max_file_size_mb'] ) );
$theme                   = in_array( $settings['theme'], array( 'consult_pro', 'hvac_3d' ), true ) ? $settings['theme'] : 'consult_pro';
$theme_class             = 'hvac_3d' === $theme ? 'smlf-theme-hvac-3d' : 'smlf-theme-consult-pro';
$form_language           = in_array( $settings['form_language'], array( 'auto', 'en', 'de', 'fa' ), true ) ? $settings['form_language'] : 'auto';
$language_attr           = 'auto' === $form_language ? substr( determine_locale(), 0, 2 ) : $form_language;
$direction_attr          = in_array( $language_attr, array( 'ar', 'fa', 'he', 'ur' ), true ) ? 'rtl' : 'ltr';
$font_family             = sanitize_text_field( $settings['font_family'] );
$primary_color           = sanitize_hex_color( $settings['primary_color'] ) ?: '#0ea5e9';
$accent_color            = sanitize_hex_color( $settings['accent_color'] ) ?: '#14b8a6';
$background_color        = sanitize_hex_color( $settings['background_color'] ) ?: '#ffffff';
$text_color              = sanitize_hex_color( $settings['text_color'] ) ?: '#111827';
$style_vars              = sprintf(
	'--smlf-primary:%1$s;--smlf-accent:%2$s;--smlf-bg:%3$s;--smlf-text:%4$s;font-family:%5$s;',
	esc_attr( $primary_color ),
	esc_attr( $accent_color ),
	esc_attr( $background_color ),
	esc_attr( $text_color ),
	esc_attr( $font_family )
);
$site_key                = sanitize_text_field( get_option( 'smlf_captcha_site_key', '' ) );
$show_initial_gate       = 'none' !== $captcha_method && 'before_form' === $captcha_gate;
$steps                   = isset( $steps ) && is_array( $steps ) ? $steps : array();
?>

<div class="smlf-form-wrapper <?php echo esc_attr( $theme_class ); ?>" id="smlf-form-<?php echo esc_attr( $form_id ); ?>" style="<?php echo esc_attr( $style_vars ); ?>" lang="<?php echo esc_attr( $language_attr ); ?>" dir="<?php echo esc_attr( $direction_attr ); ?>" data-form-id="<?php echo esc_attr( $form_id ); ?>" data-captcha-method="<?php echo esc_attr( $captcha_method ); ?>" data-captcha-gate="<?php echo esc_attr( $captcha_gate ); ?>" data-captcha-step="<?php echo esc_attr( $captcha_step ); ?>" data-allowed-file-extensions="<?php echo esc_attr( implode( ',', $allowed_extensions ) ); ?>" data-max-file-count="<?php echo esc_attr( $max_file_count ); ?>" data-max-file-size-mb="<?php echo esc_attr( $max_file_size_mb ); ?>">

	<!-- Anti-bot Gate -->
	<?php if ( 'none' !== $captcha_method ) : ?>
	<div class="smlf-anti-bot-overlay smlf-anti-bot-gate" style="<?php echo $show_initial_gate ? 'display:flex;' : 'display:none;'; ?>">
		<div class="smlf-anti-bot-modal">
			<h3><?php esc_html_e( 'Security Check', 'smart-multistep-lead-forms' ); ?></h3>
			<p><?php esc_html_e( 'Please verify you are human.', 'smart-multistep-lead-forms' ); ?></p>

			<?php if ( 'custom' === $captcha_method ) : ?>
				<label>
					<input type="checkbox" id="smlf-bot-check-<?php echo esc_attr( $form_id ); ?>"> <?php esc_html_e( 'I am not a robot', 'smart-multistep-lead-forms' ); ?>
				</label>
			<?php elseif ( 'recaptcha_v2' === $captcha_method ) : ?>
				<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>" id="smlf-recaptcha-<?php echo esc_attr( $form_id ); ?>"></div>
			<?php elseif ( 'turnstile' === $captcha_method ) : ?>
				<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
			<?php endif; ?>

			<button type="button" class="smlf-btn-verify"><?php esc_html_e( 'Verify', 'smart-multistep-lead-forms' ); ?></button>
		</div>
	</div>
	<?php endif; ?>

	<!-- Progress Bar -->
	<div class="smlf-progress-bar-container" style="<?php echo $show_initial_gate ? 'display:none;' : 'display:block;'; ?>">
		<div class="smlf-progress-bar" style="width: 0%;"></div>
	</div>

	<!-- Form Steps -->
	<form class="smlf-form-actual" style="<?php echo $show_initial_gate ? 'display:none;' : 'display:block;'; ?>" enctype="multipart/form-data">
		<input type="text" name="smlf_website" value="" class="smlf-honeypot-field" tabindex="-1" autocomplete="off" aria-hidden="true">
		<?php if ( ! empty( $steps ) ) : ?>
			<?php foreach ( $steps as $index => $step ) : ?>
				<?php
				$step       = is_array( $step ) ? $step : array();
				$step_id    = isset( $step['step_id'] ) ? absint( $step['step_id'] ) : $index + 1;
				$step_title = isset( $step['title'] ) ? $step['title'] : '';
				$logic_rules = isset( $step['logic_rules'] ) && is_array( $step['logic_rules'] ) ? wp_json_encode( $step['logic_rules'] ) : '[]';
				?>
				<div class="smlf-form-step" data-step-id="<?php echo esc_attr( $step_id ); ?>" data-step-index="<?php echo esc_attr( $index ); ?>" data-terminal="<?php echo esc_attr( isset( $step['terminal'] ) ? $step['terminal'] : '' ); ?>" data-next-step="<?php echo esc_attr( isset( $step['next_step'] ) ? absint( $step['next_step'] ) : 0 ); ?>" data-logic-target="<?php echo esc_attr( isset( $step['logic_target'] ) ? $step['logic_target'] : '' ); ?>" data-logic-value="<?php echo esc_attr( isset( $step['logic_value'] ) ? $step['logic_value'] : '' ); ?>" data-logic-rules="<?php echo esc_attr( $logic_rules ); ?>" <?php echo $index > 0 ? 'style="display:none;"' : ''; ?>>
					<?php if ( ! empty( $step['title'] ) ) : ?>
						<h3 class="smlf-step-title"><?php echo esc_html( $step_title ); ?></h3>
					<?php endif; ?>

					<?php
					$fields = isset( $step['fields'] ) && is_array( $step['fields'] ) ? $step['fields'] : array();
					?>
					<?php foreach ( $fields as $field ) : ?>
						<?php
						$field      = is_array( $field ) ? $field : array();
						$field_type = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text';
						$field_label = isset( $field['label'] ) ? $field['label'] : '';
						$field_id   = isset( $field['field_id'] ) && $field['field_id'] ? sanitize_key( $field['field_id'] ) : sanitize_key( $index . '_' . sanitize_title( $field_label ) );
						$field_name = 'smlf_field_' . $field_id;
						$required   = ! empty( $field['required'] ) || 'email' === $field_type;
						$required_attr = $required ? ' required' : '';
						$field_width = isset( $field['field_width'] ) && in_array( $field['field_width'], array( 'full', 'half', 'third' ), true ) ? $field['field_width'] : 'full';
						$display_mode = isset( $field['display_mode'] ) && in_array( $field['display_mode'], array( 'default', 'cards', 'dropdown', 'list' ), true ) ? $field['display_mode'] : ( 'cards' === $field_type ? 'cards' : 'default' );
						$label_color = isset( $field['label_color'] ) ? sanitize_hex_color( $field['label_color'] ) : '';
						$input_background = isset( $field['input_background'] ) ? sanitize_hex_color( $field['input_background'] ) : '';
						$input_text_color = isset( $field['input_text_color'] ) ? sanitize_hex_color( $field['input_text_color'] ) : '';
						$field_style = '';
						if ( $label_color ) {
							$field_style .= '--smlf-field-label:' . esc_attr( $label_color ) . ';';
						}
						if ( $input_background ) {
							$field_style .= '--smlf-field-bg:' . esc_attr( $input_background ) . ';';
						}
						if ( $input_text_color ) {
							$field_style .= '--smlf-field-text:' . esc_attr( $input_text_color ) . ';';
						}
						?>
						<div class="smlf-field-row smlf-field-type-<?php echo esc_attr( $field_type ); ?> smlf-field-width-<?php echo esc_attr( $field_width ); ?>" style="<?php echo esc_attr( $field_style ); ?>" data-field-label="<?php echo esc_attr( $field_label ); ?>">
							<?php if ( 'message' !== $field_type ) : ?>
								<label>
									<?php echo esc_html( $field_label ); ?>
									<?php if ( $required ) : ?>
										<span class="smlf-required-star" aria-hidden="true">*</span>
									<?php endif; ?>
								</label>
							<?php endif; ?>

							<?php if ( $field_type === 'message' ) : ?>
								<div class="smlf-message-block"><?php echo esc_html( $field_label ); ?></div>

							<?php elseif ( $field_type === 'consent' ) : ?>
								<?php
								$consent_text    = ! empty( $field['consent_text'] ) ? $field['consent_text'] : $field_label;
								$link_text       = isset( $field['link_text'] ) ? trim( $field['link_text'] ) : '';
								$link_behavior   = isset( $field['link_behavior'] ) && in_array( $field['link_behavior'], array( 'new_tab', 'popup_page', 'popup_text' ), true ) ? $field['link_behavior'] : 'new_tab';
								$link_url        = isset( $field['link_url'] ) ? esc_url( $field['link_url'] ) : '';
								$link_page_id    = isset( $field['link_page_id'] ) ? absint( $field['link_page_id'] ) : 0;
								$popup_text      = isset( $field['popup_text'] ) ? $field['popup_text'] : '';
								$checked_default = ! empty( $field['checked_default'] );
								$modal_id        = 'smlf-consent-modal-' . absint( $form_id ) . '-' . esc_attr( $field_id );
								$link_attrs      = '';
								$modal_content   = '';

								if ( 'popup_page' === $link_behavior && $link_page_id ) {
									$page = get_post( $link_page_id );
									if ( $page && 'page' === $page->post_type && 'publish' === $page->post_status ) {
										$modal_content = apply_filters( 'the_content', $page->post_content );
									}
								} elseif ( 'popup_text' === $link_behavior ) {
									$modal_content = wpautop( wp_kses_post( $popup_text ) );
								}

								if ( 'new_tab' === $link_behavior ) {
									$link_attrs = ' href="' . esc_url( $link_url ? $link_url : '#' ) . '" target="_blank" rel="noopener noreferrer"';
								} else {
									$link_attrs = ' href="#" class="smlf-consent-popup-link" data-smlf-modal="' . esc_attr( $modal_id ) . '"';
								}

								$link_html = $link_text ? '<a' . $link_attrs . '>' . esc_html( $link_text ) . '</a>' : '';
								$text_html = esc_html( $consent_text );
								if ( $link_text && false !== strpos( $consent_text, $link_text ) ) {
									$escaped_link_text = esc_html( $link_text );
									$link_position     = strpos( $text_html, $escaped_link_text );
									if ( false !== $link_position ) {
										$text_html = substr_replace( $text_html, $link_html, $link_position, strlen( $escaped_link_text ) );
									}
								} elseif ( $link_html ) {
									$text_html .= ' ' . $link_html;
								}
								?>
								<label class="smlf-consent-option">
									<input type="checkbox" name="<?php echo esc_attr( $field_name ); ?>" value="1"<?php echo $required_attr; ?><?php checked( $checked_default ); ?>>
									<span><?php echo wp_kses_post( $text_html ); ?></span>
								</label>
								<?php if ( $modal_content && 'new_tab' !== $link_behavior ) : ?>
									<div class="smlf-consent-modal" id="<?php echo esc_attr( $modal_id ); ?>" aria-hidden="true">
										<div class="smlf-consent-modal-backdrop" data-smlf-close-modal></div>
										<div class="smlf-consent-modal-dialog" role="dialog" aria-modal="true">
											<button type="button" class="smlf-consent-modal-close" data-smlf-close-modal><?php esc_html_e( 'Close', 'smart-multistep-lead-forms' ); ?></button>
											<div class="smlf-consent-modal-content"><?php echo wp_kses_post( $modal_content ); ?></div>
										</div>
									</div>
								<?php endif; ?>

							<?php elseif ( $field_type === 'text' ) : ?>
								<input type="text" name="<?php echo esc_attr( $field_name ); ?>" class="smlf-input"<?php echo $required_attr; ?>>

							<?php elseif ( $field_type === 'email' ) : ?>
								<input type="email" name="<?php echo esc_attr( $field_name ); ?>" class="smlf-input smlf-critical-field"<?php echo $required_attr; ?>>

							<?php elseif ( $field_type === 'phone' ) : ?>
								<input type="tel" name="<?php echo esc_attr( $field_name ); ?>" class="smlf-input smlf-critical-field"<?php echo $required_attr; ?>>

							<?php elseif ( $field_type === 'textarea' ) : ?>
								<textarea name="<?php echo esc_attr( $field_name ); ?>" class="smlf-input smlf-textarea" rows="5"<?php echo $required_attr; ?>></textarea>

							<?php elseif ( $field_type === 'file' ) : ?>
								<label class="smlf-file-dropzone">
									<input type="file" name="smlf_files[]" class="smlf-file-input" accept="<?php echo esc_attr( $accept_extensions ); ?>" multiple>
									<span class="smlf-file-dropzone-title"><?php esc_html_e( 'Drag files here or click to upload', 'smart-multistep-lead-forms' ); ?></span>
									<span class="smlf-file-dropzone-note">
										<?php
										printf(
											/* translators: 1: extension list, 2: max file count, 3: max size in MB */
											esc_html__( 'Allowed: %1$s. Up to %2$d files, %3$dMB each.', 'smart-multistep-lead-forms' ),
											esc_html( $allowed_extensions_text ),
											esc_html( $max_file_count ),
											esc_html( $max_file_size_mb )
										);
										?>
									</span>
								</label>
								<div class="smlf-file-list" aria-live="polite"></div>

							<?php elseif ( $field_type === 'cards' || $field_type === 'radio' ) :
								$options = isset( $field['options'] ) && ! empty( $field['options'] ) ? array_filter( array_map( 'trim', explode( ',', $field['options'] ) ) ) : array( 'Option 1', 'Option 2' );
							?>
								<?php if ( 'dropdown' === $display_mode ) : ?>
									<select name="<?php echo esc_attr( $field_name ); ?>" class="smlf-input smlf-select"<?php echo $required_attr; ?>>
										<option value=""><?php esc_html_e( 'Please select', 'smart-multistep-lead-forms' ); ?></option>
										<?php foreach ( $options as $opt ) : ?>
											<option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
										<?php endforeach; ?>
									</select>
								<?php elseif ( 'list' === $display_mode || ( 'radio' === $field_type && 'cards' !== $display_mode ) ) : ?>
									<div class="smlf-radio-container">
										<?php foreach ( $options as $opt ) : ?>
										<label class="smlf-radio-option">
											<input type="radio" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $opt ); ?>"<?php echo $required_attr; ?>>
											<span><?php echo esc_html( $opt ); ?></span>
										</label>
										<?php endforeach; ?>
									</div>
								<?php else : ?>
								<div class="smlf-cards-container">
									<?php foreach ( $options as $opt ) : ?>
									<label class="smlf-card">
										<input type="radio" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $opt ); ?>"<?php echo $required_attr; ?>>
										<span class="smlf-card-content"><?php echo esc_html( $opt ); ?></span>
									</label>
									<?php endforeach; ?>
								</div>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>

					<div class="smlf-step-navigation">
						<?php if ( $index > 0 ) : ?>
							<button type="button" class="smlf-btn-prev"><?php esc_html_e( 'Back', 'smart-multistep-lead-forms' ); ?></button>
						<?php endif; ?>

						<?php if ( isset( $step['terminal'] ) && 'reset' === $step['terminal'] ) : ?>
							<button type="button" class="smlf-btn-reset"><?php esc_html_e( 'Start again', 'smart-multistep-lead-forms' ); ?></button>
						<?php elseif ( $index < count( $steps ) - 1 ) : ?>
							<button type="button" class="smlf-btn-next"><?php esc_html_e( 'Next', 'smart-multistep-lead-forms' ); ?></button>
						<?php else : ?>
							<button type="button" class="smlf-btn-submit"><?php esc_html_e( 'Submit', 'smart-multistep-lead-forms' ); ?></button>
						<?php endif; ?>
					</div>

				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<p><?php esc_html_e( 'This form has no steps yet.', 'smart-multistep-lead-forms' ); ?></p>
		<?php endif; ?>
	</form>

	<div class="smlf-success-message" style="display:none;">
		<h3><?php esc_html_e( 'Thank you!', 'smart-multistep-lead-forms' ); ?></h3>
		<p><?php esc_html_e( 'Your submission has been received.', 'smart-multistep-lead-forms' ); ?></p>
		<div class="smlf-success-summary" aria-live="polite"></div>
	</div>
</div>
