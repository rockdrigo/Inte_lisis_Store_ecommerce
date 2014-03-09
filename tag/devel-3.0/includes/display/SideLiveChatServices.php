<?php
/**
 * The live chat services integration panel.
 */
class ISC_SIDELIVECHATSERVICES_PANEL extends PANEL
{
	/**
	 * Set the settings for this panel.
	 */
	public function SetPanelSettings()
	{
		// Do we have any live chat service code to show in the side bar?
		$modules = GetConfig('LiveChatModules');
		if(!empty($modules)) {
			$liveChatClass = GetClass('ISC_LIVECHAT');
			$GLOBALS['SideLiveChatCode'] = $liveChatClass->GetPageTrackingCode('panel');
			if(!$GLOBALS['SideLiveChatCode']) {
				$this->DontDisplay = true;
			}
		}
		else {
			$this->DontDisplay = true;
		}
	}
}