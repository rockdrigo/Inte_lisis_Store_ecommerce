<?php
/**
 * Upgrade class for 5.5.0
 * This class runs a series of methods used to upgrade the store to a specific version
 *
 * @package ISC
 * @subpackage ISC_Upgrade
 */

class ISC_ADMIN_UPGRADE_5500 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		"add_order_last_modified",
		"add_customer_last_modified",
		"remove_accountingspool_table",
		"alter_accountingref_table",
		'add_productvideos_table',
		'add_uk_states_avon_and_bristol',
		'add_xmlsitemap_permission',
		'add_laos_north_korea',
		'update_taiwan',
		'add_pagemetatitle_field',
		'add_website_optimizer_permission',
		'add_optimizer_config_table',
		'add_enable_optimizer_field',
		'add_categories_column_nsetleft',
		'add_categories_column_nsetright',
		'add_categories_i_categoryid_catnsetleft_catnsetright',
		'add_categories_i_catnsetleft',
		'add_categories_i_catparentid_catsort_catname',
		'add_categories_i_catvisible_catsort_catname_index',
		'rebuild_categories_nset',
		'add_pages_column_nsetleft',
		'add_pages_column_nsetright',
		'add_pages_i_pageid_pagensetleft_pagensetright',
		'add_pages_i_pagensetleft',
		'add_pages_i_pageparentid_pagesort_pagetitle',
		'rebuild_pages_nset',
		"add_search_columns",
		"add_brand_search_tables",
		"add_category_search_tables",
		"add_page_search_tables",
		"add_news_search_tables",
		"rebuild_product_search_data",
		'add_abandonorders_template_fields',
		'add_productweight_template_fields',
		'add_product_images_imagefiletiny',
		'add_product_images_imagefilethumb',
		'add_product_images_imagefilestd',
		'add_product_images_imagefilezoom',
		'add_product_images_imagedesc',
		'add_product_images_imagedateadded',
		'add_product_images_imagefiletinysize',
		'add_product_images_imagefilethumbsize',
		'add_product_images_imagefilestdsize',
		'add_product_images_imagefilezoomsize',
		'add_product_images_imageprodid_imagesort_imageprodhash_index',
		'add_product_images_imageid_imageprodid_imageprodhash_index',
		'convert_product_images',
		'add_images_template_fields',
		'add_productimages_settings',
		'add_salestax_template_fields',
		'set_install_date',
		'add_combination_last_modified',
		'fix_state_names',
	);

	public function pre_upgrade_checks()
	{
		$sensativeTables = array();

		$tablePrefix = str_replace('_', '\_', getConfig('tablePrefix'));
		$query = "
			SHOW TABLE STATUS
			WHERE
				Name LIKE '".$GLOBALS['ISC_CLASS_DB']->Quote($tablePrefix)."%'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($table = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			// Case sensative collation
			if(!empty($table['Collation']) && substr($table['Collation'], -3) != '_ci') {
				$sensativeTables[$table['Name']]['collation'] = $table['Collation'];
			}

			// Check the collation of the columns in this table
			$query = "
				SHOW FULL COLUMNS
				FROM ".$table['Name']."
				WHERE
					COllation != '' AND Collation NOT LIKE '%\_ci'
			";
			$columnResult = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while($column = $GLOBALS['ISC_CLASS_DB']->Fetch($columnResult)) {
				$sensativeTables[$table['Name']]['columns'][$column['Field']] = $column['Collation'];
			}
		}

		// All tables and columns are case insensative
		if(empty($sensativeTables)) {
			return true;
		}

		$error = 'Interspire Shopping Cart requires all database tables and columns to have their collation set to case-insensitive. ';
		$error .= 'We have identified the following tables/columns on your installation to have a case-sensitive collation. Please update ';
		$error .= 'the collation of the following tables/columns to be a case-insensitive version of your current collation:';
		$error .= '<ul>';

		foreach($sensativeTables as $table => $info) {
			$error .= '<li><strong>Table: '.$table.'</strong>';
			if(!empty($info['collation'])) {
				$error .= ' (Collation: '.$info['collation'].')';
			}
			if(!empty($info['columns'])) {
				$error .= '<ul>';
				foreach($info['columns'] as $column => $collation) {
					$error .= '<li>Column: '.$column.' ('.$collation.')</li>';
				}
				$error .= '</ul>';
			}
			$error .= '</li>';
		}

		$error .= '</ul>If you are unsure of how change the collation of the tables/columns mentioned above, please contact your web host or ';
		$error .= 'system administrator.';

		$this->SetError($error);
		return false;
	}

	public function fix_state_names()
	{
		$statesToFix = array(
			0 =>
			array(
				'statename' => 'Baden-Württemberg',
				'statecountry' => '80',
				'stateabbrv' => 'BAW',
			),
			1 =>
			array(
				'statename' => 'Thüringen',
				'statecountry' => '80',
				'stateabbrv' => 'THE',
			),
			2 =>
			array(
				'statename' => 'Niederösterreich',
				'statecountry' => '14',
				'stateabbrv' => 'NO',
			),
			3 =>
			array(
				'statename' => 'Oberösterreich',
				'statecountry' => '14',
				'stateabbrv' => 'OO',
			),
			4 =>
			array(
				'statename' => 'Kärnten',
				'statecountry' => '14',
				'stateabbrv' => 'KN',
			),
			5 =>
			array(
				'statename' => 'Graubünden',
				'statecountry' => '206',
				'stateabbrv' => 'JUB',
			),
			7 =>
			array(
				'statename' => 'Zürich',
				'statecountry' => '206',
				'stateabbrv' => 'ZH',
			),
			8 =>
			array(
				'statename' => 'A Coruña',
				'statecountry' => '199',
				'stateabbrv' => 'ACOR',
			),
		);
		foreach($statesToFix as $state) {
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('country_states', array('statename' => $state['statename']), 'stateabbrv = "'.$state['stateabbrv'].'" and statecountry="'.$state['statecountry'].'"');
		}
		return true;
	}

	public function add_productimages_settings()
	{
		GetClass('ISC_PRODUCT_IMAGE');

		$settings = GetClass('ISC_ADMIN_SETTINGS');

		$GLOBALS['ISC_NEW_CFG'] = $GLOBALS['ISC_CFG'];
		$GLOBALS['ISC_CFG']['ProductImagesStorewideThumbnail_width'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_THUMBNAIL;
		$GLOBALS['ISC_CFG']['ProductImagesStorewideThumbnail_height'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_THUMBNAIL;
		$GLOBALS['ISC_CFG']['ProductImagesProductPageImage_width'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_STANDARD;
		$GLOBALS['ISC_CFG']['ProductImagesProductPageImage_height'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_STANDARD;
		$GLOBALS['ISC_CFG']['ProductImagesGalleryThumbnail_width'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_TINY;
		$GLOBALS['ISC_CFG']['ProductImagesGalleryThumbnail_height'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_TINY;
		$GLOBALS['ISC_CFG']['ProductImagesZoomImage_width'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_ZOOM;
		$GLOBALS['ISC_CFG']['ProductImagesZoomImage_height'] = ISC_PRODUCT_DEFAULT_IMAGE_SIZE_ZOOM;
		$GLOBALS['ISC_CFG']['ProductImagesTinyThumbnailsEnabled'] = 1;
		$GLOBALS['ISC_CFG']['ProductImagesImageZoomEnabled'] = 1;

		return true;
	}


	public function add_product_images_imagefiletiny()
	{
		if (!$this->ColumnExists('[|PREFIX|]product_images', 'imagefiletiny')) {
			$sql = "ALTER TABLE `[|PREFIX|]product_images` ADD imagefiletiny varchar(255) default ''";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($sql)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_product_images_imagefilethumb()
	{
		if (!$this->ColumnExists('[|PREFIX|]product_images', 'imagefilethumb')) {
			$sql = "ALTER TABLE `[|PREFIX|]product_images` ADD imagefilethumb varchar(255) default ''";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($sql)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_product_images_imagefilestd()
	{
		if (!$this->ColumnExists('[|PREFIX|]product_images', 'imagefilestd')) {
			$sql = "ALTER TABLE `[|PREFIX|]product_images` ADD imagefilestd varchar(255) default ''";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($sql)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_product_images_imagefilezoom()
	{
		if (!$this->ColumnExists('[|PREFIX|]product_images', 'imagefilezoom')) {
			$sql = "ALTER TABLE `[|PREFIX|]product_images` ADD imagefilezoom varchar(255) default ''";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($sql)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_product_images_imagedesc()
	{
		if (!$this->ColumnExists('[|PREFIX|]product_images', 'imagedesc')) {
			$sql = "ALTER TABLE `[|PREFIX|]product_images` ADD imagedesc longtext";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($sql)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

			$sql = "UPDATE `[|PREFIX|]product_images` SET imagedesc = ''";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($sql)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_product_images_imagedateadded()
	{
		if (!$this->ColumnExists('[|PREFIX|]product_images', 'imagedateadded')) {
			$sql = "ALTER TABLE `[|PREFIX|]product_images` ADD imagedateadded int(11) default '0'";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($sql)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_product_images_imagefiletinysize()
	{
		if (!$this->ColumnExists('[|PREFIX|]product_images', 'imagefiletinysize')) {
			$sql = "ALTER TABLE `[|PREFIX|]product_images` ADD imagefiletinysize varchar(11) default ''";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($sql)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_product_images_imagefilethumbsize()
	{
		if (!$this->ColumnExists('[|PREFIX|]product_images', 'imagefilethumbsize')) {
			$sql = "ALTER TABLE `[|PREFIX|]product_images` ADD imagefilethumbsize varchar(11) default ''";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($sql)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_product_images_imagefilestdsize()
	{
		if (!$this->ColumnExists('[|PREFIX|]product_images', 'imagefilestdsize')) {
			$sql = "ALTER TABLE `[|PREFIX|]product_images` ADD imagefilestdsize varchar(11) default ''";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($sql)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_product_images_imagefilezoomsize()
	{
		if (!$this->ColumnExists('[|PREFIX|]product_images', 'imagefilezoomsize')) {
			$sql = "ALTER TABLE `[|PREFIX|]product_images` ADD imagefilezoomsize varchar(11) default ''";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($sql)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_product_images_imageprodid_imagesort_imageprodhash_index()
	{
		if (!$this->IndexExists('[|PREFIX|]product_images', 'i_product_images_imageprodid_imagesort_imageprodhash')) {
			$query = "ALTER TABLE `[|PREFIX|]product_images` ADD INDEX `i_product_images_imageprodid_imagesort_imageprodhash` (`imageprodid`,`imagesort`,`imageprodhash`)";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_product_images_imageid_imageprodid_imageprodhash_index()
	{
		if (!$this->IndexExists('[|PREFIX|]product_images', 'i_product_images_imageid_imageprodid_imageprodhash')) {
			$query = "ALTER TABLE `[|PREFIX|]product_images` ADD INDEX `i_product_images_imageid_imageprodid_imageprodhash` (`imageid`,`imageprodid`,`imageprodhash`)";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_product_images_imagefile_index()
	{
		// now only used during convert_product_images
		if (!$this->IndexExists('[|PREFIX|]product_images', 'i_product_images_imagefile')) {
			$query = "ALTER TABLE `[|PREFIX|]product_images` ADD INDEX `i_product_images_imagefile` (`imagefile`)";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function remove_product_images_imagefile_index()
	{
		// now only used during convert_product_images
		if ($this->IndexExists('[|PREFIX|]product_images', 'i_product_images_imagefile')) {
			$query = "ALTER TABLE `[|PREFIX|]product_images` DROP INDEX `i_product_images_imagefile`";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	private $_deleteImageList = array();

	/**
	* Removes a product image record and it's file if the specified file is not referened in another product image record
	*
	* @param int $imageId
	* @param string $imageFile specify as false or blank to perform no file deletion, delete from db only
	* @param int $imageFileUses the amount of times the image file is refered in the db, which is calculated by the select in convert_product_images
	* @return bool returns false if an error prevented removal, otherwise returns true in all cases
	*/
	private function _removeProductImage($imageId, $imageFile, $imageFileUses = 1)
	{
		//echo "(remove:$imageId,$imageFile)\n";
		//return true; // testing

		$imageId = (int)$imageId;
		$imageFileUses = (int)$imageFileUses;

		/*
		$db = $GLOBALS['ISC_CLASS_DB'];

		if ($imageFile && $imageFileUses <= 1) {
			// the file is not referenced by another image record and can be deleted
			$imagePath = ISC_BASE_PATH . '/product_images/' . $imageFile;
			if (file_exists($imagePath)) {
				@unlink($imagePath);
			}
		}

		$sql = "DELETE FROM `[|PREFIX|]product_images` WHERE imageid = " . $imageId;
		if (!$db->Query($sql)) {
			$this->SetError($db->GetErrorMsg());
			return false;
		}
		*/

		if ($imageFileUses > 1 || !$imageFile) {
			$this->_deleteImageList[$imageId] = false;
		} else {
			$this->_deleteImageList[$imageId] = $imageFile;
		}

		return true;
	}

	private function _finaliseProductImageRemovals()
	{
		// batch delete image records
		if (!empty($this->_deleteImageList)) {
			$imageIdList = array_keys($this->_deleteImageList);
			$sql = "DELETE FROM `[|PREFIX|]product_images` WHERE imageid IN (" . implode(',', $imageIdList) . ")";
			//var_dump($sql);
			if (!$GLOBALS['ISC_CLASS_DB']->Query($sql)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		// delete files
		foreach ($this->_deleteImageList as $imageId => $imageFile) {
			if ($imageFile) {
				$imagePath = ISC_BASE_PATH . '/product_images/' . $imageFile;
				@unlink($imagePath);
			}
		}

		return true;
	}

	/**
	* Sets the dateadded value of a product image to the current time
	*
	* @param int $imageId
	* @return bool false if any db error occurred otherwise true
	*/
	private function _setProductImageDateAdded($imageId)
	{
		//echo "(dateadded:$imageId)\n";
		//return true; // testing

		$imageId = (int)$imageId;

		$db = $GLOBALS['ISC_CLASS_DB'];

		// if the files for it exist and are not referenced by another record, delete them
		$sql = "UPDATE `[|PREFIX|]product_images` SET imagedateadded = " . time() . " WHERE imageid = " . $imageId;
		if (!$db->Query($sql)) {
			$this->SetError($db->GetErrorMsg());
			return false;
		}

		return true;
	}

	private $_imageThumbnails = array();

	/**
	* Sets the thumbnail flag on a given $productImageId while unsetting the flag on the rest of the images for the given $productId
	*
	* @param int $productId
	* @param int $productImageId
	* @return bool false if any db error occurred otherwise true
	*/
	private function _setProductThumbnail($productId, $productImageId)
	{
		//echo "(thumbnail:$productId,$productImageId)\n";
		//return true; // testing

		$productId = (int)$productId;
		$productImageId = (int)$productImageId;

		/*
		$db = $GLOBALS['ISC_CLASS_DB'];

		// set imageisthumb column to 3, which isn't used as a value yet -- later we will know to set all other records to 0
		$sql = "UPDATE `[|PREFIX|]product_images` SET imageisthumb = 3 WHERE imageid = " . $productImageId;
		if (!$db->Query($sql)) {
			$this->SetError($db->GetErrorMsg());
			return false;
		}
		*/

		$this->_imageThumbnails[] = $productImageId;

		return true;
	}

	private function _finaliseProductThumbnails()
	{
		$db = $GLOBALS['ISC_CLASS_DB'];

		$sql = "UPDATE `[|PREFIX|]product_images` SET imageisthumb = 0";
		if (!$db->Query($sql)) {
			$this->SetError($db->GetErrorMsg());
			return false;
		}

		// set the new thumbnails
		if (!empty($this->_imageThumbnails)) {
			$sql = "UPDATE `[|PREFIX|]product_images` SET imageisthumb = 1 WHERE imageid IN (" . implode(',', $this->_imageThumbnails) . ")";
			//var_dump($sql);
			if (!$db->Query($sql)) {
				$this->SetError($db->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	private $_productImageSortValues = array();

	private function _setProductImageSort($imageId, $imageSort)
	{
		//echo "(sort:$imageId,$imageSort)\n";
		//return true; // testing

		$imageId = (int)$imageId;
		$imageSort = (int)$imageSort;

		/*
		$db = $GLOBALS['ISC_CLASS_DB'];

		$sql = "UPDATE `[|PREFIX|]product_images` SET imagesort = " . $imageSort . " WHERE imageid = " . $imageId;
		if (!$db->Query($sql)) {
			$this->SetError($db->GetErrorMsg());
			return false;
		}
		*/

		if (!isset($this->_productImageSortValues[$imageSort])) {
			$this->_productImageSortValues[$imageSort] = array($imageId);
		} else {
			$this->_productImageSortValues[$imageSort][] = $imageId;
		}

		return true;
	}

	private function _finaliseProductImageSort()
	{
		$db = $GLOBALS['ISC_CLASS_DB'];

		foreach ($this->_productImageSortValues as $value => $imageIdList) {
			if (!empty($imageIdList)) {
				$sql = "UPDATE `[|PREFIX|]product_images` SET imagesort = " . $value . " WHERE imageid IN (" . implode(',', $imageIdList) . ")";
				//var_dump($sql);
				if (!$db->Query($sql)) {
					$this->SetError($db->GetErrorMsg());
					return false;
				}
			}
		}

		return true;
	}

	private function _finaliseDateAdded()
	{
		$db = $GLOBALS['ISC_CLASS_DB'];

		$sql = "UPDATE `[|PREFIX|]product_images` SET imagedateadded = " . time();
		if (!$db->Query($sql)) {
			$this->SetError($db->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function convert_product_images()
	{
		// imageisthumb0 = {filename}{random}.{ext}
		// imageisthumb1 = {filename}{random}_thumb.{ext} when automatically generated from imageisthumb0 where {filename}, {random} and {ext} match imageisthumb0
		// imageisthumb1 = {filename}{random}.{ext} when uploaded separately where {filename} may match depending on the file uploaded but {random} should be different
		// which means if imageisthumb1 is _thumb and both it's {filename} and {random} matches imageisthumb0's then it can be assumed to be the same image as the first product image and imageisthumb1 can be discarded while
		// otherwise it can be assumed to be a separate image and should be kept
		// imageisthumb2 is always discarded

		$db = $GLOBALS['ISC_CLASS_DB'];


		if (!$this->add_product_images_imagefile_index()) {
			return false;
		}

		// fetch list of all images we need to check - if the row has an imagedateadded that means it has already been processed and can be skipped
		$sql = "SELECT p.productid, (SELECT COUNT(*) FROM `[|PREFIX|]product_images` sub WHERE sub.imagefile = pi.imagefile) as imagefileuses, pi.* FROM `[|PREFIX|]product_images` pi LEFT JOIN `[|PREFIX|]products` p ON p.productid = pi.imageprodid WHERE pi.imagedateadded IS NULL OR pi.imagedateadded = 0 ORDER BY pi.imageprodid, pi.imageisthumb, pi.imagesort";
		$result = $db->Query($sql);
		if (!$result) {
			$this->SetError($db->GetErrorMsg());
			return false;
		}

		if (!$this->remove_product_images_imagefile_index()) {
			return false;
		}

		$productId = 0;

		while ($row = $db->Fetch($result)) {
			if (!$row['imagefile']) {
				// somehow the imagefile field is blank, so just remove it from the db
				if (!$this->_removeProductImage($row['imageid'], false)) {
					return false;
				}
				continue;
			}

			if ($row['productid'] === null || (int)$row['imageisthumb'] == 2) {
				// join to products table failed so the image is not attached to any product in the database (could be an old aborted 'add product', could be the product was deleted but the image wasn't), may as well clean it out while we're doing this
				// plus, all 'tiny' image records can be discarded as they will be generated based on the new thumbnail setup
				if (!$this->_removeProductImage($row['imageid'], $row['imagefile'], $row['imagefileuses'])) {
					return false;
				}
				continue;
			}

			// cast some string values to ints
			$row['imageid'] = intval($row['imageid']);
			$row['imageprodid'] = intval($row['imageprodid']);
			$row['imageisthumb'] = intval($row['imageisthumb']);

			if ($row['imageprodid'] !== $productId) {
				// commit any changes for the previous product ...
				if ($productId) {
					//	... if we're not at the very start of the loop
					if (!$this->_setProductThumbnail($productId, $thumbnailImage['imageid'])) {
						return false;
					}
				}

				$productId = $row['imageprodid']; // looping over to a new set of product images

				//echo "\n--- (product:$productId)\n";

				if (!preg_match('#/(?P<filename>.+)__(?P<random>[0-9]{5})\.(?P<extension>.+)$#', $row['imagefile'], $firstImageFileParts)) {
					// the image in the db does not match the standard {filename}__{random}.{ext} pattern
					// I think the only thing we can do is setup a bogus $firstImageFileParts so that the rest of the images assigned to the product are added
					$firstImageFileParts = array(
						'filename' => '',
						'random' => '',
						'extension' => '',
					);
				}

				$firstImage = $row; // store the first image for this product
				$thumbnailImage = $row; // by default the first image for a product is used as the thumbnail
				//if (!$this->_setProductImageDateAdded($row['imageid'])) {
				//	return;
				//}
				continue; // the first image is always kept, so the loop can end here
			}

			if ($row['imageisthumb'] == 1) {
				// specific thumbnail image

				if (preg_match('#/(?P<filename>.+)__(?P<random>[0-9]{5})_thumb\.(?P<extension>.+)$#i', $row['imagefile'], $matches)) {
					// looks like an automatically generated thumbnail - is it the same as the first product image?
					if ($matches['filename'] == $firstImageFileParts['filename'] && $matches['random'] == $firstImageFileParts['random'] && $matches['extension'] == $firstImageFileParts['extension']) {
						// safe to assume this image was automatically generated from the first product image and can be discarded as the new image management code will generate it again
						if (!$this->_removeProductImage($row['imageid'], $row['imagefile'], $row['imagefileuses'])) {
							return false;
						}
						continue;
					}
				}

				// uniquely uploaded thumbnail, use it specificially
				$thumbnailImage = $row;
			}

			// at this point the image is either a unique thumbnail upload or one of the 4 other possibly uploaded images the image should be kept

			// mark it as 'added' so it won't be reprocessed if the upgrade fails
			//if (!$this->_setProductImageDateAdded($row['imageid'])) {
			//	return;
			//}
		}

		if (!$productId) {
			// if $productId is still 0 at this point then nothing was done above, which means there were either no images in the db or the store's images have already been upgraded -- abort
			return true;
		}

		// at end of loop the final product won't be updated yet, do it now
		if (!$this->_setProductThumbnail($productId, $thumbnailImage['imageid'])) {
			return false;
		}

		if (!$this->_finaliseProductImageRemovals()) {
			return false;
		}

		if (!$this->_finaliseProductThumbnails()) {
			return false;
		}

		if (!$this->_finaliseDateAdded()) {
			return false;
		}

		// clean up the imagesort field since images have been removed directly from the db and the sort field should be contiguous from 0
		$sql = "SELECT imageid, imageprodid, imagesort FROM `[|PREFIX|]product_images` ORDER BY imageprodid, imagesort";
		if (!$result = $db->Query($sql)) {
			$this->SetError($db->GetErrorMsg());
			return false;
		}

		$productId = -1;
		while ($row = $db->Fetch($result)) {
			$row['imageid'] = (int)$row['imageid'];
			$row['imageprodid'] = (int)$row['imageprodid'];
			$row['imagesort'] = (int)$row['imagesort'];

			if ($row['imageprodid'] !== $productId) {
				// new product, (re)set the counter
				$imageCount = 0;
				$productId = $row['imageprodid'];
			}

			// the current imagesort value may already match, no need to UPDATE if it does
			if ($row['imagesort'] !== $imageCount) {
				if (!$this->_setProductImageSort($row['imageid'], $imageCount)) {
					return false;
				}
			}

			$imageCount++;
		}

		if (!$this->_finaliseProductImageSort()) {
			return false;
		}

		return true;
	}

	public function add_images_template_fields()
	{
		// add product images fields for the default template
		$query = "SELECT * FROM [|PREFIX|]export_template_fields WHERE exporttemplateid = 1 AND fieldid LIKE 'productImage%'";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!$GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			$fields = array(
				array('productImages', 'products', 'Images', 1, 46),
				array('productImageFile', 'products', 'Image File Name', 1, 47),
				array('productImageURL', 'products', 'Image URL', 1, 48),
			);

			foreach ($fields as $field) {
				$insert = array(
					'exporttemplateid'	=> '1',
					'fieldid'			=> $field[0],
					'fieldtype'			=> $field[1],
					'fieldname'			=> $field[2],
					'includeinexport'	=> $field[3],
					'sortorder'			=> $field[4]
				);

				$GLOBALS['ISC_CLASS_DB']->InsertQuery('export_template_fields', $insert);
			}
		}

		return true;
	}

	public function add_productvideos_table()
	{
		if (!$this->TableExists('product_videos')) {
			$query = "
				CREATE TABLE `[|PREFIX|]product_videos` (
					`video_id` VARCHAR( 25 ) NOT NULL ,
					`video_product_id` INT( 11 ) UNSIGNED NOT NULL ,
					`video_sort_order` INT( 11 ) UNSIGNED NOT NULL ,
					`video_title` VARCHAR( 255 ) NOT NULL ,
					`video_description` TEXT NOT NULL ,
					`video_length` VARCHAR( 10 ) NOT NULL,
					PRIMARY KEY ( `video_id` , `video_product_id` )
				) ENGINE=MYISAM CHARSET=utf8 COLLATE utf8_general_ci";

			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}


	public function add_uk_states_avon_and_bristol()
	{
		$counties = array(
			'Avon',
			'Bristol',
		);

		foreach($counties as $county) {
			$query = "SELECT * FROM [|PREFIX|]country_states WHERE statename='" . $county . "' AND statecountry = 225";
			$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
			if (!$GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
				$insert = array(
					'statename'		=> $county,
					'statecountry'	=> '225',
					'stateabbrv'	=> ''
				);

				$GLOBALS['ISC_CLASS_DB']->InsertQuery('country_states', $insert);
			}
		}
		return true;
	}


	public function add_pagemetatitle_field()
	{
		if (!$this->ColumnExists('[|PREFIX|]pages', 'pagemetatitle')) {
			$query = "ALTER TABLE `[|PREFIX|]pages` ADD `pagemetatitle` varchar(250) NOT NULL default ''";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_laos_north_korea()
	{
		// add laos
		$query = "SELECT * FROM [|PREFIX|]countries WHERE countryiso3 = 'LAO'";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!$GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			$insert = array(
					'countryname'		=> "Lao People's Democratic Republic",
					'countryiso2'		=> 'LA',
					'countryiso3'		=> 'LAO'
			);

			$GLOBALS['ISC_CLASS_DB']->InsertQuery('countries', $insert);
		}

		// add north korea
		$query = "SELECT * FROM [|PREFIX|]countries WHERE countryiso3 = 'PRK'";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!$GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			$insert = array(
					'countryname'		=> "Korea, Democratic People's Republic of",
					'countryiso2'		=> 'KP',
					'countryiso3'		=> 'PRK'
			);

			$GLOBALS['ISC_CLASS_DB']->InsertQuery('countries', $insert);
		}

		return true;
	}

	public function update_taiwan()
	{
		$query = "UPDATE [|PREFIX|]countries SET countryname = 'Taiwan' WHERE countryiso3 = 'TWN' AND countryname = 'Taiwan, Province of China'";
		$GLOBALS['ISC_CLASS_DB']->Query($query);

		return true;
	}

	public function add_xmlsitemap_permission()
	{
		// Array of new permission => insert in to users that have the following permission
		$perms = array(
			AUTH_View_XMLSitemap => AUTH_Export_Froogle
		);

		// Delete any existing occurances of these permissions
		$permIds = array_keys($perms);
		$permIds = implode(',', $permIds);
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('permissions', "WHERE permpermissionid IN (".$permIds.")");

		// OK, do some magic.. well it's not actually manage nor is it anything cool, but you get the point.
		foreach($perms as $permission => $insertWhere) {
			$query = $GLOBALS['ISC_CLASS_DB']->Query("SELECT permuserid FROM [|PREFIX|]permissions WHERE permpermissionid='".(int)$insertWhere."'");
			while($user = $GLOBALS['ISC_CLASS_DB']->Fetch($query)) {
				$newPermission = array(
					'permuserid' => $user['permuserid'],
					'permpermissionid' => $permission
				);
				if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery('permissions', $newPermission)) {
					$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
					return false;
				}
			}
		}


		return true;
	}

	public function add_website_optimizer_permission()
	{
		$query = "Select * from [|PREFIX|]users";
		$userq = $GLOBALS['ISC_CLASS_DB']->Query($query);

		while($user = $GLOBALS['ISC_CLASS_DB']->Fetch($userq)) {

			if($user['userrole'] == 'admin' || $user['userrole'] == 'manager') {

				$newPermission = array(
					'permuserid' => $user['pk_userid'],
					'permpermissionid' => AUTH_Website_Optimizer
				);

				if(!$GLOBALS['ISC_CLASS_DB']->InsertQuery('permissions', $newPermission)) {
					$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
					return false;
				}
			}
		}

		return true;
	}

	public function add_optimizer_config_table()
	{
		if (!$this->TableExists("optimizer_config")) {
			$query = "
			CREATE TABLE `[|PREFIX|]optimizer_config` (
				  `optimizer_id` int(11) NOT NULL auto_increment,
				  `optimizer_type` varchar(255) NOT NULL,
				  `optimizer_item_id` int(11) NOT NULL,
				  `optimizer_config_date` int(11) NOT NULL,
				  `optimizer_conversion_page` varchar(255) NOT NULL,
				  `optimizer_conversion_url` varchar(255) NOT NULL,
				  `optimizer_control_script` text NOT NULL,
				  `optimizer_tracking_script` text NOT NULL,
				  `optimizer_conversion_script` text NOT NULL,
				  PRIMARY KEY  (`optimizer_id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
						";

			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_enable_optimizer_field()
	{

		if (!$this->ColumnExists('[|PREFIX|]products', 'product_enable_optimizer')) {

			$query ="ALTER TABLE `[|PREFIX|]products` ADD `product_enable_optimizer` tinyint(1) unsigned NOT NULL DEFAULT '0'";

			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

		}


		if (!$this->ColumnExists('[|PREFIX|]categories', 'cat_enable_optimizer')) {

			$query ="ALTER TABLE `[|PREFIX|]categories` ADD `cat_enable_optimizer` TINYINT( 1 ) NOT NULL DEFAULT '0'";

			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

		}


		if (!$this->ColumnExists('[|PREFIX|]pages', 'page_enable_optimizer')) {

			$query ="ALTER TABLE `[|PREFIX|]pages` ADD `page_enable_optimizer` TINYINT( 1 ) NOT NULL DEFAULT '0'";

			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}

		}
		return true;
	}

	public function add_categories_column_nsetleft()
	{
		if (!$this->ColumnExists('[|PREFIX|]categories', 'catnsetleft')) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query("ALTER TABLE `[|PREFIX|]categories` ADD COLUMN `catnsetleft` INT(11) UNSIGNED NOT NULL DEFAULT 0")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_categories_column_nsetright()
	{
		if (!$this->ColumnExists('[|PREFIX|]categories', 'catnsetright')) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query("ALTER TABLE `[|PREFIX|]categories` ADD COLUMN `catnsetright` INT(11) UNSIGNED NOT NULL DEFAULT 0")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_categories_i_categoryid_catnsetleft_catnsetright()
	{
		if (!$this->IndexExists('[|PREFIX|]categories', 'i_categoryid_catnsetleft_catnsetright')) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query("ALTER TABLE `[|PREFIX|]categories` ADD INDEX i_categoryid_catnsetleft_catnsetright (`categoryid` , `catnsetleft`, `catnsetright`);")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_categories_i_catnsetleft()
	{
		if (!$this->IndexExists('[|PREFIX|]categories', 'i_catnsetleft')) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query("ALTER TABLE `[|PREFIX|]categories` ADD INDEX i_catnsetleft (`catnsetleft`);")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_categories_i_catparentid_catsort_catname()
	{
		if (!$this->IndexExists('[|PREFIX|]categories', 'i_catparentid_catsort_catname')) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query("ALTER TABLE `[|PREFIX|]categories` ADD INDEX i_catparentid_catsort_catname (`catparentid` , `catsort`, `catname`);")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_categories_i_catvisible_catsort_catname_index()
	{
		if (!$this->IndexExists('[|PREFIX|]categories', 'i_catvisible_catsort_catname')) {
			$query = "ALTER TABLE `[|PREFIX|]categories` ADD INDEX `i_catvisible_catsort_catname` (`catvisible`,`catsort`,`catname`)";
			if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function rebuild_categories_nset()
	{
		$nested = new ISC_NESTEDSET_CATEGORIES();
		return $nested->rebuildTree();
	}

	public function add_pages_column_nsetleft()
	{
		if (!$this->ColumnExists('[|PREFIX|]pages', 'pagensetleft')) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query("ALTER TABLE `[|PREFIX|]pages` ADD COLUMN `pagensetleft` INT(11) UNSIGNED NOT NULL DEFAULT 0")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_pages_column_nsetright()
	{
		if (!$this->ColumnExists('[|PREFIX|]pages', 'pagensetright')) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query("ALTER TABLE `[|PREFIX|]pages` ADD COLUMN `pagensetright` INT(11) UNSIGNED NOT NULL DEFAULT 0")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_pages_i_pageid_pagensetleft_pagensetright()
	{
		if (!$this->IndexExists('[|PREFIX|]pages', 'i_pageid_pagensetleft_pagensetright')) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query("ALTER TABLE `[|PREFIX|]pages` ADD INDEX i_pageid_pagensetleft_pagensetright (`pageid` , `pagensetleft`, `pagensetright`);")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_pages_i_pagensetleft()
	{
		if (!$this->IndexExists('[|PREFIX|]pages', 'i_pagensetleft')) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query("ALTER TABLE `[|PREFIX|]pages` ADD INDEX i_pagensetleft (`pagensetleft`);")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function add_pages_i_pageparentid_pagesort_pagetitle()
	{
		if (!$this->IndexExists('[|PREFIX|]pages', 'i_pageparentid_pagesort_pagetitle')) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query("ALTER TABLE `[|PREFIX|]pages` ADD INDEX i_pageparentid_pagesort_pagetitle (`pageparentid` , `pagesort`, `pagetitle`);")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}

	public function rebuild_pages_nset()
	{
		$nested = new ISC_NESTEDSET_PAGES();
		return $nested->rebuildTree();
	}

	public function add_order_last_modified()
	{
		if (!$this->ColumnExists("[|PREFIX|]orders", "ordlastmodified")) {
			$query = "ALTER TABLE [|PREFIX|]orders ADD `ordlastmodified` int not null default '0' AFTER `orddate`";

			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_customer_last_modified()
	{
		if (!$this->ColumnExists("[|PREFIX|]customers", "custlastmodified")) {
			$query = "ALTER TABLE [|PREFIX|]customers ADD `custlastmodified` int not null default '0' AFTER `custdatejoined`";

			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function remove_accountingspool_table()
	{
		if ($this->TableExists("accountingspool")) {
			$query = "DROP TABLE `[|PREFIX|]accountingspool`";

			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function alter_accountingref_table()
	{
		$query = "ALTER TABLE [|PREFIX|]accountingref MODIFY `accountingreftype` varchar(20) NOT NULL DEFAULT ''";

		if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		if (!$this->ColumnExists("[|PREFIX|]accountingref", "accountingrefexternalid")) {
			$query = "ALTER TABLE [|PREFIX|]accountingref ADD `accountingrefexternalid` varchar(100) NOT NULL DEFAULT '' AFTER `accountingrefmoduleid`";

			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		/**
		 * We'll need to fill in the new accountingrefexternalid column
		 */
		$query = "SELECT *
					FROM [|PREFIX|]accountingref
					WHERE accountingrefexternalid = ''";

		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {

			$value = @unserialize($row["accountingrefvalue"]);
			$externalId = '';
			$undateValueToo = false;

			if (is_array($value)) {
				switch (isc_strtolower($row["accountingreftype"])) {
					case "orderlineitem":
						if (array_key_exists("TxnLineID", $value)) {
							$externalId = $value["TxnLineID"];
						}

						break;

					case "salesorder":
					case "salesreceipt":
						if (array_key_exists("TxnID", $value)) {
							$externalId = $value["TxnID"];
						} else if (array_key_exists("TnxID", $value)) {
							$externalId = $value["TnxID"];
						}

						break;

					case "account":
					case "customer":
					case "product":
						if (array_key_exists("ListID", $value)) {
							$externalId = $value["ListID"];
						}

						break;
				}
			}

			if (trim($externalId) == "") {
				$GLOBALS["ISC_CLASS_DB"]->DeleteQuery("accountingref", "WHERE accountingrefid = " . (int)$row["accountingrefid"]);
			} else {
				$savedata = array(
					"accountingrefexternalid" => $externalId
				);

				if (isc_strtolower($row["accountingreftype"]) == "account") {
					$savedata["accountingreftype"] = "prerequisite";
				}

				if ((isc_strtolower($row["accountingreftype"]) == "salesorder" || isc_strtolower($row["accountingreftype"]) == "salesreceipt") && array_key_exists("TnxID", $value)) {
					$value["TxnID"] = $value["TnxID"];
					unset($value["TnxID"]);

					$savedata["accountingrefvalue"] = @serialize($value);

					if (trim($savedata["accountingrefvalue"]) == "") {
						$GLOBALS["ISC_CLASS_DB"]->DeleteQuery("accountingref", "WHERE accountingrefid = " . (int)$row["accountingrefid"]);
						continue;
					}
				}

				$GLOBALS["ISC_CLASS_DB"]->UpdateQuery("accountingref", $savedata, "accountingrefid = " . (int)$row["accountingrefid"]);
			}
		}

		if (!$this->IndexExists("[|PREFIX|]accountingref", "i_accountingref_accountingrefexternalid")) {
			$query = "ALTER TABLE [|PREFIX|]accountingref ADD INDEX `i_accountingref_accountingrefexternalid` (`accountingrefexternalid`)";

			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_search_columns()
	{
		$queries = array();

		if (!$this->ColumnExists("[|PREFIX|]brands", "brandsearchkeywords")) {
			$queries[] = "ALTER TABLE [|PREFIX|]brands ADD `brandsearchkeywords` varchar(255) NOT NULL default ''";
		}

		if (!$this->ColumnExists("[|PREFIX|]categories", "catsearchkeywords")) {
			$queries[] = "ALTER TABLE [|PREFIX|]categories ADD `catsearchkeywords` varchar(255) NOT NULL default ''";
		}

		if (!$this->ColumnExists("[|PREFIX|]news", "newssearchkeywords")) {
			$queries[] = "ALTER TABLE [|PREFIX|]news ADD `newssearchkeywords` varchar(255) NOT NULL default ''";
		}

		if (!$this->ColumnExists("[|PREFIX|]pages", "pagesearchkeywords")) {
			$queries[] = "ALTER TABLE [|PREFIX|]pages ADD `pagesearchkeywords` varchar(255) NOT NULL default ''";
		}

		foreach ($queries as $query) {
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function add_brand_search_tables()
	{
		$doSearch = array();
		$doSuggested = array();

		if (!$this->TableExists("brand_search")) {
			$query = "
					CREATE TABLE IF NOT EXISTS `[|PREFIX|]brand_search` (
						`brandsearchid` int(11) NOT NULL auto_increment,
						`brandid` int(11) NOT NULL default '0',
						`brandname` varchar(250) NOT NULL default '',
						`brandpagetitle` varchar(250) NOT NULL default '',
						`brandsearchkeywords` varchar(255) NOT NULL default '',
						PRIMARY KEY  (`brandsearchid`),
						KEY `i_brand_search_brandid` (`brandid`),
						FULLTEXT KEY `brandname` (`brandname`,`brandpagetitle`,`brandsearchkeywords`),
						FULLTEXT KEY `brandname2` (`brandname`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;";

			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}

			$this->rebuildSearchData("brand");
		}

		if (!$this->TableExists("brand_words")) {
			$query = "
					CREATE TABLE IF NOT EXISTS `[|PREFIX|]brand_words` (
						`wordid` int(11) NOT NULL auto_increment,
						`word` varchar(255) NOT NULL default '',
						`brandid` int(11) NOT NULL default '0',
						PRIMARY KEY  (`wordid`),
						KEY `word` (`word`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;";

			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}

			$this->rebuildSuggestedData("brand");
		}

		return true;
	}

	public function add_category_search_tables()
	{
		if (!$this->TableExists("category_search")) {
			$query = "
					CREATE TABLE IF NOT EXISTS `[|PREFIX|]category_search` (
						`categorysearchid` int(11) NOT NULL auto_increment,
						`categoryid` int(11) NOT NULL default '0',
						`catname` varchar(250) NOT NULL default '',
						`catdesc` text NOT NULL,
						`catsearchkeywords` varchar(255) NOT NULL default '',
						PRIMARY KEY  (`categorysearchid`),
						KEY `i_category_search_categoryid` (`categoryid`),
						FULLTEXT KEY `catname` (`catname`,`catdesc`,`catsearchkeywords`),
						FULLTEXT KEY `catname2` (`catname`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}

			$this->rebuildSearchData("category");
		}

		if (!$this->TableExists("category_words")) {
			$query = "
					CREATE TABLE IF NOT EXISTS `[|PREFIX|]category_words` (
						`wordid` int(11) NOT NULL auto_increment,
						`word` varchar(255) NOT NULL default '',
						`categoryid` int(11) NOT NULL default '0',
						PRIMARY KEY  (`wordid`),
						KEY `word` (`word`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;";

			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}

			$this->rebuildSuggestedData("category");
		}

		return true;
	}

	public function add_news_search_tables()
	{
		if (!$this->TableExists("news_search")) {
			$query = "
					CREATE TABLE IF NOT EXISTS `[|PREFIX|]news_search` (
						`newssearchid` int(11) NOT NULL auto_increment,
						`newsid` int(11) NOT NULL default '0',
						`newstitle` varchar(255) NOT NULL default '',
						`newscontent` longtext NOT NULL,
						`newssearchkeywords` varchar(255) NOT NULL default '',
						PRIMARY KEY  (`newssearchid`),
						KEY `i_news_search_newsid` (`newsid`),
						FULLTEXT KEY `newstitle` (`newstitle`,`newscontent`,`newssearchkeywords`),
						FULLTEXT KEY `newstitle2` (`newstitle`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}

			$this->rebuildSearchData("news");
		}

		if (!$this->TableExists("news_words")) {
			$query = "
					CREATE TABLE IF NOT EXISTS `[|PREFIX|]news_words` (
						`wordid` int(11) NOT NULL auto_increment,
						`word` varchar(255) NOT NULL default '',
						`newsid` int(11) NOT NULL default '0',
						PRIMARY KEY  (`wordid`),
						KEY `word` (`word`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;";

			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}

			$this->rebuildSuggestedData("news");
		}

		return true;
	}

	public function add_page_search_tables()
	{
		if (!$this->TableExists("page_search")) {
			$query = "
					CREATE TABLE IF NOT EXISTS `[|PREFIX|]page_search` (
						`pagesearchid` int(11) NOT NULL auto_increment,
						`pageid` int(11) NOT NULL default '0',
						`pagetitle` varchar(255) NOT NULL default '',
						`pagecontent` longtext NOT NULL,
						`pagedesc` text NOT NULL,
						`pagesearchkeywords` varchar(255) NOT NULL default '',
						PRIMARY KEY  (`pagesearchid`),
						KEY `i_page_search_pageid` (`pageid`),
						FULLTEXT KEY `pagetitle` (`pagetitle`,`pagecontent`,`pagedesc`,`pagesearchkeywords`),
						FULLTEXT KEY `pagetitle2` (`pagetitle`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}

			$this->rebuildSearchData("page");
		}

		if (!$this->TableExists("page_words")) {
			$query = "
					CREATE TABLE IF NOT EXISTS `[|PREFIX|]page_words` (
						`wordid` int(11) NOT NULL auto_increment,
						`word` varchar(255) NOT NULL default '',
						`pageid` int(11) NOT NULL default '0',
						PRIMARY KEY  (`wordid`),
						KEY `word` (`word`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;";

			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}

			$this->rebuildSuggestedData("page");
		}

		return true;
	}

	public function rebuild_product_search_data()
	{
		$queries = array();

		if (!$this->IndexExists("[|PREFIX|]product_search", "i_product_search_productid")) {
			$queries[] = "ALTER TABLE [|PREFIX|]product_search ADD INDEX `i_product_search_productid` (`productid`)";
		}

		if (!$this->IndexExists("[|PREFIX|]product_search", "i_product_search_prodcode")) {
			$queries[] = "ALTER TABLE [|PREFIX|]product_search ADD INDEX `i_product_search_prodcode` (`prodcode`)";
		}

		foreach ($queries as $query) {
			if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
				$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
				return false;
			}
		}

		$this->rebuildSearchData("product");

		// Just LOWERcase all the word values in product_words as the content is already in there
		$query = "UPDATE [|PREFIX|]product_words
					SET word = LOWER(word)";

		if (!$GLOBALS["ISC_CLASS_DB"]->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		return true;
	}

	private function rebuildSearchData($type)
	{
		$map = array();
		$stripped = array();

		switch (isc_strtolower($type)) {
			case "brand":
				$map = array(
						"brandid",
						"brandname",
						"brandpagetitle",
						"brandsearchkeywords"
				);

				break;

			case "category":
				$map = array(
						"categoryid",
						"catname",
						"catdesc",
						"catsearchkeywords"
				);

				$stripped = array("catdesc");

				break;

			case "page":
				$map = array(
						"pageid",
						"pagetitle",
						"pagecontent",
						"pagedesc",
						"pagesearchkeywords"
				);

				$stripped = array("pagecontent", "pagedesc");

				break;

			case "product";
				$map = array(
						"productid",
						"prodname",
						"prodcode",
						"proddesc",
						"prodsearchkeywords"
				);

				$stripped = array("proddesc");

				break;

			case "news":
				$map = array(
						"newsid",
						"newstitle",
						"newscontent",
						"newssearchkeywords"
				);

				$stripped = array("newscontent");

				break;

			default:
				return true;
		}

		$searchTable = $type . "_search";

		if ($GLOBALS["ISC_CLASS_DB"]->DeleteQuery($searchTable, "") === false) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		if ($type == "category") {
			$sourceTable = "categories";
		} else if ($type == "news") {
			$sourceTable = "news";
		} else {
			$sourceTable = $type . "s";
		}

		$query = "SELECT " . implode(",", $map) . "
					FROM [|PREFIX|]" . $sourceTable;

		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
			foreach ($stripped as $strip) {
				if (array_key_exists($strip, $row)) {
					$row[$strip] = stripHTMLForSearchTable($row[$strip]);
				}
			}

			$GLOBALS["ISC_CLASS_DB"]->InsertQuery($searchTable, $row);
		}

		return true;
	}

	private function rebuildSuggestedData($type)
	{
		$type = isc_strtolower(trim($type));
		$sourceTable = "";
		$idColumn = "";
		$nameColumn = "";

		switch ($type) {
			case "brand":
				$sourceTable = "brands";
				$idColumn = "brandid";
				$nameColumn = "brandname";
				break;

			case "category":
				$sourceTable = "categories";
				$idColumn = "categoryid";
				$nameColumn = "catname";
				break;

			case "news":
				$sourceTable = "news";
				$idColumn = "newsid";
				$nameColumn = "newstitle";
				break;

			case "page":
				$sourceTable = "pages";
				$idColumn = "pageid";
				$nameColumn = "pagetitle";
				break;

			default:
				return true;
		}

		$query = "SELECT " . $idColumn . "," . $nameColumn . "
					FROM [|PREFIX|]" . $sourceTable;

		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
			Store_SearchSuggestion::manageSuggestedWordDatabase($type, $row[$idColumn], $row[$nameColumn]);
		}

		return true;
	}


	public function add_abandonorders_template_fields()
	{
		$query = "SELECT * FROM [|PREFIX|]export_template_fields WHERE exporttemplateid = 1 AND fieldid = 'abandonorderOrderId'";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!$GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			$fields = array(
				array('abandonorderOrderId', 'abandonorder', 'Order Id', 1, 0),
				array('abandonorderCustomerName', 'abandonorder', 'Customer Name', 1, 1),
				array('abandonorderCustomerEmail', 'abandonorder', 'Customer Email', 1, 2),
				array('abandonorderCustomerPhone', 'abandonorder', 'Customer Phone', 1, 3),
				array('abandonorderDate', 'abandonorder', 'Date', 1, 4),
				array('abandonorderTotalOrderAmount', 'abandonorder', 'Total Order Amount', 1, 5),
			);

			foreach ($fields as $field) {
				$insert = array(
					'exporttemplateid'	=> '1',
					'fieldid'			=> $field[0],
					'fieldtype'			=> $field[1],
					'fieldname'			=> $field[2],
					'includeinexport'	=> $field[3],
					'sortorder'			=> $field[4]
				);

				$GLOBALS['ISC_CLASS_DB']->InsertQuery('export_template_fields', $insert);
			}
		}

		// add abandonorder to used types for default template
		$query = "SELECT usedtypes FROM [|PREFIX|]export_templates WHERE exporttemplateid = 1";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
			$usedtypes = explode(',', $row['usedtypes']);
			if (!in_array('abandonorder', $usedtypes)) {
				$usedtypes[] = 'abandonorder';

				$query = "UPDATE [|PREFIX|]export_templates SET usedtypes = '" . implode(',', $usedtypes) . "' WHERE exporttemplateid = 1";
				$GLOBALS['ISC_CLASS_DB']->Query($query);
			}
		}

		return true;
	}


	public function add_productweight_template_fields()
	{
		$query = "SELECT * FROM [|PREFIX|]export_template_fields WHERE exporttemplateid = 1 AND fieldid = 'orderCombinedWeight'";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!$GLOBALS['ISC_CLASS_DB']->CountResult($res)) {

			$query = "SELECT * FROM [|PREFIX|]export_template_fields WHERE exporttemplateid = 1 and fieldtype= 'orders'";
			$res = $GLOBALS['ISC_CLASS_DB']->Query($query);

			$updateSortOrder = 0;
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
				// insert the product weight before orderProdTotalPrice
				if($row['fieldid'] == 'orderProdTotalPrice') {
					$insert = array(
						'exporttemplateid'	=> '1',
						'fieldid'			=> 'orderProdWeight',
						'fieldtype'			=> 'orders',
						'fieldname'			=> 'Product Weight',
						'includeinexport'	=> '1',
						'sortorder'			=> ($row['sortorder'] + $updateSortOrder)
					);

					$updateSortOrder++;

					$GLOBALS['ISC_CLASS_DB']->InsertQuery('export_template_fields', $insert);
				}

				// insert the combined product weight before orderTodaysDate
				if($row['fieldid'] == 'orderTodaysDate') {
					$insert = array(
						'exporttemplateid'	=> '1',
						'fieldid'			=> 'orderCombinedWeight',
						'fieldtype'			=> 'orders',
						'fieldname'			=> 'Combined Product Weight',
						'includeinexport'	=> '1',
						'sortorder'			=> ($row['sortorder'] + $updateSortOrder)
					);

					$updateSortOrder++;

					$GLOBALS['ISC_CLASS_DB']->InsertQuery('export_template_fields', $insert);
				}

				if($updateSortOrder > 0) {
					$GLOBALS['ISC_CLASS_DB']->InsertQuery('export_template_fields', array('sortorder'=>($row['sortorder'] + $updateSortOrder)), 'exporttemplatefieldid=' . $row['exporttemplatefieldid']);
				}
			}
		}

		return true;
	}

	public function add_salestax_template_fields()
	{
		// add sales tax fields for the default template
		$query = "SELECT * FROM [|PREFIX|]export_template_fields WHERE exporttemplateid = 1 AND fieldid = 'salestaxDate'";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if (!$GLOBALS['ISC_CLASS_DB']->CountResult($res)) {
			$fields = array(
				array('salestaxDate', 'salestax', 'Period', 1, 0),
				array('salestaxTaxName', 'salestax', 'Tax', 1, 1),
				array('salestaxTaxRate', 'salestax', 'Rate', 1, 2),
				array('salestaxNumOrders', 'salestax', 'Number of Orders', 1, 3),
				array('salestaxTaxAmount', 'salestax', 'Tax Amount', 1, 4),
			);

			foreach ($fields as $field) {
				$insert = array(
					'exporttemplateid'	=> '1',
					'fieldid'			=> $field[0],
					'fieldtype'			=> $field[1],
					'fieldname'			=> $field[2],
					'includeinexport'	=> $field[3],
					'sortorder'			=> $field[4]
				);

				$GLOBALS['ISC_CLASS_DB']->InsertQuery('export_template_fields', $insert);
			}
		}

		// add sales tax to used types for default template
		$query = "SELECT usedtypes FROM [|PREFIX|]export_templates WHERE exporttemplateid = 1";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
			$usedtypes = explode(',', $row['usedtypes']);
			if (!in_array('salestax', $usedtypes)) {
				$usedtypes[] = 'salestax';

				$query = "UPDATE [|PREFIX|]export_templates SET usedtypes = '" . implode(',', $usedtypes) . "' WHERE exporttemplateid = 1";
				$GLOBALS['ISC_CLASS_DB']->Query($query);
			}
		}

		return true;
	}

	public function set_install_date()
	{
		if (GetConfig('InstallDate') > 0) {
			return true;
		}

		// determine the install date based off the first order
		$query = "SELECT orddate FROM [|PREFIX|]orders ORDER BY orderid LIMIT 1";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($res)) {
			$installDate = $row['orddate'];
		}
		else {
			// no orders? set it to the current time
			$installDate = isc_gmmktime(isc_date("H"), isc_date("i"), isc_date("s"), isc_date("m"), isc_date("d"), isc_date("Y"));
		}

		$GLOBALS['ISC_NEW_CFG']['InstallDate'] = $installDate;

		GetClass('ISC_ADMIN_SETTINGS')->CommitSettings();

		return true;
	}

	public function add_combination_last_modified()
	{
		if (!$this->ColumnExists('[|PREFIX|]product_variation_combinations', 'vclastmodified')) {
			if(!$GLOBALS['ISC_CLASS_DB']->Query("ALTER TABLE `[|PREFIX|]product_variation_combinations` ADD COLUMN `vclastmodified` INT(10) NOT NULL DEFAULT 0")) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}
		return true;
	}
}
