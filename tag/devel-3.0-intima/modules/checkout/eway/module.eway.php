<?php

	class CHECKOUT_EWAY extends ISC_CHECKOUT_PROVIDER
	{
		/**
		 * @var boolean Does this provider require SSL to be enabled?
		 */
		public $requiresSSL = true;

		/**
		 * @var boolean Does this provider support orders from more than one vendor?
		 */
		protected $supportsVendorPurchases = true;

		/**
		 * @var boolean Does this provider support shipping to multiple addresses?
		 */
		protected $supportsMultiShipping = true;

		/**
		 * The constructor.
		 */
		public function __construct()
		{
			// Setup the required variables for the eWay checkout module
			parent::__construct();
			$this->SetName(GetLang('EWayName'));
			$this->SetImage('eway_logo.gif');
			$this->SetDescription(GetLang('EWayDesc'));
			$this->SetHelpText(sprintf(GetLang('EWayHelp'), $GLOBALS['ShopPath']));
		}

		/**
		 * Set the configuration options for this provider.
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

			$this->_variables['customerid'] = array(
				"name" => GetLang('EWayCustomerId'),
			   "type" => "textbox",
			   "help" => GetLang('EWayCustomerIdHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['testmode'] = array(
				"name" => GetLang('TestMode'),
			   "type" => "dropdown",
			   "help" => GetLang('EWayTestModeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(
				GetLang('EWayTestModeNo') => "NO",
							  GetLang('EWayTestModeYes') => "YES"
				),
				"multiselect" => false
			);

			$this->_variables['requirecvn'] = array(
				"name" => GetLang('EWayRequireCardCode'),
				"type" => "dropdown",
				"help" => GetLang('EWayRequireCardCodeHelp'),
				"default" => "no",
				"required" => true,
				"options" => array(
					GetLang('EWayRequireCardCodeNo') => "NO",
					GetLang('EWayRequireCardCodeYes') => "YES"
				),
				"multiselect" => false
			);
		}

		/**
		 * Check if this checkout module can be enabled or not.
		 *
		 * @return boolean True if this module is supported on this install, false if not.
		 */
		public function IsSupported()
		{
			if(!GetConfig('UseSSL')) {
				$this->SetError(GetLang('EWayNoSSLError'));
			}

			if($this->HasErrors()) {
				return false;
			}
			else {
				return true;
			}
		}

		/**
		 * Generate the payment form to allow users to make a payment via this provider.
		 *
		 * @return string The generated payment form.
		 */
		public function ShowPaymentForm()
		{
			$GLOBALS['EWayMonths'] = "";
			$GLOBALS['EWayYears'] = "";

			for($i = 1; $i <= 12; $i++) {
				$stamp = mktime(0, 0, 0, $i, 15, isc_date("Y"));

				$i = str_pad($i, 2, "0", STR_PAD_LEFT);

				if(@$_POST['eway_ccexpm'] == $i) {
					$sel = 'selected="selected"';
				} else {
					$sel = "";
				}

				$GLOBALS['EWayMonths'] .= sprintf("<option %s value='%s'>%s</option>", $sel, $i, isc_date("M", $stamp));
			}

			for($i = isc_date("Y"); $i < isc_date("Y")+10; $i++) {

				if(@$_POST['eway_ccexpy'] == isc_substr($i, 2, 2)) {
					$sel = 'selected="selected"';
				} else {
					$sel = "";
				}

				$GLOBALS['EWayYears'] .= sprintf("<option %s value='%s'>%s</option>", $sel, isc_substr($i, 2, 2), $i);
			}

			$require_cvv2 = $this->GetValue("requirecvn");
			if($require_cvv2 == "YES") {
				if(isset($_POST['eway_cvn'])) {
					$GLOBALS['EWayCardCode'] = $_POST['eway_cvn'];
				}
				$GLOBALS['EWayHideCardCode'] = '';
			}
			else {
				$GLOBALS['EWayHideCardCode'] = 'none';
			}

			// Grab the billing details
			$billingDetails = $this->GetBillingDetails();

			$GLOBALS['EWayName'] = isc_html_escape($billingDetails['ordbillfirstname'].' '.$billingDetails['ordbilllastname']);

			// Format the amount that's going to be going through the gateway
			$gatewayAmount = $this->GetGatewayAmount();
			$GLOBALS['OrderAmount'] = CurrencyConvertFormatPrice($gatewayAmount);

			// Was there an error validating the payment? If so, pre-fill the form fields with the already-submitted values
			if($this->HasErrors()) {
				$GLOBALS['EWayName'] = isc_html_escape($_POST['eway_name']);
				$GLOBALS['EWayNum'] = isc_html_escape($_POST['eway_ccno']);

				$eway_error = implode("<br />", $this->GetErrors());
				$eway_error = str_replace("Error: ", "", $eway_error);
				$eway_error = str_replace(" You have not been billed for this transaction.", "", $eway_error);
				$GLOBALS['EWayErrorMessage'] = $eway_error;
			}
			else {
				// Hide the error message box
				$GLOBALS['HideEWayError'] = "none";
			}

			// Collect their details to send through to eWay
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("eway");
			return $GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate(true);
		}

		/**
		 * Process and validate input from a payment form for this provider.
		 *
		 * @return boolean True if valid details and payment has been processed. False if not.
		 */
		public function ProcessPaymentForm($dataSource = array())
		{
			if (empty($dataSource)) {
				$dataSource = $_POST;
			}

			$result = "";
			$error = false;

			$orders = $this->GetOrders();
			list(,$order) = each($orders);

			if(isset($dataSource['eway_name']) && isset($dataSource['eway_ccno']) && isset($dataSource['eway_ccexpm']) && isset($dataSource['eway_ccexpy'])) {
				$ccname = $dataSource['eway_name'];
				$ccnum = $dataSource['eway_ccno'];
				$ccexpm = $dataSource['eway_ccexpm'];
				$ccexpy = $dataSource['eway_ccexpy'];

				if ($this->GetValue('requirecvn') == 'YES' && isset($dataSource['eway_cvn'])) {
					$cvn = $dataSource['eway_cvn'];
				} else {
					$cvn = '';
				}

				$billingDetails = $this->GetBillingDetails();

				// Load the pending order
				$total = number_format($this->GetGatewayAmount(), 2, '.', '');

				// Multiply the total by 100 because it's in cents
				$total *= 100;

				// Load the eWay customer ID
				$eway_id = $this->GetValue("customerid");

				// Is eWay setup in test or live mode?
				if($this->GetValue('testmode') == 'YES') {
					if($this->GetValue('requirecvn') == 'YES') {
						$eWayUrl = 'https://www.eway.com.au/gateway_cvn/xmltest/testpage.asp';
					}
					else {
						$eWayUrl = 'https://www.eway.com.au/gateway/xmltest/testpage.asp';
					}

					// If we're in test mode them "hard code" the eWay customer ID and total to one that works in test mode
					$eway_id = "87654321";
					$ccnum = "4444333322221111";
				}
				else {
					if($this->GetValue('requirecvn') == 'YES') {
						$eWayUrl = 'https://www.eway.com.au/gateway_cvn/xmlpayment.asp';
					}
					else {
						$eWayUrl = 'https://www.eway.com.au/gateway/xmlpayment.asp';
					}
				}

				$order_desc = sprintf(GetLang('YourOrderFrom'), $GLOBALS['StoreName']);

				// Build the XML for the shipping quote
				$xml = new SimpleXMLElement("<ewaygateway/>");
				$xml->addChild('ewayCustomerID', $eway_id);
				$xml->addChild('ewayTotalAmount', $total);
				$xml->addChild('ewayCustomerFirstName', $billingDetails['ordbillfirstname']);
				$xml->addChild('ewayCustomerLastName', $billingDetails['ordbilllastname']);
				$xml->addChild('ewayCustomerEmail', $billingDetails['ordbillemail']);
				$xml->addChild('ewayCustomerAddress', trim($billingDetails['ordbillstreet1'] . " " . $billingDetails['ordbillstreet2']));
				$xml->addChild('ewayCustomerPostcode', $billingDetails['ordbillzip']);
				$xml->addChild('ewayCustomerInvoiceDescription', $order_desc);
				$xml->addChild('ewayCustomerInvoiceRef', $_COOKIE['SHOP_ORDER_TOKEN']);
				$xml->addChild('ewayCardHoldersName', $ccname);
				$xml->addChild('ewayCardNumber', $ccnum);
				$xml->addChild('ewayCardExpiryMonth', $ccexpm);
				$xml->addChild('ewayCardExpiryYear', $ccexpy);

				if ($this->GetValue('requirecvn') == 'YES') {
					$xml->addChild('ewayCVN', $cvn);
				}
				else {
					$xml->addChild('ewayCVN', '');
				}
				$xml->addChild('ewayTrxnNumber', $order['orderid']);
				$xml->addChild('ewayOption1', '');
				$xml->addChild('ewayOption2', '');
				$xml->addChild('ewayOption3', '');
				$ewayXML = $xml->asXML();

				$result = PostToRemoteFileAndGetResponse($eWayUrl, $ewayXML);

				if($result === false || $result == null) {
					$this->SetError("An error occured while trying to contact eWay.");
					return false;
				}

				// We received a response from eWay, let's see what it was
				try {
				$xml = new SimpleXMLElement($result);
				} catch (Exception $e) {
					$this->SetError("An error occured with the response from eWay.");
					return false;
				}


				$order_total = (string)$total;
				$eway_amount = (string)$xml->ewayReturnAmount;

				if((string)$xml->ewayTrxnStatus == "True") {
					// The transaction was successful

					$this->SetPaymentStatus(PAYMENT_STATUS_PAID);

					$ewayTransactionId = (string)$xml->ewayTrxnNumber;
					$updatedOrder = array(
						'ordpayproviderid'	=> $ewayTransactionId,
					);
					$this->UpdateOrders($updatedOrder);

					$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang('EWayLogSuccess'));

					return true;
				}
				else {
					// Something went wrong, show the error message with the credit card form
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('EWayLogErrorGeneral'), sprintf(GetLang('EWayLogErrorGeneralDesc'), $order['orderid'], (string)$xml->ewayTrxnError));
					$this->SetError(GetLang('EWayProcessingError'));
					return false;
				}
			}
			else {
				// Bad form details, try again
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('EWayLogErrorGeneral'), sprintf(GetLang('EWayLogErrorGeneralDesc'), $order['orderid'], (string)$xml->ewayTrxnError));
				$this->SetError(GetLang('EWayProcessingError'));
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
				'eway_name' => array(
					'type' => 'text',
					'title' => GetLang('CCManualCardHoldersName'),
					'value' => '',
					'required' => true
				),
				'eway_ccno' => array(
					'type' => 'text',
					'title' => GetLang('CCManualCreditCardNo'),
					'value' => '',
					'required' => true
				),
				'eway_ccexp' => array(
					'type' => 'html',
					'title' => GetLang('CCManualExpirationDate'),
					'html' => '
						<select name="paymentField[' . $this->GetId() . '][eway_ccexpm]">'.$monthOptions.'</select>
						&nbsp;
						<select name="paymentField[' . $this->GetId() . '][eway_ccexpy]">'.$yearOptions.'</select>
					',
					'required' => true
				)
			);

			if ($this->GetValue("requirecvn") == 'YES') {
				$cvvfield = array(
					'eway_cvn' => array(
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
			return Interspire_Template::getInstance('admin')->render('Snippets/PaymentValidation_eway.html');
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