<?php
/**
 * Vendor photo panel.
 *
 * Show the photo for the current vendor, if they have one.
 */
class ISC_VENDORPHOTO_PANEL extends PANEL
{
	/**
	 * Set the panel settings.
	 */
	public function SetPanelSettings()
	{
		$cVendor = GetClass('ISC_VENDORS');
		$vendor = $cVendor->GetVendor();
		if(!$vendor['vendorphoto']) {
			$this->DontDisplay = true;
			return;
		}

		$GLOBALS['VendorPhoto'] = GetConfig('ShopPath').'/'.GetConfig('ImageDirectory').'/'.$vendor['vendorphoto'];
	}
}