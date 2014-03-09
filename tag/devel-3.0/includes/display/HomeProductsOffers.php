<?php

	CLASS ISC_HOMEPRODUCTSOFFERS_PANEL extends PRODUCTS_PANEL
	{
		private $_numpages = 0;
		
		private $_page = 0;
		
		private $_sort = '';
		
		private function getProducts(){
			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT ".$this->getProductsTableColumns()." ipo.newprice AS 'prodsaleprice', ipo.origprice AS 'prodretailprice', ipo.newprice AS 'prodcalculatedprice', ipo.origprice as 'prodprice', pi.*, FLOOR(prodratingtotal / prodnumratings) AS prodavgrating, '.GetProdCustomerGroupPriceSQL().' 
					FROM [|PREFIX|]products p 
					LEFT JOIN [|PREFIX|]product_images pi ON (pi.imageisthumb = 1 AND p.productid = pi.imageprodid)
					JOIN [|PREFIX|]intelisis_product_offers ipo ON (p.productid = ipo.productid)
					WHERE ipo.newprice < ipo.origprice
					ORDER BY " . $this->GetSortField() . ", p.prodname ASC
						" .	$GLOBALS['ISC_CLASS_DB']->AddLimit($this->GetStart(), GetConfig('CategoryProductsPerPage')));
			
			$return = array();
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)){
				$return[] = $row;
			}
			
			return $return;
		}
		
		private function getProductsTableColumns(){
			return "p.productid,
				p.prodname,
				p.prodtype,
				p.prodcode,
				p.prodfile,
				p.proddesc,
				p.prodsearchkeywords,
				p.prodavailability,
				p.prodcostprice,
				p.prodsortorder,
				p.prodvisible,
				p.prodfeatured,
				p.prodvendorfeatured,
				p.prodrelatedproducts,
				p.prodcurrentinv,
				p.prodlowinv,
				p.prodoptionsrequired,
				p.prodwarranty,
				p.prodweight,
				p.prodwidth,
				p.prodheight,
				p.proddepth,
				p.prodfixedshippingcost,
				p.prodfreeshipping,
				p.prodinvtrack,
				p.prodratingtotal,
				p.prodnumratings,
				p.prodnumsold,
				p.proddateadded,
				p.prodbrandid,
				p.prodnumviews,
				p.prodpagetitle,
				p.prodmetakeywords,
				p.prodmetadesc,
				p.prodlayoutfile,
				p.prodvariationid,
				p.prodallowpurchases,
				p.prodhideprice,
				p.prodcallforpricinglabel,
				p.prodcatids,
				p.prodlastmodified,
				p.prodvendorid,
				p.prodhastags,
				p.prodwrapoptions,
				p.prodconfigfields,
				p.prodeventdaterequired,
				p.prodeventdatefieldname,
				p.prodeventdatelimited,
				p.prodeventdatelimitedtype,
				p.prodeventdatelimitedstartdate,
				p.prodeventdatelimitedenddate,
				p.prodmyobasset,
				p.prodmyobincome,
				p.prodmyobexpense,
				p.prodpeachtreegl,
				p.prodcondition,
				p.prodshowcondition,
				p.product_enable_optimizer,
				p.prodpreorder,
				p.prodreleasedate,
				p.prodreleasedateremove,
				p.prodpreordermessage,
				p.prodminqty,
				p.prodmaxqty,
				p.tax_class_id,
				p.opengraph_type,
				p.opengraph_title,
				p.opengraph_use_product_name,
				p.opengraph_description,
				p.opengraph_use_meta_description,
				p.opengraph_use_image,
				p.upc,
				p.disable_google_checkout,
				p.last_import,";
		}
		
		private function GetPage(){
			if (isset($_GET['page'])) {
				$start = abs((int)$_GET['page']);
			} else {
				$start = 0;
			}
			
			$this->_page = $start;
			return $this->_page;
		}
		
		private function GetStart(){
			$start = $this->getPage();
			
			switch ($start) {
				case 1: {
					$start = 0;
					break;
				}
				// Page 2 or more
				default: {
					$start = ($start * GetConfig('CategoryProductsPerPage')) - GetConfig('CategoryProductsPerPage');
					break;
				}
			}
			
			return $start;
		}
	
		private function GetSortField()
		{
			// Pre-select the current sort order (if any)
			if (isset($_GET['sort'])) {
				$this->_sort = $_GET['sort'];
			} else {
				$this->_sort = "featured";
			}

			$priceColumn = 'p.prodcalculatedprice';

			switch ($this->_sort) {
				case "featured": {
					$GLOBALS['SortFeaturedSelected'] = 'selected="selected"';
					return "p.prodsortorder asc";
					break;
				}
				case "newest": {
					$GLOBALS['SortNewestSelected'] = 'selected="selected"';
					return "p.productid desc";
					break;
				}
				case "bestselling": {
					$GLOBALS['SortBestSellingSelected'] = 'selected="selected"';
					return "p.prodnumsold desc";
					break;
				}
				case "alphaasc": {
					$GLOBALS['SortAlphaAsc'] = 'selected="selected"';
					return "p.prodname asc";
					break;
				}
				case "alphadesc": {
					$GLOBALS['SortAlphaDesc'] = 'selected="selected"';
					return "p.prodname desc";
					break;
				}
				case "avgcustomerreview": {
					$GLOBALS['SortAvgReview'] = 'selected="selected"';
					return "prodavgrating desc";
					break;
				}
				case "priceasc": {
					$GLOBALS['SortPriceAsc'] = 'selected="selected"';
					return $priceColumn.' ASC';
					break;
				}
				case "pricedesc": {
					$GLOBALS['SortPriceDesc'] = 'selected="selected"';
					return $priceColumn.' DESC';
					break;
				}
			}
		}
		
		public function SetPanelSettings()
		{

			// Load the products into the reference array
			$products = $this->getProducts();
			$GLOBALS['ProductsOffersList'] = "";

			// Should we hide the comparison button?
			if(GetConfig('EnableProductComparisons') == 0 || count($products) < 2) {
				$GLOBALS['HideCompareItems'] = "none";
			}
			
			if(GetConfig('ShowProductRating') == 0) {
				$GLOBALS['HideProductRating'] = "display: none";
			}

			$display_mode = ucfirst(GetConfig("CategoryDisplayMode"));
			if ($display_mode == "Grid") {
				$display_mode = "";
			}
			$GLOBALS['DisplayMode'] = $display_mode;

			if ($display_mode == "List") {
				if (GetConfig('ShowAddToCartLink') && count($products) > 0) {
					$GLOBALS['HideAddButton'] = '';
				} else {
					$GLOBALS['HideAddButton'] = 'none';
				}

				$GLOBALS['ListJS'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ListCheckForm");
			}

			$GLOBALS['CompareButton'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareButton" . $display_mode);

			if (GetConfig('CategoryProductsPerPage') > 0) {
				$this->_numpages = ceil(count($products) / GetConfig('CategoryProductsPerPage'));
			}
			else {
				$this->_numpages = 0;
			}
			
			if ($display_mode == "List" && $this->_numpages > 1) {
				$GLOBALS['CompareButtonTop'] = $GLOBALS['CompareButton'];
			}

			$GLOBALS['AlternateClass'] = '';
			foreach($products as $row) {
				$this->setProductGlobals($row);

				// for list style
				if ($display_mode == "List") {
					// get a small chunk of the product description
					$desc = isc_substr(strip_tags($row['proddesc']), 0, 225);
					if (isc_strlen($row['proddesc']) > 225) {
						// trim the description back to the last period or space so words aren't cut off
						$period_pos = isc_strrpos($desc, ".");
						$space_pos = isc_strrpos($desc, " ");
						// find the character that we should trim back to. -1 on space pos for a space that follows a period, so we dont end up with 4 periods
						if ($space_pos - 1 > $period_pos) {
							$pos = $space_pos;
						}
						else {
							$pos = $period_pos;
						}
						$desc = isc_substr($desc, 0, $pos);
						$desc .= "...";
					}

					$GLOBALS['ProductDescription'] = $desc;

					$GLOBALS['AddToCartQty'] = "";

					if (CanAddToCart($row) && GetConfig('ShowAddToCartLink')) {
						if (isId($row['prodvariationid']) || trim($row['prodconfigfields'])!='' || $row['prodeventdaterequired']) {
							$GLOBALS['AddToCartQty'] = '<a href="' . $GLOBALS["ProductURL"] . '">' . $GLOBALS['ProductAddText'] . "</a>";
						}
						else {
							$GLOBALS['CartItemId'] = $GLOBALS['ProductId'];
							// If we're using a cart quantity drop down, load that
							if (GetConfig('TagCartQuantityBoxes') == 'dropdown') {
								$GLOBALS['Quantity0'] = "selected=\"selected\"";
								$GLOBALS['QtyOptionZero'] = '<option %%GLOBAL_Quantity0%% value="0">'.GetLang('Quantity').'</option>';
								$GLOBALS['QtySelectStyle'] = 'width: auto;';
								$GLOBALS['AddToCartQty'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CartItemQtySelect");
							// Otherwise, load the textbox
							} else {
								$GLOBALS['ProductQuantity'] = 0;
								$GLOBALS['AddToCartQty'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CartItemQtyText");
							}
						}
					}
				} // for grid style
				else {
					$GLOBALS["CompareOnSubmit"] = "onsubmit=\"return compareProducts(config.CompareLink)\"";
				}

				$GLOBALS['ProductsOffersList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("HomeProductsOfferItem" . $display_mode);
			}

			if(count($products) == 0) {
				// There are no products in this category
				$GLOBALS['ProductsOffersList'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryNoProductsMessage");
			}
			
		// Paging
		if(count($products) <= GetConfig('CategoryProductsPerPage')) {
			return false;
		}
		
		// Workout the paging data
		$GLOBALS['SNIPPETS']['PagingData'] = "";
		
		$maxPagingLinks = 5;
		if($GLOBALS['ISC_CLASS_TEMPLATE']->getIsMobileDevice()) {
			$maxPagingLinks = 3;
		}
		
		$start = max($this->_page-$maxPagingLinks,1);
		$end = min($this->_page+$maxPagingLinks, $this->_numpages);
		
		$queryStringAppend = array(
				'sort' => $this->_sort,
		);
		
		if(!empty($_GET['price_min'])) {
			$queryStringAppend['price_min'] = (float)$_GET['price_min'];
		}
		
		if(!empty($_GET['price_max'])) {
			$queryStringAppend['price_max'] = (float)$_GET['price_max'];
		}
		
		
		for ($page = $start; $page <= $end; $page++) {
			if($page == $this->_page) {
				$snippet = "CategoryPagingItemCurrent";
			}
			else {
				$snippet = "CategoryPagingItem";
			}
		
			$pageQueryStringAppend = $queryStringAppend;
			$pageQueryStringAppend['page'] = $page;
			$GLOBALS['PageLink'] = $this->getPageLink($pageQueryStringAppend);
			$GLOBALS['PageNumber'] = $page;
			$GLOBALS['SNIPPETS']['PagingData'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet($snippet);
		}
		
		// Parse the paging snippet
		if($this->_page > 1) {
			// Do we need to output a "Previous" link?
			$pageQueryStringAppend = $queryStringAppend;
			$pageQueryStringAppend['page'] = $this->_page- 1;
			$GLOBALS['PrevLink'] = $this->getPageLink($pageQueryStringAppend);
			$GLOBALS['SNIPPETS']['CategoryPagingPrevious'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPagingPrevious");
		}
		
		if($this->_page < $this->_numpages) {
			// Do we need to output a "Next" link?
			$pageQueryStringAppend = $queryStringAppend;
			$pageQueryStringAppend['page'] = $this->_page + 1;
			$GLOBALS['NextLink'] = $this->getPageLink($pageQueryStringAppend);
			$GLOBALS['SNIPPETS']['CategoryPagingNext'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPagingNext");
		}
		
		$output = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategoryPaging");
		$output = $GLOBALS['ISC_CLASS_TEMPLATE']->ParseSnippets($output, $GLOBALS['SNIPPETS']);
		$GLOBALS['SNIPPETS']['CategoryPaging'] = $output;
		}
		
		private function getPageLink($appendArray = array()){
			$append = '';
			if(is_array($appendArray) && !empty($appendArray)) {
				if ($GLOBALS['EnableSEOUrls'] == 1) {
					$append .= '?';
				}
				else {
					$append .= '&';
				}
				$append .= http_build_query($appendArray);
			}
			
			if ($GLOBALS['EnableSEOUrls'] == 1) {
				$link = sprintf("%s/%s/%s", $GLOBALS['ShopPathNormal'], 'offers', $append);
			} else {
				$link = trim($link, "/");
				$link = sprintf("%s/offers.php%s", $GLOBALS['ShopPathNormal'], $append);
			}
		}
	}
