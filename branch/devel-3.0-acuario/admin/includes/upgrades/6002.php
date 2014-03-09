<?php

/**
 * Upgrade class for 6.0.2
 * This class runs a series of methods used to upgrade the store to a specific version
 *
 * @package ISC
 * @subpackage ISC_Upgrade
 */
class ISC_ADMIN_UPGRADE_6002 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		"add_product_upc",
		"add_upc_to_bulk_edit_template"
	);

	public function add_product_upc()
	{
		if($this->ColumnExists('[|PREFIX|]products', 'upc')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]products ADD COLUMN upc VARCHAR(32) DEFAULT ''";

		if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
			$this->SetError($GLOBALS["ISC_CLASS_DB"]->GetErrorMsg());
			return false;
		}

		return true;
	}

	public function add_upc_to_bulk_edit_template()
	{
		// get the template ID for the bulk edit template
		$query = "SELECT exporttemplateid FROM [|PREFIX|]export_templates WHERE exporttemplatename = 'Bulk Edit' AND builtin = 1";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$templateRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res);
		if (!$templateRow) {
			return true;
		}

		// enable the product UPC field
		$query = "
			SELECT
				exporttemplatefieldid
			FROM
				[|PREFIX|]export_template_fields
			WHERE
				exporttemplateid = " . $templateRow['exporttemplateid'] . " AND
				fieldid = 'productUPC'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if ($fieldRow = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			// field exists, just update it
			$update = array(
				'includeinexport' => 1
			);
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('export_template_fields', $update, "exporttemplatefieldid=" . $fieldRow['exporttemplatefieldid']);
		}
		else {
			// create the field
			$insert = array(
				'exporttemplateid' => $templateRow['exporttemplateid'],
				'fieldid' => 'productUPC',
				'fieldtype' => 'products',
				'fieldname'	=> 'Product UPC',
				'includeinexport' => 1,
				'sortorder' => 71
			);
			$GLOBALS['ISC_CLASS_DB']->InsertQuery('export_template_fields', $insert);
		}

		return true;
	}
}
