<?php

	class ISC_COMPARE
	{

		private $_compareproducts = array();
		private $_comparetitle = "";
		private $_compareids = "";
		private $_comparesort = "";

		public function __construct()
		{
			// If comparisons aren't enabled, redirect the user back to the homepage
			if(GetConfig('EnableProductComparisons') == 0) {
				header("Location: ".$GLOBALS['ShopPath']);
				exit;
			}
			$this->SetComparisonData();
		}

		public function SetComparisonData()
		{
			if ($GLOBALS['EnableSEOUrls']) {
				$path = '/'.implode('/', $GLOBALS['PathInfo']).'/';
			} else {
				if (isset($_SERVER['REQUEST_URI'])) {
					$path = $_SERVER['REQUEST_URI'];
				} elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
					$path = $_SERVER['HTTP_X_REWRITE_URL'];
				} else {
					$path = $_SERVER['QUERY_STRING'];
				}
				$path = preg_replace('#\.php[\?]?#si', '/', $path);
			}


			// Retrieve the query string variables. Can't use the $_GET array
			// because of SEO friendly links in the URL
			SetPGQVariablesManually();

			if (isset($_GET['sort'])) {
				$sort = $_GET['sort'];

				switch ($sort) {
					case "product_name": {
						$this->_comparesort = "p.prodname asc";
						break;
					}
					case "product_price": {
						$this->_comparesort = "p.prodcalculatedprice asc";
						break;
					}
					case "product_rating": {
						$this->_comparesort = "prodavgrating asc";
						break;
					}
					case "product_brand": {
						$this->_comparesort = "brand asc";
						break;
					}
				}
			}

			if (is_numeric(isc_strpos($path, '/compare/'))) {
				$iPos = isc_strpos($path, '/compare/') + isc_strlen('/compare/');
				$ids = isc_substr($path, $iPos, isc_strlen($path));
			}
			else {
				$ids = $path;
			}

			// With SEO urls off the url might have additional get vars (e.g. sorting) so make sure
			// we don't include those otherwise the last productid will fail the is_numeric check
			if (isc_strpos($ids, '&') !== false) {
				$ids = isc_substr($ids, 0, isc_strpos($ids, '&'));
			}

			$ids = rtrim($ids, '/');
			$ids_array = explode('?', $ids);
			$ids = $ids_array[0];

			$exploded_ids = explode('/', $ids);

			foreach ($exploded_ids as $k => $v) {
				if (!is_numeric($v) || $v < 0) {
					unset($exploded_ids[$k]);
				}
			}
			$exploded_ids = array_unique($exploded_ids);

			$this->_compareids = implode('/', $exploded_ids);
			$this->_compareproducts = $exploded_ids;

			// Are we comparing more products than allowed by the template?
			$this->CheckTemplateRestrictions();

			// Load the products to compare
			$this->LoadProductsToCompare();
		}

		/**
		* CheckTemplateRestrictions
		* Make sure the customer isn't trying to compare more products than allowed by the template. If they are we'll show a message telling them so.
		*
		* @return Void
		*
		*/
		public function CheckTemplateRestrictions()
		{
			// Hide the error message by default
			$GLOBALS['HideTooManyProductsMessage'] = "none";

			if(count($this->_compareproducts) > $GLOBALS['TPL_CFG']['MaxComparisonProducts']) {
				// How many products do we need to splice off the array?
				$remove = count($this->_compareproducts) - $GLOBALS['TPL_CFG']['MaxComparisonProducts'];

				// Splice the product ids array
				array_splice($this->_compareproducts, count($this->_compareproducts) - $remove, $remove);

				$GLOBALS['HideTooManyProductsMessage'] = "";

				if($remove == 1) {
					$lang_var = "TooManyProducts1";
				}
				else {
					$lang_var = "TooManyProductsX";
				}

				$GLOBALS['TooManyProductsMessage'] = sprintf(GetLang($lang_var), $GLOBALS['TPL_CFG']['MaxComparisonProducts'], $remove);
			}
		}

		public function HandlePage()
		{
			$this->ShowPage();
		}

		public function GetNumProducts()
		{
			return count($this->_compareproducts);
		}

		public function GetProductIds()
		{
			return implode(",", $this->_compareproducts);
		}

		public function LoadProductsToCompare()
		{

			$count = 0;
			$output = "";
			$tOutput = "";
			$products = array();

			// First row - the "Remove" link
			$GLOBALS['SNIPPETS']['TD1'] = "";
			$GLOBALS['SNIPPETS']['TD2'] = "";
			$GLOBALS['SNIPPETS']['TD3'] = "";
			$GLOBALS['SNIPPETS']['TD4'] = "";
			$GLOBALS['SNIPPETS']['TD5'] = "";
			$GLOBALS['SNIPPETS']['TD6'] = "";
			$GLOBALS['SNIPPETS']['TD7'] = "";
			$GLOBALS['SNIPPETS']['TD8'] = "";
			$GLOBALS['SNIPPETS']['TD9'] = "";
			$GLOBALS['SNIPPETS']['TD10'] = "";
			$GLOBALS['SNIPPETS']['TD11'] = "";

			// Do we need to sort?
			if ($this->_comparesort != "") {
				$sort = sprintf("order by %s", $this->_comparesort);
			} else {
				$sort = "";
			}

			$product_ids = $this->GetProductIds();

			if (empty($product_ids)) {
				return;
			}

			$productids_array = explode('/', $this->GetIds());

			if ($GLOBALS['EnableSEOUrls'] == 1) {
				$GLOBALS['BaseCompareLink'] = CompareLink($productids_array).'?';
			} else {
				$GLOBALS['BaseCompareLink'] = CompareLink($productids_array).'&amp;';
			}



			$compareWidth = 100 - 20;
			$compareWidth = floor($compareWidth / count($this->_compareproducts));
			$GLOBALS['CompareWidth'] = $compareWidth."%";
			$GLOBALS['CompareHeadWidth'] = 100-($compareWidth*count($this->_compareproducts))."%";

			$query = "
				SELECT p.*, FLOOR(prodratingtotal/prodnumratings) AS prodavgrating, pi.*, ".GetProdCustomerGroupPriceSQL().",
				(SELECT brandname FROM [|PREFIX|]brands WHERE brandid=prodbrandid) AS brand,
				(select count(fieldid) from [|PREFIX|]product_customfields where fieldprodid=p.productid) as numcustomfields,
				(select count(reviewid) from [|PREFIX|]reviews where revproductid=p.productid and revstatus='1') AS numreviews
				FROM [|PREFIX|]products p
				LEFT JOIN [|PREFIX|]product_images pi ON (p.productid=pi.imageprodid AND pi.imageisthumb=1)
				WHERE p.prodvisible='1' AND p.productid IN (".$product_ids.")
				".GetProdCustomerGroupPermissionsSQL()."
			".$sort;
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {

				$GLOBALS['ProductNumber'] = $count++;

				if ($row['brand'] != "") {
					$GLOBALS['ProductBrand'] = sprintf("<a href='%s'>%s</a>", BrandLink($row['brand']), isc_html_escape($row['brand']));
				} else {
					$GLOBALS['ProductBrand'] = GetLang('NA');
				}

				// Build the page title
				$this->_comparetitle .= sprintf("%s %s ", isc_html_escape($row['prodname']), GetLang('VS'));
				$GLOBALS['ProductId'] = $row['productid'];
				$GLOBALS['ProductName'] = isc_html_escape($row['prodname']);
				$GLOBALS['ProductLink'] = ProdLink($row['prodname']);
				$GLOBALS['NumReviews'] = $row['numreviews'];

				if ($row['numreviews'] == 0) {
					$GLOBALS['HideComparisonReviewLink'] = "none";
				} else {
					$GLOBALS['HideComparisonReviewLink'] = "";
				}

				$GLOBALS['ProductThumb'] = ImageThumb($row, ProdLink($row['prodname']));

				// Determine the price of this product
				$GLOBALS['HideProductPrice'] = '';
				$GLOBALS['ProductPrice'] = '';
				if (GetConfig('ShowProductPrice') && !$row['prodhideprice']) {
					$GLOBALS['ProductPrice'] = formatProductCatalogPrice($row);
				} else {
					$GLOBALS['HideProductPrice'] = 'display:none;';
				}

				if ($row['prodavailability'] != "") {
					$GLOBALS['ProductAvailability'] = isc_html_escape($row['prodavailability']);
				} else {
					$GLOBALS['ProductAvailability'] = GetLang('NA');
				}

				$compare_ids = array_diff($this->_compareproducts, array($row['productid']));

				if(count($compare_ids) == 1) {
					$GLOBALS['RemoveCompareLink'] = "javascript:alert('%%LNG_CompareTwoProducts%%');";
				}
				else {
					$GLOBALS['RemoveCompareLink'] = CompareLink($compare_ids);
					if (!empty($this->_comparesort)) {
						$GLOBALS['RemoveCompareLink'] .= '?sort='.$_GET['sort'];
					}
				}

				$GLOBALS['ProductRating'] = (int)$row['prodavgrating'];

				if ($row['proddesc'] != "") {
					// Strip out HTML from the description first
					$row['proddesc'] = strip_tags($row['proddesc']);

					if (isc_strlen($row['proddesc']) > 200) {
						$GLOBALS['ProductSummary'] = isc_substr($row['proddesc'], 0, 200) . "...";
					} else {
						$GLOBALS['ProductSummary'] = $row['proddesc'];
					}
				}
				else {
					$GLOBALS['ProductSummary'] = GetLang('NA');
				}

				// Are there any custom fields?
				if ($row['numcustomfields'] > 0) {

					$GLOBALS['CustomFields'] = "";

					// Get the custom fields for this product
					$query = "SELECT * FROM [|PREFIX|]product_customfields WHERE fieldprodid='" . $GLOBALS['ISC_CLASS_DB']->Quote($row['productid']) . "' ORDER BY fieldid";
					$cResult = $GLOBALS['ISC_CLASS_DB']->Query($query);

					while ($cRow = $GLOBALS['ISC_CLASS_DB']->Fetch($cResult)) {
						$GLOBALS['CustomFieldName'] = isc_html_escape($cRow['fieldname']);
						$GLOBALS['CustomFieldValue'] = $cRow['fieldvalue'];
						$GLOBALS['CustomFields'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductCustomField");
					}
				}
				else {
					$GLOBALS['CustomFields'] = GetLang('NA');
				}

				$GLOBALS['SNIPPETS']['TD1'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductTD1");
				$GLOBALS['SNIPPETS']['TD2'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductTD2");
				$GLOBALS['SNIPPETS']['TD3'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductTD3");
				$GLOBALS['SNIPPETS']['TD4'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductTD4");
				$GLOBALS['SNIPPETS']['TD5'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductTD5");
				$GLOBALS['SNIPPETS']['TD6'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductTD6");
				$GLOBALS['SNIPPETS']['TD7'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductTD7");
				$GLOBALS['SNIPPETS']['TD8'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductTD8");
				$GLOBALS['SNIPPETS']['TD9'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductTD9");
			}

			if(!getProductReviewsEnabled()) {
				$GLOBALS['HideProductRating'] = "display: none;";
			}

			$output1 = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductTR1");
			$output2 = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductTR2");
			$output3 = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductTR3");
			$output4 = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductTR4");
			$output5 = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductTR5");
			$output6 = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductTR6");
			$output7 = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductTR7");
			$output8 = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductTR8");
			$output9 = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CompareProductTR9");

			$output = $output1 . $output2 . $output3 . $output4 . $output9 . $output5 . $output6 . $output7 . $output8;

			$output = $GLOBALS['ISC_CLASS_TEMPLATE']->ParseSnippets($output, $GLOBALS['SNIPPETS']);
			$GLOBALS['SNIPPETS']['ComparisonList'] = $output;
		}

		public function BuildTitle()
		{
			$this->_comparetitle = preg_replace('#'.preg_quote(GetLang('VS'), '#') . ' $#', "", $this->_comparetitle);
			return sprintf("%s: %s", GetLang('ProductComparison'), $this->_comparetitle);
		}

		public function GetIds()
		{
			return $this->_compareids;
		}

		public function ShowPage()
		{
			$heading = $this->BuildTitle();

			if (isc_strlen($heading) > 70) {
				$GLOBALS['ComparisonHeading'] = isc_substr($heading, 0, 70) . "...";
			} else {
				$GLOBALS['ComparisonHeading'] = $heading;
			}

			$GLOBALS['NumCompareItems'] = $this->GetNumProducts();

			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle($this->BuildTitle());
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("compare");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}
	}