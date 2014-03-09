<?php

class ISC_INTELISIS_ECOMMERCE_ARTPROVSUCURSAL extends ISC_INTELISIS_ECOMMERCE_CATALOGO {

	public $tablename = 'intelisis_artprovsucursal';
	public $pk = array(
			'Articulo' => 'Articulo',
			'SubCuenta' => 'SubCuenta',
			'Proveedor' => 'Proveedor',
			'Sucursal' => 'Sucursal'
			);
			
	public function getTableArray() { //Llenar dependiendo del XML
		$Arreglo = array(
			'Articulo' => $this->getData('Articulo'),
			'SubCuenta' => $this->getData('SubCuenta'),
			'Proveedor' => $this->getData('Proveedor'),
			'Sucursal' => $this->getData('Sucursal'),
			'CostoAutorizado' => $this->getData('CostoAutorizado'),
			'UltimoCosto' => $this->getData('UltimoCosto'),
			'UltimaCompra' => $this->getData('UltimaCompra'),
			'CompraMinimaCantidad' => $this->getData('CompraMinimaCantidad'),
			'CompraMinimaImporte' => $this->getData('CompraMinimaImporte'),
			'Multiplos' => $this->getData('Multiplos'),
			'DiasRespuesta' => $this->getData('DiasRespuesta'),
			'UltimaCotizacion' => $this->getData('UltimaCotizacion'),
			'FechaCotizacion' => $this->getData('FechaCotizacion')
		);		
		return $Arreglo;
	}

}