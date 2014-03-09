<?php

	CLASS ISC_PRODUCTVIDEOS_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			// don't display the panel if they don't have any products
			if(!isset($GLOBALS['ISC_CLASS_PRODUCT']) || !$GLOBALS['ISC_CLASS_PRODUCT']->hasVideos()) {
				$this->DontDisplay = true;
				return;
			}

			$setFeatured = false;

			$GLOBALS['SNIPPETS']['VideoList'] = '';
			$GLOBALS['SNIPPETS']['VideoSideList'] = '';

			$GLOBALS['ProductImageMode'] = GetConfig('ProductImageMode');

			$videos = $GLOBALS['ISC_CLASS_PRODUCT']->getVideos();

			if(empty($videos)) {
				$this->DontDisplay = true;
				return;
			}

			$GLOBALS['AdditionalStylesheets'][] = GetConfig('AppPath').'/javascript/jquery/plugins/imodal/imodal.css';

			if(count($videos) == 1) {
				$GLOBALS['HideSingleVideo'] = '';
				$GLOBALS['HideVideoList'] = 'HideElement';

			} else {
				$GLOBALS['HideSingleVideo'] = 'HideElement';
				$GLOBALS['HideVideoList'] = '';
			}

			foreach($videos as $thisVideo) {
				if (!$setFeatured) {
					$GLOBALS['FeaturedVideo'] = $thisVideo['video_id'];
					$setFeatured = true;
				}

				if (strlen($thisVideo['video_description']) > 65) {
					$descShort = substr($thisVideo['video_description'], 0,62) . "...";
				} else {
					$descShort = $thisVideo['video_description'];
				}

				if (strlen($thisVideo['video_title']) > 17) {
					$titleShort = substr($thisVideo['video_title'], 0, 14) . "...";
				} else {
					$titleShort = $thisVideo['video_title'];
				}

				$GLOBALS['VideoId'] = $thisVideo['video_id'];
				$GLOBALS['VideoTitleShort'] = isc_html_escape($titleShort);
				$GLOBALS['VideoTitleLong'] = isc_html_escape($thisVideo['video_title']);
				$GLOBALS['VideoDescriptionLong'] = isc_html_escape($thisVideo['video_description']);
				$GLOBALS['VideoDescriptionShort'] = isc_html_escape($descShort);
				$GLOBALS['VideoLength'] = $thisVideo['video_length'];
				$GLOBALS['SNIPPETS']['VideoList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("VideoList");
				$GLOBALS['SNIPPETS']['VideoSideList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("VideoListSide");
			}
		}
	}