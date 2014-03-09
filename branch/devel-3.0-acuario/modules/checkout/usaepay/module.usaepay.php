<?php
	class CHECKOUT_USAEPAY extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the USAePay checkout module

			$this->_languagePrefix = "USAePay";
			$this->_id = "checkout_usaepay";
			$this->_image = "usaepay_logo.gif";

			parent::__construct();

			$this->requiresSSL = true;
			$this->_currenciesSupported = array('USD');
			$this->_cardsSupported = array ('VISA','AMEX','MC','DINERS','DISCOVER');

			$this->_curlSupported = true;
			$this->_fsocksSupported = true;
		}

		/**
		* Custom variables for the checkout module. Custom variables are stored in the following format:
		* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
		* variable_type types are: text,number,password,radio,dropdown
		* variable_options is used when the variable type is radio or dropdown and is a name/value array.
		*/
		public function SetCustomVars()
		{
			$this->_variables['displayname'] = array("name" => GetLang('DisplayName'),
			   "type" => "textbox",
			   "help" => GetLang('DisplayNameHelp'),
			   "default" => $this->GetName(),
			   "required" => true
			);

			$this->_variables['merchantsourcekey'] = array("name" => GetLang($this->_languagePrefix.'MerchantSourceKey'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'MerchantSourceKeyHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['cardcode'] = array("name" => GetLang($this->_languagePrefix."CardCode"),
			   "type" => "dropdown",
			   "help" => GetLang($this->_languagePrefix.'CardCodeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(GetLang($this->_languagePrefix.'CardCodeNo') => "NO",
							  GetLang($this->_languagePrefix.'CardCodeYes') => "YES"
				),
				"multiselect" => false
			);

			$this->_variables['testmode'] = array("name" => GetLang($this->_languagePrefix.'TestMode'),
			   "type" => "dropdown",
			   "help" => GetLang($this->_languagePrefix.'TestModeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(
					GetLang($this->_languagePrefix.'TestModeNo') => "NO",
					GetLang($this->_languagePrefix.'TestModeYes') => "YES",
					GetLang($this->_languagePrefix.'TestModeYesSandbox') => "SANDBOX"
				),
				"multiselect" => false
			);
		}

		public function ProcessPaymentForm()
		{
			$postData = $this->_validate();

			if ($postData === false) {
				return false;
			}

			$tran = $this->_constructPostData($postData);

			$result = $this->_HandleResponse($tran->Process(), $tran);

			if ($result) {
				$this->SetPaymentStatus(PAYMENT_STATUS_PAID);
			}

			return $result;
		}


		protected function _ConstructPostData($postData)
		{
			$billingDetails = $this->GetBillingDetails();
			$orders = $this->GetOrders();

			require_once('lib/usaepay.php');
			$tran = new umTransaction;

			$transactionid = $this->GetCombinedOrderId();

			$tran->key = $this->GetValue("merchantsourcekey");

			$testmode = $this->GetValue("testmode");
			if ($testmode == 'YES') {
				$tran->testmode = true;
			}
			elseif ($testmode == 'SANDBOX') {
				$tran->usesandbox = true;
			}

			$tran->card 		= $postData['ccno'];
			$tran->exp			= $postData['ccexpm'].$postData['ccexpy'];
			$tran->amount		= $this->GetGatewayAmount();
			$tran->invoice		= $transactionid;
			$tran->cardholder	= $postData['name'];
			$tran->street		= $billingDetails['ordbillstreet1'];
			$tran->zip			= $billingDetails['ordbillzip'];
			$tran->description	= GetLang('USAePayOrderFrom', array('storeName' => GetConfig('StoreName')));
			$tran->cvv2			= $postData['cccvd'];

			return $tran;
		}

		protected function _HandleResponse($result, $tran)
		{
			if ($result) {
				// The order is valid, hook back into the checkout system's validation process
				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

				return true;
			}
			else {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Failure'),  $tran->result, $tran->error));
				$this->SetError(GetLang($this->_languagePrefix.'SomethingWentWrong'). $tran->error);
				return false;
			}
		}
	}