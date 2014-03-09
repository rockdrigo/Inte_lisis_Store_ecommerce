<?php

/**
 * Basically this is the "controller" for the shopping comparison settings page.
 */
class ISC_ADMIN_SHOPPINGCOMPARISON extends ISC_ADMIN_BASE
{
	/**
	 * Whether or not to render the layout.
	 *
	 * @var bool
	 */
	protected $renderLayout = true;

	/**
	 * Holds the language variables.
	 *
	 * @var object
	 */
	protected $lang;

	/**
	 * Constructs a new shopping comparison "controller" and sets defaults.
	 *
	 * @return Isc_Admin_ShoppingComparison
	 */
	public function __construct()
	{
		parent::__construct();

		// parse the language file
		$this->engine->loadLangFile('shoppingcomparison');
	}

	/**
	 * Does internal settings page routing. Something like this should be built in.
	 *
	 * @param string $todo The "action" for this "controller"
	 * @return void
	 */
	public function HandleToDo($todo)
	{
		GetLib('class.json');

		// todo method name
		$todo = $todo . 'Action';

		// the page to render is returned as a string from the action
		$render = null;

		// process action routing
		if (method_exists($this, $todo) || method_exists($this, '__call')) {
			$render = $this->$todo();
		}

		// process template routing
		if ($render && is_string($render)) {
			if ($this->renderLayout) {
				$this->engine->printHeader();
			}

			$this->template->display($render . '.tpl');

			if ($this->renderLayout) {
				$this->engine->printFooter();
			}
		}
	}

	/**
	 * Handles any undefined actions.
	 *
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		// do page not found
	}

	public function shoppingComparisonScriptTestAction()
	{
		//$categories = $taxonomy->getSubCategories($parentid);

		$this->engine->addBreadcrumb(
			GetLang('ShoppingComparisonManagePageTitle'),
			'index.php?ToDo=viewShoppingComparison'
		);

		$this->template->assign('Message', GetFlashMessageBoxes());
		$this->template->assign('modules', $this->getManageScreenModules());

		return 'script.test';
	}

	/**
	 * Handles a call from the category selector javascript
	 * to return one or more category selection boxes.
	 * @see script category.selector.js
	 **/
	public function getShoppingComparisonCategoriesAction()
	{
		GetModuleById('shoppingcomparison',
			$module,
			$this->getValue($_REQUEST, 'mid'));

		$categoryid = (integer)$this->getValue($_REQUEST, 'categoryid');

		if($module
			&& $categoryid >= 0
			&& ($taxonomy = $module->getTaxonomy()))
		{
			/**
			 * If 'all' is defined we generate category selection
			 * boxes for all the parents, otherwise we generated a
			 * selection box for the specified cateogry id alone.
			 */
			if(!empty($_REQUEST['all']))
				$parents = $taxonomy->getParentCategories($categoryid);
			else
				$parents[] = $categoryid;

			$selectedid = $categoryid;
			foreach($parents as $parentid) {
				$html = $this->getCategorySelectBoxHTML($parentid, $taxonomy, $selectedid);
				$boxes[] = array(
					'html' => $html,
					'categoryid' => $parentid,
				);

				$selectedid = $parentid;
			}

			/**
			 * Package the output containing the boxes to be displayed
			 * and the category id that was given.
			 */
			$output = array(
				'boxes' => $boxes,
				'categoryid' => $categoryid
			);

			ISC_JSON::output('', true, $output);
		}
	}

	/**
	 * Generates a html fragment containing a selectable list of children
	 * categories for the given parent id.
	 *
	 * @param integer Parent category id
	 * @param object The category taxonomy object
	 * @param integer (optional) A category id to mark as pre selected
	 */
	private function getCategorySelectBoxHTML($parentid, $taxonomy, $selectedid)
	{
		$categories = $taxonomy->getSubCategories($parentid);

		$this->template->assign('parentid', $parentid);
		$this->template->assign('categories', $categories);
		$this->template->assign('selectedid', $selectedid);

		return $this->template->render('shoppingcomparison.categorybox.tpl');
	}

	public function mapShoppingComparisonCategoriesAction()
	{
		GetModuleById('shoppingcomparison',
			$module,
			$this->getValue($_REQUEST, 'mid'));

		if($module) {
			$categories = array();

			foreach($_POST['categories'] as $category => $checked ) {
				$categories[] = $category;
			}

			$module->mapCategories(
				$categories,
				$_REQUEST['categoryid'],
				$_REQUEST['path']);

			$message = GetLang('ShoppingComparisonCategoryAssociationsUpdatedSuccessfully');
			FlashMessage($message, MSG_SUCCESS);
		}
	}

	/**
	 * Handles managing of the shopping comparison.
	 *
	 * @return string
	 */
	public function viewShoppingComparisonAction()
	{
		$this->engine->addBreadcrumb(
			GetLang('ShoppingComparisonManagePageTitle'),
			'index.php?ToDo=viewShoppingComparison'
		);

		$this->template->assign('Message', GetFlashMessageBoxes());
		$this->template->assign('modules', $this->getManageScreenModules());

		return 'shoppingcomparison.manage';
	}

	/**
	 * Handles AJAX request to start a shopping comparison export.
	 * Outputs the controller id for the task, used for job tracking
	 * on the front end.
	 */
	public function generateShoppingComparisonFeedAction()
	{
		GetModuleById('shoppingcomparison',
			$module,
			$this->getValue($_REQUEST, 'mid'));

		if($module) {
			$controller = $module->pushExportTask();
			echo $controller->getId();
		}
	}

	public function stopShoppingComparisonFeedAction()
	{
		GetModuleById('shoppingcomparison',
			$module,
			$this->getValue($_REQUEST, 'mid'));

		if($module) {
			$module->abortExportTask();
			ISC_JSON::output(getLang('ShoppingComparisonExportCancelled'), true, null);
		}
	}

	/**
	 * Forces a feed file to be downloaded.
	 */
	public function downloadShoppingComparisonFeedAction()
	{
		GetModuleById('shoppingcomparison',
			$module,
			$this->getValue($_REQUEST, 'mid'));

		if($module)
			Interspire_Download::downloadFile($module->getExportFile());

		die();
	}

	/**
	 * Action for saving the shopping checked shopping comparisons on the settings
	 * page.
	 *
	 * @return void
	 */
	public function saveShoppingComparisonAction()
	{
		$selectedModuleIds = (array)$this->getValue($_POST, 'modules');
		$redirectUrl = 'index.php?ToDo=viewShoppingComparison';

		if ($this->setModules($selectedModuleIds)) {
			$message = GetLang('ShoppingComparisonModulesSavedSuccessfully');
			FlashMessage($message, MSG_SUCCESS, $redirectUrl);
			return;
		}

		$message = GetLang('ShoppingComparisonModulesNotSaved');
		FlashMessage($message, MSG_ERROR, $redirectUrl);
	}

	/**
	 * Get a value from an array, if it exists. @todo This does not belong
	 * here, consider moving to an input filter class.
	 *
	 * @return mixed value if it exsits, null otherwise.
	 */
	public function getValue($values, $key)
	{
		if(isset($values[$key]))
			return $values[$key];

		return null;
	}

	/**
	 * Sets and enables a new list of active modules. Disables modules
	 * that are no longer in use. Commits changes to isc settings.
	 *
	 * @param array $moduleIds A list of new module ids to enable
	 * @return bool Returns true on success
	 */
	private function setModules($moduleIds)
	{
		$updatedModuleIds = array();

		foreach($this->getAllModules() as $module) {
			$moduleId = $module->getId();
			if(in_array($moduleId, $moduleIds)) {
				$module->enable();
				$updatedModuleIds[] = $moduleId;
			}
			else
				$module->disable();
		}

		// set the global new config global so it can be commited by isc settings
		$GLOBALS['ISC_NEW_CFG']['ShoppingComparisonModules'] = implode(',', $updatedModuleIds);
		return getClass('ISC_ADMIN_SETTINGS')->commitSettings();
	}

	/**
	 * Retrieves a list of all module instances.
	 *
	 * @return array
	 */
	public function getAllModules()
	{
		$return  = array();
		$modules = getAvailableModules('shoppingcomparison');

		foreach ($modules as $module) {
			$return[] = $module['object'];
		}

		return $return;
	}

	public function getManageScreenModules()
	{
		$modules = $this->getAllModules();
		foreach($modules as &$module)
			$module->loadManageScreenData();

		return $modules;
	}

	/**
	 * Retrieves a list all of activated module instances.
	 *
	 * @return array
	 */
	public function getActivatedModules()
	{
		$modules = array();

		foreach ($this->getAllModules() as $module) {
			if ($module->checkEnabled()) {
				$modules[] = $module;
			}
		}

		return $modules;
	}

	/**
	 * Retrieves a list of all module instances based on their id.
	 *
	 * @param mixed $moduleIds A numeric string, integer or array of any
	 * of those to filter the modules by
	 * @return array
	 */
	public function getModulesById($moduleIds)
	{
		$modules    = array();
		$allModules = $this->getAllModules();

		foreach ($allModules as $module) {
			if (in_array($module->getId(), (array) $moduleIds)) {
				$modules[] = $module;
			}
		}

		return $modules;
	}
}
