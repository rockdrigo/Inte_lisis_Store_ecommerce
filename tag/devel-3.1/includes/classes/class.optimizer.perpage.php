<?php
class ISC_OPTIMIZER_PERPAGE
{
	public $conversionPages =  array(
				'NewsLetter'=>'subscribe.php',
				'AccountCreated' => 'login.php?action=save_new_account',
				'Cart' => 'cart.php',
				'Order' => 'finishorder.php',
				'Checkout' => 'checkout.php',
			);


	public function getConversionPages()
	{
		return $this->conversionPages;
	}

	public function insertControlScript()
	{
		return '';
	}

	public function getOptimizerDetails($type, $itemId)
	{
		if(!$type || !$itemId) {
			return array();
		}
		$query = "SELECT *
					FROM [|PREFIX|]optimizer_config
					WHERE optimizer_type='".$GLOBALS['ISC_CLASS_DB']->Quote($type)."'
							AND
							optimizer_item_id = '".$GLOBALS['ISC_CLASS_DB']->Quote($itemId)."'
					";
		$result=$GLOBALS['ISC_CLASS_DB']->Query($query);
		if(!$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)){
			return array();
		}
		return $row;
	}

	private function getConversionScriptsForPage($page)
	{
		if($page==''){
			return array();
		}

		$query = "SELECT *
			FROM [|PREFIX|]optimizer_config
			WHERE optimizer_conversion_page='".$GLOBALS['ISC_CLASS_DB']->Quote($page)."'
			";
		$result=$GLOBALS['ISC_CLASS_DB']->Query($query);
		$conversionScripts=array();
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)){
			$conversionScripts[] = $row;
		}
		return $conversionScripts;
	}


	public function insertConversionScript()
	{
		//built in conversion pages.
		$conversionPages = $this->getConversionPages();
		$conversionPage = '';

		// some configurations of IIS don't set REQUEST_URI so we fix it here, fixes ISC-537
		if (!isset($_SERVER['REQUEST_URI'])) {
			$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
			if (isset($_SERVER['QUERY_STRING'])) {
				$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
			}
		}

		foreach($conversionPages as $page => $url) {
			if(strpos($_SERVER["REQUEST_URI"], $url) !== false) {
				//if this is not the cart page after product is added to cart,
				if($page == 'Cart') {
					if (!isset($_SESSION['JustAddedProduct']) || $_SESSION['JustAddedProduct'] =='') {
						return;
					}
				}
				$conversionPage = $page;
				break;
			}
		}

		if($conversionPage != '') {
			$conversionScripts = $this->getConversionScriptsForPage($conversionPage);
			//$GLOBALS['OptimizerConversionScript'] .= implode(' ', $conversionScripts);
			$scripts = $GLOBALS['OptimizerConversionScript'];
			foreach($conversionScripts as $row) {
				//if it's a per product based GWO test insert the conversion script only when the action is associate to the product
				$noConversion = false;

				if($row['optimizer_type'] == 'product') {
					switch(isc_strtolower($page)) {
						case 'cart':
							if($_SESSION['JustAddedProduct'] != $row['optimizer_item_id']) {
								$noConversion = true;
							}
							break;
						case 'checkout':
							$prodInCart = array();
							$noConversion = true;
							$items = getCustomerQuote()->getItems();
							foreach($items as $item) {
								if($row['optimizer_item_id'] == $item->getProductId()) {
									$noConversion = false;
									break;
								}
							}
							break;
						case 'order':
							if(isset($_SESSION['ProductJustOrdered'])) {
								$prodOrdered = explode(',',$_SESSION['ProductJustOrdered']);
								if(!in_array($row['optimizer_item_id'], $prodOrdered)) {
									$noConversion = true;
								}
							}
						break;
					}
				}
				if($noConversion) {
					continue;
				}
				$curScript = $row['optimizer_conversion_script'];
				//merge multiple conversion script to one.
				if($scripts != '') {
					$scriptID = preg_replace("/\/goal(\s|.)*/", '', $curScript);
					$scriptID = preg_replace("/(\s|.)*trackPageview\(\"\//", '', $scriptID);

					$scriptPart = 'gwoTracker._trackPageview("/'.$scriptID.'/goal");
}catch(err){}</script>';

					$scripts = str_replace('}catch(err){}</script>',$scriptPart, $scripts);
				} else {
					$scripts = $curScript;
				}
			}

			$GLOBALS['OptimizerConversionScript'] = $scripts;
		}
	}

	public function getOptimizerDetailsByConversionPage($conversionPages)
	{
		$optimizeDetails = array();
		$query = "Select *
					from [|PREFIX|]optimizer_config
					where optimizer_conversion_page in ('".implode("','", $conversionPages)."')";

		$result=$GLOBALS['ISC_CLASS_DB']->Query($query);
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)){
			$optimizeDetails[]=$row;
		}
		return $optimizeDetails;
	}


	public function getLinkScriptForConversionPage($conversionPages)
	{
		$optimizeDetails = array();
		$query = "
			SELECT *
			FROM [|PREFIX|]optimizer_config
			WHERE optimizer_conversion_page in ('".implode("','", $conversionPages)."')
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if(empty($row['optimizer_tracking_script'])) {
			return '';
		}

		$trackingScript = $row['optimizer_tracking_script'];
		$linkScript = preg_replace('/gwoTracker\._trackPageview.*;/i', '
			gwoTracker._setAllowLinker(true);
			gwoTracker._setDomainName("none");
		', $trackingScript);
		return $linkScript;
	}
}