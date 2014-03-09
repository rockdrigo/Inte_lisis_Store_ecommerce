<?php

class ISC_ADMIN_UPGRADE_1500 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		"add_page_customers_only",
	);

	public function add_page_customers_only()
	{
		$query = "ALTER TABLE `[|PREFIX|]pages` ADD `pagecustomersonly` TINYINT(1) NOT NULL default '0';";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// This step was successful, return true to tell the upgrader to move on
		return true;
	}
}