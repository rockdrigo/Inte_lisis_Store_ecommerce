<?php
	class CHECKOUT_GOOGLECHECKOUT extends ISC_CHECKOUT_PROVIDER
	{
		/**
		* @var object The google cart object
		*/
		public $cart = null;

		/**
		 * @var boolean Disable the checkout links/buttons everywhere except on the main cart page
		 */
		public $disableNonCartCheckoutButtons = true;

		/**
		 * @var bolean Google checkout button variant, true = enabled button, false = disabled button
		 */
		public $buttonVariant = true;

		/**
		* @var boolean Don't show google checkout on the confirm page
		*/
		public $showOnConfirmPage = false;

		// Only USD and GBP default currencies are supported by Google
		public $supportedCurrencies = array('USD', 'GBP');

		/**
		 * The url to the xml processing api php file
		 *
		 * @var string
		 **/
		public $xmlUrl = '';

		private $defaultZoneGFilter = null;

		/**
		 * Checkout class constructor. Does the setup of some member variables.
		 *
		 * @return void
		 */
		public function __construct()
		{
			// Setup the required variables for the PayPal checkout module
			parent::__construct();

			$this->xmlUrl = $GLOBALS['ShopPathSSL'].'/modules/checkout/googlecheckout/xml.php';

			$this->_name = GetLang('GoogleCheckoutName');
			$this->_image = "google_checkout.gif";
			$this->_description = GetLang('GoogleCheckoutDesc');
			$this->_help = sprintf(GetLang('GoogleCheckoutHelp'), $this->xmlUrl);
			$this->_height = 0;

			if ($this->GetValue('testmode') === 'YES') {
				$this->_server_type = 'sandbox';
			} else {
				$this->_server_type = 'production';
			}

			if (GetConfig('TaxTypeSelected') == 1 && GetConfig('PricesIncludeTax') == 1) {
				$this->_help .= MessageBox(GetLang('GoogleCheckoutTaxWarning'), MSG_ERROR);
			}

			require_once(dirname(__FILE__).'/library/googlerequest.php');
			$this->request = new GoogleRequest($this->GetValue('merchantid'), $this->GetValue('merchanttoken'), $this->_server_type, $this->GetDefaultCurrencyCode());

			include_once(dirname(__FILE__).'/library/googleshipping.php');
			$this->defaultZoneGFilter = new GoogleShippingFilters();
			$this->defaultZoneGFilter->SetAllowedWorldArea(true);
		}

		/*
		 * Check if this checkout module can be enabled or not.
		 *
		 * @return boolean True if this module is supported on this install, false if not.
		 */
		public function IsSupported()
		{
			if (!in_array($this->GetDefaultCurrencyCode(), $this->supportedCurrencies)) {
				$this->SetError(GetLang('GoogleCheckoutSupportedCurrenciesError'));
			}

			// Return true if there are no errors
			if(!$this->HasErrors()) {
				return true;
			}
			else {
				return false;
			}
		}

		/**
		 * Get the currency code of the default currency for the store
		 *
		 * @return void
		 **/
		public function GetDefaultCurrencyCode()
		{
			static $code = '';

			if ($code != '') {
				return $code;
			}

			$defaultCurrency = GetDefaultCurrency();
			$code = $defaultCurrency['currencycode'];
			return $code;
		}

		/**
		 * Setup the module specific variables to show on the settings for this module
		 *
		 * @return void
		 **/
		public function SetCustomVars()
		{
			$this->_variables['merchantid'] = array (
				"name" => GetLang('GoogleCheckoutMerchantId'),
				"type" => "textbox",
				"help" => GetLang('GoogleCheckoutMerchantIdHelp'),
				"default" => "",
				"required" => true
			);

			$this->_variables['merchanttoken'] = array (
				"name" => GetLang('GoogleCheckoutMerchantToken'),
				"type" => "textbox",
				"help" => GetLang('GoogleCheckoutMerchantTokenHelp'),
				"default" => "",
				"required" => true
			);

			$this->_variables['testmode'] = array (
				"name" => GetLang('GoogleCheckoutTestMode'),
				"type" => "dropdown",
				"help" => GetLang("GoogleCheckoutTestModeHelp"),
				"default" => "NO",
				"required" => true,
				"options" => array(
					GetLang("GoogleCheckoutTestModeYes") => "YES",
					GetLang("GoogleCheckoutTestModeNo") => "NO",
				),
				"multiselect" => false
			);
			$this->_variables['fallbackshippingcost'] = array (
				"name" => GetLang('GoogleCheckoutFallbackShippingCost'),
				"type" => "textbox",
				"help" => GetLang("GoogleCheckoutFallbackShippingCostHelp"),
				"default" => "0",
				"required" => true,
			);

			$this->_variables['disablerealtimeshipping'] = array(
				'name' => GetLang('GoogleCheckoutDisableRealTimeShipping'),
				'type' => 'dropdown',
				'help' => GetLang('GoogleCheckoutDisableRealTimeShippingHelp'),
				'default' => 'NO',
				'required' => true,
				'options' => array(
					GetLang("GoogleCheckoutDisableRealTimeShippingYes") => 1,
					GetLang("GoogleCheckoutDisableRealTimeShippingNo") => 0,
				),
				'multiselect' => false
			);

			$this->_variables['autoapproveprotected'] = array (
				"name" => GetLang('GoogleCheckoutAutoApproveProtected'),
				"type" => "dropdown",
				"help" => GetLang("GoogleCheckoutAutoApproveProtectedHelp"),
				"default" => "NO",
				"required" => true,
				"options" => array(
					GetLang("GoogleCheckoutAutoApproveProtectedYes") => "YES",
					GetLang("GoogleCheckoutAutoApproveProtectedNo") => "NO",
				),
				"multiselect" => false
			);

			$this->_variables['orderchargestatus'] = array(
				'name' => GetLang('GoogleCheckoutOrderStatusOnCharge'),
				'type' => 'dropdown',
				'help' => GetLang('GoogleCheckoutOrderStatusOnChargeHelp'),
				'default' => ORDER_STATUS_AWAITING_FULFILLMENT,
				'required' => true,
				'options' => array(
				),
				'multiselect' => false
			);

			$query = "
				SELECT *
				FROM [|PREFIX|]order_status
				WHERE statusid IN (".ORDER_STATUS_AWAITING_FULFILLMENT.",".ORDER_STATUS_AWAITING_SHIPMENT.",".ORDER_STATUS_COMPLETED.")
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while($status = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$this->_variables['orderchargestatus']['options'][$status['statusdesc']] = $status['statusid'];
			}
		}

		/**
		 * Returns the checkout button for this specific module. Google checkout requires that a
		 * seperate button be used for checking out using them
		 *
		 * @return string The html to show for the button
		 **/
		public function GetCheckoutButton()
		{
			$this->BuildCart();

			$ShowNormalCheckoutButton = false;
			foreach (GetAvailableModules('checkout', true, true) as $module) {
				if (!method_exists($module['object'], 'GetCheckoutButton')) {
					$ShowNormalCheckoutButton = true;
					break;
				}
			}

			if ($ShowNormalCheckoutButton) {
				$GLOBALS['GoogleCheckoutOrUse'] = GetLang('GoogleCheckoutOrUse');
			} else {
				$GLOBALS['GoogleCheckoutOrUse'] = '';
			}

			$GLOBALS['GoogleCheckoutButton'] = $this->cart->CheckoutButtonCode('large', $this->buttonVariant, 'en_US', false, 'white');

			return $this->ParseTemplate('googlecheckout.button', true);
		}

		/**
		 * Build the representation of the shopping cart using the google checkout objects
		 *
		 * @return void
		 **/
		private function BuildCart()
		{
			if ($this->cart === null) {
				include_once(dirname(__FILE__).'/library/googlecart.php');
				include_once(dirname(__FILE__).'/library/googleitem.php');

				$id = trim($this->GetValue('merchantid'));
				$key =trim($this->GetValue('merchanttoken'));

				$currency = $this->GetDefaultCurrencyCode();

				$this->cart = new GoogleCart($id, $key, $this->_server_type, $currency);
				$this->cart->SetMerchantPrivateData($_COOKIE['SHOP_SESSION_TOKEN']);
				$this->cart->SetEditCartUrl(GetConfig('ShopPath').'/cart.php');
				$this->cart->SetContinueShoppingUrl(GetConfig('ShopPath'));
				$this->cart->SetRequestBuyerPhone('true');

				// Add tax rules
				$this->AddTaxInformationToCart();

				// Add the analytics tracking to the cart
				$this->AddAnalyticsToCart();

				// Merchant calculations are only available for US based stores
				if ($this->GetDefaultCurrencyCode() == 'USD') {
					$this->cart->SetMerchantCalculations($this->xmlUrl, "true", "true", "true");
				} else {
					$this->cart->SetMerchantCalculations($this->xmlUrl, "false", "false", "false");
				}

				$coupon_discount = 0;
				$items_total = 0;

				$quote = getCustomerQuote();
				$items = $quote->getItems();

				foreach($items as $item) {
					$description = '';

					if(!$this->canSellItem($item))
					{
						$this->buttonVariant = false;
						return;
					}

					if($item->getVariationId() > 0) {
						$options = $item->getVariationOptions();
						foreach($options as $name => $value) {
							$description .= $name.': '.$value.', ';
						}
						$description = rtrim($description, ', ');
					}

					$googleItem = new GoogleItem(
						$item->getName(),
						$description,
						$item->getQuantity(),
						$item->getPrice(false),
						$item->getWeight()
					);

					if($item->getTaxClassId()) {
						$googleItem->setTaxTableSelector($item->getTaxClassId());
					}

					// If this item is a digital item or gift certificate then mark it as email delivery
					if($item->getType() == PT_DIGITAL || $item->getType() == PT_GIFTCERTIFICATE) {
						$googleItem->setEmailDigitalDelivery(true);
					}

					$this->cart->AddItem($googleItem);

					// does this item have gift wrapping? Add it as a line item
					$giftWrapping = $item->getGiftWrapping();
					if($giftWrapping) {
						$googleItem = new GoogleItem(
							'Gift Wrapping: '.$giftWrapping['wrapname'],
							$giftWrapping['wrapmessage'],
							$item->getQuantity(),
							$item->getWrappingCost()
						);
						$this->cart->AddItem($googleItem);
					}
				}

				// Send across any applied gift certificates from the store
				// Disabled - Gift certificate amounts need to be dynamic to
				// account for changes in tax, shipping and discounts which
				// can all change on the google check out page. This method
				// only allows us to send a static value. Gift certificates
				// must be re-applied on the google checkout page.
				/*
				$giftCertificates = $quote->getAppliedGiftCertificates();
				if(!empty($giftCertificates)) {
					foreach($giftCertificates as $giftCertificate) {
						$giftCertificateName = getLang('GiftCertificate').' ('.$giftCertificate['code'].')';
						$googleItem = new GoogleItem(
							$giftCertificateName, '', 1, $giftCertificate['used'] * -1
						);
						$googleItem->setEmailDigitalDelivery(true);
						$this->cart->addItem($googleItem);
					}
				}*/

				// send across applied coupons
				$coupons = $quote->getAppliedCoupons();
				if(!empty($coupons)) {
					foreach($coupons as $coupon) {
						$googleItem = new Googleitem(
							getLang('Coupon').' ('.$coupon['code'].')', '', 1,
							$coupon['totalDiscount'] * -1
						);

						// free or discounted shipping coupon, add a zero cost item
						// handle shipping rate adjustment via merchant callback later
						if ($coupon['discountType'] == 3 || $coupon['discountType'] == 4) {
							$desc = getLang('GoogleCheckoutDiscountShippingCoupon', $coupon);
							$googleItem = new Googleitem($desc, '', 1, 0);
						}

						$itemData = array(
							'type' => 'coupon',
							'code' => $coupon['code']);

						$googleItem->setMerchantPrivateItemData(json_encode($itemData));
						$this->cart->addItem($googleItem);
					}
				}

				// is there a subtotal discount? (discount rule. eg $X off for orders over $Y)
				if($quote->getDiscountAmount() > 0) {
					$googleItem = new GoogleItem(
						getLang('GoogleCheckoutDiscountOther'), '', 1,
						$quote->getDiscountAmount() * -1
					);
					$googleItem->setEmailDigitalDelivery(true);
					$this->cart->addItem($googleItem);
				}

				if(!$quote->isDigital()) {
					$this->AddShippingInformationToCart();
				}
				else {
					$this->AddDigitalShippingInformationToCart();
				}

				$this->DebugLog($this->cart->GetXML());
			}
		}

		/**
		 * Check if an item can be sold using google checkout.
		 *
		 * @param ISC_QUOTE_ITEM the item
		 * @return boolean true if can be sold, false otherwise.
		 */
		private function canSellItem($item)
		{
			$data = $item->getProductData();

			if(!empty($data['disable_google_checkout']))
				return false;

			return true;
		}

		/**
		 * If the google analytics module is configured, add the tracking code to the checkout xml request
		 *
		 * @return void
		 **/
		private function AddAnalyticsToCart()
		{
			$module = null;
			GetModuleById('analytics', $module, 'analytics_googleanalytics');

			if ($module !== null) {
				$tracking_code = $module->GetValue('trackingcode');
				$account_id = '';

				if (strpos($tracking_code, 'pageTracker')) {
					preg_match('#_getTracker\("([^"]+)"\);#', $tracking_code, $matches);
					if (isset($matches[1])) {
						$account_id = $matches[1];
					}
				} elseif (strpos($tracking_code, 'urchin')) {
					preg_match('#_uacct\s+=\s+"([^"]+)";#', $tracking_code, $matches);
					if (isset($matches[1])) {
						$account_id = $matches[1];
					}
				}

				if ($account_id != '') {
					$this->cart->AddGoogleAnalyticsTracking($account_id);
				}
			}
		}

		/**
		 * Add the taxation information to the google object representation of the customers cart
		 *
		 * @return void
		 **/
		private function AddTaxInformationToCart()
		{
			require_once dirname(__FILE__).'/library/googletax.php';

			// Fetch available tax classes
			$taxClasses = getClass('ISC_TAX')->getTaxClasses();
			foreach($taxClasses as $id => $name) {
				$taxClasses[$id] = array(
					'name' => $name,
					'rules' => array()
				);
			}

			$taxClassIds = array_keys($taxClasses);

			// Fetch available tax zones
			$taxZones = array();
			$query = "
				SELECT id, type, `default`
				FROM [|PREFIX|]tax_zones
				WHERE enabled=1 OR `default`=1
			";
			$result = $GLOBALS['ISC_CLASS_DB']->query($query);
			while($taxZone = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
				$taxClasses[$id]['rules'] = array();

				$shippingTaxRate = getClass('ISC_TAX')->getEffectiveClassRate($taxZone['id'], getConfig('taxShippingTaxClass'));
				$shippingTaxed = 'false';
				if($shippingTaxRate > 0) {
					$shippingTaxed = 'true';
				}

				$defaultTaxRate = getClass('ISC_TAX')->getEffectiveClassRate($taxZone['id'], 0) / 100;
				$defaultTaxRule = new GoogleDefaultTaxRule($defaultTaxRate, $shippingTaxed);

				foreach($taxClassIds as $id) {
					$taxRate = getClass('ISC_TAX')->getEffectiveClassRate($taxZone['id'], $id) / 100;
					$taxClasses[$id]['rules'][$taxZone['id']] = new GoogleAlternateTaxRule($taxRate);
				}

				// Everywhere else tax zone - allow everywhere
				if($taxZone['default']) {
					$defaultTaxRule->setWorldArea(true);
					foreach($taxClassIds as $id) {
						$taxClasses[$id]['rules'][$taxZone['id']]->setWorldArea(true);
					}
				}
				// Location specific zone, so the tax becomes dependant on configured locations
				else {
					$zipPatterns = array();
					$stateCodes = array();
					$query = "
						SELECT *
						FROM [|PREFIX|]tax_zone_locations
						WHERE tax_zone_id='".$taxZone['id']."'
					";
					$locationResult = $GLOBALS['ISC_CLASS_DB']->query($query);
					while($location = $GLOBALS['ISC_CLASS_DB']->fetch($locationResult)) {
						if($location['type'] == 'country') {
							$countryIso = getCountryISO2ById($location['value_id']);
							$defaultTaxRule->addPostalArea($countryIso);
							foreach($taxClassIds as $id) {
								$taxClasses[$id]['rules'][$taxZone['id']]->addPostalArea($countryIso);
							}
						}
						else if($location['type'] == 'zip') {
							$countryIso = getCountryISO2ById($location['country_id']);

							// US zip codes are handled with setZipPatterns below
							if($countryIso == 'US') {
								$zipPatterns[] = $location['value'];
							}
							else {
								$defaultTaxRule->addPostalArea($countryIso, $location['value']);
								foreach($taxClassIds as $id) {
									$taxClasses[$id]['rules'][$taxZone['id']]->addPostalArea($countryIso, $location['value']);
								}
							}
						}
						else if($location['type'] == 'state') {
							$countryIso = getCountryISO2ById($location['country_id']);

							// Google Checkout only supports US based states
							// Ref: http://code.google.com/apis/checkout/developer/Google_Checkout_XML_API_Understanding_Areas.html
							if($countryIso != 'US') {
								continue;
							}

							$stateCodes[] = getStateISO2ById($location['value_id']);
						}
					}
				}

				$this->cart->addDefaultTaxRules($defaultTaxRule);

				// Add in the US zip codes if there are any
				if(!empty($zipPatterns)) {
					$defaultTaxRule->setZipPatterns($zipPatterns);
					foreach($taxClassIds as $id) {
						$taxClasses[$id]['rules'][$taxZone['id']]->setZipPatterns($zipPatterns);
					}
				}

				// Add in US states if there are any
				if(!empty($stateCodes)) {
					$defaultTaxRule->setStateAreas($stateCodes);
					foreach($taxClassIds as $id) {
						$taxClasses[$id]['rules'][$taxZone['id']]->setStateAreas($stateCodes);
					}
				}
			}

			foreach($taxClasses as $id => $taxClass) {
				$table = new GoogleAlternateTaxTable($id);
				foreach($taxClass['rules'] as $rule) {
					$table->addAlternateTaxRules($rule);
				}

				$this->cart->addAlternateTaxTables($table);
			}
		}

		/**
		 * Add the digital only order specific shipping information to the google
		 * object representation of the customers cart.
		 *
		 * @return void
		 **/
		private function AddDigitalShippingInformationToCart()
		{
			$ship = new GoogleFlatRateShipping('DigitalShip', 0);

			$Gfilter = new GoogleShippingFilters();

			$Gfilter->SetAllowedWorldArea(true);

			// Shipping restrictions are used if the merchant callback calculation fails
			$ship->AddShippingRestrictions($Gfilter);

			$this->cart->AddShipping($ship);
		}

		private function AddShippingZoneMethodsToCart($shippingZone, $shippingRestrictions, $fixedShippingCost, $appendZoneName=false)
		{
			// If the zone has no enabled methods
			if (!isset($shippingZone['methods'])) {
				return;
			}

			$quote = GetCustomerQuote();

			foreach ($shippingZone['methods'] as $method) {
				$module = null;
				if (!GetModuleById('shipping', $module, $method['methodmodule'])) {
					continue;
				}

				if ($module === null) {
					continue;
				}

				// Real-time shipping provider and real-time quotes are disabled for Google Checkout so skip it
				if(!$module->_flatrate && $this->getValue('disablerealtimeshipping') == 1) {
					continue;
				}

				$module->SetMethodId($method['methodid']);

				if($module->_flatrate) {
					$shippingAddress = $quote->getShippingAddress();
					$quotes = $shippingAddress->getShippingMethodQuotes($method);

					foreach($quotes as $q) {
						$shippingName = $q['description'];
						if($appendZoneName)
							$shippingName .= ' ('.$shippingZone['zonename'].')';

						$price = $q['price'];

						$ship = new GoogleMerchantCalculatedShipping($shippingName, $price + $fixedShippingCost);

						if ($shippingRestrictions) {
							// Address filters are used when a customer goes to the google checkout page
							$ship->AddAddressFilters($shippingRestrictions);

							// Shipping restrictions are used if the merchant callback calculation fails
							$ship->AddShippingRestrictions($shippingRestrictions);
						}
						$this->cart->AddShipping($ship);
					}
				}
				else
				{
					$deliveryMethods = $module->GetAvailableDeliveryMethods();

					foreach ($deliveryMethods as $deliveryMethod) {
						$shippingName = $deliveryMethod;
						if($appendZoneName)
							$shippingName .= ' ('.$shippingZone['zonename'].')';

						$ship = new GoogleMerchantCalculatedShipping($shippingName, $this->GetValue('fallbackshippingcost') + $fixedShippingCost);

						if ($shippingRestrictions) {
							// Address filters are used when a customer goes to the google checkout page
							$ship->AddAddressFilters($shippingRestrictions);

							// Shipping restrictions are used if the merchant callback calculation fails
							$ship->AddShippingRestrictions($shippingRestrictions);
						}
						$this->cart->AddShipping($ship);
					}
				}
			}
		}

		/**
		 * Add the shipping information to the google object representation of the customers cart.
		 *
		 * @return void
		 **/
		private function AddShippingInformationToCart()
		{
			$quote = getCustomerQuote();
			$noShippingCost = 0;
			$fixedShippingCost = 0;
			$fixedShippingProducts = 0;

			$items = $quote->getItems();
			foreach($items as $item) {
				if($item->getType() != PT_PHYSICAL) {
					continue;
				}

				if($item->hasFreeShipping()) {
					++$noShippingCost;
				}
				else if($item->getFixedShippingCost() > 0) {
					++$fixedShippingProducts;
					$fixedShippingCost += $item->getFixedShippingCost() * $item->getQuantity();
				}
			}

			// Global free shipping options
			$addFreeShipping = false;
			if ($quote->getHasFreeShipping() || $noShippingCost)
			{
				$freeShippingName = GetLang('FreeShipping');
				$addFreeShipping = true;
			}

			// Global fixed shipping on items option
			$addFixedShipping = false;
			if (count($items) == $fixedShippingProducts) {
				$fixedShippingName = GetConfig('StoreName');
				$addFixedShipping = true;
			}

			// Not all the products have a fixed shipping so keep on chugging away
			$shippingZones = GetShippingZoneInfo();

			// Do all the normal zones first (skip the default one)
			// this is so that we can work out where "everywhere else" equates to
			foreach ($shippingZones as $shippingZone) {
				// Skip the default zone for now
				if (!isset($shippingZone['locationtype'])) {
					continue;
				}

				$shippingRestrictions = $this->GetShippingRestrictions($shippingZone);
				$this->AddDefaultShippingRestrictions($shippingZone);

				// Add in the free shipping option if we have it
				if($addFreeShipping || !empty($shippingZone['zonefreeshipping'])) {
					$freeShippingName = GetLang('FreeShipping');
					$ship = new GoogleMerchantCalculatedShipping($freeShippingName . ' ('.$shippingZone['zonename'].')', 0);
					if ($shippingRestrictions !== false) {
						// Address filters are used when a customer goes to the google checkout page
						$ship->AddAddressFilters($shippingRestrictions);

						// Shipping restrictions are used if the merchant callback calculation fails
						$ship->AddShippingRestrictions($shippingRestrictions);
					}

					$ship->AddAddressFilters($shippingRestrictions);
					$this->cart->AddShipping($ship);
				}

				// Add in the fixed shipping option if we have it
				if($addFixedShipping) {
					$ship = new GoogleMerchantCalculatedShipping($fixedShippingName . ' ('.$shippingZone['zonename'].')', $fixedShippingCost);
					if ($shippingRestrictions !== false) {
						// Address filters are used when a customer goes to the google checkout page
						$ship->AddAddressFilters($shippingRestrictions);

						// Shipping restrictions are used if the merchant callback calculation fails
						$ship->AddShippingRestrictions($shippingRestrictions);
					}

					$ship->AddAddressFilters($shippingRestrictions);
					$this->cart->AddShipping($ship);
				}

				$this->AddShippingZoneMethodsToCart($shippingZone, $shippingRestrictions, $fixedShippingCost, true);
			}

			// Now add the methods for the default zone
			foreach ($shippingZones as $shippingZone) {
				// Skip any non-default zones now
				if (isset($shippingZone['locationtype'])) {
					continue;
				}

				// Add free shipping options
				if ($addFreeShipping || !empty($shippingZone['zonefreeshipping']))
				{
					$freeShippingName = GetLang('FreeShipping');
					$ship = new GoogleMerchantCalculatedShipping($freeShippingName, 0);
					$Gfilter = new GoogleShippingFilters();
					$Gfilter->SetAllowedWorldArea(true);
					$ship->AddAddressFilters($Gfilter);

					if ($this->defaultZoneGFilter) {
						// Address filters are used when a customer goes to the google checkout page
						$ship->AddAddressFilters($this->defaultZoneGFilter);

						// Shipping restrictions are used if the merchant callback calculation fails
						$ship->AddShippingRestrictions($this->defaultZoneGFilter);
					}

					$this->cart->AddShipping($ship);
				}

				// Add fixed shipping on items option
				if ($addFixedShipping) {
					$Gfilter = new GoogleShippingFilters();
					$ship = new GoogleMerchantCalculatedShipping($fixedShippingName, $fixedShippingCost);
					$Gfilter->SetAllowedWorldArea(true);
					$ship->AddAddressFilters($Gfilter);

					if ($this->defaultZoneGFilter) {
						// Address filters are used when a customer goes to the google checkout page
						$ship->AddAddressFilters($this->defaultZoneGFilter);

						// Shipping restrictions are used if the merchant callback calculation fails
						$ship->AddShippingRestrictions($this->defaultZoneGFilter);
					}

					$this->cart->AddShipping($ship);
				}

				$this->AddShippingZoneMethodsToCart($shippingZone, $this->defaultZoneGFilter, $fixedShippingCost, false);
			}
		}

		/**
		 * Get the shipping restrictions in the google filter module format for a specific zone
		 * so we can add it as part of the shipping rules
		 *
		 * @return object
		 **/
		private function GetShippingRestrictions($zone)
		{
			$Gfilter = new GoogleShippingFilters();

			// Handle the default zone
			if (!isset($zone['locationtype'])) {
				$Gfilter->SetAllowedWorldArea(true);
				return $Gfilter;
			}

			switch ($zone['locationtype']) {
				case 'zip':
				{
					foreach ($zone['locations'] as $location) {

						$pos = strpos($location['locationvalue'], '?');
						$country = GetCountryISO2ById($location['locationcountryid']);

						if ($pos === false) {
							$Gfilter->AddAllowedPostalArea($country, $location['locationvalue']);
						}
						else {

							$tmp = substr($location['locationvalue'], 0, $pos);
							$tmp .= '*';
							$Gfilter->AddAllowedPostalArea($country, $tmp);
						}

					}
					break;
				}
				case 'state':
				{
					foreach ($zone['locations'] as $location) {
						$country = GetCountryISO2ById($location['locationcountryid']);
						$state = GetStateISO2ById($location['locationvalueid']);

						if (empty($state)) {
							$state = GetStateById($location['locationvalueid']);
						}

						if (empty($location['locationvalueid']) && $country == 'US') {
							// If they have selected all states in the us, handle it differently
							$Gfilter->SetAllowedCountryArea('ALL');
							continue;
						} elseif (empty($location['locationvalueid'])) {
							$Gfilter->AddAllowedPostalArea($country);
							continue;
						}

						if ($country == 'US' && $this->GetDefaultCurrencyCode() == 'USD') {
							// Google does not support Puerto Rico, Guam or the Marshall Islands as US-state-areas
							// Per Google Checkout support; ref ISC-155
							if ($state != 'PR' AND $state != 'MH' AND $state != 'GU')
							{
								$Gfilter->AddAllowedStateArea($state);
							}
						} else {
							$Gfilter->AddAllowedPostalArea($country, $state);
						}
					}
					break;
				}
				case 'country':
				{
					foreach ($zone['locations'] as $location) {
						$Gfilter->AddAllowedPostalArea(GetCountryISO2ById($location['locationvalueid']));
					}
					break;
				}
			}

			return $Gfilter;
		}

		/**
		 * Set the shipping restrictions in the google filter module format for the default zone
		 * so we can add it as part of the shipping rules
		 *
		 * @return void
		 **/
		private function AddDefaultShippingRestrictions($zone)
		{

			switch ($zone['locationtype']) {
				case 'zip':
				{
					foreach ($zone['locations'] as $location) {
						$this->defaultZoneGFilter->AddExcludedPostalArea(GetCountryISO2ById($location['locationcountryid']));
					}
					return false;
					break;
				}
				case 'state':
				{
					foreach ($zone['locations'] as $location) {
						$country = GetCountryISO2ById($location['locationcountryid']);
						$state = GetStateISO2ById($location['locationvalueid']);

						if (empty($state)) {
							$state = GetStateById($location['locationvalueid']);
						}

						if (empty($location['locationvalueid']) && $country == 'US') {
							// If they have selected all states in the us, handle it differently
							$this->defaultZoneGFilter->SetExcludedCountryArea('ALL');
							break 2;
						} elseif (empty($location['locationvalueid'])) {
							continue;
						}

						if ($country == 'US' && $this->GetDefaultCurrencyCode() == 'USD') {
							$this->defaultZoneGFilter->AddExcludedStateArea($state);
						} else {
							$this->defaultZoneGFilter->AddExcludedPostalArea($country, $state);
						}
					}
					break;
				}
				case 'country':
				{
					foreach ($zone['locations'] as $location) {
						$this->defaultZoneGFilter->AddExcludedPostalArea(GetCountryISO2ById($location['locationvalueid']));
					}
					break;
				}
			}
		}

		/**
		 * Return the unique order token which was saved as a cookie pre-payment
		 *
		 * @return string The Cart Id
		 */
		public function GetOrderToken()
		{
			return $this->cartid;
		}

		/**
		 * Handle the status change of an order. This is used to send google notifications so that ISC and
		 * the Google control panel keep the order state at the same stage. It is also so that you can
		 * approve, ship etc orders from the ISC control panel.
		 *
		 * @param integer $orderid The ISC order id whose status is changing
		 * @param integer $oldstatus The status id the order is changing from. Order status are defined in lib/init.php.
		 * @param integer $newstatus The new status id the order is changing to.
		 * @param mixed $data Extra data associated with the status change
		 *
		 * @return void
		 **/
		public function HandleStatusChange($orderid, $oldstatus, $newstatus, $data = '')
		{
			$request_result = '';

			$query = "
				SELECT *
				FROM [|PREFIX|]orders
				WHERE orderpaymentmodule = '".$GLOBALS['ISC_CLASS_DB']->Quote($this->GetId())."'
				AND orderid = '".$GLOBALS['ISC_CLASS_DB']->Quote($orderid)."'
				AND deleted = 0
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$order = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			$statusActions = array(
				'cancel' => array(
					ORDER_STATUS_CANCELLED,
					),
				'refund' => array(
					ORDER_STATUS_REFUNDED,
					),
				'charge' => array(
					ORDER_STATUS_AWAITING_FULFILLMENT,
					ORDER_STATUS_AWAITING_SHIPMENT,
					ORDER_STATUS_AWAITING_PICKUP,
					ORDER_STATUS_SHIPPED,
					ORDER_STATUS_COMPLETED,
					ORDER_STATUS_PARTIALLY_SHIPPED,
					),
				'ship' => array(
					ORDER_STATUS_SHIPPED,
					ORDER_STATUS_COMPLETED,
					),
			);

			if(in_array($newstatus, $statusActions['cancel'])){
				$request_result = $this->request->SendCancelOrder($order['ordpayproviderid'], GetLang('GoogleCheckoutOrderCancelledByVendor'), '');
			}

			if(in_array($newstatus, $statusActions['refund'])){
				$request_result = $this->request->SendRefundOrder($order['ordpayproviderid'], $data, GetLang('GoogleCheckoutOrderRefundedByVendor'), 'def');
			}

			if(in_array($newstatus, $statusActions['charge']) && $oldstatus == ORDER_STATUS_AWAITING_PAYMENT){
				$request_result = $this->request->SendChargeOrder($order['ordpayproviderid'], 0);
			}

			if(in_array($newstatus, $statusActions['ship'])) {
				$request_result = $this->request->SendDeliverOrder($order['ordpayproviderid']);
			}

			$GLOBALS['ISC_CLASS_LOG']->LogSystemDebug(array('payment', $this->GetName()), "Status change for #$orderid from ".GetOrderStatusById($oldstatus)." to ".GetOrderStatusById($newstatus));
		}

		/**
		 * Send google the new tracking number
		 *
		 * @param string $orderid
		 * @param string $trackingnum
		 *
		 * @return void
		 **/
		public function HandleUpdateTrackingNum($orderid, $trackingnum)
		{
			$query = "
				SELECT *
				FROM [|PREFIX|]orders
				WHERE orderpaymentmodule = '".$GLOBALS['ISC_CLASS_DB']->Quote($this->GetId())."'
				AND orderid = '".$GLOBALS['ISC_CLASS_DB']->Quote($orderid)."'
				AND deleted = 0
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$order = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			$request_result = $this->request->SendTrackingData($order['ordpayproviderid'], $this->GetShippingProvider($order['ordershipmodule']), $trackingnum);

			$GLOBALS['ISC_CLASS_LOG']->LogSystemDebug(array('payment', $this->GetName()), "Google request result: ".print_r($request_result, true));
		}

		/**
		 * Get the shipping provider name in a format google recognises
		 *
		 * @return string
		 **/
		private function GetShippingProvider($moduleid)
		{
			switch ($moduleid) {
				case 'shipping_ups':
				{
					return 'UPS';
					break;
				}
				case 'shipping_usps':
				{
					return 'USPS';
					break;
				}
				case 'shipping_fedex':
				{
					return 'FedEx';
					break;
				}
				case 'shipping_dhl':
				{
					return 'DHL';
					break;
				}
				default:
				{
					return 'Other';
				}
			}
		}
	}
