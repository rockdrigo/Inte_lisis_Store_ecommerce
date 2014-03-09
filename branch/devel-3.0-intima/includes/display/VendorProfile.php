<?php
/**
 * Vendor profile panel.
 *
 * Show the information about a particular vebdor.
 */
class ISC_VENDORPROFILE_PANEL extends PANEL
{
	/**
	 * Set the panel settings.
	 */
	public function SetPanelSettings()
	{
		$cVendor = GetClass('ISC_VENDORS');
		$vendor = $cVendor->GetVendor();
		$GLOBALS['VendorId'] = $vendor['vendorid'];
		$GLOBALS['VendorName'] = isc_html_escape($vendor['vendorname']);
		$GLOBALS['VendorBio'] = $vendor['vendorbio'];
	}
}