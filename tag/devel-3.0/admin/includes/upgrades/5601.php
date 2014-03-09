<?php
/**
 * Upgrade class for 5.6.1
 * This class runs a series of methods used to upgrade the store to a specific version
 *
 * @package ISC
 * @subpackage ISC_Upgrade
 */

class ISC_ADMIN_UPGRADE_5601 extends ISC_ADMIN_UPGRADE_BASE
{
	public $steps = array(
		"update_bulk_edit_template"
	);

	public function update_bulk_edit_template()
	{
		// get the template ID for the bulk edit template
		$query = "SELECT exporttemplateid FROM [|PREFIX|]export_templates WHERE exporttemplatename = 'Bulk Edit' AND builtin = 1";
		$res = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$templateRow = $GLOBALS['ISC_CLASS_DB']->Fetch($res);
		if (!$templateRow) {
			return true;
		}

		// update the sort order of the fields in this template so they're sequential when updating the productID field below
		$query = "
			UPDATE
				[|PREFIX|]export_template_fields a,
				[|PREFIX|]export_template_fields b
			SET
				a.sortorder = a.sortorder + 1
			WHERE
				a.exporttemplateid = " . $templateRow['exporttemplateid'] . " AND
				a.fieldtype = 'products' AND
				a.sortorder < b.sortorder AND
				b.exporttemplateid = " . $templateRow['exporttemplateid'] . " AND
				b.fieldid = 'productID'
		";
		$GLOBALS['ISC_CLASS_DB']->Query($query);

		// enable the product ID field and set its sort order to be zero
		$update = array(
			'includeinexport' => 1,
			'sortorder' => 0
		);

		$GLOBALS['ISC_CLASS_DB']->UpdateQuery('export_template_fields', $update, "exporttemplateid=" . $templateRow['exporttemplateid'] . " AND fieldid = 'productID'");

		// enable the sort order field
		$query = "
			SELECT
				exporttemplatefieldid
			FROM
				[|PREFIX|]export_template_fields
			WHERE
				exporttemplateid = " . $templateRow['exporttemplateid'] . " AND
				fieldid = 'productSortOrder'
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
				'fieldid' => 'productSortOrder',
				'fieldtype' => 'products',
				'fieldname'	=> 'Sort Order',
				'includeinexport' => 1,
				'sortorder' => 70
			);
			$GLOBALS['ISC_CLASS_DB']->InsertQuery('export_template_fields', $insert);
		}

		return true;
	}
}
