<?php

class ISC_ADMIN_PRODUCT_VARIATIONS extends ISC_ADMIN_BASE {
	/**
	 * Whether or not to render the layout.
	 *
	 * @var bool
	 */
	protected $renderLayout = true;

	public function __construct()
	{
		parent::__construct();
		$this->engine->LoadLangFile('products');
	}

	public function HandleToDo($todo)
	{
		if (!$this->auth->HasPermission(AUTH_Manage_Variations)) {
			$this->engine->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
		}

		GetLib('class.json');

		// todo method name
		$todo = $todo . 'Action';

		$render = null;

		if (method_exists($this, $todo)) {
			$render = $this->$todo();
		}

		// process template routing
		if ($render && is_string($render)) {
			if ($this->renderLayout) {
				$this->engine->printHeader();
			}

			$this->template->display($render);

			if ($this->renderLayout) {
				$this->engine->printFooter();
			}
		}
	}

	/**
	* AddVariationStep1
	* The form to add a product variation with options to the store
	*
	* @return Void
	*/
	public function addProductVariationAction($MsgDesc = "", $MsgStatus = "", $PreservePost=false)
	{
		if($MsgDesc != "") {
			$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
		}

		$GLOBALS['FormAction'] = "addProductVariation2";
		$GLOBALS['Title'] = GetLang("AddProductVariation");
		$GLOBALS['SaveAndAddAnother'] = GetLang("SaveAndAddAnother");

		if (!array_key_exists('variationId', $_POST)) {
			$GLOBALS['VariationName'] = isc_html_escape(GetLang('ProductVariationTestDataName'));
		} else if (array_key_exists('vname', $_POST)) {
			$GLOBALS['VariationName'] = isc_html_escape($_POST['vname']);
		}

		if (array_key_exists('variationId', $_POST)) {
			$GLOBALS['HideVariationTestDataWarning'] = 'none';
		}

		if(!gzte11(ISC_HUGEPRINT)) {
			$GLOBALS['HideVendorOption'] = 'display: none';
		}
		else {
			$vendorData = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendor();
			if(isset($vendorData['vendorid'])) {
				$GLOBALS['HideVendorSelect'] = 'display: none';
				$GLOBALS['CurrentVendor'] = isc_html_escape($vendorData['vendorname']);
			}
			else {
				$GLOBALS['HideVendorLabel'] = 'display: none';
				$GLOBALS['VendorList'] = $this->BuildVendorSelect();
			}
		}

		/**
		 * Display the test data only when they have entered the variation admin for the first time
		 */
		if (!array_key_exists('variationId', $_POST)) {
			$variationData = $this->GetVariationTestData();
		} else {
			$variationData = $this->GetVariationData(0);
		}

		$GLOBALS['Variations'] = $this->BuildVariationCreate($variationData);

		$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Products') => "index.php?ToDo=viewProducts", GetLang("ProductVariations") => "index.php?ToDo=viewProductVariations", GetLang('AddProductVariation') => "index.php?ToDo=addProductVariation");

		return 'products.variation.form.tpl';
	}

	/**
	* AddVariationStep2
	* Save the details of the variation to the database
	*
	* @return Void
	*/
	public function addProductVariation2Action()
	{
		$data = $this->GetVariationData(0);

		/**
		 * Validate our data
		 */
		if (!$this->ValidateVariationData($data, $error)) {
			return $this->addProductVariationAction($error, MSG_ERROR, true);
		}

		/**
		 * Add our new variation record
		 */
		$variationId = $this->SaveVariationData($data);

		/**
		 * Did we get any errors?
		 */
		if (!isId($variationId)) {
			return $this->addProductVariationAction(sprintf(GetLang("ErrorWhenAddingVariation"), $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg()), MSG_ERROR, true);
		}

		if (isset($_POST['addanother'])) {
			$_POST = array('variationId' => '');
			return $this->addProductVariationAction(GetLang("VariationAddedSuccessfully"), MSG_SUCCESS);
		} else {
			return $this->viewProductVariationsAction(GetLang("VariationAddedSuccessfully"), MSG_SUCCESS);
		}
	}

	/**
	* EditVariationStep1
	* The form to edit a product variation with options to the store
	*
	* @return Void
	*/
	public function editProductVariationAction($MsgDesc = "", $MsgStatus = "", $PreservePost=false)
	{
		if($MsgDesc != "") {
			$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
		}

		$variationId = null;
		if (isset($_GET['variationId'])) {
			$variationId = (int)$_GET['variationId'];
		}

		if (!$this->auth->HasPermission(AUTH_Manage_Products)) {
			$this->engine->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
		}

		/**
		 * Get our variation data. If we couldn't get it then display an error
		 */
		if ($PreservePost) {
			$variationData = $this->GetVariationData(0);
		} else {
			$variationData = $this->GetVariationData($variationId);
		}

		if (!isId($variationId) || !$variationData) {
			return $this->viewProductVariationsAction(GetLang('ProductVariationErrorDoesNotExists'), MSG_ERROR);
		}

		/**
		 * We need to have a list of all the variation options that are in use by products
		 */
		$affected = array();
		$result = $GLOBALS['ISC_CLASS_DB']->Query("
			SELECT
				voptionid
			FROM
				[|PREFIX|]product_variation_options
			WHERE
				vovariationid = " . (int)$variationId);
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$affected[] = $row['voptionid'];
		}

		$affected = array_unique($affected);
		$GLOBALS['AffectedVariations'] = implode(',', $affected);

		$GLOBALS['FormAction'] = "editProductVariation2";
		$GLOBALS['Title'] = GetLang("EditProductVariation");
		$GLOBALS['VariationName'] = $variationData['name'];
		$GLOBALS['VariationId'] = $variationData['id'];
		$GLOBALS['SaveAndAddAnother'] = GetLang("SaveAndContinueEditing");
		$GLOBALS['HideVariationTestDataWarning'] = 'none';

		if(!gzte11(ISC_HUGEPRINT)) {
			$GLOBALS['HideVendorOption'] = 'display: none';
		}
		else {
			$vendorData = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendor();
			if(isset($vendorData['vendorid'])) {
				$GLOBALS['HideVendorSelect'] = 'display: none';
				$GLOBALS['CurrentVendor'] = isc_html_escape($vendorData['vendorname']);
			}
			else {
				$GLOBALS['HideVendorLabel'] = 'display: none';
				$GLOBALS['VendorList'] = $this->BuildVendorSelect();
			}
		}

		$GLOBALS['Variations'] = $this->BuildVariationCreate($variationData);

		$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Products') => "index.php?ToDo=viewProducts", GetLang("ProductVariations") => "index.php?ToDo=viewProductVariations", GetLang('EditProductVariation') => "index.php?ToDo=editProductVariation");

		return 'products.variation.form.tpl';
	}

	/**
	* EditVariationStep2
	* Save the details of the variation to the database
	*
	* @return Void
	*/
	public function editProductVariation2Action()
	{
		$variationId = null;
		if (isset($_POST['variationId'])) {
			$variationId = (int)$_POST['variationId'];
		}

		if (!$this->auth->HasPermission(AUTH_Manage_Products)) {
			$this->engine->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
		}

		/**
		 * Get our variation data. If we couldn't get it then display an error
		 */
		$variationData = $this->GetVariationData(0);

		if (!isId($variationId) || !$variationData) {
			return $this->viewProductVariationsAction(GetLang('ProductVariationErrorDoesNotExists'), MSG_ERROR);
		}

		/**
		 * Validate our data
		 */
		if (!$this->ValidateVariationData($variationData, $error)) {
			$_GET['variationId'] = $variationId;
			return $this->editProductVariationAction($error, MSG_ERROR, true);
		}

		/**
		 * Add our new variation record
		 */
		$rtn = $this->SaveVariationData($variationData);

		/**
		 * Did we get any errors?
		 */
		if (!$rtn) {
			$_GET['variationId'] = $variationId;
			return $this->editProductVariationAction(sprintf(GetLang("ErrorWhenUpdatingVariation"), $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg()), MSG_ERROR, true);
		}

		if (isset($_POST['addanother'])) {
			$_GET['variationId'] = $variationId;
			return $this->editProductVariationAction(GetLang("VariationUpdatedSuccessfully"), MSG_SUCCESS);
		} else {
			return $this->viewProductVariationsAction(GetLang("VariationUpdatedSuccessfully"), MSG_SUCCESS);
		}
	}


	/**
	 * Save the variation information
	 *
	 * Method will save the variation information to the database. Will look in the $data array for the variation ID to see if it is an update
	 * or a new record
	 *
	 * @access private
	 * @param array $data The variation information to save
	 * @return mixed Either the new variation ID if successfully added, TRUE if successfully updated, FALSE otherwise
	 */
	private function SaveVariationData($data)
	{
		/**
		 * Do we have any data to insert/update?
		 */
		if (!is_array($data) || empty($data)) {
			return false;
		}

		$variation = null;

		if (isId($data['id'])) {
			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]product_variations WHERE variationid = " . (int)$data['id']);
			$variation = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		}

		/**
		 * Check to see if we were given a proper variation ID
		 */
		if (isId($data['id']) && !is_array($variation)) {
			return false;
		}

		/**
		 * Start our transaction. If that dies then bail
		 */
		if ($GLOBALS["ISC_CLASS_DB"]->StartTransaction() === false) {
			return false;
		}

		$savedata = array(
					'vname' => $data['name'],
					'vnumoptions' => count($data['options']),
		);

		/**
		 * Assign our vendor ID
		 */
		if (gzte11(ISC_HUGEPRINT)) {
			// User is assigned to a vendor so any variations they create must be too
			if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
				$savedata['vvendorid'] = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId();
			} else if(isId($data['vendor'])) {
				$savedata['vvendorid'] = (int)$data['vendor'];
			}
		}

		/**
		 * Add/Update the variation record
		 */
		if (!isId($data['id'])) {
			$rtn = $GLOBALS['ISC_CLASS_DB']->InsertQuery('product_variations', $savedata);
			$data['id'] = $rtn;
		} else {
			$rtn = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('product_variations', $savedata, "variationid=" . (int)$data['id']);
		}

		if ($rtn === false) {
			return false;
		}

		/**
		 * Now to add/edit the options. These options are in order.
		 */
		$optionPos = 0;
		$deleteCombo = false;
		$groupedValues = array();
		$newValues = array();
		$valuesForNewOption = array();

		foreach ($data['options'] as $option) {

			$optionPos++;
			$valuePos = 0;
			$addValues = array();
			$editValues = array();
			$origOptionName = '';
			$newOptionName = '';

			$isNewOption = true;

			foreach ($option['values'] as $value) {
				$valuePos ++;
				$savedata = array(
					'vovariationid' => (int)$data['id'],
					'voname' => $option['name'],
					'vovalue' => $value['name'],
					'vooptionsort' => (int)$optionPos,
					'vovaluesort' => (int)$valuePos,
				);

				/**
				 * Are we updating or adding
				 */
				if (!isset($value['valueid']) || !isId($value['valueid'])) {
					$rtn = $GLOBALS['ISC_CLASS_DB']->InsertQuery('product_variation_options', $savedata);
					$addValues[] = (int)$rtn;
					$newValues[$option['name']][] = (int)$rtn;
				} else {
					$isNewOption = false;

					/**
					 * If we are updating then we need to make sure that option name is the same for all the values within that option
					 */
					if ($origOptionName == '') {
						$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT voname FROM [|PREFIX|]product_variation_options WHERE voptionid = " . (int)$value['valueid']);
						$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
						$origOptionName = isc_html_escape($row['voname']);
						$newOptionName = $savedata['voname'];
					}

					$editValues[] = (int)$value['valueid'];
					$rtn = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('product_variation_options', $savedata, 'voptionid=' . (int)$value['valueid']);
				}

				if ($rtn === false) {
					$GLOBALS['ISC_CLASS_DB']->RollbackTransaction();
					return false;
				}
			}

			$groupedValues = array_merge($groupedValues, $editValues, $addValues);
			$groupedValues = array_unique($groupedValues);

			/**
			 * Update our new option name if we have to
			 */
			if ($origOptionName !== '') {
				$savedata = array(
					'voname' => $newOptionName
				);

				$rtn = $GLOBALS['ISC_CLASS_DB']->UpdateQuery("product_variation_options", $savedata, "vovariationid=" . (int)$data['id']  . " AND voname='" . $GLOBALS['ISC_CLASS_DB']->Quote($origOptionName) . "'");
				if ($rtn === false) {
					$GLOBALS['ISC_CLASS_DB']->RollbackTransaction();
					return false;
				}
			}


			if (!empty($variation) && $isNewOption) {
				$valuesForNewOption[$optionPos - 1] = $newValues[$option['name']];
				unset($newValues[$option['name']]);

				// remove this when done
				//$deleteCombo = true;
			}
		}

		/**
		 * OK, we have all the option values, now we remove any combinations that were using any deleted option values.
		 */
		if (is_array($variation) && !$deleteCombo) {

			/**
			 * First, run a query to see which options (grouped option values) are to be deleted
			 */
			$query = "SELECT voname, GROUP_CONCAT(voptionid) AS vovalues, COUNT(*) AS vototal, SUM(IF(voptionid IN(" . implode(',', $groupedValues) . "), 0, 1)) AS vodelete
						FROM [|PREFIX|]product_variation_options
						WHERE vovariationid = " . (int)$data['id'] . "
						GROUP BY voname";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {

				$tmpValues = explode(',', $row['vovalues']);
				$deleteComboIdx = array();

				/**
				 * Was the entire option deleted?
				 */
				if ($row['vototal'] == $row['vodelete']) {

					/**
					 * We keep a record of what we removed because when we remove one option from the combination then we'll also create duplicate combinations
					 */
					$duplicatRecords = array();

					/**
					 * Loop through all the combinations and remove that option while still keeping that combination
					 */
					$sResult = $GLOBALS['ISC_CLASS_DB']->Query("SELECT combinationid, vcoptionids FROM [|PREFIX|]product_variation_combinations WHERE vcvariationid=" . (int)$data['id']);
					while ($sRow = $GLOBALS['ISC_CLASS_DB']->Fetch($sResult)) {
						$tmpCobmo = explode(',', $sRow['vcoptionids']);
						$tmpCount = count($tmpCobmo);

						foreach ($tmpValues as $findValue) {
							$foundKey = array_search($findValue, $tmpCobmo);
							if ($foundKey !== false) {
								unset($tmpCobmo[$foundKey]);
							}
						}

						/**
						 * Do we need to do anything?
						 */
						if ($tmpCount !== count($tmpCobmo)) {

							/**
							 * Build the key to check for duplicates
							 */
							$duplicateKey = $tmpCobmo;
							sort($duplicateKey);
							$duplicateKey = implode('-', $duplicateKey);

							/**
							 * Check our duplicate record. If we are in then mark it to be deleted
							 */
							if (array_key_exists($duplicateKey, $duplicatRecords)) {
								$deleteComboIdx[] = (int)$sRow['combinationid'];

							/**
							 * Else we update it
							 */
							} else {
								$duplicatRecords[$duplicateKey] = true;
								sort($tmpCobmo);
								$rtn = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('product_variation_combinations', array('vcoptionids' => implode(',', $tmpCobmo)), 'combinationid = ' . (int)$sRow['combinationid']);

								if ($rtn === false) {
									$GLOBALS['ISC_CLASS_DB']->RollbackTransaction();
									return false;
								}
							}
						}
					}

				/**
				 * Else we just delete those combinations that use these values IF some values were deleted. Store the combinationid to an array so we can just use one delete query
				 */
				} else if ($row['vodelete'] > 0) {
					$sResult = $GLOBALS['ISC_CLASS_DB']->Query("SELECT combinationid, vcoptionids FROM [|PREFIX|]product_variation_combinations WHERE vcvariationid=" . (int)$data['id']);

					while ($sRow = $GLOBALS['ISC_CLASS_DB']->Fetch($sResult)) {
						$tmpCobmo = explode(',', $sRow['vcoptionids']);
						$removeCombo = false;

						foreach ($tmpCobmo as $id) {
							if (!in_array($id, $groupedValues)) {
								$removeCombo = true;
								break;
							}
						}

						if ($removeCombo) {
							$deleteComboIdx[] = (int)$sRow['combinationid'];
						}
					}
				}

				/**
				 * Delete any combinations if we have to
				 */
				if (!empty($deleteComboIdx)) {
					$rtn = $GLOBALS['ISC_CLASS_DB']->DeleteQuery('product_variation_combinations', 'WHERE combinationid IN(' . implode(',', $deleteComboIdx) . ')');
					if ($rtn === false) {
						$GLOBALS['ISC_CLASS_DB']->RollbackTransaction();
						return false;
					}
				}
			}
		}

		if (is_array($variation)) {
			/**
			 * Now we delete all values that were removed. Only do this for existing variations as it is pretty usless on new variations
			 */
			$extraWhere = '';
			if (!empty($groupedValues)) {
				$extraWhere = " AND voptionid NOT IN(" . implode(',', $groupedValues) . ")";
			}

			$rtn = $GLOBALS['ISC_CLASS_DB']->DeleteQuery("product_variation_options", "WHERE vovariationid=" . (int)$data['id'] . $extraWhere);
			if ($rtn === false) {
				$GLOBALS['ISC_CLASS_DB']->RollbackTransaction();
				return false;
			}


			// were new values or a whole new option created? store this data in the session so we can update with a progress bar..
			if (!empty($newValues) || !empty($valuesForNewOption)) {
				$existingData = $this->GetVariationData($data['id']);

				$sessionid = uniqid();
				$_SESSION['variations'][$sessionid] = array(
					'variationId'		=> $data['id'],
					'existingData' 		=> $existingData,
					'newValues' 		=> $newValues,
					'newOptionValues'	=> $valuesForNewOption,
				);

			}
		}

		/**
		 * Lastly, we need to update the last modified time for all the products that use this variation
		 */
		$savedata = array(
			"prodlastmodified" => time()
		);

		$GLOBALS["ISC_CLASS_DB"]->UpdateQuery("products", $savedata, "prodvariationid=" . (int)$data["id"]);

		/**
		 * Now we try commiting this. If we get an error here then just bail
		 */
		if ($GLOBALS['ISC_CLASS_DB']->CommitTransaction() === false) {
			$GLOBALS['ISC_CLASS_DB']->RollbackTransaction();
			return false;
		}

		/**
		 * All is good, now return something to say so
		 */
		if ($variation) {
			return true;
		} else {
			return $data['id'];
		}
	}

	/**
	 * Built the variation form
	 *
	 * Function will build the sortable HTML form used for filling in the variation data
	 *
	 * @access private
	 * @param array $data The optional data to build from. Should be the return from GetVariationData()
	 * @return string The variation HTML form
	 */
	private function BuildVariationCreate($data=array())
	{
		/**
		 * A fallback for adding the essential information
		 */
		if (!is_array($data) || empty($data) || !array_key_exists('options', $data) || empty($data['options'])) {
			$data = array(
						array(
							'index' => 0,
							'name' => '',
							'values' => array(
											array(
													'index' => 0,
													'valueid' => '',
													'name' => '',
												)
											)
									)
						);
		} else {
			$data = $data['options'];
		}

		/**
		 * Now to build the rows. Firstly see if we should hide the option row delete button
		 */
		if (count($data) <= 1) {
			$GLOBALS['HideRowDelete'] = 'none';
		}

		$rows = '';

		foreach ($data as $row) {

			if (array_key_exists('values', $row) && is_array($row['values'])) {
				$values = '';

				/**
				 * Should we hide the value delete button?
				 */
				if (count($row['values']) <= 1) {
					$GLOBALS['HideOptionDelete'] = 'none';
				}
				foreach ($row['values'] as $value) {

					$GLOBALS['VariationOptionRankId'] = isc_html_escape($row['index']);
					$GLOBALS['VariationValueRankId'] = $value['index'];

					if (array_key_exists('valueid', $value)) {
						$GLOBALS['VariationValueId'] = isc_html_escape($value['valueid']);
					}

					if (array_key_exists('name', $value)) {
						$GLOBALS['VariationValue'] = isc_html_escape($value['name']);
					}

					$values .= $this->template->render('Snippets/ProductVariationValue.html');
				}

				$GLOBALS['ProductVariationValue'] = $values;
			}

			$GLOBALS['VariationOptionRankId'] = isc_html_escape($row['index']);
			$GLOBALS['VariationOptionName'] = isc_html_escape($row['name']);
			$rows .= $this->template->render('Snippets/ProductVariationRow.html');
		}

		return $rows;
	}

	/**
	 * Validate the submitted variation form data
	 *
	 * Method will validate the submitted variation form data
	 *
	 * @access private
	 * @param array $data The variation data to validate
	 * @param string &$error The referenced string to store the error in, if any were found
	 * @return bool TRUE if POST data is valid, FALSE if there were errors
	 */
	private function ValidateVariationData($data, &$error)
	{
		/**
		 * Do we have anything to validate?
		 */
		if (empty($data) || $data['name'] == '') {
			$error = GetLang("ProductVariationErrorNoVariationName");
			return false;
		}

		/**
		 * Check to see if this variation name is unique
		 */
		$query = "SELECT * FROM [|PREFIX|]product_variations WHERE vname='" . $GLOBALS['ISC_CLASS_DB']->Quote($data['name']) . "'";
		if (isId($data['id'])) {
			$query .= " AND variationid != " . (int)$data['id'];
		}

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if ($GLOBALS['ISC_CLASS_DB']->CountResult($result)) {
			$error = GetLang("ProductVariationErrorNameNotUnique");
			return false;
		}

		/**
		 * Do we have any options?
		 */
		if (!array_key_exists('options', $data) || empty($data['options'])) {
			$error = GetLang('ProductVariationErrorNoData');
			return false;
		}

		$pos=0;
		$optionNames = array();
		foreach ($data['options'] as $rowIndex => $row) {
			$pos++;

			if ($row['name'] == '') {
				$error = sprintf(GetLang('ProductVariationErrorNoOptionName'), $pos);
				return false;
			} else if (count($row['values']) <= 1) {
				$error = sprintf(GetLang('ProductVariationErrorInvalidOptions'), $pos);
				return false;
			}

			$validateUniqueIdx = array();

			foreach ($row['values'] as $value) {
				if (isset($value['valueid']) && isId($value['valueid'])) {
					$validateUnique[] = (int)$value['valueid'];
				}
			}

			/**
			 * Check to see if each of our option names are unique
			 */
			foreach ($optionNames as $id => $name) {
				if ($name == $row['name']) {
					$error = sprintf(GetLang('ProductVariationErrorOptionNameNotUnique'), $pos, ($id+1));
					return false;
				}
			}

			$optionNames[] = $row['name'];
		}

		return true;
	}

	/**
	 * Get the posted variation data
	 *
	 * Method will return the posted variation data
	 *
	 * @access private
	 * @param int $variationId The optional variation to load from the database. Default is 0 (load from POST)
	 * @return array The posted variation data
	 */
	private function GetVariationData($variationId=0)
	{
		$data = array();

		/**
		 * Load from database
		 */
		if (isId($variationId)) {
			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]product_variations WHERE variationid=" . (int)$variationId);
			$variation = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			if (!$variation) {
				return $data;
			}

			$data['id'] = (int)$variation['variationid'];
			$data['name'] = $variation['vname'];
			$data['vendor'] = (int)$variation['vvendorid'];
			$data['options'] = array();

			/**
			 * Now get the options
			 */
			$currentOption = null;
			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]product_variation_options WHERE vovariationid=" . (int)$variationId . " ORDER BY vooptionsort, vovaluesort");

			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {

				/**
				 * Check to see if we are still using the same option
				 */
				if (is_null($currentOption) || $currentOption !== $row['voname']) {
					$optionKey = count($data['options']);
					$valueKey = 0;
					$currentOption = $row['voname'];
					$data['options'][$optionKey] = array(
									'index' => $optionKey,
									'name' => $row['voname'],
									'values ' => array(),
					);
				}

				/**
				 * Add the option
				 */
				$data['options'][$optionKey]['values'][$valueKey] = array(
									'valueid' => $row['voptionid'],
									'index' => $valueKey,
									'name' => $row['vovalue']
				);

				$valueKey++;
			}

		/**
		 * Else load from POST
		 */
		} else {

			$data = array(
				'options' => array()
			);

			if (array_key_exists('variationId', $_POST)) {
				$data['id'] = (int)$_POST['variationId'];
			}

			if (array_key_exists('vname', $_POST)) {
				$data['name'] = $_POST['vname'];
			}

			if (array_key_exists('vendor', $_POST)) {
				$data['vendor'] = $_POST['vendor'];
			}

			/**
			 * Go get our options. Bail if we do not have any
			 */
			if (!array_key_exists('variationOptionName', $_POST) || !is_array($_POST['variationOptionName'])) {
				return $data;
			}

			$options = array();
			foreach ($_POST['variationOptionName'] as $optionId => $optionVal) {

				/**
				 * Start our record
				 */
				$optionKey = count($options);
				$options[$optionKey] = array(
						'index' => $optionId,
						'name' => trim($optionVal),
						'values' => array()
				);

				/**
				 * Do we have any values at all?
				 */
				if (!isset($_POST['variationOptionValue'][$optionId]) || !is_array($_POST['variationOptionValue'][$optionId])) {
					continue;
				}

				foreach ($_POST['variationOptionValue'][$optionId] as $valueId => $valueVal) {

					$valueKey = count($options[$optionKey]['values']);

					$options[$optionKey]['values'][$valueKey] = array(
										'index' => $valueId,
										'name' => trim($valueVal)
					);

					if (isset($_POST['variationOptionValueId'][$optionId][$valueId]) && isId($_POST['variationOptionValueId'][$optionId][$valueId])) {
						$options[$optionKey]['values'][$valueKey]['valueid'] = (int)$_POST['variationOptionValueId'][$optionId][$valueId];
					}
				}
			}

			/**
			 * Add our options to our return data array
			 */
			$data['options'] = $options;
		}

		return $data;
	}

	/**
	 * Get default variation options
	 *
	 * Method will return the default variations when adding in a new variation. Basically some test data
	 *
	 * @access private
	 * return array The default variation test data
	 */
	private function GetVariationTestData()
	{
		$testdata = array(
					'options' => array()
					);

		$testdata['options'][] = array(
				'index' => 0,
				'name' => GetLang('ProductVariationTestDataOptionColour'),
				'values' => array(
								array(
									'index' => 0,
									'name' => GetLang('ProductVariationTestDataValueColourRed')
								),
								array(
									'index' => 1,
									'name' => GetLang('ProductVariationTestDataValueColourBlue')
								),
								array(
									'index' => 2,
									'name' => GetLang('ProductVariationTestDataValueColourPurple')
								),
								array(
									'index' => 3,
									'name' => GetLang('ProductVariationTestDataValueColourOrange')
								)
							)
					);

		$testdata['options'][] = array(
				'index' => 1,
				'name' => GetLang('ProductVariationTestDataOptionSize'),
				'values' => array(
								array(
									'index' => 0,
									'name' => GetLang('ProductVariationTestDataValueSizeSmall')
								),
								array(
									'index' => 1,
									'name' => GetLang('ProductVariationTestDataValueSizeMedium')
								),
								array(
									'index' => 2,
									'name' => GetLang('ProductVariationTestDataValueSizeLarge')
								),
								array(
									'index' => 3,
									'name' => GetLang('ProductVariationTestDataValueSizeXLarge')
								)
							)
					);

		$testdata['options'][] = array(
				'index' => 2,
				'name' => GetLang('ProductVariationTestDataOptionStyle'),
				'values' => array(
								array(
									'index' => 0,
									'name' => GetLang('ProductVariationTestDataValueStyleModern')
								),
								array(
									'index' => 1,
									'name' => GetLang('ProductVariationTestDataValueStyleClassic')
								)
							)
					);

		return $testdata;
	}

	/**
	* ViewVariations
	* Show a list of all available product variations
	*
	* @return Void
	*/
	public function viewProductVariationsAction($MsgDesc = "", $MsgStatus = "")
	{

		$GLOBALS['VariationDataGrid'] = $this->_GetVariationGrid($num_variations);

		// Was this an ajax based sort? Return the table now
		if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == 1) {
			echo $GLOBALS['VariationDataGrid'];
			return;
		}

		// Disable the delete button if there aren't any variations
		if($num_variations == 0) {
			$GLOBALS['DisableDelete'] = "DISABLED='DISABLED'";
			$GLOBALS['DisplayGrid'] = "none";
			$MsgDesc = GetLang("NoProductVariations");
			$MsgStatus = MSG_INFO;
		}

		if($MsgDesc != "") {
			$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
		}

		if (isset($_SESSION['variations'])) {
			$this->template->assign('updateSessionId', key($_SESSION['variations']));
		}

		$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Products') => "index.php?ToDo=viewProducts", GetLang('ProductVariations') => "index.php?ToDo=viewProductVariations");

		return 'products.variations.manage.tpl';
	}

	/**
	* _GetVariationGrid
	* Get all of the product variations from the database and return them as a grid
	*
	* @param Int $NumVariations A reference variable to store the number of variations found
	* @return String
	*/
	public function _GetVariationGrid(&$NumVariations)
	{

		// Show a list of variations in a table
		$page = 0;
		$start = 0;
		$numVariations = 0;
		$numPages = 0;
		$GLOBALS['VariationsGrid'] = "";
		$GLOBALS['Nav'] = "";
		$max = 0;

		$validSortFields = array('vname', 'vnumoptions');

		if(isset($_REQUEST['sortOrder']) && $_REQUEST['sortOrder'] == "asc") {
			$sortOrder = "asc";
		}
		else {
			$sortOrder = "desc";
		}

		if(isset($_REQUEST['sortField']) && in_array($_REQUEST['sortField'], $validSortFields)) {
			$sortField = $_REQUEST['sortField'];
			SaveDefaultSortField("ViewProductVariations", $_REQUEST['sortField'], $sortOrder);
		} else {
			list($sortField, $sortOrder) = GetDefaultSortField("ViewProductVariations", "vname", $sortOrder);
		}

		if(isset($_GET['page'])) {
			$page = (int)$_GET['page'];
		}
		else {
			$page = 1;
		}

		// Build the pagination and sort URL
		$searchURL = '';
		foreach($_GET as $k => $v) {
			if($k == "sortField" || $k == "sortOrder" || $k == "page" || $k == "new" || $k == "ToDo" || $k == "SubmitButton1" || !$v) {
				continue;
			}
			if(is_array($v)) {
				foreach($v as $v2) {
					$searchURL .= sprintf("&%s[]=%s", $k, urlencode($v2));
				}
			}
			else {
				$searchURL .= sprintf("&%s=%s", $k, urlencode($v));
			}
		}

		$sortURL = sprintf("%s&amp;sortField=%s&amp;sortOrder=%s", $searchURL, $sortField, $sortOrder);
		$GLOBALS['SortURL'] = $sortURL;

		// Limit the number of questions returned
		if($page == 1) {
			$start = 1;
		}
		else {
			$start = ($page * ISC_PRODUCTS_PER_PAGE) - (ISC_PRODUCTS_PER_PAGE-1);
		}

		$start = $start-1;

		// Get the results for the query
		$variation_result = $this->_GetVariationList($start, $sortField, $sortOrder, $numVariations);
		$numPages = ceil($numVariations / ISC_PRODUCTS_PER_PAGE);
		$NumVariations = $numVariations;

		// Add the "(Page x of n)" label
		if($numVariations > ISC_PRODUCTS_PER_PAGE) {
			$GLOBALS['Nav'] = sprintf("(%s %d of %d) &nbsp;&nbsp;&nbsp;", GetLang('Page'), $page, $numPages);

			$GLOBALS['Nav'] .= BuildPagination($numVariations, ISC_PRODUCTS_PER_PAGE, $page, sprintf("index.php?ToDo=viewProductVariations%s", $sortURL));
		}
		else {
			$GLOBALS['Nav'] = "";
		}

		$GLOBALS['Nav'] = preg_replace('# \|$#',"", $GLOBALS['Nav']);
		$GLOBALS['SortField'] = $sortField;
		$GLOBALS['SortOrder'] = $sortOrder;
		$sortLinks = array(
			"Name" => "vname",
			"Options" => "vnumoptions",
		);

		BuildAdminSortingLinks($sortLinks, "index.php?ToDo=viewProductVariations&amp;".$searchURL."&amp;page=".$page, $sortField, $sortOrder);

		// Workout the maximum size of the array
		$max = $start + ISC_PRODUCTS_PER_PAGE;

		if ($max > $numVariations) {
			$max = $numVariations;
		}

		if($numVariations > 0) {
			// Display the products
			while($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($variation_result)) {
				$GLOBALS['VariationId'] = (int) $row['variationid'];
				$GLOBALS['Name'] = isc_html_escape($row['vname']);
				if(gzte11(ISC_HUGEPRINT) && $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId() == 0 && $row['vendorname']) {
					$GLOBALS['Name'] .= ' <small><strong>('.GetLang('Vendor').': '.$row['vendorname'].')</strong></small>';
				}
				$GLOBALS['NumOptions'] = (int) $row['vnumoptions'];
				$GLOBALS['Edit'] = '<a class="Action" href="index.php?ToDo=editProductVariation&amp;variationId=' . $row['variationid'] . '" title="' . GetLang('ProductVariationEdit') . '">' . GetLang('Edit') . '</a>';
				$GLOBALS['Configure'] = '<a class="Action" href="index.php?ToDo=configureVariations&amp;variationId=' . $row['variationid'] . '" title="' . GetLang('ConfigureVariations') . '">' . GetLang('ConfigureVariations') . '</a>';
				$GLOBALS['VariationsGrid'] .= $this->template->render('product.variations.manage.row.tpl');
			}

		}

		return $this->template->render('products.variations.manage.grid.tpl');
	}

	public function _GetVariationList($Start, $SortField, $SortOrder, &$NumVariations, $fields='', $AddLimit=true)
	{
		// Return an array containing details about variations.
		if($fields == '') {
			$fields = " *, v.vendorname AS vendorname ";
		}

		$queryWhere = '';

		// Only fetch variations which belong to the current vendor
		if($GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()) {
			$queryWhere .= " AND vvendorid='".(int)$GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId()."'";
		}

		$countQuery = "SELECT COUNT(variationid) FROM [|PREFIX|]product_variations WHERE 1=1 ".$queryWhere;

		$query = "
			SELECT ".$fields."
			FROM [|PREFIX|]product_variations p
			LEFT JOIN [|PREFIX|]vendors v ON (v.vendorid=p.vvendorid)
			WHERE 1=1
		";
		$query .= $queryWhere;

		// Fetch the number of results
		$result = $GLOBALS['ISC_CLASS_DB']->Query($countQuery);
		$NumVariations = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

		// Add the sorting options
		$query .= sprintf("order by %s %s", $SortField, $SortOrder);

		// Add the limit
		if($AddLimit) {
			$query .= $GLOBALS["ISC_CLASS_DB"]->AddLimit($Start, ISC_PRODUCTS_PER_PAGE);
		}

		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		return $result;
	}

	/**
	* DeleteVariations
	* Delete one/more product variations from the database
	*
	* @return Void
	*/
	public function deleteProductVariationsAction()
	{
		if(isset($_POST['variations']) && is_array($_POST['variations'])) {

			foreach ($_POST['variations'] as $k => $v) {
				$_POST['variations'][$k] = (int) $v;
			}

			// What we do here is feed the list of product IDs in to a query with the vendor applied so that way
			// we're sure we're only deleting variations this user has permission to delete.
			$variation_ids = implode("','", array_map('intval', $_POST['variations']));
			$vendorId = $GLOBALS['ISC_CLASS_ADMIN_AUTH']->GetVendorId();
			if($vendorId > 0) {
				$query = "
					SELECT variationid
					FROM [|PREFIX|]product_variations
					WHERE variationid IN ('".$variation_ids."') AND vvendorid='".(int)$vendorId."'
				";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$variation_ids = '';
				while($variation = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$variation_ids .= $variation['variationid'].',';
				}
				$variation_ids = rtrim($variation_ids, ',');
			}

			// ISC-1650 need to delete images for deleted variation combinations, to do that we need a list of the
			// images that will be deleted before deleting the records below
			$deletedImages = array();
			$deletedCombinations = "
				SELECT DISTINCT
					vcimage, vcimagezoom, vcimagestd, vcimagethumb
				FROM
					[|PREFIX|]product_variation_combinations
				WHERE
					vcvariationid IN ('" . $variation_ids . "')
			";
			$deletedCombinations = new Interspire_Db_QueryIterator($this->db, $deletedCombinations);
			foreach ($deletedCombinations as $deletedCombination) {
				$deletedImages[$deletedCombination['vcimage']] = true;
				$deletedImages[$deletedCombination['vcimagezoom']] = true;
				$deletedImages[$deletedCombination['vcimagestd']] = true;
				$deletedImages[$deletedCombination['vcimagethumb']] = true;
			}

			$GLOBALS["ISC_CLASS_DB"]->StartTransaction();

			$errors = 0;

			// Delete the variation
			if (!$GLOBALS["ISC_CLASS_DB"]->DeleteQuery("product_variations", sprintf("WHERE variationid IN('%s')", $variation_ids))) {
				$errors++;
			}

			// Delete the variation combinations
			if (!$GLOBALS["ISC_CLASS_DB"]->DeleteQuery("product_variation_combinations", sprintf("WHERE vcvariationid IN('%s')", $variation_ids))) {
				$errors++;
			}

			// Delete the variation options
			if (!$GLOBALS["ISC_CLASS_DB"]->DeleteQuery("product_variation_options", sprintf("WHERE vovariationid IN('%s')", $variation_ids))) {
				$errors++;
			}

			// Update the products that use this variation to not use any at all
			if (!$GLOBALS["ISC_CLASS_DB"]->UpdateQuery("products", array("prodvariationid" => "0"), "prodvariationid IN('" . $variation_ids . "')")) {
				$errors++;
			}

			if (!$errors) {
				// ISC-1650 delete combination images which are no longer in the system anywhere
				foreach ($deletedImages as $deletedImage => $foo) {
					try {
						if (ISC_PRODUCT_IMAGE::isImageInUse($deletedImage)) {
							// the image is referenced elsewhere and should stay
							continue;
						}
					} catch (Exception $exception) {
						// something failed -- don't delete since we're unsure if the image is in use or not
						continue;
					}

					$deletedImagePath = ISC_BASE_PATH . '/' . GetConfig('ImageDirectory') . '/' . $deletedImage;
					if (!file_exists($deletedImagePath)) {
						continue;
					}
					// the image is not used anywhere, delete it
					unlink($deletedImagePath);
				}
			}

			if (!$errors) {
				$GLOBALS["ISC_CLASS_DB"]->CommitTransaction();
				return $this->viewProductVariationsAction(GetLang("VariationDeletedSuccessfully"), MSG_SUCCESS);
			}
			else {
				$GLOBALS["ISC_CLASS_DB"]->RollbackTransaction();
				return $this->viewProductVariationsAction(sprintf(GetLang("ErrorWhenDeletingVariation"), $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg()), MSG_ERROR);
			}
		}
		else {
			return $this->viewProductVariationsAction();
		}
	}

	public function importProductVariationsAction()
	{
		if (!$this->auth->HasPermission(AUTH_Import_Products)) {
			$this->engine->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
		}

		$this->renderLayout = false;

		$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Products') => "index.php?ToDo=viewProducts", GetLang('ImportProductVariations') => "index.php?ToDo=importProductVariations");

		require_once dirname(__FILE__)."/../importer/product_variations.php";
		$importer = new ISC_BATCH_IMPORTER_PRODUCT_VARIATIONS();
	}

	/**
	 * Build a list of vendors that can be chosen for a product.
	 *
	 * @param int The vendor ID to select, if any.
	 * @return string The HTML options for the select box of vendors.
	 */
	private function BuildVendorSelect($selectedVendor=0)
	{
		$options = '<option value="0">'.GetLang('ProductNoVendor').'</option>';
		$query = "
			SELECT vendorid, vendorname
			FROM [|PREFIX|]vendors
			ORDER BY vendorname ASC
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($vendor = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$sel = '';
			if($selectedVendor == $vendor['vendorid']) {
				$sel = 'selected="selected"';
			}
			$options .= '<option value='.(int)$vendor['vendorid'].' '.$sel.'>'.isc_html_escape($vendor['vendorname']).'</option>';
		}
		return $options;
	}
}
