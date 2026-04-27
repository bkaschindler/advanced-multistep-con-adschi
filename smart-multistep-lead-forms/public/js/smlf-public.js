jQuery(document).ready(function($) {
	$('.smlf-form-wrapper').each(function() {
		const $wrapper = $(this);
		const formId = $wrapper.data('form-id');
		const $form = $wrapper.find('.smlf-form-actual');
		const $steps = $wrapper.find('.smlf-form-step');
		const totalSteps = $steps.length;
		let currentStep = 0;
		let leadId = null;
		let stepHistory = [];
		let customVerified = smlf_public_obj.captcha_method === 'none' ? '1' : '0';
		let verifiedCaptchaToken = '';

		if (smlf_public_obj.captcha_method === 'none') {
			revealForm();
		}

		$wrapper.find('.smlf-btn-verify').on('click', function(e) {
			e.preventDefault();

			getCaptchaToken(function(token, customState) {
				$.post(smlf_public_obj.ajax_url, {
					action: 'smlf_verify_bot',
					form_id: formId,
					token: token,
					custom_verified: customState
				}).done(function(response) {
					if (response.success) {
						verifiedCaptchaToken = response.data && response.data.captcha_token ? response.data.captcha_token : token;
						customVerified = customState || customVerified;
						revealForm();
						return;
					}

					showError(response);
				}).fail(function() {
					alert(smlf_public_obj.i18n.error);
				});
			});
		});

		function revealForm() {
			$wrapper.find('.smlf-anti-bot-overlay').fadeOut(300, function() {
				$wrapper.find('.smlf-progress-bar-container').fadeIn();
				$form.fadeIn();
				$wrapper.addClass('smlf-is-ready');
				updateProgress();
			});
		}

		function getCaptchaToken(callback) {
			if (smlf_public_obj.captcha_method === 'none') {
				callback('', '1');
				return;
			}

			if (verifiedCaptchaToken) {
				callback(verifiedCaptchaToken, customVerified);
				return;
			}

			if (smlf_public_obj.captcha_method === 'custom') {
				if (!$wrapper.find('#smlf-bot-check-' + formId).is(':checked')) {
					alert(smlf_public_obj.i18n.please_verify);
					return;
				}
				callback('', '1');
				return;
			}

			if (smlf_public_obj.captcha_method === 'recaptcha_v2') {
				const token = typeof grecaptcha !== 'undefined' ? grecaptcha.getResponse() : '';
				if (!token) {
					alert(smlf_public_obj.i18n.please_verify);
					return;
				}
				callback(token, '0');
				return;
			}

			if (smlf_public_obj.captcha_method === 'recaptcha_v3') {
				if (typeof grecaptcha === 'undefined') {
					alert(smlf_public_obj.i18n.error);
					return;
				}
				grecaptcha.ready(function() {
					grecaptcha.execute(smlf_public_obj.site_key, { action: 'submit' }).then(function(token) {
						callback(token, '0');
					});
				});
				return;
			}

			if (smlf_public_obj.captcha_method === 'turnstile') {
				const token = $wrapper.find('[name="cf-turnstile-response"]').val();
				if (!token) {
					alert(smlf_public_obj.i18n.please_verify);
					return;
				}
				callback(token, '0');
			}
		}

		function updateProgress() {
			if (totalSteps > 1) {
				$wrapper.find('.smlf-progress-bar').css('width', ((currentStep) / (totalSteps - 1)) * 100 + '%');
				return;
			}

			$wrapper.find('.smlf-progress-bar').css('width', '100%');
		}

		function showStep(index) {
			$steps.removeClass('smlf-step-active').hide();
			$steps.eq(index).addClass('smlf-step-active').fadeIn(220);
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

		function isTerminalStep(index) {
			return $steps.eq(index).data('terminal') === 'reset';
		}

		function hasValidContactForPartial() {
			let hasContact = false;
			$form.find('.smlf-critical-field').each(function() {
				const type = ($(this).attr('type') || '').toLowerCase();
				const value = $(this).val().trim();
				if (type === 'email' && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
					hasContact = true;
				}
				if (type === 'tel' && value.length >= 5) {
					hasContact = true;
				}
			});
			return hasContact;
		}

		function validateStep($step) {
			let valid = true;

			$step.find('[required]').each(function() {
				const $field = $(this);
				const type = ($field.attr('type') || '').toLowerCase();
				const name = $field.attr('name');

				if ((type === 'radio' || type === 'checkbox') && name) {
					if ($step.find('[name="' + name + '"]:checked').length === 0) {
						valid = false;
					}
					return;
				}

				if (!$field.val()) {
					valid = false;
					return;
				}

				if (type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($field.val())) {
					alert(smlf_public_obj.i18n.invalid_email);
					valid = false;
				}
			});

			if (!valid) {
				alert(smlf_public_obj.i18n.required);
			}

			return valid;
		}

		function validateAllSteps() {
			let valid = true;
			$steps.each(function() {
				if (!validateStep($(this))) {
					valid = false;
					return false;
				}
			});
			return valid;
		}

		$form.find('.smlf-critical-field').on('blur change', function() {
			const type = ($(this).attr('type') || '').toLowerCase();
			const value = $(this).val().trim();
			if (type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
				return;
			}

			if (value !== '') {
				savePartialLead();
			}
		});

		$form.find('.smlf-card input[type="radio"]').on('change', function() {
			if ($(this).is(':checked')) {
				if (currentStep < totalSteps - 1) {
					setTimeout(function() {
						const nextIdx = getNextStepIndex();
						if (nextIdx < totalSteps) {
							if (!isTerminalStep(nextIdx) && hasValidContactForPartial()) {
								savePartialLead();
							}
							stepHistory.push(currentStep);
							showStep(nextIdx);
						}
					}, 200);
				}
			}
		});

		$wrapper.find('.smlf-btn-next').on('click', function(e) {
			e.preventDefault();

			if (!validateStep($steps.eq(currentStep))) {
				return;
			}

			if (currentStep < totalSteps - 1) {
				const nextIdx = getNextStepIndex();
				if (nextIdx < totalSteps) {
					if (!isTerminalStep(nextIdx) && hasValidContactForPartial()) {
						savePartialLead();
					}
					stepHistory.push(currentStep);
					showStep(nextIdx);
				}
			}
		});

		$wrapper.find('.smlf-btn-prev').on('click', function(e) {
			e.preventDefault();
			if (stepHistory.length > 0) {
				showStep(stepHistory.pop());
			} else if (currentStep > 0) {
				showStep(currentStep - 1);
			}
		});

		function savePartialLead() {
			const requestData = new FormData();
			requestData.append('action', 'smlf_save_partial');
			requestData.append('form_id', formId);
			requestData.append('lead_id', leadId || '');
			appendSerializedData(requestData);

			$.ajax({
				url: smlf_public_obj.ajax_url,
				method: 'POST',
				data: requestData,
				processData: false,
				contentType: false
			}).done(function(response) {
				if (response.success && response.data && response.data.lead_id) {
					leadId = response.data.lead_id;
				}
			});
		}

		$wrapper.find('.smlf-file-input').on('change', function() {
			const files = Array.from(this.files || []);
			const $list = $(this).closest('.smlf-field-row').find('.smlf-file-list');
			$list.empty();

			files.forEach(function(file) {
				$list.append($('<span/>', {
					'class': 'smlf-file-pill',
					text: file.name
				}));
			});
		});

		$wrapper.find('.smlf-file-dropzone').on('dragover', function(e) {
			e.preventDefault();
			$(this).addClass('smlf-file-dropzone-active');
		}).on('dragleave', function() {
			$(this).removeClass('smlf-file-dropzone-active');
		}).on('drop', function(e) {
			e.preventDefault();
			$(this).removeClass('smlf-file-dropzone-active');
			const files = e.originalEvent && e.originalEvent.dataTransfer ? e.originalEvent.dataTransfer.files : null;
			const input = $(this).find('.smlf-file-input').get(0);
			if (files && input) {
				input.files = files;
				$(input).trigger('change');
			}
		});

		$wrapper.find('.smlf-btn-reset').on('click', function(e) {
			e.preventDefault();
			$form[0].reset();
			leadId = null;
			stepHistory = [];
			$wrapper.find('.smlf-file-list').empty();
			showStep(0);
		});

		$wrapper.find('.smlf-btn-submit').on('click', function(e) {
			e.preventDefault();

			if (!validateAllSteps()) {
				return;
			}

			const $btn = $(this);

			getCaptchaToken(function(token, customState) {
				$btn.prop('disabled', true).text(smlf_public_obj.i18n.submitting);

				const requestData = new FormData();
				requestData.append('action', 'smlf_submit_form');
				requestData.append('form_id', formId);
				requestData.append('lead_id', leadId || '');
				requestData.append('captcha_token', token || verifiedCaptchaToken);
				requestData.append('custom_verified', customState || customVerified);
				appendSerializedData(requestData);
				appendFiles(requestData);

				$.ajax({
					url: smlf_public_obj.ajax_url,
					method: 'POST',
					data: requestData,
					processData: false,
					contentType: false
				}).done(function(response) {
					if (response.success) {
						$wrapper.find('.smlf-progress-bar-container').hide();
						$form.hide();
						$wrapper.find('.smlf-success-message').fadeIn();
						return;
					}

					showError(response);
					$btn.prop('disabled', false).text(smlf_public_obj.i18n.submit);
				}).fail(function() {
					alert(smlf_public_obj.i18n.error);
					$btn.prop('disabled', false).text(smlf_public_obj.i18n.submit);
				});
			});
		});

		function showError(response) {
			alert((response && response.data && response.data.message) || smlf_public_obj.i18n.error);
		}

		function appendSerializedData(requestData) {
			$form.serializeArray().forEach(function(item, index) {
				requestData.append('data[' + index + '][name]', item.name);
				requestData.append('data[' + index + '][value]', item.value);
			});
		}

		function appendFiles(requestData) {
			$wrapper.find('.smlf-file-input').each(function() {
				Array.from(this.files || []).forEach(function(file) {
					requestData.append('smlf_files[]', file);
				});
			});
		}
	});
});
