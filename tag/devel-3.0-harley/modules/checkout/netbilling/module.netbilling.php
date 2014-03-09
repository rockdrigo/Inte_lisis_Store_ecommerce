<?php

class CHECKOUT_NETBILLING extends ISC_GENERIC_CREDITCARD
{
	/*
		Checkout class constructor
	*/
	public function __construct()
	{
		// Setup the required variables for the NetBilling checkout module
		$this->_languagePrefix = "NetBilling";
		$this->_id = "checkout_netbilling";
		$this->_image = "netbilling.png";

		parent::__construct();

		//$this->requiresSSL = true;
		$this->_currenciesSupported = array('USD');

		$this->_liveTransactionURL = 'https://secure.netbilling.com:1402';
		$this->_liveTransactionURI = '/gw/sas/direct3.1';
		$this->_curlSupported = true;
		$this->_fsocksSupported = true;
		$this->requireHeaders = true;
		$this->sslVersion = 3;
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

		$this->_variables['seccode'] = array("name" => GetLang($this->_languagePrefix."SecurityCode"),
		   "type" => "textbox",
		   "help" => GetLang($this->_languagePrefix.'SecurityCodeHelp'),
		   "default" => "",
		   "required" => true
		);

		$this->_variables['cardcode'] = array("name" => GetLang($this->_languagePrefix."CardCode"),
		   "type" => "dropdown",
		   "help" => GetLang($this->_languagePrefix.'CardCodeHelp'),
		   "default" => "YES",
		   "required" => true,
		   "options" => array(GetLang($this->_languagePrefix.'CardCodeNo') => "NO",
						  GetLang($this->_languagePrefix.'CardCodeYes') => "YES"
			),
			"multiselect" => false
		);

		$this->_variables['transtype'] = array("name" => GetLang($this->_languagePrefix.'TransactionType'),
		   "type" => "dropdown",
		   "help" => GetLang($this->_languagePrefix.'TransactionTypeHelp'),
		   "default" => "AUTH",
		   "required" => true,
		   "options" => array(GetLang($this->_languagePrefix.'TransactionTypeSale') => "SALE",
						  GetLang($this->_languagePrefix.'TransactionTypeAuth') => "AUTH"
			),
			"multiselect" => false
		);
	}


	protected function _ConstructPostData($postData)
	{
		$billingDetails = $this->GetBillingDetails();
		$shippingAddress = $this->getShippingAddress();

		if ($this->GetValue('transtype') == 'SALE') {
			$transType = 'S';
		}
		else {
			$transType = 'A';
		}

		$ccname 		= $postData['name'];
		$cctype 		= $postData['cctype'];
		$ccissueno 		= $postData['ccissueno'];
		$ccissuedatem 	= $postData['ccissuedatem'];
		$ccissuedatey 	= $postData['ccissuedatey'];
		$ccnum 			= $postData['ccno'];
		$ccexpm 		= $postData['ccexpm'];
		$ccexpy 		= $postData['ccexpy'];
		$cccvd 			= $postData['cccvd'];

		$netBillingPostData = array(
			'account_id' 		=> $this->GetValue('merchantid'),
			'dynip_sec_code'	=> $this->GetValue('seccode'),
			'tran_type'			=> $transType,
			'pay_type'			=> 'C',
			'amount'			=> number_format($this->GetGatewayAmount(), 2, '.', ''),
			'description' 		=> GetLang('NetBillingOrderDescription', array('orderId' => $this->GetOrderIdsString())),
			'card_number' 		=> $ccnum,
			'card_expire' 		=> $ccexpm . $ccexpy,
			'card_cvv2' 		=> $cccvd,
			'card_start_date'	=> $ccissuedatem . $ccissuedatey,
			'card_issue_number'	=> $ccissueno,
			'bill_name1' 		=> $billingDetails['ordbillfirstname'],
			'bill_name2' 		=> $billingDetails['ordbilllastname'],
			'bill_street' 		=> $billingDetails['ordbillstreet1'],
			'bill_city'			=> $billingDetails['ordbillsuburb'],
			'bill_state'		=> $billingDetails['ordbillstate'],
			'bill_zip' 			=> $billingDetails['ordbillzip'],
			'bill_country'	 	=> $billingDetails['ordbillcountry'],
			'ship_name1' 		=> $shippingAddress['first_name'],
			'ship_name2' 		=> $shippingAddress['last_name'],
			'ship_street' 		=> $shippingAddress['address_1'],
			'ship_city'			=> $shippingAddress['city'],
			'ship_state'		=> $shippingAddress['state'],
			'ship_zip' 			=> $shippingAddress['zip'],
			'ship_country'	 	=> $shippingDetails['country'],
			'cust_email'		=> $billingDetails['ordbillemail'],
			'cust_phone'		=> $billingDetails['ordbillphone'],
			'cust_ip'			=> GetIP()
		);

		if ($this->GetValue('cardcode') == 'NO') {
			$netBillingPostData['disable_cvv2'] = 1;
		}

		return http_build_query($netBillingPostData);
	}

	protected function _HandleResponse($result)
	{
		$orderIds = $this->GetOrderIdsString();

		// parse the netbilling response
		try {
			$netBillingResponse = new NETBILLING_RESPONSE($result);
		}
		catch (Exception $ex) {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('NetBillingFailure', array('orderId' => $orderIds)), $ex->getMessage());
			$this->SetError($ex->getMessage());
			return false;
		}

		// successfull transaction
		if ($netBillingResponse->approved) {
			$paymentStatus = $netBillingResponse->paymentStatus;

			if ($paymentStatus == 'captured') {
				$paymentStatusString = GetLang('NetBillingPaymentStatusCaptured');
			}
			else {
				$paymentStatusString = GetLang('NetBillingPaymentStatusAuthorized');
			}

			// update our order with the transaction id
			$updatedOrder = array(
				'ordpayproviderid' => $netBillingResponse->transactionId,
				'ordpaymentstatus' => $paymentStatus
			);
			$this->UpdateOrders($updatedOrder);

			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang('NetBillingSuccess', array('orderId' => $orderIds)), GetLang('NetBillingSuccessDetails', array('transId' => $netBillingResponse->transactionId, 'paymentStatus' => $paymentStatusString)));
			return true;
		}
		else {
			// transaction failed
			$errorMessage = $netBillingResponse->errorMessage;

			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('NetBillingFailure', array('orderId' => $orderIds)), GetLang('NetBillingFailureDetails', array('transId' => $netBillingResponse->transactionId, 'authMsg' => $netBillingResponse->authMessage, 'message' => $errorMessage)));
			$this->SetError($errorMessage);
			return false;
		}
	}

	public function DelayedCapture($order, &$message = '', $amount = 0)
	{
		$transactionId = $order['ordpayproviderid'];
		$orderId = $order['orderid'];

		$netBillingPostData = array(
			'account_id' 		=> $this->GetValue('merchantid'),
			'dynip_sec_code'	=> $this->GetValue('seccode'),
			'tran_type'			=> 'D',
			'pay_type'			=> 'C',
			'orig_id'			=> $transactionId,
			'amount'			=> number_format($amount, 2, '.', '')
		);

		$url = $this->_liveTransactionURI;
		$result = $this->_ConnectToProvider($this->_liveTransactionURL, $this->_liveTransactionURI, http_build_query($netBillingPostData));

		// parse the netbilling response
		try {
			$netBillingResponse = new NETBILLING_RESPONSE($result);
		}
		catch (Exception $ex) {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('NetBillingCaptureFailure', array('orderId' => $orderId)), $ex->getMessage());
			$message = GetLang('NetBillingFlashCaptureFailed', array('message' => $ex->getMessage()));
			return false;
		}

		// successfull transaction
		if ($netBillingResponse->approved && $netBillingResponse->paymentStatus == 'captured') {
			$message = GetLang('NetBillingFlashCaptured');

			$paymentStatusString = GetLang('NetBillingPaymentStatusCaptured');

			// update our order
			$updatedOrder = array(
				'ordpaymentstatus' => 'captured'
			);
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$orderId."'");

			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang('NetBillingCaptureSuccess', array('orderId' => $orderId)), GetLang('NetBillingCaptureSuccessDetails', array('transId' => $netBillingResponse->transactionId, 'paymentStatus' => $paymentStatusString)));
			return true;
		}
		else {
			// transaction failed
			$errorMessage = $netBillingResponse->errorMessage;

			$message = GetLang('NetBillingFlashCaptureFailed', array('message' => $errorMessage));

			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('NetBillingCaptureFailure', array('orderId' => $orderIds)), GetLang('NetBillingCaptureFailureDetails', array('transId' => $netBillingResponse->transactionId, 'authMsg' => $netBillingResponse->authMessage, 'message' => $errorMessage)));
			$this->SetError($errorMessage);
			return false;
		}
	}

	public function DoRefund($order, &$message = '', $amount = 0)
	{
		if ($amount <= 0) {
			$message = GetLang('NetBillingRefundInvalidAmount');
			return false;
		}

		$transactionId = $order['ordpayproviderid'];
		$orderId = $order['orderid'];
		$refundAmount = number_format($amount, 2, '.', '');
		$orderAmount = $order['total_inc_tax'];
		$totalRefundAmount = $amount + $order['ordrefundedamount'];

		$netBillingPostData = array(
			'account_id' 		=> $this->GetValue('merchantid'),
			'dynip_sec_code'	=> $this->GetValue('seccode'),
			'tran_type'			=> 'R',
			'pay_type'			=> 'C',
			'orig_id'			=> $transactionId,
			'amount'			=> $refundAmount,
			'description'		=> GetLang('NetBillingRefundDescription', array('amount' => $refundAmount, 'orderId' => $orderId))
		);

		$url = $this->_liveTransactionURI;
		$result = $this->_ConnectToProvider($this->_liveTransactionURL, $this->_liveTransactionURI, http_build_query($netBillingPostData));

		// parse the netbilling response
		try {
			$netBillingResponse = new NETBILLING_RESPONSE($result);
		}
		catch (Exception $ex) {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('NetBillingRefundErrorLog', array('orderId' => $orderId)), $ex->getMessage());
			$message = GetLang('NetBillingRefundError', array('message' => $ex->getMessage()));
			return false;
		}


		if ($netBillingResponse->approved) {
			$message = GetLang('NetBillingRefundSuccess');

			if ($totalRefundAmount < $orderAmount) {
				$paymentStatus = 'partially refunded';
			}
			else {
				$paymentStatus = 'refunded';
			}

			$updatedOrder = array(
				'ordpaymentstatus' => $paymentStatus,
				'ordrefundedamount'	=> $totalRefundAmount,
			);

			//update the orders table with new transaction details
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$orderId."'");

			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment',  $this->GetName()), GetLang('NetBillingRefundSuccessLog', array('orderId' => $orderId)), GetLang('NetBillingRefundSuccessLogDetails', array('transId' => $netBillingResponse->transactionId, 'amount' => $refundAmount)));
			return true;
		}
		else {
			$message = GetLang('NetBillingRefundError', array('message' => $netBillingResponse->errorMessage));
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment',  $this->GetName()), GetLang('NetBillingRefundErrorLog', array('orderId' => $orderId)), $netBillingResponse->errorMessage);
			return false;
		}
	}
}

class NETBILLING_RESPONSE {
	public $approved = false;
	public $paymentStatus = '';
	public $errorMessage = '';

	public $statusCode;
	public $transactionId;
	public $authCode;
	public $authDate;
	public $authMessage;

	public function __construct($result)
	{
		// check for a NetBilling exception in http header
		if (preg_match("|HTTP/1.[0-1]{1} ([6-9][0-9][0-9]) (.*)|", $result, $matches)) {
			$httpCode = $matches[1];
			$message = $matches[2];

			throw new NETBILLING_RESPONSE_INVALIDRESPONSE_EXCEPTION($message);
		}

		$response = array();
		parse_str($result, $response);

		// check for these fields in our response
		$responseFields = array('status_code', 'trans_id', 'auth_code', 'auth_date', 'auth_msg'); // 'avs_code', 'cvv2_code'
		foreach ($responseFields as $field) {
			if (!isset($response[$field])) {
				throw new NETBILLING_RESPONSE_MISSINGFIELD_EXCEPTION($field);
			}
		}

		$this->statusCode 		= $response['status_code'];
		$this->transactionId	= $response['trans_id'];
		$this->authCode			= $response['auth_code'];
		$this->authDate			= $response['auth_date'];
		$this->authMessage		= $response['auth_msg'];

		// Failed transaction
		if($this->statusCode == '0' || $this->statusCode == 'F') {
			if($this->authMessage == 'BAD ADDRESS') {
				$this->errorMessage = GetLang('NetBillingErrorBadAddress');
			}
			else if($this->authMessage == 'CVV2 MISMATCH') {
				$this->errorMessage = GetLang('NetBillingErrorCVV2');
			}
			else if($this->authMessage == 'A/DECLINED') {
				$this->errorMessage = GetLang('NetBillingErrorADeclined');
			}
			else if($this->authMessage == 'B/DECLINED') {
				$this->errorMessage = GetLang('NetBillingErrorBDeclined');
			}
			else if($this->authMessage == 'C/DECLINED') {
				$this->errorMessage = GetLang('NetBillingErrorCDeclined');
			}
			else if($this->authMessage == 'E/DECLINED') {
				$this->errorMessage = GetLang('NetBillingErrorEDeclined');
			}
			else if($this->authMessage == 'J/DECLINED') {
				$this->errorMessage = GetLang('NetBillingErrorJDeclined');
			}
			else if($this->authMessage == 'L/DECLINED') {
				$this->errorMessage = GetLang('NetBillingErrorLDeclined');
			}
			else {
				$this->errorMessage = GetLang('NetBillingErrorGeneric');
			}
		}
		// Duplicate transaction
		elseif($this->statusCode == 'D') {
			$this->errorMessage = GetLang('NetBillingErrorDuplicate');
		}
		// Successfull auth only transaction
		elseif ($this->statusCode == 'T') {
			$this->approved = true;
			$this->paymentStatus = 'authorized';
		}
		// Successfull monetary transaction
		elseif ($this->statusCode == '1') {
			$this->approved = true;
			$this->paymentStatus = 'captured';
		}
	}
}

class NETBILLING_RESPONSE_INVALIDRESPONSE_EXCEPTION extends Exception {
	public function __construct($message)
	{
		parent::__construct(GetLang('NetBillingInvalidResponseException', array('message' => $message)));
	}
}

class NETBILLING_RESPONSE_MISSINGFIELD_EXCEPTION extends Exception {
	public function __construct($field)
	{
		parent::__construct(GetLang('NetBillingMissingFieldException', array('field' => $field)));
	}
}
