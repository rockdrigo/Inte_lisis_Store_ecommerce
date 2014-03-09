<?php

	class CHECKOUT_HSBC extends ISC_GENERIC_CREDITCARD
	{
		/*
			Checkout class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the Cybersouce checkout module

			$this->_languagePrefix = "HSBC";
			$this->_id = "checkout_hsbc";
			$this->_image = "hsbc_logo.gif";

			parent::__construct();

			$this->requiresSSL = true;
			$this->_currenciesSupported = array("GBP");
			$this->_cardsSupported = array ('VISA','AMEX','MC','DINERS','DISCOVER','SOLO','MAESTRO','SWITCH','LASER');

			$this->_liveTransactionURL = 'https://www.secure-epayments.apixml.hsbc.com';
			$this->_testTransactionURL = 'https://www.secure-epayments.apixml.hsbc.com';
			$this->_liveTransactionURI = '';
			$this->_testTransactionURI = '';
			$this->_curlSupported = true;
			$this->_fsocksSupported = true;
		}

		protected function _ConstructPostData($postData)
		{
			// XML to send
			$hsbc_xml = '<?xml version="1.0" encoding="UTF-8" ?><EngineDocList>
							<DocVersion DataType="String">1.0</DocVersion>
								<EngineDoc>
									<ContentType DataType="String">OrderFormDoc</ContentType>
									<User>
										<ClientId DataType="S32">'.$this->GetValue("clientid").'</ClientId>
										<Name DataType="String">'.$this->GetValue("vendorname").'</Name>
										<Password DataType="String">'.$this->GetValue("vendorpassword").'</Password>
									</User>
									<Instructions>
										<Pipeline DataType="String">PaymentNoFraud</Pipeline>
									</Instructions>
									<OrderFormDoc>';

			if($this->_testmode == "YES") {
				$hsbc_xml .= '			<Mode DataType="String">Y</Mode>';
			}
			else {
				$hsbc_xml .= '			<Mode DataType="String">P</Mode>';
			}

			$hsbc_xml .= '
										<Consumer>
											<PaymentMech>
												<Type DataType="String">CreditCard</Type>
												<CreditCard>
													<Number DataType="String">' . htmlentities($postData['ccno']) .'</Number>
													<Cvv2Val DataType="String">' . htmlentities($postData['cccvd']) . '</Cvv2Val>';

			$hsbc_xml .= '							<Cvv2Indicator DataType="String">';

			if (!empty($postData['cccvd'])) {
				$hsbc_xml .= '1';
			}
			else {
				$hsbc_xml .= '2';
			}

			$hsbc_xml .= '							</Cvv2Indicator>
													<Expires DataType="ExpirationDate">'.htmlentities($postData['ccexpm']."/".$postData['ccexpy']).'</Expires>';

			if ($this->CardTypeHasIssueDate($postData['cctype'])) {
				$hsbc_xml .= '						<StartDate DataType="StartDate">'.htmlentities($postData['ccissuedatem'].$postData['ccissuedatey']).'</StartDate>';
			}

			if ($this->CardTypeHasIssueNo($postData['cctype'])) {
				$hsbc_xml .= '						<IssueNum>'.htmlentities($postData['ccissueno']).'</IssueNum>';
			}

			$hsbc_xml .= '						</CreditCard>
											</PaymentMech>
										</Consumer>
										<Transaction>
											<Type DataType="String">Auth</Type>
											<CurrentTotals>
												<Totals>
													<Total DataType="Money" Currency="826">'.($this->GetGatewayAmount()*100).'</Total>
												</Totals>
											</CurrentTotals>
										</Transaction>
									</OrderFormDoc>
								</EngineDoc>
							</EngineDocList>';



			return $hsbc_xml;
		}

		protected function _HandleResponse($result)
		{
			$xml = new SimpleXMLElement($result);

			if (empty($xml)) {
				return false;
			}

			$approved = in_array((string)$xml->EngineDoc->Overview->TransactionStatus, array('A', 'C'));
			$trnId = $xml->EngineDoc->Overview->TransactionId;

			if (isset($xml) && !empty($xml)) {
				$responseMessage = (string)$xml->EngineDoc->MessageList->Message->Text;
			}

			if($approved) {

				$updatedOrder = array(
					'ordpayproviderid' => (string)$trnId,
					'ordpaymentstatus' => 'captured'
				);

				$this->UpdateOrders($updatedOrder);

				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('payment', $this->GetName()), GetLang($this->_languagePrefix.'Success'));

				return true;
			}
			else {

				// Something went wrong, show the error message with the credit card form
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', $this->GetName()), sprintf(GetLang($this->_languagePrefix.'Failure'), '', $responseMessage));
				$this->SetError(GetLang($this->_languagePrefix."SomethingWentWrong").sprintf(": %s", $responseMessage));
				return false;
			}
		}

		/**
		* Custom variables for the checkout module. Custom variables are stored in the following format:
		* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
		* variable_type types are: text,number,password,radio,dropdown
		* variable_options is used when the variable type is radio or dropdown and is a name/value array.
		*/
		public function SetCustomVars()
		{
			$this->_variables['displayname'] = array("name" => GetLang($this->_languagePrefix."DisplayName"),
			   "type" => "textbox",
			   "help" => GetLang('DisplayNameHelp'),
			   "default" => $this->GetName(),
			   "required" => true
			);

			$this->_variables['vendorname'] = array("name" => GetLang($this->_languagePrefix."VendorName"),
			   "type" => "textbox",
			   "help" => GetLang('HSBCVendorNameHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['vendorpassword'] = array("name" => GetLang($this->_languagePrefix."VendorPassword"),
			   "type" => "password",
			   "help" => GetLang('HSBCVendorPasswordHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['clientid'] = array("name" => GetLang($this->_languagePrefix."ClientId"),
			   "type" => "textbox",
			   "help" => GetLang('HSBCVendorClientIdHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['cardcode'] = array("name" => GetLang($this->_languagePrefix."CardCode"),
			   "type" => "dropdown",
			   "help" => GetLang('HSBCCardCodeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(GetLang('HSBCCardCodeNo') => "NO",
							  GetLang('HSBCCardCodeYes') => "YES"
				),
				"multiselect" => false
			);

			$this->_variables['testmode'] = array("name" => GetLang($this->_languagePrefix."TestMode"),
			   "type" => "dropdown",
			   "help" => GetLang('HSBCTestModeHelp'),
			   "default" => "no",
			   "required" => true,
			   "options" => array(GetLang('HSBCTestModeNo') => "NO",
							  GetLang('HSBCTestModeYes') => "YES"
				),
				"multiselect" => false
			);
		}
	}