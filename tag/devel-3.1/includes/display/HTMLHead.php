<?php

CLASS ISC_HTMLHEAD_PANEL extends PANEL
{
	public function SetPanelSettings()
	{
		// Do we have any live chat service code to show in the header?
		$modules = GetConfig('LiveChatModules');
		if(!empty($modules)) {
			$liveChatClass = GetClass('ISC_LIVECHAT');
			$GLOBALS['LiveChatCode'] = $liveChatClass->GetPageTrackingCode('header');
			$GLOBALS['LiveChatCodeEnabled'] = '';
		} else {
			$GLOBALS['LiveChatCodeEnabled'] = 'display:none';
		}

		$GLOBALS['TrackingCode'] = '';

		// Get the visitor tracking Javascript
		$tracker = GetClass('ISC_VISITOR');
		$GLOBALS['TrackingCode'] .= $tracker->GetTrackingJavascript();

		$GLOBALS['CharacterSet'] = GetConfig('CharacterSet');

		// Are quick searches enabled?
		if(GetConfig('QuickSearch') != 0) {
			$GLOBALS['QuickSearchJS'] = sprintf("<script type=\"text/javascript\" src=\"%s/javascript/quicksearch.js\"></script>", GetConfig('ShopPath'));
		}

		$activeTemplate = $GLOBALS['ISC_CLASS_TEMPLATE']->getActiveTemplateName();
		$activeColorScheme = $GLOBALS['ISC_CLASS_TEMPLATE']->getActiveColorScheme();

		if(isset($GLOBALS['TPL_CFG']['HeaderImageElement'])) {
			$headerImageLocation = '';

			$headerImages = array(
				GetConfig('ImageDirectory') . '/header_images/' . $activeTemplate . '_headerImage.jpg',
				GetConfig('ImageDirectory') . '/header_images/' . $activeTemplate . '_headerImage.png',
				'templates/'.$activeTemplate . '/images/' . $activeColorScheme . '/headerImage.jpg',
				'templates/'.$activeTemplate . '/images/' . $activeColorScheme . '/headerImage.png'
			);
			foreach($headerImages as $path) {
				if(file_exists(ISC_BASE_PATH . '/' . $path)) {
					$headerImageLocation = GetConfig('ShopPath') . '/' . $path;
					break;
				}
			}

			if(!empty($headerImageLocation)) {
				$GLOBALS['HeaderImageStyle'] = '<style type="text/css"> ' .$GLOBALS['TPL_CFG']['HeaderImageElement'] . ' { background-image: url("' . $headerImageLocation . '"); } </style>';
			}
		}

		if (GetConfig('FastCartAction') == 'popup' && GetConfig('ShowCartSuggestions')) {
			$GLOBALS['AdditionalScripts'][] = GetConfig('AppPath').'/javascript/jquery/plugins/imodal/imodal.js';
			$GLOBALS['AdditionalStylesheets'][] = GetConfig('AppPath').'/javascript/jquery/plugins/imodal/imodal.css';
		}

		// Any additional stylesheets to include?
		$GLOBALS['Stylesheets'] = '';

		if(!empty($GLOBALS['TPL_CFG']['Stylesheets'])) {
			$stylesheets = $GLOBALS['TPL_CFG']['Stylesheets'];
		}
		else {
			$stylesheets = array(
				array(
					'stylesheet' => 'Styles/styles.css',
				),
				array(
					'stylesheet' => 'Styles/iselector.css'
				)
			);

			// Color overrides
			$colorCSS = 'Styles/'.$activeColorScheme.'.css';
			if(file_exists(ISC_BASE_PATH.'/templates/'.$activeTemplate.'/'.$colorCSS)) {
				$stylesheets[] = array(
					'stylesheet' => $colorCSS
				);
			}

			// Generic Internet Explorer stylesheet
			$genericIE = 'Styles/ie.css';
			if(file_exists(ISC_BASE_PATH.'/templates/'.$activeTemplate.'/'.$genericIE)) {
				$stylesheets[] = array(
					'stylesheet' => $genericIE,
					'condition' => 'IE'
				);
			}

			if(!empty($GLOBALS['TPL_CFG']['AdditionalStylesheets'])) {
				$stylesheets = array_merge($stylesheets, $GLOBALS['TPL_CFG']['AdditionalStylesheets']);
			}
		}

		$GLOBALS['Stylesheets'] = '';

		// Global/common front-end stylesheet
		$masterStylesheet = getConfig('ShopPath') . '/templates/__master/Styles/styles.css?' . getConfig('JSCacheToken');
		$GLOBALS['Stylesheets'] .= '<link href="' . $masterStylesheet . '" type="text/css" rel="stylesheet" />';

		$styleRoot = GetConfig('ShopPath').'/templates/'.$activeTemplate;
		foreach($stylesheets as $stylesheet) {
			if(empty($stylesheet['media'])) {
				$stylesheet['media'] = 'all';
			}

			// Add caching token
			if(strpos($stylesheet['stylesheet'], '?') === false) {
				$stylesheet['stylesheet'] .= '?';
			}
			else {
				$stylesheet['stylesheet'] .= '&';
			}
			$stylesheet['stylesheet'] .= getConfig('JSCacheToken');

			$link = '<link href="'.$styleRoot.'/'.$stylesheet['stylesheet'].'" media="'.$stylesheet['media'].'" type="text/css" rel="stylesheet" />';
			if(!empty($stylesheet['condition'])) {
				$link = '<!--[if '.$stylesheet['condition'].']>'.$link.'<![endif]-->';
			}
			$GLOBALS['Stylesheets'] .= $link."\n";
		}

		// @todo this check should be a method of it's which determines if flyout css is required since flyout support
		// may be disabled by the selected template, and there may be other, non-category flyout menus added in future
		if (ISC_CATEGORY::areCategoryFlyoutsEnabled()) {
			// css for fly-out menus
			if (!isset($GLOBALS['AdditionalStylesheets']) || !is_array($GLOBALS['AdditionalStylesheets'])) {
				$GLOBALS['AdditionalStylesheets'] = array();
			}
			$GLOBALS['AdditionalStylesheets'][] = GetConfig('AppPath') . '/javascript/superfish/css/store.css';
		}

		if(!empty($GLOBALS['AdditionalStylesheets']) && is_array($GLOBALS['AdditionalStylesheets'])) {
			$GLOBALS['AdditionalStylesheets'] = array_unique($GLOBALS['AdditionalStylesheets']);
			$replacements = array(
				':template' => $activeTemplate,
				':color' => $activeColorScheme
			);
			foreach($GLOBALS['AdditionalStylesheets'] as $stylesheet) {
				$stylesheet = strtr($stylesheet, $replacements);

				// Add caching token
				if(strpos($stylesheet, '?') === false) {
					$stylesheet .= '?';
				}
				else {
					$stylesheet .= '&';
				}
				$stylesheet .= getConfig('JSCacheToken');

				$GLOBALS['Stylesheets'] .= '<link href="'.$stylesheet.'" type="text/css" rel="stylesheet" />';
			}
		}


		$GLOBALS['AdditionalScriptTags'] = '';

		if(!empty($GLOBALS['AdditionalScripts']) && is_array($GLOBALS['AdditionalScripts'])) {
			$GLOBALS['AdditionalScripts'] = array_unique($GLOBALS['AdditionalScripts']);
			foreach($GLOBALS['AdditionalScripts'] as $script) {
				// Add caching token
				if(strpos($script, '?') === false) {
					$script .= '?';
				}
				else {
					$script .= '&';
				}
				$script .= getConfig('JSCacheToken');

				$GLOBALS['AdditionalScriptTags'] .= '<script type="text/javascript" src="'.$script.'"></script>';
			}
		}

		// Are site wide RSS feeds enabled?
		if(!isset($GLOBALS['HeadRSSLinks'])) {
			$GLOBALS['HeadRSSLinks'] = '';
		}

		if(GetConfig('RSSLatestBlogEntries') != 0) {
			$GLOBALS['HeadRSSLinks'] .= GenerateRSSHeaderLink($GLOBALS['ShopPathNormal']."/rss.php?action=newblogs", GetLang('HeadRSSLatestNews'));
		}

		if(GetConfig('RSSNewProducts') != 0) {
			$GLOBALS['HeadRSSLinks'] .= GenerateRSSHeaderLink($GLOBALS['ShopPathNormal']."/rss.php", GetLang('HeadRSSNewProducts'));
		}

		if(GetConfig('RSSPopularProducts') != 0) {
			$GLOBALS['HeadRSSLinks'] .= GenerateRSSHeaderLink($GLOBALS['ShopPathNormal']."/rss.php?action=popularproducts", GetLang('HeadRSSPopularProducts'));
		}

		/*
		 * if the "Enable Product Search Feeds?" is ticked in Store
		 * Settings -> Display and we are searching add the link
		 */
		if (isset($GLOBALS['ISC_CLASS_SEARCH']) && GetConfig('RSSProductSearches')) {
			$rssUri = $GLOBALS['ShopPathNormal']
					. '/rss.php?action=searchproducts&amp;type=rss'
					. SearchLink($GLOBALS['ISC_CLASS_SEARCH']->GetQuery(), 0, false);

			$GLOBALS['HeadRSSLinks'] .= GenerateRSSHeaderLink($rssUri, GetLang('HeadRSSProductSearchFeeds'));
		}

		// Do we need to include the script for design mode?
		if(!empty($_COOKIE['designModeToken']) || !empty($_POST['designModeToken'])) {
			if(GetClass('ISC_ADMIN_AUTH')->isDesignModeAuthenticated()) {
				$GLOBALS['DesignModeStyleSheet'] = sprintf("<link href=\"%s/lib/designmode/designmode.css\" type=\"text/css\" rel=\"stylesheet\" />", $GLOBALS['AppPath']);

				// If the control panel is accessibly only via SSL, we need to send design mode
				// updates to that URL instead.
				if(GetConfig('ForceControlPanelSSL')) {
					$GLOBALS['DesignModeUpdateUrl'] = GetConfig('ShopPathSSL');
				}
				else {
					$GLOBALS['DesignModeUpdateUrl'] = GetConfig('AppPath');
				}
				$GLOBALS['DesignModeUpdateUrl'] .= '/admin/designmode.php';

				$GLOBALS['DesignModeCurrentTemplate'] = $GLOBALS['ISC_CLASS_TEMPLATE']->_tplName.'.html';
				$GLOBALS['DesignModeIdleTime'] = (int)GetConfig('PCILoginIdleTimeMin') * 60 * 1000;
				$GLOBALS['DesignModeScriptTag'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('DesignModeFooter');
			}
		}

		// Include the tracking code for each analytics module
		$GLOBALS['TrackingCode'] .= GetTrackingCodeForAllPackages();

		// Define the favicon link
		$GLOBALS['Favicon'] = GetConfig('ShopPath') . '/' . GetConfig('ImageDirectory') . '/' . GetConfig('Favicon');

		if(!isset($GLOBALS['OptimizerControlScript'])) {
			$GLOBALS['OptimizerControlScript'] = '';
		}
		if(!isset($GLOBALS['OptimizerTrackingScript'])) {
			$GLOBALS['OptimizerTrackingScript'] = '';
		}
		if(!isset($GLOBALS['OptimizerConversionScript'])) {
			$GLOBALS['OptimizerConversionScript'] = '';
		}
		$runStorewideTesting = true;
		//this is product/category/page based optimizer test page, turn off storewide test on this page.
		if ((isset($GLOBALS['PerPageOptimizerEnabled']) && $GLOBALS['PerPageOptimizerEnabled'] == 1)) {
			$runStorewideTesting=false;
		}
		unset($GLOBALS['PerPageOptimizerEnabled']);
		$enabledOptimizerTests = GetConfig('OptimizerMethods');
		//the optimizer methods in the config.php file is not an array. set it to an empty array.
		if(!is_array($enabledOptimizerTests)) {
			$enabledOptimizerTests = array();
		}
		foreach ($enabledOptimizerTests as $moduleId => $date) {

			//if "optimizer" is in the URL, that means this is a request from Google to validate the scripts installed on the page for a paticular test, in this case, we should only insert the scripts for the particular test.
			if(isset($_GET['optimizer']) && 'optimizer_'.$_GET['optimizer'] != $moduleId && $_GET['optimizer'] != 'singlemulticheckout') {
				continue;
			}

			if(getModuleById('optimizer', $module, $moduleId)){
				if($runStorewideTesting) {
					$module->insertControlScript();
					$module->insertTrackingScript();
				}
				$module->insertConversionScript();
			}

			//we are here when optimizer is set, that means the needed optimizer scripts for google to validate have already installed on the page, so get out from the loop.
			if(isset($_GET['optimizer']) && $_GET['optimizer'] != 'singlemulticheckout'){
				break;
			}
		}


		//insert perpage based optimizer conversion script
		$perPageOptimizer = getClass('ISC_OPTIMIZER_PERPAGE');
		$perPageOptimizer->insertConversionScript();

		if(isset($_SESSION['JustAddedProduct'])) {
			$_SESSION['JustAddedProduct'] = '';
		}

		$GLOBALS['ProductThumbImageWidth'] = GetConfig('ProductImagesStorewideThumbnail_width');
		$GLOBALS['ProductThumbImageHeight'] = GetConfig('ProductImagesStorewideThumbnail_height');
	}
}
