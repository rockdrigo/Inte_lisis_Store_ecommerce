<?php

class ISC_INTELISIS_ECOMMERCE_WEBARTIMAGEN extends ISC_INTELISIS_ECOMMERCE
{
	private $temporaryPath = '';
	private $temporaryImgName = '';
	
	public function ProcessData() {
		if($this->getXMLdom())
		{
			//printe($this->getAttribute('Estatus').": ".$this->getAttribute('Cliente'));
			switch ($this->getAttribute('Estatus')) {
				case 'ALTA':
					return $this->createImage();
				break;
				case 'CAMBIO':
					return $this->updateImage();
				break;
				case 'BAJA':
					return $this->deleteImage();
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
	
	private function getProductId() {
		$query = "SELECT productid FROM [|PREFIX|]intelisis_products WHERE ArticuloID = '".$this->getAttribute('IDArticulo')."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	
		return $row['productid'] ? $row['productid'] : false;
	}
	
	private function getImageId() {
		$query = "SELECT imageid FROM [|PREFIX|]intelisis_images WHERE ImagenID = '".$this->getAttribute('IDImagen')."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	
		return $row['imageid'] ? $row['imageid'] : false;
	}
	
	private function moveImageFile(){
		if(!file_exists($this->temporaryPath)){
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro la imagen "'.$this->temporaryPath.'" origen para moverla y procesarla');
			return '';
		}
		
		$Clave = substr(GetConfig('tablePrefix'), 0, strlen(GetConfig('tablePrefix'))-1);
		
		$newDir = GetConfig('syncArchiveDir').$Clave.DIRECTORY_SEPARATOR.'Imagenes'.DIRECTORY_SEPARATOR.$this->getAttribute('IDArticulo');
		
		if(!is_dir($newDir)){
			if(!mkdir($newDir, 0777, true)){
				logAdd(LOG_SEVERITY_ERROR, 'Error al crear el directorio "'.$newDir.'" para guardar las imagenes');
				return '';
			}
		}
		
		$newFile = $newDir.DIRECTORY_SEPARATOR.$this->temporaryImgName;
		
		if(!rename($this->temporaryPath, $newFile)){
			logAdd(LOG_SEVERITY_ERROR, 'Error al intentar mover el archivo "'.$this->temporaryPath.'" a "'.$newFile.'"');
			return '';
		}
		else {
			return $newFile;
		}
	}

	private function setTemporaryPath(){
		$Clave = substr(GetConfig('tablePrefix'), 0, strlen(GetConfig('tablePrefix'))-1);
		$ext = substr($this->getData('ArchivoImagen'), strrpos($this->getData('ArchivoImagen'), '.'));
		$temporaryPath = GetConfig('syncDropboxDir').$Clave.DIRECTORY_SEPARATOR.'Imagenes'.DIRECTORY_SEPARATOR.$this->getAttribute('IDArticulo').DIRECTORY_SEPARATOR.$this->getData('Nombre').$ext;
		
		$this->temporaryPath = $temporaryPath;
		$this->temporaryImgName = $this->getData('Nombre').$ext;
	}
	
	private function createImage() {
	
		$imageId = $this->getImageId();
		if($imageId){
			logAdd(LOG_SEVERITY_WARNING, 'Ya existe la imageid "'.$imageId.'" que es la ImagenID "'.$this->getAttribute('IDArticulo').'"');
			$this->moveImageFile();
			return true;
		}
		
		$productId = $this->getProductId();
		if(!$productId)
		{
			logAdd(LOG_SEVERITY_WARNING, 'No se pudo encontrar el productid del Articulo Web ID "'.$this->getAttribute('IDArticulo').'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		$this->setTemporaryPath();
		
		try
		{
			$image = ISC_PRODUCT_IMAGE::importImage($this->temporaryPath, $this->getData('Nombre'), $productId, NULL, false);
		}
		catch(Exception $e)
		{
			logAdd(LOG_SEVERITY_ERROR, 'Excepcion al crear la imagen ID "'.$this->getAttribute('IDImagen').'" Archivo "'.$this->temporaryPath.'". '.$e->__toString ().'<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		if($this->getData('Orden', '') != '' && $imageId)
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('product_images', array('imagesort' => $this->getData('Orden')), 'imageid = "'.$imageId.'"');
		
		if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_images', array('ImagenID' => $this->getAttribute('IDImagen'), 'imageid' => $image->getProductImageId())))
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al registrar la imagen ID "'.$this->getAttribute('IDImagen').'" Archivo "'.$this->getData('Nombre').'". '.$GLOBALS['ISC_CLASS_DB']->Error().'<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		else {
			$this->moveImageFile();
			logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis agrego la imagen ID "'.$this->getAttribute('IDImagen').'" nombre "'.$this->getData('Nombre').'" al producto ID "'.$productId.'"');
			return true;
		}
	}
	
	private function updateImage() {
		$imageId = $this->getImageId();
		if(!$imageId){
			return $this->createImage();
		}
		
		$productId = $this->getProductId();
		if(!$productId)
		{
			logAdd(LOG_SEVERITY_WARNING, 'No se pudo encontrar el productid del Articulo Web ID "'.$this->getAttribute('IDArticulo').'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		$this->setTemporaryPath();
		
		$image = new ISC_PRODUCT_IMAGE($imageId);
		$image->delete(true, true, $newThumbnailId, false);
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('intelisis_images', 'WHERE imageid = "'.$imageId.'"');
		
		try
		{
			$newimage = $image::importImage($this->temporaryPath, $this->getData('Nombre'), $productId, NULL, false);
		}
		catch(Exception $e)
		{
			logAdd(LOG_SEVERITY_ERROR, 'Excepcion al cambiar la imagen ID "'.$this->getAttribute('IDImagen').'" Archivo "'.$this->temporaryPath.'". '.$e->__toString ().'<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		if($this->getData('Orden', '') != '' && $imageId)
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('product_images', array('imagesort' => $this->getData('Orden')), 'imageid = "'.$newimage->getProductImageId().'"');
		
		if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_images', array('ImagenID' => $this->getAttribute('IDImagen'), 'imageid' => $newimage->getProductImageId())))
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al registrar la imagen ID "'.$this->getAttribute('IDImagen').'" Archivo "'.$this->getData('Nombre').'". '.$GLOBALS['ISC_CLASS_DB']->Error().'<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		else {
			$this->moveImageFile();
			logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis cambio la imagen ID "'.$this->getAttribute('IDImagen').'" nombre "'.$this->getData('Nombre').'" al producto ID "'.$productId.'"');
			return true;
		}
	}

	private function deleteImage() {
		$productId = $this->getProductId();
		if(!$productId)
		{
			logAdd(LOG_SEVERITY_WARNING, 'No se pudo encontrar el productid del Articulo Web ID "'.$this->getAttribute('IDArticulo').'". Es posible que el producto ya haya sido eliminado.<br/>Archivo: '.$this->getXMLfilename());
			return true;
		}
		$productHash = false;
		
		$imageId = $this->getImageId();
		if(!$imageId)
		{
			logAdd(LOG_SEVERITY_WARNING, 'No se pudo encontrar la imageid del de la ImagenID "'.$this->getAttribute('IDImagen').'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}

		if (isset($productId)) {
			if (!isId($productId) || !ProductExists($productId)) {
				logAdd(LOG_SEVERITY_ERROR, 'No existe el producto 1');
				return false;
			}
		} else {
			logAdd(LOG_SEVERITY_ERROR, 'No existe el producto 2');
			return false;
		}

		$deletes = array();
		$errors = array();
		$warnings = array();
		$newThumbnailId = null;

		if (!(int)$imageId) {
			logAdd(LOG_SEVERITY_ERROR, 'Imagen ID invalida');
			return false;
		}

		try {
			$image = new ISC_PRODUCT_IMAGE($imageId);
		} catch (ISC_PRODUCT_IMAGE_RECORDNOTFOUND_EXCEPTION $exception) {
			// record was not found in database, so it's already been deleted, mark it as deleted and skip it
			$deletes[] = $imageId;
			return true;
		} catch (Exception $exception) {
			// some other error occurred when trying to load the image, note it in errors list
			logAdd(LOG_SEVERITY_ERROR, 'Error al borrar de la base de datos');
			return false;
		}

		if ($productId) {
			if ($image->getProductId() != $productId) {
				// image does not belong to specified product id, note it in errors list
				logAdd(LOG_SEVERITY_ERROR, 'Product ID invalido 1');
				return false;
			}
		} else if ($productHash) {
			if ($image->getProductId() !== 0 || $image->getProductHash() !== $productHash) {
				// image does not belong to specified product id, note it in errors list
				logAdd(LOG_SEVERITY_ERROR, 'Product ID invalido 2');
				return false;
			}
		}

		try {
			$image->delete(true, true, $newThumbnailId);
			$deletes[] = $imageId;
		} catch (ISC_PRODUCT_IMAGE_CANNOTDELETEFILE_EXCEPTION $exception) {
			// indicates that the record was deleted but files weren't
			$deletes[] = $imageId;
			logAdd(LOG_SEVERITY_WARNING, 'Error al tratar de eliminar el archivo. El registro sí fue eliminado.');
			return false;
		} catch (Exception $exception) {
			// any other error indicates a failure to delete the record
			logAdd(LOG_SEVERITY_ERROR, 'Error desconocido al tratar de eliminar el archivo.');
			return false;
		}
		
		if(!$GLOBALS['ISC_CLASS_DB']->DeleteQuery('intelisis_images', 'WHERE imageid = "'.$imageId.'"'))
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al eliminar la imagen ID "'.$this->getAttribute('IDImagen').'".<br/>'.$this->getXMLfilename());
			return false;
		}
		else 
		{
			logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis elimino la imageid "'.$imageId.'"');
			return true;
		}
	}
}