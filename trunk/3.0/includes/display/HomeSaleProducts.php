<?php
class ISC_HOMESALEPRODUCTS_PANEL extends PRODUCTS_PANEL
{
	public function SetPanelSettings()
	{
		$count = 0;
		$GLOBALS['SNIPPETS']['HomeSaleProducts'] = '';

		if (GetConfig('HomeNewProducts') == 0) {
			$this->DontDisplay = true;
			return;
		}

		if(!GetConfig('ShowProductRating')) {
			$GLOBALS['HideProductRating'] = "display: none";
		}

		$query = $this->getProductQuery(
			'p.prodsaleprice != 0 AND p.prodsaleprice < p.prodprice',
			'RAND()',
			getConfig('HomeNewProducts')
		);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$GLOBALS['AlternateClass'] = '';
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$this->setProductGlobals($row);
			$GLOBALS['SNIPPETS']['HomeSaleProducts'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("HomeSaleProductsItem");
		}
		if(!$GLOBALS['SNIPPETS']['HomeSaleProducts']) {
			$this->DontDisplay = true;
			return;
		}
	}
}