<?php

	class ISC_WISHLIST
	{

		public function HandlePage()
		{

			$action = "";
			if(isset($_REQUEST['action'])) {
				$action = isc_strtolower($_REQUEST['action']);
			}

			if(isset($_GET['from'])) {
				$_SESSION['LOGIN_REDIR'] = sprintf("%s/%s", $GLOBALS['ShopPath'], urldecode($_GET['from']));
			}

			if (isset($_GET['publicwishlist'])) {
				$this->DisplayPublicWishList();
				return true;
			}
			// Are they signed in?
			if(CustomerIsSignedIn()) {
				switch($action) {
					case "add": {
						$this->AddItemToWishList();
						break;
					}
					case "remove": {
						$this->RemoveItemFromWishList();
						break;
					}
					case "viewwishlistitems": {
						$this->DisplayWishListItems();
						break;
					}
					case "editwishlist": {
						$this->DisplayEditWishListForm();
						break;
					}
					case "deletewishlist": {
						$this->DeleteWishLists();
						break;
					}
					case "addwishlist": {
						$this->DisplayAddWishListForm();
						break;
					}
					case "sharewishlist": {
						$this->DisplayShareWishList();
						break;
					}
					default: {
						$this->MyWishLists();
					}
				}
			}
			else {
				// Naughty naughty, you need to sign in to be here
				if(isset($_SERVER['QUERY_STRING'])) {
					$get_vars = $_SERVER['QUERY_STRING'];
				}
				else {
					$get_vars = "";
				}

				$this_page = urlencode(sprintf("wishlist.php?%s", $get_vars));
				ob_end_clean();
				header(sprintf("Location: %s/login.php?from=%s", $GLOBALS['ShopPath'], $this_page));
				die();
			}
		}

		/**
		* displays the adding wish list form
		* and add the wish list to database on request
		*
		*/
		private function DisplayAddWishListForm()
		{

			$GLOBALS['WishListName'] = '';

			$this->HandleMessages(GetLang('AddWishListIntro'), MSG_INFO);

			if (isset($_POST['submit'])) {
				$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
				$customerid = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();

				if(isset($_POST['publicwishlist'])) {
					$ispublic = 1;
				}
				else {
					$ispublic = 0;
				}

				if ($this->AddWishList($customerid, $_POST['wishlistname'], $ispublic)) {
					ob_end_clean();
					header("Location: ".$GLOBALS['ShopPath']."/wishlist.php");
					return true;
				}
			}
			if (isset($_POST['wishlistname'])) {
				$GLOBALS['WishListName'] = isc_html_escape($_POST['wishlistname']);
			}

			if (isset($_POST['publicwishlist']) && $_POST['publicwishlist']==1) {
				$GLOBALS['SelectPublic'] = 'checked="yes"';
			} else {
				$GLOBALS['SelectPublic'] = '';
			}

			$GLOBALS['PageTitle'] = GetLang('AddWishList');
			$GLOBALS['WishListAction'] = '?action=addwishlist';
			$GLOBALS['HideWishListAddFrom'] = '';
			$GLOBALS['HideWishListItems'] = 'display:none;';
			$GLOBALS['HideWishLists']  = 'display:none;';
			$GLOBALS['HideLeftMenu'] = 'display:none;';

			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName').' - '.GetLang('WishLists'));
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("wishlist");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();

		}

		/**
		* add a wish list to database
		* @param $customerid int customer id
		* @param $wishlistname string wishlist name
		* @param $ispublic int is it a public wishlist
		*
		* @return int new wishlist id
		*/
		private function AddWishList($customerid, $wishlistname = 'My Wish List', $ispublic=0)
		{

			if (trim($wishlistname) == '') {
				$this->HandleMessages(GetLang('EnterWishListName'), MSG_ERROR);
				return false;
			}
			if (!is_numeric($customerid)) {
				$this->HandleMessages(GetLang('InvalideCustID'), MSG_ERROR);
				return false;
			}
			if ($ispublic!=0 && $ispublic!=1) {
				$this->HandleMessages(GetLang('InvalidPublicWishlistValue'), MSG_ERROR);
				return false;
			}
			$wishlisttoken = md5(uniqid());
			$insertwishlist = array(
					"customerid"	=> $customerid,
					"wishlistname"	=> $wishlistname,
					"ispublic"		=> $ispublic,
					"wishlisttoken"	=> $wishlisttoken
			);
			if(!$wishlistid = $GLOBALS['ISC_CLASS_DB']->InsertQuery('wishlists', $insertwishlist)) {
				$this->HandleMessages(GetLang('ErrorAddWishList'), MSG_ERROR);
				return 0;
			} else {
				return $wishlistid;
			}
		}


		/**
		*
		* delete wishlist from database
		*
		* @return bool
		*
		*/
		private function DeleteWishLists()
		{
			// delete single wish list
			if (isset($_GET['wishlistid']) && $_GET['wishlistid']>0) {
				$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
				$customerid = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();

				if ($this->IsValidWishListUser($_GET['wishlistid'], $customerid) == false) {
					$this->MyWishLists(GetLang('NoDeleteWishListPermission'), MSG_ERROR);
					return false;
				} else {

					$query = "Delete From [|PREFIX|]wishlists
								Where wishlistid='".(int)$_GET['wishlistid']."'
									AND customerid='".$customerid."'";

					if (!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
						$this->MyWishLists(GetLang('ErrorDeleteWishList'), MSG_ERROR);
						return false;
					}
				}
			// bulk delete wish lists
			} elseif (isset($_POST['deletewishlist']) && !empty($_POST['deletewishlist'])) {
				//delete the wishlist
				$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
				$customerid = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();

				foreach ($_POST['deletewishlist'] as $wishlistid) {
					// if the customer is not the owner of the wish list he is trying to delete
					if (!$this->IsValidWishListUser($wishlistid, $customerid)) {
						$this->MyWishLists(GetLang('NoDeleteWishListPermission'), MSG_ERROR);
						return false;
					}
				}
				$deletedListsSanitized = array_map('intval', $_POST['deletewishlist']);
				$deletedLists = implode(',', $deletedListsSanitized);

				$GLOBALS['ISC_CLASS_DB']->DeleteQuery("wishlist_items", "WHERE wishlistid IN (".$deletedLists.")");

				$GLOBALS['ISC_CLASS_DB']->DeleteQuery("wishlists", "WHERE wishlistid IN (".$deletedLists.") AND customerid='".(int)$customerid."'");
			} else {
				$this->MyWishLists(GetLang('ErrorDeleteWishList'), MSG_ERROR);
				return false;
			}
			$this->MyWishLists(GetLang('SuccessDeleteWishList'), MSG_SUCCESS);
			return true;
		}

		/**
		*
		* displays the wishlist edit form
		* and update the wishlist
		*
		*/
		private function DisplayEditWishListForm()
		{
			$this->HandleMessages(GetLang('EditWishListIntro'), MSG_INFO);

			if (isset($_GET['wishlistid']) && is_numeric($_GET['wishlistid'])) {
				$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
				$customerid = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();

				//if wish list belongs to the current loged in user
				if ($this->IsValidWishListUser($_GET['wishlistid'], $customerid) == false) {
					$this->MyWishLists(GetLang('NoEditWishListPermission'), MSG_ERROR);
					return false;
				}

				if (isset($_POST['submit'])) {
					if (!$this->EditWishList($customerid)) {
						$this->HandleMessages(GetLang('ErrorEditWishList'), MSG_ERROR);
					} else {
						$this->MyWishLists(GetLang('SuccessEditWishList'), MSG_SUCCESS);
						return true;
					}
				}
				$wishlist = $this->GetWishListDetailsByID($_GET['wishlistid']);
				$GLOBALS['WishListName'] = isc_html_escape($wishlist['wishlistname']);
				$GLOBALS['WishListID'] = (int) $_GET['wishlistid'];
				if ($wishlist['ispublic']==1) {
					$GLOBALS['SelectPublic'] = 'checked="yes"';
				} else {
					$GLOBALS['SelectPublic'] = '';
				}


				$GLOBALS['WishListAction'] = '?action=editwishlist&wishlistid='.(int)$GLOBALS['WishListID'];
				$GLOBALS['HideWishListAddFrom'] = '';
				$GLOBALS['HideWishListItems'] = 'display:none;';
				$GLOBALS['HideWishLists']  = 'display:none;';
				$GLOBALS['PageTitle'] = GetLang('EditWishList');
				$GLOBALS['HideLeftMenu'] = 'display:none;';

				$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName').' - '.GetLang('WishLists'));
				$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("wishlist");
				$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
			} else {
				$this -> MyWishLists(GetLang('WishListNotFound'), MSG_ERROR);
			}
		}

		/**
		* update wishlist in database with users input
		*/
		private function EditWishList($customerid)
		{
			if(isset($_POST['publicwishlist'])) {
				$ispublic = 1;
			}
			else {
				$ispublic = 0;
			}
			$wishlist = array(
						"wishlistname" => $_POST['wishlistname'],
						"ispublic" => $ispublic
					);

			if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery("wishlists", $wishlist, "wishlistid=".(int)$_GET['wishlistid']." AND customerid=".(int)$customerid)) {
				return false;
			}
			return true;
		}


		/**
		* show public wishlist
		*
		*/
		private function DisplayPublicWishListItems()
		{
			if (isset($_REQUEST['publicwishlist'])) {
				$wishlisttoken = $_REQUEST['publicwishlist'];
			}

			$wishlist = $this->GetWishListDetailsByToken($wishlisttoken);
			$num_items = 0;

			// if it's a invalide wishlist id
			if (empty($wishlist)) {
				$GLOBALS['WishListName'] = GetLang('InvalidWishList');
				$this->HandleMessages(GetLang('WishListNotFound'),MSG_ERROR);
			}
			else {
				$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
				$customerid = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();

				// if wishlist isn't public
				if ($wishlist['ispublic']!=1) {
					$GLOBALS['WishListName'] = GetLang('WishListNotPublicTitle');
					$this->HandleMessages(GetLang('NotPublicWishList'),MSG_ERROR);
					$GLOBALS['WishListItems'] = array();
				} else {
					$GLOBALS['WishListItems'] = $this->_GetWishListItemsByToken($wishlisttoken);
					$GLOBALS['WishListName'] = isc_html_escape($wishlist['wishlistname']);
					$num_items = count($GLOBALS['WishListItems']);

					if($num_items == 0) {
						$this->HandleMessages(GetLang('EmptyWishListMessage'),MSG_INFO);
					}
					else {
						if($num_items == 1) {
							$this->HandleMessages(GetLang('1ItemInWishListMessage'),MSG_INFO);
						}
						else {
							$this->HandleMessages(sprintf(GetLang('XItemsInWishListMessage'),$num_items),MSG_INFO);
						}
					}
				}
			}

			$GLOBALS['HideWishListAddFrom'] = 'display:none;';
			$GLOBALS['HideWishListItems'] = '';
			$GLOBALS['HideWishLists']  = 'display:none;';
			$GLOBALS['HideLeftMenu'] = '';
			$GLOBALS['PublicWishListUrl'] = '';
			$GLOBALS['HideShareWishList'] = 'display:none;';

			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName') . " - " . GetLang('YourWishList'));
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("wishlist");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}


		/**
		* show products in wishlist
		* @param $MsgDesc string a message
		* @param $MsgStatus int message type error, info, sussecc
		* @param $publicView bool if requested as viewing a public wish list,
		*/
		private function DisplayWishListItems($MsgDesc = "", $MsgStatus = "", $wishlistid=0)
		{

			if (isset($_REQUEST['wishlistid'])) {
				$wishlistid = (int) $_REQUEST['wishlistid'];
			}

			$GLOBALS['PublicWishListUrl'] = '';
			$GLOBALS['HideShareWishList'] = 'display:none;';

			$wishlist = $this->GetWishListDetailsByID($wishlistid);

			// if it's a invalide wishlist id
			if (empty($wishlist)) {
				$GLOBALS['WishListName'] = GetLang('InvalidWishList');
				$this->HandleMessages(GetLang('WishListNotFound'),MSG_ERROR);
			}
			else {
				$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
				$customerid = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();

				//if user trying to view a wishlist which doesn't belong to him.
				if (!$this->IsValidWishListUser($wishlistid, $customerid)) {
					$GLOBALS['WishListName'] = GetLang('CantViewWishList');
					$this->HandleMessages(GetLang('NoViewWishListPermission'),MSG_ERROR);
					$GLOBALS['WishListItems'] = array();

				// if user trying to view wish list which is not public
				} else {
					$GLOBALS['WishListItems'] = $this->_GetWishListItemsByID($num_items, $wishlistid);
					$GLOBALS['WishListName'] = isc_html_escape($wishlist['wishlistname']);

					$num_items = count($GLOBALS['WishListItems']);

					// Are there any items in the wishlist
					if (!is_numeric($MsgStatus)) {
						if($num_items == 0) {
							$this->HandleMessages(GetLang('EmptyWishListMessage'),MSG_INFO);
						}
						else {
							if($num_items == 1) {
								$this->HandleMessages(GetLang('1ItemInWishListMessage'),MSG_INFO);
							}
							else {
								$this->HandleMessages(sprintf(GetLang('XItemsInWishListMessage'),$num_items),MSG_INFO);
							}
						}
					} else {
						$this->HandleMessages($MsgDesc,$MsgStatus);
					}
					if ($wishlist['ispublic']==1) {
						$GLOBALS['PublicWishListUrl'] = GetConfig('ShopPath').'/wishlist.php?publicwishlist='.$wishlist['wishlisttoken'];
						$GLOBALS['HideShareWishList'] = '';
						$GLOBALS['ShareWishListIntro'] = GetLang('ShareWishList');
						$GLOBALS['ShareWishListClass'] = 'SharePublicWishList';
					}
					$GLOBALS['WishListID'] = (int) $wishlistid;
					$GLOBALS['IsPublicWishList'] = $wishlist['ispublic'];
				}
			}

			$GLOBALS['HideLeftMenu'] = 'display:none;';
			$GLOBALS['HideWishListAddFrom'] = 'display:none;';
			$GLOBALS['HideWishListItems'] = '';
			$GLOBALS['HideWishLists']  = 'display:none;';

			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName') . " - " . GetLang('YourWishList'));
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("wishlist");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}

		/**
		* display public wishlist share page
		*
		*/
		private function DisplayShareWishList()
		{
			$wishlist = $this->GetWishListDetailsByID($_GET['wishlistid']);

			// if it's a invalide wishlist id
			if (empty($wishlist)) {
				$GLOBALS['WishListName'] = GetLang('InvalidWishList');
				$this->HandleMessages(GetLang('WishListNotFound'),MSG_ERROR);
			}
			else {
				$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
				$customerid = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();

				//if user trying to view a wishlist which doesn't belong to him.
				if (!$this->IsValidWishListUser($_GET['wishlistid'], $customerid)) {
					$GLOBALS['WishListName'] = GetLang('CantViewWishList');
					$this->HandleMessages(GetLang('NoViewWishListPermission'),MSG_ERROR);
					$GLOBALS['WishListItems'] = array();
				}
				else {
					$this->HandleMessages('',-1);
					$GLOBALS['WishListItems'] = array();
					$GLOBALS['WishListName'] = GetLang("ShareAWishList")." - ".isc_html_escape($wishlist['wishlistname']);

					if ($wishlist['ispublic']==1) {
						$GLOBALS['PublicWishListUrl'] = GetConfig('ShopPath').'/wishlist.php?publicwishlist='.$wishlist['wishlisttoken'];
						$GLOBALS['HideShareWishList'] = '';
						$GLOBALS['ShareWishListClass'] = '';
						$GLOBALS['ShareWishListIntro'] = GetLang('ShareWishList');
					} else {
						$this->HandleMessages(GetLang('NotPublicWishList'),MSG_ERROR);
					}
				}
			}

			$GLOBALS['HideWishListAddFrom'] = 'display:none;';
			$GLOBALS['HideWishListItems'] = '';
			$GLOBALS['HideWishLists']  = 'display:none;';
			$GLOBALS['HideLeftMenu'] = 'display:none;';
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName') . " - " . GetLang('YourWishList'));
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("wishlist");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}

		/**
		*	Check if a product is already in the customer's wish list
		*/
		private function _ProductInWishList($ProductId, $VariationId, $WishListID)
		{
			$query = "
				SELECT count(wishlistitemid) AS num
				FROM [|PREFIX|]wishlist_items
				WHERE productid='".(int)$ProductId."' AND variationid='".(int)$VariationId."' AND wishlistid = '".(int)$WishListID."'
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

			if($row['num'] == 0) {
				return false;
			}
			else {
				return true;
			}
		}

		/**
		*	Add an item to the customer's wishlist for later purchase
		*/
		private function AddItemToWishList()
		{
			// get customer id
			$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
			$customerid = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();

			// if no wish list is selected
			if (!isset($_GET['wishlistid']) || !is_numeric($_GET['wishlistid'])) {
				$wishlists = $this->LoadCustomerWishLists($customerid);
				// if customer doesn't have any wish list, create one and name it to My Wish List
				if (empty($wishlists)) {
					if(!$wishlistid = $this->AddWishList($customerid, GetLang('MyWishList'), 0)) {
						// wish list couldn't be created
						$this->MyWishLists(GetLang('ErrorAddWishList'), MSG_ERROR);
						return false;
					}
				// select the first wishlist
				} else {
					$wishlistid = $wishlists[0]['wishlistid'];
				}
			} else {
				$wishlistid = $_GET['wishlistid'];
			}

			$wishlistid = (int)$wishlistid;

			if((isset($_GET['product_id']) && is_numeric($_GET['product_id'])) && is_numeric($wishlistid)) {
				$product_id = (int) $_GET['product_id'];
				$variation_id = null;
				if(isset($_GET['variation_id']) && is_numeric($_GET['variation_id'])) {
					$variation_id = $_GET['variation_id'];
				}

				// Is this product already in their wishlist?
				if(!$this->_ProductInWishList($product_id, $variation_id, $wishlistid)) {

					// Add it to the wishlist
					$new_item = array(
						"wishlistid" => $wishlistid,
						"productid" => $product_id,
					);

					if ($variation_id != null) {
						$new_item['variationid'] = $variation_id;
					}

					$GLOBALS['ISC_CLASS_DB']->InsertQuery("wishlist_items", $new_item);

					if($GLOBALS['ISC_CLASS_DB']->Error() == "") {
						$this->DisplayWishListItems(GetLang('ItemAddedToWishList'), MSG_SUCCESS, $wishlistid);
					}
					else {
						$add_link = sprintf("%s/wishlist.php?action=add&product_id=%d", $GLOBALS['ShopPath'], $product_id);
						$this->DisplayWishListItems(sprintf(GetLang('ErrorAddingItemToWishList'), $add_link), MSG_ERROR, $wishlistid);
					}
				}
				else {
					// It's already in the wishlist
					$this->DisplayWishListItems(GetLang('ItemAddedToWishList'), MSG_SUCCESS, $wishlistid);
				}
			}
			else {
				// Bad product id
				$this->DisplayWishListItems(GetLang('ErrorAddingItemToWishList'), MSG_ERROR, $wishlistid);
			}
		}


		/**
		*	Get a list of items in this customer's wishlist
		*/
		private function _GetWishListItemsByID(&$NumItems, $wishlistid=0)
		{
			$items = array();

			$query = "
				SELECT w.wishlistitemid, p.*, FLOOR(prodratingtotal/prodnumratings) AS prodavgrating, pi.*, ".GetProdCustomerGroupPriceSQL()."
				FROM [|PREFIX|]wishlist_items w
				INNER JOIN [|PREFIX|]products p ON (w.productid=p.productid)
				LEFT JOIN [|PREFIX|]product_images pi ON (p.productid=pi.imageprodid AND pi.imageisthumb=1)
				WHERE w.wishlistid='".(int)$wishlistid."'
				".GetProdCustomerGroupPermissionsSQL()."
			";


			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			$NumItems = $GLOBALS['ISC_CLASS_DB']->CountResult($result);

			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				array_push($items, $row);
			}

			return $items;
		}


		/**
		*	Get a list of items in this customer's wishlist by wish list token
		*/
		private function _GetWishListItemsByToken($wishlisttoken="")
		{
			$items = array();

			$query = "
				SELECT wi.wishlistitemid, p.*, FLOOR(prodratingtotal/prodnumratings) AS prodavgrating, pi.*, ".GetProdCustomerGroupPriceSQL()."
				FROM  [|PREFIX|]wishlists w
				LEFT JOIN [|PREFIX|]wishlist_items wi ON (w.wishlistid=wi.wishlistid)
				INNER JOIN [|PREFIX|]products p ON (wi.productid=p.productid)
				LEFT JOIN [|PREFIX|]product_images pi ON (p.productid=pi.imageprodid AND pi.imageisthumb=1)
				WHERE w.wishlisttoken='".$GLOBALS['ISC_CLASS_DB']->Quote($wishlisttoken)."'
				".GetProdCustomerGroupPermissionsSQL()."
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				array_push($items, $row);
			}
			return $items;
		}


		/**
		* hide or show different message box upon the message status
		* @param $MsgDesc the acctual message
		* @param $MsgStatus the message type
		*/
		private function HandleMessages($MsgDesc = "", $MsgStatus = "")
		{
			if($MsgStatus == MSG_INFO) {
				$GLOBALS['WishListMessage'] = $MsgDesc;
				$GLOBALS['HideErrorMessage'] = "none";
				$GLOBALS['HideSuccessMessage'] = "none";
				$GLOBALS['HideNormalMessage'] = "";
			}
			else if($MsgStatus == MSG_ERROR) {
				$GLOBALS['WishListMessage'] = $MsgDesc;
				$GLOBALS['HideNormalMessage'] = "none";
				$GLOBALS['HideSuccessMessage'] = "none";
				$GLOBALS['HideErrorMessage'] = "";
			}
			else if($MsgStatus == MSG_SUCCESS) {
				$GLOBALS['WishListMessage'] = $MsgDesc;
				$GLOBALS['HideNormalMessage'] = "none";
				$GLOBALS['HideErrorMessage'] = "none";
				$GLOBALS['HideSuccessMessage'] = "";
			} else {
				$GLOBALS['HideNormalMessage'] = "none";
				$GLOBALS['HideErrorMessage'] = "none";
				$GLOBALS['HideSuccessMessage'] = "none";
			}
		}

		/**
		*	Show a list of all items in the customer's wishlist
		*/
		private function MyWishLists($MsgDesc = "", $MsgStatus = "")
		{
			// get customer id
			$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
			$customerid = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();

			$wishlists = $this->LoadCustomerWishLists($customerid);

			if(empty($wishlists)) {
				$GLOBALS['HideNoWishlistMessage'] = '';
			}
			else {
				$GLOBALS['HideNoWishlistMessage'] = 'display: none';
			}

			// Was there an error?
			if(!is_numeric($MsgStatus)) {
				// Are there any wishlist
				if(empty($wishlists)) {
					$MsgDesc = GetLang('NoWishListsMessage');
					$GLOBALS['HideWishListsTable'] = "display:none;";
				}
				else {
					$MsgDesc = GetLang('WishListsIntro');
				}
				$this->HandleMessages($MsgDesc, MSG_INFO);
			} else {
				// are there any wishlist
				if(empty($wishlists)) {
					$GLOBALS['HideWishListsTable'] = "display:none;";
				}
				$this->HandleMessages($MsgDesc, $MsgStatus);
			}

			$GLOBALS['SNIPPETS']['WishList'] = '';
			foreach ($wishlists as $wishlist) {
				$query = "
					SELECT count(*) as num
					FROM [|PREFIX|]wishlist_items
					WHERE wishlistid='".(int)$wishlist['wishlistid']."'
				";

				$result=$GLOBALS['ISC_CLASS_DB']->Query($query);
				$GLOBALS['NumOfItems'] = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

				$GLOBALS['WishListID'] = (int) $wishlist['wishlistid'];
				$GLOBALS['WishListName'] = isc_html_escape($wishlist['wishlistname']);
				if ($wishlist['ispublic']==1) {
					$GLOBALS['WishListShared'] = GetLang('SearchLangYes');
					$GLOBALS['ShareWishListLink'] = '<a href="wishlist.php?action=sharewishlist&amp;wishlistid='.$GLOBALS['WishListID'].'">'.GetLang('Share').'</a>';
				} else {
					$GLOBALS['WishListShared'] = GetLang('SearchLangNo');
					$GLOBALS['ShareWishListLink'] = GetLang('Share');
				}
				$GLOBALS['SNIPPETS']['WishList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("WishList");
			}

			$GLOBALS['HideWishListAddFrom'] = 'display:none;';
			$GLOBALS['HideWishListItems'] = 'display:none;';
			$GLOBALS['HideWishLists']  = '';
			$GLOBALS['HideLeftMenu'] = 'display:none;';

			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle(GetConfig('StoreName').' - '.GetLang('WishLists'));
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate("wishlist");
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}

		/**
		*	Remove an item from the customer's wish list
		*/
		private function RemoveItemFromWishList()
		{

			if(isset($_GET['item_id']) && is_numeric($_GET['item_id'])) {
				$item_id = (int)$_GET['item_id'];

				$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
				$customerid = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();

				$query = "
					SELECT w.wishlistid
					FROM [|PREFIX|]wishlists w
					LEFT JOIN [|PREFIX|]wishlist_items wi ON (w.wishlistid = wi.wishlistid)
					WHERE wi.wishlistitemid ='".(int)$item_id."' AND w.customerid = '".(int)$customerid."'
				";

				$result=$GLOBALS['ISC_CLASS_DB']->Query($query);
				$row=$GLOBALS['ISC_CLASS_DB']->Fetch($result);

				if (empty($row)) {
					$remove_link = $GLOBALS['ShopPath']."/wishlist.php?action=remove&item_id=".$item_id;
					$this->DisplayWishListItems(GetLang('NoRemoveItemFromWishListPermission'), MSG_ERROR);
					return false;
				}

				$query = "delete from [|PREFIX|]wishlist_items where wishlistitemid='".$item_id."'";
				if(!$GLOBALS['ISC_CLASS_DB']->Query($query)) {
					$remove_link = $GLOBALS['ShopPath']."/wishlist.php?action=remove&item_id=".$item_id;
					$this->DisplayWishListItems(sprintf(GetLang('ErrorRemovingItemFromWishList'), $remove_link), MSG_ERROR);
					return false;
				} else {
					ob_end_clean();
					header("Location: ".$GLOBALS['ShopPath']."/wishlist.php?action=viewwishlistitems&wishlistid=".$row['wishlistid']);
					die();
				}
			}
			else {
				// Bad item details
				$this->DisplayWishListItems(GetLang('ErrorRemovingInvalidIdItemFromWishList'), MSG_ERROR);
			}
		}


		/**
		* get all wish lists for the customer
		*
		* @param $customerid int customer id
		*
		* @return array array of wishlists
		*/
		private function LoadCustomerWishLists($customerid)
		{
			$wishLists = array();
			// get customer's wish list from database
			$query = "
				SELECT *
				FROM [|PREFIX|]wishlists
				WHERE customerid =".(int)$customerid
			;
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			while ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$wishLists[] = $row;
			}
			return $wishLists;
		}


		/**
		* get information for the wishlist
		* @param $wishlistid int
		*
		* return array array of wishlist's details
		*/
		private function GetWishListDetailsByID($wishlistid)
		{
			$wishLists = array();
			// get customer's wish list from database
			$query = "
				SELECT *
				FROM [|PREFIX|]wishlists
				WHERE wishlistid = ".(int)$wishlistid
			;
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			$wishList = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			return $wishList;
		}

		/**
		* get information for the wishlist
		* @param $wishlistid int
		*
		* return array array of wishlist's details
		*/
		private function GetWishListDetailsByToken($wishlisttoken)
		{
			$wishLists = array();
			// get customer's wish list from database
			$query = "
				SELECT *
				FROM [|PREFIX|]wishlists
				WHERE wishlisttoken = '".$GLOBALS['ISC_CLASS_DB']->Quote($wishlisttoken)."'
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			$wishList = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			return $wishList;
		}

		/**
		* display the public wish list.
		*(hide account menu and remove item button on public view)
		*/
		private function DisplayPublicWishList()
		{

			$GLOBALS['HideRightMenu'] = 'display:none;';
			$GLOBALS['HideRemoveItemButton'] = 'display:none;';
			$this -> DisplayPublicWishListItems();

		}

		private function IsValidWishListUser($wishlistid, $customerid)
		{
			$query = "
				SELECT count(*)
				FROM [|PREFIX|]wishlists
				WHERE wishlistid='".(int)$wishlistid."' AND customerid='".(int)$customerid."'
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$numofrows = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);
			if ($numofrows == 0) {
				return false;
			}
			return true;
		}
	}
