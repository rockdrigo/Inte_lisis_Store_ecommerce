<?php
GetLib('class.remoteopener');
class ISC_ADMIN_LAYOUT extends ISC_ADMIN_BASE
{
	/**
	 * @var boolean True if safe_mode is enabled on this server.
	 */
	private $safeMode = false;

	private $NoCurlFopen = false;

	/**
	 * The constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('layout');
	}

	public function HandleToDo($Do)
	{
		$GLOBALS['BreadcrumEntries'] = array(
			GetLang('Home') => 'index.php',
			GetLang('Templates') => 'index.php?ToDo=viewTemplates'
		);

		if(!$GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Manage_Templates)) {
			$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
			exit;
		}
		$GLOBALS['AutoDownload'] = '';

		// If safe mode is enabld, template downloading will not work correctly.
		if(ini_get('safe_mode') == 1 || strtolower(ini_get('safe_mode')) == 'on') {
			$this->safeMode = true;
		}

		switch(isc_strtolower($Do)) {
			case 'savemobiletemplatesettings':
				$this->saveMobileTemplateSettingsAction();
				break;
			case "templatedownload":
				$this->DownloadNewTemplates1();
				break;
			case "templateuploadlogo":
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->UploadLogo();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				exit;
			case "changetemplate":
				$this->ChangeTemplate();
				exit;
			case "templateuploadfavicon":
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->UploadFavicon();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
				exit;
			default:
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
				$this->ManageLayouts();
				$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
		}
	}

	private function saveMobileTemplateSettingsAction()
	{
		$mobileSettingsUrl = 'index.php?ToDo=viewTemplates&forceTab=6';

		$enumSettings = array(
			'enableMobileTemplateDevices' => array(
				'iphone',
				'ipod',
				'pre',
				'android',
				'ipad',
			),
		);

		$newSettings = array(
			'enableMobileTemplate' => false,
			'enableMobileTemplateDevices' => array(),
		);

		if(isset($_POST['enableMobileTemplate'])) {
			$newSettings['enableMobileTemplate'] = true;
		}

		foreach($enumSettings as $setting => $options) {
			if(isset($_POST[$setting]) && is_array($_POST[$setting])) {
				foreach($_POST[$setting] as $option) {
					if(!in_array($option, $options)) {
						echo $option;
						exit;
						flashMessage(getLang('InvalidSetting'.ucfirst($setting)), MSG_ERROR, $mobileSettingsUrl);
					}

					$newSettings[$setting][] = $option;
				}
			}
		}

		$logoTypes = array(
			'mobile_logo' => 'mobileTemplateLogo',
		);
		foreach($logoTypes as $fileName => $type) {
			if(isset($_POST['delete'.ucfirst($type)]) && getConfig($type)) {
				$currentLogoPath = ISC_BASE_PATH.'/'.getConfig('ImageDirectory').'/'.getConfig($type);
				if(file_exists($currentLogoPath) && !unlink($currentLogoPath)) {
					flashMessage(getLang('UnableDelete'.ucfirst($type)), MSG_ERROR, $mobileSettingsUrl);
				}
				$newSettings[$type] = '';
			}

			if(empty($_FILES[$type]) || empty($_FILES[$type]['name'])) {
				continue;
			}

			if($_FILES[$type]['error'] != 0 || $_FILES[$type]['size'] < 1) {
				flashMessage(getLang('UploadLogoNoValidImage2'), MSG_ERROR, $mobileSettingsUrl);
			}

			if(!$this->isValidUploadedLogo($_FILES[$type])) {
				flashMessage(getLang('UploadLogoNoValidImage2'), MSG_ERROR, $mobileSettingsUrl);
			}

			// Upload and store the actual logo
			$fileName .= '.'.getFileExtension($_FILES[$type]['name']);
			if(!move_uploaded_file($_FILES[$type]['tmp_name'], ISC_BASE_PATH.'/'.getConfig('ImageDirectory').'/'.$fileName)) {
				flashMessage(getLang('UploadErrorPath'), MSG_ERROR, $mobileSettingsUrl);
			}

			// Delete existing logo
			if(getConfig($type) && $fileName != getConfig($type)) {
				$currentLogoPath = ISC_BASE_PATH.'/'.getConfig('ImageDirectory').'/'.getConfig($type);
				unlink($currentLogoPath);
			}

			// Save the updated logo in the configuration file
			$newSettings[$type] = $fileName;
		}

		$GLOBALS['ISC_NEW_CFG'] = $newSettings;
		$messages = array();
		if(!getClass('ISC_ADMIN_SETTINGS')->commitSettings($messages)) {
			flashMessage(getLang('MobileTemplateSettingsNotSaved'), MSG_ERROR, $mobileSettingsUrl);
		}

		$this->log->logAdminAction();
		flashMessage(getLang('MobileTemplateSettingsSaved'), MSG_SUCCESS, $mobileSettingsUrl);
	}

	/**
	 * Build a logo based on the specified parameters.
	 *
	 * @param string The name of the logo to use. Pass [template] to build a logo for the current store design.
	 * @param array Array of text for the logo.
	 */
	public function BuildLogo($logoName, $text=array())
	{
		GetLib('logomaker/class.logomaker');
		$logoName = basename($logoName);
		$originalLogoName = $logoName;

		$filePrefix = '';
		if($logoName == "[template]") {
			$logoPath = ISC_BASE_PATH."/templates/".GetConfig('template')."/logo/";
			$configFile = $logoPath.'config.php';
			$logoName = GetConfig('template');
		}
		else {
			$logoPath = ISC_BASE_PATH.'/templates/__logos/';
			$configFile = $logoPath.$logoName . '_config.php';
		}

		if(!file_exists($configFile)) {
			return false;
		}

		require $configFile;

		$className = $logoName .'_logo';
		$tmpClass = new $className;
		$logoImage = $logoName.'.'.$tmpClass->FileType;

		$s = GetClass('ISC_ADMIN_SETTINGS');
		$GLOBALS['ISC_NEW_CFG'] = array();

		if (implode('', $text) == '') {
			$text = array();
		}

		$fields = array();
		foreach($text as $k => $textField) {
			$tmpClass->Text[$k] = $textField;
			$fields[] = $textField;
		}

		// reflect the text that was actually generated on the logo back into the store config, just incase the logo changed it somehow (ie. default values if blanks are provided)
		foreach ($tmpClass->Text as $textKey => $textValue) {
			$fields['ExtraText' . $textKey] = $textValue;
		}

		if(!empty($fields)) {
			$GLOBALS['ISC_NEW_CFG']['LogoFields'] = $fields;
		}

		$logoData = $tmpClass->GenerateLogo();
		ClearTmpLogoImages();

		$imageFile = 'website_logo.'.$tmpClass->FileType;
		file_put_contents(ISC_BASE_PATH . '/'.GetConfig('ImageDirectory').'/'.$imageFile, $logoData);
		$GLOBALS['ISC_NEW_CFG']['StoreLogo'] = $imageFile;
		$GLOBALS['ISC_NEW_CFG']['UsingLogoEditor'] = 1;
		$GLOBALS['ISC_NEW_CFG']['LogoType'] = 'image';
		if($originalLogoName == "[template]") {
			$GLOBALS['ISC_NEW_CFG']['UsingTemplateLogo'] = 1;
		}
		else {
			$GLOBALS['ISC_NEW_CFG']['UsingTemplateLogo'] = 0;
		}
		$s->CommitSettings();

		return $imageFile;
	}

	private function MountTemplates($templateName)
	{
		$clave = substr(GetConfig('tablePrefix'), 0, -1);
		$users = explode(',', GetConfig('UsersMountForTemplates'));
		
		foreach($users as $user){
			$templatedir = ISC_BASE_PATH.'/templates/'.$templateName;
			$userdir = '/home/'.$user.'/tiendas/'.$clave.'/'.$templateName;
			
			$c_mkdir = 'sudo mkdir -p '.$userdir;
			$r_mkdir = system($c_mkdir);
			//print '"'.$c_mkdir.'" result: "'.$r_mkdir.'"<br/>';
			
			$c_mount = 'sudo mount --bind '.$templatedir.' '.$userdir;
			$r_mount = system($c_mount);
			//print '"'.$c_mount.'" result: "'.$r_mount.'"<br/>';
			
			$c_chmod = 'sudo chmod -R a+w '.$templatedir;
			$r_chmod = system($c_mount);
		}
	}
	
	private function ChangeTemplate()
	{
		GetLib('class.file');

		$settings = GetClass('ISC_ADMIN_SETTINGS');
		$GLOBALS['ISC_NEW_CFG']['template'] = Interspire_String::filterAlphaNumOnly($_REQUEST['template']);

		$StylePath = ISC_BASE_PATH . "/templates/" .Interspire_String::filterAlphaNumOnly($_REQUEST['template']) .'/Styles';
		$color = isc_strtolower(Interspire_String::filterAlphaNumOnly($_REQUEST['color']));
		if(file_exists($StylePath."/".$color.".css")) {
			$GLOBALS['ISC_NEW_CFG']['SiteColor'] = $color;
		}

		if(file_exists(ISC_BASE_PATH . '/templates/'. Interspire_String::filterAlphaNumOnly($_REQUEST['template'])  . '/config.php')) {
			include(ISC_BASE_PATH . '/templates/'.Interspire_String::filterAlphaNumOnly($_REQUEST['template'])  . '/config.php');
		}

		if($color != '') {
			$GLOBALS['ISC_NEW_CFG']['SiteColor'] = $color;
		}

		$settings->CommitSettings();

		// If we're currently using a logo template, then we need to rebuild it
		if(GetConfig('UsingTemplateLogo') && GetConfig('UsingLogoEditor')) {
			if(!$this->BuildLogo('[template]', GetConfig('LogoFields'))) {
				$GLOBALS['ISC_NEW_CFG'] = array(
					'UsingTemplateLogo' => 0,
					'UsingLogoEditor' => 0,
					'LogoType' => 'text'
				);
				$settings->CommitSettings();
			}
		}

		$this->MountTemplates($_REQUEST['template']);
		
		// Log this action
		$GLOBALS['ISC_CLASS_LOG']->LogAdminAction(GetConfig('template'), GetConfig('SiteColor'));

		FlashMessage(sprintf(GetLang('TemplateSelected'), ucfirst($_REQUEST['template'])), MSG_SUCCESS, 'index.php?ToDo=viewTemplates');
	}

	public function LoadDownloadTemplates()
	{
		if((isset($_SESSION['ForceDownloadTemplates']) && $_SESSION['ForceDownloadTemplates'] == true) || (isset($_REQUEST['forceCheck']) && $_REQUEST['forceCheck'] == '1')) {
			$_SESSION['ForceDownloadTemplates'] = false;
			unset($_SESSION['ForceDownloadTemplates']);
			$GLOBALS['AutoDownload'] .= "\nwindow.setTimeout('CheckNewTemplates()', 200);";
		}
	}

	public function IsValidUploadedLogo($logo)
	{
		// If we've just uploaded an image, we need to perform a bit of additional validation
		// to ensure it's not someone uploading bogus images.
		$imageExtensions = array(
			'gif',
			'png',
			'jpg',
			'jpeg',
			'jpe',
		);
		$extension = GetFileExtension($logo['name']);
		if(!in_array(isc_strtolower($extension), $imageExtensions)) {
			return false;
		}

		// Check a list of known MIME types to establish the type of image we're uploading
		switch(isc_strtolower($logo['type'])) {
			case 'image/gif':
				$imageType = IMAGETYPE_GIF;
				break;
			case 'image/jpg':
			case 'image/x-jpeg':
			case 'image/x-jpg':
			case 'image/jpeg':
			case 'image/pjpeg':
			case 'image/jpg':
				$imageType = IMAGETYPE_JPEG;
				break;
			case 'image/png':
			case 'image/x-png':
				$imageType = IMAGETYPE_PNG;
				break;
			case 'image/bmp':
				$imageType = IMAGETYPE_BMP;
				break;
			case 'image/tiff':
				$imageType = IMAGETYPE_TIFF_II;
				break;
			default:
				$imageType = 0;
		}

		$imageDimensions = getimagesize($logo['tmp_name']);
		if(!is_array($imageDimensions) || $imageDimensions[2] != $imageType) {
			return false;
		}
		return true;
	}

	private function UploadLogo()
	{

		$uploadLogoUrl = 'index.php?ToDo=viewTemplates&forceTab=2';

		if($_FILES['LogoFile']['error'] != 0 || $_FILES['LogoFile']['size'] < 1) {
			FlashMessage(GetLang('UploadLogoNoValidImage2'), MSG_ERROR, $uploadLogoUrl);
			exit;
		}

		if(!$this->IsValidUploadedLogo($_FILES['LogoFile'])) {
			FlashMessage(GetLang('UploadLogoNoValidImage2'), MSG_ERROR, $uploadLogoUrl);
			die();
		}

		$_FILES['LogoFile']['name'] = basename($_FILES['LogoFile']['name']);

		// Upload and store the actual logo
		if(!move_uploaded_file($_FILES['LogoFile']['tmp_name'], ISC_BASE_PATH . '/' . GetConfig('ImageDirectory') . '/' . basename($_FILES['LogoFile']['name']))) {
			FlashMessage(GetLang('UploadErrorPath'), MSG_ERROR, $uploadLogoUrl);
			die();
		}

		// Save the updated logo in the configuration file
		$GLOBALS['ISC_NEW_CFG']['StoreLogo'] = $_FILES['LogoFile']['name'];
		$GLOBALS['ISC_NEW_CFG']['UsingTemplateLogo'] = 0;
		$GLOBALS['ISC_NEW_CFG']['LogoType'] = 'image';
		$GLOBALS['ISC_NEW_CFG']['UsingLogoEditor'] = 0;
		$settings = GetClass('ISC_ADMIN_SETTINGS');

		if($settings->CommitSettings()) {
			isc_chmod(ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/'.$GLOBALS['ISC_NEW_CFG']['StoreLogo'], ISC_WRITEABLE_FILE_PERM);
			FlashMessage(GetLang('LogoUploadSuccess'), MSG_SUCCESS, $uploadLogoUrl);
		}
		else {
			FlashMessage(GetLang('UploadWorkedConfigError'), MSG_ERROR, $uploadLogoUrl);
		}
	}

	/**
	 * Get a list of logos currently installed and return them as a sorted array.
	 *
	 * @return array Array of logos
	 */
	public function GetLogoList($forceType='')
	{
		$directories = array(
			'template' => ISC_BASE_PATH."/templates/".GetConfig('template')."/__logos"
		);

		$logos = array();

		foreach($directories as $type => $dir) {
			// If we only want logos of a particular type, then skip whar we don't want
			if($forceType != '' && $type != $forceType) {
				continue;
			}

			if(!is_dir($dir)) {
				continue;
			}

			$files = scandir($dir);
			foreach($files as $file) {
				if($file{0} == "." || $file{0} == "0" || $file == "CVS") {
					continue;
				}
				if(preg_match('#_config\.php$#', $file)) {
					$file = preg_replace('#_config\.php$#', "", $file);
					$id = strtolower($type."-".$file);
					$logos[$id] = array(
						'type' => $type,
						'name' => $file
					);
				}
			}
		}

		if(!empty($logos)) {
			// Now sort the actual logos
			uksort($logos, array($this, "CustomLogoSort"));
		}
		return $logos;
	}

	public function CustomLogoSort($a, $b)
	{
		return strcasecmp($a['name'], $b['name']);
	}

	public function LoadLogoList()
	{
		GetLib('logomaker/class.logomaker');

		$logoDirectory = ISC_BASE_PATH."/templates/".GetConfig('template')."/__logos";
		$logoURL = GetConfig('AppPath')."/templates/".GetConfig('template')."/__logos";

		if(!is_dir($logoDirectory)) {
			return '';
		}

		$logos = scandir($logoDirectory);
		$logoList = '';
		foreach($logos as $file) {
			if(!preg_match('#_config\.php$#', $file)) {
				continue;
			}
			$logo = str_replace('_config.php', '', $file);
			require $logoDirectory."/".$file;
			$className = $logo.'_logo';
			if(!class_exists($className)) {
				continue;
			}
			$logoClass = new $className;
			$logoImage = $logo.'.'.$logoClass->FileType;

			if(!file_exists(ISC_BASE_PATH."/cache/logos/".$logoImage)) {
				$logoData = $logoClass->GenerateLogo();
				file_put_contents(ISC_BASE_PATH."/cache/logos/".$logoImage, $logoData);
			}
			$logoList .= '<a href="javascript:SelectLogo(\''.$logo.'\', \''.$logoImage.'\', '.(int)$logoClass->TextFieldCount.')"><img src="../cache/logos/'.$logoImage.'" style="border:1px solid #EBEBEB; margin: 10px; "></a>';
		}
		return $logoList;
	}

	public function GetTemplateLogo($template="")
	{
		if($template == '') {
			$template = GetConfig('template');
		}
		$logoDirectory = ISC_BASE_PATH."/templates/".$template."/logo/";
		if(!is_dir($logoDirectory) || !file_exists($logoDirectory."config.php")) {
			return false;
		}
		$logo = $template;
		require $logoDirectory."config.php";
		$className = $logo.'_logo';
		if(!class_exists($className)) {
			return false;
		}
		$logoClass = new $className;
		$logoImage = $template.'.'.$logo.'.'.$logoClass->FileType;

		if(!file_exists(ISC_BASE_PATH."/cache/logos/".$logoImage)) {
			$logoData = $logoClass->GenerateLogo();
			file_put_contents(ISC_BASE_PATH."/cache/logos/".$logoImage, $logoData);
		}

		return array(
			"fields" => $logoClass->TextFieldCount,
			"preview" => $logoImage
		);
	}

	public function LoadLogoTab()
	{
		$GLOBALS['AlernateTitle'] = GetConfig('StoreName');

		$logoType = GetConfig('UsingLogoEditor');
		if($logoType == 1) {
			$usingEditor = 1;
		}
		else {
			$usingEditor = 0;
		}
		$forceWebsiteTitle = GetConfig('ForceWebsiteTitleText');

		$GLOBALS['SelectALogoHide'] = 'none';

		if($usingEditor && GetConfig('LogoType') != 'text') {
			if(GetConfig('UsingTemplateLogo')) {
				$GLOBALS['TemplateLogoChecked'] = "checked=\"checked\"";
				$GLOBALS['LogoTypeSelected'] = "template";
			}
			else {
				$GLOBALS['SelectALogoHide'] = '';
				$GLOBALS['CustomLogoChecked'] = "checked=\"checked\"";
				$GLOBALS['LogoTypeSelected'] = "generic";
			}
			$GLOBALS['LogoImageSelected'] = 'create';
		}
		else if(!$usingEditor && GetConfig('LogoType') != 'text') {
			$GLOBALS['LogoImageSelected'] = 'upload';
		}
		else {
			$altTitle = GetConfig('AlternateTitle');
			if(!empty($altTitle)) {
				$GLOBALS['AlternateChecked'] = 'checked="checked"';
				$GLOBALS['AlternateTitle'] = GetConfig('AlternateTitle');
			}
			else {
				$GLOBALS['AlternateNotChecked'] = 'checked="checked"';
			}
			$GLOBALS['LogoImageSelected'] = "none";
		}

		$GLOBALS['GenericLogos'] = $this->LoadLogoList('generic');
		if(!$GLOBALS['GenericLogos']) {
			$GLOBALS['HideGenericLogoList'] = 'none';
		}
		else {
			$GLOBALS['HideNoLogosMessage'] = 'none';
		}

		$GLOBALS['HideNoTTFError'] = 'none';
		if(!function_exists('imagettftext')) {
			$GLOBALS['HideNoTTFError'] = '';
			$GLOBALS['HideLogoOptionsNoFont'] = "none";
		}
		else {
			// Get the logo for this current template if we have one
			$templateLogo = $this->GetTemplateLogo();
			if(is_array($templateLogo)) {
				$GLOBALS['TemplateLogoFile'] = $templateLogo['preview'];
				$GLOBALS['TemplateLogoFileNumFields'] = $templateLogo['fields'];
			}
			else {
				$GLOBALS['DisableTemplateOption'] = 'disabled="disabled"';
				$GLOBALS['DisableTemplateOptionClass'] = "Disabled";
			}

			$text = GetConfig('LogoFields');

			if(is_array($text)) {
				foreach($text as $k => $v) {
					$text[$k] = addslashes($v);
				}
				$GLOBALS['TextArray'] = "'".implode("', '", $text). "'";
			}
			else {
				$GLOBALS['TextArray'] = "'".GetConfig('StoreName')."'";
			}
		}

		if($GLOBALS['LogoImageSelected'] != 'none' && GetConfig('StoreLogo')) {
			$GLOBALS['CurrentLogo'] = GetConfig('AppPath') . '/' . GetConfig('ImageDirectory'). '/'. GetConfig('StoreLogo');
			$GLOBALS['HideCurrentLogo'] = '';
		}
		else {
			$GLOBALS['CurrentLogo'] = GetConfig('AppPath') . '/admin/images/nologo.gif';
			$GLOBALS['HideCurrentLogo'] = '';
		}

		$GLOBALS['LogoTab'] = $this->template->render('layout.logo.form.tpl');
	}

	public function DownloadNewTemplates1()
	{
		if(!isset($_REQUEST['template']) || empty($_REQUEST['color'])) {
			exit;
		}

		// Get the information about this template from the remote server
		$url = $this->BuildTemplateURL($GLOBALS['ISC_CFG']['TemplateInfoURL'], array(
			"template" => urlencode($_REQUEST['template'])
		));
		$response = PostToRemoteFileAndGetResponse($url);

		// A remote connection couldn't be established
		if($response === null) {
			exit;
		}

		$templateXML = @simplexml_load_string($response);
		if(!is_object($templateXML)) {
			exit;
		}

		if(isset($templateXML->error) && $templateXML->error == "invalid") {
			exit;
		}

		$templateName = strval($templateXML->name);
		$GLOBALS['DownloadPleaseWait'] = sprintf(GetLang('DownloadPleaseWait'), $templateName);
		$GLOBALS['TemplateColor'] = urlencode($_REQUEST['color']);
		$this->LoadTemplateDownloader($_REQUEST['template'], $templateName);
		
		
	}

	public function LoadTemplateDownloader($templateId, $templateName)
	{
		if($this->safeMode) {
			$urls = $this->GenerateTemplateDownloadURLs($templateId);
			echo "<script type='text/javascript'>";
			echo "tb_remove();";
			echo "window.location = '".$urls['streamUrl']."';";
			echo "</script>";
			exit;
		}
		$GLOBALS['TemplateId'] = $templateId;
		$GLOBALS['TemplateName'] = $templateName;
		$this->template->display('downloadernew.loading.tpl');
		exit;
	}

	/**
	 * Generate the URLs to download a particular template.
	 *
	 * @param string The name of the template we wish to generate the download URLs for.
	 * @param string The license key if there is one.
	 * @return array An array containing the download URL and the verification URL.
	 */
	private function GenerateTemplateDownloadURLs($template, $key="")
	{
		$urlBits = array(
			'template' => urlencode($template)
		);
		$host = '';
		if(!empty($_SERVER['HTTP_HOST'])) {
			$host = base64_encode($_SERVER['HTTP_HOST']);
		}
		if(isset($key)) {
			$urlBits['key'] = urlencode($key);
			$urlBits['host'] = $host;
		}
		else {
			$templateKeys = GetConfig('TemplateKeys');
			if(is_array($templateKeys) && isset($templateKeys[$templateId])) {
				$key = $templateKeys[$templateId];
				$urlBits['key'] = urlencode($key);
				$urlBits['host'] = $host;
			}
		}

		$url = $this->BuildTemplateURL($GLOBALS['ISC_CFG']['TemplateVerifyURL'], $urlBits);
		$streamUrl = $this->BuildTemplateURL($GLOBALS['ISC_CFG']['TemplateStreamURL'], $urlBits);
		return array(
			'url'		=> $url,
			'streamUrl'	=> $streamUrl
		);
	}

	public function DownloadNewTemplates2()
	{
		if(!isset($_REQUEST['template'])) {
			$GLOBALS['ErrorMessage'] = GetLang('InvalidTemplate');
			return false;
		}

		// Include the File_Archive package
		GetLib('class.zip');

		GetLib('class.file');
		$FileClass = new FileClass();

		$key = '';
		if(isset($_REQUEST['key'])) {
			$key = $_REQUEST['key'];
		}

		$downloadUrls = $this->GenerateTemplateDownloadURLs($_REQUEST['template'], $key);
		$url = $downloadUrls['url'];
		$streamUrl = $downloadUrls['streamUrl'];

		// Get the information about this template from the remote server
		$response = PostToRemoteFileAndGetResponse($url);

		// A remote connection couldn't be established
		if($response === null) {
			$GLOBALS['ErrorMessage'] = GetLang('InvalidTemplate');
			return false;
		}

		$templateXML = @simplexml_load_string($response);
		if(!is_object($templateXML)) {
			$GLOBALS['ErrorMessage'] = GetLang('InvalidTemplate');
			return false;
		}

		if(isset($templateXML->error)) {
			switch(strval($templateXML->error)) {
				case "invalid":
					$GLOBALS['ErrorMessage'] = GetLang('InvalidKey');
					return false;
				case "invalid_domain":
					$GLOBALS['ErrorMessage'] = GetLang('InvalidKeyDomain');
					return false;
				case "invalid_tpl":
					$GLOBALS['ErrorMessage'] = GetLang('InvalidKeyTemplate');
					return false;
				case "invalid_tpl2":
					$GLOBALS['ErrorMessage'] = GetLang('InvalidKeyTemplate2');
					return false;
				default:
					$GLOBALS['ErrorMessage'] = GetLang('InvalidTemplate');
					return false;
			}
		}

		// If safemode is enabled, simply redirect to the stream URL to download the ZIP
		if($this->safeMode) {
			header("Location: ".$streamUrl);
			exit;
		}

		// Template is valid, so download the zip file
		$data = PostToRemoteFileAndGetResponse($streamUrl, '', false);
		if($data === null) {
			$GLOBALS['ErrorMessage'] = GetLang('InvalidTemplate');
			return false;
		}

		$tmp_dir = ISC_BASE_PATH . "/cache/";
		$filename = $this->_GenRandFileName();
		$tmpFile = $tmp_dir . $filename . ".zip";

		// If we can't write to the temporary directory, show a message
		if(!CheckDirWritable($tmp_dir)) {
			$GLOBALS['ErrorMessage'] = GetLang('TempDirWriteError');
			return false;
		}

		// Cannot write the temporary file
		if(!$fp = @fopen($tmpFile, "wb+")) {
			$GLOBALS['ErrorMessage'] = GetLang("TempDirWriteError");
			return false;
		}

		// Write the contents
		if(!@fwrite($fp, $data)) {
			$GLOBALS['ErrorMessage'] = GetLang("TempDirWriteError");
			return false;
		}

		@fclose($fp);

		$templateName = strval($templateXML->name);

		// If this is an update for the template, remove the old one first
		$templatePath = ISC_BASE_PATH."/templates/".basename($_REQUEST['template']);
		if(is_dir($templatePath)) {
			$FileClass->SetDir('');
			$deleted = $FileClass->DeleteDir($templatePath, true);
			// Couldn't remove old template first
			if(!$deleted) {
				$GLOBALS['ErrorMessage'] = sprintf(GetLang("TemplateUnlinkError"), $templateName);
				return false;
			}
		}

		// Extract the new template
		$archive = new PclZip($tmpFile);
		if($archive->extract(PCLZIP_OPT_PATH, ISC_BASE_PATH."/templates") === 0) {
			$GLOBALS['ErrorMessage'] = GetLang('TemplateDirWriteError');
			return false;
		}

		// Remove the temporary file
		@unlink($tmpFile);

		// Set the file permissions on the new template
		$file = new FileClass;
		$file->SetLoadDir(ISC_BASE_PATH."/templates");
		$file->ChangeMode(basename($templatePath), ISC_TEMPLATE_DIR_PERM, ISC_TEMPLATE_FILE_PERM, true);
		$file->CloseHandle();
		return true;
	}

	public function getInstalledTemplates()
	{
		$templatePath = ISC_BASE_PATH."/templates";
		GetLib('class.file');
		$templateCount = 0;

		$templates = scandir($templatePath);
		natcasesort($templates);

		foreach($templates as $k => $template) {
			if ($template == "." || $template == ".." || $template == "CVS" || $template == ".svn" || $template == 'blank.dat' || $template{0} == '_') {
				continue;
			}
			$previewPath = $templatePath . '/' . $template . '/Previews';
			if(!is_dir($previewPath)) {
				continue;
			}

			$previews = new FileClass;
			$previews->SetLoadDir($previewPath);

			$doneColors = array();
			while(($preview = $previews->NextFile()) !== false) {
				if(substr($preview,-4) != ".jpg" && substr($preview,-5) != ".jpeg" && substr($preview,-4) != ".gif") {
					continue;
				}
				$templateColor = ucfirst(str_replace(array(".jpg",".jpeg","gif","fixed_","stretched_"), "", strtolower($preview)));
				if(in_array($templateColor, $doneColors)) {
					continue;
				}
				$doneColors[] = $templateColor;

				$templateList[] = array(
					'template' => $template,
					'templateName' => ucfirst($template),
					'templateColor' => ucfirst($templateColor),
					'preview' => $preview,
					'installed' => true,
					'previewFull' => getConfig('ShopPathNormal').'/templates/'.$template.'/Previews/'.$preview,
					'preview' => 'thumb.php?tpl='.$template.'&color='.$preview,
				);
				++$templateCount;
			}
		}

		return $templateList;
	}

	public function getDownloadableTemplates()
	{
		if(getConfig('DisableTemplateDownloading')) {
			return '';
		}

		// Get the list of currently installed templates
		$existingTemplates = $this->_GetTemplateList();
		$numExisting = count($existingTemplates);

		$templateCacheFile = ISC_CACHE_DIRECTORY . '/remote_templates.xml';
		$cacheContent = '';
		if(!file_exists($templateCacheFile) || filemtime($templateCacheFile) < time() - 86400) {
			// Fetch the list of available templates for this version
			$url = $this->BuildTemplateURL($GLOBALS['ISC_CFG']['TemplateURL'], array(
				"version" => PRODUCT_VERSION_CODE
			));

			// Send off a request to the remote server to get a list of available logos
			$templateXML = PostToRemoteFileAndGetResponse($url);

			// A remote connection couldn't be established
			if($templateXML === null || $templateXML === false) {
				return false;
			}

			file_put_contents($templateCacheFile, $templateXML);
			isc_chmod($templateCacheFile, ISC_WRITEABLE_FILE_PERM);
		}
		else {
			$templateXML = file_get_contents($templateCacheFile);
		}

		try {
			$xml = new SimpleXMLElement($templateXML);
		}
		catch(Exception $e) {
			return false;
		}

		if(empty($xml->template)) {
			return false;
		}

		$templateList = array();
		foreach($xml->template as $template) {
			$templateId = (string)$template->id;
			$templateName = (string)$template->name;

			// Don't show this template if we already have it installed
			if(in_array($templateId, $existingTemplates)) {
				continue;
			}

			foreach($template->colors->color as $color) {
				$templateList[] = array(
					'template' => $templateId,
					'templateName' => $templateName,
					'templateColor' => (string)$color->name,
					'preview' => (string)$color->preview,
					'previewFull' => (string)$color->previewFull,
					'installed' => false
				);
			}
		}

		return $templateList;
	}

	private function sortTemplateCallback($a, $b)
	{
		return strcasecmp($a['templateName'].' - '.$a['templateColor'], $b['template'].' - '.$b['templateColor']);
	}

	public function LoadChooseTemplateTab()
	{
		$installedTemplates = $this->getInstalledTemplates();
		$downloadableTemplates = $this->getDownloadableTemplates();
		if($downloadableTemplates === false) {
			flashMessage(getLang('UnableRetrieveDownloadableTemplates'), MSG_ERROR);
		}

		$templateList = $installedTemplates;
		if(is_array($downloadableTemplates)) {
			$templateList = array_merge($templateList, $downloadableTemplates);
		}
		uasort($templateList, array($this, 'sortTemplateCallback'));

		$GLOBALS['TemplateListMap'] = '';
		foreach($templateList as $template) {
			if(GetConfig('template') == $template['template'] && strtolower($template['templateColor']) == GetConfig('SiteColor')) {
				$GLOBALS['CurrentTemplateImage'] = $template['preview'];
			}

			if($template['installed']) {
				$GLOBALS['TemplateInstalledClass'] = '';
			}
			else {
				$GLOBALS['TemplateInstalledClass'] = 'Installable';
			}

			if(GetConfig('template') == $template['template'] && strtolower($template['templateColor']) == GetConfig('SiteColor')) {
				$GLOBALS['CurrentTemplateClass'] = 'TemplateBoxOn';
			}
			else {
				$GLOBALS['CurrentTemplateClass'] = '';
			}

			if($_SERVER['HTTPS'] == 'on') {
				$template['preview'] = str_replace('http://', 'https://', $template['preview']);
				$template['previewFull'] = str_replace('http://', 'https://', $template['previewFull']);
			}

			$GLOBALS['TemplateId'] = $template['template'];
			$GLOBALS['TemplateName'] = $template['templateName'];
			$GLOBALS['TemplateColor'] = $template['templateColor'];
			$GLOBALS['TemplatePreviewThumb'] = $template['preview'];
			$GLOBALS['TemplatePreviewFull'] = $template['previewFull'];

			$GLOBALS['TemplateListMap'] .= $this->template->render('layout.choosetemplate.row.tpl');
		}
	}

	public function _GetTemplateList()
	{
		GetLib('class.file');

		// Get a list of templates and return them as a sorted array
		$dir = ISC_BASE_PATH . "/templates";
		$arrTemplates = array();

		if (is_dir($dir)) {
			$fileHandle = new FileClass;
			if ($fileHandle->SetLoadDir($dir)) {
				while (($file = $fileHandle->NextFolder()) !== false) {
					if ($file != "." && $file != ".." && $file != "CVS" && $file != ".svn" && $file != 'blank.dat' && $file{0} != '_') {
						// These are the template categories. We will create
						// an array for each of them
						$arrTemplates[] = $file;
						sort($arrTemplates);
					}
				}
				$fileHandle->CloseHandle();
			}

		}
		ksort($arrTemplates);
		return $arrTemplates;
	}

	public function ManageLayouts($MsgDesc = "", $MsgStatus = "", $template = "")
	{
		$output = '';

		if(isset($_REQUEST['forceTab'])) {
			$GLOBALS['ForceTab'] = (int)$_REQUEST['forceTab'];
		}

		$opener = new connect_remote();
		if ($opener->CanOpen()) {
			$GLOBALS['FopenSupport'] = true;
		} else {
			$GLOBALS['FopenSupport'] = false;
		}

		$GLOBALS['CurrentTemplateName']  = GetConfig('template');
		$GLOBALS['CurrentTemplateNameProper']  = ucfirst(GetConfig('template'));
		$GLOBALS['CurrentTemplateColor'] = GetConfig('SiteColor');
		$GLOBALS['StoreLogo'] = GetConfig('StoreLogo');
		$GLOBALS['siteName']  = GetConfig('StoreName');

		$this->LoadChooseTemplateTab();
		$this->LoadDownloadTemplates();
		$this->LoadLogoTab();

		if(file_exists(ISC_BASE_PATH . '/templates/'. GetConfig('template') . '/config.php')) {
			include(ISC_BASE_PATH . '/templates/'. GetConfig('template') . '/config.php');
			if(isset($GLOBALS['TPL_CFG']['GenerateLogo']) && $GLOBALS['TPL_CFG']['GenerateLogo'] === true) {
				$GLOBALS['CurrentTemplateHasLogoOption'] = 'true';
			}
			else {
				$GLOBALS['CurrentTemplateHasLogoOption'] = 'false';
			}
		}

		if(GetConfig('DisableTemplateDownloading')) {
			$GLOBALS['HideDownloadTab'] = 'none';
		}

		$GLOBALS['TemplateVersion'] = '1.0';
		if(isset($GLOBALS['TPL_CFG']['Version'])) {
			$GLOBALS['TemplateVersion'] = $GLOBALS['TPL_CFG']['Version'];
		}

		$GLOBALS['LayoutIntro'] = GetLang('TemplateIntro');

		$GLOBALS['DesignModeToken'] = isc_html_escape($_COOKIE['STORESUITE_CP_TOKEN']);

		$GLOBALS['Message'] = '';

		if ($MsgDesc != "") {
			$GLOBALS['Message'] = MessageBox($MsgDesc, $MsgStatus);
		}

		$flashMessages = GetFlashMessages();
		if(is_array($flashMessages)) {
			foreach($flashMessages as $flashMessage) {
				$GLOBALS['Message'] .= MessageBox($flashMessage['message'], $flashMessage['type']);
			}
		}

		// Get the getting started box if we need to
		$GLOBALS['GettingStartedStep'] = '';
		if(empty($GLOBALS['Message']) && (isset($_GET['wizard']) && $_GET['wizard']==1) && !in_array('design', GetConfig('GettingStartedCompleted')) && !GetConfig('DisableGettingStarted')) {
			$GLOBALS['GettingStartedTitle'] = GetLang('DesignYourStore');
			$GLOBALS['GettingStartedContent'] = GetLang('DesignYourStoreDesc');
			$GLOBALS['GettingStartedStep'] = $this->template->render('Snippets/GettingStartedModal.html');
		}

		// Mark the design step as complete
		GetClass('ISC_ADMIN_ENGINE')->MarkGettingStartedComplete('design');

		if(!function_exists("gzdeflate")) {
			// No zlib - they can't download templates automatically
			$GLOBALS['HideDownloadMessage'] = "none";
			$GLOBALS['NoZLibMessage'] = MessageBox(GetLang('NoZLibInstalled'), MSG_ERROR);
		}
		else {
			// They have zlib - hide the zlib error message
			$GLOBALS['HideNoZLib'] = "none";
		}

		if(!$this->safeMode) {
			$GLOBALS['HideSafeModeMessage'] = 'display: none';
		}

		// Mobile template settings tab
		$validSettings = array(
			'enableMobileTemplate',
			'enableMobileTemplateDevices',
			'mobileTemplateLogo',
		);
		$mobileSettings = array();
		foreach($validSettings as $setting) {
			$mobileSettings[$setting] = getConfig($setting);
		}
		$this->template->assign('mobileSettings', $mobileSettings);

		require_once ISC_BASE_PATH.'/lib/templates/template.php';
		$phoneTemplateConfig = TEMPLATE::getTemplateConfiguration('__mobile');
		$this->template->assign('phoneLogoDimensions', array(
			'width' => $phoneTemplateConfig['LogoWidth'],
			'height' => $phoneTemplateConfig['LogoHeight']
		));

		// Gift certificates tab
		if(GetConfig('EnableGiftCertificates')) {
			$GLOBALS['GiftCertificateThemes'] = ISC_GIFTCERTIFICATE_THEME::findAll();
		}

		// Load the email templates
		$GLOBALS['EmailTemplatesGrid'] = $this->GetEmailTemplateRows();

		$GLOBALS['TemplatesOrderCustomURL'] = GetConfig('TemplatesOrderCustomURL');

		// Load a temporary editor to use for editing email templates
		$wysiwygOptions = array(
			'id' => 'temp_email_editor',
			'delayLoad' => true
		);
		$GLOBALS['TemporaryEditor'] = GetClass('ISC_ADMIN_EDITOR')->GetWysiwygEditor($wysiwygOptions);

		$GLOBALS['Favicon'] = GetConfig('ShopPath') . '/' . GetConfig('ImageDirectory') . '/' . GetConfig('Favicon');

		$this->template->display('layout.manage.tpl');
	}

	private function _GenRandFileName()
	{
		$output = "";

		for ($i = 0; $i < rand(8, 15); $i++) {
			$output .= chr(rand(65, 90));
		}
		return $output;
	}

	/**
	 * Return a list of the directories that should be editable in the
	 * "Email Templates" editor. The last directory of the array defines
	 * the directory where the actual changes should be saved to.
	 *
	 * @return array Array of directories for the editor.
	 */
	public function GetEmailTemplateDirectories()
	{
		$templateDirectories = array(
			ISC_EMAIL_TEMPLATES_DIRECTORY,
		);
		return $templateDirectories;
	}

	/**
	 * Generate the HTML for the list of templates in a specific directory
	 * for the email template editor.
	 *
	 * @param string The relative directory path to fetch the files in (relative to base template directory)
	 * @param string The ID of the parent row for the templates to sit under (nested directories)
	 * @return string The generated HTML.
	 */
	public function GetEmailTemplateRows($directory='', $parentRow='')
	{
		$templateDirectories = $this->GetEmailTemplateDirectories();
		$validPath = false;
		foreach($templateDirectories as $fullPath) {
			$root = realpath($fullPath.'/'.$directory);

			//replace back slashes with forward slashes in the paths, so the strpos function would also work in windows server
			$root = str_replace('\\', '/',$root);
			$fullPath  = str_replace('\\', '/',$fullPath);

			if($root && strpos($root, $fullPath) !== false && is_dir($root)) {
				$validPath = true;
				break;
			}
		}

		// Path doesn't exist at all!
		if(!$validPath) {
			return '';
		}

		// Fetch all of the files in each directory
		$files = array();
		foreach($templateDirectories as $type => $path) {
			if(!is_dir($path)) {
				continue;
			}
			$directoryFiles = scandir($path.'/'.$directory);
			$directoryFiles = array_fill_keys($directoryFiles, $type);
			$files = array_merge($files, $directoryFiles);
		}

		if(empty($files)) {
			return '';
		}

		$output = '';
		foreach($files as $file => $type) {
			// Skip hidden and special directories
			if(substr($file, 0, 1) == '.') {
				continue;
			}
			$filePath = $templateDirectories[$type].'/'.$directory.'/'.$file;
			$GLOBALS['FileName'] = isc_html_escape($file);
			$relativePath = trim($directory.'/'.$file, '/');
			$GLOBALS['RelativePath'] = $relativePath;
			$level = substr_count($relativePath, '/') * 30;
			if($level > 0) {
				$GLOBALS['NestingIndent'] = 'padding-left: '.$level.'px';
			}
			else {
				$GLOBALS['NestingIndent'] = '';
			}
			$GLOBALS['ParentClass'] = '';
			if($parentRow) {
				$GLOBALS['ParentClass'] = 'Child_'.isc_html_escape($parentRow);
			}

			$GLOBALS['RowId'] = md5($relativePath);

			if(is_dir($filePath)) {
				$GLOBALS['FileSize'] = GetLang('NA');
				$GLOBALS['FileDate'] = GetLang('NA');
				$output .= $this->template->render('Snippets/EmailTemplateDirectory.html');
			}
			else {
				$GLOBALS['FileSize'] = Store_Number::niceSize(filesize($filePath));
				$GLOBALS['FileDate'] = isc_date(GetConfig('ExtendedDisplayDateFormat'), filemtime($filePath));
				$output .= $this->template->render('Snippets/EmailTemplate.html');
			}
		}

		return $output;
	}

	public function BuildTemplateURL($url, $replacements=array())
	{
		$replacements['version'] = PRODUCT_VERSION_CODE;
		if(empty($replacements)) {
			return $url;
		}
		foreach($replacements as $find => $replacement) {
			$url = str_replace("%%".strtoupper($find)."%%", $replacement, $url);
		}
		return $url;
	}

	private function UploadFavicon()
	{
		if($_FILES['FaviconFile']['error'] == 0 && $_FILES['FaviconFile']['size'] > 0) {
			$_FILES['FaviconFile']['name'] = basename($_FILES['FaviconFile']['name']);

			if(!$this->IsValidFavicoFile($_FILES['FaviconFile'])) {
				$this->ManageLayouts(GetLang('UploadedFaviconNoValidImage2'), MSG_ERROR);
			}
			else {
				$destination = ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/'.$_FILES['FaviconFile']['name'];

				if (file_exists($destination)) {
					@unlink($destination);
				}

				// Upload and store the actual logo
				if(move_uploaded_file($_FILES['FaviconFile']['tmp_name'], $destination)) {
					// Save the updated logo in the configuration file
					$GLOBALS['ISC_NEW_CFG']['Favicon'] = $_FILES['FaviconFile']['name'];
					$settings = GetClass('ISC_ADMIN_SETTINGS');
					if($settings->CommitSettings()) {
						isc_chmod(ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/'.$GLOBALS['ISC_NEW_CFG']['Favicon'], ISC_WRITEABLE_FILE_PERM);
						FlashMessage(GetLang('FaviconUploadSuccess'), MSG_SUCCESS, 'index.php?ToDo=viewTemplates');
					}
					else {
						FlashMessage(GetLang('FaviconUploadWorkedConfigError'), MSG_ERROR, 'index.php?ToDo=viewTemplates');
					}
				}
				else {
					FlashMessage(GetLang('FaviconUploadErrorPath'), MSG_ERROR, 'index.php?ToDo=viewTemplates');
				}
			}
		}
		else {
			FlashMessage(GetLang('FaviconUploadNoValidImage'), MSG_ERROR, 'index.php?ToDo=viewTemplates');
			exit;
		}
	}

	private function IsValidFavicoFile($file)
	{
		$imageExtensions = array(
			'gif',
			'png',
			'jpg',
			'jpeg',
			'jpe',
			'ico'
		);
		$extension = GetFileExtension($file['name']);
		if(!in_array(isc_strtolower($extension), $imageExtensions)) {
			return false;
		}

		// we can't validate .ico so just return true
		if ($extension == "ico") {
			return true;
		}

		$imageTypes = array();
		$imageTypes[] = IMAGETYPE_GIF;
		$imageTypes[] = IMAGETYPE_JPEG;
		$imageTypes[] = IMAGETYPE_PNG;
		$imageTypes[] = IMAGETYPE_BMP;

		$imageDimensions = @getimagesize($file['tmp_name']);
		if(!is_array($imageDimensions) || !in_array($imageDimensions[2], $imageTypes, true)) {
			return false;
		}

		// must be 16x16
		if ($imageDimensions[0] != 16 || $imageDimensions[1] != 16) {
			return false;
		}

		return true;
	}
}