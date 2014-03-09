<?php
class ISC_CATEGORYPAGINGBOTTOMPOINTS_PANEL extends PANEL
{
	public function SetPanelSettings()
	{
		require_once ISC_BASE_PATH . '/includes/display/CategoryPagingTopPoints.php';
		if (!ISC_CATEGORYPAGINGTOPPOINTS_PANEL::generatePagingPanel()) {
			$this->DontDisplay = true;
		}
	}
}