<?php

	/*******************************************\
	*                                           *
	*  Generic Interspire Shopping Cart Panel Parsing Class   *
	*                                           *
	\*******************************************/

	CLASS ISC_CATEGORYHEADING_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			$GLOBALS['ISC_CLASS_CATEGORY'] = GetClass('ISC_CATEGORY');

			// Output breadcrumb trail
			$GLOBALS['SNIPPETS']['CatTrail'] = "";

			if ($GLOBALS['EnableSEOUrls'] == 1) {
				$baseLink = sprintf("%s/categories", $GLOBALS['ShopPath']);
			} else {
				$baseLink = sprintf("%s/categories.php?category=", $GLOBALS['ShopPath']);
			}

			$count = 0;
			$catPath = '';
			if (isset($GLOBALS['CatTrail']) && is_array($GLOBALS['CatTrail'])) {
				foreach($GLOBALS['CatTrail'] as $trail) {
					$baseLink .= "/" . MakeURLSafe($trail[1]);
					$catPath .= MakeURLSafe($trail[1])."/";

					$GLOBALS['CatTrailName'] = isc_html_escape($trail[1]);
					$GLOBALS['CatTrailLink'] = $baseLink;

					if($count++ == count($GLOBALS['CatTrail'])-1) {
						$GLOBALS['SNIPPETS']['CatTrail'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("BreadcrumbItemCurrent");
					}
					else {
						$GLOBALS['SNIPPETS']['CatTrail'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("BreadcrumbItem");
					}
				}
			}

			$catPath = rtrim($catPath, "/");

			// Output sub-categories
			$GLOBALS['SNIPPETS']['SubCategories'] = "";
			$query = sprintf("select * from [|PREFIX|]categories where catparentid='%d' and catvisible=1 order by catsort asc, catname asc", $GLOBALS['ISC_CLASS_DB']->Quote($GLOBALS['CatId']));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			if($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {

				// Check to see if we need to add in place holder images or if we are just displaying text
				if (!($rtn = $GLOBALS['ISC_CLASS_DB']->Fetch($GLOBALS['ISC_CLASS_DB']->Query("SELECT COUNT(*) AS Total FROM [|PREFIX|]categories WHERE catparentid='" . (int)$GLOBALS['CatId'] . "' AND catimagefile != ''"))) || !$rtn['Total']) {
					$useImages = false;
				} else {
					$useImages = true;
					if (GetConfig('CategoryDefaultImage') !== '') {
						$defaultImage = GetConfig('ShopPath') . '/' . GetConfig('CategoryDefaultImage');
					} else {
						$defaultImage = $GLOBALS['IMG_PATH'].'/CategoryDefault.gif';
					}
				}

				$i = 0;
				while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$i++;
					if (!CustomerGroupHasAccessToCategory($row['categoryid'])) {
						continue;
					}

					$GLOBALS['SubCatName'] = isc_html_escape($row['catname']);
					$GLOBALS['SubCatLink'] = CatLink($row['categoryid'], $row['catname']);
					if ($useImages) {
						if ($row['catimagefile'] !== '') {
							$GLOBALS['SubCatImage'] = GetConfig('ShopPath') . '/' . GetConfig('ImageDirectory') . '/' . $row['catimagefile'];
						} else {
							$GLOBALS['SubCatImage'] = $defaultImage;
						}

						$GLOBALS['ISC_CLASS_TEMPLATE']->assign('width', getConfig('CategoryImageWidth'));
						$GLOBALS['ISC_CLASS_TEMPLATE']->assign('height', getConfig('CategoryImageHeight') + 50);

						$GLOBALS['SNIPPETS']['SubCategories'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SubCatItemImage");

						if ($i % GetConfig('CategoryPerRow') == 0) {
							$GLOBALS['SNIPPETS']['SubCategories'] .= '<li class="RowDivider" style="float:none; clear:both;"></li>';
						}

					} else {
						$GLOBALS['SNIPPETS']['SubCategories'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SubCatItem");
					}
				}

				if ($useImages) {
					if ($i % GetConfig('CategoryPerRow') > 0) {
						$GLOBALS['SNIPPETS']['SubCategories'] .= '<li style="float: none; clear: both;"/>';
					}
					$output = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SubCategoriesGrid");
				} else {
					$output = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SubCategories");
				}

				$output = $GLOBALS['ISC_CLASS_TEMPLATE']->ParseSnippets($output, $GLOBALS['SNIPPETS']);
				$GLOBALS['SNIPPETS']['SubCategories'] = $output;
			}

			if($GLOBALS['ISC_CLASS_CATEGORY']->GetNumProducts() > 1) {
				// Parse the sort select box snippet
				$queryStringAppend = array();
				if(!empty($_GET['price_min'])) {
					$queryStringAppend['price_min'] = (float)$_GET['price_min'];
				}

				if(!empty($_GET['price_max'])) {
					$queryStringAppend['price_max'] = (float)$_GET['price_max'];
				}


				if($GLOBALS['EnableSEOUrls'] == 1) {
					$GLOBALS['URL'] = CatLink($GLOBALS['CatId'], $GLOBALS['ISC_CLASS_CATEGORY']->GetName(), false);
				}
				else {
					$GLOBALS['URL'] = $GLOBALS['ShopPath']."/categories.php";
					$queryStringAppend['category'] = $catPath;
				}

				$GLOBALS['HiddenSortField'] = '';
				foreach($queryStringAppend as $k => $v) {
					$GLOBALS['HiddenSortField'] .= "<input type=\"hidden\" name=\"".$k."\" value=\"".isc_html_escape($v)."\" />";
				}

				$GLOBALS['SNIPPETS']['CategorySortBox'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CategorySortBox");
			}

			// Is the category empty?
			if($GLOBALS['ISC_CLASS_CATEGORY']->GetNumProducts() == 0) {
				$GLOBALS['ExtraCategoryClass'] = "Wide WideWithLeft";
				if($GLOBALS['SNIPPETS']['SubCategories'] != '') {
					$GLOBALS['CategoryProductListing'] = '';
				}
				$GLOBALS['HideRightColumn'] = "none";
			}
		}
	}