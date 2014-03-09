<?php
//@todo remove the shipping_address_count = 1 restriction on queries when shipworks has support for multi address orders

class SHIPPINGMANAGER_SHIPWORKS extends ISC_SHIPPINGMANAGER {
	private $authenticated = false;

	public function __construct()
	{
		parent::__construct();

		// generate a hash if we don't have one
		if ($this->IsEnabled() && $this->GetValue('hash') == null) {
			$this->SetValue('hash', md5(uniqid('shipworks', true)));
			$settings = $this->moduleVariables;
			unset($settings['is_setup']);
			$this->SaveModuleSettings($settings);
		}

		$this->SetName(GetLang('ShipWorksName'));
		$this->SetDescription(GetLang('ShipWorksDescription'));
		$this->SetHelpText(GetLang('ShipWorksHelp', array('userName' => 'admin', 'userToken' => $this->GetValue('hash'), 'moduleURL' => GetConfig('ShopPathSSL') . '/admin/shippingmanager.php?manager=shipworks')));
		$this->SetImage('shipworks.gif');
	}

	public function handleAction()
	{
		// check for a secure connection
		if ($_SERVER['HTTPS'] == 'off') {
			$this->outputError(10, 'A secure (https://) connection is required.');
		}

		// authenticate the user
		$this->authenticated = $this->authenticateUser($error);
		if (!$this->authenticated) {
			$this->outputError($error[0], $error[1]);
		}

		$action = '';
		if (isset($_GET['action'])) {
			$action = strtolower($_GET['action']);
		}

		switch ($action) {
			case 'getstore':
				$this->getStoreDetails();
				break;
			case 'getstatuscodes':
				$this->getStatusCodes();
				break;
			case 'getcount':
				$this->getOrderCount();
				break;
			case 'getorders':
				$this->getOrders();
				break;
			case 'updatestatus':
				$this->updateOrderStatus();
				break;
			default:
				$this->writeShipWorksXML(array());
				break;
		}
	}

	private function authenticateUser(&$error)
	{
		if (empty($_GET['username']) || empty($_GET['password'])) {
			$error = array(60, "The username and password was not supplied.");
			return false;
		}

		$username = $_GET['username'];
		$hash = $_GET['password'];

		if ($username != 'admin' || $hash != $this->GetValue('hash')) {
			$error = array(50, "The username or password is incorrect.");
			return false;
		}

		return true;
	}

	private function writeShipWorksXML($data)
	{
		$moduleVersion = '2.9.51';

		$data['ModuleVersion'] = $moduleVersion;

		if ($this->authenticated) {
			$authStr = "true";
		}
		else {
			$authStr = "false";
		}
		$data['Admin']['Authenticated'] = $authStr;

		$xml = Interspire_Xml::createXML('ShipWorks');
		Interspire_Xml::addArrayToXML($xml, $data);
		$xmlString = Interspire_Xml::prettyIndent($xml);

		Interspire_Xml::sendHttpHeader();

		echo $xmlString;

		exit;
	}

	private function getStoreDetails()
	{
		$data['Store'] = array(
			'Name' 		=> GetConfig('CompanyName'),
			'Owner' 	=> '',
			'Email' 	=> GetConfig('AdminEmail'),
			'State' 	=> GetConfig('CompanyState'),
			'Country' 	=> GetConfig('CompanyCountry'),
			'Website' 	=> GetConfig('ShopPathNormal')
		);

		$this->writeShipWorksXML($data);
	}

	private function getStatusCodes()
	{
		$codes = array();

		$query = 'SELECT * FROM [|PREFIX|]order_status ORDER BY statusorder';
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($statusRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
			$codes['StatusCode'][] = array('Code' => $statusRow['statusid'], 'Name' => $statusRow['statusdesc']);
		}

		$data['StatusCodes'] = $codes;

		$this->writeShipWorksXML($data);
	}

	private function getOrderCount()
	{
		$startTimeStamp = 0;
		if (isset($_GET['start'])) {
			$startTimeStamp = (int)$_GET['start'];
		}

		// return the start param for diagnostic purposes
		$data['Parameters'] = array('Start' => date("Y-m-d H:i:s", $startTimeStamp));

		$query = '
			SELECT
				SUM(shipping_address_count) AS orderCount
			FROM
				[|PREFIX|]orders
			WHERE
				ordstatus != 0 AND
				deleted = 0 AND
				ordlastmodified > ' . $startTimeStamp . ' AND
				shipping_address_count = 1
		';

		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$orderCount = $GLOBALS['ISC_CLASS_DB']->FetchOne($res);

		$data['OrderCount'] = $orderCount;

		$this->writeShipWorksXML($data);
	}

	private function getOrders()
	{
		$startTimeStamp = 0;
		if (isset($_GET['start'])) {
			$startTimeStamp = (int)$_GET['start'];
		}

		$limit = 50; // maximum amount of orders to retrieve
		if (isset($_GET['maxcount'])) {
			$limit = (int)$_GET['maxcount'];
		}

		$totalRecords = 0;

		$query = '
			SELECT
				o.*,
				os.*,
				oa.*,
				oa.id AS address_id
			FROM
				(
					SELECT
						orderid
					FROM
						[|PREFIX|]orders
					WHERE
						ordstatus != 0 AND
						deleted = 0 AND
						ordlastmodified > ' . $startTimeStamp . ' AND
						shipping_address_count = 1
					LIMIT
						' . $limit . '
				) AS orderset
				INNER JOIN [|PREFIX|]orders o ON o.orderid = orderset.orderid
				LEFT JOIN [|PREFIX|]order_shipping os ON os.order_id = o.orderid
				LEFT JOIN [|PREFIX|]order_addresses oa ON oa.id = os.order_address_id
			ORDER BY
				o.ordlastmodified,
				o.orderid,
				os.id
		';

		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$orders = array();
		$lastModifiedTime = 0;
		$lastOrderId = 0;
		$addressIndex = 1;
		while ($orderRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
			if ($lastOrderId == $orderRow['orderid']) {
				$addressIndex++;
			}
			else {
				$addressIndex = 1;
			}

			$orders['Order'][] = $this->getOrderData($orderRow, $addressIndex);

			$lastModifiedTime = $orderRow['ordlastmodified'];
			$lastOrderId = $orderRow['orderid'];
			$totalRecords++;
		}

		// retrieve any other orders that might have the same last modified time
		$query = '
			SELECT
				o.*,
				os.*,
				oa.*,
				oa.id AS address_id
			FROM
				(
					SELECT
						orderid
					FROM
						[|PREFIX|]orders
					WHERE
						ordstatus != 0 AND
						deleted = 0 AND
						ordlastmodified = ' . $lastModifiedTime . ' AND
						orderid > ' . $lastOrderId . ' AND
						shipping_address_count = 1
				) AS orderset
				INNER JOIN [|PREFIX|]orders o ON o.orderid = orderset.orderid
				LEFT JOIN [|PREFIX|]order_shipping os ON os.order_id = o.orderid
				LEFT JOIN [|PREFIX|]order_addresses oa ON oa.id = os.order_address_id
			ORDER BY
				o.orderid,
				os.id
		';

		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($orderRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
			if ($lastOrderId == $orderRow['orderid']) {
				$addressIndex++;
			}
			else {
				$addressIndex = 1;
			}

			$orders['Order'][] = $this->getOrderData($orderRow, $addressIndex);

			$lastOrderId = $orderRow['orderid'];
			$totalRecords++;
		}

		$data['Parameters'] = array(
			'Start' => date("Y-m-d H:i:s", $startTimeStamp),
			'MaxCount' => $totalRecords
		);

		$data['Orders'] = $orders;

		$this->writeShipWorksXML($data);
	}

	private function getOrderData($orderRow, $addressIndex)
	{
		// Get the customer data
		if ($orderRow['ordcustid'] == 0){
			$customerData = array(
				'CustomerID' 	=> -1,
				'Phone' 		=> $orderRow['ordbillphone'],
				'Email'			=> $orderRow['ordbillemail']
			);
		}
		else {
			$customer = GetCustomer($orderRow['ordcustid']);
			$customerData = array(
				'CustomerID'	=> $orderRow['ordcustid'],
				'Phone'			=> $customer['custconphone'],
				'Email'			=> $customer['custconemail']
			);
		}

		$orderId = $orderRow['orderid'];

		if ($orderRow['shipping_address_count'] > 1) {
			$orderId .= '-' . $addressIndex;
		}

		$data = array(
			'OrderNumber' 		=> $orderId,
			'OrderDate' 		=> gmdate('Y-m-d H:i:s', $orderRow['orddate']),
			'LastModified' 		=> gmdate('Y-m-d H:i:s', $orderRow['ordlastmodified']),
			'LastModifiedLocal'	=> isc_date('Y-m-d H:i:s', $orderRow['ordlastmodified']),
			'ShippingMethod'	=> $orderRow['method'],
			'StatusCode'		=> $orderRow['ordstatus'],

			'CustomerComment'	=> $orderRow['ordcustmessage'],
			'Customer'			=> $customerData,

			'ShipAddress'		=> array(
									'Name'		=> $orderRow['first_name'] . ' ' . $orderRow['last_name'],
									'Company'	=> $orderRow['company'],
									'Street1'	=> $orderRow['address_1'],
									'Street2'	=> $orderRow['address_2'],
									'Street3'	=> '',
									'City'		=> $orderRow['city'],
									'PostalCode'=> $orderRow['zip'],
									'State'		=> $orderRow['state'],
									'Country'	=> $orderRow['country_iso2']
								),

			'BillAddress'		=> array(
									'Name'		=> $orderRow['ordbillfirstname'] . ' ' . $orderRow['ordbilllastname'],
									'Company'	=> $orderRow['ordbillcompany'],
									'Street1'	=> $orderRow['ordbillstreet1'],
									'Street2'	=> $orderRow['ordbillstreet2'],
									'Street3'	=> '',
									'City'		=> $orderRow['ordbillsuburb'],
									'PostalCode'=> $orderRow['ordbillzip'],
									'State'		=> $orderRow['ordbillstate'],
									'Country'	=> $orderRow['ordbillcountrycode']
								),

			'Payment'			=> array(
									'Method' => $orderRow['orderpaymentmethod'],
								),
		);


		$incTaxPrices = false;
		if (GetConfig('taxDefaultTaxDisplayOrders') != TAX_PRICES_DISPLAY_EXCLUSIVE) {
			$incTaxPrices = true;
		}

		// get the products for the order
		$items = array();
		$totalWrapCost = 0;

		$query = '
			SELECT
				op.*,
				pi.*
			FROM
				[|PREFIX|]order_products op
				LEFT JOIN [|PREFIX|]product_images pi ON (pi.imageprodid = op.ordprodid AND pi.imageisthumb = 1)
			WHERE
				op.order_address_id = ' . $orderRow['address_id'];

		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($productRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
			$item = array(
				'ItemID'	=> $productRow['orderprodid'],
				'ProductID'	=> $productRow['ordprodid'],
				'Code'		=> $productRow['ordprodsku'],
				'Name'		=> $productRow['ordprodname'],
				'Quantity'	=> $productRow['ordprodqty'],
				'Weight'	=> ConvertWeight($productRow['ordprodweight'], 'lbs'),
			);

			if ($incTaxPrices) {
				$item['UnitPrice'] = $productRow['price_inc_tax'];
				$totalWrapCost += $productRow['wrapping_cost_inc_tax'] * $productRow['ordprodqty'];
			}
			else {
				$item['UnitPrice'] = $productRow['price_ex_tax'];
				$totalWrapCost += $productRow['wrapping_cost_ex_tax'] * $productRow['ordprodqty'];
			}

			try {
				$image = new ISC_PRODUCT_IMAGE();
				$image->populateFromDatabaseRow($productRow);
				$item['Image'] = $image->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_ZOOM, true);
			}
			catch (Exception $ex) {
			}

			$items['Item'][] = $item;
		}

		$data['Items'] = $items;

		// get the totals
		$totals = array();
		$totalID = 1;

		// gift wrapping cost
		if ($totalWrapCost > 0) {
			$total = array(
				'TotalID' 	=> $totalID++,
				'Name'		=> GetLang('ShipWorksGiftWrapping'),
				'Text'		=> FormatPrice($totalWrapCost),
				'Value'		=> $totalWrapCost,
				'Class'		=> 'Adjust'
			);

			$totals['Total'][] = $total;
		}

		// shipping cost
		if ($orderRow['shipping_cost_ex_tax'] > 0) {
			if ($incTaxPrices) {
				$shippingCost = $orderRow['shipping_cost_inc_tax'];
			}
			else {
				$shippingCost = $orderRow['shipping_cost_ex_tax'];
			}

			$total = array(
				'TotalID' 	=> $totalID++,
				'Name'		=> GetLang('ShipWorksShipping'),
				'Text'		=> FormatPrice($shippingCost),
				'Value'		=> $shippingCost,
				'Class'		=> 'Shipping'
			);

			$totals['Total'][] = $total;
		}

		// handling cost
		if ($orderRow['handling_cost_ex_tax'] > 0) {
			if ($incTaxPrices) {
				$handlingCost = $orderRow['handling_cost_inc_tax'];
			}
			else {
				$handlingCost = $orderRow['handling_cost_ex_tax'];
			}

			$total = array(
				'TotalID' 	=> $totalID++,
				'Name'		=> GetLang('ShipWorksHandling'),
				'Text'		=> FormatPrice($handlingCost),
				'Value'		=> $handlingCost,
				'Class'		=> 'Shipping'
			);

			$totals['Total'][] = $total;
		}

		// tax (not included in total)
		if ($orderRow['total_tax'] > 0 && !$incTaxPrices) {
			$total = array(
				'TotalID' 	=> $totalID++,
				'Name'		=> 'Tax',
				'Text'		=> FormatPrice($orderRow['total_tax']),
				'Value'		=> $orderRow['total_tax'],
				'Class'		=> 'Tax'
			);

			$totals['Total'][] = $total;
		}

		// total
		if ($incTaxPrices) {
			$orderTotal = $orderRow['total_inc_tax'];
		}
		else {
			$orderTotal = $orderRow['total_ex_tax'];
		}

		$total = array(
			'TotalID' 	=> $totalID++,
			'Name'		=> GetLang('ShipWorksTotal'),
			'Text'		=> FormatPrice($orderTotal),
			'Value'		=> $orderTotal,
			'Class'		=> 'ot_total'
		);

		$totals['Total'][] = $total;

		// gift certificates
		if ($orderRow['ordgiftcertificateamount'] > 0) {
			$total = array(
				'TotalID' 	=> $totalID++,
				'Name'		=> GetLang('ShipWorksGiftCertificates'),
				'Text'		=> FormatPrice($orderRow['ordgiftcertificateamount']),
				'Value'		=> $orderRow['ordgiftcertificateamount'] * -1,
				'Class'		=> 'Adjust'
			);

			$totals['Total'][] = $total;
		}

		// other discount amount
		if ($orderRow['orddiscountamount'] > 0) {
			$total = array(
				'TotalID' 	=> $totalID++,
				'Name'		=> GetLang('ShipWorksDiscounts'),
				'Text'		=> FormatPrice($orderRow['orddiscountamount']),
				'Value'		=> $orderRow['orddiscountamount'] * -1,
				'Class'		=> 'Adjust'
			);

			$totals['Total'][] = $total;
		}

		$data['Totals'] = $totals;

		return $data;
	}

	private function updateOrderStatus()
	{
		if (!isset($_GET['order']) || !isset($_GET['code']) || !isset($_GET['comments'])) {
			$this->outputError(40, "Not all parameters supplied.");
		}

		$orderID = (int)$_GET['order'];
		$status = (int)$_GET['code'];

		$update = array(
			'ordstatus' => $status
		);

		if (!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('orders', $update, 'orderid = ' . $orderID)) {
			$this->outputError(70, "Order $orderID no longer exists.");
		}

		$this->writeShipWorksXML(array());
	}

	private function outputError($errorCode, $errorDescription)
	{
		$data['Error'] = array(
			'Code'			=> $errorCode,
			'Description' 	=> $errorDescription
		);

		$this->writeShipWorksXML($data);
	}
}
