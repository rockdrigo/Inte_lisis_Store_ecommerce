<?php

class ISC_INTELISIS_ECOMMERCE_WEBARTVARIACIONCOMBINACION extends ISC_INTELISIS_ECOMMERCE
{
	public function ProcessData() {
		if($this->getXMLdom())
		{
			//printe($this->getAttribute('Estatus').": ".$this->getAttribute('Cliente'));
			switch ($this->getAttribute('Estatus')) {
				case 'ALTA':
					return $this->createCombination();
				break;
				case 'CAMBIO':
					return $this->updateCombination();
				break;
				case 'BAJA':
					return $this->deleteCombination();
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
	
	private function getOptionIds() {
		$return = array();
		foreach(explode(',', $this->getAttribute('IDOpciones')) as $option)
		{
			$return[] = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT voptionid FROM [|PREFIX|]intelisis_variation_option_values WHERE ValorID = "'.$option.'"', 'voptionid');
		}
		return implode(',', $return);
	}
	
	private function moveImageFile(){
		$Clave = substr(GetConfig('tablePrefix'), 0, strlen(GetConfig('tablePrefix'))-1);
		$imgpath = GetConfig('syncDropboxDir').$Clave.DIRECTORY_SEPARATOR.'Imagenes'.DIRECTORY_SEPARATOR.$this->getAttribute('IDArticulo').DIRECTORY_SEPARATOR.$this->getData('NombreImagen');
		
		if(!file_exists($imgpath)){
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro la imagen "'.$imgpath.'" origen para moverla y procesarla');
			return '';
		}
	
		$newDir = GetConfig('syncArchiveDir').$Clave.DIRECTORY_SEPARATOR.'Imagenes'.DIRECTORY_SEPARATOR.$this->getAttribute('IDArticulo');
	
		if(!is_dir($newDir)){
			if(!mkdir($newDir, 0777, true)){
				logAdd(LOG_SEVERITY_ERROR, 'Error al crear el directorio "'.$newDir.'" para guardar las imagenes');
				return '';
			}
		}
	
		$newFile = $newDir.DIRECTORY_SEPARATOR.$this->getData('NombreImagen');
	
		if(!rename($imgpath, $newFile)){
			logAdd(LOG_SEVERITY_ERROR, 'Error al intentar mover el archivo "'.$imgpath.'" a "'.$newFile.'"');
			return '';
		}
		else {
			return $newFile;
		}
	}
	
	private function createCombination() {
		$productId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT productid FROM [|PREFIX|]intelisis_products WHERE ArticuloID = "'.$this->getAttribute('IDArticulo').'"', 'productid');
		$variationId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT variationid FROM [|PREFIX|]intelisis_variations WHERE VariacionID = "'.$this->getData('IDVariacion').'"', 'variationid');
		
		if($productId == '' || $variationId == '' || !$productId || !$variationId){
			logAdd(LOG_SEVERITY_ERROR, 'Error al encontrar el ArticuloID "'.$this->getAttribute('IDArticulo').'" o la VariacionID "'.$this->getData('IDVariacion').'" de la combinacion "'.$this->getData('ID').'". Archivo: '.$this->getXMLfilename().'.<br/> Error: '.$GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
		
		$combinationid = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT combinationid FROM [|PREFIX|]intelisis_variation_combinations WHERE IDCombinacion = "'.$this->getData('ID').'"', 'combinationid');
		if($combinationid){
			//logAdd(LOG_SEVERITY_ERROR, 'No se encontro la combinationid con la condicion WHERE '.$where.' Archivo: '.$this->getXMLfilename().'<br/>Error: '.$GLOBALS['ISC_CLASS_DB']->Error());
			return $this->updateCombination();
		}
		
		$optionIds = $this->getOptionIds();
		
		$insertValues = array(
			'vcproductid' => $productId,
			'vcvariationid' => $variationId,
			'vcenabled' => $this->getData('Activa'),
			'vcoptionids' => $optionIds,
			'vcsku' => $this->getData('SKU'),
			'vcpricediff' => $this->getData('OperacionPrecio'),
			'vcprice' => $this->getData('Precio', 0),
			'vcweightdiff' => $this->getData('OperacionPeso'),
			'vcweight' => $this->getData('Peso', 0),
		);
		
		if($this->getData('Precio', 0) > 0) $insertValues['vcpricediff'] = 'fixed';
		if($this->getData('Peso', 0) > 0) $insertValues['vcweightdiff'] = 'fixed';
		
		if($this->getData('Articulo') == '') {
			$insertValues['vcenabled'] = 0;
		}
		
		if($this->getData('NombreImagen') != ''){
			if(($imgpath = $this->moveImageFile()) == ''){
				return false;
			}
		
			try {
				$image = ISC_PRODUCT_IMAGE::importImage(
					$imgpath,
					$this->getData('NombreImagen'),
					false,
					false,
					false,
					false
				);
			}
			catch(Exception $e)
			{
				logAdd(LOG_SEVERITY_ERROR, 'Excepcion al crear la imagen ID "'.$this->getAttribute('IDImagen').'" Archivo "'.$imgpath.'". '.$e->__toString ().'<br/>Archivo: '.$this->getXMLfilename());
				return false;
			}
	
			$insertValues['vcimage'] = $image->getSourceFilePath();
			$insertValues['vcimagezoom'] = $image->getResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_ZOOM, true, false);
			$insertValues['vcimagestd'] = $image->getResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_STANDARD, true, false);
			$insertValues['vcimagethumb'] = $image->getResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL, true, false);
		}
		
		if(!$combinationid = $GLOBALS['ISC_CLASS_DB']->InsertQuery('product_variation_combinations', $insertValues))
		{
			logAdd(LOG_SEVERITY_ERROR, $GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
		else
		{
			$insert = array(
				'combinationid' => $combinationid,
				'IDCombinacion' => $this->getData('ID'),
				'Articulo' => $this->getData('Articulo'),
				'Situacion' => $this->getData('Situacion'),
				'SubCuenta' => $this->getData('SubCuenta'),
			);
			if($GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_variation_combinations', $insert)){
				logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis creo la combinacion "'.$optionIds.'" para el producto "'.$productId.'"');
				return true;
			}
			else {
				logAdd(LOG_SEVERITY_ERROR, 'Error al registrar la combinacionid "'.$combinationid.'" IDCombinacion "'.$this->getData('ID').'". Archivo: '.$this->getXMLfilename().'.<br/>Error: '.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
		}
	}
	
	private function updateCombination() {
		$productId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT productid FROM [|PREFIX|]intelisis_products WHERE ArticuloID = "'.$this->getAttribute('IDArticulo').'"', 'productid');
		$variationId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT variationid FROM [|PREFIX|]intelisis_variations WHERE VariacionID = "'.$this->getData('IDVariacion').'"', 'variationid');

		if($productId == '' || $variationId == '' || !$productId || !$variationId){
			logAdd(LOG_SEVERITY_ERROR, 'Error al encontrar el ArticuloID "'.$this->getAttribute('IDArticulo').'" o la VariacionID "'.$this->getData('IDVariacion').'" de la combinacion "'.$this->getData('ID').'". Archivo: '.$this->getXMLfilename().'.<br/> Error: '.$GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
		
		$combinationid = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT combinationid FROM [|PREFIX|]intelisis_variation_combinations WHERE IDCombinacion = "'.$this->getData('ID').'"', 'combinationid');
		if(!$combinationid || $combinationid == ''){
			//logAdd(LOG_SEVERITY_ERROR, 'No se encontro la combinationid con la condicion WHERE '.$where.' Archivo: '.$this->getXMLfilename().'<br/>Error: '.$GLOBALS['ISC_CLASS_DB']->Error());
			return $this->createCombination();
		}
		
		$optionIds = $this->getOptionIds();
		
		$updateValues = array(
			'vcenabled' => $this->getData('Activa'),
			'vcsku' => $this->getData('SKU'),
			'vcpricediff' => $this->getData('OperacionPrecio'),
			'vcprice' => $this->getData('Precio', 0),
			'vcweightdiff' => $this->getData('OperacionPeso'),
			'vcweight' => $this->getData('Peso', 0),
		);
		
		if($this->getData('Precio', 0) > 0) $updateValues['vcpricediff'] = 'fixed';
		if($this->getData('Peso', 0) > 0) $updateValues['vcweightdiff'] = 'fixed';
		
		$where = 'vcproductid = "'.$productId.'" AND vcvariationid = "'.$variationId.'" AND vcoptionids = "'.$optionIds.'"';
		//$combinationid = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT combinationid FROM [|PREFIX|]product_variation_combinations WHERE '.$where, 'combinationid');
		if(!$combinationid || $combinationid == ''){
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro la combinationid con la condicion WHERE '.$where.' Archivo: '.$this->getXMLfilename().'<br/>Error: '.$GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
		
		if($this->getData('Articulo') == '') {
			$updateValues['vcenabled'] = 0;
		}
		
		if($this->getData('NombreImagen') != ''){
			if(($imgpath = $this->moveImageFile()) == ''){
				return false;
			}
			
			try {
				$image = ISC_PRODUCT_IMAGE::importImage(
					$imgpath,
					$this->getData('NombreImagen'),
					false,
					false,
					false,
					false
				);
			}
			catch(Exception $e)
			{
				logAdd(LOG_SEVERITY_ERROR, 'Excepcion al crear la imagen ID "'.$this->getAttribute('IDImagen').'" Archivo "'.$imgpath.'". '.$e->__toString ().'<br/>Archivo: '.$this->getXMLfilename());
				return false;
			}
	
			$updateValues['vcimage'] = $image->getSourceFilePath();
			$updateValues['vcimagezoom'] = $image->getResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_ZOOM, true, false);
			$updateValues['vcimagestd'] = $image->getResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_STANDARD, true, false);
			$updateValues['vcimagethumb'] = $image->getResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL, true, false);
		}		
		if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('product_variation_combinations', $updateValues, $where))
		{
			logAdd(LOG_SEVERITY_ERROR, $GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
		else
		{
			$update = array(
				'combinationid' => $combinationid,
				'IDCombinacion' => $this->getData('ID'),
				'Articulo' => $this->getData('Articulo'),
				'Situacion' => $this->getData('Situacion'),
				'SubCuenta' => $this->getData('SubCuenta'),
			);
			if($GLOBALS['ISC_CLASS_DB']->CountResult($GLOBALS['ISC_CLASS_DB']->Query('SELECT combinationid
					FROM [|PREFIX|]intelisis_variation_combinations
					WHERE combinationid = "'.$combinationid.'"')) > 0){
						$GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_variation_combinations', $update, 'combinationid = "'.$combinationid.'"');
					}
					else {
						$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_variation_combinations', $update);
					}
			if($GLOBALS['ISC_CLASS_DB']->Error() == '') {
				logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis edito la combinacion "'.$optionIds.'" para el producto "'.$productId.'"');
				return true;
			}
			else {
				logAdd(LOG_SEVERITY_ERROR, 'Error al editar la combinacionid "'.$combinationid.'" IDCombinacion "'.$this->getData('ID').'". Archivo: '.$this->getXMLfilename().'.<br/>Error: '.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
		}		
	}
	
	private function deleteCombination() {
		$productId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT productid FROM [|PREFIX|]intelisis_products WHERE ArticuloID = "'.$this->getAttribute('IDArticulo').'"', 'productid');
		$optionIds = $this->getOptionIds();

		if($GLOBALS['ISC_CLASS_DB']->DeleteQuery('product_variation_combinations', 'WHERE vcproductid = "'.$productId.'" AND vcoptionids = "'.$optionIds.'"'))
		{
			$GLOBALS['ISC_CLASS_DB']->DeleteQuery('intelisis_variation_combinations', 'WHERE IDCombinacion = "'.$this->getAttribute('ID').'"');
			logAdd(LOG_SEVERITY_SUCCESS, 'Se elimino el registro de la combinacion de las opciones ids "'.$optionIds.'" del productid "'.$productId.'"');
			return true;
		}
		else
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al eliminar el registro de la combinacion de las opciones ids "'.$optionIds.'" del productid "'.$productId.'".<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
	}
}
