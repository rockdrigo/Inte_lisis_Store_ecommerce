<?php

	// Require the libraries to connect to the MSN Messenger servers
	include_once(ISC_BASE_PATH."/includes/msn/sendMsg.php");

	class NOTIFICATION_MSN extends ISC_NOTIFICATION
	{

		private $_msnfromuser = "";
		private $_msnfrompass = "";
		private $_msntouser = "";

		private $_message = "";

		private $testMode = false;

		/*
			Notification class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the module
			parent::__construct();

			$this->_name = GetLang('MSNName');
			$this->_description = GetLang('MSNDesc');
			$this->_help = GetLang('MSNHelp');
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

			$this->_variables['msnfrom'] = array("name" => GetLang('MSNUsernameFrom'),
			   "type" => "textbox",
			   "help" => GetLang('MSNUsernameFromHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['msnpass'] = array("name" => GetLang('MSNPasswordFrom'),
			   "type" => "password",
			   "help" => GetLang('MSNPasswordFromHelp'),
			   "default" => "",
			   "required" => true
			);

			$this->_variables['msnto'] = array("name" => GetLang('MSNUsernameTo'),
			   "type" => "textbox",
			   "help" => GetLang('MSNUsernameToHelp'),
			   "default" => "",
			   "required" => true
			);
		}

		/**
		* Build and format the message to be sent
		*/
		private function BuildMsnMessage()
		{
			if($this->testMode) {
				return GetLang('MSNMessageTest');
			}
			else {
				$store_name = GetConfig('StoreName');
				$message = sprintf(GetLang('MSNMessageContents'), $this->_orderid, $store_name, $this->_ordernumitems, FormatPrice($this->_ordertotal, false, true, false, GetDefaultCurrency()), $this->_orderpaymentmethod, $GLOBALS['ShopPath'], $this->_orderid);
				return str_replace("{NL}", chr(10), $message);
			}
		}

		/**
		* Send the order notification MSN messenger message
		*/
		public function SendNotification()
		{

			$this->_msnfromuser = $this->GetValue("msnfrom");
			$this->_msnfrompass = $this->GetValue("msnpass");
			$this->_msntouser = $this->GetValue("msnto");
			$this->_message = $this->BuildMsnMessage();

			$sendMsg = new sendMsg();
			$sendMsg->simpleSend($this->_msnfromuser, $this->_msnfrompass, $this->_msntouser, $this->_message);

			// Convert the response to a friendly message
			if($this->_makefriendly($sendMsg->result, $err)) {
				$result = array("outcome" => "success",
								"message" => sprintf(GetLang('MSNSentUser'), $this->_msntouser)
				);
			}
			else {
				$result = array("outcome" => "fail",
								"message" => $err
				);
			}

			return $result;
		}

		/**
		* Convert the MSN status code to a human-readable mesage
		*/
		private function _makefriendly($msg, &$friendly)
		{
			switch($msg) {
				case "911": {
					$friendly = GetLang('MSNError911');
					break;
				}
				case "500":
				case "601":
				case "910":
				case "911":
				case "921":
				case "928":
				case "600": {
					$friendly = GetLang('MSNError600');
					return false;
					break;
				}
				case "217": {
					$friendly = GetLang('MSNError217');
					return false;
					break;
				}
				case "800": {
					$friendly = GetLang('MSNError800');
					return false;
					break;
				}
				case "1": {
					$friendly = GetLang('MSNError1');
					return true;
					break;
				}
				default: {
					$friendly = GetLang('MSNError999');
					return false;
					break;
				}
			}
		}

		/**
		* Test the notification method by displaying a simple HTML form
		*/
		public function TestNotificationForm()
		{

			// Set the values that will be sent in the test message
			$this->testMode = true;

			// Send the MSN message
			$result = $this->SendNotification();

			if($result['outcome'] == "success") {
				$GLOBALS['Icon'] = "success";
				$GLOBALS['MSNResultMessage'] = sprintf(GetLang('MSNTestSuccess'), $this->_msntouser);
			}
			else {
				$GLOBALS['Icon'] = "error";
				$GLOBALS['MSNResultMessage'] = sprintf(GetLang('MSNTestFail'), $this->_msntouser, $result['message']);
			}

			$this->ParseTemplate("module.msn.test");
		}
	}