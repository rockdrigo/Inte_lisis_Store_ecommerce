<?php

/**
 * Upgrade class for 6.0.15
 * This class runs a series of methods used to upgrade the store to a specific version
 *
 * @package ISC
 * @subpackage ISC_Upgrade
 */
class ISC_ADMIN_UPGRADE_6015 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		'addEbayTransactionIdToOrderProductsTable',
	);

	public function addEbayTransactionIdToOrderProductsTable()
	{
		// add attempt_lockout timestamp col to users table
		if ($this->ColumnExists('[|PREFIX|]order_products', 'ebay_transaction_id') == false) {
			$query = "ALTER TABLE `[|PREFIX|]order_products` ADD COLUMN `ebay_transaction_id` varchar(19) NOT NULL DEFAULT ''";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}
}