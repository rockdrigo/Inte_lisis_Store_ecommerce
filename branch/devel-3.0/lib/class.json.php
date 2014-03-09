<?php

class ISC_JSON
{
	/**
	 * This is used for ajax file uploads, which use an iframe to get a response.
	 * In order to get the JSON response without being turned into entities, the
	 * Javascript will fetch the response from inside a textarea.
	 * This flag should only be set when using the ajax form uploader.
	 */
	public static $useTextarea = false;

	public static function encode($a)
	{
		return isc_json_encode($a);
	}

	public static function output($message, $success=false, $additionalArray=null)
	{
		// @codeCoverageIgnoreStart
		// if this is ever changed so that die() is optiona, remove the ignore tags - otherwise this method should never be called during a unit test since it makes phpunit quit

		if (is_array($message)) {
			$jsonArray = $message;
		} else {
			if(is_array($additionalArray) && !empty($additionalArray)) {
				$jsonArray = $additionalArray;
			}else{
				$jsonArray = array();
			}

			$jsonArray['success'] = (bool)$success;
			$jsonArray['message'] = $message;
		}

		$charset = GetConfig('CharacterSet');
		if (!$charset) {
			$charset = 'utf-8';
		}

		if(self::$useTextarea) {
			header('Content-type: text/html; charset=' . $charset);
			echo '<textarea>';
		} else {
			header('Content-type: application/json; charset=' . $charset);
		}

		echo isc_json_encode($jsonArray);

		if(self::$useTextarea) {
			echo '</textarea>';
		}

		die();
		// @codeCoverageIgnoreEnd
	}

	public static function decode($string, $assoc = false)
	{
		if(substr($string, 0, 5) == '{}&& ') {
			$string = substr($string, 5);
		}

		return json_decode($string, $assoc);
	}
}
