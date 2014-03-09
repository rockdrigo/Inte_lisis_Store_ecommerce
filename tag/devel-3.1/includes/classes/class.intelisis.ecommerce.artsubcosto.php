<?php

class ISC_INTELISIS_ECOMMERCE_ARTSUBCOSTO extends ISC_INTELISIS_ECOMMERCE_CATALOGO {

	public $tablename = 'intelisis_artsubcosto';
	public $pk = array(
			'Sucursal' => 'Sucursal',
			'Empresa' => 'Empresa',
			'Articulo' => 'Articulo',
			'SubCuenta' => 'SubCuenta'
			);
			
	public function getTableArray() { //Llenar dependiendo del XML
		$Arreglo = array(
			'Sucursal' => $this->getData('Sucursal'),
			'Empresa' => $this->getData('Empresa'),
			'Articulo' => $this->getData('Articulo'),
			'SubCuenta' => $this->getData('SubCuenta'),
			'UltimoCosto' => $this->getData('UltimoCosto'),
			'CostoPromedio' => $this->getData('CostoPromedio'),
			'CostoEstandar' => $this->getData('CostoEstandar'),
			'CostoReposicion' => $this->getData('CostoResposicion'),
			'UltimoCostoSinGastos' => $this->getData('UltimoCostoSinGastos'),
		);		
		return $Arreglo;
	}

}