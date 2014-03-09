<?php

class ISC_ECOMMERCE_INTELISIS
{
	private $xml_filename = '';
	private $xml_dom = '';
	
	private $attribs = array();
	private $headers = array();
	private $numResults = 0;
	
	public function __construct()
	{
		$this->xml_filename = '';
		$this->xml_dom = '';
		
		$attribs = array();
		$numResults = 0;
	}
	
	public function setXMLfilename($xml_filename)
	{
		$this->xml_filename = $xml_filename;
	}
	
	public function setXMLdom($xml_dom)
	{
		$this->xml_dom = $xml_dom;
		$this->getHeadersFromXML();
		$this->getAttribsFromXML();
	}
	
	public function getXMLdom() {
		return $this->xml_dom;
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
	
	public function getAttribute($name, $default = '')
	{
		if(isset($this->attribs[$name]))
		{
			return trim($this->attribs[$name]);
		}
		else {
			return $default;
		}
	}
	
	public function getHeadersFromXML() {
		$this->headers = array();
		$xpathToHeaders = '/Intelisis';
		$results = $this->xml_dom->xpath($xpathToHeaders);
		
		//Solo puede haber un solo elemento con el XPath de arriba
		if(count($results)==1){
			foreach($results[0]->attributes() as $name => $value) {
				$this->headers[$name] = (string)$value;
			}
		}
		else {
			logAdd(LOG_SEVERITY_WARNING, 'Se encontraron mas de un nodo <Intelisis> en el XML '.$this->xml_filename);
		}
	}
	
	public function getHeader($name)
	{
		if(isset($this->headers[$name]))
		{
			return $this->headers[$name];
		}
		else {
			return '';
		}
	}
	
	public function ProcessData() {
		if($this->getXMLdom())
		{
			return $this->processResult();
		}
		else
		{
			logAdd(LOG_SEVERITY_WARNING, 'Se trato de procesar un objeto '.get_class($this).' sin XML DOM especificado', 'Archivo: "'.$this->getXMLfilename().'"');
		}
	}
	
	public function processResult() {
		logAdd(LOG_SEVERITY_ERROR, 'Se trato de procesar un objeto '.get_class($this).' sin overridear el metodo processResult()');
		return false;
	}
}