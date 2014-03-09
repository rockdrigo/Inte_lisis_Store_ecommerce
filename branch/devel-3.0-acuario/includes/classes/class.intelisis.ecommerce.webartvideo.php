<?php

class ISC_INTELISIS_ECOMMERCE_WEBARTVIDEO extends ISC_INTELISIS_ECOMMERCE
{
	public function ProcessData() {
		if($this->getXMLdom())
		{
			//printe($this->getAttribute('Estatus').": ".$this->getAttribute('Cliente'));
			switch ($this->getAttribute('Estatus')) {
				case 'ALTA':
					return $this->createVideo();
				break;
				case 'CAMBIO':
					return $this->updateVideo();
				break;
				case 'BAJA':
					return $this->deleteVideo();
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
	
	private function getYouTubeVideoDetails($videoId)
	{
		GetLib('class.youtube');
		
		// make youtube request
		$youtube = new ISC_YOUTUBE;
		
		if(!$youtube->loadVideoById($videoId)) {
			logAdd(LOG_SEVERITY_ERROR, 'error al cargar de YouTube la informacion del video "'.$videoId.'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		$video = $youtube->requestResult;
		
		$return = array();
		$namespaces = $video->getNameSpaces(true);
		
		// get the media namespace
		$media = $video->children($namespaces['media']);
		
		$videoInfo = $media->group->children($namespaces['yt']);
		$duration = $videoInfo->duration->attributes();
		
		// the duration of the vieo is given in seconds, we want to format it into minutes
		$length = date('G:i:s', (int)$duration['seconds']);
		
		// if it's less than an hour, don't show zero for the hours
		if(substr($length,0, 2) == '0:') {
			$length = substr($length, 2);
		}
		
		$return['duration'] = $length;
		
		$title = (string)$video->title;
		if(strlen($title) > 25) {
			$title = substr($title, 0, 23) . "...";
		}
		
		$return['title'] = $title;
				
		$summary = (string)$media->group->description;
		if(strlen($summary) > 85) {
			$summary = substr($summary, 0, 85) . "...";
		}
		
		$return['desc'] = $summary;

		// return results
		return $return;
	}
	
	private function createVideo() {
		$productId = $this->getProductId();
		if(!$productId)
		{
			logAdd(LOG_SEVERITY_WARNING, 'No se pudo encontrar el productid del Articulo Web ID "'.$this->getAttribute('IDArticulo').'"<br/Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		$videoDetails = $this->getYouTubeVideoDetails($this->getAttribute('IDVideo'));

		if(!$videoDetails)
		{
			return false;
		}

		$insertFields = array(
			'video_id' => $this->getAttribute('IDVideo'),
			'video_product_id' => $productId,
			'video_sort_order' => $this->getData('Orden'),
			'video_title' => $this->getData('Titulo') != '' ? $this->getData('Titulo') : $videoDetails['title'],
			'video_description' => $videoDetails['desc'],
			'video_length' => $videoDetails['duration'],
		);

		if($GLOBALS['ISC_CLASS_DB']->InsertQuery('product_videos', $insertFields))
		{
			logAdd(LOG_SEVERITY_SUCCESS, 'El video '.$this->getAttribute('IDVideo').' fue agregado al producto ID '.$productId);
			return true;
		}
		else
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al agregar el video '.$this->getAttribute('IDVideo').' al producto ID '.$productId.'. '.$GLOBALS['ISC_CLASS_DB']->Error().'<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
	}
	
	private function updateVideo() {
		$productId = $this->getProductId();
		if(!$productId)
		{
			logAdd(LOG_SEVERITY_WARNING, 'No se pudo encontrar el productid del Articulo Web ID "'.$this->getAttribute('IDArticulo').'"<br/Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		if($this->getData('Titulo') != ''){
			if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('product_videos', array('video_title' => $this->getData('Titulo')), 'video_id = "'.$this->getAttribute('IDVideo').'" AND video_product_id = "'.$productId.'"'))
			{
				logAdd(LOG_SEVERITY_ERROR, 'Error al actualizar el video "'.$this->getAttribute('IDVideo').'" del productid "". '.$GLOBALS['ISC_CLASS_DB']->Error()."<br/>Archivo: ".$this->getXMLfilename());
				return false;
			}
			else
			{
				logAdd(LOG_SEVERITY_SUCCESS, 'Se actualizo el video "'.$this->getAttribute('IDVideo').'" del productid "'.$productId.'"');
				return true;
			}
		}
		return true;
	}

	private function deleteVideo() {
		$productId = $this->getProductId();
		if(!$productId)
		{
			logAdd(LOG_SEVERITY_WARNING, 'No se pudo encontrar el productid del Articulo Web ID "'.$this->getAttribute('IDArticulo').'". Es posible que el producto ya haya sido eliminado.<br/>Archivo: '.$this->getXMLfilename());
			return true;
		}
		
		if($GLOBALS['ISC_CLASS_DB']->DeleteQuery('product_videos', 'WHERE video_id = "'.$this->getAttribute('IDVideo').'" AND video_product_id = "'.$productId.'"'))
		{
			logAdd(LOG_SEVERITY_SUCCESS, 'El video '.$this->getAttribute('IDVideo').' fue eliminado del productid '.$productId);
			return true;
		}
		else
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al eliminar el video '.$this->getAttribute('IDVideo').' del productid '.$productId.'. '.$GLOBALS['ISC_CLASS_DB']->Error().'<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
	}
}
