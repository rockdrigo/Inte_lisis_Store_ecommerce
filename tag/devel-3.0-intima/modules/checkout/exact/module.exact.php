<?php

	class CHECKOUT_EXACT extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the Paymentech checkout module
			$this->_languagePrefix = "Exact";
			$this->_id = "checkout_exact";
			$this->_image = "exact_logo.png";

			parent::__construct();

			$this->requiresSSL = true;
			$this->_currenciesSupported = array('USD', 'CAD');
		//	$this->_cardsSupported = array ('VISA','AMEX','MC','DINERS','DISCOVER');

			$this->_liveTransactionURL = 'https://secure2.e-xact.com';
			$this->_testTransactionURL = 'https://secure2.e-xact.com';
			$this->_liveTransactionURI = '/vplug-in/transaction/rpc-enc/Service.asmx?wsdl';
			$this->_testTransactionURI = '/vplug-in/transaction/rpc-enc/Service.asmx?wsdl';
			$this->_curlSupported = true;
			$this->_fsocksSupported = true;
			$this->requiresSoap = true;
			$this->cardCodeRequired = true;
			$this->soapAction = 'SendAndCommit';
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

			$this->_variables['username'] = array("name" => GetLang($this->_languagePrefix.'UserName'),
				 "type" => "textbox",
				 "help" => GetLang($this->_languagePrefix.'UserNameHelp'),
				 "default" => "",
				 "required" => true
			);

			$this->_variables['password'] = array("name" => GetLang($this->_languagePrefix.'Password'),
				 "type" => "password",
				 "help" => GetLang($this->_languagePrefix.'PasswordHelp'),
				 "default" => "",
				 "required" => true
			);

			$this->_variables['transtype'] = array("name" => GetLang($this->_languagePrefix.'TransactionType'),
				 "type" => "dropdown",
				 "help" => GetLang($this->_languagePrefix.'TransactionTypeHelp'),
				 "default" => "no",
				 "required" => true,
				 "options" => array(GetLang($this->_languagePrefix.'TransactionTypeSale') => "SALE",
								GetLang($this->_languagePrefix.'TransactionTypeAuth') => "AUTH"
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

			$ccvpresenceind = 0;

			if ($cccvd != '') {
				$ccvpresenceind = 1;
			}

			$amount = number_format($this->GetGatewayAmount(),2,'.','');

			$billingDetails = $this->GetBillingDetails();

			$exactId = $this->GetValue('username');
			$password = $this->GetValue('password');

			if ($this->GetValue('transtype') == "SALE") {
				$transtype = '00';
			}
			else {
				$transtype = '01';
			}

			$xml = array(
				"ExactID"=>$exactId,
				"Password"=>$password,
				"Transaction_Type"=>$transtype,
				"DollarAmount"=>$amount,
				"Card_Number"=>$ccnum,
				"Expiry_Date"=>$ccexpm.$ccexpy,
				"CardHoldersName"=>$ccname,
				"VerificationStr2"=>$cccvd,
				"CVD_Presence_Ind"=>$ccvpresenceind,
				"Language"=>"en",
				"Client_Email"=>$billingDetails['ordbillemail']
			);

			return array('gatewayData' => array($xml), 'soapAction' => $this->soapAction);
		}

		protected function _HandleResponse($result)
		{
			$responseCode = $responseMessage = $trnId = '';

			if (isset($result['Transaction_Tag'])) {
				$trnId = $result['Transaction_Tag'];
			}

			if (isset($result['Bank_Resp_Code'])) {
				$responseCode = $result['Bank_Resp_Code'];
			} else if(isset($result['EXact_Resp_Code'])) {
				$responseCode = $result['EXact_Resp_Code'];
			}

			if (isset($result['Bank_Message'])) {
				$responseMessage = $result['Bank_Message'];
			} else if(isset($result['EXact_Message'])) {
				$responseMessage = $result['EXact_Message'];
			}

			if(isset($result['Transaction_Approved']) && $result['Transaction_Approved'] == 1) {

				if ($trnId != '') {

					if($this->GetValue('transtype') == 'SALE') {
						$paymentStatus = 'captured';
					}
					else {
						$paymentStatus = 'authorized';
					}

					$updatedOrder = array(
						'ordpayproviderid' => $trnId,
						'ordpaymentstatus' => 'captured',
					);

					$this->UpdateOrders($updatedOrder);
				}

				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

				return true;
			}
			else {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Failure'), $responseCode, $responseMessage));
				// Something went wrong, show the error message with the credit card form
				$this->SetError(GetLang($this->_languagePrefix.'SomethingWentWrong').sprintf(" %s : %s ", $responseCode, $responseMessage));
				return false;
			}
		}
	}