<?php
class ACCOUNTING_STONEEDGE_PRODUCTS
{
	 /**
	 * Count the total number of products in the database (no conditions)
	 *
	 * Method will process the post data and create text for display. Used to determine how many times SEOM should request Product Downloads.
	 *
	 * @access public
	 * @return string a response to the data to display on the page. Requires prodcode, so make sure it's there.
	 */

	public function ProcessProductCount()
	{
		$response = '';
		$count = StoneEdgeCount("products",'');
		//it's ok to return zero.
		$response = "SetiResponse: itemcount=$count";
		return $response;
	}

	/**
	 * Download product data from the database to SEOM
	 *
	 * Method will process the posted data and create XML to be displayed.
	 *
	 * @access public
	 * @return string XML response to display on the page for products requested
	 */

	public function DownloadProducts()
	{
		$xml = '';
		$xml = new SimpleXMLElement('<?xml version="1.0"?><SETIProducts />');

		// set our default queries
		$query = StoneEdgeProductQuery();
		$CountQuery = StoneEdgeProductQueryCount();

		if (isset($_REQUEST['startnum']) && (int)$_REQUEST['startnum'] > 0 && isset($_REQUEST['batchsize']) && (int)$_REQUEST['batchsize'] > 0) {
			$start = (int)$_REQUEST['startnum'] - 1;
			$numresults = (int)$_REQUEST['batchsize'];

			if ($start >= 0 && $numresults > 0) {
				$query = StoneEdgeProductQuery('LIMIT ' . $start . ', ' .$numresults);
			//	$CountQuery = StoneEdgeProductQueryCount('LIMIT ' . $start . ', ' .$numresults);
			}
		}

		if ($GLOBALS['ISC_CLASS_DB']->FetchOne($CountQuery) > $start) {
			//then there are products available for download, display header
			$responseNode = $xml->addChild('Response');
			$responseNode->addChild('ResponseCode', 1);
			$responseNode->addChild('ResponseDescription', 'Success');

			$products = $GLOBALS['ISC_CLASS_DB']->Query($query);

			//content
			while ($product = $GLOBALS['ISC_CLASS_DB']->Fetch($products)) {
				$fullImage = '';
				try {
					$productImage = ISC_PRODUCT_IMAGE::getBaseThumbnailImageForProduct($product['productid']);
					if ($productImage) {
						$fullImage = $productImage->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_STANDARD, true);
					}
				} catch (Exception $exception) {
					// nothing
				}

				if ($product['prodistaxable'] == 1) {
					$taxable = 'Yes';
				} else{
					$taxable = 'No';
				}

				if ($product['prodvisible'] == 0) {
					$discontinued = 'Yes';
				} else {
					$discontinued = 'No';
				}

				$productNode = $xml->addChild('Product');
				$desc = isc_html_escape($product['proddesc']); // the tags need to be escaped
				$productNode->addChild('Taxable', $taxable);
				$productNode->addChild('Discontinued', $discontinued);

				if ($product['prodvariationid'] == 0) {
					// no variations, just send the product as is
					if(isset($product['prodcode']) && $product['prodcode'] != ''){
						$productNode->addChild('Code', htmlentities($product['prodcode']));
					}else{
						$productNode->addChild('Code', htmlentities($product['prodname']));
					}
					$productNode->addChild('WebID', $product['productid']);
					$productNode->addChild('Name', $product['prodname']);
					$productNode->addChild('Price', number_format($product['prodcalculatedprice'],2));

					$productNode->addChild('Weight', $product['prodweight']);
					$productNode->addChild('Thumb', $GLOBALS['ShopPath'] . '/product_images/' . $product['imagefile']);
					$productNode->addChild('Image', $fullImage);
					$productNode->addChild('QOH', $product['prodcurrentinv']);

				} else {
					// product has variation, we need to send each variation as a different product
					$variationQuery = "SELECT vo.voptionid, vo.voname, vo.vovalue
					FROM [|PREFIX|]product_variations v
					LEFT JOIN [|PREFIX|]product_variation_options vo ON v.variationid = vo.vovariationid
					WHERE v.variationid = '" . $product['prodvariationid'] . "'
					ORDER BY vo.voname ASC";

					$varResource = $GLOBALS['ISC_CLASS_DB']->Query($variationQuery);
					$variationids = array();
					$varnames = array();
					$varvalues = array();

					while($varReturn = $GLOBALS['ISC_CLASS_DB']->Fetch($varResource)) {
						$variationids[] = $varReturn['voptionid'];
						$varnames[] = $varReturn['voname'];
						$varvalues[] = $varReturn['vovalue'];
					}

					$variationName = array();
					$variationValues = array();
					$options = explode(',',$product['vcoptionids']);
					foreach($options as $thisOption){
						$key = array_search($thisOption, $variationids);

						if($key === false || !isset($variationids[$key])) {
							continue;
						}

						$variationName[]   = $varnames[$key];
						$variationValues[] = $varvalues[$key];
					}

					$variationValue =  implode(', ', $variationValues);

					$comboPrice = 0;
					if ($product['vcpricediff'] != '' && $product['vcpricediff'] != 'fixed') {
						$comboPrice = $product['vcprice'];
						if ($product['vcpricediff'] == 'subtract') {
							$comboPrice = $product['vcprice'] * -1;
						}
					}

					$comboWeight = 0;
					if ($product['vcweightdiff'] != '' && $product['vcweightdiff'] != 'fixed') {
						$comboWeight = $product['vcweight'];
						if ($product['vcweightdiff'] == 'subtract') {
							$comboWeight = $product['vcweight'] * -1;
						}
					}

					if(isset($product['prodcode']) && $product['prodcode'] != ''){
						$productNode->addChild('Code', htmlentities($product['prodcode']));
					}else{
						$productNode->addChild('Code', htmlentities($product['prodname']) . ' [VARID:' . $product['combinationid'] . ']');
					}
					$productNode->addChild('WebID', $product['productid'] . "-" .  $product['combinationid']);
					$productNode->addChild('Name', $product['prodname'] . ' (' . $variationValue. ')');
					$productNode->addChild('Price', number_format(($product['prodcalculatedprice'] + $comboPrice),2));
					$productNode->addChild('Description', $desc);
					$productNode->addChild('Weight', ($product['prodweight']+$comboWeight));
					$productNode->addChild('Thumb', $GLOBALS['ShopPath'] . '/product_images/' . $product['imagefile']);
					$productNode->addChild('Image', $GLOBALS['ShopPath'] . '/product_images/' . $product['vcimage']);

					$productNode->addChild('QOH', $product['vcstock']);
				} // end if this product has a variation
			}

		} else {
			//no products, return the "none available" message.
			$responseNode = $xml->addChild('Response');
			$responseNode->addChild('ResponseCode', 2);
			$responseNode->addChild('ResponseDescription', 'Success');

		}
		return $xml->asXML();
	}

	/**
	 * Download product quantity data from the database to SEOM
	 *
	 * Method will process the posted data and create XML to be displayed.
	 *
	 * @access public
	 * @return string XML response to display on the page for product quantities on hand requested. Requires SKU so won't return any products without SKU's
	 */

	public function DownloadQuantities()
	{
		$xml = new SimpleXMLElement('<?xml version="1.0"?><SETIProducts />');

		// set our default queries
		$query = StoneEdgeProductQuery();
		$CountQuery = StoneEdgeProductQueryCount();

		if (isset($_REQUEST['startnum']) && (int)$_REQUEST['startnum'] > 0 && isset($_REQUEST['batchsize']) && (int)$_REQUEST['batchsize'] > 0) {
			$start = (int)$_REQUEST['startnum'] - 1;
			$numresults = (int)$_REQUEST['batchsize'];

			if ($start >= 0 && $numresults > 0) {
				$query = StoneEdgeProductQuery('LIMIT ' . $start . ', ' .$numresults);
			//	$CountQuery = StoneEdgeProductQueryCount('LIMIT ' . $start . ', ' .$numresults);
			}
		}

		if ($GLOBALS['ISC_CLASS_DB']->FetchOne($CountQuery) > $start) {
			//then there are products available for download, display header
			$responseNode = $xml->addChild('Response');
			$responseNode->addChild('ResponseCode', 1);
			$responseNode->addChild('ResponseDescription', 'Success');

			$products = $GLOBALS['ISC_CLASS_DB']->Query($query);

			//content
			while ($product = $GLOBALS['ISC_CLASS_DB']->Fetch($products)) {
				$productNode = $xml->addChild('Product');
				if ($product['prodvariationid'] == 0) {
					// no variations, just send the product as is
					if(isset($product['prodcode']) && $product['prodcode'] != ''){
						$productNode->addChild('Code', htmlentities($product['prodcode']));
					}else{
						$productNode->addChild('Code', htmlentities($product['prodname']));
					}
					$productNode->addChild('WebID', $product['productid']);
					$productNode->addChild('QOH', $product['prodcurrentinv']);

				} else {
					// product has variation, we need to send each variation as a different product
					$variationValue =  implode(', ', $variationValues);

					if(isset($product['prodcode']) && $product['prodcode'] != ''){
						$productNode->addChild('Code', htmlentities($product['prodcode']));
					}else{
						$productNode->addChild('Code', htmlentities($product['prodname']) . ' [VARID:' . $product['combinationid'] . ']');
					}
					$productNode->addChild('WebID', $product['productid'] . "-" .  $product['combinationid']);
					$productNode->addChild('QOH', $product['vcstock']);

				} // end if this product has a variation
			}

		} else {
			//no products, return the "none available" message.
			$responseNode = $xml->addChild('Response');
			$responseNode->addChild('ResponseCode', 2);
			$responseNode->addChild('ResponseDescription', 'Success');

		}
		return $xml->asXML();
	}


	/**
	 * Update product quantities in the database in batch as uploaded from SEOM
	 *
	 * @access public
	 * @return string text response to display on the page describing success or failure of inventory update. Requires SKU.
	 */

	public function ReplaceQuantity()
	{
		$SKUs = array();
		$xml = '';
		$xml .= "SETIResponse \n";

		if (isset($_REQUEST['update']) && $_REQUEST['update'] != '') {
			// using delimiters
			$parseme = array();
			$SKUs = array();
			$parseme = explode('|',$_REQUEST['update']);
			foreach($parseme as $parse) {
				$combocode = '';
				$prodcode = '';
				//There may be more than one tilda in their SKUs, so find the last occurrence of the tilda and everything after it is the current inventory.
				$currentinv = strrchr($parse,'~');
				$position = strpos($parse,'~');
				$prodcode = substr($parse,0,$position);
				$currentinv = (int)substr($currentinv,1,strlen($currentinv));

				// Check prodcode for a variation combination ID. If there is one, update the variations table
				preg_match('/\[VARID:([0-9]*)\]/i', $prodcode, $match);

				if (isset($match[1]) && is_numeric($match[1])) {
					$comboId = (int)$match[1];

					//update the database table product_variation combinations
					$updatedVariation = array(
						"vcstock" => $currentinv
					);
					$GLOBALS['ISC_CLASS_DB']->UpdateQuery("product_variation_combinations", $updatedVariation, "combinationid='" . $comboId . "'");
				} else {
					//update the database table products
					$updatedProduct = array(
						"prodcurrentinv" => $currentinv
					);
					$GLOBALS['ISC_CLASS_DB']->UpdateQuery("products", $updatedProduct, "prodcode='" . $GLOBALS['ISC_CLASS_DB']->Quote($prodcode) . "' OR (prodcode='' AND prodname='".$GLOBALS['ISC_CLASS_DB']->Quote($prodcode)."')");
				}

				$sku = $prodcode;

				if ($GLOBALS['ISC_CLASS_DB']->GetErrorMsg() != '') {
					//successful update
					if($sku != ''){
						$SKUs[] = array($sku => 'OK');
					}
				} else {
					$SKUs[] = array($sku => 'Update Error');
				}
			}

			//display the results (no structure necessary)
			foreach ($SKUs as $s){
				foreach($s as $sku => $response) {
					$xml .= "$sku=$response \n";
				}
			}
			$xml .= 'SETIEndOfData';
		} else{
			//then there was a problem with the SEOM output so just return the ending to the script
			$xml .= 'SETIEndOfData';
		}

		return $xml;
	}

	/**
	 * Update product current stock for one product at a time in the database as uploaded from SEOM in auto-sync. This means that every time the user updates
	 * inventory in SEOM, the shopping cart will be instantly updated.
	 *
	 * Method will process the posted data and create XML to be displayed.
	 *
	 * @access public
	 * @return string XML response to display on the page for product quantities on hand requested. Requires SKU so won't return any products without SKU's
	 */

	public function UpdateInventory()
	{
		$xml = '';
		$xml .= "SETIResponse=";

		if (isset($_REQUEST['update']) && $_REQUEST['update'] != '') {
			//delimiter version
			$parse = $_REQUEST['update'];
			//There may be more than one tilda in their SKUs, so find the last occurrence of the tilda and everything after it is the current inventory.
			$increment = strrchr($parse,'~');
			$increment = (int)substr($increment,1,strlen($increment));
			$position = strpos($parse,'~');
			$prodcode = substr($parse,0,$position);

			// Check prodcode for a variation combination ID. If there is one, update the variations table
			preg_match('/\[VARID:([0-9]*)\]/i', $prodcode, $match);

			if (isset($match[1]) && is_numeric($match[1])) {
				$comboId = (int)$match[1];
				//update the database table product_variation combinations
				if ($increment >= 0) {
					$upit = "UPDATE [|PREFIX|]product_variation_combinations set vcstock = vcstock + increment where combinationid='" . $comboId . "'";
				} else {
					$upit = "UPDATE [|PREFIX|]product_variation_combinations set vcstock = vcstock increment where combinationid='" . $comboId . "'";
				}
				$result = $GLOBALS['ISC_CLASS_DB']->Query($upit);
			} else {
				//update the database table products
				if ($increment >= 0) {
					$upit = "UPDATE [|PREFIX|]products set prodcurrentinv = prodcurrentinv + $increment where prodcode='" . $GLOBALS['ISC_CLASS_DB']->Quote($prodcode) . "' OR (prodcode='' AND prodname='".$GLOBALS['ISC_CLASS_DB']->Quote($prodcode)."')";
				} else {
					$upit = "UPDATE [|PREFIX|]products set prodcurrentinv = prodcurrentinv $increment where prodcode='" . $GLOBALS['ISC_CLASS_DB']->Quote($prodcode) . "' OR (prodcode='' AND prodname='".$GLOBALS['ISC_CLASS_DB']->Quote($prodcode)."')";
				}
				$result = $GLOBALS['ISC_CLASS_DB']->Query($upit);
			}

			if ($GLOBALS['ISC_CLASS_DB']->GetErrorMsg() == '') {
				//successful update, so now we need to know the actual quantity

				if (isset($comboId) && $comboId > 0) {
					//pull from variation combination table
					$QOHQuery = "SELECT vcstock FROM [|PREFIX|]product_variation_combinations WHERE combinationid='" . $comboId . "'";
					$QOHCount = "SELECT COUNT(*) FROM [|PREFIX|]product_variation_combinations WHERE combinationid='" . $comboId . "'";

					//are there any results?
					if ($GLOBALS['ISC_CLASS_DB']->FetchOne($QOHCount) != 0) {
						$onHand = $GLOBALS['ISC_CLASS_DB']->FetchOne($QOHQuery); //was $variations instead of $QOHQuery
						$QOH = $onHand['vcstock'];
						$xml .= "OK;SKU=" . htmlentities($prodcode) . ";QOH=$QOH;NOTE=";
					} else {
						$xml .= "False;SKU=" . htmlentities($prodcode) . ";QOH=NF;NOTE=NotFound";
					}
				} else {
					//pull from products table
					$query = "SELECT prodcurrentinv FROM [|PREFIX|]products WHERE prodcode='" . $GLOBALS['ISC_CLASS_DB']->Quote($prodcode) . "' OR (prodcode='' AND prodname='".$GLOBALS['ISC_CLASS_DB']->Quote($prodcode)."')";
					$queryCount = "SELECT COUNT(*) FROM [|PREFIX|]products WHERE prodcode='" . $GLOBALS['ISC_CLASS_DB']->Quote($prodcode) . "' OR (prodcode='' AND prodname='".$GLOBALS['ISC_CLASS_DB']->Quote($prodcode)."')";

					//are there any results?
					if ($GLOBALS['ISC_CLASS_DB']->FetchOne($queryCount) != 0) {
						$onHand = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);
						$QOH = $onHand['prodcurrentinv'];
						$xml .= "OK;SKU=" . htmlentities($prodcode) . ";QOH=$QOH;NOTE=";
					} else {
						$xml .= "False;SKU=" . htmlentities($prodcode) . ";QOH=NF;NOTE=NotFound";
					}
				}

			} else {
				$xml .= "False;SKU=" . htmlentities($prodcode) . ";QOH=NF;NOTE=NotFound";
			}
		} else {
			//shouldn't ever get here unless you're trying to hack the system
			$xml .= "False;SKU=NONETRANSMITTED;QOH=NF;NOTE=NotFound";
		}
		return $xml;
	}
}
