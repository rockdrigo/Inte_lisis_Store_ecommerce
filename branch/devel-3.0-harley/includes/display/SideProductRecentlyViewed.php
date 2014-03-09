<?php

CLASS ISC_SIDEPRODUCTRECENTLYVIEWED_PANEL extends PRODUCTS_PANEL
{
	public function SetPanelSettings()
	{
		$viewed = "";

		if(isset($_COOKIE['RECENTLY_VIEWED_PRODUCTS'])) {
			$viewed = $_COOKIE['RECENTLY_VIEWED_PRODUCTS'];
		}
		else if(isset($_SESSION['RECENTLY_VIEWED_PRODUCTS'])) {
			$viewed = $_SESSION['RECENTLY_VIEWED_PRODUCTS'];
		}

		$GLOBALS['CompareLink'] = CompareLink();

		if(!$viewed) {
			$this->DontDisplay = true;
			return;
		}

		// Hide the top selling products panel from the cart page
		$GLOBALS['HideSideTopSellersPanel'] = "none";

		$output = "";
		$viewed_products = explode(",", $viewed);
		$viewed_products = array_map('intval', $viewed_products);

		// Reverse the array so recently viewed products appear up top
		$viewed_products = array_reverse($viewed_products);

		// Hide the compare button if there's less than 2 products
		if(GetConfig('EnableProductComparisons') == 0 || count($viewed_products) < 2) {
			$GLOBALS['HideSideProductRecentlyViewedCompare'] = "none";
		}

		if(!getProductReviewsEnabled()) {
			$GLOBALS['HideProductRating'] = "display: none";
		}

		$query = $this->getProductQuery('p.productid IN ('.implode(',', $viewed_products).')');
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		$productData = array();
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$productData[$row['productid']] = $row;
		}

		if(empty($productData)) {
			$this->DontDisplay = true;
			return;
		}

		$GLOBALS['AlternateClass'] = '';
		foreach($viewed_products as $productId) {
			if(empty($productData[$productId])) {
				continue;
			}

			$this->setProductGlobals($productData[$productId]);
			$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SideRecentlyViewedProducts");
		}

		$GLOBALS['SNIPPETS']['SideProductRecentlyViewed'] = $output;
	}
}