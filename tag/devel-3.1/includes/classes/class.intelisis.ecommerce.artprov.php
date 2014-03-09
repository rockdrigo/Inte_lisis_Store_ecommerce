<?php

class ISC_INTELISIS_ECOMMERCE_ARTPROV extends ISC_INTELISIS_ECOMMERCE_CATALOGO {

	public $tablename = 'intelisis_artprov';
	public $pk = array(
			'Articulo' => 'Articulo',
			'SubCuenta' => 'SubCuenta',
			'Proveedor' => 'Proveedor'
			);
			
	public function getTableArray() { //Llenar dependiendo del XML
		$Arreglo = array(
			'Articulo' => $this->getData('Articulo'),
			'SubCuenta' => $this->getData('SubCuenta'),
			'Proveedor' => $this->getData('Proveedor'),
			'Clave' => $this->getData('Clave'),
			'Unidad' => $this->getData('Unidad'),
			'CostoAutorizado' => $this->getData('CostoAutorizado'),
			'UltimoCosto' => $this->getData('UltimoCosto'),
			'UltimaCompra' => $this->getData('UltimaCompra'),
			'CompraMinimaCantidad' => $this->getData('CompraMinimaCantidad'),
			'CompraMinimaImporte' => $this->getData('CompraMinimaImporte'),
			'Multiplos' => $this->getData('Multiplos'),
			'DiasRespuesta' => $this->getData('DiasRespuesta'),
			'Logico1' => $this->getData('Logico1'),
			'Logico2' => $this->getData('Logico2'),
			'ProveedorOmision' => $this->getData('ProveedorOmision'),
			'UltimaCotizacion' => $this->getData('UltimaCotizacion'),
			'FechaCotizacion' => $this->getData('FechaCotizacion')
		);		
		return $Arreglo;
	}

}