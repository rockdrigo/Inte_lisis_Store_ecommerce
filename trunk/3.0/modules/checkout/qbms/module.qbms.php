<?php

	class CHECKOUT_QBMS extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the Quickbooks checkout module

			$this->_languagePrefix = "QBMS";
			$this->_image = "QBMS.gif";

			parent::__construct();

			$this->requiresSSL = true;
			$this->_currenciesSupported = array(
				'USD'
			);
			$this->_cardsSupported = array ('VISA','AMEX','MC','DINERS','DISCOVER','SOLO','MAESTRO','SWITCH','LASER');

			$this->_liveTransactionURL = 'https://webmerchantaccount.quickbooks.com';
			$this->_testTransactionURL = 'https://webmerchantaccount.ptc.quickbooks.com';
			$this->_liveTransactionURI = '/j/AppGateway';
			$this->_testTransactionURI = '/j/AppGateway';
			$this->_curlSupported = true;
			$this->_fsocksSupported = false;
			$this->supportsMultiShipping = true;
			$this->cardCodeRequired 	= true;
		}

		protected function _ConstructPostData($postData)
		{

			$billingDetails = $this->GetBillingDetails();

			$qbXML = new SimpleXMLElement('<?qbmsxml version="2.0"?><QBMSXML />');
			$signOnDesktop = $qbXML->addChild('SignonMsgsRq')->addChild('SignonDesktopRq');
			$signOnDesktop->addChild('ClientDateTime', date('Y-m-d\TH:i:s'));
			$signOnDesktop->addChild('ApplicationLogin', $this->GetValue('ApplicationLogin'));
			$signOnDesktop->addChild('ConnectionTicket', $this->GetValue('ConnectionTicket'));
			$signOnDesktop->addChild('Language', 'English');
			$signOnDesktop->addChild('AppID', $this->GetValue('AppID'));
			$signOnDesktop->addChild('AppVer', '1.0');

			$cardChargeRequest = $qbXML->addChild('QBMSXMLMsgsRq')->addChild('CustomerCreditCardChargeRq');
			$cardChargeRequest->addChild('TransRequestID', $this->GetCombinedOrderId());
			$cardChargeRequest->addChild('CreditCardNumber', $postData['ccno']);
			$cardChargeRequest->addChild('ExpirationMonth', $postData['ccexpm']);
			$cardChargeRequest->addChild('ExpirationYear', $postData['ccexpy']);
			$cardChargeRequest->addChild('IsECommerce', 'true');
			$cardChargeRequest->addChild('Amount', $this->GetGatewayAmount());
			$cardChargeRequest->addChild('NameOnCard', isc_substr($postData['name'], 0, 30));
			$cardChargeRequest->addChild('CreditCardAddress', isc_substr($billingDetails['ordbillstreet1'], 0, 30));
			$cardChargeRequest->addChild('CreditCardPostalCode', isc_substr($billingDetails['ordbillzip'], 0, 9));
			$cardChargeRequest->addChild('SalesTaxAmount', $this->GetTaxCost());
			$cardChargeRequest->addChild('CardSecurityCode', $postData['cccvd']);
			return $qbXML->asXML();
		}

		/**
		* send request to payment provider and retrieve respone
		*
		* @param string $transactionURL url to connect payment provider
		* @param string $transactionURI uri to connect payment provider
		* @param string $gateway_postdata data send to payment provider
		*
		* @return array data received from payment provider.
		*/
		protected function _ConnectToProvider($transactionURL, $transactionURI, $gateway_postdata)
		{
			$responseHeader = '';

			$header[] = "Content-type: application/x-qbmsxml";
			$header[] = "Content-length: ".strlen($gateway_postdata);

			if(function_exists("curl_exec") && $this->_curlSupported) {

				// Use CURL if it's available
				$ch = curl_init($transactionURL.$transactionURI);

				curl_setopt($ch, CURLOPT_POST, 1);
				if ($this->requireHeaders == true) {
					curl_setopt($ch, CURLOPT_HEADER, 1);
				}
				curl_setopt($ch, CURLOPT_POSTFIELDS, $gateway_postdata);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
				curl_setopt($ch, CURLOPT_TIMEOUT, 60);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

				// Setup the proxy settings if there are any
				if (GetConfig('HTTPProxyServer')) {
					curl_setopt($ch, CURLOPT_PROXY, GetConfig('HTTPProxyServer'));
					if (GetConfig('HTTPProxyPort')) {
						curl_setopt($ch, CURLOPT_PROXYPORT, GetConfig('HTTPProxyPort'));
					}
				}

				if (GetConfig('HTTPSSLVerifyPeer') == 0) {
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				}

				$result = curl_exec($ch);

				if ($this->requireHeaders == true) {

					$result = explode("\n\r\n", $result);
					$responseHeader = $result[0];
					$result = $result[1];

				}

				if(curl_errno($ch)) {
					$this->SetError(GetLang('CreditCardCurlError'). $this->GetValue('displayname') . ":" .curl_error($ch));
					return false;
				}
			}
			else {
				$this->SetError(GetLang('CreditCardConnectionMethod') . $this->GetValue('displayname'));
				return false;
			}

			if (empty($result)) {
				return $responseHeader;
			}

			return $result;
		}


		protected function _HandleResponse($result)
		{
			try {
				$xml = new SimpleXMLElement($result);
			}
			catch (Exception $e) {
				$xml = null;
			}

			if (empty($xml)) {
				$logMessage = GetLang('QBMSFailure', array(
					'orderId' => $this->GetCombinedOrderId()
				));

				$logDetails = GetLang('QBMSFailureInvalidXML', array(
					'response' => isc_html_escape($result)
				));
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $logMessage, $logDetails);
				$this->SetError(GetLang('QBMSChargeFailed'));
				return false;
			}

			$approved = false;
			if (isset($xml->SignonMsgsRs->SignonDesktopRs['statusSeverity']) && $xml->SignonMsgsRs->SignonDesktopRs['statusSeverity'] == 'ERROR') {
				$statusCode = (string)$xml->SignonMsgsRs->SignonDesktopRs['statusCode'];
				$statusMessage = (string)$xml->SignonMsgsRs->SignonDesktopRs['statusMessage'];
			} else if (isset($xml->QBMSXMLMsgsRs->CustomerCreditCardChargeRs['statusSeverity'])) {
				$statusCode = (string)$xml->QBMSXMLMsgsRs->CustomerCreditCardChargeRs['statusCode'];
				$statusMessage = (string)$xml->QBMSXMLMsgsRs->CustomerCreditCardChargeRs['statusMessage'];

				if ((string)$xml->QBMSXMLMsgsRs->CustomerCreditCardChargeRs['statusCode'] == 0) {
					$approved = true;
				}
			} else {
				$statusCode = 'Unknown';
				$statusMessage = 'Unknown';
			}

			if($approved) {

				$updatedOrder = array(
					'ordpayproviderid' => (string)$xml->QBMSXMLMsgsRs->CustomerCreditCardChargeRs['CreditCardTransID'],
					'ordpaymentstatus' => 'captured'
				);

				$this->UpdateOrders($updatedOrder);

				$logMsg = GetLang('QBMSSuccess', array(
					'orderId' => $this->GetCombinedOrderId()
				));
				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $logMsg);
				return true;
			}
			else {
				$logMessage = GetLang('QBMSFailure', array(
					'orderId' => $this->GetCombinedOrderId()
				));

				$logDetails = GetLang('QBMSFailureDetails', array(
					'responseCode' => $statusCode,
					'responseMessage' => $statusMessage
				));
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $logMessage, $logDetails);
				$this->SetError(GetLang('QBMSChargeFailed'));
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

			$this->_variables['ApplicationLogin'] = array("name" => GetLang($this->_languagePrefix."ApplicationLogin"),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'ApplicationLoginHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['AppID'] = array("name" => GetLang($this->_languagePrefix."AppID"),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'AppIDHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['ConnectionTicket'] = array("name" => GetLang($this->_languagePrefix."ConnectionTicket"),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'ConnectionTicketHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['testmode'] = array(
				"name" => "Test Mode",
				"type" => "dropdown",
				"help" => GetLang("PayMateTestModeHelp"),
				"default" => "no",
				"required" => true,
				"options" => array(
					GetLang("PayMateTestModeNo") => "NO",
					GetLang("PayMateTestModeYes") => "YES"
				),
				"multiselect" => false
			);
		}
	}
