<?php

/**
* This class contains functionality for for providing a forced download dialog in the users browser
*/
class Interspire_Download {
	/**
	* Forces a file to be download
	*
	* @param string $file Path of the file to download.
	* @param string $filename The filename to use to download the file. Will default to $file if blank.
	* @param bool $delete Delete the file after downloading it?
	* @param string $mimetype The mime type to use. Defaults to detecting the type based on file extension.
	* @param string $characterSet The character set which is used in conjunction which several mime-types (html, excel extensions)
	*
	* @return bool Returns false if file doesn't exist
	*/
	public static function downloadFile($file, $filename = '', $delete = false, $mimetype = '', $characterSet = 'UTF-8')
	{
		if (!file_exists($file)) {
			return false;
		}

		if ($filename == "") {
			$filename = $file;
		}

		// set headers to force downloading
		self::setDownloadHeaders($filename, filesize($file), $mimetype);

		// pass the file through
		readfile($file);

		if ($delete) {
			@unlink($file);
		}

		exit;
	}

	/**
	* Forces content to be downloaded
	*
	* @param mixed $filename The name of the file to use when downloading the content
	* @param string $data The content to download
	* @param string $mimetype The mime type to use. Defaults to detecting the type based on file extension.
	* @param string $characterSet The character set which is used in conjunction which several mime-types (html, excel extensions)
	*/
	public static function downloadContent($filename, $data, $mimetype = '', $characterSet = 'UTF-8')
	{
		self::setDownloadHeaders($filename, strlen($data), $mimetype);

		// output data
		echo $data;

		exit;
	}

	/**
	* Sets the http headers to use for forced file downloading
	*
	* @param string $filename The name of the file to prompt for download
	* @param int $filelength The length of the file in bytes
	* @param string $mimetype The mime type to use. Defaults to detecting the type based on file extension.
	* @param string $characterSet The character set which is used in conjunction which several mime-types (html, excel extensions).
	*/
	public static function setDownloadHeaders($filename, $filelength, $mimetype = '', $characterSet = 'UTF-8')
	{
		// disable output compression
		if(ini_get('zlib.output_compression')) {
			ini_set('zlib.output_compression', 'Off');
		}

		// end buffering
		while (@ob_end_clean());

		// detect the mime type to use
		if (!$mimetype) {
			$info = pathinfo($filename);
			$ext = "";

			if(isset($info['extension']))
				$ext = $info['extension'];

			switch($ext) {
				case "zip":
					$mimetype = "application/zip";
					break;
				case "rar":
					$mimetype = "application/x-rar";
					break;
				case "pdf":
					$mimetype = "application/pdf";
					break;
				case "ppt": // power point
				case "pps":
				case "pptx":
				case "ppsx":
				case "pptm":
				case "ppsm":
					$mimetype = "application/vnd.ms-powerpoint";
					break;
				case "doc":
				case "docx":
					$mimetype = "application/msword";
					break;
				case "xml": // plain text files
				case "txt":
				case "html":
					$mimetype = "text/" . $ext . "; charset=" . $characterSet;
					break;
				case "jpg": // image files
					$ext = "jpeg";
				case "jpeg":
				case "gif":
				case "tiff":
				case "png":
					$mimetype = "image/" . $ext;
					break;
				case "csv":
					$mimetype = "text/csv";
					break;
				case "xls":
				case "xlsx":
				case "xlsm":
					$mimetype = "application/vnd.ms-excel; charset=" . $characterSet;
					break;
				default:
					$mimetype = "application/octet-stream";
			}
		}

		// disable caching
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

		// set the content type
		header('Content-Type: ' . $mimetype);

		header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . $filelength);    // provide file size

		// IE fix for https
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on" && isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
			header('Cache-Control: private');
			header('Pragma: private');
		}
	}
}