<?php

/**
* This class contains 'down for maintenance' methods which used to be in lib/general
*/
class Store_DownForMaintenance
{
	/**
	 * Loads the 'Down for Maintenance' message.
	 * It'll check if the user has saved a custom message, otherwise it'll return the one from the language pack.
	 */
	public static function getDownForMaintenanceMessage($forceFromLanguagePack=false)
	{
		if(GetConfig('DownForMaintenanceMessage') == null || trim((string)GetConfig('DownForMaintenanceMessage')) == '' || $forceFromLanguagePack) {
			// load the maintenance message from the language pack
			$frontEndLanguage = parse_ini_file(ISC_BASE_PATH . '/language/' . GetConfig('Language') . '/front_language.ini');
			if(isset($frontEndLanguage['DownForMaintenanceMessage'])) {
				return $frontEndLanguage['DownForMaintenanceMessage'];
			}
			return '';
		}

		return (string)GetConfig('DownForMaintenanceMessage');
	}

	/**
	* Outputs the 'down for maintenance' message to the browser and quits
	*/
	public static function showDownForMaintenance()
	{
		$GLOBALS['MessageText'] = self::getDownForMaintenanceMessage();

		if(defined('MAINTENANCE_IS_ADMIN') && MAINTENANCE_IS_ADMIN === true) {
			$GLOBALS['MessageText'] .= GetLang('AdminMaintenanceMessage');
		}

		// This tells crawlers to come back later, rather than crawling and indexing your "Down for maintenance" pages
		// Recommended by Google to be used on "Down for maintenance" pages
		header('HTTP/1.1 503 Service Unavailable');
		header("Status: 503 Service Temporarily Unavailable");
		header("Retry-After: 3600");
		header("Connection: Close");

		$GLOBALS['MessageTitle'] = GetLang('DownForMaintenance');
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle($GLOBALS['MessageTitle']);
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("maintenance");
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		die();
	}
}
