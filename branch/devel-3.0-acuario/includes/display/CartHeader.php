<?php

	CLASS ISC_CARTHEADER_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			$numItems = getCustomerQuote()->getNumItems();
			$ShowCheckoutButton = false;
			if($numItems > 0) {
				foreach (GetAvailableModules('checkout', true, true) as $module) {
					if ($module['object']->disableNonCartCheckoutButtons) {
						$GLOBALS['HideCheckoutButton'] = 'display: none';
						$ShowCheckoutButton = false;
						break;
					}
					if (!method_exists($module['object'], 'GetCheckoutButton')) {
						$ShowCheckoutButton = true;
					}
				}
			}

			$GLOBALS['HideCheckoutButton'] = '';

			if (!$ShowCheckoutButton) {
				$GLOBALS['HideCheckoutButton'] = 'display: none';
			}
			$this->insertOptimizerLinkScript();

		}

		public function insertOptimizerLinkScript()
		{

			// if it's not using shared ssl,  do nothing
			if(GetConfig('UseSSL') != 2) {
				return;
			}

			// if singlemulticheckout (checkout process optimizer testing) is enabled, the cross domain tracking wouldn't work when this test is enabled. So don't insert the link script on the cart page.
			$enabledOptimizerTests = GetConfig('OptimizerMethods');
			if(in_array('optimizer_singlemulticheckout', array_keys($enabledOptimizerTests))) {
				return;
			}

			$GLOBALS['OptimizerLinkScript'] = '';
			$GLOBALS['OptimizerLinkFunction'] = '';
			$trackingScript = '';

			//we are here, means the store is using a shared ssl, the checkout page is on a different domain, check if and of the storewide test using finish order page as conversion page, if so, this test is a cross domain test, we need to modify the process to checkout link on the cart page so it pass the user cookies to the checkout page.
			$optimizerStorewide = GetClass('ISC_OPTIMIZER');
			$secondDomainPages = array('order', 'checkout');
			$linkScript = $optimizerStorewide->getLinkScriptForConversionPage($secondDomainPages);

			//No storewide optimizer test is using finish order page as conversion page. we need to check the product/category/page based tests.
			if($linkScript == '') {
				$optimizerPerPage = GetClass('ISC_OPTIMIZER_PERPAGE');
				$linkScript = $optimizerPerPage->getLinkScriptForConversionPage($secondDomainPages);
			}

			//add the link script to the cart page. the link script is similar to tracking script, so use the tracking script for link script,  but need to remove the tracking code from the script
			if($linkScript != '') {
				$GLOBALS['OptimizerLinkScript'] = $linkScript;
				$GLOBALS['OptimizerLinkFunction'] = "gwoTracker._link(this.href); return false;";
				return;
			}
			return;
		}
	}