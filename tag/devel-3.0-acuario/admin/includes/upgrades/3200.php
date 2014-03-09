<?php
class ISC_ADMIN_UPGRADE_3200 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		"add_order_products_index",
		"add_quickbooks_config",
		"add_order_shipping_zone_columns",
		"create_shipping_zone_tables",
		"convert_shipping_to_zones",
		"add_order_ship_phone_and_email",
		"add_prodlastupdated_column",
		"add_malaysia_states",
		"multi_wishlist",
		"add_ship_lastused",
		"change_fields_from_text_to_longtext",
		"change_field_from_varchar_to_text",
		'add_order_downloads_columns',
		'convert_order_downloads',
	);

	public function add_order_downloads_columns()
	{
		$queries = array(
			"ALTER TABLE [|PREFIX|]order_downloads ADD downloadexpires int unsigned NOT NULL default '0'",
			"ALTER TABLE [|PREFIX|]order_downloads ADD maxdownloads int unsigned NOT NULL default '0'"
		);
		foreach($queries as $query) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function convert_order_downloads()
	{
		// For every record in the order_downloads table, we need to set the downloadexpires and maxdownloads column values
		$query = "
			SELECT od.orddownid, d.downexpiresafter, d.downmaxdownloads, od.orderid, o.orddate
			FROM [|PREFIX|]order_downloads od
			INNER JOIN [|PREFIX|]orders o ON (o.orderid=od.orderid)
			INNER JOIN [|PREFIX|]product_downloads d ON (d.downloadid=od.downloadid)
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($orderDownload = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$updatedDownload = array();
			if($orderDownload['downexpiresafter'] > 0) {
				$updatedDownload['downloadexpires'] = $orderDownload['orddate'] + $orderDownload['downexpiresafter'];
			}

			if($orderDownload['downmaxdownloads'] > 0) {
				$updatedDownload['maxdownloads'] = $orderDownload['downmaxdownloads'];
			}

			if(!empty($updatedDownload)) {
				if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('order_downloads', $updatedDownload, "orddownid='".$orderDownload['orddownid']."'")) {
					$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
					return false;
				}
			}
		}

		return true;
	}

	public function add_order_products_index()
	{
		$query = "ALTER TABLE `[|PREFIX|]order_products` ADD KEY `i_order_products_orderid_prodid` (`orderorderid`, `ordprodid`)";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_quickbooks_config()
	{
		$query = "
			CREATE TABLE `[|PREFIX|]accountingref` (
			  `accountingrefid` int(11) NOT NULL auto_increment,
			  `accountingrefmoduleid` varchar(100) NOT NULL default '',
			  `accountingrefnodeid` int(11) NOT NULL DEFAULT 0,
			  `accountingreftype` enum('customer','customergroup','product','order','salestaxcode','account') NOT NULL,
			  `accountingrefvalue` TEXT,
			  PRIMARY KEY  (`accountingrefid`),
			  KEY `i_accountingref_accountingrefmoduleid` (`accountingrefmoduleid`),
			  KEY `i_accountingref_accountingrefnodeid` (`accountingrefnodeid`),
			  KEY `i_accountingref_accountingreftype` (`accountingreftype`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
		";

		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_order_shipping_zone_columns()
	{
		$query = "ALTER TABLE [|PREFIX|]orders ADD `ordshippingzoneid` INT(11) UNSIGNED NOT NULL DEFAULT '0'";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE [|PREFIX|]orders ADD `ordshippingzone` VARCHAR(200) NOT NULL DEFAULT ''";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}
		return true;
	}

	public function create_shipping_zone_tables()
	{
		$query = "
			CREATE TABLE IF NOT EXISTS `[|PREFIX|]shipping_methods` (
			  `methodid` int(10) unsigned NOT NULL auto_increment,
			  `zoneid` int(10) unsigned NOT NULL default '0',
			  `methodname` varchar(150) NOT NULL default '',
			  `methodmodule` varchar(100) NOT NULL default '',
			  `methodhandlingfee` decimal(20,4) NOT NULL default '0.0000',
			  `methodenabled` int(1) NOT NULL default '1',
			  PRIMARY KEY  (`methodid`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "
			CREATE TABLE IF NOT EXISTS `[|PREFIX|]shipping_vars` (
			  `variableid` int(11) NOT NULL auto_increment,
			  `methodid` int(10) unsigned NOT NULL default '0',
			  `zoneid` int(10) unsigned NOT NULL default '0',
			  `modulename` varchar(100) NOT NULL default '',
			  `variablename` varchar(100) NOT NULL default '',
			  `variableval` text,
			  PRIMARY KEY  (`variableid`),
			  KEY `modulename` (`modulename`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "
			CREATE TABLE IF NOT EXISTS `[|PREFIX|]shipping_zones` (
			  `zoneid` int(10) unsigned NOT NULL auto_increment,
			  `zonename` varchar(100) NOT NULL default '',
			  `zonetype` enum('country','state','zip') default 'country',
			  `zonefreeshipping` int(1) NOT NULL default '0',
			  `zonefreeshippingtotal` decimal(20,4) NOT NULL default '0.0000',
			  `zonehandlingtype` enum('none','global','module') default 'none',
			  `zonehandlingfee` decimal(20,4) NOT NULL default '0.0000',
			  `zonehandlingseparate` int(1) NOT NULL default '1',
			  `zoneenabled` int(1) NOT NULL default '1',
			  PRIMARY KEY  (`zoneid`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "
			CREATE TABLE IF NOT EXISTS `[|PREFIX|]shipping_zone_locations` (
			  `locationid` int(10) unsigned NOT NULL auto_increment,
			  `zoneid` int(10) unsigned NOT NULL default '0',
			  `locationtype` enum('country','state','zip') default 'country',
			  `locationvalueid` int(10) unsigned NOT NULL default '0',
			  `locationvalue` varchar(100) NOT NULL default '0',
			  `locationcountryid` int(10) unsigned NOT NULL default '0',
			  PRIMARY KEY  (`locationid`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// Insert the default/master shipping zone
		$GLOBALS['ISC_CLASS_DB']->Query('TRUNCATE [|PREFIX|]shipping_zones');
		$masterZone = array(
			'zonename' => 'Default Zone',
			'zonetype' => 'country',
			'zonefreeshipping' => 0,
			'zonefreeshippingtotal' => 0,
			'zonehandlingtype' => 'none',
			'zonehandlingfee' => 0,
			'zonehandlingseparate' => 1,
			'zoneenabled' => 1
		);
		if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery('shipping_zones', $masterZone)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function convert_shipping_to_zones()
	{
		// Truncate a few tables just incase we've got half way through here before and failed
		$GLOBALS['ISC_CLASS_DB']->Query("TRUNCATE [|PREFIX|]shipping_zone_locations");
		$GLOBALS['ISC_CLASS_DB']->Query("TRUNCATE [|PREFIX|]shipping_vars");
		$GLOBALS['ISC_CLASS_DB']->Query("TRUNCATE [|PREFIX|]shipping_methods");

		// Is free shipping enabled?
		$freeShipping = $freeShippingThreshold = 0;
		if(strpos(GetConfig('ShippingMethods'), 'shipping_freeshipping') !== false) {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemDebug('general', 'Free Shipping on');
			$query = "SELECT variableval FROM [|PREFIX|]module_vars WHERE modulename='shipping_freeshipping' AND variablename='threshhold'";
			$freeShippingThreshold = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);
			$GLOBALS['ISC_CLASS_LOG']->LogSystemDebug('general', 'Threshold '.$freeShippingThreshold);
			$freeShipping = 1;
		}

		// Handling?
		$handlingType = 'none';
		$handlingFee = $handlingSeparate = 0;
		if(GetConfig('HandlingFee') > 0) {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemDebug('general', 'Handling Fee');
			$handlingType = 'global';
			$handlingFee = GetConfig('HandlingFee');
			$handlingSeparate = GetConfig('ShowHandlingFeeSeparately');
		}

		// Have a handling fee or shipping fee, update the default zone to also have this
		if($handlingFee || $freeShipping) {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemDebug('general', 'ZZZZZ');

			$updatedZone = array(
				'zonefreeshipping' => $freeShipping,
				'zonefreeshippingtotal' => $freeShippingThreshold,
				'zonehandlingtype' => $handlingType,
				'zonehandlingfee' => $handlingFee,
				'zonehandlingseparate' => $handlingSeparate
			);
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('shipping_zones', $updatedZone, 'zoneid=1');
		}

		// If we have shipping per country on, we need to create multiple zones
		if(strpos(GetConfig('ShippingMethods'), 'shipping_percountry') !== false) {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemDebug('general', 'Per Country');

			$query = "SELECT * FROM [|PREFIX|]module_vars WHERE modulename='shipping_percountry'";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while($country = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				if(strpos($country['variablename'], 'flatrateforcountry') === false) {
					continue;
				}
				$countryId = str_replace('flatrateforcountry', '', $country['variablename']);
				$countryName = GetCountryById($countryId);
				$newZone = array(
					'zonename' => $countryName,
					'zonetype' => 'country',
					'zonefreeshipping' => $freeShipping,
					'zonefreeshippingtotal' => $freeShippingThreshold,
					'zonehandlingtype' => $handlingType,
					'zonehandlingfee' => $handlingFee,
					'zonehandlingseparate' => $handlingSeparate,
					'zoneenabled' => 1
				);
				$zoneId = $GLOBALS['ISC_CLASS_DB']->InsertQuery('shipping_zones', $newZone);

				// Insert the location
				$newLocation = array(
					'zoneid' => $zoneId,
					'locationtype' => 'country',
					'locationvalueid' => $countryId,
					'locationvalue' => $countryName
				);
				$GLOBALS['ISC_CLASS_DB']->InsertQuery('shipping_zone_locations', $newLocation);

				// Insert a shipping method
				$newMethod = array(
					'methodname' => 'Flat Rate Per Order',
					'methodmodule' => 'flatrate',
					'zoneid' => $zoneId,
					'methodenabled' => 1
				);

				$methodId = $GLOBALS['ISC_CLASS_DB']->InsertQuery('shipping_methods', $newMethod);

				// Now insert the value
				$newValue = array(
					'methodid' => $methodId,
					'zoneid' => $zoneId,
					'modulename' => 'flatrate',
					'variablename' => 'shippingcost',
					'variableval' => $country['variableval']
				);
				$GLOBALS['ISC_CLASS_DB']->InsertQuery('shipping_vars', $newValue);
			}
		}
		$GLOBALS['ISC_CLASS_LOG']->LogSystemDebug('general', 'Types'.GetConfig('ShippingMethods'));

		$shippingTypes = explode(',', GetConfig('ShippingMethods'));
		foreach($shippingTypes as $shipper) {
			// We've already dealt with these - don't bother us with them again
			if($shipper == 'shipping_percountry' || $shipper == 'shipping_freeshipping') {
				continue;
			}

			$GLOBALS['ISC_CLASS_LOG']->LogSystemDebug('general', 'Config for '.$shipper);


			// Oh now this is going to be fun - we need to instantiate the module to get the name
			GetModuleById('shipping', $objModule, $shipper);
			if(!is_object($objModule)) {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemDebug('general', "Couldn't load ".$shipper);
				continue;
			}

			// Insert a record for this shipping method
			$newMethod = array(
				'methodname' => $objModule->GetName(),
				'methodmodule' => $shipper,
				'zoneid' => 1,
				'methodenabled' => 1
			);

			$methodId = $GLOBALS['ISC_CLASS_DB']->InsertQuery('shipping_methods', $newMethod);
			$GLOBALS['ISC_CLASS_LOG']->LogSystemDebug('general', 'Created method '.$methodId.' for '.$shipper);

			// Fetch out any configuration variables
			$query = "SELECT * FROM [|PREFIX|]module_vars WHERE modulename='".$shipper."'";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while($variable = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				// Now insert the value
				$newValue = array(
					'methodid' => $methodId,
					'zoneid' => 1,
					'modulename' => $variable['modulename'],
					'variablename' => $variable['variablename'],
					'variableval' => $variable['variableval']
				);
				$GLOBALS['ISC_CLASS_DB']->InsertQuery('shipping_vars', $newValue);
			}
		}

		// If there was an error message, return false!
		if($GLOBALS['ISC_CLASS_DB']->GetErrorMsg()) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// If we're still here - delete all the old variables
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('module_vars', "WHERE modulename LIKE 'shipping_%'");

		return true;
	}

	public function add_order_ship_phone_and_email()
	{
		$query = "ALTER TABLE `[|PREFIX|]orders` ADD `ordshipphone` VARCHAR( 50 ) NOT NULL AFTER `ordshipstateid` ,
		ADD `ordshipemail` VARCHAR( 250 ) NOT NULL AFTER `ordshipphone`" ;

		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_prodlastupdated_column()
	{
		$query = "ALTER TABLE [|PREFIX|]products ADD prodlastmodified int unsigned NOT NULL default '0'";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_malaysia_states()
	{
		$queries = array(
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Johor', 129, 'JHR');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Kedah', 129, 'KDH');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Kelantan', 129, 'KTN');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Melaka', 129, 'MLK');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Negeri Sembilan', 129, 'NSN');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Pahang', 129, 'PHG');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Pulau Pinang', 129, 'PNG');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Perak', 129, 'PRK');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Perlis', 129, 'PLS');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Selangor', 129, 'SGR');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Terengganu', 129, 'TRG');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Sabah', 129, 'SBH');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Sarawak', 129, 'SRW');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Kuala Lumpur', 129, 'KUL');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Labuan', 129, 'LBN');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Putrajaya', 129, 'PJY');",
			"INSERT INTO `[|PREFIX|]country_states` (`statename`, `statecountry`, `stateabbrv`) VALUES ('Labuan', 129, 'JHR');"
		);
		foreach($queries as $query) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function multi_wishlist()
	{
		//rename the current wishlists table to wishlist_items
		$query = "RENAME TABLE [|PREFIX|]wishlists TO [|PREFIX|]wishlist_items;";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// rename the wishlistid in to wishlistitemid
		$query = "ALTER TABLE [|PREFIX|]wishlist_items CHANGE COLUMN wishlistid wishlistitemid int(11) NOT NULL auto_increment;";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// add wishlistid column to wishlist_items table
		$query = "ALTER TABLE [|PREFIX|]wishlist_items ADD COLUMN wishlistid int(11) NOT NULL AFTER wishlistitemid;";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// creat wishlists table
		$query = "CREATE TABLE `[|PREFIX|]wishlists` (
					`wishlistid` INT( 11 ) NOT NULL AUTO_INCREMENT ,
					`customerid` INT( 11 ) NOT NULL ,
					`wishlistname` VARCHAR( 255 ) NOT NULL ,
					`ispublic` TINYINT NOT NULL ,
					PRIMARY KEY ( `wishlistid` )
				);";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// insert default wishlists to wishlist table for the customers who currently have wishlist items
		// update the wishlist id in wishlist_item table according to customer id
		$query = "SELECT distinct(customerid) as custid from `[|PREFIX|]wishlist_items` order by customerid";
		if(!$result = $GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}
		while ($custid = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$insertWishList = array(
				"customerid"	=> $custid['custid'],
				"wishlistname"	=> 'My Wish List',
				"ispublic"		=> 0
			);

			if(!$wishListID=$GLOBALS['ISC_CLASS_DB']->InsertQuery('wishlists', $insertWishList)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

			$updatedWishListItems = array(
				'wishlistid' => $wishListID,
			);
			if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('wishlist_items', $updatedWishListItems, "customerid='".$custid['custid']."'")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		// drop the customerid field in wishlist_items table, as it's in wishlists table
		$query = "ALTER TABLE `[|PREFIX|]wishlist_items` DROP `customerid`;";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}
		return true;
	}

	public function add_ship_lastused()
	{
		$query = "ALTER TABLE [|PREFIX|]shipping_addresses ADD `shiplastused` int(11) NOT NULL default '0'";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}
		return true;
	}

	public function change_fields_from_text_to_longtext()
	{
		$queries = array();
		$queries[] = 'ALTER TABLE [|PREFIX|]banners CHANGE content content LONGTEXT';
		$queries[] = 'ALTER TABLE [|PREFIX|]custom_searches CHANGE searchvars searchvars LONGTEXT';
		$queries[] = 'ALTER TABLE [|PREFIX|]news CHANGE newscontent newscontent LONGTEXT';
		$queries[] = 'ALTER TABLE [|PREFIX|]order_messages CHANGE message message LONGTEXT';
		$queries[] = 'ALTER TABLE [|PREFIX|]pages CHANGE pagecontent pagecontent LONGTEXT';
		$queries[] = 'ALTER TABLE [|PREFIX|]product_search CHANGE proddesc proddesc LONGTEXT';
		$queries[] = 'ALTER TABLE [|PREFIX|]products CHANGE proddesc proddesc LONGTEXT';
		$queries[] = 'ALTER TABLE [|PREFIX|]sessions CHANGE sessdata sessdata LONGTEXT';
		$queries[] = 'ALTER TABLE [|PREFIX|]system_log CHANGE logmsg logmsg LONGTEXT';
		foreach ($queries as $query) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function change_field_from_varchar_to_text()
	{
		$query = 'ALTER TABLE [|PREFIX|]categories CHANGE catdesc catdesc TEXT';
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
		$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}
		return true;
	}
}