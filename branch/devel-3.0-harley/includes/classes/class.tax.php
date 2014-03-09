<?php
class ISC_TAX
{
	/**
	 * @var object Instance of the database class.
	 */
	protected $db;

	/**
	 * Constructor - set up common items accessed.
	 */
	public function __construct()
	{
		$this->db = $GLOBALS['ISC_CLASS_DB'];
	}

	/**
	 */
	public function updateProductTaxPricing($price, $taxClassId, $taxZoneId = null)
	{
		if($taxZoneId === null) {
			$taxZones = $this->getTaxZoneIds();

			// Tax zone 0 is used for prices excluding tax
			$taxZones[] = 0;
		}
		else {
			$taxZones = array($taxZoneId);
		}

		$updatedRows = 0;
		foreach($taxZones as $taxZoneId) {
			$includingTax = true;
			if($taxZoneId == 0) {
				$includingTax = false;
			}
			$calculatedPrice = $this->getPrice(
				$price,
				$taxClassId,
				$includingTax,
				$taxZoneId
			);
			$query = "
				INSERT INTO [|PREFIX|]product_tax_pricing
					(`price_reference`, `calculated_price`, `tax_zone_id`, `tax_class_id`)
				VALUES (
					'".$this->db->quote((double)$price)."',
					'".$this->db->quote((double)$calculatedPrice)."',
					".(int)$taxZoneId.",
					".(int)$taxClassId."
				)
				ON DUPLICATE KEY UPDATE
					calculated_price=VALUES(`calculated_price`)
			";
			if(!$this->db->query($query)) {
				return false;
			}
			$updatedRows += $this->db->numAffected();
		}

		return $updatedRows;
	}

	/**
	 * Given a prioritized array of tax rates, calculate the effective tax
	 * rate as a percentage when they're all added/compounded together.
	 *
	 * @see $this->getPrioritizedRatesForClass
	 * @param array $rates Prioritized array of tax rates.
	 * @return float Tax rate.
	 */
	public function getEffectiveTaxRate(array $rates)
	{
		$effectiveRate = 0;
		ksort($rates, SORT_NUMERIC);
		foreach($rates as $priority => $priorityRates) {
			$priorityTotal = 0;
			foreach($priorityRates as $rate) {
				$priorityTotal += $rate['rate'];
			}
			$effectiveRate += (100 + $effectiveRate) * ($priorityTotal / 100);
		}

		return $effectiveRate;
	}

	/**
	* Clears the internal tax rate cache. Should only be necessary to receive updated rates during a single PHP script execution (ie. unit testing)
	*
	* @return void
	*/
	public static function flushStaticCache()
	{
		self::$_classRates = array();
		self::$_cachedRates = array();
		self::$_zoneCache = array();
		self::$_taxZoneCache = array();
	}

	/**
	* This was inside getEffectiveClassRate as a `static $classRates` definition. Moving it here so it can at least be flushed for testing reasons.
	*
	* @var array
	*/
	protected static $_classRates = array();

	/**
	 * Given a tax zone and a tax class, calculate the effective tax rate
	 * that should be applied to products, etc.
	 *
	 * @see $this->getEffectiveTaxRate
	 * @param int $taxZoneId Tax zone the rates should belong to.
	 * @param int $taxClassId Calculate tax rate for this tax class.
	 * @return float Tax rate.
	 */
	public function getEffectiveClassRate($taxZoneId, $taxClassId)
	{
		if(!isset(self::$_classRates[$taxZoneId][$taxClassId])) {
			$rates = $this->getPrioritizedRatesForClass($taxZoneId, $taxClassId);
			self::$_classRates[$taxZoneId][$taxClassId] = $this->getEffectiveTaxRate($rates);
		}

		return self::$_classRates[$taxZoneId][$taxClassId];
	}

	/**
	* This was inside getPrioritizedRatesForClass as a `static $cachedRates` definition. Moving it here so it can at least be flushed for testing reasons.
	*
	* @var mixed
	*/
	protected static $_cachedRates = array();

	/**
	 * Get a list of tax rates that apply to a specified tax zone and tax
	 * class, and return them in an array structured by the priority that
	 * they should be applied.
	 *
	 * The returned multi-dimensional array is structured so that each
	 * tax zone with the same priority is grouped together, like so:
	 *
	 * $rates => array(
	 * 	1 (priority) => array(
	 * 		1 (ID) => array(
	 * 			'name' => ....
	 * 		),
	 * 		2 (ID) => array(
	 * 			'name' => ....
	 * 		),
	 * 	),
	 * 	2 (priority) => array(
	 * 		3 (ID) => array(
	 * 			'name' => ....
	 * 		),
	 * 	)
	 * );
	 *
	 * @param int $taxZoneId Tax zone the rates should belong to.
	 * @param int $taxClassId Fetch tax rates for this tax class.
	 * @return array Multi-dimensional array of tax rates by priority.
	 */
	public function getPrioritizedRatesForClass($taxZoneId, $taxClassId)
	{
		$cacheKey = $taxZoneId.'.'.$taxClassId;

		if(isset(self::$_cachedRates[$cacheKey])) {
			self::$_cachedRates[$cacheKey];
		}

		$rates = array();
		if($taxClassId == 0) {
			$query = "
				SELECT priority, id AS tax_rate_id, default_rate AS rate, '' AS name, 0 AS id, name AS tax_rate_name
				FROM [|PREFIX|]tax_rates
				WHERE tax_zone_id='".(int)$taxZoneId."' AND enabled=1
				ORDER BY priority
			";
		}
		else {
			$query = "
				SELECT r.priority, c.id, tc.rate, c.name, tc.tax_rate_id, r.name AS tax_rate_name
				FROM [|PREFIX|]tax_rates r
				JOIN [|PREFIX|]tax_rate_class_rates tc ON (tc.tax_rate_id = r.id)
				JOIN [|PREFIX|]tax_classes c ON (c.id = tc.tax_class_id)
				WHERE r.tax_zone_id='".(int)$taxZoneId."' AND tc.tax_class_id='".(int)$taxClassId."' AND r.enabled=1
				ORDER BY priority
			";
		}

		$result = $this->db->query($query);
		while($rate = $this->db->fetch($result)) {
			if(!$rate['name']) {
				$rate['name'] = getLang('DefaultTaxClass');
			}
			$rate['rate'] /= 1;
			$rates[$rate['priority']][$rate['tax_rate_id']] = $rate;
		}
		self::$_cachedRates[$cacheKey] = $rates;
		return $rates;
	}

	/**
	 * Determine a tax zone to use for tax calculations, with an optionally
	 * supplied billing or shipping address and customer group. The resulting
	 * tax zone will be matched based on the store configuration for which
	 * address tax calculation should be performed on (billing, shipping or
	 * store)
	 *
	 * Address fields can include the following keys:
	 * - shipcountryid
	 * - shipstateid
	 * - shipzip
	 *
	 * @param array $billingAddress Array containing billing address if known.
	 * @param array $shippingAddress Array containing shipping address if known.
	 * @param int|null Customer group. If not supplied, group of current customer
	 * is used.
	 * @return int ID of the best matching tax zone for the address.
	 */
	public function determineTaxZone($billingAddress = null, $shippingAddress = null, $customerGroup = null)
	{
		// If all supplied billing address details are empty, assume default tax zone
		if (empty($billingAddress['shipcountryid']) && empty($billingAddress['shipstateid']) && empty($billingAddress['shipzip'])) {
			$billingAddress = null;
		}

		// If all supplied shipping address details are empty, assume default tax zone
		if (empty($shippingAddress['shipcountryid']) && empty($shippingAddress['shipstateid']) && empty($shippingAddress['shipzip'])) {
			$shippingAddress = null;
		}

		// Logged in as a customer? If so, grab the customer group ID
		if($customerGroup === null && !defined('ISC_ADMIN_CP')) {
			$customer = getClass('ISC_CUSTOMER')->getCustomerDataByToken();
			if(!empty($customer['custgroupid'])) {
				$customerGroup = $customer['custgroupid'];
			}
		}

		// Tax based on store's address
		if(getConfig('taxCalculationBasedOn') == TAX_BASED_ON_STORE_ADDRESS) {
			return $this->determineStoreDefaultTaxZone();
		}

		// If no address was supplied, then just return the default for the store
		if(empty($billingAddress) && empty($shippingAddress)) {
			return $this->getDefaultTaxZoneForGroup($customerGroup);
		}

		// Tax calculations are based on billing address
		if(!empty($billingAddress) && getConfig('taxCalculationBasedOn') == TAX_BASED_ON_BILLING_ADDRESS) {
			$address = $billingAddress;
		}
		// Based on shipping address
		else if(!empty($shippingAddress) && getConfig('taxCalculationBasedOn') == TAX_BASED_ON_SHIPPING_ADDRESS) {
			$address = $shippingAddress;
		}
		// Fall back to default
		else {
			return $this->getDefaultTaxZoneForGroup($customerGroup);
		}

		// Grab the tax zone for the supplied $address
		return $this->determineTaxZoneForAddress(
			$address['shipcountryid'],
			$address['shipstateid'],
			$address['shipzip'],
			$customerGroup
		);
	}

	public function determineStoreDefaultTaxZone()
	{
		return $this->getDefaultTaxZoneForGroup(0);
	}

	/**
	* This was inside getDefaultTaxZoneForGroup as a `static $taxZoneCache` definition. Moving it here so it can at least be flushed for testing reasons.
	*
	* @var array
	*/
	protected static $_taxZoneCache = array();

	/**
	 * Given a customer group, return the default tax zone that the customer
	 * group falls under.
	 *
	 * @param int $customerGroup The ID of the customer group.
	 * @return int The tax zone that the customer group falls under.
	 */
	public function getDefaultTaxZoneForGroup($customerGroup)
	{
		self::$_taxZoneCache = $GLOBALS['ISC_CLASS_DATA_STORE']->read('DefaultTaxZones');

		// Double check that the cache is not empty.
		if(empty(self::$_taxZoneCache)) {
			$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateDefaultTaxZones();
			self::$_taxZoneCache = $GLOBALS['ISC_CLASS_DATA_STORE']->read('DefaultTaxZones');
		}

		if(isset(self::$_taxZoneCache[$customerGroup])) {
			return self::$_taxZoneCache[$customerGroup];
		}
		else {
			return self::$_taxZoneCache[0];
		}
	}

	/**
	* This was inside determineTaxZoneForAddress as a `static $zoneCache` definition. Moving it here so it can at least be flushed for testing reasons.
	*
	* @var array
	*/
	protected static $_zoneCache = array();

	/**
	 * Given one or more details about an address (country ID, state ID, or
	 * zip code) as well as optionally a customer group, find the tax zone
	 * that the address best falls under and return its ID.
	 *
	 * Tax zones are searched in the following order:
	 * - Does a tax zone for the supplied zip code exist? If so, return it.
	 * - Does a tax zone for the supplied state exist? If so, return it.
	 * - Does a tax zone for the supplied country exist? If so, return it.
	 * - Otherwise, return the default tax zone.
	 *
	 * @param int $countryID Country ID.
	 * @param int $stateId State ID if known.
	 * @param int $zip Zip code if known.
	 * @param int $customerGroup Match a tax zone that is associated with this
	 * customer group.
	 * @return int ID of the best matching tax zone for the address.
	 */
	public function determineTaxZoneForAddress($countryId, $stateId, $zip = null, $customerGroup = null)
	{
		$cacheId = md5(serialize(func_get_args()));
		if(isset(self::$_zoneCache[$cacheId])) {
			return self::$_zoneCache[$cacheId];
		}

		$zoneId = 0;

		if($zip) {
			$zoneId = $this->getTaxZoneIdForZipCode($zip, $countryId, $customerGroup);
			if($zoneId) {
				self::$_zoneCache[$cacheId] = $zoneId;
				return $zoneId;
			}
		}

		// Try based on state
		$zoneId = $this->getTaxZoneIdForState($stateId, $countryId, $customerGroup);
		if($zoneId) {
			self::$_zoneCache[$cacheId] = $zoneId;
			return $zoneId;
		}

		// Or fall back to a country
		$zoneId = $this->getTaxZoneIdForCountry($countryId, $customerGroup);
		if($zoneId) {
			self::$_zoneCache[$cacheId] = $zoneId;
			return $zoneId;
		}

		// Still no tax zone then return the default
		$defaultZone = $this->getDefaultTaxZone();
		self::$_zoneCache[$cacheId] = $defaultZone['id'];
		return $defaultZone['id'];
	}

	/**
	 * Given a zip code, country ID and optionally a customer group, return the
	 * ID of the first matching zip code based tax zone.
	 *
	 * @param int $zip Zip code.
	 * @param int $countryId Country ID.
	 * @param int $customerGroup Customer group the zone should be associated
	 * with.
	 * @return int|false ID of the tax zone when found, false if not.
	 */
	public function getTaxZoneIdForZipCode($zip, $countryId, $customerGroup = null)
	{
		$zoneId = false;
		$query = "
			SELECT
				z.id, l.value_id, l.value
			FROM [|PREFIX|]tax_zone_locations l
			JOIN [|PREFIX|]tax_zones z ON (z.id=l.tax_zone_id)
			JOIN [|PREFIX|]tax_zone_customer_groups g
				ON (g.tax_zone_id=z.id AND (
					g.customer_group_id=".(int)$customerGroup." OR g.customer_group_id = 0)
				)
			WHERE
				z.enabled=1 AND l.type='zip' AND l.country_id='".(int)$countryId."' AND
				'".$this->db->quote($zip)."' REGEXP REPLACE(REPLACE(
					CONCAT('^', l.value, '$'
				), '*', '.{1,}'), '?',  '.')
			ORDER BY g.customer_group_id DESC
		";
		$result = $this->db->query($query);
		while($zone = $this->db->fetch($result)) {

			// If there was an exact match, use it and just return now
			if($zone['value'] == $zip) {
				return $zone['id'];
			}

			// Score the characters in the string, so we can determine the best match.
			$score = (substr_count($zone['value'], '*') * 10)
				+ substr_count($zone['value'], '?');

			// Lower scores mean a stronger match, so use that zone ID
			if(!isset($lastScore) || $score < $lastScore) {
				$zoneId = $zone['id'];
				$lastScore = $score;
			}
		}

		return $zoneId;
	}

	/**
	 * Given a state ID, country ID and optionally a customer group, return the
	 * ID of the first matching state based tax zone.
	 *
	 * @param int $stateId State ID.
	 * @param int $countryId Country ID.
	 * @param int $customerGroup Customer group the zone should be associated
	 * with.
	 * @return int|false ID of the tax zone when found, false if not.
	 */
	public function getTaxZoneIdForState($stateId, $countryId, $customerGroup = null)
	{
		$query = "
			SELECT z.id
			FROM [|PREFIX|]tax_zone_locations l
			JOIN [|PREFIX|]tax_zones z ON (z.id=l.tax_zone_id)
			JOIN [|PREFIX|]tax_zone_customer_groups g
				ON (g.tax_zone_id=z.id AND (
					g.customer_group_id=".(int)$customerGroup." OR g.customer_group_id = 0)
				)
			WHERE z.enabled=1 AND l.type='state' AND l.country_id='".(int)$countryId."' AND
				(l.value_id='".(int)$stateId."' OR l.value_id=0)
			ORDER BY value_id DESC, g.customer_group_id DESC
			LIMIT 1
		";
		$zoneId = $this->db->fetchOne($query);
		if($zoneId) {
			return $zoneId;
		}

		return false;
	}

	/**
	 * Given a country ID (and optionally a customer group), return the ID of
	 * the first matching country based tax zone.
	 *
	 * @param int $countryId Country ID.
	 * @param int $customerGroup Customer group the zone should be associated
	 * with.
	 * @return int|false ID of the tax zone when found, false if not.
	 */
	public function getTaxZoneIdForCountry($countryId, $customerGroup = null)
	{
		// Try based on a country level instead
		$query = "
			SELECT z.id
			FROM [|PREFIX|]tax_zone_locations l
			JOIN [|PREFIX|]tax_zones z ON (z.id=l.tax_zone_id)
			JOIN [|PREFIX|]tax_zone_customer_groups g
				ON (g.tax_zone_id=z.id AND (
					g.customer_group_id=".(int)$customerGroup." OR g.customer_group_id = 0)
				)
			WHERE z.enabled=1 AND l.type='country' AND l.value_id='".(int)$countryId."'
			ORDER BY g.customer_group_id DESC
			LIMIT 1
		";
		$zoneId = $this->db->fetchOne($query);
		if($zoneId) {
			return $zoneId;
		}

		return false;
	}

	/**
	 * Get the default tax zone.
	 *
	 * @return array Array containing database record from tax_zones for the
	 * default zone.
	 */
	public function getDefaultTaxZone()
	{
		$query = "
			SELECT *
			FROM [|PREFIX|]tax_zones
			WHERE `default`=1
		";
		$result = $this->db->query($query);
		return $this->db->fetch($result);
	}

	/**
	 * Return an array containing all of the IDs for configured tax zones.
	 *
	 * @return array Array containing IDs for all tax zones.
	 */
	public function getTaxZoneIds()
	{
		$ids = array();
		$query = "
			SELECT id
			FROM [|PREFIX|]tax_zones
		";
		$result = $this->db->query($query);
		while($taxZone = $this->db->fetch($result)) {
			$ids[] = $taxZone['id'];
		}

		return $ids;
	}

	/**
	 * Given a price and a tax rate, calculate the tax component of the price.
	 *
	 * @param float $price Price to calculate tax component of.
	 * @param float $taxRate Tax rate applied to the price.
	 * @param boolean $inclusive Set to true if the price already includes the
	 * tax component. False if it does not.
	 * @return float Tax component applied to $price.
	 */
	public function calculateTax($price, $taxRate, $inclusive = false)
	{
		if($inclusive) {
			$tax = ($price / (100 + $taxRate)) * $taxRate;
		}
		else {
			$tax = $price * ($taxRate / 100);
		}

		return $tax;
	}

	/**
	 * Given a tax zone and array of prices grouped by tax class, generate a
	 * comprehensive breakdown of all applied tax rates and all tax classes.
	 *
	 * Returned data structure:
	 * 	$taxSummary = array(
	 * 		1 (tax rate ID) => array(
	 * 			'name' => ...,
	 * 			'amount' => ... tax amount,
	 * 			'classes' => array(
	 * 				1 (tax class ID) => array(
	 * 					'name' => ....,
	 * 					'rate' => ... tax rate,
	 * 					'amount' => ... tax amount
	 * 				)
	 * 			)
	 * 		)
	 * 	);
	 *
	 * @param int $taxZoneId Tax zone to use to determine tax rates.
	 * @param int $taxClassPrices Multi dimensional array containing tax
	 * class IDs and for each class, an array of prices that apply to it.
	 * @return array Tax summary as described in doc-block.
	 */
	public function getTaxSummaryForPrice($price, $taxRates)
	{
		$compoundTotal = $price;
		$taxClass = array();
		ksort($taxRates, SORT_NUMERIC);
		foreach($taxRates as $priority => $prioritizedRates) {
			$priorityRate = 0;
			$priorityName = array();
			$taxClassRates = array();
			foreach($prioritizedRates as $taxRateId => $taxClassRate) {
				if(empty($taxClass)) {
					$taxClassId = $taxClassRate['id'];
					$taxClass = array(
						'name' => $taxClassRate['name'],
						'prioritizedRates' => array(),
						'effectiveRate' => $this->getEffectiveTaxRate($taxRates),
					);
					$taxClass['effectiveTax'] = $this->round(
						$this->calculateTax($price, $taxClass['effectiveRate'], false)
					);
				}

				$taxClassRates[$taxRateId] = array(
					'name' => $taxClassRate['tax_rate_name'],
					'rate' => $taxClassRate['rate'],
				);
				$priorityName[] = $taxClassRate['tax_rate_name'];
				$priorityRate += $taxClassRate['rate'];
			}

			// At a priority
			$tax = $this->calculateTax($compoundTotal, $priorityRate, false);
			$compoundTotal += $tax;

			$roundedTax = $this->round($tax);
			$taxClass['prioritizedRates'][$priority]['name'] = implode(' + ', $priorityName);
			$taxClass['prioritizedRates'][$priority]['rate'] = $priorityRate;
			$taxClass['prioritizedRates'][$priority]['amount'] = $roundedTax;
			foreach($taxClassRates as &$taxRate) {
				if($priorityRate == 0) {
					$taxRate['amount'] = 0;
				}
				else {
					$taxRate['amount'] = $this->round($roundedTax * ($taxRate['rate'] / $priorityRate), 4);
				}
			}
			$taxClass['prioritizedRates'][$priority]['rates'] = $taxClassRates;
		}
		return $taxClass;
	}

	public function getTaxSummaryForClassPrices($taxZoneId, $taxClassPrices, $classRates = array())
	{
		$taxSummary = array();
		foreach($taxClassPrices as $taxClassId => $prices) {
			// Use the supplied rates for this class
			if(!empty($classRates)) {
				$taxRates = $classRates[$taxClassId];
			}
			else {
				$taxRates = $this->getPrioritizedRatesForClass($taxZoneId, $taxClassId);
			}

			$priceTotal = array_sum($prices);
			$summary = $this->getTaxSummaryForPrice($priceTotal, $taxRates);
			if(empty($summary)) {
				continue;
			}

			$taxSummary[$taxClassId] = $summary;
		}
		return $taxSummary;
	}

	/**
	 * Given a price that includes tax, calculate and strip the tax component
	 * from the price and return the exclusive price.
	 *
	 * @param float $price Price to strip tax from.
	 * @param float $taxRate Tax rate applied to $price.
	 * @param boolean $round Round result or not. Defaults to true.
	 * @return float Price without applied tax component.
	 */
	public function stripTaxFromPrice($price, $taxRate, $round = true)
	{
		$tax = $this->calculateTax($price, $taxRate, true, $round);
		$price -= $tax;
		return $price;
	}

	/**
	 * Return an associative array containing the ID (as key) and name (value)
	 * of all tax zones configured.
	 *
	 * @return array Array of tax zones.
	 */
	public function getTaxClasses()
	{
		$taxClasses = array();
		$query = "
			SELECT *
			FROM [|PREFIX|]tax_classes
			ORDER BY name ASC
		";
		$result = $this->db->query($query);
		while($taxClass = $this->db->fetch($result)) {
			$taxClasses[$taxClass['id']] = $taxClass['name'];
		}

		return $taxClasses;
	}

	public function getPrice($price, $taxClassId, $displayIncTax, $taxZoneId = null, $priceIncludesTax = null, $round = true)
	{
		if($taxZoneId === null) {
			$taxZoneId = $this->determineTaxZone();
		}

		if($priceIncludesTax === null) {
			$priceIncludesTax = getConfig('taxEnteredWithPrices');
		}

		if($displayIncTax === true) {
			$displayIncTax = TAX_PRICES_DISPLAY_INCLUSIVE;
		}
		else if($displayIncTax === false) {
			$displayIncTax = TAX_PRICES_DISPLAY_EXCLUSIVE;
		}

		// Show price ex tax, and prices are entered without tax so return what we have
		if(($displayIncTax == TAX_PRICES_DISPLAY_EXCLUSIVE || $displayIncTax == TAX_PRICES_DISPLAY_BOTH) &&
			$priceIncludesTax == TAX_PRICES_ENTERED_EXCLUSIVE) {
				return $price;
		}

		// Determine the tax rate for which the price already includes
		$taxIncluded = 0;
		if($priceIncludesTax == TAX_PRICES_ENTERED_INCLUSIVE) {
			$storeTaxZone = $this->determineStoreDefaultTaxZone();
			$taxIncluded = $this->getEffectiveClassRate($storeTaxZone, $taxClassId);
		}

		// Determine the tax rate for this zone and tax class
		$taxRate = $this->getEffectiveClassRate($taxZoneId, $taxClassId);

		// If the price already includes tax, showing prices inc tax and the
		// rate matches, just return $price
		if($displayIncTax == TAX_PRICES_DISPLAY_INCLUSIVE && $priceIncludesTax == TAX_PRICES_ENTERED_INCLUSIVE &&
			$taxRate == $taxIncluded) {
				return $price;
		}

		// Price already includes tax so first remove it
		if($priceIncludesTax == TAX_PRICES_ENTERED_INCLUSIVE) {
			$price -= $this->calculateTax($price, $taxIncluded, true);
		}

		if($displayIncTax == TAX_PRICES_DISPLAY_INCLUSIVE && $taxRate > 0) {
			// If an amount has just been stripped of tax, round the price to keep it
			// consistent.
			if($priceIncludesTax == TAX_PRICES_ENTERED_INCLUSIVE) {
				$price = $this->round($price);
			}
			$price += $this->calculateTax($price, $taxRate, false);
		}

		// Round the price
		if($round == true) {
			$price = $this->round($price);
		}

		return $price;
	}

	public function round($price, $precision = null)
	{
		if($precision === null) {
			$precision = getConfig('DecimalPlaces');
		}

		// Round using traditional half-up rounding
		return round($price, $precision);
	}
}