<?php

class ISC_INTELISIS_ECOMMERCE_CTE extends ISC_INTELISIS_ECOMMERCE_CATALOGO {

	public $tablename = 'intelisis_Cte';
	public $pk = array(
			'Cliente' => 'Cliente'
			);
			
	public function getTableArray() {
		$dataArray = $this->getDataElement();
		if(empty($dataArray)) {
			return false;
		}
		
		$Arreglo = array(
			'Cliente' => $this->getData('Cliente'),
			'Nombre' => $this->getData('Nombre'),
			'Grupo' => $this->getData('Grupo'),
			'Categoria' => $this->getData('Categoria'),
			'Familia' => $this->getData('Familia'),
			'Zona' => $this->getData('Zona'),
		);		
		return $Arreglo;
	}
	
	public function createUpdatePosthook(){
		//Al actualizar o crea un Cte, hacer update a su RFC en todos los usuarios que tiene asignados, esto por si tiene RFC generico o se modifica desde Intelisis
		$result_customers = $GLOBALS['ISC_CLASS_DB']->Query('SELECT c.custformsessionid
			FROM [|PREFIX|]customers c
			JOIN [|PREFIX|]intelisis_customers ic ON (c.customerid=ic.customerid)
			WHERE ic.Cliente = "'.$this->getData('Cliente').'"');
		
		$RFCfieldId = getCustomFieldId(FORMFIELDS_FORM_ACCOUNT, 'RFC');
		
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result_customers)){
			$RFC = $GLOBALS['ISC_CLASS_FORM']->getFormField(FORMFIELDS_FORM_ACCOUNT, $RFCfieldId, '', false, $row['custformsessionid']);
			
			if(!$RFC) return true;
			
			$newFormFieldSession = array(
				$RFC->getFieldIdNo() => $this->getData('RFC'),
			);

			if($GLOBALS['ISC_CLASS_FORM']->saveFormSessionManual($newFormFieldSession, $row['custformsessionid'])){
				continue;
			}
			else {
				return false;
			}
		}
		return true;
	}

}