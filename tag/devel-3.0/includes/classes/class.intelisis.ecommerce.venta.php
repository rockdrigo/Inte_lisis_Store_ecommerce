<?php

class ISC_INTELISIS_ECOMMERCE_VENTA extends ISC_INTELISIS_ECOMMERCE
{

	public function create() {
		print "ekrtgher";
		$orderId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT orderid FROM [|PREFIX|]intelisis_orders WHERE VentaID = "'.$this->getAttribute('ID').'"', 'orderid');
		if(!$orderId || trim($orderId) == ''){
			logAdd(LOG_SEVERITY_ERROR, 'Llego una calendarizacion de Entrega de la VentaID "'.$this->getAttribute('ID').'" pero no se pudo encontrar la orderid correspondiente. Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		$GLOBALS['OrderNumber'] = $orderId;
		$order = GetOrder($orderId);
		
		$customer = GetCustomer($order['ordcustid']);
		
		$email = $customer['custconemail'];
		$GLOBALS['ScheduledDate'] = $this->getData('FechaEntrega');
		
		$emailTemplate = FetchEmailTemplateParser();
		
		$emailTemplate->SetTemplate("order_scheduled");
		
		//Shipping address
		if ($order['shipping_address_count'] > 1) {
			// multiple shipping addresses
			$GLOBALS['ShippingAddress'] = GetLang('OrderWillBeShippedToMultipleAddresses');
		} else if ($order['shipping_address_count'] == 0) {
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
				oa.order_id = " . (int)$order['orderid'] . "
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
		
		$message = $emailTemplate->ParseTemplate(true);
		
		// Send Mail
		$store_name = GetConfig('StoreName');
	
		$obj_email = GetEmailClass();
		$obj_email->From(GetConfig('OrderEmail'), $store_name);
		$obj_email->Set("Subject", sprintf(GetLang('OrderDateScheduledMailSubject'), $orderId, $store_name));
		$obj_email->AddBody("html", $message);
		$obj_email->AddRecipient($email, "", "h");
		$email_result = $obj_email->Send();
		
		// If the email was sent ok, show a confirmation message
		if ($email_result['success']) {
			return true;
		}
		else {
			// Email error
			return false;
		}
	}
	
	public function update() {
		return $this->create();
	}
	
	public function delete() {
		logAdd(LOG_SEVERITY_WARNING, 'Llego un XML '.$this->getXMLfilename().' de Referencia Venta con Estatus BAJA O.o');
		return true;
	}
}
