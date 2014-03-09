<div id="variationsSupported">
	{% if (categoryOptions.variations_supported and secCatSelectedNotSupportVariations == 0) or (categoryOptions.variations_supported == '1' and secondaryCategoryOptionsData.variations_supported == '1') %}
		<img src="images/tick.gif" /> <span>{{ lang.EbayProductsWithVariationsAllowed }}</span>
	{% else %}
		<img src="images/cross.gif" /> <span>{{ lang.EbayProductsWithVariationsNotAllowed }}</span>
	{% endif %}
</div>
<div>
	{% if ((categoryOptions.variations_supported and secCatSelectedNotSupportVariations == 0) or (categoryOptions.variations_supported == '1' and secondaryCategoryOptionsData.variations_supported == '1')) and sellingMethod != 'FixedPriceItem' %}
		<img src="images/info.gif" /> <span>{{ lang.EbayProductsWithVariationsSellingMethodNotAllowed }}</span>
	{% endif %}
</div>
<div>
	{% if categoryOptions.reserve_price_allowed %}
		<img src="images/tax.gif" /> {% lang 'EbayReservePriceAllowed' with ['minimumReserve': categoryOptions.minimum_reserve_price|formatPrice(false, true, false, currency)] %}
	{% else %}
		<img src="images/cross.gif" /> {{ lang.EbayReservePriceNotAllowed }}
	{% endif %}
</div>
{% if categoryOptions.paypal_required %}
	<div>
		<img src="images/payment.gif" /> {{ lang.EbayPayPalRequiredMethod }}
	</div>
{% endif %}
{% if categoryOptions.return_policy_supported %}
	<div>
		<img src="images/return.gif" /> {{ lang.EbayReturnPolicyAllowed }}
	</div>
{% endif %}
{% if categoryOptions.lot_size_enabled %}
	<div>
		<img src="images/product.gif" /> {{ lang.EbayLotSizeAllowed }}
	</div>
{% endif %}
{% if message %}
	<br />
	<br />
	<div class="MessageBox MessageBoxInfo">
		{{ message|safe }}
	</div>
{% endif %}
