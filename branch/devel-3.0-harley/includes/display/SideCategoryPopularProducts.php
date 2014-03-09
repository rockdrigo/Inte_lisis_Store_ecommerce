<?php

class ISC_SIDECATEGORYPOPULARPRODUCTS_PANEL extends PRODUCTS_PANEL
{
	public $cacheable = true;
	public $cacheId = "categories.products.sidepopularproducts";

	public function __construct()
	{
		$this->cacheId .= ".".$GLOBALS['CatId'];
	}

	public function SetPanelSettings()
	{
		$output = "";

		// If product ratings aren't enabled then we don't even need to load anything here
		if(!getProductReviewsEnabled()) {
			$this->DontDisplay = true;
			return;
		}

		$categorySql = $GLOBALS['ISC_CLASS_CATEGORY']->GetCategoryAssociationSQL(false);
		$query = $this->getProductQuery('prodratingtotal > 0 AND '.$categorySql, 'p.prodratingtotal DESC', 5);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
			$GLOBALS['AlternateClass'] = '';
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$this->setProductGlobals($row);
				$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SideCategoryPopularProducts");
			}

			// Showing the syndication option?
			if(GetConfig('RSSPopularProducts') != 0 && GetConfig('RSSCategories') != 0 && GetConfig('RSSSyndicationIcons') != 0) {
				$GLOBALS['ISC_LANG']['CategoryPopularProductsFeed'] = sprintf(GetLang('CategoryPopularProductsFeed'), isc_html_escape($GLOBALS['CatName']));
				$GLOBALS['SNIPPETS']['SideCategoryPopularProductsFeed'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SideCategoryPopularProductsFeed");
			}
		}
		else {
			$GLOBALS['HideSideCategoryPopularProductsPanel'] = "none";
			$this->DontDisplay = true;
		}

		$GLOBALS['SNIPPETS']['SideCategoryPopularProducts'] = $output;
	}
}