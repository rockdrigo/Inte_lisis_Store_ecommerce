<?php
/*
 * REQ11552 - NES: Clase para procesar catalogos de Situaciones de articulo, con su fecha y dias de entrega y si es descontinuado.
 */

class ISC_INTELISIS_ECOMMERCE_WEBARTSITUACION extends ISC_INTELISIS_ECOMMERCE {
	
	public function create() {
		$error = false;
		foreach($this->getDataElement() as $element) {
			$situacion = isset($element['Situacion']) ? $element['Situacion'] : '';
			if($situacion == '') {
				logAdd(LOG_SEVERITY_ERROR, 'No se encontro la clave de situacion en el archivo '.$this->getXMLfilename());
				$error = true;
			}
			$row = array(
				'Situacion' => $situacion,
				'Descontinuado' => isset($element['Descontinuado']) ? $element['Descontinuado'] : '',
				'DiasEntrega' => isset($element['DiasEntrega']) ? $element['DiasEntrega'] : '',
				'PeriodoEntrega' => isset($element['PeriodoEntrega']) ? $element['PeriodoEntrega'] : '',
			);
			
			if(!$GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT Situacion FROM [|PREFIX|]intelisis_prodstatus WHERE Situacion = "'.$situacion.'"', 'Situacion')){
				if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_prodstatus', $row)){
					logAdd(LOG_SEVERITY_ERROR, 'Error al insertar el registro de la WebArtSituacion "'.$situacion.'". Archivo: '.$this->getXMLfilename().'.<br/>Error: '.$GLOBALS['ISC_CLASS_DB']->Error());
					$error = true;
				}
			}
			else {
				if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_prodstatus', $row, 'Situacion = "'.$situacion.'"')){
					logAdd(LOG_SEVERITY_ERROR, 'Error al editar el registro de la WebArtSituacion "'.$situacion.'". Archivo: '.$this->getXMLfilename().'.<br/>Error: '.$GLOBALS['ISC_CLASS_DB']->Error());
					$error = true;
				}
			}
			
			
		}
		
		if(!$error) {
			logAdd(LOG_SEVERITY_SUCCESS, 'Se proceso el archivo de WebArtSituacion '.$this->getXMLfilename());
			return true;
		}
		else {
			logAdd(LOG_SEVERITY_ERROR, 'Error al procesar el archivo de WebArtSituacion '.$this->getXMLfilename());
			return false;
		}
	}
	
	public function update() {
		return $this->create();
	}
	
	public function delete() {
		logAdd(LOG_SEVERITY_WARNING, 'Todavia no se ha definido el evento de BAJA para objetos '.get_class($this));
		return false;
	}
}