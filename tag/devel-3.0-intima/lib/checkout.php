<?php
// If we're calling this directly, exit
if(!defined('ISC_BASE_PATH')) {
	exit;
}

require_once ISC_BASE_PATH.'/includes/classes/class.checkoutprovider.php';

/**
 * Get a list of checkout providers that are enabled but are not necessarily configured.
 *
 * @return array An array of checkout providers.
 */
function GetEnabledCheckoutModules()
{
	$modules = GetAvailableModules('checkout', true);
	return $modules;
}

/**
 * Get a list of checking modules that are 'offline' payment modules.
 *
 * @return Array An array of the offline payment modules.
 */
function GetOfflineCheckoutModules()
{
	$modules = GetCheckoutModulesThatCustomerHasAccessTo();
	$availableModules = array();
	foreach($modules as $module) {
		if($module['object']->GetPaymentType() == PAYMENT_PROVIDER_OFFLINE) {
			$availableModules[$module['object']->GetId()] = $module;
		}
	}
	return $availableModules;
}

function GetManualOrderCheckoutModules()
{
	$modules = GetCheckoutModulesThatCustomerHasAccessTo();
	$availableModules = array();
	foreach($modules as $module) {
		if($module['object']->GetPaymentType() == PAYMENT_PROVIDER_OFFLINE || (method_exists($module['object'], 'GetManualPaymentFields') && count($module['object']->GetManualPaymentFields()))) {
			$availableModules[$module['object']->GetId()] = $module;
		}
	}
	return $availableModules;
}

/**
 * Get a list of checkout modules that are enabled, configured and that the customer has access to.
 *
 * @param boolean Set to true if we're on the 'confirm order' page.
 * @return array An array of available modules.
 */
function GetCheckoutModulesThatCustomerHasAccessTo($confirmPage=false)
{
	$modules = GetAvailableModules('checkout', true, true);
	$availableModules = array();

	foreach($modules as $module) {
		// Is the module accessible and supported?
		if(!$module['object']->IsAccessible() || !$module['object']->IsSupported()) {
			$module['object']->ResetErrors();
			continue;
		}

		// If we have a vendor order, does the module support these?
		if(!defined('ISC_ADMIN_CP')) {
			// Maybe we're on the "Confirm Order" page
			if($confirmPage && !$module['object']->showOnConfirmPage) {
				continue;
			}
		}

		// Otherwise, the module is available soo add it to the list
		$availableModules[] = $module;
		$module['object']->ResetErrors();
	}
	return $availableModules;
}