<?php

	class CHECKOUT_NOCHEX extends ISC_CHECKOUT_PROVIDER
	{

		protected $requiresSSL = false;

		public $_id = "checkout_nochex";

		protected $_currenciesSupported = array('GBP');
		public $_languagePrefix = 'NoChex';

		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the Realex checkout module
			parent::__construct();
			$this->_name = GetLang($this->_languagePrefix.'Name');
			$this->_image = "nochex.jpg";
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
			$this->_variables['displayname'] = array("name" => GetLang($this->_languagePrefix.'DisplayName'),
			   "type" => "textbox",
			   "help" => GetLang('DisplayNameHelp'),
			   "default" => $this->GetName(),
			   "required" => true
			);

			$this->_variables['merchantid'] = array("name" => GetLang($this->_languagePrefix.'MerchantId'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'MerchantIdHelp'),
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

		public function TransferToProvider()
		{
			$billingDetails							= $this->GetBillingDetails();

			$nochexpost['amount']					= number_format($this->GetGatewayAmount(),2,'.','');
			$nochexpost['billing_address']			= $billingDetails['ordbillstreet1'] . ' ' . $billingDetails['ordbillstreet2'] .
													' ' . $billingDetails['ordbillsuburb'] . ' ' . $billingDetails['ordbillstate'] . ' ' . $billingDetails['ordbillcountry'];
			$nochexpost['merchant_id']				= $this->GetValue('merchantid');
			$nochexpost['description']				= GetLang($this->_languagePrefix.'YourOrderFromX') . $GLOBALS['StoreName'];
			$nochexpost['billing_fullname']			= $billingDetails['ordbillfirstname'] . ' ' . $billingDetails['ordbilllastname'];
			$nochexpost['billing_postcode']			= $billingDetails['ordbillzip'];
			$nochexpost['email_address']			= $billingDetails['ordbillemail'];
			$nochexpost['customer_phone_number']	= $billingDetails['ordbillphone'];
			$nochexpost['hide_billing_details']		= 'false';

			$session								= $_COOKIE['SHOP_ORDER_TOKEN'];
			$hash									= md5($GLOBALS['ISC_CFG']['serverStamp'].$nochexpost['amount'].$nochexpost['merchant_id'].$session);

			$nochexpost['callback_url'] = $GLOBALS['ShopPath'] . '/checkout.php?action=gateway_ping&session='.$session.'&hash='.$hash.'&provider=checkout_nochex';

			if ($this->GetValue('testmode') == 'YES') {
				$nochexpost['test_transaction'] = 100;
				$nochexpost['status'] = 'test';
				$nochexpost['test_success_url'] = $GLOBALS['ShopPath'] . '/finishorder.php';
			}
			else {
				$nochexpost['success_url'] =  $GLOBALS['ShopPath'] . '/finishorder.php?success=true';
				$nochexpost['cancel_url'] =  $GLOBALS['ShopPath'] . '/checkout.php';
				$nochexpost['declined_url'] =  $GLOBALS['ShopPath'] . '/finishorder.php';
			}

			header('Location:https://secure.nochex.com/?'.http_build_query($nochexpost));
		}

		public function ProcessGatewayPing()
		{
			$transactionid = $_REQUEST['transaction_id'];
			$order_id = $_REQUEST['order_id'];
			$amount = $_REQUEST['amount'];
			$from_email = $_REQUEST['from_email'];
			$session = $_REQUEST['session'];

			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), 'APC Pingback');

			$this->SetOrderData(LoadPendingOrdersByToken($session));

			if (md5($GLOBALS['ISC_CFG']['serverStamp'].number_format($amount,2).$this->GetValue('merchantid').$session) != $_REQUEST['hash']) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'HashMismatch'));
				return false;
			}

			if(function_exists("curl_exec")) {
				// Use CURL if it's available
				$ch = curl_init('https://www.nochex.com/nochex.dll/apc/apc');
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POST));
				curl_setopt($ch, CURLOPT_TIMEOUT, 60);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

				// Setup the proxy settings if there are any
				if (GetConfig('HTTPProxyServer')) {
					curl_setopt($ch, CURLOPT_PROXY, GetConfig('HTTPProxyServer'));
					if (GetConfig('HTTPProxyPort')) {
						curl_setopt($ch, CURLOPT_PROXYPORT, GetConfig('HTTPProxyPort'));
					}
				}

				if (GetConfig('HTTPSSLVerifyPeer') == 0) {
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				}

				$result = curl_exec($ch);

				if(curl_errno($ch)) {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'CurlError'));
					return false;
				}
			}

			if (isset($result) && $result == 'AUTHORISED') {

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

			return false;
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