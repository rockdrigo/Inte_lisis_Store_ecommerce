<?php

class ISC_ECOMMERCE_INTELISIS_WEBUSUARIO extends ISC_ECOMMERCE_INTELISIS
{
	public function processResult() {
		if($this->getAttribute('Ok') == '') {
			if($guid = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT GUID FROM [|PREFIX|]intelisis_customers WHERE GUID = "'.$this->getHeader('SubReferencia').'"', 'GUID'))
			{
				// BUG10201 - Validacion por si estos valores son nulos o vacios, que no los sobreescriba
				$this->getAttribute('Cliente') == '' ? '' : $update['Cliente'] = $this->getAttribute('Cliente');
				$this->getAttribute('WebUsuarioID') == '' ? '' : $update['IDWebUsuario'] = $this->getAttribute('WebUsuarioID'); 

				if(!(empty($update))){
					$GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_customers', $update, 'GUID = "'.$guid.'"');
				}
				logAdd(LOG_SEVERITY_SUCCESS, 'Se recibio la respuesta del customerid "'.$this->getHeader('SubReferencia').'"' );
				return true;
			}
			else
			{
				logAdd(LOG_SEVERITY_ERROR, 'No se encontro el GUID de intelisis_customers "'.$this->getHeader('SubReferencia').'"' );
				return false;
			}
		}
		else {
			logAdd(LOG_SEVERITY_ERROR, 'Se recibio el Resultado de IntelisisService ID '.$this->getAttribute('IntelisisServiceID').' con error Ok '.$this->getAttribute('Ok').' OkRef "'.$this->getAttribute('OkRef').'"' );
			return true;
		}
	}
}
