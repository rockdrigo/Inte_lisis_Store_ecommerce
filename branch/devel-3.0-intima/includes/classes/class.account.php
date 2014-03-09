<?php
require_once(ISC_BASE_PATH . '/lib/addressvalidation.php');

	class ISC_ACCOUNT
	{
		private $shippingEntity;
		private $customerEntity;

		public function __construct()
		{
			$this->shippingEntity = new ISC_ENTITY_SHIPPING();
			$this->customerEntity = new ISC_ENTITY_CUSTOMER();
		}

		public function HandlePage()
		{
			$action = "";
			if (isset($_REQUEST['action'])) {
				$action = isc_strtolower($_REQUEST['action']);
			}

			if (isset($_GET['from'])) {
				$_GET['from'] = str_replace(array("\n", "\r", "\r\n", "\t"), "", $_GET['from']);
				$_SESSION['LOGIN_REDIR'] = sprintf("%s/%s", $GLOBALS['ShopPath'], urldecode($_GET['from']));
			}

			if ($action === "download_item") {
				$this->DownloadOrderItem();
				return;
			}

			// Are they signed in?
			if (CustomerIsSignedIn()) {
				$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
				$customer = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerDataByToken();
				if ($customer['custstorecredit'] == 0) {
					$GLOBALS['HidePanels'][] = "SideAccountStoreCredit";
				}

				switch ($action) {
					case "send_message": {
						$this->SendMessage();
						break;
					}
					case "save_new_shipping_address": {
						$this->SaveNewShippingAddress();
						break;
					}
					case "add_shipping_address": {
						$this->AddShippingAddress();
						break;
					}
					case "edit_shipping_address": {
						$this->EditShippingAddress();
						break;
					}
					case "update_new_shipping_address": {
						$this->SaveEditedShippingAddress();
						break;
					}
					case "delete_shipping_address": {
						$this->DeleteShippingAddress();
						break;
					}
					case "inbox": {
						$this->Inbox();
						break;
					}
					case "order_status": {
						$this->OrderStatus();
						break;
					}
					case "view_orders": {
						$this->ViewOrders();
						break;
					}
					case "view_order": {
						$this->ViewOrderDetails();
						break;
					}
					case "download_item": {
						$this->DownloadOrderItem();
						break;
					}
					case "print_invoice": {
						$this->PrintInvoice();
						break;
					}
					case "address_book": {
						$this->AddressBook();
						break;
					}
					case "account_details": {
						$this->EditAccount();
						break;
					}
					case "update_account": {
						$this->SaveAccountDetails();
						break;
					}
					case "recent_items": {
						$this->ShowRecentItems();
						break;
					}
					case "new_return": {
						$this->NewReturn();
						break;
					}
					case "save_new_return": {
						$this->SaveNewReturn();
						break;
					}
					case "view_returns": {
						$this->ShowReturns();
						break;
					}
					case "reorder": {
						$this->DoReorder();
						break;
					}
					default: {
						$this->MyAccountPage();
					}
				}
			}
			else {
				// Naughty naughty, you need to sign in to be here
				$this_page = urlencode(sprintf("account.php?action=%s", $action));
				ob_end_clean();
				header(sprintf("Location: %s/login.php?from=%s", $GLOBALS['ShopPath'], $this_page));
				die();
			}
		}

		/**
		*	Get all returns for this customer. If $OnlyCompletedReturns is true then we will only
		*	return orders whose status is completed/denied
		*/
		private function GetCustomerReturns(&$Result, $OnlyCompletedReturns = false)
		{

			if ($OnlyCompletedReturns) {
				$complete_filter = "and (retstatus='4' or retstatus='5')";
			} else {
				$complete_filter = "";
			}

			$query = sprintf("
				SELECT r.*, p.prodname AS currentprodname
				FROM [|PREFIX|]returns r
				LEFT JOIN [|PREFIX|]products p ON (p.productid=r.retprodid)
				WHERE retcustomerid='%d' %s
				ORDER BY retdaterequested DESC",
				$GLOBALS['ISC_CLASS_DB']->Quote($GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId()), $complete_filter);
			$Result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		}

		/**
		* Show the returns/refunds a user has placed with the store
		*/
		private function ShowReturns()
		{
			if (!gzte11(ISC_LARGEPRINT)) {
				ob_end_clean();
				header("Location: " . $GLOBALS['ShopPath']);
				die();
			}

			if (GetConfig('EnableReturns') == 0) {
				// Bad details
				ob_end_clean();
				header(sprintf("location:%s/account.php", $GLOBALS['ShopPath']));
				die();
			}

			$GLOBALS['SNIPPETS']['AccountReturns'] = "";
			$GLOBALS['AccountReturnItemList'] = "";

			$result = false;
			$this->GetCustomerReturns($result);

			// Are there any orders for this customer
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$GLOBALS['DateRequested'] = isc_date(GetConfig('DisplayDateFormat'), $row['retdaterequested']);
				$GLOBALS['ReturnId'] = $row['returnid'];

				switch ($row['retstatus']) {
					case 2:
						$status = 'ReturnStatusReceived';
						break;
					case 3:
						$status = 'ReturnStatusAuthorized';
						break;
					case 4:
						$status = 'ReturnStatusRepaired';
						break;
					case 5:
						$status = 'ReturnStatusRefunded';
						break;
					case 6:
						$status = 'ReturnStatusRejected';
						break;
					case 7:
						$status = 'ReturnStatusCancelled';
						break;
					default:
						$status = 'ReturnStatusPending';
						break;
				}
				$GLOBALS['ReturnStatus'] = GetLang($status);

				if ($row['currentprodname']) {
					$GLOBALS['ReturnedProduct'] = "<a href='".ProdLink($row['currentprodname'])."'>".isc_html_escape($row['retprodname'])."</a>";
				}
				else {
					$GLOBALS['ReturnedProduct'] = isc_html_escape($row['retprodname']);
				}

				$GLOBALS['ReturnedProductOptions'] = '';
				if($row['retprodoptions'] != '') {
					$options = @unserialize($row['retprodoptions']);
					if(!empty($options)) {
						$GLOBALS['ReturnedProductOptions'] = " <small>(";
						$comma = '';
						foreach($options as $name => $value) {
							$GLOBALS['ReturnedProductOptions'] .= $comma.isc_html_escape($name).": ".isc_html_escape($value);
							$comma = ', ';
						}
						$GLOBALS['ReturnedProductOptions'] .= ")</small>";
					}
				}

				if ($row['retprodqty'] > 1) {
					$GLOBALS['ReturnedQuantity'] = (int)$row['retprodqty'] . " x ";
				}
				else {
					$GLOBALS['ReturnedQuantity'] = "";
				}

				$GLOBALS['ReturnReason'] = isc_html_escape($row['retreason']);
				if ($row['retaction'] != "") {
					$GLOBALS['ReturnAction'] = isc_html_escape($row['retaction']);
					$GLOBALS['HideReturnAction'] = '';
				}
				else {
					$GLOBALS['ReturnAction'] = '';
					$GLOBALS['HideReturnAction'] = 'none';
				}

				$GLOBALS['ReturnComments'] = nl2br(isc_html_escape($row['retcomment']));

				if ($GLOBALS['ReturnComments'] == '') {
					$GLOBALS['HideReturnComment'] = 'none';
				}
				else {
					$GLOBALS['HideReturnComment'] = '';
				}

				$GLOBALS['SNIPPETS']['AccountReturns'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("AccountReturnItem");
				$GLOBALS['AccountReturnItemList'] = "";
			}

			if ($GLOBALS['SNIPPETS']['AccountReturns'] != "") {
				$GLOBALS['HideNoReturnsMessage'] = "none";
			}
			else {
				$GLOBALS['HideReturnsList'] = "none";
			}

			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(sprintf("%s - %s", GetConfig('StoreName'), GetLang('YourReturns')));
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("account_returns");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}

		private function NewReturn($errors="")
		{
			if (!gzte11(ISC_LARGEPRINT)) {
				ob_end_clean();
				header("Location: " . $GLOBALS['ShopPath']);
				die();
			}

			if (!isset($_REQUEST['order_id']) || GetConfig('EnableReturns') == 0) {
				// Bad details
				ob_end_clean();
				header(sprintf("location:%s/account.php", $GLOBALS['ShopPath']));
				die();
			}

			// Fetch the order
			$query = sprintf("SELECT * FROM [|PREFIX|]orders WHERE orderid='%d' AND (ordstatus=2 OR ordstatus=10) AND ordcustid=%d AND deleted = 0", $GLOBALS['ISC_CLASS_DB']->Quote((int)$_REQUEST['order_id']), $GLOBALS['ISC_CLASS_DB']->Quote($GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId()));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			$order = $row;

			$GLOBALS['OrderId'] = $row['orderid'];
			$GLOBALS['ISC_LANG']['SubmitNewReturn'] = sprintf(GetLang('SubmitNewReturn'), $row['orderid']);
			$GLOBALS['ISC_LANG']['OrderId'] = sprintf(GetLang('OrderId'), $row['orderid']);

			if (!$row['orderid']) {
				// Bad details
				ob_end_clean();
				header(sprintf("location:%s/account.php", $GLOBALS['ShopPath']));
				die();
			}
			$GLOBALS['SNIPPETS']['ReturnProducts'] = '';
			$count = 0;

			$return_products = array();
			$products = array();

			// Fetch the list of items in this order and the number that have already been returned
			$query = "
				SELECT
					IFNULL(SUM(r.retprodqty), 0) AS numreturned,
					p.*
				FROM
					[|PREFIX|]order_products p
					LEFT JOIN [|PREFIX|]returns r ON (r.retorderid = p.orderorderid AND r.retprodid = p.ordprodid AND r.retprodvariationid = p.ordprodvariationid)
				WHERE
					p.orderorderid='".$order['orderid']."'
				GROUP BY
					p.ordprodid,
					p.ordprodvariationid
			";

			// Fetch a list of items in this order that haven't already got a pending return request
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while ($product = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$products[] = $product;
			}

			$itemPriceColumn = 'price_ex_tax';
			if(getConfig('taxDefaultTaxDisplayOrders') == TAX_PRICES_DISPLAY_INCLUSIVE) {
				$itemPriceColumn = 'price_inc_tax';
			}

			foreach ($products as $row) {
				if ($row['ordprodtype'] == 'giftcertificate') {
					continue;
				}

				$row['returnable'] = $row['ordprodqty'] - $row['numreturned'];
				if ($row['returnable'] <= 0) {
					continue;
				}
				if ($count++ % 2 != 0) {
					$GLOBALS['ItemClass'] = "OrderItem2";
				} else {
					$GLOBALS['ItemClass'] = "OrderItem1";
				}

				$GLOBALS['OrderProductId'] = $row['orderprodid'];
				$GLOBALS['ProductName'] = isc_html_escape($row['ordprodname']);
				$GLOBALS['ProductId'] = $row['ordprodid'];
				$GLOBALS['ProductPrice'] = CurrencyConvertFormatPrice($row[$itemPriceColumn], $order['ordcurrencyid'], $order['ordcurrencyexchangerate']);

				// If there were any options with the product, we need to show them too
				$GLOBALS['ProductOptions'] = '';
				if($row['ordprodoptions'] != '') {
					$options = @unserialize($row['ordprodoptions']);
					if(!empty($options)) {
						$GLOBALS['ProductOptions'] = "<br /><small>(";
						$comma = '';
						foreach($options as $name => $value) {
							$GLOBALS['ProductOptions'] .= $comma.isc_html_escape($name).": ".isc_html_escape($value);
							$comma = ', ';
						}
						$GLOBALS['ProductOptions'] .= ")</small>";
					}
				}

				// Remaining quantity that can be returned
				$GLOBALS['ProductQty'] = $row['returnable'];

				$GLOBALS['ProductQtySelect'] = '';
				for ($i = 0; $i <= $GLOBALS['ProductQty']; ++$i) {
					$GLOBALS['ProductQtySelect'] .= sprintf("<option value='%s'>%s</option>", $i, $i);
				}

				$GLOBALS['SNIPPETS']['ReturnProducts'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("AccountNewReturnItem");
			}

			$GLOBALS['HideReturnForm'] = "";
			if (!$GLOBALS['SNIPPETS']['ReturnProducts']) {
				if (!$errors) {
					$errors = GetLang('ReturnNoItems');
				}
				$GLOBALS['HideReturnForm'] = "none";
			}

			$GLOBALS['HideErrorMessage'] = 'none';
			if (is_array($errors)) {
				$errors = implode("<br />", $errors);
			}
			if ($errors != "") {
				$GLOBALS['HideErrorMessage'] = '';
				$GLOBALS['ErrorMessage'] = $errors;
			}

			// Generate a list of return reasons
			$GLOBALS['ReturnReasonsList'] = '';
			if (is_array(GetConfig('ReturnReasons'))) {
				foreach (GetConfig('ReturnReasons') as $reason) {
					$GLOBALS['ReturnReasonsList'] .= sprintf("<option value='%s'>%s</option>", $reason, $reason);
				}
			}

			// Generate a list of return actions if we have any
			$GLOBALS['ReturnActionsList'] = '';
			$GLOBALS['HideReturnAction'] = 'none';
			if (is_array(GetConfig('ReturnActions'))) {
				foreach (GetConfig('ReturnActions') as $action) {
					$GLOBALS['ReturnActionsList'] .= sprintf("<option value='%s'>%s</option>", $action, $action);
					$GLOBALS['HideReturnAction'] = '';
				}
			}

			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(sprintf("%s - %s", GetConfig('StoreName'), GetLang('NewReturn')));
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("account_new_return");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}

		private function SaveNewReturn()
		{
			if (!gzte11(ISC_LARGEPRINT)) {
				ob_end_clean();
				header("Location: " . $GLOBALS['ShopPath']);
				die();
			}

			if (!isset($_REQUEST['order_id']) || GetConfig('EnableReturns') == 0) {
				// Bad details
				ob_end_clean();
				header(sprintf("location:%s/account.php", $GLOBALS['ShopPath']));
				die();
			}

			// Fetch the order
			$query = sprintf("SELECT * FROM [|PREFIX|]orders WHERE orderid='%d' AND (ordstatus=2 OR ordstatus=10) AND ordcustid=%d AND deleted = 0", $GLOBALS['ISC_CLASS_DB']->Quote((int)$_REQUEST['order_id']), $GLOBALS['ISC_CLASS_DB']->Quote($GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId()));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			$order = $row;

			if (!$row['orderid']) {
				// Bad details
				ob_end_clean();
				header(sprintf("location:%s/account.php", $GLOBALS['ShopPath']));
				die();
			}

			$return_products = array();

			if(isset($_POST['return_qty'])) {
				$order_product_ids = '';
				foreach($_POST['return_qty'] as $orderprodid => $qty) {
					if($qty <= 0) {
						continue;
					}
					if($order_product_ids != '') {
						$order_product_ids .= ",";
					}
					$order_product_ids .= (int)$orderprodid;
				}

				// Fetch the list of items in this order and the number that have already been returned
				$query = "
					SELECT COUNT(r.returnid) AS numreturned, ordprodqty, p. *
					FROM [|PREFIX|]order_products p
					LEFT JOIN [|PREFIX|]returns r ON (r.retorderid=p.orderorderid AND r.retprodid=p.ordprodid AND r.retprodvariationid=p.ordprodvariationid)
					WHERE p.orderorderid='".$order['orderid']."' AND p.orderprodid IN (".$order_product_ids.")
					GROUP BY p.ordprodid, p.ordprodvariationid, r.retprodid
				";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				while ($product = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					if ($product['ordprodtype'] == 'giftcertificate') {
						continue;
					}

					$returnable = $product['ordprodqty'] - $product['numreturned'];
					if($returnable <= 0 || $_POST['return_qty'][$product['orderprodid']] > $returnable) {
						// User is trying to return too many
						unset($_POST['return_qty']);
						continue;
					}
					$return_products[] = array(
						"retprodid" => $product['ordprodid'],
						"retprodname" => $product['ordprodname'],
						"retprodcost" => $product['price_inc_tax'],
						"retprodqty" => $_POST['return_qty'][$product['orderprodid']],
						"retordprodid" => $product['orderprodid'],
						"retprodvariationid" => $product['ordprodvariationid'],
						"retprodoptions" => $product['ordprodoptions']
					);
				}
			}

			$errors = array();

			if (empty($return_products)) {
				$errors[] = GetLang('SelectOneMoreItemsReturn');
			}

			if (!isset($_POST['return_reason']) && $_POST['return_reason'] != "") {
				$errors[] = GetLang('SelectReturnReason');
			}

			if (!isset($_POST['return_action'])) {
				$_POST['return_action'] = '';
			}

			if (!isset($_POST['return_comments'])) {
				$_POST['return_comments'] = '';
			}

			if (is_array($errors) && !empty($errors)) {
				$this->NewReturn($errors);
			}
			// Everything looks good, so create the return
			else {
				$GLOBALS['ISC_CLASS_DB']->Query("START TRANSACTION");

				foreach ($return_products as $product) {
					$new_return = array(
						"retorderid" => (int)$_POST['order_id'],
						"retcustomerid" => (int)$GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId(),
						"retprodid" => (int)$product['retprodid'],
						"retprodname" => $product['retprodname'],
						"retprodcost" => $product['retprodcost'],
						"retprodqty" => $product['retprodqty'],
						"retstatus" => 1,
						"retreason" => $_POST['return_reason'],
						"retaction" => $_POST['return_action'],
						"retdaterequested" => time(),
						"retcomment" => $_POST['return_comments'],
						"retuserid" => 0,
						"retreceivedcredit" => 0,
						"retordprodid" => $product['retordprodid'],
						"retstaffnotes" => "",
						"retprodvariationid" => $product['retprodvariationid'],
						"retprodoptions" => $product['retprodoptions'],
						"retvendorid" => $order['ordvendorid']
					);
					$return_id = $GLOBALS['ISC_CLASS_DB']->InsertQuery("returns", $new_return);
				}

				// Successfully created the returns
				if ($GLOBALS['ISC_CLASS_DB']->GetErrorMsg() == "") {
					$GLOBALS['ISC_CLASS_DB']->Query("COMMIT");

					$GLOBALS['OrderId'] = $order['orderid'];
					$GLOBALS['ISC_LANG']['SubmitNewReturn'] = sprintf(GetLang('SubmitNewReturn'), $order['orderid']);
					$GLOBALS['ISC_LANG']['OrderId'] = sprintf(GetLang('OrderId'), $order['orderid']);

					$GLOBALS['ReturnInstructions'] = nl2br(GetConfig('ReturnInstructions'));

					if ($GLOBALS['ReturnInstructions'] == "") {
						$GLOBALS['HideReturnInstructions'] = "none";
					}

					$new_return['returnid'] = $return_id;

					require_once APP_ROOT."/admin/includes/classes/class.returns.php";
					$GLOBALS['ISC_CLASS_ADMIN_RETURNS'] = GetClass('ISC_ADMIN_RETURNS');

					// Do we need to notify the store owner?
					if (GetConfig('EmailOwnerOnReturn')) {
						$GLOBALS['ISC_CLASS_ADMIN_RETURNS']->SendNewReturnNotification($new_return, $return_products);
					}

					// Sending the customer a confirmation?
					if (GetConfig('SendReturnConfirmation')) {
						$GLOBALS['ISC_CLASS_ADMIN_RETURNS']->SendReturnConfirmation($new_return, $return_products);
					}

					$GLOBALS['ISC_LANG']['ReturnSubmittedInfo'] = sprintf(GetLang('ReturnSubmittedInfo'), $GLOBALS['StoreName']);

					$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(sprintf("%s - %s", GetConfig('StoreName'), GetLang('NewReturn')));
					$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("account_saved_return");
					$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
				}

				// Failed to insert
				else {
					$errors[] = GetLang('ErrorSavingReturn');
					$this->NewReturn($errors);
					$GLOBALS['ISC_CLASS_DB']->Query("ROLLBACK");
				}
			}
		}


		/**
		*	Save the changes made to an existing shipping address
		*/
		private function SaveEditedShippingAddress()
		{
			if (isset($_POST['shipid'])) {

				$shippingData = $this->shippingEntity->get($_POST['shipid']);
				$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ADDRESS, true);

				/**
				 * Validate the field input
				 */
				$errmsg = '';
				if (!validateFieldData($fields, $errmsg)) {
					$_GET['address_id'] = $_POST['shipid'];
					return $this->EditShippingAddress($errmsg, MSG_ERROR);
				}

				$ShippingAddress = parseFieldData($fields, $shippingData['shipformsessionid']);

				// Update the existing shipping address
				$ShippingAddress['shipid'] = (int)$_POST['shipid'];

				if ($this->shippingEntity->edit($ShippingAddress)) {
					if (isset($_SESSION['LOGIN_REDIR'])) {
						// Take them to the page they wanted
						$page = $_SESSION['LOGIN_REDIR'];
						unset($_SESSION['LOGIN_REDIR']);
						header(sprintf("Location: %s", $page));
					}
					else {
						// Take them to the my account page
						header(sprintf("Location: %s/account.php", $GLOBALS['ShopPath']));
					}
				}
				else {
					// Database error
					ob_end_clean();
					header(sprintf("location:%s/account.php", $GLOBALS['ShopPath']));
					die();
				}
			}
			else {
				// Bad details
				ob_end_clean();
				header(sprintf("location:%s/account.php", $GLOBALS['ShopPath']));
				die();
			}
		}

		/**
		*	Edit an existing shipping address
		*/
		private function EditShippingAddress($MsgDesc = "", $MsgStatus = "")
		{
			if (isset($_GET['address_id'])) {

				$GLOBALS['HideAddShippingAddressMessage'] = 'none';
				if ($MsgDesc !== '') {
					$GLOBALS['HideAddShippingAddressMessage'] = '';
					$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
				}

				/**
				 * Grab from the request if we have message
				 */
				if ($MsgDesc !== '') {
					$GLOBALS['ShipCustomFields'] = buildFieldData();
				} else {
					$GLOBALS['ShipCustomFields'] = buildFieldData($_GET['address_id']);
				}

				if ($GLOBALS['ShipCustomFields'] !== '') {
					$GLOBALS['ShipId'] = (int)$_GET['address_id'];
					$GLOBALS['AddressFormFieldID'] = FORMFIELDS_FORM_ADDRESS;
					$GLOBALS['ShippingAddressFormAction'] = "update_new_shipping_address";
					$GLOBALS['ShippingAddressFormTitle'] = GetLang('EditShippingAddress');
					$GLOBALS['ShippingAddressFormIntro'] = GetLang('EditShippingAddressIntro');

					/**
					 * Load up any form field JS event data and any validation lang variables
					 */
					$GLOBALS['FormFieldRequiredJS'] = $GLOBALS['ISC_CLASS_FORM']->buildRequiredJS();

					$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName') . " - " . GetLang('EditShippingAddress'));
					$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("shippingaddressform");
					$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
				}
				else {
					// Bad details or they don't own the shipping address
					ob_end_clean();
					header(sprintf("location:%s/account.php", $GLOBALS['ShopPath']));
					die();
				}
			}
			else {
				// Bad details
				ob_end_clean();
				header(sprintf("location:%s/account.php", $GLOBALS['ShopPath']));
				die();
			}
		}

		/**
		*	Add a new shipping address to the customer's account
		*/
		private function AddShippingAddress($MsgDesc = "", $MsgStatus = "")
		{
			$GLOBALS['HideAddShippingAddressMessage'] = 'none';
			if ($MsgDesc !== '') {
				$GLOBALS['HideAddShippingAddressMessage'] = '';
				$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
			}

			$GLOBALS['ShipCustomFields'] = buildFieldData();
			$GLOBALS['AddressFormFieldID'] = FORMFIELDS_FORM_ADDRESS;
			$GLOBALS['ShippingAddressFormAction'] = "save_new_shipping_address";
			$GLOBALS['ShippingAddressFormTitle'] = GetLang('AddShippingAddress');
			$GLOBALS['ShippingAddressFormIntro'] = GetLang('AddShippingAddressIntro');

			/**
			 * Load up any form field JS event data and any validation lang variables
			 */
			$GLOBALS['FormFieldRequiredJS'] = $GLOBALS['ISC_CLASS_FORM']->buildRequiredJS();

			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName') . " - " . GetLang('AddShippingAddress'));
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("shippingaddressform");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}

		private function SaveNewShippingAddress()
		{
			$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ADDRESS, true);

			/**
			 * Validate the field input
			 */
			$errmsg = '';
			if (!validateFieldData($fields, $errmsg)) {
				return $this->AddShippingAddress($errmsg, MSG_ERROR);
			}

			$ShippingAddress = parseFieldData($fields);

			if (isset($ShippingAddress['shipfirstname']) && isset($ShippingAddress['shipaddress1'])) {
				$shippingid = $this->shippingEntity->add($ShippingAddress);
				
				if (isId($shippingid)) {
					if (isset($_SESSION['LOGIN_REDIR'])) {
						// Take them to the page they wanted
						$page = $_SESSION['LOGIN_REDIR'];
						unset($_SESSION['LOGIN_REDIR']);
						header(sprintf("Location: %s", $page));
					}
					else {
						// Take them to the my account page
						header(sprintf("Location: %s/account.php", $GLOBALS['ShopPath']));
					}
				}
				else {
					// Database error
					ob_end_clean();
					return $this->AddShippingAddress(GetLang('SomethingWentWrong') . "[1]", MSG_ERROR);
					//header(sprintf("location:%s/%s", $GLOBALS['ShopPath'], 'account.php?action=add_shipping_address'));
					die();
				}
			}
			else {
				// Bad details
				ob_end_clean();
				return $this->AddShippingAddress(GetLang('SomethingWentWrong') . "[2]", MSG_ERROR);
				//header(sprintf("location:%s/%s", $GLOBALS['ShopPath'], 'account.php?action=add_shipping_address'));
				die();
			}
		}

		/**
		*	Remove a shipping address from the shipping_addresses table
		*/
		private function DeleteShippingAddress()
		{
			if (isset($_GET['address_id'])) {

				if ($this->shippingEntity->delete($_GET['address_id'], $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId())) {
					if (isset($_SESSION['LOGIN_REDIR'])) {
						// Take them to the page they wanted
						$page = $_SESSION['LOGIN_REDIR'];
						unset($_SESSION['LOGIN_REDIR']);
						header(sprintf("Location: %s", $page));
					}
					else {
						// Take them to the my account page
						header(sprintf("Location: %s/account.php", $GLOBALS['ShopPath']));
					}
				}
				else {
					// Database error
					ob_end_clean();
					header(sprintf("location:%s/account.php", $GLOBALS['ShopPath']));
					die();
				}
			}
			else {
				// Bad details
				ob_end_clean();
				header(sprintf("location:%s/account.php", $GLOBALS['ShopPath']));
				die();
			}
		}

		/**
		*	Get the shipping address details for the selected record.
		*	Returns an array on success, false on failure.
		*/
		public function GetShippingAddress($addressId, $customerId=null)
		{
			static $shippingAddresses;

			if (isset($shippingAddresses[$addressId])) {
				return $shippingAddresses[$addressId];
			}

			$searchFields = array(
				"shipid" => $addressId
			);

			if (isId($customerId)) {
				$searchFields["shipcustomerid"] = $customerId;
			}

			if (!$this->shippingEntity->search($searchFields)) {
				return false;
			}

			$shippingAddresses[$addressId] = $this->shippingEntity->get($addressId);

			return $shippingAddresses[$addressId];
		}

		/**
		 * Format/build a shipping address based on the passed aray of address details.
		 */
		public function FormatShippingAddress($address)
		{
			if(isset($address['shipid'])) {
				$GLOBALS['ShippingAddressId'] = $address['shipid'];
			}
			$GLOBALS['ShipFullName'] = isc_html_escape($address['shipfirstname'].' '.$address['shiplastname']);

			$GLOBALS['ShipCompany'] = '';
			if($address['shipcompany']) {
				$GLOBALS['ShipCompany'] = '<br />'.isc_html_escape($address['shipcompany']);
			}

			$GLOBALS['ShipAddressLines'] = isc_html_escape($address['shipaddress1']);

			if ($address['shipaddress2'] != "") {
				$GLOBALS['ShipAddressLines'] .= '<br />' . isc_html_escape($address['shipaddress2']);
			}

			$GLOBALS['ShipSuburb'] = isc_html_escape($address['shipcity']);
			$GLOBALS['ShipState'] = isc_html_escape($address['shipstate']);
			$GLOBALS['ShipZip'] = isc_html_escape($address['shipzip']);
			$GLOBALS['ShipCountry'] = isc_html_escape($address['shipcountry']);

			if (isset($address['shipphone']) && $address['shipphone'] != "") {
				$GLOBALS['ShipPhone'] = isc_html_escape(sprintf("%s: %s", GetLang('Phone'), $address['shipphone']));
			}
			else {
				$GLOBALS['ShipPhone'] = "";
			}

			$addressText = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("AddressLabel");
			return $addressText;
		}

		/**
		*	Get an address from the database and return it as a formatted address
		*/
		public function GetAndFormatShippingAddressById($AddressId)
		{
			$address_text = "";
			$customer_id = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();

			$row = $this->GetShippingAddress($AddressId, $customer_id);
			return $this->FormatShippingAddress($row);
		}

		/**
		*	Get an address from the database and return it as an unformatted address
		*/
		public function GetUnformattedShippingAddressById($AddressId)
		{
			$address_text = "";
			$customer_id = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();
			$query = sprintf("select * from [|PREFIX|]shipping_addresses where shipid='%d' and shipcustomerid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($AddressId), $GLOBALS['ISC_CLASS_DB']->Quote($customer_id));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			if ($row !== false) {

				$GLOBALS['ShippingAddressId'] = $row['shipid'];
				$GLOBALS['ShipFullName'] = isc_html_escape($row['shipfirstname'].' '.$row['shiplastname']);

				$GLOBALS['ShipCompany'] = '';
				if($row['shipcompany']) {
					$GLOBALS['ShipCompany'] = "\n".isc_html_escape($row['shipcompany']);
				}

				$GLOBALS['ShipAddressLine1'] = isc_html_escape($row['shipaddress1']);

				if($row['shipaddress2'] != "") {
					$GLOBALS['ShipAddressLine2'] = isc_html_escape($row['shipaddress2']);
				} else {
					$GLOBALS['ShipAddressLine2'] = '';
				}

				$GLOBALS['ShipSuburb'] = isc_html_escape($row['shipcity']);
				$GLOBALS['ShipState'] = isc_html_escape($row['shipstate']);
				$GLOBALS['ShipZip'] = isc_html_escape($row['shipzip']);
				$GLOBALS['ShipCountry'] = isc_html_escape($row['shipcountry']);

				$address_text = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("UnformattedAddressLabel");
			}

			return $address_text;
		}

		/**
		*	Get an address from the database and return it as an unformatted address
		*/
		public function GetZipForShippingAddressById($AddressId)
		{
			$zip = "";
			$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
			$customer_id = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();
			$query = sprintf("select shipzip from [|PREFIX|]shipping_addresses where shipid='%d' and shipcustomerid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($AddressId), $GLOBALS['ISC_CLASS_DB']->Quote($customer_id));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			if ($row !== false) {
				$zip = $row['shipzip'];
			}

			return $zip;
		}

		public function MyAccountPage()
		{
			$GLOBALS['ISC_LANG']['ViewMessagesDescription'] = sprintf(GetLang('ViewMessagesDescription'), $GLOBALS['StoreName']);
			$GLOBALS['ISC_LANG']['ViewOrderStatusDescription'] = sprintf(GetLang('ViewOrderStatusDescription'), $GLOBALS['StoreName']);
			$GLOBALS['ISC_LANG']['CompletedOrdersDescription'] = sprintf(GetLang('CompletedOrdersDescription'), $GLOBALS['StoreName']);
			$GLOBALS['ISC_LANG']['ReturnRequestsDescription'] = sprintf(GetLang('ReturnRequestsDescription'), $GLOBALS['StoreName']);
			$GLOBALS['ISC_LANG']['AddressBookDescription'] = sprintf(GetLang('AddressBookDescription'), $GLOBALS['StoreName']);
			$GLOBALS['ISC_LANG']['WishListDescription'] = sprintf(GetLang('WishListDescription'), $GLOBALS['StoreName']);
			$GLOBALS['ISC_LANG']['AccountDetailsDescription'] = sprintf(GetLang('AccountDetailsDescription'), $GLOBALS['StoreName']);
			$GLOBALS['ISC_LANG']['RecentlyViewedItemsDescription'] = sprintf(GetLang('RecentlyViewedItemsDescription'), $GLOBALS['StoreName']);

			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(sprintf("%s - %s", GetConfig('StoreName'), GetLang('YourAccount')));
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("account");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}

		/**
		*	Get a list of all messages and display them
		*/
		private function Inbox()
		{

			if (!gzte11(ISC_LARGEPRINT)) {
				ob_end_clean();
				header("Location: " . $GLOBALS['ShopPath']);
				die();
			}

			$GLOBALS['SNIPPETS']['AccountInboxMessage'] = "";
			$GLOBALS['SNIPPETS']['AccountInboxOrderItem'] = "";

			$order_ids_array = array();
			$query = sprintf("select orderid, orddate, total_inc_tax, ordcurrencyid, ordcurrencyexchangerate from [|PREFIX|]orders where ordcustid='%d' and ordstatus > 0 AND deleted = 0 order by orderid desc", $GLOBALS['ISC_CLASS_DB']->Quote($GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId()));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$order_ids_array[] = $row['orderid'];
				$GLOBALS['OrderId'] = $row['orderid'];
				$GLOBALS['OrderItemMessage'] = sprintf(GetLang('OrderItemMessage'), $row['orderid'], isc_date(GetConfig('DisplayDateFormat'), $row['orddate']), CurrencyConvertFormatPrice($row['total_inc_tax'], $row['ordcurrencyid'], $row['ordcurrencyexchangerate']));
				$GLOBALS['SNIPPETS']['AccountInboxOrderItem'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("AccountInboxOrderItem");
			}

			$order_ids = implode("', '", $GLOBALS['ISC_CLASS_DB']->Quote($order_ids_array));

			if ($order_ids != "") {
				// They've placed at least one order
				if ($order_ids != "") {
					$query = sprintf("select * from [|PREFIX|]order_messages where messageorderid in ('%s') order by messageid asc", $order_ids);
					$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

					if ($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
						while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
							$GLOBALS['MessageSubject'] = isc_html_escape($row['subject']);
							$GLOBALS['MessageContent'] = nl2br(isc_html_escape($row['message']));
							$GLOBALS['MessageDate'] = isc_date(GetConfig('ExtendedDisplayDateFormat'), $row['datestamp']);

							if ($row['messagefrom'] == "customer") {
								$GLOBALS['Sender'] = GetLang('MessageYou');
								$GLOBALS['Icon'] = "1";
							}
							else {
								$GLOBALS['Sender'] = $GLOBALS['StoreName'];
								$GLOBALS['Icon'] = "2";
							}

							$GLOBALS['SNIPPETS']['AccountInboxMessage'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("AccountInboxMessage");
						}
					}

					$GLOBALS['AccountInboxIntro'] = sprintf(GetLang('AccountInboxIntro1'), $GLOBALS['StoreName']);

					// Update all messages to "read"
					$UpdatedMessages =array(
						"messagestatus" => "read"
					);
					$GLOBALS['ISC_CLASS_DB']->UpdateQuery("order_messages", $UpdatedMessages, "messagefrom='admin' AND messageorderid IN ('".$order_ids."')");
				}

				if ($GLOBALS['SNIPPETS']['AccountInboxMessage'] == "") {
					// No messages, show a notification
					$GLOBALS['AccountInboxIntro'] = sprintf(GetLang('AccountInboxIntro2'), $GLOBALS['StoreName']);
				}

				$GLOBALS['HideNoOrderMessage'] = "none";
			}
			else {
				// No access to the inbox, they haven't placed an order
				$GLOBALS['HideInbox']= "none";
			}

			if (!isset($GLOBALS['HideMessageSuccess'])) {
				$GLOBALS['HideMessageSuccess'] = "none";
			}

			if (!isset($GLOBALS['HideMessageError'])) {
				$GLOBALS['HideMessageError'] = "none";
			}

			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(sprintf("%s - %s", GetConfig('StoreName'), GetLang('Inbox')));
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("account_inbox");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}

		/**
		*	Save the new message to the order_messages table
		*/
		private function SendMessage()
		{

			if (!gzte11(ISC_LARGEPRINT)) {
				ob_end_clean();
				header("Location: " . $GLOBALS['ShopPath']);
				die();
			}

			if (isset($_POST['message_order_id']) && isset($_POST['message_subject']) && isset($_POST['message_content'])) {
				$message = $_POST['message_content'];
				$NewMessage = array(
					"messagefrom" => "customer",
					"subject" => $_POST['message_subject'],
					"message" => $message,
					"datestamp" => time(),
					"messageorderid" => (int)$_POST['message_order_id'],
					"messagestatus" => 'unread',
					"staffuserid" => 0,
					"isflagged" => 0
				);

				$GLOBALS['HideNoOrderMessage'] = "none";
				$GLOBALS['HideInboxMessage'] = "none";

				if ($GLOBALS['ISC_CLASS_DB']->InsertQuery("order_messages", $NewMessage)) {
					$GLOBALS['HideMessageSuccess'] = "";
					$GLOBALS['HideMessageError'] = "none";
				}
				else {
					$GLOBALS['HideMessageError'] = "";
					$GLOBALS['HideMessageSuccess'] = "none";
				}

				$this->Inbox();
			}
		}

		/**
		*	Get all orders for this customer. If $OnlyCompletedOrders is true then we will only
		*	return orders whose ordstatus field is 2 or 10 (shipped or completed). If $NoIncompleteOrders
		*	is true then we will only return orders that have a valid status (ordstatus != 0)
		*/
		public function GetCustomerOrders(&$Result, $OnlyCompletedOrders = false, $NoIncompleteOrders = false)
		{
			$complete_filter = "";

			if ($OnlyCompletedOrders) {
				$complete_filter .= " and (ordstatus='2' or ordstatus='10') ";
			}

			if ($NoIncompleteOrders) {
				$complete_filter .= " and ordstatus != '0' ";
			}

			$query = "
				SELECT *,
				(SELECT statusdesc	FROM [|PREFIX|]order_status WHERE statusid=ordstatus) AS ordstatustext
				FROM [|PREFIX|]orders
				WHERE ordcustid='" . $GLOBALS['ISC_CLASS_DB']->Quote($GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId()) . "' AND deleted = 0 " . $complete_filter . "
				ORDER BY orderid DESC
			";
			$Result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		}

		/**
		*	Show a list of orders and the ability to download the product if it's a digital download
		*/
		private function OrderStatus()
		{

			$GLOBALS['SNIPPETS']['AccountOrderStatus'] = "";
			$result = false;
			$this->GetCustomerOrders($result, false, true);

			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$order = $row;
				$GLOBALS['OrderDate'] = isc_date(GetConfig('DisplayDateFormat'), $row['orddate']);
				$GLOBALS['OrderId'] = $row['orderid'];
				$GLOBALS['OrderTotal'] = CurrencyConvertFormatPrice($row['total_inc_tax'], $row['ordcurrencyid'], $row['ordcurrencyexchangerate'], true);
				$GLOBALS['OrderStatus'] = $row['ordstatustext'];

				$GLOBALS['TrackURL'] = "";

				$GLOBALS['HidePaymentInstructions'] = "none";
				$GLOBALS['OrderInstructions'] = "";

				// Is the order status "awaiting payment"? If so show the payment instructions
				if ($row['ordstatus'] == ORDER_STATUS_AWAITING_PAYMENT) {
					$checkout_object = false;
					if (GetModuleById('checkout', $checkout_object, $row['orderpaymentmodule']) && $checkout_object->getpaymenttype("text") == "PAYMENT_PROVIDER_OFFLINE") {
						$GLOBALS['HidePaymentInstructions'] = "";
						if (method_exists($checkout_object, 'GetOfflinePaymentMessage')) {
							// set the order data so any variables that are used in the GetOfflinePaymentMessage function are set correctly
							$paymentData = array(
								'orders' => array($row['orderid'] => $row)
							);
							$checkout_object->SetOrderData($paymentData);
							$GLOBALS['OrderInstructions'] = $checkout_object->GetOfflinePaymentMessage();
						}
						else {
							$GLOBALS['OrderInstructions'] = nl2br(GetModuleVariable($row['orderpaymentmodule'], "helptext"));
						}
					}
				}

				// Get a list of products in the order
				$prod_result = false;
				$products = $this->GetProductsInOrder($row['orderid'], $prod_result);
				$GLOBALS['AccountOrderItemList'] = '';
				while ($prod_row = $GLOBALS['ISC_CLASS_DB']->Fetch($prod_result)) {
					$GLOBALS['ItemName'] = isc_html_escape($prod_row['ordprodname']);
					$GLOBALS['ItemQty'] = $prod_row['ordprodqty'];

					// Is it a downloadable item?
					if ($prod_row['ordprodtype'] == "digital" && OrderIsComplete($row['ordstatus'])) {
						$GLOBALS['DownloadItemEncrypted'] = $this->EncryptDownloadKey($prod_row['orderprodid'], $prod_row['ordprodid'], $row['orderid'], $row['ordtoken']);
						$GLOBALS['DownloadLink'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("AccountOrderItemDownloadLink");
					}
					else {
						$GLOBALS['DownloadLink'] = "";
					}

					$GLOBALS['Refunded'] = '';
					if ($prod_row['ordprodrefunded'] > 0) {
						if ($prod_row['ordprodrefunded'] == $prod_row['ordprodqty']) {
							$GLOBALS['StrikeStart'] = "<s>";
							$GLOBALS['StrikeEnd'] = "</s>";
							$GLOBALS['Refunded'] = '<span class="Refunded">'.GetLang('OrderProductRefunded').'</span>';
						}
						else {
							$GLOBALS['Refunded'] = '<span class="Refunded">'.sprintf(GetLang('OrderProductsRefundedX'), $prod_row['ordprodrefunded']).'</span>';
						}
					}

					// Were there one or more options selected?
					$GLOBALS['ProductOptions'] = '';
					if($prod_row['ordprodoptions'] != '') {
						$options = @unserialize($prod_row['ordprodoptions']);
						if(!empty($options)) {
							$GLOBALS['ProductOptions'] = "<br /><small>(";
							$comma = '';
							foreach($options as $name => $value) {
								$GLOBALS['ProductOptions'] .= $comma.isc_html_escape($name).": ".isc_html_escape($value);
								$comma = ', ';
							}
							$GLOBALS['ProductOptions'] .= ")</small>";
						}
					}

					$GLOBALS['HideExpectedReleaseDate'] = 'display:none;';
					$GLOBALS['ExpectedReleaseDate'] = '';

					if ($prod_row['prodpreorder']) {
						if ($prod_row['prodreleasedate']) {
							$message = $prod_row['prodpreordermessage'];
							if (!$message) {
								$message = GetConfig('DefaultPreOrderMessage');
							}
							$GLOBALS['ExpectedReleaseDate'] = '(' . str_replace('%%DATE%%', isc_date(GetConfig('DisplayDateFormat'), $prod_row['prodreleasedate']), $message) . ')';
						} else {
							$GLOBALS['ExpectedReleaseDate'] = '(' . GetLang('PreOrderProduct') . ')';
						}
						$GLOBALS['HideExpectedReleaseDate'] = '';
					}

					$GLOBALS['AccountOrderItemList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("AccountOrderItemList");
				}

				$GLOBALS['SNIPPETS']['AccountOrderStatus'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("AccountOrderStatusItem");
			}

			if($GLOBALS['SNIPPETS']['AccountOrderStatus']) {
				$GLOBALS['HideNoOrderStatusMessage'] = "none";
			}
			else {
				$GLOBALS['HideOrderStatusList'] = "none";
			}

			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(sprintf("%s - %s", GetConfig('StoreName'), GetLang('OrderStatus')));
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("account_orderstatus");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}

		/**
		*	Return a list of items from the order_products table whose orderorderid field = $OrderId
		*/
		public function GetProductsInOrder($OrderId, &$Result)
		{
			$query = "
				SELECT
					op.orderprodid,
					op.ordprodid,
					op.ordprodname,
					op.ordprodtype,
					op.ordprodqty,
					op.ordprodrefunded,
					op.ordprodoptions,
					op.ordprodvariationid,
					p.productid,
					p.prodpreorder,
					p.prodreleasedate,
					p.prodpreordermessage,
					op.order_address_id
				FROM
					[|PREFIX|]order_products op
					LEFT JOIN [|PREFIX|]products p ON p.productid = op.ordprodid
				WHERE op.orderorderid = " . (int)$OrderId;

			$Result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		}

		private function ViewOrders()
		{
			$GLOBALS['SNIPPETS']['AccountOrders'] = "";
			$GLOBALS['AccountOrderItemList'] = "";

			$result = false;
			$this->GetCustomerOrders($result, true);

			// Are there any orders for this customer
			if ($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
				while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$GLOBALS['OrderDate'] = isc_date(GetConfig('DisplayDateFormat'), $row['orddate']);
					$GLOBALS['OrderId'] = $row['orderid'];
					$GLOBALS['OrderTotal'] = CurrencyConvertFormatPrice($row['total_inc_tax'], $row['ordcurrencyid'], $row['ordcurrencyexchangerate'], true);

					$GLOBALS['HidePaymentInstructions'] = "none";
					$GLOBALS['OrderInstructions'] = "";

					$GLOBALS['DisableReturnButton'] = "";
					if (!gzte11(ISC_LARGEPRINT)) {
						$GLOBALS['DisableReturnButton'] = "none";
					}

					if ($row['ordstatus'] == 4 || GetConfig('EnableReturns') == 0) {
						$GLOBALS['DisableReturnButton'] = "none";
					}

					// Get a list of products in the order
					$prod_result = false;
					$products = $this->GetProductsInOrder($row['orderid'], $prod_result);
					while ($prod_row = $GLOBALS['ISC_CLASS_DB']->Fetch($prod_result)) {
						$GLOBALS['ItemName'] = isc_html_escape($prod_row['ordprodname']);
						$GLOBALS['ItemQty'] = $prod_row['ordprodqty'];

						// Is it a downloadable item?
						if ($prod_row['ordprodtype'] == "digital" && OrderIsComplete($row['ordstatus'])) {
							$GLOBALS['DownloadItemEncrypted'] = $this->EncryptDownloadKey($prod_row['orderprodid'], $prod_row['ordprodid'], $row['orderid'], $row['ordtoken']);
							$GLOBALS['DownloadLink'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("AccountOrderItemDownloadLink");
						}
						else {
							$GLOBALS['DownloadLink'] = "";
						}

						$GLOBALS['Refunded'] = '';
						if ($prod_row['ordprodrefunded'] > 0) {
							if ($prod_row['ordprodrefunded'] == $prod_row['ordprodqty']) {
								$GLOBALS['StrikeStart'] = "<s>";
								$GLOBALS['StrikeEnd'] = "</s>";
								$GLOBALS['Refunded'] = '<span class="Refunded">'.GetLang('OrderProductRefunded').'</span>';
							}
							else {
								$GLOBALS['Refunded'] = '<span class="Refunded">'.sprintf(GetLang('OrderProductsRefundedX'), $prod_row['ordprodrefunded']).'</span>';
							}
						}

						// Were there one or more options selected?
						$GLOBALS['ProductOptions'] = '';
						if($prod_row['ordprodoptions'] != '') {
							$options = @unserialize($prod_row['ordprodoptions']);
							if(!empty($options)) {
								$GLOBALS['ProductOptions'] = "<br /><small>(";
								$comma = '';
								foreach($options as $name => $value) {
									$GLOBALS['ProductOptions'] .= $comma.isc_html_escape($name).": ".isc_html_escape($value);
									$comma = ', ';
								}
								$GLOBALS['ProductOptions'] .= ")</small>";
							}
						}

						$GLOBALS['HideExpectedReleaseDate'] = 'display:none;';
						$GLOBALS['ExpectedReleaseDate'] = '';

						if ($prod_row['prodpreorder']) {
							if ($prod_row['prodreleasedate']) {
								$message = $prod_row['prodpreordermessage'];
								if (!$message) {
									$message = GetConfig('DefaultPreOrderMessage');
								}
								$GLOBALS['ExpectedReleaseDate'] = '(' . str_replace('%%DATE%%', isc_date(GetConfig('DisplayDateFormat'), $prod_row['prodreleasedate']), $message) . ')';
							} else {
								$GLOBALS['ExpectedReleaseDate'] = '(' . GetLang('PreOrderProduct') . ')';
							}
							$GLOBALS['HideExpectedReleaseDate'] = '';
						}

						$GLOBALS['AccountOrderItemList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("AccountOrderItemList");
					}

					$GLOBALS['SNIPPETS']['AccountOrders'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("AccountOrderItem");
					$GLOBALS['AccountOrderItemList'] = "";
				}

				$GLOBALS['HideNoOrdersMessage'] = "none";
			}
			else {
				$GLOBALS['HideOrderList'] = "none";
			}

			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(sprintf("%s - %s", GetConfig('StoreName'), GetLang('YourOrders')));
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("account_orders");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}

		/**
		*	Build an encrypted download key that links the client to download the product they purchased
		*/
		public function EncryptDownloadKey($ItemRecordId, $ProductId, $OrderId, $OrderToken, $DownloadId=0)
		{
			// The order token can't have a ',' in it.
			if (strpos($OrderToken, ',') !== false) {
				return false;
			}

			$data = ((int)$ItemRecordId).','.((int)$ProductId).','.((int)$OrderId).','.$OrderToken;

			if ($DownloadId > 0) {
				$data .= ','.((int)$DownloadId);
			}

			// Create some random "noise" text
			$gibberish = "";

			for ($i = 0; $i < rand(30, 50); $i++) {
				$gibberish .= chr(rand(48, 57));
			}
			$data .= ','.$gibberish;

			// Merge everything into a variable
			$data = base64_encode($data);
			return $data;
		}

		/**
		*	Decrypt the product download key
		*/
		public function DecryptDownloadKey($Data)
		{
			$data = base64_decode($Data);
			return $data;
		}

		/**
		* Examines the request headers sent by the client and returns an array of chunks which were requested in the Range request. Any ranges that result in 0 bytes, or ranged that attempt to begin beyond the maximum range will be discarded.
		*
		* @link http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt Byte Range Retrieval Extension to HTTP
		* @param double $max The maximum allowed value in bytes; all values will be capped to this and any open-ended ranges (e.g. "500-" will be returned as $max) - note that filesize() will return a signed 32bit int on most platforms resulting in negative file sizes for files over 2gb, see http://php.net/filesize for more info.
		* @param string $requestHeader Optional. The range request header to parse. If not provided, will use $_SERVER['HTTP_RANGE'] automatically.
		* @return array Returns an array of chunk descriptions, where each chunk is an array containing begin and end values and a 3rd value reserved for multipart MIME response boundaries. Will return FALSE if no valid ranges were found.
		*/
		public function getDownloadChunksFromRequestHeader($max, $requestHeader = null)
		{
			$max = (double)$max;
			if ($max <= 0) {
				// invalid max value specified
				return false;
			}

			if ($requestHeader === null) {
				$requestHeader = @$_SERVER['HTTP_RANGE'];
			}

			$requestHeader = trim($requestHeader);

			if (empty($requestHeader) || substr_count($requestHeader, '=') !== 1) {
				// no or invalid range header specified
				return false;
			}

			list($type, $ranges) = explode('=', $requestHeader);

			if ($type != 'bytes') {
				// we only support byte ranges
				return false;
			}

			$chunks = array();
			$ranges = explode(',', $ranges);

			foreach ($ranges as $range) {
				if (substr_count($range, '-') !== 1) {
					// invalid range
					continue;
				}

				@list($begin, $end) = explode('-', $range);

				if ($begin === null || $end === null) {
					// invalid range
					continue;
				}

				if ($begin === '' && $end === '') {
					// both values cannot be empty
					continue;

				} else if ($begin === '') {
					// empty begin value, the specified range is last n bytes
					$begin = $max - (int)$end + 1;
					$end = $max;

				} else if ($end === '') {
					// empty end value, the specified range is n bytes (offset) and onwards to end of stream
					$begin = (int)$begin;
					$end = $max;

				} else {
					// both values specified, use values as absolute range
					$begin = (int)$begin;
					$end = (int)$end;
				}

				if ($end > $max) {
					// cap all values to maximum value
					$end = $max;
				}

				if ($begin > $end) {
					// discard ranges that are zero or smaller in size
					continue;
				}

				$chunks[] = array($begin, $end, false);
			}

			if (empty($chunks)) {
				return false;
			}

			// according to rfc, do not discard overlapping ranges

			if (count($chunks) === 1 && $chunks[0][0] === 0 && $chunks[0][1] === $max) {
				// return false if there's only one range requested and it's 0 - $max so the app behaves like no range was requested
				return false;
			}

			return $chunks;
		}

		/**
		*	Strem the product for download as defined by the values in the $_GET['data'] variable.
		*	The variable contains the item id, product id and order id which, if valid, will
		*	be used to find and then stream the file for the product to the customer
		*/
		private function DownloadOrderItem()
		{
			if (isset($_GET['data'])) {
				$data = $this->DecryptDownloadKey($_GET['data']);
				$data_vals = explode(",", $data);

				if (count($data_vals) >= 5) {
					$item_id = (int)$data_vals[0];
					$product_id = (int)$data_vals[1];
					$order_id = (int)$data_vals[2];
					$order_token = $data_vals[3];

					// Select the number of downloads for this order item
					$query = sprintf("
						select pd.downloadid, o.ordstatus
						from [|PREFIX|]product_downloads pd
						left join [|PREFIX|]order_products op on pd.productid=op.ordprodid
						inner join [|PREFIX|]orders o on op.orderorderid=o.orderid
						where pd.productid='%d' and o.orderid='%d' and o.deleted = 0 and op.orderprodid='%d'",
						$GLOBALS['ISC_CLASS_DB']->Quote($product_id), $GLOBALS['ISC_CLASS_DB']->Quote($order_id), $GLOBALS['ISC_CLASS_DB']->Quote($item_id)
					);

					$query .= " AND o.ordtoken = '".$GLOBALS['ISC_CLASS_DB']->Quote($order_token)."'";
					$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, 1);
					$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
					$product_downloads = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

					// We have a valid ordered product with downloads
					if ($product_downloads && OrderIsComplete($product_downloads['ordstatus'])) {
						// Downloading a particular file
						if (count($data_vals) == 6) {
							$download_id = (int)$data_vals[4];
							// Fetch the file we're downloading
							$query = sprintf("
								SELECT orddate, pd.downfile, od.numdownloads, od.downloadexpires, od.maxdownloads, ordstatus, pd.downexpiresafter, pd.downmaxdownloads, od.orddownid
								FROM [|PREFIX|]product_downloads pd
								INNER JOIN [|PREFIX|]products p ON pd.productid=p.productid
								LEFT JOIN [|PREFIX|]order_downloads od ON (od.orderid='%s' AND od.downloadid=pd.downloadid)
								INNER JOIN [|PREFIX|]orders o ON (o.orderid='%d')
								WHERE pd.downloadid='%d' AND p.productid='%d' AND o.deleted = 0",
								$GLOBALS['ISC_CLASS_DB']->Quote($order_id), $GLOBALS['ISC_CLASS_DB']->Quote($order_id), $GLOBALS['ISC_CLASS_DB']->Quote($download_id), $GLOBALS['ISC_CLASS_DB']->Quote($product_id)
							);

							$query .= " AND o.ordtoken = '".$GLOBALS['ISC_CLASS_DB']->Quote($order_token)."'";

							$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
							$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

							if ($row && OrderIsComplete($row['ordstatus'])) {
								// If there is no matching row in the order_downloads table for this download, we need to create it
								if(!$row['orddownid']) {
									// If this download has an expiry date, set it to now + expiry time
									$expiryDate = 0;
									if($row['downexpiresafter'] > 0) {
										$expiryDate = $row['orddate'] + $row['downexpiresafter'];
									}

									$newDownload = array(
										'orderid' => (int)$order_id,
										'downloadid' => (int)$download_id,
										'numdownloads' => 0,
										'downloadexpires' => $expiryDate,
										'maxdownloads' => $row['downmaxdownloads']
									);
									$row['maxdownloads'] = $row['downmaxdownloads'];
									$row['downloadexpires'] = $expiryDate;
									$GLOBALS['ISC_CLASS_DB']->InsertQuery('order_downloads', $newDownload);
								}
								$expired = false;
								// Have we reached the download limit for this item?
								if ($row['maxdownloads'] != 0 && $row['numdownloads'] >= $row['maxdownloads']) {
									$expired = true;
								}
								// Have we reached the expiry limit for this item?
								if ($row['downloadexpires'] > 0 && time() >= $row['downloadexpires']) {
									$expired = true;
								}

								// Download has expired
								if ($expired == true) {
									$GLOBALS['ErrorMessage'] = GetLang('DownloadItemExpired');
									$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("error");
									$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
									return;
								}

								$filename = basename($row['downfile']);
								$filepath = realpath(ISC_BASE_PATH.'/' . GetConfig('DownloadDirectory')) . "/" . $row['downfile'];

								if (file_exists($filepath)) {
									// Strip the underscores and random numbers that are added when a file is uploaded
									$filename = preg_replace("#__[0-9]+#", "", $filename);
									$filesize = (double)sprintf('%u', filesize($filepath));

									while (@ob_end_clean()) {
										// empty loop to clean all output buffers
									}

									// common headers for both full and partial responses
									header("Pragma: public");
									header("Expires: 0");
									header("Accept-Ranges: bytes");
									header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
									header("Content-Transfer-Encoding: binary");

									$chunks = $this->getDownloadChunksFromRequestHeader($filesize);
									$boundary = false;

									$countDownload = false;

									// downloads should only be counted if the download includes byte 0
									if ($chunks === false) {
										$countDownload = true;

									} else {
										foreach ($chunks as $chunk) {
											if ($chunk[0] == 0) {
												$countDownload = true;
												break;
											}
										}
									}

									if ($countDownload) {
										// increment the download counter by 1
										$query = "UPDATE `[|PREFIX|]order_downloads` SET numdownloads=numdownloads + 1 WHERE orderid='" . (int)$order_id . "' AND downloadid='" . (int)$download_id . "'";
										$GLOBALS['ISC_CLASS_DB']->Query($query);
									}

									if ($chunks === false) {
										// send the full response
										header('HTTP/1.1 200 OK');

										// browsers need a little extra help from these headesr to always force the "save" dialog
										header("Content-Type: application/force-download");
										header("Content-Type: application/octet-stream");
										header("Content-Type: application/download");
										header("Content-Disposition: attachment; filename=\"" . $filename . "\";");

										header("Content-length: " . $filesize);

										// reconfigure the chunks array to include the full response because we'll use it in the fread loops below
										$chunks = array(
											array(0, $filesize, false)
										);

									} else {
										// send a partial download
										header('HTTP/1.1 206 Partial content');

										// these requests should only ever be sent by download managers or non-interactive saving processes (ie. clicking "resume" in chrome) so a save dialog does not need to show

										if (count($chunks) == 1) {
											// send a single range request as a non-mime response as this is probably more compatible with download managers
											// if this turns out to not be the case, it may be necessary to remove this section and send all partial responses as MIME
											$chunk = $chunks[0];
											$begin = $chunk[0];
											$end = $chunk[1];
											$length = $end - $begin + 1;

											header("Content-type: application/octet-stream"); // @todo does this need to be an accurate content type for partial responses?
											header('Content-range: bytes ' . $begin . '-' . $end . '/' . $filesize);
											header('Content-length: ' . $length);

										} else {
											// multiple download ranges are sent as a multipart MIME response
											// @todo this has not been tested

											$boundary = 'BOUNDARY' . md5(uniqid(mt_rand(), true));
											header('Content-type: multipart/x-byteranges; boundary=' . $boundary);

											$length = 0;

											foreach ($chunks as &$chunk) {
												$begin = $chunk[0];
												$end = $chunk[1];

												// fill in the 3rd element of each chunk with its MIME boundary
												$chunk[3] = "\r\n";
												$chunk[3] .= "--" . $boundary . "\r\n";
												$chunk[3] .= "Content-type: application/octet-stream"; // @todo does this need to be an accurate content type for partial responses?
												$chunk[3] .= "Content-range: bytes " . $begin . "-" . $end . "/" . $filesize . "\r\n";
												$chunk[3] .= "\r\n";

												// add the length of the MIME boundary and the chunk to the total content length
												$length += strlen($chunk[3]) + ($end - $begin + 1);
											}

											header('Content-length: ' . $length);
										}
									}

									// don't abort the script on user disconnect during a stream so we can clean up the file handles properly
									ignore_user_abort(true);

									$outputBufferLength = 16384;

									// loop over each requested download chunk and stream it to the browser, adding MIME boundaries if necessary
									foreach ($chunks as $chunk) {
										$begin = $chunk[0];
										$end = $chunk[1];
										$boundary = @$chunk[2];
										$length = $end - $begin + 1;

										// set a new time limit, resetting the timer to 0
										@set_time_limit(30);

										if ($boundary) {
											echo $boundary;
											flush();
										}

										$fp = fopen($filepath, 'rb');
										fseek($fp, $begin);

										while ($length && !feof($fp)) {
											// at the end of the chunk, the buffer length may be longer than the remaining length, so we only need to read up to the end of the chunk
											$readLength = min($length, $outputBufferLength);

											echo fread($fp, $readLength);
											@flush();
											$length -= $readLength;

											if (connection_aborted()) {
												break;
											}
										}

										// @todo if tracking of downloads by bytes is ever done, log it here
										fclose($fp);
									}

									die();
								}
								else {
									// File doesn't exist
									$GLOBALS['ErrorMessage'] = GetLang('DownloadItemErrorMessage');
									$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("error");
									$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
								}
							}
							else {
								// Product doesn't exist or the download doesn't exist.
								$GLOBALS['ErrorMessage'] = GetLang('DownloadItemErrorMessage');
								$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("error");
								$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
							}
						}
						else {
							$GLOBALS['SNIPPETS']['AccountDownloadItemList'] = '';
							$query = sprintf("select prodname from [|PREFIX|]products where productid='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($product_id));
							$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
							$prodName = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
							$GLOBALS['DownloadTitle'] = sprintf(GetLang('ProductDownloads'), $prodName);
							$GLOBALS['DownloadIntro'] = sprintf(GetLang('ProductDownloadsIntro'), $prodName);

							// Show a listing of the downloadable files within this product
							$query = sprintf("
								select orddate, orderprodid, ordprodid, o.orderid, o.ordtoken, pd.downloadid, pd.downfile, pd.downname, pd.downfilesize, pd.downdescription, pd.downmaxdownloads, pd.downexpiresafter, od.numdownloads, od.maxdownloads, od.downloadexpires, od.orddownid, ordprodqty
								from [|PREFIX|]product_downloads pd
								left join [|PREFIX|]order_products op on pd.productid=op.ordprodid
								inner join [|PREFIX|]orders o on op.orderorderid=o.orderid
								left join [|PREFIX|]order_downloads od on od.downloadid=pd.downloadid and od.orderid=o.orderid
								where pd.productid='%d' and o.orderid='%d' and o.deleted = 0 and op.orderprodid='%d' order by downname",
								$product_id, $order_id, $item_id
							);

							$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
							while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
								$expired = false;
								$Color = '';
								$ExpiresDays = '';
								$ExpiresDownloads = '';
								$GLOBALS['ExpiryInfo'] = '';

								if(!$row['orddownid']) {
									$row['maxdownloads'] = $row['downmaxdownloads'];
									if($row['downexpiresafter'] > 0) {
										$row['downloadexpires'] = $row['downexpiresafter'] + $row['orddate'];
									}
								}

								// Have we reached the expiry limit for this item?
								if ($row['downexpiresafter'] > 0) {
									$diff = $row['downloadexpires'];
									if ($row['downloadexpires'] <= time()) {
										$expired = true;
									}
									else {
										$remaining_days = ceil(($diff-time())/86400);
										if ($remaining_days > 0 && ($remaining_days % 365) == 0) {
											if ($remaining_days/365 > 1) {
												$ExpiresDays = number_format($remaining_days/365)." ".GetLang('YearsLower');
											} else {
												$ExpiresDays = number_format($remaining_days/365)." ".GetLang('YearLower');
											}
										}
										else if ($remaining_days > 0 && ($remaining_days % 30) == 0) {
											if ($remaining_days/30 > 1) {
												$ExpiresDays = number_format($remaining_days/30)." ".GetLang('MonthsLower');
											} else {
												$ExpiresDays = number_format($remaining_days/30)." ".GetLang('MonthLower');
											}
										}
										else if ($remaining_days > 0 && ($remaining_days % 7) == 0) {
											if ($remaining_days/7 > 1) {
												$ExpiresDays = number_format($remaining_days/7)." ".GetLang('WeeksLower');
											} else {
												$ExpiresDays = number_format($remaining_days/7)." ".GetLang('WeekLower');
											}
										}
										else {
											if ($remaining_days > 1) {
												$ExpiresDays = number_format($remaining_days)." ".GetLang('DaysLower');
											} else {
												$ExpiresDays = number_format($remaining_days)." ".GetLang('TodayLower');
												$Color = "DownloadExpiresToday";
											}
										}
									}
								}

								// Have we reached the download limit for this item?
								if ($row['maxdownloads'] > 0) {
									$remaining_downloads = $row['maxdownloads']-$row['numdownloads'];
									if ($remaining_downloads <= 0) {
										$expired = true;
									}
									else {
										$string = 'DownloadExpiresInX';
										if ($ExpiresDays) {
											$string .= 'Download';
										}
										else {
											$string .= 'Time';
										}
										if ($remaining_downloads != 1) {
											$string .= 's';
										}
										else {
											$Color = "DownloadExpiresToday";
										}
										$ExpiresDownloads = sprintf(GetLang($string), $remaining_downloads);
									}
								}

								$GLOBALS['DownloadColor'] = $Color;
								$GLOBALS['DownloadName'] = isc_html_escape($row['downname']);

								if ($expired == true) {
									$GLOBALS['DisplayDownloadExpired'] = '';
									$GLOBALS['DisplayDownloadLink'] = 'none';
								}
								else {
									$GLOBALS['DisplayDownloadExpired'] = 'none';
									$GLOBALS['DisplayDownloadLink'] = '';
									$GLOBALS['DownloadItemEncrypted'] = $this->EncryptDownloadKey($row['orderprodid'], $row['ordprodid'], $row['orderid'], $row['ordtoken'], $row['downloadid']);
									$GLOBALS['DownloadName'] = sprintf("<a href=\"%s/account.php?action=download_item&data=%s\">%s</a>", $GLOBALS['ShopPathSSL'], $GLOBALS['DownloadItemEncrypted'], $GLOBALS['DownloadName']);

									if ($ExpiresDays && $ExpiresDownloads) {
										$GLOBALS['ExpiryInfo'] = sprintf(GetLang('DownloadExpiresBoth'), $ExpiresDays, $ExpiresDownloads);
									}
									else if ($ExpiresDays) {
										$GLOBALS['ExpiryInfo'] = sprintf(GetLang('DownloadExpiresTime'), $ExpiresDays);
										if ($Color == "DownloadExpiresToday") {
											$GLOBALS['ExpiryInfo'] = GetLang('DownloadExpiresTimeToday');
										}
									}
									else if ($ExpiresDownloads) {
										$GLOBALS['ExpiryInfo'] = sprintf(GetLang('DownloadExpires'), $ExpiresDownloads);
									}
								}

								if($row['ordprodqty'] > 1) {
									$GLOBALS['DownloadName'] = $row['ordprodqty']. ' X '.$GLOBALS['DownloadName'];
								}

								$GLOBALS['DownloadSize'] = Store_Number::niceSize($row['downfilesize']);
								$GLOBALS['DownloadDescription'] = isc_html_escape($row['downdescription']);
								$GLOBALS['OrderId'] = $row['orderid'];
								$GLOBALS['SNIPPETS']['AccountDownloadItemList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("AccountDownloadItemList");
							}

							$GLOBALS['ISC_LANG']['OrderId'] = sprintf(GetLang('OrderId'), $order_id);

							$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(sprintf("%s - %s", GetConfig('StoreName'), GetLang('DownloadItems')));
							$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("account_downloaditem");
							$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
						}
					}
					else {
						// This order does not have any downloadable products that exist
						$GLOBALS['ErrorMessage'] = GetLang('DownloadItemErrorMessage');
						$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("error");
						$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
					}
				}
				else {
					// Bad download details in the URL
					$GLOBALS['ErrorMessage'] = GetLang('DownloadItemErrorMessage');
					$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("error");
					$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
				}
			}
			else {
				$this->ViewOrders();
			}
		}

		/**
		*	Show the details of an order and allow them to print an invoice
		*/
		private function ViewOrderDetails()
		{
			$GLOBALS['SNIPPETS']['AccountOrderItemRow'] = "";
			$count = 0;

			if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
				redirect('account.php?action=view_orders');
			}

			$GLOBALS['FlassMessage'] = GetFlashMessageBoxes();

			// Retrieve the completed order that matches the customers user id
			$orderId = (int)$_GET['order_id'];
			$GLOBALS['OrderId'] = $orderId;

			$customerId = getClass('ISC_CUSTOMER')->getcustomerId();
			$query = "
				SELECT *, (
						SELECT CONCAT(custconfirstname, ' ', custconlastname)
						FROM [|PREFIX|]customers
						WHERE customerid=ordcustid
					) AS custname, (
						SELECT statusdesc
						FROM [|PREFIX|]order_status
						WHERE statusid=ordstatus
					) AS ordstatustext
				FROM [|PREFIX|]orders
				WHERE ordcustid='".(int)$customerId."' AND orderid='".(int)$orderId."' AND deleted = 0
			";
			$result = $GLOBALS['ISC_CLASS_DB']->query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->fetch($result);
			if(!$row) {
				redirect('account.php?action=view_orders');
			}

			$GLOBALS['DisableReturnButton'] = "";
			if (!gzte11(ISC_LARGEPRINT)) {
				$GLBOALS['DisableReturnButton'] = "none";
			}

			$order = $row;

			// Fetch the shipping addresses for this order
			$addresses = array();
			$query = "
				SELECT *
				FROM [|PREFIX|]order_addresses
				WHERE order_id='".$order['orderid']."'
			";
			$result = $GLOBALS['ISC_CLASS_DB']->query($query);
			while($address = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
				$addresses[$address['id']] = $address;
			}

			// Fetch the shipping details for the order
			$query = "
				SELECT *
				FROM [|PREFIX|]order_shipping
				WHERE order_id=".$order['orderid'];
			$result = $GLOBALS['ISC_CLASS_DB']->query($query);
			while($shipping = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
				$addresses[$shipping['order_address_id']]['shipping'] = $shipping;
			}

			$GLOBALS['OrderComments'] = '';
			if($row['ordcustmessage'] != '') {
				$GLOBALS['OrderComments'] = nl2br(isc_html_escape($row['ordcustmessage']));
			}
			else {
				$GLOBALS['HideOrderComments'] = 'display: none';
			}

			if(OrderIsComplete($row['ordstatus'])) {
				if (!gzte11(ISC_LARGEPRINT)) {
					$GLOBALS['DisableReturnButton'] = "none";
				}

				if ($row['ordstatus'] == 4 || GetConfig('EnableReturns') == 0) {
					$GLOBALS['DisableReturnButton'] = "none";
				}

				$GLOBALS['HideOrderStatus'] = "none";
				$orderComplete = true;
			}
			else {
				$GLOBALS['HideOrderStatus'] = '';
				$GLOBALS['OrderStatus'] = $row['ordstatustext'];
				$GLOBALS['DisableReturnButton'] = "none";
				$orderComplete = false;
			}

			// Hide print order invoive if it's a incomplete order
			$GLOBALS['ShowOrderActions'] = '';
			if(!$row['ordstatus']) {
				$GLOBALS['ShowOrderActions'] = 'display:none';
			}

			$GLOBALS['OrderDate'] = isc_date(GetConfig('ExtendedDisplayDateFormat'), $row['orddate']);

			$GLOBALS['OrderTotal'] = CurrencyConvertFormatPrice($row['total_inc_tax'], $row['ordcurrencyid'], $row['ordcurrencyexchangerate'], true);

			// Format the billing address
			$GLOBALS['ShipFullName'] = isc_html_escape($row['ordbillfirstname'].' '.$row['ordbilllastname']);
			$GLOBALS['ShipCompany'] = '';
			if($row['ordbillcompany']) {
				$GLOBALS['ShipCompany'] = '<br />'.isc_html_escape($row['ordbillcompany']);
			}

			$GLOBALS['ShipAddressLines'] = isc_html_escape($row['ordbillstreet1']);

			if ($row['ordbillstreet2'] != "") {
				$GLOBALS['ShipAddressLines'] .= '<br />' . isc_html_escape($row['ordbillstreet2']);
			}

			$GLOBALS['ShipSuburb'] = isc_html_escape($row['ordbillsuburb']);
			$GLOBALS['ShipState'] = isc_html_escape($row['ordbillstate']);
			$GLOBALS['ShipZip'] = isc_html_escape($row['ordbillzip']);
			$GLOBALS['ShipCountry'] = isc_html_escape($row['ordbillcountry']);
			$GLOBALS['ShipPhone'] = "";
			$GLOBALS['BillingAddress'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("AddressLabel");

			// Is there a shipping address, or is it a digital download?
			if ($order['ordisdigital']) {
				$GLOBALS['HideSingleShippingAddress'] = 'display: none';
			}
			else if ($order['shipping_address_count'] > 1) {
				$GLOBALS['ShippingAddress'] = GetLang('OrderWillBeShippedToMultipleAddresses');
				$GLOBALS['HideItemDetailsHeader'] = 'display:none;';
			}
			else {
				$shippingAddress = current($addresses);
				$GLOBALS['ShipFullName'] = isc_html_escape($shippingAddress['first_name'].' '.$shippingAddress['last_name']);

				$GLOBALS['ShipCompany'] = '';
				if($shippingAddress['company']) {
					$GLOBALS['ShipCompany'] = '<br />'.isc_html_escape($shippingAddress['company']);
				}

				$GLOBALS['ShipAddressLines'] = isc_html_escape($shippingAddress['address_1']);

				if ($shippingAddress['address_2'] != "") {
					$GLOBALS['ShipAddressLines'] .= '<br />' . isc_html_escape($shippingAddress['address_2']);
				}

				$GLOBALS['ShipSuburb'] = isc_html_escape($shippingAddress['city']);
				$GLOBALS['ShipState'] = isc_html_escape($shippingAddress['state']);
				$GLOBALS['ShipZip'] = isc_html_escape($shippingAddress['zip']);
				$GLOBALS['ShipCountry'] = isc_html_escape($shippingAddress['country']);

				$GLOBALS['ShipPhone'] = "";
				$GLOBALS['ShippingAddress'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("AddressLabel");
			}

			$itemTotalColumn = 'total_ex_tax';
			if(getConfig('taxDefaultTaxDisplayOrders') == TAX_PRICES_DISPLAY_INCLUSIVE) {
				$itemTotalColumn = 'total_inc_tax';
			}

			$GLOBALS['OrderTotalRows'] = '';
			$totalRows = getOrderTotalRows($order);
			foreach($totalRows as $id => $totalRow) {
				$GLOBALS['ISC_CLASS_TEMPLATE']->assign('label', $totalRow['label']);
				$GLOBALS['ISC_CLASS_TEMPLATE']->assign('classNameAppend', ucfirst($id));
				$value = currencyConvertFormatPrice(
					$totalRow['value'],
					$row['ordcurrencyid'],
					$row['ordcurrencyexchangerate']
				);
				$GLOBALS['ISC_CLASS_TEMPLATE']->assign('value', $value);
				$GLOBALS['OrderTotalRows'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->getSnippet('AccountOrderTotalRow');
			}

			$OrderProducts = array();
			$ProductIds = array();
			// Load up the items in this order
			$query = "
				SELECT
					o.*,
					op.*,
					oa.address_1,
					oa.address_2,
					oa.city,
					oa.zip,
					oa.country,
					oa.state,
					p.productid,
					p.prodpreorder,
					p.prodreleasedate,
					p.prodpreordermessage
				FROM
					[|PREFIX|]orders o
					LEFT JOIN [|PREFIX|]order_products op ON op.orderorderid
					LEFT JOIN [|PREFIX|]products p ON p.productid = op.ordprodid
					LEFT JOIN [|PREFIX|]order_addresses oa ON oa.`id` = op.order_address_id
				WHERE
					orderorderid = " . (int)$order['orderid'] ."
				ORDER BY
					op.order_address_id";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			//check if products are reorderable
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$OrderProducts[$row['orderprodid']] = $row;
				$ProductIds[] = $row['ordprodid'];
			}

			$UnreorderableProducts = $this->GetUnreorderableProducts($OrderProducts, $ProductIds);

			// for grouping of shipping addresses in template output
			$previousAddressId = null;
			$destinationCounter = 0;

			foreach ($OrderProducts as $row) {
				if ($count++ % 2 != 0) {
					$GLOBALS['ItemClass'] = "OrderItem2";
				} else {
					$GLOBALS['ItemClass'] = "OrderItem1";
				}

				$GLOBALS['OrderProductId'] = $row['orderprodid'];
				$GLOBALS['DisableReorder'] = '';

				$GLOBALS['ReorderMessage'] = "";
				$GLOBALS['HideItemMessage'] = 'display:none;';
				if(isset($UnreorderableProducts[$row['orderprodid']])) {
					$GLOBALS['DisableReorder'] = 'Disabled';
					$GLOBALS['ReorderMessage'] = $UnreorderableProducts[$row['orderprodid']];
					if(isset($_REQUEST['reorder']) && $_REQUEST['reorder']==1) {
						$GLOBALS['HideItemMessage'] = '';
					}
				}

				$GLOBALS['Qty'] = (int) $row['ordprodqty'];
				$GLOBALS['Name'] = isc_html_escape($row['ordprodname']);
				$GLOBALS['EventDate'] = '';

				if ($row['ordprodeventdate'] != 0) {
					$GLOBALS['EventDate'] = $row['ordprodeventname'] . ': '. isc_date('M jS Y', $row['ordprodeventdate']);
				}

				// Does the product still exist or has it been deleted?
				$prod_name = GetProdNameById($row['ordprodid']);

				if ($prod_name == "" && $row['ordprodtype'] == 'giftcertificate') {
					$GLOBALS['Link'] = "javascript:product_giftcertificate()";
					$GLOBALS['Target'] = "";
				}else if ($prod_name == "") {
					$GLOBALS['Link'] = "javascript:product_removed()";
					$GLOBALS['Target'] = "";
				}
				else {
					$GLOBALS['Link'] = ProdLink(GetProdNameById($row['ordprodid']));
					$GLOBALS['Target'] = "_blank";
				}

				$GLOBALS['DownloadsLink'] = '';
				if ($row['ordprodtype'] == "digital" && $orderComplete) {
					$GLOBALS['DownloadItemEncrypted'] = $this->EncryptDownloadKey($row['orderprodid'], $row['ordprodid'], $row['orderorderid'], $row['ordtoken']);
					$GLOBALS['DownloadsLink'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("AccountOrderItemDownloadLink");
				}

				$GLOBALS['Refunded'] = '';
				$GLOBALS['StrikeEnd'] = '';
				$GLOBALS['StrikeStart'] = '';

				if ($row['ordprodrefunded'] > 0) {
					if ($row['ordprodrefunded'] == $row['ordprodqty']) {
						$GLOBALS['StrikeStart'] = "<s>";
						$GLOBALS['StrikeEnd'] = "</s>";
						$GLOBALS['Refunded'] = '<span class="Refunded">'.GetLang('OrderProductRefunded').'</span>';
					}
					else {
						$GLOBALS['Refunded'] = '<span class="Refunded">'.sprintf(GetLang('OrderProductsRefundedX'), $row['ordprodrefunded']).'</span>';
					}
				}

				$GLOBALS['Price'] = CurrencyConvertFormatPrice(
					$row[$itemTotalColumn],
					$order['ordcurrencyid'],
					$order['ordcurrencyexchangerate']
				);

				// Were there one or more options selected?
				$GLOBALS['ProductOptions'] = '';
				if($row['ordprodoptions'] != '') {
					$options = @unserialize($row['ordprodoptions']);
					if(!empty($options)) {
						$GLOBALS['ProductOptions'] = "<br /><small class='OrderItemOptions'>(";
						$comma = '';
						foreach($options as $name => $value) {
							$GLOBALS['ProductOptions'] .= $comma.isc_html_escape($name).": ".isc_html_escape($value);
							$comma = ', ';
						}
						$GLOBALS['ProductOptions'] .= ")</small>";
					}
				}

				if($row['ordprodwrapname']) {
					$GLOBALS['GiftWrappingName'] = isc_html_escape($row['ordprodwrapname']);
					$GLOBALS['HideWrappingOptions'] = '';
				}
				else {
					$GLOBALS['GiftWrappingName'] = '';
					$GLOBALS['HideWrappingOptions'] = 'display: none';
				}

				$GLOBALS['HideExpectedReleaseDate'] = 'display:none;';
				$GLOBALS['ExpectedReleaseDate'] = '';

				if ($row['prodpreorder']) {
					if ($row['prodreleasedate']) {
						$message = $row['prodpreordermessage'];
						if (!$message) {
							$message = GetConfig('DefaultPreOrderMessage');
						}
						$GLOBALS['ExpectedReleaseDate'] = '(' . str_replace('%%DATE%%', isc_date(GetConfig('DisplayDateFormat'), $row['prodreleasedate']), $message) . ')';
					} else {
						$GLOBALS['ExpectedReleaseDate'] = '(' . GetLang('PreOrderProduct') . ')';
					}
					$GLOBALS['HideExpectedReleaseDate'] = '';
				}

				$GLOBALS['ItemShippingRow'] = '';
				if ($order['shipping_address_count'] > 1 && ($previousAddressId != $row['order_address_id'])) {
					$destinationCounter++;

					$GLOBALS['Destination_Number'] = GetLang('Destination_Number', array('number' => $destinationCounter));

					$addressLine = array_filter(array(
						$row['address_1'],
						$row['address_2'],
						$row['city'],
						$row['state'],
						$row['zip'],
						$row['country'],
					));

					$GLOBALS['ItemShippingRow_AddressLine'] = Store_String::rightTruncate(implode(', ', $addressLine), 60);

					$GLOBALS['ItemShippingRow'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('AccountOrderItemShippingRow');
				}

				$GLOBALS['SNIPPETS']['AccountOrderItemRow'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("AccountOrderItemRow");
				$previousAddressId = $row['order_address_id'];
			}

			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(sprintf("%s - %s%d", GetConfig('StoreName'), GetLang('OrderIdHash'), $orderId));
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("account_order");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}

		/**
		*	Print an invoice for the selected order using the invoice_print template
		*/
		public function PrintInvoice()
		{
			if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
				echo "<script type=\"text/javascript\">window.close();</script>";
			}

			$order = getOrder($_GET['order_id']);
			if(!$order || $order['ordcustid'] != getClass('ISC_CUSTOMER')->getCustomerId()) {
				echo "<script type=\"text/javascript\">window.close();</script>";
				return;
			}

			require_once ISC_BASE_PATH . '/lib/order.printing.php';
			echo GeneratePrintableInvoicePage(array($_GET['order_id']));
		}

		private function AddressBook()
		{

			$GLOBALS['FromURL'] = urlencode("account.php?action=address_book");
			$GLOBALS['HideAddressButton'] = "none";
			$GLOBALS['CheckoutShippingTitle'] = GetLang('YourAddressBook');
			$GLOBALS['CheckoutShippingIntro'] = sprintf("%s <a href='%s/account.php?action=add_shipping_address&amp;address_type=&amp;from=%s'>%s</a>", GetLang('AddressBookIntro1'), $GLOBALS['ShopPath'], $GLOBALS['FromURL'], GetLang('AddressBookIntro2'));

			$GLOBALS['CheckoutShippingIntroNoAddresses'] = sprintf("%s <a href='%s/account.php?action=add_shipping_address&amp;address_type=&amp;from=%s'>%s</a>", GetLang('AddressBookIntro1NoAddresses'), $GLOBALS['ShopPath'], $GLOBALS['FromURL'], GetLang('AddressBookIntro2NoAddresses'));

			// Show the list of available shipping addresses
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName') . " - " . GetLang('YourAddressBook'));
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("account_addressbook");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}

		/**
		*	Load the customer's details and return them in an array or false on error.
		*/
		public function GetAccountDetails()
		{

			// Get the id of the customer
			$customer_id = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();

			$query = sprintf("select * from [|PREFIX|]customers where customerid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($customer_id));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			if ($row !== false) {
				return $row;
			}
			else {
				return false;
			}
		}

		private function EditAccount($MessageText = "", $MessageStatus = -1)
		{

			// Have we just saved the account details? If so show the appropriate message box
			if ($MessageStatus == -1) {
				$GLOBALS['HideEditAccountErrorMessage'] = "none";
				$GLOBALS['HideEditAccountSuccessMessage'] = "none";
			}
			else if ($MessageStatus == MSG_SUCCESS) {
				$GLOBALS['HideEditAccountErrorMessage'] = "none";
				$GLOBALS['HideEditAccountIntroMessage'] = "none";
				$GLOBALS['StatusMessage'] = $MessageText;

			}
			else if ($MessageStatus == MSG_ERROR) {
				$GLOBALS['HideEditAccountSuccessMessage'] = "none";
				$GLOBALS['HideEditAccountIntroMessage'] = "none";
				$GLOBALS['StatusMessage'] = $MessageText;
			}

			$customer_details = $this->GetAccountDetails();

			// Load the account details for this user
			if (!empty($customer_details)) {
				$GLOBALS['AccountFirstName'] = isc_html_escape($customer_details['custconfirstname']);
				$GLOBALS['AccountLastName'] = isc_html_escape($customer_details['custconlastname']);
				$GLOBALS['AccountCompanyName'] = isc_html_escape($customer_details['custconcompany']);
				$GLOBALS['AccountPhone'] = isc_html_escape($customer_details['custconphone']);
				$GLOBALS['AccountCurrentEmail'] = isc_html_escape($customer_details['custconemail']);
				$GLOBALS['RFC'] = isc_html_escape($customer_details['custRFC']);
				$GLOBALS['EditAccountAccountFormFieldID'] = FORMFIELDS_FORM_ACCOUNT;
				$GLOBALS['AccountFields'] = '';

				if ($MessageStatus !== -1) {
					$fillPostedValues = true;
				} else {
					$fillPostedValues = false;
				}

				if (!$fillPostedValues && isId($customer_details['custformsessionid'])) {
					$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, false, $customer_details['custformsessionid']);
				} else {
					$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, $fillPostedValues);
				}

				$GLOBALS['AccountFields'] = '';

				foreach (array_keys($fields) as $fieldId) {

					/**
					 * Fill in the email address if we have just entered the page for the first time
					 */
					if (!$fillPostedValues && isc_strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'emailaddress') {
						$fields[$fieldId]->setValue($customer_details['custconemail']);
					}

					/**
					 * Un-require the password and confirm password
					 */
					if (isc_strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'password' || isc_strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'confirmpassword') {
						$fields[$fieldId]->setRequired(false);
					}
					
					/**
					 * If Store Origin field exists (compared with label), populate it with the stores
					 */
					if (isc_strtolower($fields[$fieldId]->record['formfieldlabel']) == isc_strtolower(GetLang('StoreOriginLabel'))) {
						$fields[$fieldId]->setOptions(explode(',', getStoreOriginOptions()));
						$GLOBALS['StoreOriginDefault'] = getDefaultStoreId();
						$GLOBALS['StoreOriginNameDefault'] = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT Nombre FROM [|PREFIX|]intelisis_Sucursal WHERE Sucursal = "'.$GLOBALS['StoreOriginDefault'].'"', 'Nombre');
					}
					
					if (isc_strtolower($fields[$fieldId]->record['formfieldlabel']) == isc_strtolower(GetLang('DefaultShippingMethodLabel'))) {
						$fields[$fieldId]->setOptions(explode(',', getDefaultShippingMethodOptions()));
					}

					$GLOBALS['AccountFields'] .= $fields[$fieldId]->loadForFrontend() . "\n";
				}

				/**
				 * Load up any form field JS event data and any validation lang variables
				 */
				$GLOBALS['FormFieldRequiredJS'] = $GLOBALS['ISC_CLASS_FORM']->buildRequiredJS();

				// Show the list of available shipping addresses
				$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName') . " - " . GetLang('YourAccountDetails'));
				$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("editaccount");
				$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
			}
			else {
				// Possible session timeout
				ob_end_clean();
				header(sprintf("Location:%s/account.php", $GLOBALS['ShopPath']));
				die();
			}
		}

		/**
		*	Save the edited account details back to the database
		*/
		public function SaveAccountDetails()
		{
			/**
			 * Customer Details
			 */
			$customerMap = array(
				'EmailAddress' => 'account_email',
				'Password' => 'account_password',
				'ConfirmPassword' => 'account_password_confirm'
			);

			$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, true);

			/**
			 * Validate the field input. Unset the password and confirm password fields first
			 */
			foreach (array_keys($fields) as $fieldId) {
				if (isc_strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'password' || isc_strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'confirmpassword') {
					$fields[$fieldId]->setRequired(false);
				}
			}

			addRFCValidation($fields);
			
			$errmsg = '';
			if (!validateFieldData($fields, $errmsg)) {
				return $this->EditAccount($errmsg, MSG_ERROR);
			}

			foreach(array_keys($fields) as $fieldId) {
				if (!array_key_exists($fields[$fieldId]->record['formfieldprivateid'], $customerMap)) {
					continue;
				}

				$_POST[$customerMap[$fields[$fieldId]->record['formfieldprivateid']]] = $fields[$fieldId]->GetValue();
			}

			$customer_id = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();
			$email_taken = false;
			$phone_invalid = false;
			$password_invalid = false;

			if (isset($_POST['account_firstname']) &&
			   isset($_POST['account_lastname']) &&
			   isset($_POST['account_companyname']) &&
			   isset($_POST['account_email']) &&
			   isset($_POST['account_phone']) &&
			   isset($_POST['account_password']) &&
			   isset($_POST['account_password_confirm'])) {

					// Are they updating their email address? If so is the new email address available?
					if ($GLOBALS['ISC_CLASS_CUSTOMER']->AccountWithEmailAlreadyExists($_POST['account_email'], $customer_id)) {
						$email_taken = true;
					}

					if (!$GLOBALS['ISC_CLASS_CUSTOMER']->ValidatePhoneNumber($_POST['account_phone'])) {
						$phone_invalid = true;
					}

					$pass1 = $_POST['account_password'];
					$pass2 = $_POST['account_password_confirm'];

					if ($pass1 . $pass2 !== '' && $pass1 !== $pass2) {
						$password_invalid = true;
					}

					if (!$email_taken && !$phone_invalid && !$password_invalid) {

						$UpdatedAccount = array(
							"customerid" => $customer_id,
							"custconfirstname" => $_POST['account_firstname'],
							"custconlastname" => $_POST['account_lastname'],
							"custconcompany" => $_POST['account_companyname'],
							"custconemail" => $_POST['account_email'],
							"custconphone" => $_POST['account_phone'],
							//"custRFC" => strtoupper($_POST['account_rfc'])
						);

						// Do we need to update the password?
						if ($pass1 == $pass2 && $pass1 != "") {
							$UpdatedAccount['custpassword'] = $pass1;
						}

						$existingCustomer = $this->customerEntity->get($customer_id);

						/**
						 * Create/Update our form session data
						 */
						if (isId($existingCustomer['custformsessionid'])) {
							$GLOBALS['ISC_CLASS_FORM']->saveFormSession(FORMFIELDS_FORM_ACCOUNT, true, $existingCustomer['custformsessionid']);
						} else {
							$UpdatedAccount['custformsessionid'] = $GLOBALS['ISC_CLASS_FORM']->saveFormSession(FORMFIELDS_FORM_ACCOUNT);
						}

						if ($this->customerEntity->edit($UpdatedAccount)) {
							$this->EditAccount(GetLang('AccountDetailsUpdatedSuccess'), MSG_SUCCESS);
						} else {
							$this->EditAccount(GetLang('AccountDetailsUpdatedFailed'), MSG_ERROR);
						}
					}
					else if ($email_taken) {
						// Email address is already taken
						$this->EditAccount(sprintf(GetLang('AccountUpdateEmailTaken'), $_POST['account_email']), MSG_ERROR);
					}
					else if ($phone_invalid) {
						// Phone number is invalid
						$this->EditAccount(sprintf(GetLang('AccountUpdateValidPhone'), $_POST['account_phone']), MSG_ERROR);
					}
					else if ($password_invalid) {
						$this->EditAccount(GetLang('AccountPasswordsDontMatch'), MSG_ERROR);
					}
			}
			else {
				ob_end_clean();
				header(sprintf("Location: %s/account.php", $GLOBALS['ShopPath']));
				die();
			}
		}

		/**
		*	Show a list of items that the customer has recently viewed by browsing our site
		*/
		private function ShowRecentItems()
		{

			$viewed = "";

			if (isset($_COOKIE['RECENTLY_VIEWED_PRODUCTS'])) {
				$viewed = $_COOKIE['RECENTLY_VIEWED_PRODUCTS'];
			} else if (isset($_SESSION['RECENTLY_VIEWED_PRODUCTS'])) {
				$viewed = $_SESSION['RECENTLY_VIEWED_PRODUCTS'];
			}

			if ($viewed != "") {
				$GLOBALS['HideNoRecentItemsMessage'] = "none";
				$GLOBALS['SNIPPETS']['AccountRecentItems'] = "";

				$viewed_products = array();
				$viewed_products = explode(",", $viewed);
				foreach ($viewed_products as $k => $p) {
					$viewed_products[$k] = (int) $p;
				}

				// Reverse the array so recently viewed products appear up top
				$viewed_products = array_reverse($viewed_products);

				// Hide the compare button if there's less than 2 products
				if (GetConfig('EnableProductComparisons') == 0 || count($viewed_products) < 2) {
					$GLOBALS['HideCompareItems'] = "none";
				}

				if (!empty($viewed_products)) {
					if(!getProductReviewsEnabled()) {
						$GLOBALS['HideProductRating'] = "display: none";
					}
					$query = "
						SELECT p.*, FLOOR(prodratingtotal/prodnumratings) AS prodavgrating, pi.*, ".GetProdCustomerGroupPriceSQL()."
						FROM [|PREFIX|]products p
						LEFT JOIN [|PREFIX|]product_images pi ON (productid=pi.imageprodid AND imageisthumb=1)
						WHERE prodvisible='1' AND productid in ('".implode("','", $viewed_products)."')
						".GetProdCustomerGroupPermissionsSQL()."
					";
					$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
					if ($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
						$GLOBALS['AlternateClass'] = '';
						while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
							if($GLOBALS['AlternateClass'] == 'Odd') {
								$GLOBALS['AlternateClass'] = 'Even';
							}
							else {
								$GLOBALS['AlternateClass'] = 'Odd';
							}

							$GLOBALS['ProductId'] = (int) $row['productid'];
							$GLOBALS['ProductName'] = isc_html_escape($row['prodname']);
							$GLOBALS['ProductLink'] = ProdLink($row['prodname']);
							$GLOBALS['ProductRating'] = (int)$row['prodavgrating'];

							// Determine the price of this product
							$GLOBALS['ProductPrice'] = '';
							if (GetConfig('ShowProductPrice') && !$row['prodhideprice']) {
								$GLOBALS['ProductPrice'] = formatProductCatalogPrice($row);
							}

							$GLOBALS['ProductThumb'] = ImageThumb($row, ProdLink($row['prodname']));

							$GLOBALS['SNIPPETS']['AccountRecentItems'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("AccountRecentlyViewedProducts");
						}
					}
				}
				else {
					$GLOBALS['HideRecentItemList'] = "none";
				}
			}
			else {
				$GLOBALS['HideRecentItemList'] = "none";
			}

			$GLOBALS['CompareLink'] = CompareLink();

			// Show the list of available shipping addresses
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName') . " - " . GetLang('RecentlyViewedItems'));
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("account_recentitems");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}


		/**
		* Get the list of items in an order that are not re-orderable
		* @param array the item details in the order
		* @param array lists of product ids in the order
		*
		* @return array list of unreorderable products
		*/
		private function GetUnreorderableProducts($OrderProducts, $ProductIds)
		{
			$UnreorderableProducts = array();
			if(empty($OrderProducts) || empty($ProductIds)) {
				return $UnreorderableProducts;
			}

			$ValidProductIds = array();
			$ProductInventory = array();
			$ValidVariations = array();
			$ProductsRequireVariations = array();
			$ProdductWrapIDs = array();
			$ConfigFieldChangedProds = array();
			$GiftWrapIds = array();
			$MinimumQuantities = array();
			$MaximumQuantities = array();

			$orderProductIds = implode(',', array_unique($ProductIds));


			//Get giftwraping details
			$query = "SELECT wrapid FROM [|PREFIX|]gift_wrapping";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$GiftWrapIds[] = $row['wrapid'];
			}

			//Get the current product and product variation information for the ordered products
			$query = "SELECT p.productid, p.prodname, p.prodcurrentinv, p.prodinvtrack, p.prodoptionsrequired, p.prodwrapoptions, p.prodvariationid, p.prodeventdaterequired, p.prodeventdatelimited, p.prodeventdatelimitedtype,p.prodeventdatelimitedstartdate, p.prodvisible, p.prodallowpurchases,p.prodeventdatelimitedenddate, p.prodminqty, p.prodmaxqty, vc.vcenabled, vc.combinationid, vc.vcstock
						FROM [|PREFIX|]products p
						LEFT JOIN [|PREFIX|]product_variation_combinations vc
						ON vc.vcproductid = p.productid
						WHERE p.productid IN (".$orderProductIds.")";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			while($ProdDetail = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {

				if(!in_array($ProdDetail['productid'], $ValidProductIds)) {
					$ValidProductIds[] = $ProdDetail['productid'];

					$ProductInventory[$ProdDetail['productid']] = array(
											'currentinv' => $ProdDetail['prodcurrentinv'],
											'invtrack' => $ProdDetail['prodinvtrack']
										);

					if($ProdDetail['prodoptionsrequired'] == 1 && $ProdDetail['prodvariationid'] != 0) {
						$ProductsRequireVariations[] = $ProdDetail['productid'];
					}

					$ProductEventDate[$ProdDetail['productid']] = array(
								'Required' => $ProdDetail['prodeventdaterequired'],
								'LimitEnabled' => $ProdDetail['prodeventdatelimited'],
								'LimitType' => $ProdDetail['prodeventdatelimitedtype'],
								'StartDate' => $ProdDetail['prodeventdatelimitedstartdate'],
								'EndDate' => $ProdDetail['prodeventdatelimitedenddate']
					);
					//store giftwraping details
					$ProductWrapIDs[$ProdDetail['productid']] = $ProdDetail['prodwrapoptions'];
					$ProductVisibility[$ProdDetail['productid']] = $ProdDetail['prodvisible'];
					$ProductAllowPurchase[$ProdDetail['productid']] = $ProdDetail['prodallowpurchases'];
					$MinimumQuantities[$ProdDetail['productid']] = (int)$ProdDetail['prodminqty'];
					$MaximumQuantities[$ProdDetail['productid']] = (int)$ProdDetail['prodmaxqty'];
					if ($MaximumQuantities[$ProdDetail['productid']] === 0) {
						$MaximumQuantities[$ProdDetail['productid']] = INF;
					}
				}

				//ser prduct inventory data
				if($ProdDetail['prodinvtrack'] == 1) {
					if(!isset($ProductInventory[$ProdDetail['productid']])) {
						$ProductInventory[$ProdDetail['productid']] = array(
											'currentinv' => $ProdDetail['prodcurrentinv'],
											'invtrack' => $ProdDetail['prodinvtrack']
										);
					}
				} else if($ProdDetail['prodinvtrack'] == 2) {
						$ProductInventory[$ProdDetail['productid']]['invtrack'] = $ProdDetail['prodinvtrack'];
						$ProductInventory[$ProdDetail['productid']][$ProdDetail['combinationid']] = $ProdDetail['vcstock'];
				}

				if($ProdDetail['vcenabled']) {
					$ValidVariations[$ProdDetail['productid']][] = $ProdDetail['combinationid'];
				}


			}

			//for each ordered products, if the variation combinations is still a valid combination
			foreach ($OrderProducts as $OrderProduct) {
				// if the product is a gift certificate, can't reorder it
				if($OrderProduct['ordprodtype'] == 'giftcertificate') {
					$UnreorderableProducts[$OrderProduct['orderprodid']] = sprintf(GetLang('CantReorderGiftCertificate'), 'giftcertificates.php');
					continue;
				}

				//if the product doesn't exist or visible or allow purchase anymore
				if(!in_array($OrderProduct['ordprodid'], $ValidProductIds) || $ProductVisibility[$OrderProduct['ordprodid']] == 0 || $ProductAllowPurchase[$OrderProduct['ordprodid']] == 0) {
					$UnreorderableProducts[$OrderProduct['orderprodid']] = GetLang('ProductNotExist');
					continue;
				}

				//if gift wrapping option is invalid
				if(!$this->IsGiftWrappingValid($OrderProduct['ordprodwrapid'], $ProductWrapIDs[$OrderProduct['ordprodid']], $GiftWrapIds)) {
					$UnreorderableProducts[$OrderProduct['orderprodid']] = GetLang('GiftWrappingChanged');
					continue;
				}


				if(!$this->IsProductInStock($ProductInventory, $OrderProduct)) {
					$UnreorderableProducts[$OrderProduct['orderprodid']] = GetLang('ProductOutOfStock');
					continue;
				}

				if($this->HasProductVariationsChanged($OrderProduct, $ProductsRequireVariations, $ValidVariations)) {
					$UnreorderableProducts[$OrderProduct['orderprodid']] = GetLang('VariationCombinationChanged');
					continue;
				}

				if(!$this->IsEventDateValid($OrderProduct['ordprodeventdate'], $ProductEventDate[$OrderProduct['ordprodid']])) {
					$UnreorderableProducts[$OrderProduct['orderprodid']] = GetLang('EventDateChanged');
					continue;
				}

				if ((int)$OrderProduct['ordprodqty'] < $MinimumQuantities[$OrderProduct['ordprodid']]) {
					// previously ordered quantity is lower than the present minimum
					$UnreorderableProducts[$OrderProduct['orderprodid']] = GetLang('MinimumQuantityApplies');
					continue;
				}

				if ((int)$OrderProduct['ordprodqty'] > $MaximumQuantities[$OrderProduct['ordprodid']]) {
					// previously ordered quantity is higher than the present maximum
					$UnreorderableProducts[$OrderProduct['orderprodid']] = GetLang('MaximumQuantityApplies');
					continue;
				}
			}

			//Check the configurable fields for the products that have passed the previous checks
			$OrderableProducts = array_diff(array_keys($OrderProducts), $UnreorderableProducts);
			if(!empty($OrderableProducts)) {
				foreach($OrderableProducts as $OrdProdId) {
					$FurtherCheckingProducts[$OrdProdId] = $OrderProducts[$OrdProdId]['ordprodid'];
				}
				$ConfigFieldChangedProds = $this->GetConfigFieldsChangedProds($FurtherCheckingProducts);
			}
			$UnreorderableProducts = $UnreorderableProducts+$ConfigFieldChangedProds;
			return $UnreorderableProducts;
		}

		private function IsEventDateValid($OrdProdEventDate, $ProductEventDate)
		{
			//if evendate entered in the order, check if it's still valid
			if($OrdProdEventDate>0) {
				//no eventdate for this product anymore
				if($ProductEventDate['Required']==0) {
					return false;
				}
				switch ($ProductEventDate['LimitType']) {
					case '2': //if limit start date
						if($OrdProdEventDate<$ProductEventDate['StartDate']) {
							return false;
						}
						break;
					case '3': //if limit start date
						if($OrdProdEventDate>$ProductEventDate['EndDate']) {
							return false;
						}
						break;
					default://if limit between a date range
						if($OrdProdEventDate<$ProductEventDate['StartDate'] || $OrdProdEventDate>$ProductEventDate['EndDate']) {
							return false;
						}
						break;
				}

			// if evendate is not entered in the order, check if evendate is required
			} else {
				//if product event date is required
				if($ProductEventDate['Required']==1) {
					return false;
				}
			}
			return true;
		}

		private function IsGiftWrappingValid($OrdProdWrapId, $ProductWrapIDs, $GiftWrapIds)
		{

				if($OrdProdWrapId != 0) {

					switch ($ProductWrapIDs) {
						//product is not allowed to be gift wrapped
						case '-1': {
							return false;
						}
						//all gift wrapping options can be used for this item
						case '0': {
							if(!in_array($OrdProdWrapId, $GiftWrapIds)) {
								return false;
							}
							break;
						}
						//selected gift wrapping options can be used for this item
						default: {
							if(!in_array($OrdProdWrapId, explode(',', $ProductWrapIDs))) {
								return false;
							}
							break;
						}
					}
				}
				return true;
		}


		private function GetConfigFieldsChangedProds($OrderProducts)
		{
			$UnreorderableProducts = array();
			$ProductRequiredFields = array();
			$ProductFields = array();
			$OrdProdFields = array();
			$orderProductIds = implode(",", array_keys($OrderProducts));


			//Get the configurable field ids from the previous order for this product
			$query = "SELECT fieldid, productid,  ordprodid
						FROM [|PREFIX|]order_configurable_fields o
						WHERE o.ordprodid IN (".$orderProductIds.")";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$OrdProdFields[$row['ordprodid']]['productid'] = $row['productid'];
				$OrdProdFields[$row['ordprodid']]['ordprodfieldid'][] = $row['fieldid'];
			}

			$ProductIds = implode(",", $OrderProducts);
			$query = "SELECT productfieldid, fieldrequired,fieldprodid
						FROM [|PREFIX|]product_configurable_fields
						WHERE fieldprodid in (".$ProductIds.")";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$ProductFields[$row['fieldprodid']][] = $row['productfieldid'];
				//get required fields for product
				if($row['fieldrequired']==1) {
					$ProductRequiredFields[$row['fieldprodid']][] = $row['productfieldid'];
				}
			}

			foreach($OrderProducts as $OrdProdId => $ProductId) {
				//if the prodcut has got configurable fields
				if(isset($ProductFields[$ProductId]) && !empty($ProductFields[$ProductId])) {

					if(isset($OrdProdFields[$OrdProdId]['ordprodfieldid'])) {
						//if all fields that were entered in the previous order are valid
						$InvalidFields = array_diff($OrdProdFields[$OrdProdId]['ordprodfieldid'], $ProductFields[$ProductId]);
						if(!empty($InvalidFields)) {
							$UnreorderableProducts[$OrdProdId] = GetLang('VariationCombinationChanged');
							continue;
						}
					}

					//if required fields weren't entered in the previous order item
					if(isset($ProductRequiredFields[$ProductId])) {
						//if the product in the order has also got configurable fields
						if(isset($OrdProdFields[$OrdProdId]['ordprodfieldid'])) {
							$MissingRequiredFields = array_diff($ProductRequiredFields[$ProductId], $OrdProdFields[$OrdProdId]['ordprodfieldid']);

							if(!empty($MissingRequiredFields)) {
								$UnreorderableProducts[$OrdProdId] =  GetLang('VariationCombinationChanged');
								continue;
							}
						//if no configuarable fields have been entered for the product in the previous order.
						} else {
							$UnreorderableProducts[$OrdProdId] = GetLang('VariationCombinationChanged');
							continue;
						}
					}
				//if the product doesn't have any configurable fields but the ordered product has got entries for it.
				} elseif(isset($OrdProdFields[$OrdProdId]['ordprodfieldid']) && !empty($OrdProdFields[$OrdProdId]['ordprodfieldid'])) {
						$UnreorderableProducts[$OrdProdId] = GetLang('VariationCombinationChanged');
						continue;
				}
			}
			return $UnreorderableProducts;
		}

		private function IsProductInStock(&$ProductInventory, $OrderProduct)
		{
			//check inventory level if the inventory track on the main product
			if($ProductInventory[$OrderProduct['ordprodid']]['invtrack'] == 1){
				if($ProductInventory[$OrderProduct['ordprodid']]['currentinv'] < $OrderProduct['ordprodqty']) {
					return false;
				} else {
					$ProductInventory[$OrderProduct['ordprodid']]['currentinv'] -= $OrderProduct['ordprodqty'];
				}
			//Check product inventory for variation
			} else if($ProductInventory[$OrderProduct['ordprodid']]['invtrack'] == 2) {
				if(isset($ProductInventory[$OrderProduct['ordprodid']][$OrderProduct['ordprodvariationid']])) {
					$CurrentInv = $ProductInventory[$OrderProduct['ordprodid']][$OrderProduct['ordprodvariationid']];

					if($CurrentInv < $OrderProduct['ordprodqty']) {
						return false;
					} else {
						$ProductInventory[$OrderProduct['ordprodid']][$OrderProduct['ordprodvariationid']] -= $OrderProduct['ordprodqty'];
					}
				}
			}
			return true;
		}

		private function HasProductVariationsChanged($OrderProduct, $ProductsRequireVariations, $ValidVariations)
		{
			//if no variation is selected in the previous order, Check if the product has a force variation now
			if ($OrderProduct['ordprodvariationid'] == 0) {
				//if the product is one of the products that requires variaions, then it's not reorderable
				if (in_array($OrderProduct['ordprodid'], $ProductsRequireVariations)) {
					return true;
				}

			//otherwise variation is selected in the previous order, check if the variation is still valid
			} else {
				//if the ordered product variation id is a valid variation combination ID for the product
				if(!in_array($OrderProduct['ordprodvariationid'], $ValidVariations[$OrderProduct['ordprodid']])) {
					return true;
				}
			}
			return false;
		}

		/**
		* Check if any items in the order cannot be re-ordered
		* Redirect users to the order details page if some items cant be re-ordered
		* Add products to cart if all items can be re-ordered.
		*
		*/
		private function DoReorder()
		{
			$OrderId = $_REQUEST['order_id'];
			$ProductIds = array();

			// Load up the items in this order
			$query = "SELECT *
						FROM [|PREFIX|]orders o
						LEFT JOIN [|PREFIX|]order_products p ON p.orderorderid = o.orderid
						WHERE o.orderid = " . (int)$OrderId . " AND o.deleted = 0";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			//check if products are reorderable
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$OrderProducts[$row['orderprodid']] = $row;
				$ProductIds[] = $row['ordprodid'];
			}
			$ProductIds = array_unique($ProductIds);
			$UnreorderableProducts = $this->GetUnreorderableProducts($OrderProducts, $ProductIds);
			$GLOBALS['ErrorMessage'] = '';
			if(!empty($UnreorderableProducts)) {
				FlashMessage(GetLang("ItemsCantBeReordered"), MSG_ERROR);
				ob_end_clean();
				header(sprintf("Location: %s/account.php?action=view_order&order_id=%s&reorder=1", $GLOBALS['ShopPath'], $OrderId));
			} else {
				ob_end_clean();
				header(sprintf("Location: %s/cart.php?action=addreorderitems&orderid=%s", $GLOBALS['ShopPath'], $OrderId));

			}
		}
	}
