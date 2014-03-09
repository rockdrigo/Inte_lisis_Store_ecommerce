<?php

class ISC_INTELISIS_ECOMMERCE_WEBSITUACION extends ISC_INTELISIS_ECOMMERCE {
	
	public function ProcessData() {
		if($this->getXMLdom())
		{
			//printe($this->getAttribute('Estatus').": ".$this->getAttribute('Cliente'));
			switch ($this->getAttribute('Estatus')) {
				case 'ALTA':
					return $this->createUpdateStatus();
				break;
				case 'CAMBIO':
					return $this->createUpdateStatus();
				break;
				case 'BAJA':
					return $this->deleteStatus();
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

	private function createUpdateStatus() {
		$order = 0;
		$status = array(
			/*'statusid' => $this->getData('ID'),*/
			'statusdesc' => $this->getData('Nombre'),
			'statusorder' => $order,
		);
		$existingId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT statusid FROM [|PREFIX|]intelisis_order_status WHERE IDWebSituacion = "'.$this->getAttribute('IDSituacion').'"', 'statusid');
		
		$return = false;
		if($existingId)
		{
			if($GLOBALS['ISC_CLASS_DB']->UpdateQuery('order_status', $status, 'statusid = "'.$existingId.'"'))
			{
				logAdd(LOG_SEVERITY_SUCCESS, 'Se actualizo el Estatus de pedido id "'.$existingId.'" nombre "'.$this->getData('Nombre').'"');
				$return  = true;
			}
			else
			{
				logAdd(LOG_SEVERITY_ERROR, 'Error al actualizar el Estatus de pedido id "'.$existingId.'" nombre "'.$this->getData('Nombre').'". Archivo: '.$this->getXMLfilename()."<br/>".$GLOBALS['ISC_CLASS_DB']->Error());
				$return = false;
			}
		}
		else
		{
			if($existingId = $GLOBALS['ISC_CLASS_DB']->InsertQuery('order_status', $status))
			{
				$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_order_status', array('statusid' => $existingId, 'IDWebSituacion' => $this->getAttribute('IDSituacion')));
				logAdd(LOG_SEVERITY_SUCCESS, 'Se creo el Estatus de pedido id "'.$existingId.'" nombre "'.$this->getData('Nombre').'"');
				$return = true;
			}
			else
			{
				logAdd(LOG_SEVERITY_ERROR, 'Error al crear el Estatus de pedido id "'.$existingId.'" nombre "'.$this->getData('Nombre').'". Archivo: '.$this->getXMLfilename()."<br/>".$GLOBALS['ISC_CLASS_DB']->Error());
				$return = false;
			}
		}
		
		$s = GetClass('ISC_ADMIN_SETTINGS');
		$oldNotifs = explode(',', GetConfig('OrderStatusNotifications'));
		if(in_array($existingId, $oldNotifs))
		{
			$key = array_search($existingId, $oldNotifs);
			if($this->getData('EnviarCorreo') == 0) unset($oldNotifs[$key]);
		}
		else 
		{
			if($this->getData('EnviarCorreo') == 1) $oldNotifs[] = $existingId;
		}
		$GLOBALS['ISC_NEW_CFG']['OrderStatusNotifications'] = implode(',', $oldNotifs);
		$s->CommitSettings();
		
		return $return;
	}
		
	private function deleteStatus() {
		$existingId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT statusid FROM [|PREFIX|]intelisis_order_status WHERE IDWebSituacion = "'.$this->getAttribute('IDSituacion').'"', 'statusid');
		
		$s = GetClass('ISC_ADMIN_SETTINGS');
		$oldNotifs = explode(',', GetConfig('OrderStatusNotifications'));
		if(in_array($existingId, $oldNotifs))
		{
			$key = array_search($existingId, $oldNotifs);
			unset($oldNotifs[$key]);
		}

		$GLOBALS['ISC_NEW_CFG']['OrderStatusNotifications'] = implode(',', $oldNotifs);
		$s->CommitSettings();
		
		if($existingId && $GLOBALS['ISC_CLASS_DB']->DeleteQuery('order_status', 'WHERE statusid = "'.$existingId.'"'))
		{
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('intelisis_order_status', 'WHERE statusid = "'.$existingId.'"');
			logAdd(LOG_SEVERITY_SUCCESS, 'Se elimino el Estatus de pedido id "'.$existingId.'"');
			return true;
		}
		else
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al actualizar el Estatus de pedido id "'.$existingId.'". Archivo: '.$this->getXMLfilename()."<br/>".$GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
	}
}