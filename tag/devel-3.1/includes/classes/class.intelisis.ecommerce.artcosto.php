<?php

class ISC_INTELISIS_ECOMMERCE_ARTCOSTO extends ISC_INTELISIS_ECOMMERCE_CATALOGO {

	public $tablename = 'intelisis_artcosto';
	public $pk = array(
			'Sucursal' => 'Sucursal',
			'Empresa' => 'Empresa',
			'Articulo' => 'Articulo'
			);
			
	public function getTableArray() { //Llenar dependiendo del XML
		$Arreglo = array(
			'Sucursal' => $this->getData('Sucursal'),
			'Empresa' => $this->getData('Empresa'),
			'Articulo' => $this->getData('Articulo'),
			'UltimoCosto' => $this->getData('UltimoCosto'),
			'CostoPromedio' => $this->getData('CostoPromedio'),
			'CostoEstandar' => $this->getData('CostoEstandar'),
			'CostoReposicion' => $this->getData('CostoReposicion'),
			'UltimoCostoSinGastos' => $this->getData('UltimoCostoSinGastos')
		);		
		return $Arreglo;
	}

}