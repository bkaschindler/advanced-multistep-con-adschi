<div class="smlf-form-wrapper smlf-theme-consult-pro" id="smlf-form-<?php echo esc_attr( $form_id ); ?>" data-form-id="<?php echo esc_attr( $form_id ); ?>">

	<?php
	$allowed_captcha_methods = array( 'none', 'custom', 'recaptcha_v2', 'recaptcha_v3', 'turnstile' );
	$captcha_method          = sanitize_key( get_option( 'smlf_captcha_method', 'custom' ) );
	$captcha_method          = in_array( $captcha_method, $allowed_captcha_methods, true ) ? $captcha_method : 'custom';
	$site_key                = sanitize_text_field( get_option( 'smlf_captcha_site_key', '' ) );
	?>
	<!-- Anti-bot Overlay -->
	<?php if ($captcha_method !== 'none') : ?>
	<div class="smlf-anti-bot-overlay">
		<div class="smlf-anti-bot-modal">
			<h3><?php esc_html_e( 'Security Check', 'smart-multistep-lead-forms' ); ?></h3>
			<p><?php esc_html_e( 'Please verify you are human.', 'smart-multistep-lead-forms' ); ?></p>

			<?php if ($captcha_method === 'custom') : ?>
				<label>
					<input type="checkbox" id="smlf-bot-check-<?php echo esc_attr( $form_id ); ?>"> <?php esc_html_e( 'I am not a robot', 'smart-multistep-lead-forms' ); ?>
				</label>
			<?php elseif ($captcha_method === 'recaptcha_v2') : ?>
				<div class="g-recaptcha" data-sitekey="<?php echo esc_attr($site_key); ?>" id="smlf-recaptcha-<?php echo esc_attr( $form_id ); ?>"></div>
			<?php elseif ($captcha_method === 'turnstile') : ?>
				<div class="cf-turnstile" data-sitekey="<?php echo esc_attr($site_key); ?>"></div>
			<?php endif; ?>

			<button type="button" class="smlf-btn-verify"><?php esc_html_e( 'Verify', 'smart-multistep-lead-forms' ); ?></button>
		</div>
	</div>
	<?php endif; ?>

	<!-- Progress Bar -->
	<div class="smlf-progress-bar-container" style="display:none;">
		<div class="smlf-progress-bar" style="width: 0%;"></div>
	</div>

	<!-- Form Steps -->
	<form class="smlf-form-actual" style="display:none;" enctype="multipart/form-data">
		<?php if ( ! empty( $steps ) ) : ?>
			<?php foreach ( $steps as $index => $step ) : ?>
				<div class="smlf-form-step" data-step-id="<?php echo esc_attr( $step['step_id'] ); ?>" data-step-index="<?php echo esc_attr( $index ); ?>" data-terminal="<?php echo esc_attr( isset( $step['terminal'] ) ? $step['terminal'] : '' ); ?>" data-logic-target="<?php echo esc_attr( isset($step['logic_target']) ? $step['logic_target'] : '' ); ?>" data-logic-value="<?php echo esc_attr( isset($step['logic_value']) ? $step['logic_value'] : '' ); ?>" <?php if($index > 0) echo 'style="display:none;"'; ?>>
					<?php if ( ! empty( $step['title'] ) ) : ?>
						<h3 class="smlf-step-title"><?php echo esc_html( $step['title'] ); ?></h3>
					<?php endif; ?>

					<?php
					$fields = isset( $step['fields'] ) && is_array( $step['fields'] ) ? $step['fields'] : array();
					?>
					<?php foreach ( $fields as $field ) : ?>
						<?php
						$field_type = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text';
						$field_label = isset( $field['label'] ) ? $field['label'] : '';
						$field_id   = isset( $field['field_id'] ) && $field['field_id'] ? sanitize_key( $field['field_id'] ) : sanitize_key( $index . '_' . sanitize_title( $field_label ) );
						$field_name = 'smlf_field_' . $field_id;
						$required   = ! empty( $field['required'] ) || 'email' === $field_type;
						?>
						<div class="smlf-field-row smlf-field-type-<?php echo esc_attr( $field_type ); ?>">
							<?php if ( 'message' !== $field_type ) : ?>
								<label><?php echo esc_html( $field_label ); ?></label>
							<?php endif; ?>

							<?php if ( $field_type === 'message' ) : ?>
								<div class="smlf-message-block"><?php echo esc_html( $field_label ); ?></div>

							<?php elseif ( $field_type === 'text' ) : ?>
								<input type="text" name="<?php echo esc_attr( $field_name ); ?>" class="smlf-input" <?php required( $required ); ?>>

							<?php elseif ( $field_type === 'email' ) : ?>
								<input type="email" name="<?php echo esc_attr( $field_name ); ?>" class="smlf-input smlf-critical-field" <?php required( $required ); ?>>

							<?php elseif ( $field_type === 'phone' ) : ?>
								<input type="tel" name="<?php echo esc_attr( $field_name ); ?>" class="smlf-input smlf-critical-field" <?php required( $required ); ?>>

							<?php elseif ( $field_type === 'textarea' ) : ?>
								<textarea name="<?php echo esc_attr( $field_name ); ?>" class="smlf-input smlf-textarea" rows="5" <?php required( $required ); ?>></textarea>

							<?php elseif ( $field_type === 'file' ) : ?>
								<label class="smlf-file-dropzone">
									<input type="file" name="smlf_files[]" class="smlf-file-input" multiple>
									<span class="smlf-file-dropzone-title"><?php esc_html_e( 'Drag files here or click to upload', 'smart-multistep-lead-forms' ); ?></span>
									<span class="smlf-file-dropzone-note"><?php esc_html_e( 'PDF, images, documents and ZIP files up to 10MB each.', 'smart-multistep-lead-forms' ); ?></span>
								</label>
								<div class="smlf-file-list" aria-live="polite"></div>

							<?php elseif ( $field_type === 'cards' ) :
								$options = isset($field['options']) && !empty($field['options']) ? array_map('trim', explode(',', $field['options'])) : array('Option 1', 'Option 2');
							?>
								<div class="smlf-cards-container">
									<?php foreach ($options as $opt) : ?>
									<label class="smlf-card">
										<input type="radio" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr($opt); ?>" <?php required( $required ); ?>>
										<span class="smlf-card-content"><?php echo esc_html($opt); ?></span>
									</label>
									<?php endforeach; ?>
								</div>
							<?php elseif ( $field_type === 'radio' ) :
								$options = isset($field['options']) && !empty($field['options']) ? array_map('trim', explode(',', $field['options'])) : array('Option 1', 'Option 2');
							?>
								<div class="smlf-radio-container">
									<?php foreach ($options as $opt) : ?>
									<label style="display:block; margin-bottom:5px;">
										<input type="radio" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr($opt); ?>" <?php required( $required ); ?>>
										<?php echo esc_html($opt); ?>
									</label>
									<?php endforeach; ?>
								</div>
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
	</div>
</div>
