<?php

	CLASS ISC_HOMERECENTBLOGS_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			$output = "";

			if(GetConfig('HomeBlogPosts') > 0) {
				$query = "select newsid, newstitle from [|PREFIX|]news where newsvisible='1' order by newsid desc";
				$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, GetConfig('HomeBlogPosts'));
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				if($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
					while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
						$GLOBALS['BlogText'] = isc_html_escape($row['newstitle']);
						$GLOBALS['BlogLink'] = BlogLink($row['newsid'], $row['newstitle']);
						$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("RecentBlog");
					}

					$GLOBALS['SNIPPETS']['RecentBlogs'] = $output;

					// Showing the syndication option?
					if(GetConfig('RSSLatestBlogEntries') != 0 && GetConfig('RSSSyndicationIcons') != 0) {
						$GLOBALS['SNIPPETS']['HomeRecentBlogsFeed'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("HomeRecentBlogsFeed");
					}
				}
				else {
					$this->DontDisplay = true;
					$GLOBALS['HideHomeRecentBlogsPanel'] = "none";
				}
			}
			else {
				$this->DontDisplay = true;
				$GLOBALS['HideHomeRecentBlogsPanel'] = "none";
			}
		}
	}