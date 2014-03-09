<?php

/**
* sitemap model for a vendor list
*/
class ISC_SITEMAP_MODEL_VENDORS extends ISC_SITEMAP_MODEL implements iISC_SITEMAP_MODEL {

	/**
	* Returns the heading to display for this sitemap model
	*
	* @return string
	*/
	public function getHeading()
	{
		return GetLang('SitemapHeadingVendors');
	}

	/**
	* Generates a sitemap tree for the vendors model based on the vendors table
	*
	* @param int $limit
	* @param int $offset
	* @return ISC_SITEMAP_NODE
	*/
	public function getTree($limit = null, $offset = null)
	{
		$root = new ISC_SITEMAP_ROOT();

		if (!gzte11(ISC_HUGEPRINT)) {
			return $root;
		}

		$sql = "SELECT vendorid, vendorname, vendorfriendlyname FROM `[|PREFIX|]vendors` ORDER BY vendorname ASC";

		if ($limit && $offset) {
			$sql .= " LIMIT " . $offset . "," . $limit;
		} else if ($limit) {
			$sql .= " LIMIT " . $limit;
		}

		$result = $GLOBALS['ISC_CLASS_DB']->Query($sql);

		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$label = $row['vendorname'];
			$url = VendorLink($row);

			$node = new ISC_SITEMAP_NODE($url, $label);
			$root->appendChild($node);
		}

		return $root;
	}

	/**
	* Returns a flat count of all vendors, used for paging purposes
	*
	* @return int
	*/
	public function countAll()
	{
		if (!gzte11(ISC_HUGEPRINT)) {
			return 0;
		}

		$sql = "SELECT COUNT(vendorid) as c FROM `[|PREFIX|]vendors`";
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
		$url = GetConfig('ShopPathNormal') . '/';

		if ($GLOBALS['EnableSEOUrls'] == 1) {
			$url .= 'sitemap/vendors/';
		} else {
			$url .= 'sitemap.php?view=vendors';
		}

		return $url;
	}
}
