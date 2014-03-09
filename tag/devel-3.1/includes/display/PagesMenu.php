<?php

	CLASS ISC_PAGESMENU_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			$output = "";
			$pages = array();

			if(isset($GLOBALS['ActivePage']) && $GLOBALS['ActivePage'] == "home") {
				$GLOBALS['ActivePageHomeClass'] = "ActivePage";
			}
			else if(isset($GLOBALS['ActivePage']) && $GLOBALS['ActivePage'] == 'store') {
				$GLOBALS['ActivePageStoreClass'] = 'ActivePage';
			}
			else {
				$GLOBALS['ActivePageHomeClass'] = '';
			}

			// If the customer is not logged in then they can only see pages that aren't restricted to 'customers only'
			$loggedIn = false;
			$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
			if($GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId()) {
				$loggedIn = true;
			}

			$pages = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('Pages');

			// Now that we have all of the pages we need to show in the menu, build the menu
			if(!empty($pages) && isset($pages[0])) {
				foreach($pages[0] as $page) {

					if($page['pagecustomersonly'] && !$loggedIn) {
						continue;
					}

					$GLOBALS['PageName'] = isc_html_escape($page['pagetitle']);

					// Is it a normal page, external page or RSS feed?
					switch($page['pagetype']) {
						case 0:
						case 2:
						case 3:{ // Normal Page or RSS feed
							$GLOBALS['PageLink'] = PageLink($page['pageid'], $page['pagetitle']);
							break;
						}
						case 1: { // External Link
							$GLOBALS['PageLink'] = $page['pagelink'];
							break;
						}
					}

					if(isset($GLOBALS['ActivePage']) && $GLOBALS['ActivePage'] == $page['pageid']) {
						$GLOBALS['ActivePageClass'] = "ActivePage";
					}
					else {
						$GLOBALS['ActivePageClass'] = '';
					}

					// Are there any sub-pages?
					$GLOBALS['SubMenu'] = '';
					$GLOBALS['SubMenuLinks'] = '';
					$GLOBALS['HasSubMenuClass'] = '';
					if(isset($pages[$page['pageid']])) {
						$GLOBALS['HasSubMenuClass'] = 'HasSubMenu';
						foreach($pages[$page['pageid']] as $subpage) {
							if($subpage['pagecustomersonly'] && !$loggedIn) {
								continue;
							}
							$GLOBALS['sPageName'] = isc_html_escape($subpage['pagetitle']);

							// Is it a normal page, external page or RSS feed?
							switch($subpage['pagetype']) {
								case 0:
								case 2:
								case 3:{ // Normal Page or RSS feed
									$GLOBALS['sPageLink'] = PageLink($subpage['pageid'], $subpage['pagetitle']);
									break;
								}
								case 1: { // External Link
									$GLOBALS['sPageLink'] = $subpage['pagelink'];
									break;
								}
							}
							$GLOBALS['SubMenuLinks'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("PageSubMenu");
						}
						$GLOBALS['SubMenu'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("PageMenuDropDown");
					}
					$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("PageMenu");
				}
			}
			$GLOBALS['SNIPPETS']['PageMenu'] = $output;
		}
	}