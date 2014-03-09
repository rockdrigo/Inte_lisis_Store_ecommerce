<?php

	class ISC_SIDECUSTOMCATEGORYLIST_PANEL extends PANEL
	{
		private $cacheable = true;
		private $cacheId = "categories.sidecategorylist";

		public function SetPanelSettings()
		{
			$output = '';
			$catList = array();
			if(isset($GLOBALS['ISC_CLASS_PRODUCT'])) {
				$query = sprintf("
					SELECT c.categoryid, c.catparentlist
					FROM [|PREFIX|]categoryassociations ca
					INNER JOIN [|PREFIX|]categories c ON (c.categoryid=ca.categoryid)
					WHERE ca.productid='%d'",
					$GLOBALS['ISC_CLASS_DB']->Quote($GLOBALS['ISC_CLASS_PRODUCT']->GetProductId())
				);
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$parentList = array();
				while($cat = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$parentList[$cat['categoryid']] = $cat['catparentlist'].',';
				}
			}
			else if(isset($GLOBALS['ISC_CLASS_CATEGORY'])) {
				$catId = $GLOBALS['ISC_CLASS_CATEGORY']->GetCategoryId();
				$query = "
					SELECT categoryid, catparentlist
					FROM [|PREFIX|]categories
					WHERE categoryid='".$catId."'
				";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$parentList = array();
				while($cat = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$parentList[$cat['categoryid']] = $cat['catparentlist'].',';
				}
				// In one of the root categories so set the parent list to the current category
				if(!$parentList[$catId]) {
					$parentList[$catId] = $catId;
				}
			}
			else {
				$this->DontDisplay = true;
				return;
			}

			$rootCategories = array();
			$categories = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('RootCategories');

			$parentListCsv = implode(',', $parentList);
			$parentListCsv = trim($parentListCsv, ',');

			$tree = explode(',', $parentListCsv);
			foreach($tree as $catId) {
				if(isset($categories[$catId])) {
					foreach($categories[$catId] as $child) {
						$rootCategories[] = $child['categoryid'];
					}
				}
			}

			$catsToFetch = implode(',', $rootCategories);
			if(!$catsToFetch) {
				$this->DontDisplay = true;
				return;
			}

			$children = array();
			$query = "
				SELECT *
				FROM [|PREFIX|]categories
				WHERE catparentid IN (".$catsToFetch.") AND catvisible=1
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while($child = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$children[$child['catparentid']][] = $child;
			}

			foreach($parentList as $catId => $categoryList) {
				$tree = explode(',', $categoryList);
				if(isset($categories[0][$tree[0]])) {
					$GLOBALS['RootCatName'] = isc_html_escape($categories[0][$tree[0]]['catname']);
				}
				else {
					$GLOBALS['RootCatName'] = '';
				}

				$catList = '';
				foreach($categories[$tree[0]] as $rootCat) {
					// If we don't have permission to view this category then skip
					if(!CustomerGroupHasAccessToCategory($rootCat['categoryid'])) {
						continue;
					}


					$GLOBALS['SubCategoryList'] = $this->GetSubCategory($children, $rootCat['categoryid']);
					$GLOBALS['LastChildClass']='';
					$GLOBALS['CategoryName'] = isc_html_escape($rootCat['catname']);
					$GLOBALS['CategoryLink'] = CatLink($rootCat['categoryid'], $rootCat['catname']);
					$GLOBALS['CategoryId'] = ''; // dn
					$catList .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SideCategoryList");
				}
				$GLOBALS['RootCatList'] = $catList;
				$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('SideCustomCategoryList');
			}

			if(!$output) {
				$this->DontDisplay = true;
				return;
			}

			$GLOBALS['SNIPPETS']['SideCategoryList'] = $output;
		}


		/**
		* get the html for sub category list
		*
		* @param array $categories the array of all categories in a tree structure
		* @param int $parentCatId the parent category ID of the sub category list
		*
		* return string the html of the sub category list
		*/
		private function GetSubCategory($categories, $parentCatId)
		{

			$output = '';
			//if there is sub category for this parent cat
			if (isset($categories[$parentCatId]) && !empty($categories[$parentCatId])) {
				$i=1;
				foreach ($categories[$parentCatId] as $subCat) {
					// If we don't have permission to view this category then skip
					if (!CustomerGroupHasAccessToCategory($subCat['categoryid'])) {
						continue;
					}
					$catLink = CatLink($subCat['categoryid'], $subCat['catname'], false);
					$catName = isc_html_escape($subCat['catname']);

					$GLOBALS['SubCategoryList'] = $this->GetSubCategory($categories, $subCat['categoryid']);

					//set the class for the last category of its parent category
					$GLOBALS['LastChildClass']='';
					if($i == count($categories[$parentCatId])) {
						$GLOBALS['LastChildClass']='LastChild';
					}
					$i++;

					$GLOBALS['CategoryName'] = $catName;
					$GLOBALS['CategoryLink'] = $catLink;
					$GLOBALS['CategoryId'] = ''; // dn
					$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SideCategoryList");
				}
			}
			if ($output!='') {
				$output = '<ul>'.$output.'</ul>';
			}
			return $output;
		}
	}