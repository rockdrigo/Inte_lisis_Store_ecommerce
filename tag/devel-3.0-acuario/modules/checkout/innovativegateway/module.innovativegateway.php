<?php

	class CHECKOUT_INNOVATIVEGATEWAY extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the InnovativeGateway checkout module
			$this->_languagePrefix = "InnovativeGateway";
			$this->_id = "checkout_innovativegateway";
			$this->_image = "intuit_logo.png";

			parent::__construct();

			$this->requiresSSL = true;
			$this->_currenciesSupported = array('USD');
			$this->_cardsSupported = array ('VISA','AMEX','MC','DINERS','DISCOVER');

			$this->_liveTransactionURL = 'https://transactions.innovativegateway.com';
			$this->_testTransactionURL = 'https://transactions.innovativegateway.com';
			$this->_liveTransactionURI = '/servlet/com.gateway.aai.Aai';
			$this->_testTransactionURI = '/servlet/com.gateway.aai.Aai';
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

			$orderid = $this->GetCombinedOrderId();

			$igpostdata['target_app']						= 'WebCharge_v5.06';
			$igpostdata['response_mode']					= 'simple';
			$igpostdata['response_fmt']						= 'delimited';
			$igpostdata['username']							= $this->GetValue('accountname');
			$igpostdata['pw'] 								= $this->GetValue('accountpassword');
			$igpostdata['upg_auth'] 						= 'zxcvlkjh';
			$igpostdata['delimited_fmt_field_delimiter'] 	= '=';
			$igpostdata['delimited_fmt_include_fields'] 	= 'true';
			$igpostdata['delimited_fmt_value_delimiter']	= '&';
			$igpostdata['trantype'] 						= 'sale';
			$igpostdata['reference']						= '';
			$igpostdata['trans_id']							= '';
			$igpostdata['authamount']						= '';
			$igpostdata['fulltotal']						= $amount;
			$igpostdata['cardtype']							= strtolower($cctype);
			$igpostdata['ccname']							= $ccname;
			$igpostdata['ccnumber']							= $ccnum;
			$igpostdata['month']							= $ccexpm;
			$igpostdata['year']								= $ccexpy;
			$igpostdata['ordernumber']						= $orderid;
			$igpostdata['bphone']							= $billingDetails['ordbillphone'];
			$igpostdata['email']							= $billingDetails['ordbillemail'];
			$igpostdata['baddress']							= $billingDetails['ordbillstreet1'];
			$igpostdata['baddress1']						= $billingDetails['ordbillstreet2'];
			$igpostdata['bcity']							= $billingDetails['ordbillsuburb'];
			$igpostdata['bstate']							= $billingDetails['ordbillstate'];
			$igpostdata['bzip']								= $billingDetails['ordbillzip'];
			$igpostdata['bcountry']							= $billingDetails['ordbillcountry'];

			if ($this->_testmode) {
				$igpostdata['test_override_errors'] = '1';
			}

			return http_build_query($igpostdata);
		}

		protected function _HandleResponse($result)
		{
			$response = array();
			$result = parse_str($result, $response);

			$approval = $responseMessage = '';

			if (isset($response['approval'])) {
				$approval = $response['approval'];
			}

			if (isset($response['error'])) {
				$responseMessage = $response['error'];
			}

			if($responseMessage == '' && $approval != '') {

				$transactionid 		= $response['anatransid'];
				$ordernumber 		= $response['ordernumber'];
				$amount 			= $response['amount'];

				if ($ordernumber != $this->GetCombinedOrderId()) {
					// Something went wrong, show the error message with the credit card form
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'OrderIdMismatch'), sprintf("Order Id sent %s was not the same as the order id recieved %s", $this->GetCombinedOrderId(), $ordernumber));
					$this->SetError(GetLang($this->_languagePrefix.'OrderIdMismatch'));
					return false;
				}

				if ($this->GetGatewayAmount() != $amount) {
					// Something went wrong, show the error message with the credit card form
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'PaymentMismatch'), sprintf("Amount sent %s was not the same as the amount recieved %s", $this->GetGatewayAmount(), $amount));
					$this->SetError(GetLang($this->_languagePrefix.'PaymentMismatch'));
					return false;
				}

				$updatedOrder = array(
					'ordpayproviderid' => $transactionid,
					'ordpaymentstatus' => 'captured',
				);

				$this->UpdateOrders($updatedOrder);

				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

				return true;
			}
			else {

				if ($responseMessage == '') {
					$responseMessage = GetLang($this->_languagePrefix.'UnknownError');
				}

				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Failure'), '', $responseMessage));

				// Something went wrong, show the error message with the credit card form
				$this->SetError(GetLang($this->_languagePrefix.'SomethingWentWrong').sprintf(" : %s ", $responseMessage));
				return false;
			}
		}
	}