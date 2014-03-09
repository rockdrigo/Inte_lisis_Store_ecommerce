<?php

	CLASS ISC_SIDEACCOUNTMENU_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
			$customerid = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();

			if(gzte11(ISC_LARGEPRINT)) {
				// Get the number of new messages for this customer
				$query = "
					SELECT
					COUNT(*)
					FROM [|PREFIX|]orders o, [|PREFIX|]order_messages om
					WHERE o.ordcustid = " . (int)$customerid . " AND o.deleted = 0 AND om.messageorderid = o.orderid AND om.messagefrom = 'admin' AND messagestatus = 'unread'
				";
				$GLOBALS['NumNewMessages'] = (int)$GLOBALS['ISC_CLASS_DB']->FetchOne($query);
			}
			else {
				$GLOBALS['HideMessagesMenu'] = "none";
			}

			// Do we want to show or hide the return requests menu item?
			if(gzte11(ISC_LARGEPRINT) && GetConfig('EnableReturns') == 1) {
				$query = sprintf("SELECT returnid FROM [|PREFIX|]returns WHERE retcustomerid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($customerid));
				if(!$GLOBALS['ISC_CLASS_DB']->FetchOne($query)) {
					$GLOBALS['HideReturnRequestsMenu'] = "none";
				}
			}
			else {
				$GLOBALS['HideReturnRequestsMenu'] = 'none';
			}

			// How many products are in their wish list?
			$query = sprintf("select count(wishlistid) as num from [|PREFIX|]wishlists where customerid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($customerid));
			$GLOBALS['NumWishListItems'] = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);
		}
	}
