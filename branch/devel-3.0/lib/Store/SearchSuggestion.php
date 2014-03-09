<?php

class Store_SearchSuggestion
{
	/**
	 * Manage the pspell word database for node names
	 *
	 * Method will add/edit/delete the pspell word database for node names
	 *
	 * @access public
	 * @param string $type The node type (product, brand, category, etc)
	 * @param int $id The node ID
	 * @param string $name The node name to check in
	 * @return bool TRUE if the word was added/edited OR if the 'SearchSuggest' setting is turned off, FALSE on error
	 */
	public static function manageSuggestedWordDatabase($type, $id, $name)
	{
		// If search suggestions aren't enabled, don't try to build the list of suggested words
		if(!GetConfig("SearchSuggest")) {
			return true;
		}

		if (trim($type) == "" || !isId($id)) {
			return false;
		}

		$words = array();
		$parts = preg_split("#[(\s|\(|\)\/)]+#", $name);
		$pspellInstalled = false;

		if (function_exists("pspell_new")) {
			$pspellInstalled = true;
		}

		// Create a pSpell object if it's installed
		if ($pspellInstalled) {
			$spell = @pspell_new("en");
		}

		foreach ($parts as $part) {
			if (isc_strlen(trim($part)) > 2) {
				// Can we spell check against the word?
				if ($pspellInstalled && $spell) {
					if (!@pspell_check($spell, $part)) {
						$suggestions = @pspell_suggest($spell, $part);

						// If any suggestions are returned then the word generally misspelled
						if (!empty($suggestions)) {
							$words[] = isc_strtolower($part);
						}
					}

				// pSpell isn't installed so we'll go ahead and add the word anyway
				} else {
					$words[] = isc_strtolower($part);
				}
			}
		}

		$table = isc_strtolower($type) . "_words";
		$column = isc_strtolower($type) . "id";

		$GLOBALS["ISC_CLASS_DB"]->DeleteQuery($table, "WHERE " . $column . " = " . (int)$id);

		// Add the words to the product_words table
		foreach ($words as $word) {
			$savedata = array(
				"word" => $word,
				$column => $id
			);

			$GLOBALS['ISC_CLASS_DB']->InsertQuery($table, $savedata);
		}

		return true;
	}
}
