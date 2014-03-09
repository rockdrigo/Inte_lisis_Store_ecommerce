<?php
class ISC_PRODUCTVENDORSOTHERPRODUCTS_PANEL extends PRODUCTS_PANEL
{
	public function SetPanelSettings()
	{
		if(!gzte11(ISC_HUGEPRINT) || $GLOBALS['ISC_CLASS_PRODUCT']->GetProductVendor() === false) {
			$this->DontDisplay = true;
			return false;
		}

		$vendor = $GLOBALS['ISC_CLASS_PRODUCT']->GetProductVendor();
		$GLOBALS['SNIPPETS']['VendorsOtherProducts'] = '';

		if(!getProductReviewsEnabled()) {
			$GLOBALS['HideProductRating'] = "display: none";
		}

		$query = $this->getProductQuery(
			'p.prodvendorid='.(int)$vendor['vendorid'].' AND p.productid!='.$GLOBALS['ISC_CLASS_PRODUCT']->getProductId(),
			'p.prodvendorfeatured DESC, RAND() DESC',
			10 // Select 1 more than will be shown to check if we need to show the "has more" link
		);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$productsDone = 0;
		$hasMore = false;
		$GLOBALS['AlternateClass'] = '';
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			++$productsDone;
			if($productsDone == 9) {
				$hasMore = true;
				break;
			}
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

			$GLOBALS['ProductId'] = (int) $row['productid'];
			$GLOBALS['ProductName'] = isc_html_escape($row['prodname']);
			$GLOBALS['ProductLink'] = ProdLink($row['prodname']);
			$GLOBALS['ProductRating'] = (int)$row['prodavgrating'];

			// Determine the price of this product
			$GLOBALS['ProductPrice'] = formatProductCatalogPrice($row);

			$GLOBALS['ProductThumb'] = ImageThumb($row, ProdLink($row['prodname']));

			if (isId($row['prodvariationid']) || trim($row['prodconfigfields'])!='' || $row['prodeventdaterequired'] == 1) {
				$GLOBALS['ProductURL'] = ProdLink($row['prodname']);
				$GLOBALS['ProductAddText'] = GetLang('ProductChooseOptionLink');
			} else {
				$GLOBALS['ProductURL'] = CartLink($row['productid']);
				$GLOBALS['ProductAddText'] = GetLang('ProductAddToCartLink');
			}

			if (CanAddToCart($row) && GetConfig('ShowAddToCartLink')) {
				$GLOBALS['HideActionAdd'] = '';
			} else {
				$GLOBALS['HideActionAdd'] = 'none';
			}

			$GLOBALS['SNIPPETS']['VendorsOtherProducts'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductVendorsOtherProductsItem");
		}

		if(!$GLOBALS['SNIPPETS']['VendorsOtherProducts']) {
			$this->DontDisplay = true;
		}

		$GLOBALS['VendorProductsLink'] = VendorProductsLink($vendor);
		if($hasMore == true) {
			$GLOBALS['HideViewAllLink'] = '';
		}
		else {
			$GLOBALS['HideViewAllLink'] = 'display: none';
		}
	}
}