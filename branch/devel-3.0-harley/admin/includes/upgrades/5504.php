<?php
/**
 * Upgrade class for 5.5.4
 * This class runs a series of methods used to upgrade the store to a specific version
 *
 * @package ISC
 * @subpackage ISC_Upgrade
 */

class ISC_ADMIN_UPGRADE_5504 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		"update_variation_images",
	);

	public function update_variation_images()
	{
		// if the column vcimagezoom exists, can assume that images have already been updated and nothing to do here.
		if ($this->ColumnExists('[|PREFIX|]product_variation_combinations', 'vcimagezoom')) {
			return true;
		}

		/**
		* Standardise the naming of columns to be consistent with regular product images
		*/

		// move the vcimage field to vcimagezoom
		$query = 'ALTER TABLE `[|PREFIX|]product_variation_combinations` CHANGE `vcimage` `vcimagezoom` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// move the vcthumb field to vcimagethumb
		$query = 'ALTER TABLE `[|PREFIX|]product_variation_combinations` CHANGE `vcthumb` `vcimagethumb` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';
		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// recreate the vcimage field which will hold our source image path
		$query = 'ALTER TABLE `[|PREFIX|]product_variation_combinations` ADD `vcimage` VARCHAR( 100 ) NOT NULL AFTER `vcweight`';
		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// add vcimagestd field to hold the standard size image
		$query = 'ALTER TABLE `[|PREFIX|]product_variation_combinations` ADD `vcimagestd` VARCHAR( 100 ) NOT NULL AFTER `vcimagezoom`';
		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
			return false;
		}

		// process the zoom image as the source file and recreate other versions

		// multiple combinations could be using the same image, so we don't need to resize and create multiple versions.
		$query = '
			SELECT
				GROUP_CONCAT(CAST(combinationid AS CHAR)) AS combinations,
				vcimagezoom
			FROM
				[|PREFIX|]product_variation_combinations
			WHERE
				vcimagezoom != ""
			GROUP BY
				vcimagezoom
		';

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			try {
				$image = new ISC_PRODUCT_IMAGE();
				$image->setSourceFilePath($row['vcimagezoom']);

				$updatedVariation = array(
					'vcimage' 		=> $row['vcimagezoom'],
					'vcimagezoom' 	=> $image->getResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_ZOOM, true, false),
					'vcimagestd' 	=> $image->getResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_STANDARD, true, false),
					'vcimagethumb' 	=> $image->getResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL, true, false)
				);

				$GLOBALS['ISC_CLASS_DB']->UpdateQuery('product_variation_combinations', $updatedVariation, 'combinationid IN (' . $row['combinations'] . ')');
			}
			catch (Exception $ex) {
				$this->SetError($ex->__toString());
			}
		}

		return true;
	}
}
?>