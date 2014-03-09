<?php
/**
* Collection of functions that process and return formatted data from category related operations
*/
class ISC_ADMIN_EBAY_CATEGORIES {
	/**
	* Retrieves an array of category data for the specified category
	*
	* Elements use the category ID as their key. Each category has the following fields:
	* 	name 					=> The category name (string)
	* 	category_id				=> The ID of the category (int)
	* 	parent_id				=> The ID of the parent category (int)
	* 	is_leaf					=> Is this a leaf category? (bool)
	* 	lot_size_enabled		=> Are lot sizes allowed on listings in this category? (bool)
	* 	best_offer_enabled 		=> Are best offers allowed on listings? (bool)
	* 	reserve_price_allowed	=> Are reserve prices allowed on listings? (bool)
	* 	minimum_reserve_price 	=> The minimum reserve price for listings. (double)
	*
	* @param int $parentCategoryId The parent category to retrieve child categories from.
	* @param int $levelLimit The maximum depth of the heirarchy to retrieve.
	* @param int $siteId The eBay site to get categories for
	* @return array An array of category data in the format described.
	*/
	public static function getCategories($parentCategoryId, $levelLimit, $siteId)
	{
		ISC_ADMIN_EBAY_OPERATIONS::setSiteId($siteId);

		$currentCategoryVersion = 0;
		$cacheCategoryVersion = 0;
		$categories = array();

		$keystore = Interspire_KeyStore::instance();
		$versionKey = 'ebay:categories:version:site:' . $siteId;
		$updateKey = 'ebay:categories:last_update:site:' . $siteId;

		// get the cached version and last update time
		$cacheVersion = $keystore->get($versionKey);
		$cacheLastUpdate = $keystore->get($updateKey);

		if ($cacheVersion !== false && $cacheLastUpdate !== false) {
			$cacheCategoryVersion = $cacheVersion;

			// use the cached version as the current version if it was retrieved from ebay less than 10 minutes ago
			if ($cacheLastUpdate > (time() - 600)) {
				$currentCategoryVersion = $cacheCategoryVersion;
			}
		}

		if (empty($currentCategoryVersion)) {
			// retrieve the latest category version from ebay
			$currentCategoryVersion = ISC_ADMIN_EBAY_OPERATIONS::getCategoryVersion();

			// update our cached version
			$keystore->set($versionKey, $currentCategoryVersion);
			$keystore->set($updateKey, time());
		}

		// is our category heirarchy out of date?
		if ($currentCategoryVersion != $cacheCategoryVersion) {
			// lets nuke off our locally stored categories
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('ebay_categories', 'WHERE ebay_site_id = ' . $siteId);
		}

		// fetch categories from the database if they exist
		$query = '
			SELECT
				*
			FROM
				[|PREFIX|]ebay_categories
			WHERE
				parent_id = ' . $parentCategoryId . ' AND
				ebay_site_id = ' . $siteId . '
			ORDER BY
				name
		';
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);

		if ($GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			while ($categoryRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
				$categories[$categoryRow['category_id']] = $categoryRow;
			}

			return $categories;
		}

		// retrieve categories from the API
		$categoriesXML = ISC_ADMIN_EBAY_OPERATIONS::getCategories($levelLimit, $parentCategoryId);

		if ((int)$categoriesXML->CategoryCount == 0) {
			return false;
		}

		// are reserved prices allowed (site-wide level)
		$reservePriceAllowed = (bool)$categoriesXML->ReservePriceAllowed;

		$categories = array();
		foreach($categoriesXML->CategoryArray->Category as $categoryInfo) {
			// skip categories not on the level we've requested (ie. the parent category) and virtual categories as they don't allow listing
			if ((int)$categoryInfo->CategoryLevel != $levelLimit || (bool)$categoryInfo->Virtual) {
				continue;
			}

			$categoryReservedPriceAllowed = $reservePriceAllowed;

			// is this category overriding the reserve price allowed? toggle the site-side setting.
			if (isset($categoryInfo->ORPA)) {
				$categoryReservedPriceAllowed = !$categoryReservedPriceAllowed;
			}

			$newCategory = array(
				'name' 					=> (string)$categoryInfo->CategoryName,
				'category_id'			=> (int)$categoryInfo->CategoryID,
				'parent_id'				=> $parentCategoryId,
				'ebay_site_id'			=> $siteId,
				'is_leaf' 				=> isset($categoryInfo->LeafCategory),
				'lot_size_enabled'		=> !isset($categoryInfo->LSD),
				'best_offer_enabled'	=> isset($categoryInfo->BestOfferEnabled),
				'reserve_price_allowed'	=> $categoryReservedPriceAllowed,
				'minimum_reserve_price' => (double)$categoryInfo->MinimumReservePrice
			);

			$categories[(int)$categoryInfo->CategoryID] = $newCategory;

			// add the category to the database
			$GLOBALS['ISC_CLASS_DB']->InsertQuery('ebay_categories', $newCategory);
		}

		return $categories;
	}

	/**
	* Retrieves an array of store category data for the specified store category
	*
	* Elements use the category ID as their key. Each category has the following fields:
	* 	name 		=> The category name (string)
	* 	category_id	=> The ID of the category (int)
	* 	is_leaf		=> Is this a leaf category? (bool)
	*
	* @param int $parentCategoryId The parent category to retrieve child categories from.
	* @param int $levelLimit The maximum depth of the heirarchy to retrieve (max 3).
	* @param int $siteId The eBay site to get categories for
	* @return array An array of category data in the format described.
	*/
	public static function getStoreCategories($parentCategoryId, $levelLimit, $siteId)
	{
		ISC_ADMIN_EBAY_OPERATIONS::setSiteId($siteId);

		// max of limit 2 for store categories
		if ($levelLimit > 2) {
			$levelLimit = 2;
		}

		$categoriesXML = ISC_ADMIN_EBAY_OPERATIONS::getStoreCategories($levelLimit + 1, $parentCategoryId);

		$customCategories = $categoriesXML->Store->CustomCategories->CustomCategory;

		if ($levelLimit > 1) {
			$customCategories = $customCategories[0]->ChildCategory;
		}

		$categories = array();
		foreach ($customCategories as $categoryInfo) {
			$categoryId = (int)$categoryInfo->CategoryID;

			$isLeaf = !isset($categoryInfo->ChildCategory);

			$categories[$categoryId] = array(
				'name' 			=> (string)$categoryInfo->Name,
				'category_id'	=> $categoryId,
				'is_leaf'		=> $isLeaf
			);
		}

		return $categories;
	}

	/**
	* Retrieves details and item conditions (if applicable) for a specific category.
	*
	* Returned data is in the format:
	* 	name 						=> The category name (string)
	* 	category_id					=> The ID of the category (int)
	* 	parent_id					=> The ID of the parent category (int)
	* 	is_leaf						=> Is this a leaf category? (bool)
	* 	lot_size_enabled			=> Are lot sizes allowed on listings in this category? (bool)
	* 	best_offer_enabled 			=> Are best offers allowed on listings? (bool)
	* 	reserve_price_allowed		=> Are reserve prices allowed on listings? (bool)
	* 	minimum_reserve_price 		=> The minimum reserve price for listings. (double)
	* 	has_conditions				=> Does this category support conditions? (bool)
	*  	conditions_required			=> Does this category require the condition to be specified for an item? (bool)
	* 	conditions					=> The conditions support by the category. (array) Only included if has_conditions = true.
	* 									The key of each element is the condition ID (int).
	* 	payment_methods				=> The payment method codes this category supports (array).
	* 	paypal_required				=> Indicates if PayPal payment method is required for this category (bool)
	* 	return_policy_supported		=> Does the category support a return policy?
	* 	return_policy_required		=> Does the category required a return policy?
	* 	domestic_shipping_required	=> Is a domestic shipping service and its cost required for the category?
	* 	auction_durations			=> The set of duration Id's (eg. Days_3, Days_5 etc) that this category supports for Chinese auctions (array)
	* 	fixed_durations				=> The set of duration Id's (eg. Days_3, Days_5 etc) that this category supports for Fixed price item auctions (array)
	* 	item_specifics_supported	=> Does the category support custom item specifics (atributes)? (bool)
	* 	variations_supported		=> Does the category support multi-variation listings? (bool)
	* 	catalog_enabled 			=> Is the category catalog enabled? (bool)
	*
	* @param int $categoryId The ID of the category to retrieve data for.
	* @param int $siteId The eBay site to for the specified category
	* @return array An array of category data in the format described.
	*/
	public static function getCategoryFeatures($categoryId, $siteId)
	{
		ISC_ADMIN_EBAY_OPERATIONS::setSiteId($siteId);

		$query = 'SELECT * FROM [|PREFIX|]ebay_categories WHERE category_id = ' . $categoryId . ' AND ebay_site_id = ' . $siteId;
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!($categoryRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res))) {
			throw new Exception('Unable to retrieve category data from database.');
		}

		// specifically convert to boolean to avoid any javascript type casting issues
		$categoryRow['is_leaf'] = (bool)$categoryRow['is_leaf'];
		$categoryRow['lot_size_enabled'] = (bool)$categoryRow['lot_size_enabled'];
		$categoryRow['best_offer_enabled'] = (bool)$categoryRow['best_offer_enabled'];
		$categoryRow['reserve_price_allowed'] = (bool)$categoryRow['reserve_price_allowed'];

		$categoryXML = ISC_ADMIN_EBAY_OPERATIONS::getCategoryFeatures($categoryId);

		$category = $categoryXML->Category;
		$defaults = $categoryXML->SiteDefaults;
		$features = $categoryXML->FeatureDefinitions;

		$conditionEnabled = (string)$category->ConditionEnabled;
		$hasConditions = ($conditionEnabled == 'Enabled' || $conditionEnabled == 'Required');
		$categoryRow['has_conditions'] = $hasConditions;

		// is an item condition required for the category?
		$categoryRow['conditions_required'] = ($conditionEnabled == 'Required');

		if ($hasConditions) {
			$conditions = array();

			foreach ($category->ConditionValues->Condition as $condition) {
				$conditions[(string)$condition->ID] = (string)$condition->DisplayName;
			}

			$categoryRow['conditions'] = $conditions;
		}

		// get the minimum reserve price
		if (isset($category->MinimumReservePrice)) {
			$categoryRow['minimum_reserve_price'] = (double)$category->MinimumReservePrice;
		}
		else {
			$categoryRow['minimum_reserve_price'] = (double)$defaults->MinimumReservePrice;
		}

		// get the acceptable payment methods
		$paymentMethods = array();
		if (isset($category->PaymentMethod)) {
			$paymentMethodNode = $category->PaymentMethod;
		}
		else {
			$paymentMethodNode = $defaults->PaymentMethod;
		}
		foreach ($paymentMethodNode as $paymentMethod) {
			$paymentMethods[] = (string)$paymentMethod;
		}
		$categoryRow['payment_methods'] = $paymentMethods;

		// is paypal required ?
		$payPalRequired = false;
		if (isset($category->PayPalRequired)) {
			$payPalRequired = (bool)$category->PayPalRequired;
		}
		elseif (isset($defaults->PayPalRequired)) {
			$payPalRequired = (bool)$defaults->PayPalRequired;
		}
		$categoryRow['paypal_required'] = $payPalRequired;

		// return policy required?
		if (isset($category->ReturnPolicyEnabled)) {
			$policyNode = (bool)$category->ReturnPolicyEnabled;
		}
		else {
			$policyNode = (bool)$defaults->ReturnPolicyEnabled;
		}
		// For Australia or India, if this flag is true then a policy is supported but not required
		$ausSiteId = 15;
		$indiaSiteId = 203;
		if ($siteId == $ausSiteId || $siteId == $indiaSiteId) {
			$returnPolicyRequired = false;
			$returnPolicySupported = $policyNode;
		}
		else {
			$returnPolicyRequired = $policyNode;
			$returnPolicySupported = true;
		}

		$categoryRow['return_policy_required'] = $returnPolicyRequired;
		$categoryRow['return_policy_supported'] = $returnPolicySupported;

		// domestic shipping service required?
		if (isset($category->ShippingTermsRequired)) {
			$categoryRow['domestic_shipping_required'] = (bool)$category->ShippingTermsRequired;
		}
		else {
			$categoryRow['domestic_shipping_required'] = (bool)$defaults->ShippingTermsRequired;
		}

		// get the available listing durations
		if (isset($category->ListingDuration)) {
			$durationSetNode = $category->ListingDuration;
		}
		else {
			$durationSetNode = $defaults->ListingDuration;
		}

		$auctionDurationSetId = 0;
		$fixedDurationSetId = 0;

		foreach ($durationSetNode as $durationSetId) {
			$attr = $durationSetId->attributes();
			$auctionType = (string)$attr['type'];

			switch ($auctionType) {
				case 'Chinese':
					$auctionDurationSetId = (string)$durationSetId;
					break;
				case 'FixedPriceItem':
					$fixedDurationSetId = (string)$durationSetId;
					break;
			}
		}

		$auctionDurations = array();
		$fixedDurations = array();
		// iterate over each duration set and match them to the set Id's defined above
		foreach ($features->ListingDurations->ListingDuration as $durationSet) {
			$attr = $durationSet->attributes();
			$durationSetId = (string)$attr['durationSetID'];

			$durations = array();
			// get the durations from this set
			foreach ($durationSet->Duration as $duration) {
				$durations[] = (string)$duration;
			}

			if ($auctionDurationSetId == $durationSetId) {
				$auctionDurations = $durations;
			}

			if ($fixedDurationSetId == $durationSetId) {
				$fixedDurations = $durations;
			}
		}

		$categoryRow['auction_durations'] = $auctionDurations;
		$categoryRow['fixed_durations'] = $fixedDurations;

		$itemSpecificsEnabled = (string)$category->ItemSpecificsEnabled;
		$categoryRow['item_specifics_supported'] = ($itemSpecificsEnabled == 'Enabled');

		$variationsEnabled = false;
		if (isset($category->VariationsEnabled)) {
			$variationsEnabled = (bool)$category->VariationsEnabled;
		}
		$categoryRow['variations_supported'] = $variationsEnabled;

		$categoryRow['catalog_enabled'] = self::getCategoryIsCatalogEnabled($categoryId, $siteId);

		return $categoryRow;
	}

	/**
	* Checks if a category is catalog-enabled (ie. can prefill an item's information/specifics based off UPC etc)
	*
	* @param int $categoryId The ID of the category
	* @param int $siteId The eBay site to for the specified category
	* @return bool True if the category is catalog-enabled, false otherwise
	*/
	public static function getCategoryIsCatalogEnabled($categoryId, $siteId)
	{
		ISC_ADMIN_EBAY_OPERATIONS::setSiteId($siteId);

		$categoryXML = ISC_ADMIN_EBAY_OPERATIONS::getCategory2CS($categoryId);

		if (isset($categoryXML->MappedCategoryArray->Category->CatalogEnabled)) {
			return true;
		}

		return false;
	}

	/**
	* Retrieves a tree list of arrays of category data for the specified category
	*
	* @param int $categoryId The category to retrieve the parent categories from.
	* @param int $siteId The ebay site id where the category belong to.
	* @return array An array of category tree data in the format described.
	*/
	public static function getCategoryTree($categoryId, $siteId = 0)
	{
		$categoryTree = array();
		while ($categoryId >= 0) {
			$query = 'SELECT * FROM [|PREFIX|]ebay_categories WHERE category_id = ' . $categoryId . ' AND ebay_site_id = ' . $siteId;
			$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
			if (!($categoryRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res))) {
				throw new Exception('Unable to retrieve category data from database.');
			}
			$categoryTree[] = $categoryRow;
			$categoryId = $categoryRow['parent_id'];
		}
		return array_reverse($categoryTree);
	}

	/**
	* Return a formatted categories names from the category tree
	*
	* @param array $categoryTree The category tree that returned from ISC_ADMIN_EBAY_CATEGORIES::getCategoryTree.
	* @param string $separator The separator to separate the categories names.
	* @return string A formatted categories names e.g. "category 1 > category 2".
	*/
	public static function formatCategoryTree($categoryTree, $separator)
	{
		$categoryNames = array();
		foreach ($categoryTree as $category) {
			$categoryNames[] = $category['name'];
		}
		return implode($separator, $categoryNames);
	}

	/**
	* Gets a category path in the format "category 1 > category 2"
	*
	* @param int $categoryId The category Id to get the path for
	* @param int $siteId The eBay site Id that the category belongs to
	* @return string The formatted path
	*/
	public static function getFormattedCategoryPath($categoryId, $siteId = 0)
	{
		$tree = self::getCategoryTree($categoryId, $siteId);
		$path = self::formatCategoryTree($tree, ' > ');

		return $path;
	}

	/**
	* Gets basic category details
	*
	* @param int $categoryId The category Id to get details for
	* @param int $siteId The site for the category
	* @return array The category details
	*/
	public static function getCategoryOptionsFromId($categoryId, $siteId)
	{
		if (!$categoryId) {
			return array();
		}

		$categoryTree = self::getCategoryTree($categoryId, $siteId);
		$endCat = end($categoryTree);
		$categoryPath = self::formatCategoryTree($categoryTree, ' > ');
		$categoryOptions = array(
			'category_id' => $categoryId,
			'parent_id'	=> $endCat['parent_id'],
			'name'	=> $endCat['name'],
			'path'	=> $categoryPath
		);

		return $categoryOptions;
	}
}