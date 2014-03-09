<?php
require_once(dirname(__FILE__) . "/../classes/class.batch.importer.php");

class ISC_BATCH_IMPORTER_PRODUCT_VARIATIONS extends ISC_BATCH_IMPORTER_BASE
{
	private $productEntity;

	/**
	 * @var string The type of content we're importing. Should be lower case and correspond with template and language variable names.
	 */
	protected $type = "productvariations";

	protected $_RequiredFields = array();

	public function __construct()
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('batch.importer');

		/**
		 * @var array Array of importable fields and their friendly names.
		 */
		$this->_ImportFields = array(
			"productid" => GetLang('ProductID'),
			"prodcode" => GetLang('ProductCodeSKU'),
			"prodname" => GetLang('ProductName'),
			"prodvarsku" => GetLang('ProductVarSKU'),
			"prodvarprice" => GetLang('ProductVarPrice'),
			"prodvarweight" => GetLang('ProductVarWeight'),
			"prodvarimage" => GetLang('ProductVarImage'),
			"prodvarstock" => GetLang('ProductVarStock'),
			"prodvarlowstock" => GetLang('ProductVarLowStock')
		);

		if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() == 0 && gzte11(ISC_HUGEPRINT)) {
			$this->_ImportFields['prodvendorid'] = GetLang('Vendor');
		}

		$this->productEntity = new ISC_ENTITY_PRODUCT();

		parent::__construct();
	}

	protected function _ImportStep2()
	{
		$this->ImportSession['UpdateExisting'] = isset($_POST['UpdateExisting']);
		$this->ImportSession['DefaultForEmpty'] = isset($_POST['DefaultForEmpty']);
		$this->ImportSession['CreateAllCombos'] = isset($_POST['CreateAllCombos']);

		parent::_ImportStep2();
	}

	protected function _ImportStep3()
	{
		// determine the field to use to identify products
		// prioritise by product id, sku, then name
		$field = "";
		if ($_POST['LinkField']['productid'] >= 0) {
			$field = "productid";
		}
		if ($_POST['LinkField']['prodcode'] >= 0) {
			$field = "prodcode";
		}
		if ($_POST['LinkField']['prodname'] >= 0) {
			$field = "prodname";
		}
		$this->ImportSession['IdentField'] = $field;

		// storage for a list of already imported images and what their new filenames are so that the same file isn't
		// resized multiple times
		$this->ImportSession['ImportedImages'] = array();

		parent::_ImportStep3();
	}

	protected function _ImportRecord($record)
	{
		// the field we have chosen to identify the product
		$prodIdentField = $this->ImportSession['IdentField'];
		$identFieldName = $this->_ImportFields[$prodIdentField];

		// chosen ident field is empty, can't continue
		if (empty($prodIdentField)) {
			$this->addImportResult('Failures', GetLang('NoIdentField', array('identField' => $identFieldName)));
			return;
		}

		// get the product for this row
		$query = "SELECT * FROM [|PREFIX|]products WHERE " . $prodIdentField . " = '" . $GLOBALS['ISC_CLASS_DB']->Quote(trim($record[$prodIdentField])) . "'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (($prod = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) === false) {
			// no prod found? failrow
			$this->addImportResult('Failures', GetLang('AssociatedProductNotFound', array('identField' => $identFieldName, 'identValue' => $record[$prodIdentField])));
			return;
		}
		$productID = $prod['productid'];
		$variationID = $prod['prodvariationid'];

		//---- the fields we'll be updating the product with ----

		// variation code
		if (isset($record['prodvarsku'])) {
			$prodCode = $record['prodvarsku'];

			if (!empty($prodCode) || (empty($prodCode) && $this->ImportSession['DefaultForEmpty'])) {
				$updateFields['vcsku'] = $prodCode;
			}
		}
		elseif (isset($record['prodcode'])) {
			$prodCode = $record['prodcode'];

			if (!empty($prodCode) || (empty($prodCode) && $this->ImportSession['DefaultForEmpty'])) {
				$updateFields['vcsku'] = $prodCode;
			}
		}

		// variation price
		if (isset($record['prodvarprice'])) {
			$varPrice = $record['prodvarprice'];

			if (empty($varPrice) && $this->ImportSession['DefaultForEmpty']) {
				$updateFields['vcprice'] = 0;
				$updateFields['vcpricediff'] = '';
			}
			else {
				// prefixed by a + then it's a price addition
				if (isc_substr($varPrice, 0, 1) == "+") {
					$priceDiff = "add";
				} // price subtraction
				elseif ($varPrice < 0) {
					$priceDiff = "subtract";
				} // fixed price
				else {
					$priceDiff = "fixed";
				}

				$varPrice = abs((float)DefaultPriceFormat($varPrice));
				$updateFields['vcprice'] = $varPrice;
				$updateFields['vcpricediff'] = $priceDiff;
			}
		}

		// variation weight
		if (isset($record['prodvarweight'])) {
			$varWeight = $record['prodvarweight'];

			if (empty($varWeight) && $this->ImportSession['DefaultForEmpty']) {
				$updateFields['vcweight'] = 0;
				$updateFields['vcweightdiff'] = '';
			}
			elseif (!empty($record['prodvarweight'])) {
				// prefixed by a + then it's a weight addition
				if (isc_substr($varWeight, 0, 1) == "+") {
					$weightDiff = "add";
				} // weight subtraction
				elseif ($varWeight < 0) {
					$weightDiff = "subtract";
				} // fixed weight
				else {
					$weightDiff = "fixed";
				}

				$updateFields['vcweight'] = abs((float)$varWeight);
				$updateFields['vcweightdiff'] = $weightDiff;
			}
		}

		// stock level
		if (isset($record['prodvarstock'])) {
			if (empty($record['prodvarstock']) && $this->ImportSession['DefaultForEmpty']) {
				$updateFields['vcstock'] = 0;
			}
			else {
				$updateFields['vcstock'] = $record['prodvarstock'];
			}
		}

		// low stock level
		if (isset($record['prodvarlowstock'])) {
			if (empty($record['prodvarlowstock']) && $this->ImportSession['DefaultForEmpty']) {
				$updateFields['vclowstock'] = 0;
			}
			else {
				$updateFields['vclowstock'] = $record['prodvarlowstock'];
			}
		}

		// enable the option?
		if (isset($record['prodvarenabled'])) {
			if (empty($record['prodvarenabled']) && $this->ImportSession['DefaultForEmpty']) {
				$updateFields['vcenabled'] = 1;
			}
			else {
				$updateFields['vcenabled'] = $this->StringToYesNoInt($record['prodvarenabled']);
			}
		}
		else {
			// enable by default
			$updateFields['vcenabled'] = 1;
		}

		// variation image
		if(!empty($record['prodvarimage'])) {
			// code exists in the new product image management classes to handle these imports
			$imageFile = $record['prodvarimage'];
			$imageAdmin = new ISC_ADMIN_PRODUCT_IMAGE;
			$variationImage = false;

			// check if this image file (either remote or local) has already been processed on a previous variation, if
			// so, simply re-use those values instead of re-downloading / re-processing
			if (isset($this->ImportSession['ImportedImages'][$imageFile])) {
				$importedImage = $this->ImportSession['ImportedImages'][$imageFile];
				if (is_array($importedImage)) {
					$updateFields = array_merge($updateFields, $importedImage);
				}
			} else {
				$this->ImportSession['ImportedImages'][$imageFile] = false;

				if (preg_match('#^(?P<scheme>[a-zA-Z0-9\.]+)://#i', $imageFile, $matches)) {
					// the filename is an external URL, import it against the calcualted product hash
					$imageAdmin->importImagesFromUrls(false, array($imageFile), $importImages, $importImageErrors, false);

					if (!empty($importImages)) {
						$variationImage = $importImages[0];
					}

					if (!empty($importImageErrors)) {
						// as this import works on one file only and importImagesFromWebUrls creates one error per file, can simply tack on the new error
						$importImageError = $importImageErrors[0];
						if (is_array($importImageError)) {
							$this->addImportResult('Warnings', $importImageError[1]);
						} else {
							$this->addImportResult('Warnings', $importImageError);
						}
					}

				} else {
					// the filename is a local file
					$importImageFilePath = ISC_BASE_PATH . "/" . GetConfig('ImageDirectory') . "/import/" . $imageFile;

					try {
						$variationImage = ISC_PRODUCT_IMAGE::importImage($importImageFilePath, basename($importImageFilePath), false, false, false, false);
					} catch (ISC_PRODUCT_IMAGE_SOURCEFILEDOESNTEXIST_EXCEPTION $exception) {
						// exception message may contain server path; present filtered message and log the original
						$this->addImportResult('Warnings', GetLang('ProductImageFileDoesNotExist'));
						trigger_error($exception->getMessage(), E_WARNING);
					} catch (ISC_PRODUCT_IMAGE_IMPORT_CANTCREATEDIR_EXCEPTION $exception) {
						// exception message may contain server path; present filtered message and log the original
						$this->addImportResult('Warnings', GetLang('ImportProductImageFilePermissionIssue'));
						trigger_error($exception->getMessage(), E_WARNING);
					} catch (ISC_PRODUCT_IMAGE_IMPORT_CANTMOVEFILE_EXCEPTION $exception) {
						// exception message may contain server path; present filtered message and log the original
						$this->addImportResult('Warnings', GetLang('ImportProductImageFilePermissionIssue'));
						trigger_error($exception->getMessage(), E_WARNING);
					} catch (Exception $exception) {
						// other exceptions should be ok to present
						$this->addImportResult('Warnings', $exception->getMessage());
					}
				}

				if ($variationImage !== false) {
					try {
						$importedImage = array(
							'vcimage' => $variationImage->getSourceFilePath(),
							'vcimagezoom' => $variationImage->getResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_ZOOM, true, false),
							'vcimagestd' => $variationImage->getResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_STANDARD, true, false),
							'vcimagethumb' => $variationImage->getResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL, true, false),
						);
						$updateFields = array_merge($updateFields, $importedImage);
						$this->ImportSession['ImportedImages'][$imageFile] = $importedImage;
					} catch (ISC_PRODUCT_IMAGE_SOURCEFILEDOESNTEXIST_EXCEPTION $exception) {
						// exception message may contain server path; present filtered message and log the original
						$this->addImportResult('Warnings', GetLang('ProductImageFileDoesNotExist'));
						trigger_error($exception->getMessage(), E_WARNING);
					} catch (ISC_PRODUCT_IMAGE_IMPORT_CANTCREATEDIR_EXCEPTION $exception) {
						// exception message may contain server path; present filtered message and log the original
						$this->addImportResult('Warnings', GetLang('ImportProductImageFilePermissionIssue'));
						trigger_error($exception->getMessage(), E_WARNING);
					} catch (ISC_PRODUCT_IMAGE_IMPORT_CANTMOVEFILE_EXCEPTION $exception) {
						// exception message may contain server path; present filtered message and log the original
						$this->addImportResult('Warnings', GetLang('ImportProductImageFilePermissionIssue'));
						trigger_error($exception->getMessage(), E_WARNING);
					} catch (Exception $exception) {
						// other exceptions should be ok to present
						$this->addImportResult('Warnings', $exception->getMessage());
					}
				}
			}
		}


		// get the index of the last matched field...we assume that all the remaining fields are variations
		$lastindex = 0;
		foreach ($this->ImportSession['FieldList'] as $field => $index) {
			if ($index > $lastindex) {
				$lastindex = $index;
			}
		}

		$variationStartIndex = $lastindex + 1;

		// get the variation fields
		$variationFields = array_slice($record['original_record'], $variationStartIndex);

		// split the variation fields into key => value pairs
		$variationData = array();
		foreach ($variationFields as $field) {
			$varField = explode(":", $field, 2);
			// ensure we have a key and value...otherwise bad field
			if (count($varField) != 2) {
				$this->addImportResult('Failures', GetLang('CantExtractData', array('dataField' => $field)));
				return;
			}

			$varName = trim($varField[0]);
			$varValue = trim($varField[1]);
			$variationData[$varName] = $varValue;
		}

		// ensure we actually have variation data
		if (empty($variationData)) {
			// generate a failure
			$this->addImportResult('Failures', GetLang('NoVariationData', array('rowNum' => $this->ImportSession['DoneCount'] + 1)));
			return;
		}

		// are we choosing to update an existing variation combination or replacing with a new variation?
		// make sure this isn't a variation we've created this session
		if ($this->ImportSession['UpdateExisting'] && $variationID > 0 && !isset($this->ImportSession['NewVariations'][$productID])){
			// find the variation options so we can find the combination
			$query = "
			SELECT
				voptionid,
				voname,
				vovalue
			FROM
				[|PREFIX|]product_variation_options
			WHERE
				vovariationid = " . $variationID . " AND (";

			$where = "";
			foreach ($variationData as $varName => $varValue) {
				if ($where) {
					$where .= " OR ";
				}
				$where .= "
					(
						voname = '" . $GLOBALS['ISC_CLASS_DB']->Quote($varName) . "' AND
						vovalue = '" . $GLOBALS['ISC_CLASS_DB']->Quote($varValue) . "'
					)
				";
			}

			$query .= $where . ") ORDER BY vooptionsort, vovaluesort";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$optionList = array();
			$notFoundOptions = $variationData;
			// get the option id's
			if ($GLOBALS['ISC_CLASS_DB']->CountResult($result)) {
				while ($optionRow = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$optionList[] = $optionRow['voptionid'];
					unset($notFoundOptions[$optionRow['voname']]);
				}
			}

			$updatedName = false;

			// create any remaining options
			if (!empty($notFoundOptions)) {
				foreach ($notFoundOptions as $varName => $varValue) {
					// find whether it's the option name or value that doesn't exist
					$query = "
						SELECT
							COUNT(*) AS valuecount,
							vooptionsort,
							voname
						FROM
							[|PREFIX|]product_variation_options
						WHERE
							vovariationid = " . $variationID . " AND
							voname = '" . $GLOBALS['ISC_CLASS_DB']->Quote($varName) . "'
						GROUP BY
							voname
					";
					$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
					// name exists, just create a new value
					if ($option = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
						$newOption = array(
							'vovariationid' => $variationID,
							'voname'		=> $option['voname'],
							'vovalue'		=> $varValue,
							'vooptionsort'	=> $option['vooptionsort'],
							'vovaluesort'	=> $option['valuecount'] + 1
						);

						$optionID = $GLOBALS['ISC_CLASS_DB']->InsertQuery('product_variation_options', $newOption);
						array_splice($optionList, $option['vooptionsort'] - 1, 0, $optionID);

						// we have a new value, we have to create new combinations for each of the rows using the existing data

						// get existing combinations but exclude the option name that we're on
						if ($this->ImportSession['CreateAllCombos']) {
							$combinations = GetVariationCombinations($productID, $varName);
							foreach ($combinations as $combination) {
								$newCombination = $combination;
								// insert the option at correct position
								array_splice($newCombination, $option['vooptionsort'] - 1, 0, $optionID);
								$newOptionList = implode(',', $newCombination);

								// create combination
								$newCombo = array(
									'vcproductid'	=> $productID,
									'vcvariationid'	=> $variationID,
									'vcoptionids'	=> $newOptionList,
									'vcenabled'		=> 1
								);
								$GLOBALS['ISC_CLASS_DB']->InsertQuery('product_variation_combinations', $newCombo);
							}
						}
					}
					else {
						// name not found, create it with the option

						// get total option names
						$query = "SELECT COUNT(DISTINCT voname) AS optioncount FROM [|PREFIX|]product_variation_options WHERE vovariationid = " . $variationID;
						$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
						$optioncount = $GLOBALS['ISC_CLASS_DB']->FetchOne($result, 'optioncount');

						$newOption = array(
							'vovariationid' => $variationID,
							'voname'		=> $varName,
							'vovalue'		=> $varValue,
							'vooptionsort'	=> $optioncount + 1,
							'vovaluesort'	=> 1
						);

						$optionID = $GLOBALS['ISC_CLASS_DB']->InsertQuery('product_variation_options', $newOption);
						array_splice($optionList, $optioncount, 0, $optionID);

						// we have a new option name, so append the new option id to existing combinations
						$query = "
							UPDATE
								[|PREFIX|]product_variation_combinations
							SET
								vcoptionids = CONCAT(vcoptionids, '," . $optionID . "')
							WHERE
								vcvariationid = " . $variationID;

						$GLOBALS['ISC_CLASS_DB']->Query($query);

						// update the variation option count
						$query = "UPDATE [|PREFIX|]product_variations SET vnumoptions = vnumoptions + 1 WHERE variationid = " . $variationID;
						$GLOBALS['ISC_CLASS_DB']->Query($query);
					}
				}
			}

			$optionString = implode(",", $optionList);

			// attempt to find existing combination again using list of options
			$query = "
				SELECT
					combinationid
				FROM
					[|PREFIX|]product_variation_combinations
				WHERE
					vcproductid = " . $productID . " AND
					vcvariationid = " . $variationID . " AND
					vcoptionids = '" . $optionString . "'
			";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			// update the combination
			if ($comboID = $GLOBALS['ISC_CLASS_DB']->FetchOne($result, 'combinationid')) {
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('product_variation_combinations', $updateFields, 'combinationid = ' . $comboID);

				$this->addImportResult('Updates', $prod['prodname']);
			}
			else {
				// couldn't update an existing combo, create a new one

				$newCombo = array(
					'vcproductid'	=> $productID,
					'vcvariationid'	=> $variationID,
					'vcoptionids'	=> $optionString
				);

				$newCombo = $newCombo + $updateFields;

				$GLOBALS['ISC_CLASS_DB']->InsertQuery('product_variation_combinations', $newCombo);

				$this->ImportSession['Results']['SuccessCount']++;
			}
		}
		else {
			// create a new variation for this product

			// have we already created a variation for this product in this import session?
			if (isset($this->ImportSession['NewVariations'][$productID])) {
				// we only need to create our new options and the combinations

				// find any options previously created
				$thisVar = $this->ImportSession['NewVariations'][$productID];
				$thisOptions = array();
				$thisOptionsL = array();
				foreach ($variationData as $varName => $varValue) {
					if (isset($thisVar[isc_strtolower($varName)][isc_strtolower($varValue)])) {
						$thisOptions[$varName] = $thisVar[isc_strtolower($varName)][isc_strtolower($varValue)];
						$thisOptionsL[isc_strtolower($varName)] = $thisVar[isc_strtolower($varName)][isc_strtolower($varValue)];
					}
				}

				// create any remaining uncreated options
				$remainingOptions = array_diff_key($variationData, $thisOptions);
				if (!empty($remainingOptions)) {
					foreach ($remainingOptions as $varName => $varValue) {
						$lvarName = isc_strtolower($varName);
						// get the option and value sort numbers

						// does this option name exist, but just not the value?
						if (isset($thisVar[$lvarName])) {
							$keyIndex = array_search($lvarName, array_keys($thisVar));

							$optionSort = $keyIndex + 1;
							$valueSort = count($thisVar[$lvarName]) + 1;
						}
						else {
							$valueSort = 1;
							$optionSort = count($thisVar) + 1;
						}

						$insertOption = array(
							'vovariationid'	=> $variationID,
							'voname' 		=> $varName,
							'vovalue'		=> $varValue,
							'vooptionsort'	=> $optionSort,
							'vovaluesort'	=> $valueSort
						);

						$optionID = $GLOBALS['ISC_CLASS_DB']->InsertQuery('product_variation_options', $insertOption);
						// add this new option to the list
						$thisVar[$lvarName][isc_strtolower($varValue)] = $optionID;
						$thisOptionsL[$lvarName] = $optionID;

						// is it a new option name?
						if (!$thisVar[isc_strtolower($varName)]) {
							// we have a new option name, so append the new option id to existing combinations
							$query = "
								UPDATE
									[|PREFIX|]product_variation_combinations
								SET
									vcoptionids = CONCAT(vcoptionids, '," . $optionID . "')
								WHERE
									vcvariationid = " . $variationID;

							$GLOBALS['ISC_CLASS_DB']->Query($query);
						}
					}
				}

				// store options back in session
				$this->ImportSession['NewVariations'][$productID] = $thisVar;

				// get the option ids for this combination. they must be in the order that the option names were created.
				$comboRows = array(array());
				foreach ($thisVar as $varName => $varData) {
					// is there an option that may have already been created but is missing for this record?
					if (isset($thisOptionsL[$varName])) {
						foreach ($comboRows as &$combo) {
							$combo[] = $thisOptionsL[$varName];
						}
					}
					else {
						$newRows = array();
						// missing option, iterate through all values for that option and create combinations
						foreach ($comboRows as $combo) {
							foreach ($varData as $varValue => $optionID) {
								$newRow = $combo;
								$newRow[] = $optionID;
								$newRows[] = $newRow;
							}
						}
						$comboRows = $newRows;
					}
				}

				// insert all our combinations
				foreach ($comboRows as $thisCombo) {
					$optionString = implode(",", $thisCombo);

					// now we can finally create the combination
					$newCombo = array(
						'vcproductid'	=> $prod['productid'],
						'vcvariationid'	=> $variationID,
						'vcoptionids'	=> $optionString
					);

					$newCombo = $newCombo + $updateFields;

					$GLOBALS['ISC_CLASS_DB']->InsertQuery('product_variation_combinations', $newCombo);
				}

				$this->ImportSession['Results']['SuccessCount']++;
			}
			else {
				// do we have an existing combinations for this product? we should delete any combinations for that first
				if ($variationID) {
					$GLOBALS['ISC_CLASS_DB']->DeleteQuery('product_variation_combinations', 'WHERE vcproductid = ' . $productID);
				}

				// name of our new variation .. check if it already exists
				$variationName = $prod['prodname'] . " Variations " . date('dmy');
				$query = "SELECT variationid FROM [|PREFIX|]product_variations WHERE vname = '" . $GLOBALS['ISC_CLASS_DB']->Quote($variationName) . "'";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				if ($varRow = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					// delete the old variation
					$GLOBALS['ISC_CLASS_DB']->DeleteQuery('product_variations', 'WHERE variationid = ' . $varRow['variationid']);
					$GLOBALS['ISC_CLASS_DB']->DeleteQuery('product_variation_options', 'WHERE vovariationid = ' . $varRow['variationid']);
					$GLOBALS['ISC_CLASS_DB']->DeleteQuery('product_variation_combinations', 'WHERE vcvariationid = ' . $varRow['variationid']);

					// update products that use this variation
					$updateProd = array(
						'prodvariationid' => 0
					);
					$GLOBALS['ISC_CLASS_DB']->UpdateQuery('products', $updateProd, 'prodvariationid = ' . $varRow['variationid']);
				}


				// create our new variation first
				$newVariation = array(
					'vname'		=> $variationName,
					'vvendorid' => $prod['prodvendorid']
				);

				$variationID = $GLOBALS['ISC_CLASS_DB']->InsertQuery('product_variations', $newVariation);

				$this->ImportSession['NewVariationIDs'][$productID] = $variationID;

				// update our product with the variation ID
				$updateProd = array(
					'prodvariationid' => $variationID
				);
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('products', $updateProd, 'productid = ' . $productID);

				$thisVar = array();
				$options = array();

				// now to create the options
				$optionCount = 0;
				foreach ($variationData as $varName => $varValue) {
					$newOption = array(
						'vovariationid'	=> $variationID,
						'voname'		=> $varName,
						'vovalue'		=> $varValue,
						'vooptionsort'	=> ++$optionCount,
						'vovaluesort'	=> 1
					);

					$optionID = $GLOBALS['ISC_CLASS_DB']->InsertQuery('product_variation_options', $newOption);

					$thisVar[isc_strtolower($varName)][isc_strtolower($varValue)] = $optionID;
					$options[] = $optionID;
				}

				$this->ImportSession['NewVariations'][$productID] = $thisVar;

				// create the combination
				$optionString = implode(",", $options);

				$newCombo = array(
					'vcproductid'	=> $productID,
					'vcvariationid'	=> $variationID,
					'vcoptionids'	=> $optionString
				);

				$newCombo = $newCombo + $updateFields;

				$GLOBALS['ISC_CLASS_DB']->InsertQuery('product_variation_combinations', $newCombo);

				$this->ImportSession['Results']['SuccessCount']++;
			}
		}

		// a stock or low stock is supplied, enable inventory tracking for the product
		if (!empty($record['prodvarstock']) || !empty($record['prodvarlowstock'])) {
			$updateProd = array(
				'prodinvtrack' => 2
			);

			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('products', $updateProd, 'productid = ' . $productID);
		}

		/**
		 * This is a bit hackish but we need to update the product last modified time WITHOUT using the
		 * product entity class (shock horror). This is because we have nothing else to update it with
		 */
		$savedata = array(
			"prodlastmodified" => time()
		);

		$GLOBALS["ISC_CLASS_DB"]->UpdateQuery("products", $savedata, "productid = " . $productID);
	}

	protected function _GenerateImportSummary()
	{
		// we still need to create other possible combinations
		if (isset($this->ImportSession['NewVariations']) && count($this->ImportSession['NewVariations'])) {
			foreach ($this->ImportSession['NewVariations'] as $productID => $varOptions) {
				$variationID = $this->ImportSession['NewVariationIDs'][$productID];

				// update the number of options for the variation
				$updateVar = array(
					'vnumoptions' => count($varOptions)
				);
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('product_variations', $updateVar, 'variationid = ' . $variationID);

				if ($this->ImportSession['CreateAllCombos']) {
					// get all the option id's grouped by the option name
					$variationID = 0;
					$combinations = GetVariationCombinations($productID);

					foreach ($combinations as $combination) {
						$optionList = implode(',', $combination);

						// check if this combo exists
						$query = "SELECT * FROM [|PREFIX|]product_variation_combinations WHERE vcoptionids = '" . $optionList . "' AND vcproductid = " . $productID;
						$resc = $GLOBALS['ISC_CLASS_DB']->Query($query);
						if (!$GLOBALS['ISC_CLASS_DB']->CountResult($resc)) {
							// create a new combo
							$newCombo = array(
								'vcproductid' 	=> $productID,
								'vcvariationid'	=> $variationID,
								'vcenabled'		=> 1,
								'vcoptionids'	=> $optionList
							);

							$GLOBALS['ISC_CLASS_DB']->InsertQuery('product_variation_combinations', $newCombo);
						}
					}
				}
			}
		}

		parent::_GenerateImportSummary();
	}
}

/**
* Returns an array containing all possible variation combinations
*
* @param int The product ID to get combinations for
* @param string Optional option name to exclude in the combinations
* @return array Array of all combinations
*/
function GetVariationCombinations($productID, $excludeOption = '')
{
	$where = "";
	if ($excludeOption) {
		$where = " AND voname != '" . $GLOBALS['ISC_CLASS_DB']->Quote($excludeOption) . "' ";
	}

	$query = "
		SELECT
			voname,
			GROUP_CONCAT(voptionid ORDER BY voptionid) AS optionids
		FROM
			[|PREFIX|]product_variation_options
			INNER JOIN [|PREFIX|]products p ON vovariationid = prodvariationid
		WHERE
			p.productid = " . $productID . "
			" . $where . "
		GROUP BY
			voname
	";
	$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
	$optionArray = array();
	while ($optionRow = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
		$optionArray[$optionRow['voname']] = explode(',', $optionRow['optionids']);
	}

	// calculate all the combinations
	return Interspire_Array::generateCartesianProduct($optionArray, true);
}
