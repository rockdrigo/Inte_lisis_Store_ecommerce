<?php
class CHECKOUT_ESELECTPLUSDP extends ISC_CHECKOUT_PROVIDER
{

	/*
		Does this payment provider require SSL?
	*/
	protected $requiresSSL = false;

	/*
		The eSelectPlus direct post store number
	*/
	private $_directpostid = "";

	/*
		The eSelectPlusDP hosted paypage Token
	*/
	private $_directpostToken = "";

	/*
		Should the order be passed through in test mode?
	*/
	private $_testmode = "";

	/*
		Checkout class constructor
	*/
	public function __construct()
	{
		// Setup the required variables for the eSelectPlusDP Connect checkout module
		parent::__construct();
		$this->_name = GetLang('eSelectPlusDPName');
		$this->_image = "eSelectPlus_logo.gif";
		$this->_description = GetLang('eSelectPlusDPDesc');
		$this->_help = GetLang('eSelectPlusDPHelp', array(
			'shopPathSSL' => $GLOBALS['ShopPathSSL'],
		));
		$this->_height = 0;

		$this->_file = basename(__FILE__);
	}

	/**
	* Custom variables for the checkout module. Custom variables are stored in the following format:
	* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
	* variable_type types are: text,number,password,radio,dropdown
	* variable_options is used when the variable type is radio or dropdown and is a name/value array.
	*/
	public function setcustomvars()
	{

		$this->_variables['displayname'] = array("name" => GetLang('DisplayName'),
		   "type" => "textbox",
		   "help" => GetLang('DisplayNameHelp'),
		   "default" => $this->getname(),
		   "savedvalue" => array(),
		   "required" => true
		);

		$this->_variables['directpostid'] = array("name" => GetLang('eSelectPlusDPID'),
		   "type" => "textbox",
		   "help" => GetLang('eSelectPlusDPIDHelp'),
		   "default" => "",
		   "savedvalue" => array(),
		   "required" => true
		);

		$this->_variables['directposttoken'] = array("name" => GetLang('eSelectPlusDPToken'),
		   "type" => "textbox",
		   "help" => GetLang('eSelectPlusDPTokenHelp'),
		   "default" => "",
		   "savedvalue" => array(),
		   "required" => true
		);

		$this->_variables['testmode'] = array("name" => GetLang('TestMode'),
		   "type" => "dropdown",
		   "help" => GetLang("eSelectPlusDPTestModeHelp"),
		   "default" => "no",
		   "savedvalue" => array(),
		   "required" => true,
		   "options" => array(GetLang("eSelectPlusDPTestModeNo") => "NO",
						  GetLang("eSelectPlusDPTestModeYes") => "YES"
			),
			"multiselect" => false
		);
	}

	/*
	 * Check if this checkout module can be enabled or not.
	 *
	 * @return boolean True if this module is supported on this install, false if not.
	 */
	public function IsSupported()
	{
		if(!GetConfig('UseSSL')) {
			$this->SetError(GetLang('eSelectPlusDPNoSSLError'));
		}

		if(!$this->HasErrors()) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Process the users submitted payment details and pass the customer through the eSelectPlus DP gateway
	 */
	public function ProcessPaymentForm()
	{
		// Check that all of the required fields were supplied
		$postFields = array(
			'cardholder'		=> GetLang('eSelectPlusDPEnterName'),
			'cc_num'			=> GetLang('eSelectPlusDPEnterCardNumber'),
			'avs_street_number' => GetLang('eSelectPlusDPEnterStreetNumber'),
			'avs_street_name'	=> GetLang('eSelectPlusDPEnterStreetName'),
			'avs_zipcode'		=> GetLang('eSelectPlusDPEnterPostcode'),
			'expMonth'			=> GetLang('eSelectPlusDPEnterCreditCardMonth'),
			'expYear'			=> GetLang('eSelectPlusDPEnterCreditCardYear'),
			'cvd_value'			=> GetLang('eSelectPlusDPEnterCVV2Number')
		);

		foreach($postFields as $field => $message) {
			// A required field was not entered
			if(!isset($_POST[$field]) || !trim($_POST[$field])) {
				$this->SetError($message);
				return false;
			}
		}

		$pendingOrder = LoadPendingOrderByToken();

		$gst = 0;
		$pst = 0;
		$hst = 0;
		$ordersTaxes = $this->getOrderTaxes();
		foreach($ordersTaxes as $taxes) {
			foreach($taxes as $tax) {
				$id = strtolower($tax['name']);
				if($id == 'gst' || $id == 'pst' || $id == 'hst') {
					$$id += $tax['amount'];
				}
			}
		}

		$billingDetails = $this->GetBillingDetails();
		$shippingAddress = $this->getShippingAddress();

		// Now that we have all the fields, let's build our request and send it off to eSelectPlus
		$hiddenFields = array(
			'ps_store_id'				=> $this->GetValue('directpostid'),
			'hpp_key'					=> $this->GetValue('directposttoken'),
			'charge_total'				=> number_format($pendingOrder['total_inc_tax'], 2, '.', ''),
			'bill_first_name'			=> $billingDetails['ordbillfirstname'],
			'bill_last_name'			=> $billingDetails['ordbilllastname'],
			'bill_address_one'			=> trim($billingDetails['ordbillstreet1'])." ". $billingDetails['ordbillstreet2'],
			'bill_city'					=> $billingDetails['ordbillsuburb'],
			'bill_state_or_province'	=> $billingDetails['ordbillstate'],
			'bill_postal_code'			=> $billingDetails['ordbillzip'],
			'bill_country'				=> $billingDetails['ordbillcountry'],
			'bill_phone'				=> $billingDetails['ordbillphone'],
			'ship_first_name'			=> $shippingAddress['first_name'],
			'ship_last_name'			=> $shippingAddress['last_name'],
			'ship_address_one'			=> trim($shippingAddress['address_1'])." ". $shippingAddress['address_2'],
			'ship_city'					=> $shippingAddress['city'],
			'ship_state_or_province'	=> $shippingAddress['state'],
			'ship_postal_code'			=> $shippingAddress['zip'],
			'ship_country'				=> $shippingAddress['country'],
			'gst'						=> $gst,
			'pst'						=> $pst,
			'hst'						=> $hst,
			'shipping_cost'				=> number_format($this->getShippingCost() + $this->getHandlingCost(), 2, '.', ''),
			'cust_id'					=> $pendingOrder['ordcustid'],
			'email'						=> $billingDetails['ordbillemail'],
			'cc_num'					=> $_POST['cc_num'],
			'cvd_value'					=> $_POST['cvd_value'],
			'expMonth'					=> $_POST['expMonth'],
			'expYear'					=> $_POST['expYear'],
			'cardholder'				=> $_POST['cardholder'],
			'avs_street_number'			=> $_POST['avs_street_number'],
			'avs_street_name'			=> $_POST['avs_street_name'],
			'avs_zipcode'				=> $_POST['avs_zipcode']
		);

		// Now - what we're going to do is save a few of the entered details in the session so we can
		// show the details again if their card details were declined
		$_SESSION['CHECKOUT']['ESELECTDP'] = array(
			'cardholder' => $_POST['cardholder'],
			'avs_street_number' => $_POST['avs_street_number'],
			'avs_street_name' => $_POST['avs_street_name'],
			'avs_zipcode' => $_POST['avs_zipcode'],
			'expMonth' => $_POST['expMonth'],
			'expYear' => $_POST['expYear']
		);

		// Now redirect the customer through the gateway
		if ($this->GetValue("testmode") == "YES") {
			$gatewayURL = "https://esqa.moneris.com/HPPDP/index.php";
		}
		else {
			$gatewayURL = "https://www3.moneris.com/HPPDP/index.php";
		}


		$this->RedirectToProvider($gatewayURL, $hiddenFields);
	}

	/**
	* ShowPaymentForm
	* Show a payment form for this particular gateway if there is one.
	* This is useful for gateways that require things like credit card details
	* to be submitted and then processed on the site.
	*/
	public function ShowPaymentForm()
	{
		$GLOBALS['eSelectPlusDPMonths'] = "";
		$GLOBALS['eSelectPlusDPYears'] = "";

		$selectedMonth = '';
		$selectedYear = '';

		if(isset($_POST['expMonth'])) {
			$selectedMonth = $_POST['expMonth'];
		}
		else if(isset($_SESSION['CHECKOUT']['ESELECTDP']['expMonth'])) {
			$selectedMonth = $_SESSION['CHECKOUT']['ESELECTDP']['expMonth'];
		}

		for($i = 1; $i <= 12; $i++) {
			$stamp = mktime(0, 0, 0, $i, 15, isc_date("Y"));
			$i = str_pad($i, 2, "0", STR_PAD_LEFT);
			$sel = '';
			if ($selectedMonth == $i) {
				$sel = 'selected="selected"';
			}
			$GLOBALS['eSelectPlusDPMonths'] .= sprintf("<option %s value='%s'>%s</option>", $sel, $i, isc_date("M", $stamp));
		}

		if(isset($_POST['expYear'])) {
			$selectedYear = $_POST['expYear'];
		}
		else if(isset($_SESSION['CHECKOUT']['ESELECTDP']['expYear'])) {
			$selectedYear = $_SESSION['CHECKOUT']['ESELECTDP']['expYear'];
		}

		for($i = isc_date("Y"); $i < isc_date("Y")+10; $i++) {
			$sel = '';
			if ($selectedYear == substr($i, 2, 2)) {
				$sel = 'selected="selected"';
			}
			$GLOBALS['eSelectPlusDPYears'] .= sprintf("<option %s value='%s'>%s</option>", $sel, substr($i, 2, 2), $i);
		}

		// Load the pending order
		$pendingOrder = LoadPendingOrderByToken();

		// take the first word of the street line as street number.
		// this will not work for an address like "unit 1 78 Hello Street"
		$streetline = $pendingOrder['ordbillstreet1'];
		if(preg_match('#^[0-9]+\s#', $streetline)) {
			$streetline = explode(' ', $streetline, 2);
			$streetnum = $streetline[0];
			$streetname = $streetline[1];
		}
		else {
			$streetnum = '';
			$streetname = $streetline;
		}

		$GLOBALS['eSelectPlusDPBillStNum'] = isc_html_escape($streetnum);
		$GLOBALS['eSelectPlusDPBillStName'] = isc_html_escape($streetname);
		$GLOBALS['eSelectPlusDPCardHolderName'] = isc_html_escape($pendingOrder['ordbillfirstname'].' '.$pendingOrder['ordbilllastname']);
		$GLOBALS['eSelectPlusDPBillZip'] = isc_html_escape($pendingOrder['ordbillzip']);

		// Format the amount that's going to be going through the gateway
		$GLOBALS['OrderAmount'] = CurrencyConvertFormatPrice($pendingOrder['total_inc_tax'], $pendingOrder['ordcurrencyid'], $pendingOrder['ordcurrencyexchangerate']);

		// Was there an error validating the payment? If so, pre-fill the form fields with the already-submitted values
		if($this->HasErrors()) {
			$GLOBALS['eSelectPlusDPErrorMessage'] = implode("<br />", $this->GetErrors());
		}
		else {
			// Hide the error message box
			$GLOBALS['HideeSelectPlusDPError'] = "none";
		}

		// If we have any fields we can remember the value of, take them & set them
		$rememberedFields = array(
			'eSelectPlusDPCardHolderName'	=> 'cardholder',
			'eSelectPlusDPBillStNum'		=> 'avs_street_number',
			'eSelectPlusDPBillStName'		=> 'avs_street_name',
			'eSelectPlusDPBillZip'			=> 'avs_zipcode'
		);

		foreach($rememberedFields as $field => $from) {
			if(isset($_POST[$from])) {
				$GLOBALS[$field] = isc_html_escape($_POST[$from]);
			}
			else if(isset($_SESSION['CHECKOUT']['ESELECTDP'][$from])) {
				$GLOBALS[$field] = isc_html_escape($_SESSION['CHECKOUT']['ESELECTDP'][$from]);
			}
		}

		// Collect their details to send through to Authorize.NET
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("eselectplusdp");
		return $GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate(true);
	}


	/**
	*	Return the unique order token which was saved as a cookie pre-payment
	*/
	public function getordertoken()
	{
		return @$_COOKIE['SHOP_ORDER_TOKEN'];
	}

	/**
	*	Verify the order by posting back to eSelectPlusDP direct post.
	*/
	public function verifyorder(&$PendingOrder)
	{

		// If a response code wasn't received, the customer didn't continue with the transaction or cancelled it
		if(!isset($_REQUEST['response_code'])) {
			//there was an error validating this transaction, log an error and show the error message
			$message = GetLang('NA');
			if(isset($_REQUEST['message'])) {
				$message = isc_html_escape($_REQUEST['message']);
			}
			$invalidPaymentMsg =sprintf(GetLang('eSelectPlusDPErrorInvalidMsgNoRspCode'), $PendingOrder['orderid'], $message);
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('eSelectPlusDPErrorInvalid'), $invalidPaymentMsg);
			return false;
		}

		// If the order total doesn't match up, show an error message
		if(!isset($_REQUEST['charge_total']) || $_REQUEST['charge_total'] != $PendingOrder['total_inc_tax']) {
			$invalidPaymentMsg = sprintf(GetLang('eSelectPlusDPErrorBadTotal'), $PendingOrder['orderid'], $_REQUEST['charge_total'], $PendingOrder['total_inc_tax']);
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('eSelectPlusDPErrorInvalid'), $invalidPaymentMsg);
			return false;

		}

		// If the transaction was declined, log an error message and show the payment form again
		$responseCode = $_REQUEST['response_code'];
		if($responseCode >= 50 || $responseCode < 0 || $responseCode== '' || $responseCode == 'null') {
			$invalidPaymentMsg = sprintf(GetLang('eSelectPlusDPErrorInvalidMsg'), $PendingOrder['orderid'], $responseCode);

			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('eSelectPlusDPTransactionDeclined'), $invalidPaymentMsg);

			$this->SetError(GetLang('eSelectPlusDPCardDeclined'));
			$GLOBALS['ISC_CLASS_CHECKOUT'] = GetClass('ISC_CHECKOUT');
			$GLOBALS['ISC_CLASS_CHECKOUT']->ShowPaymentForm($this);
			exit;
		}

		// Otherwise we're all good
		if ($_REQUEST['trans_name'] == 'preauth' || $_REQUEST['trans_name'] == 'cavv_preauth' ) {
			$PendingOrder['paymentstatus'] = PAYMENT_STATUS_PENDING;
		}
		elseif ($_REQUEST['trans_name'] == 'purchase' || $_REQUEST['trans_name'] == 'cavv_purchase') {
			$PendingOrder['paymentstatus'] = PAYMENT_STATUS_PAID;
		}

		if(isset($_REQUEST['response_order_id'])) {
			$updatedOrder = array(
				'ordpayproviderid'	=> $_REQUEST['response_order_id'],
			);

			//update the orders table with new transaction details
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$PendingOrder['orderid']."'");
		}
		$successPaymentMsg = sprintf(GetLang('eSelectPlusDPSuccess'), $PendingOrder['orderid'], isc_html_escape($responseCode));
		$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $successPaymentMsg);
		return true;
	}


	/**
	* get the transaction information back from eselect plus
	* Display the transaction information
	*/
	public function ShowOrderConfirmation($order)
	{
		$fields = array(
			'Amount'				=> 'charge_total',
			'TransactionType'		=> 'trans_name',
			'DateTime'				=> 'date_stamp',
			'AuthorisationCode'		=> 'bank_approval_code',
			'ResponseCode'			=> 'response_code',
			'ISOCode'				=> 'iso_code',
			'ResponseMessage'		=> 'message',
			'ReferenceNumber'		=> 'bank_transaction_id',
			'MerchantURL'			=> '',
			'CardholderName'		=> 'cardholder',
			'IssuerName'			=> 'ISSNAME',
			'IssuerConfirmation'	=> 'ISSCONF',
			'InvoiceNumber'			=> 'INVOICE'
		);

		foreach($fields as $globalField => $requestField) {
			if($globalField == 'Amount') {
				$value = CurrencyConvertFormatPrice($_REQUEST[$requestField]);
			}
			else if($globalField == 'MerchantURL') {
				$GLOBALS['MerchantURL'] = isc_html_escape(GetConfig('ShopPathSSL'));
			}
			else if(isset($_REQUEST[$requestField]) && $_REQUEST[$requestField] != '' && $_REQUEST[$requestField] != 'null') {
				$value = isc_html_escape($_REQUEST[$requestField]);
			}
			else {
				$value = GetLang('NA');
			}
			$GLOBALS[$globalField] = $value;
		}
		return $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('eSelectPlusDPConfirmationDetails');
	}
}