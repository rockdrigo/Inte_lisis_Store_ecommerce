<?php

	class CHECKOUT_PXPOST extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the PaymentExpress checkout module
			$this->_languagePrefix 		= "PaymentExpress";
			$this->_id 					= "checkout_pxpost";
			$this->_image 				= "paymentexpress.png";

			parent::__construct();

			$this->_requiresSSL 		= true;
			$this->_currenciesSupported = array('USD', 'AUD', 'NZD', 'CAD', 'EUR', 'GBP', 'JPY', 'FRF');
			$this->_liveTransactionURL 	= 'https://www.paymentexpress.com';
			$this->_testTransactionURL 	= 'https://www.paymentexpress.com';
			$this->_liveTransactionURI 	= '/pxpost.aspx';
			$this->_testTransactionURI 	= '/pxpost.aspx';
			$this->_curlSupported 		= true;
			$this->_fsocksSupported 	= true;
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

			$this->_variables['username'] = array("name" => GetLang($this->_languagePrefix.'Username'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'UsernameHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['merchantpassword'] = array("name" => GetLang($this->_languagePrefix.'MerchantPassword'),
			   "type" => "password",
			   "help" => GetLang($this->_languagePrefix.'MerchantPasswordHelp'),
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

			$currency = GetDefaultCurrency();

			$xml = '<Txn>
						<PostUsername>'.$this->GetValue('username').'</PostUsername>
						<PostPassword>'.$this->GetValue('merchantpassword').'</PostPassword>
						<CardHolderName>'.$ccname.'</CardHolderName>
						<CardNumber>'.$ccnum.'</CardNumber>
						<Amount>'.number_format($this->GetGatewayAmount(),2,'.','').'</Amount>
						<DateExpiry>'.$ccexpm.$ccexpy.'</DateExpiry>
						<Cvc2>'.$cccvd.'</Cvc2>
						<InputCurrency>'.$currency['currencycode'].'</InputCurrency>
						<TxnType>Purchase</TxnType>
						<TxnId>'.$transactionid.'</TxnId>
						<MerchantReference>'.$transactionid.'</MerchantReference>
					</Txn>';

			return $xml;
		}

		protected function _HandleResponse($result)
		{
			if (empty($result)) {
				$this->SetError($this->_languagePrefix."ErrorCommunicating");
				return false;
			}

			try {
			  $xml = @new SimpleXMLElement($result);
			} catch (Exception $e) {

				// Something went wrong, show the error message with the credit card form
				$this->SetError(GetLang($this->_languagePrefix.'SomethingWentWrong'). $result);
				return false;
			}

			$responseCode = $responseMessage = '';

			$approved 			= (string)$xml->Transaction->attributes()->responseText;
			$amount 			= (double)$xml->Transaction->Amount;
			$responseCode 		= (string)$xml->Transaction->CardHolderResponseText;
			$responseMessage 	= (string)$xml->Transaction->CardHolderHelpText;

			if ($approved == 'APPROVED') {

				$updatedOrder = array(
					'ordpayproviderid' => (string)$xml->Transaction->DpsTxnRef,
					'ordpaymentstatus' => 'captured'
				);

				$this->UpdateOrders($updatedOrder);

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