<?php

	class CHECKOUT_SECURETRADING extends ISC_CHECKOUT_PROVIDER
	{
		protected $requiresSSL = false;

		protected $_currenciesSupported = array('USD','GBP');
		public $_languagePrefix = 'SecureTrading';

		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the Secure Trading checkout module
			parent::__construct();
			$this->_name = GetLang($this->_languagePrefix.'Name');
			$this->_image = "secure_trading.gif";
			$this->_description = GetLang($this->_languagePrefix.'Desc');
			$this->_help = sprintf(GetLang($this->_languagePrefix.'Help'), $GLOBALS['ShopPath'], $GLOBALS['ShopPath']);	// Help Message
			$this->_height = 0;
		}

		public function IsSupported()
		{
			$currency = GetDefaultCurrency();

			// Check if the default currency is supported by the payment gateway
			if (!in_array($currency['currencycode'], $this->_currenciesSupported)) {
				$this->SetError(sprintf(GetLang($this->_languagePrefix.'CurrecyNotSupported'), implode(',',$this->_currenciesSupported)));
			}

			if($this->HasErrors()) {
				return false;
			}
			else {
				return true;
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
			$this->_variables['displayname'] = array("name" => GetLang($this->_languagePrefix."DisplayName"),
			 "type" => "textbox",
			 "help" => GetLang('DisplayNameHelp'),
			 "default" => $this->GetName(),
			 "required" => true
			);

			$this->_variables['MerchantId'] = array("name" => GetLang($this->_languagePrefix."MerchantId"),
			 "type" => "textbox",
			 "help" => GetLang($this->_languagePrefix.'MerchantIdHelp'),
			 "default" => '',
			 "required" => true
			);
			$this->_variables['MerchantEmail'] = array("name" => GetLang($this->_languagePrefix."MerchantEmail"),
			 "type" => "textbox",
			 "help" => GetLang($this->_languagePrefix.'MerchantEmailHelp'),
			 "default" => '',
			 "required" => true
			);
			$this->_variables['CallbackId'] = array("name" => GetLang($this->_languagePrefix."CallbackId"),
			 "type" => "textbox",
			 "help" => GetLang($this->_languagePrefix.'CallbackIdHelp'),
			 "default" => '',
			 "required" => true
			);
			$this->_variables['SecretWord'] = array("name" => GetLang($this->_languagePrefix."SecretWord"),
			 "type" => "textbox",
			 "help" => GetLang($this->_languagePrefix.'SecretWordHelp'),
			 "default" => '',
			 "required" => true
			);
		}

		public function TransferToProvider()
		{
			$url = 'https://securetrading.net/authorize/form.cgi';
			$currency = GetDefaultCurrency();
			$currency = $currency['currencycode'];

			$billingDetails = $this->GetBillingDetails();

			$merchantid = $this->GetValue('MerchantId');
			$merchantemail = $this->GetValue('MerchantEmail');
			$callbackid = $this->GetValue('CallbackId');

			$amount = number_format($this->GetGatewayAmount()*100, 0, '','');

			$stform['merchant'] = $merchantid;
			$stform['orderref'] = $this->GetCombinedOrderId();
			$stform['orderinfo'] = sprintf(GetLang($this->_languagePrefix.'YourOrderFromX'), $GLOBALS['StoreName']);
			$stform['amount'] = $amount;
			$stform['currency'] = $currency;
			$stform['merchantemail'] = $merchantemail;
			$stform['callbackurl'] = $callbackid;
			$stform['failureurl'] = $callbackid;
			$stform['formref'] = $callbackid;
			$stform['customeremail'] = $billingDetails['ordbillemail'];
			$stform['settlementday'] = 1;

			$stform['ordertoken'] = $_COOKIE['SHOP_ORDER_TOKEN'];
			$stform['sessiontoken'] = $_COOKIE['SHOP_SESSION_TOKEN'];
			$stform['provider'] = $this->GetId();
			$stform['hash'] = md5($this->GetValue('SecretWord').$this->GetCombinedOrderId().$merchantid.$amount.$currency);

			$stform['name'] = $billingDetails['ordbillfirstname'] . ' '. $billingDetails['ordbilllastname'];
			$stform['address'] = $billingDetails['ordbillstreet1'] . ' '. $billingDetails['ordbillstreet2'];
			$stform['town'] = $billingDetails['ordbillsuburb'];
			$stform['county'] = $billingDetails['ordbillstate'];
			$stform['postcode'] = $billingDetails['ordbillzip'];

			$stform['country'] = $billingDetails['ordbillcountry'];
			$stform['telephone'] = $billingDetails['ordbillphone'];
			$stform['email'] = $billingDetails['ordbillemail'];

			header('Location: ' . $url . '?'. http_build_query($stform));
		}

		/**
		*	Return the unique order token which was saved as a cookie pre-payment
		*/
		public function GetOrderToken()
		{
			return @$_POST['ordertoken'];
		}

		public function VerifyOrderPayment()
		{
			if (!isset($_POST['ordertoken']) || !isset($_POST['sessiontoken'])) {
				return false;
			}

			/**
			* Reinitialise the order session
			*/
			session_write_close();
			$session = new ISC_SESSION($_POST['sessiontoken']);

			// check for posted variables, the payment was declined if we're missing these
			if (!isset($_POST['hash']) || !isset($_POST['amount']) || !isset($_POST['currency']) || !isset($_POST['orderref'])) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'ErrorInvalid'));
				$this->SetPaymentStatus(PAYMENT_STATUS_DECLINED);
				return true;
			}

			$hash = $_POST['hash'];
			$amount = $_POST['amount'];
			$orderref = $_POST['orderref'];
			$currency = $_POST['currency'];

			// does the order hash match?
			if ($hash != md5($this->GetValue('SecretWord') . $orderref . $this->GetValue('MerchantId') . $amount . $currency)) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'ErrorInvalid'));
				return false;
			}

			$updatedOrder = array(
				'ordpayproviderid' => $_REQUEST['streference']
			);

			$this->UpdateOrders($updatedOrder);

			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));
			$this->SetPaymentStatus(PAYMENT_STATUS_PAID);

			return true;
		}
	}
