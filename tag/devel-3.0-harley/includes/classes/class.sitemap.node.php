<?php

interface iISC_SITEMAP_NODE {

	public function setParent(ISC_SITEMAP_NODE $node);

	public function getParent();

	public function setUrl($url);

	public function getUrl();

	public function setLabel($label);

	public function getLabel();

	public function setAlt($alt);

	public function getAlt();

	public function setSummary($summary);

	public function getSummary();

	public function appendChild(ISC_SITEMAP_NODE $node);

	public function insertChild(ISC_SITEMAP_NODE $node, $position);

	public function getChild($index);

	public function getChildren();

	public function __construct($url = null, $label = null, $alt = null, $summary = null);
}

class ISC_SITEMAP_NODE implements iISC_SITEMAP_NODE {

	/**
	*
	* @var ISC_SITEMAP_NODE
	*/
	private $_parent;

	/**
	*
	* @var string
	*/
	private $_url = '';

	/**
	*
	* @var string
	*/
	private $_label = '';

	/**
	*
	* @var string
	*/
	private $_alt = '';

	/**
	*
	* @var string
	*/
	private $_summary = '';

	/**
	*
	* @var array
	*/
	private $_childNodes = array();

	/**
	*
	* @param ISC_SITEMAP_NODE $parent
	*/
	public function setParent(ISC_SITEMAP_NODE $parent)
	{
		$this->_parent = $parent;
	}

	/**
	*
	* @return ISC_SITEMAP_NODE
	*/
	public function getParent()
	{
		return $this->_parent;
	}

	/**
	*
	* @param string $url
	*/
	public function setUrl($url)
	{
		$this->_url = $url;
	}

	/**
	*
	* @return string
	*/
	public function getUrl()
	{
		return $this->_url;
	}

	/**
	*
	* @param string $label
	*/
	public function setLabel($label)
	{
		$this->_label = $label;
	}

	/**
	*
	* @return string
	*/
	public function getLabel()
	{
		return $this->_label;
	}

	/**
	*
	* @param string $alt
	*/
	public function setAlt($alt)
	{
		$this->_alt = $alt;
	}

	/**
	*
	* @return string
	*/
	public function getAlt()
	{
		return $this->_alt;
	}

	/**
	*
	* @param string $summary
	*/
	public function setSummary($summary)
	{
		$this->_summary = $summary;
	}

	/**
	*
	* @return string
	*/
	public function getSummary()
	{
		return $this->_summary;
	}

	/**
	*
	* @param ISC_SITEMAP_NODE $node
	*/
	public function appendChild(ISC_SITEMAP_NODE $node)
	{
		$this->_childNodes[] = $node;
		$node->setParent($this);
	}

	/**
	*
	* @param ISC_SITEMAP_NODE $node
	*/
	public function insertChild(ISC_SITEMAP_NODE $node, $position)
	{
		throw new Exception('Not yet implemented.');
	}

	/**
	*
	* @param int $index
	* @return ISC_SITEMAP_NODE
	*/
	public function getChild($index)
	{
		return $this->_childNodes[$index];
	}

	/**
	*
	* @return array
	*/
	public function getChildren()
	{
		return $this->_childNodes;
	}

	/**
	*
	* @return int
	*/
	public function countChildren()
	{
		return count($this->_childNodes);
	}

	/**
	*
	* @return string
	*/
	public function generateNodeHtml(ISC_SITEMAP_NODE_GENERATEHTMLOPTIONS $options = null)
	{
		$html = '<li>';

		$url = $this->getUrl();
		if ($url) {
			$html .= '<a href="' . ISC_SITEMAP::encodeHtml($url) . '">';
		}

		$html .= '<span>' . ISC_SITEMAP::encodeHtml($this->getLabel()) . '</span>';

		if ($url) {
			$html .= '</a>';
		}

		if ($this->countChildren()) {
			$html .= '<ul>';

			foreach ($this->getChildren() as $child) {
				$html .= $child->generateNodeHtml();
			}

			$html .= '</ul>';
		}

		$html .= '</li>';

		return $html;
	}

	/**
	*
	* @param string $url
	* @param string $label
	* @param string $alt
	* @param string $summary
	* @return ISC_SITEMAP_NODE
	*/
	public function __construct($url = null, $label = null, $alt = null, $summary = null)
	{
		if ($url !== null) {
			$this->setUrl($url);
		}

		if ($label !== null) {
			$this->setLabel($label);
		}

		if ($alt !== null) {
			$this->setAlt($alt);
		}

		if ($summary !== null) {
			$this->setSummary($summary);
		}
	}
}
