<?php
require_once(ISC_BASE_PATH . '/lib/addressvalidation.php');

class ISC_INTELISIS_ECOMMERCE_WEBMOVSITUACION extends ISC_INTELISIS_ECOMMERCE {
	
	public function create() {
		logAddNotice('No se hace nada para eventos "ALTA" de WebMovSituacion');
		return true;
	}
	
	public function update() {
		$IDSituacion = $this->getData('SituacionID');
		$MovID = $this->getData('IDOrigen');
		
		if(!$status = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT statusid FROM [|PREFIX|]intelisis_order_status WHERE IDWebSituacion = "'.$IDSituacion.'"', 'statusid')){
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro el statusid de la Situacion eCommerce "'.$IDSituacion.'"<br/>. '.$GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
		if(!$order_id = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT orderid FROM [|PREFIX|]intelisis_orders WHERE VentaID = "'.$MovID.'"', 'orderid')){
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro la orderid de la VentaID "'.$MovID.'"<br/>. '.$GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}

		$order = GetOrder($order_id);
		$old_status = $order['ordstatus'];
		
		if (UpdateOrderStatus($order_id, $status)) {
			logAdd(LOG_SEVERITY_SUCCESS, 'Se cambio el estatus de la orderid "'.$order_id.'" de "'.$old_status.'" a "'.$status.'"');
			return true;
		}
		else {
			logAdd(LOG_SEVERITY_ERROR, 'Ocurrio un error al intentar cambiar el estatus de la orderid "'.$order_id.'" de "'.$old_status.'" a "'.$status.'"');
			return false;
		}
	}
	
	public function delete() {
		logAddNotice('No se hace nada para eventos "BAJA" de WebMovSituacion');
		return true;		
	}
}