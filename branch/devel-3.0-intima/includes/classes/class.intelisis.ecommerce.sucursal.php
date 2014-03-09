<?php

class ISC_INTELISIS_ECOMMERCE_SUCURSAL extends ISC_INTELISIS_ECOMMERCE_CATALOGO
{
		
	public $tablename = 'intelisis_Sucursal';
	public $pk = array(
			'Sucursal' => 'Sucursal'
			);
	
	public function getTableArray() {
		$dataArray = $this->getDataElement();
		if(empty($dataArray)) {
			return false;
		}
		
		$Arreglo = array(
			'Sucursal' =>  $this->getData('Sucursal', 0),
				//NES - Quito las comas del nombre para poder explotarlas con getStoreOriginOptions() si es necesario
			'Nombre' =>  str_replace(',', '', $this->getData('Nombre')),
			'Prefijo' =>  $this->getData('Prefijo'),
			'Relacion' =>  $this->getData('Relacion'),
			'Direccion' =>  $this->getData('Direccion1').' '.$this->getData('Direccion2').' '.$this->getData('Direccion3').' '.$this->getData('Direccion4').' '.$this->getData('Direccion5').' '.$this->getData('Direccion6').' '.$this->getData('Direccion7').' '.$this->getData('Direccion8'),
			'Colonia' =>  $this->getData('Colonia'),
			'Poblacion' =>  $this->getData('Poblacion'),
			'AlmacenPrincipal' =>  $this->getData('AlmacenPrincipal'),
			'Estado' =>  $this->getData('Estado'),
			'Pais' =>  $this->getData('Pais'),
			'CodigoPostal' =>  $this->getData('CodigoPostal'),
			'Telefonos' =>  $this->getData('Telefonos'),
			'Fax' =>  $this->getData('Fax'),
			'Estatus' =>  $this->getData('Estatus'),
			/*'UltimoCambio' =>  $this->getData('UltimoCambio'),*/
			'RFC' =>  $this->getData('RFC'),
			'Encargado' =>  $this->getData('Encargado'),
			'Region' =>  $this->getData('Region'),
			'EnLinea' =>  $this->getData('EnLinea'),
			'SucursalPrincipal' =>  $this->getData('SucursalPrincipal', 1),
			'ListaPreciosEsp' =>  $this->getData('ListaPreciosEsp'),
			'Cajeros' =>  $this->getData('Cajeros'),
			'CentroCostos' =>  $this->getData('CentroCostos'),
			'OperacionContinua' =>  $this->getData('OperacionContinua'),
			'DireccionNumero' =>  $this->getData('DireccionNumero'),
			'DireccionNumeroInt' =>  $this->getData('DireccionNumeroInt', 0),
			'Delegacion' =>  $this->getData('Delegacion'),
			/*'SincroID' =>  $this->getData('SincroID'),*/
			'SincroC' =>  $this->getData('SincroC', 0),
			'eCommerce' =>  $this->getData('eCommerce', 1),
			'eCommerceSucursal' =>  $this->getData('eCommerceSucursal'),
			'eCommerceImagenes' =>  $this->getData('eCommerceImagenes'),
			'eCommerceAlmacen' =>  $this->getData('eCommerceAlmacen'),
			'eCommerceListaPrecios' =>  $this->getData('eCommerceListaPrecios'),
			'eCommercePedido' =>  $this->getData('eCommercePedido'),
			'eCommerceEstrategiaDescuento' =>  $this->getData('eCommerceEstrategiaDescuento'),
			'eCommerceOffLine' => $this->getData('eCommerceOffLine', 1),
			'eCommerceArticuloFlete' =>  $this->getData('eCommerceArticuloFlete'),
			'eCommerceTipoConsecutivo' =>  $this->getData('eCommerceTipoConsecutivo'),
			'eCommerceCondicion' =>  $this->getData('eCommerceCondicion'),
			'eCommerceCajero' => $this->getData('eCommerceCajero'),
			'eCommerceCteCat' => $this->getData('eCommerceCteCat'),
			'eCommerceAgente' => $this->getData('eCommerceAgente'),
			'eCommerceImpuestoIncluido' =>  $this->getData('eCommerceImpuestoIncluido'),
			'eCommerceSincroniza' => $this->getData('eCommerceSincroniza', '1'),
			'eCommerceConsultaExistencias' =>  $this->getData('eCommerceConsultaExistencias', '0'),
			'eCommerceURL' => $this->getData('eCommerceURL'),
		);
		
		/*
		 * NES - Agrego esto comentado ya que no creo que necesitamos borrar, solo ignorar en getProductStockDetail()
		 */
		/*
		if($this->getData('eCommerceSincroniza', 0) == 0){
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('intelisis_inv', 'WHERE Sucursal = "'.$this->$this->getData('Sucursal').'"');
		}
		*/
		return $Arreglo;
	}

	public function createUpdatePosthook(){
		$s = GetClass('ISC_ADMIN_SETTINGS');
		$mode = $this->getData('eCommerceCRModo', 'No');
		
		if($mode != 'No'){
			$GLOBALS['ISC_NEW_CFG']['EnableGiftCertificates'] = 1;
		}
		else {
			$GLOBALS['ISC_NEW_CFG']['EnableGiftCertificates'] = 0;
		}
		
		if($mode == 'Personalizable'){
			$GLOBALS['ISC_NEW_CFG']['GiftCertificateCustomAmounts'] = 1;
			$GLOBALS['ISC_NEW_CFG']['GiftCertificateMinimum'] = $this->getData('eCommerceCRMinimo');
			$GLOBALS['ISC_NEW_CFG']['GiftCertificateMaximum'] = $this->getData('eCommerceCRMaximo');
		}
		else {
			$GLOBALS['ISC_NEW_CFG']['GiftCertificateCustomAmounts'] = 0;
		}
		
		$s->CommitSettings();
	}
}