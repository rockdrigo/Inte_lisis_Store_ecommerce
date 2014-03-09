var Tax_Settings = {
	activeTab: '',

	init: function()
	{
		$('#taxSettings').tabs();
		if(Tax_Settings.activeTab) {
			$('#taxSettings').tabs('select', Tax_Settings.activeTab);
		}

		$('.applyTaxChagesLink').click(function() {
			$.iModal({
				type: 'ajax',
				url: 'index.php?ToDo=rebuildTaxZonePrices',
				width: 320
			});
			return false;
		});

		$('.cancelLink').click(function() {
			if(confirm(lang.ConfirmCancel)) {
				window.location = 'index.php?ToDo=viewTaxSettings';
			}
		});

		Tax_Settings.initSettingsTab();
		Tax_Settings.initTaxZonesTab();
		Tax_Settings.initTaxClassesTab();
	},

	initSettingsTab: function()
	{
		$('select[name=taxDefaultCountry]').change(function() {
			if($(this).val() == 0) {
				$('.defaultStateRow').hide();
				return;
			}

			$.ajax({
				url: 'remote.php?w=countryStates&c='+$(this).val(),
				success: function(response) {
					stateSelect = $('select[name=taxDefaultState]');
					stateSelect.find('option:gt(0)').remove();
					states = response.split('~');
					for(i = 0; i < states.length; ++i) {
						vals = states[i].split('|');
						if(vals[0]) {
							stateSelect.append($('<option />')
								.val(vals[1])
								.html(vals[0])
							);
						}
					}

					if(stateSelect.find('option').length <= 1) {
						stateSelect.val(0);
						$('.defaultStateRow').hide();
					}
					else {
						$('.defaultStateRow').show();
					}
				}
			});
		});

		$('#taxSettingsForm').submit(function() {
			if(!$('input[name=taxLabel]').val()) {
				alert(lang.InvalidTaxSettingTaxLabel);
				$('input[name=taxLabel]').focus();
				return false;
			}

			if($('select[name=taxDefaultCountry]').val() <= 0) {
				alert(lang.InvalidTaxSettingTaxDefaultCountry);
				$('select[name=taxDefaultCountry]').focus();
				return false;
			}

			if(!$('.defaultStateRow:hidden').length && $('select[name=taxDefaultState]').val() <= 0) {
				alert(lang.InvalidTaxSettingTaxDefaultState);
				$('select[name=taxDefaultState]').focus();
				return false;
			}

			return true;
		});
	},

	initTaxZonesTab: function()
	{
		$('.addTaxZoneButton').click(function() {
			window.location = 'index.php?ToDo=addTaxZone';
		});

		$('.taxZonesGrid .checkAll input').click(function() {
			$(this.form).find('input:checkbox').not(':disabled').attr('checked', this.checked);
		});

		$('.defaultZoneDefinition').click(function() {
			alert(lang.DefaultZoneWhatDoesThisMean);
			return false;
		});

		$('#taxZonesTab form').submit(Tax_Settings.deleteSelectedTaxZones);
		$('.taxZonesGrid .deleteTaxZoneLink').live('click', Tax_Settings.deleteTaxZone);
		$('.taxZonesGrid .copyTaxZoneLink').live('click', Tax_Settings.copyTaxZone);
		$('.taxZonesGrid .toggleTaxZoneStatusLink').live('click', Tax_Settings.toggleTaxZoneStatus);
	},

	toggleTaxZoneStatus: function()
	{
		link = this;
		taxZoneId = link.href.match(/#toggle([0-9]+)/)[1];
		currentStatus = $(link).hasClass('statusToggle1');
		$(link).addClass('statusToggleLoading');
		$.ajax({
			url: 'index.php?ToDo=toggleTaxZoneStatus',
			type: 'post',
			data: {
				id: taxZoneId,
				status: Number(!currentStatus)
			},
			dataType: 'json',
			success: function(response) {
				$(link).removeClass('statusToggleLoading');
				if(response.status == 0) {
					alert(response.message);
				}
				else {
					$(link)
						.removeClass('statusToggle' + Number(currentStatus))
						.addClass('statusToggle' + Number(!currentStatus))
					;
				}
			},
			error: function() {
				$(link).removeClass('statusToggleLoading');
			}
		})

		return false;
	},

	copyTaxZone: function()
	{
		link = this;
		taxZoneId = link.href.match(/#copy([0-9]+)/)[1];

		$('<form />')
			.attr('method', 'post')
			.attr('action', 'index.php')
			.append($('<input />')
				.attr('type', 'hidden')
				.attr('name', 'ToDo')
				.attr('value', 'copyTaxZone')
			)
			.append($('<input />')
				.attr('type', 'hidden')
				.attr('name', 'id')
				.attr('value', taxZoneId)
			)
			.appendTo('body')
			.submit()
		;
	},

	deleteTaxZone: function()
	{
		$('.taxZonesGrid td.check input').attr('checked', false);
		$(this).parents('tr').find('td.check input').attr('checked', true);
		$('#taxZonesTab form').submit();
		return false;
	},

	deleteSelectedTaxZones: function()
	{
		if($('.taxZonesGrid td.check input:checked').length == 0) {
			alert(lang.SelectTaxZonesToDelete);
			return false;
		}

		if(confirm(lang.ConfirmDeleteTaxZones)) {
			return true;
		}

		return false;
	},

	initTaxClassesTab: function()
	{
		$('#taxClassesTab .addButton').live('click', function() {
			row = $(this).parents('.formRow');
			newRow = row.clone();
			input = newRow.find('input[type=text]')
				.attr('name', 'taxClass[new][]')
				.attr('readonly', false)
				.val('')
			;
			$('.removeButton', newRow).show();
			row.after(newRow);
			input.focus();
		});

		$('#taxClassesTab .removeButton').live('click', function() {
			if(confirm(lang.ConfirmDeleteTaxClass)) {
				$(this).parents('.formRow').remove();
			}
		});

		$('#taxClassesTab form').submit(function() {
			cancel = false;
			$('#taxClassesTab .formRow .taxClassLabel').each(function() {
				if(!$(this).val()) {
					alert(lang.TaxClassMissingName);
					$(this).focus();
					cancel = true;
					return false;
				}
			});

			if(cancel) {
				return false;
			}

			return true;
		});
	}
};

$(document).ready(Tax_Settings.init);