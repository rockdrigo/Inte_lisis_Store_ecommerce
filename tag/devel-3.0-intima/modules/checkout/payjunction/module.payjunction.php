<?php

	class CHECKOUT_PAYJUNCTION extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the PayJunction checkout module
			$this->_languagePrefix = "PayJunction";
			$this->_id = "checkout_payjunction";
			$this->_image = "payjunction.gif";

			parent::__construct();

			$this->requiresSSL = true;
			$this->_currenciesSupported = array('USD');
			$this->_cardsSupported = array ('VISA','AMEX','MC','DISCOVER');

			$this->_liveTransactionURL = 'https://payjunction.com';
			$this->_testTransactionURL = 'https://demo.payjunction.com';
			$this->_liveTransactionURI = '/quick_link';
			$this->_testTransactionURI = '/quick_link';
			$this->_curlSupported = true;
			$this->_fsocksSupported = true;
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

			$this->_variables['accountname'] = array("name" => GetLang($this->_languagePrefix.'AccountName'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'AccountNameHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['accountpassword'] = array("name" => GetLang($this->_languagePrefix.'AccountPassword'),
			   "type" => "password",
			   "help" => GetLang($this->_languagePrefix.'AccountPasswordHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['cardcode'] = array("name" => GetLang($this->_languagePrefix.'CardCode'),
			   "type" => "dropdown",
			   "help" => GetLang($this->_languagePrefix.'CardCodeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(GetLang($this->_languagePrefix.'CardCodeNo') => "NO",
							  GetLang($this->_languagePrefix.'CardCodeYes') => "YES"
				),
				"multiselect" => false
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

		protected function _ConstructPostData($postData)
		{
			$ccname 		= $postData['name'];
			$cctype 		= $postData['cctype'];
			$ccissueno 		= $postData['ccissueno'];
			$ccissuedatem 	= $postData['ccissuedatem'];
			$ccissuedatey 	= $postData['ccissuedatey'];
			$ccnum 			= $postData['ccno'];
			$ccexpm 		= $postData['ccexpm'];
			$ccexpy 		= $postData['ccexpy'];
			$cccvd 			= $postData['cccvd'];

			$amount 		= number_format($this->GetGatewayAmount(),2,'.','');

			$billingDetails = $this->GetBillingDetails();

			$data['dc_logon'] 				= $this->GetValue('accountname');
			$data['dc_password'] 			= $this->GetValue('accountpassword');

			$data['dc_transaction_type'] 	= 'AUTHORIZATION_CAPTURE';
			$data['dc_version'] 			= '1.2';

			$data['dc_address'] 			= $billingDetails['ordbillstreet1'] . ' ' . $billingDetails['ordbillstreet2'];
			$data['dc_city'] 				= $billingDetails['ordbillsuburb'];
			$data['dc_state'] 				= $billingDetails['ordbillstate'];
			$data['dc_zipcode'] 			= $billingDetails['ordbillzip'];
			$data['dc_transaction_amount'] 	= $amount;

			$data['dc_name'] 				= $ccname;
			$data['dc_number'] 				= $ccnum;
			$data['dc_expiration_month'] 	= $ccexpm;
			$data['dc_expiration_year'] 	= $ccexpy;
			$data['dc_verification_number'] = $cccvd;

			return http_build_query($data);
		}

		protected function _HandleResponse($result)
		{
			$content = explode(chr(28), $result);

			foreach ($content as $key_value) {
				list ($key, $value) = explode("=", $key_value);
				$response[$key] = $value;
			}

			$responseMessage = $responseCode = '';

			if (isset($response['dc_response_code'])) {
				$responseCode = $response['dc_response_code'];
			}

			if (isset($response['dc_response_message'])) {
				$responseMessage = $response['dc_response_message'];
			}

			$successCodes = array('00', '85');

			if(in_array($responseCode, $successCodes)) {

				$updatedOrder = array(
					'ordpaymentstatus' => 'captured'
				);

				$this->UpdateOrders($updatedOrder);

				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

				return true;
			}
			else {

				if ($responseMessage == '') {
					$responseMessage = GetLang($this->_languagePrefix.'UnknownError');
				}

				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Failure'), $responseCode, $responseMessage));

				// Something went wrong, show the error message with the credit card form
				$this->SetError(GetLang($this->_languagePrefix.'SomethingWentWrong').sprintf(" %s : %s ", $responseCode, $responseMessage));
				return false;
			}
		}
	}