<?php
class CHECKOUT_SECUREPAY extends ISC_GENERIC_CREDITCARD
{
	protected $_languagePrefix = 'SecurePay';

	protected $requiresSSL = true;

	protected $_currenciesSupported = array(
		'AUD',
		'CAD',
		'CHF',
		'DEM',
		'EUR',
		'FRF',
		'GBP',
		'GRD',
		'HKD',
		'ITL',
		'JPY',
		'NZD',
		'SGD',
		'USD'
	);

	protected $_cardsSupported = array(
		'VISA',
		'MC',
		'AMEX',
		'DINERS',
		'JCB'
	);

	protected $_liveTransactionURL = 'https://www.securepay.com.au';
	protected $_testTransactionURL = 'https://www.securepay.com.au';

	protected $_liveTransactionURI = '/xmlapi/payment';
	protected $_testTransactionURI = '/test/payment';

	protected $_curlSupported = true;
	protected $_fsocksSupported = true;
	protected $cardCodeRequired = true;

	/**
	 * The constructor.
	 */
	public function __construct()
	{
		$this->SetImage('securepay.gif');
		parent::__construct();
	}

	/**
	 * Setup the configurable options for this payment module.
	 *
	 * @return array Array of configurable options.
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

		$this->_variables['password'] = array("name" => GetLang($this->_languagePrefix.'MerchantPassword'),
		   "type" => "password",
		   "help" => GetLang($this->_languagePrefix.'MerchantPasswordHelp'),
		   "default" => "",
		   "required" => true
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

		$this->_variables['fraudguard'] = array(
			'type' => 'dropdown',
			'name' => GetLang($this->_languagePrefix.'FraudGuard'),
			'help' => GetLang($this->_languagePrefix.'FraudGuardHelp'),
			'default' => 0,
			'required' => true,
			'options' => array(
				GetLang($this->_languagePrefix.'FraudGuardNo') => 0,
				GetLang($this->_languagePrefix.'FraudGuardYes') => 1
			),
			'multiselect' => false
		);
	}

	protected function _ConstructPostData($postData)
	{
		$transactionid	= $this->GetCombinedOrderId();

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
			case 'JCB':
				$cctype = '1';
				break;
			default:
				$cctype = '0';
				break;
		}

		$timestamp = strftime("%Y%d%m%H%M%S000000%z");

		// A list of currencies that if is the store default, we don't need to convert to
		// cents
		$simpleCurrencies = array(
			'GRD',
			'ITL',
			'JPY'
		);
		$currency = GetCurrencyById($this->GetBaseCurrency());
		if(in_array($currency['currencycode'], $simpleCurrencies)) {
			$amount = $this->GetGatewayAmount();
		}
		else {
			$amount = number_format($this->GetGatewayAmount()*100,0,'','');
		}

		if($this->GetValue('fraudguard')) {
			$txnType = 21;
			$this->_testTransactionURI = '/antifraud_test/payment';
			$this->_liveTransactionURI = '/antifraud/payment';
		}
		else {
			$txnType = 0;
		}

		$amount = number_format($this->GetGatewayAmount()*100, 0, '', '');
		$currency = 'AUD';
		$expiryDate = str_pad($postData['ccexpm'], 2, '0', STR_PAD_LEFT).'/'.str_pad($postData['ccexpy'], 2, '0', STR_PAD_LEFT);
		$billingDetails = $this->GetBillingDetails();

		$xml = new SimpleXMLElement('<SecurePayMessage />');

		$messageInfo = $xml->addChild('MessageInfo');
		$messageInfo->addChild('messageID', md5($transactionid));
		$messageInfo->addChild('messageTimestamp', $timestamp);
		$messageInfo->addChild('timeoutValue', 60);
		$messageInfo->addChild('apiVersion', 'xml-4.2');

		$merchantInfo = $xml->addChild('MerchantInfo');
		$merchantInfo->addChild('merchantID', $this->GetValue('merchantid'));
		$merchantInfo->addChild('password', $this->GetValue('password'));

		$xml->addChild('RequestType', 'Payment');

		$payment = $xml->addChild('Payment');

		$txnList = $payment->addChild('TxnList');
		$txnList->addAttribute('count', 1);

		$txn = $xml->addChild('Txn');
		$txn->addAttribute('ID', 1);
		$txn->addChild('txnType', $txnType);
		$txn->addChild('txnSource', 23);
		$txn->addChild('amount', $amount);
		$txn->addChild('currency', $currency);
		$txn->addChild('purchaseOrderNo', $transactionid);

		$cardInfo = $txn->addChild('CreditCardInfo');
		$cardInfo->addChild('cardNumber', $postData['ccno']);
		$cardInfo->addChild('expiryDate', $expiryDate);
		$cardInfo->addChild('cardType', $cctype);
		if($this->CardTypeRequiresCVV2($postData['cctype'])) {
			$cardInfo->addChild('cvv', $postData['cccvd']);
		}

		$buyerInfo = $txn->addChild('BuyerInfo');
		$buyerInfo->addChild('firstName', $billingDetails['ordbillfirstname']);
		$buyerInfo->addChild('lastName', $billingDetails['ordbilllastname']);
		$buyerInfo->addChild('ipcode', $billingDetails['ordbillzip']);
		$buyerInfo->addChild('town', $billingDetails['ordbillsuburb']);
		$buyerInfo->addChild('billingCountry', $billingDetails['ordbillcountrycode']);
		$buyerInfo->addChild('emailAddress', $billingDetails['ordbillemail']);
		$buyerInfo->addChild('ip', $this->GetIpAddress());

		return $xml->asXML();
	}

	protected function _HandleResponse($result)
	{
		if (empty($result)) {
			$logMessage = GetLang('SecurePayConnectionFailure', array(
				'orderId' => $this->GetCombinedOrderId()
			));
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $logMessage);
			$this->SetError(GetLang('SecurePayProblemProcessingTransaction'));
			return false;
		}

		try {
			$xml = new SimpleXMLElement($result);
		}
		catch(Exception $e) {
			$logMessage = GetLang('SecurePayResponseInvalid', array(
				'orderId' => $this->GetCombinedOrderId()
			));
			$logDetails = $e->getMessage()."<br /><pre>".isc_html_escape($result).'</pre>';
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $logMessage, $logDetails);
			$this->SetError(GetLang('SecurePayProblemProcessingTransaction'));
			return false;
		}

		// We need to have a status code in the response of 000 for this transaction to have been successful
		if(!isset($xml->Status->statusCode) || $xml->Status->statusCode != '000') {
			$logMessage = GetLang('SecurePayResponseInvalid', array(
				'orderId' => $this->GetCombinedOrderId()
			));
			if(!isset($xml->Status->statusCode)) {
				$logDetails = '<pre>'.isc_html_escape($result).'</pre>';
			}
			else {
				$logDetails = $xml->Status->statusCode.': '.$xml->Status->statusDescription;
			}
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $logMessage, $logDetails);
			$this->SetError(GetLang('SecurePayProblemProcessingTransaction'));
			return false;
		}

		$txn = $xml->xpath('//Txn[@ID=1]');
		$txn = $txn[0];

		// If there's a fraud result and it's not good, then obviously the transaction can't be processed
		if(isset($txn->antiFraudResponseCode) && $txn->antiFraudResponseCode != '000') {
			$logMessage = GetLang('SecurePayFraudGuardFailed', array(
				'orderId' => $this->GetCombinedOrderId()
			));
			$logDetails = $txn->antiFraudResponseCode.': '.$txn->antiFraudResponseText;
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $logMessage, $logDetails);
			$this->SetError(GetLang('SecurePayProblemProcessingTransaction'));
			return false;
		}

		// Payment was not approved
		if(strtolower($txn->approved) != 'yes') {
			$logMessage = GetLang('SecurePayPaymentNotApproved', array(
				'orderId' => $this->GetCombinedOrderId()
			));
			$logDetails = $txn->antiFraudResponseCode.': '.$txn->antiFraudResponseText;

			$message = $xml->StatusCode.': '.$xml->StatusCode->statusDescription;
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $logMessage, $logDetails);
			$this->SetError(GetLang('SecurePayProblemProcessingTransaction'));
			return false;
		}

		$logMessage = GetLang('SecurePaySuccess', array(
			'orderId' => $this->GetCombinedOrderId()
		));

		$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $logMessage);
		return true;
	}
}