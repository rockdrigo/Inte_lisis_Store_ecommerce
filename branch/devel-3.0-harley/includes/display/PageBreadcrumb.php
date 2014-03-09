<?php
/*******************************************\
 *                                         *
 *  Generic Interspire Shopping Cart Panel Parsing Class *
 *                                         *
\*******************************************/

CLASS ISC_PAGEBREADCRUMB_PANEL extends PANEL
{
	public function SetPanelSettings()
	{
		// Build the breadcrumb trail to this page
		$parentlist = $GLOBALS['ISC_CLASS_PAGE']->GetPageParentList();
		$pageid = $GLOBALS['ISC_CLASS_PAGE']->GetPageId();

		if(isset($GLOBALS['ISC_CLASS_VENDORS'])) {
			$vendor = $GLOBALS['ISC_CLASS_VENDORS']->GetVendor();
		}
		else {
			$vendor = array();
		}

		$GLOBALS['SNIPPETS']['PageBreadcrumb'] = '';

		if($parentlist != '') {
			$query = sprintf("SELECT pageid, pagetitle, pagelink, pagetype, pageparentid FROM [|PREFIX|]pages WHERE pageid IN (%s) OR pageid='%d'", $parentlist, $pageid);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$parentPages[$row['pageid']] = $row;
			}

			// Now we have the list we can generate the breadcrumb trail
			$parentid = $pageid;
			do {
				if(!isset($parentPages[$parentid])) {
					break;
				}
				$page = $parentPages[$parentid];
				$GLOBALS['CatTrailName'] = isc_html_escape($page['pagetitle']);

				// Is it a normal page, external page or RSS feed?
				switch($page['pagetype']) {
					case 0:
					case 2:
					case 3:{ // Normal Page or RSS feed
						$GLOBALS['CatTrailLink'] = PageLink($page['pageid'], $page['pagetitle'], $vendor);
						break;
					}
					case 1: { // External Link
						$GLOBALS['CatTrailLink'] = $page['pagelink'];
						break;
					}
				}

				if($parentid == $pageid) {
					$item = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("BreadcrumbItemCurrent");
				} else {
					$item = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("BreadcrumbItem");
				}

				$GLOBALS['SNIPPETS']['PageBreadcrumb'] = $item . $GLOBALS['SNIPPETS']['PageBreadcrumb'];

				$parentid = $page['pageparentid'];
			}
			while($parentid != 0);
		}

		if(!empty($vendor)) {
			$GLOBALS['CatTrailName'] = isc_html_escape($vendor['vendorname']);
			$GLOBALS['CatTrailLink'] = VendorLink($vendor);
			$GLOBALS['SNIPPETS']['PageBreadcrumb'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("BreadcrumbItem") . $GLOBALS['SNIPPETS']['PageBreadcrumb'];
		}
	}
}