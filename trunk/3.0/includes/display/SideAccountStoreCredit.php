<?php

CLASS ISC_SIDEACCOUNTSTORECREDIT_PANEL extends PANEL
{
	public function SetPanelSettings()
	{
		$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
		$customer = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerDataByToken();
		if ($customer['custstorecredit'] > 0) {
			$GLOBALS['StoreCreditAmount'] = CurrencyConvertFormatPrice($customer['custstorecredit']);
		} else {
			$GLOBALS['HideStoreCredit'] = "none";
		}
	}
}