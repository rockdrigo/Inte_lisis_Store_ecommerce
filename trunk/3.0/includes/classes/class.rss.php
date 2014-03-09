<?php

	class ISC_RSS
	{

		private $_feed = '';
		private $_type = '';
		private $_categoryid = 0;

		public function __construct()
		{
			require_once dirname(__FILE__)."/class.feedgenerator.php";
		}

		public function HandlePage()
		{
			$action = "";
			if(isset($_REQUEST['action'])) {
				$action = isc_strtolower($_REQUEST['action']);
			}

			switch($action) {
				case "popularproducts": {
					$this->PopularProducts();
					break;
				}
				case "searchproducts": {
					$this->SearchProducts();
					break;
				}
				case "newblogs": {
					$this->NewBlogs();
					break;
				}
				case 'featuredproducts':
					$this->FeaturedProducts();
					break;
				default: {
					$this->NewProducts();
				}
			}
		}

		private function _SetFeedDetails()
		{
			if(isset($_REQUEST['type']) && $_REQUEST['type'] == "atom") {
				$this->_type = "atom";
			} else {
				$this->_type = "rss";
			}
		}

		public function NewProducts()
		{
			// Feed enabled?
			if(!GetConfig('RSSNewProducts')) {
				exit;
			}

			$feedTitle = sprintf(GetLang('RSSNewProducts'), GetConfig('StoreName'));
			$feedDescription = sprintf(GetLang('RSSNewProductsDesc'), GetConfig('StoreName'));

			$searchTerms = array();
			$cacheId = 'newproducts';
			if(isset($_REQUEST['categoryid']) && is_numeric($_REQUEST['categoryid'])) {
				$searchTerms['categoryid'] = (int)$_REQUEST['categoryid'];
				$cacheId .= $searchTerms['categoryid'];
				$query = "SELECT catname FROM [|PREFIX|]categories WHERE categoryid='".$GLOBALS['ISC_CLASS_DB']->Quote($_REQUEST['categoryid'])."'";
				$catName = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);
				$feedTitle .= ' - '.$catName;

				// determine whether or not to show products from sub-categories
				if (GetConfig('CategoryListingMode') == 'children') {
					$searchTerms['searchsubs'] = 'ON';
				} else if (GetConfig('CategoryListingMode') == 'emptychildren') {
					// determine count of products in current category
					/** @var ISC_CATEGORY */
					$category = GetClass('ISC_CATEGORY');
					$category->loadCats = (int)$_REQUEST['categoryid'];
					$category->SetNumProducts();
					if (!$category->GetNumProducts()) {
						// category is empty, show child category products
						$searchTerms['searchsubs'] = 'ON';
					}
				}
			}
			$this->_BuildProductFeed($feedTitle, $feedDescription, $cacheId, 'p.productid', 'desc', $searchTerms);
		}

		public function PopularProducts()
		{
			// Feed enabled?
			if(!GetConfig('RSSPopularProducts')) {
				exit;
			}

			$feedTitle = sprintf(GetLang('RSSPopularProducts'), GetConfig('StoreName'));
			$feedDescription = sprintf(GetLang('RSSPopularProductsDesc'), GetConfig('StoreName'));

			$searchTerms = array();
			$cacheId = 'popularproducts';
			if(isset($_REQUEST['categoryid']) && is_numeric($_REQUEST['categoryid'])) {
				$searchTerms['categoryid'] = (int)$_REQUEST['categoryid'];
				$cacheId .= $searchTerms['categoryid'];

				// determine whether or not to show products from sub-categories
				if (GetConfig('CategoryListingMode') == 'children') {
					$searchTerms['searchsubs'] = 'ON';
				} else if (GetConfig('CategoryListingMode') == 'emptychildren') {
					// determine count of products in current category
					/** @var ISC_CATEGORY */
					$category = GetClass('ISC_CATEGORY');
					$category->loadCats = (int)$_REQUEST['categoryid'];
					$category->SetNumProducts();
					if (!$category->GetNumProducts()) {
						// category is empty, show child category products
						$searchTerms['searchsubs'] = 'ON';
					}
				}
			}
			$this->_BuildProductFeed($feedTitle, $feedDescription, $cacheId, 'prodavgrating', 'desc', $searchTerms);
		}

		public function SearchProducts()
		{
			// Feed enabled?
			if(!GetConfig('RSSProductSearches')) {
				exit;
			}

			$feedTitle = sprintf(GetLang('RSSSearchProducts'), GetConfig('StoreName'));
			$feedDescription = sprintf(GetLang('RSSSearchProductsDesc'), GetConfig('StoreName'));

			$searchId = md5(isc_strtolower(serialize($_REQUEST)));
			$feedId = sprintf("search_%s", $searchId);

			$this->_BuildProductFeed($feedTitle, $feedDescription, $feedId, 'p.productid', 'desc', $_REQUEST);
		}

		private function _BuildProductFeed($feedTitle, $feedDescription, $feedId, $sortField, $sortOrder, $searchTerms=array())
		{
			$this->_SetFeedDetails();

			$feed = new ISC_FEED_GENERATOR($feedId, $this->_type, (int)GetConfig('RSSCacheTime')*60);

			$channel = array(
				"title" => $feedTitle,
				"description" => $feedDescription,
				"link" => $GLOBALS['ShopPath'],
				'namespaces' => array(
					'isc' => array(
						'http://dtd.interspire.com/rss/isc-1.0.dtd',
						array(
							'store_title' => getConfig('StoreName')
						)
					)
				)
			);
			$feed->SetChannel($channel);

			// The magical Interspire Shopping Cart RSS feeds are actually just custom searches so pipe it off to our search function
			$searchterms = BuildProductSearchTerms($searchTerms);

			$searchQueries = BuildProductSearchQuery($searchterms, '', $sortField, $sortOrder);

			// Run the query
			$searchQueries['query'] .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, (int)GetConfig('RSSItemsLimit'));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($searchQueries['query']);

			while($product = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				if(isc_strlen($product['proddesc']) > 300) {
					$product['proddesc'] = isc_substr($product['proddesc'], 0, 298)."..";
				}

				$item = array(
					'title' => $product['prodname'],
					'date' => $product['proddateadded'],
					'link' => prodLink($product['prodname']),
					'namespaces' => array(
						'isc' => array(
							'description' => $product['proddesc'],
							'productid' => $product['productid'],
						)
					)
				);

				if($product['imagefile']) {
					$thumb = ImageThumb($product, ProdLink($product['prodname']));
					$product['proddesc'] = sprintf("<div style='float: right; padding: 10px;'>%s</div>%s", $thumb, $product['proddesc']);

					$image = new ISC_PRODUCT_IMAGE;
					$image->populateFromDatabaseRow($product);

					try {
						$item['namespaces']['isc']['thumb'] = $image->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_THUMBNAIL, true);
					} catch (Exception $exception) { }

					try {
						$item['namespaces']['isc']['image'] = $image->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_ZOOM, true);
					} catch (Exception $exception) { }

					unset ($image);
				}

				// Determine the price of this product
				$price = '';
				if (GetConfig('ShowProductPrice') && !$product['prodhideprice']) {
					$calcPrice = $product['prodcalculatedprice'];
					$plainPrice = formatProductPrice($product, $calcPrice, array(
						'strikeRetail' => false,
						'displayInclusive' => getConfig('taxDefaultTaxDisplayCatalog')
					));

					if($plainPrice) {
						$item['namespaces']['isc']['price'] = $plainPrice;
						$price = '<strong>'.getLang('Price').': '.$plainPrice.'</strong>';
					}
				}

				if(GetConfig('ShowProductRating')) {
					$ratingImage = $GLOBALS['IMG_PATH'].'/IcoRating'.(int)$product['prodavgrating'].'.gif';
					$item['namespaces']['isc']['rating'] = (int)$product['prodavgrating'];
					$item['namespaces']['isc']['rating_image'] = $ratingImage;

					$ratingImage = '<img src="'.$ratingImage.'" alt="" />';
				}
				else {
					$ratingImage = '';
				}

				$product['proddesc'] .= '<p>'.$price.' '.$ratingImage.'</p>';

				$item['description'] = $product['proddesc'];
				$feed->AddItem($item);
			}

			// Send the feed to the browser
			$feed->OutputFeed();

		}

		public function NewBlogs()
		{
			// Feed enabled?
			if(!GetConfig('RSSLatestBlogEntries')) {
				exit;
			}

			$this->_SetFeedDetails();

			$feed = new ISC_FEED_GENERATOR('blogs', $this->_type, (int)GetConfig('RSSCacheTime')*60);

			$channel = array(
				"title" => sprintf(GetLang('RSSLatestNews'), GetConfig('StoreName')),
				"description" => sprintf(GetLang('RSSLatestNewsDesc'), GetConfig('StoreName')),
				"link" => $GLOBALS['ShopPath'],
				'namespaces' => array(
					'isc' => array(
						'http://dtd.interspire.com/rss/isc-1.0.dtd',
						array(
							'store_title' => getConfig('StoreName')
						)
					)
				)
			);
			$feed->SetChannel($channel);

			$query = "select newsid, newstitle, newscontent, newsdate from [|PREFIX|]news where newsvisible='1' order by newsid desc";

			// Run the query
			$query .= $GLOBALS['ISC_CLASS_DB']->AddLimit(0, (int)GetConfig('RSSItemsLimit'));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			while($item = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				// Add the item to the feed
				$item = array(
					"title" => $item['newstitle'],
					"description" => $item['newscontent'],
					"link" => BlogLink($item['newsid'], $item['newstitle']),
					"date" => $item['newsdate']
				);
				$feed->AddItem($item);
			}

			// Send the feed to the browser
			$feed->OutputFeed();
		}

		public function FeaturedProducts()
		{
			// Feed enabled?
			if(!GetConfig('RSSFeaturedProducts')) {
				exit;
			}

			$feedTitle = sprintf(GetLang('RSSFeaturedProducts'), GetConfig('StoreName'));
			$feedDescription = sprintf(GetLang('RSSFeaturedProductsDesc'), GetConfig('StoreName'));

			$searchTerms = array(
				'featured' => 1,
			);
			$cacheId = 'featuredproducts';
			if(isset($_REQUEST['categoryid']) && is_numeric($_REQUEST['categoryid'])) {
				$searchTerms['categoryid'] = (int)$_REQUEST['categoryid'];
				$cacheId .= $searchTerms['categoryid'];
				$query = "SELECT catname FROM [|PREFIX|]categories WHERE categoryid='".$GLOBALS['ISC_CLASS_DB']->Quote($_REQUEST['categoryid'])."'";
				$catName = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);
				$feedTitle .= ' - '.$catName;

				// determine whether or not to show products from sub-categories
				if (GetConfig('CategoryListingMode') == 'children') {
					$searchTerms['searchsubs'] = 'ON';
				} else if (GetConfig('CategoryListingMode') == 'emptychildren') {
					// determine count of products in current category
					/** @var ISC_CATEGORY */
					$category = GetClass('ISC_CATEGORY');
					$category->loadCats = (int)$_REQUEST['categoryid'];
					$category->SetNumProducts();
					if (!$category->GetNumProducts()) {
						// category is empty, show child category products
						$searchTerms['searchsubs'] = 'ON';
					}
				}
			}
			$this->_BuildProductFeed($feedTitle, $feedDescription, $cacheId, 'p.productid', 'desc', $searchTerms);
		}

	}