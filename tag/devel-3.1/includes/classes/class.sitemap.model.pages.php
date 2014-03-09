<?php

/**
* sitemap model for a web page list
*/
class ISC_SITEMAP_MODEL_PAGES extends ISC_SITEMAP_MODEL implements iISC_SITEMAP_MODEL {

	/**
	* Returns the heading to display for this sitemap model
	*
	* @return string
	*/
	public function getHeading()
	{
		return GetLang('SitemapHeadingWebPages');
	}

	/**
	* Generates a sitemap tree for the pages model based on the pages nested set
	*
	* @param int $limit
	* @param int $offset
	* @return ISC_SITEMAP_NODE
	*/
	public function getTree($limit = null, $offset = null)
	{
		$root = new ISC_SITEMAP_ROOT();

		$home = new ISC_SITEMAP_NODE(GetConfig('ShopPathNormal') . '/', GetLang('Home'));
		$root->appendChild($home);

		$set = new ISC_NESTEDSET_PAGES();

		$sql = $set->generateGetTreeSql(array('pageid', 'pagetitle', 'pagetype', 'pagelink'), ISC_NESTEDSET_START_ROOT, $this->getMaximumDepth(), $limit, $offset, true, $this->_getRestrictions());
		$result = $GLOBALS['ISC_CLASS_DB']->Query($sql);

		$previousDepth = -1;

		$node = $root;
		while ($page = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$depth = (int)$page['pagedepth'];

			if ($depth > $previousDepth) {
				$parent = $node;
			} else if ($depth < $previousDepth) {
				for ($depthCounter = $previousDepth - $depth; $depthCounter > 0; $depthCounter--) {
					$parent = $parent->getParent();
				}
			}

			$title = $page['pagetitle'];

			switch ($page['pagetype']) {
				case '1':
					$url = $page['pagelink'];
					break;

				default:
					$url = PageLink((int)$page['pageid'], $title);
					break;
			}

			$node = new ISC_SITEMAP_NODE($url, $title);

			$parent->appendChild($node);

			$previousDepth = $depth;
		}


		return $root;
	}

	/**
	* Returns a flat count of all pages, used for paging purposes. Must use the same visibility rules as the getTree method.
	*
	* @return int
	*/
	public function countAll()
	{
		// isc page management does not flag all child nodes as not-visible when you untick a parent page, so we can't simply count(*) to get a count of all visible pages
		// use the nested set query to do a select, but discard the results and then use a FOUND_ROWS call afterwards to get the true count of all visible pages

		$set = new ISC_NESTEDSET_PAGES();

		$sql = $set->generateGetTreeSql(array('pageid'), ISC_NESTEDSET_START_ROOT, $this->getMaximumDepth(), null, null, true, $this->_getRestrictions());

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
			$url .= 'sitemap/pages/';
		} else {
			$url .= 'sitemap.php?view=pages';
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
			'MIN(`parent`.`pagestatus`) = 1',
			'MAX(`parent`.`pagevendorid`) = 0',
		);

		$customer = GetClass('ISC_CUSTOMER');
		$loggedIn = !!$customer->GetCustomerId();

		if (!$loggedIn) {
			// customer is not logged in, filter out pages that require customer logins
			$restrictions[] = 'MAX(`parent`.`pagecustomersonly`) = 0';
		}

		return $restrictions;
	}
}
