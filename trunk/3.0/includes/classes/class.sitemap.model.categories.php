<?php

/**
* sitemap model for a category list
*/
class ISC_SITEMAP_MODEL_CATEGORIES extends ISC_SITEMAP_MODEL implements iISC_SITEMAP_MODEL {

	/**
	* Returns the heading to display for this sitemap model
	*
	* @return string
	*/
	public function getHeading()
	{
		return GetLang('Categories');
	}

	/**
	* Generates a sitemap tree for the categories model based on the categories nested set
	*
	* @param int $limit
	* @param int $offset
	* @return ISC_SITEMAP_NODE
	*/
	public function getTree($limit = null, $offset = null)
	{
		$root = new ISC_SITEMAP_ROOT();

		$set = new ISC_NESTEDSET_CATEGORIES();

		$sql = $set->generateGetTreeSql(array('categoryid', 'catname'), ISC_NESTEDSET_START_ROOT, $this->getMaximumDepth(), $limit, $offset, true, $this->_getRestrictions());
		$result = $GLOBALS['ISC_CLASS_DB']->Query($sql);

		$previousDepth = -1;

		$node = $root;
		while ($category = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$depth = (int)$category['catdepth'];

			if ($depth > $previousDepth) {
				$parent = $node;
			} else if ($depth < $previousDepth) {
				for ($depthCounter = $previousDepth - $depth; $depthCounter > 0; $depthCounter--) {
					$parent = $parent->getParent();
				}
			}

			$label = $category['catname'];
			$url = CatLink($category['categoryid'], $label);

			$node = new ISC_SITEMAP_NODE($url, $label);
			$parent->appendChild($node);

			$previousDepth = $depth;
		}


		return $root;
	}

	/**
	* Returns a flat count of all categories, used for paging purposes. Must use the same visibility rules as the getTree method.
	*
	* @return int
	*/
	public function countAll()
	{
		// isc customer group management does not flag all child categories as not-accessible when you untick a parent category from a customer group access list, so we can't simply count(*) to get a count of all truly visible categories
		// use the nested set query to do a select, but discard the results and then use a FOUND_ROWS call afterwards to get the true count of all visible categories

		$set = new ISC_NESTEDSET_CATEGORIES();

		$sql = $set->generateGetTreeSql(array('categoryid'), ISC_NESTEDSET_START_ROOT, $this->getMaximumDepth(), null, null, true, $this->_getRestrictions());

		$result = $GLOBALS['ISC_CLASS_DB']->Query($sql);
		if (!$result) {
			return false;
		}

		$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT FOUND_ROWS()");
		return $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
	}

	/**
	* Returns a url pointing to the subsection view for this model
	*
	* @return string
	*/
	public function getSubsectionUrl()
	{
		$url = GetConfig('ShopPathNormal') . '/';

		if ($GLOBALS['EnableSEOUrls'] == 1) {
			$url .= 'sitemap/categories/';
		} else {
			$url .= 'sitemap.php?view=categories';
		}

		return $url;
	}

	/**
	* Returns the restrictions array commonly used by front end page display
	*
	* @return array
	*/
	protected function _getRestrictions()
	{
		$restrictions = array(
			'MIN(`parent`.`catvisible`) = 1',
		);

		$customer = GetClass('ISC_CUSTOMER');
		$group = $customer->GetCustomerGroup();
		if (is_array($group) && $group['categoryaccesstype'] != 'all') {
			// the current customer is in a customer group, so they may not have access to all categories
			// add a restriction to the tree that all parent records must exist in the category access list (use catdepth + 1 because catdepth is set as parent count - 1 in the nested set api)
			$restrictions[] = "SUM(`parent`.`categoryid` IN (" . implode(',', $group['accesscategories']) . ")) = catdepth + 1";
		}

		return $restrictions;
	}
}
