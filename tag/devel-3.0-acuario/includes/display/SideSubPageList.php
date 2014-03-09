<?php

CLASS ISC_SIDESUBPAGELIST_PANEL extends PANEL
{
	public function SetPanelSettings()
	{

		$pageid = $GLOBALS['ISC_CLASS_PAGE']->GetPageId();

		if($pageid == 0) {
			$this->DontDisplay = true;
			return false;
		}

		$GLOBALS['PageLinks'] = '';

		// If the customer is not logged in then they can only see pages that aren't restricted to 'customers only'
		$customersOnly = '';

		$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
		if(!$GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId()) {
			$customersOnly = ' AND pagecustomersonly=0';
		}

		// Are we fetching pages for a specific vendor only?
		$vendorOnly = '';
		if(isset($GLOBALS['ISC_CLASS_VENDOR'])) {
			$vendor = $GLOBALS['ISC_CLASS_VENDOR']->GetVendor();
			$vendorOnly = " AND pagevendorid='".(int)$vendor['vendorid']."'";
		}

		// Fetch any pages that this page is a parent of
		$query = "
			SELECT *
			FROM [|PREFIX|]pages
			WHERE pagestatus='1' AND pageparentid='".(int)$pageid."' ".$customersOnly." ".$vendorOnly."
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
					if(isset($vendor)) {
						$GLOBALS['PageLink'] = PageLink($page['pageid'], $page['pagetitle'], $vendor);
					}
					else {
						$GLOBALS['PageLink'] = PageLink($page['pageid'], $page['pagetitle']);
					}
					break;
				}
				case 1: { // External Link
					$GLOBALS['PageLink'] = $page['pagelink'];
					break;
				}
			}
			$GLOBALS['PageLinks'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('SidePageLink');
		}

		if($GLOBALS['PageLinks'] == '') {
			$GLOBALS['HideSubPagesList'] = 'none';
			$this->DontDisplay = true;
		}

		$GLOBALS['PageTitle'] = isc_html_escape($GLOBALS['ISC_CLASS_PAGE']->GetPageTitle());
	}
}