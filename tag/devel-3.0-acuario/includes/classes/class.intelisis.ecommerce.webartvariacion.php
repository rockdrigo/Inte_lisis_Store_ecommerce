<?php

class ISC_INTELISIS_ECOMMERCE_WEBARTVARIACION extends ISC_INTELISIS_ECOMMERCE
{
	public function ProcessData() {
		if($this->getXMLdom())
		{
			//printe($this->getAttribute('Estatus').": ".$this->getAttribute('Cliente'));
			switch ($this->getAttribute('Estatus')) {
				case 'ALTA':
					return $this->createVariation();
				break;
				case 'CAMBIO':
					return $this->updateVariation();
				break;
				case 'BAJA':
					return $this->deleteVariation();
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
	
	private function getVariationId() {
		$query = "SELECT variationid FROM [|PREFIX|]intelisis_variations WHERE VariacionID = '".$this->getAttribute('VariacionID')."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	
		return $row['variationid'] ? $row['variationid'] : false;
	}
	
	private function createVariation() {
		
		$variationid = $this->getVariationId();
		if($variationid){
			return $this->updateVariation();
		}
		
		/**
		 * Check to see if this variation name is unique
		 */
		$query = "SELECT variationid FROM [|PREFIX|]product_variations WHERE vname='" . $GLOBALS['ISC_CLASS_DB']->Quote($this->getData('Nombre')) . "' AND variationid != '".$variationid."'";
		$variationid = $GLOBALS['ISC_CLASS_DB']->FetchOne($query, 'variationid');
		if ($variationid != '') {
			/*logAdd(LOG_SEVERITY_ERROR, 'Ya existe una variacion con nombre "'.$this->getData('Nombre').'"<br/>Archivo: '.$this->getXMLfilename());*/
			$insert = array(
				'VariacionID' => $this->getAttribute('VariacionID'),
				'variationid' => $variationid,
			);
			$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_variations', $insert);
			return $this->updateVariation();
		}
		
		$savedata = array(
				'vname' => $this->getData('Nombre'),
				/*'vnumoptions' => FormatNumber($this->getData('NumeroOpciones')),*/
		);
		
		$rtn = $GLOBALS['ISC_CLASS_DB']->InsertQuery('product_variations', $savedata);
		$variationid = $rtn;
		
		if($rtn)
		{
			if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_variations', array('VariacionID' => $this->getAttribute('VariacionID'), 'variationid' => $variationid)))
			{
				logAdd(LOG_SEVERITY_ERROR, 'No se pudo relacionar la Variacion Web "'.$this->getData('Nombre').'" ID "'.$this->getAttribute('VariacionID').'" con la variationid "'.$variationid.'"<br/>Archivo: '.$this->getXMLfilename());
				return false;
			}
			else {
				logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis creo la variacion "'.$this->getData('Nombre').'" ID "'.$this->getAttribute('VariacionID').'"');
				return true;
			}
		}
		else
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al crear la variacion "'.$this->getData('Nombre').'"<br/>Archivo: '.$this->getXMLfilename());
		}

	}
	
	private function updateVariation() {
		$variationid = $this->getVariationId();
		if(!$variationid)
		{
			//logAdd(LOG_SEVERITY_ERROR, 'No se encontro la variationid para la Variacion Web ID "'.$this->getAttribute('VariacionID').'"');
			return $this->createVariation();
		}
		
		/**
		 * Check to see if this variation name is unique
		 */
		$query = "SELECT * FROM [|PREFIX|]product_variations WHERE vname='" . $GLOBALS['ISC_CLASS_DB']->Quote($this->getData('Nombre')) . "' AND variationid != '".$variationid."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if ($GLOBALS['ISC_CLASS_DB']->CountResult($result)) {
			logAdd(LOG_SEVERITY_ERROR, 'Ya existe una variacion con nombre "'.$this->getData('Nombre').'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		$savedata = array(
				'vname' => $this->getData('Nombre'),
				/*'vnumoptions' => $this->getData('NumeroOpciones'),*/
		);
		
		$rtn = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('product_variations', $savedata, 'variationid = "'.$variationid.'"');
		
		if($rtn)
		{
			logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis edito la variacion "'.$this->getData('Nombre').'" ID "'.$this->getAttribute('VariacionID').'"');
			return true;
		}
		else
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al editar la variacion "'.$this->getData('Nombre').'" Variacion Web ID "'.$this->getAttribute('VariacionID').'" variationid "'.$variationid.'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
	}

	private function deleteVariation() {
		$variation_ids = $this->getVariationId();
		
		// ISC-1650 need to delete images for deleted variation combinations, to do that we need a list of the
		// images that will be deleted before deleting the records below
		$deletedImages = array();
		$deletedCombinations = "
		SELECT DISTINCT
		vcimage, vcimagezoom, vcimagestd, vcimagethumb
		FROM
		[|PREFIX|]product_variation_combinations
		WHERE
		vcvariationid IN ('" . $variation_ids . "')
		";
		$deletedCombinations = new Interspire_Db_QueryIterator($GLOBALS["ISC_CLASS_DB"], $deletedCombinations);
		foreach ($deletedCombinations as $deletedCombination) {
			$deletedImages[$deletedCombination['vcimage']] = true;
			$deletedImages[$deletedCombination['vcimagezoom']] = true;
			$deletedImages[$deletedCombination['vcimagestd']] = true;
			$deletedImages[$deletedCombination['vcimagethumb']] = true;
		}
		
		// Delete the variation
		if (!$GLOBALS["ISC_CLASS_DB"]->DeleteQuery("product_variations", sprintf("WHERE variationid IN('%s')", $variation_ids))) {
			logAdd(LOG_SEVERITY_ERROR, 'Error al eliminar Variacion, variationid "'.$variation_ids.'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		// Delete the variation combinations
		if (!$GLOBALS["ISC_CLASS_DB"]->DeleteQuery("product_variation_combinations", sprintf("WHERE vcvariationid IN('%s')", $variation_ids))) {
			logAdd(LOG_SEVERITY_ERROR, 'Error al eliminar Combinaciones de Variacion, variationid "'.$variation_ids.'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		// Delete the variation options
		if (!$GLOBALS["ISC_CLASS_DB"]->DeleteQuery("product_variation_options", sprintf("WHERE vovariationid IN('%s')", $variation_ids))) {
			logAdd(LOG_SEVERITY_ERROR, 'Error al eliminar Opciones de Variacion, variationid "'.$variation_ids.'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		// Update the products that use this variation to not use any at all
		if (!$GLOBALS["ISC_CLASS_DB"]->UpdateQuery("products", array("prodvariationid" => "0"), "prodvariationid IN('" . $variation_ids . "')")) {
			logAdd(LOG_SEVERITY_ERROR, 'Error al quitar Variacion de Productos, variationid "'.$variation_ids.'"<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		foreach ($deletedImages as $deletedImage => $foo) {
			try {
				if (ISC_PRODUCT_IMAGE::isImageInUse($deletedImage)) {
					// the image is referenced elsewhere and should stay
					continue;
				}
			} catch (Exception $exception) {
				// something failed -- don't delete since we're unsure if the image is in use or not
				continue;
			}
		
			$deletedImagePath = ISC_BASE_PATH . '/' . GetConfig('ImageDirectory') . '/' . $deletedImage;
			if (!file_exists($deletedImagePath)) {
				continue;
			}
			// the image is not used anywhere, delete it
			unlink($deletedImagePath);
		}
		
		if(!$GLOBALS["ISC_CLASS_DB"]->DeleteQuery("intelisis_variations", sprintf("WHERE variationid IN('%s')", $variation_ids)))
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al eliminar la variacion ID "'.$variation_ids.'".'.$GLOBALS["ISC_CLASS_DB"]->Error().'<br>Archivo: '.$this->getXMLfilename());
			return false;
		}
		else
		{
			logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis elimino la Variacion, variationid "'.$variation_ids.'"');
			return true;
		}
	}
}