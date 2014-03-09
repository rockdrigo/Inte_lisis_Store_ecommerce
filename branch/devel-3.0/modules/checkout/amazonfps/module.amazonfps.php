<?php

class CHECKOUT_AMAZONFPS extends ISC_CHECKOUT_PROVIDER
{
	/*
		Does this payment provider require SSL?
	*/
	protected $requiresSSL = false;

	public $_id = "checkout_amazonfps";

	protected $_currenciesSupported = array('USD');
	public $_languagePrefix = 'AmazonFps';


	/*
		Checkout class constructor
	*/
	public function __construct()
	{
		// Setup the required variables for the AmazonFps checkout module
		parent::__construct();
		$this->_name = GetLang('AmazonFpsName');
		$this->_image = "apmark_180x110.jpg";
		$this->_description = GetLang('AmazonFpsDesc');
		$openPopup = 'openPopup("../modules/checkout/amazonfps/lib/amazontokengenerator.php", "'.GetLang('AmazonFpsName').'");';
		$this->_help = sprintf(GetLang($this->_languagePrefix.'Help'), $openPopup);	// Help Message
		$this->_height = 0;
	}

	public function IsSupported()
	{
		$currencycode = GetDefaultCurrency();
		$currencycode = $currencycode['currencycode'];

		if (!in_array($currencycode, $this->_currenciesSupported)) {
			$this->SetError(GetLang('AmazonFpsCurrecyNotSupported'));
		}

		if ($this->RequiresSSL()) {
			if(!GetConfig('UseSSL')) {
				$this->SetError(GetLang('AmazonFpsNoSSLError'));
			}
		}

		if(!function_exists("curl_exec")) {
			$this->SetError(GetLang('CreditCardCurlRequired'));
		}

		if($this->HasErrors()) {
			return false;
		}
		else {
			return true;
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

		$this->_variables['accessid'] = array("name" => GetLang($this->_languagePrefix."AccessId"),
		   "type" => "textbox",
		   "help" => GetLang('AmazonFpsAccessIdHelp'),
		   "default" => "",
		   "required" => true
		);

		$this->_variables['secretkey'] = array("name" => GetLang($this->_languagePrefix."SecretWord"),
		   "type" => "password",
		   "help" => GetLang('AmazonFpsSecretWordHelp'),
		   "default" => "",
		   "required" => true
		);

		$this->_variables['callertoken'] = array("name" => GetLang($this->_languagePrefix."CallerToken"),
		   "type" => "textbox",
		   "help" => GetLang('AmazonFpsCallerTokenHelp'),
		   "default" => "",
		   "required" => true
		);

		$this->_variables['receipttoken'] = array("name" => GetLang($this->_languagePrefix."RecipientToken"),
		   "type" => "textbox",
		   "help" => GetLang('AmazonFpsRecipientTokenHelp'),
		   "default" => "",
		   "required" => true
		);

		$this->_variables['testmode'] = array("name" => GetLang($this->_languagePrefix."TestMode"),
		   "type" => "dropdown",
		   "help" => GetLang('AmazonFpsTestModeHelp'),
		   "default" => "no",
		   "required" => true,
		   "options" => array(GetLang('AmazonFpsTestModeNo') => "NO",
						  GetLang('AmazonFpsTestModeYes') => "YES"
			),
			"multiselect" => false
		);
	}

	/**
	*	Redirect the customer to AmazonFps's site to enter their payment details
	*/
	public function TransferToProvider()
	{
		$total = $this->GetGatewayAmount();
		$transactionid = $this->GetCombinedOrderId();

		$paymentHash = md5($this->GetValue("accessid").$transactionid.$_COOKIE['SHOP_ORDER_TOKEN'].$total.$this->GetValue("callertoken").$this->GetValue("receipttoken"));

		$returnUrl = $GLOBALS['ShopPath'].'/finishorder.php?provider='.$this->GetId();
		$returnUrl .= "&Order=" . $transactionid;
		$returnUrl .= "&Key=" . $paymentHash;
		$returnUrl .= "&SessionId=" . $_COOKIE['SHOP_ORDER_TOKEN'];
		$returnUrl .= "&PaymentAmount=" . $total;
		$returnUrl .= "&CallerTokenId=" . $this->GetValue("callertoken");
		$returnUrl .= "&RecipientTokenId=" . $this->GetValue("receipttoken");

		if ($this->GetValue('testmode') == "YES") {
			$url = 'https://authorize.payments-sandbox.amazon.com/cobranded-ui/actions/start';
		}
		else {
			$url = 'https://authorize.payments.amazon.com/cobranded-ui/actions/start';
		}

		$params = array(
			'callerKey'			=> $this->getValue('accessid'),
			'pipelineName'		=> 'SingleUse',
			'returnURL'			=> $returnUrl,
			'callerReference'	=> 'SenderToken-'.$transactionid.'-'.microtime(true),
			'paymentReason'		=> 'Payment for Order id : '.$transactionid,
			'transactionAmount'	=> $total,
		);

		$cbuiURL = $url . '?' . self::getSignedParamString($url, $params, 'GET');

		header('Location: '.$cbuiURL);
	}

	public function getSignedParamString($url, array $params, $method)
	{
		$params[Amazon_FPS_SignatureUtils::SIGNATURE_VERSION_KEYNAME] = 2;
		$params[Amazon_FPS_SignatureUtils::SIGNATURE_METHOD_KEYNAME] = Amazon_FPS_SignatureUtils::HMAC_SHA256_ALGORITHM;

		$urlInfo = parse_url($url);
		$secret = $this->GetValue('secretkey');
		$signature = Amazon_FPS_SignatureUtils::signParameters($params, $secret,
			$method, $urlInfo['host'], $urlInfo['path']);

		$params[Amazon_FPS_SignatureUtils::SIGNATURE_KEYNAME] = $signature;

		return Amazon_FPS_SignatureUtils::_getParametersAsString($params);
	}

	/**
	*	Return the unique order token which was saved as a cookie pre-payment
	*/
	public function GetOrderToken()
	{
		return @$_REQUEST['SessionId'];
	}

	public function VerifyOrderPayment()
	{
		$callertoken = $_REQUEST['CallerTokenId'];
		$receipttoken = $_REQUEST['RecipientTokenId'];
		$sendertoken = $_REQUEST['tokenID'];
		$status = $_REQUEST['status'];
		$orderid = $_REQUEST['Order'];
		$key = $_REQUEST['Key'];
		$sessionId = $_REQUEST['SessionId'];
		$amount = $_REQUEST['PaymentAmount'];

		if(empty($status)) {
			return false;
		}

		if (!in_array(isc_strtoupper($status), array('SA', 'SB', 'SC'))) {

			$amazonStatusCodes = $this->getStatusCodes();

			if(isset($amazonStatusCodes[$status])) {
				$amazonSaid = "Amazon Said: ". $amazonStatusCodes[$status];
			} else {
				$amazonSaid = "Unknown status '" . isc_htmlencode($status) ."' returned from Amazon.";
			}
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('AmazonFpsPaymentError'), 'Status returned unsuccessful. '. $amazonSaid . '<br />' . '<pre>' . isc_htmlencode(var_export($_REQUEST, true)) . '</pre>');
			return false;
		}

		if ($this->GetCombinedOrderId() != $orderid) {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('AmazonFpsErrorOrderId'), '<pre>' . isc_htmlencode(var_export($_REQUEST, true)) . '</pre>');
			return false;
		}

		if ($this->GetGatewayAmount() != $amount) {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('AmazonFpsErrorGatewayAmount'), '<pre>' . isc_htmlencode(var_export($_REQUEST, true)) . '</pre>');
			return false;
		}

		if (md5($this->GetValue("accessid").$orderid.$sessionId.$amount.$callertoken.$receipttoken) != $key) {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('AmazonFpsErrorHash'), '<pre>' . isc_htmlencode(var_export($_REQUEST, true)) . '</pre>');
			return false;
		}

		$chargeFeeTo = 'Recipient';
		$date = date('Y-m-d')."T".date('H:i:s');
		$callerReference = 'Order-'.$orderid.microtime(true);
		$timestamp = gmdate("Y-m-d\TH:i:s\Z");

		$params = array(
			'Action' => 'Pay',
			'CallerTokenId' => $callertoken,
			'SenderTokenId' => $sendertoken,
			'RecipientTokenId' => $receipttoken,
			'TransactionAmount.Amount' => round($amount,2),
			'TransactionAmount.CurrencyCode' => 'USD',
			'TransactionDate' => $date,
			'ChargeFeeTo' => $chargeFeeTo,
			'CallerReference' => $callerReference,
			'Timestamp' => $timestamp,
			'Version' => '2007-01-08',
			'AWSAccessKeyId' => $this->GetValue('accessid'),
		);

		if ($this->GetValue('testmode') == "YES") {
			$url = 'https://fps.sandbox.amazonaws.com/';
		}
		else {
			$url = 'https://fps.amazonaws.com/';
		}

		if(function_exists("curl_exec")) {

			// Use CURL if it's available
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getSignedParamString($url, $params, 'POST'));
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

			if(curl_errno($ch)) {
				$this->SetError(GetLang($this->_languagePrefix."SomethingWentWrong") . $this->GetValue('displayname') . ":" .curl_error($ch));
				return false;
			}
		}

		if (!empty($result)) {
			$xml = new SimpleXMLElement($result);
		}
		else {
			$this->SetError(GetLang($this->_languagePrefix."SomethingWentWrong") . $this->GetValue('displayname'));
			return false;
		}


		$transaction = GetClass('ISC_TRANSACTION');

		$previousTransaction = $transaction->LoadByTransactionId($sendertoken, $this->GetId());

		// Already processed before, HALT and log error
		if(is_array($previousTransaction) && $previousTransaction['transactionid']) {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang('AmazonFpsAlreadyProcessed'), $sendertoken));
			return false;
		}

		$newTransaction = array(
			'providerid' => $this->GetId(),
			'transactiondate' => time(),
			'transactionid' => $sendertoken,
			'orderid' => array_keys($this->GetOrders()),
			'message' => '',
			'status' => '',
			'amount' => $amount,
			'extrainfo' => array()
		);

		if ($xml->Status == 'Failure') {
			$this->SetError("Status : " . $xml->Status . ":" . $xml->Errors->Errors->ReasonText);
			$newTransaction['status'] = TRANS_STATUS_FAILED;
			$newTransaction['message'] = (string)$xml->Errors->Errors->ReasonText;
			$transactionId = $transaction->Create($newTransaction);
			return false;
		}

		if ($xml->Status == 'Success') {

			$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang('AmazonFpsSuccess'));
			$this->SetPaymentStatus(PAYMENT_STATUS_PAID);
			$newTransaction['status'] = TRANS_STATUS_COMPLETED;
			$newTransaction['message'] = 'Success';
			$transactionId = $transaction->Create($newTransaction);
			return true;
		}

		return false;
	}

	public function getStatusCodes()
	{
		$codes = array(
			'SA' => 'Success status for the ABT payment method.',
			'SB' => 'Success status for the ACH (bank account) payment method.',
			'SC' => 'Success status for the credit card payment method.',
			'SE' => 'System error.',
			'A' => 'Buyer abandoned the pipeline.',
			'CE' => 'Specifies a caller exception.',
			'PE' => 'Payment Method Mismatch Error: Specifies that the buyer does not have the payment method you requested.',
			'NP' => 'There are four cases where the NP status is returned:
* The payment instruction installation was not allowed on the sender\'s account, because the sender\'s email account is not verified
* The sender and the recipient are the same
* The recipient account is a personal account, and therefore cannot accept credit card payments
* A user error occurred because the pipeline was cancelled and then restarted',
			'NM' => 'You are not registered as a third-party caller to make this transaction. Contact Amazon Payments for more information.',
		);

		return $codes;
	}
}