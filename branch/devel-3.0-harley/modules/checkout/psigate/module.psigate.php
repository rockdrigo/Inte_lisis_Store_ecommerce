<?php

	class CHECKOUT_PSIGATE extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the PSIGate checkout module
			$this->_languagePrefix = "PSIGate";
			$this->_id = "checkout_psigate";
			$this->_image = "logo.gif";

			parent::__construct();

			$this->_requiresSSL = true;
			$this->_currenciesSupported = array('CAD', 'USD');

			$this->_liveTransactionURL = 'https://secure.psigate.com:7934';
			$this->_testTransactionURL = 'https://dev.psigate.com:7989';
			$this->_liveTransactionURI = '/Messenger/XMLMessenger';
			$this->_testTransactionURI = '/Messenger/XMLMessenger';
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

			$this->_variables['storeid'] = array("name" => GetLang($this->_languagePrefix.'StoreId'),
			   "type" => "textbox",
			   "help" => GetLang($this->_languagePrefix.'StoreIdHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['passphrase'] = array("name" => GetLang($this->_languagePrefix.'Passphrase'),
			   "type" => "password",
			   "help" => GetLang($this->_languagePrefix.'PassphraseHelp'),
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
					GetLang('TransactionTypeSale') => 0,
					GetLang('TransactionTypeAuthorize') => 1
				),
				"multiselect" => false
			);

			$this->_variables['cardcode'] = array(
				"name" => GetLang('RequireSecurityCode'),
				"type" => "dropdown",
				"help" => GetLang($this->_languagePrefix.'SecurityCodeHelp'),
				"default" => "no",
				"savedvalue" => array(),
				"required" => true,
				"options" => array(
					GetLang($this->_languagePrefix.'SecurityCodeNo') => "NO",
					GetLang($this->_languagePrefix.'SecurityCodeYes') => "YES"
				),
				"multiselect" => false
			);


			$this->_variables['testmode'] = array("name" => GetLang($this->_languagePrefix.'TestMode'),
			   "type" => "dropdown",
			   "help" => GetLang($this->_languagePrefix.'TestModeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(GetLang($this->_languagePrefix.'TestModeNo') => "NO",
							  GetLang($this->_languagePrefix.'TestModeYes') => "YES"
				),
				"multiselect" => false
			);
		}


		protected function _ConstructPostData($postData)
		{
			$transactionid = $this->GetCombinedOrderId();

			$ccname		= $postData['name'];
			$cctype		= $postData['cctype'];
			$ccissueno	= $postData['ccissueno'];
			$ccissuedatem	= $postData['ccissuedatem'];
			$ccissuedatey	= $postData['ccissuedatey'];
			$ccnum		= $postData['ccno'];
			$ccexpm		= $postData['ccexpm'];
			$ccexpy		= $postData['ccexpy'];
			$cccvd		= $postData['cccvd'];

			$bAddress = $this->GetBillingDetails();
			$billState = isc_html_escape($bAddress['ordbillstate']);
			if($bAddress['ordbillstateid'] != 0 && GetStateISO2ById($bAddress['ordbillstateid'])) {
				$billState = GetStateISO2ById($bAddress['ordbillstateid']);
			}
			$billStreet2 = '';
			if (isset($bAddress['ordbillstreet2'])) {
				$billStreet2 = $bAddress['ordbillstreet2'];
			}

			$sAddress = $this->getShippingAddress();
			$shipState = isc_html_escape($sAddress['state']);
			if($sAddress['state_id'] != 0 && GetStateISO2ById($sAddress['state_id'])) {
				$shipState = GetStateISO2ById($sAddress['state_id']);
			}
			$shipStreet2 = '';
			if (isset($sAddress['address_2'])) {
				$shipStreet2 = $sAddress['address_2'];
			}

			$query = "SELECT custconemail
				FROM [|PREFIX|]customers
				WHERE customerid='".$GLOBALS['ISC_CLASS_DB']->Quote($this->GetCustomerId())."'";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$email = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

			$xml  = '<Order>';
			$xml .= '<StoreID>'.$this->GetValue('storeid').'</StoreID>';
			$xml .= '<Passphrase>'.$this->GetValue('passphrase').'</Passphrase>';
			$xml .= '<Subtotal>'.$this->GetGatewayAmount().'</Subtotal>';
			$xml .= '<PaymentType>CC</PaymentType>';
			$xml .= '<CardAction>'.$this->GetValue('transactiontype').'</CardAction>';
			$xml .= '<CardNumber>'.htmlentities($ccnum).'</CardNumber>';
			$xml .= '<CardExpMonth>'.htmlentities($ccexpm).'</CardExpMonth>';
			$xml .= '<CardExpYear>'.htmlentities($ccexpy).'</CardExpYear>';
			$xml .= '<CardIDNumber>'.htmlentities($cccvd).'</CardIDNumber>';
			$xml .= '<OrderID>'.$GLOBALS['StoreName'].' ISC'.microtime(1).' '.$transactionid.'</OrderID>';

			// ISC-1094: added billing and shipping details
			$xml .= '<Email>'.$email.'</Email>';
			$xml .= '<CustomerIP>'.$this->GetIpAddress().'</CustomerIP>';

			$xml .= '<Bname>'.$bAddress['ordbillfirstname'].' '.$bAddress['ordbilllastname'].'</Bname>';
			$xml .= '<Baddress1>'.$bAddress['ordbillstreet1'].'</Baddress1>';
			$xml .= '<Baddress2>'.$billStreet2.'</Baddress2>';
			$xml .= '<Bcity>'.$bAddress['ordbillsuburb'].'</Bcity>';
			$xml .= '<Bprovince>'.$billState.'</Bprovince>';
			$xml .= '<Bpostalcode>'.$bAddress['ordbillzip'].'</Bpostalcode>';
			$xml .= '<Bcountry>'.$bAddress['ordbillcountrycode'].'</Bcountry>';

			$xml .= '<Sname>'.$sAddress['first_name'].' '.$sAddress['last_name'].'</Sname>';
			$xml .= '<Saddress1>'.$sAddress['address_1'].'</Saddress1>';
			$xml .= '<Saddress2>'.$shipStreet2.'</Saddress2>';
			$xml .= '<Scity>'.$sAddress['city'].'</Scity>';
			$xml .= '<Sprovince>'. $shipState.'</Sprovince>';
			$xml .= '<Spostalcode>'.$sAddress['zip'].'</Spostalcode>';
			$xml .= '<Scountry>'.$sAddress['country_iso2'].'</Scountry>';
			$xml .= '</Order>';

			return $xml;
		}

		protected function _HandleResponse($result)
		{
			if (empty($result)) {
				$this->SetError("There was an error communicating with the PSIGate server");
				return false;
			}

			try {
			  $xml = @new SimpleXMLElement($result);
			} catch (Exception $e) {

				// Something went wrong, show the error message with the credit card form
				$this->SetError(GetLang($this->_languagePrefix.'SomethingWentWrong'). $result);
				return false;
			}

			$responseCode = $responseMessage = '';

			if (isset($xml) && !empty($xml)) {
				$responseCode = (string)$xml->Approved;
				$responseMessage = (string)$xml->ErrMsg;
			}

			if($responseCode == 'APPROVED') {

				$amount = $xml->FullTotal;

				$trnId = (string)$xml->TransRefNumber;

				if ($this->GetGatewayAmount() != $amount) {
					$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'PaymentMismatch'), sprintf("Amount sent %s was not the same as the amount recieved %s", $this->GetGatewayAmount(), $amount));
					$this->SetError($this->_languagePrefix.'PaymentMismatch');
					return false;
				}

				$updatedOrder = array(
					'ordpayproviderid' => $trnId,
					'ordpaymentstatus' => 'captured'
				);

				$this->UpdateOrders($updatedOrder);


				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

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