<?php

	class CHECKOUT_CYNERGYDATA extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the CynergyData checkout module
			$this->_languagePrefix = "CynergyData";
			$this->_id = "checkout_cynergydata";
			$this->_image = "cynergy.jpg";

			parent::__construct();

			$this->requiresSSL = true;
			$this->_currenciesSupported = array('USD');
			$this->_cardsSupported = array ('VISA','AMEX','MC','DINERS','DISCOVER', 'JCB');

			$this->_liveTransactionURL = 'https://payments.cynergydata.com';
			$this->_testTransactionURL = 'https://payments.cynergydata.com';
			$this->_liveTransactionURI = '/SmartPayments/transact2.asmx?WSDL';
			$this->_testTransactionURI = '/SmartPayments/transact2.asmx?WSDL';
			$this->_curlSupported = false;
			$this->_fsocksSupported = false;
			$this->requiresSoap = true;
			$this->cardCodeRequired = true;
			$this->soapAction = 'ProcessCreditCard';

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

			$this->_variables['authcode'] = array("name" => GetLang($this->_languagePrefix.'Authcode'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'AuthcodeHelp'),
			   "default" => "",
			   "required" => false
			);

			$this->_variables['transtype'] = array("name" => GetLang($this->_languagePrefix.'TransactionType'),
			   "type" => "dropdown",
			   "help" => GetLang($this->_languagePrefix.'TransactionTypeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(GetLang($this->_languagePrefix.'TransactionTypeSale') => "SALE",
							  GetLang($this->_languagePrefix.'TransactionTypeAuth') => "AUTH",
							  GetLang($this->_languagePrefix.'TransactionTypeForced') => "FORCED"
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

			$username = $this->GetValue('username');
			$password = $this->GetValue('password');

			if ($this->GetValue('transtype') == "SALE") {
				$transtype = 'sale';
			}
			else if ($this->GetValue('transtype') == "AUTH") {
				$transtype = 'auth';
			}
			else {
				$transtype = 'force';
			}

			$extData = '';
			$authcode = $this->GetValue('authcode');
			if (!empty($authcode)) {
				$extData = '<AuthCode>'.$authcode.'</AuthCode>';
			}

			$xml = array('UserName'=>$username,
						'Password'=>$password,
						'TransType'=>$transtype,
						'CardNum'=>$ccnum,
						'ExpDate'=>$ccexpm.$ccexpy,
						'MagData'=>'',
						'NameOnCard'=>$ccname,
						'Amount'=> $amount,
						'InvNum'=>$this->GetCombinedOrderId(),
						'PNRef'=>'',
						'Zip'=>$billingDetails['ordbillzip'],
						'Street'=>$billingDetails['ordbillstreet1'] . ' ' . $billingDetails['ordbillstreet2'],
						'CVNum'=>$cccvd,
						'ExtData'=>$extData);


			return array('gatewayData' => $xml, 'soapAction' => $this->soapAction);
		}

		protected function _HandleResponse($result)
		{
			if (isset($result['ProcessCreditCardResult'])) {
				$result = $result['ProcessCreditCardResult'];
			}
			else {
				$this->SetError(GetLang($this->_languagePrefix.'SomethingWentWrong'). $result);
				return false;
			}

			$responseCode = $responseMessage = $trnId = '';

			if (isset($result['PNRef'])) {
				$trnId = $result['PNRef'];
			}

			if (isset($result['Result'])) {
				$responseCode = $result['Result'];
			}

			if (isset($result['RespMSG'])) {
				$responseMessage = $result['RespMSG'];
			}

			if($responseCode == 0 && $responseMessage == 'Approved' && $result['Message'] == 'APPROVED') {

				if ($trnId != '') {

					if($this->GetValue('transtype') == 'SALE' || $this->GetValue('transtype') == 'FORCED') {
						$paymentStatus = 'captured';
					}
					else {
						$paymentStatus = 'authorized';
					}

					if ($this->GetValue('transtype') == "SALE") {
						$status = 'captured';
					}
					else if ($this->GetValue('transtype') == "AUTH") {
						$status = 'authorized';
					}
					else {
						$status = 'forced';
					}

					$updatedOrder = array(
						'ordpayproviderid' => $trnId,
						'ordpaymentstatus' => $status,
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