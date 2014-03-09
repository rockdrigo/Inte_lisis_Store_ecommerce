<?php

	/*******************************************\
	*                                           *
	*  Generic Interspire Shopping Cart Panel Parsing Class   *
	*                                           *
	\*******************************************/

	CLASS ISC_CATEGORYBREADCRUMB_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			$GLOBALS['ISC_CLASS_CATEGORY'] = GetClass('ISC_CATEGORY');

			// Output breadcrumb trail
			$GLOBALS['SNIPPETS']['CatTrail'] = "";

			if ($GLOBALS['EnableSEOUrls'] == 1) {
				$baseLink = sprintf("%s/categories", $GLOBALS['ShopPath']);
			} else {
				$baseLink = sprintf("%s/categories.php?category=", $GLOBALS['ShopPath']);
			}

			$count = 0;
			$catPath = '';

			foreach($GLOBALS['CatTrail'] as $trail) {
				// if it's the root category and not friendly url, don't add / in front
				if ($count==0 && $GLOBALS['EnableSEOUrls'] != 1) {
					$baseLink .= MakeURLSafe($trail[1]);
				} else {
					$baseLink .= "/" . MakeURLSafe($trail[1]);
				}
				$catPath = MakeURLSafe($trail[1]);
				$GLOBALS['CatTrailName'] = isc_html_escape($trail[1]);
				$GLOBALS['CatTrailLink'] = $baseLink."/";

				if($count++ == count($GLOBALS['CatTrail'])-1) {
					$GLOBALS['SNIPPETS']['CatTrail'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("BreadcrumbItemCurrent");
				}
				else {
					$GLOBALS['SNIPPETS']['CatTrail'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("BreadcrumbItem");
				}
			}
		}
	}