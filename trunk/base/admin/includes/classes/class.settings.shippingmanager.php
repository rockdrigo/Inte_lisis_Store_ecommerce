<?php
class ISC_ADMIN_SETTINGS_SHIPPINGMANAGER extends ISC_ADMIN_BASE
{
	public function HandleToDo($Do)
	{
		if (!$this->auth->HasPermission(AUTH_Manage_Orders)) {
			$this->engine->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
		}

		$this->engine->LoadLangFile('settings.shippingmanager');

		$GLOBALS['BreadcrumEntries'] = array (
			GetLang('Home') => "index.php",
			GetLang('Settings') => "index.php?ToDo=viewSettings",
		);

		switch (strtolower($Do)) {
			case 'saveshippingmanagersettings':
				$this->saveShippingManagerSettings();
				break;
			default:
				$this->manageShippingManagerSettings();
		}
	}

	private function manageShippingManagerSettings()
	{
		$GLOBALS['BreadcrumEntries'][GetLang('ShippingManagerSettings')] = '';
		$this->template->assign('Message', GetFlashMessageBoxes());

		$tabs = array('general' => GetLang('GeneralSettings'));
		$moduleTabContent = '';

		$managerModules = GetAvailableModules('shippingmanager');

		$shippingManagers = array();
		$enabledShippingManagers = array();

		foreach ($managerModules as $module) {
			$shippingManagers[$module['id']] = $module['name'];

			// add the module to the list of tabs so it can be configured
			if ($module['enabled']) {
				$tabs[$module['id'] ] = $module['name'];
				$moduleTabContent .= sprintf('<div id="%s" style="padding-top: 10px;" class="tabContent">%s</div>', $module['id'], $module['object']->GetPropertiesSheet($module['id']));
				$enabledShippingManagers[] = $module['id'];
			}
		}

		$currentTab = 'general';
		if (isset($_GET['tab'])) {
			$currentTab = $_GET['tab'];
		}
		$this->template->assign('currentTab', $currentTab);

		$this->template->assign('tabs', $tabs);
		$this->template->assign('shippingManagers', $shippingManagers);
		$this->template->assign('enabledShippingManagers', $enabledShippingManagers);
		$this->template->assign('moduleTabContent', $moduleTabContent);

		$this->engine->PrintHeader();
		$this->template->display('settings.shippingmanager.manage.tpl');
		$this->engine->PrintFooter();
	}

	private function saveShippingManagerSettings()
	{
		$currentTab = $_POST['currentTab'];

		$enabledStack = array();

		if (isset($_POST['shippingManagers'])) {
			foreach ($_POST['shippingManagers'] as $moduleid) {
				GetModuleById('shippingmanager', $module, $moduleid);
				if (is_object($module)) {
					// Is this shipping manager supported on this server?
					if($module->IsSupported() == false) {
						$errors = $module->GetErrors();
						foreach($errors as $error) {
							FlashMessage($error, MSG_ERROR);
						}

						$this->manageShippingManagerSettings();
					}

					// Otherwise, this manager module is fine, so add it to the stack of enabled
					$enabledStack[] = $moduleid;
				}
			}
		}

		$shippingManagers = implode(",", $enabledStack);
		$GLOBALS['ISC_NEW_CFG']['ShippingManagerModules'] = $shippingManagers;

		$settings = GetClass('ISC_ADMIN_SETTINGS');
		$messages = array();
		if ($settings->CommitSettings($messages)) {
			if (is_array($messages) && !empty($messages)) {
				foreach($messages as $message => $status) {
					FlashMessage($message, $status);
				}
			}

			// Delete existing module configuration
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('module_vars', "WHERE modulename LIKE 'shippingmanager\_%' AND MID(variablename, 1, 6) != 'setup_'");

			// Now get all variables (they are in an array from $_POST)
			foreach($enabledStack as $module_id) {
				$vars = array();
				if(isset($_POST[$module_id])) {
					$vars = $_POST[$module_id];
				}

				GetModuleById('shippingmanager', $module, $module_id);
				$module->SaveModuleSettings($vars, false);
			}

			FlashMessage(GetLang('ShippingManagerSettingsSaved'), MSG_SUCCESS, 'index.php?ToDo=viewShippingManagerSettings&tab=' . $currentTab);
		}
		else {
			FlashMessage(GetLang('ShippingManagerSettingsNotSaved'), MSG_ERROR, 'index.php?ToDo=viewShippingManagerSettings&tab=' . $currentTab);
		}
	}
}