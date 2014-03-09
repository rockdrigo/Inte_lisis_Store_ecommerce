<?php

/**
* This class represents a common base for elements which commonly only contain a text node as their only child node.
*/
abstract class Xhtml_TextElement extends Xhtml_Element
{
	public function __construct($text = '')
	{
		$this->text($text);
	}
}
