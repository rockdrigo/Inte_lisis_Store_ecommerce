<?php

class ISC_IMAGE_WRITEOPTIONS_JPEG extends ISC_IMAGE_WRITEOPTIONS {

	protected $_quality;

	/**
	*
	* @param float $quality JPEG compression quality as a percentage value between 0 (worst) and 100 (best)
	* @return ISC_IMAGE_WRITEOPTIONS_JPEG
	*/
	public function __construct($quality = 100)
	{
		$this->setImageType(IMAGETYPE_JPEG);
		$this->setQuality((float)$quality);
	}

	/**
	* Returns the JPEG compression quality
	*
	* @return float
	*/
	public function getQuality()
	{
		return $this->_quality;
	}

	/**
	* Sets the JPEG compression quality
	*
	* @param float $quality
	*/
	public function setQuality($quality)
	{
		$this->_quality = (float)$quality;
	}
}
