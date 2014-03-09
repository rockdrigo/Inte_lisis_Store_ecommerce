<?php

	class NOTIFICATION_EMAIL extends ISC_NOTIFICATION
	{

		private $_email = "";
		private $_message = "";

		private $testMode = false;

		/*
			Notification class constructor
		*/
		public function __construct()
		{
			// Setup the required variables for the module
			parent::__construct();
			$this->_name = GetLang('NEmailName');
			$this->_description = GetLang('NEmailDesc');
			$this->_help = GetLang('NEmailHelp');
		}

		/**
		* Custom variables for the checkout module. Custom variables are stored in the following format:
		* array(variable_id, variable_name, variable_type, help_text, default_value, required, [variable_options], [multi_select], [multi_select_height])
		* variable_type types are: text,number,password,radio,dropdown
		* variable_options is used when the variable type is radio or dropdown and is a name/value array.
		*/
		public function SetCustomVars()
		{

			$this->_variables['emailaddress'] = array("name" => "Email Address",
			   "type" => "textbox",
			   "help" => GetLang('NEmailAddressHelp'),
			   "default" => "",
			   "required" => true
			);
		}

		/**
		* Build and format the message to be sent
		*/
		private function BuildEmailMessage()
		{

			$store_name = str_replace("&#39;", "'", $GLOBALS['StoreName']);
			if($this->testMode) {
				$testBanner = GetLang('NEmailTestBanner');
			}
			else {
				$testBanner = '';
			}
			$message = GetLang('NEmailMessageBody', array(
				'testBanner' => $testBanner,
				'shopName' => $store_name,
				'shopPath' => GetConfig('ShopPath'),
				'numItems' => $this->GetOrderNumItems(),
				'orderTotal' => FormatPrice($this->GetOrderTotal(), false, true, false, GetDefaultCurrency()),
				'orderId' => $this->GetOrderId(),
			));

			return $message;
		}

		/**
		* Send the order notification email
		*/
		public function SendNotification()
		{

			$emails = array();
			$this->_message = $this->BuildEmailMessage();
			$this->_email = $this->GetValue("emailaddress");

			if (empty($this->_email)) {
				return;
			}

			$emails = preg_split('#[,\s]+#si', $this->_email, -1, PREG_SPLIT_NO_EMPTY);

			// Create a new email object through which to send the email
			$store_name = GetConfig('StoreName');

			require_once(ISC_BASE_PATH . "/lib/email.php");
			$obj_email = GetEmailClass();
			$obj_email->Set('CharSet', GetConfig('CharacterSet'));
			$obj_email->From(GetConfig('OrderEmail'), $store_name);
			$obj_email->Set("Subject", sprintf(GetLang('NEmailSubjectLine'), $this->GetOrderId(), $store_name, FormatPrice($this->GetOrderTotal(), false, true, false, GetDefaultCurrency())));
			$obj_email->AddBody("html", $this->_message);

			// Add all recipients
			foreach($emails as $email) {
				$obj_email->AddRecipient($email, "", "h");
			}

			$email_result = $obj_email->Send();

			if($email_result['success']) {
				$result = array("outcome" => "success",
								"message" => sprintf(GetLang('EmailNotificationSentUser'), implode("<br />", $emails))
				);
			}
			else {
				$result = array("outcome" => "fail",
								"message" => GetLang('NEmailSendingFailed')
				);
			}

			return $result;
		}

		/**
		* Test the notification method by displaying a simple HTML form
		*/
		public function TestNotificationForm()
		{

			// Set some test variables
			$this->testMode = true;
			$this->SetOrderId(99999);
			$this->SetOrderTotal(139.50);
			$this->SetOrderNumItems(3);

			// Send the email message
			$result = $this->SendNotification();

			if($result['outcome'] == "success") {
				$GLOBALS['Icon'] = "success";

				// How many recipients was it sent to?
				if(is_numeric(isc_strpos($this->_email, ","))) {
					// There are multiple email addresses
					$tmp_emails = explode(",", $this->_email);
					$num_emails = count($tmp_emails);
					$success_msg = sprintf(GetLang('NEmailTestSuccessX'), $num_emails);
				}
				else {
					// Just one recipient
					$success_msg = GetLang('NEmailTestSuccess');
				}

				$GLOBALS['EmailResultMessage'] = sprintf($success_msg, $this->_email);
			}
			else {
				$GLOBALS['Icon'] = "error";
				$GLOBALS['EmailResultMessage'] = sprintf(GetLang('NEmailTestFail'), $this->_email, $result['message']);
			}

			$this->ParseTemplate("module.email.test");
		}
	}