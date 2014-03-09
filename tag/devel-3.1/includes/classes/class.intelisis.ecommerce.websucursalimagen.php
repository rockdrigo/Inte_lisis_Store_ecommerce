<?php

class ISC_INTELISIS_ECOMMERCE_WEBSUCURSALIMAGEN extends ISC_INTELISIS_ECOMMERCE
{
	private $subData = array();
	
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
	
	private function createImage() {
		
		$Clave = substr(GetConfig('tablePrefix'), 0, strlen(GetConfig('tablePrefix'))-1);
		$temporaryPath = GetConfig('syncDropboxDir').$Clave.DIRECTORY_SEPARATOR.GetConfig('syncDropboxImagesDir').DIRECTORY_SEPARATOR.'Sucursal'.DIRECTORY_SEPARATOR.$this->getData('Nombre').$this->getData('TipoArchivo');
		$targetPath = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.GetConfig('ImageDirectory').DIRECTORY_SEPARATOR.'uploaded_images'.DIRECTORY_SEPARATOR.$this->getData('Nombre').$this->getData('TipoArchivo');
		rename($temporaryPath, $targetPath);
		
		$insert = array(
			'Sucursal' => $this->getData('Sucursal'),
			'ArchivoImagen' => $this->getData('ArchivoImagen'),
			'Orden' => $this->getData('Orden'),
			'Nombre' => $this->getData('Nombre'),
			'Descripcion' => $this->getData('Descripcion'),
			'TipoArchivo' => $this->getData('TipoArchivo'),
			'Liga' => $this->getData('Liga'),
		);
		
		$result = $GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_slider_images', $insert);
		if(!$result)
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al registrar la Imagen de Sucursal "'.$this->getData('Nombre').$this->getData('TipoArchivo').'".<br>Archivo: '.$this->getXMLfilename().'. '.$GLOBALS["ISC_CLASS_DB"]->Error());
			return false;
		}
		
		logAdd(LOG_SEVERITY_SUCCESS, 'Se registro exitosamente la Imagen de Sucursal "'.$this->getData('Nombre').$this->getData('TipoArchivo').'"');
		return true;
	}
	
	private function updateImage() {
		
		$Clave = substr(GetConfig('tablePrefix'), 0, strlen(GetConfig('tablePrefix'))-1);
		$temporaryPath = GetConfig('syncDropboxDir').$Clave.DIRECTORY_SEPARATOR.GetConfig('syncDropboxImagesDir').DIRECTORY_SEPARATOR.'Sucursal'.DIRECTORY_SEPARATOR.$this->getData('Nombre').$this->getData('TipoArchivo');
		$targetPath = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.GetConfig('ImageDirectory').DIRECTORY_SEPARATOR.'uploaded_images'.DIRECTORY_SEPARATOR.$this->getData('Nombre').$this->getData('TipoArchivo');
		rename($temporaryPath, $targetPath);
		
		$update = array(
			'Sucursal' => $this->getData('Sucursal'),
			'ArchivoImagen' => $this->getData('ArchivoImagen'),
			'Orden' => $this->getData('Orden'),
			'Nombre' => $this->getData('Nombre'),
			'Descripcion' => $this->getData('Descripcion'),
			'TipoArchivo' => $this->getData('TipoArchivo'),
			'Liga' => $this->getData('Liga'),
		);
		
		$result = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_slider_images', $update, 'Sucursal = "'.$this->getData('Sucursal').'" AND Nombre = "'.$this->getData('Nombre').'"');
		if(!$result)
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al intentar editar la Imagen de Sucursal "'.$this->getData('Nombre').$this->getData('TipoArchivo').'".<br>Archivo: '.$this->getXMLfilename().'. '.$GLOBALS["ISC_CLASS_DB"]->Error());
			return false;
		}
		else
		{
			logAdd(LOG_SEVERITY_SUCCESS, 'Se edito exitosamente la Imagen de Sucursal "'.$this->getData('Nombre').$this->getData('TipoArchivo').'"');
			return true;
		}
	}
	
	private function deleteImage() {

		$targetPath = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.GetConfig('ImageDirectory').DIRECTORY_SEPARATOR.'uploaded_images'.DIRECTORY_SEPARATOR.$this->getAttribute('Nombre').$this->getAttribute('TipoArchivo');
		unlink($targetPath);
		
		$result = $GLOBALS['ISC_CLASS_DB']->DeleteQuery('intelisis_slider_images', 'WHERE Sucursal = "'.$this->getAttribute('Sucursal').'" AND Nombre = "'.$this->getAttribute('Nombre').'"');
		if($result)
		{
			logAdd(LOG_SEVERITY_SUCCESS, 'Se elimino exitosamente la Imagen de Sucursal "'.$this->getData('Nombre').'"');
			return true;
		}
		else {
			logAdd(LOG_SEVERITY_ERROR, 'Error al intentar eliminar la Imagen de Sucursal "'.$this->getData('Nombre').'".<br>Archivo: '.$this->getXMLfilename().'. '.$GLOBALS["ISC_CLASS_DB"]->Error());
			return false;
		}
	}
}
