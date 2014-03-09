<?php

class ISC_IMAGE_BASECLASS_FILENOTFOUND_EXCEPTION extends Exception {

	public function __construct($filePath)
	{
		parent::__construct("The image file '" . $filePath . "' was not found.");
	}
}

class ISC_IMAGE_BASECLASS_INVALIDIMAGE_EXCEPTION extends Exception {

	public function __construct($filePath)
	{
		parent::__construct("The image file '" . $filePath . "' is not a valid image, or could not be read.");
	}
}

/**
* General image information class wrapping the functionality of getimagesize() to obtain image information in a image-library-neutral manner (as getimagesize does not require any library)
*/
class ISC_IMAGE_BASECLASS {

	protected $_filePath;

	protected $_lastModifiedTime;

	protected $_width;

	protected $_height;

	protected $_imageType;

	protected $_channels;

	protected $_bits;

	protected $_imageInfo;

	protected $_iptc;

	protected $_exif;

	protected $_alternateText;

	protected $_caption;

	protected $_description;

	protected $_visible;

	protected $_productId;

	protected $_isThumbnail;

	public function __construct($filePath = null, $getImageInformation = true)
	{
		if ($filePath !== null) {
			$this->setFilePath($filePath, false);
			if ($getImageInformation) {
				$this->_updateImageInformation(true);
			}
		}
	}

	protected function _updateImageInformation($bypassModifiedTimeCheck = false)
	{
		if (!$this->getFileExists()) {
			throw new ISC_IMAGE_BASECLASS_FILENOTFOUND_EXCEPTION($this->getFilePath());
		}

		if (!$bypassModifiedTimeCheck) {
			$modifiedTime = $this->getModifiedTime();
			if ($modifiedTime === $this->_lastModifiedTime) {
				// file has not changed since it was last read -- abort
				return;
			} else {
				// file has changed, store the new modified time and continue;
				$this->_lastModifiedTime = $modifiedTime;
			}
		}

		// first call getimagesize with path only to determine valid image and actual dimensions
		$imageSize = getimagesize($this->getFilePath());
		if ($imageSize === false) {
			throw new ISC_IMAGE_BASECLASS_INVALIDIMAGE_EXCEPTION($this->getFilePath());
		}

		// next call again with info parameter, see ISC-492: image with invalid headers will return false when getimagesize is called with second parameter, despite being an otherwise valid image
		$imageInfo = array();
		if (!getimagesize($this->getFilePath(), $imageInfo)) {
			$imageInfo = array();
		}

		$this->_width = $imageSize[0];
		$this->_height = $imageSize[1];
		$this->_imageType = $imageSize[2];
		$this->_channels = @$imageSize['channels'];
		$this->_bits = $imageSize['bits'];
		$this->_imageInfo = $imageInfo;

		$this->_iptc = null;
		$this->_exif = null;

		if (isset($this->_imageInfo['APP13'])) {
			$this->_iptc = iptcparse($this->_imageInfo['APP13']);
		}

		if (function_exists('exif_read_data') && function_exists('mb_internal_encoding')) {
			// for exif to work properly, mbstring support must be enabled, and must be loaded by php.ini before exif
			// http://php.net/exif_read_data
			$this->_exif = @exif_read_data($this->getFilePath(), null, true);
		}
	}

	public function getFileExists()
	{
		return file_exists($this->getFilePath());
	}

	public function getModifiedTime()
	{
		return filemtime($this->getFilePath());
	}

	public function getLastModifiedTime()
	{
		return $this->_lastModifiedTime;
	}

	public function getWidth()
	{
		return $this->_width;
	}

	public function getHeight()
	{
		return $this->_height;
	}

	public function getImageType()
	{
		return $this->_imageType;
	}

	public function getImageTypeExtension($includeDot = true)
	{
		$ext = ISC_IMAGE_LIBRARY_FACTORY::getExtensionForImageType($this->getImageType());

		if ($includeDot) {
			$ext = '.' . $ext;
		}

		return $ext;
	}

	public function getChannels()
	{
		return $this->_channels;
	}

	public function getBits()
	{
		return $this->_bits;
	}

	public function getImageInfo()
	{
		return $this->_imageInfo;
	}

	public function getIPTC()
	{
		return $this->_iptc;
	}

	public function getEXIF()
	{
		return $this->_exif;
	}

	public function setFilePath($filePath = null, $updateImageInformation = true)
	{
		$lastFilePath = $this->getFilePath();
		$this->_filePath = $filePath;

		if ($this->getFileExists()) {
			$this->_lastModifiedTime = filemtime($filePath);
		} else {
			$this->_lastModifiedTime = null;
		}

		if ($updateImageInformation) {
			$force = ($lastFilePath !== $this->getFilePath());
			$this->_updateImageInformation($force);
		}
	}

	public function getFilePath()
	{
		return $this->_filePath;
	}

	/**
	* Resamples an image to a defined maximum width or height -- this is exactly the same as calling resampleScratchToMaximumDimensions with the exact same value as both width and height
	*
	* @param int $length
	*/
	public function resampleScratchLongEdge($length)
	{
		return $this->resampleScratchToMaximumDimensions($length, $length);
	}

	/**
	* Resamples an image to defined maximum width and height values
	*
	* @param int $maximumWidth
	* @param int $maximumHeight
	*/
	public function resampleScratchToMaximumDimensions($maximumWidth, $maximumHeight)
	{
		// note: as this is general purpose image code, this should also size up -- implementation-specific routines (such as in ISC_PRODUCT_IMAGE) should check whether an image needs to be resized before it gets here
		$ratio = $this->_width / $this->_height;

		$width = $maximumWidth;
		$height = (int)round($width / $ratio);
		if ($height > $maximumHeight) {
			$height = $maximumHeight;
			$width = (int)round($height * $ratio);
		}

		return $this->resampleScratch($width, $height);
	}
}
