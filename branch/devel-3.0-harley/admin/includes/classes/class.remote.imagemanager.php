<?php

if (!defined('ISC_BASE_PATH')) {
	die();
}

class ISC_ADMIN_REMOTE_IMAGEMANAGER extends ISC_ADMIN_REMOTE_BASE
{
	public function __construct()
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('imagemanager');
		parent::__construct();
	}

	/**
	 * Handle the incoming action and pass it off to the correct method.
	 */
	public function HandleToDo()
	{
		$what = isc_strtolower(@$_REQUEST['w']);
		switch ($what) {
			case 'uploadimage':
				$this->UploadImage();
				break;
			case 'getimageslist':
				$this->GetImagesList();
				break;
			case 'rename':
				$this->RenameImage();
				break;
			case 'delete':
				$this->DeleteImage();
				break;
		}
	}

	/**
	 * Fetch and return a list of images in a specific folder. Will only include valid images and will recurse in to subfolders.
	 *
	 * @param string The path to fetch images from.
	 * @return array An array of the images that were found.
	 */
	private function FetchImages($path='')
	{
		$images = array();
		$realPath = ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/uploaded_images/'.$path;
		if(!is_dir($realPath)) {
			return $images;
		}

		$files = scandir($realPath);
		foreach($files as $file) {
			if(substr($file, 0, 1) == '.' || (!is_dir($realPath.'/'.$file) && !$this->IsImageFile($file))) {
				continue;
			}
			else if(is_dir($realPath.'/'.$file)) {
				$images = array_merge($images, $this->FetchImages($path.$file.'/'));
			}
			else {
				$images[] = $path.$file;
			}
		}

		return $images;
	}

	/**
	 * Build and output a list of images in the image uploads directory and format them using Javascript
	 * so that the TinyMCE image manager can display a list of them.
	 */
	private function GetImagesList()
	{
		header('Content-type: text/javascript');

		$imageList = $this->FetchImages();
		echo 'var tinyMCEImageList = new Array(';
		foreach($imageList as $k => $image) {
			$comma = ',';
			if(!isset($imageList[$k+1])) {
				$comma = '';
			}
			echo '["'.$image.'","'.GetConfig('AppPath').'/'.GetConfig('ImageDirectory').'/uploaded_images/'.$image.'"]'.$comma."\n";
		}
		echo ');';
		exit;
	}

	/**
	 * Upload a new image from the Image Manager or TinyMCE itself. Images are thrown in the uploaded_images
	 * directory. Invalid images (no dimensions available, mismatched type) are not accepted. Will output
	 * a JSON encoded array of details about the image just uploaded.
	 */
	private function UploadImage()
	{
		if(empty($_FILES['Filedata'])) {
			exit;
		}

		$_FILES['Filedata']['filesize'] = Store_Number::niceSize($_FILES['Filedata']['size']);
		$_FILES['Filedata']['id'] = substr(md5($_FILES['Filedata']['name']), 0, 10);
		$_FILES['Filedata']['errorfile'] = false;
		$_FILES['Filedata']['imagepath'] = GetConfig('AppPath').'/'.GetConfig('ImageDirectory').'/uploaded_images/';
		$_FILES['Filedata']['duplicate'] = false;

		if($_FILES['Filedata']['error'] != UPLOAD_ERR_OK) {
			$_FILES['Filedata']['erorrfile'] = 'badupload';
			die(isc_json_encode($_FILES));
		}

		// Sanitise uploaded image file name.
		$tmpName = $_FILES['Filedata']['tmp_name'];
		$name = slugify(basename($_FILES['Filedata']['name']));
		$info = pathinfo($name);
		if ($info['filename'] == '') {
			$name = uniqid().$name;
		}

		$destination = ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/uploaded_images/'.$name;

		if(!$this->IsImageFile(isc_strtolower($name))) {
			$_FILES['Filedata']['errorfile'] = 'badname';
		}
		else if(file_exists($destination)) {
			$_FILES['Filedata']['duplicate'] = true;
		}
		else if(!@move_uploaded_file($tmpName, $destination)) {
			$_FILES['Filedata']['errorfile'] = 'badupload';
		}
		else if(!$this->IsValidImageFile($destination)) {
			$_FILES['Filedata']['errorfile'] = 'badtype';
			@unlink($destination);
		}

		if (!($_FILES['Filedata']['errorfile'] || $_FILES['Filedata']['duplicate'])) {
			isc_chmod($destination, ISC_WRITEABLE_FILE_PERM);

			// Get the image dimensions so we can show a thumbnail
			list($imgWidth, $imgHeight) = @getimagesize($destination);
			if(!$imgWidth || !$imgHeight) {
				$imgWidth = 200;
				$imgHeight = 150;
			}

			$_FILES['Filedata']['origwidth'] = $imgWidth;
			$_FILES['Filedata']['origheight'] = $imgHeight;

			if($imgWidth > 200) {
				$imgHeight = (200/$imgWidth) * $imgHeight;
				$imgWidth = 200;
			}

			if($imgHeight > 150) {
				$imgWidth = (150/$imgHeight) * $imgWidth;
				$imgHeight = 150;
			}

			$_FILES['Filedata']['width'] = $imgWidth;
			$_FILES['Filedata']['height'] = $imgHeight;
			$_FILES['Filedata']['name'] = $name;
			unset($_FILES['Filedata']['tmp_name']);
		}

		echo isc_json_encode($_FILES);
		exit;
	}

	private function UploadNonFlashFile()
	{
		$array = $_FILES;
		$array['hey'] = 'yo';
		die(isc_json_encode($array));
	}

	/**
	 * Check if a particular image is valid by checking the uploaded MIME type vs the actual
	 * MIME type of the image.
	 *
	 * @param string The path to the image file to check.
	 * @return boolean True if the image is valid, false if not.
	 */
	private function IsValidImageFile($fileName)
	{
		$imageTypes = array();
		$imageTypes[] = IMAGETYPE_GIF;
		$imageTypes[] = IMAGETYPE_JPEG;
		$imageTypes[] = IMAGETYPE_PNG;
		$imageTypes[] = IMAGETYPE_BMP;
		$imageTypes[] = IMAGETYPE_TIFF_II;

		$imageDimensions = @getimagesize($fileName);
		if(!is_array($imageDimensions) || !in_array($imageDimensions[2], $imageTypes, true)) {
			return false;
		}

		return true;
	}

	/**
	 * Check that a particular file name belongs to a list of known extensions
	 * for images.
	 *
	 * @param string The name of the file name.
	 * @return boolean True if the image has a valid file name, false if not.
	 */
	private function IsImageFile($fileName)
	{
		$validImages = array('png', 'jpg', 'gif', 'jpeg', 'tiff', 'bmp', 'jpe');
		foreach($validImages as $image) {
			if(substr($fileName, (int)-(strlen($image)+1)) === '.' . $image){
				return true;
			}
		}
		return false;
	}

	private function GetImagePath()
	{
		return ISC_BASE_PATH . '/'  . GetConfig('ImageDirectory') . "/uploaded_images";
	}

	private function GetImageAbsPath()
	{
		return GetConfig("AppPath") . "/" . GetConfig('ImageDirectory') . "/uploaded_images";
	}

	private function GetImageDir()
	{
		return GetConfig("ShopPath") . '/'  . GetConfig('ImageDirectory') . "/uploaded_images";
	}

	private function RenameImage()
	{
		// TODO: permission check
		ini_set('track_errors', '1');

		// lets get the extension from the old filename
		$ext = substr(strrchr($_POST['fromName'], "."), 0);
		$_POST['toName'] = slugify($_POST['toName']) . $ext;

		$return = array();
		if(strpos($_POST['toName'], '/') !== false || strpos($_POST['toName'], '\\') !== false ){
			$return['success'] = false;
			$return['message'] = GetLang('imageManagerRenameInvalidFileName');
			die(isc_json_encode($return));
		}

		if(!$this->IsImageFile($_POST['toName'])){
			$return['success'] = false;
			$return['message'] = GetLang('imageManagerRenameInvalidFileName');
			die(isc_json_encode($return));
		}

		if(!file_exists($this->GetImagePath() . '/' . $_POST['fromName'])){
			$return['success'] = false;
			$return['message'] = GetLang('imageManagerFileDoesntExistRename');
			die(isc_json_encode($return));
		}

		if(file_exists($this->GetImagePath() . '/' . $_POST['toName'])){
			$return['success'] = false;
			$return['message'] = GetLang('imageManagerRenameFileAlreadyExists');
			die(isc_json_encode($return));
		}

		if(!@rename($this->GetImagePath() . '/' . $_POST['fromName'], $this->GetImagePath() . '/' . $_POST['toName'])){
			if(isset($php_errormsg)){
				$msgBits = explode(':', $php_errormsg);
				if(isset($msgBits[1])){
					$message =  $msgBits[1] . '.';
				}else{
					$message =  $php_errormsg  . '.';
				}
			}else{
				$message = 'Unknown error.';
			}
			$return['success'] = false;
			$return['message'] = $message;
			die(isc_json_encode($return));
		}

		$return['success'] = true;
		$newName = $_POST['toName'];
		$newName = substr($newName, 0, strrpos($newName, "."));
		$return['newname'] = isc_html_escape($newName);
		$return['newrealname'] = isc_html_escape($_POST['toName']);
		$return['newurl'] = $this->GetImageDir() . '/' . urlencode($_POST['toName']);
		echo isc_json_encode($return);
	}

	private function DeleteImage()
	{
		$successImages = $errorFiles = $return = array();
		ini_set('track_errors', '1');
		// TODO: permission check

		if(!is_array($_POST['deleteimages']) || empty($_POST['deleteimages'])) {
			$return['success'] = false;
			$return['message'] = GetLang('imageManagerNoImagesSelectedDelete');
			die(isc_json_encode($return));
		}

		foreach($_POST['deleteimages'] as $k => $image) {
			if(file_exists($this->GetImagePath() . '/' . $image)){
				if(!@unlink($this->GetImagePath() . '/' . $image)) {
					if(isset($php_errormsg)){
						$msgBits = explode(':', $php_errormsg);
						if(isset($msgBits[1])){
							$errorFiles =  $msgBits[1] .'.';
						}else{
							$errorFiles =  $php_errormsg  .'.';
						}
					}else{
						$errorFiles[] = GetLang('UnableToDelete') . ' ' . $image;
					}
					unset($php_errormsg);
				}else{
					$successImages[] = $image;
				}
			}
		}

		if(!empty($errorFiles)){
			$return['success'] = false;
			$return['message'] = GetLang('imageManagerDeleteErrors') . '<ul><li>'.implode('</li><li>', $errorFiles) . '</li></ul>';
			die(isc_json_encode($return));
		}


		$return['success'] = true;
		$return['successimages'] = $successImages;
		if(count($successImages) == 1){
			$return['message'] = GetLang('imageManagerDeleteSuccessSingle');
		}elseif(count($successImages) > 1){
			$return['message'] = sprintf(GetLang('imageManagerDeleteSuccessMulti'), count($successImages));
		}
		echo isc_json_encode($return);
	}
}