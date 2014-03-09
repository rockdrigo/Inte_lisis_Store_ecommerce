<?php

/**
 * Upgrade class for 6.0.0
 * This class runs a series of methods used to upgrade the store to a specific version
 *
 * @package ISC
 * @subpackage ISC_Upgrade
 */
class ISC_ADMIN_UPGRADE_6000 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		'add_preorder_custom_search',
		'add_products_prodpreorder',
		'add_products_prodreleasedate',
		'add_products_prodreleasedateremove',
		'add_products_prodpreordermessage',
		'add_orders_orderid_ordstatus_index',
		'add_picniktokens_table',
		'add_product_views_table',
		'add_product_related_byviews_table',
		'set_producthastags_value',
		'add_new_permissions',
		'add_tasks_table',
		'add_task_status_table',
		'create_keystore_table',
		'add_email_provider_list_fields_table',
		'add_email_provider_lists_table',
		'add_email_rules_table',
		'add_system_log_type_emailintegration',
		'add_system_log_type_ebay',
		'add_system_log_type_shoppingcomparison',
		'add_ebay_categories_table',
		'add_ebay_item_fees_table',
		'add_ebay_items_table',
		'add_ebay_listing_prices_table',
		'add_ebay_listing_template_table',
		'add_ebay_shipping_serv_table',
		'add_ebay_shipping_table',
		'add_products_prodminqty',
		'add_products_prodmaxqty',
		'add_comment_system_defaults',
		'add_product_comparisons_table',
		'add_alter_category_taxonomies_table',
		'add_alternate_categories_table',
		'add_categories_to_alternate_categories_table',
		'add_cataltcategoriescache_to_categories',
		'cleanup_product_tagassociations_table',
		'add_selectbox_configurable_field',
		'createTaxZonesTable',
		'createDefaultTaxZone',
		'createTaxClassesTable',
		'createDefaultTaxClasses',
		'createTaxZoneLocationsTable',
		'createTableTaxZoneCustomerGroups',
		'createTaxRatesTable',
		'createTaxRateClassRatesTable',
		'createOrderTaxesTable',
		'createProductTaxPricingTable',
		'createOrderAddressesTable',
		'createOrderShippingTable',
		'addProductTaxClassIdColumn',
		'updateProdIsTaxableProducts',
		'addOrderCouponAmount',
		'addOrderShippingAddressCountColumn',
		'createNewOrderTaxColumns',
		'addNewebayOrderIdColumn',
		'createNewOrderProductTaxColumns',
		'addOrderProductOrderAddressIdColumn',
		'addOrderProductEbayItemIdColumn',
		'updateOrderValuesWithTaxInfo',
		'convertOldTaxRatesToNewTaxRates',
		'removeOldTaxRatesTable',
		'convertOrderShippingAddresses',
		'convertOrderTaxesInToRows',
		'updateOrderProductValuesWithTaxInfo',
		'dropOldOrderColumns',
		'dropOldOrderProductColumns',
		'truncateSessionsTable',
		'convertGlobalTaxSettings',
		'convertMiscTaxSettings',
		'buildProductTaxPricing',
		'removeUnusedFormFields',
		'enableNewLogTypes',
		'addShipmentsShippingModule',
		'updateShipmentShippingModule',
		'updateExportTemplates',
		'fixDefaultTemplateCustomerFields',
		'addEbayCustomSearch',
		'add_user_password_histories_table',
		'add_user_password_reset_tokens_table',
		'add_attempt_counter_to_users_table',
		'add_attempt_lockout_to_users_table',
		'add_last_login_to_users_table',
		'add_product_opengraph_type',
		'add_product_opengraph_title',
		'add_product_opengraph_use_product_name',
		'add_product_opengraph_description',
		'add_product_opengraph_use_meta_description',
		'add_product_opengraph_use_image',
		'addOrderProductAppliedDiscountsColumn',
		'recalculateProdCalculatedPrice',
		'convertEmailMarketerConfigToModuleVars',
	);

	protected $taxClasses = array(
		1 => 'Non-Taxable Products',
		2 => 'Shipping',
		3 => 'Gift Wrapping'
	);

	public function add_preorder_custom_search()
	{
		/** @var mysqldb */
		$db = $GLOBALS['ISC_CLASS_DB'];

		$query = "SELECT COUNT(*) AS `count` FROM `[|PREFIX|]custom_searches` WHERE `searchtype` = 'orders' AND `searchname` = 'Pre-Orders'";
		$result = $db->FetchRow($query);
		if (!$result) {
			$this->SetError($db->GetErrorMsg());
			return false;
		}

		if ((int)$result['count'] > 0) {
			return true;
		}

		$query = "INSERT INTO `[|PREFIX|]custom_searches` (`searchtype`, `searchname`, `searchvars`) VALUES ('orders', 'Pre-Orders', 'viewName=Pre-Orders&preorders[]=1')";
		if (!$db->Query($query)) {
			$this->SetError($db->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_products_prodpreorder()
	{
		if ($this->ColumnExists('[|PREFIX|]products', 'prodpreorder')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]products ADD COLUMN prodpreorder TINYINT UNSIGNED NOT NULL DEFAULT '0'";

		if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_products_prodreleasedate()
	{
		if ($this->ColumnExists('[|PREFIX|]products', 'prodreleasedate')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]products ADD COLUMN prodreleasedate INT(11) UNSIGNED NOT NULL DEFAULT '0'";

		if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_products_prodreleasedateremove()
	{
		if ($this->ColumnExists('[|PREFIX|]products', 'prodreleasedateremove')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]products ADD COLUMN prodreleasedateremove TINYINT UNSIGNED NOT NULL DEFAULT '0'";

		if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_products_prodpreordermessage()
	{
		if ($this->ColumnExists('[|PREFIX|]products', 'prodpreordermessage')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]products ADD COLUMN prodpreordermessage VARCHAR(250) NOT NULL DEFAULT ''";

		if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_orders_orderid_ordstatus_index()
	{
		if ($this->IndexExists('[|PREFIX|]orders', 'i_orders_orderid_ordstatus')) {
			return true;
		}

		$query = "ALTER TABLE `[|PREFIX|]orders` ADD INDEX `i_orders_orderid_ordstatus` (`orderid`,`ordstatus`)";
		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_picniktokens_table()
	{
		if ($this->TableExists('picniktokens')) {
			return true;
		}

		$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]picniktokens` (
			  `picniktokenid` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `hash` char(32) NOT NULL,
			  `imagetype` tinyint(3) unsigned NOT NULL,
			  `imageid` varchar(255) NOT NULL,
			  `created` int(10) unsigned NOT NULL,
			  `sessionid` char(26) NOT NULL,
			  PRIMARY KEY (`picniktokenid`),
			  KEY `i_sessionid_imagetype_imageid` (`sessionid`,`imagetype`,`imageid`),
			  KEY `i_picniktokenid_hash` (`picniktokenid`,`hash`),
			  KEY `i_created` (`created`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_product_views_table()
	{
		if ($this->TableExists('product_views')) {
			return true;
		}

		$query = "CREATE TABLE `[|PREFIX|]product_views` (
			  `session` char(32) NOT NULL,
			  `product` int(10) unsigned NOT NULL,
			  `lastview` int(10) unsigned NOT NULL,
			  PRIMARY KEY (`session`,`product`),
			  KEY `i_session_lastview` (`session`,`lastview`),
			  KEY `i_product` (`product`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_product_related_byviews_table()
	{
		if ($this->TableExists('product_related_byviews')) {
			return true;
		}

		$query = "CREATE TABLE `[|PREFIX|]product_related_byviews` (
			  `prodida` int(10) unsigned NOT NULL,
			  `prodidb` int(10) unsigned NOT NULL,
			  `relevance` int(10) unsigned NOT NULL,
			  `lastview` int(10) unsigned NOT NULL,
			  PRIMARY KEY (`prodida`,`prodidb`),
			  KEY `i_prodida_relevance` (`prodida`,`relevance`),
			  KEY `i_prodidb` (`prodidb`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function set_producthastags_value()
	{
		// Set flag to 1 for all products with at least a tag, chunck of 100.
		if(!isset($_SESSION['pidStart'])) {
			$_SESSION['pidStart'] = 0;
		}

		$query = "
			SELECT DISTINCT productid
			FROM [|PREFIX|]product_tagassociations
			WHERE productid > ".(int)$_SESSION['pidStart']."
			ORDER BY productid ASC
		";
		$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, 100);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($product = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$field = array(
				"prodhastags" => 1,
			);
			if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery("products", $field, "productid='".$GLOBALS['ISC_CLASS_DB']->Quote($product['productid'])."'")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			};

			$done = 1;
			$_SESSION['pidStart'] = $product['productid'];
		}

		if(!isset($done)) {
			return true;
		}
		else {
			return false;
		}
	}

	public function add_new_permissions()
	{
		$newPermissions = array(
			AUTH_Manage_RobotsTxt,
			AUTH_Ebay_Selling,
			AUTH_Manage_EmailMarketing,
		);

		foreach ($newPermissions as $permission) {
			$query = "
				INSERT INTO [|PREFIX|]permissions (permuserid, permpermissionid)
					SELECT
						pk_userid,
						" . $permission . "
					FROM
						[|PREFIX|]users u
					WHERE
						userrole = 'admin' AND
						pk_userid NOT IN (SELECT permuserid FROM [|PREFIX|]permissions WHERE pk_userid = u.pk_userid AND permpermissionid = " . $permission . ")
			";

			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_tasks_table()
	{
		if ($this->TableExists('tasks')) {
			return true;
		}

		$query = "CREATE TABLE `[|PREFIX|]tasks` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `queue` varchar(128) NOT NULL,
			  `class` varchar(255) NOT NULL default '',
			  `data` text,
			  `time` int(10) unsigned NOT NULL default '0',
			  `reservation` varchar(32) NOT NULL default '',
			  PRIMARY KEY  (`id`),
			  KEY `queue_reservation_time` (`queue`,`reservation`,`time`),
			  KEY `reservation_time` (`reservation`,`time`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_task_status_table()
	{
		if ($this->TableExists('task_status')) {
			return true;
		}

		$query = "CREATE TABLE `[|PREFIX|]task_status` (
			  `id` int(10) unsigned NOT NULL,
			  `queue` varchar(128) NOT NULL,
			  `class` varchar(255) NOT NULL default '',
			  `data` text,
			  `begin` int(10) unsigned NOT NULL default '0',
			  `success` tinyint(1) NOT NULL default '0',
			  `message` text,
			  `end` int(10) unsigned NOT NULL default '0',
			  PRIMARY KEY  (`id`),
			  KEY `time` (`begin`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function create_keystore_table()
	{
		if ($this->TableExists('keystore')) {
			return true;
		}

		$query = "CREATE TABLE `[|PREFIX|]keystore` (
		  `key` varchar(255) CHARACTER SET utf8 NOT NULL,
		  `value` text CHARACTER SET utf8 NOT NULL,
		  PRIMARY KEY (`key`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_email_provider_list_fields_table()
	{
		if ($this->TableExists('email_provider_list_fields')) {
			return true;
		}

		$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]email_provider_list_fields` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `email_provider_list_id` int(10) unsigned NOT NULL DEFAULT '0',
		  `provider_field_id` varchar(64) NOT NULL DEFAULT '',
		  `name` varchar(200) NOT NULL DEFAULT '',
		  `type` varchar(32) NOT NULL DEFAULT '',
		  `size` varchar(32) NOT NULL DEFAULT '',
		  `required` tinyint(1) NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `unique_email_provider_list_id_provider_field_id` (`email_provider_list_id`,`provider_field_id`),
		  KEY `idx_email_provider_list_id_name` (`email_provider_list_id`,`name`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_email_provider_lists_table()
	{
		if ($this->TableExists('email_provider_lists')) {
			return true;
		}

		$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]email_provider_lists` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `provider` varchar(64) NOT NULL DEFAULT '',
		  `provider_list_id` varchar(64) NOT NULL DEFAULT '',
		  `name` varchar(200) NOT NULL DEFAULT '',
		  `last_field_update` int(10) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `unique_provider_provider_list_id` (`provider`,`provider_list_id`),
		  KEY `idx_provider_name` (`provider`,`name`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_email_rules_table()
	{
		if ($this->TableExists('email_rules')) {
			return true;
		}

		$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]email_rules` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `provider` varchar(64) NOT NULL DEFAULT '',
		  `event` varchar(64) NOT NULL DEFAULT '',
		  `action` smallint(5) unsigned NOT NULL DEFAULT '0',
		  `provider_list_id` varchar(64) NOT NULL DEFAULT '',
		  `field_map` text NOT NULL,
		  `event_criteria` text NOT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_system_log_type_emailintegration()
	{
		if ($this->EnumExists('[|PREFIX|]system_log', 'logtype', 'emailintegration')) {
			return true;
		}

		$result = $this->AddEnumOption('[|PREFIX|]system_log', 'logtype', 'emailintegration');

		if (!$result) {
			$error = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			if ($error) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			} else {
				$this->SetError("AddEnumOption returned false without a db error.");
			}

			return false;
		}

		return true;
	}

	public function add_system_log_type_ebay()
	{
		if ($this->EnumExists('[|PREFIX|]system_log', 'logtype', 'ebay')) {
			return true;
		}

		$result = $this->AddEnumOption('[|PREFIX|]system_log', 'logtype', 'ebay');

		if (!$result) {
			$error = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			if ($error) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			} else {
				$this->SetError("AddEnumOption returned false without a db error.");
			}
			return false;
		}

		return true;
	}

	public function add_system_log_type_shoppingcomparison()
	{
		if ($this->EnumExists('[|PREFIX|]system_log', 'logtype', 'shoppingcomparison')) {
			return true;
		}

		$result = $this->AddEnumOption('[|PREFIX|]system_log', 'logtype', 'shoppingcomparison');

		if (!$result) {
			$error = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			if ($error) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			} else {
				$this->SetError("AddEnumOption returned false without a db error.");
			}

			return false;
		}

		return true;
	}

	public function add_ebay_listing_template_table()
	{
		if ($this->TableExists('ebay_listing_template')) {
			return true;
		}

		$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]ebay_listing_template` (
		 `id` int(11) unsigned NOT NULL auto_increment,
		 `name` varchar(250) default NULL,
		 `enabled` tinyint(1) unsigned default NULL,
		 `user_id` int(11) unsigned default NULL,
		 `site_id` int(5) unsigned default NULL,
		 `is_private` tinyint(1) unsigned default NULL,
		 `quantities` smallint(4) unsigned default NULL,
		 `lot_size` mediumint(6) unsigned default NULL,
		 `listing_type` varchar(50) default NULL,
		 `is_default` tinyint(1) unsigned default '0',
		 `listing_duration` varchar(10) default NULL,
		 `primary_category_options` text NOT NULL,
		 `primary_cat_id` varchar(11) default NULL,
		 `secondary_cat_id` varchar(11) default NULL,
		 `store_category1` varchar(11) default NULL,
		 `store_category2` varchar(11) default NULL,
		 `accept_best_offer` tinyint(1) unsigned default '0',
		 `payment_method` text,
		 `paypal_email` varchar(250) default NULL,
		 `payment_instruction` varchar(900) default NULL,
		 `item_country` varchar(3) default NULL,
		 `item_zip` varchar(20) default NULL,
		 `item_city` varchar(100) default NULL,
		 `use_prod_image` tinyint(1) unsigned default NULL,
		 `accept_return` tinyint(1) unsigned default '0',
		 `return_offer_as` varchar(100) default NULL,
		 `return_period` varchar(10) default NULL,
		 `return_cost_by` varchar(100) default NULL,
		 `return_policy_description` text,
		 `use_domestic_shipping` tinyint(1) unsigned NOT NULL,
		 `use_international_shipping` tinyint(1) unsigned default NULL,
		 `handling_time` tinyint(2) unsigned NOT NULL,
		 `use_salestax` tinyint(1) unsigned default NULL,
		 `sales_tax_states` varchar(3) default NULL,
		 `salestax_percent` decimal(7,4) default NULL,
		 `salestax_inc_shipping` tinyint(1) unsigned default NULL,
		 `counter_style` varchar(100) default NULL,
		 `gallery_opt` varchar(100) default NULL,
		 `featured_gallery_duration` varchar(9) NOT NULL,
		 `listing_opt` text,
		 `date_added` int(11) unsigned default NULL,
		 PRIMARY KEY  (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_ebay_shipping_table()
	{
		if ($this->TableExists('ebay_shipping')) {
			return true;
		}

		$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]ebay_shipping` (
		 `id` int(11) NOT NULL auto_increment,
		 `ebay_listing_template_id` int(11) default NULL,
		 `area` enum('Domestic','International') default NULL,
		 `cost_type` enum('Flat','Calculated','Freight') default NULL,
		 `offer_pickup` tinyint(1) default NULL,
		 `pickup_cost` decimal(20,2) default NULL,
		 `is_free_shipping` tinyint(1) default NULL,
		 `handling_cost` decimal(20,2) default NULL,
		 `package_type` varchar(100) default NULL,
		 `get_it_fast` tinyint(1) default NULL,
		 PRIMARY KEY  (`id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_ebay_shipping_serv_table()
	{
		if ($this->TableExists('ebay_shipping_serv')) {
			return true;
		}

		$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]ebay_shipping_serv` (
		 `id` int(11) NOT NULL auto_increment,
		 `ebay_shipping_id` int(11) default NULL,
		 `name` varchar(100) default NULL,
		 `cost` decimal(20,2) default NULL,
		 `additional_cost` decimal(20,2) default NULL,
		 `ship_to_locations` text NOT NULL,
		 PRIMARY KEY  (`id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_ebay_items_table()
	{
		if ($this->TableExists('ebay_items')) {
			return true;
		}

		$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]ebay_items` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `product_id` int(11) NOT NULL,
		  `ebay_item_id` varchar(19) DEFAULT NULL,
		  `title` varchar(55) DEFAULT NULL,
		  `start_time` varchar(28) NOT NULL,
		  `end_time` varchar(28) NOT NULL,
		  `datetime_listed` int(11) DEFAULT NULL,
		  `listing_type` varchar(50) DEFAULT NULL,
		  `listing_status` varchar(50) DEFAULT NULL,
		  `current_price_currency` varchar(3) DEFAULT NULL,
		  `current_price` decimal(20,4) DEFAULT NULL,
		  `buyitnow_price_currency` varchar(3) DEFAULT NULL,
		  `buyitnow_price` decimal(20,4) DEFAULT NULL,
		  `site_id` int(5) DEFAULT NULL,
		  `ebay_item_link` varchar(255) DEFAULT NULL,
		  `quantity_remaining` int(5) DEFAULT NULL,
		  `bid_count` int(5) DEFAULT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_ebay_item_fees_table()
	{
		if ($this->TableExists('ebay_item_fees')) {
			return true;
		}

		$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]ebay_item_fees` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `item_id` int(11) NOT NULL,
		  `name` varchar(50) NOT NULL,
		  `amount` double(20,4) NOT NULL,
		  `currency_code` varchar(3) NOT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_ebay_listing_prices_table()
	{
		if ($this->TableExists('ebay_listing_prices')) {
			return true;
		}

		$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]ebay_listing_prices` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `ebay_listing_template_id` int(11) DEFAULT NULL,
		  `selected_type` enum('CustomPrice','ProductPrice','PriceExtra','None') DEFAULT NULL,
		  `price` decimal(20,4) DEFAULT NULL,
		  `price_type` enum('Starting','Reserve','Buy') DEFAULT NULL,
		  `calculate_operator` varchar(20) DEFAULT NULL,
		  `calculate_option` varchar(50) DEFAULT NULL,
		  `calculate_price` decimal(20,4) DEFAULT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_ebay_categories_table()
	{
		if ($this->TableExists('ebay_categories')) {
			return true;
		}

		$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]ebay_categories` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `name` varchar(30) NOT NULL,
		  `category_id` varchar(11) NOT NULL,
		  `parent_id` varchar(11) NOT NULL,
		  `ebay_site_id` varchar(4) NOT NULL,
		  `is_leaf` tinyint(1) unsigned NOT NULL,
		  `lot_size_enabled` tinyint(1) unsigned NOT NULL,
		  `best_offer_enabled` tinyint(1) unsigned NOT NULL,
		  `reserve_price_allowed` tinyint(1) unsigned NOT NULL,
		  `minimum_reserve_price` double(20,4) NOT NULL,
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `category_id_2` (`category_id`,`ebay_site_id`),
		  KEY `parent_id` (`parent_id`),
		  KEY `ebay_site_id` (`ebay_site_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_products_prodminqty()
	{
		if ($this->ColumnExists('[|PREFIX|]products', 'prodminqty')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]products ADD COLUMN prodminqty INT(10) UNSIGNED NOT NULL DEFAULT '0'";

		if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_products_prodmaxqty()
	{
		if ($this->ColumnExists('[|PREFIX|]products', 'prodmaxqty')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]products ADD COLUMN prodmaxqty INT(10) UNSIGNED NOT NULL DEFAULT '0'";

		if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_comment_system_defaults()
	{
		$query = "SELECT * FROM [|PREFIX|]module_vars WHERE modulename = 'comments_builtincomments'";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$varNames = array();
		while ($varRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
			$varNames[] = $varRow['variablename'];
		}

		$insert = array(
			'modulename'	=> 'comments_builtincomments',
			'variableval'	=> '1',
		);

		$varsToCheck = array('commenttypes', 'is_setup');

		foreach ($varsToCheck as $var) {
			if (in_array($var, $varNames)) {
				continue;
			}

			$insert['variablename'] = $var;

			if (!$GLOBALS['ISC_CLASS_DB']->InsertQuery('module_vars', $insert)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_product_comparisons_table()
	{
		if ($this->TableExists('product_comparisons')) {
			return true;
		}

		$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]product_comparisons` (
			`product_id` int(11) NOT NULL,
			`comparison_id` varchar(255) NOT NULL,
		  PRIMARY KEY  (`product_id`, `comparison_id`),
		  KEY `i_product_comparisons_comparison_id` (`comparison_id`),
		  KEY `i_product_comparisons_product_id` (`product_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_alter_category_taxonomies_table()
	{
		if ($this->TableExists('alternate_category_taxonomies')) {
			return true;
		}

		$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]alternate_category_taxonomies` (
			`taxonomy_id` varchar(255) NOT NULL,
			`filename` varchar(255) NOT NULL,
			`lastupdated` int(11) NOT NULL,
		  PRIMARY KEY  (`taxonomy_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_alternate_categories_table()
	{
		if ($this->TableExists('alternate_categories')) {
			return true;
		}

		$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]alternate_categories` (
			`taxonomy_id` varchar(255) NOT NULL,
			`category_id` int(11) NOT NULL,
			`parent_id` int(11) NOT NULL,
			`name` varchar(255) NOT NULL,
			`path` varchar(255) NOT NULL,
			`num_children` int(11) NOT NULL default '0',
		  PRIMARY KEY  (`taxonomy_id`, `category_id`),
		  KEY `i_alternate_categories_path` (`path`),
		  KEY `i_alternate_categories_taxonomy_id` (`taxonomy_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_categories_to_alternate_categories_table()
	{
		if ($this->TableExists('categories_to_alternate_categories')) {
			return true;
		}

		$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]categories_to_alternate_categories` (
		  `category_id` int(11) NOT NULL,
		  `alternate_taxonomy_id` varchar(255) NOT NULL,
			`alternate_category_id` int(11) NOT NULL,
		  PRIMARY KEY  (`category_id`, `alternate_taxonomy_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_cataltcategoriescache_to_categories()
	{
		if ($this->ColumnExists('[|PREFIX|]categories', 'cataltcategoriescache')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]categories ADD COLUMN cataltcategoriescache TEXT";

		if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function cleanup_product_tagassociations_table()
	{
		// clean up left over tag assoc after product has been deleted
		$query = "
			DELETE FROM
				[|PREFIX|]product_tagassociations
			WHERE
				productid not in (
				SELECT DISTINCT
					productid
				FROM
					[|PREFIX|]products
				)";

		if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_selectbox_configurable_field()
	{
		if (!$this->ColumnExists('[|PREFIX|]product_configurable_fields', 'fieldselectoptions')) {
			$query = "ALTER TABLE `[|PREFIX|]product_configurable_fields` ADD `fieldselectoptions` TEXT NOT NULL";

			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]order_configurable_fields', 'fieldselectoptions')) {
			$query = "ALTER TABLE `[|PREFIX|]order_configurable_fields` ADD `fieldselectoptions` TEXT NOT NULL";

			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function createTaxZonesTable()
	{
		if($this->tableExists('tax_zones')) {
			return true;
		}

		$query = "
			CREATE TABLE `[|PREFIX|]tax_zones` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `name` varchar(200) NOT NULL,
			  `type` enum('country','state','zip') DEFAULT 'country',
			  `enabled` tinyint(1) NOT NULL DEFAULT '1',
			  `default` tinyint(1) NOT NULL DEFAULT '0',
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function createDefaultTaxZone()
	{
		$query = "
			SELECT id
			FROM [|PREFIX|]tax_zones
			WHERE `default`=1
		";
		if($GLOBALS['ISC_CLASS_DB']->fetchOne($query)) {
			return true;
		}

		$newZone = array(
			'name' => 'Default Zone',
			'default' => 1
		);
		if(!$GLOBALS['ISC_CLASS_DB']->insertQuery('tax_zones', $newZone)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function createTaxClassesTable()
	{
		$query = "
			CREATE TABLE IF NOT EXISTS `[|PREFIX|]tax_classes` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `name` varchar(200) NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function createDefaultTaxClasses()
	{
		$query = "TRUNCATE [|PREFIX|]tax_classes";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		foreach($this->taxClasses as $id => $name) {
			if(!$GLOBALS['ISC_CLASS_DB']->insertQuery('tax_classes', array(
				'id' => $id,
				'name' => $name
			))) {
				$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function createTaxZoneLocationsTable()
	{
		$query = "
			CREATE TABLE IF NOT EXISTS `[|PREFIX|]tax_zone_locations` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `tax_zone_id` int(11) unsigned NOT NULL,
			  `type` enum('country','state','zip') NOT NULL DEFAULT 'country',
			  `value_id` int(11) unsigned DEFAULT '0',
			  `value` varchar(200) DEFAULT '',
			  `country_id` int(11) DEFAULT '0',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function createTableTaxZoneCustomerGroups()
	{
		$query = "
			CREATE TABLE IF NOT EXISTS `[|PREFIX|]tax_zone_customer_groups` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `tax_zone_id` int(11) unsigned NOT NULL,
			  `customer_group_id` int(11) unsigned NOT NULL DEFAULT '0',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function createTaxRatesTable()
	{
		if($this->tableExists('tax_rates_new') ||
			$this->columnExists('[|PREFIX|]tax_rates', 'tax_zone_id')) {
				return true;
		}

		$query = "
			CREATE TABLE IF NOT EXISTS `[|PREFIX|]tax_rates_new` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `tax_zone_id` int(11) unsigned NOT NULL,
			  `name` varchar(200) NOT NULL,
			  `priority` int(11) unsigned NOT NULL DEFAULT '0',
			  `enabled` tinyint(1) NOT NULL DEFAULT '1',
			  `default_rate` decimal(20, 4) NOT NULL DEFAULT '0.00',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function createTaxRateClassRatesTable()
	{
		$query = "
			CREATE TABLE IF NOT EXISTS `[|PREFIX|]tax_rate_class_rates` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `tax_rate_id` int(11) unsigned NOT NULL,
			  `tax_class_id` int(11) unsigned NOT NULL,
			  `rate` decimal(20,4) NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function createOrderTaxesTable()
	{
		$query = "
			CREATE TABLE IF NOT EXISTS `[|PREFIX|]order_taxes` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `order_id` int(11) signed NOT NULL,
			  `order_address_id` int unsigned NOT NULL,
			  `tax_rate_id` int(11) unsigned NOT NULL,
			  `tax_class_id` int(11) unsigned NOT NULL,
			  `name` varchar(200) NOT NULL,
			  `class` varchar(200) NOT NULL,
			  `rate` decimal(20,4) NOT NULL,
			  `priority` int(11) unsigned NOT NULL DEFAULT '0',
			  `priority_amount` decimal(20, 4) NOT NULL default '0',
			  `line_amount` decimal(20, 4) NOT NULL default '0',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function createProductTaxPricingTable()
	{
		$query = "
			CREATE TABLE IF NOT EXISTS `[|PREFIX|]product_tax_pricing` (
			  `price_reference` decimal(20, 4) NOT NULL DEFAULT '0',
			  `calculated_price` decimal(20, 4) NOT NULL DEFAULT '0',
			  `tax_zone_id` int(11) unsigned NOT NULL default '0',
			  `tax_class_id` int(11) unsigned NOT NULL default '0',
			  UNIQUE KEY (`price_reference`, `tax_zone_id`, `tax_class_id`),
			  KEY (`tax_zone_id`),
			  KEY (`tax_class_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function createOrderAddressesTable()
	{
		$query = "
			CREATE TABLE IF NOT EXISTS `[|PREFIX|]order_addresses` (
				`id` int unsigned not null auto_increment,
				`order_id` int unsigned not null,
				`first_name` varchar(255) not null default '',
				`last_name` varchar(255) not null default '',
				`company` varchar(100) not null default '',
				`address_1` varchar(255) not null default '',
				`address_2` varchar(255) not null default '',
				`city` varchar(50) not null default '',
				`zip` varchar(20) not null default '',
				`country` varchar(50) not null default '',
				`country_iso2` varchar(2) not null default '',
				`country_id` int unsigned not null default '0',
				`state` varchar(100) not null default '',
				`state_id` int unsigned not null default '0',
				`email` varchar(250) not null default '',
				`phone` varchar(250) not null default '',
				`form_session_id` int unsigned NOT NULL default '0',
				`total_items` int unsigned NOT NULL default '0',
				PRIMARY KEY(id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function createOrderShippingTable()
	{
		$query = "
			CREATE TABLE IF NOT EXISTS `[|PREFIX|]order_shipping` (
				`id` int unsigned not null auto_increment,
				`order_address_id` int unsigned not null,
				`order_id` int unsigned not null,
				`base_cost` decimal(20, 4) not null default '0',
				`cost_ex_tax` decimal(20, 4) not null default '0',
				`cost_inc_tax` decimal(20, 4) not null default '0',
				`tax` decimal(20, 4) not null default '0',
				`method` varchar(250) not null default '',
				`module` varchar(100) not null default '',
				`tax_class_id` int unsigned not null default '0',
				`base_handling_cost` decimal(20,4) NOT NULL default '0',
				`handling_cost_ex_tax` decimal(20,4) NOT NULL default '0',
				`handling_cost_inc_tax` decimal(20,4) NOT NULL default '0',
				`handling_cost_tax` decimal(20,4) NOT NULL default '0',
				`handling_cost_tax_class_id` decimal(20,4) NOT NULL default '0',
				`shipping_zone_id` int unsigned not null default '0',
				`shipping_zone_name` varchar(250) not null default '',
				`total_shipped` int unsigned not null default '0',
				PRIMARY KEY(`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function addProductTaxClassIdColumn()
	{
		if($this->columnExists('[|PREFIX|]products', 'tax_class_id')) {
			return true;
		}

		$query = "
			ALTER TABLE [|PREFIX|]products
			ADD
				`tax_class_id` int unsigned NOT NULL default '0'
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function updateProdIsTaxableProducts()
	{
		// Column already gone? Done.
		if(!$this->columnExists('[|PREFIX|]products', 'prodistaxable')) {
			return true;
		}

		$name = 'Non-Taxable Items';
		$query = "
			SELECT id
			FROM [|PREFIX|]tax_classes
			WHERE name='".$name."'
		";
		$nonTaxableClassId = $GLOBALS['ISC_CLASS_DB']->fetchOne($query);

		$query = "
			UPDATE [|PREFIX|]products
			SET tax_class_id='".$nonTaxableClassId."'
			WHERE prodistaxable=0
			LIMIT 500
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		// Processed no records. Finished.
		if(!$GLOBALS['ISC_CLASS_DB']->numAffected()) {
			return true;
		}

		// Still processed records. Return to this step on next run
		return false;
	}

	public function addOrderCouponAmount()
	{
		if($this->columnExists('[|PREFIX|]order_coupons', 'applied_discount')) {
			return true;
		}

		$query = "
			ALTER TABLE [|PREFIX|]order_coupons
			ADD `applied_discount` decimal(20, 4) NOT NULL default '0'
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function createNewOrderTaxColumns()
	{
		// Add column after => New column
		$newColumns = array(
			// Columns that will hold old data
			'ordsubtotal' => 'subtotal_ex_tax',
			'ordtaxtotal' => 'total_tax',
			'ordshipcost' => 'shipping_cost_ex_tax',
			'ordhandlingcost' => 'handling_cost_ex_tax',
			'ordtotalamount' => 'total_ex_tax',

			// Entirely new columns
			'subtotal_ex_tax' => 'subtotal_inc_tax',
			'subtotal_inc_tax' => 'subtotal_tax',
			'shipping_cost_ex_tax '=> 'shipping_cost_inc_tax',
			'shipping_cost_inc_tax' => 'shipping_cost_tax',
			'shipping_cost_tax' => 'shipping_cost_tax_class_id',
			'shipping_cost_tax_class_id' => 'base_handling_cost',
			'handling_cost_ex_tax' => 'handling_cost_inc_tax',
			'handling_cost_inc_tax' => 'handling_cost_tax',
			'handling_cost_tax' => 'handling_cost_tax_class_id',
			'handling_cost_tax_class_id' => 'base_wrapping_cost',
			'base_wrapping_cost' => 'wrapping_cost_inc_tax',
			'wrapping_cost_inc_tax' => 'wrapping_cost_ex_tax',
			'wrapping_cost_ex_tax' => 'wrapping_cost_tax',
			'wrapping_cost_tax' => 'wrapping_cost_tax_class_id',
			'total_ex_tax' => 'total_inc_tax',
			'shipping_address_count' => 'coupon_discount',
			'total_tax' => 'base_shipping_cost',
		);
		foreach($newColumns as $addAfter => $newColumn) {
			if($this->columnExists('[|PREFIX|]orders', $newColumn)) {
				continue;
			}

			// Add the column
			$query = "
				ALTER TABLE [|PREFIX|]orders
				ADD ".$newColumn." decimal(20, 4) NOT NULL default '0'
					AFTER ".$addAfter;
			if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
				$this->setError($newColumn.' create: '.$GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}

			// Break after doing one - we only want to add one column per page load
			return false;
		}

		// All finished, progress to the next step
		return true;
	}

	public function addNewebayOrderIdColumn()
	{
		if($this->columnExists('[|PREFIX|]orders', 'ebay_order_id')) {
			return true;
		}

		$query = "
			ALTER TABLE [|PREFIX|]orders
			ADD ebay_order_id varchar(19) NOT NULL default '0'
			AFTER ordlastmodified
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function createNewOrderProductTaxColumns()
	{
		// Add column after => New column
		$newColumns = array(
			// Columns that will hold old data
			'ordprodtype' => 'base_price',
			'ordprodcost' => 'price_ex_tax',
			'ordprodcostprice' => 'cost_price_ex_tax',
			'ordprodwrapcost' => 'wrapping_cost_ex_tax',
			'ordprodid' => 'base_cost_price',
			'ordprodwrapname' => 'base_wrapping_cost',


			// Entirely new columns
			'price_ex_tax' => 'price_inc_tax',
			'price_inc_tax' => 'price_tax',
			'price_tax' => 'base_total',
			'base_total' => 'total_ex_tax',
			'total_ex_tax' => 'total_inc_tax',
			'total_inc_tax' => 'total_tax',
			'cost_price_ex_tax' => 'cost_price_inc_tax',
			'cost_price_inc_tax' => 'cost_price_tax',
			'wrapping_cost_ex_tax' => 'wrapping_cost_inc_tax',
			'wrapping_cost_inc_tax' => 'wrapping_cost_tax',
		);
		foreach($newColumns as $addAfter => $newColumn) {
			if($this->columnExists('[|PREFIX|]order_products', $newColumn)) {
				continue;
			}

			// Add the column
			$query = "
				ALTER TABLE [|PREFIX|]order_products
				ADD ".$newColumn." decimal(20, 4) NOT NULL default '0'
					AFTER ".$addAfter;
			if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
				$this->setError($newColumn.' create: '.$GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}

			// Break after doing one - we only want to add one column per page load
			return false;
		}

		// All finished, progress to the next step
		return true;
	}
	public function addOrderShippingAddressCountColumn()
	{
		if($this->columnExists('[|PREFIX|]orders', 'shipping_address_count')) {
			return true;
		}

		$query = "
			ALTER TABLE [|PREFIX|]orders
			ADD shipping_address_count int unsigned NOT NULL default '0'
				AFTER orddiscountamount
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function addOrderProductOrderAddressIdColumn()
	{
		if($this->columnExists('[|PREFIX|]order_products', 'order_address_id')) {
			return true;
		}

		$query = "
			ALTER TABLE [|PREFIX|]order_products
			ADD order_address_id int unsigned NOT NULL default '0'
				AFTER ordprodfixedshippingcost
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function addOrderProductEbayItemIdColumn()
	{
		if($this->columnExists('[|PREFIX|]order_products', 'ebay_item_id')) {
			return true;
		}

		$query = "
			ALTER TABLE [|PREFIX|]order_products
			ADD ebay_item_id varchar(19) NOT NULL default ''
				AFTER order_address_id
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function updateOrderValuesWithTaxInfo()
	{
		if(!$this->columnExists('[|PREFIX|]orders', 'ordsubtotal')) {
			return true;
		}

		if(isset($_SESSION['updateOrderValuesStart'])) {
			$start = (int)$_SESSION['updateOrderValuesStart'];
		}
		else {
			$_SESSION['updateOrderValuesStart'] = 0;
			$start = 0;
		}

		$query = "
			SELECT o.*, (
				SELECT SUM(op.ordprodwrapcost)
				FROM [|PREFIX|]order_products op
				WHERE op.orderorderid = o.orderid
			) AS wrappingcost, (
				SELECT tr.taxratebasedon
				FROM [|PREFIX|]tax_rates tr
				WHERE tr.taxratename = o.ordtaxname
				LIMIT 1
			) AS taxratebasedon
			FROM [|PREFIX|]orders o
			ORDER BY o.orderid
			LIMIT ".$start.", 200
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		while($order = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			if($order['ordisdigital']) {
				$addressCount = 0;
			}
			else {
				$addressCount = 1;
			}
			$orderTotal = $order['ordtotalamount'] - $order['ordgiftcertificateamount'] - $order['ordstorecreditamount'];
			if($orderTotal < 0) {
				$orderTotal = 0;
			}

			$updatedColumns = array(
				'subtotal_ex_tax' => $order['ordsubtotal'],
				'subtotal_inc_tax' => $order['ordsubtotal'],
				'shipping_cost_ex_tax' => $order['ordshipcost'],
				'shipping_cost_inc_tax' => $order['ordshipcost'],
				'shipping_cost_tax_class_id' => 0,
				'handling_cost_ex_tax' => $order['ordhandlingcost'],
				'handling_cost_inc_tax' => $order['ordhandlingcost'],
				'handling_cost_tax_class_id' => 0,
				'wrapping_cost_inc_tax' => $order['wrappingcost'],
				'wrapping_cost_ex_tax' => $order['wrappingcost'],
				'wrapping_cost_tax_class_id' => 0,
				'total_ex_tax' => $orderTotal,
				'total_inc_tax' => $orderTotal,
				'shipping_address_count' => $addressCount,
				'total_tax' => $order['ordtaxtotal'],
				'base_shipping_cost' => $order['ordshipcost'],
				'base_handling_cost' => $order['ordhandlingcost'],
				'base_wrapping_cost' => $order['wrappingcost'],
			);

			if($order['ordtaxtotal'] > 0 && $order['ordtaxrate'] > 0) {
				if($order['ordtotalincludestax'] == 1) {
					$updatedColumns['total_ex_tax'] -= $updatedColumns['total_tax'];
					$updatedColumns['subtotal_ex_tax'] -= getClass('ISC_TAX')
						->calculateTax($updatedColumns['subtotal_ex_tax'], $order['ordtaxrate'], true);
					if($order['taxratebasedon'] == 'subtotal_and_shipping') {
						$updatedColumns['shipping_cost_ex_tax'] -= getClass('ISC_TAX')
							->calculateTax($updatedColumns['shipping_cost_ex_tax'], $order['ordtaxrate'], true);
						$updatedColumns['handling_cost_ex_tax'] -= getClass('ISC_TAX')
							->calculateTax($updatedColumns['handling_cost_ex_tax'], $order['ordtaxrate'], true);
					}
					$updatedColumns['wrapping_cost_ex_tax'] -= getClass('ISC_TAX')
						->calculateTax($updatedColumns['wrapping_cost_ex_tax'], $order['ordtaxrate'], true);
				}
				else {
					$updatedColumns['subtotal_inc_tax'] += getClass('ISC_TAX')
						->calculateTax($updatedColumns['subtotal_inc_tax'], $order['ordtaxrate'], false);
					if($order['taxratebasedon'] == 'subtotal_and_shipping') {
						$updatedColumns['shipping_cost_inc_tax'] += getClass('ISC_TAX')
							->calculateTax($updatedColumns['shipping_cost_inc_tax'], $order['ordtaxrate'], false);
						$updatedColumns['handling_cost_inc_tax'] += getClass('ISC_TAX')
							->calculateTax($updatedColumns['handling_cost_inc_tax'], $order['ordtaxrate'], false);
					}
					$updatedColumns['wrapping_cost_inc_tax'] += getClass('ISC_TAX')
						->calculateTax($updatedColumns['wrapping_cost_inc_tax'], $order['ordtaxrate'], false);
				}
			}

			// Calculate the tax amounts
			$updatedColumns['shipping_cost_tax'] = $updatedColumns['shipping_cost_inc_tax'] -
				$updatedColumns['shipping_cost_ex_tax'];
			$updatedColumns['handling_cost_tax'] = $updatedColumns['handling_cost_inc_tax'] -
				$updatedColumns['handling_cost_ex_tax'];
			$updatedColumns['wrapping_cost_tax'] = $updatedColumns['wrapping_cost_inc_tax'] -
				$updatedColumns['wrapping_cost_ex_tax'];

			// Round all amounts
			foreach($updatedColumns as &$value) {
				$value = getClass('ISC_TAX')->round($value, 4);
			}

			// Update the order
			if(!$GLOBALS['ISC_CLASS_DB']->updateQuery('orders', $updatedColumns, "orderid='".$order['orderid']."'")) {
				$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}
		}

		// No records processed. Finished.
		if(!isset($updatedColumns)) {
			return true;
		}

		$_SESSION['updateOrderValuesStart'] += 200;
		return false;
	}



	public function updateOrderProductValuesWithTaxInfo()
	{
		if(!$this->columnExists('[|PREFIX|]order_products', 'ordprodcost')) {
			return true;
		}

		if(isset($_SESSION['updateOrderProductValuesStart'])) {
			$start = (int)$_SESSION['updateOrderProductValuesStart'];
		}
		else {
			$_SESSION['updateOrderProductValuesStart'] = 0;
			$start = 0;
		}

		$query = "
			SELECT op.*, o.ordtaxrate, o.ordtotalincludestax, (
				SELECT a.id
				FROM [|PREFIX|]order_addresses a
				WHERE a.order_id = op.orderorderid
			) AS order_address_id
			FROM [|PREFIX|]order_products op
			JOIN [|PREFIX|]orders o ON (o.orderid=op.orderorderid)
			ORDER BY op.orderprodid
			LIMIT ".$start.", 200
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		while($product = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			$updatedColumns = array(
				'price_ex_tax' => $product['ordprodcost'],
				'price_inc_tax' => $product['ordprodcost'],
				'total_ex_tax' => $product['ordprodcost'] * $product['ordprodqty'],
				'total_inc_tax' => $product['ordprodcost'] * $product['ordprodqty'],
				'cost_price_ex_tax' => $product['ordprodcostprice'],
				'cost_price_inc_tax' => $product['ordprodcostprice'],
				'wrapping_cost_ex_tax' => $product['ordprodwrapcost'],
				'wrapping_cost_inc_tax' => $product['ordprodwrapcost'],
				'order_address_id' => $product['order_address_id'],
				'base_price' => $product['ordprodcost'],
				'base_total' => $product['ordprodcost'] * $product['ordprodqty'],
				'base_cost_price' => $product['ordprodcostprice'],
				'base_wrapping_cost' => $product['ordprodwrapcost'],
			);

			// Product isn't taxable. Set appropriate tax class and trick the product
			// in to thinking no tax was applied.
			if(!$product['ordprodistaxable']) {
				$product['ordtaxtotal'] = 0;
			}

			if($product['ordtaxrate'] > 0) {
				if($product['ordtotalincludestax'] == 1) {
					$updatedColumns['price_ex_tax'] -= getClass('ISC_TAX')
						->calculateTax($updatedColumns['price_ex_tax'], $product['ordtaxrate'], true);
					$updatedColumns['total_ex_tax'] -= getClass('ISC_TAX')
						->calculateTax($updatedColumns['total_ex_tax'], $product['ordtaxrate'], true);
					$updatedColumns['cost_price_ex_tax'] -= getClass('ISC_TAX')
						->calculateTax($updatedColumns['cost_price_ex_tax'], $product['ordtaxrate'], true);
					$updatedColumns['wrapping_cost_ex_tax'] -= getClass('ISC_TAX')
						->calculateTax($updatedColumns['wrapping_cost_ex_tax'], $product['ordtaxrate'], true);
				}
				else {
					$updatedColumns['price_inc_tax'] += getClass('ISC_TAX')
						->calculateTax($updatedColumns['price_inc_tax'], $product['ordtaxrate'], false);
					$updatedColumns['total_inc_tax'] += getClass('ISC_TAX')
						->calculateTax($updatedColumns['total_inc_tax'], $product['ordtaxrate'], false);
					$updatedColumns['cost_price_inc_tax'] += getClass('ISC_TAX')
						->calculateTax($updatedColumns['cost_price_inc_tax'], $product['ordtaxrate'], false);
					$updatedColumns['wrapping_cost_inc_tax'] += getClass('ISC_TAX')
						->calculateTax($updatedColumns['wrapping_cost_inc_tax'], $product['ordtaxrate'], false);
				}
			}

			// Calculate the tax amounts
			$updatedColumns['price_tax'] = $updatedColumns['price_inc_tax'] -
				$updatedColumns['price_ex_tax'];
			$updatedColumns['total_tax'] = $updatedColumns['price_tax'] * $product['ordprodqty'];
			$updatedColumns['cost_price_tax'] = $updatedColumns['cost_price_inc_tax'] -
				$updatedColumns['cost_price_ex_tax'];
			$updatedColumns['wrapping_cost_tax'] = $updatedColumns['wrapping_cost_inc_tax'] -
				$updatedColumns['wrapping_cost_ex_tax'];

			// Round all amounts
			foreach($updatedColumns as &$value) {
				$value = getClass('ISC_TAX')->round($value, 4);
			}

			// Update the product
			if(!$GLOBALS['ISC_CLASS_DB']->updateQuery('order_products', $updatedColumns, "orderprodid='".$product['orderprodid']."'")) {
				$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}
		}

		// No records processed. Finished.
		if(!isset($updatedColumns)) {
			return true;
		}

		$_SESSION['updateOrderProductValuesStart'] += 200;
		return false;
	}

	// Convert existing tax rates
	public function convertOldTaxRatesToNewTaxRates()
	{
		// Already done this. Don't do it again.
		if($this->columnExists('[|PREFIX|]tax_rates', 'tax_zone_id')) {
			return true;
		}

		$query = "TRUNCATE [|PREFIX|]tax_rates_new";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		$query = "
			SELECT *
			FROM [|PREFIX|]tax_rates
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		if(!$result) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		while($rate = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			$states = explode(',', trim($rate['taxratestates'], ','));
			if(!$rate['taxratecountry']) {
				// All countries - this is the default zone
				$taxZoneId = 1;
			}
			else {
				if(in_array(0, $states) || empty($states)) {
					$type = 'country';
					// All states in country
				}
				else {
					$type = 'state';
				}

				$newTaxZone = array(
					'name' => $rate['taxratename'],
					'type' => $type,
					'enabled' => $rate['taxratestatus'],
				);
				$taxZoneId = $GLOBALS['ISC_CLASS_DB']->insertQuery('tax_zones', $newTaxZone);
				if(!$taxZoneId) {
					$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
					return false;
				}

				// Insert locations
				if($type == 'country') {
					$newLocation = array(
						'tax_zone_id' => $taxZoneId,
						'type' => 'country',
						'value_id' => $rate['taxratecountry'],
						'value' => getCountryById($rate['taxratecountry']),
					);
					if(!$GLOBALS['ISC_CLASS_DB']->insertQuery('tax_zone_locations', $newLocation)) {
						$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
						return false;
					}
				}
				else if($type == 'state') {
					foreach($states as $state) {
						$newLocation = array(
							'tax_zone_id' => $taxZoneId,
							'type' => 'state',
							'value_id' => $state,
							'value' => getStateById($state),
							'country_id' => $rate['taxratecountry']
						);
						if(!$GLOBALS['ISC_CLASS_DB']->insertQuery('tax_zone_locations', $newLocation)) {
							$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
							return false;
						}
					}
				}
			}

			$newTaxRate = array(
				'tax_zone_id' => $taxZoneId,
				'name' => $rate['taxratename'],
				'default_rate' => $rate['taxratepercent'],
			);
			$taxRateId = $GLOBALS['ISC_CLASS_DB']->insertQuery('tax_rates_new', $newTaxRate);
			if(!$taxRateId) {
				$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}

			// Insert the tax class rates
			$shippingRate = 0;
			if($rate['taxratebasedon'] == 'subtotal_and_shipping') {
				$shippingRate = $rate['taxratepercent'];
			}

			// Non-taxable
			$newTaxClassRate = array(
				'tax_rate_id' => $taxRateId,
				'tax_class_id' => 1,
				'rate' => 0
			);
			if(!$GLOBALS['ISC_CLASS_DB']->insertQuery('tax_rate_class_rates', $newTaxClassRate)) {
				$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}

			// Shipping
			$newTaxClassRate = array(
				'tax_rate_id' => $taxRateId,
				'tax_class_id' => 2,
				'rate' => $shippingRate
			);
			if(!$GLOBALS['ISC_CLASS_DB']->insertQuery('tax_rate_class_rates', $newTaxClassRate)) {
				$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}

			// Wrapping
			$newTaxClassRate = array(
				'tax_rate_id' => $taxRateId,
				'tax_class_id' => 3,
				'rate' => $rate['taxratepercent']
			);
			if(!$GLOBALS['ISC_CLASS_DB']->insertQuery('tax_rate_class_rates', $newTaxClassRate)) {
				$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}
		}

		return true;
	}

	// Convert order taxes in to order_taxes rows
	public function convertOrderTaxesInToRows()
	{
		if(!$this->columnExists('[|PREFIX|]orders', 'ordtaxrate')) {
			return true;
		}

		if(!isset($_SESSION['taxConversionLast'])) {
			$query = "TRUNCATE [|PREFIX|]order_taxes";
			if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
				$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}

			$_SESSION['taxConversionLast'] = 0;
		}

		$query = "
			SELECT orderid, ordtaxname, ordtaxtotal, ordtaxrate, (
				SELECT a.id
				FROM [|PREFIX|]order_addresses a
				WHERE a.order_id = o.orderid
			) AS order_address_id
			FROM [|PREFIX|]orders o
			ORDER BY orderid
			LIMIT ".(int)$_SESSION['taxConversionLast'].", 100
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		if(!$result) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		$hasOrders = false;
		while($order = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			$hasOrders = true;
			if(!$order['ordtaxtotal']) {
				continue;
			}

			$newTax = array(
				'order_id' => $order['orderid'],
				'order_address_id' => (int)$order['order_address_id'],
				'tax_rate_id' => 0,
				'tax_class_id' => 0,
				'name' => $order['ordtaxname'],
				'class' => 'Default',
				'rate' => $order['ordtaxrate'],
				'line_amount' => $order['ordtaxtotal'],
				'priority_amount' => $order['ordtaxtotal'],
			);
			if(!$GLOBALS['ISC_CLASS_DB']->insertQuery('order_taxes', $newTax)) {
				$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}
		}

		// Finished
		if(!$hasOrders) {
			return true;
		}

		$_SESSION['taxConversionLast'] += 100;
		return false;
	}

	public function convertOrderShippingAddresses()
	{
		if (!$this->columnExists('[|PREFIX|]orders', 'ordshipfirstname')) {
			return true;
		}

		if(!isset($_SESSION['orderAddressConvert'])) {
			$query = "TRUNCATE [|PREFIX|]order_addresses";
			if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
				$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}

			$query = "TRUNCATE [|PREFIX|]order_shipping";
			if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
				$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}

			$start = 0;
			$_SESSION['orderAddressConvert'] = 0;
		}
		else {
			$start = (int)$_SESSION['orderAddressConvert'];
		}

		$query = "
			SELECT o.*, (
				SELECT SUM(op.ordprodqty)
				FROM [|PREFIX|]order_products op
				WHERE op.orderorderid = o.orderid AND op.ordprodtype='physical'
			) AS total_items
			FROM [|PREFIX|]orders o
			ORDER BY o.orderid ASC
			LIMIT ".$start.", 200
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		while($order = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			$newAddress = array(
				'order_id' => $order['orderid'],
				'first_name' => $order['ordshipfirstname'],
				'last_name' => $order['ordshiplastname'],
				'company' => $order['ordshipcompany'],
				'address_1' => $order['ordshipstreet1'],
				'address_2' => $order['ordshipstreet2'],
				'city' => $order['ordshipsuburb'],
				'zip' => $order['ordshipzip'],
				'country' => $order['ordshipcountry'],
				'country_iso2' => $order['ordshipcountrycode'],
				'country_id' => $order['ordshipcountryid'],
				'state' => $order['ordshipstate'],
				'state_id' => $order['ordshipstateid'],
				'email' => $order['ordshipemail'],
				'phone' => $order['ordshipphone'],
				'total_items' => (int)$order['total_items'],
			);
			$addressId = $GLOBALS['ISC_CLASS_DB']->insertQuery('order_addresses', $newAddress);
			if(!$addressId) {
				$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}

			$newShipping = array(
				'order_address_id' => $addressId,
				'order_id' => $order['orderid'],
				'cost_ex_tax' => $order['shipping_cost_ex_tax'],
				'cost_inc_tax' => $order['shipping_cost_inc_tax'],
				'tax' => $order['shipping_cost_tax'],
				'method' => $order['ordshipmethod'],
				'module' => $order['ordershipmodule'],
				'tax_class_id' => 0,
				'handling_cost_ex_tax' => $order['handling_cost_ex_tax'],
				'handling_cost_inc_tax' => $order['handling_cost_inc_tax'],
				'handling_cost_tax' => $order['handling_cost_tax'],
				'handling_cost_tax_class_id' => 0,
				'shipping_zone_id' => $order['ordshippingzoneid'],
				'shipping_zone_name' => $order['ordshippingzone'],
				'total_shipped' => $order['ordtotalshipped'],
				'base_cost' => $order['base_shipping_cost'],
				'base_handling_cost' => $order['base_handling_cost'],
			);
			$shippingId = $GLOBALS['ISC_CLASS_DB']->insertQuery('order_shipping', $newShipping);
			if(!$shippingId) {
				$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}

		}

		// No records processed. Finished.
		if(!isset($shippingId)) {
			unset($_SESSION['orderAddressConvert']);
			return true;
		}

		$_SESSION['orderAddressConvert'] += 200;
		return false;
	}

	public function buildProductTaxPricing()
	{
		if(!isset($_SESSION['taxPricingRebuild'])) {
			$start = 0;
			$query = "TRUNCATE [|PREFIX|]product_tax_pricing";
			if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
				$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}
			$_SESSION['taxPricingRebuild'] = 0;
		}
		else {
			$start = (int)$_SESSION['taxPricingRebuild'];
		}

		$priceColumns = array(
			'prodprice',
			'prodsaleprice',
			'prodcostprice',
			'prodretailprice'
		);

		$query = "
			SELECT ".implode(',', $priceColumns).", tax_class_id
			FROM [|PREFIX|]products
			ORDER BY productid ASC
			LIMIT ".$start.", 200
		";

		$updatedRows = 0;
		$result = $this->db->query($query);
		while($price = $this->db->fetch($result)) {
			foreach($priceColumns as $column) {
				if($price[$column] == 0) {
					continue;
				}

				getClass('ISC_TAX')->updateProductTaxPricing(
					$price[$column],
					$price['tax_class_id']
				);
			}
			$updatedRows += 1;
		}

		// No rows processed. We're done
		if(!$updatedRows) {
			return true;
		}

		// Still processing
		$_SESSION['taxPricingRebuild'] += $updatedRows;
		return false;
	}

	public function dropOldOrderColumns()
	{
		$columns = array(
			'ordsubtotal',
			'ordtaxtotal',
			'ordtotalincludestax',
			'ordshipcost',
			'ordhandlingcost',
			'ordtotalamount',
			'ordtaxrate',
			'ordtaxname',
			'ordshipmethod',
			'ordshipmodule',
			'ordershipmodule',
			'ordshipfirstname',
			'ordshiplastname',
			'ordshipcompany',
			'ordshipstreet1',
			'ordshipstreet2',
			'ordshipsuburb',
			'ordshipstate',
			'ordshipzip',
			'ordshipcountry',
			'ordshipcountrycode',
			'ordshipcountryid',
			'ordshipstateid',
			'ordshipphone',
			'ordshipemail',
			'ordgatewayamount',
			'ordshippingzoneid',
			'ordshippingzone'
		);
		foreach($columns as $column) {
			// Column has already been deleted
			if(!$this->columnExists('[|PREFIX|]orders', $column)) {
				continue;
			}

			$query = "
				ALTER TABLE [|PREFIX|]orders
				DROP ".$column;
			if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
				$this->setError($column.' drop: '.$GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}

			// Break after doing one - we only want to drop one column per page load
			return false;
		}

		// All finished, progress to the next step
		return true;
	}

	public function dropOldOrderProductColumns()
	{
		$columns = array(
			'ordprodcost',
			'ordprodoriginalcost',
			'ordprodcostprice',
			'ordprodwrapcost',
			'ordprodistaxable',
		);
		foreach($columns as $column) {
			// Column has already been deleted
			if(!$this->columnExists('[|PREFIX|]order_products', $column)) {
				continue;
			}

			$query = "
				ALTER TABLE [|PREFIX|]order_products
				DROP ".$column;
			if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
				$this->setError($column.' drop: '.$GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}

			// Break after doing one - we only want to drop one column per page load
			return false;
		}

		// All finished, progress to the next step
		return true;
	}

	public function truncateSessionsTable()
	{
		$query = "TRUNCATE [|PREFIX|]sessions";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		if(!$result) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function convertGlobalTaxSettings()
	{
		// Already done this step
		if($this->getPreUpgradeConfig('TaxTypeSelected') === null) {
			return true;
		}

		if($this->getPreUpgradeConfig('TaxTypeSelected') != 2) {
			return true;
		}

		// Truncate initially to allow for reupgrading
		$this->db->Query('TRUNCATE [|PREFIX|]tax_rates');
		$this->db->Query('TRUNCATE [|PREFIX|]tax_rate_class_rates');

		$taxZoneId = 1;

		$newTaxRate = array(
			'tax_zone_id' => $taxZoneId,
			'name' => $this->getPreUpgradeConfig('DefaultTaxRateName'),
			'default_rate' => $this->getPreUpgradeConfig('DefaultTaxRate'),
		);
		$taxRateId = $GLOBALS['ISC_CLASS_DB']->insertQuery('tax_rates', $newTaxRate);
		if(!$taxRateId) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		// Insert the tax class rates
		$shippingRate = 0;
		if($this->getPreUpgradeConfig('DefaultTaxRateBasedOn') == 'subtotal_and_shipping') {
			$shippingRate = $this->getPreUpgradeConfig('DefaultTaxRate');
		}

		// Non-taxable
		$newTaxClassRate = array(
			'tax_rate_id' => $taxRateId,
			'tax_class_id' => 1,
			'rate' => 0
		);
		if(!$GLOBALS['ISC_CLASS_DB']->insertQuery('tax_rate_class_rates', $newTaxClassRate)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		// Shipping
		$newTaxClassRate = array(
			'tax_rate_id' => $taxRateId,
			'tax_class_id' => 2,
			'rate' => $shippingRate
		);
		if(!$GLOBALS['ISC_CLASS_DB']->insertQuery('tax_rate_class_rates', $newTaxClassRate)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		// Wrapping
		$newTaxClassRate = array(
			'tax_rate_id' => $taxRateId,
			'tax_class_id' => 3,
			'rate' => $this->getPreUpgradeConfig('DefaultTaxRate'),
		);
		if(!$GLOBALS['ISC_CLASS_DB']->insertQuery('tax_rate_class_rates', $newTaxClassRate)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function convertMiscTaxSettings()
	{
		if($this->getPreUpgradeConfig('PricesIncludeTax') === null) {
			return true;
		}

		if($this->getPreUpgradeConfig('PricesIncludeTax')) {
			$GLOBALS['ISC_NEW_CFG']['taxEnteredWithPrices'] = TAX_PRICES_ENTERED_INCLUSIVE;
			$GLOBALS['ISC_NEW_CFG']['taxDefaultTaxDisplayCatalog'] = TAX_PRICES_DISPLAY_INCLUSIVE;
			$GLOBALS['ISC_NEW_CFG']['taxDefaultTaxDisplayProducts'] = TAX_PRICES_DISPLAY_INCLUSIVE;
			$GLOBALS['ISC_NEW_CFG']['taxDefaultTaxDisplayCart'] = TAX_PRICES_DISPLAY_INCLUSIVE;
			$GLOBALS['ISC_NEW_CFG']['taxDefaultTaxDisplayOrders'] = TAX_PRICES_DISPLAY_INCLUSIVE;
		}
		else {
			$GLOBALS['ISC_NEW_CFG']['taxEnteredWithPrices'] = TAX_PRICES_ENTERED_EXCLUSIVE;
			$GLOBALS['ISC_NEW_CFG']['taxDefaultTaxDisplayCatalog'] = TAX_PRICES_DISPLAY_EXCLUSIVE;
			$GLOBALS['ISC_NEW_CFG']['taxDefaultTaxDisplayProducts'] = TAX_PRICES_DISPLAY_EXCLUSIVE;
			$GLOBALS['ISC_NEW_CFG']['taxDefaultTaxDisplayCart'] = TAX_PRICES_DISPLAY_EXCLUSIVE;
			$GLOBALS['ISC_NEW_CFG']['taxDefaultTaxDisplayOrders'] = TAX_PRICES_DISPLAY_EXCLUSIVE;
		}

		$GLOBALS['ISC_NEW_CFG']['taxDefaultCountry'] = getCountryByName(getConfig('CompanyCountry'));
		$GLOBALS['ISC_NEW_CFG']['taxDefaultState'] = getStateByName(getConfig('CompanyState'), $GLOBALS['ISC_NEW_CFG']['taxDefaultCountry']);
		$GLOBALS['ISC_NEW_CFG']['taxDefaultZipCode'] = getConfig('CompanyZip');

		$messages = array();
		if(!getClass('ISC_ADMIN_SETTINGS')->commitSettings($messages)) {
			foreach($messages as $message) {
				$this->setError($message);
			}
			return false;
		}

		return true;
	}

	public function recalculateProdCalculatedPrice()
	{
		$limit = 200;
		if(!isset($_SESSION['productPriceUpdate'])) {
			$_SESSION['productPriceUpdate'] = 0;
		}

		$query = "
			SELECT productid, prodsaleprice, prodprice
			FROM [|PREFIX|]products
			ORDER BY productid ASC
			LIMIT ".(int)$_SESSION['productPriceUpdate'].", ".$limit;
		$result = $this->db->query($query);
		while($product = $this->db->fetch($result)) {
			$updatedProduct = array(
				'prodcalculatedprice' => calcRealPrice($product['prodprice'], $product['prodsaleprice'])
			);
			if(!$this->db->updateQuery('products', $updatedProduct, "productid='".$product['productid']."'")) {
				$this->setError($this->db->getErrorMsg());
				return false;
			}
		}

		// Completed one or more
		if(isset($updatedProduct)) {
			$_SESSION['productPriceUpdate'] += $limit;
			return false;
		}

		// No rows - finished, progress to the next step
		return true;
	}

	public function removeOldTaxRatesTable()
	{
		if(!$this->tableExists('tax_rates_new')) {
			return true;
		}

		if($this->tableExists('tax_rates') && $this->columnExists('[|PREFIX|]tax_rates', 'taxrateid')) {
			$query = "DROP TABLE [|PREFIX|]tax_rates";
			if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
				$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}
		}

		$query = "RENAME TABLE [|PREFIX|]tax_rates_new TO [|PREFIX|]tax_rates";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function removeUnusedFormFields()
	{
		if(!$this->db->deleteQuery('formfields', "WHERE formfieldprivateid IN ('SaveThisAddress','ShipToAddress')")) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function enableNewLogTypes()
	{
		$newLogTypes = array(
			'emailintegration',
			'ebay',
		);

		$systemLogTypes = explode(',', GetConfig('SystemLogTypes'));
		$modified = false;

		foreach ($newLogTypes as $newLogType) {
			if (in_array($newLogType, $systemLogTypes)) {
				// already enabled
				continue;
			}

			$modified = true;
			$systemLogTypes[] = $newLogType;
		}

		if (!$modified) {
			return true;
		}

		$GLOBALS['ISC_NEW_CFG']['SystemLogTypes'] = implode(',', $systemLogTypes);

		$messages = array();
		if (!GetClass('ISC_ADMIN_SETTINGS')->CommitSettings($messages)) {
			foreach($messages as $message) {
				$this->setError($message);
			}
			return false;
		}
		return true;
	}

	public function addShipmentsShippingModule()
	{
		if ($this->ColumnExists('[|PREFIX|]shipments', 'shipping_module')) {
			return true;
		}

		$query = "
			ALTER TABLE [|PREFIX|]shipments
			ADD COLUMN shipping_module varchar(100) NOT NULL default ''
				AFTER shiptrackno
		";

		if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function updateShipmentShippingModule()
	{
		if(!isset($_SESSION['shipmentShippingModule'])) {
			$start = 0;
			$_SESSION['shipmentShippingModule'] = 0;
		}
		else {
			$start = (int)$_SESSION['shipmentShippingModule'];
		}

		$query = "
			SELECT module, method, order_id
			FROM [|PREFIX|]order_shipping
			ORDER BY order_id ASC
			LIMIT ".$start.", 200
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		while($shipping = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			$updatedShipment = array(
				'shipping_module' => $shipping['module']
			);
			if(!$this->db->updateQuery('shipments', $updatedShipment,
				"shiporderid=".$shipping['order_id']." AND shipmethod='".$this->db->quote($shipping['method'])."'"
			)) {
				$this->setError($this->db->getErrorMsg());
				return false;
			}

			$hasRecords = true;
		}

		// Haven't processed any records. Complete.
		if(!isset($hasRecords)) {
			return true;
		}

		// Still processing.
		$_SESSION['shipmentShippingModule'] += 200;
		return false;
	}

	public function updateExportTemplates()
	{
		$updateFields = array(
			'orderSubtotal' 	=> 'orderSubtotalInc',
			'orderShipCost'		=> 'orderShipCostInc',
			'orderHandlingCost'	=> 'orderHandlingCostInc',
			'orderTotalAmount'	=> 'orderTotalAmountInc',
		);

		foreach ($updateFields as $oldField => $newField) {
			$update = array(
				'fieldid' => $newField
			);

			if (!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('export_template_fields', $update, "fieldid = '" . $oldField . "'")) {
				$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function fixDefaultTemplateCustomerFields()
	{
		$updateFields = array(
			'orderCustomerName' 	=> 'Customer Name',
			'orderCustomerEmail'	=> 'Customer Email',
			'orderCustomerPhone'	=> 'Customer Phone',
		);

		foreach ($updateFields as $fieldId => $fieldName) {
			$update = array(
				'fieldname' => $fieldName
			);

			if (!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('export_template_fields', $update, "fieldid = '" . $fieldId . "' AND exporttemplateid = 1")) {
				$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function addEbayCustomSearch()
	{
		/** @var mysqldb */
		$db = $GLOBALS['ISC_CLASS_DB'];

		$query = "SELECT COUNT(*) AS `count` FROM `[|PREFIX|]custom_searches` WHERE `searchtype` = 'orders' AND `searchname` = 'Orders from eBay'";
		$result = $db->FetchRow($query);
		if (!$result) {
			$this->SetError($db->GetErrorMsg());
			return false;
		}

		if ((int)$result['count'] > 0) {
			return true;
		}

		$query = "INSERT INTO `[|PREFIX|]custom_searches` (`searchtype`, `searchname`, `searchvars`) VALUES ('orders', 'Orders from eBay', 'viewName=Orders from eBay&ebayOrderId=1')";
		if (!$db->Query($query)) {
			$this->SetError($db->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_user_password_histories_table()
	{
		// drop userimportpass col from users table
		if ($this->ColumnExists('[|PREFIX|]users', 'userimportpass') == true) {
			$query = "ALTER TABLE [|PREFIX|]users DROP COLUMN userimportpass";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		// add salt col to users table
		if ($this->ColumnExists('[|PREFIX|]users', 'salt') == false) {
			$query = "ALTER TABLE [|PREFIX|]users ADD COLUMN salt varchar(16) NOT NULL default ''";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		// add updated col to users table
		if ($this->ColumnExists('[|PREFIX|]users', 'updated') == false) {
			$query = "ALTER TABLE [|PREFIX|]users ADD COLUMN updated int(11) NOT NULL default '0'";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		// add user_histories table
		if ($this->TableExists('user_password_histories') == false) {
			$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]user_password_histories` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `user_id` int(11) NOT NULL,
			  `salt` varchar(16) NOT NULL default '',
			  `password` varchar(50) NOT NULL default '',
			  `updated` int(11) NOT NULL default '0',
			  PRIMARY KEY (`id`),
			  INDEX (`user_id`, `updated`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_user_password_reset_tokens_table()
	{
		if ($this->TableExists('user_password_reset_tokens') == false) {
			$query = "CREATE TABLE IF NOT EXISTS `[|PREFIX|]user_password_reset_tokens` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `user_id` int(11) NOT NULL,
			  `token` varchar(32) NOT NULL default '',
			  `expiry` int(11) NOT NULL default '0',
			  PRIMARY KEY (`id`),
			  UNIQUE KEY  (`token`),
			  INDEX (`user_id`, `expiry`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_attempt_counter_to_users_table()
	{
		// add attempt_counter col to users table
		if ($this->ColumnExists('[|PREFIX|]users', 'attempt_counter') == false) {
			$query = "ALTER TABLE [|PREFIX|]users ADD COLUMN attempt_counter smallint(2) NOT NULL default '0'";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_attempt_lockout_to_users_table()
	{
		// add attempt_lockout timestamp col to users table
		if ($this->ColumnExists('[|PREFIX|]users', 'attempt_lockout') == false) {
			$query = "ALTER TABLE [|PREFIX|]users ADD COLUMN attempt_lockout int(11) NOT NULL default '0'";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_last_login_to_users_table()
	{
		// add last_login timestamp col to users table
		if ($this->ColumnExists('[|PREFIX|]users', 'last_login') == false) {
			$query = "ALTER TABLE [|PREFIX|]users ADD COLUMN last_login int(11) NOT NULL default '0'";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_product_opengraph_type()
	{
		if ($this->ColumnExists('[|PREFIX|]products', 'opengraph_type')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]products ADD COLUMN opengraph_type VARCHAR(15) NOT NULL DEFAULT 'product'";

		if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_product_opengraph_title()
	{
		if ($this->ColumnExists('[|PREFIX|]products', 'opengraph_title')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]products ADD COLUMN opengraph_title VARCHAR(250) NOT NULL DEFAULT ''";

		if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_product_opengraph_use_product_name()
	{
		if ($this->ColumnExists('[|PREFIX|]products', 'opengraph_use_product_name')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]products ADD COLUMN opengraph_use_product_name TINYINT(1) UNSIGNED NOT NULL DEFAULT '1'";

		if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_product_opengraph_description()
	{
		if ($this->ColumnExists('[|PREFIX|]products', 'opengraph_description')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]products ADD COLUMN opengraph_description TEXT";

		if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_product_opengraph_use_meta_description()
	{
		if ($this->ColumnExists('[|PREFIX|]products', 'opengraph_use_meta_description')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]products ADD COLUMN opengraph_use_meta_description TINYINT(1) UNSIGNED NOT NULL DEFAULT '1'";

		if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_product_opengraph_use_image()
	{
		if ($this->ColumnExists('[|PREFIX|]products', 'opengraph_use_image')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]products ADD COLUMN opengraph_use_image TINYINT(1) UNSIGNED NOT NULL DEFAULT '1'";

		if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function addOrderProductAppliedDiscountsColumn()
	{
		if ($this->ColumnExists('[|PREFIX|]order_products', 'applied_discounts')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]order_products ADD COLUMN applied_discounts text";

		if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function convertEmailMarketerConfigToModuleVars ()
	{
		if (!$this->getPreUpgradeConfig('MailXMLAPIValid')) {
			// IEM was not configured previously, there is nothing for this step to do
			return true;
		}

		$moduleId = 'emailintegration_emailmarketer';
		GetModuleById('emailintegration', /** @var ISC_EMAILINTEGRATION */$module, $moduleId);
		if (!$module) {
			$this->SetError('Could not load Email Marketer module');
			return false;
		}

		if ($module->isConfigured()) {
			// the IEM module is already configured which means this upgrade step has probably already run - skip and do not replace the already configured module/rules
			return true;
		}

		// assume the details are valid and copy them to new module vars

		$result = $module->SaveModuleSettings(array(
			'isconfigured' => 1,
			'url' => $this->getPreUpgradeConfig('MailXMLPath'),
			'username' => $this->getPreUpgradeConfig('MailUsername'),
			'usertoken' => $this->getPreUpgradeConfig('MailXMLToken'),
		));

		if (!$result) {
			$this->SetError('SaveModuleSettings failed: ' . $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		if (!$module->IsEnabled()) {
			$methods = trim(GetConfig('EmailIntegrationMethods'));
			if ($methods) {
				$methods = explode(',', $methods);
			} else {
				$methods = array();
			}
			$methods[] = $moduleId;
			$GLOBALS['ISC_NEW_CFG']['EmailIntegrationMethods'] = implode(',', $methods);

			$messages = array();
			if (!GetClass('ISC_ADMIN_SETTINGS')->CommitSettings($messages)) {
				foreach ($messages as $message) {
					$this->setError($message);
				}
				return false;
			}
		}

		$result = $GLOBALS['ISC_CLASS_DATA_STORE']->UpdateEmailIntegrationModuleVars();
		if ($result === false) {
			$this->SetError('UpdateEmailIntegrationModuleVars failed');
			return false;
		}

		if ($this->getPreUpgradeConfig('UseMailerForNewsletter')) {
			$fieldMap = array();
			if ($this->getPreUpgradeConfig('MailNewsletterCustomField')) {
				$fieldMap[$this->getPreUpgradeConfig('MailNewsletterCustomField')] = 'subfirstname';
			}
			$rule = new Interspire_EmailIntegration_Rule_NewsletterSubscribed(null, $moduleId, Interspire_EmailIntegration_Rule::ACTION_ADD, $this->getPreUpgradeConfig('MailNewsletterList'), $fieldMap);
			if (!$rule->save()) {
				$this->SetError('Failed to save email integration rule for newsletter subscriptions');
				return false;
			}
		}

		if ($this->getPreUpgradeConfig('UseMailerForOrders')) {
			$fieldMap = array();

			$configMap = array(
				'MailOrderFirstName' => 'ordbillfirstname',
				'MailOrderLastName' => 'ordbilllastname',
				'MailOrderZip' => 'ordbillzip',
				'MailOrderCountry' => 'OrderSubscription_BillingAddress_countryiso3',
				'MailOrderTotal' => 'total_inc_tax',
				'MailOrderPaymentMethod' => 'orderpaymentmethod',
				'MailOrderShippingMethod' => 'shipping_method',
			);

			foreach ($configMap as $oldConfig => $newField) {
				if ($this->getPreUpgradeConfig($oldConfig)) {
					$fieldMap[$this->getPreUpgradeConfig($oldConfig)] = $newField;
				}
			}

			$rule = new Interspire_EmailIntegration_Rule_OrderCompleted(null, $moduleId, Interspire_EmailIntegration_Rule::ACTION_ADD, $this->getPreUpgradeConfig('MailOrderList'), $fieldMap);
			if (!$rule->save()) {
				$this->SetError('Failed to save email integration rule for order subscriptions');
				return false;
			}
		}

		return true;
	}
}
