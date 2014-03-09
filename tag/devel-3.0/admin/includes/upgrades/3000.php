<?php

class ISC_ADMIN_UPGRADE_3000 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		"update_trackingno_default",
		"add_ip_fields",
		"add_country_code_columns",
		"populate_order_country_codes",
		"html_decode_fields",
		"add_tax_columns",
		"remove_pending_orders",
		"add_new_pending_order_info",
		"page_title_fields",
	);

	public function update_trackingno_default()
	{
		$query = "ALTER TABLE [|PREFIX|]orders CHANGE ordtrackingno ordtrackingno varchar(100) NOT NULL default ''";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "UPDATE [|PREFIX|]orders SET ordtrackingno='' WHERE ordtrackingno='0'";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_ip_fields()
	{
		$query = "ALTER TABLE [|PREFIX|]orders ADD `ordipaddress` varchar(30) NOT NULL default '' AFTER extrainfo";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE [|PREFIX|]orders ADD `ordgeoipcountry` varchar(100) NOT NULL default ''";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE [|PREFIX|]orders ADD `ordgeoipcountrycode` varchar(2) NOT NULL default ''";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE [|PREFIX|]customers ADD `custregipaddress` varchar(30) NOT NULL default ''";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_country_code_columns()
	{
		$query = "ALTER TABLE [|PREFIX|]orders ADD `ordbillcountrycode` varchar(2) NOT NULL default '' AFTER ordbillcountry";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE [|PREFIX|]orders ADD `ordshipcountrycode` varchar(2) NOT NULL default '' AFTER ordshipcountry";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function populate_order_country_codes()
	{
		$query = "SELECT * FROM [|PREFIX|]orders";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($order = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$billId = GetCountryISO2ById($order['ordbillcountryid']);
			$shipId = GetCountryISO2ById($order['ordshipcountryid']);
			$updatedOrder = array(
				"ordbillcountrycode" => $billId,
				"ordshipcountrycode" => $shipId
			);
			if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery("orders", $updatedOrder, "orderid='".$order['orderid']."'")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function html_decode_fields()
	{
		$tables = array (
			'banners'			=> array ('name'),
			'brands'			=> array ('brandname', 'brandmetakeywords', 'brandmetadesc'),
			'categories'		=> array ('catname', 'catdesc', 'catmetakeywords', 'catmetadesc'),
			'coupons'			=> array ('couponname', 'couponcode', 'couponcode'),
			'custom_searches'	=> array ('searchname', 'searchvars'),
			'customer_credits'	=> array ('creditreason'),
			'customers'			=> array ('custconcompany', 'custconfirstname', 'custconlastname', 'custconemail', 'custconphone', 'customertoken', 'customertoken'),
			'gift_certificates'	=> array ('giftcertcode', 'giftcertto', 'giftcerttoemail', 'giftcertfrom', 'giftcertfromemail', 'giftcertfromemail', 'giftcertfromemail'),
			'module_vars'		=> array ('variableval'),
			'news'				=> array ('newstitle'),
			'order_messages'	=> array ('subject', 'message'),
			'order_products'	=> array ('ordprodsku', 'ordprodname', 'ordprodshiptrackno', 'ordprodoptions'),
			'orders'			=> array ('ordshipmethod', 'ordershipmodule', 'orderpaymentmethod', 'orderpaymentmodule', 'ordbillfullname', 'ordbillstreet1', 'ordbillstreet2', 'ordbillsuburb', 'ordbillstate', 'ordbillzip', 'ordbillcountry', 'ordbillphone', 'ordbillemail', 'ordshipfullname', 'ordshipstreet1', 'ordshipstreet2', 'ordshipsuburb', 'ordshipstate', 'ordshipzip', 'ordshipcountry', 'ordtrackingno', 'extrainfo'),
			'pages'				=> array ('pagetitle', 'pagelink', 'pagefeed', 'pageemail', 'pagekeywords', 'pagedesc', 'pagecontactfields', 'pagemetakeywords', 'pagemetadesc', 'pagelayoutfile'),
			'product_customfields'	=> array ('fieldname'),
			'product_downloads'	=> array ('prodhash', 'downfile', 'downname', 'downdescription'),
			'product_search'	=> array ('prodname', 'prodcode', 'proddesc', 'prodsearchkeywords'),
			'product_variation_combinations' => array ('vcsku'),
			'product_variation_options' => array ('voname', 'vovalue'),
			'product_variations' => array ('vname'),
			'product_words' => array ('word'),
			'products' => array ('prodname', 'prodcode', 'proddesc', 'prodsearchkeywords', 'prodavailability', 'prodwarranty', 'prodmetakeywords', 'prodmetadesc'),
			'returns' => array ('retprodname', 'retreason', 'retaction', 'retcomment', 'retstaffnotes'),
			'reviews' => array ('revfromname', 'revtext', 'revtitle'),
			'search_corrections' => array ('correction', 'oldsearchtext'),
			'searches' => array ('searchtext'),
			'searches_extended' => array ('searchtext'),
			'shipping_addresses' => array ('shipfullname', 'shipaddress1', 'shipaddress2', 'shipcity', 'shipstate', 'shipzip', 'shipcountry', 'shipphone'),
			'subscribers' => array ('subemail', 'subfirstname'),
			'tax_rates' => array ('taxratename'),
			'users' => array ('username', 'userfirstname', 'userlastname', 'useremail')
		);

		if (!isset($GLOBALS['ISC_CLASS_ADMIN_UPGRADE']->upgradeSession['tables_to_upgrade'])) {
			$GLOBALS['ISC_CLASS_ADMIN_UPGRADE']->upgradeSession['tables_to_upgrade'] = $tables;
		}

		// If we have done all the upgrades then finish the step
		if (empty($GLOBALS['ISC_CLASS_ADMIN_UPGRADE']->upgradeSession['tables_to_upgrade'])) {
			return true;
		}

		foreach ($GLOBALS['ISC_CLASS_ADMIN_UPGRADE']->upgradeSession['tables_to_upgrade'] as $table => $fields) {
			$queries = array();
			foreach ($fields as $field) {
				$queries[] = "UPDATE [|PREFIX|]".$table." SET ".$field." = REPLACE(".$field.", '&quot;', '\"')";
				$queries[] = "UPDATE [|PREFIX|]".$table." SET ".$field." = REPLACE(".$field.", '&#39;', '\'')";
				$queries[] = "UPDATE [|PREFIX|]".$table." SET ".$field." = REPLACE(".$field.", '&gt;', '>')";
				$queries[] = "UPDATE [|PREFIX|]".$table." SET ".$field." = REPLACE(".$field.", '&lt;', '<')";
				$queries[] = "UPDATE [|PREFIX|]".$table." SET ".$field." = REPLACE(".$field.", '&amp;', '&')";
			}

			foreach ($queries as $query) {
				$GLOBALS['ISC_CLASS_DB']->Query($query);
			}

			// Mark this step as done
			unset($GLOBALS['ISC_CLASS_ADMIN_UPGRADE']->upgradeSession['tables_to_upgrade'][$table]);
			break;
		}
		return false;
	}

	public function add_tax_columns()
	{
		$queries = array(
			"ALTER TABLE [|PREFIX|]orders ADD ordtaxrate decimal(10,4) NOT NULL default '0' AFTER ordtaxtotal;",
			"ALTER TABLE [|PREFIX|]orders ADD ordtaxname varchar(100) NOT NULL default '' AFTER ordtaxrate;",
			"ALTER TABLE [|PREFIX|]orders ADD ordtotalincludestax int(1) NOT NULL default '0' AFTER ordtaxname;"
		);
		foreach($queries as $query) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function remove_pending_orders()
	{
		$query = "DROP TABLE [|PREFIX|]pending_orders";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_new_pending_order_info()
	{
		$query = "ALTER TABLE [|PREFIX|]orders ADD ordtoken varchar(32) NOT NULL default ''";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "INSERT INTO `[|PREFIX|]custom_searches` (`searchtype`, `searchname`, `searchvars`) VALUES ('orders', 'Incomplete Orders', 'viewName=Incomplete+Orders&orderStatus=0');";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function page_title_fields()
	{
		$query = "ALTER TABLE [|PREFIX|]products ADD `prodpagetitle` VARCHAR( 250 ) DEFAULT '' NOT NULL AFTER `prodnumviews`";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE [|PREFIX|]brands ADD `brandpagetitle` VARCHAR( 250 ) DEFAULT '' NOT NULL AFTER `brandname`";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$query = "ALTER TABLE [|PREFIX|]categories ADD `catpagetitle` VARCHAR( 250 ) DEFAULT '' NOT NULL AFTER `catsort`";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}
}