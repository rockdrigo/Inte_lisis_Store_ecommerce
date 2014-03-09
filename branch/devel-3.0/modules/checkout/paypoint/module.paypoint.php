<?php

	class CHECKOUT_PAYPOINT extends ISC_GENERIC_CREDITCARD
	{
		private $identifier = null;

		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			$this->_languagePrefix 		= "PayPoint";
			$this->_id 					= "checkout_paypoint";
			$this->_image 				= "logo.jpg";

			parent::__construct();

			$this->_requiresSSL 		= true;
			$this->_currenciesSupported = array('USD', 'GBP', 'CAD', 'AUD', 'JPY', 'EUR', 'HKD');
			$this->_liveTransactionURL 	= 'https://www.secpay.com';
			$this->_testTransactionURL 	= 'https://www.secpay.com';
			$this->_liveTransactionURI 	= '/java-bin/services/SECCardService?wsdl';
			$this->_testTransactionURI 	= '/java-bin/services/SECCardService?wsdl';
			$this->_curlSupported = false;
			$this->_fsocksSupported = false;
			$this->requiresSoap = true;
			$this->cardCodeRequired = true;
			$this->soapAction = 'validateCardFull';
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

			$this->_variables['mid'] = array("name" => GetLang($this->_languagePrefix.'MID'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'TerminalIdHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['password'] = array("name" => GetLang($this->_languagePrefix.'Password'),
			   "type" => "password",
			   "help" => GetLang($this->_languagePrefix.'ServerIdHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['testmode'] = array("name" => GetLang($this->_languagePrefix."TestMode"),
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

		protected function _ConstructPostData($postData)
		{
			$transactionid 	= $this->GetCombinedOrderId();

			$ccname			= $postData['name'];
			$cctype			= $postData['cctype'];

			$ccissueno 		= $postData['ccissueno'];
			$ccissuedatem	= $postData['ccissuedatem'];
			$ccissuedatey 	= $postData['ccissuedatey'];

			$ccnum 			= $postData['ccno'];
			$ccexpm 		= $postData['ccexpm'];
			$ccexpy 		= $postData['ccexpy'];
			$cccvd 			= $postData['cccvd'];

			$amount = number_format($this->GetGatewayAmount(), 2, '.','');

			$ccnum = $postData['ccno'];
			$currency = GetDefaultCurrency();

			$billingDetails = $this->GetBillingDetails();

			$this->identifier = substr(sha1(time()),0,10);

			$this->_testmode = $this->GetValue("testmode") == "YES";

			if ($this->_testmode) {
				$test = 'true';
			}
			else {
				$test = 'live';
			}

			switch ($cctype) {

				case 'VISA':
					$cctype = 'Visa';
					break;
				case 'MC':
					$cctype = 'Master Card';
					break;
				case 'MAESTRO':
					$cctype = 'Maestro';
					break;
				case 'AMEX':
					$cctype = 'American Express';
					break;
				case 'DINERS';
					$cctype = 'Diners Card';
					break;
				case 'JCB':
					$cctype = 'JCB';
					break;

			}



			//load all orders for this transaction
			$orders = $this->GetOrders();
			if(empty($orders)) {
				$orderData = LoadPendingOrdersByToken($_COOKIE['SHOP_ORDER_TOKEN']);
				$this->SetOrderData($orderData);
				$orders = $this->GetOrders();
			}
			$order = current($orders);
			$orderIds = '#'.implode(', #', array_keys($orders));

			// Grab the billing details for the order
			$billingDetails = $this->GetBillingDetails();

			$billingAddress = array(
				'name'		=> $billingDetails['ordbillfirstname']." ".$billingDetails['ordbilllastname'],
				'company'	=> $billingDetails['ordbillcompany'],
				'addr_1'	=> $billingDetails['ordbillstreet1'],
				'addr_2'	=> $billingDetails['ordbillstreet2'],
				'city'		=> $billingDetails['ordbillsuburb'],
				'state'		=> $billingDetails['ordbillstate'],
				'country'	=> $billingDetails['ordbillcountry'],
				'post_code'	=> $billingDetails['ordbillzip'],
				'tel'		=> $billingDetails['ordbillphone'],
				'email'		=> $billingDetails['ordbillemail'],
			);

			$billingString = http_build_query($billingAddress, '', ',');

			// get the shipping details
			$shippingAddress = $this->getShippingAddress();
			$shippingAddress = array(
				'name'		=> $shippingAddress['first_name']." ".$shippingAddress['last_name'],
				'company'	=> $shippingAddress['company'],
				'addr_1'	=> $shippingAddress['address_1'],
				'addr_2'	=> $shippingAddress['address_2'],
				'city'		=> $shippingAddress['city'],
				'state'		=> $shippingAddress['state'],
				'country'	=> $shippingAddress['country'],
				'post_code'	=> $shippingAddress['zip'],
				'tel'		=> $shippingAddress['phone'],
				'email'		=> $shippingAddress['email'],
			);

			$shippingString = http_build_query($shippingAddress, '', ',');

			$gatewayData = array (
				'mid' => $this->GetValue('mid'),
				'vpn_pswd' => $this->GetValue('password'),
				'trans_id'=>$transactionid,
				'name'=>$ccname,
				'card_number'=>$ccnum,
				'amount'=>$amount,
				'expiry_date'=>$ccexpm.$ccexpy,
				'issue_number'=>$ccissueno,
				'start_date'=>$ccissuedatem.$ccissuedatey,
				'order'=>"",
				'shipping'=> $shippingString,
				'billing' => $billingString,
				'options'=>"test_status=".$test.",dups=false,card_type=".$cctype.",cv2=".$cccvd.",currency=".$currency['currencycode'],
				'returnVariable'=>"authResponse"
			);

			return array('gatewayData'=>$gatewayData, 'soapAction'=>$this->soapAction);
		}

		protected function _HandleResponse($response)
		{
			$result = array();
			parse_str($response, $result);

			$response = $response[$this->soapAction.'Result'];
			if ($result['?valid'] == 'true') {

				// Save the authorization key
				$updatedOrder = array(
					'ordpayproviderid' => $result['trans_id'],
					'ordpaymentstatus' => 'captured',
				);

				$this->UpdateOrders($updatedOrder);

				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

				return true;
			}
			else {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Failure'), $result['message']));
				$this->SetError(GetLang($this->_languagePrefix.'SomethingWentWrong').sprintf(" : %s", $result['message']));
				return false;
			}
		}
	}