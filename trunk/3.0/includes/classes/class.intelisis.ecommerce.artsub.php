<?php

class ISC_INTELISIS_ECOMMERCE_ARTSUB extends ISC_INTELISIS_ECOMMERCE_CATALOGO {

	public $tablename = 'intelisis_ArtSub';
	public $pk = array(
			'Articulo' => 'Articulo',
			'SubCuenta' => 'SubCuenta'
			);
			
	public function getTableArray() { //Llenar dependiendo del XML
		$Arreglo = array(
			'Articulo' => $this->getData('Articulo'),
			'SubCuenta' => $this->getData('SubCuenta'),
			'CostoEstandar' => $this->getData('CostoEstandar'),
			'CostoReposicion' => $this->getData('CostoReposicion'),
			'TieneMovimientos' => $this->getData('TieneMovimientos'),
			'Fabricante' => $this->getData('Fabricante'),
			'ClaveFabricante' => $this->getData('ClaveFabricante'),
			'Horas' => $this->getData('Horas'),
			'Minutos' => $this->getData('Minutos'),
		);		
		return $Arreglo;
	}

}