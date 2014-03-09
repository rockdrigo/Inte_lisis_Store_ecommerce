<?php

	class CHECKOUT_CHRONOPAYPAGE extends ISC_CHECKOUT_PROVIDER
	{
		protected $requiresSSL = false;

		public $_id = "checkout_chronopaypage";

		protected $_currenciesSupported = array('USD');
		public $_languagePrefix = 'ChronoPayPage';

		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the Chronopay Page checkout module
			parent::__construct();
			$this->_name = GetLang($this->_languagePrefix.'Name');
			$this->_image = "logo.gif";
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

			$this->_variables['ProductId'] = array("name" => GetLang($this->_languagePrefix."ProductId"),
			 "type" => "textbox",
			 "help" => GetLang($this->_languagePrefix.'ProductIdHelp'),
			 "default" => '',
			 "required" => true
			);

			$this->_variables['LanguageCode'] = array("name" => GetLang($this->_languagePrefix.'LanguageCode'),
			   "type" => "dropdown",
			   "help" => GetLang($this->_languagePrefix.'LanguageCodeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(
								GetLang($this->_languagePrefix.'LanguageEN') => "EN",
								GetLang($this->_languagePrefix.'LanguageDE') => "DE",
								GetLang($this->_languagePrefix.'LanguageNL') => "NL",
								GetLang($this->_languagePrefix.'LanguageRU') => "RU"
				),
				"multiselect" => false
			);
		}

		public function TransferToProvider()
		{
			$url = 'https://secure.chronopay.com/index_shop.cgi';
			$currency = GetDefaultCurrency();
			$currency = $currency['currencycode'];

			$hash = md5($GLOBALS['ISC_CFG']['serverStamp'].$_COOKIE['SHOP_ORDER_TOKEN'].$this->GetCombinedOrderId().$this->GetValue('ProductId'));
			$billingDetails = $this->GetBillingDetails();

			$chronopayform['product_id'] = $this->GetValue('ProductId');
			$chronopayform['product_name'] = $GLOBALS['StoreName'];
			$chronopayform['product_price'] = $this->GetGatewayAmount();
			$chronopayform['product_price_currency'] = $currency;
			$chronopayform['language'] = $this->GetValue('LanguageCode');
			$chronopayform['cs1'] = $hash;
			$chronopayform['cs2'] = $_COOKIE['SHOP_ORDER_TOKEN'];
			$chronopayform['cb_url'] = $GLOBALS['ShopPath'] . '/modules/checkout/chronopaypage/chronopaypingback.php';
			$chronopayform['decline_url'] = $GLOBALS['ShopPath'] . '/finishorder.php';
			$chronopayform['cb_type'] = 'P';
			$chronopayform['f_name'] = $billingDetails['ordbillfirstname'];
			$chronopayform['s_name'] = $billingDetails['ordbilllastname'];
			$chronopayform['street'] = $billingDetails['ordbillstreet1'] . ' '. $billingDetails['ordbillstreet2'];
			$chronopayform['city'] = $billingDetails['ordbillsuburb'];

			if ($billingDetails['ordbillcountryid'] == '38' || $billingDetails['ordbillcountryid'] == '226') {
				$chronopayform['state'] = GetStateISO2ByName($billingDetails['ordbillstate']);
			}

			$chronopayform['zip'] = $billingDetails['ordbillzip'];
			$chronopayform['country'] = GetCountryISO3ById($billingDetails['ordbillcountryid']);
			$chronopayform['phone'] = $billingDetails['ordbillphone'];
			$chronopayform['email'] = $billingDetails['ordbillemail'];

			header('Location: ' . $url . '?'. http_build_query($chronopayform));
		}

		public function ProcessGatewayPing()
		{
			$siteid = $_REQUEST['site_id'];
			$productid = $_REQUEST['product_id'];

			$email = $_REQUEST['email'];
			$country = $_REQUEST['country'];
			$name = $_REQUEST['name'];
			$city = $_REQUEST['city'];
			$street = $_REQUEST['street'];
			$state = $_REQUEST['state'];
			$zip = $_REQUEST['zip'];

			$hash = $_REQUEST['cs1'];
			$session = $_REQUEST['cs2'];

			$currency = $_REQUEST['currency'];
			$siteCurrency = GetDefaultCurrency();

			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), 'ChronoPay Pingback');

			if ($currency != $siteCurrency['currencycode']) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'CurrencyMismatch'), $siteCurrency['currencycode'], $currency));
				return false;
			}

			$this->SetOrderData(LoadPendingOrdersByToken($session));

			if (md5($GLOBALS['ISC_CFG']['serverStamp'].$session.$this->GetCombinedOrderId().$this->GetValue('ProductId')) != $hash) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'HashMismatch'));
				return false;
			}

			if (isset($_REQUEST['transaction_id'])) {
				$updatedOrder = array(
					'ordpayproviderid' => $_REQUEST['transaction_id'],
					'ordpaymentstatus' => 'captured',
				);
			}

			$this->UpdateOrders($updatedOrder);

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