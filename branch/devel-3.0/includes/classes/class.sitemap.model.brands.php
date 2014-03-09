<?php

/**
* Sitemap model for a category list
*/
class ISC_SITEMAP_MODEL_BRANDS extends ISC_SITEMAP_MODEL implements iISC_SITEMAP_MODEL {

	/**
	* Returns the heading to display for this sitemap model
	*
	* @return string
	*/
	public function getHeading()
	{
		return GetLang('SitemapHeadingBrands');
	}

	/**
	* Generates a sitemap tree for the brands model based on the brands table
	*
	* @param int $limit
	* @param int $offset
	* @return ISC_SITEMAP_NODE
	*/
	public function getTree($limit = null, $offset = null)
	{
		$root = new ISC_SITEMAP_ROOT();

		$sql = "SELECT brandname FROM `[|PREFIX|]brands` ORDER BY brandname ASC";

		if ($limit && $offset) {
			$sql .= " LIMIT " . $offset . "," . $limit;
		} else if ($limit) {
			$sql .= " LIMIT " . $limit;
		}

		$result = $GLOBALS['ISC_CLASS_DB']->Query($sql);

		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$label = $row['brandname'];
			$url = BrandLink($label);

			$node = new ISC_SITEMAP_NODE($url, $label);
			$root->appendChild($node);
		}

		return $root;
	}

	/**
	* Returns a flat count of all brands on the site, used for paging purposes.
	*
	* @return int
	*/
	public function countAll()
	{
		$sql = "SELECT COUNT(brandid) as c FROM `[|PREFIX|]brands`";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($sql);
		if (!$result) {
			return false;
		}
		return $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
	}

	/**
	* Returns a url pointing to the subsection view for this model
	*
	* @return string
	*/
	public function getSubsectionUrl()
	{
		return BrandLink();
	}
}
