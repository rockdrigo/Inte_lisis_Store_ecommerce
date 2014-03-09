<?php
	CLASS ISC_CHOOSEBILLINGADDRESS_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			$GLOBALS['SNIPPETS']['ShippingAddressList'] = "";
			$GLOBALS['ShippingAddressRow'] = "";
			$count = 0;

			$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');

			// Get a list of all shipping addresses for this customer and out them as radio buttons
			$shipping_addresses = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerShippingAddresses();

			foreach($shipping_addresses as $address) {
				$GLOBALS['ShippingAddressId'] = (int) $address['shipid'];
				$GLOBALS['ShipFullName'] = isc_html_escape($address['shipfirstname'].' '.$address['shiplastname']);

				$GLOBALS['ShipCompany'] = '';
				if($address['shipcompany']) {
					$GLOBALS['ShipCompany'] = isc_html_escape($address['shipcompany']).'<br />';
				}

				$GLOBALS['ShipAddressLine1'] = isc_html_escape($address['shipaddress1']);

				if($address['shipaddress2'] != "") {
					$GLOBALS['ShipAddressLine2'] = isc_html_escape($address['shipaddress2']);
				} else {
					$GLOBALS['ShipAddressLine2'] = '';
				}

				$GLOBALS['ShipSuburb'] = isc_html_escape($address['shipcity']);
				$GLOBALS['ShipState'] = isc_html_escape($address['shipstate']);
				$GLOBALS['ShipZip'] = isc_html_escape($address['shipzip']);
				$GLOBALS['ShipCountry'] = isc_html_escape($address['shipcountry']);

				if($address['shipphone'] != "") {
					$GLOBALS['ShipPhone'] = isc_html_escape(sprintf("%s: %s", GetLang('Phone'), $address['shipphone']));
				}
				else {
					$GLOBALS['ShipPhone'] = "";
				}

				$GLOBALS['SNIPPETS']['ShippingAddressList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CheckoutShippingAddressItem");
			}
		}
	}