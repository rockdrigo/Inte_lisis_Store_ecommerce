<?php

if (!defined('ISC_BASE_PATH')) {
	die();
}

/**
* This class contains functionality for tracking and calculating relationships built between products by similar custom viewing patterns.
*
*/
class ISC_PRODUCT_VIEWS {

	/** @var int this value controls the maximum lifetime (in seconds) of storage of session-view data in the product_views table before it is summarised into product_related_byviews and deleted */
	const VIEW_SESSION_LIFETIME = 86400;

	/** @var number on every product view there is a chance that old session data will be checked and summarised, this is one value that controls that probability, if PROBABILITY is 1 and DIVISOR is 100 that means there is a 1% chance that data will be checked and summarised */
	const VIEW_SESSION_END_PROBABILITY = 1;

	/** @var number on every product view there is a chance that old session data will be checked and summarised, this is one value that controls that probability, if PROBABILITY is 1 and DIVISOR is 100 that means there is a 1% chance that data will be checked and summarised */
	const VIEW_SESSION_END_DIVISOR = 100;

	/** @var int the maximum number of products a session can view before the client is considered to be a crawler - or just deemed as having irrelevant results - and it's views are discarded */
	const VIEW_CRAWLER_THRESHOLD = 50;

	/**
	* Logs a view against the current product with the current visitor credentials
	*
	* @param int $productId
	* @return void
	*/
	public static function logView($productId)
	{
		if (!self::isEnabled() || self::isCurrentSessionFlagged()) {
			// the setting is disabled or the current session has been flagged; this stops data tracking
			return;
		}

		if (lcg_value() <= self::VIEW_SESSION_END_PROBABILITY / self::VIEW_SESSION_END_DIVISOR) {
			// trigger a session cleanup based on the above chances
			self::summariseOldSessions();
		}

		$time = time();
		$sql = "INSERT INTO `[|PREFIX|]product_views` (product, `session`, lastview) VALUES (" . $productId . ", '" . $GLOBALS['ISC_CLASS_DB']->Quote(session_id()) . "', " . $time . ") ON DUPLICATE KEY UPDATE lastview = " . $time;
		$GLOBALS['ISC_CLASS_DB']->Query($sql);

		if (self::countCurrentSessionProductViews() > self::VIEW_CRAWLER_THRESHOLD) {
			self::flagCurrentSession();
			self::discardCurrentSession();
		}
	}

	public static function isCurrentSessionFlagged ()
	{
		return isset($_SESSION['disableProductViewTracking']) && (bool)$_SESSION['disableProductViewTracking'];
	}

	public static function flagCurrentSession ()
	{
		$_SESSION['disableProductViewTracking'] = true;
	}

	public static function unflagCurrentSession ()
	{
		$_SESSION['disableProductViewTracking'] = false;
	}

	public static function discardSession ($sessionId)
	{
		$sql = "DELETE FROM `[|PREFIX|]product_views` WHERE `session` = '" . $GLOBALS['ISC_CLASS_DB']->Quote($sessionId) . "'";
		return (bool)$GLOBALS['ISC_CLASS_DB']->Query($sql);
	}

	public static function discardCurrentSession ()
	{
		return self::discardSession(session_id());
	}

	/**
	 * @param string $sessionId
	 * @return int The number of products viewed in the specified session
	 */
	public static function countSessionProductViews ($sessionId)
	{
		$sql = "SELECT COUNT(*) FROM `[|PREFIX|]product_views` WHERE `session` = '" . $GLOBALS['ISC_CLASS_DB']->Quote($sessionId) . "'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($sql);
		$count = (int)$GLOBALS['ISC_CLASS_DB']->FetchOne($result);
		return $count;
	}

	/**
	 * @return int The number of products viewed in the current session
	 */
	public static function countCurrentSessionProductViews ()
	{
		return self::countSessionProductViews(session_id());
	}

	/**
	* To be called when a session ends
	*
	* @param string $sessionId
	* @return void
	*/
	public static function onSessionEnd($sessionHash)
	{
		self::summariseLogs($sessionHash);
	}

	/**
	* To be called when a product is deleted
	*
	* @param int $productId
	* @return void
	*/
	public static function onProductDelete($productId)
	{
		self::removeProductViews($productId);
	}

	/**
	* Determines if this feature is enabled
	*
	* @return bool True if enabled, otherwise false
	*/
	public static function isEnabled()
	{
		return !!GetConfig('EnableCustomersAlsoViewed');
	}

	/**
	* Returns the amount of products that should be shown, as per isc config data
	*
	* @return int
	*/
	public static function getNumberOfProductsToShow()
	{
		return GetConfig('CustomersAlsoViewedCount');
	}

	/**
	* Run through the view log and summarise up any sessions that haven't had product views for a set period of time
	*
	* @return void
	*/
	public static function summariseOldSessions()
	{
		if (!self::isEnabled()) {
			// the setting is disabled; this also stops data tracking
			return;
		}

		$db = $GLOBALS['ISC_CLASS_DB'];

		// fetch a list of view sessions from product_views where the session has not had any product views for at least VIEW_SESSION_LIFETIME seconds
		$sql = "SELECT `session` FROM `[|PREFIX|]product_views` GROUP BY `session` HAVING MAX(lastview) < " . (time() - self::VIEW_SESSION_LIFETIME);
		$result = $db->Query($sql);
		if (!$result) {
			return;
		}

		while ($row = $db->Fetch($result)) {
			self::summariseLogs($row['session']);
		}
	}

	/**
	* Summarise logs for a given session id, copying raw data from product_views into product_views_summary
	*
	* @param string $sessionHash
	* @return void
	*/
	public static function summariseLogs($sessionHash)
	{
		if (!self::isEnabled()) {
			// the setting is disabled; this also stops data tracking
			return;
		}

		// gather a dataset of products viewed in the given session and place it in a background task for summarising, remove it from product_views so it isn't re-processed
		$db = $GLOBALS['ISC_CLASS_DB'];

		$sql = "SELECT product FROM `[|PREFIX|]product_views` WHERE `session` = '" . $db->Quote($sessionHash) . "' ORDER BY product";
		$result = $db->Query($sql);
		$products = array();
		while ($row = $db->Fetch($result)) {
			$products[] = (int)$row['product'];
		}

		// trim the processed data
		self::discardSession($sessionHash);

		// only concerned with sessions that viewed more than 1 product
		if (count($products) > 1) {

			$job = array(
				'sessionId' => $sessionHash,
				'viewedProducts' => $products,
			);

			Interspire_TaskManager::createTask('productviews', 'Job_ProductViews_ProcessSession', $job);
		}
	}

	/**
	* Removes view and view-relation data for a given product id
	*
	* @param int $productId
	*/
	public static function removeProductViews($productId)
	{
		$productId = (int)$productId;
		$db = $GLOBALS['ISC_CLASS_DB'];

		$db->Query("DELETE FROM `[|PREFIX|]product_views` WHERE product = " . $productId);

		$db->Query("DELETE FROM `[|PREFIX|]product_related_byviews` WHERE prodida = " . $productId); // split over two queries for index optimization
		$db->Query("DELETE FROM `[|PREFIX|]product_related_byviews` WHERE prodidb = " . $productId);
	}

	/**
	* Returns a list of products, most popular first, related to the provided product id on the basis of similar viewing habits
	*
	* @param int $productId
	* @param int $limit Number of related products to return, or 0 to not limit
	* @param bool $returnResult If true, will return mysql result resource, otherwise returns array of ids with highest-cross-viewed product first
	* @return array
	*/
	public static function getRelatedProducts($productId, $limit = 0, $returnResult = false)
	{
		$db = $GLOBALS['ISC_CLASS_DB'];

		$sql = "
			SELECT p.*, FLOOR(prodratingtotal/prodnumratings) AS prodavgrating, pi.*, " . GetProdCustomerGroupPriceSQL() . "
			FROM `[|PREFIX|]products` p
			LEFT JOIN `[|PREFIX|]product_images` pi ON (p.productid=pi.imageprodid AND pi.imageisthumb=1)
			INNER JOIN `[|PREFIX|]product_related_byviews` prv ON prv.prodida = " . $productId . " AND prv.prodidb = p.productid
			WHERE p.prodvisible = 1
			" . GetProdCustomerGroupPermissionsSQL() . "
			ORDER BY prv.relevance DESC";

		if ($limit) {
			$sql .= " LIMIT " . $limit;
		}

		$result = $db->Query($sql);

		if ($returnResult) {
			// return result for an iterator or something to use
			return $result;
		}

		$products = array();
		if ($result) {
			while ($product = $db->Fetch($result)) {
				$products[] = $product;
			}
		}
		return $products;
	}
}
