<?php

	class CHECKOUT_MIGS extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the MIGS checkout module
			$this->_languagePrefix = "MIGS";
			$this->_id = "checkout_MIGS";
			$this->_image = "mastercard_logo.jpg";

			parent::__construct();

			$this->requiresSSL = true;
			$this->_currenciesSupported = array('AUD');

			$this->_liveTransactionURL = 'https://migs.mastercard.com.au';
			$this->_testTransactionURL = 'https://migs.mastercard.com.au';
			$this->_liveTransactionURI = '/vpcdps';
			$this->_testTransactionURI = '/vpcdps';
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
			$this->_variables['displayname'] = array("name" => GetLang($this->_languagePrefix."DisplayName"),
			   "type" => "textbox",
			   "help" => GetLang('DisplayNameHelp'),
			   "default" => $this->GetName(),
			   "required" => true
			);

			$this->_variables['merchantid'] = array("name" => GetLang($this->_languagePrefix."MerchantId"),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'MerchantIdHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['accesscode'] = array("name" => GetLang($this->_languagePrefix."AccessCode"),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'AccessCodeHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['securehash'] = array("name" => GetLang($this->_languagePrefix."SecureHash"),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'SecureHashHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['cardcode'] = array("name" => GetLang($this->_languagePrefix."CardCode"),
			   "type" => "dropdown",
			   "help" => GetLang($this->_languagePrefix.'CardCodeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(GetLang($this->_languagePrefix.'CardCodeNo') => "NO",
							  GetLang($this->_languagePrefix.'CardCodeYes') => "YES"
				),
				"multiselect" => false
			);

			$this->_variables['testmode'] = array(
				'name' => GetLang('MIGSTestMode'),
				'type' => 'dropdown',
				'help' => GetLang('MIGSTestModeHelp'),
				'default' => 'no',
				'required' => true,
				'options' => array(
					GetLang('MIGSTestModeNo') => 'NO',
					GetLang('MIGSTestModeYes') => 'YES'
				),
				'multiselect' => false
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

			$merchantId = $this->GetValue("merchantid");
			$chargeAmount = $this->GetGatewayAmount();

			if ($this->GetValue('testmode') == 'YES') {
				$merchantId = 'TEST' . $merchantId;
				$chargeAmount = 1;
				$ccnum = '4005550000000001';
				$ccexpm = '05';
				$ccexpy = '13';
			}

			// MIGS accepts payments in cents
			$amount = number_format($chargeAmount * 100, 0, '','');

			$post['vpc_Version'] 		= 1;
			$post['vpc_Command'] 		= 'pay';
			$post['vpc_MerchTxnRef'] 	= $transactionid;
			$post['vpc_AccessCode'] 	= $this->GetValue("accesscode");

			$post['vpc_Merchant'] 		= $merchantId;

			$post['vpc_Locale']			= 'en';
			$post['vpc_OrderInfo']		= $transactionid;
			$post['vpc_Amount'] 		= $amount;
			$post['vpc_CardNum']		= $ccnum;
			$post['vpc_CardExp']		= $ccexpy . $ccexpm;

			if ($this->GetValue('cardcode') == 'YES') {
				$post['vpc_CardSecurityCode'] = $cccvd;
			}

			return http_build_query($post);
		}

		protected function _HandleResponse($response)
		{
			$result = array();
			parse_str($response, $result);

			$trnId = $result['vpc_TransactionNo'];

			$responseCode = $result['vpc_TxnResponseCode'];
			$responseMessage = $result['vpc_Message'];

			if($responseCode == '0') {

				$updatedOrder = array(
					'ordpayproviderid' => $trnId,
					'ordpaymentstatus' => 'captured'
				);

				$this->UpdateOrders($updatedOrder);

				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

				return true;
			}
			else {

				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Failure'), $responseCode, $responseMessage));

				// Something went wrong, show the error message with the credit card form
				$this->SetError(GetLang($this->_languagePrefix."SomethingWentWrong").sprintf("%s : %s", $responseCode, $responseMessage));
				return false;
			}
		}
	}