<?php
class ACCOUNTING_STONEEDGE_CUSTOMERS
{
	 /**
	 * Count the number of customers in the database
	 *
	 * Method will process the post data and create text for display. Used by SEOM to determine how many times to run DownloadCustomers()
	 *
	 * @access public
	 * @return string a response to the data to display on the page.
	 */

	public function ProcessCustomerCount()
	{
		$response = '';
		$count = StoneEdgeCount('customers','');
		$response = "SetiResponse: itemcount=$count";
		return $response;
	}

	/**
	 * Download customers from the database to SEOM
	 *
	 * Method will process the posted data and create XML to be displayed.
	 *
	 * @access public
	 * @return string XML response to display on the page for customers requested
	 */

	public function DownloadCustomers()
	{
		// set default queries
		$query = "SELECT * FROM [|PREFIX|]customers ORDER by customerid ASC";
		$queryCount = "SELECT COUNT(*) FROM [|PREFIX|]customers ORDER by customerid ASC";

		if (isset($_REQUEST['startnum']) && (int)$_REQUEST['startnum'] > 0 && isset($_REQUEST['batchsize']) && (int)$_REQUEST['batchsize'] > 0) {
			$start = (int)$_REQUEST['startnum'] - 1;
			$numresults = (int)$_REQUEST['batchsize'];

			if ($start >= 0 && $numresults > 0) {
				$query		.= ' LIMIT ' . $start . ', ' .$numresults;
				$queryCount .= ' LIMIT ' . $start . ', ' .$numresults;
			}
		}

		if ($GLOBALS['ISC_CLASS_DB']->FetchOne($queryCount) != 0) {
			//we have results, so build the response header
			$xml = new SimpleXMLElement('<?xml version="1.0"?><SETICustomers />');
			$responseNode = $xml->addChild('Response');
			$responseNode->addChild('ResponseCode', 1);
			$responseNode->addChild('ResponseDescription', 'Success');

			$customers = $GLOBALS['ISC_CLASS_DB']->Query($query);

			//build the content
			while($customer = $GLOBALS['ISC_CLASS_DB']->Fetch($customers)) {
				$customerNode = $xml->addChild('Customer');
				$customerNode->addChild('WebID', $customer['customerid']);
				$customerNode->addChild('UserName', $customer['custconemail']);
				$customerNode->addChild('WebID', $customer['customerid']);
				$BillingAddress = $customerNode->addChild('BillAddr');
				$BillingAddress->addChild('FirstName', $customer['custconfirstname']);
				$BillingAddress->addChild('LastName', $customer['custconlastname']);
				if (isset($customer['custconcompany']) && $customer['custconcompany'] != '') {
					$BillingAddress->addChild('Company', $customer['custconcompany']);
				}
				$BillingAddress->addChild('Phone', $customer['custconphone']);
				$BillingAddress->addChild('Email', $customer['custconemail']);

				$addressQuery = "SELECT a.*,c.countryiso2 FROM [|PREFIX|]shipping_addresses a
				JOIN [|PREFIX|]countries c ON a.shipcountryid = c.countryid
				WHERE shipcustomerid = '" . $customer['customerid'] . "'
				ORDER BY a.shipid DESC LIMIT 1";

				// could be more than one address per customer in the database but we can only display one, so we will display the last one added

				$addresses = $GLOBALS['ISC_CLASS_DB']->Query($addressQuery);
				$address = $GLOBALS['ISC_CLASS_DB']->Fetch($addresses);

				$RealBillingAddress = $BillingAddress->addChild('Address');
				$RealBillingAddress->addChild('Addr1', $address['shipaddress1']);
				$RealBillingAddress->addChild('Addr2', $address['shipaddress2']);
				$RealBillingAddress->addChild('City', $address['shipcity']);
				$RealBillingAddress->addChild('State', $address['shipstate']);
				$RealBillingAddress->addChild('Zip', $address['shipzip']);
				$RealBillingAddress->addChild('Country', $address['countryiso2']); //2 digit code

				$ShippingAddress = $customerNode->addChild('ShipAddr');
				$ShippingAddress->addChild('FirstName', $customer['custconfirstname']);
				$ShippingAddress->addChild('LastName', $customer['custconlastname']);
				if (isset($customer['custconcompany']) && $customer['custconcompany'] != '') {
					$ShippingAddress->addChild('Company', $customer['custconcompany']);
				}
				$ShippingAddress->addChild('Phone', $customer['custconphone']);
				$ShippingAddress->addChild('Email', $customer['custconemail']);
				$RealShippingAddress = $BillingAddress->addChild('Address');
				$RealShippingAddress->addChild('Addr1', $address['shipaddress1']);
				$RealShippingAddress->addChild('Addr2', $address['shipaddress2']);
				$RealShippingAddress->addChild('City', $address['shipcity']);
				$RealShippingAddress->addChild('State', $address['shipstate']);
				$RealShippingAddress->addChild('Zip', $address['shipzip']);
				$RealShippingAddress->addChild('Country', $address['countryiso2']); //2 digit code

				// are there any notes from the customer?
				if (isset($address['custnotes']) && $address['custnotes'] != '') {
					$CustomFields = $customerNode->addChild('CustomFields');
					$CustomField = $CustomFields->addChild('CustomField');
					$CustomField->addChild('FieldName', 'Customer Notes');
					$CustomField->addChild('FieldValue', substr($address['custnotes'],0,255));
				}
			}

		} else {
			//no results available
			$xml = new SimpleXMLElement('<?xml version="1.0"?><SETICustomers />');
			$responseNode = $xml->addChild('Response');
			$responseNode->addChild('ResponseCode', 2);
			$responseNode->addChild('ResponseDescription', 'Success');

		}

		return $xml->asXML();
	}
}
