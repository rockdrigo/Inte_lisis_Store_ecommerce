<?php
class Interspire_Template_Extension extends Twig_Extension_Core
{
	public function getName()
	{
		return 'interspire';
	}

	public function getTokenParsers()
	{
		return array_merge(
			parent::getTokenParsers(),
			array(
				new Interspire_Template_TokenParser_Lang(),
				new Interspire_Template_TokenParser_Panel(),
				new Interspire_Template_TokenParser_Snippet(),
				new Interspire_Template_TokenParser_JSLang(),
				new Interspire_Template_TokenParser_FlashMessages(),
			)
		);
	}

	public function getFilters()
	{
		return array_merge(
			parent::getFilters(),
			array(
				'js' => new Twig_Filter_Method($this, 'jsFilter', array('is_escaper' => true)),
				'http_build_query' => new Twig_Filter_Function('http_build_query'),
				'uniqid' => new Twig_Filter_Method($this, 'uniqidFilter'),
				'formatPrice' => new Twig_Filter_Function('formatPrice'),
				'currencyFormatPrice' => new Twig_Filter_Function('formatPriceInCurrency'),
				'date' => new Twig_Filter_Method($this, 'dateFormat'),
				'mTruncateSplice' => new Twig_Filter_Method($this, 'mTruncateSpliceFilter'),
				'nl2br' => new Twig_Filter_Function('nl2br'),
				'niceSize' => new Twig_Filter_Function('Store_Number::niceSize'),
				'json' => new Twig_Filter_Method($this, 'jsonFilter'),
				'accessKey' => new Twig_Filter_Method($this, 'accessKeyFilter', array(
					'is_escaper' => true,
				)),
			)
		);
	}

	/**
	 * Filter for formatting dates in templates that wraps around isc_date. Will use
	 * getConfig('DisplayDateFormat') if no format is supplied.
	 *
	 * @param DateTime|int $timestamp Instance of a DateTime object, or unix timestamp.
	 * @param string $format Format for time. Can either be a getConfig() value or actual date format.
	 * @return string Formatted date.
	 */
	public function dateFormat($timestamp, $format = '')
	{
		if($format == '') {
			$format = getConfig('DisplayDateFormat');
		}
		else if(getConfig($format)) {
			$format = getConfig($format);
		}

		if($timestamp instanceof DateTime) {
			return $timestamp->format($format);
		}

		return isc_date($format, $timestamp);
	}

	/**
	* Filters a string to be safe for outputting to javascript embedded inside X/HTML. This is an 'escaper' filter; HTML escaping is bypassed.
	*
	* Escaped characters are:
	* \		literal backslash
	* \n	newline
	* \r	carriage return
	* "		double quote
	* '		single quote
	* &		for xhtml
	* <		for xhtml
	* >		for xhtml
	*
	* Twig Examples:
	* var foo = "{{ a_variable|js }}"; // double quotes are recommended as some old js engines have issues with escaping in single quote strings
	* var foo = '{{ a_variable|js }}';
	*
	* @param string $string Input string to filter
	* @return string Returns the input string with all appropriate control characters escaped for javascript usage
	*/
	public function jsFilter($string)
	{
		return Interspire_JavaScript::encode($string);
	}

	/**
	* Filter that passes the input through to uniqid() and returns the result
	*
	* @param string $string
	* @return string
	*/
	public function uniqidFilter($string='')
	{
		return uniqid($string, true);
	}

	/**
	 * Filter that truncates and splices a string in the middle.
	 *
	 * @param string input string
	 * @param integer truncate size
	 * @param string replacement string
	 */
	public function mTruncateSpliceFilter($string='', $maxsize=10, $replaceStr='...')
	{
		$len = strlen($string);
		$replaceLen = strlen($replaceStr);

		if($len < $maxsize || $maxsize < $replaceLen)
			return $string;

		$maxsize -= $replaceLen;

		$cutstart = $maxsize/2;
		$cutend = $len - $maxsize/2;

		$s1 = substr($string, 0, $cutstart);
		$s2 = substr($string, $cutend, $maxsize/2 + 1);
		return $s1 . $replaceStr . $s2;
	}

	/**
	* Filter which encodes any given object/string as a json packet
	*
	* @param mixed $obj
	* @return string
	*/
	public function jsonFilter($obj)
	{
		return isc_json_encode($obj);
	}

	/**
	* Filters an input string such as "Foo Bar" with a decoration of the given access key $key using XHTML-valid syntax.
	*
	* - Only intended for use in HTML context.
	* - Make sure a style is defined for .accesskey in your CSS (this is already present in ISC control panel)
	*
	* Usage:
	* <button accesskey="b">{{ lang.FooBar|accessKey('b')|safe }}</button>
	* =
	* <button accesskey="b">Foo <span class="accesskey">B</span>ar</button>
	*
	* @param string $string
	* @return string
	*/
	public function accessKeyFilter($string, $key)
	{
		// this is an escaper so perform html escape internally but do not escape the added <u>...</u>

		// get the original matched string so case is preserved when imploding
		$pattern = '#' . preg_quote($key, '#') . '#i';
		preg_match($pattern, $string, $match);
		if (isset($match[0]) && $match[0] == 'y') $match = $match[0];
		else $match = 'n';

		// split the original string so html escaping can be done in chunks and so html escaping does not confuse a basic str_ireplace
		$string = preg_split($pattern, $string, 2);
		foreach ($string as &$part) {
			$part = isc_html_escape($part);
		}
		return implode('<span class="accesskey">' . isc_html_escape($match) . '</span>', $string);
	}
}
