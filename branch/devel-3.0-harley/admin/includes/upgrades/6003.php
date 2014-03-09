<?php

/**
 * Upgrade class for 6.0.3
 * This class runs a series of methods used to upgrade the store to a specific version
 *
 * @package ISC
 * @subpackage ISC_Upgrade
 */
class ISC_ADMIN_UPGRADE_6003 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		'add_salt_column_to_customers_table',
		'add_combinations_producthash_index',
	);

	public function add_salt_column_to_customers_table()
	{
		if ($this->ColumnExists('[|PREFIX|]customers', 'salt') == false) {
			$query = "ALTER TABLE [|PREFIX|]customers ADD COLUMN salt varchar(16) NOT NULL default ''";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_combinations_producthash_index()
	{
		if ($this->IndexExists('[|PREFIX|]product_variation_combinations', 'i_product_variation_combinations_vcproducthash')) {
			return true;
		}

		$query = "ALTER TABLE `[|PREFIX|]product_variation_combinations` ADD INDEX `i_product_variation_combinations_vcproducthash` (`vcproducthash`)";
		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}
}
