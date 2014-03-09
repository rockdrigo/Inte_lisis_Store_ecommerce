<?php

/**
* This class is a container for generic string methods.
*/
class Interspire_String
{
	/**
	* Filters and returns $str with all characters except a-z A-Z - _ . , and space removed
	*
	* @param string $str
	* @return string
	*/
	public static function filterAlphaExtendedOnly($str)
	{
		return preg_replace('/[^a-zA-Z\-_ \.,]/', '', $str);
	}

	/**
	* Filters and returns $str with all characters except a-z A-Z removed
	*
	* @param string $str
	* @return string
	*/
	public static function filterAlphaOnly($str)
	{
		return preg_replace('/[^a-zA-Z]/', '', $str);
	}

	/**
	* Filters and returns $str with all characters except a-z A-Z 0-9 - _ . , and space removed
	*
	* @param string $str
	* @return string
	*/
	public static function filterAlphaNumExtendedOnly($str)
	{
		return preg_replace('/[^a-zA-Z0-9\-_ \.,]/', '', $str);
	}

	/**
	* Filters and returns $str with all characters except a-z A-Z 0-9 removed
	*
	* @param string $str
	* @return string
	*/
	public static function filterAlphaNumOnly($str)
	{
		return preg_replace('/[^a-zA-Z0-9]/', '', $str);
	}

	/**
	* Generate a random string of characters to a specific length based on the specified selection of characters
	*
	* This was originally in ISC_PRODUCT_IMAGE but has been relocated for general use
	*
	* @param int $length
	* @param string $selection
	* @return string
	*/
	public static function generateRandomString($length, $selection = '0123456789abcdefghijklmnopqrstuvwxyz')
	{
		$output = '';

		$selectionLength = strlen($selection) - 1;
		while ($length) {
			$output .= substr($selection, mt_rand(0, $selectionLength), 1);
			$length--;
		}

		return $output;
	}

	/**
	 * Generate a random semi-readable password
	 *
	 * Function will generate a random yet 'sort of' readable password, using random 2 digit numbers, 2 character words with vowles at the end,
	 * mixed in with the odd punctuation here and there
	 *
	 * @access public
	 * @param int $charLength The optional password length. Default is GENERATED_PASSWORD_LENGTH
	 * @return string The generated password
	 */
	public static function generateReadablePassword($charLength=GENERATED_PASSWORD_LENGTH)
	{
		$letters = array('b','c','d','f','g','h','j','k','l','m','n','p','q','r','s','t','v','w','x','y','z');
		$vowles = array('a','e','i','o','u');
		$punctuation = array('!','@','#','$','%','&','?');
		$password = array();
		$length = ceil($charLength/2);

		for ($i=0; $i<$length; $i++) {

			// Add a 2 digit number
			if ($i%2) {
				$password[] = mt_rand(10, 99);

			// Else add a 2 letter word
			} else {

				$letterKey = array_rand($letters);

				// If its a 'q' then use a 'u', else get a random one
				if ($letters[$letterKey] == 'q') {
					$vowleKey = 4;
				} else {
					$vowleKey = array_rand($vowles);
				}

				$password[] = $letters[$letterKey] . $vowles[$vowleKey];

				// See if we can add a punctuation while we are here
				if ($i%3 === 0) {
					$key = array_rand($punctuation);
					$password[] = $punctuation[$key];
				}
			}
		}

		shuffle($password);

		$password = implode('', $password);
		$password = substr($password, 0, $charLength);
		return $password;
	}

	/**
	 * Add the salt to a string
	 *
	 * Function will add the salt $salt to the string $str and return the MD5 value
	 *
	 * @access public
	 * @param string $str The string to add the salt to
	 * @param string $salt The salt to add
	 * @return string The MD5 value of the salted string
	 */
	public static function generateSaltedHash($str, $salt)
	{
		return md5($str . $salt);
	}

	/**
	 * Sanatise all line endings to '\r' Mac format
	 *
	 * Function will convert all line ending Mac format '\r'
	 *
	 * @access public
	 * @param string $str The string to convert all line endings to
	 * @return string The converted string
	 */
	public static function toMacLineEndings($str)
	{
		return str_replace("\n", "\r", self::toUnixLineEndings($str));
	}

	/**
	 * Sanatise all line endings to '\n' *nix format
	 *
	 * Function will convert all line ending *nix format '\n'
	 *
	 * @access public
	 * @param string $str The string to convert all line endings to
	 * @return string The converted string
	 */
	public static function toUnixLineEndings($str)
	{
		return str_replace("\r", "\n", str_replace("\r\n", "\n", $str));
	}

	/**
	 * Sanatise all line endings to '\r\n' Windows format
	 *
	 * Function will convert all line ending Windows format '\r\n'
	 *
	 * @access public
	 * @param string $str The string to convert all line endings to
	 * @return string The converted string
	 */
	public static function toWindowsLineEndings($str)
	{
		return str_replace("\n", "\r\n", self::toUnixLineEndings($str));
	}

	/**
	* Determines if the provided string is valid UTF-8. This method will return false if any characters in the string
	* are not valid utf-8 and should subsequently be passed through utf8_encode to be made safe for things such as
	* utf-8 mysql string fields. Note that a result of false does not necessarily mean that the string has no utf-8
	* encoded characters at all, it just means that at least one character in the string is not properly encoded and
	* may cause mysql to produce errors if not treated.
	*
	* @param str $str
	* @return bool false if the string is not entirely valid utf-8
	*/
	public static function isUtf8 ($string)
	{
		// detect utf8 by removing all valid utf8-encoded characters, if anything remains then the string is not valid
		// utf8 -- this technique could be optimized, but it avoids segfault issues under apache/php on large strings
		// with using one call to preg_match with a complex, single regex

		// this regex is based on the perl example at http://w3.org/International/questions/qa-forms-utf-8.html
		$string = preg_replace("#[\x09\x0A\x0D\x20-\x7E]#", "", $string); // ASCII
		$string = preg_replace("#[\xC2-\xDF][\x80-\xBF]#", "", $string); // non-overlong 2-byte
		$string = preg_replace("#\xE0[\xA0-\xBF][\x80-\xBF]#", "", $string); // excluding overlongs
		$string = preg_replace("#[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}#", "", $string); // straight 3-byte
		$string = preg_replace("#\xED[\x80-\x9F][\x80-\xBF]#", "", $string); // excluding surrogates
		$string = preg_replace("#\xF0[\x90-\xBF][\x80-\xBF]{2}#", "", $string); // planes 1-3
		$string = preg_replace("#[\xF1-\xF3][\x80-\xBF]{3}#", "", $string); //  planes 4-15
		$string = preg_replace("#\xF4[\x80-\x8F][\x80-\xBF]{2}#", "", $string); // plane 16

		return empty($string);
	}
}
