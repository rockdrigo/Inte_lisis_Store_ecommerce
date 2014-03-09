<?php

	class CHECKOUT_PAYSTATION extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the PayJunction checkout module
			$this->_languagePrefix = "PayStation";
			$this->_id = "checkout_paystation";
			$this->_image = "logo_med.jpg";

			parent::__construct();

			$this->requiresSSL = true;
			$this->_currenciesSupported = array('NZD');
			$this->_cardsSupported = array ('VISA','MC','AMEX');

			$this->_liveTransactionURL = 'https://www.paystation.co.nz';
			$this->_testTransactionURL = 'https://www.paystation.co.nz';
			$this->_liveTransactionURI = '/direct/paystation.dll?paystation';
			$this->_testTransactionURI = '/direct/paystation.dll?paystation';
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

			$this->_variables['accountid'] = array("name" => GetLang($this->_languagePrefix.'AccountId'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'AccountIdHelp'),
			   "default" => "",
			   "required" => true
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

			$amount = number_format($this->GetGatewayAmount(),2,'.','') * 100;

			switch ($cctype) {

				case 'VISA':
					$cardtype = 'visa';
				case 'MC':
					$cardtype = 'mastercard';
				case 'AMEX':
					$cardtype = 'amex';
			}


			$billingDetails = $this->GetBillingDetails();

			$orderid = $this->GetCombinedOrderId();

			$data["am"] 			= $amount;
			$data["pi"] 			= $this->GetValue("accountid");
			$data["ms"] 			= $orderid;
			$data["2pty"] 			= 'T';
			$data["no_redirect"]	= 'T';
			$data["ct"] 			= $cardtype;
			$data["cardno"] 		= $ccnum;
			$data['cardexp']		= $ccexpy . $ccexpm;

			return http_build_query($data);
		}

		protected function _HandleResponse($result)
		{
			try {
			  $xml = @new SimpleXMLElement($result);
			} catch (Exception $e) {

				// Something went wrong, show the error message with the credit card form
				$this->SetError(GetLang($this->_languagePrefix.'SomethingWentWrong'). $result);
				return false;
			}

			$responseMessage = $responseCode = '';

			if ($xml->ec) {
				$responseCode = (string)$xml->ec;
			}

			if ($xml->em) {
				$responseMessage = (string)$xml->em;
			}

			if($responseCode == '0') {

				$updatedOrder = array(
					'ordpayproviderid' => (string)$xml->TransactionID,
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