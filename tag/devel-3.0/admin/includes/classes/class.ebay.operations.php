<?php
/**
* A collection of operations that uses the eBay API
*/
class ISC_ADMIN_EBAY_OPERATIONS {

	const ROOT_CATEGORY_ID = -1;

	/**
	 * @var boolean Site Id that used for eBay request header
	 */
	private static $SITE_ID = 0;

	/**
	 * This function set the site id
	 *
	 * @param int $siteId The eBay site id
	 */
	public static function setSiteId($siteId = 0)
	{
		self::$SITE_ID = $siteId;
	}

	/**
	* Retrieve available meta-data for the store
	*
	* @return SimpleXMLElement The meta-data for the store
	*/
	public static function geteBayDetails()
	{
		$api = new ISC_ADMIN_EBAY_API(self::$SITE_ID);
		$xml = $api->getBaseXMLRequest('GeteBayDetails');

		return $api->DoRequest('GeteBayDetails', $xml);
	}

	/**
	* Retrieve the official eBay system time in GMT
	*
	* @return SimpleXMLElement The meta-data for the store
	*/
	public static function geteBayOfficialTime()
	{
		$api = new ISC_ADMIN_EBAY_API(self::$SITE_ID);
		$xml = $api->getBaseXMLRequest('GeteBayOfficialTime');

		return $api->DoRequest('GeteBayOfficialTime', $xml);
	}

	/**
	* Retrieves configuration information for the store with the root category only
	*
	* @return SimpleXMLElement The configuration information
	*/
	public static function getStore()
	{
		$api = new ISC_ADMIN_EBAY_API(self::$SITE_ID);
		$xml = $api->getBaseXMLRequest('GetStore');
		$xml->addChild('LevelLimit', '1');

		return $api->DoRequest('GetStore', $xml);
	}

	/**
	* Retrieves the category structure for the store
	*
	* @param int $parentCategoryId The category ID for the topmost category to return
	* @return SimpleXMLElement The category structure
	*/
	public static function getStoreCategories($levelLimit = 1, $parentCategoryId = self::ROOT_CATEGORY_ID)
	{
		$api = new ISC_ADMIN_EBAY_API(self::$SITE_ID);
		$xml = $api->getBaseXMLRequest('GetStore');
		$xml->addChild('CategoryStructureOnly', 'true');

		$xml->addChild('LevelLimit', $levelLimit);

		if ($parentCategoryId > self::ROOT_CATEGORY_ID) {
			$xml->addChild('RootCategoryID', $parentCategoryId);
		}

		return $api->DoRequest('GetStore', $xml);
	}

	/**
	* Retrieves the latest category hierarchy for the eBay site
	*
	* @param int $levelLimit The maximum depth of the heirarchy to retrieve
	* @param int $parentCategoryId The ID of the highest-level category to return. Defaults to null to retrieve all categories.
	* @return SimpleXMLElement The category hierarchy
	*/
	public static function getCategories($levelLimit = 1, $parentCategoryId = null)
	{
		$api = new ISC_ADMIN_EBAY_API(self::$SITE_ID);
		$xml = $api->getBaseXMLRequest('GetCategories');

		$xml->addChild('LevelLimit', $levelLimit);

		if (!is_null($parentCategoryId)) {
			$xml->addChild('CategoryParent', $parentCategoryId);
		}
		$xml->addChild('DetailLevel', 'ReturnAll');

		return $api->DoRequest('GetCategories', $xml);
	}

	/**
	* Gets the current version of the category heirarchy
	*
	* @return string The category version
	*/
	public static function getCategoryVersion()
	{
		$api = new ISC_ADMIN_EBAY_API(self::$SITE_ID);
		$xml = $api->getBaseXMLRequest('GetCategories');

		$result = $api->DoRequest('GetCategories', $xml);
		return (string)$result->CategoryVersion;
	}

	/**
	* Gets the features and details of a specific category
	*
	* @param int $categoryId The ID of the category to retrieve details for
	* @return SimpleXMLElement
	*/
	public static function getCategoryFeatures($categoryId)
	{
		$api = new ISC_ADMIN_EBAY_API(self::$SITE_ID);
		$xml = $api->getBaseXMLRequest('GetCategoryFeatures');

		$xml->addChild('DetailLevel', 'ReturnAll');
		$xml->addChild('CategoryID', $categoryId);
		$xml->addChild('AllFeaturesForCategory', true);

		return $api->DoRequest('GetCategoryFeatures', $xml);
	}

	/**
	* Retrieve the mappings between categories and their corresponding characteristic sets
	*
	* @param int $categoryId The ID of the category to retrieve details for
	* @return SimpleXMLElement
	*/
	public static function getCategory2CS($categoryId)
	{
		$api = new ISC_ADMIN_EBAY_API(self::$SITE_ID);
		$xml = $api->getBaseXMLRequest('GetCategory2CS');

		$xml->addChild('DetailLevel', 'ReturnAll');
		$xml->addChild('CategoryID', $categoryId);

		return $api->DoRequest('GetCategory2CS', $xml);
	}

	/**
	* Retrieves usage information about platform notifications for a given application. You can use this notification information to troubleshoot issues with platform notifications. You can call this up to 50 times per hour for a given application.
	*
	* @param int $endTime Specifies the end date and time for which notification information will be retrieved. EndTime is optional. If no EndTime is specified, the current time (the time the call is made) is used. If no EndTime is specified or if an invalid EndTime is specified, date range errors are returned in the response. For an EndTime to be valid, it must be no more than 72 hours before the time the of the call, it cannot be before the StartTime, and it cannot be later than the time of the call. If an invalid EndTime is specified, the current time is used.
	* @param string $itemId Specifies an item ID for which detailed notification information will be retrieved. ItemID is optional. If no ItemID is specified, the response will not include any individual notification details.
	* @param int $startTime Specifies the start date and time for which notification information will be retrieved. StartTime is optional. If no StartTime is specified, the default value of 24 hours prior to the call time is used. If no StartTime is specified or if an invalid StartTime is specified, date range errors are returned in the response. For a StartTime to be valid, it must be no more than 72 hours before the time of the call, it cannot be more recent than the EndTime, and it cannot be later than the time of the call. If an invalid StartTime is specified, the default value is used.
	* @return SimpleXMLElement
	*/
	public static function getNotificationsUsage($endTime = null, $itemId = null, $startTime = null)
	{
		$api = new ISC_ADMIN_EBAY_API();

		$xml = $api->getBaseXMLRequest('GetNotificationsUsage');

		if ($endTime !== null) {
			$xml->EndTime = date('c', $endTime);
		}

		if ($itemId !== null) {
			$xml->ItemID = $itemId;
		}

		if ($startTime !== null) {
			$xml->StartTime = date('c', $startTime);
		}

		return $api->DoRequest('GetNotificationsUsage', $xml);
	}

	/**
	* Retrieves the requesting application's notification preferences. Details are only returned for events for which a preference was set at one point. For example, if you enabled notification for the EndOfAuction event and later disabled it, the GetNotificationPreferences response would cite the EndOfAuction event preference as Disabled. Otherwise, no details would be returned regarding EndOfAuction.
	*
	* @param string $preferenceLevel Specifies what type of Preference to retrieve. NotificationRoleCodeType: Application, CustomCode, Event, User, UserData
	* @return SimpleXMLElement
	*/
	public static function getNotificationPreferences($preferenceLevel = 'Application')
	{
		$api = new ISC_ADMIN_EBAY_API();

		$xml = $api->getBaseXMLRequest('GetNotificationPreferences');
		$xml->PreferenceLevel = $preferenceLevel;

		return $api->DoRequest('GetNotificationPreferences', $xml);
	}

	/**
	 * Get the URL of the store's eBay notification listener
	 * @return string the URL of the eBay notification listener
	 */
	public static function getPlatformNotificationsListenerUrl()
	{
		$customPlatformUrl = GetConfig('EbayPlatformNotificationUrl');
		if (!empty($customPlatformUrl)) {
			return $customPlatformUrl;
		}

		return GetConfig('ShopPath') . '/ebaylistener.php';
	}

	/**
	* Enable or disable all platform notifications and configures the listener URL.
	*
	* @param bool $enabled
	* @return SimpleXMLElement
	*/
	public static function setApplicationNotificationsEnabled($enabled = true)
	{
		$api = new ISC_ADMIN_EBAY_API();

		$xml = $api->getBaseXMLRequest('SetNotificationPreferences');

		$applicationDeliveryPreferences = $xml->addChild('ApplicationDeliveryPreferences');

		if ($enabled) {
			$applicationDeliveryPreferences->ApplicationEnable = 'Enable';
		} else {
			$applicationDeliveryPreferences->ApplicationEnable = 'Disable';
		}

		$applicationDeliveryPreferences->ApplicationURL = self::getPlatformNotificationsListenerUrl();
		$applicationDeliveryPreferences->DeviceType = 'Platform';

		return $api->DoRequest('SetNotificationPreferences', $xml);
	}

	/**
	* Enable or disable specific platform notifications.
	*
	* @param array $events e.g. array('ItemSold' => 'true', 'ItemUnsold' => false, ...)
	* @return SimpleXMLElement or false if no events provided
	*/
	public static function setApplicationNotificationEvents($events)
	{
		if (empty($events)) {
			return false;
		}

		$api = new ISC_ADMIN_EBAY_API();

		$xml = $api->getBaseXMLRequest('SetNotificationPreferences');

		$userDeliveryPreferenceArray = $xml->addChild('UserDeliveryPreferenceArray');

		foreach ($events as $event => $enable) {
			$notificationEnable = $userDeliveryPreferenceArray->addChild('NotificationEnable');
			$notificationEnable->EventType = $event;

			if ($enable) {
				$notificationEnable->EventEnable = 'Enable';
			} else {
				$notificationEnable->EventEnable = 'Disable';
			}
		}

		return $api->DoRequest('SetNotificationPreferences', $xml);
	}

	/**
	* Verifies an addItem request
	*
	* @param array $product The array of product data
	* @param ISC_ADMIN_EBAY_TEMPLATE $template The template to use to add the product
	* @return SimpleXMLElement The XML result of the AddItem request
	*/
	public static function verifyAddItem($product, $template)
	{
		$api = new ISC_ADMIN_EBAY_API(self::$SITE_ID);
		$xml = $api->getBaseXMLRequest('VerifyAddItem');

		self::addItemData($xml, $product, $template);

		return $api->DoRequest('VerifyAddItem', $xml);
	}

	/**
	* Lists a single item on eBay
	*
	* @param array $product The array of product data
	* @param ISC_ADMIN_EBAY_TEMPLATE $template The template to use to add the product
	* @return SimpleXMLElement The XML result of the AddItem request
	*/
	public static function addItem($product, $template)
	{
		// update this method to support selling multiple quantity auction items as in the addItems request.
		// this function isn't beind used currently however.

		$api = new ISC_ADMIN_EBAY_API(self::$SITE_ID);
		$xml = $api->getBaseXMLRequest('AddItem');

		self::addItemData($xml, $product, $template);

		return $api->DoRequest('AddItem', $xml);
	}

	/**
	* Lists a single fixed-price item on eBay. This operation is used when fixed price item specific fields are required such as variations.
	*
	* @param array $product The array of product data
	* @param ISC_ADMIN_EBAY_TEMPLATE $template The template to use to add the product
	* @return SimpleXMLElement The XML result of the AddItem request
	*/
	public static function addFixedPriceItem($product, $template)
	{
		$api = new ISC_ADMIN_EBAY_API(self::$SITE_ID);
		$xml = $api->getBaseXMLRequest('AddFixedPriceItem');

		self::addItemData($xml, $product, $template);

		return $api->DoRequest('AddFixedPriceItem', $xml);
	}

	/**
	* Lists up to 5 items on eBay
	*
	* @param array $products The array of products containing product data to list
	* @param ISC_ADMIN_EBAY_TEMPLATE $template The template to use to add the product
	* @return SimpleXMLElement The XML result of the AddItems request
	*/
	public static function addItems($products, $template)
	{
		$api = new ISC_ADMIN_EBAY_API(self::$SITE_ID);
		$xml = $api->getBaseXMLRequest('AddItems');

		foreach ($products as $product) {
			// add each item
			try {
				$itemContainer = $xml->addChild('AddItemRequestContainer');
				$itemContainer->addChild('MessageID', $product['productid']);

				self::addItemData($itemContainer, $product, $template);
			}
			catch (ISC_EBAY_LISTING_EXCEPTION $ex) {

			}
		}

		return $api->DoRequest('AddItems', $xml);
	}

	/**
	* Adds an Item element for the specified product and template into the supplied XML element
	*
	* @param SimpleXMLElement $xml The XML element to add the item to
	* @param array $product The array of product data
	* @param ISC_ADMIN_EBAY_TEMPLATE $template The template to use to add the product
	*/
	private static function addItemData(&$xml, $product, $template)
	{
		$template->setProductData($product);

		$productId = $product['productid'];

		$item = $xml->addChild('Item');

		$item->addChild('Site', $template->getSiteCode());

		// required details
		$item->addChild('Country', $template->getItemLocationCountry());
		$item->addChild('Currency', $template->getCurrencyCode());
		$item->addChild('ListingDuration', $template->getListingDuration());
		$item->addChild('ListingType', $template->getSellingMethod());

		$item->addChild('Location', $template->getItemLocationCityState());
		$item->addChild('PostalCode', $template->getItemLocationZip());

		$item->addChild('Title', isc_html_escape($product['prodname']));
		$item->addChild('Description', isc_html_escape($product['proddesc']));
		$item->addChild('SKU', isc_html_escape($product['prodcode']));

		$primaryOptions = $template->getPrimaryCategoryOptions();

		// are item specifics supported by the primary category?
		if (!empty($primaryOptions['item_specifics_supported'])) {
			$itemSpecifics = null;

			// brand name
			if (!empty($product['brandname'])) {
				$itemSpecifics = $item->addChild('ItemSpecifics');

				$specific = $itemSpecifics->addChild('NameValueList');
				$specific->addChild('Name', GetLang('Brand'));
				$specific->addChild('Value', $product['brandname']);
			}

			// do we have custom fields for the product?
			if (!empty($product['custom_fields'])) {
				if ($itemSpecifics == null) {
					$itemSpecifics = $item->addChild('ItemSpecifics');
				}

				foreach ($product['custom_fields'] as $customField) {
					$specific = $itemSpecifics->addChild('NameValueList');
					$specific->addChild('Name', $customField['fieldname']);
					$specific->addChild('Value', $customField['fieldvalue']);
				}
			}
		}

		// does this product have a upc? it can be used to pull in product information
		if (!empty($primaryOptions['catalog_enabled']) && !empty($product['upc'])) {
			$productListingDetails = $item->addChild('ProductListingDetails');
			$productListingDetails->addChild('UPC', $product['upc']);
		}

		// does the product have a variation?
		if ($product['prodvariationid']) {
			$variationId = $product['prodvariationid'];

			$variations = $item->addChild('Variations');
			$variationSpecificsSet = $variations->addChild('VariationSpecificsSet');

			$lastOptionName = '';
			$variationOptions = array();

			// add the variation options
			$res = Store_Variations::getOptions($variationId);
			while ($optionRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
				if ($optionRow['voname'] != $lastOptionName) {
					$lastOptionName = $optionRow['voname'];

					$nameValueList = $variationSpecificsSet->addChild('NameValueList');
					$nameValueList->addChild('Name', $lastOptionName);
				}

				$nameValueList->addChild('Value', $optionRow['vovalue']);

				$variationOptions[$optionRow['voptionid']] = array($optionRow['voname'], $optionRow['vovalue']);
			}

			// add the combinations
			$res = Store_Variations::getCombinations($productId, $variationId);
			while ($comboRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
				$variation = $variations->addChild('Variation');

				if ($comboRow['vcsku']) {
					$variation->addChild('SKU', $comboRow['vcsku']);
				}

				$variation->addChild('Quantity', $template->getQuantityToSell());

				$startPrice = $template->getStartPrice(false);
				$comboStartPrice = CalcProductVariationPrice($startPrice, $comboRow['vcpricediff'], $comboRow['vcprice']);
				$comboStartPrice = ConvertPriceToCurrency($comboStartPrice, $template->getCurrency());

				$variation->addChild('StartPrice', $comboStartPrice);

				// add the options for this combination
				$variationSpecifics = $variation->addChild('VariationSpecifics');

				$options = explode(',', $comboRow['vcoptionids']);
				foreach ($options as $optionId) {
					list($optionName, $optionValue) = $variationOptions[$optionId];

					$nameValueList = $variationSpecifics->addChild('NameValueList');
					$nameValueList->addChild('Name', $optionName);
					$nameValueList->addChild('Value', $optionValue);
				}
			}

			// add images
			$optionPictures = Store_Variations::getCombinationImagesForFirstOption($productId, $variationId);
			if (!empty($optionPictures)) {
				$pictures = $variations->addChild('Pictures');

				// we'll be adding images for the first option set
				list($optionName) = current($variationOptions);

				$pictures->addChild('VariationSpecificName', $optionName);

				foreach ($optionPictures as $optionName => $imageUrl) {
					$variationSpecificPictureSet = $pictures->addChild('VariationSpecificPictureSet');
					$variationSpecificPictureSet->addChild('VariationSpecificValue', $optionName);
					$variationSpecificPictureSet->addChild('PictureURL', $imageUrl);
				}
			}
		}

		// add quantity
		if (!$product['prodvariationid']) {
			$item->addChild('Quantity', $template->getTrueQuantityToSell());
		}

		$item->addChild('PrivateListing', (int)$template->isPrivateListing());

		// schedule date
		if ($template->getScheduleDate()) {
			$item->addChild('ScheduleTime', $template->getScheduleDate());
		}

		// condition
		if ($template->getItemCondition()) {
			$item->addChild('ConditionID', $template->getItemCondition());
		}

		// payment details
		foreach ($template->getPaymentMethods() as $paymentMethod) {
			$item->addChild('PaymentMethods', $paymentMethod);
		}

		if (in_array('PayPal', $template->getPaymentMethods())) {
			$item->addChild('PayPalEmailAddress', $template->getPayPalEmailAddress());
		}

		// add categories
		$item->addChild('PrimaryCategory')->addChild('CategoryID', $template->getPrimaryCategoryId());
		if ($template->getSecondaryCategoryId()) {
			$item->addChild('SecondaryCategory')->addChild('CategoryID', $template->getSecondaryCategoryId());
		}

		$item->addChild('CategoryMappingAllowed', (int)$template->getAllowCategoryMapping());

		// add store categories
		if ($template->getPrimaryStoreCategoryId()) {
			$storeFront = $item->addChild('Storefront');
			$storeFront->addChild('StoreCategoryID', $template->getPrimaryStoreCategoryId());

			if ($template->getSecondaryStoreCategoryId()) {
				$storeFront->addChild('StoreCategory2ID', $template->getSecondaryStoreCategoryId());
			}
		}

		// prices
		if ($template->getSellingMethod() == ISC_ADMIN_EBAY::CHINESE_AUCTION_LISTING) {
			$item->addChild('StartPrice', $template->getStartPrice());

			if ($template->getReservePrice() !== false) {
				$item->addChild('ReservePrice', $template->getReservePrice());
			}

			if ($template->getBuyItNowPrice() !== false) {
				$item->addChild('BuyItNowPrice', $template->getBuyItNowPrice());
			}
		}
		elseif (!$product['prodvariationid']) {
			$item->addChild('StartPrice', $template->getStartPrice());
		}

		// add return policy info
		$policy = $item->addChild('ReturnPolicy');
		$policy->addChild('ReturnsAcceptedOption', $template->getReturnsAcceptedOption());
		if ($template->getReturnsAccepted()) {
			if ($template->getAdditionalPolicyInfo()) {
				$policy->addChild('Description', isc_html_escape($template->getAdditionalPolicyInfo()));
			}
			if ($template->getReturnOfferedAs()) {
				$policy->addChild('RefundOption', $template->getReturnOfferedAs());
			}
			if ($template->getReturnsPeriod()) {
				$policy->addChild('ReturnsWithinOption', $template->getReturnsPeriod());
			}
			if ($template->getReturnCostPaidBy()) {
				$policy->addChild('ShippingCostPaidByOption', $template->getReturnCostPaidBy());
			}
		}

		// counter
		$item->addChild('HitCounter', $template->getCounterStyle());

		// gallery option
		$pictureDetails = $item->addChild('PictureDetails');
		$pictureDetails->addChild('GalleryType', $template->getGalleryType());
		if ($template->getGalleryType() == 'Featured') {
			$pictureDetails->addChild('GalleryDuration', $template->getFeaturedGalleryDuration());
		}

		if ($template->getItemPhoto()) {
			if ($template->getGalleryType() != 'None') {
				$pictureDetails->addChild('GalleryURL', $template->getItemPhoto());
			}

			$pictureDetails->addChild('PictureURL', $template->getItemPhoto());
		}

		// listing features
		foreach ($template->getListingFeatures() as $feature) {
			$item->addChild('ListingEnhancement', $feature);
		}

		// domestic shipping
		if ($template->getUseDomesticShipping()) {
			// add shipping details
			$shippingDetails = $item->addChild('ShippingDetails');
			// the actual shipping type - Flat or Calculated - where's our freight option gone?
			$shippingDetails->addChild('ShippingType', $template->getShippingType());

			//$insuranceDetails = $shippingDetails->addChild('InsuranceDetails');
			$shippingDetails->addChild('InsuranceOption', 'NotOffered');

			$calculatedRate = null;

			// add checkout instructions
			if ($template->getCheckoutInstructions()) {
				$shippingDetails->addChild('PaymentInstructions', $template->getCheckoutInstructions());
			}

			// add sales tax - US only
			if ($template->getUseSalesTax()) {
				$salesTax = $shippingDetails->addChild('SalesTax');
				$salesTax->addChild('SalesTaxState', $template->getSalesTaxState());
				$salesTax->addChild('SalesTaxPercent', $template->getSalesTaxPercent());
				$salesTax->addChild('ShippingIncludedInTax', $template->getShippingIncludedInTax());
			}


			$domesticServices = $template->getDomesticShippingServices();
			$domesticSettings = $template->getDomesticShippingSettings();

			if (empty($domesticSettings)) {
				throw new Exception('Missing domestic shipping settings');
			}

			// add a pickup service - can't get this to work gives error:  ShippingService is required if Insurance, SalesTax, or AutoPay is specified. (10019)
			if ($domesticSettings['offer_pickup']) {
				$domesticServices['Pickup'] = array(
					'additional_cost'	=> 0,
					'cost'				=> 0 //(double)$domesticServices['pickup_cost'] // where has this option gone?
				);
			}


			if (empty($domesticServices)) {
				throw new Exception('Missing domestic shipping services');
			}

			$domesticFeeShipping = (bool)$domesticSettings['is_free_shipping'];

			// add our domestic services
			self::addShippingServices($shippingDetails, $domesticSettings, $domesticServices, 'ShippingServiceOptions', $domesticFeeShipping);

			// buy it fast enabled?  domestic only
			if ($domesticSettings['get_it_fast']) {
				$item->addChild('GetItFast', true);
				$item->addChild('DispatchTimeMax', 1); // required for getitfast
			}
			else {
				// add handling time
				$item->addChild('DispatchTimeMax', $template->getHandlingTime());
			}

			if ($domesticSettings['cost_type'] == 'Calculated') {
				if ($calculatedRate == null) {
					$calculatedRate = self::addCalculatedDetails($shippingDetails, $template);
				}

				// handling cost
				if ($domesticSettings['handling_cost']) {
					$calculatedRate->addChild('PackagingHandlingCosts', $domesticSettings['handling_cost']);
				}
			}

			// international shipping - we can't supply international services if we don't specify domestic
			if ($template->getUseInternationalShipping()) {
				$internationalSettings = $template->getInternationalShippingSettings();
				$internationalServices = $template->getInternationalShippingServices();

				if (empty($internationalSettings)) {
					throw new Exception('Missing international shipping settings');
				}

				if (empty($internationalServices)) {
					throw new Exception('Missing international shipping services');
				}

				// add our international services
				self::addShippingServices($shippingDetails, $internationalSettings, $internationalServices, 'InternationalShippingServiceOption', false, true);


				if ($internationalSettings['cost_type'] == 'Calculated') {
					if ($calculatedRate == null) {
						$calculatedRate = self::addCalculatedDetails($shippingDetails, $template);
					}

					// handling cost
					if ($internationalSettings['handling_cost']) {
						$calculatedRate->addChild('InternationalPackagingHandlingCosts', $internationalSettings['handling_cost']);
					}
				}
			}
		}
		else {
			// domestic pickup only
			$item->addChild('ShipToLocations', 'None');
		}
	}

	/**
	* Processes and adds shipping services to the XML element
	*
	* @param SimpleXMLElement $shippingDetails The referenced shipping details element to add shipping services into
	* @param array $shippingServices The shipping services to add
	* @param array $shippingSettings Associated shipping settings for the services
	* @param string $optionName The name of the XML element to add for each service
	* @param bool $freeShipping Should be free shipping be applied to the first service?
	* @param bool $addShipTo Adds the ship to location. Valid only for international.
	*/
	private static function addShippingServices(&$shippingDetails, $shippingSettings, $shippingServices, $optionName, $freeShipping = false, $addShipTo = false)
	{
		$serviceCount = 0;

		foreach ($shippingServices as $serviceType => $service) {
			$shippingServiceOption = $shippingDetails->addChild($optionName);

			// free shipping can only be applied to the first service

			$shippingServiceOption->addChild('ShippingService', $serviceType);
			$shippingServiceOption->addChild('ShippingServicePriority', $serviceCount + 1);

			if ($freeShipping && $serviceCount == 0){
				$shippingServiceOption->addChild('FreeShipping', true);
			}
			elseif ($shippingSettings['cost_type'] == 'Flat') {
				$shippingServiceOption->addChild('ShippingServiceCost', (double)$service['cost']);
				$shippingServiceOption->addChild('ShippingServiceAdditionalCost', (double)$service['additional_cost']);
			}

			if ($addShipTo) {
				if (empty($service['ship_to_locations'])) {
					throw new Exception("Missing international ship to locations for service '" . $serviceType . "'.");
				}
				foreach ($service['ship_to_locations'] as $locationCode) {
					$shippingServiceOption->addChild('ShipToLocation', $locationCode);
				}
			}

			$serviceCount++;
		}
	}

	/**
	* Adds calculated rate details to the shipping details
	*
	* @param SimpleXMLElement $shippingDetails
	* @param ISC_ADMIN_EBAY_TEMPLATE $template
	* @return SimpleXMLElement
	*/
	private static function addCalculatedDetails(&$shippingDetails, $template)
	{
		$calculatedRate = $shippingDetails->addChild('CalculatedShippingRate');

		$calculatedRate->addChild('MeasurementUnit', 'English');
		$calculatedRate->addChild('OriginatingPostalCode', $template->getItemLocationZip());

		// add dimensions - whole inches only
		$depth = round(ConvertLength($template->getItemDepth(), 'in'));
		$length = round(ConvertLength($template->getItemLength(), 'in'));
		$width = round(ConvertLength($template->getItemWidth(), 'in'));

		$depthXML = $calculatedRate->addChild('PackageDepth', $depth);
		$depthXML->addAttribute('measurementSystem', 'English');
		$depthXML->addAttribute('unit', 'in');

		$lengthXML = $calculatedRate->addChild('PackageLength', $length);
		$lengthXML->addAttribute('measurementSystem', 'English');
		$lengthXML->addAttribute('unit', 'in');

		$widthXML = $calculatedRate->addChild('PackageWidth', $width);
		$widthXML->addAttribute('measurementSystem', 'English');
		$widthXML->addAttribute('unit', 'in');

		//add weight in pounds and ounces
		$weightTotal = ConvertWeight($template->getItemWeight(), 'lbs');
		$weightMajor = floor($weightTotal);
		$weightMinor = ConvertWeight($weightTotal - $weightMajor, 'ounces', 'lbs');
		if ($weightMinor < 1) {
			$weightMinor = 1;
		}

		$calculatedRate->addChild('WeightMajor', $weightMajor);
		$calculatedRate->addChild('WeightMinor', $weightMinor);

		return $calculatedRate;
	}

	/**
	* Ends up to 10 items on eBay
	*
	* @param array $item The array of item contains item id and reason of ending
	* @return SimpleXMLElement The XML result of the AddItems request
	*/
	public static function endItems($items)
	{
		$api = new ISC_ADMIN_EBAY_API();
		$xml = $api->getBaseXMLRequest('EndItems');

		foreach ($items as $item) {
			$itemContainer = $xml->addChild('EndItemRequestContainer');
			$itemContainer->addChild('MessageID', $item['Id']);
			if (!empty ($item['EndingReason'])) {
				$itemContainer->addChild('EndingReason', $item['EndingReason']);
			}
			$itemContainer->addChild('ItemID', $item['Id']);
		}
		return $api->DoRequest('EndItems', $xml);
	}

	/**
	* Ends a single item on eBay
	*
	* @param int $itemId The item id
	* @param string $itemId The reason of ending the listing
	* @return SimpleXMLElement The XML result of the AddItems request
	*/
	public static function endItem($itemId, $endingReason)
	{
		$api = new ISC_ADMIN_EBAY_API();
		$xml = $api->getBaseXMLRequest('EndItem');

		$xml->addChild('MessageID', $itemId);
		$xml->addChild('EndingReason', $endingReason);
		$xml->addChild('ItemID', $itemId);

		return $api->DoRequest('EndItem', $xml);
	}

	/**
	* Get the order transaction for item sold
	*
	* @param int $orderId The ID of the order
	* @return SimpleXMLElement The XML result of the GetOrderTransactions request
	*/
	public static function getOrderTransactions($orderId)
	{
		$api = new ISC_ADMIN_EBAY_API();
		$xml = $api->getBaseXMLRequest('GetOrderTransactions');

		$orderIDArray = $xml->addChild('OrderIDArray');
		$orderIDArray->addChild('OrderID', $orderId);

		return $api->DoRequest('GetOrderTransactions', $xml);
	}
}
