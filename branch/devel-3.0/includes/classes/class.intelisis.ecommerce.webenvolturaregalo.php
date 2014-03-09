<?php

class ISC_INTELISIS_ECOMMERCE_WEBENVOLTURAREGALO extends ISC_INTELISIS_ECOMMERCE
{
	private function copyImageFile($name){
		$Clave = substr(GetConfig('tablePrefix'), 0, strlen(GetConfig('tablePrefix'))-1);
		$origPath = GetConfig('syncDropboxDir').$Clave.DIRECTORY_SEPARATOR.'Imagenes'.DIRECTORY_SEPARATOR.'Envoltura'.DIRECTORY_SEPARATOR.$this->getData('Imagen');
		if(!file_exists($origPath)){
			logAdd(LOG_SEVERITY_WARNING, 'No se encontro la imagen "" para agregar a la envoltura ID "'.$this->getAttribute('ID').'" Nombre "'.$this->getData('Nombre').'"');
			return false;
		}
		
		$destPath = ISC_BASE_PATH . '/' . GetConfig('ImageDirectory') . '/wrap_images/' . $name;
		if(!copy($origPath, $destPath)){
			logAdd(LOG_SEVERITY_ERROR, 'Error al copiar la imagen de "'.$origPath.'" a "'.$destPath.'"');
			return false;
		}
		
		return true;
	}
	
	private function CommitWrap($data, $wrapId=0)
	{
		if(!isset($data['wrapvisible'])) {
			$data['wrapvisible'] = 0;
		}
	
		if(!isset($data['wrapallowcomments'])) {
			$data['wrapallowcomments'] = '';
		}
	
		$wrapData = array(
				'wrapname' => $data['wrapname'],
				'wrapprice' => DefaultPriceFormat($data['wrapprice']),
				'wrapvisible' => (int)$data['wrapvisible'],
				'wrapallowcomments' => (int)$data['wrapallowcomments'],
		);
	
		if(isset($data['wrappreview'])) {
			$wrapData['wrappreview'] = $data['wrappreview'];
		}
	
		if($wrapId == 0) {
			$wrapId = $GLOBALS['ISC_CLASS_DB']->InsertQuery('gift_wrapping', $wrapData);
		}
		else {
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('gift_wrapping', $wrapData, "wrapid='".(int)$wrapId."'");
		}
		
		$register = array(
				'IDEnvoltura' => $this->getAttribute('ID'),
				'wrapid' => $wrapId
		);
		
		if($GLOBALS['ISC_CLASS_DB']->GetErrorMsg()) {
			logAdd(LOG_SEVERITY_ERROR, 'Error al guardar la Envoltura ID "'.$wrapId.'" Nombre "'.$wrapData['wrapname'].'".<br/>Error: ');
			return false;
		}
		
		$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateGiftWrapping();
		
		if(!$GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT wrapid FROM [|PREFIX|]intelisis_gift_wrapping WHERE IDEnvoltura = "'.$this->getAttribute('ID').'"', 'wrapid')){
			$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_gift_wrapping', $register);
		}
		else{
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_gift_wrapping', $register, 'IDEnvoltura = "'.$this->getAttribute('ID').'"');
		}
	
		// Couldn't save? return an error message
		if($GLOBALS['ISC_CLASS_DB']->GetErrorMsg()) {
			logAdd(LOG_SEVERITY_ERROR, 'Error al registrar la Envoltura ID "'.$wrapId.'" Nombre "'.$wrapData['wrapname'].'".<br/>Error: ');
			return false;
		}
	
		logAdd(LOG_SEVERITY_SUCCESS, 'Se creo la envoltura "'.$wrapData['wrapname'].'" con ID "'.$this->getAttribute('ID').'"');
		return true;
	}
	
	public function create(){
		$message = '';
		$data = array(
				'wrapId' => 0,
				'wrapname' => $this->getData('Nombre'),
				'wrapprice' => $this->getData('Precio'),
				'wrapallowcomments' => $this->getData('PermiteMensaje', 1),
				'wrapvisible' => $this->getData('Visible', 1),
		);
		
		if($data['wrapname'] == '' || $data['wrapprice'] == ''){
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro el nombre ('.$data['wrapname'].') o precio ('.$data['wrapprice'].') de la envoltura. '.$this->getXMLfilename());
			return false;
		}
		
		if($wrapId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT wrapid FROM [|PREFIX|]intelisis_gift_wrapping WHERE IDEnvoltura = "'.$this->getAttribute('ID').'"', 'wrapid')){
			$data['wrapId'] = $wrapId;
		}
		
		// Check that a duplicate wrapping type doesn't exist with this name
		$query = "
			SELECT wrapid
			FROM [|PREFIX|]gift_wrapping
			WHERE wrapname='".$GLOBALS['ISC_CLASS_DB']->Quote($data['wrapname'])."'";
		
		if($data['wrapId'] && $data['wrapId'] != '' && $data['wrapId'] > 0) {
			$query .= " AND wrapid!='".(int)$data['wrapId']."'";
		}
		
		if($GLOBALS['ISC_CLASS_DB']->FetchOne($query)) {
			logAdd(LOG_SEVERITY_ERROR, 'La envoltura "'.$data['wrapname'].'" ya existe con el ID "'.$data['wrapId'].'". '.$this->getXMLfilename());
			return false;
		}
		
		if($this->getData('Imagen') != ''){
			$imgName = GenRandFileName($this->getData('Imagen'));
			if(!$this->copyImageFile($imgName)){
				@unlink(ISC_BASE_PATH . '/' . GetConfig('ImageDirectory') . '/wrap_images/' . $imgName);
				return false;
			}
			$data['wrappreview'] = 'wrap_images/' . $imgName;
		}
		
		if(!$this->CommitWrap($data, $data['wrapId'])) {
			return false;
		}
		else {
			@unlink(GetConfig('syncDropboxDir').$Clave.DIRECTORY_SEPARATOR.'Imagenes'.DIRECTORY_SEPARATOR.'Envoltura'.DIRECTORY_SEPARATOR.$this->getData('Imagen'));
			return true;
		}
	}
	
	public function update(){
			return $this->create();
	}
	
	public function delete(){
		
		$wrapid = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT wrapid FROM [|PREFIX|]intelisis_gift_wrapping WHERE IDEnvoltura = "'.$this->getAttribute('ID').'"', 'wrapid');
		
		if(!$wrapid){
			return true;
		}
		
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('intelisis_gift_wrapping', "WHERE IDEnvoltura = '".$this->getAttribute('ID')."'");
		
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('gift_wrapping', "WHERE wrapid = '".$wrapid."'");
		
		$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateGiftWrapping();
		
		$err = $GLOBALS['ISC_CLASS_DB']->GetErrorMsg();
		if($err) {
			logAdd(LOG_SEVERITY_ERROR, 'Error al eliminar la Envoltura ID "'.$this->getAttribute('ID').'". Archivo: '.$this->getXMLfilename().'<br/>Error: '.$err);
			return false;
		}
		else {
			logAdd(LOG_SEVERITY_SUCCESS, 'Se elimino la Envoltura ID "'.$this->getAttribute('ID').'"');
			return true;
		}
		
	}
}
