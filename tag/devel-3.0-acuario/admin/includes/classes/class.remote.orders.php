<?php

	if (!defined('ISC_BASE_PATH')) {
		die();
	}

	class ISC_ADMIN_REMOTE_ORDERS extends ISC_ADMIN_REMOTE_BASE
	{
		public function __construct()
		{
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('orders');
			parent::__construct();
		}

		public function HandleToDo()
		{
			$what = isc_strtolower(@$_REQUEST['w']);

			if(!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Orders)) {
				exit;
			}

			$methodName = $what . 'Action';
			if(!method_exists($this, $methodName)) {
				exit;
			}

			try {
				$this->$methodName();
			} catch (ISC_QUOTE_EXCEPTION $exception) {
				// lazy handler for quote-editing exceptions -- if a method requires a more specific response, catch the exception there instead
				$this->sendEditOrderResponse(array(
					'errors' => array(
						$exception->getMessage(),
					),
				));
			}
		}

		/**
		 * Handle a callback action for a checkout module wanting to handle an
		 * AJAX request in the control panel.
		 */
		private function checkoutModuleActionAction()
		{
			$module = null;
			if (empty($_REQUEST['module']) || !getModuleById('checkout', $module, $_REQUEST['module'])) {
				exit;
			}
			// The checkout module must implement this method
			else if (!method_exists($module, 'handleRemoteAdminRequest')) {
				exit;
			}

			$module->handleRemoteAdminRequest();
			exit;
		}

		/**
		 * Save a tracking number for a shipment.
		 */
		private function saveShipmentTrackingNoAction()
		{
			if(empty($_POST['id'])) {
				exit;
			}

			$query = "
				SELECT shipmentid
				FROM [|PREFIX|]shipments
				WHERE shipmentid='".(int)$_POST['id']."'
			";
			if(!$this->db->fetchOne($query)) {
				exit;
			}

			// Attempt to update the shipment ID
			$updatedShipment = array(
				'shiptrackno' => $_POST['trackingNo']
			);
			if(!$this->db->updateQuery('shipments', $updatedShipment, "shipmentid='".(int)$_POST['id']."'")) {
				exit;
			}

			echo isc_json_encode(array(
				'result' => true
			));
			exit;
		}

		protected function editOrderSaveSplitShippingAction()
		{
			if(empty($_POST['quoteSession']) || empty($_POST['quantity'])) {
				exit;
			}

			/** @var ISC_QUOTE */
			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_POST['quoteSession']);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse();
			}

			if (!$quote->getIsSplitShipping()) {
				// loading the multi address table should have already converted the quote to split shipping mode
				exit;
			}

			// set the new address + shipping details
			$shippingAddress = Interspire_Request::post('addressId', false);
			if ($shippingAddress) {
				// existing address
				$shippingAddress = $quote->getAddressById($shippingAddress);
				if (!$shippingAddress) {
					// invalid id
					exit;
				}
				$editing = true;
				$adding = false;
			} else {
				// new address
				/** @var ISC_QUOTE_ADDRESS_SHIPPING */
				$shippingAddress = $quote->createShippingAddress();
				$editing = false;
				$adding = true;
			}

			$methodCacheAddress = $quote->getAddressById(ISC_QUOTE_ADDRESS::ID_TEMP_SHIPPING_QUOTES);
			if (!$methodCacheAddress) {
				$methodCacheAddress = null;
			}

			if (!$this->validateAndPopulatePostedShippingAddress($shippingAddress, $errors, $methodCacheAddress)) {
				if ($adding) {
					$quote->removeShippingAddress($shippingAddress->getId());
				}
				$this->sendEditOrderResponse(array(
					'errors' => $errors,
				));
			}

			// remove the method cache address from the order
			if ($methodCacheAddress) {
				$quote->removeShippingAddress($methodCacheAddress->getId());
			}

			// @todo look into possibly simpler method moving an edited address's items back to unallocated and then treating everything as a new address

			/** @var ISC_QUOTE_ADDRESS */
			$unallocatedAddress = $quote->getAddressById(ISC_QUOTE_ADDRESS::ID_UNALLOCATED);
			if (!$unallocatedAddress) {
				$unallocatedAddress = $quote->createShippingAddress(ISC_QUOTE_ADDRESS::ID_UNALLOCATED);
			}

			$quantities = Interspire_Request::post('quantity', array());
			if (empty($quantities)) {
				if ($adding) {
					$quote->removeShippingAddress($shippingAddress->getId());
				}
				$this->sendEditOrderResponse(array(
					'errors', array(
						GetLang('AddProdToOrder'),
					),
				));
			}

			if ($editing) {
				// working with an already allocated destination
				// - if a quantity is increased, pull it from the unallocated address
				// - if a quantity is decreased, put it back to the unallocated address
				// - if a quantity is set to 0 or not provided in request, move the entire item back to the unallocated address
				// - there is no ui for adding a new item to an existing destination so there should never be more than $address->getItems in the request

				foreach ($shippingAddress->getItems() as /** @var ISC_QUOTE_ITEM */$item) {
					if (isset($quantities[$item->getId()])) {
						$quantity = (int)$quantities[$item->getId()];
					} else {
						$quantity = false;
					}

					if ($quantity) {
						if ($quantity > $item->getQuantity()) {
							// qty specified is greater than original, pull from unallocated
							/** @var ISC_QUOTE_ITEM */
							$unallocatedItem = $unallocatedAddress->getItemByHash($item->getHash());
							if (!$unallocatedItem) {
								// item not found in unallocated?
								continue;
							}
							$unallocatedItem->moveToAddress($shippingAddress, $quantity - $item->getQuantity());
						} else if ($quantity < $item->getQuantity()) {
							// qty specified is less than original, push to unallocated
							$item->moveToAddress($unallocatedAddress, $item->getQuantity() - $quantity);
						}
					} else {
						// no quantity supplied or 0 specified, move the item back to unallocated
						$item->moveToAddress($unallocatedAddress);
					}
				}
			} else {
				// allocating products, move all quantities specified in the request from unallocated to a new address
				foreach ($quantities as $item => $quantity) {
					$quantity = (int)$quantity;
					if (!$quantity) {
						// no quantity to move from unallocated, ignore
						continue;
					}

					/** @var ISC_QUOTE_ITEM */
					$item = $quote->getItemById($item);
					if (!$item) {
						// invalid id, ignore
						continue;
					}

					$item->moveToAddress($shippingAddress, $quantity);
				}
			}

			if (!$shippingAddress->getItemCount()) {
				if ($adding) {
					$quote->removeShippingAddress($shippingAddress->getId());
				}
				$this->sendEditOrderResponse(array(
					'errors' => array(
						GetLang('AddProdToOrder'),
					),
				));
			}

			$this->sendEditOrderResponse(array(
				'closeModal' => 1,
				'itemsTable' => getClass('ISC_ADMIN_ORDERS')->generateEditOrderItemsTable($quote),
				'multiShippingTable' => getClass('ISC_ADMIN_ORDERS')->renderMultiShippingTable($quote),
				'isDigital' => $quote->isDigital(),
			));
		}

		protected function editOrderFetchSplitShippingQuotesAction()
		{
			if(empty($_POST['quoteSession']) || empty($_POST['quantity'])) {
				exit;
			}

			/** @var ISC_QUOTE */
			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_POST['quoteSession']);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse();
			}

			if (!$quote->getIsSplitShipping()) {
				// loading the multi address table should have already converted the quote to split shipping mode
				exit;
			}

			// get / create the temporary address for fetching shipping quotes
			/** @var ISC_QUOTE_ADDRESS_SHIPPING */
			$shippingAddress = $quote->getAddressById(ISC_QUOTE_ADDRESS::ID_TEMP_SHIPPING_QUOTES);
			if (!$shippingAddress) {
				$shippingAddress = $quote->createShippingAddress(ISC_QUOTE_ADDRESS::ID_TEMP_SHIPPING_QUOTES);
			}

			// make sure the temp address is empty of items
			foreach ($shippingAddress->getItems() as $item) {
				$quote->removeItem($item->getId());
			}

			// clone the items specified for quotation and place them into the temporary address
			foreach (Interspire_Request::post('quantity', array()) as $item => $quantity) {
				$quantity = (int)$quantity;
				if (!$quantity) {
					continue;
				}

				$item = $quote->getItemById($item);
				if (!$item) {
					continue;
				}

				/** @var ISC_QUOTE_ITEM */
				$item = clone $item;
				$quote->addItem($item, false);
				$item
					->setAddressId($shippingAddress)
					->setQuantity((int)$quantity, false);
			}

			if (!$shippingAddress->getItemCount()) {
				$this->sendEditOrderResponse(array(
					'errors' => array(
						GetLang('AddProdToOrder'),
					),
				));
			}

			// get country, state, zip from posted form fields and assign them to the temporary address (taken from editOrderFetchSingleShippingQuotesAction)
			$shippingFormFields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_SHIPPING, true);

			$country = '';
			$state = '';
			$zip = '';

			foreach($shippingFormFields as $formField) {
				$privateId = $formField->record['formfieldprivateid'];
				if($privateId == 'State') {
					$state = $formField->getValue();
				}
				else if($privateId == 'Country') {
					$country = $formField->getValue();
				}
				else if($privateId == 'Zip') {
					$zip = $formField->getValue();
				}
			}

			$shippingAddress
				->setCountryByName($country)
				->setStateByName($state)
				->setZip($zip);

			// generate the quotes we need
			$shippingMethods = $shippingAddress->getAvailableShippingMethods();

			// remove items from the temp shipping address but leave it in place in the order for accessing the cached shipping methods
			foreach ($shippingAddress->getItems() as $item) {
				$quote->removeItem($item->getId());
			}

			if (!$shippingMethods) {
				$shippingMethods = array();
			}

			// apply taxes and generate response values
			$taxZone = $shippingAddress->getApplicableTaxZone();
			foreach($shippingMethods as &$method) {
				$price = getClass('ISC_TAX')->getPrice(
					$method['price'],
					getConfig('taxShippingTaxClass'),
					getConfig('taxDefaultTaxDisplayCart'),
					$taxZone
				);
				$method['price'] = formatPrice($price);
				$method['unformattedPrice'] = formatPrice($price, false, false);
			}

			// Send the response back (singleShippingMethods can be used in the context of the iframe)
			$this->sendEditOrderResponse(array(
				'singleShippingMethods' => $shippingMethods
			));
		}

		private function editOrderFetchSingleShippingQuotesAction()
		{
			if(empty($_POST['quoteSession'])) {
				exit;
			}

			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_POST['quoteSession']);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse();
			}

			$quote->setIsSplitShipping(false);
			$shippingFormFields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_SHIPPING, true);

			$country = '';
			$state = '';
			$zip = '';

			foreach($shippingFormFields as $formField) {
				$privateId = $formField->record['formfieldprivateid'];
				if($privateId == 'State') {
					$state = $formField->getValue();
				}
				else if($privateId == 'Country') {
					$country = $formField->getValue();
				}
				else if($privateId == 'Zip') {
					$zip = $formField->getValue();
				}
			}

			$shippingAddress = $quote->getShippingAddress()
				->setCountryByName($country)
				->setStateByName($state)
				->setZip($zip);

			$shippingMethods = $shippingAddress->getAvailableShippingMethods();
			$taxZone = $shippingAddress->getApplicableTaxZone();
			foreach($shippingMethods as &$method) {
				$price = getClass('ISC_TAX')->getPrice(
					$method['price'],
					getConfig('taxShippingTaxClass'),
					getConfig('taxDefaultTaxDisplayCart'),
					$taxZone
				);
				$method['price'] = formatPrice($price);
				$method['unformattedPrice'] = formatPrice($price, false, false);
			}

			// Send the response back
			$response = array(
				'singleShippingMethods' => $shippingMethods
			);
			$this->sendEditOrderResponse($response);
		}

		/**
		* Validates POSTed address field + shipping method info and, if valid, populates the provided address object
		*
		* This was originally inside editOrderSaveSingleShippingAction but it's moved for use with both single and split-shipping addresses. -ge
		*
		* @param ISC_QUOTE_ADDRESS_SHIPPING $address address object to populate with validated details
		* @param array $errors by-reference array of errors
		* @param ISC_QUOTE_ADDRESS_SHIPPING $shippingMethodAddress an optional second address instance which carries cached shipping method information (this is a bit of a hack, but it's used by the split-shipping quotation functionality where a second temporary address is used to calculate shipping before actually saving in a different request)
		* @return bool true on success, otherwise false (with error information stored in $errors)
		*/
		protected function validateAndPopulatePostedShippingAddress(ISC_QUOTE_ADDRESS_SHIPPING $address, &$errors = null, ISC_QUOTE_ADDRESS_SHIPPING $shippingMethodAddress = null)
		{
			// this is tied to $_POST because of form fields

			if ($shippingMethodAddress === null) {
				$shippingMethodAddress = $address;
			}

			$shippingMethod = 'builtin:none';
			if (isset($_POST['shippingQuoteList'])) {
				$shippingMethod = $_POST['shippingQuoteList'];
			}

			$errors = array();

			$shippingCustomFields = array();
			$saveAddress = Interspire_Request::post('saveShippingAddress');

			$shippingFormFields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_SHIPPING, true);
			foreach($shippingFormFields as $formFieldId => $formField) {
				// All fields are optional on the order management page, so only validate
				// when there is a value.
				$error = '';
				if($formField->getValue() && !$formField->runValidation($error)) {
					$errors[] = $error;
				}

				if(!$formField->record['formfieldprivateid']) {
					$shippingCustomFields[$formFieldId] = $formField->getValue();
				}
			}

			if (!empty($errors)) {
				return false;
			}

			require ISC_BASE_PATH . '/lib/addressvalidation.php';
			$shippingAddressArray = convertAddressFieldsToArray($shippingFormFields);

			// Actually set the shipping address on the quote
			/** @var ISC_QUOTE_ADDRESS_SHIPPING */
			$address->setAddressByArray($shippingAddressArray)
				->setCustomFields($shippingCustomFields)
				->setSaveAddress($saveAddress);

			if ($shippingMethod == 'builtin:none') {
				$address->setShippingMethod(0, GetLang('xNone'), '', true);
				$address->setHandlingCost(0);
			} else if ($shippingMethod == 'builtin:custom') {
				if (empty($_POST['customShippingDescription'])) {
					$errors[] = GetLang('ErrorEnterShippingMethodName');
				} else {
					if (empty($_POST['customShippingPrice'])) {
						$customShippingPrice = 0;
					} else {
						$customShippingPrice = $_POST['customShippingPrice'];
					}

					$address->setShippingMethod(
						DefaultPriceFormat($customShippingPrice),
						$_POST['customShippingDescription'],
						'custom',
						true
					);

					$address->setHandlingCost(0);
				}
			} else if ($shippingMethod == 'builtin:current') {
				// restore previous shipping method
				$current = Interspire_Request::post('currentShipping', array());
				if (!empty($current)) {
					$address->setShippingMethod(
						$current['price'],
						$current['description'],
						$current['module'],
						(bool)$current['isCustom']
					);
				}
			} else {
				$method = $shippingMethodAddress->getCachedShippingMethod($shippingMethod);
				if (!$method) {
					$errors[] = GetLang('OldQuoteNotFound');
				} else {
					$address->setShippingMethod(
						$method['price'],
						$method['description'],
						$method['module'],
						true
					);

					$address->setHandlingCost($method['handling']);
				}
			}

			if(!empty($errors)) {
				return false;
			}

			return true;
		}

		protected function editOrderSaveShippingAction()
		{
			if(empty($_POST['quoteSession'])) {
				exit;
			}

			/** @var ISC_QUOTE */
			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_POST['quoteSession']);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse('shippingInvalid');
			}

			try {
				$shipItemsTo = Interspire_Request::post('shipItemsTo');
				if ($shipItemsTo == 'multiple') {
					// validate that the split-shipping order has all addresses completed

					$unallocated = $quote->getAddressById(ISC_QUOTE_ADDRESS::ID_UNALLOCATED);
					if ($unallocated && $unallocated->getItemCount()) {
						$this->sendEditOrderResponse(array(
							'stateTransition' => 'shippingInvalid',
							'errors' => array(
								GetLang('UnallocatedItemsExist'),
							),
						));
					}

					// ensure the temp addresses are gone
					$quote->removeShippingAddress(ISC_QUOTE_ADDRESS::ID_TEMP_SHIPPING_QUOTES);
					$quote->removeShippingAddress(ISC_QUOTE_ADDRESS::ID_UNALLOCATED);
				} else {
					// validate single-address shipping
					$quote->setIsSplitShipping(false);
					$address = $quote->getShippingAddress();

					if (!$this->validateAndPopulatePostedShippingAddress($address, $errors)) {
						$this->sendEditOrderResponse(array(
							'stateTransition' => 'shippingInvalid',
							'errors' => $errors,
						));
					}
				}

				$this->sendEditOrderResponse(array(
					'stateTransition' => 'shippingOk',
					'summaryTable' => $this->generateOrderFormSummaryTable($quote),
					'shippingDetailsSummary' => $this->generateShippingDetailsSummary($quote),
				));
			} catch (ISC_QUOTE_EXCEPTION $exception) {
				$this->sendEditOrderResponse(array(
					'stateTransition' => 'shippingInvalid',
					'errors' => array(
						$exception->getMessage(),
					),
				));
			}
		}

		protected function generateOrderFormSummaryTable(ISC_QUOTE $quote)
		{
			return $this->template->render('order.form.summary.totals.tpl', array(
				'quote' => $quote,
				'totals' => ISC_CHECKOUT::getQuoteTotalRows($quote, null, false), // @todo this should probably be part of ISC_QUOTE
			));
		}

		private function editOrderLoadMultiShippingTableAction()
		{
			if(empty($_POST['quoteSession'])) {
				exit;
			}

			/** @var ISC_ADMIN_ORDERS */
			$orders = GetClass('ISC_ADMIN_ORDERS');

			/** @var ISC_QUOTE */
			$quote = $orders->getQuoteSession($_POST['quoteSession']);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse();
			}

			if(!$quote->getIsSplitShipping()) {
				// Make sure that split shipping is enabled for this quote
				$unallocatedAddress = $quote->getShippingAddress()
					->setId(ISC_QUOTE_ADDRESS::ID_UNALLOCATED);
				$quote->setIsSplitShipping(true);
			} else {
				if(!$unallocatedAddress = $quote->getAddressById(ISC_QUOTE_ADDRESS::ID_UNALLOCATED)) {
					$unallocatedAddress = new ISC_QUOTE_ADDRESS_SHIPPING;
					$unallocatedAddress
						->setQuote($quote)
						->setId(ISC_QUOTE_ADDRESS::ID_UNALLOCATED);
					$quote->addShippingAddress($unallocatedAddress);
				}
			}

			$quote->removeShippingAddress(ISC_QUOTE_ADDRESS::ID_TEMP_SHIPPING_QUOTES);

			$response = array(
				'itemsTable' => getClass('ISC_ADMIN_ORDERS')->generateEditOrderItemsTable($quote),
				'multiShippingTable' => $orders->renderMultiShippingTable($quote),
				'isDigital' => $quote->isDigital(),
			);
			$this->sendEditOrderResponse($response);
		}

		private function editOrderDeleteItemAction()
		{
			if(empty($_POST['quoteSession']) || empty($_POST['itemId'])) {
				exit;
			}

			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_POST['quoteSession']);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse();
			}

			if(!$quote->hasItem($_POST['itemId'])) {
				exit;
			}

			$quote->removeItem($_POST['itemId']);
			if(getConfig('taxDefaultTaxDisplayCart') == TAX_PRICES_DISPLAY_INCLUSIVE) {
				$incTax = true;
			}
			else {
				$incTax = false;
			}

			$response = array(
				'itemsRemoveItem' => $_POST['itemId'],
				'itemsSubtotal' => formatPrice($quote->getSubtotal($incTax)),
				'isDigital' => $quote->isDigital(),
			);

			if ($quote->getIsSplitShipping()) {
				$response['multiShippingTable'] = getClass('ISC_ADMIN_ORDERS')->renderMultiShippingTable($quote);
			}

			$this->sendEditOrderResponse($response);
		}

		private function editOrderCopyItemAction()
		{
			if(empty($_POST['quoteSession']) || empty($_POST['itemId'])) {
				exit;
			}

			/** @var ISC_QUOTE */
			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_POST['quoteSession']);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse();
			}

			if(!$quote->hasItem($_POST['itemId'])) {
				exit;
			}

			$item = $quote->getItemById($_POST['itemId']);
			if ($item->isGiftCertificate()) {
				exit; // this should be denied by the UI
			}

			$newItem = clone $item;
			$quote->addItem($newItem, false);

			if(getConfig('taxDefaultTaxDisplayCart') == TAX_PRICES_DISPLAY_INCLUSIVE) {
				$incTax = true;
			}
			else {
				$incTax = false;
			}

			$response = array(
				'itemsTable' => getClass('ISC_ADMIN_ORDERS')->generateEditOrderItemsTable($quote),
				'itemsSubtotal' => formatPrice($quote->getSubtotal($incTax)),
				'isDigital' => $quote->isDigital(),
			);

			if ($quote->getIsSplitShipping()) {
				$response['multiShippingTable'] = getClass('ISC_ADMIN_ORDERS')->renderMultiShippingTable($quote);
			}

			$this->sendEditOrderResponse($response);
		}

		private function editOrderLoadCustomerAction()
		{
			if(empty($_POST['customerId'])) {
				exit;
			}

			$customer = getClass('ISC_CUSTOMER')->getCustomerInfo($_POST['customerId']);
			if(!$customer) {
				exit;
			}

			$response = array(
				'email' => $customer['custconemail'],
				'firstName' => $customer['custconfirstname'],
				'lastName' => $customer['custconlastname'],
				'addresses' => array()
			);

			$addresses = getClass('ISC_CUSTOMER')->getCustomerShippingAddresses($customer['customerid']);
			require_once ISC_BASE_PATH . '/lib/addressvalidation.php';
			foreach($addresses as $address) {
				$address = convertAddressArrayToFieldArray($address);
				$countryIso = getCountryISO2ByName($address['Country']);
				if(file_exists(ISC_BASE_PATH.'/lib/flags/'.strtolower($countryIso.'.gif'))) {
					$address['countryFlag'] = strtolower($countryIso);
				}
				$response['addresses'][] = $address;
			}

			$this->sendEditOrderResponse($response);
		}

		private function editOrderCustomerSearchAction()
		{
			if(empty($_REQUEST['q']) || empty($_REQUEST['quoteSession'])) {
				exit;
			}

			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_REQUEST['quoteSession']);
			if(!$quote) {
				exit;
			}

			$customerGroupId = $quote->getCustomerGroupId();

			$_REQUEST['searchQuery'] = $_REQUEST['q'];

			$numCustomers = 0;
			$result = getClass('ISC_ADMIN_CUSTOMERS')->_getCustomerList(0, 'custconlastname', 'asc', $numCustomers, 10);

			if($numCustomers == 0) {
				exit;
			}

			$results = array();
			while($customer = $this->db->fetch($result)) {
				$results[] = array(
					'id'		=> $customer['customerid'],
					'name'		=> $customer['custconfirstname'].' '.$customer['custconlastname'],
					'link'		=> '',
					'phone'		=> $customer['custconphone'],
					'email'		=> $customer['custconemail'],
					'company'	=> $customer['custconcompany']
				);
			}

			echo isc_json_encode($results);
		}

		private function editOrderSaveItemCustomizationsAction()
		{
			if(empty($_POST['quoteSession'])) {
				exit;
			}

			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_POST['quoteSession']);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse();
			}

			// Adding a new item
			$newItem = false;
			if(empty($_POST['itemId'])) {
				$newItem = true;
				$item = new ISC_QUOTE_ITEM;
				$item->setQuote($quote);

				if (empty($_POST['productId'])) {
					// Virtual item, disable checks that apply only to actual products
					$item
						->setInventoryCheckingEnabled(false)
						->setType(PT_VIRTUAL);
				} else {
					// New item is based on an existing product
					$item->setProductId($_POST['productId']);
				}
			}
			// Updating an existing item in the quote. Attempt to find it.
			else {
				$item = $quote->getItemById($_POST['itemId']);
				if(!$item || $item->isGiftCertificate()) {
					exit;
				}
			}

			$item->setQuantity($_POST['quantity']);

			// are we managing a virtual item?
			if (!$item->getProductId()) {
				// Set a custom sku for this quote item
				if (!empty($_POST['sku'])) {
					$item->setSku($_POST['sku']);
				}

				// Set a custom name for this quote item
				if (!empty($_POST['name'])) {
					$item->setName($_POST['name']);
				}
			} else {
				$query = "
					SELECT *
					FROM [|PREFIX|]products
					WHERE productid=".(int)$item->getProductId()."
				";
				$result = $this->db->query($query);
				$product = $this->db->fetch($result);
			}

			// Set the priceof the product
			$item->setBasePrice(DefaultPriceFormat($_POST['price'], null, true), true);

			// Select the variation if one was chosen
			if(isset($_POST['variationId'])) {
				$item->setVariation($_POST['variationId']);
			}

			// Set configurable fields
			$configurableFields = $this->buildProductConfigurableFieldData();
			if(!empty($configurableFields)) {
				$item->applyConfiguration($configurableFields);
			}

			// Handle event dates
			if(!empty($_POST['eventDate']) && !empty($product)) {
				$item->setEventDate($_POST['eventDate']['month'], $_POST['eventDate']['day'], $_POST['eventDate']['year']);
				$item->setEventName($product['prodeventdatefieldname']);
			}

			// And finally gift wrapping
			if(!empty($_POST['giftWrappingType'])) {
				if($_POST['giftWrappingType'] == 'none') {
					$item->removeGiftWrapping();
				}
				else {
					$item->applyGiftWrapping($_POST['giftWrappingType'], $_POST['giftWrapping'], $_POST['giftMessage']);
				}
			}

			// If this is a new item, actually add it to the quote

			if(getConfig('taxDefaultTaxDisplayCart') == TAX_PRICES_DISPLAY_INCLUSIVE) {
				$incTax = true;
			}
			else {
				$incTax = false;
			}

			if($newItem) {
				$quote->addItem($item);

				$response = array(
					'closeModal' => true,
					'itemsTable' => getClass('ISC_ADMIN_ORDERS')->generateEditOrderItemsTable($quote),
					'itemsSubtotal' => formatPrice($quote->getSubtotal($incTax)),
					'isDigital' => $quote->isDigital(),
				);

				if ($quote->getIsSplitShipping()) {
					$response['multiShippingTable'] = getClass('ISC_ADMIN_ORDERS')->renderMultiShippingTable($quote);
				}

				$this->sendEditOrderResponse($response);
			}
			else {
				// If gift wrapping we need to send back multiple items - send back the entire cart
				$response = array(
					'closeModal' => true,
					'itemsSubtotal' => formatPrice($quote->getSubtotal($incTax)),
				);

				if(isset($_POST['giftWrappingType']) && $_POST['giftWrappingType'] == 'different') {
					$response['itemsTable'] = GetClass('ISC_ADMIN_ORDERS')->generateEditOrderItemsTable($quote);
					$response['isDigital'] = $quote->isDigital();
					if ($quote->getIsSplitShipping()) {
						$response['multiShippingTable'] = getClass('ISC_ADMIN_ORDERS')->renderMultiShippingTable($quote);
					}
				}
				else {
					$response['itemsUpdateItem'] = array(
						'id' => $item->getId(),
						'content' => getClass('ISC_ADMIN_ORDERS')->generateEditOrderItemRow($item),
					);
				}

				$this->sendEditOrderResponse($response);
			}
		}

		protected function editOrderDeleteShippingDestinationAction()
		{
			if(empty($_POST['quoteSession']) || empty($_POST['addressId'])) {
				exit;
			}

			/** @var ISC_QUOTE */
			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_POST['quoteSession']);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse();
			}

			/** @var ISC_QUOTE_ADDRESS */
			$address = $quote->getAddressById($_POST['addressId']);
			if (!$address || $address->getType() == ISC_QUOTE_ADDRESS::TYPE_BILLING) {
				exit;
			}

			$createdUnallocated = false;
			$unallocatedAddress = $quote->getAddressById(ISC_QUOTE_ADDRESS::ID_UNALLOCATED);
			if(!$unallocatedAddress) {
				$unallocatedAddress = new ISC_QUOTE_ADDRESS_SHIPPING;
				$unallocatedAddress
					->setQuote($quote)
					->setId(ISC_QUOTE_ADDRESS::ID_UNALLOCATED);
				$quote->addShippingAddress($unallocatedAddress);
				$createdUnallocated = true;
			}

			$address->moveAllItemsToAddress($unallocatedAddress);
			$quote->removeShippingAddress($address->getId());

			$response = array(
				'itemsTable' => getClass('ISC_ADMIN_ORDERS')->generateEditOrderItemsTable($quote),
				'multiShippingTable' => getClass('ISC_ADMIN_ORDERS')->renderMultiShippingTable($quote),
				'isDigital' => $quote->isDigital(),
			);

			$this->sendEditOrderResponse($response);
		}

		/**
		 * Build a normalized array containing information about configurable product fields
		 * from the POST.
		 *
		 * @return array A normalized array of files/fields for configurable fields.
		 */
		private function buildProductConfigurableFieldData()
		{
			$configurableFields = array();
			if(isset($_POST['configurableFields']) && is_array($_POST['configurableFields'])) {
				$configurableFields = $_POST['configurableFields'];
			}

			if(isset($_FILES['configurableFields']) && is_array($_FILES['configurableFields'])) {
				$fileFields = array_keys($_FILES['configurableFields']);
				foreach($_FILES['configurableFields']['name'] as $fieldId => $name) {
					if(!$name) {
						continue;
					}
					$configurableFields[$fieldId] = array();
					foreach($fileFields as $field) {
						if(!isset($_FILES['configurableFields'][$field][$fieldId])) {
							continue;
						}
						$configurableFields[$fieldId][$field] = $_FILES['configurableFields'][$field][$fieldId];
					}
				}
			}
			return $configurableFields;
		}

		private function editOrderAddItemAction()
		{
			if(empty($_REQUEST['productId']) || empty($_REQUEST['quoteSession'])) {
				exit;
			}

			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_REQUEST['quoteSession']);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse();
			}

			$query = "
				SELECT *
				FROM [|PREFIX|]products
				WHERE productid='".(int)$_REQUEST['productId']."'
			";
			$result = $this->db->query($query);
			$product = $this->db->fetch($result);
			if(!$product) {
				exit;
			}

			// If the product requires configuration, show the configuration window
			if($product['prodvariationid'] || $product['prodconfigfields'] || $product['prodeventdaterequired']) {
				$price = formatPrice(calculateFinalProductPrice($product, $product['prodcalculatedprice'], array(
					'customerGroup' => $quote->getCustomerGroupId()
				)), false, false);
				$item = array(
					'name' => $product['prodname'],
					'price' => $price,
					'productId' => $product['productid'],
					'quoteSession' => $_POST['quoteSession'],
				);

				// Set focus to the tab that contains the first bit of content we need
				if($product['prodvariationid']) {
					$item['activeTab'] = 'variationTab';
				}
				else if($product['prodconfigfields']) {
					$item['activeTab'] = 'configurableFieldsTab';
				}
				else if($product['prodeventdaterequired']) {
					$item['activeTab'] = 'eventDateTab';
				}

				$this->sendEditOrderResponse(array(
					'modal' => $this->generateCustomizeItemForm($item),
				));
			}

			$quantity = (int)$product['prodminqty'];
			if ($quantity < 1) {
				$quantity = 1;
			}

			$item = new ISC_QUOTE_ITEM;
			$item
				->setQuote($quote)
				->setProductId($_REQUEST['productId'])
				->setQuantity($quantity);

			$quote->addItem($item, false);

			if ($quote->getIsSplitShipping()) {
				$unallocatedAddress = $quote->getAddressById(ISC_QUOTE_ADDRESS::ID_UNALLOCATED);
				if (!$unallocatedAddress) {
					$unallocatedAddress = $quote->createShippingAddress(ISC_QUOTE_ADDRESS::ID_UNALLOCATED);
				}
				$item->setAddressId($unallocatedAddress->getId());
			}

			if(getConfig('taxDefaultTaxDisplayCart') == TAX_PRICES_DISPLAY_INCLUSIVE) {
				$incTax = true;
			}
			else {
				$incTax = false;
			}

			$response = array(
				'itemsTable' => getClass('ISC_ADMIN_ORDERS')->generateEditOrderItemsTable($quote),
				'itemsSubtotal' => formatPrice($quote->getSubtotal($incTax)),
				'isDigital' => $quote->isDigital(),
			);

			if ($quote->getIsSplitShipping()) {
				$response['multiShippingTable'] = getClass('ISC_ADMIN_ORDERS')->renderMultiShippingTable($quote);
			}

			$this->sendEditOrderResponse($response);
		}

		private function generateCustomizeItemForm(array $item)
		{
			$defaultItem = array(
				'name' => '',
				'quantity' => 1,
				'price' => '',
				'productId' => '',
				'variationOptions' => array(),
				'variationId' => 0,
				'configuration' => '',
				'wrapping' => '',
				'itemId' => '',
				'quoteSession' => '',
				'eventDate' => array(),
				'eventName' => '',
				'sku' => '',
			);
			$item = array_merge($defaultItem, $item);
			$this->template->assign('item', $item);

			if($item['productId']) {
				$productClass = new ISC_PRODUCT($item['productId']);
				if(!$productClass->getProductId()) {
					$this->sendEditOrderResponse(array(
						'errors' => array(
							getLang('InvalidProduct')
						)
					));
				}

				$this->template->assign('product', $productClass->getProduct());

				$this->template->assign('variationOptions', $productClass->GetProductVariationOptions());
				$this->template->assign('variationValues', $productClass->GetProductVariationOptionValues());

				$configurableFields = $productClass->GetProductFields($item['productId']);
				foreach($configurableFields as &$field) {
					if($field['type'] == 'select') {
						$options = explode(',', $field['selectOptions']);
						$field['selectOptions'] = array_map('trim', $options);
					}
				}
				$this->template->assign('configurableFields', $configurableFields);

				// Event date
				if($productClass->getEventDateRequired()) {
					$eventDateFromStamp = $productClass->getEventDateLimitedStartDate();
					$eventDateToStamp = $productClass->getEventDateLimitedEndDate();

					$eventDate = array(
						'fromStamp' => $eventDateFromStamp,
						'toStamp' => $eventDateToStamp,
						'yearFrom' => isc_date('Y', $eventDateFromStamp),
						'yearTo' => isc_date('Y', $eventDateToStamp)
					);

					// Generate a list of month options
					$eventDate['monthOptions'] = array();
					for($i = 1; $i <= 12; ++$i) {
						$stamp = isc_gmmktime(0, 0, 0, $i, 1, 2000);
						$month = isc_date("M", $stamp);
						$eventDate['monthOptions'][$i] = $month;
					}

					$eventDateLimit = $productClass->getEventDateLimited();
					if(empty($eventDateLimit)) {
						$eventDate['yearFrom'] = isc_date('Y');
						$eventDate['yearTo'] = $eventDate['yearFrom'] + 5;
					}
					else {
						$eventDate['limitationType'] = $productClass->getEventDateLimitedType();
						if($eventDate['limitationType'] == 1) {
							$eventDate['compDate'] = isc_date('Y/m/d', $eventDateFromStamp);
							$eventDate['compDateEnd'] = isc_date('Y/m/d', $eventDateToStamp);
						}
						else if($eventDate['limitationType'] == 2) {
							$eventDate['yearTo'] = $eventDate['yearFrom'] + 5;
							$eventDate['compDate'] = isc_date('Y/m/d', $eventDateFromStamp);
						}
						else if($eventDate['limitationType'] == 3) {
							$eventDate['yearFrom'] = isc_date('Y');
							$eventDate['compDate'] = isc_date('Y/m/d', $eventDateToStamp);
						}
					}

					$this->template->assign('eventDate', $eventDate);
				}
			}

			if(!empty($item['quoteItem'])) {
				$allowableWrappingOptions = $item['quoteItem']->getGiftWrappingOptions();
			}

			// Product still exists - get the gift wrapping options on the product
			if(isset($productClass)) {
				$product = $productClass->getProduct();
				$allowableWrappingOptions = explode(',', $product['prodwrapoptions']);
			}

			if(!empty($allowableWrappingOptions)) {
				if(empty($allowableWrappingOptions) || in_array(0, $allowableWrappingOptions)) {
					$giftWrapWhere = "wrapvisible='1'";
				}
				else {
					$wrappingOptions = implode(',', array_map('intval', $allowableWrappingOptions));
					$giftWrapWhere = "wrapid IN (".$wrappingOptions.")";
				}
				$query = "
					SELECT *
					FROM [|PREFIX|]gift_wrapping
					WHERE ".$giftWrapWhere."
					ORDER BY wrapname ASC
				";
				$giftWrappingOptions = array();
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				while($wrap = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$giftWrappingOptions[$wrap['wrapid']] = $wrap;
				}

				$this->template->assign('giftWrappingOptions', $giftWrappingOptions);
			}

			return array(
				'data' => $this->template->render('order.form.customizeitem.tpl'),
				'width' => 600,
				'height' => 500,
			);
		}

		public function editOrderUpdateItemQuantityPriceAction()
		{
			if(empty($_POST['quoteSession']) || empty($_POST['itemId'])) {
				exit;
			}

			/** @var ISC_QUOTE */
			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_POST['quoteSession']);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse();
			}
			if(!$quote->hasItem($_POST['itemId'])) {
				exit;
			}

			$item = $quote->getItemById($_POST['itemId']);
			if ($item->isGiftCertificate()) {
				exit; // this should be denied by the ui
			}

			$item->setQuantity($_POST['quantity']);
			if($item->getBasePrice() != $_POST['price']) {
				$item->setBasePrice(DefaultPriceFormat($_POST['price'], null, true), true);
			}

			if(getConfig('taxDefaultTaxDisplayCart') == TAX_PRICES_DISPLAY_INCLUSIVE) {
				$incTax = true;
			}
			else {
				$incTax = false;
			}

			$response = array(
				'itemsUpdateItemTotal' => array(
					'id' => $item->getId(),
					'content' => formatPrice($item->getTotal($incTax))
				),
				'itemsSubtotal' => formatPrice($quote->getSubtotal($incTax)),
			);

			if ($quote->getIsSplitShipping()) {
				$response['multiShippingTable'] = getClass('ISC_ADMIN_ORDERS')->renderMultiShippingTable($quote);
			}

			$this->sendEditOrderResponse($response);
		}

		protected function editOrderAddVirtualItemAction()
		{
			if(empty($_POST['quoteSession'])) {
				exit;
			}

			$name = '';
			if (isset($_POST['name'])) {
				$name = $_POST['name'];
			}

			/** @var ISC_QUOTE */
			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_POST['quoteSession']);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse();
			}

			// this is basically the same as showing the configuration dialog when adding a product which requires configuration
			$item = array(
				'name' => $name,
				'quoteSession' => $_POST['quoteSession'],
			);

			$this->sendEditOrderResponse(array(
				'modal' => $this->generateCustomizeItemForm($item),
			));
		}

		private function editOrderCustomizeItemAction()
		{
			if(empty($_POST['quoteSession']) || empty($_POST['itemId'])) {
				exit;
			}

			/** @var ISC_QUOTE */
			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_POST['quoteSession']);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse();
			}
			if(!$quote->hasItem($_POST['itemId'])) {
				exit;
			}

			$item = $quote->getItemById($_POST['itemId']);
			if ($item->isGiftCertificate()) {
				exit; // this should be denied by the UI
			}

			$customizeItem = array(
				'name' => $item->getName(),
				'sku' => $item->getSku(),
				'quantity' => $item->getQuantity(),
				'price' => formatPrice($item->getPrice($quote->doesStoreCartDisplayIncludeTax()), false, false),
				'productId' => $item->getProductId(),
				'variationOptions' => $item->getVariationOptions(),
				'variationId' => $item->getVariationId(),
				'configuration' => $item->getConfiguration(),
				'wrapping' => $item->getGiftWrapping(),
				'itemId' => $item->getId(),
				'quoteItem' => $item,
				'quoteSession' => $_POST['quoteSession'],
				'eventDate' => $item->getEventDate(),
				'eventName' => $item->getEventName(),
			);
			$this->sendEditOrderResponse(array(
				'modal' => $this->generateCustomizeItemForm($customizeItem),
			));
		}

		private function editOrderItemSearchAction()
		{
			if(empty($_REQUEST['q']) || empty($_REQUEST['quoteSession'])) {
				exit;
			}

			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_REQUEST['quoteSession']);
			if(!$quote) {
				exit;
			}

			$customerGroupId = $quote->getCustomerGroupId();

			$_REQUEST['searchQuery'] = $_REQUEST['q'];

			// autocomplete plugin can send a limit which will be at most 11 but internally we limit this to 2-11 and
			// reduce it by 1 to get 1-10 and append the 'virtual' item result as #11
			$limit = 11;
			if (isset($_REQUEST['limit'])) {
				$limit = max(2, min(10, (int)$_REQUEST['limit']));
			}
			$limit--;

			$numProducts = 0;
			$result = getClass('ISC_ADMIN_PRODUCT')->_getProductList(
				0, 'p.prodname', 'asc', $numProducts,
				'DISTINCT p.*, '.GetProdCustomerGroupPriceSQL($customerGroupId), 10);

			$results = array();
			while($product = $this->db->fetch($result)) {
				$isConfigurable = false;
				if($product['prodvariationid'] != 0 || $product['prodconfigfields'] != 0) {
					$isConfigurable = true;
				}

				$options = array(
					'customerGroup' => $customerGroupId
				);
				$price = calculateFinalProductPrice($product, $product['prodcalculatedprice'], $options);
				$price = formatPrice($price);
				$results[] = array(
					'id'				=> $product['productid'],
					'name'				=> $product['prodname'],
					'link'				=> prodLink($product['prodname']),
					'sku'				=> $product['prodcode'],
					'isConfigurable'	=> $isConfigurable,
					'price'				=> $price
				);
			}

			$results[] = array(
				'id'			=> 'virtual',
				'virtualName'	=> $_REQUEST['q'],
				'name'			=> GetLang('AddManualProduct'),
				'className'		=> 'recordContentManual',
				'price'			=> GetLang('AddManualProductHelp'),
			);

			echo isc_json_encode($results);
		}


		private function editOrderSaveBillingAddressAction()
		{
			if(empty($_POST['quoteSession']) || empty($_POST['orderFor'])) {
				exit;
			}

			$errors = array();
			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_POST['quoteSession']);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse('customerDetailsInvalid');
			}

			try {
				$customerId = 0;

				$password = '';
				$confirmedPassword = '';
				$email = '';
				$accountFormFields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, true);
				foreach($accountFormFields as $formField) {
					$formFieldPrivateId = $formField->record['formfieldprivateid'];
					if($formFieldPrivateId == 'EmailAddress') {
						$email = $formField->getValue();
					}
					else if($formFieldPrivateId == 'Password') {
						$password = $formField->getValue();
					}
					else if($formFieldPrivateId == 'ConfirmPassword') {
						$confirmedPassword = $formField->getValue();
					}
				}

				if($email && !is_email_address($email)) {
					$this->sendEditOrderResponse(array(
						'stateTransition' => 'customerDetailsInvalid',
						'errors' => array(
							getLang('CustomerEmailInvalid')
						)
					));
				}

				if($_POST['orderFor'] == 'new') {
					foreach($accountFormFields as $formField) {
						// All fields are optional on the order management page, so only validate
						// when there is a value.
						$error = '';
						if($formField->getValue() && !$formField->runValidation($error)) {
							$errors[] = $error;
							break;
						}
					}

					// Passwords don't match
					if($password && $password != $confirmedPassword) {
						$errors[] = getLang('CustomerPasswordConfirmError');
					}

					// If there's a password and an email then we're registering an
					// account. Make sure the email address isn't already in use
					// by another customer.
					if($email && $password && getClass('ISC_CUSTOMER')->accountWithEmailAlreadyExists($email)) {
						$errors[] = getLang('CustomerEmailNotUnique');
					}

					if(!empty($errors)) {
						$this->sendEditOrderResponse(array(
							'stateTransition' => 'customerDetailsInvalid',
							'errors' => $errors
						));
					}

					$quote->setCustomerId(0);
					if(!empty($_POST['accountCustomerGroup'])) {
						$quote->setCustomerGroupId($_POST['accountCustomerGroup']);
					}
				}
				// Verify a valid customer was selected for the order
				else {
					if(empty($_POST['customerId'])) {
						$this->sendEditOrderResponse(array(
							'stateTransition' => 'customerDetailsInvalid',
							'errors' => array(
								getLang('OrderInvalidCustomer')
							)
						));
					}
					$customerId = $_POST['customerId'];
					$customer = getClass('ISC_CUSTOMER')->getCustomerInfo($customerId);
					if(!$customer) {
						$this->sendEditOrderResponse(array(
							'stateTransition' => 'customerDetailsInvalid',
							'errors' => array(
								getLang('OrderInvalidCustomer')
							)
						));
					}

					$quote->setCustomerId($customerId);
					$quote->setCustomerGroupId($customer['custgroupid']);
				}

				$saveAddress = false;

				// If the "save billing address" option is ticked, and this order is for a new
				// customer where an account is being created, or for an existing customer and
				// an existing address isn't being used, then the address can be saved.
				if((bool)Interspire_Request::post('saveBillingAddress', false) && (($_POST['orderFor'] == 'new' && $email && $password) ||
					$_POST['orderFor'] == 'customer' || $_POST['orderFor'] == 'dontchange')) {
						$saveAddress = true;
				}

				$billingCustomFields = array();

				$billingFormFields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_BILLING, true);
				foreach($billingFormFields as $formFieldId => $formField) {
					// All fields are optional on the order management page, so only validate
					// when there is a value.
					$error = '';
					if($formField->getValue() && !$formField->runValidation($error)) {
						$errors[] = $error;
					}

					if(!$formField->record['formfieldprivateid']) {
						$billingCustomFields[$formFieldId] = $formField->getValue();
					}
				}

				require ISC_BASE_PATH . '/lib/addressvalidation.php';
				$billingAddressArray = convertAddressFieldsToArray($billingFormFields);

				// Actually set the billing address on the quote
				$quote->getBillingAddress()
					->setAddressByArray($billingAddressArray)
					->setEmail($email)
					->setCustomFields($billingCustomFields)
					->setSaveAddress($saveAddress);

				// Send the response back
				$response = array(
					'stateTransition' => 'customerDetailsOk',
					'billingDetailsSummary' => $this->generateBillingDetailsSummary($quote),
				);

				$response['billingEmailAddress'] = $email;
				$response['itemsTable'] = GetClass('ISC_ADMIN_ORDERS')->generateEditOrderItemsTable($quote);
				$response['isDigital'] = $quote->isDigital();

				$this->sendEditOrderResponse($response);
			} catch (ISC_QUOTE_EXCEPTION $exception) {
				$this->sendEditOrderResponse(array(
					'stateTransition' => 'customerDetailsInvalid',
					'errors' => array(
						$exception->getMessage(),
					),
				));
			}
		}

		protected function generateBillingDetailsSummary(ISC_QUOTE $quote)
		{
			return $this->template->render('order.form.summary.billing.tpl', array(
				'address' => $quote->getBillingAddress(),
			));
		}

		protected function generateShippingDetailsSummary(ISC_QUOTE $quote)
		{
			return $this->template->render('order.form.summary.shipping.tpl', array(
				'quote' => $quote,
			));
		}

		protected function editOrderApplyCouponCodeAction()
		{
			if(empty($_POST['quoteSession']) || empty($_POST['couponGiftCertificate'])) {
				exit;
			}

			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_POST['quoteSession']);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse();
			}

			// Were we passed a gift certificate code?
			$code = trim($_POST['couponGiftCertificate']);

			if (ISC_CHECKOUT::isCertificateCode($code)) {
				$quote->applyGiftCertificate($code);
			} else {
				$quote->applyCoupon($code);
			}

			$response = array(
				'summaryTable' => $this->generateOrderFormSummaryTable($quote),
				'highlight' => '.orderFormSummaryTable',
			);

			$this->sendEditOrderResponse($response);
		}

		protected function editOrderRemoveCouponAction()
		{
			$couponId = (int)Interspire_Request::post('couponId');

			if (!$couponId) {
				exit;
			}

			$errors = array();
			/** @var ISC_QUOTE */
			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_POST['quoteSession']);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse();
			}

			$quote->removeCouponById($couponId);

			$response = array(
				'summaryTable' => $this->generateOrderFormSummaryTable($quote),
				'highlight' => '.orderFormSummaryTable',
			);

			$this->sendEditOrderResponse($response);
		}

		protected function editOrderRemoveGiftCertificateAction()
		{
			$giftCertificateId = (int)Interspire_Request::post('giftCertificateId');

			if (!$giftCertificateId) {
				exit;
			}

			$errors = array();
			/** @var ISC_QUOTE */
			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_POST['quoteSession']);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse();
			}

			$quote->removeGiftCertificateById($giftCertificateId);

			$response = array(
				'summaryTable' => $this->generateOrderFormSummaryTable($quote),
				'highlight' => '.orderFormSummaryTable',
			);

			$this->sendEditOrderResponse($response);
		}

		protected function editOrderSaveAction()
		{
			if (empty($_POST['quoteSession'])) {
				exit;
			}

			$quoteSession = $_POST['quoteSession'];

			/** @var ISC_QUOTE */
			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($quoteSession);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse('saveError');
			}

			try {
				$quote->setCustomerMessage(Interspire_Request::post('customerMessage'));
				$quote->setStaffNotes(Interspire_Request::post('staffNotes'));

				$entity = new ISC_ENTITY_ORDER;

				$currency = GetDefaultCurrency();
				$order = array(
					'ordcurrencyid' => $currency['currencyid'],
					'ordcurrencyexchangerate' => $currency['currencyexchangerate'],
					'ordipaddress' => getIp(),
					'extraInfo' => array(),
					'quote' => $quote,
				);

				$createAccount = false;

				// process customer details to see if an account should be made
				if (Interspire_Request::post('orderFor') == 'new') {
					// this really needs to be split off into another method because it's done both at the front end checkout, in save billing, and in here! -ge
					$password = '';
					$confirmedPassword = '';
					$email = '';
					$accountFormFields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, true);
					$accountCustomFields = array();
					foreach($accountFormFields as $formFieldId => $formField) {
						$formFieldPrivateId = $formField->record['formfieldprivateid'];

						if (!$formFieldPrivateId) {
							$accountCustomFields[$formFieldId] = $formField->getValue();
						} else if($formFieldPrivateId == 'EmailAddress') {
							$email = $formField->getValue();
						} else if($formFieldPrivateId == 'Password') {
							$password = $formField->getValue();
						} else if($formFieldPrivateId == 'ConfirmPassword') {
							$confirmedPassword = $formField->getValue();
						}
					}

					// shouldn't reach this point with a valid email without all the details already being validated after step 1 > next, so go ahead and assign it to the order
					if ($email) {
						$createAccount = array(
							'addresses' => array(),
							'password' => $password,
							'customFormFields' => $accountCustomFields,
						);

						foreach ($quote->getAllAddresses() as /** @var ISC_QUOTE_ADDRESS */$address) {
							if (!$address->getSaveAddress()) {
								continue;
							}

							$customerAddress = $address->getAsArray();
							$customFields = $address->getCustomFields();
							if (!empty($customFields)) {
								$customerAddress['customFormFields'] = $customFields;

								// Shipping fields need to be mapped back to billing so they can be stored
								if ($address->getType() == ISC_QUOTE_ADDRESS::TYPE_SHIPPING) {
									$newCustomFields = array();
									$map = $GLOBALS['ISC_CLASS_FORM']->mapAddressFieldList(FORMFIELDS_FORM_SHIPPING, array_keys($customFields));
									foreach($map as $oldId => $newId) {
										$newCustomFields[$newId] = $customFields[$oldId];
									}
									$customerAddress['customFormFields'] = $newCustomFields;
								}
							}

							$createAccount['addresses'][] = $customerAddress;
						}
					}
				}

				if ($quote->getOrderId()) {
					$editing = true;
					$adding = false;

					$orderId = $quote->getOrderId();

					$existingOrder = $entity->get($orderId);
					if ($existingOrder['deleted']) {
						// don't allow saving changes for a deleted order
						$errors[] = GetLang('EditDeletedOrderError');
					} else {
						$order['orderid'] = $orderId;
						if (!$entity->edit($order)) {
							$errors[] = $entity->getError();
						}
					}
				} else {
					$editing = false;
					$adding = true;

					$order['orderpaymentmodule'] = '';

					$orderId = $entity->add($order);

					if ($orderId) {
						$quote->setOrderId($orderId);
					} else {
						$errors[] = $entity->getError();
					}
				}

				if (!empty($errors)) {
					$this->sendEditOrderResponse(array(
						'errors' => $errors,
						'stateTransition' => 'saveError',
					));
				}

				// retrieve the created/edited order record
				$order = GetOrder($orderId);

				if ($createAccount) {
					// this function doesn't return anything for error testing
					createOrderCustomerAccount($order, $createAccount);
				}

				// Process a payment
				$paymentMethod = Interspire_Request::post('paymentMethod');

				$providerSuccess = false;

				// Retrieve the payment method details
				$paymentFields = Interspire_Request::post('paymentField');
				if (!empty($paymentFields[$paymentMethod])) {
					$paymentFields = $paymentFields[$paymentMethod];
				}
				else {
					$paymentFields = array();
				}

				if ($quote->getGrandTotalWithStoreCredit() > 0 && ($adding || empty($order['ordpaymentstatus']) || empty($order['orderpaymentmodule'])) && !empty($paymentMethod)) {
					$gatewayAmount = $quote->getGrandTotalWithStoreCredit();

					$provider = null;

					// was a custom payment specified?
					if ($paymentMethod == 'custom') {
						$paymentMethodName = $paymentFields['custom_name'];
						$providerSuccess = true;
					}
					// actual payment module
					else {
						GetModuleById('checkout', $provider, $paymentMethod);
						if(is_object($provider)) {
							$paymentMethodName = $provider->GetDisplayName();

							if (method_exists($provider, 'ProcessManualPayment')) {
								// set the order token as required by various payment methods
								ISC_SetCookie('SHOP_ORDER_TOKEN', $order['ordtoken'], time() + (3600*24), true);
								// make the token immediately available
								$_COOKIE['SHOP_ORDER_TOKEN'] = $order['ordtoken'];

								// process the payment
								$result = $provider->ProcessManualPayment($order, $paymentFields);
								if ($result['result']) {
									$providerSuccess = true;
									$gatewayAmount = $result['amount'];

									FlashMessage(GetLang('OrderPaymentSuccess', array('amount' => FormatPrice($gatewayAmount), 'orderId' => $orderId, 'provider' => $paymentMethodName)), MSG_SUCCESS);
								}
								else {
									$errors[] = GetLang('OrderPaymentFail', array('orderId' => $orderId, 'provider' => $paymentMethodName, 'reason' => $result['message']));
								}
							}
							else {
								// all manual/offline methods will always be successfull
								$providerSuccess = true;
							}
						}
						else {
							// failed to get a payment module
						}
					}
				// if the grand total after minus the coupon,etc is 0 and it's adding order also the payment method is custom.
				} else if ($quote->getGrandTotalWithStoreCredit() == 0 && ($adding || empty($order['ordpaymentstatus']) || empty($order['orderpaymentmodule'])) && $paymentMethod == 'custom') {
					$paymentMethodName = $paymentFields['custom_name'];
					$providerSuccess = true;
				}

				// was payment successfull?
				if ($providerSuccess) {
					// record payment info for the order
					$updatedOrder = array(
						'orderpaymentmethod' 	=> $paymentMethodName,
						'orderpaymentmodule'	=> $paymentMethod,
					);

					$this->db->UpdateQuery("orders", $updatedOrder, "orderid = " . $orderId);

					// set appropriate status for the order
					if ($quote->isDigital()) {
						$newStatus = ORDER_STATUS_COMPLETED;
					}
					else {
						$newStatus = ORDER_STATUS_AWAITING_FULFILLMENT;
					}
					UpdateOrderStatus($orderId, $newStatus, false);

					// email invoice
					if (Interspire_Request::post('emailInvoiceToCustomer')) {
						EmailInvoiceToCustomer($orderId);
					}
				}

				if (!empty($errors)) {
					$response = array(
						'errors' => $errors,
						'stateTransition' => 'saveError',
					);
				}
				else {
					if ($editing) {
						FlashMessage(GetLang('OrderUpdated', array('orderId' => $orderId)), MSG_SUCCESS);
					} else {
						FlashMessage(GetLang('OrderCreated', array('orderId' => $orderId)), MSG_SUCCESS);
					}

					$response = array(
						'stateTransition' => 'saveOk',
					);

					// remove quote object from session after successful save and successful payment
					getClass('ISC_ADMIN_ORDERS')->deleteQuoteSession($quoteSession);
				}

				if ($adding) {
					$response['updateOrderId'] = $orderId;
				}

				$this->sendEditOrderResponse($response);
			} catch (ISC_QUOTE_EXCEPTION $exception) {
				$this->sendEditOrderResponse(array(
					'stateTransition' => 'saveError',
					'errors' => array(
						$exception->getMessage(),
					),
				));
			}
		}

		/**
		* Retrieves the next set of variation options or a total combination's details based off currently chosen variation options
		*
		*/
		private function editOrderGetVariationDetailsAction()
		{
			if(empty($_REQUEST['quoteSession']) || empty($_REQUEST['productId']) || empty($_REQUEST['options'])) {
				exit;
			}

			/** @var ISC_QUOTE */
			$quote = getClass('ISC_ADMIN_ORDERS')->getQuoteSession($_REQUEST['quoteSession']);
			if(!$quote) {
				$this->sendEditOrderNoQuoteResponse();
			}

			$customerGroupId = $quote->getCustomerGroupId();
			if (!$customerGroupId) {
				$customerGroupId = null;
			}

			$remote = new ISC_REMOTE();
			$remote->GetVariationOptions($customerGroupId);
		}

		/**
		* Since the 'quote does not exist' response is referred to so often (though, rarely triggered), it exists as it's own method.
		*
		* @param string $stateTransition optional state transition to send with response
		*/
		protected function sendEditOrderNoQuoteResponse($stateTransition = null)
		{
			$response = array(
				'errors' => array(
					GetLang('QuoteSessionNotFound'),
				),
			);

			if ($stateTransition) {
				$response['stateTransition'] = $stateTransition;
			}

			$this->sendEditOrderResponse($response);
		}

		private function sendEditOrderResponse(array $response)
		{
			if(isset($_REQUEST['ajaxFormUpload'])) {
				echo '<textarea>'.isc_html_escape(isc_json_encode($response)).'</textarea>';
				exit;
			}

			echo isc_json_encode($response);
			exit;
		}

		private function LoadRefundFormAction()
		{
			if(!isset($_REQUEST['orderid'])) {
				exit;
			}
			$orderId = $_REQUEST['orderid'];

			$GLOBALS['CurrencyToken'] = GetConfig('CurrencyToken');
			$GLOBALS['OrderId'] = (int)$orderId;
			echo $this->template->render('Snippets/OrderRefundForm.html');
			exit;
		}


		private function VoidTransactionAction()
		{
			if(!isset($_REQUEST['orderid'])) {
				exit;
			}
			$orderId = $_REQUEST['orderid'];
			$order = GetOrder($_REQUEST['orderid']);
			if(!isset($order['orderid'])) {
				exit;
			}

			$message = '';
			$provider = null;
			$paymentStatus = 2;
			$msgStatus = MSG_ERROR;
			$transactionId = trim($order['ordpayproviderid']);
			if($transactionId == '') {
				$message = GetLang('OrderTranscationIDNotFound');
			}
			elseif(!GetModuleById('checkout', $provider, $order['orderpaymentmodule'])) {
				$message = GetLang('PaymentMethodNotExist');
			}
			elseif(!$provider->IsEnabled()) {
				$message = GetLang('PaymentProviderIsDisabled');
			}
			elseif(!method_exists($provider, "DoVoid")) {
				$message = GetLang('VoidNotAvailable');
			}
			else {
				//still here, perform a delay capture
				if($provider->DoVoid($orderId, $transactionId, $message)) {

					$paymentStatus = 1;
					$msgStatus = MSG_SUCCESS;
					//update order status
					$orderStatus = ORDER_STATUS_CANCELLED;
					UpdateOrderStatus($order['orderid'], $orderStatus, true);
				}
			}

			FlashMessage($message, $msgStatus);
			$tags[] = $this->MakeXMLTag('status', $paymentStatus);
			$tags[] = $this->MakeXMLTag('message', $message, true);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}

		private function DelayedCaptureAction()
		{
			if(!isset($_REQUEST['orderid'])) {
				exit;
			}
			$orderId = $_REQUEST['orderid'];
			$order = GetOrder($_REQUEST['orderid']);
			if(!isset($order['orderid'])) {
				exit;
			}
			$message = '';
			$provider = null;
			$paymentStatus = 2;
			$msgStatus = MSG_ERROR;
			$transactionId = trim($order['ordpayproviderid']);
			if($transactionId == '') {
				$message = GetLang('OrderTranscationIDNotFound');
			}
			elseif(!GetModuleById('checkout', $provider, $order['orderpaymentmodule'])) {
				$message = GetLang('PaymentMethodNotExist');
			}
			elseif(!$provider->IsEnabled()) {
				$message = GetLang('PaymentProviderIsDisabled');
			}
			elseif(!method_exists($provider, "DelayedCapture")) {
				$message = GetLang('DelayedCaptureNotAvailable');
			}
			else {
				//still here, perform a delay capture
				if($provider->DelayedCapture($order, $message, $order['total_inc_tax'])) {
					$paymentStatus = 1;
					$msgStatus = MSG_SUCCESS;
					//update order status
					if($order['ordisdigital'] == 0 && ($order['ordtotalqty']-$order['ordtotalshipped']) > 0) {
						$orderStatus = ORDER_STATUS_AWAITING_SHIPMENT;
					} else {
						$orderStatus = ORDER_STATUS_COMPLETED;
					}

					UpdateOrderStatus($order['orderid'], $orderStatus, true);
				}
			}

			FlashMessage($message, $msgStatus);
			$tags[] = $this->MakeXMLTag('status', $paymentStatus);
			$tags[] = $this->MakeXMLTag('message', $message, true);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}

		private function SaveOrderNotesAction()
		{
			if(!isset($_REQUEST['orderId'])) {
				exit;
			}

			$order = GetOrder($_REQUEST['orderId']);
			if(!isset($order['orderid'])) {
				exit;
			}

			$orderNotes = "";
			if (isset($_REQUEST['ordnotes'])) {
				$orderNotes = $_REQUEST['ordnotes'];
			}

			$customerMessage = "";
			if (isset($_REQUEST['ordcustmessage'])) {
				$customerMessage = $_REQUEST['ordcustmessage'];
			}

			$updatedOrder = array(
				'ordnotes' => $orderNotes,
				'ordcustmessage' => $customerMessage,
				'ordlastmodified' => time()
			);

			if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery("orders", $updatedOrder, "orderid='".(int)$_REQUEST['orderId']."'")) {
				exit;
			}

			$message = sprintf(GetLang('OrderNotesSuccessMsg'), $order['orderid']);
			$tags[] = $this->MakeXMLTag('status', 1);
			$tags[] = $this->MakeXMLTag('message', $message, true);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}

		private function ViewOrderNotesAction()
		{
			if(!isset($_REQUEST['orderId']) || ! isId($_REQUEST['orderId'])) {
				exit;
			}

			// Load the order
			$order = GetOrder($_REQUEST['orderId'], null, null, true);
			if(!$order || ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $order['ordvendorid'] != $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId())) {
				exit;
			}

			$GLOBALS['OrderID'] = $order['orderid'];
			$GLOBALS['OrderNotes'] = isc_html_escape($order['ordnotes']);
			$GLOBALS['OrderCustomerMessage'] = isc_html_escape($order['ordcustmessage']);

			$this->template->assign('order', $order);
			$this->template->display('orders.notes.popup.tpl');
		}

		private function ViewCustomFieldsAction()
		{
			if(!isset($_REQUEST['orderId']) || ! isId($_REQUEST['orderId'])) {
				exit;
			}

			// Load the order
			$order = GetOrder($_REQUEST['orderId'], null, null, true);
			if(!$order || ($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() && $order['ordvendorid'] != $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId())) {
				exit;
			}

			$fields = null;
			if ($order['ordcustomfields'] !== '') {
				$fields = unserialize($order['ordcustomfields']);
			}

			$GLOBALS['OrderID'] = $order['orderid'];
			$GLOBALS['OrderCustomFieldsPopupHeading'] = sprintf(GetLang('OrderCustomFieldsPopupHeading'), $order['orderid']);
			$GLOBALS['OrderCustomFields'] = '';

			if (!is_array($fields) || empty($fields)) {
				$GLOBALS['HideCustomFields'] = 'none';
			} else {
				$GLOBALS['HideMissingCustomFields'] = 'none';

				foreach ($fields as $widgetId => $data) {
					if ($data['type'] == 'singlecheckbox') {
						$data['data'] = GetLang('Yes');
					}

					$GLOBALS['CustomFieldLabel'] = isc_html_escape($data['label']);
					$GLOBALS['CustomFieldData'] = isc_html_escape($data['data']);
					$GLOBALS['OrderCustomFields'] .= $this->template->render('Snippets/OrderCustomFields.html');
				}
			}

			$this->template->display('orders.customfields.popup.tpl');
		}

		public function GetShipmentQuickViewAction()
		{
			if(!isset($_REQUEST['shipmentId'])) {
				exit;
			}

			$GLOBALS['ISC_CLASS_ADMIN_SHIPMENTS'] = GetClass('ISC_ADMIN_SHIPMENTS');
			echo $GLOBALS['ISC_CLASS_ADMIN_SHIPMENTS']->GetShipmentQuickView($_REQUEST['shipmentId']);
		}

		/**
		 * Create a shipment of one or more items from an order.
		 */
		public function CreateShipmentAction()
		{
			$GLOBALS['ISC_CLASS_ADMIN_SHIPMENTS'] = GetClass('ISC_ADMIN_SHIPMENTS');
			$GLOBALS['ISC_CLASS_ADMIN_SHIPMENTS']->CreateShipment();
		}

		/**
		 * Save a shipment of one or more items from an order.
		 */
		public function SaveNewShipmentAction()
		{
			$GLOBALS['ISC_CLASS_ADMIN_SHIPMENTS'] = GetClass('ISC_ADMIN_SHIPMENTS');
			$GLOBALS['ISC_CLASS_ADMIN_SHIPMENTS']->SaveNewShipment();
		}

		/**
		 * View the details for gift wrapping for a particular item.
		 */
		public function viewGiftWrappingDetailsAction()
		{
			if(!isset($_REQUEST['orderprodid']) || !IsId($_REQUEST['orderprodid'])) {
				exit;
			}

			$query = "
				SELECT *
				FROM [|PREFIX|]order_products
				WHERE orderprodid='".(int)$_REQUEST['orderprodid']."'
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$orderProduct = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			if(!isset($orderProduct['ordprodid']) || !$orderProduct['ordprodwrapname']) {
				exit;
			}

			$GLOBALS['ProductName'] = isc_html_escape($orderProduct['ordprodname']);
			$GLOBALS['ProductQuantity'] = $orderProduct['ordprodqty'];
			$GLOBALS['WrapName'] = isc_html_escape($orderProduct['ordprodwrapname']);

			$wrapping = $orderProduct['wrapping_cost_ex_tax'];
			if(getConfig('taxDefaultTaxDisplayOrders') == TAX_PRICES_DISPLAY_INCLUSIVE) {
				$wrapping = $orderProduct['wrapping_cost_inc_tax'];
			}

			$GLOBALS['WrapPrice'] = FormatPrice($wrapping);
			if($orderProduct['ordprodwrapmessage']) {
				$GLOBALS['WrapMessage'] = nl2br(isc_html_escape($orderProduct['ordprodwrapmessage']));
			}
			else {
				$GLOBALS['HideWrapMessage'] = 'display: none';
			}

			$this->template->display('order.viewwrapping.tpl');
		}

		private function updateMultiOrderStatusRequestAction()
		{
			$success = (int)@$_REQUEST['success'];
			$failed = (int)@$_REQUEST['failed'];
			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]order_status WHERE statusid='" . $GLOBALS['ISC_CLASS_DB']->Quote(@$_REQUEST['statusId']) . "'");

			if (isId(@$_REQUEST['orderId']) && isId(@$_REQUEST['statusId']) && ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) && UpdateOrderStatus($_REQUEST['orderId'], $_REQUEST['statusId'])) {
				echo '1';
				$success++;
			} else {
				echo '0';
				$failed++;
			}

			$message = sprintf(GetLang('OrderUpdateStatusReport'), $success, $row['statusdesc']);
			if ($failed) {
				$message .= sprintf(GetLang('OrderUpdateStatusReportFail'), $failed);
			}

			MessageBox($message, MSG_SUCCESS);
			exit;
		}

		public function loadOrderProductFieldsDataAction()
		{
			$GLOBALS['ISC_CLASS_ADMIN_ORDERS'] = GetClass('ISC_ADMIN_ORDERS');
			$GLOBALS['ISC_CLASS_ADMIN_ORDERS']->LoadOrderProductFieldsFullView();
		}

		private function restoreOrderActionHandler ($orderId)
		{
			if (!$this->auth->HasPermission(AUTH_Undelete_Orders)) {
				return array(
					'success' => false,
				);
			}

			$orderId = (int)$orderId;
			if (!$orderId) {
				return array(
					'success' => false,
				);
			}

			$order = GetOrder($orderId, false, false, true);
			if (!$order) {
				return array(
					'success' => false,
				);
			}

			$entity = new ISC_ENTITY_ORDER;
			if (!$entity->undelete($orderId)) {
				return array(
					'success' => false,
				);
			}

			FlashMessage(GetLang('iphoneRestoreOrderSuccess', array(
				'orderId' => $orderId,
			)), MSG_SUCCESS);

			return array(
				'success' => true,
			);
		}

		protected function restoreOrderAction ()
		{
			echo isc_json_encode($this->restoreOrderActionHandler(Interspire_Request::post('orderId', 0)));
			exit;
		}
	}
