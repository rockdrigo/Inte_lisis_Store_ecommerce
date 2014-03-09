<?php

	class CHECKOUT_VALUTEC extends ISC_GENERIC_CREDITCARD
	{
		private $identifier = null;

		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			$this->_languagePrefix 		= "Valutec";
			$this->_id 					= "checkout_valutec";
			$this->_image 				= "logo.jpg";

			parent::__construct();

			$this->_requiresSSL 		= true;
			$this->_currenciesSupported = array('USD');
			$this->_liveTransactionURL 	= 'https://www.valutec.net';
			$this->_testTransactionURL 	= 'https://www.valutec.net';
			$this->_liveTransactionURI 	= '/customers/transactions/valutec.asmx?WSDL';
			$this->_testTransactionURI 	= '/customers/transactions/valutec.asmx?WSDL';
			$this->_curlSupported = false;
			$this->_fsocksSupported = false;
			$this->requiresSoap = true;
			$this->cardCodeRequired = true;
			$this->soapAction = 'Sale';
		}

		/**
		* Custom variables for the checkout module. Custom variables are stored in the following format:
		* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
		* variable_type types are: text,number,password,radio,dropdown
		* variable_options is used when the variable type is radio or dropdown and is a name/value array.
		*/
		public function SetCustomVars()
		{
			$this->_variables['displayname'] = array("name" => GetLang($this->_languagePrefix.'DisplayName'),
			   "type" => "textbox",
			   "help" => GetLang('DisplayNameHelp'),
			   "default" => $this->GetName(),
			   "required" => true
			);

			$this->_variables['terminalid'] = array("name" => GetLang($this->_languagePrefix.'TerminalId'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'TerminalIdHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['serverid'] = array("name" => GetLang($this->_languagePrefix.'ServerId'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'ServerIdHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['programtype'] = array("name" => GetLang($this->_languagePrefix.'ProgramType'),
			   "type" => "dropdown",
			   "help" => GetLang($this->_languagePrefix.'ProgramTypeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(GetLang($this->_languagePrefix.'ProgramTypeGift') => "Gift",
							  GetLang($this->_languagePrefix.'ProgramTypeLoyalty') => "Loyalty"
				),
				"multiselect" => false
			);
		}

		public function ShowPaymentForm()
		{
			// Was there an error validating the payment? If so, pre-fill the form fields with the already-submitted values
			if($this->HasErrors()) {
				$fields = array(
					"CreditCardNum" => 'creditcard_ccno'
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

			// Collect their details to send through to CreditCard
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("valuteccard");
			return $GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate(true);
		}

		public function ProcessPaymentForm()
		{
			$postData = $this->_Validate();

			if ($postData === false) {
				return false;
			}

			// Is setup in test or live mode?
			$this->_testmode = $this->GetValue("testmode") == "YES";

			$this->soapAction = 'CardBalance';
			$gateway_postdata = $this->_ConstructPostData($postData);
			$gatewayData = $gateway_postdata['gatewayData'];
			$soapAction = $gateway_postdata['soapAction'];

			$transactionURL = $this->_liveTransactionURL;
			$transactionURI = $this->_liveTransactionURI;

			$result = $this->_ConnectToProvider($transactionURL, $transactionURI, $gatewayData, $soapAction);

			$response = $result[$this->soapAction.'Result'];

			if ($response['Balance'] < $this->GetGatewayAmount()) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'InsufficientBalance'));
				$this->SetError(GetLang($this->_languagePrefix.'InsufficientBalance'));
				return false;
			}

			$this->soapAction = 'Sale';
			$gateway_postdata = $this->_ConstructPostData($postData);
			$gatewayData = $gateway_postdata['gatewayData'];
			$soapAction = $gateway_postdata['soapAction'];
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

		protected function _Validate()
		{
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
				"creditcard_ccno"		=> GetLang('CreditCardEnterCardNumber'),
			);

			foreach($requiredFields as $field => $message) {
				if(!isset($_POST[$field]) || trim($_POST[$field]) == '') {
					$this->SetError($message);
					return false;
				}
			}

			$validatedVariables['ccno'] = $_POST['creditcard_ccno'];

			return $validatedVariables;
		}

		protected function _ConstructPostData($postData)
		{
			// PaymentExpress accepts payments in cents

			$ccnum = $postData['ccno'];
			$currency = GetDefaultCurrency();

			$billingDetails = $this->GetBillingDetails();

			$this->identifier = substr(sha1(time()),0,10);

			$gatewayData = array
			(
				'TerminalID' => $this->GetValue('terminalid'),
				'ProgramType' => $this->GetValue('programtype'),
				'CardNumber' => $ccnum,
				'Amount' => $this->GetGatewayAmount(),
				'ServerID' => $this->GetValue('serverid'),
				'Identifier' => $this->identifier
			);

			return array('gatewayData'=>$gatewayData, 'soapAction'=>$this->soapAction);
		}

		protected function _HandleResponse($response)
		{
			$response = $response[$this->soapAction.'Result'];

			if ($response['Authorized'] == 'true' && $this->identifier == $response['Identifier']) {

				// Save the authorization key
				$updatedOrder = array(
					'ordpayproviderid' => $response['AuthorizationCode'],
					'ordpaymentstatus' => 'captured',
				);

				$this->UpdateOrders($updatedOrder);

				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

				return true;
			}
			else {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Failure'), $response['ErrorMsg']));
				$this->SetError(GetLang($this->_languagePrefix.'SomethingWentWrong').sprintf(" : %s", $response['ErrorMsg']));
				return false;
			}
		}
	}