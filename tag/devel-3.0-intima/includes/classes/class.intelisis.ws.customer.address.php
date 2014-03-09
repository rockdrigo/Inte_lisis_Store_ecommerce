<?php

class ISC_INTELISIS_WS_CUSTOMER_ADDRESS extends ISC_INTELISIS_WS {

	private $shippingid = 0;
	private $ShippingAddress = array();
	private $GUID = '';
	
	public function __construct($shippingid, $address, $action) {
		parent::__construct();
		
		$this->shippingid = $shippingid;
		$this->useDropbox = true;
		$this->action = $action;
		
		$result = $GLOBALS['ISC_CLASS_DB']->Query('SELECT * FROM [|PREFIX|]shipping_addresses WHERE shipid = "'.$shippingid.'"');
		
		$ShippingAddress = array();
		$ShippingAddress = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		if(empty($ShippingAddress))
		{
			logAddError('No se encontraron los detalles de la direccion de envio shipid "'.$shippingid.'"');
			return false;
		}
		
		$IDEnviarA = '';
		if($action == 'edit') {
			$result_isa = $GLOBALS['ISC_CLASS_DB']->Query('SELECT GUID, IDEnviarA FROM [|PREFIX|]intelisis_shipping_addresses WHERE shipid = "'.$shippingid.'"');
			if(!$result_isa) {
				logAddError('No se encontro el registro del shipid "'.$shippingid.'" para editar');
				return false;
			}
			$row_isa = $GLOBALS['ISC_CLASS_DB']->Fetch($result_isa);
			$this->GUID = $row_isa['GUID'];
			$IDEnviarA = $row_isa['IDEnviarA'];
		}
		else if($action == 'add') $this->GUID = $address['GUID'];
		else {
			logAddError('Se envio una peticion '.get_class($this).' con accion '.$action.' no valida');
			return false;			
		}
		
		$this->ShippingAddress = $ShippingAddress;
		
		if($result = $GLOBALS['ISC_CLASS_DB']->Query('SELECT GUID, IDWebUsuario, Cliente FROM [|PREFIX|]intelisis_customers WHERE customerid = "'.$ShippingAddress['shipcustomerid'].'"'))
		{
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			$IDWebUsuario = isset($row['IDWebUsuario']) ? $row['IDWebUsuario'] : '';
			$Cliente = isset($row['Cliente']) ? $row['Cliente'] : '';
			$GUIDUsuario = isset($row['GUID']) ? $row['GUID'] : '';
		}
		else
		{
			logAddNotice('No se encontraron los detalles del customerid "'.$ShippingAddress['shipcustomerid'].'" que pertenece la shipID "'.$shippingid.'". customerid "'.$ShippingAddress['shipcustomerid'].'".<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
		}
		
		$Estatus = '';
		if($action == 'add') $Estatus = 'ALTA';
		if($action == 'edit') $Estatus = 'CAMBIO';

		$customFieldsAddress = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_BILLING, false, $ShippingAddress['shipformsessionid']);
		
		/*
		$Colonia = getCustomFieldByLabel($customFieldsAddress, FORMFIELDS_FORM_BILLING, 'Colonia');
		$Delegacion = getCustomFieldByLabel($customFieldsAddress, FORMFIELDS_FORM_BILLING, 'Delegacion');
		$NoExt = getCustomFieldByLabel($customFieldsAddress, FORMFIELDS_FORM_BILLING, 'Numero Exterior');
		$Vivienda = getCustomFieldByLabel($customFieldsAddress, FORMFIELDS_FORM_BILLING, 'Vivienda');
		*/

		foreach($customFieldsAddress as $fieldid => $field){
			if($field->record['formfieldisimmutable'] > 0)
				unset($customFieldsAddress[$fieldid]);
		}
		$customFieldsAddressXML = getCustomFieldsAsXMLAttributes($customFieldsAddress);
		
		$xml = '<?xml version="1.0" encoding="windows-1252"?><Intelisis Sistema="Intelisis" Contenido="Solicitud" Referencia="eCommerce.Intelisis.WebUsuarioEnviarA" SubReferencia="'.$this->GUID.'" Version="1.0"><Solicitud Empresa="'.$this->empresa.'" Sucursal="'.GetConfig('syncIWSintelisissucursal').'" Estatus="'.$Estatus.'" ><Direccion GUID="'.$this->GUID.'" ID="'.$IDEnviarA.'" GUIDUsuario="'.$GUIDUsuario.'" UsuarioID="'.$IDWebUsuario.'" Cliente="'.$Cliente.'" Nombre="'.$ShippingAddress['shipfirstname'].'" Apellido="'.$ShippingAddress['shiplastname'].'" Direccion1="'.$ShippingAddress['shipaddress1'].'" Direccion2="'.$ShippingAddress['shipaddress2'].'" Ciudad="'.$ShippingAddress['shipcity'].'" Pais="'.$ShippingAddress['shipcountry'].'" Estado="'.$ShippingAddress['shipstate'].'" CP="'.$ShippingAddress['shipzip'].'" Telefono="'.$ShippingAddress['shipphone'].'" '.$customFieldsAddressXML.' /></Solicitud></Intelisis>';
	
		$this->xml = $xml;
	}
	
	public function handleIWSResult() {

		if($this->action == 'add') $GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_shipping_addresses', array('GUID' => $this->GUID, 'customerid' => $this->ShippingAddress['shipcustomerid'], 'shipid' => $this->shippingid));
		if($this->getOk() == 0)
		{
			$resultado = $this->getIWSResult();
				
			$Cliente =  isset($resultado['Cliente']) ? $resultado['Cliente'] : '';
			$EnviarAID =  isset($resultado['EnviarAID']) ? $resultado['EnviarAID'] : '';
			
			//NES: EnviarAID lo pongo en 1 ya que por ahora Intelisis Viana me regresa nulo. Esto es de mientras
			//$EnviarAID = $EnviarAID == '' ? '0' : $EnviarAID; 
			
			if($Cliente == ''/* || $EnviarAID == ''*/)
			{
				logAddError('No se encontraron los ID\'s de Cliente o EnviarAID de la respuesta de IWS. Cliente "'.$Cliente.'" EnviarAID "'.$EnviarAID.'"');
				return false;
			}
			else
			{
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_shipping_addresses', array('Cliente' => $Cliente, 'IDEnviarA' => $EnviarAID), 'GUID = "'.$this->GUID.'"');
				logAddSuccess('Se transmitio la direccion shipid "'.$this->ShippingAddress['shipid'].'" del cliente "'.$this->ShippingAddress['shipcustomerid'].'" a Intelisis');
				return true;
			}
		}
		else
		{
			logAddError('Se encontro un error al procesar la shipid "'.$this->shippingid.'" en Intelisis. OK="'.$this->getOk().'" OkRef="'.$this->getOkRef().'" IS-ID="'.$this->getID().'"');
			return false;
		}
	}
	
	public function handleDropboxResult() {
		if($this->action == 'add') {
			if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_shipping_addresses', array('GUID' => $this->GUID, 'shipid' => $this->shippingid, 'customerid' => $this->ShippingAddress['shipcustomerid']))) {
				logAddError('Error al registrar la ship "'.$this->shippingid.'" con GUID "'.$this->GUID.'".<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
			else {
				return true;
			}
		}
		else {
			return true;
		}
	}
	
	public function getCustomerId() {
		return $this->customerId;
	}
}