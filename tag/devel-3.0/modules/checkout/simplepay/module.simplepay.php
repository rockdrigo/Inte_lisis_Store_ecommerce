<?php

	require_once('lib/pay.php');
	require_once('lib/simplepayclient.php');

	class CHECKOUT_SIMPLEPAY extends ISC_CHECKOUT_PROVIDER
	{
		public $_id = "checkout_simplepay";

		/*
			Does this payment provider require SSL?
		*/
		protected $requiresSSL = false;
		protected $_currenciesSupported = array('USD');

		private $_languagePrefix = 'SimplePay';

		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the SimplePay checkout module
			parent::__construct();

			$this->_name = GetLang('SimplePayName');
			$this->_image = "apmark_180x110.jpg";
			$this->_description = GetLang('SimplePayDesc');
			$this->_help = sprintf(GetLang($this->_languagePrefix.'Help'), $GLOBALS['ShopPath']);	// Help Message
			$this->_height = 0;

		}

		public function IsSupported()
		{
			$currencycode = GetDefaultCurrency();
			$currencycode = $currencycode['currencycode'];

			if (!in_array($currencycode, $this->_currenciesSupported)) {
				$this->SetError(GetLang($this->_languagePrefix.'CurrecyNotSupported'));
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
			$this->_variables['displayname'] = array("name" => GetLang($this->_languagePrefix.'DisplayName'),
			   "type" => "textbox",
			   "help" => GetLang('DisplayNameHelp'),
			   "default" => $this->GetName(),
			   "required" => true
			);

			$this->_variables['accessid'] = array("name" => GetLang($this->_languagePrefix.'AccessId'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'AccessIdHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['secretkey'] = array("name" => GetLang($this->_languagePrefix.'SecretKey'),
			   "type" => "password",
			   "help" => GetLang($this->_languagePrefix.'SecretKeyHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['testmode'] = array("name" => GetLang($this->_languagePrefix.'TestMode'),
			   "type" => "dropdown",
			   "help" => GetLang($this->_languagePrefix.'TestModeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(GetLang($this->_languagePrefix.'TestModeNo') => "NO",
							  GetLang($this->_languagePrefix.'TestModeYes') => "YES"
				),
				"multiselect" => false
			);
		}

		/**
		*	Redirect the customer to SimplePay's site to enter their payment details
		*/
		public function TransferToProvider()
		{
			$client = new SIMPLEPAY_CLIENT(
				$this->GetValue('accessid'),
				$this->GetValue('secretkey'),
				$this->GetValue('testmode') == "YES"
			);

			$simplePay = new SIMPLEPAY_PAY();
			$simplePay->setDescription('Order from Shopping Cart');
			$simplePay->setReferenceId($this->GetCombinedOrderId());
			$simplePay->setAmount('USD '.round($this->GetGatewayAmount(),2));
			$client->pay($simplePay);
			die();
		}


		/**
		*	Return the unique order token which was saved as a cookie pre-payment
		*/
		public function GetOrderToken()
		{
			return Interspire_Request::request('sessionId');
		}

		public function VerifyOrderPayment()
		{
			$status 	= Interspire_Request::request('status');
			$orderid 	= Interspire_Request::request('referenceId');
			$hash 		= Interspire_Request::request('hash');
			$sessionId 	= Interspire_Request::request('sessionId');
			$amazonAmount	= Interspire_Request::request('transactionAmount');
			$operation 	= Interspire_Request::request('operation');
			$paymentMethod 	= Interspire_Request::request('paymentMethod');
			$buyerEmail = Interspire_Request::request('buyerEmail');
			$transactionId = Interspire_Request::request('transactionId');

			$amount = false;
			if ($amazonAmount) {
				$amount = explode(' ', $amazonAmount);
				if (count($amount) >= 1) {
					$amount = $amount[1];
				} else {
					$amount = false;
				}
			}

			if (!$amount) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'InvalidAmount'), $amazonAmount);
				return false;
			}

			if ($orderid != $this->GetCombinedOrderId() || $operation != 'pay' || $sessionId != $_COOKIE['SHOP_ORDER_TOKEN'] || $amount != $this->GetGatewayAmount()) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'ErrorMismatch'));
				return false;
			}

			// check signature to ensure this response is from amazon simple pay
			if (!$this->_verifySignature()) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'ErrorVerifySignature'));
				return false;
			}

			if (md5($this->GetValue("accessid").$this->GetValue("secretkey").$orderid.$sessionId.$amazonAmount) != $hash) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'ErrorMismatch'));
				return false;
			}

			if (!($status == 'PS' || $status == 'PI')) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'ErrorMismatch'), $status), GetLang($this->_languagePrefix.'ResponseCodes'));
				return false;
			}

			$orders = $this->GetOrders();
			$order = current($orders);

			$amazonInfo = array(
				'Amazon Email' => $buyerEmail,
				'Payment Method' => $paymentMethod,
			);

			// Is there any existing extra info for the pending order?
			$extraInfo = serialize($amazonInfo);
			if ($order['extrainfo'] != "") {
				$extraArray = @unserialize($order['extrainfo']);
				if (is_array($extraArray)) {
					$extraInfo = serialize(array_merge($extraArray, $amazonInfo));
				}
			}

			$updatedOrder = array(
				'ordpayproviderid' => $transactionId,
				'ordpaymentstatus' => 'captured',
				'extrainfo' => $extraInfo,
			);

			$this->UpdateOrders($updatedOrder);

			$this->SetPaymentStatus(PAYMENT_STATUS_PAID);
			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Success'), $this->GetCombinedOrderId()));
			return true;
		}

		private function _verifySignature()
		{
			$client = new SIMPLEPAY_CLIENT(
				$this->GetValue('accessid'),
				$this->GetValue('secretkey'),
				$this->GetValue('testmode') == "YES"
			);

			$urlEndPoint = preg_replace('/\?(.)*$/', '', GetCurrentUrl());

			return $client->validateRequest($_GET, $urlEndPoint, 'GET');
		}
	}
