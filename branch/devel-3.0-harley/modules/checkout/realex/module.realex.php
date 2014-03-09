<?php

	class CHECKOUT_REALEX extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the Realex checkout module
			$this->_languagePrefix = "Realex";
			$this->_id = "checkout_realex";
			$this->_image = "realex_logo.jpg";

			parent::__construct();

			$this->requiresSSL = true;
			$this->_currenciesSupported = array('GBP','EUR');
			$this->_cardsSupported = array('VISA', 'AMEX', 'MC', 'LASER', 'SWITCH');
			$this->_liveTransactionURL = 'https://epage.payandshop.com';
			$this->_testTransactionURL = 'https://epage.payandshop.com';
			$this->_liveTransactionURI = '/epage-remote.cgi';
			$this->_testTransactionURI = '/epage-remote.cgi';
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
			$this->_variables['displayname'] = array("name" => GetLang($this->_languagePrefix.'DisplayName'),
			   "type" => "textbox",
			   "help" => GetLang('DisplayNameHelp'),
			   "default" => $this->GetName(),
			   "required" => true
			);

			$this->_variables['merchantid'] = array("name" => GetLang($this->_languagePrefix.'MerchantId'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'MerchantIdHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['merchantsecret'] = array("name" => GetLang($this->_languagePrefix.'MerchantSecret'),
			   "type" => "password",
			   "help" => GetLang($this->_languagePrefix.'MerchantSecretHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['account'] = array("name" => GetLang($this->_languagePrefix.'Account'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'AccountHelp'),
			   "default" => "",
			   "required" => false
			);

			$this->_variables['cardcode'] = array("name" => GetLang($this->_languagePrefix.'CardCode'),
			   "type" => "dropdown",
			   "help" => GetLang($this->_languagePrefix.'CardCodeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(GetLang($this->_languagePrefix.'CardCodeNo') => "NO",
							  GetLang($this->_languagePrefix.'CardCodeYes') => "YES"
				),
				"multiselect" => false
			);

		}

		protected function _ConstructPostData($postData)
		{
			// Realex accepts payments in cents

			$ccname 		= $postData['name'];
			$cctype 		= strtolower($postData['cctype']);
			$ccissueno 		= $postData['ccissueno'];
			$ccissuedatem 	= $postData['ccissuedatem'];
			$ccissuedatey 	= $postData['ccissuedatey'];
			$ccnum 			= $postData['ccno'];
			$ccexpm 		= $postData['ccexpm'];
			$ccexpy 		= $postData['ccexpy'];
			$cccvd 			= $postData['cccvd'];

			$timestamp = strftime("%Y%m%d%H%M%S");
			$amount = number_format($this->GetGatewayAmount()*100,0,'','');
			$account = $this->GetValue('account');

			$currency = GetDefaultCurrency();
			$currency = $currency['currencycode'];

			$orderid = $this->GetCombinedOrderId();

			$merchantid = $this->GetValue('merchantid');
			$merchantsecret = $this->GetValue('merchantsecret');

			$hash = sha1("$timestamp.$merchantid.$orderid.$amount.$currency.$ccnum");
			$hash = sha1("$hash.$merchantsecret");

			$order_desc = sprintf(GetLang('YourOrderFrom'), $GLOBALS['StoreName']);

			$xml = "<request type='auth' timestamp='$timestamp'>
						<merchantid>$merchantid</merchantid>";

			if (!empty($account)) {
				$xml .=		"<account>$account</account>";
			}

			$xml .= 	"<orderid>$orderid</orderid>
						<amount currency='$currency'>$amount</amount>
						<card>
							<number>$ccnum</number>
							<expdate>$ccexpm$ccexpy</expdate>
							<type>$cctype</type>
							<chname>$ccname</chname>
							";

			if ($this->GetValue("cardcode") == "YES") {
				$xml .=	"	<cvn>
								<number>$cccvd</number>
								<presind>1</presind>
							</cvn>";
			}

			$xml .="	</card>
						<autosettle flag='1' />
						<sha1hash>$hash</sha1hash>
					</request>";

			return $xml;
		}

		protected function _HandleResponse($result)
		{
			$result = preg_replace('|<([/\w]+)(:)|m','<$1',$result);
			$result = preg_replace('|(\w+)(:)(\w+=\")|m','$1$3',$result);

			try {
			  $xml = @new SimpleXMLElement($result);
			} catch (Exception $e) {

				// Something went wrong, show the error message with the credit card form
				$this->SetError(GetLang($this->_languagePrefix.'SomethingWentWrong'). $result);
				return false;
			}

			$responseCode = $responseMessage = '';

			if (isset($xml) && !empty($xml)) {
				$responseCode = (string)$xml->result;
				$responseMessage = (string)$xml->message;
				$pasref = (string)$xml->pasref;
			}

			if($responseCode == '00') {
				// The order is valid, hook back into the checkout system's validation process
				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

				$updatedOrder = array(
					'ordpayproviderid' => $pasref,
					'ordpaymentstatus' => 'captured',
				);

				$this->UpdateOrders($updatedOrder);

				return true;
			}
			else {
				// Something went wrong, show the error message with the credit card form
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Failure'), $responseCode, $responseMessage));
				$this->SetError(GetLang($this->_languagePrefix.'SomethingWentWrong').sprintf(" %s : %s", $responseCode, $responseMessage));
				return false;
			}
		}
	}