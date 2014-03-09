<?php

	class CHECKOUT_INTELLIPAY extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the IntelliPay checkout module
			$this->_languagePrefix = "IntelliPay";
			$this->_id = "checkout_intellipay";
			$this->_image = "intellipay_logo.gif";

			parent::__construct();

			$this->requiresSSL = true;
			$this->_currenciesSupported = array('USD');
			$this->_cardsSupported = array ('VISA','AMEX','MC','DINERS','DISCOVER');

			$this->_liveTransactionURL = 'https://www.intellipay.net';
			$this->_testTransactionURL = 'https://www.intellipay.net';
			$this->_liveTransactionURI = '/LinkSmart/';
			$this->_testTransactionURI = '/LinkSmart/';
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

			$this->_variables['merchantid'] = array("name" => GetLang($this->_languagePrefix.'MerchantId'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'MerchantIdHelp'),
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
			// IntelliPay accepts payments in cents

			$ccname 		= $postData['name'];
			$cctype 		= $postData['cctype'];
			$ccissueno 		= $postData['ccissueno'];
			$ccissuedatem 	= $postData['ccissuedatem'];
			$ccissuedatey 	= $postData['ccissuedatey'];
			$ccnum 			= $postData['ccno'];
			$ccexpm 		= $postData['ccexpm'];
			$ccexpy 		= $postData['ccexpy'];
			$cccvd 			= $postData['cccvd'];

			switch ($cctype) {
				case 'VISA':
					$cctype = 'VI';
					break;
				case 'MC':
					$cctype = 'MC';
					break;
				case 'AMEX':
					$cctype = 'AX';
					break;
				case 'DINERS':
					$cctype = 'DI';
					break;
				case 'DISCOVER':
					$cctype = 'NO';
					break;
				default:
					$cctype = '0';
					break;
			}

			$amount = ($this->GetGatewayAmount());

			$billingDetails = $this->GetBillingDetails();

			$intellipaypost['ADDRESS'] = $billingDetails['ordbillstreet1'] . ' ' . $billingDetails['ordbillstreet2'];

			$intellipaypost['CARDNUM'] = $ccnum;
			$intellipaypost['CITY'] = $billingDetails['ordbillsuburb'];
			$intellipaypost['COUNTRY'] = $billingDetails['ordbillcountry'];
			$intellipaypost['STATE'] = $billingDetails['ordbillstate'];
			$intellipaypost['ZIP'] = $billingDetails['ordbillzip'];
			$intellipaypost['CUSTID'] = $billingDetails['ordbillemail'];
			$intellipaypost['AMOUNT'] = $amount;
			$intellipaypost['DELIMCHARACTER'] = ',';
			$intellipaypost['DUPECHECK'] = 'Y';
			$intellipaypost['EMAIL'] = $billingDetails['ordbillemail'];
			$intellipaypost['EXPDATE'] = $ccexpm . $ccexpy;
			$intellipaypost['INVOICE'] = $this->GetCombinedOrderId();
			$intellipaypost['LOGIN'] = $this->GetValue('merchantid');
			$intellipaypost['METHOD'] = $cctype;
			$intellipaypost['NAME'] = $billingDetails['ordbillfirstname'] . ' ' . $billingDetails['ordbilllastname'];
			$intellipaypost['PASSWORD'] = $this->GetValue('merchantpassword');
			$intellipaypost['PHONE'] = $billingDetails['ordbillphone'];
			$intellipaypost['RECEIPTFORMAT'] = 'NamedValueList';
			$intellipaypost['TYPE'] = 'NA';

			return http_build_query($intellipaypost);
		}

		protected function _HandleResponse($result)
		{
			$data = array();
			$result = str_replace("\n", '&', $result);
			parse_str($result, $data);

			$responseCode = $responseMessage = '';

			if(isset($data['RESPONSECODE']) && $data['RESPONSECODE'] == 'A') {

				$updatedOrder = array(
					'ordpayproviderid' => $data['TRANSID'],
					'ordpaymentstatus' => 'captured'
				);

				$this->UpdateOrders($updatedOrder);

				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

				return true;
			}
			else {

				$responseMessage = $data['DECLINEREASON,1,ERRORCLASS'];

				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Failure'),'', $responseMessage));

				// Something went wrong, show the error message with the credit card form
				$this->SetError(GetLang($this->_languagePrefix.'SomethingWentWrong').sprintf(" : %s", $responseMessage));
				return false;
			}
		}
	}