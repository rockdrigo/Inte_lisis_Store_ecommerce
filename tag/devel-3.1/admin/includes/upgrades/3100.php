<?php

class ISC_ADMIN_UPGRADE_3100 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		"add_product_purchase_columns",
		"add_customer_groups_permission",
		"add_customer_groups_table",
		"add_customer_groupid_column",
		"add_customer_group_discounts_table",
		"assignImportTrackingNoPermission",
		"add_transaction_table",
		"add_multiple_currencies",
		"add_ordpayproviderid_to_orders",
		"add_prodcatids_column",
		"build_prodcatids_columns"
	);

	public function add_product_purchase_columns()
	{
		$query = "ALTER TABLE `[|PREFIX|]products` ADD `prodallowpurchases` int(1) NOT NULL default '1';";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE `[|PREFIX|]products` ADD `prodhideprice` int(1) NOT NULL default '0';";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE `[|PREFIX|]products` ADD `prodcallforpricinglabel` varchar(200) NOT NULL default '';";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$updatedProducts = array(
			"prodallowpurchases" => 1
		);
		if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery("products", $updatedProducts)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// This step was successful, return true to tell the upgrader to move on
		return true;
	}

	public function add_customer_groups_permission()
	{
		$newPermission = array(
			"permuserid" => "1",
			"permpermissionid" => "165",
		);
		$GLOBALS['ISC_CLASS_DB']->InsertQuery("permissions", $newPermission);

		if($GLOBALS['ISC_CLASS_DB']->GetErrorMsg() == "") {
			// This step was successful, return true to tell the upgrader to move on
			return true;
		}
		else {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}
	}

	public function add_customer_groups_table()
	{
		$query = "CREATE TABLE `[|PREFIX|]customer_groups` (
		  `customergroupid` int(11) NOT NULL auto_increment,
		  `groupname` varchar(255) NOT NULL,
		  `discount` decimal(10,4) NOT NULL,
		  `isdefault` tinyint(4) NOT NULL,
		  `accesscategories` varchar(255) NOT NULL,
		  PRIMARY KEY  (`customergroupid`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
		";

		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// This step was successful, return true to tell the upgrader to move on
		return true;
	}

	public function add_customer_groupid_column()
	{
		$query = "ALTER TABLE `[|PREFIX|]customers` ADD `custgroupid` INT DEFAULT '0' NOT NULL AFTER `custregipaddress`;";
		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// This step was successful, return true to tell the upgrader to move on
		return true;
	}

	public function add_customer_group_discounts_table()
	{
		$query = "CREATE TABLE `[|PREFIX|]customer_group_discounts` (
		  `groupdiscountid` INT NOT NULL AUTO_INCREMENT ,
		  `customergroupid` INT NOT NULL ,
		  `discounttype` ENUM( 'CATEGORY', 'PRODUCT' ) NOT NULL ,
		  `catorprodid` INT NOT NULL ,
		  `discountpercent` DECIMAL( 10, 4 ) NOT NULL ,
		  `appliesto` ENUM( 'CATEGORY_ONLY', 'CATEGORY_AND_SUBCATS', 'NOT_APPLICABLE' ) NOT NULL ,
		PRIMARY KEY ( `groupdiscountid` )
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
		";

		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// This step was successful, return true to tell the upgrader to move on
		return true;
	}

	public function assignImportTrackingNoPermission()
	{
		$query = "SELECT pk_userid
					FROM [|PREFIX|]users";
		if (!$result = $GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}
		while ($user = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$query = "INSERT INTO [|PREFIX|]permissions (permuserid, permpermissionid)
						VALUES (".$user['pk_userid'].", '166')";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_transaction_table()
	{
		$query = "CREATE table IF NOT EXISTS [|PREFIX|]transactions (
			id int unsigned not null auto_increment PRIMARY KEY,
			orderid int unsigned default NULL,
			transactionid varchar(160) default NULL,
			providerid varchar(160),
		    amount DECIMAL(20, 4) NOT NULL,
			message text not null,
			status int unsigned default 0,
			transactiondate int not null,
			extrainfo text,
			KEY `i_order_transation` (orderid, transactionid),
			KEY `i_transaction_provider` (transactionid, providerid)
		) TYPE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
		";

		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// This step was successful, return true to tell the upgrader to move on
		return true;
	}

	public function add_multiple_currencies()
	{
		$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]currencies` (
			 `currencyid` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			 `currencycountryid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
			 `currencycode` CHAR(3) NOT NULL DEFAULT '',
			 `currencyconvertercode` VARCHAR(255) DEFAULT NULL,
			 `currencyname` varchar(255) NOT NULL DEFAULT '',
			 `currencyexchangerate` DECIMAL(20,10) NOT NULL DEFAULT 0,
			 `currencystring` CHAR(1) NOT NULL DEFAULT '',
			 `currencystringposition` CHAR(5) NOT NULL DEFAULT '',
			 `currencydecimalstring` CHAR(1) NOT NULL DEFAULT '',
			 `currencythousandstring` CHAR(1) NOT NULL DEFAULT '',
			 `currencydecimalplace` SMALLINT UNSIGNED NOT NULL DEFAULT 2,
			 `currencylastupdated` INT(11) NOT NULL DEFAULT 0,
			 `currencyisdefault` SMALLINT(1) NOT NULL DEFAULT 0,
			 `currencystatus` SMALLINT(1) NOT NULL DEFAULT 0,
			 PRIMARY KEY (`currencyid`),
			 UNIQUE KEY `u_currencies_currencycode_currencycountryid` (`currencycode`,`currencycountryid`),
			 KEY `i_countries_currencycountryid`(`currencycountryid`)
		)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
		";

		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		if (GetConfig('CompanyCountry') != "") {
			$countryname = GetConfig('CompanyCountry');
		} else {
			$countryname = GetLang('InstallDefaultCountryName');
		}

		$query = "SELECT * FROM `[|PREFIX|]countries` WHERE `countryname` = '" . $GLOBALS['ISC_CLASS_DB']->Quote($countryname) . "'";

		if (!($result = $GLOBALS['ISC_CLASS_DB']->Query($query))) {
			$this->SetError(GetLang('UpdateMissingCountryName'));
			return false;
		}

		$row		= $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		$countryId	= $row['countryid'];

		$defaultCurrency = array(
			'currencycountryid'			=> $countryId,
			'currencycode'				=> GetLang('USD'),
			'currencyname'				=> GetLang('InstallDefaultCurrencyName'),
			'currencyexchangerate'		=> 1,
			'currencystringposition'	=> strtolower(GetLang('InstallDefaultCurrencyStringPosition')),
			'currencylastupdated'		=> time(),
			'currencystatus'			=> 1,
			'currencyisdefault'			=> 1
		);

		if (GetConfig('CurrencyToken') !== '') {
			$defaultCurrency['currencystring'] = GetConfig('CurrencyToken');
		} else {
			$defaultCurrency['currencystring'] = GetLang('InstallDefaultCurrencyString');
		}

		if (GetConfig('DecimalToken') !== '') {
			$defaultCurrency['currencydecimalstring'] = GetConfig('DecimalToken');
		} else {
			$defaultCurrency['currencydecimalstring'] = GetLang('InstallDefaultCurrencyDecimalString');
		}

		if (GetConfig('ThousandsToken') !== '') {
			$defaultCurrency['currencythousandstring'] = GetConfig('ThousandsToken');
		} else {
			$defaultCurrency['currencythousandstring'] = GetLang('InstallDefaultCurrencyThousandString');
		}

		if (GetConfig('DecimalPlaces') !== '') {
			$defaultCurrency['currencydecimalplace'] = GetConfig('DecimalPlaces');
		} else {
			$defaultCurrency['currencydecimalplace'] = GetLang('InstallDefaultCurrencyDecimalPlace');
		}

		$defaultCurrencyId = $GLOBALS['ISC_CLASS_DB']->InsertQuery("currencies", $defaultCurrency);
		if (!isId($defaultCurrencyId)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$GLOBALS['ISC_NEW_CFG']['DefaultCurrencyID'] = $defaultCurrencyId;

		$query = "ALTER TABLE `[|PREFIX|]orders` ADD `ordcurrencyid` INT UNSIGNED NOT NULL DEFAULT 0;";
		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE `[|PREFIX|]orders` ADD `orddefaultcurrencyid` INT UNSIGNED NOT NULL DEFAULT 0;";
		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE `[|PREFIX|]orders` ADD `ordcurrencyexchangerate` DECIMAL(20,10) NOT NULL DEFAULT 0;";
		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$updatedOrders = array(
			"ordcurrencyid" => (int)$defaultCurrencyId,
			"orddefaultcurrencyid" => (int)$defaultCurrencyId,
			"ordcurrencyexchangerate" => 1
		);
		if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery("orders", $updatedOrders)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_ordpayproviderid_to_orders()
	{
		$query = 'ALTER TABLE `[|PREFIX|]orders` ADD `ordpayproviderid` VARCHAR( 255 ) DEFAULT NULL AFTER `orderpaymentmodule`' ;

		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// This step was successful, return true to tell the upgrader to move on
		return true;
	}

	public function add_prodcatids_column()
	{
		$query = 'ALTER TABLE `[|PREFIX|]products` ADD `prodcatids` TEXT NOT NULL';
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// This step was successful, return true to tell the upgrader to move on
		return true;
	}

	public function build_prodcatids_columns()
	{
		if (!isset($GLOBALS['ISC_CLASS_ADMIN_UPGRADE']->upgradeSession['prodcats_start'])) {
			$GLOBALS['ISC_CLASS_ADMIN_UPGRADE']->upgradeSession['prodcats_start'] = 0;
		}

		$query = "
			SELECT p.productid,
			(SELECT group_concat(ca.categoryid SEPARATOR ',') FROM [|PREFIX|]categoryassociations ca WHERE p.productid=ca.productid) AS categoryids
			FROM [|PREFIX|]products p
			ORDER BY p.productid
		";
		$perPage = 100;
		$done = 0;
		$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($GLOBALS['ISC_CLASS_ADMIN_UPGRADE']->upgradeSession['prodcats_start'], $perPage);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($product = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$updatedProduct = array(
				'prodcatids' => $product['categoryids']
			);
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('products', $updatedProduct, "productid='".$product['productid']."'");
			++$done;
		}

		if($GLOBALS['ISC_CLASS_DB']->GetErrorMsg()) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		if($done == 0) {
			// Nothing to update - continue
			return true;
		}
		// Need to run another iteration of this step
		else {
			$GLOBALS['ISC_CLASS_ADMIN_UPGRADE']->upgradeSession['prodcats_start'] += $done;
			return false;
		}
	}
}