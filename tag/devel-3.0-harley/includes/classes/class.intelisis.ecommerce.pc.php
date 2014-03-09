<?php

class ISC_INTELISIS_ECOMMERCE_PC extends ISC_INTELISIS_ECOMMERCE_CATALOGO {

	public $tablename = 'intelisis_PC';
	public $pk = array(
			'ID' => 'PCID',
			);
			
	public function getTableArray() { //Llenar dependiendo del XML
		$Arreglo = array(
			'ID' => $this->getData('ID'),
			'Empresa' => $this->getData('Empresa'),
			'Mov' => $this->getData('Mov'),
			'MovID' => $this->getData('MovID'),
			/*'FechaEmision' => $this->getData('FechaEmision'),
			'UltimoCambio' => $this->getData('UltimoCambio'),*/
			'Proyecto' => $this->getData('Proyecto'),
			'UEN' => $this->getData('UEN'),
			'Concepto' => $this->getData('Concepto'),
			'Moneda' => $this->getData('Moneda'),
			'TipoCambio' => $this->getData('TipoCambio'),
			'Usuario' => $this->getData('Usuario'),
			'Autorizacion' => $this->getData('Autorizacion'),
			'DocFuente' => $this->getData('DocFuente'),
			'Observaciones' => $this->getData('Observaciones'),
			'Referencia' => $this->getData('Referencia'),
			'Estatus' => $this->getData('Estatus'),
			'Situacion' => $this->getData('Situacion'),
			'SituacionFecha' => $this->getData('SituacionFecha'),
			'SituacionUsuario' => $this->getData('SituacionUsuario'),
			'SituacionNota' => $this->getData('SituacionNota'),
			'OrigenTipo' => $this->getData('OrigenTipo'),
			'Origen' => $this->getData('Origen'),
			'OrigenID' => $this->getData('OrigenID'),
			'Ejercicio' => $this->getData('Ejercicio'),
			'Periodo' => $this->getData('Periodo'),
			/*'FechaRegistro' => $this->getData('FechaRegistro'),
			'FechaConclusion' => $this->getData('FechaConclusion'),
			'FechaCancelacion' => $this->getData('FechaCancelacion'),*/
			'Poliza' => $this->getData('Poliza'),
			'PolizaID' => $this->getData('PolizaID'),
			'GenerarPoliza' => $this->getData('GenerarPoliza'),
			'ContID' => $this->getData('ContID'),
			'Sucursal' => $this->getData('Sucursal'),
			'ListaModificar' => $this->getData('ListaModificar'),
			'Proveedor' => $this->getData('Proveedor'),
			'FechaInicio' => $this->getData('FechaInicio'),
			'FechaTermino' => $this->getData('FechaTermino'),
			'Recalcular' => $this->getData('Recalcular'),
			'Parcial' => $this->getData('Parcial'),
			'Metodo' => $this->getData('Metodo'),
			'Monto' => $this->getData('Monto'),
			'Logico1' => $this->getData('Logico1'),
			'Logico2' => $this->getData('Logico2'),
			'Logico3' => $this->getData('Logico3'),
			'Logico4' => $this->getData('Logico4'),
			'Logico5' => $this->getData('Logico5'),
			'Logico6' => $this->getData('Logico6'),
			'Logico7' => $this->getData('Logico7'),
			'Logico8' => $this->getData('Logico8'),
			'Logico9' => $this->getData('Logico9'),
			'SucursalOrigen' => $this->getData('SucursalOrigen'),
			'SucursalDestino' => $this->getData('SucursalDestino'),
		);		
		return $Arreglo;
	}

}