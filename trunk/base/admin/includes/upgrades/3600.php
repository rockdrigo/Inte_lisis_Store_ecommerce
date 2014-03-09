<?php
class ISC_ADMIN_UPGRADE_3600 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		'add_customers_and_orders_note_columns',
		'change_product_warranty_to_text',
		'image_column_for_category_and_brands',
		"add_ordcustmessage_to_orders",
		"add_catvisible_to_categories",
		"add_tax_address_column",
		'add_south_african_states'
	);

	public function add_customers_and_orders_note_columns()
	{
		if (!$this->ColumnExists('[|PREFIX|]customers', 'custnotes')) {
			if (!$GLOBALS['ISC_CLASS_DB']->Query("ALTER TABLE `[|PREFIX|]customers` ADD `custnotes` TEXT")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]orders', 'ordnotes')) {
			if (!$GLOBALS['ISC_CLASS_DB']->Query("ALTER TABLE `[|PREFIX|]orders` ADD `ordnotes` TEXT")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function change_product_warranty_to_text()
	{
		if (!$GLOBALS['ISC_CLASS_DB']->Query("ALTER TABLE `[|PREFIX|]products` MODIFY `prodwarranty` TEXT")) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function image_column_for_category_and_brands()
	{
		if (!$this->ColumnExists('[|PREFIX|]brands', 'brandimagefile')) {
			if (!$GLOBALS['ISC_CLASS_DB']->Query("ALTER TABLE `[|PREFIX|]brands` ADD `brandimagefile` VARCHAR(255) NOT NULL DEFAULT ''")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]categories', 'catimagefile')) {
			if (!$GLOBALS['ISC_CLASS_DB']->Query("ALTER TABLE `[|PREFIX|]categories` ADD `catimagefile` VARCHAR(255) NOT NULL DEFAULT ''")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_ordcustmessage_to_orders()
	{
		if (!$this->ColumnExists('[|PREFIX|]orders', 'ordcustmessage')) {
			$query = "ALTER TABLE `[|PREFIX|]orders` ADD `ordcustmessage` TEXT";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_tax_address_column()
	{
		if (!$this->ColumnExists('[|PREFIX|]tax_rates', 'taxaddress')) {
			$query = "ALTER TABLE [|PREFIX|]tax_rates ADD `taxaddress` enum('billing','shipping') NOT NULL default 'billing' AFTER taxratebasedon";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_catvisible_to_categories()
	{
		if (!$this->ColumnExists('[|PREFIX|]categories', 'catvisible')) {
			$query = "ALTER TABLE `[|PREFIX|]categories` ADD `catvisible` TINYINT NOT NULL DEFAULT 1";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_south_african_states()
	{
		// Delete any previous records for South African provinces if there are any
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('country_states', 'WHERE statecountry=197');

		$queries = array(
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Eastern Cape', 197, 'EC');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Free State', 197, 'FS');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Gauteng', 197, 'GT');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('KwaZulu-Natal', 197, 'NL');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Limpopo', 197, 'LP');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Mpumalanga', 197, 'MP');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Northern Cape', 197, 'NC');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('North-West', 197, 'NW');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Western Cape', 197, 'WC');"
		);
		foreach($queries as $query) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}
}