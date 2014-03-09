<?php
/**
 * Show a listing of products belonging to a specific vendor.
 */
class ISC_VENDORPRODUCTS_PANEL extends PRODUCTS_PANEL
{
	/**
	 * Set the panel settings.
	 */
	public function SetPanelSettings()
	{
		$cVendor = GetClass('ISC_VENDORS');
		$vendor = $cVendor->GetVendor();

		$GLOBALS['VendorId'] = $vendor['vendorid'];
		$GLOBALS['VendorName'] = $vendor['vendorname'];

		// Set the field we're sorting results by
		if(isset($_REQUEST['sort'])) {
			$sort = $_REQUEST['sort'];
		}
		else {
			$sort = '';
		}

		switch($sort) {
			case 'newest':
				$sortField = 'p.productid DESC';
				$GLOBALS['SortNewestSelected'] = 'selected="selected"';
				break;
			case 'bestselling':
				$sortField = 'p.prodnumsold DESC';
				$GLOBALS['SortBestSellingSelected'] = 'selected="selected"';
				break;
			case 'alphaasc':
				$sortField = 'p.prodname ASC';
				$GLOBALS['SortAlphaAsc'] = 'selected="selected"';
				break;
			case 'alphadesc':
				$sortField = 'p.prodname DESC';
				$GLOBALS['SortAlphaDesc'] = 'selected="selected"';
				break;
			case 'avgcustomerreview':
				$sortField = 'prodavgrating DESC';
				$GLOBALS['SortAvgReview'] = 'selected="selected"';
				break;
			case 'priceasc':
				$sortField = 'p.calculated_price ASC';
				$GLOBALS['SortPriceAsc'] = 'selected="selected"';
				break;
			case 'pricedesc';
				$sortField = 'p.calculated_price DESC';
				$GLOBALS['SortPriceDesc'] = 'selected="selected"';
				break;
			default:
				$sortField = 'p.prodvendorfeatured DESC';
				$sort = 'featured';
				$GLOBALS['SortFeaturedSelected'] = 'selected="selected"';
				break;
		}

		// If we're viewing a certain page, fetch our starting position
		if(isset($_REQUEST['page']) && IsId($_REQUEST['page'])) {
			$page = (int)$_REQUEST['page'];
			$start = ($page * GetConfig('CategoryProductsPerPage')) - GetConfig('CategoryProductsPerPage');
		}
		else {
			$page = 1;
			$start = 0;
		}

		// Count the number of products that belong in this vendor
		$query = "
			SELECT COUNT(p.productid) AS numproducts
			FROM [|PREFIX|]products p
			".GetProdCustomerGroupPermissionsSQL()."
			WHERE p.prodvisible='1' AND p.prodvendorid='".(int)$vendor['vendorid']."'
		";
		$numProducts = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);
		$numPages = ceil($numProducts / GetConfig('CategoryProductsPerPage'));

		// Now load the actual products for this vendor
		$query = $this->getProductQuery(
			'p.prodvendorid='.(int)$vendor['vendorid'],
			$sortField.', prodname ASC',
			getConfig('CategoryProductsPerPage'),
			$start
		);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$GLOBALS['SNIPPETS']['VendorProducts'] = '';

		if(!GetConfig('ShowProductRating')) {
			$GLOBALS['HideProductRating'] = "display: none";
		}

		// Should we show the compare button?
		if(GetConfig('EnableProductComparisons') == 0 || $numProducts < 2) {
			$GLOBALS['HideCompareItems'] = "none";
		}
		else {
			$GLOBALS['CompareLink'] = CompareLink();
		}

		$GLOBALS['AlternateClass'] = '';
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$this->setProductGlobals($row);
			$GLOBALS['SNIPPETS']['VendorProducts'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("VendorProductsItem");
		}

		// Does paging need to be shown?
		if($numProducts > GetConfig('CategoryProductsPerPage')) {
			$GLOBALS['SNIPPETS']['PagingData'] = "";

			$numEitherSide = 5;
			$start = max($page-$numEitherSide,1);
			$end = min($page+$numEitherSide, $numPages);

			for($i = $start; $i <= $end; $i++) {
				if ($i == $page) {
					$snippet = "CategoryPagingItemCurrent";
				}
				else {
					$snippet = "CategoryPagingItem";
				}

				$pageData = array(
					'page' => $i,
					'sort' => $sort
				);
				$GLOBALS['PageLink'] = VendorProductsLink($vendor, $pageData);
				$GLOBALS['PageNumber'] = $i;
				$GLOBALS['SNIPPETS']['PagingData'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet($snippet);
			}

			// Do we need to output a "Previous" link?
			if($page > 1) {
				$pageData = array(
					'page' => $page-1,
					'sort' => $sort
				);
				$GLOBALS['PrevLink'] = VendorProductsLink($vendor, $pageData);
				$GLOBALS['SNIPPETS']['CategoryPagingPrevious'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPagingPrevious");
			}

			// Do we need to output a "Next" link?
			if($page < $numPages) {
				$pageData = array(
					'page' => $page+1,
					'sort' => $sort
				);
				$GLOBALS['NextLink'] = VendorProductsLink($vendor, $pageData);
				$GLOBALS['SNIPPETS']['CategoryPagingNext'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPagingNext");
			}

			$output = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPaging");
			$output = $GLOBALS['ISC_CLASS_TEMPLATE']->ParseSnippets($output, $GLOBALS['SNIPPETS']);
			$GLOBALS['SNIPPETS']['ProductPaging'] = $output;
		}

		// Parse the sort select box snippet
		if($numProducts > 1) {

			// Parse the sort select box snippet
			if($GLOBALS['EnableSEOUrls'] == 1 && $vendor['vendorfriendlyname']) {
				$GLOBALS['URL'] = VendorProductsLink($vendor);
			}
			else {
				$GLOBALS['URL'] = $GLOBALS['ShopPath']."/vendors.php";
				$GLOBALS['HiddenSortField'] = "<input type=\"hidden\" name=\"vendorid\" value=\"".(int)$vendor['vendorid']."\" />";
				$GLOBALS['HiddenSortField'] .= "<input type=\"hidden\" name=\"action\" value=\"products\" />";
			}

			$GLOBALS['SNIPPETS']['CategorySortBox'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategorySortBox");
		}
	}
}