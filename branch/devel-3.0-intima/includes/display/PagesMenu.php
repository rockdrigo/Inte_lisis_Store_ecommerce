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
				$CustomerInfo = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerInfo();
				$groupId = $CustomerInfo['custgroupid'];
			}

			$pages = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('Pages');
			
			$pages_customer_groups = $this->getPagesCustomerGroups();

			// Now that we have all of the pages we need to show in the menu, build the menu
			if(!empty($pages) && isset($pages[0])) {
				foreach($pages[0] as $page) {

					if($page['pagecustomersonly'] && !$loggedIn) {
						continue;
					}
					
					// REQ 11890 - NES: Agrego esto para checar si el usuairo tiene permiso de ver la pagina, y que no lo agregue si no lo tiene 
					if($page['pagecustomersonly'] == '2') {
						if(isset($pages_customer_groups[$page['pageid']])) {
							if(!isset($pages_customer_groups[$page['pageid']][$groupId])) {
								continue;
							}
						}
						else {
							continue;
						}
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
							
							// REQ 11890 - NES: Agrego esto para checar si el usuairo tiene permiso de ver la pagina, y que no lo agregue si no lo tiene 
							if($subpage['pagecustomersonly'] == '2') {
								if(isset($pages_customer_groups[$subpage['pageid']])) {
									if(!isset($pages_customer_groups[$subpage['pageid']][$groupId])) {
										continue;
									}
								}
								else {
									continue;
								}
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
		
		private function getPagesCustomerGroups() {
			$return = array();
			$result = $GLOBALS['ISC_CLASS_DB']->Query('SELECT * FROM [|PREFIX|]pages_customer_groups');
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				if(!isset($return[$row['pageid']])) $return[$row['pageid']] = array();

				$return[$row['pageid']][$row['custgroupid']] = 1;
			}
			return $return;
		}
	}