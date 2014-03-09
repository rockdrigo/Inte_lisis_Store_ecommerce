<?php
/**
 * Product Tag Cloud Panel.
 */
class ISC_PRODUCTTAGCLOUD_PANEL extends PANEL
{
	/**
	 * Set the settings for this panel.
	 */
	public function SetPanelSettings()
	{
		// How many tags do we have?
		$query = "
			SELECT COUNT(tagid) AS tagcount
			FROM [|PREFIX|]product_tags
		";
		$tagCount = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);

		// How many products does the most popular tag contain?
		$query = "
			SELECT MAX(tagcount) AS popularcount, MIN(tagcount) AS leastcount
			FROM [|PREFIX|]product_tags
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$tagCounts = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		// Get a list of all of the tags
		$query = "
			SELECT *
			FROM [|PREFIX|]product_tags
			ORDER BY tagname ASC
		";
		$min = GetConfig('TagCloudMinSize');
		$max = GetConfig('TagCloudMaxSize');
		$GLOBALS['SNIPPETS']['TagList'] = '';
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($tag = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$weight = ceil(($tag['tagcount']/$tagCount)*100);
			if($max > $min) {
				$fontSize = (($weight/100) * ($max - $min)) + $min;
			}
			else {
				$fontSize = (((100-$weight)/100) * ($max - $min)) + $max;
			}
			$fontSize = (int)$fontSize;
			$GLOBALS['FontSize'] = $fontSize.'%';
			$GLOBALS['TagName'] = isc_html_escape($tag['tagname']);
			$GLOBALS['TagLink'] = TagLink($tag['tagfriendlyname'], $tag['tagid']);
			$GLOBALS['TagProductCount'] = sprintf(GetLang('XProductsTaggedWith'), $tag['tagcount'], isc_html_escape($tag['tagname']));
			$GLOBALS['SNIPPETS']['TagList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('ProductTagCloudItem');
		}
	}
}