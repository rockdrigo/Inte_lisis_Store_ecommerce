var weightOk = true;

$('.WeightRanges:first input:first').addClass('FirstWeight');
$('.WeightRanges:last input:last').prev().addClass('LastWeight');


if ($('.WeightRanges input.WeightRange').length > 3) {
	$('.WeightRanges input.WeightRange').each(function() {
		if ($(this).hasClass('FirstWeight') || $(this).hasClass('LastWeight')) {
			return true;
		}

		if (isNaN(priceFormat($(this).val())) || $(this).val() == "") {

			if ($(this).hasClass('RangeCost')) {
				alert('{% lang 'JsEnterAShippingCost' %}');
			}

			$(this).focus();
			weightOk = false;
			return false;
		}
	});
} else {
	var cost = $('.WeightRanges input.RangeCost').val();
	var lower = $('.WeightRanges input.LowerRange').val();
	var upper = $('.WeightRanges input.UpperRange').val();

	if (isNaN(priceFormat(cost)) || cost == "" ) {
		alert('{% lang 'JsEnterAShippingCost' %}');
		$('.WeightRanges input.RangeCost').focus();
		weightOk = false;
	} else if ((isNaN(priceFormat(lower)) || lower == "") && (isNaN(priceFormat(upper)) || upper == "")) {
		alert('{% lang 'JsShippingCostRuleRequired' %}');
		weightOk = false;
	}

}

if (weightOk == false) {
	$('.WeightRanges:first input:first').removeClass('FirstWeight');
	$('.WegihtRanges:last input:last').prev().removeClass('LastWeight');
	return false;
}


