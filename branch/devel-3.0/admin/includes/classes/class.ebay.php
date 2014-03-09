<?php
if (!defined('ISC_BASE_PATH')) {
	die();
}

/**
 * Interspire Shopping Cart Ebay Integration.
 */
class ISC_ADMIN_EBAY extends ISC_ADMIN_BASE {

	/**
	 * @var constant Default eBay site id
	 */
	const DEFAULT_SITE_ID = 0;

	/**
	 * @var constant Default listing duration
	 */
	const DEFAULT_LISTING_DURATION = 'Days_7';

	/**
	 * @var constant Chinese Listing Type
	 */
	const CHINESE_AUCTION_LISTING = 'Chinese';

	/**
	 * @var constant Fixed Price Listing Type
	 */
	const FIXED_PRICE_LISTING = 'FixedPriceItem';

	/**
	 * @var constant Root category Id of eBay listing
	 */
	const ROOT_CATEGORY_ID = -1;

	/**
	 * @var constant Reserve price type of eBay listing
	 */
	const RESERVE_PRICE_TYPE = 'Reserve';

	/**
	 * @var constant Starting price type of eBay listing
	 */
	const STARTING_PRICE_TYPE = 'Starting';

	/**
	 * @var constant Buy It Now price type of eBay listing
	 */
	const BUY_PRICE_TYPE = 'Buy';

	/**
	* @var constant The amount of seconds eBay details cache is valid for
	*/
	CONST CACHE_VALID_FOR = 86400;

	/**
	 * @var string The path of where the ebay request cache is stored
	 */
	private $cacheBaseDir = '';

	/**
	 * @var object An instance of FileClass for file handling
	 */
	private $fileHandler = null;

	/**
	 * @var array An array that contains all the operators for assigning listing prices
	 */
	private $priceOperators = array ('+'=>'Plus', '-'=>'Minus');

	/**
	 * @var array An array that contains all the options for assigning listing prices
	 */
	private $priceOptions = array ('percent'=>'Percent');

	/**
	 * @var array An array that contains all the options for listing statuses
	 */
	private $listingStatuses = array (
		'scheduled'=>'Scheduled',
		'active_selling'=>'Active Selling',
		'sold'=>'Sold',
		'unsold'=>'Unsold',
		'won'=>'Won',
	);

	/**
	* Supported eBay sites
	*
	* @var mixed
	*/
	private $availableSites = array(
		'EbayAuSite', 'EbayCaSite', 'EbayUkSite', 'EbayUsSite'
	);

	/**
	 * The constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		require_once(ISC_BASE_PATH.'/lib/class.file.php');
		$this->cacheBaseDir = ISC_BASE_PATH.'/cache/ebaydata';
		$this->fileHandler = new FileClass();
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('ebay');
	}

	/**
	 * Handle the incoming action we want to perform.
	 *
	 * @param string The name of the action to perform.
	 */
	public function HandleToDo($do)
	{
		// All the actions that need to pass the cache checking before the user can continue
		$cacheCheckToDo = array('viewebay', 'addebaytemplate', 'editebaytemplate');

		if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Ebay_Selling) || !gzte11(ISC_LARGEPRINT)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
		}

		// Set up breadcrumb entries
		$GLOBALS['BreadcrumEntries'] = array(
			GetLang('Home') => 'index.php',
			GetLang('Ebay') => 'index.php?ToDo=viewEbay'
		);

		// In order to use eBay feature, the user need to use the supported currencies
		if (!count($this->getSupportedSites())) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('EbayCountryNotSupported'), MSG_SUCCESS);
			exit;
		} else if (!self::checkEbayConfig() && isc_strtolower($do) != 'saveebay') {
			// redirect the user to input the eBay keys before they can continue
			$this->ManageEbay(false);
			exit;
		// if we need to update the cache for this action
		} else if (in_array(isc_strtolower($do), $cacheCheckToDo) && !$this->cacheUpToDate()) {
			$this->preCacheUpdate();
		// if there are no listing or template added to eBay
		} else if (!isset ($_REQUEST['currentTab'])) {
			$start = 0;
			$queries = array();
			$query = '';
			$sortField = '';
			$sortOrder = '';
			$numListing = 0;
			$numTemplate = 0;
			$listingResult = $this->_GetEbayListingList($queries, $start, $sortField, $sortOrder, $numListing);
			if ($numListing > 0) {
				$_REQUEST['currentTab'] = 0;
			} else {
				$templateResult = $this->_GetEbayTemplateList($query, $start, $sortOrder, $sortField, $numTemplate);
				if ($numTemplate == 0) {
					$_REQUEST['currentTab'] = 1;
				} else {
					$_REQUEST['currentTab'] = 0;
				}
			}
		}

		switch(isc_strtolower($do)) {
			case 'saveebay':
				$this->SaveEbay();
				break;
			case 'ajaxebayupdate':
				$this->updateCache();
				break;
			case 'addebaytemplate':
				echo $this->addEbayTemplate();
				break;
			case 'editebaytemplate':
				$this->editEbayTemplate();
				break;
			case 'saveebaytemplate':
				$this->SaveEbayTemplate();
				break;
			case 'editebaytemplatestatus':
				$this->editEbayTemplateStatus();
				break;
			case 'deleteebaytemplate':
				$this->deleteEbayTemplate();
				break;
			case 'deletelocalebaylisting':
				$this->deleteLocalEbayListing();
				break;
			default:
				$this->ManageEbay();
		}
	}

	/**
	 * To delete ebay template
	 */
	public function deleteEbayTemplate()
	{
		$filteredIdx = array();

		if (isset($_POST['templates']) && is_array($_POST['templates'])) {
			$filteredIdx = array_filter($_POST['templates'], "isId");
		}

		if (is_array($filteredIdx) && !empty($filteredIdx)) {
			$query = "DELETE [|PREFIX|]ebay_listing_prices, [|PREFIX|]ebay_listing_template, [|PREFIX|]ebay_shipping, [|PREFIX|]ebay_shipping_serv "
			. "FROM [|PREFIX|]ebay_listing_template "
			. "LEFT JOIN [|PREFIX|]ebay_listing_prices ON ([|PREFIX|]ebay_listing_prices.ebay_listing_template_id = [|PREFIX|]ebay_listing_template.id) "
			. "LEFT JOIN [|PREFIX|]ebay_shipping ON ([|PREFIX|]ebay_shipping.ebay_listing_template_id = [|PREFIX|]ebay_listing_template.id) "
			. "LEFT JOIN [|PREFIX|]ebay_shipping_serv ON ([|PREFIX|]ebay_shipping_serv.ebay_shipping_id = [|PREFIX|]ebay_shipping.id) "
			. "WHERE [|PREFIX|]ebay_listing_template.id IN (".implode(',', $filteredIdx).") "
			;
			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			if (!$result) {
				FlashMessage(GetLang('UnknownErrorDeletion'), MSG_ERROR, 'index.php?ToDo=viewEbay&currentTab=1', 'EbayListingTemplate');
			} else {
				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($_POST['templates']));
				FlashMessage(GetLang('TemplateDeletedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewEbay&currentTab=1', 'EbayListingTemplate');
			}
		} else {
			FlashMessage(GetLang('ChooseTemplate'), MSG_ERROR, 'index.php?ToDo=viewEbay&currentTab=1', 'EbayListingTemplate');
		}
	}

	/**
	 * To save the defined eBay listing template
	 *
	 * @param string $error Referenced variable to store an error message in if saving is unsuccessfull
	 * @param int $templateId The optional template to update instead. If 0 a new template is created.
	 * @return mixed Returns the template Id if saved successfully, FALSE otherwise
	 */
	public function SaveEbayTemplate(&$error, $templateId = 0)
	{
		// site we're listing on
		$siteId = (int)$_POST['siteId'];

		// set up basic template variables
		$templateName = $_POST['templateName'];
		$templateIsDefault = isset($_POST['templateAsDefault']);
		$privateListing = isset($_POST['privateListing']);

		// set up category variables
		$categoryOptions = $_POST['primaryCategoryOptions'];
		$secondaryCategoryOptions = array();
		$primaryCategoryId = $categoryOptions['category_id'];
		$secondaryCategoryId = 0;
		$secondaryCategoryName = '';
		$primaryStoreCategoryId = 0;
		$primaryStoreCategoryName = '';
		$secondaryStoreCategoryId = 0;
		$secondaryStoreCategoryName = '';

		if (!empty($_POST['secondaryCategoryOptions'])) {
			$secondaryCategoryId = $_POST['secondaryCategoryOptions']['category_id'];
			$secondaryCategoryName = $_POST['secondaryCategoryOptions']['name'];
			$secondaryCategoryOptions = $_POST['secondaryCategoryOptions'];
		}

		if (!empty($_POST['primaryStoreCategoryOptions'])) {
			$primaryStoreCategoryId = $_POST['primaryStoreCategoryOptions']['category_id'];
			$primaryStoreCategoryName = $_POST['primaryStoreCategoryOptions']['name'];
		}

		if (!empty($_POST['secondaryStoreCategoryOptions'])) {
			$secondaryStoreCategoryId = $_POST['secondaryStoreCategoryOptions']['category_id'];
			$secondaryStoreCategoryName = $_POST['secondaryStoreCategoryOptions']['name'];
		}

		// item details
		$quantityToSell = 1;
		if ($_POST['quantityType'] == 'more') {
			$quantityToSell = (int)$_POST['quantityMore'];
		}
		if ($quantityToSell < 1) {
			$quantityToSell = 1;
		}

		$useItemPhoto = isset($_POST['useItemPhoto']);

		$lotSize = 0;
		if (isset($_POST['lotSize'])) {
			$lotSize = (int)$_POST['lotSize'];
		}
		if ($lotSize < 0) {
			$lotSize = 0;
		}

		$useProductImage = isset($_POST['useItemPhoto']);

		// item location
		$locationCountry = (int)$_POST['locationCountry'];
		$locationCountryCode = GetCountryISO2ById($locationCountry);

		$locationZip = $_POST['locationZip'];
		$locationCityState = $_POST['locationCityState'];

		$prices = array();

		// selling method
		$sellingMethod = $_POST['sellingMethod'];

		// Online Auction
		if ($sellingMethod == 'Chinese') {
			// reserve price
			$useReservePrice = isset($_POST['useReservePrice']);
			if ($useReservePrice) {
				$reservePriceOption = $_POST['reservePriceOption'];

				$price = array(
					'price_type'	=> self::RESERVE_PRICE_TYPE,
					'selected_type' => $reservePriceOption
				);

				if ($reservePriceOption == 'PriceExtra') {
					$price['calculate_operator'] = $_POST['reservePricePlusOperator'];
					$price['calculate_option'] = $_POST['reservePricePlusType'];
					if ($price['calculate_option'] == 'amount') {
						$price['calculate_price'] = DefaultPriceFormat($_POST['reservePricePlusValue']);
					}
					else {
						$price['calculate_price'] = (double)$_POST['reservePricePlusValue'];
					}
				}
				elseif ($reservePriceOption == 'CustomPrice') {
					$price['price'] = DefaultPriceFormat($_POST['reservePriceCustomValue']);
				}

				$prices[] = $price;
			}

			// start price
			$startPriceOption = $_POST['startPriceOption'];

			$price = array(
				'price_type'	=> self::STARTING_PRICE_TYPE,
				'selected_type' => $startPriceOption
			);

			if ($startPriceOption == 'PriceExtra') {
				$price['calculate_operator'] = $_POST['startPricePlusOperator'];
				$price['calculate_option'] = $_POST['startPricePlusType'];
				if ($price['calculate_option'] == 'amount') {
					$price['calculate_price'] = DefaultPriceFormat($_POST['startPricePlusValue']);
				}
				else {
					$price['calculate_price'] = (double)$_POST['startPricePlusValue'];
				}
			}
			elseif ($startPriceOption == 'CustomPrice') {
				$price['price'] = DefaultPriceFormat($_POST['startPriceCustomValue']);
			}

			$prices[] = $price;

			// buy it now
			if (isset($_POST['useBuyItNowPrice'])) {
				$buyItNowPriceOption = $_POST['buyItNowPriceOption'];

				$price = array(
					'price_type'	=> self::BUY_PRICE_TYPE,
					'selected_type' => $buyItNowPriceOption
				);

				if ($buyItNowPriceOption == 'PriceExtra') {
					$price['calculate_operator'] = $_POST['buyItNowPricePlusOperator'];
					$price['calculate_option'] = $_POST['buyItNowPricePlusType'];
					if ($price['calculate_option'] == 'amount') {
						$price['calculate_price'] = DefaultPriceFormat($_POST['buyItNowPricePlusValue']);
					}
					else {
						$price['calculate_price'] = (double)$_POST['buyItNowPricePlusValue'];
					}
				}
				elseif ($buyItNowPriceOption == 'CustomPrice') {
					$price['price'] = DefaultPriceFormat($_POST['buyItNowPriceCustomValue']);
				}

				$prices[] = $price;
			}

			// auction duration
			$listingDuration = $_POST['auctionDuration'];
		}
		// Fixed item listing
		else {
			$fixedBuyItNowPriceOption = $_POST['fixedBuyItNowPriceOption'];

			$price = array(
				'price_type'	=> self::STARTING_PRICE_TYPE,
				'selected_type' => $fixedBuyItNowPriceOption
			);

			if ($fixedBuyItNowPriceOption == 'PriceExtra') {
				$price['calculate_operator'] = $_POST['fixedBuyItNowPricePlusOperator'];
				$price['calculate_option'] = $_POST['fixedBuyItNowPricePlusType'];
				if ($price['calculate_option'] == 'amount') {
					$price['calculate_price'] = DefaultPriceFormat($_POST['fixedBuyItNowPricePlusValue']);
				}
				else {
					$price['calculate_price'] = (double)$_POST['fixedBuyItNowPricePlusValue'];
				}
			}
			elseif ($fixedBuyItNowPriceOption == 'CustomPrice') {
				$price['price'] = DefaultPriceFormat($_POST['fixedBuyItNowPriceCustomValue']);
			}

			$prices[] = $price;

			// auction duration
			$listingDuration = $_POST['fixedDuration'];
		}


		// payment options
		$paymentMethods = array();
		if (isset($_POST['paymentMethods'])) {
			foreach ($_POST['paymentMethods'] as $paymentMethod) {
				$paymentMethods[] = $paymentMethod;
			}
		}
		// manually add paypal in if required since it wont be posted (disabled form field)
		if ($categoryOptions['paypal_required']) {
			$paymentMethods[] = 'PayPal';
		}

		$paypalEmail = $_POST['paypalEmailAddress'];

		// shipping options
		$useInternationalShipping = isset($_POST['yesInternationalShipping']);
		$useDomesticShipping = false;
		if ($_POST['domesticShipping'] == 'specify') {
			$useDomesticShipping = true;
		}

		$shippingAreas = array(
			'domestic'		=> $useDomesticShipping,
			'international'	=> $useInternationalShipping
		);

		$dispatchTimeMax = 0;
		if (isset($_POST['handlingTime'])) {
			$dispatchTimeMax = (int)$_POST['handlingTime'];
		}

		// sales tax
		$useSalesTax = false;
		if (!empty ($_POST['salesTax']) && $_POST['salesTax'] == '1') {
			$useSalesTax = (bool)$_POST['salesTax'];
		}
		$salesTaxState = '';
		$salesTaxPercentage = 0;
		$salesTaxIncludesShippingCost = false;

		if ($useSalesTax) {
			$salesTaxState = $_POST['salesTaxState'];
			$salesTaxPercentage = DefaultPriceFormat($_POST['salesTaxPercentage'], false);
			$salesTaxIncludesShippingCost = isset($_POST['salesTaxIncludeShippingCost']);
		}


		// other options
		$checkoutInstructions = $_POST['checkoutInstructions'];

		// returns
		$acceptReturns = isset($_POST['acceptReturns']);
		$returnOfferAs =  '';
		if (isset($_POST['refundOption'])) {
			$returnOfferAs= $_POST['refundOption'];
		}
		$returnsPeriod = '';
		if (isset($_POST['returnsWithin'])) {
			$returnsPeriod = $_POST['returnsWithin'];
		}
		$returnCostPaidBy = '';
		if (isset($_POST['returnCostPaidBy'])) {
			$returnCostPaidBy = $_POST['returnCostPaidBy'];
		}
		$additionalPolicyInfo = '';
		if (isset($_POST['additionalPolicyInfo'])) {
			$additionalPolicyInfo = $_POST['additionalPolicyInfo'];
		}

		// upgrade options
		$counterStyle = $_POST['hitCounter'];

		$galleryOption = $_POST['galleryOption'];
		$galleryDuration = '';
		if ($galleryOption == 'Featured') {
			$galleryDuration = $_POST['galleryDuration'];
		}

		$listingFeatures = array();
		if (isset($_POST['listingFeature'])) {
			$listingFeatures = $_POST['listingFeature'];
		}

		$acceptBestOffer = false; // where did this option go?

		// our template data to insert
		$newTemplate = array(
			'name'					=> $templateName,
			'enabled'				=> 1,
			'user_id'				=> $this->auth->GetUserId(),
			'site_id'				=> $siteId,
			'is_default'			=> $templateIsDefault,
			'is_private'			=> $privateListing,

			'quantities'			=> $quantityToSell,
			'use_prod_image'		=> $useItemPhoto,
			'lot_size'				=> $lotSize,

			'listing_type'			=> $sellingMethod,
			'listing_duration'		=> $listingDuration,

			'primary_category_options' 	=> serialize($categoryOptions),
			'secondary_category_options' 	=> serialize($secondaryCategoryOptions),
			'primary_category_id'		=> $primaryCategoryId,
			'secondary_category_id'		=> $secondaryCategoryId,
			'secondary_category_name'	=> $secondaryCategoryName,
			'store_category1_id'		=> $primaryStoreCategoryId,
			'store_category1_name'		=> $primaryStoreCategoryName,
			'store_category2_id'		=> $secondaryStoreCategoryId,
			'store_category2_name'		=> $secondaryStoreCategoryName,

			'accept_best_offer'		=> $acceptBestOffer,

			'payment_method'		=> serialize($paymentMethods),
			'paypal_email'			=> $paypalEmail,
			'payment_instruction'	=> $checkoutInstructions,

			'item_country'			=> $locationCountryCode,
			'item_zip'				=> $locationZip,
			'item_city'				=> $locationCityState,

			'accept_return'			=> $acceptReturns,
			'return_offer_as'		=> $returnOfferAs,
			'return_period'			=> $returnsPeriod,
			'return_cost_by'		=> $returnCostPaidBy,
			'return_policy_description' 	=> $additionalPolicyInfo,

			'use_domestic_shipping'			=> $useDomesticShipping,
			'use_international_shipping'	=> $useInternationalShipping,
			'handling_time'			=> $dispatchTimeMax,

			'use_salestax'			=> $useSalesTax,
			'sales_tax_states'		=> $salesTaxState,
			'salestax_percent'		=> $salesTaxPercentage,
			'salestax_inc_shipping'	=> $salesTaxIncludesShippingCost,

			'counter_style'			=> $counterStyle,
			'gallery_opt'			=> $galleryOption,
			'featured_gallery_duration' => $galleryDuration,
			'listing_opt'			=> serialize($listingFeatures),

			'date_added'			=> time()
		);

		if (!$this->db->StartTransaction()) {
			$error = $this->db->Error();
			return false;
		}

		if ($templateId) {
			if (!$this->db->UpdateQuery('ebay_listing_template', $newTemplate, 'id = ' . $templateId)) {
				$this->db->RollbackTransaction();
				$error = $this->db->Error();
				return false;
			}

			// delete old prices and shipping settings
			$this->db->DeleteQuery('ebay_listing_prices', 'WHERE ebay_listing_template_id = ' . $templateId);
			$query = 'DELETE es.*, ess.* FROM [|PREFIX|]ebay_shipping es, [|PREFIX|]ebay_shipping_serv ess WHERE ess.ebay_shipping_id = es.id AND es.ebay_listing_template_id = ' . $templateId;
			$this->db->Query($query);
		}
		else {
			// create new template
			$templateId = $this->db->InsertQuery('ebay_listing_template', $newTemplate);
		}

		if (!$templateId) {
			$this->db->RollbackTransaction();
			$error = $this->db->Error();
			return false;
		}

		// add the prices
		foreach ($prices as $price) {
			$price['ebay_listing_template_id'] = $templateId;

			if (!$this->db->InsertQuery('ebay_listing_prices', $price)) {
				$this->db->RollbackTransaction();
				$error = $this->db->Error();
				return false;
			}
		}

		// Saving Shipping Details
		foreach ($shippingAreas as $shippingArea => $enable) {
			// Skip if the shipping area isn't enabled
			if (!$enable) {
				continue;
			}

			// Skip the Freight shipping as there is nothing to be saved
			$shippingType = $_POST[$shippingArea . 'ShippingType'];
			$offerPickup = 0;

			$getItFast = 0;
			if (isset($_POST[$shippingArea . 'YesGetItFast'])) {
				$getItFast = 1;
			}

			$freeShipping = 0;

			$handlingCost = 0;
			if (isset($_POST[$shippingArea . 'HandlingCost'])) {
				$handlingCost = DefaultPriceFormat($_POST[$shippingArea . 'HandlingCost']);
			}

			$shippingPackage = '';
			$services = array();

			switch ($shippingType) {
				case 'Flat':
					// local pickup only available for domestic
					if (!empty($_POST[$shippingArea . 'LocalPickup'])) {
						$offerPickup = 1;
					}
					if (!empty($_POST[$shippingArea . 'YesFreeFlatShipping'])) {
						$freeShipping = 1;
					}


					foreach ($_POST[$shippingArea . 'ShippingServFlat'] as $index => $shippingService) {
						if (!$shippingService['Type']) {
							continue;
						}

						$shipToLocations = array();
						if (!empty($shippingService['ShipTo'])) {
							// only support once service currently, but store as array for future use
							$shipToLocations = array($shippingService['ShipTo']);
						}

						if ($freeShipping && $index == 0) {
							$cost = 0;
							$additionalCost = 0;
						}
						else {
							$cost = DefaultPriceFormat($shippingService['Cost']);
							$additionalCost = DefaultPriceFormat($shippingService['MoreCost']);
						}

						$services[] = array (
							'ebay_shipping_id' => 0,
							'name' => $shippingService['Type'],
							'cost' => $cost,
							'additional_cost' => $additionalCost,
							'ship_to_locations' => serialize($shipToLocations),
						);
					}
					break;
				case 'Calculated':
					$shippingPackage = $_POST[$shippingArea . 'ShippingPackage'];

					if (!empty($_POST[$shippingArea . 'YesFreeCalculatedShipping'])) {
						$freeShipping = 1;
					}

					foreach ($_POST[$shippingArea . 'ShippingServCalculated'] as $shippingService) {
						if (!$shippingService['Type']) {
							continue;
						}

						$shipToLocations = array();
						if (!empty($shippingService['ShipTo'])) {
							// only support once service currently, but store as array for future use
							$shipToLocations = array($shippingService['ShipTo']);
						}

						$services[] = array (
							'ebay_shipping_id' => 0,
							'name' => $shippingService['Type'],
							'cost' => 0,
							'additional_cost' => 0,
							'ship_to_locations' => serialize($shipToLocations),
						);
					}
					break;
			}

			// Save the shipping data
			$shippingData = array(
				'ebay_listing_template_id' 	=> $templateId,
				'area' 						=> $shippingArea,
				'cost_type' 				=> $shippingType,
				'offer_pickup' 				=> $offerPickup,
				'is_free_shipping' 			=> $freeShipping,
				'handling_cost' 			=> $handlingCost,
				'package_type' 				=> $shippingPackage,
				'get_it_fast' 				=> $getItFast,
			);
			$eBayShippingId = $this->db->InsertQuery("ebay_shipping", $shippingData);
			if (!$eBayShippingId) {
				$this->db->RollbackTransaction();
				$error = $this->db->Error();
				return false;
			}

			// Save Shipping Services to the database
			foreach ($services as $serviceData) {
				$serviceData['ebay_shipping_id'] = (int)$eBayShippingId;
				if(!$this->db->InsertQuery("ebay_shipping_serv", $serviceData)) {
					$this->db->RollbackTransaction();
					$error = $this->db->Error();
					return false;
				}
			}
		}

		$this->db->CommitTransaction();

		FlashMessage(GetLang('EbayTemplateSavedSuccessfully'), MSG_SUCCESS, '', 'EbayListingTemplate');

		return $templateId;
	}

	/**
	 * To save the eBay settings on Interspire Shopping Cart
	 */
	public function SaveEbay()
	{
		$devId = trim($_POST['EbayDevId']);
		$appId = trim($_POST['EbayAppId']);
		$certId = trim($_POST['EbayCertId']);
		$userToken = trim($_POST['EbayUserToken']);
		$defaultSite = (int)$_POST['EbayDefaultSite'];
		$testMode = $_POST['EbayTestMode'];
		if (!$this->isProductionSiteAllowed()) {
			$testMode = 'sandbox';
		}
		ISC_ADMIN_EBAY_OPERATIONS::setSiteId($defaultSite);

		// the events that we're subscribing to
		$ebayEvents = array(
			'AuctionCheckoutComplete',
			'BestOffer',
			'BestOfferPlaced',
			'BidPlaced',
			'BidReceived',
			'CheckoutBuyerRequestsTotal',
			'EndOfAuction',
			'Feedback',
			'FeedbackLeft',
			'FeedbackReceived',
			'FixedPriceEndOfTransaction',
			'FixedPriceTransaction',
			'ItemClosed',
			'ItemExtended',
			'ItemListed',
			'ItemRevised',
			'ItemRevisedAddCharity',
			'ItemSold',
			'ItemSuspended',
			'ItemUnsold',
		);

		$getStoreAndSubscribe = false;

		$settingsValid = true;

		// was the keys changed? we should unsubscribe from the old notifications first
		if (self::checkEbayConfig('', false)) {
			$currentDevId = GetConfig('EbayDevId');
			$currentAppId = GetConfig('EbayAppId');
			$currentCertId = GetConfig('EbayCertId');
			$currentUserToken = GetConfig('EbayUserToken');
			$currentTestMode = GetConfig('EbayTestMode');

			if ($currentDevId != $devId ||
				$currentAppId != $appId ||
				$currentCertId != $certId ||
				$currentUserToken != $userToken ||
				$currentTestMode != $testMode
				) {

				try {
					ISC_ADMIN_EBAY_OPERATIONS::setApplicationNotificationsEnabled(false);

					$disableArray = array_fill(0, count($ebayEvents), false);
					$unsubscribeEvents = array_combine($ebayEvents, $disableArray);
					ISC_ADMIN_EBAY_OPERATIONS::setApplicationNotificationEvents($unsubscribeEvents);
				}
				catch (ISC_EBAY_API_EXCEPTION $ex) {

				}

				$getStoreAndSubscribe = true;
			}
		}
		else {
			$getStoreAndSubscribe = true;
		}

		// Save the Ebay Settings to config file
		$GLOBALS['ISC_NEW_CFG']['EbayDevId'] = $devId;
		$GLOBALS['ISC_NEW_CFG']['EbayAppId'] = $appId;
		$GLOBALS['ISC_NEW_CFG']['EbayCertId'] = $certId;
		$GLOBALS['ISC_NEW_CFG']['EbayUserToken'] = $userToken;
		$GLOBALS['ISC_NEW_CFG']['EbayDefaultSite'] = $defaultSite;
		$GLOBALS['ISC_NEW_CFG']['EbayTestMode'] = $testMode;

		$settings = GetClass('ISC_ADMIN_SETTINGS');

		// if we save the configuration successfully
		if(!$settings->CommitSettings()) {
			// otherwise display the error message
			FlashMessage(GetLang('EbaySettingsNotSaved'), MSG_ERROR, 'index.php?ToDo=viewEbay&currentTab=2');
		}

		try {
			// attempt to get ebay details for US to validate settings
			ISC_ADMIN_EBAY_OPERATIONS::setSiteId(0);
			ISC_ADMIN_EBAY_OPERATIONS::geteBayOfficialTime();
		}
		catch (ISC_EBAY_API_EXCEPTION $ex) {
			$settingsValid = false;
		}

		$GLOBALS['ISC_NEW_CFG']['EbaySettingsValid'] = $settingsValid;

		if(!$settings->CommitSettings()) {
			FlashMessage(GetLang('EbaySettingsNotSaved'), MSG_ERROR, 'index.php?ToDo=viewEbay&currentTab=2');
		}

		if (empty ($devId) && empty ($appId) && empty ($certId) && empty ($userToken)) {
			FlashMessage(GetLang('EbayKeysRemovedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewEbay&currentTab=2', 'EbayConfig');
		}

		if ($settingsValid) {
			if ($getStoreAndSubscribe) {
				// above settings need to be committed first before we can check for the ebay store correctly
				$ebayStore = '';
				try {
					$xml = ISC_ADMIN_EBAY_OPERATIONS::getStore();

					$ebayStore = (string)$xml->Store->Name;
				}
				catch (ISC_EBAY_API_EXCEPTION $ex) {
					// if we can't retrieve a store name then we don't want to produce an error, silently continue
				}

				// save ebay store setting
				$GLOBALS['ISC_NEW_CFG']['EbayStore'] = $ebayStore;

				if(!$settings->CommitSettings()) {
					// otherwise display the error message
					FlashMessage(GetLang('EbaySettingsNotSaved'), MSG_ERROR, 'index.php?ToDo=viewEbay&currentTab=2', 'EbayConfig');
				}

				// ensure our application is subscribed to ebay notifications
				try {
					ISC_ADMIN_EBAY_OPERATIONS::setApplicationNotificationsEnabled(true);

					$enableArray = array_fill(0, count($ebayEvents), true);
					$subscribeEvents = array_combine($ebayEvents, $enableArray);
					ISC_ADMIN_EBAY_OPERATIONS::setApplicationNotificationEvents($subscribeEvents);
				}
				catch (ISC_EBAY_API_EXCEPTION $ex) {
					FlashMessage(GetLang('EbayNotificationsNotSubscribed', array('message' => $ex->getMessage())), MSG_ERROR, 'index.php?ToDo=viewEbay&currentTab=2', 'EbayConfig');
				}
			}
		}
		else {
			FlashMessage(GetLang('EbaySettingsNotValid'), MSG_ERROR, 'index.php?ToDo=viewEbay&currentTab=2', 'EbayConfig');
		}

		// log the action
		if (defined('ISC_ADMIN_CP')) {
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();
		}

		// display the success message
		FlashMessage(GetLang('EbaySettingsSavedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewEbay&currentTab=2', 'EbayConfig');
	}

	/**
	* Displays the 'Create an eBay Listing Template' form
	*
	*/
	public function addEbayTemplate()
	{
		$this->template->assign('Message', GetFlashMessageBoxes());

		// Check if the eBay keys are exist for API connection
		if (!self::checkEbayConfig()) {
			exit;
		}

		$this->template->assign('formTitle', GetLang('CreateEbayTemplate'));

		$this->template->assign('ebaySites', $this->getSupportedSites());

		$defaultSite = self::DEFAULT_SITE_ID;
		if (GetConfig('EbayDefaultSite')) {
			$defaultSite = (int)GetConfig('EbayDefaultSite');
		}
		$this->template->assign('siteId', $defaultSite);

		$this->template->assign('hasStore', (bool)GetConfig('EbayStore'));

		$GLOBALS['BreadcrumEntries'][GetLang('CreateEbayTemplate')] = 'index.php?ToDo=addEbayTemplate';
		$this->engine->PrintHeader();
		$this->template->display('ebay.template.form.tpl');
		$this->engine->PrintFooter();
	}

	/**
	* Displays the edit template form
	*
	*/
	public function editEbayTemplate()
	{
		GetLib('class.json');

		$templateId = (int)$_GET['templateId'];

		try {
			$template = new ISC_ADMIN_EBAY_TEMPLATE($templateId);

			$this->template->assign('templateId', $templateId);

			$this->template->assign('formTitle', GetLang('EditEbayTemplate'));
			$this->template->assign('hasStore', (bool)GetConfig('EbayStore'));

			$this->template->assign('ebaySites', $this->getSupportedSites());
			$this->template->assign('siteId', $template->getSiteId());
			$this->template->assign('templateName', $template->getTemplateName());
			$this->template->assign('templateIsDefault', $template->isDefaultTemplate());
			$this->template->assign('isPrivateListing', $template->isPrivateListing());

			// setup category details
			$primaryCategoryOptions = $template->getPrimaryCategoryOptions();
			$secondaryCategoryOptions = $template->getSecondaryCategoryOptions();
			$this->template->assign('primaryCategory', $primaryCategoryOptions['path']);
			$this->template->assign('primaryCategoryOptions', ISC_JSON::encode($primaryCategoryOptions));
			$this->template->assign('categoryOptions', $primaryCategoryOptions);
			$this->template->assign('sellingMethod', $template->getSellingMethod());

			try {
				if (empty ($secondaryCategoryOptions)) {
					$secondaryCategoryId = $template->getSecondaryCategoryId();
					if (!empty ($secondaryCategoryId)) {
						$secondaryCategoryOptions = '';
						$secondaryCategoryOptions = ISC_ADMIN_EBAY_CATEGORIES::getCategoryFeatures($secondaryCategoryId, $template->getSiteId());
						$categoryPath = ISC_ADMIN_EBAY_CATEGORIES::getFormattedCategoryPath($secondaryCategoryId, $template->getSiteId());
						$secondaryCategoryOptions['path'] = $categoryPath;
					} else {
						$secondaryCategoryOptions = ISC_ADMIN_EBAY_CATEGORIES::getCategoryOptionsFromId($template->getSecondaryCategoryId(), $template->getSiteId());
					}
				}
			}
			catch (Exception $ex) {
				$secondaryCategoryOptions = array(
					'category_id'	=> $template->getSecondaryCategoryId(),
					'name'			=> $template->getSecondaryCategoryName(),
					'path'			=> $template->getSecondaryCategoryName(),
				);
			}


			if ($secondaryCategoryOptions) {
				$this->template->assign('secondaryCategoryOptionsData', $secondaryCategoryOptions);
				$this->template->assign('secondaryCategory', $secondaryCategoryOptions['path']);
			}
			$secCatNotSupportVariations = (isset ($secondaryCategoryOptions['variations_supported']) && $secondaryCategoryOptions['variations_supported'] == 0);
			$this->template->assign('secCatSelectedNotSupportVariations', ($secCatNotSupportVariations));
			$this->template->assign('secondaryCategoryOptions', ISC_JSON::encode($secondaryCategoryOptions));
			$this->template->assign('categoryFeaturesList', $this->template->render('ebay.template.featureslist.tpl'));

			$primaryStoreCategoryOptions = array();
			if ($template->getPrimaryStoreCategoryId()) {
				$primaryStoreCategoryOptions = array(
					'category_id'	=> $template->getPrimaryStoreCategoryId(),
					'name'			=> $template->getPrimaryStoreCategoryName(),
					'path'			=> $template->getPrimaryStoreCategoryName(),
				);

				$this->template->assign('primaryStoreCategory', $primaryStoreCategoryOptions['path']);
			}
			$this->template->assign('primaryStoreCategoryOptions', ISC_JSON::encode($primaryStoreCategoryOptions));

			$secondaryStoreCategoryOptions = array();
			if ($template->getSecondaryStoreCategoryId()) {
				$secondaryStoreCategoryOptions = array(
					'category_id'	=> $template->getSecondaryStoreCategoryId(),
					'name'			=> $template->getSecondaryStoreCategoryName(),
					'path'			=> $template->getSecondaryStoreCategoryName(),
				);

				$this->template->assign('secondaryStoreCategory', $secondaryStoreCategoryOptions['path']);
			}
			$this->template->assign('secondaryStoreCategoryOptions', ISC_JSON::encode($secondaryStoreCategoryOptions));

			$this->template->assign('sellingMethod', $template->getSellingMethod());
		}
		catch (Exception $ex) {
			FlashMessage($ex->getMessage(), MSG_ERROR, 'index.php?ToDo=viewEbay');
		}

		$GLOBALS['BreadcrumEntries'][GetLang('EditEbayTemplate')] = 'index.php?ToDo=editEbayTemplate';
		$this->engine->PrintHeader();
		$this->template->display('ebay.template.form.tpl');
		$this->engine->PrintFooter();
	}

	/**
	* Displays a template details form specific for an eBay site and selected category options
	*
	* @param int $siteId The eBay site to display a template for
	* @param array $categoryOptions The primary category options to customize the form
	* @param int $templateId Optional template Id to use to fill the form with
	* @return string The form HTML
	*/
	public function getTemplateForm($siteId, $categoryOptions, $templateId = 0)
	{
		// Load eBay XML cache
		$xmlContent = str_replace('xmlns=', 'ns=', $this->ReadCache($siteId));
		$getEbayDetailsXml = new SimpleXMLElement($xmlContent);

		$currencyId = $this->getCurrencyFromSiteId($siteId);
		$currency = GetCurrencyById($currencyId);

		$this->template->assign('currency', $currency);
		$this->template->assign('currencyToken', $currency['currencystring']);
		$this->template->assign('options', $categoryOptions);

		$this->template->assign('auctionDurations',  $this->getDurationOptions($categoryOptions['auction_durations']));
		$this->template->assign('fixedDurations',  $this->getDurationOptions($categoryOptions['fixed_durations']));

		$paymentMethods = $categoryOptions['payment_methods'];
		asort($paymentMethods);
		$this->template->assign('paymentMethods', $this->getPaymentMethodOptions($paymentMethods));

		// location details
		$this->template->assign('countries', GetCountryListAsIdValuePairs());

		// shipping details
		// Options for shipping services
		$shippingServiceObj = $getEbayDetailsXml->xpath('/GeteBayDetailsResponse/ShippingServiceDetails');
		$shippingServices = $this->getShippingAsOptions($shippingServiceObj);

		// Options for handling time
		$handlingTimeObject = $getEbayDetailsXml->xpath('/GeteBayDetailsResponse/DispatchTimeMaxDetails');
		$handlingTimeArray = $this->convertEbayObjectToArray('DispatchTimeMax', 'Description', $handlingTimeObject);
		// remove the 0 days option as handling time is now required with ebay and 0 isnt valid
		unset($handlingTimeArray[0]);
		ksort($handlingTimeArray);
		$this->template->assign('handlingTimes', $handlingTimeArray);

		// Retrieving shipping cost type
		$this->template->assign('domesticShippingCostTypes', $shippingServices['Domestic']['ServiceTypes']);
		$this->template->assign('internationalShippingCostTypes', $shippingServices['International']['ServiceTypes']);

		// Shipping service Flat
		$domesticFlatServices = $shippingServices['Domestic']['Services']['Flat'];

		// is Pickup offered as a service? remove it from our service list and set it as a template var
		if (isset($domesticFlatServices['Other']['Pickup'])) {
			$this->template->assign('domesticPickupAllowed', true);
			unset($domesticFlatServices['Other']['Pickup']);
		}
		$this->template->assign('DomesticShippingServFlat', $domesticFlatServices);
		$this->template->assign('InternationalShippingServFlat', $shippingServices['International']['Services']['Flat']);

		// Shipping service Calculated
		if (!empty($shippingServices['Domestic']['Services']['Calculated'])) {
			$this->template->assign('DomesticShippingServCalculated', $shippingServices['Domestic']['Services']['Calculated']);
		}
		if (!empty($shippingServices['International']['Services']['Calculated'])) {
			$this->template->assign('InternationalShippingServCalculated', $shippingServices['International']['Services']['Calculated']);
		}

		// Shipping Service Package Details - only used for calculated shipping cost type
		$shippingPackageObj = $getEbayDetailsXml->xpath('/GeteBayDetailsResponse/ShippingPackageDetails');
		$shippingPackageArr = $this->convertEbayObjectToArray('ShippingPackage', 'Description', $shippingPackageObj);
		$this->template->assign('DomesticShippingPackage', $shippingPackageArr);
		$this->template->assign('InternationalShippingPackage', $shippingPackageArr);

		// ship to locations
		$shippingLocationObj = $getEbayDetailsXml->xpath('/GeteBayDetailsResponse/ShippingLocationDetails');
		$shippingLocationArr = $this->convertEbayObjectToArray('ShippingLocation', 'Description', $shippingLocationObj);
		asort($shippingLocationArr);
		$this->template->assign('ShipToLocations', $shippingLocationArr);

		// additional shipping details
		$salesTaxStatesObject = $getEbayDetailsXml->xpath('/GeteBayDetailsResponse/TaxJurisdiction');
		$salesTaxStatesArray = $this->convertEbayObjectToArray('JurisdictionID', 'JurisdictionName', $salesTaxStatesObject);
		$this->template->assign('hasSalesTaxStates', !empty($salesTaxStatesArray));
		asort($salesTaxStatesArray);
		$this->template->assign('salesTaxStates', $salesTaxStatesArray);

		// refund details
		$refundObject = $getEbayDetailsXml->xpath('/GeteBayDetailsResponse/ReturnPolicyDetails/Refund');
		if ($refundObject) {
			$this->template->assign('refundOptions', $this->convertEbayObjectToArray('RefundOption', 'Description', $refundObject));
		}
		$this->template->assign('refundSupported', (bool)$refundObject);

		$returnsWithinObject = $getEbayDetailsXml->xpath('/GeteBayDetailsResponse/ReturnPolicyDetails/ReturnsWithin');
		if ($returnsWithinObject) {
			$this->template->assign('returnsWithinOptions', $this->convertEbayObjectToArray('ReturnsWithinOption', 'Description', $returnsWithinObject));
		}
		$this->template->assign('returnsWithinSupported', (bool)$returnsWithinObject);

		$returnCostPaidByObject = $getEbayDetailsXml->xpath('/GeteBayDetailsResponse/ReturnPolicyDetails/ShippingCostPaidBy');
		if ($returnCostPaidByObject) {
			$this->template->assign('returnCostPaidByOptions', $this->convertEbayObjectToArray('ShippingCostPaidByOption', 'Description', $returnCostPaidByObject));
		}
		$this->template->assign('returnCostPaidBySupported', (bool)$returnCostPaidByObject);

		$returnDescriptionObject = $getEbayDetailsXml->xpath('/GeteBayDetailsResponse/ReturnPolicyDetails/Description');
		$this->template->assign('returnDescriptionSupported', (bool)$returnDescriptionObject);

		// hit counter
		$availableHitCounters = array ('NoHitCounter','HiddenStyle','BasicStyle','RetroStyle');
		$hitCounters = array();
		foreach ($availableHitCounters as $counter) {
			$hitCounters[$counter] = GetLang($counter);
		}
		$this->template->assign('hitCounters', $hitCounters);

		// Paid upgrade options

		// Gallery Style
		$availableGalleryOptions = array ('None', 'Gallery', 'Plus', 'Featured');
		$galleryOptions = array();
		foreach ($availableGalleryOptions as $galleryOption) {
			$galleryOptions[$galleryOption] = GetLang('EbayGallery' . $galleryOption);
		}
		$this->template->assign('galleryOptions', $galleryOptions);

		// Listing enhancement
		$listingFeaturesObject = $getEbayDetailsXml->xpath('/GeteBayDetailsResponse/ListingFeatureDetails');
		$supportedListingFeatures = array('BoldTitle','Border','FeaturedFirst','FeaturedPlus','GiftIcon','Highlight','HomePageFeatured','ProPack');
		$listingFeatures = array();
		if (isset($listingFeaturesObject[0])) {
			foreach ($listingFeaturesObject[0] as $featureCode => $availability) {
				//@ToDo add support for PowerSellerOnly and TopRatedSellerOnly options
				if (!in_array($featureCode, $supportedListingFeatures) || $availability != 'Enabled') {
					continue;
				}

				$listingFeatures[$featureCode] = GetLang($featureCode);
			}
		}
		$this->template->assign('listingFeatures', $listingFeatures);

		// any defaults we should set
		$this->template->assign('quantityOption', 'one');
		$this->template->assign('useItemPhoto', true);

		$this->template->assign('locationCountry', GetCountryIdByName(GetConfig('CompanyCountry')));
		$this->template->assign('locationZip', GetConfig('CompanyZip'));
		$this->template->assign('locationCityState', GetConfig('CompanyCity') . ', ' . GetConfig('CompanyState'));

		$this->template->assign('reservePriceOption', 'ProductPrice');
		$this->template->assign('reservePriceCustom', $categoryOptions['minimum_reserve_price']);
		$this->template->assign('startPriceOption', 'ProductPrice');
		$this->template->assign('startPriceCustom', 0.01);
		$this->template->assign('buyItNowPriceOption', 'ProductPrice');
		$this->template->assign('buyItNowPriceCalcPrice', 10);
		$this->template->assign('buyItNowPriceCustom', 0.01);
		$this->template->assign('fixedBuyItNowPriceOption', 'ProductPrice');
		$this->template->assign('fixedBuyItNowPriceCustom', 0.01);

		$this->template->assign('auctionDuration', 'Days_7');
		$this->template->assign('fixedDuration', 'Days_7');

		$this->template->assign('useDomesticShipping', false);
		$this->template->assign('useInternationalShipping', false);
		$this->template->assign('useSalesTax', false);

		$this->template->assign('hitCounter', 'BasicStyle');

		$this->template->assign('galleryOption', 'Gallery');

		$this->template->assign('domesticFlatCount', 0);
		$this->template->assign('domesticCalcCount', 0);
		$this->template->assign('internationalFlatCount', 0);
		$this->template->assign('internationalCalcCount', 0);

		// assign template specific variables
		if ($templateId) {
			$template = new ISC_ADMIN_EBAY_TEMPLATE($templateId);

			$this->template->assign('currency', $template->getCurrency());

			// quantity
			if ($template->getQuantityToSell() == 1) {
				$quantityOption = 'one';
			}
			else {
				$quantityOption = 'more';
				$this->template->assign('moreQuantity', $template->getQuantityToSell());
			}
			$this->template->assign('quantityOption', $quantityOption);

			// item photo
			$this->template->assign('useItemPhoto', $template->getUseItemPhoto());

			// lot size
			$this->template->assign('lotSize', $template->getLotSize());

			// location details
			$this->template->assign('locationCountry', GetCountryIdByISO2($template->getItemLocationCountry()));
			$this->template->assign('locationZip', $template->getItemLocationZip());
			$this->template->assign('locationCityState', $template->getItemLocationCityState());

			// selling method
			$this->template->assign('sellingMethod', $template->getSellingMethod());

			if ($template->getSellingMethod() == self::CHINESE_AUCTION_LISTING) {
				// reserve price
				$this->template->assign('useReservePrice', $template->getReservePriceUsed());
				$reservePriceOption = 'ProductPrice';
				if ($template->getReservePriceUsed()) {
					$reservePriceOption = $template->getReservePriceOption();

					if ($reservePriceOption == 'PriceExtra') {
						$this->template->assign('reservePriceCalcPrice', $template->getReservePriceCalcPrice());
						$this->template->assign('reservePriceCalcOption', $template->getReservePriceCalcOption());
						$this->template->assign('reservePriceCalcOperator', $template->getReservePriceCalcOperator());
					}
					elseif ($reservePriceOption == 'CustomPrice') {
						$this->template->assign('reservePriceCustom', $template->getReservePriceCustomPrice());
					}
				}
				$this->template->assign('reservePriceOption', $reservePriceOption);

				// start price
				$startPriceOption = $template->getStartPriceOption();

				if ($startPriceOption == 'PriceExtra') {
					$this->template->assign('startPriceCalcPrice', $template->getStartPriceCalcPrice());
					$this->template->assign('startPriceCalcOption', $template->getStartPriceCalcOption());
					$this->template->assign('startPriceCalcOperator', $template->getStartPriceCalcOperator());
				}
				elseif ($startPriceOption == 'CustomPrice') {
					$this->template->assign('startPriceCustom', $template->getStartPriceCustomPrice());
				}
				$this->template->assign('startPriceOption', $startPriceOption);


				// buy it now price
				$this->template->assign('useBuyItNowPrice', $template->getBuyItNowPriceUsed());
				$buyItNowPriceOption = 'ProductPrice';
				if ($template->getBuyItNowPriceUsed()) {
					$buyItNowPriceOption = $template->getBuyItNowPriceOption();

					if ($buyItNowPriceOption == 'PriceExtra') {
						$this->template->assign('buyItNowPriceCalcPrice', $template->getBuyItNowPriceCalcPrice());
						$this->template->assign('buyItNowPriceCalcOption', $template->getBuyItNowPriceCalcOption());
						$this->template->assign('buyItNowPriceCalcOperator', $template->getBuyItNowPriceCalcOperator());
					}
					elseif ($buyItNowPriceOption == 'CustomPrice') {
						$this->template->assign('buyItNowPriceCustom', $template->getBuyItNowPriceCustomPrice());
					}
				}
				$this->template->assign('buyItNowPriceOption', $buyItNowPriceOption);

				$this->template->assign('auctionDuration', $template->getListingDuration());
			}
			else {
				// Fixed Price Item
				$fixedBuyItNowPriceOption = $template->getStartPriceOption();
				if ($fixedBuyItNowPriceOption == 'PriceExtra') {
					$this->template->assign('fixedBuyItNowPriceCalcPrice', $template->getStartPriceCalcPrice());
					$this->template->assign('fixedBuyItNowPriceCalcOption', $template->getStartPriceCalcOption());
					$this->template->assign('fixedBuyItNowPriceCalcOperator', $template->getStartPriceCalcOperator());
				}
				elseif ($fixedBuyItNowPriceOption == 'CustomPrice') {
					$this->template->assign('fixedBuyItNowPriceCustom', $template->getStartPriceCustomPrice());
				}

				$this->template->assign('fixedBuyItNowPriceOption', $fixedBuyItNowPriceOption);

				$this->template->assign('fixedDuration', $template->getListingDuration());
			}

			// payment details
			$this->template->assign('selectedPaymentMethods', $template->getPaymentMethods());
			$this->template->assign('paypalEmailAddress', $template->getPayPalEmailAddress());

			// domestic shipping
			$this->template->assign('useDomesticShipping', $template->getUseDomesticShipping());
			if ($template->getUseDomesticShipping()) {
				$settings = $template->getDomesticShippingSettings();
				$shippingType = $settings['cost_type'];
				$this->template->assign('domesticShippingCostType', $shippingType);

				$services = $template->getDomesticShippingServices();

				// flat options
				if ($shippingType == 'Flat') {
					$this->template->assign('domesticFlatShippingServices', $services);
					$this->template->assign('domesticFlatCount', count($services));
				}
				// calculated options
				else {
					$service = current($services);

					$this->template->assign('domesticPackageType', $settings['package_type']);
					$this->template->assign('domesticCalculatedShippingServices', $services);
					$this->template->assign('domesticCalcCount', count($services));
				}

				$this->template->assign('domesticFreeShipping', $settings['is_free_shipping']);
				$this->template->assign('domesticGetItFast', $settings['get_it_fast']);
				$this->template->assign('domesticLocalPickup', $settings['offer_pickup']);
				$this->template->assign('domesticHandlingCost', $settings['handling_cost']);
			}

			// international shipping
			$this->template->assign('useInternationalShipping', $template->getUseInternationalShipping());
			if ($template->getUseInternationalShipping()) {
				$settings = $template->getInternationalShippingSettings();
				$shippingType = $settings['cost_type'];
				$this->template->assign('internationalShippingCostType', $shippingType);

				$services = $template->getInternationalShippingServices();

				// flat options
				if ($shippingType == 'Flat') {
					$this->template->assign('internationalFlatShippingServices', $services);
					$this->template->assign('internationalFlatCount', count($services));
				}
				// calculated options
				else {
					$service = current($services);

					$this->template->assign('internationalPackageType', $settings['package_type']);
					$this->template->assign('internationalCalculatedShippingServices', $services);
					$this->template->assign('internationalCalcCount', count($services));
				}

				$this->template->assign('internationalFreeShipping', $settings['is_free_shipping']);
				$this->template->assign('internationalHandlingCost', $settings['handling_cost']);
			}

			// other shipping
			$this->template->assign('handlingTime', $template->getHandlingTime());
			$this->template->assign('useSalesTax', $template->getUseSalesTax());
			$this->template->assign('salesTaxState', $template->getSalesTaxState());
			$this->template->assign('salesTaxPercent', $template->getSalesTaxPercent());
			$this->template->assign('salesTaxIncludesShipping', $template->getShippingIncludedInTax());

			// other details
			$this->template->assign('checkoutInstructions', $template->getCheckoutInstructions());
			$this->template->assign('acceptReturns', $template->getReturnsAccepted());
			$this->template->assign('returnOfferedAs', $template->getReturnOfferedAs());
			$this->template->assign('returnsPeriod', $template->getReturnsPeriod());
			$this->template->assign('returnCostPaidBy', $template->getReturnCostPaidBy());
			$this->template->assign('additionalPolicyInfo', $template->getAdditionalPolicyInfo());

			$this->template->assign('hitCounter', $template->getCounterStyle());

			$this->template->assign('galleryOption', $template->getGalleryType());

			$this->template->assign('selectedListingFeatures', $template->getListingFeatures());
		}

		return $this->template->render('ebay.template.form.details.tpl');
	}

	/**
	 * This function get the options of ebay duration
	 *
	 * @param string The eBay duration
	 * @return array The options of eBay durations
	 */
	private function getDurationOptions($durations)
	{
		$options = array();
		foreach ($durations as $duration) {
			$options[$duration] = GetLang('EbayDuration' . $duration);
		}

		return $options;
	}

	/**
	 * This function get the options of ebay payment methods
	 *
	 * @param string The eBay payment method
	 * @return array The options of eBay payment methods
	 */
	private function getPaymentMethodOptions($methods)
	{
		$options = array();
		foreach ($methods as $method) {
			$options[$method] = GetLang('EbayPaymentMethod' . $method);
		}

		return $options;
	}

	/**
	 * Display the form of eBay management
	 *
	 * @param boolean $showOtherTab To determine if other tab need to be shown other than eBay config tab
	 */
	public function ManageEbay($showOtherTab = true)
	{
		// Fetch any results of ebay templates, place them in the data grid
		$GLOBALS['EbayTemplateDataGrid'] = $this->ManageEbayTemplateGrid($numTemplate);

		// Fetch any results of available active listing, place them in the data grid
		$GLOBALS['EbayListingDataGrid'] = $this->ManageEbayLiveListingGrid($numTemplate);

		// Was this an ajax based sort? Return the table now
		if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
			if (isset ($_REQUEST['currentTab']) && $_REQUEST['currentTab'] == 1) {
				echo $GLOBALS['EbayTemplateDataGrid'];
			} else if (isset ($_REQUEST['currentTab']) && $_REQUEST['currentTab'] == 0) {
				echo $GLOBALS['EbayListingDataGrid'];
			}
			return;
		}
		if (empty($GLOBALS['EbayTemplateDataGrid'])) {
			$GLOBALS['DisableTemplateDelete'] = 'disabled';
			if (isset ($_GET['searchQueryTemplate'])) {
				FlashMessage(GetLang('NoTemplateFound'), MSG_ERROR, '', 'EbayListingTemplate');
			} else {
				$GLOBALS['DisplayTemplateSearch'] = 'display:none;';
				FlashMessage(GetLang('NoTemplates'), MSG_INFO, '', 'EbayListingTemplate');
			}
		}
		if (!trim($GLOBALS['EbayListingDataGrid'])) {
			$GLOBALS['DisableListingActionDropdown'] = 'disabled';
			if (isset ($_GET['searchQueryListing'])) {
				FlashMessage(GetLang('NoListingMatch'), MSG_ERROR, '', 'EbayLiveListing');
			}
			else {
				$GLOBALS['DisplayListingSearch'] = 'display:none;';
				FlashMessage(GetLang('NoListingFound'), MSG_INFO, '', 'EbayLiveListing');
			}
		}
		if (!$this->isProductionSiteAllowed()) {
			$GLOBALS['DisableProd'] = 'disabled="disabled"';
		}

		$GLOBALS['Message'] = GetFlashMessageBoxes();
		$GLOBALS['EbayConfigMessage'] = GetFlashMessageBoxes('EbayConfig');
		$GLOBALS['EbayListingTemplateMessage'] = GetFlashMessageBoxes('EbayListingTemplate');
		$GLOBALS['EbayLiveListingMessage'] = GetFlashMessageBoxes('EbayLiveListing');

		// Retrieve the existing value if it's exist
		$GLOBALS['EbayDevId'] = GetConfig('EbayDevId');
		$GLOBALS['EbayAppId'] = GetConfig('EbayAppId');
		$GLOBALS['EbayCertId'] = GetConfig('EbayCertId');
		$GLOBALS['EbayUserToken'] = GetConfig('EbayUserToken');
		$GLOBALS['EbayDefaultSite'] = GetConfig('EbayDefaultSite');
		$GLOBALS['EbayStore'] = GetConfig('EbayStore');
		$GLOBALS['EbayTestMode'] = GetConfig('EbayTestMode');

		// determine the selected tab
		if(isset($_REQUEST['currentTab']) && $showOtherTab) {
			$GLOBALS['CurrentTab'] = (int)$_REQUEST['currentTab'];
		}
		else {
			$GLOBALS['CurrentTab'] = 2;
		}

		// determine the visibility of other tabs
		$GLOBALS['ShowTab'] = '';
		if (!$showOtherTab) {
			$GLOBALS['ShowTab'] = 'display:none;';
		}

		// Generate the sites options
		$availableSites = $this->getSupportedSites();
		$defaultSite = self::DEFAULT_SITE_ID;
		if (GetConfig('EbayDefaultSite')) {
			$defaultSite = (int)GetConfig('EbayDefaultSite');
		}
		if (!empty ($availableSites)) {
			$GLOBALS['EbayDefaultSite'] = $this->GetArrayAsOptions($availableSites, $defaultSite, false);
		}

		// Retrive Ebay Store's value
		$GLOBALS['EbayStoreDisplay'] = sprintf(GetLang('EbayStoreDesc'), GetLang('EbayNoStoreDesc'));
		if (trim($GLOBALS['EbayStore']) != "") {
			$GLOBALS['EbayStoreDisplay'] = sprintf(GetLang('EbayStoreDesc'), $GLOBALS['EbayStore']);
		}

		// Form Action
		$GLOBALS['FormActionEbayConf'] = "saveEbay";

		// Prepare the template rendering
		if(!isset($_REQUEST['ajax'])) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
		}

		$this->template->display('ebay.manage.tpl');

		if(!isset($_REQUEST['ajax'])) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
		}
	}

	/**
	 * This function generate ebay template listing page
	 *
	 * @param int $numTemplate The total number of the template
	 * @return string Return a rendered html of template grid
	 */
	public function ManageEbayTemplateGrid(&$numTemplate)
	{
		// Show a list of ebay templates in a table
		$page = 0;
		$start = 0;
		$numTemplate = 0;
		$numPages = 0;
		$GLOBALS['EbayTemplateGrid'] = "";
		$GLOBALS['Nav'] = "";
		$max = 0;
		$searchURL = '';

		if (isset($_GET['searchQueryTemplate'])) {
			$query = $_GET['searchQueryTemplate'];
			$GLOBALS['Query'] = $query;
			$searchURL = '&amp;searchQueryTemplate='.$query;
		} else {
			$query = "";
			$GLOBALS['Query'] = "";
		}

		if (isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'asc') {
			$sortOrder = 'asc';
		} else {
			$sortOrder = "desc";
		}

		$sortLinks = array(
			"Name" => "elt.name",
			"Date" => "elt.date_added",
			"Enabled" => "elt.enabled",
		);

		if (isset($_GET['sortField']) && in_array($_GET['sortField'], $sortLinks)) {
			$sortField = $_GET['sortField'];
			SaveDefaultSortField("ManageEbay", $_REQUEST['sortField'], $sortOrder);
		}
		else {
			$sortField = "elt.name";
			list($sortField, $sortOrder) = GetDefaultSortField("ManageEbay", $sortField, $sortOrder);
		}

		if (isset($_GET['page'])) {
			$page = (int)$_GET['page'];
		} else {
			$page = 1;
		}

		$sortURL = sprintf("&sortField=%s&sortOrder=%s", $sortField, $sortOrder);
		$GLOBALS['SortURL'] = $sortURL;

		// Limit the number of questions returned
		if ($page == 1) {
			$start = 1;
		} else {
			$start = ($page * ISC_EBAY_TEMPLATE_PER_PAGE) - (ISC_EBAY_TEMPLATE_PER_PAGE-1);
		}

		$start = $start-1;

		// Get the results for the query
		$templateResult = $this->_GetEbayTemplateList($query, $start, $sortField, $sortOrder, $numTemplate);
		$numPages = ceil($numTemplate / ISC_EBAY_TEMPLATE_PER_PAGE);

		// Add the "(Page x of n)" label
		if($numTemplate > ISC_EBAY_TEMPLATE_PER_PAGE) {
			$GLOBALS['Nav'] = sprintf("(%s %d of %d) &nbsp;&nbsp;&nbsp;", GetLang('Page'), $page, $numPages);
			$GLOBALS['Nav'] .= BuildPagination($numTemplate, ISC_EBAY_TEMPLATE_PER_PAGE, $page, sprintf("index.php?ToDo=viewEbay&currentTab=1%s", $sortURL));
		}
		else {
			$GLOBALS['Nav'] = "";
		}

		$GLOBALS['Nav'] = rtrim($GLOBALS['Nav'], ' |');
		$GLOBALS['SearchQueryTemplate'] = $query;
		$GLOBALS['SortField'] = $sortField;
		$GLOBALS['SortOrder'] = $sortOrder;

		BuildAdminSortingLinks($sortLinks, "index.php?ToDo=viewEbay&amp;currentTab=1&amp;".$searchURL."&amp;page=".$page, $sortField, $sortOrder);

		// Workout the maximum size of the array
		$max = $start + ISC_EBAY_TEMPLATE_PER_PAGE;

		if ($max > count($templateResult)) {
			$max = count($templateResult);
		}

		if($numTemplate > 0) {
			// Display the listing template
			while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($templateResult))
			{

				$GLOBALS['Name'] = isc_html_escape($row['name']);
				$GLOBALS['Date'] = CDate($row['date_added']);
				$GLOBALS['Id'] = $row['id'];

				// edit the template's status
				if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Ebay_Selling)) {
					if ($row['enabled'] == 1) {
						$GLOBALS['Enabled'] = sprintf("<a title='%s' href='index.php?ToDo=editEbayTemplateStatus&amp;templateId=%d&amp;enabled=0'><img border='0' src='images/tick.gif'></a>", GetLang('ClickToDisableTemplate'), $row['id']);
					} else {
						$GLOBALS['Enabled'] = sprintf("<a title='%s' href='index.php?ToDo=editEbayTemplateStatus&amp;templateId=%d&amp;enabled=1'><img border='0' src='images/cross.gif'></a>", GetLang('ClickToEnableTemplate'), $row['id']);
					}
				}

				// Workout the edit link -- do they have permission to do so?
				if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Ebay_Selling)) {
					$GLOBALS['EditTemplateLink'] = sprintf("<a title='%s' class='Action' href='index.php?ToDo=editEbayTemplate&amp;templateId=%d'>%s</a>", GetLang('EbayTemplateEdit'), $row['id'], GetLang('Edit'));
				}

				$GLOBALS['EbayTemplateGrid'] .= $this->template->render('ebay.template.manage.row.tpl');
			}

			return $this->template->render('ebay.template.manage.grid.tpl');
		}
		return '';
	}

	/**
	 * This function edit the template status to enable or disable
	 */
	private function editEbayTemplateStatus()
	{
		// Update the status of ebay template
		$templateId = (int)$_GET['templateId'];
		$enabled = (int)$_GET['enabled'];

		$updatedTemplate = array(
			"enabled" => $enabled
		);
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery("ebay_listing_template", $updatedTemplate, "id='".$GLOBALS['ISC_CLASS_DB']->Quote($templateId)."'");

		if ($GLOBALS["ISC_CLASS_DB"]->Error() == "") {
			if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Ebay_Selling)) {
				FlashMessage(GetLang('TemplateStatusChangedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewEbay&currentTab=1', 'EbayListingTemplate');
			}
		} else {
			$err = '';
			if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Ebay_Selling)) {
				FlashMessage(sprintf(GetLang('ErrTemplateStatusNotChanged'), $err), MSG_ERROR, 'index.php?ToDo=viewEbay&currentTab=1', 'EbayListingTemplate');
			}
		}
	}

	/**
	 * This function generates and executes the query for template listing page
	 *
	 * @param string $Query The search string for template name and description
	 * @param int $Start The start index of the query
	 * @param string $SortField The sort field of the query
	 * @param string $SortOrder The sort order of the query
	 * @param int $NumResults The total number of the query result
	 * @return Mixed Returns false if the query is empty or if there is no result. Otherwise returns the result of the query.
	 */
	private function _GetEbayTemplateList(&$Query, $Start, $SortField, $SortOrder, &$NumResults)
	{
		// Return an array containing details about news.
		// Takes into account search too.

		// PostgreSQL is case sensitive for likes, so all matches are done in lower case
		$Query = trim($Query);

		$query = "SELECT * FROM [|PREFIX|]ebay_listing_template elt";
		$countQuery = "SELECT COUNT(id) FROM [|PREFIX|]ebay_listing_template elt";

		$queryWhere = '';
		if($Query != '') {
			$queryWhere .= " WHERE name LIKE '%".$GLOBALS['ISC_CLASS_DB']->Quote($Query)."%'";
		}

		// Add any conditions on to the query
		$query .= $queryWhere;
		$countQuery .= $queryWhere;

		if ($SortField != '') {
			$query .= " ORDER BY ".$SortField." ".$SortOrder;
		}

		$result = $GLOBALS['ISC_CLASS_DB']->Query($countQuery);
		$NumResults = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

		// Add the limit
		$query .= $GLOBALS["ISC_CLASS_DB"]->AddLimit($Start, ISC_EBAY_TEMPLATE_PER_PAGE);
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		return $result;
	}

	/**
	 * This function generates and executes the query for ebay live listing page
	 *
	 * @param array $Queries The search option of listing type, status and search string for eBay listing page
	 * @param int $Start The start index of the query
	 * @param string $SortField The sort field of the query
	 * @param string $SortOrder The sort order of the query
	 * @param int $NumResults The total number of the query result
	 * @return Mixed Returns false if the query is empty or if there is no result. Otherwise returns the result of the query.
	 */
	private function _GetEbayListingList(&$Queries, $Start, $SortField, $SortOrder, &$NumResults)
	{
		// Return an array containing details about news.
		// Takes into account search too.

		$query = "SELECT ei.*,
			(
				SELECT IF(COUNT(orderprodid)>1,'Multiple',orderorderid)
				FROM [|PREFIX|]order_products op
				WHERE op.ebay_item_id = ei.ebay_item_id
			) AS order_no
			FROM [|PREFIX|]ebay_items ei";
		$countQuery = "SELECT COUNT(id) FROM [|PREFIX|]ebay_items ei";

		$queryWhere = '';
		if(!empty($Queries)) {
			$conditions = array();
			if (!empty ($Queries['searchQueryListing'])) {
				$conditions[] = " ei.title LIKE '%".$GLOBALS['ISC_CLASS_DB']->Quote($Queries['searchQueryListing'])."%' ";
			}
			if (!empty ($Queries['listingType'])) {
				$conditions[] = " ei.listing_type = '".$GLOBALS['ISC_CLASS_DB']->Quote($Queries['listingType'])."' ";
			}
			if (!empty ($Queries['listingStatus'])) {
				$conditions[] = " ei.listing_status = '".$GLOBALS['ISC_CLASS_DB']->Quote($Queries['listingStatus'])."' ";
			}
			if (!empty ($conditions)) {
				$queryWhere .= ' WHERE ' . implode(' AND ', $conditions);
			}
		}

		// Add any conditions on to the query
		$query .= $queryWhere;
		$countQuery .= $queryWhere;

		if ($SortField != '') {
			$query .= " ORDER BY ".$SortField." ".$SortOrder;
		}

		$result = $GLOBALS['ISC_CLASS_DB']->Query($countQuery);
		$NumResults = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

		// Add the limit
		$query .= $GLOBALS["ISC_CLASS_DB"]->AddLimit($Start, ISC_EBAY_LISTING_PER_PAGE);
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		return $result;
	}

	/**
	 * Get listing duration of eBay listing depends on the listing type
	 *
	 * @param integer $listingType The type of ebay listing, defaulted to chinese auction type
	 * @param integer $selectedOption The existing selected option. If none specified, it's defaulted to default listing duration
	 * @return string A set of available options of listing duration
	 */
	public function GetListingDurationAsOpt($listingType=self::CHINESE_AUCTION_LISTING, $selectedOption=self::DEFAULT_LISTING_DURATION)
	{

		// variables init
		$options = '';

		// Fixed Price Listing
		$availableDurations = array('Days_3','Days_5','Days_7','Days_10','Days_30', 'GTC');

		// Chinese Auction Listing
		if ($listingType == self::CHINESE_AUCTION_LISTING) {
			$availableDurations = array('Days_1','Days_3','Days_5','Days_7','Days_10');
		}

		// Setting up the option for listing duration
		foreach ($availableDurations as $duration) {
			$sel = '';
			if($selectedOption == $duration) {
				$sel = 'selected="selected"';
			}
			$numDuration = explode('_', $duration);
			if (count($numDuration) > 1) {
				$numDuration = $numDuration[1];
				$daysDisplay = sprintf(GetLang('ListingDurationDays'), $numDuration);
			} else {
				$daysDisplay = GetLang('GTC');
			}
			$options .= sprintf("<option %s value='%s'>%s</option>\n", $sel, $duration, $daysDisplay);
		}

		// return the options
		return $options;
	}

	/**
	 * Convert an array to html options
	 *
	 * @param array $availableOptions The source array to be converted
	 * @param mixed $selectedOption Selected option
	 * @param boolean $useLangVars Determine if we would like to use the language file as the text for the option
	 * @return string Return converted options from the params
	 */
	public function GetArrayAsOptions($availableOptions, $selectedOption='', $useLangVars = true)
	{

		// variable init
		$options = '';

		// Converting the array to html option selection
		foreach($availableOptions as $option => $optionText) {
			$sel = '';
			$text = $optionText;
			if($selectedOption == $option) {
				$sel = 'selected="selected"';
			}
			if ($useLangVars) {
				$text = GetLang($optionText);
			}
			$options .= sprintf("<option %s value='%s'>%s</option>\n", $sel, $option, $text);
		}

		// return the html option
		return $options;
	}

	/**
	 * Get a list of all supported site id and merge to the country currencies that the user setup.
	 *
	 * @return array The list of available ebay countries site that are supported.
	 */
	public function getSupportedSites()
	{
		// variables init
		$supportedSites = array();

		// Get the available and active currencies supported in store
		$query = "SELECT * FROM [|PREFIX|]currencies "
		. "WHERE currencystatus = '1' ";
		$storeCurrencies = array();
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$storeCurrencies[] = $row['currencycode'];
		}

		// Intersect the store currency and supported ebay site
		foreach($this->availableSites as $site) {
			if (in_array(GetLang($site . 'Currency'), $storeCurrencies)) {
				$siteId = GetLang($site . 'Id');
				$siteName = GetLang($site);
				$supportedSites[$siteId] = $siteName;
			}
		}
		return $supportedSites;
	}


	/**
	* Gets the site code for a specific eBay site
	*
	* @param int $siteId The eBay site Id
	* @return string The eBay site code
	*/
	public function getSiteCodeFromSiteId($siteId)
	{
		foreach($this->availableSites as $site) {
			$siteCode = GetLang($site . 'Code');
			$siteCodeSiteId = GetLang($site . 'Id');

			if ($siteCodeSiteId == $siteId) {
				return $siteCode;
			}
		}

		return false;
	}

	/**
	* Gets the currency Id for the associated eBay site
	*
	* @param int The eBay site Id
	* @return int The currency Id
	*/
	public function getCurrencyFromSiteId($siteId)
	{
		// Get the available and active currencies supported in store
		$query = "SELECT * FROM [|PREFIX|]currencies "
		. "WHERE currencystatus = '1' ";
		$storeCurrencies = array();
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$storeCurrencies[$row['currencyid']] = $row['currencycode'];
		}

		foreach($this->availableSites as $site) {
			$currencyCode = GetLang($site . 'Currency');
			if (in_array($currencyCode, $storeCurrencies)) {
				$currencySiteId = GetLang($site . 'Id');

				if ($currencySiteId == $siteId) {
					return array_search($currencyCode, $storeCurrencies);
				}
			}
		}

		return false;
	}

	/**
	 * Check if the eBay mandatory configuration has been setup
	 *
	 * @param string $configName The name of the config that we would like to verify
	 * @return boolean Return true if the specified config exist. Otherwise, return false.
	 */
	public static function checkEbayConfig($configName = '', $includeValidCheck = true)
	{

		// variable init
		$validEbayConfigs = array(
			'EbayDevId',
			'EbayAppId',
			'EbayCertId',
			'EbayUserToken',
		);

		if ($includeValidCheck) {
			$validEbayConfigs[] = 'EbaySettingsValid';
		}

		// if we don't specify any config, we will check if all the config fields in above array are exist
		if ($configName === '') {
			foreach ($validEbayConfigs as $keyConfig) {
				if (!GetConfig($keyConfig)) {
					return false;
				}
			}
			return true;
			// we validate the data against eBay
		}
		// Otherwise, check the existance of the config field from the param
		return (GetConfig($configName) && in_array($configName, $validEbayConfigs));
	}

	/**
	 * Read the result of the request cache that return from eBay
	 *
	 * @param int $siteId The Id of the site to read the cache for
	 * @return string Return the content of the specified file
	 */
	public function ReadCache($siteId)
	{
		$name = $this->cacheBaseDir . '/ebaydetails_' . $siteId . '.xml';
		return $this->fileHandler->readFromFile($name);
	}

	/**
	 * Write the result of the request cache that return from eBay
	 *
	 * @param int $site The Id of the site to write the cache for
	 * @return boolean Return true if we saved the content successfully. Otherwise, return false
	 */
	public function WriteCache($siteId, $content)
	{
		$name = $this->cacheBaseDir . '/ebaydetails_' . $siteId . '.xml';

		if (!is_dir($this->cacheBaseDir)) {
			if (!isc_mkdir($this->cacheBaseDir)) {
				return false;
			}
		}

		if (!$this->fileHandler->CheckDirWritable($this->cacheBaseDir)) {
			isc_chmod($this->cacheBaseDir, ISC_WRITEABLE_DIR_PERM);
		}

		// Create a file if it's not exist
		if (!is_file($name)) {
			touch($name);
		}
		return $this->fileHandler->WriteToFile($content, $name);
	}

	/**
	 * Display a blank page when we updating the eBay cache
	 */
	public function preCacheUpdate()
	{
		$this->template->assign('updateCache', true);
	}

	/**
	* Gets the site Id's whose cache has expired
	*
	* @return array Array of site Id's
	*/
	public function getExpiredCacheSites()
	{
		$keystore = Interspire_KeyStore::instance();

		$prefix = 'ebay:details:last_update:site:';

		$expiredSites = array();

		foreach ($this->getSupportedSites() as $siteId => $siteName) {
			// the time the cache for the site was last updated
			$lastUpdate = $keystore->get($prefix . $siteId);

			$cacheFile = $this->cacheBaseDir . '/ebaydetails_' . $siteId . '.xml';

			// if the cache has never been updated, has expired or the cache file doesn't exist then add it to our list of expired sites
			if (!$lastUpdate ||
				(($lastUpdate + self::CACHE_VALID_FOR) < time()) ||
				!is_file($cacheFile)
				) {
				$expiredSites[] = $siteId;
			}
		}

		return $expiredSites;
	}

	/**
	 * Check if the cache are up to date
	 *
	 * @return boolean Return true if the caches are uptodate. Otherwise, return false
	 */
	public function cacheUpToDate()
	{
		$expiredSites = $this->getExpiredCacheSites();

		return empty($expiredSites);
	}

	/**
	 * Function to get eBay details, the results contain shipping, payment, and others details
	 * @return SimpleXMLElement The eBay Details object
	 */
	public function GeteBayDetailsAction($siteId = 0)
	{
		//@ToDo refactor the cache system to use the operations class directly
		ISC_ADMIN_EBAY_OPERATIONS::setSiteId($siteId);
		return ISC_ADMIN_EBAY_OPERATIONS::geteBayDetails();
	}

	/**
	 * This function convert the object from eBay API to Array
	 *
	 * @param string $key The key in the object we would like to save it as array key
	 * @param string $value The value in the object we would like to save it as array value
	 * @param string $object The source object we would like to convert it from
	 * @return array Return the converted array
	 */
	public function convertEbayObjectToArray($key, $value, $object)
	{
		$convertedArray = array();
		foreach ($object as $eachObject) {
			$eachObject = get_object_vars($eachObject);
			$convertedArray[$eachObject[$key]] = $eachObject[$value];
		}
		return $convertedArray;
	}

	/**
	 * This function convert the shippings details to the format where we could display if to the template
	 *
	 * @param SimpleXMLElement $servicesObject The shipping object to extract services from
	 * @return array Return an array of shipping details
	 */
	public function getShippingAsOptions($servicesObject)
	{
		$services = array();
		$domesticServices = array();
		$domesticServiceTypes = array();
		$internationalServices = array();
		$internationalServiceTypes = array();

		$supportedServiceTypes = array('Flat', 'Calculated');

		foreach ($servicesObject as $service) {
			$service = get_object_vars($service);

			// service is no longer valid? exclude
			if (empty($service['ValidForSellingFlow'])) {
				continue;
			}

			// the service code. eg USPSGlobalExpress
			$serviceCode = $service['ShippingService'];

			// the shipping carier for the service
			$carrier = 'Other';
			if (isset($service['ShippingCarrier'])) {
				$carrier = $service['ShippingCarrier'];
			}

			$newService = array(
				'id' 		=> $service['ShippingServiceID'], // numerical ID of the service
				'service'	=> $serviceCode,
				'name'		=> $service['Description'],
				'carrier'	=> $carrier,
			);

			// The service types this service is valid for (Flat, Calculated, Freight etc)
			$serviceTypes = $service['ServiceType'];
			if (!is_array($serviceTypes)) {
				$serviceTypes = array($serviceTypes);
			}

			// The packages that are valid for this service
			$packages = array();
			if (!empty($service['ShippingPackage'])) {
				$packages = $service['ShippingPackage'];
			}

			$dimensionsRequired = !empty($service['DimensionsRequired']);
			$weightRequired = !empty($service['WeightRequired']);
			$expeditedService = !empty($service['ExpeditedService']);

			// Add the service into our appropriate arrays
			foreach ($serviceTypes as $serviceType) {
				// skip any unspported types
				if (!in_array($serviceType, $supportedServiceTypes)) {
					continue;
				}

				// for Calculated type we should build a list of classes that are used in the select options
				$classes = array();
				if ($serviceType == 'Calculated') {
					$classes += $packages;
				}

				if ($expeditedService) {
					$classes[] = 'ExpeditedService';
				}

				if (!empty($classes)) {
					$classString = implode(' ', $classes);
					$newService['class'] = $classString;
				}

				// is this an international or domestic service?
				if (!empty($service['InternationalService'])) {
					$internationalServices[$serviceType][$carrier][$serviceCode] = $newService;

					$internationalServiceTypes[$serviceType] = GetLang('ServiceType' . $serviceType);
				}
				else {
					$domesticServices[$serviceType][$carrier][$serviceCode] = $newService;

					$domesticServiceTypes[$serviceType] = GetLang('ServiceType' . $serviceType);
				}
			}
		}

		$services = array(
			'Domestic' => array(
				'Services' 		=> $domesticServices,
				'ServiceTypes'	=> $domesticServiceTypes,
			),
			'International'	=> array(
				'Services' 		=> $internationalServices,
				'ServiceTypes'	=> $internationalServiceTypes,
			),
		);

		return $services;
	}

	/**
	 * This function restore the value of price options on the template
	 *
	 * @param array $priceData Array of prices details
	 * @param string $priceType the price type, it could be Reserve, Buy or Starting price
	 * @param string $listingMethod name of the listing method
	 */
	public function restorePriceValue($priceData, $priceType, $listingMethod)
	{
		$prefix = $listingMethod . $priceType . 'PriceOption';
		if (!empty ($priceData[$priceType])) {
			$selectedType = $priceData[$priceType]['selected_type'];
			if ($priceData[$priceType]['price'] > 0) {
				$GLOBALS[$prefix]['Price'] = $priceData[$priceType]['price'];
			}
			if ($priceData[$priceType]['calculate_price'] > 0) {
				$GLOBALS[$prefix]['CalculatePrice'] = $priceData[$priceType]['calculate_price'];
			}
			$GLOBALS[$prefix]['YesPrice'] = 'checked';
			$GLOBALS[$prefix][$selectedType] = 'checked';
			$GLOBALS[$prefix]['Operators'] = $this->GetArrayAsOptions($this->priceOperators, $priceData[$priceType]['calculate_operator']);
			$GLOBALS[$prefix]['Options'] = $this->GetArrayAsOptions($this->priceOptions, $priceData[$priceType]['calculate_option']);
		} else {
			$GLOBALS[$prefix]['ShowPriceDetails'] = 'display:none;';
		}
	}

	/**
	 * This function get all the available eBay live listing for the user and return a string of the managing template html.
	 * @return string Return the html of the eBay live listing page
	 */
	public function ManageEbayLiveListingGrid()
	{
		// Show a list of ebay item in a table
		$page = 0;
		$start = 0;
		$numListing = 0;
		$numPages = 0;
		$GLOBALS['EbayListingGrid'] = "";
		$GLOBALS['Nav'] = "";
		$max = 0;
		$searchURL = '';

		if (isset($_GET['searchQueryListing']) && isset($_GET['listingType']) && isset($_GET['listingStatus'])) {
			$GLOBALS['ListingQuery'] = $query['searchQueryListing'] = $_GET['searchQueryListing'];
			$GLOBALS['ListingType'] = $query['listingType'] = $_GET['listingType'];
			$GLOBALS['ListingStatus'] = $query['listingStatus'] = $_GET['listingStatus'];
			$searchURL = '&amp;searchQueryListing='.$query;
			foreach ($query as $k => $v) {
				$searchURL .= "&amp;$k=$v";
			}
		} else {
			$query = "";
			$GLOBALS['Query'] = "";
		}

		if (isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'asc') {
			$sortOrder = 'asc';
		} else {
			$sortOrder = "desc";
		}

		$sortLinks = array(
			"Item" => "ei.title",
			"DateListed" => "ei.datetime_listed",
			"Type" => "ei.listing_type",
			"Status" => "ei.listing_status",
			"BidCount" => "ei.bid_count",
			"QuantityRemaining" => "ei.quantity_remaining",
			"CurrentPrice" => "ei.current_price",
			"BinPrice" => "ei.buyitnow_price",
			"OrderNumber" => "order_no",
		);


		if (isset($_GET['sortField']) && in_array($_GET['sortField'], $sortLinks)) {
			$sortField = $_GET['sortField'];
			SaveDefaultSortField("ManageEbayListing", $_REQUEST['sortField'], $sortOrder);
		}
		else {
			$sortField = "ei.datetime_listed";
			list($sortField, $sortOrder) = GetDefaultSortField("ManageEbayListing", $sortField, $sortOrder);
		}

		if (isset($_GET['page'])) {
			$page = (int)$_GET['page'];
		} else {
			$page = 1;
		}

		$sortURL = sprintf("&sortField=%s&sortOrder=%s", $sortField, $sortOrder);
		$GLOBALS['SortURL'] = $sortURL;

		// Limit the number of questions returned
		if ($page == 1) {
			$start = 1;
		} else {
			$start = ($page * ISC_EBAY_LISTING_PER_PAGE) - (ISC_EBAY_LISTING_PER_PAGE-1);
		}

		$start = $start-1;

		// Get the results for the query
		$listingResult = $this->_GetEbayListingList($query, $start, $sortField, $sortOrder, $numListing);
		$numPages = ceil($numListing / ISC_EBAY_LISTING_PER_PAGE);

		// Add the "(Page x of n)" label
		if($numListing > ISC_EBAY_LISTING_PER_PAGE) {
			$GLOBALS['Nav'] = sprintf("(%s %d of %d) &nbsp;&nbsp;&nbsp;", GetLang('Page'), $page, $numPages);
			$GLOBALS['Nav'] .= BuildPagination($numListing, ISC_EBAY_LISTING_PER_PAGE, $page, sprintf("index.php?ToDo=viewEbay&currentTab=0%s", $sortURL));
		}
		else {
			$GLOBALS['Nav'] = "";
		}

		$GLOBALS['Nav'] = rtrim($GLOBALS['Nav'], ' |');
		$GLOBALS['SearchQueryListing'] = $query;
		$GLOBALS['SortField'] = $sortField;
		$GLOBALS['SortOrder'] = $sortOrder;

		BuildAdminSortingLinks($sortLinks, "index.php?ToDo=viewEbay&amp;currentTab=0&amp;".$searchURL."&amp;page=".$page, $sortField, $sortOrder);

		// Workout the maximum size of the array
		$max = $start + ISC_EBAY_LISTING_PER_PAGE;

		if ($max > count($listingResult)) {
			$max = count($listingResult);
		}
		if($numListing > 0) {
			$GLOBALS['ManageEbayLiveListingIntro'] = sprintf(GetLang('ManageEbayLiveListingIntro'), $numListing);

			// Display the live listing
			while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($listingResult))
			{
				$GLOBALS['Item'] = isc_html_escape($row['title']);
				if (trim($row['ebay_item_link'])) {
					$GLOBALS['Item'] = '<a target="_blank" href="'.$row['ebay_item_link'].'">' .$GLOBALS['Item']. '</a>';
				}
				$GLOBALS['DateListed'] = CDate($row['datetime_listed']);
				$GLOBALS['Type'] = GetLang(isc_html_escape($row['listing_type']));
				$GLOBALS['Status'] = GetLang(isc_html_escape(ucfirst($row['listing_status'])));
				$GLOBALS['BidCount'] = GetLang('NA');
				if (!empty ($row['bid_count'])) {
					$GLOBALS['BidCount'] = $row['bid_count'];
				}
				$GLOBALS['QuantityRemaining'] = GetLang('NA');
				if (!empty ($row['quantity_remaining'])) {
					$GLOBALS['QuantityRemaining'] = $row['quantity_remaining'];
				}
				$currentPriceCurrency = GetCurrencyByCode($row['current_price_currency']);
				$GLOBALS['CurrentPrice'] = FormatPriceInCurrency($row['current_price'], $currentPriceCurrency['currencyid']);
				$binPriceCurrency = GetCurrencyByCode($row['buyitnow_price_currency']);
				$GLOBALS['BinPrice'] = FormatPriceInCurrency($row['buyitnow_price'], $binPriceCurrency['currencyid']);
				$GLOBALS['OrderNumber'] = $row['order_no'];
				if ($row['order_no'] == '') {
					$GLOBALS['OrderNumber'] = '';
				}
				$GLOBALS['EbayItemId'] = $row['ebay_item_id'];
				if ($row['listing_type'] == 'FixedPriceItem') {
					$GLOBALS['BinPrice'] = $GLOBALS['CurrentPrice'];
					$GLOBALS['CurrentPrice'] = GetLang('NA');
				}

				$GLOBALS['EbayListingGrid'] .= $this->template->render('ebay.listing.manage.row.tpl');
			}

			return $this->template->render('ebay.listing.manage.grid.tpl');
		}
		$GLOBALS['ShowListingOptions'] = 'display:none;';
		return '';
	}

	/**
	 * This function send command to end item listing on eBay and remove the reference data locally
	 *
	 * @param array $items An array of item containing item data id and reason of ending
	 */
	public static function endEbayListing($items)
	{
		$results = array();
		try {
			$endListingResultXML = ISC_ADMIN_EBAY_OPERATIONS::endItems($items);
			foreach ($endListingResultXML->EndItemResponseContainer as $item) {
				$results[] = new ISC_ADMIN_EBAY_END_ITEM_RESULT($item);
			}
			return $results;
		} catch (ISC_EBAY_API_REQUEST_EXCEPTION $e) {
			return false;
		}
	}

	/**
	 * This function is to delete local reference of ebay listing.
	 *
	 * @param mixed $itemIds The eBay item id, this could be int or array
	 * @return boolean Return true if it's deleted. Otherwise, return false
	 */
	public static function deleteEbayListingRefByItemId($itemIds)
	{
		if (!is_array($itemIds)) {
			$itemIds = array($itemIds);
		}
		$query = "DELETE FROM [|PREFIX|]ebay_items "
		. "WHERE ebay_item_id IN ('" . implode("','", $itemIds) . "') "
		;
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		if ($result) {
			return true;
		}
		return false;
	}

	/**
	 * This function is to get local reference of ebay listing.
	 *
	 * @param mixed $itemIds The eBay item id, this could be int or array
	 * @return array Return an array of item data
	 */
	public static function getEbayListingRefByItemId($itemIds)
	{
		$itemData = array();
		if (!is_array($itemIds)) {
			$itemIds = array($itemIds);
		}
		$query = "SELECT * FROM [|PREFIX|]ebay_items "
		. "WHERE ebay_item_id IN (" . implode(",", $itemIds) . ") "
		;
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		while( $row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result) ) {
			$itemData[] = $row;
		}
		return $itemData;
	}

	/**
	 * This function remove the local record(s) of eBay listing.
	 */
	private function deleteLocalEbayListing()
	{
		if (!empty ($_REQUEST['listings'])) {
			$itemIds = $_REQUEST['listings'];
			if (!is_array($itemIds)) {
				$itemIds = array($itemIds);
			}
			if( $this->deleteEbayListingRefByItemId($itemIds) ) {
				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($itemIds));
				FlashMessage(GetLang('LocalListingDeletedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewEbay&currentTab=0', 'EbayLiveListing');
			} else {
				FlashMessage(GetLang('UnknownErrorDeletion'), MSG_ERROR, 'index.php?ToDo=viewEbay&currentTab=0', 'EbayLiveListing');
			}
		} else {
			FlashMessage(GetLang('InvalidDataSupplied'), MSG_ERROR, 'index.php?ToDo=viewEbay&currentTab=0', 'EbayLiveListing');
		}
	}

	/**
	 * This function check/verify if the item id, is valid eBay item id in the store.
	 * @param int $itemId The eBay item ID
	 * @return boolean Return true if the item id exist. Otherwise, return false
	 */
	public static function validEbayItemId($itemId)
	{
		$query = "SELECT * FROM [|PREFIX|]ebay_items "
		. "WHERE ebay_item_id = '$itemId'"
		;
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if ($GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			return true;
		}
		return false;
	}

	/**
	 * Determine if production site is allowed
	 * @return boolean Return true if production site is allowed. Otherwise, Return false.
	 */
	private function isProductionSiteAllowed()
	{
		if (GetConfig('HostingId')) {
			if (GetConfig('AvailableFeatures') & FEATURE_AVAILABLE_LIVE_EBAY_LISTING) {
				return true;
			} else {
				return false;
			}
		}
		return true;
	}

	/**
	* Updates the notification delivery URL for eBay
	*
	* @return bool TRUE if updated successfully, FALSE otherwise
	*/
	public static function resubscribeNotifications()
	{
		try {
			ISC_ADMIN_EBAY_OPERATIONS::setApplicationNotificationsEnabled(true);
		}
		catch (ISC_EBAY_API_EXCEPTION $ex) {
			return false;
		}

		return true;
	}
}

/**
 * Class used by to store results returned by calling EndItem to end item on eBay
 */
class ISC_ADMIN_EBAY_END_ITEM_RESULT {

	/**
	 * @var int Item Id of eBay listing
	 */
	private $_itemID;

	/**
	 * @var boolean Indicates if the request valid or it's
	 */
	private $_isValid;

	/**
	 * @var array An array contains lists of error details
	 */
	private $_errors;


	/**
	* @param int The associated product ID for the item
	* @param SimpleXMLElement $xml
	* @return ISC_ADMIN_EBAY_LIST_ITEM_RESULT
	*/
	public function __construct($xml)
	{
		$this->_isValid = true;
		if (isset($xml->Errors)) {
			foreach ($xml->Errors as $error) {
				if ((string)$error->SeverityCode == 'Error') {
					$this->_isValid = false;
				}
				$this->setError((string)$error->ShortMessage . ' (' . (string)$error->ErrorCode . ')');
			}
		}
		$this->_itemID = (string)$xml->CorrelationID;
		if (!$this->_isValid) {
			return;
		}
	}

	/**
	* Sets an error
	*
	* @param strin $error The error message to store
	*/
	private function setError($error)
	{
		$this->_errors[] = $error;
	}

	/**
	* Checks if this result has errors
	*
	* @return bool True if the result has errors, false otherwise
	*/
	public function hasErrors()
	{
		return !empty($this->_errors);
	}

	/**
	* Gets the array of errors
	*
	* @return array The errors array
	*/
	public function getErrors()
	{
		return $this->_errors;
	}

	/**
	* Gets the the item id
	*
	* @return int The item id
	*/
	public function getItemId()
	{
		return $this->_itemID;
	}

	/**
	 * Check if the result is valid
	 *
	 * @return boolean Return true if the request was sucess. Otherwise, return false.
	 */
	public function isValid()
	{
		return $this->_isValid;
	}
}
