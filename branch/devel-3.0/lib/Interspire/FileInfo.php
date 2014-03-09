<?php

/**
 * Extend SplFileInfo to set appropriate file and fileinfo classes upon
 * construction.
 * 
 * @package Interspire
 * @subpackage FileInfo
 */
class Interspire_FileInfo extends SplFileInfo
{
	/**
	 * Constructs the file info object and sets defaults.
	 * 
	 * @param string $file
	 * @return Interspire_FileInfo
	 */
	public function __construct($file)
	{
		// construct SplFileInfo to set defaults
		parent::__construct($file);

		// set classes to use
		$this->setFileClass('Interspire_File');
		$this->setFileInfoClass('Interspire_FileInfo');
	}
}