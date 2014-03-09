<?php

	/**
	*	Before the template parsing engine runs we will create a global list of banners
	*	for the selected page which will be "hooked" into the template system so they
	*	can be displayed on the site.
	**/
	class ISC_BANNER
	{

		public function __construct()
		{

			// First up, which page are we on?
			$GLOBALS['Banners'] = array();
			$banners = array();
			$page = "";
			$page_type = "";

			if(isset($GLOBALS['ISC_CLASS_SEARCH'])) {
				$page_type = 'search_page';
			}
			else if(isset($GLOBALS['ISC_CLASS_BRANDS'])) {
				$page_type = 'brand_page';
			}
			else if(isset($GLOBALS['ISC_CLASS_CATEGORY'])) {
				$page_type = 'category_page';
			}
			else if(isset($GLOBALS['ISC_CLASS_INDEX'])) {
				$page_type = 'home_page';
			}

			// Save the page type globally so we can access it from the template engine
			$GLOBALS['PageType'] = $page_type;

			if($page_type != "") {
				$from_stamp = isc_mktime();
				$to_stamp = $from_stamp - 83699;
				$query = "
					SELECT
						*
					FROM
						[|PREFIX|]banners
					WHERE
						page = '" . $GLOBALS['ISC_CLASS_DB']->Quote($page_type) . "' AND
						status = 1 AND
						(
							(datefrom = 0 AND dateto = 0) OR
							(datefrom <= " . $from_stamp . " AND dateto >= " . $to_stamp . ")
						)
					ORDER BY
						RAND()";

				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					array_push($banners, $row);
				}

				if($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
					foreach($banners as $banner) {
						if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
							$banner['content'] = str_replace($GLOBALS['ShopPathNormal'], $GLOBALS['ShopPathSSL'], $banner['content']);
						}

						$bannerContent = $banner['content'];
						// Wrap the banner in a div which can be styled
						$banner['content'] = sprintf("<div class='Block BlockContent banner_%s_%s'>%s</div>", $banner['page'], $banner['location'], $bannerContent);

						switch($page_type) {
							case "home_page":
								if(isset($GLOBALS['HomePromoControlScript']) && isset($GLOBALS['HomePromoOptimizerScriptTag']) && isset($GLOBALS['HomePromoOptimizerNoScriptTag'])) {
									$banner['content'] = sprintf($GLOBALS['HomePromoControlScript']
									."<div class='Block BlockContent banner_%s_%s'>".
										$GLOBALS['HomePromoOptimizerScriptTag']."
											%s
									".$GLOBALS['HomePromoOptimizerNoScriptTag']."
									</div>", $banner['page'], $banner['location'], $bannerContent);
								}
							case "search_page": {
								if($banner['location'] == "top" && !isset($GLOBALS['Banners']['top'])) {
									$GLOBALS['Banners']['top'] = $banner;
								}
								else if($banner['location'] == "bottom" && !isset($GLOBALS['Banners']['bottom'])) {
									$GLOBALS['Banners']['bottom'] = $banner;
								}
								break;
							}
							case "brand_page":
							case "category_page": {
								if($banner['location'] == "top" && !isset($GLOBALS['Banners'][$banner['catorbrandid']]['top'])) {
									$GLOBALS['Banners'][$banner['catorbrandid']]['top'] = $banner;
								}
								else if($banner['location'] == "bottom" && !isset($GLOBALS['Banners'][$banner['catorbrandid']]['bottom'])) {
									$GLOBALS['Banners'][$banner['catorbrandid']]['bottom'] = $banner;
								}
								break;
							}
						}
					}
				}
			}
		}
	}