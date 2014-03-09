<?php

class ISC_INTELISIS_ECOMMERCE_WEBARTOPCION extends ISC_INTELISIS_ECOMMERCE
{
	public function ProcessData() {
		if($this->getXMLdom())
		{
			switch ($this->getAttribute('Estatus')) {
				case 'ALTA':
					logAddNotice('La Interfaz Intelisis no procesa ALTA de Referencias Intelisis.eCommerce.WebArtOpcion. Las Opciones de Variacion se crean con el objeto WebArtOpcionValor.<br/>El Archivo "'.$this->getXMLfilename().'" sera marcado como Procesado.');
					return true;
				break;
				case 'CAMBIO':
					return $this->updateOption();
				break;
				case 'BAJA':
					return $this->deleteOption();
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
	
	private function getVariationId() {
		$query = "SELECT variationid FROM [|PREFIX|]intelisis_variations WHERE VariacionID = '".$this->getAttribute('VariacionID')."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	
		return $row['variationid'] ? $row['variationid'] : false;
	}
	
	private function getOptionName() {
		$query = "SELECT Nombre FROM [|PREFIX|]intelisis_variation_options WHERE VariacionID = '".$this->getAttribute('VariacionID')."' AND OpcionID = '".$this->getAttribute('OpcionID')."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	
		return $row['Nombre'] ? $row['Nombre'] : false;
	}
	
	private function updateOption() {
		$variationid = $this->getVariationId();
		if(!$variationid)
		{
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro la variationid para la Variacion Web ID "'.$this->getAttribute('VariacionID').'"');
			return false;
		}
		
		$optionName = $this->getOptionName();
		if(!$optionName)
		{
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro la opcion ID "" para la Variacion Web ID "'.$this->getAttribute('VariacionID').'"');
			return false;
		}
		
		$updateFields = array(
			'voname' => $this->getData('Nombre'),
		);
		
		if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('product_variation_options', $updateFields, 'vovariationid = "'.$variationid.'" AND voname = "'.$optionName.'"'))
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al cambiar el nombre de la opcion "'.$optionName.'" a "'.$this->getData('Nombre').'". '.$GLOBALS['ISC_CLASS_DB']->Error().'<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		else
		{
			if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_variation_options', array('Nombre' => $this->getData('Nombre')), 'VariacionID = "'.$this->getAttribute('VariacionID').'" AND OpcionID = "'.$this->getAttribute('OpcionID').'"')){
				logAdd(LOG_SEVERITY_ERROR, 'Error al cambiar el registro de la opcion "'.$optionName.'".'.$GLOBALS['ISC_CLASS_DB']->Error().'<br/>Archivo: '.$this->getXMLfilename());
				return false;
			}
			else
			{
				logAdd(LOG_SEVERITY_SUCCESS, 'Se cambio el nombre de la opcion "'.$optionName.'" a "'.$this->getData('Nombre').'"');
				return true;
			}
		}
	}

	private function deleteOption() {
		$variationid = $this->getVariationId();
		if(!$variationid)
		{
			logAddNotice('No se encontro la variationid para la Variacion Web ID "'.$this->getAttribute('VariacionID').'". Es posible que ya haya sido eliminada.<br/>Archivo: '.$this->getXMLfilename());
			if(!$GLOBALS['ISC_CLASS_DB']->DeleteQuery('intelisis_variation_options', 'WHERE VariacionID = "'.$this->getAttribute('VariacionID').'" AND OpcionID = "'.$this->getAttribute('OpcionID').'"')){
				logAddNotice('Error al eliminar el registro de la opcion "'.$optionName.'".'.$GLOBALS['ISC_CLASS_DB']->Error().'.<br/>Archivo: '.$this->getXMLfilename());
			}
			return true;
		}
		
		$optionName = $this->getOptionName();
		if(!$optionName)
		{
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro la opcion ID "" para la Variacion Web ID "'.$this->getAttribute('VariacionID').'"');
			return false;
		}
		
		if(!$GLOBALS['ISC_CLASS_DB']->DeleteQuery('product_variation_options', 'WHERE vovariationid = "'.$variationid.'" AND voname = "'.$optionName.'"'))
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al eliminar la opcion "'.$optionName.'" '.$GLOBALS['ISC_CLASS_DB']->Error().'<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		else
		{
			logAddNotice('eliminando :'.$this->getAttribute('VariacionID')." ".$this->getAttribute('OpcionID'));
			if(!$GLOBALS['ISC_CLASS_DB']->DeleteQuery('intelisis_variation_options', 'WHERE VariacionID = "'.$this->getAttribute('VariacionID').'" AND OpcionID = "'.$this->getAttribute('OpcionID').'"')){
				logAdd(LOG_SEVERITY_ERROR, 'Error al eliminar el registro de la opcion "'.$optionName.'".'.$GLOBALS['ISC_CLASS_DB']->Error().'<br/>Archivo: '.$this->getXMLfilename());
				return false;
			}
			else
			{
				logAdd(LOG_SEVERITY_SUCCESS, 'Se elimino la opcion "'.$optionName.'"');
				return true;
			}
		}
	}
}