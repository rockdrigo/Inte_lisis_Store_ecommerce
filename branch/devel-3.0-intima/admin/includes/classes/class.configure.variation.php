<?php

class ISC_ADMIN_CONFIGURE_VARIATION extends ISC_ADMIN_PRODUCT_VARIATIONS {

	/**
	 * NES:
	 * ConfigureVariations
	 * The form to edit the default values of price and weight of each option in a variation.
	 * Then we can recaulculate all combinations for a product
	 *
	 * @return Void
	 */
	public function configureVariationsAction($MsgDesc = "", $MsgStatus = "", $PreservePost=false)
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

		if (!isset($variationId)) {
			return $this->viewProductVariationsAction("", "");
		}

		if (!isId($variationId) || !$variationData) {
			return $this->viewProductVariationsAction(GetLang('ProductVariationErrorDoesNotExists'), MSG_ERROR);
		}

		$productData = $this->GetProductsWithVariation($variationId);

		//print_r($productData);

		$GLOBALS['FormActionConfig'] = "editConfigVariation";
		$GLOBALS['FormActionAsignVariations'] = "asignVariation";
		$GLOBALS['FormActionRecalculate'] = "recalculateConfigVariation";

		$GLOBALS['ConfigTitle'] = GetLang("ConfigureVariations");
		$GLOBALS['AsignTitle'] = GetLang("AsignVariation");
		$GLOBALS['RecalculateTitle'] = GetLang("ProductOptionRecalculateValues");

		$GLOBALS['VariationName'] = $variationData['name'];
		$GLOBALS['VariationId'] = $variationData['id'];

		$GLOBALS['Variations'] = $this->BuildVariationCreate($variationData);
		$GLOBALS['ProductsTable'] = $this->BuildProductsTable();
		$GLOBALS['ProductsToRecalculate'] = $this->BuildProductsSelect($productData);

		$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('Products') => "index.php?ToDo=viewProducts", GetLang("ProductVariations") => "index.php?ToDo=viewProductVariations", $variationData['name'] => "index.php?ToDo=editProductVariation&variationId=".$variationData['id'], GetLang('ConfigureVariations') => "index.php?ToDo=editProductVariation&variationId=".$variationData['id']);

		return 'configure.variation.form.tpl';
	}

	public function asignVariationAction()
	{
		if (isset($_POST['variationId'])) {
			$variationId = (int)$_POST['variationId'];
		}
		else $variationId = "";

		if (!$this->auth->HasPermission(AUTH_Manage_Products)) {
			$this->engine->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
		}

		$_GET['variationId'] = $variationId;
		if (!isId($variationId)) {
			return $this->configureVariationsAction(GetLang('ProductVariationErrorDoesNotExists'), MSG_ERROR);
		}
		
		if (!isset($_POST['products']))
		{
			return $this->configureVariationsAction(GetLang("ErrorNoProductsSelected"), MSG_ERROR);
		}

		//print_r($_REQUEST);
		$variationData = $this->GetVariationData($variationId);

		$combos = $this->LoadVariationCombinations($variationId);
		
		$errors = array();
		foreach($_POST['products'] as $key => $productId)
		{
			$errors[$key] = $this->asignVariationToProduct($combos, $variationId, $productId);
		}

		return $this->configureVariationsAction(GetLang("VariationConfigUpdatedSuccesfully"), MSG_SUCCESS);
	}
	
	private function asignVariationToProduct($combos, $variationId, $productId)
	{
		if (isId($productId)) {
			$rtn_delete = $GLOBALS['ISC_CLASS_DB']->DeleteQuery('product_variation_combinations', "WHERE vcproductid=" . (int)$productId);
		}
		$data = array();
		$data['prodvariationid'] = $variationId;
		if (isset($_POST['chk_prodoptionsrequired']) && $_POST['chk_prodoptionsrequired'] == 'on')
		{
			$data['prodoptionsrequired'] = 1;
		} 
		else $data['prodoptionsrequired'] = 0;
		
		$rtn_update = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('products', $data, "productid = '".$productId."'");
		foreach($combos as $combo)
		{
			$combo = substr($combo, 1);
			$options = explode('#', $combo);
			$combo = implode(',', $options);
			$query_insert = "INSERT INTO [|PREFIX|]product_variation_combinations (vcproductid, vcvariationid, vcoptionids) VALUES ('$productId', '$variationId', '$combo')";
			$rtn = $GLOBALS['ISC_CLASS_DB']->Query($query_insert);
		}				
	}

	public function editConfigVariationAction()
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
		 * Add our new variation record
		 */
		foreach($_REQUEST['hdn_voptionid'] as $key => $value)
		{
			$_REQUEST['sel_vcpricediff'][$key] == '' ? $_REQUEST['inp_vcprice'][$key] = '0.00' : $_REQUEST['inp_vcprice'][$key];
			$_REQUEST['sel_vcweightdiff'][$key] == '' ? $_REQUEST['inp_vcweight'][$key] = '0.00' : $_REQUEST['inp_vcweight'][$key];
			$query_update_variations = "UPDATE [|PREFIX|]product_variation_options
			SET
			`vcpricediff` = '".$_REQUEST['sel_vcpricediff'][$key]."',
			`vcprice` = '".$_REQUEST['inp_vcprice'][$key]."',
			`vcweightdiff` = '".$_REQUEST['sel_vcweightdiff'][$key]."',
			`vcweight` = '".$_REQUEST['inp_vcweight'][$key]."'
			WHERE `vovariationid` = '".$variationId."' AND `voptionid` = '".$value."'";
				
			//echo $query_update_variations ."<br />\n";
			$rtn = $GLOBALS['ISC_CLASS_DB']->Query($query_update_variations);
			$rtn = true;
				
			/**
			 * Did we get any errors?
			 */
			$_GET['variationId'] = $variationId;
			if (!$rtn) {
				return $this->configureVariationsAction(sprintf(GetLang("ErrorWhenUpdatingVariation"), $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg()), MSG_ERROR, true);
			}
		}

		return $this->configureVariationsAction(GetLang("VariationConfigUpdatedSuccesfully"), MSG_SUCCESS);
	}

	public function recalculateConfigVariationAction()
	{
		$updated = 0; $inserted = 0; $errors = 0;
		$variationId = $_POST['variationId'];
		
		$_GET['variationId'] = $_POST['variationId'];
		if(isset($_POST['chk_product']))
		{
			$combos = $this->LoadVariationCombinations($variationId);
			foreach($_POST['chk_product'] as $key => $productId)
			{
				//echo "--".$key."--".$productId."-<br />";
				if(isset($_POST['RecalculateFormRecalculate']))
				{
					$prod_returns[$productId] = $this->RecalculateValues($combos, $variationId, $productId);	
				}
				elseif (isset($_POST['RecalculateFormUnasign']))
				{
					$prod_returns[$productId] = $this->UnasignVariation($productId);
				}
			}
			//print_r($prod_errors);print("<br />");
			foreach ($prod_returns as $returned)
			{
				$updated += $returned['updated'];
				$inserted += $returned['inserted'];
				$errors += $returned['errors'];
			}

			if ($errors == 0)
			{
				if(isset($_POST['RecalculateFormRecalculate'])) return $this->configureVariationsAction(sprintf(GetLang("VariationRecalculateUpdatedSuccesfully"), $updated, $inserted), MSG_SUCCESS);
				else if (isset($_POST['RecalculateFormUnasign'])) return $this->configureVariationsAction(GetLang("VariationUnasigned"), MSG_SUCCESS);
			}
			else return $this->configureVariationsAction(sprintf(GetLang("ErrorWhenUpdatingVariation"), $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg()), MSG_ERROR, true);
		}
		else
		{
			return $this->configureVariationsAction(sprintf(GetLang("ErrorNoProductsSelected"), $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg()), MSG_ERROR, true);
		}
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
									'values' => array(),
					);
				}

				/**
				 * Add the option
				 */
				$data['options'][$optionKey]['values'][$valueKey] = array(
									'valueid' => $row['voptionid'],
									'index' => $valueKey,
									'name' => $row['vovalue'],
									'pricediff' => $row['vcpricediff'],
									'price' => $row['vcprice'],
									'weightdiff' => $row['vcweightdiff'],
									'weight' => $row['vcweight'],
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


	private function GetProductsWithVariation($variationId=0)
	{
		$data = array();

		/**
		 * Load from database
		 */
		if (isId($variationId)) {

			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT DISTINCT pvc.vcproductid, p.prodname
			FROM [|PREFIX|]product_variation_combinations pvc
			JOIN [|PREFIX|]products p ON (pvc.vcproductid = p.productid) 
			WHERE pvc.vcvariationid=" . (int)$variationId);
				
			if (!$result) {
				return $data;
			}

			$i = 0;
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result))
			{
				$data[$i]['id'] = $row['vcproductid'];
				$data[$i]['name'] = $row['prodname'];
				$i++;
			}
		}

		return $data;
	}

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

		$rows = '';

		foreach ($data as $row) {

			//print_r($row);

			if (array_key_exists('values', $row) && is_array($row['values'])) {
				$values = '';

				foreach ($row['values'] as $value) {
					$values .= "\t<tr class='GridRow' >\n";
					//$values .= "\t<td>".$value['valueid']."</td>\n";
					$values .= "\t<INPUT type='hidden' name='hdn_voptionid[]' value='".$value['valueid']."' />\n";
					$values .= "\t<td>".$row['name']."</td>\n";

					$values .= "\t<td>".$value['name']."</td>\n";
					$values .= "\t<INPUT type='hidden' name='hdn_vovalue[]' value='".$row['name']."' />\n";

					$values .= "\t<td>\n";
					$values .= "\t<SELECT name='sel_vcpricediff[]'>\n";
					$values .= "\t<OPTION \n"; if($value['pricediff'] == '') $values .= "\tselected='selected' \n"; $values .= "\tvalue=''>Sin Accion</option>\n";
					$values .= "\t<OPTION \n"; if($value['pricediff'] == 'add') $values .= "\tselected='selected' \n"; $values .= "\tvalue='add'>Agregar</option>\n";
					$values .= "\t<OPTION \n"; if($value['pricediff'] == 'subtract') $values .= "\tselected='selected' \n"; $values .= "\tvalue='subtract'>Restar</option>\n";
					$values .= "\t</SELECT>\n";
					$values .= "\t</td>\n";

					$values .= "\t<td>\n";
					$values .= "\t<INPUT type='text' name='inp_vcprice[]' size='10' value='".$value['price']."' />\n";
					$values .= "\t</td>\n";

					$values .= "\t<td>\n";
					$values .= "\t<SELECT name='sel_vcweightdiff[]'>\n";
					$values .= "\t<OPTION \n"; if($value['weightdiff'] == '') $values .= "\tselected='selected' \n"; $values .= "\tvalue=''>Sin Accion</option>\n";
					$values .= "\t<OPTION \n"; if($value['weightdiff'] == 'add') $values .= "\tselected='selected' \n"; $values .= "\tvalue='add'>Agregar</option>\n";
					$values .= "\t<OPTION \n"; if($value['weightdiff'] == 'subtract') $values .= "\tselected='selected' \n"; $values .= "\tvalue='subtract'>Restar</option>\n";
					$values .= "\t</SELECT>\n";
					$values .= "\t</td>\n";

					$values .= "\t<td>\n";
					$values .= "\t<INPUT type='text' name='inp_vcweight[]' size='10' value='".$value['weight']."' />\n";
					$values .= "\t</td>\n";

					$values .= "\t</tr>\n";
				}

				$GLOBALS['ProductVariationValue'] = $values;
			}

			/*			$GLOBALS['VariationOptionRankId'] = isc_html_escape($row['index']);
			 $GLOBALS['VariationOptionName'] = isc_html_escape($row['name']);*/
			$rows .= $values;
		}

		return $rows;
	}

	private function BuildProductsSelect($data=array())
	{
		$numProducts = count($data);
		$rows = '';
		if ($numProducts <= 4)
		{
			foreach ($data as $row) {
				$rows .= '<tr class="GridRow"><td><input type="checkbox" value="'.$row['id'].'" name=chk_product[] checked="checked" /> '.$row['name'].' (ID:'.$row['id'].')</td></tr>';
			}
		}
		else
		{
			for($i=0;$i<$numProducts;$i++)
			{
				if ($i ==0 || $i%4 == 0) $rows .= '<tr class="GridRow">';
				$rows .= '<td><input type="checkbox" value="'.$data[$i]['id'].'" name=chk_product[] checked="checked" /> '.$data[$i]['name'].' (ID:'.$data[$i]['id'].')</td>';
				if (($i+1)%4 == 0) $rows .= '</tr>';
			}
		}
		return $rows;
	}
	
	private function CalculatePriceWeightDiff ($VariationID, $combination)
	{
		$result = array();
		
		$result['vcpricediff'] = "";
		$result['vcprice'] = 0;
		$result['vcweightdiff'] = "";
		$result['vcweight'] = 0;
		$where = " WHERE vovariationid = '".$VariationID."' AND (";
		foreach ($combination as $key => $OptionID)
		{
			if ($key == 0) $where .= "voptionid = '".$OptionID."'"; 
			else $where .= " OR voptionid = '".$OptionID."'";
		}
		$where .= ")";
		
		$query_combo_details = "SELECT vcpricediff, vcprice, vcweightdiff, vcweight FROM [|PREFIX|]product_variation_options".$where;
		//print($query_combo_details);print("<br />");
		$rtn_combo_details = $GLOBALS['ISC_CLASS_DB']->Query($query_combo_details);
		
		while($detail = $GLOBALS['ISC_CLASS_DB']->Fetch($rtn_combo_details))
		{
			//print_r($detail);print("<br />");
			switch ($detail['vcpricediff'])
			{
				case 'add': $result['vcprice'] += $detail['vcprice']; break;
				case 'subtract': $result['vcprice'] -= $detail['vcprice']; break;
				case 'fixed': $result['vcprice'] = $detail['vcprice']; break;
			}
			switch ($detail['vcweightdiff'])
			{
				case 'add': $result['vcweight'] += $detail['vcweight']; break;
				case 'subtract': $result['vcweight'] -= $detail['vcweight']; break;
				case 'fixed': $result['vcweight'] = $detail['vcweight']; break;
			}
		}
		
		if ($result['vcprice'] > 0) $result['vcpricediff'] = 'add';
		else if ($result['vcprice'] < 0) $result['vcpricediff'] = 'subtract';
		else if ($result['vcprice'] == 0) $result['vcpricediff'] = '';
		
		if ($result['vcweight'] > 0) $result['vcweightdiff'] = 'add';
		else if ($result['vcweight'] < 0) $result['vcweightdiff'] = 'subtract';
		else if ($result['vcweight'] == 0) $result['vcweightdiff'] = '';
		
		return $result;
	}

	private function RecalculateValues($combos, $VariationID = 0, $ProductID = 0)
	{
		$u = 0; $i = 0; $e = 0;
		foreach($combos as $key => $value)
		{

			$value = substr($value, 1, strlen($value));
			$combination = explode("#", $value);
			//print_r($combination);print("<br />");
			//sort($combination);
			$vcoptionids = implode(",", $combination);
			
			$newResult = $this->CalculatePriceWeightDiff($VariationID, $combination);
			
			$query_combo_exists = "SELECT vcpricediff, IFNULL(vcprice, 0) AS vcprice, vcweightdiff, IFNULL(vcweight, 0) AS vcweight FROM [|PREFIX|]product_variation_combinations WHERE vcproductid = '".$ProductID."' AND vcvariationid = '".$VariationID."' AND vcoptionids = '".$vcoptionids."'";
			//print($query_combo_exists);print("<br />");
			$rtn_combo_exists = $GLOBALS['ISC_CLASS_DB']->Query($query_combo_exists);
			
			$updateData = array();
			if($GLOBALS['ISC_CLASS_DB']->NumAffected() > 0)
			{
				$row_combo_exists = $GLOBALS['ISC_CLASS_DB']->Fetch($rtn_combo_exists);
//				print("------------------------------------------------------<br />");
//				print_r($row_combo_exists);print("<br />");
//				print_r($newResult);print("<br />");
//				print("------------------------------------------------------<br />");
				$row_combo_exists['vcpricediff'] != $newResult['vcpricediff'] ? $updateData['vcpricediff'] = $newResult['vcpricediff'] : $newResult['vcpricediff'];
				$row_combo_exists['vcprice'] != $newResult['vcprice'] ? $updateData['vcprice'] = $newResult['vcprice'] : $newResult['vcprice'];
				$row_combo_exists['vcweightdiff'] != $newResult['vcweightdiff'] ? $updateData['vcweightdiff'] = $newResult['vcweightdiff'] : $newResult['vcweightdiff'];
				$row_combo_exists['vcweight'] != $newResult['vcweight'] ? $updateData['vcweight'] = $newResult['vcweight'] : $newResult['vcweight'];
				
				if (!empty($updateData))
				{
					$rtn_update = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('product_variation_combinations', $updateData, "vcproductid = '".$ProductID."' AND vcvariationid = '".$VariationID."' AND vcoptionids = '".$vcoptionids."'");
					if ($rtn_update) $u++;
					else $e++;
				}
			}
			else
			{
				$updateData['vcvariationid'] = $VariationID;
				$updateData['vcproductid'] = $ProductID;
				$updateData['vcpricediff'] = $newResult['vcpricediff'];
				$updateData['vcprice'] = $newResult['vcprice'];
				$updateData['vcweightdiff'] = $newResult['vcweightdiff'];
				$updateData['vcweight'] = $newResult['vcweight'];
				$rtn_insert_combo = $GLOBALS['ISC_CLASS_DB']->InsertQuery('product_variation_combinations', $updateData, true);
				if ($rtn_insert_combo) $i++;
				else $e++;
			}
			
			//print($vcoptionids." ");print_r($updateData); print("<br />");
			
		}
		$return = array();
		$return['updated'] = $u;
		$return['inserted'] = $i;
		$return['errors'] = $e;
		//print($ProductID." ");print("--$u  $i--");print("<br />");
		return $return;
	}
	

	private function UnasignVariation($ProductId = 0)
	{
		if (isId($ProductId)) {
			$rtn_delete = $GLOBALS['ISC_CLASS_DB']->DeleteQuery('product_variation_combinations', "WHERE vcproductid=" . (int)$ProductId);
			$rtn_update = $GLOBALS['ISC_CLASS_DB']->Query("UPDATE [|PREFIX|]products SET prodvariationid = '0' WHERE productid = '".$ProductId."'");
		}
	}
	
	private function buildProductsTable()
	{
		$rtn_cats = $GLOBALS['ISC_CLASS_DB']->Query("SELECT categoryid, catname FROM [|PREFIX|]categories ORDER BY catname");
		
		$prodtable = '';
		while($category = $GLOBALS['ISC_CLASS_DB']->Fetch($rtn_cats))
		{
			$prodtable .= '<tr class="GridRow"><td class="Heading2">'.GetLang('Category').": <b>".$category['catname'].'<b></td>';
			$prodtable .= '<td class="Heading2"><a href="#" onclick="selectAll($(\'.'.$category['categoryid'].'class\'), 0);return false;">'.GetLang('SelectAll').'</a></td>
			<td class="Heading2"><a href="#" onclick="selectAll($(\'.'.$category['categoryid'].'class\'), 1);return false;">'.GetLang('UnselectAll').'</a></td>
			<td class="Heading2"> </td>';
			$prodtable .= '</tr>'.PHP_EOL;
			$prodtable .= $this->buildProductCategoryTable($category['categoryid']);
		}
		
		return $prodtable;
	}

	private function buildProductCategoryTable($categoryId)
	{
		$select_products_query = "SELECT p.productid, p.prodname
		FROM [|PREFIX|]categoryassociations ca
		JOIN [|PREFIX|]products p ON (ca.productid=p.productid)
		WHERE categoryid = '".$categoryId."'";

		$result = $GLOBALS['ISC_CLASS_DB']->Query($select_products_query);

		$rows = "";

		$numProducts = $GLOBALS['ISC_CLASS_DB']->NumAffected();
		
		if ($numProducts <= 4)
		{
			$rows .= '<tr class="GridRow">';
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result))
			{
				$rows .= '<td><INPUT type="checkbox" class="'.$categoryId.'class" name="products[]" value="'.$row['productid'].'">'.$row['prodname'].'</td>'.PHP_EOL;
			}
			$rows .= '</tr>'.PHP_EOL;
		}
		
		else
		{
			$leftover = $numProducts % 4;
			//echo "--".$numProducts."--".$leftover."--";
			for($i=0;$i<$numProducts-$leftover;$i=$i+4)
			{
				$rows .= '<tr class="GridRow">';
				$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
				$rows .= '<td><INPUT type="checkbox" class="'.$categoryId.'class" name="products[]" value="'.$row['productid'].'">'.$row['prodname'].'</td>'.PHP_EOL;
				$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
				$rows .= '<td><INPUT type="checkbox" class="'.$categoryId.'class" name="products[]" value="'.$row['productid'].'">'.$row['prodname'].'</td>'.PHP_EOL;
				$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
				$rows .= '<td><INPUT type="checkbox" class="'.$categoryId.'class" name="products[]" value="'.$row['productid'].'">'.$row['prodname'].'</td>'.PHP_EOL;
				$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
				$rows .= '<td><INPUT type="checkbox" class="'.$categoryId.'class" name="products[]" value="'.$row['productid'].'">'.$row['prodname'].'</td>'.PHP_EOL;
				$rows .= '</tr>'.PHP_EOL;
			}
			
			if($leftover > 0)
			{
				$rows .= '<tr class="GridRow">';
				for($i=0;$i<$leftover;$i++)
				{
					$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
					$rows .= '<td><INPUT type="checkbox" class="'.$categoryId.'class" name="products[]" value="'.$row['productid'].'">'.$row['prodname'].'</td>'.PHP_EOL;
				}
				$rows .= '</tr>'.PHP_EOL;
			}
		}

		return $rows;
	}

	private function LoadVariationCombinations($VariationId)
	{
		$newOptionIds = array();

		$query = sprintf("SELECT DISTINCT(voname) FROM [|PREFIX|]product_variation_options WHERE vovariationid='%d' ORDER BY vooptionsort, vovaluesort", $VariationId);
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

		while($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
			$newOptionIds[$row['voname']] = array();
		}

		// Now get all of the variation options
		$query = sprintf("SELECT * FROM [|PREFIX|]product_variation_options WHERE vovariationid='%d' ORDER BY vooptionsort, vovaluesort", $VariationId);
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

		while($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
			$newOptionIds[$row['voname']][] = $row['voptionid'];
		}

		// Get the variation combinations ID's, such as #145#185#195
		$GLOBALS["variation_data"] = array();
		$this->GetCombinationText('', $newOptionIds, 0, 0);
		$GLOBALS["variation_combination_ids"] = $GLOBALS["variation_data"];
		
		// Setup a counter
		$count = 0;
		
		$combinations = array();

		// Loop through the variation combination ID's and output them as hidden fields
		foreach($GLOBALS["variation_combination_ids"] as $k => $combo) {
			$combinations[] = $combo;
			++$count;
		}
		
		return $combinations;
	}

	private function GetCombinationText($string, $traits, $i=0, $offset = 0, &$counter = 0)
	{
		$keys = array_keys($traits);
		
		if($i >= count($traits)) {
			$counter++;
			if ($counter > $offset) {
				$GLOBALS["variation_data"][] = trim($string);
			}
		}
		else {
			foreach($traits[$keys[$i]] as $trait) {
				$this->GetCombinationText("$string#$trait", $traits, $i + 1, $offset, $counter);
			}
		}
	}
}
