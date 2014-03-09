<?php

	CLASS ISC_CREATEACCOUNTFORM_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			if(IsCheckingOut()) {
				$GLOBALS['CreateAccountHeading'] = GetLang("CreateAnAccount");
				$GLOBALS['CreateAccountButtonText'] = GetLang("ContinueRaquo");
				$_SESSION['IsCheckingOut'] = true;
			}
			else {
				$GLOBALS['CreateAccountHeading'] = GetLang("CreateAnAccount");
				$GLOBALS['CreateAccountButtonText'] = GetLang("CreateMyAccount");
			}
		}
	}