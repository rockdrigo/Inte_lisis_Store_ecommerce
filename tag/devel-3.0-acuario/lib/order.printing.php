<?php
/**
 * Generate a printable invoice page for one or more orders.
 * @param array List of order ids.
 * @param bool (optional) Include auto print script.
 * @param bool (optional) Include auto close script when empty.
 * @return string The generated printable invoice page (HTML)
 */
function generatePrintableInvoicePage($orderIds, $autoPrint=true, $autoCloseIfEmpty=true)
{
	$template = TEMPLATE::GetInstance();
	$template->assign('HeaderLogo', fetchHeaderLogo());

	// Set page title
	$template->setPageTitle(generatePrintableInvoicePageTitle($orderIds));

	// Prepare the invoice list
	$invoiceList = generatePrintableInvoiceList($orderIds);

	// Prepare the JS
	$invoiceJS = "";

	if(!$invoiceList && $autoCloseIfEmpty) {
		$invoiceJS .= '<script type="text/javascript">window.close();</script>';
	}
	else if($autoPrint) {
		$invoiceJS .= '<script type="text/javascript">window.setTimeout("window.print();", 1000);</script>';
	}

	// Assign template variables
	$template->assign('PrintableInvoiceList', $invoiceList);
	$template->assign('PrintableInvoiceScript', $invoiceJS);

	$template->setTemplate('invoice_print');
	return $template->parseTemplate(true);
}

function generatePrintableInvoicePageTitle($orderIds)
{
	$title = GetConfig('StoreName').' - ';

	// allow access to deleted orders if printing from within control panel
	$isAdmin = (defined('ISC_ADMIN_CP') && ISC_ADMIN_CP);

	if(count($orderIds) == 1 && ($order = GetOrder($orderIds[0], null, null, $isAdmin))) {
		return $title .= sprintf(GetLang('PrintInvoiceForOrderNumber'), $orderIds[0]);
	}

	return $title .= GetLang('PrintInvoices');
}

/**
 * Generate a list of printable invoices.
 * @param array List of order ids.
 * @return string The generated printable invoice list (HTML)
 */
function generatePrintableInvoiceList($orderIds)
{
	$numOrders = count($orderIds);
	$invoiceList = "";
	$pageBreak = "<p class='PageBreak'>&nbsp;</p>";

	for($i = 0; $i < $numOrders; $i++) {
		$invoice = generatePrintableInvoice($orderIds[$i]);

		if(!$invoice)
			continue;

		if($i > 0)
			$invoiceList .= $pageBreak;

		$invoiceList .= $invoice;
	}

	return $invoiceList;
}

/**
 * Generate a single printable invoice.
 * @param int
 * @return strong The generated printable invoice (HTML)
 */
function generatePrintableInvoice($orderId)
{
	$db = $GLOBALS['ISC_CLASS_DB'];

	$template = TEMPLATE::GetInstance();
	$template->assign('StoreAddressFormatted', nl2br(getConfig('StoreAddress')));

	// allow access to deleted orders if printing from within control panel
	$isAdmin = (defined('ISC_ADMIN_CP') && ISC_ADMIN_CP);

	$query = "
		SELECT o.*, CONCAT(c.custconfirstname, ' ', c.custconlastname) AS ordcustname, c.custconemail AS ordcustemail, c.custconphone AS ordcustphone
		FROM [|PREFIX|]orders o
		LEFT JOIN [|PREFIX|]customers c ON o.ordcustid = c.customerid
		WHERE o.orderid = '".(int)$orderId."'
	";

	if (!$isAdmin) {
		$query .= " AND o.deleted = 0 ";
	}

	$result = $db->Query($query);
	$row = $db->Fetch($result);
	$order = $row;

	if(!$row) {
		return false;
	}

	$template->assign('OrderId', $row['orderid']);
	$template->assign('OrderDate', cDate($row['orddate']));

	if($row['ordcustmessage']) {
		$template->assign('Comments', nl2br(isc_html_escape($row['ordcustmessage'])));
		$template->assign('HideComments', '');
	}
	else {
		$template->assign('Comments', '');
		$template->assign('HideComments', 'display: none');
	}

	$template->assign('InvoiceTitle', sprintf(getLang('InvoiceTitle'), $orderId));

	$showShipping = true;
	$template->assign('totalRowColspan', 4);
	$template->assign('hideAddressColumn', 'display: none');

	if($row['shipping_address_count'] > 1) {
		$showShipping = false;
		$template->assign('totalRowColspan', 5);
		$template->assign('hideAddressColumn', '');
		$template->assign('hideInvoiceShippingDetails', 'display: none');
	}
	else if($row['ordisdigital']) {
		$template->assign('hideInvoiceShippingDetails', 'display: none');
		$showShipping = false;
	}

	$totalRows = getOrderTotalRows($row, $showShipping);
	$templateTotalRows = '';
	foreach($totalRows as $id => $totalRow) {
		$template->assign('label', $totalRow['label']);
		$template->assign('classNameAppend', ucfirst($id));
		$value = currencyConvertFormatPrice(
			$totalRow['value'],
			$row['ordcurrencyid'],
			$row['ordcurrencyexchangerate'],
			true
		);
		$template->assign('value', $value);
		$templateTotalRows .= $template->getSnippet('PrintableInvoiceTotalRow');
	}

	$template->assign('totals', $templateTotalRows);

	// Fetch shipping addresses in this order
	$addresses = array();
	$query = "
		SELECT *
		FROM [|PREFIX|]order_addresses
		WHERE order_id='".(int)$orderId."'
		ORDER BY `id`
	";
	$result = $db->query($query);
	while($address = $db->fetch($result)) {
		$addresses[$address['id']] = $address;
	}

	// Fetch shippng details
	$query = "
		SELECT *
		FROM [|PREFIX|]order_shipping
		WHERE order_id='".(int)$orderId."'
		ORDER BY order_address_id
	";
	$result = $db->query($query);
	while($shipping = $db->fetch($result)) {
		$addresses[$shipping['order_address_id']]['shipping'] = $shipping;
	}

	// Order has a single shipping address
	if($row['shipping_address_count'] == 1) {
		$address = current($addresses);
		$template->assign('ShippingAddress', getInvoiceShippingAddressBlock($address));
		$template->assign('ShippingEmail', isc_html_escape($address['email']));
		if(!$address['email']) {
			$template->assign('HideShippingEmail', 'display: none');
		}
		$template->assign('ShippingMethod', isc_html_escape($address['shipping']['method']));
	}

	// Format the customer details
	if($row['ordcustid'] == 0) {
		$template->assign('HideCustomerDetails', 'display: none');
	}
	$template->assign('CustomerId', $row['ordcustid']);
	$template->assign('CustomerName', isc_html_escape($row['ordcustname']));
	$template->assign('CustomerEmail', $row['ordcustemail']);
	$template->assign('CustomerPhone', $row['ordcustphone']);

	// Format the billing address
	$template->assign('ShipFullName', isc_html_escape($row['ordbillfirstname'].' '.$row['ordbilllastname']));

	if($row['ordbillcompany']) {
		$template->assign('ShipCompany', '<br />'.isc_html_escape($row['ordbillcompany']));
	}
	else {
		$template->assign('ShipCompany', '');
	}

	$addressLine = isc_html_escape($row['ordbillstreet1']);
	if ($row['ordbillstreet2'] != "") {
		$addressLine .=  '<br />' . isc_html_escape($row['ordbillstreet2']);
	}
	$template->assign('ShipAddressLines', $addressLine);

	$template->assign('ShipSuburb', isc_html_escape($row['ordbillsuburb']));
	$template->assign('ShipState', isc_html_escape($row['ordbillstate']));
	$template->assign('ShipZip', isc_html_escape($row['ordbillzip']));
	$template->assign('ShipCountry', isc_html_escape($row['ordbillcountry']));
	$template->assign('BillingAddress', $template->getSnippet('AddressLabel'));
	$template->assign('BillingPhone', isc_html_escape($row['ordbillphone']));
	if(!$row['ordbillphone']) {
		$template->assign('HideBillingPhone', 'display: none');
	}
	$template->assign('BillingEmail', isc_html_escape($row['ordbillemail']));
	if(!$row['ordbillemail']) {
		$template->assign('HideBillingEmail', 'display: none');
	}

	// Set the payment method
	$paymentMethod = $row['orderpaymentmethod'];
	if($row['orderpaymentmethod'] == '') {
		$paymentMethod = getLang('NA');
	}

	if($row['orderpaymentmethod'] != 'storecredit' && $row['orderpaymentmethod'] != 'giftcertificate') {
		$paymentMethod .= " (". formatPriceInCurrency($row['total_inc_tax'], $row['orddefaultcurrencyid']).")";
	}

	$template->assign('PaymentMethod', $paymentMethod);

	// Get the products in the order
	$fieldsArray = array();
	$query = "
		SELECT o.*
		FROM [|PREFIX|]order_configurable_fields o
		JOIN [|PREFIX|]product_configurable_fields p ON o.fieldid = p.productfieldid
		WHERE o.orderid=".(int)$orderId."
		ORDER BY p.fieldsortorder ASC
	";
	$result = $db->Query($query);
	$fields = array();
	while ($row = $db->Fetch($result)) {
		$fieldsArray[$row['ordprodid']][] = $row;
	}

	$query = "
		SELECT
			op.*,
			p.productid,
			p.prodpreorder,
			p.prodreleasedate,
			p.prodpreordermessage
		FROM
			[|PREFIX|]order_products op
			LEFT JOIN [|PREFIX|]products p ON p.productid = op.ordprodid
		WHERE
			op.orderorderid='".(int)$orderId."'
		ORDER BY op.order_address_id
	";
	$result = $db->query($query);

	$productsTable = '';
	$lastAddressId = -1;

	$shippingCostColumn = 'cost_ex_tax';
	$itemPriceColumn = 'price_ex_tax';
	$itemTotalColumn = 'total_ex_tax';

	if(getConfig('taxDefaultTaxDisplayOrders') == TAX_PRICES_DISPLAY_INCLUSIVE) {
		$shippingCostColumn = 'cost_inc_tax';
		$itemPriceColumn = 'price_inc_tax';
		$itemTotalColumn = 'total_inc_tax';
	}

	$addressProducts = array();
	while($product = $db->fetch($result)) {
		if(!isset($addressProducts[$product['order_address_id']])) {
			$addressProducts[$product['order_address_id']] = array();
		}

		$addressProducts[$product['order_address_id']][] = $product;
	}

	foreach($addressProducts as $addressId => $products) {
		$numProducts = count($products);
		if(!isset($addresses[$addressId])) {
			$template->assign('productShippingAddress', getLang('NA'));
		}
		else {
			$address = $addresses[$addressId];
			$template->assign('productShippingAddress', getInvoiceShippingAddressBlock($address));
		}
		$template->assign('addressColumnRowSpan', $numProducts);
		if($order['shipping_address_count'] > 1) {
			$template->assign('hideAddressColumn', '');
			$template->assign('invoiceItemClass', 'InvoiceItemDivider');
		}

		foreach($products as $product) {
			$template->assign('ProductName', isc_html_escape($product['ordprodname']));
			if($product['ordprodsku']) {
				$template->assign('ProductSku', isc_html_escape($product['ordprodsku']));
			}
			else {
				$template->assign('ProductSku', getLang('NA'));
			}
			$template->assign('ProductQuantity', $product['ordprodqty']);

			$pOptions = '';
			if($product['ordprodoptions'] != '') {
				$options = @unserialize($product['ordprodoptions']);
				if(!empty($options)) {
					foreach($options as $name => $value) {
						$template->assign('FieldName', isc_html_escape($name));
						$template->assign('FieldValue', isc_html_escape($value));
						$pOptions .= $template->GetSnippet('PrintableInvoiceItemConfigurableField');
					}
				}
			}

			if($pOptions) {
				$template->assign('ProductOptions', $pOptions);
				$template->assign('HideVariationOptions', '');
			}
			else {
				$template->assign('HideVariationOptions', 'display: none');
			}

			$productFields = '';
			if(!empty($fieldsArray[$product['orderprodid']])) {
				$fields = $fieldsArray[$product['orderprodid']];
				foreach($fields as $field) {
					if(empty($field['textcontents']) && empty($field['filename'])) {
						continue;
					}

					$fieldValue = '-';
					$template->assign('FieldName', isc_html_escape($field['fieldname']));

					if($field['fieldtype'] == 'file') {
						$fieldValue = '<a href="'.GetConfig('ShopPath').'/'.GetConfig('ImageDirectory').'/configured_products/'.urlencode($field['originalfilename']).'">'.isc_html_escape($field['originalfilename']).'</a>';
					}
					else {
						$fieldValue = isc_html_escape($field['textcontents']);
					}

					$template->assign('FieldValue', $fieldValue);
					$productFields .= $template->getSnippet('PrintableInvoiceItemConfigurableField');
				}
			}
			$template->assign('ProductConfigurableFields', $productFields);
			if(!$productFields) {
				$template->assign('HideConfigurableFields', 'display: none');
			}
			else {
				$template->assign('HideConfigurableFields', '');
			}

			$template->assign('ProductCost', currencyConvertFormatPrice(
				$product[$itemPriceColumn],
				$order['ordcurrencyid'],
				$order['ordcurrencyexchangerate'],
				true)
			);

			$template->assign('ProductTotalCost', currencyConvertFormatPrice(
				$product[$itemTotalColumn],
				$order['ordcurrencyid'],
				$order['ordcurrencyexchangerate'],
				true)
			);

			if($product['ordprodwrapname']) {
				$template->assign('FieldName', getLang('GiftWrapping'));
				$template->assign('FieldValue', isc_html_escape($product['ordprodwrapname']));
				$template->assign('ProductGiftWrapping', $template->getSnippet('PrintableInvoiceItemConfigurableField'));
				$template->assign('HideGiftWrapping', '');
			}
			else {
				$template->assign('ProductGiftWrapping', '');
				$template->assign('HideGiftWrapping', 'display: none');
			}

			if($product['ordprodeventdate']) {
				$template->assign('FieldName', isc_html_escape($product['ordprodeventname']));
				$template->assign('FieldValue', isc_date('dS M Y', $product['ordprodeventdate']));
				$template->assign('ProductEventDate', $template->getSnippet('PrintableInvoiceItemConfigurableField'));
				$template->assign('HideEventDate', '');
			}
			else {
				$template->assign('ProductEventDate', '');
				$template->assign('HideEventDate', 'display: none');
			}

			// determine preorder status
			$template->Assign('HidePreOrder', '');
			$template->Assign('ProductPreOrder', '');

			if ($product['productid'] && $product['prodpreorder']) {
				// product is pre-order because it exists in current db with preorder status
				if ($product['prodreleasedate']) {
					$message = $product['prodpreordermessage'];
					if (!$message) {
						$message = GetConfig('DefaultPreOrderMessage');
					}
					$message = str_replace('%%DATE%%', isc_date(GetConfig('DisplayDateFormat'), $product['prodreleasedate']), $message);
				} else {
					$message = GetLang('PreOrderProduct');
				}
				$template->Assign('ProductPreOrder', $message);
			} else {
				$template->Assign('HidePreOrder', 'display:none;');
			}

			$productsTable .= $template->GetSnippet('PrintableInvoiceItem');

			$template->assign('hideAddressColumn', 'display: none');
			$template->assign('productShippingAddress', '');
			$template->assign('addressColumnRowSpan', 1);
			$template->assign('invoiceItemClass', '');
		}
	}

	if($order['shipping_address_count'] > 1) {
		$template->assign('hideAddressColumn', '');
	}

	$template->assign('ProductsTable', $productsTable);

	return $template->GetSnippet('PrintableInvoice');
}

/**
 * Given some details, generate a printable packing slip.
 *
 * @param string $title Title of the packing slip.
 * @param array $details Array of details about the packing slip.
 * @param array $products Array of products for the packing slip.
 * @return string Generated HTML packing slip.
 */
function generatePrintablePackingSlip($title, $details, $products)
{
	$db = $GLOBALS['ISC_CLASS_DB'];

	$template = new TEMPLATE('ISC_LANG');
	$template->frontEnd();
	$template->setTemplateBase(ISC_BASE_PATH . "/templates");
	$template->panelPHPDir = ISC_BASE_PATH . "/includes/display/";
	$template->templateExt = "html";
	$template->setTemplate(getConfig("template"));

	$template->assign('PackingSlipTitle', $title);
	$template->assign('OrderId', $details['shiporderid']);
	$template->assign('OrderDate', cdate($details['shiporderdate']));

	if(!empty($details['shipmethod'])) {
		$template->assign('ShippingMethod', isc_html_escape($details['shipmethod']));
	}
	else {
		$template->assign('HideShippingMethod', 'display: none');
	}

	if(!empty($details['shiptrackno'])) {
		$template->assign('TrackingNo', isc_html_escape($details['shiptrackno']));
	}
	else {
		$template->assign('HideTrackingNo', 'display: none');
	}

	if(!empty($details['shipcomments'])) {
		$template->assign('Comments', nl2br(isc_html_escape($details['shipcomments'])));
		$template->assign('HideComments', '');
	}
	else {
		$template->assign('Comments', '');
		$template->assign('HideComments', 'display: none');
	}

	if(!empty($details['shipdate'])) {
		$template->assign('DateShipped', cDate($details['shipdate']));
	}
	else {
		$template->assign('HideShippingDate', 'display: none');
	}

	if(empty($products)) {
		return false;
	}

	$query = "
		SELECT customerid, CONCAT(custconfirstname, ' ', custconlastname) AS ordcustname, custconemail AS ordcustemail, custconphone AS ordcustphone
		FROM [|PREFIX|]customers
		WHERE customerid = '".$db->Quote($details['shipcustid'])."'
	";
	$query .= $db->AddLimit(0, 1);
	$result = $db->Query($query);

	$template->assign('CustomerName', '');
	$template->assign('CustomerEmail', '');
	$template->assign('CustomerPhone', '');

	if($customer = $db->Fetch($result)) {
		// Format the customer details
		$template->assign('CustomerName', isc_html_escape($customer['ordcustname']));
		$template->assign('CustomerEmail', isc_html_escape($customer['ordcustemail']));
		$template->assign('CuastomerPhone', isc_html_escape($customer['ordcustphone']));
		$template->assign('CustomerId', $customer['customerid']);
	}
	else {
		$template->assign('HideCustomerDetails', 'display: none');
	}

	$template->assign('StoreAddressFormatted', nl2br(GetConfig('StoreAddress')));

	$addressDetails = array(
		'shipfirstname'	=> $details['shipbillfirstname'],
		'shiplastname'	=> $details['shipbilllastname'],
		'shipcompany'	=> $details['shipbillcompany'],
		'shipaddress1'	=> $details['shipbillstreet1'],
		'shipaddress2'	=> $details['shipbillstreet2'],
		'shipcity'		=> $details['shipbillsuburb'],
		'shipstate'		=> $details['shipbillstate'],
		'shipzip'		=> $details['shipbillzip'],
		'shipcountry'	=> $details['shipbillcountry'],
		'countrycode'	=> $details['shipbillcountrycode'],
	);
	$template->assign('BillingAddress', ISC_ADMIN_ORDERS::buildOrderAddressDetails($addressDetails, false));
	$template->assign('BillingPhone', isc_html_escape($details['shipbillphone']));
	if(!$details['shipbillphone']) {
		$template->assign('HideBillingPhone', 'display: none');
	}
	$template->assign('BillingEmail', isc_html_escape($details['shipbillemail']));
	if(!$details['shipbillemail']) {
		$template->assign('HideBillingEmail', 'display: none');
	}

	$addressDetails = array(
		'shipfirstname'	=> $details['shipshipfirstname'],
		'shiplastname'	=> $details['shipshiplastname'],
		'shipcompany'	=> $details['shipshipcompany'],
		'shipaddress1'	=> $details['shipshipstreet1'],
		'shipaddress2'	=> $details['shipshipstreet2'],
		'shipcity'		=> $details['shipshipsuburb'],
		'shipstate'		=> $details['shipshipstate'],
		'shipzip'		=> $details['shipshipzip'],
		'shipcountry'	=> $details['shipshipcountry'],
		'countrycode'	=> $details['shipshipcountrycode'],
	);
	$template->assign('ShippingAddress', ISC_ADMIN_ORDERS::buildOrderAddressDetails($addressDetails, false));
	$template->assign('ShippingPhone', isc_html_escape($details['shipshipphone']));
	if(!$details['shipshipphone']) {
		$template->assign('HideShippingPhone', 'display: none');
	}
	$template->assign('ShippingEmail', isc_html_escape($details['shipshipemail']));
	if(!$details['shipshipemail']) {
		$template->assign('HideShippingEmail', 'display: none');
	}

	$fieldsArray = array();
	$query = "
		SELECT o.*
		FROM [|PREFIX|]order_configurable_fields o
		JOIN [|PREFIX|]product_configurable_fields p ON o.fieldid = p.productfieldid
		WHERE o.orderid=".(int)$details['shiporderid']."
		ORDER BY p.fieldsortorder ASC
	";
	$result = $db->Query($query);
	$fields = array();
	while ($row = $db->Fetch($result)) {
		$fieldsArray[$row['ordprodid']][] = $row;
	}

	// Build the list of products that are being shipped
	$productsTable = '';
	foreach($products as $product) {
		$template->assign('ProductName', isc_html_escape($product['prodname']));
		if($product['prodcode']) {
			$template->assign('ProductSku', isc_html_escape($product['prodcode']));
		}
		else {
			$template->assign('ProductSku', getLang('NA'));
		}
		$template->assign('ProductQuantity', $product['prodqty']);

		$pOptions = '';
		if($product['prodoptions'] != '') {
			$options = @unserialize($product['prodoptions']);
			if(!empty($options)) {
				foreach($options as $name => $value) {
					$template->assign('FieldName', isc_html_escape($name));
					$template->assign('FieldValue', isc_html_escape($value));
					$pOptions .= $template->GetSnippet('PrintableInvoiceItemConfigurableField');
				}
			}
		}

		if($pOptions) {
			$template->assign('ProductOptions', $pOptions);
			$template->assign('HideVariationOptions', '');
		}
		else {
			$template->assign('HideVariationOptions', 'display: none');
		}

		$productFields = '';
		if(!empty($fieldsArray[$product['prodordprodid']])) {
			$fields = $fieldsArray[$product['prodordprodid']];
			foreach($fields as $field) {
				if(empty($field['textcontents']) && empty($field['filename'])) {
					continue;
				}

				$fieldValue = '-';
				$template->assign('FieldName', isc_html_escape($field['fieldname']));

				if($field['fieldtype'] == 'file') {
					$fieldValue = '<a href="'.GetConfig('ShopPath').'/'.GetConfig('ImageDirectory').'/configured_products/'.urlencode($field['originalfilename']).'">'.isc_html_escape($field['originalfilename']).'</a>';
				}
				else {
					$fieldValue = isc_html_escape($field['textcontents']);
				}

				$template->assign('FieldValue', $fieldValue);
				$productFields .= $template->getSnippet('PrintableInvoiceItemConfigurableField');
			}
		}
		$template->assign('ProductConfigurableFields', $productFields);
		if(!$productFields) {
			$template->assign('HideConfigurableFields', 'display: none');
		}
		else {
			$template->assign('HideConfigurableFields', '');
		}

		if($product['prodeventdatename']) {
			$template->assign('FieldName', isc_html_escape($product['prodeventdatename']));
			$template->assign('FieldValue', isc_date('dS M Y', $product['prodeventdate']));
			$template->assign('ProductEventDate', $template->getSnippet('PrintableInvoiceItemConfigurableField'));
			$template->assign('HideEventDate', '');
		}
		else {
			$template->assign('ProductEventDate', '');
			$template->assign('HideEventDate', 'display: none');
		}

		$productsTable .= $template->GetSnippet('PrintablePackingSlipItem');
	}
	$template->assign('ProductsTable', $productsTable);
	$template->setTemplate('packing_slip_print');
	return $template->parseTemplate(true);
}

/**
 * Generate a packing slip for a shipment in an order.
 *
 * @param int The shipment ID to print the packing slip for.
 * @return string The generated packing slip (HTML)
 */
function generateShipmentPackingSlip($shipmentId)
{
	$db = $GLOBALS['ISC_CLASS_DB'];

	$products = array();

	$query = "
		SELECT *
		FROM [|PREFIX|]shipments
		WHERE shipmentid='".(int)$shipmentId."'
	";
	$result = $db->query($query);
	$shipmentDetails = $db->fetch($result);
	if(!isset($shipmentDetails['shipmentid'])) {
		return false;
	}

	// Load the items
	$query = "
		SELECT *
		FROM [|PREFIX|]shipment_items
		WHERE shipid='".(int)$shipmentId."'
	";
	$result = $db->Query($query);
	while($product = $db->Fetch($result)) {
		// Standadize the product details
		$products[] = array(
			'prodcode' => $product['itemprodsku'],
			'prodname' => $product['itemprodname'],
			'prodqty' => $product['itemqty'],
			'prodoptions' => $product['itemprodoptions'],
			'prodvariationid' => $product['itemprodvariationid'],
			'prodordprodid' => $product['itemordprodid'],
			'prodeventdatename' => $product['itemprodeventname'],
			'prodeventdate' => $product['itemprodeventdate'],
		);
	}

	$title = sprintf(GetLang('PackingSlipTitleShipment'), $shipmentDetails['shipmentid']);
	return generatePrintablePackingSlip($title, $shipmentDetails, $products);
}

/**
 * Generate a packing slip for an entire order. If the order has multiple
 * shipping destinations then a packing slip will be generated for each.
 *
 * @param int The order ID to print the packing slip for.
 * @return string The generated packing slip (HTML)
 */
function generateOrderPackingSlip($orderId)
{
	$db = $GLOBALS['ISC_CLASS_DB'];

	// allow access to deleted orders if printing from within control panel
	$isAdmin = (defined('ISC_ADMIN_CP') && ISC_ADMIN_CP);

	$order = getOrder($orderId, null, null, $isAdmin);
	if(empty($order) || $order['ordisdigital']) {
		return false;
	}

	// Fetch the shipping addresses in this order
	$addresses = array();
	$query = "
		SELECT *
		FROM [|PREFIX|]order_addresses
		WHERE order_id='".(int)$orderId."'
	";
	$result = $db->query($query);
	while($address = $db->fetch($result)) {
		$addresses[$address['id']] = $address;
	}

	// Fetch shipping details for this order too
	$query = "
		SELECT *
		FROM [|PREFIX|]order_shipping
		WHERE order_id='".(int)$orderId."'
		ORDER BY order_address_id
	";
	$result = $db->query($query);
	while($shipping = $db->fetch($result)) {
		$addresses[$shipping['order_address_id']]['shipping'] = $shipping;
	}

	// Now fetch products
	$addressProducts = array();
	$query = "
		SELECT *
		FROM [|PREFIX|]order_products
		WHERE orderorderid='".(int)$orderId."'
	";
	$result = $db->query($query);
	while($product = $db->fetch($result)) {
		// Digital item - these do not have an address
		if(!$product['order_address_id']) {
			continue;
		}
		$addressProducts[$product['order_address_id']][] = array(
			'prodcode' => $product['ordprodsku'],
			'prodname' => $product['ordprodname'],
			'prodqty' => $product['ordprodqty'],
			'prodoptions' => $product['ordprodoptions'],
			'prodvariationid' => $product['ordprodvariationid'],
			'prodordprodid' => $product['orderprodid'],
			'prodeventdatename' => $product['ordprodeventname'],
			'prodeventdate' => $product['ordprodeventdate'],
		);
	}

	$packingSlips = '';
	foreach($addresses as $addressId => $address) {
		if(empty($addressProducts[$addressId])) {
			continue;
		}
		$title = sprintf(GetLang('PackingSlipTitleOrder'), $order['orderid']);
		$shipmentDetails = array(
			'shipcustid'			=> $order['ordcustid'],
			'shipping_module'		=> $address['shipping']['module'],
			'shipmethod'			=> $address['shipping']['method'],
			'shiporderid'			=> $order['orderid'],
			'shiporderdate'			=> $order['orddate'],
			'shipcomments'			=> $order['ordcustmessage'],
			'shipbillfirstname'		=> $order['ordbillfirstname'],
			'shipbilllastname'		=> $order['ordbilllastname'],
			'shipbillcompany'		=> $order['ordbillcompany'],
			'shipbillstreet1'		=> $order['ordbillstreet1'],
			'shipbillstreet2'		=> $order['ordbillstreet2'],
			'shipbillsuburb'		=> $order['ordbillsuburb'],
			'shipbillstate'			=> $order['ordbillstate'],
			'shipbillzip'			=> $order['ordbillzip'],
			'shipbillcountry'		=> $order['ordbillcountry'],
			'shipbillcountrycode'	=> $order['ordbillcountrycode'],
			'shipbillcountryid'		=> $order['ordbillcountryid'],
			'shipbillstateid'		=> $order['ordbillstateid'],
			'shipbillphone'			=> $order['ordbillphone'],
			'shipbillemail'			=> $order['ordbillemail'],
			'shipshipfirstname'		=> $address['first_name'],
			'shipshiplastname'		=> $address['last_name'],
			'shipshipcompany'		=> $address['company'],
			'shipshipstreet1'		=> $address['address_1'],
			'shipshipstreet2'		=> $address['address_2'],
			'shipshipsuburb'		=> $address['city'],
			'shipshipstate'			=> $address['state'],
			'shipshipzip'			=> $address['zip'],
			'shipshipcountry'		=> $address['country'],
			'shipshipcountrycode'	=> $address['country_iso2'],
			'shipshipcountryid'		=> $address['country_id'],
			'shipshipstateid'		=> $address['state_id'],
			'shipshipphone'			=> $address['phone'],
			'shipshipemail'			=> $address['email'],
		);

		if($packingSlips) {
			$packingSlips .= '<p class="PageBreak">&nbsp;</p>';
		}
		$packingSlips .= generatePrintablePackingSlip($title, $shipmentDetails, $addressProducts[$addressId]);
	}
	return $packingSlips;
}

function getInvoiceShippingAddressBlock($address)
{
	$template = $GLOBALS['ISC_CLASS_TEMPLATE'];
	$template->assign('ShipFullName', $address['first_name'].' '.$address['last_name']);
	if($address['company']) {
		$template->assign('ShipCompany', '<br />'.isc_html_escape($address['company']));
	}
	else {
		$template->assign('ShipCompany', '');
	}

	$addressLine = isc_html_escape($address['address_1']);
	if ($address['address_2'] != "") {
		$addressLine .=  '<br />' . isc_html_escape($address['address_2']);
	}
	$template->assign('ShipAddressLines', $addressLine);

	$template->assign('ShipSuburb', isc_html_escape($address['city']));
	$template->assign('ShipState', isc_html_escape($address['state']));
	$template->assign('ShipZip', isc_html_escape($address['zip']));
	$template->assign('ShipCountry', isc_html_escape($address['country']));
	$template->assign('ShippingPhone', getLang('Phone').': '.isc_html_escape($address['phone']));
	return $template->getSnippet('AddressLabel');
}
