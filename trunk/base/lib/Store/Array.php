<?php

/**
* This class is a container for shopping-cart-specific array methods.
*/
class Store_Array extends Interspire_Array
{
	/**
	 * Case insensitive in_array
	 *
	 * @todo refactor
	 * @param mixed $needle
	 * @param mixed $haystack
	 * @return bool
	 */
	public static function inArrayCI($needle, $haystack)
	{
		return in_array(isc_strtolower($needle), array_map('isc_strtolower', $haystack));
	}

	/**
	 * Case insensitive array_search
	 *
	 * @todo refactor
	 * @param mixed $needle
	 * @param mixed $haystack
	 * @return mixed Key on success, FALSE on no match
	 */
	public static function searchCI($needle, $haystack)
	{
		return array_search(isc_strtolower($needle), array_map('isc_strtolower', $haystack));
	}
}
