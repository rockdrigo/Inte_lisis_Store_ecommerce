<?php

/**
 * Upgrade class for 6.0.7
 * This class runs a series of methods used to upgrade the store to a specific version
 *
 * @package ISC
 * @subpackage ISC_Upgrade
 */
class ISC_ADMIN_UPGRADE_6007 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		'renameEbayListingTemplatePrimaryCatIdColumn',
	);

	public function renameEbayListingTemplatePrimaryCatIdColumn ()
	{
		if ($this->ColumnExists('[|PREFIX|]ebay_listing_template', 'primary_cat_id')) {
			$query = "ALTER TABLE `[|PREFIX|]ebay_listing_template` CHANGE `primary_cat_id` `primary_category_id` VARCHAR( 11 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}
		return true;
	}
}
