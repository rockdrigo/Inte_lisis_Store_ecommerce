<?php

class ISC_INTELISIS_ECOMMERCE_PCD extends ISC_INTELISIS_ECOMMERCE_CATALOGO {

	public $tablename = 'intelisis_PCD';
	public $pk = array(
			'ID' => 'PCDID',
			'Renglon' => 'Renglon'
			);
			
	public function getTableArray() { //Llenar dependiendo del XML
		$Arreglo = array(
			'ID' => $this->getData('ID'),
			'Renglon' => $this->getData('Renglon'),
			'Articulo' => $this->getData('Articulo'),
			'SubCuenta' => $this->getData('SubCuenta'),
			'Unidad' => $this->getData('Unidad'),
			'Nuevo' => $this->getData('Nuevo', 0),
			'Anterior' => $this->getData('Anterior', 0),
			'Baja' => $this->getData('Baja'),
			'ExistenciaSucursal' => $this->getData('ExistenciaSucursal', null),
			'ListaModificar' => $this->getData('ListaModificar'),
			'SucursalEsp' => $this->getData('SucursalEsp', null),
			'Monto' => $this->getData('Monto', 0),
			'Sucursal' => $this->getData('Sucursal', null),
			'SucursalOrigen' => $this->getData('SucursalOrigen', null),
			'CostoBase' => $this->getData('CostoBase', 0)
		);		
		return $Arreglo;
	}

}