<?php

/**
 * This class implements a recursive renderer for use with sitemap tree models. Typical usage for this is for generating
 * HTML output via the template system, since the front-end template system does not easily support recursive template
 * references and does not support conditionals at all.
 *
 * @todo this was created for category flyouts, the sitemap itself should be updated to use this, too
 */
class Store_SiteMap_Renderer
{
	/** @var TEMPLATE */
	protected $_templateEngine;

	/** @var string */
	protected $_treeTemplate = 'FlyoutTree';

	/** @var string */
	protected $_nodeTemplate = 'FlyoutNode';

	/** @var string */
	protected $_rootClasses = '';

	/** @var ISC_SITEMAP_ROOT */
	protected $_siteMapTree;

	public function setTemplateEngine (TEMPLATE $value)
	{
		$this->_templateEngine = $value;
		return $this;
	}

	public function getTemplateEngine ()
	{
		if ($this->_templateEngine === null) {
			$this->_templateEngine = $GLOBALS['ISC_CLASS_TEMPLATE'];
		}
		return $this->_templateEngine;
	}

	public function setTreeTemplate ($value)
	{
		$this->_treeTemplate = (string)$value;
		return $this;
	}

	public function getTreeTemplate ()
	{
		return $this->_treeTemplate;
	}

	public function setNodeTemplate ($value)
	{
		$this->_nodeTemplate = (string)$value;
		return $this;
	}

	public function getNodeTemplate ()
	{
		return $this->_nodeTemplate;
	}

	public function setRootClasses ($value)
	{
		$this->_rootClasses = (string)$value;
		return $this;
	}

	public function getRootClasses ()
	{
		return $this->_rootClasses;
	}

	public function setSiteMapTree (ISC_SITEMAP_ROOT $value)
	{
		$this->_siteMapTree = $value;
		return $this;
	}

	public function getSiteMapTree ()
	{
		return $this->_siteMapTree;
	}

	public function render ()
	{
		return $this->_renderNode($this->getSiteMapTree());
	}

	protected function _renderNode (ISC_SITEMAP_NODE $node)
	{
		$childHtml = '';
		foreach ($node->getChildren() as $child) {
			$childHtml .= $this->_renderNode($child);
		}

		// html attributes are being generated here in php because front end templates have no conditionals yet and
		// I don't want to output blank class="" id="" etc. for 99% of nodes
		// @todo change this behaviour when the front end uses Twig or whatever
		$GLOBALS['FlyoutChildHtml'] = '';
		if ($childHtml) {
			$attributes = array();
			if ($node instanceof ISC_SITEMAP_ROOT) {
				if ($this->getRootClasses()) {
					$attributes['class'] = $this->getRootClasses();
				}
			}
			$GLOBALS['FlyoutAttributes'] = '';
			foreach ($attributes as $key => $value) {
				// don't ltrim me! see usage in FlyoutTree and FlyoutNode.html
				$GLOBALS['FlyoutAttributes'] .= ' ' . isc_html_escape($key) . '="' . isc_html_escape($value) . '"';
			}

			$GLOBALS['FlyoutChildHtml'] = $childHtml;
			$GLOBALS['FlyoutChildHtml'] = $this->getTemplateEngine()->GetSnippet($this->getTreeTemplate());
			unset($GLOBALS['FlyoutAttributes'], $attributes, $key, $value);
		}
		unset($childHtml);

		if ($node instanceof ISC_SITEMAP_ROOT) {
			// sitemap root does not have any node info of its own to show, just return child info
			$childHtml = $GLOBALS['FlyoutChildHtml'];
			unset($GLOBALS['FlyoutChildHtml']);
			return $childHtml;
		}

		$GLOBALS['FlyoutNodeLabel'] = isc_html_escape($node->getLabel());
		$GLOBALS['FlyoutNodeUrl'] = isc_html_escape($node->getUrl());
		$html = $this->getTemplateEngine()->GetSnippet($this->getNodeTemplate());
		unset($GLOBALS['FlyoutNodeLabel'], $GLOBALS['FlyoutNodeUrl'], $GLOBALS['FlyoutChildHtml']);

		return $html;
	}
}
