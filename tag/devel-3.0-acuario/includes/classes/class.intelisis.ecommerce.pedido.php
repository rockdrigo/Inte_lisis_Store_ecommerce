<?php

//A proposito es al revez ya que intelisis tiene la referencia asi 
class ISC_INTELISIS_ECOMMERCE_PEDIDO extends ISC_ECOMMERCE_INTELISIS
{
	public function processResult() {
		if($this->getAttribute('Ok') == '') {
			if($guid = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT GUID FROM [|PREFIX|]intelisis_orders WHERE GUID = "'.$this->getHeader('SubReferencia').'"', 'GUID'))
			{
				$update = array (
					'VentaID' => $this->getAttribute('ModuloID'),
				);
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_orders', $update, 'GUID = "'.$guid.'"');
				logAdd(LOG_SEVERITY_SUCCESS, 'Se recibio la respuesta de la orderid "'.$this->getHeader('SubReferencia').'"');
				return true;
			}
			else
			{
				logAdd(LOG_SEVERITY_ERROR, 'No se encontro el GUID de intelisis_orders "'.$this->getHeader('SubReferencia').'"');
				return false;
			}
		}
		else {
			logAdd(LOG_SEVERITY_ERROR, 'Se recibio el Resultado de IntelisisService ID '.$this->getAttribute('IntelisisServiceID').' con error Ok '.$this->getAttribute('Ok').' OkRef "'.$this->getAttribute('OkRef').'"');
			return false;
		}
	}
}
