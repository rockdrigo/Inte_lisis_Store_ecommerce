<?php
class ISC_CARTSTATUSMESSAGE_PANEL extends PANEL
{
	public function setPanelSettings()
	{
		$discountRules = getCustomerQuote()->getAppliedDiscountRules();
		foreach($discountRules as $discountRule) {
			if(!empty($discountRule['banners'])) {
				foreach($discountRule['banners'] as $banner) {
					flashMessage(getLang('DiscountCongratulations').' '.$banner, MSG_INFO);
				}
			}
		}

		$messages = getFlashMessageBoxes();
		if(!$messages) {
			$this->DontDisplay = true;
			return;
		}

		$GLOBALS['CartStatusMessage'] = $messages;
	}
}
