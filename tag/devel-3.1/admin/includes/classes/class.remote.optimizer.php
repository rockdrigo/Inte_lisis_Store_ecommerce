<?php

if (!defined('ISC_BASE_PATH')) {
	die();
}

class ISC_ADMIN_REMOTE_OPTIMIZER extends ISC_ADMIN_REMOTE_BASE
{
	public function __construct()
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('optimizer');
		parent::__construct();
	}

	public function HandleToDo()
	{
		if (!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Website_Optimizer)) {
			return false;
		}
		$todo = isc_strtolower(@$_REQUEST['w']);
		switch ($todo) {
			case "showconfigform":
					$this->showConfigForm();
				break;
			case "saveconfigform":
					$this->saveConfigForm();
				break;
			case "installautoscripts":
					$this->installAutoScripts();
				break;
			case "resetmodule":
					$this->resetMoudle();
				break;
			case "downloadvalidationfiles":
					$this->downloadValidationFiles();
				break;
			case "getconversionpageurl":
					$this->getConversionPageUrl();
				break;
		}
	}


	private function installAutoScripts()
	{
		$errorMessage = '';
		if(!isset($_REQUEST['InstallUrl']) || $_REQUEST['InstallUrl'] == '') {
			//return invalid error message;
			$errorMessage = GetLang('EnterInstallUrl');
		}

		if($errorMessage != '') {
			$tags[] = $this->MakeXMLTag('status', 0);
			$tags[] = $this->MakeXMLTag('msg', $errorMessage);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}

		// adding &xml to the end of the URL that google provides on the website optimizer page to retrive the xml version of response.
		$path = $_REQUEST['InstallUrl']."&xml";

		$scripts = $this->retriveInstallScripts($path, $errorMessage);

		if($errorMessage != '') {
			$tags[] = $this->MakeXMLTag('status', 0);
			$tags[] = $this->MakeXMLTag('msg', $errorMessage);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		} else {
			$tags[] = $this->MakeXMLTag('status', 1);
			$tags[] = $this->MakeXMLTag('ConversionScript', isc_html_escape($scripts['goal-tracking-script']));

			$tags[] = $this->MakeXMLTag('ControlScript', isc_html_escape($scripts['control-script']));

			$tags[] = $this->MakeXMLTag('TrackingScript', isc_html_escape($scripts['test-tracking-script']));

			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}
	}

	/**
	* Retrive the GWO Scripts from Google and save in an array
	*
	* @param string $path, the Google install script path
	* @param string $errorMessage
	*
	* return array
	*/
	private function retriveInstallScripts($path, &$errorMessage)
	{
		$resultXml = PostToRemoteFileAndGetResponse($path);
		if(!$resultXml) {
			$errorMessage = GetLang('ProblemRetrivingScript');
			return array();
		}
		$result = @simplexml_load_string($resultXml);
		$result = (array) $result;

		$scripts = array();
		if(isset($result['install-scripts'])) {
			foreach($result['install-scripts'] as $scriptName => $script) {
				//if it's a shared ssl, it's a cross domain tracking
				if(GetConfig('UseSSL') == 2) {
					switch($scriptName) {
						case 'control-script':
							$script = '<script>_udn = "none";</script>'.$script;
							break;
						case 'test-tracking-script':
						case 'goal-tracking-script':
							$replaceString = 'gwoTracker._setDomainName("none");
gwoTracker._setAllowLinker(true);
gwoTracker._trackPageview';

							$script = str_replace('gwoTracker._trackPageview', $replaceString, $script);
							break;
					}
				}
				$scripts[$scriptName] = trim((string)$script);
			}
		}

		// check if the scripts we need are saved in the array,
		if(empty($scripts)) {
			$errorMessage = GetLang('ProblemRetrivingScript');
			return array();
		}
		return $scripts;
	}


	/**
	* save a store-wide GWO configure data
	*
	* @param array $moduleData
	* @param string $errorMessage
	*
	* return bool
	*/
	private function saveModuleData($moduleData, &$errorMessage)
	{
		if (empty($moduleData)) {
			$errorMessage = GetLang("ProblemWhenSavingScripts");
			return false;
		}

		$query = "Select variablename from [|PREFIX|]module_vars where modulename='".$GLOBALS['ISC_CLASS_DB']->Quote($moduleData['module_id'])."'";

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$existingVars = array();
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$existingVars[] = $row['variablename'];
		}
		foreach($moduleData as $varName => $data) {

			if(in_array($varName, $existingVars)) {
				$updateScript = array(
					'variableval' => $data,
				);

				$GLOBALS['ISC_CLASS_DB']->UpdateQuery("module_vars", $updateScript, "modulename='".$GLOBALS['ISC_CLASS_DB']->Quote($moduleData['module_id'])."' and variablename='".$GLOBALS['ISC_CLASS_DB']->Quote($varName)."'", true);

			} else {
				$insertScript = array(
					'modulename' => $moduleData['module_id'],
					'variablename' => $varName,
					'variableval' => $data,
				);

				$GLOBALS['ISC_CLASS_DB']->InsertQuery("module_vars", $insertScript, true);
			}
		}

		//save optimizer data in cache for the use of store front code
		if($GLOBALS['ISC_CLASS_DATA_STORE']->UpdateOptimizerData() === false) {
			$errorMessage = GetLang("ProblemWhenSavingScripts");
			return false;
		}
	}

	/**
	* display the store-wide GWO config form
	*
	*/
	private function showConfigForm()
	{
		GetModuleById('optimizer', $optimizerModule, $_REQUEST['moduleId']);

		$ModuleDetails = $optimizerModule->GetModuleDetails();
		$GLOBALS['ValidateTestPageUrl'] = $optimizerModule->getTestPageUrl();
		$optimizerName = str_replace('optimizer_','',$_REQUEST['moduleId']);

		$GLOBALS['ValidateConversionPageUrl'] = $optimizerModule->getConversionValidateUrl();

		//variation page url for AB testings
		$GLOBALS['HideVariationPageUrl'] = 'display:none;';
		if($optimizerModule->_testType == 'AB') {
			$GLOBALS['ValidateVariationPageUrl'] = $optimizerModule->getVariationPageUrl();
			$GLOBALS['HideVariationPageUrl'] = '';
			$GLOBALS['TestType'] = GetLang('ABExperiment');
			$GLOBALS['TestPageURLLabel'] = GetLang('OriginalPageUrl');
		} else {
			$GLOBALS['TestType'] = GetLang('MultivariateExperiment');
			$GLOBALS['TestPageURLLabel'] = GetLang('TestPageUrl');

		}


		// conversion url input for custom conversion page
		$GLOBALS['HideCustomConversionHelp'] = 'display:none;';
		$GLOBALS['ConversionPageURL'] = 'http://';

		if(isset($ModuleDetails['conversion_page'])) {
			if($ModuleDetails['conversion_page'] == 'Custom') {
				$GLOBALS['HideCustomConversionHelp'] = '';
			}

			if(isset($ModuleDetails['conversion_url']) && $ModuleDetails['conversion_url'] != '') {
				$GLOBALS['ConversionPageURL'] = isc_html_escape($ModuleDetails['conversion_url']);
			}
		}


		$GLOBALS['ConversionPageOptions'] = '';
		$conversionPages = $optimizerModule->conversionPages;
		$conversionPages['Custom'] = '';
		foreach ($conversionPages as $page => $url) {

			$selectOption = '';
			if(isset($ModuleDetails['conversion_page']) && $ModuleDetails['conversion_page'] == $page) {
				$selectOption = 'Selected=Selected';
			} else if(!isset($ModuleDetails['conversion_page'])) {
				if($_REQUEST['moduleId'] == 'optimizer_newsletterbox') {
					if(strtolower($page) == 'newsletter') {
						$selectOption = 'Selected=Selected';
					}
				} else if($page == 'Order') {
					$selectOption = 'Selected=Selected';
				}
			}

			$GLOBALS['ConversionPageOptions'] .= "<option value='".$page."' ".$selectOption.">".GetLang('ConversionPage'.$page)."</option>";
		}


		if(isset($ModuleDetails['control_script'])) {
			$GLOBALS['ControlScript'] = isc_html_escape($ModuleDetails['control_script']);
		}

		if(isset($ModuleDetails['tracking_script'])) {
			$GLOBALS['TrackingScript'] = isc_html_escape($ModuleDetails['tracking_script']);
		}

		if(isset($ModuleDetails['conversion_script'])) {
			$GLOBALS['ConversionScript'] = isc_html_escape($ModuleDetails['conversion_script']);
		}

		$GLOBALS['ModuleName'] = isc_html_escape($optimizerModule->GetName());
		$GLOBALS['Help'] = $optimizerModule->_help;
		$GLOBALS['ModuleId'] = isc_html_escape($optimizerModule->GetId());
		echo $this->template->render('Snippets/OptimizerConfigFormStorewide.html');
		exit;
	}

	/**
	* save the store-wide GWO config form and update the config and cache file
	*/
	private function saveConfigForm()
	{
		$errorMessage = '';

		$optimizerClass = getClass('ISC_ADMIN_OPTIMIZER');

		$errorMessage = $optimizerClass->validateConfigForm();

		if(!isset($_REQUEST['moduleId']) || $_REQUEST['moduleId'] == '') {
			$errorMessage = GetLang('MissingModuleId');
		} else if(!GetModuleById('optimizer', $optimizerModule, $_REQUEST['moduleId'])) {
			$errorMessage = GetLang('InvalidModuleId');
		}

		if($errorMessage != '') {
			$tags[] = $this->MakeXMLTag('status', 0);
			$tags[] = $this->MakeXMLTag('msg', $errorMessage);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}

		$moduleId = $_REQUEST['moduleId'];
		$configDate = time();
		$moduleData = array(
			'config_date'		=> $configDate,
			'conversion_page'	=> $_REQUEST['ConversionPage'],
			'module_id'			=> $moduleId,
			'tracking_script'	=> $_REQUEST['TrackingScript'],
			'conversion_script' => $_REQUEST['ConversionScript'],
			'control_script'	=> $_REQUEST['ControlScript'],
			'conversion_url'	=> $_REQUEST['ConversionPageUrl'],
		);

		if(isset($_REQUEST['CustomConversionUrl']) && $_REQUEST['CustomConversionUrl'] != '') {
			$moduleData['custom_conversion_url'] = $_REQUEST['CustomConversionUrl'];
		}

		$this->saveModuleData($moduleData, $errorMessage);


		//update config.php
		$enabledModules = GetConfig('OptimizerMethods');
		if (!isset($enabledModules[$moduleId])) {
			$enableModules = array_merge($enabledModules, array($moduleId=>$configDate));
			$GLOBALS['ISC_NEW_CFG']['OptimizerMethods'] = $enableModules;
		}

		$settings = GetClass('ISC_ADMIN_SETTINGS');
		if (!$settings->CommitSettings()) {
			$errorMessage = GetLang('ConfigFileUpdateFail');
		}


		if($errorMessage != '') {
			$tags[] = $this->MakeXMLTag('status', 0);
			$tags[] = $this->MakeXMLTag('msg', $errorMessage);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		} else {

			$message = isc_html_escape(sprintf(GetLang('ConfigFormSavedSuccess'), $optimizerModule->GetName()));

			$message = MessageBox($message, MSG_SUCCESS);
			$tags[] = $this->MakeXMLTag('status', 1);
			$tags[] = $this->MakeXMLTag('configDate', isc_date('jS M Y', $configDate));
			$tags[] = $this->MakeXMLTag('msg', isc_html_escape($message));

			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}
	}

	/**
	* reset a store wide GWO module
	*
	*/
	private function resetMoudle()
	{
		$errorMessage = '';
		if(!isset($_REQUEST['moduleId']) || $_REQUEST['moduleId'] == '') {
			$errorMessage = GetLang('MissingModuleId');
		} else if(!GetModuleById('optimizer', $optimizerModule, $_REQUEST['moduleId'])) {
			$errorMessage = GetLang('InvalidModuleId');
		}

		if($errorMessage != '') {
			$tags[] = $this->MakeXMLTag('status', 0);
			$tags[] = $this->MakeXMLTag('msg', $errorMessage);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}

		$moduleId = $_REQUEST['moduleId'];

		$query = "Delete from [|PREFIX|]module_vars where modulename = '".$GLOBALS['ISC_CLASS_DB']->Quote($moduleId)."'";
		$GLOBALS['ISC_CLASS_DB']->Query($query);

		//update config.php
		$enabledModules = GetConfig('OptimizerMethods');
		if (isset($enabledModules[$moduleId])) {
			unset($enabledModules[$moduleId]);
			$GLOBALS['ISC_NEW_CFG']['OptimizerMethods'] = $enabledModules;
		}

		$settings = GetClass('ISC_ADMIN_SETTINGS');
		if (!$settings->CommitSettings()) {
			$errorMessage = GetLang('ConfigFileUpdateFail');
		}


		//save optimizer data in cache for the use of store front code
		if($GLOBALS['ISC_CLASS_DATA_STORE']->UpdateOptimizerData()===false) {
			$errorMessage = GetLang("UpdateCacheFail");
		}

		if($errorMessage != '') {
			$tags[] = $this->MakeXMLTag('status', 0);
			$tags[] = $this->MakeXMLTag('msg', $errorMessage);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;

		} else {
			$message = isc_html_escape(sprintf(GetLang('ConfigFormResetSuccess'), $optimizerModule->GetName()));

			$message = MessageBox($message, MSG_SUCCESS);
			$tags[] = $this->MakeXMLTag('status', 1);
			$tags[] = $this->MakeXMLTag('msg', isc_html_escape($message));

			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}
	}


	/**
	* Get the conversion URL for custom conversion page, this URL should be saved in the database
	*/
	private function getConversionPageUrl()
	{
		$errorMessage = '';
		if(!isset($_REQUEST['moduleId']) || $_REQUEST['moduleId'] == '') {
			$errorMessage = GetLang('MissingModuleId');
		} else if(!GetModuleById('optimizer', $optimizerModule, $_REQUEST['moduleId'])) {
			$errorMessage = GetLang('InvalidModuleId');
		}

		if(!isset($_REQUEST['conversionPage']) || $_REQUEST['conversionPage'] == '') {
			$errorMessage = GetLang('MissingInfoForRequest');
		}

		if($errorMessage != '') {
			$tags[] = $this->MakeXMLTag('status', 0);
			$tags[] = $this->MakeXMLTag('msg', $errorMessage);
			$this->SendXMLHeader();
			$this->SendXMLResponse($tags);
			exit;
		}

		$conversionUrls = $optimizerModule->conversionPages;
		$conversionUrl = $GLOBALS['ShopPathSSL'].'/'.$conversionUrls[$_REQUEST['conversionPage']];

		$tags[] = $this->MakeXMLTag('status', 1);
		$tags[] = $this->MakeXMLTag('ConversionUrl', $conversionUrl);
		$this->SendXMLHeader();
		$this->SendXMLResponse($tags);
		exit;

	}
}