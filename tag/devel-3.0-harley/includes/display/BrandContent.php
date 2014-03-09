<?php
	CLASS ISC_BRANDCONTENT_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{

			// Do we need to show paging?
			if($GLOBALS['ISC_CLASS_BRANDS']->GetNumProducts() > GetConfig('CategoryProductsPerPage')) {
				// Workout the paging data
				$GLOBALS['SNIPPETS']['PagingData'] = "";

				$num_pages_either_side_of_current = 5;
				if($GLOBALS['ISC_CLASS_TEMPLATE']->getIsMobileDevice()) {
					$num_pages_either_side_of_current = 3;
				}

				$start = max($GLOBALS['ISC_CLASS_BRANDS']->GetPage()-$num_pages_either_side_of_current,1);
				$end = min($GLOBALS['ISC_CLASS_BRANDS']->GetPage()+$num_pages_either_side_of_current, $GLOBALS['ISC_CLASS_BRANDS']->GetNumPages());

				for ($page = $start; $page <= $end; $page++) {
					if ($page == $GLOBALS['ISC_CLASS_BRANDS']->GetPage()) {
						$snippet = "CategoryPagingItemCurrent";
					} else {
						$snippet = "CategoryPagingItem";
					}

					$GLOBALS['PageLink'] = BrandLink($GLOBALS['ISC_CLASS_BRANDS']->GetBrandName(), array('page' => $page, 'sort' => $GLOBALS['ISC_CLASS_BRANDS']->GetSort()));
					$GLOBALS['PageNumber'] = $page;
					$GLOBALS['SNIPPETS']['PagingData'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet($snippet);
				}

				// Parse the paging snippet
				if($GLOBALS['ISC_CLASS_BRANDS']->GetPage() > 1) {
					// Do we need to output a "Previous" link?
					$GLOBALS['PrevLink'] = BrandLink($GLOBALS['ISC_CLASS_BRANDS']->GetBrandName(), array('page' => $GLOBALS['ISC_CLASS_BRANDS']->GetPage()-1, 'sort' => $GLOBALS['ISC_CLASS_BRANDS']->GetSort()));
					$GLOBALS['SNIPPETS']['CategoryPagingPrevious'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPagingPrevious");
				}

				if($GLOBALS['ISC_CLASS_BRANDS']->GetPage() < $GLOBALS['ISC_CLASS_BRANDS']->GetNumPages()) {
					// Do we need to output a "Next" link?
					$GLOBALS['NextLink'] = BrandLink($GLOBALS['ISC_CLASS_BRANDS']->GetBrandName(), array('page' => $GLOBALS['ISC_CLASS_BRANDS']->GetPage()+1, 'sort' => $GLOBALS['ISC_CLASS_BRANDS']->GetSort()));
					$GLOBALS['SNIPPETS']['CategoryPagingNext'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPagingNext");
				}

				$output = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPaging");
				$output = $GLOBALS['ISC_CLASS_TEMPLATE']->ParseSnippets($output, $GLOBALS['SNIPPETS']);
				$GLOBALS['SNIPPETS']['BrandPaging'] = $output;
			}

			// Should we show the compare button?
			if(GetConfig('EnableProductComparisons') == 0 || $GLOBALS['ISC_CLASS_BRANDS']->GetNumProducts() < 2) {
				$GLOBALS['HideCompareItems'] = "none";
			}

			// Parse the sort select box snippet
			if($GLOBALS['ISC_CLASS_BRANDS']->GetNumProducts() > 1) {
				// Parse the sort select box snippet
				if($GLOBALS['EnableSEOUrls'] == 1) {
					$GLOBALS['URL'] = BrandLink($GLOBALS['ISC_CLASS_BRANDS']->GetBrandName());
				}
				else {
					$GLOBALS['URL'] = $GLOBALS['ShopPath']."/brands.php";
					$GLOBALS['HiddenSortField'] = "<input type=\"hidden\" name=\"brand\" value=\"".MakeURLSafe($GLOBALS['ISC_CLASS_BRANDS']->GetBrandName())."\" />";
				}

				$GLOBALS['SNIPPETS']['CategorySortBox'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategorySortBox");
			}
		}
	}