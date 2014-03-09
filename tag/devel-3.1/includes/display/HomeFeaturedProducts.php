<?php
class ISC_HOMEFEATUREDPRODUCTS_PANEL extends PRODUCTS_PANEL
{
	public function SetPanelSettings()
	{
		$count = 0;
		$GLOBALS['SNIPPETS']['HomeFeaturedProducts'] = '';

		if (GetConfig('HomeFeaturedProducts') <= 0) {
			$this->DontDisplay = true;
			return;
		}

		if(!GetConfig('ShowProductRating')) {
			$GLOBALS['HideProductRating'] = "display: none";
		}

		$GLOBALS['AlternateClass'] = '';

		$query = $this->getProductQuery('p.prodfeatured=1', 'RAND()', getConfig('HomeFeaturedProducts'));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$this->setProductGlobals($row);
			$GLOBALS['SNIPPETS']['HomeFeaturedProducts'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("HomeFeaturedProductsItem");
		}

		if(!$GLOBALS['SNIPPETS']['HomeFeaturedProducts']) {
			$this->DontDisplay = true;
		}

		// Showing the syndication option?
		if(GetConfig('RSSFeaturedProducts') != 0 && GetConfig('RSSSyndicationIcons') != 0) {
			$GLOBALS['SNIPPETS']['HomeFeaturedProductsFeed'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("HomeFeaturedProductsFeed");
		}
	}
}