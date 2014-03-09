{% import "macros/util.tpl" as util %}
{% import "macros/forms.tpl" as forms %}
{% import "macros/orderform.tpl" as orderform %}

{% set itemCounter = 0 %}

{% for address in quote.getAllAddresses %}
	{% if address.getItems|length %}
		{{ forms.startForm([
			'type': 'vertical',
			'class': 'orderFormSummaryShippingDetails',
		]) }}

			{% set heading %}
				{% lang 'ShippingDetails_' ~ address.type %}: {% lang 'ItemsXtoYofZ' with [
					'x': itemCounter + 1,
					'y': itemCounter + address.getItemCount,
					'z': quote.getItemCount,
				] %}
			{% endset %}

			{{ forms.heading(heading) }}

			{{ forms.startRow(['type':'vertical']) }}
				{% if address.type == 'shipping' %}
					<div class="detailsHeading">{% lang 'ShippingTo' %}: <a href="#" class="orderFormChangeShippingDetailsLink">{% lang 'Change' %}</a></div>

					<div class="detailsAddress">
						{{ util.address(address) }}
					</div>

					<div class="detailsHeading">{% lang 'ShippingMethod' %}: <a href="#" class="orderFormChangeShippingDetailsLink">{% lang 'Change' %}</a></div>

					<div class="detailsShippingMethod">
						{% set shippingMethod = address.shippingMethod %}
						{% if not shippingMethod.description and not shippingMethod.price %}
							{% lang 'xNone' %}
						{% else %}
							{{ address.shippingMethod.description }}: {{ address.shippingMethod.price|formatPrice }}
						{% endif %}
					</div>
				{% else %}
					<div class="detailsAddress">
						<div class="MessageBox MessageBoxInfo">{% lang 'DigitalItemsNotice' %}</div>
					</div>
				{% endif %}

				{{ orderform.quoteItemsGrid(quote, config, [
					'addresses': [address],
					'interactive': false,
					'headerLang': 'ShippingDetailsColon',
					'headerItemCountLang': 'ItemsXtoYofZ',
				]) }}

			{{ forms.endRow }}
		{{ forms.endForm }}

		{% set itemCounter = itemCounter + address.getItemCount %}
	{% endif %}
{% endfor %}
