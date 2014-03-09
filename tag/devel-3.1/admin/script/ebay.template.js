;(function($){

// create and use `fsm` locally
var fsm = new Interspire_FSM();

// the init state
fsm.state('Init')
	.initial()
	.enter(function(ev, state) {
		state.machine.transition('InitForm')
	})
	.transition('InitForm', 'SelectEbaySite')
		.execute(function(){
			$('.TemplateMachine_BackButton').click(function(){
				fsm.transition('ClickBack');
			});

			$('.TemplateMachine_NextButton').click(function(){
				fsm.transition('ClickNext');
			});

			$('.TemplateMachine_CancelButton').click(function(){
				EbayTemplate.ConfirmCancel();
			});

			$('.TemplateMachine_SaveButton').click(function(){
				fsm.transition('ClickSave');
			});
		});

/**
* This state displays the form to select a site to list on, as well as entering the template name and description
*/
fsm.state('SelectEbaySite')
	.transition('ClickCancel', 'End')
	.from.transition('ClickNext', 'CheckTemplateName')
		.execute(function() {
			// template name been entered?
			if ($("#templateName").val() == '') {
				alert(lang.EnterTemplateName);
				$("#templateName").focus();
				return false;
			}

			// site selected?
			if (EbayTemplate.siteId == null) {
				alert(lang.SelectEbaySite);
				$("#siteId").focus();
				return false;
			}

			// was a primary category selected ?
			if (!EbayTemplate.primaryCategoryOptions.category_id) {
				alert(lang.EnterPrimaryCategory);
				return false;
			}
		});


/**
* Intermediate state to check if the template name is in use
*/
fsm.state('CheckTemplateName')
	.enter(function(ev, state) {
			var templateName = $("#templateName").val();

			// check if template name is in use
			$.ajax({
				url: 'remote.php',
				type: 'post',
				dataType: 'json',
				data: {
					remoteSection: 'ebay',
					w: 'checkTemplateName',
					templateName: templateName,
					templateId: EbayTemplate.templateId
				},
				success: function(data) {
					if (data && data.success) {
						fsm.transition('TemplateNameOK');
					}
					else {
						alert(data.message);
						$("#ebayTemplateName").focus();
						fsm.transition('TemplateNameBad');
					}
				},
				error: function() {
					alert(lang.UnknownTemplateNameError);
					$("#templateName").focus();
					fsm.transition('TemplateNameBad');
				}
			});
	})
	.transition('TemplateNameOK', 'LoadTemplateDetails')
	.from.transition('TemplateNameBad', 'SelectEbaySite');

/**
* Intermediate state that loads the template form and displays a loading message
*/
fsm.state('LoadTemplateDetails')
	.enter(function(ev, state){
		// if the category wasnt changed then we can just transition directly to the template details form
		if (!EbayTemplate.primaryCategoryChanged) {
			fsm.transition('TemplateFormLoaded');
			return;
		}

		$("#templateLoadError").html('').hide();

		// request the template details form via ajax
		$.ajax({
			url: 'remote.php',
			type: 'post',
			dataType: 'json',
			data: {
				remoteSection: 'ebay',
				w: 'loadTemplateForm',
				siteId: EbayTemplate.siteId,
				categoryOptions: EbayTemplate.primaryCategoryOptions,
				templateId: EbayTemplate.templateId
			},
			success: function(data) {
				if (data && data.success) {
					$('#templateDetailsContainer').html(data.listingTemplateHtml);
					EbayTemplate.primaryCategoryChanged = false;
					fsm.transition('TemplateFormLoaded');
				}
				else {
					$("#templateLoadError").html(data.message).show();
					fsm.transition('TemplateFormLoadFailed');
				}
			},
			error: function() {
				fsm.transition('TemplateFormLoadFailed');
			}
		});
	})
	.transition('TemplateFormLoaded', 'TemplateDetails')
	.from.transition('TemplateFormLoadFailed', 'TemplateDetailsFormNotLoaded');

fsm.state('TemplateDetails')
	.transition('ClickCancel', 'End')
	.from.transition('ClickBack', 'SelectEbaySite')
	.from.transition('ClickSave', 'SaveTemplate')
		.execute(function() {
			// validate the form
			if (EbayTemplate.Validate()) {
				EbayTemplate.Submit();
			}

			return false;
		});

fsm.state('TemplateDetailsFormNotLoaded')
	.transition('ClickBack', 'SelectEbaySite');

fsm.state('End')
	.enter(function(ev, state){
		state.machine.finish();
	});

$(fsm).bind('machine_finish', function(event, fsm){
	// the machine has finished, so make sure the modal is closed
	$.iModal.close();
});

$(fsm).bind('state_enter', function(event, state){
	// on entering any state, try showing a dialog element that relates to that state
	//console.debug('entering state: ' + state.name);
	$('.TemplateMachine_State_' + state.name).show();

	$("#EbayFormSteps li").removeClass('active');
	$('.TemplateMachine_State_' + state.name + '_Step').addClass('active');
});

$(fsm).bind('state_exit', function(event, state){
	// on exiting any state, try showing a dialog element that relates to that state
	//console.debug('exiting state: ' + state.name);
	$('.TemplateMachine_State_' + state.name).hide();
});

$(fsm).bind('transitions_change', function(event, fsm){
	// the transition available for the machine's current state have changed, check to see how buttons should be configured

	$('.TemplateMachine_SaveButton').enabled(fsm.can('ClickSave'));
	$('.TemplateMachine_BackButton').enabled(fsm.can('ClickBack'));
	$('.TemplateMachine_CancelButton').enabled(fsm.can('ClickCancel'));

	if (fsm.can('ClickSave')) {
		$('.TemplateMachine_NextButton').hide();
		$('.TemplateMachine_SaveButton').show();
	}
	else {
		$('.TemplateMachine_NextButton').show();
		$('.TemplateMachine_SaveButton').hide();
	}
});

// assign to global scope with proper name
Interspire_Ebay_TemplateMachine = fsm;

})(jQuery);


var EbayTemplate = {
	siteId: null,

	templateId: null,

	primaryCategoryOptions: {},
	secondaryCategoryOptions: {},

	primaryStoreCategoryOptions: {},
	secondaryStoreCategoryOptions: {},

	primaryCategoryChanged: true,

	sellingMethod: '',

	/**
	* Determine if the 'get it fast' option should be shown and enabled
	*/
	CheckGetItFastAvailable: function()
	{
		// buy it now must be enabled to enable get it fast
		if ($("#useBuyItNowPrice").attr('checked') || EbayTemplate.sellingMethod == 'FixedPriceItem') {
			$("#domesticYesGetItFast").removeAttr('disabled');
		}
		else {
			$("#domesticYesGetItFast").attr('disabled', 'disabled');
			$("#domesticYesGetItFast").removeAttr('checked');
		}

		// if any of the domestic services are expidited, show the get it fast option
		var getItFastVisible = false;
		if ($("#domesticShippingType").val() == 'Calculated') {
			var selector = '.domesticShippingServCalculatedType';
		}
		else {
			var selector = '.domesticShippingServFlatType';
		}
		$(selector + " :selected").each(function() {
			if ($(this).hasClass('ExpeditedService')) {
				getItFastVisible = true;
				return;
			}
		});

		if (getItFastVisible) {
			$("#domesticGetItFastRow").show();
		}
		else {
			$("#domesticGetItFastRow").hide();
		}

		// ensure handling time is enabled or disable correctly
		if ($("#domesticYesGetItFast").attr('checked') && getItFastVisible) {
			$("#handlingTime").attr('disabled', 'disabled');
		}
		else {
			$("#handlingTime").removeAttr('disabled');
		}
	},

	ConfirmCancel: function()
	{
		if(confirm(lang.ConfirmCancel)) {
			window.location = 'index.php?ToDo=viewEbay&currentTab=1';
		}
	},

	ResetTemplate: function()
	{
		EbayTemplate.siteId = null;

		EbayTemplate.primaryCategoryOptions = {};
		EbayTemplate.secondaryCategoryOptions = {};
		EbayTemplate.primaryStoreCategoryOptions = {};
		EbayTemplate.secondaryStoreCategoryOptions = {};

		$("#primaryCategoryLabel, #secondaryCategoryLabel, #primaryStoreCategoryLabel, #secondaryStoreCategoryLabel").html(lang.NoneSelected);
	},

	/**
	* Show the dialog to select a category for a specific type (ebay or store)
	*/
	ShowAddCategoryDialog: function(categoryType, isPrimaryCategory)
	{
		Interspire_Ebay_SelectCategoryMachine.start({categoryType: categoryType, isPrimaryCategory: isPrimaryCategory, siteId: EbayTemplate.siteId});
	},

	/**
	* Adds a selected category into the template
	*/
	AddCategory: function(categoryType, isPrimaryCategory, categoryOptions)
	{
		var categoryLabel;
		var selectedCategory;

		if (categoryType == 'ebay') {
			if (isPrimaryCategory) {
				EbayTemplate.primaryCategoryOptions = categoryOptions;
				categoryLabel = $("#primaryCategoryLabel");
				selectedCategory = $("#primaryCategory");

				EbayTemplate.primaryCategoryChanged = true;
			}
			else {
				EbayTemplate.secondaryCategoryOptions = categoryOptions;
				categoryLabel = $("#secondaryCategoryLabel");
				selectedCategory = $("#secondaryCategory");
			}
		}
		else {
			if (isPrimaryCategory) {
				EbayTemplate.primaryStoreCategoryOptions = categoryOptions;
				categoryLabel = $("#primaryStoreCategoryLabel");
				selectedCategory = $("#primaryStoreCategory");
			}
			else {
				EbayTemplate.secondaryStoreCategoryOptions = categoryOptions;
				categoryLabel = $("#secondaryStoreCategoryLabel");
				selectedCategory = $("#secondaryStoreCategory");
			}
		}

		categoryLabel.html(categoryOptions.path);
		selectedCategory.val(categoryOptions.category_id);
	},

	UpdateCategoryFeaturesList: function(categoryFeaturesList)
	{
		$("#categoryFeaturesList").html(categoryFeaturesList).parents('div').show();
	},

	/**
	* Validate the entire template form
	*/
	Validate: function()
	{
		if (!EbayTemplate.CheckValidationResult(EbayTemplate.ValidateGeneralTab(), $("#generalTab"))) {
			return false;
		}

		if (!EbayTemplate.CheckValidationResult(EbayTemplate.ValidatePaymentTab(), $("#paymentTab"))) {
			return false;
		}

		if (!EbayTemplate.CheckValidationResult(EbayTemplate.ValidateShippingTab(), $("#shippingTab"))) {
			return false;
		}

		if (!EbayTemplate.CheckValidationResult(EbayTemplate.ValidateOtherTab(), $("#otherTab"))) {
			return false;
		}

		return true;
	},

	CheckValidationResult: function(result, tab)
	{
		if (typeof result == 'boolean') {
			if (!result) {
				tab.click();
			}
			return result;
		}

		tab.click();
		result.control.focus().select();
		alert(result.message);

		return false;
	},

	ValidateGeneralTab: function()
	{
		var intFilter = /^[0-9]+$/;

		// quantity
		if ($("input[name='quantityType']:checked").val() == 'more' && !intFilter.test($('#quantityMore').val())) {
			return {control: $("#quantityMore"), message: lang.EnterQuantity};
		}

		// City/State
		if ($('#locationCityState').val() == '') {
			return {control: $('#locationCityState'), message: lang.EnterCityStateDetails};
		}

		// Item Postal Code
		if ($('#locationZip').val() == '') {
			return {control: $('#locationZip'), message: lang.EnterZipPostcodeDetails};
		}

		// validate auction tab
		if (EbayTemplate.sellingMethod == 'Chinese') {
			var validateResult = EbayTemplate.ValidateAuctionMethod();
			if (validateResult !== true)	{
				return validateResult;
			}
		}
		// validate fixed price tab
		else if (EbayTemplate.sellingMethod == 'FixedPriceItem') {
			var validateResult = EbayTemplate.ValidateFixedPriceMethod();
			if (validateResult !== true)	{
				return validateResult;
			}
		}
		else {
			alert(lang.ChooseSellingMethod);
			return false;
		}

		return true;
	},

	ValidateAuctionMethod: function()
	{
		var floatFilter = /^[0-9\.,]*[\.,]?[0-9]+$/;

		// reserve price used?
		if (EbayTemplate.primaryCategoryOptions.reserve_price_allowed && $("#useReservePrice").attr('checked')) {
			var reservePriceOption = $("input[name='reservePriceOption']:checked").val();

			// using prod price + extra
			if (reservePriceOption == 'PriceExtra') {
				if (!floatFilter.test($("#reservePricePlusValue").val())) {
					return {control: $('#reservePricePlusValue'), message: lang.EnterReservePrice};
				}
			}
			// using custom price
			else if (reservePriceOption == 'CustomPrice') {
				if (!floatFilter.test($("#reservePriceCustomValue").val())) {
					return {control: $('#reservePriceCustomValue'), message: lang.EnterReservePrice};
				}
				else if (parseFloat($("#reservePriceCustomValue").val()) < EbayTemplate.primaryCategoryOptions.minimum_reserve_price) {
					return {control: $('#reservePriceCustomValue'), message: lang.MinimumReserveNotMet + EbayTemplate.primaryCategoryOptions.minimum_reserve_price};
				}
			}
		}

		// start price
		var startPriceOption = $("input[name='startPriceOption']:checked").val();
		// using prod price + extra
		if (startPriceOption == 'PriceExtra') {
			if (!floatFilter.test($("#startPricePlusValue").val())) {
				return {control: $('#startPricePlusValue'), message: lang.EnterStartPrice};
			}
		}
		// using custom price
		else if (startPriceOption == 'CustomPrice') {
			if (!floatFilter.test($("#startPriceCustomValue").val())) {
				return {control: $('#startPriceCustomValue'), message: lang.EnterStartPrice};
			}
		}

		// buy it now price
		if ($("#useBuyItNowPrice").attr('checked')) {
			var buyItNowPriceOption = $("input[name='buyItNowPriceOption']:checked").val();
			// using prod price + extra
			if (buyItNowPriceOption == 'PriceExtra') {
				if (!floatFilter.test($("#buyItNowPricePlusValue").val())) {
					return {control: $('#buyItNowPricePlusValue'), message: lang.EnterBuyItNowPrice};
				}
			}
			// using custom price
			else if (buyItNowPriceOption == 'CustomPrice') {
				if (!floatFilter.test($("#buyItNowPriceCustomValue").val())) {
					return {control: $('#buyItNowPriceCustomValue'), message: lang.EnterBuyItNowPrice};
				}
				// if using a custom start price as well, we can check that the buy it now price is at least 10% higher than the start price
				if (startPriceOption == 'CustomPrice') {
					var binPrice = parseFloat($("#buyItNowPriceCustomValue").val());
					var startPrice =  parseFloat($("#startPriceCustomValue").val());

					if (binPrice < (startPrice * 1.1)) {
						return {control: $('#buyItNowPriceCustomValue'), message: lang.BuyItNowPriceTooLow};
					}
				}
			}
		}

		return true;
	},

	ValidateFixedPriceMethod: function()
	{
		var floatFilter = /^[0-9\.,]*[\.,]?[0-9]+$/;

		var fixedBuyItNowPriceOption = $("input[name='fixedBuyItNowPriceOption']:checked").val();
		// using prod price + extra
		if (fixedBuyItNowPriceOption == 'PriceExtra') {
			if (!floatFilter.test($("#fixedBuyItNowPricePlusValue").val())) {
				return {control: $('#fixedBuyItNowPricePlusValue'), message: lang.EnterBuyItNowPrice};
			}
		}
		// using custom price
		else if (fixedBuyItNowPriceOption == 'CustomPrice') {
			if (!floatFilter.test($("#fixedBuyItNowPriceCustomValue").val())) {
				return {control: $('#fixedBuyItNowPriceCustomValue'), message: lang.EnterBuyItNowPrice};
			}
		}

		return true;
	},

	ValidatePaymentTab: function()
	{
		var payPalRequired = EbayTemplate.primaryCategoryOptions.paypal_required;

		// ensure payment methods are selected
		if ($("input[name='paymentMethods[]']:checked").length == 0 && !payPalRequired) {
			alert(lang.SelectPaymentMethod);
			return false;
		}

		// check if a paypal email address is needed
		if (($("#paymentMethod_PayPal").attr('checked') || payPalRequired) &&
			$("#paypalEmailAddress").val() == ''
			) {
			return {control: $('#paypalEmailAddress'), message: lang.EnterPayPalEmail};
		}

		return true;
	},

	ValidateShippingTab: function()
	{
		var floatFilter = /^[0-9\.,]*[\.,]?[0-9]+$/;

		var validateResult;

		validateResult = EbayTemplate.ValidateDomesticShipping();
		if (validateResult !== true) {
			$("#domesticTab").click();
			return validateResult;
		}

		validateResult = EbayTemplate.ValidateInternationalShipping();
		if (validateResult !== true) {
			$("#internationalTab").click();
			return validateResult;
		}

		// check sales tax (may not exist)
		var salesTaxOption = $("input[name='salesTax']:checked").val();
		if (salesTaxOption == '1') {
			if (!floatFilter.test($("#salesTaxPercentage").val())) {
				return {control: $('#salesTaxPercentage'), message: lang.EnterSalesTaxPercentage};
			}
		}

		return true;
	},

	ValidateDomesticShipping: function()
	{
		// local pickup only? no validation needed
		if ($("input[name='domesticShipping']:checked").val() == 'pickup') {
			return true;
		}

		return EbayTemplate.ValidateShippingType('domestic', $("#domesticShippingType").val());

	},

	ValidateInternationalShipping: function()
	{
		// not using international shipping? no validation needed
		if (!$("#yesInternationalShipping").attr('checked')) {
			return true;
		}

		return EbayTemplate.ValidateShippingType('international', $("#internationalShippingType").val());
	},

	ValidateShippingType: function(shippingArea, shippingType)
	{
		var floatFilter = /^[0-9\.,]*[\.,]?[0-9]+$/;
		var valid = true;
		var control;

		// check that a ship to location is set (international)
		if (shippingArea == 'international') {
			$('.' + shippingArea + 'ShippingServ' + shippingType + 'ShipTo').each(function() {
				if($(this).val() == '') {
					control = $(this);
					valid = false;
					return false;
				}
			});

			if (!valid) {
				return {control: control, message: lang.EnterShipToLocation};
			}
		}

		// check that a shipping service is selected
		$('.' + shippingArea + 'ShippingServ' + shippingType + 'Type').each(function() {
			if($(this).val() == '') {
				control = $(this);
				valid = false;
				return false;
			}
		});

		if (!valid) {
			return {control: control, message: lang.EnterShippingService};
		}

		valid = true;

		// validate cost for flat shipping
		if (shippingType == 'Flat') {
			$('.' + shippingArea + 'ShippingServFlatCost').each(function(index) {
				// if the domestic free shipping option is enabled, don't validate the first row
				if (shippingArea == 'domestic' && $("#domesticYesFreeFlatShipping").attr('checked') && index == 0) {
					return;
				}

				if($(this).val() == '' || !floatFilter.test($(this).val())) {
					control = $(this);
					valid = false;
					return false;
				}
			});

			if (!valid) {
				return {control: control, message: lang.EnterShippingServiceCost};
			}
		} else {
			if ($('#' + shippingArea + 'HandlingCost').val() != '' && !floatFilter.test($('#' + shippingArea + 'HandlingCost').val())) {
				return {control: $('#' + shippingArea + 'HandlingCost'), message: lang.EnterHandlingCost};
			}
		}

		return true;
	},

	ValidateOtherTab: function()
	{
		var intFilter = /^[0-9]+$/;

		// lot size
		if (EbayTemplate.primaryCategoryOptions.lot_size_enabled) {
			if ($("#lotSize").val() != '' && !intFilter.test($("#lotSize").val())) {
				return {control: $("#lotSize"), message: lang.EnterLotSize};
			}
		}

		return true;
	},

	Submit: function()
	{
		// prepare form data
		var formData = {
			'siteId': EbayTemplate.siteId,
			'templateId': EbayTemplate.templateId,
			'primaryCategoryOptions': EbayTemplate.primaryCategoryOptions,
			'secondaryCategoryOptions': EbayTemplate.secondaryCategoryOptions,
			'primaryStoreCategoryOptions': EbayTemplate.primaryStoreCategoryOptions,
			'secondaryStoreCategoryOptions': EbayTemplate.secondaryStoreCategoryOptions,
			'sellingMethod': EbayTemplate.sellingMethod,
			'remoteSection': 'ebay',
			'w': 'saveTemplate'
		};

		var formOptions =  $("#templateForm").serializeArray();
		$.each(formOptions, function(i, field) {
			formData[field.name] = field.value;
		});


		// save the template
		$.ajax({
			url: 'remote.php',
			type: 'post',
			dataType: 'json',
			data: formData,
			success: function(data) {
				if (data && data.success) {
					document.location = 'index.php?ToDo=viewEbay&currentTab=1';
				}
				else {
					alert(data.message);
				}
			},
			error: function() {
				alert(lang.UnknownSavingTemplateError);
			}
		});
	}
}

$(document).ready(function() {
	Interspire_Ebay_TemplateMachine.start();
});
