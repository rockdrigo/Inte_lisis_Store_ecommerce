<?php

class ISC_INTELISIS_ECOMMERCE_WEBARTOPCIONVALOR extends ISC_INTELISIS_ECOMMERCE
{
	public function ProcessData() {
		if($this->getXMLdom())
		{
			switch ($this->getAttribute('Estatus')) {
				case 'ALTA':
					return $this->createVariationOptionValue();
				break;
				case 'CAMBIO':
					return $this->updateVariationOptionValue();
				break;
				case 'BAJA':
					return $this->deleteVariationOptionValue();
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

	private function getVariationData($variationId)
	{
		if (isId($variationId)) {
			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]product_variations WHERE variationid=" . (int)$variationId);
			$variation = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	
			if (!$variation) {
				return $data;
			}
	
			$data['id'] = (int)$variation['variationid'];
			$data['name'] = $variation['vname'];
			$data['vendor'] = (int)$variation['vvendorid'];
			$data['options'] = array();
	
			/**
			 * Now get the options
			 */
			$currentOption = null;
			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]product_variation_options WHERE vovariationid=" . (int)$variationId . " ORDER BY vooptionsort, vovaluesort");
	
			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
	
				/**
				 * Check to see if we are still using the same option
				 */
				if (is_null($currentOption) || $currentOption !== $row['voname']) {
					$optionKey = count($data['options']);
					$valueKey = 0;
					$currentOption = $row['voname'];
					$data['options'][$optionKey] = array(
									'index' => $optionKey,
									'name' => $row['voname'],
									'values ' => array(),
					);
				}
	
				/**
				 * Add the option
				 */
				$data['options'][$optionKey]['values'][$valueKey] = array(
									'valueid' => $row['voptionid'],
									'index' => $valueKey,
									'name' => $row['vovalue']
				);
	
				$valueKey++;
			}
		}
		
		return $data;
	}

	private function createVariationOptionValue() {
		$variationId = $this->getVariationId();
		if(!$variationId)
		{
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro la variationid para la Variacion Web ID "'.$this->getAttribute('VariacionID').'"');
			return false;
		}
		
		$data = $this->getVariationData($variationId);

		/**
		 * Do we have any data to insert/update?
		 */
		if (!is_array($data) || empty($data)) {
			return false;
		}

		$variation = null;

		if (isId($data['id'])) {
			$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]product_variations WHERE variationid = " . (int)$data['id']);
			$variation = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		}

		/**
		 * Check to see if we were given a proper variation ID
		 */
		if (isId($data['id']) && !is_array($variation)) {
			return false;
		}
		
		$origOptionName = '';
		$option = array('index' => 0, 'name' => $this->getData('Nombre'), 'values' => array());
		$optionPos = $GLOBALS['ISC_CLASS_DB']->FetchOne(
		'SELECT max(vooptionsort)+1 as "max"
		FROM [|PREFIX|]product_variation_options
		WHERE vovariationid = "'.$variationId.'"
		', 'max');
		foreach ($data['options'] as $key => $existingOption){
			if($existingOption['name'] == $this->getData('Nombre'))
			{
				$option = $existingOption;
				$optionPos = $key;
			}
		}

		$value = array();
		foreach ($option['values'] as $existingValue){
			if($existingValue['name'] == $this->getData('Valor'))
			{
				$value = $existingValue;
			}
		}
		
		$savedata = array(
			'vovariationid' => (int)$variationId,
			'voname' => $this->getData('Nombre'),
			'vovalue' => $this->getData('Valor'),
			'vooptionsort' => (int)$optionPos,
			'vovaluesort' => (int)$this->getData('Orden', 0),
		);
	
		/**
		 * Are we updating or adding
		 */
		if (!isset($value['valueid']) || !isId($value['valueid'])) {
			if ($GLOBALS['ISC_CLASS_DB']->CountResult('SELECT voname FROM [|PREFIX|]product_variation_options WHERE vovariationid = "'.$variationId.'" AND voname = "'.$this->getData('Nombre').'"') == 0) {
				$insertValues = array(
					'VariacionID' => $this->getAttribute('VariacionID'),
					'OpcionID' => $this->getData('OpcionID'),
					'Nombre' => $this->getData('Nombre'),
				);
				if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_variation_options', $insertValues))
				{
					logAdd(LOG_SEVERITY_ERROR, 'Error al registrar la Opcion "'.$this->getAttribute('OpcionID').'" de la Variacion "'.$data['name'].'".'.$GLOBALS['ISC_CLASS_DB']->Error());
					return false;
				}
			}
			
			$rtn = $GLOBALS['ISC_CLASS_DB']->InsertQuery('product_variation_options', $savedata);
			//printe('voy a insertar en product_variation_options: '.serialize($savedata));$rtn=1;
			$addValues[] = (int)$rtn;
			$newValues[$option['name']][] = (int)$rtn;
		} else {
			$isNewOption = false;
	
			/**
			 * If we are updating then we need to make sure that option name is the same for all the values within that option
			 */
			if ($origOptionName == '') {
				$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT voname FROM [|PREFIX|]product_variation_options WHERE voptionid = " . (int)$value['valueid']);
				$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
				$origOptionName = isc_html_escape($row['voname']);
				$newOptionName = $savedata['voname'];
			}
	
			$editValues[] = (int)$value['valueid'];
			$rtn = $GLOBALS['ISC_CLASS_DB']->UpdateQuery('product_variation_options', $savedata, 'voptionid=' . (int)$value['valueid']);
			//printe('voy a actualizar product_variation_options en voptionid='.(int)$value['valueid'].': '.serialize($savedata));$rtn=1;
		}
		
		$result_num = $GLOBALS['ISC_CLASS_DB']->Query('select voname
			from [|PREFIX|]product_variation_options
			where vovariationid = "'.$variationId.'"
			group by voname');
		$numOptions = $GLOBALS['ISC_CLASS_DB']->CountResult($result_num);
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery('product_variations', array('vnumoptions' => $numOptions), 'variationid = "'.$variationId.'"');

		if($rtn)
		{
			if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_variation_option_values', array('ValorID' => $this->getAttribute('ValorID'), 'voptionid' => $rtn)))
			{
				logAdd(LOG_SEVERITY_ERROR, 'Error al registrar el Valor "'.$this->getAttribute('ValorID').'" con la voptionid "'.$rtn.'" de la Variacion "'.$data['name'].'".'.$GLOBALS['ISC_CLASS_DB']->Error());
				return false;
			}
			else
			{
				logAdd(LOG_SEVERITY_SUCCESS, 'Se creo el Valor "'.$this->getData('Nombre').'" para la Opcion "'.$this->getData('Valor').'" de la Variacion "'.$data['name'].'"');
				return true;
			}
		}
		else
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al crear el Valor "'.$this->getData('Nombre').'" para la Opcion "'.$this->getData('Valor').'" de la Variacion "'.$data['name'].'".'.$GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
	}
	
	private function updateVariationOptionValue() {
		$optionID = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT voptionid FROM [|PREFIX|]intelisis_variation_option_values WHERE ValorID = "'.$this->getAttribute('ValorID').'"', 'voptionid');
		if(!$optionID)
		{
			//logAdd(LOG_SEVERITY_ERROR, 'No se encontro el voptionid del ValorID "'.$this->getAttribute('ValorID').'". Es posible que ya haya sido eliminado.<br/>Archivo: '.$this->getXMLfilename());
			return $this->createVariationOptionValue();
		}
		
		if($GLOBALS['ISC_CLASS_DB']->UpdateQuery('product_variation_options', array('vovalue' => $this->getData('Valor'), 'vovaluesort' => $this->getData('Orden', 0)), 'voptionid = "'.$optionID.'"'))
		{
			logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis cambio el nombre del Valor ID "'.$optionID.'" a "'.$this->getData('Valor').'"');
			return true;
		}
		else
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al editar el Valor ID "'.$optionID.'".'.$GLOBALS['ISC_CLASS_DB']->Error().'<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
	}

	private function deleteVariationOptionValue() {
		$optionID = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT voptionid FROM [|PREFIX|]intelisis_variation_option_values WHERE ValorID = "'.$this->getAttribute('ValorID').'"', 'voptionid');
		if(!$optionID)
		{
			logAdd(LOG_SEVERITY_WARNING, 'No se encontro el voptionid del ValorID "'.$this->getAttribute('ValorID').'". Es posible que ya haya sido eliminado.<br/>Archivo: '.$this->getXMLfilename());
			/* Nissim: Para que necesitamos esto?
			if(!$GLOBALS['ISC_CLASS_DB']->DeleteQuery('intelisis_variation_option_values', 'WHERE ValorID = "'.$this->getData('ValorID').'"'))
			{
				logAddNotice('No se pudo eliminar el registro del ValorID "'.$this->getAttribute('ValorID').'".<br/>Archivo: '.$this->getXMLfilename());
			}
			*/
			return true;
		}
		
		if(!$GLOBALS['ISC_CLASS_DB']->DeleteQuery('product_variation_options', 'WHERE voptionid = "'.$optionID.'"'))
		{
			
			$result_num = $GLOBALS['ISC_CLASS_DB']->Query('select voname
				from [|PREFIX|]product_variation_options
				where vovariationid = "'.$variationId.'"
				group by voname');
			$numOptions = $GLOBALS['ISC_CLASS_DB']->CountResult($result_num);
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('product_variations', array('vnumoptions' => $numOptions), 'variationid = "'.$variationId.'"');
			
			logAdd(LOG_SEVERITY_ERROR, 'Error al eliminar el ValorID "'.$this->getAttribute('ValorID').'".<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
		else {
			// Nissim: No borro valores para que siempre esten disponibles para cuando quiera convertir los ValoresID de Intelisis a optionids de aqui
			//$GLOBALS['ISC_CLASS_DB']->DeleteQuery('intelisis_variation_option_values', 'WHERE ValorID = "'.$this->getAttribute('ValorID').'"');
			return true;
		}
	}
}
