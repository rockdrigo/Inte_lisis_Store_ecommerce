<?php

CLASS ISC_PAGECOMMENTS_PANEL extends PANEL
{
	public function SetPanelSettings()
	{
		// get our comments system
		if (!GetModuleById('comments', /** @var ISC_COMMENTS **/$commentsModule, GetConfig('CommentSystemModule'))) {
			$this->DontDisplay = true;
			return;
		}

		$pageId = $GLOBALS['ISC_CLASS_PAGE']->GetPageId();

		if (!$commentsModule->commentsEnabledForType(ISC_COMMENTS::PAGE_COMMENTS) || !$commentsModule->pageEnabled($pageId)) {
			$this->DontDisplay = true;
			return;
		}

		$GLOBALS['CommentsHTML'] = $commentsModule->getCommentsHTMLForType(ISC_COMMENTS::PAGE_COMMENTS, $pageId);
	}
}