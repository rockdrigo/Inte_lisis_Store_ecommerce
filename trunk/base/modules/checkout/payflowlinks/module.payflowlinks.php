<?php

	class CHECKOUT_PAYFLOWLINKS extends ISC_CHECKOUT_PROVIDER
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the USAePay checkout module

			$this->_languagePrefix = "PayFlowLinks";
			parent::__construct();

			$this->_id = "checkout_payflowlinks";
			$this->_image = "paypal_logo.gif";

			$this->_name = GetLang('PayFlowLinksName');
			$this->_description = GetLang('PayFlowLinksDesc');
			$this->_help = sprintf(GetLang('PayFlowLinksHelp'), $GLOBALS['ShopPathSSL'].'/finishorder.php?provider='.$this->GetId(), $GLOBALS['ShopPathSSL'].'/checkout.php?action=gateway_ping&provider='.$this->GetId());
			$this->_height = 0;


			$this->requiresSSL = true;
			$this->_currenciesSupported = array('USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD');
			$this->_curlSupported = true;
			$this->_fsocksSupported = true;
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
			$this->_variables['displayname'] = array("name" => GetLang('DisplayName'),
			   "type" => "textbox",
			   "help" => GetLang('DisplayNameHelp'),
			   "default" => GetLang('PayFlowLinksDefaultDisplayName'),
			   "required" => true
			);

			$this->_variables['paypallogin'] = array("name" => GetLang($this->_languagePrefix.'Login'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'LoginHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['paypalpartner'] = array("name" => GetLang($this->_languagePrefix.'Partner'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'PartnerHelp'),
			   "default" => "",
			   "required" => true
			);

		}

		public function TransferToProvider()
		{
			$total = number_format($this->GetGatewayAmount(),2,'.','');
			$transactionid = $this->GetCombinedOrderId();
			$billingDetails = $this->GetBillingDetails();

			$hash = md5($GLOBALS['ISC_CFG']['serverStamp'].$total.$transactionid.$_COOKIE['SHOP_ORDER_TOKEN'].$this->GetValue('paypallogin').$this->GetValue('paypalpartner'));

			$url = 'https://payflowlink.paypal.com/';

			$postData = '?login=' . $this->GetValue('paypallogin');
			$postData .= '&partner=' . $this->GetValue('paypalpartner');
			$postData .= '&amount=' . $total;
			$postData .= '&description=' . sprintf(GetLang($this->_languagePrefix.'YourOrderFromX'), $GLOBALS['StoreName']);
			$postData .= '&type=S';
			$postData .= '&orderform=true';
			$postData .= '&method=cc';
			$postData .= '&invoice=' . $transactionid;
			$postData .= '&name=' . $billingDetails['ordbillfirstname'] . ' ' . $billingDetails['ordbilllastname'];
			$postData .= '&email=' . $billingDetails['ordbillemail'];
			$postData .= '&phone=' . $billingDetails['ordbillphone'];
			$postData .= '&address=' . $billingDetails['ordbillstreet1'] . ' ' . $billingDetails['ordbillstreet2'];
			$postData .= '&city=' . $billingDetails['ordbillsuburb'];
			$postData .= '&state=' . $billingDetails['ordbillstate'];
			$postData .= '&zip=' . $billingDetails['ordbillzip'];
			$postData .= '&country=' . $billingDetails['ordbillcountry'];
			$postData .= '&user1=' . $_COOKIE['SHOP_ORDER_TOKEN'];
			$postData .= '&user2=' . $hash;
			$postData .= '&user3=' . $_COOKIE['SHOP_SESSION_TOKEN'];

			header('Location: '.$url. $postData);
		}

		public function ProcessGatewayPing()
		{
			$this->SetOrderData(LoadPendingOrdersByToken($_REQUEST['USER1']));

			if($this->GetGatewayAmount() == 0) {
				return false;
			}

			$orderid = $this->GetCombinedOrderId();

			$hash = md5($GLOBALS['ISC_CFG']['serverStamp'].$_REQUEST['AMOUNT'].$orderid.$_REQUEST['USER1'].$this->GetValue('paypallogin').$this->GetValue('paypalpartner'));

			if ($_REQUEST['USER2'] != $hash) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'HashMismatch'));
				return false;
			}

			if (!isset($_REQUEST['INVOICE']) || $orderid != $_REQUEST['INVOICE']) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'OrderMismatch'), sprintf("Sent %s. Received %s", $orderid, $_REQUEST['INVOICE']));
				return false;
			}

			if ($this->GetGatewayAmount() != $_REQUEST['AMOUNT']) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'PaymentMismatch'), sprintf("Sent %s. Received %s", $this->GetGatewayAmount(), $_REQUEST['AMOUNT']));
				return false;
			}

			if ($_REQUEST['RESULT'] == 0 && $_REQUEST['RESPMSG'] == 'Approved') {


				$oldOrderStatus = $this->GetOrderStatus();
				// If the order was previously incomplete, we need to do some extra work
				if($oldOrderStatus == ORDER_STATUS_INCOMPLETE) {
					// If a customer doesn't return to the store from PayPal, their cart will never be
					// emptied. So what we do here, is if we can, load up the existing customers session
					// and empty the cart and kill the checkout process. When they next visit the store,
					// everything should be "hunky-dory."
					session_write_close();
					$session = new ISC_SESSION($_REQUEST['USER3']);
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

//			$transactionId = $_REQUEST['PNREF'];
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