<?php
class ISC_PRODUCTOTHERDETAILS_PANEL extends PANEL
{
	public function SetPanelSettings()
	{
		// Are there any custom fields for this product? If so, load them
		if ($GLOBALS['ISC_CLASS_PRODUCT']->GetNumCustomFields() == 0) {
			$this->DontDisplay = true;
			return;
		}

		$GLOBALS['SNIPPETS']['ProductCustomFields'] = "";

		$query = "
			SELECT *
			FROM [|PREFIX|]product_customfields
			WHERE fieldprodid='".(int)$GLOBALS['ISC_CLASS_PRODUCT']->GetProductId()."'
			ORDER BY fieldid ASC
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$GLOBALS['CustomFieldName'] = isc_html_escape($row['fieldname']);
			$GLOBALS['CustomFieldValue'] = $row['fieldvalue'];
			$GLOBALS['SNIPPETS']['ProductCustomFields'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductCustomFieldItem");
		}
	}
}