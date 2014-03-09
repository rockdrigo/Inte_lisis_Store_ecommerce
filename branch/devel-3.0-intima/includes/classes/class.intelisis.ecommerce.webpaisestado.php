<?php

class ISC_INTELISIS_ECOMMERCE_WEBPAISESTADO extends ISC_INTELISIS_ECOMMERCE
{
	public function ProcessData() {
		if($this->getXMLdom())
		{
			switch ($this->getAttribute('Estatus')) {
				case 'ALTA':
					return $this->createUpdateState();
				break;
				case 'CAMBIO':
					return $this->createUpdateState();
				break;
				case 'BAJA':
					return $this->deleteState();
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

	private function createUpdateState() {
		$state = array(
			'stateid' => $this->getData('IDEstado') == '' ? $this->getData('ID') : $this->getData('IDEstado'),
			'statename' => $this->getData('Nombre'),
			'statecountry' => $this->getData('IDPais'),
			'stateabbrv' => $this->getData('Clave'),
		);

		$existingId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT stateid FROM [|PREFIX|]country_states WHERE stateid = "'.$state['stateid'].'"', 'stateid');

		if(!$existingId)
		{
			if($newid = $GLOBALS['ISC_CLASS_DB']->InsertQuery('country_states', $state))
			{
				logAdd(LOG_SEVERITY_SUCCESS, 'Se creo el Estado "'.$this->getData('Nombre').'" con id "'.$newid.'"');
				return true;
			}
			else
			{
				logAdd(LOG_SEVERITY_ERROR, 'Error al crear el Estado "'.$this->getData('Nombre').'" con id "'.$newid.'". Archivo: '.$this->getXMLfilename().'<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
		}
		else {
			if($GLOBALS['ISC_CLASS_DB']->UpdateQuery('country_states', $state, 'stateid = "'.$existingId.'"'))
			{
				logAdd(LOG_SEVERITY_SUCCESS, 'Se edito el Estado "'.$this->getData('Nombre').'" con id "'.$existingId.'"');
				return true;
			}
			else
			{
				logAdd(LOG_SEVERITY_ERROR, 'Error al editar el Estado "'.$this->getData('Nombre').'" con id "'.$existingId.'". Archivo: '.$this->getXMLfilename().'<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
		}
	}
	
	private function deleteState() {
		$existingId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT stateid FROM [|PREFIX|]country_states WHERE stateid = "'.$this->getAttribute('IDEstado').'"', 'stateid');
		
		if($GLOBALS['ISC_CLASS_DB']->DeleteQuery('country_states', 'WHERE stateid = "'.$existingId.'"'))
		{
			logAdd(LOG_SEVERITY_SUCCESS, 'Se elimino el Estado con ID "'.$existingId.'"');
			return true;
		}
		else
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al eliminar el Estado ID "'.$existingId.'". Archivo: '.$this->getXMLfilename().'<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
	}
}