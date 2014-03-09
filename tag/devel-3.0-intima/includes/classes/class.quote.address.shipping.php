<?php
class ISC_QUOTE_ADDRESS_SHIPPING extends ISC_QUOTE_ADDRESS
{
	protected $shippingMethod;
	protected $shippingCache = null;

	/**
	 * @var array Array of discounts and their amount applied to this shipping.
	 */
	protected $discounts = array();


	public function setShippingMethod($price, $description, $module = '', $isCustom = false)
	{
		$this->shippingMethod = array(
			'price'			=> $price,
			'description'	=> $description,
			'module'		=> $module,
			'isCustom'		=> $isCustom,
		);

		// Remove any cached totals on this address
		$this->invalidateCachedTotals(false);

		return $this;
	}

	public function getCachedShippingMethod($id)
	{
		if (!empty($this->shippingCache[$id])) {
			return $this->shippingCache[$id];
		}

		return false;
	}

	/**
	* Takes a cached shipping method id and uses it's values as this shipping address's shipping method.
	*
	* @param int $id
	* @return bool false if method not found
	*/
	public function useCachedShippingMethod($id, $isCustom = false)
	{
		$method = $this->getCachedShippingMethod($id);
		if (!$method) {
			return false;
		}

		$this->setShippingMethod(
			$method['price'],
			$method['description'],
			$method['module'],
			$isCustom
		);

		$this->setHandlingCost($method['handling']);

		return true;
	}

	public function invalidateCachedTotals($removeShippingMethod = true)
	{
		if ($removeShippingMethod && $this->shippingMethod !== null) {
			if (empty($this->shippingMethod['isCustom'])) {
				$this->shippingMethod = null;
			}
			$this->removeCachedShippingMethods();
		}

		parent::invalidateCachedTotals();
	}

	public function removeCachedShippingMethods()
	{
		$this->shippingCache = null;
	}

	/**
	 * Returns a list of the shippable items attached to this
	 * quote and the total fixed shipping cost for the items.
	 *
	 * @return array (shippableItemsList, fixedShippingCost)
	 */
	public function getShippingQuoteItems()
	{
		$items = $this->getItems();
		if (empty($items)) {
			return null;
		}

		$shippingQuoteItems = array();
		$quoteFixedShipping = 0;
		foreach ($items as $k => $item) {
			if (!$item->getProductId() || $item->hasFreeShipping()) {
				continue;
			}

			$fixedShippingCost = $item->getFixedShippingCost() * $item->getQuantity();
			if ($fixedShippingCost > 0) {
				// add to total fixed shipping cost
				$quoteFixedShipping += $fixedShippingCost;
				continue;
			}

			// otherwise add item to be processed later
			$shippingQuoteItems[$k] = $item;
		}

		return array($shippingQuoteItems, $quoteFixedShipping);
	}

	/**
	 * Calculates shipping quotes for a shipping method, taking into
	 * consideration address, shipping zone, quote items, fixed shipping
	 * costs and handling fees.
	 *
	 * @param array A shipping_methods row
	 * @param boolean True to fetch real time shipping quotes
	 * @return array Calculated shipping quotes
	 */
	public function getShippingMethodQuotes($method, $enableRealTime=true)
	{
		$shippingZone = GetShippingZoneById($method['zoneid']);
		$zoneHandlingFee = 0;

		if ($shippingZone['zonehandlingtype'] == 'global' && $shippingZone['zonehandlingseparate']) {
			$zoneHandlingFee = $shippingZone['zonehandlingfee'];
		}

		if ($shippingZone['zonehandlingtype'] == 'module' && $shippingZone['zonehandlingseparate']) {
			$methodHandling = $method['methodhandlingfee'];
		}
		else {
			$methodHandling = $zoneHandlingFee;
		}

		$shippingModule = null;
		getModuleById('shipping', $shippingModule, $method['methodmodule']);

		if (!is_object($shippingModule)) {
			return null;
		}

		// Real-time shipping provider yet real-time rates are disabled, so skip it
		if (!$shippingModule->_flatrate && !$enableRealTime) {
			return null;
		}

		$shippingModule->setMethodId($method['methodid']);

		// Set the destination settings
		$shippingModule->setDestinationCountry($this->getCountryId());
		$shippingModule->setDestinationState($this->getStateName());
		$shippingModule->setDestinationZip($this->getZip());
		$shippingModule->setDestinationType("RES");

		// Add the products to the shipping quote
		list($shippingQuoteItems, $fixedShippingCost) = $this->getShippingQuoteItems();

		foreach ($shippingQuoteItems as $k => $item) {
			$dimensions = $item->getDimensions();
			$shippingModule->addItem(
				$item->getWeight(),
				$dimensions['depth'],
				$dimensions['width'],
				$dimensions['height'],
				$item->getQuantity(),
				$item->getName(),
				$item->getBasePrice()
			);
		}

		// Now attempt to fetch the quotes
		if (method_exists($shippingModule, 'getServiceQuotes')) {
			$quotes = $shippingModule->getServiceQuotes();
		}
		else {
			$err = null;
			$quotes = $shippingModule->getQuote($err);
		}

		if ($quotes === false) {
			return array();
		}
		else if (!is_array($quotes)) {
			$quotes = array($quotes);
		}

		$shippingQuotes = array();

		foreach ($quotes as $quote) {
			// Kept for legacy compatibility?
			if (!is_object($quote)) {
				$quote = $quote[0];
			}

			$price = $this->factorInShippingHandling(
				$method['zoneid'],
				$quote->getPrice() + $fixedShippingCost,
				$method['methodhandlingfee']
			);

			$shippingQuotes[] = array(
				'description'	=> $quote->getDesc(true),
				'price'			=> $price,
				'handling'		=> $methodHandling,
				'module'		=> $method['methodmodule'],
				'methodId'		=> $method['methodid']
			);

			if ($quote->getTransit() != '' && $quote->getTransit() != -1) {
				$shippingQuotes[count($shippingQuotes) - 1]['transit'] = $quote->getTransit();
			}
		}

		return $shippingQuotes;
	}

	public function getAvailableShippingMethods($enableRealTime = true, $cacheResults = true)
	{
		list($shippingQuoteItems, $fixedShippingCost) = $this->getShippingQuoteItems();

		$shippingQuotes = array();
		if ($cacheResults) {
			$this->shippingCache = array();
			$shippingQuotes = &$this->shippingCache;
		}

		// Fetch the shipping zone that this address belongs in
		$shippingZoneId = $this->getShippingAddressZone();
		$shippingZone = GetShippingZoneById($shippingZoneId);

		// Calculate the handling fee
		$zoneHandlingFee = 0;
		if ($shippingZone['zonehandlingtype'] == 'global' && $shippingZone['zonehandlingseparate']) {
			$zoneHandlingFee = $shippingZone['zonehandlingfee'];
		}

		// Free shipping method
		if ($this->allowsFreeShipping()) {
			$shippingQuotes[] = array(
				'description'	=> getLang('FreeShipping'),
				'price'			=> 0,
				'methodId'		=> -1,
				'module'		=> '',
				'handling'		=> $zoneHandlingFee,
			);

			if (empty($shippingQuoteItems)) {
				return $shippingQuotes;
			}
		}

		// Fixed shipping cost method
		if (empty($shippingQuoteItems) && $fixedShippingCost) {
			$adjustedPrice = $this->factorInShippingHandling($shippingZoneId, $fixedShippingCost, 0);
			$fixedShippingName = GetConfig('StoreName');

			$shippingQuotes[] = array(
				'description'	=> $fixedShippingName,
				'price'			=> $adjustedPrice,
				'methodId'		=> -1,
				'module'		=> '',
				'handling'		=> $zoneHandlingFee,
			);

			if (empty($shippingQuoteItems)) {
				return $shippingQuotes;
			}
		}

		// Fetch applicable shipping methods for this zone
		$query = "
			SELECT *
			FROM [|PREFIX|]shipping_methods
			WHERE zoneid='".(int)$shippingZoneId."' AND methodenabled='1' AND methodvendorid='".(int)$shippingZone['zonevendorid']."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($method = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$quotes = $this->getShippingMethodQuotes($method, $enableRealTime);

			if($quotes)
				$shippingQuotes = array_merge($shippingQuotes, $quotes);
		}

		// Sort the shipping quotes by price
		uasort($shippingQuotes, array($this, 'sortShippingQuotes'));
		return $shippingQuotes;
	}

	/**
	 * User defined comparison function for sorting shipping quotes from
	 * least expensive to most expensive.
	 *
	 * @param array The first field to compare
	 * @param array The second field to compare
	 * @return int The sorting position for the two compared shipping quotes.
	 */
	private function sortShippingQuotes($a, $b)
	{
		if ($a['price'] == $b['price']) {
			return 0;
		}
		else if ($a['price'] < $b['price']) {
			return -1;
		}
		else {
			return 1;
		}
	}

	/**
	 * Factor in the handling fee for a shipping quote in a particular shipping zone.
	 *
	 * @param int The ID of the shipping zone this quote comes from.
	 * @param float The base shipping price to have handling factored in to.
	 * @param float The handling fee for this shipping method.
	 * @return float The adjusted price with handling factored in.
	 */
	public function factorInShippingHandling($zoneId, $price, $methodHandling)
	{
		$shippingZone = getShippingZoneById($zoneId);
		if (!is_array($shippingZone)) {
			return $price;
		}
		else if ($shippingZone['zonehandlingseparate'] == 1) {
			return $price;
		}

		if ($shippingZone['zonehandlingtype'] == 'module') {
			return $price + $methodHandling;
		}
		else if ($shippingZone['zonehandlingtype'] == 'global') {
			return $price + $shippingZone['zonehandlingfee'];
		}

		return $price;
	}

	/**
	 * Get the tax zone that this quote falls under based on its associated
	 * customer billing and shipping addresses as well as the customer group.
	 *
	 * @return int The ID of the tax zone that the quote falls under.
	 */
	public function getApplicableTaxZone()
	{
		return getClass('ISC_TAX')->determineTaxZone(
			$this->getQuote()->getBillingAddress()->getAsArray(),
			$this->getAsArray(),
			$this->getQuote()->getCustomerGroupId()
		);
	}

	public function getShippingAddressZoneName()
	{
		$shippingZoneId = $this->getShippingAddressZone();
		$shippingZone = getShippingZoneById($shippingZoneId);
		if (!empty($shippingZone)) {
			return $shippingZone['zonename'];
		}

		return '';
	}

	public function getShippingAddressZone()
	{
		return getShippingZoneIdByAddress($this->getAsArray());
	}

	public function allowsFreeShipping()
	{
		// If the quote has a free shipping option, then so does this address
		if ($this->getQuote()->getHasFreeShipping()) {
			return true;
		}

		$items = $this->getItems();
		$freeShippingProducts = 0;

		foreach ($items as $item) {
			if ($item->hasFreeShipping()) {
				++$freeShippingProducts;
			}
		}

		if ($freeShippingProducts == count($items)) {
			return true;
		}

		$shippingZoneId = $this->getShippingAddressZone();
		if (!$shippingZoneId) {
			return false;
		}

		$shippingZone = getShippingZoneById($shippingZoneId);
		if ($shippingZone['zonefreeshipping']) {
			$subTotal = $this->getQuote()->getDiscountedBaseSubTotal();
			if ($subTotal >= $shippingZone['zonefreeshippingtotal']) {
				return true;
			}
		}

		return false;
	}

	public function getBaseSubTotal()
	{
		$total = 0;
		$items = $this->getItems();
		foreach ($items as $item) {
			$total = $item->getBasePrice();
		}

		return $total;
	}
	public function getShippingMethod()
	{
		return $this->shippingMethod;
	}

	public function hasShippingMethod()
	{
		return !empty($this->shippingMethod['description']);
	}

	public function getBaseShippingCost()
	{
		$shippingMethod = $this->getShippingMethod();
		if (!isset($shippingMethod['price'])) {
			return 0;
		}

		return $shippingMethod['price'] - $this->getDiscountAmount();
	}

	public function getNonDiscountedBaseShippingCost()
	{
		$shippingMethod = $this->getShippingMethod();
		if (!isset($shippingMethod['price'])) {
			return 0;
		}

		return $shippingMethod['price'];
	}

	public function getShippingCost($incTax = null)
	{
		// there won't be tax to be applied if no shipping cost
		$shippingBaseCost = $this->getBaseShippingCost();
		if ($shippingBaseCost <= 0) {
			return 0;
		}
		return getClass('ISC_TAX')->getPrice(
			$shippingBaseCost,
			getConfig('taxShippingTaxClass'),
			$incTax,
			$this->getApplicableTaxZone()
		);

	}

	public function getNonDiscountedShippingCost($incTax = null)
	{
		return getClass('ISC_TAX')->getPrice(
			$this->getNonDiscountedBaseShippingCost(),
			getConfig('taxShippingTaxClass'),
			$incTax,
			$this->getApplicableTaxZone()
		);

	}

	public function setShippingCost($newPrice)
	{
		if (isset($this->shippingMethod['price'])) {
			$this->shippingMethod['price'] = $newPrice;
		}
	}

	public function getShippingCostTax()
	{
		return $this->getShippingCost(true) - $this->getShippingCost(false);
	}

	public function getShippingProvider()
	{
		$shippingMethod = $this->getShippingMethod();
		if (!empty($shippingMethod)) {
			return $shippingMethod['description'];
		}
		return false;
	}

	public function getShippingModule()
	{
		$shippingMethod = $this->getShippingMethod();
		if (!empty($shippingMethod)) {
			return $shippingMethod['module'];
		}
		return false;
	}

	public function setHandlingCost($cost)
	{
		$this->handlingCost = $cost;
		$this->invalidateCachedTotals(false);
		return $this;
	}


	public function isComplete()
	{
		if (!$this->hasCompleteAddress()) {
			return false;
		}

		// Has a shipping provider been selected?
		return $this->hasShippingMethod();
	}

	/**
	 * Apply a discount to this shipping with the given ID and amount.
	 * If the supplied amount is 0, the discount with the given ID is removed.
	 *
	 * @param string $id Unique identifier for the discount.
	 * @param float $amount Discount amount. If 0, removes discount.
	 * @return ISC_QUOTE_ADDRESS_SHIPPING Current shipping address instance.
	 */
	public function addDiscount($id, $amount)
	{
		if ($amount == 0) {
			unset($this->discounts[$id]);
			return $this;
		}

		$this->discounts[$id] = $amount;
		return $this;
	}

	/**
	 * Get the total amount that this shipping should be discounted by.
	 *
	 * @return float Amount shipping should be discounted by.
	 */
	public function getDiscountAmount()
	{
		return array_sum($this->discounts);
	}

	/**
	* Return list of discounts applied to this quote shipping as an array
	*
	* @return array as id => value
	*/
	public function getDiscounts()
	{
		return $this->discounts;
	}
}