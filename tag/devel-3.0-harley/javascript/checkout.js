var Checkout = {
	ValidateNewAddress: function()
	{
		if(document.getElementById('account_email')) {
			if($('#account_email').val().indexOf('@') == -1 || $('#account_email').val().indexOf('.') == -1) {
				alert(lang.LoginEnterValidEmail);
				$('#account_email').focus();
				$('#account_email').select();
				return false;
			}
		}

		var requiredFields = {
			'#ship_firstname': lang.EnterShippingFirstName,
			'#ship_lastname': lang.EnterShippingLastName,
			'#ship_phone': lang.EnterShippingPhone,
			'#ship_address1': lang.EnterShippingAddress,
			'#ship_city': lang.EnterShippingCity,
			'#ship_country': lang.ChooseShippingCountry,
			'#ship_state': lang.ChooseShippingState,
			'#ship_zip': lang.EnterShippingZip
		};

		var hasErrors = false;
		for(field in requiredFields) {
			message = requiredFields[field];
			if($(field).css('display') != 'none' && (!$(field).val() || $(field).val() == -1 || $(field).val() == 0)) {
				alert(message);
				$(field).focus();
				hasErrors = true;
				return false;
			}
		}

		if(hasErrors == true) {
			return false;
		}
		else {
			return true;
		}
	},

	ToggleCountry: function() {
		var countryId = $('#ship_country').val();
		$.ajax({
			url: 'remote.php',
			type: 'post',
			data: 'w=countryStates&c='+countryId,
			success: function(data)
			{
				$('#ship_state option:gt(0)').remove();
				var states = data.split('~');
				var numStates = 0;
				for(var i =0; i < states.length; ++i) {
					vals = states[i].split('|');
					if(!vals[0]) {
						continue;
					}
					$('#ship_state').append('<option value="'+vals[1]+'">'+vals[0]+'</option>');
					++numStates;
				}

				if(numStates == 0) {
					$('#ship_state').hide();
					$('#ship_state_1').show();
				}
				else {
					$('#ship_state').show();
					$('#ship_state_1').hide();
				}
				$('#ship_state').val('0');
			}
		});
	},

	ChooseShippingProvider: function()
	{
		// A shipping provider hasn't been selected
		var shippingValid = true;
		$('.ShippingProviderList').each(function() {
			if(!$(this).find('input[type=radio]:checked').val()) {
				alert(lang.PleaseChooseShippingProvider);
				$(this).find('input[type=radio]').get(0).focus();
				shippingValid = false;
				return false;
			}
		});

		if(shippingValid == false) {
			return false;
		}
	},

	MultiAddNewAddress: function(type)
	{
		var input;
		if (type !== undefined && type !== '') {
			if (type !== 'billing') {
				type = 'shipping';
			}
			input = $('<input type="hidden" name="address_type" />');
			input.val(type);
			$('#multiAddressForm').append(input);
		}
		input = $('<input type="hidden" name="addAnotherAddress" value="1" />');
		$('#multiAddressForm').append(input).submit();
		return false;
	}
};
