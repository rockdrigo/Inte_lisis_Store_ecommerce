<?php

/* REQ11022 JIB: 
 * Esta clase obtiene el inventario por sucursal de los productos para mostrarlos
 * en una ventana emergente.
 * Regresa la tabla como variable global 'ProductStockDP'
 */

 class ISC_PRODUCT_STOCKDETAILP
{
	public function HandlePage()
	{
		
		$GLOBALS['ShopPath'] = GetConfig('ShopPath');
		$GLOBALS['ProductStockDP'] = $this->GetProductStockDetail();
		
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('product.stockdetailp');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}
	public function GetProductStockDetail(){
		
		if(!isset($_GET['prodID'])){
			return "No se definio un ID de Producto para buscar su inventario";
		}
		
		$productId = $_GET["prodID"];
		$optionIds = isset($_GET['optionIds']) ? $_GET['optionIds'] : '';

		$numoptions = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT pv.vnumoptions
			FROM [|PREFIX|]products p
			JOIN [|PREFIX|]product_variations pv ON (p.prodvariationid=pv.variationid)
			WHERE p.productid = "'.$productId.'"', 'vnumoptions');
		$options_array = explode(',', $optionIds);
		foreach($options_array as $key => $value) {
			if(trim($value) == '') unset($options_array[$key]);
		}
		
		if($numoptions != count($options_array)){
			$optionIds = '';
		}
		
		if($optionIds == ''){
			$query = 'SELECT prodcode
			FROM [|PREFIX|]products
			WHERE productid = "'.$productId.'"';
			$sku = $GLOBALS['ISC_CLASS_DB']->FetchOne($query, 'prodcode');
		}
		else {
			$query = 'SELECT vcsku
			FROM [|PREFIX|]product_variation_combinations
			WHERE vcproductid = "'.$productId.'"
			AND vcoptionids = "'.$optionIds.'"';
			$sku = $GLOBALS['ISC_CLASS_DB']->FetchOne($query, 'vcsku');
		}
		
		if(!$sku || $sku == ''){
			logAddError('No se pudo encontrar el SKU del productid "'.$productId.'" con optionids "'.$optionIds.'"');
			return 'Error al encontrar el SKU del producto o combinacion seleccionada. Asegurese de seleccionar todas las opciones';
		}
		
		$ProductStockTable = getProductStockDetail($sku);
		
		$ProductTableP = '<table id="ProductStockDetailTable" class="ProductStockDetailTable">';
		if(count($ProductStockTable) == 0) $ProductTableP .= '<tr><td>No se encontraron existencias de este producto con la combinacion elegida en las Sucursales</td></tr>';
		else {
			$ProductTableP .= '<th width="20%">Sucursal</th><th width="10%">Cantidad</th><th width="70%">Contacto</th>';
			foreach($ProductStockTable as $row) {
				$ProductTableP .= '<tr>
				<td>'.$row['Nombre'].'</td>
				<td>'.$row['Existencia'].'</td>
				<td>'.$row['Contacto'].'</td>
				</tr>
				';
			}
		}
		$ProductTableP .= '</table>';
		return $ProductTableP;
	}
}