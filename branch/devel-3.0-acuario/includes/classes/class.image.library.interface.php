<?php

interface ISC_IMAGE_LIBRARY_INTERFACE {

	// these are methods that the library specific implementations of this interface should provide

	public static function isTextSupported();

	public static function isFileSupported($filePath);

	public static function isLibrarySupported();

	public static function getSupportedImageTypes();

	public function loadImageFileToScratch();

	public function saveScratchToFile($destionationFilePath, ISC_IMAGE_WRITEOPTIONS $imageWriteOptions);

	public function saveScratchToStream($stream, ISC_IMAGE_WRITEOPTIONS $imageWriteOptions);

	public function saveScratchToOutput(ISC_IMAGE_WRITEOPTIONS $imageWriteOptions);

	public function resampleScratch($width, $height);

	// these are methods that ISC_IMAGE_BASECLASS will provide, but we define them in the interface to assist with code insight in php editors, and just in case the implementatin of this interface does not extend the base class for some reason, this enforces the methods

	public function getFileExists();

	public function getModifiedTime();

	public function getLastModifiedTime();

	public function getWidth();

	public function getHeight();

	public function getImageType();

	public function isScratchTrueColor();

	public function sharpenScratch();

	/**
	* @return string
	*/
	public function getImageTypeExtension($includeDot = true);

	public function getChannels();

	public function getBits();

	public function getImageInfo();

	public function getIPTC();

	public function getEXIF();

	public function setFilePath($filePath = null, $updateImageInformation = true);

	public function getFilePath();

	public function resampleScratchLongEdge($length);

	public function resampleScratchToMaximumDimensions($maximumWidth, $maximumHeight);
}
