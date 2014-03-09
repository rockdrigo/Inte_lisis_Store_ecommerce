<?php

	class CHECKOUT_PAYMENTCLEARING extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the SecurePay checkout module
			$this->_languagePrefix = "PaymentClearing";
			$this->_id = "checkout_paymentclearing";
			$this->_image = "logo_white.gif";

			parent::__construct();

			$this->requiresSSL = true;
			$this->_currenciesSupported = array('USD');

			$this->_liveTransactionURL = 'https://secure.paymentclearing.com';
			$this->_testTransactionURL = 'https://secure.paymentclearing.com';
			$this->_liveTransactionURI = '/cgi-bin/rc/xmltrans.cgi';
			$this->_testTransactionURI = '/cgi-bin/rc/xmltrans.cgi';
			$this->_curlSupported = true;
			$this->_fsocksSupported = true;
			$this->contentType = 'text/xml';
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

			$this->_variables['vendorid'] = array("name" => GetLang($this->_languagePrefix.'VendorId'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'VendorIdHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['vendorpassword'] = array("name" => GetLang($this->_languagePrefix.'VendorPassword'),
			   "type" => "password",
			   "help" => GetLang($this->_languagePrefix.'VendorPasswordHelp'),
			   "default" => "",
			   "required" => true
			);
		}

		protected function _ConstructPostData($postData)
		{
			$transactionid 	= $this->GetCombinedOrderId();
			$billingDetails = $this->GetBillingDetails();

			$ccname 		= $postData['name'];
			$cctype 		= $postData['cctype'];

			$ccissueno 		= $postData['ccissueno'];
			$ccissuedatem 	= $postData['ccissuedatem'];
			$ccissuedatey 	= $postData['ccissuedatey'];

			$ccnum 			= $postData['ccno'];
			$ccexpm 		= $postData['ccexpm'];
			$ccexpy 		= $postData['ccexpy'];
			$cccvd 			= $postData['cccvd'];

			switch ($postData['cctype']) {
				case 'VISA':
					$cctype = '6';
					break;
				case 'MC':
					$cctype = '5';
					break;
				case 'AMEX':
					$cctype = '2';
					break;
				case 'DINERS':
					$cctype = '3';
					break;
				default:
					$cctype = '0';
					break;
			}

			// Rounding to 2 decimal places. Any amount with more will cause PaymentClearing to reject the order
			// As per bug ISC-248
			$orderPaymentAmount = number_format($this->GetGatewayAmount(), 2, '.', '');

			$xml = 'xml=<?xml version="1.0" ?>
				<SaleRequest>
					<CustomerData>
						<Email>'.$billingDetails['ordbillemail'].'</Email>
						<BillingAddress>
							<Address1>'.$billingDetails['ordbillstreet1'].' '.$billingDetails['ordbillstreet2'].'</Address1>
							<FirstName>'.$billingDetails['ordbillfirstname'].'</FirstName>
							<LastName>'.$billingDetails['ordbilllastname'].'</LastName>
							<City>'.$billingDetails['ordbillsuburb'].'</City>
							<State>'.$billingDetails['ordbillstate'].'</State>
							<Zip>'.$billingDetails['ordbillzip'].'</Zip>
							<Country>'.$billingDetails['ordbillcountry'].'</Country>
							<Phone>'.$billingDetails['ordbillphone'].'</Phone>
						</BillingAddress>
						<AccountInfo>
							<CardInfo>
								<CCNum>'.$ccnum.'</CCNum>
								<CCMo>'.$ccexpm.'</CCMo>
								<CCYr>20'.$ccexpy.'</CCYr>
								<CVV2Number>'.$cccvd.'</CVV2Number>
							</CardInfo>
						</AccountInfo>
					</CustomerData>
					<TransactionData>
						<VendorId>'.$this->GetValue('vendorid').'</VendorId>
						<VendorPassword>'.$this->GetValue('vendorpassword').'</VendorPassword>
						<OrderItems>
							<Item>
								<Description>Payment for order</Description>
								<Cost>' . $orderPaymentAmount . '</Cost>
								<Qty>1</Qty>
							</Item>
						</OrderItems>
					</TransactionData>
				</SaleRequest>';

			return $xml;
		}

		protected function _HandleResponse($result)
		{
			$status = '';

			if (empty($result)) {
					$this->SetError(GetLang($this->_langaugePrefix).'ConnectionError');
					return false;
			}

			$xml = new SimpleXMLElement($result);

			$responseCode = $responseMessage = '';

			if (isset($xml) && isset($xml->TransactionData->Status)) {
				$status = $xml->TransactionData->Status;
			}

			if (isset($xml) && !empty($xml)) {

				if (!$xml->TransactionData) {
					$responseCode = (string)$xml->ErrorCategory;
					$responseMessage = (string)$xml->ErrorMessage;
				}
				else {
					$responseCode = (string)$xml->TransactionData->ErrorCategory;
					$responseMessage = (string)$xml->TransactionData->ErrorMessage;
				}

			}

			if($status == 'OK') {

				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

				return true;
			}
			else {

				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Failure'), $responseCode, $responseMessage));

				// Something went wrong, show the error message with the credit card form
				$this->SetError(GetLang($this->_languagePrefix.'SomethingWentWrong').sprintf(" %s : %s", $responseCode, $responseMessage));
				return false;
			}
		}
	}