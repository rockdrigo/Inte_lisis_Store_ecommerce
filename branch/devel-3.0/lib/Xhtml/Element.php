<?php

/**
* This abstract class repesents an XHTML element
*/
abstract class Xhtml_Element
{
	/**
	* Return the provided string encoded as XHTML text
	*
	* @param mixed $string
	* @return string
	*/
	public static function encodeXhtmlText($string)
	{
		return isc_html_escape($string);
	}

	public static function encodeXhtmlTagName($string)
	{
		// @todo check which chars are valid in a tag name
		return isc_html_escape($string);
	}

	public static function encodeXhtmlAttributeName($string)
	{
		// @todo check which chars are valid in an attribute name
		return isc_html_escape($string);
	}

	public static function encodeXhtmlAttributeValue($string)
	{
		// @todo check which chars are valid in an attribute value
		return isc_html_escape($string);
	}

	protected $_short = false;
	protected $_tag;
	protected $_attributes = array();
	protected $_text = '';
	protected $_children = array();

	/**
	* Returns the tag name
	*
	* @return string
	*/
	public function tag()
	{
		return $this->_tag;
	}

	/**
	* Returns whether this element is short (self-closing) or not, such as <br />
	*
	* @return bool
	*/
	public function short()
	{
		return $this->_short;
	}

	/**
	* Set or return text content
	*
	* @param string $value Provide value to set to, or leave undefined to get current value
	* @return Xhtml_Element Returns $this on set, otherwise string value on get
	*/
	public function text($value = '')
	{
		if (func_num_args() < 1) {
			return $this->_text;
		}
		$this->_text = (string)$value;
		return $this;
	}

	/**
	* Set or return value of a specific attribute
	*
	* @param string $name Attribute name, will be forced to lowercase
	* @param string $value Provide value to set to, or leave undefined to get current value
	* @return Xhtml_Element Returns $this on set, otherwise string value on get
	*/
	public function attribute($name, $value = '')
	{
		$name = strtolower($name);
		if (func_num_args() == 1) {
			return $this->_attributes[$name];
		}
		$this->_attributes[$name] = (string)$value;
		return $this;
	}

	/**
	* Return array of attribute name => attribute value
	*
	* @return array
	*/
	public function attributes()
	{
		return $this->_attributes;
	}

	public function appendChild(Xhtml_Element $element)
	{
		$this->_children[] = $element;
		return $this;
	}

	/**
	* Render the current Xhtml_Element and return as string
	*
	* @return string
	*/
	public function render()
	{
		ob_start();
		$this->display();
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/**
	* Echo the Xhtml_Element directly to output stream
	*
	* @return void
	*/
	public function display()
	{
		echo '<' . self::encodeXhtmlTagName($this->tag());

		foreach ($this->_attributes as $attribute => $value) {
			echo ' ' . self::encodeXhtmlAttributeName($attribute) . '="' . self::encodeXhtmlAttributeValue($value) . '"';
		}

		if ($this->_short) {
			echo ' />';
			return;
		}

		echo '>';
		echo self::encodeXhtmlText($this->text());

		foreach ($this->_children as /** @var Xhtml_Element */$child) {
			$child->display();
		}

		echo '</' . self::encodeXhtmlTagName($this->tag()) . '>';
	}
}
