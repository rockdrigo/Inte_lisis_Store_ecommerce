<?php

	class CHECKOUT_FIRSTDATA extends ISC_CHECKOUT_PROVIDER
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

			$this->SetName('FirstData');
			//$this->SetImage("FirstData_logo.gif");
			$this->SetDescription(GetLang('FirstDataDesc'));
			//$this->SetHelpText(sprintf(GetLang('FirstDataHelp'), $GLOBALS['ShopPath']));
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

			$this->_variables['storeid'] = array(
				"name" => GetLang('FirstDataStoreId'),
				"type" => "textbox",
				"help" => GetLang('FirstDataStoreIdHelp'),
				"default" => $this->GetName(),
				"required" => true
			);

			$this->_variables['password'] = array(
				"name" => GetLang('FirstDataPassword'),
				"type" => "password",
				"help" => GetLang('FirstDataPasswordHelp'),
				"default" => $this->GetName(),
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
			$GLOBALS['FirstDataMonths'] = "";
			$GLOBALS['FirstDataYears'] = "";

			for($i = 1; $i <= 12; $i++) {
				$stamp = mktime(0, 0, 0, $i, 15, isc_date("Y"));

				$i = str_pad($i, 2, "0", STR_PAD_LEFT);

				if (@$_POST['FirstData_ccexpm'] == $i) {
					$sel = 'selected="selected"';
				} else {
					$sel = "";
				}

				$GLOBALS['FirstDataMonths'] .= sprintf("<option %s value='%s'>%s</option>", $sel, $i, isc_date("M", $stamp));
			}

			for($i = isc_date("Y"); $i < isc_date("Y")+10; $i++) {

				if (@$_POST['Firstdata_ccexpy'] == substr($i, 2, 2)) {
					$sel = 'selected="selected"';
				} else {
					$sel = "";
				}
				$GLOBALS['FirstDataYears'] .= sprintf("<option %s value='%s'>%s</option>", $sel, substr($i, 2, 2), $i);
			}

			if(isset($_POST['FirstData_cccode'])) {
				$GLOBALS['FirstDataCCV2'] = $_POST['FirstData_cccode'];
			}
			
			// Grab the billing details for the order
			$billingDetails = $this->GetBillingDetails();

			$GLOBALS['FirstDataName'] = isc_html_escape($billingDetails['ordbillfirstname'].' '.$billingDetails['ordbilllastname']);
			// Format the amount that's going to be going through the gateway
			$GLOBALS['OrderAmount'] = CurrencyConvertFormatPrice($this->GetGatewayAmount());

			// Was there an error validating the payment? If so, pre-fill the form fields with the already-submitted values
			if($this->HasErrors()) {
				$GLOBALS['FirstDataName'] = isc_html_escape($_POST['FirstData_name']);
				$GLOBALS['FirstDataNum'] = isc_html_escape($_POST['FirstData_ccno']);
				$GLOBALS['FirstDataErrorMessage'] = implode("<br />", $this->GetErrors());
			}
			else {
				// Hide the error message box
				$GLOBALS['HideFirstDataError'] = "none";
			}

			// Collect their details to send through to Authorize.NET
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("FirstData");
			return $GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate(true);
		}

		public function getAuthentication($userid, $passwd){
			$authkey = base64_encode ($userid.':'.$passwd);

			return $authkey;
		}

		/**
		 * Process the details entered on the payment form.
		 *
		 * @return boolean True if valid details and payment has been processed. False if not.
		 */
		public function ProcessPaymentForm($dataSource = array())
		{

			/*** INICIA ESTRUCTURA XML ***/
			
			$xmlComplete = '<fdggwsapi:FDGGWSApiOrderRequest xmlns:v1="http://secure.linkpt.net/fdggwsapi/schemas_us/v1" xmlns:fdggwsapi="http://secure.linkpt.net/fdggwsapi/schemas_us/fdggwsapi">
				<v1:Transaction>
					<v1:CreditCardTxType>
						<v1:Type>'.$this->GetValue('transactiontype').'</v1:Type>
					</v1:CreditCardTxType>
					<v1:CreditCardData>
						<v1:CardNumber>'.$_POST['FirstData_ccno'].'</v1:CardNumber>
						<v1:ExpMonth>'.$_POST['FirstData_ccexpm'].'</v1:ExpMonth>
						<v1:ExpYear>'.$_POST['FirstData_ccexpy'].'</v1:ExpYear>
						<v1:CardCodeValue>'.$_POST['FirstData_cccode'].'</v1:CardCodeValue>
					</v1:CreditCardData>
					<v1:Payment>
						<v1:ChargeTotal>'.$this->GetGatewayAmount().'</v1:ChargeTotal>
						<v1:SubTotal>'.$this->GetGatewayAmount('subtotal_ex_tax').'</v1:SubTotal>
						<v1:VATTax>'.$this->GetTaxCost().'</v1:VATTax>
						<v1:Shipping>'.$this->GetShippingCost().'</v1:Shipping>
					</v1:Payment>';
			
			foreach($this->GetOrders() as $orderid => $order){
				$xmlComplete .= '
					<v1:TransactionDetails>
						<v1:UserID>'.$order['ordcustid'].'</v1:UserID>
						<v1:OrderId>'.$order['orderid'].'</v1:OrderId>
						<v1:Ip>'.GetIP().'</v1:Ip>
						<v1:TransactionOrigin>RETAIL</v1:TransactionOrigin>
					</v1:TransactionDetails>';
			}
			
			$xmlComplete .= '
				</v1:Transaction>
			</fdggwsapi:FDGGWSApiOrderRequest>';
			
			
			/*** INICIA "SOAP REQUEST MESSAGE" ***/
			$request = '<?xml version="1.0" encoding="UTF-8"?>
			<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas/xmlsoap.org/soap/envelope/">
				<SOAP-ENV:Header/>
				<SOAP-ENV:Body>
					'.$xmlComplete.'
				</SOAP-ENV:Body>
			</SOAP-ENV:Envelope>';
			
			$url = 'ws.merchanttest.firstdataglobalgateway.com';
			$uri = 'fdggwsapi/services';
			$endpoint = 'https://'.$url.'/'.$uri;
			
			$authentication = $this->getAuthentication($this->getValue('storeid'), $this->getValue('password'));
			
			//'authentication'=> SOAP_AUTHENTICATION_DIGEST,
			
			$data = array (
			'conection_timeout'	=> 60,
			'location'		=> $endpoint,
			'style'			=> SOAP_RPC,
			'use'			=> SOAP_ENCODED,
			'uri'			=> $uri,
			'trace'			=> true,
			//'login'			=> 'soap',
			//'password'		=> $authkey,
			'authentication'	=> $authentication,
			//'local_cert'	=> file_get_contents('C:\OpenSSL-Win32\bin\keyandcert.pem'),
			);
			
			$result = $this->ConnectToProvider($endpoint, $url, $request);
			echo $result;exit;
		
			echo $result;exit;
			
			if (isset($an_response['response']) && $an_response['response'] == 1) {
			
				libxml_use_internal_errors(true);
				$xml_errors[] = array();
				try {
					$xml_dom = new SimpleXMLElement();
				}
				catch (Exception $e) {
					foreach(libxml_get_errors() as $error) {
						$xml_errors[] = $error->message;
						echo implode('<br/>', $error->message);
					}
				}
				
				if(!$xml_dom) {
					echo 'Se recibio un XML de resultado mal formado de una peticion '.get_class($this).'<br/>'.htmlentities($result);
					return false;
				}
						
				$paymentStatus = 'captured';
				
				$transactionidXML = $Resultado_xml->xpath('/FDGGWSApiOrderResponse/TransactionID');
				
				$transactionid = (string)$transactionidXML->children();
				
				// Save the authorization key
				$updatedOrder = array(
					'ordpayproviderid' => $transactionid,
					'ordpaymentstatus' => $paymentStatus,
				);

				$this->UpdateOrders($updatedOrder);

				$this->SetPaymentStatus(PAYMENT_STATUS_PAID);

				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), sprintf(GetLang('FirstDataSuccess'), $invoiceNum));
				return true;
			}
			else if (isset($an_response['response']) && $an_response['response'] == 2) {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang('FirstDataErrorDeclined'), $invoiceNum, $an_response['responsetext']) , $an_response['responsetext']);
					$this->SetError('Error tarjeta declinada');
					return false;
			}
			else if (isset($an_response['response']) && $an_response['response'] == 3) {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang('FirstDataErrorInvalid'), $invoiceNum, $an_response['responsetext']), '#'.$an_response['response_code'].' '.$an_response['responsetext']);
					$this->SetError('Error al autorizar: '.$an_response['responsetext']);
					return false;
			}
			else {
				// Invalid Authorize.net response
				$this->SetError(GetLang('FirstDataMissingFields'));
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
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
				curl_setopt($ch, CURLOPT_USERPWD, $this->getAuthentication($this->getValue('storeid'), $this->getValue('password')));
				curl_setopt($ch, CURLOPT_TIMEOUT, 60);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $an_data);
				curl_setopt($ch, CURLOPT_SSLCERT, "C:\OpenSSL-Win32\bin\mycerts\cacert.pem");
				curl_setopt($ch, CURLOPT_SSLKEY, "C:\OpenSSL-Win32\bin\mycerts\privkey.pem");
				curl_setopt($ch, CURLOPT_SSLCERTPASSWD, "test");
				curl_setopt($ch, CURLOPT_SSLKEYPASSWD, "test");
				
				curl_setopt($ch, CURLOPT_CAPATH, "C:\OpenSSL-Win32\bin\firstdatacerts");

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

				$result =  @curl_exec($ch);

				if(curl_error($ch) != '') {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang('FirstDataConnectErrorLogMsg'), $an_url, "(" . curl_errno($ch) . ") " . curl_error($ch)));
					$this->SetError(GetLang('FirstDataNotSupported'));
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
					$this->SetError(sprintf(GetLang('FirstDataFSocketError'), $an_pp_url));
					return false;
				}
			}
			else {
				$this->SetError(GetLang('FirstDataNotSupported'));
				return false;
			}

			// Check to see the we got a response
			if ($result == "") {
				$this->SetError(sprintf(GetLang('FirstDataNoResult'), $an_pp_url));
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('FirstDataNoResultLogSubject'), sprintf(GetLang('FirstDataNoResultLogMsg'), $an_url));
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
				'FirstData_name' => array(
					'type' => 'text',
					'title' => GetLang('CCManualCardHoldersName'),
					'value' => '',
					'required' => true
				),
				'FirstData_ccno' => array(
					'type' => 'text',
					'title' => GetLang('CCManualCreditCardNo'),
					'value' => '',
					'required' => true
				),
				'FirstData_ccexp' => array(
					'type' => 'html',
					'title' => GetLang('CCManualExpirationDate'),
					'html' => '
						<select name="paymentField[' . $this->GetId() . '][FirstData_ccexpm]">'.$monthOptions.'</select>
						&nbsp;
						<select name="paymentField[' . $this->GetId() . '][FirstData_ccexpy]">'.$yearOptions.'</select>
					',
					'required' => true
				),
				'FirstData_cccode' => array(
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
			return Interspire_Template::getInstance('admin')->render('Snippets/PaymentValidation_FirstData.html');
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
