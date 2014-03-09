$(function()
{
	$('input[name=coupontype]').live('change', function(event){
		$('.offCurrency').hide();
		$('.offPercentage').hide();
		$('.discountAmount').show();

		var value = $(this).val();
		var couponTypeLang = {
			0:{'appendLang':lang.OffEachItem, 'className':'offCurrency'},
			1:{'appendLang':lang.OffEachItem, 'className':'offPercentage'},
			2:{'appendLang':lang.OffTheTotal, 'className':'offCurrency'},
			3:{'appendLang':lang.OffTheShipping, 'className':'offCurrency'},
			4:{'appendLang':'', 'className':'discountAmount'}
		}
		if (value == 4) {
			$('.' + couponTypeLang[value]['className']).hide();
		} else {
			$('.' + couponTypeLang[value]['className']).show();
			$('#discountAmountDesc').html(couponTypeLang[value]['appendLang']);
			$('#discountAmountDesc').show();
		}
	});
	$('input[name=coupontype]:checked').trigger('change');

	$('input[name=LocationType]').live('change', function(event){
		// hide all sub options.
		$(".OptionLocationTypeCountry").hide();
		$(".OptionLocationTypeState").hide();
		$(".OptionLocationTypeZip").hide();

		// show only the sub options of selected option
		$(".Option" + $(this).attr('id')).show();
	});

	$('.LearnMoreAboutEnteringPostCodes').click(function(event){
		event.preventDefault();
		LaunchHelp('850');
	});

	// "Number of Uses" toggle
	$("#CouponMaxUsesEnabled").change(function() {
		if($(this).is(':checked')) {
			$('#CouponMaxUsesNode').show();
		}
		else {
			$('#CouponMaxUsesNode').hide();
			$('#CouponMaxUsesNode #couponmaxuses').val(0);
		}
	});
	$("#CouponMaxUsesPerCustomerEnabled").change(function() {
		if($(this).is(':checked')) {
			$('#CouponMaxUsesPerCustomerNode').show();
		}
		else {
			$('#CouponMaxUsesPerCustomerNode').hide();
			$('#CouponMaxUsesPerCustomerNode #couponmaxusespercus').val(0);
		}
	});

});

function updateLocationTypeStatesSelect()
{
	var options = document.getElementById('LocationTypeStatesCountries[]').options;
	var selectedCount = 0;
	for(var i = 0; i < options.length; ++i) {
		var option = options[i];
		var countryId = option.value;
		if(option.selected == true) {
			if($('#LocationTypeStatesSelect .country'+countryId).length == 0) {
				LoadCountryStates(countryId, option.innerHTML);
			}
			++selectedCount;
		}
		else {
			$('#LocationTypeStatesSelect .country'+countryId).remove();
			$('#LocationTypeStatesSelect_old .country'+countryId).remove();
		}
	}

	if(selectedCount == 0) {
		$('#LocationTypeStatesSelect').hide();
		$('#LocationStateSelectNone').show();
	}
	else {
		$('#LocationTypeStatesSelect').show();
		$('#LocationStateSelectNone').hide();
	}
}

function LoadCountryStates(countryId, countryName) {
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
			var data = '<option value="'+countryId+'-0">-- ' + lang.AllStatesProvinces + ' --</option>' + options;
			if(document.getElementById('LocationTypeStatesSelect_old')) {
				$('#LocationTypeStatesSelect').remove();
				$('#LocationTypeStatesSelect_old').attr('id', 'LocationTypeStatesSelect');
			}
			$('#LocationTypeStatesSelect').append('<optgroup class="country'+countryId+'" label="'+countryName+'">'+data+'</optgroup>');
			$('#LocationTypeStatesSelect').css({display: 'block'});
			$('#LocationTypeStatesSelectHolder').css({display: 'block'});
			ISSelectReplacement.replace_select(document.getElementById('LocationTypeStatesSelect'));
			ISSelectReplacement.scrollToItem('zonetype_states', countryId+'-0', 1);
		}
	});
}

function confirmCancel()
{
	if(confirm(lang.ConfirmCancelCoupon))
		document.location.href = "index.php?ToDo=viewCoupons";
}

function CheckCouponForm()
{
	if ($('#couponcode').val() == '') {
		alert(lang.EnterCouponCode);
		$("#tab0").click();
		$('#couponcode').focus();
		return false;
	}

	if ($('#couponname').val() == '') {
		alert(lang.EnterCouponName);
		$("#tab0").click();
		$('#couponname').focus();
		return false;
	}

	// note: no need to check amount if coupon type is free-shipping (4)
	var couponType = $('input[name=coupontype]:checked').val();
	var amount = $('#couponamount').val();
	if (couponType != 4 && (amount == '' || isNaN(amount) || parseInt(amount) <= 0)) {
		alert(lang.EnterValidAmount);
		$("#tab0").click();
		$('#couponamount').focus().select();
		return false;
	}

	// coupon applies to 'categories'/'product'
	if ($('#usedforcatdiv').css('display') != 'none') {
		if ($("#catids").get(0).selectedIndex == -1) {
			alert(lang.ChooseCouponCategory);
			$("#tab0").click();
			$('#catids').focus();
			return false;
		}
	} else if ($('#usedforproddiv').css('display') != 'none') {
		var prodids = document.getElementById("prodids");
		if($('#prodids').val() == '') {
			alert(lang.EnterCouponProductId);
			$("#tab0").click();
			$('#ProductSelect').focus();
			return false;
		}
	}

	// -- optional fields check start here --

	var minPurchase = $('#couponminpurchase').val().replace(',', '');
	if (minPurchase != '' && (isNaN(minPurchase) || parseInt(minPurchase) < 0)) {
		alert(lang.EnterValidMinPrice);
		$("#tab0").click();
		$('#couponminpurchase').focus().select();
		return false;
	}

	// copy expiry date across to hidden field
	$('#couponexpires').val($('#dc1').val());

	var maxUses = $('#couponmaxuses').val();
	if (maxUses != '' && (isNaN(maxUses) || parseInt(maxUses) < 0)) {
		alert(lang.EnterValidMaxUses);
		$("#tab0").click();
		$('#couponmaxuses').focus().select();
		return false;
	}

	var maxUsesPerCus = $('#couponmaxusespercus').val();
	if (maxUsesPerCus != '' && (isNaN(maxUsesPerCus) || parseInt(maxUsesPerCus) < 0)) {
		alert(lang.EnterValidMaxUsesPerCus);
		$("#tab0").click();
		$('#couponmaxusespercus').focus().select();
		return false;
	}

	// Validation of Shipping Location Restriction
	if ($("#YesLimitByLocation").attr('checked')) {
		if (!$("input[name=LocationType]:checked").length) {
			alert (lang.EnterLocationOption);
			$("#tab1").click();
			$("input[name=LocationType]").focus();
			return false;
		}

		if ($("input[name=LocationType]:checked").val() == 'country') {
			var locationTypeCountries = document.getElementById("LocationTypeCountries[]");
			if(locationTypeCountries.selectedIndex == -1) {
				alert(lang.EnterLocationTypeCountries);
				$("#tab1").click();
				locationTypeCountries.focus();
				return false;
			}
		}
		else if ($("input[name=LocationType]:checked").val() == 'state') {
			var locationTypeStatesCountries = document.getElementById("LocationTypeStatesCountries[]");
			if(locationTypeStatesCountries.selectedIndex == -1) {
				alert(lang.EnterLocationTypeStatesCountries);
				$("#tab1").click();
				locationTypeStatesCountries.focus();
				return false;
			}
			var locationTypeStatesSelect = document.getElementById("LocationTypeStatesSelect");
			if(locationTypeStatesSelect.selectedIndex == -1) {
				alert(lang.EnterLocationTypeStatesSelect);
				$("#tab1").click();
				locationTypeStatesSelect.focus();
				return false;
			}
		}
		else if ($("input[name=LocationType]:checked").val() == 'zip') {
			if (!$("#LocationTypeZipCountry").val()) {
				alert(lang.EnterLocationTypeZipCountry);
				$("#tab1").click();
				$("#LocationTypeZipCountry").focus();
				return false;
			}
			if (!$.trim($("#LocationTypeZipPostCodes").val())) {
				alert(lang.EnterLocationTypeZipPostCodes);
				$("#tab1").click();
				$("#LocationTypeZipPostCodes").focus();
				return false;
			}
		} else {
			alert (lang.EnterLocationOption);
			$("#tab1").click();
			$("input[name=LocationType]").focus();
			return false;
		}
	}

	// Validation of Shipping Methods Restriction
	if ($("#YesLimitByShipping").attr('checked')) {
		var locationTypeShipping = document.getElementById("LocationTypeShipping[]");
		if(locationTypeShipping.selectedIndex == -1) {
			alert(lang.EnterLocationTypeShipping);
			$("#tab1").click();
			locationTypeShipping.focus();
			return false;
		}
	}

	// Everything is OK
	return true;
}

function ToggleUsedFor(Which) {
	var usedforcatdiv = document.getElementById("usedforcatdiv");
	var usedforproddiv = document.getElementById("usedforproddiv");
	var usedforcat = document.getElementById("usedforcat");
	var usedforprod = document.getElementById("usedforprod");

	if(Which == 0) {
		usedforcat.checked = true;
		usedforcatdiv.style.display = "";
		usedforproddiv.style.display = "none";
	}
	else {
		usedforprod.checked = true;
		usedforcatdiv.style.display = "none";
		usedforproddiv.style.display = "";
	}
}