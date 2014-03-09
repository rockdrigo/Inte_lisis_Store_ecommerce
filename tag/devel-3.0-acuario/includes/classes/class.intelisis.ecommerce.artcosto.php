<?php

class ISC_INTELISIS_ECOMMERCE_ARTCOSTO extends ISC_INTELISIS_ECOMMERCE_CATALOGO {

	public $tablename = 'intelisis_ArtCosto';
	public $pk = array(
		'Articulo' => 'Articulo',
		'Sucursal' => 'Sucursal',
		'Empresa' => 'Empresa',
	);

	public function getTableArray() {
		$dataArray = $this->getDataElement();
		if(empty($dataArray)) {
			return false;
		}
		
		$Arreglo = array(
			'Sucursal' => $this->getData('Sucursal'),
			'Empresa' => $this->getData('Empresa'),
			'Articulo' => $this->getData('Articulo'),
			'UltimoCosto' => $this->getData('UltimoCosto', 0),
			'CostoPromedio' => $this->getData('CostoPromedio', 0),
			'CostoEstandar' => $this->getData('CostoEstandar', 0),
			'CostoReposicion' => $this->getData('CostoReposicion', 0),
			'UltimoCostoSinGastos' => $this->getData('UltimoCostoSinGastos', 0),
		);
		return $Arreglo;
	}

}