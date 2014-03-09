<?php

	class CHECKOUT_MIGSREDIR extends ISC_CHECKOUT_PROVIDER
	{
		protected $requiresSSL = false;

		public $_id = 'checkout_migsredir';

		protected $_currenciesSupported = array('AUD');
		public $_languagePrefix = 'MIGSRedirect';

		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the MIGS checkout module
			parent::__construct();

			$this->SetName(GetLang('MIGSRedirectName'));
			$this->SetImage('mastercard_logo.jpg');
			$this->SetDescription(GetLang('MIGSRedirectDesc'));
			$this->SetHelpText(sprintf(GetLang('MIGSRedirectHelp'), $GLOBALS['ShopPath']));
		}

		/**
		* Custom variables for the checkout module. Custom variables are stored in the following format:
		* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
		* variable_type types are: text,number,password,radio,dropdown
		* variable_options is used when the variable type is radio or dropdown and is a name/value array.
		*/
		public function SetCustomVars()
		{
			$this->_variables['displayname'] = array(
				'name'		=> GetLang('MIGSRedirectDisplayName'),
				'type'		=> 'textbox',
				'help'		=> GetLang('DisplayNameHelp'),
				'default'	=> $this->GetName(),
				'required'	=> true
			);

			$this->_variables['merchantid'] = array(
				'name'		=> GetLang('MIGSRedirectMerchantId'),
				'type'		=> 'textbox',
				'help'		=> GetLang('MIGSRedirectMerchantIdHelp'),
				'default'	=> '',
				'required'	=> true
			);

			$this->_variables['accesscode'] = array(
				'name'		=> GetLang('MIGSRedirectAccessCode'),
				'type'		=> 'textbox',
				'help'		=> GetLang('MIGSRedirectAccessCodeHelp'),
				'default'	=> '',
				'required'	=> true
			);

			$this->_variables['securehash'] = array(
				'name'		=> GetLang('MIGSRedirectSecureHash'),
				'type'		=> 'textbox',
				'help'		=> GetLang('MIGSRedirectSecureHashHelp'),
				'default'	=> '',
				'required'	=> true
			);

			$this->_variables['testmode'] = array(
				'name' => GetLang('MIGSRedirectTestMode'),
				'type' => 'dropdown',
				'help' => GetLang('MIGSRedirectTestModeHelp'),
				'default' => 'no',
				'required' => true,
				'options' => array(
					GetLang('MIGSRedirectTestModeNo') => 'NO',
					GetLang('MIGSRedirectTestModeYes') => 'YES'
				),
				'multiselect' => false
			);
		}

		public function IsSupported()
		{
			$currency = GetDefaultCurrency();

			// Check if the default currency is supported by the payment gateway
			if (!in_array($currency['currencycode'], $this->_currenciesSupported)) {
				$this->SetError(sprintf(GetLang('MIGSRedirectCurrecyNotSupported'), implode(',',$this->_currenciesSupported)));
			}

			if($this->HasErrors()) {
				return false;
			}
			else {
				return true;
			}
		}

		public function TransferToProvider()
		{
			$migsurl = 'https://migs.mastercard.com.au/vpcpay';

			$transactionid 	= $this->GetCombinedOrderId();

			$sHash = $this->GetValue('securehash');

			$merchantId = $this->GetValue("merchantid");
			$chargeAmount = $this->GetGatewayAmount();

			if($this->GetValue('testmode') == 'YES') {
				$merchantId = 'TEST' . $merchantId;
				$chargeAmount = 1;
			}

			// MIGS accepts payments in cents
			$amount = number_format($chargeAmount * 100, 0, '','');

			$post['vpc_Version'] 		= 1;
			$post['vpc_Command'] 		= 'pay';
			$post['vpc_MerchTxnRef'] 	= $transactionid;
			$post['vpc_AccessCode'] 	= $this->GetValue('accesscode');
			$post['vpc_ReturnURL'] 		= GetConfig('ShopPathSSL').'/finishorder.php';
			$post['vpc_Merchant'] 		= $merchantId;
			$post['vpc_Locale']			= 'en';
			$post['vpc_OrderInfo']		= $transactionid;
			$post['vpc_Amount'] 		= $amount;

			$md5HashData = $sHash . $post['vpc_AccessCode'] . $post['vpc_Amount'] . $post['vpc_Command'] . $post['vpc_Locale'] . $post['vpc_MerchTxnRef'] . $post['vpc_Merchant'] . $post['vpc_OrderInfo'] . $post['vpc_ReturnURL'] . $post['vpc_Version'];
			$post['vpc_SecureHash'] 	= strtoupper(md5($md5HashData));

			$this->RedirectToProvider($migsurl, $post);
		}

		public function VerifyOrderPayment()
		{
			$trnId = $_REQUEST['vpc_TransactionNo'];

			$responseCode = $_REQUEST['vpc_TxnResponseCode'];
			$responseMessage = $_REQUEST['vpc_Message'];
			$amount = $_REQUEST['vpc_Amount'];

			$checkAmount = $this->GetGatewayAmount();
			if ($this->GetValue('testmode') == 'YES') {
				$checkAmount = 1;
			}

			if ($amount != number_format($checkAmount * 100, 0, '','')) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang('MIGSRedirectAmountIncorrect'), $amount, number_format($checkAmount * 100, 0, '','')));

				// Something went wrong, show the error message with the credit card form
				$this->SetError(GetLang('MIGSRedirectSomethingWentWrong').sprintf("%s : %s", $responseCode, $responseMessage));
				return false;
			}

			if($responseCode == '0') {
				$updatedOrder = array(
					'ordpayproviderid' => $trnId,
					'ordpaymentstatus' => 'captured'
				);
				$this->UpdateOrders($updatedOrder);

				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang('MIGSRedirectSuccess'));
				$this->SetPaymentStatus(PAYMENT_STATUS_PAID);
				return true;
			}

			$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang('MIGSRedirectFailure'), $responseCode, $responseMessage));
			return false;
		}
	}