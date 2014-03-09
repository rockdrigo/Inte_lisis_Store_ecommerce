<?php

/**
 * This file contains the ISC_ADMIN_PICNIK class
 *
 * @version $Id$
 *
 * @package ISC
 * @subpackage ISC_Admin
 */

define('ISC_PICNIK_SERVICEURL', 'www.picnik.com/service/');
define('ISC_PICNIK_APIKEY', 'd0a572a5131244ff3451f62d5afbac44');

define('ISC_PICNIK_TYPE_PRODUCTIMAGE', 1);
define('ISC_PICNIK_TYPE_IMAGEMANAGER', 2);

/**
 * Picnik.com Integration Class
 * This class handles the integration of Picnik with ISC
 *
 * @package ISC
 * @subpackage ISC_Admin
 */
class ISC_ADMIN_PICNIK extends ISC_ADMIN_BASE
{
	/**
	 * The routing method that determines what methods should be called based on the GET parameter 'ToDo'
	 *
	 * @param string $Do A short 'action' string, determining what method should be executed
	 *
	 * @return void Doesn't return anything
	 */
	public function HandleToDo($Do)
	{
		$method = 'handle' . ucfirst($Do);
		if (method_exists($this, $method)) {
			$this->$method();
		}
	}

	/**
	* For a provided type and id, return the path to the original for loading and saving purposes
	*
	* @param int $imageType One of ISC_PICNIK_TYPE_ constants
	* @param mixed $imageId Image identifier (could be numeric for product images, filename for image manager, etc.)
	* @return string
	*/
	public function getSourceFileForImage($imageType, $imageId)
	{
		switch ($imageType) {
			case ISC_PICNIK_TYPE_PRODUCTIMAGE:
				try {
					$image = new ISC_PRODUCT_IMAGE((int)$imageId);
				} catch (Exception $exception) {
					return false;
				}
				return $image->getAbsoluteSourceFilePath();
				break;

			case ISC_PICNIK_TYPE_IMAGEMANAGER:
				return ISC_BASE_PATH . '/' . GetConfig('ImageDirectory') . '/uploaded_images/' . rawurlencode($imageId);
				break;
		}
	}

	/**
	* For a provided image type and id, return the URL to send to picnik for loading
	*
	* @param int $imageType One of ISC_PICNIK_TYPE_ constants
	* @param mixed $imageId Image identifier (could be numeric for product images, filename for image manager, etc.)
	* @return string
	*/
	public function getSourceUrlForImage($imageType, $imageId)
	{
		// for internal testing when picnik cannot access the server, send an external url
		//return 'http://farm4.static.flickr.com/3433/3777641358_76e74c2846_o.jpg';

		switch ($imageType) {
			case ISC_PICNIK_TYPE_PRODUCTIMAGE:
				$image = new ISC_PRODUCT_IMAGE((int)$imageId);
				return $image->getSourceUrl();
				break;

			case ISC_PICNIK_TYPE_IMAGEMANAGER:
				return GetConfig('ShopPathSSL') . '/' . GetConfig('ImageDirectory') . '/uploaded_images/' . rawurlencode($imageId);
				break;
		}
	}

	/**
	* Generate and save a new picnik token (or existing duplicate token) - this makes a note of an image-edit-in-progress so as to authorise the download and writing of a new remote image from picnik
	*
	* @param int $imageType One of ISC_PICNIK_TYPE_ constants
	* @param mixed $imageId Image identifier (could be numeric for product images, filename for image manager, etc.)
	* @return array|bool Generated token hash and image url to be used for a picnik image editing session, or false if an error occurred while generating a token
	*/
	public function generatePicnikToken($imageType, $imageId)
	{
		$imageId = $imageId;
		$sourceUrl = $this->getSourceUrlForImage($imageType, $imageId);
		$sessionId = session_id();

		// check for token identical to given parameters
		$duplicate = false;
		$sql = "SELECT * FROM `[|PREFIX|]picniktokens` WHERE sessionid = '" . $this->db->Quote($sessionId) . "' AND imagetype = '" . $this->db->Quote($imageType) . "' AND imageid = '" . $this->db->Quote($imageId) . "'";
		$result = $this->db->Query($sql);
		if ($result) {
			$token = $this->db->Fetch($result);
			if ($token) {
				// use existing token
				$duplicate = true;
			}
		}

		if (!$duplicate) {
			// generate a new token
			// hash is just a randomly generated 32byte string based on allowable url characters
			$hash = Interspire_String::generateRandomString(32, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz01234567890');
			$token = $this->writePicnikToken($hash, $sessionId, $imageType, $imageId);
			if (!$token) {
				return false;
			}
		}

		return array(
			'token' => $token['picniktokenid'] . '_' . $token['hash'],
			'url' => $sourceUrl,
		);
	}

	/**
	* Store a picnik token and return its identifier, also cleans up old picnik tokens
	*
	* @param string $hash Unique or random key for this edit
	* @param string $sessionId PHP session id or other session identifier
	* @param int $imageType One of ISC_PICNIK_TYPE_ constants
	* @param mixed $imageId Image identifier (could be numeric for product images, filename for image manager, etc.)
	* @return array Token information
	*/
	public function writePicnikToken($hash, $sessionId, $imageType, $imageId)
	{
		$now = time();

		if (!$this->db->Query("DELETE FROM `[|PREFIX|]picniktokens` WHERE created < " . (time() - 86400))) {
			// failed to delete tokens older than 24hr
			return false;
		}

		$token = array(
			'hash' => $hash,
			'sessionid' => $sessionId,
			'imagetype' => $imageType,
			'imageid' => $imageId,
			'created' => $now,
		);

		$tokenId = $this->db->InsertQuery('picniktokens', $token);

		if (!$tokenId) {
			// failed to create token
			return false;
		}

		$token['picniktokenid'] = $tokenId;

		return $token;
	}

	/**
	* Sets up a picnik editing session along with template variables for use with control panel pages
	*
	* @param string $imageTypeString Image type in string form, so as to match with an ISC_PICNIK_TYPE_? constant (e.g., 'productimage')
	* @param mixed $imageId Image identifier (could be numeric for product images, filename for image manager, etc.)
	* @param bool $https True to return an HTTPS url, false to return an HTTP url, otherwise leave as null to autodetect based on the current request
	* @return array|bool False if any error occurred
	*/
	public function setupPicnikSession($imageTypeString, $imageId, $https = null)
	{
		$imageTypeConstant = 'ISC_PICNIK_TYPE_' . strtoupper($imageTypeString);
		if (!defined($imageTypeConstant)) {
			$this->log->LogSystemWarning('general', 'Unhandled picnik image type specified: ' . $imageTypeString);
			return false;
		}

		$imageType = constant($imageTypeConstant);

		$token = $this->generatePicnikToken($imageType, $imageId);
		if (!$token) {
			$this->log->LogSystemError('general', 'Failed to generate and save picnik token to database');
			return false;
		}

		if ($https === null) {
			// to account for security warnings, determine based on current request, not store settings - why?
			// 1. could potentially be used on front end design mode
			// 2. the cp request will already be forced to ssl if the store is configured as such
			if (Interspire_Request::server('HTTPS') == 'on') {
				$https = true;
			} else {
				$https = false;
			}
		} else {
			$https = (bool)$https;
		}

		$protocol = 'http';
		if ($https) {
			$protocol .= 's';
		}

		$this->template->assign('PicnikServiceUrl', $protocol . '://' . ISC_PICNIK_SERVICEURL);
		$this->template->assign('PicnikApiKey', ISC_PICNIK_APIKEY);
		$this->template->assign('PicnikImageUrl', $token['url']);
		$this->template->assign('PicnikSaveHandler', GetConfig('ShopPathSSL') . '/admin/index.php?ToDo=receivePicnik&token=' . rawurlencode($token['token']));
		$this->template->assign('PicnikCloseHandler', GetConfig('ShopPathSSL') . '/admin/index.php?ToDo=cancelPicnik&token=' . rawurlencode($token['token']));

		$this->template->assign('PicnikSaveTitle', GetLang('PicnikSaveTitle', array('storename' => GetConfig('StoreName'))));

		return $token;
	}

	/**
	* Outputs the HTML to instruct the browser to POST directly to picnik -- used when a browser cookie indicates the user wishes to bypass the picnik loading message
	*
	* @return void
	*/
	public function handleLaunchPicnikDirect()
	{
		if (!isset($_POST['imageType']) || !isset($_POST['imageId'])) {
			return;
		}

		$token = $this->setupPicnikSession($_POST['imageType'], $_POST['imageId']);

		$this->template->display('pageheader.popup.tpl');
		$this->template->display('picnik.direct.tpl');
		$this->template->display('pagefooter.popup.tpl');
	}

	/**
	* Outputs the HTML to power the picnik editor launching dialog
	*
	* @return void
	*/
	public function handleLaunchPicnikModal()
	{
		if (!isset($_POST['imageType']) || !isset($_POST['imageId'])) {
			return;
		}

		$token = $this->setupPicnikSession($_POST['imageType'], $_POST['imageId']);

		$this->template->display('picnik.intro.tpl');
	}

	/**
	* Load a picnik edit token
	*
	* @param string $token Token string in the format of '{id}_{hash}' - typically this is provided as a GET/POST parameter
	* @return array|bool Token row from database, or false on error
	*/
	public function loadToken($token)
	{
		list($tokenId, $tokenHash) = explode('_', $token, 2);
		$tokenId = (int)$tokenId;

		$sql = "SELECT * FROM `[|PREFIX|]picniktokens` WHERE picniktokenid = " . $tokenId . " AND hash = '" . $this->db->Quote($tokenHash) . "'";
		$result = $this->db->Query($sql);
		if (!$result) {
			$this->log->LogSystemError('general', 'Failed to query database for Picnik token');
			return false;
		}

		$row = $this->db->Fetch($result);
		if (!$row) {
			$this->log->LogSystemNotice('general', 'Found no matching rows in database for Picnik token');
			return false;
		}

		return $row;
	}

	/**
	* Handle a browser request to cancel a picnik edit, typically triggered by clicking the 'close' button in picnik
	*
	* @return void
	*/
	public function handleCancelPicnik()
	{
		$token = $this->loadToken(Interspire_Request::get('token'));
		if ($token) {
			$this->removeToken($token['picniktokenid']);
		}

		// all done, redirect to where the user was when starting the edit session
		$this->template->display('pageheader.popup.tpl');
		$this->template->display('picnik.cancelled.tpl');
		$this->template->display('pagefooter.popup.tpl');
	}

	/**
	* Given a picnik token and a remote file, downloads and processes the remote image, updating and cleaning up local data as required, and sets up template data for displaying to the browser
	*
	* @param array $token
	* @param string $remoteFile
	* @return bool True on success, false on error - on error, a template variable named 'PicnikError' will be assigned as non-false
	*/
	public function receivePicnik($token, $remoteFile)
	{
		$this->template->assign('PicnikError', false);

		$sourceFile = $this->getSourceFileForImage($token['imagetype'], $token['imageid']);
		if (!$sourceFile) {
			$this->template->assign('PicnikError', GetLang('PicnikError_NoSourceFile'));
			return false;
		}

		$errorType = null;

		if (!$this->downloadToFile($remoteFile, $sourceFile, $errorType)) {
			if ($errorType == 1) {
				$this->template->assign('PicnikError', GetLang('PicnikError_NoWrite'));
			} else {
				$this->template->assign('PicnikError', GetLang('PicnikError_NoDownload'));
			}
			return false;
		}

		$imageSize = @getimagesize($sourceFile);
		if (!$imageSize) {
			$this->template->assign('PicnikError', GetLang('PicnikError_Invalid'));
			return false;
		}

		$callbackData = array();

		// the source file has been replaced, now regenerate other files based on it if necessary
		switch ($token['imagetype']) {
			case ISC_PICNIK_TYPE_PRODUCTIMAGE:
				$image = new ISC_PRODUCT_IMAGE((int)$token['imageid']);
				$image->removeResizedFiles();
				$image->saveToDatabase(true);
				$callbackData['thumbnail'] = $image->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL, true);
				$callbackData['zoom'] = $image->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_ZOOM, true);
				break;

			case ISC_PICNIK_TYPE_IMAGEMANAGER:
				$callbackData['name'] = basename($sourceFile);
				$callbackData['size'] = Store_Number::niceSize(filesize($sourceFile));
				$callbackData['url'] = GetConfig('ShopPathSSL') . '/' . GetConfig('ImageDirectory') . '/uploaded_images/' . $callbackData['name'];
				$callbackData['dimensions'] = $imageSize[0] . ' x ' . $imageSize[1];
				$callbackData['id'] = md5($callbackData['name']);

				$callbackData['displaywidth'] = $imageSize[0];
				$callbackData['displayheight'] = $imageSize[1];

				if ($callbackData['displaywidth'] > 200) {
					$callbackData['displayheight'] = (200 / $callbackData['displaywidth']) * $callbackData['displayheight'];
					$callbackData['displaywidth']= 200;
				}

				if ($callbackData['displayheight'] > 150) {
					$callbackData['displaywidth'] = (150/$callbackData['displayheight']) * $callbackData['displaywidth'];
					$callbackData['displayheight'] = 150;
				}
				break;
		}

		$this->removeToken($token['picniktokenid']);
		$this->template->assign('PicnikCallbackData', isc_json_encode($callbackData));
		return $callbackData;
	}

	/**
	* Handle a browser request to finish a picnik edit, typically triggered by clicking the 'save and close' button in picnik -- will download the new image and store it locally
	*
	* @return void
	*/
	public function handleReceivePicnik()
	{
		$token = $this->loadToken(Interspire_Request::get('token'));

		$remoteFile = $_GET['file'];
		$this->template->assign('PicnikRemoteFile', $remoteFile);

		if (!$token) {
			$this->template->assign('PicnikError', GetLang('PicnikError_InvalidToken'));
		} else {
			$token['imagetype'] = (int)$token['imagetype'];
			$this->receivePicnik($token, $remoteFile);
		}

		// all done, redirect to where the user was when starting the edit session
		$this->template->display('pageheader.popup.tpl');
		$this->template->display('picnik.received.tpl');
		$this->template->display('pagefooter.popup.tpl');
	}

	/**
	* Delete a picnik token from the database
	*
	* @param int $tokenId Token row by picniktokenid to delete
	*/
	public function removeToken($tokenId)
	{
		$this->db->Query("DELETE FROM `[|PREFIX|]picniktokens` WHERE picniktokenid = " . (int)$tokenId);
	}

	/**
	* Downloads image from picnik (or can be used for any url, really) and stores it at a file named $destination -- primarily a wrapper for PostToRemoteFileAndGetResponse with built-in file writing and error handling
	*
	* @param string $url
	* @param string $destination (optional)
	* @return string|bool Returns the filename the image was saved to, or false if anything went wrong
	*/
	public function downloadToFile($url, $destination = false, &$errorType = null)
	{
		$result = PostToRemoteFileAndGetResponse($url);

		if (!$destination) {
			// generate a random name for our downloaded file and store it in cache dir
			while (true) {
				// we can name it .tmp because the extension will be corrected after the image type is detected
				$destination = ISC_CACHE_DIRECTORY . 'picnikimage_' . Interspire_String::generateRandomString(16) . '.tmp';

				if (!file_exists($destination)) {
					break;
				}
			}
		}

		$fh = fopen($destination, 'wb');
		if ($fh) {
			if (!fwrite($fh, $result)) {
				fclose($fh);
				$this->log->LogSystemError('general', 'Failed to write downloaded Picnik image to local file');
				$errorType = 1;
				return false;
			}
			fclose($fh);
			isc_chmod($destination, ISC_WRITEABLE_FILE_PERM); // set the chmod just incase this was a new file
		} else {
			$this->log->LogSystemError('general', 'Failed to open local file for saving downloaded Picnik image');
			$errorType = 2;
			return false;
		}

		return $destination;
	}
}
