<?php

CLASS ISC_PRODUCTSTOCKDETAIL_PANEL extends PANEL
{
	/**
	 * REQ10046 - Panel para contener la tabla de detalle de existencias por sucursal.
	 */
	public function SetPanelSettings()
	{
		$productClass = GetClass('ISC_PRODUCT');
		$productStockDetail = getProductStockDetail($productClass->GetSKU());

		$GLOBALS['ProductStockDetailTable'] = '';
		if(count($productStockDetail) == 0) $GLOBALS['ProductStockDetailTable'] = '<td>No se encontraron existencias de este producto con la combinacion elegida en las Sucursales</td>';
		else {
			$GLOBALS['ProductStockDetailTable'] = '<th width="20%">Sucursal</th><th width="20%">Cantidad</th><th width="60%">Contacto</th>';
			foreach($productStockDetail as $row) {
				$GLOBALS['ProductStockDetailTable'] .= '<tr>
				<td>'.$row['Nombre'].'</td>
				<td>'.$row['Existencia'].'</td>
				<td>'.$row['Contacto'].'</td>
				</tr>
				';
			}
		}
		
		if(GetConfig('syncIWSurl') != ''){
			$GLOBALS['ProductStockDetailRefreshButton'] = '<input type="button" id="ProductStockDetailRefreshButton" class="SmallButton" value="Consultar Existencias."></div>';
		}
	}
}
