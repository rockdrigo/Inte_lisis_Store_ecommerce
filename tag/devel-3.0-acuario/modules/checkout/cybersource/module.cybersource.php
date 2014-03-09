<?php

	require_once('lib/HOP.php');

	class CHECKOUT_CYBERSOURCE extends ISC_GENERIC_CREDITCARD
	{

		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the Cybersouce checkout module

			$this->_languagePrefix = "CyberSource";
			$this->_id = "checkout_cybersource";
			$this->_image = "cybersource.jpg";
			$this->shoppathssl = true;

			parent::__construct();

			$this->requiresSSL = true;
			$this->_currenciesSupported = array("USD");
			$this->_cardsSupported = array ('VISA','AMEX','MC','DINERS','DISCOVER','MAESTRO');
			$this->_redirect = true;

			$this->_liveTransactionURL = 'https://orderpage.ic3.com';
			$this->_testTransactionURL = 'https://orderpagetest.ic3.com';
			$this->_liveTransactionURI = '/hop/ProcessOrder.do';
			$this->_testTransactionURI = '/hop/ProcessOrder.do';
			$this->_curlSupported = true;
			$this->_fsocksSupported = true;
			$this->shoppathssl = true;
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

			$this->_variables['cardcode'] = array("name" => GetLang($this->_languagePrefix."CardCode"),
			   "type" => "dropdown",
			   "help" => GetLang('CyberSourceCardCodeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(GetLang('CyberSourceCardCodeNo') => "NO",
							  GetLang('CyberSourceCardCodeYes') => "YES"
				),
				"multiselect" => false
			);

			$this->_variables['testmode'] = array("name" => GetLang($this->_languagePrefix."TestMode"),
			   "type" => "dropdown",
			   "help" => GetLang('CyberSourceTestModeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(GetLang('CyberSourceTestModeNo') => "NO",
							  GetLang('CyberSourceTestModeYes') => "YES"
				),
				"multiselect" => false
			);
		}

		protected function _ConstructPostData($postData)
		{
			$currency = GetDefaultCurrency();

			$currencycode = strtolower($currency['currencycode']);

			switch ($postData['cctype']) {
				case 'VISA':
					$cctype = '001';
					break;
				case 'MC':
					$cctype = '002';
					break;
				case 'AMEX':
					$cctype = '003';
					break;
				case 'DISCOVER':
					$cctype = '004';
					break;
				case 'DINERS':
					$cctype = '005';
					break;
				default:
					$cctype = '000';
					break;
			}

			$amount = $this->GetGatewayAmount();
			$billingDetails = $this->GetBillingDetails();
			$timestamp = getmicrotime();
			$transactionid = $this->GetCombinedOrderId();

			$signatureData = getMerchantID() . $amount . $currencycode . $timestamp.'sale';

			$cybersourcePost['merchantID'] 					= getMerchantID();
			$cybersourcePost['billTo_firstName'] 			= htmlentities($billingDetails['ordbillfirstname']);
			$cybersourcePost['billTo_lastName'] 			= $billingDetails['ordbilllastname'];
			$cybersourcePost['billTo_street1'] 				= $billingDetails['ordbillstreet1'];
			$cybersourcePost['billTo_city'] 				= $billingDetails['ordbillsuburb'];
			$cybersourcePost['billTo_state'] 				= $billingDetails['ordbillstate'];
			$cybersourcePost['billTo_postalCode'] 			= $billingDetails['ordbillzip'];
			$cybersourcePost['billTo_country'] 				= $billingDetails['ordbillcountry'];
			$cybersourcePost['billTo_email'] 				= $billingDetails['ordbillemail'];
			$cybersourcePost['card_cardType'] 				= $cctype;
			$cybersourcePost['card_accountNumber'] 			= $postData['ccno'];
			$cybersourcePost['card_expirationMonth'] 		= $postData['ccexpm'];
			$cybersourcePost['card_expirationYear']	 		= '20'.$postData['ccexpy'];
			$cybersourcePost['card_cvNumber']	 			= $postData['cccvd'];
			$cybersourcePost['orderPage_timestamp'] 		= $timestamp;
			$cybersourcePost['orderPage_signaturePublic'] 	= hopHash($signatureData,getPublicKey());
			$cybersourcePost['orderPage_serialNumber']		= getSerialNumber();
			$cybersourcePost['orderPage_version'] 			= '4';
			$cybersourcePost['orderPage_transactionType'] 	= 'sale';
			$cybersourcePost['amount'] 						= $amount;
			$cybersourcePost['currency'] 					= $currencycode;
			$cybersourcePost['hash'] 						= md5($this->GetValue("accessid").$transactionid.$_COOKIE['SHOP_ORDER_TOKEN'].$amount);
			$cybersourcePost['orderid'] 					= $transactionid;
			$cybersourcePost['iscsessionid'] 				= $_COOKIE['SHOP_ORDER_TOKEN'];

			return $cybersourcePost;
		}

		public function VerifyOrderPayment()
		{
			$status = $_REQUEST['decision'];
			$orderid = $_REQUEST['orderid'];
			$hash = $_REQUEST['hash'];
			$sessionId = $_REQUEST['iscsessionid'];
			$amount = $_REQUEST['orderAmount'];
			$transactionid = $this->GetCombinedOrderId();

			if (empty($status) || $status != 'ACCEPT' ) {
				$ErrorLog = sprintf(GetLang('CyberSourceError'), $orderid, $_REQUEST['reasonCode'], $_REQUEST['decision']);

				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $ErrorLog);
				$this->RedirectToOrderConfirmation(GetLang('CyberSourceDeclinedRedirect'));
				exit;
			}

			if ($orderid != $transactionid || $sessionId != $_COOKIE['SHOP_ORDER_TOKEN'] || $amount != $this->GetGatewayAmount()) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('CyberSourceErrorMismatch'));
				return false;
			}

			if (md5($this->GetValue("accessid").$orderid.$sessionId.$amount) != $hash) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('CyberSourceErrorMismatch'));
				return false;
			}

			if ($orderid != -1) {

				$updatedOrder = array(
					'ordpayproviderid' => $orderid,
					'ordpaymentstatus' => 'captured',
				);

				$this->UpdateOrders($updatedOrder);
			}

			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang('CyberSourceSuccess'));
			$this->SetPaymentStatus(PAYMENT_STATUS_PAID);
			return true;
		}
	}