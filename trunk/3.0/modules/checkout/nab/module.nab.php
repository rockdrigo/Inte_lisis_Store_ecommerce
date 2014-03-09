<?php

class CHECKOUT_NAB extends ISC_CHECKOUT_PROVIDER
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
	 * @var string Should the order be passed through in test mode?
	 */
	private $_testmode = "";

	/**
	 *	Checkout class constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->_name = GetLang('NabName');
		$this->_image = "nab_logo.gif";
		$this->_description = GetLang('NabDesc');
		$this->_help = sprintf(GetLang('NabHelp'), $GLOBALS['ShopPathSSL']);
	}

	/**
	 * Custom variables for the checkout module. Custom variables are stored in the following format:
	 * array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
	 * variable_type types are: text,number,password,radio,dropdown
	 * variable_options is used when the variable type is radio or dropdown and is a name/value array.
	 */
	public function SetCustomVars()
	{
		$this->_variables['displayname'] = array("name" => GetLang('NabDisplayName'),
		   "type" => "textbox",
		   "help" => GetLang('DisplayNameHelp'),
		   "default" => $this->GetName(),
		   "required" => true
		);

		$this->_variables['vendor_name'] = array("name" => GetLang('NabVendorName'),
		   "type" => "textbox",
		   "help" => GetLang('NabVendorNameHelp'),
		   "default" => "",
		   "required" => true
		);

		$this->_variables['email'] = array("name" => GetLang('NabPaymentEmail'),
		   "type" => "textbox",
		   "help" => GetLang('NabPaymentAlertHelp'),
		   "default" => "",
		   "required" => true
		);

		$this->_variables['testmode'] = array("name" => GetLang('NabTestMode'),
		   "type" => "dropdown",
		   "help" => GetLang("NabTestModeHelp"),
		   "default" => "no",
		   "required" => true,
		   "options" => array(GetLang("NabTestModeNo") => "NO",
						  GetLang("NabTestModeYes") => "YES"
			),
			"multiselect" => false
		);
	}

	public function TransferToProvider()
	{
		$total = number_format($this->GetGatewayAmount(), 2, '.', '');

		$testmode_on = $this->GetValue("testmode");

		if($testmode_on == "YES") {
			$nab_url = 'https://transact.nab.com.au/test/hpp/payment';
		}
		else {
			$nab_url = 'https://transact.nab.com.au/live/hpp/payment';
		}

		$billingDetails = $this->GetBillingDetails();

		$orders = $this->GetOrders();
		$orderIds = array();
		foreach($orders as $order) {
			$orderIds[] = '#'.$order['orderid'];
		}
		$orderIdAppend = '('.implode(', ', $orderIds).')';

		$merge_products = array();
		$name = sprintf(GetLang('YourOrderFromX'), GetConfig('StoreName')).' '.$orderIdAppend;

		$merge_products[$name] = "1,$total";

		$orderToken = $_COOKIE['SHOP_ORDER_TOKEN'];
		$sessionToken = $_COOKIE['SHOP_SESSION_TOKEN'];

		$signature = md5($total . $orderToken . $this->GetCombinedOrderId() . GetConfig('EncryptionToken'));

		$replyParams = array(
			'provider' 			=> $this->GetId(),
			'orderToken'		=> $orderToken,
			'sessionToken'		=> $sessionToken,
			'signature'			=> $signature,
			'bank_reference'	=> '',
			'payment_reference'	=> '',
			'payment_amount'	=> '',
			'payment_date'		=> '',
			'payment_number'	=> '',
		);

		$replyParamString = '';
		foreach ($replyParams as $param => $paramValue) {
			$replyParamString .= '&' . $param . '=' . $paramValue;
		}

		$hiddenFields = array(
			'vendor_name'			=>	$this->GetValue('vendor_name'),
			'payment_reference'		=>	$this->GetCombinedOrderId(),
			'payment_alert'			=>  $this->GetValue('email'),
			'Name'					=>	$billingDetails['ordbillfirstname'].' '.$billingDetails['ordbilllastname'],
			'Phone'					=>	$billingDetails['ordbillphone'],
			'Email'					=>	$billingDetails['ordbillemail'],
			'Postal Code'			=>	$billingDetails['ordbillzip'],
			'City'					=>	$billingDetails['ordbillsuburb'],
			'State'					=>	$billingDetails['ordbillstate'],
			'Street'				=>	$billingDetails['ordbillstreet1'],
			'information_fields'	=>	'Name,Phone,Email,Postal Code,City,State,Street',
			'return_link_url'		=> 	GetConfig('ShopPathSSL').'/finishorder.php',
			'reply_link_url'		=> 	GetConfig('ShopPathSSL').'/checkout.php?action=gateway_ping' . $replyParamString,
		);

		// Merging the product hidden fields with the rest of the fields
		$hidden_fields = array_merge($hiddenFields, $merge_products);

		$this->RedirectToProvider($nab_url, $hidden_fields);
	}

	public function VerifyOrderPayment()
	{
		if(!empty($_COOKIE['SHOP_ORDER_TOKEN'])) {
			// This order is still incomplete, the notification hasn't been received yet, so the payment status is pending
			if($this->GetOrderStatus() == ORDER_STATUS_INCOMPLETE) {
				$this->SetPaymentStatus(PAYMENT_STATUS_PENDING);
			}
			// Always return successful, the pingback will actually validate the order and do all of the magic
			return true;
		}
		else {
			// Bad order details
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('NabErrorInvalid'), __FUNCTION__);
			return false;
		}
	}
	/**
	 * Process the NAB pingback
	 */
	public function ProcessGatewayPing()
	{
		if(!isset($_REQUEST['payment_reference']) || !isset($_REQUEST['bank_reference']) || !isset($_REQUEST['orderToken']) || !isset($_REQUEST['signature'])) {
			exit;
		}

		$paymentReference = $_REQUEST['payment_reference'];
		$paymentAmount = number_format($_REQUEST['payment_amount'], 2, '.', '');
		$orderToken = $_REQUEST['orderToken'];
		$sessionToken = $_REQUEST['sessionToken'];
		$requestSignature = $_REQUEST['signature'];
		$transactionId = $_REQUEST['payment_number'];
		$bankReference = $_REQUEST['bank_reference'];

		$this->SetOrderData(LoadPendingOrdersByToken($orderToken));

		$orders = $this->GetOrders();
		list(,$order) = each($orders);
		$orderId = $order['orderid'];

		// GetGatewayAmount returns the amount from the order record, so $amount is that but formatted into #.##
		$amount = number_format($this->GetGatewayAmount(), 2, '.', '');

		// verify that the signature matches
		$verifySignature = md5($amount . $orderToken . $orderId . GetConfig('EncryptionToken'));

		if ($verifySignature != $requestSignature) {
			$errorMsg = GetLang('NabSignatureMismatchDetails', array('orderId' => $orderId, 'transactionId' => $transactionId));
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('NabSignatureMismatch'), $errorMsg);
			return false;
		}

		/** @var ISC_TRANSACTION */
		$transaction = GetClass('ISC_TRANSACTION');

		$previousTransaction = $transaction->LoadByTransactionId($transactionId, $this->GetId());

		if(is_array($previousTransaction) && $previousTransaction['transactionid']) {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang('NabTransactionAlreadyProcessed'), $_REQUEST['payment_date']));
			return false;
		}

		// Need to finish the processing of the pingback
		$newTransaction = array(
			'providerid' => $this->GetId(),
			'transactiondate' => $_REQUEST['payment_date'],
			'transactionid' => $transactionId,
			'orderid' => $orderId,
			'message' => 'Completed',
			'status' => '',
			'amount' => $_REQUEST['payment_amount'],
			'extrainfo' => array()
		);

		$newTransaction['status'] = TRANS_STATUS_COMPLETED;
		$newOrderStatus = ORDER_STATUS_AWAITING_FULFILLMENT;

		$transaction->Create($newTransaction);

		// If the order was previously incomplete, empty the customers cart
		if($this->GetOrderStatus() == ORDER_STATUS_INCOMPLETE) {
			session_write_close();
			$session = new ISC_SESSION($sessionToken);
			EmptyCartAndKillCheckout();
		}

		$status = $newOrderStatus;
		// If it's a digital order & awaiting fulfillment, automatically complete it
		if($order['ordisdigital'] && $status == ORDER_STATUS_AWAITING_FULFILLMENT) {
			$status = ORDER_STATUS_COMPLETED;
		}
		UpdateOrderStatus($orderId, $status);

		$updatedOrder = array(
			'ordpayproviderid' => $_REQUEST['payment_number'],
			'ordpaymentstatus' => 'captured',
		);

		$this->UpdateOrders($updatedOrder);

		// This was a successful order
		$oldStatus = GetOrderStatusById($this->GetOrderStatus());

		if(!$oldStatus) {
			$oldStatus = 'Incomplete';
		}

		$newStatus = GetOrderStatusById($newOrderStatus);
		$extra = GetLang('NabSuccessDetails',
			array(
				'orderId' 			=> $orderId,
				'amount' 			=> $amount,
				'bankAuth' 			=> $bankReference,
				'transactionId' 	=> $transactionId,
				'paymentStatus' 	=> 'Captured',
				'newOrderStatus' 	=> $newStatus,
				'oldOrderStatus' 	=> $oldStatus,
			)
		);
		$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang('NabSuccess'), $extra);
		return true;
	}
}
