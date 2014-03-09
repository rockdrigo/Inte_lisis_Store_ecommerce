<?php

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'entity.base.php');

class ISC_ENTITY_ORDER extends ISC_ENTITY_BASE
{
	private $shipping;
	private $product;
	private $customer;

	protected $useTransactions;

	/**
	 * Constructor
	 *
	 * Base constructor
	 *
	 * @access public
	 */
	public function __construct()
	{
		$schema = array(
				"orderid" => "int",
				"ordtoken" => "text",
				"ordcustid" => "int",
				"orddate" => "date",
				"ordlastmodified" => "date",
				"subtotal_ex_tax" => "price",
				"subtotal_inc_tax" => "price",
				"subtotal_tax" => "price",
				"total_tax" => "price",
				"shipping_cost_ex_tax" => "price",
				"shipping_cost_inc_tax" => "price",
				"shipping_cost_tax" => "price",
				'shipping_cost_tax_class_id' => 'int',
				'handling_cost_ex_tax' => 'price',
				'handling_cost_inc_tax' => 'price',
				'handling_cost_tax' => 'price',
				'handling_cost_tax_class_id' => 'int',
				'wrapping_cost_ex_tax' => 'price',
				'wrapping_cost_inc_tax' => 'price',
				'wrapping_cost_tax' => 'price',
				'wrapping_cost_tax_class_id' => 'int',
				'total_ex_tax' => 'price',
				'total_inc_tax' => 'price',
				"ordstatus" => "int",
				"ordtotalqty" => "int",
				"ordtotalshipped" => "int",
				"orderpaymentmethod" => "text",
				"orderpaymentmodule" => "text",
				"ordpayproviderid" => "text",
				"ordpaymentstatus" => "text",
				"ordrefundedamount" => "price",
				"ordbillfirstname" => "text",
				"ordbilllastname" => "text",
				"ordbillcompany" => "text",
				"ordbillstreet1" => "text",
				"ordbillstreet2" => "text",
				"ordbillsuburb" => "text",
				"ordbillstate" => "text",
				"ordbillzip" => "text",
				"ordbillcountry" => "text",
				"ordbillcountrycode" => "text",
				"ordbillcountryid" => "int",
				"ordbillstateid" => "int",
				"ordbillphone" => "text",
				"ordbillemail" => "text",
				"ordisdigital" => "bool",
				"orddateshipped" => "date",
				"ordstorecreditamount" => "price",
				"ordgiftcertificateamount" => "price",
				"ordinventoryupdated" => "bool",
				"ordonlygiftcerts" => "bool",
				"extrainfo" => "text",
				"ordipaddress" => "text",
				"ordgeoipcountry" => "text",
				"ordgeoipcountrycode" => "text",
				"ordcurrencyid" => "int",
				"orddefaultcurrencyid" => "int",
				"ordcurrencyexchangerate" => "price",
				"ordnotes" => "text",
				"ordcustmessage" => "text",
				"ordvendorid" => "int",
				"ordformsessionid" => "int",
				"orddiscountamount" => "price",
				'shipping_address_count' => 'int',
				'coupon_discount' => 'price',
				'deleted' => 'bool',
				'extraField1' => 'text',
				'extraField2' => 'text',
				'extraField3' => 'text',
				'extraField4' => 'text',
				'extraField5' => 'text',
		);

		$tableName = "orders";
		$primaryKeyName = "orderid";
		$searchFields = array(
				"orderid",
				"ordtoken",
				"ordcustid",
				"ordbillfirstname",
				"ordbilllastname",
				"ordbillemail",
		);

		$customKeyName = "ordformsessionid";

		parent::__construct($schema, $tableName, $primaryKeyName, $searchFields, $customKeyName);

		$this->shipping = new ISC_ENTITY_SHIPPING();
		$this->product = new ISC_ENTITY_PRODUCT();
		$this->customer = new ISC_ENTITY_CUSTOMER();

		$this->useTransactions = true;
	}

	protected function parseInput($input)
	{
		if(empty($input['quote']) || !($input['quote'] instanceof ISC_QUOTE)) {
			return false;
		}

		/** @var ISC_QUOTE */
		$quote = $input['quote'];
		$billingAddress = $quote->getBillingAddress();
		$billingCustomFields = $billingAddress->getCustomFields();
		$billingFormSessionId = 0;
		if(!empty($billingCustomFields)) {
			$billingFormSessionId = $GLOBALS['ISC_CLASS_FORM']->saveFormSessionManual($billingCustomFields);
		}

		$order = array(
			'ordcustid'						=> $quote->getCustomerId(),
			'ordtotalqty'					=> $quote->getNumItems(),
			'ordisdigital'					=> (int)$quote->isDigital(),

			'subtotal_ex_tax'				=> $quote->getSubTotal(false),
			'subtotal_inc_tax'				=> $quote->getSubTotal(true),
			'subtotal_tax'					=> $quote->getSubTotalTax(),

			'total_tax'						=> $quote->getTaxTotal(),

			'base_shipping_cost'			=> $quote->getBaseShippingCost(),
			'shipping_cost_ex_tax'			=> $quote->getShippingCost(false),
			'shipping_cost_inc_tax'			=> $quote->getShippingCost(true),
			'shipping_cost_tax'				=> $quote->getShippingCostTax(),
			'shipping_cost_tax_class_id'	=> getConfig('taxShippingTaxClass'),

			'base_handling_cost'			=> $quote->getBaseHandlingCost(),
			'handling_cost_ex_tax'			=> $quote->getHandlingCost(false),
			'handling_cost_inc_tax'			=> $quote->getHandlingCost(true),
			'handling_cost_tax'				=> $quote->getHandlingCostTax(),
			'handling_cost_tax_class_id'	=> getConfig('taxShippingTaxClass'),

			'base_wrapping_cost'			=> $quote->getBaseWrappingCost(),
			'wrapping_cost_inc_tax'			=> $quote->getWrappingCost(true),
			'wrapping_cost_ex_tax'			=> $quote->getWrappingCost(false),
			'wrapping_cost_tax'				=> $quote->getWrappingCostTax(),
			'wrapping_cost_tax_class_id' => getConfig('taxGiftWrappingTaxClass'),

			'total_ex_tax'					=> $quote->getGrandTotalWithStoreCredit(),
			'total_inc_tax'					=> $quote->getGrandTotalWithStoreCredit(),

			'ordbillfirstname'				=> $billingAddress->getFirstName(),
			'ordbilllastname'				=> $billingAddress->getLastName(),
			'ordbillcompany'				=> $billingAddress->getCompany(),
			'ordbillstreet1'				=> $billingAddress->getAddress1(),
			'ordbillstreet2'				=> $billingAddress->getAddress2(),
			'ordbillsuburb'					=> $billingAddress->getCity(),
			'ordbillstate'					=> $billingAddress->getStateName(),
			'ordbillzip'					=> $billingAddress->getZip(),
			'ordbillcountry'				=> $billingAddress->getCountryName(),
			'ordbillcountrycode'			=> $billingAddress->getCountryIso2(),
			'ordbillcountryid'				=> $billingAddress->getCountryId(),
			'ordbillstateid'				=> $billingAddress->getStateId(),
			'ordbillphone'					=> $billingAddress->getPhone(),
			'ordbillemail'					=> $billingAddress->getEmail(),
			'ordformsessionid'				=> $billingFormSessionId,

			'ordgiftcertificateamount'		=> $quote->getGiftCertificateTotal(),
			'ordstorecreditamount'			=> $quote->getAppliedStoreCredit(),

			'orddiscountamount'				=> $quote->getDiscountAmount(),
			'coupon_discount'				=> $quote->getCouponDiscount(),

			'shipping_address_count'		=> count($quote->getShippingAddresses()),

			'ordcustmessage'				=> $quote->getCustomerMessage(),
			'ordnotes'						=> $quote->getStaffNotes(),
		);

		// Add in any other user supplied variables
		foreach($input as $k => $v) {
			if($k == 'quote') {
				continue;
			}

			$order[$k] = $v;
		}
		return parent::parseInput($order);
	}

	protected function addPrehook(&$savedata, $rawInput)
	{
		if(!isset($rawInput['ordtoken'])) {
			$savedata['ordtoken'] = generateOrderToken();
		}

		if(!array_key_exists("ordstatus", $rawInput)) {
			$savedata["ordstatus"] = 0;
		}

		$providerName = "";
		$providerId = "";

		// Order was paid for entirely with gift certificates
		if ($rawInput["orderpaymentmodule"] == "giftcertificate") {
			$providerName = "giftcertificate";
			$providerid = "";
		}
		// Order was paid for entirely using store credit
		else if ($rawInput["orderpaymentmodule"] == "storecredit") {
			$providerName = "storecredit";
			$providerId = "";
		}
		// Went through some sort of payment gateway
		else if(!empty($rawInput['orderpaymentmodule'])) {
			if (GetModuleById("checkout", $provider, $rawInput["orderpaymentmodule"]) && is_object($provider)) {
				$providerName = $provider->GetDisplayName();
				$providerId = $provider->GetId();
			}
			else {
				$providerId = $rawInput["orderpaymentmodule"];
				$providerName = $rawInput["orderpaymentmethod"];
			}
		}

		$savedata["orderpaymentmodule"] = $providerId;
		$savedata["orderpaymentmethod"] = $providerName;

		if (!array_key_exists("ordgeoipcountry", $rawInput) && !array_key_exists("ordgeoipcountrycode", $rawInput)) {
			// Attempt to determine the GeoIP location based on their IP address

			require_once ISC_BASE_PATH."/lib/geoip/geoip.php";
			$gi = geoip_open(ISC_BASE_PATH."/lib/geoip/GeoIP.dat", GEOIP_STANDARD);
			$savedata["ordgeoipcountrycode"] = geoip_country_code_by_addr($gi, GetIP());

			// If we get the country, look up the country name as well
			if (trim($savedata["ordgeoipcountrycode"]) !== "") {
				$savedata["ordgeoipcountry"] = geoip_country_name_by_addr($gi, GetIP());
			}
		}

		if (!array_key_exists("extraInfo", $rawInput) || !is_array($rawInput["extraInfo"])) {
			$savedata["extraInfo"] = array();
		} else {
			$savedata["extraInfo"] = $rawInput["extraInfo"];
		}

		$giftCertificates = $rawInput['quote']->getAppliedGiftCertificates();
		if(!empty($giftCertificates)) {
			$savedata["extraInfo"]["giftcertificates"] = $giftCertificates;
		}

		$savedata["extraInfo"] = serialize($savedata["extraInfo"]);

		$defaultCurrency = GetDefaultCurrency();
		if (is_array($defaultCurrency) && array_key_exists("currencyid", $defaultCurrency) && isId($defaultCurrency["currencyid"])) {
			$savedata["orddefaultcurrencyid"] = $defaultCurrency["currencyid"];
		}

		$savedata["orddate"] = time();
		$savedata["ordlastmodified"] = time();

		ResetStartingOrderNumber();
	}

	protected function editPrehook($orderId, &$savedata, $rawInput)
	{
		$giftCertificates = $rawInput['quote']->getAppliedGiftCertificates();
		if(!empty($giftCertificates)) {
			$savedata["extraInfo"]["giftcertificates"] = $giftCertificates;
		}

		$savedata["ordlastmodified"] = time();
	}

	private function commitCoupons($orderId, $rawInput, $editingExisting = false)
	{
		// Delete existing coupon codes for this order
		if($editingExisting) {
			$GLOBALS['ISC_CLASS_DB']->deleteQuery('order_coupons',
				"WHERE ordcouporderid='".(int)$orderId."'"
				);
		}

		$quote = $rawInput['quote'];
		$coupons = $quote->getAppliedCoupons();
		foreach($coupons as $coupon) {
			$newCoupon = array(
				'ordcouporderid' => $orderId,
				'ordcouponid' => $coupon['id'],
				'ordcouponcode' => $coupon['code'],
				'ordcouponamount' => $coupon['discountAmount'],
				'ordcoupontype' => $coupon['discountType'],
				'applied_discount' => $coupon['totalDiscount'],
			);
			if(!$GLOBALS['ISC_CLASS_DB']->insertQuery('order_coupons', $newCoupon)) {
				return false;
			}
		}

		return true;
	}

	private function commitProducts($orderId, $rawInput, $itemAddressMap, $editingExisting=false, $adjustInventory=true)
	{
		$orderId = (int)$orderId;
		if (!$orderId) {
			$this->setError("Invalid arguments passed to commitProducts");
			return false;
		}

		/** @var ISC_QUOTE */
		$quote = $rawInput['quote'];

		$existingOrder = false;
		$existingProducts = false;

		if ($editingExisting && $orderId) {
			$existingOrder = $this->get($orderId);
			if (!$existingOrder) {
				$this->setError("editingExisting specified in commitProducts but order " . $orderId . " not found");
				return false;
			}

			// prune products from the existing order which are no longer on the incoming edit (products tied to unused addresses should already be gone after commitAddresses())
			$this->deleteUnusedOrderProducts($orderId, $quote);

			$existingProducts = $this->getOrderProducts($orderId);
			$existingProducts = Interspire_Array::remapToSubkey($existingProducts, 'orderprodid'); // make existing products easier to find by id
		}

		$giftCertificates = array();
		foreach($quote->getItems() as /** @var ISC_QUOTE_ITEM */$item) {
			$itemType = 'physical';
			if($item->getType() == PT_DIGITAL) {
				$itemType = 'digital';
			}
			else if($item instanceof ISC_QUOTE_ITEM_GIFTCERTIFICATE) {
				// Gift certificates cannot be modified so continue if this is an existing order
				if($existingOrder) {
					continue;
				}
				$itemType = 'giftcertificate';
				$giftCertificates[] = $item;
			}

			$existingProduct = false;
			if (is_numeric($item->getId())) {
				// numeric quote_item id denotes existing order_product, but if it doesn't exist for some reason treat it as a new order_product I guess
				if (isset($existingProducts[$item->getId()])) {
					$existingProduct = $existingProducts[$item->getId()];
				}
			}

			// addresses are already stored in db and assigned real ids by commitAddresses()
			$addressId = 0;
			$itemAddressId = $item->getAddressId();
			if(isset($itemAddressMap[$itemAddressId])) {
				$addressId = $itemAddressMap[$itemAddressId];
			}

			$appliedDiscounts = $item->getDiscounts();
			if (empty($appliedDiscounts)) {
				$appliedDiscounts = '';
			} else {
				$appliedDiscounts = serialize($appliedDiscounts);
			}

			// build order_products data for upsert
			$newProduct = array(
				'orderorderid'				=> $orderId,
				'ordprodid'					=> $item->getProductId(),
				'ordprodsku'				=> $item->getSku(),
				'ordprodname'				=> $item->getName(),
				'ordprodtype'				=> $itemType,
				'ordprodqty'				=> $item->getQuantity(),

				'base_price'				=> $item->getBasePrice(),
				'price_ex_tax'				=> $item->getPrice(false),
				'price_inc_tax'				=> $item->getPrice(true),
				'price_tax'					=> $item->getTax(),

				'base_total'				=> $item->getBaseTotal(),
				'total_ex_tax'				=> $item->getTotal(false),
				'total_inc_tax'				=> $item->getTotal(true),
				'total_tax'					=> $item->getTaxTotal(),

				'base_cost_price'			=> $item->getBaseCostPrice(),
				'cost_price_inc_tax'		=> $item->getCostPrice(false),
				'cost_price_inc_tax'		=> $item->getCostPrice(true),
				'cost_price_tax'			=> $item->getCostPriceTax(),

				'ordprodweight'				=> $item->getWeight(),

				'ordprodoptions'			=> '',
				'ordprodvariationid'		=> $item->getVariationid(),

				'ordprodwrapid'				=> 0,
				'ordprodwrapname'			=> '',
				'base_wrapping_cost'		=> $item->getBaseWrappingCost(),
				'wrapping_cost_ex_tax'		=> $item->getWrappingCost(false),
				'wrapping_cost_inc_tax'		=> $item->getWrappingCost(true),
				'wrapping_cost_tax'			=> $item->getWrappingCost(true) - $item->getWrappingCost(false),
				'ordprodwrapmessage'		=> '',
				'ordprodeventname'			=> $item->getEventName(),
				'ordprodeventdate'			=> $item->getEventDate(true),
				'ordprodfixedshippingcost'	=> $item->getFixedShippingCost(),

				'order_address_id'			=> $addressId,

				'applied_discounts'			=> $appliedDiscounts,
			);

			$variationOptions = $item->getVariationOptions();
			if(!empty($variationOptions)) {
				$newProduct['ordprodoptions'] = serialize($variationOptions);
			}

			$wrapping = $item->getGiftWrapping();
			if(!empty($wrapping)) {
				$newProduct['ordprodwrapid'] = $wrapping['wrapid'];
				$newProduct['ordprodwrapname'] = $wrapping['wrapname'];
				$newProduct['ordprodwrapmessage'] = $wrapping['wrapmessage'];
			}

			// upsert to order_products
			if ($existingProduct) {
				if (!$this->db->UpdateQuery('order_products', $newProduct, "orderprodid = " . $existingProduct['orderprodid'])) {
					$this->setError("Failed to update existing order product");
					return false;
				}

				// remove existing configurable fields so they can be reinserted if needed
				$this->deleteOrderProductConfigurableFields($existingProduct['orderprodid']);
			} else {
				$orderProductId = $GLOBALS['ISC_CLASS_DB']->insertQuery('order_products', $newProduct);
				if(!$orderProductId) {
					$this->setError("Failed to insert new order product");
					return false;
				}
			}

			// adjust inventory levels for existing orders only (UpdateOrderStatus() seems to do it for new orders)
			if ($adjustInventory && $existingOrder && $existingOrder['ordinventoryupdated']) {
				$inventoryRequiresAdjustment = true;

				if ($existingProduct && $existingProduct['ordprodid']) {
					if ($existingProduct['ordprodid'] == $item->getProductId() && $existingProduct['ordprodvariationid'] == $item->getVariationId() && $existingProduct['ordprodqty'] == $item->getQuantity()) {
						// don't bother adjusting if the product, variation and qty details have not changed
						$inventoryRequiresAdjustment = false;
					}

					if ($inventoryRequiresAdjustment) {
						$product = $this->product->get($existingProduct['ordprodid']);
						if ($product) {
							// put old product inventory back to store
							if (!AdjustProductInventory($existingProduct['ordprodid'], $existingProduct['ordprodvariationid'], $product['prodinvtrack'], '+' . $existingProduct['ordprodqty'])) {
								$this->setError("Failed to adjust inventory for old order product");
								return false;
							}
						}
					}
				}

				// pull new product inventory from store
				if ($inventoryRequiresAdjustment && $item->getProductId()) {
					if (!AdjustProductInventory($item->getProductId(), $item->getVariationId(), $item->getInventoryTrackingMethod(), '-' . $item->getQuantity())) {
						$this->setError("Failed to adjust inventory for new order product");
						return false;
					}
				}
			}

			$configurableFields = $item->getConfiguration();
			foreach($configurableFields as $fieldId => $field) {
				if($field['type'] == 'file' && trim($field['value']) != '') {
					$filePath = ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/configured_products/'.$field['value'];
					$fileTmpPath = ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/configured_products_tmp/'.$field['value'];

					//do not remove the temp file here, because the payment may not successful
					//the file should still be viewable in in the cart,
					@copy($fileTmpPath, $filePath);
				}

				$newField = array(
					'ordprodid' => $orderProductId,
					'fieldid' => $fieldId,
					'orderid' => $orderId,
					'fieldname' => $field['name'],
					'fieldtype' => $field['type'],
					'fieldselectoptions' => '',
					'textcontents' => '',
					'filename' => '',
					'filetype' => '',
					'originalfilename' => '',
					'productid' => $item->getProductId(),
				);

				if($field['type'] == 'file' && trim($field['value']) != '') {
					$newField['filename'] = trim($field['value']);
					$newField['filetype'] = trim($field['fileType']);
					$newField['originalfilename'] = trim($field['fileOriginalName']);
				}
				elseif ($field['type'] == 'select') {
					$newField['fieldselectoptions'] = $field['selectOptions'];
					$newField['textcontents'] = trim($field['value']);
				}
				else {
					$newField['textcontents'] = trim($field['value']);
				}

				if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery('order_configurable_fields', $newField)) {
					return false;
				}
			}
		}

		if(!empty($giftCertificates)) {
			getClass('ISC_GIFTCERTIFICATES')->createGiftCertificatesFromOrder(
			$orderId,
			$giftCertificates,
			1
			);
		}

		return true;
	}

	/**
	 * Delete product data assigned to a specific order address
	 *
	 * @param int $orderId
	 * @param int $addressId
	 * @return bool false on failure otherwise true
	 */
	protected function deleteOrderAddressProducts($orderId, $addressId)
	{
		$orderId = (int)$orderId;
		$addressId = (int)$addressId; // note: address 0 is still valid as it is used as the address id for digital products
		if (!$orderId) {
			$this->setError("Invalid arguments passed to deleteOrderAddressProducts");
			return false;
		}

		$products = $this->db->Query("SELECT orderprodid FROM [|PREFIX|]order_products WHERE order_address_id = " . $addressId . " AND orderorderid = " . $orderId);
		if (!$products) {
			$this->setError($this->db->GetErrorMsg());
			return false;
		}

		while ($product = $this->db->Fetch($products)) {
			if (!$this->deleteOrderProduct($product['orderprodid'])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns a list of order products from the database for the specified order $orderId
	 *
	 * @param int $orderId
	 * @return array list of raw order_products data rows or false on failure
	 */
	public function getOrderProducts($orderId, $useOrderProductIdAsArrayIndex = false)
	{
		$orderId = (int)$orderId;
		if (!$orderId) {
			$this->setError("Invalid arguments passed to getOrderProducts");
			return false;
		}

		$products = array();

		$query = $this->db->Query("SELECT * FROM [|PREFIX|]order_products WHERE orderorderid = " . $orderId);
		if (!$query) {
			$this->setError($this->db->GetErrorMsg());
			return false;
		}

		while ($product = $this->db->Fetch($query)) {
			$products[] = $product;
		}

		return $products;
	}

	/**
	 * Deletes order_configurable_fields for the specified order product $orderProductId
	 *
	 * @param int $orderProductId
	 * @return bool false on failure otherwise true
	 */
	public function deleteOrderProductConfigurableFields($orderProductId)
	{
		$orderProductId = (int)$orderProductId;
		if (!$orderProductId) {
			$this->setError("Invalid arguments passed to deleteOrderProductConfigurableFields");
			return false;
		}

		if (!$this->db->DeleteQuery('order_configurable_fields', "WHERE ordprodid = " . $orderProductId)) {
			$this->setError($this->db->GetErrorMsg());
			return false;
		}

		return true;
	}

	/**
	 * Deletes an order product from the specified order, optionally adjusting inventory levels accordingly
	 *
	 * @param int $orderProductId
	 * @param bool $adjustInventory
	 * @return bool false on failure otherwise true
	 */
	public function deleteOrderProduct($orderProductId, $adjustInventory = true)
	{
		// @hack maybe order products should be their own entity, as they would be under a model system

		// verify the order and product

		$orderProductId = (int)$orderProductId;
		if (!$orderProductId) {
			$this->setError("Invalid arguments passed to deleteOrderProduct");
			return false;
		}

		$orderProduct = $this->db->FetchRow("
			SELECT op.ordprodid, op.ordprodvariationid, op.ordprodqty, op.orderorderid, p.productid, p.prodinvtrack
			FROM [|PREFIX|]order_products op
			LEFT JOIN [|PREFIX|]products p ON p.productid = op.ordprodid
			WHERE op.orderprodid = " . $orderProductId . "
		");
		if (!$orderProduct) {
			$this->setError("Order product " . $orderProductId . " not found.");
			return false;
		}

		// cascade delete related data

		if (!$this->deleteOrderProductConfigurableFields($orderProductId)) {
			$this->setError("Failed to delete order product configurable fields");
			return false;
		}

		if (!$this->db->DeleteQuery('order_products', "WHERE orderprodid = " . $orderProductId)) {
			$this->setError($this->db->GetErrorMsg());
			return false;
		}

		// adjust inventory

		if (!$adjustInventory) {
			// nothing to do
			return true;
		}

		$order = $this->get($orderProduct['orderorderid']);

		if (!$order || !$order['ordinventoryupdated'] || !$orderProduct['productid']) {
			// order doesn't exist, or order did not update inventory, or original product not found - nothing to do
			$this->log->LogSystemDebug("general", "adjustInventory set in deleteOrderProduct but can't adjust inventory because order " . $orderProduct['orderorderid'] . " does not exist");
			return true;
		}

		if (!AdjustProductInventory($orderProduct['ordprodid'], $orderProduct['ordprodvariationid'], $orderProduct['prodinvtrack'], '+' . $orderProduct['ordprodqty'])) {
			$this->setError("Failed to adjust product inventory for deleted order product.");
			return false;
		}

		return true;
	}

	/**
	 * Removes a specific order address (by id) from the database, also removing data related to it.
	 *
	 * @param int $orderId
	 * @param int $addressId
	 * @return bool false on error otherwise true
	 */
	public function deleteOrderAddress($orderId, $addressId)
	{
		// note: orderId is required as a protection measure against unintended deletion of records -- if it becomes annoying to provide it, then remove the requirement, just make sure all calls to it are safe

		$orderId = (int)$orderId;
		$addressId = (int)$addressId;
		if (!$orderId || !$addressId) {
			return false;
		}

		// delete the address record itself
		if (!$this->db->DeleteQuery('order_addresses', "WHERE `id` = " . $addressId)) {
			return false;
		}

		// delete shipping information for the address
		if (!$this->db->DeleteQuery('order_shipping', "WHERE order_id = " . $orderId . " AND order_address_id = " . $addressId)) {
			return false;
		}

		if (!$this->deleteOrderAddressProducts($orderId, $addressId)) {
			return false;
		}

		return true;
	}

	/**
	 * Removes (order)products from the database for $orderId which are not in the ISC_QUOTE instance $quote
	 *
	 * @param int $orderId
	 * @param ISC_QUOTE $quote
	 * @return bool false on failure otherwise true
	 */
	public function deleteUnusedOrderProducts($orderId, ISC_QUOTE $quote)
	{
		$orderId = (int)$orderId;
		$products = $this->db->Query("SELECT `orderprodid` FROM [|PREFIX|]order_products WHERE orderorderid = " . $orderId);
		if (!$products) {
			return false;
		}

		while ($product = $this->db->Fetch($products)) {
			$quoteProduct = $quote->getItemById($product['orderprodid']);
			if ($quoteProduct) {
				// product is still in the quote
				continue;
			}

			if (!$this->deleteOrderProduct($product['orderprodid'])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Removes addresses from the database for order $orderId which are not in the ISC_QUOTE instance $quote, will also delete info associated with that address (shipping and products)
	 *
	 * @param int $orderId
	 * @param ISC_QUOTE $quote
	 * @return bool false on error otherwise true
	 */
	public function deleteUnusedOrderAddresses($orderId, ISC_QUOTE $quote)
	{
		$orderId = (int)$orderId;
		$addresses = $this->db->Query("SELECT `id` FROM [|PREFIX|]order_addresses WHERE order_id = " . $orderId);
		if (!$addresses) {
			return false;
		}

		while ($address = $this->db->Fetch($addresses)) {
			$addressId = (int)$address['id'];
			$quoteAddress = $quote->getAddressById($addressId);
			if ($quoteAddress) {
				continue;
			}

			if (!$this->deleteOrderAddress($orderId, $addressId)) {
				return false;
			}
		}

		return true;
	}

	protected function commitAddresses($orderId, $rawInput, $existingOrder = false)
	{
		$quote = $rawInput['quote'];
		$addresses = $quote->getAllAddresses();
		$itemAddressMap = array();

		if($existingOrder) {
			// Remove all existing taxes
			$GLOBALS['ISC_CLASS_DB']->deleteQuery('order_taxes', "WHERE order_id='".(int)$orderId."'");
			$this->deleteUnusedOrderAddresses($orderId, $quote);
		}

		foreach($addresses as /** @var ISC_QUOTE_ADDRESS */$address) {
			// Save the custom fields for this address
			$formSessionId = 0;
			$customFormFields = $address->getCustomFields();
			if(!empty($customFormFields)) {
				$formSessionId = $GLOBALS['ISC_CLASS_FORM']->saveFormSessionManual($customFormFields);
			}
				
			// Billing addresses are still inserted into the orders table.
			if($address->getType() != ISC_QUOTE_ADDRESS::TYPE_BILLING) {

				$orderAddress = array(
					'order_id'			=> $orderId,
					'first_name'		=> $address->getFirstName(),
					'last_name'			=> $address->getLastName(),
					'company'			=> $address->getCompany(),
					'address_1'			=> $address->getAddress1(),
					'address_2'			=> $address->getAddress2(),
					'city'				=> $address->getCity(),
					'zip'				=> $address->getZip(),
					'country'			=> $address->getCountryName(),
					'country_iso2'		=> $address->getCountryIso2(),
					'country_id'		=> $address->getCountryId(),
					'state'				=> $address->getStateName(),
					'state_id'			=> $address->getStateId(),
					'email'				=> $address->getEmail(),
					'phone'				=> $address->getPhone(),
					'form_session_id'	=> $formSessionId,
					'total_items'		=> $address->getNumItems(),
				);

				if (is_numeric($address->getId())) {
					// numeric ids denote addresses which already exist in the db and should be updated instead
					$addressId = (int)$address->getId();
					if (!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('order_addresses', $orderAddress, "`id`=" . $addressId)) {
						return false;
					}
				} else {
					$addressId = $GLOBALS['ISC_CLASS_DB']->insertQuery('order_addresses', $orderAddress);
					if(!$addressId) {
						return false;
					}
				}

				$itemAddressMap[$address->getId()] = $addressId;

				// Now insert the shipping information
				$orderShipping = array(
					'order_address_id'				=> $addressId,
					'order_id'						=> $orderId,
					'base_cost'						=> $address->getBaseShippingCost(),
					'cost_ex_tax'					=> $address->getShippingCost(false),
					'cost_inc_tax'					=> $address->getShippingCost(true),
					'tax'							=> $address->getShippingCostTax(),
					'method'						=> $address->getShippingProvider(),
					'module'						=> $address->getShippingModule(),
					'tax_class_id'					=> getConfig('taxShippingTaxClass'),
					'base_handling_cost'			=> $address->getBaseHandlingCost(),
					'handling_cost_ex_tax'			=> $address->getHandlingCost(false),
					'handling_cost_inc_tax'			=> $address->getHandlingCost(true),
					'handling_cost_tax'				=> $address->getHandlingCostTax(),
					'handling_cost_tax_class_id'	=> getConfig('taxShippingTaxClass'),
					'shipping_zone_id'				=> $address->getShippingAddressZone(),
					'shipping_zone_name'			=> $address->getShippingAddressZoneName(),
				);

				if (is_numeric($address->getId())) {
					// numeric ids denote addresses which already exist in the db and should be updated instead
					$addressId = (int)$address->getId();
					if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('order_shipping', $orderShipping, "order_id = " . (int)$orderId . " AND order_address_id = " . $addressId)) {
						return false;
					}
				} else {
					if(!$GLOBALS['ISC_CLASS_DB']->insertQuery('order_shipping', $orderShipping)) {
						return false;
					}
				}
			}
			else {
				$addressId = 0;
			}

			// Save the tax summary for this address
			$taxSummary = $address->getTaxSummary();
			foreach($taxSummary as $taxClassId => $taxClass) {
				foreach($taxClass['prioritizedRates'] as $priority => $priorityRate) {
					foreach($priorityRate['rates'] as $taxRateId => $taxRate) {
						$orderTax = array(
							'order_id'			=> $orderId,
							'order_address_id'	=> $addressId,
							'tax_rate_id'		=> $taxRateId,
							'tax_class_id'		=> $taxClassId,
							'name'				=> $taxRate['name'],
							'class'				=> $taxClass['name'],
							'priority'			=> $priority,
							'line_amount'		=> $taxRate['amount'],
							'priority_amount'	=> $priorityRate['amount'],
							'rate'				=> $taxRate['rate'],
						);
						// taxes were deleted above so checking for existing records when editing does not apply
						if(!$GLOBALS['ISC_CLASS_DB']->insertQuery('order_taxes', $orderTax)) {
							return false;
						}
					}
				}
			}

			if($address->getCustomerAddressId() && $quote->getCustomerId()) {
				$updatedAddress = array(
					'shiplastused' => time()
				);
				$GLOBALS['ISC_CLASS_DB']->updateQuery('shipping_addresses', $updatedAddress, 'shipid='.$address->getCustomerAddressId());
			}
			// Should this address be saved? This can only be done for quotes belonging to a customer
			else if($address->getSaveAddress() && $quote->getCustomerId()) {
				$addressArray = $address->getAsArray();
				$addressArray['shipformsessionid'] = $formSessionId;
				$addressArray['shipcustomerid'] = $quote->getCustomerId();
				$addressArray['GUID'] = $address->getGUID();
				if(!$this->shipping->basicSearch($addressArray)) {
					$this->shipping->add($addressArray);
				}
			}
		}

		return $itemAddressMap;
	}
	protected function addPosthook($orderId, $savedata, $rawInput)
	{
		$itemAddressMap = $this->commitAddresses($orderId, $rawInput, false);
		if($itemAddressMap === false) {
			return false;
		}

		if(!$this->commitProducts($orderId, $rawInput, $itemAddressMap, false)) {
			return false;
		}

		if(!$this->commitCoupons($orderId, $rawInput, false)) {
			return false;
		}

		// Delete any orders that are incomplete and were placed more than a week ago. This helps keep the database clean
		$this->deleteOldOrders();

		deleteOldConfigProductFiles();

		if(isset($rawInput['ordstatus']) && $rawInput['ordstatus'] != ORDER_STATUS_INCOMPLETE) {
			$isNewOrder = true;
		}
		else {
			$isNewOrder = false;
		}

		if($isNewOrder && GetConfig('isIntelisis') && (GetConfig('syncIWSurl') != '' || GetConfig('syncDropboxActive') == 1)){
			$IWS = new ISC_INTELISIS_WS_ORDER($orderId);
			if(!$IWS->prepareRequest()) return false;
		}

		return true;
	}

	protected function editPosthook($orderId, $savedata, $rawInput)
	{
		$itemAddressMap = $this->commitAddresses($orderId, $rawInput, true);
		if ($itemAddressMap === false) {
			return false;
		}

		if (!$this->commitProducts($orderId, $rawInput, $itemAddressMap, true, (bool)GetConfig('UpdateInventoryOnOrderEdit'))) {
			return false;
		}

		if (!$this->commitCoupons($orderId, $rawInput, true)) {
			return false;
		}

		$oldOrder = GetOrder($orderId);

		if(isset($rawInput['ordstatus']) && $oldOrder['ordstatus'] == ORDER_STATUS_INCOMPLETE && $rawInput['ordstatus'] != ORDER_STATUS_INCOMPLETE) {
			$isNewOrder = true;
		}
		else {
			$isNewOrder = false;
		}

		if($isNewOrder && GetConfig('isIntelisis') && (GetConfig('syncIWSurl') != '' || GetConfig('syncDropboxActive') == 1)){
			$IWS = new ISC_INTELISIS_WS_ORDER($orderId);
			if(!$IWS->prepareRequest()) return false;
				}

		// Delete any orders that are incomplete and were placed more than a week ago. This helps keep the database clean
		$this->deleteOldOrders();

		deleteOldConfigProductFiles();

		return true;
	}

	/**
	 * Undeletes the specified and adjusts store inventory levels as required. This only works if the order was deleted
	 * using delete() -- if the data was removed completely using purge() this will do nothing and return false.
	 *
	 * @param int $orderId The order id to delete, or the array result of entity->get() -- providing full data saves a db select call
	 * @return bool false on error otherwise true
	 */
	public function undelete ($orderId)
	{
		if (is_array($orderId) && isset($orderId[$this->primaryKeyName])) {
			// order data provided
			$order = $orderId;
			$orderId = (int)$order[$this->primaryKeyName];
		} else {
			// get order data
			$orderId = (int)$orderId;
			$order = $this->get($orderId);
		}

		if (!$orderId || !$order) {
			// order not found
			return false;
		}

		if (!$order['deleted']) {
			// order not deleted
			return true;
		}

		// query directly because we can't use edit(): parseInput() for orders is expecting an ISC_QUOTE instance which
		// we don't want or need at this point
		$update = array(
			'ordlastmodified' => time(),
			'deleted' => 0,
		);

		$GLOBALS["ISC_CLASS_LOG"]->LogSystemDebug("general", "Restoring order " . $orderId);

		if (!$this->db->UpdateQuery('orders', $update, 'orderid = ' . $orderId)) {
			return false;
		}

		if (!GetConfig('UpdateInventoryOnOrderDelete') || !$order['ordinventoryupdated']) {
			// either the store is not configured to adjust inventory on order deletes or the order has not adjusted
			// stock levels yet - nothing more to do
			return true;
		}

		// remove inventory for this order from the store - find products even if they have inv tracking off because
		// AdjustProductInventory will adjust numsold too
		$products = "
			SELECT op.orderprodid, op.ordprodid, op.ordprodvariationid, op.ordprodqty, op.orderorderid, p.productid, p.prodinvtrack
			FROM [|PREFIX|]order_products op, [|PREFIX|]products p
			WHERE op.orderorderid = " . $orderId . " AND p.productid = op.ordprodid
		";
		$products = $this->db->Query($products);
		if (!$products) {
			return false;
		}

		while ($product = $this->db->Fetch($products)) {
			$adjustment = 0 - (int)$product['ordprodqty'];
			if (!AdjustProductInventory($product['productid'],
			$product['ordprodvariationid'],
			$product['prodinvtrack'],
			$adjustment
			)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Completely deletes the specified order and all data related to it. To only mark an order as deleted as far as the
	 * rest of the app is concerned, use delete() instead, which is overriden.
	 *
	 * @see ISC_ENTITY_BASE::delete
	 */
	public function purge ($orderId, $extraOption = false)
	{
		return parent::delete($orderId, $extraOption);
	}

	/**
	 * Marks the specified order as deleted and adjusts store inventory levels as though the order was actually deleted.
	 * The order can be restored with undelete(). To permanently remove all data for an order, use purge() instead.
	 *
	 * @param int $orderId The order id to delete, or the array result of entity->get() -- providing full data saves a db select call
	 * @param mixed $extraOption see ISC_ENTITY_BASE::delete()
	 * @return bool false on error otherwise true
	 */
	public function delete ($orderId, $extraOption = false)
	{
		// note: do not call parent::delete(), save that for purge() instead

		if (is_array($orderId) && isset($orderId[$this->primaryKeyName])) {
			// order data provided
			$order = $orderId;
			$orderId = (int)$order[$this->primaryKeyName];
		} else {
			// get order data
			$orderId = (int)$orderId;
			$order = $this->get($orderId);
		}

		if (!$orderId || !$order) {
			return false;
		}

		if ($order['deleted']) {
			return true;
		}

		$GLOBALS["ISC_CLASS_LOG"]->LogSystemDebug("general", "Marking order " . $orderId . " as deleted.");

		// query directly because we can't use edit(): parseInput() for orders is expecting an ISC_QUOTE instance which
		// we don't want or need at this point
		$update = array(
			'ordlastmodified' => time(),
			'deleted' => 1,
		);

		if (!$this->db->UpdateQuery('orders', $update, 'orderid = ' . $orderId)) {
			return false;
		}

		if (!GetConfig('UpdateInventoryOnOrderDelete') || !$order['ordinventoryupdated']) {
			// either the store is not configured to adjust inventory on order deletes or the order has not adjusted
			// stock levels yet - nothing more to do
			return true;
		}

		// put inventory for this order back into the store - find products even if they have inv tracking off because
		// AdjustProductInventory will adjust numsold too
		$products = "
			SELECT op.orderprodid, op.ordprodid, op.ordprodvariationid, op.ordprodqty, op.orderorderid, p.productid, p.prodinvtrack
			FROM [|PREFIX|]order_products op, [|PREFIX|]products p
			WHERE op.orderorderid = " . $orderId . " AND p.productid = op.ordprodid
		";
		$products = $this->db->Query($products);
		if (!$products) {
			return false;
		}

		while ($product = $this->db->Fetch($products)) {
			if (!AdjustProductInventory($product['productid'],
			$product['ordprodvariationid'],
			$product['prodinvtrack'],
			$product['ordprodqty']
			)) {
				return false;
			}
		}

		return true;
	}

	protected function deletePrehook($orderId, $order, $rawInput)
	{
		// this is for really deleting an order and will occurr when purge() is called, but not delete()
		// order_product deletion should happen before order deletion so inventory adjustments can happen properly

		$products = $this->db->Query("SELECT `orderprodid` FROM [|PREFIX|]order_products WHERE orderorderid = " . (int)$orderId);
		if (!$products) {
			return false;
		}

		// only adjust inventory if the order is not marked as deleted - if an order is as deleted, the store inventory
		// levels should have already been adjusted by the delete() method
		$adjustInventory = GetConfig('UpdateInventoryOnOrderDelete') && !(bool)$order['deleted'];

		while ($product = $this->db->Fetch($products)) {
			if (!$this->deleteOrderProduct($product['orderprodid'], $adjustInventory)) {
				return false;
			}
		}

		return true;
	}

	protected function deletePosthook($orderId, $order, $deleteGiftCertificates=false)
	{
		// this is for really deleting an order and will occurr when purge() is called, but not delete()

		$formSessions = array();
		if($order['ordformsessionid']) {
			$formSessions[] = $order['ordformsessionid'];
		}

		$query = "
			SELECT form_session_id
			FROM [|PREFIX|]order_addresses
			WHERE order_id='".(int)$orderId."' AND form_session_id != ''
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		while($address = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			$formSessions[] = $address['form_session_id'];
		}

		/**
		 * Set up the delete queries we'll be using
		 */
		$queries = array(
			"DELETE FROM [|PREFIX|]order_coupons WHERE ordcouporderid = " . (int)$orderId,
			"DELETE FROM [|PREFIX|]order_messages WHERE messageorderid = " . (int)$orderId,
			"DELETE FROM [|PREFIX|]order_downloads WHERE orderid = " . (int)$orderId,
			"DELETE FROM [|PREFIX|]order_taxes WHERE order_id = " . (int)$orderId,
			"DELETE FROM [|PREFIX|]order_addresses WHERE order_id = " . (int)$orderId,
			"DELETE FROM [|PREFIX|]order_shipping WHERE order_id = " . (int)$orderId,
		);

		/**
		 * If deleting gift certificates too, add that in to the mix
		 */
		if ($deleteGiftCertificates) {
			$queries[] = "DELETE FROM [|PREFIX|]gift_certificates WHERE giftcertorderid = " . (int)$orderId;
		}

		foreach ($queries as $query) {
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				return false;
			}
		}

		// Delete form sessions if there are any
		$formSessions = array_unique($formSessions);
		foreach($formSessions as $formSession) {
			$GLOBALS["ISC_CLASS_FORM"]->deleteFormSession($formSession);
		}

		return true;
	}

	/**
	 * Delete old incomplete orders
	 *
	 * @access public
	 * @return bool TRUE if all the incomplete old orders were deleted, FALSE otherwise
	 */
	public function deleteOldOrders()
	{
		$saveOrdersFor = GetConfig('AbandonOrderLifetime') * 24 * 60 * 60;
		if (!$saveOrdersFor) {
			// default to a week
			$saveOrdersFor = 7 * 24 * 60 * 60;
		}

		$query = "
			SELECT
				orderid
			FROM
				[|PREFIX|]orders
			WHERE
				ordstatus=0 AND
				orddate < '".(time() - $saveOrdersFor)."'";
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
			self::delete($row["orderid"]);

			$GLOBALS["ISC_CLASS_DB"]->DeleteQuery("gift_certificates", "WHERE giftcertorderid = " . $row["orderid"]);
		}

		return true;
	}

	public function get($orderId)
	{
		$order = parent::get($orderId);

		if (!$order) {
			return false;
		}

		$order["customer"] = false;
		if (isId($order["ordcustid"])) {

			$customer = $this->customer->get($order["ordcustid"]);
			$order["customer"] = $customer;
		}

		$order["products"] = array();

		$query = "SELECT *
					FROM [|PREFIX|]order_products
					WHERE orderorderid = " . (int)$orderId;

		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
			$prod = $this->product->get($row["ordprodid"]);
			if ($prod) {
				$prod["prodorderquantity"] = $row["ordprodqty"];
				if(GetConfig('taxDefaultTaxDisplayOrders') == TAX_PRICES_DISPLAY_INCLUSIVE) {
					$prod['prodorderamount'] = $row['price_inc_tax'];
				}
				else {
					$prod['prodorderamount'] = $row['price_ex_tax'];
				}
				$prod["prodordvariationid"] = $row["ordprodvariationid"];
				$prod["prodorderid"] = $row["orderprodid"];
				$order["products"][] = $prod;
			}
		}

		return $order;
	}

	/**
	 * Given an order ID, load the order and convert it in to a quote based off
	 * the ISC_QUOTE class.
	 *
	 * @param int $orderId The order ID to load in to a quote.
	 * @return ISC_QUOTE Quote object for the order.
	 */
	public function convertOrderToQuote($orderId, $enableDiscounts = true)
	{
		$order = GetOrder($orderId, null, null, true);
		if(!$order) {
			return false;
		}

		$quote = new ISC_QUOTE;
		$quote
		->setDiscountsEnabled($enableDiscounts)
		->setOrderId($orderId)
		->setCustomerId($order['ordcustid'])
		->setAppliedStoreCredit($order['ordstorecreditamount'])
		->setCustomerMessage($order['ordcustmessage'])
		->setStaffNotes($order['ordnotes'])
		->setOrderStatus($order['ordstatus']);

		$billingCustomFields = array();
		if($order['ordformsessionid']) {
			$billingCustomFields = $GLOBALS['ISC_CLASS_FORM']->getSavedSessionData(
			$order['ordformsessionid'],
			array(),
			FORMFIELDS_FORM_BILLING,
			true
			);
		}

		$quote->getBillingAddress()
		->setFirstName($order['ordbillfirstname'])
		->setLastName($order['ordbilllastname'])
		->setCompany($order['ordbillcompany'])
		->setEmail($order['ordbillemail'])
		->setPhone($order['ordbillphone'])
		->setAddress1($order['ordbillstreet1'])
		->setAddress2($order['ordbillstreet2'])
		->setCity($order['ordbillsuburb'])
		->setZip($order['ordbillzip'])
		->setCountryByName($order['ordbillcountry'])
		->setStateByName($order['ordbillstate'])
		->setCustomFields($billingCustomFields);

		if($order['shipping_address_count'] > 1) {
			$quote->setIsSplitShipping(true);
		}

		// Set the shipping addresses on the quote
		$query = "
			SELECT *
			FROM [|PREFIX|]order_addresses a
			LEFT JOIN [|PREFIX|]order_shipping s ON (s.order_address_id = a.id)
			WHERE a.order_id='".$order['orderid']."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		while($address = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			$shippingCustomFields = array();
			if($address['form_session_id']) {
				$shippingCustomFields = $GLOBALS['ISC_CLASS_FORM']->getSavedSessionData(
				$address['form_session_id'],
				array(),
				FORMFIELDS_FORM_SHIPPING,
				true
				);
			}
			$quoteAddress = new ISC_QUOTE_ADDRESS_SHIPPING;
			$quoteAddress
			->setQuote($quote)
			->setId($address['order_address_id'])
			->setFirstName($address['first_name'])
			->setLastName($address['last_name'])
			->setCompany($address['company'])
			->setEmail($address['email'])
			->setPhone($address['phone'])
			->setAddress1($address['address_1'])
			->setAddress2($address['address_2'])
			->setCity($address['city'])
			->setZip($address['zip'])
			->setCountryByName($address['country'])
			->setStateByName($address['state'])
			->setCustomFields($shippingCustomFields)
			->setShippingMethod($address['base_cost'], $address['method'], $address['module'], true)
			->setHandlingCost($address['base_handling_cost'], true);
			$quote->addShippingAddress($quoteAddress);
		}

		// Load any configurable fields for items on this order
		$configurableFields = array();
		$query = "
			SELECT *
			FROM [|PREFIX|]order_configurable_fields
			WHERE orderid='".$order['orderid']."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		while($configurableField = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			$quoteField = array(
				'name' => $configurableField['fieldname'],
				'type' => $configurableField['fieldtype'],
				'fileType' => $configurableField['filetype'],
				'fileOriginalName' => $configurableField['originalfilename'],
				'value' => $configurableField['textcontents']
			);
			if($quoteField['type'] == 'file') {
				$quoteField['value'] = $configurableField['filename'];
				$quoteField['isExistingFile'] = true;
			}

			$configurableFields[$configurableField['ordprodid']][$configurableField['fieldid']] = $quoteField;
		}

		// Loop through all of the items and add them to the quote
		$query = "
			SELECT *
			FROM [|PREFIX|]order_products
			WHERE orderorderid='".$order['orderid']."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		while($product = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			$variationOptions = array();
			if($product['ordprodoptions']) {
				$variationOptions = unserialize($product['ordprodoptions']);
			}

			$configuration = array();
			if(isset($configurableFields[$product['orderprodid']])) {
				$configuration = $configurableFields[$product['orderprodid']];
			}

			$itemClass = 'ISC_QUOTE_ITEM';
			$type = PT_PHYSICAL;
			if($product['ordprodtype'] == 'digital') {
				$type = PT_DIGITAL;
			}
			else if($product['ordprodtype'] == 'giftcertificate') {
				$type = PT_GIFTCERTIFICATE;
				$itemClass = 'ISC_QUOTE_ITEM_GIFTCERTIFICATE';
			}
			else if (!$product['ordprodid']) {
				$type = PT_VIRTUAL;
			}

			$quoteItem = new $itemClass;
			$quoteItem
			->setQuote($quote)
			->setName($product['ordprodname'])
			->setSku($product['ordprodsku'])
			->setId($product['orderprodid'])
			->setProductId($product['ordprodid'])
			->setQuantity($product['ordprodqty'], false)
			->setOriginalOrderQuantity($product['ordprodqty'])
			->setConfiguration($configuration)
			->setVariationId($product['ordprodvariationid'])
			->setVariationOptions($variationOptions)
			->setType($type)
			->setEventName($product['ordprodeventname'])
			->setAddressId($product['order_address_id'])
			->setBasePrice($product['base_price'], true)
			->setFixedShippingCost($product['ordprodfixedshippingcost'])
			->setWeight($product['ordprodweight'])
			->setInventoryCheckingEnabled(false);

			if ($product['applied_discounts']) {
				$appliedDiscounts = unserialize($product['applied_discounts']);
				if (!empty($appliedDiscounts)) {
					foreach ($appliedDiscounts as $discountId => $discountValue) {
						$quoteItem->addDiscount($discountId, $discountValue);
					}
				}
			}

			if($product['ordprodwrapid']) {
				$quoteItem->setGiftWrapping(
				$product['ordprodwrapid'],
				$product['base_wrapping_cost'],
				$product['ordprodwrapname'],
				$product['ordprodwrapmessage']
				);
			}

			if($product['ordprodeventdate']) {
				list($day, $month, $year) = explode('-', isc_date('d-m-Y', $product['ordprodeventdate']));
				$quoteItem->setEventDate($month, $day, $year);
			}

			$quote->addItem($quoteItem, false);
			$quoteItem->setInventoryCheckingEnabled(true);
		}

		// Add any applied coupon codes
		$query = "
			SELECT *
			FROM [|PREFIX|]order_coupons
			WHERE ordcouporderid='".$order['orderid']."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		while($coupon = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			$quoteCoupon = array(
				'id' => 0,
				'code' => $coupon['ordcouponcode'],
				'discountType' => $coupon['ordcoupontype'],
				'discountAmount' => $coupon['ordcouponamount'],
				'totalDiscount' => $coupon['applied_discount'],
			);
			$quote->addCoupon($quoteCoupon);
		}

		// Add any applied gift certificates
		$query = "
			SELECT h.*, g.giftcertcode
			FROM [|PREFIX|]gift_certificate_history h
			LEFT JOIN [|PREFIX|]gift_certificates g ON (g.giftcertid = h.histgiftcertid)
			WHERE historderid='".$order['orderid']."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		while($giftCertificate = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			$quoteGiftCertificate = array(
				'code' => $giftCertificate['giftcertcode'],
				'id' => 0,
				'amount' => $giftCertificate['histbalanceused']
			);
			$quote->addGiftCertificate($quoteGiftCertificate);
		}

		if($order['orddiscountamount'] > 0) {
			$quote->addDiscount('existing-discount', $order['orddiscountamount']);
		}

		return $quote;
	}
}
