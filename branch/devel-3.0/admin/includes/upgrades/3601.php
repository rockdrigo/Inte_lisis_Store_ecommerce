<?php
class ISC_ADMIN_UPGRADE_3601 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		'rename_eselectplus_vars'
	);

	public function pre_upgrade_checks()
	{
		if(is_dir(ISC_BASE_PATH."/modules/checkout/eselectplus")) {
			$this->SetError('Please delete the /modules/checkout/eselectplus/ directory from your store. This module has been renamed and is no longer necessary.');
		}
	}

	public function rename_eselectplus_vars()
	{
		$updatedVars = array(
			'modulename' => 'checkout_eselectplushp'
		);
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery('module_vars', $updatedVars, "modulename='checkout_eselectplus'");
		return true;
	}
}