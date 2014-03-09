<?php
/**
 * The checkout process class.
 *
 * Handles everything to do with the checkout process.
 */
class ISC_CHECKOUT
{
	/**
	 * Handle the incoming page request.
	 */
	public function HandlePage()
	{
		$action = "";
		
		if(GetConfig('StoreClosed') == 1 || (GetConfig('UseStoreHours') == 1 && WorkingAfterHours()))
		{
			header('Location: '.GetConfig('AppPath').'/storeclosed.php');
		}
	
		if (isset($_REQUEST['action'])) {
			$action = isc_strtolower($_REQUEST['action']);
		}

		if($action == 'gateway_ping') {
			$this->GatewayPing();
			exit;
		}
		else if($action == 'set_external_checkout') {
			$this->SetExternalCheckout();
			exit;
		}

		// Check that there is items in the cart and store purchasing is allowed.
		// If cart is empty or purchasing is disabled or prices are hidden (inferring purchasing disabled), redirect back to the cart page.
		if($this->getQuote()->getNumItems() == 0 || !(bool)GetConfig('AllowPurchasing') || !(bool)GetConfig('ShowProductPrice')) {
			if(!isset($_GET['optimizer'])) {
				header('Location: '.GetConfig('AppPath').'/cart.php');
				exit;
			}
		}

		switch($action) {
			case "process_gateway_callback":
				$this->ProcessGatewayCallBack();
				break;
			case "process_payment":
				$this->ProcessOrderPayment();
				break;
			case "pay_for_order": {
				$this->PayForOrder();
				break;
			}
			case "save_biller": {
				$this->SaveBillingAddress();
				break;
			}
			case "choose_billing_address": {
				$this->ChooseBillingAddress();
				break;
			}
			case "confirm_order": {
				$this->ConfirmOrder();
				break;
			}
			case "save_shipper": {
				$this->SaveShippingProvider();
				break;
			}
			case 'save_multiple_shipping_addresses':
				$this->SaveMultipleShippingAddresses();
				break;
			case "choose_shipping_address":
				$this->ChooseShippingAddress();
				break;
			case "choose_shipper": {
				$this->ChooseShippingProvider();
				break;
			}
			case "removegiftcertificate": {
				$this->RemoveGiftCertificate();
				break;
			}
			case 'multiple':
				$this->BeginMultipleAddressCheckout();
				break;
			case "checkout":
				$this->Checkout();
				break;
			default: {
				// If we're performing an express checkout, show that
				if(GetConfig('CheckoutType') == 'single' && $this->SinglePageCheckoutSupported()) {
					$this->ExpressCheckout();
				}
				else {
					$this->Checkout();
				}
			}
		}
	}

	/**
	 * Begin the multiple shipping address checkout process.
	 */
	private function BeginMultipleAddressCheckout()
	{
		$this->getQuote()->setIsSplitShipping(true);
		$this->Checkout();
	}

	/**
	 * Checks if the current visitor's browser supports the single page checkout.
	 *
	 * @return boolean True if supported, false if not.
	 */
	public function SinglePageCheckoutSupported()
	{
		$agent = '';
		if(isset($_SERVER['HTTP_USER_AGENT'])) {
			$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
		}

		// iPhone
		if(strpos($agent, 'safari') !== false && strpos($agent, 'mobile') !== false) {
			return false;
		}

		// Windows Mobile
		else if(strpos($agent, 'windows ce') !== false) {
			return true;
		}

		// Opera Mini & Opera Mobile
		else if(strpos($agent, 'opera mini') !== false || strpos($agent, 'opera mobile') !== false) {
			return true;
		}

		return true;
	}

	/**
	 * Begin the checkout process.
	 */
	private function Checkout()
	{
		// ensure products are in stock
		$this->CheckStockLevels();

		// If the customer is signed in, then the first step of the checkout is actually the choose billing address page so show that
		if(CustomerIsSignedIn()) {
			$this->ChooseBillingAddress();
			return;
		}

		$_SESSION['CHECKOUT']['CHECKOUT_TYPE'] = 'normal';

		if(isset($_REQUEST['bad_login']) && $_REQUEST['bad_login'] == 1) {
			$GLOBALS['LoginMessage'] = getLang('BadLoginDetails');
			$GLOBALS['MessageClass'] = 'ErrorMessage';
		}
		else {
			$GLOBALS['HideLoginMessage'] = 'none';
		}

		// Otherwise, we need to show the login page for checking out
		if(GetConfig('GuestCheckoutEnabled') && (!isset($_REQUEST['action']) || $_REQUEST['action'] != 'multiple')) {
			$GLOBALS['HideCheckoutRegistrationRequired'] = 'display: none';
		}
		else {
			$GLOBALS['HideCheckoutGuest'] = 'display: none';
		}

		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName').' - '.GetLang('Checkout'));
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('checkout');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}

	/**
	 * Start the express checkout process.
	  */
	private function ExpressCheckout()
	{
		// ensure products are in stock
		$this->CheckStockLevels();

		$_SESSION['CHECKOUT']['CHECKOUT_TYPE'] = 'express';

		// Begin by setting some defaults
		$GLOBALS['ExpressCheckoutSignedIn']		= 0;
		$GLOBALS['ExpressCheckoutDigitalOrder'] = 0;

		$GLOBALS['ExpressCheckoutHideAccountDetails'] 	 = 'display: none';
		$GLOBALS['ExpressCheckoutHideShippingAddress'] 	 = 'display: none';
		$GLOBALS['ExpressCheckoutHideShippingProviders'] = 'display: none';
		$GLOBALS['ExpressCheckoutHidePaymentDetails']	 = 'display: none';

		$checkoutSteps = array(
			'AccountDetails' => 1,
			'BillingAddress' => 2,
			'ShippingAddress' => 3,
			'ShippingProvider' => 4,
			'Confirmation' => 5,
			'PaymentDetails' => 6
		);

		// If the customer is signed in there are significantly fewer steps we need to complete
		if(CustomerIsSignedIn()) {
			$GLOBALS['ExpressCheckoutSignedIn'] = 1;
			// Remove the account details step
			unset($checkoutSteps['AccountDetails']);
		}
		// Customer isn't signed in - we need to show the account details section
		else {
			$GLOBALS['ExpressCheckoutHideAccountDetails'] = '';
		}

		// If guest checkout isn't enabled, we need to hide that section
		if(!GetConfig('GuestCheckoutEnabled')) {
			$GLOBALS['HideGuestCheckoutOptions'] = 'display: none';
		}
		else {
			$GLOBALS['HideRegisteredCheckoutOptions'] = 'display: none';
		}
		
		$GLOBALS['CheckoutShippingIntro'] = GetLang('EnterShippingAddressBelow');

		// Get a list of this customers billing/shipping addresses as straight up, the billing form will be shown
		$GLOBALS['SNIPPETS']['BillingAddressStepContents'] = $this->ExpressCheckoutChooseAddress('billing');
		$GLOBALS['SNIPPETS']['ShippingAddressStepContents'] = $this->ExpressCheckoutChooseAddress('shipping');

		if($this->getQuote()->isDigital()) {
			// this is a digital order
			$GLOBALS['ExpressCheckoutDigitalOrder'] = 1;
			// Remove the shipping address & providers section?
			unset($checkoutSteps['ShippingAddress']);
			unset($checkoutSteps['ShippingProvider']);
		} else {
			// this is a physical order - we need to ask for the shipping address
			$GLOBALS['ExpressCheckoutHideShippingAddress'] = '';
			$GLOBALS['ExpressCheckoutHideShippingProviders'] = '';
		}

		// Now calculate the number for each of the steps
		$step = 1;
		foreach($checkoutSteps as $name => $oldStep) {
			$GLOBALS['ExpressCheckoutStep'.$name] = $step;

			// If this is the first step then we need to show it by default
			if($step == 1) {
				$GLOBALS['CollapsedStepClass'.$name] = '';
			}
			else {
				$GLOBALS['CollapsedStepClass'.$name] = 'ExpressCheckoutBlockCollapsed';
			}
			++$step;
		}

		$GLOBALS['GoToStep'] = '';
		if(isset($_SESSION['CHECKOUT']['GoToCheckoutStep'])) {
			if(!CustomerIsSignedIn()) {
					$GLOBALS['GoToStep'] = "
						$('#checkout_type_guest').attr('checked', true);
						ExpressCheckout.ChangeStep('AccountDetails');
						ExpressCheckout.GuestCheckout();
					";
			}
			switch($_SESSION['CHECKOUT']['GoToCheckoutStep']) {

					case "BillingAddress":
						$GLOBALS['GoToStep'] .= "$('#ship_to_billing').attr('checked', false);";
						break;
					 case "ShippingProvider":
							$GLOBALS['GoToStep'] .= "
								ExpressCheckout.ChangeStep('BillingAddress');
								ExpressCheckout.ChooseBillingAddress();
							";
							break;
					default:
							$GLOBALS['GoToStep'] = "";
							break;
			}
		}

		/**
		 * ID's for the custom checkout field forms
		 */
		$GLOBALS['CustomCheckoutFormNewAccount'] = FORMFIELDS_FORM_ACCOUNT;
		$GLOBALS['CustomCheckoutFormBillingAddress'] = FORMFIELDS_FORM_BILLING;
		$GLOBALS['CustomCheckoutFormShippingAddress'] = FORMFIELDS_FORM_SHIPPING;

		/**
		 * Load up any form field JS event data and any validation lang variables
		 */
		$GLOBALS['FormFieldRequiredJS'] = $GLOBALS['ISC_CLASS_FORM']->buildRequiredJS();

		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName').' - '.GetLang('ExpressCheckout'));
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('checkout_express');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		exit;
	}

	/**
	 * Generate the choose an address form for the express checkout for either a billing or shipping address.
	 *
	 * @param string The type of address fields to generate (either billing or shipping)
	 * @return string The generated address form.
	 */
	public function ExpressCheckoutChooseAddress($addressType, $buildRequiredJS=false)
	{
		$templateAddressType = $addressType;
		if($templateAddressType == 'account') {
			$templateAddressType = 'billing';
		}
		$templateUpperAddressType = ucfirst($templateAddressType);

		$GLOBALS['AddressList'] = '';

		$GLOBALS['AddressType'] = $templateAddressType;
		$GLOBALS['UpperAddressType'] = $templateUpperAddressType;
		$GLOBALS['HideCreateAddress'] = 'display: none';
		$GLOBALS['HideChooseAddress'] = 'display: none';

		$GLOBALS['CreateAccountForm'] = '';
		$country_id = GetCountryIdByName(GetConfig('CompanyCountry'));

		$selectedCountry = GetConfig('CompanyCountry');
		$selectedState = 0;

		if ($addressType == 'shipping') {
			$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_SHIPPING);
		} else if (!CustomerIsSignedIn() && $addressType == 'account') {
			$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT);
			$fields += $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_BILLING);
		} else {
			$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_BILLING);
		}

		// If the customer isn't signed in, then by default we show the create form
		if(!CustomerIsSignedIn() ) {
			$GLOBALS['HideCreateAddress'] = '';
		}
		// If the customer is logged in, load up their existing addresses
		else {
			$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
			$shippingAddresses = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerShippingAddresses();

			// If the customer doesn't have any addresses, show the creation form
			if(empty($shippingAddresses)) {
				$GLOBALS['HideChooseAddress'] = 'display: none';
				$GLOBALS['HideCreateAddress'] = '';
				$GLOBALS['AddressNewChecked'] = 'checked';
			}
			else {
				$GLOBALS['HideChooseAddress'] = '';
				$GLOBALS['AddressExistingChecked'] = 'checked';
				$addressMap = array(
					'shipfullname',
					'shipcompany',
					'shipaddress1',
					'shipaddress2',
					'shipcity',
					'shipstate',
					'shipzip',
					'shipcountry'
				);

				foreach($shippingAddresses as $address) {
					$formattedAddress = '';
					foreach($addressMap as $field) {
						if(!$address[$field]) {
							continue;
						}
						$formattedAddress .= $address[$field] .', ';
					}
					$GLOBALS['AddressSelected'] = '';

					if(isset($_SESSION['CHECKOUT']['SelectAddress'])) {
						if($_SESSION['CHECKOUT']['SelectAddress'] == $address['shipid']) {
							$GLOBALS['AddressSelected'] = ' selected="selected"';
						}
					} else if(!$GLOBALS['AddressList']) {
						$GLOBALS['AddressSelected'] = ' selected="selected"';
					}

					$GLOBALS['AddressId'] = $address['shipid'];
					$GLOBALS['AddressLine'] = isc_html_escape(trim($formattedAddress, ', '));
					$GLOBALS['AddressList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('ExpressCheckoutAddress');
				}
			}
		}

		if($addressType == 'billing') {
			$quoteAddress = getCustomerQuote()->getBillingAddress();
		}
		else {
			$quoteAddress = getCustomerQuote()->setIsSplitShipping(false)
				->getShippingAddress();
		}

		$selectedCountry = $quoteAddress->getCountryName();
		$selectedState = $quoteAddress->getStateName();
		$country_id = $quoteAddress->getCountryId();

		$quoteAddressFields = array(
			'EmailAddress' => 'getEmail',
			'FirstName' => 'getFirstName',
			'LastName' => 'getLastName',
			'CompanyName' => 'getCompany',
			'AddressLine1' => 'getAddress1',
			'AddressLine2' => 'getAddress2',
			'City' => 'getCity',
			'Zip' => 'getZip',
			'State' => 'getStateName',
			'Country' => 'getCountryName',
			'Phone' => 'getPhone',
		);

		foreach($fields as $fieldId => $formField) {
			$formFieldPrivateId = $formField->record['formfieldprivateid'];

			// Hide the leave blank label for passwords on checkout
			if($formField->getFieldType() == 'password') {
				$formField->setLeaveBlankLabel(false);
			}

			if(isset($quoteAddressFields[$formFieldPrivateId])) {
				$method = $quoteAddressFields[$formFieldPrivateId];
				$formField->setValue($quoteAddress->$method());
			}
			else {
				$value = $quoteAddress->getCustomField($fieldId);
				if($value !== false) {
					$formField->setValue($value);
				}
			}
		}

		$GLOBALS['HideSaveAddress'] = 'display: none';
		$GLOBALS['SaveAddressChecked'] = '';

		// If the customer is signed in, or creating an account they can save addresses
		if(customerIsSignedIn() || $addressType == 'account') {
			$GLOBALS['HideSaveAddress'] = '';
			if($quoteAddress->getSaveAddress() === true || $quoteAddress->getSaveAddress() === null) {
				$GLOBALS['SaveAddressChecked'] = 'checked="checked"';
			}
		}

		if($addressType == 'billing' || $addressType == 'account') {
			$GLOBALS['BillToAddressButton'] = GetLang('InvoiceToThisAddress');
			if($this->getQuote()->isDigital()) {
				$GLOBALS['UseAddressTitle'] = isc_html_escape(GetLang('InvoiceToThisAddress'));
				$GLOBALS['HideShippingOptions'] = 'display: none';
			}
			else {
				$GLOBALS['UseAddressTitle'] = isc_html_escape(GetLang('BillAndShipToAddress'));
			}
			$GLOBALS['UseExistingAddress'] = GetLang('UseExistingInvoiceAddress');
			$GLOBALS['AddNewAddress'] = GetLang('UseNewInvoiceAddress');
			$GLOBALS['ShipToBillingName'] = 'ship_to_billing';
			$GLOBALS['ShipToAddressChecked'] = 'checked="checked"';
		}
		else {
			$GLOBALS['BillToAddressButton'] = GetLang('ShipToThisAddress');
			$GLOBALS['UseAddressTitle'] = isc_html_escape(GetLang('ShipToThisAddress'));
			$GLOBALS['UseExistingAddress'] = GetLang('UseExistingShippingAddress');
			$GLOBALS['AddNewAddress'] = GetLang('UseNewShippingAddress');
			$GLOBALS['ShipToBillingName'] = 'bill_to_shipping';
			$GLOBALS['HideShippingOptions'] = 'display: none';
		}

		// We need to loop here so we can get the field Id for the state
		$countryId = GetCountryIdByName($selectedCountry);
		$stateFieldId = 0;
		$countryFieldId = 0;
		foreach($fields as $fieldId => $formField) {
			if (strtolower($formField->record['formfieldprivateid']) == 'state') {
				$stateFieldId = $fieldId;
			} else if (strtolower($formField->record['formfieldprivateid']) == 'country') {
				$countryFieldId = $fieldId;
			}
		}

		// Compile the fields. Also set the country and state dropdowns while we are here
		$GLOBALS['CompiledFormFields'] = '';

		// If checking out as a guest, the email address field also needs to be shown
		if($addressType == 'billing' && !customerIsSignedIn()) {
			$emailField = $GLOBALS['ISC_CLASS_FORM']->getFormField(FORMFIELDS_FORM_ACCOUNT, '1', '', true);
			$emailField->setValue($quoteAddress->getEmail());
			$GLOBALS['ISC_CLASS_FORM']->addFormFieldUsed($emailField);
			$GLOBALS['CompiledFormFields'] .= $emailField->loadForFrontend();
		}

		foreach($fields as $fieldId => $formField) {

			//	lowercase the formfieldprivateid for conditional matching below
			$formFieldPrivateId = strtolower($formField->record['formfieldprivateid']);

			if ($formFieldPrivateId == 'country') {
				$formField->setOptions(GetCountryListAsIdValuePairs());

				if ($selectedCountry !== '') {
					$formField->setValue($selectedCountry);
				}

				/**
				 * This is the event handler for changing the states where a country is selected
				 */
				$formField->addEventHandler('change', 'FormFieldEvent.SingleSelectPopulateStates', array('countryId' => $countryFieldId, 'stateId' => $stateFieldId));

			} else if ($formFieldPrivateId == 'state' && isId($countryId)) {
				$stateOptions = GetStateListAsIdValuePairs($countryId);
				if (is_array($stateOptions) && !empty($stateOptions)) {
					$formField->setOptions($stateOptions);
				}
				else {
					// no states for our country, we need to mark this as not required
					$formField->setRequired(false);
				}
			}

			$GLOBALS['CompiledFormFields'] .= $fields[$fieldId]->loadForFrontend() . "\n";
		}

		$GLOBALS['CompiledFormFieldJavascript'] = "\n\n" . $GLOBALS['ISC_CLASS_FORM']->buildRequiredJS(true);
		return $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('ExpressCheckoutChooseAddress');
	}

	/**
	 * Generate the order confirmation for the express checkout.
	 */
	public function GenerateExpressCheckoutConfirmation()
	{
		// The current quote cannot be finalized - don't let it proceed
		if(!$this->getQuote()->canBeFinalized()) {
			return false;
		}

		try {
			$this->getQuote()->reapplyCoupons(true);
		}
		catch(ISC_QUOTE_EXCEPTION $e) {
			$GLOBALS['CheckoutErrorMsg'] = $e->getMessage();
		}

		$this->buildOrderConfirmation();
		$confirmation = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('ExpressCheckoutConfirmation');
		return $GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate(true, $confirmation);
	}

	/**
	 * Process a ping back from a particular payment gateway.
	 */
	private function GatewayPing()
	{
		if(!isset($_REQUEST['provider'])) {
			exit;
		}

		// Invalid checkout provider passed
		if(!GetModuleById('checkout', $provider, $_REQUEST['provider'])) {
			exit;
		}

		// This gateway doesn't support a ping back/notification
		if(!method_exists($provider, 'ProcessGatewayPing')) {
			exit;
		}

		// Call the process method
		$provider->ProcessGatewayPing();
		exit;
	}

	/**
	*	Process details for a particular payment gateway inline.
	*/
	private function ProcessOrderPayment()
	{
		// ensure products are in stock
		$this->CheckStockLevels();

		$order_token = "";
		if(isset($_COOKIE['SHOP_ORDER_TOKEN'])) {
			$order_token = $_COOKIE['SHOP_ORDER_TOKEN'];
		}

		// If the order token is empty then something has gone wrong.
		if($order_token == '') {
			@ob_end_clean();
			header("Location: ".$GLOBALS['ShopPathSSL']."/checkout.php?action=confirm_order");
			die();
		}

		// Load the pending order
		$orders = LoadPendingOrdersByToken($order_token);

		if(!is_array($orders)) {
			@ob_end_clean();
			header("Location: ".$GLOBALS['ShopPathSSL']."/checkout.php?action=confirm_order");
			die();
		}

		if ($orders['status'] != ORDER_STATUS_INCOMPLETE) {
			// has this order already been completed? redirect to finish order
			@ob_end_clean();
			header("Location: ".$GLOBALS['ShopPathSSL']."/finishorder.php");
			die();
		}

		// Get the payment module
		if(!GetModuleById('checkout', $provider, $orders['paymentmodule'])) {
			@ob_end_clean();
			header("Location: ".$GLOBALS['ShopPathSSL']."/checkout.php?action=confirm_order");
			die();
		}

		$provider->SetOrderData($orders);

		if(isset($_SESSION['CHECKOUT']['ProviderListHTML']) && method_exists($provider, 'DoExpressCheckoutPayment')) {
			$provider->DoExpressCheckoutPayment();
			die();
		}

		// Does this method have it's own processing method?
		if(method_exists($provider, "ProcessPaymentForm")) {
			$result = $provider->ProcessPaymentForm();
			if($result) {
				$paymentStatus = $provider->GetPaymentStatus();
				$orderStatus = GetOrderStatusFromPaymentStatus($paymentStatus);
				if(CompletePendingOrder($order_token, $orderStatus)) {
					// Everything is fine, send the customer to the thank you page.
					redirect(getConfig('ShopPathSSL').'/finishorder.php');
				}
			}

			// Otherwise there was an error
			$this->ShowPaymentForm($provider);
		}

		// If we're still here then something from the above has gone wrong. Show the confirm page again
		redirect(getConfig('ShopPathSSL').'/checkout.php?action=confirm_order');
	}

	private function RenderExtraField($fieldNo) {
		if (GetConfig('CheckoutExtraFieldActive'.$fieldNo) == 1) {
			$return = '<div class="PL20" id="CheckoutExtraField'.$fieldNo.'" style="padding-top: 2em;">';
			$type = GetConfig('CheckoutExtraFieldType'.$fieldNo);
			GetConfig('CheckoutExtraFieldRequired'.$fieldNo) == 1 ? $required = ' *' : $required = '';
			switch ($type) {
			case "input":
				$return .= GetConfig('CheckoutExtraFieldName'.$fieldNo).': ';
				$return .= '<input type="text" id="CheckoutExtraField'.$fieldNo.'" name="CheckoutExtraField'.$fieldNo.'" size="60" value="'.GetConfig('CheckoutExtraFieldValue'.$fieldNo).'" />'.$required;
				break;
			case "text":
				$return = '<h3 style="padding-top: 2em;">'.GetConfig('CheckoutExtraFieldName'.$fieldNo).$required.'</h3>';
				$return .= '<div class="PL20" id="CheckoutExtraField'.$fieldNo.'">';
				$return .= '<textarea class="Field400" rows="6" cols="40" name="CheckoutExtraField'.$fieldNo.'">'.GetConfig('CheckoutExtraFieldValue'.$fieldNo).'</textarea>';
				break;
			case "checkbox":
				$arrayYes = array('Si', 'SI', 'Yes', 'YES', 'S', 1, 'Y');
				if (in_array(GetConfig('CheckoutExtraFieldValue'.$fieldNo), $arrayYes)) $value = 'checked ';
				else $value = 'blarg ';
				$return .= '<input type="checkbox" id="CheckoutExtraField'.$fieldNo.'" name="CheckoutExtraField'.$fieldNo.'" '.$value.'/> ';
				$return .= GetConfig('CheckoutExtraFieldName'.$fieldNo).$required;
				break;
			case "select":
				$return .= GetConfig('CheckoutExtraFieldName'.$fieldNo).': ';
				$return .= '<select id="CheckoutExtraField'.$fieldNo.'" name="CheckoutExtraField'.$fieldNo.'">';
				
				$options = explode(',', GetConfig('CheckoutExtraFieldValue'.$fieldNo));
				foreach ($options as $key => $value) {
					$return .= '<option value="'.trim($value).'">'.trim($value).'</option>';
				}
				
				$return .= '</select>'.$required;
				break;
			default:
				$return .= 'Error al cargar campo: '.$fieldNo.'. Tipo: '.$type;
				break;
			}
			$return .= '</div>';
		}
		else $return = '';
		
		return $return;
	}
	
	private function RenderCheckoutExtraFields() {
		$extraFieldsSnippet = '';
		if (GetConfig('CheckoutUseExtraFields') == 1) {
			$numExtraFields = 5;
			$extraFieldsSnippet = '<div class="CheckoutExtraFields">';
			for ($i=1;$i<=$numExtraFields;$i++) {
				$extraFieldsSnippet .= $this->RenderExtraField($i);
			}
			$extraFieldsSnippet .= '</div>';
		}
		return $extraFieldsSnippet;
	}
	
	/**
	*	Show the order confirmation page before redirecting to the payment provider
	*/
	private function ConfirmOrder()
	{
		if(isset($_SESSION['CHECKOUT']['CHECKOUT_TYPE']) && $_SESSION['CHECKOUT']['CHECKOUT_TYPE'] == 'express') {
			$redirectOnError = getConfig('ShopPath').'/checkout.php?action=express';
		}
		else {
			$redirectOnError = getConfig('ShopPath').'/checkout.php?action=checkout';
		}

		// If guest checkout is not enabled and the customer isn't signed in then send the customer
		// back to the beginning of the checkout process.
		if(!GetConfig('GuestCheckoutEnabled') && !CustomerIsSignedIn()) {
			redirect($redirectOnError);
		}

		// The current quote cannot be finalized - don't let it proceed
		if(!$this->getQuote()->canBeFinalized()) {
			redirect($redirectOnError);
		}

		// ensure products are in stock
		$this->CheckStockLevels();

		// ensure that the applied coupon still valid
		try {
			$this->getQuote()->reapplyCoupons(true);
		}
		catch(ISC_QUOTE_EXCEPTION $e) {
			$GLOBALS['CheckoutErrorMsg'] = $e->getMessage();
		}

		$GLOBALS['EnterCouponCode'] = isc_html_escape(GetLang('EnterCouponCode'));
		
		$GLOBALS['CheckoutExtraFieldsContent'] = $this->RenderCheckoutExtraFields();
		
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName') . " - " . GetLang('ConfirmYourOrder'));
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("checkout_confirm");
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}

	/**
	* Remove an applied gift certificate from this order.
	*/
	private function RemoveGiftCertificate()
	{
		$this->getQuote()
			->removeGiftCertificateById($_REQUEST['giftcertificateid']);
		$GLOBALS['CheckoutSuccessMsg'] = GetLang('GiftCertificateRemovedFromCart');
		$this->confirmOrder();
	}


	/**
	*	Save the selected shipping provider's details as a cookie
	*/
	private function SaveBillingAddress()
	{
		if(isset($_SESSION['CHECKOUT']['CHECKOUT_TYPE']) && $_SESSION['CHECKOUT']['CHECKOUT_TYPE'] == 'express') {
			$redirectOnError = getConfig('ShopPath').'/checkout.php?action=express';
		}
		else {
			$redirectOnError = getConfig('ShopPath').'/checkout.php?action=checkout';
		}

		// If guest checkout is not enabled and the customer isn't signed in then send the customer
		// back to the beginning of the checkout process.
		if(!GetConfig('GuestCheckoutEnabled') && !CustomerIsSignedIn()) {
			redirect($redirectOnError);
		}

		// If the customer isn't signed in then they've just entered an address that we need to validate
		if(!CustomerIsSignedIn()) {
			$errors = array();
			// An invalid address was entered, show the form again
			$addressDetails = $this->ValidateGuestCheckoutAddress('billing', $errors);
			if(!$addressDetails) {
				$this->ChooseBillingAddress($errors);
				return;
			}
		}
		else {
			// We've just selected an address
			if(isset($_GET['address_id'])) {
				$addressDetails = (int)$_GET['address_id'];
			}
		}

		// There was a problem saving the selected billing address
		if(!$this->SetOrderBillingAddress($addressDetails)) {
			$this->ChooseBillingAddress();
			return;
		}

		// If we're automatically creating accounts for customers then we need to save those details too
		if(!CustomerIsSignedIn() && GetConfig('GuestCheckoutCreateAccounts')) {
			$password = substr(md5(uniqid(true)), 0, 8);
			$autoAccount = 1;
			$_SESSION['CHECKOUT']['CREATE_ACCOUNT'] = 1;
			$_SESSION['CHECKOUT']['ACCOUNT_DETAILS'] = array(
				'email' => $addressDetails['shipemail'],
				'password' => $password,
				'firstname' => $addressDetails['shipfirstname'],
				'lastname' => $addressDetails['shiplastname'],
				'company' => '',
				'phone' => $addressDetails['shipphone'],
				'autoAccount' => $autoAccount
			);
		}

		if($this->getQuote()->isDigital()) {
			@ob_end_clean();
			header(sprintf("location:%s/checkout.php?action=confirm_order", $GLOBALS['ShopPath']));
		}
		else {
			// Are we shipping to the same address?
			if(isset($_POST['ship_to_billing'])) {
				if(!$this->SetOrderShippingAddress($addressDetails, true)) {
					$this->ChooseShippingAddress();
					return;
				}

				// Now they need to choose the shipping provider for their order
				@ob_end_clean();
				header("Location: ".GetConfig('ShopPath')."/checkout.php?action=choose_shipper");
				exit;
			}

			// Otherwise, we just move to the next step
			@ob_end_clean();
			header(sprintf("location:%s/checkout.php?action=choose_shipping_address", $GLOBALS['ShopPath']));
		}
		exit;
	}

	/**
	 * Set the billing address of an order either based on a passed ID or an array of billing details.
	 *
	 * @param mixed Either a billing address ID or an array containing the billing address.
	 * @return boolean True if successful, false if there was an error.
	 */
	public function SetOrderBillingAddress($address)
	{
		// The billing address attached to the quote, where the address should be stored
		$quoteBillingAddress = $this->getQuote()->getBillingAddress();

		// Is an address ID - so it needs to be fetched
		if(!is_array($address)) {
			$address = getClass('ISC_ACCOUNT')->getShippingAddress($address,
				getClass('ISC_CUSTOMER')->getCustomerId()
			);

			if(!$address) {
				return false;
			}

			// Get the customer email address, if they're logged in
			$customer = getClass('ISC_CUSTOMER')->getCustomerInfo();
			if($customer) {
				$address['shipemail'] = $customer['custconemail'];
			}

			// If the address has custom fields, load those in too
			if($address['shipformsessionid']) {
				$formFields = $GLOBALS['ISC_CLASS_FORM']->getSavedSessionData(
					$address['shipformsessionid'],
					array(),
					FORMFIELDS_FORM_ADDRESS
				);
				$quoteBillingAddress->setCustomFields($formFields);
			}
			/*
			$GUID = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT GUID FROM [|PREFIX|]intelisis_shipping_addresses WHERE shipid = "'.$address['shipid'].'"', 'GUID');
			//en SetGUID valido si es falso o vacio genera uno nuevo
			$quoteBillingAddress->setGUID($GUID);*/
		}
		else {
			if(!empty($address['customFormFields'])) {
				$quoteBillingAddress->setCustomFields($address['customFormFields']);
			}
			//$quoteBillingAddress->setGUID();
		}

		// Store the billing address in the quote
		$quoteBillingAddress->setAddressByArray($address);
		$quoteBillingAddress->setGUID();

		return true;
	}

	/**
	 * Set the shipping address of an order either based on a passed ID or an array of shipping details.
	 *
	 * @param mixed Either a shipping address ID or an array containing the shipping address.
	 * @param boolean $isBilling Set to true if the supplied address is an array, and billing address.
	 * If so, custom fields will be remapped to match.
	 * @return boolean True if successful, false if there was an error.
	 */
	public function SetOrderShippingAddress($address, $isBilling = false)
	{
		// The shipping address attached to the quote, where the address should be stored
		$quoteShippingAddress = $this->getQuote()
			->setIsSplitShipping(false)
			->getShippingAddress();

		// Is an address ID - so it needs to be fetched
		if(!is_array($address)) {
			$address = getClass('ISC_ACCOUNT')->getShippingAddress($address,
				getClass('ISC_CUSTOMER')->getCustomerId()
			);

			if(!$address) {
				return false;
			}

			// Get the customer email address, if they're logged in
			$customer = getClass('ISC_CUSTOMER')->getCustomerInfo();
			if($customer) {
				$address['shipemail'] = $customer['custconemail'];
			}

			// If the address has custom fields, load those in too
			if($address['shipformsessionid']) {
				$formFields = $GLOBALS['ISC_CLASS_FORM']->getSavedSessionData(
					$address['shipformsessionid'],
					array(),
					FORMFIELDS_FORM_ADDRESS
				);
				$quoteShippingAddress->setCustomFields($formFields);
			}
/*
			$GUID = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT GUID FROM [|PREFIX|]intelisis_shipping_addresses WHERE shipid = "'.$address['shipid'].'"', 'GUID');
			//en SetGUID valido si es falso o vacio genera uno nuevo
			$quoteShippingAddress->setGUID($GUID);*/
		}
		else {
			if(!empty($address['customFormFields'])) {
				if($isBilling == true) {
					$address['customFormFields'] =
						$this->mapBillingFieldsToShipping($address['customFormFields']);
				}
				$quoteShippingAddress->setCustomFields($address['customFormFields']);
			}
		}

		// Store the billing address in the quote
		$quoteShippingAddress->setAddressByArray($address);
		if(isset($_POST['ship_to_billing_new']) || isset($_POST['ship_to_billing'])) {
			$quoteShippingAddress->setGUID($this->getQuote()->getBillingAddress()->getGUID());
		}
		else {
			$quoteShippingAddress->setGUID();
		}
		return true;
	}

	private function mapBillingFieldsToShipping($customFields)
	{
		$newCustomFields = array();
		$map = $GLOBALS['ISC_CLASS_FORM']->mapAddressFieldList(FORMFIELDS_FORM_BILLING, array_keys($customFields));
		foreach($map as $oldId => $newId) {
			$newCustomFields[$newId] = $customFields[$oldId];
		}
		return $newCustomFields;
	}

	/**
	*	Save the selected shipping provider's details as a cookie
	*/
	private function SaveShippingProvider()
	{
		if(isset($_SESSION['CHECKOUT']['CHECKOUT_TYPE']) && $_SESSION['CHECKOUT']['CHECKOUT_TYPE'] == 'express') {
			$redirectOnError = getConfig('ShopPath').'/checkout.php?action=express';
		}
		else {
			$redirectOnError = getConfig('ShopPath').'/checkout.php?action=checkout';
		}

		// If guest checkout is not enabled and the customer isn't signed in then send the customer
		// back to the beginning of the checkout process.
		if(!GetConfig('GuestCheckoutEnabled') && !CustomerIsSignedIn()) {
			redirect($redirectOnError);
		}

		// ensure products are in stock
		$this->CheckStockLevels();

		// For each shipping address in the order, the shipping provider now needs to be saved
		$shippingAddresses = $this->getQuote()->getShippingAddresses();
		foreach($shippingAddresses as $shippingAddress) {
			$shippingAddressId = $shippingAddress->getId();
			if(!isset($_POST['shipping_provider'][$shippingAddressId])) {
				redirect($redirectOnError);
			}

			$id = $_POST['shipping_provider'][$shippingAddressId];
			$cachedShippingMethod = $shippingAddress->getCachedShippingMethod($id);
			$shippingAddress->removeCachedShippingMethods();
			if(empty($cachedShippingMethod)) {
				redirect($redirectOnError);
			}

			$shippingAddress->setShippingMethod(
				$cachedShippingMethod['price'],
				$cachedShippingMethod['description'],
				$cachedShippingMethod['module']
			);
			$shippingAddress->setHandlingCost($cachedShippingMethod['handling']);
		}

		// We've saved the shipping provider - to the next step we go!
		@ob_end_clean();
		header(sprintf("location: %s/checkout.php?action=confirm_order", $GLOBALS['ShopPath']));
		die();
	}

	/**
	 * Validate an incoming shipping/billing address.
	 *
	 * @param string The type of address to validate (billing or shipping)
	 * @param array An array of errors, passed by reference - if there are any
	 * @return array An array of information about the address if valid.
	 */
	public function ValidateGuestCheckoutAddress($type, &$errors)
	{
		$address = array();
		$errors = array();

		// for the billing address we need to validate the email address
		$email = '';
		if($type == 'billing' && !customerIsSignedIn()) {
			$emailField = $GLOBALS['ISC_CLASS_FORM']->getFormField(FORMFIELDS_FORM_ACCOUNT, '1', '', true);
			$email = $emailField->getValue();

			if($email == '' || !is_email_address($email)) {
				$errors[] = GetLang('AccountEnterValidEmail');
				return false;
			}

			// if guess checkout enabled and guess account creation on checkout is enabled and the entered email is already exist in the system
			// then we do email existance checking
			$customer = GetClass('ISC_CUSTOMER');
			if(getConfig('GuestCheckoutEnabled') && getConfig('GuestCheckoutCreateAccounts') && $customer->AccountWithEmailAlreadyExists($email)) {
				$errors[] = sprintf(GetLang('AccountEmailTaken'), isc_html_escape($email));
				return false;
			}
			$address['shipemail'] = $email;
		}

		require_once(ISC_BASE_PATH . '/lib/addressvalidation.php');

		// parse the form fields and validate them
		$errmsg = '';
		if($type == 'billing') {
			$formFieldType = FORMFIELDS_FORM_BILLING;
		}
		else {
			$formFieldType = FORMFIELDS_FORM_SHIPPING;
		}

		$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields($formFieldType, true);

		$countryFieldId = 0;
		$stateFieldId = 0;
		foreach($fields as $fieldId => $formField) {
			if($formField->record['formfieldprivateid'] == 'Country') {
				$countryFieldId = $fieldId;
			}
			else if($formField->record['formfieldprivateid'] == 'State') {
				$stateFieldId = $fieldId;
			}
		}

		// Mark the state field as being optional if there are no states in the
		// selected country.
		if ($countryFieldId && $stateFieldId) {
			$countryId = GetCountryByName($fields[$countryFieldId]->getValue());
			$stateOptions = GetStateListAsIdValuePairs($countryId);

			if (is_array($stateOptions) && !empty($stateOptions)) {
				$fields[$stateFieldId]->setOptions($stateOptions);
			}
			else {
				$fields[$stateFieldId]->setRequired(false);
			}
		}

		if (!validateFieldData($fields, $errmsg)) {
			$errors[] = $errmsg;
			return false;
		}

		$fieldMap = array(
			'FirstName' => 'firstname',
			'LastName' => 'lastname',
			'CompanyName' => 'company',
			'AddressLine1' => 'address1',
			'AddressLine2' => 'address2',
			'City' => 'city',
			'State' => 'state',
			'Country' => 'country',
			'Zip' => 'zip',
			'Phone' => 'phone',
			'Email' => 'email',
		);

		foreach($fields as $fieldId => $formField) {
			// This isn't a built in field, so save the value for later handling
			if(!$formField->record['formfieldprivateid']) {
				$address['customFormFields'][$fieldId] = $formField->getValue();
				continue;
			}
			// Disregard any fields we don't know about
			else if(!isset($fieldMap[$formField->record['formfieldprivateid']])) {
				continue;
			}

			$key = 'ship' . $fieldMap[$formField->record['formfieldprivateid']];
			$address[$key] = $formField->getValue();
		}

		return $address;
	}

	/**
	 * Save one or more selected addresses for split-shipping.
	 */
	private function SaveMultipleShippingAddresses()
	{
		//  Split shipping only works for signed in users
		if(!CustomerIsSignedIn()) {
			redirect(getConfig('ShopPath').'/checkout.php?action=checkout');
		}

		// If split shipping is not available, take the customer back to the shipping address selection page
		if(!gzte11(ISC_MEDIUMPRINT) || !GetConfig('MultipleShippingAddresses') || !isset($_POST['multiaddress'])) {
			redirect(getConfig('ShopPath').'/checkout.php?action=choose_shipping_address&type=single');
		}

		$silent = false;
		if(isset($_POST['addAnotherAddress'])) {
			$silent = true;
		}

		$customerAddresses = getClass('ISC_CUSTOMER')
			->getCustomerShippingAddresses();

		$quoteItems = $this->getQuote()->getItems();
		$addressItems = array();
		foreach($quoteItems as $item) {
			// Digital items aren't shipped
			if($item->isDigital()) {
				continue;
			}

			// If we don't have an address for this product, we need to throw back to the address
			// selection page, as they've done something dodgy.
			$quantity = $item->getQuantity();
			$itemId = $item->getId();
			for($i = 1; $i <= $quantity; ++$i) {
				$id = $itemId.'_'.$i;
				if(!isset($_POST['multiaddress'][$id]) ||
					!isset($customerAddresses[$_POST['multiaddress'][$id]]) && $silent == false) {
						redirect(GetConfig('ShopPath').'/checkout.php?action=choose_shipping_address&type=single');
				}

				$customerAddressId = $customerAddresses[$_POST['multiaddress'][$id]]['shipid'];
				if(!isset($addressItems[$customerAddressId])) {
					$addressItems[$customerAddressId] = array();
				}
				if(!isset($addressItems[$customerAddressId][$itemId])) {
					$addressItems[$customerAddressId][$itemId] = 0;
				}
				++$addressItems[$customerAddressId][$itemId];
			}
		}

		// Remove all shipping addresses on the order and start fresh
		$this->getQuote()->setIsSplitShipping(true);
		$this->getQuote()->removeAllShippingAddresses();

		$updatedItems = array();
		$first = true;
		foreach($addressItems as $addressId => $items) {
			$address = getClass('ISC_ACCOUNT')->getShippingAddress($addressId,
				getClass('ISC_CUSTOMER')->getCustomerId()
			);

			// Invalid address was selected
			if(!$address) {
				redirect(GetConfig('ShopPath').'/checkout.php?action=choose_shipping_address&type=single');
			}

			// First iteration should use existing shipping address
			if($first) {
				$quoteShippingAddress = $this->getQuote()->getShippingAddress();
				$first = false;
			}
			else {
				$quoteShippingAddress = new ISC_QUOTE_ADDRESS_SHIPPING;
				$quoteShippingAddress->setQuote($this->getQuote());
				$this->getQuote()->addShippingAddress($quoteShippingAddress);
			}

			$quoteShippingAddress->setAddressByArray($address);

			// If the address has custom fields, load those in too
			if($address['shipformsessionid']) {
				$formFields = $GLOBALS['ISC_CLASS_FORM']->getSavedSessionData(
					$address['shipformsessionid'],
					array(),
					FORMFIELDS_FORM_ADDRESS
				);
				$quoteShippingAddress->setCustomFields($formFields);
			}

			$quoteAddressId = $quoteShippingAddress->getId();
			foreach($items as $itemId => $qty) {
				$item = $this->getQuote()->getItemById($itemId);
				if(!in_array($itemId, $updatedItems)) {
					$item->setAddressId($quoteAddressId);
					$item->setQuantity($qty, false);
					$updatedItems[] = $itemId;
				}
				else {
					$newItem = clone $item;
					$newItem->setQuantity($qty, false);
					$newItem->setAddressId($quoteAddressId);
					$this->getQuote()->addItem($newItem, false);
				}
			}
		}

		// Do we need to go to the add address page?
		if(isset($_POST['addAnotherAddress'])) {
			$this_page = urlencode("checkout.php?action=choose_shipping_address");
			redirect(getConfig('ShopPath').'/account.php?action=add_shipping_address&from='.$this_page);
		}

		// OK, the shipping method has been set, move on to the next step
		redirect(getConfig('ShopPath').'/checkout.php?action=choose_shipper');
	}

	/**
	 *	Show a list of available shipping providers and let the customer choose the one to use
	 */
	public function ChooseShippingProvider()
	{
		if(isset($_SESSION['CHECKOUT']['CHECKOUT_TYPE']) && $_SESSION['CHECKOUT']['CHECKOUT_TYPE'] == 'express') {
			$redirectOnError = getConfig('ShopPath').'/checkout.php?action=express';
		}
		else {
			$redirectOnError = getConfig('ShopPath').'/checkout.php?action=checkout';
		}

		// If guest checkout is not enabled and the customer isn't signed in then send the customer
		// back to the beginning of the checkout process.
		if(!GetConfig('GuestCheckoutEnabled') && !CustomerIsSignedIn()) {
			redirect($redirectOnError);
		}

		// ensure products are in stock
		$this->CheckStockLevels();

		// Setup the default shipping error message
		$GLOBALS['ShippingError'] = GetLang("NoShippingProvidersError");

		$addressDetails = 0;

		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			// If the customer isn't signed in then they've just entered an address that we need to validate
			if(!CustomerIsSignedIn()) {
				$errors = array();
				// An invalid address was entered, show the form again
				$addressDetails = $this->ValidateGuestCheckoutAddress('shipping', $errors);
				if(!$addressDetails) {
					$this->ChooseShippingAddress($errors);
					return;
				}
			}
		}

		// We've just selected an address
		if(isset($_GET['address_id'])) {
			$addressDetails = (int)$_GET['address_id'];
		}

		if($addressDetails !== 0 && !$this->SetOrderShippingAddress($addressDetails)) {
			redirect($redirectOnError);
		}

		// Are we split shipping?
		$splitShipping = $this->getQuote()->getIsSplitShipping();

		// At this stage, the quote should have a complete shipping address. Make sure there's
		// nothing missing.
		$shippingAddresses = $this->getQuote()->getShippingAddresses();
		foreach($shippingAddresses as $shippingAddress) {
			if(!$shippingAddress->hasCompleteAddress()) {
				redirect($redirectOnError);
			}
		}

		// Now, for each shipping address, fetch available shipping quotes
		$GLOBALS['HideNoShippingProviders'] = 'none';
		$GLOBALS['ShippingQuotes'] = '';

		$hideItemList = true;
		if(count($shippingAddresses) > 1) {
			$hideItemList = false;
		}
		else {
			$splitShipping = false;
		}
		$hasTransit = false;
		$numLoopedAddresses = 0;
		$totalAddresses = count($shippingAddresses);
		foreach($shippingAddresses as $i => $shippingAddress) {
			++$numLoopedAddresses;

			if(!$splitShipping) {
				$GLOBALS['HideAddressLine'] = 'display: none';
				$GLOBALS['HideItemList'] = 'display: none';
			}
			else {
				$GLOBALS['HideAddressLine'] = '';
				$GLOBALS['HideItemList'] = '';
			}

			$GLOBALS['HideHorizontalRule'] = 'display: none';
			if($numLoopedAddresses != $totalAddresses) {
				$GLOBALS['HideHorizontalRule'] = '';
			}

			$GLOBALS['AddressId'] = $shippingAddress->getId();

			// If no methods are available, this order can't progress so show an error
			$shippingQuotes = $shippingAddress->getAvailableShippingMethods();
			if(empty($shippingQuotes)) {
				$GLOBALS['HideNoShippingProviders'] = '';
				$GLOBALS['HideShippingProviderList'] = 'none';
				$hideItemList = false;
			}

			$GLOBALS['ItemList'] = '';
			if(!$hideItemList) {
				$items = $shippingAddress->getItems();
				foreach($items as $item) {
					// Only show physical products
					if($item->isDigital() == true) {
						continue;
					}

					$GLOBALS['ProductQuantity'] = $item->getQuantity();
					$GLOBALS['ProductName'] = isc_html_escape($item->getName());

					$GLOBALS['HideGiftWrapping'] = 'display: none';
					$GLOBALS['HideGiftMessagePreview'] = 'display: none';
					$GLOBALS['GiftWrappingName'] = '';
					$GLOBALS['GiftMessagePreview'] = '';
					$giftWrapping = $item->getGiftWrapping();
					if($giftWrapping !== false) {
						$GLOBALS['HideGiftWrapping'] = '';
						$GLOBALS['GiftWrappingName'] = isc_html_escape($giftWrapping['wrapname']);
						if($giftWrapping['wrapmessage']) {
							if(isc_strlen($giftWrapping['wrapmessage']) > 30) {
								$giftWrapping = substr($giftWrapping['wrapmessage'], 0, 27).'...';
							}

							$GLOBALS['GiftMessagePreview'] = isc_html_escape($giftWrapping['wrapmessage']);
							$GLOBALS['HideGiftMessagePreview'] = '';
						}
					}
					$GLOBALS['ItemList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('ShippingQuoteProduct');
				}
			}

			// If no methods are available, this order can't progress so show an error
			if(empty($shippingQuotes)) {
				break;
			}

			if(!$GLOBALS['HideAddressLine']) {
				$addressLine = array(
					$shippingAddress->getFirstName().' '.$shippingAddress->getLastName(),
					$shippingAddress->getCompany(),
					$shippingAddress->getAddress1(),
					$shippingAddress->getAddress2(),
					$shippingAddress->getCity(),
					$shippingAddress->getStateName(),
					$shippingAddress->getZip(),
					$shippingAddress->getCountryName()
				);

				// Please see self::GenerateShippingSelect below.
				$addressLine = array_filter($addressLine, array($this, 'FilterAddressFields'));
				$GLOBALS['AddressLine'] = isc_html_escape(implode(', ', $addressLine));
			}
			else {
				$GLOBALS['AddressLine'] = '';
			}

			// Now build a list of the actual available quotes
			$GLOBALS['ShippingProviders'] = '';
			foreach($shippingQuotes as $quoteId => $method) {
				$price = getClass('ISC_TAX')->getPrice(
					$method['price'],
					getConfig('taxShippingTaxClass'),
					getConfig('taxDefaultTaxDisplayCart'),
					$shippingAddress->getApplicableTaxZone()
				);
				$GLOBALS['ShippingProvider'] = isc_html_escape($method['description']);
				$GLOBALS['ShippingPrice'] = CurrencyConvertFormatPrice($price);
				$GLOBALS['ShipperId'] = $quoteId;
				$GLOBALS['ShippingData'] = $GLOBALS['ShipperId'];

				if(isset($method['transit'])) {
						$hasTransit = true;

					$days = $method['transit'];

					if ($days == 0) {
						$transit = GetLang("SameDay");
					}
					else if ($days == 1) {
						$transit = GetLang('NextDay');
					}
					else {
						$transit = sprintf(GetLang('Days'), $days);
					}

					$GLOBALS['TransitTime'] = $transit;
					$GLOBALS['TransitTime'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('CartShippingTransitTime');
				}
				else {
					$GLOBALS['TransitTime'] = "";
				}
				$GLOBALS['ShippingProviders'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ShippingProviderItem");
			}

			// Add it to the list
			$GLOBALS['ShippingQuotes'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('ShippingQuote');
		}

		if ($hasTransit) {
			$GLOBALS['DeliveryDisclaimer'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('CartShippingDeliveryDisclaimer');
		}

		// Show the list of available shipping providers
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName') . " - " . GetLang('ChooseShippingProvider'));
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("checkout_shipper");
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}

	/**
	 * Show the page allowing a customer to choose the shipping address for their order.
	 *
	 * @param array Optionally, an array of errors that have occurred and need to be shown.
	 */
	private function ChooseShippingAddress($errors = array())
	{
		// If we're coming here from a post request and we're not logged in then we've just chosen how we're checking out
		if(empty($errors) && $_SERVER['REQUEST_METHOD'] == "POST" && !CustomerIsSignedIn()) {

			// Are we logging in?
			if(isset($_REQUEST['login_email'])) {
				$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
				if(!$GLOBALS['ISC_CLASS_CUSTOMER']->CheckLogin(true)) {
					@ob_end_clean();
					header("Location: ".GetConfig('ShopPath').'/checkout.php?action=checkout&bad_login=1');
					exit;
				}
			}
			// Perhaps we've chosen to create an account?
			else if(isset($_REQUEST['checkout_type']) && $_REQUEST['checkout_type'] == 'register') {
				@ob_end_clean();
				header("Location: ".GetConfig('ShopPath').'/login.php?action=create_account&checking_out=yes');
				exit;
			}
			// Otherwise, we're trying to checkout as a guest
		}

		if(isset($_SESSION['CHECKOUT']['CHECKOUT_TYPE']) && $_SESSION['CHECKOUT']['CHECKOUT_TYPE'] == 'express') {
			$redirectOnError = getConfig('ShopPath').'/checkout.php?action=express';
		}
		else {
			$redirectOnError = getConfig('ShopPath').'/checkout.php?action=checkout';
		}

		// If guest checkout is not enabled and the customer isn't signed in then send the customer
		// back to the beginning of the checkout process.
		if(!GetConfig('GuestCheckoutEnabled') && !CustomerIsSignedIn()) {
			redirect($redirectOnError);
		}

		// If it's an order with only intangible products we can skip this step
		if ($this->getQuote()->isDigital()) {
			// Skip this step because all products are downloadable
			@ob_end_clean();
			header(sprintf("Location: %s/checkout.php?action=confirm_order", $GLOBALS['ShopPath']));
			die();
		}

		$GLOBALS['HideErrors'] = 'display: none';
		if(!empty($errors)) {
			$GLOBALS['ErrorMessage'] = implode('<br />', $errors);
			$GLOBALS['SavedAddress'] = $_POST;
			$GLOBALS['HideIntro'] = 'display: none';
			$GLOBALS['HideErrors'] = '';
		}
		else if(isset($_SESSION['CHECKOUT']['SHIPPING_ADDRESS']) && is_array($_SESSION['CHECKOUT']['SHIPPING_ADDRESS'])) {
			$GLOBALS['SavedAddress'] = $_SESSION['CHECKOUT']['SHIPPING_ADDRESS'];
		}

		$GLOBALS['FromURL'] = urlencode("checkout.php?action=choose_shipping_address");
		$GLOBALS['ShipAddressButtonText'] = isc_html_escape(GetLang('ShipToThisAddress'));
		$GLOBALS['ShipAddressButtonText_JS'] = isc_json_encode(GetLang('ShipToThisAddress'));
		$GLOBALS['ShippingFormAction'] = "choose_shipper";

		// If the cart is empty, take them back to it
		if ($this->getQuote()->getNumItems() == 0) {
			@ob_end_clean();
			header(sprintf("Location: %s/cart.php", $GLOBALS['ShopPath']));
			die();
		}

		// If the customer isn't signed in then they're performing a guest checkout so they don't see a list of addresses, but actually
		// the shipping address form
		$GLOBALS['HidePanels'][] = 'ChooseBillingAddress';

		if(!CustomerIsSignedIn()) {
			$GLOBALS['HidePanels'][] = 'ChooseShippingAddress';
			$GLOBALS['HideShippingOptions'] = 'display: none';
			$GLOBALS['CreateAccountForm'] = '';

			$GLOBALS['CheckoutShippingTitle'] = GetLang('ShippingDetails');
			$GLOBALS['CheckoutShippingIntro'] = GetLang('EnterShippingAddressBelow');
		}
		else {
			// Hide the address entry panel
			$GLOBALS['HidePanels'][] = 'CheckoutNewAddressForm';

			// Do they have a shipping address stored in the system?
			// If not we will ask them to create one

			if ($this->GetNumShippingAddresses() == 0) {
				// Take them to add a shipping address
				$this_page = urlencode("checkout.php?action=checkout");
				@ob_end_clean();
				header(sprintf("Location: %s/account.php?action=add_shipping_address&from=%s", $GLOBALS['ShopPath'], $this_page));
				die();
			}

			$GLOBALS['CheckoutShippingTitle'] = GetLang('ChooseShippingAddress');
			$GLOBALS['CheckoutShippingIntro'] = sprintf("%s <a href='%s/account.php?action=add_shipping_address&amp;address_type=" . FORMFIELDS_FORM_SHIPPING . "&amp;from=%s'>%s</a>", GetLang('ChooseShippingAddressIntro1'), $GLOBALS['ShopPath'], $GLOBALS['FromURL'], GetLang('ChooseShippingAddressIntro2'));
			$GLOBALS['CheckoutMultiShippingIntro'] = sprintf("%s <a href='%s/account.php?action=add_shipping_address&amp;address_type=" . FORMFIELDS_FORM_SHIPPING . "&amp;from=%s' onclick='Checkout.MultiAddNewAddress(\"shipping\"); return false;'>%s</a>", GetLang('ChooseMultiShippingAddressIntro1'), $GLOBALS['ShopPath'], $GLOBALS['FromURL'], GetLang('ChooseMultiShippingAddressIntro2'));
		}

		$GLOBALS['CustomFieldSelectedAddressType'] = FORMFIELDS_FORM_SHIPPING;

		// Show the list of available shipping addresses
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName') . " - " . GetLang('ChooseShippingAddress'));
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("checkout_address");
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}

	/**
	 * Show the page allowing a customer to choose the billing address for their order.
	 *
	 * @param array Optionally, an array of errors that have occurred and need to be shown.
	 */
	private function ChooseBillingAddress($errors=array())
	{
		// If we're coming here from a post request and we're not logged in then we've just chosen how we're checking out
		if(empty($errors) && $_SERVER['REQUEST_METHOD'] == "POST" && !CustomerIsSignedIn()) {

			// Are we logging in?
			if(isset($_REQUEST['login_email'])) {
				$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
				if(!$GLOBALS['ISC_CLASS_CUSTOMER']->CheckLogin(true)) {
					@ob_end_clean();
					header("Location: ".GetConfig('ShopPath').'/checkout.php?action=checkout&bad_login=1');
					exit;
				}
			}
			// Perhaps we've chosen to create an account?
			else if(isset($_REQUEST['checkout_type']) && $_REQUEST['checkout_type'] == 'register') {
				@ob_end_clean();
				header("Location: ".GetConfig('ShopPath').'/login.php?action=create_account&checking_out=yes');
				exit;
			}
			// Otherwise, we're trying to checkout as a guest
		}

		if(isset($_SESSION['CHECKOUT']['CHECKOUT_TYPE']) && $_SESSION['CHECKOUT']['CHECKOUT_TYPE'] == 'express') {
			$redirectOnError = getConfig('ShopPath').'/checkout.php?action=express';
		}
		else {
			$redirectOnError = getConfig('ShopPath').'/checkout.php?action=checkout';
		}

		// If guest checkout is not enabled and the customer isn't signed in then send the customer
		// back to the beginning of the checkout process.
		if(!GetConfig('GuestCheckoutEnabled') && !CustomerIsSignedIn()) {
			redirect($redirectOnError);
		}

		$GLOBALS['HideErrors'] = 'display: none';
		if(!empty($errors)) {
			$GLOBALS['ErrorMessage'] = implode('<br />', $errors);
			$GLOBALS['SavedAddress'] = $_POST;
			$GLOBALS['HideIntro'] = 'display: none';
			$GLOBALS['HideErrors'] = '';
		}
		else if(isset($_SESSION['CHECKOUT']['BILLING_ADDRESS']) && is_array($_SESSION['CHECKOUT']['BILLING_ADDRESS'])) {
			$GLOBALS['SavedAddress'] = $_SESSION['CHECKOUT']['BILLING_ADDRESS'];
			if(isset($_SESSION['CHECKOUT']['ACCOUNT_EMAIL'])) {
				$GLOBALS['SavedAddress']['account_email'] = $_SESSION['CHECKOUT']['ACCOUNT_EMAIL'];
			}
		}

		$addressVars = array(
			'account_email' => 'AccountEmail',
		);
		foreach($addressVars as $addressField => $formField) {
			if(isset($GLOBALS['SavedAddress'][$addressField])) {
				$GLOBALS[$formField] = isc_html_escape($GLOBALS['SavedAddress'][$addressField]);
			}
		}


		$GLOBALS['FromURL'] = urlencode("checkout.php?action=choose_billing_address");
		$GLOBALS['ShipAddressButtonText'] = isc_html_escape(GetLang('InvoiceToThisAddress'));
		$GLOBALS['ShipAddressButtonText_JS'] = isc_json_encode(GetLang('InvoiceToThisAddress'));
		$GLOBALS['ShippingFormAction'] = "save_biller";

		// If the customer isn't signed in then they're performing a guest checkout so they don't see a list of addresses, but actually
		// the shipping address form
		$GLOBALS['HidePanels'][] = 'ChooseShippingAddress';
		$GLOBALS['ShipToBillingName'] = 'ship_to_billing';
		if(!CustomerIsSignedIn()) {
			$GLOBALS['HidePanels'][] = 'ChooseBillingAddress';
			$GLOBALS['CheckoutShippingTitle'] = GetLang('BillingDetails');
			$GLOBALS['CheckoutShippingIntro'] = GetLang('EnterBillingAddressBelow');
			$GLOBALS['ShipAddressButtonText'] = isc_html_escape(GetLang('BillAndShipToAddress'));
			$GLOBALS['ShipAddressButtonText_JS'] = isc_json_encode(GetLang('BillAndShipToAddress'));
		}
		else {
			// Hide the address entry panel
			$GLOBALS['HidePanels'][] = 'CheckoutNewAddressForm';

			// Do they have a shipping address stored in the system?
			// If not we will ask them to create one

			if ($this->GetNumShippingAddresses() == 0) {
				// Take them to add a shipping address
				$this_page = urlencode("checkout.php?action=choose_billing_address");
				@ob_end_clean();
				header(sprintf("Location: %s/account.php?action=add_shipping_address&from=%s", $GLOBALS['ShopPath'], $this_page));
				die();
			}

			$GLOBALS['CheckoutShippingTitle'] = GetLang('ChooseInvoiceAddress');
			$GLOBALS['CheckoutShippingIntro'] = sprintf("%s <a href='%s/account.php?action=add_shipping_address&amp;from=%s'>%s</a>", GetLang('ChooseInvoiceAddressIntro1'), $GLOBALS['ShopPath'], $GLOBALS['FromURL'], GetLang('ChooseInvoiceAddressIntro2'));
		}

		if(isset($_SESSION['CART_CHANGED'])) {
			$GLOBALS['CheckoutShippingIntro'] = GetLang('CartChangedSinceCheckout');
			unset($_SESSION['CART_CHANGED']);
		}

		if($this->getQuote()->isDigital()) {
			$GLOBALS['HideShippingOptions'] = 'display: none';
			$GLOBALS['ShipAddressButtonText'] = isc_html_escape(GetLang('InvoiceToThisAddress'));
			$GLOBALS['ShipAddressButtonText_JS'] = isc_json_encode(GetLang('InvoiceToThisAddress'));
		}
		else {
			$GLOBALS['ShipToAddressChecked'] = 'checked="checked"';
		}

		// If the cart is empty, take them back to it
		if ($this->getQuote()->getNumItems() == 0) {
			@ob_end_clean();
			header(sprintf("Location: %s/cart.php", $GLOBALS['ShopPath']));
			die();
		}

		$GLOBALS['CustomFieldSelectedAddressType'] = FORMFIELDS_FORM_BILLING;

		// Show the list of available shipping addresses
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName') . " - " . GetLang('ChooseInvoiceAddress'));
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("checkout_address");
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}

	/**
	 * Return the number of shipping addresses configured for the current customer.
	 *
	 * @return int The number of shipping addresses belonging to the customer.
	 */
	private function GetNumShippingAddresses()
	{
		$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
		$query = sprintf("select count(shipid) as num from [|PREFIX|]shipping_addresses where shipcustomerid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId()));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		return $row['num'];
	}

	/**
	 * Create the pending order in the database with the customers selected payment details, etc.
	 *
	 * @return array An array containing information about what needs to be done next.
	 */
	public function SavePendingOrder()
	{
		$provider = null;
		$verifyPaymentProvider = true;
		$redirectToFinishOrder = false;
		$providerId = '';

		$pendingOrderResult = array();
		$creditUsed = 0;
		$giftCertificates = array();

		$orderTotal = $this->getQuote()->getGrandTotal();

		// store the discounted subtotal in the session for affiliate tracking
		$incTax = (getConfig('taxDefaultTaxDisplayOrders') == TAX_PRICES_DISPLAY_INCLUSIVE);
		$_SESSION['LAST_ORDER_DISCOUNTED_SUBTOTAL'] = $this->getQuote()->getDiscountedSubTotal($incTax);

		// Find out what currency we are using. We'll need this later to display their previous orders in the currency that they have selected
		$selectedCurrency = GetCurrencyById($GLOBALS['CurrentCurrency']);

		$giftCertificates = $this->getQuote()->getAppliedGiftCertificates();
		if(!empty($giftCertificates)) {
			$badCertificates = array();
			$remainingBalance = 0;
			$totalWithoutCertificates =
				$this->getQuote()->getGrandTotalWithoutGiftCertificates();
			$giftCertificateAmount =
				$this->getQuote()->getGiftCertificateTotal();
			getClass('ISC_GIFTCERTIFICATES')
				->giftCertificatesApplicableToOrder(
					$totalWithoutCertificates,
					$giftCertificates,
					$remainingBalance,
					$badCertificates);

			// One or more gift certificates were invalid so this order is now invalid
			if(!empty($badCertificates)) {
				$badCertificatesList = '<strong>'.GetLang('BadGiftCertificates').'</strong><ul>';
				foreach($badCertificates as $code => $reason) {
					if(is_array($reason) && $reason[0] == "expired") {
						$reason = sprintf(GetLang('BadGiftCertificateExpired'), CDate($reason[1]));
					}
					else {
						$reason = GetLang('BadGiftCertificate'.ucfirst($reason));
					}
					$badCertificatesList .= sprintf("<li>%s - %s", isc_html_escape($code), $reason);
				}
				$badCertificatesList .= "</ul>";
				$pendingOrderResult = array(
					'error' => GetLang('OrderContainedInvalidGiftCertificates'),
					'errorDetails' => $badCertificatesList
				);
				return $pendingOrderResult;
			}
			// This order was entirely paid for using gift certificates but the totals don't add up
			else if($totalWithoutCertificates == $giftCertificateAmount && $remainingBalance > 0) {
				$pendingOrderResult = array(
					'error' => GetLang('OrderTotalStillRemainingCertificates')
				);
				return $pendingOrderResult;
			}
			// Order was entirely paid for using gift certificates
			else if($totalWithoutCertificates == $giftCertificateAmount) {
				$providerId = 'giftcertificate';
				$verifyPaymentProvider = false;
				$redirectToFinishOrder = true;
			}
		}

		// If the order total is 0, then we just forward the user on to the "Thank You" page and set the payment provider to ''
		if($orderTotal == 0) {
			$providerId = '';
			$verifyPaymentProvider = false;
			$redirectToFinishOrder = true;
		}

		$selected_provider = '';
		if($verifyPaymentProvider) {
			$candidate = '';
			if (isset($_POST['checkout_provider']) && $_POST['checkout_provider'] != '') {
				$candidate = $_POST['checkout_provider'];
			} else if (isset($_POST['credit_checkout_provider']) && $_POST['credit_checkout_provider'] != '') {
				// used by paypal
				$candidate = $_POST['credit_checkout_provider'];
			}

			// Check if the chosen checkout method is valid
			$providers = GetCheckoutModulesThatCustomerHasAccessTo(true);
			foreach ($providers as $p) {
				if ($p['id'] == $candidate) {
					$selected_provider = $candidate;
				}
			}

			// If there's only one payment provider, then they're paying via that
			if($selected_provider == '' && count($providers) == 1) {
				$selected_provider = $providers[0]['object']->GetId();
			}

			// Are we using our store credit?
			$customer = getClass('ISC_CUSTOMER')->getCustomerDataByToken();
			if (isset($_POST['store_credit']) && $_POST['store_credit'] == 1
				&& $customer['custstorecredit'] > 0) {
					// User has not chosen a payment provider and can't afford this order using only store credit, throw back as error
					if ($selected_provider == '' && $customer['custstorecredit'] < $orderTotal) {
						return false;
					}
					// Otherwise we can use the store credit.
					// Subtract store credit from users account and send them to the finished page
					else {
						$onlyCredit = false;
						$updateExtra = '';
						// If we're only using store credit
						$creditToUse = $orderTotal;
						if ($customer['custstorecredit'] >= $creditToUse) {
							// Set the checkout provider
							$providerId = 'storecredit';
							$verifyPaymentProvider = false;
							$redirectToFinishOrder = true;
							$creditUsed = $creditToUse;
							$onlyCredit = true;
						}
						else {
							// Using all of our store credit to pay for this order and we owe more.
							$creditUsed = $customer['custstorecredit'];
						}
					}
			}
		}

		$orderStatus = ORDER_STATUS_INCOMPLETE;

		// Now with round 2, do we still need to verify the payment provider?
		if($verifyPaymentProvider) {
			// If there's more than one provider and one wasn't selected on the order confirmation screen then there's a problem
			if ((count($providers) == 0 ||
				(count($providers) > 1 && $selected_provider == '')) &&
					!isset($_SESSION['CHECKOUT']['ProviderListHTML'])) {
					return false;
			}

			// Is the payment provider selected actually valid?
			if (!GetModuleById('checkout', $provider, $selected_provider)) {
				return false;
			}
			$providerId = $provider->GetId();
		}

		if(isset($_COOKIE['SHOP_TOKEN'])) {
			$customerToken = $_COOKIE['SHOP_TOKEN'];
		}
		else {
			$customerToken = '';
		}

		$orderComments = '';
		if(isset($_REQUEST['ordercomments'])) {
			$orderComments = $_POST['ordercomments'];
		}

		// Set up the order to be created
		$this->getQuote()
			->setAppliedStoreCredit($creditUsed)
			->setCustomerMessage($orderComments);
			;

		$newOrder = array(
			'orderpaymentmodule' => $providerId,
			'ordcurrencyid' => $selectedCurrency['currencyid'],
			'ordcurrencyexchangerate' => $selectedCurrency['currencyexchangerate'],
			'ordipaddress' => getIp(),
			'ordstatus' => $orderStatus,
			'extraInfo' => array(),

			'quote' => $this->getQuote(),
		);


		// OK, we're successful down to here - do they want to create an account? If so then assign it to
		// a session so we can create the actual record on a successful order
		if(!empty($_SESSION['CHECKOUT']['CREATE_ACCOUNT']) ||
			!customerIsSignedIn() && getConfig('GuestCheckoutCreateAccounts')) {
				$createAccount = array(
					'addresses' => array()
				);
				if(!empty($_SESSION['CHECKOUT']['CREATE_ACCOUNT'])) {
					$createAccount['password'] = $_SESSION['CHECKOUT']['CREATE_ACCOUNT']['password'];
					$createAccount['customFormFields'] = $_SESSION['CHECKOUT']['CREATE_ACCOUNT']['customFields'];
				}
				else {
					$createAccount['autoCreated'] = 1;
				}

				// Handle saving of addresses for new customers
				foreach($this->getQuote()->getAllAddresses() as $address) {
					if($address->getSaveAddress()) {
						$customerAddress = $address->getAsArray();
						$customFields = $address->getCustomFields();
						if(!empty($customFields)) {
							$customerAddress['customFormFields'] = $customFields;

							// Shipping fields need to be mapped back to billing so they can be stored
							if($address->getType() == ISC_QUOTE_ADDRESS::TYPE_SHIPPING) {
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

				$newOrder['extraInfo']['createAccount'] = $createAccount;
		}

		// Did they agree to signup to any mailing lists?
		if (isset($_POST['join_mailing_list'])) {
			$newOrder['extraInfo']['join_mailing_list'] = true;
		}

		if (isset($_POST['join_order_list'])) {
			$newOrder['extraInfo']['join_order_list'] = true;
		}

		if (isset($_POST['join_mailing_list']) || isset($_POST['join_order_list'])) {
			if (isset($_POST['mail_format_preference'])) {
				$newOrder['extraInfo']['mail_format_preference'] = (int)$_POST['mail_format_preference'];
			} else {
				$newOrder['extraInfo']['mail_format_preference'] = Interspire_EmailIntegration_Subscription::FORMAT_PREF_NONE;
			}
			$newOrder['extraInfo']['join_order_list'] = true;
		}


		if(isset($_POST['ordermessage'])) {
			$newOrder['ordermessage'] = $_POST['ordermessage'];
		} else {
			$newOrder['ordermessage'] = '';
		}
		
		if (GetConfig('CheckoutUseExtraFields') == 1) {
			$numExtraFields = 5;
			for ($i=1;$i<=$numExtraFields;$i++) {
				if (GetConfig('CheckoutExtraFieldActive'.$i)) {
					if (isset($_POST['CheckoutExtraField'.$i])) $newOrder['extraField'.$i] = $_POST['CheckoutExtraField'.$i];
				}
			}
		}

		$entity = new ISC_ENTITY_ORDER();
		$orderId = $entity->add($newOrder);

		// Failed to create the order
		if(!$orderId) {
			return false;
		}

		$order = getOrder($orderId);
		
		// Persist the pending order token as a cookie for 24 hours
		ISC_SetCookie("SHOP_ORDER_TOKEN", $order['ordtoken'], time() + (3600*24), true);
		$_COOKIE['SHOP_ORDER_TOKEN'] = $order['ordtoken'];

		// Redirecting to finish order page?
		if($redirectToFinishOrder) {
			return array(
				'redirectToFinishOrder' => true
			);
		}

		// Otherwise, the gateway want's to do something
		$orderData = LoadPendingOrdersByToken($order['ordtoken']);
		$provider->SetOrderData($orderData);

		// Is this an online payment provider? It would like to do something
		if($provider->GetPaymentType() == PAYMENT_PROVIDER_ONLINE || method_exists($provider, "ShowPaymentForm")) {
			// Call the checkout process for the selected provider
			if(method_exists($provider, "ShowPaymentForm")) {
				return array(
					'provider' => $provider,
					'showPaymentForm' => true
				);
			}
			else {
				return array(
					'provider' => $provider
				);
			}
		}
		// If an offline method, we throw them to the "Thank you for your order" page
		else {
			return array(
				'provider' => $provider
			);
		}
	}

	/**
	 * Redirect to the payment provider if one is chosen - otherwise process the payment for an order.
	 */
	private function PayForOrder()
	{
		// If guest checkout is not enabled and the customer isn't signed in then send the customer
		// back to the beginning of the checkout process.
		if(!GetConfig('GuestCheckoutEnabled') && !CustomerIsSignedIn() && !isset($_SESSION['CHECKOUT']['CREATE_ACCOUNT'])) {
			@ob_end_clean();
			header("Location: ".GetConfig('ShopPath').'/checkout.php');
			exit;
		}

		if (GetConfig('EnableOrderTermsAndConditions')==1  && !isset($_POST['AgreeTermsAndConditions'])) {
			@ob_end_clean();
			$_SESSION['REDIRECT_TO_CONFIRMATION_MSG'] = GetLang('TickArgeeTermsAndConditions');
			header("Location: ".$GLOBALS['ShopPath']."/checkout.php?action=confirm_order");
			exit;
		}

		$customerId = $_SESSION['QUOTE']->getCustomerId();
		$CustomerFormSessionId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT custformsessionid FROM [|PREFIX|]customers WHERE customerid = "'.$customerId.'"');
		$customFieldsAccount = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, false, $CustomerFormSessionId);
		$RFC = getCustomFieldByLabel($customFieldsAccount, FORMFIELDS_FORM_ACCOUNT, 'RFC');
		
		for($i=1;$i<=5;$i++){
				if(GetConfig('CheckoutExtraFieldActive'.$i)) {
					$fieldName = preg_replace("/[^A-Za-z0-9]|[\s]/", '', GetConfig('CheckoutExtraFieldName'.$i));
					if(!isset($_POST['CheckoutExtraField'.$i])){ 
						$_POST['CheckoutExtraField'.$i] = ''; 
					}
					$fieldValue = $_POST['CheckoutExtraField'.$i];
	
					if($fieldName == 'RequiereFactura' && $fieldValue == 'on'){
						if(!isset($RFC) || $RFC == ''){
							logAddError('Se solicito factura pero el RFC esta vacio');
							FlashMessage('Necesita registrar un RFC para poder solicitar factura. Puede registrar su RFC en <a href="%%GLOBAL_ShopPath%%/account.php?action=account_details">Detalles de Cuenta</a>', MSG_ERROR);
							if(GetConfig('CheckoutType') == 'multipage') {
								header("Location: ".GetConfig('ShopPath').'/checkout.php?action=confirm_order');
							}
							else {
								header("Location: ".GetConfig('ShopPath').'/checkout.php');
							}
							exit;
						}
					}
				}
		}
		
		
		// ensure products are in stock
		$this->CheckStockLevels();

		// Customer actually chose to apply a gift certificate or coupon code to this order so
		// we actually show the confirm order page again which does all of the magic.
		if (isset($_REQUEST['apply_code'])) {
			$this->ConfirmOrder();
			return;
		}

		// Attempt to create the pending order with the selected details
		$pendingResult = $this->SavePendingOrder();

		// There was a problem creating the pending order
		if(!is_array($pendingResult)) {
			@ob_end_clean();
			header("Location: ".$GLOBALS['ShopPath']."/checkout.php?action=confirm_order");
			exit;
		}

		// There was a problem creating the pending order but we have an actual error message
		if(isset($pendingResult['error'])) {
			if(isset($pendingResult['errorDetails'])) {
				$this->BadOrder('', $pendingResult['error'], $pendingResult['errorDetails']);
			}
			else {
				$this->BadOrder('', $pendingResult['error']);
			}
		}

		// We've been told all we need to do is redirect to the finish order page, so do that
		if(isset($pendingResult['redirectToFinishOrder']) && $pendingResult['redirectToFinishOrder']) {
			@ob_end_clean();
			header("Location: ".$GLOBALS['ShopPath']."/finishorder.php");
			die();
		}

		// Otherwise, the gateway want's to do something
		if(!empty($pendingResult['provider']) && ($pendingResult['provider']->GetPaymentType() == PAYMENT_PROVIDER_ONLINE || method_exists($pendingResult['provider'], "ShowPaymentForm"))) {
			// ProviderListHTML is stored in the session when the provider requires that it can only be the only payment provider during checkout, disable the other checkout method.
			if(isset($_SESSION['CHECKOUT']['ProviderListHTML']) && method_exists($pendingResult['provider'], 'DoExpressCheckoutPayment')) {
				$pendingResult['provider']->DoExpressCheckoutPayment();
				die();
			}

			// If we have a payment form to show then show that
			if(isset($pendingResult['showPaymentForm']) && $pendingResult['showPaymentForm']) {
				$this->ShowPaymentForm($pendingResult['provider']);
			}
			else {
				$pendingResult['provider']->TransferToProvider();
			}
		}
		else {
			// It's an offline payment method, no need to accept payment now
			if(!empty($pendingResult['provider']))
				$providerId = $pendingResult['provider']->GetId();
			else
				$providerId = '';

			@ob_end_clean();
			header(sprintf("Location:%s/finishorder.php?provider=%s", $GLOBALS['ShopPath'], $providerId));
			die();
		}
	}

	/**
	 * Display the payment form for a payment provider.
	 *
	 * @param object The payment provider object with the payment form.
	 */
	public function ShowPaymentForm($provider)
	{
		$GLOBALS['PaymentFormContent'] = $provider->ShowPaymentForm();
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('checkout_payment');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		exit;
	}

	/**
	 * If they're a first time customer and are checking out we don't need to let them choose
	 * a shipping address because we just entered it, so we'll automatically select it and
	 * take them straight to the shipping quote page
	 */
	private function ChooseShipperAndGoToBillingAddress()
	{
		$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
		$query = sprintf("select shipid from [|PREFIX|]shipping_addresses where shipcustomerid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId()));
		$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, 1);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			@ob_end_clean();
			header("Location: " . $GLOBALS['ShopPath'] . "/checkout.php?action=choose_shipper&address_id=" . $row['shipid']);
			die();
		}
		else {
			$this->ChooseShippingAddress();
		}
	}

	/**
	 * Show a "bad order" error message.
	 *
	 * @param string The title of the message to be shown.
	 * @param string The message to be shown.
	 * @param string Any additional/extra information we want to show.
	 */
	public function BadOrder($title="", $message="", $detailed="")
	{
		$GLOBALS['ErrorTitle'] = GetLang('OrderError');
		if($title) {
			$GLOBALS['ISC_LANG']['SomethingWentWrong'] = $title;
		}

		if($message == "") {
			$GLOBALS['ErrorMessage'] = sprintf(GetLang('BadOrderDetailsFromProvider'), GetConfig('OrderEmail'), GetConfig('OrderEmail'));
		}
		else {
			$GLOBALS['ErrorMessage'] = $message;
		}

		if($detailed != "") {
			$GLOBALS['ErrorDetails'] = $detailed;
		}

		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle($GLOBALS['ErrorTitle']);
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("error");
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}

	public static function getQuoteTotalRows(ISC_QUOTE $quote, $displayIncTax = null, $expandShipping = true)
	{
		if($displayIncTax === null) {
			$displayIncTax = false;
			if(getConfig('taxDefaultTaxDisplayCart') == TAX_PRICES_DISPLAY_INCLUSIVE) {
				$displayIncTax = true;
			}
		}

		$totalRows = array();

		// Subtotal
		$totalRows['subtotal'] = array(
			'label' => getLang('Subtotal'),
			'value' => $quote->getSubTotal($displayIncTax)
		);

		// Gift Wrapping
		$wrappingCost = $quote->getWrappingCost($displayIncTax);
		if($wrappingCost > 0) {
			$totalRows['giftWrapping'] = array(
				'label' => getLang('GiftWrapping'),
				'value' => $wrappingCost
			);
		}

		// Coupon codes
		$quote->reapplyCoupons();
		$coupons = $quote->getAppliedCoupons();
		$freeShippingCoupons = array();
		foreach($coupons as $coupon) {

			// Discard the coupon if it's already expired.
			if (isset ($coupon['expiresDate']) && $quote->isCouponExpired($coupon['expiresDate'])) {
				$quote->removeCoupon($coupon['code']);
				continue;
			}
			$couponRow = array(
				'type' => 'coupon',
				'label' => getLang('Coupon').' ('.$coupon['code'].')',
				'value' => $coupon['totalDiscount'] * -1,
				'id' => $coupon['id'],
			);

			if (getclass('ISC_COUPON')->isFreeShippingCoupon($coupon['discountType'])) {
				$freeShippingCoupons['coupon-'.$coupon['id']] = $couponRow;
				continue;
			}
			$totalRows['coupon-'.$coupon['id']] = $couponRow;
		}

		// Discount Amount
		$discountAmount = $quote->getDiscountAmount();
		if($discountAmount > 0){
			$totalRows['discount'] = array(
				'label' => getLang('Discount'),
				'value' => $discountAmount * -1,
			);
		}

		// Shipping & handling
		if($quote->getNonDiscountedShippingCost($displayIncTax) > 0 && !$quote->isDigital()) {
			// show each shipping quote separately?
			if ($expandShipping) {
				$shippingAddresses = $quote->getShippingAddresses();
				foreach($shippingAddresses as $address) {
					if(!$address->hasShippingMethod()) {
						continue;
					}

					$totalRows['shipping-'.$address->getId()] = array(
						'label' => getLang('Shipping').' ('.$address->getShippingProvider().')',
						'value' => $address->getNonDiscountedShippingCost($displayIncTax)
					);
				}
			}
			else {
				$totalRows['shipping'] = array(
					'label' => getLang('Shipping'),
					'value' => $quote->getNonDiscountedShippingCost($displayIncTax),
				);
			}

			// Added the free shipping coupon display below shipping cost
			// Only if we have free shipping coupon applied
			if (!empty ($freeShippingCoupons)) {
				foreach ($freeShippingCoupons as $key=>$val) {
					$totalRows[$key] = $val;
				}
			}
		}

		$handlingCost = $quote->getHandlingCost($displayIncTax);
		if($handlingCost > 0) {
			$totalRows['handling'] = array(
				'label' => getLang('Handling'),
				'value' => $handlingCost
			);
		}

		// Taxes
		$taxes = array();
		$includedTaxes = array();
		$taxTotal = $quote->getTaxTotal();
		if($taxTotal) {
			$taxAppend = '';
			if(getConfig('taxDefaultTaxDisplayCart') == TAX_PRICES_DISPLAY_INCLUSIVE) {
				$taxAppend = ' '.getLang('IncludedInTotal');
			}

			// Show a single summary of applied tax
			if(getConfig('taxChargesInCartBreakdown') == TAX_BREAKDOWN_SUMMARY) {
				$taxes[] = array(
					'name'	=> getConfig('taxLabel').$taxAppend,
					'total'	=> $taxTotal,
				);
			}
			else {
				$taxSummary = $quote->getTaxRateSummary();
				foreach($taxSummary as $taxRateName => $taxRateAmount) {
					if($taxRateAmount == 0) {
						continue;
					}
					$taxes[] = array(
						'name' => $taxRateName.$taxAppend,
						'total' => $taxRateAmount,
					);
				}
			}

			if(getConfig('taxDefaultTaxDisplayCart') == TAX_PRICES_DISPLAY_INCLUSIVE) {
				$includedTaxes = $taxes;
				$taxes = array();
			}
		}

		foreach($taxes as $id => $taxRate) {
			$totalRows['tax-'.$id] = array(
				'label' => $taxRate['name'],
				'value' => $taxRate['total'],
			);
		}

		// Gift Certificates
		$giftCertificates = $quote->getAppliedGiftCertificates();
		foreach($giftCertificates as $giftCertificate) {
			$totalRows['giftcertificate-'.$giftCertificate['id']] = array(
				'type' => 'giftCertificate',
				'label' => getLang('GiftCertificate').' ('.$giftCertificate['code'].')',
				'value' => $giftCertificate['used'] * -1,
				'id' => $giftCertificate['id'],
			);
		}

		$totalRows['total'] = array(
			'label' => getLang('GrandTotal'),
			'value' => $quote->getGrandTotal($displayIncTax),
		);

		// Included taxes
		foreach($includedTaxes as $id => $taxRate) {
			$totalRows['tax-'.$id] = array(
				'label' => $taxRate['name'],
				'value' => $taxRate['total'],
			);
		}

		return $totalRows;
	}

	/**
	* Determines if the given code appears to be a gift certificate. This is a superficial check only and does not attempt to validate the certificate for use in an order.
	*
	* @param mixed $code
	* @return int
	*/
	public static function isCertificateCode($code)
	{
		return preg_match('#^[A-Z0-9]{3}\-[A-Z0-9]{3}\-[A-Z0-9]{3}\-[A-Z0-9]{3}$#', $code) && gzte11(ISC_LARGEPRINT);
	}

	/**
	 * Build the contents for the order confirmation page. This function sets up everything to be used by
	 * the order confirmation on the express checkout page as well as the ConfirmOrder page when using a
	 * multi step checkout.
	 */
	public function BuildOrderConfirmation()
	{
		$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
		if(!GetConfig('ShowMailingListInvite')) {
			$GLOBALS['HideMailingListInvite'] = 'none';
		}

		// Do we need to show the special offers & discounts checkbox and should they
		// either of the newsletter checkboxes be ticked by default?
		if (GetConfig('MailAutomaticallyTickNewsletterBox')) {
			$GLOBALS['NewsletterBoxIsTicked'] = 'checked="checked"';
		}

		if (ISC_EMAILINTEGRATION::doOrderAddRulesExist()) {
			if (GetConfig('MailAutomaticallyTickOrderBox')) {
				$GLOBALS['OrderBoxIsTicked'] = 'checked="checked"';
			}
		}
		else {
			$GLOBALS['HideOrderCheckBox'] = "none";
		}

		if(isset($_REQUEST['ordercomments'])) {
			$GLOBALS['OrderComments'] = $_REQUEST['ordercomments'];
		}
		
		$GLOBALS['CheckoutExtraFieldsContent'] = $this->RenderCheckoutExtraFields();

		// Now we check if we have an incoming coupon or gift certificate code to apply
		if (isset($_REQUEST['couponcode']) && $_REQUEST['couponcode'] != '') {
			$code = trim($_REQUEST['couponcode']);

			// Were we passed a gift certificate code?
			if (self::isCertificateCode($code)) {
				try {
					$this->getQuote()->applyGiftCertificate($code);

					// If successful show a message
					$GLOBALS['CheckoutSuccessMsg'] = GetLang('GiftCertificateAppliedToCart');
				}
				catch(ISC_QUOTE_EXCEPTION $e) {
					$GLOBALS['CheckoutErrorMsg'] = $e->getMessage();
				}
			}
			// Otherwise, it must be a coupon code
			else {
				try {
					$this->getQuote()->applyCoupon($code);

					// Coupon code applied successfully
					$GLOBALS['CheckoutSuccessMsg'] = GetLang('CouponAppliedToCart');
				}
				catch(ISC_QUOTE_EXCEPTION $e) {
					$GLOBALS['CheckoutErrorMsg'] = $e->getMessage();
				}
			}
		}

		$GLOBALS['ISC_CLASS_ACCOUNT'] = GetClass('ISC_ACCOUNT');

		// Determine what we'll be showing for the redeem gift certificate/coupon code box
		if (gzte11(ISC_LARGEPRINT)) {
			$GLOBALS['RedeemTitle'] = GetLang('RedeemGiftCertificateOrCoupon');
			$GLOBALS['RedeemIntro'] = GetLang('RedeemGiftCertificateorCouponIntro');
		}
		else {
			$GLOBALS['RedeemTitle'] = GetLang('RedeemCouponCode');
			$GLOBALS['RedeemIntro'] = GetLang('RedeemCouponCodeIntro');
		}

		$GLOBALS['HideCheckoutError'] = "none";
		$GLOBALS['HidePaymentOptions'] = "";
		$GLOBALS['HideUseCoupon'] = '';
		$checkoutProviders = array();

		// if the provider list html is set in session then use it as the payment provider options.
		// it's normally set in payment modules when it's required.
		if(isset($_SESSION['CHECKOUT']['ProviderListHTML'])) {
			$GLOBALS['HidePaymentProviderList'] = "";
			$GLOBALS['HidePaymentOptions'] = "";
			$GLOBALS['PaymentProviders'] = $_SESSION['CHECKOUT']['ProviderListHTML'];
			$GLOBALS['StoreCreditPaymentProviders'] = $_SESSION['CHECKOUT']['ProviderListHTML'];
			$GLOBALS['CheckoutWith'] = "";
		} else {
			// Get a list of checkout providers
			$checkoutProviders = GetCheckoutModulesThatCustomerHasAccessTo(true);


			// If no checkout providers are set up, send an email to the store owner and show an error message
			if (empty($checkoutProviders)) {
				$GLOBALS['HideConfirmOrderPage'] = "none";
				$GLOBALS['HideCheckoutError'] = '';
				$GLOBALS['HideTopPaymentButton'] = "none";
				$GLOBALS['HidePaymentProviderList'] = "none";
				$GLOBALS['CheckoutErrorMsg'] = GetLang('NoCheckoutProviders');
				$GLOBALS['NoCheckoutProvidersError'] = sprintf(GetLang("NoCheckoutProvidersErrorLong"), $GLOBALS['ShopPath']);

				$GLOBALS['EmailHeader'] = GetLang("NoCheckoutProvidersSubject");
				$GLOBALS['EmailMessage'] = sprintf(GetLang("NoCheckoutProvidersErrorLong"), $GLOBALS['ShopPath']);

				$emailTemplate = FetchEmailTemplateParser();
				$emailTemplate->SetTemplate("general_email");
				$message = $emailTemplate->ParseTemplate(true);

				require_once(ISC_BASE_PATH . "/lib/email.php");
				$obj_email = GetEmailClass();
				$obj_email->Set('CharSet', GetConfig('CharacterSet'));
				$obj_email->From(GetConfig('OrderEmail'), GetConfig('StoreName'));
				$obj_email->Set("Subject", GetLang("NoCheckoutProvidersSubject"));
				$obj_email->AddBody("html", $message);
				$obj_email->AddRecipient(GetConfig('AdminEmail'), "", "h");
				$email_result = $obj_email->Send();
			}

			// We have more than one payment provider, hide the top button and build a list
			else if (count($checkoutProviders) > 1) {
				$GLOBALS['HideTopPaymentButton'] = "none";
				$GLOBALS['HideCheckoutError'] = "none";
			}

			// There's only one payment provider - hide the list
			else {
				$GLOBALS['HidePaymentProviderList'] = "none";
				$GLOBALS['HideCheckoutError'] = "none";
				$GLOBALS['HidePaymentOptions'] = "none";
				list(,$provider) = each($checkoutProviders);
				if(method_exists($provider['object'], 'ShowPaymentForm') && !isset($_SESSION['CHECKOUT']['ProviderListHTML'])) {
					$GLOBALS['ExpressCheckoutLoadPaymentForm'] = 'ExpressCheckout.ShowSingleMethodPaymentForm();';
				}
				if ($provider['object']->GetPaymentType() == PAYMENT_PROVIDER_OFFLINE) {
					$GLOBALS['PaymentButtonSwitch'] = "ShowContinueButton();";
				}
				$GLOBALS['CheckoutWith'] = $provider['object']->GetDisplayName();
			}

			// Build the list of payment provider options
			$GLOBALS['PaymentProviders'] = $GLOBALS['StoreCreditPaymentProviders'] =  "";
			foreach ($checkoutProviders as $provider) {
				$GLOBALS['ProviderChecked'] = '';
				if(count($checkoutProviders) == 1) {
					$GLOBALS['ProviderChecked'] = 'checked="checked"';
				}
				$GLOBALS['ProviderId'] = $provider['object']->GetId();
				//NES: Quito le isc_html_escape para poder poner codigo HTML en el nombre
				$GLOBALS['ProviderName'] = $provider['object']->GetDisplayName();
				$GLOBALS['ProviderType'] = $provider['object']->GetPaymentType("text");
				if(method_exists($provider['object'], 'ShowPaymentForm')) {
					$GLOBALS['ProviderPaymentFormClass'] = 'ProviderHasPaymentForm';
				}
				else {
					$GLOBALS['ProviderPaymentFormClass'] = '';
				}
				$GLOBALS['PaymentFieldPrefix'] = '';
				$GLOBALS['PaymentProviders'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CheckoutProviderOption");
				$GLOBALS['PaymentFieldPrefix'] = 'credit_';
				$GLOBALS['StoreCreditPaymentProviders'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CheckoutProviderOption");
			}

		}

		// Are we coming back to this page for a particular reason?
		if (isset($_SESSION['REDIRECT_TO_CONFIRMATION_MSG'])) {
			$GLOBALS['HideCheckoutError'] = '';
			$GLOBALS['CheckoutErrorMsg'] = $_SESSION['REDIRECT_TO_CONFIRMATION_MSG'];
			unset($_SESSION['REDIRECT_TO_CONFIRMATION_MSG']);
		}

		$displayIncludingTax = false;
		if(getConfig('taxDefaultTaxDisplayCart') != TAX_PRICES_DISPLAY_EXCLUSIVE) {
			$displayIncludingTax = true;
		}

		$items = $this->getQuote()->getItems();

		// Start building the summary of all of the items in the order
		$GLOBALS['SNIPPETS']['CartItems'] = '';
		foreach ($items as $item) {
			$GLOBALS['ProductQuantity'] = $item->getQuantity();

			$price = $item->getPrice($displayIncludingTax);
			$total = $item->getTotal($displayIncludingTax);
			$GLOBALS['ProductPrice'] = currencyConvertFormatPrice($price);
			$GLOBALS['ProductTotal'] = currencyConvertFormatPrice($total);

			if($item instanceof ISC_QUOTE_ITEM_GIFTCERTIFICATE) {
				$GLOBALS['GiftCertificateName'] = isc_html_escape($item->getName());
				$GLOBALS['GiftCertificateTo'] = isc_html_escape($item->getRecipientName());
				$GLOBALS['SNIPPETS']['CartItems'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CheckoutCartItemGiftCertificate");
				continue;
			}

			$GLOBALS['ProductAvailability'] = $item->getAvailability();
			$GLOBALS['ItemId'] = $item->getProductId();

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
			$GLOBALS['EventDate'] = '';
			$eventDate = $item->getEventDate(true);
			if(!empty($eventDate)) {
				$GLOBALS['EventDate'] = '
					<div style="font-style: italic; font-size:10px; color:gray">(' .
						$item->getEventName() . ': ' . isc_date('M jS Y', $eventDate) .
					')</div>';
			}

			$GLOBALS['HideGiftWrapping'] = 'display: none';
			$GLOBALS['GiftWrappingName'] = '';
			$GLOBALS['GiftMessagePreview'] = '';
			$GLOBALS['HideGiftMessagePreview'] = 'display: none';

			$wrapping = $item->getGiftWrapping();
			if($wrapping !== false) {
				$GLOBALS['HideGiftWrapping'] = '';
				$GLOBALS['GiftWrappingName'] = isc_html_escape($wrapping['wrapname']);
				if(!empty($wrapping['wrapmessage'])) {
					if(isc_strlen($wrapping['wrapmessage']) > 30) {
						$wrapping['wrapmessage'] = substr($wrapping['wrapmessage'], 0, 27).'...';
					}
					$GLOBALS['GiftMessagePreview'] = isc_html_escape($wrapping['wrapmessage']);
					$GLOBALS['HideGiftMessagePreview'] = '';
				}
			}

			//create configurable product fields on order confirmation page with the data posted from add to cart page
			$GLOBALS['CartProductFields'] = '';
			$configuration = $item->getConfiguration();
			if (!empty($configuration)) {
				require_once ISC_BASE_PATH.'/includes/display/CartContent.php';
				ISC_CARTCONTENT_PANEL::GetProductFieldDetails($configuration, $item->getId());
			}

			$GLOBALS['ProductName'] = isc_html_escape($item->getName());
			$GLOBALS['ProductImage'] = imageThumb($item->getThumbnail(), prodLink($item->getName()));

			$GLOBALS['HideExpectedReleaseDate'] = 'display: none;';
			if($item->isPreOrder()) {
				$GLOBALS['ProductExpectedReleaseDate'] = $item->getPreOrderMessage();
				$GLOBALS['HideExpectedReleaseDate'] = '';
			}

			$GLOBALS['SNIPPETS']['CartItems'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CheckoutCartItem");
		}

		// Do we have a shipping price to show?
		if(!$this->getQuote()->isDigital()) {
			$shippingAddresses = $this->getQuote()->getShippingAddresses();
			$numShippingAddresses = count($shippingAddresses);
			if($numShippingAddresses == 1) {
				$shippingAddress = $this->getQuote()->getShippingAddress();
				$GLOBALS['ShippingAddress'] = $GLOBALS['ISC_CLASS_ACCOUNT']->FormatShippingAddress($shippingAddress->getAsArray());
			}
			else {
				$GLOBALS['ShippingAddress'] = '<em>(Order will be shipped to multiple addresses)</em>';
			}

			// Show the shipping details
			$GLOBALS['HideShippingDetails'] = '';
		}
		// This is a digital order - no shipping applies
		else {
			$GLOBALS['HideShippingDetails'] = 'display: none';
			$GLOBALS['HideShoppingCartShippingCost'] = 'none';
			$GLOBALS['ShippingAddress'] = GetLang('NotRequiredForDigitalDownloads');
			$GLOBALS['ShippingMethod'] = GetLang('ShippingImmediateDownload');
		}

		$billingAddress = $this->getQuote()->getBillingAddress();
		$GLOBALS['BillingAddress'] = getClass('ISC_ACCOUNT')
			->formatShippingAddress($billingAddress->getAsArray());

		$totalRows = self::getQuoteTotalRows($this->getQuote());
		$templateTotalRows = '';
		foreach($totalRows as $id => $totalRow) {
			$GLOBALS['ISC_CLASS_TEMPLATE']->assign('label', $totalRow['label']);
			$GLOBALS['ISC_CLASS_TEMPLATE']->assign('classNameAppend', ucfirst($id));
			$value = currencyConvertFormatPrice($totalRow['value']);
			$GLOBALS['ISC_CLASS_TEMPLATE']->assign('value', $value);
			$templateTotalRows .= $GLOBALS['ISC_CLASS_TEMPLATE']->getSnippet('CheckoutCartTotal');
		}
		$GLOBALS['ISC_CLASS_TEMPLATE']->assign('totals', $templateTotalRows);

		$grandTotal = $this->getQuote()->getGrandTotal();
		$GLOBALS['GrandTotal'] = formatPrice($grandTotal);
		if($grandTotal == 0) {
			$GLOBALS['HidePaymentOptions'] = "none";
			$GLOBALS['HideUseCoupon'] = 'none';
			$GLOBALS['HidePaymentProviderList'] = "none";
			$GLOBALS['PaymentButtonSwitch'] = "ShowContinueButton(); ExpressCheckout.UncheckPaymentProvider();";
		}

		// Does the customer have any store credit they can use?
		$GLOBALS['HideUseStoreCredit'] = "none";
		$GLOBALS['HideRemainingStoreCredit'] = "none";
		$customer = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerDataByToken();
		if ($customer['custstorecredit'] > 0) {
			$GLOBALS['HidePaymentOptions'] = "";
			$GLOBALS['StoreCredit'] = CurrencyConvertFormatPrice($customer['custstorecredit']);
			$GLOBALS['HideUseStoreCredit'] = "";
			$GLOBALS['HidePaymentProviderList'] = "none";
			// The customer has enough store credit to pay for the entirity of this order
			if ($customer['custstorecredit'] >= $grandTotal) {
				$GLOBALS['PaymentButtonSwitch'] = "ShowContinueButton();";
				$GLOBALS['HideLimitedCreditWarning'] = "none";
				$GLOBALS['HideLimitedCreditPaymentOption'] = "none";
				$GLOBALS['HideCreditPaymentMethods'] = "none";
				$GLOBALS['RemainingCredit'] = $customer['custstorecredit'] - $grandTotal;
				if ($GLOBALS['RemainingCredit'] > 0) {
					$GLOBALS['HideRemainingStoreCredit'] = '';
					$GLOBALS['RemainingCredit'] = CurrencyConvertFormatPrice($GLOBALS['RemainingCredit']);
				}
			}
			// Customer doesn't have enough store credit to pay for the order
			else {
				$GLOBALS['Remaining'] = CurrencyConvertFormatPrice($grandTotal-$customer['custstorecredit']);

				if(count($checkoutProviders) == 1) {
					$GLOBALS['CheckoutStoreCreditWarning'] = sprintf(GetLang('CheckoutStoreCreditWarning2'), $GLOBALS['Remaining'], $GLOBALS['CheckoutWith']);
					$GLOBALS['HideLimitedCreditPaymentOption'] = "none";
				}
				else {
					$GLOBALS['CheckoutStoreCreditWarning'] = GetLang('CheckoutStoreCreditWarning');
				}
				$GLOBALS['ISC_LANG']['CreditPaymentMethod'] = sprintf(GetLang('CreditPaymentMethod'), $GLOBALS['Remaining']);
			}

			if (count($checkoutProviders) > 1) {
				$GLOBALS['CreditAlt'] = GetLang('CheckoutCreditAlt');
			}
			else if (count($checkoutProviders) <= 1 && isset($GLOBALS['CheckoutWith'])) {
				$GLOBALS['CreditAlt'] = sprintf(GetLang('CheckoutCreditAltOneMethod'), $GLOBALS['CheckoutWith']);
			}
			else {
				if ($customer['custstorecredit'] >= $grandTotal) {
					$GLOBALS['HideCreditAltOptionList'] = "none";
					$GLOBALS['HideConfirmOrderPage'] = "";
					$GLOBALS['HideTopPaymentButton'] = "none";
					$GLOBALS['HideCheckoutError'] = "none";
					$GLOBALS['CheckoutErrorMsg'] = '';
				}
			}
		}

		// Customer has hit this page before. Delete the existing pending order
		// The reason we do a delete is if they're hitting this page again, something
		// has changed with their order or something has become invalid with it along the way.
		if (isset($_COOKIE['SHOP_ORDER_TOKEN']) && IsValidPendingOrderToken($_COOKIE['SHOP_ORDER_TOKEN'])) {
			$query = "
				SELECT orderid
				FROM [|PREFIX|]orders
				WHERE ordtoken='".$GLOBALS['ISC_CLASS_DB']->Quote($_COOKIE['SHOP_ORDER_TOKEN'])."' AND ordstatus=0
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while($order = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$entity = new ISC_ENTITY_ORDER();
				/** @todo ISC-1141 check to see if this needs changing to ->purge() */
				/** @todo ISC-860 this is relying on another bugfix, I'm leaving this as ->delete() for now so that orders remain in the db somewhere at least -gwilym */
				if ($entity->delete($order['orderid'], true)) {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemNotice('general', GetLang('OrderDeletedAutomatically', array('order' => $order['orderid'])));
				}
			}
		}

		// Are we showing an error message?
		if (isset($GLOBALS['CheckoutErrorMsg']) && $GLOBALS['CheckoutErrorMsg'] != '') {
			$GLOBALS['HideCheckoutError'] = '';
		}
		else {
			$GLOBALS['HideCheckoutError'] = "none";
		}

		// Is there a success message to show?
		if (isset($GLOBALS['CheckoutSuccessMsg']) && $GLOBALS['CheckoutSuccessMsg'] != '') {
			$GLOBALS['HideCheckoutSuccess'] = '';
		}
		else {
			$GLOBALS['HideCheckoutSuccess'] = "none";
		}

		if(GetConfig('EnableOrderComments') == 1) {
			$GLOBALS['HideOrderComments'] = "";
		} else {
			$GLOBALS['HideOrderComments'] = "none";
		}

		if(GetConfig('EnableOrderTermsAndConditions') == 1) {

			$GLOBALS['HideOrderTermsAndConditions'] = "";

			if(GetConfig('OrderTermsAndConditionsType') == "link") {
				$GLOBALS['AgreeTermsAndConditions'] = GetLang('YesIAgree');

				$GLOBALS['TermsAndConditionsLink'] = "<a href='".GetConfig('OrderTermsAndConditionsLink')."' target='_BLANK'>".strtolower(GetLang('TermsAndConditions'))."</a>.";

				$GLOBALS['HideTermsAndConditionsTextarea'] = "display:none;";

			} else {
				$GLOBALS['HideTermsAndConditionsTextarea']= '';
				$GLOBALS['OrderTermsAndConditions'] = GetConfig('OrderTermsAndConditions');
				$GLOBALS['AgreeTermsAndConditions'] = GetLang('AgreeTermsAndConditions');
				$GLOBALS['TermsAndConditionsLink'] = '';
			}
		} else {
			$GLOBALS['HideOrderTermsAndConditions'] = "display:none;";
		}

		// BCSIXBETA-372 - mail format preferences removed/disabled for now
		// %%SNIPPET_CheckoutMailFormatPreference%% references also need to be added back into the checkout panels/snippets to re-enable this if needed
//		$GLOBALS['MailFormatPreferenceOptions'] = $this->GenerateMailFormatPreferenceOptions();
//		$GLOBALS['SNIPPETS']['CheckoutMailFormatPreference'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('CheckoutMailFormatPreference');
	}

	/**
	 * Polls each configured email provider module to determine which format preferences are available
	 *
	 * @return array An array of preference id => description values
	 */
	public function GenerateMailFormatPreferences()
	{
		$modules = ISC_EMAILINTEGRATION::getConfiguredModules();
		$preferences = array();
		foreach ($modules as /** @var ISC_EMAILINTEGRATION */$module) {
			$modulePreferences = $module->getAvailableMailFormatPreferences();
			foreach ($modulePreferences as $preference) {
				if (isset($preferences[$preference])) {
					continue;
				}
				$preferences[$preference] = GetLang('EmailFormatDescription_' . $preference);
			}
		}
		ksort($preferences);
		return $preferences;
	}

	public function GenerateMailFormatPreferenceOptions()
	{
		$preferences = $this->GenerateMailFormatPreferences();
		$options = '';
		foreach ($preferences as $preferenceId => $preferenceDescription) {
			$option = new Xhtml_Option($preferenceDescription, $preferenceId);
			$options .= $option->render();
		}
		return $options;
	}

	/**
	 * This function can be used when the checkout button for paticular payment providers are on the cart page,
	 * it calls the coresponding function in the checkout module to set needed data and send to the provider
	 *
	 */
	public function SetExternalCheckout()
	{
		if(!isset($_REQUEST['provider'])) {
			header("Location: ".$GLOBALS['ShopPath']."/cart.php");
			exit;
		}
		if(!GetModuleById('checkout', $provider, $_REQUEST['provider'])) {
			header("Location: ".$GLOBALS['ShopPath']."/cart.php");
			exit;
		}
		// This gateway doesn't support a ping back/notification
		if(!method_exists($provider, 'SetCheckoutData')) {
			header("Location: ".$GLOBALS['ShopPath']."/cart.php");
			exit;
		}
		$provider->SetCheckoutData();
	}

	/**
	 * Filter a field and if it's empty, return false. Used in an array_filter in SetPanelSettings()
	 *
	 * @param string The field value.
	 * @return boolean False if the field is empty.
	 * @see SetPanelSettings
	 */
	public function FilterAddressFields($field)
	{
		if(!$field) {
			return false;
		}
		else {
			return true;
		}
	}

	/**
	* Checks the stock levels of each product during various stages of checkout to ensure you can't purchase a product that has become out of stock
	*
	*/
	private function CheckStockLevels()
	{
		$quote = getCustomerQuote();
		$items = $quote->getItems();

		foreach($items as $item) {
			if($item->getProductId() && !$item->checkStockLevel()) {
				$outOfStock = $item->getName();
				break;
			}
		}

		if(!empty($outOfStock)) {
			FlashMessage(sprintf(getLang('CheckoutInvLevelBelowOrderQty'), $outOfStock), MSG_ERROR, 'cart.php');
		}
	}

	public function ProcessGatewayCallBack()
	{
		if(!isset($_REQUEST['provider'])) {
			header("Location: ".$GLOBALS['ShopPath']."/cart.php");
			exit;
		}
		if(!GetModuleById('checkout', $provider, $_REQUEST['provider'])) {
			header("Location: ".$GLOBALS['ShopPath']."/cart.php");
			exit;
		}

		if(!isset($_REQUEST['callback'])) {
			header("Location: ".$GLOBALS['ShopPath']."/cart.php");
			exit;
		}
		// This gateway doesn't support a ping back/notification
		if(!method_exists($provider, $_REQUEST['callback'])) {
			header("Location: ".$GLOBALS['ShopPath']."/cart.php");
			exit;
		}

		$provider->$_REQUEST['callback']();
	}

	public function getQuote()
	{
		return getCustomerQuote();
	}
}
