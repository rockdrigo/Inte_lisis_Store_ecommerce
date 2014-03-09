<?php
class ISC_CART
{
	private $pageTitle = "";
	private $badCouponCode = false;
	private $badCouponMessage = "";
	private $cartErrorMessage = "";

	/**
	 * @var int The ID of the item that was just added to the cart.
	 */
	public $newCartItem = 0;

	public function __construct()
	{
		// Setup the page title
		$this->pageTitle = GetConfig('StoreName') . " - " . GetLang('ShoppingCart');

		if($this->getQuote()->getNumitems() > 0) {
			$GLOBALS['KeepShoppingText'] = GetLang('ClickHereToKeepShopping');
			$GLOBALS['KeepShoppingLink'] = $GLOBALS['ShopPath'];
		} else {
			$GLOBALS['KeepShoppingText'] = '';
			$GLOBALS['KeepShoppingLink'] = '';
		}

		if (isset($_SESSION['JustAddedProduct']) && $_SESSION['JustAddedProduct'] != '') {
			// Get the category of the last product added to the store
			$query = sprintf("select c.categoryid, catname from [|PREFIX|]categoryassociations ca inner join [|PREFIX|]categories c on ca.categoryid=c.categoryid where ca.productid='%d' ", $GLOBALS['ISC_CLASS_DB']->Quote((int)$_SESSION['JustAddedProduct']));
			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, 1);
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				if(CustomerGroupHasAccessToCategory($row['categoryid'])) {
					$GLOBALS['KeepShoppingLink'] = CatLink($row['categoryid'], $row['catname']);
					$GLOBALS['KeepShoppingText'] = sprintf(GetLang('ClickHereToKeepShoppingCat'), isc_html_escape($row['catname']));
					break;
				}
			}
		}
	}

	public function HandlePage()
	{
		$action = "";
		if (isset($_REQUEST['action'])) {
			$action = isc_strtolower($_REQUEST['action']);
		}

		$GLOBALS['AdditionalStylesheets'][] = GetConfig('AppPath').'/javascript/jquery/plugins/imodal/imodal.css';

		$routes = array(
			'add'						=> 'AddToCart',
			'addcertificate'			=> 'AddGiftCertificateToCart',
			'remove'					=> 'RemoveFromCart',
			'update'					=> 'UpdateInCart',
			'applycoupon'				=> 'ApplyCoupon',
			'applygiftcertificate'		=> 'ApplyGiftCertificate',
			'save_giftwrapping'			=> 'SaveGiftWrapping',
			'remove_giftwrapping'		=> 'RemoveGiftWrapping',
			'removegiftcertificate'		=> 'RemoveGiftCertificate',
			'editproductfieldsincart'	=> 'EditProductFieldsInCart',
			'removecoupon'				=> 'RemoveCoupon',
			'addreorderitems'			=> 'AddReorderItems'
		);

		if(isset($routes[$action])) {
			$this->$routes[$action]();
		}
		else {
			$this->showRegularCart();
		}
	}

	/**
	 * Remove the gift wrapping preferences for a particular item in the cart.
	 */
	private function RemoveGiftWrapping()
	{
		if(isset($_REQUEST['item_id']) &&
			$this->getQuote()->hasItem($_REQUEST['item_id'])) {
				try {
					$this->getQuote()
						->getItemById($_REQUEST['item_id'])
						->removeGiftWrapping();
				}
				catch(ISC_QUOTE_EXCEPTION $e) {
					flashMessage($e->getMessage(), MSG_ERROR, 'cart.php');
				}
		}

		flashMessage(getLang('GiftWrappingRemoved'), MSG_SUCCESS, 'cart.php');
	}

	/**
	 * Save the gift wrapping preferences for a particular item in the cart.
	 */
	private function SaveGiftWrapping()
	{
		if(!isset($_POST['item_id']) ||
			!$this->getQuote()->hasItem($_REQUEST['item_id'])) {
				redirect('cart.php');
		}

		try {
			$this->getQuote()
				->getItemById($_POST['item_id'])
				->applyGiftWrapping($_POST['giftwraptype'], $_POST['giftwrapping'], $_POST['giftmessage']);
		}
		catch(ISC_QUOTE_EXCEPTION $e) {
			flashMessage($e->getMessage(), MSG_ERROR, 'cart.php');
		}

		flashMessage(getLang('GiftWrappingApplied'), MSG_SUCCESS, 'cart.php');
	}

	/**
	 * Edit the custom information of the items in cart.
	 */
	private function EditProductFieldsInCart()
	{
		if(!isset($_REQUEST['item_id']) ||
			!$this->getQuote()->hasItem($_REQUEST['item_id'])) {
				redirect('cart.php');
		}

		$configurableFields = null;
		if(isset($_REQUEST['ProductFields']) || isset($_FILES['ProductFields'])) {
			$configurableFields = $this->BuildProductConfigurableFieldData();
		}

		try {
			$this->getQuote()->getItemById($_REQUEST['item_id'])
				->applyConfiguration($configurableFields);
		}
		catch(ISC_QUOTE_EXCEPTION $e) {
			flashMessage($e->getMessage(), MSG_ERROR, 'cart.php');
		}

		redirect('cart.php');
	}

	private function BuildProductConfigurableFieldData()
	{
		$configurableFields = array();
		if(isset($_REQUEST['ProductFields']) && is_array($_REQUEST['ProductFields'])) {
			$configurableFields = $_REQUEST['ProductFields'];
		}

		if(isset($_FILES['ProductFields']) && is_array($_FILES['ProductFields'])) {
			$fileFields = array_keys($_FILES['ProductFields']);
			foreach(array_keys($_FILES['ProductFields']['name']) as $fieldId) {
				$configurableFields[$fieldId] = array();
				foreach($fileFields as $field) {
					if(!isset($_FILES['ProductFields'][$field][$fieldId])) {
						continue;
					}
					$configurableFields[$fieldId][$field] = $_FILES['ProductFields'][$field][$fieldId];
				}
			}
		}
		return $configurableFields;
	}

	/**
	* Adds a simple product (no variations, configurable fields or events) to the cart
	*
	* @param mixed $product_id
	* @param mixed $qty
	*/
	public function AddSimpleProductToCart($product_id, $qty = 1)
	{
		$error = "";
		$query = "
			SELECT p.*, ".GetProdCustomerGroupPriceSQL()."
			FROM [|PREFIX|]products p
			WHERE p.productid='".(int)$product_id."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$product = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		// Check that the customer has permisison to view this product
		$canView = false;
		$productCategories = explode(',', $product['prodcatids']);
		foreach($productCategories as $categoryId) {
			// Do we have permission to access this category?
			if(CustomerGroupHasAccessToCategory($categoryId)) {
				$canView = true;
			}
		}
		if($canView == false) {
			$_SESSION['AddProductErrorMessage'] = sprintf(GetLang("NoPermissionAddProduct"), $product["prodname"]);
			return false;
		}

		try {
			$item = new ISC_QUOTE_ITEM;
			$item
				->setQuote($this->getQuote())
				->setProductId($product_id)
				->setQuantity($qty);
			$this->getQuote()->addItem($item);
		}
		catch(ISC_QUOTE_EXCEPTION $e) {
			$_SESSION['AddProductErrorMessage'] = $e->getMessage();
			return false;
		}

		return true;
	}

	private function AddToCart()
	{
		$error = false;
		$product = false;
		$product_id = false;
		$isFastCart = GetConfig('FastCartAction') == 'popup' && isset($_REQUEST['fastcart']) && GetConfig('ShowCartSuggestions');
		if(isset($_REQUEST['product_id']) && (bool)GetConfig('AllowPurchasing')) {
			$product_id = (int)$_REQUEST['product_id'];
			$query = "
				SELECT p.*, ".GetProdCustomerGroupPriceSQL()."
				FROM [|PREFIX|]products p
				WHERE p.productid='".$product_id."'
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$product = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			if (!$product) {
				$error = true;
			} else {
				$GLOBALS['ProductJustAdded'] = $product_id;
				$GLOBALS['Product'] = &$product;
			}
		} else {
			$error = true;
		}

		if ($error) {
			flashMessage(getLang('ProductUnavailableForPruchase'), MSG_ERROR);
			if ($isFastCart) {
				// dont show fast cart pop up if an error occurs
				GetClass('ISC_404')->HandlePage();
				return;
			} else {
				redirect('cart.php');
			}
		}

		// Check that the customer has permisison to view this product
		$canView = false;
		$productCategories = explode(',', $product['prodcatids']);
		foreach($productCategories as $categoryId) {
			// Do we have permission to access this category?
			if(CustomerGroupHasAccessToCategory($categoryId)) {
				$canView = true;
			}
		}
		if($canView == false) {
			$noPermissionsPage = GetClass('ISC_403');
			$noPermissionsPage->HandlePage();
			exit;
		}

		$variation = 0;
		if(isset($_REQUEST['variation_id']) && $_REQUEST['variation_id'] != 0) {
			$variation = (int)$_REQUEST['variation_id'];
		}
		// User added a variation but had javascript disabled
		else if(isset($_REQUEST['variation']) && is_array($_REQUEST['variation']) && $_REQUEST['variation'][1] != 0) {
			$variation = $_REQUEST['variation'];
		}

		$qty = 1;
		if(isset($_REQUEST['qty'])) {
			if(is_array($_REQUEST['qty'])) {
				$qty = (int)array_pop($_REQUEST['qty']);
			}
			else if($_REQUEST['qty'] > 0) {
				$qty = (int)$_REQUEST['qty'];
			}
		}

		$configurableFields = null;
		if(isset($_REQUEST['ProductFields']) || isset($_FILES['ProductFields'])) {
			$configurableFields = $this->BuildProductConfigurableFieldData();
		}

		if (isset($_REQUEST['EventDate']['Day'])) {
			$result = true;

			$eventDate = isc_gmmktime(0, 0, 0, $_REQUEST['EventDate']['Mth'],$_REQUEST['EventDate']['Day'],$_REQUEST['EventDate']['Yr']);
			$eventName = $product['prodeventdatefieldname'];

			if ($product['prodeventdatelimitedtype'] == 1) {
				if ($eventDate < $product['prodeventdatelimitedstartdate'] || $eventDate > $product['prodeventdatelimitedenddate']) {
					$result = false;
				}
			} else if ($product['prodeventdatelimitedtype'] == 2) {
				if ($eventDate < $product['prodeventdatelimitedstartdate']) {

					$result = false;
				}
			} else if ($product['prodeventdatelimitedtype'] == 3) {
				if ($eventDate > $product['prodeventdatelimitedenddate']) {
					$result = false;
				}
			}

			if ($result == false) {
				if ($isFastCart) {
					GetClass('ISC_404')->HandlePage();
					return;
				} else {
					redirect('cart.php');
				}
			}
		}

		$showMinQuantityAdjustment = false;
		if($product['prodminqty'] && $qty < $product['prodminqty']) {
			$qty = $product['prodminqty'];
			$showMinQuantityAdjustment = true;
		}

		try {
			if(!GetConfig('UsePreCart')){
				$item = new ISC_QUOTE_ITEM;
				$item
					->setQuote($this->getQuote())
					->setProductId($product_id)
					->setQuantity($qty)
					->setVariation($variation)
					->applyConfiguration($configurableFields)
					->afterAddedtoCart();
				if(!empty($_REQUEST['EventDate'])) {
					$item
						->setEventDate(
							$_REQUEST['EventDate']['Mth'],
							$_REQUEST['EventDate']['Day'],
							$_REQUEST['EventDate']['Yr'])
						->setEventName($eventName);
				}
				
				$this->getQuote()->addItem($item);
			}
			else {
				$item = new ISC_PREQUOTE_ITEM;
				$item
				->setQuote($this->getPreQuote())
				->setProductId($product_id)
				->setQuantity($qty)
				->setVariation($variation)
				->applyConfiguration($configurableFields)
				->afterAddedtoCart();
				if(!empty($_REQUEST['EventDate'])) {
					$item
					->setEventDate(
							$_REQUEST['EventDate']['Mth'],
							$_REQUEST['EventDate']['Day'],
							$_REQUEST['EventDate']['Yr'])
							->setEventName($eventName);
				}
				
				if(!$item->setStoreOrigin()){
					throw new ISC_QUOTE_EXCEPTION(GetLang('StoreOriginNoStock'));
				}
				
				$this->getPreQuote()->addItem($item);
			}

		}
		catch(ISC_QUOTE_EXCEPTION $e) {
			if ($isFastCart) {
				GetClass('ISC_404')->HandlePage();
				return;
			}

			if($e->getCode() == ISC_QUOTE_EXCEPTION::ERROR_NO_STOCK && $showMinQuantityAdjustment) {
				flashMessage(getLang('CannotAddMinQuantityToCart', array(
					'minqty' => $qty,
					'product' => $product['prodname']
				)), MSG_ERROR, prodLink($product['prodname']));
			}
			else {
				flashMessage($e->getMessage(), MSG_ERROR, prodLink($product['prodname']));
			}
		}

		if($showMinQuantityAdjustment) {
			flashMessage(getLang('AddToCartMinimumQuantityNotice', array(
				'product' => $product['prodname'],
				'qty' => $product['prodminqty'])), MSG_INFO);
		}

		$_SESSION['JustAddedProduct'] = $product_id;

		// Are we redirecting to a specific location?
		if(isset($_REQUEST['returnUrl'])) {
			$redirectLocation = urldecode($_REQUEST['returnUrl']);
			$urlPieces = @parse_url($redirectLocation);
			$storeUrlPieces = @parse_url(GetConfig('ShopPath'));
			if(is_array($urlPieces) && isset($urlPieces['host'])) {
				$urlHost = str_replace('www.', '', isc_strtolower($urlPieces['host']));
				$storeHost = str_replace('www.', '', isc_strtolower($storeUrlPieces['host']));
				if($urlHost == $storeHost) {
					if(strpos($redirectLocation, '?') === false) {
						$redirectLocation .= '?';
					}
					else {
						$redirectLocation .= '&';
					}
					$redirectLocation .= 'justAddedProduct='.$product_id;
					redirect($redirectLocation);
				}
			}
		}

		// Show the new contents of the cart
		$url = 'cart.php';
		if (GetConfig('ShowCartSuggestions')) {
			$url .= '?suggest='.$item->getId();
		}

		if ($isFastCart) {
			$this->_setupFastCartData($this->getQuote(), $item);
			echo $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('FastCartThickBoxContent');
		} else {
			redirect($url);
		}
	}

	private function _setupFastCartData($quote, $item)
	{
		$GLOBALS['FlashMessages'] = getFlashMessageBoxes();
		
		// product info
		$name = $item->getName();
		$prodLink = '<a href="'.prodLink($name).'">'.$name.'</a>';
		$prodImg = imageThumb($item->getThumbnail(), prodLink($name));
		$prodTotal = CurrencyConvertFormatPrice($item->getTotal());

		// x item(s) has/have been added
		$quantity = $item->getQuantity();
		$quantityTxt = GetLang('OneItemAdded');
		if ($quantity > 1) {
			$quantityTxt = GetLang('XItemsAdded', array('count' => $quantity));
		}

		// your cart contains x item(s)
		$numItems = $quote->getNumItems();
		$numItemsTxt = GetLang('OneItem');
		if ($numItems > 1) {
			$numItemsTxt = GetLang('XItems', array('count' => $numItems));
		}

		// variation?
		$options = $item->getVariationOptions();
		if(!empty($options)) {
			$holder = array();
			foreach($options as $name => $value) {
				if(!trim($name) || !trim($value)) {
					continue;
				}

				$holder[] = isc_html_escape($name).': '.isc_html_escape($value);
			}

			$prodLink .= ' <small>('.implode(', ', $holder).')</small>';
		}

		// setup suggestive content
		$GLOBALS['HideSuggestiveCartContent'] = 'display:none';
		if (GetConfig('ShowCartSuggestions')) {
			$GLOBALS['HideSuggestiveCartContent'] = '';
			$GLOBALS['SuggestiveCartContentLimit'] = 4;
			$GLOBALS['ISC_CLASS_TEMPLATE']->GetPanelContent('SuggestiveCartContent');
		}

		// setup buttons
		ob_start();
			$this->showRegularCart();
		ob_end_clean();

		$GLOBALS['fastCartProdImg'] = $prodImg;
		$GLOBALS['fastCartProdLink'] = $prodLink;
		$GLOBALS['fastCartProdTotal'] = $prodTotal;
		$GLOBALS['fastCartQuantity'] = $quantity;
		$GLOBALS['fastCartQuantityTxt'] = $quantityTxt;
		$GLOBALS['fastCartNumItemsTxt'] = $numItemsTxt;
		$GLOBALS['fastCartSubtotal'] = CurrencyConvertFormatPrice($quote->getSubTotal());
	}

	private function RemoveFromCart()
	{
		if(isset($_GET['item'])) {
			try {
				if(!GetConfig('UsePrecart') && $this->getQuote()->hasItem($_GET['item'])) {
					$this->getQuote()
						->removeItem($_GET['item']);
				}
				elseif($this->getPreQuote()->hasItem($_GET['item'])){
					$this->getPreQuote()
					->removeItem($_GET['item']);
				}
			}
			catch(ISC_QUOTE_EXCEPTION $e) {
				flashMessage($e->getMessage(), MSG_ERROR, 'cart.php');
			}
		}

		flashMessage(getLang('CartUpdated'), MSG_SUCCESS, 'cart.php');
	}

	private function UpdateInCart()
	{
		// Just selected a shipping method
		if(isset($_REQUEST['selectedShippingMethod'])) {
			$id = $_REQUEST['selectedShippingMethod'];

			// Legancy - Vendor template
			if(isset($id[0])) {
				$id = $id[0];
			}

			// Make sure split shipping is disabled here - it doesn't work on
			// the cart pages.
			$this->getQuote()->SetIsSplitShipping(false);
			$shippingAddress = $this->getQuote()->getShippingAddress(0);
			$cachedShippingMethod = $shippingAddress->getCachedShippingMethod($id);

			if(!empty($cachedShippingMethod)) {
				$shippingAddress->setShippingMethod(
					$cachedShippingMethod['price'],
					$cachedShippingMethod['description'],
					$cachedShippingMethod['module']
				);
				$shippingAddress->setHandlingCost($cachedShippingMethod['handling']);
			}
			$shippingAddress->removeCachedShippingMethods();
		}

		if(!empty($_REQUEST['qty']) && is_array($_REQUEST['qty'])) {
			foreach($_REQUEST['qty'] as $itemId => $quantity) {
				try {
					if(!$this->getQuote()->hasItem($itemId)) {
						continue;
					}

					// if the quantity updated to 0, then remove it from cart
					if (empty ($quantity)) {
						$this->getQuote()
						->removeItem($itemId);
					} else {
						$item = $this->getQuote()->getItemById($itemId);
						if(!$item->getParentId()) {
							$item->setQuantity($quantity);
						}
					}
				}
				catch(ISC_QUOTE_EXCEPTION $e) {
					flashMessage($e->getMessage(), MSG_ERROR);
				}
			}
		}

		// update the coupon code, if there are any applied, as shipping method
		// changed affect the coupon amount.
		try {
			$this->getQuote()
			->reapplyCoupons(true);
		}
		catch(ISC_QUOTE_EXCEPTION $e) {
			flashMessage($e->getMessage(), MSG_ERROR);
		}

		if(empty($_REQUEST['selectedShippingMethod'])) {
			flashMessage(getLang('CartUpdated'), MSG_SUCCESS);
		}
		
		ReapplyPyC();

		redirect('cart.php');
	}

	private function ShowRegularCart()
	{
		if(isset($_GET['error']))
		{
			if($_GET['error'] == 'afterhours') {
				$GLOBALS['ClosedStore'] = GetLang('ClosedStore');
				$StoreHoursFrom = str_pad(GetConfig('StoreHoursFromHours'), 2, "0", STR_PAD_LEFT).":".str_pad(GetConfig('StoreHoursFromMinutes'), 2, "0", STR_PAD_LEFT);
				$StoreHoursTo = str_pad(GetConfig('StoreHoursToHours'), 2, "0", STR_PAD_LEFT).":".str_pad(GetConfig('StoreHoursToMinutes'), 2, "0", STR_PAD_LEFT);
				$GLOBALS['ClosedStoreHelp'] = sprintf(GetLang('ClosedStoreHelp'), $StoreHoursFrom, $StoreHoursTo);
			}
		}
		unset($_SESSION['IsCheckingOut']);

		// suggestive cart functionality
		$productId = 0;
		if(isset($_REQUEST['suggest']) &&
			$this->getQuote()->hasItem($_REQUEST['suggest'])) {
				$item = $this->getQuote()->getItemById($_REQUEST['suggest']);
				$this->newCartItem = $_REQUEST['suggest'];
				$productId = $item->getProductId();
		}

		if ($productId > 0) {
			$query = sprintf("
				SELECT * FROM
				[|PREFIX|]products
				WHERE productid='%d'
			", $GLOBALS['ISC_CLASS_DB']->Quote($productId));

			$Result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$product = $GLOBALS['ISC_CLASS_DB']->Fetch($Result);
			$GLOBALS['Product'] = $product;

			$GLOBALS['ProductJustAdded'] = $productId;
		} else {
			$GLOBALS['ProductJustAdded'] = false;
		}

		// Are gift certificates disabled?
		if (GetConfig('EnableGiftCertificates') == 0) {
			$GLOBALS['HidePanels'][] = "SideGiftCertificateCodeBox";
		}

		// Was a coupon code applied successfully?
		if (!isset($_GET['coupon_applied'])) {
			// Nope, so hide the message
			$GLOBALS['HideCartCouponAppliedPanel'] = "none";
		}

		if ($this->getQuote()->getNumItems() == 0) {
			$GLOBALS['HideCheckoutButton'] = "none";
		}

		// Show the regular shopping cart page
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle($this->GetPageTitle());
		if(!GetConfig('UsePreCart')){
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("cart");
		}
		else {
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("precart");
		}
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}

	private function GetPageTitle()
	{
		return $this->pageTitle;
	}

	public function ApplyGiftCertificate()
	{
		if(empty($_REQUEST['giftcertificatecode'])) {
			redirect('cart.php');
		}

		try {
			$this->getQuote()
				->applyGiftCertificate($_REQUEST['giftcertificatecode']);
			flashMessage(getLang('GiftCertificateAppliedToCart'), MSG_SUCCESS, 'cart.php');
		}
		catch(ISC_QUOTE_EXCEPTION $e) {
			flashMessage($e->getMessage(), MSG_ERROR, 'cart.php');
		}
	}

	private function RemoveGiftCertificate()
	{
		if(empty($_REQUEST['giftcertificateid'])) {
			redirect('cart.php');
		}

		try {
			$this->getQuote()
				->removeGiftCertificateById($_REQUEST['giftcertificateid']);
			flashMessage(getLang('GiftCertificateRemovedFromCart'), MSG_SUCCESS, 'cart.php');
		}
		catch(ISC_QUOTE_EXCEPTION $e) {
			flashMessage($e->getMessage(), MSG_ERROR, 'cart.php');
		}
	}

	private function ApplyCoupon()
	{
		if(empty($_POST['couponcode'])) {
			redirect('cart.php');
		}

		try {
			$this->getQuote()
				->applyCoupon($_POST['couponcode']);
			FlashMessage(GetLang('CouponAppliedToCart'), MSG_SUCCESS, 'cart.php?coupon_applied=true');
		}
		catch(ISC_QUOTE_EXCEPTION $e) {
			flashMessage($e->getMessage(), MSG_ERROR, 'cart.php');
		}
	}

	private function RemoveCoupon()
	{
		if(empty($_REQUEST['couponid'])) {
			redirect('cart.php');
		}

		try {
			$this->getQuote()
				->removeCouponById($_REQUEST['couponid']);
			flashMessage(getLang('CouponRemovedFromCart'), MSG_SUCCESS, 'cart.php');
		}
		catch(ISC_QUOTE_EXCEPTION $e) {
			flashMessage($e->getMessage(), MSG_ERROR, 'cart.php');
		}
	}

	private function ValidateReorder()
	{
		if(empty($_REQUEST['orderid'])) {
			flashMessage(getLang('InvalidOrderId'), MSG_ERROR, 'cart.php');
		}

		$customerId = getClass('ISC_CUSTOMER')->getCustomerId();
		if(!$customerId) {
			flashMessage(getLang('MustBeLoggedInToReorder'), MSG_ERROR, 'cart.php');
		}

		// Was this order placed by the same customer?
		$order = getOrder($_REQUEST['orderid']);
		if($order['ordcustid'] != $customerId) {
			flashMessage(getLang('InvalidOrderId'), MSG_ERROR, 'cart.php');
		}
	}

	private function AddReorderItems()
	{
		$this->ValidateReorder();
		if (isset($_REQUEST['reorderitem'])) {
			$OrdProdIds = implode(',', array_keys($_REQUEST['reorderitem']));
			$QueryWhere = "op.orderprodid IN (".$GLOBALS['ISC_CLASS_DB']->Quote($OrdProdIds).")";
		} else if (isset($_REQUEST['orderid'])) {
			$QueryWhere = "op.orderorderid = ".(int)$_REQUEST['orderid'];
		}

		$orderItems = array();

		// Grab any configurable fields
		$configurableFields = array();
		$query = "
			SELECT ocf.*, op.orderprodid
			FROM [|PREFIX|]order_configurable_fields ocf
			JOIN [|PREFIX|]order_products op ON (op.orderprodid = ocf.ordprodid)
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		while($field = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			if(!isset($configurableFields['ordprodid'])) {
				$configurableFields[$field['orderprodid']] = array();
			}

			$configurableFields[$field['orderprodid']][] = $field;
		}

		$query = "
			SELECT *
			FROM [|PREFIX|]order_products op
			WHERE ".$QueryWhere;
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		while($row = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			try {
				$quote = getCustomerQuote();
				$item = new ISC_QUOTE_ITEM;
				$item
					->setQuote($quote)
					->setProductId($row['ordprodid'])
					->setQuantity($row['ordprodqty'])
					->setVariation($row['ordprodvariationid'])
				;
				if($row['ordprodeventdate']) {
					$item
						->setEventDate($row['ordprodeventdate'])
						->setEventName($row['ordprodeventname']);
				}

				if($row['ordprodwrapid']) {
					$wrappingOptions = array(
						'all' => $row['ordprodwrapid']
					);
					$wrappingMessage = array(
						'all' => $row['ordprodwrapmessage']
					);
					$item->applyGiftWrapping('same', $wrappingOptions, $wrappingMessage);
				}

				$configuredFields = array();
				if(!empty($configurableFields[$row['orderprodid']])) {
					$configuration = $configurableFields[$row['orderprodid']];
					foreach($configuration as $field) {
						if($field['fieldtype'] == 'file') {
							$filePath = ISC_BASE_PATH.'/'.getConfig('ImageDirectory').'/configured_products/'.$field['filename'];
							$fileTmpPath = ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/configured_products_tmp/'.$field['filename'];

							// Copy the field to the temp directory
							if(!@copy($filePath, $fileTmpPath)) {
								flashMessage(getLang('ConfigurableFileCantBeMoved'), MSG_ERROR, 'cart.php');
							}

							// Add it to the configuration
							$configuredFields[$field['fieldid']] = array(
								'name' => $field['originalfilename'],
								'type' => $field['filetype'],
								'size' => filesize($filePath),
								'existingPath' => $fileTmpPath,
							);
						}
						else {
							$configuredFields[$field['fieldid']] = $field['textcontents'];
						}
					}

					$item->applyConfiguration($configuredFields);
				}

				$quote->addItem($item);
			}
			catch(ISC_QUOTE_EXCEPTION $e) {
				flashMessage($e->getMessage(), MSG_ERROR);
				$hasErrors = true;
			}
		}

		redirect('cart.php');
	}

	protected function getQuote()
	{
		return getCustomerQuote();
	}
	
	protected function getPreQuote()
	{
		return getCustomerPreQuote();
	}
}
