<?php

class ISC_INTELISIS_ECOMMERCE_WEBESTATUSEXISTENCIA extends ISC_INTELISIS_ECOMMERCE_CATALOGO {

	public $tablename = 'intelisis_prodstatus';
	public $pk = array(
		'Situacion' => 'WebEstatusExistencia',
	);

	public function getTableArray() {
		$dataArray = $this->getDataElement();
		if(empty($dataArray)) {
			return false;
		}
		
		$Arreglo = array(
			'Situacion' => $this->getData('WebEstatusExistencia'),
			'Descontinuado' => $this->getData('VentaPermitir') == 1 ? '0' : '1', //Las banderas estan al revez en Intelisis y la original
			'DiasEntrega' => $this->getData('EntregaDias'),
			'PeriodoEntrega' => $this->getData('PeriodoEntrega', 'Lun-Dom'),
		);
		return $Arreglo;
	}

}