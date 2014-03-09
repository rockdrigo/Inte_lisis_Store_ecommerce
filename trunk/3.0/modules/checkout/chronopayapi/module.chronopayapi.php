<?php

	class CHECKOUT_CHRONOPAYAPI extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			$this->_languagePrefix 		= "ChronoPayApi";
			$this->_id 					= "checkout_chronopayapi";
			$this->_image 				= "logo.gif";

			parent::__construct();

			$this->_requiresSSL 		= true;
			$this->_currenciesSupported = array('USD');
			$this->_liveTransactionURL 	= 'https://secure.chronopay.com';
			$this->_testTransactionURL 	= 'https://secure.chronopay.com';
			$this->_liveTransactionURI 	= '/gateway.cgi';
			$this->_testTransactionURI 	= '/gateway.cgi';
			$this->_curlSupported 		= true;
			$this->_fsocksSupported 	= true;
			$this->cardCodeRequired 	= true;
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

			$this->_variables['productid'] = array("name" => GetLang($this->_languagePrefix.'ProductId'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'ProductIdHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['sharedsecret'] = array("name" => GetLang($this->_languagePrefix.'SharedSecret'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'SharedSecretHelp'),
			   "default" => "",
			   "required" => true
			);
		}

		protected function _ConstructPostData($postData)
		{
			// PaymentExpress accepts payments in cents

			$ccname 		= $postData['name'];
			$cctype 		= $postData['cctype'];

			$ccissueno 		= $postData['ccissueno'];
			$ccissuedatem 	= $postData['ccissuedatem'];
			$ccissuedatey 	= $postData['ccissuedatey'];

			$ccnum 			= $postData['ccno'];
			$ccexpm 		= $postData['ccexpm'];
			$ccexpy 		= $postData['ccexpy'];
			$cccvd 			= $postData['cccvd'];

			$currency = GetDefaultCurrency();

			$billingDetails = $this->GetBillingDetails();

			$chronoPayPostData['opcode'] = 1;
			$chronoPayPostData['product_id'] = $this->GetValue('productid');
			$chronoPayPostData['fname'] = $billingDetails['ordbillfirstname'];
			$chronoPayPostData['lname'] = $billingDetails['ordbilllastname'];
			$chronoPayPostData['cardholder'] = $ccname;
			$chronoPayPostData['zip'] = $billingDetails['ordbillzip'];
			$chronoPayPostData['street'] = $billingDetails['ordbillstreet1'] . ' ' . $billingDetails['ordbillstreet2'];
			$chronoPayPostData['city'] = $billingDetails['ordbillsuburb'];

			if ($billingDetails['ordbillcountryid'] == '38' || $billingDetails['ordbillcountryid'] == '226') {
				$chronoPayPostData['state'] = GetStateISO2ByName($billingDetails['ordbillstate']);
			}

			$chronoPayPostData['country'] = GetCountryISO3ById($billingDetails['ordbillcountryid']);
			$chronoPayPostData['email'] = $billingDetails['ordbillemail'];
			$chronoPayPostData['phone'] = $billingDetails['ordbillphone'];

			if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR']) {
				$chronoPayPostData['ip'] = $_SERVER['REMOTE_ADDR'];
			} else {
				// if the above is not set we're probably running in a unit test
				$chronoPayPostData['ip'] = '127.0.0.1';
			}

			$chronoPayPostData['card_no'] = $ccnum;
			$chronoPayPostData['cvv'] = $cccvd;
			$chronoPayPostData['expirey'] = "20".$ccexpy;
			$chronoPayPostData['expirem'] = $ccexpm;
			$chronoPayPostData['amount'] = $this->GetGatewayAmount();
			$chronoPayPostData['currency'] = $currency['currencycode'];

			$hash = md5(
				$this->GetValue('sharedsecret').
				$chronoPayPostData['opcode'].
				$chronoPayPostData['product_id'].
				$chronoPayPostData['fname'].
				$chronoPayPostData['lname'].
				$chronoPayPostData['street'].
				$chronoPayPostData['ip'].
				$chronoPayPostData['card_no'].
				$chronoPayPostData['amount']
			);

			$chronoPayPostData['hash'] = $hash;

			return http_build_query($chronoPayPostData);
		}

		protected function _HandleResponse($response)
		{
			$response = preg_replace('/[\r\n]+/', '', $response);
			$response = explode('|', $response);

			$responseCode = $responseMessage = '';

			if (isset($response[0])) {
				$responseCode = $response[0];
			}

			if (isset($response[1])) {
				$responseMessage = $response[1];
			}

			if ($responseCode == 'Y') {

				if ($responseMessage != '') {
					// Save the authorization key
					$updatedOrder = array(
						'ordpayproviderid' => $responseMessage,
						'ordpaymentstatus' => 'captured',
					);

					$this->UpdateOrders($updatedOrder);
				}

				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

				return true;
			}
			else {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Failure'), $responseCode, $responseMessage));
				$this->SetError(GetLang($this->_languagePrefix.'SomethingWentWrong').sprintf(" %s : %s", $responseCode, $responseMessage));
				return false;
			}
		}
	}
