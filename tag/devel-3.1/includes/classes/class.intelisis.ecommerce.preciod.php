<?php

class ISC_INTELISIS_ECOMMERCE_PRECIOD extends ISC_INTELISIS_ECOMMERCE_CATALOGO
{
	public $tablename = 'intelisis_preciod';
	public $pk = array(
			'GUID' => 'GUID'
	);
	
	public function getTableArray() {
		$Arreglo = array(
			'ID' => $this->getData('ID'),
			'Cantidad' => $this->getData('Cantidad'),
			'Monto' => $this->getData('Monto'),
			'Sucursal' => $this->getData('Sucursal'),
			'GUID' => $this->getAttribute('GUID'),
		);
		return $Arreglo;
	}
}
