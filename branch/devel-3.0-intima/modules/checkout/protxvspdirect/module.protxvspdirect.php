<?php

	class CHECKOUT_PROTXVSPDIRECT extends ISC_GENERIC_CREDITCARD
	{

	//	private $simulatorTransactionURL = 'https://test.sagepay.com';
	//	private $simulatorTransactionURI = '/gateway/service/vspdirect-register.vsp';


		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the Protx Vps Direct checkout module

			$this->_languagePrefix = "ProtxVspDirect";
			$this->_id = "checkout_vspdirect";
			$this->_image = "sagepay_logo.jpg";

			parent::__construct();

			$this->requiresSSL = true;
			$this->_currenciesSupported = array("GBP","EUR","USD","AUD","JPY","CAD");
			$this->_cardsSupported = array ('VISA','AMEX','MC', 'DELTA', 'DINERS', 'DISCOVER', 'SOLO','MAESTRO','SWITCH','LASER');
			$this->_liveTransactionURL = 'https://live.sagepay.com';
			$this->_testTransactionURL = 'https://test.sagepay.com';
			$this->_liveTransactionURI = '/gateway/service/vspdirect-register.vsp';
			$this->_testTransactionURI = '/gateway/service/vspdirect-register.vsp';
			$this->_simulatorTransactionURL = 'https://test.sagepay.com';
			$this->_simulatorTransactionURI = '/Simulator/VSPDirectGateway.asp';


			$this->_similator3DSecureURL = 'https://test.sagepay.com/Simulator/VSPDirectCallback.asp';
			$this->_test3DSecureURL = 'https://test.sagepay.com/gateway/service/direct3dcallback.vsp';
			$this->_live3DSecureURL = 'https://live.sagepay.com/gateway/service/direct3dcallback.vsp';

			$this->_curlSupported = true;
			$this->_fsocksSupported = true;
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

			$this->_variables['vendorname'] = array("name" => GetLang('ProtxVspDirectVendorName'),
			   "type" => "textbox",
			   "help" => GetLang('ProtxVspDirectVendorNameHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['cardcode'] = array("name" => GetLang('ProtxVspDirectCardCode'),
			   "type" => "dropdown",
			   "help" => GetLang('ProtxVspDirectCardCodeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(GetLang('ProtxVspDirectCardCodeNo') => "NO",
							  GetLang('ProtxVspDirectCardCodeYes') => "YES"
				),
				"multiselect" => false
			);

			$this->_variables['transactiontype'] = array(
				"name" => GetLang('ProtxVspDirectTransactionType'),
				"type" => "dropdown",
				"help" => GetLang('ProtxVspDirectTransactionTypeHelp'),
				"default" => "no",
				"savedvalue" => array(),
				"required" => true,
				"options" => array(
					GetLang('ProtxVspDirectAuthorize') => "DEFERRED",
					GetLang('ProtxVspDirectSale') => "PAYMENT"
				),
				"multiselect" => false
			);


			$this->_variables['testmode'] = array("name" => GetLang('ProtxVspDirectTestMode'),
	   "type" => "dropdown",
			   "help" => GetLang('ProtxVspDirectTestModeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(GetLang('ProtxVspDirectTestModeTest') => "TEST",
							  GetLang('ProtxVspDirectTestModeSimulator') => "SIMULATOR",
							  GetLang('ProtxVspDirectTestModeLive') => "LIVE"
				),
				"multiselect" => false
			);
		}

		public function ProcessPaymentForm($data= array(), $manualPayment = false)
		{
			$postData = $this->_Validate();

			if ($postData === false) {
				return false;
			}

			// Is setup in test or live mode?
			$this->_testmode = $this->GetValue("testmode") == "YES";

			$gateway_postdata = $this->_ConstructPostData($postData);


			if ($this->GetValue("testmode") == "TEST") {
				$transactionURL = $this->_testTransactionURL;
				$transactionURI = $this->_testTransactionURI;
			}
			else if ($this->GetValue("testmode") == "LIVE") {
				$transactionURL = $this->_liveTransactionURL;
				$transactionURI = $this->_liveTransactionURI;
			}
			else if ($this->GetValue("testmode") == "SIMULATOR") {
				$transactionURL = $this->_simulatorTransactionURL;
				$transactionURI = $this->_simulatorTransactionURI;
			}

			$result = $this->_ConnectToProvider($transactionURL, $transactionURI, $gateway_postdata);

			if (!$result) {
				$this->SetError(GetLang('CreditCardGatewayFail'));
				return false;
			}

			$result = $this->_HandleResponse($result, $manualPayment);

			if ($result) {
				$this->SetPaymentStatus(PAYMENT_STATUS_PAID);
			}

			return $result;
		}

		protected function _ConstructPostData($postData)
		{
			$transactionid	= $this->GetCombinedOrderId();

			$pendingOrder = LoadPendingOrderByToken();
			$description = sprintf(GetLang('ProtxVspDirectOrderFromX'), $transactionid, $GLOBALS['StoreName']);

			$ccname 		= $postData['name'];
			$cctype 		= $postData['cctype'];

			$ccissueno 		= $postData['ccissueno'];
			$ccissuedatem 	= $postData['ccissuedatem'];
			$ccissuedatey 	= $postData['ccissuedatey'];

			$ccnum 			= $postData['ccno'];
			$ccexpm 		= $postData['ccexpm'];
			$ccexpy 		= $postData['ccexpy'];
			$cccvd 			= $postData['cccvd'];

			$currency = GetDefaultCurrency();

			$amount = number_format($this->GetGatewayAmount(),2,'.','');

			$billState = '';
			if($pendingOrder['ordbillcountrycode'] == 'US') {
				$billState = GetStateISO2ById($pendingOrder['ordbillstateid']);
			}

			$shippingAddress = $this->getShippingAddress();
			$shipState = '';
			if($shippingAddress['country_iso2'] == 'US') {
				$shipState = GetStateISO2ById($shipingAddress['state_id']);
			}

			$TransType = 'DEFERRED';
			if($this->GetValue('transactiontype')) {
				$TransType = $this->GetValue('transactiontype');
			}

			// Contstruct the POST data
			$vspdirect_post = array(
				'VPSProtocol'		=> '2.23',
				'TxType'			=> $TransType,
				'Vendor' 			=> $this->GetValue("vendorname"),
				'VendorTxCode' 		=> 'ISC-'.$transactionid,
				'Description'		=> $description,

				'CardType' 			=> $cctype,
				'CardNumber' 		=> $ccnum,
				'CardHolder' 		=> $ccname,
				'ExpiryDate' 		=> $ccexpm.$ccexpy,
				'Amount' 			=> $amount,
				'Currency' 			=> $currency['currencycode'],

				'BillingSurname'	=> $pendingOrder['ordbilllastname'],
				'BillingFirstnames'	=> $pendingOrder['ordbillfirstname'],
				'BillingAddress1'	=> $pendingOrder['ordbillstreet1'],
				'BillingAddress2'	=> $pendingOrder['ordbillstreet2'],
				'BillingCity'		=> $pendingOrder['ordbillsuburb'],
				'BillingState'		=> $billState,
				'BillingPostCode' 	=> $pendingOrder['ordbillzip'],
				'BillingCountry'	=> $pendingOrder['ordbillcountrycode'],
				'BillingPhone' 		=> $pendingOrder['ordbillphone'],

				'DeliverySurname'	=> $shippingAddress['last_name'],
				'DeliveryFirstnames'=> $shippingAddress['first_name'],
				'DeliveryAddress1'	=> $shippingAddress['address_1'],
				'DeliveryAddress2'	=> $shippingAddress['address_2'],
				'DeliveryCity'		=> $shippingAddress['city'],
				'DeliveryState'		=> $shipState,
				'DeliveryPostCode' 	=> $shippingAddress['zip'],
				'DeliveryCountry'	=> $shippingAddress['country_iso2'],
				'DeliveryPhone' 	=> $shippingAddress['phone'],


			);

			if ($this->CardTypeHasIssueDate($cctype)) {
				$vspdirect_post['StartDate'] 	= $ccissuedatem . $ccissuedatey;
			}

			if ($this->CardTypeHasIssueNo($cctype)) {
				$vspdirect_post['IssueNumber'] 	= $ccissueno;
			}

			if ($this->CardTypeRequiresCVV2($cctype)) {
				$vspdirect_post['CV2'] 			= $cccvd;
			}

			return http_build_query($vspdirect_post);
		}

		protected function _HandleResponse($result, $manualPayment=false)
		{
			$resultArray = $this -> ConvertResponseToArray($result);

			if(!isset($_SESSION['Checkout']['OrderIDs'])) {
				$orders = $this->GetOrders();
				$orderIds = array_keys($orders);
				$orderIdsString = '#'.implode(', #', $orderIds);
			} else {
				$orderIds = $_SESSION['Checkout']['OrderIDs'];
				$orderIdsString = '#'.implode(', #', $orderIds);
				unset($_SESSION['Checkout']['OrderIDs']);
			}

			$validStatuses = array(
				'OK',
				'ATTEMPTONLY'
			);

			$valid3DSecureStatuses = array(
				'OK',
				'NOTCHECKED',
				'ATTEMPTONLY',
				'NOAUTH',
				'CANTAUTH',
			);

			if(!empty($resultArray['Status']) && in_array($resultArray['Status'], $validStatuses) && (empty($resultArray['3DSecureStatus']) || in_array($resultArray['3DSecureStatus'], $valid3DSecureStatuses))) {
				if($this -> GetValue('transactiontype') == 'PAYMENT') {
					$paymentStatus = 'captured';
				} else {
					$paymentStatus = 'authorized';
				}

				$updatedOrder = array(
					'ordpayproviderid' => $resultArray['VPSTxId'],
					'ordpaymentstatus' => $paymentStatus
				);

				$this->UpdateOrders($updatedOrder, $orderIds);


				$successMsg = sprintf(GetLang('ProtxVspDirectSuccess'), $orderIdsString);
				$successDetails = sprintf(GetLang('ProtxVspDirectSuccessDetails'), $resultArray['Status'], $resultArray['StatusDetail'], $resultArray['VPSTxId']);

				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), $successMsg, $successDetails);
				return true;
			}

			//3D secure is enabled in merchants account
			if ($resultArray['Status'] == "3DAUTH") {
				if ($resultArray['3DSecureStatus'] == 'OK') {
					if($manualPayment){
						$this->SetError(GetLang('ProtxVspDirect3DManualPaymentError'));
						return false;
					}
					$_SESSION['Checkout']['OrderIDs'] = $orderIds;
					$this->_Redirect3DTransacitons($resultArray);
					die();
				}
			}

			//we are still here, the payment wasn't successful
			$error = sprintf(GetLang('ProtxVspDirectFailure'),  $orderIdsString);
			$errorDetails = sprintf(GetLang('ProtxVspDirectFailureDetails'), $resultArray['Status'], $resultArray['StatusDetail']);

			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), $error, $errorDetails);


			// Something went wrong, show the error message on order confirmation page.
			if($manualPayment == false) {
				$this->RedirectToOrderConfirmation(sprintf(GetLang('ProtxVspDirectSomethingWentWrong'), $resultArray['Status'], $resultArray['StatusDetail']));
			//payment made in control panel - phone order
			} else {
				$this->SetError(sprintf(GetLang('ProtxVspDirectSomethingWentWrong'), $resultArray['Status'], $resultArray['StatusDetail']));
			}
			return false;
		}

		private function _Redirect3DTransacitons($resultArray)
		{

			$GLOBALS['AuthURL'] = $resultArray['ACSURL'];

			$GLOBALS['PaReq'] = isc_html_escape($resultArray['PAReq']);
			$GLOBALS['TermUrl'] = $GLOBALS['ShopPathSSL']."/checkout.php?action=process_gateway_callback&provider=protxvspdirect&callback=Process3DCallBack";
			$GLOBALS['MD'] = isc_html_escape($resultArray['MD']);

			echo $this->ParseTemplate('protxvspdirect.3dredirectform', true);
		}

		protected function _Handle3DCallBackResponse()
		{
			if ($this->GetValue("testmode") == "TEST") {
				$callbackUrl = $this->_test3DSecureURL;
			}
			else if ($this->GetValue("testmode") == "LIVE") {
				$callbackUrl = $this->_live3DSecureURL;
			}
			else if ($this->GetValue("testmode") == "SIMULATOR") {
				$callbackUrl = $this->_similator3DSecureURL;
			}

			$postVars = "MD=" . $_REQUEST['MD'] . "&PARes=" . urlencode($_REQUEST['PaRes']);
			$result = PostToRemoteFileAndGetResponse($callbackUrl, $postVars);

			if($this->_HandleResponse($result)) {
				$orderStatus = ORDER_STATUS_AWAITING_FULFILLMENT;
			} else {
				$orderStatus = ORDER_STATUS_DECLINED;
			}
			return $orderStatus;
		}

		private function ConvertResponseToArray($responseString)
		{
			$resArr = explode("\n", trim($responseString));

			$resultArray = array();
			foreach ($resArr as $res) {
				list($name, $value) = explode("=", $res, 2);
				$resultArray[trim($name)] = trim($value);
			}
			return $resultArray;
		}
	}