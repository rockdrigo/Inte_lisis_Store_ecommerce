<?php
/*
 * REQ11552 - NES: Clase para procesar catalogos de Dias Festivos, para calculo de Fecha de entrega dependiendo color (Situacion) de Articulo
 */

class ISC_INTELISIS_ECOMMERCE_DIAFESTIVO extends ISC_INTELISIS_ECOMMERCE {
	
	public function create() {
		$created = array();
		$updated = array();
		foreach($this->getDataElement() as $fecha) {
			$id = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT id FROM [|PREFIX|]intelisis_festivedays WHERE Fecha = "'.$fecha['Fecha'].'"');
			if(!$id) {
				$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_festivedays', array('Fecha' => $fecha['Fecha'], 'EsLaborable' => '0'));
				$created[] = $fecha['Fecha'];
			}
			else {
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_festivedays', array('EsLaborable' => '0'), 'id = "'.$id.'"');
				$updated[] = $fecha['Fecha'];
			}
			
			if($GLOBALS['ISC_CLASS_DB']->Error() != '') {
				logAdd(LOG_SEVERITY_ERROR, 'Ocurrio un error al hacer '.$this->getAttribute('Estatus').' al Dia Festivo '.$fecha['Fecha'].'. Archivo: '.$this->getXMLfilename().'.<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
		}
		
		logAdd(LOG_SEVERITY_SUCCESS, 'Se proceso el archivo de Dias Festivos '.$this->getXMLfilename().'. Creados: '.implode(',', $created).' Editados: '.implode(',', $updated));
		return true;
	}
	
	public function update() {
		return $this->create();
	}
	
	public function delete() {
		$deleted = array();
		foreach($this->getDataElement() as $fecha) {
			$deleted[] = $fecha['Fecha'];
		}
		
		if(!empty($deleted)) {
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('intelisis_festivedays', 'WHERE Fecha IN ("'.implode('","', $deleted).'")');
			if($GLOBALS['ISC_CLASS_DB']->Error() == ''){
				logAdd(LOG_SEVERITY_SUCCESS, 'Se proceso el archivo de Dias Festivos '.$this->getXMLfilename().'. Eliminados: '.implode(',', $deleted));
				return true;
			}
			else {
				logAdd(LOG_SEVERITY_ERROR, 'Ocurrio un error al tratar de eliminar los dias festivos del archivo '.$this->getXMLfilename().'. Error: '.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
		}
	}
}