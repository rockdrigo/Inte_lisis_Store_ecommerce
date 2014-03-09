<?php

	class CHECKOUT_QUANTUMGATEWAY extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the Quantum Gateway checkout module
			$this->_languagePrefix = "QuantumGateway";
			$this->_id = "checkout_quantumgateway";
			$this->_image = "logo.gif";

			parent::__construct();

			$this->requiresSSL = true;
			$this->_currenciesSupported = array('USD');
			$this->_cardsSupported = array ('VISA','AMEX','MC','DISCOVER');

			$this->_liveTransactionURL = 'https://secure.quantumgateway.com';
			$this->_testTransactionURL = 'https://secure.quantumgateway.com';
			$this->_liveTransactionURI = '/cgi/xml.php';
			$this->_testTransactionURI = '/cgi/xml.php';
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

			$amount = number_format($this->GetGatewayAmount(),2,'.','');

			$billingDetails = $this->GetBillingDetails();

			$xml = 'xml=<QGWRequest>
						<Details>
							<gwLogin>'.$this->GetValue('accountname').'</gwLogin>
							<RestrictKey>'.$this->GetValue('accountpassword').'</RestrictKey>
							<ResponseType>Response</ResponseType>
						</Details>
						<TransactionRequest>
							<trans_method>CC</trans_method>
							<ccnum>'.$ccnum.'</ccnum>
							<ccmo>'.$ccexpm.'</ccmo>
							<ccyr>'.$ccexpy.'</ccyr>';

			if ($this->GetValue('cardcode')) {

				$xml .= '<CVV2>'.$cccvd.'</CVV2>
							<CVVtype>1</CVVtype>';

			}


			$xml .= '		<FNAME>'.$billingDetails['ordbillfirstname'].'</FNAME>
							<LNAME>'.$billingDetails['ordbilllastname'].'</LNAME>
							<BCUST_EMAIL>'.$billingDetails['ordbillemail'].'</BCUST_EMAIL>
							<phone>'.$billingDetails['ordbillphone'].'</phone>
							<override_email_customer>N</override_email_customer>
							<override_trans_email>N</override_trans_email>
							<amount>'.$amount.'</amount>
							<company>'.$billingDetails['ordbillcompany'].'</company>
							<BADDR1>'.$billingDetails['ordbillstreet1'].' '.$billingDetails['ordbillstreet2'].'</BADDR1>
							<BZIP1>'.$billingDetails['ordbillzip'].'</BZIP1>
							<BCITY>'.$billingDetails['ordbillsuburb'].'</BCITY>
							<BSTATE>'.$billingDetails['ordbillstate'].'</BSTATE>
							<BCOUNTRY>'.$billingDetails['ordbillcountry'].'</BCOUNTRY>
						</TransactionRequest>
					</QGWRequest>';

			return $xml;
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

			$responseCode = '';

			if (isset($xml->Request->Response)) {
				$responseCode = (string)$xml->Request->Response;
			}

			if($responseCode == 'APPROVED') {
				// The order is valid, hook back into the checkout system's validation process
				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

				return true;
			}
			else {
				// Something went wrong, show the error message with the credit card form
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Failure'), $responseCode, ''));
				$this->SetError(GetLang($this->_languagePrefix.'SomethingWentWrong').sprintf(" %s ", $responseCode));
				return false;
			}
		}
	}
