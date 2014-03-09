<?php

	CLASS ISC_SIDENEWSLETTERBOX_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			if (!GetConfig('ShowMailingListInvite')) {
				$this->DontDisplay = true;
				return;
			}

			$output = "";
			$GLOBALS['SNIPPETS']['SideNewsletterBox'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("DefaultNewsletterSubscriptionForm");
		}
	}