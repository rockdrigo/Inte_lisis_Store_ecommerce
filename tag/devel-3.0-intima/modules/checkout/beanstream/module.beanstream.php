<?php

	class CHECKOUT_BEANSTREAM extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the BeanStream checkout module

			$this->_languagePrefix = "BeanStream";
			$this->_id = "checkout_beanstream";
			$this->_image = "beanstream_logo.gif";

			parent::__construct();

			$this->requiresSSL = true;
			$this->_currenciesSupported = array("USD", "CAD");

			$this->_liveTransactionURL = 'https://www.beanstream.com';
			$this->_testTransactionURL = 'https://www.beanstream.com';
			$this->_liveTransactionURI = '/scripts/process_transaction.asp';
			$this->_testTransactionURI = '/scripts/process_transaction.asp';
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

			$this->_variables['cardcode'] = array("name" => GetLang($this->_languagePrefix."CardCode"),
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

			if ($billingDetails['ordbillcountrycode'] == 'CA' || $billingDetails['ordbillcountrycode'] == 'US') {
				$query = "Select stateabbrv from [|PREFIX|]country_states Where stateid = '".$billingDetails['ordbillstateid']."'";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$province = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
			} else {
				$province = '--';
			}

			$ccemail = $billingDetails['ordbillemail'];

			if (empty($ccemail)) {
				// Get the customer's email address
				$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
				$ccemail = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerEmailAddress();
			}

			// Contstruct the POST data
			$beanstream_post['merchant_id'] 	= $this->GetValue('merchantid');
			$beanstream_post['trnCardNumber'] 	= $ccnum;
			$beanstream_post['trnCardOwner'] 	= $ccname;
			$beanstream_post['trnExpMonth'] 	= $ccexpm;
			$beanstream_post['trnExpYear'] 		= $ccexpy;
			$beanstream_post['trnAmount'] 		= $this->GetGatewayAmount();
			$beanstream_post['trnOrderNumber'] 	= $transactionid;

			$require_cardcode = $this->GetValue("cardcode");

			if ($require_cardcode == "YES") {
				$beanstream_post['trnCardCvd'] = $cccvd;
			}

			$beanstream_post['ordName'] = $ccname;
			$beanstream_post['ordEmailAddress'] = $ccemail;
			$beanstream_post['ordPhoneNumber'] = $billingDetails['ordbillphone'];
			$beanstream_post['ordAddress1'] = $billingDetails['ordbillstreet1'];
			$beanstream_post['ordAddress2'] = $billingDetails['ordbillstreet2'];
			$beanstream_post['ordCity'] = $billingDetails['ordbillsuburb'];
			$beanstream_post['ordProvince'] = $province;
			$beanstream_post['ordCountry'] = $billingDetails['ordbillcountrycode'];
			$beanstream_post['ordPostalCode'] = $billingDetails['ordbillzip'];
			$beanstream_post['ordName'] = $billingDetails['ordbillfirstname'] . " " . $billingDetails['ordbilllastname'];

			// Use the backend so we don't need to display an error page
			$beanstream_post['requestType'] = 'BACKEND';

			return http_build_query($beanstream_post);
		}

		protected function _HandleResponse($response)
		{
			$result = array();
			parse_str($response,$result);

			$approved = false;
			$trnAmount = -1;
			$trnId = -1;

			if (isset($result['trnApproved'])) {
				$approved = (int)$result['trnApproved'];
			}

			if (isset($result['trnAmount'])) {
				$trnAmount = $result['trnAmount'];
			}

			if (isset($result['trnId'])) {
				$trnId = $result['trnId'];
			}

			if($approved) {

				// The transaction was successful, make sure it was for the right amount
				$order_total = $this->GetGatewayAmount();

				settype($order_total, "double");
				settype($trnAmount, "double");

				if($order_total == $trnAmount) {

					if ($trnId != -1) {
						// Save the authorization key
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
					$this->SetError(GetLang($this->_languagePrefix.'PaymentMismatch'));
					return false;
				}
			}
			else {

				$responseCode = $responseMessage = 'Undefined';

				if (isset($result['messageId'])) {
					$responseCode = $result['messageId'];
				}

				if (isset($result['messageText'])) {
					$responseMessage = $result['messageText'];
				}

				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Failure'), $responseCode, $responseMessage));
				// Something went wrong, show the error message with the credit card form
				$this->SetError(GetLang($this->_languagePrefix."SomethingWentWrong").sprintf(" %s : %s", $responseCode, $responseMessage));
				return false;
			}
		}
	}