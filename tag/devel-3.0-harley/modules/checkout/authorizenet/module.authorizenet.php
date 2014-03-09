<?php

	class CHECKOUT_AUTHORIZENET extends ISC_CHECKOUT_PROVIDER
	{
		/**
		 * @var boolean True if this checkout module requires SSL or not.
		 */
		protected $requiresSSL = true;

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

			$this->SetName(GetLang('AuthorizeNetName'));
			$this->SetImage("authorizenet_logo.gif");
			$this->SetDescription(GetLang('AuthorizeNetDesc'));
			$this->SetHelpText(sprintf(GetLang('AuthorizeNetHelp'), $GLOBALS['ShopPath']));
		}

		/**
		 * Check to make sure all the critical dependencies are available
		 *
		 * @return boolean
		 **/
		public function IsSupported()
		{
			if (function_exists('curl_exec') && defined('CURL_VERSION_SSL')) {
				return true;
			}
			else if (in_array('ssl', stream_get_transports())) {
				return true;
			}
			else {
				$this->SetError(GetLang('AuthorizeNetSSLNotAvailable'));
				return false;
			}
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

			$this->_variables['merchantid'] = array(
				"name" => GetLang('AuthorizeNetApiLoginId'),
				"type" => "textbox",
				"help" => GetLang('AuthorizeNetMerchantIDHelp'),
				"default" => "",
				"required" => true
			);

			$this->_variables['transactionkey'] = array(
				"name" => GetLang('AuthorizeNetTransationKey'),
				"type" => "password",
				"help" => GetLang('AuthorizeNetTransactionKeyHelp'),
				"default" => "",
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
					GetLang('TransactionTypeSale') => "AUTH_CAPTURE",
					GetLang('TransactionTypeAuthorize') => "AUTH_ONLY"
				),
				"multiselect" => false
			);

			$this->_variables['testmode'] = array(
				"name" => GetLang('AuthorizeNetTestMode'),
				"type" => "dropdown",
				"help" => GetLang('AuthorizeNetTestModeHelp'),
				"default" => "no",
				"required" => true,
				"options" => array(
					GetLang('AuthorizeNetTestModeNo') => "NO",
					GetLang('AuthorizeNetTestModeYes') => "YES"
				),
				"multiselect" => false
			);

			$this->_variables['requirecvv2'] = array(
				"name" => GetLang('AuthorizeNetRequireCardCode'),
				"type" => "dropdown",
				"help" => GetLang('AuthorizeNetCardCodeHelp'),
				"default" => "no",
				"required" => true,
				"options" => array(
					GetLang('AuthorizeNetCardCodeNo') => "NO",
					GetLang('AuthorizeNetCardCodeYes') => "YES"
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
			// Authorize.net needs HTTPS, so if it's not on then stop
			if(!strtolower($_SERVER['HTTPS']) == "on") {
				ob_end_clean();
				?>
					<script type="text/javascript">
						alert("<?php echo GetLang('AuthorizeNetNoSSLError'); ?>");
						document.location.href="<?php echo $GLOBALS['ShopPath']; ?>/checkout.php?action=confirm_order";
					</script>
				<?php
				die();
			}

			$GLOBALS['AuthorizeNetMonths'] = "";
			$GLOBALS['AuthorizeNetYears'] = "";

			for($i = 1; $i <= 12; $i++) {
				$stamp = mktime(0, 0, 0, $i, 15, isc_date("Y"));

				$i = str_pad($i, 2, "0", STR_PAD_LEFT);

				if (@$_POST['AuthorizeNet_ccexpm'] == $i) {
					$sel = 'selected="selected"';
				} else {
					$sel = "";
				}

				$GLOBALS['AuthorizeNetMonths'] .= sprintf("<option %s value='%s'>%s</option>", $sel, $i, isc_date("M", $stamp));
			}

			for($i = isc_date("Y"); $i < isc_date("Y")+10; $i++) {

				if (@$_POST['AuthorizeNet_ccexpy'] == substr($i, 2, 2)) {
					$sel = 'selected="selected"';
				} else {
					$sel = "";
				}
				$GLOBALS['AuthorizeNetYears'] .= sprintf("<option %s value='%s'>%s</option>", $sel, substr($i, 2, 2), $i);
			}

			$require_cvv2 = $this->GetValue("requirecvv2");
			if($require_cvv2 == "YES") {
				if(isset($_POST['AuthorizeNet_cccode'])) {
					$GLOBALS['AuthorizeNetCCV2'] = $_POST['AuthorizeNet_cccode'];
				}
				$GLOBALS['AuthorizeNetHideCVV2'] = '';
			}
			else {
				$GLOBALS['AuthorizeNetHideCVV2'] = 'none';
			}

			// Grab the billing details for the order
			$billingDetails = $this->GetBillingDetails();

			$GLOBALS['AuthorizeNetName'] = isc_html_escape($billingDetails['ordbillfirstname'].' '.$billingDetails['ordbilllastname']);
			// Format the amount that's going to be going through the gateway
			$GLOBALS['OrderAmount'] = CurrencyConvertFormatPrice($this->GetGatewayAmount());

			// Was there an error validating the payment? If so, pre-fill the form fields with the already-submitted values
			if($this->HasErrors()) {
				$GLOBALS['AuthorizeNetName'] = isc_html_escape($_POST['AuthorizeNet_name']);
				$GLOBALS['AuthorizeNetNum'] = isc_html_escape($_POST['AuthorizeNet_ccno']);
				$GLOBALS['AuthorizeNetErrorMessage'] = implode("<br />", $this->GetErrors());
			}
			else {
				// Hide the error message box
				$GLOBALS['HideAuthorizeNetError'] = "none";
			}

			// Collect their details to send through to Authorize.NET
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("authorizenet");
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
			$an_data = "";
			$an_uri = "/gateway/transact.dll";
			$error = false;

			$requiredFields = array(
				"AuthorizeNet_name",
				"AuthorizeNet_ccno",
				"AuthorizeNet_ccexpm",
				"AuthorizeNet_ccexpy"
			);

			$require_cvv2 = $this->GetValue("requirecvv2");
			if($require_cvv2 == "YES") {
				$requiredFields[] = "AuthorizeNet_cccode";
			}

			$missingFields = false;
			foreach($requiredFields as $field) {
				if(!isset($dataSource[$field]) || !$dataSource[$field]) {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError('php', 'missing field: ' . $field, '');
					$missingFields = true;
				}
			}

			if(isset($_COOKIE['SHOP_ORDER_TOKEN']) && $missingFields == false) {
				$ccname = $dataSource['AuthorizeNet_name'];
				$ccnum = $dataSource['AuthorizeNet_ccno'];
				$ccexpm = $dataSource['AuthorizeNet_ccexpm'];
				$ccexpy = $dataSource['AuthorizeNet_ccexpy'];
				$ccexp = sprintf("%s%s", $ccexpm, $ccexpy);

				if($require_cvv2 == "YES") {
					$cccode = $dataSource['AuthorizeNet_cccode'];
				}

				// Load the Authorize.net merchant ID
				$merchant_id = $this->GetValue("merchantid");

				// Load the tranaction key
				$transaction_key = $this->GetValue("transactionkey");

				// Is Authorize.net setup in test or live mode?
				$test_mode = $this->GetValue("testmode");

				// Load the Authorize.net transaction Type
				$transactionType = $this->GetValue('transactiontype');

				if($test_mode == "YES") {
					$an_url = "https://test.authorize.net/gateway/transact.dll";
					$an_pp_url = "test.authorize.net";
				}
				else {
					$an_url = "https://secure.authorize.net/gateway/transact.dll";
					$an_pp_url = "secure.authorize.net";
				}

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
					"x_login"				=> $merchant_id,
					"x_version"				=> "3.1",
					"x_delim_char"			=> "|",
					"x_delim_data"			=> "true",
					"x_url"					=> "false",
					"x_duplicate_window"	=> "0",
					"x_type"				=> $transactionType,
					"x_method"				=> "CC",
					"x_tran_key"			=> $transaction_key,
					"x_relay_response"		=> "false",
					"x_card_num"			=> $ccnum,
					"x_exp_date"			=> $ccexp,
					'x_invoice_num'			=> $invoiceNum,
					"x_description"			=> $order_desc,
					"x_amount"				=> $this->GetGatewayAmount(),
					"x_phone"				=> $addressDetails['ordbillphone'],
					"x_first_name"			=> $addressDetails['ordbillfirstname'],
					"x_last_name"			=> $addressDetails['ordbilllastname'],
					"x_address"				=> trim($addressDetails['ordbillstreet1'] . " " . $addressDetails['ordbillstreet2']),
					"x_email"				=> $addressDetails['ordbillemail'],
					"x_city"				=> $addressDetails['ordbillsuburb'],
					"x_state"				=> $addressDetails['ordbillstate'],
					"x_zip"					=> $addressDetails['ordbillzip'],
					"x_country"				=> $addressDetails['ordbillcountry'],
					"x_company"				=> $addressDetails['ordbillcompany'],
					"x_customer_ip"			=> GetIP(),

					//shipping info
					"x_ship_to_first_name"	=> $shippingAddress['first_name'],
					"x_ship_to_last_name"	=> $shippingAddress['last_name'],
					"x_ship_to_address"		=> trim($shippingAddress['address_1'] . " " . $shippingAddress['address_2']),
					"x_ship_to_city"		=> $shippingAddress['city'],
					"x_ship_to_state"		=> $shippingAddress['state'],
					"x_ship_to_zip"			=> $shippingAddress['zip'],
					"x_ship_to_country"		=> $shippingAddress['country'],
					"x_ship_to_company"		=> $shippingAddress['company'],
					"x_ship_to_phone"		=> $shippingAddress['phone'],

					"shop_order_token"		=> $_COOKIE['SHOP_ORDER_TOKEN']
				);

				$require_cvv2 = $this->GetValue("requirecvv2");
				if($require_cvv2 == "YES") {
					$an_values['x_card_code'] = $cccode;
				}

				// Merge the name/value pairs into a string
				foreach($an_values as $k=>$v) {
					$an_data .= sprintf("%s=%s&", $k, urlencode($v));
				}

				$an_data = rtrim($an_data, '&');

				$an_response = $this->ConnectToProvider($an_url, $an_pp_url, $an_data);
				if(!$an_response || empty($an_response)) {
					return false;
				}

				// Ref:
				// http://developer.authorize.net/guides/AIM/Transaction_Response/Response_Code_Details.htm
				// http://developer.authorize.net/guides/AIM/Transaction_Response/Response_Reason_Codes_and_Response_Reason_Text.htm
				$successfulResponses = array(
					// Success
					1 => array(
						1, // This transaction has been approved.
					),
					// Held for Review
					4 => array(
						193, // The transaction is currently under review.
						252, // The transaction was accepted, but is being held for merchant review.
						253, // The transaction was accepted and was authorized, but is being held for merchant review.
					),
				);

				if (isset($successfulResponses[$an_response[0]]) && in_array($an_response[2], $successfulResponses[$an_response[0]])) {
					$extraInfo = '';
					$paymentStatus = '';

					if($transactionType == 'AUTH_ONLY') {
						$paymentStatus = 'authorized';
					} else if ($transactionType == 'AUTH_CAPTURE') {
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
						'ordpayproviderid' => $an_response[6],
						'ordpaymentstatus' => $paymentStatus,
						'extrainfo' => $extraInfo
					);

					$this->UpdateOrders($updatedOrder);

					$this->SetPaymentStatus(PAYMENT_STATUS_PAID);

					$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), sprintf(GetLang('AuthorizeNetSuccess'), $invoiceNum));
					return true;
				}
				else {
					// Status was declined or error, show the response message as an error
					if($an_response[2] == 11) {
						$duplicateMessage = sprintf(GetLang('AuthorizeNetErrorDuplicate'), GetConfig('AdminEmail'));
						$this->SetError($duplicateMessage);
					}
					else {
						$this->SetError($an_response[3]);
					}

					if($an_response[0] == 2) {
						$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang('AuthorizeNetErrorDeclined'), $invoiceNum, $an_response[3]) , $an_response[3]);
					}
					else {
						$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang('AuthorizeNetErrorInvalid'), $invoiceNum, $an_response[3]), $an_response[3]);
					}
					return false;
				}
			}
			else {
				// Invalid Authorize.net response
				$this->SetError(GetLang('AuthorizeNetMissingFields'));
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
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang('AuthorizeNetConnectErrorLogMsg'), $an_url, "(" . curl_errno($ch) . ") " . curl_error($ch)));
					$this->SetError(GetLang('AuthorizeNetNotSupported'));
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
					$this->SetError(sprintf(GetLang('AuthorizeNetFSocketError'), $an_pp_url));
					return false;
				}
			}
			else {
				$this->SetError(GetLang('AuthorizeNetNotSupported'));
				return false;
			}

			// Check to see the we got a response
			if ($result == "") {
				$this->SetError(sprintf(GetLang('AuthorizeNetNoResult'), $an_pp_url));
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('AuthorizeNetNoResultLogSubject'), sprintf(GetLang('AuthorizeNetNoResultLogMsg'), $an_url));
				return false;
			}

			$an_response = explode("|", $result);
			for ($i=0; $i<count($an_response); $i++) {
				$an_response[$i] = trim($an_response[$i], '"');
			}
			return $an_response;
		}

		private function GetResponseFromProvider($transactionType, $extraFields=array())
		{
				// Load the Authorize.net merchant ID
			$merchant_id = $this->GetValue("merchantid");

			// Load the tranaction key
			$transaction_key = $this->GetValue("transactionkey");

			// Is Authorize.net setup in test or live mode?
			$test_mode = $this->GetValue("testmode");

			if($test_mode == "YES") {
				$an_url = "https://test.authorize.net/gateway/transact.dll";
				$an_pp_url = "test.authorize.net";
			}
			else {
				$an_url = "https://secure.authorize.net/gateway/transact.dll";
				$an_pp_url = "secure.authorize.net";
			}

			// Arrange the data into name/value pairs ready to send
			$an_values = array (
				"x_login"				=> $merchant_id,
				"x_version"				=> "3.1",
				"x_delim_char"			=> "|",
				"x_delim_data"			=> "true",
				"x_type"				=> $transactionType,
				"x_method"				=> "CC",
				"x_tran_key"			=> $transaction_key,
				"x_relay_response"		=> "false"
			);

			$an_values = array_merge($an_values, $extraFields);

			$an_data = '';
			// Merge the name/value pairs into a string
			foreach($an_values as $k=>$v) {
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
				"x_trans_id" => $transactionId,
			);

			$an_response = $this->GetResponseFromProvider("PRIOR_AUTH_CAPTURE", $extraFields);
			if(!$an_response || empty($an_response)) {
				$message = GetLang('ErrorConnectToProvider');
				return false;
			}
			// Based on specific items in the array we can determine if the transaction was successful or not
			if($an_response[0] == 1) { // 1 is a success, 2 is declined and 3 is an error

				//set the message that's displayed in the front end
				$message = GetLang('DelayedCaptureSuccess');


				// Save the authorization key
				$updatedOrder = array(
					'ordpaymentstatus' => 'captured'
				);

				//update the orders table with new transaction details
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$orderId."'");


				//log the transaction in store logs
				$delayedCaptureSuccess = sprintf(GetLang('DelayedCaptureSuccessLog'), $orderId, $an_response[4]);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $delayedCaptureSuccess, $an_response[3]);

				return true;
			}
			else {
				//set the message that's displayed in the front end
				$message = sprintf(GetLang('DelayedCaptureFailed'), $an_response[3], $an_response[4]);

				//log the transaction in store logs
				$delayedCaptureError = sprintf(GetLang('DelayedCaptureError'), $orderId, $an_response[4]);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $delayedCaptureError, $an_response[3]);
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

			$extraInfo = @unserialize($order['extrainfo']);
			if(!isset($extraInfo['cc_ccno'])) {
				$message = GetLang('RefundMissingInfo');
				return false;
			}

			$ccnum = $extraInfo['cc_ccno'];

			$extraFields = array(
				'x_amount' => $amt,
				"x_trans_id" => $transactionId,
				'x_card_num' => $extraInfo['cc_ccno'],
				'x_invoice_num' => $orderId,
			);

			$an_response = $this->GetResponseFromProvider('CREDIT', $extraFields);
			if(!$an_response || empty($an_response)) {
				$message = GetLang('ErrorConnectToProvider');
				return false;
			}

			if($an_response[0] == 1) {

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
				if(isset($an_response[3])) {
					$responseMsg = $an_response[3];
				}
				if(isset($an_response[6])) {
					$transid = isc_html_escape($an_response[6]);
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
				"x_trans_id" => $transactionId
			);

			$an_response = $this->GetResponseFromProvider('VOID', $extraFields);
			if(!$an_response || empty($an_response)) {
				$message = GetLang('ErrorConnectToProvider');
				return false;
			}

			if($an_response[0] == 1) {

				$message = GetLang('VoidSuccess');

				$updatedOrder = array(
					'ordpaymentstatus'	=> 'void',
					'ordpayproviderid'	=> $an_response[6],
				);
				//update the orders table with new transaction details
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$orderId."'");

				$voidSuccess = sprintf(GetLang('VoidSuccessLog'), $orderId, $an_response[6]);
				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment',  $this->GetName()), $voidSuccess);
				return true;

			} else {
				$responseMsg = '';
				$transid = '';
				if(isset($an_response[3])) {
					$responseMsg = $an_response[3];
				}
				if(isset($an_response[6])) {
					$transid = isc_html_escape($an_response[6]);
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
				'AuthorizeNet_name' => array(
					'type' => 'text',
					'title' => GetLang('CCManualCardHoldersName'),
					'value' => '',
					'required' => true
				),
				'AuthorizeNet_ccno' => array(
					'type' => 'text',
					'title' => GetLang('CCManualCreditCardNo'),
					'value' => '',
					'required' => true
				),
				'AuthorizeNet_ccexp' => array(
					'type' => 'html',
					'title' => GetLang('CCManualExpirationDate'),
					'html' => '
						<select name="paymentField[' . $this->GetId() . '][AuthorizeNet_ccexpm]">'.$monthOptions.'</select>
						&nbsp;
						<select name="paymentField[' . $this->GetId() . '][AuthorizeNet_ccexpy]">'.$yearOptions.'</select>
					',
					'required' => true
				)
			);

			if ($this->GetValue("requirecvv2") == 'YES') {
				$cvvfield = array(
					'AuthorizeNet_cccode' => array(
						'type' => 'text',
						'title' => GetLang('CCManualCreditCardCCV2'),
						'value' => '',
						'required' => true,
						'class' => 'Field50',
					)
				);

				$keys = array_keys($fields);
				array_splice($keys, 2, 0, array_keys($cvvfield));
				array_splice($fields, 2, 0, $cvvfield);
				$fields = array_combine($keys, $fields);
			}

			return $fields;
		}

		public function GetManualPaymentJavascript()
		{
			return Interspire_Template::getInstance('admin')->render('Snippets/PaymentValidation_authorizenet.html');
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
