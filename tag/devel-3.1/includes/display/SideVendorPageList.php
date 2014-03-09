<?php
class ISC_SIDEVENDORPAGELIST_PANEL extends PANEL
{
	public function SetPanelSettings()
	{
		if(isset($GLOBALS['ISC_CLASS_PRODUCT'])) {
			$vendor = GetClass('ISC_PRODUCT')->GetProductVendor();
		}
		else {
			$cVendor = GetClass('ISC_VENDORS');
			$vendor = $cVendor->GetVendor();
		}
		$GLOBALS['VendorName'] = isc_html_escape($vendor['vendorname']);
		$GLOBALS['VendorHomeLink'] = VendorLink($vendor);
		$GLOBALS['VendorProductsLink'] = VendorProductsLink($vendor);

		// Does this vendor have any products?
		$query = "
			SELECT productid
			FROM [|PREFIX|]products
			WHERE prodvendorid='".(int)$vendor['vendorid']."'
			LIMIT 1
		";
		$hasProducts = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);
		if(!$hasProducts) {
			$GLOBALS['HideVendorProductsLink'] = 'display: none';
		}

		// Fetch out any pages belonging to this vendor to show in the menu
		$GLOBALS['PageLinks'] = '';

		// If the customer is not logged in then they can only see pages that aren't restricted to 'customers only'
		$customersOnly = '';

		$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
		if(!$GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId()) {
			$customersOnly = ' AND pagecustomersonly=0';
		}

		// Fetch any pages that this page is a parent of
		$query = "
			SELECT *
			FROM [|PREFIX|]pages
			WHERE pagestatus='1' AND pageparentid='0' AND pagevendorid='".(int)$vendor['vendorid']."' ".$customersOnly."
			ORDER BY pagesort ASC, pagetitle ASC
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($page = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$GLOBALS['PageName'] = isc_html_escape($page['pagetitle']);

			// Is it a normal page, external page or RSS feed?
			switch($page['pagetype']) {
				case 0:
				case 2:
				case 3:{ // Normal Page or RSS feed

					$GLOBALS['PageLink'] = PageLink($page['pageid'], $page['pagetitle'], $vendor);
					break;
				}
				case 1: { // External Link
					$GLOBALS['PageLink'] = $page['pagelink'];
					break;
				}
			}
			$GLOBALS['PageLinks'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('SidePageLink');
		}
	}
}