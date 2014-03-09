<?php
class ISC_TAGPRODUCTS_PANEL extends PRODUCTS_PANEL
{
	public function SetPanelSettings()
	{
		$tagId = $GLOBALS['ISC_CLASS_TAGS']->GetTagId();
		$tagFriendlyName = $GLOBALS['ISC_CLASS_TAGS']->GetTagFriendlyName();
		$GLOBALS['TaggedProducts'] = sprintf(GetLang('ProductsTaggedWith'), $GLOBALS['ISC_CLASS_TAGS']->GetTagName());

		// Does paging need to be shown?
		if($GLOBALS['ISC_CLASS_TAGS']->GetNumProducts() > GetConfig('CategoryProductsPerPage')) {
			$GLOBALS['SNIPPETS']['PagingData'] = "";

			$numEitherSide = 5;
			if($GLOBALS['ISC_CLASS_TEMPLATE']->getIsMobileDevice()) {
				$numEitherSide = 3;
			}

			$start = max($GLOBALS['ISC_CLASS_TAGS']->GetPage()-$numEitherSide,1);
			$end = min($GLOBALS['ISC_CLASS_TAGS']->GetPage()+$numEitherSide, $GLOBALS['ISC_CLASS_TAGS']->GetNumPages());

			for($page = $start; $page <= $end; $page++) {
				if ($page == $GLOBALS['ISC_CLASS_TAGS']->GetPage()) {
					$snippet = "CategoryPagingItemCurrent";
				}
				else {
					$snippet = "CategoryPagingItem";
				}

				$pageData = array(
					'page' => $page,
					'sort' => $GLOBALS['ISC_CLASS_TAGS']->GetSort()
				);
				$GLOBALS['PageLink'] = TagLink($tagFriendlyName, $tagId, $pageData);
				$GLOBALS['PageNumber'] = $page;
				$GLOBALS['SNIPPETS']['PagingData'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet($snippet);
			}

			// Do we need to output a "Previous" link?
			if($GLOBALS['ISC_CLASS_TAGS']->GetPage() > 1) {
				$pageData = array(
					'page' => $GLOBALS['ISC_CLASS_TAGS']->GetPage()-1,
					'sort' => $GLOBALS['ISC_CLASS_TAGS']->GetSort()
				);
				$GLOBALS['PrevLink'] = TagLink($tagFriendlyName, $tagId, $pageData);
				$GLOBALS['SNIPPETS']['CategoryPagingPrevious'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPagingPrevious");
			}

			// Do we need to output a "Next" link?
			if($GLOBALS['ISC_CLASS_TAGS']->GetPage() < $GLOBALS['ISC_CLASS_TAGS']->GetNumPages()) {
				$pageData = array(
					'page' => $GLOBALS['ISC_CLASS_TAGS']->GetPage()+1,
					'sort' => $GLOBALS['ISC_CLASS_TAGS']->GetSort()
				);
				$GLOBALS['NextLink'] = TagLink($tagFriendlyName, $tagId, $pageData);
				$GLOBALS['SNIPPETS']['CategoryPagingNext'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPagingNext");
			}

			$output = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPaging");
			$output = $GLOBALS['ISC_CLASS_TEMPLATE']->ParseSnippets($output, $GLOBALS['SNIPPETS']);
			$GLOBALS['SNIPPETS']['TagPaging'] = $output;
		}

		// Should we show the compare button?
		if(GetConfig('EnableProductComparisons') == 0 || $GLOBALS['ISC_CLASS_TAGS']->GetNumProducts() < 2) {
			$GLOBALS['HideCompareItems'] = "display: none";
		}
		else {
			$GLOBALS['CompareLink'] = CompareLink();
		}

		// Parse the sort select box snippet
		if($GLOBALS['ISC_CLASS_TAGS']->GetNumProducts() > 1) {

			// Parse the sort select box snippet
			if($GLOBALS['EnableSEOUrls'] == 1) {
				$GLOBALS['URL'] = TagLink($tagFriendlyName, $tagId);
			}
			else {
				$GLOBALS['URL'] = $GLOBALS['ShopPath']."/tags.php";
				$GLOBALS['HiddenSortField'] = "<input type=\"hidden\" name=\"tagid\" value=\"".(int)$tagId."\" />";
			}

			$GLOBALS['SNIPPETS']['CategorySortBox'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategorySortBox");
		}

		// Actually load the products
		$products = $GLOBALS['ISC_CLASS_TAGS']->GetProducts();

		$GLOBALS['TagProductListing'] = '';

		// Show products for a specific tag
		if(!getProductReviewsEnabled()) {
			$GLOBALS['HideProductRating'] = "display: none";
		}

		$GLOBALS['AlternateClass'] = '';
		foreach($products as $row) {
			$this->setProductGlobals($row);
			$GLOBALS['TagProductListing'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("TagProductsItem");
		}
	}
}
