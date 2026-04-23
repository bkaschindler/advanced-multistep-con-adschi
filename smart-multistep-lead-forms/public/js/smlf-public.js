jQuery(document).ready(function($) {

	$('.smlf-form-wrapper').each(function() {
		const $wrapper = $(this);
		const formId = $wrapper.data('form-id');
		const $form = $wrapper.find('.smlf-form-actual');
		const $steps = $wrapper.find('.smlf-form-step');
		const totalSteps = $steps.length;
		let currentStep = 0;
		let leadId = null;

		// Initialize if no bot check needed
		if (smlf_public_obj.captcha_method === 'none') {
			$wrapper.find('.smlf-anti-bot-overlay').hide();
			$wrapper.find('.smlf-progress-bar-container').show();
			$form.show();
			updateProgress();
		}

		// Anti-bot verify
		$wrapper.find('.smlf-btn-verify').on('click', function(e) {
			e.preventDefault();

			let captchaToken = '';

			if (smlf_public_obj.captcha_method === 'custom') {
				const isChecked = $wrapper.find('#smlf-bot-check-' + formId).is(':checked');
				if (!isChecked) {
					alert('Please check the box.');
					return;
				}
			} else if (smlf_public_obj.captcha_method === 'recaptcha_v2') {
				captchaToken = grecaptcha.getResponse();
				if (!captchaToken) {
					alert('Please complete the reCAPTCHA.');
					return;
				}
			} else if (smlf_public_obj.captcha_method === 'recaptcha_v3') {
				grecaptcha.ready(function() {
					grecaptcha.execute(smlf_public_obj.site_key, {action: 'submit'}).then(function(token) {
						verifyBotToken(token);
					});
				});
				return; // Wait for v3 promise
			} else if (smlf_public_obj.captcha_method === 'turnstile') {
				captchaToken = $wrapper.find('[name="cf-turnstile-response"]').val();
				if (!captchaToken) {
					alert('Please complete the verification.');
					return;
				}
			}

			verifyBotToken(captchaToken);
		});

		function verifyBotToken(token) {
			$.post(smlf_public_obj.ajax_url, {
				action: 'smlf_verify_bot',
				form_id: formId,
				token: token
			}, function(response) {
				if (response.success) {
					$wrapper.find('.smlf-anti-bot-overlay').fadeOut(300, function() {
						$wrapper.find('.smlf-progress-bar-container').fadeIn();
						$form.fadeIn();
						updateProgress();
					});
				} else {
					alert('Verification failed. Please try again.');
				}
			});
		}

		function updateProgress() {
			if (totalSteps > 1) {
				const percentage = ((currentStep) / (totalSteps - 1)) * 100;
				$wrapper.find('.smlf-progress-bar').css('width', percentage + '%');
			} else {
				$wrapper.find('.smlf-progress-bar').css('width', '100%');
			}
		}

		let stepHistory = [];

		function showStep(index) {
			$steps.hide();
			$steps.eq(index).fadeIn(300);
			currentStep = index;
			updateProgress();
		}

		function getNextStepIndex() {
			const $currentStepEl = $steps.eq(currentStep);
			const logicTarget = $currentStepEl.data('logic-target');
			const logicValue = $currentStepEl.data('logic-value');

			if (logicTarget && logicValue) {
				let matched = false;
				$currentStepEl.find('input[type="radio"]:checked').each(function() {
					if ($(this).val() === logicValue) {
						matched = true;
					}
				});

				if (matched) {
					let targetIndex = -1;
					$steps.each(function(idx) {
						if ($(this).data('step-id') == logicTarget) {
							targetIndex = idx;
						}
					});
					if (targetIndex !== -1) {
						return targetIndex;
					}
				}
			}

			return currentStep + 1;
		}

		// Auto-save on blur/change of critical fields
		$form.find('.smlf-critical-field').on('blur change', function() {
			if ($(this).val().trim() !== '') {
				savePartialLead();
			}
		});

		// Instant advance on card click
		$form.find('.smlf-card input[type="radio"]').on('change', function() {
			if ($(this).is(':checked')) {
				savePartialLead();
				if (currentStep < totalSteps - 1) {
					setTimeout(function() {
						const nextIdx = getNextStepIndex();
						if (nextIdx < totalSteps) {
							stepHistory.push(currentStep);
							showStep(nextIdx);
						}
					}, 200);
				}
			}
		});

		$wrapper.find('.smlf-btn-next').on('click', function(e) {
			e.preventDefault();
			// Basic validation hook can go here
			savePartialLead();
			if (currentStep < totalSteps - 1) {
				const nextIdx = getNextStepIndex();
				if (nextIdx < totalSteps) {
					stepHistory.push(currentStep);
					showStep(nextIdx);
				}
			}
		});

		$wrapper.find('.smlf-btn-prev').on('click', function(e) {
			e.preventDefault();
			if (stepHistory.length > 0) {
				const prevIdx = stepHistory.pop();
				showStep(prevIdx);
			} else if (currentStep > 0) {
				showStep(currentStep - 1);
			}
		});

		function savePartialLead() {
			const formData = $form.serializeArray();
			$.post(smlf_public_obj.ajax_url, {
				action: 'smlf_save_partial',
				nonce: smlf_public_obj.nonce,
				form_id: formId,
				lead_id: leadId,
				data: formData
			}, function(response) {
				if (response.success && response.data.lead_id) {
					leadId = response.data.lead_id;
				}
			});
		}

		// Final Submit
		$wrapper.find('.smlf-btn-submit').on('click', function(e) {
			e.preventDefault();

			const $btn = $(this);
			$btn.prop('disabled', true).text('Submitting...');

			const formData = $form.serializeArray();

			$.post(smlf_public_obj.ajax_url, {
				action: 'smlf_submit_form',
				nonce: smlf_public_obj.nonce,
				form_id: formId,
				lead_id: leadId,
				data: formData
			}, function(response) {
				if (response.success) {
					$wrapper.find('.smlf-progress-bar-container').hide();
					$form.hide();
					$wrapper.find('.smlf-success-message').fadeIn();
				} else {
					alert('Error submitting form.');
					$btn.prop('disabled', false).text('Submit');
				}
			});
		});

	});

});
