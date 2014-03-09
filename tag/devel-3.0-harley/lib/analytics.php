<?php
// If we're calling this directly, exit
if(!defined('ISC_BASE_PATH')) {
	exit;
}

require_once(dirname(__FILE__).'/module.php');

/**
 * Return the tracking code for all of the enabled analytics modules.
 *
 * @return string The tracking code to be inserted on pages.
 */
function GetTrackingCodeForAllPackages()
{
	$packages = GetAvailableModules('analytics', true, true);
	$code = "";

	foreach ($packages as $package) {
		if (GetModuleById('analytics', $module, $package['id'])) {
			$trackingCode = $module->GetTrackingCode();
		}
		$code .= "<!-- Start Tracking Code for " . $package['id'] . " -->\n\n" . $trackingCode . "\n\n<!-- End Tracking Code for " . $package['id'] . " -->\n\n";
	}

	return $code;
}