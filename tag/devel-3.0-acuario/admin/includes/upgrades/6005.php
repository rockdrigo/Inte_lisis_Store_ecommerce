<?php

/**
 * Upgrade class for 6.0.5
 * This class runs a series of methods used to upgrade the store to a specific version
 *
 * @package ISC
 * @subpackage ISC_Upgrade
 */
class ISC_ADMIN_UPGRADE_6005 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		'update_ebay_template_category_columns',
	);

	public function update_ebay_template_category_columns()
	{
		if ($this->ColumnExists('[|PREFIX|]ebay_listing_template', 'secondary_cat_id')) {
			$query = "ALTER TABLE `[|PREFIX|]ebay_listing_template` CHANGE `secondary_cat_id` `secondary_category_id` VARCHAR( 11 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]ebay_listing_template', 'secondary_category_name')) {
			$query = "ALTER TABLE [|PREFIX|]ebay_listing_template ADD COLUMN secondary_category_name varchar(30) NOT NULL DEFAULT ''";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		if ($this->ColumnExists('[|PREFIX|]ebay_listing_template', 'store_category1')) {
			$query = "ALTER TABLE `[|PREFIX|]ebay_listing_template` CHANGE `store_category1` `store_category1_id` VARCHAR( 11 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]ebay_listing_template', 'store_category1_name')) {
			$query = "ALTER TABLE [|PREFIX|]ebay_listing_template ADD COLUMN store_category1_name varchar(30) NOT NULL DEFAULT ''";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		if ($this->ColumnExists('[|PREFIX|]ebay_listing_template', 'store_category2')) {
			$query = "ALTER TABLE `[|PREFIX|]ebay_listing_template` CHANGE `store_category2` `store_category2_id` VARCHAR( 11 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]ebay_listing_template', 'store_category2_name')) {
			$query = "ALTER TABLE [|PREFIX|]ebay_listing_template ADD COLUMN store_category2_name varchar(30) NOT NULL DEFAULT ''";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}
}
