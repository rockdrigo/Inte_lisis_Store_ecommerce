<?php
	class CHECKOUT_LINKPOINTCONNECT extends ISC_CHECKOUT_PROVIDER
	{
		/*
			The LinkPoint store number
		*/
		private $_storenumber = "";


		/*
			Should the order be passed through in test mode?
		*/
		private $_testmode = "";

		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the LinkPoint Connect checkout module
			parent::__construct();
			$this->_name = GetLang('LinkPointConnectName');
			$this->_image = "firstdata_logo.gif";
			$this->_description = GetLang('LinkPointConnectDesc');
			$this->_help = GetLang('LinkPointConnectHelp', array(
				'shopPathSSL' => $GLOBALS['ShopPathSSL'],
			));
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

			$this->_variables['storenumber'] = array("name" => GetLang('LinkPointStoreNumber'),
			   "type" => "textbox",
			   "help" => GetLang('LinkPointConnectStoreNumberHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['transactiontype'] = array("name" => GetLang('TransactionType'),
			   "type" => "dropdown",
			   "help" => GetLang("LinkPointConnectTransactionTypeHelp"),
			   "default" => "sale",
			   "required" => true,
			   "options" => array(GetLang("LinkPointConnectTransactionTypeSale") => "sale",
							  GetLang("LinkPointConnectTransactionTypePreauth") => "preauth"
				),
				"multiselect" => false
			);

			$this->_variables['testmode'] = array("name" => GetLang('TestMode'),
			   "type" => "dropdown",
			   "help" => GetLang("LinkPointConnectTestModeHelp"),
			   "default" => "no",
			   "required" => true,
			   "options" => array(GetLang("LinkPointConnectTestModeNo") => "NO",
							  GetLang("LinkPointConnectTestModeYes") => "YES"
				),
				"multiselect" => false
			);
		}

		/**
		*	Redirect the customer to LinkPointConnect's site to enter their payment details
		*/
		public function TransferToProvider()
		{
			// deduct tax and shipping from gateway amount instead of $this->GetSubTotal as that function doesn't factor in any discounts,
			// which results in a gateway error if subtotal + shipping + tax != charge (gateway) amount
			$subtotal = $this->GetGatewayAmount() - $this->GetTaxCost() - $this->GetShippingCost() - $this->GetHandlingCost();
			$subtotal = number_format($subtotal, 2, '.', '');

			$shippingcost = number_format($this->GetShippingCost() + $this->GetHandlingCost(), 2, '.', '');
			$taxcost = number_format($this->GetTaxCost(), 2, '.', '');

			$total = number_format($this->GetGatewayAmount(), 2, '.', '');

			$this->_storenumber = $this->GetValue("storenumber");
			$transactiontype = $this->GetValue("transactiontype");
			$testmode_on = $this->GetValue("testmode");

			if($testmode_on == "YES") {
				$linkpointconnect_url = "https://www.staging.linkpointcentral.com/lpc/servlet/lppay";
			} else {
				$linkpointconnect_url = "https://www.linkpointcentral.com/lpc/servlet/lppay";
			}

			// Load the pending order
			$order = LoadPendingOrderByToken($_COOKIE['SHOP_ORDER_TOKEN']);

			$shippingAddress = $this->getShippingAddress();

			$bcountry = GetCountryISO2ById($order['ordbillcountryid']);
			$scountry = $shippingAddress['country_iso2'];

			$phone = $order['ordbillphone'];
			$phone = preg_replace("#[^\+0-9]+#", "", $phone);

			//if it's us, we need to have find the us state code
			if($bcountry == "US") {
				$bstate = GetStateISO2ById($order['ordbillstateid']);
				$bstate_name='bstate';
			} else {
				$bstate = $order['ordbillstate'];
				$bstate_name='bstate2';
			}

			$billstate = 'name="' . $bstate_name . '" value="' . isc_html_escape($bstate) . '"';

			if($scountry == "US") {
				$sstate = GetStateISO2ById($shippingAddress['state_id']);
				$sstate_name='sstate';
			} else {
				$sstate = $shippingAddress['state'];
				$sstate_name='sstate2';
			}

			$shipstate = 'name="' . $sstate_name . '" value="' . isc_html_escape($sstate) . '"';

			?>
				<html>
					<head>
						<title><?php echo GetLang('RedirectingToLinkPointConnect'); ?></title>
					</head>

					<body onload="document.forms[0].submit()">
						<a href="javascript:void(0)" onclick="document.forms[0].submit()" style="color:gray; font-size:12px"><?php echo GetLang('ClickIfNotRedirected'); ?></a>
						<form name="linkpointconnect" id="linkpointconnect" action="<?php echo $linkpointconnect_url; ?>" method="post">
							<input type="hidden" name="mode" value="fullpay">
							<input type="hidden" name="chargetotal" value="<?php echo $total;?>">
							<input type="hidden" name="tax" value="<?php echo $taxcost;?>">
							<input type="hidden" name="shipping" value="<?php echo $shippingcost;?>">
							<input type="hidden" name="subtotal" value="<?php echo $subtotal;?>">



							<input type="hidden" name="storename" value="<?php echo $this->_storenumber;?>">
							<input type="hidden" name="txntype" value="<?php echo $transactiontype;?>">

							<input type="hidden" name="bname" value="<?php echo isc_html_escape($order['ordbillfirstname'].' '.$order['ordbilllastname']); ?>" />
							<input type="hidden" name="email" value="<?php echo isc_html_escape($order['ordbillemail']); ?>" />
							<input type="hidden" name="phone" value="<?php echo $phone; ?>" />


							<input type="hidden" name="baddr1" value="<?php echo isc_html_escape($order['ordbillstreet1']); ?>" />
							<input type="hidden" name="baddr2" value="<?php echo isc_html_escape($order['ordbillstreet2']); ?>" />
							<input type="hidden" name="bcountry" value="<?php echo isc_html_escape($bcountry); ?>" />
							<input type="hidden" name="bzip" value="<?php echo isc_html_escape($order['ordbillzip']); ?>" />
							<input type="hidden" name="bcity" value="<?php echo isc_html_escape($order['ordbillsuburb']); ?>" />
							<input type="hidden" <?php echo $billstate; ?> />


							<input type="hidden" name="sname" value="<?php echo isc_html_escape($shippingAddress['first_name'].' '.$shippingAddress['last_name']); ?>" />
							<input type="hidden" name="saddr1" value="<?php echo isc_html_escape($shippingAddress['address_1']); ?>" />
							<input type="hidden" name="saddr2" value="<?php echo isc_html_escape($shippingAddress['address_2']); ?>" />
							<input type="hidden" name="scountry" value="<?php echo isc_html_escape($scountry); ?>" />
							<input type="hidden" name="szip" value="<?php echo isc_html_escape($shippingAddress['zip']); ?>" />
							<input type="hidden" name="scity" value="<?php echo isc_html_escape($oshippingAddressrder['city']); ?>" />
							<input type="hidden" <?php echo $shipstate; ?> />


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
		*	Verify the order by posting back to LinkPointConnect.
		*/
		public function VerifyOrder(&$PendingOrder)
		{
			if ($_REQUEST['status'] == 'APPROVED') {
				$transactiontype = $this->GetValue("transactiontype");
				if ($transactiontype == 'preauth') {
					$PendingOrder['paymentstatus'] = 2;
				} elseif ($transactiontype == 'sale') {
					$PendingOrder['paymentstatus'] = 1;
				}
				$successMsg = sprintf(GetLang('LinkPointConnectSuccess'),$PendingOrder['orderid']);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $successMsg);
				return true;
			} else {
				$failmsg = sprintf(GetLang('LinkPointConnectInvalidMsg'), $_REQUEST['fail_reason']);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $failmsg);
				return false;
			}
		}
	}
