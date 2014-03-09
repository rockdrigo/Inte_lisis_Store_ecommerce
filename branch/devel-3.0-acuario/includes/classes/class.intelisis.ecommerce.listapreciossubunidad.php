<?php

class ISC_INTELISIS_ECOMMERCE_LISTAPRECIOSSUBUNIDAD extends ISC_INTELISIS_ECOMMERCE_CATALOGO
{
	public $tablename = 'intelisis_ListaPreciosSubUnidad';
	public $pk = array(
			'Lista' => 'Lista',
			'Moneda' => 'Moneda',
			'Articulo' => 'Articulo',
			'SubCuenta' => 'SubCuenta',
			'Unidad' => 'Unidad',
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
			'SubCuenta' => $this->getData('SubCuenta'),
			'Unidad' => $this->getData('Unidad'),
			'Unidad' => $this->getData('Unidad'),
			'Precio' => $this->getData('Precio'),
			'Region' => $this->getData('Region'),
		);
		return $Arreglo;
	}
}
