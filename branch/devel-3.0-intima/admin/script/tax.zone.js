var Tax_Zone = {
	id: 0,

	init: function()
	{
		$('#taxZoneSettings').tabs();

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
				window.location = 'index.php?ToDo=viewTaxSettings#taxZonesTab';
			}
		});

		$('#taxZoneForm input[name=type]').click(function() {
			value = $(this).val();
			if(value == 'state') {
				$('.stateSelectNone').css({width: $('#stateSelect').css('width'), height: $('#stateSelect').css('height')});
			}
			activeItem = '.zoneType' + value.charAt(0).toUpperCase() + value.substr(1);
			$('.zoneTypeToggle').not(activeItem).hide();
			$(activeItem).show();
		});
		$('#taxZoneForm input[name=type]:checked').click();

		$('#taxZoneForm input[name=applies_to]').click(function() {
			if($(this).val() == 'all') {
				$('.zoneGroupSelect').hide();
			}
			else {
				$('.zoneGroupSelect').show();
			}
		});

		if($('#taxZoneForm input[name=id]').length > 0) {
			Tax_Zone.id = $('#taxZoneForm input[name=id]').val();
		}

		$('#taxZoneForm').submit(Tax_Zone.validateForm);
		Tax_Zone.initTaxRatesTab();
		Tax_Zone.initTaxClassesTab();
	},

	initTaxClassesTab: function()
	{
		$('.addTaxRateButton').click(function() {
			window.location = 'index.php?ToDo=addTaxRate&tax_zone_id=' + Tax_Zone.id;
		});

		$('.deleteTaxZonesButton').click(function() {
		});
	},

	initTaxRatesTab: function()
	{
		$('#taxRatesGrid .checkAll input').click(function() {
			$(this.form).find('input:checkbox').not(':disabled').attr('checked', this.checked);
		});

		$('#taxRatesTab form').submit(Tax_Zone.deleteSelectedTaxRates);
		$('#taxRatesGrid .deleteTaxRateLink').live('click', Tax_Zone.deleteTaxRate);
		$('#taxRatesGrid .copyTaxRateLink').live('click', Tax_Zone.copyTaxRate);
		$('#taxRatesGrid .toggleTaxRateStatusLink').live('click', Tax_Zone.toggleTaxRateStatus);
	},

	validateForm: function()
	{
		if(!$('#taxZoneForm input[name=name]').val()) {
			alert(lang.TaxZoneMissingName);
			$('#taxZoneForm input[name=name]').focus();
			return false;
		}

		type = $('#taxZoneForm input[name=type]:checked').val();
		if(!type && !defaultZone) {
			alert(lang.TaxZoneMissingType);
			$('#taxZoneForm input[name=type]').focus();
			return false;
		}

		if(type == 'country' && !$('#taxZoneForm .zoneTypeCountry select').val()) {
			alert(lang.TaxZoneSelectOneMoreCountries);
			return false;
		}
		else if(type == 'state' && !$('#taxZoneForm .zoneTypeState .stateSelectSome select').val()) {
			alert(lang.TaxZoneSelectOneMoreStates);
			return false;
		}
		else if(type == 'zip') {
			if(!$('#taxZoneForm .zoneTypeZip select[name=country]').val()) {
				alert(lang.TaxZoneSelectCountry);
				$('#taxZoneForm .zoneTypeZip select[name=country').focus();
				return false;
			}

			if(!$('#taxZoneForm .zoneTypeZip textarea').val()) {
				alert(lang.TaxZoneEnterOneMoreZipCodes);
				$('#taxZoneForm .zoneTypeZip textarea').focus();
				return false;
			}
		}

		if($('#taxZoneForm input[name=applies_to]:checked').val() == 'groups' &&
			!$('#taxZoneForm .zoneGroupSelect select').val()) {
				alert(lang.TaxZoneSelectOneMoreGroups);
				return false;
		}
		return true;
	},

	toggleStateCountry: function()
	{
		options = $('#stateCountrySelect').get(0).options;
		selectedCount = 0;

		for(var i = 0; i < options.length; ++i) {
			option = options[i];
			countryId = option.value;
			if(option.selected == true) {
				if($('#stateSelect .country'+countryId).length == 0) {
					Tax_Zone.loadCountryStates(countryId, option.innerHTML);
				}
				++selectedCount;
			}
			else {
				$('#stateSelect .country'+countryId).remove();
				$('#stateSelect_old .country'+countryId).remove();
			}
		}

		if(selectedCount == 0) {
			$('.stateSelectNone').css({width: $('.stateSelectSome').width(), height: $('.stateSelectSome').height()});
			$('.stateSelectSome').hide();
			$('.stateSelectNone').show();
		}
		else {
			$('.stateSelectSome').show();
			$('.stateSelectNone').hide();
		}
	},

	loadCountryStates: function(countryId, countryName)
	{
		// Load this country in
		$.ajax({
			url: 'remote.php?w=countryStates&c='+countryId,
			method: 'GET',
			success: function(response) {
				var options = '';
				if(response != '') {
					states = response.split("~");
					for(i = 0; i < states.length; i++) {
						vals = states[i].split("|");
						if(states[i].length > 0) {
							options += '<option value="'+countryId+'-'+vals[1]+'">'+vals[0]+'</option>';
						}
					}
				}
				var data = '<option value="'+countryId+'-0">' + lang.AllStates + '</option>' + options;

				if($('#stateSelect_old').length > 0) {
					$('#stateSelect').remove();
					$('#stateSelect_old').attr('id', 'stateSelect');
				}
				$('#stateSelect').append('<optgroup class="country'+countryId+'" label="'+countryName+'">'+data+'</optgroup>');
				$('#stateSelect').css({
					display: 'block'
				});
				ISSelectReplacement.replace_select(document.getElementById('stateSelect'));
				ISSelectReplacement.scrollToItem('zonetype_states', countryId+'-0', 1);
			}
		});
	},

	toggleTaxRateStatus: function()
	{
		link = this;
		taxRateId = link.href.match(/#toggle([0-9]+)/)[1];
		currentStatus = $(link).hasClass('statusToggle1');
		$(link).addClass('statusToggleLoading');
		$.ajax({
			url: 'index.php?ToDo=toggleTaxRateStatus',
			type: 'post',
			data: {
				id: taxRateId,
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
					window.location.reload();
				}
			},
			error: function() {
				$(link).removeClass('statusToggleLoading');
			}
		})

		return false;
	},

	copyTaxRate: function()
	{
		link = this;
		taxRateId = link.href.match(/#copy([0-9]+)/)[1];

		$('<form />')
			.attr('method', 'post')
			.attr('action', 'index.php')
			.append($('<input />')
				.attr('type', 'hidden')
				.attr('name', 'ToDo')
				.attr('value', 'copyTaxRate')
			)
			.append($('<input />')
				.attr('type', 'hidden')
				.attr('name', 'id')
				.attr('value', taxRateId)
			)
			.appendTo('body')
			.submit()
		;
	},

	deleteTaxRate: function()
	{
		$('#taxRatesGrid td.check input').attr('checked', false);
		$(this).parents('tr').find('td.check input').attr('checked', true);
		$('#taxRatesTab form').submit();
		return false;
	},

	deleteSelectedTaxRates: function()
	{
		if($('#taxRatesGrid td.check input:checked').length == 0) {
			alert(lang.SelectTaxRatesToDelete);
			return false;
		}

		if(confirm(lang.ConfirmDeleteTaxRates)) {
			return true;
		}

		return false;
	}
}
$(document).ready(Tax_Zone.init);