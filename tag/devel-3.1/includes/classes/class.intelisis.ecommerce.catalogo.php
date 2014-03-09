<?php

class ISC_INTELISIS_ECOMMERCE_CATALOGO extends ISC_INTELISIS_ECOMMERCE {
	

	public function __constuct(){
		if($this->tablename == ''){
			logAdd(LOG_SEVERITY_ERROR, 'No esta definido el nombre de la tabla');
			return false;
		}
		if(!is_array($this->pk)){
			logAdd(LOG_SEVERITY_ERROR, 'No esta definida la llave primaria');
			return false;
		}
	}
	
	public function BuildWhereClause(){
		$where = '1=1';
		foreach($this->pk as $key => $value){
			$valor = $this->getAttribute($value, '');
			if($valor == '') return false;
			$where .= ' AND '.$key . '= "' . $valor . '"';
		}
	return $where;
	}
	
	public function BuildSelectClause(){
		$select = implode(',',$this->pk);
		return $select;
	}
	
	
	public function create(){
		$where = $this->BuildWhereClause();
		
		if(!$where){
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro valor en la llave primaria');
			return false;
		}
		$select = $this->BuildSelectClause();
		$arreglo = $this->getTableArray();
		
		$regex = '^[a-z|A-z]{3}[[:space:]][0-9]{2}[[:space:]][0-9]{4}[[:space:]][0-9]{1,2}[:][0-9]{1,2}[a-z|A-Z]{2}$^';
		foreach($arreglo as $key => $value) {
			if($value == null) unset($arreglo[$key]);
			if(preg_match($regex, $value)){
				$arreglo[$key] = convertDatetimeFromSQLSRV($value);
			}
		}
		
		if (method_exists($this, "createUpdatePrehook") && $this->createUpdatePrehook() === false) {
			return false;
		}
		if($GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT 1 AS "UNO" FROM [|PREFIX|]'.$this->tablename.' WHERE '.$where, 'UNO'))
		{
			if($GLOBALS['ISC_CLASS_DB']->UpdateQuery($this->tablename, $arreglo, $where))
			{
				if (method_exists($this, "createUpdatePosthook") && $this->createUpdatePosthook() === false) {
					return false;
				}
				logAdd(LOG_SEVERITY_SUCCESS, 'Se edito la tabla '.$this->tablename.' donde '.$where.'. Archivo: '.$this->getXMLfilename());
				return true;
			}
			else
			{
				//printe($GLOBALS['ISC_CLASS_DB']->Error());
				logAdd(LOG_SEVERITY_ERROR, 'Error al intentar editar la tabla '.$this->tablename.' donde '.$where.'. Archivo: '.$this->getXMLfilename());
				return false;
			}
		}
		else
		{
			if($GLOBALS['ISC_CLASS_DB']->InsertQuery($this->tablename, $arreglo))
			{
				if (method_exists($this, "createUpdatePosthook") && $this->createUpdatePosthook() === false) {
					return false;
				}
				logAdd(LOG_SEVERITY_SUCCESS, 'Se creo registro en la tabla '.$this->tablename.' donde '.$where.'. Archivo: '.$this->getXMLfilename());
				return true;
			}
			else
			{
				//printe($GLOBALS['ISC_CLASS_DB']->Error());
				logAdd(LOG_SEVERITY_ERROR, 'Error al intentar crear registro en la tabla '.$this->tablename.' donde '.$where.'. Archivo: '.$this->getXMLfilename());
				return false;
			}
		}	
	}
	
	public function update(){
		$this->create();
		return $this;
	}
	
	public function delete(){
		$where = $this->BuildWhereClause();
		if(!$where){
			logAdd(LOG_SEVERITY_ERROR, 'No se encontro valor en la llave primaria');
			return false;
		}
		
		if (method_exists($this, "deletePrehook") && $this->deletePrehook() === false) {
			return false;
		}
		
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery($this->tablename, 'WHERE '.$where);
		if (method_exists($this, "deletePosthook") && $this->deletePosthook() === false) {
			return false;
		}
		logAdd(LOG_SEVERITY_SUCCESS, 'Se elimino el registro "'.$this->getTableArray().' de la tabla '.$this->tablename.' donde '.$where.'. Archivo: '.$this->getXMLfilename());
		return true;
		}
	}
	