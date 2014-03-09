<?php

class ISC_INTELISIS_WS_ORDER extends ISC_INTELISIS_WS {

	private $orderid = 0;
	private $order = NULL;
	private $orderShippingAddresses = array();
	private $GUID = '';

	// NES: "IMPORTANTE" Las solicitudes de Pedido siempre son accion=ALTA ya que solo damos de alta el pedido. Por le momento no enviamos cambios en el pedido una vez ya capturado
	// Aun asi, se crea una solicitud IWS desde ISC_ENTITY_ORDER->EditPostHook solo para cuando el Estatus de la Order cambia de INCOMPLETE a otra cosa,
	// ya que no se envio la solicitud en addPostHook cuando el status original era INCOMPLETE debido a un Payment (Checkout) Gateway Module
	public function __construct($orderid) {
		parent::__construct();

		$this->orderid = $orderid;
		$this->useDropbox = true;
		$this->GUID = gen_uuid();

		/* NES
		 * Mando HardReset (3er parametro) porque los orderproductid se comportan raro cuando los traemos de cache
		 */
		if(!$this->order = getOrder($orderid, true, true))
		{
			logAddError('No se pudo cargar la orderid "'.$orderid.'" para transmitir a IntelisisWebService');
			$this->setError('Ocurrio un error al cargar su pedido en nuestro sistema [1]. Favor de contactar al administrador del sistema con el ID de pedido "'.$orderid.'"');
			return false;
		}
		/*
		 * NES: REQ12891 - Obtengo los campos configurables de los productos
		 */
		$orderFields = GetOrderProductFieldsData($orderid);

		if(($Cliente = $this->getCliente()) == ''){
			logAddWarning('No se encontro el ID de WebUsuario del customer id "'.$Cliente.'"');
			$this->setError('Ocurrio un error al cargar su pedido en nuestro sistema [2]. Favor de contactar al administrador del sistema con el ID de pedido "'.$orderid.'"');
		}
		//$discount = $this->order['coupon_discount'] >= 0 ? ($this->order['coupon_discount'] / $this->order['subtotal_inc_tax']) * 100 : 0;

		/*
		 NES BUGS 13066, 13102, 13154, 13155, 13148 de cupones
		 Arreglo esto para poner descuentos globales, por producto, o al envío.
		 */
		$discount_result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]order_coupons WHERE ordcouporderid = '".$orderid."'");
		$order_discount = $GLOBALS['ISC_CLASS_DB']->Fetch($discount_result);

		$shippingCost = $_SESSION['QUOTE']->getNonDiscountedShippingCost(true);

		$discount = 0;
		$discount_product = 0;
		$discount_product_percent = 0;
		$discount_shipping = 0;

		switch($order_discount['ordcoupontype']){
			case '0':
				$discount_product = $order_discount['ordcouponamount'];
				break;
			case '1':
				$discount_product_percent = $order_discount['ordcouponamount'];
				break;
			case '2':
				$discount = ($order_discount['ordcouponamount'] * 100) / $this->order['subtotal_inc_tax'];
				break;
			case '3':
				if($shippingCost == 0) $discount_shipping = 100;
				 else $discount_shipping = ($order_discount['ordcouponamount'] * 100) / $shippingCost;
				break;
			case '4':
				 $discount_shipping = 100;
				break;
		}

		$customerData = $this->getCustomer();

		$customFieldsAccount = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, false, $customerData['custformsessionid']);

		$RFC = getCustomFieldByLabel($customFieldsAccount, FORMFIELDS_FORM_ACCOUNT, 'RFC');

		$extrafields = '';
		$invoice = '';
		for($i=1;$i<=5;$i++){
			if(GetConfig('CheckoutExtraFieldActive'.$i)) {
				//ToDo: Probar bien este parseo para evitar XML mal formados, dependiendo de nombres de campos extras
				$fieldName = preg_replace("/[^A-Za-z0-9]|[\s]/", '', GetConfig('CheckoutExtraFieldName'.$i));
				$fieldValue = $this->order['extraField'.$i];

				if($fieldName == 'RequiereFactura'){
					if(strtoupper($fieldValue) == 'ON'){
						if(trim($RFC) == '') {
							logAddError('Se intento hacer el orderid "'.$orderid.'" que requiere factura pero el RFC esta vacio');
							return false;
						}
						else {
							$invoice = 'SI';
						}
					}
					else {
						$invoice = 'NO';
					}
				}

				$extrafields .= " ".$fieldName."='".$fieldValue."'";
			}
		}

		$deliveryDates = array();
		foreach($this->order['products'] as $product)
		{
			/*
		  NES BUGS 13066, 13102, 13154, 13155, 13148 de cupones
		  Aumento el descuento en linea. Convierto los descuentos en cantidad a porcentaje.
		  */
			$this_product_discount = 0;

			if($discount_product > 0) {
				$this_product_discount = ($discount_product * 100) / $product['price_inc_tax'];
			}
			elseif($discount_product_percent > 0){
				$this_product_discount = $discount_product_percent;
			}

			/* 
			 * NES: REQ12891 - Agrego esto para poner el nombre y valor del campo confiugrable, para luego agregarselo al XML
			 * Si el producto no tiene campos confiugrables, el arreglo se deja vacio
			 */
			$productFields = array();
			if(isset($orderFields[$product['orderprodid']])){
				foreach($orderFields[$product['orderprodid']] as $key => $field){
					if($field['fieldtype'] == 'checkbox') {
						if(in_array(strtoupper($field['textcontents']), array('ON', 'CHECKED'))) $field['textcontents'] = 'Si';
						else $field['textcontents'] = 'No';
					}
					
					$IDCampo = $this->getIDCampo($field['fieldid']);
					
					$productFields[] = array(
						'Nombre' => $field['fieldname'],
						//'Valor' => str_replace(array('"'), array("'"), $field['textcontents']),
						'Valor' => stripInvalidXml($field['textcontents']),
						'IDCampo' => $IDCampo,
					);
				}
			}
			
			if($product['ordprodwrapid'] > 0){
				$productFields[] = array(
					'Nombre' => 'Envoltura Regalo #'.$GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT IDEnvoltura FROM [|PREFIX|]intelisis_gift_wrapping WHERE wrapid = "'.$product['ordprodwrapid'].'"', 'IDEnvoltura'),
					//'Valor' => str_replace(array('"'), array("'"), $field['textcontents']),
					'Valor' => stripInvalidXml($product['ordprodwrapname'].' - '.$product['ordprodwrapmessage']),
					'IDCampo' => '',
				);
			}


			$pedidoD[] = array(
				'ID' => $product['orderprodid'],
				'Cantidad' => $product['ordprodqty'],
				'SKU' => $product['ordprodsku'],
				'Precio' => $product['price_inc_tax'],
				'DescuentoLinea' => $this_product_discount,
				// NES: REQ12891 - Agrego el arreglo de campos configurables
				'CamposConfigurables' => $productFields,
			);

			if($date = $this->getDeliveryDateFromStatus($product)){
				$deliveryDates[] = $date;
			}
		}

		if(!empty($deliveryDates)){
			rsort($deliveryDates);
			$maxdate = date('Y-m-d', $deliveryDates[0]);
		}
		else {
			$maxdate = '';
		}

		if(empty($pedidoD))
		{
			logAddWarning('El orderid "'.$this->orderid.'" no tiene productos para transmitir a Intelisis');
			$this->setError('Ocurrio un error al procesar el XML de su pedido. Favor de contactar al administrador del sistema con el ID de pedido "'.$this->orderid.'" [2]');
			return false;
		}

		$xml = "<?xml version='1.0' encoding='windows-1252'?><Intelisis Sistema='Intelisis' Contenido='Solicitud' Referencia='Intelisis.eCommerce.Pedido' SubReferencia='".$this->GUID."' Version='1.0'><Solicitud Sucursal='".GetConfig('syncIWSintelisissucursal')."' Estatus='ALTA' Empresa='".$this->empresa."'><Pedido Empresa='".$this->empresa."' Cliente='".$Cliente."' Moneda='Pesos' TipoCambio='1'  Importe='".$this->order['total_ex_tax']."' Impuestos='".$this->order['total_tax']."' Sucursal='".GetConfig('syncIWSintelisissucursal')."' Observaciones='".$this->order['ordcustmessage']."' Referencia='Facturar:".$invoice." ID#".$orderid."' DescuentoGlobal='".$discount."' CostoFlete='".$shippingCost."' DescuentoFlete = '".$discount_shipping."' FormaPago='".$this->order['orderpaymentmodule']."' ReferenciaFormaPago='".$this->order['ordpayproviderid']."' ".$extrafields." FechaEntrega='".$maxdate."' PedidoID='".$orderid."' >";

		foreach($pedidoD as $D) {
			$line = "<PedidoD";
			foreach ($D as $attribute => $value) {
				/* NES: REQ12891 - Agrego validacion de is_array para no meter el arreglo de campos configurables
				*  asi como cualquier otro arreglo que le podamos agregar al arreglo de productos
				* */
				if(!is_array($value)) $line .= ' '.$attribute.'="'.$value.'"';
			}
			$line .= ">";

			/*
			 * NES: REQ12891 - Ahora si, agrego el XML de los campos configurables si no viene vacio
			 */
			if(!empty($D['CamposConfigurables'])){
				$line .= '<CamposConfigurables>';
				foreach($D['CamposConfigurables'] as $key => $field) {
					$line .= '<CampoConfigurable IDCampo="'.$field['IDCampo'].'" Campo="'.$field['Nombre'].'" Valor="'.$field['Valor'].'" />';
				}
				$line .= '</CamposConfigurables>';
			}

			$line .= "</PedidoD>";
			$xml .= $line;
		}

		$customFieldsBillingAddress = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_BILLING, false, $this->order['ordformsessionid']);
		foreach($customFieldsBillingAddress as $fieldid => $field){
			if($field->record['formfieldisimmutable'] > 0)
				unset($customFieldsBillingAddress[$fieldid]);
		}
		$customFieldsBillingXML = getCustomFieldsAsXMLAttributes($customFieldsBillingAddress);
		/*
		$Colonia = getCustomFieldByLabel($customFieldsBillingAddress, FORMFIELDS_FORM_BILLING, 'Colonia');
		$Delegacion = getCustomFieldByLabel($customFieldsBillingAddress, FORMFIELDS_FORM_BILLING, 'Delegacion');
		$NoExt = getCustomFieldByLabel($customFieldsBillingAddress, FORMFIELDS_FORM_BILLING, 'Numero Exterior');
		$Vivienda = getCustomFieldByLabel($customFieldsBillingAddress, FORMFIELDS_FORM_BILLING, 'Vivienda');
		*/

		$billingAddress = '<DireccionFactura Nombre="'.$this->order['ordbillfirstname'].'" Apellido="'.$this->order['ordbilllastname'].'" Direccion1="'.$this->order['ordbillstreet1'].'" Direccion2="'.$this->order['ordbillstreet2'].'" Ciudad="'.$this->order['ordbillsuburb'].'" Pais="'.$this->order['ordbillcountry'].'" Estado="'.$this->order['ordbillstate'].'" CP="'.$this->order['ordbillzip'].'" eMail="'.$this->order['ordbillemail'].'" Telefono="'.$this->order['ordbillphone'].'" '.$customFieldsBillingXML.' />';

		$shippingAddress = "";
		$cost = 0;
		// ToDo: Sacar las addresses de un lugar que no dependa de la variable de sesion
		foreach($_SESSION['QUOTE']->getAllAddresses() as $address) {
			//logAddNotice('add "'.$address->getId().'" g "'.$address->getGUID().'" type "'.$address->getType().'"');
			if($address->getType() == ISC_QUOTE_ADDRESS::TYPE_SHIPPING) {
				$cost += $address->getNonDiscountedBaseShippingCost();
				$GUID = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT GUID FROM [|PREFIX|]intelisis_shipping_addresses WHERE shipid = "'.$address->getCustomerAddressId().'"', 'GUID');
				if(!$GUID){
					$GUID = $address->getGUID();
					//logAddNotice('No hubo GUID. "'.$GUID.'"');
				}
				if(!$GUID) {
					logAddNotice('No hay GUID para la direccion de envío del pedido '.$this->orderid);
				}
				$customFieldsShippingAddress = $address->getCustomFields();
				$customFieldsShippingXML = '';
				foreach($customFieldsShippingAddress as $fieldid => $value){
					$field = getCustomFieldIdById($fieldid);
					$label = $field['formfieldlabel'];
					$label = iconv('UTF-8','ASCII//TRANSLIT',$label);
					$label = preg_replace("/[^A-Za-z0-9]|[\s]/", '', $label);
					if(is_array($value)){
						$value = implode(',', $value);
					}
					$customFieldsShippingXML .= $label . '="'.$value.'" ';
				}

				/*
				$NoExtEnvio = $address->getCustomField(getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Numero Exterior'));
				$ColoniaEnvio = $address->getCustomField(getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Colonia'));
				$DelegacionEnvio = $address->getCustomField(getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Delegacion'));
				$ViviendaEnvio = $address->getCustomField(getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Vivienda'));
				*/
				$shippingAddress .= '<DireccionEnvio GUID="'.$GUID.'" Nombre="'.$address->getFirstName().'" Apellido="'.$address->getLastName().'" Direccion1="'.$address->getAddress1().'" Direccion2="'.$address->getAddress2().'" Ciudad="'.$address->getCity().'" Pais="'.$address->getCountryName().'" Estado="'.$address->getStateName().'" CP="'.$address->getZip().'" eMail="'.$address->getEmail().'" Telefono="'.$address->getPhone().'" '.$customFieldsShippingXML.' />';
				//logAddSuccess('sefsefswef.'.$shippingAddress.$IDEnviarA.$address->getCustomerAddressId());
			}
		}
		
		foreach($customFieldsAccount as $fieldid => $field){
			if($field->record['formfieldisimmutable'] > 0)
				unset($customFieldsAccount[$fieldid]);
		}
		$customFieldsAccountXML = getCustomFieldsAsXMLAttributes($customFieldsAccount);

		$customer = '<Usuario GUID="'.$customerData['GUID'].'" Nombre="'.$customerData['custconfirstname'].'" Apellidos="'.$customerData['custconlastname'].'" eMail= "'.$customerData['custconemail'].'" Contrasena="'.$customerData['custpassword'].'" Telefono="'.$customerData['custconphone']./*'" RFC="'.$RFC.*/'" Compania="'.$customerData['custconcompany'].'" '.$customFieldsAccountXML.'/>';

		$xml .= $billingAddress.$shippingAddress.$customer. "</Pedido></Solicitud></Intelisis>";

		$this->xml = $xml;
	}
	
	private function getIDCampo($fieldId) {
		$query = "SELECT IDCampo FROM [|PREFIX|]intelisis_configurable_fields WHERE productfieldid = '".$fieldId."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	
		return $row['IDCampo'] ? $row['IDCampo'] : false;
	}

	private function getDeliveryDateFromStatus($product) {
		if($product['ordprodvariationid'] == 0) {
			$Situacion = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT Situacion FROM [|PREFIX|]intelisis_products WHERE productid = "'.$product['ordprodid'].'"', 'Situacion');
		}
		else {
			$Situacion = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT Situacion FROM [|PREFIX|]intelisis_variation_combinations WHERE combinationid = "'.$product['ordprodvariationid'].'"', 'Situacion');
		}
		if(!$Situacion || $Situacion == ''){
			return false;
		}

		$result = $GLOBALS['ISC_CLASS_DB']->Query('SELECT DiasEntrega, PeriodoEntrega FROM [|PREFIX|]intelisis_prodstatus WHERE Situacion = "'.$Situacion.'"');
		if(!$result){
			return false;
		}
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		if($row['DiasEntrega'] == '' || $row['PeriodoEntrega'] == ''){
			return false;
		}

		$date = getDeliveryDate($row['DiasEntrega'], $row['PeriodoEntrega']);
		return $date;
	}

	private function getCustomer() {
		$query = 'SELECT c.*, ic.GUID
					FROM [|PREFIX|]customers c
					JOIN [|PREFIX|]orders o ON (o.ordcustid=c.customerid)
					JOIN [|PREFIX|]intelisis_customers ic ON (c.customerid=ic.customerid)
					WHERE o.orderid = "'.$this->orderid.'"';
		return $GLOBALS['ISC_CLASS_DB']->Fetch($GLOBALS['ISC_CLASS_DB']->Query($query));
	}

	private function getCliente() {
		$query = "SELECT IDWebUsuario FROM [|PREFIX|]intelisis_customers WHERE customerid= '".$this->order['ordcustid']."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		return $row['IDWebUsuario'] ? $row['IDWebUsuario'] : false;
	}

	public function handleIWSResult() {

		if($this->getOk() == 0)
		{
			if(!$resultado = $this->getIWSResult()){
				return false;
			}

			if($resultado['ModuloID'] == ''){
				logAddError('No se encontro el ID de Venta generado en el Resultado de IWS');
				return false;
			}

			$insert = array(
				'GUID' => $this->GUID,
				'orderid' => $this->orderid,
				'VentaID' => isset($resultado['ModuloID']) ? $resultado['ModuloID'] : '',
			);
			$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_orders', $insert);
			logAddSuccess('Se transmitio el pedido "'.$this->orderid.'" a Intelisis');
			return true;
		}
		else
		{
			logAddError('Se encontro un error al procesar el pedido "'.$this->orderid.'" en Intelisis. OK="'.$this->getOk().'" OkRef="'.$this->getOkRef().'" IS-ID="'.$this->getID().'" Resultado="<pre>'.$this->getResultado().'</pre>"');
			return false;
		}
	}

	public function handleDropboxResult() {
		$insert = array(
			'GUID' => $this->GUID,
			'orderid' => $this->orderid,
			'VentaID' => 0,
		);
		if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_orders', $insert))
		{
			logAddError('Error al registrar la orderid "'.$this->orderid.'" con GUID "'.$this->GUID.'".<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
		else {
			return true;
		}
	}
}