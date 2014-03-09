<?php
/**
 * Top Selling Products Panel
 *
 * This panel will show the top selling products
 * for the entire store, or if you're viewing
 * a vendor profile, the top selling products
 * for the current vendor.
 */
class ISC_SIDETOPSELLERS_PANEL extends PRODUCTS_PANEL
{
	/**
	 * Set the panel settings
	 */
	public function SetPanelSettings()
	{
		$count = 1;

		if(!GetConfig('ShowProductRating')) {
			$GLOBALS['HideProductRating'] = "display: none";
		}

		$output = "";

		$vendorRestriction = '';

		// If we're on a vendor page, only show top sellers from this particular vendor
		if(isset($GLOBALS['ISC_CLASS_VENDORS'])) {
			$vendor = $GLOBALS['ISC_CLASS_VENDORS']->GetVendor();
			$vendorRestriction = " AND p.prodvendorid='".(int)$vendor['vendorid']."'";
		}

		$query = $this->getProductQuery(
			'p.prodnumsold > 0 '.$vendorRestriction,
			'p.prodnumsold DESC',
			5
		);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$GLOBALS['AlternateClass'] = '';
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			// Use the SideTopSellersFirst snippet for the first product only
			if($count == 1) {
				$snippet = "SideTopSellersFirst";
			}
			else {
				$snippet = "SideTopSellers";
			}

			$GLOBALS['ProductNumber'] = $count++;

			$this->setProductGlobals($row);

			$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet($snippet);
		}

		if ($count == 2) { // if only one product then we need to clear the list by adding an empty list item otherwise the layout can be broken
			$output .= "<li></li>";
		}

		$GLOBALS['SNIPPETS']['SideTopSellers'] = $output;

		if(!$output) {
			$this->DontDisplay = true;
		}
	}
}