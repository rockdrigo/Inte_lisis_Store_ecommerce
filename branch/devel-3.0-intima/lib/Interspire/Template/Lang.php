<?php

class Interspire_Template_Lang implements ArrayAccess
{
	public function offsetExists($name)
	{
		return true;
	}

	public function offsetGet($name)
	{
		return getLang($name);
	}

	public function offsetSet($name, $value)
	{
		return true;
	}

	public function offsetUnset($name)
	{
		return true;
	}
}
