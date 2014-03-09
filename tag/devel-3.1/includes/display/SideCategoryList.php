<?php

	CLASS ISC_SIDECATEGORYLIST_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			if (ISC_CATEGORY::areCategoryFlyoutsEnabled()) {
				$GLOBALS['SideCategoryListTypeClass'] = 'SideCategoryListFlyout';
				$output = $this->_generateFlyoutOutput();
			} else {
				$GLOBALS['SideCategoryListTypeClass'] = 'SideCategoryListClassic';
				$output = $this->_generateClassicOutput();
			}

			if (!$output) {
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
		protected function _getSubCategory($categories, $parentCatId)
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

					$GLOBALS['SubCategoryList'] = $this->_getSubCategory($categories, $subCat['categoryid']);

					//set the class for the last category of its parent category
					$GLOBALS['LastChildClass']='';
					if($i == count($categories[$parentCatId])) {
						$GLOBALS['LastChildClass']='LastChild';
					}
					$i++;

					$GLOBALS['CategoryName'] = $catName;
					$GLOBALS['CategoryLink'] = $catLink;
					$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SideCategoryList");
				}
			}
			if ($output!='') {
				// @todo ul here is hacky but front end templates are limited, fix this when possible
				$output = '<ul>'.$output.'</ul>';
			}
			return $output;
		}

		/**
		 * This method creates and returns front-end output for original, non-flyout-enabled category menus
		 *
		 * @return string
		 */
		protected function _generateClassicOutput ()
		{
			$output = "";
			$categories = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('RootCategories');

			if (!isset($categories[0])) {
				return $output;
			}

			foreach($categories[0] as $rootCat) {
				// If we don't have permission to view this category then skip
				if(!CustomerGroupHasAccessToCategory($rootCat['categoryid'])) {
					continue;
				}

				$GLOBALS['SubCategoryList'] = $this->_getSubCategory($categories, $rootCat['categoryid']);
				$GLOBALS['LastChildClass']='';
				$GLOBALS['CategoryName'] = isc_html_escape($rootCat['catname']);
				$GLOBALS['CategoryLink'] = CatLink($rootCat['categoryid'], $rootCat['catname'], true);
				// @todo ul here is hacky but front end templates are limited, fix this when possible
				$output .= '<ul>' . $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SideCategoryList") . '</ul>';
			}

			return $output;
		}

		/**
		 * This method creates and returns front-end output for flyout-enabled category menus
		 *
		 * @return string
		 */
		protected function _generateFlyoutOutput ()
		{
			$categories = new ISC_SITEMAP_MODEL_CATEGORIES;
			$categories->setMaximumDepth((int)GetConfig('CategoryListDepth') - 1);
			$categories = $categories->getTree();

			$renderer = new Store_SiteMap_Renderer;
			return $renderer->setSiteMapTree($categories)
				->setRootClasses('sf-menu sf-vertical')
				->render();
		}
	}
