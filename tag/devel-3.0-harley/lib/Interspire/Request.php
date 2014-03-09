<?php

/**
* This class implements an abstraction of GET/POST/REQUEST elements, removing the need to manually perform isset tests by supplying default values.
*/
class Interspire_Request
{
	/**
	* Returns $_GET[$key] if it exists, otherwise $default
	*
	* @param string $key The variable name from the GET collection to return
	* @param mixed $default The default value to return if $key does not exist
	* @return mixed
	*/
	public static function get($key, $default = '')
	{
		if (isset($_GET[$key])) {
			return $_GET[$key];
		}
		return $default;
	}

	/**
	* Returns $_POST[$key] if it exists, otherwise $default
	*
	* @param string $key The variable name from the POST collection to return
	* @param mixed $default The default value to return if $key does not exist
	* @return mixed
	*/
	public static function post($key, $default = '')
	{
		if (isset($_POST[$key])) {
			return $_POST[$key];
		}
		return $default;
	}

	/**
	* Returns $_REQUEST[$key] if it exists, otherwise $default
	*
	* @param string $key The variable name from the REQUEST collection to return
	* @param mixed $default The default value to return if $key does not exist
	* @return mixed
	*/
	public static function request($key, $default = '')
	{
		if (isset($_REQUEST[$key])) {
			return $_REQUEST[$key];
		}
		return $default;
	}

	/**
	* Returns $_SERVER[$key] if it exists, otherwise $default
	*
	* @param string $key The variable name from the SERVER collection to return
	* @param mixed $default The default value to return if $key does not exist
	* @return mixed
	*/
	public static function server($key, $default = '')
	{
		if (isset($_SERVER[$key])) {
			return $_SERVER[$key];
		}
		return $default;
	}
}
