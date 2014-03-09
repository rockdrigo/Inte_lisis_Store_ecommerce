<?php
class ISC_ADMIN_EBAY_ITEMS {

	/**
	 * @var int The product Id that associates with the eBay item
	 */
	private $_productId;

	/**
	 * @var int The ebay item id that returned from eBay after item listed
	 */
	private $_ebayItemId;

	/**
	 * @var string The title of the eBay item
	 */
	private $_title;

	/**
	 * @var int The start time timestamp of eBay item listing
	 */
	private $_startTime;

	/**
	 * @var int The end time timestamp of eBay item listing
	 */
	private $_endTime;

	/**
	 * @var int The timestamp of the item listed on eBay
	 */
	private $_dateTimeListed;

	/**
	 * @var string The listing type. One of these: Chinese or FixedPriceItem
	 */
	private $_listingType;

	/**
	 * @var string The listing status. One of these: Pending, Active, Sold, Unsold or Won
	 */
	private $_listingStatus;

	/**
	 * @var string The currency of eBay item current price
	 */
	private $_currentPriceCurrency;

	/**
	 * @var float eBay item current price
	 */
	private $_currentPrice;

	/**
	 * @var string The currency of eBay item buy it now price
	 */
	private $_buyItNowPriceCurrency;

	/**
	 * @var float eBay item buy it now price
	 */
	private $_buyItNowPrice;

	/**
	 * @var int The site id of where the eBay item listed
	 */
	private $_siteId;

	/**
	 * @var string The link to the item, this is set to blank initially as item's status is pending
	 */
	private $_ebayItemLink;

	/**
	 * @var int The remaining of the eBay item quantity
	 */
	private $_quantityRemaining;

	/**
	 * @var int Number of bidding on the eBay item
	 */
	private $_bidCount;

	public function  __construct($ebayItemId)
	{
		$query = "SELECT * FROM [|PREFIX|]ebay_items "
		. "WHERE ebay_item_id = '".$ebayItemId."'"
		;
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!($row = $GLOBALS['ISC_CLASS_DB']->Fetch($res))) {
			throw new Exception('Ebay Item with ID: ' . $ebayItemId . ' not found.');
		}

		$this->_productId = $row['product_id'];
		$this->_ebayItemId = $row['ebay_item_id'];
		$this->_title = $row['title'];
		$this->_startTime = $row['start_time'];
		$this->_endTime = $row['end_time'];
		$this->_dateTimeListed = $row['datetime_listed'];
		$this->_listingType = $row['listing_type'];
		$this->_listingStatus = $row['listing_status'];
		$this->_currentPriceCurrency = $row['current_price_currency'];
		$this->_currentPrice = $row['current_price'];
		$this->_buyItNowPriceCurrency = $row['buyitnow_price_currency'];
		$this->_buyItNowPrice = $row['buyitnow_price'];
		$this->_siteId = $row['site_id'];
		$this->_ebayItemLink = $row['ebay_item_link'];
		$this->_quantityRemaining = $row['quantity_remaining'];
		$this->_bidCount = $row['bid_count'];

	}

	/**
	 * Get the product id that associates with the ebay item
	 *
	 * @return int Return the product Id
	 */
	public function getProductId()
	{
		return $this->_productId;
	}

	/**
	 * Get the ebay item id that returned from eBay after item listed
	 *
	 * @return int return the eBay Item Id
	 */
	public function getEbayItemId()
	{
		return $this->_ebayItemId;
	}

	/**
	 * Get the title of the eBay item
	 *
	 * @return string Return the eBay item title
	 */
	public function getTitle()
	{
		return $this->_title;
	}

	/**
	 * Get the start time timestamp of eBay item listing
	 *
	 * @return int Return the timestamp of item start date time
	 */
	public function getStartTime()
	{
		return $this->_startTime;
	}

	/**
	 * Get the end time timestamp of eBay item listing
	 *
	 * @return int Return the timestamp of item end date time
	 */
	public function getEndTime()
	{
		return $this->_endTime;
	}

	/**
	 * Get the timestamp of the item listed on eBay
	 *
	 * @return int Return the timestamp of item listed
	 */
	public function getDateTimeListed()
	{
		return $this->_dateTimeListed;
	}

	/**
	 * Get the listing type. One of these: Chinese or FixedPriceItem
	 *
	 * @return string Return the listing type
	 */
	public function getListingType()
	{
		return $this->_listingType;
	}

	/**
	 * Get the listing status. One of these: Pending, Active, Sold, Unsold or Won
	 *
	 * @return string Return the listing status
	 */
	public function getListingStatus()
	{
		return $this->_listingStatus;
	}

	/**
	 * Get the currency of eBay item current price
	 *
	 * @return string Return the current price currency
	 */
	public function getCurrentPriceCurrency()
	{
		return $this->_currentPriceCurrency;
	}

	/**
	 * Get the eBay item current price
	 *
	 * @return float Return the current price
	 */
	public function getCurrentPrice()
	{
		return $this->_currentPrice;
	}

	/**
	 * Get the currency of eBay item buy it now price
	 *
	 * @return string Return the buy it now currency
	 */
	public function getBuyItNowPriceCurrency()
	{
		return $this->_buyItNowPriceCurrency;
	}

	/**
	 * Get the eBay item buy it now price
	 *
	 * @return float Return the buy it now price
	 */
	public function getBuyItNowPrice()
	{
		return $this->_buyItNowPrice;
	}

	/**
	 * Get the site id of where the eBay item listed
	 *
	 * @return int Return the Site Id
	 */
	public function getSiteId()
	{
		return $this->_siteId;
	}

	/**
	 * Get the link to the item
	 *
	 * @return string Return the link to the item on eBay
	 */
	public function getEbayItemLink()
	{
		return $this->_ebayItemLink;
	}

	/**
	 * Get the remaining of the eBay item quantity
	 *
	 * @return int Return the remaining quantity
	 */
	public function getQuantityRemaining()
	{
		return $this->_quantityRemaining;
	}

	/**
	 * Get the number of bidding on the eBay item
	 *
	 * @return int Return the bid count
	 */
	public function getBidCount()
	{
		return $this->_bidCount;
	}
}
