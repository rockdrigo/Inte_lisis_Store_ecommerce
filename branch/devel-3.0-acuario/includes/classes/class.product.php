<?php

	class ISC_PRODUCT
	{

		public $_product = array();

		public $_prodid = 0;
		public $_prodprice = 0;
		public $_prodcalculatedprice = 0;
		public $_prodretailprice = 0;
		public $_prodsaleprice = 0;
		public $_prodimages = 0;
		public $_prodfixedshippingcost = 0;
		public $_prodtype = 0;
		public $_prodweight = 0;
		public $_prodavgrating = 0;
		public $_prodoptionsrequired = 0;
		public $_prodnumreviews = 0;
		public $_prodnumcustomfields = 0;
		public $_prodnumbulkdiscounts = 0;
		public $_prodinvtrack = 0;
		public $_prodcurrentinv = 0;

		public $_prodallowpurchases = 1;
		public $_prodhideprice = 0;
		public $_prodcallforpricinglabel = '';

		public $_prodname = "";
		public $_prodthumb = "";
		public $_proddesc = "";
		public $_prodbrandname = "";
		public $_prodavailability = "";
		public $_prodrelatedproducts = "";
		public $_prodlayoutfile = "";
		public $_prodsku = "";

		public $_prodpagetitle = '';
		public $_prodmetakeywords = '';
		public $_prodmetadesc = '';

		public $_prodvideos = array();

		public $_prodfreeshipping = false;
		public $_prodvariationid = 0;
		//public $_prodvariations = array();
		public $_prodvariationcombinations = array();
		public $_prodvariationoptions = array();
		public $_prodvariationvalues = array();

		public $_prodeventdatelimited;
		public $_prodeventdaterequired;
		public $_prodeventdatefieldname;
		public $_prodeventdatelimitedtype;
		public $_prodeventdatelimitedstartdate;
		public $_prodeventdatelimitedenddate;

		public $_prodcondition;
		public $_prodshowcondition;

		public $_prodpreorder;
		public $_prodreleasedate;
		public $_prodreleasedateremove;
		public $_prodpreordermessage;

		public $_opengraph_type = '';
		public $_opengraph_title = '';
		public $_opengraph_description = '';
		public $_opengraph_image = '';

		public $_currencyrecord = null;

		public $_prodminqty = 0;
		public $_prodmaxqty = 0;

		public $_upc = "";
		public $_disable_google_checkout = 0;

		public function __construct($productid=0)
		{
			// Load the data for this product
			$this->_SetProductData($productid);

			$GLOBALS['AdditionalScripts'][] = GetConfig('AppPath').'/javascript/jquery/plugins/imodal/imodal.js';
			$GLOBALS['AdditionalStylesheets'][] = GetConfig('AppPath').'/javascript/jquery/plugins/imodal/imodal.css';

			if(GetConfig('ProductImagesImageZoomEnabled')) {
				$GLOBALS['AdditionalStylesheets'][] = GetConfig('AppPath').'/javascript/jquery/plugins/jqzoom/jqzoom.css';
			}

			// Add it to the list of recently viewed products
			if($productid == 0) {

				$this->_insertOptimizerScripts();

				// We must load the CSS file here for the product details bulk discount thickbox as it needs to be defined before the
				// headers get built
				$this->_AddToRecentlyViewedProducts();

				// Workout the breadcrumb(s)
				$this->_BuildBreadCrumbs();

				// Track a view for this page
				$this->_TrackView();
			}
		}

		public function _SetProductData($productid=0)
		{

			if ($productid == 0) {
				// Retrieve the query string variables. Can't use the $_GET array
				// because of SEO friendly links in the URL
				SetPGQVariablesManually();
				if (isset($_REQUEST['product'])) {
					$product = $_REQUEST['product'];
				}
				else if(isset($GLOBALS['PathInfo'][1])) {
					$product = preg_replace('#\.html$#i', '', $GLOBALS['PathInfo'][1]);
				}
				else {
					$product = '';
				}

				$product = $GLOBALS['ISC_CLASS_DB']->Quote(MakeURLNormal($product));
				$productSQL = sprintf("p.prodname='%s'", $product);
			}
			else {
				$productSQL = sprintf("p.productid='%s'", (int)$productid);
			}

			$query = "
				SELECT p.*, FLOOR(prodratingtotal/prodnumratings) AS prodavgrating, pi.*, ".GetProdCustomerGroupPriceSQL().",
				(SELECT COUNT(fieldid) FROM [|PREFIX|]product_customfields WHERE fieldprodid=p.productid) AS numcustomfields,
				(SELECT COUNT(reviewid) FROM [|PREFIX|]reviews WHERE revstatus='1' AND revproductid=p.productid AND revstatus='1') AS numreviews,
				(SELECT brandname FROM [|PREFIX|]brands WHERE brandid=p.prodbrandid) AS prodbrandname,
				(SELECT COUNT(imageid) FROM [|PREFIX|]product_images WHERE imageprodid=p.productid) AS numimages,
				(SELECT COUNT(discountid) FROM [|PREFIX|]product_discounts WHERE discountprodid=p.productid) AS numbulkdiscounts
				FROM [|PREFIX|]products p
				LEFT JOIN [|PREFIX|]product_images pi ON (pi.imageisthumb=1 AND p.productid=pi.imageprodid)
				WHERE ".$productSQL;

			if(!isset($_COOKIE['STORESUITE_CP_TOKEN'])) {
				// ISC-1073: don't check visibility if we are on control panel
				$query .= " AND p.prodvisible='1'";
			}

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			if (!$row) {
				return;
			}

			$this->_product = $row;
			$this->_prodid = $row['productid'];
			$this->_prodname = $row['prodname'];
			$this->_prodsku = $row['prodcode'];
			$this->_prodthumb = $row['imagefile'];
			$this->_proddesc = $row['proddesc'];
			$this->_prodimages = $row['numimages'];
			$this->_prodprice = $row['prodprice'];
			$this->_prodretailprice = $row['prodretailprice'];
			$this->_prodsaleprice = $row['prodsaleprice'];
			$this->_prodfixedshippingcost = $row['prodfixedshippingcost'];
			$this->_prodbrandname = $row['prodbrandname'];
			$this->_prodweight = $row['prodweight'];
			$this->_prodavgrating = (int)$row['prodavgrating'];
			$this->_prodcalculatedprice = $row['prodcalculatedprice'];
			$this->_prodoptionsrequired = $row['prodoptionsrequired'];
			$this->_prodnumreviews = $row['numreviews'];
			$this->_prodavailability = $row['prodavailability'];
			$this->_prodnumcustomfields = $row['numcustomfields'];
			$this->_prodnumbulkdiscounts = $row['numbulkdiscounts'];
			$this->_prodeventdatelimited = $row['prodeventdatelimited'];
			$this->_prodeventdaterequired = $row['prodeventdaterequired'];
			$this->_prodeventdatefieldname = $row['prodeventdatefieldname'];
			$this->_prodeventdatelimitedtype = $row['prodeventdatelimitedtype'];
			$this->_prodeventdatelimitedstartdate = $row['prodeventdatelimitedstartdate'];
			$this->_prodeventdatelimitedenddate = $row['prodeventdatelimitedenddate'];
			$this->_prodcondition = $row['prodcondition'];
			$this->_prodshowcondition = $row['prodshowcondition'];
			$this->_prodoptimizerenabled = $row['product_enable_optimizer'];
			$this->_prodvariationid = $row['prodvariationid'];
			$this->_prodminqty = $row['prodminqty'];
			$this->_prodmaxqty = $row['prodmaxqty'];
			$this->_upc = $row['upc'];
			$this->_disable_google_checkout = $row['disable_google_checkout'];

			$videoQuery = 'select * from `[|PREFIX|]product_videos` where video_product_id=' . (int)$this->_prodid . ' order by `video_sort_order` asc';
			$videoResource = $GLOBALS['ISC_CLASS_DB']->Query($videoQuery);

			$this->_prodvideos = array();

			while($videoRow = $GLOBALS['ISC_CLASS_DB']->Fetch($videoResource)) {
				$this->_prodvideos[] = $videoRow;
			}

			$this->_prodrelatedproducts = $row['prodrelatedproducts'];
			$this->setLayoutFile($row['prodlayoutfile']);

			$this->_prodpagetitle = $row['prodpagetitle'];
			$this->_prodmetakeywords = $row['prodmetakeywords'];
			$this->_prodmetadesc = $row['prodmetadesc'];
			$this->_prodinvtrack = $row['prodinvtrack'];
			$this->_prodcurrentinv = $row['prodcurrentinv'];

			if ($row['prodtype'] == 1) {
				$this->_prodtype = PT_PHYSICAL;
			} else {
				$this->_prodtype = PT_DIGITAL;
			}

			if ($row['prodfreeshipping'] == 0) {
				$this->_prodfreeshipping = false;
			} else {
				$this->_prodfreeshipping = true;
			}

			$this->_prodallowpurchases = $row['prodallowpurchases'];
			$this->_prodhideprice = $row['prodhideprice'];
			$this->_prodcallforpricinglabel = $row['prodcallforpricinglabel'];

			// If there are product variations, set them up
			if($row['prodvariationid'] > 0) {
				$this->SetupProductVariations();
			}

			$this->_prodpreorder = true;
			if ($row['prodpreorder'] == 0) {
				$this->_prodpreorder = false;
			}

			$this->_prodpreordermessage = $row['prodpreordermessage'];

			$this->_prodreleasedate = $row['prodreleasedate'];

			$this->_prodreleasedateremove = true;
			if ($row['prodreleasedateremove'] == 0) {
				$this->_prodreleasedateremove = false;
			}

			$this->_opengraph_type = $row['opengraph_type'];

			if ($row['opengraph_use_product_name']) {
				$this->_opengraph_title = $row['prodname'];
			}
			else {
				$this->_opengraph_title = $row['opengraph_title'];
			}

			if ($row['opengraph_use_meta_description']) {
				$this->_opengraph_description = $row['prodmetadesc'];
			}
			else {
				$this->_opengraph_description = $row['opengraph_description'];
			}

			if ($row['opengraph_use_image'] != '') {
				try {
					$productImage = new ISC_PRODUCT_IMAGE;
					$productImage->populateFromDatabaseRow($row);
					$this->_opengraph_image = $productImage->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_STANDARD, true);
				} catch (Exception $exception) {
					// nothing
				}
			}

			$GLOBALS['CurrentProductLink'] = ProdLink($this->_prodname);
		}

		private function getLayoutFile()
		{
			$layoutFile = $this->_prodlayoutfile;

			if($GLOBALS['ISC_CLASS_TEMPLATE']->getTemplateFilePath($layoutFile)) {
				return $layoutFile;
			}
			else {
				return $this->_prodlayoutfile = 'product';
			}
		}

		private function setLayoutFile($layoutFile)
		{
			$this->_prodlayoutfile = str_replace(array(".html", ".htm"), "", $layoutFile);
		}

		/**
		*	Track a view for this product by updating the prodnumviews field in the products table
		*/
		public function _TrackView()
		{
			$query = sprintf("update [|PREFIX|]products set prodnumviews=prodnumviews+1 where productid='%d'", $this->_prodid);
			$GLOBALS['ISC_CLASS_DB']->Query($query);
		}

		public function _AddToRecentlyViewedProducts()
		{
			/*
				Store this product's ID in a persistent cookie
				that will be used to remember the last 5 products
				that this person has viewed
			*/

			$viewed_products = array();

			if (isset($_COOKIE['RECENTLY_VIEWED_PRODUCTS'])) {
				$viewed_products = explode(",", $_COOKIE['RECENTLY_VIEWED_PRODUCTS']);
			}

			if (in_array($this->GetProductId(), $viewed_products)) {
				// Remove it from the array
				foreach ($viewed_products as $k => $v) {
					if ($v == $this->GetProductId()) {
						unset($viewed_products[$k]);
					}
				}
			}

			// Add it to the list
			$viewed_products[] = $this->GetProductId();

			// Only store the 5 most recent product Id's
			if (count($viewed_products) > 5) {
				$reverse_viewed_products = array_reverse($viewed_products);
				$viewed_products = array();

				for ($i = 0; $i < 5; $i++) {
					$viewed_products[] = $reverse_viewed_products[$i];
				}

				// Reverse the array so the oldest products show first
				$viewed_products = array_reverse($viewed_products);
			}

			$new_viewed_products = implode(",", $viewed_products);

			// Persist the cookie for 30 days
			ISC_SetCookie("RECENTLY_VIEWED_PRODUCTS", $new_viewed_products, time() + (3600*24*30));

			// Persist the cookie session-wide for use on the cart page
			$_SESSION['RECENTLY_VIEWED_PRODUCTS'] = $new_viewed_products;
		}

		public function SetupProductVariations()
		{
			// get a list of option names
			$query = "
				SELECT
					DISTINCT voname
				FROM
					[|PREFIX|]product_variation_options
				WHERE
					vovariationid = " . $this->_prodvariationid . "
				ORDER BY
					vooptionsort
			";

			$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$vOptions = array();
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
				$vOptions[] = $row['voname'];
			}

			$this->_prodvariationoptions = $vOptions;

			// get a list of enabled option values to limit what's shown in the select boxes (for the first option or if javascript is disabled)
			$query = "
				SELECT
					*
				FROM
					[|PREFIX|]product_variation_combinations
				WHERE
					vcenabled = 1 AND
					vcvariationid  = " . $this->_prodvariationid . " AND
					vcproductid = " . $this->_prodid;

			$this->_prodvariationcombinations = new Interspire_Db_QueryIterator($GLOBALS['ISC_CLASS_DB'], $query);

			$enabledOptions = array();
			foreach ($this->_prodvariationcombinations as $row) {
				$options = explode(',', $row['vcoptionids']);
				foreach ($options as $option) {
					if (!in_array($option, $enabledOptions)) {
						$enabledOptions[] = $option;
					}
				}
			}

			$vValues = array();

			if (!empty($enabledOptions)) {
				// get an initial list of options values
				$query = "
					SELECT
						voname,
						voptionid,
						vovalue
					FROM
						[|PREFIX|]product_variation_options
					WHERE
						vovariationid = " . $this->_prodvariationid . " AND
						voptionid IN (" . implode(',', $enabledOptions) . ")
					ORDER BY
						vovaluesort
				";

				$res = $GLOBALS['ISC_CLASS_DB']->Query($query);

				while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
					$vValues[$row['voname']][$row['voptionid']] = $row['vovalue'];
				}
			}

			$this->_prodvariationvalues = $vValues;
		}

		public function GetVariationCombination($combination)
		{
			if(!is_array($combination)) {
				$combination = explode(",", $combination);
			}

			// Sort it numerically
			sort($combination, SORT_NUMERIC);
			$combination = implode(",", $combination);

			// find combinations that contain the selected list of options
			$query = "
				SELECT
					combinationid
				FROM
					[|PREFIX|]product_variation_combinations
				WHERE
					vcproductid = " . $this->_prodid . " AND
					vcenabled = 1 AND
					vcoptionids = '" . $GLOBALS['ISC_CLASS_DB']->Quote($combination) . "'
			";

			$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
			if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
				return $row['combinationid'];
			}

			// Nothing found, return false
			return false;
		}

		/**
		* Gets an array of formatted details for a specific combination row such as pricing, weight, images
		*
		* @param array Database row of combination information
		* @param int The customer group to use to determine the final product price (used when getting variation details from back end quote system)
		* @return array The formatted combination details
		*/
		public function GetCombinationDetails($combination, $customerGroupId = null)
		{
			$thumb = '';
			$image = '';

			$priceOptions = array(
				'variationModifier'		=> $combination['vcpricediff'],
				'variationAdjustment'	=> $combination['vcprice'],
			);

			if (!is_null($customerGroupId)) {
				$priceOptions['customerGroup'] = $customerGroupId;
			}

			if($this->GetProductCallForPricingLabel()) {
				$variationPrice = $GLOBALS['ISC_CLASS_TEMPLATE']->ParseGL($this->GetProductCallForPricingLabel());
			}
			else {
				$variationPrice = formatProductDetailsPrice($this->_product, $priceOptions);
			}

			$calculatedPrice = calculateFinalProductPrice($this->_product, $this->_prodcalculatedprice, $priceOptions);
			$calculatedPrice = getClass('ISC_TAX')->getPrice(
				$calculatedPrice,
				$this->_product['tax_class_id'],
				getConfig('taxDefaultTaxDisplayProducts')
			);

			$variationSaveAmount = '';
			if($this->_prodretailprice > 0) {
				$rrp = calculateFinalProductPrice($this->_product, $this->_prodretailprice, $priceOptions);
				$rrp = getClass('ISC_TAX')->getPrice(
					$rrp,
					$this->_product['tax_class_id'],
					getConfig('taxDefaultTaxDisplayProducts')
				);

				$youSave = $rrp - $calculatedPrice;
				if($youSave > 0) {
					$variationSaveAmount = CurrencyConvertFormatPrice($youSave);
				}
			}

			$variationWeight = FormatWeight(CalcProductVariationWeight($this->_prodweight, $combination['vcweightdiff'], $combination['vcweight']), true);

			// use the product image class for automatic resizing
			$productImage = new ISC_PRODUCT_IMAGE;
			if ($combination['vcimage']) {
				$productImage->setSourceFilePath($combination['vcimage']);

				if ($combination['vcimagestd']) {
					$productImage->setResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_STANDARD, $combination['vcimagestd']);
					$thumb = $productImage->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_STANDARD, true, false);
				}

				if ($combination['vcimagezoom']) {
					//check if zoom image is large enough for image zoomer
					try {
						$productImage->setResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_ZOOM, $combination['vcimagezoom']);
						list($zoomWidth, $zoomHeight) = $productImage->getResizedFileDimensions(ISC_PRODUCT_IMAGE_SIZE_ZOOM, true, false);
						if ($zoomWidth >= ISC_PRODUCT_IMAGE_MIN_ZOOM_WIDTH || $zoomHeight >= ISC_PRODUCT_IMAGE_MIN_ZOOM_HEIGHT) {
							$image = $productImage->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_ZOOM, true, false);
						}
					} catch (Exception $exception) {
						// do nothing, will result in returning blank string, which is fine
					}
				}
			}

			// Tracking inventory on a product variation level
			if($this->_prodinvtrack == 2) {
				$stock = $combination['vcstock'];
			}
			else {
				$stock = $this->_prodcurrentinv;
			}

			if($stock <= 0 && $this->_prodinvtrack != 0) {
				$instock = false;
			}
			else {
				$instock = true;
			}

			$return = array(
				'combinationid'			=> $combination['combinationid'],
				'saveAmount'			=> $variationSaveAmount,
				'price'					=> $variationPrice,
				'unformattedPrice'      => formatPrice($calculatedPrice, false, false),
				'sku'					=> $combination['vcsku'],
				'weight'				=> $variationWeight,
				'thumb'					=> $thumb,
				'image'					=> $image,
				'instock'				=> $instock
			);

			if (GetConfig('ShowInventory') == 1 && (!$this->IsPreOrder() || GetConfig('ShowPreOrderInventory') == 1)) {
				$return['stock'] = $stock;
			}
			return $return;
		}

		/*public function GetProductVariations()
		{
			return $this->_prodvariations;
		}*/

		public function GetProductVariationOptions()
		{
			return $this->_prodvariationoptions;
		}

		public function GetProductVariationOptionValues()
		{
			return $this->_prodvariationvalues;
		}

		public function GetProductInventoryTracking()
		{
			return $this->_prodinvtrack;
		}

		public function GetInventoryLevel()
		{
			return $this->_prodcurrentinv;
		}

		public function IsOptionRequired()
		{
			if ($this->_prodoptionsrequired == 1) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Should bread crumbs be shown for this product.
		 *
		 * @return boolean
		 */
		protected function hideBreadCrumbs()
		{
			return GetConfig('ProductBreadcrumbs') === 'shownone';
		}

		/**
		 * Returns max number of breadcrumbs to display. Currently
		 * only 1 or all.
		 *
		 * @return int max breadcrumbs to display, 0 = all
		 */
		protected function maxBreadCrumbs()
		{
			return GetConfig('ProductBreadcrumbs') === 'showone' ? 1 : 0;
		}

		public function _BuildBreadCrumbs()
		{
			if($this->hideBreadCrumbs()) {
				$GLOBALS['HideBreadCrumbs'] = 'style="display:none"';
				return;
			}

			$GLOBALS['HideBreadCrumbs'] = "";

			/*
				Build a list of one or more breadcrumb trails for this
				product based on which categories it appears in
			*/

			// Build the arrays that will contain the category names to build the trails
			$count = 0;

			$GLOBALS['BreadCrumbs'] = "";
			$GLOBALS['FindByCategory'] = "";

			// First we need to fetch the parent lists of all of the categories
			$trailCategories = array();
			$crumbList = array();
			$catDescs = array();
			$query = sprintf("
				SELECT c.categoryid, c.catparentlist
				FROM [|PREFIX|]categoryassociations ca
				INNER JOIN [|PREFIX|]categories c ON (c.categoryid=ca.categoryid)
				WHERE ca.productid='%d' AND c.catvisible='1'",
				$GLOBALS['ISC_CLASS_DB']->Quote($this->GetProductId())
			);

			if($maxBreadcrumbs = $this->maxBreadcrumbs()) {
				$query .= ' LIMIT '.$maxBreadcrumbs;
			}

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				if ($row['catparentlist'] == '') {
					$row['catparentlist'] = $row['categoryid'];
				}
				$cats = explode(",", $row['catparentlist']);
				$trailCategories = array_merge($trailCategories, $cats);
				$crumbList[$row['categoryid']] = $row['catparentlist'];
			}

			$trailCategories = implode(",", array_unique($trailCategories));
			$categories = array();
			if ($trailCategories != '') {
				// Now load the names for the parent categories from the database
				$query = sprintf("SELECT categoryid, catname, catdesc FROM [|PREFIX|]categories WHERE categoryid IN (%s)", $trailCategories);
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$categories[$row['categoryid']] = $row['catname'];
					$catDescs[$row['categoryid']] = $row['catdesc'];
				}

				$GLOBALS['AdditionalProductsList'] = '';
				$query_a = sprintf("select p.productid AS \"productid\", p.prodname AS \"prodname\" 
					from [|PREFIX|]categoryassociations ca
					join [|PREFIX|]products p on (ca.productid=p.productid)
					where ca.categoryid IN (%s)
					group by p.prodname
					limit 0, 4
					", $trailCategories);
				$result_a = $GLOBALS['ISC_CLASS_DB']->Query($query_a);
				while($row_a = $GLOBALS['ISC_CLASS_DB']->Fetch($result_a)) {
					$GLOBALS['AdditionalProductLink'] = ProdLink($row_a['prodname']);
					$GLOBALS['AdditionalProductName'] = $row_a['prodname'];
					if ($row_a['productid'] == $this->GetProductId()) { $GLOBALS['AdditionalProductActive'] = "AdditionalProductActive"; }
					else { $GLOBALS['AdditionalProductActive'] = ""; }
					$GLOBALS['AdditionalProductsList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("AdditionalProductsItem");
				}
			}

			// Now we have all of the information we need to build the trails, lets actually build them
			foreach ($crumbList as $productcatid => $trail) {
				$GLOBALS['CatTrailLink'] = $GLOBALS['ShopPath'];
				$GLOBALS['CatTrailName'] = GetLang('Home');
				$GLOBALS['BreadcrumbItems'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("BreadcrumbItem");
				$GLOBALS['FindByCategoryItems'] = "";

				$cats = explode(",", $trail);
				$breadcrumbitems = "";
				$findbycategoryitems = "";
				$hasAccess = true;

				foreach ($cats as $categoryid) {
					if(!CustomerGroupHasAccessToCategory($categoryid)) {
						/*
						if customer doesn't have access to this category and this category is the category of the product,
						dont print the trail, otherwise just exclude the category from the trail
						*/
						if ($categoryid == $productcatid) {
							$hasAccess = false;
							break;
						}
						continue;
					}
					if (isset($categories[$categoryid])) {
						$catname = $categories[$categoryid];
						$GLOBALS['CatTrailLink'] = CatLink($categoryid, $catname);
						$GLOBALS['CatTrailName'] = isc_html_escape($catname);
						$breadcrumbitems .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("BreadcrumbItem");
						$findbycategoryitems .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductFindByCategoryItem");
					}
				}

				if ($hasAccess) {
					$GLOBALS['CatTrailName'] = isc_html_escape($this->GetProductName());
					$GLOBALS['CatDesc'] = $catDescs[$categoryid];
					$GLOBALS['BreadcrumbItems'] .= $breadcrumbitems . $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("BreadcrumbItemCurrent");
					$GLOBALS['BreadCrumbs'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductBreadCrumb");
					$GLOBALS['FindByCategoryItems'] = $findbycategoryitems;
					$GLOBALS['FindByCategory'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductFindByCategory");
				}
			}
		}

		public function HandlePage()
		{
			$this->ShowPage();
		}

		public function HasFreeShipping()
		{
			return $this->_prodfreeshipping;
		}

		public function GetFixedShippingCost()
		{
			return $this->_prodfixedshippingcost;
		}

		public function GetProduct()
		{
			return $this->_product;
		}

		public function GetProductName()
		{
			return $this->_prodname;
		}

		public function GetProductUPC()
		{
			return $this->_upc;
		}

		public function GetProductId()
		{
			return $this->_prodid;
		}

		public function GetThumb()
		{
			return $this->_prodthumb;
		}

		public function GetDesc()
		{
			return $this->_proddesc;
		}

		public function GetNumImages()
		{
			return $this->_prodimages;
		}

		public function GetBrandName()
		{
			return $this->_prodbrandname;
		}

		public function GetProductType()
		{
			return $this->_prodtype;
		}

		public function GetWeight($includemeasure=true)
		{
			return FormatWeight($this->_prodweight, $includemeasure);
		}

		public function GetRating()
		{
			return $this->_prodavgrating;
		}

		public function GetNumReviews()
		{
			return $this->_prodnumreviews;
		}

		public function GetSKU()
		{
			return $this->_prodsku;
		}

		public function GetAvailability()
		{
			return $this->_prodavailability;
		}

		public function GetNumCustomFields()
		{
			return $this->_prodnumcustomfields;
		}

		public function GetNumBulkDiscounts()
		{
			return $this->_prodnumbulkdiscounts;
		}

		public function GetEventDateRequired()
		{
			return $this->_prodeventdaterequired;
		}

		public function GetEventDateLimited()
		{
			return $this->_prodeventdatelimited;
		}
		public function GetEventDateFieldName()
		{
			return $this->_prodeventdatefieldname;
		}
		public function GetEventDateLimitedType()
		{
			return $this->_prodeventdatelimitedtype;
		}
		public function GetEventDateLimitedStartDate()
		{
			return $this->_prodeventdatelimitedstartdate;
		}
		public function GetEventDateLimitedEndDate()
		{
			return $this->_prodeventdatelimitedenddate;
		}

		/**
		* Returns whether this product is flagged as a pre-order - DOES NOT CHECK RELEASE DATE, see IsPreOrder for that
		*
		* @return bool
		*/
		public function GetPreOrder()
		{
			return $this->_prodpreorder;
		}

		public function GetReleaseDate()
		{
			return $this->_prodreleasedate;
		}

		public function GetReleaseDateRemove()
		{
			return $this->_prodreleasedateremove;
		}

		/**
		* Returns the pre-order message to use for this product. If no product-specific message is set, this will return the site-wide default. If the product is not a pre-order, or no release date, a blank string will be returned.
		*
		* @param bool $replaceDatePlaceholder If true, will automatically replace any %%DATE%% placeholder in the message with the product's release date, according to the configured date format
		* @param int $releaseDate If provided, will override the local product's preorder date to the one provided (such as from an order)
		* @return string
		*/
		public function GetPreOrderMessage($replaceDatePlaceholder = true, $releaseDate = null)
		{
			if (!$releaseDate) {
				if (!$this->IsPreOrder()) {
					return '';
				}

				$releaseDate = $this->GetReleaseDate();
			}

			if ($releaseDate) {
				if ($this->_prodpreordermessage) {
					$message = $this->_prodpreordermessage;
				} else {
					$message = GetConfig('DefaultPreOrderMessage');
				}

				if ($replaceDatePlaceholder) {
					$message = str_replace('%%DATE%%', isc_date(GetConfig('DisplayDateFormat'), $releaseDate), $message);
				}
			} else {
				$message = GetConfig('PreOrderProduct');
			}

			return $message;
		}

		/**
		 * Does this product have usable bulk discounts
		 *
		 * Method will check to see if this product has any bulks discounts without any product variations
		 *
		 * @access public
		 * @return bool TRUE if this product has any usable bulk discounts, FALSE if not
		 */
		public function CanUseBulkDiscounts()
		{
			if (GetConfig('BulkDiscountEnabled') && $this->GetNumBulkDiscounts() > 0 && empty($this->_prodvariations)) {
				return true;
			}

			return false;
		}

		public function GetRelatedProducts()
		{
			// Related products are set to automatic, find them
			return GetRelatedProducts($this->_prodid, $this->_prodname, $this->_prodrelatedproducts);
		}

		public function IsPurchasingAllowed()
		{
			return !$this->ArePricesHidden() && $this->_prodallowpurchases && (bool)GetConfig('AllowPurchasing');
		}

		/**
		* Returns whether or not this product is a preorder based on the preorder flag and the release date; will automatically update the db record accordingly.
		*
		* @return bool
		*/
		public function IsPreOrder()
		{
			if (!$this->_prodpreorder) {
				return false;
			}

			if ($this->_prodreleasedateremove && $this->_prodreleasedate && time() >= $this->_prodreleasedate) {
				// preorder still set in data and should be removed
				if ($this->_prodid) {
					$query = "UPDATE /*ISC_PRODUCT->IsPreOrder*/ [|PREFIX|]products SET prodpreorder = 0, prodreleasedate = 0, prodreleasedateremove = 0 WHERE productid = " . $this->_prodid;
					$GLOBALS['ISC_CLASS_DB']->Query($query);
				}
				$this->_prodpreorder = 0;
				$this->_prodreleasedate = 0;
				$this->_prodreleasedateremove = 0;
				return false;
			}

			return true;
		}
		//REQ11191 JIB: Verifica la condicion de ShowPriceGuest
		public function ArePricesHidden()
		{
			$custInfo = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerInfo();
			if(!GetConfig('ShowProductPrice') || $this->_prodhideprice == 1 || (!GetConfig('ShowPriceGuest') && GetConfig('ShowProductPrice') && $custInfo == NULL)) {
				return true;
			}

			return false;
		}

		public function GetProductCallForPricingLabel()
		{
			if ($this->_prodhideprice == 1) {
				return $this->_prodcallforpricinglabel;
			}
			return '';
		}

		public function GetPageTitle()
		{
			return $this->_prodpagetitle;
		}

		public function GetProductWarranty()
		{
			return $this->_product['prodwarranty'];
		}

		/**
		* This method checks if the associated product has any videos.
		*
		* @return boolean TRUE if this product has associated videos, FALSE otherwise
		*/
		public function hasVideos()
		{
			return !empty($this->_prodvideos);
		}

		/**
		* This method returns an array of associated videos
		*
		* @return array An array of youtube video IDs associated with this product.
		*/
		public function getVideos()
		{
			if(!is_array($this->_prodvideos) || empty($this->_prodvideos)) {
				return array();
			}

			return $this->_prodvideos;
		}

		/**
		 * Get the details of the vendor that this product belongs to (if there are any)
		 *
		 * @return array The details of the vendor. False if there aren't any
		 */
		public function GetProductVendor()
		{
			if(!gzte11(ISC_HUGEPRINT) || $this->_product['prodvendorid'] == 0) {
				return false;
			}

			$vendorCache = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('Vendors');
			if(isset($vendorCache[$this->_product['prodvendorid']])) {
				return $vendorCache[$this->_product['prodvendorid']];
			}

			return false;
		}

		public function BuildTitle()
		{
			$title = '';
			if ($this->GetPageTitle()!="") {
				$title = $this->GetPageTitle();
			} elseif ($this->GetProductName()!="") {
				$title = sprintf("%s - %s", $this->GetProductName(), GetConfig('StoreName'));
			} else {
				$title = sprintf("%s %s", GetConfig('StoreName'), GetLang('Products'));
			}
			return $title;
		}

		public function ShowPage()
		{
			if ($this->_prodid > 0) {
				// Check that the customer has permission to view this product
				$canView = false;
				$productCategories = explode(',', $this->_product['prodcatids']);
				foreach($productCategories as $categoryId) {
					// Do we have permission to access this category?
					if(CustomerGroupHasAccessToCategory($categoryId)) {
						$canView = true;
					}
				}
				if($canView == false) {
					$noPermissionsPage = GetClass('ISC_403');
					$noPermissionsPage->HandlePage();
					exit;
				}

				if ($this->_prodmetakeywords != "") {
					$GLOBALS['ISC_CLASS_TEMPLATE']->SetMetaKeywords(isc_html_escape($this->_prodmetakeywords));
				}

				if ($this->_prodmetadesc != "") {
					$GLOBALS['ISC_CLASS_TEMPLATE']->SetMetaDescription(isc_html_escape($this->_prodmetadesc));
				}

				$GLOBALS['ISC_CLASS_TEMPLATE']->SetCanonicalLink(ProdLink($this->_prodname));
				$GLOBALS['CompareLink'] = CompareLink();

				// If we're showing images as a lightbox, we need to load up the URLs for the other images for this product
				if(GetConfig('ProductImageMode') == 'lightbox') {
					$GLOBALS['AdditionalStylesheets'][]  = 	GetConfig('ShopPath').'/javascript/jquery/plugins/lightbox/lightbox.css';
				}

				$GLOBALS['AdditionalMetaTags'] = ISC_OPENGRAPH::getMetaTags($this->_opengraph_type, $this->_opengraph_title, $this->_opengraph_description, $this->_opengraph_image, ProdLink($this->_prodname));

				ISC_PRODUCT_VIEWS::logView($this->_prodid);

				$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle($this->BuildTitle());
				$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate($this->getLayoutFile());
				$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
			}
			// Visiting an invalid product, show a lovely error message
			else {
				$GLOBALS['ISC_CLASS_404'] = GetClass('ISC_404');
				$GLOBALS['ISC_CLASS_404']->HandlePage();
				exit;
			}
		}

		/**
		 * Check if the current product has tags or not.
		 *
		 * @return boolean True if the product has tags, false if not.
		 */
		public function ProductHasTags()
		{
			return (bool)$this->_product['prodhastags'];
		}

		/**
		 * Can the product be gift wrapped? This depends on two things - are there any gift
		 * wrapping options available and has this product been configured to allow gift wrapping?
		 */
		public function CanBeGiftWrapped()
		{
			if($this->_product['prodwrapoptions'] == -1) {
				return false;
			}
			else {
				$wrapCache = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('GiftWrapping');
				if(!empty($wrapCache)) {
					return true;
				}
			}

			return false;
		}

		public function GetProductFields($productId)
		{
			$fields = array();
			if($productId == 0) {
				return $fields;
			}
			// Get the product fields for this product from the database
			$query = "Select *
					From [|PREFIX|]product_configurable_fields
					Where fieldprodid='".(int)$productId."'
					Order by fieldsortorder ASC";
			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

			while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
				$fields[] = array(
					"id"			=> $row['productfieldid'],
					"name"			=> $row['fieldname'],
					"type"			=> $row['fieldtype'],
					"fileType"		=> $row['fieldfiletype'],
					"fileSize"		=> $row['fieldfilesize'],
					"selectOptions"	=> $row['fieldselectoptions'],
					"required"		=> $row['fieldrequired'],
					"layer"			=> $row['fieldlayer']
				);
			}
			return $fields;
		}
		
		public function GetProductFieldsActivatedByOption($optionName) {
			$query_cond = "SELECT concat(vovariationid, ',', vooptionsort) AS optioncond  
			FROM [|PREFIX|]product_variation_options 
			WHERE voname = '".$optionName."'";
			
			$optioncond = $GLOBALS["ISC_CLASS_DB"]->FetchOne($query_cond, 'optioncond');
			
			$query_fields = "SELECT fieldprodid FROM [|PREFIX|]product_configurable_fields 
			WHERE fieldlayeroptionid = '".$optioncond."'";
			return ($GLOBALS["ISC_CLASS_DB"]->CountResult($query_fields) > 0) ? true : false;
			
		}

		public function GetEventDateFields()
		{
			return array(
				'prodeventdaterequired' => $this->_product['prodeventdaterequired'],
				'prodeventdatefieldname' => $this->_product['prodeventdatefieldname'],
				'prodeventdatelimited' => $this->_product['prodeventdatelimited'],
				'prodeventdatelimitedtype' => $this->_product['prodeventdatelimitedtype'],
				'prodeventdatelimitedstartdate' => $this->_product['prodeventdatelimitedstartdate'],
				'prodeventdatelimitedenddate' => $this->_product['prodeventdatelimitedenddate'],
			);
		}

		/**
		* Gets the condition of the product
		*
		* @return string The condition of the product (New, Used or Refurbished)
		*/
		public function GetCondition()
		{
			return $this->_prodcondition;
		}

		/**
		* Gets the minimum quantity required in an order. A value of 0 means there is no minimum.
		*
		* @return int
		*/
		public function GetMinQty()
		{
			return (int)$this->_prodminqty;
		}

		/**
		* Gets the maximum quantity allowed in an order. A value of INF means there is no maximum.
		*
		* @return int
		*/
		public function GetMaxQty()
		{
			if ($this->_prodmaxqty == 0) {
				return INF;
			}

			return (int)$this->_prodmaxqty;
		}

		/**
		* Should the condition be displayed on product page?
		*
		* @return bool True if it should be shown or false if not
		*/
		public function IsConditionVisible()
		{
			return (bool)$this->_prodshowcondition;
		}


		/**
		* Insert GWO scripts to the product page.
		*
		*/
		private function _insertOptimizerScripts()
		{

			if(isset($_GET['optimizer'])){
				return;
			}
			// if optimizer is not enabled for this product
			if(!isset($this->_prodoptimizerenabled) || $this->_prodoptimizerenabled != 1) {
				return;
			}
			$optimizer = getClass('ISC_OPTIMIZER_PERPAGE');
			$optimizerDetails = $optimizer->getOptimizerDetails('product', $this->_prodid);
			if(empty($optimizerDetails)) {
				return;
			}

			$GLOBALS['PerPageOptimizerEnabled'] = $this->_prodoptimizerenabled;

			$GLOBALS['OptimizerControlScript'] = $optimizerDetails['optimizer_control_script'];
			$GLOBALS['OptimizerTrackingScript'] = $optimizerDetails['optimizer_tracking_script'];

			$GLOBALS['ProductNameOptimizerScriptTag'] = '<script>utmx_section("ProductName")</script>';
			$GLOBALS['ProductNameOptimizerNoScriptTag'] = '</noscript>';

			$GLOBALS['ProductImageOptimizerScriptTag'] = '<script>utmx_section("ProductImage")</script>';
			$GLOBALS['ProductImageOptimizerNoScriptTag'] = '</noscript>';

			$GLOBALS['AddToCartButtonOptimizerScriptTag'] = '<script>utmx_section("AddToCartButton")</script>';
			$GLOBALS['AddToCartButtonOptimizerNoScriptTag'] = '</noscript>';

			$GLOBALS['ProductDescriptionOptimizerScriptTag'] = '<script>utmx_section("ProductDescription")</script>';
			$GLOBALS['ProductDescriptionOptimizerNoScriptTag'] = '</noscript>';

			$GLOBALS['ProductTabsOptimizerScriptTag'] = '<script>utmx_section("ProductTabs")</script>';
			$GLOBALS['ProductTabsOptimizerNoScriptTag'] = '</noscript>';

		}


		/**
		 * Search for products
		 *
		 * Method will search for all the products and return an array of product records records
		 *
		 * @access public
		 * @param array $searchQuery The search query array. Currently will only understand the 'search_query' option
		 * @param int &$totalAmount The referenced variable to store in the total amount of the result
		 * @param int $start The optional start position of the result total. Default is 0
		 * @param int $limit The optional limit position of the result total. Default is -1 (no limit)
		 * @param string $sortBy The optional order by. Default is GetConfig("SearchDefaultProductSort")
		 * @return array The array result set on success, FALSE on error
		 */
		static public function searchForItems($searchQuery, &$totalAmount, $start=0, $limit=-1, $sortBy="")
		{
			if (trim($sortBy) == "") {
				$sortBy = GetConfig("SearchDefaultProductSort");
			}

			if (!is_array($searchQuery)) {
				return false;
			}

			$totalAmount = 0;

			if (!is_array($searchQuery) || empty($searchQuery)) {
				return false;
			}

			$fullTextFields = array("ps.prodname", "ps.prodcode", "ps.proddesc", "ps.prodsearchkeywords");

			$products = array();
			$query = "SELECT SQL_CALC_FOUND_ROWS p.*, FLOOR(p.prodratingtotal/p.prodnumratings) AS prodavgrating,
							" . GetProdCustomerGroupPriceSQL() . ", pi.* ";

			if (isset($searchQuery["search_query"]) && trim($searchQuery["search_query"]) !== "") {
				$query .= ", (IF(p.prodname='" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["search_query"]) . "', 10000, 0) +
							  IF(p.prodcode='" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["search_query"]) . "', 10000, 0) +
							  ((" . $GLOBALS["ISC_CLASS_DB"]->FullText(array("ps.prodname"), $searchQuery["search_query"], false) . ") * 10) +
								" . $GLOBALS["ISC_CLASS_DB"]->FullText($fullTextFields, $searchQuery["search_query"], false) . ") AS score ";
			}

			$query .= " FROM [|PREFIX|]products p
							LEFT JOIN [|PREFIX|]product_images pi ON (p.productid = pi.imageprodid AND pi.imageisthumb = 1) ";

			// Sorting or filtering by price. Need to join the tax pricing table
			if(!empty($searchQuery['price']) || !empty($searchQuery['price_from']) || !empty($searchQuery['price_to']) || $sortBy == 'priceasc' || $sortBy == 'pricedesc') {
				$priceColumn = 'tp.calculated_price';

				// Showing prices ex tax, so the tax zone ID = 0
				if(getConfig('taxDefaultTaxDisplayCatalog') == TAX_PRICES_DISPLAY_EXCLUSIVE) {
					$taxZone = 0;
				}
				// Showing prices inc tax, so we need to fetch the applicable tax zone
				else {
					$taxZone = getClass('ISC_TAX')->determineTaxZone();
				}

				$query .= '
					JOIN [|PREFIX|]product_tax_pricing tp
					ON (
						tp.price_reference=p.prodcalculatedprice AND
						tp.tax_zone_id='.$taxZone.' AND
						tp.tax_class_id=p.tax_class_id
					)
				';
			}
			else {
				$priceColumn = 'p.prodcalculatedprice';
			}

			if (isset($searchQuery["categoryid"])) {
				$searchQuery["category"] = array($searchQuery["categoryid"]);
			}

			$searchTerms = $GLOBALS['ISC_CLASS_SEARCH']->readSearchSession();

			$categorySearch = false;
			$categoryIds = array();
			$nestedset = new ISC_NESTEDSET_CATEGORIES;
			if (isset($searchQuery["category"]) && is_array($searchQuery["category"])) {
				foreach ($searchQuery["category"] as $categoryId) {
					// All categories were selected, so don"t continue
					if (!isId($categoryId)) {
						$categorySearch = false;
						break;
					}

					$categoryIds[] = (int)$categoryId;

					// If searching sub categories automatically, fetch & tack them on
					if (isset($searchQuery["searchsubs"]) && $searchQuery["searchsubs"] == "ON") {
						foreach ($nestedset->getTree(array('categoryid'), $categoryId) as $childCategory) {
							$categoryIds[] = (int)$childCategory['categoryid'];
						}
						unset($childCategory);
					}
				}

				$categoryIds = array_unique($categoryIds);
				if (!empty($categoryIds)) {
					$categorySearch = true;
				}
			}

			if ($categorySearch == true) {
				$query .= " INNER JOIN [|PREFIX|]categoryassociations a ON a.productid = p.productid AND a.categoryid IN (" . implode(",", $categoryIds) . ") ";
			}

			if (isset($searchQuery["search_query"]) && trim($searchQuery["search_query"]) !== "") {
				$query .= " INNER JOIN [|PREFIX|]product_search ps ON p.productid = ps.productid ";
			}

			$query .= " WHERE p.prodvisible = 1 " . GetProdCustomerGroupPermissionsSQL();

			// Do we need to filter on brand?
			if (isset($searchQuery["brand"]) && isId($searchQuery["brand"])) {
				$query .= " AND p.prodbrandid = " . (int)$searchQuery["brand"];
			}

			// Do we need to filter on price?
			if (isset($searchQuery["price"]) && is_numeric($searchQuery["price"])) {
				$query .= " AND ".$priceColumn." ='" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchTerms["price"]) . "'";
			} else {
				if (isset($searchQuery["price_from"]) && is_numeric($searchQuery["price_from"])) {
					$query .= " AND ".$priceColumn." >= '" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["price_from"]) . "'";
				}

				if (isset($searchQuery["price_to"]) && is_numeric($searchQuery["price_to"])) {
					$query .= " AND ".$priceColumn." <= '" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["price_to"]) . "'";
				}
			}

			// Do we need to filter on rating?
			if (isset($searchQuery["rating"])) {
				$query .= " AND FLOOR(p.prodratingtotal/p.prodnumratings) = " . (int)$searchQuery["rating"];
			} else {
				if (isset($searchQuery["rating_from"]) && is_numeric($searchQuery["rating_from"])) {
					$query .= " AND FLOOR(p.prodratingtotal/p.prodnumratings) >= " . (int)$searchQuery["rating_from"];
				}

				if (isset($searchQuery["rating_to"]) && is_numeric($searchQuery["rating_to"])) {
					$query .= " AND FLOOR(p.prodratingtotal/p.prodnumratings) <= " . (int)$searchQuery["rating_to"];
				}
			}

			// Do we need to filter on featured?
			if (isset($searchQuery["featured"]) && is_numeric($searchQuery["featured"])) {
				if ((int)$searchQuery["featured"] == 1) {
					$query .= " AND p.prodfeatured = 1 ";
				} else {
					$query .= " AND p.prodfeatured = 0 ";
				}
			}

			// Do we need to filter on free shipping?
			if (isset($searchQuery["shipping"]) && is_numeric($searchQuery["shipping"])) {
				if ((int)$searchQuery["shipping"] == 1) {
					$query .= " AND p.prodfreeshipping = 1 ";
				}
				else {
					$query .= " AND p.prodfreeshipping = 0 ";
				}
			}

			// Do we need to filter only products we have in stock?
			if (isset($searchQuery["instock"]) && is_numeric($searchQuery["instock"])) {
				if ((int)$searchQuery["instock"] == 1) {
					$query .= " AND (p.prodcurrentinv > 0 OR p.prodinvtrack = 0) ";
				}
			}

			if (isset($searchQuery["search_query"]) && trim($searchQuery["search_query"]) !== "") {
				$searchPart = array();

				if (GetConfig("SearchOptimisation") == "fulltext" || GetConfig("SearchOptimisation") == "both") {
					$searchPart[] = $GLOBALS["ISC_CLASS_DB"]->FullText($fullTextFields, $searchQuery["search_query"], true);
				}

				if (GetConfig("SearchOptimisation") == "like" || GetConfig("SearchOptimisation") == "both") {
					$searchPart[] = "p.prodname LIKE '%" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["search_query"]) . "%'";
					$searchPart[] = "p.proddesc LIKE '%" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["search_query"]) . "%'";
					$searchPart[] = "p.prodsearchkeywords LIKE '%" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["search_query"]) . "%'";
				}

				$query .= " AND (ps.prodcode = '" . $GLOBALS["ISC_CLASS_DB"]->Quote($searchQuery["search_query"]) . "' OR TRUE) ";
				$query .= " AND (" . implode(" OR ", $searchPart) . ") ";
			}

			$orderBy = "";

			switch (isc_strtolower($sortBy)) {
				case "relevance":
					if (isset($searchQuery["search_query"]) && trim($searchQuery["search_query"]) !== "") {
						$orderBy = "score DESC";
					}

					break;

				case "featured":
					$orderBy = "p.prodfeatured DESC";
					break;

				case "newest":
					$orderBy = "p.productid DESC";
					break;

				case "bestselling":
					$orderBy = "p.prodnumsold DESC";
					break;

				case "alphaasc":
					$orderBy = "p.prodname ASC";
					break;

				case "alphadesc":
					$orderBy = "p.prodname DESC";
					break;

				case "avgcustomerreview":
					$orderBy = "prodavgrating DESC";
					break;

				case "priceasc":
					$orderBy = $priceColumn.' ASC';
					break;

				case "pricedesc":
					$orderBy = $priceColumn.' DESC';
					break;
			}

			if (trim($orderBy) !== "") {
				$query .= " ORDER BY " . $orderBy;
			} else {
				$query .= " ORDER BY p.productid DESC";
			}

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
			$products[$row["productid"]] = $row;

			while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
				$products[$row["productid"]] = $row;
			}

			return $products;
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

			$totalRecords = $GLOBALS["ISC_CLASS_SEARCH"]->GetNumResults("product");

			if ($totalRecords == 0) {
				return "";
			}

			$altCount = -1;
			$results = $GLOBALS["ISC_CLASS_SEARCH"]->GetResults("product");
			$resultHTML = "";

			if (!array_key_exists("results", $results) || !is_array($results["results"])) {
				return "";
			}

			if (GetConfig("SearchProductDisplayMode") == "list") {
				$displayMode = "List";
				$GLOBALS["AlternateClass"] = "ListView ";
			} else {
				$displayMode = "Grid";
				$GLOBALS["AlternateClass"] = "";
			}

			// Should we hide the comparison button?
			if(GetConfig('EnableProductComparisons') == 0 || $totalRecords < 2) {
				$GLOBALS['HideCompareItems'] = "none";
			}
			
			$custInfo = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerInfo();

			foreach ($results["results"] as $product) {
				if (!is_array($product) || !array_key_exists("productid", $product)) {
					continue;
				}

				if (++$altCount%2 > 0) {
					$GLOBALS["AlternateClass"] .= "Odd";
				} else {
					$GLOBALS["AlternateClass"] .= "Even";
				}

				if (GetConfig("SearchProductDisplayMode") == "list") {
					$GLOBALS["AlternateClass"] .= " ListView";
				}

				$GLOBALS["ProductCartQuantity"] = "";
				if (isset($GLOBALS["CartQuantity" . $product["productid"]])) {
					$GLOBALS["ProductCartQuantity"] = (int)$GLOBALS["CartQuantity" . $product["productid"]];
				}
				// Sarch for show the sku (code) of the products
				$queryClave = "	SELECT Articulo
							FROM [|PREFIX|]intelisis_products
							WHERE productid = '".$product["productid"]."'";
				$GLOBALS["ProductSearchClave"] = $GLOBALS["ISC_CLASS_DB"]->FetchOne($queryClave, 'Articulo');

				$GLOBALS["ProductId"] = (int)$product["productid"];
				$GLOBALS["ProductName"] = isc_html_escape($product["prodname"]);
				$GLOBALS["ProductLink"] = ProdLink($product["prodname"]);
				$GLOBALS["ProductRating"] = (int)$product["prodavgrating"];

				// Determine the price of this product
				//REQ11191 JIB: Verifica la condicion de ShowPriceGuest
				$GLOBALS['ProductPrice'] = '';
				if (((GetConfig('ShowProductPrice') && !$product['prodhideprice']) && $custInfo != NULL) || ((GetConfig('ShowPriceGuest') && GetConfig('ShowProductPrice') && !$product['prodhideprice'] && $custInfo == NULL))) {
					$GLOBALS['ProductPrice'] = formatProductCatalogPrice($product);
				}

				$GLOBALS["ProductThumb"] = ImageThumb($product, ProdLink($product["prodname"]), "", "TrackLink");

				if (isId($product["prodvariationid"]) || trim($product["prodconfigfields"]) !== "" || $product["prodeventdaterequired"] == 1) {
					$GLOBALS["ProductURL"] = ProdLink($product["prodname"]);
					$GLOBALS["ProductAddText"] = GetLang("ProductChooseOptionLink");
				} else {
					$GLOBALS["ProductURL"] = CartLink($product["productid"]);
					$GLOBALS["ProductAddText"] = GetLang("ProductAddToCartLink");
				}

				if (CanAddToCart($product) && GetConfig("ShowAddToCartLink")) {
					$GLOBALS["HideActionAdd"] = "";
				} else {
					$GLOBALS["HideActionAdd"] = "none";
				}

				$GLOBALS["HideProductVendorName"] = "display: none";
				$GLOBALS["ProductVendor"] = "";

				if (GetConfig("ShowProductVendorNames") && $product["prodvendorid"] > 0) {
					$vendorCache = $GLOBALS["ISC_CLASS_DATA_STORE"]->Read("Vendors");
					if (isset($vendorCache[$product["prodvendorid"]])) {
						$GLOBALS["ProductVendor"] = "<a href=\"" . VendorLink($vendorCache[$product["prodvendorid"]]) . "\">" . isc_html_escape($vendorCache[$product["prodvendorid"]]["vendorname"]) . "</a>";
						$GLOBALS["HideProductVendorName"] = "";
					}
				}

				// for list style
				if ($displayMode == "List") {
					// get a small chunk of the product description
					$desc = isc_substr(strip_tags($product["proddesc"]), 0, 250);
					if (isc_strlen($product["proddesc"]) > 250) {
						// trim the description back to the last period or space so words aren"t cut off
						$period_pos = isc_strrpos($desc, ".");
						$space_pos = isc_strrpos($desc, " ");
						// find the character that we should trim back to. -1 on space pos for a space that follows a period, so we dont end up with 4 periods
						if ($space_pos - 1 > $period_pos) {
							$pos = $space_pos;
						} else {
							$pos = $period_pos;
						}
						$desc = isc_substr($desc, 0, $pos);
						$desc .= "...";
					}

					$GLOBALS["ProductDescription"] = $desc;

					$GLOBALS["AddToCartQty"] = "";

					if (CanAddToCart($product) && GetConfig("ShowAddToCartLink")) {
						if (isId($product["prodvariationid"]) || trim($product["prodconfigfields"]) !== "" || $product["prodeventdaterequired"]) {
							$GLOBALS["AddToCartQty"] = "<a href=\"" . $GLOBALS["ProductURL"] . "\">" . $GLOBALS["ProductAddText"] . "</a>";
						} else {
							$GLOBALS["CartItemId"] = $GLOBALS["ProductId"];
							// If we"re using a cart quantity drop down, load that
							if (GetConfig("TagCartQuantityBoxes") == "dropdown") {
								$GLOBALS["Quantity0"] = "selected=\"selected\"";
								$GLOBALS["QtyOptionZero"] = "<option %%GLOBAL_Quantity0%% value=\"0\">".GetLang('Quantity')."</option>";
								$GLOBALS["QtySelectStyle"] = "width: auto;";
								$GLOBALS["AddToCartQty"] = $GLOBALS["ISC_CLASS_TEMPLATE"]->GetSnippet("CartItemQtySelect");
							// Otherwise, load the textbox
							} else {
								$GLOBALS["ProductQuantity"] = 0;
								$GLOBALS["AddToCartQty"] = $GLOBALS["ISC_CLASS_TEMPLATE"]->GetSnippet("CartItemQtyText");
							}
						}
					}

				// for grid style
				} else {
					$GLOBALS["CompareOnSubmit"] = "onsubmit=\"return compareProducts(config.CompareLink)\"";
				}

				$resultHTML .= $GLOBALS["ISC_CLASS_TEMPLATE"]->GetSnippet("SearchResultProduct" . $displayMode);
			}

			$resultHTML = trim($resultHTML);
			return $resultHTML;
		}

		/**
		 * Build the array of searched item results for the AJAX request
		 *
		 * Method will build an array of searched item results for the AJAX request. Method will work with the ISC_SEARCH
		 * class to get the results so make sure that the object is initialised and the DoSearch executed.
		 *
		 * Each key in the array will be the 'score' value (as a string) so it can be merged in with other results and can
		 * then be further sorted using any PHP array sorting functions, so output would be something like this:
		 *
		 * EG: return = array(10, // result count
		 *                    array(
		 *                        "12.345" => array(
		 *                                          0 => [product HTML]
		 *                                          1 => [product HTML]
		 *                                          2 => [product HTML]
		 *                                    ),
		 *                        "2.784" => array(
		 *                                          0 => [product HTML]
		 *                                    ),
		 *                        "6.242" => array(
		 *                                          0 => [product HTML]
		 *                                          1 => [product HTML]
		 *                                   )
		 *                    )
		 *              );
		 *
		 * @access public
		 * @return array An array with two values, first is total number of search results. Other is the search item results AJAX array on success, empty array on error
		 */
		static public function buildSearchResultsAJAX()
		{
			if (!isset($GLOBALS["ISC_CLASS_SEARCH"]) || !is_object($GLOBALS["ISC_CLASS_SEARCH"])) {
				return array(0, array());
			}

			$totalRecords = $GLOBALS["ISC_CLASS_SEARCH"]->GetNumResults("product");

			if ($totalRecords == 0) {
				return array(0, array());
			}

			$results = $GLOBALS["ISC_CLASS_SEARCH"]->GetResults("product");
			$ajaxArray = array();

			if (!array_key_exists("results", $results) || !is_array($results["results"])) {
				return array();
			}

			$products = $results["results"];
			$custInfo = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerInfo();

			foreach ($products as $product) {
				if (!isset($product["score"])) {
					$product["score"] = 0;
				}
				//REQ11191 JIB: Verifica la condicion de ShowPriceGuest
				$GLOBALS["ProductName"] = $product["prodname"];
				$GLOBALS["ProductURL"] = ProdLink($product["prodname"]);
				$GLOBALS['ProductPrice'] = '';
				if (((GetConfig('ShowProductPrice') && !$product['prodhideprice']) && $custInfo != NULL) || ((GetConfig('ShowPriceGuest') && GetConfig('ShowProductPrice') && !$product['prodhideprice'] && $custInfo == NULL))) {
					$GLOBALS['ProductPrice'] = formatProductCatalogPrice($product);
				}

				if(getProductReviewsEnabled()) {
					$ratingURL = $GLOBALS["IMG_PATH"] . "/IcoRating" . (int)$product["prodavgrating"] . ".gif";
					$GLOBALS["ProductRatingImage"] = "<img src=\"" . $ratingURL . "\" class=\"RatingIMG\" />";
				} else {
					$GLOBALS["ProductRatingImage"] = "";
				}

				$GLOBALS["ProductNoImageClassName"] = "";

				if (isset($product["imageid"]) && $product["imageid"] !== "") {
					$image = new ISC_PRODUCT_IMAGE();
					$image->populateFromDatabaseRow($product);
					$productImageSize = $image->getResizedFileDimensions(ISC_PRODUCT_IMAGE_SIZE_TINY, true);
					if ($productImageSize[0] > 70) {
						// ISCFIVEFIVEBETA-89 - cap to 70px wide
						// note: will need to adjust height by proper ratio if we want the height output to html
						$productImageSize[0] = 70;
					}
					$GLOBALS["ProductImage"] = "<img src=\"" . isc_html_escape($image->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_TINY, true)) . "\" alt=\"" . isc_html_escape($product["prodname"]) . "\" title=\"" . isc_html_escape($product["prodname"]) . "\" width=\"" . $productImageSize[0] . "\" />";
				} else {
					$GLOBALS["ProductNoImageClassName"] = "QuickSearchResultNoImage";
					$GLOBALS["ProductImage"] = "<span>" . GetLang("QuickSearchNoImage") . "</span>";
				}

				$sortKey = (string)$product["score"];

				if (!array_key_exists($sortKey, $ajaxArray) || !is_array($ajaxArray[$sortKey])) {
					$ajaxArray[$sortKey] = array();
				}

				$ajaxArray[$sortKey][] = $GLOBALS["ISC_CLASS_TEMPLATE"]->GetSnippet("SearchResultAJAXProduct");
			}

			return array($totalRecords, $ajaxArray);
		}
	}
