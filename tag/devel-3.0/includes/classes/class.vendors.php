<?php
/**
 * Front end vendor interface.
 *
 * Show a list of vendors, the profile of a vendor,
 * a list of products for a vendor or a list of vendor
 * created pages.
 *
 * @todo Vendor pages
 */
class ISC_VENDORS
{
	/**
	 * @var string The type of page we're displaying.
	 */
	private $displaying = '';

	/**
	 * @var mixed False if not showing content not belonging to a vendor, otherwise an array of data about the vendor.
	 */
	private $vendor = false;

	/**
	 * Handle the incoming page request.
	 */
	public function HandlePage()
	{
		if(!gzte11(ISC_HUGEPRINT)) {
			exit;
		}

		$this->SetVendorData();

		if($this->displaying == 'products') {
			$this->ShowVendorProducts();
		}
		else if($this->displaying == 'page') {
			$this->ShowVendorPage();
		}
		else if($this->displaying == 'profile') {
			$this->ShowVendorProfile();
		}
		else {
			$this->ShowVendors();
		}
	}

	/**
	 * Return information about the loaded vendor.
	 *
	 * @return array An array of information about the loaded vendor.
	 */
	public function GetVendor()
	{
		return $this->vendor;
	}

	/**
	 * Show the page containing products belonging to the current vendor.
	 */
	public function ShowVendorProducts()
	{
		$GLOBALS['BreadCrumbs'] = array(
			array(
				'name' => GetLang('Vendors'),
				'link' => VendorLink()
			),
			array(
				'name' => $this->vendor['vendorname'],
				'link' => VendorLink($this->vendor)
			),
			array(
				'name' => GetLang('Products')
			)
		);
		$title = sprintf(GetLang('ProductsFromVendorX'), isc_html_escape($this->vendor['vendorname']));
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName').' - '.$title);
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('vendor_products');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}

	/**
	 * Show the page containing a web page set up by a particular vendor.
	 */
	public function ShowVendorPage()
	{
		if(isset($_REQUEST['pageid'])) {
			$pageWhere = " pageid='".(int)$_REQUEST['pageid']."'";
		}
		else {
			$page = preg_replace('#\.html$#i', '', $GLOBALS['PathInfo'][2]);
			$page = MakeURLNormal($page);
			$pageWhere = " pagetitle='".$GLOBALS['ISC_CLASS_DB']->Quote($page)."'";
		}

		$query = "
			SELECT *
			FROM [|PREFIX|]pages
			WHERE ".$pageWhere." AND pagevendorid='".(int)$this->vendor['vendorid']."' AND pagestatus='1'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$page = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		if(!isset($page['pageid'])) {
			$GLOBALS['ISC_CLASS_404'] = GetClass('ISC_404');
			$GLOBALS['ISC_CLASS_404']->HandlePage();
			exit;
		}

		// Otherwise show the page
		$GLOBALS['ISC_CLASS_PAGE'] = new ISC_PAGE($page['pageid'], false, $page);
		$GLOBALS['ISC_CLASS_PAGE']->HandlePage();
		exit;
	}

	/**
	 * Show the profile page belonging to the current vendor.
	 */
	public function ShowVendorProfile()
	{
		$GLOBALS['BreadCrumbs'] = array(
			array(
				'name' => GetLang('Vendors'),
				'link' => VendorLink()
			),
			array(
				'name' => $this->vendor['vendorname'],
			)
		);

		$title = isc_html_escape($this->vendor['vendorname']);
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName').' - '.$title);
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('vendor_profile');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}

	/**
	 * Show a listing of all of the vendors configured on the store.
	 */
	public function ShowVendors()
	{
		$GLOBALS['BreadCrumbs'] = array(
			array(
				'name' => GetLang('Vendors')
			)
		);
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName').' - '.GetLang('Vendors'));
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('vendors');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}

	/**
	 * Load a vendor based on the passed vendor ID.
	 *
	 * @param int The vendor ID.
	 * @return array An array of information about the vendor.
	 */
	private function LoadVendorById($id)
	{
		$query = "
			SELECT *
			FROM [|PREFIX|]vendors
			WHERE vendorid='".(int)$id."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		return $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	}

	/**
	 * Load a vendor based on the passed vendor friendly name.
	 *
	 * @param string The vendor friendly name.
	 * @return array An array of information about the vendor.
	 */
	private function LoadVendorByFriendlyName($friendlyName)
	{
		$query = "
			SELECT *
			FROM [|PREFIX|]vendors
			WHERE vendorfriendlyname='".$GLOBALS['ISC_CLASS_DB']->Quote($friendlyName)."'
			LIMIT 1
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		return $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	}

	/**
	 * Set the incoming information about the page we're viewing from the request.
	 */
	private function SetVendorData()
	{
		if(isset($_REQUEST['vendorid'])) {
			$this->vendor = $this->LoadVendorById($_REQUEST['vendorid']);
		}
		else if(isset($GLOBALS['PathInfo'][1]) && $GLOBALS['PathInfo'][1] != '') {
			$this->vendor = $this->LoadVendorByFriendlyName($GLOBALS['PathInfo'][1]);
		}

		// Viewing the products that belong to a specific vendor
		if((isset($GLOBALS['PathInfo'][2]) && $GLOBALS['PathInfo'][2] == 'products') || (isset($_REQUEST['action']) && $_REQUEST['action'] == 'products')) {
			if(!is_array($this->vendor)) {
				$GLOBALS['ISC_CLASS_404'] = GetClass('ISC_404');
				$GLOBALS['ISC_CLASS_404']->HandlePage();
			}

			$this->displaying = 'products';
		}

		// Viewing a specific page
		else if((isset($GLOBALS['PathInfo'][2]) && $GLOBALS['PathInfo'][2] != '') || isset($_REQUEST['pageid'])) {
			//
			if(!is_array($this->vendor)) {
				$GLOBALS['ISC_CLASS_404'] = GetClass('ISC_404');
				$GLOBALS['ISC_CLASS_404']->HandlePage();
			}

			$this->displaying = 'page';
		}

		// Viewing vendor profile
		else if(isset($GLOBALS['PathInfo'][1]) || isset($_REQUEST['vendorid'])) {
			if(!is_array($this->vendor)) {
				$GLOBALS['ISC_CLASS_404'] = GetClass('ISC_404');
				$GLOBALS['ISC_CLASS_404']->HandlePage();
			}
			$this->displaying = 'profile';
		}

		// Otherwise, just showing a list of vendors
		else {
			$this->displaying = 'vendors';
		}
	}
}