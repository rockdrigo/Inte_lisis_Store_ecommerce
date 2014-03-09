<?php

CLASS ISC_SIDECARTCONTENTS_PANEL extends PANEL
{
	public function SetPanelSettings()
	{
		$GLOBALS['SNIPPETS']['SideCartItems'] = '';

		// We check $_SESSION['QUOTE'] directly here as to not
		// instantiate the quote if it doesn't already exist.
		if(!isset($_SESSION['QUOTE']) && getCustomerQuote()->getNumItems() == 0) {
			$this->DontDisplay = true;
			return;
		}

		$incTax = false;
		if(getConfig('taxDefaultTaxDisplayCart') != TAX_PRICES_DISPLAY_EXCLUSIVE) {
			$incTax = true;
		}

		$quote = getCustomerQuote();
		$items = $quote->getItems();
		foreach($items as $item) {
			if($item->getProductId()) {
				$GLOBALS['ProductName'] = "<a href=\"".ProdLink($item->getName())."\">".isc_html_escape($item->getName())."</a>";
			}
			else {
				$GLOBALS['ProductName'] = isc_html_escape($item->getName());
			}

			// Is this product a variation?
			$GLOBALS['ProductOptions'] = '';
			$options = $item->getVariationOptions();
			if(!empty($options)) {
				$GLOBALS['ProductOptions'] .= "<br /><small>(";
				$comma = '';
				foreach($options as $name => $value) {
					if(!trim($name) || !trim($value)) {
						continue;
					}
					$GLOBALS['ProductOptions'] .= $comma.isc_html_escape($name).": ".isc_html_escape($value);
					$comma = ', ';
				}
				$GLOBALS['ProductOptions'] .= ")</small>";
			}
			
			$GLOBALS['ProductThumbnail'] = $item->getThumbnail();

			$GLOBALS['ProductCartPrice'] = currencyConvertFormatPrice($item->getTotal($incTax));
			$GLOBALS['ProductQuantity'] = $item->getQuantity();
			$GLOBALS['SNIPPETS']['SideCartItems'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SideCartItem");
		}

		$numItems = $quote->getNumItems();
		if($numItems == 1) {
			$GLOBALS['SideCartItemCount'] = GetLang('SideCartYouHave1Item');
		} else {
			$GLOBALS['SideCartItemCount'] = sprintf(GetLang('SideCartYouHaveXItems'), $numItems);
		}

		$total = $quote->getSubTotal($incTax);
		$GLOBALS['ISC_LANG']['SideCartTotalCost'] = sprintf(GetLang('SideCartTotalCost'), CurrencyConvertFormatPrice($total));

		// Go through all the checkout modules looking for one with a GetSidePanelCheckoutButton function defined
		$GLOBALS['AdditionalCheckoutButtons'] = '';
		$HideCheckout = false;
		foreach (GetAvailableModules('checkout', true, true) as $module) {
			if (method_exists($module['object'], 'GetSidePanelCheckoutButton')) {
				$GLOBALS['AdditionalCheckoutButtons'] .= $module['object']->GetSidePanelCheckoutButton();
			}

			if ($module['object']->disableNonCartCheckoutButtons) {
				$HideCheckout = true;
			}

		}

		if ($HideCheckout) {
			$GLOBALS['SNIPPETS']['SideCartContentsCheckoutLink'] = '';
		} else {
			require_once ISC_BASE_PATH.'/includes/display/CartHeader.php';

			$cartPanel = getClass('ISC_CARTHEADER_PANEL');
			$cartPanel -> insertOptimizerLinkScript();
			$GLOBALS['SNIPPETS']['SideCartContentsCheckoutLink'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('SideCartContentsCheckoutLink');
		}

	}
}