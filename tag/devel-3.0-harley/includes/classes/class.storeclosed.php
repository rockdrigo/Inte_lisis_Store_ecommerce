<?php

class ISC_STORECLOSED
{
	public function HandlePage()
	{
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName')." - ".GetLang('ClosedStore'));
		$StoreHoursFrom = str_pad($GLOBALS['ISC_CFG']['StoreHoursFromHours'], 2, "0", STR_PAD_LEFT).":".str_pad($GLOBALS['ISC_CFG']['StoreHoursFromMinutes'], 2, "0", STR_PAD_LEFT);
		$StoreHoursTo = str_pad($GLOBALS['ISC_CFG']['StoreHoursToHours'], 2, "0", STR_PAD_LEFT).":".str_pad($GLOBALS['ISC_CFG']['StoreHoursToMinutes'], 2, "0", STR_PAD_LEFT);

		if($GLOBALS['ISC_CFG']['StoreClosed'] == 1)
		{
			$GLOBALS['ClosedStoreHelp'] = GetLang('StoreClosedMessage');
		}
		else if($GLOBALS['ISC_CFG']['UseStoreHours'] == 1 && WorkingAfterHours())
		{
			$GLOBALS['ClosedStoreHelp'] = sprintf(GetLang('ClosedStoreHelp'), $StoreHoursFrom, $StoreHoursTo);
		}
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("storeclosed");
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}
}
