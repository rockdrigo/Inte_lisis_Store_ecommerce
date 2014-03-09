<?php

	CLASS ISC_SIDESHOPBYBRANDFULL_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			$output = "";

			// Get the number of brands
			$query = "select count(brandid) as num from [|PREFIX|]brands";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			$num_brands = $row['num'];

			if($num_brands > 0) {
				// Get the 5 most popular brands
				$query = "select b.brandid, b.brandname, (select count(productid) from [|PREFIX|]products p where p.prodbrandid=b.brandid and p.prodvisible='1') as num from [|PREFIX|]brands b order by b.brandname asc";

				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$GLOBALS['BrandLink'] = BrandLink($row['brandname']);
					$GLOBALS['BrandName'] = isc_html_escape($row['brandname']);
					$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ShopByBrandItem");
				}

				if($num_brands > 5) {
					$GLOBALS['SNIPPETS']['ShopByBrandAllItem'] = $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ShopByBrandAllItem");
				}

				$output = $GLOBALS['ISC_CLASS_TEMPLATE']->ParseSnippets($output, $GLOBALS['SNIPPETS']);
				$GLOBALS['SNIPPETS']['SideShopByBrandFullList'] = $output;
			}
			else {
				// Hide the panel
				$this->DontDisplay = true;
				$GLOBALS['HideSideShopByBrandFullPanel'] = "none";
			}
		}
	}