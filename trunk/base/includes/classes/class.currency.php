<?php
	require_once(dirname(__FILE__).'/class.module.php');
	/**
	* The Interspire Shopping Cart notification base class, used by all notification modules
	*/
	class ISC_CURRENCY extends ISC_MODULE
	{
		/*
			Should we show a "Test Notification Method" link? Defaults to yes
		*/
		public $_showtestlink = true;

		/*
			The url of the currency module
		*/
		public $_url = "";

		/**
		 * @var string The type of module this is
		 */
		public $type = 'currency';

		private $_textCurrencyCode = "AUD";

		/*
			Return the URL of the currency module
		*/
		public function GetURL()
		{
			return $this->_url;
		}

		public function GetTargetURL()
		{
			return $this->_targetURL;
		}


		protected function CheckEnabled()
		{
			$currency_methods = explode(",", GetConfig('CurrencyMethods'));
			if(in_array($this->GetId(), $currency_methods)) {
				return true;
			}
			else {
				return false;
			}
		}

		/**
		 * Validate currency code
		 *
		 * Method will validate the currency code
		 *
		 * @access protected
		 * @param string The currency code
		 * @return bool true if the currency code is valid, false otherwise
		 */
		protected function IsValidCode($code)
		{
			if (preg_match('/^[a-z]{3}$/i', $code)) {
				return true;
			}

			return false;
		}

		public function IsRealCode($code)
		{
			if (!$this->IsValidCode($code)) {
				return false;
			}

			return (bool)$this->GetExchangeRate($this->_textCurrencyCode, $code);
		}

		protected function GetBaseCode()
		{
			$currency = GetDefaultCurrency();
			return $currency['currencycode'];
		}

		protected function GetBaseRate()
		{
			return GetConfig("DefaultCurrencyRate");
		}

		public function GetExchangeRateUsingBase($toCode)
		{
			return $this->GetExchangeRate($this->GetBaseCode(), $toCode);
		}

		public function GetExchangeRate($fromCode, $toCode)
		{
			if (!$this->IsValidCode($fromCode)) {
				$this->SetError(sprintf(GetLang("CurrencyModuleInvalidFromCode"), $fromCode));
				return false;
			}
			else if (!$this->IsValidCode($toCode)) {
				$this->SetError(sprintf(GetLang("CurrencyModuleInvalidToCode"), $toCode));
				return false;
			}

			return $this->FetchExchangeRate($fromCode, $toCode);
		}
	}