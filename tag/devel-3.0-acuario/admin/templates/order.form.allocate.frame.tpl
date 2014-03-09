{% extends 'layouts/iframe.tpl' %}

{% block body %}
{% import 'macros/forms.tpl' as forms %}
{% import 'macros/util.tpl' as util %}
{% import 'macros/orderform.tpl' as orderform %}

<form action="#" method="post" accept-charset="utf-8" id="orderForm">

<input type="hidden" name="quoteSession" value="{{ quoteSession }}" id="quoteSession" />

<div id="content" class="orderFormAllocateFrame orderMachineStateShipping" style="display:block;">
	{% if address %}
		{# when changing an already allocated address #}
		<input type="hidden" name="addressId" value="{{ address.id }}" id="addressId" />
	{% endif %}

	{% set shippingMethod = address.shippingMethod %}
	{% if shippingMethod %}
		<input type="hidden" name="currentShipping[isCustom]" value="{{ shippingMethod.isCustom }}" />
		<input type="hidden" name="currentShipping[module]" value="{{ shippingMethod.module }}" />
		<input type="hidden" name="currentShipping[description]" value="{{ shippingMethod.description }}" />
		<input type="hidden" name="currentShipping[price]" value="{{ shippingMethod.price|formatPrice(false, false) }}" />
	{% endif %}

	{# @hack using orderMachineStateShipping to satisfy selectors in order.form.js - because of that, need display:block because it's display:none in CSS (which should NOT be done but it's too late to change right now) #}
	<div class="shippingItemsGrid">
		{{ forms.startForm }}
			{{ forms.heading('Selected Items') }}
			{{ forms.startRow([
				'last': true,
			]) }}
				<table class="gridTable">
					<thead>
						<tr>
							<th>Product</th>
							<th class="shippingItemsQuantity">Quantity to Ship</th>
						</tr>
					</thead>
					<tbody>
						{% for item in items %}
							<tr class="itemRow">
								<td>
									<div class="shippingItemsName">{{ item.name }}</div>
									{{ self.itemConfiguration(item) }}
								</td>
								<td class="shippingItemsQuantity">
									<select name="quantity[{{ item.id }}]">
										{% if item.address.isUnallocated %}
											{% set maxQty = item.quantity %}
										{% else %}
											{% set maxQty = item.quantity + item.unallocatedQuantity %}
										{% endif %}
										{% for quantity in 0..maxQty %}
											<option value="{{ quantity }}" {% if quantity == item.quantity %}selected="selected"{% endif %}>{{ quantity }}</option>
										{% endfor %}
									</select>
								</td>
							</tr>
						{% endfor %}
					</tbody>
				</table>
			{{ forms.endRow }}
		{{ forms.endForm }}
	</div>

	{{ forms.startForm }}
		{{ forms.heading('Shipping Address') }}

		<fieldset class="existingAddressList">
			<legend>Use Existing Address</legend>
			<ul>
			</ul>
		</fieldset>

		{% for formField in shippingFormFields %}
			{{ formField.loadForFrontend|safe }}
		{% endfor %}

		{{ forms.startRow([
			'label': ' ',
			'last': true,
		]) }}
			<label>
				<input type="checkbox" name="saveShippingAddress" value="1" checked="checked" />
				Save to customer's address book
			</label>
		{{ forms.endRow }}

	{{ forms.endForm }}

	{{ forms.startForm }}
		{{ forms.heading('Shipping Method') }}

		{{ forms.startRow([
			'label': 'Choose a Provider:',
		]) }}
			<select name="shippingQuoteList" class="Field300 showByValue shippingQuoteList" size="5">
				{% if address and address.shippingMethod and address.shippingMethod.module != 'custom' %}
					<option value="builtin:current" selected="selected">{% lang 'UseCurrentShippingMethod' with [
						'method': address.shippingMethod.description,
						'price': address.shippingMethod.price|formatPrice
					] %}</option>
				{% endif %}
				<option value="builtin:none">{{ lang.xNone }}</option>
				<option value="builtin:custom" {% if address and address.shippingMethod and address.shippingMethod.module == 'custom' %}selected="selected"{% endif %}>{{ lang.Custom }}</option>
			</select>
			<a href="#" class="fetchSplitShippingQuotesLink">Fetch Shipping Quotes</a>
			<div class="nodeJoin showByValue_shippingQuoteList showByValue_shippingQuoteList_builtincustom" style="display:none;">
				{{ forms.startForm }}
					{{ forms.startRow([
						'label': lang.ShippingMethod ~ ':',
						'required': true,
					]) }}
						{{ forms.input('text', 'customShippingDescription', shippingMethod.description, [
							'class': 'Field300',
						]) }}
					{{ forms.endRow }}

					{{ forms.startRow([
						'label': lang.ShippingCost ~ ':',
					]) }}
						$ {{ forms.input('text', 'customShippingPrice', shippingMethod.price|formatPrice(false, false), [
							'class': 'Field100',
						]) }}
					{{ forms.endRow }}
				{{ forms.endForm }}
			</div>
		{{ forms.endRow }}

		{{ forms.startRow([
			'label': 'Shipping Method:',
			'hidden': true,
			'class': 'toggleSingleCustomShippingMethod'
		]) }}
			<select name="singleShippingModule" class="Field150">
				<option value="">{{ lang.NA }}</option>
				{% for id, name in shippingModules %}
					<option value="{{ id }}">{{ name }}</option>
				{% endfor %}
			</select>
			<input type="text" name="singleShippingMethod" class="Field150" />
		{{ forms.endRow }}

		{{ forms.startRow([
			'label': 'Shipping Cost:',
			'hidden': true,
			'class': 'toggleSingleCustomShippingMethod'
		]) }}
			{{ CurrencyTokenLeft }}
			<input type="text" name="singleShippingCost" class="Field50" />
			{{ CurrencyTokenRight }}
		{{ forms.endRow }}
	{{ forms.endForm }}
</div>

<script language="javascript" type="text/javascript">//<![CDATA[
	{# this set is copied directly from order.form.tpl -- may not need all of them so look into reducing it sometime #}
	{{ util.jslang([
		'ChooseVariationBeforeAdding',
		'EnterProductRequiredFields',
		'ChooseValidProductFieldFile',
		'AddingProductToOrder',
		'UpdatingProductInOrder',
		'OrderCustomerSearchNone',
		'CustomerPasswordConfirmError',
		'CustomerEmailInvalid',
		'ConfirmRemoveProductFromOrder',
		'InvalidPaymentModule',
		'NoShippingMethodsAreAvailable_1',
		'NoShippingMethodsAreAvailable_2',
		'PleaseAddOneOrMoreItems',
		'ConfirmCancelMessage',
		'AddEditOrderConfirmPageNavigation',
		'ViewOrderHistory',
		'TypeACustomerNameEmailEtc',
		'PleaseSearchForACustomer',
		'UseThisAddress',
		'TypeAProductNameSkuEtc',
		'ChooseOneItemForShippingDestination',
		'AllocateProducts',
		'Cancel',
		'SaveChanges',
		'ConfirmDeleteShippingDestination',
	]) }}

	{{ formFieldJavascript|safe }}

	orderCustomFormFieldsAccountFormId = {{ formFieldTypes.accountFormFields }}
	orderCustomFormFieldsBillingFormId = {{ formFieldTypes.billingFormFields }};
	orderCustomFormFieldsShippingFormId = {{ formFieldTypes.shippingFormFields }};

	(function($){
		$(function(){
			{% if addresses %}
				Order_Form.populateExistingAddresses({{ addresses|json }});
			{% endif %}
		});
	})(jQuery);
//]]></script>

</form>

{% endblock %}
