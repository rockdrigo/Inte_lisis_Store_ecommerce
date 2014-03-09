<?php
class ACCOUNTING_STONEEDGE_ORDERS
{
	 /**
	 * Count the number of orders in the database
	 *
	 * Method will process the post data and create text for display. Used by SEOM to determine how many times to run DownloadOrders().
	 *
	 * @access public
	 * @return string a response to the data to display on the page.
	 */

	public function ProcessOrderCount()
	{
		$response = '';
		if (isset($_REQUEST['lastorder'])) {
			/* either an order number or 'All' */
			if ($_REQUEST['lastorder'] == 'All') {
				/* return a count of all orders in the database */
				$count = StoneEdgeCount('orders',"WHERE ordstatus IN('2', '3', '9', '10', '11') AND deleted = 0");
				$response = "SetiResponse: ordercount=$count";
			} else {
				/* return a count of all orders after the last order passed to SEOM to make sure that this isn't a SQL injection attempt. */
				if (is_numeric($_REQUEST['lastorder'])) {
					$previousend = $_REQUEST['lastorder'];
				} else {
					/* Stone Edge Order Manager will always send lastorder. If we get here, ignore their request and change the last order to 1. */
					$previousend = 1;
				}
				$count = StoneEdgeCount("orders","WHERE ordstatus IN('2', '3', '9', '10', '11') AND deleted = 0 AND orderid > $previousend");
				$response = "SetiResponse: ordercount=$count";
			}
		} else {
			/* either a date in 10/Jun/2003 format or 'All' */
			if ($_REQUEST['lastdate'] == 'All') {
				/* return a count of all orders in the database */
				$count = StoneEdgeCount('orders','');
				$response = "SetiResponse: ordercount=$count";
			} else {
				/* return the orders after the last timestamp. */
				/* convert their date to our date structure */
				list($day,$month,$year) = explode('/',$_REQUEST['lastdate']);
				$date = strtotime("$day $month $year");
				/* make sure that this isn't a SQL injection attempt. */
				if (is_numeric($date)) {
					$count = StoneEdgeCount('orders',"WHERE ordstatus IN('2', '3', '9', '10', '11') AND deleted = 0 AND orddate >= $date");
				} else {
					/* then something went wrong so return all orders. Always return something. */
					$count = StoneEdgeCount('orders','');
				}
				$response = "SetiResponse: ordercount=$count";
			}
		}
		return $response;
	}

	/**
	 * Download orders in the database to SEOM
	 *
	 * Method will process the posted data and create XML to be displayed.
	 *
	 * @access public
	 * @return string XML response to display on the page for orders requested
	 */

	public function DownloadOrders()
	{
		$start = 0;
		$numresults = 0;
		$lastOrderCondition = '';
		$xml = new SimpleXMLElement('<?xml version="1.0"?><SETIOrders />');

		if (isset($_REQUEST['startnum']) && (int)$_REQUEST['startnum'] > 0 && isset($_REQUEST['batchsize']) && (int)$_REQUEST['batchsize']  > 0) {
			$start = (int)$_REQUEST['startnum'] - 1;
			$numresults = (int)$_REQUEST['batchsize'];
		}

		/* should we limit the number of results in this query? */
		$limitQuery = '';
		if($start >= 0 && $numresults > 0) {
			$limitQuery = ' LIMIT '. $start .', '. $numresults;
		}

		if (isset($_REQUEST['lastorder'])) {
			if (strtolower($_REQUEST['lastorder']) == 'all') {
				$previousend = 0;
			} else {
				$previousend = (int)$_REQUEST['lastorder'];
			}

			/* check to see if we should start the query from a particular order number */
			if (is_numeric($previousend) && $previousend > 0) {
				$lastOrderCondition = 'AND o.orderid > ' . $previousend;
			}
		} else {
			$GLOBALS['XMLReturn'] .= '<?xml version="1.0" encoding="UTF-8" ?>';
			$GLOBALS['XMLReturn'] .= "<SETIError>";
				$GLOBALS['XMLReturn'] .= "<Response>";
					$GLOBALS['XMLReturn'] .= "<ResponseCode>3</ResponseCode>";
					$GLOBALS['XMLReturn'] .= "<ResponseDescription>No lastorder field received.</ResponseDescription>";
				$GLOBALS['XMLReturn'] .= "</Response>";
			$GLOBALS['XMLReturn'] .= "</SETIError>";
			die($GLOBALS['XMLReturn']);
		}

		/* build our query */
		$query = StoneEdgeOrderQuery("WHERE o.ordstatus IN('2', '3', '9', '10', '11') " . $lastOrderCondition . " AND deleted = 0 ORDER BY o.orderid ASC" . $limitQuery);
		$queryCount = StoneEdgeOrderQueryCount("WHERE o.ordstatus IN('2', '3', '9', '10', '11') " . $lastOrderCondition . " AND deleted = 0 ORDER BY o.orderid ASC");

		if ($GLOBALS['ISC_CLASS_DB']->FetchOne($queryCount) != 0) {
			/* then we have at least one result, so let's build the XML	header response */
			$responseNode = $xml->addChild('Response');
			$responseNode->addChild('ResponseCode', 1);
			$responseNode->addChild('ResponseDescription', 'Success');
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$orderNode = $xml->addChild('Order');
				$orderNode->addChild('OrderNumber', $row['orderid']);
				$orderNode->addChild('OrderDate', date('Y-m-d H:i:s', $row['orddate']));
				$BillingDetails = $orderNode->addChild('Billing');
				$BillingDetails->addChild('FullName', $row['ordbillfirstname'] . ' ' . $row['ordbilllastname']);
				if(isset($row['ordbillcompany']) && $row['ordbillcompany'] != ''){
					$BillingDetails->addChild('Company', $row['ordbillcompany']);
				}
				$BillingDetails->addChild('Phone', $row['ordbillphone']);
				$BillingDetails->addChild('Email', $row['ordbillemail']);
				$AddressBilling = $BillingDetails->addChild('Address');
				$AddressBilling->addChild('Street1', $row['ordbillstreet1']);
				if(isset($row['ordbillstreet2']) && $row['ordbillstreet2'] != ''){
					$AddressBilling->addChild('Street2', $row['ordbillstreet2']);
				}
				$AddressBilling->addChild('City', $row['ordbillsuburb']);
				if(isset($row['stateabbrv']) && $row['stateabbrv'] != ''){
					$AddressBilling->addChild('State', $row['stateabbrv']);
				} else {
					$AddressBilling->addChild('State', $row['ordbillstate']);
				}
				$AddressBilling->addChild('Code', $row['ordbillzip']);
				$AddressBilling->addChild('Country', $row['ordbillcountrycode']);
				$ShippingQuery = "SELECT * FROM [|PREFIX|]order_addresses WHERE order_id = " . $row['orderid'] . " ORDER by id DESC LIMIT 1";
				$ShippingResult = $GLOBALS['ISC_CLASS_DB']->Query($ShippingQuery);
				$firstname = 0;
				while($ship = $GLOBALS['ISC_CLASS_DB']->Fetch($ShippingResult)){
					$ShippingDetails = $orderNode->addChild('Shipping');
					$ShippingDetails->addChild('FullName', $ship['first_name'] . ' ' . $ship['last_name']);
					$firstname = 1;
					if(isset($ship['company']) && $ship['company'] != ''){
						$ShippingDetails->addChild('Company', $ship['company']);
					}
					$ShippingDetails->addChild('Phone', $ship['phone']);
					$ShippingDetails->addChild('Email', $ship['email']);
					$AddressShipping = $ShippingDetails->addChild('Address');
					$AddressShipping->addChild('Street1', $ship['address_1']);
					if(isset($ship['address_2']) && $ship['address_2'] != ''){
						$AddressShipping->addChild('Street2', $ship['address_2']);
					}
					$AddressShipping->addChild('City', $ship['city']);
					$AddressShipping->addChild('State', $ship['state']);
					$AddressShipping->addChild('Code', $ship['zip']);
					$AddressShipping->addChild('Country', $ship['country_iso2']);
				}
				if($firstname == 0){
					$ShippingDetails = $orderNode->addChild('Shipping');
					$ShippingDetails->addChild('FullName', $row['ordbillfirstname'] . ' ' . $row['ordbilllastname']);
					if(isset($row['ordbillcompany']) && $row['ordbillcompany'] != ''){
						$ShippingDetails->addChild('Company', $row['ordbillcompany']);
					}
					$ShippingDetails->addChild('Phone', $row['ordbillphone']);
					$ShippingDetails->addChild('Email', $row['ordbillemail']);
					$AddressShipping = $ShippingDetails->addChild('Address');
					$AddressShipping->addChild('Street1', $row['ordbillstreet1']);
					if(isset($row['ordbillstreet2']) && $row['ordbillstreet2'] != ''){
						$AddressShipping->addChild('Street2', $row['ordbillstreet2']);
					}
					$AddressShipping->addChild('City', $row['ordbillsuburb']);
					if(isset($row['stateabbrv']) && $row['stateabbrv'] != ''){
						$AddressShipping->addChild('State', $row['stateabbrv']);
					} else {
						$AddressShipping->addChild('State', $row['ordbillstate']);
					}
					$AddressShipping->addChild('Code', $row['ordbillzip']);
					$AddressShipping->addChild('Country', $row['ordbillcountrycode']);
				}

				/* set fixed variables for later to save some server time */
				$OrderID = $row['orderid'];
				$OrderSubTotal = $row['subtotal_ex_tax'];
				$OrderDiscounts = '';
				if($row['orddiscountamount'] > 0 || $row['coupon_discount'] > 0){
					$OrderDiscounts = $row['orddiscountamount'] + $row['coupon_discount'];
					$SubTotalLessDiscounts = $OrderSubTotal - $OrderDiscounts;
				}else{
					$SubTotalLessDiscounts = $OrderSubTotal;
				}

				$OrderTaxTotal = $row['subtotal_inc_tax'];
				$TaxAmount = $row['total_tax'];
				$OrderShippingCost = $row['shipping_cost_ex_tax'];
				$OrderTotal = $row['total_inc_tax'];
				$OrderShippingMethod = $row['method'];

				$OrderComments = $row['ordnotes']; /* not shown on invoice */
				$OrderMessage = $row['ordcustmessage']; /* shown on invoice */
				$OrderIP = $row['ordipaddress'];
				$OrderHandlingCost = $row['handling_cost_ex_tax'];
				/**
				 * Sometimes we are having a rounding problem with tax. This next bit adds or subtracts the 0.01 difference
				 * to the TaxAmount variable so that the math makes sense.
				 */
				$calculatedTotal = $SubTotalLessDiscounts + $TaxAmount + $OrderShippingCost + $OrderHandlingCost;
				if ($calculatedTotal != $OrderTotal) {
					$difference = $OrderTotal - $calculatedTotal;
					$TaxAmount += $difference;
				}

				/* Time to get product information for this order from order_products and products tables */
				$productQuery = "SELECT op.*, p.prodweight, p.prodwidth, p.prodheight, p.proddepth FROM [|PREFIX|]order_products op
								 LEFT JOIN [|PREFIX|]products p ON op.ordprodid = p.productid
						 		 WHERE orderorderid = '" . $OrderID ."'";
				$products = $GLOBALS['ISC_CLASS_DB']->Query($productQuery);
				while($prodReturn = $GLOBALS['ISC_CLASS_DB']->Fetch($products)) {

					if (isset($prodReturn['total_tax']) && $prodReturn['total_tax'] > 0) {
						$taxable = 'Yes';
					} else {
						$taxable = 'No';
					}

					/* assign SKU for default product */
					$sku = $prodReturn['ordprodsku'];
					$prodType = $prodReturn['ordprodtype'];

					if ($prodType == 'physical') {
						$prodType = 'Tangible';
					} else {
						$prodType = 'Download';
					}
					$isVariation = 0;

					$productNode = $ShippingDetails->addChild('Product');
					if($sku != ''){
						$productNode->addChild('SKU', htmlentities($sku));
					} else {
						$productNode->addChild('SKU', htmlentities($ProductName));
					}

					$ProductName = htmlentities($prodReturn['ordprodname']);
					$productNode->addChild('Name', $ProductName);
					$productNode->addChild('Quantity', $prodReturn['ordprodqty']);
					$productNode->addChild('ItemPrice', number_format($prodReturn['price_ex_tax'],2));
					$productNode->addChild('Weight', number_format($prodReturn['ordprodweight'],2));
					$productNode->addChild('ProdType', $prodType);
					$productNode->addChild('Taxable', $taxable); //Yes or No
					$total = number_format($prodReturn['ordprodqty'] * $prodReturn['price_ex_tax'],2);
					$productNode->addChild('Total', number_format($total,2));
					$dimension = $productNode->addChild('Dimension');
					$dimension->addChild('Length', number_format($prodReturn['prodwidth'],2));
					$dimension->addChild('Width', number_format($prodReturn['proddepth'],2));
					$dimension->addChild('Height', number_format($prodReturn['prodheight'],2));

					if (isset($prodReturn['ordprodvariationid']) && $prodReturn['ordprodvariationid'] != 0) {
						$isVariation = 1;
						$variationQuery = "SELECT vc.vcpricediff, vc.vcprice, vc.vcweightdiff, vc.vcweight, p.prodcalculatedprice, p.prodweight FROM 
											[|PREFIX|]product_variation_combinations vc
											LEFT JOIN [|PREFIX|]products p on p.productid = vc.vcproductid
											WHERE vc.combinationid = '" . $prodReturn['ordprodvariationid'] . "'";
						$changes = $GLOBALS['ISC_CLASS_DB']->Fetch($GLOBALS['ISC_CLASS_DB']->Query($variationQuery));

						$varprice = 0;
						$varweight = 0;
						if ($changes['vcprice'] != 0.00) {
							if ($changes['vcpricediff'] == 'fixed') {
								$varprice = $changes['vcprice'] - $changes['prodcalculatedprice'];
							} else {
								$varprice = $changes['vcprice'];
							}
						}
						if ($changes['vcweight'] != 0.00) {
							if($changes['vcweightdiff'] == 'fixed') {
								$varweight = $changes['vcweight'] - $changes['prodweight'];
							} else {
								$varweight = $changes['vcweight'];
							}
						}

						$varCounter = 0;
						$variationInfo = unserialize($prodReturn['ordprodoptions']);
						foreach($variationInfo as $oName => $oValue) {
							$varCounter++;
							if ($varCounter == 1) {
								/* first one gets the weight and price*/
								$variationNode = $productNode->addChild('OrderOption');
								$variationNode->addChild('OptionName', $oName);
								$variationNode->addChild('SelectedOption', $oValue);
								$variationNode->addChild('OptionPrice', number_format($varprice,2));
								$variationNode->addChild('OptionWeight', number_format($varweight,2));
							} else {
								$variationNode = '';
								$variationNode = $productNode->addChild('OrderOption');
								$variationNode->addChild('OptionName', $oName);
								$variationNode->addChild('SelectedOption', $oValue);
								$variationNode->addChild('OptionPrice', number_format(0,2));
								$variationNode->addChild('OptionWeight', number_format(0,2));
							}
						}
					}
					$configurableCount = "SELECT COUNT(*) FROM [|PREFIX|]order_configurable_fields
											WHERE orderid = '" . $row['orderid'] . "'
											AND productid = '" . $prodReturn['ordprodid'] . "'";

					if($GLOBALS['ISC_CLASS_DB']->FetchOne($GLOBALS['ISC_CLASS_DB']->Query($configurableCount)) != 0){
						$configurableQuery = "SELECT * FROM [|PREFIX|]order_configurable_fields
												WHERE orderid = '" . $row['orderid'] . "'
												AND productid = '" . $prodReturn['ordprodid'] . "'";
						$fields = $GLOBALS['ISC_CLASS_DB']->Query($configurableQuery);
						/* build OrderOption XML for all configurable fields */
						while ($field = $GLOBALS['ISC_CLASS_DB']->Fetch($fields)) {
							$fieldFileType = $field['filetype'];
							$fieldName = $field['fieldname'];

							switch($fieldFileType) {
								case 'file': {
										$fieldValue = isc_html_escape($field['originalfilename']);
										break;
								}
								default: {
									if (isc_strlen($field['textcontents']) > 50) {
										$fieldValue = isc_html_escape(isc_substr($field['textcontents'], 0, 50))." ..";
									} else {
										$fieldValue = isc_html_escape($field['textcontents']);
									}
									break;
								}
							}
							$configurableNode = $productNode->addChild('OrderOption');
							$configurableNode->addChild('OptionName', $fieldName);
							$configurableNode->addChild('SelectedOption', $fieldValue);
						}
					}
				}
				$productExtraInfo = @unserialize($row['extrainfo']);

				if($row['orderpaymentmodule'] == 'checkout_creditcardmanually') {
					$paymentNode = $orderNode->addChild('Payment');
					$ccNode = $paymentNode->addChild('CreditCard');
						$ccNode->addChild('FullName', $productExtraInfo['cc_name']);
						$ccNode->addChild('Issuer', $productExtraInfo['cc_cctype']);
						$ccNode->addChild('ExpirationDate', $productExtraInfo['cc_ccexpm'].$productExtraInfo['cc_ccexpy']);
				} elseif ($row['orderpaymentmodule'] == 'checkout_cod') {
					$paymentNode = $orderNode->addChild('Payment');
					$paymentNode->addChild('COD'); /* has no children nodes */
				} else {

					$provider = null;
					if(GetModuleById('checkout', $provider, $row['orderpaymentmodule'])) {
						if(method_exists($provider, "GetName")) {
							$paymentNode = $orderNode->addChild('Payment');

							$specialGateways = array(
								'checkout_authorizenet',
								'checkout_paypalpaymentsprouk',
								'checkout_paypalpaymentsprous',
								'checkout_payflowpro',
							);

							if (in_array($row['orderpaymentmodule'],$specialGateways) && strtolower($row['ordpaymentstatus']) == 'authorized') {
								$creditcardNode = $paymentNode->addChild('CreditCard');
								$extraInfo = unserialize($row['extrainfo']);
								if(isset($extraInfo['cardtype']) && $extraInfo['cardtype'] != ''){
									$creditcardNode->addChild('Issuer', $extraInfo['cardtype']);
									$creditcardNode->addChild('TransID', $row['ordpayproviderid']);
									$creditcardNode->addChild('AuthCode', $row['ordpayproviderid']);
									$creditcardNode->addChild('Amount', number_format($OrderTotal,2));
								}
							}else{
								$genericNode = $paymentNode->addChild('Generic1');
								$genericNode->addChild('Name', 'Generic 1');
								$genericNode->addChild('Description', $provider->GetName());
							}
						}
					}
				}

				$totalNode = $orderNode->addChild('Totals');
				$totalNode->addChild('ProductTotal', number_format($OrderSubTotal,2));
				if(isset($OrderDiscounts) && $OrderDiscounts != ''){
					$discountNode = $totalNode->addChild('Discount');
					$discountNode->addChild('Amount', number_format($OrderDiscounts,2));
				}

				/* The Subtotal node below this comment should be subtotal - discounts but before adding in taxes, shipping, and handling. */
				$totalNode->addChild('SubTotal', number_format($SubTotalLessDiscounts,2));

				if ($OrderTaxTotal > $OrderSubTotal) {
					$taxNode = $totalNode->addChild('Tax');
					$taxNode->addChild('TaxAmount', number_format($TaxAmount,2));
				}

				$totalNode->addChild('GrandTotal', number_format($OrderTotal,2)); /* includes all shipping, handling, tax, and wrapping costs */
				if ($OrderHandlingCost > 0) {
					$surcharge = $totalNode->addChild('Surcharge');
					$surcharge->addChild('Total', number_format($OrderHandlingCost,2));
					$surcharge->addChild('Description', 'Handling Charge');
				}
				$shipTotal = $totalNode->addChild('ShippingTotal');
				$shipTotal->addChild('Total', number_format($OrderShippingCost,2));
				$shipTotal->addChild('Description', $OrderShippingMethod);

				$otherNode = $orderNode->addChild('Other');
				if ($OrderMessage != '') {
					$otherNode->addChild('OrderInstructions', $OrderMessage);
				}
				if ($OrderComments != '') {
					$otherNode->addChild('Comments', $OrderComments);
				}
				$otherNode->addChild('IpHostname', $OrderIP);

			}
		} else {
			//no results, give a response code of 2 to let SEOM know
			$responseNode = $xml->addChild('Response');
			$responseNode->addChild('ResponseCode', 2);
			$responseNode->addChild('ResponseDescription', 'Success');
		}

		return $xml->asXML();
	} //end function DownloadOrders
}
