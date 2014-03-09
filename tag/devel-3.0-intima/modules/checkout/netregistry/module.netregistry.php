<?php

	class CHECKOUT_NETREGISTRY extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the NetRegistry checkout module

			$this->_languagePrefix = "NetRegistry";
			$this->_id = "checkout_netregistry";
			$this->_image = "netregistry.jpg";

			parent::__construct();

			$this->requiresSSL = true;
			$this->_currenciesSupported = array("AUD");

			$this->_liveTransactionURL = 'https://4tknox.au.com';
			$this->_testTransactionURL = 'https://4tknox.au.com';
			$this->_liveTransactionURI = '/cgi-bin/themerchant.au.com/ecom/external2.pl';
			$this->_testTransactionURI = '/cgi-bin/themerchant.au.com/ecom/external2.pl';
			$this->_curlSupported = true;
			$this->_fsocksSupported = false;

		}

		protected function _ConstructPostData($postData)
		{
			$transactionid = $this->GetCombinedOrderId();

			// Contstruct the POST data
			$nrPost['COMMAND'] 	= 'purchase';
			$nrPost['LOGIN'] 	= $this->GetValue("merchantid") . '/' . $this->GetValue("password");
			$nrPost['CCNUM'] 	= $postData['ccno'];
			$nrPost['CCEXP'] 	= $postData['ccexpm'] . "/" . $postData['ccexpy'];
			$nrPost['AMOUNT'] 	= $this->GetGatewayAmount();
			$nrPost['TXNREF'] 	= $transactionid;
			$nrPost['COMMENT'] 	= $transactionid;

			return http_build_query($nrPost);
		}

		protected function _HandleResponse($result)
		{
			$response = explode("\n", $result);

			$approved = 'NO';

			if (isset($response[0])) {
				$approved = $response[0];
			}

			if($approved == 'approved') {

				$updatedOrder = array(
					'ordpaymentstatus' => 'captured'
				);

				$this->UpdateOrders($updatedOrder);

				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

				return true;
			}
			else {

				$responseCode = $responseMessage = 'Undefined';

				foreach ($response as $rsp) {
					$parts = explode("=", $rsp);

					if (isc_strtolower($parts[0]) == "response_text") {
						$responseMessage = urldecode($parts[1]);
					} else if (isc_strtolower($parts[0]) == "response_code") {
						$responseCode = urldecode($parts[1]);
					}
				}

				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Failure'), $responseCode, $responseMessage));

				// Something went wrong, show the error message with the credit card form
				$this->SetError(sprintf(GetLang($this->_languagePrefix."SomethingWentWrong")."%s : %s", $responseCode, $responseMessage));
				return false;
			}
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
			   "help" => GetLang('NetRegistryMerchantIdHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['password'] = array("name" => GetLang($this->_languagePrefix."Password"),
			   "type" => "password",
			   "help" => GetLang('NetRegistryPasswordHelp'),
			   "default" => "",
			   "required" => true
			);

		}

	}