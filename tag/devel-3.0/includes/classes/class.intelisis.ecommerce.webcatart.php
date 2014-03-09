<?php

include_once(ISC_BASE_PATH.'/lib/api/category.api.php');

class ISC_INTELISIS_ECOMMERCE_WEBCATART extends ISC_INTELISIS_ECOMMERCE
{
	private $categoryAPI;
	
	public function __construct()
	{
		$this->categoryAPI = new API_CATEGORY();
	}
	
	public function ProcessData() {
		if($this->getXMLdom())
		{
			//printe($this->getAttribute('Estatus').": ".$this->getAttribute('Cliente'));
			switch ($this->getAttribute('Estatus')) {
				case 'ALTA':
					return $this->createCategory();
				break;
				case 'CAMBIO':
					return $this->updateCategory();
				break;
				case 'BAJA':
					return $this->deleteCategory();
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
	
	private function getCategoryId($id=0) {
		if($id==0) $id = $this->getAttribute('IDCategoria');

		$query = "SELECT categoryid FROM [|PREFIX|]intelisis_categories WHERE IDCategoria = '".$id."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		return $row['categoryid'] ? $row['categoryid'] : false;
	}

	private function createCategory() {
		
		$catID = $this->getCategoryId();
		if($catID){
			return $this->updateCategory();
		}
		/*
		$query_name = 'SELECT categoryid FROM [|PREFIX|]categories WHERE catname = "'.$this->getData('Nombre').'"';
		if($catID = $GLOBALS['ISC_CLASS_DB']->FetchOne($query_name, 'categoryid')){
			if($IDCat = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT IDCategoria FROM [|PREFIX|]intelisis_categories WHERE categoryid = "'.$catID.'"', 'IDCategoria')){
				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_categories', array('IDCategoria' => $this->getAttribute('IDCategoria')), 'categoryid = "'.$catID.'"');
			}
			else {
				$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_categories', array('IDCategoria' => $this->getAttribute('IDCategoria'), 'categoryid' => $catID));
			}
			return $this->updateCategory();
		}
*/
		$this->categoryAPI->error = array();

		if($this->getData('Rama') != '' && !$this->getCategoryId($this->getData('Rama')))
		{
			logAdd(LOG_SEVERITY_WARNING, 'Se definio una Rama "'.$this->getData('Rama').'" para la categoria ID "'.$this->getAttribute('IDCategoria').'" la cual no ha sido creada');
			return false;
		}

		$_POST['catname'] =  $this->getData('Nombre');
		$_POST['catdesc'] = $this->getData('Descripcion');
		$_POST['catparentid'] = $this->getData('Rama') != '' ? $this->getCategoryId($this->getData('Rama')) : 0;
		$_POST['catviews'] = 0;
		$_POST['catsort'] = $this->getData('Orden');
		$_POST['catpagetitle'] = $this->getData('Titulo');
		$_POST['catmetakeywords'] = $this->getData('MetaKeyWords');
		$_POST['catmetadesc'] = $this->getData('Metadesc');
		$_POST['catsearchkeywords'] = $this->getData('PalabrasBusqueda');
		$_POST['catlayoutfile'] = $this->getData('LayOut') != '' ? $this->getData('LayOut') : 'category.html';
		$_POST['catparentlist'] = '';
		$_POST['catimagefile'] = $this->getData('ArchivoImagen');
		$_POST['catvisible'] = $this->getData('Visible');
		$_POST['cataltcategoriescache'] = '';
		$_POST['cat_enable_optimizer'] = $this->getData('HabilitarOptimizacion');

		$catID = $this->categoryAPI->create(true);
		if($catID) {
			$GLOBALS['NewCategoryId'] = $catID;
		}
		
		if (empty($this->categoryAPI->error)) {
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('categories', array('catvisible' => $this->getData('Visible')), 'categoryid = "'.$catID.'"');
			$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateRootCategories();
	
			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($_POST['catname']);
	
			if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_categories', array('IDCategoria' => $this->getAttribute('IDCategoria'), 'categoryid' => $catID)))
			{
				logAdd(LOG_SEVERITY_ERROR, 'No se pudo relacionar la Categoria Web ID "'.$this->getAttribute('IDCategoria').'" con categoryid "'.$catID.'"');
				return false;
			}
			else {
				logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis creo la categoria "'.$this->getData('Nombre').'" ID "'.$this->getAttribute('IDCategoria').'"');
				return true;
			}
		} else {
			logAdd(LOG_SEVERITY_ERROR, 'Error al crear la categoria "'.$this->getData('Nombre').'" ID "'.$this->getAttribute('IDCategoria').'".'.$this->categoryAPI->error.'<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
	}
	
	private function updateCategory() {
		$this->categoryAPI->error = array();
		$categoryId = $this->getCategoryId();
		if(!$categoryId)
		{
			return $this->createCategory();
		}

		if($this->getData('Rama') != '' && !$this->getCategoryId($this->getData('Rama')))
		{
			logAdd(LOG_SEVERITY_WARNING, 'Se definio una Rama "'.$this->getData('Rama').'" para la categoria ID "'.$this->getAttribute('IDCategoria').'" la cual no ha sido creada');
			return false;
		}

		$_POST['categoryId'] = $categoryId;
		$_POST['catname'] =  $this->getData('Nombre');
		$_POST['catdesc'] = $this->getData('Descripcion');
		$_POST['catparentid'] = $this->getData('Rama') != '' ? $this->getCategoryId($this->getData('Rama')) : 0;
		$_POST['catviews'] = 0;
		$_POST['catsort'] = $this->getData('Orden');
		$_POST['catpagetitle'] = $this->getData('Titulo');
		$_POST['catmetakeywords'] = $this->getData('MetaKeyWords');
		$_POST['catmetadesc'] = $this->getData('Metadesc');
		$_POST['catsearchkeywords'] = $this->getData('PalabrasBusqueda');
		$_POST['catlayoutfile'] = $this->getData('LayOut');
		$_POST['catparentlist'] = '';
		$_POST['catimagefile'] = $this->getData('ArchivoImagen');
		$_POST['catvisible'] = $this->getData('Visible');
		$_POST['cataltcategoriescache'] = '';
		$_POST['cat_enable_optimizer'] = $this->getData('HabilitarOptimizacion');

		if (isId($categoryId)) {
			$this->categoryAPI->load($categoryId);
		}

		$catID = $this->categoryAPI->save();

		$GLOBALS['ISC_CLASS_DB']->UpdateQuery('categories', array('catvisible' => $this->getData('Visible')), 'categoryid = "'.$categoryId.'"');
		
		if (empty($this->categoryAPI->error)) {
			$nested = new ISC_NESTEDSET_CATEGORIES();
			$nested->rebuildTree();

			// Update the data store
			$GLOBALS['ISC_CLASS_DATA_STORE']->UpdateRootCategories();
	
			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction($_POST['catname']);
	
			logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis edito la categoria "'.$this->getData('Nombre').'" ID "'.$this->getAttribute('IDCategoria').'"');
			return true;
		} else {
			logAdd(LOG_SEVERITY_ERROR, 'Error al editar la categoria "'.$this->getData('Nombre').'" ID "'.$this->getAttribute('IDCategoria').'". '.$this->categoryAPI->error.'<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
	}

	private function deleteCategory() {
		$categoryId = array($this->getCategoryId() => $this->getCategoryId());
		if(!$categoryId)
		{
			logAdd(LOG_SEVERITY_WARNING, 'No se encontro la category id de la Categoria ID "'.$this->getAttribute('IDCategoria').'"');
			return true;
		}
		/*
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery('categories', array('catvisible' => 0), 'categoryid = "'.$this->getCategoryId().'"');
		logAddNotice('Se escondio la categoria id "'.$this->getCategoryId().' en vez de borrarla');
		return true;
		*/
		if ($this->categoryAPI->multiDelete($categoryId)) {
			$catIds = array_keys($categoryId);
			$optimizer = getClass('ISC_ADMIN_OPTIMIZER');
			$optimizer->deletePerItemOptimizerConfig('category', $catIds);

			// Log this action
			$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(count($catIds));
			
			if(!$GLOBALS['ISC_CLASS_DB']->DeleteQuery('intelisis_categories', 'WHERE IDCategoria ="'. $this->getAttribute('IDCategoria').'"'))
			{
				logAdd(LOG_SEVERITY_ERROR, 'No se pudo eliminar la Categoria Web ID "'.$this->getAttribute('IDCategoria').'" con categoryid "'.$this->getCategoryId().'"');
				return false;
			}
			else {
				logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis elimino la categoria ID "'.$this->getAttribute('IDCategoria').'" ID "'.$this->getCategoryId());
				return true;
			}

		} else {
			logAdd(LOG_SEVERITY_ERROR, 'Error al eliminar la categoria ID "'.$this->getAttribute('IDCategoria').'" categoryid "'.$this->getCategoryId().'".<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
	}
}
