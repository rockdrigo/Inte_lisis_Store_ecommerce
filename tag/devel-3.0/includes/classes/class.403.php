<?php

class ISC_403
{
	function HandlePage()
	{
		// Send the 403 status headers
		header("HTTP/1.1 403 Forbidden");

		$GLOBALS['Contact'] = sprintf(GetLang('NoPermissionContact'), GetConfig('AdminEmail'), GetConfig('AdminEmail'));

		// Simply show the 403 page
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName')." - ".GetLang('ForbiddenAccessPage'));
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("403");
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}
}