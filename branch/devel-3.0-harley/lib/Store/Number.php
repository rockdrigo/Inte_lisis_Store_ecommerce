<?php

/**
* This class is a container for shopping-cart-specific number methods.
*/
class Store_Number extends Interspire_Number
{
	/**
	 * Apply a numeric suffix to a number (eg: 1 => 1st, 2 => 2nd, etc)
	 *
	 * Function will apply the numeric suffix to the integer $int
	 *
	 * @access public
	 * @param int $int The numerical value to apped the suffix to
	 * @return string The integer value with the appended suffix on success, unchanged value on failure
	 */
	public static function addOrdinalSuffix($int)
	{
		if (!is_numeric($int)) {
			return $int;
		}

		$int = (string)(int)$int;

		if (substr($int, -1) == '1' && substr($int, -2) !== '11') {
			$ext = GetLang('DateDaySt');
		} else if (substr($int, -1) == '2' && substr($int, -2) !== '12') {
			$ext = GetLang('DateDayNd');
		} else if (substr($int, -1) == '3' && substr($int, -2) !== '13') {
			$ext = GetLang('DateDayRd');
		} else {
			$ext = GetLang('DateDayTh');
		}

		return $int . $ext;
	}

	/**
	 * Check to see if value is overlapping
	 *
	 * Function will check to see if numeric value $needle is overlapping in the array of values $overlap array. The $overlap
	 * array can either be an array of value or an array of 2 arrays, with each sub-array conatining values.
	 *
	 * EG: Array of values. $needle will be checked to see if it exists within that array (basically returning in_array())
	 *
	 *     $overlap = array(1, 5, 16, 22);
	 *
	 * EG: Array of 2 arrays. $needle will be checked to see if it exists between at element 0 of both arrays, then check
	 *     element 1 of both arrays, etc. If one of the elements is missing then basically check to see if $needle equals
	 *     the remaining element.
	 *
	 *     $overlap = array(
	 *                      array(1, 6, '', 18, 24),
	 *                      array(4, 11, 16, 22, ''),
	 *                );
	 *
	 * @access public
	 * @param int $needle The search needle
	 * @param array $haystack The arry haystack to search in
	 * @return mixed 1 if $needle does overlap, 0 if there is no overlapping, FALSE on error
	 */
	public static function isOverlapping($needle, $haystack)
	{
		if (!is_numeric($needle) || !is_array($haystack)) {
			return false;
		}

		// Make sure that if we are using sub arrays that we have 2 of them
		if (count($haystack) > 1 && (!is_array($haystack[0]) || !is_array($haystack[0]))) {
			return false;
		}

		// If we have no sub arrays then just use the in_array() function
		if (!is_array($haystack[0])) {
			return (int)in_array($needle, $haystack);
		}

		// Else we loop through the sub arrays to see if we are overlapping
		$fromRange = array();
		$toRange = array();
		$total = max(count($haystack[0]), count($haystack[1]));

		// This loop will filter our haystack
		for ($i=0; $i<$total; $i++) {

			// Filter out any blank ranges
			if ((!array_key_exists($i, $haystack[0]) || !isId($haystack[0][$i])) && (!array_key_exists($i, $haystack[1]) || !isId($haystack[1][$i]))) {
				continue;
			}

			// If the beginning of this range is empty then use the previous end range number plus 1
			if (!array_key_exists($i, $haystack[0]) || !isId($haystack[0][$i])) {
				if (!empty($toRange)) {
					$haystack[0][$i] = $toRange[count($toRange)-1]+1;
				} else {
					$haystack[0][$i] = 0;
				}
			}

			// If the end of our range is empty then use the next available beginning range minus 1
			if (!array_key_exists($i, $haystack[1]) || !isId($haystack[1][$i])) {
				for ($j=$i+1; $j<$total; $j++) {
					if (array_key_exists($j, $haystack[0]) && isId($haystack[0][$j])) {
						$haystack[1][$i] = $haystack[0][$j]-1;
						break;
					}
					if (array_key_exists($j, $haystack[1]) && isId($haystack[1][$j])) {
						$haystack[1][$i] = $haystack[1][$j]-1;
						break;
					}
				}

				// If we couldn't find any either invent the unlimited number or assign -1
				if (!array_key_exists($i, $haystack[1]) || !isId($haystack[1][$i])) {
					$haystack[1][$i] = -1;
				}
			}

			// Assign our range
			$fromRange[] = $haystack[0][$i];
			$toRange[] = $haystack[1][$i];
		}

		// Now we have filtered our haystack, lets see if the needle is in range
		for ($i=0; $i<$total; $i++) {
			if ($needle >= $fromRange[$i] && $needle <= $toRange[$i]) {
				return 1;
			}
		}

		return 0;
	}

	/**
	* NiceSize
	*
	* Returns a datasize formatted into the most relevant units
	* @return string The formatted filesize
	* @param int Size In Bytes
	* @param int How many decimal places to use in the return
	* @param string Optionally force size into this format ('B', 'KB', 'MB', 'GB')
	*/
	public static function niceSize($SizeInBytes=0, $Precision=2, $ForceToFormat='', $noPostFix=false)
	{
		static $map = array(
			'GB' => 1073741824, // 1024 to the power of 3
			'MB' => 1048576, // 1024 to the power of 2
			'KB' => 1024,
			'B' => 1
		);

		$key = '';

		if ($ForceToFormat !== '') {
			$key = strtoupper($ForceToFormat);
			if (!array_key_exists($key, $map)) {
				return false;
			}
		} else {
			foreach ($map as $k => $v) {
				if ($SizeInBytes >= $v) {
					$key = $k;
					break;
				}
			}
		}

		if (!isc_is_int($Precision)) {
			$Precision = 2;
		} else {
			$Precision = (int)$Precision;
		}

		if ($key == '') {
			$key = 'B';
		}

		if ($noPostFix) {
			return sprintf("%01." . $Precision . "f", $SizeInBytes / $map[$key]);
		} else {
			return sprintf("%01." . $Precision . "f %s", $SizeInBytes / $map[$key], $key);
		}
	}
}
