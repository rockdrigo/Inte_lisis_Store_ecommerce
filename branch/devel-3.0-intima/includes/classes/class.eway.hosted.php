<?php
/**
* This is the base eWay Hosted payment module.
*/
class ISC_EWAY_HOSTED extends ISC_CHECKOUT_PROVIDER {

	protected $_ewayURL = '';

	public function __construct()
	{
		parent::__construct();
		$this->setName(GetLang('EwayName'));
		$this->setImage("eway_logo.gif");
		$this->setDescription(GetLang('EwayDesc'));
		$this->SetHelpText(GetLang('EwayHelp'));
	}

	public function SetCustomVars()
	{
		$this->_variables['displayname'] = array(
			"name" => GetLang('DisplayName'),
			"type" => "textbox",
			"help" => GetLang('DisplayNameHelp'),
			"default" => $this->GetName(),
			"required" => true
		);

		$this->_variables['customerid'] = array(
			"name" => GetLang('EwayCustomerID'),
			"type" => "textbox",
			"help" => GetLang('EwayCustomerIDHelp'),
			"default" => "",
			"required" => true
		);

		$this->_variables['username'] = array(
			"name" => GetLang('EwayUserName'),
			"type" => "textbox",
			"help" => GetLang('EwayUserNameHelp'),
			"default" => "",
			"required" => true
		);
	}

	public function TransferToProvider()
	{
		$currency = GetDefaultCurrency();
		$currencyCode = $currency['currencycode'];

		$orders = $this->GetOrders();
		list(,$order) = each($orders);

		$amount = number_format($this->GetGatewayAmount(), '2');

		$billingDetails = $this->GetBillingDetails();

		$invoiceDescription = '';
		$quote = getCustomerQuote();
		foreach ($quote->getItems() as /** @var ISC_QUOTE_ITEM */$item){
			if ($invoiceDescription) {
				$invoiceDescription .= ", ";
			}

			$invoiceDescription .= $item->getQuantity() . 'x ' . $item->getName();
		}

		$data = array(
			'CustomerID'		=> $this->GetValue('customerid'),
			'UserName'			=> $this->GetValue('username'),
			'Currency'			=> $currencyCode,
			'Amount'			=> $amount,
			'ReturnURL'			=> $GLOBALS['ShopPath'] . '/finishorder.php',
			'CancelURL'			=> $GLOBALS['ShopPath'] . '/finishorder.php',
			'CompanyName'		=> GetConfig('CompanyName'),

			'CustomerFirstName'	=> $billingDetails['ordbillfirstname'],
			'CustomerLastName'	=> $billingDetails['ordbilllastname'],
			'CustomerAddress'	=> $billingDetails['ordbillstreet1'] . ' ' . $billingDetails['ordbillstreet2'],
			'CustomerCity'		=> $billingDetails['ordbillsuburb'],
			'CustomerState'		=> $billingDetails['ordbillstate'],
			'CustomerPostCode'	=> $billingDetails['ordbillzip'],
			'CustomerCountry'	=> $billingDetails['ordbillcountry'],
			'CustomerPhone'		=> $billingDetails['ordbillphone'],
			'CustomerEmail'		=> $billingDetails['ordbillemail'],

			'InvoiceDescription'=> $invoiceDescription,
			'MerchantReference' => $this->GetCombinedOrderId(),
		);

		$ewayUrl = $this->_ewayURL . 'Request?';
		$ewayUrl .= http_build_query($data);

		$response = PostToRemoteFileAndGetResponse($ewayUrl);

		if (empty($response)) {
			$this->logInvalidResponse($response, true);
		}

		try {
			$xml = new SimpleXMLElement($response);
		}
		catch (Exception $ex) {
			$this->logInvalidResponse($response, true);
		}

		if ((string)$xml->Result == 'True') {
			$transferUri = (string)$xml->URI;
			$this->RedirectToProvider($transferUri);
		}
		else {
			$this->logInvalidResponse((string)$xml->Error(), true);
		}
	}

	public function VerifyOrderPayment()
	{
		if (empty($_POST['AccessPaymentCode'])) {
			return false;
		}

		$accessPaymentCode = $_POST['AccessPaymentCode'];

		$data = array(
			'CustomerID'		=> $this->GetValue('customerid'),
			'UserName'			=> $this->GetValue('username'),
			'AccessPaymentCode'	=> $accessPaymentCode,
		);

		$verifyUrl = $this->_ewayURL . 'Result?';
		$verifyUrl .= http_build_query($data);

		$response = PostToRemoteFileAndGetResponse($verifyUrl);

		if (empty($response)) {
			$this->logInvalidResponse($response);
			return false;
		}

		try {
			$xml = new SimpleXMLElement($response);
		}
		catch (Exception $ex) {
			$this->logInvalidResponse($response);
			return false;
		}

		$amount = (string)$xml->ReturnAmount;
		$orderId = (string)$xml->MerchantReference;
		$responseCode = (string)$xml->ResponseCode;
		$transactionId = (string)$xml->TrxnNumber;
		$transactionMessage = (string)$xml->TrxnResponseMessage;
		$transactionStatus = (string)$xml->TrxnStatus;
		$errorMessage = (string)$xml->ErrorMessage;

		$expectedAmount = number_format($this->GetGatewayAmount(), '2');

		$transactionLogDetails = array(
			'responseCode'		=> $responseCode,
			'transactionNumber'	=> $transactionId,
			'transactionMessage'=> $transactionMessage,
			'errorMessage'		=> $errorMessage,
		);

		// transaction failed or payment details don't match
		if ($transactionStatus == 'false' || $orderId != $this->GetCombinedOrderId() || $amount != $expectedAmount) {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(
				array('payment', $this->GetName()),
				GetLang('EwayFailure', array('orderId' => $this->GetCombinedOrderId())),
				GetLang('EwayTransactionDetailsFailure', $transactionLogDetails)
			);

			$this->SetPaymentStatus(PAYMENT_STATUS_DECLINED);
			return false;
		}

		// set the payment status
		$updatedOrder = array(
			'ordpayproviderid' => $transactionId
		);

		$this->UpdateOrders($updatedOrder);

		$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(
			array('payment', $this->GetName()),
			GetLang('EwaySuccess', array('orderId' => $this->GetCombinedOrderId())),
			GetLang('EwayTransactionDetailsSuccess', $transactionLogDetails)
		);
		$this->SetPaymentStatus(PAYMENT_STATUS_PAID);

		return true;
	}

	private function logInvalidResponse($response, $redirectToConfirmation = false)
	{
		$GLOBALS['ISC_CLASS_LOG']->LogSystemError(
			array('payment', $this->GetName()),
			GetLang('EwayFailureInvalid', array('orderId' => $this->GetCombinedOrderId())),
			isc_html_escape($response)
		);

		if ($redirectToConfirmation) {
			$this->RedirectToOrderConfirmation(GetLang('EwayErrorProcessing'));
		}
	}
}
