<?php
class ISC_ADMIN_SETTINGS_LIVECHAT extends ISC_ADMIN_BASE
{
	/**
		* Handle the action for this section.
		*
		* @param string The name of the action to do.
		*/
	public function HandleToDo($Do)
	{
		if (isset($_REQUEST['currentTab'])) {
			$GLOBALS['CurrentTab'] = (int)$_REQUEST['currentTab'];
		}
		else {
			$GLOBALS['CurrentTab'] = 0;
		}

		$GLOBALS['BreadcrumEntries'] = array (
			GetLang('Home') => "index.php",
			GetLang('Settings') => "index.php?ToDo=viewSettings",
			GetLang('LiveChatSettings') => "index.php?ToDo=viewLiveChatSettings"
			);

		if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Settings)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		switch(isc_strtolower($Do)) {
			case "saveupdatedlivechatsettings":
				$this->SaveUpdatedLiveChatSettings();
				break;
			case "livechatsettingscallback":
				$this->LiveChatSettingsCallback();
			case "viewlivechatsettings":
				$GLOBALS['BreadcrumEntries'][GetLang('LiveChatSettings')] = "index.php?ToDo=viewLiveChatSettings";
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->ManageLiveChatSettings();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				break;
		}
	}

	/**
	 * Perform a callback request to a live chat module.
	 */
	private function LiveChatSettingsCallback()
	{
		if(!isset($_REQUEST['module']) || !isset($_REQUEST['func'])) {
			exit;
		}

		$id = explode('_', $_REQUEST['module'], 2);
		GetModuleById('livechat', $module, $_REQUEST['module']);
		if(!is_object($module)) {
			exit;
		}

		if(!method_exists($module, $_REQUEST['func'].'Action')) {
			exit;
		}

		call_user_func(array($module, $_REQUEST['func'].'Action'));
		exit;
	}

	private function ManageLiveChatSettings()
	{
		$GLOBALS['Message'] = GetFlashMessageBoxes();

		$GLOBALS['LiveChatJavascript'] = '';
		$GLOBALS['LiveChatServices'] = $this->GetLiveChatServicesAsOptions();

		// Which live chat services are currently enabled?
		$liveChatServices = GetClass('ISC_LIVECHAT');
		$enabledServices = $liveChatServices->GetEnabledModules();

		$GLOBALS['LiveChatTabs'] = $GLOBALS['LiveChatDivs'] = '';
		$count = 2;

		// Set up each service with it's own tab
		foreach($enabledServices as $module) {
			$GLOBALS['LiveChatTabs'] .= '<li><a href="#" id="tab'.$count.'" onclick="ShowTab('.$count.');">'.$module['name']."</a></li>";
			$GLOBALS['LiveChatDivs'] .= '<div id="div'.$count.'" style="padding-top: 10px;">'.$module['object']->GetPropertiesSheet($count).'</div>';
			++$count;
		}

		$this->template->display('settings.livechat.manage.tpl');
	}

	/**
		* Save the updated live chat service integration settings.
		*/
	public function SaveUpdatedLiveChatSettings()
	{
		// Delete existing module configuration
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('module_vars', "WHERE modulename LIKE 'livechat\_%'");

		$enabledStack = array();
		$messages = array();

		// Can the selected live chat services be enabled?
		if (isset($_POST['livechatservices']) && is_array($_POST['livechatservices'])) {
			foreach ($_POST['livechatservices'] as $provider) {
				GetModuleById('livechat', $module, $provider);
				if (is_object($module)) {
					// Is this analytics provider supported on this server?
					if($module->IsSupported() == false) {
						$errors = $module->GetErrors();
						foreach($errors as $error) {
							FlashMessage($error, MSG_ERROR);
						}
						continue;
					}

					// Otherwise, this analytics provider is fine, so add it to the stack of enabled
					$enabledStack[] = $provider;
				}
			}
		}

		// Push everything to globals and save
		$GLOBALS['ISC_NEW_CFG']['LiveChatModules'] = implode(",", $enabledStack);
		$s = GetClass('ISC_ADMIN_SETTINGS');

		if ($s->CommitSettings($messages)) {
			// Now get all checkout variables (they are in an array from $_POST)
			foreach($enabledStack as $module_id) {
				$vars = array();
				if(isset($_POST[$module_id])) {
					$vars = $_POST[$module_id];
				}

				GetModuleById('livechat', $module, $module_id);
				$module->SaveModuleSettings($vars);
			}

			// Rebuild the cache of the analytics module variables
			$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateLiveChatModuleVars();

			if ($GLOBALS['ISC_CLASS_DB']->Error() == "") {
				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();
				$currentTab = '';
				if(isset($_REQUEST['currentTab'])) {
					$currentTab = '&currentTab='.(int)$_REQUEST['currentTab'];
				}
				FlashMessage(GetLang('LiveChatSettingsSavedSuccessfully'), MSG_SUCCESS, 'index.php?ToDo=viewLiveChatSettings'.$currentTab);
			}
			else {
				FlashMessage(GetLang('LiveChatSettingsNotSaved'), MSG_ERROR, 'index.php?ToDo=viewLiveChatSettings');
			}
		} else {
			FlashMessage(GetLang('LiveChatSettingsNotSaved'), MSG_ERROR, 'index.php?ToDo=viewLiveChatSettings');
		}
	}

	private function GetLiveChatServicesAsOptions()
	{
		$modules = GetAvailableModules('livechat');
		$output = "";
		foreach($modules as $package) {
			$sel = '';
			if($package['enabled']) {
				$sel = 'selected="selected"';
			}
			$output .= "<option value='".$package['id']."' ".$sel.">".$package['name']."</option>";
		}
		return $output;
	}
}
