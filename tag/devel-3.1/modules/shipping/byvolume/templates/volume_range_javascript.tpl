var volumeOk = true;

$('.VolumeRanges:first input:first').addClass('FirstVolume');
$('.VolumeRanges:last input:last').prev().addClass('LastVolume');


if ($('.VolumeRanges input.VolumeRange').length > 3) {
	$('.VolumeRanges input.VolumeRange').each(function() {
		if ($(this).hasClass('FirstVolume') || $(this).hasClass('LastVolume')) {
			return true;
		}

		if (isNaN(priceFormat($(this).val())) || $(this).val() == "") {

			if ($(this).hasClass('RangeCost')) {
				alert('{% lang 'JsEnterAShippingCost' %}');
			}

			$(this).focus();
			volumeOk = false;
			return false;
		}
	});
} else {
	var cost = $('.VolumeRanges input.RangeCost').val();
	var lower = $('.VolumeRanges input.LowerRange').val();
	var upper = $('.VolumeRanges input.UpperRange').val();

	if (isNaN(priceFormat(cost)) || cost == "" ) {
		alert('{% lang 'JsEnterAShippingCost' %}');
		$('.VolumeRanges input.RangeCost').focus();
		volumeOk = false;
	} else if ((isNaN(priceFormat(lower)) || lower == "") && (isNaN(priceFormat(upper)) || upper == "")) {
		alert('{% lang 'JsShippingCostRuleRequired' %}');
		volumeOk = false;
	}

}

if (volumeOk == false) {
	$('.VolumeRanges:first input:first').removeClass('FirstVolume');
	$('.VolumeRanges:last input:last').prev().removeClass('LastVolume');
	return false;
}


