<?php

class ISC_INTELISIS_ECOMMERCE_CTE extends ISC_INTELISIS_ECOMMERCE_CATALOGO {

	public $tablename = 'intelisis_Cte';
	public $pk = array(
			'Cliente' => 'Cliente'
			);
			
	public function getTableArray() {
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

}