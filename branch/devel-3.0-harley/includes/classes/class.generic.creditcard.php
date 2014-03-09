<?php

	class ISC_GENERIC_CREDITCARD extends ISC_CHECKOUT_PROVIDER
	{
		protected $_card_types = array(
			'VISA' => array(
				'type' => 'Visa',
				'regexp' => '^4[0-9]{15,18}$',
				'requiresCVV2' => true
			),
			'AMEX' => array(
				'type' => 'American Express',
				'regexp' => '^(34|37)[0-9]{13}$',
				'requiresCVV2' => true
			),
			'MC' => array(
				'type' => 'Mastercard',
				'regexp' => '^5[1-5]{1}[0-9]{14}$',
				'requiresCVV2' => true
			),
			'DINERS' => array(
				'type' => 'Diners Club',
				'regexp' => '^(30|36|38|55)[0-9]{12}([0-9]{2})?$',
				'requiresCVV2' => false
			),
			'DISCOVER' => array(
				'type' => 'Discover',
				'regexp' => '^6011[0-9]{12}$',
				'requiresCVV2' => true
			),
			'SOLO' => array(
				'type' => 'Solo',
				'regexp' => '^6767[0-9]{12}([0-9]{2,3})?$',
				'requiresCVV2' => true,
				'hasIssueNo' => true,
				'hasIssueDate' => true,
				'requiresIssueNoOrDate' => true
			),
			'MAESTRO' => array(
				'type' => 'Maestro',
				'regexp' => '^(50[0-9]{4}|5[6-8][0-9]{4}|6[0-9]{5})[0-9]{6,13}$',
				'requiresCVV2' => true,
				'hasIssueNo' => true,
				'hasIssueDate' => true,
				'requiresIssueNoOrDate' => true
			),
			'SWITCH' => array(
				'type' => 'Switch/Maestro UK',
				'regexp' => '^6759[0-9]{12}([0-9]{2,3})?$',
				'requiresCVV2' => true,
				'hasIssueNo' => true,
				'hasIssueDate' => true,
				'requiresIssueNoOrDate' => true

			),
			'LASER' => array(
				'type' => 'Laser',
				'regexp' => '^(6304|6706|6771|6709)[0-9]{12,15}?$'
			),
			'JCB' => array(
				'type' => 'JCB',
				'regexp' => '^35(2[8-9]|[3-8][0-9])[0-9]{12}$',
				'requiresCVV2' => true
			),
			'DELTA' => array(
				'type' => 'Visa Debit/Delta',
				'regexp' => '^4[0-9]{15}$',
				'requiresCVV2' => true
			)
		);

		/*
			Does this payment provider require SSL?
		*/
		protected $requiresSSL = true;

		/*
			The Language Prefix for the module
		 */
		protected $_languagePrefix;

		protected $_currenciesSupported = array ();
		protected $_avsCheck;
		protected $_cardsSupported = array ('VISA','AMEX','MC');
		protected $_liveTransactionURL = '';
		protected $_testTransactionURL = '';
		protected $_liveTransactionURI = '';
		protected $_testTransactionURI = '';
		protected $_curlSupported = false;
		protected $_fsocksSupported = false;
		protected $requiresSoap = false;
		protected $soapAction = '';
		protected $_redirect = false;
		protected $_requiresCurl = false;
		protected $requireHeaders = false;
		protected $shoppathssl = false;
		protected $cardCodeRequired = false;
		protected $sslCertificatePath = '';
		protected $port = 0;
		protected $sslVersion = '';

		public function __construct()
		{
			// Setup the required variables for the checkout module
			parent::__construct();

			$this->_name = GetLang($this->_languagePrefix.'Name');			// Name of the Checkout Module
			$this->_description = GetLang($this->_languagePrefix.'Desc');	// Description

			if ($this->shoppathssl) {
				$this->_help = sprintf(GetLang($this->_languagePrefix.'Help'), $GLOBALS['ShopPathSSL'], $GLOBALS['ShopPathSSL']);	// Help Message
			}
			else {
				$this->_help = sprintf(GetLang($this->_languagePrefix.'Help'), $GLOBALS['ShopPath'], $GLOBALS['ShopPath']);		// Help Message
			}

			$this->_height = 0;
		}

		/**
		* Check if this checkout module is a redirect-type module.
		*
		* @return bool
		*/
		public function IsRedirect()
		{
			return $this->_redirect;
		}

		/*
		 * Check if this checkout module can be enabled or not.
		 *
		 * @return boolean True if this module is supported on this install, false if not.
		 */
		public function IsSupported()
		{
			$currency = GetDefaultCurrency();

			// Check if the default currency is supported by the payment gateway
			if (!in_array($currency['currencycode'], $this->_currenciesSupported)) {

				$currencies = '';

				if (count($this->_currenciesSupported) == 1) {
					$currencies = implode(',',$this->_currenciesSupported);
				} else {

					foreach ($this->_currenciesSupported as $currency) {

						if ($currency == $this->_currenciesSupported[count($this->_currenciesSupported)-1]) {
							$currencies .= ' and ' . $currency;
						} else {
							$currencies .= $currency . ', ';
						}

					}
				}

				$this->SetError(sprintf(GetLang($this->_languagePrefix.'CurrecyNotSupported'),$currencies));
			}

			// Check if SSL is required and exists
			if ($this->RequiresSSL()) {
				if(!GetConfig('UseSSL')) {
					$this->SetError(GetLang($this->_languagePrefix.'NoSSLError'));
				}
			}

			if($this->_requiresCurl && !function_exists("curl_exec")) {
				$this->SetError(GetLang('CreditCardCurlRequired'));
			}

			if($this->HasErrors()) {
				return false;
			}
			else {
				return true;
			}
		}

		/**
		* ShowPaymentForm
		* Show a payment form for this particular gateway if there is one.
		* This is useful for gateways that require things like credit card details
		* to be submitted and then processed on the site.
		*/
		public function ShowPaymentForm()
		{
			$GLOBALS['CreditCardMonths'] = $GLOBALS['CreditCardYears'] = '';
			$GLOBALS['CreditCardIssueDateMonths'] = $GLOBALS['CreditCardIssueDateYears'] = '';

			$cc_type = "";

			if(isset($_POST['creditcard_cctype'])) {
				$cc_type = $_POST['creditcard_cctype'];
			}

			$GLOBALS['CCTypes'] = $this->_GetCCTypes($cc_type);

			for ($i = 1; $i <= 12; $i++) {
				$stamp = mktime(0, 0, 0, $i, 15, date("Y"));

				$i = str_pad($i, 2, "0", STR_PAD_LEFT);

				if (isset($_POST['creditcard_ccexpm']) && $_POST['creditcard_ccexpm'] == $i) {
					$sel = 'selected="selected"';
				} else {
					$sel = "";
				}

				if(isset($_POST['creditcard_issuedatem']) && $_POST['creditcard_issuedatem'] == $i) {
					$issueSel = 'selected="selected"';
				}
				else {
					$issueSel = '';
				}

				$GLOBALS['CreditCardMonths'] .= sprintf("<option %s value='%s'>%s</option>", $sel, $i, date("M", $stamp));
				$GLOBALS['CreditCardIssueDateMonths'] .= sprintf("<option %s value='%s'>%s</option>", $issueSel, $i, date("M", $stamp));
			}

			for ($i = date("Y"); $i <= date("Y")+10; $i++) {
				if(isset($_POST['creditcard_ccexpy']) && $_POST['creditcard_ccexpy'] == isc_substr($i, 2, 2)) {
					$sel = 'selected="selected"';
				}
				else {
					$sel = "";
				}
				$GLOBALS['CreditCardYears'] .= sprintf("<option %s value='%s'>%s</option>", $sel, isc_substr($i, 2, 2), $i);
			}

			for ($i = date("Y"); $i > date("Y")-5; --$i) {
				if(isset($_POST['creditcard_issuedatey']) && $_POST['creditcard_issuedatey'] == isc_substr($i, 2, 2)) {
					$sel = 'selected="selected"';
				}
				else {
					$sel = "";
				}
				$GLOBALS['CreditCardIssueDateYears'] .= sprintf("<option %s value='%s'>%s</option>", $sel, isc_substr($i, 2, 2), $i);
			}

			if ($this->CardTypeRequiresCVV2($cc_type)) {
				$GLOBALS['CreditCardHideCardCode'] = '';
			}
			else {
				$GLOBALS['CreditCardHideCardCode'] = 'none';
			}

			// Was there an error validating the payment? If so, pre-fill the form fields with the already-submitted values
			if($this->HasErrors()) {
				$fields = array(
					"CreditCardName" => 'creditcard_name',
					"CreditCardNum" => 'creditcard_ccno',
					"CreditCardCardCode" => 'creditcard_cccvd',
					"CreditCardIssueNo" => 'creditcard_issueno'
				);
				foreach($fields as $global => $post) {
					if(isset($_POST[$post])) {
						$GLOBALS[$global] = isc_html_escape($_POST[$post]);
					}
				}

				$errorMessage = implode("<br />", $this->GetErrors());
				$GLOBALS['CreditCardErrorMessage'] = $errorMessage;
			}
			else {
				// Hide the error message box
				$GLOBALS['HideCreditCardError'] = "none";
			}

			$pendingOrder = LoadPendingOrderByToken();
			$GLOBALS['OrderAmount'] = CurrencyConvertFormatPrice($pendingOrder['total_inc_tax'], $pendingOrder['ordcurrencyid'], $pendingOrder['ordcurrencyexchangerate']);


			// Get additional payment page contents if there is any
			if(method_exists($this, 'GetAdditionalPaymentPageContents')) {
				$GLOBALS['AdditionalPaymentPageContents'] = $this->GetAdditionalPaymentPageContents();
			}
			// Collect their details to send through to CreditCard
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("genericcreditcard");
			return $GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate(true);
		}

		/**
		 * Check if a particular credit card type requires a CVV2/CSV code.
		 *
		 * @param string The type of the credit card to check.
		 * @return boolean True if a CVV2 code is required.
		 */
		public function CardTypeRequiresCVV2($type)
		{
			if(isset($this->_card_types[$type]['requiresCVV2']) && $this->_card_types[$type]['requiresCVV2'] && ($this->GetValue("cardcode") == "YES" || $this->cardCodeRequired)) {
				return true;
			}
			else {
				return false;
			}
		}

		/**
		 * Check if a particular credit card type can contain an issue code.
		 *
		 * @param string The type of the credit card to check.
		 * @return boolean True if an issue code.
		 */
		public function CardTypeHasIssueNo($type)
		{
			if(isset($this->_card_types[$type]['hasIssueNo']) && $this->_card_types[$type]['hasIssueNo']) {
				return true;
			}
			else {
				return false;
			}
		}

		/**
		 * Check if a particular credit card type requires an issue code.
		 *
		 * @param string The type of the credit card to check.
		 * @return boolean True if an issue code.
		 */
		public function CardTypeRequiresIssueNo($type)
		{
			if(isset($this->_card_types[$type]['requireIssueNo']) && $this->_card_types[$type]['requireIssueNo']) {
				return true;
			}
			else {
				return false;
			}
		}

		/**
		 * Check if a particular credit card type can contain an issue date.
		 *
		 * @param string The type of the credit card to check.
		 * @return boolean True if an issue date is allowable.
		 */
		public function CardTypeHasIssueDate($type)
		{
			if(isset($this->_card_types[$type]['hasIssueDate']) && $this->_card_types[$type]['hasIssueDate']) {
				return true;
			}
			else {
				return false;
			}
		}

		/**
		 * Check if a particular credit card type requires the issue date
		 *
		 * @param string The type of the credit card to check.
		 * @return boolean True if an issue date is allowable.
		 */
		public function CardTypeRequiresIssueDate($type)
		{
			if(isset($this->_card_types[$type]['requireIssueDate']) && $this->_card_types[$type]['hasIssueDate']) {
				return true;
			}
			else {
				return false;
			}
		}


		/**
		 * Check if a particular credit card type requires either issue date or issue number
		 *
		 * @param string The type of the credit card to check.
		 * @return boolean True if an issue date is allowable.
		 */
		public function CardTypeRequiresIssueNoOrDate($type)
		{
			if(isset($this->_card_types[$type]['requiresIssueNoOrDate']) && $this->_card_types[$type]['requiresIssueNoOrDate']) {
				return true;
			}
			else {
				return false;
			}
		}


		/**
		* ProcessPaymentForm
		* Process and validate input from a payment form for this particular
		* gateway.
		*
		* @return boolean True if valid details and payment has been processed. False if not.
		*/
		public function ProcessPaymentForm($dataSource = array())
		{
			if (empty($dataSource)) {
				$dataSource = $_POST;
			}

			$postData = $this->_Validate($dataSource);

			if ($postData === false) {
				return false;
			}

			// Is setup in test or live mode?
			$this->_testmode = $this->GetValue("testmode") == "YES";

			$gateway_postdata = $this->_ConstructPostData($postData);

			if ($this->_testmode) {
				$transactionURL = $this->_testTransactionURL;
				$transactionURI = $this->_testTransactionURI;
			}
			else {
				$transactionURL = $this->_liveTransactionURL;
				$transactionURI = $this->_liveTransactionURI;
			}

			if ($this->_redirect) {
				$this->RedirectToProvider($transactionURL.$transactionURI,$gateway_postdata);
				exit;
			}

			if (is_array($gateway_postdata)) {

				$gatewayData = $gateway_postdata['gatewayData'];
				$soapAction = $gateway_postdata['soapAction'];

			} else {
				$gatewayData = $gateway_postdata;
				$soapAction = null;
			}

			$result = $this->_ConnectToProvider($transactionURL, $transactionURI, $gatewayData, $soapAction);

			if (!$result) {
				$this->SetError(GetLang('CreditCardGatewayFail'));
				return false;
			}

			$result = $this->_HandleResponse($result);

			if ($result) {
				$this->SetPaymentStatus(PAYMENT_STATUS_PAID);
			}

			return $result;
		}


		/**
		* send request to payment provider and retrieve respone
		*
		* @param string $transactionURL url to connect payment provider
		* @param string $transactionURI uri to connect payment provider
		* @param string $gateway_postdata data send to payment provider
		*
		* @return array data received from payment provider.
		*/
		protected function _ConnectToProvider($transactionURL, $transactionURI, $gateway_postdata, $soapAction = null)
		{
			$responseHeader = '';
			if ($this->requiresSoap) {

				if ($soapAction == null) {
					$soapAction = $this->soapAction;
				}

				require_once(dirname(__FILE__)."/../../lib/nusoap/nusoap.php");

				$client = new nusoap_client($transactionURL.$transactionURI, 'wsdl');
				$result = $client->call($soapAction, $gateway_postdata);

				return $result;
			}

			if(function_exists("curl_exec") && $this->_curlSupported) {

				// Use CURL if it's available
				$ch = curl_init($transactionURL.$transactionURI);

				curl_setopt($ch, CURLOPT_POST, 1);
				if ($this->requireHeaders == true) {
					curl_setopt($ch, CURLOPT_HEADER, 1);
				}
				curl_setopt($ch, CURLOPT_POSTFIELDS, $gateway_postdata);
				curl_setopt($ch, CURLOPT_TIMEOUT, 60);
				if ($this->port != 0) {
					curl_setopt($ch, CURLOPT_PORT, $this->port);
				}
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

				if ($this->sslCertificatePath != '') {
					curl_setopt($ch, CURLOPT_SSLCERT, dirname(__FILE__).'/../../modules/checkout/'.$this->sslCertificatePath);
				}

				if ($this->sslVersion) {
					curl_setopt($ch, CURLOPT_SSLVERSION, $this->sslVersion);
				}

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

				if(curl_errno($ch)) {
					$this->SetError(GetLang('CreditCardCurlError'). $this->GetValue('displayname') . ": " .curl_error($ch));
					return false;
				}

				if ($this->requireHeaders == true) {
					$result = explode("\n\r\n", $result);
					$responseHeader = $result[0];
					$result = $result[1];

				}
			}
			else if(function_exists("fsockopen") && $this->_fsocksSupported) {

				$header = "POST " . $transactionURI . " HTTP/1.0\r\n";
				$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
				$header .= "Content-Length: " . strlen($gateway_postdata) . "\r\n\r\n";

				$url = parse_url($transactionURL);

				if ($url['scheme'] == 'https') {
						$url['host'] = 'ssl://'.$url['host'];
				}

				if (!isset($url['port']) || $url['port'] == '') {
					if ($url['scheme'] == 'http') {
						$url['port'] = 80;
					}
					else if ($url['scheme'] == 'https') {
						$url['port'] = 443;
					}
				}

				if($fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 30)) {
					@fputs($fp, $header . $gateway_postdata);

					// Read the body data
					$result = "";
					$responseHeader = '';
					$headerdone = false;

					while(!@feof($fp)) {
						$line = @fgets($fp, 1024);

						if(@strcmp($line, "\r\n") == 0) {
							// Header has been read
							$headerdone = true;
						}
						else if (!$headerdone) {
							// Read the header
							$responseHeader .= (string)$line;
						}
						else if($headerdone) {
							// Header has been read, read the contents
							$result .= $line;
						}
					}

				}
				else {
					$this->SetError(GetLang('CreditCardFSockError') . $this->GetValue('displayname'));
					return false;
				}
			}
			else {
				$this->SetError(GetLang('CreditCardConnectionMethod') . $this->GetValue('displayname'));
				return false;
			}

			if (empty($result)) {
				return $responseHeader;
			}

			return $result;
		}


		/**
		* _ConstructPostData
		* Construct data to send to the payment gateway
		*
		* @param String $postData The data from the input form
		* @param String $pendingOrder Data from the users order
		* @return String
		*/
		protected function _ConstructPostData($postData)
		{
			return '';
		}

		/**
		* _HandleResponse
		* Handle the response sent by the payment gateway
		*
		* @param String $response The data from the payment gateway
		* @param String $pendingOrder Data from the users order
		*/
		protected function _HandleResponse($response)
		{
			return;
		}

		/**
		* _GetCCTypes
		* Get a list of credit card types and return them as options
		*
		* @param String $Selected The selected card type if the form was already posted
		* @return String
		*/
		public function _GetCCTypes($Selected="")
		{
			$options = "";

			// Get the enabled credit card types
			$card_types = $this->GetValue("acceptedcards");

			if(!is_array($card_types)) {
				if($card_types != '') {
					$card_types = array($card_types);
				}
				else {
					$card_types = array_keys($this->_card_types);
				}
			}

			// sort the cards alphabetically
			uasort($card_types, array($this, 'compare_cards'));

			foreach($card_types as $type) {

				if (!in_array($type,$this->_cardsSupported)) {
					continue;
				}

				$card = $this->_card_types[$type];
				if($Selected == $type) {
					$sel = "selected=\"selected\"";
				}
				else {
					$sel = "";
				}

				$class = '';
				if($this->CardTypeRequiresCVV2($type)) {
					$class .= ' requiresCVV2';
				}

				if($this->CardTypeHasIssueNo($type)) {
					$class .= ' hasIssueNo';
				}

				if($this->CardTypeHasIssueDate($type)) {
					$class .= ' hasIssueDate';
				}

				$options .= sprintf("<option id='CCType_%s' class='%s' value='%s' %s>%s</option>", $type, $class, $type, $sel, $card['type']);
			}

			return $options;
		}

		private function compare_cards($a, $b)
		{
			$a = $this->_card_types[$a];
			$b = $this->_card_types[$b];

			return strcasecmp($a['type'], $b['type']);
		}

		protected function _Validate($data = array())
		{
			if(empty($data)) {
				//if this is a control panel manual payment
				if(isset($_POST['paymentField'][$this->GetId()])) {
					$data=$_POST['paymentField'][$this->GetId()];
				//store front normal checkout
				} else {
					$data=$_POST;
				}
			}
			$validatedVariables = array();

			// Check for HTTPS if its required
			if(!strtolower($_SERVER['HTTPS']) == "on") {
				ob_end_clean();
				?>
					<script type="text/javascript">
						alert("<?php echo GetLang($this->_languagePrefix.'NoSSLError'); ?>");
						document.location.href="<?php echo $GLOBALS['ShopPath']; ?>/checkout.php?action=confirm_order";
					</script>
				<?php
				die();
			}

			//basic required credit card fields
			$requiredFields = array(
				"creditcard_cctype"		=> GetLang('CreditCardSelectCardType'),
				"creditcard_name"		=> GetLang('CreditCardEnterName'),
				"creditcard_ccno"		=> GetLang('CreditCardEnterCardNumber'),
				"creditcard_ccexpm"		=> GetLang('CreditCardEnterCreditCardMonth'),
				"creditcard_ccexpy"		=> GetLang('CreditCardEnterCreditCardYear'),
			);
			foreach($requiredFields as $field => $message) {
				if(!isset($data[$field]) || trim($data[$field]) == '') {
					$this->SetError($message);
					return false;
				}
			}

			//if CVV2 is required
			if($this->CardTypeRequiresCVV2($data['creditcard_cctype'])) {
				if(!isset($data['creditcard_cccvd']) || trim($data['creditcard_cccvd']) == '') {
					$this->SetError(GetLang('CreditCardEnterCardCode'));
					return false;
				}
			}

			//if issue date/number is required
			if($this->CardTypeRequiresIssueNoOrDate($data['creditcard_cctype'])) {
				//if issue number is invalid
				if((!isset($data['creditcard_issueno']) || !is_numeric($data['creditcard_issueno']))) {
					// and if issue date is invalid
					if(!isset($data['creditcard_issuedatem']) || !is_numeric($data['creditcard_issuedatem']) || !isset($data['creditcard_issuedatey']) || !is_numeric($data['creditcard_issuedatey'])) {

						$this->SetError(GetLang('CreditCardEnterIssueNoOrDate'));
						return false;
					}
				}
			}



			//if issue date is required
			if($this->CardTypeHasIssueDate($data['creditcard_cctype']) && $this->CardTypeRequiresIssueDate($_POST['creditcard_cctype'])) {
				if(!isset($data['creditcard_issuedatey']) || trim($data['creditcard_issuedatey']) == '') {
					$this->SetError(GetLang('CreditCardSelectCreditCardIssueYear'));
					return false;
				}
				if(!isset($data['creditcard_issuedatem']) || trim($data['creditcard_issuedatem']) == '') {
					$this->SetError(GetLang('CreditCardSelectCreditCardIssueMonth'));
					return false;
				}
			}

			//if issue No is required
			if($this->CardTypeHasIssueNo($data['creditcard_issueno']) && $this->CardTypeRequiresIssueNo($data['creditcard_cctype'])) {
				if(!isset($data['creditcard_issueno']) || trim($data['creditcard_issueno']) == '') {
					$this->SetError(GetLang('CreditCardSelectCreditCardIssueNo'));
					return false;
				}
			}

			//check if credit card expired.
			$currentMY = isc_mktime(0, 0, 0, isc_date('m')+1, 0, isc_date('y'));
			$cardMY = isc_mktime(0, 0, 0, $data['creditcard_ccexpm']+1, 0, $data['creditcard_ccexpy']);
			if ($currentMY > $cardMY) {
				$this->SetError(GetLang('CreditCardExpired'));
				return false;
			}

			$validatedVariables['cctype'] = $data['creditcard_cctype'];
			$validatedVariables['name'] = $data['creditcard_name'];
			$validatedVariables['ccno'] = $data['creditcard_ccno'];
			$validatedVariables['ccissueno'] = $data['creditcard_issueno'];
			$validatedVariables['ccissuedatem'] = $data['creditcard_issuedatem'];
			$validatedVariables['ccissuedatey'] = $data['creditcard_issuedatey'];
			$validatedVariables['cccvd'] = $data['creditcard_cccvd'];
			$validatedVariables['ccexpm'] = $data['creditcard_ccexpm'];
			$validatedVariables['ccexpy'] = $data['creditcard_ccexpy'];

			return $validatedVariables;
		}

		public function Process3DCallBack()
		{
			$orderStatus=$this->_Handle3DCallBackResponse();
			if($orderStatus) {
				if(CompletePendingOrder($_COOKIE['SHOP_ORDER_TOKEN'], $orderStatus)) {
					// Everything is fine, send the customer to the thank you page.
					@ob_end_clean();
					//header("Location: ".$GLOBALS['ShopPathSSL']."/finishorder.php");
					//it needs to redirect the parent window for PayPal website payments pro, because 3D secure authoriation form is loaded in an iframe on the page
					echo "<script>top.location.href='".$GLOBALS['ShopPathSSL']."/finishorder.php';</script>";
					die();
				}
			}
		}

		/**
		* this function reads the 3D secure response from the gateway, and returns a order status
		*
		*/
		protected function _Handle3DCallBackResponse()
		{
			return;
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
			$issueMonthOptions = '<option value="">&nbsp;</option>';
			for($i = 1; $i <= 12; $i++) {
				$stamp = mktime(0, 0, 0, $i, 15, date("Y"));
				$i = str_pad($i, 2, "0", STR_PAD_LEFT);

				$monthOptions .= '<option value="'.$i.'">'.date('M', $stamp).'</option>';

				$issueMonthOptions .= '<option value="'.$i.'">'.date('M', $stamp).'</option>';
			}

			$yearOptions = '';
			for($i = date("Y"); $i <= date("Y")+10; $i++) {
				$value = isc_substr($i, 2, 2);
				$yearOptions .= '<option value="'.$value.'">'.$i.'</option>';
			}

			$issueYearOptions = '<option value="">&nbsp;</option>';
			for($i = date("Y"); $i > date("Y")-5; --$i) {
				$value = isc_substr($i, 2, 2);
				$issueYearOptions .= '<option value="'.$value.'">'.$i.'</option>';
			}

			$cardOptions = $this->_GetCCTypes();
			return array(
				'creditcard_name' => array(
					'type' => 'text',
					'title' => GetLang('CCManualCardHoldersName'),
					'value' => '',
					'required' => true
				),
				'creditcard_cctype' => array(
					'type' => 'select',
					'title' => GetLang('CCManualCreditCardType'),
					'options' => $cardOptions,
					'onchange' => "PaymentValidation_" . $this->GetId() . ".updateCreditCardType()",
					'required' => true
				),
				'creditcard_ccno' => array(
					'type' => 'text',
					'title' => GetLang('CCManualCreditCardNo'),
					'value' => '',
					'required' => true
				),
				'creditcard_cccvd' => array(
					'type' => 'text',
					'title' => GetLang('CCManualCreditCardCCV2'),
					'value' => '',
					'required' => true,
					'class' => 'Field50',
				),
				'creditcard_ccexp' => array(
					'type' => 'html',
					'title' => GetLang('CCManualExpirationDate'),
					'html' => '
						<select name="paymentField[' . $this->GetId() . '][creditcard_ccexpm]">'.$monthOptions.'</select>
						&nbsp;
						<select name="paymentField[' . $this->GetId() . '][creditcard_ccexpy]">'.$yearOptions.'</select>
					',
					'required' => true
				),
				'creditcard_issueno' => array(
					'type' => 'text',
					'title' => GetLang('CCManualCreditCardIssueNo'),
					'value' => '',
					'required' => true
				),
				'creditcard_issuedate' => array(
					'type' => 'html',
					'title' => GetLang('CCManualIssueDate'),
					'html' => '
						<select name="paymentField[' . $this->GetId() . '][creditcard_issuedatem]">'.$issueMonthOptions.'</select>
						&nbsp;
						<select name="paymentField[' . $this->GetId() . '][creditcard_issuedatey]">'.$issueYearOptions.'</select>
					',
					'required' => true
				)
			);
		}

		public function GetManualPaymentJavascript()
		{
			return Interspire_Template::getInstance('admin')->render('Snippets/PaymentValidation_generic.creditcard.html');
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

			$result = $this->ProcessPaymentForm($data, true);
			$message = '';
			if ($this->HasErrors()) {
				foreach ($this->GetErrors() as $error) {
					$message .= $error . "<br />";
				}
			}
			return array('amount' => $amount, 'result' => $result, 'message' => $message);
		}
	}