<?php
/**
 * Upgrade class for 5.6.0
 * This class runs a series of methods used to upgrade the store to a specific version
 *
 * @package ISC
 * @subpackage ISC_Upgrade
 */

class ISC_ADMIN_UPGRADE_5600 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		"add_new_permissions",
		"add_redirects_table",
		"add_variationid_to_wishlist",
		"add_strip_html_column",
		"add_bulk_edit_template",
		"add_redirects_to_templates",
		"update_templates_subitems",
		"delete_alt_customers_setting",
		"update_taiwan_again",
		"fix_laois_county",
		"add_montenegro",
		"update_serbia"
	);

	public function add_new_permissions()
	{
		$newPermissions = array(
			AUTH_See_Store_During_Maintenance,
			AUTH_Manage_Redirects
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

	public function add_redirects_table()
	{
		if (!$this->TableExists('redirects')) {
			$query = "
				CREATE TABLE `[|PREFIX|]redirects` (
					`redirectid` INT( 11 ) NOT NULL AUTO_INCREMENT ,
					`redirectpath` VARCHAR( 255 ) NOT NULL ,
					`redirectassocid` INT( 11 ) NOT NULL ,
					`redirectassoctype` SMALLINT( 1 ) NOT NULL ,
					`redirectmanual` VARCHAR( 255 ) NOT NULL,
					 PRIMARY KEY  (`redirectid`),
					 KEY `redirectpath` (`redirectpath`)
				) ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_variationid_to_wishlist()
	{
		// add the variation id to the wishlist table
		if (!$this->ColumnExists('[|PREFIX|]wishlist_items', 'variationid')) {
			$query = 'ALTER TABLE `[|PREFIX|]wishlist_items` ADD `variationid` INT( 11 ) NULL AFTER `productid`';
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_strip_html_column()
	{
		// if the column vcimagezoom exists, can assume that images have already been updated and nothing to do here.
		if (!$this->ColumnExists('[|PREFIX|]export_templates', 'striphtml')) {
			$query = 'ALTER TABLE `[|PREFIX|]export_templates` ADD `striphtml` TINYINT( 1 ) UNSIGNED NOT NULL AFTER `blankforfalse`';
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_bulk_edit_template()
	{
		// create bulk edit template
		$query = "SELECT * FROM [|PREFIX|]export_templates WHERE exporttemplatename = 'Bulk Edit' AND builtin = 1";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!$GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			// make template
			$query = "INSERT INTO `[|PREFIX|]export_templates` (`exporttemplatename`, `myobassetaccount`, `myobincomeaccount`, `myobexpenseaccount`, `peachtreereceivableaccount`, `peachtreeglaccount`, `modifyforpeachtree`, `dateformat`, `priceformat`, `boolformat`, `blankforfalse`, `striphtml`, `vendorid`, `usedtypes`, `builtin`) VALUES('Bulk Edit', '', '', '', '', '', 0, 'mdy-slash', 'number', 'yn', 0, 0, 0, 'customers,products', 1)";
			$GLOBALS['ISC_CLASS_DB']->Query($query);

			$tempId = $GLOBALS['ISC_CLASS_DB']->LastId();

			// add method settings
			$settings =	array(
							array('FieldSeparator',	','),
							array('FieldEnclosure', '"'),
							array('IncludeHeader', '1'),
							array('BlankLine', '0'),
							array('SubItems', 'expand'),
							array('SubItemSeparator', '|'),
							array('LineEnding', 'Windows'),
			);

			foreach ($settings as $setting) {
				$insert = array(
					'methodname'		=> 'CSV',
					'exporttemplateid'	=> $tempId,
					'variablename'		=> $setting[0],
					'variablevalue'		=> $setting[1]
				);

				$GLOBALS['ISC_CLASS_DB']->InsertQuery('export_method_settings', $insert);
			}

			// add template fields
			$fields = array(
				array('abandonorderOrderId', 'abandonorder', 'Order ID', 1, 0),
				array('abandonorderCustomerName', 'abandonorder', 'Customer Name', 1, 1),
				array('abandonorderCustomerEmail', 'abandonorder', 'Customer Email', 1, 2),
				array('abandonorderCustomerPhone', 'abandonorder', 'Customer Phone', 1, 3),
				array('abandonorderDate', 'abandonorder', 'Date', 1, 4),
				array('abandonorderTotalOrderAmount', 'abandonorder', 'Total Order Amount', 1, 5),
				array('customerID', 'customers', 'Customer ID', 0, 0),
				array('customerName', 'customers', 'Customer Name', 0, 1),
				array('customerDateJoined', 'customers', 'Date Joined', 0, 10),
				array('addressName', 'customers', 'Address Name', 0, 12),
				array('addressStateAbbrv', 'customers', 'Address State Abbreviation', 0, 21),
				array('addressBuilding', 'customers', 'Building Type', 0, 24),
				array('customerEmail', 'customers', 'Email Address', 1, 2),
				array('customerFirstName', 'customers', 'First Name', 1, 3),
				array('customerLastName', 'customers', 'Last Name', 1, 4),
				array('customerCompany', 'customers', 'Company', 1, 5),
				array('customerPhone', 'customers', 'Phone', 1, 6),
				array('customerNotes', 'customers', 'Notes', 1, 7),
				array('customerCredit', 'customers', 'Store Credit', 1, 8),
				array('customerGroup', 'customers', 'Customer Group', 1, 9),
				array('customerAddresses', 'customers', 'Addresses', 1, 11),
				array('addressID', 'customers', 'Address ID', 1, 13),
				array('addressFirstName', 'customers', 'Address First Name', 1, 14),
				array('addressLastName', 'customers', 'Address Last Name', 1, 15),
				array('addressCompany', 'customers', 'Address Company', 1, 16),
				array('addressLine1', 'customers', 'Address Line 1', 1, 17),
				array('addressLine2', 'customers', 'Address Line 2', 1, 18),
				array('addressSuburb', 'customers', 'Address City', 1, 19),
				array('addressState', 'customers', 'Address State', 1, 20),
				array('addressPostcode', 'customers', 'Address Zip', 1, 22),
				array('addressCountry', 'customers', 'Address Country', 1, 23),
				array('addressPhone', 'customers', 'Address Phone', 1, 25),
				array('addressFormFields', 'customers', 'Address Form Fields', 1, 26),
				array('customerFormFields', 'customers', 'Form Fields', 1, 27),
				array('orderID', 'orders', 'Order ID', 1, 0),
				array('orderStatus', 'orders', 'Order Status', 1, 1),
				array('orderDate', 'orders', 'Order Date', 1, 2),
				array('orderSubtotal', 'orders', 'Subtotal', 1, 3),
				array('orderTaxtotal', 'orders', 'Tax Total', 1, 4),
				array('orderTaxRate', 'orders', 'Tax Rate', 1, 5),
				array('orderTaxName', 'orders', 'Tax Name', 1, 6),
				array('orderTotalIncTax', 'orders', 'Total Includes Tax', 1, 7),
				array('orderShipCost', 'orders', 'Shipping Cost', 1, 8),
				array('orderHandlingCost', 'orders', 'Handling Cost', 1, 9),
				array('orderTotalAmount', 'orders', 'Order Total', 1, 10),
				array('orderCustomerID', 'orders', 'Customer ID', 1, 11),
				array('orderCustomerName', 'orders', 'Customer Name', 1, 12),
				array('orderCustomerEmail', 'orders', 'Customer Email', 1, 13),
				array('orderCustomerPhone', 'orders', 'Customer Phone', 1, 14),
				array('orderShipMethod', 'orders', 'Ship Method', 1, 15),
				array('orderPayMethod', 'orders', 'Payment Method', 1, 16),
				array('orderTotalQty', 'orders', 'Total Quantity', 1, 17),
				array('orderTotalShipped', 'orders', 'Total Shipped', 1, 18),
				array('orderDateShipped', 'orders', 'Date Shipped', 1, 19),
				array('orderTrackingNo', 'orders', 'Tracking No', 1, 20),
				array('orderCurrency', 'orders', 'Order Currency Code', 1, 21),
				array('orderExchangeRate', 'orders', 'Exchange Rate', 1, 22),
				array('orderNotes', 'orders', 'Order Notes', 1, 23),
				array('orderCustMessage', 'orders', 'Customer Message', 1, 24),
				array('billName', 'orders', 'Billing Name', 1, 25),
				array('billFirstName', 'orders', 'Billing First Name', 1, 26),
				array('billLastName', 'orders', 'Billing Last Name', 1, 27),
				array('billCompany', 'orders', 'Billing Company', 1, 28),
				array('billStreet1', 'orders', 'Billing Street 1', 1, 29),
				array('billStreet2', 'orders', 'Billing Street 2', 1, 30),
				array('billSuburb', 'orders', 'Billing Suburb', 1, 31),
				array('billState', 'orders', 'Billing State', 1, 32),
				array('billStateAbbrv', 'orders', 'Billing State Abbreviation', 1, 33),
				array('billZip', 'orders', 'Billing Zip', 1, 34),
				array('billCountry', 'orders', 'Billing Country', 1, 35),
				array('billSSC', 'orders', 'Billing Suburb + State + Zip', 1, 36),
				array('billPhone', 'orders', 'Billing Phone', 1, 37),
				array('billEmail', 'orders', 'Billing Email', 1, 38),
				array('billFormFields', 'orders', 'Billing Form Fields', 1, 39),
				array('shipName', 'orders', 'Shipping Name', 1, 40),
				array('shipFirstName', 'orders', 'Shipping First Name', 1, 41),
				array('shipLastName', 'orders', 'Shipping Last Name', 1, 42),
				array('shipCompany', 'orders', 'Shipping Company', 1, 43),
				array('shipStreet1', 'orders', 'Shipping Street 1', 1, 44),
				array('shipStreet2', 'orders', 'Shipping Street 2', 1, 45),
				array('shipSuburb', 'orders', 'Shipping Suburb', 1, 46),
				array('shipState', 'orders', 'Shipping State', 1, 47),
				array('shipStateAbbrv', 'orders', 'Shipping State Abbreviation', 1, 48),
				array('shipZip', 'orders', 'Shipping Zip', 1, 49),
				array('shipCountry', 'orders', 'Shipping Country', 1, 50),
				array('shipSSC', 'orders', 'Shipping Suburb + State + Zip', 1, 51),
				array('shipPhone', 'orders', 'Shipping Phone', 1, 52),
				array('shipEmail', 'orders', 'Shipping Email', 1, 53),
				array('shipFormFields', 'orders', 'Shipping Form Fields', 1, 54),
				array('orderProdDetails', 'orders', 'Product Details', 1, 55),
				array('orderProdID', 'orders', 'Product ID', 1, 56),
				array('orderProdQty', 'orders', 'Product Qty', 1, 57),
				array('orderProdSKU', 'orders', 'Product SKU', 1, 58),
				array('orderProdName', 'orders', 'Product Name', 1, 59),
				array('orderProdVariationDetails', 'orders', 'Product Variation Details', 1, 60),
				array('orderProdPrice', 'orders', 'Product Unit Price', 1, 61),
				array('orderProdIndex', 'orders', 'Product Index', 1, 62),
				array('orderProdWeight', 'orders', 'Product Weight', 1, 63),
				array('orderProdTotalPrice', 'orders', 'Product Total Price', 1, 64),
				array('orderGLAccount', 'orders', 'Peachtree General Ledger Account', 1, 65),
				array('orderPTTaxType', 'orders', 'Peachtree Tax Type', 1, 66),
				array('orderProductCount', 'orders', '# Unique Products in Order', 1, 67),
				array('orderCombinedWeight', 'orders', 'Combined Product Weight', 1, 68),
				array('orderTodaysDate', 'orders', 'Todays Date', 1, 69),
				array('orderAccountsReceivable', 'orders', 'Peachtree Accounts Receivable Account', 1, 70),
				array('productID', 'products', 'Product ID', 0, 1),
				array('productBrandName', 'products', 'Brand + Name', 0, 5),
				array('productCalculatedPrice', 'products', 'Calculated Price', 0, 12),
				array('productNotVisible', 'products', 'Product Not Visible', 0, 22),
				array('productInventoried', 'products', 'Product Inventoried', 0, 24),
				array('productDateAdded', 'products', 'Date Added', 0, 27),
				array('productLastModified', 'products', 'Date Modified', 0, 28),
				array('productFilePath', 'products', 'Product File Path', 0, 32),
				array('productFileTotalDownloads', 'products', 'Product File Total Downloads', 0, 35),
				array('productCategories', 'products', 'Category Details', 0, 37),
				array('productCategoryID', 'products', 'Category ID', 0, 38),
				array('productCategoryName', 'products', 'Category Name', 0, 39),
				array('productCategoryPath', 'products', 'Category Path', 0, 40),
				array('productImageFile', 'products', 'Product Image File', 0, 44),
				array('productImageURL', 'products', 'Product Image URL', 0, 45),
				array('productVariations', 'products', 'Product Variations', 0, 53),
				array('productVarDetails', 'products', 'Variation Details', 0, 54),
				array('productVarSKU', 'products', 'SKU', 0, 55),
				array('productVarPrice', 'products', 'Price', 0, 56),
				array('productVarWeight', 'products', 'Weight', 0, 57),
				array('productVarStockLevel', 'products', 'Stock Level', 0, 58),
				array('productVarLowStockLevel', 'products', 'Low Stock Level', 0, 59),
				array('productName', 'products', 'Product Name', 1, 0),
				array('productType', 'products', 'Product Type', 1, 2),
				array('productCode', 'products', 'Product Code/SKU', 1, 3),
				array('productBrand', 'products', 'Brand Name', 1, 4),
				array('productDesc', 'products', 'Product Description', 1, 6),
				array('productPrice', 'products', 'Price', 1, 7),
				array('productCostPrice', 'products', 'Cost Price', 1, 8),
				array('productRetailPrice', 'products', 'Retail Price', 1, 9),
				array('productSalePrice', 'products', 'Sale Price', 1, 10),
				array('productTaxable', 'products', 'Taxable Product?', 1, 11),
				array('productShippingPrice', 'products', 'Fixed Shipping Cost', 1, 13),
				array('productFreeShipping', 'products', 'Free Shipping', 1, 14),
				array('productWarranty', 'products', 'Product Warranty', 1, 15),
				array('productWeight', 'products', 'Product Weight', 1, 16),
				array('productWidth', 'products', 'Product Width', 1, 17),
				array('productHeight', 'products', 'Product Height', 1, 18),
				array('productDepth', 'products', 'Product Depth', 1, 19),
				array('productPurchasable', 'products', 'Allow Purchases?', 1, 20),
				array('productVisible', 'products', 'Product Visible?', 1, 21),
				array('productAvailability', 'products', 'Product Availability', 1, 23),
				array('productStockLevel', 'products', 'Current Stock Level', 1, 25),
				array('productLowStockLevel', 'products', 'Low Stock Level', 1, 26),
				array('productCategoryString', 'products', 'Category', 1, 29),
				array('productFiles', 'products', 'Product Files', 1, 30),
				array('productFileFileName', 'products', 'Product File', 1, 31),
				array('productFileDescription', 'products', 'Product File Description', 1, 33),
				array('productFileMaxDownloads', 'products', 'Product File Max Downloads', 1, 34),
				array('productFileDisabledAfter', 'products', 'Product File Expires After', 1, 36),
				array('productImages', 'products', 'Product Images', 1, 41),
				array('productImageID', 'products', 'Product Image ID', 1, 42),
				array('productImagePath', 'products', 'Product Image File', 1, 43),
				array('productImageDescription', 'products', 'Product Image Description', 1, 46),
				array('productImageIsThumbnail', 'products', 'Product Image Is Thumbnail', 1, 47),
				array('productImageIndex', 'products', 'Product Image Sort', 1, 48),
				array('productSearchKeywords', 'products', 'Search Keywords', 1, 49),
				array('productPageTitle', 'products', 'Page Title', 1, 50),
				array('productMetaKeywords', 'products', 'Meta Keywords', 1, 51),
				array('productMetaDesc', 'products', 'Meta Description', 1, 52),
				array('productMYOBAsset', 'products', 'MYOB Asset Acct', 1, 60),
				array('productMYOBIncome', 'products', 'MYOB Income Acct', 1, 61),
				array('productMYOBExpense', 'products', 'MYOB Expense Acct', 1, 62),
				array('productCondition', 'products', 'Product Condition', 1, 63),
				array('productShowCondition', 'products', 'Show Product Condition?', 1, 64),
				array('productEventDateRequired', 'products', 'Event Date Required?', 1, 65),
				array('productEventDateName', 'products', 'Event Date Name', 1, 66),
				array('productEventDateLimited', 'products', 'Event Date Is Limited?', 1, 67),
				array('productEventDateStartDate', 'products', 'Event Date Start Date', 1, 68),
				array('productEventDateEndDate', 'products', 'Event Date End Date', 1, 69),
				array('salestaxDate', 'salestax', 'Period', 1, 0),
				array('salestaxTaxName', 'salestax', 'Tax', 1, 1),
				array('salestaxTaxRate', 'salestax', 'Rate', 1, 2),
				array('salestaxNumOrders', 'salestax', 'Number of Orders', 1, 3),
				array('salestaxTaxAmount', 'salestax', 'Tax Amount', 1, 4),
				array('redirectPath', 'redirects', 'Old Path', 1, 0),
				array('redirectOldURL', 'redirects', 'Old URL', 1, 1),
				array('redirectNewURL', 'redirects', 'New URL', 1, 2),
				array('redirectAssocType', 'redirects', 'Associated Type', 1, 3),
				array('redirectAssocId', 'redirects', 'Associated ID', 1, 4)
			);

			foreach ($fields as $field) {
				$insert = array(
					'exporttemplateid'	=> $tempId,
					'fieldid'			=> $field[0],
					'fieldtype'			=> $field[1],
					'fieldname'			=> $field[2],
					'includeinexport'	=> $field[3],
					'sortorder'			=> $field[4]
				);

				$GLOBALS['ISC_CLASS_DB']->InsertQuery('export_template_fields', $insert);
			}
		}

		return true;
	}

	public function add_redirects_to_templates()
	{
		// add to default
		$query = "SELECT * FROM [|PREFIX|]export_template_fields WHERE exporttemplateid = 1 AND fieldid = 'redirectPath'";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!$GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			$fields = array(
				array('redirectPath', 'redirects', 'Old Path', 1, 0),
				array('redirectOldURL', 'redirects', 'Old URL', 1, 1),
				array('redirectNewURL', 'redirects', 'New URL', 1, 2),
				array('redirectAssocType', 'redirects', 'Associated Type', 1, 3),
				array('redirectAssocId', 'redirects', 'Associated ID', 1, 4),
				array('redirectNewURLOrType', 'redirects', 'New URL or Associated Type', 0, 5)
			);

			foreach ($fields as $field) {
				$insert = array(
					'exporttemplateid'	=> '1',
					'fieldid'			=> $field[0],
					'fieldtype'			=> $field[1],
					'fieldname'			=> $field[2],
					'includeinexport'	=> $field[3],
					'sortorder'			=> $field[4]
				);

				$GLOBALS['ISC_CLASS_DB']->InsertQuery('export_template_fields', $insert);
			}
		}

		// add redirects to used types for default template
		$query = "SELECT usedtypes FROM [|PREFIX|]export_templates WHERE exporttemplateid = 1";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
			$usedtypes = explode(',', $row['usedtypes']);
			if (!in_array('redirects', $usedtypes)) {
				$usedtypes[] = 'redirects';

				$query = "UPDATE [|PREFIX|]export_templates SET usedtypes = '" . implode(',', $usedtypes) . "' WHERE exporttemplateid = 1";
				$GLOBALS['ISC_CLASS_DB']->Query($query);
			}
		}

		// add to bulk edit
		$query = "SELECT * FROM [|PREFIX|]export_template_fields WHERE exporttemplateid = 4 AND fieldid = 'redirectPath'";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!$GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			$fields = array(
				array('redirectPath', 'redirects', 'Old Path', 0, 0),
				array('redirectOldURL', 'redirects', 'Old URL', 1, 1),
				array('redirectNewURL', 'redirects', 'New URL', 0, 2),
				array('redirectAssocType', 'redirects', 'Associated Type', 0, 3),
				array('redirectAssocId', 'redirects', 'Associated ID', 1, 5),
				array('redirectNewURLOrType', 'redirects', 'New URL', 1, 4)
			);

			foreach ($fields as $field) {
				$insert = array(
					'exporttemplateid'	=> '4',
					'fieldid'			=> $field[0],
					'fieldtype'			=> $field[1],
					'fieldname'			=> $field[2],
					'includeinexport'	=> $field[3],
					'sortorder'			=> $field[4]
				);

				$GLOBALS['ISC_CLASS_DB']->InsertQuery('export_template_fields', $insert);
			}
		}

		// add redirects to used types for bulk edit template
		$query = "SELECT usedtypes FROM [|PREFIX|]export_templates WHERE exporttemplateid = 4";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
			$usedtypes = explode(',', $row['usedtypes']);
			if (!in_array('redirects', $usedtypes)) {
				$usedtypes[] = 'redirects';

				$query = "UPDATE [|PREFIX|]export_templates SET usedtypes = '" . implode(',', $usedtypes) . "' WHERE exporttemplateid = 4";
				$GLOBALS['ISC_CLASS_DB']->Query($query);
			}
		}

		return true;
	}

	public function update_templates_subitems()
	{
		$update = array(
			'variablevalue' => 'combine'
		);
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery('export_method_settings', $update, "variablename = 'SubItems' AND variablevalue = '1'");

		$update = array(
			'variablevalue' => 'rows'
		);
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery('export_method_settings', $update, "variablename = 'SubItems' AND variablevalue = '0'");

		return true;
	}

	public function delete_alt_customers_setting()
	{
		if (!$GLOBALS['ISC_CLASS_DB']->DeleteQuery('export_method_settings' , 'WHERE variablename = "AltCustomers"')) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function update_taiwan_again()
	{
		$query = "UPDATE [|PREFIX|]countries SET countryname = 'Taiwan' WHERE countryiso3 = 'TWN' AND countryname = 'Taiwan, Province of China'";
		$GLOBALS['ISC_CLASS_DB']->Query($query);

		return true;
	}

	public function fix_laois_county()
	{
		$query = "UPDATE [|PREFIX|]country_states SET statename = 'Laois' WHERE stateabbrv = 'LS' AND statecountry = 103";
		$GLOBALS['ISC_CLASS_DB']->Query($query);

		return true;
	}

	public function add_montenegro()
	{
		$query = "SELECT * FROM [|PREFIX|]countries WHERE countryiso3 = 'MNE'";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!$GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			$insert = array(
					'countryname'		=> "Montenegro",
					'countryiso2'		=> 'ME',
					'countryiso3'		=> 'MNE'
			);

			$GLOBALS['ISC_CLASS_DB']->InsertQuery('countries', $insert);
		}

		return true;
	}

	public function update_serbia()
	{
		$query = "UPDATE [|PREFIX|]countries SET countryname = 'Serbia', countryiso2 = 'RS', countryiso3 = 'SRB' WHERE countryiso2 = 'CS'";
		$GLOBALS['ISC_CLASS_DB']->Query($query);

		return true;
	}

}
