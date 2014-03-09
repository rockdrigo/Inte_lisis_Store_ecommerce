<?php
/**
 * Fetch an order from the database based on the specified order ID.
 *
 * @param int $orderId The order ID
 * @param boolean $products True to fetch products in the order too (default: true)
 * @param boolean $hardRefresh ? (default: false)
 * @param boolean $includeDeleted allow this function to return a deleted order (default: false)
 * @return array Array of fetched information for this product.
 */
function GetOrder($orderId, $products = null, $hardRefresh = null, $includeDeleted = null)
{
	static $orderCache;

	if ($products === null) {
		$products = true;
	}

	if ($hardRefresh === null) {
		$hardRefresh = false;
	}

	if ($includeDeleted === null) {
		$includeDeleted = false;
	}

	if (isset($orderCache[$orderId]) && !$hardRefresh) {
		if($products == false || ($products == true && isset($orderCache[$orderId]['products']))) {
			return $orderCache[$orderId];
		}
	}

	$query = "SELECT * FROM [|PREFIX|]orders WHERE orderid='" . $GLOBALS['ISC_CLASS_DB']->Quote($orderId) . "'";
	if (!$includeDeleted) {
		$query .= " AND deleted = 0";
	}

	$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
	if (!$result) {
		return false;
	}

	$order = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	if (!$order) {
		return false;
	}

	// Do we need to fetch the products in this order too?
	if ($products == true) {
		$order['products'] = array();

		$query = "
			SELECT
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
				[|PREFIX|]order_products op
				LEFT JOIN [|PREFIX|]products p ON p.productid = op.ordprodid
				LEFT JOIN [|PREFIX|]order_addresses oa ON oa.`id` = op.order_address_id
			WHERE
				orderorderid = " . (int)$orderId . "
			ORDER BY
				order_address_id,
				ordprodname";

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($product = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$order['products'][$product['orderprodid']] = $product;
		}
	}

	$orderCache[$orderId] = $order;
	return $order;
}

/**
 *	Is an order completed? If the status is complete or shipped then it is.
 *
 * @param int The status of the order
 * @return boolean True if the order is complete
 */
function OrderIsComplete($OrderStatus)
{
	if ($OrderStatus == ORDER_STATUS_COMPLETED || $OrderStatus == ORDER_STATUS_SHIPPED) {
		return true;
	}
	else {
		return false;
	}
}

// ISC-1141 removed OrderExists() - doesn't seem to be referenced anywhere

/**
 * Get a friendly name for an order status from the database.
 *
 * @param int The order status ID
 * @return string The status description/name/
 */
function GetOrderStatusById($StatusId)
{
	static $status = Array();

	if (empty($status)) {
		$query = "select statusid, statusdesc from [|PREFIX|]order_status";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$status[$row['statusid']] = $row['statusdesc'];
		}
	}

	if (isset($status[$StatusId])) {
		return $status[$StatusId];
	} else {
		return '';
	}
}


/**
* get the product fields data for each order
*
* @param int $orderId, order id
*
* @return array an array of product fields data
*/
function GetOrderProductFieldsData($orderId)
{
	$query = "SELECT o.*
				FROM [|PREFIX|]order_configurable_fields o
					JOIN [|PREFIX|]product_configurable_fields p ON o.fieldid = p.productfieldid
				WHERE
					o.orderid=".(int)$orderId."
				ORDER BY p.fieldsortorder ASC";

	$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

	$fields = array();
	while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
		$fields[$row['ordprodid']][] = $row;
	}

	return $fields;
}

function LoadEmailOrderProductFields($fields)
{
	$productFields = '';

	//each configurable field customer submited
	foreach($fields as $row) {

		$fieldValue = '-';
		$fieldName = $row['fieldname'];
		switch($row['fieldtype']) {
			case 'file': {
				//file is an image, display the image
				if (preg_match('/image/', $row['filetype'])) {
					$fieldValue = "<img width='50' src ='".$GLOBALS['ShopPath']."/viewfile.php?orderprodfield=".$row['orderfieldid']."' />";
				}
				//file other than image, display the file name
				else {
					$fieldValue = isc_html_escape($row['originalfilename']);
				}
				break;
			}
			default: {
				if(isc_strlen($row['textcontents'])>50) {
					$fieldValue = isc_html_escape(isc_substr($row['textcontents'], 0, 50))." ..";
				} else {
					$fieldValue = isc_html_escape($row['textcontents']);
				}
				break;
			}
		}

		if($fieldValue!='') {
			$productFields .= "<tr><td>".isc_html_escape($fieldName).":</td>";
			$productFields .= "<td>".$fieldValue."</td></tr>";
		}
	}

	return $productFields;
}

/**
 *	Email the invoice from an order to a customer
 *
 * @param int The ID of the order to email the invoice for.
 * @param int The optional ID of the order status. Will default to the already stored status ID of the order
 */
function EmailInvoiceToCustomer($orderId, $newStatusId=null)
{
	// Load the details for this order
	$order_row = GetOrder($orderId, true);
	if($order_row === false) {
		return false;
	}

	// All prices in the emailed invoices will be shown in the default currency of the store
	$defaultCurrency = GetDefaultCurrency();

	$GLOBALS['OrderNumber'] = $orderId;

	if (isId($newStatusId)) {
		$order_status = $newStatusId;
	} else {
		$order_status = $order_row['ordstatus'];
	}

	$order_payment_module = $order_row['orderpaymentmodule'];

	if($order_row['ordcustid'] > 0) {
		$GLOBALS['ViewOrderStatusMsg'] = GetLang('ASummaryIsShownBelow')." <a href='".$GLOBALS['ShopPath']."/orderstatus.php'>".GetLang('ClickHere')."</a>.";
	} else {
		$GLOBALS['ViewOrderStatusMsg'] = "";
	}

	$emailTemplate = FetchEmailTemplateParser();

	if ($order_row['shipping_address_count'] > 1) {
		// multiple shipping addresses
		$GLOBALS['ShippingAddress'] = GetLang('OrderWillBeShippedToMultipleAddresses');
	} else if ($order_row['shipping_address_count'] == 0) {
		// no shipping addresses (digital order)
		$GLOBALS['ShippingAddress'] = GetLang('ShippingImmediateDownload');
	} else {
		// single shipping address
		$address = $GLOBALS['ISC_CLASS_DB']->FetchRow("
			SELECT
				oa.*
			FROM
				`[|PREFIX|]order_addresses` oa
			WHERE
				oa.order_id = " . (int)$order_row['orderid'] . "
		");

		$GLOBALS['ShipFullName'] = isc_html_escape($address['first_name'].' '.$address['last_name']);

		$GLOBALS['ShipCompany'] = '';
		if($address['company']) {
			$GLOBALS['ShipCompany'] = '<br />'.isc_html_escape($address['company']);
		}

		$GLOBALS['ShipAddressLines'] = isc_html_escape($address['address_1']);

		if ($address['address_2'] != "") {
			$GLOBALS['ShipAddressLines'] .= '<br />' . isc_html_escape($address['address_2']);
		}

		$GLOBALS['ShipSuburb'] = isc_html_escape($address['city']);
		$GLOBALS['ShipState'] = isc_html_escape($address['state']);
		$GLOBALS['ShipZip'] = isc_html_escape($address['zip']);
		$GLOBALS['ShipCountry'] = isc_html_escape($address['country']);
		$GLOBALS['ShipPhone'] = isc_html_escape($address['phone']);

		// show shipping email, if any
		if(!$address['email']) {
			$GLOBALS['HideShippingEmail'] = 'display: none';
		} else {
			$GLOBALS['ShippingEmail'] = $address['email'];
		}

		$GLOBALS['ShippingAddress'] = $emailTemplate->GetSnippet("AddressLabel");
	}

	// Format the billing address
	$GLOBALS['ShipFullName'] = isc_html_escape($order_row['ordbillfirstname'].' '.$order_row['ordbilllastname']);

	$GLOBALS['ShipCompany'] = '';
	if($order_row['ordbillcompany']) {
		$GLOBALS['ShipCompany'] = '<br />'.isc_html_escape($order_row['ordbillcompany']);
	}

	$GLOBALS['ShipAddressLines'] = isc_html_escape($order_row['ordbillstreet1']);

	if ($order_row['ordbillstreet2'] != "") {
		$GLOBALS['ShipAddressLines'] .= '<br />' . isc_html_escape($order_row['ordbillstreet2']);
	}

	$GLOBALS['ShipSuburb'] = isc_html_escape($order_row['ordbillsuburb']);
	$GLOBALS['ShipState'] = isc_html_escape($order_row['ordbillstate']);
	$GLOBALS['ShipZip'] = isc_html_escape($order_row['ordbillzip']);
	$GLOBALS['ShipCountry'] = isc_html_escape($order_row['ordbillcountry']);
	$GLOBALS['ShipPhone'] = isc_html_escape($order_row['ordbillphone']);

	// show billing email, if any
	if(!$order_row['ordbillemail']) {
		$GLOBALS['HideBillingEmail'] = 'display: none';
	} else {
		$GLOBALS['BillingEmail'] = $order_row['ordbillemail'];
	}

	$GLOBALS['BillingAddress'] = $emailTemplate->GetSnippet("AddressLabel");

	// Format the shipping provider's details
	$shippingCostColumn = 'cost_ex_tax';
	$itemPriceColumn = 'price_ex_tax';
	$itemTotalColumn = 'total_ex_tax';
	$subTotalColumn = 'subtotal_ex_tax';

	if(getConfig('taxDefaultTaxDisplayOrders') == TAX_PRICES_DISPLAY_INCLUSIVE) {
		$shippingCostColumn = 'cost_inc_tax';
		$itemPriceColumn = 'price_inc_tax';
		$itemTotalColumn = 'total_inc_tax';
		$subTotalColumn = 'subtotal_inc_tax';
	}

	$GLOBALS['TotalCost'] = FormatPrice($order_row['total_inc_tax'], false, true, false, $defaultCurrency, true);

	$email = $order_row['ordbillemail'];
	if(!$order_row['ordbillemail']) {
		// Get the customer's email address
		$query = sprintf("select custconemail from [|PREFIX|]customers where customerid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($order_row['ordcustid']));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$email = $row['custconemail'];
		}
	}

	if(!$email) {
		return false;
	}

	$prodHasSKU = false;
	$WrapCost = 0;
	$fieldArray = GetOrderProductFieldsData($orderId);

	// We need to loop throuh all the prodcts to see if any of them have an SKU
	foreach($order_row['products'] as $product_row) {
		if (trim($product_row['ordprodsku']) !== '') {
			$prodHasSKU = true;
		}
	}

	// OK, now set the proper columns for the product list
	if ($prodHasSKU) {
		$GLOBALS['CartItemColumns'] = $emailTemplate->GetSnippet("InvoiceProductColumns");
	} else {
		$GLOBALS['CartItemColumns'] = $emailTemplate->GetSnippet("InvoiceProductColumnsNoSKU");
	}

	$GLOBALS['SNIPPETS']['CartItems'] = '';
	$previousAddressId = null;
	foreach($order_row['products'] as $product_row) {
		if ($order_row['shipping_address_count'] > 1 && $product_row['order_address_id'] != $previousAddressId) {
			if ($product_row['order_address_id']) {
				$addressLine = array_filter(array(
					$product_row['address_1'],
					$product_row['address_2'],
					$product_row['city'],
					$product_row['state'],
					$product_row['zip'],
					$product_row['country'],
				));
				$addressLine = GetLang('ItemsShippedTo') . ' ' . Store_String::rightTruncate(implode(', ', $addressLine), 70);
			} else {
				$addressLine = GetLang('ItemsShippedToDigital');
			}

			$GLOBALS['AddressLine'] = $addressLine;
			$GLOBALS['SNIPPETS']['CartItems'] .= $emailTemplate->GetSnippet("InvoiceProductShipRow");
			$previousAddressId = $product_row['order_address_id'];
		}

		$pOptions = '';
		if($product_row['ordprodoptions'] != '') {
			$options = @unserialize($product_row['ordprodoptions']);
			if(!empty($options)) {
				$pOptions = "<br /><small>(";
				$comma = '';
				foreach($options as $name => $value) {
					$pOptions .= $comma.isc_html_escape($name).": ".isc_html_escape($value);
					$comma = ', ';
				}
				$pOptions .= ")</small>";
			}
		}
		$GLOBALS['ProductOptions'] = $pOptions;
		$GLOBALS['EventDate'] = '';
		if ($product_row['ordprodeventdate']) {
			$GLOBALS['EventDate'] = '<br /><span style="padding-left : 10px; padding-bottom:10px; font-size:11px; font-style:italic">('.$product_row['ordprodeventname'] . ': ' . isc_date('dS M Y', $product_row['ordprodeventdate']) . ')</span>';
		}
		$GLOBALS['ProductPrice'] = FormatPrice($product_row[$itemPriceColumn], false, true, false, $defaultCurrency, true);
		$GLOBALS['ProductTotal'] = FormatPrice($product_row[$itemTotalColumn], false, true, false, $defaultCurrency, true);
		$GLOBALS['ProductQuantity'] = $product_row['ordprodqty'];

		$GLOBALS['ProductName'] = isc_html_escape($product_row['ordprodname']);

		$GLOBALS['ProductSku'] = ' ';
		if ($prodHasSKU && trim($product_row['ordprodsku']) !== '') {
			$GLOBALS['ProductSku'] = isc_html_escape($product_row['ordprodsku']);
		}

		// If this is a digital download and the order is complete, append a download link to the name of the product
		if($product_row['ordprodtype'] == 'digital' && OrderIsComplete($order_status)) {
			$GLOBALS['ISC_CLASS_ACCOUNT'] = GetClass('ISC_ACCOUNT');
			$downloadEncrypted = $GLOBALS['ISC_CLASS_ACCOUNT']->EncryptDownloadKey($product_row['orderprodid'], $product_row['ordprodid'], $orderId, $order_row['ordtoken']);
			$downloadLink = $GLOBALS['ShopPathSSL'].'/account.php?action=download_item&amp;data=' . $downloadEncrypted;
			$GLOBALS['ProductName'] .= ' (<a href="'.$downloadLink.'">'.GetLang('DownloadLink').'</a>)';
		}

		$GLOBALS['CartProductFields'] = '';
		if(isset($fieldArray[$product_row['orderprodid']])) {
			$GLOBALS['CartProductFields'] = LoadEmailOrderProductFields($fieldArray[$product_row['orderprodid']]);
		}

		if(isset($product_row['ordprodwrapcost'])) {
			$WrapCost += $product_row['ordprodwrapcost'];
		}

		$GLOBALS['ExpectedReleaseDate'] = '';

		if ($product_row['prodpreorder']) {
			if ($product_row['prodreleasedate']) {
				$message = $product_row['prodpreordermessage'];
				if (!$message) {
					$message = GetConfig('DefaultPreOrderMessage');
				}
				$GLOBALS['ExpectedReleaseDate'] = '(' . str_replace('%%DATE%%', isc_date(GetConfig('DisplayDateFormat'), $product_row['prodreleasedate']), $message) . ')';
			} else {
				$GLOBALS['ExpectedReleaseDate'] = '(' . GetLang('PreOrderProduct') . ')';
			}
		}

		if ($prodHasSKU) {
			$GLOBALS['SNIPPETS']['CartItems'] .= $emailTemplate->GetSnippet("InvoiceCartItem");
		} else {
			$GLOBALS['SNIPPETS']['CartItems'] .= $emailTemplate->GetSnippet("InvoiceCartItemNoSKU");
		}
	}

	$totalRows = getOrderTotalRows($order_row);
	$GLOBALS['SNIPPETS']['TotalRows'] = '';
	foreach($totalRows as $row) {
		$emailTemplate->assign('label', isc_html_escape($row['label']));
		$emailTemplate->assign('value', formatPrice($row['value'], false, true, false, $defaultCurrency, true));
		$GLOBALS['SNIPPETS']['TotalRows'] .= $emailTemplate->getSnippet('InvoiceTotalRow');
	}

	// Set the shipping method
	if ($order_row['ordisdigital']) {
		$GLOBALS['ShippingMethod'] = GetLang('ImmediateDownload');
	} else {
		$GLOBALS['ShippingMethod'] = sprintf(GetLang('FreeShippingFromX'), $GLOBALS['StoreName']);
	}

	// What's the status of the order? If it's awaiting payment (7) then show the awaiting payment notice
	if ($order_status == 7) {
		// Get the awaiting payment snippet, for offline payment providers also show the "how to pay for your order" message"
		$checkout_provider = null;
		GetModuleById('checkout', $checkout_provider, $order_payment_module);
		if (is_object($checkout_provider) && $checkout_provider->getpaymenttype() == PAYMENT_PROVIDER_OFFLINE && method_exists($checkout_provider, 'GetOfflinePaymentMessage')) {
			$paymentData = array(
				'orders' => array($order_row['orderid'] => $order_row)
			);
			$checkout_provider->SetOrderData($paymentData);
			$GLOBALS['PaymentGatewayAmount'] = CurrencyConvertFormatPrice($order_row['total_inc_tax'], $order_row['ordcurrencyid'], $order_row['ordcurrencyexchangerate'], true);
			$GLOBALS['PaymentMessage'] = $checkout_provider->GetOfflinePaymentMessage();
			$GLOBALS['PendingPaymentDetails'] = $emailTemplate->GetSnippet("InvoicePendingPaymentDetails");
			$GLOBALS['PendingPaymentNotice'] = $emailTemplate->GetSnippet("InvoicePendingPaymentNotice");
		}
	}

	$GLOBALS['OrderCommentBlock'] = '';
	if($order_row['ordcustmessage'] != '') {
		$GLOBALS['OrderComments'] = isc_html_escape($order_row['ordcustmessage']);
		$GLOBALS['OrderCommentBlock'] = $emailTemplate->GetSnippet("InvoiceOrderComment");
	}

	$emailTemplate->SetTemplate("invoice_email");
	$message = $emailTemplate->ParseTemplate(true);

	// Create a new email API object to send the email
	$store_name = GetConfig('StoreName');

	$obj_email = GetEmailClass();
	$obj_email->From(GetConfig('OrderEmail'), $store_name);
	$obj_email->Set("Subject", sprintf(GetLang('YourOrderFrom'), $store_name));
	$obj_email->AddBody("html", $message);
	$obj_email->AddRecipient($email, "", "h");
	$email_result = $obj_email->Send();

	$forwardEmails = array();
	if($order_row['ordvendorid'] > 0) {
		$query = "
			SELECT vendororderemail
			FROM [|PREFIX|]vendors
			WHERE vendorid='".(int)$order_row['ordvendorid']."'
		";
		$vendorOrderEmails = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);
		$forwardEmails = array_merge($forwardEmails, explode(',', $vendorOrderEmails));
	}

	// If there are any additional recipients (forward invoices to addresses), send them as well
	if(GetConfig('ForwardInvoiceEmails')) {
		$forwardEmails = array_merge($forwardEmails, explode(',', GetConfig('ForwardInvoiceEmails')));
	}

	$forwardEmails = array_unique($forwardEmails);
	foreach($forwardEmails as $address) {
		if(!trim($address)) {
			continue;
		}
		$emailClass = GetEmailClass();
		$emailClass->Set('CharSet', GetConfig('CharacterSet'));
		$emailClass->From(GetConfig('OrderEmail'), $store_name);
		$emailClass->Set("Subject", "Fwd: ".sprintf(GetLang('YourOrderFrom'), $store_name)." (#".$order_row['orderid'].")");
		$emailClass->AddBody("html", $message);
		$emailClass->AddRecipient($address, "", "h");
		$status = $emailClass->Send();
	}

	// If the email was sent ok, show a confirmation message
	if ($email_result['success']) {
		return true;
	}
	else {
		// Email error
		return false;
	}
}

/**
 * Decrease the inventory levels for items from an order and update the maount of an item sold.
 *
 * @param int The order ID
 * @return boolean True if successful
 */
function DecreaseInventoryFromOrder($orderId)
{
	$order = GetOrder($orderId, false);
	if (!$order) {
		return false;
	}

	// Fetch all of the products in this order
	// (we can't use the function above because we also need to fetch the inventory tracking option for each product)
	$query = sprintf("
		SELECT op.*, p.prodinvtrack, p.prodcurrentinv, p.prodlowinv, v.vcstock, v.vclowstock
		FROM [|PREFIX|]order_products op
		LEFT JOIN [|PREFIX|]products p ON (p.productid=op.ordprodid)
		LEFT JOIN [|PREFIX|]product_variation_combinations v ON (v.combinationid=op.ordprodvariationid)
		WHERE orderorderid='%d' and ordprodtype!=3",
		$orderId
	);
	$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
	while ($product = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
		// Actually adjust the inventory
		AdjustProductInventory($product['ordprodid'], $product['ordprodvariationid'], $product['prodinvtrack'], '-'.$product['ordprodqty']);

		// Is inventory tracking enabled on a per product or per product option basis?
		if ($product['prodinvtrack'] == 1) {
			// This product doesn't use options or one wasn't selected
			$newQty = $product['prodcurrentinv'] - $product['ordprodqty'];
			if ($newQty < 0) {
				$newQty = 0;
			}

			// Have we reached the inventory warning level for this product?
			if ($product['prodlowinv'] > 0 && $newQty <= $product['prodlowinv'] && $product['prodcurrentinv'] > $product['prodlowinv']) {
				SendLowInventoryWarning($product['ordprodid'], 0);
			}
		}
		else if ($product['prodinvtrack'] == 2) {
			// This product uses variations
			$newQty = $product['vcstock'] - $product['ordprodqty'];
			if ($newQty < 0) {
				$newQty = 0;
			}

			// Have we reached the inventory warning level for this product?
			if ($product['vclowstock'] > 0 && $newQty <= $product['vclowstock'] && $product['vcstock'] > $product['vclowstock']) {
				SendLowInventoryWarning($product['ordprodid'], $product['ordprodvariationid']);
			}
		}
	}

	// Update this order to say we've decreased the quantity
	$updatedOrder = array('ordinventoryupdated' => 1);
	$GLOBALS['ISC_CLASS_DB']->UpdateQuery("orders", $updatedOrder, sprintf("orderid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($orderId)));
	return true;
}

/**
 * Send a low inventory warning ot the store owner when a certain product/option reaches the
 * defined low inventory level.
 *
 * @param int The product ID.
 * @param int The variation ID.
 * @param int The current (new) quantity of the item.
 * @return boolean Returns true if successful.
 */
function SendLowInventoryWarning($productId, $variationId)
{
	// Only send the emails if we have this feature enabled
	if(GetConfig('LowInventoryNotificationAddress') == '') {
		return;
	}

	// Fetch the name of this product as well as the product option
	if ($variationId > 0) {
		$query = sprintf("
			SELECT p.prodname, p.prodcurrentinv, p.prodlowinv, v.vclowstock, v.vcstock, v.vcoptionids
			FROM [|PREFIX|]products p
			LEFT JOIN [|PREFIX|]product_variation_combinations v ON (v.combinationid='%d')
			WHERE p.productid='%d'",
			$variationId, $productId
		);
	}
	else {
		$query = sprintf("SELECT prodname, prodcurrentinv, prodlowinv FROM [|PREFIX|]products WHERE productid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($productId));
	}

	$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
	$product = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	if ($variationId > 0) {
		// Fetch out the variation
		$query = "SELECT * FROM [|PREFIX|]product_variation_options WHERE voptionid IN (".$product['vcoptionids'].")";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$optionName = '';
		$comma = '';
		while($option = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$optionName .= $comma.$option['voname']." :".$option['vovalue'];
			$comma = ', ';
		}
		$prodName = $product['prodname'] . " (" . $optionName . ")";
		$stock = $product['vcstock'];
		$lowStockLevel = $product['vclowstock'];
	}
	else {
		$prodName = $product['prodname'];
		$stock = $product['prodcurrentinv'];
		$lowStockLevel = $product['prodlowinv'];
	}

	$GLOBALS['ProductId'] = $productId;

	// Now we build the email
	$GLOBALS['LowInventoryWarningIntro'] = sprintf(GetLang('LowInventoryWarningIntro'), $GLOBALS['StoreName']);
	$GLOBALS['LowInventoryWarning'] = sprintf(GetLang('LowInventoryWarning'), isc_html_escape($product['prodname']));
	$GLOBALS['LowInventoryWarningProduct'] = sprintf(GetLang('LowInventoryWarningProduct'), sprintf('<a href="%s">%s</a>', ProdLink($product['prodname']), isc_html_escape($prodName)));
	$GLOBALS['LowInventoryWarningCurrentStock'] = sprintf(GetLang('LowInventoryWarningCurrentStock'), $stock);
	$GLOBALS['LowInventoryWarningNotice'] = sprintf(GetLang('LowInventoryWarningNotice'), $lowStockLevel);

	$emailTemplate = FetchEmailTemplateParser();
	$emailTemplate->SetTemplate("low_inventory_email");
	$message = $emailTemplate->ParseTemplate(true);

	// Create a new email API object to send the email
	$store_name = GetConfig('StoreName');
	$subject = sprintf(GetLang('LowInventoryWarningSubject'), isc_html_escape($product['prodname']));

	require_once(ISC_BASE_PATH . "/lib/email.php");
	$obj_email = GetEmailClass();
	$obj_email->Set('CharSet', GetConfig('CharacterSet'));
	$obj_email->From(GetConfig('AdminEmail'), $store_name);
	$obj_email->Set('Subject', $subject);
	$obj_email->AddBody("html", $message);
	$obj_email->AddRecipient(GetConfig('LowInventoryNotificationAddress'), "", "h");
	$email_result = $obj_email->Send();

	if ($email_result['success']) {
		return true;
	}
	else {
		return false;
	}
}

/**
 * Adjust the inventory levels of a particular product or variation in the store by a defined
 * amount.
 *
 * @param int The ID of the product.
 * @param int The ID of the variation, if there was one (otherwise 0)
 * @param int The inventory tracking setting for this product (0 = none, 1 = product level, 2 = variation level)
 * @param string The adjustment to make. (For example to subtract from the inventory, -1, to add +1)
 * @return boolean True if successful, false if not.
 */
function AdjustProductInventory($productId, $variationId, $inventoryTracking, $inventoryAdjustment)
{
	$queries = array();

	if(substr($inventoryAdjustment, 0, 1) == '-') {
		$numSoldDirection = '+';
		$inventoryDirection = '-';
	}
	else {
		$numSoldDirection = '-';
		$inventoryDirection = '+';
	}

	if(substr($inventoryAdjustment, 0, 1) == '-' || substr($inventoryAdjustment, 0, 1) == '+') {
		$inventoryAdjustment = substr($inventoryAdjustment, 1);
	}

	if($inventoryAdjustment == 0) {
		return true;
	}


	$GLOBALS["ISC_CLASS_LOG"]->LogSystemDebug("general", "Adjusting number of items sold for product " . $productId . " (variation " . $variationId . ") by " . $numSoldDirection . $inventoryAdjustment);
	// Adjust the number of this item sold
	$queries[] = "
		UPDATE [|PREFIX|]products
		SET prodnumsold=prodnumsold".$numSoldDirection.$inventoryAdjustment."
		WHERE productid='".(int)$productId."'
	";

	// If inventory tracking is enabled, update the inventory
	if(gzte11(ISC_MEDIUMPRINT)) {
		// Inventory tracking is per variation and we have a variation
		if($inventoryTracking == 2 && $variationId > 0) {
			$queries[] = "
				UPDATE [|PREFIX|]product_variation_combinations
				SET vcstock=vcstock".$inventoryDirection.$inventoryAdjustment."
				WHERE combinationid='".(int)$variationId."'
			";
		}

		// Product level (we also update here if on a variation level as it contains the total in stock for all combos)
		if($inventoryTracking > 0) {
			$GLOBALS["ISC_CLASS_LOG"]->LogSystemDebug("general", "Adjusting inventory for product " . $productId . " (variation " . $variationId . ") by " . $inventoryDirection . $inventoryAdjustment);
			$queries[] = "
				UPDATE [|PREFIX|]products
				SET prodcurrentinv=prodcurrentinv".$inventoryDirection.$inventoryAdjustment."
				WHERE productid='".(int)$productId."'
			";
		}
	}

	// Run the queries
	foreach($queries as $query) {
		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			return false;
		}
	}

	return true;
}

/**
 * Increase the inventory for items from an order when an order is returned. Also updates the number sold (decreases it)
 *
 * @param int The order ID
 * @return boolean True if successful
 */
function UpdateInventoryOnReturn($orderId)
{
	$order = GetOrder($orderId, false);
	if (!$order) {
		return false;
	}

	// Fetch all of the products in this order
	// (we can't use the function above because we also need to fetch the inventory tracking option for each product)
	$query = sprintf("
		SELECT op.*, p.prodinvtrack
		FROM [|PREFIX|]order_products op
		LEFT JOIN [|PREFIX|]products p ON (p.productid=op.ordprodid)
		WHERE orderorderid='%d' and ordprodtype!=3",
		$orderId
	);
	$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
	while ($product = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
		AdjustProductInventory($product['ordprodid'], $product['ordprodvariationid'], $product['prodinvtrack'], '+'.$product['ordprodqty']);
	}

	// Update this order to say we've increased the quantity
	$updatedOrder = array('ordinventoryupdated' => 0);
	$GLOBALS['ISC_CLASS_DB']->UpdateQuery("orders", $updatedOrder, sprintf("orderid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($orderId)));
	return true;
}

/**
 * Update the ip address of an order. This is useful for checkout providers like google checkout who provide
 * the customers ip address after the initial notification of a new order
 *
 * @return boolean
 **/
function UpdateOrderIpAddress($orderid, $ipaddress, $dogeoip=true)
{
	$value = trim($ipaddress);
	if (empty($ipaddress)) {
		return false;
	}

	if (!preg_match('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#', $ipaddress)) {
		return false;
	}

	$data = array (
		'ordipaddress' => $ipaddress
	);

	if ($dogeoip) {
		// Attempt to determine the GeoIP location based on their IP address
		require_once ISC_BASE_PATH."/lib/geoip/geoip.php";
		$gi = geoip_open(ISC_BASE_PATH."/lib/geoip/GeoIP.dat", GEOIP_STANDARD);


		$data['ordgeoipcountrycode'] = geoip_country_code_by_addr($gi, $ipaddress);

		// If we get the country, look up the country name as well
		if($data['ordgeoipcountrycode']) {
			$data['ordgeoipcountry'] = geoip_country_name_by_addr($gi, $ipaddress);
		}
	}

	$result = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $data, "orderid='".$GLOBALS['ISC_CLASS_DB']->Quote($orderid)."'");
	if (!$result) {
		return $result;
	}

	// send update to email provider/s that support an IP field
	$query = "
		SELECT ordbillemail
		FROM `[|PREFIX|]orders`
		WHERE orderid = '".$GLOBALS['ISC_CLASS_DB']->Quote($orderid)."'";

	$email = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);

	if (!$email) {
		return false;
	}

	ISC_EMAILINTEGRATION::routeSubscriptionIpUpdate($email, $ipaddress);

	return true;
}

/**
 * Update the status of an order.
 *
 * @param mixed Either an array of order IDs to update, or an integer for a single order ID.
 * @param int The new status of the order.
 * @param boolean Should emails be sent out if the email on status change feature is enabled?
 * @param boolean Set to true if this status update is in a pingback from a payment module and payment modules should not be notified of the change.
 * @return boolean True if successful.
 */
function UpdateOrderStatus($orderIds, $status, $email=true, $preventModuleUpdateCallback=false)
{
	if(!is_array($orderIds)) {
		$orderIds = array($orderIds);
	}

	foreach($orderIds as $orderId) {
		$order = GetOrder($orderId, false);

		if (!$order || !$order['orderid']) {
			return false;
		}

		// Start transaction
		$GLOBALS['ISC_CLASS_DB']->Query("START TRANSACTION");

		$existing_status = $order['ordstatus'];

		// If the order is incomplete, it needs to be completed first
		if($existing_status == 0) {
			CompletePendingOrder($order['ordtoken'], $status, $email);
		}

		$updatedOrder = array(
			"ordstatus" => (int)$status,
			"ordlastmodified" => time(),
		);

		// If the order status is 2 or 10 (completed, shipped) then set the orddateshipped timestamp
		if (OrderIsComplete($status)) {
			$updatedOrder['orddateshipped'] = time();
		}

		//NES: Hago esto porque la orden pudo haber sido cambiada de estatus en CompletePendingOrder() y ahi manda XML tambien, pero este Update y el que esta ahi son independientes
		$nowOrderStatus = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT ordstatus FROM [|PREFIX|]orders WHERE orderid = "'.$orderId.'"', 'ordstatus');
		// Update the status for this order
		if ($GLOBALS['ISC_CLASS_DB']->UpdateQuery("orders", $updatedOrder, "orderid=" . (int)$orderId)) {
			
			if(isset($updatedOrder['ordstatus']) && $nowOrderStatus == ORDER_STATUS_INCOMPLETE && $updatedOrder['ordstatus'] != ORDER_STATUS_INCOMPLETE) {
				$isNewOrder = true;
			}
			else {
				$isNewOrder = false;
			}

			if(!isFromCron('pendingjobs') && $isNewOrder && GetConfig('isIntelisis') && (GetConfig('syncIWSurl') != '' || GetConfig('syncDropboxActive') == 1)){
				$IWS = new ISC_INTELISIS_WS_ORDER((int)$orderId);
				if(!$IWS->prepareRequest()) return false;
			}
			
			// Fetch the name of the status this order was changed to
			$query = sprintf("SELECT statusdesc FROM [|PREFIX|]order_status WHERE statusid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($status));
			$result2 = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$statusName = $GLOBALS['ISC_CLASS_DB']->FetchOne($result2);

			// Log this action if we are in the control panel
			if (defined('ISC_ADMIN_CP')) {
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($orderId, $statusName);
			}

			// This order was marked as refunded or cancelled
			if ($status == ORDER_STATUS_REFUNDED || $status == ORDER_STATUS_CANCELLED) {
				// If the inventory levels for products in this order have previously been changed, we need to
				// return the inventory too
				if ($order['ordinventoryupdated'] == 1) {
					UpdateInventoryOnReturn($orderId);
				}

				// Marked as refunded or cancelled, need to cancel the gift certificates in this order too if there are any
				$updatedCertificates = array(
					"giftcertstatus" => 3
				);
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery("gift_certificates", $updatedCertificates, "giftcertorderid='" . $GLOBALS['ISC_CLASS_DB']->Quote($orderId) . "'");
			}
			// This order was marked as completed/shipped as long as the inventory hasn't been adjusted previously
			else if (OrderIsComplete($status)) {
				if ($order['ordinventoryupdated'] == 0) {
					DecreaseInventoryFromOrder($orderId);
				}

				// Send out gift certificates if the order wasn't already complete
				if (!OrderIsComplete($existing_status)) {
					$GLOBALS['ISC_CLASS_GIFT_CERTIFICATES'] = GetClass('ISC_GIFTCERTIFICATES');
					$GLOBALS['ISC_CLASS_GIFT_CERTIFICATES']->ActivateGiftCertificates($orderId);
				}
			}
		}

		// Was there an error? If not, commit
		if ($GLOBALS['ISC_CLASS_DB']->Error() == "") {
			$GLOBALS['ISC_CLASS_DB']->Query("COMMIT");

			// Does the customer now need to be notified for this status change?
			$statuses = explode(",", GetConfig('OrderStatusNotifications'));
			if (in_array($status, $statuses) && $email == true) {
				foreach($orderIds as $orderId) {
					EmailOnStatusChange($orderId, $status);
				}
			}

			// If the checkout module that was used for an order is still enabled and has a function
			// to handle a status change, then call that function
			if($preventModuleUpdateCallback == false) {
				$valid_checkout_modules = GetAvailableModules('checkout', true, true);
				$valid_checkout_module_ids = array();
				foreach ($valid_checkout_modules as $valid_module) {
					$valid_checkout_module_ids[] = $valid_module['id'];
				}

				foreach($orderIds as $orderId) {
					$order = GetOrder($orderId, false);

					if (in_array($order['orderpaymentmodule'], $valid_checkout_module_ids)) {
						GetModuleById('checkout', $checkout_module, $order['orderpaymentmodule']);
						if (method_exists($checkout_module, 'HandleStatusChange')) {
							call_user_func(array($checkout_module, 'HandleStatusChange'), $orderId, $existing_status, $status, 0);
						}
					}
				}
			}

			return true;
		}
		else {
			return false;
		}
	}

	return false;
}

/**
 *	Send an email notification to a customer when the status of their order changes.
 *
 * @param int The ID of the order to email the invoice for.
 * @return boolean True if successful.
 */
function EmailOnStatusChange($orderId, $status)
{
	// Load the order
	$order = GetOrder($orderId);
	if (!$order) {
		return false;
	}

	// Load the customer we'll be contacting
	if ($order['ordcustid'] > 0) {
		$customer = GetCustomer($order['ordcustid']);
		$GLOBALS['ViewOrderStatusLink'] = '<a href="'.$GLOBALS['ShopPathSSL'].'/orderstatus.php">'.GetLang('ViewOrderStatus').'</a>';
	} else {
		$customer['custconemail'] = $order['ordbillemail'];
		$customer['custconfirstname'] = $order['ordbillfirstname'];
		$GLOBALS['ViewOrderStatusLink'] = '';
	}

	if (empty($customer['custconemail'])) {
		return;
	}

	// All prices in the emailed invoices will be shown in the default currency of the store
	$defaultCurrency = GetDefaultCurrency();

	$statusName = GetOrderStatusById($status);
	$GLOBALS['OrderStatusChangedHi'] = sprintf(GetLang('OrderStatusChangedHi'), isc_html_escape($customer['custconfirstname']));
	$GLOBALS['OrderNumberStatusChangedTo'] = sprintf(GetLang('OrderNumberStatusChangedTo'), $order['orderid'], $statusName);
	$GLOBALS['OrderTotal'] = FormatPrice($order['total_inc_tax'], false, true, false, $defaultCurrency, true);
	$GLOBALS['DatePlaced'] = CDate($order['orddate']);

	if ($order['orderpaymentmethod'] === 'giftcertificate') {
		$GLOBALS['PaymentMethod'] = GetLang('PaymentGiftCertificate');
	}
	else if ($order['orderpaymentmethod'] === 'storecredit') {
		$GLOBALS['PaymentMethod'] = GetLang('PaymentStoreCredit');
	}
	else {
		$GLOBALS['PaymentMethod'] = $order['orderpaymentmethod'];
	}

	$query = "
		SELECT COUNT(*)
		FROM [|PREFIX|]order_products
		WHERE ordprodtype='digital'
		AND orderorderid='".$GLOBALS['ISC_CLASS_DB']->Quote($orderId)."'
	";

	$numDigitalProducts = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);

	$emailTemplate = FetchEmailTemplateParser();

	$GLOBALS['SNIPPETS']['CartItems'] = "";

	if (OrderIsComplete($status) && $numDigitalProducts > 0) {
		$query = "
			SELECT *
			FROM [|PREFIX|]order_products op INNER JOIN [|PREFIX|]products p ON (op.ordprodid = p.productid)
			WHERE ordprodtype='digital'
			AND orderorderid='".$GLOBALS['ISC_CLASS_DB']->Quote($orderId)."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($product_row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$GLOBALS['ProductOptions'] = '';
			$GLOBALS['ProductQuantity'] = $product_row['ordprodqty'];
			$GLOBALS['ProductName'] = isc_html_escape($product_row['ordprodname']);

			$GLOBALS['ISC_CLASS_ACCOUNT'] = GetClass('ISC_ACCOUNT');
			$DownloadItemEncrypted = $GLOBALS['ISC_CLASS_ACCOUNT']->EncryptDownloadKey($product_row['orderprodid'], $product_row['ordprodid'], $orderId, $order['ordtoken']);
			$GLOBALS['DownloadsLink'] = $GLOBALS['ShopPathSSL'].'/account.php?action=download_item&amp;data='.$DownloadItemEncrypted;

			$GLOBALS['SNIPPETS']['CartItems'] .= $emailTemplate->GetSnippet("StatusCompleteDownloadItem");
		}
	}

	$GLOBALS['SNIPPETS']['OrderTrackingLink'] = "";

	$shipments = $GLOBALS['ISC_CLASS_DB']->Query("
		SELECT shipmentid, shipdate, shiptrackno, shipping_module, shipmethod, shipcomments
		FROM [|PREFIX|]shipments
		WHERE shiporderid = " . (int)$orderId . "
		ORDER BY shipdate, shipmentid
	");

	$GLOBALS['TrackingLinkList'] = '';

	while($shipment = $GLOBALS['ISC_CLASS_DB']->Fetch($shipments)) {
		if (!$shipment['shiptrackno']) {
			continue;
		}

		GetModuleById('shipping', /** @var ISC_SHIPPING */$module, $shipment['shipping_module']);

		if ($module) {
			$link = $module->GetTrackingLink($shipment['shiptrackno']);
			if ($link) {
				$link = '<a href="' . isc_html_escape($link) . '" target="_blank">' . $shipment['shiptrackno'] . '</a>';
			} else {
				$link = $shipment['shiptrackno'];
			}
		} else {
			$link = $shipment['shiptrackno'];
		}

		if($shipment['shipmethod']) {
			$link .= ' (' . $shipment['shipmethod'] . ')';
		}

		if ($link) {
			$GLOBALS['TrackingLinkList'] .= '<li>' . $link . '</li>';
		}
	}

	if (empty($GLOBALS['TrackingLinkList'])) {
		$GLOBALS['TrackingLinkList'] = GetLang('NoTrackingNumbersYet');
	} else {
		$GLOBALS['TrackingLinkList'] = '<ul>' . $GLOBALS['TrackingLinkList'] . '</ul>';
	}

	// Set up tracking numbers for orders. Whilst we don't have tracking numbers
	// on orders any longer, this code is being kept for legacy reasons where
	// orders may already have a tracking number saved. To be removed in a future
	// version.
	if (!empty($order['ordtrackingno'])) {
		$GLOBALS['HideTrackingText'] = "";
		$GLOBALS['OrderTrackingNo'] = isc_html_escape($order['ordtrackingno']);

		// Let's instantiate an object for the shipper
		$shipper_object = false;
		if ($order['ordershipmodule'] != "" && GetModuleById('shipping', $shipper_object, $order['ordershipmodule'])) {
			// Does it have a link to track the order?
			if ($shipper_object->GetTrackingLink() != "") {
				// Show the tracking link
				$GLOBALS['TrackURL'] = $shipper_object->GetTrackingLink($order['ordtrackingno']);
				$GLOBALS['SNIPPETS']['OrderTrackingLink'] = $emailTemplate->GetSnippet("OrderTrackingLink");
			}
		}
	}

	if (empty($GLOBALS['SNIPPETS']['CartItems'])) {
		$emailTemplate->SetTemplate("order_status_email");
	} else {
		$emailTemplate->SetTemplate("order_status_downloads_email");
	}
	$message = $emailTemplate->ParseTemplate(true);

	// Create a new email API object to send the email
	$store_name = GetConfig('StoreName');
	$subject = GetLang('OrderStatusChangedSubject');

	require_once(ISC_BASE_PATH . "/lib/email.php");
	$obj_email = GetEmailClass();
	$obj_email->Set('CharSet', GetConfig('CharacterSet'));
	$obj_email->From(GetConfig('OrderEmail'), $store_name);
	$obj_email->Set('Subject', $subject);
	$obj_email->AddBody("html", $message);
	$obj_email->AddRecipient($customer['custconemail'], '', "h");
	$email_result = $obj_email->Send();

	if ($email_result['success']) {
		return true;
	}
	else {
		return false;
	}
}

/**
 * Verifies that a pending order is actually valid and has been paid for.
 * If the pending order is valid, it will return the order status that the
 * order should be set to. Returns false if the order is invalid.
 *
 * @param string The token for the pending order.
 * @return mixed Integer for the order status if the order is valid, false if invalid.
 */
function VerifyPendingOrder($pendingOrderToken)
{
	$status = false;
	$orderData = LoadPendingOrdersByToken($pendingOrderToken);
	if($orderData === false) {
		return false;
	}
	// This order was paid for entirely using a gift certificate, it's automatically valid
	if($orderData['paymentmethod'] == "giftcertificate") {
		$status = ORDER_STATUS_AWAITING_FULFILLMENT;
	}
	// This order was paid for entirely using store credit, it's automatically valid
	else if($orderData['paymentmethod'] == "storecredit") {
		$status = ORDER_STATUS_AWAITING_FULFILLMENT;
	}
	// Don't have to pay for this order because the total is $0.00
	else if($orderData['total'] == 0 && $orderData['paymentmethod'] == '') {
		$status = ORDER_STATUS_AWAITING_FULFILLMENT;
	}
	// Otherwise we went through a payment gateway
	else {
		// Invalid payment module - this is an invalid order
		if(!GetModuleById('checkout', $provider, $orderData['paymentmodule'])) {
			return false;
		}

		// If we have a payment provider that needs to validate the payment
		// do so.
		if($provider->GetPaymentType() != PAYMENT_PROVIDER_OFFLINE) {
			$provider->SetOrderData($orderData);
			// This module doesn't support the new VerifyOrderPayment method (kept for backwards compat.)
			if(method_exists($provider, 'VerifyOrder')) {
				// Grab the first order
				$order = current($orderData['orders']);

				// Order is invalid
				if(!$provider->VerifyOrder($order)) {
					return false;
				}

				if(isset($order['paymentstatus'])) {
					$paymentStatus = $order['paymentstatus'];
				}
			}
			// Otherwise, use the VerifyOrderPayment method to validate the entire order
			else {
				// Order is invalid
				if(!$provider->VerifyOrderPayment()) {
					return false;
				}

				// Get the payment status for this order
				if($provider->GetPaymentStatus() !== false) {
					$paymentStatus = $provider->GetPaymentStatus();
				}
			}

			// Did we have a payment status?
			if(isset($paymentStatus)) {
				$status = GetOrderStatusFromPaymentStatus($paymentStatus);
			}
		}

		// Offline provider, so the payment is valid
		else {
			$status = ORDER_STATUS_AWAITING_PAYMENT;
		}
	}
	return $status;
}

/**
 * Completes a pending order and marks it's status as whatever it should be next.
 * This function will process any payments, capture amounts from gateways, increase
 * # sold for each product in the order, etc.
 *
 * @param string The pending order token.
 * @param int The status to set the completed order to.
 * @return boolean True if successful, false on failure.
 */
function CompletePendingOrder($pendingOrderToken, $status, $sendInvoice=true)
{
	$orderData = LoadPendingOrdersByToken($pendingOrderToken, true);
	if($orderData === false) {
		return false;
	}

	$processedStoreCredit = false;
	$processedGiftCertificates = false;
	$orderStoreCredit = 0;
	$orderTotalAmount = 0;

	// Flag used to create the customer record but only if atleast one order was successful
	$createCustomer = false;

	// Sum up our total amount and store credit
	foreach ($orderData['orders'] as $order) {
		if ($order['ordstatus'] != 0) {
			continue;
		}

		$orderStoreCredit += $order['ordstorecreditamount'];
		$orderTotalAmount += $order['total_inc_tax'];
	}

	// flag to indicate if we should send notifications? only if the order was previously incomplete and the new status isn't declined/cancelled/refunded
	$sendNotifications = false;

	foreach($orderData['orders'] as $order) {
		$newStatus = $status;

		// Wait, was the order already complete? Then we don't do anything
		if($order['ordstatus'] != ORDER_STATUS_INCOMPLETE) {
			continue;
		}

		// If this order is digital, and the status is awaiting fulfillment, there's nothing
		// to actually fulfill, so set it to completed.
		if($order['ordisdigital'] && $newStatus == ORDER_STATUS_AWAITING_FULFILLMENT) {
			$newStatus = ORDER_STATUS_COMPLETED;
		}

		$extraInfo = @unserialize($order['extrainfo']);
		if(!is_array($extraInfo)) {
			$extraInfo = array();
		}

		// only email and update order data (coupons, certificates, store credit etc) if it's not a declined, cancelled or refunded order
		if($newStatus != ORDER_STATUS_DECLINED && $newStatus != ORDER_STATUS_CANCELLED && $newStatus != ORDER_STATUS_REFUNDED) {
			$createCustomer = true;
			$sendNotifications = true;

			if($sendInvoice && !EmailInvoiceToCustomer($order['orderid'], $newStatus)) {
				$GLOBALS['HideError'] = "";
				$GLOBALS['ErrorMessage'] = GetLang('ErroSendingInvoiceEmail');
				$GLOBALS['HideSuccess'] = "none";
			}

			// Are we updating the inventory levels when an order has been placed?
			if(GetConfig('UpdateInventoryLevels') == 1) {
				DecreaseInventoryFromOrder($order['orderid']);
			}

			// If this order now complete, we need to activate any gift certificates
			if(OrderIsComplete($newStatus)) {
				$GLOBALS['ISC_CLASS_GIFTCERTIFICATES'] = GetClass('ISC_GIFTCERTIFICATES');
				$GLOBALS['ISC_CLASS_GIFTCERTIFICATES']->ActivateGiftCertificates($order['orderid']);
			}

			// If we've had one or more coupons been applied to this order, we now need to increment the number of uses
			$couponIds = array();
			$query = "
				SELECT *
				FROM [|PREFIX|]order_coupons
				WHERE ordcouporderid='".(int)$order['orderid']."'
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while($coupon = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$couponIds[] = $coupon['ordcouponid'];
			}
			if(!empty($couponIds)) {
				$couponsUsed = array_unique($couponIds);
				$couponList = implode(",", array_map("intval", $couponsUsed));
				$query = "
					UPDATE [|PREFIX|]coupons
					SET couponnumuses=couponnumuses+1
					WHERE couponid IN (".$couponList.")
				";
				$GLOBALS['ISC_CLASS_DB']->Query($query);

				foreach ($couponIds as $cid) {
					getclass('ISC_COUPON')->updatePerCustomerUsage($cid);
				}
			}

			// If we used store credit on this order, we now need to subtract it from the users account.
			if($order['ordstorecreditamount'] > 0 && $processedStoreCredit == false) {
				$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
				$currentCredit = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerStoreCredit($order['ordcustid']);
				$newCredit = $currentCredit - $orderStoreCredit;
				if($newCredit < 0) {
					$newCredit = 0;
				}
				$updatedCustomer = array(
					'custstorecredit' => $newCredit,
				);
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('customers', $updatedCustomer, "customerid='".(int)$order['ordcustid']."'");
				$processedStoreCredit = true;
			}

			// If one or more gift certificates were used we need to apply them to this order and subtract the total
			if($order['ordgiftcertificateamount'] > 0 && isset($extraInfo['giftcertificates']) && !empty($extraInfo['giftcertificates']) && $processedGiftCertificates == false) {
				$usedCertificates = array();
				$GLOBALS['ISC_CLASS_GIFT_CERTIFICATES'] = GetClass('ISC_GIFTCERTIFICATES');
				$GLOBALS['ISC_CLASS_GIFT_CERTIFICATES']->ApplyGiftCertificatesToOrder($order['orderid'], $orderTotalAmount + $order['ordgiftcertificateamount'], $extraInfo['giftcertificates'], $usedCertificates);
				unset($extraInfo['giftcertificates']);
				$processedGiftCertificates = true;
			}

			// If there are one or more digital products in this order then we need to create a record in the order_downloads table
			// for each of them and set the expiry dates
			$query = "
				SELECT ordprodid, ordprodqty
				FROM [|PREFIX|]order_products
				WHERE orderorderid='".$order['orderid']."' AND ordprodtype='digital'
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$digitalProductIds = array();
			while($digitalProduct = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$digitalProductIds[$digitalProduct['ordprodid']] = $digitalProduct;
			}

			if(!empty($digitalProductIds)) {
				$query = "
					SELECT downloadid, productid, downexpiresafter, downmaxdownloads
					FROM [|PREFIX|]product_downloads
					WHERE productid IN (".implode(',', array_keys($digitalProductIds)).")
				";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				while($digitalDownload = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$expiryDate = 0;

					// If this download has an expiry date, set it to now + expiry time
					if($digitalDownload['downexpiresafter'] > 0) {
						$expiryDate = time() + $digitalDownload['downexpiresafter'];
					}

					// If they've purchased more than one, we need to give them max downloads X quantity downloads
					$quantity = $digitalProductIds[$digitalDownload['productid']]['ordprodqty'];

					$newDownload = array(
						'orderid' => $order['orderid'],
						'downloadid' => $digitalDownload['downloadid'],
						'numdownloads' => 0,
						'downloadexpires' => $expiryDate,
						'maxdownloads' => $digitalDownload['downmaxdownloads'] * $quantity
					);
					$GLOBALS['ISC_CLASS_DB']->InsertQuery('order_downloads', $newDownload);
				}
			}
		}

		// Does a customer account need to be created?
		if(!empty($extraInfo['createAccount'])) {
			createOrderCustomerAccount($order, $extraInfo['createAccount']);
			unset($extraInfo['createAccount']);
		}

		// Now update the order and set the status
		$updatedOrder = array(
			"ordstatus" => $newStatus,
			"extrainfo" => serialize($extraInfo),
		);

		$GLOBALS['ISC_CLASS_DB']->UpdateQuery("orders", $updatedOrder, "orderid='".$order['orderid']."'");
		if(isset($updatedOrder['ordstatus']) && $order['ordstatus'] == ORDER_STATUS_INCOMPLETE && $updatedOrder['ordstatus'] != ORDER_STATUS_INCOMPLETE) {
			$isNewOrder = true;
		}
		else {
			$isNewOrder = false;
		}
		
		if(!isFromCron('pendingjobs') && $isNewOrder && GetConfig('isIntelisis') && (GetConfig('syncIWSurl') != '' || GetConfig('syncDropboxActive') == 1)){
			$IWS = new ISC_INTELISIS_WS_ORDER((int)$order['orderid']);
			if(!$IWS->prepareRequest()) return false;
		}
	}

	if($sendNotifications) {
		// Trigger all active new order notification methods
		SendOrderNotifications($pendingOrderToken);

		// Do we need to add them to a Interspire Email Marketer mailing list?
		SubscribeCustomerToLists($pendingOrderToken);

		// Update the current uses of each rule
		$quote = getCustomerQuote();
		$appliedRules = array_keys(getCustomerQuote()->getAppliedDiscountRules());
		if(!empty($appliedRules)) {
			require_once ISC_BASE_PATH.'/lib/rule.php';
			updateRuleUses($appliedRules);
		}
	}

	// Empty the users cart and kill the checkout process
	EmptyCartAndKillCheckout();
	return true;
}

function createOrderCustomerAccount($order, $accountDetails)
{
	$autoAccount = false;
	if(empty($accountDetails['password'])) {
		$accountDetails['password'] = substr(md5(uniqid(true)), 0, 8);
		$autoAccount = true;
	}

	$savedata = array(
		'custconemail' => $order['ordbillemail'],
		'custpassword' => $accountDetails['password'],
		'custconfirstname' => $order['ordbillfirstname'],
		'custconlastname' => $order['ordbilllastname'],
		'custconcompany' => $order['ordbillcompany'],
		'custconphone' => $order['ordbillphone'],
		'customertoken' => generateCustomerToken(),
	);

	if(!empty($accountDetails['customFormFields'])) {
		$savedata['custformsessionid'] =
			$GLOBALS['ISC_CLASS_FORM']->saveFormSessionManual($accountDetails['customFormFields']);
	}

	$customerId = getClass('ISC_CUSTOMER')
		->CreateCustomerAccount($savedata, true, $autoAccount);
	if(!$customerId) {
		return;
	}

	// OK, we've added in the customer, now for the addresses
	if (isset($accountDetails['addresses']) && is_array($accountDetails['addresses'])) {
		$shippingEntity = new ISC_ENTITY_SHIPPING;
		foreach($accountDetails['addresses'] as $address) {
			$address['shipcustomerid'] = $customerId;

			// If our address is unique then stick it in (the database that is :))
			if (!$shippingEntity->basicSearch($address)) {
				// OK, its unique. We now need to save the custom form data (if we have to) and then the
				// shipping record
				if (isset($address['customFormFields']) && is_array($address['customFormFields'])) {
					$address['shipformsessionid'] = $GLOBALS['ISC_CLASS_FORM']->saveFormSessionManual($address['customFormFields']);

					if (!isId($address['shipformsessionid'])) {
						unset($address['shipformsessionid']);
					}
				}
				$shippingEntity->add($address);
			}
		}
	}

	if(!$autoAccount) {
		getClass('ISC_CUSTOMER')->LoginCustomerById($customerId, true);
	}

	// Lastly, we need to update the orders with this new customer ID
	$savedata = array(
		"ordcustid" => $customerId
	);

	$GLOBALS["ISC_CLASS_DB"]->UpdateQuery("orders", $savedata, "orderid='".$order['orderid']."'");
}

/**
 * Load all of the pending orders with the specified token.
 *
 * @param string The token of the pending orders to load.
 * @param boolean Set to true to force a reload if the data is already cached.
 * @return array An array of information about the pending orders.
 */
function LoadPendingOrdersByToken($token='', $hardRefresh=false)
{
	static $pendingCache = array();
	if($token == '' && isset($_COOKIE['SHOP_ORDER_TOKEN'])) {
		$token = $_COOKIE['SHOP_ORDER_TOKEN'];
	}

	if(isset($pendingCache[$token]) && $hardRefresh == false) {
		return $pendingCache[$token];
	}

	$pendingArray = array(
		'orders'				=> array(),
		'total'					=> 0,
		'gatewayamount'			=> 0,
		'storecreditamount'		=> 0,
		'giftcertificateamount'	=> 0,
		'status'				=> 0,
		'currencyid'			=> 0,
		'customerid'			=> 0,
		'isdigital'				=> 0,
		'paymentmodule'			=> '',
		'paymentmethod'			=> '',
		'ipaddress'				=> '',
	);

	$query = "
		SELECT *
		FROM [|PREFIX|]orders
		WHERE ordtoken='".$GLOBALS['ISC_CLASS_DB']->Quote($token)."' AND deleted = 0
	";
	$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
	while($order = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
		$pendingArray['orders'][$order['orderid']] = $order;
		$pendingArray['total'] += $order['total_inc_tax'];
		if(!$pendingArray['gatewayamount']) {
			$pendingArray['gatewayamount'] = $order['total_inc_tax'];
		}

		if(!$pendingArray['storecreditamount']) {
			$pendingArray['storecreditamount'] = $order['ordstorecreditamount'];
		}

		if(!$pendingArray['giftcertificateamount']) {
			$pendingArray['giftcertificateamount'] = $order['ordgiftcertificateamount'];
		}

		if($order['orderpaymentmodule'] != 'giftcertificate' && $order['orderpaymentmodule'] != 'storecredit' && $order['orderpaymentmodule'] != '') {
			$pendingArray['paymentmodule'] = $order['orderpaymentmodule'];
		}
		$pendingArray['paymentmethod'] = $order['orderpaymentmethod'];
		$pendingArray['status'] = $order['ordstatus'];
		$pendingArray['ipaddress'] = $order['ordipaddress'];
		$pendingArray['currencyid'] = $order['ordcurrencyid'];
		$pendingArray['customerid'] = $order['ordcustid'];
		$pendingArray['isdigital'] = $order['ordisdigital'];
		$pendingArray['extrainfo'] = @unserialize($order['extraInfo']);
	}

	if(empty($pendingArray['orders'])) {
		return false;
	}

	// Cache the result & then return it
	$pendingCache[$token] = $pendingArray;
	return $pendingArray;
}

/**
 *	Checks the token against the pendingtoken field in the pending_orders table to see if it's a valid pending order.
 *
 * @param string The token to look for.
 * @return boolean True if the order is valid.
 */
function IsValidPendingOrderToken($Token)
{
	$query = sprintf("select count(ordtoken) as num from [|PREFIX|]orders where ordtoken='%s' AND ordstatus=0 AND deleted=0", $GLOBALS['ISC_CLASS_DB']->Quote($Token));
	$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
	$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

	if ($row['num'] > 0) {
		return true;
	} else {
		return false;
	}
}

/**
 * Create an actual order.
 *
 * @param array An array of information about the order.
 * @param array An array of items in the order.
 * @return string The token of the pending order.
 */
function CreateOrder($orderData, $orderProducts)
{
	$entity = new ISC_ENTITY_ORDER();

	// Delete the old configurable product files uploaded by the customers.
	DeleteOldConfigProductFiles();

	$pendingToken = GenerateOrderToken();
	$orderData['ordtoken'] = $pendingToken;
	$vendorInfo = $orderData['vendorinfo'];
	unset($orderData['vendorinfo']);
	foreach($vendorInfo as $vendorId => $vendorData) {
		$products = array();
		foreach($vendorData['products'] as $productId => $quantity) {
			$productInfo = $orderProducts[$productId];
			$productInfo['quantity'] = $quantity;
			$products[] = $productInfo;
		}
		list($vendorId,) = explode('_', $vendorId, 2);
		$vendorOrder = array_merge($orderData, $vendorData);
		$vendorOrder['products'] = $products;
		$vendorOrder['vendorid'] = $vendorId;
		// If we failed to add the order, stop
		if(!$entity->add($vendorOrder)) {
			return false;
		}
	}
	return $pendingToken;
}

/**
 * Save an address from an order in to the shipping addresses table.
 *
 * @param int The customer ID to assign this address to
 * @param array An array of details about the address.
 */
function SaveOrderShippingAddress($customerId, $address)
{
	// First, does an address already exist under this street address 1 & full name? If so, don't add it
	$query = "
		SELECT shipid
		FROM [|PREFIX|]shipping_addresses
		WHERE
			shipcustomerid='".(int)$customerId."' AND
			shipaddress1='".$GLOBALS['ISC_CLASS_DB']->Quote($address['shipaddress1'])."' AND
			(CONCAT(shipfirstname, ' ', shiplastname)='".$GLOBALS['ISC_CLASS_DB']->Quote(trim($address['shipfirstname'].' '.$address['shiplastname']))."'
	";
	$existingAddress = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);
	if($existingAddress) {
		return;
	}

	$address['shiplastused'] = time();
	unset($address['saveAddress']);
	unset($address['shipemail']);
	$address['shipcustomerid'] = (int)$customerId;

	$GLOBALS['ISC_CLASS_DB']->InsertQuery('shipping_addresses', $address);
}

/**
 * Generate a unique order token.
 *
 * @return string THe unique order token (32 characters)
 */
function GenerateOrderToken()
{
	return md5(uniqid());
}

/**
 * Get a list of order statuses that orders that have been paid for may be set to. This is primarily used for store statistics.
 *
 * @return array An array of paid order statuses.
 */
function GetPaidOrderStatusArray()
{
	return array(
		ORDER_STATUS_SHIPPED,
		ORDER_STATUS_PARTIALLY_SHIPPED,
		ORDER_STATUS_AWAITING_PICKUP,
		ORDER_STATUS_AWAITING_SHIPMENT,
		ORDER_STATUS_COMPLETED,
		ORDER_STATUS_AWAITING_FULFILLMENT,
	);
}

/**
 * We need to sort the gift certificates from least balance to most balance remaining and apply them in that order
 *
 * @param array The first gift certificate
 * @param array The second gift certificate
 *
 * @return integer
 **/
function GiftCertificateSort($a, $b)
{
	if ($a['giftcertbalance'] == $b['giftcertbalance']) {
		return 0;
	}
	if ($a['giftcertbalance'] < $b['giftcertbalance']) {
		return -1;
	}
	else {
		return 1;
	}
}

/**
 * Load a pending order from the pending orders table.
 *
 * @param string The token of the pending order to load.
 * @return array Array containing the pending order.
 * @deprecated 4.0
 * @see LoadPendingOrdersByToken()
 */
function LoadPendingOrderByToken($Token="")
{
//	echo "WARNING: LoadPendingOrderByToken called.";
//	echo trace();

	$orderData = LoadPendingOrdersByToken($Token);
	if($orderData === false) {
		return false;
	}
	$order = current($orderData['orders']);
	return $order;
}

/**
* Gets the equivalent order status from a given payment status
*
* @param int The payment status (eg. PAYMENT_STATUS_PAID)
* @return int The order status (eg. ORDER_STATUS_AWAITING_FULFILLMENT)
*/
function GetOrderStatusFromPaymentStatus($paymentStatus)
{
	$status = ORDER_STATUS_INCOMPLETE;

	switch($paymentStatus) {
		case PAYMENT_STATUS_PAID:
			$status = ORDER_STATUS_AWAITING_FULFILLMENT;
			break;
		case PAYMENT_STATUS_PENDING:
			$status = ORDER_STATUS_AWAITING_PAYMENT;
			break;
		case PAYMENT_STATUS_DECLINED:
			$status = ORDER_STATUS_DECLINED;
			break;
	}

	return $status;
}

/**
*	Check which order notification methods are enabled and trigger each one
*/
function SendOrderNotifications($pendingOrderToken)
{
	$orders = LoadPendingOrdersByToken($pendingOrderToken);

	// Firstly, are there any order notification methods that are enabled?
	$notifications = GetEnabledNotificationModules();

	if(!is_array($notifications) || empty($notifications)) {
		return false;
	}

	foreach($notifications as $notifier) {
		// Instantiate the notification object by reference
		if(!GetModuleById('notification', $notify_object, $notifier['object']->GetId())) {
			continue;
		}
		// Set the required variables
		foreach($orders['orders'] as $order) {
			$notify_object->SetOrderId($order['orderid']);
			$notify_object->SetOrderTotal($order['total_inc_tax']);
			$notify_object->SetOrderNumItems($order['ordtotalqty']);
			$notify_object->SetOrderPaymentMethod($order['orderpaymentmethod']);

			$response = $notify_object->SendNotification();
			if(isset($response['outcome']) && $response['outcome'] == "fail") {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('notification', $notify_object->_name), GetLang('NotificationOrderError'), $response['message']);
			}
			else if(isset($response['outcome']) && $response['outcome'] == "success") {
				$GLOBALS['ISC_CLASS_LOG']->LogSystemSuccess(array('notification', $notify_object->_name), GetLang('NotificationOrderSuccess'), $response['message']);
			}
		}
	}
}

/**
*	Do we need to subscribe the customer to either of our mailing lists?
*	If they ticked yes then the appropriate cookies were set before they
*	chose their shipping provider and entered their payment details
*/
function SubscribeCustomerToLists($pendingOrderToken)
{
	$orders = LoadPendingOrdersByToken($pendingOrderToken);
	$order = current($orders['orders']);
	$email = $order['ordbillemail'];
	$firstName = $order['ordbillfirstname'];

	foreach($orders['orders'] as $order) {
		$extraInfo =array();
		if(isset($order['extrainfo']) && $order['extrainfo'] != '') {
			$extraInfo = @unserialize($order['extrainfo']);
		}

		$format = Interspire_EmailIntegration_Subscription::FORMAT_PREF_NONE;
		if (isset($extraInfo['mail_format_preference'])) {
			$format = (int)$extraInfo['mail_format_preference'];
		}

		// Should we add them to our newsletter mailing list?
		if(isset($extraInfo['join_mailing_list']) && $extraInfo['join_mailing_list'] == 1) {
			$subscription = new Interspire_EmailIntegration_Subscription_Newsletter($email, $firstName);
			$subscription->setDoubleOptIn(GetConfig('EmailIntegrationOrderDoubleOptin')); // override newsletter double-opt-in preference with order double-opt-in preference when subscribing someone to newsletter list through the checkout
			$subscription->setSendWelcome(GetConfig('EmailIntegrationOrderSendWelcome')); // as above
			$subscription->setEmailFormatPreference($format);
			$subscription->routeSubscription();
		}

		// Should we add them to our special offers & discounts mailing list?
		if(isset($extraInfo['join_order_list']) && $extraInfo['join_order_list']) {
			$subscription = new Interspire_EmailIntegration_Subscription_Order($order['orderid']);
			$subscription->setEmailFormatPreference($format);
			$subscription->routeSubscription();
		}
	}
}

function EmptyCartAndKillCheckout()
{
	// Unset the cart the user previously had
	unset($_SESSION['QUOTE']);

	// Unset our checkout session
	unset($_SESSION['CHECKOUT']);
}

function UpdateOrderTableAutoIncrement($newAutoIncrement=100)
{
	$alterOrderTableQuery = "ALTER TABLE `[|PREFIX|]orders` AUTO_INCREMENT=" . $newAutoIncrement;
	return (bool)$GLOBALS['ISC_CLASS_DB']->Query($alterOrderTableQuery);
}

function GetOrderTableAutoIncrement()
{
	$query = "SHOW CREATE TABLE `[|PREFIX|]orders`";
	$createTable = $GLOBALS['ISC_CLASS_DB']->Fetch($GLOBALS['ISC_CLASS_DB']->Query($query));

	preg_match('/AUTO_INCREMENT=([0-9]+)/', $createTable['Create Table'], $match);

	if(!isset($match[1])) {
		return 1;
	}

	return $match[1];
}

function GetHighestOrderNumber()
{
	// do not filter by orders.deleted here
	$countOrdersQuery = "SELECT orderid FROM `[|PREFIX|]orders` ORDER BY orderid desc LIMIT 1";
	return (int)$GLOBALS['ISC_CLASS_DB']->FetchOne($GLOBALS['ISC_CLASS_DB']->Query($countOrdersQuery));
}

function ResetStartingOrderNumber()
{
	$StartingOrderNumber = GetOrderTableAutoIncrement();
	$StartingOrderNumber_config = GetConfig('StartingOrderNumber');
	$HighestOrderNumber = GetHighestOrderNumber();

	$return = $StartingOrderNumber;

	if($StartingOrderNumber <= $StartingOrderNumber_config) {
		// mysql was probably reset (InnoDB tables don't save auto_increment to disk)
		// or the auto increment value is lower than our saved config value
		$return = $StartingOrderNumber_config;

		// set the database to be inline with our config
		UpdateOrderTableAutoIncrement($StartingOrderNumber_config);

	} else if ($StartingOrderNumber <= 1 && $StartingOrderNumber_config <= 1 && $HighestOrderNumber >= 1) {
		// if mysql was reset and we've never had a config start number set but have orders, we want to use the highest order number + 1 as our auto increment
		$HighestOrderNumber++;
		$return = $HighestOrderNumber;
		UpdateOrderTableAutoIncrement($HighestOrderNumber);
	}
	return $return;
}

function getOrderTotalRows($order)
{
	$taxColumnAppend = '_ex_tax';
	if(getConfig('taxDefaultTaxDisplayOrders') == TAX_PRICES_DISPLAY_INCLUSIVE) {
		$taxColumnAppend = '_inc_tax';
	}

	$totalRows = array();

	// Subtotal
	$totalRows['subtotal'] = array(
		'label' => getLang('Subtotal'),
		'value' => $order['subtotal'.$taxColumnAppend]
	);

	// Gift Wrapping
	if($order['wrapping_cost'.$taxColumnAppend] > 0) {
		$totalRows['giftWrapping'] = array(
			'label' => getLang('GiftWrapping'),
			'value' => $order['wrapping_cost'.$taxColumnAppend]
		);
	}

	// Discount Amount
	if($order['orddiscountamount'] > 0) {
		$totalRows['discount'] = array(
			'label' => getLang('Discount'),
			'value' => $order['orddiscountamount'] * -1,
		);
	}

	// Coupon codes
	$query = "
		SELECT *
		FROM [|PREFIX|]order_coupons
		WHERE ordcouporderid='".$order['orderid']."'
	";
	$result = $GLOBALS['ISC_CLASS_DB']->query($query);
	$freeShippingCoupons = array();
	while($coupon = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
		if($coupon['applied_discount'] == 0) {
			continue;
		}
		$couponRow = array(
			'label' => getLang('CouponCode').' ('.$coupon['ordcouponcode'].')',
			'value' => $coupon['applied_discount'],
			/* NES - Quito esto para que no se lo descuente erroneamente despues al total del envio. Despues lo hago negativo para que se muestre como deducción en el totalizador */
			/*'value' => $coupon['applied_discount'] * -1,*/
			'couponid' => $coupon['ordcoupid'],
		);

		if (getclass('ISC_COUPON')->isFreeShippingCoupon($coupon['ordcoupontype'])) {
			$freeShippingCoupons['coupon-'.$coupon['ordcoupid']] = $couponRow;
			continue;
		}
		else {
			// NES - Tambien agrego esto porque quitamos el "* - 1" arriba, para que se lo aplique a los cupones que no son de shipping
			$couponRow['value'] = $couponRow['value']  * -1; 
		}
		$totalRows['coupon-'.$coupon['ordcoupid']] = $couponRow;
	};

	// Shipping & handling
	if($order['shipping_address_count'] > 1) {
		$query = "
			SELECT *
			FROM [|PREFIX|]order_shipping
			WHERE order_id='".$order['orderid']."'
			ORDER BY order_address_id
		";
		$result = $GLOBALS['ISC_CLASS_DB']->query($query);
		$destinationCounter = 0;
		while($shipping = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
			$destinationCounter++;
			$totalRows['shipping-'.$shipping['id']] = array(
				'label' => getLang('Shipping_Destination_Number', array('number' => $destinationCounter)).' ('.$shipping['method'].')',
				'value' => $shipping['cost'.$taxColumnAppend]
			);
		}
	}
	else if(!$order['ordisdigital']) {
		$totalRows['shipping'] = array(
			'label' => getLang('Shipping'),
			'value' => $order['shipping_cost'.$taxColumnAppend]
		);
		if (!empty ($freeShippingCoupons)) {
			//$totalRows['shipping']['value'] = 0;
			foreach($freeShippingCoupons as $key=>$val) {
				$totalRows[$key] = $val;
				$totalRows['shipping']['value'] += (int)$val['value'];
				/* NES - Aumento esto porque lo quite cuando calcula $freeShippingCoupons */
				$totalRows['coupon-'.$val['couponid']]['value'] = (int)$val['value'] * -1;
			}
			if ($totalRows['shipping']['value'] < 0) {
				$totalRows['shipping']['value'] *= -1;
			}
		}
	}

	if($order['handling_cost'.$taxColumnAppend] > 0) {
		$totalRows['handling'] = array(
			'label' => getLang('Handling'),
			'value' => $order['handling_cost'.$taxColumnAppend]
		);
	}

	// Taxes
	$taxes = array();
	$includedTaxes = array();
	if($order['total_tax']) {
		// Show a single summary of applied tax
		if(getConfig('taxChargesOnOrdersBreakdown') == TAX_BREAKDOWN_SUMMARY) {
			$taxes[] = array(
				'name'	=> getConfig('taxLabel'),
				'total'	=> $order['total_tax']
			);
		}
		else {
			// Whilst the loop here seems like overhead for now, when we
			// switch to the new template system on the front end, we'll
			// just assign $taxes to the template to allow further
			// customization.
			$query = "
				SELECT name, tax_rate_id, SUM(priority_amount) AS priority_amount, priority
				FROM [|PREFIX|]order_taxes
				WHERE order_id='".$order['orderid']."'
				GROUP BY priority, tax_rate_id
				ORDER BY priority
			";
			$result = $GLOBALS['ISC_CLASS_DB']->query($query);
			while($tax = $GLOBALS['ISC_CLASS_DB']->fetch($result)) {
				if(!isset($taxes[$tax['priority']])) {
					$taxes[$tax['priority']] = array(
						'name' => $tax['name'],
						'total' => $tax['priority_amount'],
					);
					continue;
				}
				$taxes[$tax['priority']]['name'] .= ' + ' . $tax['name'];
			}
		}

		if(getConfig('taxDefaultTaxDisplayOrders') == TAX_PRICES_DISPLAY_INCLUSIVE) {
			$includedTaxes = $taxes;
			$taxes = array();
		}
	}

	foreach($taxes as $id => $taxRate) {
		if($taxRate['total'] == 0) {
			continue;
		}
		$totalRows['tax-'.$id] = array(
			'label' => $taxRate['name'],
			'value' => $taxRate['total'],
		);
	}

	// Gift Certificates
	if($order['ordgiftcertificateamount'] > 0) {
		$totalRows['giftCertificates'] = array(
			'label' => getLang('GiftCertificates'),
			'value' => $order['ordgiftcertificateamount'] * -1,
		);
	}

	// Store Credit
	if($order['ordstorecreditamount'] > 0) {
		$totalRows['storeCredit'] = array(
			'label' => getLang('StoreCredit'),
			'value' => $order['ordstorecreditamount'] * -1,
		);
	}

	$totalRows['total'] = array(
		'label' => getLang('GrandTotal'),
		'value' => $order['total_inc_tax'],
	);

	// Included taxes
	foreach($includedTaxes as $id => $taxRate) {
		if($taxRate['total'] == 0) {
			continue;
		}
		$totalRows['tax-'.$id] = array(
			'label' => $taxRate['name'] . ' ' . getLang('IncludedInTotal'),
			'value' => $taxRate['total'],
		);
	}

	return $totalRows;
}

/**
* Returns a sorted list of order status and their localized names.
*
* @return array as status id => name or false on failure
*/
function getOrderStatusList()
{
	$statuses = array();

	$result = $GLOBALS['ISC_CLASS_DB']->Query("
		SELECT *
		FROM [|PREFIX|]order_status
		ORDER BY statusorder ASC
	");
	if (!$result) {
		return false;
	}

	while ($status = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
		$statuses[$status['statusid']] = $status['statusdesc'];
	}

	return $statuses;
}

/**
 * Get Store order based on ebay order id
 *
 * @param string $ebayOrderId The id of ebay order
 * @return array If the order exist, return an array of the order. Otherwise, return empty array
 */
function GetOrderByEbayOrderId($ebayOrderId)
{
	$query = sprintf("SELECT * FROM [|PREFIX|]orders WHERE ebay_order_id='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($ebayOrderId));
	$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
	$order = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	if ($order) {
		return $order;
	}
	return array();
}
