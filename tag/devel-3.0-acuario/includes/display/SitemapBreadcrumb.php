<?php

/**
* Implements the sitemap breadcrumb panel
*/
CLASS ISC_SITEMAPBREADCRUMB_PANEL extends PANEL {

	public function SetPanelSettings()
	{

		// basic check for trail purposes only, the SitemapContent panel will do a 404 if an invalid subsection is specified

		$view = 'default';

		if (isset($_GET['view'])) {
			$view = $_GET['view'];

		} else {
			$path = $GLOBALS['PathInfo'];
			array_shift($path);

			if (isset($path[0])) {
				$view = $path[0];
			}
		}

		$GLOBALS['HideIfSubsection'] = '';
		$GLOBALS['HideIfNoSubsection'] = '';
		$GLOBALS['SitemapSubsectionTrail'] = '';

		if ($view == 'default') {
			$GLOBALS['HideIfNoSubsection'] = 'display:none;';

		} else {
			$GLOBALS['HideIfSubsection'] = 'display:none;';

			$className = 'ISC_SITEMAP_MODEL_' . strtoupper($view);
			if (class_exists($className)) {
				$model = new $className();
				$GLOBALS['SitemapSubsectionTrail'] = isc_html_escape($model->getHeading());
			}
		}

		$GLOBALS['SitemapLink'] = SitemapLink();
	}
}
