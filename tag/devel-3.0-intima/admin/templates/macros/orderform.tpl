{# macros which are used primarily by the add/ed order control panel UI #}

{% macro beginQuoteItemsGrid (quote, config, options) %}
<div class="quoteItemsGrid {% if options.interactive|default(true) %}quoteItemsGridInteractive{% else %}quoteItemsGridNonInteractive{% endif %}">
	<table class="gridTable">
{% endmacro %}

{% macro endQuoteItemsGrid (quote, config, options) %}
	</table>
</div>
{% endmacro %}

{% macro quoteItemsGrid (quote, config, options) %}
	{{ _self.beginQuoteItemsGrid(quote, config, options) }}
		{% if options.addresses %}{% set addresses = options.addresses %}{% else %}{% set addresses = quote.getAllAddresses %}{% endif %}
		{% for address in addresses %}
			{% if address.items|length %}
				<tbody class="itemHeading gridTableHead">
					<tr>
						<th colspan="2">
							{% if address.type == 'billing' %}
								{% lang 'DigitalOrGift' %}
							{% elseif address.isUnallocated %}
								{% lang 'UnallocatedProducts' %}
							{% elseif address.isComplete %}
								{% lang 'ProductsShippedTo' with [
									'address': address.line
								] %}
							{% else %}
								{% lang 'Products' %}
							{% endif %}
						</th>
						<th class="quoteItemQuantity">{% lang 'Quantity' %}</th>
						<th class="quoteItemPrice">{% lang 'ItemPrice' %}</th>
						<th class="quoteItemTotal">{% lang 'ItemTotal' %}</th>
						{% if options.interactive|default(true) %}
							<th class="quoteItemAction">{% lang 'Action' %}</th>
						{% endif %}
					</tr>
				</tbody>
				<tbody class="itemRows">
					{% for item in address.items %}
						{{ _self.quoteItemGridItem(item, config, options) }}
					{% endfor %}
				</tbody>
			{% endif %}
		{% endfor %}
	{{ _self.endQuoteItemsGrid(quote, config, options) }}
{% endmacro %}

{% macro quoteItemGridItem (item, config, options) %}
{% set interactive = options.interactive|default(true) %}
<tr class="itemRow" id="itemId_{{ item.id }}">
	<td class="quoteItemImage">
		{% if item.thumbnail %}
			<img src="{{ item.thumbnail }}" alt="" />
		{% else %}
			&nbsp;
		{% endif %}
	</td>
	<td>
		<div class="quoteItemName">{{ item.name }}</div>
		{% if item.sku %}
			<div class="quoteItemSku">{{ item.sku }}</div>
		{% endif %}
		<div class="quoteItemConfiguration">
			{% for name, value in item.variationOptions %}
				<div class="quoteItemConfigurationRow">
					<div class="name">{{ name }}:</div>
					<div class="value">{{ value }}</div>
				</div>
			{% endfor %}
			{% for field in item.configuration %}
				<div class="quoteItemConfigurationRow">
					{% if field.value %}
						<div class="name">{{ field.name }}:</div>
						<div class="value">
							{% if field.type == "file" %}
								{{ field.fileOriginalName }}
							{% elseif field.type == "checkbox" %}
								{% if field.value == 1 %}{% lang 'xYes' %}{% else %}{% lang 'xNo' %}{% endif %}
							{% else %}
								{{ field.value }}
							{% endif %}
						</div>
					{% endif %}
				</div>
			{% endfor %}
			{% if item.eventName %}
				<div class="quoteItemConfigurationRow">
					<div class="name">{{ item.eventName }}:</div>
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
			{% if item.isPreOrder %}
				<div class="quoteItemConfigurationRow">
					<div class="name">{% lang 'PreOrder' %}:</div>
					<div class="value">{{ item.getPreOrderMessage }}</div>
				</div>
			{% endif %}
		</div>
	</td>
	<td class="quoteItemQuantity">
		{% if interactive and not item.isGiftCertificate %}
			<input type="text" name="quantity" value="{{ item.quantity }}" class="Field50" id="qty" />
		{% else %}
			{{ item.quantity }}
		{% endif %}
	</td>
	<td class="quoteItemPrice">
		{% if interactive and not item.isGiftCertificate %}
			{% if config.CurrencyLocation == 'left' %}{{ config.CurrencyToken }}{% endif %}
			<input type="text" name="price" value="{{ item.price(item.quote.doesStoreCartDisplayIncludeTax)|formatPrice(false, false) }}" class="Field50" />
			{% if config.CurrencyLocation == 'right' %}{{ config.CurrencyToken }}{% endif %}
		{% else %}
			{{ item.price(item.quote.doesStoreCartDisplayIncludeTax)|formatPrice }}
		{% endif %}
	</td>
	<td class="quoteItemTotal"><span>{{ item.total(item.quote.doesStoreCartDisplayIncludeTax)|formatPrice }}</span></td>
	{% if interactive %}
		<td class="quoteItemAction">
			{% if not item.isGiftCertificate %}
				<a href="#" class="customizeItemLink">{% lang 'Customize' %}</a>
				<a href="#" class="copyItemLink">{% lang 'Copy' %}</a>
			{% else %}
				<span class="Disabled">{% lang 'Customize' %}</span>
				<span class="Disabled">{% lang 'Copy' %}</span>
			{% endif %}
			<a href="#" class="deleteItemLink">{% lang 'Delete' %}</a>
		</td>
	{% endif %}
</tr>
{% endmacro %}

{% macro addressDetails (address, options) %}
	<div class="addressDetails" style="{% if address.countryFlag %}background-image: url('../lib/flags/{{ address.countryFlag }}.gif');{% endif %}">
		<a href="#" class="useExistingAddress">{% lang 'UseThisAddress' %}</a>
		<strong>{{ address.FirstName }} {{ address.LastName }}</strong>
		<div>{{ address.CompanyName }}</div>
		<div>{{ address.AddressLine1 }}</div>
		<div>{{ address.AddressLine2 }}</div>
		<div>
			{{ address.City }}{% if address.State %}{% if address.City %},{% endif %} {{ address.State }}{% endif %}{% if address.Zip %}{% if address.City or address.State %},{% endif %} {{ address.Zip }}{% endif %}
		</div>
		<div>{{ address.Country }}</div>
	</div>
{% endmacro %}

{% macro paymentModuleForm(module) %}
	{% import "macros/forms.tpl" as formBuilder %}

	<div id="paymentMethodForm_{{ module.id }}" class="paymentMethodForm">
		{% for fieldName, fieldData in module.formFields %}
			{% set inputName = 'paymentField[' ~ module.id ~ '][' ~ fieldName ~ ']' %}
			{% if fieldData.class %}
				{% set fieldClass = fieldData.class %}
			{% else %}
				{% set fieldClass = 'Field200' %}
			{% endif %}

			{{ formBuilder.startRow(['label': fieldData.title ~ ':', 'required': fieldData.required, 'class': 'Field_' ~ fieldName]) }}
				{% if fieldData.type == 'text' %}
					{{ formBuilder.input('text', inputName, fieldData.value, ['class': fieldClass]) }}
				{% elseif fieldData.type == 'select' %}
					<select name="{{ inputName }}" onchange="{{ fieldData.onchange|safe }};" class="{{ fieldClass }}">
						{{ fieldData.options|safe }}
					</select>
				{% elseif fieldData.type == 'html' %}
					{{ fieldData.html|safe }}
				{% endif %}
			{{ formBuilder.endRow }}
		{% endfor %}
	</div>
{% endmacro %}
