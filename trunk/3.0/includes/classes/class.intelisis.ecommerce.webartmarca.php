<?php

class ISC_INTELISIS_ECOMMERCE_WEBARTMARCA extends ISC_INTELISIS_ECOMMERCE
{
	public function ProcessData() {
		if($this->getXMLdom())
		{
			//printe($this->getAttribute('Estatus').": ".$this->getAttribute('Cliente'));
			switch ($this->getAttribute('Estatus')) {
				case 'ALTA':
					return $this->createBrand();
				break;
				case 'CAMBIO':
					return $this->updateBrand();
				break;
				case 'BAJA':
					return $this->deleteBrand();
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
	
	private function GetBrandsAsArray(&$RefArray)
	{
		/*
			Return a list of brands as an array. This will be used to check
			if a brand already exists. It's more efficient to do one query
			rather than one query per brand check.

			$RefArray - An array passed in by reference only
		*/

		$query = "select brandname from [|PREFIX|]brands";
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

		while($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result))
			$RefArray[] = isc_strtolower($row['brandname']);
	}
	
	private function createBrand() {
		$brand = trim($this->getData('Nombre'));
		if($brand == '')
		{
			logAdd(LOG_SEVERITY_ERROR, 'Se intento crear una Marca con un nombre nulo.<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
		
		$current_brands = array();
		$this->GetBrandsAsArray($current_brands);
		
		if(in_array(isc_strtolower($brand), $current_brands)) {
			return $this->updateBrand();
		}
		else {
			$newBrand = array(
				"brandname" => $brand,
				"brandpagetitle" => "",
				"brandmetakeywords" => "",
				"brandmetadesc" => "",
				"brandsearchkeywords" => ""
			);

			$newBrandId = $GLOBALS['ISC_CLASS_DB']->InsertQuery("brands", $newBrand);

			if (isId($newBrandId)) {

				// Save to our brand search table
				$searchData = array(
					"brandid" => $newBrandId,
					"brandname" => $brand,
					"brandpagetitle" => "",
					"brandsearchkeywords" => ""
				);

				$GLOBALS['ISC_CLASS_DB']->InsertQuery("brand_search", $searchData);

				// Save the words to the brand_words table for search spelling suggestions
				Store_SearchSuggestion::manageSuggestedWordDatabase("brand", $newBrandId, $brand);
			}
		} 

		// Check for an error message from the database
		if($newBrandId == '' || $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg() == "") {
			if($GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_brands', array('IDMarca' => $this->getAttribute('IDMarca'), 'brandid' => $newBrandId)))
			{
				logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis creo la Marca "'.$brand.'"');
				return true;
			}
			else
			{
				logAdd(LOG_SEVERITY_WARNING, 'No se pudo registrar la Marca "'.$brand.'" ID "'.$this->getAttribute('IDMarca').'" brandid "'.$newBrandId.'".<br/>'.$GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}		
		}
		else {
			// Something went wrong
			logAdd(LOG_SEVERITY_ERROR, 'Error al crear la Marca "'.$brand.'" ID "'.$this->getAttribute('IDMarca').'".<br/>Archivo: '.$this->getXMLfilename().'<br/>'.$GLOBALS['ISC_CLASS_DB']->Error());
			return false;
		}
	}
	
	private function getBrandId() {
		
		$query = "SELECT brandid FROM [|PREFIX|]brands WHERE brandname = '".$this->getData('Nombre')."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if($result) {
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			return $row['brandid'];
		}
		
		$query = "SELECT brandid FROM [|PREFIX|]intelisis_brands WHERE IDMarca = '".$this->getAttribute('IDMarca')."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	
		return $row['brandid'] ? $row['brandid'] : false;
	}
	
	private function updateBrand() {
		$brandId = $this->getBrandId();
		if(!$brandId)
		{
			return $this->createBrand();
		}

		$oldBrandName = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT brandname FROM [|PREFIX|]brands WHERE brandid = "'.$brandId.'"', 'brandname');
		if(!$oldBrandName)
		{
			logAdd(LOG_SEVERITY_WARNING, 'Error al buscar el nombre original de la Marca ID "'.$this->getAttribute('IDMarca').'"');
			return false;
		}

		$brandName = $this->getData('Nombre');
		$brandPageTitle = $this->getData('Titulo');
		$brandMetaKeywords = $this->getData('MetaKeyWords');
		$brandMetaDesc = $this->getData('Metadesc');
		$brandSearchKeywords = $this->getData('PalbrasBusquedea'); //ToDo: Checar que no corrijan este nombre
	
		// Make sure the brand doesn't already exist
		$query = sprintf("select count(brandid) as num from [|PREFIX|]brands where brandname='%s' and brandname !='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($brandName), $GLOBALS['ISC_CLASS_DB']->Quote($oldBrandName));
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		$row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);
	
		if($row['num'] != 0) {
			// Duplicate brand name, take them back to the 'Edit' page
			logAdd(LOG_SEVERITY_ERROR, 'El nombre nuevo "'.$brandName.'" de la Marca ID "'.$this->getAttribute('IDMarca').'" brandid "'.$brandId.'" esta duplicado');
			return false;
		}
		else {
			// No duplicates
			$updatedBrand = array(
				"brandname" => $brandName,
				"brandpagetitle" => $brandPageTitle,
				"brandmetakeywords" => $brandMetaKeywords,
				"brandmetadesc" => $brandMetaDesc,
				"brandsearchkeywords" => $brandSearchKeywords
			);
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery("brands", $updatedBrand, "brandid='".$GLOBALS['ISC_CLASS_DB']->Quote($brandId)."'");
			if($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg() == "") {
	
				// Update our brand search table
				$searchData = array(
					"brandid" => $brandId,
					"brandname" => $brandName,
					"brandpagetitle" => $brandPageTitle,
					"brandsearchkeywords" => $brandSearchKeywords
				);
	
				$query = "SELECT brandsearchid
							FROM [|PREFIX|]brand_search
							WHERE brandid=" . (int)$brandId;
	
				$searchId = $GLOBALS["ISC_CLASS_DB"]->FetchOne($query);
	
				if (isId($searchId)) {
					$GLOBALS['ISC_CLASS_DB']->UpdateQuery("brand_search", $searchData, "brandsearchid = " . (int)$searchId);
				} else {
					$GLOBALS['ISC_CLASS_DB']->InsertQuery("brand_search", $searchData);
				}
	
				// Save the words to the brand_words table for search spelling suggestions
				Store_SearchSuggestion::manageSuggestedWordDatabase("brand", $brandId, $brandName);
	
				/* ToDo: Manejar los archivos de imagen de otra manera
				if (array_key_exists('delbrandimagefile', $_POST) && $_POST['delbrandimagefile']) {
					$this->DelBrandImage($brandId);
					$GLOBALS['ISC_CLASS_DB']->UpdateQuery('brands', array('brandimagefile' => ''), "brandid='" . (int)$brandId . "'");
				} else if (array_key_exists('brandimagefile', $_FILES) && ($brandimagefile = $this->SaveBrandImage())) {
					$GLOBALS['ISC_CLASS_DB']->UpdateQuery('brands', array('brandimagefile' => $brandimagefile), "brandid='" . (int)$brandId . "'");
				}
				*/
				logAdd(LOG_SEVERITY_SUCCESS, 'Se edito la Marca ID "'.$this->getAttribute('IDMarca').'" brandid "'.$brandId.'"');
				return true;
			}
			else {
				logAdd(LOG_SEVERITY_ERROR, 'Ocurrio un error al editar la Marca ID "'.$this->getAttribute('IDMarca').'" brandid "'.$brandId.'"<br/>Archivo: '.$this->getXMLfilename().'<br/>'.$GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}
	}
	
	private function deleteBrand() {
		$brandId = $this->getBrandId();
		if(!$brandId)
		{
			logAdd(LOG_SEVERITY_WARNING, 'No se pudo encontrar el brandid de la Marca ID "'.$this->getAttribute('IDMarca').'" para eliminar. Archivo: <br/>'.$this->getXMLfilename());
			return true;
		}
		
		// Delete the brands
		$query = sprintf("delete from [|PREFIX|]brands where brandid in ('%s')", $brandId);
		$GLOBALS["ISC_CLASS_DB"]->Query($query);

		// Delete the brand associations
		$updatedProducts = array(
			"prodbrandid" => 0
		);

		// Delete the search record
		$GLOBALS["ISC_CLASS_DB"]->DeleteQuery("brand_search", "WHERE brandid IN('" . $brandId . "')");

		$GLOBALS['ISC_CLASS_DB']->UpdateQuery("products", $updatedProducts, "prodbrandid IN ('".$brandId."')");
		$err = $GLOBALS["ISC_CLASS_DB"]->Error();
		if ($err != "") {
			logAdd(LOG_SEVERITY_ERROR, 'Error al eliminar la Marca ID "'.$this->getAttribute('IDMarca').'" brandid "'.$brandId.'"<br/>Archivo: '.$this->getXMLfilename().'<br/>'.$err);
			return false;
		} else {
			if($GLOBALS['ISC_CLASS_DB']->DeleteQuery('intelisis_brands', "WHERE brandid IN('" . $brandId . "')"))
			{
				logAdd(LOG_SEVERITY_SUCCESS, 'Se elimino la Marca ID "'.$this->getAttribute('IDMarca').'" brandid "'.$brandId.'"');
			}
			else 
			{
				logAdd(LOG_SEVERITY_WARNING, 'Error al eliminar la Marca ID "'.$this->getAttribute('IDMarca').'" brandid "'.$brandId.'"');
			}
			
			return true;
		}
	}
}
