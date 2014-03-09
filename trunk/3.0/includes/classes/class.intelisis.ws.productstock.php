<?php

class ISC_INTELISIS_WS_PRODUCTSTOCK extends ISC_INTELISIS_WS {

	private $productId = 0;
	
	/*
	 * Funcionalidad para consultar el inve3ntario del producto a travez de IntelisisWebService.
	 * Se requiere incluir el Panel "ProductStockDetails.html" en algun lugar de la plantilla product.html 
	 */
	public function __construct($productCode) {
		parent::__construct();
		
		//$this->productId = $productId;
		/*
		$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]products WHERE productid = '".$productId."'");
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		$productDetails = array();
		foreach($row as $key => $value) {
			$productDetails[$key] = $value;
		}
		*/
		$Clave = substr(GetConfig('tablePrefix'), 0, strlen(GetConfig('tablePrefix'))-1);
		
		$this->xml = '<?xml version="1.0" encoding="windows-1252"?>
<Intelisis Sistema="Intelisis" Contenido="Solicitud" Referencia="Intelisis.eCommerce.WebArtExistencia" SubReferencia="WebArtExistencia" Version="1.0">
<Solicitud Empresa="'.$this->empresa.'">
<WebArtExistencia Sucursal="'.GetConfig('syncIWSintelisissucursal').'" eCommerceSucursal="'.$Clave.'" SKU="'.$productCode.'" />
</Solicitud>
</Intelisis>';
	}
	
	public function handleIWSResult() {

		if($this->getOk() == 0)
		{
			libxml_use_internal_errors(true);
			$xml_errors[] = array();
			try {
				$xml_dom = new SimpleXMLElement($this->getResultado());
			}
			catch (Exception $e) {
				foreach(libxml_get_errors() as $error) {
					$xml_errors[] = $error->message;
				}
			}
			
			if(!$xml_dom) {
				logAddError('Se recibio un XML de resultado mal formado de una peticion a IWS de Inventario');
				return true;
			}
			
			$Resultado_xml = $xml_dom->xpath('/Intelisis/Resultado/WebArtExistencia');
			if(isset($Resultado_xml[0]))
			{
				$Resultados = array();
				foreach ($Resultado_xml as $index => $Resultado_linea) {
					foreach($Resultado_linea->attributes() as $name => $value) {
						$Resultados[$index][$name] = (string)$value;
					}
				}

				return $Resultados;
			}
			else
			{
				//logAddError('No se encontro el Resultado de la peticion a IntelisisWebService de Existencia de Producto');
				return array('error' => 'No hay inventario para este producto');
			}
		}
		else
		{
			logAddError('Se encontro un error al pedir la existencia');
			return false;
		}
		;
	}
}