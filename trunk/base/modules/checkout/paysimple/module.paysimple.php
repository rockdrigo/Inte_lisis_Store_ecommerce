<?php

	require_once('lib/paysimple.php');

	class CHECKOUT_PAYSIMPLE extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			$this->_languagePrefix 		= "PaySimple";
			$this->_id 					= "checkout_paysimple";
			$this->_image 				= "paysimple.jpg";

			parent::__construct();

			$this->_requiresSSL 		= true;
			$this->_currenciesSupported = array('USD', 'AUD', 'NZD', 'CAD', 'EUR', 'GBP', 'JPY', 'FRF');
			$this->_liveTransactionURL 	= 'https://www.paysimple.com';
			$this->_testTransactionURL 	= 'https://sandbox.paysimple.com';
			$this->_liveTransactionURI 	= '/Gateway.asmx?wsdl';
			$this->_testTransactionURI 	= '/Gateway.asmx?wsdl';
			$this->_curlSupported 		= false;
			$this->_fsocksSupported 	= false;
			$this->cardCodeRequired 	= true;
			$this->requiresSoap = true;
			$this->cardCodeRequired = true;
			$this->soapAction = 'ProcessCreditCard';
			$this->_help = sprintf(GetLang($this->_languagePrefix.'Help'), GetConfig('AppPath').'/modules/paysimple/lib/');	// Help Message
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

			$this->_variables['merchantkey'] = array("name" => GetLang($this->_languagePrefix.'MerchantKey'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'MerchantKeyHelp'),
			   "required" => true
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

		public function getMerchantKey()
		{
			$key = $this->GetValue('merchantkey');

			if (is_null($key) || trim($key) == '') {
				return false;
			}

			return $key;
		}

		public function setMerchantKey($newKey)
		{
			if (trim($newKey) == '') {
				return false;
			}

			$settings = $this->moduleVariables;
			$settings['merchantkey'] = trim($newKey);

			return $this->SaveModuleSettings($settings, true);
		}

		public function ProcessPaymentForm($data = array())
		{
			$postData = $this->_Validate($data);

			if ($postData === false) {
				return false;
			}

			// Is setup in test or live mode?
			$this->_testmode = $this->GetValue("testmode") == "YES";

			// PaySimple accepts payments in cents

			$ccname 		= $postData['name'];
			$cctype 		= $postData['cctype'];

			$ccissueno 		= $postData['ccissueno'];
			$ccissuedatem 	= $postData['ccissuedatem'];
			$ccissuedatey 	= $postData['ccissuedatey'];

			$ccnum 			= $postData['ccno'];
			$ccexpm 		= $postData['ccexpm'];
			$ccexpy 		= $postData['ccexpy'];
			$cccvd 			= $postData['cccvd'];

			$billingDetails = $this->GetBillingDetails();

			$testmode = ($this->GetValue('testmode') == 'YES');
			$message = '';

			try{
				$gateway = new Gateway();
				$gateway->production = !$testmode;

				$dynamicKey = new DynamicKey($this, $gateway);

				//Create Customer
				$customer = new Customer();
				$BillingAddress = new Address();
				$BillingAddress->AddressLine1 	= $billingDetails['ordbillstreet1'] . ' ' . $billingDetails['ordbillstreet2'];
				$BillingAddress->City 			= $billingDetails['ordbillsuburb'];
				$BillingAddress->ZipCode 		= $billingDetails['ordbillzip'];
				$customer->BillingAddress 		= $BillingAddress;
				$customer->BillingCountryName 	= GetCountryISO3ById($billingDetails['ordbillcountryid']);
				$customer->ShippingAddress 		= $BillingAddress;

				$contact = new Contact();
				$contact->EMail 	= $billingDetails['ordbillemail'];
				$contact->Phone1	= $billingDetails['ordbillphone'];

				$name = new Name();
				$name->FirstName	= $billingDetails['ordbillfirstname'];
				$name->LastName		= $billingDetails['ordbilllastname'];
				$contact->Name		= $name;

				$customer->Contact = $contact;
				$customer = $gateway->AddCustomer($dynamicKey->key, $customer);

				if ($gateway->isError()) {
					$message = $gateway->getErrorMessage();
				}

				$account = new CustomerAccountDTO();
				$account->IsCreditCard 		= true;
				$account->CreditCardNo 		= $ccnum;
				$account->CCExpiry 			= "20".$ccexpy."-".$ccexpm."-01T00:00:00";
				$account->CCType 			= $cctype;
				$account->CustomerId 		= $customer;
				$account->CardName 			= $ccname;

				if (!$gateway->isError()) {
					$account = $gateway->AddAccount($dynamicKey->key, $account);
				}
				if ($gateway->isError()) {
					$message = $gateway->getErrorMessage();
				}

				if (isset($account->Id)) {

					$PSpayment = new Payment();
					if ($testmode) {
						$PSpayment->Amount = 120;
					}
					else {
						$PSpayment->Amount = $this->GetGatewayAmount();
					}
					$PSpayment->CustomerId = $customer;
					$PSpayment->FromAccountId = $account->Id;

					$PSpayment->PaymentTypeCode = "CC";
					$PSpayment->PaymentSubTypeCode = "MOTO";

					if (!$gateway->isError()) {
						$PSpayment = $gateway->MakePayment($dynamicKey->key, $PSpayment, null);
					}
					if ($gateway->isError()) {
						$message = $gateway->getErrorMessage();
					}
				}
				else {
					$PSpayment = $account;
				}

			}
			catch (Exception $e) {
				$message = $e;
			}

			return $this->_HandleResponse($PSpayment, $message);
		}

		protected function _HandleResponse($response, $message)
		{
			$responseCode = $responseMessage = '';

			if (isset($response->faultstring)) {
				// There was an error
				$response = explode("Server stack trace", $response->faultstring);
				$response = explode("System.ServiceModel.FaultException", $response[0]);
				$response = explode("(Fault Detail is equal to An ExceptionDetail", $response[1]);
				$responseMessage = $response[0];
			}
			else {
				$responseCode = $response->Status;
			}

			if ($responseCode == 'Authorized') {

				$updatedOrder = array(
					'ordpayproviderid' => $response->Id,
					'ordpaymentstatus' => 'captured'
				);

				$this->UpdateOrders($updatedOrder);

				$this->SetPaymentStatus(PAYMENT_STATUS_PAID);

				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

				return true;
			}
			else {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Failure'), '', $responseMessage));
				$this->SetError(GetLang($this->_languagePrefix.'SomethingWentWrong').sprintf(" %s ", $responseMessage));
				return false;
			}
		}
	}
