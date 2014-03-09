<?php

CLASS ISC_SIDEPOPULARVENDORS_PANEL extends PANEL
{
	public function SetPanelSettings()
	{
		if(!gzte11(ISC_HUGEPRINT)) {
			$this->DontDisplay = true;
			return false;
		}

		$output = "";

		// Get the link to the 'all vendors' page
		$GLOBALS['AllVendorsLink'] = VendorLink();

		// Get the 10 most popular vendors
		$query = "
			SELECT vendorid, vendorname, vendorfriendlyname
			FROM [|PREFIX|]vendors
			ORDER BY vendornumsales DESC, vendorname ASC
		";
		$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, 11); // Fetch 10 + 1 - so that way we can determine if we need the all vendors link
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$x = 1;
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			if($x <= 10) {
				$GLOBALS['VendorLink'] = VendorLink($row);
				$GLOBALS['VendorName'] = isc_html_escape($row['vendorname']);
				$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("PopularVendorsItem");
			}
			++$x;
		}

		if($x == 11) {
			$GLOBALS['SNIPPETS']['ShopByVendorAllItem'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("PopularVendorsAllItem");
		}

		if(!$output) {
			$this->DontDisplay = true;
		}

		$GLOBALS['SNIPPETS']['PopularVendorsList'] = $output;
	}
}