<?php
CLASS ISC_SIDEPRODUCTALSOBOUGHT_PANEL extends PRODUCTS_PANEL
{
	public function SetPanelSettings()
	{
		$GLOBALS['AlsoBoughtProductListing'] = '';
		$query = "
			SELECT ordprodid
			FROM [|PREFIX|]order_products
			WHERE orderorderid IN (SELECT orderorderid FROM [|PREFIX|]order_products WHERE ordprodid='".$GLOBALS['ISC_CLASS_PRODUCT']->GetProductId()."') AND ordprodid != ".$GLOBALS['ISC_CLASS_PRODUCT']->GetProductId()."
			GROUP BY ordprodid
			ORDER BY COUNT(ordprodid) DESC
		";
		$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, 10);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$productIds = array();
		while($product = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$productIds[] = $product['ordprodid'];
		}

		if(empty($productIds)) {
			$this->DontDisplay = true;
			return;
		}

		if(!getProductReviewsEnabled()) {
			$GLOBALS['HideProductRating'] = "display: none";
		}

		$query = $this->getProductQuery('p.productid IN ('.implode(',', $productIds).')');
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$GLOBALS['AlternateClass'] = '';
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$this->setProductGlobals($row);
			$GLOBALS['AlsoBoughtProductListing'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SideProductAlsoBoughtItem");
		}

		if(!$GLOBALS['AlsoBoughtProductListing']) {
			$this->DontDisplay = true;
		}
	}
}