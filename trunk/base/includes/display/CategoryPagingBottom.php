<?php
class ISC_CATEGORYPAGINGBOTTOM_PANEL extends PANEL
{
	public function SetPanelSettings()
	{
		require_once ISC_BASE_PATH . '/includes/display/CategoryPagingTop.php';
		if (!ISC_CATEGORYPAGINGTOP_PANEL::generatePagingPanel()) {
			$this->DontDisplay = true;
		}
	}
}