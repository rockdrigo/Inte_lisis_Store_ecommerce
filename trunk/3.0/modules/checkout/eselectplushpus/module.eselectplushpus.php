<?php
class CHECKOUT_ESELECTPLUSHPUS extends ISC_CHECKOUT_PROVIDER
{
	/*
		The eSelectPlus store number
	*/
	private $_hostedpaypageid = "";

	/*
		The eSelectPlus hosted paypage Token
	*/
	private $_hostedpaypagetoken = "";

	/*
		Should the order be passed through in test mode?
	*/
	private $_testmode = "";

	/*
		Checkout class constructor
	*/
	public function __construct()
	{
		// Setup the required variables for the eSelectPlus Connect checkout module
		parent::__construct();
		$this->_name = GetLang('eSelectPlusName');
		$this->_image = "eSelectPlus_logo.gif";
		$this->_description = GetLang('eSelectPlusDesc');
		$this->_help = GetLang('eSelectPlusHelp', array(
			'shopPathSSL' => $GLOBALS['ShopPathSSL'],
		));
		$this->_height = 0;
	}

	/**
	* Custom variables for the checkout module. Custom variables are stored in the following format:
	* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
	* variable_type types are: text,number,password,radio,dropdown
	* variable_options is used when the variable type is radio or dropdown and is a name/value array.
	*/
	public function SetCustomVars()
	{

		$this->_variables['displayname'] = array("name" => GetLang('DisplayName'),
		   "type" => "textbox",
		   "help" => GetLang('DisplayNameHelp'),
		   "default" => $this->GetName(),
		   "required" => true
		);

		$this->_variables['hostedpaypageid'] = array("name" => GetLang('HostedPayPageUSID'),
		   "type" => "textbox",
		   "help" => GetLang('HostedPayPageIDHelp'),
		   "default" => "",
		   "required" => true
		);

		$this->_variables['hostedpaypagetoken'] = array("name" => GetLang('HostedPayPageToken'),
		   "type" => "textbox",
		   "help" => GetLang('HostedPayPageTokenHelp'),
		   "default" => "",
		   "required" => true
		);

		$this->_variables['testmode'] = array("name" => GetLang('TestMode'),
		   "type" => "dropdown",
		   "help" => GetLang("eSelectPlusTestModeHelp"),
		   "default" => "no",
		   "required" => true,
		   "options" => array(GetLang("eSelectPlusTestModeNo") => "NO",
						  GetLang("eSelectPlusTestModeYes") => "YES"
			),
			"multiselect" => false
		);
	}

	/**
	*	Redirect the customer to eSelectPlus's site to enter their payment details
	*/
	public function TransferToProvider()
	{
		$total = number_format($this->GetGatewayAmount(), 2,'.', '');

		$this->_hostedpaypageid = $this->GetValue("hostedpaypageid");
		$this->_hostedpaypagetoken = $this->GetValue("hostedpaypagetoken");
		$testmode_on = $this->GetValue("testmode");
		if ($testmode_on == "YES") {
			$eselectplus_url = "https://esplusqa.moneris.com/DPHPP/index.php";
		} else {
			$eselectplus_url = "https://esplus.moneris.com/DPHPP/index.php";
		}

		$billingDetails = $this->GetBillingDetails();
		$shippingAddress = $this->getShippingAddress();

		$formFields = array(
			'hpp_id' 			=> $this->_hostedpaypageid,
			'hpp_key'			=> $this->_hostedpaypagetoken,
			'amount' 			=> $total,
			'cust_id' 			=> GetLang('eSelectPlusOrder', array('id' => $this->GetCombinedOrderId())),
			'client_email' 		=> $billingDetails['ordbillemail'],
			'od_bill_company'	=> $billingDetails['ordbillcompany'],
			'od_bill_firstname' => $billingDetails['ordbillfirstname'],
			'od_bill_lastname' 	=> $billingDetails['ordbilllastname'],
			'od_bill_address' 	=> $billingDetails['ordbillstreet1'] . ", " . $billingDetails['ordbillstreet2'],
			'od_bill_city' 		=> $billingDetails['ordbillsuburb'],
			'od_bill_state' 	=> $billingDetails['ordbillstate'],
			'od_bill_zipcode' 	=> $billingDetails['ordbillzip'],
			'od_bill_country' 	=> $billingDetails['ordbillcountry'],
			'od_bill_phone' 	=> $billingDetails['ordbillphone'],
			'od_ship_company' 	=> $shippingAddress['company'],
			'od_ship_firstname' => $shippingAddress['first_name'],
			'od_ship_lastname' 	=> $shippingAddress['last_name'],
			'od_ship_address' 	=> $shippingAddress['address_1'] . ", " . $shippingAddress['address_2'],
			'od_ship_city' 		=> $shippingAddress['city'],
			'od_ship_state' 	=> $shippingAddress['state'],
			'od_ship_zipcode' 	=> $shippingAddress['zip'],
			'od_ship_country' 	=> $shippingAddress['country'],
			'od_ship_phone' 	=> $shippingAddress['phone']
		);

		// add the items
		$orders = $this->GetOrders();
		$products = array();
		foreach ($orders as $order) {
			$order = GetOrder($order['orderid']);
			foreach ($order['products'] as $product) {
				$products[] = $product;
			}
		}

		$i = 1;
		foreach ($products as $product) {
			$productFields = array(
				'li_id'.$i			=> $product['ordprodsku'],
				'li_description'.$i	=> $product['ordprodname'],
				'li_quantity'.$i 	=> $product['ordprodqty'],
				'li_price'.$i 		=> number_format($product['total_inc_tax'], 2,'.','')
			);

			$formFields += $productFields;

			$i++;
		}

		// add the shipping
		$shipping_cost = $this->GetShippingCost() + $this->GetHandlingCost();
		if ($shipping_cost > 0) {
			$formFields['li_shipping'] = number_format($shipping_cost, 2, '.', '');
		}

		 // add tax
		if ($this->GetTaxCost() > 0) {
			$formFields['li_taxes'] = number_format($this->GetTaxCost(), 2, '.', '');
		}

		$this->RedirectToProvider($eselectplus_url, $formFields);
	}

	/**
	*	Return the unique order token which was saved as a cookie pre-payment
	*/
	public function GetOrderToken()
	{
		return @$_COOKIE['SHOP_ORDER_TOKEN'];
	}

	/**
	*	Verify the order by posting back to eSelectPlus.
	*/
	public function VerifyOrder(&$PendingOrder)
	{

		if (isset($_REQUEST['response_code'])) {
			$response_code = (int) $_REQUEST['response_code'];
			if ($response_code < 50) { // approved
				if ($_REQUEST['txn_type'] == 'preauth' || $_REQUEST['txn_type'] == 'cavv_preauth' ) {
					$PendingOrder['paymentstatus'] = PAYMENT_STATUS_PENDING;
				}
				elseif ($_REQUEST['txn_type'] == 'purchase' || $_REQUEST['txn_type'] == 'cavv_purchase') {
					$PendingOrder['paymentstatus'] = PAYMENT_STATUS_PAID;
				}

				if(isset($_REQUEST['order_no'])) {
					$updatedOrder = array(
						'ordpayproviderid'	=> $_REQUEST['order_no'],
					);

					//update the orders table with new transaction details
					$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$PendingOrder['orderid']."'");
				}

				$successPaymentMsg = GetLang('eSelectPlusSuccess', array('orderId' => $PendingOrder['orderid']));
				$msgDetails = GetLang('eSelectPlusSuccessDetails' , array('code' => $response_code, 'transId' =>  $_REQUEST['order_no'], 'transType' => $_REQUEST['txn_type'], 'refNum' => $_REQUEST['ref_num'], 'message' => $_REQUEST['message']));
				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $successPaymentMsg, $msgDetails);
				return true;
			}
			elseif ($response_code >= 50) { // declined
				$invalidPaymentMsg = GetLang('eSelectPlusDeclined', array('orderId' => $PendingOrder['orderid']));
				$msgDetails = GetLang('eSelectPlusDeclinedDetails' , array('code' => $response_code, 'transId' =>  $_REQUEST['order_no'], 'message' => $_REQUEST['message']));
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $invalidPaymentMsg, $msgDetails);
				return false;
			}
		}
		elseif (isset($_REQUEST['cancel'])) { // user cancelled the transaction
			// redirect back to cart
			header('Location: ' . GetConfig('ShopPath') . '/cart.php');
			exit;
		}
		else {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('eSelectPlusErrorInvalidMsgNoRspCode', array('orderId' => $PendingOrder['orderid'])), @$_REQUEST['message']);
			return false;
		}
	}


	/**
	* get the transaction information back from eselect plus
	* Display the transaction information
	*/
	public function ShowOrderConfirmation($order)
	{
		$GLOBALS['MerchantName'] = GetConfig('StoreName');
		$GLOBALS['MerchantURL'] = GetConfig('ShopPathNormal');

		if ($_REQUEST['txn_type'] == 'preauth' || $_REQUEST['txn_type'] == 'cavv_preauth' ) {
			$GLOBALS['TransactionType'] = GetLang('TransactionTypeSale');
		}
		elseif ($_REQUEST['txn_type'] == 'purchase' || $_REQUEST['txn_type'] == 'cavv_purchase') {
			$GLOBALS['TransactionType'] = GetLang('TransactionTypeAuth');
		}

		$GLOBALS['Amount'] 				= FormatPrice($_REQUEST['amount']);
		$GLOBALS['DateTime'] 			= date('jS M Y G:i:s'); // must include day, month and year and 24 hour time (Appendex C)
		$GLOBALS['ReferenceNumber'] 	= $_REQUEST['ref_num'];
		$GLOBALS['AuthorisationCode']	= $_REQUEST['auth_code'];
		$GLOBALS['ResponseCode'] 		= $_REQUEST['response_code'];
		$GLOBALS['ResponseMessage'] 	= $_REQUEST['message'];
		$GLOBALS['CardholderName'] 		= $_REQUEST['cardholder'];
		$GLOBALS['InvoiceNumber'] 		= $_REQUEST['order_no'];

		return $this->ParseTemplate('eselectplushpus.receipt', true);
	}
}
