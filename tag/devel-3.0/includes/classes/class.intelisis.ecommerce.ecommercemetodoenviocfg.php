<?php

class ISC_INTELISIS_ECOMMERCE_ECOMMERCEMETODOENVIOCFG extends ISC_INTELISIS_ECOMMERCE {
	
	private function getShippingMethod() {
		switch (strtolower(trim($this->getAttribute('MetodoEnvio')))) {
			case 'por peso':
				return 'shipping_byweight';
				break;
			case 'por total de pedido':
				return 'shipping_bytotal';
				break;
			case 'porcentaje de total':
				return 'shipping_percentage';
				break;
			case 'monto fijo':
				return 'shipping_flatrate';
				break;
			case 'por articulo':
				return 'shipping_peritem';
				break;
			default:
				return false;
				break;
		}
	}
	
	public function create() {
		$zone = $this->getDefaultShippingZone();

		// If the zone doesn't exist, show an error message
		if(!isset($zone['zoneid'])) {
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro la zona por default');
		}
		
		if(!$methodname = $this->getShippingMethod()){
			logAdd(LOG_SEVERITY_ERROR, 'Se intentó crear un método de envío con un módulo "'.$this->getAttribute('MetodoEnvio').'" inválido');
			return false;
		}

		$data = array(
		    'zoneId' => $zone,
		    'methodmodule' => $methodname,
		    'methodname' => $this->getData('Nombre'),
		    'methodenabled' => $this->getData('EstatusMetodo') == 'ACTIVO' ? '1' : '0',
		);
		
		if($existingId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT methodid
		FROM [|PREFIX|]shipping_methods 
		WHERE methodname = "'.$this->getAttribute('NombreAntes').'"', 'methodid')){
			$data['methodid'] = $existingId;
		}
		else {
			$data['methodid'] = 0;
		}
		
		$this->getShippingVars($data);

		if(!$this->ValidateShippingZoneMethod($data, $error)) {
			logAdd(LOG_SEVERITY_ERROR, 'Error al crear el metodo de envio "'.$this->getData('Nombre').'". '.$error);
			return false;
		}

		$GLOBALS["ISC_CLASS_DB"]->StartTransaction();
		$GLOBALS['ISC_CLASS_DB']->clearError();
		if(!$this->CommitShippingZoneMethod($data, $data['methodid'])) {
			$error = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
			$GLOBALS["ISC_CLASS_DB"]->RollbackTransaction();
			logAdd(LOG_SEVERITY_ERROR, 'Se encontro un error al intentar guardar el Metodo de Envio "'.$this->getData('Nombre').'"'.$error);
			return false;
		}
		else {
			$GLOBALS["ISC_CLASS_DB"]->CommitTransaction();
			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($zone['zoneid'], $data['methodname']);
			logAdd(LOG_SEVERITY_SUCCESS, 'Se creo el Metodo de Envio "'.$this->getData('Nombre').'"');
			return true;
		}
	}
	
	public function update() {
		return $this->create();
	}
	
	public function delete() {
			$moduleid = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT methodid FROM [|PREFIX|]shipping_methods
				WHERE methodmodule = "'.$this->getShippingMethod().'" AND zoneid = "'.$this->getDefaultShippingZone().'"', 'methodid');
			// Delete the methods from the database
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('shipping_vars', "WHERE methodid = '".$moduleid."'");
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('shipping_methods', "WHERE methodid = '".$moduleid."'");
			logAdd(LOG_SEVERITY_SUCCESS, 'Se elimino el Metodo de Envío "'.$this->getAttribute('Nombre').'" ('.$this->getShippingMethod().')');
			return true;
	}
	
	private function getShippingVars(&$data){
		$dataArray = array();
		
		switch($this->getShippingMethod()){
			case 'shipping_byweight':
				$dataArray['defaultcost'] = floatval($this->getData('Precio'));
				$xml = $this->getXMLdom();
				$details = $xml->xpath('/Intelisis/Resultado/eCommerceMetodoEnvioCfg/eCommerceMetodoEnvioCfgD');
				foreach($details as $index => $detailXML) {
					$detail = array();
					foreach($detailXML->attributes() as $name => $value){
						$detail[$name] = (string)$value;
					}
					$dataArray['lower_'.$index] = floatval($detail['NumeroD']);
					$dataArray['upper_'.$index] = floatval($detail['NumeroA']);
					$dataArray['cost_'.$index] = floatval($detail['Precio']);
				}
			break;
			case 'shipping_percentage':
				$dataArray['percentage'] = floatval($this->getData('Precio'));
			break;
			case 'shipping_peritem':
				$dataArray['peritemcost'] = floatval($this->getData('Precio'));
			break;
			case 'shipping_flatrate':
				$dataArray['shippingcost'] = floatval($this->getData('Precio'));
			break;
			case 'shipping_bytotal':
				$dataArray['defaultcost'] = floatval($this->getData('Precio'));
				$xml = $this->getXMLdom();
				$details = $xml->xpath('/Intelisis/Resultado/eCommerceMetodoEnvioCfg/eCommerceMetodoEnvioCfgD');
				foreach($details as $index => $detailXML) {
					$detail = array();
					foreach($detailXML->attributes() as $name => $value){
						$detail[$name] = (string)$value;
					}
					$dataArray['lower_'.$index] = floatval($detail['NumeroD']);
					$dataArray['upper_'.$index] = floatval($detail['NumeroA']);
					$dataArray['cost_'.$index] = floatval($detail['Precio']);
				}
			break;
		}
		
		$data[$this->getShippingMethod()] = $dataArray;
	}
	
	private function getDefaultShippingZone() {
		$query = "
			SELECT zoneid
			FROM [|PREFIX|]shipping_zones
			WHERE zonedefault='1'
		";
		$zoneId = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);
		
		return $zoneId;
	}
	
	private function GetShippingMethodData($methodId)
	{
		$query = "
			SELECT *
			FROM [|PREFIX|]shipping_methods
			WHERE methodid='".(int)$methodId."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$method = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		return $method;
	}
	
	private function GetShippingZoneData($zoneId)
	{
		$query = "
			SELECT *
			FROM [|PREFIX|]shipping_zones
			WHERE zoneid='".(int)$zoneId."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$zone = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		return $zone;
	}
	
	private function ValidateShippingZoneMethod(&$data, &$message)
	{
		$methodId = 0;
		if (isset($data['methodId']) && $data['methodId'] > 0) {
			$methodId = $data['methodId'];
		}

		// Check that the select method exists
		if (!isId($methodId) && (!isset($data['methodmodule']) || trim($data['methodmodule']) == '')) {
			return $this->create();
		}

		$methodIdQuery = '';
		if(isId($methodId)) {
			$methodIdQuery = " AND methodid!='".(int)$methodId."'";
		}

		// Check that a duplicate zone doesn't exist with this name
		$query = "
			SELECT methodid
			FROM [|PREFIX|]shipping_methods
			WHERE methodname='".$GLOBALS['ISC_CLASS_DB']->Quote($data['methodname'])."' AND zoneid='".(int)$data['zoneId']."'".$methodIdQuery
		;
		if($id = $GLOBALS['ISC_CLASS_DB']->FetchOne($query)) {
			$data['methodid'] = $id;
		}

		return true;
	}
	
	public function CommitShippingZoneMethod($data, $methodId = 0)
	{
		// If the method id is 0, then we're creating a new method
		if($methodId > 0) {
			$existingMethod = $this->GetShippingMethodData($methodId);
		}

		if(!trim($data['methodname'])) {
			return false;
		}

		if($methodId) {
			$data['zoneId'] = $existingMethod['zoneid'];
			$data['methodmodule'] = $existingMethod['methodmodule'];
		}

		$zone = $this->GetShippingZoneData($data['zoneId']);

		if($zone['zonehandlingtype'] != 'module') {
			$data['methodhandlingfee'] = 0;
		}
/* NES - Quito esto porque siempre aumento esta variable y ya en create() le pongo 0 o 1
		if(!isset($data['methodenabled'])) {
			$data['methodenabled'] = 0;
		}
		else {
			$data['methodenabled'] = 1;
		}
*/
		$methodData = array(
			'zoneid' => (int)$data['zoneId'],
			'methodname' => $data['methodname'],
			'methodmodule' => $data['methodmodule'],
			'methodhandlingfee' => $data['methodhandlingfee'],
			'methodenabled' => $data['methodenabled'],
			'methodvendorid' => $zone['zonevendorid'],
		);

		if($methodId == 0) {
			$methodId = $GLOBALS['ISC_CLASS_DB']->InsertQuery("shipping_methods", $methodData);
		}
		else {
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery("shipping_methods", $methodData, "methodid='".(int)$methodId."'");
		}

		// Couldn't save? return an error message
		if($GLOBALS['ISC_CLASS_DB']->GetErrorMsg()) {
			return false;
		}

		$moduleId = str_replace('shipping_', '', $data['methodmodule']);
		GetModuleById('shipping', $module, $moduleId);
		$moduleVars = array();
		if(isset($data[$data['methodmodule']])) {
			$moduleVars = $data[$data['methodmodule']];
		}

		$module->SetMethodId($methodId);
		$module->SaveModuleSettings($moduleVars);

		// Couldn't save? return an error message
		if($GLOBALS['ISC_CLASS_DB']->GetErrorMsg()) {
			return false;
		}

		// We've just configured shipping - mark it as so.
		if(!in_array('shippingOptions', GetConfig('GettingStartedCompleted'))) {
			GetClass('ISC_ADMIN_ENGINE')->MarkGettingStartedComplete('shippingOptions');
		}

		return $methodId;
	}
}