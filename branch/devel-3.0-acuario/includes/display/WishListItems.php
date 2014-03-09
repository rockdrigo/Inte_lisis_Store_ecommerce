<?php

	CLASS ISC_WISHLISTITEMS_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			$count = 0;
			$output = "";

			$GLOBALS['SNIPPETS']['WishListItems'] = "";
			$GLOBALS['HideCompareItems'] = "none";

			if(!GetConfig('ShowProductRating')) {
				$GLOBALS['HideProductRating'] = "display: none";
			}

			if (!isset($GLOBALS['WishListItems'])) {
				return false;
			}
			$GLOBALS['AlternateClass'] = '';
			foreach($GLOBALS['WishListItems'] as $row) {

				if($GLOBALS['AlternateClass'] == 'Odd') {
					$GLOBALS['AlternateClass'] = 'Even';
				}
				else {
					$GLOBALS['AlternateClass'] = 'Odd';
				}

				$GLOBALS['ProductCartQuantity'] = '';
				if(isset($GLOBALS['CartQuantity'.$row['productid']])) {
					$GLOBALS['ProductCartQuantity'] = (int)$GLOBALS['CartQuantity'.$row['productid']];
				}

				$GLOBALS['ItemId'] = (int) $row['wishlistitemid'];
				$GLOBALS['ProductId'] = (int) $row['productid'];
				$GLOBALS['ProductName'] = isc_html_escape($row['prodname']);
				$GLOBALS['ProductLink'] = ProdLink($row['prodname']);
				$GLOBALS['ProductRating'] = (int)$row['prodavgrating'];

				// Determine the price of this product
				$GLOBALS['ProductPrice'] = '';
				if (GetConfig('ShowProductPrice') && !$row['prodhideprice']) {
					$GLOBALS['ProductPrice'] = formatProductCatalogPrice($row);
				}

				$GLOBALS['ProductThumb'] = ImageThumb($row, ProdLink($row['prodname']));

				$GLOBALS['SNIPPETS']['WishListItems'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("WishListItem");
			}
		}
	}