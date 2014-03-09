<?php

/**
 * Upgrade class for 6.1.0
 * This class runs a series of methods used to upgrade the store to a specific version
 *
 * @package ISC
 * @subpackage ISC_Upgrade
 */
class ISC_ADMIN_UPGRADE_6100 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		'addNewPermissions',
		'addTrackInventoryToBulkEditTemplate',
		'addOrdersDeletedColumn',
		'addOrderStatusDeletedIndex',
		'deleteOrderIdStatusIndex',
		'addCouponsLocationRestrictedColumn',
		'addCouponsShippingMethodRestrictedColumn',
		'createTableCouponsLocations',
		'createTableCouponsShippingMethods',
		'addCouponMaxUsesPerCusColumn',
		'createTableCouponUsages',
		'addFreeShippingMessageColumnToDiscountsTable',
		'addFreeShippingMessageLocationColumnToDiscountsTable',
		'add_last_import_column_to_products_table',
		'add_products_last_import_index',
		'add_search_label_column_to_custom_searches_table',
		'add_last_import_custom_search',
		'add_custom_searches_searchlabel_index',
		'update_custom_search_labels',
		'addDeletedOrdersCustomSearch',
		'disableFacebookLike',
		'setStaticCategoryFlyoutStyle',
		'addOrderCouponOrderIdIndex',
		'addOrderDownloadsOrderIdIndex',
		'addTaxZoneLocationsZoneIdIndex',
		'addOrderTaxesOrderIdIndex',
		'addOrderTaxesOrderAddressIdIndex',
		'addOrderAddressesOrderIdIndex',
		'addOrderAddressesFormSessionIdIndex',
		'addOrderShippingOrderAddressIdIndex',

		'deleteIncompleteVariationImports',

		// these are implemented as unique steps to provide some progress feedback on the upgrade screen
		'deleteUnusedProductImagesA',
		'deleteUnusedProductImagesB',
		'deleteUnusedProductImagesC',
		'deleteUnusedProductImagesD',
		'deleteUnusedProductImagesE',
		'deleteUnusedProductImagesF',
		'deleteUnusedProductImagesG',
		'deleteUnusedProductImagesH',
		'deleteUnusedProductImagesI',
		'deleteUnusedProductImagesJ',
		'deleteUnusedProductImagesK',
		'deleteUnusedProductImagesL',
		'deleteUnusedProductImagesM',
		'deleteUnusedProductImagesN',
		'deleteUnusedProductImagesO',
		'deleteUnusedProductImagesP',
		'deleteUnusedProductImagesQ',
		'deleteUnusedProductImagesR',
		'deleteUnusedProductImagesS',
		'deleteUnusedProductImagesT',
		'deleteUnusedProductImagesU',
		'deleteUnusedProductImagesV',
		'deleteUnusedProductImagesW',
		'deleteUnusedProductImagesX',
		'deleteUnusedProductImagesY',
		'deleteUnusedProductImagesZ',

		'changeProdsearchkeywordsToText',
		'addGiftCertificateConfigSettings',
	);

	public function deleteOrderIdStatusIndex ()
	{
		if (!$this->IndexExists('[|PREFIX|]orders', 'i_orders_orderid_ordstatus')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]orders DROP INDEX i_orders_orderid_ordstatus";
		if (!$this->db->Query($query)) {
			$this->SetError($this->db->GetErrorMsg());
			return false;
		}
		return true;
	}

	public function addOrderStatusDeletedIndex ()
	{
		if ($this->IndexExists('[|PREFIX|]orders', 'ordstatus_deleted')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]orders ADD INDEX ordstatus_deleted (ordstatus, deleted)";
		if (!$this->db->Query($query)) {
			$this->SetError($this->db->GetErrorMsg());
			return false;
		}
		return true;
	}

	public function addNewPermissions ()
	{
		$newPermissions = array(
			AUTH_Undelete_Orders,
			AUTH_Purge_Orders,
		);

		foreach ($newPermissions as $permission) {
			$query = "
				INSERT INTO [|PREFIX|]permissions (permuserid, permpermissionid)
				SELECT pk_userid, " . $permission . "
				FROM [|PREFIX|]users u
				WHERE userrole = 'admin'
				AND pk_userid NOT IN (SELECT permuserid FROM [|PREFIX|]permissions WHERE pk_userid = u.pk_userid AND permpermissionid = " . $permission . ")
			";

			if (!$this->db->Query($query)) {
				$this->SetError($this->db->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function addDeletedOrdersCustomSearch ()
	{
		$query = "SELECT COUNT(*) AS `count` FROM `[|PREFIX|]custom_searches` WHERE `searchlabel` = 'deletedorders'";
		$result = $this->db->FetchRow($query);
		if (!$result) {
			$this->SetError($this->db->GetErrorMsg());
			return false;
		}

		if ((int)$result['count'] > 0) {
			return true;
		}

		$query = "INSERT INTO `[|PREFIX|]custom_searches` (`searchtype`, `searchname`, `searchvars`,`searchlabel`) VALUES ('orders', 'Deleted Orders', 'viewName=Deleted+Orders&searchDeletedOrders=only', 'deletedorders')";
		if (!$this->db->Query($query)) {
			$this->SetError($this->db->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function addOrdersDeletedColumn ()
	{
		if ($this->ColumnExists('[|PREFIX|]orders', 'deleted')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]orders ADD COLUMN deleted TINYINT(1) UNSIGNED NOT NULL DEFAULT 0";
		if (!$this->db->Query($query)) {
			$this->SetError($this->db->GetErrorMsg());
			return false;
		}
		return true;
	}

	/**
	 * Remove failed or otherwise incomplete variation imports so that the following image checks don't decide to keep
	 * an image which is in the db but actually unused.
	 *
	 * @return bool
	 */
	public function deleteIncompleteVariationImports ()
	{
		// no real way of detecting if this has already run so just try it...

		$query = "
			DELETE FROM
				`[|PREFIX|]product_variation_combinations`
			WHERE
				vcproductid = 0
		";

		if ($this->db->Query($query)) {
			return true;
		}

		$this->SetError($this->db->GetErrorMsg());
		return false;
	}

	protected function _deleteUnusedProductImages ($letter)
	{
		// check to see if this upgrade step has already run once
		// storage of this may be volatile but it's a compromise between using upgrade session (very volatile) and some new specific mysql table (permanent)
		$key = 'upgrade:6100:deleteUnusedProductImages:' . $letter;
		$keys = new Interspire_KeyStore;
		if ($keys->exists($key)) {
			return true;
		}

		// scan our product_images directory to begin detecting orphaned images
		$base = ISC_BASE_PATH . '/' . GetConfig('ImageDirectory') . '/' . $letter;

		$imageDirectoryLength = strlen(ISC_BASE_PATH . '/' . GetConfig('ImageDirectory') . '/');

		$deleteCounter = 0;
		$directory = new RecursiveDirectoryIterator($base);
		foreach (new RecursiveIteratorIterator($directory) as $absoluteFilename => $current) {
			if ($current->isDir()) {
				// skip directories
				continue;
			}

			if (DIRECTORY_SEPARATOR == "\\") {
				// normalise the filename because DirectoryIterator will return \ on Windows but we store / in db
				$absoluteFilename = str_replace("\\", "/", $absoluteFilename);
			}

			$filename = substr($absoluteFilename, $imageDirectoryLength);

			// check that the filename matches our pattern of generated names such as __{random}_{size}.ext before
			// deleting it, if it doesn't match then it's a file that probably wasn't generated by the product.image
			// classes and we should leave it alone
			if (!preg_match('#__[0-9]{5}(_(std|thumb|tiny|zoom))?\.[^.]+$#', $filename)) {
				continue;
			}

			try {
				if ($this->_isImageInUse($filename)) {
					// filename exists in db somewhere; skip
					continue;
				}
			} catch (Exception $exception) {
				$this->SetError($exception->getMessage());
				return false;
			}

			// remove this file
			if (!unlink($absoluteFilename)) {
				$this->SetError('unlink() failed for file: ' . $absoluteFilename);
				return false;
			}

			$deleteCounter++;
		}

		// the key need only exist to flag the step as run, we can store whatever we want, so let's store when the step
		// ran and the number of files which were deleted
		$keys->set($key, time() . '.' . $deleteCounter);
		return true;
	}

	/**
	 * Determines if the specified image (relative to product_images) is currently in use in the database or not
	 *
	 * @return bool true if the specified image is in use, otherwise false
	 * @throws Exception throws an exception with database error message if a database error occurrs
	 */
	protected function _isImageInUse ($image)
	{
		$dbQuotedImage = $this->db->Quote($image);

		$queries = array();

		// variation images
		$queries[] = "
			SELECT
				combinationid
			FROM
				[|PREFIX|]product_variation_combinations
			WHERE
				vcimage = '" . $dbQuotedImage . "'
				OR vcimagethumb = '" . $dbQuotedImage . "'
				OR vcimagestd = '" . $dbQuotedImage . "'
				OR vcimagezoom = '" . $dbQuotedImage . "'
			LIMIT
				1
		";

		// product images
		$queries[] = "
			SELECT
				imageid
			FROM
				[|PREFIX|]product_images
			WHERE
				imagefile = '" . $dbQuotedImage . "'
				OR imagefiletiny = '" . $dbQuotedImage . "'
				OR imagefilethumb = '" . $dbQuotedImage . "'
				OR imagefilestd = '" . $dbQuotedImage . "'
				OR imagefilezoom = '" . $dbQuotedImage . "'
			LIMIT
				1
		";

		// category images
		$queries[] = "
			SELECT
				categoryid
			FROM
				[|PREFIX|]categories
			WHERE
				catimagefile = '" . $dbQuotedImage . "'
			LIMIT
				1
		";

		// brand images
		$queries[] = "
			SELECT
				brandid
			FROM
				[|PREFIX|]brands
			WHERE
				brandimagefile = '" . $dbQuotedImage . "'
			LIMIT
				1
		";

		foreach ($queries as $query) {
			$result = $this->db->Query($query);
			if (!$result) {
				// throw exception instead of returning false, since true/false is needed as a real result
				throw new Exception($this->db->GetErrorMsg());
			}

			$row = $this->db->Fetch($result);
			if ($row !== false) {
				return true;
			}
		}

		return false;
	}

	public function deleteUnusedProductImagesA ()
	{
		return $this->_deleteUnusedProductImages('a');
	}

	public function deleteUnusedProductImagesB ()
	{
		return $this->_deleteUnusedProductImages('b');
	}

	public function deleteUnusedProductImagesC ()
	{
		return $this->_deleteUnusedProductImages('c');
	}

	public function deleteUnusedProductImagesD ()
	{
		return $this->_deleteUnusedProductImages('d');
	}

	public function deleteUnusedProductImagesE ()
	{
		return $this->_deleteUnusedProductImages('e');
	}

	public function deleteUnusedProductImagesF ()
	{
		return $this->_deleteUnusedProductImages('f');
	}

	public function deleteUnusedProductImagesG ()
	{
		return $this->_deleteUnusedProductImages('g');
	}

	public function deleteUnusedProductImagesH ()
	{
		return $this->_deleteUnusedProductImages('h');
	}

	public function deleteUnusedProductImagesI ()
	{
		return $this->_deleteUnusedProductImages('i');
	}

	public function deleteUnusedProductImagesJ ()
	{
		return $this->_deleteUnusedProductImages('j');
	}

	public function deleteUnusedProductImagesK ()
	{
		return $this->_deleteUnusedProductImages('k');
	}

	public function deleteUnusedProductImagesL ()
	{
		return $this->_deleteUnusedProductImages('l');
	}

	public function deleteUnusedProductImagesM ()
	{
		return $this->_deleteUnusedProductImages('m');
	}

	public function deleteUnusedProductImagesN ()
	{
		return $this->_deleteUnusedProductImages('n');
	}

	public function deleteUnusedProductImagesO ()
	{
		return $this->_deleteUnusedProductImages('o');
	}

	public function deleteUnusedProductImagesP ()
	{
		return $this->_deleteUnusedProductImages('p');
	}

	public function deleteUnusedProductImagesQ ()
	{
		return $this->_deleteUnusedProductImages('q');
	}

	public function deleteUnusedProductImagesR ()
	{
		return $this->_deleteUnusedProductImages('r');
	}

	public function deleteUnusedProductImagesS ()
	{
		return $this->_deleteUnusedProductImages('s');
	}

	public function deleteUnusedProductImagesT ()
	{
		return $this->_deleteUnusedProductImages('t');
	}

	public function deleteUnusedProductImagesU ()
	{
		return $this->_deleteUnusedProductImages('u');
	}

	public function deleteUnusedProductImagesV ()
	{
		return $this->_deleteUnusedProductImages('v');
	}

	public function deleteUnusedProductImagesW ()
	{
		return $this->_deleteUnusedProductImages('w');
	}

	public function deleteUnusedProductImagesX ()
	{
		return $this->_deleteUnusedProductImages('x');
	}

	public function deleteUnusedProductImagesY ()
	{
		return $this->_deleteUnusedProductImages('y');
	}

	public function deleteUnusedProductImagesZ ()
	{
		return $this->_deleteUnusedProductImages('z');
	}

	public function addTrackInventoryToBulkEditTemplate()
	{
		$db = $GLOBALS['ISC_CLASS_DB'];

		// the field to create
		$field = array(
			'fieldid' => 'productTrackInventory',
			'fieldtype' => 'products',
			'fieldname'	=> 'Track Inventory',
			'includeinexport' => 1,
			'sortorder' => 24,
		);

		// get the template ID for the bulk edit template
		$query = "SELECT exporttemplateid FROM [|PREFIX|]export_templates WHERE exporttemplatename = 'Bulk Edit' AND builtin = 1";
		$res = $db->Query($query);
		$templateRow = $db->Fetch($res);
		if (!$templateRow) {
			return true;
		}

		$field['exporttemplateid'] = $templateRow['exporttemplateid'];

		// Get the bulk edit template id
		$query = "
			SELECT
				exporttemplatefieldid
			FROM
				[|PREFIX|]export_template_fields
			WHERE
				exporttemplateid = " . $field['exporttemplateid'] . " AND
				fieldid = '". $field['fieldid'] ."'
		";
		$result = $db->Query($query);

		// If the Track Inventory field doesn't exist add it
		if (!$fieldRow = $db->Fetch($result)) {
			// bump sort orders to make room for the field
			$query = "
				UPDATE
					[|PREFIX|]export_template_fields
				SET
					sortorder = sortorder + 1
				WHERE
					exporttemplateid = ".$templateRow['exporttemplateid']."
					AND fieldtype = '".$field['fieldtype']."'
					AND sortorder >= ".$field['sortorder']."
			";

			if(!$db->Query($query)) {
				$this->SetError($db->GetErrorMsg());
				return false;
			}

			// create the field
			if(!$db->InsertQuery('export_template_fields', $field)) {
				$this->SetError($db->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function addCouponsLocationRestrictedColumn()
	{
		// add attempt_lockout timestamp col to users table
		if ($this->ColumnExists('[|PREFIX|]coupons', 'location_restricted') == false) {
			$query = "ALTER TABLE `[|PREFIX|]coupons` ADD COLUMN `location_restricted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function addCouponsShippingMethodRestrictedColumn()
	{
		// add attempt_lockout timestamp col to users table
		if ($this->ColumnExists('[|PREFIX|]coupons', 'shipping_method_restricted') == false) {
			$query = "ALTER TABLE `[|PREFIX|]coupons` ADD COLUMN `shipping_method_restricted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function createTableCouponsLocations()
	{
		$query = "
		CREATE TABLE IF NOT EXISTS `[|PREFIX|]coupon_locations` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`coupon_id` INT(11) DEFAULT NULL,
			`selected_type` VARCHAR(10) DEFAULT NULL,
			`value_id` INT(10) DEFAULT NULL,
			`value` VARCHAR(100) DEFAULT NULL,
			`country_id` INT(11) DEFAULT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function createTableCouponsShippingMethods()
	{
		$query = "
		CREATE TABLE IF NOT EXISTS `[|PREFIX|]coupon_shipping_methods` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`coupon_id` INT(11) DEFAULT NULL,
			`module_id` VARCHAR(100) DEFAULT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function addCouponMaxUsesPerCusColumn()
	{
		if ($this->ColumnExists('[|PREFIX|]coupons', 'couponmaxusespercus') == false) {
			$query = "ALTER TABLE `[|PREFIX|]coupons` ADD COLUMN `couponmaxusespercus` int(11) NOT NULL DEFAULT '0'";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function createTableCouponUsages()
	{
		$query = "
		CREATE TABLE IF NOT EXISTS `[|PREFIX|]coupon_usages` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`coupon_id` INT(11) NOT NULL,
			`customer` varchar(250) NOT NULL,
			`numuses` int(11) NOT NULL default '0',
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		if(!$GLOBALS['ISC_CLASS_DB']->query($query)) {
			$this->setError($GLOBALS['ISC_CLASS_DB']->getErrorMsg());
			return false;
		}

		return true;
	}

	public function addFreeShippingMessageColumnToDiscountsTable()
	{
		if ($this->ColumnExists('[|PREFIX|]discounts', 'free_shipping_message') == false) {
			$query = "ALTER TABLE `[|PREFIX|]discounts` ADD COLUMN `free_shipping_message` TEXT NOT NULL";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function addFreeShippingMessageLocationColumnToDiscountsTable()
	{
		if ($this->ColumnExists('[|PREFIX|]discounts', 'free_shipping_message_location') == false) {
			$query = "ALTER TABLE `[|PREFIX|]discounts` ADD COLUMN `free_shipping_message_location` TEXT NOT NULL";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_last_import_column_to_products_table()
	{
		if ($this->ColumnExists('[|PREFIX|]products', 'last_import') == false) {
			$query = "ALTER TABLE [|PREFIX|]products ADD COLUMN last_import int(11) NOT NULL default '0'";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_products_last_import_index()
	{
		if ($this->IndexExists('[|PREFIX|]products', 'i_products_last_import')) {
			return true;
		}

		$query = "ALTER TABLE `[|PREFIX|]products` ADD INDEX `i_products_last_import` (`last_import`)";
		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_search_label_column_to_custom_searches_table()
	{
		if ($this->ColumnExists('[|PREFIX|]custom_searches', 'searchlabel') == false) {
			$query = "ALTER TABLE [|PREFIX|]custom_searches ADD COLUMN searchlabel varchar(50) NOT NULL default ''";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_last_import_custom_search()
	{
		/** @var mysqldb */
		$db = $GLOBALS['ISC_CLASS_DB'];

		$query = "SELECT COUNT(*) AS `count` FROM `[|PREFIX|]custom_searches` WHERE `searchtype` = 'products' AND `searchlabel` = 'lastimportproducts'";
		$result = $db->FetchRow($query);
		if (!$result) {
			$this->SetError($db->GetErrorMsg());
			return false;
		}

		if ((int)$result['count'] > 0) {
			return true;
		}

		$query = "INSERT INTO `[|PREFIX|]custom_searches` (`searchtype`, `searchname`, `searchvars`, `searchlabel`) VALUES ('products', 'Last Import', 'viewName=Last+Import&lastImport=1', 'lastimportproducts')";
		if (!$db->Query($query)) {
			$this->SetError($db->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function update_custom_search_labels()
	{
		$labels = array(
			"%&lastImport=1"	=> 'lastimportproducts',
			"%&orderStatus=0"	=> 'incompleteorders',
			"%&ebayOrderId=-1"	=> 'ebayorders',
			"%preorders[]=1"	=> 'preorders',
			);

		foreach($labels as $searchVar => $searchLabel) {
			if(!$this->_updateCustomSearchLabel($searchVar, $searchLabel)) {
				return false;
			}
		}

		return true;
	}

	private function _updateCustomSearchLabel($searchVars, $label)
	{
		$db = $GLOBALS['ISC_CLASS_DB'];

		$query = "UPDATE `[|PREFIX|]custom_searches` SET `searchlabel`= '".$label."' WHERE `searchvars` LIKE '".$searchVars."' LIMIT 1;";
		if (!$db->Query($query)) {
			$this->SetError($db->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_custom_searches_searchlabel_index()
	{
		if ($this->IndexExists('[|PREFIX|]custom_searches', 'i_custom_searches_label')) {
			return true;
		}

		$query = "ALTER TABLE `[|PREFIX|]custom_searches` ADD INDEX `i_custom_searches_label` (`searchlabel`)";
		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	/**
	 * Disable facebook like button if the store has no admin ID configured
	 *
	 * @todo ISC-1200 this may need moving to 6101.php depending on the status of 6.1.0
	 * @link https://track.interspire.com/browse/ISC-1200 Facebook recommend/like button throwing error
	 */
	public function disableFacebookLike ()
	{
		if (!$this->getPreUpgradeConfig('FacebookLikeButtonEnabled')) {
			// ignore/skip if button is disabled
			return true;
		}

		$adminIds = $this->getPreUpgradeConfig('FacebookLikeButtonAdminIds');
		if (!empty($adminIds)) {
			// ignore/skip if admin ids are present
			return true;
		}

		// otherwise disable
		$GLOBALS['ISC_NEW_CFG']['FacebookLikeButtonEnabled'] = 0;
		$messages = array();
		if (!GetClass('ISC_ADMIN_SETTINGS')->CommitSettings($messages)) {
			foreach ($messages as $message) {
				$this->setError($message);
			}
			return false;
		}

		return true;
	}

	public function setStaticCategoryFlyoutStyle ()
	{
		// upgraded stores have flyouts turned off by default to retain their original design

		if ($this->getPreUpgradeConfig('CategoryListStyle')) {
			// list style was already set before the upgrade started; don't set it again
			return true;
		}

		$GLOBALS['ISC_NEW_CFG']['CategoryListStyle'] = 'static';
		$messages = array();
		if (!GetClass('ISC_ADMIN_SETTINGS')->CommitSettings($messages)) {
			foreach ($messages as $message) {
				$this->setError($message);
			}
			return false;
		}

		return true;
	}

	public function addOrderCouponOrderIdIndex()
	{
		if ($this->indexExists('[|PREFIX|]order_coupons', 'ordcouporderid')) {
			return true;
		}

		$query = "
			ALTER TABLE [|PREFIX|]order_coupons
			ADD INDEX `ordcouporderid` (`ordcouporderid`)
		";
		if (!$this->db->query($query)) {
			$this->setError($this->db->getErrorMsg());
			return false;
		}
		return true;
	}

	public function addOrderDownloadsOrderIdIndex()
	{
		if ($this->indexExists('[|PREFIX|]order_downloads', 'orddownid')) {
			return true;
		}

		$query = "
			ALTER TABLE [|PREFIX|]order_downloads
			ADD INDEX `orddownid` (`orddownid`)
		";
		if (!$this->db->query($query)) {
			$this->setError($this->db->getErrorMsg());
			return false;
		}
		return true;
	}

	public function addTaxZoneLocationsZoneIdIndex()
	{
		if ($this->indexExists('[|PREFIX|]tax_zone_locations', 'tax_zone_id')) {
			return true;
		}

		$query = "
			ALTER TABLE [|PREFIX|]tax_zone_locations
			ADD INDEX `tax_zone_id` (`tax_zone_id`)
		";
		if (!$this->db->query($query)) {
			$this->setError($this->db->getErrorMsg());
			return false;
		}
		return true;
	}

	public function addOrderTaxesOrderIdIndex()
	{
		if ($this->indexExists('[|PREFIX|]order_taxes', 'order_id')) {
			return true;
		}

		$query = "
			ALTER TABLE [|PREFIX|]order_taxes
			ADD INDEX `order_id` (`order_id`)
		";
		if (!$this->db->query($query)) {
			$this->setError($this->db->getErrorMsg());
			return false;
		}
		return true;
	}

	public function addOrderTaxesOrderAddressIdIndex()
	{
		if ($this->indexExists('[|PREFIX|]order_taxes', 'order_address_id')) {
			return true;
		}

		$query = "
			ALTER TABLE [|PREFIX|]order_taxes
			ADD INDEX `order_address_id` (`order_address_id`)
		";
		if (!$this->db->query($query)) {
			$this->setError($this->db->getErrorMsg());
			return false;
		}
		return true;
	}

	public function addOrderAddressesOrderIdIndex()
	{
		if ($this->indexExists('[|PREFIX|]order_addresses', 'order_id')) {
			return true;
		}

		$query = "
			ALTER TABLE [|PREFIX|]order_addresses
			ADD INDEX `order_id` (`order_id`)
		";
		if (!$this->db->query($query)) {
			$this->setError($this->db->getErrorMsg());
			return false;
		}
		return true;
	}

	public function addOrderAddressesFormSessionIdIndex()
	{
		if ($this->indexExists('[|PREFIX|]order_addresses', 'form_session_id')) {
			return true;
		}

		$query = "
			ALTER TABLE [|PREFIX|]order_addresses
			ADD INDEX `form_session_id` (`form_session_id`)
		";
		if (!$this->db->query($query)) {
			$this->setError($this->db->getErrorMsg());
			return false;
		}
		return true;
	}

	public function addOrderShippingOrderAddressIdIndex()
	{
		if ($this->indexExists('[|PREFIX|]order_shipping', 'order_address_id')) {
			return true;
		}

		$query = "
			ALTER TABLE [|PREFIX|]order_shipping
			ADD INDEX `order_address_id` (`order_address_id`)
		";
		if (!$this->db->query($query)) {
			$this->setError($this->db->getErrorMsg());
			return false;
		}
		return true;
	}

	public function changeProdsearchkeywordsToText()
	{
		if (strtolower($this->getColumnType('products', 'prodsearchkeywords')) != 'text') {
			$query = "ALTER TABLE `[|PREFIX|]products` MODIFY COLUMN `prodsearchkeywords` TEXT";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		if (strtolower($this->getColumnType('product_search', 'prodsearchkeywords')) != 'text') {
			$query = "ALTER TABLE `[|PREFIX|]product_search` MODIFY COLUMN `prodsearchkeywords` TEXT";
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	/**
	 * Add gift certificate master and custom directory settings to the
	 * config.
	 */
	public function addGiftCertificateConfigSettings()
	{
		$GLOBALS['ISC_NEW_CFG']['GiftCertificateCustomDirectory'] = '__gift_themes';
		$GLOBALS['ISC_NEW_CFG']['GiftCertificateMasterDirectory'] = '__master/__gift_themes';

		$messages = array();
		if (!GetClass('ISC_ADMIN_SETTINGS')->CommitSettings($messages)) {
			foreach ($messages as $message) {
				$this->setError($message);
			}
			return false;
		}

		return true;
	}
}
