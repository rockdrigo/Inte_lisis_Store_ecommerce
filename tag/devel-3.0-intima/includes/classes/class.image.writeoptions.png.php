<?php

class ISC_IMAGE_WRITEOPTIONS_PNG extends ISC_IMAGE_WRITEOPTIONS {

	protected $_compression;

	protected $_filters;

	/**
	*
	* @param int $compression PNG compression level as a value between 0 (no compression) and 9 (maximum compression)
	* @param int $filters bitmask of filters from PNG_FILTER_XXX constants to use when saving
	* @return ISC_IMAGE_WRITEOPTIONS_PNG
	*/
	public function __construct($compression = 9, $filters = PNG_ALL_FILTERS)
	{
		$this->setImageType(IMAGETYPE_PNG);
		$this->setCompression($compression);
		$this->setFilters($filters);
	}

	/**
	* Returns the PNG compression level
	*
	* @return int
	*/
	public function getCompression()
	{
		return $this->_compression;
	}

	/**
	* Sets the PNG compression level as a value between 0 (no compression) and 9 (maximum compression)
	*
	* @param int $compression
	*/
	public function setCompression($compression)
	{
		$this->_compression = (int)$compression;
	}

	/**
	* Returns a bitmask value based on values of PNG_FILTER_XXX constants
	*
	* @return int
	*/
	public function getFilters()
	{
		return $this->_filters;
	}

	/**
	* Sets the PNG filters to use when saving based on a bitmask of PNG_FILTER_XXX constants
	*
	* @param int $filters
	*/
	public function setFilters($filters)
	{
		$this->_filters = (int)$filters;
	}
}
