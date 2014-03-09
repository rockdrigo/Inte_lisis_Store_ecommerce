<?php

CLASS ISC_PRODUCTREVIEWS_PANEL extends PANEL
{
	public function SetPanelSettings()
	{
		// get our comments system
		if (!GetModuleById('comments', /** @var ISC_COMMENTS **/$commentsModule, GetConfig('CommentSystemModule'))) {
			$this->DontDisplay = true;
			return;
		}

		if (!$commentsModule->commentsEnabledForType(ISC_COMMENTS::PRODUCT_COMMENTS)) {
			$this->DontDisplay = true;
			return;
		}

		$GLOBALS['CommentsHTML'] = $commentsModule->getCommentsHTMLForType(ISC_COMMENTS::PRODUCT_COMMENTS, $GLOBALS['ISC_CLASS_PRODUCT']->GetProductId());
	}
}