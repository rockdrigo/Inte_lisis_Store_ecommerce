<?php

	class ISC_INTELISIS_WS_REPORT extends ISC_INTELISIS_WS{
	
	private $ReportName = '';	
		
	public function __construct($ReportName, $fechaD = NULL, $fechaA = NULL, $ReportId = NULL) {
		parent::__construct();
	
		$Cliente = getClass('ISC_CUSTOMER')->getCustomerId();
		if($Cliente == ''){
			logAddError('No se encontro el ID de WebUsuario del customer id "'.$Cliente.'"');
			$this->setError('Ocurrio un error al cargar su reporte en nuestro sistema. Favor de contactar al administrador del sistema');
		}
		
		$query = 'SELECT IDWebUsuario FROM [|PREFIX|]intelisis_customers WHERE customerid = "'.$Cliente.'"';
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		
		$IDWebusuario = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
		$empresa = $this->empresa;
		$sucursal = GetConfig('syncIWSintelisissucursal');
		if($fechaA == NULL){
			$fechaA = time();
		}
		
		
		$xml = '<?xml version="1.0" encoding="windows-1252"?>';
		$xml .= '<Intelisis Sistema="Intelisis" Contenido="Solicitud" Referencia="Intelisis.eCommerce.Reporte" SubReferencia="'.$ReportName.'" Version="1.0">';
		$xml .= '<Solicitud Empresa="'.$empresa.'" Sucursal="'.$sucursal.'" Estatus="ALTA" UsuarioID="'.$IDWebusuario.'" FechaD="'.$fechaD.'" FechaA="'.$fechaA.'" ID="'.$ReportId.'" />';
		$xml .= '</Intelisis>';
		
		$this->xml = $xml;

		$this->ReportName = $ReportName;
		//DEBUGGING
		//$GLOBALS['ISC_CLASS_DB']->InsertQuery('sincronizacion', array('xml' => $xml, 'estatus' => '3'));
			
	}

	public function handleIWSResult() {
		if($this->getOk() == 0) {
			//DEBUGGING
			//$GLOBALS['ISC_CLASS_DB']->InsertQuery('sincronizacion', array('xml' => $this->getResultado(), 'estatus' => '4'));
			//
			if(!$resultado = $this->getIWSResult()){
				return false;
			}
			
			$tableHead = array();
			switch($this->ReportName){
				case 'AtencionClientesLista':
					$tableHead = array(
					);
				break;
				case 'EstadoCuenta':
					$tableHead = array(
					);
				break;
				case 'ReportePedidos':
					$tableHead = array(
						'ID' => 'ID',
						'Mov' => 'Movimiento',
						'MovID' => 'ID de movimiento',
						'FechaEmision' => 'Fecha de emision',
						'UltimoCambio' => 'Ultima modificacion',
						'Moneda' => 'Moneda',
						'Referencia' => 'Referencia',
						'Estatus' => 'Estatus',
						'EnviarA' => 'Direccion de envio'
					);
				break;
				case 'ComprasPendientes':
					$tableHead = array(
					);
				break;
				case 'ComprasPorArticulo':
					$tableHead = array(
					);
				break;
				case 'DetallePedidoIntelisis':
					$tableHead = array(
					);
				break;
				case 'AtencionClienteDetalle':
					$tableHead = array(
					);
				break;
				default:
					logAddError('No se encontro la referencia del reporte');
					return false;
					break;
			}
			
			
			$GLOBALS['ReportsTableHead'] = '<thead><tr class="First Last Odd Even">';
			foreach($tableHead as $key => $value){
				$GLOBALS['ReportsTableHead'] .= '<th style="width: 20%;">'.$value.'</th>';
			}
			$GLOBALS['ReportsTableHead'] .= '</tr></thead>';
			
			$regex = '^[0-9]{10}$^';
			$table = '';
			foreach($resultado['Reportes'] as $key => $array){
			$table .= '<tr>';
			foreach($array as $column => $value){
				if(preg_match($regex, $value)){
					$value = date('d/m/Y', $value);
				}			
				$table .= '<td>'.$value.'</td>';
			}
			$table .= '</tr>';
			}
			
			$GLOBALS['ReportsList'] = $table;
		
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('report.list');
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
			
			return true;
		}else{
			logAddError('Se encontro un error al procesar el reporte en Intelisis. OK="'.$this->getOK().'" OkRef="'.$this->getOkREf().'"');
			return false;
		}
	}
		
	public function getIWSResult() {
		libxml_use_internal_errors(true);
		$xml_errors[] = array();
		try {
			$xml_dom = new SimpleXMLElement($this->getResultado());
		}
		catch (Exception $e) {
			foreach(libxml_get_errors() as $error) {
				$xml_errors[] = $error->message;
				logAddError(implode('<br/>', $error->message));
			}
		}
		if(!$xml_dom) {
			logAddError('Se recibio un XML de resultado mal formado de una peticion '.get_class($this).'<br/>'.htmlentities($this->getResultado()));
			//$GLOBALS['ISC_CLASS_DB']->InsertQuery('sincronizacion', array('xml' => htmlentities($this->getResultado()), 'estatus' => 'ERROR'));
			return false;
		}
		
		$objeto = $xml_dom->xpath('/Intelisis/Resultado/ReporteLinea');
		$ResultadoXMLReporte = $objeto;
		
		
		$reportResult = array(
			'SubReferencia' => $this->ReportName,
			'Reportes' => array()
		);
		
		foreach($ResultadoXMLReporte as $key => $result){
			$Lineas = array();
			foreach($result->attributes() as $name => $value){
				$Lineas[$name] = (string)$value;
			}
			$reportResult['Reportes'][] = $Lineas;
		}

		return $reportResult;
	}
	
}