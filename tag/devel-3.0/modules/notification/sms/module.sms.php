<?php

	class NOTIFICATION_SMS extends ISC_NOTIFICATION
	{

		private $_username = "";
		private $_password = "";
		private $_cellnumber = 0;
		private $_message = "";

		private $testMode = false;

		/*
			Notification class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the module
			parent::__construct();
			$this->_name = GetLang('SMSName');
			$this->_description = GetLang('SMSDesc');
			$this->_help = GetLang('SMSHelp');
		}

		public function LECheck()
		{
			if(!gzte11(ISC_MEDIUMPRINT)) {
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

			$this->_variables['username'] = array("name" => "SMSGlobal Username",
			   "type" => "textbox",
			   "help" => GetLang('SMSGlobalUsernameHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['password'] = array("name" => "SMSGlobal Password",
			   "type" => "password",
			   "help" => GetLang('SMSGlobalPasswordHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['cellnumber'] = array("name" => "Cell/Mobile Number",
			   "type" => "textbox",
			   "help" => GetLang('SMSCellNumberHelp'),
			   "default" => "",
			   "required" => true
			);
		}

		/**
		* Build and format the message to be sent
		*/
		private function BuildSmsMessage()
		{
			if($this->testMode == true) {
				return urlencode('SMSMessageTest');
			}
			else {
				$message = sprintf(GetLang('SMSMessageContents'), $this->_orderid, $GLOBALS['StoreName'], $this->_ordernumitems, FormatPrice($this->_ordertotal, false, true, false, GetDefaultCurrency()), $this->_orderpaymentmethod);
				return urlencode($message);
			}
		}

		/**
		* Send the order notification SMS text message
		*/
		public function SendNotification()
		{

			// Load up the variables for the SMS gateway
			$this->_username = $this->GetValue("username");
			$this->_password = $this->GetValue("password");
			$this->_cellnumber = $this->GetValue("cellnumber");
			$this->_message = $this->BuildSmsMessage();

			$sms_url = sprintf("http://www.smsglobal.com.au/http-api.php?action=sendsms&user=%s&password=%s&from=%s&to=%s&clientcharset=UTF-8&text=%s", $this->_username, $this->_password, $this->_cellnumber, $this->_cellnumber, urlencode($this->_message));

			// Let's try to send the message
			$result = PostToRemoteFileAndGetResponse($sms_url, '', 5);

			if(is_numeric(isc_strpos($result, "OK"))) {
				$result = array("outcome" => "success",
								"message" => sprintf(GetLang('SMSNotificationSentNumber'), $this->_cellnumber)
				);
			}
			else {
				// The message couldn't be sent. Do they have enough credit?
				$low_balance = false;
				$bal_url = sprintf("http://www.smsglobal.com.au/http-api.php?action=balancesms&user=%s&password=%s", $this->_username, $this->_password);
				$bal_result = PostToRemoteFileAndGetResponse($bal_url, '', 5);

				// SMSGlobal returns the balance in the format: BALANCE: 0.0999999; USER: johndoe
				$bal_data = explode(";", $bal_result);

				if(is_array($bal_data) && count($bal_data) > 1) {
					$bal_data_1 = explode(":", $bal_data[0]);

					if(is_array($bal_data_1)) {
						$balance = floor((int)trim($bal_data_1[1]));

						if($balance == 0) {
							$low_balance = true;
						}
					}
				}

				if($low_balance) {
					$error_message = GetLang('SMSZeroBalance');
				}
				else {
					$error_message = $bal_result;
				}

				$result = array("outcome" => "fail",
								"message" => $error_message
				);
			}

			return $result;
		}

		/**
		* Test the notification method by displaying a simple HTML form
		*/
		public function TestNotificationForm()
		{

			// Set the values that will be sent in the test message
			$this->testMode = true;

			// Send the SMS message
			$result = $this->SendNotification();

			if($result['outcome'] == "success") {
				$GLOBALS['Icon'] = "success";
				$GLOBALS['SMSResultMessage'] = sprintf(GetLang('SMSTestSuccess'), $this->_cellnumber);
			}
			else {
				$GLOBALS['Icon'] = "error";
				$GLOBALS['SMSResultMessage'] = sprintf(GetLang('SMSTestFail'), $this->_cellnumber, $result['message']);
			}

			$this->ParseTemplate("module.sms.test");
		}
	}