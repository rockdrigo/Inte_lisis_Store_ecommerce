<?php
class ISC_HOMENEWPRODUCTS_PANEL extends PRODUCTS_PANEL
{
	public $cacheable = true;
	public $cacheId = "products.homenewproducts";

	public function SetPanelSettings()
	{
		$count = 0;
		$output = "";
		$GLOBALS['SNIPPETS']['HomeNewProducts'] = '';

		if(GetConfig('HomeNewProducts') <= 0) {
			$this->DontDisplay = true;
			return;
		}

		$GLOBALS['AlternateClass'] = '';
		if(!GetConfig('ShowProductRating')) {
			$GLOBALS['HideProductRating'] = "display: none";
		}

		$query = $this->getProductQuery('', 'proddateadded DESC', getConfig('HomeNewProducts'));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$this->setProductGlobals($row);
			$GLOBALS['SNIPPETS']['HomeNewProducts'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("HomeNewProductsItem");
		}

		if(!$GLOBALS['SNIPPETS']['HomeNewProducts']) {
			$this->DontDisplay = true;
		}

		// Showing the syndication option?
		if(GetConfig('RSSNewProducts') != 0 && GetConfig('RSSSyndicationIcons') != 0) {
			$GLOBALS['SNIPPETS']['HomeNewProductsFeed'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("HomeNewProductsFeed");
		}
	}
}