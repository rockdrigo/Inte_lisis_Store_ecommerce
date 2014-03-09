<?php

	class CHECKOUT_MONEYBOOKERS extends ISC_CHECKOUT_PROVIDER
	{
		protected $requiresSSL = false;

		public $_id = "checkout_moneybookers";
		public $_languagePrefix = "MoneyBookers";

		protected $_currenciesSupported = array(
			'EUR',
			'GBP',
			'BGN',
			'USD',
			'AUD',
			'CAD',
			'CZK',
			'DKK',
			'EEK',
			'HKD',
			'HUF',
			'ILS',
			'JPY',
			'LTL',
			'LVL',
			'MYR',
			'TWD',
			'YTL',
			'NZD',
			'NOK',
			'PLN',
			'SGD',
			'CZK',
			'ZAR',
			'KRW',
			'SEK',
			'CHF',
			'THB'
		);

		protected $_liveTransactionURL 	= 'https://www.moneybookers.com';
		protected $_liveTransactionURI 	= '/app/payment.pl';


		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the Realex checkout module
			parent::__construct();
			$this->_name = GetLang($this->_languagePrefix.'Name');
			$this->_image = "moneybookers.gif";
			$this->_description = GetLang($this->_languagePrefix.'Desc');
			$this->_help = sprintf(GetLang($this->_languagePrefix.'Help'), $GLOBALS['ShopPath']);	// Help Message
			$this->_height = 0;
		}

		public function IsSupported()
		{
			$currency = GetDefaultCurrency();

			// Check if the default currency is supported by the payment gateway
			if (!in_array($currency['currencycode'], $this->_currenciesSupported)) {
				$this->SetError(sprintf(GetLang($this->_languagePrefix.'CurrecyNotSupported'), $this->_currenciesSupported));
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

			$this->_variables['mbemail'] = array("name" => GetLang($this->_languagePrefix.'Email'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'EmailHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['secretword'] = array("name" => GetLang($this->_languagePrefix.'SecretWord'),
			   "type" => "password",
			   "help" => GetLang($this->_languagePrefix.'SecretWordHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['logourl'] = array("name" => GetLang($this->_languagePrefix.'LogoUrl'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'LogoUrlHelp'),
			   "default" => "",
			   "required" => false
			);

			$this->_variables['language'] = array("name" => GetLang($this->_languagePrefix.'Language'),
			   "type" => "dropdown",
			   "help" => GetLang($this->_languagePrefix.'LanguageHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(
							GetLang($this->_languagePrefix.'LanguageEN') => "EN",
							GetLang($this->_languagePrefix.'LanguageDE') => "DE",
							GetLang($this->_languagePrefix.'LanguageES') => "ES",
							GetLang($this->_languagePrefix.'LanguageFR') => "FR",
							GetLang($this->_languagePrefix.'LanguageIT') => "IT",
							GetLang($this->_languagePrefix.'LanguagePL') => "PL",
							GetLang($this->_languagePrefix.'LanguageGR') => "GR",
							GetLang($this->_languagePrefix.'LanguageRO') => "RO",
							GetLang($this->_languagePrefix.'LanguageRU') => "RU",
							GetLang($this->_languagePrefix.'LanguageTR') => "TR",
							GetLang($this->_languagePrefix.'LanguageCN') => "CN",
							GetLang($this->_languagePrefix.'LanguageCZ') => "CZ",
							GetLang($this->_languagePrefix.'LanguageNL') => "NL"
				),
				"multiselect" => false
			);

		}

		public function TransferToProvider()
		{
			$transactionid	= 'ISC-'.$this->GetCombinedOrderId();

			$url = $this->_liveTransactionURL . $this->_liveTransactionURI;

			$session = $_COOKIE['SHOP_ORDER_TOKEN'];

			$amount = number_format($this->GetGatewayAmount(),2,'.','');

			$hash = md5($GLOBALS['ISC_CFG']['serverStamp'].$this->GetCombinedOrderId().$session.$amount);

			$currency = GetDefaultCurrency();

			$billingDetails = $this->GetBillingDetails();

			$moneyBookersPostData['pay_to_email'] = $this->GetValue('mbemail');
			$moneyBookersPostData['recipient_description'] = $GLOBALS['StoreName'];
			$moneyBookersPostData['logo_url'] = $this->GetValue('logourl');
			$moneyBookersPostData['transaction_id'] = $transactionid;
			$moneyBookersPostData['return_url'] = $GLOBALS['ShopPath'] . '/finishorder.php';
			$moneyBookersPostData['cancel_url'] = $GLOBALS['ShopPath'] . '/finishorder.php';
			$moneyBookersPostData['status_url'] = $GLOBALS['ShopPath'] . '/checkout.php?action=gateway_ping&provider='.$this->GetId();
			$moneyBookersPostData['language'] = $this->GetValue('language');
			$moneyBookersPostData['hide_login'] = '1';
			$moneyBookersPostData['merchant_fields'] = 'isc_hash,isc_session';
			$moneyBookersPostData['isc_hash'] = $hash;
			$moneyBookersPostData['isc_session'] = $session;
			$moneyBookersPostData['amount'] = $amount;
			$moneyBookersPostData['currency'] = $currency['currencycode'];
			$moneyBookersPostData['detail1_description'] = 'For Order :';
			$moneyBookersPostData['detail1_text'] = $this->GetCombinedOrderId();

			$moneyBookersPostData['firstname'] = $billingDetails['ordbillfirstname'];
			$moneyBookersPostData['lastname'] = $billingDetails['ordbilllastname'];
			$moneyBookersPostData['address'] = $billingDetails['ordbillstreet1'];
			$moneyBookersPostData['address2'] = $billingDetails['ordbillstreet2'];
			$moneyBookersPostData['phone_number'] = $billingDetails['ordbillphone'];
			$moneyBookersPostData['postal_code'] = $billingDetails['ordbillzip'];
			$moneyBookersPostData['city'] = $billingDetails['ordbillsuburb'];
			$moneyBookersPostData['state'] = $billingDetails['ordbillstate'];
			$moneyBookersPostData['country'] = GetCountryISO3ById($billingDetails['ordbillcountryid']);

			header('Location:' . $url . '?'. http_build_query($moneyBookersPostData));
		}


		public function ProcessGatewayPing()
		{
			$hash = $_REQUEST['isc_hash'];
			$returnStatus = $_REQUEST['status'];
			$md5sig = $_REQUEST['md5sig'];
			$merchant_id = $_REQUEST['merchant_id'];
			$pay_to_email = $_REQUEST['pay_to_email'];
			$mb_amount = $_REQUEST['mb_amount'];
			$mb_transaction_id = $_REQUEST['mb_transaction_id'];
			$session = $_REQUEST['isc_session'];
			$mb_currency = $_REQUEST['mb_currency'];
			$transaction_id = $_REQUEST['transaction_id'];

			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), 'MoneyBookers Pingback');

			$this->SetOrderData(LoadPendingOrdersByToken($session));

			if (md5($GLOBALS['ISC_CFG']['serverStamp'].$this->GetCombinedOrderId().$session.number_format($this->GetGatewayAmount(),2)) != $hash) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'HashMismatch'));
				return false;
			}

			if (strtoupper(md5($merchant_id.$transaction_id.strtoupper(md5($this->GetValue('secretword'))).$mb_amount.$mb_currency.$returnStatus)) != $md5sig) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'HashMBMismatch'));
				return false;
			}

			$currency = GetDefaultCurrency();

			if ($returnStatus != 2) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Failure'));
				return false;
			}

			if ($mb_currency != $currency['currencycode']) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'CurrencyMismatch'));
				return false;
			}

			if ($mb_amount != $this->GetGatewayAmount()) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'PaymentMismatch'));
				return false;
			}

			if ($pay_to_email != $this->GetValue('mbemail')) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'AccountMismatch'));
				return false;
			}


			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

			// Update the status for all orders that we've just received the payment for
			foreach($this->GetOrders() as $orderId => $order) {
				$status = ORDER_STATUS_AWAITING_FULFILLMENT;
				// If it's a digital order & awaiting fulfillment, automatically complete it
				if($order['ordisdigital'] && ORDER_STATUS_AWAITING_FULFILLMENT) {
					$status = ORDER_STATUS_COMPLETED;
				}
				UpdateOrderStatus($orderId, $status);
			}

			return true;
		}


		public function VerifyOrderPayment()
		{
			if(!empty($_COOKIE['SHOP_ORDER_TOKEN'])) {
				// This order is still incomplete, IPN notification hasn't been received yet, so the payment status is pending
				if($this->GetOrderStatus() == ORDER_STATUS_INCOMPLETE) {
					$this->SetPaymentStatus(PAYMENT_STATUS_PENDING);
				}
				// Always return successful, the IPN pingback will actually validate the order and do all of the magic
				return true;
			}
			else {
				// Bad order details
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'ErrorInvalid'), __FUNCTION__);
				return false;
			}
		}


	}