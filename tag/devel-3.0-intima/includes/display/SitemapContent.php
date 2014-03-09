<?php

/**
* Implements the sitemap panel
*
*/
CLASS ISC_SITEMAPCONTENT_PANEL extends PANEL {

	/**
	* Sets up data for displaying this panel and routes to more specific handling methods if necessary
	*
	*/
	public function SetPanelSettings()
	{

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

		$viewHandler = 'set' . ucfirst(strtolower($view)) . 'ViewSettings';

		if (!method_exists($this, $viewHandler)) {
			$GLOBALS['ISC_CLASS_404'] = GetClass('ISC_404');
			$GLOBALS['ISC_CLASS_404']->HandlePage();
			exit;
		}

		$this->$viewHandler();
	}

	/**
	* Sets up this panel for displaying the default sitemap view
	*
	*/
	protected function setDefaultViewSettings()
	{
		$GLOBALS['SNIPPETS']['SitemapContent'] = '';
		$template = $GLOBALS['ISC_CLASS_TEMPLATE'];

		$firstPageItemCount = 20;

		$models = array('PAGES', 'CATEGORIES', 'BRANDS', 'VENDORS');

		foreach ($models as &$model) {
			$modelType = ucfirst(strtolower($model));
			$className = 'ISC_SITEMAP_MODEL_' . $model;
			$model = new $className();
			$subsection = $model->getSubsectionUrl();

			if ($subsection) {
				$tree = $model->getTree($firstPageItemCount);
			} else {
				$tree = $model->getTree();
			}

			if (!$tree->countChildren()) {
				continue;
			}

			$html = $tree->generateNodeHtml();

			$template->assign('ModelHideAllLink', 'display: none');
			$template->assign('ModelSubsectionURL', $model->getSubsectionUrl());
			if ($subsection && $model->countAll() > $firstPageItemCount) {
				$template->assign('ModelHideAllLink', '');
			}

			$template->assign('ModelType', $modelType);
			$template->assign('ModelHeading', isc_html_escape($model->getHeading()));
			$template->assign('ModelBody', $html);
			$GLOBALS['SNIPPETS']['SitemapContent'] .= $template->getSnippet('SitemapSection');
		}
	}

	/**
	* Sets up this panel for displaying a subsection view
	*
	* @param string $modelName The name of the model to display a subsection for, ISC_SITEMAP_MODEL_$modelName
	*/
	protected function setViewSettings($modelName)
	{
		$className = 'ISC_SITEMAP_MODEL_' . $modelName;
		$model = new $className();
		$tree = $model->getTree();

		$GLOBALS['TrailSitemapName'] = $model->getHeading();

		$html = $tree->generateNodeHtml();

		$template = $GLOBALS['ISC_CLASS_TEMPLATE'];
		$modelType = ucfirst(strtolower($modelName));
		$template->assign('ModelType', $modelType);
		$template->assign('ModelHeading', isc_html_escape($model->getHeading()));
		$template->assign('ModelHideAllLink', 'display: none');
		$template->assign('ModelBody', $html);
		$GLOBALS['SNIPPETS']['SitemapContent'] = $template->getSnippet('SitemapSection');
	}

	/**
	* Sets up this panel for displaying the categories subsection view
	*
	*/
	protected function setCategoriesViewSettings()
	{
		$this->setViewSettings('CATEGORIES');
	}

	/**
	* Sets up this panel for displaying the pages subsection view
	*
	*/
	protected function setPagesViewSettings()
	{
		$this->setViewSettings('PAGES');
	}

	/**
	* Sets up this panel for displaying the vendors subsection view
	*
	*/
	protected function setVendorsViewSettings()
	{
		$this->setViewSettings('VENDORS');
	}
}
