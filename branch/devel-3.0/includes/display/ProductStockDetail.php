<?php

CLASS ISC_PRODUCTSTOCKDETAIL_PANEL extends PANEL
{
	/**
	 * REQ10046 - Panel para contener la tabla de detalle de existencias por sucursal.
	 */
	public function SetPanelSettings()
	{
		if($GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT eCommerceConsultaExistencias FROM [|PREFIX|]intelisis_Sucursal WHERE Sucursal = "'.GetConfig('syncIWSintelisissucursal').'"', 'eCommerceConsultaExistencias') == 0){
			$GLOBALS['ProductStockDetailDisplay'] = 'none';
			return;
		}
		
		$productClass = GetClass('ISC_PRODUCT');
		$productStockDetail = getProductStockDetail($productClass->GetSKU());

		$GLOBALS['ProductStockDetailTable'] = '';
		if(count($productStockDetail) == 0) $GLOBALS['ProductStockDetailTable'] = '<td>No se encontraron existencias de este producto con la combinacion elegida en las Sucursales</td>';
		else {
			$GLOBALS['ProductStockDetailTable'] = '<th width="20%">Sucursal</th><th width="20%">Cantidad</th><th width="60%">Contacto</th>';
			foreach($productStockDetail as $row) {
				$GLOBALS['ProductStockDetailNumber'] = $row['Numero'];
				$GLOBALS['ProductStockDetailName'] = $row['Nombre'];
				$GLOBALS['ProductStockDetailStock'] = $row['Existencia'];
				$GLOBALS['ProductStockDetailContact'] = $row['Contacto'];
				$GLOBALS['ProductStockDetailTable'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('ProductStockDetailItem');
			}
		}
		
		if(GetConfig('syncIWSurl') != ''){
			$GLOBALS['ProductStockDetailRefreshButton'] = '<input type="button" id="ProductStockDetailRefreshButton" class="SmallButton" value="Consultar Existencias.">';
		}
	}
}
