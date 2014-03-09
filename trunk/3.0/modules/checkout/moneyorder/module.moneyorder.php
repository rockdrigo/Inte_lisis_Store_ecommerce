<?php

	class CHECKOUT_MONEYORDER extends ISC_CHECKOUT_PROVIDER
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
			// Setup the required variables for the money order checkout module
			parent::__construct();

			$this->SetName(GetLang('MoneyOrderName'));
			$this->_description = GetLang('MoneyOrderDesc');
			$this->_help = GetLang('MoneyOrderHelp');
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

			$this->_variables['displayname'] = array("name" => "Display Name",
			   "type" => "textbox",
			   "help" => GetLang('DisplayNameHelp'),
			   "default" => $this->GetName(),
			   "required" => true
			);

			$this->_variables['availablecountries'] = array("name" => "Available Countries",
			   "type" => "dropdown",
			   "help" => GetLang('MoneyOrderAvailableCountriesHelp'),
			   "default" => "all",
			   "required" => true,
			   "options" => GetCountryListAsNameValuePairs(),
				"multiselect" => true
			);

			$this->_variables['helptext'] = array("name" => "Payment Information",
			   "type" => "textarea",
			   "help" => GetLang('MoneyOrderPaymentInformationHelp'),
			   "default" => "Escriba las instrucciones para el pago en este campo.",
			   "required" => true,
			   "rows" => 7
			);
		}

		/**
		*	Return the delivery details needed to pay for the order
		*/
		public function GetOfflinePaymentMessage()
		{
			return nl2br($this->GetValue("helptext"));
		}
	}