<?php
class ISC_GIFTCERTIFICATE_THEME {
	public $id;
	public $name;
	public $fileSize;
	public $filename;
	public $lastModified;
	private $templateContents;

	public function __construct($id=null, $name=null, $filename=null)
	{
		$this->id = $id;
		$this->name = $name;
		$this->filename = $filename;
		$this->updateFileDetails();
	}

	/**
	 * Loads all gift certificate theme details
	 *
	 * @return array gift certificate theme details
	 */
	static private function loadThemeDetails()
	{
		static $themeDetails = array(
			// id, name, file
			array(1, 'Birthday', 'Birthday.html'),
			array(2, 'Boy', 'Boy.html'),
			array(3, 'Celebration', 'Celebration.html'),
			array(4, 'Christmas', 'Christmas.html'),
			array(5, 'General', 'General.html'),
			array(6, 'Girl', 'Girl.html'),
		);

		return $themeDetails;
	}

	/**
	 * Return a gift certificate object by id
	 *
	 * @param integer the id of the gift certificate
	 *
	 * @return ISC_GIFTCERTIFICATE_THEME the gift certificate, null on failure.
	 */
	static public function findById($id)
	{
		$themeDetails = self::loadThemeDetails();

		foreach($themeDetails as $theme)
		{
			if($theme[0] == $id) {
				return new ISC_GIFTCERTIFICATE_THEME($theme[0], $theme[1], $theme[2]);
			}
		}

		return null;
	}

	/**
	 * Return a gift certificate object by name
	 *
	 * @param string the name of the gift certificate
	 *
	 * @return ISC_GIFTCERTIFICATE_THEME the gift certificate, null on failure
	 */
	static public function findByFilename($name)
	{
		$themeDetails = self::loadThemeDetails();

		foreach($themeDetails as $theme)
		{
			if($theme[2] == $name) {
				return new ISC_GIFTCERTIFICATE_THEME($theme[0], $theme[1], $theme[2]);
			}
		}

		return null;
	}

	/*
	 * Returns all gift certificate themes as ISC_GIFTCERTIFICATE
	 * objects.
	 *
	 * @return array the gift certifcate objects
	 */
	static public function findAll()
	{
		$themeDetails = self::loadThemeDetails();
		$themes = array();

		foreach($themeDetails as $theme){
			$themes[] = new ISC_GIFTCERTIFICATE_THEME($theme[0], $theme[1], $theme[2]);
		}

		return $themes;
	}

	/**
	 * Returns a list of enabled gift certificate themes
	 *
	 * @return array associative array of enabled themes keyed by filename
	 */
	static private function getEnabledGiftCertificateThemes()
	{
		$enabledThemes = explode(',', GetConfig('GiftCertificateThemes'));
		$enabledThemes = array_fill_keys($enabledThemes, 'on');

		return $enabledThemes;
	}

	/**
	 * Saves a list of enabled gift certificate themes
	 *
	 * @param array associative array enabled themes keyed by filename
	 */
	static private function saveEnabledGiftCertificateThemes($themes)
	{
		$themes = array_keys($themes);

		$GLOBALS['ISC_NEW_CFG']['GiftCertificateThemes'] = implode(',', $themes);
		return getClass('ISC_ADMIN_SETTINGS')->commitSettings();
	}

	/**
	 * Returns the gift certificate templates directory
	 *
	 * @return string the directory path
	 */
	static private function getGiftCertificatesDirectory()
	{
		return ISC_BASE_PATH . '/templates/' . GetConfig('GiftCertificateCustomDirectory');
	}

	/**
	 * Returns the gift certificate master templates directory
	 *
	 * @return string the directory path
	 */
	static private function getMasterGiftCertificatesDirectory()
	{
		return ISC_BASE_PATH . '/templates/' . GetConfig('GiftCertificateMasterDirectory');
	}

	/**
	 * Updates fileSize and lastModified values
	 */
	private function updateFileDetails()
	{
		$filePath = $this->getReadFilePath();

		if(!file_exists($filePath)){
			return;
		}

		$this->fileSize = filesize($filePath);
		$this->lastModified = filemtime($filePath);
	}

	/**
	 * Restore the contents of the template from the master template.
	 *
	 * @return bool returns true on success or if the template has not
	 * been customized.
	 **/
	public function restoreFromMaster()
	{
		$templateFile = $this->getFilePath();

		if(!file_exists($templateFile)) {
			return true;
		}

		return unlink($templateFile);
	}

	/**
	 * Returns the contents of the gift certificate's template file
	 *
	 * @return string template contents
	 */
	public function getTemplateContents()
	{
		if($this->templateContents) {
			return $this->templateContents;
		}

		if($filePath = $this->getReadFilePath()) {
			$templateContents = file_get_contents($filePath);
		}
		else {
			// Template files not found
			return "";
		}

		$this->setTemplateContents($templateContents);
		return $this->templateContents;
	}

	/**
	 * Update the template contents of this gift certificate object
	 *
	 * @param string template contents
	 *
	 * @return bool true on success
	 */
	public function setTemplateContents($template)
	{
		$this->templateContents = $template;
		return true;
	}

	/**
	 * Commits the gift certificate's template contents to file.
	 *
	 * @param string (optional) template contents to set and commit
	 *
	 * @param int number of bytes written to file, or false on failure
	 */
	public function saveTemplateContents($template=null)
	{
		if(!empty($template)) {
			$this->setTemplateContents($template);
		}

		$template = $this->getTemplateContents();

		return file_put_contents($this->getFilePath(), $template);
	}

	/**
	 * Returns the path to the theme's template file
	 *
	 * @return string the template's file path or false on failure
	 */
	public function getFilePath()
	{
		return self::getGiftCertificatesDirectory() . '/' . $this->filename;
	}

	/**
	 * Returns the path to the theme's master template file
	 *
	 * @return string the template's file path or false on failure
	 */
	public function getMasterFilePath()
	{
		return self::getMasterGiftCertificatesDirectory() . '/' . $this->filename;
	}

	/**
	 * Returns the file path the template contents should be read from.
	 *
	 * @param string A file path to the custom template file if it exists
	 * otherwise to the master template file. Null if neither file exists.
	 */
	public function getReadFilePath()
	{
		if(file_exists($this->getFilePath())) {
			return $this->getFilePath();
		}

		if(file_exists($this->getMasterFilePath())) {
			return $this->getMasterFilePath();
		}

		return null;
	}

	/**
	 * Is the theme enabled?
	 *
	 * @return boolean true if enabled
	 */
	public function isEnabled()
	{
		$enabledThemes = self::getEnabledGiftCertificateThemes();

		return isset($enabledThemes[$this->filename]);
	}

	/**
	 * Toggles the theme's enabled setting
	 *
	 * @return boolean true on success
	 */
	public function toggleEnabled()
	{
		$enabledThemes = self::getEnabledGiftCertificateThemes();

		if($this->isEnabled()) {
			unset($enabledThemes[$this->filename]);
		}
		else {
			$enabledThemes[$this->filename] = 'on';
		}

		return self::saveEnabledGiftCertificateThemes($enabledThemes);
	}

	/**
	 * Generates the HTML for a gift certificate using this theme.
	 *
	 * @param array gift certificate placeholder data
	 *
	 * @return string the generated gift certificate html
	 */
	public function generateGiftCertificateHTML($certificate)
	{
		$template = TEMPLATE::getInstance();

		if(!isset($GLOBALS['ShopPathNormal'])) {
			$GLOBALS['ShopPathNormal'] = $GLOBALS['ShopPath'];
		}

		// Fetch the store logo or store title
		if(GetConfig('UseAlternateTitle')) {
			$text = GetConfig('AlternateTitle');
		}
		else {
			$text = GetConfig('StoreName');
		}
		$text = explode(" ", $text, 2);
		$text[0] = "<span class=\"Logo1stWord\">".$text[0]."</span>";
		$GLOBALS['LogoText'] = implode(" ", $text);
		$GLOBALS['HeaderLogo'] = $template->GetSnippet("LogoText");

		// Set gift certificate details
		$GLOBALS['CharacterSet']=GetConfig('CharacterSet');
		$GLOBALS['GiftCertificateTo'] = isc_html_escape($certificate['giftcertto']);
		$GLOBALS['GiftCertificateToEmail'] = isc_html_escape($certificate['giftcerttoemail']);
		$GLOBALS['GiftCertificateFrom'] = isc_html_escape($certificate['giftcertfrom']);
		$GLOBALS['GiftCertificateFromEmail'] = isc_html_escape($certificate['giftcertfromemail']);
		$GLOBALS['GiftCertificateAmount'] = CurrencyConvertFormatPrice($certificate['giftcertamount']);
		$GLOBALS['GiftCertificateMessage'] = isc_html_escape($certificate['giftcertmessage']);
		$GLOBALS['GiftCertificateCode'] = isc_html_escape($certificate['giftcertcode']);
		if(isset($certificate['giftcertexpirydate']) && $certificate['giftcertexpirydate'] != 0) {
			$GLOBALS['GiftCertificateExpiryInfo'] = sprintf(GetLang('GiftCertificateExpiresOn'), CDate($certificate['giftcertexpirydate']));
		}
		else {
			$GLOBALS['GiftCertificateExpiryInfo'] = '';
		}

		// Build the html
		$html = $template->ParseTemplate(true, $this->getTemplateContents());

		return $html;
	}
}