<?php
class ISC_OPENGRAPH {
	/**
	* Gets a list of valid object types
	*
	* @param bool $includeLabels Set to true to return an associative array that includes the labels for each type
	* @return array The array of object types
	*/
	public static function getObjectTypes($includeLabels = false)
	{
		$objectTypes = array(
			'product' 	=> GetLang('TypeProduct'),
			'album'		=> GetLang('TypeAlbum'),
			'book'		=> GetLang('TypeBook'),
			'drink'		=> GetLang('TypeDrink'),
			'food'		=> GetLang('TypeFood'),
			'game'		=> GetLang('TypeGame'),
			'movie'		=> GetLang('TypeMovie'),
			'song'		=> GetLang('TypeSong'),
			'tv_show'	=> GetLang('TypeTVShow'),
		);

		if (!$includeLabels) {
			return array_keys($objectTypes);
		}

		return $objectTypes;
	}

	/**
	* Generates meta HTML tags using the Open Graph schema
	*
	* @param string $type The object type
	* @param string $title The object's title
	* @param string $description Description of the object
	* @param string $image URL to an image of the object
	* @param string $url The URL to the object itself
	* @return string The HTML meta tags
	*/
	public static function getMetaTags($type = 'product', $title = '', $description = '', $image = '', $url = '')
	{
		$tags = array(
			'og:type' => $type,
			'og:title' => $title,
			'og:description' => $description,
			'og:image' => $image,
			'og:url' => $url,
			'og:site_name' => GetConfig('StoreName')
		);

		if (GetConfig('FacebookLikeButtonAdminIds')) {
			$tags['fb:admins'] = GetConfig('FacebookLikeButtonAdminIds');
		}

		$metaTagsHTML = '';
		foreach ($tags as $propertyName => $tagContent) {
			$metaTagsHTML .= '<meta property="' . $propertyName . '" content="' . isc_html_escape($tagContent) . '" />' . "\n";
		}

		return $metaTagsHTML;
	}
}