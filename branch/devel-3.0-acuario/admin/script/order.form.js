$(function()
{
	$('#orderForm').submit(function(){
		return false;
	});

	$('input[name=orderFor]').change(function() {
		Order_Form.setIsChanged();
		var value = $(this).val();
		var activeItem = '.orderForToggle' + value.charAt(0).toUpperCase() + value.substr(1);
		if(value == 'new') {
			Order_Form.selectCustomer(0);
		} else if (value == 'dontchange') {
			Order_Form.selectCustomer($('#existingCustomerId').val());
		}
		$('.orderForToggle').not(activeItem).hide();
		$(activeItem).show();
	});
	$('input[name=orderFor]:checked').trigger('change');
	Order_Form.setIsChanged(false); // triggering the above will set the form as changed: cancel that out

	$('input[name=shipItemsTo]').change(function() {
		Order_Form.setIsChanged();

		var val = $(this).val();

		if (val == 'multiple') {
			Order_Form.loadMultiAddressShippingTable();
			return;
		}

		if (val == 'billing') {
			Order_Form.copyBillingDetailsToShipping();
			return;
		}
	});

	$('.fetchShippingQuotesLink').click(Order_Form.getSingleAddressQuotes);
	$('.fetchSplitShippingQuotesLink').click(Order_Form.getSplitAddressQuotes);

	if (typeof orderFormFsm !== 'undefined') {
		// this js file can be included on a page that doesn't contain the form fsm
		orderFormFsm.start();
	}

	$('.orderItemsGrid .customizeItemLink').live('click', Order_Form.customizeItem);
	$('.orderItemsGrid .copyItemLink').live('click', Order_Form.copyItem);
	$('.orderItemsGrid .deleteItemLink').live('click', Order_Form.deleteItem);

	$('.shippingItemsGrid thead .shippingItemsCheck input[type=checkbox]').live('click', Order_Form.toggleShippingItemsCheckboxes);
	$('.shippingItemsGrid .selectItemDestinationLink').live('click', Order_Form.selectSingleItemDestination);
	$('.shippingItemsGrid .selectItemsDestinationLink').live('click', Order_Form.selectMultiItemsDestination);
	$('.splitShippingFrameAllocateButton').live('click', Order_Form.submitSelectItemDestinationClick);
	$('.splitShippingFrameCancelButton').live('click', Order_Form.cancelSelectItemDestinationClick)
	$('.shippingDestinationChangeLink').live('click', Order_Form.changeShippingDestinationClick);
	$('.shippingDestinationDeleteLink').live('click', Order_Form.deleteShippingDestinationClick);

	$('.orderMachineCouponButton').live('click', Order_Form.couponButtonClick);
	$('.orderFormChangeBillingDetailsLink').live('click', Order_Form.changeBillingDetailsClick);
	$('.orderFormChangeShippingDetailsLink').live('click', Order_Form.changeShippingDetailsClick);
	$('.orderFormChangeShippingMethodLink').live('click', Order_Form.changeShippingMethodClick);

	$('.quoteItemQuantity input[name=quantity]')
		.live('change', Order_Form.itemQuantityPriceChange);
	$('.quoteItemPrice input[name=price]')
		.live('change', Order_Form.itemQuantityPriceChange);

	$('.quoteItemSearchIcon').click(Order_Form.openProductSelect);

	$('div.orderMachineStateCustomerDetails .useExistingAddress').live('click', function(event){
		if (event && event.preventDefault) { event.preventDefault(); }

		var addressId = $(this).parents('li').data('addressId');
		if(typeof(Order_Form.existingAddresses[addressId]) == 'undefined') {
			return;
		}
		var address = Order_Form.existingAddresses[addressId];
		Order_Form.useExistingAddress('billing', address);
	});

	$('div.orderMachineStateShipping .useExistingAddress, .orderFormAllocateFrame .useExistingAddress').live('click', function(event){
		if (event && event.preventDefault) { event.preventDefault(); }

		var addressId = $(this).parents('li').data('addressId');
		if(typeof(Order_Form.existingAddresses[addressId]) == 'undefined') {
			return;
		}
		var address = Order_Form.existingAddresses[addressId];
		Order_Form.useExistingAddress('shipping', address);
	});
	Order_Form.initCustomerSearch();
	Order_Form.initProductSearch();

	$("#paymentMethod").live('change', Order_Form.changePaymentMethod);

	$('.addVirtualItemLink').live('click', function(event){
		event.preventDefault();
		Order_Form.showAddVirtualItemDialog();
	});
});

var Order_Form = {
	orderId: false, // to be populated with a valid order id by order.form template or left as false when adding
	isChanged: false, // to be set by calls to setChanged and used by the onbeforeunload handler
	isDigital: false, // to be set by calls to setIsDigital,
	isDeleted: false, // to be set by calls to setIsDeleted (typically by the order.form template on page load)

	setIsDeleted: function (deleted) {
		Order_Form.isDeleted = !!deleted;
	},

	getIsDeleted: function () {
		return Order_Form.isDeleted;
	},

	setIsDigital: function (digital) {
		Order_Form.isDigital = !!digital;

		if (Order_Form.isDigital) {
			$('.orderFormCheckIfDigital').attr('checked', true).change();
			$('.orderFormDisableIfDigital').disable();
			$('.orderFormHideIfDigital').hide();
			$('.orderFormShowIfDigital').show();
		} else {
			$('.orderFormDisableIfDigital').enable();
			$('.orderFormHideIfDigital').show();
			$('.orderFormShowIfDigital').hide();
		}
	},

	setIsChanged: function (changed) {
		if (typeof changed == 'undefined') {
			var changed = true;
		} else {
			var changed = !!changed;
		}

		if (changed != Order_Form.isChanged) {
			// changed has changed
			if (changed) {
				Order_Form.preventUnload(true);
			} else {
				Order_Form.preventUnload(false);
			}
			Order_Form.isChanged = changed;
		}
	},

	confirmCancel: function () {
		if (!confirm(lang.ConfirmCancelMessage)) {
			return false;
		}

		// cancel should bypass the unload prevention
		Order_Form.preventUnload(false);
		return true;
	},

	preventUnload: function (prevent) {
		if (prevent) {
			$(window).bind('beforeunload', Order_Form.onBeforeUnload);
		} else {
			$(window).unbind('beforeunload', Order_Form.onBeforeUnload);
		}
	},

	onBeforeUnload: function () {
		return lang.AddEditOrderConfirmPageNavigation;
	},

	validateShipping: function()
	{
		// Validate the shipping address
		var formFields = FormField.GetValues(orderCustomFormFieldsShippingFormId);
		for (var i=0; i<formFields.length; i++) {
			var rtn = FormField.Validate(formFields[i].field);
			if (!rtn.status) {
				alert(rtn.msg);
				FormField.Focus(formFields[i].field);
				return false;
			}
		}

		return true;
	},

	getSplitAddressQuotes: function(event) {
		Order_Form.getSingleAddressQuotes(event, true);
	},

	getSingleAddressQuotes: function(event, isSplit)
	{
		if (typeof isSplit == 'undefined') {
			var isSplit = false;
		}

		if (event && event.preventDefault) { event.preventDefault(); }

		// Validate the shipping address
		var formFields = FormField.GetValues(orderCustomFormFieldsShippingFormId);
		for (var i=0; i<formFields.length; i++) {
			privateId = FormField.GetFieldPrivateId(formFields[i].field);

			// Only validate these fields here
			if(privateId != 'Country' && privateId != 'State' && privateId != 'Zip') {
				continue;
			}

			var rtn = FormField.Validate(formFields[i].field);
			if (!rtn.status) {
				alert(rtn.msg);
				FormField.Focus(formFields[i].field);
				return;
			}
		}

		var action = 'editOrderFetchSingleShippingQuotes';
		if (isSplit) {
			action = 'editOrderFetchSplitShippingQuotes';
		}

		Order_Form.setIsChanged();
		var formData = Order_Form.getSerializedSection('.orderMachineStateShipping');
		$.ajax({
			url: 'remote.php?remoteSection=orders&w=' + action,
			data: formData,
			dataType: 'json',
			type: 'post',
			success: function(response) {
				Order_Form.handleResponse(response);
			}
		});
	},

	loadMultiAddressShippingTable: function()
	{
		var indicator = LoadingIndicator.Show({
			background: '#fff',
			parent: '#multiShippingTable'
		});

		Order_Form.setIsChanged();
		$.ajax({
			url: 'remote.php?remoteSection=orders&w=editOrderLoadMultiShippingTable',
			data: {
				quoteSession: $('input[name=quoteSession]').val()
			},
			dataType: 'json',
			type: 'post',
			success: function(response) {
				LoadingIndicator.Destroy(indicator);
				Order_Form.handleResponse(response);
			}
		});
	},

	copyBillingDetailsToShipping: function()
	{
		var formValues = {};

		// Copy all of the form field values from the billing address in to the shipping address form fields
		var billingFormFields = FormField.GetValues(orderCustomFormFieldsBillingFormId);
		for(var i = 0; i < billingFormFields.length; ++i) {
			var billingFormField = billingFormFields[i].field;
			var matching = false;
			var privateId = FormField.GetFieldPrivateId(billingFormField);
			var label = FormField.GetLabel(billingFormField);

			if (privateId !== '') {
				matching = FormField.GetFieldByPrivateId(orderCustomFormFieldsShippingFormId, privateId);
			}
			else if (label !== '') {
				matching = FormField.GetFieldByLabel(orderCustomFormFieldsShippingFormId, label);
			}

			if (!matching) {
				continue;
			}

			var value = FormField.GetValue(billingFormField);

			if(privateId) {
				formValues[privateId] = value;
			}

			if (privateId == 'State') {
				var options = {
					'options': FormField.GetOptions(billingFormField, true),
					'display': 'select'
				};

				if (options.options.length == 0) {
					options.display = 'option';
				}

				FormField.SetValue(matching, value, options);
			}
			else {
				FormField.SetValue(matching, value);
			}
		}

		// Set the address value
		var billingAddressPreview = ''
			+ '<div>' + $.trim(formValues.FirstName + ' ' + formValues.LastName) + '</div>'
			+ '<div>' + formValues.CompanyName + '</div>'
			+ '<div>' + formValues.AddressLine1 + '</div>'
			+ '<div>' + formValues.AddressLine2 + '</div>'
			+ '<div>' + formValues.City;
		if(formValues.City && (formValues.State || formValues.Zip)) {
			billingAddressPreview += ', ';
		}

		billingAddressPreview += formValues.State;
		if(formValues.State && formValues.Zip) {
			billingAddressPreview += ', ';
		}
		billingAddressPreview += formValues.Zip + '</div>'
			+ '<div>' + formValues.Country + '</div>';

		Order_Form.setIsChanged();
		$('#shipItemsToBillingAddress').html(billingAddressPreview);
	},

	customizeItemModalLoaded: function()
	{
		$('#orderCustomizeItem').tabs();
		$('#orderCustomizeItemForm').submit(Order_Form.saveItemCustomizations);

		var activeTab = $('#orderCustomizeItem input[name=activeTab]').val();
		if(activeTab) {
			$('#orderCustomizeItem').tabs('select', activeTab);
		}
		$('input[name=giftWrappingType]').click(function()
		{
			var newOption = '.giftWrappingType' + $(this).val().charAt(0).toUpperCase() + $(this).val().substr(1);
			$('.giftWrappingType').not(newOption).hide();
			$(newOption).show();
		});

		$('#orderCustomizeItem .giftWrappingSelect').change(function() {
			$(this).parents('.giftWrappingOptionGroup').find('.giftWrappingPreviewLink').hide();
			$(this).parents('.giftWrappingOptionGroup').find('.giftWrappingPreviewLink' + $(this).val()).show();

			if($(this).find('option:selected').hasClass('allowComments')) {
				$(this).parents('.giftWrappingOptionGroup').find('.giftMessage').show();
			}
			else {
				$(this).parents('.giftWrappingOptionGroup').find('.giftMessage').hide();
			}
		});

		if($('.VariationSelect').length > 0) {
			Order_Form.initProductVariationSelection($('#ModalContentContainer input[name=productId]').val());
		}

		$('#orderCustomizeItem input[name=price]')
			.focus(function() {
				// Only set the initial price once
				if(typeof($(this).data('previousValue')) == 'undefined') {
					$(this).data('previousValue', $(this).val());
				}
			})
			.blur(function() {
				if($(this).val() != $(this).data('previousValue')) {
					$('#orderCustomizeItem input[name=isCustomPrice]').val(1);
				}
				else {
					$('#orderCustomizeItem input[name=isCustomPrice]').val(0);
				}
			});
	},

	saveItemCustomizations: function()
	{
		if(!$('#orderCustomizeItem input[name=name]').val()) {
			alert('Please enter a name for this item.');
			$('#orderCustomizeItem').tabs('select', 'basicTab');
			$('#orderCustomizeItem input[name=name]').focus();
			return false;
		}

		if(isNaN(parseInt($('#orderCustomizeItem input[name=quantity]').val()))) {
			alert('Please enter a quantity for this item.');
			$('#orderCustomizeItem').tabs('select', 'basicTab');
			$('#orderCustomizeItem input[name=quantity]').focus();
			return false;
		}

		var itemPrice = $('#orderCustomizeItem input[name=price]');
		if (isNaN(priceFormat(itemPrice.val())) || itemPrice.val() == "") {
			alert('Please enter a valid price for this item.');
			$('#orderCustomizeItem').tabs('select', 'basicTab');
			itemPrice.select().focus();
			return false;
		}

		// If a variation is required, check that we have one
		if($('#orderCustomizeItem .ProductVariationRequired').val() == 1 && !$('#orderCustomizeItem .CartVariationId').val()) {
			alert(lang.ChooseVariationBeforeAdding);
			$('#orderCustomizeItem').tabs('select', 'variationTab');
			$('.ProductOptionList').find('select').focus();
			return false;
		}

		// Now check that any required product fields were also supplied
		var valid = true;
		$('#orderCustomizeItem #configurableFieldsTab .FieldRequired').each(function() {
			if($(this).is('[type=checkbox]') && !this.checked) {
				alert(lang.EnterProductRequiredFields);
				$('#orderCustomizeItem').tabs('select', 'configurableFieldsTab');
				$(this).focus();
				valid = false;
				return false;
			}
			else if($(this).is('[type=file]')) {
				if(!$(this).val() && (!$(this).is('.HasExistingValue') || $(this).parents('.ConfigurableField').find('.RemoveCheckbox:checked').val())) {
					alert(lang.EnterProductRequiredFields);
					$('#orderCustomizeItem').tabs('select', 'configurableFieldsTab');
					$(this).focus();
					valid = false;
					return false;
				}
			}
			else if(!$(this).val()) {
				alert(lang.EnterProductRequiredFields);
				$('#orderCustomizeItem').tabs('select', 'configurableFieldsTab');
				$(this).focus();
				valid = false;
				return false;
			}
		});

		if(valid == false) {
			return false;
		}

		$('#orderCustomizeItem #configurableFieldsTab input[type=file]').each(function() {
			if($(this).val()) {
				var fileTypes = $(this).parents('div.value').find('.FileTypes').html();
				var ext = $(this).val().replace(/^.*\./, '').toLowerCase();
				if(fileTypes && fileTypes.toLowerCase().replace(' ', '').indexOf(ext) == -1) {
					alert(lang.ChooseValidProductFieldFile);
					$('#orderCustomizeItem').tabs('select', 'configurableFieldsTab');
					$(this).focus();
					valid = false;
					return false;
				}
			}
		});

		if(valid == false) {
			return false;
		}

		if(!CheckEventDate()) {
			$('#orderCustomizeItem').tabs('select', 'eventDateTab');
			return false;
		}

		var buttonLabel;
		if(!$('#ModalContainer .itemId').val()) {
			buttonLabel = lang.AddingProductToOrder;
		}
		else {
			buttonLabel = lang.UpdatingProductInOrder;
		}

		$('#orderCustomizeItemForm input.SubmitButton')
			.data('oldVal', $('#orderCustomizeItemForm input.SubmitButton').val())
			.val(buttonLabel)
			.disable();

		Order_Form.setIsChanged();
		$('#orderCustomizeItemForm').ajaxSubmit({
			url: 'remote.php?remoteSection=orders&w=editOrderSaveItemCustomizations&ajaxFormUpload=1',
			type: 'post',
			iframe: true,
			dataType: 'json',
			success: function(data)
			{
				$('#orderCustomizeItemForm input.SubmitButton')
					.val($('#orderCustomizeItemForm input.SubmitButton')
					.data('oldVal'))
					.enable();
				Order_Form.handleResponse(data)
			},
			error: function(XMLHttpRequest, textStatus, errorThrown)
			{
				eval('var obj = ' + XMLHttpRequest.responseText);
				alert(obj);
			}

		});

		return false;
	},

	itemQuantityPriceChange: function()
	{
		if($(this).data('previousValue') == $(this).val()) {
			return true;
		}

		var self = this;
		var parentRow = $(this).parents('tr');

		var quantityField = parentRow.find('.quoteItemQuantity input[name=quantity]');
		var quantity = quantityField.val();

		var priceField = parentRow.find('.quoteItemPrice input[name=price]');
		var price = priceField.val();

		if(isNaN(priceFormat(price)) || price == "") {
			alert('Please enter a valid price for this item.');
			$(priceField).select().focus();
			return false;
		}
		else if(quantity.match(/[^0-9]/)) {
			alert('Please enter a valid quantity for this item.');
			$(quantityField).select().focus();
			return false;
		}

		var indicator = LoadingIndicator.Show({background: '#fff', parent: parentRow});
		var itemId = parentRow.attr('id').replace('itemId_', '');

		Order_Form.setIsChanged();
		$.ajax({
			url: 'remote.php?remoteSection=orders&w=editOrderUpdateItemQuantityPrice',
			dataType: 'json',
			type: 'post',
			data: {
				itemId: itemId,
				quoteSession: $('input[name=quoteSession]').val(),
				quantity: quantity,
				price: priceFormat(price)
			},
			success: function(response)
			{
				LoadingIndicator.Destroy(indicator);
				Order_Form.handleResponse(response);
				if(response.errors) {
					$(self)
						.val($(self).data('previousValue'))
						.focus().select()
				} else {
					$(self).data('previousValue', $(self).val());
				}
			}
		})
	},

	customizeItem: function(event)
	{
		if (event && event.preventDefault) { event.preventDefault(); }

		var indicator = LoadingIndicator.Show({background: '#fff', parent: $(this).parents('tr')});
		var itemId = $(this).parents('tr').attr('id').replace('itemId_', '');
		$.ajax({
			url: 'remote.php?remoteSection=orders&w=editOrderCustomizeItem',
			dataType: 'json',
			type: 'post',
			data: {
				itemId: itemId,
				quoteSession: $('input[name=quoteSession]').val()
			},
			success: function(response)
			{
				LoadingIndicator.Destroy(indicator);
				Order_Form.handleResponse(response);
			}
		});
	},

	copyItem: function(event)
	{
		if (event && event.preventDefault) { event.preventDefault(); }

		Order_Form.setIsChanged();
		var itemId = $(this).parents('tr').attr('id').replace('itemId_', '');
		var indicator = LoadingIndicator.Show({background: '#fff', parent: $(this).parents('tr')});
		$.ajax({
			url: 'remote.php?remoteSection=orders&w=editOrderCopyItem',
			dataType: 'json',
			type: 'post',
			data: {
				itemId: itemId,
				quoteSession: $('input[name=quoteSession]').val()
			},
			success: function(response)
			{
				LoadingIndicator.Destroy(indicator);
				Order_Form.handleResponse(response);
			}
		});
	},

	deleteItem: function(event)
	{
		if (event && event.preventDefault) { event.preventDefault(); }

		if(!confirm(lang.ConfirmRemoveProductFromOrder)) {
			return;
		}

		Order_Form.setIsChanged();
		var itemId = $(this).parents('tr').attr('id').replace('itemId_', '');
		var indicator = LoadingIndicator.Show({background: '#fff', parent: $(this).parents('tr')});
		$.ajax({
			url: 'remote.php?remoteSection=orders&w=editOrderDeleteItem',
			dataType: 'json',
			type: 'post',
			data: {
				itemId: itemId,
				quoteSession: $('input[name=quoteSession]').val()
			},
			success: function(response)
			{
				LoadingIndicator.Destroy(indicator);
				Order_Form.handleResponse(response);
			}
		});
	},

	initProductVariationSelection: function(productId)
	{

		var VariationSelects = $(".VariationSelect");
		
		var i; var j; var activate = false;
		for(i=0;i<VariationSelects.length;i++)
		{
			for(j=0;j<VariationSelects[i].length;j++)
			{
				if (VariationSelects[i].options[j].innerHTML == "N/A") activate = true;
			}
		}
		
		//alert("--"+activate+"--");
		
		// disable all but the first variation box
		if($('.CartVariationId').val() == 0 || activate == false) {
			$(".VariationSelect:gt(0)").disable();
		}


		$(".VariationSelect").change(function() {
			// get the index of this select
			var index = $('.VariationSelect').index($(this));

			if (activate == false)
			{
				// deselected an option, disable all select's below this one and remove their options
				if (this.selectedIndex == 0) {
					$('.VariationSelect:gt(' + index + ')').each(function() {
						$(this).disable();
						//$(this).find('option:gt(0)').remove();
					});

					updateSelectedVariation($('#ModalContentContainer'));

					return;
				}
				else {
					// disable selects greater than the next
					$('.VariationSelect:gt(' + (index + 1) + ')').disable();
				}
			}
			//serialize the options of the variation selects
			var optionIds = '';
			$('.VariationSelect:lt(' + (index + 1) + ')').each(function() {
				if (optionIds != '') {
					optionIds += ',';
				}

				optionIds += $(this).val();
			});

			// request values for this option
			$.getJSON(
				'remote.php?remoteSection=orders&w=editOrderGetVariationDetails',
				{
					productId: productId,
					options: optionIds,
					quoteSession: $('input[name=quoteSession]').val()
				},
				function(data) {
					// were options returned?
					if (data.hasOptions) {
						// load options into the next select, disable and focus it
						$('.VariationSelect:eq(' + (index + 1) + ') option:gt(0)').remove();
						$('.VariationSelect:eq(' + (index + 1) + ')').append(data.options).enable().focus();
					}
					else if (data.comboFound) { // was a combination found instead?
						// display price, image etc
						updateSelectedVariation($('#ModalContentContainer'), data, data.combinationid);
						$('#ModalContentContainer .VariationProductPrice').val(data.unformattedPrice);
					}
				}
			);
		});
	},

	initCustomerSearch: function()
	{
		if (!$('#orderForSearch').size()) {
			return;
		}

		$('#orderForSearch')
			.autocomplete('remote.php', {
				dataType: 'json',
				highlight: false,
				matchSubset: false,
				resultsClass: 'orderCustomerSearchResults',
				focusOnSelect: false,
				parse: function(data)
				{
					var array = new Array();
					if(!data || !data.length) {
						array[array.length] = {
							data: {
								id: 0
							},
							value: '',
							result: ''
						};
						return array;
					}
					for(var i=0; i < data.length; i++) {
						array[array.length] = {
							data: data[i],
							value: data[i].name + ' (' + data[i].email + ')',
							result: data[i].name + ' (' + data[i].email + ')'
						};
					}
					return array;
				},
				extraParams: {
					remoteSection: 'orders',
					w: 'editOrderCustomerSearch',
					quoteSession: $('input[name=quoteSession]').val()
				},
				formatItem: function(result, position, total, query)
				{
					var row;
					if(result.id == 0) {
						row = '<div class="recordNoResults">'
							+ lang.OrderCustomerSearchNone.replace('%s', $('#orderForSearch').val())
							+ '</div>';
						return row;
					}
					row = '<div class="recordContent">'
						+ '	<input type="hidden" class="searchResultCustomerId" value="' + result.id + '" /> '
						+ '	<div class="viewItemLink">'
						+ '		<a href="index.php?ToDo=viewOrders&amp;customerId=' + result.id + '" target="_blank">' + lang.ViewOrderHistory + '</a>'
						+ '	</div>'
						+ '<strong>' + result.name + '</strong>'
						+ '<div class="customerDetails">';

					if(result.company) {
						row += '<div>' + result.company + '</div>';
					}

					if(result.email) {
						row += '<div>' + result.email + '</div>';
					}

					if(result.phone) {
						row += '<div>' + result.phone + '</div>';
					}
					row += '	</div>'
						+ '</div>';
					return row;
				},
				scrollHeight: 300
			})
			.result(function(e, item)
			{
				$('#orderForSearch').blur();
				if(item.id > 0) {
					Order_Form.selectCustomer(item.id);
				}
			})
			.blur(function()
			{
				if(!$(this).val()) {
					$(this)
						.val(lang.TypeACustomerNameEmailEtc)
						.addClass('orderCustomerSearchDefaultValue')
						.data('defaultValue', true);
				}
				else {
					$(this).data('defaultValue', false);
				}
			})
			.focus(function()
			{
				if($(this).data('defaultValue')) {
					$(this)
						.val('')
						.removeClass('orderCustomerSearchDefaultValue');
				}
			})
			.trigger('blur');
	},

	selectCustomer: function(customerId)
	{
		Order_Form.setIsChanged();
		if(customerId == 0) {
			Order_Form.existingAddresses = null;
			$('#customerId').val(0);
			$('#orderForm input[name=orderFor][value=new]').attr('checked', true);
			$('#orderForm .existingAddressList').hide();
			$('#orderForm .newAccountOnlyFields').show();
			$('#orderForm .existingAccountOnlyFields').hide();
			return;
		}

		if (customerId == $('#existingCustomerId').val()) {
			$('#orderForm input[name=orderFor][value=dontchange]').attr('checked', true);
		} else {
			$('#orderForm input[name=orderFor][value=customer]').attr('checked', true);
		}

		$('#orderForm .newAccountOnlyFields').hide();
		$('#orderForm .existingAccountOnlyFields').show();
		$('.accountFormFields div.formRow').hide();

		Order_Form.loadCustomer(customerId);
	},

	loadCustomer: function(customerId)
	{
		if (!$('div.orderMachineStateCustomerDetails:visible').size()) {
			var showIndicator = false;
		} else {
			var showIndicator = true;
		}

		var indicator = false;
		if (showIndicator) {
			indicator = LoadingIndicator.Show({background: '#fff', parent: 'div.orderMachineStateCustomerDetails'});
		}

		$.ajax({
			url: 'remote.php?remoteSection=orders&w=editOrderLoadCustomer',
			type: 'post',
			dataType: 'json',
			data: {
				customerId: customerId
			},
			success: function(response)
			{
				$('#customerId').val(customerId);

				// Populate email field
				var formField = FormField.GetFieldByPrivateId(orderCustomFormFieldsAccountFormId, 'EmailAddress');
				if(formField) {
					FormField.SetValue(formField, response.email);
				}

				if (indicator) {
					LoadingIndicator.Destroy(indicator);
				}

				Order_Form.populateExistingAddresses(response.addresses);
			},
			error: function()
			{

			}
		});
	},

	validateCustomerDetails: function()
	{
		if($('input[name=orderFor]:checked').val() == 'customer' && !$('input[name=customerId]').val()) {
			alert(lang.PleaseSearchForACustomer);
			return false;
		}

		var formFields = FormField.GetValues(orderCustomFormFieldsAccountFormId);

		var password = '';
		var confirmPassword = '';

		var formField;
		for(var i = 0; i < formFields.length; ++i) {
			formField = formFields[i];

			if(formField.privateId == 'EmailAddress' && formField.value && !Order_Form.validateEmailAddress(formField.value)) {
				alert(lang.CustomerEmailInvalid);
				FormField.Focus(formField.field);
				return false;
			}
			else if(formField.privateId == 'Password') {
				password = formField;
			}
			else if(formField.privateId == 'ConfirmPassword') {
				confirmPassword = formField;
			}
		}

		// Validate these fields as well if this order is for a new customer
		if(!$('#customerId').val()) {
			if(password && password.value != confirmPassword.value) {
				alert(lang.CustomerPasswordConfirmError);
				FormField.Focus(confirmPassword.field);
				return false;
			}
		}

		// Validate the billing address
		formFields = FormField.GetValues(orderCustomFormFieldsBillingFormId);
		for (var i=0; i<formFields.length; i++) {
			var rtn = FormField.Validate(formFields[i].field);
			if (!rtn.status) {
				alert(rtn.msg);
				FormField.Focus(formFields[i].field);
				return false;
			}
		}

		return true;
	},

	validateEmailAddress: function(email)
	{
		if(email.indexOf('@') == -1 || email.indexOf('.') == -1) {
			return false;
		}

		return true;

	},

	populateExistingAddresses: function(addresses)
	{
		if(!addresses || addresses.length == 0) {
			$('.existingAddressList').hide();
			Order_Form.existingAddresses = null;
			return;
		}

		// Empty lists first
		$('.existingAddressList ul > *').remove();

		Order_Form.existingAddresses = addresses;

		$.each(Order_Form.existingAddresses, function(i)
		{
			var addressRow = Order_Form.buildAddressRow(this);

			$('.existingAddressList ul').each(function()
			{
				$('<li />')
					.data('addressId', i)
					.append(addressRow)
					.appendTo(this);
			});
		});

		$('.existingAddressList').show();
	},

	buildAddressRow: function(address)
	{
		var row = '<div class="addressDetails" ';
		if(address.countryFlag) {
			row += 'style="background-image: url(\'../lib/flags/' + address.countryFlag + '.gif\')"';
		}
		row += '>'
			+ '<a href="#" class="useExistingAddress">' + $.htmlEncode(lang.UseThisAddress) + '</a>'
			+ '<strong>' + $.htmlEncode(address.FirstName) + ' ' + $.htmlEncode(address.LastName) + '</strong>'
			+ '<div>' + $.htmlEncode(address.CompanyName) + '</div>'
			+ '<div>' + $.htmlEncode(address.AddressLine1) + '</div>'
			+ '<div>' + $.htmlEncode(address.AddressLine2) + '</div>'
			+ '<div>' + $.htmlEncode(address.City);

		if(address.City && (address.State || address.Zip)) {
			row += ', ';
		}

		row += $.htmlEncode(address.State);

		if(address.State && address.Zip) {
			row += ', ';
		}

		if (address.Zip) {
			row += $.htmlEncode(address.Zip);
		}

		row += '</div>'
			+ '<div>' + $.htmlEncode(address.Country) + '</div>'
			+ '</div>';

		return row;
	},

	useExistingAddress: function(where, address)
	{
		Order_Form.setIsChanged();

		var formId;
		if(where == 'billing') {
			formId = orderCustomFormFieldsBillingFormId;
		}
		else {
			formId = orderCustomFormFieldsShippingFormId;
		}

		var countryFieldId = 0;
		var stateFieldId = 0;

		var formFields = FormField.GetValues(formId);
		var privateId, stateFieldId, state, countryFieldId;
		for (var i=0; i<formFields.length; i++) {
			privateId = formFields[i].privateId;
			if (privateId == '') {
				continue;
			}

			// Special case for 'state'. We'll do it later as we need the country first
			if (privateId == 'State') {
				stateFieldId = formFields[i].fieldId;
				state = address[privateId];
				continue;
			}
			else if (address[privateId] == undefined) {
				continue;
			}

			FormField.SetValue(formFields[i].field, address[privateId]);

			// Pick up the country if this is it
			if (privateId == 'Country') {
				countryFieldId = formFields[i].fieldId;
			}
		}

		// Now assign the states
		if (countryFieldId > 0 && stateFieldId > 0) {
			var args = {
				'data': {
					'countryId': countryFieldId,
					'stateId': stateFieldId,
					'selectedState': state
				}
			};

			FormFieldEvent.SingleSelectPopulateStates(args);
		}
	},

	initProductSearch: function()
	{
		if (!$('.quoteItemSearch input').size()) {
			return;
		}

		$('.quoteItemSearch input')
			.autocomplete('remote.php', {
				dataType: 'json',
				highlight: false,
				matchSubset: false,
				resultsClass: 'quoteItemSearchResults',
				focusOnSelect: false,
				max: 11,
				parse: function(data)
				{
					// count the actual results of the query - if there are no results, prepend a 'no results' indicator
					var actualResults = 0;
					var array = new Array();

					for(var i=0; i < data.length; i++) {
						if (data[i].id != 'virtual') {
							actualResults++;
						}

						array[array.length] = {
							data: data[i],
							value: data[i].id,
							result: data[i].name
						};
					}

					if (!actualResults) {
						// prepend no-results indicator for formatItem to use
						array.unshift({
							data: {
								id: 0
							},
							value: '',
							result: ''
						});
					}

					return array;
				},
				extraParams: {
					remoteSection: 'orders',
					w: 'editOrderItemSearch',
					quoteSession: $('input[name=quoteSession]').val()
				},
				formatItem: function(result, position, total, query)
				{
					if (result.id === 0) {
						row = '<div class="recordNoResults">'
							+ lang.QuoteItemSearchNone.replace(':query', $('.quoteItemSearch input').val())
							+ '</div>';
						return row;
					}

					var row = $('<div></div>');

					row.append('<input type="hidden" class="searchResultProductId" value="' + result.id + '" />');
					if (result.link) {
						row.append('<div class="viewItemLink"><a href="' + result.link + '" target="_blank">View Product</a></div>');
					}
					row.append('<strong>' + result.name + '</strong>');

					var sku = '';
					if (result.sku) {
						sku = result.sku + ' / ';
					}

					var details = $('<div class="productDetails">' + sku + result.price + '</div>');
					row.append(details);

					return '<div class="recordContent ' + result.className + '">' + row.html() + '</div>';
				},
				scrollHeight: 300
			})
			.result(function(e, item)
			{
				if (item.id === 0) {
					return;
				}

				if (item.id == 'virtual') {
					Order_Form.showAddVirtualItemDialog(item.virtualName);
					return;
				}

				Order_Form.addItem(item.id);
			})
			.blur(function()
			{
				$(this)
					.val(lang.TypeAProductNameSkuEtc)
					.addClass('quoteItemSearchDefaultValue');
			})
			.focus(function()
			{
				$(this)
					.val('')
					.removeClass('quoteItemSearchDefaultValue');
			})
			.trigger('blur');
	},

	showAddVirtualItemDialog: function (name)
	{
		if (typeof name == 'undefined') {
			var name = '';
		}

		$.ajax({
			url: 'remote.php?remoteSection=orders&w=editOrderAddVirtualItem',
			dataType: 'json',
			type: 'post',
			data: {
				name: name,
				quoteSession: $('input[name=quoteSession]').val()
			},
			success: function(response)
			{
				Order_Form.handleResponse(response);
			}
		});
	},

	addItem: function(id)
	{
		Order_Form.setIsChanged();
		var indicator = LoadingIndicator.Show({background: '#fff', parent: 'div.orderMachineStateItems'});
		$.ajax({
			url: 'remote.php?remoteSection=orders&w=editOrderAddItem',
			data: {
				productId: id,
				quoteSession: $('input[name=quoteSession]').val()
			},
			dataType: 'json',
			type: 'post',
			success: function(data)
			{
				LoadingIndicator.Destroy(indicator);
				Order_Form.handleResponse(data);
			}
		})
		Order_Form.selectedProduct = null;
	},

	openProductSelect: function()
	{
		var l = (screen.availWidth/2) - (700/2) + 50;
		var t = (screen.availHeight/2) - (490/2) + 50;
		var width = 700;

		var windowLocation = 'index.php?ToDo=popupProductSelect';
		windowLocation += '&selectCallback=Order_Form.productSelectCallback';
		windowLocation += '&getSelectedCallback=Order_Form.productSelectGetSelected';
		windowLocation += '&closeCallback=Order_Form.productSelectCloseCallback';
		windowLocation += '&ProductList=x';
		windowLocation += '&ProductSelect=y';
		windowLocation += '&single=1';
		windowLocation += '&FocusOnClose=';
		var w = window.open(windowLocation, 'productSelecttypesingle', "width="+width+",height=540,left="+l+",top="+t);
		w.focus();
		return false;
	},

	productSelectCloseCallback: function(rowId)
	{
		if(!Order_Form.selectedProduct) {
			return;
		}

		Order_Form.addItem(Order_Form.selectedProduct);
	},

	productSelectCallback: function(unused1, unused2, product)
	{
		Order_Form.selectedProduct = product.id;
	},

	productSelectGetSelected: function()
	{
		return '';
	},

	getSerializedSection: function(selector, filter)
	{
		var formData = $(selector).find('input, select, textarea');
		if (typeof filter != 'undefined') {
			formData = formData.filter(filter);
		}
		formData = formData.serialize();
		formData += '&quoteSession=' + Order_Form.getQuoteSession();
		return formData;
	},

	getQuoteSession: function()
	{
		return $('input[name=quoteSession]').val();
	},

	validateOrderItems: function()
	{
		if($('.orderMachineStateItems .orderItemsGrid .itemRow').length == 0) {
			alert(lang.PleaseAddOneOrMoreItems);
			return false;
		}

		return true
	},

	handleResponse: function(response)
	{
		if (!response) {
			// blank/invalid response, stop here to prevent js errors
			return;
		}

		// Transition the state machine to a particular stage
		if(response.stateTransition) {
			orderFormFsm.transition(response.stateTransition);
		}

		// Show one or more error messages
		if(response.errors) {
			var errorMessage = response.errors.join("\n");
			alert(errorMessage);
		}

		// Give focus to a particular form field
		if(response.focusField) {
			$(response.focusField).focus();
		}

		// Close current modal window
		if(response.closeModal) {
			$.iModal.close();
		}

		if(response.modal) {
			$.iModal(response.modal);
		}

		// Add a new item to the items list on step 2
		if(response.itemsNewItem) {
			// @todo can't just prepend to the table after introduction of headers on the editing grid due to editing multi-address orders
//			$('.orderMachineStateItems .orderItemsGrid table')
//				.prepend(response.itemsNewItem)
//				.parent().show();
		}

		// Remove an item with the given ID from step 2
		if(response.itemsRemoveItem) {
			var row = $('.orderMachineStateItems .orderItemsGrid table #itemId_' + response.itemsRemoveItem);
			var container = row.closest('.itemRows');
			row.remove();

			if (!container.find('.itemRow').size()) {
				// destination is empty, remove it and it's heading
				container.prev('.itemHeading').remove();
				container.remove();
			}
		}

		// Replace entire contents of order items grid
		if(response.itemsTable) {
			$('.orderMachineStateItems .orderItemsGrid').html(response.itemsTable);
		}

		// Replace the contents of an item on step 2 with the given ID by content
		if(response.itemsUpdateItem) {
			var id = response.itemsUpdateItem.id;
			var content = response.itemsUpdateItem.content;
			$('.orderMachineStateItems .orderItemsGrid table #itemId_' + id).replaceWith(content);
		}

		// Update the total row for a particular item
		if(response.itemsUpdateItemTotal) {
			var id = response.itemsUpdateItemTotal.id;
			var content = response.itemsUpdateItemTotal.content;
			$('.orderMachineStateItems .orderItemsGrid table #itemId_' + id + ' .quoteItemTotal span')
				.html(content)
				.effect("highlight", {}, 1500);
		}

		var orderHasItems = true;
		if($('.orderMachineStateItems .orderItemsGrid .itemRow').length == 0) {
			orderHasItems = false;
			$('.orderNoItemsMessage').show();
			$('.orderMachineStateItems .orderItemsGrid').hide();
		}
		else {
			$('.orderNoItemsMessage').hide();
			$('.orderMachineStateItems .orderItemsGrid').show();
		}

		// Update the subtotal shown at the bottom of step 2
		if(response.itemsSubtotal) {
			$('.orderMachineStateItems #itemSubtotal').toggle(orderHasItems);
			$('.orderMachineStateItems #itemSubtotal span').html(response.itemsSubtotal);
		}

		// Handle a response containing a set of shipping quotes for a single shipping destination
		if(response.singleShippingMethods) {
			Order_Form.showSingleShippingMethodOptions(response.singleShippingMethods);
		}

		if(response.multiShippingTable) {
			$('#multiShippingTable').html(response.multiShippingTable);
		}

		if (response.summaryTable) {
			$('.orderFormSummaryOrderSummaryContainer').html(response.summaryTable);
		}

		if (response.billingDetailsSummary) {
			$('.orderFormSummaryBillingDetailsContainer').html(response.billingDetailsSummary);
		}

		if (response.shippingDetailsSummary) {
			$('.orderFormSummaryShippingDetailsContainer').html(response.shippingDetailsSummary);
		}

		if (response.highlight) {
			$(response.highlight).effect('highlight', {}, 1500);
		}

		if (response.updateOrderId) {
			Order_Form.orderId = response.updateOrderId;
		}

		if (typeof response.isDigital !== 'undefined') {
			Order_Form.setIsDigital(response.isDigital);
		}

		if (response.billingEmailAddress) {
			Order_Form.updateBillingEmailAddress(response.billingEmailAddress);
		}
	},

	updateBillingEmailAddress: function (email) {
		$('.billingEmailAddress').text(email);
		if (email) {
			$('.billingEmailAddressContainer').show();
		} else {
			$('.billingEmailAddressContainer').hide();
		}
	},

	showSingleShippingMethodOptions: function(options)
	{
		Order_Form.populateShippingMethodSelect(
			$('.orderMachineStateShipping select[name=shippingQuoteList]'),
			options
		);
	},

	populateShippingMethodSelect: function(select, options)
	{
		// Remove existing options (jquery freaks out when this is one select)
		$('option:not([value^=builtin:])', select).remove();

		var optionsExist = false;
		$.each(options, function(index) {
			optionsExist = true;
			$('<option />')
				.val(index)
				.html(this.description + ' (' + this.price + ')')
				.data('unformattedPrice', this.unformattedPrice)
				.data('description', this.description)
				.data('module', this.module)
				.data('handling', this.handling)
				.prependTo(select)
		});

		if(!optionsExist) {
			alert(lang.NoShippingMethodsAreAvailable_1 + "\n\n" + lang.NoShippingMethodsAreAvailable_2);
			return;
		}

		$(select).show();
	},

	selectSingleItemDestination: function (event) {
		if (event && event.preventDefault) { event.preventDefault(); }
		var $$ = $(this);
		var row = $$.closest('.itemRow');
		var itemId = row.attr('id').replace(/^shippingUnallocated_/, '');
		Order_Form.showDestinationSelectionForItems([itemId]);
	},

	selectMultiItemsDestination: function (event) {
		if (event && event.preventDefault) { event.preventDefault(); }
		var checked = $('.shippingUnallocatedGrid .itemRow .shippingItemsCheck input[type=checkbox]:checked');
		if (!checked.size()) {
			alert(lang.ChooseOneItemForShippingDestination);
			return;
		}

		var items = [];
		checked.each(function(){
			items.push(this.value);
		});

		Order_Form.showDestinationSelectionForItems(items);
	},

	showDestinationSelectionForItems: function (items) {
		Order_Form.setIsChanged();
		var url = 'index.php?ToDo=editOrderMultiAddressFrame' + Order_Form.getSerializedSection('#multiShippingTable', ':not(.shippingItemsCheck input[type=checkbox])');

		$.each(items, function(index, value){
			url += '&item%5B%5D=' + encodeURIComponent(value);
		});

		$.iModal({
			width: $('.ContentContainer').width(), // match the control panel border width
			title: 'Select Destination',
			data: '<iframe id="multiAddressFrame" width="100%" height="' + ($(window).height() - 150) + '" frameborder="0" src="' + $.htmlEncode(url) + '"></iframe>',
			buttons: '<button class="splitShippingFrameAllocateButton">' + lang.AllocateProducts + '</button><button class="splitShippingFrameCancelButton">' + lang.Cancel + '</button>',
			top: 20
		});
	},


	changeShippingDestination: function (addressId) {
		Order_Form.setIsChanged();
		var url = 'index.php?ToDo=editOrderMultiAddressFrame&quoteSession=' + encodeURIComponent($('input[name=quoteSession]').val()) + '&address=' + encodeURIComponent(addressId);

		$.iModal({
			width: $('.ContentContainer').width(), // match the control panel border width
			title: 'Select Destination',
			data: '<iframe id="multiAddressFrame" width="100%" height="' + ($(window).height() - 150) + '" frameborder="0" src="' + $.htmlEncode(url) + '"></iframe>',
			buttons: '<button class="splitShippingFrameAllocateButton">' + lang.SaveChanges + '</button><button class="splitShippingFrameCancelButton">' + lang.Cancel + '</button>',
			top: 20
		});
	},

	toggleShippingItemsCheckboxes: function (event) {
		var $$ = $(this);
		var checkboxes = $('.shippingUnallocatedGrid .itemRow .shippingItemsCheck input[type=checkbox]');
		checkboxes.attr('checked', $$.attr('checked'));
	},

	couponButtonClick: function (event) {
		event.preventDefault();
		Order_Form.applyCouponCode($('input[name=couponGiftCertificate]').val());
	},

	applyCouponCode: function (code) {
		if (!code) {
			alert(lang.EnterACoupon);
			return;
		}

		Order_Form.setIsChanged();
		var indicator = LoadingIndicator.Show({
			background: '#fff',
			parent: '.couponGiftCertificateContainer'
		});

		var formData = Order_Form.getSerializedSection('.couponGiftCertificateContainer');

		$.ajax({
			url: 'remote.php?remoteSection=orders&w=editOrderApplyCouponCode',
			data: formData,
			dataType: 'json',
			type: 'post',
			complete: function() {
				LoadingIndicator.Destroy(indicator);
			},
			success: function(response) {
				Order_Form.handleResponse(response);
			}
		});
	},

	removeCouponById: function(id) {
		if (!id) {
			return;
		}

		Order_Form.setIsChanged();
		var indicator = LoadingIndicator.Show({
			background: '#fff',
			parent: '.orderSummaryContainer'
		});

		$.ajax({
			url: 'remote.php?remoteSection=orders&w=editOrderRemoveCoupon',
			data: {
				quoteSession: Order_Form.getQuoteSession(),
				couponId: id
			},
			dataType: 'json',
			type: 'post',
			complete: function() {
				LoadingIndicator.Destroy(indicator);
			},
			success: function(response) {
				Order_Form.handleResponse(response);
			}
		});
	},

	removeGiftCertificateById: function(id) {
		if (!id) {
			return;
		}

		Order_Form.setIsChanged();
		var indicator = LoadingIndicator.Show({
			background: '#fff',
			parent: '.orderSummaryContainer'
		});

		$.ajax({
			url: 'remote.php?remoteSection=orders&w=editOrderRemoveGiftCertificate',
			data: {
				quoteSession: Order_Form.getQuoteSession(),
				giftCertificateId: id
			},
			dataType: 'json',
			type: 'post',
			complete: function() {
				LoadingIndicator.Destroy(indicator);
			},
			success: function(response) {
				Order_Form.handleResponse(response);
			}
		});
	},

	getPaymentMethod: function()
	{
		return $("#paymentMethod").val();
	},

	changePaymentMethod: function() {
		Order_Form.setIsChanged();

		$('.paymentMethodForm').hide();
		var paymentMethod = Order_Form.getPaymentMethod();
		if (!paymentMethod) {
			return;
		}

		$("#paymentMethodForm_" + paymentMethod).show();
	},

	validatePaymentMethod: function() {
		var paymentMethod = Order_Form.getPaymentMethod();
		if (!paymentMethod) {
			alert(lang.InvalidPaymentModule);
			$('#paymentMethod').focus();
			return false;
		}

		// attempt to do validation on the payment method fields
		var validationObject = 'PaymentValidation_' + paymentMethod;
		if (typeof window[validationObject] != 'undefined') {
			if (!window[validationObject].checkForm()) {
				return false;
			}
		}

		return true;
	},

	changeBillingDetailsClick: function (event) {
		event.preventDefault();
		orderFormFsm.transition('clickChangeBillingDetails');
	},

	changeShippingDetailsClick: function (event) {
		event.preventDefault();
		orderFormFsm.transition('clickChangeShippingDetails');
	},

	changeShippingMethodClick: function (event) {
		event.preventDefault();
		orderFormFsm.transition('clickChangeShippingMethod');
	},

	changeShippingDestinationClick: function (event) {
		event.preventDefault();
		var $$ = $(this);
		var row = $$.closest('.shippingDestinationRow');
		var addressId = row.attr('id').replace(/^shippingDestination_/i, '');
		Order_Form.changeShippingDestination(addressId);
	},

	deleteShippingDestinationClick: function (event) {
		event.preventDefault();
		var $$ = $(this);
		var row = $$.closest('.shippingDestinationRow');
		var addressId = row.attr('id').replace(/^shippingDestination_/i, '');
		Order_Form.deleteShippingDestination(addressId);
	},

	deleteShippingDestination: function (addressId) {
		if (!confirm(lang.ConfirmDeleteShippingDestination)) {
			return;
		}

		Order_Form.setIsChanged();

		var indicator = LoadingIndicator.Show({
			background: '#fff',
			parent: '#multiAddressShippingAllocation'
		});

		$.ajax({
			url: 'remote.php?remoteSection=orders&w=editOrderDeleteShippingDestination',
			data: {
				quoteSession: $('input[name=quoteSession]').val(),
				addressId: addressId
			},
			dataType: 'json',
			type: 'post',
			complete: function() {
				LoadingIndicator.Destroy(indicator);
			},
			success: function(response) {
				Order_Form.handleResponse(response);
			}
		});
	},

	submitSelectItemDestinationClick: function (event) {
		event.preventDefault();
		Order_Form.submitSelectItemDestination();
	},

	submitSelectItemDestination: function (event) {
		// validate address
		var frame = window.frames['multiAddressFrame'];
		if (!frame) {
			return;
		}

		var formFields = frame.FormField.GetValues(orderCustomFormFieldsShippingFormId);
		for (var i=0; i<formFields.length; i++) {
			var rtn = frame.FormField.Validate(formFields[i].field);
			if (!rtn.status) {
				alert(rtn.msg);
				frame.FormField.Focus(formFields[i].field);
				return;
			}
		}

		// this reaches into the content of the iframe but performs the post within the context of the top-level window's document
		$.ajax({
			url: 'remote.php?remoteSection=orders&w=editOrderSaveSplitShipping',
			data: Order_Form.getSerializedSection($('#multiAddressFrame').contents()),
			dataType: 'json',
			type: 'post',
			success: function(response){
				Order_Form.handleResponse(response);
			}
		});
	},

	cancelSelectItemDestinationClick: function (event) {
		event.preventDefault();
		Order_Form.cancelSelectItemDestination();
	},

	cancelSelectItemDestination: function () {
		$.iModal.close();
	}
};
