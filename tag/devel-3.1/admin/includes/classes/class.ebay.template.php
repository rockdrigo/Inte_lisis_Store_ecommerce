<?php

class ISC_ADMIN_EBAY_TEMPLATE {
	private $_siteId;
	private $_siteCode;

	private $_templateId;
	private $_templateName;
	private $_defaultTemplate;

	private $_privateListing;

	private $_sellingMethod;

	private $_currency;

	private $_reservePriceOptions = null;
	private $_startPriceOptions = null;
	private $_buyItNowPriceOptions = null;

	private $_listingDuration;

	private $_acceptBestOffers;

	private $_quantityToSell;

	private $_paymentMethods = array();

	private $_payPalEmailAddress;

	private $_itemLocationCountry;
	private $_itemLocationZip;
	private $_itemLocationCityState;

	private $_useProductImage;
	private $_itemImage = '';

	private $_lotSize;

	private $_allowCategoryMapping;
	private $_primaryCategoryOptions = array();
	private $_secondaryCategoryOptions = array();
	private $_primaryCategoryId;
	private $_secondaryCategoryId;
	private $_secondaryCategoryName;
	private $_primaryStoreCategoryId;
	private $_primaryStoreCategoryName;
	private $_secondaryStoreCategoryId;
	private $_secondaryStoreCategoryName;

	private $_productData;

	private $_checkoutInstructions;
	private $_acceptReturns;
	private $_returnOfferedAs;
	private $_returnsPeriod;
	private $_returnCostPaidBy;
	private $_additionalPolicyInfo;

	private $_counterStyle;
	private $_galleryOption;
	private $_featuredGalleryDuration;
	private $_listingFeatures = array();

	private $_handlingTime;
	private $_useSalesTax = false;
	private $_salesTaxState;
	private $_salesTaxPercent;
	private $_salesTaxIncludesShipping;

	private $_shippingServices = array();
	private $_shippingSettings = array();
	private $_useDomesticShipping = false;
	private $_useInternationalShipping = false;

	private $_scheduleDate = null;

	public function __construct($templateId = 0)
	{
		$templateId = (int)$templateId;

		if (!$templateId) {
			return;
		}

		$query = '
			SELECT
				*
			FROM
				[|PREFIX|]ebay_listing_template
			WHERE
				id = ' . (int)$templateId;

		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!($row = $GLOBALS['ISC_CLASS_DB']->Fetch($res))) {
			throw new Exception('Template ' . $templateId . ' not found.');
		}

		// get prices
		$query = 'SELECT * FROM [|PREFIX|]ebay_listing_prices WHERE ebay_listing_template_id = ' . $templateId;
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$prices = array();
		while ($priceRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
			$prices[$priceRow['price_type']] = $priceRow;
		}

		// get shipping
		$query = '
			SELECT
				es.*
			FROM
				[|PREFIX|]ebay_shipping es
			WHERE
				es.ebay_listing_template_id = ' . $templateId . '
			ORDER BY
				es.id';
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$shippingSettings = array();
		while ($shippingRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
			$shippingSettings[$shippingRow['area']] = $shippingRow;
		}

		// get shipping services
		$query = '
			SELECT
				ess.*,
				es.area,
				es.ebay_listing_template_id
			FROM
				[|PREFIX|]ebay_shipping_serv ess
				LEFT JOIN [|PREFIX|]ebay_shipping es ON es.id = ess.ebay_shipping_id
			WHERE
				es.ebay_listing_template_id = ' . $templateId . '
			ORDER BY
				ess.id';
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$shippingServices = array();
		while ($shippingRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
			// only support one location at this stage
			$shipToLocations = unserialize($shippingRow['ship_to_locations']);
			$shipToLocation = '';
			if (!empty($shipToLocations)) {
				$shipToLocation = $shipToLocations[0];
			}
			$shippingRow['ship_to_location'] = $shipToLocation;
			$shippingRow['ship_to_locations'] = $shipToLocations;
			$shippingServices[$shippingRow['area']][$shippingRow['name']] = $shippingRow;
		}

		$this->_siteId = $row['site_id'];
		$this->_siteCode = GetClass('ISC_ADMIN_EBAY')->getSiteCodeFromSiteId($this->_siteId);

		// set the currency
		$currencyId = GetClass('ISC_ADMIN_EBAY')->getCurrencyFromSiteId($this->_siteId);
		if (!$currencyId) {
			throw new Exception('Currency for site not found');
		}
		$this->_currency = GetCurrencyById($currencyId);

		$this->_templateId = $templateId;
		$this->_templateName = $row['name'];
		$this->_defaultTemplate = (bool)$row['is_default'];
		$this->_privateListing = (bool)$row['is_private'];

		$this->_quantityToSell = $row['quantities'];
		$this->_useProductImage = (bool)$row['use_prod_image'];
		$this->_lotSize = $row['lot_size'];
		$this->_acceptBestOffers = (bool)$row['accept_best_offer'];

		$this->_sellingMethod = $row['listing_type'];
		$this->_listingDuration = $row['listing_duration'];

		$this->_itemLocationCountry = $row['item_country'];
		$this->_itemLocationZip = $row['item_zip'];
		$this->_itemLocationCityState = $row['item_city'];

		$this->_paymentMethods = unserialize($row['payment_method']);
		$this->_payPalEmailAddress = $row['paypal_email'];

		foreach ($prices as $price) {
			switch ($price['price_type']) {
				case ISC_ADMIN_EBAY::RESERVE_PRICE_TYPE:
					$this->_reservePriceOptions = $price;
					break;
				case ISC_ADMIN_EBAY::STARTING_PRICE_TYPE:
					$this->_startPriceOptions = $price;
					break;
				case ISC_ADMIN_EBAY::BUY_PRICE_TYPE:
					$this->_buyItNowPriceOptions = $price;
					break;
			}
		}

		// set category info
		$this->_primaryCategoryOptions = unserialize($row['primary_category_options']);
		$this->_secondaryCategoryOptions = unserialize($row['secondary_category_options']);
		$this->_primaryCategoryId = $row['primary_category_id'];
		$this->_secondaryCategoryId = $row['secondary_category_id'];
		$this->_secondaryCategoryName = $row['secondary_category_name'];
		$this->_primaryStoreCategoryId = $row['store_category1_id'];
		$this->_primaryStoreCategoryName = $row['store_category1_name'];
		$this->_secondaryStoreCategoryId = $row['store_category2_id'];
		$this->_secondaryStoreCategoryName = $row['store_category2_name'];

		// checkout and return info
		$this->_checkoutInstructions = $row['payment_instruction'];
		$this->_acceptReturns = (bool)$row['accept_return'];
		$this->_returnOfferedAs = $row['return_offer_as'];
		$this->_returnsPeriod = $row['return_period'];
		$this->_returnCostPaidBy = $row['return_cost_by'];
		$this->_additionalPolicyInfo = $row['return_policy_description'];

		// counter
		$this->_counterStyle = $row['counter_style'];

		$this->_galleryOption = $row['gallery_opt'];
		$this->_featuredGalleryDuration = $row['featured_gallery_duration'];

		// listing features/enhancements
		$this->_listingFeatures = unserialize($row['listing_opt']);

		// sales tax - US only
		$this->_useSalesTax = (bool)$row['use_salestax'];
		$this->_salesTaxState = $row['sales_tax_states'];
		$this->_salesTaxPercent = $row['salestax_percent'];
		$this->_salesTaxIncludesShipping = (int)$row['salestax_inc_shipping'];

		// shipping
		$this->_shippingSettings = $shippingSettings;
		$this->_shippingServices = $shippingServices;
		$this->_useDomesticShipping = (bool)$row['use_domestic_shipping'];
		$this->_useInternationalShipping = (bool)$row['use_international_shipping'];
		$this->_handlingTime = (int)$row['handling_time'];

		// option to specify this when listing has been removed and will be allowed always
		$this->_allowCategoryMapping = true;
	}

	/**
	* Sets the product data to be used to determine certain template options such as start, reserve prices etc.
	*
	* @param array $productData
	*/
	public function setProductData($productData)
	{
		$this->_productData = $productData;

		// set the item image
		if (isset($this->_productData['imageid'])) {
			$image = new ISC_PRODUCT_IMAGE;
			$image->populateFromDatabaseRow($this->_productData);

			try {
				$this->_itemImage = $image->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_STANDARD, true, true, false);
			} catch (Exception $exception) { }
		}
	}

	/**
	* Gets the Id of the eBay site this template is defined for
	*
	* @return int The eBay site Id
	*/
	public function getSiteId()
	{
		return $this->_siteId;
	}

	/**
	* Gets the eBay site code
	*
	* @return string The site code. eg US
	*/
	public function getSiteCode()
	{
		return $this->_siteCode;
	}

	/**
	* Gets the Id of this template
	*
	* @return int The template Id
	*/
	public function getTemplateId()
	{
		return $this->_templateId;
	}

	/**
	* Gets the name of this template
	*
	* @return string The template name
	*/
	public function getTemplateName()
	{
		return $this->_templateName;
	}

	/**
	* Checks if this template is the default template
	*
	* @return bool TRUE if default, FALSE otherwise
	*/
	public function isDefaultTemplate()
	{
		return $this->_defaultTemplate;
	}

	/**
	* Is this a private listing?
	*
	* @return bool TRUE if the listing is marked as private, FALSE otherwise
	*/
	public function isPrivateListing()
	{
		return $this->_privateListing;
	}

	/**
	* Gets the selling method for the listing. Either Chinese or FixedPriceItem
	*
	* @return string The selling method
	*/
	public function getSellingMethod()
	{
		return $this->_sellingMethod;
	}

	/**
	* Gets the reserve price for the listing
	*
	* @return double The reserve price or FALSE if not set
	*/
	public function getReservePrice()
	{
		if (!$this->_reservePriceOptions) {
			return false;
		}

		return $this->getPriceFromOptions($this->_reservePriceOptions);
	}

	/**
	* Checks if this template is using a reserve price
	*
	* @return bool TRUE if using a reserve price, FALSE otherwise
	*/
	public function getReservePriceUsed()
	{
		return (bool)$this->_reservePriceOptions;
	}

	/**
	* Gets the type of reserve price selected: ProductPrice, PriceExtra or CustomPrice
	*
	* @return string The reserve price option
	*/
	public function getReservePriceOption()
	{
		return $this->_reservePriceOptions['selected_type'];
	}

	/**
	* Gets the custom reserve price
	*
	* @return double
	*/
	public function getReservePriceCustomPrice()
	{
		return ($this->_reservePriceOptions['price'] / 1);
	}

	/**
	* Gets the reserve price calculated operator. 'plus' or 'minus'
	*
	* @return string The operator
	*/
	public function getReservePriceCalcOperator()
	{
		return $this->_reservePriceOptions['calculate_operator'];
	}

	/**
	* Gets the reserve price calculated price
	*
	* @return double The calculated price
	*/
	public function getReservePriceCalcPrice()
	{
		return ($this->_reservePriceOptions['calculate_price'] / 1);
	}

	/**
	* Gets the reserve price calculated option. 'percent' or 'amount'
	*
	* @return string The option
	*/
	public function getReservePriceCalcOption()
	{
		return $this->_reservePriceOptions['calculate_option'];
	}

	/**
	* Gets the start price of the listing
	*
	* @param bool $convert Should the price be converted into the template's currency?
	* @return double The starting price or FALSE if not set
	*/
	public function getStartPrice($convert = true)
	{
		if (!$this->_startPriceOptions) {
			return false;
		}

		return $this->getPriceFromOptions($this->_startPriceOptions, $convert);
	}

	/**
	* Gets the type of start price selected: ProductPrice, PriceExtra or CustomPrice
	*
	* @return string The start price option
	*/
	public function getStartPriceOption()
	{
		return $this->_startPriceOptions['selected_type'];
	}

	/**
	* Gets the custom start price
	*
	* @return double
	*/
	public function getStartPriceCustomPrice()
	{
		return ($this->_startPriceOptions['price'] / 1);
	}

	/**
	* Gets the start price calculated operator. 'plus' or 'minus'
	*
	* @return string The operator
	*/
	public function getStartPriceCalcOperator()
	{
		return $this->_startPriceOptions['calculate_operator'];
	}

	/**
	* Gets the start price calculated price
	*
	* @return double The calculated price
	*/
	public function getStartPriceCalcPrice()
	{
		return ($this->_startPriceOptions['calculate_price'] / 1);
	}

	/**
	* Gets the start price calculated option. 'percent' or 'amount'
	*
	* @return string The option
	*/
	public function getStartPriceCalcOption()
	{
		return $this->_startPriceOptions['calculate_option'];
	}

	/**
	* Gets the Buy It Now price for a listing. NB: A Fixed Price Item listing stores its "buy it now" price in the Start price
	*
	* @return double The Buy It Now price or FALSE if not set
	*/
	public function getBuyItNowPrice()
	{
		if (!$this->_buyItNowPriceOptions) {
			return false;
		}

		return $this->getPriceFromOptions($this->_buyItNowPriceOptions);
	}

	/**
	* Checks if this template is using a Buy It Now price
	*
	* @return bool TRUE if using a Buy It Now price, FALSE otherwise
	*/
	public function getBuyItNowPriceUsed()
	{
		return (bool)$this->_buyItNowPriceOptions;
	}

	/**
	* Gets the type of Buy It Now price selected: ProductPrice, PriceExtra or CustomPrice
	*
	* @return string The Buy It Now price option
	*/
	public function getBuyItNowPriceOption()
	{
		return $this->_buyItNowPriceOptions['selected_type'];
	}

	/**
	* Gets the custom Buy It Now price
	*
	* @return double
	*/
	public function getBuyItNowPriceCustomPrice()
	{
		return ($this->_buyItNowPriceOptions['price'] / 1);
	}

	/**
	* Gets the Buy It Now price calculated operator. 'plus' or 'minus'
	*
	* @return string The operator
	*/
	public function getBuyItNowPriceCalcOperator()
	{
		return $this->_buyItNowPriceOptions['calculate_operator'];
	}

	/**
	* Gets the Buy It Now price calculated price
	*
	* @return double The calculated price
	*/
	public function getBuyItNowPriceCalcPrice()
	{
		return ($this->_buyItNowPriceOptions['calculate_price'] / 1);
	}

	/**
	* Gets the Buy It Now price calculated option. 'percent' or 'amount'
	*
	* @return string The option
	*/
	public function getBuyItNowPriceCalcOption()
	{
		return $this->_buyItNowPriceOptions['calculate_option'];
	}

	/**
	* Calculates a price based on the chosen options for that price type and the product data
	*
	* @param array $options The price options
	* @param bool $convert Should the price be converted into the template's currency?
	* @return double The calculated price
	*/
	private function getPriceFromOptions($options, $convert = true)
	{
		switch ($options['selected_type']) {
			case 'CustomPrice':
				// no conversion necessary, assume it's already entered based on site currency
				return $options['price'];
			case 'ProductPrice':
				$productPrice = $this->getProductPrice();
			case 'PriceExtra':
				$productPrice = $this->getProductPrice();

				$modifierAmount = (double)$options['calculate_price'];
				if ($options['calculate_option'] == 'percent') {
					$modifierAmount = ($modifierAmount / 100) * $productPrice;
				}

				if ($options['calculate_operator'] == 'plus') {
					$productPrice += $modifierAmount;
				}
				else {
					$productPrice -= $modifierAmount;
				}
				break;
		}

		// convert our price from our currency to site's currency
		if ($convert) {
			return ConvertPriceToCurrency($productPrice, $this->getCurrency());
		}

		return $productPrice;
	}

	/**
	* Returns the current product price for the set product data
	*
	* @return double The product price
	*/
	public function getProductPrice()
	{
		// update to do correct calculations (tax etc?)
		return $this->_productData['prodprice'];
	}

	/**
	* Gets the listing duration
	*
	* @return string The listing duration code. eg Days_7
	*/
	public function getListingDuration()
	{
		return $this->_listingDuration;
	}

	/**
	* Does the listing accept best offers?
	*
	* @return bool TRUE if best offers are accepted, FALSE otherwise
	*/
	public function getAcceptBestOffers()
	{
		return $this->_acceptBestOffers;
	}

	/**
	* Gets the quantity to sell
	*
	* @return int The quantity
	*/
	public function getQuantityToSell()
	{
		return $this->_quantityToSell;
	}

	/**
	* Gets the true quantity that are being sold.
	* For a chinese auction this is always 1, for Fixed Price it is the specified quantity.
	*
	* @return int The true selling quantity
	*/
	public function getTrueQuantityToSell()
	{
		if ($this->getSellingMethod() == ISC_ADMIN_EBAY::CHINESE_AUCTION_LISTING) {
			return 1;
		}

		$qtyToSell = $this->getQuantityToSell();

		// does the product have a variation? the quantity to sell will be the amount of combinations in total x quantity to sell
		if ($this->_productData['prodvariationid']) {
			$combinationsTotal = Store_Variations::getCombinationsCount($this->_productData['productid'], $this->_productData['prodvariationid']);
			$qtyToSell *= $combinationsTotal;
		}

		return $qtyToSell;
	}

	/**
	* Gets the payment methods for the listing
	*
	* @return array An array of eBay payment method codes. eg PayPal
	*/
	public function getPaymentMethods()
	{
		return $this->_paymentMethods;
	}

	/**
	* Gets the PayPal email address
	*
	* @return string The PayPal email address
	*/
	public function getPayPalEmailAddress()
	{
		return $this->_payPalEmailAddress;
	}

	/**
	* Gets the item's combined city/state and country
	*
	* @return string The item location
	*/
	public function getItemLocation()
	{
		// when country and city/state are combined to a single field, return that here
		$location = $this->getItemLocationCountry() . ', ' . $this->getItemLocationCityState();
		return $location;
	}

	/**
	* Gets the item's country
	*
	* @return string The item's country
	*/
	public function getItemLocationCountry()
	{
		return $this->_itemLocationCountry;
	}

	/**
	* Gets the item's zip or postal code
	*
	* @return string The item's zip code
	*/
	public function getItemLocationZip()
	{
		return $this->_itemLocationZip;
	}

	/**
	* Gets the items city/state location
	*
	* @return string The item's city/state
	*/
	public function getItemLocationCityState()
	{
		return $this->_itemLocationCityState;
	}

	/**
	* Gets the item's default image to use in the listing
	*
	@ return mixed The item's photo URL or FALSE if not using an image
	*/
	public function getItemPhoto()
	{
		if ($this->_useProductImage) {
			return $this->_itemImage;
		}
		else {
			return false;
		}
	}

	/**
	* Checks whether the template is set to use the item's photo
	*
	* @return bool TRUE if using item's photo, FALSE otherwise
	*/
	public function getUseItemPhoto()
	{
		return $this->_useProductImage;
	}

	/**
	* Overrides the item image with the specified URL
	*
	* @param strin $url The new image URL
	*/
	public function setItemPhoto($url)
	{
		$this->_itemImage = $url;
	}

	/**
	* Gets the item's condition mapped to the primary category
	*
	* @return int The condition ID or FALSE if the condition isn't mapped
	*/
	public function getItemCondition()
	{
		$condition = strtolower($this->_productData['prodcondition']) . 'Condition';

		if (!empty($this->_primaryCategoryOptions[$condition])) {
			return (int)$this->_primaryCategoryOptions[$condition];
		}

		return false;
	}

	/**
	* Gets the lot size for the listing
	*
	* @return int The lot size
	*/
	public function getLotSize()
	{
		return $this->_lotSize;
	}

	/**
	* Gets the currency data for the listing
	*
	* @return array The currency data
	*/
	public function getCurrency()
	{
		return $this->_currency;
	}

	/**
	* Gets the currency code for the listing
	*
	* @return string The currency code. eg USD
	*/
	public function getCurrencyCode()
	{
		return $this->_currency['currencycode'];
	}

	/**
	* Checks if eBay should be allowed to remap old categories to new ones
	*
	* @return bool TRUE if mapping is allowed, FALSE otherwise
	*/
	public function getAllowCategoryMapping()
	{
		return $this->_allowCategoryMapping;
	}

	/**
	* Sets if eBay should be allowed to remap categories if the chosen ones are out of date
	*
	* @param bool $allowMapping
	*/
	public function setAllowCategoryMapping($allowMapping)
	{
		$this->_allowCategoryMapping = $allowMapping;
	}

	/**
	* Gets the primary eBay category Id
	*
	* @return int The catgory Id
	*/
	public function getPrimaryCategoryId()
	{
		return $this->_primaryCategoryId;
	}

	/**
	* Gets the defined category options for the primary category
	*
	* @return array The category options
	*/
	public function getPrimaryCategoryOptions()
	{
		return $this->_primaryCategoryOptions;
	}

	/**
	* Gets the defined category options for the secondary category
	*
	* @return array The category options
	*/
	public function getSecondaryCategoryOptions()
	{
		if (is_array($this->_secondaryCategoryOptions)) {
			return $this->_secondaryCategoryOptions;
		}
		return array();
	}

	/**
	* Gets the secondary eBay category Id
	*
	* @return int The catgory Id
	*/
	public function getSecondaryCategoryId()
	{
		return $this->_secondaryCategoryId;
	}

	/**
	* Gets the secondary eBay category name
	*
	* @return string The category name
	*/
	public function getSecondaryCategoryName()
	{
		return $this->_secondaryCategoryName;
	}

	/**
	* Gets the users primary store category Id
	*
	* @return int The catgory Id
	*/
	public function getPrimaryStoreCategoryId()
	{
		return $this->_primaryStoreCategoryId;
	}

	/**
	* Gets the users secondary store category name
	*
	* @return string The category name
	*/
	public function getPrimaryStoreCategoryName()
	{
		return $this->_primaryStoreCategoryName;
	}

	/**
	* Gets the users secondary store category Id
	*
	* @return int The catgory Id
	*/
	public function getSecondaryStoreCategoryId()
	{
		return $this->_secondaryStoreCategoryId;
	}

	/**
	* Gets the users secondary store category name
	*
	* @return string The category name
	*/
	public function getSecondaryStoreCategoryName()
	{
		return $this->_secondaryStoreCategoryName;
	}

	/**
	* Gets the checkout instructions
	*
	* @return string The instructions
	*/
	public function getCheckoutInstructions()
	{
		return $this->_checkoutInstructions;
	}

	/**
	* Checks if returns are accepted
	*
	* @return bool TRUE if returns are accepted, FALSE otherwise
	*/
	public function getReturnsAccepted()
	{
		return $this->_acceptReturns;
	}

	/**
	* Gets the returns accepted option code
	*
	* @return string The return accepted code
	*/
	public function getReturnsAcceptedOption()
	{
		if ($this->_acceptReturns) {
			return 'ReturnsAccepted';
		}
		else {
			return 'ReturnsNotAccepted';
		}
	}

	/**
	* Gets the type of return offered. eg. Money Back, Exchange etc.
	*
	* @return string The return type offered
	*/
	public function getReturnOfferedAs()
	{
		return $this->_returnOfferedAs;
	}

	/**
	* Gets the period that returns are allowed within. eg. Days_7
	*
	* return string The return period code
	*/
	public function getReturnsPeriod()
	{
		return $this->_returnsPeriod;
	}

	/**
	* Gets who will pay the cost of shipping for returning the item
	*
	* @return string The payer
	*/
	public function getReturnCostPaidBy()
	{
		return $this->_returnCostPaidBy;
	}

	/**
	* Gets any additional return policy info
	*
	* @return string The policy info
	*/
	public function getAdditionalPolicyInfo()
	{
		return $this->_additionalPolicyInfo;
	}

	/**
	* Gets the hit counter style code
	*
	* @return string The hit counter codee
	*/
	public function getCounterStyle()
	{
		return $this->_counterStyle;
	}

	/**
	* Gets the paid listing enhancements chosen for the listing
	*
	* @return array The listing enhancements
	*/
	public function getListingFeatures()
	{
		return $this->_listingFeatures;
	}

	/**
	* Gets the gallery type to use in search results
	*
	* @return string The gallery type code
	*/
	public function getGalleryType()
	{
		return $this->_galleryOption;
	}

	/**
	* Gets the duration to show a featued gallery in search results.
	*
	* @return string The gallery duration: Days_7 or LifeTime
	*/
	public function getFeaturedGalleryDuration()
	{
		return $this->_featuredGalleryDuration;
	}

	/**
	* Gets the handling/dispatch time in days
	*
	* @return int The handling time
	*/
	public function getHandlingTime()
	{
		return $this->_handlingTime;
	}

	/**
	* Checks if sales tax is defined for the listing - Applies to US only
	*
	* @return bool TRUE if sales tax is used, FALSE otherwise
	*/
	public function getUseSalesTax()
	{
		return $this->_useSalesTax;
	}

	/**
	* Gets the state for which tax is being collected
	*
	* @return string The state code
	*/
	public function getSalesTaxState()
	{
		return $this->_salesTaxState;
	}

	/**
	* The percentage of the item's original price to be charged as sales tax
	*
	* @return double The sales tax percentage
	*/
	public function getSalesTaxPercent()
	{
		return ((double)$this->_salesTaxPercent / 1);
	}

	/**
	* Gets whether shipping costs are part of the base amount that was taxed
	*
	* @return bool TRUE of shipping costs are included, FALSE otherwise
	*/
	public function getShippingIncludedInTax()
	{
		return $this->_salesTaxIncludesShipping;
	}

	/**
	* Checks if the listing has enabled domestic shipping
	*
	* @return bool TRUE if using domestic shipping, FALSE if local pick-up only
	*/
	public function getUseDomesticShipping()
	{
		return $this->_useDomesticShipping;
	}

	/**
	* Gets the chosen domestic shipping settings
	*
	* @return array The domestic settings or FALSE
	*/
	public function getDomesticShippingSettings()
	{
		if (isset($this->_shippingSettings['Domestic'])) {
			return $this->_shippingSettings['Domestic'];
		}

		return false;
	}

	/**
	* Gets domestic only shipping services
	*
	* @return array The domestic services or FALSE
	*/
	public function getDomesticShippingServices()
	{
		if (isset($this->_shippingServices['Domestic'])) {
			return $this->_shippingServices['Domestic'];
		}

		return false;
	}

	/**
	* Checks if international shipping has been defined for the listing
	*
	* @return bool TRUE if international services have been defined, FALSE otherwise
	*/
	public function getUseInternationalShipping()
	{
		return $this->_useInternationalShipping;
	}

	/**
	* Gets the chosen domestic shipping settings
	*
	* @return array The domestic settings or FALSE
	*/
	public function getInternationalShippingSettings()
	{
		if (isset($this->_shippingSettings['International'])) {
			return $this->_shippingSettings['International'];
		}

		return false;
	}

	/**
	* Gets domestic only shipping services
	*
	* @return array The domestic services or FALSE
	*/
	public function getInternationalShippingServices()
	{
		if (isset($this->_shippingServices['International'])) {
			return $this->_shippingServices['International'];
		}

		return false;
	}

	/**
	* Gets the combined shipping cost model shipping type
	*
	* @return string The shiping type code
	*/
	public function getShippingType()
	{
		$shippingType = '';
		$domesticType = '';
		$internationalType = '';

		if ($this->getUseDomesticShipping()) {
			$domesticSettings = $this->getDomesticShippingSettings();
			$domesticType = $domesticSettings['cost_type'];
		}

		if ($this->getUseDomesticShipping()) {
			$internationalSettings = $this->getInternationalShippingSettings();
			$internationalType = $internationalSettings['cost_type'];
		}

		if ($domesticType == $internationalType) {
			$shippingType = $domesticType;
		}
		elseif ($domesticType != '' && $internationalType != '')  {
			$shippingType = $domesticType . 'Domestic' . $internationalType . 'International';
		}
		elseif ($domesticType != ''){
			$shippingType = $domesticType;
		}
		elseif ($internationalType != '') {
			$shippingType = $internationalType;
		}
		else {
			$shippingType = 'NotSpecified';
		}

		return $shippingType;
	}

	/**
	* The item's depth
	*
	* @return double
	*/
	public function getItemDepth()
	{
		return $this->_productData['proddepth'];
	}

	/**
	* The item's width
	*
	* @return double
	*/
	public function getItemWidth()
	{
		return $this->_productData['prodwidth'];
	}

	/**
	* The item's length
	*
	* @return double
	*/
	public function getItemLength()
	{
		return $this->_productData['prodheight'];
	}

	/**
	* The item's weight
	*
	* @return double
	*/
	public function getItemWeight()
	{
		return $this->_productData['prodweight'];
	}

	/**
	* Sets the date/time for when the listing becomes active
	*
	* @param string $scheduleDate The schedule date in ISO 8601
	*/
	public function setScheduleDate($scheduleDate)
	{
		$this->_scheduleDate = $scheduleDate;
	}

	/**
	* Gets the date/time for when the listing becomes active
	*
	* @return string The ISO 8601 schedule date
	*/
	public function getScheduleDate()
	{
		return $this->_scheduleDate;
	}
}