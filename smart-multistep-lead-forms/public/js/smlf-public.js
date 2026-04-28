jQuery(document).ready(function($) {
	$('.smlf-form-wrapper').each(function() {
		const $wrapper = $(this);
		const formId = $wrapper.data('form-id');
		const $form = $wrapper.find('.smlf-form-actual');
		const $steps = $wrapper.find('.smlf-form-step');
		const totalSteps = $steps.length;
		const captchaMethod = $wrapper.data('captcha-method') || smlf_public_obj.captcha_method || 'custom';
		const captchaGate = $wrapper.data('captcha-gate') || 'before_form';
		const captchaStep = parseInt($wrapper.data('captcha-step') || '1', 10);
		const allowedFileExtensions = String($wrapper.data('allowed-file-extensions') || '').split(',').map(function(extension) {
			return extension.trim().toLowerCase().replace(/^\./, '');
		}).filter(Boolean);
		const maxFileCount = parseInt($wrapper.data('max-file-count') || '5', 10);
		const maxFileSizeMb = parseInt($wrapper.data('max-file-size-mb') || '10', 10);
		const maxFileSizeBytes = maxFileSizeMb * 1024 * 1024;
		let currentStep = 0;
		let leadId = null;
		let stepHistory = [];
		let customVerified = captchaMethod === 'none' ? '1' : '0';
		let verifiedCaptchaToken = '';
		let pendingStepIndex = null;
		let pendingSubmitButton = null;
		const startedAt = Date.now();

		if (captchaMethod === 'none' || captchaGate !== 'before_form') {
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
						if (pendingSubmitButton) {
							$wrapper.find('.smlf-anti-bot-overlay').hide();
							const $button = pendingSubmitButton;
							pendingSubmitButton = null;
							submitForm($button);
						} else if (pendingStepIndex !== null) {
							$wrapper.find('.smlf-anti-bot-overlay').hide();
							$wrapper.find('.smlf-progress-bar-container').show();
							$form.show();
							showStep(pendingStepIndex);
							pendingStepIndex = null;
						} else {
							revealForm();
						}
						return;
					}

					showError(response);
				}).fail(function() {
					alert(smlf_public_obj.i18n.error);
				});
			});
		});

		function revealForm() {
			$wrapper.find('.smlf-anti-bot-overlay').hide();
			$wrapper.find('.smlf-progress-bar-container').fadeIn();
			$form.fadeIn();
			$wrapper.addClass('smlf-is-ready');
			updateProgress();
		}

		function getCaptchaToken(callback) {
			if (captchaMethod === 'none') {
				callback('', '1');
				return;
			}

			if (verifiedCaptchaToken) {
				callback(verifiedCaptchaToken, customVerified);
				return;
			}

			if (captchaMethod === 'custom') {
				if (!$wrapper.find('#smlf-bot-check-' + formId).is(':checked')) {
					alert(smlf_public_obj.i18n.please_verify);
					return;
				}
				callback('', '1');
				return;
			}

			if (captchaMethod === 'recaptcha_v2') {
				const token = typeof grecaptcha !== 'undefined' ? grecaptcha.getResponse() : '';
				if (!token) {
					alert(smlf_public_obj.i18n.please_verify);
					return;
				}
				callback(token, '0');
				return;
			}

			if (captchaMethod === 'recaptcha_v3') {
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

			if (captchaMethod === 'turnstile') {
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
			$steps.eq(index).addClass('smlf-step-active').css('display', 'flex').hide().fadeIn(220);
			currentStep = index;
			updateProgress();
		}

		function needsCaptchaBeforeStep(index) {
			return captchaMethod !== 'none' && !verifiedCaptchaToken && captchaGate === 'on_step' && index === captchaStep - 1;
		}

		function requestCaptchaForStep(index) {
			pendingStepIndex = index;
			$form.hide();
			$wrapper.find('.smlf-progress-bar-container').hide();
			$wrapper.find('.smlf-anti-bot-overlay').fadeIn(180);
		}

		function getNextStepIndex() {
			const $currentStepEl = $steps.eq(currentStep);
			const logicTarget = $currentStepEl.data('logic-target');
			const logicValue = $currentStepEl.data('logic-value');
			const stepValues = getCurrentStepChoiceValues($currentStepEl);
			const logicRules = normalizeLogicRules($currentStepEl.data('logic-rules'));

			if (logicRules.length && stepValues.length) {
				for (let i = 0; i < logicRules.length; i++) {
					if (stepValues.indexOf(logicRules[i].value) !== -1) {
						const ruleTargetIndex = findStepIndex(logicRules[i].target);
						if (ruleTargetIndex !== -1) {
							return ruleTargetIndex;
						}
					}
				}
			}

			if (logicTarget && logicValue) {
				if (stepValues.indexOf(logicValue) !== -1) {
					let targetIndex = findStepIndex(logicTarget);
					if (targetIndex !== -1) {
						return targetIndex;
					}
				}
			}

			const nextStep = parseInt($currentStepEl.data('next-step') || '0', 10);
			if (nextStep) {
				const nextStepIndex = findStepIndex(nextStep);
				if (nextStepIndex !== -1) {
					return nextStepIndex;
				}
			}

			return currentStep + 1;
		}

		function getCurrentStepChoiceValues($step) {
			const values = [];

			$step.find('input[type="radio"]:checked').each(function() {
				if ($(this).val()) {
					values.push($(this).val());
				}
			});

			$step.find('select').each(function() {
				if ($(this).val()) {
					values.push($(this).val());
				}
			});

			return values;
		}

		function normalizeLogicRules(rules) {
			if (typeof rules === 'string') {
				try {
					rules = JSON.parse(rules);
				} catch (e) {
					rules = [];
				}
			}

			if (!Array.isArray(rules)) {
				return [];
			}

			return rules.map(function(rule) {
				return {
					target: parseInt(rule.target || 0, 10),
					value: String(rule.value || '')
				};
			}).filter(function(rule) {
				return rule.target && rule.value;
			});
		}

		function findStepIndex(stepId) {
			let targetIndex = -1;
			$steps.each(function(idx) {
				if ($(this).data('step-id') == stepId) {
					targetIndex = idx;
				}
			});

			return targetIndex;
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

		function validateStep($step, options) {
			options = options || {};
			let valid = true;
			clearStepErrors($step);

			$step.find('[required]').each(function() {
				const $field = $(this);
				const type = ($field.attr('type') || '').toLowerCase();
				const name = $field.attr('name');

				if ((type === 'radio' || type === 'checkbox') && name) {
					if ($step.find('[name="' + name + '"]:checked').length === 0) {
						if (!options.silent) {
							showFieldError($field, smlf_public_obj.i18n.required);
						}
						valid = false;
					}
					return;
				}

				if (!$field.val()) {
					if (!options.silent) {
						showFieldError($field, smlf_public_obj.i18n.required);
					}
					valid = false;
					return;
				}

				if (type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($field.val())) {
					if (!options.silent) {
						showFieldError($field, smlf_public_obj.i18n.invalid_email);
					}
					valid = false;
				}
			});

			if (!valid && !options.silent) {
				const $firstInvalid = $step.find('.smlf-field-invalid').first();
				if ($firstInvalid.length) {
					$firstInvalid.get(0).scrollIntoView({ behavior: 'smooth', block: 'center' });
				}
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
						if (!validateStep($steps.eq(currentStep), { silent: true })) {
							return;
						}

						const nextIdx = getNextStepIndex();
						if (nextIdx < totalSteps) {
							if (!isTerminalStep(nextIdx) && hasValidContactForPartial()) {
								savePartialLead();
							}
							if (needsCaptchaBeforeStep(nextIdx)) {
								requestCaptchaForStep(nextIdx);
								return;
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
					if (needsCaptchaBeforeStep(nextIdx)) {
						requestCaptchaForStep(nextIdx);
						return;
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
			requestData.append('page_url', window.location.href);
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

			const validationError = validateFiles(files);
			if (validationError) {
				showFieldError($(this), validationError);
				$(this).val('');
				$(this).data('smlf-files', []);
				renderFileList($(this));
				return;
			}

			$(this).data('smlf-files', files);
			renderFileList($(this));
		});

		$wrapper.on('click', '.smlf-file-remove', function(e) {
			e.preventDefault();
			const $input = $(this).closest('.smlf-field-row').find('.smlf-file-input');
			const index = parseInt($(this).data('file-index'), 10);
			const files = ($input.data('smlf-files') || []).filter(function(file, fileIndex) {
				return fileIndex !== index;
			});
			$input.data('smlf-files', files);
			syncFileInput($input, files);
			renderFileList($input);
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

		$wrapper.find('.smlf-consent-popup-link').on('click', function(e) {
			e.preventDefault();
			const modalId = $(this).data('smlf-modal');
			if (modalId) {
				$wrapper.find('#' + modalId).addClass('smlf-consent-modal-open').attr('aria-hidden', 'false');
			}
		});

		$wrapper.find('[data-smlf-close-modal]').on('click', function(e) {
			e.preventDefault();
			$(this).closest('.smlf-consent-modal').removeClass('smlf-consent-modal-open').attr('aria-hidden', 'true');
		});

		$wrapper.find('.smlf-btn-reset').on('click', function(e) {
			e.preventDefault();
			$form[0].reset();
			leadId = null;
			stepHistory = [];
			$wrapper.find('.smlf-file-input').data('smlf-files', []);
			$wrapper.find('.smlf-file-list').empty();
			showStep(0);
		});

		$wrapper.find('.smlf-btn-submit').on('click', function(e) {
			e.preventDefault();

			if (!validateAllSteps()) {
				return;
			}

			const $btn = $(this);

			if (captchaMethod !== 'none' && captchaGate === 'before_submit' && !verifiedCaptchaToken) {
				pendingStepIndex = null;
				pendingSubmitButton = $btn;
				$form.hide();
				$wrapper.find('.smlf-progress-bar-container').hide();
				$wrapper.find('.smlf-anti-bot-overlay').fadeIn(180);
				return;
			}

			submitForm($btn);
		});

		function submitForm($btn) {
			getCaptchaToken(function(token, customState) {
				$btn.prop('disabled', true).text(smlf_public_obj.i18n.submitting);

				const requestData = new FormData();
				requestData.append('action', 'smlf_submit_form');
				requestData.append('form_id', formId);
				requestData.append('lead_id', leadId || '');
				requestData.append('captcha_token', token || verifiedCaptchaToken);
				requestData.append('custom_verified', customState || customVerified);
				requestData.append('page_url', window.location.href);
				requestData.append('smlf_elapsed', Math.max(0, Math.round((Date.now() - startedAt) / 1000)));
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
						renderSuccessSummary();
						$wrapper.find('.smlf-progress-bar-container').hide();
						$form.hide();
						$wrapper.find('.smlf-success-message').fadeIn();
						return;
					}

					showError(response);
					$btn.prop('disabled', false).text(smlf_public_obj.i18n.submit);
				}).fail(function() {
					showFormError(smlf_public_obj.i18n.error);
					$btn.prop('disabled', false).text(smlf_public_obj.i18n.submit);
				});
			});
		}

		$form.on('input change', 'input, textarea, select', function() {
			const $row = $(this).closest('.smlf-field-row');
			$row.removeClass('smlf-field-invalid');
			$row.find('.smlf-field-error').remove();
			$form.find('.smlf-form-error').remove();
		});

		function showError(response) {
			showFormError((response && response.data && response.data.message) || smlf_public_obj.i18n.error);
		}

		function appendSerializedData(requestData) {
			$form.serializeArray().forEach(function(item, index) {
				requestData.append('data[' + index + '][name]', item.name);
				requestData.append('data[' + index + '][value]', item.value);
			});
		}

		function appendFiles(requestData) {
			$wrapper.find('.smlf-file-input').each(function() {
				const files = $(this).data('smlf-files') || Array.from(this.files || []);
				files.forEach(function(file) {
					requestData.append('smlf_files[]', file);
				});
			});
		}

		function renderFileList($input) {
			const files = $input.data('smlf-files') || [];
			const $list = $input.closest('.smlf-field-row').find('.smlf-file-list');
			$list.empty();

			files.forEach(function(file, index) {
				const sizeKb = Math.max(1, Math.round(file.size / 1024));
				const $pill = $('<span/>', { 'class': 'smlf-file-pill' });
				$pill.append($('<span/>', {
					'class': 'smlf-file-pill-name',
					text: file.name + ' (' + sizeKb + ' KB)'
				}));
				$pill.append($('<button/>', {
					type: 'button',
					'class': 'smlf-file-remove',
					'data-file-index': index,
					'aria-label': smlf_public_obj.i18n.remove_file,
					text: 'x'
				}));
				$list.append($pill);
			});
		}

		function syncFileInput($input, files) {
			if (typeof DataTransfer === 'undefined') {
				if (!files.length) {
					$input.val('');
				}
				return;
			}

			const dataTransfer = new DataTransfer();
			files.forEach(function(file) {
				dataTransfer.items.add(file);
			});
			$input.get(0).files = dataTransfer.files;
		}

		function renderSuccessSummary() {
			const $summary = $wrapper.find('.smlf-success-summary');
			if (!$summary.length || !$wrapper.hasClass('smlf-theme-hvac-3d')) {
				return;
			}

			const items = [];
			$form.find('.smlf-field-row').each(function() {
				const $row = $(this);
				const label = $row.data('field-label');
				let value = '';

				const $checked = $row.find('input[type="radio"]:checked');
				if ($checked.length) {
					value = $checked.val();
				} else if ($row.find('select').length) {
					value = $row.find('select').val();
				} else if ($row.find('textarea').length) {
					value = $row.find('textarea').val();
				} else if ($row.find('input[type="file"]').length) {
					const files = $row.find('input[type="file"]').data('smlf-files') || [];
					value = files.map(function(file) { return file.name; }).join(', ');
				} else {
					value = $row.find('input[type="text"], input[type="email"], input[type="tel"]').val();
				}

				if (label && value) {
					items.push({
						label: label,
						value: value
					});
				}
			});

			if (!items.length) {
				$summary.empty();
				return;
			}

			const $list = $('<dl/>');
			items.slice(0, 8).forEach(function(item) {
				$list.append($('<dt/>', { text: item.label }));
				$list.append($('<dd/>', { text: item.value }));
			});

			$summary.empty().append($('<h4/>', { text: smlf_public_obj.i18n.summary_title })).append($list);
		}

		function validateFiles(files) {
			if (!files.length) {
				return '';
			}

			if (files.length > maxFileCount) {
				return formatMessage(smlf_public_obj.i18n.too_many_files, [maxFileCount]);
			}

			for (let i = 0; i < files.length; i++) {
				const file = files[i];
				const extension = file.name.indexOf('.') !== -1 ? file.name.split('.').pop().toLowerCase() : '';

				if (allowedFileExtensions.length && allowedFileExtensions.indexOf(extension) === -1) {
					return formatMessage(smlf_public_obj.i18n.file_type, [file.name]);
				}

				if (file.size > maxFileSizeBytes) {
					return formatMessage(smlf_public_obj.i18n.file_size, [maxFileSizeMb, file.name]);
				}
			}

			return '';
		}

		function formatMessage(message, values) {
			let formatted = message || '';
			values.forEach(function(value, index) {
				formatted = formatted.replace('%' + (index + 1) + '$d', value);
				formatted = formatted.replace('%' + (index + 1) + '$s', value);
				formatted = formatted.replace('%d', value);
				formatted = formatted.replace('%s', value);
			});
			return formatted;
		}

		function clearStepErrors($step) {
			$step.find('.smlf-field-invalid').removeClass('smlf-field-invalid');
			$step.find('.smlf-field-error').remove();
			$step.find('.smlf-form-error').remove();
		}

		function showFieldError($field, message) {
			const $row = $field.closest('.smlf-field-row');
			if (!$row.length || $row.find('.smlf-field-error').length) {
				return;
			}
			$row.addClass('smlf-field-invalid');
			$row.append($('<div/>', {
				'class': 'smlf-field-error',
				text: message
			}));
		}

		function showFormError(message) {
			$wrapper.find('.smlf-form-error').remove();
			$form.prepend($('<div/>', {
				'class': 'smlf-form-error',
				text: message
			}));
		}
	});
});
