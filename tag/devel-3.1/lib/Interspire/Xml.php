<?php
/**
 * Library class for generic XML methods.
 *
 * supports attributes but not namespaces
 * special indexes are @attributes and @value
 * children with the same name are flattened into numerical array
 *
 * always use utf-8 encoding
 * CDATA tag will be added if a special char is detected
 * ']]>' in CDATA tag will be automatically escaped
 */
class Interspire_Xml
{
	/**
	 * Adds an array of data to a SimpleXMLElement
	 *
	 * @param  SimpleXMLElement  $xml The SimpleXMLElement to add the array to
	 * @param  array $data	The array of data to add
	 * @see unit test for more info
	 */
	public static function addArrayToXML(&$xml, $data)
	{
		foreach ($data as $key => $val) {

			if (is_array($val)) {

				if (empty($val)) {
					$xml->addChild($key, '');
				} else if ($key == '@attributes') {
					foreach ($val as $attributeName => $attributeValue) {
						$xml->addAttribute($attributeName, $attributeValue);
					}
				 } else if ($key == '@value') {
					self::addArrayToXML($xml, $val);
				} else {
					$keys = array_keys($val);

					// is this array indexed numerically
					if (is_numeric($keys[0])) {
						// add each element in this array as a child with the current tag name
						foreach($val as $subVal) {
							// is this element an array itself? we should process it recursively
							if (is_array($subVal)) {
								$node = $xml->addChild($key);
								self::addArrayToXML($node, $subVal);
							}
							else {
								// just a regular value, just add it
								self::_processValue($key, $subVal, $xml);
							}
						}
					}
					else {
						// otherwise just process array normally
						$node = $xml->addChild($key);
						self::addArrayToXML($node, $val);
					}
				}
			} else {
				// normal + text
				self::_processValue($key, $val, $xml);
			}
		}
	}


	/**
	 * turn SimpleXMLElement into an array
	 *
	 * @param  object  $input The input SimpleXMLElement
	 * @param  string  $tag   Flatten this tag to normal numeric index if found
	 * @param  boolean $start Internal indicator, do no modify
	 * @return string
	 * @see array2xml()
	 */
	public static function xml2array($input, $tag='item', $start=true)
	{
		$result = array();
		if (is_object($input)) {
			$value = array();
			foreach ($input->children() as $name => $child) {
				$next = self::xml2array($child, $tag, false);
				if ($name == $tag) {
					// flatten this
					$value[] = $next;
				} else {
					$value[$name] = $next;
				}
			}

			if (empty($value)) {
				$text = trim((string) $input);
				if (strlen($text) != 0) {
					$value = $text;
				}
			}

			$attributes = array();
			foreach ($input->attributes() as $k => $v) {
				$attributes[$k] = (string) $v;
			}

			if (!empty($attributes)) {
				$result['@attributes'] = $attributes;
				$result['@value'] = $value;
			} else {
				if ($start && !is_array($value)) {
					// '<simple>tag</simple>
					$result[$input->getName()] = $value;
				} else {
					$result = $value;
				}
			}
		}

		return $result;
	}


	/**
	 * bundle all methods to turn array into xml response with validation
	 *
	 * @param  array $input The response array
	 * @return string
	 */
	public static function getResponse($input, $root='response')
	{
		$xml = self::createXML($root);
		self::addArrayToXML($xml, $input);
		$xmlString = $xml->asXML();

		if (self::validateXMLString($xmlString)) {
			$xmlString = self::prettyIndent($xml);
		} else {
			// error
			$xmlString =  self::prettyIndent($root);
		}

		return $xmlString;
	}


	/**
	 * check if a string is well formatted XML
	 *
	 * generate error message for malformed XML
	 *
	 * @param  string $input The input string
	 * @param  string $error Reference string to hold error/warning messages
	 * @return boolean
	 */
	public static function validateXMLString($input, &$error='')
	{
		$val = libxml_use_internal_errors(true);
		libxml_clear_errors();
		$dom = new DOMDocument();
		if (!@$dom->loadXML($input)) {
			$errors = libxml_get_errors();
			foreach ($errors as $e) {
				$error .= $e->message;
			}

			libxml_use_internal_errors($val);
			return false;
		}

		libxml_use_internal_errors($val);
		return true;
	}


	/**
	 * indent/format XML string nicely
	 *
	 * @param  mixed  $input The input string or simplexml object
	 * @return string formatted XML or empty string on failure
	 */
	public static function prettyIndent($input)
	{
		if ($input instanceof SimpleXMLElement) {
			$input = $input->asXML();
		}

		$result = $input;
		if (function_exists('dom_import_simplexml')) {
			$dom = new DOMDocument('1.0');
			if ($dom->loadXML($input)) {
				$dom->preserveWhiteSpace = false;
				$dom->formatOutput = true;
				$result = $dom->saveXML();
			}
		}

		return $result;
	}


	/**
	 * return the charset (http header)/encoding (xml declaration)
	 *
	 * @param  array $input The input array
	 * @return string
	 */
	public static function getCharset()
	{
		// always use utf-8
		$charset = 'utf-8';
		if (getConfig('CharacterSet')) {
			//$charset = strtolower(getConfig('CharacterSet'));
		}

		return $charset;
	}


	/**
	 * return the xml declaration
	 *
	 * @return string
	 */
	public static function getDeclaration()
	{
		$version = '1.0';
		$res = '<?xml version="'.$version.'" encoding="'.self::getCharset().'"?>';
		return $res;
	}


	/**
	 * send http header
	 *
	 * @return void
	 */
	public static function sendHttpHeader()
	{
		header('Content-Type: text/xml; charset='.self::getCharset());
	}


	/**
	 * helper method to automatically create cdata section for a given value
	 *
	 * note: assuming all array values are already utf-8 encoded
	 *
	 * @param string $key   Name of the text node
	 * @param string $value Content of the text node
	 * @param object $xml   SimpleXMLElement to add text node to
	 *
	 * @return object
	 */
	private static function _processValue($key, $value, $xml)
	{
		$node = null;
		if (!is_string($value) && !is_numeric($value)) {
			// true false null object etc
			if ($value == true) {
				$node = $xml->addChild($key, 1);
			} else {
				$node = $xml->addChild($key, 0);
			}

			return $node;
		}

		$res = htmlentities($value);
		if ($res != $value) {
			// has special character
			// use dom to add a cdata section
			// note: ]]> will be escaped by createCDATASection()
			$node = dom_import_simplexml($xml);
			$owner = $node->ownerDocument;
			$elem = $owner->createElement($key);
			$cdata = $owner->createCDATASection($value);
			$elem->appendChild($cdata);
			$node->appendChild($elem);
			$node = simplexml_import_dom($elem);
		} else {
			$node = $xml->addChild($key, $value);
		}

		return $node;
	}


	/**
	 * escape ']]>' in a CDATA section
	 *
	 * a CDATA section cannot contain the string ']]>', use multiple CDATA
	 * sections by splitting each occurrence just before the '>'
	 * libxml error: Sequence ']]>' not allowed in content
	 *
	 * @param  string $input The input string
	 * @return string
	 * @see XMLAPI class
	 */
	public static function escapeCdata($input)
	{
		$res = str_replace(']]>', ']]]]><![CDATA[>', $input);

		return $res;
	}

	/**
	* Creates a SimpleXMLElement with the specified root element tag
	*
	* @param string $rootElement The root element tag name
	* @return SimpleXMLElement
	*/
	public static function createXML($rootElement)
	{
		$xml = self::getDeclaration();
		$xml .= '<' . $rootElement . '/>';

		return new SimpleXMLElement($xml);
	}
}
