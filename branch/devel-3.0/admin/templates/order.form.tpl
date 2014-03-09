{% import "macros/util.tpl" as util %}
{% import "macros/forms.tpl" as formBuilder %}
<link rel="stylesheet" href="Styles/order.form.css" type="text/css" charset="utf-8">
<div id="content">
	<form action="#" method="post" accept-charset="utf-8" id="orderForm">
		<input type="hidden" name="quoteSession" value="{{ quoteSession }}" id="quoteSession">
		<h1>
			{% if addingOrder %}
				{% lang 'AddAnOrder' %}
			{% else %}
				{% lang 'EditAnOrder' %}
			{% endif %}
			<span class="orderMachineStateCustomerDetails" style="display: none;">
				({% lang 'StepXofY' with [
					'x': 1,
					'y': 4,
				] %})
			</span>
			<span class="orderMachineStateItems" style="display: none;">
				({% lang 'StepXofY' with [
					'x': 2,
					'y': 4,
				] %})
			</span>
			<span class="orderMachineStateShipping" style="display: none;">
				({% lang 'StepXofY' with [
					'x': 3,
					'y': 4,
				] %})
			</span>
			<span class="orderMachineStateSummary" style="display: none;">
				({% lang 'StepXofY' with [
					'x': 4,
					'y': 4,
				] %})
			</span>
		</h1>
		<p class="intro">
			{% lang 'AddOrderIntro' %}
		</p>
		{% flashMessages %}

		{{ formBuilder.startButtonRow }}
			<button class="orderMachineBackButton" disabled="disabled" accesskey="b">&lt; {{ lang.Back|accessKey('b')|safe }}</button>
			<button class="orderMachineNextButton" accesskey="n">{{ lang.Next|accessKey('n')|safe }} &gt;</button>
			{% lang 'Or' %}
			<a href="#" class="orderMachineCancelButton">{% lang 'Cancel' %}</a>
		{{ formBuilder.endButtonRow }}

		<div class="orderMachineStateCustomerDetails">
			<input type="hidden" name="customerId" value="{{ quote.customerId }}" id="customerId" />
			{{ formBuilder.startForm }}
				{{ formBuilder.heading(lang.CustomerDetails) }}

				{{ formBuilder.startRow([
					'label': lang.OrderFor ~ ':',
					'required': true
				]) }}
					{% if quote.customerId %}
						<label class="row">
							<input type="radio" name="orderFor" value="dontchange" checked="checked" /> {% lang 'KeepExistingCustomerDetails' %}
							<input type="hidden" id="existingCustomerId" value="{{ quote.customerId }}" />
						</label>
					{% endif %}
					<label class="row">
						<input type="radio" name="orderFor" value="customer" {% if addingOrder or not quote.customerId %}checked="checked"{% endif %} /> {% lang 'SearchMyExistingCustomerList' %}
					</label>
					<div class="orderForToggle orderForToggleCustomer">
						<input type="text" name="orderForSearch" id="orderForSearch" class="Field300" />
					</div>
					<label class="row">
						<input type="radio" name="orderFor" value="new" {% if not addingOrder and not quote.customerId %}checked="checked"{% endif %} /> {% lang 'ANewCustomerOrInStorePurchase' %}
					</label>
				{{ formBuilder.endRow }}
			{{ formBuilder.endForm }}

			{{ formBuilder.startForm([
				'class': 'orderForToggle orderForToggleNew',
			]) }}
				{{ formBuilder.heading(lang.AccountDetails ~ ' (' ~ lang.Optional ~ ')') }}

				{{ formBuilder.startRow }}
					<p class="MessageBox MessageBoxInfo">
						{% lang 'IfDetailsAreNotSpecified' %}
					</p>
				{{ formBuilder.endRow }}

				{% for formField in accountFormFields %}
					{% if formField.record.formfieldprivateid != 'EmailAddress' %}
						{{ formBuilder.startRowGroup }}
					{% endif %}

					{{ formField.loadForFrontend|safe }}

					{% if formField.record.formfieldprivateid != 'EmailAddress' %}
						{{ formBuilder.endRowGroup }}
					{% endif %}
				{% endfor %}

				{% if accountCustomerGroups %}
					{{ formBuilder.startRow([
						'label': lang.CustomerGroup ~ ':',
					]) }}
						{{ formBuilder.select('accountCustomerGroup', accountCustomerGroups, null, [
							'class': 'Field200'
						]) }}
					{{ formBuilder.endRow }}
				{% endif %}
			{{ formBuilder.endForm }}

			{{ formBuilder.startForm }}
				{{ formBuilder.heading(lang.BillingAddress) }}

				<fieldset class="existingAddressList">
					<legend>{% lang 'UseExistingAddress' %}</legend>
					<ul>
					</ul>
				</fieldset>
				{% for formField in billingFormFields %}
					{{ formField.loadForFrontend|safe }}
				{% endfor %}

				{{ formBuilder.startRow([
					'label': ' '
				]) }}
					<label>
						<input type="checkbox" name="saveBillingAddress" value="1" checked="checked" />
						{% lang 'SaveToCustomersAddressBook' %}
					</label>
				{{ formBuilder.endRow }}
			{{ formBuilder.endForm }}
		</div>

		<div class="orderMachineStateItems">
			{{ formBuilder.startForm(['type': 'vertical']) }}
				{{ formBuilder.heading('Search Items') }}

				{{ formBuilder.startRow([
					'last': true,
				]) }}
					<div>
						<a href="#" class="quoteItemSearchIcon" />&nbsp;</a>
						<div class="quoteItemSearch">
							<input type="text" />
						</div>
					</div>
					<div>
						<a class="addVirtualItemLink" href="#">{% lang 'AddVirtualItemLink' %}</a>
						<div class="addVirtualItemLinkAfter"></div>
					</div>
				{{ formBuilder.endRow }}
			{{ formBuilder.endForm }}

			{{ formBuilder.startForm(['type': 'vertical']) }}
				{{ formBuilder.heading(lang.ItemsInOrder) }}

				{{ formBuilder.startRow }}
					<div {% if quote.items %}style="display: none"{% endif %} class="orderNoItemsMessage">
						<p class="MessageBox MessageBoxInfo">
							{% lang 'ThisOrderIsCurrentlyEmpty' %}
						</p>
					</div>
					<div {% if quote.items == false %}style="display: none"{% endif %} class="orderItemsGrid">
						{{ itemsTable|safe }}
					</div>
					<div {% if quote.items == false %}style="display: none"{% endif %} id="itemSubtotal">
						{% lang 'SubTotal' %}: <span>{{ subtotal }}</span>
					</div>
				{{ formBuilder.endRow }}
			{{ formBuilder.endForm }}
		</div>

		<div class="orderMachineStateShipping">
			{{ formBuilder.startForm }}
				{{ formBuilder.heading(lang.ShippingDestination) }}

				{{ formBuilder.startRow([
					'label': lang.ShipItemsTo ~ ':',
					'required': true
				]) }}
					<label class="row">
						<input type="radio" name="shipItemsTo" class="showByValue orderFormDisableIfDigital orderFormCheckIfDigital" value="billing" {% if shipItemsTo == 'billing' %}checked="checked"{% endif %} /> {% lang 'TheBillingAddressAlreadySpecified' %}
					</label>
					<div class="showByValue_shipItemsTo showByValue_shipItemsTo_billing nodeJoin" id="shipItemsToBillingAddress">
					</div>
					<label class="row">
						<input type="radio" name="shipItemsTo" class="showByValue orderFormDisableIfDigital" value="single" {% if shipItemsTo == 'single' %}checked="checked"{% endif %} /> {% lang 'ASingleAddress' %}
					</label>
					<label class="row">
						<input type="radio" name="shipItemsTo" class="showByValue orderFormDisableIfDigital" value="multiple" {% if shipItemsTo == 'multiple' %}checked="checked"{% endif %} /> {% lang 'MultipleAddressesSpecifyBelow' %}
					</label>
				{{ formBuilder.endRow }}
			{{ formBuilder.endForm }}

			<div class="orderFormShowIfDigital" style="display:none;">
				<div class="MessageBox  MessageBoxInfo">
					{% lang 'DigitalOrderNotice' %}
				</div>
			</div>

			<div class="orderFormHideIfDigital" style="display:none;">
				<div class="showByValue_shipItemsTo showByValue_shipItemsTo_single">
					{{ formBuilder.startForm }}
						{{ formBuilder.heading(lang.ShippingAddress) }}

						<fieldset class="existingAddressList">
							<legend>{% lang 'UseExistingAddress' %}</legend>
							<ul>
							</ul>
						</fieldset>

						{% for formField in shippingFormFields %}
							{{ formField.loadForFrontend|safe }}
						{% endfor %}

						{{ formBuilder.startRow([
							'label': ' '
						]) }}
							<label>
								<input type="checkbox" name="saveShippingAddress" value="1" checked="checked" />
								{% lang 'SaveToCustomersAddressBook' %}
							</label>
						{{ formBuilder.endRow }}

					{{ formBuilder.endForm }}
				</div>

				{% set address = quote.getShippingAddress %}

				<div class="showByValue_shipItemsTo showByValue_shipItemsTo_billing showByValue_shipItemsTo_single">
					{% set shippingMethod = address.shippingMethod %}
					{% if shippingMethod %}
						<input type="hidden" name="currentShipping[isCustom]" value="{{ shippingMethod.isCustom }}" />
						<input type="hidden" name="currentShipping[module]" value="{{ shippingMethod.module }}" />
						<input type="hidden" name="currentShipping[description]" value="{{ shippingMethod.description }}" />
						<input type="hidden" name="currentShipping[price]" value="{{ shippingMethod.price|formatPrice(false, false) }}" />
					{% endif %}

					{{ formBuilder.startForm }}
						{{ formBuilder.heading(lang.ShippingMethod) }}

						{{ formBuilder.startRow([
							'label': lang.ChooseAProvider ~ ':',
						]) }}
							<select name="shippingQuoteList" {% if not shippingMethod %}style="display: none"{% endif %} class="Field300 showByValue shippingQuoteList" size="5">
								{% if shippingMethod and shippingMethod.module != 'custom' %}
									<option value="builtin:current" selected="selected">{% lang 'UseCurrentShippingMethod' with [
										'method': shippingMethod.description,
										'price': shippingMethod.price|formatPrice
									] %}</option>
								{% endif %}
								<option value="builtin:none">{{ lang.xNone }}</option>
								<option value="builtin:custom" {% if shippingMethod and shippingMethod.module == 'custom' %}selected="selected"{% endif %}>{{ lang.Custom }}</option>
							</select> <a href="#" class="fetchShippingQuotesLink">{% lang 'FetchShippingQuotes' %}</a>
							<div class="nodeJoin customShippingContainer showByValue_shippingQuoteList showByValue_shippingQuoteList_builtincustom" style="display:none;">
								{{ formBuilder.startForm }}
									{{ formBuilder.startRow([
										'label': lang.ShippingMethod ~ ':',
										'required': true,
									]) }}
										{{ formBuilder.input('text', 'customShippingDescription', shippingMethod.description, [
											'class': 'Field300',
										]) }}
									{{ formBuilder.endRow }}

									{{ formBuilder.startRow([
										'label': lang.ShippingCost ~ ':',
										'last': true,
									]) }}
										{{ CurrencyTokenLeft }} {{ formBuilder.input('text', 'customShippingPrice', shippingMethod.price|formatPrice(false, false), [
											'class': 'Field70',
										]) }} {{ CurrencyTokenRight }}
									{{ formBuilder.endRow }}
								{{ formBuilder.endForm }}
							</div>
						{{ formBuilder.endRow }}
					{{ formBuilder.endForm }}
				</div>

				<div class="showByValue_shipItemsTo showByValue_shipItemsTo_multiple" id="multiShippingTable">
					<!-- Placeholder for content loaded in via ajax -->
					{% if multiShippingTable %}
						{{ multiShippingTable|safe }}
					{% endif %}
				</div>
			</div>
		</div>

		<div class="orderMachineStateSummary orderMachineStateSummaryLoadingIndicator" style="display:none;">
			<div class="orderFormColumns">
				<div class="orderFormRightColumn">
					{{ formBuilder.startForm(['type': 'vertical', 'class': 'greenFormContainer']) }}
						{{ formBuilder.heading(lang.FinalizeOrder) }}
						{{ formBuilder.startRow }}
							<label class="row">
								<input type="checkbox" name="emailInvoiceToCustomer" value="1" {% if addingOrder %}checked="checked"{% endif %} /> {% lang 'EmailCustomerInvoice' %}?
							</label>
							<div class="billingEmailAddressContainer">(<span class="billingEmailAddress"></span>)</div>
							<button class="orderMachineSaveButton" disabled="disabled" accesskey="s">{% if payment %}{{ lang.SaveOnly|accessKey('s')|safe }}{% else %}{{ lang.SaveAndProcessPayment|accessKey('s')|safe }}{% endif %}</button>
						{{ formBuilder.endRow }}
					{{ formBuilder.endForm }}

					{{ formBuilder.startForm([
						'type': 'vertical',
						'class': 'orderSummaryContainer'
					]) }}
						{{ formBuilder.heading(lang.OrderSummary) }}
						{{ formBuilder.startRow }}
							<div class="orderFormSummaryOrderSummaryContainer"><!-- placeholder --></div>
						{{ formBuilder.endRow }}
					{{ formBuilder.endForm }}

					{{ formBuilder.startForm([
						'type': 'vertical',
						'class': 'couponGiftCertificateContainer',
					]) }}

						{% if allowGiftCertificates %}
							{% set couponPanelHeading = lang.CouponOrGiftCertificateQ %}
						{% else %}
							{% set couponPanelHeading = lang.ApplyCouponOnly %}
						{% endif %}

						{{ formBuilder.heading(couponPanelHeading) }}
						{{ formBuilder.startRow }}
							{{ formBuilder.input('text', 'couponGiftCertificate', '', [
								'class': 'Field120',
							]) }}<input type="button" class="orderMachineCouponButton" value="{{ lang.Apply }}" />
						{{ formBuilder.endRow }}
					{{ formBuilder.endForm }}

					{{ formBuilder.startForm(['type': 'vertical']) }}
						{{ formBuilder.heading(lang.PaymentDetails) }}

						{{ paymentForm|safe }}
					{{ formBuilder.endForm }}
				</div>
				<div class="orderFormLeftColumn">
					<div class="orderFormSummaryBillingDetailsContainer"><!-- placeholder --></div>

					<div class="orderFormSummaryShippingDetailsContainer"><!-- placeholder --></div>

					{{ formBuilder.startForm(['type': 'vertical']) }}
						{{ formBuilder.heading(lang.OrderCommentsVisible) }}
						{{ formBuilder.startRow([ 'last': true ]) }}
							{{ formBuilder.textarea('customerMessage', quote.customerMessage, [
								'class': 'Field100pct',
								'rows': 6,
							]) }}
						{{ formBuilder.endRow }}
					{{ formBuilder.endForm }}

					{{ formBuilder.startForm(['type': 'vertical']) }}
						{{ formBuilder.heading(lang.StaffNotesNotVisible) }}
						{{ formBuilder.startRow([ 'last': true ]) }}
							{{ formBuilder.textarea('staffNotes', quote.staffNotes, [
								'class': 'Field100pct',
								'rows': 6,
							]) }}
						{{ formBuilder.endRow }}
					{{ formBuilder.endForm }}
				</div>
				<div class="orderFormColumnsEnd"></div>
			</div>
		</div>

		{{ formBuilder.startButtonRow }}
			<button class="orderMachineBackButton" disabled="disabled" accesskey="b">&lt; {{ lang.Back|accessKey('b')|safe }}</button>
			<button class="orderMachineNextButton" accesskey="n">{{ lang.Next|accessKey('n')|safe }} &gt;</button>
			{% lang 'Or' %}
			<a href="#" class="orderMachineCancelButton">{% lang 'Cancel' %}</a>
		{{ formBuilder.endButtonRow }}
	</form>
</div>
<script type="text/javascript" src="../javascript/jquery/plugins/jquery.form.js?{{ JSCacheToken }}"></script>
<script type="text/javascript" src="../javascript/product.functions.js?{{ JSCacheToken }}"></script>
<script type="text/javascript" src="../javascript/jquery/plugins/autocomplete/jquery.autocomplete.js?{{ JSCacheToken }}" charset="utf-8"></script>
<script type="text/javascript" src="../javascript/jquery/plugins/disabled/jquery.disabled.js?{{ JSCacheToken }}" charset="utf-8"></script>
<script type="text/javascript" src="../javascript/formfield.js?{{ JSCacheToken }}" charset="utf-8"></script>
<script type="text/javascript" src="../javascript/fsm.js?{{ JSCacheToken }}" charset="utf-8"></script>
<script src="script/order.form.fsm.js" type="text/javascript" charset="utf-8"></script>
<script src="script/order.form.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript" charset="utf-8">//<![CDATA[
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
		'QuoteItemSearchNone',
		'EditDeletedOrderSaveNotice',
	]) }}

	{% if allowGiftCertificates %}
		lang["EnterACoupon"] = "{% jslang 'PleaseEnterACouponOrGiftCert' %}";
	{% else %}
		lang["EnterACoupon"] = "{% jslang 'PleaseEnterACoupon' %}";
	{% endif %}

	{{ formFieldJavascript|safe }}

	orderCustomFormFieldsAccountFormId = {{ formFieldTypes.accountFormFields }}
	orderCustomFormFieldsBillingFormId = {{ formFieldTypes.billingFormFields }};
	orderCustomFormFieldsShippingFormId = {{ formFieldTypes.shippingFormFields }};

	{% if quote.orderId %}
		Order_Form.orderId = {{ quote.orderId }};
	{% endif %}

	$(function(){
		{% if quote.isDigital %}
			Order_Form.setIsDigital(true);
		{% else %}
			Order_Form.setIsDigital(false);
		{% endif %}

		{% if order.deleted %}
			Order_Form.setIsDeleted(true);
		{% endif %}

		Order_Form.updateBillingEmailAddress("{{ quote.getBillingAddress.getEmail|js }}");
	});
//]]></script>
