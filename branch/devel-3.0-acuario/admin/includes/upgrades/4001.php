<?php
class ISC_ADMIN_UPGRADE_4001 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array (
		'add_product_image_hash',
		'sort_variation_combination',
		'add_northern_ireland_counties',
	);

	public function pre_upgrade_checks()
	{
		if(is_dir(ISC_BASE_PATH."/modules/analytics/trackpoint")) {
			$this->SetError('Please delete the /modules/analytics/trackpoint directory from your store. This module has been removed and is no longer necessary.');
		}

		if(is_dir(ISC_BASE_PATH."/modules/shipping/freeshipping")) {
			$this->SetError('Please delete the /modules/shipping/freeshipping directory from your store. This module has been removed and is no longer necessary.');
		}
	}

	public function add_product_image_hash()
	{
		if (!$this->ColumnExists('[|PREFIX|]product_images', 'imageprodhash')) {
			$query = "ALTER TABLE [|PREFIX|]product_images ADD imageprodhash VARCHAR(32) NOT NULL DEFAULT '' AFTER imageprodid";
			if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
				$this->SetError($GLOBALS['ISC_CLASS_DB']->GetErrorMsg());
				return false;
			}
		}

		return true;
	}

	public function sort_variation_combination()
	{
		$result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT * FROM [|PREFIX|]product_variation_combinations");
		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$combo = $row['vcoptionids'];
			@sort($combo);

			if (!is_array($combo) || empty($combo)) {
				continue;
			}

			$row['vcoptionids'] = $combo;

			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('product_variation_combinations', $row, 'combinationid=' . (int)$row['combinationid']);
		}

		return true;
	}

	public function add_northern_ireland_counties()
	{
		$savedata = array(
			'statename' => 'Londonderry'
		);

		if ($GLOBALS['ISC_CLASS_DB']->UpdateQuery('country_states', $savedata, 'statecountry=225 AND stateabbrv="DRY"') === false) {
			return false;
		}

		$query = "SELECT * FROM [|PREFIX|]country_states WHERE statecountry=225 AND stateabbrv='TYR'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		if ($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
			$savedata = array(
				'statename' => 'Tyrone',
				'statecountry' => 225,
				'stateabbrv' => 'TYR',
			);

			if ($GLOBALS['ISC_CLASS_DB']->InsertQuery('country_states', $savedata) === false) {
				return false;
			}
		}

		return true;
	}
}