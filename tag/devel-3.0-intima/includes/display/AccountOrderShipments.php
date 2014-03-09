<?php

class ISC_ACCOUNTORDERSHIPMENTS_PANEL extends PANEL
{
	public function setPanelSettings()
	{
		if (!isset($GLOBALS['OrderId']) || !isId($GLOBALS['OrderId'])) {
			$this->DontDisplay = true;
			return;
		}

		$orderId = $GLOBALS['OrderId'];

		// Fetch the shipments for the order (not bothering to select address details here since we're viewing in the context of the order where addresses should already show)
		$shipments = array();
		$query = "
			SELECT shipmentid, shipdate, shiptrackno, shipping_module, shipmethod, shipcomments, shipshipcountryid
			FROM [|PREFIX|]shipments
			WHERE shiporderid = " . $orderId . "
			ORDER BY shipdate, shipmentid
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($shipment = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$shipments[] = $shipment;
		}

		if (empty($shipments)) {
			$this->DontDisplay = true;
			return;
		}

		$GLOBALS['LNG_OrderShipments'] = GetLang('ShipmentsForOrder', array(
			'order' => $orderId,
		));

		$GLOBALS['SNIPPETS']['AccountOrderShipmentRow'] = '';

		foreach ($shipments as $shipment) {
			GetModuleById('shipping', /** @var ISC_SHIPPING */$module, $shipment['shipping_module']);

			$GLOBALS['DateShipped'] = isc_date(GetConfig('DisplayDateFormat'), $shipment['shipdate']);

			if ($module) {
				$GLOBALS['ShippingProvider'] = $module->GetName();
				$module->SetDestinationCountry($shipment['shipshipcountryid']);
			} else {
				$GLOBALS['ShippingProvider'] = $shipment['shipping_module'];
			}

			$GLOBALS['ShippingMethod'] = $shipment['shipmethod'];
			if (empty($GLOBALS['ShippingMethod']) || $GLOBALS['ShippingMethod'] == $GLOBALS['ShippingProvider']) {
				$GLOBALS['HideShippingMethod'] = 'display:none';
			} else {
				$GLOBALS['HideShippingMethod'] = '';
			}

			$GLOBALS['TrackingLink'] = isc_html_escape($shipment['shiptrackno']);
			if ($module) {
				$link = $module->GetTrackingLink($shipment['shiptrackno']);
				if ($link) {
					$GLOBALS['TrackingLink'] = '<a href="' . isc_html_escape($link) . '" target="_blank">' . $GLOBALS['TrackingLink'] . '</a>';
				}
			}

			$GLOBALS['SNIPPETS']['AccountOrderShipmentRow'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('AccountOrderShipmentRow');
		}
	}
}
