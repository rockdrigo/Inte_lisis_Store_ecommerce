<?php
/**
 * Show a listing of all of the vendors configured on the store.
 */
class ISC_VENDORLIST_PANEL extends PANEL
{
	/**
	 * Set the panel settings.
	 */
	public function SetPanelSettings()
	{
		$GLOBALS['SNIPPETS']['VendorList'] = '';
		$query = "
			SELECT *
			FROM [|PREFIX|]vendors
			ORDER BY vendorname ASC
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($vendor = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$GLOBALS['VendorId'] = $vendor['vendorid'];
			$GLOBALS['VendorName'] = isc_html_escape($vendor['vendorname']);
			$GLOBALS['VendorLink'] = VendorLink($vendor);
			$GLOBALS['VendorProductsLink'] = VendorProductsLink($vendor);
			$GLOBALS['SNIPPETS']['VendorList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('VendorListItem');
		}

		if(!$GLOBALS['SNIPPETS']['VendorList']) {
			$this->DontDisplay = true;
			return false;
		}
	}
}