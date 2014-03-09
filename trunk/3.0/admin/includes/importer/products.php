<?php
require_once(dirname(__FILE__) . "/../classes/class.batch.importer.php");

class ISC_BATCH_IMPORTER_PRODUCTS extends ISC_BATCH_IMPORTER_BASE
{
	/**
	 * @var string The type of content we're importing. Should be lower case and correspond with template and language variable names.
	 */
	protected $type = "products";

	protected $_RequiredFields = array(
		"prodname"
	);

	public function __construct()
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('batch.importer');

		/**
		 * @var array Array of importable fields and their friendly names.
		 */
		$this->_ImportFields = array(
			"productid" => GetLang('ProductID'),
			"prodname" => GetLang('ProductName'),
			"category" => GetLang('ImportProductsCategory'),
			"category2" => GetLang('ImportProductsCategory2'),
			"category3" => GetLang('ImportProductsCategory3'),
			"brandname" => GetLang('BrandName'),
			"prodcode" => GetLang('ProductCodeSKU'),
			"proddesc" => GetLang('ProductDescription'),
			"prodprice" => GetLang('Price'),
			"prodcostprice" => GetLang('CostPrice'),
			"prodsaleprice" => GetLang('SalePrice'),
			"prodretailprice" => GetLang('RetailPrice'),
			"prodfixedshippingcost" => GetLang('FixedShippingCost'),
			"prodfreeshipping" => GetLang('FreeShipping'),
			"prodallowpurchases" => GetLang('ProductAllowPurchases'),
			"prodavailability" => GetLang('Availability'),
			"prodvisible" => GetLang('ProductVisible'),
			"prodinvtrack" => GetLang('ProductTrackInventory'),
			"prodcurrentinv" => GetLang('CurrentStockLevel'),
			"prodlowinv" => GetLang('LowStockLevel'),
			"prodwarranty" => GetLang('ProductWarranty'),
			"prodweight" => GetLang('ProductWeight'),
			"prodwidth" => GetLang('ProductWidth'),
			"prodheight" => GetLang('ProductHeight'),
			"proddepth" => GetLang('ProductDepth'),
			"prodpagetitle" => GetLang('PageTitle'),
			"prodsearchkeywords" => GetLang('SearchKeywords'),
			"prodmetakeywords" => GetLang('MetaKeywords'),
			"prodmetadesc" => GetLang('MetaDescription'),
			"prodimagefile" => GetLang('ProductImage'),
			"prodimagedescription" => GetLang('ProductImageDescription'),
			"prodimageisthumb" => GetLang('ProductImageIsThumb'),
			"prodimagesort" => GetLang('ProductImageSort'),
			"prodfile" => GetLang('ProductFile'),
			"prodfiledescription" => GetLang('ProductFileDescription'),
			"prodfilemaxdownloads" => GetLang('ProductFileMaxDownloads'),
			"prodfileexpiresafter" => GetLang('ProductFileExpiresAfter'),
			"prodcondition" => GetLang('ProductCondition'),
			"prodshowcondition" => GetLang('ProductShowCondition'),
			"prodeventdaterequired" => GetLang('ProductEventDateRequired'),
			"prodeventdatefieldname" => GetLang('ProductEventDateName'),
			"prodeventdatelimited" => GetLang('ProductEventDateLimited'),
			"prodeventdatelimitedstartdate" => GetLang('ProductEventDateStartDate'),
			"prodeventdatelimitedenddate" => GetLang('ProductEventDateEndDate'),
			"prodsortorder"	=> GetLang('SortOrder'),
			'tax_class_name' => getLang('ProductTaxClass'),
			'upc'	=> GetLang('ProductUPC'),
		);

		parent::__construct();
	}

	/**
	 * Custom step 1 code specific to product importing. Calls the parent ImportStep1 funciton.
	 */
	protected function _ImportStep1($MsgDesc="", $MsgStatus="")
	{
		if ($MsgDesc != "" && !isset($GLOBALS['Message'])) {
			$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
		}

		if(isset($_POST['AutoCategory']) || $_SERVER['REQUEST_METHOD'] != "POST") {
			$GLOBALS['AutoCategoryChecked'] = "checked=\"checked\"";
		}

		$GLOBALS['ISC_CLASS_ADMIN_CATEGORY'] = GetClass('ISC_ADMIN_CATEGORY');

		$GLOBALS['CategoryOptions'] = $GLOBALS["ISC_CLASS_ADMIN_CATEGORY"]->GetCategoryOptions(array(), "<option %s value='%d'>%s</option>", "selected=\"selected\"", "", false);
		if($GLOBALS['CategoryOptions'] == '') {
			$GLOBALS['ISC_LANG']['ImportProductsCategory'] = GetLang('ImportCreateCategory');
			$GLOBALS['HideCategorySelect'] = "none";
			$GLOBALS['HideCategoryTextbox'] = '';
		}
		else {
			$GLOBALS['HideCategoryTextbox'] = 'none';
		}

		// Set up generic import options
		parent::_ImportStep1();
	}

	/**
	 * Custom step 2 code specific to product importing. Calls the parent ImportStep2 funciton.
	 */
	protected function _ImportStep2($MsgDesc="", $MsgStatus="")
	{
		// Haven't been to this step before, need to parse CSV file
		if(!empty($_POST)) {
			$this->ImportSession['DeleteImages'] = isset($_POST['DeleteImages']);
			$this->ImportSession['DeleteDownloads'] = isset($_POST['DeleteDownloads']);
			$this->ImportSession['IsBulkEdit'] = isset($_POST['BulkEditTemplate']);
			$this->ignoreBlankFields(isset($_POST['IgnoreBlankFields']));

			if ($this->ImportSession['IsBulkEdit']) {
				$_POST['OverrideDuplicates'] = 1;
				$_POST['Headers'] = 1;
			}

			if(!isset($this->ImportSession['CategoryId']) && !isset($this->ImportSession['AutoCategory'])) {
				if(isset($_POST['AutoCategory'])) {
					$this->ImportSession['AutoCategory'] = 1;
					$this->_RequiredFields[] = "category";
					$GLOBALS['CategoryRequired'] = 1;
				}
				else {
					if(!isset($_POST['CategoryId']) && !isset($_POST['CategoryName'])) {
						$this->_ImportStep1(GetLang('ImportInvalidCategory'), MSG_ERROR);
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						exit;
					}
					// Creating a new category
					else if(isset($_POST['CategoryName']) && $_POST['CategoryName'] != "") {
						// Pass on to category creation function
						$_POST['catname'] = $_POST['CategoryName'];
						$_POST['catdesc'] = '';
						$_POST['catpagetitle'] = '';
						$_POST['catmetakeywords'] = '';
						$_POST['catmetadesc'] = '';
						$_POST['catlayoutfile'] = '';
						$_POST['catsort'] = 0;
						$_POST['catparentid'] = 0;
						$_POST['catsearchkeywords'] = '';
						$GLOBALS['ISC_CLASS_ADMIN_CATEGORY'] = GetClass('ISC_ADMIN_CATEGORY');
						$error = $GLOBALS['ISC_CLASS_ADMIN_CATEGORY']->_CommitCategory(0);
						if($error) {
							$this->_ImportStep1($error, MSG_ERROR);
						}
						$_POST['CategoryId'] = $GLOBALS['ISC_CLASS_DB']->LastId();
					}
					// Missing selection
					else if(empty($_POST['CategoryId'])) {
						$this->_ImportStep1(GetLang('ImportInvalidCategory'), MSG_ERROR);
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
						exit;
					}
					$this->ImportSession['CategoryId'] = $_POST['CategoryId'];
				}

			}
		}

		// Set up generic import options

		if ($MsgDesc != "") {
			$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
		}

		parent::_ImportStep2();
	}

	protected function _GetMultiFields()
	{
		if (!$this->ImportSession['IsBulkEdit']) {
			return array();
		}

		// look for images
		$multiFields = array(
			'images' => array(
				'prefix' => 'prodimage',
				'regex' => 'Product Image File',
				'fields' => array(
					"prodimageid" => GetLang('ProductImageID'),
					"prodimagefile" => GetLang('ProductImageFile'),
					"prodimagedescription" => GetLang('ProductImageDescription'),
					"prodimageisthumb" => GetLang('ProductImageIsThumb'),
					"prodimagesort" => GetLang('ProductImageSort')
				)
			),
			'files' => array(
				'prefix' => 'prodfile',
				'regex' => 'Product File',
				'fields' => array(
					"prodfile" => GetLang('ProductFile'),
					"prodfiledescription" => GetLang('ProductFileDescription'),
					"prodfilemaxdownloads" => GetLang('ProductFileMaxDownloads'),
					"prodfileexpiresafter" => GetLang('ProductFileExpiresAfter'),
					"prodimagesort" => GetLang('ProductImageSort')
				)
			)
		);

		return $multiFields;
	}

	/**
	 * Imports an actual product record in to the database.
	 *
	 * @param array Array of record data
	 */
	protected function _ImportRecord($record)
	{
		if(empty($record['prodname'])) {
			$this->ImportSession['Results']['Failures'][] = implode(",", $record['original_record'])." ".GetLang('ImportProductsMissingName');
			return;
		}

		if ($message = strtokenize($_REQUEST, '#')) {
			$this->ImportSession['Results']['Failures'][] = implode(",", $record['original_record'])." ".GetLang(B('UmVhY2hlZFByb2R1Y3RMaW1pdA=='));
			return;
		}

		$record = $this->normalizeInventoryTracking($record);

		$productHash = uniqid('IMPORT', true);
		$productId = 0;
		$hasThumb = false;
		$productFiles = array();
		$productImages = array();
		$existing = null;
		$isOverrideDuplicates = !empty($this->ImportSession['OverrideDuplicates']);
		$dupeCheckWhere = '';

		// Is there an existing product with this product ID ?
		if (!empty($record['productid'])) {
			$query = "SELECT * FROM [|PREFIX|]products WHERE productid = " . (int)$record['productid'];
			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			if($existing = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
				// Overriding existing products, set the product id
				if($isOverrideDuplicates) {
					$productId = $existing['productid'];
					$this->addImportResult('Updates', $record['prodname']);
				}
				else {
					// a product was found, but we're not updating existing record: skip
					$this->addImportResult('Duplicates', $record['prodname']);
					return;
				}

				// merge existing product details with the incoming record
				$record = $this->mergeExistingRecord($record, $existing);
			}
			else {
				// no product for this id was found, skip
				$this->addImportResult('Failures', $record['productid'] . " " . GetLang('ImportProductNotFound'));
				return;
			}

			$dupeCheckWhere  = " AND productid != " . (int)$record['productid'];
		}

		// Check if there is a different product with the same name
		$query = "SELECT * FROM [|PREFIX|]products WHERE prodname = '" . $GLOBALS['ISC_CLASS_DB']->Quote($record['prodname']) . "'" . $dupeCheckWhere;
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		$differentProductWithSameName = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if($differentProductWithSameName) {
			if($existing || !$isOverrideDuplicates) {
				$this->addImportResult('Duplicates', $record['prodname']);
				return;
			}

			$existing = $differentProductWithSameName;
			$productId = $existing['productid'];
			$this->addImportResult('Updates', $record['prodname']);

			$record = $this->mergeExistingRecord($record, $existing);
		}

		// Apply any default data
		$defaults = array(
			'prodprice' => 0,
			'prodcostprice' => 0,
			'prodretailprice' => 0,
			'prodsaleprice' => 0,
			'prodweight' => 0,
			'prodheight' => 0,
			'prodwidth' => 0,
			'proddepth' => 0,
			'prodsearchkeywords' => '',
			'prodsortorder' => 0,
			'prodvisible' => 1,
			'prodfeatured' => 0,
			'prodrelatedproducts' => '-1',
			'prodoptionsrequired' => 0,
			'prodfreeshipping' => 0,
			'prodlayoutfile' => '',
			'prodtags' => '',
			'prodcondition' => 'New',
			'prodshowcondition' => 0,
			'prodallowpurchases' => 1,
			'prodeventdaterequired' => 0,
			'prodeventdatefieldname' => '',
			'prodeventdatelimited' => 0,
			'prodeventdatelimitedtype' => 0,
			'prodeventdatelimitedstartdate' => 0,
			'prodeventdatelimitedenddate' => 0,
			'prodbrandid' => 0,
			'tax_class_name' => '',
			'upc' => '',
			'category' => null,
		);

		$record += $defaults;

		// check validity of price columns
		$priceFields = array(
			'prodprice',
			'prodcostprice',
			'prodsaleprice',
			'prodretailprice'
		);
		foreach ($priceFields as $field) {
			// price was invalid
			if (!IsPrice($record[$field])) {
				if ($productId) {
					// using existing price
					$record[$field] = $existing[$field];
				}
				else {
					$record[$field] = 0;
				}
				$this->addImportResult('Warnings', $record['prodname']." ".GetLang('ImportProductInvalidPrice'));
			}
		}

		// Do we have a product file?
		$productFiles = array();
		if (!$this->ImportSession['IsBulkEdit']) {
			if (!empty($record['prodfile'])) {
				$productFile = $this->_ImportFile($record);
				if ($productFile) {
					$productFiles[] = $productFile;
				}
			}
		}
		else {
			// bulk import files
			for ($x = 1; $x <= $this->ImportSession['MultiFieldCount']['files']; $x++) {
				if (empty($record['prodfile' . $x])) {
					continue;
				}

				$productFile = $this->_ImportFile($record, $x);
				if ($productFile) {
					$productFiles[] = $productFile;
				}
			}
		}


		// Do we have an image?
		$productImages = array();
		if (!$this->ImportSession['IsBulkEdit']) {
			if(!empty($record['prodimagefile'])) {
				$importedImage = $this->_ImportImage($productId, $record);
				if ($importedImage) {
					$productImages[] = $importedImage;
				}
			}
		}
		else {
			// bulk import images
			for ($x = 1; $x <= $this->ImportSession['MultiFieldCount']['images']; $x++) {
				if (empty($record['prodimagefile' . $x])) {
					if (empty($record['prodimageid' . $x])) {
						continue;
					}

					// image file is empty but an ID was supplied, we should delete the image
					if ($productId) {
						try {
							$image = new ISC_PRODUCT_IMAGE($record['prodimageid' . $x]);
							// ensure this image is associated with this product
							if ($image->getProductId() == $productId) {
								$image->delete();
							}
						}
						catch (Exception $ex) {
						}
					}

					continue;
				}

				$importedImage = $this->_ImportImage($productId, $record, $x);
				if ($importedImage) {
					$productImages[] = $importedImage;
				}
			}
		}

		// a category is not required if we have an existing record and ignore blanks is enabled
		$requireCatsField = !(!empty($record['productid']) && $this->ignoreBlankFields());
		$cats = $this->getImportRecordCategories($record);

		if($requireCatsField && empty($cats))
		{
			$this->addImportResult('Failures', implode(",", $record['original_record'])." ".GetLang('ImportProductsMissingCategory'));
			return;
		}

		// If there's a tax class, we need to fetch it now
		$record['tax_class_id'] = 0;
		if(!empty($record['tax_class_name'])) {
			static $taxClassCache = array();
			if(!isset($taxClassCache[$record['tax_class_name']])) {
				$query = "
					SELECT id
					FROM [|PREFIX|]tax_classes
					WHERE name='".$GLOBALS['ISC_CLASS_DB']->quote($record['tax_class_name'])."'
				";
				$taxClassCache[$record['tax_class_name']] = $GLOBALS['ISC_CLASS_DB']->fetchOne($query);
			}

			// Still don't have a matching tax class? Must be new.
			if(!$taxClassCache[$record['tax_class_name']]) {
				$newTaxClass = array(
					'name' => $record['tax_class_name']
				);
				$taxClassCache[$record['tax_class_name']] =
					$GLOBALS['ISC_CLASS_DB']->insertQuery('tax_classes', $newTaxClass);
			}

			$record['tax_class_id'] = $taxClassCache[$record['tax_class_name']];
		}

		// check the condition is valid
		$validConditions = array('new', 'used', 'refurbished');
		if (!isset($record['prodcondition']) || !in_array(isc_strtolower($record['prodcondition']), $validConditions)) {
			$record['prodcondition'] = 'New';
		}

		// Does the brand already exist?
		if(isset($record['brandname']) && $record['brandname'] != '') {
			$query = sprintf("select brandid from [|PREFIX|]brands where brandname='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($record['brandname']));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			if($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$brandId = $row['brandid'];
			}
			// Create new brand
			else {
				// do we have permission to create brands?
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Add_Brands)) {
					$newBrand = array(
						"brandname" => $record['brandname']
					);
					$brandId = $GLOBALS['ISC_CLASS_DB']->InsertQuery("brands", $newBrand);
				}
				else {
					// no brand creation permission, abort this record
					$this->addImportResult('Failures', $record['prodname'] . " " . GetLang('ImportNoPermissionCreateBrand'));
					return;
				}
			}

			$record['prodbrandid'] = $brandId;
		}
		else if(!$this->ignoreBlankFields()){
			$record['prodbrandid'] = 0;
		}

		if (isset($record['prodfile']) && $record['prodfile'] != '') {
			$productType = 2;
		} else if (isset($existing['prodtype']) && isId($existing['prodtype'])) {
			$productType = (int)$existing['prodtype'];
		} else {
			$productType = 1;
		}

		// event date
		$record['prodeventdaterequired'] = $this->StringToYesNoInt($record['prodeventdaterequired']);
		if ($record['prodeventdaterequired']) {
			// we must have an event name
			if (empty($record['prodeventdatefieldname'])) {
				$record['prodeventdaterequired'] = 0;
				$this->addImportResult('Warnings', $record['prodname'] . ' ' . GetLang('ImportNoEventDateName'));
			}
			else {
				$record['prodeventdatelimited'] = $this->StringToYesNoInt($record['prodeventdatelimited']);
				if ($record['prodeventdatelimited']) {
					if (!empty($record['prodeventdatelimitedstartdate'])) {
						$record['prodeventdatelimitedstartdate'] = (int)@ConvertDateToTime($record['prodeventdatelimitedstartdate']);
					}

					if (!empty($record['prodeventdatelimitedenddate'])) {
						$record['prodeventdatelimitedenddate'] = (int)@ConvertDateToTime($record['prodeventdatelimitedenddate']);
					}

					// determine what type of event date it is
					if ($record['prodeventdatelimitedstartdate'] > 0 && $record['prodeventdatelimitedenddate'] == 0) {
						$record['prodeventdatelimitedtype'] = 2; // start date
					}
					elseif ($record['prodeventdatelimitedstartdate'] == 0 && $record['prodeventdatelimitedenddate'] > 0) {
						$record['prodeventdatelimitedtype'] = 3; // end date
					}
					elseif ($record['prodeventdatelimitedenddate'] > $record['prodeventdatelimitedstartdate']) {
						$record['prodeventdatelimitedtype'] = 1; // date range
					}
					else {
						$record['prodeventdatelimited'] = 0;
						$this->addImportResults('Warnings', $record['prodname'] . ' ' . GetLang('ImportEventDateInvalid'));
					}
				}
			}
		}

		// Verify the inventory tracking method is valid.
		if($record['prodinvtrack'] == 2 && !($existing && $existing['prodvariationid'])) {
			$this->addImportResult('Warnings', $record['prodname'] . ' ' . GetLang('ImportProductTrackInventoryNoVariations'));
			$record['prodinvtrack'] = $existing['prodinvtrack'];
		}

		// This is our product
		$productData = array(
			"prodname" => $record['prodname'],
			"prodcode" => @$record['prodcode'],
			"proddesc" => @$record['proddesc'],
			"prodsearchkeywords" => @$record['prodsearchkeywords'],
			"prodtype" => $productType,
			"prodprice" => DefaultPriceFormat($record['prodprice']),
			"prodcostprice" => DefaultPriceFormat($record['prodcostprice']),
			"prodretailprice" => DefaultPriceFormat($record['prodretailprice']),
			"prodsaleprice" => DefaultPriceFormat($record['prodsaleprice']),
			"prodavailability" => @$record['prodavailability'],
			"prodsortorder" => $record['prodsortorder'],
			"prodvisible" => (int)$record['prodvisible'],
			"prodfeatured" => $record['prodfeatured'],
			"prodrelatedproducts" => $record['prodrelatedproducts'],
			"prodinvtrack" => (int)@$record['prodinvtrack'],
			"prodcurrentinv" => (int)@$record['prodcurrentinv'],
			"prodlowinv" => (int)@$record['prodlowinv'],
			"prodoptionsrequired" => $record['prodoptionsrequired'],
			"prodwarranty" => @$record['prodwarranty'],
			"prodheight" => DefaultDimensionFormat(@$record['prodheight']),
			"prodweight" => DefaultDimensionFormat(@$record['prodweight']),
			"prodwidth" => DefaultDimensionFormat(@$record['prodwidth']),
			"proddepth" => DefaultDimensionFormat(@$record['proddepth']),
			"prodfreeshipping" => (int)$record['prodfreeshipping'],
			"prodfixedshippingcost" => DefaultPriceFormat(@$record['prodfixedshippingcost']),
			"prodbrandid" => (int)$record['prodbrandid'],
			"prodcats" => $cats,
			"prodpagetitle" => @$record['prodpagetitle'],
			"prodmetakeywords" => @$record['prodmetakeywords'],
			"prodmetadesc" => @$record['prodmetadesc'],
			"prodlayoutfile" => $record['prodlayoutfile'],
			'prodtags' => $record['prodtags'],
			'prodmyobasset' => '',
			'prodmyobincome' => '',
			'prodmyobexpense' => '',
			'prodpeachtreegl' => '',
			'prodcondition' => $record['prodcondition'],
			'prodshowcondition' => (bool)$record['prodshowcondition'],
			'prodallowpurchases' => (bool)$record['prodallowpurchases'],
			'prodeventdaterequired' => $record['prodeventdaterequired'],
			'prodeventdatefieldname' => $record['prodeventdatefieldname'],
			'prodeventdatelimited' => $record['prodeventdatelimited'],
			'prodeventdatelimitedtype' => $record['prodeventdatelimitedtype'],
			'prodeventdatelimitedstartdate' => $record['prodeventdatelimitedstartdate'],
			'prodeventdatelimitedenddate' => $record['prodeventdatelimitedenddate'],
			'tax_class_id' => $record['tax_class_id'],
			'upc' => $record['upc'],
			'last_import' => $this->ImportSession['StartTime'],
		);

		/**
		 * The variation is part of the product record, so it will have to be attached to the record if this is an
		 * update AND the existing product already has a variation
		 */
		if (isset($existing) && is_array($existing) && isId($existing['prodvariationid'])) {
			$productData['prodvariationid'] = $existing['prodvariationid'];
		}

		$empty = array();

		// Save it
		$err = '';
		if (!$GLOBALS['ISC_CLASS_ADMIN_PRODUCT']->_CommitProduct($productId, $productData, $empty, $empty, $empty, $err, $empty, true)) {
			$this->addImportResult('Failures', $record['prodname'] . " " . GetLang('ImportDatabaseError'));
			return;
		}

		if($productId == 0) {
			$productId = $GLOBALS['NewProductId'];
		}

		// Post process images
		$existingImages = new ISC_PRODUCT_IMAGE_ITERATOR("SELECT * FROM `[|PREFIX|]product_images` WHERE imageprodid = " . (int)$productId);
		$maxSort = count($existingImages);
		if ($this->ImportSession['DeleteImages']) {
			foreach ($existingImages as $existingImage) {
				$existingImage->delete(false);
			}

			$maxSort = 0;
		}

		if(!empty($productImages)) {
			// sort the images
			usort($productImages, array($this, "_compare_images"));

			// update our images with the product id
			foreach ($productImages as $image) {
				$image->setProductId($productId);
				// ensure that an image doesn't have a sort set higher than max, or if no sort specified, then also set it to the highest.
				if ($image->getSort() > $maxSort || $image->getSort() === null) {
					$image->setSort($maxSort);
					$maxSort++;
				}
				$image->saveToDatabase(false);
			}
		}

		// Delete existing files
		if ($this->ImportSession['DeleteDownloads']) {
			$query = "
				SELECT
					*
				FROM
					[|PREFIX|]product_downloads
				WHERE
					productid = " . $productId;
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while ($download = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				// Remove the file from the file system
				@unlink(GetConfig('DownloadDirectory') . "/" . $download['downfile']);

				// Delete from the database
				$GLOBALS['ISC_CLASS_DB']->DeleteQuery('product_downloads', 'WHERE downloadid = ' . $download['downloadid']);
			}
		}

		// Process product files
		if(!empty($productFiles)) {
			foreach($productFiles as $file) {
				$file['productid'] = $productId;
				$GLOBALS['ISC_CLASS_DB']->InsertQuery("product_downloads", $file);
			}
		}

		++$this->ImportSession['Results']['SuccessCount'];
	}

	/**
	 * Normalize the inventory tracking method in an imported
	 * record.
	 *
	 * @param array the imported record
	 *
	 * @return array the import record with a normalized prodinvtrack value
	 */
	private function normalizeInventoryTracking($record)
	{
		if(empty($record['prodinvtrack'])) {
			$record['prodinvtrack'] = 0;
			return $record;
		}

		switch($record['prodinvtrack']) {
			case 'by product':
			case '1':
				$method = 1;
				break;
			case 'by variation':
			case '2':
				$method = 2;
				break;
			case 'none':
			case '0':
				$method = 0;
				break;
			default:
				$error = $record['prodname'] . ' ' . GetLang('ImportProductTrackInventoryInvalid');
				$method = '';
				break;
		}

		if(!empty($error)) {
			$this->addImportResult('Warnings', $error);
		}

		$record['prodinvtrack'] = $method;

		return $record;
	}

	/**
	 * Returns an array of categories to associate with an imported product
	 * record
	 *
	 * @param array the record being imported
	 *
	 * @return array an array of category ids to be associated with this product
	 * record
	 */
	private function getImportRecordCategories($record)
	{
		static $categoryCache;
		static $categoryNameCache;

		if(!is_array($categoryCache)) {
			$categoryCache = array();
		}
		if(!is_array($categoryNameCache)) {
			$categoryNameCache = array();
		}

		$cats = null;

		// Automatically fetching categories based on CSV field
		if(isset($this->ImportSession['AutoCategory'])) {
			// We specified more than one level for the category back in the configuration
			if(isset($record['category1'])) {
				$record['category'] = array($record['category1']);
				if(isset($record['category2']) && $record['category2'] != '') {
					$record['category'][] = $record['category2'];
				}
				if(isset($record['category3']) && $record['category3'] != '') {
					$record['category'][] = $record['category3'];
				}
				$record['category'] = implode("/", $record['category']);
			}


			if(empty($record['category'])) {
				return null;
			}

			// Import the categories for the products too
			$categoryList = explode(";", $record['category']);
			$cats = array();
			foreach($categoryList as $importCategory) {
				$categories = explode("/", $importCategory);
				$parentId = 0;
				$lastCategoryId = 0;
				if(!isset($categoryCache[$importCategory])) {
					foreach($categories as $category) {
						$category = trim($category);
						if($category == '') {
							continue;
						}
						$query = "SELECT catname, categoryid, catparentlist FROM [|PREFIX|]categories WHERE catname='".$GLOBALS['ISC_CLASS_DB']->Quote($category)."' AND catparentid='".$parentId."'";
						$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
						$existingCategory = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
						// category doesn't exist, create the category if we have permission
						if(!$existingCategory) {
							if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Create_Category)) {
								// Create the category
								$_POST['catname'] = $category;
								$_POST['catdesc'] = '';
								$_POST['catsort'] = 0;
								$_POST['catparentid'] = $parentId;
								$_POST['catpagetitle']  = '';
								$_POST['catmetakeywords'] = '';
								$_POST['catmetadesc'] = '';
								$_POST['catlayoutfile'] = '';
								$_POST['catsearchkeywords'] = '';
								$GLOBALS['ISC_CLASS_ADMIN_CATEGORY'] = GetClass('ISC_ADMIN_CATEGORY');
								$error = $GLOBALS['ISC_CLASS_ADMIN_CATEGORY']->_CommitCategory(0, false);
								if($error) {
									return null;
								}
								$lastCategoryId = $GLOBALS['NewCategoryId'];
							}
							else {
								// no category creation permission, abort this record
								$this->ImportSession['Results']['Warnings'][] = $record['prodname'] . " " . GetLang('ImportNoPermissionCreateCategory');
							}
						}
						else {
							$lastCategoryId = $existingCategory['categoryid'];
							$categoryNameCache[$lastCategoryId] = $existingCategory['catname'];
						}
						$parentId = $lastCategoryId;
					}
					// add the category to the cache
					if($lastCategoryId) {
						$categoryCache[$importCategory] = $lastCategoryId;
						$cats[] = $lastCategoryId;
					}
				}
				else {
					$cats[] = $categoryCache[$importCategory];
				}
			}
		}
		// Manually set a category
		elseif (isset($this->ImportSession['CategoryId'])) {
			$cats = array($this->ImportSession['CategoryId']);
		}

		return $cats;
	}

	/**
	 * Set and get the import the 'ignore blank fields' import behaviour.
	 *
	 * @param boolean (optional) the value to set ignore blank fields to
	 *
	 * @return boolean true if blank fields should be ignored.
	 */
	private function ignoreBlankFields($ignoreBlankFields=null)
	{
		if($ignoreBlankFields !== null) {
			$this->ImportSession['IgnoreBlankFields'] = $ignoreBlankFields;
		}

		return !empty($this->ImportSession['IgnoreBlankFields']);
	}

	/**
	 * Merge a new record with an existing record.
	 *
	 * @param array new record
	 * @param array existing record
	 *
	 * @return array merged record
	 */
	private function mergeExistingRecord($newRecord, $existingRecord)
	{
		if($this->ignoreBlankFields()) {
			foreach($newRecord as $field => $value) {
				if($value === "") {
					continue;
				}

				$existingRecord[$field] = $value;
			}

			$newRecord = $existingRecord;
		}
		else {
			$newRecord = $newRecord + $existingRecord;
		}

		return $newRecord;
	}

	private function _compare_images(ISC_PRODUCT_IMAGE $image1, ISC_PRODUCT_IMAGE $image2)
	{
		if ((int)$image1->getSort() < (int)$image2->getSort()) {
			return -1;
		}
		else {
			return 1;
		}
	}

	private function _ImportImage($productId, $record, $index = '')
	{
		$existingImage = false;
		$imageId = 0;

		if (!empty($record['prodimageid' . $index]) && IsId($record['prodimageid' . $index])) {
			$imageId = $record['prodimageid' . $index];
			try {
				$existingImage = new ISC_PRODUCT_IMAGE((int)$imageId);
				if ($existingImage->getProductId() != $productId) {
					// the existing image doesn't belong to this product
					$existingImage = false;
				}
			}
			catch (Exception $ex) {
			}
		}
		$imageFile = $record['prodimagefile' . $index];
		$imageDescription = '';
		if (!empty($record['prodimagedescription' . $index])) {
			$imageDescription = $record['prodimagedescription' . $index];
		}
		$imageIsThumb = false;
		if (!empty($record['prodimageisthumb' . $index])) {
			$imageIsThumb = $record['prodimageisthumb' . $index];
		}
		$imageSort = -1;
		if (isset($record['prodimagesort' . $index]) && $record['prodimagesort' . $index] != '') {
			$imageSort = (int)$record['prodimagesort' . $index];
		}

		$importedImage = false;

		if (!$existingImage || $existingImage->getSourceFilePath() != $imageFile) {
			if (preg_match('#^(?P<scheme>[a-zA-Z0-9\.]+)://#i', $imageFile, $matches)) {
				// code exists in the new product image management classes to handle these imports
				$imageAdmin = new ISC_ADMIN_PRODUCT_IMAGE();

				// the filename is an external URL, import it against the calcualted product hash
				$imageAdmin->importImagesFromUrls(false, array($imageFile), $importImages, $importImageErrors, false, true);

				if (!empty($importImages)) {
					$importedImage = $importImages[0];
				}

				if (!empty($importImageErrors)) {
					// as this import works on one file only and importImagesFromWebUrls creates one error per file, can simply tack on the new error
					$importImageError = $importImageErrors[0];
					if (is_array($importImageError)) {
						$this->ImportSession['Results']['Warnings'][] = $importImageError[1];
					} else {
						$this->ImportSession['Results']['Warnings'][] = $importImageError;
					}
				}
			} else {
				// the filename is a local file
				$importImageFilePath = ISC_BASE_PATH . "/" . GetConfig('ImageDirectory') . "/import/" . $imageFile;
				if (file_exists($importImageFilePath)) {
					try {
						$importedImage = ISC_PRODUCT_IMAGE::importImage($importImageFilePath, basename($importImageFilePath), false, false, false, false);
						$productImages[] = $importedImage;
					} catch (Exception $exception) {
						$this->ImportSession['Results']['Warnings'][] = $exception->getMessage();
					}
				}
				else {
					$this->ImportSession['Results']['Warnings'][] = $record['prodname'].GetLang('ImportProductImageDoesntExist');
				}
			}
		}

		// do we have an existing image?
		if ($existingImage) {
			// assign the imported image file to our existing image
			if ($importedImage) {
				$existingImage->setSourceFilePath($importedImage->getSourceFilePath());
				$existingImage->saveToDatabase(false);
			}

			// use the existing image to set the description, thumb, sort
			$importedImage = $existingImage;
		}

		if ($importedImage) {
			$importedImage->setDescription($imageDescription);
			$importedImage->setIsThumbnail($imageIsThumb);
			if ($imageSort >= 0) {
				$importedImage->setSort($imageSort);
			}
		}

		return $importedImage;
	}

	private function _ImportFile($record, $index = '')
	{
		$productFileName = $record['prodfile' . $index];

		$fileDescription = '';
		if (!empty($record['prodfiledescription' . $index])) {
			$fileDescription = $record['prodfiledescription' . $index];
		}

		$fileMaxDownloads = 0;
		if (!empty($record['prodfilemaxdownloads' . $index])) {
			$fileMaxDownloads = (int)$record['prodfilemaxdownloads' . $index];
		}

		$fileExpiresAfter = 0;
		if (!empty($record['prodfileexpiresafter' . $index])) {
			if (preg_match('/([0-9]+) (days|weeks|months|years)/i', $record['prodfileexpiresafter' . $index], $matches)) {
				$quantity = $matches[1];
				$unit = strtolower($matches[2]);

				switch ($unit) {
					case 'days':
						$fileExpiresAfter = 86400 * $quantity;
						break;
					case 'weeks':
						$fileExpiresAfter = 604800 * $quantity;
						break;
					case 'months':
						$fileExpiresAfter = 2592000 * $quantity; //assumed to be 30 days, as per class.product.php
						break;
					case 'years':
						$fileExpiresAfter = 31536000 * $quantity;
						break;
				}
			}
		}

		$productFile = false;

		// Is this a remote file?
		$downloadDirectory = ISC_BASE_PATH."/".GetConfig('DownloadDirectory');
		if(isc_substr(isc_strtolower($productFileName), 0, 7) == "http://") {
			// Need to fetch the remote file
			$file = PostToRemoteFileAndGetResponse($productFileName);
			if($file) {
				// Place it in our downloads directory
				$randomDir = strtolower(chr(rand(65, 90)));
				if(!is_dir($downloadDirectory.$randomDir)) {
					if(!isc_mkdir($downloadDirectory."/".$randomDir)) {
						$randomDir = '';
					}
				}

				// Generate a random filename
				$fileName = $randomDir . "/" . GenRandFileName(basename($productFileName));
				if(!@file_put_contents($downloadDirectory."/".$fileName, $file)) {
					$this->ImportSession['Results']['Warnings'][] = $record['prodname'].GetLang('ImportProductFileUnableToMove');
				}
				else {
					$productFile = array(
						"prodhash" => "",
						"downfile" => $fileName,
						"downdateadded" => time(),
						"downmaxdownloads" => $fileMaxDownloads,
						"downexpiresafter" => $fileExpiresAfter,
						"downfilesize" => filesize($downloadDirectory."/".$fileName),
						"downname" => basename($productFileName),
						"downdescription" => $fileDescription
					);
				}
			}
			else {
				$this->ImportSession['Results']['Warnings'][] = $record['prodname'].GetLang('ImportProductFileDoesntExist');
			}
		}
		// Treating the file as a local file, in the product_fules/import directory
		else {
			// This file exists, can be imported
			if(file_exists($downloadDirectory."/import/".$productFileName)) {

				// Move it to our images directory
				$randomDir = strtolower(chr(rand(65, 90)));
				if(!is_dir("../".$downloadDirectory."/".$randomDir)) {
					if(!isc_mkdir($downloadDirectory."/".$randomDir)) {
						$randomDir = '';
					}
				}

				// Generate a random filename
				$fileName = $randomDir . "/" . GenRandFileName($productFileName);
				if(!@copy($downloadDirectory."/import/".$productFileName, $downloadDirectory."/".$fileName)) {
					$this->ImportSession['Results']['Warnings'][] = $record['prodname'].GetLang('ImportProductFileUnableToMove');
				}
				else {
					$productFile = array(
						"prodhash" => "",
						"downfile" => $fileName,
						"downdateadded" => time(),
						"downmaxdownloads" => $fileMaxDownloads,
						"downexpiresafter" => $fileExpiresAfter,
						"downfilesize" => filesize($downloadDirectory."/".$fileName),
						"downname" => basename($productFileName),
						"downdescription" => $fileDescription
					);
				}
			}
			else {
				$this->ImportSession['Results']['Warnings'][] = $record['prodname'].GetLang('ImportProductFileDoesntExist');
			}
		}

		return $productFile;
	}

	protected function getViewImportLink()
	{
		$link = $GLOBALS['ISC_CLASS_ADMIN_CUSTOMSEARCH']->getViewUrlBySearchLabel('lastimportproducts');
		return GetLang('ImportProductViewImportedProductsLink', array('link' => $link));
	}

	protected function _GenerateImportSummary()
	{
		// rebuild the nested set data for categories
		$nestedSet = new ISC_NESTEDSET_CATEGORIES();
		$nestedSet->rebuildTree();

		// update cache of root categories
		$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateRootCategories();

		// update cache for category discounts
		$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateCustomerGroupsCategoryDiscounts();

		parent::_GenerateImportSummary();
	}
}
