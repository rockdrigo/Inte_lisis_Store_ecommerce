<?php
class ISC_GLOBALSTATUSMESSAGE_PANEL extends PANEL
{
	public function setPanelSettings()
	{
		$messages = getFlashMessageBoxes();
		if(!$messages) {
			$this->DontDisplay = true;
			return;
		}

		$GLOBALS['GlobalStatusMessage'] = $messages;
	}
}
