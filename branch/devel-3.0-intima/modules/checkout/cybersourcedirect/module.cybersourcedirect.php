<?php

class CHECKOUT_CYBERSOURCEDIRECT extends ISC_GENERIC_CREDITCARD {
	/*
	Test environment: https://ics2wstest.ic3.com/commerce/1.x/transactionProcessor
	Production environment: https://ics2ws.ic3.com/commerce/1.x/transactionProcessor
	*/

	public function __construct()
	{
		// Setup the required variables for the Cybersouce checkout module

		$this->_languagePrefix = "CyberSource";
		$this->_image = "cybersource.jpg";

		$this->_liveTransactionURL = 'https://ics2ws.ic3.com';
		$this->_testTransactionURL = 'https://ics2wstest.ic3.com';
		$this->_liveTransactionURI = '/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.53.wsdl';
		$this->_testTransactionURI = '/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.53.wsdl';

		parent::__construct();

		$this->requiresSSL = true;
		$this->_currenciesSupported = array('AUD', 'CAD', 'NZD', 'EUR', 'GBP', 'USD', 'JPY');
		$this->_cardsSupported = array ('VISA','AMEX','MC','DINERS','DISCOVER','MAESTRO', 'JCB', 'SOLO', 'LASER');
	}

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
		   "help" => GetLang('CyberSourceMerchantIdHelp'),
		   "default" => '',
		   "required" => true
		);

		$this->_variables['securitykey'] = array("name" => GetLang($this->_languagePrefix."SecurityKey"),
		   "type" => "textbox",
		   "help" => GetLang('CyberSourceSecurityKeyHelp'),
		   "default" => '',
		   "required" => true
		);

		$this->_variables['transactiontype'] = array(
			"name" => GetLang('CyberSourceTransactionType'),
			"type" => "dropdown",
			"help" => GetLang('CyberSourceTransactionTypeHelp'),
			"default" => "AUTH",
			"savedvalue" => array(),
			"required" => true,
			"options" => array(
				GetLang('CyberSourceTransactionTypeSale') => "SALE",
				GetLang('CyberSourceTransactionTypeAuthorize') => "AUTH"
			),
			"multiselect" => false
		);

		$this->_variables['cardcode'] = array("name" => GetLang($this->_languagePrefix."CardCode"),
			   "type" => "dropdown",
			   "help" => GetLang('CyberSourceCardCodeHelp'),
			   "default" => "NO",
			   "required" => true,
			   "options" => array(GetLang('CyberSourceCardCodeNo') => "NO",
							  GetLang('CyberSourceCardCodeYes') => "YES"
				),
				"multiselect" => false
			);

		$this->_variables['testmode'] = array("name" => GetLang($this->_languagePrefix."TestMode"),
		   "type" => "dropdown",
		   "help" => GetLang('CyberSourceTestModeHelp'),
		   "default" => "no",
		   "required" => true,
		   "options" => array(GetLang('CyberSourceTestModeNo') => "NO",
						  GetLang('CyberSourceTestModeYes') => "YES"
			),
			"multiselect" => false
		);
	}

	public function ProcessPaymentForm($dataSource = array())
	{
		if (empty($dataSource)) {
			$dataSource = $_POST;
		}

		$postData = $this->_Validate($dataSource);

		if ($postData === false) {
			return false;
		}

		$xmlData = $this->_ConstructPostData($postData);

		$result = $this->runTransaction($xmlData);

		$result = $this->_HandleResponse($result);

		if ($result) {
			$this->SetPaymentStatus(PAYMENT_STATUS_PAID);
		}

		return $result;
	}

	protected function _ConstructPostData($postData)
	{

		$billingDetails = $this->GetBillingDetails();
		$billState = '';
		if ($billingDetails['ordbillcountrycode'] == 'US' || $billingDetails['ordbillcountrycode'] == 'CA') {
			$billState = GetStateISO2ById($billingDetails['ordbillstateid']);
		}

		$shippingDetails = $this->getShippingAddress();
		$shipState = '';
		if ($shippingDetails['country_iso2'] == 'US' || $shippingDetails['country_iso2'] == 'CA') {
			$shipState = GetStateISO2ById($shippingDetails['state_id']);
		}


		switch ($postData['cctype']) {
			case 'VISA':
				$cctype = '001';
				break;
			case 'MC':
				$cctype = '002';
				break;
			case 'AMEX':
				$cctype = '003';
				break;
			case 'DISCOVER':
				$cctype = '004';
				break;
			case 'DINERS':
				$cctype = '005';
				break;
			case 'JCB':
				$cctype = '007';
				break;
			case 'MAESTRO':
			case 'SOLO':
				$cctype = '024';
				break;
			case 'LASER':
				$cctype = '035';
				break;
		}

		$quote = getCustomerQuote();

		$xml = array(
			'merchantID' => $this->GetValue('merchantid'),
			'merchantReferenceCode' => $this->GetCombinedOrderId(),
			'ccAuthService' => array(
				'run' => 'true',
			),
			'clientLibrary' => 'PHP',
			'clientLibraryVersion' => phpversion(),
			'clientEnvironment' => php_uname(),
			'billTo' => array(
				'firstName' 	=> $billingDetails['ordbillfirstname'],
				'lastName'		=> $billingDetails['ordbilllastname'],
				'company'		=> $billingDetails['ordbillcompany'],
				'street1'		=> $billingDetails['ordbillstreet1'],
				'city'			=> $billingDetails['ordbillsuburb'],
				'state'			=> $billState,
				'country'		=> $billingDetails['ordbillcountrycode'],
				'postalCode'	=> $billingDetails['ordbillzip'],
				'email'			=> $billingDetails['ordbillemail'],
				'phoneNumber'	=> $billingDetails['ordbillphone'],
			),
			'shipTo' => array(
				'firstName' 	=> $shippingDetails['first_name'],
				'lastName'		=> $shippingDetails['last_name'],
				'company'		=> $shippingDetails['company'],
				'street1'		=> $shippingDetails['address_1'],
				'street2'		=> $shippingDetails['address_2'],
				'city'			=> $shippingDetails['city'],
				'state'			=> $shipState,
				'country'		=> $shippingDetails['country_iso2'],
				'postalCode'	=> $shippingDetails['zip'],
				'email'			=> $shippingDetails['email'],
				'phoneNumber'	=> $shippingDetails['phone'],
			),
			'purchaseTotals' => array(
				'currency' => GetCurrencyCodeByID($this->GetCurrency()),
				'grandTotalAmount' => number_format($this->GetGatewayAmount(), 2, '.', ''),
			),
		);

		$ip = GetIP();
		if ($ip) {
			$xml['billTo']['ipAddress'] = $ip;
		}

		// are we doing a sale ?
		if ($this->GetValue('transactiontype') == 'SALE') {
			$xml['ccCaptureService'] = array(
				'run' => 'true',
			);
		}

		$card = array(
			'fullName'			=> $postData['name'],
			'accountNumber' 	=> $postData['ccno'],
			'expirationMonth' 	=> $postData['ccexpm'],
			'expirationYear' 	=> '20' . $postData['ccexpy'],
			'cardType' 			=> $cctype,
		);
		if ($this->GetValue('cardcode') == 'YES') {
			$card['cvNumber'] = $postData['cccvd'];
		}
		if ($this->CardTypeRequiresIssueNoOrDate($postData['cctype'])) {
			$card['issueNumber'] = $postData['ccissueno'];
			$card['startMonth'] = $postData['ccissuedatem'];
			$card['startYear'] = $postData['ccissuedatey'];
		}

		$xml['card'] = $card;

		// add items to order
		$x = 0;
		$items = array();
		foreach ($quote->getItems() as /** @var ISC_QUOTE_ITEM */$item) {
			$itemXml = array(
				'productName' 	=> $item->getName(),
				'productSKU'	=> $item->getSku(),
				'unitPrice'		=> number_format($item->getPrice(), '2', '.', ''),
				'quantity'		=> $item->getQuantity(),
				'id'			=> (string)$x,
			);

			$items[] = $itemXml;

			$x++;
		}

		if (!empty($items)) {
			$xml['items'] = $items;
		}

		return $xml;
	}

	private function getAuthHeaders()
	{
		$headers = '
			<wsse:Security SOAP-ENV:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
				<wsse:UsernameToken>
					<wsse:Username>' . $this->GetValue('merchantid') . '</wsse:Username>
					<wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . $this->GetValue('securitykey') . '</wsse:Password>
				</wsse:UsernameToken>
			</wsse:Security>
		';

		return $headers;
	}

	private function runTransaction($data)
	{
		require_once(dirname(__FILE__)."/../../../lib/nusoap/nusoap.php");

		$headers = $this->getAuthHeaders();

		if ($this->GetValue('testmode') == 'YES') {
			$url = $this->_testTransactionURL . $this->_testTransactionURI;
		}
		else {
			$url = $this->_liveTransactionURL . $this->_liveTransactionURI;
		}

		$client = new nusoap_client($url, true);
		$result = $client->call('runTransaction', array($data), '', '', $headers);

		return $result;
	}

	public function _HandleResponse($response)
	{
		if (!isset($response['decision'])) {
			$this->SetError(GetLang('CyberSourceInvalidRequest'));
			return false;
		}

		$decision = $response['decision'];
		$reasonCode = $response['reasonCode'];
		$requestID = $response['requestID'];
		$requestToken = $response['requestToken'];

		if ($this->GetValue('transactiontype') == 'SALE') {
			$transactionType = GetLang('CyberSourceTransactionTypeSale');
		}
		else {
			$transactionType = GetLang('CyberSourceTransactionTypeAuthorize');
		}

		if ($decision == 'ACCEPT') {
			$reconciliationID = '';

			if($this->GetValue('transactiontype') == 'SALE') {
				$paymentStatus = 'captured';
				if (isset($response['ccCaptureReply']['reconciliationID'])) {
					$reconciliationID = $response['ccCaptureReply']['reconciliationID'];
				}
			}
			else {
				$paymentStatus = 'authorized';
				if (isset($response['ccAuthReply']['reconciliationID'])) {
					$reconciliationID = $response['ccAuthReply']['reconciliationID'];
				}
			}

			$order = current($this->GetOrders());

			// Is there any existing extra info for the pending order?
			$extraInfo = array();
			if($order['extrainfo'] != "") {
				$extraInfo = @unserialize($order['extrainfo']);
			}

			if (!is_array($extraInfo)) {
				$extraInfo = array();
			}

			$extraInfo['CyberSourceRequestToken'] = $requestToken;
			$extraInfo['CyberSourceRequestID'] = $requestID;

			$updatedOrder = array(
				'ordpayproviderid' 	=> $reconciliationID,
				'ordpaymentstatus' 	=> $paymentStatus,
				'extrainfo'			=> serialize($extraInfo),
			);

			$this->UpdateOrders($updatedOrder);

			$logMessage = GetLang('CyberSourceSuccess', array('orderId' => $this->GetCombinedOrderId()));

			$logDetails = GetLang('CyberSourceSuccessDetails', array(
				'decision' 			=> $decision,
				'reasonCode' 		=> $reasonCode,
				'requestID' 		=> $requestID,
				'reconciliationID'	=> $reconciliationID,
				'transactionType'	=> $transactionType,
			));

			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $logMessage, $logDetails);

			return true;
		}
		else {
			// any missing fields?
			$missingFieldsString = '';
			if (isset($response['missingField'])) {
				$missingFields = $response['missingField'];
				if (!is_array($missingFields)) {
					$missingFields = array($missingFields);
				}

				$missingFieldsString = implode(', ', $missingFields);
			}

			//any invalid fields?
			$invalidFieldsString = '';
			if (isset($response['invalidField'])) {
				$invalidFields = $response['invalidField'];
				if (!is_array($invalidFields)) {
					$invalidFields = array($invalidFields);
				}

				$invalidFieldsString = implode(', ', $invalidFields);
			}

			$logMessage = GetLang('CyberSourceFailure', array('orderId' => $this->GetCombinedOrderId()));

			$logDetails = GetLang('CyberSourceFailureDetails', array(
				'decision' 			=> $decision,
				'reasonCode' 		=> $reasonCode,
				'requestID' 		=> $requestID,
				'transactionType'	=> $transactionType,
				'missingFields'		=> $missingFieldsString,
				'invalidFields'		=> $invalidFieldsString,
			));


			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $logMessage, $logDetails);

			$this->SetError(GetLang('CyberSourcePaymentRejected'));

			return false;
		}
	}

	/**
	* Performs a delayed capture transaction
	*
	* @param mixed $order
	* @param string $message
	* @param mixed $amount
	*/
	public function DelayedCapture($order, &$message = '', $amount = 0)
	{
		$extraInfo = @unserialize($order['extrainfo']);
		if (!is_array($extraInfo) || empty($extraInfo['CyberSourceRequestToken']) || empty($extraInfo['CyberSourceRequestID'])) {
			$message = GetLang('CyberSourceTransactionDetailsMissing');
			return false;
		}

		$request = array(
			'merchantID' => $this->GetValue('merchantid'),
			'merchantReferenceCode' => $order['orderid'],
			'ccCaptureService' => array(
				'authRequestID'	=> $extraInfo['CyberSourceRequestID'],
				'run' 			=> 'true',
			),
			'orderRequestToken' => $extraInfo['CyberSourceRequestToken'],
			'purchaseTotals' => array(
				'currency' 			=> GetCurrencyCodeByID($order['ordcurrencyid']),
				'grandTotalAmount' 	=> number_format($amount, 2, '.', ''),
			),
		);

		$response = $this->runTransaction($request);

		if (!isset($response['decision'])) {
			$message = GetLang('CyberSourceInvalidRequest');
			return false;
		}

		$decision = $response['decision'];
		$reasonCode = $response['reasonCode'];
		$requestID = $response['requestID'];
		$requestToken = $response['requestToken'];

		$transactionType = GetLang('CyberSourceTransactionTypeCapture');

		if ($decision == 'ACCEPT') {
			$message = GetLang('CyberSourcePaymentCaptured');

			$reconciliationID = $order['ordpayproviderid'];
			if (isset($response['ccCaptureReply']['reconciliationID'])) {
				$reconciliationID = $response['ccCaptureReply']['reconciliationID'];
			}

			$extraInfo['CyberSourceRequestID'] = $requestID;
			$extraInfo['CyberSourceRequestToken'] = $requestToken;

			// Mark the order as captured
			$updatedOrder = array(
				'ordpayproviderid' 	=> $reconciliationID,
				'ordpaymentstatus' 	=> 'captured',
				'extrainfo'			=> serialize($extraInfo),
			);

			// Update the orders table with new transaction details
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$order['orderid']."'");

			// Log the transaction in store logs
			$logMessage = GetLang('CyberSourcePaymentCapturedLogMsg', array('orderId' => $order['orderid']));

			$logDetails = GetLang('CyberSourceSuccessDetails', array(
				'decision' 			=> $decision,
				'reasonCode' 		=> $reasonCode,
				'requestID' 		=> $requestID,
				'reconciliationID'	=> $reconciliationID,
				'transactionType'	=> $transactionType,
			));

			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $logMessage, $logDetails);

			return true;
		}
		else {
			$message = GetLang('CyberSourceCaptureFailed');

			// any missing fields?
			$missingFieldsString = '';
			if (isset($response['missingField'])) {
				$missingFields = $response['missingField'];
				if (!is_array($missingFields)) {
					$missingFields = array($missingFields);
				}

				$missingFieldsString = implode(', ', $missingFields);
			}

			//any invalid fields?
			$invalidFieldsString = '';
			if (isset($response['invalidField'])) {
				$invalidFields = $response['invalidField'];
				if (!is_array($invalidFields)) {
					$invalidFields = array($invalidFields);
				}

				$invalidFieldsString = implode(', ', $invalidFields);
			}

			$logMessage = GetLang('CyberSourceCaptureFailedLogMsg', array('orderId' => $order['orderid']));

			$logDetails = GetLang('CyberSourceFailureDetails', array(
				'decision' 			=> $decision,
				'reasonCode' 		=> $reasonCode,
				'requestID' 		=> $requestID,
				'transactionType'	=> $transactionType,
				'missingFields'		=> $missingFieldsString,
				'invalidFields'		=> $invalidFieldsString,
			));

			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $logMessage, $logDetails);

			return false;
		}
	}


	/**
	* Voids an authorized transaction
	*
	* @param mixed $orderId
	* @param mixed $transactionId
	* @param string $message
	*/
	public function DoVoid($orderId, $transactionId, &$message = '')
	{
		$order = GetOrder($orderId);

		$extraInfo = @unserialize($order['extrainfo']);
		if (!is_array($extraInfo) || empty($extraInfo['CyberSourceRequestToken']) || empty($extraInfo['CyberSourceRequestID'])) {
			$message = GetLang('CyberSourceTransactionDetailsMissing');
			return false;
		}

		$request = array(
			'merchantID' => $this->GetValue('merchantid'),
			'merchantReferenceCode' => $orderId,
			'ccAuthReversalService' => array(
				'authRequestID'	=> $extraInfo['CyberSourceRequestID'],
				'run' 			=> 'true',
			),
			'orderRequestToken' => $extraInfo['CyberSourceRequestToken'],
			'purchaseTotals' => array(
				'currency' 			=> GetCurrencyCodeByID($order['ordcurrencyid']),
				'grandTotalAmount' 	=> number_format($order['total_inc_tax'], 2, '.', ''),
			),
		);

		$response = $this->runTransaction($request);

		if (!isset($response['decision'])) {
			$message = GetLang('CyberSourceInvalidRequest');
			return false;
		}

		$decision = $response['decision'];
		$reasonCode = $response['reasonCode'];
		$requestID = $response['requestID'];
		$requestToken = $response['requestToken'];

		$transactionType = GetLang('CyberSourceTransactionTypeVoid');

		if ($decision == 'ACCEPT') {
			$message = GetLang('CyberSourcePaymentVoided');

			unset($extraInfo['CyberSourceRequestID']);
			unset($extraInfo['CyberSourceRequestToken']);

			// Mark the order as captured
			$updatedOrder = array(
				'ordpaymentstatus' 	=> 'void',
				'extrainfo' 		=> serialize($extraInfo),
			);

			// Update the orders table with new transaction details
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$orderId."'");

			$authorizationCode = '';
			if (isset($response['ccAuthReversalReply']['authorizationCode'])) {
				$authorizationCode = $response['ccAuthReversalReply']['authorizationCode'];
			}

			// Log the transaction in store logs
			$logMessage = GetLang('CyberSourcePaymentVoidedLogMsg', array('orderId' => $orderId));

			$logDetails = GetLang('CyberSourcePaymentVoidedLogDetails', array(
				'decision' 			=> $decision,
				'reasonCode' 		=> $reasonCode,
				'requestID' 		=> $requestID,
				'authorizationCode'	=> $authorizationCode,
				'transactionType'	=> $transactionType,
			));

			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $logMessage, $logDetails);

			return true;
		}
		else {
			$message = GetLang('CyberSourceVoidFailed');

			// any missing fields?
			$missingFieldsString = '';
			if (isset($response['missingField'])) {
				$missingFields = $response['missingField'];
				if (!is_array($missingFields)) {
					$missingFields = array($missingFields);
				}

				$missingFieldsString = implode(', ', $missingFields);
			}

			//any invalid fields?
			$invalidFieldsString = '';
			if (isset($response['invalidField'])) {
				$invalidFields = $response['invalidField'];
				if (!is_array($invalidFields)) {
					$invalidFields = array($invalidFields);
				}

				$invalidFieldsString = implode(', ', $invalidFields);
			}

			$logMessage = GetLang('CyberSourceVoidFailedLogMsg', array('orderId' => $orderId));

			$logDetails = GetLang('CyberSourceFailureDetails', array(
				'decision' 			=> $decision,
				'reasonCode' 		=> $reasonCode,
				'requestID' 		=> $requestID,
				'transactionType'	=> $transactionType,
				'missingFields'		=> $missingFieldsString,
				'invalidFields'		=> $invalidFieldsString,
			));

			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $logMessage, $logDetails);
			return false;
		}
	}

	/**
	* Refunds a transaction
	*
	* @param mixed $order
	* @param mixed $message
	* @param mixed $amt
	*/
	public function DoRefund($order, &$message = '', $amount = 0)
	{
		$orderId = $order['orderid'];
		$orderAmount = $order['total_inc_tax'];
		$totalRefundedAmount = $amount + $order['ordrefundedamount'];

		$extraInfo = @unserialize($order['extrainfo']);
		if (!is_array($extraInfo) || empty($extraInfo['CyberSourceRequestToken']) || empty($extraInfo['CyberSourceRequestID'])) {
			$message = GetLang('CyberSourceTransactionDetailsMissing');
			return false;
		}

		$request = array(
			'merchantID' => $this->GetValue('merchantid'),
			'merchantReferenceCode' => $orderId,
			'ccCreditService' => array(
				'captureRequestID'	=> $extraInfo['CyberSourceRequestID'],
				'run' 				=> 'true',
			),
			'orderRequestToken' => $extraInfo['CyberSourceRequestToken'],
			'purchaseTotals' => array(
				'currency' 			=> GetCurrencyCodeByID($order['ordcurrencyid']),
				'grandTotalAmount' 	=> number_format($amount, 2, '.', ''),
			),
		);

		$response = $this->runTransaction($request);

		if (!isset($response['decision'])) {
			$message = GetLang('CyberSourceInvalidRequest');
			return false;
		}

		$decision = $response['decision'];
		$reasonCode = $response['reasonCode'];
		$requestID = $response['requestID'];
		$requestToken = $response['requestToken'];

		$transactionType = GetLang('CyberSourceTransactionTypeRefund');

		if ($decision == 'ACCEPT') {
			$message = GetLang('CyberSourcePaymentRefunded');

			//if total refunded is less than the order total amount
			if($totalRefundedAmount < $orderAmount) {
				$paymentStatus = 'partially refunded';
			} else {
				$paymentStatus = 'refunded';
			}

			// update the order payment status and refund amount
			$updatedOrder = array(
				'ordpaymentstatus' => $paymentStatus,
				'ordrefundedamount'	=> $totalRefundedAmount,
			);

			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$orderId."'");

			$reconciliationID = '';
			if (!isset($response['ccCreditReply']['reconciliationID'])) {
				$reconciliationID = $response['ccCreditReply']['reconciliationID'];
			}

			$refundAmount = $response['ccCreditReply']['amount'];

			// Log the transaction in store logs
			$logMessage = GetLang('CyberSourcePaymentRefundedLogMsg', array('orderId' => $orderId));

			$logDetails = GetLang('CyberSourcePaymentRefundedLogDetails', array(
				'decision' 			=> $decision,
				'reasonCode' 		=> $reasonCode,
				'requestID' 		=> $requestID,
				'reconciliationID'	=> $reconciliationID,
				'amount'			=> $refundAmount,
				'transactionType'	=> $transactionType,
			));

			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $logMessage, $logDetails);

			return true;
		}
		else {
			$message = GetLang('CyberSourceRefundFailed');

			// any missing fields?
			$missingFieldsString = '';
			if (isset($response['missingField'])) {
				$missingFields = $response['missingField'];
				if (!is_array($missingFields)) {
					$missingFields = array($missingFields);
				}

				$missingFieldsString = implode(', ', $missingFields);
			}

			//any invalid fields?
			$invalidFieldsString = '';
			if (isset($response['invalidField'])) {
				$invalidFields = $response['invalidField'];
				if (!is_array($invalidFields)) {
					$invalidFields = array($invalidFields);
				}

				$invalidFieldsString = implode(', ', $invalidFields);
			}

			$logMessage = GetLang('CyberSourceRefundFailedLogMsg', array('orderId' => $orderId));

			$logDetails = GetLang('CyberSourceFailureDetails', array(
				'decision' 			=> $decision,
				'reasonCode' 		=> $reasonCode,
				'requestID' 		=> $requestID,
				'transactionType'	=> $transactionType,
				'missingFields'		=> $missingFieldsString,
				'invalidFields'		=> $invalidFieldsString,
			));

			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $logMessage, $logDetails);
			return false;
		}

	}
}
