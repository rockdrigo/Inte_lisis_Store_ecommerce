<?php

	CLASS ISC_FOOTER_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			$GLOBALS['FooterScripts'] = '';

			$GLOBALS['HideLogoutLink'] = 'display: none';
			if(CustomerIsSignedIn()) {
				$GLOBALS['HideLogoutLink'] = '';
			}

			if($_SERVER['REQUEST_METHOD'] == 'POST') {
				$baseURL = getConfig('ShopPathNormal');
			}
			else {
				$baseURL = getCurrentLocation();
			}

			if(strpos($baseURL, '?') === false) {
				$baseURL .= '?';
			}
			else {
				$baseURL .= '&';
			}

			$fullSiteLink = $baseURL.'fullSite=1';
			$GLOBALS['ISC_CLASS_TEMPLATE']->assign('FullSiteLink', $fullSiteLink);

			// Show Mobile Site link
			if(canViewMobileSite()) {
				$mobileSiteURL = preg_replace('/(&)?fullSite=\d*/i', '', $baseURL);
				$GLOBALS['MobileSiteURL'] = $mobileSiteURL.'fullSite=0';
				$GLOBALS['MobileSiteLink'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('MobileSiteLink');
			}

			// Show "All prices are in [currency code]"
			$currency = GetCurrencyById($GLOBALS['CurrentCurrency']);
			if(is_array($currency) && $currency['currencycode']) {
				$GLOBALS['AllPricesAreInCurrency'] = sprintf(GetLang('AllPricesAreInCurrency'), isc_html_escape($currency['currencyname']), isc_html_escape($currency['currencycode']));
			}

			if(GetConfig('DebugMode') == 1) {
				$end_time = microtime_float();
				$GLOBALS['ScriptTime'] = number_format($end_time - ISC_START_TIME, 4);
				$GLOBALS['QueryCount'] = $GLOBALS['ISC_CLASS_DB']->NumQueries;
				if (function_exists('memory_get_peak_usage')) {
					$GLOBALS['MemoryPeak'] = "Memory usage peaked at ".Store_Number::niceSize(memory_get_peak_usage(true));
				} else {
					$GLOBALS['MemoryPeak'] = '';
				}

				if (isset($_REQUEST['debug'])) {
					$GLOBALS['QueryList'] = "<ol class='QueryList' style='font-size: 13px;'>\n";
					foreach($GLOBALS['ISC_CLASS_DB']->QueryList as $query) {
						$GLOBALS['QueryList'] .= "<li style='line-height: 1.4; margin-bottom: 4px;'>".isc_html_escape($query['Query'])." &mdash; <em>".number_format($query['ExecutionTime'], 4)."seconds</em></li>\n";
					}
					$GLOBALS['QueryList'] .= "</ol>";
				}
				$GLOBALS['DebugDetails'] = "<p>Page built in ".$GLOBALS['ScriptTime']."s with ".$GLOBALS['QueryCount']." queries. ".$GLOBALS['MemoryPeak']."</p>";
			}
			else {
				$GLOBALS['DebugDetails'] = '';
			}

			// Do we have any live chat service code to show in the footer
			$modules = GetConfig('LiveChatModules');
			if(!empty($modules)) {
				$liveChatClass = GetClass('ISC_LIVECHAT');
				$GLOBALS['LiveChatFooterCode'] = $liveChatClass->GetPageTrackingCode('footer');
			}

			// Load our whitelabel file for the front end
			require_once ISC_BASE_PATH.'/includes/whitelabel.php';

			// Load the configuration file for this template
			$poweredBy = 0;
			require_once ISC_BASE_PATH.'/templates/'.GetConfig('template').'/config.php';
			if(isset($GLOBALS['TPL_CFG']['PoweredBy'])) {
				if(!isset($GLOBALS['ISC_CFG']['TemplatePoweredByLines'][$GLOBALS['TPL_CFG']['PoweredBy']])) {
					$GLOBALS['TPL_CFG']['PoweredBy'] = 0;
				}
				$poweredBy = $GLOBALS['TPL_CFG']['PoweredBy'];
			}

			// Showing the powered by?
			$GLOBALS['PoweredBy'] = '';
			if($GLOBALS['ISC_CFG']['DisableFrontEndPoweredBy'] == false && isset($GLOBALS['ISC_CFG']['TemplatePoweredByLines'][$poweredBy])) {
				$GLOBALS['PoweredBy'] = $GLOBALS['ISC_CFG']['TemplatePoweredByLines'][$poweredBy];
			}

			if(empty($GLOBALS['OptimizerConversionScript']) && empty($GLOBALS['OptimizerTrackingScript']) && empty($GLOBALS['OptimizerControlScript'])) {
				$this->setGwoCookieCrossDomain();
			}

			$GLOBALS['SitemapURL_HTML'] = isc_html_escape(SitemapLink());
			$GLOBALS['SNIPPETS']['SitemapLink'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('SitemapLink');

			if (Interspire_TaskManager::hasTasks()) {
				// hasTasks is only implemented for Internal so this will (should) never run for Resque-based task manager
				$GLOBALS['FooterScripts'] .= Interspire_TaskManager::getTriggerHtml('json');
			}

			if (ISC_CATEGORY::areCategoryFlyoutsEnabled()) {
				// this needs to be output from php into the body since it's based on config vars
				// @todo use the stuff gaston is working on instead

				// bgiframe fixes some IE-related issues with CSS menus (like hovering over SELECT elements)
				$GLOBALS['FooterScripts'] .= '<script type="text/javascript" src="'
					. GetConfig('AppPath') . '/javascript/superfish/js/jquery.bgiframe.min.js?'
					. GetConfig('JSCacheToken') . '"></script>' . "\n";
				$GLOBALS['FooterScripts'] .= '<script type="text/javascript" src="'
					. GetConfig('AppPath') . '/javascript/superfish/js/superfish.js?'
					. GetConfig('JSCacheToken') . '"></script>' . "\n";
				$GLOBALS['FooterScripts'] .= '<script type="text/javascript">
	$(function(){
		if (typeof $.fn.superfish == "function") {
			$("ul.sf-menu").superfish({
				delay: ' . ((float)GetConfig('categoryFlyoutMouseOutDelay') * 1000) . ',
				dropShadows: ' . isc_json_encode(GetConfig('categoryFlyoutDropShadow')) . ',
				speed: "fast"
			})
			.find("ul")
			.bgIframe();
		}
	})
</script>
';
			}

			if (GetConfig('FastCartAction') == 'popup' && GetConfig('ShowCartSuggestions')) {
				$GLOBALS['SNIPPETS']['FastCartThickBoxJs'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('FastCartThickBoxJs');
			}
		}



		private function setGwoCookieCrossDomain()
		{
			if(!isset($_GET['__utmx'])) {
				return;
			}

			//we are still here, this is when __utmx is in the url, it should be when the store is using shared ssl, url is different on the checkout page, so the cookies are passed to checkout page via url, we need to run call the GWO script to set the cookies in the url. The script will be just similar to the conversion script, just need to remove the test id in the conversion script, so it doesn't track in correct conversion.

			// we need to get a conversion script from any test that uses finish order and checkout page and modify it so it can be used to simply set the cookies we got from the url
			// we only check the tests that uses order or checkout page as conversion page, because the other tests wouldn't need the cookies to be passed to the checkout page,
			$conversionScript = '';
			$optimizerStorewide = GetClass('ISC_OPTIMIZER');
			$secondDomainPages = array('order', 'checkout', 'accountcreated');
			$crossDomainOptimizerDetails = $optimizerStorewide->getModuleDetailsByConversionPage($secondDomainPages);

			//No storewide optimizer test is using finish order page as conversion page. we need to check the product/category/page based tests.
			if(empty($crossDomainOptimizerDetails)) {
				$optimizerPerPage = GetClass('ISC_OPTIMIZER_PERPAGE');
				$crossDomainOptimizerDetails = $optimizerPerPage->getOptimizerDetailsByConversionPage($secondDomainPages);
				if(isset($crossDomainOptimizerDetails[0]['optimizer_conversion_script'])) {
					$conversionScript = $crossDomainOptimizerDetails[0]['optimizer_conversion_script'];
				}
			} else {
				if(isset($crossDomainOptimizerDetails[0]['conversion_script'])) {
					$conversionScript = $crossDomainOptimizerDetails[0]['conversion_script'];
				}
			}
			//add the link script to the cart page. the link script is similar to tracking script, so use the tracking script for link script,  but need to remove the tracking code from the script
			if($conversionScript != '') {
				//$conversionScript = $crossDomainOptimizerDetails[0]['conversion_script'];
				$conversionScript = preg_replace('/Conversion Script/i', 'Set Cookie Script', $conversionScript);

				$GLOBALS['OptimizerSetCookieScript'] = preg_replace('/gwoTracker\._trackPageview.*;/', '
					gwoTracker._setAllowLinker(true);
					gwoTracker._setDomainName("none");
					gwoTracker._trackPageview();
				', $conversionScript);
				return;
			}
			return;
		}
	}
