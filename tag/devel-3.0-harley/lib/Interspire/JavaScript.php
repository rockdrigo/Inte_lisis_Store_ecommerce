<?php

/**
* This class contains methods for various javascript-related processes.
*/
class Interspire_JavaScript
{
	/**
	* The translation table used by encode()
	*
	* @var array
	*/
	public static $_encodeTable = array(
		"\\"	=>	"\\\\",
		"\n"	=>	"\\n",
		"\r"	=>	"\\r",
		"'"		=>	"\\'",
		"\""	=>	"\\\"",
		"&"		=>	"\\x26",
		"<"		=>	"\\x3C",
		">"		=>	"\\x3E",
	);

	/**
	* Takes the supplied PHP string and encodes it for safe output as a part of a javascript string.
	*
	* @param string $string
	* @return string JavaScript-escaped/encoded string NOT including any quotation marks.
	*/
	public static function encode($string)
	{
		return strtr($string, self::$_encodeTable);
	}
}
