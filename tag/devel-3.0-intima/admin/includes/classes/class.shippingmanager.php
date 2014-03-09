<?php
class ISC_ADMIN_SHIPPINGMANAGER
{
	public function handleManager()
	{
		if (!isset($_GET['manager'])) {
			exit;
		}

		$manager = 'shippingmanager_' . $_GET['manager'];

		if (!GetModuleById('shippingmanager', $module, $manager) || !$module->IsEnabled() || !method_exists($module, 'handleAction')) {
			exit;
		}

		$module->handleAction();
	}
}