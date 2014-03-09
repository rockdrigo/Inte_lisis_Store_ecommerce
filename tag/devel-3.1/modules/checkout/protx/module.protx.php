<?php

	class CHECKOUT_PROTX extends ISC_CHECKOUT_PROVIDER
	{
		/*
			The vendor name for the Protx accoun*/
		private $_vendorname = "";

		/*
			The encryption password used when XOR'ing the crpyt data
		*/
		private $_encryptionpassword = "";

		/*
			The page to post the form to
		*/
		private $_protxurl = "";

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
			// Setup the required variables for the Protx checkout module
			parent::__construct();
			$this->_name = GetLang('ProtxName');
			$this->_image = "protx_logo.gif";
			$this->_description = GetLang('ProtxDesc');
			$this->_help = sprintf(GetLang('ProtxHelp'), $GLOBALS['ShopPathSSL']);
		}

		/**
		* Custom variables for the checkout module. Custom variables are stored in the following format:
		* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
		* variable_type types are: text,number,password,radio,dropdown
		* variable_options is used when the variable type is radio or dropdown and is a name/value array.
		*/
		public function SetCustomVars()
		{

			$this->_variables['displayname'] = array("name" => "Display Name",
			   "type" => "textbox",
			   "help" => GetLang('DisplayNameHelp'),
			   "default" => $this->GetName(),
			   "required" => true
			);

			$this->_variables['vendorname'] = array("name" => "VSP Vendor Name",
			   "type" => "textbox",
			   "help" => GetLang('ProtxVendorNameHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['encryptionpassword'] = array("name" => "Encryption Password",
			   "type" => "password",
			   "help" => GetLang('ProtxEncryptionPasswordHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['testmode'] = array("name" => "Test Mode",
			   "type" => "dropdown",
			   "help" => GetLang('ProtxTestModeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(GetLang('ProtxTestModeNo') => "NO",
							  GetLang('ProtxTestModeYes') => "YES",
							  GetLang('ProtxTestModeSim') => "SIMULATOR"
				),
				"multiselect" => false
			);
		}

		/**
		*	Redirect the customer to Protx's site to enter their payment details
		*/
		public function TransferToProvider()
		{
			$currency = GetDefaultCurrency();

			$crypt_after = "";
			$shipping_address = "";
			$shipping_zip = "";
			$total = number_format($this->GetGatewayAmount(),2,'.','');
			$this->_vendorname = $this->GetValue("vendorname");
			$this->_encryptionpassword = $this->GetValue("encryptionpassword");
			$testmode_on = $this->GetValue("testmode");

			if($testmode_on == "YES") {
				$this->_protxurl = "https://test.sagepay.com/gateway/service/vspform-register.vsp";
			}
			else if ($testmode_on == "SIMULATOR") {
				$this->_protxurl = "https://test.sagepay.com/Simulator/VSPFormGateway.asp";
			}
			else {
				$this->_protxurl = "https://live.sagepay.com/gateway/service/vspform-register.vsp";
			}


			$description = GetLang('ProtxYourOrderFromX') . str_replace("&#39;", "'", $GLOBALS['StoreName']);

			$billingDetails = $this->GetBillingDetails();

			$billState = '';
			if($billingDetails['ordbillcountrycode'] == 'US') {
				$billState = GetStateISO2ById($billingDetails['ordbillstateid']);
			}

			if($this->IsDigitalOrder()) {
				$ShippingAddress = array(
					'firstname' => $billingDetails['ordbillfirstname'],
					'lastname'	=> $billingDetails['ordbilllastname'],
					'address1'	=> $billingDetails['ordbillstreet1'],
					'address2'	=> $billingDetails['ordbillstreet2'],
					'city'		=> $billingDetails['ordbillsuburb'],
					'state'		=> $billState,
					'country'	=> $billingDetails['ordbillcountrycode'],
					'postcode'	=> $billingDetails['ordbillzip']
				);
			} else {
				$shippingAddress = $this->getShippingAddress();
				$shipState = '';
				if($shippingAddress['country_iso2'] == 'US') {
					$shipState = GetStateISO2ById($shippingAddress['state_id']);
				}

				$ShippingAddress = array(
					'firstname' => $shippingAddress['first_name'],
					'lastname'	=> $shippingAddress['last_name'],
					'address1'	=> $shippingAddress['address_1'],
					'address2'	=> $shippingAddress['address_2'],
					'city'		=> $shippingAddress['city'],
					'state'		=> $shipState,
					'country'	=> $shippingAddress['country_iso2'],
					'postcode'	=> $shippingAddress['zip']
				);
			}

			$data = array(
						'VendorTxCode'		=> $_COOKIE['SHOP_ORDER_TOKEN'] . "_" . rand(1,100000),
						'Amount'			=> $total,
						'Currency'			=> $currency['currencycode'],
						'Description'		=> $description,
						'SuccessURL'		=> $GLOBALS['ShopPath'].'/finishorder.php',
						'FailureURL'		=> $GLOBALS['ShopPath'].'/finishorder.php?protx_failure=true',
						'CustomerName'		=> str_replace("&", "", $billingDetails['ordbillfirstname'].' '.$billingDetails['ordbilllastname']),
						'CustomerEMail'		=> str_replace("&", "", $billingDetails['ordbillemail']),
						'VendorEMail'		=> str_replace("&", "", GetConfig('OrderEmail')),
						'ContactNumber'		=> str_replace("&", "", $billingDetails['ordbillphone']),

						'BillingSurname'	=> str_replace("&", "", $billingDetails['ordbilllastname']),
						'BillingFirstnames'	=> str_replace("&", "", $billingDetails['ordbillfirstname']),
						'BillingAddress1'	=> str_replace("&", "", $billingDetails['ordbillstreet1']),
						'BillingAddress2'	=> str_replace("&", "", $billingDetails['ordbillstreet2']),
						'BillingCity'		=> str_replace("&", "", $billingDetails['ordbillsuburb']),
						'BillingState'		=> str_replace("&", "", $billState),
						'BillingPostCode'	=> str_replace("&", "", $billingDetails['ordbillzip']),
						'BillingCountry'	=> str_replace("&", "", $billingDetails['ordbillcountrycode']),

						'DeliverySurname'	=> str_replace("&", "", $ShippingAddress['lastname']),
						'DeliveryFirstnames'=> str_replace("&", "", $ShippingAddress['firstname']),
						'DeliveryAddress1'	=> str_replace("&", "", $ShippingAddress['address1']),
						'DeliveryAddress2'	=> str_replace("&", "", $ShippingAddress['address2']),
						'DeliveryCity'		=> str_replace("&", "", $ShippingAddress['city']),
						'DeliveryState'		=> str_replace("&", "", $ShippingAddress['state']),
						'DeliveryPostCode'	=> str_replace("&", "", $ShippingAddress['postcode']),
						'DeliveryCountry'	=> str_replace("&", "", $ShippingAddress['country'])
			);

			$crypt_before = '';
			// Build the XOR'd crypt string as per the Protx documentation
			foreach ($data as $key => $value) {
				$crypt_before .= $key."=".$value."&";
			}
			$crypt_before = rtrim($crypt_before, '&');

			// Base 64 encode to make it binary-safe
			$crypt_after = $this->simplexor($crypt_before, $this->_encryptionpassword);
			$crypt_after = base64_encode($crypt_after);
			?>
				<html>
					<head>
						<title><?php echo GetLang('RedirectingToProtx'); ?></title>
					</head>
					<body onload="document.forms[0].submit()">
						<a href="javascript:void(0)" onclick="document.forms[0].submit()" style="color:gray; font-size:12px"><?php echo GetLang('ClickIfNotRedirected'); ?></a>
						<form action="<?php echo $this->_protxurl; ?>" method="post">
							<input type="hidden" name="VPSProtocol" value="2.23">
							<input type="hidden" name="TxType" value="PAYMENT">
							<input type="hidden" name="Vendor" value="<?php echo htmlentities($this->_vendorname); ?>">
							<input type="hidden" name="Crypt" value="<?php echo $crypt_after; ?>">
						</form>
					</body>
				</html>
			<?php
		}

		/**
		*	Verify the order by checking the Protx variables
		*/
		public function VerifyOrder(&$PendingOrder)
		{
			$crypt_after = "";
			$protx_data = array();
			$this->_encryptionpassword = $this->GetValue("encryptionpassword");

			// Protx send back a base64 encoded, XOR'ed string which contains all order data
			if(isset($_GET['crypt'])) {
				$crypt_after = $this->simplexor($this->Base64Decode($_GET['crypt']), $this->_encryptionpassword);
				parse_str($crypt_after, $protx_data);

				// If $_GET['protx_failure'] then the order failed
				if(!isset($_GET['protx_failure'])) {
					// Make sure we have the details we need from Protx
					$protx_fields = array("Status",
										  "VendorTxCode",
										  "Amount"
					);

					foreach($protx_fields as $field) {
						if(!isset($protx_data[$field])) {
							$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('ProtxErrorInvalid'), GetLang('ProtxErrorInvalidMsg'));
							return false;
						}
					}

					// if the amount is over 1,000 protx adds an unexpected comma which throws off the checks below
					$protx_data['Amount'] = str_replace(',', '', $protx_data['Amount']);

					// If the status is OK and the order amounts match then the order was successful
					if($protx_data['Status'] == "OK" && $protx_data['Amount'] == $PendingOrder['total_inc_tax']) {
						$successMessage = sprintf(GetLang('ProtxSuccess'), $PendingOrder['orderid'], $protx_data['VendorTxCode'], $protx_data['Status']);
						$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $successMessage);
						$PendingOrder['paymentstatus'] = PAYMENT_STATUS_PAID;
						return true;
					}
					else {
						$errorMsg = sprintf(GetLang('ProtxErrorMisMatchMsg'), $protx_data['Status'], $protx_data['Amount'], $PendingOrder['total_inc_tax']);
						$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('ProtxErrorMisMatch'), $errorMsg);
						return false;
					}
				}
				else {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang('ProtxErrorFailed'), $_GET['protx_failure']));
					return false;
				}
			}
			else {
				// No crypt data, assume it's a bad order
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('ProtxErrorFailedCrypt'));
				return false;
			}
		}

		/**
		*	Create a function that works with XOR as per Protx's spec
		*/
		private function simplexor($data, $key)
		{
			$output = "";

			for($i = 0; $i < strlen($data); ) {
				for($j = 0; $j < strlen($key); $j++, $i++) {
					if($i < strlen($data)) {
						$output .= $data[$i] ^ $key[$j];
					} else {
						break;
					}
				}
			}

			return $output;
		}

		private function Base64Decode($string)
		{
			// Initialise output variable
			$output = "";

			// Fix plus to space conversion issue
			$string = str_replace(" ","+",$string);

			// Do encoding
			$output = base64_decode($string);

			// Return the result
			return $output;
		}
	}