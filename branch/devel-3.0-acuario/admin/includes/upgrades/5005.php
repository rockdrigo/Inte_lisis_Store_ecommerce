<?php
class ISC_ADMIN_UPGRADE_5005 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		'add_product_condition'
	);

	public function add_product_condition()
	{
		if (!$this->ColumnExists('[|PREFIX|]products', 'prodcondition')) {
			$query = "ALTER TABLE `[|PREFIX|]products` ADD `prodcondition` ENUM('New','Used','Refurbished') NOT NULL DEFAULT 'New'";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]products', 'prodshowcondition')) {
			$query = "ALTER TABLE `[|PREFIX|]products` ADD `prodshowcondition` TINYINT(1) NOT NULL DEFAULT '0'";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}
}