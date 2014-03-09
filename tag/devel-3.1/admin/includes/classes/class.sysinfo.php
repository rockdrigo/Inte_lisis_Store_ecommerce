<?php
	class ISC_ADMIN_SYSINFO extends ISC_ADMIN_BASE
	{
		public function HandleToDo($Do)
		{
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('sysinfo');
			if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_System_Info) || GetConfig('DisableSystemInfo')) {
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			}

			switch(strtolower($Do)) {
				case "phpsysteminfo":
					phpinfo();
					exit;
				default:
					$GLOBALS['BreadcrumEntries'] = array (
						$GLOBALS['ISC_LANG']['Home'] => "index.php",
						$GLOBALS['ISC_LANG']['SystemInfo'] => "index.php?ToDo=systemInfo"
					);
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					$this->ShowSystemInfo();
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
			}
		}

		private function ShowSystemInfo()
		{
			// Server Information
			$GLOBALS['ProductVersion'] = isc_html_escape(PRODUCT_VERSION);
			$GLOBALS['PHPVersion'] = isc_html_escape(phpversion());
			$GLOBALS['MySQLVersion'] = isc_html_escape(mysql_get_server_info());
			$GLOBALS['ServerSoftware'] = isc_html_escape($_SERVER['SERVER_SOFTWARE']);

			if(GetConfig('DisableSystemInfoEdition')) {
				$GLOBALS['HideEdition'] = 'display: none';
			}
			else {
				if(isset($GLOBALS['ProductEditionUpgrade'])) {
					$GLOBALS['ProductEdition'] .= " (<a href='".GetConfig('SystemInfoEditionUpgradeLink')."' target='_blank'>Upgrade</a>)";
				}
			}

			if (GDEnabled()) {
				$php_mods = parsePHPModules();
				$GLOBALS['GDVersion'] = isc_html_escape($php_mods['gd']['GD Version']);
			} else {
				$GLOBALS['GDVersion'] = GetLang('GDMissing');
			}

			if((bool)ini_get('safe_mode') == true) {
				$GLOBALS['SafeMode'] = GetLang('Enabled');
			}
			else {
				$GLOBALS['SafeMode'] = GetLang('Disabled');
			}

			$GLOBALS['MultiByteFunctions'] = array();
			if(function_exists("mb_strpos")) {
				$GLOBALS['MultiByteFunctions'][] = "Multibyte";
			}

			if(function_exists("iconv_strpos")) {
				$GLOBALS['MultiByteFunctions'][] = "iconv";
			}
			$GLOBALS['MultiByteFunctions'] = implode("<br />", $GLOBALS['MultiByteFunctions']);

			if(!$GLOBALS['MultiByteFunctions']) {
				$GLOBALS['MultiByteFunctions'] = GetLang('NotSupported');
			}

			$GLOBALS['RemoteConnections'] = array();
			if(function_exists("curl_init")) {
				$GLOBALS['RemoteConnections'][] = "CURL";
			}

			if(!(bool)ini_get('safe_mode') && ini_get('allow_url_fopen')) {
				$GLOBALS['RemoteConnections'][] = GetLang('RemoteFOpen');
			}
			$GLOBALS['RemoteConnections'] = implode("<br />", $GLOBALS['RemoteConnections']);

			if(!$GLOBALS['RemoteConnections']) {
				$GLOBALS['RemoteConnections'] = GetLang('NoneSupported');
			}

			if(function_exists('pspell_suggest')) {
				$GLOBALS['PSpell'] = GetLang('Enabled');
			}
			else {
				$GLOBALS['PSpell'] = GetLang('NotSupported');
			}

			$GLOBALS['OperatingSystem'] = isc_html_escape(php_uname());

			$this->template->display('sysinfo.tpl');
		}
	}