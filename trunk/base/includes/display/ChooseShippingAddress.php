<?php

	CLASS ISC_CHOOSESHIPPINGADDRESS_PANEL extends PANEL
	{
		/**
		 * Set the settings for this panel.
		 */
		public function SetPanelSettings()
		{

			$GLOBALS['HideTabMultiple'] = 'display: none';
			$GLOBALS['ActiveTabSingle'] = 'Active';

			$GLOBALS['SNIPPETS']['ShippingAddressList'] = "";
			$GLOBALS['ShippingAddressRow'] = "";
			$count = 0;

			$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');


			$numItems = getCustomerQuote()->getNumPhysicalItems();

			// Get a list of all shipping addresses for this customer and out them as radio buttons
			$shipping_addresses = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerShippingAddresses();

			if(empty($shipping_addresses) && isset($GLOBALS['CheckoutShippingIntroNoAddresses'])) {
				$GLOBALS['CheckoutShippingIntro'] = $GLOBALS['CheckoutShippingIntroNoAddresses'];
			}

			$GLOBALS['SplitAddressList'] = '';
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

				$splitAddressFields = array(
					$address['shipfirstname'].' '.$address['shiplastname'],
					$address['shipcompany'],
					$address['shipaddress1'],
					$address['shipaddress2'],
					$address['shipcity'],
					$address['shipstate'],
					$address['shipzip'],
					$address['shipcountry']
				);

				// Please see self::GenerateShippingSelect below.
				$splitAddressFields = array_filter($splitAddressFields, array($this, 'FilterAddressFields'));
				$splitAddress = isc_html_escape(implode(', ', $splitAddressFields));
				$GLOBALS['SplitAddressList'] .= '<option value="'.$address['shipid'].'" <sel'.$address['shipid'].'>>'.$splitAddress.'</option>';

				$GLOBALS['SNIPPETS']['ShippingAddressList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("CheckoutShippingAddressItem");
			}

			$GLOBALS['SNIPPETS']['MultiShippingItem'] = '';
			if(!gzte11(ISC_MEDIUMPRINT) || !GetConfig('MultipleShippingAddresses') || !CustomerIsSignedIn() || $numItems == 1 || !isset($GLOBALS['ISC_CLASS_CHECKOUT'])) {
				$GLOBALS['HideShippingTabs'] = 'display: none';
				$GLOBALS['HideMultiShipping'] = 'display: none';
			}
			else {
				if((isset($_REQUEST['type']) && $_REQUEST['type'] == 'multiple') || getCustomerQuote()->getIsSplitShipping() && CustomerIsSignedIn()) {
					$GLOBALS['HideTabSingle'] = 'display: none';
					$GLOBALS['HideTabMultiple'] = '';
					$GLOBALS['ActiveTabSingle'] = '';
					$GLOBALS['ActiveTabMultiple'] = 'Active';
				}

				$items = getCustomerQuote()->getItems();
				foreach($items as $item) {
					// If this isn't a physical item, skip it
					if($item->getType() != PT_PHYSICAL) {
						continue;
					}
					$GLOBALS['ProductName'] = isc_html_escape($item->getName());

					// Is this product a variation?
					$GLOBALS['ProductOptions'] = '';
					$options = $item->getVariationOptions();
					if(!empty($options)) {
						$GLOBALS['ProductOptions'] .= "<br /><small>(";
						$comma = '';
						foreach($options as $name => $value) {
							if(!trim($name) || !trim($value)) {
								continue;
							}
							$GLOBALS['ProductOptions'] .= $comma.isc_html_escape($name).": ".isc_html_escape($value);
							$comma = ', ';
						}
						$GLOBALS['ProductOptions'] .= ")</small>";
					}


					// Loop through the cart items and add them individually to the list
					$quantity = $item->getQuantity();
					for($i = 1; $i <= $quantity; ++$i) {
						$GLOBALS['AddressFieldId'] = $item->getId().'_'.$i;
						$sel = $item->getAddressId();
						$GLOBALS['ShippingAddressSelect'] = $this->GenerateShippingSelect($GLOBALS['SplitAddressList'], $sel);
						$GLOBALS['SNIPPETS']['MultiShippingItem'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('MultiShippingItem');
					}
				}
			}
		}

		/**
		 * Build the shipping address selection box from the string of addresses, optionally
		 * selecting a specific address. The incoming list contains special <sel[id]> markers
		 * to indicate each row and where to put the selected="selected" option. Having these indicators
		 * in the string seems to be a lot faster (for the possible number of loops it could do with many
		 * items in the cart) than manually looping and building the list.
		 *
		 * @param string The list of addresses.
		 * @param int Optionally the ID of the selected address.
		 * @return string The generated address list.
		 */
		private function GenerateShippingSelect($list, $selected=0)
		{
			$list = str_replace('<sel'.$selected.'>', 'selected="selected"', $list);
			$list = preg_replace('#<sel[0-9]+>#', '', $list);
			return $list;
		}

		/**
		 * Filter a field and if it's empty, return false. Used in an array_filter in SetPanelSettings()
		 *
		 * @param string The field value.
		 * @return boolean False if the field is empty.
		 * @see SetPanelSettings
		 */
		private function FilterAddressFields($field)
		{
			if(!$field) {
				return false;
			}
			else {
				return true;
			}
		}
	}