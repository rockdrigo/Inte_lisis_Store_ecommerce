<?php

	class CHECKOUT_NMI extends ISC_CHECKOUT_PROVIDER
	{
		/**
		 * @var boolean True if this checkout module requires SSL or not.
		 */
		protected $requiresSSL = false;

		/**
		 * @var boolean Does this provider support orders from more than one vendor?
		 */
		protected $supportsVendorPurchases = true;
		protected $supportsMultiShipping = true;

		/**
		 * The constructor.
		 */
		public function __construct()
		{
			// Setup the required variables for the Authorize.net checkout module
			parent::__construct();

			$this->SetName(GetLang('NMIName'));
			//$this->SetImage("NMI_logo.gif");
			$this->SetDescription(GetLang('NMIDesc'));
			//$this->SetHelpText(sprintf(GetLang('NMIHelp'), $GLOBALS['ShopPath']));
		}

		/**
		 * Set up the configuration options for this module.
		 */
		public function SetCustomVars()
		{
			$this->_variables['displayname'] = array(
				"name" => GetLang('DisplayName'),
				"type" => "textbox",
				"help" => GetLang('DisplayNameHelp'),
				"default" => $this->GetName(),
				"required" => true
			);

			$this->_variables['username'] = array(
				"name" => GetLang('Username'),
				"type" => "textbox",
				"help" => GetLang('NMIUsernameHelp'),
				"default" => '',
				"required" => true
			);
			
			$this->_variables['password'] = array(
				"name" => GetLang('Password'),
				"type" => "password",
				"help" => GetLang('NMIPasswordHelp'),
				"default" => '',
				"required" => true
			);
			
			$this->_variables['transactiontype'] = array(
				"name" => GetLang('TransactionType'),
				"type" => "dropdown",
				"help" => GetLang('TransactionTypeHelp'),
				"default" => "no",
				"savedvalue" => array(),
				"required" => true,
				"options" => array(
					GetLang('TransactionTypeSale') => "sale",
					GetLang('TransactionTypeAuthorize') => "auth"
				),
				"multiselect" => false
			);
		}

		/**
		 * Generate the payment form to collect payment details and pass them back
		 * to the payment provider.
		 *
		 * @return string The generated payment form.
		 */
		public function ShowPaymentForm()
		{
			$GLOBALS['NMIMonths'] = "";
			$GLOBALS['NMIYears'] = "";

			for($i = 1; $i <= 12; $i++) {
				$stamp = mktime(0, 0, 0, $i, 15, isc_date("Y"));

				$i = str_pad($i, 2, "0", STR_PAD_LEFT);

				if (@$_POST['NMI_ccexpm'] == $i) {
					$sel = 'selected="selected"';
				} else {
					$sel = "";
				}

				$GLOBALS['NMIMonths'] .= sprintf("<option %s value='%s'>%s</option>", $sel, $i, isc_date("M", $stamp));
			}

			for($i = isc_date("Y"); $i < isc_date("Y")+10; $i++) {

				if (@$_POST['NMI_ccexpy'] == substr($i, 2, 2)) {
					$sel = 'selected="selected"';
				} else {
					$sel = "";
				}
				$GLOBALS['NMIYears'] .= sprintf("<option %s value='%s'>%s</option>", $sel, substr($i, 2, 2), $i);
			}

			if(isset($_POST['NMI_cccode'])) {
				$GLOBALS['NMICCV2'] = $_POST['NMI_cccode'];
			}
			
			// Grab the billing details for the order
			$billingDetails = $this->GetBillingDetails();

			$GLOBALS['NMIName'] = isc_html_escape($billingDetails['ordbillfirstname'].' '.$billingDetails['ordbilllastname']);
			// Format the amount that's going to be going through the gateway
			$GLOBALS['OrderAmount'] = CurrencyConvertFormatPrice($this->GetGatewayAmount());

			// Was there an error validating the payment? If so, pre-fill the form fields with the already-submitted values
			if($this->HasErrors()) {
				$GLOBALS['NMIName'] = isc_html_escape($_POST['NMI_name']);
				$GLOBALS['NMINum'] = isc_html_escape($_POST['NMI_ccno']);
				$GLOBALS['NMIErrorMessage'] = implode("<br />", $this->GetErrors());
			}
			else {
				// Hide the error message box
				$GLOBALS['HideNMIError'] = "none";
			}

			// Collect their details to send through to Authorize.NET
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("NMI");
			return $GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate(true);
		}

		/**
		 * Process the details entered on the payment form.
		 *
		 * @return boolean True if valid details and payment has been processed. False if not.
		 */
		public function ProcessPaymentForm($dataSource = array())
		{
			if (empty($dataSource)) {
				$dataSource = $_POST;
			}

			$bill_firstname = "";
			$bill_lastname = "";
			$result = "";
			$an_url = "https://secure.networkmerchants.com/api/transact.php";
			$an_pp_url = "secure.networkmerchants.com";
			$error = false;

			$requiredFields = array(
				"NMI_name",
				"NMI_ccno",
				"NMI_ccexpm",
				"NMI_ccexpy",
				"NMI_cccode",
			);

			$missingFields = false;
			foreach($requiredFields as $field) {
				if(!isset($dataSource[$field]) || !$dataSource[$field]) {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError('php', 'missing field: ' . $field, '');
					$missingFields = true;
				}
			}

			if(isset($_COOKIE['SHOP_ORDER_TOKEN']) && $missingFields == false) {
				$ccname = $dataSource['NMI_name'];
				$ccnum = $dataSource['NMI_ccno'];
				$ccexpm = $dataSource['NMI_ccexpm'];
				$ccexpy = $dataSource['NMI_ccexpy'];
				$ccexp = sprintf("%s%s", $ccexpm, $ccexpy);
				$cccode = $dataSource['NMI_cccode'];

				// Load the Authorize.net transaction Type
				$transactionType = $this->GetValue('transactiontype');

				$orders = $this->GetOrders();
				if(count($orders) == 1) {
					list(,$order) = each($orders);
					$invoiceNum = $order['orderid'];
				}
				else {
					$invoiceNum = '';
				}

				$orderIds = '#'.implode(', #', array_keys($orders));
				$order_desc = sprintf(GetLang('YourOrderFrom'), $GLOBALS['StoreName']).' ('.$orderIds.')';

				$addressDetails = $this->GetBillingDetails();

				$shippingAddress = $this->getShippingAddress();

				// Arrange the data into name/value pairs ready to send
				$an_values = array (
					"type" => $transactionType,
					"username" => $this->GetValue('username'),
					"password" => $this->GetValue('password'),
					"ccnumber" => $ccnum,
					"ccexp" => $ccexp,
					"checkname" => '',
					"checkaba" => '',
					"checkaccount" => '',
					"account_holder_type" => '',
					"account_type" => '',
					"sec_code" => '',
					"amount" => $this->GetGatewayAmount(),
					"cvv" => $cccode,
					"payment" => 'creditcard',
					"processor_id" => '',
					/*"dup_seconds" => 0,*/
					"descriptor" => '',
					"descriptor_phone" => '',
					"product_sku_#" => '',
					"orderdescription" => $order_desc,
					"orderid" => $orderIds,
					"ipaddress" => GetIP(),
					"tax" => $this->GetTaxCost(true),
					"shipping" => $this->GetShippingCost(),
					"ponumber" => '',
					"firstname" => $addressDetails['ordbillfirstname'],
					"lastname" => $addressDetails['ordbilllastname'],
					"company" => $addressDetails['ordbillcompany'],
					"address1" => trim($addressDetails['ordbillstreet1']),
					"address2" => trim($addressDetails['ordbillstreet2']),
					"city" => $addressDetails['ordbillsuburb'],
					"state" => $addressDetails['ordbillstate'],
					"zip" => $addressDetails['ordbillzip'],
					"country" => $addressDetails['ordbillcountry'],
					"phone" => $addressDetails['ordbillphone'],
					"fax" => '',
					"email" => $addressDetails['ordbillemail'],
					"validation" => '',
					"shipping_firstname" => $shippingAddress['first_name'],
					"shipping_lastname" => $shippingAddress['last_name'],
					"shipping_company" => $shippingAddress['company'],
					"shipping_address1" => trim($shippingAddress['address_1']),
					"shipping_address2" => trim($shippingAddress['address_1']),
					"shipping_city" => $shippingAddress['city'],
					"shipping_state" => $shippingAddress['state'],
					"shipping_zip" => $shippingAddress['zip'],
					"shipping_country" => $shippingAddress['country'],
					"shipping_email" => $addressDetails['ordbillemail'],
				);

				// Merge the name/value pairs into a string
				$an_data = '';
				foreach($an_values as $k=>$v) {
					$an_data .= sprintf("%s=%s&", $k, urlencode($v));
				}

				$an_data = rtrim($an_data, '&');

				$an_response = $this->ConnectToProvider($an_url, $an_pp_url, $an_data);
				if(!$an_response || empty($an_response)) {
					return false;
				}

				if (isset($an_response['response']) && $an_response['response'] == 1) {
					$extraInfo = '';
					$paymentStatus = '';

					if($transactionType == 'auth') {
						$paymentStatus = 'authorized';
					} else if ($transactionType == 'sale') {
						$paymentStatus = 'captured';
					}

					//store credit card number, used in refund transaction
					$cc_vars = array(
						"cc_ccno" => substr($ccnum, -4),
						"cardtype" => Store_CreditCard::getCardType($ccnum),
					);

					// Is there any existing extra info for the pending order?
					if($order['extrainfo'] != "") {
						$extraArray = @unserialize($order['extrainfo']);
						if(is_array($extraArray)) {
							$extraInfo = serialize(@array_merge($extraArray, $cc_vars));
						}
					}
					else {
						$extraInfo = serialize($cc_vars);
					}

					// Save the authorization key
					$updatedOrder = array(
						'ordpayproviderid' => $an_response['transactionid'],
						'ordpaymentstatus' => $paymentStatus,
						'extrainfo' => $extraInfo
					);

					$this->UpdateOrders($updatedOrder);

					$this->SetPaymentStatus(PAYMENT_STATUS_PAID);

					$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), sprintf(GetLang('NMISuccess'), $invoiceNum));
					return true;
				}
				else if (isset($an_response['response']) && $an_response['response'] == 2) {
						$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang('NMIErrorDeclined'), $invoiceNum, $an_response['responsetext']) , $an_response['responsetext']);
						$this->SetError('Error tarjeta declinada');
						return false;
				}
				else if (isset($an_response['response']) && $an_response['response'] == 3) {
						$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang('NMIErrorInvalid'), $invoiceNum, $an_response['responsetext']), '#'.$an_response['response_code'].' '.$an_response['responsetext']);
						$this->SetError('Error al autorizar: '.$an_response['responsetext']);
						return false;
				}
			}
			else {
				// Invalid Authorize.net response
				$this->SetError(GetLang('NMIMissingFields'));
				return false;
			}
		}

		private function ConnectToProvider($an_url, $an_pp_url, $an_data)
		{
			$an_response = array();
			// Use Authorize.net's API to charge the credit card
			if(function_exists("curl_exec")) {
				// Use CURL if it's available
				$ch = @curl_init($an_url);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $an_data);
				curl_setopt($ch, CURLOPT_TIMEOUT, 60);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

				// Setup the proxy settings if there are any
				if (GetConfig('HTTPProxyServer')) {
					curl_setopt($ch, CURLOPT_PROXY, GetConfig('HTTPProxyServer'));
					if (GetConfig('HTTPProxyPort')) {
						curl_setopt($ch, CURLOPT_PROXYPORT, GetConfig('HTTPProxyPort'));
					}
				}

				if (GetConfig('HTTPSSLVerifyPeer') == 0) {
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				}

				$result = curl_exec($ch);

				if(curl_error($ch) != '') {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang('NMIConnectErrorLogMsg'), $an_url, "(" . curl_errno($ch) . ") " . curl_error($ch)));
					$this->SetError(GetLang('NMINotSupported'));
					return false;
				}
			}
			else if(function_exists("fsockopen")) {
				$header = "";
				$header .= "POST " . $an_url . " HTTP/1.0\r\n";
				$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
				$header .= "Content-Length: " . strlen($an_data) . "\r\n\r\n";

				if($fp = @fsockopen("ssl://" . $an_pp_url, 443, $errno, $errstr, 30)) {
					@fputs($fp, $header . $an_data);

					// Read the body data
					$result = "";
					$headerdone = false;

					while(!@feof($fp)) {
						$line = @fgets($fp, 1024);

						if(@strcmp($line, "\r\n") == 0) {
							// Read the header
							$headerdone = true;
						}
						else if($headerdone) {
							// Header has been read, read the contents
							$result .= $line;
						}
					}
				}
				else {
					$this->SetError(sprintf(GetLang('NMIFSocketError'), $an_pp_url));
					return false;
				}
			}
			else {
				$this->SetError(GetLang('NMINotSupported'));
				return false;
			}

			// Check to see the we got a response
			if ($result == "") {
				$this->SetError(sprintf(GetLang('NMINoResult'), $an_pp_url));
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('NMINoResultLogSubject'), sprintf(GetLang('NMINoResultLogMsg'), $an_url));
				return false;
			}

			$an_response = explode("&", $result);
			foreach ($an_response as $value) {
				$temp = explode('=', $value);
				$an_response[$temp[0]] = $temp[1];
			}
			return $an_response;
		}

		private function GetResponseFromProvider($extraFields=array())
		{
				// Load the Authorize.net merchant ID
			$merchant_id = $this->GetValue("merchantid");

			// Load the tranaction key
			$transaction_key = $this->GetValue("transactionkey");

			$an_url = "https://secure.networkmerchants.com/api/transact.php";
			$an_pp_url = "secure.networkmerchants.com";

			$an_data = '';
			// Merge the name/value pairs into a string
			foreach($extraFields as $k=>$v) {
				$an_data .= sprintf("%s=%s&", $k, urlencode($v));
			}

			$an_data = rtrim($an_data, '&');
			$an_response = $this->ConnectToProvider($an_url, $an_pp_url, $an_data);
			return $an_response;
		}

		public function DelayedCapture($order, &$message = '', $amt=0)
		{
			if($amt == 0 || $amt > $order['total_inc_tax']) {
				$message = GetLang('DelayedCaptureIncorrectAmount');
				return false;
			}

			$amt = number_format($amt, 2);
			$orderId = $order['orderid'];
			$transactionId = $order['ordpayproviderid'];

			$extraFields = array(
				'type' => 'capture',
				'username' => $this->GetValue('username'),
				'password' => $this->GetValue('password'),
				'transactionid' => $transactionId,
				'amount' => $amt,
			);

			$an_response = $this->GetResponseFromProvider($extraFields);
			if(!$an_response || empty($an_response)) {
				$message = GetLang('ErrorConnectToProvider');
				return false;
			}
			// Based on specific items in the array we can determine if the transaction was successful or not
			if($an_response['response'] == 1) { // 1 is a success, 2 is declined and 3 is an error

				//set the message that's displayed in the front end
				$message = GetLang('DelayedCaptureSuccess');


				// Save the authorization key
				$updatedOrder = array(
					'ordpaymentstatus' => 'captured'
				);

				//update the orders table with new transaction details
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$orderId."'");


				//log the transaction in store logs
				$delayedCaptureSuccess = sprintf(GetLang('DelayedCaptureSuccessLog'), $orderId, $an_response['transactionid']);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $delayedCaptureSuccess, $an_response['responsetext']);

				return true;
			}
			else {
				//set the message that's displayed in the front end
				$message = sprintf(GetLang('DelayedCaptureFailed'), $an_response['responsetext'], $an_response['transactionid']);

				//log the transaction in store logs
				$delayedCaptureError = sprintf(GetLang('DelayedCaptureError'), $orderId, $an_response['transactionid']);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $delayedCaptureError, $an_response['responsetext']);
				return false;
			}
		}


		public function DoRefund($order, &$message = '', $amt = 0)
		{
			if($amt == 0) {
				$message = GetLang('RefundIncorrectAmount');
				return false;
			}

			$transactionId = $order['ordpayproviderid'];
			$orderId = $order['orderid'];

			$orderAmount = number_format($order['total_inc_tax'], 2);
			$amt = number_format($amt,2);
			$TotalRefundedAmt = number_format($amt+$order['ordrefundedamount'], 2);

			$extraFields = array(
				'type' => 'refund',
				'username' => $this->GetValue('username'),
				'password' => $this->GetValue('password'),
				'amount' => $amt,
				'transactionid' => $transactionId,
			);

			$an_response = $this->GetResponseFromProvider($extraFields);
			if(!$an_response || empty($an_response)) {
				$message = GetLang('ErrorConnectToProvider');
				return false;
			}

			if($an_response['response'] == 1) {

				$message = GetLang('RefundSuccess');

				//if total refunded is less than the order total amount
				if($TotalRefundedAmt < $orderAmount) {
					$paymentStatus = 'partially refunded';
				} else {
					$paymentStatus = 'refunded';
				}

				$updatedOrder = array(
					'ordpaymentstatus' => $paymentStatus,
					'ordrefundedamount'	=> $TotalRefundedAmt,
				);

				//update the orders table with new transaction details
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$orderId."'");


				$refundSuccess = sprintf(GetLang('RefundSuccessLog'), $orderId);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment',  $this->GetName()), $refundSuccess);
				return true;

			} else {
				$responseMsg = '';
				$transid = '';
				if(isset($an_response['responsetext'])) {
					$responseMsg = $an_response['responsetext'];
				}
				if(isset($an_response['transactionid'])) {
					$transid = isc_html_escape($an_response['transactionid']);
				}
				$message = sprintf(GetLang('RefundFailed'), $responseMsg, $transid);

				$RefundError = sprintf(GetLang('RefundError'), $orderId, $transid);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $RefundError, $responseMsg);
				return false;

			}
		}

		public function DoVoid($orderId, $transactionId, &$message = '')
		{
			$extraFields = array(
				'type' => 'void',
				'username' => $this->GetValue('username'),
				'password' => $this->GetValue('password'),
				'transactionid' => $transactionId
			);

			$an_response = $this->GetResponseFromProvider($extraFields);
			if(!$an_response || empty($an_response)) {
				$message = GetLang('ErrorConnectToProvider');
				return false;
			}

			if($an_response['response'] == 1) {

				$message = GetLang('VoidSuccess');

				$updatedOrder = array(
					'ordpaymentstatus'	=> 'void',
					'ordpayproviderid'	=> $an_response['transactionid'],
				);
				//update the orders table with new transaction details
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$orderId."'");

				$voidSuccess = sprintf(GetLang('VoidSuccessLog'), $orderId, $an_response[6]);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment',  $this->GetName()), $voidSuccess);
				return true;

			} else {
				$responseMsg = '';
				$transid = '';
				if(isset($an_response['responsetext'])) {
					$responseMsg = $an_response['responsetext'];
				}
				if(isset($an_response['transactionid'])) {
					$transid = isc_html_escape($an_response['transactionid']);
				}
				$message = sprintf(GetLang('VoidFailed'), $responseMsg, $transid);

				$voidError = sprintf(GetLang('VoidError'), $orderId, $transid);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $voidError, $responseMsg);
				return false;
			}
		}

		/**
		 * Return a list of any manual payment fields that should be shown when creating/editing
		 * an order via the control panel, if any.
		 *
		 * @param array An array containing the details of existing values, if any.
		 * @return array An array of manual payment fields.
		 */
		public function GetManualPaymentFields($existingOrder=array())
		{
			$monthOptions = '';
			for($i = 1; $i <= 12; $i++) {
				$stamp = mktime(0, 0, 0, $i, 15, date("Y"));
				$i = str_pad($i, 2, "0", STR_PAD_LEFT);

				$monthOptions .= '<option value="'.$i.'">'.date('M', $stamp).'</option>';
			}

			$yearOptions = '';
			for($i = date("Y"); $i <= date("Y")+10; $i++) {
				$value = isc_substr($i, 2, 2);
				$yearOptions .= '<option value="'.$value.'">'.$i.'</option>';
			}

			$fields = array(
				'NMI_name' => array(
					'type' => 'text',
					'title' => GetLang('CCManualCardHoldersName'),
					'value' => '',
					'required' => true
				),
				'NMI_ccno' => array(
					'type' => 'text',
					'title' => GetLang('CCManualCreditCardNo'),
					'value' => '',
					'required' => true
				),
				'NMI_ccexp' => array(
					'type' => 'html',
					'title' => GetLang('CCManualExpirationDate'),
					'html' => '
						<select name="paymentField[' . $this->GetId() . '][NMI_ccexpm]">'.$monthOptions.'</select>
						&nbsp;
						<select name="paymentField[' . $this->GetId() . '][NMI_ccexpy]">'.$yearOptions.'</select>
					',
					'required' => true
				),
				'NMI_cccode' => array(
					'type' => 'text',
					'title' => GetLang('CCManualCreditCardCCV2'),
					'value' => '',
					'required' => true,
					'class' => 'Field50',
				)
			);

			return $fields;
		}

		public function GetManualPaymentJavascript()
		{
			return Interspire_Template::getInstance('admin')->render('Snippets/PaymentValidation_NMI.html');
		}

		public function ProcessManualPayment($order, $data)
		{
			$amount = $order['total_inc_tax'];
			$amount = DefaultPriceFormat($amount);
			if ($amount <= 0) {
				return array('amount' => 0, 'result' => false, 'message' => GetLang('ManualPaymentNoAmountSpecified'));
			}
			$order['total_inc_tax'] = $amount;
			$orderData = array(
				'orders' => array(
					$order['orderid'] => $order
				)
			);
			$this->SetOrderData($orderData);

			$result = $this->ProcessPaymentForm($data);
			$message = '';
			if ($this->HasErrors()) {
				foreach ($this->GetErrors() as $error) {
					$message .= $error . "<br />";
				}
			}
			return array('amount' => $amount, 'result' => $result, 'message' => $message);
		}
	}
