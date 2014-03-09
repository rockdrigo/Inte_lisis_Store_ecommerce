<?php

class Interspire_Template_Config implements ArrayAccess
{
	public function offsetExists($name)
	{
		return true;
	}

	public function offsetGet($name)
	{
		return GetConfig($name);
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
