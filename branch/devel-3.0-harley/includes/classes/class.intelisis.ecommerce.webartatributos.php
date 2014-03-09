<?php

class ISC_INTELISIS_ECOMMERCE_WEBARTATRIBUTOS extends ISC_INTELISIS_ECOMMERCE
{
	public function ProcessData() {
		if($this->getXMLdom())
		{
			//printe($this->getAttribute('Estatus').": ".$this->getAttribute('Cliente'));
			switch ($this->getAttribute('Estatus')) {
				case 'ALTA':
					return $this->addAttribute();
				break;
				case 'CAMBIO':
					return $this->editAttribute();
				break;
				case 'BAJA':
					return $this->deleteAttribute();
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
		$query = "SELECT fieldid FROM [|PREFIX|]intelisis_customfields WHERE AtributoID = '".$this->getAttribute('IDAtributo')."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	
		return $row['fieldid'] ? $row['fieldid'] : false;
	}
	
	private function getExistingCustomFields() {
		$productId = $this->getProductId();
		if(!$productId)
		{
			logAdd(LOG_SEVERITY_WARNING, 'No se pudo encontrar el productid del Articulo Web ID "'.$this->getAttribute('IDArticulo').'"<br/Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		$query = sprintf("select * from [|PREFIX|]product_customfields where fieldprodid='%d' Order by fieldid ASC", $GLOBALS['ISC_CLASS_DB']->Quote($productId));
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		
		while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
			$RefArray[] = array(
					"name" => $row['fieldname'],
					"value" => $row['fieldvalue']
			);
		}
	}
	
	private function addAttribute() {
		$productCustomFields = $this->getExistingCustomFields();
		if($productCustomFields === false)
		{
			return false;
		}
		
		if($productCustomFields && array_key_exists($this->getData('Nombre'), $productCustomFields))
		{
			$result = $GLOBALS["ISC_CLASS_DB"]->UpdateQuery('product_customfields', array('fieldvalue' => $this->getData('Valor')), 'fieldprodid = "'.$this->getProductId().'" AND fieldname = "'.$this->getData('Nombre').'"');
		}
		else
		{
			$result = $GLOBALS["ISC_CLASS_DB"]->InsertQuery('product_customfields', array('fieldprodid' => $this->getProductId(), 'fieldname' => $this->getData('Nombre'), 'fieldvalue' => $this->getData('Valor')));
			if(!$GLOBALS["ISC_CLASS_DB"]->InsertQuery('intelisis_customfields', array('AtributoID'=>$this->getAttribute('IDAtributo'), 'fieldid'=>$GLOBALS["ISC_CLASS_DB"]->LastId())))
			{
				logAdd(LOG_SEVERITY_ERROR, 'No se pudo relacionar al Atributo Web ID "'.$this->getAttribute('IDAtributo').'" con el fieldid "'.$GLOBALS["ISC_CLASS_DB"]->LastId().'"<br/>'.$this->getXMLfilename());
				return false;
			}
		}
		
		if($result){
			logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis asigno al productid "'.$this->getProductId().'" el campo "'.$this->getData('Nombre').'" con valor "'.$this->getData('Valor').'"');
			return true;
		}
		else
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al asignar al productid "'.$this->getProductId().'" el campo "'.$this->getData('Nombre').'" con valor "'.$this->getData('Valor').'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
	}
	
	private function editAttribute() {
		$fieldId = $this->getFieldId();
		if(!$fieldId)
		{
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro el fieldid del attributo con ID "'.$this->getAttribute('IDAtributo').'".<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		if($GLOBALS["ISC_CLASS_DB"]->UpdateQuery('product_customfields', array('fieldname' => $this->getData('Nombre'), 'fieldvalue' => $this->getData('Valor')), 'fieldid = "'.$fieldId.'"'))
		{
			logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis actualizo el atributo ID "'.$fieldId.'" nombre "'.$this->getData('Nombre').'" valor "'.$this->getData('Valor').'"');
			return true;
		}
		else
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al cambiar el atributo ID "'.$fieldId.'" nombre "'.$this->getData('Nombre').'" valor "'.$this->getData('Valor').'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}

	}

	private function deleteAttribute() {
		$fieldId = $this->getFieldId();
		if(!$fieldId)
		{
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro el fieldid del attributo con ID "'.$this->getAttribute('IDAtributo').'".<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		if($GLOBALS["ISC_CLASS_DB"]->DeleteQuery('product_customfields', 'WHERE fieldid = "'.$fieldId.'"'))
		{
			logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis elimino el atributo ID "'.$fieldId.'"');
			return true;
		}
		else
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al eliminar el atributo ID "'.$fieldId.'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
	}
}