<?php
	class CHECKOUT_PAYPALPAYMENTSPROUS extends ISC_GENERIC_CREDITCARD
	{
		public $_showBothButtons = true;
		private $_cardtype = '';

		/**
		 * @var boolean Does this provider support orders from more than one vendor?
		 */
		protected $supportsVendorPurchases = true;

		/**
		 * @var boolean Does this provider support shipping to multiple addresses?
		 */
		protected $supportsMultiShipping = true;

		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the PayPal Payments Pro checkout module
			$this->_requiresSSL= true;
			$this->_languagePrefix = 'PayPalPaymentsPro';
			$this->_requiresCurl = true;
			$this->_curlSupported = true;

			$this->_liveTransactionURL = 'https://api-3t.paypal.com';
			$this->_testTransactionURL = 'https://api-3t.sandbox.paypal.com';
			$this->_liveTransactionURI = '/nvp';
			$this->_testTransactionURI = '/nvp';

			// for 3D secure
			$this->_cardinalLiveTransactionURL = 'https://paypal.cardinalcommerce.com/maps/txns.asp';
			$this->_cardinalTestTransactionURL = 'https://centineltest.cardinalcommerce.com/maps/txns.asp';

			$this->_currenciesSupported = array('USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD');
			$this->setSupportedCards();

			$this->_image = "paypal_logo.gif";
			$this->_file = basename(__FILE__);
			parent::__construct();
		}

		/**
		* Sets the supported card types based on the default currency
		*
		*/
		private function setSupportedCards()
		{
			$defaultCurrency = GetDefaultCurrency();
			$currencyCode = $defaultCurrency['currencycode'];

			switch ($currencyCode) {
				case 'USD':
					$supportedCards = array('VISA', 'MC', 'DISCOVER', 'AMEX');
					break;
				case 'GBP':
					$supportedCards = array('VISA', 'MC', 'MAESTRO', 'SOLO', 'SWITCH');
					break;
				default:
					$supportedCards = array('VISA', 'MC');
			}

			$this->_cardsSupported = $supportedCards;
		}


		/**
		* Custom variables for the checkout module. Custom variables are stored in the following format:
		* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
		* variable_type types are: text,number,password,radio,dropdown
		* variable_options is used when the variable type is radio or dropdown and is a name/value array.
		*/
		public function setcustomvars()
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

			$this->_variables['cardcode'] = array(
				"name" => GetLang('RequireSecurityCode'),
				"type" => "dropdown",
				"help" => GetLang('PayPalPaymentsProSecurityCodeHelp'),
				"default" => "no",
				"savedvalue" => array(),
				"required" => true,
				"options" => array(
					GetLang('PayPalPaymentsProSecurityCodeNo') => "NO",
					GetLang('PayPalPaymentsProSecurityCodeYes') => "YES"
				),
				"multiselect" => false
			);

			$this->_variables['cardinalprocessorid'] = array(
				"name" => GetLang('CardinalProcessorId'),
				"type" => "textbox",
				"help" => GetLang('CardinalProcessorIdHelp'),
				"default" => "",
				"savedvalue" => array(),
				"required" => false,
			);

			$this->_variables['cardinalmerchantid'] = array(
				"name" => GetLang('CardinalMerchantId'),
				"type" => "textbox",
				"help" => GetLang('CardinalMerchantIdHelp'),
				"default" => "",
				"savedvalue" => array(),
				"required" => false,
			);

			$this->_variables['cardinaltransactionpwd'] = array(
				"name" => GetLang('CardinalTransactionPwd'),
				"type" => "password",
				"help" => GetLang('CardinalTransactionPwdHelp'),
				"default" => "",
				"savedvalue" => array(),
				"required" => false,
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
		* ProcessPaymentForm
		* Process and validate input from a payment form for this particular
		* gateway.
		*
		* @return boolean True if valid details and payment has been processed. False if not.
		*/
		protected function _ConstructPostData($postData, $additionalFields=array())
		{

			//if the postdata is got from session then the ccno is encrypted, we need to decrypt it
			if(isset($_SESSION['CHECKOUT']['CardDetails']['ccno']) && $_SESSION['CHECKOUT']['CardDetails']['ccno'] == $postData['ccno']) {
				$ccnum = $this->_CCDecrypt($postData['ccno']);
			} else {
				$ccnum = $postData['ccno'];
			}
			$this->_cardtype = Store_CreditCard::getCardType($ccnum);
			$ccname = $postData['name'];
			$ccTypeName = $postData['cctype'];
			$ccexpm = str_pad($postData['ccexpm'], 2, '0', STR_PAD_LEFT);
			$ccexpy = $postData['ccexpy']+2000;
			$ccexp = sprintf("%s%s", $ccexpm, $ccexpy);
			$cccvd = $postData['cccvd'];

			$startDateM = $postData['ccissuedatem'];
			$startDateY = $postData['ccissuedatey'] + 2000;
			$startDate = $startDateM . $startDateY;

			$cardMap = array(
				'VISA' 		=> 'Visa',
				'MC'		=> 'MasterCard',
				'AMEX'		=> 'Amex',
				'DISCOVER'	=> 'Discover',
				'MAESTRO'	=> 'Maestro',
				'SWITCH'	=> 'Maestro',
				'SOLO'		=> 'Solo',
			);

			$cctype = $cardMap[$ccTypeName];

			$currency = GetCurrencyCodeByID(GetConfig('DefaultCurrencyID'));

			$merchant = $this->GetMerchantSettings();

			//load all orders for this transaction
			$orders = $this->GetOrders();
			if(empty($orders)) {
				$orderData = LoadPendingOrdersByToken($_COOKIE['SHOP_ORDER_TOKEN']);
				$this->SetOrderData($orderData);
				$orders = $this->GetOrders();
			}
			$order = current($orders);
			$orderIds = '#'.implode(', #', array_keys($orders));

			$orderdesc = sprintf(GetLang('YourOrderFrom'), $GLOBALS['StoreName']).' ('.$orderIds.')';
			$custip = $this->GetIpAddress();

			// Grab the billing details for the order
			$billingDetails = $this->GetBillingDetails();
			$customeremail = $billingDetails['ordbillemail'];

			//get bill state in ISO code
			if($billingDetails['ordbillstateid'] != 0 && GetStateISO2ById($billingDetails['ordbillstateid'])) {
				$billstate = GetStateISO2ById($billingDetails['ordbillstateid']);
			}
			else {
				$billstate = isc_html_escape($billingDetails['ordbillstate']);
			}

			// get the shipping details
			$shippingAddress = $this->getShippingAddress();


			//get ship state in ISO code
			if($shippingAddress['state_id'] != 0) {
				$shipstate = GetStateISO2ById($shippingAddress['state_id']);
			}
			else {
				$shipstate = isc_html_escape($shippingAddress['state']);
			}

			// Arrange the data into name/value pairs ready to send
			$pp_values = array (

				'METHOD'			=> 'DoDirectPayment',
				'USER'				=> $merchant['username'],
				'PWD'				=> $merchant['password'],
				'SIGNATURE'			=> $merchant['signature'],
				'VERSION'			=> '61.0',
				'PAYMENTACTION'		=> $merchant['transactionType'],

				/*customer details*/
				'IPADDRESS'			=> $custip,
				'FIRSTNAME'			=> $billingDetails['ordbillfirstname'],
				'LASTNAME'			=> $billingDetails['ordbilllastname'],
				'STREET'			=> $billingDetails['ordbillstreet1']." ".$billingDetails['ordbillstreet2'],
				'CITY'				=> $billingDetails['ordbillsuburb'],
				'STATE'				=> $billstate,
				'ZIP'				=> $billingDetails['ordbillzip'],
				'COUNTRYCODE'		=> $billingDetails['ordbillcountrycode'],
				'EMAIL'				=> $customeremail,
				'PHONENUM'			=> $billingDetails['ordbillphone'],

				/*shipping details*/
				'SHIPTONAME'		=> $shippingAddress['first_name']." ".$shippingAddress['last_name'],
				'SHIPTOSTREET'		=> $shippingAddress['address_1'],
				'SHIPTOSTREET2'		=> $shippingAddress['address_2'],
				'SHIPTOCITY'		=> $shippingAddress['city'],
				'SHIPTOSTATE'		=> $shipstate,
				'SHIPTOZIP'			=> $shippingAddress['zip'],
				'SHIPTOCOUNTRYCODE'	=> $shippingAddress['country_iso2'],
				'SHIPTOPHONENUM'	=> $shippingAddress['phone'],

				/*payment details*/
				'CREDITCARDTYPE'	=> $cctype,
				'ACCT'				=> $ccnum,
				'EXPDATE'			=> $ccexp,
				'CVV2'				=> $cccvd,
				'CURRENCYCODE'		=> $currency,
				'AMT'				=> number_format($order['total_inc_tax'],2,'.',''),

				'INVNUM'			=> $orderIds,
				'BUTTONSOURCE'		=> 'ISC_ShoppingCart_DP',
			);

			if ($this->CardTypeHasIssueNo($ccTypeName)) {
				$pp_values['STARTDATE'] = $startDate;
				$pp_values['ISSUENUMBER'] = $postData['ccissueno'];
			}

			if(!empty($additionalFields)) {
				$pp_values = array_merge($pp_values, $additionalFields);
			}

			$paypal_query = http_build_query($pp_values);

			return $paypal_query;
		}

		/**
		* save the response in session and redirect customer to finish order
		* @param string $response the response returned from paypal
		* @param bool $manualPayment if it's a manual payment form control panel
		*
		*/
		protected function _HandleResponse($response, $manualPayment = false)
		{
			//decode response and fetch it into array
			$nvpArray = $this->_DecodePaypalResult($response);

			$orders = $this->GetOrders();
			$orderIds = '#'.implode(', #', array_keys($orders));


			$responseMsg = isc_html_escape($nvpArray['ACK']);
			$transactionId = '';
			if (isset($nvpArray['TRANSACTIONID'])) {
				$transactionId = isc_html_escape($nvpArray['TRANSACTIONID']);
			}



			$orderStatus = PAYMENT_STATUS_PAID;

			// Load the paypal transaction Type
			$transactionType = $this->GetValue('transactiontype');
			if($transactionType == 'Authorization') {
				$paymentStatus = 'authorized';
			} elseif ($transactionType == 'Sale') {
				$paymentStatus = 'captured';
			}


			if(isset($nvpArray['PAYMENTTYPE']) && $nvpArray['PAYMENTTYPE'] == 'echeck') {
				$orderStatus = PAYMENT_STATUS_PENDING;
				$paymentStatus = '';
			} else {
				$orderStatus = PAYMENT_STATUS_PAID;
			}

			//if transaction is successful
			if (isc_substr(isc_strtolower($responseMsg), 0, 7) == 'success') {

				$order = current($orders);
				$cc_vars = array(
					"cardtype" => $this->_cardtype,
				);

				// Is there any existing extra info for the pending order?
				$extraInfo = serialize($cc_vars);
				if($order['extrainfo'] != "") {
					$extraArray = @unserialize($order['extrainfo']);
					if(is_array($extraArray)) {
						$extraInfo = serialize(array_merge($extraArray, $cc_vars));
					}
				}

				$updatedOrder = array(
					'ordpayproviderid' => $transactionId,
					'ordpaymentstatus' => $paymentStatus,
					'extrainfo' => $extraInfo,
				);
				$this->UpdateOrders($updatedOrder);


				$paypalPaymentStatus = '';
				if(isset($nvpArray['PAYMENTSTATUS'])) {
					$paypalPaymentStatus = $nvpArray['PAYMENTSTATUS'];
				}
				$extra = '';
				if(isset($nvpArray['PENDINGREASON'])) {
					$extra = "Pending Reason: ".$nvpArray['PENDINGREASON'];
				}
				$paymentSuccess = sprintf(GetLang('PayPalPaymentsProSuccess'), $orderIds);
				$paymentMessage = sprintf(GetLang('PayPalPaymentsProDetails'), $transactionId, $paypalPaymentStatus, $extra);


				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment',  $this->GetName()), $paymentSuccess, $paymentMessage);

				//set order status
				$this->SetPaymentStatus($orderStatus);
				return true;
			} else {

				$errorMsg = '';
				if(isset($nvpArray['L_LONGMESSAGE0']) && isset($nvpArray['L_SHORTMESSAGE0'])) {
					$errorMsg = isc_html_escape($nvpArray['L_SHORTMESSAGE0'])."<br />".isc_html_escape($nvpArray['L_LONGMESSAGE0']);
				}

				$paypalPaymentStatus = '';
				if(isset($nvpArray['PAYMENTSTATUS'])) {
					$paypalPaymentStatus = $nvpArray['PAYMENTSTATUS'];
				}

				// Status was declined or error, show the response message as an error
				$error = sprintf(GetLang('PayPalPaymentsProError'), $orderIds);
				$errorDetails = sprintf(GetLang('PayPalPaymentsProErrorDetails'), $transactionId, $paypalPaymentStatus, $errorMsg);
				$this->SetError($errorDetails);

				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $error, $errorDetails);
				if ($manualPayment === true) {
					return false;
				} else {
					$this->RedirectToOrderConfirmation(GetLang('UnauthorizedMessage'), true);
				}
			}
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

			// Is PayPal Payments setup in test or live mode?
			$merchant['testmode'] = $this->GetValue('testmode');

			return $merchant;
		}

		/**
		*	Verify the order by checking the PayPalPaymentsPro Pro variables
		*/
		public function VerifyOrderPayment()
		{
			if(isset($_COOKIE['SHOP_ORDER_TOKEN']) && isset($_REQUEST['o']) && md5(GetConfig('EncryptionToken').$_COOKIE['SHOP_ORDER_TOKEN']) == $_REQUEST['o']) {
				if(isset($_REQUEST['success']) && $_REQUEST['success']==1) {
					$this->SetPaymentStatus(PAYMENT_STATUS_PAID);
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		/**
		 * Returns the checkout button for this specific module. Paypal Express Checkout requires that a
		 * seperate button be used for checking out using them
		 *
		 * @return string The html to show for the button
		 **/
		public function GetCheckoutButton()
		{
			return $this->ParseTemplate('paypalpaymentsprous.button', true);
		}


		public function SetCheckoutData()
		{
			if(!GetConfig('GuestCheckoutEnabled') && !CustomerIsSignedIn()) {
				@ob_end_clean();
				header("Location: ".$GLOBALS['ShopPath'].'/checkout.php?action=checkout&bad_login=1');
				exit;
			}
			unset($_COOKIE['SHOP_ORDER_TOKEN']);
			if(!isset($_REQUEST['token'])) {
				$this->SetExpressCheckout();
			} else {
				$this->GetExpressCheckoutDetails();
			}
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
				'METHOD'	=> 'SetExpressCheckout',
				'USER'		=> $merchant['username'],
				'PWD'		=> $merchant['password'],
				'SIGNATURE'	=> $merchant['signature'],
				'VERSION'	=> '53.0',
				'PAYMENTACTION'	=> $merchant['transactionType'],
				'AMT'		=> number_format($amount,2,'.',''),
				'CURRENCYCODE'	=> $currency,
				'PAYMENTACTION'	=> $merchant['transactionType'],
				'RETURNURL'	=> $GLOBALS['ShopPath']."/checkout.php?action=set_external_checkout&provider=paypalpaymentsprous",
				'CANCELURL'	=> $GLOBALS['ShopPath']."/cart.php",
				'NOSHIPPING'	=> 0,
				'NOTIFYURL'	=> $GLOBALS['ShopPath'].'/checkout.php?action=gateway_ping&provider='.$this->GetId(),
			);
			$paypal_query = http_build_query($pp_array);


			$result = $this->_ConnectToProvider($transactionURL, $transactionURI, $paypal_query);
			$nvpArray = $this->_DecodePaypalResult($result);
			//if data is sent to paypal successfully, a token for this transaction will return from paypal
			if(strtolower($nvpArray['ACK']) == 'success') {
				// Redirect to paypal.com here
				$token = $nvpArray["TOKEN"];
				$PayPalURL = $PaypalExpressCheckoutURL.$token;
				header("Location: ".$PayPalURL);
			} else {
				//Redirecting to APIError.php to display errors.
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
								'METHOD'			=> 'GetExpressCheckoutDetails',
								'USER'				=> $merchant['username'],
								'PWD'				=> $merchant['password'],
								'SIGNATURE'			=> $merchant['signature'],
								'VERSION'			=> '53.0',
								'PAYMENTACTION'		=> $merchant['transactionType'],
								'TOKEN'				=> $_REQUEST['token'],
								'NOTIFYURL'			=> $GLOBALS['ShopPath'].'/checkout.php?action=gateway_ping&provider='.$this->GetId(),

							);

			$paypal_query = http_build_query($pp_array);

			// get the customer details from paypal
			$result = $this->_ConnectToProvider($transactionURL, $transactionURI, $paypal_query);
			$nvpArray = $this->_DecodePaypalResult($result);

			if(strtolower($nvpArray['ACK']) == 'success') {
				$countryName = trim($nvpArray['SHIPTOCOUNTRYNAME']);
				$query = "select
								countryid
							from
								[|PREFIX|]countries
							where
								countryname = '".$GLOBALS['ISC_CLASS_DB']->Quote($countryName)."'";

				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				$countryID = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

				$stateName = trim($nvpArray['SHIPTOSTATE']);
				$query = "Select
								stateid
							from
								[|PREFIX|]country_states
							where
								stateabbrv = '".$GLOBALS['ISC_CLASS_DB']->Quote($stateName)."'
								AND
								statecountry = '".$GLOBALS['ISC_CLASS_DB']->Quote($countryID)."'
								";

				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				$stateID = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

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
					'shipstate'		=> $nvpArray['SHIPTOSTATE'],
					'shipzip'		=> $nvpArray['SHIPTOZIP'],
					'shipcountry'		=> $countryName,
					'shipstateid'		=> $stateID,
					'shipcountryid'		=> $countryID,
					'shipdestination'	=> 'residential',
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
				$GLOBALS['ISC_CLASS_CHECKOUT'] -> SetOrderShippingAddress($address);

				$_SESSION['CHECKOUT']['PayPalExpressCheckout'] = $nvpArray;
				// Only want to display paypal as the payment provider on order confirmation page, as customer has already selected the pay with paypal previously, so save paypal in provider list in session, so confirmation page will read from the session.
				$_SESSION['CHECKOUT']['ProviderListHTML'] = $this->ParseTemplate('paypalpaymentsprous.providerlist', true);

				//skip choose a billing and shipping address step
				if(GetConfig('CheckoutType') == 'single') {
					$returnURL = $GLOBALS['ShopPath']."/checkout.php";
					$_SESSION['CHECKOUT']['GoToCheckoutStep'] = "ShippingProvider";
				} else {
					$returnURL = $GLOBALS['ShopPath']."/checkout.php?action=choose_shipper";
				}
				header("Location: ".$returnURL);
			}
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
				$orderIds = '#'.implode(', #', array_keys($orders));
				$order_desc = sprintf(GetLang('YourOrderFrom'), $GLOBALS['StoreName']).' ('.$orderIds.')';

				$merchant = $this->GetMerchantSettings();

				if($merchant['testmode'] == 'YES') {
					$transactionURL = $this->_testTransactionURL;
					$transactionURI = $this->_testTransactionURI;
				}
				else {
					$transactionURL = $this->_liveTransactionURL;
					$transactionURI = $this->_liveTransactionURI;
				}

				$amount = $this->GetGatewayAmount();

				$response = $_SESSION['CHECKOUT']['PayPalExpressCheckout'];

				// unset PayPalPaymentsPro response in session
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
					'AMT'		=> number_format($amount,2,'.',''),
					'CURRENCYCODE'	=> $currency,
					'IPADDRESS'	=> $this->GetIpAddress(),
					'INVNUM'	=> $orderIds,
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
				);

				$paypal_query = http_build_query($pp_array);

				$result = $this->_ConnectToProvider($transactionURL, $transactionURI, $paypal_query);
				//$nvpArray = $this->_DecodePaypalResult($result);
				//$_SESSION['PayPalPaymentsProResponse'] = $nvpArray;


				if($this->_HandleResponse($result)) {
					$success = 1;
				} else {
					$success = 0;
				}

				$_REQUEST['o'] = md5(GetConfig('EncryptionToken').$_COOKIE['SHOP_ORDER_TOKEN']);
				$_REQUEST['success'] = $success;
				$GLOBALS['ISC_CLASS_ORDER'] = GetClass('ISC_ORDER');
				$GLOBALS['ISC_CLASS_ORDER']->HandlePage();
			}
			else {
				// Invalid PayPalPaymentsPro response
				$this->SetError(GetLang('PayPalPaymentsProInvalidOrder'));
				return false;
			}
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
			//make it only work for echeck pings,
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
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('PayPalErrorInvalid'), "RESPONSE : "  .$response);
				return false;
			}

			// If we're still here, the ping back was valid, so we check the payment status and everything else match up


			$paypalEmail = $this->GetValue('email');

			if(!isset($_POST['receiver_email']) || !isset($_POST['mc_gross']) || !isset($_POST['payment_status'])) {
				// Bad order details
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('PayPalErrorInvalid'), print_r($_POST, true));
				return false;
			}

			// The values passed don't match what we expected
			if(($_POST['mc_gross'] != $amount && !in_array($_POST['payment_status'], array('Reversed', 'Refunded', 'Canceled_Reversed')))) {
				$errorMsg = sprintf(GetLang('PayPalErrorInvalidMsg'), $_POST['mc_gross'], $amount, $_POST['receiver_email'], $paypalEmail, $_POST['payment_status']);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('PayPalErrorInvalid'), $errorMsg);
				return false;
			}

			$currency = GetDefaultCurrency();

			if($_POST['mc_currency'] != $currency['currencycode']) {
				$errorMsg = sprintf(GetLang('PayPalErrorInvalidMsg3'), $currency['currencycode'], $_POST['mc_currency']);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('PayPalErrorInvalid'), $errorMsg);
				return false;
			}

			// Has the transaction been processed before? If so, we can't process it again
			$transaction = GetClass('ISC_TRANSACTION');

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
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang('PayPalTransactionAlreadyProcessed'), $_POST['txn_id']));
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
			$extra = sprintf(GetLang('PayPalSuccessDetails'), implode(', ', array_keys($this->GetOrders())), $amount, $_POST['txn_id'], $_POST['payment_status'], $newStatus, $oldStatus);

			$successMsg = sprintf(GetLang('PayPalPaymentsProSuccess'), implode(', ', array_keys($this->GetOrders())));

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
					return GetLang('PayPalTransactionStatus'.$status);
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
					return GetLang('PayPalTransactionStatusPending'.$langString);
				case "Reversed":
				case "Refunded":
				case "Canceled_Reversal":
					switch($paypalData['reason_code']) {
						case "chargeback":
							$langString = 'PayPalTransactionStatusReversedChargeback';
							break;
						case "guarantee":
							$langString = 'PayPalTransactionStatusReversedGuarantee';
							break;
						case "buyer-complaint":
							$langString = 'PayPalTransactionStatusReversedBuyerComplaint';
							break;
						case "refund":
							$langString = 'PayPalTransactionStatusReversedRefund';
						default:
							$status = str_replace('_', '', $paypalData['payment_status']);
							$langString = 'PayPalTransactionStatus'.$status;
					}
					return GetLang($langString);
			}
		}

		/**
		* get the ISO numeric currency code
		* @param currency the currency code in alphabet format
		*
		* return ISO numeric currency code
		*/
		private function getCurrencyNumericCode($currency)
		{
			$currencyLines = file(dirname(__FILE__).'/iso4217.txt');
			foreach ($currencyLines as $codes) {
				list($currencyCode, $currencyNum) = explode(',', $codes);
				if($currencyCode == $currency) {
					return trim($currencyNum);
				}
			}
			return '';
		}

		/**
		* send cmpi_lookup request
		* @param postData the posted creditcard details
		*/
		private function _doLookupRequest($postData, $manualPayment=false)
		{
			$ccnum = $postData['ccno'];
			$ccexpm = str_pad($postData['ccexpm'], 2, '0', STR_PAD_LEFT);
			$ccexpy = $postData['ccexpy']+2000;
			$cccvd = $postData['cccvd'];

			foreach ($postData as $key => $value) {
				if($key == 'ccno') {
					$value = $this->_CCEncrypt($postData['ccno']);
				}
				$_SESSION['CHECKOUT']['CardDetails'][$key] = $value;
			}


			$currency = GetCurrencyCodeByID(GetConfig('DefaultCurrencyID'));
			$currencyCode = $this->getCurrencyNumericCode($currency);
			$orders = $this->GetOrders();
			$order = current($orders);
			$orderIds = '#'.implode(', #', array_keys($orders));
			$orderdesc = sprintf(GetLang('YourOrderFrom'), $GLOBALS['StoreName']).' ('.$orderIds.')';

			$gatewayAmountCents = $this->GetGatewayAmount() * 100;
			$gatewayAmountCents = number_format($gatewayAmountCents, 0, '', '');

			require(dirname(__FILE__).'/lib/CentinelClient.php');
			$centinelClient = new CentinelClient;

			$centinelClient->Add("MsgType", "cmpi_lookup");
			$centinelClient->Add("Version", "1.7");
			$centinelClient->Add("ProcessorId", $this->GetValue('cardinalprocessorid'));
			$centinelClient->Add("MerchantId", $this->GetValue('cardinalmerchantid'));
			$centinelClient->Add("TransactionPwd", $this->GetValue('cardinaltransactionpwd'));
			$centinelClient->Add("OrderNumber", $orderIds);
			$centinelClient->Add("TransactionType", 'C');
			$centinelClient->Add("Amount", $gatewayAmountCents);

			$centinelClient->Add("CurrencyCode", $currencyCode);

			$centinelClient->Add("OrderDescription", $orderdesc);

			$centinelClient->Add("CardNumber", $ccnum);
			$centinelClient->Add("CardExpMonth", $ccexpm);
			$centinelClient->Add("CardExpYear", $ccexpy);

			$this->_testmode = $this->GetValue("testmode") == "YES";

			if ($this->_testmode) {
				$transactionURL = $this->_cardinalTestTransactionURL;
			}
			else {
				$transactionURL = $this->_cardinalLiveTransactionURL;
			}

			$centinelClient->sendHTTP($transactionURL, "30", "30");

			if($centinelClient->getValue("ErrorNo") == '0') {
				//if Cardholder not enrolled
				if($centinelClient->getValue("Enrolled") != 'Y') {
					$DPFields = array (
						'VERSION'		=> '59.0',
						'AUTHSTATUS3DS'	=> '',
						'MPIVENDOR3DS'	=> $centinelClient->getValue("Enrolled"),
						'CAVV'			=> '',
						'ECI3DS'		=> $centinelClient->getValue("EciFlag"),
						'XID'			=> '',
					);

					return $this->doDirectPayment($postData, $DPFields, $manualPayment);

				}

				//if the payment is a manual payment from control panel, 3D secure transactions are not supported, so finish the payment with error here.
				if($manualPayment) {
					$this->SetError(GetLang('PayPal3DManualPaymentError'));
					return false;
				}
				//we are still here, card holder is enrolled, and not a manual control panel payment, check error
				$result['ACSUrl'] = $centinelClient->getValue("ACSUrl");
				$result['Payload'] = $centinelClient->getValue("Payload");
				$result['TransactionId'] = $centinelClient->getValue("TransactionId");

			} else {
				$this->SetError($centinelClient->getValue("ErrorDesc"));
				return false;
			}

			$_SESSION['CHECKOUT']['CardinalEnrolled'] = $centinelClient->getValue("Enrolled");
			$_SESSION['CHECKOUT']['CardinalTransactionId'] = $result['TransactionId'];
			$_SESSION['CHECKOUT']['ACSUrl'] = $result['ACSUrl'];
			$_SESSION['CHECKOUT']['Payload'] = $result['Payload'];


			$this->_redirect3DTransacitons();
			exit;
		}

		/**
		* include the redirect form and redirects the customer to the card issuer site to verify the credit card
		*@param resultArray the result return from the lookup request
		*
		*/
		private function _redirect3DTransacitons()
		{

			$GLOBALS['PaymentFormContent'] = $this->ParseTemplate('paypalpaymentsprous.3dredirectform', true);
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('checkout_payment');
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
			exit;
		}

		/**
		* check 3d secure Authentication response and take relavent action
		*
		*/
		protected function _Handle3DCallBackResponse()
		{

			require(dirname(__FILE__).'/lib/CentinelClient.php');
			$centinelClient = new CentinelClient;

			$centinelClient->Add("MsgType", "cmpi_authenticate");
			$centinelClient->Add("Version", "1.7");
			$centinelClient->Add("ProcessorId", $this->GetValue('cardinalprocessorid'));
			$centinelClient->Add("MerchantId", $this->GetValue('cardinalmerchantid'));
			$centinelClient->Add("TransactionPwd", $this->GetValue('cardinaltransactionpwd'));
			$centinelClient->Add("TransactionType", 'C');
			$centinelClient->Add("TransactionId", $_SESSION['CHECKOUT']['CardinalTransactionId']);
			$centinelClient->Add("PAResPayload", $_REQUEST['PaRes']);


			$this->_testmode = $this->GetValue("testmode") == "YES";

			if ($this->_testmode) {
				$transactionURL = $this->_cardinalTestTransactionURL;
			}
			else {
				$transactionURL = $this->_cardinalLiveTransactionURL;
			}

			$centinelClient->sendHTTP($transactionURL, "30", "30");

			if($centinelClient->getValue("ErrorNo") == '0') {
				$PAResStatus = $centinelClient->getValue("PAResStatus");
				$SignatureVerification = $centinelClient->getValue("SignatureVerification");

				//if cardholder is authticated successfully
				if(($PAResStatus == 'Y' || $PAResStatus == 'A') && $SignatureVerification =='Y') {

					$DPFields = array (
						'VERSION'		=> '59.0',
						'AUTHSTATUS3DS'	=> $centinelClient->getValue("PAResStatus"),
						'MPIVENDOR3DS'	=> $_SESSION['CHECKOUT']['CardinalEnrolled'],
						'CAVV'			=> $centinelClient->getValue("Cavv"),
						'ECI3DS'		=> $centinelClient->getValue("EciFlag"),
						'XID'			=> $centinelClient->getValue("Xid"),
					);

					if($this->doDirectPayment($_SESSION['CHECKOUT']['CardDetails'], $DPFields)) {
						return ORDER_STATUS_AWAITING_FULFILLMENT;
					} else {
						return ORDER_STATUS_DECLINED;
					}
				} else {
					$this->RedirectToOrderConfirmation(GetLang('UnauthorizedMessage'), true);
					exit;
				}
			} else {
				//redirect back to order confirmation page.
				$this->RedirectToOrderConfirmation($centinelClient->getValue("ErrorDesc"), true);
				exit;
			}
		}

		/**
		* encrypt credit card number
		* @param CCNo credit card number
		*
		*/
		private function _CCEncrypt($CCNo)
		{
			$iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
			$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
			$crypt = mcrypt_encrypt(MCRYPT_BLOWFISH, GetConfig('EncryptionToken'), $CCNo, MCRYPT_MODE_ECB, $iv);
			$crypt = base64_encode($crypt);
			return $crypt;
		}

		/**
		* decrypt credit card number
		* @param CCEnc encrypted credit card number
		*
		*/
		private function _CCDecrypt($CCEnc)
		{
			$CCEnc = base64_decode($CCEnc);
			$iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
			$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
			$decrypt = mcrypt_decrypt(MCRYPT_BLOWFISH, GetConfig('EncryptionToken'), $CCEnc, MCRYPT_MODE_ECB, $iv);
			$decrypt = rtrim($decrypt, "\0");
			return $decrypt;
		}


		public function ProcessPaymentForm($data=array(), $manualPayment = false)
		{
			$postData = $this->_Validate($data);

			if ($postData === false) {
				return false;
			}

			// if the merchant is registed for 3D secure
			if(trim($this->GetValue('cardinalmerchantid')) != '') {
				return $this->_doLookupRequest($postData, $manualPayment);
			} else {
				return $this->doDirectPayment($postData, array(), $manualPayment);
			}

		}

		private function doDirectPayment($postData, $threeDData = array(), $manualPayment = false)
		{
			// Is setup in test or live mode?
			$this->_testmode = $this->GetValue("testmode") == "YES";

			$gateway_postdata = $this->_ConstructPostData($postData,$threeDData);

			if ($this->_testmode) {
				$transactionURL = $this->_testTransactionURL;
				$transactionURI = $this->_testTransactionURI;
			}
			else {
				$transactionURL = $this->_liveTransactionURL;
				$transactionURI = $this->_liveTransactionURI;
			}

			$result = $this->_ConnectToProvider($transactionURL, $transactionURI, $gateway_postdata);

			if (!$result) {
				$this->SetError(GetLang('CreditCardGatewayFail'));
				return false;
			}
			return $this->_HandleResponse($result, $manualPayment);
		}


		protected function GetAdditionalPaymentPageContents()
		{

			if(trim($this->GetValue('cardinalmerchantid')) == '') {
				return '';
			}
			$GLOBALS['ModuleImagePath'] = $GLOBALS['ShopPath']."/modules/checkout/paypalpaymentsprous/images";

			$MCSLearnMore= preg_replace("/\s/", " ", $this->ParseTemplate('paypalpaymentsprous.mcslearnmore', true));

			$VSCLearnMore= preg_replace("/\s/", " ", $this->ParseTemplate('paypalpaymentsprous.vbvlearnmore', true));

			$GLOBALS['MCSLearnMore'] = htmlentities($MCSLearnMore);
			$GLOBALS['VSCLearnMore'] = htmlentities($VSCLearnMore);

			return $this->ParseTemplate('paypalpaymentsprous.learnmore', true);
		}

		public function GetAuthFrom()
		{
			$GLOBALS['AuthURL'] = $_SESSION['CHECKOUT']['ACSUrl'];
			$GLOBALS['Payload'] = isc_html_escape($_SESSION['CHECKOUT']['Payload']);
			$GLOBALS['TermUrl'] = $GLOBALS['ShopPath']."/checkout.php?action=process_gateway_callback&provider=paypalpaymentsprous&callback=Process3DCallBack";

			return $this->ParseTemplate('paypalpaymentsprous.authform', true);
		}
	}
