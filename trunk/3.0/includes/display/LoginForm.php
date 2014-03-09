<?php

	CLASS ISC_LOGINFORM_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			if(IsCheckingOut()) {
				// Show messages specific to checking out
				$GLOBALS['NewAccountHeading'] = GetLang("NewCustomer");
				$GLOBALS['ExistingUserHeading'] = GetLang("ReturningCustomer");
				$GLOBALS['LoginMessage'] = GetLang('SignInForFastCheckout');
				$GLOBALS['HideLoginNewAccountIntro'] = "none";
			}
			else {
				// Show the default messages on the login screen
				$GLOBALS['NewAccountHeading'] = GetLang("CreateANewAccount");
				$GLOBALS['ExistingUserHeading'] = GetLang("LoginToYourAccount");
				$GLOBALS['HideNewCustomerButton'] = "none";
			}
		}
	}