<?php

class ISC_IMAGE_LIBRARY_GD_UNSUPPORTEDIMAGETYPE_EXCEPTION extends Exception {

	public function __construct($imageType)
	{
		parent::__construct("Image type '" . $imageType . "' is not supported by the GD image library in the current installation of PHP.");
	}
}

class ISC_IMAGE_LIBRARY_GD_IMAGECREATEFROMFILE_EXCEPTION extends Exception {

	public function __construct($imageType, $filePath)
	{
		parent::__construct("A GD error occurred while trying to load " . $filePath . " (detected as image type " . $imageType . ")");
	}
}

class ISC_IMAGE_LIBRARY_GD extends ISC_IMAGE_BASECLASS implements ISC_IMAGE_LIBRARY_INTERFACE {

	private $_scratchResource;

	/**
	* Storage for the result of getSupportedImageTypes so it does not have to be calculated on each call
	*
	* @var mixed
	*/
	private static $_imageTypes;

	/**
	* Returns an array of IMAGETYPE_XXX constants supported by the current installation of GD
	*
	* @return array
	*/
	public static function getSupportedImageTypes()
	{
		if (self::$_imageTypes === null) {
			$types = imagetypes();
			// imagetypes returns a bitfield, convert it to an array

			self::$_imageTypes = array();

			if ($types & IMG_GIF) {
				self::$_imageTypes[] = IMAGETYPE_GIF;
			}

			if ($types & IMG_JPG) {
				self::$_imageTypes[] = IMAGETYPE_JPEG;
			}

			if ($types & IMG_PNG) {
				self::$_imageTypes[] = IMAGETYPE_PNG;
			}

			if ($types & IMG_WBMP) {
				self::$_imageTypes[] = IMAGETYPE_WBMP;
			}

			if ($types & IMG_XPM) {
				self::$_imageTypes[] = IMAGETYPE_XBM;
			}
		}

		return self::$_imageTypes;
	}

	/**
	* Determines if text rendering support of any kind is available.
	*
	* @return bool True if text rendering is available, otherwise false.
	*/
	public static function isTextSupported()
	{
		$gd = gd_info();
		return $gd['FreeType Support'];
	}

	/**
	* Determines if the current library is available to use at all on this server
	*
	* @return bool True if the library is available, otherwise false.
	*/
	public static function isLibrarySupported()
	{
		return function_exists('gd_info');
	}

	/**
	* Determines if the file at $filePath is supported by this library.
	*
	* @param string $filePath
	* @return bool True if the file is supported, otherwise false.
	*/
	public static function isFileSupported($filePath)
	{

		$image = @getimagesize($filePath);
		if ($image === false) {
			return false;
		}

		$imageType = $image[2];

		// raw list of types supported by gd in this build of php
		$supportedTypes = imagetypes();

		// check against image types this module has been coded for
		switch ($image[2]) {
			case IMAGETYPE_GIF:
				return ($supportedTypes & IMG_GIF);

			case IMAGETYPE_PNG:
				return ($supportedTypes & IMG_PNG);

			case IMAGETYPE_JPEG:
				return ($supportedTypes & IMG_JPEG);
		}

		return false;
	}

	/**
	* Load the current file from disk to in-memory resource
	*
	* @return void
	*/
	public function loadImageFileToScratch()
	{
		$filePath = $this->getFilePath();
		$imageType = $this->getImageType();

		// Attempt to increase the memory limit before loading in the image, to ensure it'll fit in memory
		ISC_IMAGE_LIBRARY_FACTORY::setImageFileMemLimit($filePath);

		switch ($imageType) {
			case IMAGETYPE_GIF:
				$this->_scratchResource = @imagecreatefromgif($filePath);
				if ($this->getScratchResource()) {
					imagecolortransparent($this->getScratchResource());
				}
				break;

			case IMAGETYPE_PNG:
				$this->_scratchResource = @imagecreatefrompng($filePath);
				if ($this->_scratchResource) {
					// this sets up alpha transparency support when manipulating and saving the in-memory image
					imagealphablending($this->getScratchResource(), false);
					imagesavealpha($this->getScratchResource(), true);
				}
				break;

			case IMAGETYPE_JPEG:
				$this->_scratchResource = @imagecreatefromjpeg($filePath);
				break;

			default:
				throw new ISC_IMAGE_LIBRARY_GD_UNSUPPORTEDIMAGETYPE_EXCEPTION($imageType);
		}

		$this->_updateImageInformation(true);

		if (!$this->getScratchResource()) {
			throw new ISC_IMAGE_LIBRARY_GD_IMAGECREATEFROMFILE_EXCEPTION($imageType, $filePath);
		}
	}

	/**
	* Write the current scratch to a file using the file format specs set in $imageWriteOptions
	*
	* @param string $destinationFilePath
	* @param ISC_IMAGE_WRITEOPTIONS $imageWriteOptions
	*/
	public function saveScratchToFile($destinationFilePath, ISC_IMAGE_WRITEOPTIONS $imageWriteOptions)
	{
		$imageType = $imageWriteOptions->getImageType();

		switch ($imageType) {

			case IMAGETYPE_JPEG:
				imagejpeg($this->getScratchResource(), $destinationFilePath, (int)$imageWriteOptions->getQuality());
				break;

			case IMAGETYPE_PNG:
				if (version_compare(PHP_VERSION, '5.1.3', '>=')) {
					// filters parameter was added in 5.1.3
					imagepng($this->getScratchResource(), $destinationFilePath, (int)$imageWriteOptions->getCompression(), (int)$imageWriteOptions->getFilters());
				} else if (version_compare(PHP_VERSION, '5.1.2', '>=')) {
					// quality parameter was added in 5.1.2
					imagepng($this->getScratchResource(), $destinationFilePath, (int)$imageWriteOptions->getCompression());
				} else {
					imagepng($this->getScratchResource(), $destinationFilePath);
				}
				break;

			case IMAGETYPE_GIF:
				imagegif($this->getScratchResource(), $destinationFilePath);
				break;

			default:
				throw new ISC_IMAGE_LIBRARY_GD_UNSUPPORTEDIMAGETYPE_EXCEPTION($imageType);
				break;
		}

		isc_chmod($destinationFilePath, ISC_WRITEABLE_FILE_PERM);
	}

	/**
	* Write the current scratch to the output buffer using the file format specs set in $imageWriteOptions
	*
	* @param ISC_IMAGE_WRITEOPTIONS $imageWriteOptions
	*/
	public function saveScratchToOutput(ISC_IMAGE_WRITEOPTIONS $imageWriteOptions)
	{
		$this->saveScratchToFile(null, $imageWriteOptions);
	}

	/**
	* Write the current scratch to a stream handler opened by fopen() using the file format specs set in $imageWriteOptions
	*
	* @param resource $handle
	* @param ISC_IMAGE_WRITEOPTIONS $imageWriteOptions
	*/
	public function saveScratchToStream($handle, ISC_IMAGE_WRITEOPTIONS $imageWriteOptions)
	{
		// I can't find a native way of doing this with the gd extension except for capturing the buffer and using saveScratchToOutput
		throw new Exception('saveScratchToStream is not supported by the GD module');
	}

	/**
	* Rotates the scratch by given degrees. No cropping will occurr - the canvas will be resized to fit the new boundaries of the image if necessary.
	*
	* @param float $degrees Angle in degrees to rotate the image. Positive angles indicate a clockwise rotation, negative values are counter-clockwise.
	* @param mixed $background Specifies the color of the uncovered zone after the rotation
	*/
	public function rotateScratch($degrees, $background = 0)
	{
		$this->_scratchResource = imagerotate($this->_scratchResource, $degrees, $background);
		$this->_width = imagesx($this->getScratchResource());
		$this->_height = imagesy($this->getScratchResource());
	}

	public function isScratchTrueColor()
	{
		return imageistruecolor($this->getScratchResource());
	}

	/**
	* Applies sharpening to the current scratch
	*
	* @return bool Returns TRUE on success or FALSE on failure
	*/
	public function sharpenScratch()
	{
		// this needs a $strength parameter but I don't know how matrix convultions work

		$matrix = array(
			array(-1, -1, -1),
			array(-1, 16, -1),
			array(-1, -1, -1)
		);

		$divisor = 8;
		$offset = 0;

		return imageconvolution($this->getScratchResource(), $matrix, $divisor, $offset);
	}

	/**
	* Create a blank true-colour image resource with alpha settings enabled
	*
	* @param int $width
	* @param int $height
	* @return resource
	*/
	protected function _getBlankImageResource($width, $height)
	{
		$resource = imagecreatetruecolor($width, $height);
		imagealphablending($resource, false);
		imagesavealpha($resource, true);
		return $resource;
	}

	/**
	* Resample the scratch to a specific width and height -- will stretch the image, ignoring aspect ratios. To keep aspect ratios intact, use other functions like resampleScratchLongEdge and resampleScratchToMaximumDimensions
	*
	* @param mixed $width
	* @param mixed $height
	*/
	public function resampleScratch($width, $height)
	{
		if ($this->isScratchTrueColor()) {
			$temp = $this->_getBlankImageResource($width, $height);
		} else {
			// determine fill background, transparent if possible
			$fill = imagecolortransparent($this->getScratchResource());
			if ($fill === -1) {
				// no transparency found, find the closest colour to white in the palette and use that as the background
				$fill = imagecolorclosest($this->getScratchResource(), 255, 255, 255);
			}

			$temp = imagecreate($width, $height);
			imagepalettecopy($temp, $this->getScratchResource());
			imagecolortransparent($temp, imagecolortransparent($this->getScratchResource())); // set transparency of new image to same as old image
			imagefill($temp, 0, 0, $fill);
		}

		imagecopyresampled($temp, $this->getScratchResource(), 0, 0, 0, 0, $width, $height, $this->_width, $this->_height);

		$this->_scratchResource = $temp;
		$this->_width = $width;
		$this->_height = $height;
	}

	/**
	* Returns a GD image resource for the current scratch
	*
	*/
	public function getScratchResource()
	{
		return $this->_scratchResource;
	}

	/**
	* Overlay another GD resource onto the current scratch at the given offset
	*
	* @param ISC_IMAGE_LIBRARY_GD $image
	* @param mixed $offsetX
	* @param mixed $offsetY
	*/
	public function overlayImage(ISC_IMAGE_LIBRARY_GD $image, $offsetX, $offsetY)
	{
		// when watermarking is implemented this may need to be re-examined for when gifs or 8bit pngs are used

		$overlayResource = $image->getScratchResource();
		$overlayWidth = $image->getWidth();
		$overlayHeight = $image->getHeight();

		if ($offsetX < 0) {
			$offsetX = $this->getWidth() + $offsetX - $overlayWidth;
		}

		if ($offsetY < 0) {
			$offsetY = $this->getHeight() + $offsetY - $overlayHeight;
		}

		imagecopyresampled($this->getScratchResource(), $overlayResource, $offsetX, $offsetY, 0, 0, $overlayWidth, $overlayHeight, $overlayWidth, $overlayHeight);
	}
}
