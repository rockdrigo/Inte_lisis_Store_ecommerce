<?php
class ISC_ADMIN_UPGRADE_5006 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		'mark_gettingstarted_completed',
		'add_tax_shipping_option',
		'fix_gift_wrap_collation',
		'update_simplepay_checkout_module'
	);

	public function pre_upgrade_checks()
	{
		if(is_dir(ISC_BASE_PATH."/modules/checkout/ideal_rabo")) {
			$this->SetError('Please delete the /modules/checkout/ideal_rabo/ directory from your store. This module has been rewritten and is no longer necessary. If you were previously using this module, you should enable and configure the new iDEAL Professional/Advanced module.');
		}
	}

	public function mark_gettingstarted_completed()
	{
		$GLOBALS['ISC_NEW_CFG']['GettingStartedCompleted'] = array(
			'settings',
			'design',
			'taxSettings',
			'paymentMethods',
			'products',
			'shippingOptions',
			'storeComplete',
		);
		GetClass('ISC_ADMIN_SETTINGS')->CommitSettings();
		return true;
	}

	public function add_tax_shipping_option()
	{
		if (!$this->ColumnExists('[|PREFIX|]tax_rates', 'taxshippingfortaxableorder')) {
			$query = "ALTER TABLE `[|PREFIX|]tax_rates` ADD `taxshippingfortaxableorder` TINYINT(1) NOT NULL DEFAULT '0'";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function fix_gift_wrap_collation()
	{
		if (!$GLOBALS['ISC_CLASS_DB']->Query('ALTER TABLE `[|PREFIX|]gift_wrapping` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci')) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		if (!$GLOBALS['ISC_CLASS_DB']->Query('ALTER TABLE `[|PREFIX|]gift_wrapping` CHANGE `wrapname` `wrapname` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL')) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		if (!$GLOBALS['ISC_CLASS_DB']->Query('ALTER TABLE `[|PREFIX|]gift_wrapping` CHANGE `wrappreview` `wrappreview` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL')) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function update_simplepay_checkout_module()
	{
		if (!ModuleIsConfigured('checkout_paysimple')) {
			return true;
		}

		GetModuleById('checkout', $module, 'checkout_paysimple');

		// Check to see if the module hasn't already been updated
		$value = $module->GetValue('merchantkey');

		if (!is_null($value) && trim($value) !== '') {
			return true;
		}

		// OK, it hasn't been updated yet, so do so
		$keyFile = ISC_BASE_PATH . "/modules/checkout/paysimple/lib/keyHalf.txt";

		if (!file_exists($keyFile)) {
			return true;
		}

		if (!is_readable($keyFile)) {
			$this->SetError('Unable to read the key file ' . GetConfig('AppPath') . '/modules/checkout/paysimple/lib/keyHalf.txt. Please CHMOD it to 646 or 666.');
			return false;
		}

		$newKey = @file_get_contents(ISC_BASE_PATH . "/modules/checkout/paysimple/lib/keyHalf.txt");
		$newKey = trim($newKey);

		if ($newKey == '') {
			return true;
		}

		// Make sure you get the 'static' part
		$newKey = Interspire_String::toUnixLineEndings($newKey);
		$newKey = explode("\n", $newKey);
		$newKey = $newKey[0];
		$module->setMerchantKey($newKey);

		return true;
	}
}