<?php
class ISC_FACEBOOKLIKEBUTTON {
	public static function getButtonHTML()
	{
		$href = urlencode(GetCurrentURL());

		if (GetConfig('FacebookLikeButtonStyle') == 'countonly') {
			$href .= '&layout=button_count';
		}

		if (GetConfig('FacebookLikeButtonVerb') == 'recommend') {
			$href .= '&action=recommend';
		}

		if (!GetConfig('FacebookLikeButtonShowFaces')) {
			$href .= '&show_faces=false';
		}

		$GLOBALS['FacebookButtonHref'] = $href;

		return $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('FacebookLikeButton');
	}
}