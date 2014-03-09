var totalOk = true;

$('.TotalRanges:first input:first').addClass('FirstTotal');
$('.TotalRanges:last input:last').prev().addClass('LastTotal');


if ($('.TotalRanges input.TotalRange').length > 3) {
	$('.TotalRanges input.TotalRange').each(function() {
		if ($(this).hasClass('FirstTotal') || $(this).hasClass('LastTotal')) {
			return true;
		}

		if (isNaN(priceFormat($(this).val())) || $(this).val() == "") {

			if ($(this).hasClass('RangeCost')) {
				alert('{% lang 'JsEnterAShippingCost' %}');
				$(this).focus();
			}

			$(this).focus();
			totalOk = false;
			return false;
		}
	});
} else {
	var cost = $('.TotalRanges input.RangeCost').val();
	var lower = $('.TotalRanges input.LowerRange').val();
	var upper = $('.TotalRanges input.UpperRange').val();

	if (isNaN(priceFormat(cost)) || cost == "" ) {
		alert('{% lang 'JsEnterAShippingCost' %}');
		$('.TotalRanges input.RangeCost').focus();
		totalOk = false;
	} else if ((isNaN(priceFormat(lower)) || lower == "") && (isNaN(priceFormat(upper)) || upper == "")) {
		alert('{% lang 'JsShippingCostRuleRequired' %}');
		totalOk = false;
	}

}

if (totalOk == false) {
	$('.TotalRanges:first input:first').removeClass('FirstTotal');
	$('.TotalRanges:last input:last').prev().removeClass('LastTotal');
	return false;
}


