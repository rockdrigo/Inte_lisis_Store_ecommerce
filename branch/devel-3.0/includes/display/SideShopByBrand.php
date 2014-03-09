<?php

CLASS ISC_SIDESHOPBYBRAND_PANEL extends PANEL
{
	public function SetPanelSettings()
	{
		$output = "";

		// Get the link to the "all brands" page
		$GLOBALS['AllBrandsLink'] = BrandLink();

		// Get the 10 most popular brands
		$query = "SELECT brandid, brandname, COUNT(*) AS num
			FROM [|PREFIX|]brands b, [|PREFIX|]products p
			WHERE p.prodbrandid = b.brandid
			AND prodvisible=1
			GROUP BY prodbrandid
			ORDER BY num DESC, brandname ASC
			";
		$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, 11);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$x = 1;
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			if($x <= 10) {
				$GLOBALS['BrandLink'] = BrandLink($row['brandname']);
				$GLOBALS['BrandName'] = isc_html_escape($row['brandname']);
				$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ShopByBrandItem");
			}
			++$x;
		}

		if($x == 12) {
			$GLOBALS['SNIPPETS']['ShopByBrandAllItem'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ShopByBrandAllItem");
		}

		if(!$output) {
			$this->DontDisplay = true;
		}

		$output = $GLOBALS['ISC_CLASS_TEMPLATE']->ParseSnippets($output, $GLOBALS['SNIPPETS']);
		$GLOBALS['SNIPPETS']['SideShopByBrandList'] = $output;
	}
}