<?php

	CLASS ISC_SEARCHTABPRODUCTS_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			if (!$GLOBALS["ISC_CLASS_SEARCH"]->searchIsLoaded()) {
				return;
			}

			// Do we have any categories
			$GLOBALS["SearchResultsCategory"] = "";

			if ($GLOBALS["ISC_CLASS_SEARCH"]->GetNumResults("category") > 0) {
				$GLOBALS["SearchResultsCategory"] = ISC_CATEGORY::buildSearchResultsHTML();
			}

			if (trim($GLOBALS["SearchResultsCategory"]) !== "") {
				$GLOBALS["HideSearchResultsCategory"] = "";
			} else {
				$GLOBALS["HideSearchResultsCategory"] = "none";
			}

			// Do we have any brands
			$GLOBALS["SearchResultsBrand"] = "";

			if ($GLOBALS["ISC_CLASS_SEARCH"]->GetNumResults("brand") > 0) {
				$GLOBALS["SearchResultsBrand"] = ISC_BRANDS::buildSearchResultsHTML();
			}

			if (trim($GLOBALS["SearchResultsBrand"]) !== "") {
				$GLOBALS["HideSearchResultsBrand"] = "";
			} else {
				$GLOBALS["HideSearchResultsBrand"] = "none";
			}

			// Now for the products
			$GLOBALS["SearchResultsProduct"] = "";
			$productSearchResults = "";

			if ($GLOBALS["ISC_CLASS_SEARCH"]->GetNumResults("product") > 0) {
				$productSearchResults = ISC_PRODUCT::buildSearchResultsHTML();
			}

			if (GetConfig("SearchProductDisplayMode") == "list") {
				$displayMode = "List";
			} else {
				$displayMode = "Grid";
			}

			if (trim($productSearchResults) !== "") {
				$GLOBALS["SectionResults"] = $productSearchResults;
				$GLOBALS["SectionType"] = "ProductList";
				$GLOBALS["SectionExtraClass"] = "";
				$GLOBALS["HideAddButton"] = "none";
				$GLOBALS["CompareButton"] = "";
				$GLOBALS["CompareButtonTop"] = "";

				$totalPages = $GLOBALS['ISC_CLASS_SEARCH']->GetNumPages("product");
				$totalRecords = $GLOBALS['ISC_CLASS_SEARCH']->GetNumResults("product");

				$page = (int)@$_REQUEST['page'];
				if ($page < 1) {
					$page = 1;
				} else if ($page > $totalPages) {
					$page = $totalPages;
				}

				if (GetConfig("SearchProductDisplayMode") == "list") {
					$GLOBALS["SectionExtraClass"] = "List";
					$GLOBALS["HideAddButton"] = "";
					$GLOBALS["ListJS"] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ListCheckForm");
					$GLOBALS["CompareButton"] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareButton" . $displayMode);

					if ($totalPages > 1) {
						$GLOBALS["CompareButtonTop"] = $GLOBALS["CompareButton"];
					}
				} else {
					$GLOBALS["CompareButton"] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareButton");
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
				$url[] = "section=product";
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

				if ($GLOBALS["ISC_CLASS_SEARCH"]->GetNumResults("product") <= 1) {
					$GLOBALS["HideSectionSorting"] = "none";
				} else {
					$GLOBALS["HideSectionSorting"] = "";
				}

				$GLOBALS["SectionSortingOptions"] = getAdvanceSearchSortOptions("product");
				$GLOBALS["SectionSearchResults"] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SearchResultGrid");
				$GLOBALS["SearchResultsProduct"] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SearchResultSectionProduct");
				$GLOBALS["HideSearchResultsProduct"] = "none";

				if(!getProductReviewsEnabled()) {
					$GLOBALS["HideProductRating"] = "display: none";
				}

			} else {
				$GLOBALS["HideSearchResultsProduct"] = "";
			}

			// If no results then show the 'no results found' div
			if (trim($GLOBALS["SearchResultsBrand"]) == "" && trim($GLOBALS["SearchResultsCategory"]) == "" && trim($GLOBALS["SearchResultsProduct"]) == "") {
				$GLOBALS["HideSearchResultsCategoryAndBrand"] = "none";
				$GLOBALS["HideSearchResultsProduct"] = "none";
				$GLOBALS["HideSearchResultsNoResult"] = "";

			// Else if we just have no categories or brands then do not show the top containing div
			} else if (trim($GLOBALS["SearchResultsBrand"]) == "" && trim($GLOBALS["SearchResultsCategory"]) == "") {
				$GLOBALS["HideSearchResultsCategoryAndBrand"] = "none";
				$GLOBALS["HideSearchResultsProduct"] = "";
				$GLOBALS["HideSearchResultsNoResult"] = "none";

			// Else if we have categories or brands BUT no products then display the 'no results found' div
			} else if ((trim($GLOBALS["SearchResultsBrand"]) == "" || trim($GLOBALS["SearchResultsCategory"]) !== "") && trim($GLOBALS["SearchResultsProduct"]) == "") {
				$GLOBALS["HideSearchResultsCategoryAndBrand"] = "";
				$GLOBALS["HideSearchResultsProduct"] = "none";
				$GLOBALS["HideSearchResultsNoResult"] = "";
			} else {
				$GLOBALS["HideSearchResultsCategoryAndBrand"] = "";
				$GLOBALS["HideSearchResultsProduct"] = "";
				$GLOBALS["HideSearchResultsNoResult"] = "none";
			}

			/*
			 * if the "Enable Product Search Feeds?" is ticked in Store
			 * Settings -> Display and we are searching add the link
			 */
			if (isset($GLOBALS['ISC_CLASS_SEARCH']) && GetConfig('RSSProductSearches')) {
				$GLOBALS['RSSURL'] = SearchLink($GLOBALS['ISC_CLASS_SEARCH']->GetQuery(), 0, false);
				$GLOBALS['SnippetSearchResultsFeed'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('SearchResultsFeed');
			}
		}
	}