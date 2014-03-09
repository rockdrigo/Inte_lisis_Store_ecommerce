f<?php

class ISC_INTELISIS_ECOMMERCE_PRECIO extends ISC_INTELISIS_ECOMMERCE_CATALOGO
{
	public $tablename = 'intelisis_Precio';
	public $pk = array(
			'ID' => 'PrecioID'
	);
		
	public function getTableArray() {
		$dataArray = $this->getDataElement();
		if(empty($dataArray)) {
			return false;
		}
		
		$Arreglo = array(
			'ID'	=>	$this->getData('ID'),
			'Descripcion'	=>	$this->getData('Descripcion'),
			'Estatus'	=>	$this->getData('Estatus'),
			'UltimoCambio'	=>	date('Y-m-d h:i:s', $this->getData('UltimoCambio')),
			'NivelArticulo'	=>	$this->getData('NivelArticulo', 0),
			'Articulo'	=>	$this->getData('Articulo'),
			'NivelSubCuenta'	=>	$this->getData('NivelSubCuenta', 0),
			'SubCuenta'	=>	$this->getData('SubCuenta'),
			'NivelArtGrupo'	=>	$this->getData('NivelArtGrupo', 0),
			'ArtGrupo'	=>	$this->getData('ArtGrupo'),
			'NivelArtCat'	=>	$this->getData('NivelArtCat', 0),
			'ArtCat'	=>	$this->getData('ArtCat'),
			'NivelArtFam'	=>	$this->getData('NivelArtFam', 0),
			'ArtFam'	=>	$this->getData('ArtFam'),
			'NivelArtABC'	=>	$this->getData('NivelArtABC', 0),
			'ArtABC'	=>	$this->getData('ArtABC'),
			'NivelFabricante'	=>	$this->getData('NivelFabricante', 0),
			'Fabricante'	=>	$this->getData('Fabricante'),
			'NivelArtLinea'	=>	$this->getData('NivelArtLinea', 0),
			'ArtLinea'	=>	$this->getData('ArtLinea'),
			'NivelArtRama'	=>	$this->getData('NivelArtRama', 0),
			'ArtRama'	=>	$this->getData('ArtRama'),
			'NivelCliente'	=>	$this->getData('NivelCliente', 0),
			'Cliente'	=>	$this->getData('Cliente'),
			'NivelCteGrupo'	=>	$this->getData('NivelCteGrupo', 0),
			'CteGrupo'	=>	$this->getData('CteGrupo'),
			'NivelCteCat'	=>	$this->getData('NivelCteCat', 0),
			'CteCat'	=>	$this->getData('CteCat'),
			'NivelCteFam'	=>	$this->getData('NivelCteFam', 0),
			'CteFam'	=>	$this->getData('CteFam'),
			'NivelCteZona'	=>	$this->getData('NivelCteZona', 0),
			'CteZona'	=>	$this->getData('CteZona'),
			'NivelMoneda'	=>	$this->getData('NivelMoneda', 0),
			'Moneda'	=>	$this->getData('Moneda'),
			'NivelCondicion'	=>	$this->getData('NivelCondicion', 0),
			'Condicion'	=>	$this->getData('Condicion'),
			'NivelAlmacen'	=>	$this->getData('NivelAlmacen', 0),
			'Almacen'	=>	$this->getData('Almacen'),
			'NivelProyecto'	=>	$this->getData('NivelProyecto', 0),
			'Proyecto'	=>	$this->getData('Proyecto'),
			'NivelAgente'	=>	$this->getData('NivelAgente', 0),
			'Agente'	=>	$this->getData('Agente'),
			'NivelFormaEnvio'	=>	$this->getData('NivelFormaEnvio', 0),
			'FormaEnvio'	=>	$this->getData('FormaEnvio'),
			'NivelMov'	=>	$this->getData('NivelMov', 0),
			'Mov'	=>	$this->getData('Mov'),
			'NivelServicioTipo'	=>	$this->getData('NivelServicioTipo', 0),
			'ServicioTipo'	=>	$this->getData('ServicioTipo'),
			'NivelContratoTipo'	=>	$this->getData('NivelContratoTipo', 0),
			'ContratoTipo'	=>	$this->getData('ContratoTipo'),
			'NivelUnidadVenta'	=>	$this->getData('NivelUnidadVenta', 0),
			'UnidadVenta'	=>	$this->getData('UnidadVenta'),
			'NivelEmpresa'	=>	$this->getData('NivelEmpresa', 0),
			'Empresa'	=>	$this->getData('Empresa'),
			'NivelRegion'	=>	$this->getData('NivelRegion', 0),
			'Region'	=>	$this->getData('Region'),
			'NivelSucursal'	=>	$this->getData('NivelSucursal', 0),
			'Sucursal'	=>	$this->getData('Sucursal', 0),
			'Tipo'	=>	$this->getData('Tipo'),
			'Nivel'	=>	$this->getData('Nivel'),
			'ListaPrecios'	=>	$this->getData('ListaPrecios'),
			'ConVigencia'	=>	$this->getData('ConVigencia'),
			'FechaD'	=>	date('Y-m-d h:i:s', $this->getData('FechaD')),
			'FechaA'	=>	date('Y-m-d h:i:s', $this->getData('FechaA')),
			'ArticuloObsequio'	=>	$this->getData('ArticuloObsequio'),
			'Logico1'	=>	$this->getData('Logico1', 0),
			'Logico2'	=>	$this->getData('Logico2', 0),
			'wMostrar'	=>	$this->getData('wMostrar', 0),
		);
		return $Arreglo;
	}
}
