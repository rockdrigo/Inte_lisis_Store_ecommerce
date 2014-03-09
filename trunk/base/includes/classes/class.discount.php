<?php

	class ISC_DISCOUNT
	{
		public function  __construct()
		{
			$eligibleFreeShippingInfo = getCustomerQuote()
				->getEligibleFreeShippingInfo();
			if (!empty ($eligibleFreeShippingInfo)) {

				$pageType = '';
				$message = '';
				if(isset($GLOBALS['ISC_CLASS_INDEX']) && !empty ($eligibleFreeShippingInfo['homepage'])) {
					$pageType = 'homepage';
				}
				else if(isset($GLOBALS['ISC_CLASS_CHECKOUT']) && !empty ($eligibleFreeShippingInfo['checkoutpage'])) {
					$pageType = 'checkoutpage';
				}
				else if(isset($GLOBALS['ISC_CLASS_PRODUCT']) && !empty ($eligibleFreeShippingInfo['productpage'])) {
					$pageType = 'productpage';
				}
				else if(isset($GLOBALS['ISC_CLASS_CART']) && !empty ($eligibleFreeShippingInfo['cartpage'])) {
					$pageType = 'cartpage';
				}
				if (!empty ($pageType)) {
					$maxRandNum = count($eligibleFreeShippingInfo[$pageType]) - 1;
					$randNum = rand(0, $maxRandNum);
					$message = $eligibleFreeShippingInfo[$pageType][$randNum]['message'];

					// we will show the message of the product, if the user can get get
					// get free shipping by buying 1 or more of current viewed product.
					if ($pageType == 'productpage') {
						$currProductId = $GLOBALS['ISC_CLASS_PRODUCT']->GetProductId();
						foreach ($eligibleFreeShippingInfo[$pageType] as $freeShippingInfo) {
							if (!empty($freeShippingInfo['productId']) && $freeShippingInfo['productId'] == $currProductId)  {
								$message = $freeShippingInfo['message'];
							}
						}
					}
					// Save the page type globally so we can access it from the template engine
					$GLOBALS['DiscountPageType'] = $pageType;
					$GLOBALS['DiscountMessage'] = sprintf("<div class='SpecificInfoMessage FreeShippingMessage_%s'>%s</div>", $pageType, $message);
				}
			}
		}
	}
