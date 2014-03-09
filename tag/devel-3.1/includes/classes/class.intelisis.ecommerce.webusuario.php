<?php
require_once(ISC_BASE_PATH . '/lib/addressvalidation.php');

class ISC_INTELISIS_ECOMMERCE_WEBUSUARIO extends ISC_INTELISIS_ECOMMERCE {
	
	public function ProcessData() {
		if($this->getXMLdom())
		{
			//printe($this->getAttribute('Estatus').": ".$this->getAttribute('Cliente'));
			switch ($this->getAttribute('Estatus')) {
				case 'ALTA':
					return $this->createCustomer();
				break;
				case 'CAMBIO':
					return $this->updateCustomer();
				break;
				case 'BAJA':
					return $this->deleteCustomer();
				break;
				default:
					logAdd(LOG_SEVERITY_ERROR, 'Estatus de archivo no valido. '.get_class($this).'. Estatus: "'.$this->getAttribute('Estatus').'". Archivo: "'.$this->getXMLfilename().'"');
					return false;
				break;
			}
		}
		else
		{
			logAdd(LOG_SEVERITY_WARNING, 'Se trato de procesar un objeto '.get_class($this).' sin XML DOM especificado. Archivo: "'.$this->getXMLfilename().'"');
		}
	}
	
	private function getCurrencyId(){
		$currencyId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT currencyid FROM [|PREFIX|]currencies WHERE currencyname = "'.$this->getData('Moneda').'"', 'currencyid');
	
		return $currencyId ? $currencyId : NULL;
	}
	
	private function createCustomer() {
		$customer = new ISC_CUSTOMER();
		
		if($RFCFieldId = getCustomFieldId(FORMFIELDS_FORM_ACCOUNT, 'RFC')) {
			$_GET['FormField'][FORMFIELDS_FORM_ACCOUNT][$RFCFieldId] = $this->getData('RFC');
		}
		$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, true);
		
		$customerData = array(
			'custconemail' => $this->getData('eMail'),
			'custpassword' => $this->getData('Contrasena'),
			'custconfirmpassword' => $this->getData('Contrasena'),
			'custconfirstname' => $this->getData('Nombre'),
			'custconlastname' => $this->getData('Apellido'),
			'custconcompany' => '', //$this->getData('Nombre'), NES: Falta este campo en la tabla de Intelisis
			'custconphone' => $this->getData('Telefono'),
		);

		if($customerData['custconphone'] == '') $customerData['custconphone'] = "000"; 
		// Does an account with this email address already exist?
		if ($customer->AccountWithEmailAlreadyExists($customerData['custconemail'])) {
			logAdd(LOG_SEVERITY_WARNING, 'Interfaz con Intelisis intento crear un Cliente con Mail ya existente: '.$customerData['custconemail']);
			return true;
		}
		// Else is the provided phone number valid?
		else if (!$customer->ValidatePhoneNumber($customerData['custconphone'])) {
			logAdd(LOG_SEVERITY_WARNING, 'Interfaz con Intelisis intento crear un Cliente con Telefono no valido: '.$customerData['custconphone']);
			return false;
		}
		else {
			// Create the user account in the database
			$token = GenerateCustomerToken();
			$customerData['customertoken'] = $token;

			// Add in the form sessions here AFTER all the validation
			$accountFormSessionId = $GLOBALS['ISC_CLASS_FORM']->saveFormSession(FORMFIELDS_FORM_ACCOUNT);

			if (isId($accountFormSessionId)) {
				$customerData['custformsessionid'] = $accountFormSessionId;
			}

			$customerId = $customer->CreateCustomerAccount($customerData);

			if (isId($customerId)) {
				if(!($rtn_insert = $GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_customers', array('GUID' => $this->getData('GUID'), 'customerid'=>$customerId, 'Cliente'=>$this->getData('Cliente'), 'IDWebUsuario' => $this->getData('ID'), 'currencyid' => $this->getCurrencyId()), true)))
				{
					logAdd(LOG_SEVERITY_ERROR, 'Error al relacionar al cliente ID '.$customerId.' con el Cliente Intelisis '.$this->getAttribute('Cliente').'.');
					return false;
				}
				else {
					logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz con Intelisis creo el Cliente ID: '.$customerId.' Mail: '.$customerData['custconemail']);
					return true;					
				}
			}
			else {
				// Couldn't create the account
				logAdd(LOG_SEVERITY_ERROR, 'Interfaz con Intelisis genero un error al crear un Cliente');
				return false;
			}
		}
	}
	
	private function getCustomerId() {
		$query = "SELECT customerid FROM [|PREFIX|]intelisis_customers WHERE GUID = '".$this->getData('GUID')."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		
		if(!$row['customerid']) {
			$query = "SELECT customerid FROM [|PREFIX|]intelisis_customers WHERE IDWebUsuario = '".$this->getAttribute('IDUsuario')."'";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		}

		return $row['customerid'] ? $row['customerid'] : false;
	}
	
	private function GetAccountDetails()
	{

		// Get the id of the customer
		$customer_id = $this->getCustomerId();
		if(!$customer_id)
		{
			logAdd(LOG_SEVERITY_ERROR, 'Se intento editar un WebUsuario con id "'.$this->getData('ID').'" invalido');
			return false;
		}

		$query = sprintf("select * from [|PREFIX|]customers where customerid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($customer_id));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if ($row !== false) {
			return $row;
		}
		else {
			return false;
		}
	}
	
	private function getFormFieldIdByPrivateId($privateId) {
		$id = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT formfieldid FROM [|PREFIX|]formfields WHERE formfieldprivateid = "'.$privateId.'"', 'formfieldid');
		
		return $id ? $id : false;  
	}
	
	private function updateCustomer() {
			$customer_id = $this->getCustomerId();
			
			if(!$customerDetails = $this->GetAccountDetails())
			{
				logAdd(LOG_SEVERITY_ERROR, 'No se encontro un registro del customer id "'.$this->getData('ID').'"<br/> Archivo: '.$this->getXMLfilename().'<br/> '.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
		
			$customer = new ISC_CUSTOMER();
			/**
			 * Customer Details
			 */
			$customerMap = array(
				'EmailAddress' => 'account_email',
				'Password' => 'account_password',
				'ConfirmPassword' => 'account_password_confirm'
			);
			
			$intelisis_keys = array(
				'EmailAddress' => 'eMail',
				'Password' => 'Contrasena',
				'ConfirmPassword' => 'ContrasenaConfirmacion'		
			);

			foreach(array_keys($customerMap) as $key)
			{
				$_POST[___FORM_DEFAULT_NAME___][FORMFIELDS_FORM_ACCOUNT][$this->getFormFieldIdByPrivateId($key)] = $this->getData($intelisis_keys[$key]);
			}
			
			$fields = $GLOBALS['ISC_CLASS_FORM']->getFormFields(FORMFIELDS_FORM_ACCOUNT, true);

			/**
			 * Validate the field input. Unset the password and confirm password fields first
			 */
			foreach (array_keys($fields) as $fieldId) {
				if (isc_strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'password' || isc_strtolower($fields[$fieldId]->record['formfieldprivateid']) == 'confirmpassword') {
					$fields[$fieldId]->setRequired(false);
				}
			}

			$errmsg = '';
			if (!validateFieldData($fields, $errmsg)) {
				logAdd(LOG_SEVERITY_ERROR, 'Error de validacion. '.$errmsg);
				return false;
				//return $this->EditAccount($errmsg, MSG_ERROR);
			}

			$postdata = array();
			foreach(array_keys($fields) as $fieldId) {
				if (!array_key_exists($fields[$fieldId]->record['formfieldprivateid'], $customerMap)) {
					continue;
				}

				$_POST[$customerMap[$fields[$fieldId]->record['formfieldprivateid']]] = $fields[$fieldId]->GetValue();
			}
			
			$_POST['account_phone'] = $this->getData('Telefono');

			if(isset($customerDetails['custconemail']) && $customerDetails['custconemail'] != $_POST['account_email'])
			{
				// Are they updating their email address? If so is the new email address available?
				if ($customer->AccountWithEmailAlreadyExists($_POST['account_email'], $customer_id)) {
					logAdd(LOG_SEVERITY_ERROR, 'Ya existe un customer con el email "'.$_POST['account_email'].'". Archivo: '.$this->getXMLfilename());
					return false;
				}
			}

			$phone = $this->getData('Telefono') == '' ? '000' : $this->getData('Telefono'); 
			if (!$customer->ValidatePhoneNumber($phone)) {
				logAdd(LOG_SEVERITY_ERROR, 'El telefono proveido "'.$phone.'" no es valido. Archivo: '.$this->getXMLfilename());
				return false;
			}

			$pass1 = $_POST['account_password'];
			$pass2 = $_POST['account_password_confirm'];

			if ($pass1 . $pass2 !== '' && $pass1 !== $pass2) {
				logAdd(LOG_SEVERITY_ERROR, 'Los dos passwords "'.$pass1.'" y "'.$pass2.'" no coinciden. Archivo: '.$this->getXMLfilename());
				return false;
			}

			$UpdatedAccount = array(
				"customerid" => $customer_id,
				"custconfirstname" => $this->getData('Nombre'),
				"custconlastname" => $this->getData('Apellido'),
				"custconcompany" => '', //ToDo: Intelisis siempre me manda DEMO, explicar lo que espero
				"custconemail" => $this->getData('eMail'),
				"custconphone" => $this->getData('Telefono'),
			);

			// Do we need to update the password?
			if ($pass1 == $pass2 && $pass1 != "" && $customerDetails['custpassword'] != $pass1) {
				$UpdatedAccount['custpassword'] = $pass1;
			}

			$customerEntity = new ISC_ENTITY_CUSTOMER(); 
			if ($customerEntity->edit($UpdatedAccount))
			{
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_customers', array('Cliente' => $this->getData('Cliente'), 'customerid' => $customer_id, 'currencyid' => $this->getCurrencyId()), 'GUID = "'.$this->getData('GUID').'"');
				logAdd(LOG_SEVERITY_SUCCESS, 'Se edito el customer id "'.$customer_id.'"');
				return true;
			}
			else
			{
				logAdd(LOG_SEVERITY_ERROR, 'Error al editar el customerid "'.$customer_id.'". Archivo: '.$this->getXMLfilename().'.<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
				return false; 
			}
	}
	
	private function deleteCustomer() {
		$queries = array();
		
		$customerId = $this->getCustomerId();

		if ($customerId) {
			$entity = new ISC_ENTITY_CUSTOMER();
			if (!$entity->delete($customerId)) {
				$err = $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg();
				return false;
				//$this->ManageCustomers($err, MSG_ERROR);
			} else {
				// Log this action
				$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($customerId));
				logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz con Intelisis elimino el Cliente ID: '.$customerId.' Cliente: '.$this->getAttribute('Cliente'));
				$GLOBALS["ISC_CLASS_DB"]->DeleteQuery('intelisis_customers', 'WHERE customerid = "'.$customerId.'"', 1);
				//$this->ManageCustomers(GetLang('CustomersDeletedSuccessfully'), MSG_SUCCESS);
				return true;
			}
		}
		else {
			logAdd(LOG_SEVERITY_ERROR, 'No se pudo borrar el cliente '.$this->getAttribute('Cliente').'. No se pudo encontrar el customer id. Archivo: '.$this->getXMLfilename());
			return false;
		}
	}
}