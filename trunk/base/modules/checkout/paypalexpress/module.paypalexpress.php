<?php

	class CHECKOUT_PAYPALEXPRESS extends ISC_CHECKOUT_PROVIDER
	{
		public $_showBothButtons = true;
		public $showOnConfirmPage = true;

		protected $supportsVendorPurchases = true;

		private $_liveTransactionURL = 'https://api-3t.paypal.com';
		private $_testTransactionURL = 'https://api-3t.sandbox.paypal.com';
		private $_liveTransactionURI = '/nvp';
		private $_testTransactionURI = '/nvp';

		/**
		 * The constructor.
		 */
		public function __construct()
		{
			parent::__construct();

			$this->SetImage('paypal_logo.gif');
			$this->SetName(GetLang('PayPalExpressName'));
			$this->SetDescription(GetLang('PayPalExpressDesc'));
			$this->_help = sprintf(GetLang('PayPalExpressHelp'), $GLOBALS['ShopPath'], $GLOBALS['ShopPath']);
		}

		/**
		 * Check if this checkout module can be enabled or not.
		 *
		 * @return boolean True if this module is supported on this install, false if not.
		 */
		public function IsSupported()
		{
			$currency = GetDefaultCurrency();

			$supportedCurrencies = array(
				'USD',
				'EUR',
				'GBP',
				'JPY',
				'CAD',
				'AUD',
				'MXP'
			);

			// Check if the default currency is supported by the payment gateway
			if (!in_array($currency['currencycode'], $supportedCurrencies)) {
				$this->SetError(sprintf(GetLang('PayPalExpressCurrecyNotSupported'),implode(',', $supportedCurrencies)));
			}

			if($this->HasErrors()) {
				return false;
			}
			else {
				return true;
			}
		}

		public function SetCustomVars()
		{
			$this->_variables['displayname'] = array(
				"name" => GetLang('DisplayName'),
				"type" => "textbox",
				"help" => GetLang('DisplayNameHelp'),
				"default" => GetLang('PayPalPaymentsProDefaultDispayName'),
				"savedvalue" => array(),
				"required" => true
			);

			$this->_variables['username'] = array(
				"name" => GetLang('PayPalAPIUsername'),
				"type" => "textbox",
				"help" => GetLang('PayPalAPIUsernameHelp'),
				"default" => "",
				"savedvalue" => array(),
				"required" => true
			);

			$this->_variables['password'] = array(
				"name" => GetLang('PayPalAPIPassword'),
				"type" => "password",
				"help" => GetLang('PayPalAPIPasswordHelp'),
				"default" => "",
				"savedvalue" => array(),
				"required" => true
			);

			$this->_variables['signature'] = array(
				"name" => GetLang('PayPalAPISignature'),
				"type" => "textbox",
				"help" => GetLang('PayPalAPISignatureHelp'),
				"default" => "",
				"savedvalue" => array(),
				"required" => true
			);

			$this->_variables['transactiontype'] = array(
				"name" => GetLang('TransactionType'),
				"type" => "dropdown",
				"help" => GetLang('TransactionTypeHelp'),
				"default" => "no",
				"savedvalue" => array(),
				"required" => true,
				"options" => array(
					GetLang('TransactionTypeSale') => "Sale",
					GetLang('TransactionTypeAuthorize') => "Authorization"
				),
				"multiselect" => false
			);

			$this->_variables['testmode'] = array(
				"name" => GetLang('TestMode'),
				"type" => "dropdown",
				"help" => GetLang('TestModeHelp'),
				"default" => "no",
				"savedvalue" => array(),
				"required" => true,
				"options" => array(
					GetLang('TestModeNo') => "NO",
					GetLang('TestModeYes') => "YES"
				),
				"multiselect" => false
			);
		}

		/**
		* decode response line from paypal and store it in an array
		*
		* @param string $result response come back from paypal
		*
		* @return array contents response from paypal.
		*/
		private function _DecodePaypalResult($result)
		{
			// prepare responses into array
			$proArray = array();
			while (strlen($result)) {
				// name
				$keypos = strpos($result,'=');
				$keyval = substr($result,0,$keypos);
				// value
				if(strpos($result, '&') !== false) {
					$valuepos = strpos($result, '&');
				}
				else {
					$valuepos = strlen($result);
				}
				$valval = substr($result,$keypos+1,$valuepos-$keypos-1);
				// decoding the respose
				$proArray[urldecode($keyval)] = urldecode($valval);
				$result = substr($result,$valuepos+1,strlen($result));
			}
			return $proArray;
		}

		/**
		* Get merchant's payment gateway details from the backend
		*
		* @return array merchants details
		*/
		private function GetMerchantSettings()
		{
			$merchant = array();
			// Load the paypal api username
			$merchant['username'] = $this->GetValue('username');

			// Load the paypal api signature
			$merchant['signature'] = $this->GetValue('signature');

			// Load the paypal password
			$merchant['password'] = $this->GetValue('password');

			// Load the paypal transaction Type
			$merchant['transactionType'] = $this->GetValue('transactiontype');

			// Is PayPal Express Checkout setup in test or live mode?
			$merchant['testmode'] = $this->GetValue('testmode');

			return $merchant;
		}

		/**
		 * Returns the checkout button for this specific module. Paypal Express Checkout requires that a
		 * seperate button be used for checking out using them
		 *
		 * @return string The html to show for the button
		 **/
		public function GetCheckoutButton()
		{
			return $this->ParseTemplate('paypalexpress.button', true);
		}



		public function GetSidePanelCheckoutButton()
		{
			$showNormalCheckoutButton = false;
			foreach (GetAvailableModules('checkout', true, true) as $module) {
				if (!method_exists($module['object'], 'GetCheckoutButton')) {
					$showNormalCheckoutButton = true;
					break;
				}
			}

			if ($showNormalCheckoutButton) {
				$GLOBALS['PayPalExpressOrUse'] = GetLang('PayPalExpressOrUse');
			} else {
				$GLOBALS['PayPalExpressOrUse'] = '';
			}

			return $this->ParseTemplate('paypalexpress.button', true);
		}

		public function SetCheckoutData()
		{
			if(!GetConfig('GuestCheckoutEnabled') && !CustomerIsSignedIn()) {
				@ob_end_clean();
				header("Location: ".$GLOBALS['ShopPath'].'/checkout.php?action=checkout&bad_login=1');
				exit;
			}
			if(!isset($_REQUEST['token'])) {
				$_SESSION['CHECKOUT']['FromCartPage'] = true;
				unset($_COOKIE['SHOP_ORDER_TOKEN']);
				$this->SetExpressCheckout();
			} else {
				$this->GetExpressCheckoutDetails();
			}

		}

		/**
		* Redirect the customer to PalPal site to enter their payment details
		* This is called when customer checkout through the normal checkout process,
		* transfer customers from order confirmation page to paypal
		*
		*/
		public function TransferToProvider()
		{
			$this->SetExpressCheckout();
			exit;
		}

		/**
		* Set Express Checkout step in Paypal Express checkout
		* it sends cart details to paypal and redirect customer to paypal login page.
		*
		*/
		private function SetExpressCheckout()
		{

			$currency = GetCurrencyCodeByID(GetConfig('DefaultCurrencyID'));

			$merchant = $this->GetMerchantSettings();

			$quote = getCustomerQuote();
			$amount = $quote->getGrandTotal();

			$shippingDetails = array();
			//if user click the paypal button on order confirmation page
			if(isset($_COOKIE['SHOP_ORDER_TOKEN'])) {
				$userAction = '&useraction=commit';
				$orders = $this->GetOrders();
				reset($orders);
				$order = current($orders);
				$orderId = '#'.implode(', #', array_keys($orders));

				if($order['ordisdigital']) {
					$shippingDetails = array (
						'NOSHIPPING' => 1,
					);
				} else {
					$shippingAddress = $this->getShippingAddress();
					$shippingDetails = array (
						'NAME' => $shippingAddress['first_name']." ".$shippingAddress['last_name'],
						'SHIPTOSTREET' => $shippingAddress['address_1'],
						'SHIPTOSTREET2' => $shippingAddress['address_2'],
						'SHIPTOCITY' => $shippingAddress['city'],
						'SHIPTOZIP' => $shippingAddress['zip'],
						'SHIPTOCOUNTRY' => $shippingAddress['country_iso2'],
						'PHONENUM' => $shippingAddress['phone'],
					);

					if($shippingAddress['state_id'] != 0 && GetStateISO2ById($shippingAddress['state_id'])) {
						$shippingDetails['SHIPTOSTATE'] = GetStateISO2ById($shippingAddress['state_id']);
					}
					else {
						$shippingAddress['SHIPTOSTATE'] = isc_html_escape($shippingAddress['state']);
					}
				}

				//don't display shipping address in PayPal
				$addressOverride = 1;
			} else {
				$userAction = '&useraction=continue';
				//display shipping address in PayPal
				$addressOverride = 0;
			}

			if($merchant['testmode'] == 'YES') {
				$transactionURL = $this->_testTransactionURL;
				$transactionURI = $this->_testTransactionURI;
				$PaypalExpressCheckoutURL = 'https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=';
			}
			else {
				$transactionURL = $this->_liveTransactionURL;
				$transactionURI = $this->_liveTransactionURI;
				$PaypalExpressCheckoutURL = 'https://www.paypal.com/webscr&cmd=_express-checkout&token=';
			}

			$pp_array = array(
				'METHOD'		=> 'SetExpressCheckout',
				'USER'			=> $merchant['username'],
				'PWD'			=> $merchant['password'],
				'SIGNATURE'		=> $merchant['signature'],
				'VERSION'		=> '53.0',
				'PAYMENTACTION'		=> $merchant['transactionType'],

				'AMT'			=> number_format($amount,2,'.',''),
				'CURRENCYCODE'		=> $currency,
				'PAYMENTACTION'		=> $merchant['transactionType'],

				'RETURNURL'		=> $GLOBALS['ShopPath']."/checkout.php?action=set_external_checkout&provider=paypalexpress",
				'CANCELURL'		=> $GLOBALS['ShopPath']."/cart.php",
				'ADDRESSOVERRIDE'	=> $addressOverride,
				'NOTIFYURL'		=> $GLOBALS['ShopPath'].'/checkout.php?action=gateway_ping&provider='.$this->GetId(),
				'L_NAME0'		=> getLang('YourOrderFromX', array('storeName' => getConfig('StoreName'))),
				'L_AMT0'		=> number_format($amount,2,'.',''),
				'L_QTY0'		=> 1,
			);

			//if shipping details are known here, which happens when user chose paypay express checkout at normal  order confirmation page
			if(!empty($shippingDetails)) {
				$pp_array = array_merge($pp_array, $shippingDetails);
			}
			$paypal_query = '';
			foreach ($pp_array as $key => $value) {
				$paypal_query .= $key.'='.urlencode($value).'&';
			}
			$paypal_query = rtrim($paypal_query, '&');

			$result = $this->_ConnectToProvider($transactionURL, $transactionURI, $paypal_query);
			$nvpArray = $this->_DecodePaypalResult($result);

			//if data is sent to paypal successfully, a token for this transaction will return from paypal
			if(strtolower($nvpArray['ACK']) == 'success') {
				// Redirect to paypal.com here
				$token = $nvpArray["TOKEN"];
				$PayPalURL = $PaypalExpressCheckoutURL.$token.$userAction;
				header("Location: ".$PayPalURL);
			} else {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment',  $this->GetName()), GetLang('ErrorConnectingToPaypal'), $nvpArray['L_ERRORCODE0']." ".$nvpArray['L_LONGMESSAGE0']);
				flashMessage(getLang('ErrorConnectingToPaypal'), MSG_ERROR, 'cart.php');
			}
		}


		/**
		* Get Express Checkout Details step
		* When customer come back from paypal after they select the payment method and shipping address in paypal,
		* This function takes the shipping address and redirect customer to choose shipping provider page.
		*/
		private function GetExpressCheckoutDetails()
		{
			if(isset($_SESSION['CHECKOUT']['FromCartPage'])) {
				unset($_COOKIE['SHOP_ORDER_TOKEN']);
			}
			$merchant = $this->GetMerchantSettings();
			if($merchant['testmode'] == 'YES') {
				$transactionURL = $this->_testTransactionURL;
				$transactionURI = $this->_testTransactionURI;
			}
			else {
				$transactionURL = $this->_liveTransactionURL;
				$transactionURI = $this->_liveTransactionURI;
			}

			$pp_array = array(
				'METHOD'	=> 'GetExpressCheckoutDetails',
				'USER'		=> $merchant['username'],
				'PWD'		=> $merchant['password'],
				'SIGNATURE'	=> $merchant['signature'],
				'VERSION'	=> '53.0',
				'PAYMENTACTION'	=> $merchant['transactionType'],
				'TOKEN'		=> $_REQUEST['token'],
				'NOTIFYURL'	=> $GLOBALS['ShopPath'].'/checkout.php?action=gateway_ping&provider='.$this->GetId(),

			);

			$paypal_query = '';
			foreach ($pp_array as $key => $value) {
				$paypal_query .= $key.'='.urlencode($value).'&';
			}
			$paypal_query = rtrim($paypal_query, '&');

			// get the customer details from paypal
			$result = $this->_ConnectToProvider($transactionURL, $transactionURI, $paypal_query);
			$nvpArray = $this->_DecodePaypalResult($result);

			if(strtolower($nvpArray['ACK']) == 'success') {

				$_SESSION['CHECKOUT']['PayPalExpressCheckout'] = $nvpArray;
				// if user started paypal express checkout at confirmation page, redirect user back to confirmation page
				if(isset($_COOKIE['SHOP_ORDER_TOKEN'])) {

					// Load the pending order
					$orders = LoadPendingOrdersByToken($_COOKIE['SHOP_ORDER_TOKEN']);
					if(!is_array($orders)) {
						@ob_end_clean();
						header("Location: ".$GLOBALS['ShopPath']."/checkout.php?action=confirm_order");
						die();
					}

					$this->SetOrderData($orders);

					$this->DoExpressCheckoutPayment();
					exit;
				}

				$countryID = GetCountryIdByISO2($nvpArray['SHIPTOCOUNTRYCODE']);
				$countryName = GetCountryById($countryID);

				$stateID = $this->GetStateId($countryID, $nvpArray['SHIPTOSTATE']);
				$stateName = GetStateById($stateID);

				$phone = '';
				if(isset($nvpArray['PHONENUM'])) {
					// phone will only be available if (see ISC-937)
					// 1. seller chooses "On (Required Field)" for contact telephone
					// 2. and, buyer ticks "Share this phone number with <store_name>"
					$phone = $nvpArray['PHONENUM'];
				}

				$shipaddress2 = '';
				if (isset($nvpArray['SHIPTOSTREET2'])) {
					$shipaddress2 = $nvpArray['SHIPTOSTREET2'];
				}

				$address = array(
					'shipfirstname'		=> $nvpArray['FIRSTNAME'],
					'shiplastname'		=> $nvpArray['LASTNAME'],
					'shipcompany'		=> '',
					'shipaddress1'		=> $nvpArray['SHIPTOSTREET'],
					'shipaddress2'		=> $shipaddress2,
					'shipcity'		=> $nvpArray['SHIPTOCITY'],
					'shipstate'		=> $stateName,
					'shipzip'		=> $nvpArray['SHIPTOZIP'],
					'shipcountry'		=> $countryName,
					'shipstateid'		=> $stateID,
					'shipcountryid'		=> $countryID,
					'shipdestination'	=> 'residential',
					'shipphone'		=> $phone
				);

				if(CustomerIsSignedIn()) {
					$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
					$customerID = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();
					$address['shipcustomerid'] = $customerID;

					// check if the customer's address we get back from paypal is already exist in the customer's ISC address book
					$addressid = $this->GetAddressID($address);

					if($addressid > 0) {
						//if address is already in ISC address book, set the ISC address id to session so it can be selected by default on the checkout page.
						$_SESSION['CHECKOUT']['SelectAddress'] = $addressid;
					} else {
						//if address isn't in ISC address book, add it to customer's address book.
						$_SESSION['CHECKOUT']['SelectAddress'] = $GLOBALS['ISC_CLASS_DB']->InsertQuery("shipping_addresses", $address, 1);
					}
				}
				$address['shipemail'] = $nvpArray['EMAIL'];
				$address['saveAddress'] = 0;

				$GLOBALS['ISC_CLASS_CHECKOUT'] = GetClass('ISC_CHECKOUT');
				//set the address to the session
				$GLOBALS['ISC_CLASS_CHECKOUT'] -> SetOrderBillingAddress($address);

				if($nvpArray['FIRSTNAME']." ".$nvpArray['LASTNAME'] != $nvpArray['SHIPTONAME']) {
					$_SESSION['CHECKOUT']['GoToCheckoutStep'] = "BillingAddress";
					$firstName = trim(preg_replace('/\s.*$/', '', $nvpArray['SHIPTONAME']));
					$lastName = trim(str_replace($firstName, '', $nvpArray['SHIPTONAME']));
					$address['shipfirstname'] = $firstName;
					$address['shiplastname'] = $lastName;
				} else {
					$_SESSION['CHECKOUT']['GoToCheckoutStep'] = "ShippingProvider";
				}
				$GLOBALS['ISC_CLASS_CHECKOUT'] -> SetOrderShippingAddress($address);


				// Only want to display paypal as the payment provider on order confirmation page, as customer has already selected the pay with paypal previously, so save paypal in provider list in session, so confirmation page will read from the session.
				$_SESSION['CHECKOUT']['ProviderListHTML'] = $this->ParseTemplate('paypalexpress.providerlist', true);

				// Skip choose a billing and shipping address step
				if(GetConfig('CheckoutType') == 'single') {
					$returnURL = $GLOBALS['ShopPath']."/checkout.php";
				} else {
					//set the address to the session
					$GLOBALS['ISC_CLASS_CHECKOUT']->SetOrderBillingAddress($address);
					$GLOBALS['ISC_CLASS_CHECKOUT']->SetOrderShippingAddress($address);
					$returnURL = $GLOBALS['ShopPath']."/checkout.php?action=choose_shipper";
				}

				header("Location: ".$returnURL);
			}
		}


		private function GetStateID($countryID, $stateName)
		{
			$stateID = GetStateByAbbrev($stateName, $countryID);

			if ($stateID) {
				return $stateID;
			}

			$stateID = GetStateByName($stateName, $countryID);
			if ($stateID) {
				return $stateID;
			}

			return 0;
		}

		/**
		* Get address ID by address details and customer ID
		*
		* @param array address details
		* @return string The generated address form.
		*
		*/
		private function GetAddressID($address)
		{
			$whereSql = '';
			foreach ($address as $field => $value) {
				if($field == 'shipcustomerid') {
					$whereSql .= $field." = ".(int)$value." AND ";
				} else {
					$whereSql .= $field." = '".$GLOBALS['ISC_CLASS_DB']->Quote($value)."' AND ";
				}
			}
			$whereSql = rtrim($whereSql, ' AND ');
			$query = "Select shipid
					From
						[|PREFIX|]shipping_addresses
					Where ".$whereSql;

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$addressid = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

			if($addressid > 0) {
				return $addressid;
			} else {
				return 0;
			}
		}

		/**
		* Sends the order details to Paypal to process
		*
		*/
		public function DoExpressCheckoutPayment()
		{
			if(isset($_COOKIE['SHOP_ORDER_TOKEN'])) {

				$orders = $this->GetOrders();
				reset($orders);
				$order = current($orders);
				$orderId = '#'.implode(', #', array_keys($orders));

				$merchant = $this->GetMerchantSettings();

				if($merchant['testmode'] == 'YES') {
					$transactionURL = $this->_testTransactionURL;
					$transactionURI = $this->_testTransactionURI;
				}
				else {
					$transactionURL = $this->_liveTransactionURL;
					$transactionURI = $this->_liveTransactionURI;
				}

				$response = $_SESSION['CHECKOUT']['PayPalExpressCheckout'];

				// unset PayPalExpress response in session
				unset($_SESSION['CHECKOUT']['PayPalExpressCheckout']);

				$shippingAddress = $this->getShippingAddress();
				if($shippingAddress['state_id'] != 0 && GetStateISO2ById($shippingAddress['state_id'])) {
					$shipstate = GetStateISO2ById($shippingAddress['state_id']);
				}
				else {
					$shipstate = isc_html_escape($shippingAddress['state']);
				}

				$currency = GetCurrencyCodeByID(GetConfig('DefaultCurrencyID'));
				$pp_array = array(
					'METHOD'	=> 'DoExpressCheckoutPayment',
					'USER'		=> $merchant['username'],
					'PWD'		=> $merchant['password'],
					'SIGNATURE'	=> $merchant['signature'],
					'VERSION'	=> '53.0',
					'TOKEN'		=> $response['TOKEN'],
					'PAYERID'	=> $response['PAYERID'],
					'PAYMENTACTION'	=> $merchant['transactionType'],
					'AMT'		=> number_format($order['total_inc_tax'], 2, '.', ''),
					'CURRENCYCODE'	=> $currency,
					'IPADDRESS'	=> $this->GetIpAddress(),
					'INVNUM'	=> $orderId,
					'NAME'		=> $shippingAddress['first_name']." ".$shippingAddress['last_name'],
					'SHIPTOSTREET'	=> $shippingAddress['address_1'],
					'SHIPTOSTREET2'	=> $shippingAddress['address_2'],
					'SHIPTOCITY'	=> $shippingAddress['city'],
					'SHIPTOSTATE'	=> $shipstate,
					'SHIPTOZIP'	=> $shippingAddress['zip'],
					'SHIPTOCOUNTRY'	=> $shippingAddress['country_iso2'],
					'PHONENUM'	=> $shippingAddress['phone'],
					'BUTTONSOURCE'	=> "ISC_ShoppingCart_EC",
					'CUSTOM'	=> $_COOKIE['SHOP_ORDER_TOKEN'] . '_' . $_COOKIE['SHOP_SESSION_TOKEN'],
					'NOTIFYURL'	=> $GLOBALS['ShopPath'].'/checkout.php?action=gateway_ping&provider='.$this->GetId(),
					'L_NAME0'	=> getLang('YourOrderFromX', array('storeName' => getConfig('StoreName'))),
					'L_AMT0'	=> number_format($order['total_inc_tax'],2,'.',''),
					'L_QTY0'	=> 1,
				);

				$paypal_query = '';
				foreach ($pp_array as $key => $value) {
					$paypal_query .= $key.'='.urlencode($value)."&";
				}
				$paypal_query = rtrim($paypal_query, '&');

				$result = $this->_ConnectToProvider($transactionURL, $transactionURI, $paypal_query);
				$nvpArray = $this->_DecodePaypalResult($result);
				$_SESSION['PayPalExpressResponse'] = $nvpArray;

				// verify payment right here to prevent man-in-the-middle attack
				$_REQUEST['o'] = md5(GetConfig('EncryptionToken').$_COOKIE['SHOP_ORDER_TOKEN']);
				$GLOBALS['ISC_CLASS_ORDER'] = GetClass('ISC_ORDER');
				$GLOBALS['ISC_CLASS_ORDER']->HandlePage();
			}
			else {
				// Invalid PayPalExpress response
				$this->SetError(GetLang('PayPalExpressInvalidOrder'));
				return false;
			}
		}

		/**
		*	Verify the order by checking the PayPal Express Checkout variables
		*/
		public function VerifyOrderPayment()
		{
			// The *only* way someone can end up here is AFTER the order has ALREADY been validated, so we pass an MD5 has of the pending
			// order token in the $_GET array and compare that to the pending token, returning true if they are equal and false if not.
			if(isset($_COOKIE['SHOP_ORDER_TOKEN']) && isset($_REQUEST['o']) && md5(GetConfig('EncryptionToken').$_COOKIE['SHOP_ORDER_TOKEN']) == $_REQUEST['o']) {


				$orders = $this->GetOrders();
				reset($orders);
				$orderId = '#'.implode(', #', array_keys($orders));

				//$orders = $this->GetOrders();
				//$orderIds = '#'.implode(', #', array_keys($orders));
				$order = LoadPendingOrderByToken($_COOKIE['SHOP_ORDER_TOKEN']);
				$orderId = '#'.$order['orderid'];

				$nvpArray = $_SESSION['PayPalExpressResponse'];
				unset($_SESSION['PayPalExpressResponse']);

				$responseMsg = isc_html_escape($nvpArray['ACK']);
				$transactionId = '';
				if (isset($nvpArray['TRANSACTIONID'])) {
					$transactionId = isc_html_escape($nvpArray['TRANSACTIONID']);
				}


				// Load the paypal transaction Type
				//$transactionType = $this->GetValue('transactiontype');


				//if transaction is successful
				if (strtolower($responseMsg) == 'success') {

					//	if($transactionType == 'Authorization') {
					if($nvpArray['PAYMENTSTATUS'] == 'Pending') {
						$paymentStatus = 'authorized';
					} else {
						$paymentStatus = 'captured';
					}

					if($nvpArray['PAYMENTTYPE'] == 'echeck' && $nvpArray['PAYMENTSTATUS'] == 'Pending') {
						$orderStatus = PAYMENT_STATUS_PENDING;
						$paymentStatus = '';
					} else {
						$orderStatus = PAYMENT_STATUS_PAID;
					}

					$updatedOrder = array(
						'ordpayproviderid' => $transactionId,
						'ordpaymentstatus' => $paymentStatus
					);
					$this->UpdateOrders($updatedOrder);

					$paypalPaymentStatus = '';
					if(isset($nvpArray['PAYMENTSTATUS'])) {
						$paypalPaymentStatus = $nvpArray['PAYMENTSTATUS'];
					}

					$paymentSuccess = sprintf(GetLang('PayPalExpressSuccess'), $orderId);
					$paymentMessage = sprintf(GetLang('PayPalExpressDetails'), $transactionId, $paypalPaymentStatus, $nvpArray['PENDINGREASON']);

					$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment',  $this->GetName()), $paymentSuccess, $paymentMessage);

					//set order status
					$this->SetPaymentStatus($orderStatus);

					return true;
				} else {

					$errorMsg = '';
					if(isset($nvpArray['L_LONGMESSAGE0'])) {
						$errorMsg = isc_html_escape($nvpArray['L_LONGMESSAGE0']);
					}

					$paypalPaymentStatus = '';
					if(isset($nvpArray['PAYMENTSTATUS'])) {
						$paypalPaymentStatus = $nvpArray['PAYMENTSTATUS'];
					}

					// Status was declined or error, show the response message as an error
					$error = sprintf(GetLang('PayPalExpressError'), $orderId);
					$errorDetails = sprintf(GetLang('PayPalExpressErrorDetails'), $transactionId, $paypalPaymentStatus, $errorMsg);

					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $error, $errorDetails);
					return false;
				}
			} else {
				return false;
			}
		}

		protected function _ConnectToProvider($transactionURL, $transactionURI, $gateway_postdata)
		{
			$responseHeader = '';


			if(function_exists("curl_exec")) {

				// Use CURL if it's available
				$ch = curl_init($transactionURL.$transactionURI);

				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_HEADER, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $gateway_postdata);
				curl_setopt($ch, CURLOPT_TIMEOUT, 60);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

				// Setup the proxy settings if there are any
				if (GetConfig('HTTPProxyServer')) {
					curl_setopt($ch, CURLOPT_PROXY, GetConfig('HTTPProxyServer'));
					if (GetConfig('HTTPProxyPort')) {
						curl_setopt($ch, CURLOPT_PROXYPORT, GetConfig('HTTPProxyPort'));
					}
				}

				if (GetConfig('HTTPSSLVerifyPeer') == 0) {
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				}

				$result = curl_exec($ch);
				$result = explode("\n\r\n", $result);
					$responseHeader = $result[0];
					$result = $result[1];

				if(curl_errno($ch)) {
					$this->SetError(GetLang('CreditCardCurlError'). $this->GetValue('displayname') . ":" .curl_error($ch));
					return false;
				}
			}
			else if(function_exists("fsockopen")) {

				$header = "POST " . $transactionURI . " HTTP/1.0\r\n";
				$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
				$header .= "Content-Length: " . strlen($gateway_postdata) . "\r\n\r\n";

				$url = parse_url($transactionURL);

				if ($url['scheme'] == 'https') {
						$url['host'] = 'ssl://'.$url['host'];
				}

				if (!isset($url['port']) || $url['port'] == '') {
					if ($url['scheme'] == 'http') {
						$url['port'] = 80;
					}
					else if ($url['scheme'] == 'https') {
						$url['port'] = 443;
					}
				}

				if($fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 30)) {
					@fputs($fp, $header . $gateway_postdata);

					// Read the body data
					$result = "";
					$responseHeader = '';
					$headerdone = false;

					while(!@feof($fp)) {
						$line = @fgets($fp, 1024);

						if(@strcmp($line, "\r\n") == 0) {
							// Header has been read
							$headerdone = true;
						}
						else if (!$headerdone) {
							// Read the header
							$responseHeader .= (string)$line;
						}
						else if($headerdone) {
							// Header has been read, read the contents
							$result .= $line;
						}
					}

				}
				else {
					$this->SetError(GetLang('CreditCardFSockError') . $this->GetValue('displayname'));
					return false;
				}
			}
			else {
				$this->SetError(GetLang('CreditCardConnectionMethod') . $this->GetValue('displayname'));
				return false;
			}

			if (empty($result)) {
				return $responseHeader;
			}

			return $result;
		}



		private function GetResponseFromProvider($transactionType, $extraFields=array())
		{
			$merchant = $this->GetMerchantSettings();

			if($merchant['testmode'] == 'YES') {
				$transactionURL = $this->_testTransactionURL;
				$transactionURI = $this->_testTransactionURI;
			}
			else {
				$transactionURL = $this->_liveTransactionURL;
				$transactionURI = $this->_liveTransactionURI;
			}


			//NOTE=$note
			$pp_array = array(
				'METHOD'			=> $transactionType,
				'USER'				=> $merchant['username'],
				'PWD'				=> $merchant['password'],
				'SIGNATURE'			=> $merchant['signature'],
				'VERSION'			=> '53.0',
			);

			$pp_array = array_merge($pp_array, $extraFields);

			$paypal_query = http_build_query($pp_array);

			$result = $this->_ConnectToProvider($transactionURL, $transactionURI, $paypal_query);
			$nvpArray = $this->_DecodePaypalResult($result);

			return $nvpArray;
		}



		public function DelayedCapture($order, &$message = '', $amt = 0)
		{

			$orderId = $order['orderid'];
			$originalTransId = $order['ordpayproviderid'];

			if($amt == 0) {
				$message = GetLang('DelayedCaptureIncorrectAmount');
				return false;
			}

			$extraFields = array(
								'AUTHORIZATIONID' => $originalTransId,
								'AMT' => number_format($amt,2,'.',''),
								'CURRENCYCODE' => GetCurrencyCodeByID(GetConfig('DefaultCurrencyID')),
								'COMPLETETYPE' => 'Complete'
								);

			$nvpArray = $this->GetResponseFromProvider('DoCapture', $extraFields);
			if(empty($nvpArray)) {
				$message = GetLang('ErrorConnectToPaypal');
				return false;
			}

			$transactionId = '';
			if (isset($nvpArray['TRANSACTIONID'])) {
				$transactionId = isc_html_escape($nvpArray['TRANSACTIONID']);
			}

			if(strtolower($nvpArray['ACK']) == 'success') {
				$message = GetLang('DelayedCaptureSuccess');

				$updatedOrder = array(
					'ordpaymentstatus' => 'captured',
					'ordpayproviderid' => $transactionId,
				);
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$orderId."'");


				$DelayedCaptureSuccess = sprintf(GetLang('DelayedCaptureSuccessLog'), $orderId, $transactionId);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment',  $this->GetName()), $DelayedCaptureSuccess, $message);
				return true;

			} else {
				$errorMsg = '';
				if(isset($nvpArray['L_LONGMESSAGE0'])) {
					$errorMsg = isc_html_escape($nvpArray['L_LONGMESSAGE0']);
				}

				$message = sprintf(GetLang('DelayedCaptureFailed'), $errorMsg);

				$DelayedCaptureError = sprintf(GetLang('DelayedCaptureError'), $orderId);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $DelayedCaptureError, $errorMsg);
				return false;
			}
		}


		public function DoRefund($order, &$message = '', $amt = 0)
		{
			if($amt == 0) {
				$message = GetLang('RefundIncorrectAmount');
				return false;
			}


			$originalTransId = $order['ordpayproviderid'];
			$orderId = $order['orderid'];

			$orderAmount = number_format($order['total_inc_tax'],2,'.','');
			$amt = number_format($amt,2,'.','');
			$TotalRefundedAmt = number_format($amt+$order['ordrefundedamount'],2,'.','');

			$extraFields = array();
			if($orderAmount == $amt) {
				$extraFields = array('REFUNDTYPE' => 'Full',
									'TRANSACTIONID' => $originalTransId);
			}
			elseif ($orderAmount > $amt) {
				$extraFields = array(
									'REFUNDTYPE' => 'Partial',
									'AMT' => $amt,
									'CURRENCYCODE' => GetCurrencyCodeByID(GetConfig('DefaultCurrencyID')),
									'TRANSACTIONID' => $originalTransId,
								);
			}

			$nvpArray = $this->GetResponseFromProvider('RefundTransaction', $extraFields);
			if(empty($nvpArray)) {
				$message = GetLang('ErrorConnectToPaypal');
				return false;
			}

			$transactionId = '';
			if (isset($nvpArray['REFUNDTRANSACTIONID'])) {
				$transactionId = isc_html_escape($nvpArray['REFUNDTRANSACTIONID']);
			}

			if (strtolower($nvpArray['ACK']) == 'success') {
				$message = GetLang('RefundSuccess');

				//if total refunded is less than the order total amount
				if($TotalRefundedAmt < $orderAmount) {
					$paymentStatus = 'partially refunded';
				} else {
					$paymentStatus = 'refunded';
				}


				$updatedOrder = array(
					'ordpaymentstatus' => $paymentStatus,
					'ordrefundedamount'	=> $TotalRefundedAmt,
				);
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$orderId."'");


				$refundSuccess = sprintf(GetLang('RefundSuccessLog'), $orderId, $transactionId);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment',  $this->GetName()), $refundSuccess, $message);
				return true;


			} else {
				$errorMsg = '';
				if(isset($nvpArray['L_LONGMESSAGE0'])) {
					$errorMsg = isc_html_escape($nvpArray['L_LONGMESSAGE0']);
				}

				$message = sprintf(GetLang('RefundFailed'), $errorMsg);

				$refundError = sprintf(GetLang('RefundError'), $orderId);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $refundError, $errorMsg);
				return false;
			}
		}



		public function DoVoid($orderId, $originalTransId, &$message = '')
		{

			$extraFields = array(
								'AUTHORIZATIONID' => $originalTransId,
								);

			$nvpArray = $this->GetResponseFromProvider('DoVoid', $extraFields);
			if(empty($nvpArray)) {
				$message = GetLang('ErrorConnectToPaypal');
				return false;
			}

			$transactionId = '';
			if (isset($nvpArray['AUTHORIZATIONID'])) {
				$transactionId = isc_html_escape($nvpArray['AUTHORIZATIONID']);
			}

			if(strtolower($nvpArray['ACK']) == 'success') {
				$message = GetLang('VoidSuccess');

				$updatedOrder = array(
					'ordpaymentstatus' => 'void',
					'ordpayproviderid' => $transactionId,
				);

				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$orderId."'");


				$voidSuccess = sprintf(GetLang('VoidSuccessLog'), $orderId, $transactionId);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment',  $this->GetName()), $voidSuccess, $message);
				return true;


			} else {
				$errorMsg = '';
				if(isset($nvpArray['L_LONGMESSAGE0'])) {
					$errorMsg = isc_html_escape($nvpArray['L_LONGMESSAGE0']);
				}

				$message = sprintf(GetLang('VoidFailed'), $errorMsg);

				$voidError = sprintf(GetLang('VoidError'), $orderId);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $voidError, $errorMsg);
				return false;
			}
		}

		/**
		 * Process the PayPal IPN ping back.
		 */
		public function ProcessGatewayPing()
		{
			//make it only work for echeck pings
			if($_POST['payment_type'] != 'echeck' || $_POST['payment_status']== 'Pending') {
				exit;
			}

			if(!isset($_POST['custom'])) {
				exit;
			}

			$sessionToken = explode('_', $_REQUEST['custom'], 2);

			$this->SetOrderData(LoadPendingOrdersByToken($sessionToken[0]));

			$amount = number_format($this->GetGatewayAmount(), 2, '.', '');

			if($amount == 0) {
				exit;
			}

			// Perform a post back to PayPal with exactly what we received in order to validate the request
			$queryString = array();
			$queryString[] = "cmd=_notify-validate";
			foreach($_POST as $k => $v) {
				$queryString[] = $k."=".urlencode($v);
			}
			$queryString = implode('&', $queryString);

			$testMode = $this->GetValue('testmode');
			if($testMode == 'YES') {
				$verifyURL = 'http://www.sandbox.paypal.com/cgi-bin/webscr';
			}
			else {
				$verifyURL = 'http://www.paypal.com/cgi-bin/webscr';
			}

			$response = PostToRemoteFileAndGetResponse($verifyURL, $queryString);

			// This pingback was not valid
			if($response != "VERIFIED") {
				// Bad order details
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('PayPalExpressErrorInvalid'), "RESPONSE : "  .$response);
				return false;
			}

			// If we're still here, the ping back was valid, so we check the payment status and everything else match up

			// Has the transaction been processed before? If so, we can't process it again
			$transaction = GetClass('ISC_TRANSACTION');

			$paypalEmail = $this->GetValue('email');

			if(!isset($_POST['receiver_email']) || !isset($_POST['mc_gross']) || !isset($_POST['payment_status'])) {
				// Bad order details
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('PayPalExpressErrorInvalid'), print_r($_POST, true));
				return false;
			}

			// The values passed don't match what we expected
			if(($_POST['mc_gross'] != $amount && !in_array($_POST['payment_status'], array('Reversed', 'Refunded', 'Canceled_Reversed')))) {
				$errorMsg = sprintf(GetLang('PayPalExpressErrorInvalidMsg'), $_POST['mc_gross'], $amount, $_POST['receiver_email'], $paypalEmail, $_POST['payment_status']);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('PayPalExpressErrorInvalid'), $errorMsg);
				return false;
			}

			$currency = GetDefaultCurrency();

			if($_POST['mc_currency'] != $currency['currencycode']) {
				$errorMsg = sprintf(GetLang('PayPalExpressErrorInvalidMsg3'), $currency['currencycode'], $_POST['mc_currency']);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('PayPalExpressErrorInvalid'), $errorMsg);
				return false;
			}

			$newTransaction = array(
				'providerid' => $this->GetId(),
				'transactiondate' => time(),
				'transactionid' => $_POST['txn_id'],
				'orderid' => array_keys($this->GetOrders()),
				'message' => '',
				'status' => '',
				'amount' => $_POST['mc_gross'],
				'extrainfo' => array()
			);

			$orderPaymentStatus = '';
			switch($_POST['payment_status']) {
				case "Completed":
					$orderPaymentStatus = 'captured';
					$newTransaction['status'] = TRANS_STATUS_COMPLETED;
					$newOrderStatus = ORDER_STATUS_AWAITING_FULFILLMENT;
					break;
				case "Pending":
					if($_POST['payment_type'] != 'echeck') {
						$orderPaymentStatus = 'authorized';
					}
					$newTransaction['status'] = TRANS_STATUS_PENDING;
					$newOrderStatus = ORDER_STATUS_AWAITING_PAYMENT;
					$newTransaction['extrainfo']['reason'] = $_POST['pending_reason'];
					break;
				case "Denied":
					$newTransaction['status'] = TRANS_STATUS_DECLINED;
					$newOrderStatus = ORDER_STATUS_DECLINED;
					break;
				case "Failed":
					$newTransaction['status'] = TRANS_STATUS_FAILED;
					$newOrderStatus = ORDER_STATUS_DECLINED;
					break;
				case "Refunded":
					$newTransaction['status'] = TRANS_STATUS_REFUND;
					$newOrderStatus = ORDER_STATUS_REFUNDED;
					break;
				case "Reversed":
					$newTransaction['status'] = TRANS_STATUS_CHARGEBACK;
					$newOrderStatus = ORDER_STATUS_REFUNDED;
					break;
				case "Canceled_Reversal":
					$newTransaction['status'] = TRANS_STATUS_CANCELLED_REVERSAL;
					$newOrderStatus = ORDER_STATUS_REFUNDED;
					break;
			}


			$previousTransaction = $transaction->LoadByTransactionId($_POST['txn_id'], $this->GetId());

			// Already processed before, HALT and log error
			if(is_array($previousTransaction) && $previousTransaction['transactionid'] && $previousTransaction['status'] == $newTransaction['status']) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang('PayPalExpressTransactionAlreadyProcessed'), $_POST['txn_id']));
				return false;
			}

			$newTransaction['message'] = $this->GetPayPalTransactionMessage($_POST);

			$transactionId = $transaction->Create($newTransaction);
			$oldOrderStatus = $this->GetOrderStatus();
			// If the order was previously incomplete, we need to do some extra work
			if($oldOrderStatus == ORDER_STATUS_INCOMPLETE) {
				// If a customer doesn't return to the store from PayPal, their cart will never be
				// emptied. So what we do here, is if we can, load up the existing customers session
				// and empty the cart and kill the checkout process. When they next visit the store,
				// everything should be "hunky-dory."
				session_write_close();
				$session = new ISC_SESSION($sessionToken[1]);
				EmptyCartAndKillCheckout();
			}

			// Update the status for all orders that we've just received the payment for
			foreach($this->GetOrders() as $orderId => $order) {
				$status = $newOrderStatus;
				// If it's a digital order & awaiting fulfillment, automatically complete it
				if($order['ordisdigital'] && $status == ORDER_STATUS_AWAITING_FULFILLMENT) {
					$status = ORDER_STATUS_COMPLETED;
				}
				UpdateOrderStatus($orderId, $status);
			}

			$updatedOrder = array(
				'ordpayproviderid' => $_POST['txn_id'],
				'ordpaymentstatus' => $orderPaymentStatus,
			);

			$this->UpdateOrders($updatedOrder);

			// This was a successful order
			$oldStatus = GetOrderStatusById($oldOrderStatus);
			if(!$oldStatus) {
				$oldStatus = 'Incomplete';
			}
			$newStatus = GetOrderStatusById($newOrderStatus);
			$extra = sprintf(GetLang('PayPalExpressSuccessDetails'), implode(', ', array_keys($this->GetOrders())), $amount, $_POST['txn_id'], $_POST['payment_status'], $newStatus, $oldStatus);
			$successMsg = sprintf(GetLang('PayPalExpressSuccess'), implode(', ', array_keys($this->GetOrders())));
			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $successMsg, $extra);
			return true;
		}


		/**
		 * Build and return a transaction message for a PayPal IPN response. This is saved to the transactions table.
		 *
		 * @param array Array of information (from $_POST) about the IPN response.
		 * @return string The language string for this transaction status.
		 */
		private function GetPayPalTransactionMessage($paypalData)
		{
			switch($paypalData['payment_status']) {
				case "Completed":
				case "Denied":
				case "Failed":
					$status = str_replace('_', '', $paypalData['payment_status']);
					return GetLang('PayPalExpressTransactionStatus'.$status);
				case "Pending":
					switch($paypalData['pending_reason']) {
						case "address":
							$langString = 'Address';
							break;
						case "echeck":
							$langString = 'Echeck';
							break;
						case "intl":
							$langString = 'Intl';
							break;
						case "multi-currency":
							$langString = 'MC';
							break;
						case "unilateral":
							$langString = 'Unilateral';
							break;
						case "upgrade":
							$langString = 'Upgrade';
							break;
						case "verify":
							$langString = 'Verify';
							break;
						default:
							$langString ='';
					}
					return GetLang('PayPalExpressTransactionStatusPending'.$langString);
				case "Reversed":
				case "Refunded":
				case "Canceled_Reversal":
					switch($paypalData['reason_code']) {
						case "chargeback":
							$langString = 'PayPalExpressTransactionStatusReversedChargeback';
							break;
						case "guarantee":
							$langString = 'PayPalExpressTransactionStatusReversedGuarantee';
							break;
						case "buyer-complaint":
							$langString = 'PayPalExpressTransactionStatusReversedBuyerComplaint';
							break;
						case "refund":
							$langString = 'PayPalExpressTransactionStatusReversedRefund';
						default:
							$status = str_replace('_', '', $paypalData['payment_status']);
							$langString = 'PayPalExpressTransactionStatus'.$status;
					}
					return GetLang($langString);
			}
		}
	}
