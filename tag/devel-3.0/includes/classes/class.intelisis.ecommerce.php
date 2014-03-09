<?php

class ISC_INTELISIS_ECOMMERCE
{
	private $xml_filename = '';
	private $xml_dom = '';
	
	private $attribs = array();
	private $numResults = 0;
	private $data = array();
	
	public function __construct()
	{
		$this->xml_filename = '';
		$this->xml_dom = '';
		
		$attribs = array();
		$numResults = 0;
		$data = array();
	}
	
	public function setXMLfilename($xml_filename)
	{
		$this->xml_filename = $xml_filename;
	}
	
	public function setXMLdom($xml_dom)
	{
		$this->xml_dom = $xml_dom;
		$this->getAttribsFromXML();
		$this->getDataFromXML();
	}
	
	public function getXMLfilename()
	{
		return $this->xml_filename;
	}
	
	public function getXMLdom() {
		return $this->xml_dom;
	}
	
	public function setXMLfromFile($filename)
	{
		if($xml_contents = file_get_contents($filename)){
			$this->setXMLfilename($filename);
			$this->setXMLdom(new SimpleXMLElement($xml_contents));
		}
		else {
			return false;
		}
	}
	
	public function getAttribsFromXML() {
		$this->attribs = array();
		$xpathToAttrribs = '/Intelisis/Resultado';
		$results = $this->xml_dom->xpath($xpathToAttrribs);
		
		//Solo puede haber un solo elemento con el XPath de arriba
		if(count($results)==1){
			foreach($results[0]->attributes() as $name => $value) {
				$this->attribs[$name] = (string)$value;
			}
		}
		else {
			logAdd(LOG_SEVERITY_WARNING, 'Se encontraron mas de un nodo <Resultado> en el XML '.$this->xml_filename);
		}
	}
	
	public function getAttribute($name)
	{
		if(isset($this->attribs[$name]))
		{
			return $this->attribs[$name];
		}
		else {
			return '';
		}
	}
	
	private function getDataFromXML()
	{
		$this->data = array();
		$xpathToData = '/Intelisis/Resultado';
		$results = $this->xml_dom->xpath($xpathToData);
		
		//Solo debe de haber un elemento <Resultado>
		if(count($results)==1)
		{
			$results = $results[0]->children();
		}

		//Obtenemos los hijos de <Resultado>
		if(count($results)==1)
		{
			//$data = $results[0]->children();

			foreach($results[0]->attributes() as $name => $value)
			{
				$this->data[$name] = (string)$value;
			}
			
			//BUG 10667
			$this->data[0] = $this->data;
		}
		else
		{
			// REQ10046 - Se agrega esto para manejar multiples elementos <Resultado>, como es el caso de Intelisis.eCommerce.ExistenciaSucursal
			$i = 0;
			foreach ($results as $key => $result) {
				$this->data[$i] = array();
				foreach($result->attributes() as $name => $value)
				{
					//printe($i."-".$name."-".(string)$value);
					$this->data[$i][$name] = (string)$value;
				}
				$i++;
			}
		}
	}
	
	public function getDataElement() {
		return $this->data;
	}
	
	public function getData($name, $default = '')
	{
		if(isset($this->data[$name]) && $this->data[$name] != '')
		{
			return trim($this->data[$name]);
		}
		else {
			return $default;
		}
	}
	
	public function ProcessData() {
		if($this->getXMLdom())
		{
			//printe($this->getAttribute('Estatus').": ".$this->getAttribute('Cliente'));
			switch ($this->getAttribute('Estatus')) {
				case 'ALTA':
					return $this->create();
				break;
				case 'CAMBIO':
					return $this->update();
				break;
				case 'BAJA':
					return $this->delete();
				break;
				default:
					logAdd(LOG_SEVERITY_ERROR, 'Estatus de archivo no valido. '.get_class($this).'. Estatus: "'.$this->getAttribute('Estatus').'". Archivo: "'.$this->getXMLfilename().'"');
					return false;
				break;
			}
		}
		else
		{
			logAdd(LOG_SEVERITY_WARNING, 'Se trato de procesar un objeto '.get_class($this).' sin XML DOM especificado', 'Archivo: "'.$this->getXMLfilename().'"');
		}
	}
}