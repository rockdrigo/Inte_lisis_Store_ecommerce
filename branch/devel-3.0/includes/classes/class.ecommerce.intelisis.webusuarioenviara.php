<?php

class ISC_ECOMMERCE_INTELISIS_WEBUSUARIOENVIARA extends ISC_ECOMMERCE_INTELISIS
{
	public function processResult() {
		if($this->getAttribute('Ok') == '') {
			$result = $GLOBALS['ISC_CLASS_DB']->Query('SELECT * FROM [|PREFIX|]intelisis_shipping_addresses WHERE GUID = "'.$this->getHeader('SubReferencia').'"');
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			$guid = $row['GUID'];
			if($guid != '')
			{
				if($guid == '0' || $guid == '1') $guid = gen_uuid();
				// BUG10201 - Validacion por si estos valores son nulos o vacios, que no los sobreescriba
				$this->getAttribute('Cliente') == '' ? '' : $update['Cliente'] = $this->getAttribute('Cliente');
				$update['IDEnviarA'] = $this->getAttribute('EnviarAID') != '' ? $this->getAttribute('EnviarAID') : $row['IDEnviarA'];

				//if($this->getAttribute('EnviarAID') == '') $update['IDEnviarA'] = '1';
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_shipping_addresses', $update, 'GUID = "'.$guid.'"');
				logAdd(LOG_SEVERITY_SUCCESS, 'Se recibio la respuesta del shipid "'.$this->getHeader('SubReferencia').'"' );
				return true;
			}
			else
			{
				logAdd(LOG_SEVERITY_ERROR, 'No se encontro el GUID de intelisis_shipping_addresses "'.$this->getHeader('SubReferencia').'"' );
				return false;
			}
		}
		else {
			logAdd(LOG_SEVERITY_ERROR, 'Se recibio el Resultado de IntelisisService ID '.$this->getAttribute('IntelisisServiceID').' con error Ok '.$this->getAttribute('Ok').' OkRef "'.$this->getAttribute('OkRef').'"' );
			return true;
		}
	}
}
