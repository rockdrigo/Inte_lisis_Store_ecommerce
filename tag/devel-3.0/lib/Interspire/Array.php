<?php

/**
* This class is a container for generic array methods.
*/
class Interspire_Array
{
	/**
	 * Recursively remove any empty values and trim the others
	 *
	 * Function will recursively trim the values and remove any empties
	 *
	 * @todo refactor
	 * @param array $array The array to clean
	 * @param string $trimFunc The callback function for trimming the value. Default is "trim"
	 * @return array The filtered array
	 */
	public static function clean($array, $trimFunc="trim")
	{
		if (!is_array($array) || empty($array)) {
			return;
		}

		$filtered = array();
		foreach ($array as $key => $val) {
			if (is_array($val)) {
				$newVal = self::clean($val, $trimFunc);
				if (!empty($newVal)) {
					$filtered[$key] = $newVal;
				}
			} else if (is_scalar($val)) {
				$newVal = call_user_func($trimFunc, $val);
				if ($newVal !== "") {
					$filtered[$key] = $newVal;
				}
			} else {
				$filtered[$key] = $val;
			}
		}

		return $filtered;
	}

	/**
	* Calculates the cartesian product of arrays
	*
	* <code>
	* $array["color"] = array("red", "green", "blue")
	* $array["size"] = array("S", "L");
	* $cartesian = Interspire_Array::generateCartesianProduct($array, true);
	*
	* //result: {"color" => red, "size" => S}, {red, L}, {green, S}, {green, L}, {blue, S}, {blue, L}
	* </code>
	*
	* @todo refactor
	* @param array The array of sets
	* @param bool Maintain index association of the input sets
	* @return array Cartesian product array
	*/
	public static function generateCartesianProduct($sets, $maintain_index = false)
	{
		$cartesian = array();

		// calculate size of the cartesian product (the amount of elements in each array multiplied by each other)
		$size = 1;
		foreach ($sets as $set) {
			$size *= count($set);
		}

		$scale_factor = $size;

		foreach ($sets as $key => $set) {
			// number of elements in this set
			$set_elements = count($set);

			$scale_factor /= $set_elements;

			// add the elements from each set into their correct position into the result
			for ($i = 0; $i < $size; $i++) {
				$pos = $i / $scale_factor % $set_elements;

				if ($maintain_index) {
					$cartesian[$i][$key] = $set[$pos];
				}
				else {
					if (empty($cartesian[$i])) {
						$cartesian[$i] = array();
					}
					array_push($cartesian[$i], $set[$pos]);
				}
			}
		}

		return $cartesian;
	}

	/**
	* This was moved from lib/general to here and was originally undocumented
	*
	* @todo document
	* @param mixed $array
	* @param mixed $cols
	* @return mixed
	*/
	public static function msort($array, $cols)
	{
		$colarr = array();
		foreach ($cols as $col => $order) {
			$colarr[$col] = array();
			foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
		}
		$params = array();
		foreach ($cols as $col => $order) {
			$params[] =& $colarr[$col];
			$order = (array)$order;
			foreach($order as $order_element) {
				$params[] = &$order_element;
			}
		}

		call_user_func_array('array_multisort', $params);
		$ret = array();
		$keys = array();
		$first = true;
		foreach ($colarr as $col => $arr) {
			foreach ($arr as $k => $v) {
				if ($first) { $keys[$k] = substr($k,1); }
				$k = $keys[$k];
				if (!isset($ret[$k])) $ret[$k] = $array[$k];
				$ret[$k][$col] = $array[$k][$col];
			}
			$first = false;
		}
		return $ret;
	}

	/**
	* Takes the multi-dimensional array $input and produces an output array where all elements have been assigned a new key based on the specified sub-array key. Note: elements that are not arrays or do not contain the key $subkey will be dropped, duplicate subkeys will be overwritten by subsequent matches.
	*
	* E.g.:
	* $input = array(
	* 	0 => array('product_id' => 12, ...),
	* 	1 => array('product_id' => 14, ...),
	* 	2 => array('product_id' => 15, ...),
	* 	...
	* );
	*
	* $output = Interspire_Array::remapToSubkey($input, 'product_id');
	*
	* $output === array(
	* 	12 => array('product_id' => 12, ...),
	* 	14 => array('product_id' => 14, ...),
	* 	15 => array('product_id' => 15, ...),
	* 	...
	* );
	*
	* @param array $input
	* @param mixed $subkey
	* @return array
	*/
	public static function remapToSubkey($input, $subkey)
	{
		$output = array();

		foreach ($input as $subarray) {
			if (!is_array($subarray) || !isset($subarray[$subkey])) {
				continue;
			}
			$output[$subarray[$subkey]] = $subarray;
		}

		return $output;
	}
}
