<?php
class ISC_QUOTE_ADDRESS
{
	const TYPE_BILLING = 'billing';
	const TYPE_SHIPPING = 'shipping';

	/** @var string the address id used for 'unallocated' items */
	const ID_UNALLOCATED = 'unallocated';

	/** @var string the address id used when generating a shipping quote for a not-yet-allocated batch of items */
	const ID_TEMP_SHIPPING_QUOTES = 'tmpshippingquotes';

	protected $handlingCost;

	/**
	 * @var ISC_QUOTE Quote instance this address belongs to.
	 */
	protected $quote;

	/**
	 * @var string One of the ISC_QUOTE_ADDRESS::TYPE_* constants.
	 */
	protected $type;

	/**
	 * @var string Unique identifier for this quote address.
	 */
	protected $id;

	/**
	 * @var string Customer's first name.
	 */
	protected $firstName;

	/**
	 * @var string Customer's last name.
	 */
	protected $lastName;

	/**
	 * @var string Customer's company name.
	 */
	protected $company;

	/**
	 * @var string Customer's phone number.
	 */
	protected $phone;

	/**
	 * @var string Customer's email address.
	 */
	protected $email;

	/**
	 * @var string Customer's first address line.
	 */
	protected $address1;

	/**
	 * @var string Customer's second address line.
	 */
	protected $address2;

	/**
	 * @var string Customer's zip code.
	 */
	protected $zip;

	/**
	 * @var string Customer's city.
	 */
	protected $city;

	/**
	 * @var array Array containing ID, name and ISO of customer's state.
	 */
	protected $state = array(
		'id'	=> 0,
		'name'	=> '',
		'iso2'	=> ''
	);

	/**
	 * @var array Array containing ID, name and ISO of customer's country.
	 */
	protected $country = array(
		'id'	=> 0,
		'name'	=> '',
		'iso2'	=> ''
	);

	/**
	 * @var array In-memory array of cached/pre-calculated totals.
	 */
	protected $cachedTotals = array();

	/**
	 * @var array Key/value array containing any custom fields for this address.
	 */
	protected $customFields = array();

	/**
	 * @var null|boolean Set to true to save this address in the customer's
	 * address book.
	 */
	protected $saveAddress = null;

	/**
	 * @var int ID of this address in the customer address book table.
	 */
	protected $customerAddressId = 0;

	public function getType()
	{
		return $this->type;
	}

	public function setType($type)
	{
		$this->type = $type;
		$this->invalidateCachedTotals();
		return $this;
	}

	public function setQuote(ISC_QUOTE $quote)
	{
		$this->quote = $quote;
		return $this;
	}

	public function getQuote()
	{
		return $this->quote;
	}

	public function setId($id)
	{
		$oldId = $this->id;
		$this->id = $id;

		// Update all items to have this address ID
		if($oldId) {
			$items = $this->quote->getItems();
			foreach($items as $item) {
				if($item->getAddressId(false) == $oldId) {
					$item->setAddressId($id);
				}
			}
		}

		return $this;
	}

	public function generateId()
	{
		// do not allow all-number ids to be generated for addresses, since a numeric id is assumed to exist in the db already when ISC_ENTITY_ORDER is editing an order
		do {
			$id = uniqid();
		}
		while (is_numeric($id) || $this->getQuote()->hasAddress($id));
		$this->setId($id);
		return $this;
	}

	public function getId()
	{
		if (!$this->id) {
			$this->generateId();
		}

		return $this->id;
	}

	public function getItems()
	{
		$items = array();
		$digitalTypes = array(
			PT_DIGITAL,
			PT_GIFTCERTIFICATE
		);

		// This is a billing address, return all digital items in the
		// quote.
		$quoteItems = $this->getQuote()->getItems();
		if ($this->getType() == self::TYPE_BILLING) {
			foreach ($quoteItems as $item) {
				if (in_array($item->getType(), $digitalTypes)) {
					$items[] = $item;
				}

			}
			return $items;
		}
		// Not split shipping, return all items if this is the primary
		// shipping address.
		if (!$this->getQuote()->getIsSplitShipping() &&
			$this->getQuote()->getShippingAddress()->getId() == $this->getId()) {
				return array_merge($this->getQuote()->getItems(PT_PHYSICAL), $this->getQuote()->getItems(PT_VIRTUAL));
		}

		// Shipping address, return all shippable items
		foreach ($quoteItems as $item) {
			if ($item->getAddressId() == $this->getId()) {
				$items[] = $item;
			}
		}
		return $items;
	}

	/**
	* Finds and returns an item with the given hash on this address only.
	*
	* @param string $hash
	* @return ISC_QUOTE_ITEM or false if not found on this address
	*/
	public function getItemByHash($hash)
	{
		foreach ($this->getItems() as /** @var ISC_QUOTE_ITEM */$item) {
			if ($item->getHash() == $hash) {
				return $item;
			}
		}
		return false;
	}

	/**
	* Returns the number of unique items being shipped to this address.
	*
	* @return int
	*/
	public function getItemCount()
	{
		return count($this->getItems());
	}

	/**
	 * Get the total number of items (based on the quantity of each item)
	 * that are destined for this address.
	 *
	 * @return int Total number of items.
	 */
	public function getNumItems()
	{
		$total = 0;
		$items = $this->getItems();
		foreach ($items as $item) {
			$total += $item->getQuantity();
		}

		return $total;
	}

	public function setHandlingCost($cost)
	{
		$this->handlingCost = $cost;
		$this->invalidateCachedTotals();
		return $this;
	}

	public function getBaseHandlingCost()
	{
		return $this->handlingCost;
	}

	public function getTaxSummary()
	{
		$taxClassPrices = array();
		$taxZoneId = $this->getApplicableTaxZone();
		$items = $this->getItems();
		foreach ($items as $item) {
			if (!$item->isTaxable()) {
				continue;
			}

			$price = $item->getDiscountedBaseTotal();
			$price = getClass('ISC_TAX')->getPrice($price,
				$item->getTaxClassId(),
				false, 0, null, false);
			$taxClassPrices[$item->getTaxClassId()][] = $price;
		}

		if ($this instanceof ISC_QUOTE_ADDRESS_SHIPPING) {
			$shippingCost = $this->getShippingCost(false);
			if ($shippingCost > 0) {
				$taxClassPrices[getConfig('taxShippingTaxClass')][] = $shippingCost;
			}
		}

		$handlingCost = $this->getHandlingCost();
		if ($handlingCost > 0) {
			$taxClassPrices[getConfig('taxShippingTaxClass')][] = $handlingCost;
		}

		$wrappingCost = $this->getGiftWrappingCost(false);
		if ($wrappingCost > 0) {
			$taxClassPrices[getConfig('taxGiftWrappingTaxClass')][] = $wrappingCost;
		}
		$taxSummary = getClass('ISC_TAX')->getTaxSummaryForClassPrices($taxZoneId, $taxClassPrices);
		return $taxSummary;
	}

	public function getGiftWrappingCost($incTax = false)
	{
		$wrappingCost = 0;
		$items = $this->getItems();
		foreach ($items as $item) {
			$wrappingCost += $item->getBaseWrappingCost($incTax);
		}

		return getClass('ISC_TAX')->getPrice(
			$wrappingCost,
			getConfig('taxGiftWrappingTaxClass'),
			$incTax,
			$this->getApplicableTaxZone()
		);
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
			$this->getAsArray(),
			$this->getAsArray(),
			$this->getQuote()->getCustomerGroupId()
		);
	}

	public function getTaxTotal()
	{
		$total = 0;
		$taxSummary = $this->getTaxSummary();
		foreach ($taxSummary as $taxClass) {
			$total += $taxClass['effectiveTax'];
		}
		return $total;
	}

	public function invalidateCachedTotals()
	{
		$this->cachedTotals = array();
		$this->getQuote()->invalidateCachedTotals();
		return $this;
	}

	public function getHandlingCost($incTax = false)
	{
		return getClass('ISC_TAX')->getPrice(
			$this->getBaseHandlingCost(),
			getConfig('taxShippingTaxClass'),
			$incTax,
			$this->getApplicableTaxZone()
		);
	}

	public function getHandlingCostTax()
	{
		return $this->getHandlingCost(true) - $this->getHandlingCost(false);
	}

	/**
	* @param array $address
	* @return ISC_QUOTE_ADDRESS
	*/
	public function setAddressByArray(array $address)
	{
		if (isset($address['shipid'])) {
			$this->setCustomerAddressId($address['shipid']);
		}
		else {
			$this->setCustomerAddressId(0);
		}

		$defaults = array(
			'shipfirstname' => null,
			'shiplastname' => null,
			'shipcompany' => null,
			'shipaddress1' => null,
			'shipaddress2' => null,
			'shipcity' => null,
			'shipstate' => null,
			'shipcountry' => null,
			'shipphone' => null,
			'shipemail' => null,
			);

		$address = array_merge($defaults, $address);

		$this
			->setFirstName($address['shipfirstname'])
			->setLastName($address['shiplastname'])
			->setCompany($address['shipcompany'])
			->setAddress1($address['shipaddress1'])
			->setAddress2($address['shipaddress2'])
			->setCity($address['shipcity'])
			->setStateByName($address['shipstate'], $address['shipcountry'])
			->setZip($address['shipzip'])
			->setCountryByName($address['shipcountry'])
			->setPhone($address['shipphone']);

		if (isset($address['shipemail'])) {
			$this->setEmail($address['shipemail']);
		}
		return $this;
	}

	public function setAddressById($id)
	{
		$address = getClass('ISC_ACCOUNT')->getShippingAddress($id);
		if (!$address) {
			return false;
		}

		return $this->setAddressByArray($address);
	}

	public function getAsArray()
	{
		return array(
			'shipemail'			=> $this->getEmail(),
			'shipfirstname'		=> $this->getFirstName(),
			'shiplastname'		=> $this->getLastName(),
			'shipcompany'		=> $this->getCompany(),
			'shipaddress1'		=> $this->getAddress1(),
			'shipaddress2'		=> $this->getAddress2(),
			'shipcity'			=> $this->getCity(),
			'shipstate'			=> $this->getStateName(),
			'shipstateid'		=> $this->getStateId(),
			'shipzip'			=> $this->getZip(),
			'shipcountry'		=> $this->getCountryName(),
			'shipcountryid'		=> $this->getCountryId(),
			'shipphone'			=> $this->getPhone()
		);
	}

	public function setFirstName($firstName)
	{
		$this->firstName = $firstName;
		return $this;
	}

	public function setLastName($lastName)
	{
		$this->lastName = $lastName;
		return $this;
	}

	public function setCompany($company)
	{
		$this->company = $company;
		return $this;
	}

	public function setPhone($phone)
	{
		$this->phone = $phone;
		return $this;
	}

	public function setEmail($email)
	{
		$this->email = $email;
		return $this;
	}

	public function setAddress1($address1)
	{
		$this->address1 = $address1;
		return $this;
	}

	public function setAddress2($address2)
	{
		$this->address2 = $address2;
		return $this;
	}

	public function setZip($zip)
	{
		if ($this->zip == $zip) {
			return $this;
		}

		$this->zip = $zip;
		$this->invalidateCachedTotals();

		return $this;
	}

	public function setCity($city)
	{
		$this->city = $city;
		return $this;
	}

	public function setCountryByName($name)
	{
		$country = getCountryInfoByName($name);
		if ($country && $this->country['id'] != $country['countryid']) {
			$this->country = array(
				'id'	=> $country['countryid'],
				'name'	=> $country['countryname'],
				'iso2'	=> $country['countryiso2']
			);
			$this->invalidateCachedTotals();
		}
		return $this;
	}

	public function setCountryByIso2($iso2)
	{
		return $this->setCountryById(getCountryIdByISO2($iso2));
	}

	public function setCountryById($id)
	{
		$country = getCountryInfoById($id);
		if ($country && $this->country['id'] != $id) {
			$this->country = array(
				'id'	=> $id,
				'name'	=> $country['countryname'],
				'iso2'	=> $country['countryiso2']
			);
			$this->invalidateCachedTotals();
		}
		return $this;

	}

	public function setStateById($id)
	{
		$state = getStateInfoById($id);
		if ($state && $this->state['id'] != $id) {
			$this->state = array(
				'id'	=> $id,
				'name'	=> $state['statename'],
				'iso2'	=> $state['stateabbrv']
			);
			$this->invalidateCachedTotals();
		}

		return $this;
	}

	public function setStateByName($stateName, $countryName = '')
	{
		if (!$countryName) {
			$countryName = $this->getCountryName();
		}

		$countryId = getCountryByName($countryName);
		$state = getStateInfoByName($stateName);
		if (!$state) {
			$this->state = array(
				'id' => 0,
				'iso2' => '',
				'name' => $stateName
			);
			$this->invalidateCachedTotals();
		}
		if ($state['statecountry'] == $countryId && $state['stateid'] != $this->state['id']) {
			$this->state = array(
				'id' => $state['stateid'],
				'name' => $state['statename'],
				'iso2' => $state['stateabbrv']
			);
			$this->invalidateCachedTotals();
		}
		return $this;
	}

	/**
	 * Get the recipient's first name for this quote address.
	 *
	 * @return string First name.
	 */
	public function getFirstName()
	{
		return $this->firstName;
	}

	/**
	 * Get the recipient's last name for this quote address.
	 *
	 * @return string Last name.
	 */
	public function getLastName()
	{
		return $this->lastName;
	}

	/**
	 * Get the recipient's company name for this quote address.
	 *
	 * @return string Company name.
	 */
	public function getCompany()
	{
		return $this->company;
	}

	/**
	 * Get the recipient's email address for this quote address.
	 *
	 * @return string Email address.
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * Get the recipient's phone number for this quote address.
	 *
	 * @return string Phone number.
	 */
	public function getPhone()
	{
		return $this->phone;
	}

	/**
	 * Get the recipient's first address line for this quote address.
	 *
	 * @return string Address line 1.
	 */
	public function getAddress1()
	{
		return $this->address1;
	}

	/**
	 * Get the recipient's second address line for this quote address.
	 *
	 * @return string Address line 2.
	 */
	public function getAddress2()
	{
		return $this->address2;
	}

	/**
	 * Get the recipient's zip code for this quote address.
	 *
	 * @return string Zip code.
	 */
	public function getZip()
	{
		return $this->zip;
	}

	/**
	 * Get the city of this quote address.
	 *
	 * @return string City name.
	 */
	public function getCity()
	{
		return $this->city;
	}

	/**
	 * Get the state name of this quote address.
	 *
	 * @return string State name.
	 */
	public function getStateName()
	{
		return $this->state['name'];
	}

	public function getStateId()
	{
		return $this->state['id'];
	}

	public function getStateIso2()
	{
		return $this->state['iso2'];
	}

	public function getCountryName()
	{
		return $this->country['name'];
	}

	public function getCountryId()
	{
		return $this->country['id'];
	}

	public function getCountryIso2()
	{
		return $this->country['iso2'];
	}

	public function isComplete()
	{
		return $this->hasCompleteAddress();
	}

	public function setCustomField($id, $value)
	{
		$this->customFields[$id] = $value;
		return $this;
	}

	/**
	* @param array $customFields
	* @return ISC_QUOTE_ADDRESS
	*/
	public function setCustomFields($customFields)
	{
		foreach ($customFields as $id => $value) {
			$this->setCustomField($id, $value);
		}

		return $this;
	}

	/**
	* @param bool $saveAddress
	* @return ISC_QUOTE_ADDRESS
	*/
	public function setSaveAddress($saveAddress)
	{
		$this->saveAddress = $saveAddress;
		return $this;
	}

	public function getSaveAddress()
	{
		return $this->saveAddress;
	}

	public function getCustomFields()
	{
		return $this->customFields;
	}

	public function getCustomField($id)
	{
		if (!isset($this->customFields[$id])) {
			return false;
		}

		return $this->customFields[$id];
	}

	public function getCustomerAddressId()
	{
		return $this->customerAddressId;
	}

	public function setCustomerAddressId($customerAddressId)
	{
		$this->customerAddressId = $customerAddressId;
		return $this;
	}

	public function hasCompleteAddress()
	{
		$requiredFields = array(
			'firstName',
			'lastName',
			'address1',
			'zip',
			'city',
		);
		foreach ($requiredFields as $field) {
			if (!trim($this->$field)) {
				return false;
			}
		}

		if (empty($this->country['name'])) {
			return false;
		}

		return true;
	}

	/**
	* Returns the physical details of this address in a single line, ideal for display as shipping details.
	*
	* @return string
	*/
	public function getLine()
	{
		return implode(', ', array_filter(array(
			$this->getAddress1(),
			$this->getAddress2(),
			$this->getCity(),
			$this->getStateIso2(),
			$this->getZip(),
			$this->getCountryName(),
		)));
	}

	/**
	* Moves all items allocated to this address to another specified address
	*
	* @param ISC_QUOTE_ADDRESS $address
	* @return ISC_QUOTE
	*/
	public function moveAllItemsToAddress(ISC_QUOTE_ADDRESS $address)
	{
		$items = array();
		foreach ($this->getItems() as /** @var ISC_QUOTE_ITEM */$item) {
			$items[$item->getId()] = $item->getQuantity();
		}

		$this->getQuote()->moveItems($address, $items);
		return $this;
	}

	/**
	* Returns whether or not this address is the 'unallocated' address for a split-shipping order. For use primarily by the template system since Twig can't access PHP constants easily.
	*
	* @return bool
	*/
	public function getIsUnallocated()
	{
		return $this->id == self::ID_UNALLOCATED;
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
}
