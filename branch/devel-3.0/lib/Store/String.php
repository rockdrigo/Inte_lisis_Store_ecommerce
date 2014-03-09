<?php

/**
* This class is a container for shopping-cart-specific array methods.
*/
class Store_String extends Interspire_String
{
	/**
	* Cuts the provided string to the specified length, applying a suffix if necessary, using the store's current character set.
	*
	* Usage:
	* $str = 'alpha beta gamma';
	* $str = Store_String::rightTruncate($str, 10);
	* // $str === 'alpha b...';
	*
	* @param string $str
	* @param int $length
	* @param string $suffix
	* @return string
	*/
	public static function rightTruncate($str, $length, $suffix = '...')
	{
		$strLength = isc_strlen($str);
		if ($strLength <= $length) {
			return $str;
		}

		$suffixLength = isc_strlen($suffix);
		return isc_substr($str, 0, $length - $suffixLength) . $suffix;
	}
}
