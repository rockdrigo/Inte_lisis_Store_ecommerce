<?php

class ISC_INTELISIS_ECOMMERCE_ANEXOCTA extends ISC_INTELISIS_ECOMMERCE_CATALOGO {
	
	public $origenPath;
	
	public $tablename = 'intelisis_anexocta';
	public $pk = array(
			'Rama' => 'Rama',
			'Cuenta' => 'Cuenta',
			'IDR' => 'IDR'
			);
			
	public function getTableArray() { //Llenar dependiendo del XML
		$Arreglo = array(
			'Rama' => $this->getData('Rama'),
			'Cuenta' => $this->getData('Cuenta'),
			'IDR' => $this->getData('IDR'),
			'Nombre' => $this->getData('Nombre'),
			'Direccion' => $this->getData('Direccion'),
			'Icono' => $this->getData('Icono'),
			'Tipo' => $this->getData('Tipo'),
			'Orden' => $this->getData('Orden'),
			'Comentario' => $this->getData('Comentario'),
			'Origen' => $this->getData('Origen'),
			'Destino' => $this->getData('Destino'),
			'Fecha' => $this->getData('Fecha'),
			'FechaEmision' => $this->getData('FechaEmision'),
			'Vencimiento' => $this->getData('Vencimiento'),
			'TipoDocumento' => $this->getData('TipoDocumento'),
			'Inicio' => $this->getData('Inicio'),
			'Alta' => $this->getData('Alta'),
			'UltimoCambio' => $this->getData('UltimoCambio'),
			'Usuario' => $this->getData('Usuario'),
			'NivelAcceso' => $this->getData('NivelAcceso'),
			'Categoria' => $this->getData('Categoria'),
			'Grupo' => $this->getData('Grupo'),
			'Familia' => $this->getData('Familia'),
			'Direccion2' => $this->getData('Direccion2'),
			'Direccion3' => $this->getData('Direccion3'),
		);		
		return $Arreglo;
	}

	public function createUpdatePrehook(){
		$Clave = substr(GetConfig('tablePrefix'), 0, strlen(GetConfig('tablePrefix'))-1);
		$ext = substr($this->getData('Direccion'), strrpos($this->getData('Direccion'), '.'));
		if($this->getData('Rama') == 'INV'){
			$directory = 'Articulo';
		}elseif($this->getData('Rama') == 'CTE'){
			$directory = 'Cliente';
		}else{
			logAddError('Error al procesar archivo '.$this->getData('Direccion').' el tipo de cuenta '.$this->getData('Rama').' no es valido');
			return false;
		}
		
		$temporaryPath = GetConfig('syncDropboxDir').$Clave.DIRECTORY_SEPARATOR.'Anexos'.DIRECTORY_SEPARATOR.'Cuenta'.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$this->getData('Cuenta').DIRECTORY_SEPARATOR.$this->getData('Direccion');
		$GLOBALS['origenPath'] = $temporaryPath;
		
		if(!file_exists($temporaryPath)){
			logAddError('No se encontro el archivo '.$this->getData('Nombre').' en la direccion: '.$temporaryPath);
			return false;
		}
		
		$newPath = ISC_BASE_PATH.DIRECTORY_SEPARATOR.GetConfig('ImageDirectory').DIRECTORY_SEPARATOR.'uploaded_images'.DIRECTORY_SEPARATOR;
		
		if(!is_dir($newPath.'Anexos')){
			mkdir($newPath.'Anexos');
		}
		$newPath .= 'Anexos'.DIRECTORY_SEPARATOR;
		
		if(!is_dir($newPath.'Cuenta')){
			mkdir($newPath.'Cuenta');
		}
		$newPath .= 'Cuenta'.DIRECTORY_SEPARATOR;
		
		if(!is_dir($newPath.$directory)){
			mkdir($newPath.$directory);
		}
		$newPath .= $directory.DIRECTORY_SEPARATOR;
		
		if(!is_dir($newPath.$this->getData('Cuenta'))){
			mkdir($newPath.$this->getData('Cuenta'));
		}
		$newPath .= $this->getData('Cuenta').DIRECTORY_SEPARATOR;
		
		if(!copy($temporaryPath, $newPath.$this->getData('Direccion'))){
			logAddError('Error al copiar el archivo: '.$this->getData('Direccion').' a la ruta '.$newPath);
			return false;
		}
		
	}
	
	public function createUpdatePostHook(){
		unlink($GLOBALS['origenPath']);
	}
	
	public function deletePosthook(){
		
		
		if($this->getData('Rama') == 'INV'){
			$directory = 'Articulo';
		}elseif($this->getData('Rama') == 'CTE'){
			$directory = 'Cliente';
		}else{
			logAddError('Error al procesar archivo '.$this->getData('Direccion').' el tipo de cuenta '.$this->getData('Rama').' no es valido');
			return false;
		}
		$DeleteFilePath = ISC_BASE_PATH.DIRECTORY_SEPARATOR.GetConfig('ImageDirectory').DIRECTORY_SEPARATOR.'uploaded_images'.DIRECTORY_SEPARATOR.'Anexos'.DIRECTORY_SEPARATOR.'Cuenta'.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$this->getData('Cuenta').DIRECTORY_SEPARATOR;
		
		if(file_exists($DeleteFilePath.$this->getData('Direccion'))){
			unlink($DeleteFilePath.$this->getData('Direccion'));
		}
	}
	
}