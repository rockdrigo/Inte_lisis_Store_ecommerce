<?php

class ISC_SIDENEWPRODUCTS_PANEL extends PRODUCTS_PANEL
{
	public function SetPanelSettings()
	{
		$output = "";
		// If ratings are disabled, hide them
		if(!GetConfig('ShowProductRating')) {
			$GLOBALS['HideProductRating'] = "display: none";
		}

		$query = $this->getProductQuery('', 'p.proddateadded DESC',	5);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$GLOBALS['AlternateClass'] = '';
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$this->setProductGlobals($row);
			$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SideNewProducts");
		}

		// Showing the syndication option?
		if(GetConfig('RSSNewProducts') != 0 && GetConfig('RSSSyndicationIcons') != 0) {
			$GLOBALS['SNIPPETS']['SideNewProductsFeed'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SideNewProductsFeed");
		}

		if(!$output) {
			$this->DontDisplay = true;
			return;
		}

		$GLOBALS['SNIPPETS']['SideNewProducts'] = $output;
	}
}