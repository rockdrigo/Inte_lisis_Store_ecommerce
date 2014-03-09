<?php

class ISC_INTELISIS_WS
{
	public $xml = '';
	private $url = '';
	public $empresa = 'DEMO';
	public $requestInnerXML = '';
	private $requestResult = '';
	private $error = '';
	public $action = '';
	
	private $Resultado = NULL;
	private $Ok = NULL;
	private $OkRef = NULL;
	private $ID = NULL;

	public $useDropbox = false;
	
	public function __construct() {
		$this->url = GetConfig('syncIWSurl');
		$emp = GetConfig('syncIWSintelisisempresa');
		$this->empresa = ($emp == '') ? 'DEMO' : $emp; 
	}
	
	public function prepareRequest() {
		$xml = $this->xml;
		if($xml == ''){
			logAddNotice('No hay XML. '.get_class($this));
			return false;
		}
		
		//ToDo: Checar que no quite el encabezado de <?xml con el encoding
		libxml_use_internal_errors(true);
		$xml_errors[] = array();
		try {
			$xml_dom = new SimpleXMLElement($xml);
		}
		catch (Exception $e) {
			foreach(libxml_get_errors() as $error) {
				$xml_errors[] = $error->message;
			}
		}
		/*
		if(!empty($xml_errors))
		{
			if($xml_errors[0] = '') continue;
			logAddError('Error al procesar XML.<br/><pre>'.$xml.'</pre><br/>'.implode('<br/>', $xml_errors));
			$this->setError('Ocurrio un error al procesar el XML de su pedido. Favor de contactar al administrador del sistema con el ID de pedido "'.$this->customerId.'" [1]');
			return false;
		}
		*/
				
		if(!isset($xml_dom) || !$xml_dom) {
			logAddError('El XML de un objeto '.get_class($this).' esta mal formado');
			return false;
		}

		$this->requestInnerXML = $xml_dom;
		$IWSresult = false;
		if($this->url != '') {
			$IWSresult = $this->doIWSRequest();
		}

		if(!$IWSresult) {
			if(GetConfig('syncDropboxActive')) {
				if($this->useDropbox) return $this->doDropboxRequest();
				else return false;
			}
			else {
				logAddError('Se hizo una solicitud a IWS de clase "'.get_class($this).'" que resulto en error y la transmicion asincrona (syncDropboxActive) no esta activa.');
				return false;
			}
		}
		else {
			return $IWSresult;
		}
	}
	
	public function doDropboxRequest() {
		$Clave = substr($GLOBALS['ISC_CLASS_DB']->TablePrefix, 0, strlen($GLOBALS['ISC_CLASS_DB']->TablePrefix)-1);
		$dropbox_dir = GetConfig('syncDropboxDir');
		$syncFileNameOut = GetConfig('syncFileNameOut');

		$sincroID = $GLOBALS['ISC_CLASS_DB']->InsertQuery('sincronizacion', array('xml' => htmlentities($this->requestInnerXML->asXML())));
		if(!$sincroID) {
			logAddNotice('No se pudo registrar la peticion de tipo '.get_class($this).' en la tabla de sincronizacion para obtener su ID');
			return false;
		}
		
		$filename =  GetConfig('syncDropboxOffline') ? $dropbox_dir.$Clave.'/Offline/'.sprintf($syncFileNameOut, $sincroID) : $dropbox_dir.$Clave.'/'.sprintf($syncFileNameOut, $sincroID);
		
		if($filename != '' && file_put_contents($filename, utf8_decode($this->requestInnerXML->asXML()))) {
			logAddSuccess('Se transmitio la peticion ID "'.$sincroID.'" a Dropbox');
			return $this->handleDropboxResult();
		}
		else {
			logAddError('Ocurrio un error al intentar transmitir la peticion ID "'.$sincroID.'" a Dropbox. Archivo "'.$filename.'"');
			return false;
		}
	}
	
	public function doIWSRequest() {
		$request = '
		<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.intelisis.com/">
		   <soapenv:Header/>
		   <soapenv:Body>
		      <ws:solicitar>
		         <!--Optional:-->
		         <mssqlServidor>'.GetConfig('syncIWShost').'</mssqlServidor>
		         <!--Optional:-->
		         <mssqlPuerto>'.GetConfig('syncIWSport').'</mssqlPuerto>
		         <!--Optional:-->
		         <mssqlBaseDatos>'.GetConfig('syncIWSdbname').'</mssqlBaseDatos>
		         <!--Optional:-->
		         <mssqlUsuario>'.GetConfig('syncIWSdbuser').'</mssqlUsuario>
		         <!--Optional:-->
		         <mssqlContrasena>'.GetConfig('syncIWSdbpass').'</mssqlContrasena>
		         <!--Optional:-->
		         <Solicitud><![CDATA['.$this->requestInnerXML->asXML().']]></Solicitud>
		         <!--Optional:-->
		         <intelisisUsuario>'.GetConfig('syncIWSintelisisuser').'</intelisisUsuario>
		         <!--Optional:-->
		         <intelisisContrasena>'.GetConfig('syncIWSintelisispass').'</intelisisContrasena>
		         <procesar>1</procesar>
		         <eliminarProcesado>0</eliminarProcesado>
		      </ws:solicitar>
		   </soapenv:Body>
		</soapenv:Envelope>
		';
		
		try {
			$soap = new SoapClient($this->url."?wsdl", array());
		}
		catch (Exception $e)
		{
			logAddError('Error al contactar al servidor de WebService');
			$this->error = 'No se pudo contactar al WebService';
			return false;
		}
		
		if(!$soap) {
			return false;
		}
		
		try {
			$result = $soap->__doRequest($request, $this->url, '', 1.1);
		}
		catch (SoapFault $sf) {
			logAddError('SoapFault no se pudo conectar con el WebService');
			$this->error = 'Error al transmitir[1]';
			return false;
		}
		catch (Exception $e) {
			logAddError('Excepcion no se pudo conectar con el WebService');
			$this->error = 'Error al transmitir[2]';
			return false;
		} 
		
		if(!$result) {
			logAddError('No hay Resultado');
			return false;
		}
		
		$this->requestResult = $result;
		
		$this->Resultado = str_replace(array('&lt;', '&gt;', '&quot;'), array('<', '>', "'"), getXMLnode($this->requestResult, 'Intelisis'));
		$this->Ok = getXMLnode($this->requestResult, 'Ok', 1);
		$this->OkRef = getXMLnode($this->requestResult, 'OkRef', 1);
		$this->ID = getXMLnode($this->requestResult, 'ID', 1);
		
		return $this->handleIWSResult();
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
		
		$Resultado_xml = $xml_dom->xpath('/Intelisis/Resultado');
		if(isset($Resultado_xml[0]))
		{
			$Resultado_attribs = array();
			foreach($Resultado_xml[0]->attributes() as $name => $value) {
				$Resultado_attribs[$name] = (string)$value;
			}
			
			return $Resultado_attribs;			
		}
		else
		{
			logAddError('No se encontro el Resultado de la peticion a IntelisisWebService '.get_class($this).' IS-ID="'.$this->getID());
			return true;
		}
	}
	
	public function handleIWSResult() {
		logAddWarning('Se hizo una peticion a IWS "'.get_class($this).'" sin haber sobreescrito el metodo handleIWSResult()');
		return true;
	}
	
	public function handleDropboxResult() {
		logAddWarning('Se hizo una peticion a IWS '.get_class($this).' sin haber sobreescrito el metodo handleDropboxResult()');
		return true;
	}
	
	public function setError($msg) {
		$this->error = $msg;
	}
	
	public function getError() {
		return $this->error;
	}
	
	public function getResultado(){
		return $this->Resultado;
	}

	public function getOk(){
			return $this->Ok;
	}
		
	public function getOkRef(){
			return $this->OkRef;
	}
		
	public function getID(){
			return $this->ID;
	}
}