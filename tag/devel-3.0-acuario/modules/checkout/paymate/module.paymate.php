<?php

class CHECKOUT_PAYMATE extends ISC_CHECKOUT_PROVIDER
{
	/**
	 * The constructor.
	 */
	public function __construct()
	{
		// Setup the required variables for the PayMate checkout module
		parent::__construct();
		$this->SetName(GetLang('PayMateName'));
		$this->SetImage('paymate_logo.jpg');
		$this->SetDescription(GetLang('PayMateDesc'));
		$this->SetHelpText(sprintf(GetLang("PayMateHelp"), $GLOBALS['ShopPathSSL']));

		$this->setSupportedCurrencies(array('AUD', 'NZD', 'USD', 'GBP', 'EUR'));
	}

	/**
	 * Set up the configurable options for this module.
	 */
	public function SetCustomVars()
	{

		$this->_variables['displayname'] = array(
			"name" => "Display Name",
			"type" => "textbox",
			"help" => GetLang("DisplayNameHelp"),
			"default" => $this->GetName(),
			"required" => true
		);

		$this->_variables['username'] = array(
			"name" => "PayMate Username",
			"type" => "textbox",
			"help" => GetLang("PayMateUserNameHelp"),
			"default" => "",
			"required" => true
		);

		$this->_variables['declinemethod'] = array(
			"name" => "Declined Orders",
			"type" => "dropdown",
			"help" => GetLang("PayMateDeclineMethodHelp"),
			"default" => 1,
			"required" => true,
			"options" => array(
				GetLang('PayMateDeclineMethod1') => 1,
				GetLang('PayMateDeclineMethod2') => 2,
				GetLang('PayMateDeclineMethod3') => 3
			),
			"multiselect" => false
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

	/**
	*	Redirect the customer to PayMate's site to enter their payment details
	*/
	public function TransferToProvider()
	{
		$payMateCurrency = '';
		$defaultCurrency = GetDefaultCurrency();

		if (isset($defaultCurrency['currencycode']) && trim($defaultCurrency['currencycode']) !== '') {
			$payMateCurrency = $defaultCurrency['currencycode'];
		}

		// Default the default currency code to AUD if we have none or if we have an unsupported one
		if ($payMateCurrency == '' || !$this->checkSupportedCurrencies($payMateCurrency)) {
			$payMateCurrency = 'AUD';
		}

		$payMateUsername = trim($this->GetValue("username"));

		if($this->GetValue("testmode") == "YES") {
			$payMateURL = sprintf("https://www.paymate.com.au/PayMate/TestExpressPayment?mid=%s", $payMateUsername);
		}
		else {
			$payMateURL = sprintf("https://www.paymate.com/PayMate/ExpressPayment?mid=%s", $payMateUsername);
		}

		$billingDetails = $this->GetBillingDetails();
		$hiddenFields = array(
			'currency'				=> $payMateCurrency,
			'amt'					=> $this->GetGatewayAmount(),
			'amt_editable'			=> 'N',
			'ref'					=> $_COOKIE['SHOP_ORDER_TOKEN'],
			'return'				=> $GLOBALS['ShopPathSSL'].'/finishorder.php',
			'popup'					=> 'false',

			// Customer details
			'pmt_contact_firstname'	=> $billingDetails['ordbillfirstname'],
			'pmt_contact_surname'	=> $billingDetails['ordbilllastname'],
			'pmt_sender_email'		=> $billingDetails['ordbillemail'],
			'pmt_contact_phone'		=> $billingDetails['ordbillphone'],
			'pmt_country'			=> GetCountryISO2ByName($billingDetails['ordbillcountry']),
			'regindi_address1'		=> $billingDetails['ordbillstreet1'],
			'regindi_address2'		=> $billingDetails['ordbillstreet2'],
			'regindi_pcode'			=> $billingDetails['ordbillzip'],
			'regindi_sub'			=> $billingDetails['ordbillsuburb'],
			'regindi_state'			=> $billingDetails['ordbillstate']
		);

		$this->RedirectToProvider($payMateURL, $hiddenFields);
	}

	/**
	*	Return the unique order token which was saved as a cookie pre-payment
	*/
	public function GetOrderToken()
	{
		return @$_COOKIE['SHOP_ORDER_TOKEN'];
	}

	/**
	 * Verify the payment for the order.
	 */
	public function VerifyOrderPayment()
	{
		if(!isset($_POST['ref'])) {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('PayMateErrorInvalid'), GetLang('PayMateErrorInvalidMsg'));
			return false;
		}

		$amount = preg_replace("#[^0-9.]#i", "", $_POST['paymentAmount']);
		$ref = $_POST['ref'];
		$declineMethod = $this->GetValue("declinemethod");

		if($this->GetGatewayAmount() != $amount) {
			$errorMsg = sprintf(GetLang('PayMateErrorMismatchMsg'), $amount, $this->GetGatewayAmount(), $ref, $_POST['transactionID']);
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('PayMateErrorMismatch'), $errorMsg);
			return false;
		}

		// If the payment was approved, set the order as paid
		if($_POST['responseCode'] == "PA") {
			$this->SetPaymentStatus(PAYMENT_STATUS_PAID);
			return true;
		}
		// Payment is being processed
		else if($_POST['responseCode'] == "PP") {
			$this->SetPaymentStatus(PAYMENT_STATUS_PENDING);
			return true;
		}
		// Payment was declined
		else if($_POST['responseCode'] == "PD") {
			switch($declineMethod) {
				// Payment was declined and we're not accepting this order at all
				case 3:
					$this->SetPaymentStatus(PAYMENT_STATUS_DECLINED);
					return false;
					break;
				// Payment was declined but we're still accepting the order and setting status to 'Declined'
				case 2:
					$this->SetPaymentStatus(PAYMENT_STATUS_DECLINED);
					return true;
					break;
				// Payment was declined and we're redirecting the user back to the "Choose a payment method" page
				case 1:
				default:
					$this->RedirectToOrderConfirmation(GetLang('PayMateDeclinedRedirect'));
					break;
			}
		}
	}
}