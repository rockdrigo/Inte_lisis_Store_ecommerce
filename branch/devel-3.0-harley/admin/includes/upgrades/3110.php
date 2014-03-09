<?php

class ISC_ADMIN_UPGRADE_3110 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		"add_country_regions",
		"modify_currency_table",
		"fix_default_currency",
		"add_groups_permissions"
	);

	public function add_country_regions()
	{
		$query = "CREATE TABLE `[|PREFIX|]country_regions` (
		 `couregid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		 `couregname` VARCHAR(255) NOT NULL DEFAULT '',
		 `couregiso2` CHAR(2) NOT NULL DEFAULT '',
		 `couregiso3` CHAR(3) NOT NULL DEFAULT '',
		 PRIMARY KEY(`couregid`)
		)ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
		";

		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$data = array(
			"couregname" => "European Union",
			"couregiso2" => "EU",
			"couregiso3" => "EUR"
		);

		if(!isId($regionid = $GLOBALS['ISC_CLASS_DB']->InsertQuery("country_regions", $data))) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE `[|PREFIX|]currencies` MODIFY `currencycountryid` INT(11) UNSIGNED DEFAULT NULL";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE `[|PREFIX|]currencies` ADD `currencycouregid` INT(11) UNSIGNED DEFAULT NULL AFTER `currencycountryid`";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE `[|PREFIX|]currencies` DROP KEY `u_currencies_currencycode_currencycountryid`";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE `[|PREFIX|]currencies` ADD UNIQUE KEY `u_currencies_currencycode_currencycountryid_currencycouregid` (`currencycode`,`currencycountryid`, `currencycouregid`)";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE `[|PREFIX|]countries` ADD `countrycouregid` INT(11) UNSIGNED DEFAULT NULL AFTER `countryid`";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE `[|PREFIX|]countries` ADD KEY `i_regions_countrycouregid` (`countrycouregid`)";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "
		UPDATE `[|PREFIX|]countries`
		SET `countrycouregid` = " . $regionid . "
		WHERE LOWER(countryname) IN ('austria', 'belgium', 'bulgaria', 'finland', 'france', 'germany', 'greece', 'ireland', 'italy', 'luxembourg', 'netherlands', 'portugal', 'spain')";

		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function modify_currency_table()
	{
		$query = "ALTER TABLE `[|PREFIX|]currencies` MODIFY `currencystring` VARCHAR(20) NOT NULL DEFAULT ''";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function fix_default_currency()
	{
		$query = "UPDATE `[|PREFIX|]currencies` SET `currencystatus`='1' WHERE `currencyisdefault`='1'";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_groups_permissions()
	{
		$query = "SELECT pk_userid FROM [|PREFIX|]users";
		if (!$result = $GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}
		while ($user = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$insertPermission = array(
				'permuserid' => $user['pk_userid'],
				'permpermissionid' => AUTH_Customer_Groups,
			);
			if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery('permissions', $insertPermission)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}
}