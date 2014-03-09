<?php
class CHECKOUT_BANCOMER extends ISC_CHECKOUT_PROVIDER
{

	/**
	 * @var boolean Does this payment provider require SSL?
	 */
	protected $requiresSSL = true;

	/**
	 * @var boolean Does this provider support orders from more than one vendor?
	 */
	protected $supportsVendorPurchases = false;

	/**
	 * @var boolean Does this provider support shipping to multiple addresses?
	 */
	protected $supportsMultiShipping = false;

	/**
	 *	Checkout class constructor
	 */
	public function __construct()
	{
		// Setup the required variables for the PayPal checkout module
		parent::__construct();
		$this->_name = GetLang('BancomerName');
		$this->_image = "Bancomer_logo.gif";
		$this->_description = GetLang('BancomerDesc');
		$this->_help = sprintf(GetLang('BancomerHelp'), $GLOBALS['ShopPathSSL']);
		$this->_currenciesSupported = array("MXN");
	}

	/**
	 * Custom variables for the checkout module. Custom variables are stored in the following format:
	 * array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
	 * variable_type types are: text,number,password,radio,dropdown
	 * variable_options is used when the variable type is radio or dropdown and is a name/value array.
	 */
	public function SetCustomVars()
	{
		$this->_variables['afiliationcode'] = array("name" => GetLang('BancomerAfiliationCode'),
		   "type" => "textbox",
		   "help" => GetLang('BancomerAfiliationCodeHelp'),
		   "default" => '',
		   "required" => true
		);

		$this->_variables['terminalno'] = array("name" => GetLang('BancomerTerminalNo'),
		   "type" => "textbox",
		   "help" => GetLang('BancomerTerminalNoHelp'),
		   "default" => "",
		   "required" => true
		);
		
		$this->_variables['secretcode'] = array("name" => GetLang('BancomerSecretCode'),
				"type" => "textbox",
				"help" => GetLang('BancomerSecretCodeHelp'),
				"default" => "",
				"required" => true
		);
		$this->_variables['testmode'] = array(
				"name" => GetLang('BancomerTestMode'),
				"type" => "dropdown",
				"help" => GetLang("BancomerTestModeHelp"),
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

		$billingDetails = $this->GetBillingDetails();

		$orders = $this->GetOrders();
		$orderIdsNum = array();
		foreach($orders as $order) {
			$orderIdsNum[] = $order['orderid'];
		}
		$orderIdAppendNum = implode('', $orderIdsNum);
		
		$storeNameSafe = preg_replace("/[^A-Za-z0-9 ]/", '', GetConfig('StoreName'));

		$hiddenFields = array(
			// Order details
			'Ds_Merchant_Amount'	=> number_format($total, 2, '', ''),
			'Ds_Merchant_Order'		=> $orderIdAppendNum,
			'Ds_Merchant_Currency'	=> 484,  //solo soportamos pesos, este es el codigo
			'Ds_Merchant_ProductDescription'		=> substr(sprintf(GetLang('YourOrderFromX'), $storeNameSafe).' '.$orderIdAppendNum, 0, 125),
			'Ds_Merchant_MerchantCode'				=> $this->GetValue("afiliationcode"),
			'Ds_Merchant_MerchantURL'				=> GetConfig('ShopPathSSL').'/checkout.php?action=gateway_ping&provider='.$this->GetId(),

			// Notification and return URLS
			'Ds_Merchant_UrlOK'		=> GetConfig('ShopPath').'/finishorder.php',
			'Ds_Merchant_UrlKO'	=> GetConfig('ShopPath').'/checkout.php',

			// Merchant details
			'Ds_Merchant_MerchantName'	=> substr($storeNameSafe, 0, 25),
			'Ds_Merchant_Terminal'			=> $this->GetValue("terminalno"),
			'Ds_Merchant_TransactionType'		=> '0',
			'Ds_Merchant_MerchantData'		=> $_COOKIE['SHOP_ORDER_TOKEN'] . '_' . $_COOKIE['SHOP_SESSION_TOKEN'],

		);
		
		$hiddenFields['Ds_Merchant_MerchantSignature'] = $this->_calculateSecurityHash($hiddenFields);

		$testmode_on = $this->GetValue("testmode");
		if($testmode_on == "YES") {
			$url = GetConfig('ShopPath').'/modules/checkout/bancomer/testmode.php';
		}
		else {
			$url = 'https://ecom.eglobal.com.mx/VPBridgeWeb/servlets/TransactionStartBridge';
		}
		
		$this->RedirectToProvider($url, $hiddenFields);
	}

	/**
	 * Calcula la firma de la transaccion a partir de los parametros, para enviar junto con los datos
	 * @param unknown $orders
	 * @param unknown $amount
	 * @return string
	 */
	private function _calculateSecurityHash($hiddenFields)
	{
		return sha1($hiddenFields['Ds_Merchant_Amount'].$hiddenFields['Ds_Merchant_Order'].$hiddenFields['Ds_Merchant_MerchantCode'].$hiddenFields['Ds_Merchant_Currency'].$hiddenFields['Ds_Merchant_TransactionType'].$this->GetValue("secretcode"));
	}

	/**
	 * Process the ping back.
	 */
	public function ProcessGatewayPing()
	{
		if(!isset($_POST['Ds_Signature']) || !isset($_POST['Ds_MerchantCode'])) {
			exit;
		}

		$sessionToken = explode('_', $_REQUEST['Ds_MerchantData'], 3);
	
		$o = LoadPendingOrdersByToken($sessionToken[0]);
		if(!$o || empty($o)){
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), 'No se encontro la orden', print_r($_POST, true));
			return false;
		}
		$this->SetOrderData($o);
		$amount = number_format($this->GetGatewayAmount(), 2, '.', '');
		if($amount == 0) {
			exit;
		}
		
		// If we're still here, the ping back was valid, so we check the payment status and everything else match up

		// Has the transaction been processed before? If so, we can't process it again
		$transaction = GetClass('ISC_TRANSACTION');

		if(!isset($_POST['Ds_ErrorCode']) || !isset($_POST['Ds_ErrorMessage']) || !isset($_POST['Ds_Response'])) {
			// Bad order details
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('BancomerErrorInvalid'), print_r($_POST, true));
			return false;
		}

		$newTransaction = array(
			'providerid' => $this->GetId(),
			'transactiondate' => time(),
			'transactionid' => $_POST['Ds_Signature'],
			'orderid' => array_keys($this->GetOrders()),
			'message' => '',
			'status' => '',
			'amount' => $amount,
			'extrainfo' => array()
		);

		$orderPaymentStatus = '';
		switch($_POST['Ds_Response']) {
			case "000":
				$orderPaymentStatus = 'captured';
				$newTransaction['status'] = TRANS_STATUS_COMPLETED;
				$newOrderStatus = ORDER_STATUS_AWAITING_FULFILLMENT;
				break;
			case "001":
				$orderPaymentStatus = 'declined';
				$newTransaction['status'] = TRANS_STATUS_DECLINED;
				$newOrderStatus = ORDER_STATUS_DECLINED;
				break;
		}

		$newTransaction['message'] = $_POST['Ds_ErrorMessage'];

		$transactionId = $transaction->Create($newTransaction);

		$oldOrderStatus = $this->GetOrderStatus();
		
		$orderIDs = array();
		// Update the status for all orders that we've just received the payment for
		foreach($this->GetOrders() as $orderId => $order) {
			$status = $newOrderStatus;
			// If it's a digital order & awaiting fulfillment, automatically complete it
			if($order['ordisdigital'] && $status == ORDER_STATUS_AWAITING_FULFILLMENT) {
				$status = ORDER_STATUS_COMPLETED;
			}
			UpdateOrderStatus($orderId, $status);
			$orderIDs[] = $orderId;
		}

		$updatedOrder = array(
			'ordpayproviderid' => $_POST['Ds_Signature'],
			'ordpaymentstatus' => $orderPaymentStatus,
		);

		$this->UpdateOrders($updatedOrder, $orderIDs);
		
		// If the order was previously incomplete, we need to do some extra work
		if($oldOrderStatus == ORDER_STATUS_INCOMPLETE) {
			// If a customer doesn't return to the store from PayPal, their cart will never be
			// emptied. So what we do here, is if we can, load up the existing customers session
			// and empty the cart and kill the checkout process. When they next visit the store,
			// everything should be "hunky-dory."
			session_write_close();
			$session = new ISC_SESSION($sessionToken[1]);
		}

		// This was a successful order
		$oldStatus = GetOrderStatusById($oldOrderStatus);
		if(!$oldStatus) {
			$oldStatus = 'Incomplete';
		}
		$newStatus = GetOrderStatusById($newOrderStatus);
		$extra = sprintf(GetLang('BancomerSuccessDetails'), implode(', ', array_keys($this->GetOrders())), $amount, $_POST['Ds_Signature'], $_POST['Ds_Response'], $newStatus, $oldStatus);
		$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang('BancomerSuccess'), $extra);
		return true;
	}
}