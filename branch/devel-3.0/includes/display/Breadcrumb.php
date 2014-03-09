<?php
CLASS ISC_BREADCRUMB_PANEL extends PANEL
{
	public function SetPanelSettings()
	{
		if(!isset($GLOBALS['BreadCrumbs'])) {
			$this->DontDisplay = true;
			return;
		}

		$GLOBALS['ISC_CLASS_TAGS'] = GetClass('ISC_TAGS');

		$GLOBALS['SNIPPETS']['Trail'] = '';
		$trailCount = count($GLOBALS['BreadCrumbs']);
		foreach($GLOBALS['BreadCrumbs'] as $k => $trail) {
			$GLOBALS['CatTrailName'] = isc_html_escape($trail['name']);
			$GLOBALS['CatTrailLink'] = '';
			if(isset($trail['link'])) {
				$GLOBALS['CatTrailLink'] = isc_html_escape($trail['link']);
			}

			if($k == $trailCount-1) {
				$GLOBALS['SNIPPETS']['Trail'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("BreadcrumbItemCurrent");
			}
			else {
				$GLOBALS['SNIPPETS']['Trail'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("BreadcrumbItem");
			}
		}
	}
}