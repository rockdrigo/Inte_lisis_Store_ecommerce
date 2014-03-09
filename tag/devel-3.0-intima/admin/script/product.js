
// Are we selling a digital product? By default we're not
var sellingDP = false;
var productCheckedDimensions = false;

if(ReadCookie('DontShowDimensionsCheck') == 'yes') {
	productCheckedDimensions = true;
}

function ShowTab(T)
{
		i = 0;
		while (document.getElementById("tab" + i) != null) {
			$('#div'+i).hide();
			$('#tab'+i).removeClass('active');
			++i;
		}

		if (T == 2) {
			$('#SaveButtons').hide();
		} else {
			$('#SaveButtons').show();
		}

		// Bulk Discount checks
		if (T == 7) {
			// Are we enabled?
			if (shop.config.DiscountRulesEnabled !== "1") {
				$('#DiscountRulesWarning').text(lang.DiscountRulesNotEnabledWarning);
				$('#DiscountRulesWarning').show();
				$('#DiscountRulesDisplay').hide();

			// Else check to see if we have variations when we switch to the discount rules tab
			} else if (document.getElementById('useProdVariationYes').checked) {
				$('#DiscountRulesWarning').text(lang.DiscountRulesVariationWarning);
				$('#DiscountRulesWarning').show();
				$('#DiscountRulesDisplay').hide();
			} else {
				$('#DiscountRulesWarning').hide();
				$('#DiscountRulesDisplay').show();
			}
		}

		$('#div'+T).show();
		$('#tab'+T).addClass('active');
		$('#currentTab').val(T);
		document.getElementById("currentTab").value = T;
}

function ToggleProductInventoryOptions(YesNo)
{
	if(YesNo) {
		document.getElementById("divTrackProd").style.display = "";
	}
	else {
		document.getElementById("divTrackProd").style.display = "none";
	}
}

function GetProducts(selObj)
{
	// Clear the products list
	document.getElementById("relProducts").options.length = 0;

	if(selObj.selectedIndex > -1) {
		// Get the products using AJAX
		var cat = document.getElementById("relCategory").options[document.getElementById("relCategory").selectedIndex].value;
		window.status = lang.PleaseWaitRelated;
		DoCallback("w=relatedProducts&c="+cat);
		window.status = "";
	}
}

function ProcessData(html)
{
	var arrProds = html.split("|||");
	var s = document.getElementById("relProducts");

	if(arrProds.length > 1) {
		for(i = 0; i < arrProds.length; i++) {
			o = arrProds[i].split("~~~");

			if(o[1] != undefined) {
				s.options[s.options.length] = new Option(o[1], o[0]);
			}
		}
	}
	else {
		// No products
		s.options[s.options.length] = new Option(lang.NoProdsInCat, 0);
	}
}

function AddRelatedProduct(selObj)
{
	var p = document.getElementById("related");
	var i = selObj.selectedIndex;

	if(i > -1) {
		var v = selObj.options[selObj.selectedIndex].value;
		var t = selObj.options[selObj.selectedIndex].text;

		if(v == 0) {
			alert(lang.PleaseChooseAProduct);
		}
		else {
			// Make sure the product isn't already in the list
			for(i = 0; i < p.options.length; i++) {
				if(p.options[i].value == v) {
					alert(lang.ProdAlreadyInRelatedList);
					return false;
				}
			}

			p.options[p.options.length] = new Option(t, v);
		}
	}
}

function SelectAllRelatedProducts()
{
	// Select all related products
	var p = document.getElementById("related");

	for(i = 0; i < p.options.length; i++)
		p.options[i].selected = true;
}

function RemoveRelatedProduct(selObj)
{
	if(selObj.selectedIndex > -1)
		selObj.options[selObj.selectedIndex] = null;

	// Select all related products
	if(selObj.options.length > 0) {
		for(i = 0; i < selObj.options.length; i++)
			selObj.options[i].selected = true;
	}
}

function CheckProductFields()
{
	var badNode = null;
	var message = '';
	var prodFieldContainer
	$('#ProductFieldsContainer').find('.ConfigField').each(function() {
		if(message != '') {
			return;
		}
		var fieldName = $(this).find('.productFieldName');
		var fieldType = $(this).find('.productFieldType');
		var fieldRequired = $(this).find('.productFieldRequired');
		var fieldFileType = $(this).find('.productFieldFileType');
		var fieldFileSize = $(this).find('.productFieldFileSize');

		if(fieldName.val() == '' || fieldName.is('.FieldHelp')) {
			if(fieldType.val() != 'text' || fieldRequired.attr('checked') == true) {
				badNode = fieldName;
				message = lang.EnterProductFieldName;
				return;
			} else {
				fieldName.attr('value', '');
			}

		}

		if(fieldType.val() == 'file' && (fieldFileType.val() == '' || fieldFileType.is('.FieldHelp'))) {
			badNode = fieldFileType;
			message = lang.EnterProductFieldFileType;
			return;
		}


		if(fieldType.val() == 'file' && (fieldFileSize.val() == '' || fieldFileSize.is('.FieldHelp'))) {
			badNode = fieldFileSize;
			message = lang.EnterProductFieldFileSize;
			return;
		}

		if(fieldType.val() == 'file' && (fieldFileSize.val() % 1) != 0) {
			badNode = fieldFileSize;
			message = lang.EnterValidProductFieldFileSize;
			return;
		}

	});

	if(message != '') {
		ShowTab(5);
		if(badNode != null) {
			badNode.focus();
		}
		alert(message);
		return false;
	}
	return true;
}


function CheckedDimensionsContinue() {
	if($('#DontShowDimensionsCheck').is(':checked')) {
		SetCookie('DontShowDimensionsCheck', 'yes', 365);
	}

	productCheckedDimensions = true;
	$.iModal.close();
	$('#frmProduct').submit();
}

function CheckAddProductForm() {

	// Go through each required field and make sure it is complete
	var sellingDP = document.getElementById("ProductType_1").checked;
	var prodName = document.getElementById("prodName");

	var category = document.getElementById("category");
	var prodSummary = document.getElementById("prodSummary");

	if (document.getElementById("wysiwyg")) {
		var wysiwyg = document.getElementById("wysiwyg"); // Text area
	} else {
		var wysiwyg = document.getElementById("wysiwyg_html"); // DevEdit
	}

	var prodPrice = document.getElementById("prodPrice");
	var prodCostPrice = document.getElementById("prodCostPrice");
	var prodRetailPrice = document.getElementById("prodRetailPrice");
	var prodSalePrice = document.getElementById("prodSalePrice");
	var prodSortOrder = document.getElementById("prodSortOrder");
	var prodWeight = document.getElementById("prodWeight");
	var prodWidth = document.getElementById("prodWidth");
	var prodHeight = document.getElementById("prodHeight");
	var prodDepth = document.getElementById("prodDepth");
	var prodFixedCost = document.getElementById("prodFixedCost");
	var prodInvTrack_1 = document.getElementById("prodInvTrack_1");
	var prodCurrentInv = document.getElementById("prodCurrentInv");
	var prodLowInv = document.getElementById("prodLowInv");
	var prodInvTrack_2 = document.getElementById("prodInvTrack_2");
	var eventdatename = document.getElementById('EventDateFieldName');
	var eventdaterequired = document.getElementById('EventDateRequired');
	var eventdaterequired = document.getElementById('EventDateRequired');
	var eventdatetype = $('#LimitDatesSelect').val();
	// Check the "Discount Rules" tab
	var badNode = null;
	var badError = '';
	var badRow = 0;
	var overlapping = [[], []];
	var prevMax = null;

	if(isNaN(dimensionsFormat(prodWidth.value)) && prodWidth.value != "" && !sellingDP) {
		ShowTab(0);
		alert(lang.EnterWidth);
		prodWidth.focus();
		prodWidth.select();
		return false;
	}

	if(isNaN(dimensionsFormat(prodHeight.value)) && prodHeight.value != "" && !sellingDP) {
		ShowTab(0);
		alert(lang.EnterHeight);
		prodHeight.focus();
		prodHeight.select();
		return false;
	}

	if(isNaN(dimensionsFormat(prodDepth.value)) && prodDepth.value != "" && !sellingDP) {
		ShowTab(0);
		alert(lang.EnterDepth);
		prodDepth.focus();
		prodDepth.select();
		return false;
	}

	// Is this a physical product without all of the required shipping dimensions?
	// If so we'll warn the customer that shipping calculations might be wrong
	// without all of the dimensions

	if(!productCheckedDimensions){
		if(!sellingDP &&
		  ((prodWidth.value == "" || isNaN(priceFormat(prodWidth.value))) ||
		  (prodHeight.value == "" || isNaN(priceFormat(prodHeight.value))) ||
		  (prodDepth.value == ""  || isNaN(priceFormat(prodDepth.value))))) {
			$.iModal({
				type: 'inline',
				inline: '#AddProductWithEmptyDimensions',
				width: 450
			});
			return false;
		}
	}


	$('#DiscountRulesContainer tr').each(function() {
		badRow++;

		if (badNode !== null) {
			return;
		}

		// Check to see if this is an empty record
		var empty = true;
		$('input', this).each(function() {
			if (!empty) {
				return;
			}

			if ($(this).val() !== '') {
				empty = false;
			}
		});

		// If we're empty then don't validate it
		if (empty) {
			return;
		}

		// Else we validate
		var matches, rowId = null;

		$(':input', this).each(function() {

			// Found out our id for this row
			if (rowId == null) {
				matches = $(this).attr('name').match(/\[([0-9]+)\]$/);
				rowId = parseInt(matches[1]);
			}

			if (badNode !== null) {
				return;
			}

			// Minimum quantity
			if ($(this).attr('id').substring(0, 25) == 'discountRulesQuantityMin_') {
				if ($(this).val() == '') {
					badNode = this;
					badError = lang.DiscountRulesQuantityMinRequired;
				} else if ((!isNaN($(this).val()) && $(this).val() <= 0) || (isNaN($(this).val()) && $(this).val()!== '*')) {
					badNode = this;
					badError = lang.DiscountRulesQuantityMinInvalid;
				}
			}

			// Maximum quantity
			if ($(this).attr('id').substring(0, 25) == 'discountRulesQuantityMax_') {
				if ($(this).val() == '') {
					badNode = this;
					badError = lang.DiscountRulesQuantityMaxRequired;
				} else if ((!isNaN($(this).val()) && $(this).val() <= 0) || (isNaN($(this).val()) && $(this).val()!== '*')) {
					badNode = this;
					badError = lang.DiscountRulesQuantityMaxInvalid;
				}
			}

			// Type
			if ($(this).attr('id').substring(0, 18) == 'discountRulesType_') {
				if ($(this).val() == '') {
					badNode = this;
					badError = lang.DiscountRulesTypeRequired;
				} else if ($(this).val().toLowerCase() !== 'price' && $(this).val().toLowerCase() !== 'percent' && $(this).val().toLowerCase() !== 'fixed') {
					badNode = this;
					badError = lang.DiscountRulesTypeInvalid;
				}
			}

			// Amount
			if ($(this).attr('id').substring(0, 20) == 'discountRulesAmount_') {
				if ($(this).val() == '') {
					badNode = this;
					badError = lang.DiscountRulesAmountRequired;
				} else if (isNaN(priceFormat($(this).val())) && isNaN($(this).val())) {
					badNode = this;
					badError = lang.DiscountRulesAmountInvalid;
				} else {

					// Else we have a look at the value to compare it with the product price
					switch (document.getElementById('discountRulesType_' + rowId).value.toLowerCase())
					{
						case 'price':
							// Is our price discount higher than the actual product price?
							if (parseFloat($(this).val()) >= parseFloat($('#prodPrice').val())) {
								badNode = this;
								badError = lang.DiscountRulesAmountPriceInvalid;
							}
							break;

						case 'percent':
							// Is the discount percentage is 100 or above?
							if (parseInt($(this).val()) >= 100) {
								badNode = this;
								badError = lang.DiscountRulesAmountPercentInvalid;
							} else if ($(this).val().indexOf('.') !== -1) {
								badNode = this;
								badError = lang.DiscountRulesAmountPercentIsFloat;
							}
							break;

						case 'fixed':
							// Is the fixed rate higher than the actual product price? This check is just for their sake
							if (parseFloat($(this).val()) >= parseFloat($('#prodPrice').val())) {
								badNode = this;
								badError = lang.DiscountRulesAmountFixedInvalid;
							}
							break;
					}
				}
			}

			// Fix up our error message
			if (badError !== '') {
				badError = badError.replace(/\%d/, badRow);
			}
		});

		// Some error check per row based instead of the above input based
		if (badNode == null) {

			var minQuantity = document.getElementById('discountRulesQuantityMin_' + rowId).value;
			var maxQuantity = document.getElementById('discountRulesQuantityMax_' + rowId).value;

			// Now we need to check the min and max quantities to see if min is lower and max is higher
			if (minQuantity !== '*' && maxQuantity !== '*' && parseInt(minQuantity) > parseInt(maxQuantity)) {
				badError = lang.DiscountRulesQuantityMinHigher;
				badNode = document.getElementById('discountRulesQuantityMin_' + rowId);

			// Else check to see if they have put in an astrix for both min and max values
			} else if (minQuantity == '*' && maxQuantity == '*') {
				badError = lang.DiscountRulesQuantityBothAstrix;
				badNode = document.getElementById('discountRulesQuantityMin_' + rowId);

			// Next we check to see if the current min and the previous max quantities are both astrixes
			} else if (prevMax !== null && prevMax == '*' && minQuantity == '*') {
				badError = lang.DiscountRulesQuantityMinPrevMaxAstrix;
				badNode = document.getElementById('discountRulesQuantityMin_' + rowId);

			// Now we check for overlapping
			} else if (minQuantity !== '*' && CheckNumericOverlapping(minQuantity, overlapping) == 1) {
				badError = lang.DiscountRulesQuantityMinOverlap;
				badNode = document.getElementById('discountRulesQuantityMin_' + rowId);
			} else if (maxQuantity !== '*' && CheckNumericOverlapping(maxQuantity, overlapping) == 1) {
				badError = lang.DiscountRulesQuantityMaxOverlap;
				badNode = document.getElementById('discountRulesQuantityMax_' + rowId);
			}

			// Check those values for our next loop
			if (minQuantity !== '*') {
				overlapping[0][overlapping[0].length] = minQuantity;
			} else {
				overlapping[0][overlapping[0].length] = '';
			}

			if (maxQuantity !== '*') {
				overlapping[1][overlapping[1].length] = maxQuantity;
			} else {
				overlapping[1][overlapping[1].length] = '';
			}

			prevMax = maxQuantity;

			if (badNode !== null) {
				badError = badError.replace(/\%d/, badRow);
			}
		}
	});

	if (badNode !== null) {
		ShowTab(7);
		alert(badError);
		badNode.focus();
		return false;
	}

	if (prodName.value == "") {
		ShowTab(0);
		alert(lang.EnterProdName);
		prodName.focus();
		return false;
	}

	if ($('#_prodorderable_pre').attr('checked')) {
		var releaseDate = $('#prodreleasedate').val();
		if ($('#prodreleasedateremove').attr('checked') && !releaseDate) {
			ShowTab(0);
			$('#prodreleasedateremove').focus();
			alert(lang.PleaseChooseAReleaseDate);
			return false;
		} else if (releaseDate) {
			releaseDate = releaseDate.split('/');
			releaseDate = new Date(parseInt(releaseDate[2], 10), parseInt(releaseDate[0], 10) - 1, parseInt(releaseDate[1], 10), 0, 0, 0);
			var now = new Date();
			if (now >= releaseDate) {
				ShowTab(0);
				$('#prodreleasedateremove').focus();
				alert(lang.PleaseChooseAReleaseDateInTheFuture);
				return false;
			}
		}
	}


	// Check the "Product Details" tab
	if (category.selectedIndex == -1) {
		ShowTab(0);
		if(shop.config.NoCategoriesJS == 'true') {
			if(confirm(lang.MustCreateCategoryFirst)) {
				CreateNewCategory();
				return false;
			}
		}
		alert(lang.ChooseCategory);
		category.focus();
		return false;
	}

	if (isNaN(priceFormat(prodPrice.value)) || prodPrice.value == "") {
		ShowTab(0);
		alert(lang.EnterPrice);
		prodPrice.focus();
		prodPrice.select();
		return false;
	}

	if (prodCostPrice.value != "") {
		if (isNaN(priceFormat(prodCostPrice.value))) {
			if (document.getElementById("tr_costprice").style.display == "none") {
				toggle_price_options();
			}

			ShowTab(0);
			alert(lang.EnterCostPrice);
			prodCostPrice.focus();
			prodCostPrice.select();
			return false;
		}
	}

	if (prodRetailPrice.value != "") {
		if(isNaN(priceFormat(prodRetailPrice.value))) {
			if(document.getElementById("tr_retailprice").style.display == "none") {
				toggle_price_options();
			}

			ShowTab(0);
			alert(lang.EnterRetailPrice);
			prodRetailPrice.focus();
			prodRetailPrice.select();
			return false;
		}
	}

	if(prodSalePrice.value != "") {
		if(isNaN(priceFormat(prodSalePrice.value))){
			if(document.getElementById("tr_saleprice").style.display == "none") {
				toggle_price_options();
			}

			ShowTab(0);
			alert(lang.EnterSalePrice);
			prodSalePrice.focus();
			prodSalePrice.select();
			return false;
		}
	}

	if((isNaN(dimensionsFormat(prodWeight.value)) || prodWeight.value == "") && !sellingDP) {
		ShowTab(0);
		alert(lang.EnterWeight);
		prodWeight.focus();
		prodWeight.select();
		return false;
	}

	if(prodFixedCost.value != "") {
		if(isNaN(priceFormat(prodFixedCost.value))) {
			ShowTab(0);
			alert(lang.EnterFixedShipping);
			prodFixedCost.focus();
			prodFixedCost.select();
			return false;
		}
	}

	// Check the product downloads tab
	var f = g('ProductType_1');
	if(f.checked) {
		var empty = true;
		var f = document.getElementById('ExistingDownloadsGrid').getElementsByTagName('tr');
		var collapse = true;
		for(var i = 0; i < f.length; i++) {
			if(f[i].className && f[i].className.indexOf('DownloadGridRow') != -1) {
				var empty = false;
			}
		}
		if(empty == true && !$('#NewDownloadFile').val()) {
			ShowTab(2);
			$('#NewDownloadFile').focus();
			alert(lang.ProductHasNoDownloads);
			return false;
		}
		if(!validateNewDownload() && $('#NewDownloadFile').val()) {
			return false;
		}
	}


	// Inventory tracking options
	if(prodInvTrack_1.checked) {
		// Tracking per product
		if(isNaN(priceFormat(prodCurrentInv.value)) || prodCurrentInv.value == "") {
			ShowTab(3);
			alert(lang.EnterCurrentInventory);
			prodCurrentInv.focus();
			prodCurrentInv.select();
			return false;
		}

		if(isNaN(priceFormat(prodLowInv.value)) || prodLowInv.value == "") {
			ShowTab(3)
			alert(lang.EnterLowInventory);
			prodLowInv.focus();
			prodLowInv.select();
			return false;
		}
	}

	// Check the "Product Options" tab
	if(prodInvTrack_2.checked) {
		// Track per variation - make sure they've selected a variation
		if(g('variationList').style.display == 'none' || g('variationId').selectedIndex == 0) {
			ShowTab(4);
			alert(lang.ChooseProductVariation);
			return false;
		}
	}

	if(g('useProdVariationYes').checked && g('variationId').selectedIndex == 0) {
		ShowTab(4);
		alert(lang.VariationChooseVariation);
		g('variationId').focus();
		return false;
	}

	if(g('variationId').selectedIndex > 0) {
		// Make sure there are valid price/weight/stock values if variation tracking is enabled
		var err = false;

		// Check the price fields
		$('.CombinationRow').each(function() {
			if($(this).find('.PriceDrop').val() != '') {
				var p = $(this).find('.PriceBox');
				if(p.val() == '' || isNaN(priceFormat(p.val()))) {
					ShowTab(4);
					alert(lang.VariationEnterValidPrice);
					p.focus();
					p.select();
					err = true;
					return false;
				}
			}

			// Check the weight fields
			if($(this).find('.WeightDrop').val() != '') {
				var p = $(this).find('.WeightBox');
				if(p.val() == '' || isNaN(priceFormat(p.val()))) {
					ShowTab(4);
					alert(lang.VariationEnterValidWeight);
					p.focus();
					p.select();
					err = true;
					return false;
				}
			}

			// Check the option's image
			if($(this).find('.OptionImage').val() != '') {
				var i = $(this).find('.OptionImage');
				var image_loc = i.val().split('.');
				var image_ext = image_loc[image_loc.length-1].toLowerCase();

				if(image_ext != 'gif' && image_ext != 'jpg' && image_ext != 'jpeg' && image_ext != 'png') {
					ShowTab(4);
					alert(lang.VariationEnterValidImage);
					i.focus();
					i.select();
					err = true;
					return false;
				}
			}

			// Check the "stock level" fields
			var s = $(this).find('.StockLevel');

			if(s.val() == '' || isNaN(priceFormat(s.val()))) {
				ShowTab(4);
				alert(lang.VariationEnterValidStockLevel);
				s.focus();
				s.select();
				err = true;
				return false;
			}

			// Check the "low stock level" fields
			var s = $(this).find('.LowStockLevel');

			if(s.val() == '' || isNaN(priceFormat(s.val()))) {
				ShowTab(4);
				alert(lang.VariationEnterValidLowStockLevel);
				s.focus();
				s.select();
				err = true;
				return false;
			}
		});
	}

	if(err) {
		return false;
	}

	// Check the "Custom Fields" tab
	var badNode = null;
	$('#CustomFieldsContainer tr').each(function() {
		if (badNode !== null) {
			return;
		}

		if (document.getElementById('customFieldValue['+this.rowIndex+']').value !== '' && document.getElementById('customFieldName['+this.rowIndex+']').value == '') {
			badNode = document.getElementById('customFieldName['+this.rowIndex+']');
		}
	});

	if (badNode !== null) {
		ShowTab(5);
		alert(lang.EnterCustomFieldName);
		badNode.focus();
		return false;
	}

	// Check the "Other Details" tab

	if($('#prodwraptype_custom:checked').val() && !$('#prodwrapoptions_old').val()) {
		ShowTab(6);
		alert(lang.SelectOneMoreWrapOptions);
		$('#prodwrapoptions').focus();
		return false;
	}

	if(isNaN(prodSortOrder.value)) {
		ShowTab(6);
		alert(lang.EnterSortOrder);
		prodSortOrder.focus();
		prodSortOrder.select();
		return false;
	}

	if(!CheckProductFields()) {
		return false;
	}

	if (eventdaterequired.checked == true) {
		if (eventdatename.value == '') {
			ShowTab(1);
			alert(lang.EnterEventDateName);
			eventdatename.focus();
			eventdatename.select();
			return false;
		}

		if (eventdatetype == 1) {
			var fday = $("#LimitDates1 :input[name='Calendar1[From][Day]']").val();
			var fmonth = $("#LimitDates1 :input[name='Calendar1[From][Mth]']").val();
			var fyear = $("#LimitDates1 :input[name='Calendar1[From][Yr]']").val();

			var tday = $("#LimitDates1 :input[name='Calendar1[To][Day]']").val();
			var tmonth = $("#LimitDates1 :input[name='Calendar1[To][Mth]']").val();
			var tyear = $("#LimitDates1 :input[name='Calendar1[To][Yr]']").val();

			if (new Date(fyear+'/'+fmonth+'/'+fday) >
			new Date(tyear+'/'+tmonth+'/'+tday)) {
				ShowTab(1);
				alert(lang.EnterEventDateRange);
				return false;
			}
		}
	}

	//validate google optimzer form
	if ($('#prodEnableOptimizer').attr('checked')) {
		if(!Optimizer.ValidateConfigForm(ShowTab,'8')) {
			return false;
		}
	}

	// validate minimum / maximum quantities
	var prodminqty = $('#prodminqty').val().replace(/^\s+|\s+$/g, '');
	var prodmaxqty = $('#prodmaxqty').val().replace(/^\s+|\s+$/g, '');

	if (prodminqty == '') {
		prodminqty = Number.NEGATIVE_INFINITY;
	} else {
		prodminqty = parseInt(prodminqty, 10);
		if (prodminqty == 0) {
			prodminqty = Number.NEGATIVE_INFINITY;
		} else if (isNaN(prodminqty) || prodminqty < 0) {
			ShowTab(6);
			alert(lang.ProductMinimumError);
			$('#prodminqty').focus().select();
			return false;
		}
	}

	if (prodmaxqty == '') {
		prodmaxqty = Number.POSITIVE_INFINITY;
	} else {
		prodmaxqty = parseInt(prodmaxqty, 10);

		if (prodmaxqty == 0) {
			prodmaxqty = Number.POSITIVE_INFINITY;
		} else if (isNaN(prodmaxqty) || prodmaxqty < 0) {
			ShowTab(6);
			alert(lang.ProductMaximumError);
			$('#prodmaxqty').focus().select();
			return false;
		}
	}

	if (prodminqty > prodmaxqty) {
		ShowTab(6);
		alert(lang.ProductMinimumMaximumError);
		$('#prodminqty').focus().select();
		return false;
	}

	if (prodminqty == Number.NEGATIVE_INFINITY) {
		$('#prodminqty').val('');
	} else {
		$('#prodminqty').val(prodminqty);
	}

	if (prodmaxqty == Number.POSITIVE_INFINITY) {
		$('#prodmaxqty').val('');
	} else {
		$('#prodmaxqty').val(prodmaxqty);
	}

	SelectAllRelatedProducts();

	// set the youtube value settings
	$('#youTubeData').html('');

	var youTubeVideos = [];
	$('#youTubeCurrentVideos li').each(function () {
		var videoId = $(this).find('img').attr('id');

		var titleInput = $('<input type="hidden" />').attr('name', 'videos[' + videoId + '][title]').attr('value', $(this).find('img').attr('title'));
		$('#youTubeData').append(titleInput);

		var descInput = $('<input type="hidden" />').attr('name', 'videos[' + videoId + '][desc]').attr('value', $(this).find('.ytVideoDetails span').attr('title'));
		$('#youTubeData').append(descInput);

		var descLength = $('<input type="hidden" />').attr('name', 'videos[' + videoId + '][length]').attr('value', $(this).find('.ytVideoLength').text());
		$('#youTubeData').append(descLength);

	});

	return true;
}

function ConfirmCancel() {
	if(confirm(lang.ConfirmCancelProduct))
		document.location.href = "index.php?ToDo=viewProducts";
}

function MoveOptionUp(obj) {
	if(obj.selectedIndex == -1) {
		alert(lang.ChooseOptionValue);
		obj.focus();
		return;
	}

	for(i = 0; i < obj.options.length; i++) {
		if(obj.options[i].selected) {
			if(i != 0 && !obj.options[i-1].selected) {
				SwapOptions(obj, i, i-1);
				obj.options[i-1].selected = true;
			}
		}
	}
}

function MoveOptionDown(obj)
{
	if(obj.selectedIndex == -1) {
		alert(lang.ChooseOptionValue);
		obj.focus();
		return;
	}

	for(i = obj.options.length-1; i >= 0; i--) {
		if(obj.options[i].selected) {
			if (i != (obj.options.length-1) && ! obj.options[i+1].selected) {
				SwapOptions(obj, i, i+1);
				obj.options[i+1].selected = true;
			}
		}
	}
}

function SwapOptions(obj, i, j)
{
	var o = obj.options;
	var i_selected = o[i].selected;
	var j_selected = o[j].selected;
	var temp = new Option(o[i].text, o[i].value, o[i].defaultSelected, o[i].selected);
	var temp2= new Option(o[j].text, o[j].value, o[j].defaultSelected, o[j].selected);

	o[i] = temp2;
	o[j] = temp;
	o[i].selected = j_selected;
	o[j].selected = i_selected;
}

// Show/hide tabs depending on which type of prduct is being added/edited (physical or digital)
function ToggleType(sel)
{
	if(sel == 0) {
		// We're selling a physical product
		$('.HideOnDigitalProduct').show();
		$('.ShowOnDigitalProduct').hide();
		sellingDP = false;
	}
	else {
		// We're selling a digital product
		$('.HideOnDigitalProduct').hide();
		$('.ShowOnDigitalProduct').show();
		sellingDP = true;
	}
}

function toggle_price_options() {
	var mpo = document.getElementById("more_price_options");
	var cp = document.getElementById("tr_costprice");
	var rp = document.getElementById("tr_retailprice");
	var sp = document.getElementById("tr_saleprice");

	if(mpo.innerHTML.indexOf(lang.MorePricingOptions) > -1) {
		// Show the pricing fields
		mpo.innerHTML = "&laquo; " + lang.LessPricingOptions;
		cp.style.display = "";
		rp.style.display = "";
		sp.style.display = "";
	}
	else {
		// Hide the pricing fields
		mpo.innerHTML = lang.MorePricingOptions + " &raquo;";
		cp.style.display = "none";
		rp.style.display = "none";
		sp.style.display = "none";
	}
}

function toggle_related_auto(IsChecked) {
	var rc = document.getElementById("relCategory");
	var rp = document.getElementById("relProducts");
	var re = document.getElementById("related");

	rp.length = 0;
	re.length = 0;

	if(IsChecked) {
		// Disable all related product fields
		rc.disabled = true;
		rp.disabled = true;
		re.disabled = true;
		$('#relatedProductsBoxes').hide();
	}
	else {
		// Enable all related product fields
		rc.disabled = false;
		rp.disabled = false;
		re.disabled = false;
		$('#relatedProductsBoxes').show();
	}
}

function editDownload(id)
{
	var current = g('CurrentDownloadId');
	if(current.value != '') {
		if(!confirm(lang.ConfirmChangeDownloadEdit)) {
			return false;
		}
	}

	$('#LoadingIndicator').show();
	if($('#download_'+$('#CurrentDownloadId').val())) {
		$('#download_'+$('#CurrentDownloadId').val()).removeClass('QuickView').addClass('GridRow');
		$('#download_edit_'+$('#CurrentDownloadId').val()+' .QuickView').html('');
		$('#download_edit_'+$('#CurrentDownloadId').val()).hide();
	}

	current.value = id;

	var row = g('download_'+id);
	$('#download_edit_'+id+' .QuickView').load('remote.php?w=editProductDownload&downloadid='+id, {}, editDownloadLoaded);
}

function editDownloadLoaded()
{
	var id= $('#CurrentDownloadId').val();
	var row = g('download_'+id);
	row.className = 'QuickView';
	row.oldClass = 'QuickView';
	g('download_edit_'+id).style.display = '';
	$('#LoadingIndicator').hide();
}

function saveDownload()
{
	if($('#productId').val()) {
		var id = 'productId='+$('#productId').val();
	}
	else
	{
		var id = 'productHash='+$('#productHash').val();
	}

	var downloadid = $('#CurrentDownloadId').val();
	// Performing an edit is easy, we just do a normal ajax request
	if(downloadid) {
		var QuickRow = $('#download_edit_'+downloadid+' .QuickView');
		if($('#DownloadExpiresAfter'+downloadid).val() && isNaN(parseInt($('#DownloadExpiresAfter'+downloadid).val()))) {
			alert(lang.InvalidExpiresAfter);
			$('#DownloadExpiresAfter'+downloadid).focus();
			$('#DownloadExpiresAfter'+downloadid).select();
			return false;
		}

		if($('#DownloadMaxDownloads'+downloadid).val() && isNaN(parseInt($('#DownloadMaxDownloads'+downloadid).val()))) {
			alert(lang.InvalidMaxDownloads);
			$('#DownloadMaxDownloads'+downloadid).focus();
			$('#DownloadMaxDownloads'+downloadid).select();
			return false;
		}

		$('#LoadingIndicator').show();

		$(QuickRow).find('.SaveButton').attr('disabled', true);
		$(QuickRow).find('.SaveButton').css({width: '120px'});
		$(QuickRow).find('.SaveButton').val(lang.SavingDownload);
		$(QuickRow).find('.CancelButton').hide();

		$.ajax({
			url: 'remote.php?'+id,
			type: 'POST',
			data: {
				'w': 'saveProductDownload',
				'downloadid': downloadid,
				'downmaxdownloads': $('#DownloadMaxDownloads'+downloadid).val(),
				'downexpiresafter': $('#DownloadExpiresAfter'+downloadid).val(),
				'downloadexpiresrange': $('#DownloadExpiresRange'+downloadid).val(),
				'downdescription': encodeURIComponent($('#DownloadDescription'+downloadid).val())
			},
			dataType: 'xml',
			success: function(xml)
			{
				downloadSaved(xml);
			},
			error: function(xml)
			{
				if(!error) { error = lang.UploadFailed2; }
				display_error('DownloadStatus', error);
			}
		});
	}
	// Uploading a new download
	else
	{
		if(!validateNewDownload()) {
			return false;
		}

		if(!$('#NewDownloadFile').val()) {
			alert(lang.SelectDownloadFile);
			g('NewDownloadFile').focus();
			g('NewDownloadFile').select();
			$('#StatusUploading').hide();
			$('#StatusNormal').show();
			return false;
		}

		$('#LoadingIndicator').show();
		$('#StatusUploading').show();
		$('#StatusNormal').hide();
		$('#StatusUploading input').val(lang.UploadingDownload);
		$.ajaxFileUpload({
			url: 'remote.php?w=saveProductDownload&'+id+'&downdescription='+encodeURIComponent($('#DownloadDescription').val())+'&downmaxdownloads='+$('#DownloadMaxDownloads').val()+'&downexpiresafter='+$('#DownloadExpiresAfter').val()+'&downexpiresrange='+$('#DownloadExpiresRange').val(),
			secureuri: false,
			fileElementId: 'NewDownloadFile',
			dataType: 'xml',
			success: function(xml)
			{
				downloadSaved(xml);
			}
		});
	}
}

function attachFile()
{
	if ($('#ProductImportUseUpload').attr('checked') == true) {
		saveDownload();
	} else {
		useServerFile();
	}

}

function useServerFile()
{
	if($('#productId').val()) {
		var id = 'productId='+$('#productId').val();
	}
	else {
		var id = 'productHash='+$('#productHash').val();
	}

	if(!validateNewDownload()) {
		return false;
	}

	if(!$('#ServerFile').val()) {
		alert(lang.SelectDownloadFile);
		g('ServerFile').focus();
		g('NewDownloadFile').select();
		ToggleSource();
		return false;
	}
	$('#LoadingIndicator').show();
		$.ajax({
			url: 'remote.php?w=useProductServerFile&serverfile='+$('#ServerFile').val()+'&downdescription='+encodeURIComponent($('#DownloadDescription').val())+'&downmaxdownloads='+$('#DownloadMaxDownloads').val()+'&downexpiresafter='+$('#DownloadExpiresAfter').val()+'&downexpiresrange='+$('#DownloadExpiresRange').val()+'&'+id,
			secureuri: false,
			dataType: 'xml',
			success: function(xml)
			{
				downloadSaved(xml);
			}
		});

}

function downloadSaved(xml)
{
	if($('status', xml).text() == 1) {
		display_success('DownloadStatus', $('message', xml).text(), 5000);
		$('#ExistingDownloadsGrid').find('tbody').html($('grid', xml).text());
		$('#ExistingDownloads').show();
		$('#DownloadUploadGap').show();
		$('#DownloadMaxDownloads').val('');
		$('#DownloadExpiresAfter').val('');
		$('#DownloadExpiresRange').val('days');
		$('#DownloadDescription').val('');
		$('#NewDownloadButtons').show();
		$('#CurrentDownloadId').val('');
		$('#NewDownloadUpload').show();
		$('#StatusNormal').html($('#StatusNormal').html());
	}
	else
	{
		var error = $('message', xml).text();
		if(!error) { var error = lang.UploadFailed2; }
		display_error('DownloadStatus', error);
	}
	$('#LoadingIndicator').hide();
	$('#StatusUploading').hide();
	$('#StatusNormal').show();
}

function cancelDownloadEdit()
{
	var id = $('#CurrentDownloadId').val();
	$('#download_'+$('#CurrentDownloadId').val()).removeClass('QuickView').addClass('GridRow');
	$('#CurrentDownloadId').val('');
	$('#download_edit_'+id+' .QuickView').html('');
	$('#download_edit_'+id).hide();
}

function deleteDownload(id)
{
	if(!confirm(lang.ConfirmDeleteDownload)) {
		return false;
	}
	document.getElementById('download_'+id).parentNode.removeChild(document.getElementById('download_'+id));
	if($('#CurrentDownloadId').val() == id) {
		cancelDownloadEdit();
	}
	var f = document.getElementById('ExistingDownloadsGrid').getElementsByTagName('tr');
	var collapse = true;
	for(var i = 0; i < f.length; i++) {
		if(f[i].className && f[i].className.indexOf('DownloadGridRow') != -1) {
			var collapse = false;
		}
	}
	if(collapse == true) {
		$('#ExistingDownloads').hide();
		$('#DownloadUploadGap').hide();
	}
	$.get('remote.php', {'w': 'deleteProductDownload', 'downloadid': id}, function() {
		$('#LoadingIndicator').hide();
		display_success('DownloadStatus', lang.DigitalDownloadDeleted, 5000);
	});
}

function validateNewDownload()
{
	if($('#DownloadExpiresAfter').val() && isNaN(parseInt($('#DownloadExpiresAfter').val()))) {
		alert(lang.InvalidExpiresAfter);
		ShowTab(2);
		$('#DownloadExpiresAfter').focus();
		$('#DownloadExpiresAfter').select();
		return false;
	}

	if($('#DownloadMaxDownloads').val() && isNaN(parseInt($('#DownloadMaxDownloads').val()))) {
		alert(lang.InvalidMaxDownloads);
		ShowTab(2);
		$('#DownloadMaxDownloads').focus();
		$('#DownloadMaxDownloads').select();
		return false;
	}

	return true;
}

function SaveAndAddAnother() {
	var f = g('frmProduct');
	var d = document.createElement('input');
	d.type = 'hidden';
	d.name = 'addanother';
	d.value = '1';
	f.appendChild(d);
}

function CreateNewCategory() {
	if(g('category_old')) {
		var f = 'category_old';
		$('#QuickCatParent').html('<option value="0" selected="selected">-- ' + lang.NoParent + ' --</option>');
		$('#QuickCatParent').append($('#category_old').html());
	}
	else {
		var f = 'category';
		$('#QuickCatParent').html($('#category').html());
	}
	g('QuickCatParent').selectedIndex = 0;
	$.iModal({
		type: 'inline',
		inline: '#QuickCategoryCreation',
		width: 410
	});
	g('QuickCatName').focus();
}

function SaveQuickCategory() {
	if($('#QuickCatName').val() == '') {
		alert(lang.NoCategoryName);
		g('QuickCatName').focus();
		return false;
	}

	$('#CatSavingIndicator').show();
	var f = 'category';
	var opts = g(f).getElementsByTagName('option');
	selectedcats = '';
	for(i = 0; i < opts.length; i++) {
		if(opts[i].selected == true) {
			selectedcats +=' &selectedcats[]='+opts[i].value;
		}
	}
	jQuery.ajax({
		url: 'remote.php',
		data: 'w=saveQuickCategory&catdesc=&catsort=&catname='+encodeURIComponent($('#QuickCatName').val())+"&catparentid="+$('#QuickCatParent').val()+selectedcats,
		type: 'POST',
		dataType: 'xml',
		success: QuickCategorySaved,
		error: QuickCategorySaved
	});
}

function QuickCategorySaved(response) {
	$('#CatSavingIndicator').hide();
	var status = $('status', response).text();
	if(status == 0) {
		alert($('message', response).text());
	}
	else {
		var select = $('categories', response).text();
		if(g('category_old')) {
			$('#category').remove();
			$('#category_old').replaceWith(select);
			$('#category_old').attr('id', 'category');
			ISSelectReplacement.replace_select(g('category'));
			ISSelectReplacement.scrollToItem('category', $('catid', response).text());
			$('#QuickCatName').val('');
			$('#QuickCatParent').val(0);
		}
		else {
			$('#category').replaceWith(select);
		}
		$.modal.close();
	}
}

$('#useProdVariationNo').click(function() {
	$('#variationList').hide();
	$('#variationLabel').html(lang.ProductWillUseVariation);
	$('#variationCombinationsList').hide();
	g('variationId').selectedIndex = 0;
});

$('#useProdVariationYes').click(function() {
	$('#variationList').show();
	$('#variationLabel').html(lang.ProductWillUseVariationSemi);
});

$('#variationId').change(function() {
	if(this.value != '') {
		vid = g('variationId').options[g('variationId').selectedIndex].value;

		// If inventory tracking is setup to "Track inventory for this product"
		// then we'll reset it to "Track inventory by product variations (from the 'Product Variations' tab above)"
		if(g('prodInvTrack_1').checked) {
			g('prodInvTrack_2').checked = true;
		}

		// Is inventory tracking enabled for variations?
		if(g('prodInvTrack_2').checked) {
			inv = 1;
		}
		else {
			inv = 0;
		}

		var pid = $("#productId").val();
		var phash = $("#productHash").val();

		$('#variationCombinationsList').html('');
		$('#variationCombinationsList').load('remote.php?w=getVariationCombinations&productId='+pid+'&productHash='+phash+'&v='+vid+'&inv='+inv, function() {
			$('#variationCombinationsList').show();
			BindAjaxGridSorting();
		});
	}
	else {
		$('#variationCombinationsList').html('');
	}
});

function toggleVariationInventoryColumns()
{
	if(g('prodInvTrack_2').checked) {
		$('.VariationStockColumn').show();
		$('.VariationSpanRow').attr('colspan', 11);
	}
	else {
		$('.VariationStockColumn').hide();
		$('.VariationSpanRow').attr('colspan', 9);
	}
}

function ToggleSource()
{
	var file = document.getElementById('ProductImportUseUpload');
	if (file.checked == true) {
		$('#StatusNormal').show();
		document.getElementById('NewDownloadFile').style.display = '';
		document.getElementById('ProductImportServerField').style.display = 'none';
		document.getElementById('ProductImportServerNoList').style.display = 'none';
	} else {
		$('#StatusNormal').hide();
		document.getElementById('NewDownloadFile').style.display = 'none';
		if (document.getElementById('ProductImportServerField').getElementsByTagName('SELECT')[0].options.length == 1) {
			document.getElementById('ProductImportServerNoList').style.display = '';
		} else {
			document.getElementById('ProductImportServerField').style.display = '';
		}
	}
}

function ToggleAllowPurchasing() {
	if($('#prodAllowPurchasing').attr('checked')) {
		$('#prodCallForPricingOptions').hide();
	}
	else {
		$('#prodCallForPricingOptions').show();
	}
}

function ToggleCallForPricing() {
	if($('#prodHidePrices').attr('checked')) {
		$('#prodCallForPricingLabelContainer').show();
	}
	else {
		$('#prodCallForPricingLabelContainer').hide();
	}
}

function DelCustomField(tr)
{
	if ($(tr).attr('rowIndex') == 0) {
		return;
	}

	$(tr).remove();
}

function AddCustomField()
{
	var nodes = $('#CustomFieldsContainer tbody tr');
	var inputNode = nodes[nodes.length-1].getElementsByTagName('input')[0];
	var matches;

	if (!(matches = inputNode.name.match(/\[([0-9]+)\]$/))) {
		return;
	}

	var nextId = parseInt(matches[1]) + 1;

	$.ajax({
		type	: "POST",
		url		: "remote.php",
		data	: "w=addCustomField&remoteSection=products&nextId=" + nextId,
		success	: AddCustomFieldProcess
	});
}

function AddCustomFieldProcess(data)
{
	if (data !== '') {
		$('#CustomFieldsContainer tbody').append(data);
	}
}

function AddProductField(CurrentKey)
{
	var nextId = parseInt(document.getElementById('FieldLastKey').value);
	var LastKey = document.getElementById('FieldLastKey');
	LastKey.value = parseInt(LastKey.value)+1;

	$.ajax({
		type	: "POST",
		url		: "remote.php",
		data	: "w=addproductfield&remoteSection=products&nextId=" + nextId,
		success	: function(data) {
			AddProductFieldProcess(data,CurrentKey);
		}
	});
	return false;
}

function AddProductFieldProcess(data, key)
{
	if (data !== '') {
		$('#productFieldTR_'+key).after(data);

		var FieldNumber = 1;
		$('.FieldNumber').each(function() {
			this.innerHTML =  FieldNumber;
			FieldNumber++;
		});
	}
}

function DelProductField(key)
{
	var tr = $('#productFieldTR_'+key);
	if ($(tr).attr('rowIndex') == 0) {
		return;
	}
	if(confirm(lang.ConfirmRemoveProdField)) {
		$(tr).remove();
	}
	return false;
}


function ToggleFieldFileType(fieldType, fieldKey)
{
	$('#FileTypeContainer_'+fieldKey).hide();
	$('#SelectOptionsContainer_'+fieldKey).hide();

	if(fieldType == 'file') {
		$('#FileTypeContainer_'+fieldKey).show();
	}
	else if (fieldType == 'select') {
		$('#SelectOptionsContainer_'+fieldKey).show();
	}
}

function HideFieldHelpText(field)
{

	if ($(field).is('.FieldHelp')) {
		field.value = '';
		$(field).removeClass('FieldHelp');
	}
}


function ShowFieldHelpText(field, help)
{
	field.value = field.value.replace(/^\s+/, '').replace(/\s+$/, '');
	if (field.value == '') {
		field.value = help;
		$(field).addClass('FieldHelp');
	}
}

function ToggleGiftWrapping(type)
{
	if(type == 'custom') {
		$('#GiftWrapOptions').show();
	}
	else {
		$('#GiftWrapOptions').hide();
	}
}

function ToggleDiscountRateValueType(id)
{
	if ($('#discountRulesType_' + id).val() == 'percent') {
		$('#discountRulesAmountPrefix_' + id).html('%');
		$('#discountRulesAmountPostfix_' + id).html('');
	} else {
		$('#discountRulesAmountPrefix_' + id).html(shop.config.CurrencyTokenLeft);
		$('#discountRulesAmountPostfix_' + id).html(shop.config.CurrencyTokenRight);
	}

	if ($('#discountRulesType_' + id).val() == 'fixed') {
		$('#discountRulesLineEnding_' + id).html(lang.DiscountRulesForEachItem);
	} else {
		$('#discountRulesLineEnding_' + id).html(lang.DiscountRulesOffEachItem);
	}
}

function AddDiscountRules(sibling)
{
	var matches, rowId, tmpId, tbody, prevVal, nextId = null;

	prevVal = 0;
	tbody = document.getElementById('DiscountRulesContainer').getElementsByTagName('tbody')[0];

	// Find the highest ID
	$('tr', tbody).each(function() {

		var matches = this.id.match(/\_([0-9]+)$/);
		if (!matches) {
			return;
		}

		tmpId = parseInt(matches[1]);

		if (nextId == null || tmpId > nextId) {
			nextId = tmpId;
		}
	});

	// Increment so we can get the next ID
	nextId++;

	// Get our rowId so we can create proper field label
	rowId = $(sibling).attr('rowIndex');
	rowId++;

	// Now we can clone this row
	var newTR = $(sibling).clone();
	$(newTR).attr('id', 'discountRulesTR_' + nextId);

	// Change our IDs
	$(':input', newTR).each(function() {
		if (this.id !== '') {
			$(this).attr('id', this.id.replace(/\_[0-9]+$/, '_' + nextId));
			$(this).attr('name', this.name.replace(/\[[0-9]+\]$/, '[' + nextId + ']'));

			// Get the highest pervious value so we can use it in our next row value
			if (this.id.substr(0, 21) == 'discountRulesQuantity' && parseInt($(this).val()) > 0) {
				prevVal = Math.max(prevVal, parseInt($(this).val()));
			}

			$(this).val('');
		}

		// Assign our onchange event for our select
		if (this.id.substr(0, 17) == 'discountRulesType') {
			this.onchange = function() { ToggleDiscountRateValueType(nextId); }
		}
	});

	$('span,a', newTR).each(function() {
		if (this.id !== '') {
			$(this).attr('id', this.id.replace(/\_[0-9]+$/, '_' + nextId));

			// Assign our onclick events for the add/del buttons
			if (this.id.substr(0, 16) == 'discountRulesAdd') {
				this.onclick = function() { AddDiscountRules(newTR); }
			} else if (this.id.substr(0, 16) == 'discountRulesDel') {
				this.onclick = function() { DelDiscountRules(newTR); }

				// Also show our delete button
				$(this).show();

			// Assign our field label here as this new close is not part of the document yet
			} else if (this.id.substr(0, 23) == 'discountRulesFieldLabel') {
				$(this).html(GetFieldLabel(parseInt(rowId)+1, lang.DiscountRulesField));
			}
		}
	});

	$(sibling).after(newTR);

	// Assign our new minimum quantity from the previous values
	if (prevVal > 0) {
		$('#discountRulesQuantityMin_' + nextId).val(prevVal+1);
		$('#discountRulesQuantityMax_' + nextId).val('*');
	}

	ReFormatDiscountRules();
}

function DelDiscountRules(sibling)
{
	var tbody = document.getElementById('DiscountRulesContainer').getElementsByTagName('tbody')[0];

	// Check to see if we are the last row. If so then return
	if ($(sibling).attr('rowIndex') == 0 && tbody.rows.length <= 1) {
		return;
	}

	// Remove our row
	$(sibling).remove();

	// If we are the last one then hide the delete button
	if (tbody.rows.length == 1) {
		var matches;

		if (!(matches = tbody.rows[0].id.match(/\_([0-9]+)$/))) {
			return;
		}

		$('#discountRulesDel_' + matches[1]).hide();
	}

	ReFormatDiscountRules();
}

function ReFormatDiscountRules()
{
	var firstTR = null;
	var total = 0;

	// This part here will assign the new 'Discount Rule #' numbers to each label
	$('#DiscountRulesContainer tbody tr').each(function() {

		total++;

		if (this.rowIndex == 0) {
			firstTR = this;
		}

		var matches = this.id.match(/\_([0-9]+)$/);

		if (!matches) {
			return;
		}

		$('#discountRulesFieldLabel_' + matches[1]).html(GetFieldLabel($(this).attr('rowIndex')+1, lang.DiscountRulesField));
	});

	// This part here will hide/show the delete button on the first record
	if (firstTR !== null) {
		var matches = firstTR.id.match(/\_([0-9]+)$/);
		if (matches) {
			if (total > 1) {
				$('#discountRulesDel_' + matches[1]).show();
			} else {
				$('#discountRulesDel_' + matches[1]).hide();
			}
		}
	}
}

function GetFieldLabel(key, label)
{
	var newNo, parts, index, numbers = [lang.Number0, lang.Number1, lang.Number2, lang.Number3, lang.Number4, lang.Number5, lang.Number6, lang.Number7, lang.Number8, lang.Number9];

	parts = key.toString();
	parts = parts.split('');
	newNo = '';

	for (var i=0; i<parts.length; i++) {
		index = numbers.array_search(parts[i]);
		if (index !== false) {
			newNo = newNo + numbers[index];
		}
	}

	return label.replace(/\%s/, newNo);
}

function toggleVendorFeatured(val) {
	if(!val) {
		$('#vendorFeaturedToggle').hide();
	}
	else {
		$('#vendorFeaturedToggle').show();
	}
}

function ToggleOptimizerConfigForm(skipConfirmMsg) {
	if($('#frmProduct #prodEnableOptimizer').attr('checked')) {
		var showForm = true;
		if(!skipConfirmMsg) {
			showForm = confirm(lang.ConfirmEnableProductOptimizer);
		}

		if(showForm) {
			$('#frmProduct #OptimizerConfigForm').show();
		} else {
			$('#frmProduct #prodEnableOptimizer').attr('checked', false)
		}
	} else {
		$('#frmProduct #OptimizerConfigForm').hide();
	}
}

$(document).ready(function() {
	if ($('#EventDateRequired').attr('checked')) {
		$('#DateFieldNameTR').show();
		$('#DateLimitTR').show();
	} else {
		$('#DateFieldNameTR').hide();
		$('#DateLimitTR').hide();
	}

	$('#LimitDates1').hide();
	$('#LimitDates2').hide();
	$('#LimitDates3').hide();

	if ($('#LimitDates').attr('checked')) {
		if ($('#LimitDatesSelect').val() == 1) {
			$('#LimitDates1').show();
		}
		if ($('#LimitDatesSelect').val() == 2) {
			$('#LimitDates2').show();
		}
		if ($('#LimitDatesSelect').val() == 3) {
			$('#LimitDates3').show();
		}
	} else {
			$('#LimitDatesSelect').attr("disabled", true);
	}
});



$('#EventDateRequired').click(function () {
		$('#DateFieldNameTR').toggle();
		$('#DateLimitTR').toggle();
});

$('#LimitDates').click(function () {

		$('#LimitDates1').hide();
		$('#LimitDates2').hide();
		$('#LimitDates3').hide();

		if ($('#LimitDates').attr('checked')) {
			$('#LimitDatesSelect').attr("disabled", false);
			if ($('#LimitDatesSelect').val() == 1) {
				$('#LimitDates1').show();
			}
			if ($('#LimitDatesSelect').val() == 2) {
				$('#LimitDates2').show();
			}
			if ($('#LimitDatesSelect').val() == 3) {
				$('#LimitDates3').show();
			}
		} else {
			$('#LimitDatesSelect').attr("disabled", true);
		}
});

$('#LimitDatesSelect').change(function () {

		$('#LimitDates1').hide();
		$('#LimitDates2').hide();
		$('#LimitDates3').hide();

		if ($('#LimitDatesSelect').val() == 1) {
			$('#LimitDates1').show();
		}
		if ($('#LimitDatesSelect').val() == 2) {
			$('#LimitDates2').show();
		}
		if ($('#LimitDatesSelect').val() == 3) {
			$('#LimitDates3').show();
		}
});
