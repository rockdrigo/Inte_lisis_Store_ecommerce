{% import "macros/util.tpl" as util %}
{% import "macros/forms.tpl" as forms %}

{{ forms.startForm([
	'type': 'vertical',
	'class': 'orderFormSummaryBillingDetails',
]) }}
	{{ forms.heading('Customer Billing Details') }}
	{{ forms.startRow(['type':'vertical']) }}
		<div class="detailsHeading">{% lang 'BillingTo' %}: <a href="#" class="orderFormChangeBillingDetailsLink">{% lang 'Change' %}</a></div>
		<div class="detailsAddress">
			{{ util.address(address) }}
		</div>
	{{ forms.endRow }}
{{ forms.endForm }}
