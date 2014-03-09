<?php

if (!defined('ISC_BASE_PATH')) {
	die();
}

class ISC_SITEMAP {

	public static function encodeHtml($text)
	{
		return isc_html_escape($text);
	}

	public function getPageTitle()
	{
		return GetConfig('StoreName') . ' ' . GetLang('Sitemap');
	}

	public function HandlePage()
	{
		SetPGQVariablesManually();
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle($this->getPageTitle());
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('sitemap');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}
}
