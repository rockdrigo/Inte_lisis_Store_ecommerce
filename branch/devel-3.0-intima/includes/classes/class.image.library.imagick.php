<?php

/**
* Stub class to support image magick as image manipulation library -- not yet implemented
*/

class ISC_IMAGE_LIBRARY_IMAGICK extends ISC_IMAGE_BASECLASS implements ISC_IMAGE_LIBRARY_INTERFACE {

	public static function isTextSupported()
	{
		throw new Exception('Not Yet Implemented');
	}

	public static function getSupportedImageTypes()
	{
		return array();
	}

	public static function isFileSupported($filePath)
	{
		return false;
	}

	public static function isLibrarySupported()
	{
		return false;
	}

	public function loadImageFileToScratch()
	{
		throw new Exception('Not Yet Implemented');
	}

	public function saveScratchToFile($destinationFilePath, ISC_IMAGE_WRITEOPTIONS $imageWriteOptions)
	{
		throw new Exception('Not Yet Implemented');
	}

	public function resampleScratch($width, $height)
	{
		throw new Exception('Not Yet Implemented');
	}

	public function saveScratchToStream($stream, ISC_IMAGE_WRITEOPTIONS $imageWriteOptions)
	{
		throw new Exception('Not Yet Implemented');
	}

	public function saveScratchToOutput(ISC_IMAGE_WRITEOPTIONS $imageWriteOptions)
	{
		throw new Exception('Not Yet Implemented');
	}

	public function isScratchTrueColor()
	{
		throw new Exception('Not Yet Implemented');
	}

	public function sharpenScratch()
	{
		throw new Exception('Not Yet Implemented');
	}
}
