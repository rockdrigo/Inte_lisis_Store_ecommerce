<?php
class ISC_SIDECATEGORYTOPSELLERS_PANEL extends PRODUCTS_PANEL
{
	public function SetPanelSettings()
	{
		$count = 1;
		$output = "";

		if(!GetConfig('ShowProductRating')) {
			$GLOBALS['HideProductRating'] = "display: none";
		}

		$categorySql = $GLOBALS['ISC_CLASS_CATEGORY']->GetCategoryAssociationSQL(false);
		$query = $this->getProductQuery('p.prodnumsold > 0 AND '.$categorySql, 'p.prodnumsold DESC', 5);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		if($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
			$GLOBALS['AlternateClass'] = '';
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				if($count == 1) {
					$snippet = "SideTopSellersFirst";
				}
				else {
					$snippet = "SideTopSellers";
				}

				$GLOBALS['ProductNumber'] = $count++;

				$this->setProductGlobals($row);

				$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet($snippet);
			}

			// if only one product then we need to clear the list by adding an empty list item otherwise the layout can be broken
			if ($count == 2) {
				$output .= "<li></li>";
			}
		}
		else {
			$GLOBALS['HideSideCategoryTopSellersPanel'] = "none";
			$this->DontDisplay = true;
		}

		$GLOBALS['SNIPPETS']['SideTopSellers'] = $output;
	}
}