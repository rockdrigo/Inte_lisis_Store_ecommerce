<?php

	/**
	* ISC_ADDON
	* Handles the execution of all addon modules through the control panel
	*
	* @author Mitchell Harper
	* @copyright Interspire Pty. Ltd.
	* @date	19th Jan 2008
	*/

	class ISC_ADMIN_ADDON extends ISC_ADMIN_BASE
	{

		/*
			An instantiation of the selected addon
		*/
		public $_addon = null;

		/**
		* Constructor
		* Work out which addon we're running so we can show it in the breadcrum trail amongst other things
		*
		* @return Void
		*/
		public function __construct()
		{
			parent::__construct();

			if(isset($_REQUEST['addon'])) {
				$addon_folder = str_replace("addon_", "", $_REQUEST['addon']);

				if(!GetAddonsModule($this->_addon, strtolower($addon_folder))) {
					$this->_BadAddon(sprintf(GetLang('InvalidAddon'), $addon_folder));
				}
			}
			else {
				// No addon specified
				$this->_BadAddon(GetLang('NoAddonSpecified'));
			}
		}

		/**
		* BadAddon
		* Redirect to the home page if the addon's details are invalid or it couldn't be loaded
		*
		* @param String $Message An optional error message to be displayed after the user has been redirected to the home page
		* @return Void
		*/
		public function _BadAddon($Message='')
		{
			if($Message != '') {
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage($Message, MSG_ERROR);
				exit;
			}
			else {
				ob_end_clean();
				header("Location: index.php?ToDo=");
				die();
			}
		}

		/**
		* HandleToDo
		* Which addon function should we run?
		*
		* @param String $ToDo The function to run
		* @return Void
		*/
		public function HandleToDo($Do)
		{

			// Is the addon enabled?
			if(!$this->_addon->IsEnabled()) {
				$this->_BadAddon(sprintf(GetLang('AddonNotEnabled'), $this->_addon->GetName()));
			}

			if (isset($this->_addon->_location)) {
				$GLOBALS['HighlightedMenuItem'] = $this->_addon->_location;
			}

			if(isset($_REQUEST['func'])) {
				$func = $_REQUEST['func'];
			}
			else {
				$func = 'EntryPoint';
			}

			$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Addons') => "index.php?ToDo=viewDownloadAddons", $this->_addon->GetName() => $_SERVER["PHP_SELF"]);
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
			$this->RunAddon($func);
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
		}

		/**
		* RunAddon
		* Handles the execution of functions specific to the addon. The function is
		* passed back to the addon and that function is executed as required. If no
		* function is specified, then the addon's EntryPoint function is executed.
		* Every addon should expose an EntryPoint function as its first point of entry.
		*
		* @param String $Function The name of the addon function to execute
		* @return Void
		*/
		public function RunAddon($Function='EntryPoint')
		{
			$funcs = get_class_methods($this->_addon);

			if(in_array($Function, $funcs)) {
				$this->_addon->$Function();
			}
			else {
				$this->_BadAddon(sprintf(GetLang('AddonInvalidFunction'), $this->_addon->GetName(), $Function));
			}
		}
	}