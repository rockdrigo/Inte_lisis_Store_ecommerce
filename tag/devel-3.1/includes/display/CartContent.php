<?php
class ISC_CARTCONTENT_PANEL extends PANEL
{
	protected $quote;
	protected $displayIncludingTax = false;

	/**
	 * Set the settings to display this panel.
	 */
	public function setPanelSettings()
	{
		$this->quote = getCustomerQuote();

		// Are there any products in the cart?
		if($this->quote->getNumItems() == 0) {
			$GLOBALS['HideShoppingCartGrid'] = "none";
			return;
		}

		$this->displayIncludingTax = false;
		if(getConfig('taxDefaultTaxDisplayCart') != TAX_PRICES_DISPLAY_EXCLUSIVE) {
			$this->displayIncludingTax = true;
		}

		$GLOBALS['HideShoppingCartEmptyMessage'] = "none";

		$this->generateAdditionalCheckoutButtons();
		$this->generateCartContent();
		$this->setUpShippingAndHandling();
		$this->setUpAppliedGiftCertificates();
		$this->setUpAppliedCouponsList();
		$this->setUpTaxDisplay();
		$this->setUpDiscountAmount();
	}

	/**
	 * Determine if we should show the 'Proceed to Checkout' button, as well
	 * as fetch any other checkout links for alt. checkout methods such as
	 * Google Checkout, PayPal Website Payments Express.
	 */
	public function generateAdditionalCheckoutButtons()
	{
		$GLOBALS['AdditionalCheckoutButtons'] = '';
		$GLOBALS['HideCheckoutButton'] = '';
		$GLOBALS['HideMultipleAddressShipping'] = 'display: none';

		// Go through all the checkout modules looking for one with a GetSidePanelCheckoutButton function defined
		$showCheckoutButton = false;
		foreach(getAvailableModules('checkout', true, true) as $module) {
			if(isset($module['object']->_showBothButtons) && $module['object']->_showBothButtons) {
				$showCheckoutButton = true;
				$GLOBALS['AdditionalCheckoutButtons'] .= $module['object']->getCheckoutButton();
			}
			elseif (method_exists($module['object'], 'GetCheckoutButton')) {
				$GLOBALS['AdditionalCheckoutButtons'] .= $module['object']->getCheckoutButton();
			}
			else {
				$showCheckoutButton = true;
			}
		}

		if(gzte11(ISC_MEDIUMPRINT) && $this->quote->getNumPhysicalItems() > 1 && $showCheckoutButton && getConfig("MultipleShippingAddresses")) {
			$GLOBALS['HideMultipleAddressShipping'] = '';
		}

		if($this->quote->getNumItems() == 0 || $showCheckoutButton == false) {
			$GLOBALS['HideCheckoutButton'] = 'display: none';
			$GLOBALS['HideMultipleAddressShippingOr'] = 'display: none';
			return;
		}
	}

	/**
	 * Generate the body of the 'CartContent' panel, including the list of
	 * products in the customer's shopping cart and the subtotal.
	 */
	public function generateCartContent()
	{
		if(!GetConfig('ShowThumbsInCart')) {
			$GLOBALS['HideThumbColumn'] = 'display: none';
			$GLOBALS['ProductNameSpan'] = 2;
		}
		else {
			$GLOBALS['HideThumbColumn'] = '';
			$GLOBALS['ProductNameSpan'] = 1;
		}

		$GLOBALS['SNIPPETS']['CartItems'] = "";

		$deliveryDates = array();
		$items = $this->quote->getItems();
		
		foreach($items as $item) {
			
			$name = $item->getName();
			$quantity = $item->getQuantity();

			$GLOBALS['CartItemId'] = $item->getId();

			if($item instanceof ISC_QUOTE_ITEM_GIFTCERTIFICATE) {
				$GLOBALS['GiftCertificateName'] = isc_html_escape($name);
				$GLOBALS['GiftCertificateAmount'] = CurrencyConvertFormatPrice($item->getPrice());
				$GLOBALS['GiftCertificateTo'] = isc_html_escape($item->getRecipientName());
				$GLOBALS["Quantity" . $quantity] = 'selected="selected"';
				$GLOBALS['ProductPrice'] = CurrencyConvertFormatPrice($item->getPrice());
				$GLOBALS['ProductTotal'] = CurrencyConvertFormatPrice($item->getTotal());
				$GLOBALS['SNIPPETS']['CartItems'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CartItemGiftCertificate");
				continue;
			}

			$GLOBALS['ProductName'] = isc_html_escape($name);
			$GLOBALS['ProductLink'] = prodLink($name);
			$GLOBALS['ProductAvailability'] = $item->getAvailability();
			$GLOBALS['ItemId'] = $item->getProductId();
			$GLOBALS['VariationId'] = $item->getVariationId();
			$GLOBALS['ProductQuantity'] = $quantity;

			if(getConfig('ShowThumbsInCart')) {
				$GLOBALS['ProductImage'] = imageThumb($item->getThumbnail(), prodLink($name));
			}

			$GLOBALS['UpdateCartQtyJs'] = "Cart.UpdateQuantity(this.options[this.selectedIndex].value);";
			$GLOBALS['HideCartProductFields'] = 'display:none;';
			$GLOBALS['CartProductFields'] = '';
			$this->GetProductFieldDetails($item->getConfiguration(), $item->getId());

			$GLOBALS['EventDate'] = '';
			$eventDate = $item->getEventDate(true);
			if(!empty($eventDate)) {
				$GLOBALS['EventDate'] = '
					<div style="font-style: italic; font-size:10px; color:gray">(' .
						$item->getEventName() . ': ' . isc_date('M jS Y', $eventDate) .
					')</div>';
			}

			$GLOBALS['GiftWrappingName'] = '';
			$GLOBALS['HideGiftWrappingAdd'] = '';
			$GLOBALS['HideGiftWrappingEdit'] = 'display: none';
			$GLOBALS['HideGiftWrappingPrice'] = 'display: none';
			$GLOBALS['GiftWrappingPrice'] = '';
			$GLOBALS['GiftMessagePreview'] = '';
			$GLOBALS['HideGiftMessagePreview'] = 'display: none';
			$GLOBALS['HideWrappingOptions'] = 'display: none';

			if($item->allowsGiftWrapping()) {
				$wrapping = $item->getGiftWrapping();
				$GLOBALS['HideWrappingOptions'] = '';
				if(!empty($wrapping)) {
					$GLOBALS['GiftWrappingName'] = isc_html_escape($wrapping['wrapname']);
					$GLOBALS['HideGiftWrappingAdd'] = 'display: none';
					$GLOBALS['HideGiftWrappingEdit'] = '';
					$GLOBALS['HideGiftWrappingPrice'] = '';
					$GLOBALS['GiftWrappingPrice'] = CurrencyConvertFormatPrice($wrapping['wrapprice']);
					if(!empty($wrapping['wrapmessage'])) {
						if(isc_strlen($wrapping['wrapmessage']) > 30) {
							$wrapping['wrapmessage'] = substr($wrapping['wrapmessage'], 0, 27).'...';
						}
						$GLOBALS['GiftMessagePreview'] = isc_html_escape($wrapping['wrapmessage']);
						$GLOBALS['HideGiftMessagePreview'] = '';
					}
				}
				else {
					$GLOBALS['HideGiftWrappingAdd'] = '';
				}
			}

			$price = $item->getPrice($this->displayIncludingTax);
			$total = $item->getTotal($this->displayIncludingTax);
			
			$currencyName = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT MonedaPrecio FROM [|PREFIX|]intelisis_art WHERE Articulo = "'.$name.'"', 'MonedaPrecio');
			$currencyId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT currencyid FROM [|PREFIX|]currencies WHERE currencyname = "'.$currencyName.'"', 'currencyid');
			$defaultCurrency = GetDefaultCurrency();

			if($currencyId == ''){
				$currencyId = $defaultCurrency['currencyid'];
			}
			
			if($currencyId != $defaultCurrency['currencyid'] && $currencyId != NULL){
				$GLOBALS['ProductPrice'] = currencyConvertFormatPrice($price, $currencyId, null, true);
			}else{
	
				$GLOBALS['ProductPrice'] = currencyConvertFormatPrice($price,null,null,true);
			}
			
			$discountTotal = 0;
			$discountArray = $item->getDiscounts();
			foreach($discountArray as $name => $amount){
				if($name != 'total-coupon'){
					$discountTotal += (int)$amount;
				}else{
					$total += $amount;
				}
			}
			$discountAmount = $discountTotal / $item->getQuantity();
			$discount = ($discountAmount * 100) / $item->getBasePrice();
			$GLOBALS['ProductTotal'] = currencyConvertFormatPrice($total, $defaultCurrency['currencyid'],null,true);
			
			// Don't allow the quantity of free items/parent restricted items to be changed
			$GLOBALS['HideCartItemRemove'] = '';
			if($item->getParentId()) {
				$GLOBALS['CartItemQty'] = number_format($item->getQuantity());
				$GLOBALS['HideCartItemRemove'] = 'display: none';
			}
			// If we're using a cart quantity drop down, load that
			else if (GetConfig('TagCartQuantityBoxes') == 'dropdown') {
				$GLOBALS["Quantity" . $quantity] = "selected=\"selected\"";
				if($quantity == 0) {
					$GLOBALS['QtyOptionZero'] = "<option ".$GLOBALS["Quantity0"]." value='0'>0</option>";
				}
				else {
					$GLOBALS['QtyOptionZero'] = "<option value='0'>0</option>";
				}

				// Fixes products being displayed with '0' quantity when the quantity is greater than 30 (hard coded limit in snippet)
				if ($quantity > 30) {
					$GLOBALS["QtyOptionSelected"] = "<option ".$GLOBALS["Quantity" . $quantity]." value='" . $quantity . "'>" . $quantity . "</option>";
				}
				$GLOBALS['CartItemQty'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CartItemQtySelect");
			}
			// Otherwise, load the textbox
			else {
				$GLOBALS['CartItemQty'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CartItemQtyText");
			}
			
			
			$discountPrice = $price * ($discount/100);
			if($discount != '0'/*$discountAmount > '0'*/){
				$GLOBALS['CartItemDiscount'] = number_format($discount,2).'%<br /><span class="CartDiscountQuantity">('.CurrencyConvertFormatPrice($discountPrice,$defaultCurrency['currencyid'],null,true).')';
			}else{
				$GLOBALS['CartItemDiscount'] = '';
			}
			
			$ProdWeight = $item->getWeight();
			if($ProdWeight == '0'){
				$GLOBALS['CartProductWeight'] = '';
			}else{
				$GLOBALS['CartProductWeight'] = number_format($ProdWeight,2).'<br>'.GetConfig('WeightMeasurement');
			}
			
			$ProdDimensions = $item->getDimensions();
			$ProdVolume = $ProdDimensions['width'] * $ProdDimensions['height'] * $ProdDimensions['depth'];
			if($ProdVolume == '0'){
				$GLOBALS['CartProductVolume'] = '';
			}else{
				$GLOBALS['CartProductVolume'] = number_format($ProdVolume,2).'<br>'.GetConfig('LengthMeasurement').'<span style="font-size:0.7em; vertical-align:top;">3</span>';
			}
			$GLOBALS['CartProductStorage'] = $this->getStorage($item->getId());
			
			
			
			// Is this product a variation?
			$GLOBALS['ProductOptions'] = '';
			$options = $item->getVariationOptions();
			if(!empty($options)) {
				$GLOBALS['ProductOptions'] .= "<br /><small>(";
				$comma = '';
				foreach($options as $name => $value) {
					if(!trim($name) || !trim($value)) {
						continue;
					}
					$GLOBALS['ProductOptions'] .= $comma.isc_html_escape($name).": ".isc_html_escape($value);
					$comma = ', ';
				}
				$GLOBALS['ProductOptions'] .= ")</small>";
			}

			$GLOBALS['HideExpectedReleaseDate'] = 'display: none;';
			if($item->isPreOrder()) {
				$GLOBALS['ProductExpectedReleaseDate'] = $item->getPreOrderMessage();
				$GLOBALS['HideExpectedReleaseDate'] = '';
			}

			$GLOBALS['SNIPPETS']['CartItems'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CartItem");
			$GLOBALS["Quantity" . $quantity] = "";
			
			if($date = $this->getDeliveryDateFromStatus($item)){
				$deliveryDates[] = $date;
			}
		}

		if(!empty($deliveryDates)){
			rsort($deliveryDates);
			$maxdate = $deliveryDates[0];
			$GLOBALS['CartOrderDeliveryDateFromStatus'] = "La fecha de entrega de este pedido es ".date('d/M/Y', $maxdate)." basado en el producto con fecha de entrega mas lejana en su carrito";
		}		
		
		$GLOBALS['CartItemTotal'] = currencyConvertFormatPrice($this->quote->getSubTotal($this->displayIncludingTax),$defaultCurrency['currencyid'],null,true);
		$GLOBALS['CartTotal'] = currencyConvertFormatPrice($this->quote->getGrandTotal(),$defaultCurrency['currencyid'],null,true);

		if($this->quote->getWrappingCost() > 0) {
			$GLOBALS['GiftWrappingTotal'] = currencyConvertFormatPrice($this->quote->getWrappingCost());
		}
		else {
			$GLOBALS['HideGiftWrappingTotal'] = 'display: none';
		}
		$script = "
			$('.quantityInput').live('change', function() {
				Cart.UpdateQuantity($(this).val());
			});
		";
		$GLOBALS['ISC_CLASS_TEMPLATE']->clientScript->registerScript($script,'ready');

	}
	
	private function getDeliveryDateFromStatus($item) {
		if($item->getVariationId() == 0) {
			$Situacion = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT Situacion FROM [|PREFIX|]intelisis_products WHERE productid = "'.$item->getProductId().'"', 'Situacion');
		}
		else {
			$Situacion = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT Situacion FROM [|PREFIX|]intelisis_variation_combinations WHERE combinationid = "'.$item->getVariationId().'"', 'Situacion');
		}
		if(!$Situacion || $Situacion == ''){
			return false;
		}
		
		$result = $GLOBALS['ISC_CLASS_DB']->Query('SELECT DiasEntrega, PeriodoEntrega FROM [|PREFIX|]intelisis_prodstatus WHERE Situacion = "'.$Situacion.'"');
		if(!$result){
			return false;
		}
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		if($row['DiasEntrega'] == '' || $row['PeriodoEntrega'] == ''){
			return false;			
		}

		$date = getDeliveryDate($row['DiasEntrega'], $row['PeriodoEntrega']);
		return $date;
	}

	/**
	 * Set up everything pertaining to the display of the 'Estimate Shipping'
	 * feature, as well as the shipping cost of all items in the cart if it is
	 * known.
	 */
	public function setUpShippingAndHandling()
	{
		$defaultCurrency = GetDefaultCurrency();
		$GLOBALS['HideShoppingCartShippingCost'] = 'none';
		$GLOBALS['HideShoppingCartHandlingCost'] = 'none';
		$GLOBALS['HideShoppingCartShippingEstimator'] = 'display: none';

		$this->quote->setIsSplitShipping(false);

		$handling = $this->quote->getHandlingCost($this->displayIncludingTax);
		if($handling > 0) {
			$handlingFormatted = currencyConvertFormatPrice($handling);
			$GLOBALS['HandlingCost'] = $handlingFormatted;
			$GLOBALS['HideShoppingCartHandlingCost'] = '';
		}

		// All products in the cart are digital downloads so the shipping doesn't apply
		if($this->quote->isDigital()) {
			return;
		}

		// If we're still here, shipping applies to this order
		$GLOBALS['HideShoppingCartShippingEstimator'] = '';

		$selectedCountry = GetCountryIdByName(GetConfig('CompanyCountry'));
		$selectedState = 0;
		$selectedStateName = '';

		// Retain the country, stae and zip code selections if we have them
		$shippingAddress = $this->quote->getShippingAddress(0);
		if($shippingAddress->getCountryId()) {
			$selectedCountry = $shippingAddress->getCountryId();
			$selectedState = $shippingAddress->getStateId();
			$GLOBALS['ShippingZip'] = $shippingAddress->getZip();
		}

		$GLOBALS['ShippingCountryList'] = GetCountryList($selectedCountry);
		$GLOBALS['ShippingStateList'] = GetStateListAsOptions($selectedCountry, $selectedState);
		$GLOBALS['ShippingStateName'] = isc_html_escape($selectedStateName);

		// If there are no states for the country then hide the dropdown and show the textbox instead
		if (GetNumStatesInCountry($selectedCountry) == 0) {
			$GLOBALS['ShippingHideStateList'] = "none";
		}
		else {
			$GLOBALS['ShippingHideStateBox'] = "none";
		}

		// Show the stored shipping estimate if we have one
		if($shippingAddress->hasShippingMethod()) {
			$GLOBALS['HideShoppingCartShippingCost'] = '';
			$cost = $shippingAddress->getNonDiscountedShippingCost($this->displayIncludingTax);

			if($cost == 0) {
				$GLOBALS['ShippingCost'] = getLang('Free');
			}
			else {
				$GLOBALS['ShippingCost'] = currencyConvertFormatPrice($cost,$defaultCurrency['currencyid'],null,true);
			}

			$GLOBALS['ShippingProvider'] = isc_html_escape($shippingAddress->getShippingProvider());
		}
	}

	/**
	 * Configure the display of the 'Discount:' total in the cart.
	 */
	public function setUpDiscountAmount()
	{
		$defaultCurrency = GetDefaultCurrency();
		$discount = $this->quote->getDiscountAmount();
		if($discount == 0) {
			$GLOBALS['HideDiscountAmount'] = 'display: none';
			return;
		}

		$GLOBALS['DiscountAmount'] = currencyConvertFormatPrice($discount * -1);
	}

	/**
	 * Set up everything to show the tax totals for the cart. Depending on
	 * the store settings, this may be a single "Tax:" line, or may be a
	 * series of taxes applied broken up by tax rate.
	 */
	public function setUpTaxDisplay()
	{
		if(getConfig('taxDefaultTaxDisplayCart') != TAX_PRICES_DISPLAY_EXCLUSIVE) {
			$taxVar = 'InclusiveTaxes';
			$taxLabelAppend = ' '.getLang('IncludedInTotal');
		}
		else {
			$taxVar = 'Taxes';
			$taxLabelAppend = '';
		}

		// Show a summary of tax charges, instead of broken down by rate
		if(getConfig('taxChargesInCartBreakdown') == TAX_BREAKDOWN_SUMMARY) {
			$GLOBALS['TaxName'] = getConfig('taxLabel').$taxLabelAppend;
			$taxCost = $this->quote->getTaxTotal();

			if($taxCost == 0) {
				return;
			}

			$GLOBALS['TaxCost'] = currencyConvertFormatPrice($taxCost);
			$GLOBALS[$taxVar] = $GLOBALS['ISC_CLASS_TEMPLATE']->getSnippet('CartTotalTaxRow');
		}
		else {
			$GLOBALS[$taxVar] = '';
			$taxSummary = $this->quote->getTaxRateSummary();
			foreach($taxSummary as $taxRateName => $taxRateAmount) {
				if($taxRateAmount == 0) {
					continue;
				}
				$GLOBALS['TaxCost'] = currencyConvertFormatPrice($taxRateAmount);
				$GLOBALS['TaxName'] = $taxRateName.$taxLabelAppend;
				$GLOBALS[$taxVar] .= $GLOBALS['ISC_CLASS_TEMPLATE']->getSnippet('CartTotalTaxRow');
			}
		}
	}

	/**
	 * Set up the list of coupon codes that have been applied to the shopping
	 * cart.
	 */
	public function setUpAppliedCouponsList()
	{
		$GLOBALS['SNIPPETS']['Coupons'] = '';
		$coupons = $this->quote->getAppliedCoupons();
		if(empty($coupons)) {
			return;
		}

		$defaultCurrency = GetDefaultCurrency();
		$GLOBALS['SNIPPETS']['FreeShippingCoupons'] = '';
		$GLOBALS['SNIPPETS']['NormalCoupons'] = '';
		$freeShippingTypesIds = array(4,3);
		foreach ($coupons as $coupon) {
			$GLOBALS['CouponId'] = $coupon['id'];
			$GLOBALS['CouponCode'] = $coupon['code'];
			$GLOBALS['CouponDiscount'] = currencyConvertFormatPrice($coupon['totalDiscount'] * -1,$defaultCurrency,null,true);
			$GLOBALS['SNIPPETS']['Coupons'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CartCoupon");

			if (!empty ($coupon['discountType']) && getclass('ISC_COUPON')->isFreeShippingCoupon($coupon['discountType'])) {
				$GLOBALS['SNIPPETS']['FreeShippingCoupons'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CartCoupon");
			} else {
				$GLOBALS['SNIPPETS']['NormalCoupons'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CartCoupon");
			}
		}
	}

	/**
	 * Setup the list of gift certificates that have been applied to the
	 * shopping cart.
	 */
	public function setUpAppliedGiftCertificates()
	{
		if (!gzte11(ISC_LARGEPRINT)) {
			$GLOBALS['HidePanels'][] = "SideGiftCertificateCodeBox";
			return;
		}

		$GLOBALS['SNIPPETS']['GiftCertificates'] = '';

		$giftCertificates = $this->quote->getAppliedGiftCertificates();
		if(empty($giftCertificates)) {
			return;
		}

		foreach ($giftCertificates as $certificate) {
			$GLOBALS['GiftCertificateCode'] = isc_html_escape($certificate['code']);
			$GLOBALS['GiftCertificateId'] = $certificate['id'];
			$GLOBALS['GiftCertificateRemaining'] = CurrencyConvertFormatPrice($certificate['remaining']);
			$GLOBALS['CertificateAmountUsed'] = CurrencyConvertFormatPrice($certificate['used'] * -1);
			$GLOBALS['SNIPPETS']['GiftCertificates'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CartGiftCertificate");
		}

		if ($GLOBALS['SNIPPETS']['GiftCertificates']) {
			if($this->quote->getGrandTotal() == 0) {
				$GLOBALS['HidePanels'][] = "SideGiftCertificateCodeBox";
			}
		}
	}

	/**
	 * Generate a list of product fields for configurable products to be shown
	 * for a particular item in the cart based on the customer's configuration.
	 *
	 * @param array $productFields Array containing list of product fields for this product.
	 * @param int $cartItemId The ID of the item in the shopping cart.
	 */
	public function GetProductFieldDetails($productFields, $cartItemId)
	{
		// custom product fields on cart page
		$GLOBALS['HideCartProductFields'] = 'display:none;';
		$GLOBALS['CartProductFields'] = '';
		if(isset($productFields) && !empty($productFields) && is_array($productFields)) {
			$GLOBALS['HideCartProductFields'] = '';
			foreach($productFields as $filedId => $field) {

				switch ($field['type']) {
					//field is a file
					case 'file': {

						//file is an image, display the image
						$fieldValue = '<a target="_Blank" href="'.$GLOBALS['ShopPath'].'/viewfile.php?cartitem='.$cartItemId.'&prodfield='.$filedId.'">'.isc_html_escape($field['fileOriginalName']).'</a>';
						break;
					}
					//field is a checkbox
					case 'checkbox': {
						$fieldValue = GetLang('Checked');
						break;
					}
					//if field is a text area or short text display first
					default: {
						if(isc_strlen($field['value'])>50) {
							$fieldValue = isc_substr(isc_html_escape($field['value']), 0, 50)." ..";
						} else {
							$fieldValue = isc_html_escape($field['value']);
						}
					}
				}

				if(trim($fieldValue) != '') {
					$GLOBALS['CustomFieldName'] = isc_html_escape($field['name']);
					$GLOBALS['CustomFieldValue'] = $fieldValue;
					$GLOBALS['CartProductFields'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CartProductFields");
				}
			}
		}
	}
	public function getStorage($ProdId){
		$CustomerId = $_SESSION['QUOTE']->getCustomerId();
		$CteId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT Cliente FROM [|PREFIX|]intelisis_customers WHERE customerid = "'.$CustomerId.'"');
		$CustStorage = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT AlmacenDef FROM [|PREFIX|]intelisis_cte WHERE Cliente = "'.$CteId.'"');
		if($CustStorage){
			return $CustStorage;
		}else{
			$DefStorage = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT eCommerceSucursal FROM [|PREFIX|]intelisis_sucursal WHERE Sucursal = "'.getConfig('syncIWSintelisissucursal').'"');
			if($DefStorage){
				return $DefStorage;
			}else{
				return;
			}
		}
	}
}