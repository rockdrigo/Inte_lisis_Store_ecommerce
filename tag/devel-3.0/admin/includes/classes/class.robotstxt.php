<?php

class ISC_ADMIN_ROBOTSTXT extends ISC_ADMIN_BASE
{
	/**
	 * @var string The file path to robots.txt file.
	 * @access private
	 */
	private $filePath = '';

	/**
	 * @var string The default content of the robots.txt file, for revert.
	 * @access private
	 */
	private $defaultContent = '';

	/**
	 * @var string The main view URL to redirect to.
	 * @access private
	 */
	private $mainUrl = '';


	/**
	 * Constructor, set some default values.
	*/
	public function __construct()
	{
		parent::__construct();
		$this->filePath = ISC_BASE_PATH.'/robots.txt';

		$default  = "User-agent: *\n";
		$default .= "Disallow: /account.php\n";
		$default .= "Disallow: /cart.php\n";
		$default .= "Disallow: /checkout.php\n";
		$default .= "Disallow: /finishorder.php\n";
		$default .= "Disallow: /login.php\n";
		$default .= "Disallow: /orderstatus.php\n";
		$default .= "Disallow: /postreview.php\n";
		$default .= "Disallow: /productimage.php\n";
		$default .= "Disallow: /productupdates.php\n";
		$default .= "Disallow: /remote.php\n";
		$default .= "Disallow: /search.php\n";
		$default .= "Disallow: /viewfile.php\n";
		$default .= "Disallow: /wishlist.php\n";
		$default .= "Disallow: /admin/\n";
		$default .= "Disallow: /*sort=\n";
		$this->defaultContent = $default;

		$this->mainUrl = 'index.php?ToDo=ViewEditRobotsTxt';
	}

	/**
	 * Route the incoming action to the specified action handler.
	 *
	 * @param string $todo The incoming action.
	 * @return void
	 */
	public function handleToDo($todo)
	{
		// Check permission.
		if (!$this->auth->hasPermission(AUTH_Manage_RobotsTxt)) {
			$this->engine->doHomePage(getLang('Unauthorized'), MSG_ERROR);
			return;
		}

		// Process action routing.
		$todo .= 'Action';
		if (!method_exists($this, $todo) && !method_exists($this, '__call')) {
			header('Location: index.php');
			exit;
		}

		$this->engine->loadLangFile('robotstxt');
		$this->engine->addBreadcrumb(getLang('EditRobotsTxtFile'), $this->mainUrl);
		$this->$todo();
	}

	/**
	 * Load the file content, or default content if file is not found.
	 *
	 * @return string The file content.
	*/
	public function getContent()
	{
		include_once(ISC_BASE_PATH.'/lib/class.file.php');
		$fc = new FileClass();
		$filePath = $this->filePath;
		$content = $fc->readFromFile($filePath);
		if ($content === false) {
			// Init new file with default content.
			$content = $this->defaultContent;
			$res = $fc->writeToFile($content, $filePath);
		}

		return $content;
	}

	/**
	 * Display the interface.
	 *
	 * @return void
	*/
	private function viewEditRobotsTxtAction()
	{
		$this->engine->printHeader();
		$this->template->assign('flashMessages', getFlashMessageBoxes());
		$this->template->assign('fileContent', $this->getContent());
		$this->template->display('robotstxt.tpl');
		$this->engine->printFooter();
	}


	/**
	 * Save new content or revert to default, and set flash message.
	 *
	 * @return void
	*/
	private function saveRobotsTxtAction()
	{
		include_once(ISC_BASE_PATH.'/lib/class.file.php');
		$fc = new FileClass();
		$content = $_POST['robotstxtFileContent'];
		$success = 'RobotsSaveSuccess';

		if (isset($_POST['robotstxtRevertButton'])) {
			// Revert button is clicked instead.
			$content = $this->defaultContent;
			$success = 'RobotsRevertSuccess';
		}

		$res = $fc->writeToFile($content, $this->filePath);
		if ($res == true) {
			FlashMessage(GetLang($success), MSG_SUCCESS, $this->mainUrl);
		} else {
			FlashMessage(GetLang('RobotsSaveError'), MSG_ERROR, $this->mainUrl);
		}
	}

}