<?php

	CLASS ISC_SIDEBRANDTAGCLOUD_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			$output = "";
			$brands = array();
			$min = 0;
			$max = 0;
			$diff = 0;
			$distribution = 0;

			// Get the number of brands
			$query = "select count(brandid) as num from [|PREFIX|]brands";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			$num_brands = $row['num'];

			if($num_brands > 0 && GetConfig('TagCloudsEnabled')) {
				// Hide the alternate side brand panel
				$GLOBALS['HideSideShopByBrandFullPanel'] = "none";

				// Get the 5 most popular brands
				$query = "select b.brandid, b.brandname, (
					select count(productid) from [|PREFIX|]products p where p.prodbrandid=b.brandid and p.prodvisible='1'
				) as num
				from [|PREFIX|]brands b
				order by b.brandname asc";

				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$brands[] = $row;
				}

				// Find the minimum and maximum products per brand

				foreach($brands as $k => $v) {
					// No product under this brand, skip
					if ($brands[$k]['num'] == 0) {
						continue;
					}

					// Is it the new minimum?
					if($min == 0) {
						$min = $brands[$k]['num'];
					}

					if($brands[$k]['num'] > 0 && $brands[$k]['num'] < $min) {
						$min = $brands[$k]['num'];
					}

					// Is it the new maximum?
					if($brands[$k]['num'] > $max) {
						$max = $brands[$k]['num'];
					}
				}

				// Is there only one brand?
				if($min == $max) {
					$min = 0;
				}

				// Workout the differences and distribution
				$diff = $max - $min;
				if ($diff == 0) {
					$diff = 1;
				}

				$tagCount = count($brands);

				$min_size = GetConfig('TagCloudMinSize');
				$max_size = GetConfig('TagCloudMaxSize');

				$step = ($max_size - $min_size) / $diff;

				foreach($brands as $k => $v) {
					// Workout the tag size and output the brandname
					$num = $brands[$k]['num'];
					if ($num == 0) {
						continue;
					}

					$size = $min_size + (($num - $min) * $step);

					$GLOBALS['TagSize'] = ceil($size);

					// Create a snippet for the template system
					$GLOBALS['BrandLink'] = BrandLink($brands[$k]['brandname']);
					$GLOBALS['BrandName'] = isc_html_escape($brands[$k]['brandname']);
					$GLOBALS['NumProducts'] = (int) $num;
					$output .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("BrandCloudItem");
				}

				$GLOBALS['SNIPPETS']['SideBrandTagCloud'] = $output;
			}
			else {
				// Hide the panel
				$this->DontDisplay = true;
				$GLOBALS['HideBrandTagCloudPanel'] = "none";
			}
		}
	}