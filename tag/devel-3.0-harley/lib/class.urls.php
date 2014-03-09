<?php
/**
 * URL Library Class
 *
 * This class holds a number of functions that generate and deconstruct URLs used by Shopping Cart
 */
class ISC_URLS
{
	/**
	 * Returns the URL for a product based on it's productid
	 *
	 * @param integer $productId The ID number of the product to generate the URL for
	 */
	public static function getProductUrl($productId, $returnTitle=false)
	{
		$productId = (int)$productId;
		if($productId < 1) {
			return false;
		}

		$productName = $GLOBALS['ISC_CLASS_DB']->FetchOne('select prodname from `[|PREFIX|]products` where productid=' . $productId, 'prodname');

		if(empty($productName)) {
			return false;
		}

		if($returnTitle) {
			return array('title'=>$productName, 'url'=> ProdLink($productName));
		}

		return ProdLink($productName);
	}

	/**
	 * Returns the URL for a category based on it's category id
	 *
	 * @param integer $categoryId The ID number of the category to generate the URL for
	 */
	public static function getCategoryUrl($categoryId, $returnTitle=false)
	{
		$categoryId = (int)$categoryId;
		if($categoryId < 1) {
			return false;
		}

		$categoryQuery = $GLOBALS['ISC_CLASS_DB']->Query('select * from `[|PREFIX|]categories` where categoryid=' . $categoryId);
		$categoryInfo = $GLOBALS['ISC_CLASS_DB']->Fetch($categoryQuery);

		if(empty($categoryInfo)) {
			return false;
		}

		$isParent = false;

		if($categoryInfo['catparentid'] == 0) {
			$isParent = true;
		}

		if($returnTitle) {
			return array('title'=>$categoryInfo['catname'], 'url'=> CatLink($categoryInfo['categoryid'], $categoryInfo['catname'], $isParent));
		}

		return CatLink($categoryInfo['categoryid'], $categoryInfo['catname'], $isParent);
	}

	/**
	 * Returns the URL for a brand based on it's id
	 *
	 * @param integer $brandId The ID number of the brand to generate the URL for
	 */
	public static function getBrandUrl($brandId, $returnTitle=false)
	{
		$brandId = (int)$brandId;
		if($brandId < 1) {
			return false;
		}

		$brandName = $GLOBALS['ISC_CLASS_DB']->FetchOne('select brandname from `[|PREFIX|]brands` where brandid=' . $brandId, 'brandname');

		if(empty($brandName)) {
			return false;
		}

		if($returnTitle) {
			return array('title'=>$brandName, 'url'=> BrandLink($brandName));
		}

		return BrandLink($brandName);
	}

	/**
	 * Returns the URL for a page based on it's id
	 *
	 * @param integer $pageId The ID number of the page to generate the URL for
	 */
	public static function getPageUrl($pageId, $returnTitle=false)
	{
		$pageId = (int)$pageId;
		if($pageId < 1) {
			return false;
		}


		$pageQuery = $GLOBALS['ISC_CLASS_DB']->Query('select * from `[|PREFIX|]pages` where pageid=' . $pageId);
		$pageInfo = $GLOBALS['ISC_CLASS_DB']->Fetch($pageQuery);

		if(empty($pageInfo)) {
			return false;
		}

		if($returnTitle) {
			return array('title'=>$pageInfo['pagetitle'], 'url'=> PageLink($pageId, $pageInfo['pagetitle']));
		}

		return PageLink($pageId, $pageInfo['pagetitle']);
	}

	/**
	* Given the result of a parse_url, returns a string url
	*
	* @param array $parsed
	* @return string
	*/
	public static function unparseUrl($parsed)
	{
		$url = '';

		if (isset($parsed['scheme'])) {
			$url .= $parsed['scheme'] . '://';
		}

		if (isset($parsed['user'])) {
			$url .= $parsed['user'];
			if (isset($parsed['pass'])) {
				$url .= ':' . $parsed['pass'];
			}
			$url .= '@';
		}

		if (isset($parsed['host'])) {
			$url .= $parsed['host'];
		}

		if (isset($parsed['port'])) {
			$url .= ':' . $parsed['port'];
		}

		if (isset($parsed['path'])) {
			$url .= $parsed['path'];
		}

		if (isset($parsed['query'])) {
			$url .= '?' . $parsed['query'];
		}

		if (isset($parsed['fragment'])) {
			$url .= '#' . $parsed['fragment'];
		}

		return $url;
	}
}
