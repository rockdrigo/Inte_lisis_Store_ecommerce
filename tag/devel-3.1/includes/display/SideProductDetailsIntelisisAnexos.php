<?php
class ISC_SIDEPRODUCTDETAILSINTELISISANEXOS_PANEL extends PANEL
{
	private $prodcutClass = null;
	
	public function SetPanelSettings()
	{
		
		$this->productClass = GetClass('ISC_PRODUCT');
		$claveProd = $this->productClass->GetProductId();
		armarListaAnexos($claveProd);
		
	}
}