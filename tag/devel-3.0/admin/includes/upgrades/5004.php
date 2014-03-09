<?php
class ISC_ADMIN_UPGRADE_5004 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		'fix_coupons'
	);

	public function fix_coupons()
	{
		// change the table name of isc_coupon_values created in old 5.0.3 upgrade to correct prefix
		if ($this->TableExists('isc_coupon_values', false) && GetConfig('tablePrefix') != "isc_" && !$this->TableExists('coupon_values')) {
			$query = "ALTER TABLE `isc_coupon_values` RENAME `[|PREFIX|]coupon_values`";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		// fixes situation where this column exists for new 5.0.3 installs
		if ($this->ColumnExists('[|PREFIX|]coupons', 'couponappliestovalues')) {
			$query = "ALTER TABLE [|PREFIX|]coupons DROP COLUMN couponappliestovalues";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}
}