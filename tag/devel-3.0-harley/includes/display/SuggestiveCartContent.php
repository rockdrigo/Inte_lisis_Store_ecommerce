<?php
class ISC_SUGGESTIVECARTCONTENT_PANEL extends PRODUCTS_PANEL
{
	public function SetPanelSettings()
	{
		if (!isset($GLOBALS['ProductJustAdded']) || !$GLOBALS['ProductJustAdded']) {
			$this->DontDisplay = true;
			return;
		}

		$limit = 8;
		if (isset($GLOBALS['SuggestiveCartContentLimit'])) {
			$limit = (int)$GLOBALS['SuggestiveCartContentLimit'];
		}

		$count = 0;
		$prod_ids = array();
		$output = "";

		$GLOBALS['SuggestedProductListing'] = "";

		// Hide the "compare" checkbox for each product
		$GLOBALS['HideCompareItems'] = "none";

			require_once(APP_ROOT."/includes/classes/class.product.php");
			$related = GetRelatedProducts($GLOBALS['Product']['productid'], $GLOBALS['Product']['prodname'], $GLOBALS['Product']['prodrelatedproducts']);

			// Any returned products? add them to the list
			$relatedProducts = explode(",", $related);
			// Limit the number of products to the # of empty spaces we have
			for($i = 0; $i < $limit; ++$i) {
				if(!isset($relatedProducts[$i]) || $relatedProducts[$i] == "") {
					break;
				}

				if(!in_array($relatedProducts[$i], $prod_ids) && !@in_array($relatedProducts[$i], $ignore_prod_list)) {
					$prod_ids[] = $relatedProducts[$i];
				}

			}

			$remaining_places = $limit -count($prod_ids);
			$suggest_prod_ids = implode(",", $prod_ids);

		// If there aren't enough suggested products, fetch related products for this item
		if($remaining_places > 0) {
				// Make sure the query doesn't return the product we're adding to
				// the cart or any other products in the cart for that matter
				$ignore_prod_list = getCustomerQuote()->getUniqueProductIds();
				$ignore_prod_list = implode(',', $ignore_prod_list);
				if($ignore_prod_list == "") {
					$ignore_prod_list = 0;
				}
				$query = "
					SELECT ordprodid
					FROM [|PREFIX|]order_products
					WHERE orderorderid IN (
						SELECT orderorderid FROM [|PREFIX|]order_products WHERE ordprodid='".(int)$GLOBALS['ProductJustAdded']."'
					) AND ordprodid NOT IN (".$ignore_prod_list.")
					GROUP BY ordprodid
					ORDER BY COUNT(ordprodid) DESC
				";
				$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, $limit);
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		
				// Get the list of suggested product id's
				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$prod_ids[] = $row['ordprodid'];
				}
		
				$suggest_prod_ids = implode(",", $prod_ids);
		
				$remaining_places = $limit -count($prod_ids);
				// If there aren't enough products to suggest, we will get
				// the popular products (based on reviews) instead
		}
		// Still don't have enough? Fetch popular products
		if($remaining_places > 0) {
			if(!$suggest_prod_ids) {
				$suggest_prod_ids = 0;
			}

			$query = sprintf("select productid, floor(prodratingtotal/prodnumratings) as prodavgrating from [|PREFIX|]products where productid not in (%s) and productid not in (%s) and prodvisible='1' order by prodavgrating desc", $suggest_prod_ids, $ignore_prod_list);
			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, $remaining_places);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			// Is there at least one product to suggest?
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$prod_ids[] = $row['productid'];
			}

			$suggest_prod_ids = implode(",", $prod_ids);
		}

		// If there are *still* no products to suggest, just show them
		// the normal shopping cart view instead

		if(!empty($prod_ids)) {
			// Get a list of products that were ordered at the
			// same time as the product that was just added to the cart
			if(!$suggest_prod_ids) {
				$suggest_prod_ids = 0;
			}

			if(!getProductReviewsEnabled()) {
				$GLOBALS['HideProductRating'] = "display: none";
			}

			$query = $this->getProductQuery(
				'p.productid IN ('.$suggest_prod_ids.')',
				'p.prodnumsold DESC, p.prodratingtotal DESC'
			);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			$GLOBALS['AlternateClass'] = '';
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$this->setProductGlobals($row);
				$GLOBALS['SuggestedProductListing'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryProductsItem");
			}
		}

		if(!$GLOBALS['SuggestedProductListing']) {
			ob_end_clean();
			header("Location:cart.php");
			die();
		}
	}
}