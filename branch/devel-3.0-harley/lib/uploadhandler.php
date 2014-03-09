<?php

if (!defined('UPLOAD_ERR_EXTENSION')) {
	// introduced in 5.1.2
	define('UPLOAD_ERR_EXTENSION', 8);
}

class UploadHandlerException extends Exception { }
class UploadHandlerFileException extends UploadHandlerException { }

class UploadHandlerProcessException extends UploadHandlerException { }
class UploadHandlerProcessPostSizeException extends UploadHandlerProcessException { }
class UploadHandlerProcessNoInputException extends UploadHandlerProcessException { }

class UploadHandlerFileMoveException extends UploadHandlerFileException { }
class UploadHandlerFileMoveExistsException extends UploadHandlerFileMoveException { }
class UploadHandlerFileMoveNotWritableException extends UploadHandlerFileMoveException { }

/**
* Class for storing information about an uploaded file. Returned as a result of the various 'get' file methods in the UploadHandler class.
*
*/
class UploadHandlerFile {

	/**
	 * Name of upload field in HTML form
	 *
	 * @var string
	 */
	public $fieldName;

	/**
	 * Name of uploaded file from client system
	 *
	 * @var string
	 */
	public $name;

	/**
	 * MIME type of uploaded file
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Full path and filename of temporary uploaded file
	 *
	 * @var string
	 */
	public $tmp_name;

	/**
	 * Error code of uploaded file, if any
	 *
	 * @var int
	 */
	public $error;

	/**
	 * Size of uploaded file, in bytes
	 *
	 * @var int
	 */
	public $size;

	/**
	 * Indicates if the file has been moved out of the temp storage.
	 *
	 * @var string blank if not yet moved otherwise location uploaded file was moved to
	 */
	private $moved = '';

	/**
	*
	* @param string $fieldName Name of uploaded field in HTML form
	* @param string $name Name of uploaded file from client system
	* @param string $type MIME type of uploaded file
	* @param string $tmp_name Full path and filename of temporary uploaded file
	* @param int $error Error code of uploaded file, if any
	* @param int $size Size of uploaded file, in bytes
	* @return UploadHandlerFile
	*/
	public function __construct($fieldName, $name, $type, $tmp_name, $error, $size)
	{
		$this->fieldName = $fieldName;
		$this->name = $name;
		$this->type = $type;
		$this->tmp_name = $tmp_name;
		$this->error = $error;
		$this->size = $size;
	}

	/**
	 * Attempts to moves this uploaded file to the given destination folder, automatically using the client-provided filename.
	 *
	 * @param string $destination Folder to move the file to
	 * @param bool $overwrite Optional. Default true. If true, any existing destination file will be overwritten. If false, this will throw UploadHandlerFileMoveExistsException if the destination file exists.
	 * @return bool Returns true on success, otherwise false.
	 * @throws UploadHandlerFileMoveExistsException If the destination file exists and $overwrite is false.
	 */
	public function move($destination, $overwrite = true)
	{
		//	remove trailing slashes in the destination folder
		$destination = rtrim($destination, '\/');

		return $this->moveAs($destination . '/' . $this->name);
	}

	/**
	 * Attempted to move this uploaded file to the given destination folder with the given filename
	 *
	 * @param $destination Folder to move the file to
	 * @param bool $overwrite Optional. Default true. If true, any existing destination file will be overwritten. If false, this will throw UploadHandlerFileMoveExistsException if the destination file exists.
	 * @return bool Returns true if the file was moved otherwise false if the file was already moved or if is_uploaded_file check returns false. If a move was attempted but failed an exception will be thrown.
	 * @throws UploadHandlerFileMoveExistsException If the destination file exists and $overwrite is false.
	 * @throws UploadHandlerFileMoveNotWritableException If the destination file exists and is not writable.
	 */
	public function moveAs($destination, $overwrite = true)
	{
		if ($this->moved || !is_uploaded_file($this->tmp_name)) {
			return false;
		}

		if (file_exists($destination)) {
			if (!$overwrite) {
				throw new UploadHandlerFileMoveExistsException(sprintf(UploadHandler::$i18n['UPLOADHANDLER_ERR_MOVE_EXISTS'], $this->tmp_name, $destination));
			}

			if (!is_writable($destination)) {
				throw new UploadHandlerFileMoveNotWritableException(sprintf(UploadHandler::$i18n['UPLOADHANDLER_ERR_MOVE_NOT_WRITABLE'], $this->tmp_name, $destination));
			}
		}

		$result = @move_uploaded_file($this->tmp_name, $destination);
		if (!$result) {
			// checks are performed above to avoid this but move_uploaded_file can still fail since the calls are not atomic
			throw new UploadHandlerFileMoveNotWritableException(sprintf(UploadHandler::$i18n['UPLOADHANDLER_ERR_MOVE_NOT_WRITABLE'], $this->tmp_name, $destination));
		}

		$this->moved = $destination;
		return true;
	}

	/**
	 * Returns the extension of the uploaded file based on the client-side filename. Files with no extensions will return a blank string, files such as ".htaccess" will return "htaccess".
	 *
	 * @return string
	 */
	public function getExtension()
	{
		//	reverse the string, find the text before the first (formerly last) dot and return it
		$reversed = strrev($this->name);
		$dot = strpos($reversed, '.', 0);
		if ($dot === false || $dot === 0) {
			//	no dot or dot is at end of filename
			return '';
		}

		//	reverse the extension and return it
		return strrev(substr($reversed, 0, $dot));
	}

	/**
	 * Determines if the file was uploaded OK according to the error code.
	 *
	 * @return bool
	 */
	public function getSuccess()
	{
		return ($this->error === 0);
	}

	/**
	 *
	 *
	 * @return string
	 */
	public function getErrorMessage()
	{
		$messages = array(
			UPLOAD_ERR_INI_SIZE		=> 'UPLOAD_ERR_INI_SIZE',
			UPLOAD_ERR_FORM_SIZE	=> 'UPLOAD_ERR_FORM_SIZE',
			UPLOAD_ERR_PARTIAL		=> 'UPLOAD_ERR_PARTIAL',
			UPLOAD_ERR_NO_FILE		=> 'UPLOAD_ERR_NO_FILE',
			UPLOAD_ERR_NO_TMP_DIR	=> 'UPLOAD_ERR_NO_TMP_DIR',
			UPLOAD_ERR_CANT_WRITE	=> 'UPLOAD_ERR_CANT_WRITE',
			UPLOAD_ERR_EXTENSION	=> 'UPLOAD_ERR_EXTENSION',
		);

		if (!isset($messages[$this->error])) {
			return '';
		}

		return sprintf(UploadHandler::$i18n[$messages[$this->error]], $this->fieldName, $this->type, $this->tmp_name, $this->error, $this->size, $this->getExtension());
	}

	/**
	* Determines if this uploaded file has been moved out of the temp location yet.
	*
	* @return bool
	*/
	public function getIsMoved()
	{
		return (bool)$this->moved;
	}

	/**
	* If this file has been moved out of the temp location, returns a string pointing to the location it was moved to otherwise returns false.
	*
	* @return string
	*/
	public function getMovedDestination()
	{
		if (!$this->getIsMoved()) {
			return false;
		}
		return $this->moved;
	}
}

/**
* Generic file upload handler class with temporary file management, exception throwing and language-based error information
*
*/
class UploadHandler {

	/**
	 * Whether or not uploaded files have been processed yet.
	 *
	 * @var bool
	 */
	protected static $_processed = false;

	/**
	 * Default language text that can be changed by the app using this lib.
	 *
	 * @var array
	 */
	public static $i18n;

	/**
	 * Content length specified in the POST request.
	 *
	 * @var int
	 */
	public static $contentLength;

	/**
	 * Max post size specified in php.ini stored as a number in bytes
	 *
	 * @var int
	 */
	public static $maxPostSize;

	/**
	 * Max post size specified in php.ini stored as a number in bytes
	 *
	 * @var int
	 */
	public static $uploadMaxFilesize;

	/**
	 * Max uploadable file size based on max POST size and max upload file size
	 *
	 * @var int
	 */
	public static $maxUploadSize;

	/**
	 * Storage for data about files that have been uploaded. Array will contain a set of UploadHandlerFile instances.
	 *
	 * @var array
	 */
	public static $files;

	/**
	 * Takes a php.ini-like bytes-size string such as 2M and converts it to a number. G, M and K each represent a unit of 1024 bytes: http://php.net/ini_get
	 *
	 * @param string $val The value to convert
	 * @return int The result in bytes
	 */
	public static function iniBytes($val)
	{
		$val = trim($val);
		$scale = strtolower($val[strlen($val) - 1]);

		$val = intval($val);
		switch ($scale) {
			case 'g':
				$val *= 1024;

			case 'm':
				$val *= 1024;

			case 'k':
				$val *= 1024;
		}

		return $val;
	}

	/**
	 * Processes server and $_FILE info relating to uploads to produce usable info. Assigns all gathered data to class variables which are accessible via public methods.
	 *
	 * @return void
	 * @throws UploadHandlerProcessPostSizeException If the posted data size exceeds the maximum post size defined by php configuration
	 * @throws UploadHandlerProcessNoInputException If no files were uploaded
	 */
	public static function processUploads()
	{
		if (self::$_processed) {
			return;
		}

		self::$_processed = true;

		self::$files = array();

		if (self::$contentLength >= self::$maxPostSize) {
			//	post length exceeds maximum post length ini directive - impossible for php to parse uploaded files because $_FILES is empty
			throw new UploadHandlerProcessPostSizeException(sprintf(self::$i18n['UPLOADHANDLER_ERR_PROCESS_POST_SIZE'], self::$contentLength, self::$maxPostSize));
		}

		if (empty($_FILES)) {
			//	no files uploaded
			throw new UploadHandlerProcessNoInputException(self::$i18n['UPLOADHANDLER_ERR_PROCESS_NO_INPUT']);
		}

		foreach ($_FILES as $fieldName => $field) {

			if (is_array($field['name'])) {
				//	input field that uses [] in it's name

				self::$files[$fieldName] = array();

				foreach ($field['name'] as $index => $ignoreThisValue) {
					$name = $field['name'][$index];
					$type = $field['type'][$index];
					$tmp_name = $field['tmp_name'][$index];
					$error = intval($field['error'][$index]);
					$size = intval($field['size'][$index]);

					$file = new UploadHandlerFile($fieldName, $name, $type, $tmp_name, $error, $size);

					self::$files[$fieldName][] = $file;
				}

			} else {
				//	single input field
				$name = $field['name'];
				$type = $field['type'];
				$tmp_name = $field['tmp_name'];
				$error = intval($field['error']);
				$size = intval($field['size']);

				$file = new UploadHandlerFile($fieldName, $name, $type, $tmp_name, $error, $size);

				self::$files[$fieldName] = array($file);
			}
		}
	}

	/**
	 * Returns a flat list of all uploaded files; whether successful or not
	 *
	 * @return array
	 */
	public static function getAllFiles()
	{
		self::processUploads();

		$return = array();
		foreach (self::$files as $field => $files) {
			foreach ($files as $file) {
				$return[] = $file;
			}
		}
		return $return;
	}

	/**
	 * Returns only a list of files that were uploaded successfully
	 *
	 * @return array
	 */
	public static function getUploadedFiles()
	{
		self::processUploads();

		$return = array();
		foreach (self::$files as $field => $files) {
			foreach ($files as $file) {
				if ($file->getSuccess()) {
					$return[] = $file;
				}
			}
		}
		return $return;
	}

	/**
	 * Returns only a list of files that were not uploaded successfully
	 *
	 * @param bool $includeEmpty Optional. Default false. Set to true to include empty file upload fields as errors.
	 * @return array
	 */
	public static function getErrorFiles($includeEmpty = false)
	{
		self::processUploads();

		$return = array();
		foreach (self::$files as $field => $files) {
			foreach ($files as $file) {
				if (!$includeEmpty && $file->error == UPLOAD_ERR_NO_FILE) {
					continue;
				}

				if (!$file->getSuccess()) {
					$return[] = $file;
				}
			}
		}
		return $return;
	}
}

//	statically set default language vars - an app that needs to customize messages can do so after including this class (only needs to happen once)

UploadHandler::$i18n = array(
	'UPLOADHANDLER_ERR_PROCESS_POST_SIZE'	=> 'POST size of %1$d bytes exceeds the limit set in php.ini#post_max_size of %2$d bytes.',
	'UPLOADHANDLER_ERR_PROCESS_NO_INPUT'	=> 'No file input fields were detected in the POST request.',
	'UPLOADHANDLER_ERR_MOVE_EXISTS'			=> 'Could not move uploaded file "%1$s" to "%2$s" as the destination file already exists and the overwrite option was not set.',
	'UPLOAD_ERR_INI_SIZE'					=> 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
	'UPLOAD_ERR_FORM_SIZE'					=> 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
	'UPLOAD_ERR_PARTIAL'					=> 'The uploaded file was only partially uploaded.',
	'UPLOAD_ERR_NO_FILE'					=> 'No file was uploaded.',
	'UPLOAD_ERR_NO_TMP_DIR'					=> 'Missing a temporary folder.',
	'UPLOAD_ERR_CANT_WRITE'					=> 'Failed to write file to disk.',
	'UPLOAD_ERR_EXTENSION'					=> 'File upload stopped by extension.',
);

//	statically set some values that do not change throughout the lifetime of a request

UploadHandler::$contentLength = (int)@$_SERVER['CONTENT_LENGTH'];
UploadHandler::$maxPostSize = UploadHandler::iniBytes(ini_get('post_max_size'));

// store this for informational purposes only - php upload support will internally deny uploads which are over this size but client-side code can use this to advise of file size limits
UploadHandler::$uploadMaxFilesize = UploadHandler::iniBytes(ini_get('upload_max_filesize'));

// the lowest of both maxPostSize and uploadMaxFile size determines the maximum size of a single file upload (may not be accurate to the byte since there's some POST overhead if max POST size happens to be smaller than max file size)
UploadHandler::$maxUploadSize = min(UploadHandler::$uploadMaxFilesize, UploadHandler::$maxPostSize);
