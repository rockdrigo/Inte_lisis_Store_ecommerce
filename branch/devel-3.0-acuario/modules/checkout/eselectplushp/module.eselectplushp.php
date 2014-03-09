<?php
class CHECKOUT_ESELECTPLUSHP extends ISC_CHECKOUT_PROVIDER
{
	/*
		The eSelectPlus store number
	*/
	private $_hostedpaypageid = "";

	/*
		The eSelectPlus hosted paypage Token
	*/
	private $_hostedpaypageToken = "";

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

		$this->_variables['hostedpaypageid'] = array("name" => GetLang('HostedPayPageID'),
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
		$total = number_format($this->gettotal(), 2,'.', '');

		$this->_hostedpaypageid = $this->GetValue("hostedpaypageid");
		$this->_hostedpaypagetoken = $this->GetValue("hostedpaypagetoken");
		$testmode_on = $this->GetValue("testmode");
		if ($testmode_on == "YES") {
			$eselectplus_url = "https://esqa.moneris.com/HPPDP/index.php";
		} else {
			$eselectplus_url = "https://www3.moneris.com/HPPDP/index.php";
		}

		$order = LoadPendingOrderByToken($_COOKIE['SHOP_ORDER_TOKEN']);

		// get the tax and shipping costs
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

		$shippingAddress = $this->getShippingAddress();
		?>
			<html>
				<head>
					<title><?php echo GetLang('RedirectingToeSelectPlus'); ?></title>
				</head>

				<body onload="document.forms[0].submit()">
					<a href="javascript:void(0)" onclick="document.forms[0].submit()" style="color:gray; font-size:12px"><?php echo GetLang('ClickIfNotRedirected'); ?></a>
					<form name="eselectplus" id="eselectplus" action="<?php echo $eselectplus_url; ?>" method="post">
						<input type="hidden" name="ps_store_id" value="<?php echo $this->_hostedpaypageid;?>">
						<input type="hidden" name="hpp_key" value="<?php echo $this->_hostedpaypagetoken;?>">
						<input type="hidden" name="charge_total" value="<?php echo $total;?>">

						<input type="hidden" name="bill_company_name" value="<?php echo isc_html_escape($order['ordbillcompany']); ?>" />
						<input type="hidden" name="bill_first_name" value="<?php echo isc_html_escape($order['ordbillfirstname']); ?>" />
						<input type="hidden" name="bill_last_name" value="<?php echo isc_html_escape($order['ordbilllastname']); ?>" />
						<input type="hidden" name="bill_address_one" value="<?php echo isc_html_escape($order['ordbillstreet1']); ?>" />
						<input type="hidden" name="bill_city" value="<?php echo isc_html_escape($order['ordbillsuburb']); ?>" />
						<input type="hidden" name="bill_state_or_province" value="<?php echo isc_html_escape($order['ordbillstate']); ?>" />
						<input type="hidden" name="bill_postal_code" value="<?php echo isc_html_escape($order['ordbillzip']); ?>" />
						<input type="hidden" name="bill_country" value="<?php echo isc_html_escape($order['ordbillcountry']); ?>" />
						<input type="hidden" name="bill_phone" value="<?php echo isc_html_escape($order['ordbillphone']); ?>" />

						<input type="hidden" name="ship_company_name" value="<?php echo isc_html_escape($shippingAddress['company']); ?>" />
						<input type="hidden" name="ship_first_name" value="<?php echo isc_html_escape($shippingAddress['first_name']); ?>" />
						<input type="hidden" name="ship_last_name" value="<?php echo isc_html_escape($shippingAddress['last_name']); ?>" />
						<input type="hidden" name="ship_address_one" value="<?php echo isc_html_escape($shippingAddress['address_1']); ?>" />
						<input type="hidden" name="ship_city" value="<?php echo isc_html_escape($shippingAddress['city']); ?>" />
						<input type="hidden" name="ship_state_or_province" value="<?php echo isc_html_escape($shippingAddress['state']); ?>" />
						<input type="hidden" name="ship_postal_code" value="<?php echo isc_html_escape($shippingAddress['zip']); ?>" />
						<input type="hidden" name="ship_country" value="<?php echo isc_html_escape($shippingAddress['country']); ?>" />


					<?php
					if ($gst>0) {
					?>
						<input type="hidden" name="gst" value="<?php echo $gst;?>">
					<?php }
					if ($pst>0) {
					?>
						<input type="hidden" name="pst" value="<?php echo $pst;?>">
					<?php }
					if ($pst>0) {
					?>
						<input type="hidden" name="hst" value="<?php echo $hst;?>">
					<?php }
					if ($shipping_cost>0) {
					?>
						<input type="hidden" name="shipping_cost" value="<?php echo $shipping_cost;?>">
					<?php }?>

					<input type="hidden" name="email" value="<?php echo isc_html_escape($order['ordbillemail']); ?>">
					</form>
				</body>
			</html>
		<?php
		exit;
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
			if ($response_code < 50 && $response_code>=0) {
				if ($_REQUEST['trans_name'] == 'preauth' || $_REQUEST['trans_name'] == 'cavv_preauth' ) {
					$PendingOrder['paymentstatus'] = PAYMENT_STATUS_PENDING;
				} elseif ($_REQUEST['trans_name'] == 'purchase' || $_REQUEST['trans_name'] == 'cavv_purchase') {
					$PendingOrder['paymentstatus'] = PAYMENT_STATUS_PAID;
				}

				if(isset($_REQUEST['order_no'])) {
					$updatedOrder = array(
						'ordpayproviderid'	=> $_REQUEST['order_no'],
					);

					//update the orders table with new transaction details
					$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$PendingOrder['orderid']."'");
				}

				$successPaymentMsg = sprintf(GetLang('eSelectPlusSuccess'), $response_code);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $successPaymentMsg);
				return true;
			} else {
				$invalidPaymentMsg = sprintf(GetLang('eSelectPlusErrorInvalidMsg'), $response_code);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $invalidPaymentMsg);
				return false;
			}
		} else {
			if (isset($_REQUEST['cancelTXN'])) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('eSelectPlusCancelTransaction'));
				return false;
			} else {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('eSelectPlusErrorInvalidMsgNoRspCode'));
				return false;
			}
		}
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
			'InvoiceNumber'			=> 'response_order_id'
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

		return $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('eSelectPlusConfirmationDetails');
	}
}