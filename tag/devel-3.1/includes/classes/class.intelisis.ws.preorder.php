<?php

	class ISC_INTELISIS_WS_PREORDER extends ISC_INTELISIS_WS {
		
		private $GUID = '';
		private $orderShippingAddresses = array();
		
		public function __construct() {
			parent::__construct();
			
			$this->useDropbox = false;
			$this->GUID = gen_uuid();
			$quote = $_SESSION['QUOTE'];
			
			if(!$quote){
				logAddError('No se pudo cargar la sesion');
				return false;
			}
			
			$Cliente = getClass('ISC_CUSTOMER')->getCustomerId();
			if($Cliente == ''){
				logAddError('No se encontro el ID de WebUsuario del customer id "'.$Cliente.'"');
				$this->setError('Ocurrio un error al cargar su pedido en nuestro sistema [2]. Favor de contactar al administrador del sistema');
			}
			
			$query = 'SELECT GUID FROM [|PREFIX|]intelisis_customers WHERE customerid = "'.$Cliente.'"';
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$GUIDUsuario = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
			
			$customerData = $this->getCustomer($GUIDUsuario);
			$customFieldsAccount = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, false, $customerData['custformsessionid']);
			$RFC = getCustomFieldByLabel($customFieldsAccount, FORMFIELDS_FORM_ACCOUNT, 'RFC');
			
			
			$items = $_SESSION['QUOTE']->getItems();
			$consecutivo = 1;
			foreach($items as $product)
			{
				$productFields = array();
				$productConfig = $product->getConfiguration();
				
				if(isset($productConfig)){
					foreach($productConfig as $key => $field){
						$productFields[] = array(
							'IDCampo' => $key,
							'Nombre' => $field['name'],
							'Valor' => $field['value'],
						);
					}
				}

				$PedidoD[] = array(
					'ID' => $consecutivo,
					'Cantidad' => $product->getQuantity(),
					'SKU' => $product->getSku(),
					'Precio' => $product->getBasePrice(),
					'DescuentoLinea' => '0',
					'CamposConfigurables' => $productFields,
				);
				$consecutivo++;
			}
			
			
			if(empty($PedidoD))
			{
				logAddWarning('La pre-orden no tiene productos para transmitir a Intelisis');
				return false;
			}
			
			$empresa = $this->empresa;
			$importe = $quote->getGrandTotal();
			$impuestos = $quote->getTaxTotal();
			$discount = $quote->getDiscountAmount();
			$shippingCost = $quote->getShippingCost();
			
			$xml = '<?xml version="1.0" encoding="windows-1252"?>';
			$xml .= '<Intelisis Sistema="Intelisis" Contenido="Solicitud" Referencia="eCommerce.Intelisis.PrePedido" Subreferencia="'.$this->GUID.'" Version="1.0">';
			$xml .= '<Solicitud Sucursal="'.GetConfig('syncIWSintelisissucursal').'" Estatus="ALTA" Empresa="'.$this->empresa.'">';
			$xml .= '<PrePedido Empresa="'.$this->empresa.'" Cliente="'.$Cliente.'" Moneda="Pesos" TipoCambio="1" Importe="'.$importe.'" Impuestos="'.$impuestos.'" Sucursal="'.GetConfig('syncIWSintelisissucursal').'" Observaciones="" Referencia="" DescuentoGlobal="'.$discount.'" CostoFlete="'.$shippingCost.'" FormaPago="" ReferenciaPago="" FechaEntrega="" >';

			foreach($PedidoD as $D){
				$line = "<PedidoD";
				foreach($D as $attribute => $value){
					if(!is_array($value)) $line .= ' '.$attribute.'="'.$value.'"';
				}
				$line .= ">";
				if(!empty($D['CamposConfigurables'])){
					$line .= '<CamposConfigurables>';
					foreach($D['CamposConfigurables'] as $key => $field){
						if(trim($field['Valor']) != ''){
							$line .= '<CampoConfigurable IDCampo="'.$field['IDCampo'].'" Campo="'.$field['Nombre'].'" Valor="'.$field['Valor'].'" />';
						}
					}
					$line .= '</CamposConfigurables>';
				}
				$line .= "</PedidoD>";
				$xml .= $line;
			}
			

			$billingAddress = '';
			$shippingAddress = "";
			foreach($_SESSION['QUOTE']->getAllAddresses() as $address){
				if($address->getType() == ISC_QUOTE_ADDRESS::TYPE_SHIPPING){
					$GUID = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT GUID FROM [|PREFIX|]intelisis_shipping_addresses WHERE shipid = "'.$address->getCustomerAddressId().'"', 'GUID');
					if($GUID == '') $GUID = gen_uuid();
					$NoExtEnvio = $address->getCustomField(getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Numero Exterior'));
					$ColoniaEnvio = $address->getCustomField(getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Colonia'));
					$DelegacionEnvio = $address->getCustomField(getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Delegacion'));
					$ViviendaEnvio = $address->getCustomField(getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Vivienda'));
					$shippingAddress .= '<DireccionEnvio GUID="'.$GUID.'" Nombre="'.$address->getFirstName().'" Apellido="'.$address->getLastName().'" Direccion1="'.$address->getAddress1().'" NumeroExterior="'.$NoExtEnvio.'" Direccion2="'.$address->getAddress2().'" Colonia="'.$ColoniaEnvio.'" Delegacion="'.$DelegacionEnvio.'" Ciudad="'.$address->getCity().'" Pais="'.$address->getCountryId().'" Estado="'.$address->getStateId().'" CP="'.$address->getZip().'" eMail="'.$address->getEmail().'" Telefono="'.$address->getPhone().'" Vivienda="'.$ViviendaEnvio.'" />';
				}
				if($address->getType() == ISC_QUOTE_ADDRESS::TYPE_BILLING){
					$NoExt = $address->getCustomField(getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Numero Exterior'));
					$Colonia = $address->getCustomField(getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Colonia'));
					$Delegacion = $address->getCustomField(getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Delegacion'));
					$Vivienda = $address->getCustomField(getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Vivienda'));
					$billingAddress = '<DireccionFactura Nombre="'.$address->getFirstName().'" Apellido="'.$address->getLastName().'" Direccion1="'.$address->getAddress1().'" NumeroExterior="'.$NoExt.'" Direccion2="'.$address->getAddress2().'" Colonia="'.$Colonia.'" Delegacion="'.$Delegacion.'" Ciudad="'.$address->getCity().'" Pais="'.$address->getCountryId().'" Estado="'.$address->getStateId().'" CP="'.$address->getZip().'" eMail="'.$address->getEmail().'" Telefono="'.$address->getPhone().'" Vivienda="'.$Vivienda.'" />';
			
				}
			}
			
			$customer = '<Usuario GUID="'.$GUIDUsuario.'" Nombre="'.$customerData['custconfirstname'].'" Apellido="'.$customerData['custconlastname'].'" eMail="'.$customerData['custconemail'].'" Contrasena="'.$customerData['custpassword'].'" Telefono="'.$customerData['custconphone'].'" RFC="'.$RFC.'" Compania="'.$customerData['custconcompany'].'" />';
			
			
			
			$xml .= $billingAddress.$shippingAddress.$customer."</PrePedido></Solicitud></Intelisis>";
			$this->xml = $xml;

			//$GLOBALS['ISC_CLASS_DB']->InsertQuery('sincronizacion', array('xml' => $xml, 'estatus' => '1'));
		}

	private function getCustomer($GUIDUsuario){
		$query = 'SELECT c.*, ic.GUID
					FROM [|PREFIX|]customers c
					JOIN [|PREFIX|]intelisis_customers ic ON (c.customerid=ic.customerid)
					WHERE ic.GUID = "'.$GUIDUsuario.'"';
		return $GLOBALS['ISC_CLASS_DB']->Fetch($GLOBALS['ISC_CLASS_DB']->Query($query));
	}
	
	public function handleIWSResult() {
		if($this->getOk() == 0)
		{
			//$GLOBALS['ISC_CLASS_DB']->InsertQuery('sincronizacion', array('xml' => $this->getResultado(), 'estatus' => '2'));
			if(!$resultado = $this->getIWSResult()){
				return false;
			}

			if($resultado['AplicoOfertas'] == '1'){
				$combination = false;
				$GLOBALS['AppliedOffers'] = true;
				
				foreach ($_SESSION['QUOTE']->getItems() as $key => $item) {
					$itemId = $item->getId();
					$_SESSION['QUOTE']->removeItem($itemId);
				}
				
				foreach($resultado['Productos'] as $key => $result){
					$Product = $result['Producto'];
					$ProductFields = $result['CamposConfigurables'];
					
					$ProdSKU = $Product['SKU'];
					if(substr($ProdSKU, 0, 3) == 'ID#'){
						$combination = false;
						$ProdSKUid = substr($ProdSKU, 3);
					}elseif(substr($ProdSKU, 0, 5) == 'IDCO#'){
						$combination = true;
						$ProdSKUid = substr($ProdSKU, 5);
					}else{
						logAddNotice('No se encontro el ID del producto del SKU '.$ProdSKU);
						return false;
					}
					
					if($combination == false){
						$query = 'select productid from [|PREFIX|]intelisis_products where ArticuloID =  "'.$ProdSKUid.'"';
						$resultDB = $GLOBALS['ISC_CLASS_DB']->Query($query);
						$ProdId = $GLOBALS['ISC_CLASS_DB']->FetchOne($resultDB);
						if(!$ProdId){
							$query = 'select productid from [|PREFIX|]products where prodcode =  "'.$ProdSKU.'"';
							$resultDB = $GLOBALS['ISC_CLASS_DB']->Query($query);
							$ProdId = $GLOBALS['ISC_CLASS_DB']->FetchOne($resultDB);
							if(!$ProdId){
								logAddError('No se encontro el articulo con el SKU '.$ProdSKU);
								return false;
							}
						}
					}else{
						$query = 'select combinationid from [|PREFIX|]intelisis_variation_combinations where IDCombinacion =  "'.$ProdSKUid.'"';
						$resultDB = $GLOBALS['ISC_CLASS_DB']->Query($query);
						$ProdId = $GLOBALS['ISC_CLASS_DB']->FetchOne($resultDB);
						if(!$ProdId){
							$query = 'select combinationid from [|PREFIX|]product_variation_combinations where vcsku =  "'.$ProdSKU.'"';
							$resultDB = $GLOBALS['ISC_CLASS_DB']->Query($query);
							$ProdId = $GLOBALS['ISC_CLASS_DB']->FetchOne($resultDB);
							if(!$ProdId){
								logAddError('No se encontro el articulo con el SKU '.$ProdSKU);
								return false;
							}
						}
						$query = 'select vcproductid from [|PREFIX|]product_variation_combinations where combinationid = "'.$ProdId.'"';
						$resultDB = $GLOBALS['ISC_CLASS_DB']->Query($query);
						$vcprodid = $GLOBALS['ISC_CLASS_DB']->FetchOne($resultDB);
					}
					
					$ProdPrice = $Product['Precio'];
					$ProdQty = $Product['Cantidad'];
					$ProdDisc = $Product['DescuentoLinea'];
					if($ProdDisc > 0){
						$ProdPrice -= ($ProdPrice*($ProdDisc/100));
					}
					//logAddNotice('sku = '.$ProdSKU.' id = '.$ProdId.' price = '.$ProdPrice.' Qty = '.$ProdQty.' disc = '.$ProdDisc);
					//logAddNotice('campso = '.serialize($ProductFields));
					$item = new ISC_QUOTE_ITEM;
					$item
						->setBasePrice($ProdPrice, true)
						->setProductId($ProdId)
						->setQuantity($ProdQty)
						->setQuote($_SESSION['QUOTE'])
						->applyConfiguration($ProductFields);
					if($combination == true){
						$item
							->setVariation($ProdId)
							->setProductId($vcprodid);
					}
					$_SESSION['QUOTE']->addItem($item);
				}
				return true;
			}else{
				$GLOBALS['AppliedOffers'] = false;
				return true;
			}

			return true;
		}
		else
		{
			logAddError('Se encontro un error al procesar el prepedido en Intelisis. OK="'.$this->getOK().'" OkRef="'.$this->getOkREf().'"');
			return false;
		}

	}
		
	public function getIWSResult() {
		
		libxml_use_internal_errors(true);
		$xml_errors[] = array();
		try {
			$xml_dom = new SimpleXMLElement($this->getResultado());
		}
		catch (Exception $e) {
			foreach(libxml_get_errors() as $error) {
				$xml_errors[] = $error->message;
				logAddError(implode('<br/>', $error->message));
			}
		}
		if(!$xml_dom) {
			logAddError('Se recibio un XML de resultado mal formado de una peticion '.get_class($this).'<br/>'.htmlentities($this->getResultado()));
			//$GLOBALS['ISC_CLASS_DB']->InsertQuery('sincronizacion', array('xml' => htmlentities($this->getResultado()), 'estatus' => 'ERROR'));
			return false;
		}

		$objeto = $xml_dom->xpath('/Intelisis/Resultado/PrePedido');
		$ResultadoPrePedido = $objeto[0];
		
		$objeto = $xml_dom->xpath('/Intelisis/Resultado/PrePedido/PedidoD');
		$ResultadoPedidoD = $objeto;
		
		if(isset($ResultadoPrePedido))
		{
			$ResultadoProductos = array(
				'AplicoOfertas' => $ResultadoPrePedido['AplicoOfertas'],
				'Productos' => array(),
			);
			foreach($ResultadoPedidoD as $key => $result) {
				$PedidoD = array();
				foreach($result->attributes() as $name => $value){
					$PedidoD[$name] = (string)$value;
				}
				
				$CamposConfigurables = array();
				$camposXMLdom = $result->xpath('CamposConfigurables/CampoConfigurable');
				foreach($camposXMLdom as $key => $result){
					$campo = $result->attributes();
					$campoId = (string)$campo['IDCampo'];
					$campoNombre = (string)$campo['Campo'];
					$campoValor = (string)$campo['Valor'];
					$CamposConfigurables[$campoId] = $campoValor;
				}
				
				$ResultadoProductos['Productos'][] = array(
					'Producto' => $PedidoD,
					'CamposConfigurables' => $CamposConfigurables,	
				);
			}

			return $ResultadoProductos;			
		}
		else
		{
			logAddError('No se encontro el Resultado de la peticion a IntelisisWebService '.get_class($this).' IS-ID="'.$this->getID().'"');
			return true;
		}
	}

	
}