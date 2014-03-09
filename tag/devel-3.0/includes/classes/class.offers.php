<?php

class ISC_OFFERS {
	
	public function HandlePage(){
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetLang('Offers'));
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("offers");
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}
}