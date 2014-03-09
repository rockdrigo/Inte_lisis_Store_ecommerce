<?php

if (!defined('ISC_BASE_PATH')) {
	die();
}

class ISC_ADMIN_REMOTE_REDIRECTS extends ISC_ADMIN_BASE
{
	public function __construct()
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('redirects');

		parent::__construct();
	}

	public function HandleToDo()
	{
		/**
		 * Convert the input character set from the hard coded UTF-8 to their
		 * selected character set
		 */
		convertRequestInput();

		GetLib('class.json');

		$action = isc_strtolower(@$_REQUEST['w']);

		if (!$GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Redirects)) {
			ISC_JSON::output(GetLang('NoPermission'));
			die();
		}

		if(method_exists($this, $action)) {
			$this->$action();
			die();
		}

		ISC_JSON::output(GetLang('InvalidAction'));
	}

	private function saveRedirectType()
	{
		$redirectType = $_POST['type'];
		$redirectId = (int)$_POST['redirectid'];

		if(!in_array($redirectType, array('auto', 'manual'))) {
			ISC_JSON::output(GetLang('RedirectsInvalidDataType'));
		}

		if($redirectId < 1) {
			ISC_JSON::output(GetLang('RedirectModifyDoesntExist'));
		}

		GetLib('class.urls');
		GetLib('class.redirects');

		$redirect = ISC_REDIRECTS::loadRedirectById($redirectId);

		$UpdateData = array(
			'redirectassoctype' => ISC_REDIRECTS::REDIRECT_TYPE_PRODUCT,
		);

		if($redirectType == 'manual') {
			$newUrl = '';

			switch($redirect['redirectassoctype']) {
				case ISC_REDIRECTS::REDIRECT_TYPE_PRODUCT:
					$newUrl = ISC_URLS::getProductUrl($redirect['redirectassocid']);
					break;
				case ISC_REDIRECTS::REDIRECT_TYPE_CATEGORY:
					$newUrl = ISC_URLS::getCategoryUrl($redirect['redirectassocid']);
					break;
				case ISC_REDIRECTS::REDIRECT_TYPE_BRAND:
					$newUrl = ISC_URLS::getBrandUrl($redirect['redirectassocid']);
					break;
				case ISC_REDIRECTS::REDIRECT_TYPE_PAGE:
					$newUrl = ISC_URLS::getPageUrl($redirect['redirectassocid']);
					break;
			}

			if($newUrl == '0') {
				$newUrl = '';
			}

			$UpdateData = array(
				'redirectmanual' => $newUrl,
				'redirectassoctype' => ISC_REDIRECTS::REDIRECT_TYPE_MANUAL
			);
		}

		if($GLOBALS['ISC_CLASS_DB']->UpdateQuery('redirects', $UpdateData, 'redirectid=' . $redirectId)) {
			ISC_JSON::output('', true);
			return;
		}

		ISC_JSON::output(GetLang('RedirectSaveErrorDatabase'));
	}

	private function deleteRedirects()
	{
		if(!isset($_POST['redirects'])) {
			ISC_JSON::output(GetLang('NoRedirectsSelected'));
		}

		$cleanIds = array();
		$tmpRedirects = 0;

		foreach($_POST['redirects'] as $redirectId) {
			if(substr($redirectId, 0, 3) == 'tmp') {
				$tmpRedirects++;
				continue;
			}
			$redirectId = (int)$redirectId;
			if($redirectId > 0) {
				$cleanIds[] = $redirectId;
			}
		}

		if(empty($cleanIds) && $tmpRedirects == 0) {
			ISC_JSON::output(GetLang('NoValidRedirectsSelected'));
		}

		if(empty($cleanIds) && $tmpRedirects > 0) {
			ISC_JSON::output(GetLang('RedirectsDeleteSuccessful'), true);
		}

		$query = "delete from `[|PREFIX|]redirects` where redirectid IN (" . implode($cleanIds, ',') .")";

		if($GLOBALS['ISC_CLASS_DB']->query($query)) {
			ISC_JSON::output(GetLang('RedirectsDeleteSuccessful'), true);
		}

		ISC_JSON::output(GetLang('RedirectsDeleteError'));
	}

	/**
	* Deletes a single redirect
	*
	*/
	private function deleteRedirect()
	{
		if (empty($_POST['id'])) {
			ISC_JSON::output(GetLang('RedirectDeleteDoesntExist'));
		}

		$id = (int)$_POST['id'];

		GetLib('class.urls');
		GetLib('class.redirects');

		$redirect = ISC_REDIRECTS::loadRedirectById($id);
		if (!$redirect) {
			ISC_JSON::output(GetLang('RedirectDeleteDoesntExist'));
		}

		if ($GLOBALS['ISC_CLASS_DB']->DeleteQuery('redirects', 'WHERE redirectid = ' . $id)) {
			ISC_JSON::output(GetLang('RedirectDeleteSuccessful'), true);
		}

		ISC_JSON::output(GetLang('RedirectDeleteError'));
	}

	private function uploadBulkFile()
	{
		$newLimit = ceil(((filesize($_FILES['BulkImportRedirectsFile']['tmp_name']) / 1024 / 1024)*2) + 8);
		$oldLimit = (int)str_replace('M', '', @ini_get('memory_limit'));

		if($newLimit > $oldLimit) {
			@ini_set('memory_limit', $newLimit . 'M');
		}

		GetLib('class.urls');
		GetLib('class.redirects');

		if(substr(isc_strtolower($_FILES['BulkImportRedirectsFile']['name']), -4) == '.xml') {
			$xml = new SimpleXMLElement($_FILES['BulkImportRedirectsFile']['tmp_name'], null, true);
			$urls = $xml->children();
			foreach($urls as $thisUrl) {
				$url = (string)$thisUrl->loc;
				$url = ISC_REDIRECTS::normalizeURLForDatabase($url);
				if ($url === false) {
					continue;
				}
				$InsertData = array(
					'redirectassoctype' => ISC_REDIRECTS::REDIRECT_TYPE_NOREDIRECT,
					'redirectassocid'=> 0,
					'redirectpath' => '',
					'redirectmanual' => '',
					'redirectpath' => $url,
				);
				$GLOBALS['ISC_CLASS_DB']->InsertQuery('redirects', $InsertData);
			}
		} else {
			// must be a CSV
			$importer = new ISC_ADMIN_CSVPARSER();
			$importer->OpenCSVFile($_FILES['BulkImportRedirectsFile']['tmp_name'], 0);

			// skip header line if required
			if (isset($_POST['Headers'])) {
				$importer->FetchNextRecord();
			}

			while(($record = $importer->FetchNextRecord()) !== false) {
				$redirectPath = ISC_REDIRECTS::normalizeURLForDatabase($record[0]);
				if ($redirectPath === false) {
					continue;
				}

				$InsertData = array(
					'redirectassoctype' => ISC_REDIRECTS::REDIRECT_TYPE_NOREDIRECT,
					'redirectassocid'=> 0,
					'redirectpath' => '',
					'redirectmanual' => '',
					'redirectpath' => $redirectPath,
				);

				if(!empty($record[2])) {
					$label = ISC_REDIRECTS::getTypeFromLabel($record[1]);
					if (is_numeric($record[2]) &&  $label !== false) {
						$InsertData['redirectassocid'] = (int)$record[2];
						$InsertData['redirectassoctype'] = $label;
					}
				}
				elseif (!empty($record[1])) {
					$InsertData['redirectmanual'] = ISC_REDIRECTS::normalizeNewURLForDatabase($record[1]);
					$InsertData['redirectassoctype'] = ISC_REDIRECTS::REDIRECT_TYPE_MANUAL;
				}

				$GLOBALS['ISC_CLASS_DB']->InsertQuery('redirects', $InsertData);
			}
		}

		ISC_JSON::$useTextarea = true;
		ISC_JSON::output(GetLang('ImportSuccessful'), true);

	}

	private function loadBulkForm()
	{
		$GLOBALS['ImportMaxSize'] = sprintf(GetLang('ImportMaxSize'),  $this->_GetMaxUploadSize());

		$this->template->display('redirects.bulkform.tpl');
	}

	private function getEmptyRow()
	{
		GetLib('class.urls');
		GetLib('class.redirects');

		$RedirectsGrid = '';
		$GLOBALS['RedirectId'] = 'insertId';
		$GLOBALS['OldURL'] = '';
		$GLOBALS['NewURL'] = GetLang('ClickHereToEnterAURL');
		$GLOBALS['RedirectTypeManualSelected'] = "selected='selected'";
		$GLOBALS['RedirectTypeAutoSelected'] = "";
		$GLOBALS['RedirectTypeManualDisplay'] = "";
		$GLOBALS['RedirectTypeAutoDisplay'] = "display: none;";
		$GLOBALS['LinkerTitle'] = GetLang('BrowseForLink');
		$GLOBALS['RedirectActionsDisplay'] = "display: none;";

		$row['redirectassoctype'] = ISC_REDIRECTS::REDIRECT_TYPE_MANUAL;

		$RedirectsGrid = $this->template->render('redirects.row.tpl');

		ISC_JSON::output('', true, array('html' => $RedirectsGrid));
	}

	private function copyRedirect()
	{
		$id = (int)$_POST['id'];

		if($id < 1) {
			ISC_JSON::output(GetLang('RedirectCopyDoesntExist'));
		}

		GetLib('class.urls');
		GetLib('class.redirects');

		$redirect = ISC_REDIRECTS::loadRedirectById($id);

		unset($redirect['redirectid']);

		$redirectId = $GLOBALS['ISC_CLASS_DB']->InsertQuery('redirects', $redirect);

		if($redirectId) {
			$returnData = array(
				'id' => $redirectId
			);
			ISC_JSON::output('', true, $returnData);
			return;
		}

		ISC_JSON::output(GetLang('RedirectCopyError'));
	}

	private function getRedirectsTable()
	{
		$redirects = new ISC_ADMIN_REDIRECTS();

		list($table, $numResults) = $redirects->getRedirectsTable();

		if($numResults < 1) {
			ISC_JSON::output(GetLang('NoRedirects'), false, array('html' => $table));
		}

		// return results
		ISC_JSON::output('', true, array('html' => $table));
	}

	private function saveLinkById()
	{
		$redirectId = (int)$_POST['redirectid'];
		$newId = (int)$_POST['newid'];
		$dataType = $_POST['datatype'];

		if(!in_array($dataType, array('product', 'category', 'page', 'brand'))) {
			ISC_JSON::output(GetLang('LinkerInvalidDataType'));
		}

		GetLib('class.urls');
		GetLib('class.redirects');
		$assocType = ISC_REDIRECTS::REDIRECT_TYPE_MANUAL;

		switch($dataType) {
			case 'product':
				$assocType = ISC_REDIRECTS::REDIRECT_TYPE_PRODUCT;
				$urlInfo = ISC_URLS::getProductUrl($newId , true);
				$newUrl = $urlInfo['url'];
				$title = GetLang('Product') . ': ' . $urlInfo['title'];
				break;
			case 'category':
				$assocType = ISC_REDIRECTS::REDIRECT_TYPE_CATEGORY;
				$urlInfo = ISC_URLS::getCategoryUrl($newId , true);
				$newUrl = $urlInfo['url'];
				$title = GetLang('Category') . ': ' . $urlInfo['title'];
				break;
			case 'page':
				$assocType = ISC_REDIRECTS::REDIRECT_TYPE_PAGE;
				$urlInfo = ISC_URLS::getPageUrl($newId , true);
				$newUrl = $urlInfo['url'];
				$title = GetLang('Page') . ': ' . $urlInfo['title'];
				break;
			case 'brand':
				$assocType = ISC_REDIRECTS::REDIRECT_TYPE_BRAND;
				$urlInfo = ISC_URLS::getBrandUrl($newId , true);
				$newUrl = $urlInfo['url'];
				$title = GetLang('Brand') . ': ' . $urlInfo['title'];
				break;
		}

		$returnData = array(
			'title' => $title,
			'url' => $newUrl,
			'newid' =>  $newId,
			'redirectid' =>  $redirectId,
		);

		if($redirectId == 0 && substr($_POST['redirectid'], 0, 3) == 'tmp') {
			$redirectId = $GLOBALS['ISC_CLASS_DB']->InsertQuery('redirects', array('redirectassoctype' => $assocType, 'redirectassocid'=> $newId,'redirectpath' => '', 'redirectmanual' => ''));

			if($redirectId) {
				$returnData['redirectid'] = $redirectId;
				$returnData['tmpredirectid'] = $_POST['redirectid'];
				ISC_JSON::output('', true, $returnData);
				return;
			}
		} else {
			if($GLOBALS['ISC_CLASS_DB']->UpdateQuery('redirects', array('redirectassoctype' => $assocType, 'redirectassocid'=> $newId ), 'redirectid=' . $redirectId)) {
				ISC_JSON::output('', true, $returnData);
				return;
			}
		}
		// return results
		ISC_JSON::output(GetLang('RedirectSaveErrorDatabase'));
	}


	private function saveRedirectURL()
	{
		$newUrl = trim($_POST['newurl']);
		$redirectId = (int)$_POST['id'];

		if(empty($newUrl) || $newUrl == "/") {
			ISC_JSON::output(GetLang('InvalidRedirect'));
		}

		GetLib('class.redirects');

		$newUrl = ISC_REDIRECTS::normalizeURLForDatabase($newUrl, $error);
		if ($newUrl === false) {
			if (empty($error)) {
				$error = GetLang('InvalidRedirect');
			}
			ISC_JSON::output($error);
			return;
		}

		$returnData = array('url' => $newUrl, 'id' => $redirectId);

		// check if the redirect already exists
		$query = "
			SELECT
				*
			FROM
				[|PREFIX|]redirects r
			WHERE
				redirectpath = '" . $GLOBALS['ISC_CLASS_DB']->Quote($newUrl) . "' AND
				redirectid != " . $redirectId;

		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if ($GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			ISC_JSON::output(GetLang('RedirectAlreadyExists', array('redirectPath' => $newUrl)));
		}

		if($redirectId == 0 && substr($_POST['id'], 0, 3) == 'tmp') {
			$redirectId = $GLOBALS['ISC_CLASS_DB']->InsertQuery('redirects',  array('redirectpath' => $newUrl, 'redirectmanual' => '', 'redirectassoctype' => ISC_REDIRECTS::REDIRECT_TYPE_MANUAL, 'redirectassocid'=>0));
			if($redirectId) {
				$returnData['id'] = $redirectId;
				$returnData['tmpredirectid'] = $_POST['id'];
				ISC_JSON::output('', true, $returnData);
				return;
			}
		} else {
			if($GLOBALS['ISC_CLASS_DB']->UpdateQuery('redirects', array('redirectpath' => $newUrl), 'redirectid=' . $redirectId)) {
				ISC_JSON::output('', true, $returnData);
				return;
			}
		}

		ISC_JSON::output(GetLang('RedirectSaveErrorDatabase'));
	}

	private function saveNewRedirectURL()
	{
		$newUrl = trim($_POST['newurl']);
		$redirectId = (int)$_POST['id'];

		if(empty($newUrl) || $newUrl == "/") {
			ISC_JSON::output(GetLang('InvalidRedirect'));
		}

		GetLib('class.redirects');
		$newUrl = ISC_REDIRECTS::normalizeNewURLForDatabase($newUrl, $error);
		if ($newUrl === false) {
			if (empty($error)) {
				$error = GetLang('InvalidRedirect');
			}
			ISC_JSON::output($error);
		}
		$returnData = array('url' => $newUrl, 'id' => $redirectId);

		if($redirectId == 0 && substr($_POST['id'], 0, 3) == 'tmp') {
			$redirectId = $GLOBALS['ISC_CLASS_DB']->InsertQuery('redirects',  array('redirectpath'=> '', 'redirectmanual' => $newUrl, 'redirectassoctype' => ISC_REDIRECTS::REDIRECT_TYPE_MANUAL, 'redirectassocid'=>0));
			//echo "REdirect iD is " . $GLOBALS['ISC_CLASS_DB']->getErrorMsg();
			if($redirectId) {
				$returnData['id'] = $redirectId;
				$returnData['tmpredirectid'] = $_POST['id'];
				ISC_JSON::output('', true, $returnData);
				return;
			}
		} else {
			if($GLOBALS['ISC_CLASS_DB']->UpdateQuery('redirects', array('redirectmanual' => $newUrl), 'redirectid=' . $redirectId)) {
				ISC_JSON::output('', true, $returnData);
				return;
			}
		}

		ISC_JSON::output(GetLang('RedirectSaveErrorDatabase'));
	}

	protected function _GetMaxUploadSize()
	{
		$sizes = array(
			"upload_max_filesize" => ini_get("upload_max_filesize"),
			"post_max_size" => ini_get("post_max_size")
		);
		$max_size = -1;
		foreach($sizes as $size) {
			if (!$size) {
				continue;
			}
			$unit = isc_substr($size, -1);
			$size = isc_substr($size, 0, -1);
			switch(isc_strtolower($unit)) {
				case "g":
					$size *= 1024;
				case "m":
					$size *= 1024;
				case "k":
					$size *= 1024;
			}
			if($max_size == -1 || $size > $max_size) {
				$max_size = $size;
			}
		}
		if($max_size >= 1048576) {
			$max_size = floor($max_size/1048576)."MB";
		} else {
			$max_size = floor($max_size/1024)."KB";
		}
		return $max_size;
	}
}