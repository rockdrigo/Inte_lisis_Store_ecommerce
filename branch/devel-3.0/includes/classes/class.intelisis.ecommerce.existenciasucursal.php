<?php

class ISC_INTELISIS_ECOMMERCE_EXISTENCIASUCURSAL extends ISC_INTELISIS_ECOMMERCE
{
	/* 
	 * REQ10046
	 * 
	 * Clase para manejar XML con cambios o altas de existencia por articulo y por sucursal
	 * Las existencias se guardan en la tabla intelisis_inv, y se muestran ya sea si el ISC_IWS_PRODUCTSTOCK falla, al cargar la pagina, o al cambiar de opciones de variacion 
	 */
	public function ProcessData(){
		return $this->process();
	}
	
	private function convertToCombination($options) {
		$value_array = array();
		$result = $GLOBALS['ISC_CLASS_DB']->Query('SELECT ivov.ValorIntelisis, ivov.voptionid, ivo.OpcionIntelisis, ivo.Nombre
			FROM [|PREFIX|]intelisis_variation_option_values ivov
			JOIN [|PREFIX|]product_variation_options pvo ON (ivov.voptionid=pvo.voptionid)
			JOIN [|PREFIX|]intelisis_variation_options ivo ON (pvo.voname=ivo.Nombre)');
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)){
			if(!isset($value_array[$row['OpcionIntelisis']])) $value_array[$row['OpcionIntelisis']] = array(
				'name' => $row['Nombre'],
				'options' => array(),
			);
			$value_array[$row['OpcionIntelisis']]['options'][$row['ValorIntelisis']] = $row['voptionid'];
		}
		
		$combination_array = array();
		$option_array = preg_split('/(?<=[0-9])(?=[a-z]+)/i',$options);

		foreach($option_array as $key => $each) {
			$numbers = preg_split('/[a-zA-Z]/',$each);
			$letters= preg_split('/[0-9]/',$each);
			$combination_array[$key] = array(
				'Opcion' => implode('', $letters),
				'Valor' => implode('', $numbers),
			);
		}
		
		$return = array();

		foreach($combination_array as $combination) {
			//printe('opcion "'.$combination['Opcion'].'" valor "'.$combination['Valor'].'"');
			if(isset($value_array[$combination['Opcion']]['options'][$combination['Valor']])){
				$return[] = $value_array[$combination['Opcion']]['options'][$combination['Valor']];
			}
			else {
				$return[] = 'Xx';
			}
		}
		
		return implode(',', $return);
	}
	
	public function process() {
		$errors = array();
		foreach($this->getDataElement() as $row){
			//Convertir SubCuenta a opciones de combinacion por medio de las nuevas columnas
			//en intelisis_variation_options e intelisis_variation_values
			$date = time();
			$line = array(
				'SKU' => $row['SKU'],
				'Sucursal' => $row['Sucursal'],
				'Fecha' => $date,
			);
			$line['Existencia'] = isset($row['Cantidad']) ? $row['Cantidad'] : 0;
			$Articulo = $row['Articulo'];
			$Situacion = isset($row['Situacion']) ? $row['Situacion'] : '';
			
			//if(trim($row['SubCuenta']) != '') $line['Opciones'] = $this->convertToCombination($row['SubCuenta']);
			//else $line['Opciones'] = '';
			
			//NES - REQ10156
			if($GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT Descontinuado FROM [|PREFIX|]intelisis_prodstatus WHERE Situacion = "'.$Situacion.'"', 'Descontinuado') == '1') {
				$prodallowpurchases = 0;
			}
			else {
				$prodallowpurchases = 1;
			}

			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT productid FROM [|PREFIX|]intelisis_products WHERE Articulo = '".$row['Articulo']."'");
			$prodIds = array();
			while($productrow = $GLOBALS['ISC_CLASS_DB']->Fetch($result)){
				$prodIds[] = $productrow['productid'];
			}
			$updateDiscont = array(
						"prodallowpurchases" => $prodallowpurchases,
			);

			if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('products', $updateDiscont, 'productid IN ("'.implode('","', $prodIds).'")')){
				logAdd(LOG_SEVERITY_ERROR, 'Error al descontinuar productos que pertenecen al articulo. Archivo: '.$this->getXMLfilename().'.<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
				
			/*
			 * REQ11552: NES - Actualizo los registros de productos con la situacion nueva
			 */
			$updateStatusProducts = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_products', array('Situacion' => $Situacion),
				'Articulo = "'.$Articulo.'"');
			if(!$updateStatusProducts) {
				logAdd('Ocurrio un error al cambiar el estatus de los productos del articulo "'.$Articulo.'". Archivo: '.$this->getXMLfilename().'.<br/>. Error: '.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
			
			$updateStatusCombinations = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_variation_combinations', array('Situacion' => $Situacion), 
				'Articulo = "'.$Articulo.'"');
			if(!$updateStatusCombinations) {
				logAdd('Ocurrio un error al cambiar el estatus de las combinaciones del articulo "'.$Articulo.'". Archivo: '.$this->getXMLfilename().'.<br/>. Error: '.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
			
			/*------------------------*/
			
			/* NES - Decido no usar esto para evitar datos de-sincronizados. Que entre el inventario, solo voy a borrar los datos con update o
			 * insert de intelisis_Sucursal, e ignorarlos en getProductStockDetail()
			 *
			 */
			/*  
			if($GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT eCommerceSincroniza FROM [|PREFIX|]intelisis_Sucursal WHERE Sucursal = "'.$line['Sucursal'].'"', 'eCommerceSincroniza') == 0){
				continue;
			}
			*/

			if($GLOBALS['ISC_CLASS_DB']->CountResult('SELECT SKU FROM [|PREFIX|]intelisis_inv
				WHERE SKU = "'.$line['SKU'].'"
				AND Sucursal = "'.$line['Sucursal'].'"')){
					$GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_inv', $line, 'SKU = "'.$line['SKU'].'"	AND Sucursal = "'.$line['Sucursal'].'"');
				}
				else {
					$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_inv', $line);
				}
			if($GLOBALS['ISC_CLASS_DB']->Error() != ''){
				logAdd(LOG_SEVERITY_ERROR, 'Error al procesar archivo de Inventario '.$this->getXMLfilename().'. '.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
			//updateStockTotalsFromIntelisis($line['SKU']);
		}
		logAdd(LOG_SEVERITY_SUCCESS, 'Se proceso el archivo de inventario '.$this->getXMLfilename());
		return true;
	}
}
