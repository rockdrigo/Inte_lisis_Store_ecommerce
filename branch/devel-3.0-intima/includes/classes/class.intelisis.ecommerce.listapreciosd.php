<?php

class ISC_INTELISIS_ECOMMERCE_LISTAPRECIOSD extends ISC_INTELISIS_ECOMMERCE_CATALOGO
{
	public $tablename = 'intelisis_ListaPreciosD';
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
			'Precio' => $this->getData('Precio'),
			'CodigoCliente' => $this->getData('CodigoCliente'),
			'Margen' => $this->getData('Margen'),
			'Region' => $this->getData('Region'),
		);
		return $Arreglo;
	}
}
