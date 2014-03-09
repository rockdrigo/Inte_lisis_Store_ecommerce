<?php
class ISC_QUOTE
{
	/**
	 * @var int Customer ID that this quote belongs to.
	 */
	protected $customerId;

	/**
	 * @var int The customer that this quote pulls pricing from.
	 */
	protected $customerGroupId;

	/**
	 * @var array Array of arbitrary discounts applied to this quote.
	 */
	protected $discounts = array();

	/**
	 * @var array Array of addresses attached to this quote.
	 */
	protected $addresses = array();

	/**
	 * @var array Associative array containing applied coupons.
	 */
	protected $coupons = array();

	/**
	 * @var array Associative array containing applied gift certificates.
	 */
	protected $giftCertificates = array();

	/**
	 * @var array Array containing ISC_QUOTE_ITEM instances for items in the quote.
	 */
	protected $items = array();

	/**
	 * @var boolean Set to true if split-address shipping is being used for this quote.
	 */
	protected $isSplitShipping = false;

	/**
	 * @var array In-memory cache of calculated quote totals.
	 */
	protected $cachedTotals = array();

	/**
	 * @var boolean Set to true if this quote should have free shipping available.
	 */
	protected $hasFreeShipping = false;

	/**
	 * @var float Amount of store credit to be applied to this quote when finalizing.
	 */
	protected $appliedStoreCredit = 0;

	/**
	 * @var array Array containing discount rules applied to the quote.
	 */
	protected $appliedDiscountRules = array();

	/**
	 * @var array Array containing eligibility free shipping details.
	 */
	protected $eligibleFreeShippingInfo = array();

	/**
	* @var string Message provided by customer during checkout (visible to the customer). This is _not_ related to the order messaging system.
	*/
	protected $customerMessage = '';

	/** @var string Notes added by store staff (not visible to customer) */
	protected $staffNotes = '';

	/** @var int The order status after conversion from an existing quote (may also be used in future to define which status an order should be created at when being created from a quote) */
	protected $orderStatus = ORDER_STATUS_INCOMPLETE;

	/** @var int The order id being edited if this quote instance is being used to edit an order, otherwise this will be false */
	protected $orderId = false;

	/**
	* Callback method for PHP array sorting methods for sorting a list of items by their shipping address id.
	*
	* Usage:
	* $items = $quote->getItems();
	* usort($items, array('ISC_QUOTE', 'sortItemsByAddressIdCallback'));
	*
	* @param ISC_QUOTE_ITEM $a
	* @param ISC_QUOTE_ITEM $b
	* @return int
	*/
	public static function sortItemsByAddressIdCallback($a, $b)
	{
		return strcmp($a->getAddressId(), $b->getAddressId());
	}

	/**
	* Determines if the store, by default, includes tax on prices displayed in the cart.
	*
	* This calculation was originally performed in a few places. It seemed logical to move it to ISC_QUOTE so Twig templates can access it via quote objects.
	*
	* @return bool
	*/
	public function doesStoreCartDisplayIncludeTax()
	{
		if (getConfig('taxDefaultTaxDisplayCart') == TAX_PRICES_DISPLAY_INCLUSIVE) {
			return true;
		}
		return false;
	}

	/**
	 * Set the flag indicating if this quote should offer free shipping as
	 * an available shipping option.
	 *
	 * @param boolean $hasFreeShipping True if free shipping should be available.
	 * @return ISC_QUOTE This quote instance.
	 */
	public function setHasFreeShipping($hasFreeShipping)
	{
		$this->hasFreeShipping = $hasFreeShipping;
		return $this;
	}

	/**
	 * Return the value of the free shipping flag - that is, if this quote
	 * should have a free shipping option when calculating shipping.
	 *
	 * @return boolean True for free shipping, false if not.
	 */
	public function getHasFreeShipping()
	{
		return $this->hasFreeShipping;
	}

	/**
	 * Get the billing address associated with this quote. If there's no billing
	 * address, one will be created and assigned to the quote.
	 *
	 * @return ISC_QUOTE_ADDRESS ISC_QUOTE_ADDRESS instance w/ billing details.
	 */
	public function getBillingAddress()
	{
		foreach ($this->addresses as $address) {
			if ($address->getType() == ISC_QUOTE_ADDRESS::TYPE_BILLING) {
				return $address;
			}
		}

		// No billing address was found. Silently create one
		$address = new ISC_QUOTE_ADDRESS;
		$this->setBillingAddress($address);
		return $address;
	}

	/**
	 * Set an address to use as the billing address for this quote.
	 *
	 * @param ISC_QUOTE_ADDRESS $address ISC_QUOTE_ADDRESS instance.
	 * @return ISC_QUOTE This quote instance.
	 */
	public function setBillingAddress(ISC_QUOTE_ADDRESS $address)
	{
		$address->setQuote($this);
		$address->setType(ISC_QUOTE_ADDRESS::TYPE_BILLING);
		$this->addresses[$address->getId()] = $address;
		return $this;
	}

	/**
	 * Get the single shipping address for this order, in the case of
	 * single address shipping. If more than one shipping address is
	 * attached to the quote, the first will be returned.
	 *
	 * A shipping address object will be created if a match isn't found
	 * on the quote.
	 *
	 * @return ISC_QUOTE_ADDRESS_SHIPPING Quote shipping address instance.
	 */
	public function getShippingAddress()
	{
		foreach ($this->addresses as $address) {
			if ($address->getType() == ISC_QUOTE_ADDRESS::TYPE_SHIPPING) {
				return $address;
			}
		}

		// Still here? Create an address siliently
		$address = new ISC_QUOTE_ADDRESS_SHIPPING;
		$this->addShippingAddress($address);
		return $address;
	}

	/**
	 * Remove all of the shipping addresses applied to this quote, leaving
	 * only the first matching shipping address. Used when collapsing a
	 * multi-shipping quote in to a single-shipping quote.
	 *
	 * @return ISC_QUOTE This quote instance.
	 */
	public function removeAllShippingAddresses()
	{
		$keptFirst = false;
		foreach ($this->addresses as $id => $address) {
			if ($address->getType() != ISC_QUOTE_ADDRESS::TYPE_SHIPPING) {
				continue;
			}
			else if (!$keptFirst) {
				$keptFirst = true;
				continue;
			}
			unset($this->addresses[$id]);
		}

		// Remove split shipping on all items by setting it all back
		// to the billing address (always address ID 0)
		$shippingAddressId = $this->getShippingAddress()
			->getId();
		foreach ($this->items as $item) {
			$item->setAddressId($shippingAddressId);
		}
		return $this;
	}

	/**
	* Remove the specified shipping address from this quote, optionally moving all items associated with it to another address in the quote
	*
	* @param string $addressId id of address to remove
	* @param string $moveItemsTo optional id of address to move items to
	* @return ISC_QUOTE
	*/
	public function removeShippingAddress($addressId, $moveItemsTo = null)
	{
		$address = false;

		foreach ($this->addresses as $key => $address) {
			// this loop can potentially clean up duplicate addresses to so perhaps leave it un-broken
			if ($address->getId() == $addressId) {
				if ($moveItemsTo === null) {
					unset($this->addresses[$key]);
					break;
				}

				$items = $address->getItems();
				foreach ($items as /** @var ISC_QUOTE_ITEM */$item) {
					$item->setAddressId($moveItemsTo);
				}
				unset($this->addresses[$key]);
			}
		}

		return $this;
	}

	/**
	 * Remove all shipping and billing addresses.
	 *
	 * @return ISC_QUOTE This quote instance.
	 */
	public function removeAllAddresses()
	{
		$this->addresses = array();

		foreach ($this->items as $item)
			$item->setAddressId(0);

		return $this;
	}

	/**
	* Array sorting callback method to place the 'unallocated' ISC_QUOTE_ADDRESS first as sometimes it may be created after the other addresses (e.g. when editing an order)
	*
	* @param ISC_QUOTE_ADDRESS $a
	* @param ISC_QUOTE_ADDRESS $b
	* @return int
	*/
	public static function sortUnallocatedAddressFirst(ISC_QUOTE_ADDRESS $a, ISC_QUOTE_ADDRESS $b)
	{
		if ($a->getIsUnallocated()) {
			return -1;
		}

		if ($b->getIsUnallocated()) {
			return 1;
		}

		return 0;
	}

	/**
	 * Get all of the shipping addresses attached to this quote.
	 *
	 * @return array Array of ISC_QUOTE_ADDRESS_SHIPPING instances.
	 */
	public function getShippingAddresses()
	{
		$shippingAddresses = array();
		foreach ($this->addresses as $address) {
			if ($address->getType() == ISC_QUOTE_ADDRESS::TYPE_SHIPPING) {
				$shippingAddresses[] = $address;
			}
		}

		// If an order is digital, remove all shipping addresses
		if ($this->isDigital() && !empty($shippingAddresses)) {
			$this->removeAllShippingAddresses();
			return array();
		}

		usort($shippingAddresses, array('ISC_QUOTE', 'sortUnallocatedAddressFirst'));
		return $shippingAddresses;
	}

	/**
	 * Given the ID of an address, return the instance of it.
	 *
	 * @return ISC_QUOTE_ADDRESS Address instance with given ID or false on failure
	 */
	public function getAddressById($id)
	{
		foreach ($this->addresses as $address) {
			if ($address->getId() == $id) {
				return $address;
			}
		}

		return false;
	}

	/**
	 * Add a shipping address to this quote. If split-shipping is not enabled
	 * the supplied shipping address will override the address already on this
	 * quote.
	 *
	 * @param ISC_QUOTE_ADDRESS_SHIPPING $address Shipping address instance.
	 * @return ISC_QUOTE This quote instance.
	 */
	public function addShippingAddress(ISC_QUOTE_ADDRESS_SHIPPING $address)
	{
		$address->setQuote($this);
		$address->setType(ISC_QUOTE_ADDRESS::TYPE_SHIPPING);
		if ($this->isSplitShipping == false) {
			foreach ($this->addresses as $k => $existingAddress) {
				if ($existingAddress->getType() == ISC_QUOTE_ADDRESS::TYPE_SHIPPING) {
					$this->addresses[$k] = $address;
					$address->setId($existingAddress->getId());
					return $this;
				}
			}
		}
		$this->addresses[$address->getId()] = $address;
		return $this;
	}

	/**
	 * Given an address ID, check if it exists in this quote.
	 *
	 * @param string $id Address ID.
	 * @return boolean True if the address is associated with the quote, or
	 * false if not.
	 */
	public function hasAddress($id)
	{
		foreach ($this->addresses as $address) {
			if ($address->getId() == $id) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Given an ISC_QUOTE_ITEM instance, add it to this quote.
	 *
	 * @param ISC_QUOTE_ITEM $item ISC_QUOTE_ITEM instance to add.
	 * @param boolean Attempt to increment quantities if this item already exists or not.
	 * @return ISC_QUOTE This quote instance.
	 */
	public function addItem(ISC_QUOTE_ITEM $item, $collapse = true)
	{
		$hash = $item->getHash();
		if ($collapse != false) {
			foreach ($this->items as $existingItem) {
				if ($item->getProductId() && $existingItem->getHash() == $hash) {
					FlashMessage(GetLang('CartHasProductAlready'), MSG_INFO);
					$existingItem->incrementQuantity($item->getQuantity());
					return $this;
				}
				if ($item->getProductId()  == $existingItem->getProductId()) {
					FlashMessage(GetLang('CartHasProductAlready'), MSG_INFO);
				}
			}
		}
		$item->setQuote($this);
		try {
			$this->items[] = $item;
			$item->setInQuote(true);
		}
		catch(ISC_QUOTE_EXCEPTION $e) {
			// Remove the item from the quote.
			array_pop($this->items);
			$item->setInQuote(false);

			throw $e;
		}
		
		$clasifier = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT clasifier FROM [|PREFIX|]intelisis_products WHERE productid = "'.$item->getProductId().'"', 'clasifier');
		if($clasifier){
			$item->setClasifier($clasifier);
		}
		else {
			$item->setClasifier('');
		}
		
		return $this;
	}

	/**
	 * Given the ID of an item in the quote, remove it.
	 *
	 * @param string $itemId ID of item to remove.
	 * @return ISC_QUOTE This instance of ISC_QUOTE.
	 */
	public function removeItem($itemId)
	{
		foreach ($this->items as $key => $item) {
			if ($itemId == $item->getId()) {
				$item->removeChildren();
				$address = $item->getAddress();
				unset($this->items[$key]);
				$address->invalidateCachedTotals();
				if (!$item->getParentId()) {
					$this->reapplyDiscounts();
				}
				break;
			}
		}

		return $this;
	}

	/**
	 * Get the customer ID associated with the quote.
	 *
	 * @return int Customer ID.
	 */
	public function getCustomerId()
	{
		return $this->customerId;
	}

	/**
	 * Set the ID of the customer that this quote belongs to.
	 *
	 * @param int $customerId ID of the customer.
	 * @return ISC_QUOTE This instance of ISC_QUOTE.
	 */
	public function setCustomerId($customerId)
	{
		$this->customerId = $customerId;
		return $this;
	}

	/**
	 * Load and set extended product and variation details for items in the
	 * quote.
	 *
	 * @param ISC_QUOTE_ITEM $item Optional item, to ensure it is also loaded.
	 * @return ISC_QUOTE This instance of ISC_QUOTE.
	 */
	public function loadProductData(ISC_QUOTE_ITEM $additionalItem = null)
	{
		$productIds = array();
		$variationIds = array();

		if ($additionalItem !== null) {
			$productIds[] = $additionalItem->getProductId();
			$variationIds[] = $additionalItem->getVariationId();
		}

		foreach ($this->items as $item) {
			$productIds[] = $item->getProductId();
			$variationIds[] = $item->getVariationId();
		}

		$productIds = array_unique($productIds);
		$variationIds = array_unique($variationIds);

		if (empty($productIds)) {
			return $this;
		}

		$productData = array();
		$variationData = array();

		// Load up the data for the products
		$query = "
			SELECT
				p.productid, prodcurrentinv, prodcode, prodinvtrack,
				prodweight, prodwidth, prodheight, prodvariationid,
				proddepth, prodname, prodprice, prodretailprice,
				prodsaleprice, prodcalculatedprice, tax_class_id,
				prodavailability, prodtype, prodcostprice,
				prodfixedshippingcost, prodfreeshipping,
				prodoptionsrequired, pi.*, prodwrapoptions, prodvendorid,
				prodeventdaterequired, prodeventdatefieldname, prodpreorder,
				prodreleasedate, prodpreordermessage, prodcatids, prodminqty,
				prodmaxqty, disable_google_checkout,
				".getProdCustomerGroupPriceSQL().", (
					SELECT GROUP_CONCAT(ca.categoryid SEPARATOR ',')
					FROM [|PREFIX|]categoryassociations ca
					WHERE p.productid=ca.productid
				) AS categoryids 
			FROM [|PREFIX|]products p
			LEFT JOIN [|PREFIX|]product_images pi
				ON (p.productid=pi.imageprodid AND pi.imageisthumb=1)
			WHERE p.productid in (".implode(', ', $productIds).")
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($product = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$productData[$product['productid']] = $product;
		}

		// Are there any variations to load?
		if (!empty($variationIds)) {
			$query = "
				SELECT
					combinationid, vcproductid, vcimage, vcimagethumb, vcweight,
					vcweightdiff, vcstock, vcpricediff, vcprice, vcsku
				FROM [|PREFIX|]product_variation_combinations
				WHERE combinationid IN (".implode(",", $variationIds).")
			";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$variationImages = array();
			while ($variation = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$variationData[$variation['combinationid']] = $variation;
			}
		}

		foreach ($this->items as $item) {
			$productId = $item->getProductId();
			$variationId = $item->getVariationId();
			if (isset($productData[$productId])) {
				$data = $productData[$productId];
				if (isset($variationData[$variationId])) {
					$data['variation'] = $variationData[$variationId];
				}

				$item->setProductData($data);
			}
		}

		if ($additionalItem !== null) {
			$productId = $additionalItem->getProductId();
			$variationId = $additionalItem->getVariationId();
			if (isset($productData[$productId])) {
				$data = $productData[$productId];
				if (isset($variationData[$variationId])) {
					$data['variation'] = $variationData[$variationId];
				}

				$additionalItem->setProductData($data);
			}
		}

		return $this;
	}

	/**
	 * Return an array of all of the items in this quote. Each returned
	 * item is its instance of the ISC_QUOTE_ITEM object.
	 *
	 * @param int $type optional, limit returned items to one of PT_? specifiers
	 * @return array Array of ISC_QUOTE_ITEM objects for this quote.
	 */
	public function getItems($type = null)
	{
		if ($type === null) {
			return $this->items;
		}

		$items = array();
		foreach ($this->items as /** @var ISC_QUOTE_ITEM */$item) {
			if ($item->getType() == $type) {
				$items[] = $item;
			}
		}
		return $items;
	}
	
	public function getItemsClasifiers(){
		$items = array();
		foreach($this->items as $item){
			$clasifier = $item->getClasifier();
			if(!isset($items[$clasifier])){
				$items[$clasifier] = array();
			}
			$items[$clasifier][] = $item;
		}
		return $items;
	}

	/**
	 * Given the ID of a quote item, find and return the instance of it.
	 *
	 * @param string $id ID of the item.
	 * @return ISC_QUOTE_ITEM False on fail, otherwise item instance.
	 */
	public function getItemById($id)
	{
		foreach ($this->items as $item) {
			if ($item->getId() == $id) {
				return $item;
			}
		}

		return false;
	}

	/**
	 * Get the subtotal of the quote, either including or excluding tax.
	 * The subtotal consists of all of the line-item totals for the items
	 * in the quote.
	 *
	 * @param boolean $incTax True to include taxes, false to not.
	 * @return float Subtotal of the quote.
	 */
	public function getSubTotal($incTax = false)
	{
		$subtotal = 0;
		foreach ($this->items as $item) {
			$subtotal += $item->getTotal($incTax);
		}

		return $subtotal;
	}

	public function getDiscountedSubTotal($incTax)
	{
		$subtotal = 0;
		foreach ($this->items as $item) {
			$subtotal += $item->getDiscountedTotal($incTax);
		}

		return $subtotal;
	}

	/**
	 * Get the amount of tax applied to the subtotal of the quote.
	 *
	 * @return float Tax applied to subtotal.
	 */
	public function getSubTotalTax()
	{
		return $this->getSubTotal(true) - $this->getSubTotal(false);
	}

	public function getDiscountedBaseSubTotal()
	{
		return $this->getBaseSubTotal()
			- $this->getCouponDiscount()
			- $this->getDiscountAmount();
	}

	public function getBaseSubTotal()
	{
		$subtotal = 0;
		foreach ($this->items as $item) {
			$subtotal += $item->getBasePrice() * $item->getQuantity();
		}

		return $subtotal;
	}

	/**
	 * Get the base shipping cost of the quote (if known). The base shipping
	 * cost is the sum of all of the base shipping costs on each address. A
	 * base shipping cost is a price entered by the store owner, either inc or
	 * ex tax.
	 *
	 * @return float Base shipping cost.
	 */
	public function getBaseShippingCost($incTax = false)
	{
		$cost = 0;
		foreach ($this->addresses as $address) {
			if ($address->getType() != ISC_QUOTE_ADDRESS::TYPE_SHIPPING) {
				continue;
			}
			$cost += $address->getBaseShippingCost();
		}
		return $cost;
	}


	/**
	 * Get the shipping cost of the quote (if known) either including or
	 * excluding tax.
	 *
	 * @param boolean $incTax True to include taxes, false to not.
	 * @return float Subtotal of the quote.
	 */
	public function getShippingCost($incTax = false)
	{
		$cost = 0;
		foreach ($this->addresses as $address) {
			if ($address->getType() != ISC_QUOTE_ADDRESS::TYPE_SHIPPING) {
				continue;
			}
			$cost += $address->getShippingCost($incTax);
		}
		return $cost;
	}

	/**
	 * Get the non discounted shipping cost of the quote (if known) either including or
	 * excluding tax.
	 *
	 * @param boolean $incTax True to include taxes, false to not.
	 * @return float Subtotal of the quote.
	 */
	public function getNonDiscountedShippingCost($incTax = false)
	{
		$cost = 0;
		foreach ($this->addresses as $address) {
			if ($address->getType() != ISC_QUOTE_ADDRESS::TYPE_SHIPPING) {
				continue;
			}
			$cost += $address->getNonDiscountedShippingCost($incTax);
		}
		return $cost;
	}

	/**
	 * Get the amount of tax applied to the shipping cost for the quote.
	 *
	 * @return float Amount of tax applied to shipping.
	 */
	public function getShippingCostTax()
	{
		$tax = 0;
		foreach ($this->addresses as $address) {
			if ($address->getType() != ISC_QUOTE_ADDRESS::TYPE_SHIPPING) {
				continue;
			}
			$tax += $address->getShippingCostTax();
		}
		return $tax;
	}

	/**
	 * Get the base gift wrapping of the quote (if known). The base wrapping
	 * cost is the sum of all of the base wrapping costs on each item. A
	 * base wrapping cost is a price entered by the store owner, either inc or
	 * ex tax.
	 *
	 * @param boolean $incTax True to include taxes, false to not.
	 * @return float Base total cost of all gift wrapping.
	 */
	public function getBaseWrappingCost($incTax = null)
	{
		$cost = 0;
		foreach ($this->items as $item) {
			$cost += $item->getBaseWrappingCost();
		}

		return $cost;
	}

	/**
	 * Get the total amount for any gift wrapping applied to the quote either
	 * including or excluding tax.
	 *
	 * @param boolean $incTax True to include taxes, false to not.
	 * @return float Total cost of all gift wrapping.
	 */
	public function getWrappingCost($incTax = null)
	{
		$cost = 0;
		foreach ($this->addresses as $address) {
			if ($address->getType() != ISC_QUOTE_ADDRESS::TYPE_SHIPPING) {
				continue;
			}
			$cost += $address->getGiftWrappingCost($incTax);
		}
		return $cost;
	}

	/**
	 * Get the amount of tax that has been applied to gift wrapping.
	 *
	 * @return float Tax applied to gift wrapping.
	 */
	public function getWrappingCostTax()
	{
		return $this->getWrappingCost(true) - $this->getWrappingCost(false);
	}

	/**
	 * Set the amount of store credit to be applied to this quote which should
	 * come off the grand total.
	 *
	 * @param float $credit Amount of store credit to be used.
	 * @return ISC_QUOTE This instance of ISC_QUOTE.
	 */
	public function setAppliedStoreCredit($credit)
	{
		$this->appliedStoreCredit = $credit;
		return $this;
	}

	/**
	 * Get the amount of store credit applied to this quote.
	 *
	 * @return float Amount of store credit applied to the quote.
	 */
	public function getAppliedStoreCredit()
	{
		return $this->appliedStoreCredit;
	}

	/**
	 * Get the base handling cost for all addresses on this quote. The
	 * base handling cost is defined as the handling cost entered by the
	 * store owner, either including or excluding tax.
	 *
	 * @return float Base total handling cost for all addresses.
	 */
	public function getBaseHandlingCost()
	{
		$cost = 0;
		foreach ($this->addresses as $address) {
			$cost += $address->getBaseHandlingCost();
		}
		return $cost;
	}

	/**
	 * Get the handling cost for all addresses on this quote, either including
	 * or excluding tax.
	 *
	 * @param boolean $incTax True to include tax, false to not.
	 * @return float Total handling cost for all addresses.
	 */
	public function getHandlingCost($incTax = null)
	{
		$cost = 0;
		foreach ($this->addresses as $address) {
			$cost += $address->getHandlingCost($incTax);
		}
		return $cost;
	}

	/**
	 * Get the total amount of tax that applies to all of the handling costs
	 * from the addresses on this quote.
	 *
	 * @return float Total tax on handling.
	 */
	public function getHandlingCostTax()
	{
		$tax = 0;
		foreach ($this->addresses as $address) {
			$tax += $address->getHandlingCostTax();
		}
		return $tax;
	}

	public function getGrandTotal()
	{
		$totals = array(
			'subtotal' => $this->getDiscountedSubTotal(true),
			'wrapping' => $this->getWrappingCost(true),
			'shipping' => $this->getShippingCost(true),
			'handling' => $this->getHandlingCost(true),
			'giftCertificates' => $this->getGiftCertificateTotal() * -1,
		);
		$total = array_sum($totals);
		if ($total < 0) {
			$total = 0;
		}
		return $total;
	}

	public function getGrandTotalWithStoreCredit()
	{
		$total = $this->getGrandTotal();
		$total -= $this->getAppliedStoreCredit();
		if ($total < 0) {
			$total = 0;
		}

		return $total;
	}

	public function getGrandTotalWithoutGiftCertificates()
	{
		$totals = array(
			'subtotal' => $this->getDiscountedSubTotal(true),
			'wrapping' => $this->getWrappingCost(true),
			'shipping' => $this->getShippingCost(true),
			'handling' => $this->getHandlingCost(true),
		);
		$total = array_sum($totals);
		if ($total < 0) {
			$total = 0;
		}
		return $total;
	}

	/**
	 * Get all of the addresses (billing and shipping) that belong to this
	 * quote and return them as an array.
	 *
	 * @return array Array of ISC_QUOTE_ADDRESS instances belong to this quote.
	 */
	public function getAllAddresses()
	{
		$addresses = array();
		$isDigital = $this->isDigital();
		if (!$isDigital) {
			return $this->addresses;
		}

		foreach ($this->addresses as $address) {
			if ($address instanceof ISC_QUOTE_ADDRESS_SHIPPING) {
				continue;
			}

			$addresses[] = $address;
		}
		return $addresses;
	}

	public function getTaxTotal()
	{
		$total = 0;
		$addresses = $this->getAllAddresses();
		foreach ($addresses as $address) {
			$total += $address->getTaxTotal();
		}
		return $total;
	}

	public function getTaxRateSummary()
	{
		$taxSummary = array();
		$taxClassSummary = $this->getTaxSummary();
		foreach ($taxClassSummary as $taxClassId => $taxClass) {
			foreach ($taxClass['prioritizedRates'] as $rate) {
				if (!isset($taxSummary[$rate['name']])) {
					$taxSummary[$rate['name']] = $rate['amount'];
				}
				else {
					$taxSummary[$rate['name']] += $rate['amount'];
				}
			}
		}

		return $taxSummary;
	}

	public function getTaxSummary()
	{
		$taxSummary = array();
		$addresses = $this->getAllAddresses();
		foreach ($addresses as $address) {
			$addressSummary = $address->getTaxSummary();

			foreach ($addressSummary as $taxClassId => $taxClass) {
				if (!isset($taxSummary[$taxClassId])) {
					$taxSummary[$taxClassId] = $taxClass;
					continue;
				}

				$taxSummary[$taxClassId]['effectiveTax'] += $taxClass['effectiveTax'];
				foreach ($taxClass['prioritizedRates'] as $priority => $rate) {
					$taxSummary[$taxClassId]['prioritizedRates'][$priority]['amount'] +=
						$rate['amount'];
					foreach ($rate['rates'] as $taxRateId => $taxRate) {
						$taxSummary[$taxClassId]['prioritizedRates'][$priority]['rates'][$taxRateId]['amount'] +=
							$taxRate['amount'];
					}
				}
			}
		}

		return $taxSummary;
	}

	public function addDiscount($id, $value)
	{
		if ($value == 0) {
			unset($this->discounts[$id]);
			return $this;
		}

		$this->discounts[$id] = $value;
		return $this;
	}

	public function getDiscountAmount()
	{
		return array_sum($this->discounts);
	}

	public function emptyDiscounts()
	{
		$this->discounts = array();
		return $this;
	}

	/**
	 * Check if the quote is intangible (contains only digital products and
	 * as a result no shipping applies.)
	 *
	 * @return boolean True if the quote is digital, false if not.
	 */
	public function isIntangible()
	{
		foreach ($this->items as $item) {
			if ($item->getType() == PT_PHYSICAL) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the number of items attached to this quote, optionally only of a
	 * certain product type.
	 *
	 * @param int $productType Optionally a PT_* constant to return # of that
	 *  type of product.
	 * @return int Number of items in the quote of the given type, or all.
	 */
	public function getNumItems($productType = null)
	{
		$total = 0;
		foreach ($this->items as $item) {
			if ($productType !== null && $item->getType() != $productType) {
				continue;
			}
			$total += $item->getQuantity();
		}

		return $total;
	}

	/**
	 * Get the number of physical items attached to this quote.
	 *
	 * @return int Number of physical products.
	 */
	public function getNumPhysicalItems()
	{
		return $this->getNumItems(PT_PHYSICAL);
	}

	public function addCoupon($coupon)
	{
		$this->coupons[$coupon['code']] = $coupon;
		return $this;
	}

	/**
	 * Re-apply all of the discounts (coupon codes and discount rules)
	 * that apply to this quote.
	 *
	 * @return ISC_QUOTE This quote instance.
	 */
	public function reapplyDiscounts()
	{
		$this->reapplyCoupons();
		$this->applyDiscountRules();
		$this->refreshFreeShippingEligibility();
		return $this;
	}

	/**
	 * Re-apply all previously applied coupons on this quote. To reflect
	 * the new discounts offered by items just added, or remove coupon
	 * codes that no longer apply due to quote changes.
	 *
	 * @param boolean $throwException Determine if this function would throw exception, defaulted to false
	 * @return ISC_QUOTE This quote instance.
	 */
	public function reapplyCoupons($throwException = false)
	{
		$coupons = $this->coupons;
		$couponCodes = array_keys($coupons);
		foreach ($couponCodes as $coupon) {
			$this->removeCoupon($coupon);
		}

		// Now go ahead and re-apply
		try {
			foreach ($couponCodes as $coupon) {
				$this->applyCoupon($coupon);
			}
		}
		catch(ISC_QUOTE_EXCEPTION $e) {
			if ($throwException) {
				throw $e;
			}
		}

		return $this;
	}

	public function getAppliedDiscountRules()
	{
		return $this->appliedDiscountRules;
	}

	public function applyDiscountRule($id, $type, $banners = array())
	{
		$this->appliedDiscountRules[$id] = array(
			'type' => $type,
			'banners' => $banners
		);
		return $this;
	}

	/** @var bool true if checking for store discounts on this quote are enabled, otherwise false */
	protected $discountsEnabled = true;

	/**
	* Sets whether or not this quote will attempt to apply current discount rules. Manually editing discounts, and discounts which already exist on the quote are unaffected.
	*
	* @param bool $enabled
	* @return ISC_QUOTE
	*/
	public function setDiscountsEnabled($enabled)
	{
		$this->discountsEnabled = (bool)$enabled;
		return $this;
	}

	/**
	* Gets whether or not this quote will attempt to apply current discount rules. Manually editing discounts, and discounts which already exist on the quote are unaffected. Discounts may still apply in these cases (such as when editing an existing, discounted order).
	*
	* @return bool true if discounts are enabled, otherwise false
	*/
	public function getDiscountsEnabled()
	{
		return $this->discountsEnabled;
	}

	/**
	 * Run through all available discount rules and attempt to apply them
	 * to the quote. Any discount rules that no longer apply will also be
	 * removed from the quote.
	 *
	 * @return ISC_QUOTE This quote instance.
	 */
	public function applyDiscountRules()
	{
		if (!$this->getDiscountsEnabled()) {
			return $this;
		}

		require_once ISC_BASE_PATH.'/lib/rule.php';

		// Fetch any rules that have already been applied and reset them.
		if (!empty($this->appliedDiscountRules)) {
			$existingRules = getDiscountRulesById(array_keys($this->appliedDiscountRules));
			foreach ($existingRules as $rule) {
				$rule->resetState($this);
			}
		}

		// Reset applied discount rules
		$this->appliedDiscountRules = array();

		// Now, grab all of the rules that are available
		$rules = getRuleModuleInfo();
		$halt = false;
		foreach ($rules as $rule) {
			if (!$rule->enabled() || $halt) {
				$rule->haltReset($this);
				continue;
			}

			$ruleApplies = $rule->applyRule($this);

			// Discount rule does not apply
			if (!$ruleApplies) {
				continue;
			}

			$numUses = $rule->getUses();
			$banners = $rule->getBanners();
			$this->applyDiscountRule($rule->getDbId(), $numUses, $banners);

			if ($rule->checkHalt($this)) {
				$halt = true;
			}
		}
		return $this;
	}

	public function removeAllCoupons()
	{
		// Remove all coupon codes from the quote. Presently the software only supports
		// a single coupon code per quote.
		$couponCodes = array_keys($this->coupons);
		foreach ($couponCodes as $oldCoupon) {
			$this->removeCoupon($oldCoupon);
		}
	}

	public function fetchCoupon($code)
	{
		$query = "
			SELECT *
			FROM [|PREFIX|]coupons
			WHERE couponcode='".$GLOBALS['ISC_CLASS_DB']->quote($code)."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		$coupon = $GLOBALS['ISC_CLASS_DB']->fetch($result);

		return $coupon;
	}

	public function applyCoupon($coupon)
	{
		$this->removeAllCoupons();

		if(is_string($coupon) || is_numeric($coupon)) {
			// Look up the coupon code
			$code = trim($coupon);
			$coupon = $this->fetchCoupon($code);

			if (!$coupon || !$coupon['couponcode']) {
				throw new ISC_QUOTE_EXCEPTION(GetLang('InvalidCouponCode'), ISC_QUOTE_EXCEPTION::COUPON_INVALID);
			}

			// Check if the coupon actually applies to any of the items in the cart
			$appliesTo = array();
			$query = "
				SELECT valueid
				FROM [|PREFIX|]coupon_values
				WHERE couponid='".$coupon['couponid']."'
			";

			$result = $GLOBALS['ISC_CLASS_DB']->query($query);
			while ($value = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
				$appliesTo[] = $value['valueid'];
			}
		}
		else {
			$appliesTo = $coupon['appliesto'];
		}

		// Coupon code is disabled, it can't be applied
		if (isset($coupon['couponenabled']) && $coupon['couponenabled'] == 0) {
			throw new ISC_QUOTE_EXCEPTION(GetLang('InvalidCouponDisabled'), ISC_QUOTE_EXCEPTION::COUPON_DISABLED);
		}

		// If the coupon has expired, it can't be used
		if (isset($coupon['couponexpires']) && $coupon['couponexpires'] != 0) {
			// coupon expires at 23:59:59 of the day
			$expires = $coupon['couponexpires'] + 86399;

			if (isc_mktime() > $expires) {
				throw new ISC_QUOTE_EXCEPTION(
					sprintf(GetLang('InvalidCouponExpired'), date(GetConfig('DisplayDateFormat'), $coupon['couponexpires'])),
					ISC_QUOTE_EXCEPTION::COUPON_EXPIRED_TIME
				);
			}
		}

		// Has the coupon already reached it's maximum number of uses? It can't be used
		if (isset($coupon['couponmaxuses']) && $coupon['couponnumuses'] && $coupon['couponmaxuses'] != 0 && $coupon['couponnumuses'] >= $coupon['couponmaxuses']) {
			throw new ISC_QUOTE_EXCEPTION(GetLang('InvalidCouponExpiredUses'), ISC_QUOTE_EXCEPTION::COUPON_EXPIRED_USES);
			return false;
		}

		// check max use per customer info
		if (isset($coupon['couponmaxusespercus']) && (int)$coupon['couponmaxusespercus'] > 0) {
			if (getclass('ISC_COUPON')->isPerCustomerUsageLimitReached($coupon['couponid'], $coupon['couponmaxusespercus'])) {
				throw new ISC_QUOTE_EXCEPTION(getLang('InvalidCouponExpiredUses'), ISC_QUOTE_EXCEPTION::COUPON_EXPIRED_USES);
				return false;
			}
		}

		// Does the cart subtotal meet the minimum purchase amount required?
		$subtotal = $this->getBaseSubTotal();
		if (isset($coupon['couponminpurchase']) && $coupon['couponminpurchase'] > 0 && $subtotal < $coupon['couponminpurchase']) {
			$amountDiff = $coupon['couponminpurchase'] - $subtotal;
			$amountDiff = CurrencyConvertFormatPrice($amountDiff);
			$invalidCouponMesg = sprintf(GetLang('InvalidCouponMinPrice'), $amountDiff);
			throw new ISC_QUOTE_EXCEPTION($invalidCouponMesg, ISC_QUOTE_EXCEPTION::COUPON_MIN_PURCHASE);
		}

		if ($coupon['coupontype'] == 1) {
			$discountType = 'percent';
		}
		else {
			$discountType = 'fixed';
		}


		if (empty($appliesTo)) {
			return 0;
		}

		$totalCouponDiscount = 0;
		$applyOrderDiscount = false;
		$applyDiscountShipping = false;
		$applyFreeShipping = false;
		$shippingLocationUnapplied = false;
		$shippingMethodUnapplied = false;
		$appliesToQuote = false;
		foreach ($this->items as $item) {
			$applyToItem = false;
			if ($coupon['couponappliesto'] == 'products') {
				if (in_array($item->getProductId(), $appliesTo)) {
					$applyToItem = true;
				}
			}
			else if ($coupon['couponappliesto'] == 'categories') {
				if (in_array('0', $appliesTo)) {
					$applyToItem = true;
				}
				else {
					$categories = $item->getCategoryIds();
					foreach ($categories as $categoryId) {
						if (in_array($categoryId, $appliesTo)) {
							$applyToItem = true;
							break;
						}
					}
				}
			}

			// if the coupon restricted by location
			if (!empty ($coupon['location_restricted']) && $applyToItem) {
				// Checking address based on individual item's address
				$shippingAddress = $item->getAddress();
				try {
					$applyToItem = $this->_isCouponValidByLocation($shippingAddress, (int)$coupon['couponid']);
				}
				catch(ISC_QUOTE_EXCEPTION $e) {
					throw $e;
				}
				if (!$applyToItem) {
					$shippingLocationUnapplied = true;
				}
			}

			// if the coupon restricted by shipping method
			if (!empty ($coupon['shipping_method_restricted']) && $applyToItem) {
				$shippingAddress = $item->getAddress();
				$applyToItem = $this->_isCouponValidByShippingMethod($shippingAddress, (int)$coupon['couponid']);
				if (!$applyToItem) {
					$shippingMethodUnapplied = true;
				}
			}

			// Coupon does not apply to this product. Continue.
			if ($applyToItem == false) {
				continue;
			}

			$appliesToQuote = true;

			// Percentage discount
			if ($coupon['coupontype'] == 1) {
				$discountAmount = $item->getDiscountedBaseTotal() * ($coupon['couponamount'] / 100);
				$discountAmount = round($discountAmount, getConfig('DecimalPlaces'));
			}
			// Discount the entire order - do this outside the item loop
			else if ($coupon['coupontype'] == 2) {
				$applyOrderDiscount = true;
				break;
			}
			// Discound on Shipping Coupon
			else if ($coupon['coupontype'] == 3) {
				$applyDiscountShipping = true;
				break;
			}
			// Freeshipping Coupon
			else if ($coupon['coupontype'] == 4) {
				$applyFreeShipping = true;
				break;
			}
			// Discount a fixed amount off each item
			else {
				$discountedBaseTotal = $item->getDiscountedBaseTotal();
				$discountAmount = $coupon['couponamount'] * $item->getQuantity();
				if ($discountAmount > $discountedBaseTotal) {
					$discountAmount = $discountedBaseTotal;
				}
			}
			// Add the coupons in under 'coupon' as only one coupon is allowed per product
			$totalCouponDiscount += $discountAmount;
			$item->addDiscount('coupon', $discountAmount);
		}

		if ($applyOrderDiscount) {
			// If a coupon is applied to an entire order, it cancels out any other
			// already applied coupons
			$existingCoupons = $this->getAppliedCoupons();
			foreach ($existingCoupons as $existingCoupon) {
				$this->removeCoupon($existingCoupon['code']);
			}
			$totalCouponDiscount = $coupon['couponamount'];
			$runningTotal = $totalCouponDiscount;
			foreach ($this->items as $item) {
				$discountedBase = $item->getDiscountedBaseTotal();
				if($discountedBase - $runningTotal < 0) {
					$item->addDiscount('total-coupon', $discountedBase);
					$runningTotal -= $discountedBase;
				}
				else {
					$item->addDiscount('total-coupon', $runningTotal);
					$runningTotal -= $runningTotal;
				}

				if($runningTotal <= 0) {
					break;
				}
			}
		}

		if ($totalCouponDiscount > $subtotal) {
			$totalCouponDiscount = $subtotal;
		}

		if ($applyDiscountShipping) {
			$discountAmount = 0;
			$shippingAddresses = $this->getShippingAddresses();
			if (!empty ($shippingAddresses)) {
				$discountRemaining = $coupon['couponamount'];

				// do check if each address has the ability to get the discount.
				foreach ($shippingAddresses as $shippingAddress) {
					$applyToAddress = true;
					if (!empty ($coupon['location_restricted'])) {
						try {
							$applyToAddress = $this->_isCouponValidByLocation($shippingAddress, (int)$coupon['couponid']);
						}
						catch(ISC_QUOTE_EXCEPTION $e) {
							throw $e;
						}
					}

					// if the coupon restricted by shipping methods
					if (!empty ($coupon['shipping_method_restricted'])) {
						$applyToAddress = $this->_isCouponValidByShippingMethod($shippingAddress, (int)$coupon['couponid']);
					}

					// apply the discount to the address
					if ($applyToAddress) {
						$shippingCost = $shippingAddress->getNonDiscountedShippingCost(true);
						$postDiscountRemaining = $shippingCost - $discountRemaining;
						if ($postDiscountRemaining < 0) {
							$discountRemaining -= $shippingCost;
							$discountAmount += $shippingCost;
						} else {
							$discountAmount += $discountRemaining;
							$discountRemaining -= $discountRemaining;
						}
						$shippingAddress->addDiscount('total-coupon', $discountAmount);
						if($discountRemaining <= 0) {
							break;
						}
					}
				}
			}
			$totalCouponDiscount = round($discountAmount, getConfig('DecimalPlaces'));
		}

		if ($applyFreeShipping) {
			$discountAmount = 0;
			$shippingAddresses = $this->getShippingAddresses();
			if (!empty ($shippingAddresses)) {
				$discountRemaining = $coupon['couponamount'];

				// do check if each address has the ability to get the discount.
				foreach ($shippingAddresses as $shippingAddress) {
					$applyToAddress = true;
					if (!empty ($coupon['location_restricted'])) {
						$applyToAddress = $this->_isCouponValidByLocation($shippingAddress, (int)$coupon['couponid']);
					}

					// if the coupon restricted by shipping methods
					if (!empty ($coupon['shipping_method_restricted'])) {
						$applyToAddress = $this->_isCouponValidByShippingMethod($shippingAddress, (int)$coupon['couponid']);
					}

					// apply the discount to the address
					if ($applyToAddress) {
						$discountAmount += round($shippingAddress->getNonDiscountedShippingCost(true), getConfig('DecimalPlaces'));
						$shippingAddress->addDiscount('total-coupon', $discountAmount);
					}
				}
			}
			$totalCouponDiscount = round($discountAmount, getConfig('DecimalPlaces'));
		}

		if(!$appliesToQuote) {
			if ($shippingLocationUnapplied) {
				throw new ISC_QUOTE_EXCEPTION(GetLang('InvalidCouponLocation'), ISC_QUOTE_EXCEPTION::COUPON_LOCATION_DOES_NOT_APPLY);
			}
			else if ($shippingMethodUnapplied) {
				throw new ISC_QUOTE_EXCEPTION(GetLang('InvalidCouponMethod'), ISC_QUOTE_EXCEPTION::COUPON_METHOD_DOES_NOT_APPLY);
			}
			else {
				throw new ISC_QUOTE_EXCEPTION(GetLang('InvalidCouponCode'), ISC_QUOTE_EXCEPTION::COUPON_DOES_NOT_APPLY);
			}
		}

		$coupon = array(
			'id' => $coupon['couponid'],
			'code' => $coupon['couponcode'],
			'name' => $coupon['couponname'],
			'discountType' => $coupon['coupontype'],
			'discountAmount' => $coupon['couponamount'],
			'expiresDate' => $coupon['couponexpires'],
			'totalDiscount' => round($totalCouponDiscount, getConfig('DecimalPlaces')),
		);

		$this->addCoupon($coupon);
		return $this;
	}

	/**
	 * This function will find the coupon based on restricted shipping methods
	 * by the selected shipping method.
	 *
	 * @param string $shippingAddress The shipping address
	 * @param int $couponId The coupon id
	 * @return boolean Return true if the selected shipping method applied to the coupon. Otherwise, return false.
	 */
	protected function _isCouponValidByShippingMethod($shippingAddress, $couponId)
	{
		static $validCouponMethodCache = array();
		$tempAddress = $shippingAddress->getAsArray();
		if ($shippingAddress->hasShippingMethod()) {
			$tempAddress['shippingmodule'] = $shippingAddress->getShippingModule();
		}

		// Do we have a cached result use that
		$cacheId = md5(strtolower(serialize($tempAddress)));

		if(isset($validCouponMethodCache[$cacheId])) {
			return $validCouponMethodCache[$cacheId];
		}

		if (!$tempAddress['shipcountryid'] &&
			!$tempAddress['shipstateid'] &&
			!$tempAddress['shipzip'] &&
			!$shippingAddress->hasShippingMethod()) {
			$validCouponMethodCache[$cacheId] = true;
			return true;
		} else {
			$shippingModuleId = $shippingAddress->getShippingModule();

			$query = "
				SELECT c.couponid
				FROM [|PREFIX|]coupon_shipping_methods csm
				INNER JOIN [|PREFIX|]coupons c ON (c.couponid=csm.coupon_id)
				WHERE csm.coupon_id = '". (int)$couponId ."'
				AND csm.module_id = '". $shippingModuleId ."'
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$coupon = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			if(isset($coupon['couponid'])) {
				$validCouponMethodCache[$cacheId] = true;
				return true;
			}
		}
		$validCouponMethodCache[$cacheId] = false;
		return false;
	}

	/**
	 * This function will find the coupon based on restricted location
	 * by the address.
	 *
	 * @param ISC_QUOTE_ADDRESS_SHIPPING $shippingAddress The shipping address
	 * @param int $couponId The coupon id
	 * @return boolean Return true if the shipping location applied to the coupon. Otherwise, return false.
	 */
	protected function _isCouponValidByLocation($shippingAddress, $couponId)
	{
		static $validCouponLocationCache = array();
		$address = $shippingAddress->getAsArray();

		// Do we have a cached result use that
		$cacheId = md5(strtolower(serialize($address)));

		if(isset($validCouponLocationCache[$cacheId])) {
			return $validCouponLocationCache[$cacheId];
		}

		if (!$address['shipzip']) {
			$validCouponLocationCache[$cacheId] = false;
			throw new ISC_QUOTE_EXCEPTION(GetLang('CouponLocationNotSpecified'), ISC_QUOTE_EXCEPTION::COUPON_LOCATION_DOES_NOT_SPECIFIED);
		} else {
			$address = $shippingAddress->getAsArray();
			// Zip Code restriction check
			if($address['shipzip']) {
				$couponExist = false;
				$query = "
					SELECT c.couponid, cl.value_id, cl.value
					FROM [|PREFIX|]coupon_locations cl
					INNER JOIN [|PREFIX|]coupons c ON (c.couponid=cl.coupon_id)
					WHERE c.couponenabled='1' AND
					cl.selected_type='zip' AND
					cl.coupon_id='".(int)$couponId."' AND
					cl.country_id='".(int)$address['shipcountryid']."' AND
					'".$GLOBALS['ISC_CLASS_DB']->Quote($address['shipzip'])."' REGEXP REPLACE(REPLACE(CONCAT('^', cl.value, '$'), '*', '.{1,}'), '?',  '.')
				";

				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				while($coupon = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					if($coupon['value'] == $address['shipzip']) {
						$couponExist = true;
						continue;
					}
					else {
						// Score the characters in the string
						$score = (substr_count($coupon['value'], '*')*10)+(substr_count($coupon['couponid'], '?'));

						// A lower score means a stronger match, so we use that zone ID
						if(!isset($lastScore) || $score < $lastScore) {
							$couponExist = true;
							$lastScore = $score;
						}
					}
				}
				if ($couponExist) {
					$validCouponLocationCache[$cacheId] = $couponExist;
					return $couponExist;
				}
			}

			// State & Country restriction check
			$query = "
				SELECT c.couponid
				FROM [|PREFIX|]coupon_locations cl
				INNER JOIN [|PREFIX|]coupons c ON (c.couponid=cl.coupon_id)
				WHERE c.couponenabled='1' AND
				cl.selected_type='state' AND
				cl.country_id='".(int)$address['shipcountryid']."' AND
				cl.coupon_id='".(int)$couponId."' AND
				(cl.value_id='".(int)$address['shipstateid']."' OR cl.value_id='0')
				ORDER BY cl.value_id DESC
				LIMIT 1
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$coupon = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			if(isset($coupon['couponid'])) {
				$validCouponLocationCache[$cacheId] = true;
				return true;
			}

			// Country level restriction check
			$query = "
				SELECT c.couponid
				FROM [|PREFIX|]coupon_locations cl
				INNER JOIN [|PREFIX|]coupons c ON (c.couponid=cl.coupon_id)
				WHERE c.couponenabled='1'
				AND cl.selected_type='country'
				AND cl.value_id='".(int)$address['shipcountryid']."'
				AND cl.coupon_id='".(int)$couponId."'
				LIMIT 1
			";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$coupon = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			if(isset($coupon['couponid'])) {
				$validCouponLocationCache[$cacheId] = true;
				return true;
			}
		}
		$validCouponLocationCache[$cacheId] = false;
		return false;
	}

	protected function applyCouponToItems($coupon)
	{
		return $applicableItems;
	}

	public function removeCoupon($code)
	{
		// Remove the coupon from the item
		foreach ($this->items as $item) {
			$item->addDiscount('coupon', 0);
			$item->addDiscount('total-coupon', 0);
		}

		// Remove the coupon from the shipping quote
		foreach ($this->addresses as $address) {
			if ($address->getType() == ISC_QUOTE_ADDRESS::TYPE_SHIPPING) {
				$address->addDiscount('total-coupon', 0);
			}
		}
		unset($this->coupons[$code]);
		return $this;
	}

	/**
	 * Check if the coupon already expired
	 *
	 * @param integer $couponExpires The integer that indicate the timestamp of the coupon
	 * @return boolean Return true if the coupon already expired.
	 */
	public function isCouponExpired($couponExpires)
	{
		// If the coupon has expired, it can't be used
		if ($couponExpires != 0) {
			// coupon expires at 23:59:59 of the day
			$expires = $couponExpires + 86399;

			if (isc_mktime() > $expires) {
				return true;
			}
		}
		return false;
	}

	public function removeCouponById($id)
	{
		foreach ($this->coupons as $coupon) {
			if ($coupon['id'] == $id) {
				return $this->removeCoupon($coupon['code']);
			}
		}

		return $this;
	}

	/**
	 * Return an array containing information of all of the coupon codes
	 * applied to this quote.
	 *
	 * @return array Array of coupon codes applied to the quote.
	 */
	public function getAppliedCoupons()
	{
		return $this->coupons;
	}

	/**
	 * Return the total discount given on the quote due to all applied
	 * coupon codes.
	 *
	 * @return float Total discount off the quote total from coupon codes.
	 */
	public function getCouponDiscount()
	{
		$discount = 0;
		$coupons = $this->getAppliedCoupons();
		foreach ($coupons as $coupon) {
			$discount += $coupon['totalDiscount'];
		}

		return $discount;
	}

	public function addGiftCertificate($certificate)
	{
		$this->giftCertificates[$certificate['code']] = $certificate;
		return $this;
	}

	public function fetchGiftCertificate($code)
	{
		$query = "
			SELECT *
			FROM [|PREFIX|]gift_certificates
			WHERE
				(giftcertstatus=2 OR giftcertstatus=4) AND
				giftcertcode='".$GLOBALS['ISC_CLASS_DB']->quote(trim($code))."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		$certificate = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		return $certificate;
	}

	/**
	 * Given a gift certificate code, apply it to the quote.
	 *
	 * @throws ISC_QUOTE_EXCEPTION when the gift certificate cannot be applied
	 * @param string $code Gift certificate code.
	 * @return ISC_QUOTE This quote instance.
	 */
	public function applyGiftCertificate($code)
	{
		// First check if we have a valid gift certificate
		$certificate = $this->fetchGiftCertificate($code);

		// Invalid gift certificate code was entered
		if (!$certificate['giftcertid'] || $certificate['giftcertbalance'] == 0) {
			throw new ISC_QUOTE_EXCEPTION(getLang('BadGiftCertificateInvalid'));
		}

		// This gift certificate has expired
		if ($certificate['giftcertstatus'] == 4 ||
			($certificate['giftcertexpirydate'] != 0 && time() >= $certificate['giftcertexpirydate'])) {
				if ($certificate['giftcertstatus'] != 4) {
					$updatedCertificate = array(
						'giftcertstatus' => 4
					);

					$GLOBALS['ISC_CLASS_DB']->updateQuery(
						'gift_certificates',
						$updatedCertificate,
						"giftcertid='".$GLOBALS['ISC_CLASS_DB']->quote($certificate['giftcertid'])."'"
					);
			}

			if ($certificate['giftcertexpirydate'] != 0) {
				throw new ISC_QUOTE_EXCEPTION(
					sprintf(getLang('BadGiftCertificateExpired'), cDate($certificate['giftcertexpirydate']))
				);
			}
			else {
				throw new ISC_QUOTE_EXCEPTION(getLang('BadGiftCertificateInvalid'));
			}
		}

		$certificate = array(
			'code'		=> $code,
			'id'		=> $certificate['giftcertid'],
			'amount'	=> $certificate['giftcertamount'],
			'balance'	=> $certificate['giftcertbalance'],
			'expiry'	=> $certificate['giftcertexpirydate']
		);
		return $this->addGiftCertificate($certificate);
	}

	/**
	 * Remove all gift certificates from this quote
	 *
	 * @return ISC_QUOTE This quote instance.
	 */
	public function removeAllGiftCertificates()
	{
		foreach($this->giftCertificates as $code => $cert)
			$this->removeGiftCertificate($code);

		return $this;
	}

	/**
	 * Given a gift certificate code, remove it from the quote.
	 *
	 * @param string $code Gift certificate code.
	 * @return ISC_QUOTE This quote instance.
	 */
	public function removeGiftCertificate($code)
	{
		unset($this->giftCertificates[$code]);
		return $this;
	}

	/**
	 * Given the ID of a gift certificate, (as in, the ID from the database)
	 * remove it from the quote.
	 *
	 * @param int $id Gift certificate ID.
	 * @return ISC_QUOTE This quote instance.
	 */
	public function removeGiftCertificateById($id)
	{
		foreach ($this->giftCertificates as $certificate) {
			if ($certificate['id'] == $id) {
				return $this->removeGiftCertificate($certificate['code']);
			}
		}

		return $this;
	}

	public function getAppliedGiftCertificates()
	{
		uasort($this->giftCertificates, 'giftCertificateSort');
		$giftCertificates = array();

		$runningTotal = $this->getGrandTotalWithoutGiftCertificates();
		if ($runningTotal == 0) {
			return $giftCertificates;
		}
		foreach ($this->giftCertificates as $certificate) {
			if ($certificate['balance'] > $runningTotal) {
				$remaining = $certificate['balance'] - $runningTotal;
				$used = $certificate['balance'] - $remaining;
			}
			else {
				$used = $certificate['balance'];
				$remaining = 0;
			}

			$certificate['used'] = $used;
			$certificate['remaining'] = $remaining;
			$runningTotal -= $certificate['balance'];
			$giftCertificates[$certificate['code']] = $certificate;
			if ($runningTotal <= 0) {
				break;
			}
		}

		return $giftCertificates;
	}

	public function getGiftCertificateTotal()
	{
		$giftCertificates = $this->getAppliedGiftCertificates();
		if (empty($giftCertificates)) {
			return 0;
		}

		$giftCertificateTotal = 0;
		$runningTotal = $this->getGrandTotalWithoutGiftCertificates();
		foreach ($giftCertificates as $certificate) {
			if ($certificate['balance'] > $runningTotal) {
				$remaining = $certificate['balance'] - $runningTotal;
				$used = $certificate['balance'] - $remaining;
			}
			else {
				$used = $certificate['balance'];
				$remaining = 0;
			}

			$runningTotal -= $certificate['balance'];
			$giftCertificateTotal += $used;
			if ($runningTotal <= 0) {
				break;
			}
		}

		return $giftCertificateTotal;
	}

	public function rebuildItemPrices()
	{
		foreach ($this->items as $item) {
			$item->invalidateCachedTotals();
		}
		return $this;
	}

	public function getIsSplitShipping()
	{
		return $this->isSplitShipping;
	}

	public function setIsSplitShipping($isSplitShipping)
	{
		if ($isSplitShipping == false && $this->isSplitShipping == true) {
			$this->removeAllShippingAddresses();
			$this->flattenQuoteItems();
		}

		$this->isSplitShipping = $isSplitShipping;
		return $this;
	}

	/**
	 * Flatten down all of the items in this quote by finding all items
	 * with the same configuration, and then removing all of that particular
	 * item with the exception of one, and setting its quantity to the
	 * combined total quantity.
	 *
	 * @return ISC_QUOTE This quote instance.
	 */
	public function flattenQuoteItems()
	{
		$existingItems = array();
		foreach ($this->items as $item) {
			$itemHash = $item->getHash();
			$itemId = $item->getId();
			if (!isset($existingItems[$itemHash])) {
				$existingItems[$itemHash] = $item;
				continue;
			}

			$quantity = $item->getQuantity();
			$this->removeItem($itemId);
			$existingItems[$itemHash]->setQuantity(
				$existingItems[$itemHash]->getQuantity() +
				$quantity
			);
		}

		return $this;
	}

	public function isDigital()
	{
		foreach ($this->items as $item) {
			if ($item->getType() != PT_DIGITAL && $item->getType() != PT_GIFTCERTIFICATE) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if the quote can be finalized (ie, an order created).
	 *
	 * @return boolean True if the quote has all required details. False if not.
	 */
	public function canBeFinalized()
	{
		if (empty($this->items) || empty($this->addresses)) {
			return false;
		}

		// Missing billing details
		if (!$this->getBillingAddress()->isComplete()) {
			return false;
		}

		$shippingAddresses = $this->getShippingAddresses();
		if (empty($shippingAddresses) && !$this->isDigital()) {
			return false;
		}

		foreach ($shippingAddresses as $address) {
			if (!$address->isComplete()) {
				return false;
			}
		}

		// Final stock level check?

		return true;
	}

	/**
	 * Given the ID of a quote item, check if it is already in this quote or
	 * not.
	 *
	 * @param string $id ID to check.
	 * @param object $not Don't check this item.
	 * @return boolean True if the item is already in the quote. False if not.
	 */
	public function hasItem($id, $not = null)
	{
		foreach ($this->items as $item) {
			if ($item !== $not && $item->getId() == $id) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get an array of all of the unique product IDs for the products in this
	 * quote.
	 *
	 * @return array Array of product IDs.
	 */
	public function getUniqueProductIds()
	{
		$productIds = array();
		foreach ($this->items as $item) {
			$productIds[$item->getProductId()] = $item->getProductId();
		}
		return $productIds;
	}

	/**
	 * Get the ID of the customer group that this quote should use for calculating
	 * pricing.
	 *
	 * @return int Customer group ID.
	 */
	public function getCustomerGroupId()
	{
		return $this->customerGroupId;
	}

	/**
	 * Set the ID of the customer group that this quote should use to determine
	 * product pricing.
	 *
	 * @param int $id Customer group ID.
	 * @return ISC_QUOTE This quote instance.
	 */
	public function setCustomerGroupId($id)
	{
		// Group ID has changed. Recalculate prices.
		if ($id != $this->customerGroupId) {
			$recalculate = true;
		}

		$this->customerGroupId = $id;

		if (!empty($recalculate)) {
			$this->rebuildItemPrices();
		}

		return $this;
	}

	/**
	* @param string $value
	* @return ISC_QUOTE
	*/
	public function setCustomerMessage($value)
	{
		$this->customerMessage = $value;
		return $this;
	}

	/** @return string */
	public function getCustomerMessage()
	{
		return $this->customerMessage;
	}

	/**
	* @param string $value
	* @return ISC_QUOTE
	*/
	public function setStaffNotes($value)
	{
		$this->staffNotes = $value;
		return $this;
	}

	/** @return string */
	public function getStaffNotes()
	{
		return $this->staffNotes;
	}

	/**
	* @param int $value one of ORDER_STATUS_* constants
	* @return ISC_QUOTE
	*/
	public function setOrderStatus($value)
	{
		$this->orderStatus = (int)$value;
		return $this;
	}

	/** @return string */
	public function getOrderStatus()
	{
		return $this->orderStatus;
	}

	/**
	* @param int $value
	* @return ISC_QUOTE
	*/
	public function setOrderId($value)
	{
		$this->orderId = (int)$value;
		return $this;
	}

	/** @return int */
	public function getOrderId()
	{
		return $this->orderId;
	}

	/**
	 * Invalidate any cached/pre-calculated totals on this quote.
	 *
	 * @return ISC_QUOTE This instance of ISC_QUOTE.
	 */
	public function invalidateCachedTotals()
	{
		$this->cachedTotals = array();
		return $this;
	}

	/**
	* Creates a new ISC_QUOTE_ITEM instance for this ISC_QUOTE and returns it. Note: the item is not added to the quote, you must still call ISC_QUOTE->addItem
	*
	* @return ISC_QUOTE_ITEM
	*/
	public function createItem()
	{
		$item = new ISC_QUOTE_ITEM;
		$item->setQuote($this);
		return $item;
	}

	/**
	* Creates a new ISC_QUOTE_ADDRESS_SHIPPING instance for this ISC_QUOTE and returns it.
	*
	* @param string $id specific id to create this address with otherwise leave it to random id generator
	* @return ISC_QUOTE_ADDRESS_SHIPPING
	*/
	public function createShippingAddress($id = null)
	{
		$address = new ISC_QUOTE_ADDRESS_SHIPPING;
		if ($id !== null) {
			$address->setId($id);
		}
		$address->setQuote($this);
		$this->addShippingAddress($address);
		return $address;
	}

	/**
	* Returns a count of all unique items in this order, where a unique item is defined based on configuration and shipping destinations.
	*
	* @return int
	*/
	public function getItemCount()
	{
		return count($this->items);
	}

	/**
	* Move items in this quote from their current address to the specified address.
	*
	* @param ISC_QUOTE_ADDRESS $address
	* @param array $items as an array of quote item id => quantity or use null as quantity to move all
	* @return ISC_QUOTE
	*/
	public function moveItems(ISC_QUOTE_ADDRESS $address, $items)
	{
		foreach ($items as $item => $quantity) {
			$item = $this->getItemById($item);
			if ($item) {
				$item->moveToAddress($address, (int)$quantity);
			}
		}
		return $this;
	}

	public function __sleep()
	{
		$dontSave = array(
			'cachedTotals'
		);

		$vars = array_keys(get_object_vars($this));
		$vars = array_diff($vars, $dontSave);
		return $vars;
	}

	/**
	 * This function refresh the content of the cart against the free shipping rules.
	 *
	 * @return ISC_QUOTE Return an object of ISC QUOTE
	 */
	public function refreshFreeShippingEligibility()
	{
		$this->removeEligibleFreeShippingInfo();
		if (!$this->getDiscountsEnabled()) {
			return $this;
		}

		require_once ISC_BASE_PATH.'/lib/rule.php';

		$rules = array_merge(getRuleModuleInfo('buyxgetfreeshipping'), getRuleModuleInfo('freeshippingwhenoverx'));
		$halt = false;
		foreach ($rules as $rule) {
			if (!$rule->enabled()) {
				continue;
			}

			// Reset the free shipping eligibility message for current rule
			$rule->resetFreeShippingEligibility();
			if($rule->checkFreeShippingEligibility($this)) {
			$keyName = isc_strtolower(get_class($rule));
				if (count($rule->getFreeShippingEligibilityData())) {
					$tempData = $rule->getFreeShippingEligibilityData();
					foreach ($tempData['location'] as $location) {
						if ($tempData['name'] == 'BUYXGETFREESHIPPING') {
							$this->eligibleFreeShippingInfo[$location][] = array(
								'productId' => $tempData['productId'],
								'message' => $tempData['message'],
								'name' => $tempData['name'],
							);
						} else {
							$this->eligibleFreeShippingInfo[$location][] = array(
								'message' => $tempData['message'],
								'name' => $tempData['name'],
							);
						}
					}
				}
			}
		}
		return $this;
	}

	/**
	 * This function get the information of the eligible free shipping.
	 *
	 * @return array Return array that contains free shipping message details.
	 */
	public function getEligibleFreeShippingInfo()
	{
		return $this->eligibleFreeShippingInfo;
	}

	/**
	 * This function reset the information of the eligible free shipping.
	 */
	public function removeEligibleFreeShippingInfo()
	{
		$this->eligibleFreeShippingInfo = array();
	}

}
