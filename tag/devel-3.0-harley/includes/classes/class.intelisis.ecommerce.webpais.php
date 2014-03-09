<?php

class ISC_INTELISIS_ECOMMERCE_WEBPAIS extends ISC_INTELISIS_ECOMMERCE
{
	public function ProcessData() {
		if($this->getXMLdom())
		{
			switch ($this->getAttribute('Estatus')) {
				case 'ALTA':
					return $this->createUpdateCountry();
				break;
				case 'CAMBIO':
					return $this->createUpdateCountry();
				break;
				case 'BAJA':
					return $this->deleteCountry();
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

	private function createUpdateCountry() {
		$country = array(
			'countryid' => $this->getData('ID'),
			/*'countrycouregid' => '',*/ //Nulo mientras no me mande region
			'countryname' => $this->getData('Nombre'),
			'countryiso2' => $this->getData('Clave2'),
			'countryiso3' => $this->getData('Clave3'),
		);

		$existingId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT countryid FROM [|PREFIX|]countries WHERE countryid = "'.$this->getAttribute('IDPais').'"', 'countryid');

		if(!$existingId)
		{
			if($newid = $GLOBALS['ISC_CLASS_DB']->InsertQuery('countries', $country))
			{
				logAdd(LOG_SEVERITY_SUCCESS, 'Se creo el Pais "'.$this->getData('Nombre').'" con id "'.$newid.'"');
				return true;
			}
			else
			{
				logAdd(LOG_SEVERITY_ERROR, 'Error al crear el Pais "'.$this->getData('Nombre').'" con id "'.$newid.'". Archivo: '.$this->getXMLfilename().'<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
		}
		else {
			if($GLOBALS['ISC_CLASS_DB']->UpdateQuery('countries', $country, 'countryid = "'.$existingId.'"'))
			{
				logAdd(LOG_SEVERITY_SUCCESS, 'Se edito el Pais "'.$this->getData('Nombre').'" con id "'.$existingId.'"');
				return true;
			}
			else
			{
				logAdd(LOG_SEVERITY_ERROR, 'Error al editar el Pais "'.$this->getData('Nombre').'" con id "'.$existingId.'". Archivo: '.$this->getXMLfilename().'<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
		}
	}
	
	private function deleteCountry() {
		$existingId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT countryid FROM [|PREFIX|]countries WHERE countryid = "'.$this->getAttribute('IDPais').'"', 'countryid');
		
		if($GLOBALS['ISC_CLASS_DB']->DeleteQuery('countries', 'WHERE countryid = "'.$existingId.'"'))
		{
			logAdd(LOG_SEVERITY_SUCCESS, 'Se elimino el Pais con ID "'.$existingId.'"');
			return true;
		}
		else
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al eliminar el Pais ID "'.$existingId.'". Archivo: '.$this->getXMLfilename().'<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
	}
}