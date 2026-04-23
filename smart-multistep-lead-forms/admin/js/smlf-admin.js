jQuery(document).ready(function($) {
	let stepCounter = 1;

	// Add step
	$('#smlf-add-step').on('click', function(e) {
		e.preventDefault();
		const stepHtml = `
			<div class="smlf-step" data-step="${stepCounter}">
				<div class="smlf-step-header">
					<span>Step ${stepCounter}</span>
					<button class="button smlf-remove-step">Remove</button>
				</div>
					<div class="smlf-step-logic" style="margin-bottom:10px; padding:5px; background:#e9f0f5; border:1px solid #ccd0d4;">
						<label style="font-size: 12px;">Condition: Go to Step
							<input type="number" class="smlf-logic-target" style="width:50px" placeholder="#">
							if answer equals <input type="text" class="smlf-logic-value" placeholder="Option name">
						</label>
					</div>
				<div class="smlf-fields-dropzone"></div>
			</div>
		`;
		$('#smlf-steps-container').append(stepHtml);
		initSortable();
		stepCounter++;
	});

	// Remove step
	$(document).on('click', '.smlf-remove-step', function(e) {
		e.preventDefault();
		$(this).closest('.smlf-step').remove();
	});

	// Init Draggable & Sortable
	function initSortable() {
		$('.smlf-draggable-blocks li').draggable({
			connectToSortable: ".smlf-fields-dropzone",
			helper: "clone",
			revert: "invalid"
		});

		$('.smlf-fields-dropzone').sortable({
			revert: true,
			receive: function(event, ui) {
				const type = ui.helper.data('type');
				const text = ui.helper.text();
				let optionsHtml = '';
				if (type === 'cards' || type === 'radio') {
					optionsHtml = `<label style="display:block;margin-top:5px;">Options (comma separated): <input type="text" class="field-options" value="Option 1, Option 2" style="width:100%"></label>`;
				}
				const fieldHtml = `
					<div class="smlf-field-item" data-type="${type}" style="background:#fff; border:1px solid #ddd; padding:10px; margin-bottom:5px;">
						<strong>${text}</strong>
						<button class="button-link smlf-remove-field" style="float:right; color:red;">x</button>
						<div class="smlf-field-settings" style="margin-top:10px;">
							<label>Label: <input type="text" class="field-label" value="${text}"></label>
							${optionsHtml}
						</div>
					</div>
				`;
				// Replace the cloned helper with the actual field UI
				ui.helper.replaceWith(fieldHtml);
			}
		});
	}

	$(document).on('click', '.smlf-remove-field', function(e) {
		e.preventDefault();
		$(this).closest('.smlf-field-item').remove();
	});

	// Add initial step
	if ($('#smlf-steps-container').length > 0) {
		$('#smlf-add-step').trigger('click');
	}

	// Save Form via AJAX
	$('#smlf-save-form').on('click', function(e) {
		e.preventDefault();
		const title = $('#smlf-form-title').val();
		const steps = [];

		$('.smlf-step').each(function() {
			const stepId = $(this).data('step');
			const logicTarget = $(this).find('.smlf-logic-target').val();
			const logicValue = $(this).find('.smlf-logic-value').val();
			const fields = [];
			$(this).find('.smlf-field-item').each(function() {
				const type = $(this).data('type');
				const options = (type === 'cards' || type === 'radio') ? $(this).find('.field-options').val() : '';
				fields.push({
					type: type,
					label: $(this).find('.field-label').val(),
					options: options
				});
			});
			steps.push({
				step_id: stepId,
				logic_target: logicTarget,
				logic_value: logicValue,
				fields: fields
			});
		});

		const formData = {
			title: title,
			steps: steps
		};

		// Parse ID from URL if editing
		const urlParams = new URLSearchParams(window.location.search);
		const formId = urlParams.get('id');

		$.post(smlf_admin_obj.ajax_url, {
			action: 'smlf_save_form_admin',
			nonce: smlf_admin_obj.nonce,
			form_id: formId,
			form_data: JSON.stringify(formData)
		}, function(response) {
			if (response.success) {
				alert(smlf_admin_obj.i18n.save_success);
				if (!formId && response.data.form_id) {
					window.location.href = window.location.href + '&id=' + response.data.form_id;
				}
			} else {
				alert(smlf_admin_obj.i18n.save_error);
			}
		});
	});
});
