<?php

	class ISC_PRODUCTIMAGE
	{
		private $_prodid = 0;
		private $_prodcurrentimage = 0;
		private $_prodnumimages = 0;
		private $_prodname = '';

		private $_variationid = 0;

		private $_prodimages = array();
		private $_prodImagesDescriptions = array();

		private $_variationImage = '';
		public function __construct()
		{
			if(isset($_GET['product_id'])) {
				$this->_SetImageData();
				return;
			}

			if(isset($_GET['video_id'])) {
				$videoId = $_GET['video_id'];
				if(preg_match('/^[a-zA-Z0-9_\-]*$/', $videoId)) {
					$GLOBALS['VideoId'] = $videoId;
					$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("product_video");
					$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
					die();
				}
			}

			$GLOBALS['ISC_CLASS_404'] = GetClass('ISC_404');
			$GLOBALS['ISC_CLASS_404']->HandlePage();
			exit;
		}

		private function _SetImageData()
		{
			$this->_prodid = (int)$_GET['product_id'];

			// Load the product name
			$query = sprintf("select prodname from [|PREFIX|]products where productid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($this->GetProductId()));
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$this->_prodname = $GLOBALS['ISC_CLASS_DB']->FetchOne($result, "prodname");

			// Are we showing the image for a particular variation?
			if(isset($_GET['variation_id'])) {
				$this->_variationid = (int)$_GET['variation_id'];
				$query = "SELECT vcimage, vcimagezoom FROM [|PREFIX|]product_variation_combinations WHERE vcproductid='".$this->_prodid."' AND combinationid='".$this->_variationid."'";
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				if (!$variation = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					// Invalid variation
					exit;
				}

				if ($variation['vcimage'] && $variation['vcimagezoom']) {
					// use the product image library to get the url which will trigger a resize if necessary
					try {
						$productImage = new ISC_PRODUCT_IMAGE;
						$productImage->setSourceFilePath($variation['vcimage']);
						$productImage->setResizedFilePath(ISC_PRODUCT_IMAGE_SIZE_ZOOM, $variation['vcimagezoom']);
						$this->_variationImage = $productImage->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_ZOOM, true, false);
					} catch (Exception $exception) {
						// nothing
					}
				}
			}
			// Otherwise, just load general images for a product
			//else {
				// Load the images into an array
				$productImages = ISC_PRODUCT_IMAGE::getProductImagesFromDatabase($this->GetProductId());
				if(!empty($productImages)) {
					$i = 0;
					foreach ($productImages as $productImage) {
						$imgDesc = '';
						$zoomImg = '';
						$tinyImg = '';
						$thumbImg = '';

						try{
							$zoomImg = $productImage->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_ZOOM, true);
						} catch (Exception $exception) {
							// do nothing, will result in returning blank string, which is fine
						}


						try{
							$thumbImg = $productImage->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_STANDARD, true);
							//$GLOBALS['ProductThumbURL'] = $thumbURL;
						} catch (Exception $exception) {
						}


						// if both standard image and zoom image not exist, go to the next image.
						if($thumbImg == '' && $zoomImg == '') {
							continue;
						}


						try{
							$tinyImg = $productImage->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_TINY, true);
						} catch (Exception $exception) {
							// do nothing, will result in returning blank string, which is fine
						}

						$imgDesc = $productImage->getDescription();
						if ($imgDesc == '') {
							$imgIndex= $i+1;
							$imgDesc = GetLang("Image")." ".$imgIndex;
						}

						if($zoomImg!= '' ) {
							$this->_prodimages['zoom'][] = $zoomImg;
						//use standard image if zoom image not exist.
						} else {
							$this->_prodimages['zoom'][] = $thumbImg;
						}
						$this->_prodimages['tiny'][] = $tinyImg;

						$this->_prodImagesDescriptions[] = $imgDesc;
						$i++;
					}
				//}
				// How many images are there?
				$this->_prodnumimages = count($this->_prodimages['zoom']);
			}
			// Which image should we show?
			if(isset($_GET['current_image'])) {
				$this->_prodcurrentimage = (int)$_GET['current_image'];
			} elseif(isset($_GET['variation_id'])) {
				$this->_prodcurrentimage = 'variation';
			}
		}

		public function GetProductId()
		{
			return $this->_prodid;
		}

		public function GetCurrentImage()
		{
			return $this->_prodcurrentimage;
		}

		public function getCurrentImageDescription()
		{
			return $this->_prodImagesDescriptions[$this->GetCurrentImage()];
		}

		public function GetImage($type = 'zoom')
		{
			if($this->GetCurrentImage() === 'variation') {
				return $this->_variationImage;

			// Return the image to be displayed. Returns an array on success, false on failure.
			} elseif(isset($this->_prodimages[$type][$this->GetCurrentImage()])) {
				return $this->_prodimages[$type][$this->GetCurrentImage()];
			}
			else {
				return false;
			}
		}

		public function GetNumImages()
		{
			return $this->_prodnumimages;
		}

		public function HandlePage()
		{
			$this->ShowImage();
		}

		public function ShowImage()
		{
			if ($this->GetNumImages() == 1) {
				// do no show nav link if there is only 1 image
				$GLOBALS['NavLinkDisplay'] = 'display:none;';
			}

			if($image = $this->GetImage()) {
				// Set product name
				$GLOBALS['ProductName'] = isc_html_escape($this->_prodname);

				// Show we show the "Previous Image" link?
				if($this->GetCurrentImage() == 0 || $this->GetCurrentImage() == 'variation') {
					$GLOBALS['DisablePrevLink'] = "disabled";
				} else {
					$GLOBALS['PrevLink'] = sprintf("%s/productimage.php?product_id=%d&current_image=%d", $GLOBALS['ShopPath'], $this->GetProductId(), $this->GetCurrentImage()-1);
				}

				// Should we show the "Next Image" link?
				if($this->GetNumImages()-1 == $this->GetCurrentImage() || $this->GetCurrentImage() == 'variation') {
					$GLOBALS['DisableNextLink'] = "disabled";
				} else {
					$GLOBALS['NextLink'] = sprintf("%s/productimage.php?product_id=%d&current_image=%d", $GLOBALS['ShopPath'], $this->GetProductId(), $this->GetCurrentImage()+1);
				}

				if($this->GetCurrentImage() == 'variation') {
					$GLOBALS['VariationImage'] = $image;
				}

				$GLOBALS['ProductMaxImageWidth'] = ISC_PRODUCT_IMAGE::getSizeWidth(ISC_PRODUCT_IMAGE_SIZE_ZOOM);
				$GLOBALS['ProductMaxImageHeight'] = ISC_PRODUCT_IMAGE::getSizeHeight(ISC_PRODUCT_IMAGE_SIZE_ZOOM);

				$GLOBALS['ProductMaxTinyWidth'] = ISC_PRODUCT_IMAGE::getSizeWidth(ISC_PRODUCT_IMAGE_SIZE_TINY);
				$GLOBALS['ProductMaxTinyHeight'] = ISC_PRODUCT_IMAGE::getSizeHeight(ISC_PRODUCT_IMAGE_SIZE_TINY);

				$GLOBALS['ProductTinyBoxWidth'] = $GLOBALS['ProductMaxTinyWidth']+4;
				$GLOBALS['ProductTinyBoxHeight'] = $GLOBALS['ProductMaxTinyHeight']+4;

				// a list of images does exist in _prodimages but it's just a list of urls with no sizing information, with the given time frame I have no choice but to re-query the db -ge
				$productImages = ISC_PRODUCT_IMAGE::getProductImagesFromDatabase($this->GetProductId());

				$GLOBALS['TotalImages'] = count($productImages);
				$GLOBALS['ProdImageJavascript'] = '';

				if ($GLOBALS['TotalImages']) {
					$GLOBALS['SNIPPETS']['ProductTinyImages'] = '';
					$GLOBALS['ProductZoomImageURLs'] = array();

					foreach ($productImages as $index => /** @var ISC_PRODUCT_IMAGE */$productImage) {
						$thumbURL = $productImage->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_ZOOM, true);

						$GLOBALS['ProductThumbURL'] = $thumbURL;
						$GLOBALS['ProductThumbIndex'] = $index;
						$GLOBALS['ImageDescription'] = isc_html_escape($productImage->getDescription());

						$GLOBALS['ProdImageJavascript'] .= "ThumbURLs[" . $index . "] = " . isc_json_encode($thumbURL) . ";";
						$GLOBALS['ProdImageJavascript'] .= "ImageDescriptions[" . $index . "]=" . isc_json_encode($GLOBALS['ImageDescription']) . ";";

						$GLOBALS['ProductTinyImageURL'] = $productImage->getResizedUrl(ISC_PRODUCT_IMAGE_SIZE_TINY, true);

						$resizedTinyDimension = $productImage->getResizedFileDimensions(ISC_PRODUCT_IMAGE_SIZE_TINY, true);
						$GLOBALS['TinyImageWidth'] = $resizedTinyDimension[ISC_PRODUCT_IMAGE_DIMENSIONS_WIDTH];
						$GLOBALS['TinyImageHeight'] = $resizedTinyDimension[ISC_PRODUCT_IMAGE_DIMENSIONS_HEIGHT];

						$GLOBALS['TinyImageTopPadding'] = floor(($GLOBALS['ProductMaxTinyHeight'] - $GLOBALS['TinyImageHeight']) / 2);

						$GLOBALS['TinyImageClickJavascript'] = "showProductZoomImage(" . $index . ");";
						$GLOBALS['SNIPPETS']['ProductTinyImages'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("ProductTinyImage");
					}
				}

				$GLOBALS['CurrentImageIndex'] = $this->GetCurrentImage();
				$GLOBALS['ImageFile'] = $image;
				$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("productimage");
				$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
			}
		}
	}
