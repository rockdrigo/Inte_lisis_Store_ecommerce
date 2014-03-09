<?php

class ISC_404
{
	public function HandlePage()
	{
		GetLib("class.redirects");

		// We're here because we can't find the requested URL
		// It may be a URL that has been set up as a redirect, so lets check that
		ISC_REDIRECTS::checkRedirect($_SERVER['REQUEST_URI']);

		// Send the 404 status headers
		header("HTTP/1.1 404 Not Found");

		// Simply show the 404 page
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName')." - ".GetLang('NotFound'));
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("404");
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}
}