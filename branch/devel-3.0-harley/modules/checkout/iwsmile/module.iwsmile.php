<?php

	class CHECKOUT_IWSMILE extends ISC_CHECKOUT_PROVIDER
	{
		protected $requiresSSL = false;

		public $_id = "checkout_iwsmile";

		protected $_currenciesSupported = array('EUR');
		public $_languagePrefix = 'IWSmile';

		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the IWSmile checkout module
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

			$this->_variables['account'] = array("name" => GetLang($this->_languagePrefix."Account"),
			 "type" => "textbox",
			 "help" => GetLang($this->_languagePrefix.'AccountHelp'),
			 "default" => '',
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

			$this->_variables['LanguageCode'] = array("name" => GetLang($this->_languagePrefix.'LanguageCode'),
			   "type" => "dropdown",
			   "help" => GetLang($this->_languagePrefix.'LanguageCodeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(
								GetLang($this->_languagePrefix.'LanguageIT') => "IT",
								GetLang($this->_languagePrefix.'LanguageEN') => "EN",
								GetLang($this->_languagePrefix.'LanguageDE') => "DE",
								GetLang($this->_languagePrefix.'LanguageFR') => "FR",
								GetLang($this->_languagePrefix.'LanguageES') => "ES"
				),
				"multiselect" => false
			);
		}

		public function TransferToProvider()
		{
			if ($this->GetValue('testmode') == 'YES') {
				$url = "https://testcheckout.iwsmile.it/Pagamenti/";
			} else {
				$url = "https://checkout.iwsmile.it/Pagamenti/";
			}

			$billingDetails = $this->GetBillingDetails();

			$hiddenFields = array(
				'ACCOUNT'			=> $this->GetValue('account'),
				'AMOUNT'			=> number_format($this->GetGatewayAmount(),2,'.',''),
				'ITEM_NAME'			=> 'Your Order from ' . $GLOBALS['StoreName'],
				'ITEM_NUMBER'		=> $this->GetCombinedOrderId(),
				'LANG_COUNTRY'		=> $this->GetValue('LanguageCode'),
				'QUANTITY'			=> '1',
				'NOTE'				=> '1',
				'CUSTOM'			=> $_COOKIE['SHOP_ORDER_TOKEN'] . '_' . $_COOKIE['SHOP_SESSION_TOKEN'],
				'URL_OK'			=> $GLOBALS['ShopPath'] . '/finishorder.php',
				'URL_BAD'			=> $GLOBALS['ShopPath'] . '/cart.php',
				'URL_CALLBACK' 		=> $GLOBALS['ShopPath'] . '/checkout.php?action=gateway_ping&provider='.$this->GetId(),
				'FLAG_ONLY_IWS'		=> '0',
			);

			$this->RedirectToProvider($url, $hiddenFields);
		}

		public function ProcessGatewayPing()
		{
			$pName = $_REQUEST['payer_name'];
			$pEmail = $_REQUEST['payer_email'];
			$qta = $_REQUEST['qta'];
			$thxId = $_REQUEST['thx_id'];

			$sessionToken = explode('_', $_REQUEST['custom'], 2);

			$payerId = $_REQUEST['payer_id'];
			$amount = $_REQUEST['amount'];

			$this->SetOrderData(LoadPendingOrdersByToken($sessionToken[0]));

			if($this->GetGatewayAmount() == 0) {
				exit;
			}

			if ($amount != $this->GetGatewayAmount()) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'AmountMismatch'), $this->GetGatewayAmount(), $amount));
				return false;
			}

			if (isset($_REQUEST['payer_email'])) {
				$updatedOrder = array(
					'ordpayproviderid' => $pEmail,
					'ordpaymentstatus' => 'captured',
				);

				$this->UpdateOrders($updatedOrder);
			}

			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

			// If the order was previously incomplete, we need to do some extra work
			if($this->GetOrderStatus() == ORDER_STATUS_INCOMPLETE) {
				// If a customer doesn't return to the store from PayPal, their cart will never be
				// emptied. So what we do here, is if we can, load up the existing customers session
				// and empty the cart and kill the checkout process. When they next visit the store,
				// everything should be "hunky-dory."
				session_write_close();
				$session = new ISC_SESSION($sessionToken[1]);
				EmptyCartAndKillCheckout();
			}

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