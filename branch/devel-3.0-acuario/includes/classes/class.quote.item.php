<?php
class ISC_QUOTE_ITEM
{
	/**
	 * @var int The type of quote item this is (one of the PT_ constants)
	 */
	protected $type = 0;

	/**
	 * @var string The unique ID of this item in the quote.
	 */
	protected $id = 0;

	/**
	 * @var string String representation of the configuration of this item.
	 */
	protected $hash;

	/**
	 * @var array Array of product details fetched from the database for
	 * this quote item.
	 */
	protected $productData;

	/**
	 * @var int ID of the product this item belongs to.
	 */
	protected $productId = 0;

	/**
	 * @var int ID of the selected variation combination.
	 */
	protected $variationId = 0;

	/**
	 * @var array Key-value array containing the options for the selected variation.
	 */
	protected $variationOptions = array();

	/**
	 * @var int Quantity of this item in the quote.
	 */
	protected $quantity = 0;

	/**
	* @var int The quantity of the item in an existing order.
	*/
	protected $originalOrderQuantity = 0;

	/**
	 * @var string The name of the product, as it exists in the quote.
	 */
	protected $name = '';

	protected $basePrice = null;

	/**
	 * @var string SKU of this item.
	 */
	protected $sku = '';

	/**
	 * @var array Gift wrapping details for this item.
	 */
	protected $wrapping = array();

	/**
	 * @var array Configurable field values for this item.
	 */
	protected $configuration = array();

	/**
	 * @var array Configured event date for this item.
	 */
	protected $eventDate = array();

	/**
	 * @var string Event date name for this item.
	 */
	protected $eventName;

	/**
	 * @var string The quote item ID that this item is a child of.
	 * Primarily used for discount rules and free items.
	 */
	protected $parentId;

	/**
	 * @var ISC_QUOTE ISC_QUOTE instance this item belongs to.
	 */
	protected $quote;

	/**
	 * @var array Array of discounts and their amount applied to this item.
	 */
	protected $discounts = array();

	/**
	 * @var boolean Flag set to true when the price of this item is custom and
	 * should not be recalculated.
	 */
	protected $isCustomPrice = false;

	/**
	 * @var string ID of the ISC_QUOTE_ADDRESS object this item should be shipped
	 * to.
	 */
	protected $addressId = 0;

	/**
	 * @var array Array containing an in-memory cache of calculated totals for this item.
	 */
	protected $cachedTotals = array();

	/**
	 * @var boolean Flag indicating if this item has been attached to the quote or not.
	 */
	protected $inQuote = false;

	/**
	 * @var boolean Flag indicating if inventory checking is enabled or not.
	 */
	protected $inventoryChecking = true;

	protected $weight = 0;

	protected $fixedShippingCost = 0;
	
	/** @var string Product Clasifier */
	protected $clasifier = '';

	/**
	 * Set the flag that indicates if this item has been added to the quote or not.
	 * This allows methods such as setQuantity() etc to be run that need to apply
	 * discounts/check for this product to occur AFTER the product is added to
	 * the quote.
	 *
	 * @param boolean $value True to mark the item as being in the quote.
	 * @return ISC_QUOTE_ITEM This item instance.
	 */
	public function setInQuote($value)
	{
		$this->inQuote = (bool)$value;
		if ($value == true) {
			$this->handleCommitToQuote();
		}
		return $this;
	}

	/**
	 * Set the type for this item. Item type should be one of the PT_* constants.
	 *
	 * @param int Item type, as one of the PT* constants.
	 * @return ISC_QUOTE_ITEM This item instance.
	 */
	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * Apply a discount to this item with the given ID and amount.
	 * If the supplied amount is 0, the discount with the given ID is removed.
	 *
	 * @param string $id Unique identifier for the discount.
	 * @param float $amount Discount amount. If 0, removes discount.
	 * @return ISC_QUOTE_ITEM Current item instance.
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
	 * Get the total amount that this item should be discounted by.
	 *
	 * @return float Amount item should be discounted by.
	 */
	public function getDiscountAmount()
	{
		return array_sum($this->discounts);
	}

	/**
	* Return list of discounts applied to this quote item as an array
	*
	* @return array as id => value
	*/
	public function getDiscounts()
	{
		return $this->discounts;
	}

	/**
	 * Get the type of item (product type) for this item.
	 * Will return one of the PT_* constants that identifies this
	 * item.
	 *
	 * @return int PT_* constant representing the type of item.
	 */
	public function getType()
	{
		if (!$this->type) {
			$productData = $this->getProductData();
			if (!empty($productData)) {
				$this->type = $productData['prodtype'];
			}
		}
		return $this->type;
	}

	public function getProductData()
	{
		if (empty($this->productData) && $this->productId) {
			$this->getQuote()->loadProductData($this);
		}
		return $this->productData;
	}

	public function getGiftWrapping()
	{
		if (empty($this->wrapping)) {
			return false;
		}

		return $this->wrapping;
	}

	/**
	 * Set the gift wrapping options for this item.
	 *
	 * @param int $id Gift wrapping ID.
	 * @param float $price Price of gift wrapping. (per quantity)
	 * @param string $name Gift wrapping name.
	 * @param string $message Gift message.
	 * @return ISC_QUOTE_ITEM Current item instance.
	 */
	public function setGiftWrapping($id, $price, $name, $message = '')
	{
		$this->wrapping = array(
			'wrapid' => (int)$id,
			'wrapname' => $name,
			'wrapprice' => (double)$price,
			'wrapmessage' => $message
		);
		// @todo Reset the cached cart
		return $this;
	}

	public function __sleep()
	{
		$dontSave = array(
			'productData',
			'cachedTotals',
		);

		$vars = array_keys(get_object_vars($this));
		$vars = array_diff($vars, $dontSave);
		return $vars;
	}

	public function __clone()
	{
		// Force regeneration of the item ID
		$this->id = null;
		$this->inQuote = false;
		if ($this->inQuote) {
			$this->invalidateCachedTotals();
			if (!$this->getParentId()) {
				$this->getQuote()->reapplyDiscounts();
			}
		}
	}

	public function getGiftWrappingOptions()
	{
		$options = array();
		if (!$this->allowsGiftWrapping()) {
			return false;
		}

		$productData = $this->getProductData();
		return explode(',', $productData['prodwrapoptions']);
	}

	public function applyGiftWrapping($wrapMethod = 'same', $wrappingOptions = array(), $wrappingMessages = array())
	{
		// Gift wrapping is not available for this product
		if (!$this->allowsGiftWrapping()) {
			throw new ISC_QUOTE_EXCEPTION(getLang('GiftWrappingNotApplied'));
		}

		$productData = $this->getProductData();

		// If we have a quantity of one in the cart or selected "all the same" it's quite easy to do this
		if ($this->getQuantity() == 1 || $wrapMethod == 'same') {
			// Don't wrap this item
			if (!isset($wrappingOptions['all']) || !$wrappingOptions['all']) {
				return $this->removeGiftWrapping();
			}

			// Load the select method of gift wrapping
			$query = "
				SELECT *
				FROM [|PREFIX|]gift_wrapping
				WHERE wrapid='".(int)$wrappingOptions['all']."'
			";
			$result = $GLOBALS['ISC_CLASS_DB']->query($query);
			$wrapping = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			if (!isset($wrapping['wrapid']) || ($wrapping['wrapvisible'] == 0 &&
				!in_array($wrapping['wrapid'], explode(',', $productData['prodwrapoptions'])))) {
					throw new ISC_QUOTE_EXCEPTION(getLang('GiftWrappingNotApplied'));
			}

			// Apply it to the item in the cart
			$message = '';
			if ($wrapping['wrapallowcomments'] == 1 && isset($wrappingMessages['all'])) {
				$message = $wrappingMessages['all'];
			}
			$this->setGiftWrapping($wrapping['wrapid'], $wrapping['wrapprice'], $wrapping['wrapname'], $message);
		}
		else {
			// Otherwise, we've selected multiple types of gift wrapping, so we need to break each item up in the cart
			$wrappingIds = range(1, $this->getQuantity());
			$wrappedCount = 0;
			$newCartItems = array();
			foreach ($wrappingIds as $id) {
				if (!isset($wrappingOptions[$id]) || $wrappingOptions[$id] == '') {
					continue;
				}

				// Load the select method of gift wrapping
				$query = "
					SELECT *
					FROM [|PREFIX|]gift_wrapping
					WHERE wrapid='".(int)$wrappingOptions[$id]."'
				";
				$result = $GLOBALS['ISC_CLASS_DB']->query($query);
				$wrapping = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

				// Ignore any invalid selections
				if (!isset($wrapping['wrapid']) || ($wrapping['wrapvisible'] == 0 &&
					!in_array($wrapping['wrapid'], explode(',', $productData['prodwrapoptions'])))) {
						continue;
				}

				// Apply it to the item in the cart
				$message = '';
				if ($wrapping['wrapallowcomments'] == 1 && isset($wrappingMessages[$id])) {
					$message = $wrappingMessages[$id];
				}
				if($this->quantity > 1) {
					$newItem = clone $this;
					$newItem->setQuantity(1, false);

					// is this an item from an order?
					if ($this->getOriginalOrderQuantity() > 0) {
						// set the order quantity of new item and decrement this item
						$newItem->setOriginalOrderQuantity(1);
						$this->originalOrderQuantity -= 1;
					}

					$newItem->setGiftWrapping($wrapping['wrapid'], $wrapping['wrapprice'], $wrapping['wrapname'], $message);

					// Remove the quantity for this item
					$this->quantity -= 1;
					try {
						$this->getQuote()->addItem($newItem, false);
					} catch(ISC_QUOTE_EXCEPTION $e) {
						// Do nothing here.
					}
				}
				else {
					$this->setGiftWrapping($wrapping['wrapid'], $wrapping['wrapprice'], $wrapping['wrapname'], $message);
				}
			}
		}

		if ($this->inQuote) {
			$this->invalidateCachedTotals();
			if (!$this->getParentId()) {
				$this->getQuote()->reapplyDiscounts();
			}
		}
		$this->regenerateHash();

		return $this;
	}

	public function invalidateCachedTotals()
	{
		$this->cachedTotals = array();
		$this->productData = array();

		// Force recalculation of the base price for this item
		if (!$this->isCustomPrice) {
			$this->basePrice = null;
		}

		$this->getAddress()->invalidateCachedTotals();
		return $this;
	}

	public function setProductData($data)
	{
		$this->productData = $data;
		if ($data['prodname'] != $this->name) {
			$this->setName($data['prodname']);
		}

		$this->fixedShippingCost = $data['prodfixedshippingcost'];
		$this->weight = $data['prodweight'];
		if(!empty($data['variation']['vcweight'])) {
			$this->weight = calcProductVariationWeight(
				$this->weight,
				$data['variation']['vcweightdiff'],
				$data['variation']['vcweight']
			);
		}

		return $this;
	}

	/**
	 * Remove this item from its attached quote.
	 *
	 * @return ISC_QUOTE Instance of the quote object the product was attached
	 * to.
	 */
	public function remove()
	{
		return $this->getQuote()->removeItem($this->getId());
	}

	/**
	 * Get the quote that this item is attached to.
	 *
	 * @return ISC_QUOTE Instance of the ISC_QUOTE object.
	 */
	public function getQuote()
	{
		return $this->quote;
	}

	/**
	 * Set the quote instance that this item belongs to.
	 *
	 * @param ISC_QUOTE $quote Quote this item should belong to.
	 * @return ISC_QUOTE_ITEM This item instance.
	 */
	public function setQuote(ISC_QUOTE $quote)
	{
		$this->quote = $quote;
		return $this;
	}

	/**
	 * Remove any items that are marked as children of this item.
	 *
	 * @return ISC_QUOTE_ITEM This item instance.
	 */
	public function removeChildren()
	{
		$items = $this->getQuote()->getItems();
		foreach ($items as $item) {
			if ($item->getParentId() == $this->getId()) {
				$this->quote->removeItem($item->getId());
			}
		}

		return $this;
	}

	public function setParentId($parentId)
	{
		if (is_object($parentId)) {
			$parentId = $parent->getId();
		}

		$this->parentId = $parentId;
		$this->regenerateHash();
		return $this;
	}

	public function addChildItem(ISC_QUOTE_ITEM $item)
	{
		$this->quote->addItem($item);
		$this->children[] = $item->getId();
		return $this;
	}

	/**
	 * Return the product availability string for this product. This is just
	 * a field configured for the product and shown during checkout.
	 *
	 * @return string Availability message.
	 */
	public function getAvailability()
	{
		$productData = $this->getProductData();
		if (!empty($productData['prodavailability'])) {
			return $productData['prodavailability'];
		}

		return '';
	}

	/**
	 * Check if this item can be gift wrapped or not.
	 *
	 * @return boolean True if the item can be gift wrapped, false if not.
	 */
	public function allowsGiftWrapping()
	{
		$productData = $this->getProductData();
		if ($this->getType() != PT_PHYSICAL || empty($productData) ||
			$productData['prodwrapoptions'] == -1) {
				return false;
		}

		$wrapOptions = $GLOBALS['ISC_CLASS_DATA_STORE']->read('GiftWrapping');
		if (empty($wrapOptions)) {
			return false;
		}

		return true;
	}

	/**
	 * Remove the gift wrapping applied to this item.
	 *
	 * @return ISC_QUOTE_ITEM Current quote item.
	 */
	public function removeGiftWrapping()
	{
		$this->wrapping = array();
		return $this;
	}

	/**
	 * Check if this quote item is currently a pre-ordered item or not.
	 *
	 * @return boolean True if the item is a pre-order, false if not.
	 */
	public function isPreOrder()
	{
		$message = $this->getPreOrderMessage();
		if ($message === false) {
			return false;
		}

		return true;
	}

	/**
	 * Get the pre-order message associated with this item, if the product is
	 * setup to allow pre-orders.
	 *
	 * @return false|string False if no pre-order support, otherwise string
	 * containing the pre-order message.
	 */
	public function getPreOrderMessage()
	{
		$productData = $this->getProductData();
		if (empty($productData) || !$productData['prodpreorder']) {
			return false;
		}

		if ($productData['prodreleasedate']) {
			$message = $productData['prodpreordermessage'];
			if (!$message) {
				$message = getConfig('DefaultPreOrderMessage');
			}
			$message = str_replace('%%DATE%%', isc_date(GetConfig('DisplayDateFormat'), $productData['prodreleasedate']), $message);
		}
		else {
			$message = getLang('PreOrderProduct');
		}

		return $message;
	}

	/**
	 * Get the weight of an individual item in this quote line.
	 *
	 * @return float Weight of the item.
	 */
	public function getWeight()
	{
		return $this->weight;
	}

	/**
	 * Get the thumbnail image of this quote item.
	 *
	 * @return string Thumbnail image path.
	 */
	public function getThumbnail()
	{
		$productData = $this->getProductData();

		if (!empty($productData['variation']['vcimage']) && !empty($productData['variation']['vcimagethumb'])) {
			try {
				$image = new ISC_PRODUCT_IMAGE;
				$image->setSourceFilePath($productData['variation']['vcimage']);
				$image->setResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL, $productData['variation']['vcimagethumb']);
				return $image->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL, true, false);
			} catch (Exception $exception) {
				return '';
			}
		}

		try {
			$image = new ISC_PRODUCT_IMAGE();
			$image->populateFromDatabaseRow($productData);
			return $image->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL, true);
		} catch (Exception $exception) {
			return '';
		}
	}

	public function setVariation($variation)
	{
		$existingVariationId = $this->variationId;
		$existingVariationOptions = $this->variationOptions;

		$variationOptions = array();

		if (is_array($variation)) {
			$variation = getClass('ISC_PRODUCT')->getVariationCombination($variation);
		}

		// Load the variation
		if ($variation != 0) {
			$query = "
				SELECT *
				FROM [|PREFIX|]product_variation_combinations
				WHERE combinationid='".(int)$variation."'
			";
			$result = $GLOBALS['ISC_CLASS_DB']->query($query);
			$productVariation = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			if (empty($productVariation)) {
				throw new ISC_QUOTE_EXCEPTION('todo');
			}

			// Grab the combination for this variation
			$query = "
				SELECT *
				FROM [|PREFIX|]product_variation_options
				WHERE voptionid IN (".$productVariation['vcoptionids'].")
			";
			$result = $GLOBALS['ISC_CLASS_DB']->query($query);
			while ($variationOption = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$variationOptions[$variationOption['voname']] = $variationOption['vovalue'];
			}
		}

		// Make sure that if variations are required, a select was made
		$productData = $this->getProductData();
		if (isset($productData['prodoptionsrequired']) && $productData['prodoptionsrequired'] && $productData['prodvariationid'] && $variation == 0) {
			throw new ISC_QUOTE_EXCEPTION(getLang('ErrorNoVariationSelected'));
		}

		$this->setVariationOptions($variationOptions);
		$this->setVariationId($variation);

		// Attempt to set the quanitty to make sure this option is in stock
		try {
			// Force reload of product information with the variaiton attached.
			$this->productData = array();
			$this->setQuantity($this->getQuantity());
		}
		catch(ISC_QUOTE_EXCEPTION $e) {
			$this->productData = array();
			$this->setVariationOptions($existingVariationOptions);
			$this->setVariationId($existingVariationId);
			throw $e;
		}

		// Remove cached cart item info as well as other cached info?
		if ($this->inQuote) {
			$this->invalidateCachedTotals();
			if (!$this->getParentId()) {
				$this->getQuote()->reapplyDiscounts();
			}
		}
		$this->regenerateHash();

		return $this;
	}

	public function setVariationId($id)
	{
		$this->variationId = $id;
		return $this;
	}

	public function setVariationOptions(array $options)
	{
		$this->variationOptions = $options;
		return $this;
	}

	public function getInventoryTrackingMethod()
	{
		$productData = $this->getProductData();
		if (!empty($productData)) {
			return $productData['prodinvtrack'];
		}

		return 0;
	}

	public function getExistingQuantityForProduct()
	{
		$quantity = 0;
		$items = $this->getQuote()->getItems();
		foreach ($items as $item) {
			if ($item->getId() != $this->getId() && $item->getProductId() == $this->getProductId() &&
				$item->getVariationId() == $this->getVariationId()) {
					$quantity += $item->getQuantity();
			}
		}

		$quantity += $this->getQuantity();
		return $quantity;
	}

	public function getExisitingOrderQuantityForProduct()
	{
		$quantity = 0;
		$items = $this->getQuote()->getItems();
		foreach ($items as $item) {
			if ($item->getId() != $this->getId() && $item->getProductId() == $this->getProductId() &&
				$item->getVariationId() == $this->getVariationId()) {
					$quantity += $item->getOriginalOrderQuantity();
			}
		}

		$quantity += $this->getOriginalOrderQuantity();
		return $quantity;
	}

	/**
	 */
	public function getVariationId()
	{
		return (int)$this->variationId;
	}

	public function getVariationOptions()
	{
		return $this->variationOptions;
	}

	private function uploadConfigurableFile($fieldOptions, $file)
	{
		if (!is_array($fieldOptions) || !is_array($file) || empty($file['name'])) {
			return false;
		}

		$extension = GetFileExtension($file['name']);
		$allowedExtensions = array_map('trim', explode(',', $fieldOptions['fieldfiletype']));
		$allowedExtensions = array_map('isc_strtolower', $allowedExtensions);
		if ($fieldOptions['fieldfiletype'] && $fieldOptions['fieldfiletype'] != '*' && !in_array(isc_strtolower($extension), $allowedExtensions)) {
			throw new ISC_QUOTE_EXCEPTION(sprintf(GetLang('InvalidFileType'), isc_html_escape($fieldOptions['fieldfiletype'])));
		}

		// Check that the maximum size is not exceeded
		if ($fieldOptions['fieldfilesize'] > 0 && $file['size'] > $fieldOptions['fieldfilesize']*1024) {
			throw new ISC_QUOTE_EXCEPTION(sprintf(GetLang('InvalidFileSize'), $fieldOptions['fieldfilesize']));
		}

		// Store the uploaded files in our configured products directory
		$uploadDirectory = ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/configured_products_tmp/';
		if (empty($file['existingPath'])) {
			/**
			 * @todo Implement temporary sharing/storage location with automatic pruning.
			 */
			$fileName = $fieldOptions['productfieldid'].'_'.md5(uniqid()).'.'.$extension;
			if (!move_uploaded_file($file['tmp_name'], $uploadDirectory.$fileName)) {
				throw new ISC_QUOTE_EXCEPTION(getLang('CanNotUploadFile'));
			}
		}
		else {
			$fileName = basename($file['existingPath']);
		}

		// If we've just uploaded an image, we need to perform a bit of additional validation
		// to ensure it's not someone uploading bogus images.
		$imageExtensions = array(
			'gif',
			'png',
			'jpg',
			'jpeg',
			'jpe',
			'tiff',
			'bmp'
		);
		if (in_array($extension, $imageExtensions)) {
			// Check a list of known MIME types to establish the type of image we're uploading
			switch(isc_strtolower($file['type'])) {
				case 'image/gif':
					$imageType = IMAGETYPE_GIF;
					break;
				case 'image/jpg':
				case 'image/x-jpeg':
				case 'image/x-jpg':
				case 'image/jpeg':
				case 'image/pjpeg':
				case 'image/jpg':
					$imageType = IMAGETYPE_JPEG;
					break;
				case 'image/png':
				case 'image/x-png':
					$imageType = IMAGETYPE_PNG;
					break;
				case 'image/bmp':
					$imageType = IMAGETYPE_BMP;
					break;
				case 'image/tiff':
					$imageType = IMAGETYPE_TIFF_II;
					break;
				default:
					$imageType = 0;
			}

			$imageDimensions = getimagesize($uploadDirectory.$fileName);
			if (!is_array($imageDimensions) || $imageDimensions[2] != $imageType) {
				@unlink($uploadDirectory.$fileName);
				throw new ISC_QUOTE_EXCEPTION(GetLang('InvalidImageFile'));
				return false;
			}
		}

		return $fileName;
	}

	public function setConfiguration($configuration)
	{
		$this->configuration = $configuration;
		$this->regenerateHash();
		return $this;
	}

	public function deleteConfigurableFile($fieldId, $overrideValidation = false)
	{
		if(empty($this->configuration[$fieldId])) {
			return false;
		}

		$configurableFields = $this->getConfigurableOptions();
		$existingConfiguration = $this->configuration[$fieldId];

		// Only file type fields can be deleted
		if($existingConfiguration['type'] != 'file') {
			return false;
		}

		// If we're not overriding validation and this field is required, then
		// throw an error.
		if($overrideValidation == false && !empty($configurableFields[$fieldId]['fieldrequired'])) {
			return false;
		}

		// Otherwise, delete the file
		if(!empty($existingConfiguration['isExistingFile'])) {
			@unlink(
				ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').
				'/configured_products/'.$existingConfiguration['value']
			);
		}
		else {
			@unlink(
				ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').
				'/configured_products_tmp/'.$existingConfiguration['value']
			);
		}

		unset($this->configuration[$fieldId]);

		return true;
	}

	public function applyConfiguration($configuration)
	{
		$configurableFields = $this->getConfigurableOptions();

		// Get the current configuration
		$existingConfiguration = $this->configuration;

		$newConfiguration = array();

		foreach ($configurableFields as $fieldId => $field) {
			if ($field['fieldrequired'] && empty($configuration[$fieldId]) &&
				empty($existingConfiguration[$fieldId])) {
					throw new ISC_QUOTE_EXCEPTION(getLang('EnterRequiredField'));
			}

			if ($field['fieldtype'] == 'file') {
				// Field was empty, use existing value
				if (empty($configuration[$fieldId]['name']) && !empty($existingConfiguration[$fieldId])) {
					$newConfiguration[$fieldId] = $existingConfiguration[$fieldId];
					continue;
				}
				else if(empty($configuration[$fieldId]['name'])) {
					continue;
				}

				// If we can't save the uploaded file, throw back an error
				$uploadedFile = $this->uploadConfigurableFile($field, $configuration[$fieldId]);
				if ($uploadedFile === false) {
					throw new ISC_QUOTE_EXCEPTION(getLang('CanNotUploadFile'));
				}

				// If there was an existing file, delete it
				if (isset($existingConfiguration[$fieldId]['value']) &&
					$existingConfiguration[$fieldId]['value'] != $uploadedFile) {
						$this->deleteConfigurableFile($fieldId, true);
				}

				$newConfiguration[$fieldId] = array(
					'type'				=> $field['fieldtype'],
					'name'				=> $field['fieldname'],
					'fileType'			=> $configuration[$fieldId]['type'],
					'fileOriginalName'	=> $configuration[$fieldId]['name'],
					'value'				=> $uploadedFile,
				);
				continue;
			}
			elseif ($field['fieldtype'] == 'select') {
				$newConfiguration[$fieldId] = array(
					'type'				=> $field['fieldtype'],
					'name'				=> $field['fieldname'],
					'selectOptions'		=> $field['fieldselectoptions'],
					'value'				=> $configuration[$fieldId],
				);
				continue;
			}

			if (!isset($configuration[$fieldId])) {
				continue;
			}

			$value = $configuration[$fieldId];
			$newConfiguration[$fieldId] = array(
				'type'	=> $field['fieldtype'],
				'name'	=> $field['fieldname'],
				'value'	=> $configuration[$fieldId],
			);
		}

		// Store the finalized configuration for this product
		$this->setConfiguration($newConfiguration);
		return $this;
	}

	public function getConfiguration()
	{
		return $this->configuration;
	}

	public function getConfigurableOptions()
	{
		$configurableFields = array();
		$query = "
			SELECT *
			FROM [|PREFIX|]product_configurable_fields
			WHERE fieldprodid='".(int)$this->getProductId()."'
			ORDER BY fieldsortorder
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		while ($field = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$configurableFields[$field['productfieldid']] = $field;
		}

		return $configurableFields;
	}

	public function setEventDate($month, $day, $year)
	{
		if ($month === null && $day === null && $year === null) {
			$this->eventDate = array();
		}

		$this->eventDate = array(
			'month' => $month,
			'day' => $day,
			'year' => $year
		);

		$this->regenerateHash();

		return $this;
	}

	public function getEventDate($timestamp = false)
	{
		if (empty($this->eventDate)) {
			return false;
		}

		if ($timestamp == false) {
			return $this->eventDate;
		}

		return isc_gmmktime(0, 0, 0, $this->eventDate['month'], $this->eventDate['day'], $this->eventDate['year']);
	}

	/**
	 * Check that the supplied quantity (or quantity of the item on the quote)
	 * is available for this item and its inventory tracking method.
	 *
	 * @param int $quantity Quantity to check. If null uses existing quote item
	 * quantity.
	 * @return boolean True if the quantity is available, false if not.
	 */
	public function checkStockLevel($quantity = null)
	{
		// Inventory checking is disabled. Don't check quantity restrictions.
		if($this->inventoryChecking == false) {
			return true;
		}

		if ($quantity === null) {
			$quantity = $this->getQuantity();
		}

		$productData = $this->getProductData();

		$totalProductQuantity = $this->getExistingQuantityForProduct();

		// if this item is from an order, then we only want to deal with the difference between the originally ordered quantity and the new requested quantity
		if ($this->getQuote()->getOrderId()) {
			$totalOrderProductQuantity = $this->getExisitingOrderQuantityForProduct();

			$quantityToCheck = $totalProductQuantity
				- $totalOrderProductQuantity
				- $this->getQuantity()
				+ $quantity;
		}
		else {
			$quantityToCheck = $totalProductQuantity
			- $this->getQuantity()
			+ $quantity;
		}

		if ($this->getInventoryTrackingMethod() == 1 && $quantityToCheck > $productData['prodcurrentinv']) {
			return false;
		}

		if ($this->variationId > 0 && $this->getInventoryTrackingMethod() == 2 &&
			$quantityToCheck > $productData['variation']['vcstock']) {
				return false;
		}

		return true;
	}

	/**
	 * Check the minimum and maximum quantity restrictions that may be applied
	 * to this item allow for the requested quantity.
	 *
	 * @throws ISC_QUOTE_EXCEPTION when not meeting the quantity restrictions.
	 * @param int $quantity Quantity to check applies. If null, uses existing
	 * quantity for the quote item.
	 * @return boolean True when successful.
	 */
	public function checkQuantityRestrictions($quantity = null)
	{
		// Inventory checking is disabled. Don't check quantity restrictions.
		if($this->inventoryChecking == false) {
			return true;
		}

		if ($quantity === null) {
			$quantity = $this->getQuantity();
		}

		if ($quantity == 0) {
			return true;
		}

		$productData = $this->getProductData();
		$totalQuoteQuantity = $this->getExistingQuantityForProduct()
			- $this->getQuantity()
			+ $quantity;

		if ($productData['prodminqty'] && $totalQuoteQuantity < $productData['prodminqty']) {
			throw new ISC_QUOTE_EXCEPTION(getLang('ProductMinQtyError', array(
				'product'	=> $productData['prodname'],
				'qty'		=> $productData['prodminqty']
			)));
		}
		else if ($productData['prodmaxqty'] && $totalQuoteQuantity > $productData['prodmaxqty']) {
			throw new ISC_QUOTE_EXCEPTION(getLang('ProductMaxQtyError', array(
				'product'	=> $productData['prodname'],
				'qty'		=> $productData['prodmaxqty']
			)));
		}

		return true;
	}

	/**
	 * Throw an error message indicating that the requested quantity for this
	 * quote item is not available.
	 *
	 * @throws ISC_QUOTE_EXCEPTION
	 */
	public function throwBadStockLevelError()
	{
		throw new ISC_QUOTE_EXCEPTION(
			getLang('CannotAddQuantityToCart', array(
				'product' => $this->getName()
			)), ISC_QUOTE_EXCEPTION::ERROR_NO_STOCK
		);
	}

	/**
	 * Set the quantity of this quote item to the supplied value, and optionally
	 * bypassing all of the inventory and quantity restriction checks.
	 *
	 * @param int $quantity Quantity to set.
	 * @param boolean $checkStockLevel Set to false to not check inventory levels.
	 * @return ISC_QUOTE_ITEM This quote item instance.
	 */
	public function setQuantity($quantity, $checkStockLevel = true)
	{
		$quantity = (int)$quantity;

		if ($this->inQuote && $checkStockLevel) {
			if (!$this->checkStockLevel($quantity)) {
				$this->throwBadStockLevelError();
			}

			$this->checkQuantityRestrictions($quantity);
		}

		$oldQuantity = $this->quantity;
		$this->quantity = $quantity;
		if ($oldQuantity != $quantity && $this->inQuote) {
			$this->invalidateCachedTotals();
			if (!$this->getParentId()) {
				$this->getQuote()->reapplyDiscounts();
			}
		}

		return $this;
	}

	/**
	* Sets the quantity of the item that was ordered in an existing order.
	* This value is used to correctly check stock levels based on the difference between the
	* new requested quantity level and the original quantity.
	*
	* @param int $quantity Quantity to set
	* @return ISC_QUOTE_ITEM This quote item instance.
	*/
	public function setOriginalOrderQuantity($quantity)
	{
		$this->originalOrderQuantity = (int)$quantity;

		return $this;
	}

	public function handleCommitToQuote()
	{
		if (!$this->checkStockLevel()) {
			$this->throwBadStockLevelError();
		}

		$this->checkQuantityRestrictions();

		$this->invalidateCachedTotals();
		if (!$this->getParentId()) {
			$this->getQuote()->reapplyDiscounts();
		}
	}

	public function getProductId()
	{
		return $this->productId;
	}

	public function setProductId($id)
	{
		$this->productId = (int)$id;
		return $this;
	}

	public function getQuantity()
	{
		return $this->quantity;
	}

	/**
	* Returns the unallocated quantity for this item if the order is a split shipping order
	*
	* @return int 0 if the order is not split shipping or if all qty is allocated to addresses
	*/
	public function getUnallocatedQuantity()
	{
		if (!$this->quote->getIsSplitShipping()) {
			return 0;
		}

		$unallocated = $this->quote->getAddressById(ISC_QUOTE_ADDRESS::ID_UNALLOCATED);
		if (!$unallocated) {
			return 0;
		}

		$item = $unallocated->getItemByHash($this->getHash());
		if (!$item) {
			return 0;
		}

		return $item->getQuantity();
	}

	public function getOriginalOrderQuantity()
	{
		return $this->originalOrderQuantity;
	}

	public function generateId()
	{
		// do not allow all-number ids to be generated for items, since a numeric id is assumed to exist in the db already when ISC_ENTITY_ORDER is editing an order
		do {
			$id = uniqid();
		} while (is_numeric($id) || $this->getQuote()->getItemById($id));
		$this->setId($id);
		return $this;
	}

	public function getId()
	{
		if (!$this->id) {
			$this->id = -1; // to stop function recursion through generateId -> getItemById
			$this->generateId();
		}

		return $this->id;
	}

	public function setId($id)
	{
		if ($id != $this->id) {
			// Do stuff - notify children etc
		}

		$this->id = $id;
		return $this;
	}

	public function getParentId()
	{
		return $this->parentId;
	}

	public function getHash()
	{
		if (!$this->hash) {
			$this->regenerateHash();
		}

		return $this->hash;
	}

	public function regenerateHash()
	{
		$hash = array(
			$this->getType(),
			$this->getProductId(),
			$this->getVariationId(),
			$this->wrapping,
			$this->configuration,
			$this->eventDate,
			$this->getParentId(),
			$this->getAddressId(),
		);
		$newHash = md5(serialize($hash));
		$this->hash = $newHash;
		return $this;
	}

	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	public function setSku($sku)
	{
		$this->sku = $sku;
		return $this;
	}

	public function getName()
	{
		$productData = $this->getProductData();
		if (!empty($productData['prodname'])) {
			return $productData['prodname'];
		}

		return $this->name;
	}

	public function setBasePrice($price, $isCustomPrice = false)
	{
		$this->basePrice = (double)$price;
		$this->isCustomPrice = $isCustomPrice;
		if ($this->inQuote) {
			$this->invalidateCachedTotals();
			if (!$this->getParentId()) {
				$this->getQuote()->reapplyDiscounts();
			}
		}
		return $this;
	}

	public function getBasePrice()
	{
		if ($this->basePrice === null) {
			$this->recalculateBasePrice();
		}

		return $this->basePrice;
	}

	public function recalculateBasePrice()
	{
		if ($this->isCustomPrice) {
			return $this;
		}

		$priceOptions = array(
			'quantity' => $this->getQuantity(),
			'customerGroup' => $this->getQuote()->getCustomerGroupId(),
		);

		$productData = $this->getProductData();

		// Tack on the variation if there is one. There may not be, if we just
		// removed it.
		if (!empty($this->variationId)) {
			$priceOptions['variationModifier'] = $productData['variation']['vcpricediff'];
			$priceOptions['variationAdjustment'] = $productData['variation']['vcprice'];
		}

		$price = calculateFinalProductPrice($productData, $productData['prodcalculatedprice'], $priceOptions);
		$this->basePrice = $price;
		return $this;
	}

	public function getTax()
	{
		if(!$this->isTaxable()) {
			return 0;
		}

		return $this->getPrice(true) - $this->getPrice(false);
	}

	public function getTaxTotal()
	{
		if(!$this->isTaxable()) {
			return 0;
		}
		return $this->getTax() * $this->getQuantity();
	}

	public function getDiscountedBaseTotal()
	{
		return $this->getBaseTotal() - $this->getDiscountAmount();
	}

	public function getDiscountedTotal($incTax = false)
	{
		$discountedTotal = $this->getDiscountedBaseTotal();

		if(!$this->isTaxable()) {
			return $discountedTotal;
		}

		$taxPrice = getClass('ISC_TAX')->getPrice(
			$discountedTotal,
			$this->getTaxClassId(),
			$incTax,
			$this->getAddress()->getApplicableTaxZone()
		);
		return $taxPrice;
	}

	public function getBaseTotal()
	{
		return $this->getBasePrice() * $this->getQuantity();
	}

	public function getPrice($incTax = null)
	{
		if(!$this->isTaxable()) {
			return $this->getBasePrice();
		}

		$price = getClass('ISC_TAX')->getPrice(
			$this->getBasePrice(),
			$this->getTaxClassId(),
			$incTax,
			$this->getAddress()->getApplicableTaxZone()
		);
		return $price;
	}

	public function getTaxClassId()
	{
		$data = $this->getProductData();
		if (!empty($data)) {
			return $data['tax_class_id'];
		}

		return 0;
	}

	public function getBaseCostPrice()
	{
		$productData = $this->getProductData();
		if (!$productData) {
			return 0;
		}

		return $productData['prodcostprice'];
	}

	public function getCostPrice($incTax = null)
	{
		if(!$this->isTaxable()) {
			return $this->getBasePrice();
		}

		return getClass('ISC_TAX')->getPrice(
			$this->getBaseCostPrice(),
			$this->getTaxClassId(),
			$incTax,
			$this->getAddress()->getApplicableTaxZone()
		);
	}

	public function getCostPriceTax()
	{
		return $this->getCostPrice(true) - $this->getCostPrice(false);
	}

	public function getTotal($incTax = null)
	{
		if(!$this->isTaxable()) {
			return $this->getBasePrice() * $this->getQuantity();
		}

		$price = getClass('ISC_TAX')->getPrice(
			$this->getBasePrice(),
			$this->getTaxClassId(),
			$incTax,
			$this->getAddress()->getApplicableTaxZone(),
			null,
			false
		);

		return getClass('ISC_TAX')->round($price * $this->getQuantity(), null);
	}

	public function getSku()
	{
		$productData = $this->getProductData();

		// ISC-1209: return variation sku if available
		if (!empty($productData['prodvariationid'])) {
			if (isset($productData['variation']) && !empty($productData['variation'])) {
				$v = $productData['variation'];
				if (!empty($v['vcsku'])) {
					return $v['vcsku'];
				}
			}
		}

		if (!empty($productData['prodcode'])) {
			return $productData['prodcode'];
		}

		return $this->sku;
	}

	public function incrementQuantity($by)
	{
		$this->setQuantity($this->getQuantity() + $by);
		return $this;
	}

	/**
	* Sets the id of the address this item is assigned to
	*
	* @param string $id can also be supplied as ISC_QUOTE_ADDRESS instance from which the id will be taken
	* @return ISC_QUOTE_ITEM
	*/
	public function setAddressId($id)
	{
		if ($id instanceof ISC_QUOTE_ADDRESS) {
			$this->addressId = $id->getId();
		} else {
			$this->addressId = $id;
		}
		return $this;
	}

	public function getAddress($autoAssign = true)
	{
		if (!$autoAssign && !$this->addressId) {
			return false;
		}

		// Digital items always belong to the billing address
		if ($this->getType() == PT_DIGITAL || $this->getType() == PT_GIFTCERTIFICATE) {
			return $this->getQuote()->getBillingAddress();
		}

		$address = $this->getQuote()->getAddressById($this->addressId);
		// If this item is attached to an invalid address attach it to the first shipping
		// address.
		if (!is_object($address)) {
			$address = $this->getQuote()->getShippingAddress();
			$this->setAddressId($address->getId());
		}

		return $address;
	}

	public function getAddressId($autoAssign = true)
	{
		if (!$autoAssign && !$this->addressId) {
			return false;
		}

		return $this->getAddress()->getId();
	}

	public function hasFreeShipping()
	{
		$productData = $this->getProductData();
		if (!$productData) {
			return false;
		}

		return (bool)$productData['prodfreeshipping'];
	}

	public function setWeight($weight)
	{
		$this->weight = $weight;
		return $this;
	}

	public function setFixedShippingCost($cost)
	{
		$this->fixedShippingCost = (double)$cost;
		return $this;
	}

	public function getFixedShippingCost()
	{
		return $this->fixedShippingCost;
	}

	public function getDimensions()
	{
		$dimensions = array(
			'width' => 0,
			'depth' => 0,
			'height' => 0
		);
		$productData = $this->getProductData();
		if (!$productData) {
			return $dimensions;
		}

		$dimensions['width']	= $productData['prodwidth'];
		$dimensions['depth']	= $productData['proddepth'];
		$dimensions['height']	= $productData['prodheight'];
		return $dimensions;
	}

	public function setEventName($name)
	{
		$this->eventName = $name;
		return $this;
	}

	public function getEventName()
	{
		return $this->eventName;
	}

	public function isTaxable()
	{
		$productData = $this->getProductData();
		if ($this->getType() == PT_GIFTCERTIFICATE) {
			return false;
		}

		return true;
	}

	public function getBaseWrappingCost()
	{
		$giftWrapping = $this->getGiftWrapping();
		if (empty($giftWrapping['wrapprice'])) {
			return 0;
		}

		return $giftWrapping['wrapprice'] * $this->getQuantity();
	}

	public function getWrappingCost($incTax = false)
	{
		return getClass('ISC_TAX')->getPrice(
			$this->getBaseWrappingCost(),
			getConfig('taxGiftWrappingTaxClass'),
			$incTax,
			$this->getAddress()->getApplicableTaxZone()
		);
	}

	public function isDigital()
	{
		if ($this->getType() == PT_DIGITAL || $this->getType() == PT_GIFTCERTIFICATE) {
			return true;
		}

		return false;
	}

	public function getCategoryIds()
	{
		$productData = $this->getProductData();
		if (!empty($productData)) {
			return explode(',', $productData['prodcatids']);
		}

		return array();
	}

	/**
	 * Set the flag indicating if inventory checking should be enabled for this
	 * item. This is useful in cases where inventory levels for the product should
	 * be completely disregarded.
	 *
	 * @param boolean $checkInventory True to enable inventory checking, false if not.
	 * @return ISC_QUOTE_ITEM This quote item instance.
	 */
	public function setInventoryCheckingEnabled($checkInventory)
	{
		$this->inventoryChecking = $checkInventory;
		return $this;
	}

	/**
	 * Get the status flag indicating if inventory checking should be enabled for
	 * this item whenever the quantity is adjusted.
	 *
	 * @return boolean True if inventory levels should be checked, false if not.
	 */
	public function getInventoryCheckingEnabled()
	{
		return $this->inventoryChecking;
	}

	/**
	* Moves the specified quantity of this item from it's current address to another address.
	*
	* @param ISC_QUOTE_ADDRESS $address
	* @param mixed $quantity
	* @return ISC_QUOTE_ITEM returns the instance of the item at it's new location (meaning, if the item is cloned the NEW instance is returned)
	*/
	public function moveToAddress(ISC_QUOTE_ADDRESS $address, $quantity = null)
	{
		// note: no stock levels should be checked here since we're just re-allocating quantity within the same order

		/** @var ISC_LOG */
		$log = $GLOBALS['ISC_CLASS_LOG'];

		// does the item already exists at the destination?
		$item = $address->getItemByHash($this->getHash());

		$log->LogSystemDebug('general', 'request to move ' . $quantity . ' x item ' . $this->getName() . ' (' . $this->getId() . ') to address ' . $address->getId());

		if ($address->getId() === $this->getAddressId(false)) {
			// item is already at destination: ignore
			return $this;
		}

		if ($quantity !== null) {
			$quantity = (int)$quantity;
		}

		if ($quantity === null || $quantity >= $this->getQuantity()) {
			// moving all of this item
			if ($item) {
				// already exists at destination so increment qty and remove current item
				$log->LogSystemDebug('general', 'adding all of item to existing item at destination');
				$item->setQuantity($this->getQuantity() + $item->getQuantity(), false);
				$this->quote->removeItem($this->getId());
				return $item;
			}

			// does not exist at destination so just move the current item there
			$log->LogSystemDebug('general', 'moving item to destination');
			$this->setAddressId($address);
			return $this;
		}

		if ($quantity < 1) {
			// nothing to do
			return $this;
		}

		// moving partial qty: remove qty from current item now
		$this->setQuantity($this->getQuantity() - $quantity, false);

		// is the quantity of the the product now less than what was originally ordered? move the excess to the new item
		$originalOrderQuantityToMove = 0;
		if ($this->getQuantity() < $this->getOriginalOrderQuantity()) {
			$originalOrderQuantityToMove = $this->getOriginalOrderQuantity() - $this->getQuantity();
			$this->setOriginalOrderQuantity($this->getQuantity());
		}

		if ($item) {
			// already exists at destination so just increment that qty
			$log->LogSystemDebug('general', 'moving partial qty to existing item at destination');
			$item->setQuantity($item->getQuantity() + $quantity, false);
			$item->setOriginalOrderQuantity($originalOrderQuantityToMove);

			return $item;
		}

		$log->LogSystemDebug('general', 'moving partial qty to new item at destination');
		// does not exist at destination so create a new instance and put it there with the right qty
		/** @var ISC_QUOTE_ITEM */
		$item = clone $this;
		$this->getQuote()->addItem($item, false);

		$item
			->setAddressId($address)
			->setQuantity($quantity, false)
			->setOriginalOrderQuantity($originalOrderQuantityToMove);

		return $item;
	}

	/**
	* Primarily for twig
	*
	* @return bool
	*/
	public function isGiftCertificate()
	{
		return $this->type == PT_GIFTCERTIFICATE;
	}
	
	public function afterAddedtoCart() {

		if(GetConfig('isIntelisis')){
			$productData = $this->getProductData();
			$newprice = applyListaPreciosEsp($productData, $this->getQuantity(), $this->getVariationId());
			
			if($newprice != '')
			{
				$this->setBasePrice($newprice, true);
				$productData['prodcalculatedprice'] = $newprice;				
			}
			
			$newprice = applyPyC($productData, $this->getQuantity(), $this->getVariationId());
			
			if($newprice != '')
			{
				$this->setBasePrice($newprice, true);
			}
		}

	return $this;
	}
	
	public function setClasifier($clasifier){
		$this->clasifier = $clasifier;
	}
	
	public function getClasifier(){
		return $this->clasifier;
	}

}
