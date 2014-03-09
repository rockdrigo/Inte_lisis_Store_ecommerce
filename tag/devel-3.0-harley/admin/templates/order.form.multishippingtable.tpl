{% import "macros/util.tpl" as util %}
{% import "macros/forms.tpl" as forms %}

{% macro itemConfiguration(item) %}
	{% if item.variationOptions|length or item.configuration|length or item.eventName or item.giftWrapping %}
		<div class="nodeJoin quoteItemConfiguration">
			{% for name, value in item.variationOptions %}
				<div class="quoteItemConfigurationRow">
					<div class="name">{{ name }}:</div>
					<div class="value">{{ value }}</div>
				</div>
			{% endfor %}
			{% for field in item.configuration %}
				<div class="quoteItemConfigurationRow">
					<div class="name">{{ field.name }}:</div>
					<div class="value">{{ field.value }}</div>
				</div>
			{% endfor %}
			{% if item.eventName %}
				<div class="quoteItemConfigurationRow">
					<div class="name">{{ item.eventName }}</div>
					<div class="value">{{ item.eventDate(true)|date }}</div>
				</div>
			{% endif %}
			{% if item.giftWrapping %}
				<div class="quoteItemConfigurationRow">
					<div class="name">{% lang 'GiftWrapping' %}:</div>
					<div class="value">{{ item.giftWrapping.wrapname }} ({{ item.giftWrapping.wrapprice|formatPrice }})</div>
				</div>
				{% if item.giftWrapping.wrapmessage %}
					<div class="quoteItemConfigurationRow">
						<div class="name">{% lang 'GiftMessage' %}:</div>
						<div class="value">{{ item.giftWrapping.wrapmessage }}</div>
					</div>
				{% endif %}
			{% endif %}
		</div>
	{% endif %}
{% endmacro %}

{% if unallocatedItems|length %}
	<div class="shippingItemsGrid shippingUnallocatedGrid">
		{{ forms.startForm }}
			{{ forms.heading(lang.SelectProductsToAllocate) }}

			{{ forms.startRow }}
				<table class="gridTable">
					<thead>
						<tr>
							<th class="shippingItemsCheck"><input type="checkbox" /></th>
							<th>{% lang 'Product' %}</th>
							<th class="shippingItemsQuantity">{% lang 'UnallocatedQuantity' %}</th>
							<th class="shippingItemsAction">{% lang 'Action' %}</th>
						</tr>
					</thead>
					<tbody>
						{% for item in unallocatedItems %}
							<tr class="itemRow" id="shippingUnallocated_{{ item.id }}">
								<td class="shippingItemsCheck"><input type="checkbox" name="items[]" value="{{ item.id }}" /></td>
								<td>
									<div class="shippingItemsName">{{ item.name }}</div>
									{{ _self.itemConfiguration(item) }}
								</td>
								<td class="shippingItemsQuantity">
									{{ item.quantity }}
								</td>
								<td class="shippingItemsAction">
									<a href="#" class="selectItemDestinationLink">{% lang 'SelectDestination' %}</a>
								</td>
							</tr>
						{% endfor %}
					</tbody>
				</table>
				<div class="shippingItemsGridControls">
					<img src="images/nodejoin.gif" alt="" />
					<input type="button" class="selectItemsDestinationLink" value="{% lang 'SelectDestinationForProducts' %}" />
				</div>
			{{ forms.endRow }}
		{{ forms.endForm }}
	</div>
{% endif %}

{% if allocatedItems|length %}
	<div class="shippingItemsGrid shippingDestinationGrid">
		{{ forms.startForm }}
			{{ forms.heading(lang.ShippingDestinations) }}

			{{ forms.startRow }}
				<table class="gridTable">
					<thead>
						<tr>
							<th>{% lang 'DestinationAndItems' %}</th>
							<th class="shippingDestinationMethod">{% lang 'Method' %}</th>
							<th class="shippingDestinationCost">{% lang 'ShippingCost' %}</th>
							<th class="shippingDestinationMethodAction">{% lang 'Action' %}</th>
						</tr>
					</thead>
					<tbody>
						{% for shippingAddress in shippingAddresses %}
							{% if shippingAddress.id != 'unallocated' %}
								<tr id="shippingDestination_{{ shippingAddress.id }}" class="shippingDestinationRow">
									<td>
										<div class="shippingDestinationAddressLine">
											{{ shippingAddress.line }}
										</div>
										<ul class="shippingDestinationItemList">
											{% for item in shippingAddress.items %}
												<li>
													{{ item.quantity }} x {{ item.name }}
													{{ _self.itemConfiguration(item) }}
												</li>
											{% endfor %}
										</ul>
									</td>
									<td class="shippingDestinationMethod">{{ shippingAddress.shippingProvider }}</td>
									<td class="shippingDestinationCost">{{ shippingAddress.shippingCost(incTax)|formatPrice }}</td>
									<td class="shippingDestinationMethodAction">
										<a href="#" class="shippingDestinationChangeLink">{% lang 'Change' %}</a>
										<a href="#" class="shippingDestinationDeleteLink">{% lang 'Delete' %}</a>
									</td>
								</tr>
							{% endif %}
						{% endfor %}
					</tbody>
				</table>
			{{ forms.endRow }}
		{{ forms.endForm }}
	</div>
{% endif %}
