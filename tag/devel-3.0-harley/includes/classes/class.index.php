<?php

	class ISC_INDEX
	{
		public function HandlePage()
		{
			// No action here, just show the home page
			$this->ShowHomePage();
		}

		public function ShowHomePage()
		{
			if(isset($GLOBALS['PathInfo'][0]) && ($GLOBALS['PathInfo'][0] == 'store' || $GLOBALS['PathInfo'][0] == 'shop')) {
				$GLOBALS['ActivePage'] = 'store';
			}
			else {
				$GLOBALS['ActivePage'] = "home";
			}

			// Is there a normal page set to be the default home page?
			$pagesCache = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('Pages');
			if($GLOBALS['ActivePage'] != 'store' && !empty($pagesCache['defaultPage']['pageid']) &&
				!$GLOBALS['ISC_CLASS_TEMPLATE']->getIsMobileDevice()) {
					// Load a page created from the control panel
					$GLOBALS['ISC_CLASS_PAGE'] = new ISC_PAGE($pagesCache['defaultPage']['pageid'], true, $pagesCache['defaultPage']);
					$GLOBALS['ISC_CLASS_PAGE']->ShowPage();
			}
			else {
				// Load the dynamic home page instead
				if(GetConfig('HomePagePageTitle')) {
					$title = GetConfig('HomePagePageTitle');
				}
				else {
					$title = GetConfig('StoreName');
				}
				$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle($title);
				$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("default");
				$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
			}
		}
	}