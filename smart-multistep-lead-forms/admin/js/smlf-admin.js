jQuery(document).ready(function($) {
	let stepCounter = 1;
	const i18n = smlf_admin_obj.i18n || {};

	function addStep(stepData) {
		const data = stepData || {};
		const stepId = parseInt(data.step_id || stepCounter, 10);
		const stepTitle = data.title || i18n.step + ' ' + stepId;

		const $step = $('<div/>', {
			'class': 'smlf-step',
			'data-step': stepId,
			'data-next-step': data.next_step || '',
			'data-logic-rules': JSON.stringify(data.logic_rules || [])
		});

		const $header = $('<div/>', { 'class': 'smlf-step-header' });
		$header.append($('<span/>', { 'class': 'step-title-display', text: i18n.step + ' ' + stepId }));
		$header.append($('<input/>', {
			type: 'text',
			'class': 'smlf-step-title-input',
			value: stepTitle
		}));
		$header.append($('<button/>', {
			'class': 'button smlf-remove-step',
			text: i18n.remove
		}));

		const $logic = $('<div/>', {
			'class': 'smlf-step-logic'
		});
		$logic.append($('<strong/>', { text: i18n.conditional_logic }));

		const $nextLabel = $('<label/>', {
			'class': 'smlf-step-next-label',
			text: i18n.default_next_step + ': '
		});
		$nextLabel.append($('<input/>', {
			type: 'number',
			'class': 'smlf-next-step',
			value: data.next_step || '',
			placeholder: '#',
			min: 1
		}));
		$logic.append($nextLabel);

		const $rules = $('<div/>', { 'class': 'smlf-logic-rules' });
		normalizeStepLogicRules(data.logic_rules || []).forEach(function(rule) {
			$rules.append(createLogicRule(rule));
		});
		$logic.append($rules);
		$logic.append($('<button/>', {
			type: 'button',
			'class': 'button smlf-add-logic-rule',
			text: i18n.add_logic_rule
		}));

		const $logicLabel = $('<label/>', { css: { fontSize: '12px' }, text: i18n.condition_prefix + ' ' });
		$logicLabel.append($('<input/>', {
			type: 'number',
			'class': 'smlf-logic-target',
			value: data.logic_target || '',
			placeholder: '#',
			css: { width: '50px' }
		}));
		$logicLabel.append(document.createTextNode(' ' + i18n.condition_middle + ' '));
		$logicLabel.append($('<input/>', {
			type: 'text',
			'class': 'smlf-logic-value',
			value: data.logic_value || '',
			placeholder: i18n.condition_placeholder
		}));
		$logicLabel.addClass('smlf-legacy-logic');
		$logic.append($logicLabel);
		const $terminalLabel = $('<label/>', {
			text: ' ' + i18n.terminal_reset,
			css: { display: 'block', marginTop: '8px', fontSize: '12px' }
		});
		$terminalLabel.prepend($('<input/>', {
			type: 'checkbox',
			'class': 'smlf-step-terminal',
			checked: data.terminal === 'reset'
		}));
		$logic.append($terminalLabel);

		$step.append($header, $logic, $('<div/>', { 'class': 'smlf-fields-dropzone' }));
		$('#smlf-steps-container').append($step);

		if (Array.isArray(data.fields)) {
			data.fields.forEach(function(field) {
				$step.find('.smlf-fields-dropzone').append(createFieldItem(field));
			});
		}

		stepCounter = Math.max(stepCounter, stepId + 1);
		initSortable();
		renderPreview();
	}

	function createLogicRule(ruleData) {
		const data = ruleData || {};
		const $rule = $('<div/>', { 'class': 'smlf-logic-rule' });
		$rule.append($('<span/>', { text: i18n.if_answer_equals }));
		$rule.append($('<input/>', {
			type: 'text',
			'class': 'smlf-rule-value',
			value: data.value || '',
			placeholder: i18n.condition_placeholder
		}));
		$rule.append($('<span/>', { text: i18n.go_to_step }));
		$rule.append($('<input/>', {
			type: 'number',
			'class': 'smlf-rule-target',
			value: data.target || '',
			placeholder: '#',
			min: 1
		}));
		$rule.append($('<button/>', {
			type: 'button',
			'class': 'button-link smlf-remove-logic-rule',
			text: 'x'
		}));
		return $rule;
	}

	function createFieldItem(fieldData) {
		const data = fieldData || {};
		const type = data.type || 'text';
		const label = data.label || getDefaultLabel(type);
		const fieldId = data.field_id || '';
		const fieldWidth = data.field_width || 'full';
		const displayMode = data.display_mode || (type === 'cards' ? 'cards' : 'default');

		const $item = $('<details/>', {
			'class': 'smlf-field-item',
			'data-type': type,
			'data-field-id': fieldId
		});

		const $summary = $('<summary/>', { 'class': 'smlf-field-item-summary' });
		$summary.append($('<strong/>', { text: label }));
		$summary.append($('<span/>', { 'class': 'smlf-field-item-type', text: getDefaultLabel(type) }));
		$summary.append($('<button/>', {
			'class': 'button-link smlf-remove-field',
			text: 'x',
			css: { float: 'right', color: 'red' }
		}));
		$item.append($summary);

		const $settings = $('<div/>', {
			'class': 'smlf-field-settings',
			css: { marginTop: '10px' }
		});
		const $label = $('<label/>', { text: i18n.label + ': ' });
		$label.append($('<input/>', {
			type: 'text',
			'class': 'field-label',
			value: label
		}));
		$settings.append($label);

		const $requiredLabel = $('<label/>', {
			text: ' ' + i18n.required,
			css: { display: 'block', marginTop: '5px' }
		});
		$requiredLabel.prepend($('<input/>', {
			type: 'checkbox',
			'class': 'field-required',
			checked: !!parseInt(data.required || 0, 10)
		}));
		$settings.append($requiredLabel);

		const $widthLabel = $('<label/>', { text: i18n.field_width + ': ' });
		const $widthSelect = $('<select/>', { 'class': 'field-width' });
		[
			['full', i18n.width_full],
			['half', i18n.width_half],
			['third', i18n.width_third]
		].forEach(function(option) {
			$widthSelect.append($('<option/>', { value: option[0], text: option[1] }));
		});
		$widthSelect.val(fieldWidth);
		$widthLabel.append($widthSelect);
		$settings.append($widthLabel);

		if (type === 'cards' || type === 'radio') {
			const $displayLabel = $('<label/>', { text: i18n.display_mode + ': ' });
			const $displaySelect = $('<select/>', { 'class': 'field-display-mode' });
			[
				['default', i18n.display_default],
				['cards', i18n.display_cards],
				['dropdown', i18n.display_dropdown],
				['list', i18n.display_list]
			].forEach(function(option) {
				$displaySelect.append($('<option/>', { value: option[0], text: option[1] }));
			});
			$displaySelect.val(displayMode);
			$displayLabel.append($displaySelect);
			$settings.append($displayLabel);

			const $optionsLabel = $('<label/>', {
				text: i18n.options + ': ',
				css: { display: 'block', marginTop: '5px' }
			});
			$optionsLabel.append($('<input/>', {
				type: 'text',
				'class': 'field-options',
				value: data.options || i18n.option_1 + ', ' + i18n.option_2,
				css: { width: '100%' }
			}));
			$settings.append($optionsLabel);
		}

		if (type === 'consent') {
			const $consentTextLabel = $('<label/>', { text: i18n.consent_text + ': ' });
			$consentTextLabel.append($('<textarea/>', {
				'class': 'field-consent-text',
				rows: 3,
				text: data.consent_text || i18n.consent_default_text
			}));
			$settings.append($consentTextLabel);

			[
				['field-link-text', i18n.linked_text, data.link_text || ''],
				['field-link-url', i18n.link_url, data.link_url || '']
			].forEach(function(item) {
				const $itemLabel = $('<label/>', { text: item[1] + ': ' });
				$itemLabel.append($('<input/>', { type: 'text', 'class': item[0], value: item[2] }));
				$settings.append($itemLabel);
			});

			const $behaviorLabel = $('<label/>', { text: i18n.link_behavior + ': ' });
			const $behaviorSelect = $('<select/>', { 'class': 'field-link-behavior' });
			[
				['new_tab', i18n.open_new_tab],
				['popup_page', i18n.popup_wordpress_page],
				['popup_text', i18n.popup_custom_text]
			].forEach(function(option) {
				$behaviorSelect.append($('<option/>', { value: option[0], text: option[1] }));
			});
			$behaviorSelect.val(data.link_behavior || 'new_tab');
			$behaviorLabel.append($behaviorSelect);
			$settings.append($behaviorLabel);

			const $pageLabel = $('<label/>', { text: i18n.wordpress_page + ': ' });
			const $pageSelect = $('<select/>', { 'class': 'field-link-page-id' });
			$pageSelect.append($('<option/>', { value: '0', text: '-' }));
			(smlf_admin_obj.pages || []).forEach(function(page) {
				$pageSelect.append($('<option/>', { value: page.id, text: page.title }));
			});
			$pageSelect.val(String(data.link_page_id || 0));
			$pageLabel.append($pageSelect);
			$settings.append($pageLabel);

			const $popupTextLabel = $('<label/>', { text: i18n.popup_text + ': ' });
			$popupTextLabel.append($('<textarea/>', {
				'class': 'field-popup-text',
				rows: 4,
				text: data.popup_text || ''
			}));
			$settings.append($popupTextLabel);

			const $defaultLabel = $('<label/>', { text: ' ' + i18n.checked_by_default });
			$defaultLabel.prepend($('<input/>', {
				type: 'checkbox',
				'class': 'field-checked-default',
				checked: !!parseInt(data.checked_default || 0, 10)
			}));
			$settings.append($defaultLabel);
		}

		const $colors = $('<div/>', { 'class': 'smlf-field-color-grid' });
		[
			['label-color', i18n.label_color, data.label_color || ''],
			['input-background', i18n.input_background, data.input_background || ''],
			['input-text-color', i18n.input_text_color, data.input_text_color || '']
		].forEach(function(colorField) {
			const $colorLabel = $('<label/>', { text: colorField[1] + ': ' });
			$colorLabel.append($('<input/>', {
				type: 'color',
				'class': 'field-' + colorField[0],
				value: colorField[2] || '#ffffff'
			}));
			$colorLabel.append($('<input/>', {
				type: 'checkbox',
				'class': 'field-' + colorField[0] + '-enabled',
				checked: !!colorField[2],
				title: colorField[1]
			}));
			$colors.append($colorLabel);
		});
		$settings.append($colors);

		if (type === 'message') {
			$item.find('.field-required').closest('label').hide();
		}

		$item.append($settings);
		return $item;
	}

	$('#smlf-add-step').on('click', function(e) {
		e.preventDefault();
		addStep();
	});

	$('.smlf-draggable-blocks li').on('click', function(e) {
		e.preventDefault();
		addFieldToActiveStep($(this).data('type'), $(this).text());
	});

	$(document).on('click', '.smlf-remove-step', function(e) {
		e.preventDefault();
		$(this).closest('.smlf-step').remove();
		renderPreview();
	});

	$(document).on('click', '.smlf-add-logic-rule', function(e) {
		e.preventDefault();
		$(this).siblings('.smlf-logic-rules').append(createLogicRule());
		renderPreview();
	});

	$(document).on('click', '.smlf-remove-logic-rule', function(e) {
		e.preventDefault();
		$(this).closest('.smlf-logic-rule').remove();
		renderPreview();
	});

	function initSortable() {
		$('.smlf-draggable-blocks li').draggable({
			connectToSortable: '.smlf-fields-dropzone',
			helper: 'clone',
			revert: 'invalid',
			appendTo: 'body',
			zIndex: 100000
		});

		$('.smlf-fields-dropzone').sortable({
			revert: true,
			items: '.smlf-field-item',
			placeholder: 'smlf-field-placeholder',
			receive: function(event, ui) {
				const $item = ui.item;
				const type = $item.data('type');
				const text = $item.text();
				$item.replaceWith(createFieldItem({
					type: type,
					label: text,
					required: type === 'email' ? 1 : 0
				}));
				renderPreview();
			}
		});
	}

	function addFieldToActiveStep(type, text) {
		let $dropzone = $('.smlf-step').last().find('.smlf-fields-dropzone');

		if (!$dropzone.length) {
			addStep();
			$dropzone = $('.smlf-step').last().find('.smlf-fields-dropzone');
		}

		$dropzone.append(createFieldItem({
			type: type,
			label: text || getDefaultLabel(type),
			required: type === 'email' ? 1 : 0
		}));
		renderPreview();
	}

	$(document).on('click', '.smlf-remove-field', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$(this).closest('.smlf-field-item').remove();
		renderPreview();
	});

	$(document).on('change', '.smlf-lead-status-select', function() {
		const $select = $(this);
		const previous = $select.data('previous') || 'new';

		$select.prop('disabled', true);
		$.post(smlf_admin_obj.ajax_url, {
			action: 'smlf_update_lead_status',
			nonce: smlf_admin_obj.nonce,
			lead_id: $select.data('lead-id'),
			lead_status: $select.val()
		}).done(function(response) {
			if (response.success) {
				$select.data('previous', $select.val());
				return;
			}

			$select.val(previous);
			alert((response.data && response.data.message) || i18n.save_error);
		}).fail(function() {
			$select.val(previous);
			alert(i18n.save_error);
		}).always(function() {
			$select.prop('disabled', false);
		});
	});

	if (typeof window.smlf_existing_form_data !== 'undefined' && Array.isArray(window.smlf_existing_form_data.steps) && window.smlf_existing_form_data.steps.length > 0) {
		$('#smlf-form-title').val(window.smlf_existing_form_data.title || 'New Form');
		loadSettings(window.smlf_existing_form_data.settings || {});
		window.smlf_existing_form_data.steps.forEach(function(step) {
			addStep(step);
		});
	} else if ($('#smlf-steps-container').length > 0) {
		loadSettings(window.smlf_existing_form_data && window.smlf_existing_form_data.settings ? window.smlf_existing_form_data.settings : {});
		addStep();
	}

	$(document).on('input change', '#smlf-form-title, #smlf-form-language, #smlf-theme, #smlf-font-family, #smlf-primary-color, #smlf-accent-color, #smlf-background-color, #smlf-text-color, #smlf-captcha-method, #smlf-captcha-gate, #smlf-captcha-step, .smlf-step input, .smlf-step select, .smlf-field-item input, .smlf-field-item select, .smlf-field-item textarea', renderPreview);

	$('#smlf-load-template').on('click', function(e) {
		e.preventDefault();
		loadTemplate(getTemplate('consultation'));
	});

	$('#smlf-load-hvac-template').on('click', function(e) {
		e.preventDefault();
		loadTemplate(getTemplate('hvac'));
	});

	$('#smlf-save-form').on('click', function(e) {
		e.preventDefault();

		const title = $('#smlf-form-title').val();
		const steps = collectSteps();
		const settings = collectSettings();
		const urlParams = new URLSearchParams(window.location.search);
		const formId = urlParams.get('id') || (window.smlf_existing_form_data ? window.smlf_existing_form_data.id : 0);
		const $button = $(this);

		$button.prop('disabled', true);

		$.post(smlf_admin_obj.ajax_url, {
			action: 'smlf_save_form_admin',
			nonce: smlf_admin_obj.nonce,
			form_id: formId,
			form_data: JSON.stringify({
				title: title,
				settings: settings,
				steps: steps
			})
		}).done(function(response) {
			if (response.success) {
				alert(i18n.save_success);
				if (!formId && response.data.form_id) {
					window.location.href = window.location.href + '&id=' + response.data.form_id;
				}
				return;
			}

			alert((response.data && response.data.message) || i18n.save_error);
		}).fail(function() {
			alert(i18n.save_error);
		}).always(function() {
			$button.prop('disabled', false);
		});
	});

	function collectSteps() {
		const steps = [];

		$('.smlf-step').each(function() {
			const $step = $(this);
			const fields = [];

			$step.find('.smlf-field-item').each(function(index) {
				const $field = $(this);
				const type = $field.data('type');
				const existingId = $field.data('field-id');

				fields.push({
					field_id: existingId || 'field_' + $step.data('step') + '_' + (index + 1),
					type: type,
					label: $field.find('.field-label').val(),
					options: (type === 'cards' || type === 'radio') ? $field.find('.field-options').val() : '',
					required: $field.find('.field-required').is(':checked') ? 1 : 0,
					field_width: $field.find('.field-width').val() || 'full',
					display_mode: $field.find('.field-display-mode').val() || 'default',
					label_color: getOptionalColor($field, 'label-color'),
					input_background: getOptionalColor($field, 'input-background'),
					input_text_color: getOptionalColor($field, 'input-text-color'),
					consent_text: $field.find('.field-consent-text').val() || '',
					link_text: $field.find('.field-link-text').val() || '',
					link_url: $field.find('.field-link-url').val() || '',
					link_behavior: $field.find('.field-link-behavior').val() || 'new_tab',
					link_page_id: parseInt($field.find('.field-link-page-id').val() || '0', 10),
					popup_text: $field.find('.field-popup-text').val() || '',
					checked_default: $field.find('.field-checked-default').is(':checked') ? 1 : 0
				});
			});

			steps.push({
				step_id: $step.data('step'),
				title: $step.find('.smlf-step-title-input').val(),
				next_step: parseInt($step.find('.smlf-next-step').val() || '0', 10),
				logic_rules: collectLogicRules($step),
				logic_target: $step.find('.smlf-logic-target').val(),
				logic_value: $step.find('.smlf-logic-value').val(),
				terminal: $step.find('.smlf-step-terminal').is(':checked') ? 'reset' : '',
				fields: fields
			});
		});

		return steps;
	}

	function loadTemplate(template) {
		if (!template || !Array.isArray(template.steps)) {
			return;
		}

		$('#smlf-form-title').val(template.title || '');
		loadSettings(template.settings || {});
		$('#smlf-steps-container').empty();
		stepCounter = 1;
		template.steps.forEach(function(step) {
			addStep(step);
		});
		renderPreview();
	}

	function getTemplate(type) {
		const language = $('#smlf-template-language').val() || $('#smlf-form-language').val() || 'auto';
		const grouped = smlf_admin_obj.templates_by_language || {};
		const languageTemplates = grouped[language] || grouped.auto || smlf_admin_obj.templates || {};
		return languageTemplates[type] || (smlf_admin_obj.templates ? smlf_admin_obj.templates[type] : null) || smlf_admin_obj.template;
	}

	function renderPreview() {
		const $preview = $('#smlf-builder-preview');
		if (!$preview.length) {
			return;
		}

		const title = $('#smlf-form-title').val();
		const settings = collectSettings();
		const steps = collectSteps();
		$preview.empty();

		const $shell = $('<div/>', { 'class': 'smlf-preview-shell' });
		$shell.css({
			'--smlf-primary': settings.primary_color,
			'--smlf-accent': settings.accent_color,
			'--smlf-bg': settings.background_color,
			'--smlf-text': settings.text_color,
			fontFamily: settings.font_family || 'inherit',
			color: settings.text_color
		}).addClass('smlf-preview-theme-' + settings.theme);
		$shell.append($('<h3/>', { text: title }));
		if (settings.captcha_method !== 'none') {
			$shell.append($('<div/>', {
				'class': 'smlf-preview-captcha-note',
				text: i18n.captcha_method + ': ' + $('#smlf-captcha-method option:selected').text() + ' / ' + $('#smlf-captcha-gate option:selected').text()
			}));
		}
		if (!steps.length) {
			$shell.append($('<div/>', {
				'class': 'smlf-preview-message',
				text: i18n.add_step
			}));
			$preview.append($shell);
			return;
		}

		steps.forEach(function(step, stepIndex) {
			const $step = $('<div/>', { 'class': 'smlf-preview-step' });
			$step.append($('<div/>', { 'class': 'smlf-preview-step-title', text: step.title || i18n.step + ' ' + (stepIndex + 1) }));

			step.fields.forEach(function(field) {
				const $field = $('<div/>', { 'class': 'smlf-preview-field smlf-preview-field-' + field.type + ' smlf-preview-width-' + (field.field_width || 'full') });
				if (field.input_background) {
					$field.css('--smlf-field-bg', field.input_background);
				}
				if (field.input_text_color) {
					$field.css('--smlf-field-text', field.input_text_color);
				}
				if (field.type === 'message') {
					$field.append($('<div/>', { 'class': 'smlf-preview-message', text: field.label }));
				} else if (field.type === 'consent') {
					$field.append($('<div/>', { 'class': 'smlf-preview-consent', text: field.consent_text || field.label }));
				} else {
					const $fieldLabel = $('<label/>', { text: field.label + (field.required ? ' *' : '') });
					if (field.label_color) {
						$fieldLabel.css('color', field.label_color);
					}
					$field.append($fieldLabel);
				}

				if (field.type === 'text' || field.type === 'email' || field.type === 'phone') {
					$field.append($('<div/>', { 'class': 'smlf-preview-input' }));
				} else if (field.type === 'textarea') {
					$field.append($('<div/>', { 'class': 'smlf-preview-textarea' }));
				} else if (field.type === 'file') {
					$field.append($('<div/>', { 'class': 'smlf-preview-file', text: i18n.drag_files }));
				} else if (field.type === 'cards' || field.type === 'radio') {
					const mode = field.display_mode || (field.type === 'cards' ? 'cards' : 'list');
					if (mode === 'dropdown') {
						$field.append($('<div/>', { 'class': 'smlf-preview-select', text: i18n.display_dropdown }));
						$step.append($field);
						return;
					}
					const $cards = $('<div/>', { 'class': mode === 'cards' ? 'smlf-preview-cards' : 'smlf-preview-list' });
					String(field.options || '').split(',').map(function(option) {
						return option.trim();
					}).filter(Boolean).forEach(function(option) {
						$cards.append($('<span/>', { text: option }));
					});
					$field.append($cards);
				}

				$step.append($field);
			});

			const $nav = $('<div/>', { 'class': 'smlf-preview-nav' });
			if (stepIndex > 0) {
				$nav.append($('<button/>', { type: 'button', text: i18n.back }));
			}
			$nav.append($('<button/>', { type: 'button', text: step.terminal === 'reset' ? i18n.reset : (stepIndex < steps.length - 1 ? i18n.next : i18n.submit) }));
			$step.append($nav);
			$shell.append($step);
		});

		$preview.append($shell);
	}

	function getDefaultLabel(type) {
		const map = {
			text: i18n.text_input,
			email: i18n.email_input,
			phone: i18n.phone_input,
			textarea: i18n.long_text,
			file: i18n.file_upload,
			message: i18n.message_text,
			consent: i18n.consent_checkbox,
			cards: i18n.clickable_cards,
			radio: i18n.radio_buttons
		};
		return map[type] || type;
	}

	function collectSettings() {
		return {
			captcha_method: $('#smlf-captcha-method').val() || 'inherit',
			captcha_gate: $('#smlf-captcha-gate').val() || 'before_form',
			captcha_step: parseInt($('#smlf-captcha-step').val() || '1', 10),
			form_language: $('#smlf-form-language').val() || 'auto',
			theme: $('#smlf-theme').val() || 'consult_pro',
			font_family: $('#smlf-font-family').val() || 'inherit',
			primary_color: $('#smlf-primary-color').val() || '#0ea5e9',
			accent_color: $('#smlf-accent-color').val() || '#14b8a6',
			background_color: $('#smlf-background-color').val() || '#ffffff',
			text_color: $('#smlf-text-color').val() || '#111827'
		};
	}

	function loadSettings(settings) {
		$('#smlf-form-language').val(settings.form_language || 'auto');
		$('#smlf-theme').val(settings.theme || 'consult_pro');
		$('#smlf-font-family').val(settings.font_family || 'inherit');
		$('#smlf-primary-color').val(settings.primary_color || '#0ea5e9');
		$('#smlf-accent-color').val(settings.accent_color || '#14b8a6');
		$('#smlf-background-color').val(settings.background_color || '#ffffff');
		$('#smlf-text-color').val(settings.text_color || '#111827');
		$('#smlf-captcha-method').val(settings.captcha_method || 'inherit');
		$('#smlf-captcha-gate').val(settings.captcha_gate || 'before_form');
	$('#smlf-captcha-step').val(settings.captcha_step || 1);
	}

	function collectLogicRules($step) {
		const rules = [];
		$step.find('.smlf-logic-rule').each(function() {
			const target = parseInt($(this).find('.smlf-rule-target').val() || '0', 10);
			const value = String($(this).find('.smlf-rule-value').val() || '').trim();
			if (target && value) {
				rules.push({
					target: target,
					value: value
				});
			}
		});

		return rules;
	}

	function getOptionalColor($field, key) {
		return $field.find('.field-' + key + '-enabled').is(':checked') ? $field.find('.field-' + key).val() : '';
	}

	function normalizeStepLogicRules(rules) {
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

	setTimeout(renderPreview, 0);
});
