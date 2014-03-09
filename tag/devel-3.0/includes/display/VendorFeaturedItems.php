<?php
/**
 * Vendor's featured items panel.
 *
 * Shows a listing of featured items as defined by the vendor.
 * as well as the 'view all items' block
 */
class ISC_VENDORFEATUREDITEMS_PANEL extends PRODUCTS_PANEL
{
	/**
	 * Set the panel settings.
	 */
	public function SetPanelSettings()
	{
		if(GetConfig('HomeFeaturedProducts') <= 0) {
			$this->DontDisplay = true;
			return false;
		}

		$GLOBALS['SNIPPETS']['VendorFeaturedItems'] = '';

		if(!getProductReviewsEnabled()) {
			$GLOBALS['HideProductRating'] = "display: none";
		}

		$cVendor = GetClass('ISC_VENDORS');
		$vendor = $cVendor->GetVendor();

		$query = $this->getProductQuery(
			'p.prodvendorid='.(int)$vendor['vendorid'],
			'p.prodvendorfeatured DESC, RAND()',
			getConfig('HomeFeaturedProducts')
		);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$GLOBALS['AlternateClass'] = '';
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$this->setProductGlobals($row);
			$GLOBALS['SNIPPETS']['VendorFeaturedItems'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("VendorFeaturedItemsItem");
		}

		$GLOBALS['VendorProductsLink'] = VendorProductsLink($vendor);

		if(!$GLOBALS['SNIPPETS']['VendorFeaturedItems']) {
			$this->DontDisplay = true;
		}
	}
}