<?php
require_once(ISC_BASE_PATH . '/lib/addressvalidation.php');

class ISC_INTELISIS_ECOMMERCE_WEBCTEENVIARA extends ISC_INTELISIS_ECOMMERCE {
	
	
	private function getCustomerId() {
		$query = "SELECT customerid FROM [|PREFIX|]intelisis_customers WHERE IDWebUsuario = '".$this->getAttribute('IDUsuario')."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		return $row['customerid'] ? $row['customerid'] : false;
	}
	
	private function parseFieldData($fields, $formSessionId='')
	{
		if (!is_array($fields)) {
			return false;
		}
	
		$fieldMap = getAddressFormMapping();
		$savedata = array();
		$countryFieldId = '';
		$stateFieldId = '';
	
		foreach (array_keys($fields) as $fieldId) {
			if (!array_key_exists($fields[$fieldId]->record['formfieldprivateid'], $fieldMap)) {
				continue;
			}
	
			$key = 'ship' . $fieldMap[$fields[$fieldId]->record['formfieldprivateid']];
			$savedata[$key] = $fields[$fieldId]->getValue();
	
			if ($key == 'shipcountry') {
				$countryFieldId = $fieldId;
			} else if ($key == 'shipstate') {
				$stateFieldId = $fieldId;
			}
		}
	
		$savedata['shipcustomerid'] = $this->getCustomerId();
	
		/**
		 * Fill in the country and state IDs
		 */
		$savedata['shipcountryid'] = GetCountryByName($fields[$countryFieldId]->getValue());
	
		if (isId($savedata['shipcountryid'])) {
			$savedata['shipstateid'] = GetStateByName($fields[$stateFieldId]->getValue(), $savedata['shipcountryid']);
		} else {
			$savedata['shipstateid'] = 0;
		}
	
		/**
		 * Now save the form session record
		 */
		$formSessionId = $GLOBALS['ISC_CLASS_FORM']->saveFormSession(FORMFIELDS_FORM_ADDRESS, true, $formSessionId);
	
		if (isId($formSessionId)) {
			$savedata['shipformsessionid'] = $formSessionId;
		}
		
		return $savedata;
	}
	
	public function create() {
		
		// Sirve para validar que sea una alta a la tabla de direcciones
		if($this->getData('ID') == '0') {
			return true;
		}

		if(!$customer_id = $this->getCustomerId()) {
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro el customerid del IDWebUsuario "'.$this->getAttribute('IDUsuario').'"');
			return false;
		}
		
		if($GUID = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT GUID FROM [|PREFIX|]intelisis_shipping_addresses
				WHERE customerid = "'.$customer_id.'" AND GUID = "'.$this->getData('GUID').'"')){
			if($GUID) {
				return $this->update();//logAddWarning('Interfaz con Intelisis intento crear una direccion ID "'.$this->getData('ID').'" repetida (Cliente "'.$this->getData('Cliente').'", customerid "'.$customer_id.'")');
				return false;
			}
		}
		
		// Campos obligatorios y extras
		$name = $this->getData('Nombre');
		if($name == '') {
			$name = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT CONCAT(custconfirstname, " ", custconlastname) AS "Name" FROM [|PREFIX|]customers WHERE customerid = "'.$customer_id.'"', 'Name');
		}
		$nameparts = separateName($name);
		if (trim($nameparts['firstname']) == '') $nameparts['firstname'] = 'N/A';
		if (trim($nameparts['lastname']) == '') $nameparts['lastname'] = 'N/A';
		
		$tel = $this->getData('Telefono');
		$noext = $this->getData('NoExterior');

		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Nombre')] = $nameparts['firstname'];
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Apellidos')] = $nameparts['lastname'];
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Teléfono')] =  $tel == '' ? '00000' : $tel;
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldIdByPrivate(FORMFIELDS_FORM_ADDRESS, 'AddressLine1')] = $this->getData('Direccion', 'N/A');
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldIdByPrivate(FORMFIELDS_FORM_ADDRESS, 'AddressLine2')] = $this->getData('Direccion2', 'N/A');
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Ciudad')] = $this->getData('Ciudad', 'N/A');
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'País')] = $this->getData('Pais', GetCountryByName('Mexico'));
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Estado')] = $this->getData('Estado', GetStateByName('Distrito Federal', GetCountryByName('Mexico')));
		
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Código Postal')] = $this->getData('CP', '00000');
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Colonia')] = $this->getData('Colonia', 'N/A');
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Delegacion')] = $this->getData('Delegacion', 'N/A');
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Numero Exterior')] = $noext == '' ? 'S/N' : $noext;
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Vivienda')] = $this->getData('Vivienda', 'N/A');
		
		// Parseo los campos extras para su validacion
		$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ADDRESS, true);
	
		if (!validateFieldData($fields, $errmsg)) {
			logAdd(LOG_SEVERITY_ERROR, 'Error de validacion: '.$errmsg.'. Archivo: '.$this->getXMLfilename());
			return false;
		}

		// Creacion de la entidad (registro en shipping_addresses
		$ShippingAddress = $this->parseFieldData($fields);

		if (isset($ShippingAddress['shipfirstname']) && isset($ShippingAddress['shipaddress1'])) {
			$entity = new ISC_ENTITY_SHIPPING();
			$shippingid = $entity->add($ShippingAddress);
		}
		
		
		// Registro en intelisis_shipping_addresses
		if(isId($shippingid)) {
			if(($GUIDn = $this->getData('GUID')) == ''){
				$GUIDn = $this->getData('ID');
			}
			$register = array(
				'GUID' => $GUIDn,
				'shipid' => $shippingid,
				'customerid' => $customer_id,
				'Cliente' => $this->getData('Cliente'),
				'IDEnviarA' => $this->getData('ID'),
				'ListaPreciosEsp' => $this->getData('ListaPreciosEsp'),
			);

			$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_shipping_addresses', $register);
			logAdd(LOG_SEVERITY_SUCCESS, 'Se agrego la Direccion id "'.$this->getData('ID').'"  del customerid "'.$customer_id.'"');
			return true;
		}
		else {
			logAdd(LOG_SEVERITY_ERROR, 'Error al crear la direccion del customerid "'.$customer_id.'". Archivo: '.$this->getXMLfilename().'.<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
	}

	public function update() {
		
		if($this->getData('ID') == '0') {
			return true;
		}
		
		// ToDo: obtener el shipid, y editarlo. No olvidarse 'shipformsessionid'
		if($customer_id = $this->getCustomerId()){
			if(!$shipid = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT shipid FROM [|PREFIX|]intelisis_shipping_addresses
			WHERE customerid = "'.$customer_id.'" AND GUID = "'.$this->getData('GUID').'"', 'shipid')){
					return $this->create();
					//logAdd(LOG_SEVERITY_ERROR, 'No se encontro la direcionID "'.$this->getData('ID').'" del customerid "'.$customer_id.'". Archivo: '.$this->getXMLfilename().".<br/>Error: ".$GLOBALS['ISC_CLASS_DB']->Error());
					return false;
				}
		}
		else {
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro el customerid del IDWebUsuario "'.$this->getData('UsuarioID').'" para editar su direccion. Archivo: '.$this->getXMLfilename().".<br/>Error: ".$GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
		
		$entity = new ISC_ENTITY_SHIPPING();
		$previousEntity = $entity->get($shipid);
		$previousFormFields = $GLOBALS['ISC_CLASS_FORM']->getFormFieldsSession(FORMFIELDS_FORM_ADDRESS, $previousEntity['shipformsessionid']);
		
		$name = $this->getData('Nombre');
		if($name == '') {
			$name = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT CONCAT(custconfirstname, " ", custconlastname) AS "Name" FROM [|PREFIX|]customers WHERE customerid = "'.$customer_id.'"', 'Name');
		}
		$nameparts = separateName($name);
		if (trim($nameparts['firstname']) == '') $nameparts['firstname'] = $previousEntity['shipfirstname'];
		if (trim($nameparts['lastname']) == '') $nameparts['lastname'] = $previousEntity['shiplastname'];
		
		$tel = $this->getData('Telefono');
		$noext = $this->getData('NoExterior');
		
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Nombre')] = $nameparts['firstname'];
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Apellidos')] = $nameparts['lastname'];
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Teléfono')] =  $tel == '' ? $previousEntity['shipphone'] : $tel;
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldIdByPrivate(FORMFIELDS_FORM_ADDRESS, 'AddressLine1')] = $this->getData('Direccion', $previousEntity['shipaddress1']);
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldIdByPrivate(FORMFIELDS_FORM_ADDRESS, 'AddressLine2')] = $this->getData('Direccion2', $previousEntity['shipaddress2']);
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Ciudad')] = $this->getData('Ciudad', $previousEntity['shipcity']);
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'País')] = $this->getData('Pais', $previousEntity['shipcountryid']);
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Estado')] = $this->getData('Estado', $previousEntity['shipstateid']);
		$_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Código Postal')] = $this->getData('CP', $previousEntity['shipzip']);
		if($previousFormFields){
			$formfieldids = array(
				'Colonia' => getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Colonia'),
				'Delegacion' => getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Delegacion'),
				'Numero Exterior' => getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Numero Exterior'),
				'Vivienda' => getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Vivienda'),
			);
			
			if($formfieldids['Colonia']) $_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Colonia')] = $this->getData('Colonia', $previousFormFields[$formfieldids['Colonia']]->getValue());
			if($formfieldids['Delegacion']) $_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Delegacion')] = $this->getData('Delegacion', $previousFormFields[$formfieldids['Delegacion']]->getValue());
			if($formfieldids['Numero Exterior']) $_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Numero Exterior')] = $noext == '' ? $previousFormFields[$formfieldids['Numero Exterior']]->getValue() : $noext;
			if($formfieldids['Vivienda']) $_GET['FormField'][FORMFIELDS_FORM_ADDRESS][getCustomFieldId(FORMFIELDS_FORM_ADDRESS, 'Vivienda')] = $this->getData('Vivienda', $previousFormFields[$formfieldids['Vivienda']]->getValue());
		}
		
		$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ADDRESS, true);
		
		if (!validateFieldData($fields, $errmsg)) {
			logAdd(LOG_SEVERITY_ERROR, 'Error de validacion: '.$errmsg);
			return false;
		}

		$ShippingAddress = $this->parseFieldData($fields);

		if (isset($ShippingAddress['shipfirstname']) && isset($ShippingAddress['shipaddress1'])) {
			$shippingid = $entity->edit($ShippingAddress, $shipid);
		}
		
		if(isId($shippingid)) {
			if(($GUIDn = $this->getData('GUID')) == ''){
				$GUIDn = $this->getData('ID');
			}
			$register = array(
				'GUID' => $GUIDn,
				'shipid' => $shippingid,
				'customerid' => $customer_id,
				'Cliente' => $this->getData('Cliente'),
				'IDEnviarA' => $this->getData('ID'),
				'ListaPreciosEsp' => $this->getData('ListaPreciosEsp'),
			);
			logAdd(LOG_SEVERITY_SUCCESS, 'Se edito la Direccion id "'.$this->getData('ID').'"  del customerid "'.$customer_id.'"');
			return true;
		}
		else {
			logAdd(LOG_SEVERITY_ERROR, 'Error al editar la direccion del customerid "'.$customer_id.'". Archivo: '.$this->getXMLfilename().'.<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
	}
	
	public function delete() {
		if(!$customerId = $this->getCustomerId()) {
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro el customerid del IDWebUsuario "'.$this->getAttribute('IDUsuario').'". Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		if($this->getAttribute('IDEnviarA') == '') {
			logAdd(LOG_SEVERITY_ERROR, 'Se recibio un XML de BAJA de Direccion, pero el ID de direccion viene vacio "'.$this->getAttribute('IDEnviarA').'". Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		$shipid = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT shipid FROM [|PREFIX|]intelisis_shipping_addresses WHERE customerid = "'.$customerId.'" AND IDEnviarA = "'.$this->getAttribute('IDEnviarA').'"', 'shipid');
		if(!$shipid) {
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro el shipid "'.$shipid.'" de la direccion a eliminar del customerid "'.$customerId.'" con IDEnviarA "'.$this->getAttribute('IDEnviarA').'". Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('intelisis_shipping_addresses', 'WHERE shipid = "'.$shipid.'"');
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('shipping_addresses', 'WHERE shipid = "'.$shipid.'"');
		
		logAdd(LOG_SEVERITY_SUCCESS, 'Se elimino el registro de la direccion id "'.$shipid.'" del customerid "'.$customerId.'"');
		return true;
	}
}
