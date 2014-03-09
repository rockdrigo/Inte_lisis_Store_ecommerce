<?php

interface iISC_SITEMAP_MODEL {

	/**
	*
	* @return string
	*/
	public function getHeading();

	/**
	*
	* @param int $limit
	* @param int $offset
	* @return ISC_SITEMAP_NODE
	*/
	public function getTree($limit = null, $offset = null);

	/**
	*
	* @return int
	*/
	public function countAll();

	/**
	*
	* return string
	*/
	public function getSubsectionUrl();

	public function setMaximumDepth ($value);

	public function getMaximumDepth ();
}


class ISC_SITEMAP_MODEL {

	protected $_maximumDepth = ISC_NESTEDSET_DEPTH_ALL;

	/**
	 * Set the maximum depth for a tree model, only currently applies to categories and pages. The default is no limit.
	 *
	 * @param int $value
	 * @return ISC_SITEMAP_MODEL
	 */
	public function setMaximumDepth ($value)
	{
		$this->_maximumDepth = (int)$value;
		return $this;
	}

	/**
	 * Get the maximum depth for a tree model, only currently applies to categories and pages. The default is no limit.
	 *
	 * @return int
	 */
	public function getMaximumDepth ()
	{
		return $this->_maximumDepth;
	}
}
