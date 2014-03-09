<?php
class Interspire_Url
{
	const MERGE_PARAMS = 1;
	const REPLACE_PARAMS = 2;

	/**
	 * Modifies parameters in a url.
	 *
	 * @param string The url to be modified
	 * @param array The parameters to be applied
	 * @param integer (optional) Type of modification, merge or replace. Merge by default.
	 *
	 * @return string The modified url
	 **/
	public static function modifyParams($url, $newparams, $type=self::MERGE_PARAMS)
	{
		$query = parse_url($url, PHP_URL_QUERY);
		$params = array();
		parse_str($query, $params);

		switch($type)
		{
			case self::MERGE_PARAMS:
				$params = array_merge($params, $newparams);
				break;
			case self::REPLACE_PARAMS:
				$params = $newparams;
				break;
			default:
				return false;
		}

		$query = http_build_query($params);
		$url = preg_replace('/(\?.*$|$)/','?'.$query,$url, 1);
		return $url;
	}
}