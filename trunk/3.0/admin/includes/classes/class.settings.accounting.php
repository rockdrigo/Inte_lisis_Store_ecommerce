<?php
class ISC_ADMIN_SETTINGS_ACCOUNTING extends ISC_ADMIN_BASE
{
	/**
	 * Handle the action for this section.
	 *
	 * @param string The name of the action to do.
	 */
	public function HandleToDo($Do)
	{
		if (isset($_REQUEST["currentTab"])) {
			$GLOBALS["CurrentTab"] = (int)$_REQUEST["currentTab"];
		}
		else {
			$GLOBALS["CurrentTab"] = 0;
		}

		$GLOBALS["BreadcrumEntries"] = array (
			GetLang("Home") => "index.php",
			GetLang("Settings") => "index.php?ToDo=viewSettings",
			GetLang("AccountingSettings") => "index.php?ToDo=viewAccountingSettings"
		);

		if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Settings) || !gzte11(ISC_MEDIUMPRINT)) {
			$GLOBALS["ISC_CLASS_ADMIN_ENGINE"]->DoHomePage(GetLang("Unauthorized"), MSG_ERROR);
			return;
		}

		$GLOBALS["ISC_CLASS_ADMIN_ENGINE"]->LoadLangFile("settings.accounting");

		/**
		 * Load the language file
		 */
		$lang = "en";

		if (strpos(GetConfig("Language"), "/") === false) {
			$lang = GetConfig("Language");
		}

		ParseLangFile(ISC_BASE_PATH . "/modules/accounting/quickbooks/lang/" . $lang . "/language.ini");

		switch(isc_strtolower($Do))
		{
			case "viewaccountingsettings": {
				$GLOBALS["ISC_CLASS_ADMIN_ENGINE"]->PrintHeader();
				$this->ManageAccountingSettings();
				$GLOBALS["ISC_CLASS_ADMIN_ENGINE"]->PrintFooter();
				break;
			}
			case "saveupdatedaccountingsettings": {
				$GLOBALS["ISC_CLASS_ADMIN_ENGINE"]->PrintHeader();
				$this->SaveUpdatedAccountingSettings();
				$GLOBALS["ISC_CLASS_ADMIN_ENGINE"]->PrintFooter();
				break;
			}
			case "getfileaccountingsettings": {
				$this->getFileAccountingSettings();
				break;
			}
			default:
				if(!isset($_REQUEST['ajax'])) {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				}
				$this->ManageAccountingSettings();
				if(!isset($_REQUEST['ajax'])) {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				}
				break;
		}
	}


	public function ManageAccountingSettings($messages=array())
	{
		$GLOBALS['Message'] = GetFlashMessageBoxes();

		$GLOBALS['AccountingProviders'] = $this->_getAccountingPackagesAsOptions();

		// Which shipping modules are enabled?
		$accountings = GetAvailableModules('accounting', true, false);

		$GLOBALS['AccountingTabs'] = "";
		$GLOBALS['AccountingDivs'] = "";
		$GLOBALS['SSLIsConfigured'] = GetConfig('UseSSL');
		$count = 2;

		// Setup each shipping module with its own tab
		foreach ($accountings as $accounting) {
			$GLOBALS['AccountingTabs'] .= sprintf('<li><a href="#" id="tab%d" onclick="AdminAccountingSettings.showTab(%d)">%s</a></li>', $count, $count, $accounting['name']);
			$GLOBALS['AccountingDivs'] .= sprintf('<div id="div%d" style="padding-top: 10px;">%s</div>', $count, $accounting['object']->getpropertiessheet($count));

			$count++;
		}

		$this->template->display('settings.accounting.manage.tpl');
	}

	private function _getAccountingPackagesAsOptions()
	{
		// Get a list of all available accounting packages as <option> tags
		$packages = GetAvailableModules('accounting');
		$output = "";

		foreach ($packages as $package) {
			$sel = '';
			if($package['enabled']) {
				$sel = 'selected="selected"';
			}
			$output .= sprintf("<option %s value='%s'>%s</option>", $sel, $package['id'], $package['name']);
		}

		return $output;
	}

	public function SaveUpdatedAccountingSettings()
	{
		$originalAccountSettings = GetConfig('AccountingMethods');
		$enabledStack = array();
		$messages = array();

		// Can the selected payment modules be enabled?
		if (isset($_POST['accountingproviders']) && is_array($_POST['accountingproviders'])) {
			foreach ($_POST['accountingproviders'] as $moduleid) {
				GetModuleById('accounting', $module, $moduleid);
				if (is_object($module)) {
				// Is this checkout provider supported on this server?
					if($module->IsSupported() == false) {
						$errors = $module->GetErrors();
						foreach($errors as $error) {
							FlashMessage($error, MSG_ERROR);
						}

						return $this->ManageAccountingSettings();
					}

					// Otherwise, this accounting module is fine, so add it to the stack of enabled
					$enabledStack[] = $moduleid;
				}
			}
		}

		$accountingproviders = implode(",", $enabledStack);
		$GLOBALS['ISC_NEW_CFG']['AccountingMethods'] = $accountingproviders;

		$settings = GetClass('ISC_ADMIN_SETTINGS');
		$messages = array();
		if ($settings->CommitSettings($messages)) {
			if (is_array($messages) && !empty($messages)) {
				foreach($messages as $message => $status) {
					FlashMessage($message, $status);
				}
			}

			// Delete existing module configuration
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('module_vars', "WHERE modulename LIKE 'accounting\_%' AND MID(variablename, 1, 6) != 'setup_'");

			/**
			 * This next delete query is only needed for QuickBooks for clearing out the accounting spool of prerequisite items, orders, customers, and products.
			 */

			if(!isset($_POST['accountingproviders'])){
				/**
				 * Then we turned off all of the providers
				 */
				$GLOBALS['ISC_CLASS_DB']->DeleteQuery('accountingref', "WHERE accountingrefmoduleid LIKE 'accounting_quickbooks'");
			}else{
				/**
				 * We have at least one accounting provider active in the system. Is it QuickBooks or....
				 */

				$deleteSpool = 1;
				foreach($enabledStack as $provider){
					if($provider == 'accounting_quickbooks'){
						/**
						 * then QuickBooks is still active, so we need to change the flag so that we don't delete the spool.
						 */
						$deleteSpool = 0;
					}
				}

				if($deleteSpool == 1){
					/**
					 * Then we had an active accounting provider, but it wasn't QuickBooks. Delete the spool for Quickbooks.
					 */
					$GLOBALS['ISC_CLASS_DB']->DeleteQuery('accountingref', "WHERE accountingrefmoduleid LIKE 'accounting_quickbooks'");
				}
			}
			// Now get all accounting variables (they are in an array from $_POST)
			foreach($enabledStack as $module_id) {
				$vars = array();
				if(isset($_POST[$module_id])) {
					$vars = $_POST[$module_id];
				}

				GetModuleById('accounting', $module, $module_id);
				$module->SaveModuleSettings($vars, false);
			}

			/**
			 * Initialise our accounting modules. Only initialise the modules that were selected, not the modules
			 * that were already selected
			 */
			$old = explode(',', $originalAccountSettings);
			$new = explode(',', $accountingproviders);
			$initMods = array_diff($new, $old);

			if (is_array($initMods) && !empty($initMods)) {
				foreach ($initMods as $moduleId) {
					GetModuleById("accounting", $module, $moduleId);
					if (!is_object($module)) {
						continue;
					}

					if (!method_exists($module, "initModule")) {
						continue;
					}

					try {
						$module->initModule();
					} catch (Exception $e) {}
				}
			}

			$extraURL = array();
			$currentTabs = array();

			foreach ($new as $moduleId) {
				if (isset($_POST["currentTab" . $moduleId])) {
					$currentTabs[] = "currentTab" . $moduleId . "=" . urlencode($_POST["currentTab" . $moduleId]);
				}
			}

			$extraURL = implode("&", $currentTabs);

			$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateAccountingModuleVars();

			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();
			FlashMessage(GetLang('AccountingSettingsSavedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewAccountingSettings&currentTab=' . ((int) $_POST['currentTab']) . '&' . $extraURL);
		}
		else {
			FlashMessage(GetLang('AccountingSettingsNotSaved'), MSG_ERROR, 'index.php?ToDo=viewAccountingSettings&currentTab=' . ((int) $_POST['currentTab']));
		}
	}

	public function getFileAccountingSettings()
	{
		switch (strtolower(substr($_REQUEST["module"], 11))) {
			case "quickbooks": {
				switch (strtolower($_REQUEST["action"])) {
					case "qbwc": {
						GetModuleById("accounting", $module, $_REQUEST["module"]);

						$module->downloadQBWC();

						break;
					}
				}

				break;
			}
		}
	}
}