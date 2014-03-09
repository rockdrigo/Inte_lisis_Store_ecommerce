<?php
/**
 * Vendor logo panel.
 *
 * Show the logo for the current vendor, if they have one.
 */
class ISC_VENDORLOGO_PANEL extends PANEL
{
	/**
	 * Set the panel settings.
	 */
	public function SetPanelSettings()
	{
		$cVendor = GetClass('ISC_VENDORS');
		$vendor = $cVendor->GetVendor();
		if(!$vendor['vendorlogo']) {
			$this->DontDisplay = true;
			return;
		}

		$GLOBALS['VendorLogo'] = GetConfig('ShopPath').'/'.GetConfig('ImageDirectory').'/'.$vendor['vendorlogo'];
	}
}