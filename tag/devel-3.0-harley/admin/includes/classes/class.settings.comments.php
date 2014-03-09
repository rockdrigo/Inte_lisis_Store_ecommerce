<?php
class ISC_ADMIN_SETTINGS_COMMENTS extends ISC_ADMIN_BASE
{
	public function HandleToDo($Do)
	{
		$GLOBALS["ISC_CLASS_ADMIN_ENGINE"]->LoadLangFile("settings.comments");

		$GLOBALS['BreadcrumEntries'] = array (
			GetLang('Home') => "index.php",
			GetLang('Settings') => "index.php?ToDo=viewSettings",
		);

		switch (strtolower($Do)) {
			case 'savecommentsystemsettings':
				$this->saveCommentSystemSettings();
				break;
			default:
				$this->manageCommentSystemSettings();
		}
	}

	private function manageCommentSystemSettings()
	{
		$GLOBALS['BreadcrumEntries'][GetLang('CommentSettingsTitle')] = '';
		$this->template->assign('Message', GetFlashMessageBoxes());

		$tabs = array('general' => GetLang('GeneralSettings'));
		$moduleTabContent = '';

		$systemModules = GetAvailableModules('comments');
		$commentSystems = array();
		foreach ($systemModules as $module) {
			$commentSystems[] = array('label' => $module['name'], 'value' => $module['id'], 'selected' => $module['enabled']);

			// add the module to the list of tabs so it can be configured
			if ($module['enabled']) {
				$tabs[$module['id']] = $module['name'];
				$moduleTabContent .= sprintf('<div id="%s" style="padding-top: 10px;" class="tabContent">%s</div>', $module['id'], $module['object']->GetPropertiesSheet($module['id']));
			}
		}

		$currentTab = 0;
		if (isset($_GET['tab'])) {
			$currentTab = $_GET['tab'];
		}
		$this->template->assign('currentTab', $currentTab);
		$this->template->assign('tabs', $tabs);
		$this->template->assign('commentSystems', $commentSystems);
		$this->template->assign('moduleTabContent', $moduleTabContent);

		$this->engine->PrintHeader();
		$this->template->display('settings.comments.manage.tpl');
		$this->engine->PrintFooter();
	}

	private function saveCommentSystemSettings()
	{
		$currentTab = $_POST['currentTab'];

		$enabledModule = '';

		if (isset($_POST['commentSystem'])) {
			$moduleid = $_POST['commentSystem'];
			GetModuleById('comments', $module, $moduleid);
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
				$enabledModule = $moduleid;
			}
		}

		// has the selected comment system changed?
		if (GetConfig('CommentSystemModule') != $enabledModule) {
			// activate the tab for the module
			$currentTab = '1';

			// enable all the types for this module
			$_POST[$enabledModule]['commenttypes'] = $module->getAvailableCommentTypes();

			// select all the pages for this module by default
			$_POST[$enabledModule]['pages'] = $this->getPageIds();
		}
		elseif (!$module->commentsEnabledForType(ISC_COMMENTS::PAGE_COMMENTS) && in_array(ISC_COMMENTS::PAGE_COMMENTS, $_POST[$enabledModule]['commenttypes'])) {
			// were page comments just enabled?
			$_POST[$enabledModule]['pages'] = $this->getPageIds();
		}

		$GLOBALS['ISC_NEW_CFG']['CommentSystemModule'] = $enabledModule;

		$settings = GetClass('ISC_ADMIN_SETTINGS');
		$messages = array();
		if ($settings->CommitSettings($messages)) {
			if (is_array($messages) && !empty($messages)) {
				foreach($messages as $message => $status) {
					FlashMessage($message, $status);
				}
			}

			// Delete existing module configuration
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('module_vars', "WHERE modulename LIKE 'comments\_%' AND MID(variablename, 1, 6) != 'setup_'");

			if ($enabledModule) {
				$vars = array();
				if(isset($_POST[$enabledModule])) {
					$vars = $_POST[$enabledModule];
				}

				GetModuleById('comments', $module, $enabledModule);
				$module->SaveModuleSettings($vars, false);
			}

			FlashMessage(GetLang('CommentSettingsSaved'), MSG_SUCCESS, 'index.php?ToDo=viewCommentSystemSettings&tab=' . $currentTab);
		}
		else {
			FlashMessage(GetLang('CommentSettingsNotSaved'), MSG_ERROR, 'index.php?ToDo=viewCommentSystemSettings&tab=' . $currentTab);
		}
	}

	/**
	* Returns an array of Id's for all pages
	*
	* @return array Array of page Id's
	*/
	private function getPageIds()
	{
		$nested = new ISC_NESTEDSET_PAGES();
		$pages = $nested->getTree(
			array('pageid')
		);
		$pageIds = array();
		foreach ($pages as $page) {
			$pageIds[] = $page['pageid'];
		}

		return $pageIds;
	}
}