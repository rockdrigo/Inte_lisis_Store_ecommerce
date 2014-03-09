<?php

class ISC_ADMIN_SETTINGS_EMAILINTEGRATION extends ISC_ADMIN_BASE
{
	public function __construct()
	{
		parent::__construct();

		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('settings');
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('settings.emailintegration');

		include_once(ISC_BASE_PATH . '/lib/form.php');
		$GLOBALS['ISC_CLASS_FORM'] = new ISC_FORM();
	}

	/**
	 * Handle the action for this section.
	 *
	 * @param string The name of the action to do.
	 */
	public function HandleToDo($Do)
	{
		if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_EmailMarketing)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			return;
		}

		$this->template->assign('message', GetFlashMessageBoxes());
		$this->template->assign('javascript', '');

		$method = 'handle' . $_REQUEST['ToDo'];

		if ($method != 'handletodo' && is_callable(array($this, $method))) {
			$this->$method();
			return;
		}

		// default action
		$this->handleViewEmailIntegrationSettings();
	}

	/**
	* Displays the settings for this module type
	*
	* @return void
	*/
	protected function handleViewEmailIntegrationSettings()
	{
		if (isset($_REQUEST['currentTab'])) {
			$this->template->assign('tab', $_REQUEST['currentTab']);
		}

		$this->engine->addBreadcrumb(GetLang('EmailMarketing'), 'index.php?ToDo=viewEmailIntegrationSettings');

		$this->engine->stylesheets[] = 'Styles/settings.emailintegration.manage.css';
		$this->engine->bodyScripts[] = '../javascript/json2.js';
		$this->engine->bodyScripts[] = 'script/linker.js';
		$this->engine->bodyScripts[] = '../javascript/ajaxDataProvider.js';
		$this->engine->bodyScripts[] = 'script/emailintegration.js';
		$this->engine->bodyScripts[] = 'script/settings.emailintegration.manage.js';

		// full list of modules; for tabs, divs and js
		$modules = GetAvailableModules('emailintegration');

		// for visual purposes, place the export only module at the end
		foreach ($modules as $index => $module) {
			if ($module['id'] == 'emailintegration_exportonly') {
				array_splice($modules, $index, 1);
				$modules[] = $module;
				break;
			}
		}

		// flag to store whether or not there are selectable modules which are enabled -- used to show/hide some form elements
		$enabledSelectableModules = false;

		// add some data to the array returned by GetAvailableModules for display purposes
		foreach ($modules as &$module) {
			$module['provider'] = str_replace('emailintegration_', '', $module['id']);
		}
		unset($module);

		$this->template->assign('modules', $modules);

		// for the twig form builder; a list of selectable modules
		$selectableModules = array();
		foreach ($modules as $module) {
			if ($module['object']->isSelectable()) {
				$selectableModules[$module['id']] = $module['name'];
			}
		}
		$this->template->assign('selectableModules', $selectableModules);

		// for the twig form builder; a list of module tab id / labels and selected modules
		$tabs = array('modules' => GetLang('GeneralSettings'));
		$selectedModules = array();
		foreach ($modules as $module) {
			if (!$module['enabled']) {
				continue;
			}
			$tabs[$module['id']] = $module['name'];
			if ($module['object']->isSelectable()) {
				$enabledSelectableModules = true;
				$selectedModules[] = $module['id'];
			}

			if ($module['object']->getSettingsJavascript()) {
				$this->engine->bodyScripts[] = '../modules/emailintegration/' . $module['provider'] . '/javascript/' . $module['object']->getSettingsJavascript();
			}
		}

		$this->template->assign('enabledSelectableModules', $enabledSelectableModules);
		$this->template->assign('tabs', $tabs);
		$this->template->assign('selectedModules', $selectedModules);

		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
		$this->template->display('settings.emailintegration.manage.tpl');
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
	}

	/**
	* Validates and saves the settings for this module type
	*
	* @return void
	*/
	protected function handleSaveUpdatedEmailIntegrationSettings()
	{
		// make sure a valid array of modules has been posted
		if (isset($_POST['modules'])) {
			$modules = $_POST['modules'];
			if (!is_array($modules)) {
				$modules = array($modules);
			}
		} else {
			$modules = array();
		}

		// check general settings
		if (isset($_POST['ShowMailingListInvite'])) {
			$GLOBALS['ISC_NEW_CFG']['ShowMailingListInvite'] = (int)$_POST['ShowMailingListInvite'];
		}

		if (isset($_POST['MailAutomaticallyTickNewsletterBox'])) {
			$GLOBALS['ISC_NEW_CFG']['MailAutomaticallyTickNewsletterBox'] = (int)$_POST['MailAutomaticallyTickNewsletterBox'];
		}

		if (isset($_POST['newsletterDoubleOptIn'])) {
			$GLOBALS['ISC_NEW_CFG']['EmailIntegrationNewsletterDoubleOptin'] = (int)$_POST['newsletterDoubleOptIn'];
		}

		if (isset($_POST['newsletterSendWelcome'])) {
			$GLOBALS['ISC_NEW_CFG']['EmailIntegrationNewsletterSendWelcome'] = (int)$_POST['newsletterSendWelcome'];
		}

		if (isset($_POST['MailAutomaticallyTickOrderBox'])) {
			$GLOBALS['ISC_NEW_CFG']['MailAutomaticallyTickOrderBox'] = (int)$_POST['MailAutomaticallyTickOrderBox'];
		}

		if (isset($_POST['orderDoubleOptIn'])) {
			$GLOBALS['ISC_NEW_CFG']['EmailIntegrationOrderDoubleOptin'] = (int)$_POST['orderDoubleOptIn'];
		}

		if (isset($_POST['orderSendWelcome'])) {
			$GLOBALS['ISC_NEW_CFG']['EmailIntegrationOrderSendWelcome'] = (int)$_POST['orderSendWelcome'];
		}

		// check all posted modules
		$enabled = array();
		foreach ($modules as $moduleId) {
			if (!GetModuleById('emailintegration', /** @var ISC_EMAILINTEGRATION */$module, $moduleId)) {
				// not a valid module; skip
				continue;
			}

			if (!$module->IsSupported()) {
				// module reporting as not supported; pipe errors to the ui
				foreach ($module->GetErrors() as $error) {
					FlashMessage($error, MSG_ERROR);
				}
				continue;
			}

			// module enabled and supported, add its id to list of enabled modules
			$enabled[] = $moduleId;
		}

		// the new list of module ids is all we need to update the config with
		$GLOBALS['ISC_NEW_CFG']['EmailIntegrationMethods'] = implode(',', $enabled);

		/** @var ISC_ADMIN_SETTINGS */
		$settings = GetClass('ISC_ADMIN_SETTINGS');

		if (!$settings->CommitSettings($messages)) {
			FlashMessage(GetLang('EmailIntegrationSettingsNotSaved'), MSG_SUCCESS, 'index.php?ToDo=viewEmailIntegrationSettings');
			return;
		}

		foreach ($enabled as $moduleId) {
			if (isset($_POST[$moduleId])) {
				$vars = $_POST[$moduleId];
			} else {
				$vars = array();
			}

			GetModuleById('emailintegration', $module, $moduleId);
			$module->SaveModuleSettings($vars);
		}

		// Rebuild the cache of the email integration module variables
		$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateEmailIntegrationModuleVars();

		if ($GLOBALS['ISC_CLASS_DB']->Error() != "") {
			FlashMessage(GetLang('EmailIntegrationSettingsNotSaved'), MSG_SUCCESS, 'index.php?ToDo=viewEmailIntegrationSettings');
			return;
		}

		// Log this action
		$GLOBALS['ISC_CLASS_LOG']->LogAdminAction();
		$currentTab = '';
		if(isset($_REQUEST['currentTab'])) {
			$currentTab = '&currentTab=' . $_REQUEST['currentTab'];
		}

		FlashMessage(GetLang('EmailIntegrationSettingsSaved'), MSG_SUCCESS, 'index.php?ToDo=viewEmailIntegrationSettings'.$currentTab);
	}
}
