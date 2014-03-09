<?php

	/**
	* The Interspire Shopping Cart addon base class, used by all addon modules
	*
	* @author: Mitchell Harper
	* @copyright: Interspire Pty. Ltd.
	* @date: 18th Jan 2008
	*/

	class ISC_ADDON extends ISC_MODULE
	{
		/**
		 * @var string The type of module this is.
		 */
		protected $type = 'addon';

		/*
			A unique permission ID that will be tied into user permissions
		*/
		protected $_permissionid = 0;

		/*
			Should we show the save and cancel buttons on the bottom of the settings page?
		*/

		protected $_showsavecancel = true;

		/**
		 * An array of menu items for this addon.
		 */
		public $menuItems = array();

		/**
		 * Check if this particular type of module is enabled or not. (Module specific)
		 *
		 * @return boolean True if the module is enabled, false if not.
		 */
		protected function CheckEnabled()
		{
			$addon_modules = explode(",", GetConfig('AddonModules'));
			if(in_array($this->GetId(), $addon_modules)) {
				return true;
			} else {
				return false;
			}
		}

		/**
		* SetPermissionId
		* Assign a unique permission ID to the addon that will be tied into
		* the users permission system when setting permissions for staff
		* who can access the control panel
		*
		* @param Int $PermissionId The unique permission ID for this addon
		*
		* @return Void
		*/
		protected function SetPermissionId($PermissionId)
		{
			$this->_permissionid = (int)$PermissionId;
		}

		/**
		* GetPermissionId
		* Return the unique permission ID assigned to this addon
		*
		* @return Int
		*/
		public function GetPermissionId()
		{
			return $this->_permissionid;
		}

		/**
		 * Register a menu item for a particular addon.
		 *
		 * @param array An array containing the details of the menu item.
		 */
		protected function RegisterMenuItem($item)
		{
			if(!isset($item['location'])) {
				return false;
			}

			if(isset($item['icon']) && $item['icon'] != '') {
				$folder = str_replace("addon_", "", $this->GetId());
				$item['icon'] = sprintf("%s/addons/%s/%s", $GLOBALS['ShopPath'], $folder, $item['icon']);
			}

			$this->menuItems[] = $item;
		}

		/**
		 * Get a list of menu items for a particular addon.
		 *
		 * @return array An array of items for this particular addon to be added to the menu.
		 */
		public function GetMenuItems()
		{
			return $this->menuItems;
		}

		/**
		* ShowSaveAndCancelButtons
		* Should the save and cancel buttons be shown at the bottom of the
		* the settings page for this addon?
		*
		* @return Void
		*/
		protected function ShowSaveAndCancelButtons($State)
		{
			$this->_showsavecancel = $State;
		}

		/**
		* IsShowSaveCancelButtons
		* Should the save and cancel buttons be output?
		*
		* @return Boolean
		*/
		public function IsShowSaveCancelButtons()
		{
			return $this->_showsavecancel;
		}

		/**
		* SetVariable
		* Set an addon variable
		*
		* @param Array $Values The details of the variable
		* @return Void
		*/
		protected function SetVariable($Values)
		{
			array_push($this->_variables, $Values);
		}

		/**
		* GetPropertiesSheet
		* Return a HTML-formatted list of properties for this addon module
		*
		* @return String
		*/
		public function GetPropertiesSheet($tab_id)
		{
			// Are we showing the save and cancel buttons for this addon?
			if(!$this->IsShowSaveCancelButtons()) {
				if(!isset($GLOBALS['TabIdsToHideButtonsFrom'])) {
					$GLOBALS['TabIdsToHideButtonsFrom'] = "";
				}

				$GLOBALS['TabIdsToHideButtonsFrom'] .= $tab_id . "|";
			}

			return parent::GetPropertiesSheet($tab_id, 'ShipperId', 'AddonJavaScript', 'package_selected');
		}

		/**
		* Addon module type specific wrapper for getting help text - includes a yellow highlight div
		*
		* @return string
		*/
		public function GetHelpText()
		{
			$text = parent::GetHelpText();
			if ($text) {
				$text = '<div class="HelpInfo">' . $text . '</div>';
			}
			return $text;
		}

		/**
		* LogAction
		* Log that the addon has been executed
		*
		* @return Void
		*/
		protected function LogAction()
		{
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($this->GetId(), $this->GetName());
		}
	}