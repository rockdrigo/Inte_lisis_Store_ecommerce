<?php

class ISC_ADMIN_UPGRADE_1300 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		"add_taxable_field"
	);

	public function add_taxable_field()
	{
		$query = "ALTER TABLE [|PREFIX|]products ADD prodistaxable tinyint(1) NOT NULL default '1' AFTER prodcalculatedprice;";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$updatedProducts = array(
			"prodistaxable" => 1
		);
		if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery("products", $updatedProducts)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// This step was successful, return true to tell the upgrader to move on
		return true;
	}
}