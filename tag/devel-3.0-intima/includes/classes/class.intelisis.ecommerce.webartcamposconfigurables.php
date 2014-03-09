<?php

class ISC_INTELISIS_ECOMMERCE_WEBARTCAMPOSCONFIGURABLES extends ISC_INTELISIS_ECOMMERCE
{
	
	public function ProcessData() {
		if($this->getXMLdom())
		{
			switch ($this->getAttribute('Estatus')) {
				case 'ALTA':
					return $this->createConfigurableField();
				break;
				case 'CAMBIO':
					return $this->updateConfigurableField();
				break;
				case 'BAJA':
					return $this->deleteConfigurableField();
				break;
				default:
					logAdd(LOG_SEVERITY_ERROR, 'Estatus de archivo no valido. '.get_class($this).'. Estatus: "'.$this->getAttribute('Estatus').'"', 'Archivo: "'.$this->getXMLfilename().'"');
					return false;
				break;
			}
		}
		else
		{
			logAdd(LOG_SEVERITY_WARNING, 'Se trato de procesar un objeto '.get_class($this).' sin XML DOM especificado', 'Archivo: "'.$this->getXMLfilename().'"');
		}
	}
	
	private function getProductId() {
		$query = "SELECT productid FROM [|PREFIX|]intelisis_products WHERE ArticuloID = '".$this->getAttribute('IDArticulo')."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	
		return $row['productid'] ? $row['productid'] : false;
	}
	
	private function getFieldId() {
		$query = "SELECT productfieldid FROM [|PREFIX|]intelisis_configurable_fields WHERE IDCampo = '".$this->getAttribute('IDAtributo')."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	
		return $row['productfieldid'] ? $row['productfieldid'] : false;
	}
	
	private function getFieldType()
	{
		switch ($this->getData('TipoCampo')){
			case 'Texto Corto':
				return 'text';
				break;
			case 'Texto Area':
				return 'textarea';
				break;
			case 'Archivo':
				return 'file';
				break;
			case 'Si/No':
			case 'Checkbox':
				return 'checkbox';
				break;
			case 'Menu de seleccion':
			case 'Menu de selección':
			case 'Menú de selección':
			case 'Menú de seleccion':
			case 'Menu de seleccion':
			case 'Seleccionar Opciones':
			case 'OpcionIntelisis':
				return 'select';
				break;
			default:
				return false;
				break;
		}
	}
	
	private function SaveFieldDetails(){
		$values = $this->getData('ValorSelect');
		$OpcionesIntelisis = $this->getData('OpcionesIntelisis');
		
		$values = explode(',', $values);
		$OpcionesIntelisis = explode(',', $OpcionesIntelisis);

		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('intelisis_configurable_fields_detail', 'WHERE ID = "'.$this->getAttribute('IDAtributo').'"');
		
		foreach($values as $index => $value){
			$savedata = array(
					'ID' => $this->getAttribute('IDAtributo'),
					'Valor' => $value,
					'OpcionIntelisis' => $OpcionesIntelisis[$index],
			);
			
			$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_configurable_fields_detail', $savedata);
		}
	}
	
	private function createConfigurableField() {
		$productId = $this->getProductId();
		if(!$productId)
		{
			logAdd(LOG_SEVERITY_WARNING, 'No se pudo encontrar el productid del Articulo Web ID "'.$this->getAttribute('IDArticulo').'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
	
		$options = $this->getData('ValorSelect');
		//A veces el valor de Intelisis viene con una coma al principio, esto la quita
		if(substr($options, 0, 1) == ',') $options = substr($options, 1);
		
		if($this->getData('TipoCampo') == 'Seleccionar Opciones'){
			$this->SaveFieldDetails();
		}
		
		$ProductFields=array(
			"fieldprodid" => $productId,
			"fieldname" => $this->getData('Nombre'),
			"fieldtype" => $this->getFieldType(),
			"fieldfileType" => $this->getData('tipoArchivo', '*'),
			"fieldfileSize" => FormatNumber($this->getData('tamanoArchivo')),
			"fieldselectOptions" => $options,
			"fieldrequired" => $this->getData('Requerido'),
			"fieldsortOrder" => $this->getData('Orden'),
			"fieldlayermodifiers" => '',
		);
		
		$fieldId = $GLOBALS['ISC_CLASS_DB']->InsertQuery("product_configurable_fields", $ProductFields);
		if($fieldId)
		{
			$dataInsert = array('IDCampo' => $this->getAttribute('IDAtributo'), 'productfieldid' => $fieldId);
			if($GLOBALS['ISC_CLASS_DB']->TableHasField('intelisis_configurable_fields', 'OpcionIntelisis')){
				$dataInsert['OpcionIntelisis'] = $this->getData('OpcionIntelisis');
			}
			if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery("intelisis_configurable_fields", $dataInsert))
			{
				logAdd(LOG_SEVERITY_ERROR, 'No se pudo registrar en intelisis_configurable_fields al Campo ID "'.$this->getAttribute('IDAtributo').'" con el productfieldid "'.$fieldId.'".<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
			}
			else
			{
				logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis creo el campo configurable "'.$this->getData('Nombre').'" para el producto ID "'.$fieldId.'"');
			}
			return true;
		}
		else
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al guardar el campo configurable "'.$this->getData('Nombre').'" para el producto ID "'.$productId.'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
	}
	
	private function updateConfigurableField() {
		$productId = $this->getProductId();
		if(!$productId)
		{
			logAdd(LOG_SEVERITY_WARNING, 'No se pudo encontrar el productid del Articulo Web ID "'.$this->getAttribute('IDArticulo').'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		$fieldId = $this->getFieldId();
		if(!$fieldId)
		{
			logAdd(LOG_SEVERITY_WARNING, 'No se pudo encontrar el productfieldid del Campo Configurable ID "'.$this->getAttribute('IDAtributo').'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		$options = $this->getData('ValorSelect');
		//A veces el valor de Intelisis viene con una coma al principio, esto la quita
		if(substr($options, 0, 1) == ',') $options = substr($options, 1);
		
		if($this->getData('TipoCampo') == 'Seleccionar Opciones'){
			$this->SaveFieldDetails();
		}
		
		$ProductFields = array(
			"fieldprodid" => $productId,
			"fieldname" => $this->getData('Nombre'),
			"fieldtype" => $this->getFieldType(),
			"fieldfileType" => $this->getData('tipoArchivo', '*'),
			"fieldfileSize" => FormatNumber($this->getData('tamanoArchivo')),
			"fieldselectOptions" => $options,
			"fieldrequired" => $this->getData('Requerido'),
			"fieldsortOrder" => $this->getData('Orden'),
		);
		
		if($GLOBALS['ISC_CLASS_DB']->TableHasField('intelisis_configurable_fields', 'OpcionIntelisis')){
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery("intelisis_configurable_fields", array('OpcionIntelisis' => $this->getData('OpcionIntelisis')), "productfieldid='".(int)$fieldId."'");
		}
		
		if($GLOBALS['ISC_CLASS_DB']->UpdateQuery("product_configurable_fields", $ProductFields, "productfieldid='".(int)$fieldId."'"))
		{
			logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis edito el campo configurable "'.$this->getData('Nombre').'" para el producto ID "'.$fieldId.'"');
			return true;
		}
		else
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al editar el campo configurable "'.$this->getData('Nombre').'" para el producto ID "'.$productId.'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
	}

	private function deleteConfigurableField() {
		$fieldId = $this->getFieldId();
		if(!$fieldId)
		{
			logAdd(LOG_SEVERITY_WARNING, 'No se pudo encontrar el productfieldid del Campo Configurable ID "'.$this->getAttribute('IDAtributo').'". Es posible que el producto ya haya sido eliminado.<br/>Archivo: '.$this->getXMLfilename());
			return true;
		}
		
		if($GLOBALS['ISC_CLASS_DB']->DeleteQuery("product_configurable_fields", " WHERE productfieldid='".$fieldId."'"))
		{
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery("intelisis_configurable_fields", " WHERE productfieldid='".$fieldId."'");
			logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis elimino el campo configurable ID "'.$this->getAttribute('IDAtributo').'"');
			return true;
		}
		else
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al editar el campo configurable "'.$this->getData('Nombre').'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
	}
}