<?php

class ISC_INTELISIS_WS_CUSTOMER extends ISC_INTELISIS_WS {

	private $customerId = 0;
	private $customerData = array();
	private $GUID = '';
	
	public function __construct($customerId, $customerData, $action) {
		parent::__construct();
		
		$this->customerId = $customerId;
		$this->customerData = $customerData;
		$this->useDropbox = true;
		$this->action = $action;

		$customerDB = GetCustomer($this->customerId);
		
		if(!isset($this->customerData['custformsessionid'])) {
			$this->customerData['custformsessionid'] = $customerDB['custformsessionid'];
		}
		$customFieldsAccount = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, false, $this->customerData['custformsessionid']);

		$RFC = getCustomFieldByLabel($customFieldsAccount, FORMFIELDS_FORM_ACCOUNT, 'RFC');
		if($action == 'edit') {
			$this->GUID = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT GUID FROM [|PREFIX|]intelisis_customers WHERE customerid = "'.$customerId.'"', 'GUID');
			if(!$this->GUID || $this->GUID == '') {
				logAddError('No se encontro el registro del customerid "'.$customerId.'" para editar');
				return false;
			}
		}
		else if($action == 'add') $this->GUID = gen_uuid();
		else {
			logAddError('Se envio una peticion '.get_class($this).' con accion '.$action.' no valida');
			return false;			
		}
		
		$Estatus = '';
		if($action == 'add') $Estatus = 'ALTA';
		if($action == 'edit') $Estatus = 'CAMBIO';
		
		if(!isset($this->customerData['custpassword'])) {
			$this->customerData['custpassword'] = $customerDB['custpassword'];
		}
		
		$this->customerData['custpassword'] = str_replace(array('”', '"', '&', '°'), array('&#8221;', '&#34;', '&#38;', '&#176;'), $this->customerData['custpassword']);
		
		$xml = '<?xml version="1.0" encoding="windows-1252"?><Intelisis Sistema="Intelisis" Contenido="Solicitud" Referencia="eCommerce.Intelisis.WebUsuario" SubReferencia="'.$this->GUID.'" Compania="'.$this->empresa.'"><Solicitud Empresa ="'.$this->empresa.'" Sucursal="'.GetConfig('syncIWSintelisissucursal').'" Estatus="'.$Estatus.'" ><Usuario GUID="'.$this->GUID.'" Nombre="'.$this->customerData['custconfirstname'].'" Apellidos="'.$this->customerData['custconlastname'].'" eMail= "'.$this->customerData['custconemail'].'" Contrasena="'.$this->customerData['custpassword'].'" Telefono="'.$this->customerData['custconphone'].'" RFC="'.$RFC.'" Compania="'.$this->customerData['custconcompany'].'">';
		
		if(!empty($customerData['addresses']))
		{
			$customFieldsAddress = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_BILLING, false, $customerData['addresses'][0]['shipformsessionid']);
			
			$Colonia = getCustomFieldByLabel($customFieldsAddress, FORMFIELDS_FORM_BILLING, 'Colonia');
			$Delegacion = getCustomFieldByLabel($customFieldsAddress, FORMFIELDS_FORM_BILLING, 'Delegacion');
			$NoExt = getCustomFieldByLabel($customFieldsAddress, FORMFIELDS_FORM_BILLING, 'Numero Exterior');

			$xml .= '<Direccion GUID="'.$customerData['addresses'][0]['GUID'].'" Direccion1="'.$customerData['addresses'][0]['shipaddress1'].'" NumeroExterior="'.$NoExt.'" Direccion2="'.$customerData['addresses'][0]['shipaddress2'].'" Colonia="'.$Colonia.'" Delegacion="'.$Delegacion.'" Ciudad="'.$customerData['addresses'][0]['shipcity'].'" Pais="'.$customerData['addresses'][0]['shipcountry'].'" Estado="'.$customerData['addresses'][0]['shipstate'].'" CP="'.$customerData['addresses'][0]['shipzip'].'" />';
		}
		$xml .= '</Usuario></Solicitud></Intelisis>';
	
		$this->xml = $xml;
	}
	
	public function handleIWSResult() {

		if($this->action == 'add') $GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_customers', array('GUID' => $this->GUID, 'customerid' => $this->getCustomerId()));
		if($this->getOk() == 0)
		{
			$resultado = $this->getIWSResult();
			
			$Cliente =  isset($resultado['Cliente']) ? $resultado['Cliente'] : '';
			$IDWebUsuario =  isset($resultado['WebUsuarioID']) ? $resultado['WebUsuarioID'] : '';
			
			if($Cliente == '' || $IDWebUsuario == '')
			{
				$xml = '';
				logAddError('No se encontraron los ID\'s de Cliente o WebUsuario de la respuesta de IWS.');
				return false;
			}
			else
			{
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_customers', array('Cliente' => $Cliente, 'IDWebUsuario' => $IDWebUsuario), 'GUID = "'.$this->GUID.'"');
				logAddSuccess('Se transmitio el cliente "'.$this->customerId.'" a Intelisis');
				return true;
			}
		}
		else
		{
			logAddError('Se encontro un error al procesar el cliente "'.$this->customerId.'" en Intelisis. OK="'.$this->getOk().'" OkRef="'.$this->getOkRef().'" IS-ID="'.$this->getID().'"');
			return false;
		}
	}
	
	public function handleDropboxResult() {
		if($this->action == 'add' && !$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_customers', array('GUID' => $this->GUID, 'customerid' => $this->customerId, 'Cliente' => '', 'IDWebUsuario' => 0)))
		{
			logAddError('Error al registrar al customer id "'.$this->customerId.'" con GUID "'.$this->GUID.'".<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
		else {
			return true;
		}
	}
	
	public function getCustomerId() {
		return $this->customerId;
	}
}