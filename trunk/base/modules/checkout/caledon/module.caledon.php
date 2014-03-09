<?php

	class CHECKOUT_CALEDON extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			$this->_languagePrefix 		= "Caledon";
			$this->_id 					= "checkout_caledon";
			$this->_image 				= "caledon.jpg";

			parent::__construct();

			$this->_requiresSSL 		= true;
			$this->_currenciesSupported = array('USD');
			$this->_liveTransactionURL 	= 'https://lt3a.caledoncard.com';
			$this->_testTransactionURL 	= 'https://lt3a.caledoncard.com';
			$this->_liveTransactionURI 	= '';
			$this->_testTransactionURI 	= '';
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

			$this->_variables['terminalid'] = array("name" => GetLang($this->_languagePrefix.'TerminalId'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'TerminalIdHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['operatorid'] = array("name" => GetLang($this->_languagePrefix.'OperatorId'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'OperatorIdHelp'),
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

			$transactionid	= $this->GetCombinedOrderId();
			$terminalid = $this->GetValue('terminalid');
			$operatorid = $this->GetValue('operatorid');

			$billingDetails = $this->GetBillingDetails();

			$caledonPost['TERMID'] = $terminalid;
			$caledonPost['TYPE'] = 'S';
			$caledonPost['OPERID'] = $operatorid;
			$caledonPost['CARD'] = $ccname;
			$caledonPost['EXP'] = $ccexpm.$ccexpy;
			$caledonPost['AMT'] = $this->GetGatewayAmount()*100;
			$caledonPost['CVV2'] = $cccvd;
			$caledonPost['AVS'] = $billingDetails['ordbillstreet1'] . ' ' . $billingDetails['ordbillstreet2'];
			$caledonPost['REF'] = $this->GetCombinedOrderId();

			return http_build_query($caledonPost);
		}

		protected function _HandleResponse($response)
		{
			$result = array();
			parse_str($response,$result);
			$responseCode = $responseMessage = '';

			if (isset($result['CODE'])) {
				$responseCode = $result['CODE'];
			}

			if (isset($result['TEXT'])) {
				$responseMessage = $result['TEXT'];
			}

			if ($responseCode == '1000') {
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