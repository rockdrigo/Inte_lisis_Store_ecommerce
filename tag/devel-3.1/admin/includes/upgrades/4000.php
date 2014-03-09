<?php
class ISC_ADMIN_UPGRADE_4000 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		'add_vendor_tables',
		'add_user_roles',
		'add_product_vendorid',
		'add_product_variations_vendorid',
		'add_orders_vendorid',
		'add_returns_vendorid',
		'add_pages_vendorid',
		'add_new_permissions',
		'add_shipping_fields',
		'convert_shipping_names',
		'drop_shipfullname',
		'convert_order_names',
		'drop_ordernames',
		'add_product_tag_tables',
		'add_vendornumsold_vendorfriendlyname',
		'add_vendororderemail',
		'add_giftwrapping_table',
		'add_giftwrapping_products',
		'add_giftwrapping_orders',
		'add_shipping_address_admin_fields',
		'add_vendor_shipping_support',
		'add_shipment_tables',
		'add_shipment_columns',
		'update_order_totals',
		'add_shipments_custom_type',
		'add_product_discount_table',
		'add_vendor_payments_table',
		'add_vendor_email',
		'add_customer_password_token',
		'add_configurable_fields_table',
		'add_vendor_cat_restrictions',
		'add_vendor_image_columns',
		'add_product_vendor_featured',
		'delayed_capture_payment',
		'add_vendor_profitmargin',
		'add_prodconfigfields',
		'add_product_variation_indexes',
		'convert_accounting_spool',
		'add_product_visible_indexes',
		'add_category_association_indexes',
		'alter_product_image_index'
	);

	public function pre_upgrade_checks()
	{
		if(is_dir(ISC_BASE_PATH."/modules/analytics/trackpoint")) {
			$this->SetError('Please delete the /modules/analytics/trackpoint directory from your store. This module has been removed and is no longer necessary.');
		}

		if(is_dir(ISC_BASE_PATH."/modules/shipping/freeshipping")) {
			$this->SetError('Please delete the /modules/shipping/freeshipping directory from your store. This module has been renamed and is no longer necessary.');
		}
	}

	public function add_vendor_tables()
	{
		if (!$this->TableExists('vendors')) {
			$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]vendors` (
				`vendorid` int unsigned NOT NULL auto_increment,
				`vendorname` varchar(200) NOT NULL default '',
				`vendorphone` varchar(50) NOT NULL default '',
				`vendorbio` text NOT NULL,
				`vendoraddress` varchar(200) NOT NULL default '',
				`vendorcity` varchar(100) NOT NULL default '',
				`vendorcountry` varchar(100) NOT NULL default '',
				`vendorstate` varchar(100) NOT NULL default '',
				`vendorzip` varchar(20) NOT NULL default '',
				PRIMARY KEY(vendorid)
			);";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]users', 'uservendorid')) {
			$query = "ALTER TABLE [|PREFIX|]users ADD uservendorid int unsigned NOT NULL default '0'";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_user_roles()
	{
		if (!$this->ColumnExists('[|PREFIX|]users', 'userrole')) {
			$query = "ALTER TABLE [|PREFIX|]users ADD `userrole` varchar(20) NOT NULL default 'custom';";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		$updatedUsers = array(
			'userrole' => 'custom'
		);
		if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('users', $updatedUsers)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}
		return true;
	}

	public function add_product_vendorid()
	{
		if (!$this->ColumnExists('[|PREFIX|]products', 'prodvendorid')) {
			$query = "ALTER TABLE [|PREFIX|]products ADD `prodvendorid` int unsigned NOT NULL default '0' AFTER prodlastmodified";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_product_variations_vendorid()
	{
		if (!$this->ColumnExists('[|PREFIX|]product_variations', 'vvendorid')) {
			$query = "ALTER TABLE [|PREFIX|]product_variations ADD `vvendorid` int unsigned NOT NULL default '0'";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_orders_vendorid()
	{
		if (!$this->ColumnExists('[|PREFIX|]orders', 'ordvendorid')) {
			$query = "ALTER TABLE [|PREFIX|]orders ADD `ordvendorid` int unsigned NOT NULL default '0' AFTER ordnotes";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_returns_vendorid()
	{
		if (!$this->ColumnExists('[|PREFIX|]returns', 'retvendorid')) {
			$query = "ALTER TABLE [|PREFIX|]returns ADD `retvendorid` int unsigned NOT NULL default '0' AFTER retstaffnotes";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_pages_vendorid()
	{
		if (!$this->ColumnExists('[|PREFIX|]pages', 'pagevendorid')) {
			$query = "ALTER TABLE [|PREFIX|]pages ADD `pagevendorid` int unsigned NOT NULL default '0' AFTER pagecustomersonly";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_new_permissions()
	{
		// Array of new permission => insert in to users that have the following permission
		$perms = array(
			AUTH_Manage_Vendors => AUTH_Manage_Users,
			AUTH_Add_Vendors => AUTH_Manage_Users,
			AUTH_Edit_Vendors => AUTH_Manage_Users,
			AUTH_Delete_Vendors => AUTH_Manage_Users,

			AUTH_Statistics_Products => AUTH_Statistics_Overview,
			AUTH_Statistics_Orders => AUTH_Statistics_Overview,
			AUTH_Statistics_Customers => AUTH_Statistics_Overview,
			AUTH_Statistics_Search => AUTH_Statistics_Overview,

			AUTH_System_Info => AUTH_Manage_Logs
		);

		// Delete any existing occurances of these permissions
		$permIds = array_keys($perms);
		$permIds = implode(',', $permIds);
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('permissions', "WHERE permpermissionid IN (".$permIds.")");

		// OK, do some magic.. well it's not actually manage nor is it anything cool, but you get the point.
		foreach($perms as $permission => $insertWhere) {
			$query = $GLOBALS['ISC_CLASS_DB']->Query("SELECT permuserid FROM [|PREFIX|]permissions WHERE permpermissionid='".(int)$insertWhere."'");
			while($user = $GLOBALS['ISC_CLASS_DB']->Fetch($query)) {
				$newPermission = array(
					'permuserid' => $user['permuserid'],
					'permpermissionid' => $permission
				);
				if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery('permissions', $newPermission)) {
					$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
					return false;
				}
			}
		}

		return true;
	}

	public function add_shipping_fields()
	{
		if($this->ColumnExists('[|PREFIX|]shipping_addresses', 'shipfirstname')) {
			return true;
		}

		$queries = array(
			"ALTER TABLE [|PREFIX|]shipping_addresses ADD shipfirstname varchar(100) NOT NULL default '' AFTER shipcustomerid",
			"ALTER TABLE [|PREFIX|]shipping_addresses ADD shiplastname varchar(100) NOT NULL default '' AFTER shipfirstname",
			"ALTER TABLE [|PREFIX|]shipping_addresses ADD shipcompany varchar(100) NOT NULL default '' AFTER shiplastname",

			"ALTER TABLE [|PREFIX|]orders ADD ordbillfirstname varchar(100) NOT NULL default '' AFTER ordbillfullname",
			"ALTER TABLE [|PREFIX|]orders ADD ordbilllastname varchar(100) NOT NULL default '' AFTER ordbillfirstname",
			"ALTER TABLE [|PREFIX|]orders ADD ordbillcompany varchar(100) NOT NULL default '' AFTER ordbilllastname",

			"ALTER TABLE [|PREFIX|]orders ADD ordshipfirstname varchar(100) NOT NULL default '' AFTER ordshipfullname",
			"ALTER TABLE [|PREFIX|]orders ADD ordshiplastname varchar(100) NOT NULL default '' AFTER ordshipfirstname",
			"ALTER TABLE [|PREFIX|]orders ADD ordshipcompany varchar(100) NOT NULL default '' AFTER ordshiplastname",
		);
		foreach($queries as $query) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function convert_shipping_names()
	{
		if(!$this->ColumnExists('[|PREFIX|]shipping_addresses', 'shipfullname')) {
			return true;
		}

		if(!isset($_SESSION['shipStart'])) {
			$_SESSION['shipStart'] = 0;
		}

		$query = "
			SELECT shipid, shipfullname
			FROM [|PREFIX|]shipping_addresses
			WHERE shipid > ".(int)$_SESSION['shipStart']."
			ORDER BY shipid ASC
		";
		$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, 100);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($address = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$done = true;
			$name = trim($address['shipfullname']);
			$name = explode(' ', $name);
			$lastName = array_pop($name);
			$firstName = implode(' ', $name);
			$updatedAddress = array(
				'shipfirstname' => $firstName,
				'shiplastname' => $lastName
			);
			if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('shipping_addresses', $updatedAddress, "shipid='".(int)$address['shipid']."'")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
			$_SESSION['shipStart'] = $address['shipid'];
		}

		// If we didn't do any iterations of the above, we're done so move on to the next step
		if(!isset($done)) {
			return true;
		}
		// Otherwise, still some left to go!
		else {
			return false;
		}
	}

	public function drop_shipfullname()
	{
		if(!$this->ColumnExists('[|PREFIX|]shipping_addresses', 'shipfullname')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]shipping_addresses DROP shipfullname";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}
		return true;
	}

	public function convert_order_names()
	{
		if(!$this->ColumnExists('[|PREFIX|]orders', 'ordshipfullname')) {
			return true;
		}

		if(!isset($_SESSION['orderStart'])) {
			$_SESSION['orderStart'] = 0;
		}

		$query = "
			SELECT orderid, ordbillfullname, ordshipfullname
			FROM [|PREFIX|]orders
			WHERE orderid > ".(int)$_SESSION['orderStart']."
			ORDER BY orderid ASC
		";
		$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, 100);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($order = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$done = true;
			$name = trim($order['ordbillfullname']);
			$name = explode(' ', $name);
			$billLast = array_pop($name);
			$billFirst = implode(' ', $name);

			$name = trim($order['ordshipfullname']);
			$name = explode(' ', $name);
			$shipLast = array_pop($name);
			$shipFirst = implode(' ', $name);

			$updatedOrder = array(
				'ordbillfirstname' => $billFirst,
				'ordbilllastname' => $billLast,
				'ordshipfirstname' => $shipFirst,
				'ordshiplastname' => $shipLast
			);
			if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$order['orderid']."'")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
			$_SESSION['orderStart'] = $order['orderid'];
		}

		// If we didn't do any iterations of the above, we're done so move on to the next step
		if(!isset($done)) {
			return true;
		}
		// Otherwise, still some left to go!
		else {
			return false;
		}
	}

	public function drop_ordernames()
	{
		if(!$this->ColumnExists('[|PREFIX|]orders', 'ordbillfullname')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]orders DROP ordbillfullname";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}
		$query = "ALTER TABLE [|PREFIX|]orders DROP ordshipfullname";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}
		return true;
	}

	public function add_product_tag_tables()
	{
		if($this->TableExists('product_tags')) {
			return true;
		}

		$queries = array(
			"CREATE TABLE [|PREFIX|]product_tags (
			 tagid INT UNSIGNED NOT NULL AUTO_INCREMENT,
			 tagname VARCHAR( 100 ) NOT NULL DEFAULT '',
			 tagfriendlyname VARCHAR( 100 ) NOT NULL DEFAULT '',
			 tagcount INT UNSIGNED NOT NULL DEFAULT '0',
			 PRIMARY KEY (tagid)
			) TYPE=MyISAM;",

			"CREATE TABLE [|PREFIX|]product_tagassociations (
			 tagassocid INT UNSIGNED NOT NULL auto_increment,
			 tagid INT UNSIGNED NOT NULL default '0',
			 productid INT UNSIGNED NOT NULL default '0',
			 PRIMARY KEY (tagassocid)
			) TYPE=MyISAM;",

			"ALTER TABLE [|PREFIX|]products ADD prodhastags INT( 1 ) NOT NULL default '0';"
		);
		foreach($queries as $query) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_vendornumsold_vendorfriendlyname()
	{
		if($this->ColumnExists('[|PREFIX|]vendors', 'vendornumsales')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]vendors ADD vendornumsales int unsigned NOT NULL default '0'";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}
		$query = "ALTER TABLE [|PREFIX|]vendors ADD vendorfriendlyname varchar(100) NOT NULL default '' AFTER vendorname";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}
		return true;
	}

	public function add_vendororderemail()
	{
		if($this->ColumnExists('[|PREFIX|]vendors', 'vendororderemail')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]vendors ADD `vendororderemail` varchar(200) NOT NULL default ''";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}
		return true;
	}

	public function add_giftwrapping_table()
	{
		if($this->TableExists('gift_wrapping')) {
			return true;
		}

		$query = "
		CREATE TABLE [|PREFIX|]gift_wrapping (
			wrapid int unsigned NOT NULL auto_increment,
			wrapname varchar(100) NOT NULL default '',
			wrapprice decimal(20, 4) NOT NULL default '0.00',
			wrapvisible int(1) NOT NULL default '0',
			wrapallowcomments int(1) NOT NULL default '0',
			wrappreview varchar(100) NOT NULL default '',
			PRIMARY KEY(wrapid)
		) ENGINE=MyISAM;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}
		return true;
	}

	public function add_giftwrapping_products()
	{
		if($this->ColumnExists('[|PREFIX|]products', 'prodwrapoptions')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]products ADD prodwrapoptions text NULL";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}
		return true;
	}

	public function add_giftwrapping_orders()
	{
		if($this->ColumnExists('[|PREFIX|]order_products', 'ordprodwrapid')) {
			return true;
		}

		$queries = array(
			"ALTER TABLE [|PREFIX|]order_products ADD ordprodwrapid int unsigned NOT NULL default '0'",
			"ALTER TABLE [|PREFIX|]order_products ADD ordprodwrapname varchar(100) NOT NULL default ''",
			"ALTER TABLE [|PREFIX|]order_products ADD ordprodwrapcost decimal(20, 4) NOT NULL default '0.00'",
			"ALTER TABLE [|PREFIX|]order_products ADD ordprodwrapmessage text NULL"
		);

		foreach($queries as $query) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_shipping_address_admin_fields()
	{
		if (!$this->IndexExists('[|PREFIX|]shipping_addresses', 'i_shipping_addresses_shipcustomerid')) {
			$query = "ALTER TABLE `[|PREFIX|]shipping_addresses` ADD KEY `i_shipping_addresses_shipcustomerid` (`shipcustomerid`)";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_vendor_shipping_support()
	{
		if(!$this->ColumnExists('[|PREFIX|]shipping_methods', 'methodvendorid')) {
			$query = "ALTER TABLE [|PREFIX|]shipping_methods ADD `methodvendorid` int unsigned NOT NULL default '0';";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if(!$this->ColumnExists('[|PREFIX|]shipping_vars', 'varvendorid')) {
			$query = "ALTER TABLE [|PREFIX|]shipping_vars ADD `varvendorid` int unsigned NOT NULL default '0';";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if(!$this->ColumnExists('[|PREFIX|]shipping_zones', 'zonevendorid')) {
			$query = "ALTER TABLE [|PREFIX|]shipping_zones ADD `zonevendorid` int unsigned NOT NULL default '0';";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if(!$this->ColumnExists('[|PREFIX|]shipping_zone_locations', 'locationvendorid')) {
			$query = "ALTER TABLE [|PREFIX|]shipping_zone_locations ADD `locationvendorid` int unsigned NOT NULL default '0';";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if(!$this->ColumnExists('[|PREFIX|]vendors', 'vendorshipping')) {
			$query = "ALTER TABLE [|PREFIX|]vendors ADD `vendorshipping` int (1) NOT NULL default '0';";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if(!$this->ColumnExists('[|PREFIX|]shipping_zones', 'zonedefault')) {
			$query = "ALTER TABLE [|PREFIX|]shipping_zones ADD `zonedefault` int (1) NOT NULL default '0';";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
			$updatedZone = array(
				'zonedefault' => 1
			);
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('shipping_zones', $updatedZone, "zoneid='1'");
		}

		return true;
	}

	public function add_shipment_tables()
	{
		if(!$this->TableExists('shipments')) {
			$query = "
				CREATE TABLE [|PREFIX|]shipments (
					shipmentid int unsigned NOT NULL auto_increment,
					shipcustid int unsigned NOT NULL default '0',
					shipvendorid int unsigned NOT NULL default '0',
					shipdate int(11) NOT NULL default '0',
					shiptrackno varchar(50) NOT NULL default '',
					shipmethod varchar(100) NOT NULL default '',
					shiporderid int unsigned NOT NULL default '0',
					shiporderdate int(11) NOT NULL default '0',
					shipcomments TEXT NULL,
					shipbillfirstname varchar(255) NOT NULL default '',
					shipbilllastname varchar(255) NOT NULL default '',
					shipbillcompany varchar(100) NOT NULL default '',
					shipbillstreet1 varchar(255) NOT NULL default '',
					shipbillstreet2 varchar(255) NOT NULL default '',
					shipbillsuburb varchar(100) NOT NULL default '',
					shipbillstate varchar(50) NOT NULL default '',
					shipbillzip varchar(20) NOT NULL default '',
					shipbillcountry varchar(50) NOT NULL default '',
					shipbillcountrycode varchar(2) NOT NULL default '',
					shipbillcountryid int(11) NOT NULL default '0',
					shipbillstateid int(11) NOT NULL default '0',
					shipbillphone varchar(50) NOT NULL default '',
					shipbillemail varchar(250) NOT NULL default '',
					shipshipfirstname varchar(100) NOT NULL default '',
					shipshiplastname varchar(100) NOT NULL default '',
					shipshipcompany varchar(100) NOT NULL default '',
					shipshipstreet1 varchar(255) NOT NULL default '',
					shipshipstreet2 varchar(255) NOT NULL default '',
					shipshipsuburb varchar(100) NOT NULL default '',
					shipshipstate varchar(50) NOT NULL default '',
					shipshipzip varchar(20) NOT NULL default '',
					shipshipcountry varchar(50) NOT NULL default '',
					shipshipcountrycode varchar(2) NOT NULL default '',
					shipshipcountryid int(11) NOT NULL default '0',
					shipshipstateid int(11) NOT NULL default '0',
					shipshipphone varchar(50) NOT NULL default '',
					shipshipemail varchar(250) NOT NULL default '',
					PRIMARY KEY(shipmentid)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
			";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if(!$this->TableExists('shipment_items')) {
			$query = "
				CREATE TABLE [|PREFIX|]shipment_items (
					itemid int unsigned NOT NULL auto_increment,
					shipid int unsigned NOT NULL default '0',
					itemprodid int unsigned NOT NULL default '0',
					itemordprodid int unsigned NOT NULL default '0',
					itemprodsku varchar(250) NOT NULL default '',
					itemprodname varchar(250) NOT NULL default '',
					itemqty int unsigned NOT NULL default '0',
					itemprodoptions text NULL,
					itemprodvariationid int unsigned NOT NULL default '0',
					PRIMARY KEY(itemid)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
			";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_shipment_columns()
	{
		$queries = array();

		if($this->ColumnExists('[|PREFIX|]order_products', 'ordprodshipped')) {
			$queries[] = "ALTER TABLE [|PREFIX|]order_products DROP ordprodshipped;";
		}

		if($this->ColumnExists('[|PREFIX|]order_products', 'ordprodshipdate')) {
			$queries[] = "ALTER TABLE [|PREFIX|]order_products DROP ordprodshipdate;";
		}

		if($this->ColumnExists('[|PREFIX|]order_products', 'ordprodshiptrackno')) {
			$queries[] = "ALTER TABLE [|PREFIX|]order_products DROP ordprodshiptrackno;";
		}

		if(!$this->ColumnExists('[|PREFIX|]order_products', 'ordprodqtyshipped')) {
			$queries[] = "ALTER TABLE [|PREFIX|]order_products ADD ordprodqtyshipped int unsigned NOT NULL default '0';";
		}

		if(!$this->ColumnExists('[|PREFIX|]orders', 'ordtotalqty')) {
			$queries[] = "ALTER TABLE [|PREFIX|]orders ADD ordtotalqty int unsigned NOT NULL default '0' AFTER ordstatus;";
		}

		if(!$this->ColumnExists('[|PREFIX|]orders', 'ordtotalshipped')) {
			$queries[] = "ALTER TABLE [|PREFIX|]orders ADD ordtotalshipped int unsigned NOT NULL default '0' AFTER ordtotalqty;";
		}

		foreach($queries as $query) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function update_order_totals()
	{
		if(!isset($_SESSION['totalStart'])) {
			$_SESSION['totalStart'] = 0;
		}

		$query = "
			SELECT orderid,
				(SELECT COUNT(*) FROM [|PREFIX|]order_products op WHERE op.orderorderid=o.orderid) AS numproducts
			FROM [|PREFIX|]orders o
			WHERE orderid > ".(int)$_SESSION['totalStart']."
			ORDER BY orderid ASC
		";
		$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, 100);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($order = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$updatedOrder = array(
				'ordtotalqty' => $order['numproducts']
			);
			if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $updatedOrder, "orderid='".(int)$order['orderid']."'")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
			$done = 1;
			$_SESSION['totalStart'] = $order['orderid'];
		}

		// If we didn't do any iterations of the above, we're done so move on to the next step
		if(!isset($done)) {
			return true;
		}
		// Otherwise, still some left to go!
		else {
			return false;
		}
	}

	public function add_shipments_custom_type()
	{
		$query = "
			ALTER TABLE [|PREFIX|]custom_searches
			CHANGE searchtype searchtype ENUM('orders','products','customers', 'returns', 'giftcertificates', 'shipments') NOT NULL default 'orders'
	 	";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_product_discount_table()
	{
		if (!$this->TableExists('product_discounts')) {
			$query = "CREATE TABLE `[|PREFIX|]product_discounts` (
					 `discountid` INT NOT NULL auto_increment,
					 `discountprodid` INT NOT NULL default '0',
					 `discountquantitymin` INT NOT NULL default '0',
					 `discountquantitymax` INT NOT NULL default '0',
					 `discounttype` ENUM('price', 'percent', 'fixed') NOT NULL default 'price',
					 `discountamount` DECIMAL(20,4) NOT NULL default '0',
					 PRIMARY KEY (`discountid`),
					 INDEX `i_product_discounts_discountprodid` (`discountprodid`)
				)ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;";

			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_vendor_payments_table()
	{
		if($this->TableExists('vendor_payments')) {
			return true;
		}

		$query = "
			CREATE TABLE [|PREFIX|]vendor_payments (
				paymentid int unsigned NOT NULL auto_increment,
				paymentfrom int(11) NOT NULL default '0',
				paymentto int(11) NOT NULL default '0',
				paymentvendorid int unsigned NOT NULL default '0',
				paymentamount decimal(20, 4) NOT NULL default '0.0000',
				paymentforwardbalance decimal(20, 4) NOT NULL default '0.0000',
				paymentdate int(11) NOT NULL default '0',
				paymentdeducted int(1) NOT NULL default '0',
				paymentmethod varchar(100) NOT NULL default '',
				paymentcomments text NULL,
				PRIMARY KEY(paymentid)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_vendor_email()
	{
		if($this->ColumnExists('[|PREFIX|]vendors', 'vendoremail')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]vendors ADD vendoremail varchar(200) NOT NULL default '';";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_customer_password_token()
	{
		if(!$this->ColumnExists('[|PREFIX|]customers', 'customerpasswordresettoken')) {
			$query = "ALTER TABLE [|PREFIX|]customers ADD customerpasswordresettoken varchar(32) NOT NULL default '' AFTER customertoken;";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if(!$this->ColumnExists('[|PREFIX|]customers', 'customerpasswordresetemail')) {
			$query = "ALTER TABLE [|PREFIX|]customers ADD customerpasswordresetemail varchar(255) NOT NULL default '' AFTER customerpasswordresettoken;";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if($this->ColumnExists('[|PREFIX|]customers', 'custnewpassword')) {
			$query = "ALTER TABLE [|PREFIX|]customers DROP COLUMN custnewpassword;";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_configurable_fields_table()
	{
		$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]product_configurable_fields` (
					  `productfieldid` int(11) NOT NULL auto_increment,
					  `fieldprodid` int(11) NOT NULL default '0',
					  `fieldname` varchar(255) NOT NULL default '',
					  `fieldtype` varchar(255) NOT NULL default '',
					  `fieldfiletype` varchar(255) NOT NULL default '',
					  `fieldfilesize` int(11) NOT NULL default '0',
					  `fieldrequired` tinyint(4) NOT NULL default '0',
					  `fieldsortorder` int(11) NOT NULL default '1',
					  PRIMARY KEY  (`productfieldid`)
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
				";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]order_configurable_fields` (
					  `orderfieldid` int(11) NOT NULL auto_increment,
					  `fieldid` int(11) NOT NULL default '0',
					  `orderid` int(11) NOT NULL default '0',
					  `ordprodid` int(11) NOT NULL default '0',
					  `textcontents` text NULL,
					  `filename` varchar(255) NOT NULL default '',
					  `filetype` varchar(255) NOT NULL default '',
					  `originalfilename` varchar(255) NOT NULL default '',
					  `fieldname` varchar(255) NOT NULL default '',
					  `fieldtype` varchar(255) NOT NULL default '',
					  PRIMARY KEY  (`orderfieldid`)
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
				";

		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_vendor_cat_restrictions()
	{
		if(!$this->ColumnExists('[|PREFIX|]vendors', 'vendoraccesscats')) {
			$query = "ALTER TABLE [|PREFIX|]vendors ADD vendoraccesscats text NULL";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_vendor_image_columns()
	{
		if(!$this->ColumnExists('[|PREFIX|]vendors', 'vendorlogo')) {
			$query = "ALTER TABLE [|PREFIX|]vendors ADD vendorlogo varchar(200) NOT NULL default ''";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if(!$this->ColumnExists('[|PREFIX|]vendors', 'vendorphoto')) {
			$query = "ALTER TABLE [|PREFIX|]vendors ADD vendorphoto varchar(200) NOT NULL default ''";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_product_vendor_featured()
	{
		if(!$this->ColumnExists('[|PREFIX|]products', 'prodvendorfeatured')) {
			$query = "ALTER TABLE [|PREFIX|]products ADD prodvendorfeatured tinyint(1) NOT NULL default '0' AFTER prodfeatured";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function delayed_capture_payment()
	{
		if(!$this->ColumnExists('[|PREFIX|]orders', 'ordpaymentstatus')) {
			$query = "ALTER TABLE `[|PREFIX|]orders` ADD `ordpaymentstatus` VARCHAR( 100 ) NOT NULL DEFAULT '' AFTER `ordpayproviderid` ;";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if(!$this->ColumnExists('[|PREFIX|]orders', 'ordrefundedamount')) {
			$query = "ALTER TABLE `[|PREFIX|]orders` ADD `ordrefundedamount` decimal( 20, 4 ) NOT NULL DEFAULT 0 AFTER `ordpaymentstatus` ;";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_vendor_profitmargin()
	{
		if(!$this->ColumnExists('[|PREFIX|]vendors', 'vendorprofitmargin')) {
			$query = "ALTER TABLE `[|PREFIX|]vendors` ADD `vendorprofitmargin` decimal(20,4) NOT NULL default '0.00'";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_prodconfigfields()
	{

		if(!$this->ColumnExists('[|PREFIX|]products', 'prodconfigfields')) {
			$query = "ALTER TABLE `[|PREFIX|]products` ADD `prodconfigfields` VARCHAR(255) NOT NULL default ''";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_product_variation_indexes()
	{
		if (!$this->IndexExists('[|PREFIX|]product_variation_combinations', 'i_product_variation_combinations_vcvariationid')) {
			$query = "ALTER TABLE `[|PREFIX|]product_variation_combinations` ADD INDEX `i_product_variation_combinations_vcvariationid` (`vcvariationid`)";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->IndexExists('[|PREFIX|]product_variation_combinations', 'i_product_variation_combinations_vcproductid')) {
			$query = "ALTER TABLE `[|PREFIX|]product_variation_combinations` ADD INDEX `i_product_variation_combinations_vcproductid` (`vcproductid`)";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->IndexExists('[|PREFIX|]product_variation_options', 'i_product_variation_options_vovariationid')) {
			$query = "ALTER TABLE `[|PREFIX|]product_variation_options` ADD INDEX `i_product_variation_options_vovariationid` (`vovariationid`)";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]product_variation_options', 'vooptionsort')) {
			$query = "ALTER TABLE `[|PREFIX|]product_variation_options` ADD `vooptionsort` int(11) NOT NULL default '0'";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]product_variation_options', 'vovaluesort')) {
			$query = "ALTER TABLE `[|PREFIX|]product_variation_options` ADD `vovaluesort` int(11) NOT NULL default '0'";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_product_visible_indexes()
	{
		if (!$this->IndexExists('[|PREFIX|]products', 'i_products_rating_vis')) {
			$query = "ALTER TABLE `[|PREFIX|]products` ADD INDEX `i_products_rating_vis` (`prodvisible`, `prodratingtotal`)";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->IndexExists('[|PREFIX|]products', 'i_products_added_vis')) {
			$query = "ALTER TABLE `[|PREFIX|]products` ADD INDEX `i_products_added_vis` (`prodvisible`, `proddateadded`)";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->IndexExists('[|PREFIX|]products', 'i_products_hideprice_vis')) {
			$query = "ALTER TABLE `[|PREFIX|]products` ADD INDEX `i_products_hideprice_vis` (`prodhideprice`, `prodvisible`)";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->IndexExists('[|PREFIX|]products', 'i_products_sortorder_vis')) {
			$query = "ALTER TABLE `[|PREFIX|]products` ADD INDEX `i_products_sortorder_vis` (`prodvisible`, `prodsortorder`, `prodname`)";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_category_association_indexes()
	{
		if (!$this->IndexExists('[|PREFIX|]categoryassociations', 'i_categoryassociations_prodcat')) {
			$query = "ALTER TABLE `[|PREFIX|]categoryassociations` ADD INDEX `i_categoryassociations_prodcat` (`productid`, `categoryid`)";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->IndexExists('[|PREFIX|]categoryassociations', 'i_categoryassociations_catprod')) {
			$query = "ALTER TABLE `[|PREFIX|]categoryassociations` ADD INDEX `i_categoryassociations_catprod` (`categoryid`, `productid`)";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function alter_product_image_index()
	{
		if ($this->IndexExists('[|PREFIX|]product_images', 'i_product_images_imageprodid')) {
			$query = "ALTER TABLE `[|PREFIX|]product_images` DROP INDEX `i_product_images_imageprodid`";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		$query = "ALTER TABLE `[|PREFIX|]product_images` ADD INDEX `i_product_images_imageprodid` (`imageprodid`, `imageisthumb`)";
		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function convert_accounting_spool()
	{
		$query = "ALTER TABLE [|PREFIX|]accountingref MODIFY `accountingreftype` enum('customer','customergroup','product','order','salestaxcode','account','inventorylevel','orderlineitem') NOT NULL";
		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		if (!$this->TableExists('accountingspool')) {
			$query = "
				CREATE TABLE `[|PREFIX|]accountingspool` (
				  `accountingspoolid` int(10) unsigned NOT NULL auto_increment,
				  `accountingspoolparentid` int(10) unsigned NOT NULL default '0',
				  `accountingspoolmoduleid` varchar(100) NOT NULL default '',
				  `accountingspoolnodeid` int(10) unsigned NOT NULL default '0',
				  `accountingspoolserial` text,
				  `accountingspooltype` enum('customer','customergroup','product','order','salestaxcode','account','inventorylevel') NOT NULL,
				  `accountingspoolservice` enum('add','edit','query') NOT NULL,
				  `accountingspoollock` char(36) NOT NULL default '',
				  `accountingspoolstatus` tinyint(1) default '0',
				  `accountingspooldisabled` tinyint(1) NOT NULL default '0',
				  `accountingspoolerrmsg` tinytext,
				  `accountingspoolerrno` int(10) unsigned NOT NULL default '0',
				  `accountingspoolreturn` text,
				  PRIMARY KEY  (`accountingspoolid`),
				  KEY `i_accountingspool_accountingspoolparentid` (`accountingspoolparentid`),
				  KEY `i_accountingspool_accountingspoolmoduleid` (`accountingspoolmoduleid`),
				  KEY `i_accountingspool_accountingspoolnodeid` (`accountingspoolnodeid`),
				  KEY `i_accountingspool_accountingspooltype` (`accountingspooltype`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;";

			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

		/**
		 * If this table already exists and it has records in it then DO NOT import the spool files as order will double up and could potentially duplciate
		 * products and customers
		 */
		} else {
			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]accountingspool");
			if ($result && $GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
				return true;
			}
		}

		/**
		 * Now convert the existsing spool files into database accountingspool records. Force out the mandatory account spools just in case
		 */
		$accounting = GetClass('ISC_ACCOUNTING');
		$initdata = array(
						array(
								'type'		=> 'account',
								'service'	=> 'add',
								'data'		=> array(
												'Name' => GetLang('QuickBooksIncomeAccountName'),
												'AccountType' => 'Income'
												)
							),
						array(
								'type'		=> 'account',
								'service'	=> 'add',
								'data'		=> array(
												'Name' => GetLang('QuickBooksCOGSAccountName'),
												'AccountType' => 'CostOfGoodsSold'
												)
							),
						array(
								'type'		=> 'account',
								'service'	=> 'add',
								'data'		=> array(
												'Name' => GetLang('QuickBooksAssetAccountName'),
												'AccountType' => 'FixedAsset'
												)
							),
				);

		foreach ($initdata as $data) {
			$accounting->createServiceRequest($data['type'], $data['service'], $data['data']);
		}

		/**
		 * Now for the rest. These will be in the spool cache file so you'll need to read the files from there
		 */
		$files = scandir(ISC_BASE_PATH.'/cache/spool');
		foreach ($files as $file) {

			$realfile = ISC_BASE_PATH.'/cache/spool/' . $file;
			if (!is_file($realfile) || !is_readable($realfile) || substr($file, 0, 6) !== 'spool.') {
				continue;
			}

			$spooldata = null;

			@include_once($realfile);

			if (!is_array($spooldata)) {
				continue;
			}

			/**
			 * Find out if this entity exists. If not then do not import it
			 */
			if (isId($spooldata['nodeid'])) {
				$className = "ISC_ENTITY_" . isc_strtoupper($spooldata['type']);
				$entity = new $className();

				/**
				 * Save it using the data array instead of the nodeid as they might delete that entity before they import
				 */
				$savedata = $entity->get($spooldata['nodeid']);
				if (!$savedata) {
					continue;
				}

			} else {
				continue;
			}

			switch (isc_strtolower($spooldata['type'])) {
				case 'order':

					/**
					 * We need to check if the customer and all of the products for this order still exist
					 */
					$query = "SELECT IF(EXISTS(SELECT * FROM [|PREFIX|]customers c WHERE o.ordcustid=c.customerid), 1, 0) AS CustomerExists,
									(SELECT COUNT(*) FROM [|PREFIX|]order_products op1 WHERE op1.orderorderid=o.orderid) AS TotalProducts,
									(SELECT COUNT(*) FROM [|PREFIX|]order_products op2 JOIN [|PREFIX|]products p ON op2.ordprodid=p.productid WHERE op2.orderorderid=o.orderid) AS ValidProducts
								FROM [|PREFIX|]orders o
								WHERE o.orderid=" . (int)$spooldata['nodeid'];

					$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
					if (!$result) {
						break;
					}

					$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
					if (!$row) {
						break;
					}

					if (!$row['CustomerExists'] || $row['TotalProducts'] !== $row['ValidProducts']) {
						break;
					}

					$accounting->createServiceRequest('order', 'add', $savedata, 'order_create');
					break;

				case 'product':
				case 'customer':
				case 'customergroup':

					/**
					 * Find out if this is an add or mod. If query then skip
					 */
					if (substr(isc_strtolower($spooldata['service']), -3) == 'add') {
						$permission = 'create';
						$service = 'add';
					} else if (substr(isc_strtolower($spooldata['service']), -3) == 'mod') {
						$permission = 'edit';
						$service = 'edit';
					} else {
						break;
					}

					if (isc_strtolower($spooldata['type']) == 'product') {
						$permission = 'product_' . $permission;
					} else {
						$permission = 'customer_' . $permission;
					}

					$accounting->createServiceRequest(isc_strtolower($spooldata['type']), $service, $savedata, $permission);
					break;

				default:
					break;

			}
		}

		return true;
	}
}
