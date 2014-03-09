<?php

	class ISC_BRANDS
	{

		private $_brand = "";
		private $_brandsort = "";
		private $_brandsortfield = "";
		private $_brandname = '';

		private $_brandid = 0;
		private $_brandnumproducts = 0;
		private $_brandpage = 0;
		private $_brandstart = 0;
		private $_brandnumpages = 0;

		private $_brandpagetitle = '';
		private $_brandmetakeywords = '';
		private $_brandmetadesc = '';
		private $_brandsearchkeywords = '';
		private $_brandcanonicallink = '';

		private $_allbrands = false;

		private $_brandproducts = array();

		public function __construct()
		{
			$this->_SetBrandData();
		}

		public function SetSortField($Field)
		{
			// Set the field that the results will be sorted by in the query
			$this->_brandsortfield = $Field;
		}

		public function GetSortField()
		{
			return $this->_brandsortfield;
		}

		public function SetSort()
		{
			// Pre-select the current sort order (if any)
			if (isset($_GET['sort'])) {
				$sort = $_GET['sort'];
			} else {
				$sort = "featured";
			}
			$this->_brandsort = $sort;

			$priceColumn = 'p.prodcalculatedprice';
			// If we need to join the tax pricing table then the sort price column for
			// products changes.
			if($this->getTaxPricingJoin()) {
				$priceColumn = 'tp.calculated_price';
			}

			switch ($sort) {

				case "newest": {
					$GLOBALS['SortNewestSelected'] = 'selected="selected"';
					$this->SetSortField("p.productid desc");
					break;
				}
				case "bestselling": {
					$GLOBALS['SortBestSellingSelected'] = 'selected="selected"';
					$this->SetSortField("p.prodnumsold desc");
					break;
				}
				case "alphaasc": {
					$GLOBALS['SortAlphaAsc'] = 'selected="selected"';
					$this->SetSortField("p.prodname asc");
					break;
				}
				case "alphadesc": {
					$GLOBALS['SortAlphaDesc'] = 'selected="selected"';
					$this->SetSortField("p.prodname desc");
					break;
				}
				case "avgcustomerreview": {
					$GLOBALS['SortAvgReview'] = 'selected="selected"';
					$this->SetSortField("prodavgrating desc");
					break;
				}
				case "priceasc": {
					$GLOBALS['SortPriceAsc'] = 'selected="selected"';
					$this->SetSortField($priceColumn.' ASC');
					break;
				}
				case "pricedesc": {
					$GLOBALS['SortPriceDesc'] = 'selected="selected"';
					$this->SetSortField($priceColumn.' DESC');
					break;
				}
				case "featured":
				default:
				{
					$GLOBALS['SortFeaturedSelected'] = 'selected="selected"';
					$this->SetSortField("p.prodsortorder asc");
					break;
				}

			}
		}

		public function GetSort()
		{
			return $this->_brandsort;
		}

		public function _SetBrandData()
		{

			// Retrieve the query string variables. Can't use the $_GET array
			// because of SEO friendly links in the URL
			SetPGQVariablesManually();

			// Grab the page sort details
			$GLOBALS['URL'] = implode("/", $GLOBALS['PathInfo']);
			$this->SetSort();

			if (isset($_REQUEST['brand'])) {
				$brand = $_REQUEST['brand'];
			}
			else {
				if (isset($GLOBALS['PathInfo'][1])) {
					$brand = preg_replace('#\.html\??.*$#i', "", $GLOBALS['PathInfo'][1]);
				} else {
					$brand = '';
				}
			}

			$brand = MakeURLNormal($brand);

			// Get the link to the "all brands" page
			$GLOBALS['AllBrandsLink'] = BrandLink();

			// Viewing a particular brand
			if($brand) {
				// Get the Id of the brand
				$query = sprintf("select * from [|PREFIX|]brands where brandname='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($brand));
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

				// Invalid brand
				if(!$row) {
					$GLOBALS['ISC_CLASS_404'] = GetClass('ISC_404');
					$GLOBALS['ISC_CLASS_404']->HandlePage();
					exit;
				}

				// Store the brand name
				$this->SetBrand($brand);

				$this->SetBrandName($row['brandname']);

				// Store the brand Id
				$this->SetId($row['brandid']);

				$this->SetBrandPageTitle($row['brandpagetitle']);
				// Store brand meta details
				$this->SetMetaKeywords($row['brandmetakeywords']);
				$this->SetMetaDesc($row['brandmetadesc']);
				$this->SetSearchKeywords($row['brandsearchkeywords']);
				$this->SetNumProducts();
				$this->SetPage();
				$this->SetStart();
				$this->SetNumPages();

				// Load the products for the brand
				$this->LoadProductsForBrand();
			}
		}

		public function SetBrand($Brand)
		{
			$this->_brand = $Brand;
		}

		public function SetBrandName($BrandName)
		{
			$this->_brandname = $BrandName;
		}

		public function GetBrandName()
		{
			return $this->_brandname;
		}

		public function GetBrand()
		{
			return $this->_brand;
		}

		public function SetId($BrandId)
		{
			$this->_brandid = $BrandId;
		}

		public function GetId()
		{
			return $this->_brandid;
		}

		public function GetPageTitle()
		{
			return $this->_brandpagetitle;
		}

		public function SetBrandPageTitle($pagetitle)
		{
			$this->_brandpagetitle = $pagetitle;
		}

		public function SetMetaKeywords($Keywords)
		{
			$this->_brandmetakeywords = $Keywords;
		}

		public function SetMetaDesc($Desc)
		{
			$this->_brandmetadesc = $Desc;
		}

		public function SetCanonicalLink($Link)
		{
			$this->_brandcanonicallink = $Link;
		}

		public function SetSearchKeywords($Keywords)
		{
			$this->_brandsearchkeywords = $Keywords;
		}

		public function SetPage()
		{
			if (isset($_GET['page'])) {
				$this->_brandpage = abs((int)$_GET['page']);
			} else {
				$this->_brandpage = 1;
			}
		}

		public function GetPage()
		{
			return $this->_brandpage;
		}

		// Workout the number of pages for products in this category
		public function SetNumPages()
		{
			$this->_brandnumpages = ceil($this->GetNumProducts() / GetConfig('CategoryProductsPerPage'));
		}

		public function GetNumPages()
		{
			return $this->_brandnumpages;
		}

		// Set the start record for the products query
		public function SetStart()
		{
			$start = 0;

			switch ($this->_brandpage) {
				case 1: {
					$start = 0;
					break;
				}
				// Page 2 or more
				default: {
					$start = ($this->GetPage() * GetConfig('CategoryProductsPerPage')) - GetConfig('CategoryProductsPerPage');
					break;
				}
			}

			$this->_brandstart = $start;
		}

		public function GetStart()
		{
			return $this->_brandstart;
		}

		public function SetNumProducts()
		{
			if ($this->GetId() > 0) {
				$query = "
					SELECT COUNT(productid) AS numproducts
					FROM [|PREFIX|]products p
					WHERE prodbrandid='" . $GLOBALS['ISC_CLASS_DB']->Quote($this->GetId()) . "' AND prodvisible='1'
					" . GetProdCustomerGroupPermissionsSQL() . "
					";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
				$this->_brandnumproducts = $row['numproducts'];
			}
		}

		public function GetNumProducts()
		{
			return $this->_brandnumproducts;
		}

		/**
		 * Get the SQL used to join the product pricing table when tax
		 * is set to be shown as inclusive for catalog prices.
		 *
		 * @return string SQL containing join to product_tax_pricing.
		 */
		protected function getTaxPricingJoin()
		{
			// Prices entered without tax and shown without tax, so we don't need this join
			if(getConfig('taxDefaultTaxDisplayCatalog') == TAX_PRICES_DISPLAY_EXCLUSIVE &&
				getConfig('taxEnteredWithPrices') == TAX_PRICES_ENTERED_EXCLUSIVE) {
					return '';
			}

			// Not sorting or searching by prices. This join is not necessary
			if(empty($_GET['sort']) || ($_GET['sort'] != 'priceasc' && $_GET['sort'] != 'pricedesc')) {
				return '';
			}

			// Showing prices ex tax, so the tax zone ID = 0
			if(getConfig('taxDefaultTaxDisplayCatalog') == TAX_PRICES_DISPLAY_EXCLUSIVE) {
				$taxZone = 0;
			}
			// Showing prices inc tax, so we need to fetch the applicable tax zone
			else {
				$taxZone = getClass('ISC_TAX')->determineTaxZone();
			}

			return '
				JOIN [|PREFIX|]product_tax_pricing tp
				ON (
					tp.price_reference=p.prodcalculatedprice AND
					tp.tax_zone_id='.$taxZone.' AND
					tp.tax_class_id=p.tax_class_id
				)
			';
		}

		// Load the products to show for this brand, taking into account paging, filters, etc
		public function LoadProductsForBrand()
		{
			$taxJoin = $this->getTaxPricingJoin();
			$query = "
				SELECT p.*, FLOOR(prodratingtotal/prodnumratings) AS prodavgrating, pi.*, ".GetProdCustomerGroupPriceSQL()."
				FROM [|PREFIX|]products p
				LEFT JOIN [|PREFIX|]product_images pi ON (p.productid=pi.imageprodid AND pi.imageisthumb=1)
				".$taxJoin."
				WHERE prodbrandid='".(int)$this->GetId()."' AND prodvisible='1'
				".GetProdCustomerGroupPermissionsSQL()."
				ORDER BY ".$this->GetSortField().", prodname ASC
			";
			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit($this->GetStart(), GetConfig('CategoryProductsPerPage'));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$row['prodavgrating'] = (int)$row['prodavgrating'];
				$this->_brandproducts[] = $row;
			}
		}

		public function BuildTitle()
		{
			// use preset page title if it exsits
			if (trim($this->GetPageTitle()) != "") {
				$title = $this->GetPageTitle();
			// Build an SEO-friendly page title
			} elseif ($this->GetBrand() != "") {
				$title = sprintf("%s %s - %s", $this->GetBrand(), GetLang('Products'), GetConfig('StoreName'));
			} else {
				$title = sprintf("%s %s", GetConfig('StoreName'), GetLang('Brands'));
			}

			return $title;
		}

		public function GetProducts(&$Ref)
		{
			$Ref = $this->_brandproducts;
		}

		public function HandlePage()
		{
			$this->ShowBrand();
		}

		public function ShowingAllBrands()
		{
			return $this->_allbrands;
		}

		public function ShowBrand()
		{
			$GLOBALS['BrandId'] = $this->GetId();
			$GLOBALS['BrandName'] = $this->GetBrand();
			if($this->GetPage() > 1) {
				$this->_brandcanonicallink = BrandLink($this->GetBrandName(), array('page' => $this->GetPage()));
			} else {
				$this->_brandcanonicallink = BrandLink($this->GetBrandName());
			}

			$GLOBALS['CompareLink'] = CompareLink();

			if ($this->GetBrand() == "") {
				$GLOBALS['TrailBrandName'] = GetLang('AllBrands');
				$this->_allbrands = true;
			} else {
				$GLOBALS['TrailBrandName'] = isc_html_escape($this->GetBrand());
			}

			if ($this->_brandmetakeywords != "") {
				$GLOBALS['ISC_CLASS_TEMPLATE']->SetMetaKeywords($this->_brandmetakeywords);
			}

			if ($this->_brandmetadesc != "") {
				$GLOBALS['ISC_CLASS_TEMPLATE']->SetMetaDescription($this->_brandmetadesc);
			}

			if ($this->_brandcanonicallink != "") {
				$GLOBALS['ISC_CLASS_TEMPLATE']->SetCanonicalLink($this->_brandcanonicallink);
			}

			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle($this->BuildTitle());
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("brands");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}

		/**
		*	Get a list of all brands as <option> tags
		*/
		public function GetBrandsAsOptions($SelectedBrand=0)
		{
			$query = "select * from [|PREFIX|]brands order by brandname asc";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$output = "";

			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				if ($SelectedBrand == $row['brandid']) {
					$sel = 'selected="selected"';
				} else {
					$sel = "";
				}

				$output .= sprintf("<option value='%d' %s>%s</option>", $row['brandid'], $sel, isc_html_escape($row['brandname']));
			}

			return $output;
		}

		/**
		 * Search for brands
		 *
		 * Method will search for all the brands and return an array for brand records
		 *
		 * @access public
		 * @param array $searchQuery The search query array. Currently will only understand the 'search_query' option
		 * @param int &$totalAmount The referenced variable to store in the total amount of the result
		 * @param int $start The optional start position of the result total. Default is 0
		 * @param int $start The optional limit position of the result total. Default is -1 (no limit)
		 * @return array The array result set on success, FALSE on error
		 */
		static public function searchForItems($searchQuery, &$totalAmount, $start=0, $limit=-1)
		{
			if (!is_array($searchQuery)) {
				return false;
			}

			$totalAmount = 0;

			if (!array_key_exists("search_query", $searchQuery) || $searchQuery["search_query"] == '') {
				return array();
			}

			$fullTextFields = array("bs.brandname", "bs.brandpagetitle", "bs.brandsearchkeywords");

			$brands = array();
			$query = "SELECT SQL_CALC_FOUND_ROWS b.*,
							(IF(b.brandname='" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["search_query"]) . "', 10000, 0) +
							 IF(b.brandpagetitle='" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["search_query"]) . "', 10000, 0) +
							 ((" . $GLOBALS["ISC_CLASS_DB"]->FullText(array("bs.brandname"), $searchQuery["search_query"], false) . ") * 10) +
							   " . $GLOBALS["ISC_CLASS_DB"]->FullText($fullTextFields, $searchQuery["search_query"], false) . ") AS score
						FROM [|PREFIX|]brands b
							INNER JOIN [|PREFIX|]brand_search bs ON b.brandid = bs.brandid
						WHERE ";

			$searchPart = array();

			if (GetConfig("SearchOptimisation") == "fulltext" || GetConfig("SearchOptimisation") == "both") {
				$searchPart[] = $GLOBALS["ISC_CLASS_DB"]->FullText($fullTextFields, $searchQuery["search_query"], true);
			}

			if (GetConfig("SearchOptimisation") == "like" || GetConfig("SearchOptimisation") == "both") {
				$searchPart[] = "b.brandname LIKE '%" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["search_query"]) . "%'";
				$searchPart[] = "b.brandpagetitle LIKE '%" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["search_query"]) . "%'";
				$searchPart[] = "b.brandsearchkeywords LIKE '%" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["search_query"]) . "%'";
			}

			$query .= " " . implode(" OR ", $searchPart) . " ORDER BY score DESC";

			if (is_numeric($limit) && $limit > 0) {
				if (is_numeric($start) && $start > 0) {
					$query .= " LIMIT " . (int)$start . "," . (int)$limit;
				} else {
					$query .= " LIMIT " . (int)$limit;
				}
			}

			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			$row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);

			if (!$row) {
				return array();
			}

			$totalAmount = $GLOBALS["ISC_CLASS_DB"]->FetchOne("SELECT FOUND_ROWS()");
			$brands[] = $row;

			while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
				$brands[] = $row;
			}

			return $brands;
		}

		/**
		 * Build the searched item results HTML
		 *
		 * Method will build the searched item results HMTL. Method will work with the ISC_SEARCH class to get the results
		 * so make sure that the object is initialised and the DoSearch executed.
		 *
		 * @access public
		 * @return string The search item result HTML on success, empty string on error
		 */
		static public function buildSearchResultsHTML()
		{
			if (!isset($GLOBALS["ISC_CLASS_SEARCH"]) || !is_object($GLOBALS["ISC_CLASS_SEARCH"])) {
				return "";
			}

			$totalRecords = $GLOBALS["ISC_CLASS_SEARCH"]->GetNumResults("brand");

			if ($totalRecords == 0) {
				return "";
			}

			$results = $GLOBALS["ISC_CLASS_SEARCH"]->GetResults("brand");
			$resultHTML = array();

			if (!array_key_exists("results", $results) || !is_array($results["results"])) {
				return "";
			}

			foreach ($results["results"] as $brand) {
				if (!is_array($brand) || !array_key_exists("brandid", $brand)) {
					continue;
				}

				$resultHTML[] = "<a href=\"" . BrandLink($brand["brandname"]) . "\">" . isc_html_escape($brand["brandname"]) . "</a>";
			}

			$resultHTML = implode(", ", $resultHTML);
			$resultHTML = trim($resultHTML);
			return $resultHTML;
		}
	}
