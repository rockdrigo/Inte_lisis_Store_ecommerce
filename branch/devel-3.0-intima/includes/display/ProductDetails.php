<?php

CLASS ISC_PRODUCTDETAILS_PANEL extends PANEL
{
	/**
	 * @var ISC_PRODUCT Instance of the product class that this panel is loading details for.
	 */
	private $productClass = null;

	/**
	 * @var MySQLDb Instance of the database class.
	 */
	private $db = null;

	private $hasRequiredFileFields = false;

	/**
	 * Set the display settings for this panel.
	 */
	public function SetPanelSettings()
	{
		$this->productClass = GetClass('ISC_PRODUCT');
		$this->db = $GLOBALS['ISC_CLASS_DB'];

		if(!empty($_SESSION['ProductErrorMessage'])) {
			FlashMessage($_SESSION['ProductErrorMessage'], 'error');
		}
		$GLOBALS['ProductDetailFlashMessages'] = GetFlashMessageBoxes();

		$GLOBALS['ProductName'] = isc_html_escape($this->productClass->GetProductName());
		$GLOBALS['ProductId'] = $this->productClass->GetProductId();
		$GLOBALS['ProductDetailPrice'] = '';

		if(isset($_SESSION['ProductErrorMessage']) && $_SESSION['ProductErrorMessage']!='') {
			$GLOBALS['HideProductErrorMessage']='';
			$GLOBALS['ProductErrorMessage']=$_SESSION['ProductErrorMessage'];
			unset($_SESSION['ProductErrorMessage']);
		}

		$GLOBALS['ProductCartQuantity'] = '';
		if(isset($GLOBALS['CartQuantity'.$this->productClass->GetProductId()])) {
			$GLOBALS['ProductCartQuantity'] = (int)$GLOBALS['CartQuantity'.$this->productClass->GetProductId()];
		}

		$product = $this->productClass->getProduct();
		if($product['prodvariationid'] > 0 || $product['prodconfigfields'] || $product['prodeventdaterequired']) {
			$GLOBALS['ISC_CLASS_TEMPLATE']->assign('ConfigurableProductClass', 'ConfigurableProduct');
		}
		else {
			$GLOBALS['ISC_CLASS_TEMPLATE']->assign('ConfigurableProductClass', 'NonConfigurableProduct');
		}

		// We've got a lot to do on this page, so to make it easier to follow,
		// everything is broken down in to smaller functions.
		$this->SetVendorDetails();
		$this->SetWrappingDetails();
		$this->SetProductImages();
		$this->SetShippingCost();
		$this->SetPricingDetails();
		$this->SetProductDimensions();
		$this->SetProductReviews();
		$this->SetBulkDiscounts();
		$this->SetBrandDetails();
		$this->SetInventoryDetails();
		$this->SetMiscAttributes();
		$this->SetPurchasingOptions();
		$this->SetProductVariations();
		$this->SetPreorderData();
		$this->SetMinMaxQty();
		$this->SetDeliveryDateFromStatus();

		// Mobile devices don't support file uploads, so if this is a mobile device then don't show
		// any configuration for the product and show a message that the product must be purchased
		// on the full site.
		if($this->hasRequiredFileFields && $GLOBALS['ISC_CLASS_TEMPLATE']->getIsMobileDevice()) {
			$GLOBALS['SNIPPETS']['ProductFieldsList'] = '';
			$GLOBALS['SNIPPETS']['VariationList'] = '';
			$GLOBALS['SNIPPETS']['EventDate'] = '';
			$GLOBALS['ConfigurableProductClass'] = 'NonConfigurableProduct';
			$GLOBALS['DisplayAdd'] = 'none';
			$GLOBALS['SNIPPETS']['SideAddItemSoldOut'] = $GLOBALS['ISC_CLASS_TEMPLATE']->getSnippet('ProductNotOrderableOnMobiles');
		}

		$GLOBALS['SNIPPETS']['ProductAddToCart'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductAddToCart");
	}
	
	/*
	 * REQ11552: NES - Calcula la fecha de entrega a partir de la Situacion del articulo. Como aqui es solo el despliegue inicial de la
	 * pagina del articulo, todvia no hay opciones seleccionadas, por lo que se busca la situacion del articulo padre
	 */
	private function SetDeliveryDateFromStatus() {
		if(GetConfig('showDeliveryDateFromStatus')){
			$GLOBALS['HideDeliveryDateFromStatus'] = '';
		}
		else {
			$GLOBALS['HideDeliveryDateFromStatus'] = 'display: none';
			return;
		}
		
		$Situacion = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT Situacion FROM [|PREFIX|]intelisis_products WHERE productid = "'.$GLOBALS['ProductId'].'"', 'Situacion');
		if(!$Situacion || $Situacion == ''){
			$GLOBALS['DeliveryDateFromStatus'] = 'No se encontraron datos sobre la situacion del articulo';
			return;
		}
		
		$result = $GLOBALS['ISC_CLASS_DB']->Query('SELECT Descontinuado, DiasEntrega, PeriodoEntrega FROM [|PREFIX|]intelisis_prodstatus WHERE Situacion = "'.$Situacion.'"');
		if(!$result){
			$GLOBALS['DeliveryDateFromStatus'] = 'No se encontraron datos de la situacion del articulo';
			return;
		}
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		if($row['DiasEntrega'] == '' || $row['PeriodoEntrega'] == ''){
			$GLOBALS['DeliveryDateFromStatus'] = 'No se configuraron datos de la situacion del articulo';
			return;			
		}
		
		$date = getDeliveryDate($row['DiasEntrega'], $row['PeriodoEntrega']);
		$GLOBALS['DeliveryDateFromStatus'] = isc_date('d/M/Y', $date);
		
		if($row['Descontinuado'] == 1){
			$GLOBALS['DeliveryDateFromStatus'] .= '<br>El Producto esta marcado como Descontinuado, por lo que no se puede agregar al carrito';
		}
		
		return;
	}

	/**
	* Set display options for a preorder product
	*
	*/
	private function SetPreorderData()
	{
		$GLOBALS['SNIPPETS']['ProductExpectedReleaseDate'] = '';

		if (!$this->productClass->IsPreOrder()) {
			return;
		}

		if ($this->productClass->GetReleaseDate()) {
			$GLOBALS['ReleaseDate'] = isc_html_escape($this->productClass->GetPreOrderMessage());
			if (!$GLOBALS['ReleaseDate']) {
				return;
			}
		} else {
			$GLOBALS['ReleaseDate'] = GetLang('PreOrderProduct');
		}

		$GLOBALS['SNIPPETS']['ProductExpectedReleaseDate'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('ProductExpectedReleaseDate');
	}

	/**
	* Set the display options for min/max qty
	*/
	private function SetMinMaxQty()
	{
		$js = '';

		if ($this->productClass->GetMinQty()) {
			$GLOBALS['HideMinQty'] = '';
			$GLOBALS['MinQty'] = $this->productClass->GetMinQty();
			$js .= 'productMinQty=' . $this->productClass->GetMinQty() . ';';
			$js .= 'lang.ProductMinQtyError = ' . isc_json_encode(GetLang('ProductMinQtyError', array(
				'product' => $this->productClass->GetProductName(),
				'qty' => $this->productClass->GetMinQty(),
			))) . ';';
		} else {
			$GLOBALS['HideMinQty'] = 'display:none;';
			$GLOBALS['MinQty'] = '';
			$js .= 'productMinQty=0;';
		}

		if ($this->productClass->GetMaxQty() !== INF) {
			$GLOBALS['HideMaxQty'] = '';
			$GLOBALS['MaxQty'] = $this->productClass->GetMaxQty();
			$js .= 'productMaxQty=' . $this->productClass->GetMaxQty() . ';';
			$js .= 'lang.ProductMaxQtyError = ' . isc_json_encode(GetLang('ProductMaxQtyError', array(
				'product' => $this->productClass->GetProductName(),
				'qty' => $this->productClass->GetMaxQty(),
			))) . ';';
		} else {
			$GLOBALS['HideMaxQty'] = 'display:none;';
			$GLOBALS['MaxQty'] = '';
			$js .= 'productMaxQty=Number.POSITIVE_INFINITY;';
		}

		$GLOBALS['ProductMinMaxQtyJavascript'] = $js;
	}

	/**
	 * Set the display options for the shipping pricing.
	 */
	private function SetShippingCost()
	{
		if(!GetConfig('ShowProductShipping') || $this->productClass->GetProductType() != PT_PHYSICAL) {
			$GLOBALS['HideShipping'] = 'none';
			return;
		}

		if ($this->productClass->GetFixedShippingCost() != 0) {
			// Is there a fixed shipping cost?
			$GLOBALS['ShippingPrice'] = sprintf("%s %s", CurrencyConvertFormatPrice($this->productClass->GetFixedShippingCost()), GetLang('FixedShippingCost'));
		}
		else if ($this->productClass->HasFreeShipping()) {
			// Does this product have free shipping?
			$GLOBALS['ShippingPrice'] = GetLang('FreeShipping');
		}
		// Purchasing is allowed, so show calculated at checkout
		else if($this->productClass->IsPurchasingAllowed()) {
			$GLOBALS['ShippingPrice'] = GetLang('CalculatedAtCheckout');
		}
		else {
			$GLOBALS['HideShipping'] = 'none';
		}
	}

	/**
	 * Set general pricing details for the product.
	 */
	private function SetPricingDetails()
	{
		$product = $this->productClass->getProduct();

		$GLOBALS['PriceLabel'] = GetLang('Price');

		if(GetConfig('isIntelisis'))
		{

			$listprice = applyListaPreciosEsp($product);
			if($listprice != ''){
				$product['prodcalculatedprice'] = $listprice;
			}
			$saleprice =  applyPyC($product);
			
			if($saleprice != '') {
				// BUG10495
				$product['prodretailprice'] = $product['prodcalculatedprice']; // El original, que se va a tachar
				
				$product['prodcalculatedprice'] = $saleprice; //Precio a mostrar en la lista, regresado por PyC
				$product['prodsaleprice'] = $product['prodcalculatedprice']; //Precio para decidir si es Sale o no
			}
		}
		
		if($this->productClass->GetProductCallForPricingLabel()) {
			$GLOBALS['ProductDetailPrice'] = $GLOBALS['ISC_CLASS_TEMPLATE']->ParseGL($this->productClass->GetProductCallForPricingLabel());
		}
		// If prices are hidden, then we don't need to go any further
		else if($this->productClass->ArePricesHidden()) {
			$GLOBALS['HidePrice'] = "display: none;";
			$GLOBALS['HideRRP'] = 'none';
			$GLOBALS['ProductDetailPrice'] = '';
			return;
		}
		else {
			$options = array('strikeRetail' => false);
			$GLOBALS['ProductDetailPrice'] = formatProductDetailsPrice($product, $options);
		}

		// Determine if we need to show the RRP for this product or not
		// by comparing the price of the product including any taxes if
		// there are any
		$GLOBALS['HideRRP'] = "none";
		$productPrice = $product['prodcalculatedprice'];
		$retailPrice = $product['prodretailprice'];
		if($retailPrice) {
			// Get the tax display format
			$displayFormat = getConfig('taxDefaultTaxDisplayProducts');
			$options['displayInclusive'] = $displayFormat;

			// Convert to the browsing currency, and apply group discounts
			$productPrice = formatProductPrice($product, $productPrice, array(
				'localeFormat' => false, 'displayInclusive' => $displayFormat
			));
			$retailPrice = formatProductPrice($product, $retailPrice, array(
				'localeFormat' => false, 'displayInclusive' => $displayFormat
			));

			if($productPrice < $retailPrice) {
				$GLOBALS['HideRRP'] = '';

				// Showing call for pricing, so just show the RRP and that's all
				if($this->productClass->GetProductCallForPricingLabel()) {
					$GLOBALS['RetailPrice'] = CurrencyConvertFormatPrice($retailPrice);
				}
				else {
					// ISC-1057: do not apply customer discount to RRP in this case
					$retailPrice = formatProductPrice($product, $product['prodretailprice'], array(
						'localeFormat' => false,
						'displayInclusive' => $displayFormat,
						'customerGroup' => 0,
					));
					$GLOBALS['RetailPrice'] = '<strike>' . formatPrice($retailPrice) . '</strike>';
					$GLOBALS['PriceLabel'] = GetLang('YourPrice');
					$savings = $retailPrice - $productPrice;
					$string = sprintf(getLang('YouSave'), '<span class="YouSaveAmount">'.formatPrice($savings).'</span>');
					$GLOBALS['YouSave'] = '<span class="YouSave">'.$string.'</span>';
				}
			}
		}
	}

	/**
	 * Setup the purchasing options such as quantity box, stock messages,
	 * add to cart button, product fields etc.
	 */
	private function SetPurchasingOptions()
	{
		if(!$this->productClass->IsPurchasingAllowed()) {
			$GLOBALS['DisplayAdd'] = 'none';
			return;
		}

		$GLOBALS['AddToCartButton'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('ProductAddToCartButton');

		$GLOBALS['CartLink'] = CartLink();

		$GLOBALS['ProductCartQuantity'] = '';
		if(isset($GLOBALS['CartQuantity'.$this->productClass->GetProductId()])) {
			$GLOBALS['ProductCartQuantity'] = (int)$GLOBALS['CartQuantity'.$this->productClass->GetProductId()];
		}

		// If we're using a cart quantity drop down, load that
		if (GetConfig('TagCartQuantityBoxes') == 'dropdown') {
			$arrLimitDrop = '';
			if ($this->productClass->GetMinQty()) {
				$minQty = $this->productClass->GetMinQty();
			}
			else {
				$minQty = 1;
			}

			for ( $i = $minQty ; $i <= GetConfig('DisplayCheckBoxLimit') ; $i++ ) {
				if($minQty == $i)	
					$arrLimitDrop = $arrLimitDrop . '<option selected ="selected" value="'.$i.'">'.$i.'</option>';
				else 
					$arrLimitDrop = $arrLimitDrop . '<option value="'.$i.'">'.$i.'</option>';
				
			}
			$GLOBALS['QtyLimitDropBox'] = $arrLimitDrop;
			
			/*if ($this->productClass->GetMinQty()) {
				$GLOBALS['Quantity' . $this->productClass->GetMinQty()] = 'selected="selected"';
			} else {
				$GLOBALS['Quantity1'] = '';
			}*/
			
			$GLOBALS['QtyOptionZero'] = "";
			$GLOBALS['AddToCartQty'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CartItemQtySelect");
		}
		// Otherwise, load the textbox
		else {
			$GLOBALS['ProductQuantity'] = 1;
			$GLOBALS['AddToCartQty'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CartItemQtyText");
		}

		// Can we sell this product/option
		$saleable = IsProductSaleable($this->productClass->GetProduct());
		$variations = $this->productClass->GetProductVariationOptions();
		if(!empty($variations) && $this->productClass->GetProductInventoryTracking() == 2) {
			$productInStock = true;
		}
		else {
			$productInStock = $saleable;
		}

		if($productInStock == true) {
			$GLOBALS['SNIPPETS']['SideAddItemSoldOut'] = '';
			$GLOBALS['DisplayAdd'] = "";

			if (GetConfig('FastCartAction') == 'popup' && GetConfig('ShowCartSuggestions')) {
				$GLOBALS['FastCartButtonJs'] = ' && fastCartAction(event)';
			}
		}
		else if($this->productClass->IsPurchasingAllowed()) {
			$output = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SideAddItemSoldOut");
			$output = $GLOBALS['ISC_CLASS_TEMPLATE']->ParseSnippets($output, $GLOBALS['SNIPPETS']);
			$GLOBALS['SNIPPETS']['SideAddItemSoldOut'] = $output;

			$GLOBALS['BuyBoxSoldOut'] = "ProductBuyBoxSoldOut";
			$GLOBALS['DisplayAdd'] = "none";
			$GLOBALS['ISC_LANG']['BuyThisItem'] = GetLang('ItemUnavailable');
		}

		if(GetConfig('ShowAddToCartQtyBox') == 1) {
			$GLOBALS['DisplayAddQty'] = $GLOBALS['DisplayAdd'];
		}
		else {
			$GLOBALS['DisplayAddQty'] = "none";
		}

		if($this->productClass->IsPurchasingAllowed()) {
			$this->LoadEventDate();
			$this->LoadProductFieldsLayout();
		}

		$GLOBALS['ShowAddToCartQtyBox'] = GetConfig('ShowAddToCartQtyBox');
	}

	/**
	 * Setup the list of variations for this product if it has any.
	 */
	private function SetProductVariations()
	{
		$GLOBALS['VariationList'] = '';
		$GLOBALS['SNIPPETS']['VariationList'] = '';
		$GLOBALS['HideVariationList'] = '';
		$GLOBALS['ProductOptionRequired'] = "false";

		// Are there any product variations?
		$variationOptions = $this->productClass->GetProductVariationOptions();

		if(empty($variationOptions)) {
			$GLOBALS['HideVariationList'] = 'display:none;';
			return;
		}

		$variationValues = $this->productClass->GetProductVariationOptionValues();

		// Is a product option required when adding the product to the cart?
		if($this->productClass->IsOptionRequired()) {
			$GLOBALS['ProductOptionRequired'] = "true";
		}

		if(count($variationOptions) == 1) {
			$onlyOneVariation = true;
			$GLOBALS['OptionMessage'] = GetLang('ChooseAnOption');
		}
		else {
			$GLOBALS['OptionMessage'] = GetLang('ChooseOneOrMoreOptions');
			$onlyOneVariation = false;
		}
		$useSelect = false;
		$GLOBALS['VariationNumber'] = 0;

		foreach($variationOptions as $optionName) {
			
			$GLOBALS['ActivatesLayersClass'] = ($this->productClass->GetProductFieldsActivatedByOption($optionName)) ? 'ActivatesLayersClass' : '';
			
			
			// If this is the only variation then instead of select boxes, just show radio buttons
			$GLOBALS['VariationChooseText'] = "";
			$GLOBALS['VariationNumber']++;
			$GLOBALS['VariationName'] = isc_html_escape($optionName);
			$GLOBALS['SNIPPETS']['OptionList'] = '';

			// Fixes cases where for one reason or another there are no options for a specific variation
			// Botched import?
			if(empty($variationValues[$optionName])) {
				continue;
			}

			if($onlyOneVariation && count($variationValues[$optionName]) <= 5 && !$this->productClass->IsOptionRequired()) {
				$GLOBALS['OptionId'] = 0;
				$GLOBALS['OptionValue'] = GetLang('zNone');
				$GLOBALS['OptionChecked'] = "checked=\"checked\"";
				$GLOBALS['SNIPPETS']['OptionList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductVariationListSingleItem");
			}
			else if($onlyOneVariation && count($variationValues[$optionName]) > 5) {
				$useSelect = true;
			}

			// Build the list of options
			$GLOBALS['OptionChecked'] = '';
			if (isset($variationValues[$optionName])) {
				//$GLOBALS['SNIPPETS']['OptionList'] .= "<option value="">Elija</option>"
				foreach($variationValues[$optionName] as $optionid => $value) {
					$GLOBALS['OptionId'] = (int)$optionid;
					$GLOBALS['OptionValue'] = isc_html_escape($value);
					if($onlyOneVariation && !$useSelect) {
						$GLOBALS['SNIPPETS']['OptionList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductVariationListSingleItem");
					}
					else {
							if ($value == 'N/A')
							 $GLOBALS['SNIPPETS']['OptionList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductVariationListMultipleItemSelected");
							else
							 $GLOBALS['SNIPPETS']['OptionList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductVariationListMultipleItem");
					}
						
				}
			}

			if($onlyOneVariation == true && !$useSelect) {
				$output = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductVariationListSingle");
			}
			else {
				$GLOBALS['VariationChooseText'] = GetLang('ChooseA')." ".isc_html_escape($optionName);
				$output = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductVariationListMultiple");
			}
			$output = $GLOBALS['ISC_CLASS_TEMPLATE']->ParseSnippets($output, $GLOBALS['SNIPPETS']);
			$GLOBALS['SNIPPETS']['VariationList'] .= $output;
		}
	}

	/**
	 * Set the event date entry fields up.
	 */
	public function LoadEventDate()
	{
		$output = '';
		$productId = $this->productClass->GetProductId();
		$fields = ($this->productClass->GetEventDateFields());


		if (empty($fields['prodeventdaterequired'])) {
			return;
		}

		$GLOBALS['EventDateName'] = '<span class="Required">*</span>'.$fields['prodeventdatefieldname'];

		$from_stamp = $fields['prodeventdatelimitedstartdate'];
		$to_stamp = $fields['prodeventdatelimitedenddate'];

		$to_day = isc_date("d", $to_stamp);
		$from_day = isc_date("d", $from_stamp);

		$to_month = isc_date("m", $to_stamp);
		$from_month = isc_date("m", $from_stamp);

		$to_year = isc_date("Y", $to_stamp);
		$from_year = isc_date("Y", $from_stamp);

		$to_date = isc_date('jS M Y',$to_stamp);
		$from_date = isc_date('jS M Y',$from_stamp);

		$eventDateInvalidMessage = sprintf(GetLang('EventDateInvalid'), strtolower($fields['prodeventdatefieldname']));

		$comp_date = '';
		$comp_date_end = '';
		$eventDateErrorMessage = '';

		$edlimited = $fields['prodeventdatelimited'];
		if (empty($edlimited)) {
			$from_year = isc_date('Y');
			$to_year = isc_date('Y',isc_gmmktime(0, 0, 0, 0,0,isc_date('Y')+5));
			$GLOBALS['EventDateLimitations'] = '';
		} else {
			if ($fields['prodeventdatelimitedtype'] == 1) {
				$GLOBALS['EventDateLimitations'] = sprintf(GetLang('EventDateLimitations1'),$from_date,$to_date);

				$comp_date = isc_date('Y/m/d', $from_stamp);
				$comp_date_end = isc_date('Y/m/d', $to_stamp);

				$eventDateErrorMessage = sprintf(GetLang('EventDateLimitationsLong1'), strtolower($fields['prodeventdatefieldname']),$from_date, $to_date);

			} else if ($fields['prodeventdatelimitedtype'] == 2) {
				$to_year = isc_date('Y', isc_gmmktime(0, 0, 0, isc_date('m',$from_stamp),isc_date('d',$from_stamp),isc_date('Y',$from_stamp)+5));
				$GLOBALS['EventDateLimitations'] = sprintf(GetLang('EventDateLimitations2'), $from_date);

				$comp_date = isc_date('Y/m/d', $from_stamp);

				$eventDateErrorMessage = sprintf(GetLang('EventDateLimitationsLong2'), strtolower($fields['prodeventdatefieldname']),$from_date);


			} else if ($fields['prodeventdatelimitedtype'] == 3) {
				$from_year = isc_date('Y', time());
				$GLOBALS['EventDateLimitations'] = sprintf(GetLang('EventDateLimitations3'),$to_date);

				$comp_date = isc_date('Y/m/d', $to_stamp);

				$eventDateErrorMessage = sprintf(GetLang('EventDateLimitationsLong3'), strtolower($fields['prodeventdatefieldname']),$to_date);
			}
		}


		$GLOBALS['OverviewToDays'] = $this->GetDayOptions();
		$GLOBALS['OverviewToMonths'] = $this->GetMonthOptions();
		$GLOBALS['OverviewToYears'] = $this->GetYearOptions($from_year,$to_year);

		$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('EventDate');
		$GLOBALS['SNIPPETS']['EventDate'] = $output;

		$GLOBALS['EventDateJavascript'] = sprintf("<script type=\"text/javascript\"> var eventDateData = {type:'%s',compDate:'%s',compDateEnd:'%s',invalidMessage:'%s',errorMessage:'%s'}; </script>",
			$fields['prodeventdatelimitedtype'],
			$comp_date,
			$comp_date_end,
			$eventDateInvalidMessage,
			$eventDateErrorMessage
		);
	}

	/**
	 * Generate a list of the day options available for event dates.
	 *
	 * @return string HTML string containing option tags for days 1 to 31.
	 */
	private function GetDayOptions()
	{
		$output = '<option value=\'-1\'>---</option>';
		for($i = 1; $i <= 31; $i++) {
			$output .= sprintf("<option value='%d'>%s</option>", $i, $i);
		}

		return $output;
	}

	/**
	 * Generate select options for selecting a delivery date month.
	 *
	 * @return string HTML string containing option tags for available months.
	 */
	private function GetMonthOptions()
	{
		$output = '<option value=\'-1\'>---</option>';
		for($i = 1; $i <= 12; $i++) {
			$stamp = isc_gmmktime(0, 0, 0, $i, 1, 2000);
			$month = isc_date("M", $stamp);
			$output .= sprintf("<option value='%d'>%s</option>", $i, $month);
		}

		return $output;
	}

	/**
	 * Generate select options for selecting a delivery date year.
	 *
	 * @param int $from The year to start from.
	 * @param int $to The year to end at.
	 * @return string HTML string containing option tags for available years.
	 */
	private function GetYearOptions($from, $to)
	{
		$output = '<option value=\'-1\'>---</option>';
		for($i = $from; $i <= $to; $i++) {
			$output .= sprintf("<option value='%d'>%s</option>", $i, $i);
		}

		return $output;
	}
	
	private function GetLayerFieldModifierList($fieldID) {
		$list = '';
		
		$LayerModifiers = $GLOBALS['ISC_CLASS_DB']->FetchOne($GLOBALS['ISC_CLASS_DB']->Query('SELECT fieldlayermodifiers 
		FROM [|PREFIX|]product_configurable_fields 
		WHERE productfieldid = '.$fieldID), 'fieldlayermodifiers');
		
		if($LayerModifiers != '' || !is_array($LayerModifiers)) {
			$LayerModifiers = unserialize($LayerModifiers);
			
			foreach ($LayerModifiers as $value => $label) {
				$GLOBALS['LayerFieldNumber'] = $fieldID;
				$GLOBALS['LayerFieldModifierValue'] = $value;
				$GLOBALS['LayerFieldModifierLabel'] = $label;
				$list .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('LayerFieldModifierListItem');
			}
		}
		return $list;
	}

	/**
	 * Generate the configurable product fields if this product has any.
	 */
	public function LoadProductFieldsLayout()
	{
		$output = '';
		$productId = $this->productClass->GetProductId();
		$fields = $this->productClass->GetProductFields($productId);
		if(empty($fields)) {
			return;
		}

		foreach($fields as $field) {
			$GLOBALS['ProductFieldType'] = isc_html_escape($field['type']);
			$GLOBALS['ItemId'] = 0;
			$GLOBALS['ProductFieldId'] = (int)$field['id'];
			$GLOBALS['ProductFieldName'] = isc_html_escape($field['name']);
			$GLOBALS['ProductFieldInputSize'] = '';
			$GLOBALS['ProductFieldRequired'] = '';
			$GLOBALS['FieldRequiredClass'] = '';
			$GLOBALS['CheckboxFieldNameLeft'] = '';
			$GLOBALS['CheckboxFieldNameRight'] = '';
			$GLOBALS['HideCartFileName'] = 'display:none';
			$GLOBALS['HideDeleteFileLink'] = 'display:none';
			$GLOBALS['HideFileHelp'] = "display:none";
			$GLOBALS['ProductFieldRowExtraClasses'] = '';
			$snippetFile = 'ProductFieldInput';

			switch ($field['type']) {
				case 'textarea': {
					$snippetFile = 'ProductFieldTextarea';
					break;
				}
				case 'file': {
					if(!$GLOBALS['ISC_CLASS_TEMPLATE']->getIsMobileDevice()) {
						$GLOBALS['HideFileHelp'] = "";
						$GLOBALS['FileSize'] = Store_Number::niceSize($field['fileSize']*1024);
						$GLOBALS['FileTypes'] = $field['fileType'];
					}
					if($field['required']) {
						$this->hasRequiredFileFields = true;
					}
					break;
				}
				case 'checkbox': {
					$GLOBALS['CheckboxFieldNameLeft'] = isc_html_escape($field['name']);
					$snippetFile = 'ProductFieldCheckbox';
					break;
				}
				case 'select':
					$options = explode(',', $field['selectOptions']);
					$optionStr = '<option value="">' . GetLang('PleaseChooseAnOption') . '</option>';
					foreach ($options as $option) {
						$option = trim($option);
						$optionStr .= "<option value=\"" . isc_html_escape($option) . "\">" . isc_html_escape($option) . "</option>\n";
					}
					$GLOBALS['SelectOptions'] = $optionStr;
					//$snippetFile = ($field['layer'] == 0) ? 'ProductFieldSelect' : 'ProductFieldSelectLayer';
					if ($field['layer'] == 0) {
						$snippetFile = 'ProductFieldSelect';
					}
					else {
						$snippetFile = 'ProductFieldSelectLayer';
						$GLOBALS['LayerFieldModifierList'] = $this->GetLayerFieldModifierList($field['id']);
					}
					
					break;
				default: break;
			}
			
			if($field['required']) {
				$GLOBALS['ProductFieldRequired'] = '<span class="Required">*</span>';
				$GLOBALS['FieldRequiredClass'] = 'FieldRequired';
			}
			
			// NES: Aumento esto para ver si este campo activa otros por nombre, y les agrego la clase que tiene le evento de OnChange
			$query_field = 'SELECT * FROM [|PREFIX|]intelisis_configurable_fields WHERE productfieldid = "'.(int)$field['id'].'"';
			$result_field = $GLOBALS['ISC_CLASS_DB']->Query($query_field);
			$Campo = $GLOBALS['ISC_CLASS_DB']->Fetch($result_field);
			
			if($GLOBALS["ISC_CLASS_DB"]->CountResult('SELECT * FROM [|PREFIX|]intelisis_configurable_fields_detail WHERE ID = "'.$Campo['IDCampo'].'" AND IFNULL(OpcionIntelisis, "") != ""') > 0){
				$GLOBALS['FieldRequiredClass'] .= ' ActivatesFieldsByName';
			}
				
			if(trim($Campo['OpcionIntelisis']) != ''){
				if($GLOBALS["ISC_CLASS_DB"]->CountResult('SELECT icf.*
									FROM [|PREFIX|]intelisis_configurable_fields icf
									JOIN [|PREFIX|]intelisis_configurable_fields_detail icfd ON (icf.IDCampo=icfd.ID)
									WHERE icfd.OpcionIntelisis = "'.$Campo['OpcionIntelisis'].'"') > 0)
					$GLOBALS['ProductFieldRowExtraClasses'] = ' ActivatedByName ActivatedByName'.$field['id'];
					$GLOBALS['FieldRequiredClass'] .= ' SelectActivatedByName';
			}
				
			
			$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet($snippetFile);
		}
		$GLOBALS['SNIPPETS']['ProductFieldsList'] = $output;
	}

	/**
	 * Generate the bulk discounts window if this product has any.
	 */
	public function SetBulkDiscounts()
	{
		// Does this product have any bulk discount?
		if (!$this->productClass->CanUseBulkDiscounts()) {
			$GLOBALS['HideBulkDiscountLink'] = 'none';
			return;
		}

		$GLOBALS['HideBulkDiscountLink'] = '';
		$GLOBALS['BulkDiscountThickBoxTitle'] = sprintf(GetLang('BulkDiscountThickBoxTitle'), isc_html_escape($this->productClass->GetProductName()));

		$rates = '';
		$prevMax = 0;
		$query = "
			SELECT *
			FROM [|PREFIX|]product_discounts
			WHERE discountprodid = " . (int)$this->productClass->GetProductId() . "
			ORDER BY IF(discountquantitymax > 0, discountquantitymax, discountquantitymin) ASC
		";

		$result = $this->db->Query($query);
		while ($row = $this->db->Fetch($result)) {

			$range = '';
			if ($row['discountquantitymin'] == 0) {
				$range = isc_html_escape(intval($prevMax+1) . ' - ' . (int)$row['discountquantitymax']);
			} else if ($row['discountquantitymax'] == 0) {
				$range = isc_html_escape(sprintf(GetLang('BulkDiscountThickBoxDiscountOrAbove'), (int)$row['discountquantitymin']));
			} else {
				$range = isc_html_escape((int)$row['discountquantitymin'] . ' - ' . (int)$row['discountquantitymax']);
			}

			$discount = '';
			switch (isc_strtolower(isc_html_escape($row['discounttype']))) {
				case 'price':
					$discount = sprintf(GetLang('BulkDiscountThickBoxDiscountPrice'), $range, CurrencyConvertFormatPrice(isc_html_escape($row['discountamount'])));
					break;

				case 'percent':
					$discount = sprintf(GetLang('BulkDiscountThickBoxDiscountPercent'), $range, (int)$row['discountamount'] . '%');
					break;

				case 'fixed';
					$price = CalculateCustGroupDiscount($this->productClass->GetProductId(),$row['discountamount']);
					$discount = sprintf(GetLang('BulkDiscountThickBoxDiscountFixed'), $range, CurrencyConvertFormatPrice(isc_html_escape($price)));
					break;
			}

			$rates .= '<li>' . isc_html_escape($discount) . '</li>';

			if ($row['discountquantitymax'] !== 0) {
				$prevMax = $row['discountquantitymax'];
			}
		}

		$GLOBALS['BulkDiscountThickBoxRates'] = $rates;
		$GLOBALS['ProductBulkDiscountThickBox'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductBulkDiscountThickBox");
	}

	/**
	 * Fetch the vendor name/details if there is one for this product.
	 */
	private function SetVendorDetails()
	{
		// Get the vendor information
		$vendorInfo = $this->productClass->GetProductVendor();
		$GLOBALS['HideVendorDetails'] = 'display: none';
		$GLOBALS['VendorName'] = '';
		if(is_array($vendorInfo)) {
			$GLOBALS['HideVendorDetails'] = '';
			$GLOBALS['VendorName'] = '<a href="'.VendorLink($vendorInfo).'">'.isc_html_escape($vendorInfo['vendorname']).'</a>';
		}
	}

	/**
	 * Setup gift wrapping message if it is available for this product.
	 */
	private function SetWrappingDetails()
	{
		// Can this product be gift wrapped? And do we have any gift wrapping options set up?
		if(!$this->productClass->CanBeGiftWrapped() || $this->productClass->GetProductType() != PT_PHYSICAL) {
			$GLOBALS['HideGiftWrapMessage'] = 'display: none';
			return;
		}

		$GLOBALS['HideGiftWrapMessage'] = '';
		$GLOBALS['GiftWrappingAvailable'] = GetLang('GiftWrappingOptionsAvailable');
	}

	/**
	 * Generate the product images/thumbnails to be shown.
	 */
	private function SetProductImages()
	{

		$GLOBALS['ProductThumbWidth'] = ISC_PRODUCT_IMAGE::getSizeWidth(ISC_PRODUCT_IMAGE_SIZE_STANDARD);
		$GLOBALS['ProductThumbHeight'] = ISC_PRODUCT_IMAGE::getSizeHeight(ISC_PRODUCT_IMAGE_SIZE_STANDARD);

		$GLOBALS['ProductMaxTinyWidth'] = ISC_PRODUCT_IMAGE::getSizeWidth(ISC_PRODUCT_IMAGE_SIZE_TINY);
		$GLOBALS['ProductMaxTinyHeight'] = ISC_PRODUCT_IMAGE::getSizeHeight(ISC_PRODUCT_IMAGE_SIZE_TINY);



		$GLOBALS['ProductTinyBoxWidth'] = $GLOBALS['ProductMaxTinyWidth']+4;
		$GLOBALS['ProductTinyBoxHeight'] = $GLOBALS['ProductMaxTinyHeight']+4;


		$GLOBALS['ProductMaxZoomWidth'] = ISC_PRODUCT_IMAGE::getSizeWidth(ISC_PRODUCT_IMAGE_SIZE_ZOOM);
		$GLOBALS['ProductMaxZoomHeight'] = ISC_PRODUCT_IMAGE::getSizeHeight(ISC_PRODUCT_IMAGE_SIZE_ZOOM);

		$GLOBALS['ProductZoomWidth'] = ISC_PRODUCT_IMAGE::getSizeWidth(ISC_PRODUCT_IMAGE_SIZE_ZOOM);
		$GLOBALS['ProductZoomHeight'] = ISC_PRODUCT_IMAGE::getSizeHeight(ISC_PRODUCT_IMAGE_SIZE_ZOOM);


		$productImages = ISC_PRODUCT_IMAGE::getProductImagesFromDatabase($GLOBALS['ProductId']);
		$GLOBALS['NumProdImages'] = count($productImages);

		$GLOBALS['CurrentProdThumbImage'] = 0;
		$thumb = '';
		$curZoomImage = '';
		$GLOBALS['SNIPPETS']['ProductTinyImages'] = '';
		$GLOBALS['HideImageCarousel'] = 'display:none;';
		$GLOBALS['HideMorePicturesLink'] = 'display:none;';
		$thumbImageDescription = '';
		$i = 0;

		$GLOBALS['ProdImageJavascript'] = '';
		$GLOBALS['ProdImageZoomJavascript'] = '';
		$GLOBALS['LightBoxImageList'] = '';
		$GLOBALS['ZoomImageMaxWidth'] = 0;
		$GLOBALS['ZoomImageMaxHeight'] = 0;
		$GLOBALS['ZoomImageMaxWidthHeight'] = 0;
		$GLOBALS['HideAlwaysLinkedMorePicturesLink'] = 'display: none';

		if ($GLOBALS['NumProdImages']) {
			//Show image carousel

			if ($GLOBALS['NumProdImages'] == 2) {
				$var = "MorePictures1";
			} else if ($GLOBALS['NumProdImages'] == 1) {
				$var = "SeeLargerImage";
			} else {
				$var = "MorePictures2";
			}

			$GLOBALS['SeeMorePictures'] = sprintf(GetLang($var), count($productImages) - 1);
			$GLOBALS['HideAlwaysLinkedMorePicturesLink'] = '';

			if (GetConfig('ProductImagesTinyThumbnailsEnabled')) {
				$GLOBALS['HideImageCarousel'] = '';
			} else {
				$GLOBALS['HideMorePicturesLink'] = '';
			}

			$continue=false;

			foreach ($productImages as $productImage) {

				$thumbURL = '';
				$zoomImageURL = '';

				try{
					$thumbURL = $productImage->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_STANDARD, true);
					//$GLOBALS['ProductThumbURL'] = $thumbURL;
				} catch (Exception $exception) {
					// do nothing, will result in returning blank string, which is fine
				}

				try{
					$zoomImageURL = $productImage->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_ZOOM, true);
				} catch (Exception $exception) {
					// do nothing, will result in returning blank string, which is fine
				}

				if($thumbURL == '' && $zoomImageURL == '') {
					continue;
				}

				$resizedZoomDimension = $productImage->getResizedFileDimensions(ISC_PRODUCT_IMAGE_SIZE_ZOOM);
				$resizedTinyDimension = $productImage->getResizedFileDimensions(ISC_PRODUCT_IMAGE_SIZE_TINY);

				//calculate the max zoom image width and height
				if ($GLOBALS['ZoomImageMaxWidth'] < $resizedZoomDimension[ISC_PRODUCT_IMAGE_DIMENSIONS_WIDTH]) {

					$GLOBALS['ZoomImageMaxWidth'] = $resizedZoomDimension[ISC_PRODUCT_IMAGE_DIMENSIONS_WIDTH];
					//the height of the image has got the max width needed to calulate the image fancy box size.
					$GLOBALS['ZoomImageMaxWidthHeight'] = $resizedZoomDimension[ISC_PRODUCT_IMAGE_DIMENSIONS_HEIGHT];
				}

				if ($GLOBALS['ZoomImageMaxHeight'] < $resizedZoomDimension[ISC_PRODUCT_IMAGE_DIMENSIONS_HEIGHT]) {
					$GLOBALS['ZoomImageMaxHeight'] = $resizedZoomDimension[ISC_PRODUCT_IMAGE_DIMENSIONS_HEIGHT];
					//the width of the image has got the max height needed to calulate the image fancy box size.
					$GLOBALS['ZoomImageMaxHeightWidth'] = $resizedZoomDimension[ISC_PRODUCT_IMAGE_DIMENSIONS_HEIGHT];
				}

				$GLOBALS['ImageDescription'] = isc_html_escape($productImage->getDescription());
				if($GLOBALS['ImageDescription'] == '') {
					$GLOBALS['ImageDescription'] = GetLang("Image") . " " . ($i + 1);
				}

				//show image carousel
				if(GetConfig('ProductImagesTinyThumbnailsEnabled')==1) {

					$GLOBALS['ProdImageJavascript'] .= "
						ThumbURLs[".$i."] = " . isc_json_encode($thumbURL) . ";
						ProductImageDescriptions[".$i."] = " . isc_json_encode($GLOBALS['ImageDescription']) . ";
					";
					$GLOBALS['TinyImageOverJavascript'] = "showProductThumbImage(".$i.")";
					//$GLOBALS['ProductTinyImageURL'] = $productImage->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_TINY, true);

					try{
						$GLOBALS['ProductTinyImageURL'] = $productImage->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_TINY, true);
						//$GLOBALS['ProductThumbURL'] = $thumbURL;
					} catch (Exception $exception) {
						// do nothing, will result in returning blank string, which is fine
					}

					$GLOBALS['ProductThumbIndex'] = $i;
					if(GetConfig('ProductImageMode') == 'lightbox') {
						$GLOBALS['TinyImageClickJavascript'] = "showProductImageLightBox(".$i."); return false;";

					} else {
						$GLOBALS['TinyImageClickJavascript'] = "showProductImage('".GetConfig('ShopPath')."/productimage.php', ".$GLOBALS['ProductId'].", ".$i.");";
					}

					$GLOBALS['TinyImageWidth'] = $resizedTinyDimension[ISC_PRODUCT_IMAGE_DIMENSIONS_WIDTH];
					$GLOBALS['TinyImageHeight'] = $resizedTinyDimension[ISC_PRODUCT_IMAGE_DIMENSIONS_HEIGHT];
					$GLOBALS['TinyImageTopPadding'] = floor(($GLOBALS['ProductMaxTinyHeight'] - $GLOBALS['TinyImageHeight']) / 2);
					$GLOBALS['SNIPPETS']['ProductTinyImages'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductTinyImage");
					$continue = true;
				}

				if(GetConfig('ProductImagesImageZoomEnabled') == 1) {
					//check if zoom image is large enough for image zoomer
					if($resizedZoomDimension[ISC_PRODUCT_IMAGE_DIMENSIONS_WIDTH]<ISC_PRODUCT_IMAGE_MIN_ZOOM_WIDTH && $resizedZoomDimension[ISC_PRODUCT_IMAGE_DIMENSIONS_HEIGHT]<ISC_PRODUCT_IMAGE_MIN_ZOOM_HEIGHT) {
						$zoomImageURL = '';
					}
					$GLOBALS['ProdImageZoomJavascript'] .= "
						ZoomImageURLs[".$i."] = " . isc_json_encode($zoomImageURL) . ";
					";
					$continue = true;
				}

				//	$GLOBALS['ProductZoomImageURL'] = $zoomImageURL;

				//this image is the product page thumbnail
				if($i==0) {
					//get the thumb image for product page
					$thumb = $thumbURL;
					$curZoomImage = $zoomImageURL;
					$thumbImageDescription = $GLOBALS['ImageDescription'];
					//if there is no need to loop through images anymore, get out from the loop.
					if($continue === false) {
						break;
					}
				}
				$i++;
			}
		}

		$GLOBALS['VisibleImageTotal'] = $i+1;

		$GLOBALS['ShowImageZoomer'] = GetConfig('ProductImagesImageZoomEnabled');
		if ($GLOBALS['ShowImageZoomer']) {
			$GLOBALS['SNIPPETS']['ProductImageZoomer'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductImageZoomer");
		}
		$GLOBALS['ZoomImageURL'] = $curZoomImage;

		//if no product thumb images
		if($thumb == '') {
			if(GetConfig('DefaultProductImage') == 'template') {
				$thumb = $GLOBALS['IMG_PATH'].'/ProductDefault.gif';
			}
			else {
				$thumb = GetConfig('ShopPath').'/'.GetConfig('DefaultProductImage');
			}
		}

		// need to check for variation images
		//$GLOBALS['HideOnNoImages'] = 'display: none;';
		$GLOBALS['ImageDescription'] = $thumbImageDescription;
		$GLOBALS['ThumbImageURL'] = $thumb;


		//image popup javascript for the thumbnail image when the page is loaded
		$imagePopupLink = "showProductImage('".GetConfig('ShopPath')."/productimage.php', ".$GLOBALS['ProductId'].");";
		$GLOBALS['ImagePopupLink'] = $imagePopupLink;
		$GLOBALS['TinyImageClickJavascript'] = $imagePopupLink;

		// If we're showing images as a lightbox, we need to load up the URLs for the other images for this product
		if(GetConfig('ProductImageMode') == 'lightbox') {
			$GLOBALS['TinyImageClickJavascript'] = "showProductImageLightBox(); return false;";
			$GLOBALS['LightBoxImageJavascript'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('ProductImagesLightBox');
		}

		if ($GLOBALS['NumProdImages']) {
			$GLOBALS['SNIPPETS']['ProductThumbImage'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('ProductThumbImage');
		} else {
			$GLOBALS['SNIPPETS']['ProductThumbImage'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('ProductThumbImagePlaceholder');
		}
	}

	/**
	 * Setup product dimension details.
	 */
	private function SetProductDimensions()
	{
		if ($this->productClass->GetProductType() == PT_PHYSICAL && GetConfig('ShowProductWeight')) {
			// It's a physical product
			$GLOBALS['ProductWeight'] = $this->productClass->GetWeight();
		}
		else {
			// It's a digital product
			$GLOBALS['HideWeight'] = "none";
		}

		$product = $this->productClass->GetProduct();
		$dimensions = array(
			'ProductHeight' => 'prodheight',
			'ProductWidth' => 'prodwidth',
			'ProductDepth' => 'proddepth'
		);
		foreach($dimensions as $global => $field) {
			if($product[$field] > 0) {
				$GLOBALS[$global] = FormatWeight($product[$field], false);
				$hasDimensions = true;
			}
			else {
				$GLOBALS['Hide'.$global] = 'display: none';
			}
		}

		if(!isset($hasDimensions)) {
			$GLOBALS['HideDimensions'] = 'display: none';
		}
	}

	/**
	 * Setup the number of product reviews etc if this product has any.
	 */
	private function SetProductReviews()
	{
		if(!GetConfig('ShowProductRating')) {
			$GLOBALS['HideRating'] = "none";
		}

		$GLOBALS['Rating'] = $this->productClass->GetRating();
		if($GLOBALS['Rating'] == 0) {
			$GLOBALS['HideRating'] = 'none';
		}
		$GLOBALS['ProductNumReviews'] = (int) $this->productClass->GetNumReviews();

		// Are reviews disabled? Then don't show anything related to reviews
		if(!getProductReviewsEnabled()) {
			$GLOBALS['HideReviewLink'] = "none";
			$GLOBALS['HideRating'] = "none";
			$GLOBALS['HideReviews'] = "none";
		}
		else {
			// How many reviews are there?
			if ($this->productClass->GetNumReviews() == 0) {
				$GLOBALS['HideReviewLink'] = "none";
			}
			else {
				$GLOBALS['HideNoReviewsMessage'] = "none";
				if ($this->productClass->GetNumReviews() == 1) {
					$GLOBALS['ReviewLinkText'] = GetLang('ReviewLinkText1');
				} else {
					$GLOBALS['ReviewLinkText'] = sprintf(GetLang('ReviewLinkText2'), $this->productClass->GetNumReviews());
				}
				if (GetConfig('EnableProductTabs')) {
					$GLOBALS['ReviewLinkOnClick'] = "ActiveProductTab('ProductReviews_Tab'); return false;";
				}
			}
		}

	}

	/**
	 * Setup selected brand for this product.
	 */
	public function SetBrandDetails()
	{
		if(!$this->productClass->GetBrandName() || !GetConfig('ShowProductBrand')) {
			$GLOBALS['HideBrandLink'] = 'none';
			return false;
		}

		$GLOBALS['BrandName'] = isc_html_escape($this->productClass->GetBrandName());
		$GLOBALS['BrandLink'] = BrandLink($this->productClass->GetBrandName());
	}

	/**
	 * Setup any other misc attributes such as the condition, availability, etc.
	 */
	public function SetMiscAttributes()
	{
		if (!$this->productClass->IsConditionVisible()) {
			$GLOBALS['HideCondition'] = "none";
		}
		else {
			$GLOBALS['ProductCondition'] = isc_html_escape(GetLang('Condition' . $this->productClass->GetCondition()));
		}

		// Has a product availability been given?
		if ($this->productClass->GetAvailability() != "") {
			$GLOBALS['Availability'] = isc_html_escape($this->productClass->GetAvailability());
		} else {
			$GLOBALS['HideAvailability'] = "none";
		}

		// Is there an SKU for this product?
		if ($this->productClass->GetSKU() != "" && GetConfig('ShowProductSKU')) {
			$GLOBALS['SKU'] = isc_html_escape($this->productClass->GetSKU());
		}
		else {
			$GLOBALS['HideSKU'] = "none";
		}
		
		// Obtengo los datos del articulo de Intelisis. Ahorita solo usamos la Clave, pero me traigo toda la linea de Art por si piden mas columnas.
		$query_Articulo = 'SELECT ia.*, ip.Articulo AS "ClaveArticulo"
		FROM [|PREFIX|]intelisis_products ip
		LEFT OUTER JOIN [|PREFIX|]intelisis_Art ia ON (ia.Articulo=ip.Articulo)
		WHERE ip.productid = "'.$this->productClass->GetProductId().'"';
		$result_Articulo = $GLOBALS['ISC_CLASS_DB']->Query($query_Articulo);

		if($GLOBALS['ISC_CLASS_DB']->CountResult($result_Articulo) == 0) {
			$GLOBALS['ArticuloClave'] = '';
			$GLOBALS['HideClave'] = 'none';
		}
		else {
			$Articulo = $GLOBALS['ISC_CLASS_DB']->Fetch($result_Articulo);
			$GLOBALS['ArticuloClave'] = $Articulo['ClaveArticulo'];
			$GLOBALS['HideClave'] = '';
		}

		$GLOBALS['UPC'] = isc_html_escape(trim($this->productClass->GetProductUPC()));
		if (!$GLOBALS['UPC']) {
			$GLOBALS['HideUPC'] = "none";
		}

		// do we want to show an AddThis.com link on our product?
		if (GetConfig('ShowAddThisLink')) {
			$GLOBALS['AddThisLink'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('AddThisLink');
		}

		$likeButtonPosition = GetConfig('FacebookLikeButtonPosition');
		if (GetConfig('FacebookLikeButtonEnabled') && GetConfig('FacebookLikeButtonAdminIds')) {
			$GLOBALS['FacebookLikeButton' . ucfirst($likeButtonPosition)] = ISC_FACEBOOKLIKEBUTTON::getButtonHTML();
		}
	}

	/**
	 * If we're showing inventory, setup the inventory details for this product.
	 */
	private function SetInventoryDetails()
	{
		if (!GetConfig('ShowInventory') || $this->productClass->GetProductInventoryTracking() == 0 || ($this->productClass->IsPreOrder() && !GetConfig('ShowPreOrderInventory'))) {
			$GLOBALS['HideCurrentStock'] = "display: none;";
			return;
		}

		if ($this->productClass->IsPreOrder()) {
			$GLOBALS['CurrentStockLabel'] = GetLang('PreOrderStock');
		} else {
			$GLOBALS['CurrentStockLabel'] = GetLang('CurrentStock');
		}

		$GLOBALS['InventoryList'] = '';
		if ($this->productClass->GetProductInventoryTracking() == 2) {
			$options = $this->productClass->GetProductVariationOptions();
			if (empty($options)) {
				$GLOBALS['HideCurrentStock'] = "display: none;";
			}
		}
		else if ($this->productClass->GetProductInventoryTracking() == 1) {
			$currentStock = $this->productClass->GetInventoryLevel();
			if ($currentStock <= 0) {
				$GLOBALS['InventoryList'] = GetLang('SoldOut');
			}
			else {
				$GLOBALS['InventoryList'] = $currentStock;
			}
		}
	}
}
