<?php

/**
* This class is a container for shopping-cart-specific currency methods.
*/
class Store_Currency
{
	/**
	* This method compares two currency values and returns a value noting their equality. This is here to avoid issues with comparing double floating point numbers, where comparing two seemingly equal numbers will fail. This assumes that all ISC currency values, for comparison reasons, are significant to 4 digits only.
	*
	* @param double $left
	* @param double $right
	* @return int Returns 0 if the two operands are equal, 1 if the left_operand is larger than the right_operand, -1 otherwise.
	*/
	public static function compare($left_operand, $right_operand)
	{
		if (function_exists('bccomp')) {
			return bccomp($left_operand, $right_operand, 4);
		}

		if ($left_operand > $right_operand) {
			return 1;
		} else if ($right_operand < $left_operand) {
			return -1;
		}
		return 0;
	}
}
