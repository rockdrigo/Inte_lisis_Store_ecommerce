<?php

	CLASS ISC_SIDEPRODUCTADDTOWISHLIST_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			$GLOBALS['ProductId'] = $GLOBALS['ISC_CLASS_PRODUCT']->GetProductId();
			$GLOBALS['ProductName'] = isc_html_escape($GLOBALS['ISC_CLASS_PRODUCT']->GetProductName());
			$wishLists = $this->LoadCustomerWishLists();
			$GLOBALS['WishLists'] = '';

			$i=0;
			foreach ($wishLists as $wishlist) {
				if ($i == 0) {
					$checked = 'checked';
				} else {
					$checked = '';
				}
				$GLOBALS['WishLists'] .= '<input type="radio" name="wishlistid" id="wishlistid'.(int)$wishlist['wishlistid'].'" value="'.(int)$wishlist['wishlistid'].'" '.$checked.' /> <label for="wishlistid'.(int)$wishlist['wishlistid'].'">'. isc_html_escape($wishlist['wishlistname']).'</label><br />';
				++$i;
			}
		}

		private function LoadCustomerWishLists()
		{
			$wishLists = array();
			if(CustomerIsSignedIn()) {
				$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
				$customer_id = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();

				// get customer's wish list from database
				$query = "SELECT * FROM [|PREFIX|]wishlists WHERE customerid = ".$customer_id;
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$wishLists[] = $row;
				}
			}
			return $wishLists;
		}
	}