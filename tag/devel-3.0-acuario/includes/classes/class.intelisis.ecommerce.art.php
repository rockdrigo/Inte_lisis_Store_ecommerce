<?php

class ISC_INTELISIS_ECOMMERCE_ART extends ISC_INTELISIS_ECOMMERCE
{
	public function ProcessData() {
		if($this->getXMLdom())
		{
			//printe($this->getAttribute('Estatus').": ".$this->getAttribute('Cliente'));
			switch ($this->getAttribute('Estatus')) {
				case 'ALTA':
					return $this->createUpdateArt();
				break;
				case 'CAMBIO':
					return $this->createUpdateArt();
				break;
				case 'BAJA':
					return $this->delete();
				break;
				default:
					logAdd(LOG_SEVERITY_ERROR, 'Estatus de archivo no valido. '.get_class($this).'. Estatus: "'.$this->getAttribute('Estatus').'"', 'Archivo: "'.$this->getXMLfilename().'"');
					return false;
				break;
			}
		}
		else
		{
			logAdd(LOG_SEVERITY_WARNING, 'Se trato de procesar un objeto '.get_class($this).' sin XML DOM especificado', 'Archivo: "'.$this->getXMLfilename().'"');
		}
	}

	private function createUpdateArt() {
		$Art = array(
			'Articulo' => $this->getData('Articulo'),
			'Categoria' => $this->getData('Categoria'),
			'Grupo' => $this->getData('Grupo'),
			'Familia' => $this->getData('Familia'),
			'ABC' => $this->getData('ABC'),
			'Fabricante' => $this->getData('Fabricante'),
			'Linea' => $this->getData('Linea'),
			'Rama' => $this->getData('Rama'),
			'PrecioLista' => $this->getData('PrecioLista', 0),
			'Precio2' => $this->getData('Precio2', 0),
			'Precio3' => $this->getData('Precio3', 0),
			'Precio4' => $this->getData('Precio4', 0),
			'Precio5' => $this->getData('Precio5', 0),
			'Precio6' => $this->getData('Precio6', 0),
			'Precio7' => $this->getData('Precio7', 0),
			'Precio8' => $this->getData('Precio8', 0),
			'Precio9' => $this->getData('Precio9', 0),
			'Precio10' => $this->getData('Precio10', 0),
			'MonedaCosto' => $this->getData('MonedaCosto'),
			'MonedaPrecio' => $this->getData('MonedaPrecio'),
		/*
			'Articulo' => $this->getData('Articulo'),
			'Rama' => $this->getData('Rama'),
			'Descripcion1' => $this->getData('Descripcion1'),
			'Descripcion2' => $this->getData('Descripcion2'),
			'NombreCorto' => $this->getData('NombreCorto'),
			'Grupo' => $this->getData('Grupo'),
			'Categoria' => $this->getData('Categoria'),
			'CategoriaActivoFijo' => $this->getData('CategoriaActivoFijo'),
			'Familia' => $this->getData('Familia'),
			'Linea' => $this->getData('Linea'),
			'Fabricante' => $this->getData('Fabricante'),
			'ClaveFabricante' => $this->getData('ClaveFabricante'),
			'Impuesto1' => FormatNumber($this->getData('Impuesto1')),
			'Impuesto2' => FormatNumber($this->getData('Impuesto2')),
			'Impuesto3' => FormatNumber($this->getData('Impuesto3')),
			'Factor' => $this->getData('Factor'),
			'Unidad' => $this->getData('Unidad'),
			'UnidadCompra' => $this->getData('UnidadCompra'),
			'UnidadTraspaso' => $this->getData('UnidadTraspaso'),
			'UnidadCantidad' => $this->getData('UnidadCantidad'),
			'TipoCosteo' => $this->getData('TipoCosteo'),
			'Peso' => FormatNumber($this->getData('Peso')),
			'Tara' => FormatNumber($this->getData('Tara')),
			'Volumen' => FormatNumber($this->getData('Volumen')),
			'Tipo' => $this->getData('Tipo'),
			'TipoOpcion' => $this->getData('TipoOpcion'),
			'Accesorios' => FormatNumber($this->getData('Accesorios')),
			'Refacciones' => FormatNumber($this->getData('Refacciones')),
			'Sustitutos' => FormatNumber($this->getData('Sustitutos')),
			'Servicios' => FormatNumber($this->getData('Servicios')),
			'Consumibles' => FormatNumber($this->getData('Consumibles')),
			'MargenMinimo' => FormatNumber($this->getData('MargenMinimo')),
			'PrecioLista' => FormatNumber($this->getData('PrecioLista')),
			'PrecioMinimo' => FormatNumber($this->getData('PrecioMinimo')),
			'FactorAlterno' => FormatNumber($this->getData('FactorAlterno')),
			'PrecioAnterior' => FormatNumber($this->getData('PrecioAnterior')),
			'Utilidad' => $this->getData('Utilidad'),
			'DescuentoCompra' => FormatNumber($this->getData('DescuentoCompra')),
			'Clase' => $this->getData('Clase'),
			'Estatus' => $this->getData('Estatus'),
			'UltimoCambio' => $this->getData('UltimoCambio'),
			'Alta' => $this->getData('Alta'),
			'Conciliar' => FormatNumber($this->getData('Conciliar')),
			'Mensaje' => $this->getData('Mensaje'),
			'Comision' => $this->getData('Comision'),
			'Arancel' => $this->getData('Arancel'),
			'ArancelDesperdicio' => $this->getData('ArancelDesperdicio'),
			'ABC' => $this->getData('ABC'),
			'Usuario' => $this->getData('Usuario'),
			'Precio2' => FormatNumber($this->getData('Precio2')),
			'Precio3' => FormatNumber($this->getData('Precio3')),
			'Precio4' => FormatNumber($this->getData('Precio4')),
			'Precio5' => FormatNumber($this->getData('Precio5')),
			'Precio6' => FormatNumber($this->getData('Precio6')),
			'Precio7' => FormatNumber($this->getData('Precio7')),
			'Precio8' => FormatNumber($this->getData('Precio8')),
			'Precio9' => FormatNumber($this->getData('Precio9')),
			'Precio10' => FormatNumber($this->getData('Precio10')),
			'Refrigeracion' => FormatNumber($this->getData('Refrigeracion')),
			'TieneCaducidad' => FormatNumber($this->getData('TieneCaducidad')),
			'BasculaPesar' => FormatNumber($this->getData('BasculaPesar')),
			'SeProduce' => $this->getData('SeProduce'),
			'Situacion' => $this->getData('Situacion'),
			'SituacionFecha' => $this->getData('SituacionFecha') != '' ? $this->getData('SituacionFecha') : false,
			'SituacionUsuario' => $this->getData('SituacionUsuario'),
			'SituacionNota' => $this->getData('SituacionNota'),
			'EstatusPrecio' => $this->getData('EstatusPrecio'),
			'wMostrar' => $this->getData('wMostrar'),
			'Merma' => FormatNumber($this->getData('Merma')),
			'Desperdicio' => FormatNumber($this->getData('Desperdicio')),
			'SeCompra' => $this->getData('SeCompra'),
			'SeVende' => $this->getData('SeVende'),
			'EsFormula' => $this->getData('EsFormula'),
			'TiempoEntrega' => FormatNumber($this->getData('TiempoEntrega')),
			'TiempoEntregaUnidad' => $this->getData('TiempoEntregaUnidad'),
			'TiempoEntregaSeg' => FormatNumber($this->getData('TiempoEntregaSeg')),
			'TiempoEntregaSegUnidad' => $this->getData('TiempoEntregaSegUnidad'),
			'LoteOrdenar' => $this->getData('LoteOrdenar'),
			'CantidadOrdenar' => FormatNumber($this->getData('CantidadOrdenar')),
			'MultiplosOrdenar' => FormatNumber($this->getData('MultiplosOrdenar')),
			'InvSeguridad' => FormatNumber($this->getData('InvSeguridad')),
			'ProdRuta' => $this->getData('ProdRuta'),
			'AlmacenROP' => $this->getData('AlmacenROP'),
			'CategoriaProd' => $this->getData('CategoriaProd'),
			'ProdCantidad' => FormatNumber($this->getData('ProdCantidad')),
			'ProdUsuario' => $this->getData('ProdUsuario'),
			'ProdPasoTotal' => FormatNumber($this->getData('ProdPasoTotal')),
			'ProdMovGrupo' => $this->getData('ProdMovGrupo'),
			'ProdEstacion' => $this->getData('ProdEstacion'),
			'ProdOpciones' => $this->getData('ProdOpciones'),
			'ProdVerConcentracion' => $this->getData('ProdVerConcentracion'),
			'ProdVerCostoAcumulado' => $this->getData('ProdVerCostoAcumulado'),
			'ProdVerMerma' => $this->getData('ProdVerMerma'),
			'ProdVerDesperdicio' => $this->getData('ProdVerDesperdicio'),
			'ProdVerPorcentaje' => $this->getData('ProdVerPorcentaje'),
			'RevisionUltima' => $this->getData('RevisionUltima'),
			'RevisionUsuario' => $this->getData('RevisionUsuario'),
			'RevisionFrecuencia' => FormatNumber($this->getData('RevisionFrecuencia')),
			'RevisionFrecuenciaUnidad' => $this->getData('RevisionFrecuenciaUnidad'),
			'RevisionSiguiente' => $this->getData('RevisionSiguiente'),
			'ProdMov' => $this->getData('ProdMov'),
			'TipoCompra' => $this->getData('TipoCompra'),
			'TieneMovimientos' => $this->getData('TieneMovimientos'),
			'Registro1' => $this->getData('Registro1'),
			'Registro1Vencimiento' => $this->getData('Registro1Vencimiento'),
			'AlmacenEspecificoVenta' => $this->getData('AlmacenEspecificoVenta'),
			'AlmacenEspecificoVentaMov' => $this->getData('AlmacenEspecificoVentaMov'),
			'RutaDistribucion' => $this->getData('RutaDistribucion'),
			'CostoEstandar' => FormatNumber($this->getData('CostoEstandar')),
			'CostoReposicion' => FormatNumber($this->getData('CostoReposicion')),
			'EstatusCosto' => $this->getData('EstatusCosto'),
			'Margen' => FormatNumber($this->getData('Margen')),
			'Proveedor' => $this->getData('Proveedor'),
			'NivelAcceso' => $this->getData('NivelAcceso'),
			'Temporada' => $this->getData('Temporada'),
			'SolicitarPrecios' => $this->getData('SolicitarPrecios'),
			'AutoRecaudacion' => $this->getData('AutoRecaudacion'),
			'Concepto' => $this->getData('Concepto'),
			'Cuenta' => $this->getData('Cuenta'),
			'Retencion1' => FormatNumber($this->getData('Retencion1')),
			'Retencion2' => FormatNumber($this->getData('Retencion2')),
			'Retencion3' => FormatNumber($this->getData('Retencion3')),
			'Espacios' => FormatNumber($this->getData('Espacios')),
			'EspaciosEspecificos' => FormatNumber($this->getData('EspaciosEspecificos')),
			'EspaciosSobreventa' => FormatNumber($this->getData('EspaciosSobreventa')),
			'EspaciosNivel' => $this->getData('EspaciosNivel'),
			'EspaciosMinutos' => FormatNumber($this->getData('EspaciosMinutos')),
			'EspaciosBloquearAnteriores' => FormatNumber($this->getData('EspaciosBloquearAnteriores')),
			'EspaciosHoraD' => $this->getData('EspaciosHoraD'),
			'EspaciosHoraA' => $this->getData('EspaciosHoraA'),
			'SerieLoteInfo' => FormatNumber($this->getData('SerieLoteInfo')),
			'CantidadMinimaVenta' => FormatNumber($this->getData('CantidadMinimaVenta')),
			'CantidadMaximaVenta' => FormatNumber($this->getData('CantidadMaximaVenta')),
			'EstimuloFiscal' => FormatNumber($this->getData('EstimuloFiscal')),
			'OrigenPais' => $this->getData('OrigenPais'),
			'OrigenLocalidad' => $this->getData('OrigenLocalidad'),
			'Incentivo' => FormatNumber($this->getData('Incentivo')),
			'FactorCompra' => FormatNumber($this->getData('FactorCompra')),
			'Horas' => FormatNumber($this->getData('Horas')),
			'ISAN' => FormatNumber($this->getData('ISAN')),
			'ExcluirDescFormaPago' => FormatNumber($this->getData('ExcluirDescFormaPago')),
			'EsDeducible' => FormatNumber($this->getData('EsDeducible')),
			'Peaje' => FormatNumber($this->getData('Peaje')),
			'CodigoAlterno' => $this->getData('CodigoAlterno'),
			'TipoCatalogo' => $this->getData('TipoCatalogo'),
			'AnexosAlFacturar' => FormatNumber($this->getData('AnexosAlFacturar')),
			'CaducidadMinima' => FormatNumber($this->getData('CaducidadMinima')),
			'Actividades' => FormatNumber($this->getData('Actividades')),
			'ValidarPresupuestoCompra' => $this->getData('ValidarPresupuestoCompra'),
			'SeriesLotesAutoOrden' => $this->getData('SeriesLotesAutoOrden'),
			'LotesFijos' => FormatNumber($this->getData('LotesFijos')),
			'LotesAuto' => FormatNumber($this->getData('LotesAuto')),
			'Consecutivo' => FormatNumber($this->getData('Consecutivo')),
			'TipoEmpaque' => $this->getData('TipoEmpaque'),
			'Modelo' => $this->getData('Modelo'),
			'Version' => $this->getData('Version'),
			'TieneDireccion' => FormatNumber($this->getData('TieneDireccion')),
			'Direccion' => $this->getData('Direccion'),
			'DireccionNumero' => $this->getData('DireccionNumero'),
			'DireccionNumeroInt' => $this->getData('DireccionNumeroInt'),
			'EntreCalles' => $this->getData('EntreCalles'),
			'Plano' => $this->getData('Plano'),
			'Observaciones' => $this->getData('Observaciones'),
			'Colonia' => $this->getData('Colonia'),
			'Delegacion' => $this->getData('Delegacion'),
			'Poblacion' => $this->getData('Poblacion'),
			'Estado' => $this->getData('Estado'),
			'Pais' => $this->getData('Pais'),
			'CodigoPostal' => $this->getData('CodigoPostal'),
			'Ruta' => $this->getData('Ruta'),
			'Codigo' => $this->getData('Codigo'),
			'ClaveVehicular' => $this->getData('ClaveVehicular'),
			'TipoVehiculo' => $this->getData('TipoVehiculo'),
			'DiasLibresIntereses' => FormatNumber($this->getData('DiasLibresIntereses')),
			'PrecioLiberado' => FormatNumber($this->getData('PrecioLiberado')),
			'ValidarCodigo' => FormatNumber($this->getData('ValidarCodigo')),
			'Presentacion' => $this->getData('Presentacion'),
			'GarantiaPlazo' => FormatNumber($this->getData('GarantiaPlazo')),
			'CostoIdentificado' => FormatNumber($this->getData('CostoIdentificado')),
			'CantidadTarima' => FormatNumber($this->getData('CantidadTarima')),
			'UnidadTarima' => $this->getData('UnidadTarima'),
			'MinimoTarima' => FormatNumber($this->getData('MinimoTarima')),
			'DepartamentoDetallista' => FormatNumber($this->getData('DepartamentoDetallista')),
			'TratadoComercial' => $this->getData('TratadoComercial'),
			'CuentaPresupuesto' => $this->getData('CuentaPresupuesto'),
			'ProgramaSectorial' => $this->getData('ProgramaSectorial'),
			'PedimentoClave' => $this->getData('PedimentoClave'),
			'PedimentoRegimen' => $this->getData('PedimentoRegimen'),
			'Permiso' => $this->getData('Permiso'),
			'PermisoRenglon' => $this->getData('PermisoRenglon'),
			'Cuenta2' => $this->getData('Cuenta2'),
			'Cuenta3' => $this->getData('Cuenta3'),
			'Impuesto1Excento' => FormatNumber($this->getData('Impuesto1Excento')),
			'CalcularPresupuesto' => FormatNumber($this->getData('CalcularPresupuesto')),
			'InflacionPresupuesto' => FormatNumber($this->getData('InflacionPresupuesto')),
			'Excento2' => FormatNumber($this->getData('Excento2')),
			'Excento3' => FormatNumber($this->getData('Excento3')),
			'ContUso' => $this->getData('ContUso'),
			'ContUso2' => $this->getData('ContUso2'),
			'ContUso3' => $this->getData('ContUso3'),
			'NivelToleranciaCosto' => $this->getData('NivelToleranciaCosto'),
			'ToleranciaCosto' => FormatNumber($this->getData('ToleranciaCosto')),
			'ToleranciaCostoInferior' => FormatNumber($this->getData('ToleranciaCostoInferior')),
			'ObjetoGasto' => $this->getData('ObjetoGasto'),
			'ObjetoGastoRef' => $this->getData('ObjetoGastoRef'),
			'ClavePresupuestalImpuesto1' => $this->getData('ClavePresupuestalImpuesto1'),
			'Estructura' => $this->getData('Estructura'),
			'TipoImpuesto1' => $this->getData('TipoImpuesto1'),
			'TipoImpuesto2' => $this->getData('TipoImpuesto2'),
			'TipoImpuesto3' => $this->getData('TipoImpuesto3'),
			'TipoImpuesto4' => $this->getData('TipoImpuesto4'),
			'TipoImpuesto5' => $this->getData('TipoImpuesto5'),
			'TipoRetencion1' => $this->getData('TipoRetencion1'),
			'TipoRetencion2' => $this->getData('TipoRetencion2'),
			'TipoRetencion3' => $this->getData('TipoRetencion3'),
			'SincroID' => $this->getData('SincroID'),
			'SincroC' => FormatNumber($this->getData('SincroC')),
			'Calificacion' => FormatNumber($this->getData('Calificacion')),
			'HTML' => $this->getData('HTML'),
			'wDescripcion3' => $this->getData('wDescripcion3'),
			'wDescripcion4' => $this->getData('wDescripcion4'),
			'wDescripcion5' => $this->getData('wDescripcion5'),
			'wDescripcion6' => $this->getData('wDescripcion6'),
			'wMostrarNuevo' => FormatNumber($this->getData('wMostrarNuevo')),
			'wMostrarAgotado' => FormatNumber($this->getData('wMostrarAgotado')),
			'wMostrarPromocion' => FormatNumber($this->getData('wMostrarPromocion')),
			'NoParticipantes' => FormatNumber($this->getData('NoParticipantes')),
			'ArticuloWeb' => $this->getData('ArticuloWeb'),*/
		);

		/* FUCK THIS
		//NES - REQ10156
		$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT productid FROM [|PREFIX|]intelisis_products WHERE Articulo = '".$this->getAttribute('Articulo')."'");
		$prodIds = array();
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)){
			$prodIds[] = $row['productid'];
		}
		$updateDiscont = array( 
				"prodallowpurchases" => $this->getData('Descontinuado', 0) == '1' ? 0 : 1,
		);
		*/
		
		/* 
		 * REQ11552: NES - Actualizo los registros de productos con la situacion nueva
		 */
		if($this->getData('Situacion', '') != ''){
			$updateStatusProducts = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_products', array('Situacion' => $this->getData('Situacion')), 
				'Articulo = "'.$this->getAttribute('Articulo').'"');
			if(!$updateStatusProducts) {
				logAdd('Ocurrio un error al cambiar el estatus de los productos del articulo "'.$this->getAttribute('Articulo').'". Archivo: '.$this->getXMLfilename().'.<br/>. Error: '.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
			
			$updateStatusCombinations = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_variation_combinations', array('Situacion' => $this->getData('Situacion')), 
				'Articulo = "'.$this->getAttribute('Articulo').'"');
			if(!$updateStatusCombinations) {
				logAdd('Ocurrio un error al cambiar el estatus de las combinaciones del articulo "'.$this->getAttribute('Articulo').'". Archivo: '.$this->getXMLfilename().'.<br/>. Error: '.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
		}

		/* FUCK THIS
		if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('products', $updateDiscont, 'productid IN ("'.implode('","', $prodIds).'")')){
			logAdd(LOG_SEVERITY_ERROR, 'Error al descontinuar productos que pertenecen al articulo. Archivo: '.$this->getXMLfilename().'.<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
		*/
		
		if($Articulo = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT Articulo FROM [|PREFIX|]intelisis_Art WHERE Articulo = "'.$this->getAttribute('Articulo').'"', 'Articulo'))
		{
			if($GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_Art', $Art, 'Articulo = "'.$Articulo.'"'))
			{
				logAdd(LOG_SEVERITY_SUCCESS, 'Se edito el Art "'.$this->getAttribute('Articulo').'"');
				return true;
			}
			else
			{
				//printe($GLOBALS['ISC_CLASS_DB']->Error());
				logAdd(LOG_SEVERITY_ERROR, 'Error al intentar editar el Art "'.$this->getAttribute('Articulo').'". Archivo: "'.$this->getXMLfilename().'".<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
		}
		else
		{
			if($GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_Art', $Art))
			{
				logAdd(LOG_SEVERITY_SUCCESS, 'Se creo el Art "'.$this->getAttribute('Articulo').'"');
				return true;
			}
			else
			{
				//printe($GLOBALS['ISC_CLASS_DB']->Error());
				logAdd(LOG_SEVERITY_ERROR, 'Error al intentar crear el Art "'.$this->getAttribute('Articulo').'". Archivo: "'.$this->getXMLfilename().'".<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
		}
	}
	
	public function update(){
		return $this->create();
	}
	
	public function delete() {
		if($Articulo = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT Articulo FROM [|PREFIX|]intelisis_Art WHERE Articulo = "'.$this->getAttribute('Articulo').'"', 'Articulo'))
		{
			if($GLOBALS['ISC_CLASS_DB']->DeleteQuery('intelisis_Art', 'WHERE Articulo = "'.$Articulo.'"'))
			{
				logAdd(LOG_SEVERITY_SUCCESS, 'Se elimino el Art "'.$this->getAttribute('Articulo').'"');
				return true;
			}
			else
			{
				logAdd(LOG_SEVERITY_ERROR, 'Error al intentar eliminar el Art "'.$this->getAttribute('Articulo').'". Archivo: "'.$this->getXMLfilename().'".<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
		}
	}
}
