<?php
class ISC_CATEGORYPAGINGTOPPOINTS_PANEL extends PANEL
{
	public function SetPanelSettings()
	{
		if (!self::generatePagingPanel()) {
			$this->DontDisplay = true;
		}
	}

	public static function generatePagingPanel()
	{
		// Do we need to show paging, etc?
		if($GLOBALS['ISC_CLASS_CATEGORY']->GetNumProducts() <= GetConfig('CategoryProductsPerPage')) {
			return false;
		}

		// Workout the paging data
		$GLOBALS['SNIPPETS']['PagingData'] = "";

		$maxPagingLinks = 5;
		if($GLOBALS['ISC_CLASS_TEMPLATE']->getIsMobileDevice()) {
			$maxPagingLinks = 3;
		}

		//$start = max($GLOBALS['ISC_CLASS_CATEGORY']->GetPage()-$maxPagingLinks,1);
		//$end = min($GLOBALS['ISC_CLASS_CATEGORY']->GetPage()+$maxPagingLinks, $GLOBALS['ISC_CLASS_CATEGORY']->GetNumPages());
		$start = 1;
		$end = $GLOBALS['ISC_CLASS_CATEGORY']->GetNumPages();

		$queryStringAppend = array(
			'sort' => $GLOBALS['ISC_CLASS_CATEGORY']->getSort(),
		);

		if(!empty($_GET['price_min'])) {
			$queryStringAppend['price_min'] = (float)$_GET['price_min'];
		}

		if(!empty($_GET['price_max'])) {
			$queryStringAppend['price_max'] = (float)$_GET['price_max'];
		}

		for ($page = $start; $page <= $end; $page++) {

			if (($page == 1) || ($page == $GLOBALS['ISC_CLASS_CATEGORY']->GetNumPages())) {
				if ($page == $GLOBALS['ISC_CLASS_CATEGORY']->GetPage()) {
					$snippet = "CategoryPagingItemCurrent";
				}
				else {
					$snippet = "CategoryPagingItem";
				}
				
			}
			else {
				if($page == $GLOBALS['ISC_CLASS_CATEGORY']->GetPage()) {
					$snippet = "CategoryPagingItemCurrent";
				}
				else if (($page == ($GLOBALS['ISC_CLASS_CATEGORY']->GetPage()-1)) || ($page == ($GLOBALS['ISC_CLASS_CATEGORY']->GetPage()+1))) {
					$snippet = "CategoryPagingItem";
				}
				else if (($page == ($start+1)) || ($page == ($end-1))) {
					$snippet = "CategoryPagingItemPoint";
				}
				else {
					$snippet = "CategoryPagingItemSpace";
				}
			}
			
				$pageQueryStringAppend = $queryStringAppend;
				$pageQueryStringAppend['page'] = $page;
				$GLOBALS['PageLink'] = CatLink($GLOBALS['CatId'], $GLOBALS['ISC_CLASS_CATEGORY']->GetName(), false, $pageQueryStringAppend);
				$GLOBALS['PageNumber'] = $page;
				$GLOBALS['SNIPPETS']['PagingData'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet($snippet);
		}

		// Parse the paging snippet
		if($GLOBALS['ISC_CLASS_CATEGORY']->GetPage() > 1) {
			// Do we need to output a "Previous" link?
			$pageQueryStringAppend = $queryStringAppend;
			//$pageQueryStringAppend['page'] = $GLOBALS['ISC_CLASS_CATEGORY']->getPage() - 1;
			$pageQueryStringAppend['page'] = 1;
			$GLOBALS['FirstLink'] = CatLink($GLOBALS['CatId'], $GLOBALS['ISC_CLASS_CATEGORY']->GetName(), false, $pageQueryStringAppend);
			$GLOBALS['SNIPPETS']['CategoryPagingFirst'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPagingFirst");
			
			// Do we need to output a "First" link?
			$pageQueryStringAppend['page'] = $GLOBALS['ISC_CLASS_CATEGORY']->getPage() - 1;
			$GLOBALS['PrevLink'] = CatLink($GLOBALS['CatId'], $GLOBALS['ISC_CLASS_CATEGORY']->GetName(), false, $pageQueryStringAppend);
			$GLOBALS['SNIPPETS']['CategoryPagingPrevious'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPagingPrevious");
		}

		if($GLOBALS['ISC_CLASS_CATEGORY']->GetPage() < $GLOBALS['ISC_CLASS_CATEGORY']->GetNumPages()) {
			// Do we need to output a "Next" link?
			$pageQueryStringAppend = $queryStringAppend;
			//$pageQueryStringAppend['page'] = $GLOBALS['ISC_CLASS_CATEGORY']->getPage() + 1;
			$pageQueryStringAppend['page'] = $GLOBALS['ISC_CLASS_CATEGORY']->GetNumPages();
			$GLOBALS['LastLink'] = CatLink($GLOBALS['CatId'], $GLOBALS['ISC_CLASS_CATEGORY']->GetName(), false, $pageQueryStringAppend);
			$GLOBALS['SNIPPETS']['CategoryPagingLast'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPagingLast");
			
			// Do we need to output a "Last" link?
			$pageQueryStringAppend['page'] = $GLOBALS['ISC_CLASS_CATEGORY']->getPage() + 1;
			$GLOBALS['NextLink'] = CatLink($GLOBALS['CatId'], $GLOBALS['ISC_CLASS_CATEGORY']->GetName(), false, $pageQueryStringAppend);
			$GLOBALS['SNIPPETS']['CategoryPagingNext'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPagingNext");
		}

		$output = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPaging");
		$output = $GLOBALS['ISC_CLASS_TEMPLATE']->ParseSnippets($output, $GLOBALS['SNIPPETS']);
		$GLOBALS['SNIPPETS']['CategoryPaging'] = $output;
		return true;
	}
}