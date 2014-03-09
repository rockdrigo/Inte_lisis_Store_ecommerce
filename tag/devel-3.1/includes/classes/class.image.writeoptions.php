<?php

class ISC_IMAGE_WRITEOPTIONS {

	protected $_imageType;

	public function getImageType()
	{
		return $this->_imageType;
	}

	protected function setImageType($imageType)
	{
		$this->_imageType = $imageType;
	}
}
