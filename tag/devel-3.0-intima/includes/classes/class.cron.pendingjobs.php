<?php

class ISC_CRON_PENDINGJOBS {
	private $Clave = '';
	private $numberToProcess = 100;
	
	private $dropbox_dir = '';
	private $Archive_dir = '';
	private $syncFileNameInc = '';
	private $syncPathToType = '';
	private $syncTypeAtributeName = '';
	
	private $pattern_before = '';
	private $pattern_after = '';
	
	function __construct() {
		$this->Clave = substr($GLOBALS['ISC_CLASS_DB']->TablePrefix, 0, strlen($GLOBALS['ISC_CLASS_DB']->TablePrefix)-1);
		
		if(GetConfig('syncDropboxDir') == '' || !is_dir(GetConfig('syncDropboxDir'))) die('El directorio de Dropbox configurado "'.GetConfig('syncDropboxDir').'" no es valido');
		
		// Usar siempre "/" como separador aun cuando estamos en Windows, para que no se rompa luego el path pasado a file_get_contents()
		$this->dropbox_dir = GetConfig('syncDropboxDir');
		$this->Archive_dir = GetConfig('syncArchiveDir');
		
		$this->syncFileNameInc = GetConfig('syncFileNameInc');
		$this->syncPathToType = GetConfig('syncPathToType');
		$this->syncTypeAtributeName = GetConfig('syncTypeAtributeName');
		
		$this->pattern_before = substr($this->syncFileNameInc, 0, strpos($this->syncFileNameInc, '%s') );
		$this->pattern_after = substr($this->syncFileNameInc, strpos($this->syncFileNameInc, '%s') + 2);
	}
	
	public function setNumberToProcess($i) {
		$this->numberToProcess = (is_numeric($i) && $i > 0) ? $i : 100;
	}
	
	private function processDir($dirpath = '', $limit, $movefiles = true){
		$files = array();
		$store_dir_handle = opendir($dirpath);
		if (!$store_dir_handle) {
			return false;
		}
		
		// Aqui ponemos un limite de 100 archivos si es que no se especifico un limite en $i. Siempre es 100 pero se puede cambiar o hacer configurable
		// ToDo: Hacer variable de configuracion syncFilesToProcess, que sea default 100 o algo asi
		$j = 0;
		while ($limit != $j && false !== ($xml_file = readdir($store_dir_handle))) {
			if ((substr($xml_file, 0, strlen($this->pattern_before)) == $this->pattern_before) && substr($xml_file, -strlen($this->pattern_after)) == $this->pattern_after){
				$fileID = (int)str_replace($this->pattern_after, '', str_replace($this->pattern_before, '', $xml_file));
				$files[$fileID] = $xml_file;
				$j++;
			}
		}
		
		ksort($files);
		
		foreach($files as $key => $file)
		{
			$this->processFile($dirpath, $file, $movefiles);
			ob_flush();
		}
	}
	
	public function processDropboxDir(){
		$dirpath = $this->dropbox_dir.$this->Clave;
		$this->processDir($dirpath, $this->numberToProcess, true);
		
		$this->doLog();
		
		if(GetConfig('isIntelisis')){
			$toDelete = checkIntelisisTables();
			if(!empty($toDelete)) purgeIntelisisTables($toDelete);
			$chmod = 'find '.ISC_BASE_PATH.DIRECTORY_SEPARATOR.GetConfig('ImageDirectory').' -user sincro -exec chmod a+w {} +';
			$out = array();
			if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') exec($chmod, $out);
		}

	}
	
	public function processErrorDir(){
		$dirpath = $this->Archive_dir.$this->Clave.'/Errores';
		$GLOBALS['cronPrendingJobsErrorJob'] = true;
		$this->processDir($dirpath, -1, false);
		
		$this->doLog(true);
	}
	
	private function processFile($dirpath, $file, $movefile = true) {
		$xml_errors = array();
		print('Procesando archivo '.$file);
		
		libxml_use_internal_errors(true);
		$xml_contents = file_get_contents($dirpath.'/'.$file);
		try {
			$xml_dom = new SimpleXMLElement($xml_contents);
		}
		catch (Exception $e) {
			$xml_errors[$file] = array();
			foreach(libxml_get_errors() as $error) {
				$xml_errors[$file][] = $error->message;
			}
		}
		
		foreach($xml_errors as $file => $errors) {
			logAdd(LOG_SEVERITY_ERROR, 'Error al procesar XML "'.$file.'"', 'Errores: '.implode('<br/>', $errors));
			rename($dirpath.'/'.$file, $this->Archive_dir.$this->Clave.'/XMLMalFormado/'.$file);
			printe(" [ERROR]");
			return;
		}
	
		if(isset($xml_dom)) {
			$root = $xml_dom->xpath($this->syncPathToType);
			$attribs = $root['0']->attributes();
			$syncTypeAtributeName = $this->syncTypeAtributeName;
			$type = (string)$attribs->$syncTypeAtributeName;
	
			//Interfaz con Intelisis activada. Verificar y correr trabajos pendientes.
			if(GetConfig('isIntelisis')){
				printe('. Referencia: "'.$type.'"');
				
				if(trim($type) == '') {
					logAdd(LOG_SEVERITY_WARNING, 'Se recibio el Archivo "'.$file.'" Sin Referencia ('.$this->syncPathToType.'/'.$syncTypeAtributeName.')');
					rename($dirpath.'/'.$file, $this->Archive_dir.$this->Clave.'/XMLMalFormado/'.$file);
				}
				
				//Esto convierte la Referencia en el nombre de la clase, Ej. Intelisis.eCommerce.Cte es recibido por la clase ISC_INTELISIS_ECOMMERCE_CTE
				$class_name = strtoupper(str_replace(array(substr($type, 0, mb_strpos($type, '.')+1), '.'), array('ISC_'.strtoupper(substr($type, 0, mb_strpos($type, '.'))).'_', '_'), $type));
				if(!class_exists($class_name))
				{
					logAdd(LOG_SEVERITY_WARNING, 'Todavia no hay una clase para manejar las Referencias "'.$type.'". El archivo '.$file.' sera movido al subdirectorio "Errores"');
					rename($dirpath.'/'.$file, $this->Archive_dir.$this->Clave.'/Errores/'.$file);
				}
				else
				{
					$class = GetClass($class_name);
					$class->setXMLdom($xml_dom);
					$class->setXMLfilename($dirpath.'/'.$file);
					if($class->ProcessData())
					{
						rename($dirpath.'/'.$file, $this->Archive_dir.$this->Clave.'/procesados/'.$file);
						print("[Y]");
					}
					else 
					{
						if($movefile) rename($dirpath.'/'.$file, $this->Archive_dir.$this->Clave.'/Errores/'.$file);
					}
					print('. '.$class->getAttribute('Estatus').' ');
					unset($class);
				}
			}
			printe('Procesado');
		}
	}
	
	public function doLog($onlySuccess = false) {
		if(!empty($GLOBALS['cron_log'])){
			foreach($GLOBALS['cron_log'] as $log) {
				if($onlySuccess == true && $log['severity'] != LOG_SEVERITY_SUCCESS) continue;
				switch($log['severity']){
					case LOG_SEVERITY_SUCCESS:
						logAddSuccess($log['msg'], array('php', 'cron'), $log['trace']);
						break;
					case LOG_SEVERITY_WARNING:
						logAddWarning($log['msg'], array('php', 'cron'), $log['trace']);
						break;
					case LOG_SEVERITY_ERROR:
						logAddError($log['msg'], array('php', 'cron'), $log['trace']);
						break;
					case LOG_SEVERITY_NOTICE:
						logAddNotice($log['msg'], array('php', 'cron'), $log['trace']);
						break;
				}
			}
			$GLOBALS['cron_log'] = array();
		}
	}
	/*
	public function moveErrorFiles(){
		$errors_dir_handle = opendir($this->dropbox_dir.$this->Clave.'/Errores/');

		while ($error_file = readdir($errors_dir_handle)) {
			//printe($error_file);
			if ((substr($error_file, 0, strlen($this->pattern_before)) == $this->pattern_before) && substr($error_file, -strlen($this->pattern_after)) == $this->pattern_after){
				//printe("ren ".$this->dropbox_dir.$this->Clave.'/Errores/'.$error_file. " a ".$this->dropbox_dir.$this->Clave.'/'.$error_file);
				rename($this->dropbox_dir.$this->Clave.'/Errores/'.$error_file, $this->dropbox_dir.$this->Clave.'/'.$error_file);
			}
		}
	}
	*/
}
