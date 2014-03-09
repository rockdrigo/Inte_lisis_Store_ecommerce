<?php

/**
* This class represents an OPTION element in XHTML
*/
class Xhtml_Option extends Xhtml_Element
{
	protected $_tag = 'option';

	/**
	* Sets or returns the value attribute of this Option element - acts as shortcut to attribute('value', $value)
	*
	* @param string $value Provide value to set to, or leave undefined to get current value
	* @return Html_Element Returns $this on set, otherwise string value on get
	*/
	public function value($value = '')
	{
		if (func_num_args() < 1) {
			return $this->attribute('value');
		}
		return $this->attribute('value', $value);
	}

	public function __construct($text, $value = null)
	{
		if ($value === null) {
			$value = $text;
		}

		$this->text($text)->value($value);
	}
}
