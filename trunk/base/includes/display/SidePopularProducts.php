<?php
class ISC_SIDEPOPULARPRODUCTS_PANEL extends PRODUCTS_PANEL
{
	public $cacheable = true;
	public $cacheId = "products.sidepopularproducts";

	public function SetPanelSettings()
	{
		$output = "";

		// If product ratings aren't enabled then we don't even need to load anything here
		if(!getProductReviewsEnabled()) {
			$this->DontDisplay = true;
			return;
		}

		$query = $this->getProductQuery('p.prodratingtotal > 0', 'p.prodratingtotal DESC', 5);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		if($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
			$GLOBALS['AlternateClass'] = '';
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$this->setProductGlobals($row);
				$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SidePopularProducts");
			}

			// Showing the syndication option?
			if(GetConfig('RSSPopularProducts') != 0 && GetConfig('RSSSyndicationIcons') != 0) {
				$GLOBALS['SNIPPETS']['SidePopularProductsFeed'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SidePopularProductsFeed");
			}
		}
		else {
			$GLOBALS['HideSidePopularProductsPanel'] = "none";
			$this->DontDisplay = true;
		}

		$GLOBALS['SNIPPETS']['SidePopularProducts'] = $output;
	}
}