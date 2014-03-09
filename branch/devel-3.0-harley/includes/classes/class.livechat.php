<?php
// If Shopping Cart is not loaded, then get the hell out of here
if(!defined('ISC_BASE_PATH')) {
	exit;
}

/**
 * The Interspire Shopping Cart live chat services integration base class.
 * This class is used by all live chat services that integrate with Interspire
 * Shopping Cart.
 *
 * Each live chat service extends upon this base class.
 */
class ISC_LIVECHAT extends ISC_MODULE
{
	/**
	 * @var string The type of module this is.
	 */
	public $type = 'livechat';

	/**
	 * Check if this particular module is enabled or not.
	 *
	 * @return boolean True if the module is enabled, false if not.
	 */
	public function CheckEnabled()
	{
		$liveChatServices = explode(',', GetConfig('LiveChatModules'));
		if(in_array($this->GetId(), $liveChatServices)) {
			return true;
		}

		return false;
	}

	/**
	 * Return the properties/settings sheet for this live chat module.
	 *
	 * @param int The identifier of the tab for this live chat module.
	 * @return string The properties sheet contents.
	 */
	public function GetPropertiesSheet($tabId)
	{
		return parent::GetPropertiesSheet($tabId, 'PackageId', 'LiveChatJavascript', 'package_selected');
	}

	/**
	 * Return a list of the enabled live chat modules.
	 *
	 * @return array An array containing the enabled live chat modules.
	 */
	public function GetEnabledModules()
	{
		return GetAvailableModules('livechat', true);
	}

	/**
	 * Get the live chat tracking code for this module for the specified page position.
	 *
	 * @param string The position (header or panel) to fetch the tracking code for. If not the position that's
	 *				 enabled for this module, then this method should return an empty string.
	 * @return string String containing the live chat code.
	 */
	public function GetLiveChatCode($position)
	{
		return '';
	}

	/**
	 * Get the live chat service tracking code for the enabled live chat services for
	 * a specific location (header or panel)
	 */
	public function GetPageTrackingCode($position)
	{
		$enabledModules = $this->GetEnabledModules();
		if(empty($enabledModules)) {
			return '';
		}

		$chatCode = '';
		foreach($enabledModules as $module) {
			$chatCode .= $module['object']->GetLiveChatCode($position);
		}

		$chatCode = str_replace('%%IMG_DIRECTORY%%', $GLOBALS['IMG_PATH'].'/'.GetConfig('SiteColor'), $chatCode);
		return $chatCode;
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
}
