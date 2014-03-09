<?php

/**
* Product variation related functions
*/
class Store_Variations {
	/**
	* Gets the list of options for a specified variation as a resource
	*
	* @param int $variationId The variation to get options for
	* @return resource MySQL database resource
	*/
	public static function getOptions($variationId)
	{
		$query = "
			SELECT
				*
			FROM
				[|PREFIX|]product_variation_options
			WHERE
				vovariationid = " . $variationId . "
			ORDER BY
				vooptionsort,
				vovaluesort
		";

		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);

		return $res;
	}

	/**
	* Retrieves the total combinations for a specific product and variation
	*
	* @param int $productId The product to query for
	* @param int $variationId The variation to query for
	* @return int The total combinations
	*/
	public static function getCombinationsCount($productId, $variationId)
	{
		$query = "
			SELECT
				COUNT(*)
			FROM
				[|PREFIX|]product_variation_combinations
			WHERE
				vcvariationid = " . $variationId . " AND
				vcproductid = " . $productId . " AND
				vcenabled = 1
		";

		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);

		return $GLOBALS['ISC_CLASS_DB']->FetchOne($res);
	}

	/**
	* Gets the list of combinations for a specified variation as a resource
	*
	* @param int $productId The product to get combinations for
	* @param int $variationId The variation to get combinations for
	* @return resource MySQL database resource
	*/
	public static function getCombinations($productId, $variationId)
	{
		$query = "
			SELECT
				*
			FROM
				[|PREFIX|]product_variation_combinations
			WHERE
				vcvariationid = " . $variationId . " AND
				vcproductid = " . $productId . " AND
				vcenabled = 1
		";

		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);

		return $res;
	}

	/**
	* Gets a set of images for the first option set in a variation for a specific product.
	* Eg. For a variation: Color => (Red, Blue, Green), Size => (S, M, L) :
	*  	Red => ../image1.jpg
	* 	Blue => ../image2.jpg
	* 	Green => ../image3.jpg
	* will be returned.
	*
	* @param int $productId The product to find images for
	* @param int $variationId The variation to find images for
	* @return array An array of combination images indexed by option name
	*/
	public static function getCombinationImagesForFirstOption($productId, $variationId)
	{
		// get the variation options for the first option set
		$query = "
			SELECT
				*
			FROM
				[|PREFIX|]product_variation_options
			WHERE
				vovariationid = " . $variationId . " AND
				vooptionsort = 1
		";

		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$optionIdsArray = array();
		$optionName = '';

		while ($optionRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
			$optionName = $optionRow['voname'];

			$optionIdsArray[$optionRow['voptionid']] = $optionRow['vovalue'];
		}

		// now find a set of images for the options
		$setMatches = array();
		foreach($optionIdsArray as $optionId => $optionValue) {
			$setMatches[] = 'FIND_IN_SET(' . $optionId . ', vcoptionids)';
		}

		$query = "
			SELECT
				vcoptionids,
				vcimage,
				vcimagestd
			FROM
				[|PREFIX|]product_variation_combinations
			WHERE
				vcvariationid = " . $variationId . " AND
				vcproductid = " . $productId . " AND
				vcenabled = 1 AND (
				" . implode(' OR ', $setMatches) . "
				) AND
				vcimage != '' AND
				vcimagestd != ''
		";

		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$images = array();
		while ($comboRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
			$comboOptionIds = explode(',', $comboRow['vcoptionids']);

			// get the option id that was matched for this row
			$optionId = current(array_intersect(array_keys($optionIdsArray), $comboOptionIds));

			// get the option that this row corresponds to
			$optionName = $optionIdsArray[$optionId];

			if (!isset($images[$optionName])) {
				try {
					$productImage = new ISC_PRODUCT_IMAGE;
					$productImage->setSourceFilePath($comboRow['vcimage'])
						->setResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_STANDARD, $comboRow['vcimagestd']);
					$images[$optionName] = $productImage->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_STANDARD, true, false);
				} catch (Exception $exception) {
					// nothing
				}
			}
		}

		return $images;
	}
}
