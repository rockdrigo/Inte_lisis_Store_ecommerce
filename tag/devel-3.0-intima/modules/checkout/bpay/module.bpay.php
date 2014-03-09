<?php

	class CHECKOUT_BPAY extends ISC_CHECKOUT_PROVIDER
	{

		/*
			Does this payment provider require SSL?
		*/
		public $requiresSSL = false;

		/*
			The help text that will be displayed post-checkout
		*/
		public $_paymenthelp = "";

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
			// Setup the required variables for the BPAY checkout module
			parent::__construct();
			$this->_name = GetLang('BPAYName');
			$this->_description = GetLang('BPAYDesc');
			$this->_image = "BPAY_logo.gif";
			$this->_help = GetLang('BPAYHelp');
			$this->_height = 0;

			// This is an offline payment method
			$this->paymentType = PAYMENT_PROVIDER_OFFLINE;
		}

		/**
		 * Check if this checkout method is accessible by the current customer.
		 * Useful for checking things such as available countries etc.
		 *
		 * @return boolean True if accessible, false if not.
		 */
		public function IsAccessible()
		{
			$availableCountries = $this->GetValue('availablecountries');

			if(!is_array($availableCountries)) {
				$availableCountries = array($availableCountries);
			}

			// Always available if we're in the control panel
			if(defined('ISC_ADMIN_CP')) {
				return true;
			}

			// Available in all countries
			if(in_array('all', $availableCountries)) {
				return true;
			}

			// Otherwise we need to check against the billing address
			$billingCountry = $this->GetBillingCountry();
			if($billingCountry === false) {
				return true;
			}
			// We're in the available countries - return true
			else if(in_array($billingCountry, $availableCountries)) {
				return true;
			}
			// Not available here, return false.
			else {
				return false;
			}
		}

		/**
		* Custom variables for the checkout module. Custom variables are stored in the following format:
		* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
		* variable_type types are: text,number,password,radio,dropdown
		* variable_options is used when the variable type is radio or dropdown and is a name/value array.
		*/
		public function SetCustomVars()
		{

			$this->_variables['displayname'] = array("name" => GetLang('BPAYDisplayName'),
			   "type" => "textbox",
			   "help" => GetLang('DisplayNameHelp'),
			   "default" => $this->GetName(),
			   "required" => true
			);

			$this->_variables['billercode'] = array("name" => GetLang('BPAYBillerCode'),
			   "type" => "textbox",
			   "help" => GetLang('BPAYBillerCodeHelp'),
			   "default" => "",
			   "required" => true,
			);

			$this->_variables['availablecountries'] = array("name" => GetLang('BPAYAvailableCountries'),
			   "type" => "dropdown",
			   "help" => GetLang('BPAYAvailableCountriesHelp'),
			   "default" => "all",
			   "required" => true,
			   "options" => GetCountryListAsNameValuePairs(),
				"multiselect" => true
			);

			$this->_variables['padlength'] = array("name" => GetLang('BPAYPadLength'),
				"type" => "dropdown",
				"help" => GetLang('BPAYPadLengthHelp'),
				"default" => 10,
				"required" => true,
				"size" => 2,
				"options" => array(5 => 5,6 => 6,7 => 7,8 => 8,9 => 9,10 => 10)
			);

			$this->_variables['referenceprefix'] = array("name" => GetLang('BPAYReferencePrefix'),
				"type" => "textbox",
				"help" => GetLang('BPAYReferencePrefixHelp'),
				"default" => "",
				"required" => false,
				"size" => 2
			);
		}

		public function SaveModuleSettings($settings=array(), $deleteFirst=true)
		{
			// validate the prefix
			$prefix = trim($settings['referenceprefix']);
			if ($prefix) {
				$prefix = isc_strtoupper(isc_substr($prefix, 0, 1));
				if (!preg_match("/[a-zA-Z0-9]/", $prefix)) {
					$this->SetError(GetLang('BPAYInvalidPrefix'));
					$prefix = "";
				}
			}
			$settings['referenceprefix'] = $prefix;

			$return = parent::SaveModuleSettings($settings, $deleteFirst);

			if ($this->HasErrors()) {
				return false;
			}
			else {
				return $ret;
			}
		}

		/**
		*	Return the BPAY details needed to pay for the order
		*/
		public function GetOfflinePaymentMessage()
		{
			// We can only pass one of the orders for the reference code, so pass the first one in the stack
			$GLOBALS['RefNumber'] = $this->GenerateReferenceNumber();
			$GLOBALS['BillerCode'] = $this->GetValue("billercode");

			return $this->ParseTemplate('bpay.billreference', true);
		}


		/**
		* Generate BPay reference number based on the MOD10V1 rule.
		*
		*/
		public function GenerateReferenceNumber($orderId='', $padLength='', $refPrefix='')
		{
			if ($orderId == '') {
				$orders = $this->GetOrders();
				list(,$order) = each($orders);
				$orderId = $order['orderid'];
			}

			if ($padLength == '') {
				$padLength = $this->GetValue('padlength');
			}
			// Minus 1 for the check digit.
			$padLength--;

			if ($refPrefix == '') {
				$refPrefix = $this->GetValue('referenceprefix');
			}
			if (is_numeric($refPrefix) == false) {
				$refPrefix = '';
			}
			if ($refPrefix) {
				// Minus 1 for the reference prefix.
				$padLength--;
			}

			// Prefix and pad should be included for the check digit calculation.
			$orderId = $refPrefix . str_pad($orderId, $padLength, 0, STR_PAD_LEFT);

			$total = 0;
			$orderIdStr = (string) $orderId;
			for ($i=0; $i<strlen($orderIdStr); $i++) {
				if ($i%2 == 0) {
					$weight = 2;
				}
				else {
					$weight = 1;
				}

				$curIndex = strlen($orderIdStr) - ($i+1);
				$currentDigit = $orderIdStr[$curIndex];
				$subTotal = intval($currentDigit) * $weight;
				if ($subTotal >= 10) {
					$subTotalStr = (string) $subTotal;
					$subTotal = intval($subTotalStr[0]) + intval($subTotalStr[1]);
				}
				$total += $subTotal;
			}

			$checkDigit = (10 - ($total % 10)) % 10;
			$referenceNumber = $orderId . $checkDigit;

			return $referenceNumber;
		}
	}