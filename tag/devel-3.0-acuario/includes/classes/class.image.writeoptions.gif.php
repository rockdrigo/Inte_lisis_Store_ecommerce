<?php

class ISC_IMAGE_WRITEOPTIONS_GIF extends ISC_IMAGE_WRITEOPTIONS {

	public function __construct()
	{
		$this->setImageType(IMAGETYPE_GIF);
	}

	// currently this is coded against GD which has no extra options when writing a gif
}
