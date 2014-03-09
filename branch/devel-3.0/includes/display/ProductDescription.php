<?php
CLASS ISC_PRODUCTDESCRIPTION_PANEL extends PANEL
{
	public function SetPanelSettings()
	{
		$GLOBALS['ProductDesc'] = $GLOBALS['ISC_CLASS_PRODUCT']->GetDesc();
		if(!trim($GLOBALS['ProductDesc'])) {
			$GLOBALS['HidePanels'][] = 'ProductDescription';
		}

	}
}