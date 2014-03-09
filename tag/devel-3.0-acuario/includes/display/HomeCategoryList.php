<?php

	CLASS ISC_HomeCategoryList_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			$output = $this->_generateCategoryList();

			if (!$output) {
				$this->DontDisplay = true;
				return;
			}

			$GLOBALS['SNIPPETS']['HomeCategoryList'] = $output;
		}

		/**
		 * This method creates and returns front-end output for original, non-flyout-enabled category menus
		 *
		 * @return string
		 */
		protected function _generateCategoryList ()
		{
			$output = "";
			$result = $GLOBALS['ISC_CLASS_DB']->Query('SELECT * FROM [|PREFIX|]categories WHERE catparentid = "0" AND catvisible = "1" ORDER BY catsort, catname asc');
			$categories = array();
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)){
				$categories[] = $row;
			}

			if (empty($categories)) {
				return $output;
			}

			foreach($categories as $rootCat) {
				// If we don't have permission to view this category then skip
				if(!CustomerGroupHasAccessToCategory($rootCat['categoryid'])) {
					continue;
				}

				$GLOBALS['LastChildClass']='';
				$GLOBALS['CategoryName'] = isc_html_escape($rootCat['catname']);
				$GLOBALS['CategoryLink'] = CatLink($rootCat['categoryid'], $rootCat['catname']);
				$GLOBALS['CategoryImage'] = GetConfig('ShopPath') . '/' . GetConfig('ImageDirectory') . '/' . $rootCat['catimagefile'];
				$GLOBALS['CategoryDescription'] = isc_substr($rootCat['catdesc'], 0, 50);
				if(isc_strlen($rootCat['catdesc']) > 47)  $rootCat['catdesc'] .= '...';
				// @todo ul here is hacky but front end templates are limited, fix this when possible
				$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("HomeCategoryListItem");
			}

			return $output;
		}
	}
