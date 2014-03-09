<?php

	CLASS ISC_SEARCHTABCONTENTS_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			if (!$GLOBALS["ISC_CLASS_SEARCH"]->searchIsLoaded()) {
				return;
			}

			// Do we have any pages and news items?
			$GLOBALS["SearchResultsContent"] = "";
			$contentSearchResults = "";
			$searchResults = $GLOBALS["ISC_CLASS_SEARCH"]->GetResults("content");
			$contentSearchResults = "";

			if (!empty($searchResults["results"]) && is_array($searchResults["results"])) {
				foreach ($searchResults["results"] as $item) {
					if ($item["nodetype"] == "page") {
						$contentSearchResults .= ISC_PAGE::buildContentSearchResultHTML($item);
					} else {
						$contentSearchResults .= ISC_NEWS::buildContentSearchResultHTML($item);
					}
				}
			}

			if (trim($contentSearchResults) !== "") {
				$GLOBALS["SectionResults"] = $contentSearchResults;
				$GLOBALS["SectionType"] = "ContentList";
				$GLOBALS["SectionExtraClass"] = "";

				$totalPages = $GLOBALS['ISC_CLASS_SEARCH']->GetNumPages("content");
				$totalRecords = $GLOBALS['ISC_CLASS_SEARCH']->GetNumResults("content");

				$page = (int)@$_REQUEST['page'];
				if ($page < 1) {
					$page = 1;
				} else if ($page > $totalPages) {
					$page = $totalPages;
				}

				// generate url with all current GET params except page, ajax and section
				$url = array();
				foreach ($_GET as $key => $value) {
					if ($key == 'page' || $key == 'ajax' || $key == 'section') {
						continue;
					}
					if (is_array($value)) {
						foreach ($value as $subvalue) {
							$url[] = urlencode($key . '[]') . '=' . urlencode($subvalue);
						}
					} else {
						$url[] = urlencode($key) . '=' . urlencode($value);
					}
				}
				$url[] = "page={page}";
				$url[] = "section=content";
				$url = 'search.php?' . implode('&', $url) . '#results';

				$GLOBALS['SectionPaging'] = '';

				$maxPagingLinks = 5;
				if($GLOBALS['ISC_CLASS_TEMPLATE']->getIsMobileDevice()) {
					$maxPagingLinks = 3;
				}

				$start = max($page - $maxPagingLinks, 1);
				$end = min($page + $maxPagingLinks, $totalPages);

				for ($i = $start; $i <= $end; $i++) {
					if($i == $page) {
						$snippet = "CategoryPagingItemCurrent";
					}
					else {
						$snippet = "CategoryPagingItem";
					}

					$GLOBALS['PageLink'] = str_replace('{page}', $i, $url);
					$GLOBALS['PageNumber'] = $i;
					$GLOBALS['SectionPaging'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet($snippet);
				}

				// Parse the paging snippet
				if($page > 1) {
					$prevPage = $page - 1;
					$GLOBALS['PrevLink'] = str_replace('{page}', $prevPage, $url);
					$GLOBALS['SectionPagingPrevious'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPagingPrevious");
				}

				if($page < $totalPages) {
					$prevPage = $page + 1;
					$GLOBALS['NextLink'] = str_replace('{page}', $prevPage, $url);
					$GLOBALS['SectionPagingNext'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPagingNext");
				}

				if ($totalPages > 1) {
					$GLOBALS["HideSectionPaging"] = "";
				} else {
					$GLOBALS["HideSectionPaging"] = "none";
				}

				if ($GLOBALS["ISC_CLASS_SEARCH"]->GetNumResults("content") <= 1) {
					$GLOBALS["HideSectionSorting"] = "none";
				} else {
					$GLOBALS["HideSectionSorting"] = "";
				}

				$GLOBALS["SectionSortingOptions"] = getAdvanceSearchSortOptions("content");
				$GLOBALS["SectionSearchResults"] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SearchResultGrid");
				$GLOBALS["SearchResultsContent"] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SearchResultSectionContent");
			}

			// If no results then show the 'no results found' div
			if (trim($GLOBALS["SearchResultsContent"]) !== "") {
				$GLOBALS["HideSearchResultsContent"] = "";
				$GLOBALS["HideSearchResultsNoResult"] = "none";
			} else {
				$GLOBALS["HideSearchResultsContent"] = "none";
				$GLOBALS["HideSearchResultsNoResult"] = "";
			}
		}
	}