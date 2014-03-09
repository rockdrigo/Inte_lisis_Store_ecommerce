<?php

	class CHECKOUT_DIRECTONE extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the BeanStream checkout module

			$this->_languagePrefix = "DirectOne";
			$this->_id = "checkout_directone";
			$this->_image = "directone_logo.gif";
			parent::__construct();

			$this->requiresSSL = true;
			$this->_currenciesSupported = array("AUD", "USD", "GBP");
			$this->_liveTransactionURL = 'https://vault.safepay.com.au';
			$this->_testTransactionURL = 'https://vault.safepay.com.au';
			$this->_liveTransactionURI = '/cgi-bin/direct_process.pl';
			$this->_testTransactionURI = '/cgi-bin/direct_test.pl';
			$this->_curlSupported = false;
			$this->_fsocksSupported = true;

			$this->debug = '';
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

			$this->_variables['password'] = array("name" => GetLang($this->_languagePrefix.'MerchantPassword'),
			   "type" => "password",
			   "help" => GetLang($this->_languagePrefix.'MerchantPasswordHelp'),
			   "default" => "",
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

		protected function _ConstructPostData($postData)
		{
			$transactionid = $this->GetCombinedOrderId();

			$ccname = $postData['name'];
			$cctype = $postData['cctype'];

			$ccissueno = $postData['ccissueno'];
			$ccissuedatem = $postData['ccissuedatem'];
			$ccissuedatey = $postData['ccissuedatey'];

			$ccnum = $postData['ccno'];
			$ccexpm = $postData['ccexpm'];
			$ccexpy = $postData['ccexpy'];
			$cccvd = $postData['cccvd'];

			$order_desc = sprintf(GetLang('YourOrderFrom'), $GLOBALS['StoreName']);

			$billingDetails = $this->GetBillingDetails();

			$province = '--';

			$ccemail = $billingDetails['ordbillemail'];

			if (empty($ccemail)) {
				// Get the customer's email address
				$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
				$ccemail = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerEmailAddress();
			}

			// Contstruct the POST data
			$directone_post['vendor_name'] = $this->GetValue('merchantid');
			$directone_post['vendor_password'] = $this->GetValue('password');
			$directone_post['card_number'] = $ccnum;
			$directone_post['card_expiry'] = $ccexpm.$ccexpy;
			$directone_post['card_holder'] = $ccname;
			$directone_post['payment_amount'] = $this->GetGatewayAmount();
			$directone_post['payment_reference'] = $transactionid;

			return http_build_query($directone_post);
		}

		protected function _HandleResponse($response)
		{	$result = array();
			$var_pairs = explode("\n",$response);

			foreach($var_pairs as $var_pair)
			{
				if (!$var_pair) {
					continue; // skip blank lines
				}
				$var_pair = explode('=',$var_pair);
				$key = $var_pair[0];
				$value = $var_pair[1];
				$result[$key] = $value;
			}

			$approved = false;
			$trnAmount = -1;
			$trnId = -1;

			if (isset($result['response_code'])) {
				$approved = $result['response_code'] == '00';
			}

			if (isset($result['bank_reference'])) {
				$trnId = $result['bank_reference'];
			}

			if($approved) {

				// The transaction was successful, make sure it was for the right amount
				$order_total = $this->GetGatewayAmount();

				settype($order_total, "double");
				settype($trnAmount, "double");

				if ($trnId != -1) {
					// Save the authorization key
					$updatedOrder = array(
						'ordpayproviderid' => $trnId,
						'ordpaymentstatus' => 'captured',
					);

					$this->UpdateOrders($updatedOrder);

					$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

					return true;
				}
				else {
					$this->SetError(GetLang($this->_languagePrefix.'PaymentMismatch'));
					return false;
				}
			}
			else {

				$responseCode = $responseMessage = 'Undefined';

				if (isset($result['response_code'])) {
					$responseCode = $result['response_code'];
				}

				if (isset($result['response_text'])) {
					$responseMessage = $result['response_text'];
				}

				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Failure'), $responseCode, $responseMessage));
				// Something went wrong, show the error message with the credit card form
				$this->SetError(GetLang($this->_languagePrefix."SomethingWentWrong").$this->debug.sprintf(" %s : %s", $responseCode, $responseMessage));
				return false;
			}
		}
	}
