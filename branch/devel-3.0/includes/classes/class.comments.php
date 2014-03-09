<?php
/**
* Base comment system module
*
* @author Ray Ward
*/
abstract class ISC_COMMENTS extends ISC_MODULE {
	const PRODUCT_COMMENTS = 1;
	const NEWS_COMMENTS = 2;
	const PAGE_COMMENTS = 4;

	protected $type = 'comments';
	protected $availableCommentTypes = array();

	public function __construct()
	{
		parent::__construct();
	}

	/**
	* Checks if comments are enabled for a given type
	*
	* @param ISC_COMMENTS::PRODUCT_COMMENTS|ISC_COMMENTS::NEWS_COMMENTS|ISC_COMMENTS::PAGE_COMMENTS $commentType The type to check
	* @return bool Returns true if enable for the type, false otherwise
	*/
	public function commentsEnabledForType($commentType)
	{
		if (!in_array($commentType, array(self::PRODUCT_COMMENTS, self::NEWS_COMMENTS, self::PAGE_COMMENTS))) {
			return false;
		}

		$enabledCommentTypes = $this->getEnabledCommentTypes();
		if (empty($enabledCommentTypes)) {
			return false;
		}

		return in_array($commentType, $enabledCommentTypes);
	}

	/**
	* Gets an array of comment types this module supports
	*
	* @return array The available types
	*/
	public function getAvailableCommentTypes()
	{
		return $this->availableCommentTypes;
	}

	/**
	* Gets an array of all the comment types enabled for this module
	*
	* @return array The enabled types
	*/
	public function getEnabledCommentTypes()
	{
		$enabledCommentTypes = $this->GetValue('commenttypes');
		if (empty($enabledCommentTypes)) {
			return array();
		}

		if (!is_array($enabledCommentTypes)) {
			$enabledCommentTypes = array($enabledCommentTypes);
		}

		return $enabledCommentTypes;
	}

	/**
	* Gets an array of all enabled web pages for this module
	*
	* @return array The enabled pages
	*/
	public function getEnabledPages()
	{
		$enabledPages = $this->GetValue('pages');
		if (empty($enabledPages)) {
			return array();
		}

		if (!is_array($enabledPages)) {
			$enabledPages = array($enabledPages);
		}

		return $enabledPages;
	}

	/**
	* Checks if comments are enabled for a specific web page
	*
	* @param mixed $pageId
	* @return bool
	*/
	public function pageEnabled($pageId)
	{
		$enabledPages = $this->getEnabledPages();
		if (empty($enabledPages)) {
			return false;
		}

		return in_array($pageId, $enabledPages);
	}

	/**
	* Retrieves HTML content to display comments for the specified type and object
	*
	* @param ISC_COMMENTS::PRODUCT_COMMENTS|ISC_COMMENTS::NEWS_COMMENTS|ISC_COMMENTS::PAGE_COMMENTS $commentType The type to get comments for
	* @param mixed $objectReference The specific object of a the type to get comments for, such as a product id.
	* @return string The comments HTML
	*/
	abstract public function getCommentsHTMLForType($commentType, $objectReference);

	public function GetCustomVars()
	{
		$variables = array();

		$validCommentTypes = array(
			self::PRODUCT_COMMENTS	=> 'Product reviews',
			self::NEWS_COMMENTS		=> 'News posts',
			self::PAGE_COMMENTS 	=> 'Web pages'
		);

		$commentTypes = array_intersect(array_flip($validCommentTypes), $this->availableCommentTypes);
		if (!empty($commentTypes)) {
			$variables['commenttypes'] = array(
				'name' => 'Enable For',
				'type' => 'dropdown',
				'required' => true,
				'help' => '',
				'options' => $commentTypes,
				'multiselect' => true,
				'multiselectheight' => 6
			);

			if ($this->commentsEnabledForType(self::PAGE_COMMENTS)) {
				$nested = new ISC_NESTEDSET_PAGES();

				// Get a formatted list of all of the pages in the system
				$pages = $nested->getTree(
					array('pageid', 'pagetitle', 'pagevendorid')
				);

				$pageOptions = array();

				foreach ($pages as $page) {
					$pageOptions[$page['pagetitle']] = $page['pageid'];
				}

				$variables['pages'] = array(
					'name' => 'Web Pages',
					'type' => 'dropdown',
					'required' => false,
					'help' => '',
					'options' => $pageOptions,
					'multiselect' => true,
					'multiselectheight' => 5
				);
			}
		}

		$variables += parent::GetCustomVars();

		return $variables;
	}

	/**
	 * Check to see if the module is enabled
	 *
	 * Method will to see if the module is enabled
	 *
	 * @access public
	 * @return bool TRUE if the module is enabled, FALSE if not
	 */
	public function CheckEnabled()
	{
		$commentSystem = GetConfig("CommentSystemModule");
		if ($this->getid() == $commentSystem) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Build the HTML property sheet
	 *
	 * Method will build and return the HTML property sheet for the module
	 *
	 * @access public
	 * @param int $tabId The tab ID that the property sheet will be displayed in
	 * @return string The HMTL property sheet
	 */
	public function getPropertiesSheet($tabId)
	{
		$this->tabId = $tabId;

		$GLOBALS['ShippingJavaScript'] = "";
		$GLOBALS['HelpText'] = $this->gethelptext();
		$GLOBALS['HelpIcon'] = "success";
		$GLOBALS['Properties'] = "";
		$GLOBALS['ShipperId'] = $this->GetName();

		$mod_dir = str_replace($this->type.'_', '', $this->GetId());

		$GLOBALS['HideSelectAllLinks'] = 'display: none';

		// Add the logo
		$image = $this->GetImage();
		if ($image != "") {
			$GLOBALS['HelpTip'] = "";
			$GLOBALS['PropertyBox'] = sprintf("<img style='margin-top:5px' src='%s' />", $this->GetImage());
			$GLOBALS['Properties'] .= Interspire_Template::getInstance('admin')->render('module.property.tpl');
		}

		foreach ($this->GetCustomVars() as $id=>$var) {
			$GLOBALS['PropertyBox'] = "";
			$GLOBALS['PropertyName'] = $var['name'] . ":";
			$GLOBALS['HelpTip'] = "";
			$GLOBALS['FieldId'] = $this->GetId().'_'.$id;

			if($var['type'] == 'dropdown' && isset($var['multiselect']) && $var['multiselect'] == true) {
				$GLOBALS['HideSelectAllLinks'] = '';
			}
			else {
				$GLOBALS['HideSelectAllLinks'] = 'display: none';
			}

			$GLOBALS['PropertyBox'] = $this->_buildformitem($id, $var, false);
			$help_id = rand(1000,100000);

			if ($var['help'] != "") {
				$GLOBALS['HelpTip'] = sprintf("<img onmouseout=\"HideHelp('d%d')\" onmouseover=\"ShowHelp('d%d', '%s', '%s')\" src=\"images/help.gif\" width=\"24\" height=\"16\" border=\"0\"><div style=\"display:none\" id=\"d%d\"></div>", $help_id, $help_id, $var['name'], $var['help'], $help_id);
			}

			$GLOBALS['Properties'] .= Interspire_Template::getInstance('admin')->render('module.property.tpl');
		}

		// First check if the shipping provider is configured.
		$configured = false;
		if(!empty($this->moduleVariables)) {
			$configured = true;
		}

		if (empty($this->_variables)) {
			// Hide the heading of the property sheet if there aren't any properties
			$GLOBALS['HidePropSheet'] = "none";
		}
		else {
			$GLOBALS['HidePropSheet'] = "";
		}


		$sheet = Interspire_Template::getInstance('admin')->render('module.propertysheet.tpl');
		return $sheet;
	}
}