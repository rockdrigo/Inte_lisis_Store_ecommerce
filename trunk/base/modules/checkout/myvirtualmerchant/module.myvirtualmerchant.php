<?php

	class CHECKOUT_MYVIRTUALMERCHANT extends ISC_GENERIC_CREDITCARD
	{

		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the MyVirtualMerchant checkout module
			$this->_languagePrefix = "MyVirtualMerchant";
			$this->_id = "checkout_myvirtualmerchant";
			$this->_image = "vm_logo.jpg";
			parent::__construct();
			$this->_cardsSupported = array ('VISA','AMEX','MC', 'DISCOVER');
			$this->_currenciesSupported = array("GBP","EUR","USD","AUD","JPY","CAD");

			$this->requiresSSL = true;
			$this->_liveTransactionURL = 'https://www.myvirtualmerchant.com';
			$this->_testTransactionURL = 'https://www.myvirtualmerchant.com';
			$this->_liveTransactionURI = '/VirtualMerchant/process.do';
			$this->_testTransactionURI = '/VirtualMerchant/process.do';
			$this->_curlSupported = true;
			$this->_fsocksSupported = true;
			$this->cardCodeRequired = true;
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

			$this->_variables['userid'] = array("name" => GetLang($this->_languagePrefix.'UserId'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'UserIdHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['merchantpin'] = array("name" => GetLang($this->_languagePrefix.'MerchantPin'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'MerchantPinHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['testmode'] = array(
				"name" => GetLang('MyVirtualMerchantTestMode'),
				"type" => "dropdown",
				"help" => GetLang('MyVirtualMerchantTestModeHelp'),
				"default" => "no",
				"required" => true,
				"options" => array(
					GetLang('MyVirtualMerchantTestModeNo') => "NO",
					GetLang('MyVirtualMerchantTestModeYes') => "YES"
				),
				"multiselect" => false
			);
		}


		protected function _ConstructPostData($postData)
		{
			$billingDetails = $this->GetBillingDetails();

			$ccname 		= $postData['name'];
			$ccissueno 		= $postData['ccissueno'];
			$ccissuedatem 	= $postData['ccissuedatem'];
			$ccissuedatey 	= $postData['ccissuedatey'];
			$ccnum 			= $postData['ccno'];
			$ccexpm 		= $postData['ccexpm'];
			$ccexpy 		= $postData['ccexpy'];
			$cccvd 			= $postData['cccvd'];

			$amount = ($this->GetGatewayAmount());

			$order_desc = sprintf(GetLang('YourOrderFrom'), $GLOBALS['StoreName']);

			$myvPostData['ssl_merchant_id'] 			= $this->GetValue('merchantid');
			$myvPostData['ssl_pin']			 			= $this->GetValue('merchantpin');
			$myvPostData['ssl_user_id'] 				= $this->GetValue('userid');
			$myvPostData['ssl_card_number'] 			= $ccnum;
			$myvPostData['ssl_exp_date'] 				= $ccexpm.$ccexpy;
			$myvPostData['ssl_customer_code']			= $this->getCombinedOrderId();

			$myvPostData['ssl_cvv2cvc2_indicator'] 	= '1';
			$myvPostData['ssl_cvv2cvc2'] 			= $cccvd;
			$myvPostData['ssl_avs_zip'] 			= $billingDetails['ordbillzip'];
			$myvPostData['ssl_avs_address'] 		= substr($billingDetails['ordbillstreet1'], 0, 20);
			$myvPostData['ssl_address2'] 			= substr($billingDetails['ordbillstreet2'], 0, 20);
			$myvPostData['ssl_city'] 				= $billingDetails['ordbillsuburb'];
			$myvPostData['ssl_state'] 				= $billingDetails['ordbillstate'];
			$myvPostData['ssl_avs_zip'] 			= $billingDetails['ordbillzip'];
			$myvPostData['ssl_country'] 			= $billingDetails['ordbillcountry'];

			$myvPostData['ssl_transaction_type'] 		= 'ccsale';
			$myvPostData['ssl_amount']					= number_format($this->GetGatewayAmount(), 2, '.', '');
			$myvPostData['ssl_salestax'] 				= $this->getTaxCost(true);

			$myvPostData['ssl_show_form'] 				= 'false';
			$myvPostData['ssl_receipt_link_method'] 	= 'post';
			$myvPostData['ssl_result_format'] 			= 'ascii';

			$test_mode = $this->GetValue("testmode");

			if($test_mode == 'YES') {
				$myvPostData['ssl_test_mode'] = 'true';
			} else {
				$myvPostData['ssl_test_mode'] = 'false';
			}

			return http_build_query($myvPostData);
		}

		protected function _HandleResponse($response)
		{
			$result = array();

			$response = str_replace("\n", "&", $response);

			parse_str($response, $result);

			$responseCode = $responseMessage = $ssl_result = '';

			if (isset($result['ssl_result'])) {
				$ssl_result = $result['ssl_result'];
			}

			if (isset($result['errorCode'])) {
				$responseCode = $result['errorCode'];
			}
			if (isset($result['errorMessage'])) {
				$responseMessage = $result['errorMessage'];
			}

			if ($responseMessage == '' && isset($result['ssl_result_message'])) {
				$responseMessage = $result['ssl_result_message'];
			}

			if($ssl_result == '0') {
				$updatedOrder = array(
					'ordpayproviderid' => $result['ssl_txn_id'],
					'ordpaymentstatus' => 'captured'
				);

				$this->UpdateOrders($updatedOrder);

				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

				return true;
			}
			else {

				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Failure'), $responseCode, $responseMessage));

				// Something went wrong, show the error message with the credit card form
				if ($responseCode === '') {
					// no response code detected so this is most likely a communication error of some sorts
					$error = GetLang($this->_languagePrefix.'SomethingWentWrong');
				} else {
					// treat all error codes as "declined" as far as the customer is concerned (the true error is logged above)
					$error = GetLang($this->_languagePrefix.'Declined');
				}

				$this->SetError($error);
				return false;
			}
		}
	}
