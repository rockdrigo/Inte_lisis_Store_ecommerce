<?php

	CLASS ISC_PRODUCTWARRANTY_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			if(!isset($GLOBALS['ISC_CLASS_PRODUCT']) || !$GLOBALS['ISC_CLASS_PRODUCT']->GetProductWarranty()) {
				$this->DontDisplay = true;
				return;
			}

			$warranty = $GLOBALS['ISC_CLASS_PRODUCT']->GetProductWarranty();
			if(strpos($warranty, "<") === false) {
				$warranty = nl2br($warranty);
			}

			$GLOBALS['ProductWarranty'] = $warranty;
		}
	}