<?php

class ISC_INTELISIS_ECOMMERCE_LISTAPRECIOSDUNIDAD extends ISC_INTELISIS_ECOMMERCE_CATALOGO
{
	public $tablename = 'intelisis_ListaPreciosDUnidad';
	public $pk = array(
			'Lista' => 'Lista',
			'Moneda' => 'Moneda',
			'Articulo' => 'Articulo',
	);
	
	public function getTableArray() {
		$dataArray = $this->getDataElement();
		if(empty($dataArray)) {
			return false;
		}
		
		$Arreglo = array(
			'Lista' => $this->getData('Lista'),
			'Moneda' => $this->getData('Moneda'),
			'Articulo' => $this->getData('Articulo'),
			'Unidad' => $this->getData('Unidad'),
			'Precio' => $this->getData('Precio'),
			'Region' => $this->getData('Region'),
		);
		return $Arreglo;
	}
}
