<?php

	class CHECKOUT_WORLDPAY extends ISC_CHECKOUT_PROVIDER
	{
		/*
			The WorldPay installation ID
		*/
		private $_installid = 0;

		/*
			The currency to use for WorldPay
		*/
		private $_currency = "";

		/*
			Should the order be passed through in test mode?
		*/
		private $_testmode = "";


		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the WorldPay checkout module
			parent::__construct();
			$this->_name = GetLang('WorldPayName');
			$this->_image = "worldpay_logo.gif";
			$this->_description = GetLang('WorldPayDesc');
			$this->_help = sprintf(GetLang('WorldPayHelp'), $GLOBALS['ShopPathSSL']);
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
			$this->_variables['displayname'] = array("name" => "Display Name",
			   "type" => "textbox",
			   "help" => GetLang('DisplayNameHelp'),
			   "default" => $this->GetName(),
			   "required" => true
			);

			$this->_variables['installid'] = array("name" => "Installation ID",
			   "type" => "textbox",
			   "help" => GetLang('WorldPayInstallIdHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['callbackpw'] = array("name" => "Callback Password",
			   "type" => "password",
			   "help" => GetLang('WorldPayCallbackPWHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['currency'] = array("name" => "Currency",
			   "type" => "dropdown",
			   "help" => GetLang('WorldPayCurrencyHelp'),
			   "default" => "USD",
			   "required" => true,
			   "options" => array(GetLang('WorldPayCurrency1') => "AUD",
							  GetLang('WorldPayCurrency2') => "GBP",
							  GetLang('WorldPayCurrency3') => "CAD",
							  GetLang('WorldPayCurrency4') => "CZK",
							  GetLang('WorldPayCurrency5') => "DKK",
							  GetLang('WorldPayCurrency6') => "EUR",
							  GetLang('WorldPayCurrency7') => "HKD",
							  GetLang('WorldPayCurrency8') => "HUF",
							  GetLang('WorldPayCurrency9') => "JPY",
							  GetLang('WorldPayCurrency10') => "NZD",
							  GetLang('WorldPayCurrency11') => "NOK",
							  GetLang('WorldPayCurrency12') => "PLN",
							  GetLang('WorldPayCurrency13') => "SGD",
							  GetLang('WorldPayCurrency14') => "SEK",
							  GetLang('WorldPayCurrency15') => "CHF",
							  GetLang('WorldPayCurrency16') => "USD",
				),
				"multiselect" => false
			);

			$this->_variables['testmode'] = array("name" => "Test Mode",
			   "type" => "dropdown",
			   "help" => GetLang('WorldPayTestModeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(GetLang('WorldPayTestModeNo') => "NO",
							  GetLang('WorldPayTestModeYes') => "YES"
				),
				"multiselect" => false
			);
		}

		/**
		*	Redirect the customer to WorldPay's site to enter their payment details
		*/
		public function TransferToProvider()
		{
			$total = $this->GetGatewayAmount();
			$this->_installid = trim($this->GetValue("installid"));
			$this->_currency = $this->GetValue("currency");
			$testmode_on = $this->GetValue("testmode");

			// Load the pending order
			$billingDetails = $this->GetBillingDetails();
			$phone = $billingDetails['ordbillphone'];
			$phone = preg_replace("#[^\+0-9]+#", "", $phone);

			$country = GetCountryISO2ById($billingDetails['ordbillcountryid']);

			if($testmode_on == "YES") {
				$this->_testmode = "100";
				$url = "https://select-test.worldpay.com/wcc/purchase";
			} else {
				$this->_testmode = "0";
				$url = "https://secure.wp3.rbsworldpay.com/wcc/purchase";
			}

			$hiddenFields = array(
				'address' => $billingDetails['ordbillstreet1'].' '.$billingDetails['ordbillstreet2'],
				'country' => $country,
				'postcode' => $billingDetails['ordbillzip'],
				'tel' => $phone,
				'email' => $billingDetails['ordbillemail'],
				'instId' => $this->_installid,
				'cartId' => $_COOKIE['SHOP_ORDER_TOKEN'],
				'M_customerToken' => $_COOKIE['SHOP_SESSION_TOKEN'],
				'currency' => $this->_currency,
				'amount' => $total,
				'desc' => sprintf(GetLang('YourOrderFromX'), GetConfig('StoreName')),
				'testMode' => $this->_testmode
			);

			$this->RedirectToProvider($url, $hiddenFields);
		}

		/**
		*	Return the unique order token which was saved as a cookie pre-payment
		*/
		public function GetOrderToken()
		{
			return @$_REQUEST['cartId'];
		}

		/**
		*	Verify the order by checking the WorldPay variables
		*/
		public function VerifyOrderPayment()
		{
			// WorldPay fetches the contents of this page and displays it to the customer
			// so we need to restart the session with the customers session ID
			// so we can do the rest of the magic later such as resetting the customers
			// cart contents.
			session_write_close();
			$session = new ISC_SESSION($_REQUEST['M_customerToken']);

			if(isset($_POST['amount']) && isset($_POST['transStatus']) && isset($_POST['callbackPW'])) {
				$password = $_POST['callbackPW'];
				$amount = $_POST['amount'];
				$status = $_POST['transStatus'];
				$expectedPassword = $this->GetValue('callbackpw');

				// Check that the order totals match, the callback password matches and the transaction was successful
				if($this->GetGatewayAmount() == $amount && $status == "Y" && $password == $expectedPassword) {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang('WorldPaySuccess'));
					$this->SetPaymentStatus(PAYMENT_STATUS_PAID);
					return true;
				}
				else {
					$errorMsg = isc_html_escape(sprintf(GetLang('WorldPayErrorMismatchMsg'), $password, $expectedPassword, $amount, $this->GetGatewayAmount(), $status));
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('WorldPayErrorMismatch'), isc_html_escape($errorMsg));
					return false;
				}
			}
			else {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang('WorldPayErrorInvalid'), GetLang('WorldPayErrorInvalidMsg'));
				return false;
			}
		}
	}