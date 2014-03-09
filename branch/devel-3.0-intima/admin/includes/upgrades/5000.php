<?php
class ISC_ADMIN_UPGRADE_5000 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		'add_customer_group_discount_method',
		"add_export_template_tables",
		"add_new_permissions",
		'add_discount_rules',
		'add_event_date',
		'add_form_field_tables',
		'add_variation_combination_product_hash',
		'add_accounting_fields',
		'add_productid_field',
		'prodwarranty_to_text_field'
	);

	public function add_customer_group_discount_method()
	{
		if (!$this->ColumnExists('[|PREFIX|]customer_group_discounts', 'discountmethod')) {
			$query = "ALTER TABLE [|PREFIX|]customer_group_discounts ADD discountmethod VARCHAR(100) NOT NULL AFTER appliesto";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

			$query = "UPDATE [|PREFIX|]customer_group_discounts SET discountmethod='percent' Where discountmethod = ''";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]customer_groups', 'discountmethod')) {
			$query = "ALTER TABLE `[|PREFIX|]customer_groups` ADD `discountmethod` VARCHAR( 100 ) NOT NULL AFTER `discount`";

			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

			$query = "UPDATE [|PREFIX|]customer_groups SET discountmethod='percent' Where discountmethod = ''";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_export_template_tables()
	{
		if (!$this->TableExists("export_templates")) {
			$query = "
					CREATE TABLE `[|PREFIX|]export_templates` (
						`exporttemplateid` int(11) unsigned NOT NULL auto_increment,
						`exporttemplatename` varchar(100) NOT NULL,
						`myobassetaccount` varchar(20) NOT NULL,
						`myobincomeaccount` varchar(20) NOT NULL,
						`myobexpenseaccount` varchar(20) NOT NULL,
						`peachtreereceivableaccount` varchar(20) NOT NULL,
						`peachtreeglaccount` varchar(20) NOT NULL,
						`modifyforpeachtree` tinyint(1) unsigned NOT NULL,
						`dateformat` varchar(15) NOT NULL,
						`priceformat` varchar(15) NOT NULL,
						`boolformat` varchar(15) NOT NULL,
						`blankforfalse` tinyint(1) unsigned NOT NULL,
						`vendorid` int(11) unsigned NOT NULL,
						`usedtypes` varchar(63) NOT NULL,
						`builtin` tinyint(1) unsigned NOT NULL,
						PRIMARY KEY  (`exporttemplateid`),
						KEY `exporttemplatename` (`exporttemplatename`),
						KEY `vendorid` (`vendorid`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->TableExists('export_template_fields')) {
			$query = "
				CREATE TABLE `[|PREFIX|]export_template_fields` (
					`exporttemplatefieldid` smallint(5) unsigned NOT NULL auto_increment,
					`exporttemplateid` smallint(5) unsigned NOT NULL,
					`fieldid` varchar(31) NOT NULL,
					`fieldtype` varchar(31) NOT NULL,
					`fieldname` varchar(63) NOT NULL,
					`includeinexport` tinyint(1) unsigned NOT NULL,
					`sortorder` tinyint(3) unsigned NOT NULL,
					PRIMARY KEY  (`exporttemplatefieldid`),
					KEY `exporttemplateid` (`exporttemplateid`,`fieldtype`,`includeinexport`,`sortorder`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->TableExists('export_method_settings')) {
			$query = "
				CREATE TABLE `[|PREFIX|]export_method_settings` (
					`exportmethodid` int(11) unsigned NOT NULL auto_increment,
					`methodname` varchar(15) NOT NULL,
					`exporttemplateid` int(11) unsigned NOT NULL,
					`variablename` varchar(31) NOT NULL,
					`variablevalue` varchar(31) NOT NULL,
					PRIMARY KEY  (`exportmethodid`),
					KEY `methodname` (`methodname`,`exporttemplateid`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci
			";

			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		// create Default template
		$query = "SELECT * FROM [|PREFIX|]export_templates WHERE exporttemplatename = 'Default' AND builtin = 1";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!$GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			// make template
			$query = "INSERT INTO `[|PREFIX|]export_templates` (`exporttemplatename`, `myobassetaccount`, `myobincomeaccount`, `myobexpenseaccount`, `peachtreereceivableaccount`, `peachtreeglaccount`, `modifyforpeachtree`, `dateformat`, `priceformat`, `boolformat`, `blankforfalse`, `vendorid`, `usedtypes`, `builtin`) VALUES ('Default', '', '', '', '', '', 0, 'dmy-slash', 'number', 'yn', 0, 0, 'customers,orders,products', 1);";
			$GLOBALS['ISC_CLASS_DB']->Query($query);

			$tempId = $GLOBALS['ISC_CLASS_DB']->LastId();

			// add method settings
			$settings =	array(
							array('FieldSeparator',	','),
							array('FieldEnclosure', '"'),
							array('IncludeHeader', '1'),
							array('BlankLine', '0'),
							array('SubItems', '1'),
							array('SubItemSeparator', '|'),
							array('LineEnding', 'Windows'),
							array('AltCustomers', '1')
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
						array('customerID', 'customers', 'Customer ID', 1, 0),
						array('customerName', 'customers', 'Customer Name', 0, 1),
						array('customerFirstName', 'customers', 'First Name', 1, 2),
						array('customerLastName', 'customers', 'Last Name', 1, 3),
						array('customerCompany', 'customers', 'Company', 1, 4),
						array('customerEmail', 'customers', 'Customer E-mail', 1, 5),
						array('customerPhone', 'customers', 'Telephone 1', 1, 6),
						array('customerNotes', 'customers', 'Notes', 1, 7),
						array('customerCredit', 'customers', 'Customer Balance', 1, 8),
						array('customerGroup', 'customers', 'Customer Group', 1, 9),
						array('customerDateJoined', 'customers', 'Customer Since Date', 1, 10),
						array('customerAddresses', 'customers', 'Addresses', 1, 11),
						array('customerFormFields', 'customers', 'Form Fields', 1, 26),
						array('addressName', 'customers', 'Address Name', 0, 12),
						array('addressFirstName', 'customers', 'Address First Name', 1, 13),
						array('addressLastName', 'customers', 'Address Last Name', 1, 14),
						array('addressCompany', 'customers', 'Address Company', 1, 15),
						array('addressLine1', 'customers', 'Address Line 1', 1, 16),
						array('addressLine2', 'customers', 'Address Line 2', 1, 17),
						array('addressSuburb', 'customers', 'City', 1, 18),
						array('addressState', 'customers', 'State/Province', 0, 19),
						array('addressStateAbbrv', 'customers', 'State', 1, 20),
						array('addressPostcode', 'customers', 'Zip', 1, 21),
						array('addressCountry', 'customers', 'Country', 1, 22),
						array('addressBuilding', 'customers', 'Building Type', 0, 23),
						array('addressPhone', 'customers', 'Address Phone', 1, 24),
						array('addressFormFields', 'customers', 'Address Form Fields', 1, 25),
						array('orderID', 'orders', 'Order ID', 1, 0),
						array('orderStatus', 'orders', 'Order Status', 1, 6),
						array('orderDate', 'orders', 'Order Date', 1, 5),
						array('orderSubtotal', 'orders', 'Subtotal', 1, 7),
						array('orderTaxtotal', 'orders', 'Tax Total', 1, 8),
						array('orderTaxRate', 'orders', 'Tax Rate', 1, 9),
						array('orderTaxName', 'orders', 'Tax Name', 1, 10),
						array('orderTotalIncTax', 'orders', 'Total Includes Tax', 1, 11),
						array('orderShipCost', 'orders', 'Shipping Cost', 1, 12),
						array('orderHandlingCost', 'orders', 'Handling Cost', 1, 14),
						array('orderTotalAmount', 'orders', 'Order Total', 1, 15),
						array('orderCustomerID', 'orders', 'Customer ID', 1, 1),
						array('orderCustomerName', 'orders', 'Customer Name', 1, 2),
						array('orderCustomerEmail', 'orders', 'Customer Email', 1, 3),
						array('orderCustomerPhone', 'orders', 'Customer Phone', 1, 4),
						array('orderShipMethod', 'orders', 'Ship Method', 1, 13),
						array('orderPayMethod', 'orders', 'Payment Method', 1, 16),
						array('orderTotalQty', 'orders', 'Total Quantity', 1, 17),
						array('orderTotalShipped', 'orders', 'Total Shipped', 1, 18),
						array('orderDateShipped', 'orders', 'Date Shipped', 1, 19),
						array('orderTrackingNo', 'orders', 'Tracking No', 1, 20),
						array('orderCurrency', 'orders', 'Order Currency Code', 1, 21),
						array('orderExchangeRate', 'orders', 'Exchange Rate', 1, 22),
						array('orderNotes', 'orders', 'Order Notes', 1, 23),
						array('orderCustMessage', 'orders', 'Customer Message', 1, 24),
						array('billName', 'orders', 'Billing Name', 0, 25),
						array('billFirstName', 'orders', 'Billing First Name', 1, 26),
						array('billLastName', 'orders', 'Billing Last Name', 1, 27),
						array('billCompany', 'orders', 'Billing Company', 1, 28),
						array('billStreet1', 'orders', 'Billing Street 1', 1, 29),
						array('billStreet2', 'orders', 'Billing Street 2', 1, 30),
						array('billSuburb', 'orders', 'Billing Suburb', 1, 31),
						array('billState', 'orders', 'Billing State', 0, 32),
						array('billStateAbbrv', 'orders', 'Billing State', 1, 33),
						array('billZip', 'orders', 'Billing Zip', 1, 34),
						array('billCountry', 'orders', 'Billing Country', 1, 35),
						array('billSSC', 'orders', 'Billing Suburb + State + Zip', 0, 36),
						array('billPhone', 'orders', 'Billing Phone', 1, 37),
						array('billEmail', 'orders', 'Billing Email', 1, 38),
						array('billFormFields', 'orders', 'Billing Form Fields', 1, 39),
						array('shipName', 'orders', 'Shipping Name', 0, 40),
						array('shipFirstName', 'orders', 'Shipping First Name', 1, 41),
						array('shipLastName', 'orders', 'Shipping Last Name', 1, 42),
						array('shipCompany', 'orders', 'Shipping Company', 1, 43),
						array('shipStreet1', 'orders', 'Shipping Street 1', 1, 44),
						array('shipStreet2', 'orders', 'Shipping Street 2', 1, 45),
						array('shipSuburb', 'orders', 'Shipping Suburb', 1, 46),
						array('shipState', 'orders', 'Shipping State', 0, 47),
						array('shipStateAbbrv', 'orders', 'Shipping State', 1, 48),
						array('shipZip', 'orders', 'Shipping Zip', 1, 49),
						array('shipCountry', 'orders', 'Shipping Country', 1, 50),
						array('shipSSC', 'orders', 'Shipping Suburb + State + Zip', 0, 51),
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
						array('orderProdIndex', 'orders', 'Product Index', 0, 62),
						array('orderProdTotalPrice', 'orders', 'Product Total Price', 1, 63),
						array('orderGLAccount', 'orders', 'Peachtree General Ledger Account', 0, 64),
						array('orderPTTaxType', 'orders', 'Peachtree Tax Type', 0, 65),
						array('orderProductCount', 'orders', '# Unique Products in Order', 0, 66),
						array('orderTodaysDate', 'orders', 'Todays Date', 0, 67),
						array('orderAccountsReceivable', 'orders', 'Peachtree Accounts Receivable Account', 0, 68),
						array('productID', 'products', 'Product ID', 1, 0),
						array('productType', 'products', 'Product Type', 1, 1),
						array('productCode', 'products', 'Code', 1, 2),
						array('productName', 'products', 'Name', 1, 3),
						array('productBrand', 'products', 'Brand', 1, 4),
						array('productBrandName', 'products', 'Brand + Name', 0, 5),
						array('productDesc', 'products', 'Description', 1, 6),
						array('productTaxable', 'products', 'Taxable Product', 1, 7),
						array('productCostPrice', 'products', 'Cost Price', 1, 8),
						array('productRetailPrice', 'products', 'Retail Price', 1, 9),
						array('productSalePrice', 'products', 'Sale Price', 1, 10),
						array('productCalculatedPrice', 'products', 'Calculated Price', 1, 11),
						array('productShippingPrice', 'products', 'Fixed Shipping Price', 1, 12),
						array('productFreeShipping', 'products', 'Free Shipping', 1, 13),
						array('productWarranty', 'products', 'Warranty', 1, 14),
						array('productWeight', 'products', 'Weight', 1, 15),
						array('productWidth', 'products', 'Width', 1, 16),
						array('productHeight', 'products', 'Height', 1, 17),
						array('productDepth', 'products', 'Depth', 1, 18),
						array('productPurchasable', 'products', 'Allow Purchases', 1, 19),
						array('productVisible', 'products', 'Product Visible', 1, 20),
						array('productNotVisible', 'products', 'Product Not Visible', 0, 21),
						array('productAvailability', 'products', 'Product Availability', 1, 22),
						array('productInventoried', 'products', 'Product Inventoried', 1, 23),
						array('productStockLevel', 'products', 'Stock Level', 1, 24),
						array('productLowStockLevel', 'products', 'Low Stock Level', 1, 25),
						array('productDateAdded', 'products', 'Date Added', 1, 26),
						array('productLastModified', 'products', 'Date Modified', 1, 27),
						array('productCategories', 'products', 'Category Details', 1, 28),
						array('productCategoryID', 'products', 'Category ID', 0, 29),
						array('productCategoryName', 'products', 'Category Name', 1, 30),
						array('productCategoryPath', 'products', 'Category Path', 1, 31),
						array('productPageTitle', 'products', 'Page Title', 1, 32),
						array('productMetaKeywords', 'products', 'META Keywords', 1, 33),
						array('productMetaDesc', 'products', 'META Description', 1, 34),
						array('productVariations', 'products', 'Product Variations', 1, 35),
						array('productVarDetails', 'products', 'Variation Details', 1, 36),
						array('productVarSKU', 'products', 'SKU', 1, 37),
						array('productVarPrice', 'products', 'Price', 1, 38),
						array('productVarWeight', 'products', 'Weight', 1, 39),
						array('productVarStockLevel', 'products', 'Stock Level', 1, 40),
						array('productVarLowStockLevel', 'products', 'Low Stock Level', 1, 41),
						array('productMYOBAsset', 'products', 'MYOB Asset Acct', 0, 42),
						array('productMYOBIncome', 'products', 'MYOB Income Acct', 0, 43),
						array('productMYOBExpense', 'products', 'MYOB Expense Acct', 0, 44)
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

		// create MYOB template
		$query = "SELECT * FROM [|PREFIX|]export_templates WHERE exporttemplatename = 'MYOB' AND builtin = 1";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!$GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			// make template
			$query = "INSERT INTO `[|PREFIX|]export_templates` (`exporttemplatename`, `myobassetaccount`, `myobincomeaccount`, `myobexpenseaccount`, `peachtreereceivableaccount`, `peachtreeglaccount`, `modifyforpeachtree`, `dateformat`, `priceformat`, `boolformat`, `blankforfalse`, `vendorid`, `usedtypes`, `builtin`) VALUES ('MYOB', '', '', '', '', '', 0, 'dmy-slash', 'number', 'yn', 1, 0, 'customers,orders,products', 1)";
			$GLOBALS['ISC_CLASS_DB']->Query($query);

			$tempId = $GLOBALS['ISC_CLASS_DB']->LastId();

			// add method settings
			$settings =	array(
							array('FieldSeparator',	','),
							array('FieldEnclosure', '"'),
							array('IncludeHeader', '1'),
							array('BlankLine', '1'),
							array('SubItems', '0'),
							array('SubItemSeparator', '|'),
							array('LineEnding', 'Windows'),
							array('AltCustomers', '1')
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
						array('customerID', 'customers', 'Card ID', 1, 2),
						array('customerName', 'customers', 'Customer Name', 0, 19),
						array('customerFirstName', 'customers', 'First Name', 1, 1),
						array('customerLastName', 'customers', 'Co./Last Name', 1, 0),
						array('customerCompany', 'customers', 'Company', 0, 20),
						array('customerEmail', 'customers', 'Customer E-mail', 0, 21),
						array('customerPhone', 'customers', 'Telephone 1', 0, 22),
						array('customerNotes', 'customers', 'Notes', 1, 18),
						array('customerCredit', 'customers', 'Customer Balance', 0, 23),
						array('customerGroup', 'customers', 'Customer Group', 0, 24),
						array('customerDateJoined', 'customers', 'Customer Since Date', 0, 25),
						array('customerAddresses', 'customers', 'Addresses', 1, 3),
						array('customerFormFields', 'customers', 'Form Fields', 0, 26),
						array('addressName', 'customers', 'Address Contact Name', 1, 11),
						array('addressFirstName', 'customers', 'Address First Name', 0, 12),
						array('addressLastName', 'customers', 'Address Last Name', 0, 13),
						array('addressCompany', 'customers', 'Address Company', 0, 14),
						array('addressLine1', 'customers', 'Address Line 1', 1, 4),
						array('addressLine2', 'customers', 'Address Line 2', 1, 5),
						array('addressSuburb', 'customers', 'Address City', 1, 6),
						array('addressState', 'customers', 'Address State', 1, 7),
						array('addressStateAbbrv', 'customers', 'State Abbreviation', 0, 15),
						array('addressPostcode', 'customers', 'Address Postcode', 1, 8),
						array('addressCountry', 'customers', 'Address Country', 1, 9),
						array('addressBuilding', 'customers', 'Building Type', 0, 16),
						array('addressPhone', 'customers', 'Address Phone 1', 1, 10),
						array('addressFormFields', 'customers', 'Address Form Fields', 0, 17),
						array('orderID', 'orders', 'Invoice #', 1, 7),
						array('orderStatus', 'orders', 'Order Status', 0, 37),
						array('orderDate', 'orders', 'Date', 1, 8),
						array('orderSubtotal', 'orders', 'Total', 1, 20),
						array('orderTaxtotal', 'orders', 'Tax Amount', 1, 25),
						array('orderTaxRate', 'orders', 'Tax Rate', 0, 32),
						array('orderTaxName', 'orders', 'Tax Code', 1, 24),
						array('orderTotalIncTax', 'orders', 'Inclusive', 1, 6),
						array('orderShipCost', 'orders', 'Inc-Tax Freight Amount', 1, 26),
						array('orderHandlingCost', 'orders', 'Handling Cost', 0, 38),
						array('orderTotalAmount', 'orders', 'Inc-Tax Total', 1, 21),
						array('orderCustomerID', 'orders', 'Card ID', 1, 31),
						array('orderShipMethod', 'orders', 'Ship Via', 1, 9),
						array('orderPayMethod', 'orders', 'Payment Method', 1, 29),
						array('orderTotalQty', 'orders', 'Total Quantity', 0, 39),
						array('orderTotalShipped', 'orders', 'Total Shipped', 0, 40),
						array('orderDateShipped', 'orders', 'Shipping Date', 1, 23),
						array('orderTrackingNo', 'orders', 'Tracking No', 0, 41),
						array('orderCurrency', 'orders', 'Currency Code', 1, 27),
						array('orderExchangeRate', 'orders', 'Exchange Rate', 1, 28),
						array('orderNotes', 'orders', 'Comment', 1, 22),
						array('orderCustMessage', 'orders', 'Payment Notes', 1, 30),
						array('billName', 'orders', '', 0, 42),
						array('billFirstName', 'orders', 'First Name', 0, 33),
						array('billLastName', 'orders', 'Co./Last Name', 0, 34),
						array('billCompany', 'orders', 'Billing Company', 0, 43),
						array('billStreet1', 'orders', 'Address Line 1', 0, 44),
						array('billStreet2', 'orders', 'Address Line 2', 0, 45),
						array('billSuburb', 'orders', 'Billing Suburb', 0, 46),
						array('billState', 'orders', 'Billing State', 0, 47),
						array('billStateAbbrv', 'orders', 'Billing State Abbreviation', 0, 48),
						array('billZip', 'orders', 'Billing Zip', 0, 49),
						array('billCountry', 'orders', 'Billing Country', 0, 50),
						array('billSSC', 'orders', '', 0, 35),
						array('billPhone', 'orders', 'Billing Phone', 0, 51),
						array('billEmail', 'orders', 'Billing Email', 0, 52),
						array('billFormFields', 'orders', 'Billing Form Fields', 0, 63),
						array('shipName', 'orders', 'Shipping Name', 0, 53),
						array('shipFirstName', 'orders', 'First Name', 1, 1),
						array('shipLastName', 'orders', 'Co./Last Name', 1, 0),
						array('shipCompany', 'orders', 'Shipping Company', 0, 54),
						array('shipStreet1', 'orders', 'Address Line 1', 1, 2),
						array('shipStreet2', 'orders', 'Address Line 2', 1, 3),
						array('shipSuburb', 'orders', 'Address Line 3', 0, 36),
						array('shipState', 'orders', 'Shipping State', 0, 55),
						array('shipStateAbbrv', 'orders', 'Shipping State Abbreviation', 0, 56),
						array('shipZip', 'orders', 'Shipping Zip', 0, 57),
						array('shipCountry', 'orders', 'Address Line 4', 1, 5),
						array('shipSSC', 'orders', 'Address Line 3', 1, 4),
						array('shipPhone', 'orders', 'Shipping Phone', 0, 58),
						array('shipEmail', 'orders', 'Shipping Email', 0, 59),
						array('shipFormFields', 'orders', 'Shipping Form Fields', 0, 64),
						array('orderProdDetails', 'orders', 'Product Details', 1, 10),
						array('orderProdID', 'orders', 'Item Number', 1, 11),
						array('orderProdQty', 'orders', 'Quantity', 1, 12),
						array('orderProdSKU', 'orders', 'Product SKU', 0, 15),
						array('orderProdName', 'orders', 'Description', 1, 13),
						array('orderProdPrice', 'orders', 'Inc-Tax Price', 1, 14),
						array('orderProdIndex', 'orders', 'Product Index', 0, 16),
						array('orderProdTotalPrice', 'orders', 'Product Total Price', 0, 18),
						array('orderGLAccount', 'orders', 'G/L Account', 0, 17),
						array('orderPTTaxType', 'orders', 'Peachtree Tax Type', 0, 19),
						array('orderProductCount', 'orders', '# Unique Products in Order', 0, 60),
						array('orderTodaysDate', 'orders', 'Todays Date', 0, 61),
						array('orderAccountsReceivable', 'orders', 'Peachtree Accounts Receivable Account', 0, 62),
						array('productID', 'products', 'Item Number', 1, 0),
						array('productType', 'products', 'Product Type', 0, 12),
						array('productCode', 'products', 'Code', 0, 14),
						array('productName', 'products', 'Item Name', 1, 1),
						array('productBrand', 'products', 'Brand', 0, 15),
						array('productBrandName', 'products', '', 0, 13),
						array('productDesc', 'products', 'Description', 1, 7),
						array('productTaxable', 'products', 'Taxable Product', 0, 16),
						array('productCostPrice', 'products', 'Standard Cost', 1, 11),
						array('productRetailPrice', 'products', 'Retail Price', 0, 17),
						array('productSalePrice', 'products', 'Sale Price', 0, 18),
						array('productCalculatedPrice', 'products', 'Selling Price', 1, 9),
						array('productShippingPrice', 'products', 'Fixed Shipping Price', 0, 19),
						array('productFreeShipping', 'products', 'Free Shipping', 0, 20),
						array('productWarranty', 'products', 'Warranty', 0, 21),
						array('productWeight', 'products', 'Weight', 0, 22),
						array('productWidth', 'products', 'Width', 0, 23),
						array('productHeight', 'products', 'Height', 0, 24),
						array('productDepth', 'products', 'Depth', 0, 25),
						array('productPurchasable', 'products', 'Sell', 1, 2),
						array('productVisible', 'products', 'Product Visible', 0, 26),
						array('productNotVisible', 'products', 'Inactive Item', 1, 10),
						array('productAvailability', 'products', 'Product Availability', 0, 27),
						array('productInventoried', 'products', 'Inventory', 1, 3),
						array('productStockLevel', 'products', 'Stock Level', 0, 28),
						array('productLowStockLevel', 'products', 'Minimum Level', 1, 8),
						array('productDateAdded', 'products', 'Date Added', 0, 29),
						array('productLastModified', 'products', 'Date Modified', 0, 30),
						array('productCategories', 'products', 'Category Details', 0, 31),
						array('productCategoryID', 'products', 'Category ID', 0, 32),
						array('productCategoryName', 'products', 'Category Name', 0, 33),
						array('productCategoryPath', 'products', 'Category Path', 0, 34),
						array('productPageTitle', 'products', 'Page Title', 0, 35),
						array('productMetaKeywords', 'products', 'META Keywords', 0, 36),
						array('productMetaDesc', 'products', 'META Description', 0, 37),
						array('productVariations', 'products', 'Product Variations', 0, 38),
						array('productVarDetails', 'products', 'Variation Details', 0, 39),
						array('productVarSKU', 'products', 'SKU', 0, 40),
						array('productVarPrice', 'products', 'Price', 0, 41),
						array('productVarWeight', 'products', 'Weight', 0, 42),
						array('productVarStockLevel', 'products', 'Stock Level', 0, 43),
						array('productVarLowStockLevel', 'products', 'Low Stock Level', 0, 44),
						array('productMYOBAsset', 'products', 'Asset Acct', 1, 4),
						array('productMYOBIncome', 'products', 'Income Acct', 1, 5),
						array('productMYOBExpense', 'products', 'Expense/COS Acct', 1, 6)
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

		// create Peachtree Template template
		$query = "SELECT * FROM [|PREFIX|]export_templates WHERE exporttemplatename = 'Peachtree Accounting' AND builtin = 1";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!$GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			// make template
			$query = "INSERT INTO `[|PREFIX|]export_templates` (`exporttemplatename`, `myobassetaccount`, `myobincomeaccount`, `myobexpenseaccount`, `peachtreereceivableaccount`, `peachtreeglaccount`, `modifyforpeachtree`, `dateformat`, `priceformat`, `boolformat`, `blankforfalse`, `vendorid`, `usedtypes`, `builtin`) VALUES ('Peachtree Accounting', '', '', '', '', '', 1, 'dmy-slash', 'number', 'truefalse', 0, 0, 'customers,orders,products', 1)";
			$GLOBALS['ISC_CLASS_DB']->Query($query);

			$tempId = $GLOBALS['ISC_CLASS_DB']->LastId();

			// add method settings
			$settings =	array(
							array('FieldSeparator',	','),
							array('FieldEnclosure', '"'),
							array('IncludeHeader', '1'),
							array('BlankLine', '0'),
							array('SubItems', '0'),
							array('SubItemSeparator', '|'),
							array('LineEnding', 'Windows'),
							array('AltCustomers', '1')
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
						array('customerID', 'customers', 'Customer ID', 1, 0),
						array('customerName', 'customers', 'Customer Name', 1, 1),
						array('customerFirstName', 'customers', 'First Name', 0, 21),
						array('customerLastName', 'customers', 'Last Name', 0, 22),
						array('customerCompany', 'customers', 'Company', 0, 23),
						array('customerEmail', 'customers', 'Customer E-mail', 1, 18),
						array('customerPhone', 'customers', 'Telephone 1', 1, 17),
						array('customerNotes', 'customers', 'Notes', 0, 24),
						array('customerCredit', 'customers', 'Customer Balance', 1, 20),
						array('customerGroup', 'customers', 'Customer Group', 0, 25),
						array('customerDateJoined', 'customers', 'Customer Since Date', 1, 19),
						array('customerAddresses', 'customers', 'Addresses', 1, 2),
						array('customerFormFields', 'customers', 'Form Fields', 0, 26),
						array('addressName', 'customers', 'Ship to Name', 1, 3),
						array('addressFirstName', 'customers', 'Address First Name', 0, 10),
						array('addressLastName', 'customers', 'Address Last Name', 0, 11),
						array('addressCompany', 'customers', 'Address Company', 0, 12),
						array('addressLine1', 'customers', 'Ship to Address Line 1', 1, 4),
						array('addressLine2', 'customers', 'Ship to Address Line 2', 1, 5),
						array('addressSuburb', 'customers', 'Ship to City', 1, 6),
						array('addressState', 'customers', 'Ship to State', 0, 13),
						array('addressStateAbbrv', 'customers', 'Ship to State', 1, 7),
						array('addressPostcode', 'customers', 'Ship to Zipcode', 1, 8),
						array('addressCountry', 'customers', 'Ship to Country', 1, 9),
						array('addressBuilding', 'customers', 'Building Type', 0, 14),
						array('addressPhone', 'customers', 'Address Phone', 0, 15),
						array('addressFormFields', 'customers', 'Address Form Fields', 0, 16),
						array('orderID', 'orders', 'Invoice/CM #', 1, 1),
						array('orderStatus', 'orders', 'Order Status', 0, 27),
						array('orderDate', 'orders', 'Date', 1, 2),
						array('orderSubtotal', 'orders', 'Subtotal', 0, 28),
						array('orderTaxtotal', 'orders', 'Tax Total', 0, 29),
						array('orderTaxRate', 'orders', 'Tax Rate', 0, 30),
						array('orderTaxName', 'orders', 'Sales Tax Agency', 0, 31),
						array('orderTotalIncTax', 'orders', 'Total Includes Tax', 0, 32),
						array('orderShipCost', 'orders', 'Shipping Cost', 0, 33),
						array('orderHandlingCost', 'orders', 'Handling Cost', 0, 34),
						array('orderTotalAmount', 'orders', 'Order Total', 0, 35),
						array('orderCustomerID', 'orders', 'Customer ID', 1, 0),
						array('orderShipMethod', 'orders', 'Ship Via', 1, 10),
						array('orderPayMethod', 'orders', 'Payment Method', 0, 36),
						array('orderTotalQty', 'orders', 'Total Quantity', 0, 37),
						array('orderTotalShipped', 'orders', 'Total Shipped', 0, 38),
						array('orderDateShipped', 'orders', 'Ship Date', 1, 11),
						array('orderTrackingNo', 'orders', 'Tracking No', 0, 39),
						array('orderCurrency', 'orders', 'Order Currency Code', 0, 40),
						array('orderExchangeRate', 'orders', 'Exchange Rate', 0, 41),
						array('orderNotes', 'orders', 'Internal Note', 1, 14),
						array('orderCustMessage', 'orders', 'Invoice Note', 1, 13),
						array('billName', 'orders', 'Billing Name', 0, 58),
						array('billFirstName', 'orders', 'Billing First Name', 0, 42),
						array('billLastName', 'orders', 'Billing Last Name', 0, 43),
						array('billCompany', 'orders', 'Billing Company', 0, 44),
						array('billStreet1', 'orders', 'Billing Street 1', 0, 45),
						array('billStreet2', 'orders', 'Billing Street 2', 0, 46),
						array('billSuburb', 'orders', 'Billing Suburb', 0, 47),
						array('billState', 'orders', 'Billing State', 0, 48),
						array('billStateAbbrv', 'orders', 'Billing State Abbreviation', 0, 59),
						array('billZip', 'orders', 'Billing Zip', 0, 49),
						array('billCountry', 'orders', 'Billing Country', 0, 50),
						array('billSSC', 'orders', 'Billing Suburb + State + Zip', 0, 61),
						array('billPhone', 'orders', 'Billing Phone', 0, 51),
						array('billEmail', 'orders', 'Billing Email', 0, 52),
						array('billFormFields', 'orders', 'Billing Form Fields', 0, 63),
						array('shipName', 'orders', 'Ship to Name', 1, 3),
						array('shipFirstName', 'orders', '', 0, 53),
						array('shipLastName', 'orders', 'Shipping Last Name', 0, 54),
						array('shipCompany', 'orders', 'Shipping Company', 0, 55),
						array('shipStreet1', 'orders', 'Ship to Address-Line One', 1, 4),
						array('shipStreet2', 'orders', 'Ship to Address-Line Two', 1, 5),
						array('shipSuburb', 'orders', 'Ship to City', 1, 6),
						array('shipState', 'orders', 'Ship to State', 1, 7),
						array('shipStateAbbrv', 'orders', 'Shipping State Abbreviation', 0, 60),
						array('shipZip', 'orders', 'Ship to Zipcode', 1, 8),
						array('shipCountry', 'orders', 'Ship to Country', 1, 9),
						array('shipSSC', 'orders', 'Shipping Suburb + State + Zip', 0, 62),
						array('shipPhone', 'orders', 'Shipping Phone', 0, 56),
						array('shipEmail', 'orders', 'Shipping Email', 0, 57),
						array('shipFormFields', 'orders', 'Shipping Form Fields', 1, 64),
						array('orderProdDetails', 'orders', 'Product Details', 1, 16),
						array('orderProdID', 'orders', 'Item ID', 1, 19),
						array('orderProdQty', 'orders', 'Quantity', 1, 18),
						array('orderProdSKU', 'orders', 'UPC/SKU', 1, 24),
						array('orderProdName', 'orders', 'Description ', 1, 20),
						array('orderProdPrice', 'orders', 'Unit Price', 1, 22),
						array('orderProdIndex', 'orders', 'Invoice/CM Distribution', 1, 17),
						array('orderProdTotalPrice', 'orders', 'Amount', 1, 25),
						array('orderGLAccount', 'orders', 'G/L Account', 1, 21),
						array('orderPTTaxType', 'orders', 'Tax Type', 1, 23),
						array('orderProductCount', 'orders', 'Number of Distributions', 1, 15),
						array('orderTodaysDate', 'orders', 'Date Due', 0, 26),
						array('orderAccountsReceivable', 'orders', 'Accounts Receivable Account', 1, 12),
						array('productID', 'products', 'Item ID', 1, 0),
						array('productType', 'products', 'Item Type', 1, 7),
						array('productCode', 'products', 'UPC/SKU', 1, 6),
						array('productName', 'products', 'Item Description', 1, 1),
						array('productBrand', 'products', 'Brand', 0, 13),
						array('productBrandName', 'products', 'Brand + Name', 0, 39),
						array('productDesc', 'products', 'Description for Sales ', 1, 3),
						array('productTaxable', 'products', 'Is Taxable', 1, 10),
						array('productCostPrice', 'products', 'Last Unit Cost', 1, 5),
						array('productRetailPrice', 'products', 'Retail Price', 0, 14),
						array('productSalePrice', 'products', 'Sales Price 1', 0, 15),
						array('productCalculatedPrice', 'products', 'Sales Price 1', 1, 4),
						array('productShippingPrice', 'products', 'Fixed Shipping Price', 0, 16),
						array('productFreeShipping', 'products', 'Free Shipping', 0, 17),
						array('productWarranty', 'products', 'Warranty', 0, 18),
						array('productWeight', 'products', 'Weight', 1, 8),
						array('productWidth', 'products', 'Width', 0, 20),
						array('productHeight', 'products', 'Height', 0, 21),
						array('productDepth', 'products', 'Depth', 0, 22),
						array('productPurchasable', 'products', 'Allow Purchases', 0, 40),
						array('productVisible', 'products', 'Product Visible', 0, 12),
						array('productNotVisible', 'products', 'Inactive', 1, 2),
						array('productAvailability', 'products', 'Product Availability', 0, 23),
						array('productInventoried', 'products', 'Product Inventoried', 0, 41),
						array('productStockLevel', 'products', 'Quantity on Hand', 0, 19),
						array('productLowStockLevel', 'products', 'Minimum Stock', 1, 9),
						array('productDateAdded', 'products', 'Date Added', 0, 24),
						array('productLastModified', 'products', 'Effective Date ', 1, 11),
						array('productCategories', 'products', 'Category Details', 0, 25),
						array('productCategoryID', 'products', 'Category ID', 0, 26),
						array('productCategoryName', 'products', 'Category Name', 0, 27),
						array('productCategoryPath', 'products', 'Category Path', 0, 28),
						array('productPageTitle', 'products', 'Page Title', 0, 29),
						array('productMetaKeywords', 'products', 'META Keywords', 0, 30),
						array('productMetaDesc', 'products', 'META Description', 0, 31),
						array('productVariations', 'products', 'Product Variations', 0, 32),
						array('productVarDetails', 'products', 'Variation Details', 0, 33),
						array('productVarSKU', 'products', 'SKU', 0, 34),
						array('productVarPrice', 'products', 'Price', 0, 35),
						array('productVarWeight', 'products', 'Weight', 0, 36),
						array('productVarStockLevel', 'products', 'Stock Level', 0, 37),
						array('productVarLowStockLevel', 'products', 'Low Stock Level', 0, 38),
						array('productMYOBAsset', 'products', 'MYOB Asset Acct', 0, 42),
						array('productMYOBIncome', 'products', 'MYOB Income Acct', 0, 43),
						array('productMYOBExpense', 'products', 'MYOB Expense Acct', 0, 44)
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

	public function add_new_permissions()
	{
		// Array of new permission => insert in to users that have the following permission
		$perms = array(
			AUTH_Manage_ExportTemplates => AUTH_Export_Orders
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

	public function add_discount_rules()
	{

		if (!$this->TableExists('discounts')) {
			$query = "
				CREATE TABLE `[|PREFIX|]discounts` (
					`discountid` int(11) NOT NULL auto_increment,
					`discountname` varchar(100) NOT NULL default '',
					`discountruletype` varchar(100) NOT NULL,
					`discountmaxuses` int(11) NOT NULL default '0',
					`discountcurrentuses` int(11) NOT NULL default '0',
					`discountexpiry` int(11) NOT NULL default '0',
					`discountenabled` tinyint(4) NOT NULL default '0',
					`sortorder` int(9) NOT NULL,
					`halts` int(1) NOT NULL,
					`configdata` text NOT NULL,
					PRIMARY KEY  (`discountid`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
			";

			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_event_date()
	{


		if (!$this->ColumnExists('[|PREFIX|]products', 'prodeventdaterequired')) {
			$query = "ALTER TABLE [|PREFIX|]products ADD prodeventdaterequired TINYINT(4) AFTER prodconfigfields";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]products', 'prodeventdatefieldname')) {
			$query = "ALTER TABLE [|PREFIX|]products ADD prodeventdatefieldname VARCHAR(255) AFTER prodeventdaterequired";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]products', 'prodeventdatelimited')) {
			$query = "ALTER TABLE [|PREFIX|]products ADD prodeventdatelimited TINYINT(4) AFTER prodeventdatefieldname";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]products', 'prodeventdatelimitedtype')) {
			$query = "ALTER TABLE [|PREFIX|]products ADD prodeventdatelimitedtype TINYINT(4) AFTER prodeventdatelimited";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]products', 'prodeventdatelimitedstartdate')) {
			$query = "ALTER TABLE [|PREFIX|]products ADD prodeventdatelimitedstartdate INT(9) AFTER prodeventdatelimitedtype";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]products', 'prodeventdatelimitedenddate')) {
			$query = "ALTER TABLE [|PREFIX|]products ADD prodeventdatelimitedenddate INT(9) AFTER prodeventdatelimitedstartdate";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]order_products', 'ordprodeventname')) {
			$query = "ALTER TABLE [|PREFIX|]order_products ADD ordprodeventname VARCHAR(255) AFTER ordprodqtyshipped";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]order_products', 'ordprodeventdate')) {
			$query = "ALTER TABLE [|PREFIX|]order_products ADD ordprodeventdate INT(9) AFTER ordprodeventname";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]shipment_items', 'itemprodeventname')) {
			$query = "ALTER TABLE [|PREFIX|]shipment_items ADD itemprodeventname VARCHAR(255) AFTER itemprodvariationid";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]shipment_items', 'itemprodeventdate')) {
			$query = "ALTER TABLE [|PREFIX|]shipment_items ADD itemprodeventdate INT(9) AFTER itemprodeventname";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}


		return true;
	}

	public function add_form_field_tables()
	{
		if (!$this->TableExists('forms')) {
			$query = "
				CREATE TABLE `[|PREFIX|]forms` (
					`formid` int(10) unsigned NOT NULL auto_increment,
					`formname` varchar(255) NOT NULL default '',
					PRIMARY KEY  (`formid`)
				) ENGINE=MyISAM CHARSET=utf8 COLLATE utf8_general_ci";

			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->TableExists('formfields')) {
			$query = "
				CREATE TABLE `[|PREFIX|]formfields` (
					`formfieldid` int(10) unsigned NOT NULL auto_increment,
					`formfieldformid` int(10) unsigned NOT NULL default '0',
					`formfieldtype` varchar(50) NOT NULL default '',
					`formfieldlabel` varchar(255) NOT NULL default '',
					`formfielddefaultval` varchar(255) NOT NULL default '',
					`formfieldextrainfo` text,
					`formfieldisrequired` tinyint(1) NOT NULL default '0',
					`formfieldisimmutable` tinyint(1) default '0',
					`formfieldprivateid` varchar(255) NOT NULL default '',
					`formfieldlastmodified` int(10) unsigned NOT NULL default '0',
					`formfieldsort` int(10) unsigned NOT NULL default '0',
					PRIMARY KEY  (`formfieldid`),
					KEY `i_formfields_formfieldformid` (`formfieldformid`)
				) ENGINE=MyISAM CHARSET=utf8 COLLATE utf8_general_ci";

			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->TableExists('formsessions')) {
			$query = "
				CREATE TABLE `[|PREFIX|]formsessions` (
					`formsessionid` int(10) unsigned NOT NULL auto_increment,
					`formsessiondate` int(10) unsigned NOT NULL default '0',
					`formsessionformidx` varchar(255) NOT NULL default '',
					PRIMARY KEY  (`formsessionid`)
				) ENGINE=MyISAM CHARSET=utf8 COLLATE utf8_general_ci";

			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->TableExists('formfieldsessions')) {
			$query = "
				 CREATE TABLE `[|PREFIX|]formfieldsessions` (
					`formfieldsessioniformsessionid` int(10) unsigned NOT NULL default '0',
					`formfieldfieldid` int(10) unsigned NOT NULL default '0',
					`formfieldformid` int(10) unsigned NOT NULL default '0',
					`formfieldfieldtype` varchar(50) NOT NULL default '',
					`formfieldfieldlabel` varchar(255) NOT NULL default '',
					`formfieldfieldvalue` text,
					PRIMARY KEY  (`formfieldsessioniformsessionid`,`formfieldfieldid`),
					KEY `i_formfieldsessions_formfieldsessioniformsessionid` (`formfieldsessioniformsessionid`),
					KEY `i_formfieldsessions_formfieldfieldid` (`formfieldfieldid`),
					KEY `i_formfieldsessions_formfieldformid` (`formfieldformid`)
				) ENGINE=MyISAM CHARSET=utf8 COLLATE utf8_general_ci";

			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]orders', 'ordformsessionid')) {
			$query = "ALTER TABLE [|PREFIX|]orders ADD `ordformsessionid` int(11) NOT NULL default '0'";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]customers', 'custformsessionid')) {
			$query = "ALTER TABLE [|PREFIX|]customers ADD `custformsessionid` int(11) NOT NULL default '0'";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]shipping_addresses', 'shipformsessionid')) {
			$query = "ALTER TABLE [|PREFIX|]shipping_addresses ADD `shipformsessionid` int(11) NOT NULL default '0'";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		/**
		 * Clear all the forms as these are non-editable
		 */
		if ($GLOBALS['ISC_CLASS_DB']->DeleteQuery('forms', 'WHERE 1=1') === false) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		/**
		 * Now add them back in
		 */
		$queries = array(
			array('formid' => FORMFIELDS_FORM_ACCOUNT, 'formname' => 'Account'),
			array('formid' => FORMFIELDS_FORM_BILLING, 'formname' => 'Billing Details'),
			array('formid' => FORMFIELDS_FORM_SHIPPING, 'formname' => 'Shipping Details')
		);

		foreach ($queries as $savedata) {
			if ($GLOBALS['ISC_CLASS_DB']->InsertQuery('forms', $savedata) === false) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		/**
		 * Here is all the form field information. Check each form to see if they have any fields. If so then leave
		 * it alone, else add them in
		 */
		$queries = array(
			FORMFIELDS_FORM_ACCOUNT => array(
					array(1,'singleline','Email Address','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:16:"Textbox Field200";s:5:"style";s:0:"";}',1,1,'EmailAddress',1),
					array(1,'password','Password','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:16:"Textbox Field200";s:5:"style";s:0:"";}',1,1,'Password',2),
					array(1,'password','Confirm Password','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:16:"Textbox Field200";s:5:"style";s:0:"";}',1,1,'ConfirmPassword',3),
			),

			FORMFIELDS_FORM_BILLING => array(
					array(2,'singleline','First Name','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:16:"Textbox Field200";s:5:"style";s:0:"";}',1,1,'FirstName',1),
					array(2,'singleline','Last Name','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:16:"Textbox Field200";s:5:"style";s:0:"";}',1,1,'LastName',2),
					array(2,'singleline','Company Name','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:16:"Textbox Field200";s:5:"style";s:0:"";}',0,1,'CompanyName',3),
					array(2,'singleline','Phone Number','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:16:"Textbox Field200";s:5:"style";s:0:"";}',1,1,'Phone',4),
					array(2,'singleline','Address Line 1','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:16:"Textbox Field200";s:5:"style";s:0:"";}',1,1,'AddressLine1',5),
					array(2,'singleline','Address Line 2','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:16:"Textbox Field200";s:5:"style";s:0:"";}',0,1,'AddressLine2',6),
					array(2,'singleline','Suburb/City','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:16:"Textbox Field200";s:5:"style";s:0:"";}',1,1,'City',7),
					array(2,'singleselect','Country','','a:4:{s:5:"class";s:8:"Field200";s:5:"style";s:0:"";s:12:"chooseprefix";s:16:"Choose a Country";s:7:"options";a:0:{}}',1,1,'Country',8),
					array(2,'selectortext','State/Province','','a:6:{s:5:"class";s:8:"Field200";s:5:"style";s:0:"";s:12:"chooseprefix";s:14:"Choose a State";s:7:"options";a:0:{}s:4:"size";s:0:"";s:9:"maxlength";s:0:"";}',1,1,'State',9),
					array(2,'singleline','Zip/Postcode','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:15:"Textbox Field45";s:5:"style";s:11:"width:40px;";}',1,1,'Zip',10),
					array(2,'checkboxselect','Save This Address?','','a:3:{s:5:"class";s:0:"";s:5:"style";s:0:"";s:7:"options";a:1:{i:0;s:41:"Yes, save this address to my address book";}}',0,1,'SaveThisAddress',11),
					array(2,'checkboxselect','Ship to This Address?','','a:3:{s:5:"class";s:0:"";s:5:"style";s:0:"";s:7:"options";a:1:{i:0;s:25:"Yes, ship to this address";}}',0,1,'ShipToAddress',12),
			),

			FORMFIELDS_FORM_SHIPPING => array(
					array(3,'singleline','First Name','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:16:"Textbox Field200";s:5:"style";s:0:"";}',1,1,'FirstName',1),
					array(3,'singleline','Last Name','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:16:"Textbox Field200";s:5:"style";s:0:"";}',1,1,'LastName',2),
					array(3,'singleline','Company Name','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:16:"Textbox Field200";s:5:"style";s:0:"";}',0,1,'CompanyName',3),
					array(3,'singleline','Phone Number','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:16:"Textbox Field200";s:5:"style";s:0:"";}',1,1,'Phone',4),
					array(3,'singleline','Address Line 1','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:16:"Textbox Field200";s:5:"style";s:0:"";}',1,1,'AddressLine1',5),
					array(3,'singleline','Address Line 2','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:16:"Textbox Field200";s:5:"style";s:0:"";}',0,1,'AddressLine2',6),
					array(3,'singleline','Suburb/City','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:16:"Textbox Field200";s:5:"style";s:0:"";}',1,1,'City',7),
					array(3,'singleselect','Country','','a:4:{s:5:"class";s:8:"Field200";s:5:"style";s:0:"";s:12:"chooseprefix";s:16:"Choose a Country";s:7:"options";a:0:{}}',1,1,'Country',8),
					array(3,'selectortext','State/Province','','a:6:{s:5:"class";s:8:"Field200";s:5:"style";s:0:"";s:12:"chooseprefix";s:14:"Choose a State";s:7:"options";a:0:{}s:4:"size";s:0:"";s:9:"maxlength";s:0:"";}',1,1,'State',9),
					array(3,'singleline','Zip/Postcode','','a:5:{s:12:"defaultvalue";s:0:"";s:4:"size";s:0:"";s:9:"maxlength";s:0:"";s:5:"class";s:15:"Textbox Field45";s:5:"style";s:11:"width:40px;";}',1,1,'Zip',10),
					array(3,'checkboxselect','Save This Address?','','a:3:{s:5:"class";s:0:"";s:5:"style";s:0:"";s:7:"options";a:1:{i:0;s:41:"Yes, save this address to my address book";}}',0,1,'SaveThisAddress',11),
					array(3,'checkboxselect','Ship to This Address?','','a:3:{s:5:"class";s:0:"";s:5:"style";s:0:"";s:7:"options";a:1:{i:0;s:25:"Yes, ship to this address";}}',0,1,'ShipToAddress',12),
			)
		);

		$savedataKeys = array('formfieldformid', 'formfieldtype', 'formfieldlabel', 'formfielddefaultval', 'formfieldextrainfo', 'formfieldisrequired', 'formfieldisimmutable', 'formfieldprivateid', 'formfieldsort');

		foreach ($queries as $formid => $savedata) {
			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]formfields WHERE formfieldformid=" . (int)$formid);
			if ($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
				continue;
			}

			foreach ($savedata as $data) {
				$data = array_combine($savedataKeys, $data);
				if ($GLOBALS['ISC_CLASS_DB']->InsertQuery('formfields', $data) === false) {
					$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
					return false;
				}
			}
		}

		$GLOBALS['ISC_CLASS_DB']->Query("UPDATE [|PREFIX|]formfields SET formfieldlastmodified=UNIX_TIMESTAMP()");

		/**
		 * Lastly we need to add in the permissions for this
		 */
		$perms = array(
			AUTH_Manage_FormFields,
			AUTH_Add_FormFields,
			AUTH_Edit_FormFields,
			AUTH_Delete_FormFields
		);

		if (!$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT pk_userid FROM [|PREFIX|]users")) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('permissions', "WHERE permpermissionid IN (" . implode(',', $perms) . ")");

		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			foreach ($perms as $permId) {
				$savedata = array(
					'permuserid' => $row['pk_userid'],
					'permpermissionid' => $permId,
				);

				if (!$GLOBALS['ISC_CLASS_DB']->InsertQuery('permissions', $savedata)) {
					$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
					return false;
				}
			}
		}

		return true;
	}

	public function add_variation_combination_product_hash()
	{
		if (!$this->ColumnExists('[|PREFIX|]product_variation_combinations', 'vcproducthash')) {
			$query = "ALTER TABLE [|PREFIX|]product_variation_combinations ADD vcproducthash VARCHAR(32) NOT NULL DEFAULT '' AFTER vcproductid";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_accounting_fields()
	{
		if (!$this->ColumnExists('[|PREFIX|]products', 'prodmyobasset')) {
			$query = "ALTER TABLE [|PREFIX|]products ADD prodmyobasset VARCHAR(20) NOT NULL DEFAULT ''";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]products', 'prodmyobincome')) {
			$query = "ALTER TABLE [|PREFIX|]products ADD prodmyobincome VARCHAR(20) NOT NULL DEFAULT ''";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]products', 'prodmyobexpense')) {
			$query = "ALTER TABLE [|PREFIX|]products ADD prodmyobexpense VARCHAR(20) NOT NULL DEFAULT ''";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		if (!$this->ColumnExists('[|PREFIX|]products', 'prodpeachtreegl')) {
			$query = "ALTER TABLE [|PREFIX|]products ADD prodpeachtreegl VARCHAR(20) NOT NULL DEFAULT ''";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_productid_field()
	{
		if (!$this->ColumnExists('[|PREFIX|]order_configurable_fields', 'productid')) {
			$query = "ALTER TABLE `[|PREFIX|]order_configurable_fields` ADD `productid` INT NOT NULL DEFAULT '0' AFTER `ordprodid` ;";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}


			$query = "UPDATE [|PREFIX|]order_configurable_fields AS o, [|PREFIX|]product_configurable_fields AS p
						SET o.productid = p.fieldprodid
						WHERE o.fieldid = p.productfieldid ;";

			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function prodwarranty_to_text_field()
	{
		$query = "ALTER TABLE `[|PREFIX|]products` CHANGE `prodwarranty` `prodwarranty` TEXT";
		if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		return true;
	}
}