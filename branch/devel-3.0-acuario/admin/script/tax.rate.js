var Tax_Rate = {
	init: function()
	{
		$('.cancelLink').click(function() {
			if(confirm(lang.ConfirmCancel)) {
				taxZoneId = $('#taxRateForm input[name=tax_zone_id]').val();
				window.location = 'index.php?ToDo=editTaxZone&id=' + taxZoneId + '#taxRatesTab';
			}
		});

		$('.priorityHelpLink').click(function() {
			LaunchHelp(904);
			return false;
		});

		$('#taxRateForm').submit(Tax_Rate.validateForm);
	},

	validateForm: function()
	{
		if(!$('#taxRateForm input[name=name]').val()) {
			alert(lang.TaxRateMissingName);
			$('#taxRateForm input[name=name]').focus();
			return false;
		}

		valid = true;
		$('#taxRateForm .taxClassRate input').each(function() {
			if(isNaN($(this).val()) || $(this).val() > 100 || $(this).val() < 0) {
				alert(lang.InvalidTaxRateClassRate);
				$(this).focus().select();
				valid = false;
				return false;
			}
		})

		if(valid == false) {
			return false;
		}

		if(isNaN($('#taxRateForm input[name=priority]').val())) {
			alert(lang.InvalidTaxRatePriority);
			$('#taxRateForm input[name=priority]').focus().select();
			return false;
		}
		return true;
	}
};
$(document).ready(Tax_Rate.init);