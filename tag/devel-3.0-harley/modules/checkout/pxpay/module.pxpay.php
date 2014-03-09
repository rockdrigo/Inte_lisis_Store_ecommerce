<?php

	class CHECKOUT_PXPAY extends ISC_CHECKOUT_PROVIDER
	{
		protected $requiresSSL = false;

		public $_id = "checkout_pxpay";

		protected $_currenciesSupported = array('NZD', 'AUD', 'CAD', 'CHF', 'EUR', 'FRF', 'GBP', 'HKD', 'JPY', 'SGD', 'USD', 'ZAR', 'WST', 'VUV', 'TOP', 'SBD', 'PNG', 'MYR', 'KWD', 'FJD');
		public $_languagePrefix = 'PXPay';

		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the Cybersouce checkout module
			parent::__construct();
			$this->_name = GetLang($this->_languagePrefix.'Name');
			$this->_image = "paymentexpress.png";
			$this->_description = GetLang($this->_languagePrefix.'Desc');
			$this->_help = sprintf(GetLang($this->_languagePrefix.'Help'), $GLOBALS['ShopPath']);	// Help Message
			$this->_height = 0;
		}

		public function IsSupported()
		{
			$currency = GetDefaultCurrency();

			// Check if the default currency is supported by the payment gateway
			if (!in_array($currency['currencycode'], $this->_currenciesSupported)) {
				$this->SetError(sprintf(GetLang($this->_languagePrefix.'CurrecyNotSupported'), implode(',',$this->_currenciesSupported)));
			}

			if (!function_exists("mcrypt_module_open")) {
				$this->SetError(sprintf(GetLang($this->_languagePrefix.'McryptRequired')));
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

			$this->_variables['userid'] = array("name" => GetLang($this->_languagePrefix."UserId"),
			 "type" => "textbox",
			 "help" => GetLang($this->_languagePrefix.'UserIdHelp'),
			 "default" => '',
			 "required" => true
			);

			$this->_variables['key'] = array("name" => GetLang($this->_languagePrefix."Key"),
			 "type" => "textbox",
			 "help" => GetLang($this->_languagePrefix.'KeyHelp'),
			 "default" => '',
			 "required" => true
			);

			$this->_variables['mackey'] = array("name" => GetLang($this->_languagePrefix."MacKey"),
			 "type" => "textbox",
			 "help" => GetLang($this->_languagePrefix.'MacKeyHelp'),
			 "default" => '',
			 "required" => true
			);
		}

		public function TransferToProvider()
		{
			require_once "lib/pxaccess.php";
			$pxaccess = new PxAccess('https://www.paymentexpress.com/pxpay/pxpay.aspx', $this->GetValue('userid'), $this->GetValue('key'), $this->GetValue('mackey'));
			$request = new PxPayRequest();

			$http_host		= getenv("HTTP_HOST");
			$request_uri	= getenv("SCRIPT_NAME");
			$server_url		= "http://$http_host";

			$hash = md5($this->GetGatewayAmount().$this->GetValue('userid').$this->GetValue('key').$this->GetValue('mackey'));
			$session = $_COOKIE['SHOP_ORDER_TOKEN'];
			$currency = GetDefaultCurrency();

			$script_url = $GLOBALS['ShopPath'] . '/finishorder.php';

			$request->setAmountInput($this->GetGatewayAmount());
			$request->setTxnData1($this->GetCombinedOrderId());
			$request->setTxnData2($hash);
			$request->setTxnData3('');
			$request->setTxnType("Purchase");
			$request->setInputCurrency($currency['currencycode']);
			$request->setMerchantReference($this->GetCombinedOrderId());
			$request->setEmailAddress('');
			$request->setUrlFail($script_url);
			$request->setUrlSuccess($script_url);

			$request_string = $pxaccess->makeRequest($request);

			header('Location: ' . $request_string);
		}

		public function VerifyOrderPayment()
		{
			require_once "lib/pxaccess.php";
			$pxaccess = new PxAccess('https://www.paymentexpress.com/pxpay/pxpay.aspx', $this->GetValue('userid'), $this->GetValue('key'), $this->GetValue('mackey'));

			$rsp = $pxaccess->getResponse($_REQUEST["result"]);

			if ($rsp->getStatusRequired() == "1" || $rsp->getSuccess() != 1) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'ErrorInvalid'));
				return false;
			}

			$AmountSettlement		= $rsp->getAmountSettlement();
			$TxnData1				= $rsp->getTxnData1();
			$currencySettlement		= $rsp->getCurrencySettlement();

			$currency = GetDefaultCurrency();

			if ($currencySettlement != $currency['currencycode']) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'CurrencyMismatch'), sprintf("Sent %s. Returned %s", $currency['currencycode'], $currencySettlement));
				return false;
			}

			if ($AmountSettlement != $this->GetGatewayAmount()) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'PaymentMismatch'), sprintf("Sent %s. Returned %s", $this->GetGatewayAmount(), $AmountSettlement));
				return false;
			}

			if ($TxnData1 != $this->GetCombinedOrderId()) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'InvalidOrder'), sprintf("Sent %s. Returned %s", $this->GetCombinedOrderId(), $TxnData1));
				return false;
			}

			$updatedOrder = array(
				'ordpayproviderid' => $rsp->getMerchantTxnId(),
				'ordpaymentstatus' => 'captured'
			);

			$this->UpdateOrders($updatedOrder);

			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));
			$this->SetPaymentStatus(PAYMENT_STATUS_PAID);
			return true;
		}
	}