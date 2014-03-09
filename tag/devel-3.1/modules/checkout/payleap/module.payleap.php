<?php

class CHECKOUT_PAYLEAP extends ISC_GENERIC_CREDITCARD
{
	protected $_languagePrefix = 'PayLeap';

	protected $requiresSSL = true;

	protected $_currenciesSupported = array(
		'USD'
	);

	protected $_cardsSupported = array(
		'VISA',
		'AMEX',
		'MC',
		'DISCOVER',
		'DINERS',
	);

	protected $_liveTransactionURL = 'https://secure.payleap.com';
	protected $_testTransactionURL = 'http://test.payleap.com';

	protected $_liveTransactionURI = '/SmartPayments/transact.asmx/ProcessCreditCard';
	protected $_testTransactionURI = '/SmartPayments/transact.asmx/ProcessCreditCard';

	protected $_curlSupported = true;
	protected $_fsocksSupported = true;
	protected $cardCodeRequired = true;

	public function __construct()
	{
		$this->SetImage('logo.gif');
		parent::__construct();
	}

	public function SetCustomVars()
	{
		$this->_variables['displayname'] = array(
			'name' => GetLang('PayLeapDisplayName'),
			'type' => 'textbox',
			'help' => GetLang('DisplayNameHelp'),
			'default' => $this->GetName(),
			'required' => true
		);

		$this->_variables['username'] = array(
			'name' => GetLang('PayLeapUserName'),
			'type' => 'textbox',
			'help' => GetLang('PayLeapUserNameHelp'),
			'default' => "",
			'required' => true
		);

		$this->_variables['password'] = array(
			'name' => GetLang('PayLeapPassword'),
			'type' => 'password',
			'help' => GetLang('PayLeapPasswordHelp'),
			'default' => "",
			'required' => true
		);

		$this->_variables['transtype'] = array(
			'name' => GetLang('PayLeapTransactionType'),
			'type' => 'dropdown',
			'help' => GetLang('PayLeapTransactionTypeHelp'),
			'default' => 'no',
			'required' => true,
			'options' => array(
				GetLang('PayLeapTransactionTypeSale') => 'SALE',
				GetLang('PayLeapTransactionTypeAuth') => 'AUTH'
			),
			'multiselect' => false
		);

		$this->_variables['testmode'] = array(
			'name' => "Test Mode",
			'type' => 'dropdown',
			'help' => GetLang('PayLeapTestModeHelp'),
			'default' => 'no',
			'required' => true,
			'options' => array(
				GetLang('PayLeapTestModeNo') => 'NO',
				GetLang('PayLeapTestModeYesLive') => 'YESLIVE',
				GetLang('PayLeapTestModeYesDev') => 'YES',
			),
			'multiselect' => false
		);
	}

	protected function _ConstructPostData($postData)
	{
		if ($this->GetValue('transtype') == 'SALE') {
			$transType = 'Sale';
		}
		else {
			$transType = 'Auth';
		}

		if ($this->GetValue("testmode") == 'YESLIVE') {
			$trainingMode = 'T';
		}
		else {
			$trainingMode = 'F';
		}

		$billingDetails = $this->GetBillingDetails();

		$request = array(
			'UserName'		=> $this->GetValue('username'),
			'Password'		=> $this->GetValue('password'),
			'TransType'		=> $transType,
			'CardNum'		=> $postData['ccno'],
			'ExpDate'		=> $postData['ccexpm'].$postData['ccexpy'],
			'PNRef'			=> '',
			'MagData'		=> '',
			'NameOnCard'		=> $postData['name'],
			'Amount'		=> number_format($this->GetGatewayAmount(), 2, '.', ''),
			'InvNum'		=> $this->GetCombinedOrderId(),
			'Zip'			=> $billingDetails['ordbillzip'],
			'Street'		=> $billingDetails['ordbillstreet1'].' '.$billingDetails['ordbillstreet2'],
			'CVNum'			=> $postData['cccvd'],
			'ExtData'		=> '<TrainingMode>' . $trainingMode . '</TrainingMode>'
		);

		return http_build_query($request);
	}

	protected function _HandleResponse($result)
	{
		try {
			$xml = new SimpleXMLElement($result);
		} catch (Exception $e) {
			// Something went wrong, show the error message with the credit card form
			$this->SetError(GetLang('PayLeapInvalidRequest'));
			return false;
		}

		$responseCode = '';
		$responseMessage = '';

		if (isset($xml->Result)) {
			$responseCode = (string)$xml->Result;
		}

		if (isset($xml->RespMSG)) {
			$responseMessage = (string)$xml->RespMSG;
		}

		if($responseCode == 0 && $responseMessage == 'Approved') {
			// The order is valid, hook back into the checkout system's validation process

			if($this->GetValue('transtype') == 'SALE') {
				$paymentStatus = 'captured';
			}
			else {
				$paymentStatus = 'authorized';
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

			$extraInfo['PLAuthCode'] = (string)$xml->AuthCode;

			$updatedOrder = array(
				'ordpayproviderid'	=> (string)$xml->PNRef,
				'ordpaymentstatus'	=> $paymentStatus,
				'extrainfo'		=> serialize($extraInfo)
			);

			$this->UpdateOrders($updatedOrder);


			$logMsg = GetLang('PayLeapSuccess', array(
				'orderId' => $this->GetCombinedOrderId()
			));
			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $logMsg);
			return true;
		}
		else {
			$logMessage = GetLang('PayLeapFailure', array(
				'orderId' => $this->GetCombinedOrderId()
			));

			$logDetails = GetLang('PayLeapFailureDetails', array(
				'responseCode' => $responseCode,
				'responseMessage' => $responseMessage
			));

			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $logMessage, $logDetails);

			// Something went wrong, show the error message with the credit card form
			$this->SetError(GetLang('PayLeapInvalidRequest'));
			return false;
		}
	}

	public function DelayedCapture($order, &$message = '', $amt=0)
	{
		if ($this->GetValue("testmode") == 'YESLIVE') {
			$trainingMode = 'T';
		}
		else {
			$trainingMode = 'F';
		}

		if($this->GetValue('testmode') == 'YES') {
			$url = $this->_testTransactionURL.$this->_testTransactionURI;
		}
		else {
			$url = $this->_liveTransactionURL.$this->_liveTransactionURI;
		}

		$extraInfo = @unserialize($order['extrainfo']);
		if (!is_array($extraInfo) || empty($extraInfo['PLAuthCode'])) {
			$message = GetLang('PayLeapCaptureFailedNoAuth');
			return false;
		}

		$request = array(
			'UserName'		=> $this->GetValue('username'),
			'Password'		=> $this->GetValue('password'),
			'TransType'		=> 'Force',
			'Amount'		=> number_format($amt, 2, '.', ''),
			'CardNum'		=> '',
			'ExpDate'		=> '',
			'MagData'		=> '',
			'NameOnCard'		=> '',
			'InvNum'		=> '',
			'Zip'			=> '',
			'Street'		=> '',
			'CVNum'			=> '',
			'PNRef'			=> $order['ordpayproviderid'],
			'ExtData'		=> '<TrainingMode>' . $trainingMode . '</TrainingMode><AuthCode>' . $extraInfo['PLAuthCode'] . '</AuthCode>'
		);

		$response = PostToRemoteFileAndGetResponse($url, http_build_query($request));
		try {
			$xml = new SimpleXMLElement($response);
		}
		catch (Exception $e) {
			// Something went wrong, show the error message.
			$message = GetLang('PayLeapCaptureFailed');
			return false;
		}

		$responseCode = '';
		$responseMessage = '';

		if (isset($xml->Result)) {
			$responseCode = (string)$xml->Result;
		}

		if (isset($xml->RespMSG)) {
			$responseMessage = (string)$xml->RespMSG;
		}

		if($responseCode == 0 && ($responseMessage == 'Approved' || $responseMessage == 'SETTLED')) {
			$message = GetLang('PayLeapPaymentCaptured');

			// remove the auth code
			unset($extraInfo['PLAuthCode']);

			// Mark the order as captured
			$updatedOrder = array(
				'ordpaymentstatus'	=> 'captured',
				'extrainfo'		=> serialize($extraInfo)
			);

			// Update the orders table with new transaction details
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$order['orderid']."'");

			// Log the transaction in store logs
			$logMessage = GetLang('PayLeapPaymentCapturedLogMsg', array(
				'orderId' => $order['orderid']
			));
			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $logMessage);

			return true;
		}
		else {
			$message = GetLang('PayLeapCaptureFailed');

			$logMessage = GetLang('PayLeapCaptureFailedLogMsg', array(
				'orderId' => $order['orderid']
			));

			$logDetails = GetLang('PayLeapCaptureFailedLogDetails', array(
				'paymentReference' => $order['ordpayproviderid'],
				'responseCode' => $responseCode,
				'responseMessage' => $responseMessage
			));

			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $logMessage, $logDetails);
			return false;
		}
	}

	public function DoVoid($orderId, $transactionId, &$message = '')
	{
		if ($this->GetValue("testmode") == 'YESLIVE') {
			$trainingMode = 'T';
		}
		else {
			$trainingMode = 'F';
		}

		$order = GetOrder($orderId);
		$request = array(
			'UserName'		=> $this->GetValue('username'),
			'Password'		=> $this->GetValue('password'),
			'TransType'		=> 'Void',
			'PNRef'			=> $transactionId,
			'CardNum'		=> '',
			'ExpDate'		=> '',
			'MagData'		=> '',
			'NameOnCard' 	=> '',
			'Amount'		=> number_format($order['total_inc_tax'], 2, '.', ''),
			'InvNum'		=> '',
			'Zip'			=> '',
			'Street'		=> '',
			'CVNum'			=> '',
			'ExtData'		=> '<TrainingMode>' . $trainingMode . '</TrainingMode>'
		);

		if($this->GetValue('testmode') == 'YES') {
			$url = $this->_testTransactionURL.$this->_testTransactionURI;
		}
		else {
			$url = $this->_liveTransactionURL.$this->_liveTransactionURI;
		}

		$response = PostToRemoteFileAndGetResponse($url, http_build_query($request));

		try {
			$xml = new SimpleXMLElement($response);
		}
		catch (Exception $e) {
			// Something went wrong, show the error message.
			$message = GetLang('PayLeapVoidFailed');
			return false;
		}

		$responseCode = '';
		$responseMessage = '';

		if (isset($xml->Result)) {
			$responseCode = (string)$xml->Result;
		}

		if (isset($xml->RespMSG)) {
			$responseMessage = (string)$xml->RespMSG;
		}

		if($responseCode == 0 && $responseMessage == 'Approved') {
			$message = GetLang('PayLeapPaymentVoided');

			// Mark the order as captured
			$updatedOrder = array(
				'ordpaymentstatus' => 'void'
			);

			// Update the orders table with new transaction details
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$orderId."'");

			// Log the transaction in store logs
			$logMessage = GetLang('PayLeapPaymentVoidedLogMsg', array(
				'orderId' => $orderId
			));
			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $logMessage);

			return true;
		}
		else {
			$message = GetLang('PayLeapVoidFailed');

			$logMessage = GetLang('PayLeapVoidFailedLogMsg', array(
				'orderId' => $orderId
			));

			$logDetails = GetLang('PayLeapVoidFailedLogDetails', array(
				'paymentReference' => $transactionId,
				'responseCode' => $responseCode,
				'responseMessage' => $responseMessage
			));

			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $logMessage, $logDetails);
			return false;
		}
	}
}