jQuery(document).ready(function($) {
	let stepCounter = 1;

	function addStep(stepData) {
		const data = stepData || {};
		const stepId = parseInt(data.step_id || stepCounter, 10);
		const stepTitle = data.title || 'Step ' + stepId;

		const $step = $('<div/>', {
			'class': 'smlf-step',
			'data-step': stepId
		});

		const $header = $('<div/>', { 'class': 'smlf-step-header' });
		$header.append($('<span/>', { 'class': 'step-title-display', text: 'Step ' + stepId }));
		$header.append($('<input/>', {
			type: 'text',
			'class': 'smlf-step-title-input',
			value: stepTitle,
			css: { marginLeft: '10px', width: '200px' }
		}));
		$header.append($('<button/>', {
			'class': 'button smlf-remove-step',
			text: 'Remove'
		}));

		const $logic = $('<div/>', {
			'class': 'smlf-step-logic',
			css: {
				marginBottom: '10px',
				padding: '5px',
				background: '#e9f0f5',
				border: '1px solid #ccd0d4'
			}
		});
		const $logicLabel = $('<label/>', { css: { fontSize: '12px' }, text: 'Condition: Go to Step ' });
		$logicLabel.append($('<input/>', {
			type: 'number',
			'class': 'smlf-logic-target',
			value: data.logic_target || '',
			placeholder: '#',
			css: { width: '50px' }
		}));
		$logicLabel.append(document.createTextNode(' if answer equals '));
		$logicLabel.append($('<input/>', {
			type: 'text',
			'class': 'smlf-logic-value',
			value: data.logic_value || '',
			placeholder: 'Option name'
		}));
		$logic.append($logicLabel);
		const $terminalLabel = $('<label/>', {
			text: ' End step with reset button',
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
	}

	function createFieldItem(fieldData) {
		const data = fieldData || {};
		const type = data.type || 'text';
		const label = data.label || type;
		const fieldId = data.field_id || '';

		const $item = $('<div/>', {
			'class': 'smlf-field-item',
			'data-type': type,
			'data-field-id': fieldId,
			css: {
				background: '#fff',
				border: '1px solid #ddd',
				padding: '10px',
				marginBottom: '5px'
			}
		});

		$item.append($('<strong/>', { text: label }));
		$item.append($('<button/>', {
			'class': 'button-link smlf-remove-field',
			text: 'x',
			css: { float: 'right', color: 'red' }
		}));

		const $settings = $('<div/>', {
			'class': 'smlf-field-settings',
			css: { marginTop: '10px' }
		});
		const $label = $('<label/>', { text: 'Label: ' });
		$label.append($('<input/>', {
			type: 'text',
			'class': 'field-label',
			value: label
		}));
		$settings.append($label);

		const $requiredLabel = $('<label/>', {
			text: ' Required',
			css: { display: 'block', marginTop: '5px' }
		});
		$requiredLabel.prepend($('<input/>', {
			type: 'checkbox',
			'class': 'field-required',
			checked: !!parseInt(data.required || 0, 10)
		}));
		$settings.append($requiredLabel);

		if (type === 'cards' || type === 'radio') {
			const $optionsLabel = $('<label/>', {
				text: 'Options (comma separated): ',
				css: { display: 'block', marginTop: '5px' }
			});
			$optionsLabel.append($('<input/>', {
				type: 'text',
				'class': 'field-options',
				value: data.options || 'Option 1, Option 2',
				css: { width: '100%' }
			}));
			$settings.append($optionsLabel);
		}

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

	$(document).on('click', '.smlf-remove-step', function(e) {
		e.preventDefault();
		$(this).closest('.smlf-step').remove();
	});

	function initSortable() {
		$('.smlf-draggable-blocks li').draggable({
			connectToSortable: '.smlf-fields-dropzone',
			helper: 'clone',
			revert: 'invalid'
		});

		$('.smlf-fields-dropzone').sortable({
			revert: true,
			receive: function(event, ui) {
				const type = ui.helper.data('type');
				const text = ui.helper.text();
				ui.helper.replaceWith(createFieldItem({
					type: type,
					label: text,
					required: type === 'email' ? 1 : 0
				}));
			}
		});
	}

	$(document).on('click', '.smlf-remove-field', function(e) {
		e.preventDefault();
		$(this).closest('.smlf-field-item').remove();
	});

	if (typeof window.smlf_existing_form_data !== 'undefined' && Array.isArray(window.smlf_existing_form_data.steps) && window.smlf_existing_form_data.steps.length > 0) {
		$('#smlf-form-title').val(window.smlf_existing_form_data.title || 'New Form');
		window.smlf_existing_form_data.steps.forEach(function(step) {
			addStep(step);
		});
	} else if ($('#smlf-steps-container').length > 0) {
		addStep();
	}

	$('#smlf-save-form').on('click', function(e) {
		e.preventDefault();

		const title = $('#smlf-form-title').val();
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
					required: $field.find('.field-required').is(':checked') ? 1 : 0
				});
			});

			steps.push({
				step_id: $step.data('step'),
				title: $step.find('.smlf-step-title-input').val(),
				logic_target: $step.find('.smlf-logic-target').val(),
				logic_value: $step.find('.smlf-logic-value').val(),
				terminal: $step.find('.smlf-step-terminal').is(':checked') ? 'reset' : '',
				fields: fields
			});
		});

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
				steps: steps
			})
		}).done(function(response) {
			if (response.success) {
				alert(smlf_admin_obj.i18n.save_success);
				if (!formId && response.data.form_id) {
					window.location.href = window.location.href + '&id=' + response.data.form_id;
				}
				return;
			}

			alert((response.data && response.data.message) || smlf_admin_obj.i18n.save_error);
		}).fail(function() {
			alert(smlf_admin_obj.i18n.save_error);
		}).always(function() {
			$button.prop('disabled', false);
		});
	});
});
