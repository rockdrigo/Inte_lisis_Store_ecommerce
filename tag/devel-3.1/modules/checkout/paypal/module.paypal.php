<?php
class CHECKOUT_PAYPAL extends ISC_CHECKOUT_PROVIDER
{

	/**
	 * @var boolean Does this payment provider require SSL?
	 */
	protected $requiresSSL = false;

	/**
	 * @var boolean Does this provider support orders from more than one vendor?
	 */
	protected $supportsVendorPurchases = true;

	/**
	 * @var boolean Does this provider support shipping to multiple addresses?
	 */
	protected $supportsMultiShipping = true;

	/**
	 * @var string The shop owners PayPal email address
	 */
	private $_email = "";

	/**
	 * @var string Should the order be passed through in test mode?
	 */
	private $_testmode = "";

	/**
	 *	Checkout class constructor
	 */
	public function __construct()
	{
		// Setup the required variables for the PayPal checkout module
		parent::__construct();
		$this->_name = GetLang('PayPalName');
		$this->_image = "paypal_logo.gif";
		$this->_description = GetLang('PayPalDesc');
		$this->_help = sprintf(GetLang('PayPalHelp'), $GLOBALS['ShopPathSSL']);
	}

	/**
	 * Custom variables for the checkout module. Custom variables are stored in the following format:
	 * array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
	 * variable_type types are: text,number,password,radio,dropdown
	 * variable_options is used when the variable type is radio or dropdown and is a name/value array.
	 */
	public function SetCustomVars()
	{
		$this->_variables['displayname'] = array("name" => "Display Name",
		   "type" => "textbox",
		   "help" => GetLang('DisplayNameHelp'),
		   "default" => GetLang('PayPalDefaultDispayName'),
		   "required" => true
		);

		$this->_variables['email'] = array("name" => "PayPal Email Address",
		   "type" => "textbox",
		   "help" => GetLang('PayPalEmailHelp'),
		   "default" => "",
		   "required" => true
		);

		$this->_variables['testmode'] = array("name" => "Test Mode",
		   "type" => "dropdown",
		   "help" => GetLang("PayPalTestModeHelp"),
		   "default" => "no",
		   "required" => true,
		   "options" => array(GetLang("PayPalTestModeNo") => "NO",
						  GetLang("PayPalTestModeYes") => "YES"
			),
			"multiselect" => false
		);
	}

	/**
	 *	Redirect the customer to PayPal's site to enter their payment details
	 */
	public function TransferToProvider()
	{
		$total = $this->GetGatewayAmount();
		$this->_email = $this->GetValue("email");

		$testmode_on = $this->GetValue("testmode");

		if($testmode_on == "YES") {
			$paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
		}
		else {
			$paypal_url = "https://www.paypal.com/cgi-bin/webscr";
		}

		$billingDetails = $this->GetBillingDetails();
		$country = GetCountryISO2ById($billingDetails['ordbillcountryid']);

		// If US phone numbers, we need to format them appropriately
		$phone_1 = $phone_2 = $phone_3 = $state = '';
		$phone = $billingDetails['ordbillphone'];
		if($country == "US") {
			// Numbers only
			$phone = str_replace("+1", "", $phone);
			$phone = preg_replace("#[^0-9]+#", "", $phone);
			if(strlen($phone) == 10) {
				$phone_1 = substr($phone, 0, 3);
				$phone_2 = substr($phone, 3, 3);
				$phone_3 = substr($phone, 6, 4);
			}
			$state = GetStateISO2ById($billingDetails['ordbillstateid']);
		}
		else {
			$phone = preg_replace("#[^\+0-9]+#", "", $phone);
			$phone_2 = $phone;
		}

		$orders = $this->GetOrders();
		$orderIds = array();
		foreach($orders as $order) {
			$orderIds[] = '#'.$order['orderid'];
		}
		$orderIdAppend = '('.implode(', ', $orderIds).')';

		$currency = GetDefaultCurrency();
		$hash =  $this->_calculateSecurityHash($orders, $total);

		$hiddenFields = array(
			'cmd'			=> '_ext-enter',
			'redirect_cmd'	=> '_xclick',
			'business'		=>$this->_email,
			'rm'			=> 2,

			// Order details
			'amount'		=> number_format($total, 2, '.', ''),
			'tax'			=> 0,
			'no_note'		=> 1,
			'currency_code'	=> $currency['currencycode'],
			'item_name'		=> sprintf(GetLang('YourOrderFromX'), GetConfig('StoreName')).' '.$orderIdAppend,
			'custom'		=> $_COOKIE['SHOP_ORDER_TOKEN'] . '_' . $_COOKIE['SHOP_SESSION_TOKEN'].'_'.$hash,

			// Notification and return URLS
			'return'		=> GetConfig('ShopPathSSL').'/finishorder.php',
			'cancel_return'	=> GetConfig('ShopPathSSL').'/cart.php',
			'notify_url'	=> GetConfig('ShopPathSSL').'/checkout.php?action=gateway_ping&provider='.$this->GetId(),

			// Customer details
			'first_name'	=> $billingDetails['ordbillfirstname'],
			'last_name'		=> $billingDetails['ordbilllastname'],
			'email'			=> $billingDetails['ordbillemail'],
			'address1'		=> $billingDetails['ordbillstreet1'],
			'address2'		=> $billingDetails['ordbillstreet2'],
			'day_phone_a'	=> $phone_1,
			'day_phone_b'	=> $phone_2,
			'day_phone_c'	=> $phone_3,
			'night_phone_a'	=> $phone_1,
			'night_phone_b'	=> $phone_2,
			'night_phone_c'	=> $phone_3,
			'country'		=> $country,
			'zip'			=> $billingDetails['ordbillzip'],
			'city'			=> $billingDetails['ordbillsuburb'],
			'state'			=> $state,
			'address_override' => 0,
			'no_shipping'	=> 2,
			'BUTTONSOURCE'	=> 'ISC_ShoppingCart_ST',

		);

		$this->RedirectToProvider($paypal_url, $hiddenFields);
	}

	/**
	 * Hash that binds recipient email, order details and total amount.
	 * Provides binding for payment and order.
	 * If a malicious user edit the hash, PayPal validate notify will fail.
	 * If a malicious user edit the cookie, security hash validation will fail.
	 */
	private function _calculateSecurityHash($orders, $amount)
	{
		$email = $this->GetValue('email');
		$orderHash = '';
		foreach ($orders as $oid => $order) {
			$orderHash .= $oid.$order['ordtoken'];
		}

		return md5($email.$orderHash.$amount.getConfig('EncryptionToken'));
	}

	/**
	 *	Verify the order.
	 *
	 * @return boolean True if the order has been verified successfully or false if not.
	 */
	public function VerifyOrderPayment()
	{
		if(!empty($_COOKIE['SHOP_ORDER_TOKEN'])) {
			// This order is still incomplete, IPN notification hasn't been received yet, so the payment status is pending
			if($this->GetOrderStatus() == ORDER_STATUS_INCOMPLETE) {
				$this->SetPaymentStatus(PAYMENT_STATUS_PENDING);
			}
			// Always return successful, the IPN pingback will actually validate the order and do all of the magic
			return true;
		}
		else {
			// Bad order details
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('PayPalErrorInvalid'), __FUNCTION__);
			return false;
		}
	}

	/**
	 * Process the PayPal IPN ping back.
	 */
	public function ProcessGatewayPing()
	{
		if(!isset($_POST['custom'])) {
			exit;
		}

		$sessionToken = explode('_', $_REQUEST['custom'], 3);

		$o = LoadPendingOrdersByToken($sessionToken[0]);
		$hash = $this->_calculateSecurityHash($o['orders'], $o['gatewayamount']);
		if ($hash != $sessionToken[2]) {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('PayPalErrorInvalid'), getLang('PayPalSecurityHashMismatch'));
			exit;
		}

		$this->SetOrderData($o);
		$amount = number_format($this->GetGatewayAmount(), 2, '.', '');
		if($amount == 0) {
			exit;
		}

		// Perform a post back to PayPal with exactly what we received in order to validate the request
		$queryString = array();
		$queryString[] = "cmd=_notify-validate";
		foreach($_POST as $k => $v) {
			$queryString[] = $k."=".urlencode($v);
		}
		$queryString = implode('&', $queryString);

		$testMode = $this->GetValue('testmode');
		if($testMode == 'YES') {
			$verifyURL = 'http://www.sandbox.paypal.com/cgi-bin/webscr';
		}
		else {
			$verifyURL = 'http://www.paypal.com/cgi-bin/webscr';
		}

		$response = PostToRemoteFileAndGetResponse($verifyURL, $queryString);

		// This pingback was not valid
		if($response != "VERIFIED") {
			// Bad order details
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('PayPalErrorInvalid'), "RESPONSE : "  .$response);
			return false;
		}

		// If we're still here, the ping back was valid, so we check the payment status and everything else match up

		// Has the transaction been processed before? If so, we can't process it again
		$transaction = GetClass('ISC_TRANSACTION');

		$previousTransaction = $transaction->LoadByTransactionId($_POST['txn_id'], $this->GetId());

		$paypalEmail = $this->GetValue('email');

		if(!isset($_POST['receiver_email']) || !isset($_POST['mc_gross']) || !isset($_POST['payment_status'])) {
			// Bad order details
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('PayPalErrorInvalid'), print_r($_POST, true));
			return false;
		}

		// The values passed don't match what we expected
		if(trim(isc_strtolower($_POST['receiver_email'])) != trim(isc_strtolower($paypalEmail)) || ($_POST['mc_gross'] != $amount && !in_array($_POST['payment_status'], array('Reversed', 'Refunded', 'Canceled_Reversed')))) {
			$errorMsg = sprintf(GetLang('PayPalErrorInvalidMsg'), $_POST['mc_gross'], $amount, $_POST['receiver_email'], $paypalEmail, $_POST['payment_status']);
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('PayPalErrorInvalid'), $errorMsg);
			return false;
		}

		$currency = GetDefaultCurrency();

		if($_POST['mc_currency'] != $currency['currencycode']) {
			$errorMsg = sprintf(GetLang('PayPalErrorInvalidMsg3'), $currency['currencycode'], $_POST['mc_currency']);
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('PayPalErrorInvalid'), $errorMsg);
			return false;
		}

		$newTransaction = array(
			'providerid' => $this->GetId(),
			'transactiondate' => time(),
			'transactionid' => $_POST['txn_id'],
			'orderid' => array_keys($this->GetOrders()),
			'message' => '',
			'status' => '',
			'amount' => $_POST['mc_gross'],
			'extrainfo' => array()
		);

		$orderPaymentStatus = '';
		switch($_POST['payment_status']) {
			case "Completed":
				$orderPaymentStatus = 'captured';
				$newTransaction['status'] = TRANS_STATUS_COMPLETED;
				$newOrderStatus = ORDER_STATUS_AWAITING_FULFILLMENT;
				break;
			case "Pending":
				$newTransaction['status'] = TRANS_STATUS_PENDING;
				$newOrderStatus = ORDER_STATUS_AWAITING_PAYMENT;
				$newTransaction['extrainfo']['reason'] = $_POST['pending_reason'];
				break;
			case "Denied":
				$newTransaction['status'] = TRANS_STATUS_DECLINED;
				$newOrderStatus = ORDER_STATUS_DECLINED;
				break;
			case "Failed":
				$newTransaction['status'] = TRANS_STATUS_FAILED;
				$newOrderStatus = ORDER_STATUS_DECLINED;
				break;
			case "Refunded":
				$newTransaction['status'] = TRANS_STATUS_REFUND;
				$newOrderStatus = ORDER_STATUS_REFUNDED;
				break;
			case "Reversed":
				$newTransaction['status'] = TRANS_STATUS_CHARGEBACK;
				$newOrderStatus = ORDER_STATUS_REFUNDED;
				break;
			case "Canceled_Reversal":
				$newTransaction['status'] = TRANS_STATUS_CANCELLED_REVERSAL;
				$newOrderStatus = ORDER_STATUS_REFUNDED;
				break;
		}

		$newTransaction['message'] = $this->GetPayPalTransactionMessage($_POST);

		$transactionId = $transaction->Create($newTransaction);

		$oldOrderStatus = $this->GetOrderStatus();
		// If the order was previously incomplete, we need to do some extra work
		if($oldOrderStatus == ORDER_STATUS_INCOMPLETE) {
			// If a customer doesn't return to the store from PayPal, their cart will never be
			// emptied. So what we do here, is if we can, load up the existing customers session
			// and empty the cart and kill the checkout process. When they next visit the store,
			// everything should be "hunky-dory."
			session_write_close();
			$session = new ISC_SESSION($sessionToken[1]);
		}

		// Update the status for all orders that we've just received the payment for
		foreach($this->GetOrders() as $orderId => $order) {
			$status = $newOrderStatus;
			// If it's a digital order & awaiting fulfillment, automatically complete it
			if($order['ordisdigital'] && $status == ORDER_STATUS_AWAITING_FULFILLMENT) {
				$status = ORDER_STATUS_COMPLETED;
			}
			UpdateOrderStatus($orderId, $status);
		}

		$updatedOrder = array(
			'ordpayproviderid' => $_POST['txn_id'],
			'ordpaymentstatus' => $orderPaymentStatus,
		);

		$this->UpdateOrders($updatedOrder);

		// This was a successful order
		$oldStatus = GetOrderStatusById($oldOrderStatus);
		if(!$oldStatus) {
			$oldStatus = 'Incomplete';
		}
		$newStatus = GetOrderStatusById($newOrderStatus);
		$extra = sprintf(GetLang('PayPalSuccessDetails'), implode(', ', array_keys($this->GetOrders())), $amount, $_POST['txn_id'], $_POST['payment_status'], $newStatus, $oldStatus);
		$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang('PayPalSuccess'), $extra);
		return true;
	}



	/**
	 * Build and return a transaction message for a PayPal IPN response. This is saved to the transactions table.
	 *
	 * @param array Array of information (from $_POST) about the IPN response.
	 * @return string The language string for this transaction status.
	 */
	private function GetPayPalTransactionMessage($paypalData)
	{
		switch($paypalData['payment_status']) {
			case "Completed":
			case "Denied":
			case "Failed":
				$status = str_replace('_', '', $paypalData['payment_status']);
				return GetLang('PayPalTransactionStatus'.$status);
			case "Pending":
				switch($paypalData['pending_reason']) {
					case "address":
						$langString = 'Address';
						break;
					case "echeck":
						$langString = 'Echeck';
						break;
					case "intl":
						$langString = 'Intl';
						break;
					case "multi-currency":
						$langString = 'MC';
						break;
					case "unilateral":
						$langString = 'Unilateral';
						break;
					case "upgrade":
						$langString = 'Upgrade';
						break;
					case "verify":
						$langString = 'Verify';
						break;
					default:
						$langString ='';
				}
				return GetLang('PayPalTransactionStatusPending'.$langString);
			case "Reversed":
			case "Refunded":
			case "Canceled_Reversal":
				switch($paypalData['reason_code']) {
					case "chargeback":
						$langString = 'PayPalTransactionStatusReversedChargeback';
						break;
					case "guarantee":
						$langString = 'PayPalTransactionStatusReversedGuarantee';
						break;
					case "buyer-complaint":
						$langString = 'PayPalTransactionStatusReversedBuyerComplaint';
						break;
					case "refund":
						$langString = 'PayPalTransactionStatusReversedRefund';
					default:
						$status = str_replace('_', '', $paypalData['payment_status']);
						$langString = 'PayPalTransactionStatus'.$status;
				}
				return GetLang($langString);
		}
	}
}