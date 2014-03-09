<?php
CLASS ISC_CONFIRMORDER_PANEL extends PANEL
{
	public function SetPanelSettings()
	{
		$GLOBALS['ISC_CLASS_CHECKOUT'] = GetClass('ISC_CHECKOUT');
		$GLOBALS['ISC_CLASS_CHECKOUT']->BuildOrderConfirmation();
	}
}