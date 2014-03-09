<?php
class ISC_SIMILARPRODUCTSBYTAG_PANEL extends PANEL
{
	public function SetPanelSettings()
	{
		if($GLOBALS['ISC_CLASS_PRODUCT']->ProductHasTags() == false) {
			$this->DontDisplay = true;
			return false;
		}

		// Get the correct tag count associated with this product
		$tags = array();
		$query = "
			SELECT
				tt.tagid, tt.tagname, tt.tagfriendlyname, validtag.tagcount
			FROM (
				SELECT
					t.tagid, COUNT(*) AS tagcount
				FROM
					[|PREFIX|]product_tags t
				INNER JOIN
					[|PREFIX|]product_tagassociations ta
					ON
					ta.tagid = t.tagid
				GROUP BY
					t.tagid
				HAVING COUNT(*) > 1
			) validtag
			INNER JOIN
				[|PREFIX|]product_tagassociations tta
				ON
				tta.tagid = validtag.tagid
			INNER JOIN
				[|PREFIX|]product_tags tt ON
				tt.tagid = tta.tagid
			WHERE
				tta.productid = '".(int)$GLOBALS['ISC_CLASS_PRODUCT']->GetProductId()."'";

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($tag = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$tags[] = $tag;
		}

		if(empty($tags)) {
			$this->DontDisplay = true;
			return false;
		}
		$tagCount = count($tags);

		$min = GetConfig('TagCloudMinSize');
		$max = GetConfig('TagCloudMaxSize');
		$GLOBALS['SNIPPETS']['TagList'] = '';
		foreach($tags as $tag) {
			$weight = ceil(($tag['tagcount']/$tagCount)*100);
			if ($weight > 100) {
				$weight = 100;
			}

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
			$GLOBALS['SNIPPETS']['TagList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet('SimilarProductsByTagTag');
		}
	}
}