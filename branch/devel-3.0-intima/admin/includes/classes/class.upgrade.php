<?php
/**
 * ISC_ADMIN_UPGRADE
 *
 * This class handles the upgrade wizard used for upgrading Interspire Shopping Cart.
 *
 * @author Chris Boulton
 * @copyright 	Copyright (c) 2004-2008 Interspire Pty. Ltd.
 * @package 	Interspire Shopping Cart
 */

class ISC_ADMIN_UPGRADE extends ISC_ADMIN_BASE
{
	public $upgradeSession;
	protected $_preUpgradeConfig = null;

	private $errors = array();

	/**
	 * @var boolean True if this upgrade is being run via the command line.
	 */
	private $cliMode = false;

	/**
	 * @var array A key/value array of product version codes (stored in the database) and their friendly name to
	 *            be displayed to a user.
	 */
	private $versions = array (
		1000 => "1.0 (Beta)",
		1100 => "1.1 (Beta)",
		1200 => "1.2 (Beta)",
		1300 => "1.3 (Beta)",
		1400 => "1.4 (Beta)",
		1500 => "1.5 (Beta)",
		1800 => "1.8 (Beta)",
		2000 => "2.0 (Beta)",
		2500 => "2.5 (Beta)",
		3000 => "3.0",
		3010 => "3.0.1",
		3100 => "3.1",
		3110 => "3.1.1",
		3120 => "3.1.2",
		3500 => "3.5 (Beta)",
		3501 => '3.5.0',
		3510 => '3.5.1',
		3600 => '3.6',
		3601 => '3.6.1',
		3602 => '3.6.2',
		3603 => '3.6.3',
		4000 => '4.0',
		4001 => '4.0.1',
		4002 => '4.0.2',
		4003 => '4.0.3',
		4004 => '4.0.4',
		4005 => '4.0.5',
		4006 => '4.0.6',
		4007 => '4.0.7',
		4008 => '4.0.8',
		4009 => '4.0.9',
		5000 => '5.0 (Beta)',
		5001 => '5.0.1',
		5002 => '5.0.2',
		5003 => '5.0.3',
		5004 => '5.0.4',
		5005 => '5.0.5',
		5006 => '5.0.6',
		5500 => '5.5',
		5501 => '5.5.1',
		5502 => '5.5.2',
		5503 => '5.5.3',
		5504 => '5.5.4',
		5600 => '5.6.0',
		5601 => '5.6.1',
		5602 => '5.6.2',
		6000 => '6.0.0',
		6001 => '6.0.1',
		6002 => '6.0.2',
		6003 => '6.0.3',
		6004 => '6.0.4',
		6005 => '6.0.5',
		6006 => '6.0.6',
		6007 => '6.0.7',
		6008 => '6.0.8',
		6009 => '6.0.9',
		6010 => '6.0.10',
		6011 => '6.0.11',
		6012 => '6.0.12',
		6013 => '6.0.13',
		6014 => '6.0.14',
		6015 => '6.0.15',
		6016 => '6.0.16',
		6100 => '6.1.0',
		6101 => '6.1.1',
	);

	public function HandleTodo()
	{
		if(!$this->CanUpgrade()) {
			header("Location: index.php");
			exit;
		}

		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('upgrade');

		if(isset($_GET['ToDo'])) {
			$todo = $_GET['ToDo'];
		} else {
			$todo = "";
		}
		switch(strtolower($todo)) {
			case "runupgrade":
				$this->RunUpgrade();
				break;
			case "showupgradeframe":
				$this->ShowUpgradeFrame();
				break;
			case "showupgradethanks":
				$this->ShowUpgradeThanks();
				break;
			case "showupgradeerrors":
				$this->ShowUpgradeErrors();
				break;
			default:
				$this->ShowUpgradeWelcome();
		}

		// We don't want anything running after the upgrader!
		die();
	}

	/**
	 * Run an upgrade via the command line. This method simply sets up
	 * the environment then invokes all of the steps necessary to push
	 * the store to the latest version.
	 */
	public function CliUpgrade()
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('upgrade');
		$this->cliMode = true;
		$dbVersion = $this->GetDbVersion();

		if(!$this->InUpgradeSession()) {
			if(!$this->RunPreUpgradeChecks()) {
				fwrite(STDOUT, "ERROR:\n-");
				fwrite(STDOUT, implode("\n- ", $this->errors));
				exit(1);
			}

			$fromVersion = $this->GetDbVersion();
			if(isset($this->versions[$dbVersion])) {
				$fromVersion = $this->versions[$fromVersion];
			}
			fwrite(STDOUT, "BEGIN: Upgrade from ".$fromVersion." to ".PRODUCT_VERSION."\n");

			if (!$this->_createPreUpgradeConfigFile()) {
				fwrite(STDOUT, "ERROR:\n-");
				fwrite(STDOUT, implode("\n- ", $this->errors));
				exit(1);
			}

			$this->CreateUpgradeSession();
			$this->upgradeSession['moduleStack'] = $this->GetUpgradePath();
			$this->upgradeSession['upgradePath'] = $this->upgradeSession['moduleStack'];

			if(!isset($this->upgradeSession['progress'])) {
				$this->upgradeSession['progress'] = array(
					"complete" => 0,
					"total" => count($this->upgradeSession['moduleStack'], COUNT_RECURSIVE) - count($this->upgradeSession['moduleStack'])
				);
			}
		}
		else {
			$fromVersion = $this->GetDbVersion();
			if(isset($this->versions[$dbVersion])) {
				$fromVersion = $this->versions[$fromVersion];
			}
			fwrite(STDOUT, "CONTINUE: Upgrade from ".$fromVersion." to ".PRODUCT_VERSION."\n");
			$this->LoadUpgradeSession();
		}

		$this->UpdateUpgradeSession();

		// We now do a while() loop that just checks the result of
		// $this->UpgradeSession. Each step will be run, and the loop
		// is automatically broken when the upgrade is complete (thanks page exits)
		do {
			$result = $this->RunUpgrade();
		} while($result == true);
	}

	private function ShowUpgradeErrors()
	{

		if(!$this->InUpgradeSession()) {
			$this->ShowUpgradeWelcome();
			return;
		}

		$this->LoadUpgradeSession();

		$dbVersion = $this->GetDbVersion();
		if(isset($this->versions[$dbVersion])) {
			$GLOBALS['FromVersion'] = $this->versions[$dbVersion];
		}
		else {
			$GLOBALS['FromVersion'] = $dbVersion;
		}

		$errorReport = "Interspire Shopping Cart Upgrade Error Report\n";
		$errorReport .= "----------------------------------------------\n";
		$errorReport .= gmdate("r")."\n";
		$errorReport .= "\n";
		$errorReport .= "Store URL: ".$GLOBALS['ShopPath']."\n";
		$errorReport .= "Contact Email: ".GetConfig('AdminEmail')."\n";
		$errorReport .= "Product Edition: ".$GLOBALS['ProductEdition']."\n";
		$errorReport .= "\n";

		$errorReport .= "Upgrade Details:\n";
		$errorReport .= "----------------\n";
		$errorReport .= "Upgrade From: ".$GLOBALS['FromVersion']." (".$dbVersion.")\n";
		$errorReport .= "Upgrade To: ".PRODUCT_VERSION." (".PRODUCT_VERSION_CODE.")\n";
		$errorReport .= "\n";

		$errorReport .= "Upgrade Error:\n";
		$errorReport .= "----------------\n";
		$errorReport .= $this->upgradeSession['errorMessage'];
		$errorReport .= "\n";
		$errorReport .= "\n";

		$errorReport .= "Upgrade Trace:\n";
		$errorReport .= "-------------\n";
		$errorReport .= "\n";
		foreach($this->upgradeSession['upgradePath'] as $module => $steps) {
			$errorReport .= "[".$module."]\n";
			foreach($steps as $step) {
				$errorReport .= "  -- ".$step;
				if($module == $this->upgradeSession['currentStep']['module'] && $step == $this->upgradeSession['currentStep']['step']) {
					$errorReport .= " <-- Upgrade failed here";
				}
				$errorReport .= "\n";
			}
		}
		$errorReport .= "\n";
		$errorReport .= "Server Information:\n";
		$errorReport .= "---------------------\n";
		$errorReport .= "PHP Version: ".phpversion()."\n";
		$errorReport .= "MySQL Version: ".mysql_get_server_info()."\n";

		$GLOBALS['ErrorMessage'] = $errorReport;

		// Delete the upgrade session
		$this->DeleteUpgradeSession();

		if($this->cliMode) {
			fwrite(STDOUT, $errorReport);
			exit(1);
		}
		else {
			$this->template->display('upgrade.error.tpl');
		}
	}

	private function ShowErrorPage()
	{
		if($this->cliMode) {
			$this->ShowUpgradeErrors();
		}
		else {
			echo "<script type='text/javascript'>\n";
			echo "self.parent.ShowErrorPage();\n";
			echo "</script>";
			exit;
		}
	}

	private function RunUpgrade()
	{
		// Run the current module in the upgrade stack, if it returns true then we're done, skip to a new page and run the next
		$this->LoadUpgradeSession();

		$module = $step = '';

		// Still in the middle of a module, we need to run that one
		if(isset($this->upgradeSession['currentStep']) && is_array($this->upgradeSession['currentStep'])) {
			$module = $this->upgradeSession['currentStep']['module'];
			$step = $this->upgradeSession['currentStep']['step'];
		}
		// No module, shift the next one off the stack
		else {
			if (is_array($this->upgradeSession['moduleStack'])) {
				$module = array_slice($this->upgradeSession['moduleStack'], 0, 1);
				if (is_array($module)) {
					$moduleName = array_keys($module);
					$module = array_shift($module);
					if (is_array($module)) {
						$step = array_shift($module);
						$module = $moduleName[0];
					}
				}
			}
		}
		// Are we finished?
		if($this->upgradeSession['progress']['complete'] == $this->upgradeSession['progress']['total']) {
			$module = '';
			$step = '';
		}

		// Do we have a step to run now?
		if($module && $step) {
			$this->upgradeSession['currentStep'] = array(
				"module" => $module,
				"step" => $step
			);

			// Load the module
			require_once ISC_BASE_PATH."/admin/includes/upgrades/".$module;
			$moduleName = preg_replace("#\.php#i", '', $module);
			$class = "ISC_ADMIN_UPGRADE_".$moduleName;
			$upgradeClass = new $class($this);

			// Run this step
			if(!method_exists($upgradeClass, $step)) {
				$this->upgradeSession['errorMessage'] = 'Invalid method in upgrade class';
				$this->UpdateUpgradeSession();
				$this->ShowErrorPage();
			}

			$result = $upgradeClass->$step();

			// If this module returned true, it has finished running
			if($result == true) {
				unset($this->upgradeSession['currentStep']);
				++$this->upgradeSession['progress']['complete'];

				// Remove this step from the stack
				$stepKey = array_search($step, $this->upgradeSession['moduleStack'][$module]);
				unset($this->upgradeSession['moduleStack'][$module][$stepKey]);

				// Is this module now empty? If so, remove the whole module from the stack
				if(empty($this->upgradeSession['moduleStack'][$module])) {
					unset($this->upgradeSession['moduleStack'][$module]);
				}
			}
			else {
				// If this module returned false and has reported one or more errors
				// then we have a problem - show the error messgae page
				$errors = $upgradeClass->GetErrors();
				if(!empty($errors)) {
					$this->upgradeSession['errorMessage'] = implode("\n\n", $errors);
					$this->UpdateUpgradeSession();
					$this->ShowErrorPage();
				}
			}

			// Update the upgrader progress bar
			$this->UpdateUpgradeProgress();

			$this->UpdateUpgradeSession();

			if(!$this->cliMode) {
				// Throw back to this same page to continue the upgrade process
				echo "<script type='text/javascript'>\n";
				echo "setTimeout(function() { window.location = 'index.php?ToDo=runUpgrade&time=".time()."'; }, 10);\n";
				echo "</script>";
				exit;
			}
			else {
				return true;
			}
		}
		// Nothing left to run, show the completed page
		else {
			$this->HideUpgradeFrame();
		}
	}

	private function ShowUpgradeFrame()
	{
		// Are we not already in an upgrade session? We need to create it
		if(!$this->InUpgradeSession()) {
			if (!$this->_createPreUpgradeConfigFile()) {
				$this->ShowUpgradeErrors();
				return;
			}
			$this->CreateUpgradeSession();
			$this->upgradeSession['moduleStack'] = $this->upgradeSession['upgradePath'] = $this->GetUpgradePath();
		}
		else {
			$this->LoadUpgradeSession();
		}

		// Set the progress bar to where we're at
		if(!isset($this->upgradeSession['progress'])) {
			$this->upgradeSession['progress'] = array(
				"complete" => 0,
				"total" => count($this->upgradeSession['moduleStack'], COUNT_RECURSIVE) - count($this->upgradeSession['moduleStack'])
			);
		}

		if(isset($_REQUEST['sendServerDetails'])) {
			$this->upgradeSession['sendServerDetails'] = true;
		}

		$this->UpdateUpgradeSession();

		$GLOBALS['UpgradeIntro'] = sprintf(GetLang('UpgradeInProgressIntro'), PRODUCT_VERSION);

		$GLOBALS['StepsComplete'] = $this->upgradeSession['progress']['complete'];
		$GLOBALS['TotalSteps'] = $this->upgradeSession['progress']['total'];

		if ($this->upgradeSession['progress']['total'] > 0) {
			$GLOBALS['PercentComplete'] = ceil($GLOBALS['StepsComplete']/$GLOBALS['TotalSteps']*100);
			$GLOBALS['RunningStepOfX'] = sprintf(GetLang('UpgradeRunningStepXOfY'), $this->upgradeSession['progress']['complete'], $this->upgradeSession['progress']['total']);
		} else {
			$GLOBALS['PercentComplete'] = 100;
			$GLOBALS['RunningStepOfX'] = '';
		}

		// Show the frame which holds the upgrade progress bar/details etc
		$this->template->display('pageheader.popup.tpl');
		$this->template->display('upgrade.progress.tpl');
		$this->template->display('pagefooter.tpl');
	}

	private function HideUpgradeFrame()
	{
		if($this->cliMode) {
			$this->ShowUpgradeThanks();
		}
		else {
			$this->UpdateUpgradeProgress(GetLang('UpgradeComplete'));
			echo "<script type=\"text/javascript\">\n";
			echo "self.parent.UpgradeFinished();";
			echo "</script>";
		}
	}

	private function ShowUpgradeThanks()
	{
		$this->LoadUpgradeSession();

		// Show the "Thank you for upgrading to X" page.
		$dbVersion = $this->GetDbVersion();
		if(isset($this->versions[$dbVersion])) {
			$GLOBALS['FromVersion'] = $this->versions[$dbVersion];
		}
		else {
			$GLOBALS['FromVersion'] = $dbVersion;
		}

		$GLOBALS['ToVersion'] = PRODUCT_VERSION;

		// Clear all of the contents from the data store so it'll be rebuilt when we next hit the shopping cart
		$GLOBALS['ISC_CLASS_DATA_STORE']->Clear();

		// Mark the version number in the DB the version we've just installed
		$this->UpdateDbVersion();

		// Delete the upgrade session
		$this->DeleteUpgradeSession();

		// Remove cached templates
		$this->_clearCachedTemplates();

		$GLOBALS['UpgradeTitle'] = sprintf(GetLang('UpgradeInterspireShoppingCartComplete'), $GLOBALS['ToVersion']);

		// Generate a new token to use for any Javascript/stylesheets
		// to ensure that browsers are using the new version after the upgrade
		$GLOBALS['ISC_NEW_CFG']['JSCacheToken'] = substr(md5(uniqid()), 0, 5);

		// Rewrite the configuration file to make sure we have the latest one
		$this->RewriteConfig();
		// Are we sending the details back to Interspire?
		if(isset($this->upgradeSession['sendServerDetails'])) {
			$GLOBALS['ISC_CLASS_ADMIN_INSTALL'] = GetClass('ISC_ADMIN_INSTALL');
			$GLOBALS['HiddenImage'] = $GLOBALS['ISC_CLASS_ADMIN_INSTALL']->SendServerDetails(1, $GLOBALS['FromVersion']);
		}

		if($this->cliMode) {
			fwrite(STDOUT, 'COMPLETE: Upgrade from '.$GLOBALS['FromVersion'].' to '.$GLOBALS['ToVersion']." complete.\n");
			exit(0);
		}
		else {
			$this->template->display('upgrade.done.tpl');
		}
	}

	public function ShowUpgradeWelcome()
	{
		if(defined('UPGRADE_WARNING_MSG') && UPGRADE_WARNING_MSG) {
			$GLOBALS['UpgradeWarning'] = UPGRADE_WARNING_MSG;
		}
		else {
			$GLOBALS['HideUpgradeWarning'] = 'display: none';
		}

		$GLOBALS['HideUpgradeErrors'] = "none";
		if($this->InUpgradeSession()) {
			$GLOBALS['HideUpgradeWelcome'] = "none";
		}
		else {
			// Check if we can actually run the upgrade process now
			if(!$this->RunPreUpgradeChecks()) {
				$GLOBALS['HideUpgradeWelcome'] = "none";
				$GLOBALS['HideUpgradeErrors'] = '';
				$GLOBALS['UpgradeErrors'] = "<li>".implode("</li>\n<li>", $this->errors)."</li>";
			}

			$GLOBALS['HideUpgradeContinue'] = "none";
		}

		// Show the "Thank you for upgrading to X" page.
		$dbVersion = $this->GetDbVersion();
		if(isset($this->versions[$dbVersion])) {
			$GLOBALS['FromVersion'] = $this->versions[$dbVersion];
		}
		else {
			$GLOBALS['FromVersion'] = $dbVersion;
		}

		$GLOBALS['ToVersion'] = PRODUCT_VERSION;
		$GLOBALS['UpgradeFromTo'] = sprintf(GetLang('UpgradeWelcomeFromTo'), $GLOBALS['FromVersion'], $GLOBALS['ToVersion']);

		$this->template->display('upgrade.welcome.tpl');
	}

	private function InUpgradeSession()
	{
		// Are we currently in an upgrade session? (Does the "upgrade_session" table exist?)
		$query = "SHOW TABLES LIKE '[|PREFIX|]upgrade_session'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
		if($row) {
			return true;
		}
		else {
			return false;
		}
	}

	private function LoadUpgradeSession()
	{
		$query = "SELECT data FROM [|PREFIX|]upgrade_session WHERE type='primary'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$this->upgradeSession = @unserialize($GLOBALS['ISC_CLASS_DB']->FetchOne($result));
		// Fetch the upgrade session information from the database
	}

	private function CreateUpgradeSession()
	{
		// Create the temporary upgrade table in the database
		$query = "CREATE TABLE [|PREFIX|]upgrade_session (
			type varchar(20) NOT NULL default '',
			data TEXT NOT NULL
		);";
		$GLOBALS['ISC_CLASS_DB']->Query($query);

		// Create the primary record
		$upgradeSession = array(
			'type' => 'primary',
			'data' => ''
		);
		$GLOBALS['ISC_CLASS_DB']->InsertQuery("upgrade_session", $upgradeSession);
	}

	private function UpdateUpgradeSession()
	{
		$updatedSession = array(
			"data" => serialize($this->upgradeSession)
		);
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery("upgrade_session", $updatedSession, "type='primary'");
	}

	private function DeleteUpgradeSession()
	{
		// Delete the upgrade session
		$query = "DROP TABLE [|PREFIX|]upgrade_session";
		$GLOBALS['ISC_CLASS_DB']->Query($query);
	}

	private function GetUpgradePath()
	{
		$currentVersion = $this->GetDbVersion();
		$modules = scandir(ISC_BASE_PATH."/admin/includes/upgrades/");
		$moduleStart = false;
		$moduleStack = array();

		foreach($modules as $module) {
			$moduleName = preg_replace("#\.php#i", '', $module);
			if(!is_numeric($moduleName)) {
				continue;
			}

			if($moduleName == $currentVersion) {
				$moduleStart = true;
				continue;
			}
			else if($moduleName >= $currentVersion) {
				$moduleStart = true;
			}
			else if(!$moduleStart) {
				continue;
			}

			if($moduleStart == true) {
				if(!is_file(ISC_BASE_PATH."/admin/includes/upgrades/".$module)) {
					continue;
				}

				// Load the module
				require_once ISC_BASE_PATH."/admin/includes/upgrades/".$module;
				$class = "ISC_ADMIN_UPGRADE_".$moduleName;
				if(class_exists($class)) {
					$upgradeClass = new $class($this);
					$moduleStack[$module] = $upgradeClass->steps;
				}
			}
		}
		return $moduleStack;
	}

	private function RunPreUpgradeChecks()
	{
		$stack = $this->GetUpgradePath();
		$errors = array();
		if(!is_array($stack)) {
			return false;
		}

		if ($this->CheckPermissions()) {
			foreach(array_keys($stack) as $module) {
				$moduleName = preg_replace("#\.php#i", '', $module);
				$class = "ISC_ADMIN_UPGRADE_".$moduleName;
				if (class_exists($class)) {
					$upgradeClass = new $class($this);
					if (method_exists($upgradeClass, "pre_upgrade_checks")) {
						if (!$upgradeClass->pre_upgrade_checks()) {
							$this->errors = array_merge($this->errors, $upgradeClass->GetErrors());
						}
					}
				}
			}
		}

		if (!empty($this->errors)) {
			return false;
		}
		else {
			return true;
		}
	}

	public function CheckPermissions()
	{
		include_once(ISC_BASE_PATH.'/lib/class.file.php');

		$f = new FileClass();

		$install = GetClass('ISC_ADMIN_INSTALL');

		foreach ($install->FoldersToCheck as $folder) {

			$path = ISC_BASE_PATH . '/' . $folder;

			if (file_exists($path)) {
				if (is_dir($path) && !$f->CheckDirWritable($path)) {
					$this->errors[] = sprintf(GetLang('UpgradePreCheckDirectoryNotWriteable'), $folder);
				}
				else if (is_file($path) && !$f->CheckFileWritable($path)) {
					$this->errors[] = sprintf(GetLang('UpgradePreCheckFileNotWriteable'), $folder);
				}
			}
		}

		if (empty($this->errors)) {
			return true;
		}
		else {
			return false;
		}
	}

	public function CanUpgrade()
	{
		$dbVersion = $this->GetDbVersion();

		// If no result, it's quite possible they are running a very early version of ISC, so we create the version number
		if(!$dbVersion) {
			$newVersion = array(
				"database_version" => '1200'
			);
			$GLOBALS['ISC_CLASS_DB']->InsertQuery("config", $newVersion);
			$dbVersion = 1200;
		}

		if($dbVersion < PRODUCT_VERSION_CODE) {
			return true;
		}
		else {
			return false;
		}
	}

	public function GetDbVersion()
	{
		$query = "SELECT MAX(database_version) FROM [|PREFIX|]config";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		return $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
	}


	public function UpdateDbVersion()
	{
		$query = "UPDATE [|PREFIX|]config SET database_version='".$GLOBALS['ISC_CLASS_DB']->Quote(PRODUCT_VERSION_CODE)."'";
		$GLOBALS['ISC_CLASS_DB']->Query($query);
	}

	/**
	 * Rewrite the local config file with new values from config.default.php
	 *
	 * @return boolean True if successful, false on failure.
	 */
	public function RewriteConfig()
	{
		if (!defined('ISC_CONFIG_FILE') || !defined('ISC_CONFIG_DEFAULT_FILE')) {
			die("Config sanity check failed");
		}

		// Include the default config file. This will override the global settings
		require(ISC_CONFIG_DEFAULT_FILE);

		// Now load the local config file to override those
		require(ISC_CONFIG_FILE);

		// Now we rebuild the configuration file using the template and we should have the latest version of the settings
		$GLOBALS['ISC_CLASS_ADMIN_SETTINGS'] = GetClass('ISC_ADMIN_SETTINGS');
		if (!$GLOBALS['ISC_CLASS_ADMIN_SETTINGS']->CommitSettings()) {
			return false;
		}

		$this->_deletePreUpgradeConfigFile();
		return true;
	}

	private function UpdateUpgradeProgress($msg = '')
	{
		if ($this->upgradeSession['progress']['total'] > 0) {
			$percent = ceil($this->upgradeSession['progress']['complete']/$this->upgradeSession['progress']['total']*100);
		} else {
			$percent = 100;
		}

		if($msg == '') {
			$msg = sprintf(GetLang('UpgradeRunningStepXOfY'), $this->upgradeSession['progress']['complete'], $this->upgradeSession['progress']['total']);

			if($this->cliMode) {
				fwrite(STDOUT, $msg."\n");
				return;
			}
		}

		echo "<script type=\"text/javascript\">";
		echo "self.parent.UpdateProgress('".$msg."', '".$percent."');\n";
		echo "</script>";
		flush();
	}

	/**
	* Gets a config value as it was before the current upgrade began
	*
	* @param string $config the config key
	* @return mixed the config value or blank string if not found
	*/
	public function getPreUpgradeConfig ($config)
	{
		$this->_loadPreUpgradeConfigFile();
		if (array_key_exists($config, $this->_preUpgradeConfig)) {
			return $this->_preUpgradeConfig[$config];
		}
		return '';
	}

	/**
	* Creates the preupgradeconfig.php cache file based on the current config values but only if the file does not already exist
	*
	* @return bool false if creation of the file failed, otherwise true on success or if the file already existed
	*/
	protected function _createPreUpgradeConfigFile ($force = false)
	{
		$file = ISC_CACHE_DIRECTORY . 'preupgradeconfig.php';
		if (file_exists($file) && !$force) {
			return true;
		}

		$contents = '<' . '?php' . "\n" . '$preUpgradeConfig = ' . var_export($GLOBALS['ISC_CFG'], true) . ';' . "\n";
		if (!file_put_contents($file, $contents)) {
			$this->errors[] = sprintf(GetLang('UpgradePreUpgradeConfigFileWriteFailed'), $folder);
			return false;
		}
		if (!isc_chmod($file, ISC_WRITEABLE_FILE_PERM)) {
			$this->errors[] = sprintf(GetLang('UpgradePreUpgradeConfigFileWriteFailed'), $folder);
			return false;
		}
		return true;
	}

	/**
	* Reads preupgradeconfig.php cache file into $this->_preUpgradeConfig
	*
	* @param bool $force bypass internal caching mechanism and force a reload of the config file
	* @return void
	*/
	protected function _loadPreUpgradeConfigFile ($force = false)
	{
		if ($this->_preUpgradeConfig !== null && !$force) {
			return;
		}

		$this->_preUpgradeConfig = array();
		$file = ISC_CACHE_DIRECTORY . 'preupgradeconfig.php';
		if (!file_exists($file)) {
			return;
		}

		$preUpgradeConfig = array();
		require_once $file;
		if (isset($preUpgradeConfig) && !empty($preUpgradeConfig)) {
			$this->_preUpgradeConfig = $preUpgradeConfig;
		}
	}

	/**
	* Deletes the preupgradeconfig.php cache file
	*
	* @return bool false if failed otherwise true
	*/
	protected function _deletePreUpgradeConfigFile ()
	{
		error_log(__FUNCTION__);
		$file = ISC_CACHE_DIRECTORY . 'preupgradeconfig.php';
		if (file_exists($file) && !unlink($file)) {
			return false;
		}
		return true;
	}

	/**
	 * Remove any precompiled/cached templates after a completed upgrade to
	 * prevent potential problems with templates not being recompiled when
	 * there are newer versions.
	 */
	protected function _clearCachedTemplates()
	{
		$directories = array(
			ISC_ADMIN_TEMPLATE_CACHE_DIRECTORY,
			ISC_FRONT_TEMPLATE_CACHE_DIRECTORY,
		);
		foreach($directories as $directory) {
			$dh = opendir($directory);
			if (!$dh) {
				continue;
			}

			while (($file = readdir($dh)) !== false) {
				if (substr($file, -4) != '.php') {
					continue;
				}

				@unlink($directory . '/' . $file);
			}

			closedir($dh);
		}
	}
}

class ISC_ADMIN_UPGRADE_BASE
{
	private $steps = array();
	private $errors = array();
	private $upgrade_class = null;

	/** @var Db */
	protected $db;

	public function __construct(ISC_ADMIN_UPGRADE $upgradeClass)
	{
		$this->upgrade_class = $upgradeClass;
		$this->db = $GLOBALS['ISC_CLASS_DB'];
	}

	public function SetError($message)
	{
		$this->errors[] = $message;
	}

	public function GetErrors()
	{
		return $this->errors;
	}

	public function HasErrors()
	{
		return !empty($this->errors);
	}

	/**
	* Check if a column exists in a table
	*
	* @param $table The table with the column we are checking for
	* @param $column The column to check for
	* @param $returnArray TRUE to return the array setup if column if found. Default is FALSE (just return boolue)
	*
	* @return boolean If the column exists in the table return true
	*/
	public function ColumnExists($table, $column, $returnArray=false)
	{
		if ($table == '' || $column == '') {
			return false;
		}

		$query = 'SHOW COLUMNS FROM '.$table." LIKE '".$column."'";

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		if ($row['Field'] == $column) {
			if ($returnArray) {
				return $row;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * Check if a ENUM option exists within a column
	 *
	 * @param $table The table with the column we are checking for
	 * @param $column The ENUM column to check for
	 * @param $option The ENUM option to check for
	 *
	 * @return boolean TRUE If the ENUM option exists, FALSE if not
	 */
	public function EnumExists($table, $column, $option)
	{
		$setup = $this->ColumnExists($table, $column, true);

		if ($option == '' || !is_array($setup)) {
			return false;
		}

		$type = isc_substr(trim($setup['Type']), 0, 4);

		if (isc_strtolower($type) !== 'enum') {
			return false;
		}

		$options = isc_substr(trim($setup['Type']), 6, -2);
		$options = explode("','", $options);
		$options = array_map('trim', $options);

		return in_array($option, $options);
	}

	public function IndexExists($table, $index)
	{
		$indexes = array();

		$query = 'SHOW INDEX FROM '.$table;
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if ($result === false) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
		}
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			if ($row['Key_name'] == $index) {
				return true;
			}
		}
		return false;
	}

	public function TableExists($table, $forcePrefix=true)
	{
		if ($forcePrefix) {
			$table = "[|PREFIX|]" . $table;
		}

		$result = $GLOBALS['ISC_CLASS_DB']->Query("SHOW TABLES LIKE '".$table."'");

		if ($result !== false && $GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
			return true;
		}

		return false;
	}

	/**
	* Perform an ALTER TABLE MODIFY COLUMN on the specified table to update an existing ENUM column to add a new option. This assumes the column exists and is ENUM type, so make sure to check !$this->EnumExists(...) first before attempting this.
	*
	* @param string $table Table name with prefix
	* @param string $column Column name
	* @param string $option ENUM option to add
	*/
	public function AddEnumOption($table, $column, $option)
	{
		$details = $this->ColumnExists($table, $column, true);
		if (!$details) {
			return false;
		}

		$options = preg_replace('#enum\(\'(.*)\'\)#', '$1', $details['Type']);
		if ($options === null) {
			// preg_replace error?
			return false;
		}

		$options = explode("','", $options);

		if (in_array($option, $options)) {
			// option is already in enum, abort successfully
			return true;
		}

		$options[] = $option;

		$query = "ALTER TABLE `" . $table . "` MODIFY COLUMN `" . $column . "` ENUM('" . implode("','", $options) . "')";

		if ($details['Null'] != 'YES') {
			$query .= " NOT NULL";
		}

		if ($details['Default'] === null) {
			if ($details['Null'] == 'YES') {
				$query .= " default NULL";
			}
		} else {
			$query .= " default '" . $details['Default'] . "'";
		}

		return $GLOBALS["ISC_CLASS_DB"]->Query($query);
	}

	/**
	* @return ISC_ADMIN_UPGRADE
	*/
	public function getUpgradeClass ()
	{
		return $this->upgrade_class;
	}

	/**
	* @param string $config the config key
	* @return mixed the config value or a blank string if not found
	*/
	public function getPreUpgradeConfig ($config)
	{
		return $this->getUpgradeClass()->getPreUpgradeConfig($config);
	}

	/**
	 * Returns the column type as determined by mysql's DESCRIBE command
	 *
	 * @param string $table Full name of the table (no prefix necessary)
	 * @param string $column Name of the column
	 * @return string or false if column does not exist or if query fails
	 */
	public function getColumnType ($table, $column)
	{
		$query = "SHOW COLUMNS FROM `[|PREFIX|]" . $table . "` WHERE `Field` = '" . $GLOBALS["ISC_CLASS_DB"]->Quote($column) . "'";
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		if (!$result) {
			return false;
		}

		$row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);
		if (!$row || !isset($row['Type'])) {
			return false;
		}

		return (string)$row['Type'];
	}
}
