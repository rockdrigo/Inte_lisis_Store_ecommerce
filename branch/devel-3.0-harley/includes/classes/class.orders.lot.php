<?php
class ISC_ORDERS_LOT
{
	private $pageTitle = "";

	public function HandlePage()
	{
		if(isset($_POST['SubmitOrdersLotForm'])) $this->ProcessNewOrderLot();
		else $this->PrintProducts();
	}
	
	private function PrintProducts() {

		$GLOBALS['ShopPath'] = GetConfig('ShopPath');
		$GLOBALS['ProductListForm'] = $this->GetProductList();
		
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('orders.lot');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}
	
	private function GetProductList() {
		$ProductTable = '';
		
		$categories = $GLOBALS['ISC_CLASS_DB']->Query("SELECT categoryid, catname FROM [|PREFIX|]categories ORDER BY catsort");
		
		while($category = $GLOBALS['ISC_CLASS_DB']->Fetch($categories)) {
					$ProductTable .= '<tr><td colspan=5 style="background: none repeat scroll 0 0 #FF7C81;color: #FFFFFF;">'.$category['catname'].'</td></tr>';
					$products = $GLOBALS['ISC_CLASS_DB']->Query("SELECT productid, prodname, prodcode, prodprice, prodcatids
					FROM [|PREFIX|]products
					WHERE prodvariationid = 0 AND prodallowpurchases = 1
					-- AND (prodcatids = ".$category['categoryid']." OR prodcatids LIKE '%,".$category['categoryid'].",%' OR prodcatids LIKE '%,".$category['categoryid']."' OR prodcatids LIKE '".$category['categoryid'].",%')
					AND (prodcatids LIKE '".$category['categoryid']."%')
					ORDER BY productid");

					while($product = $GLOBALS['ISC_CLASS_DB']->Fetch($products)) {
						$ProductTable .= '<tr>'.PHP_EOL;
						$ProductTable .= '<td>'.$product['prodname'].'</td>'.PHP_EOL;
						$ProductTable .= '<td>'.$product['prodcode'].'</td>'.PHP_EOL;
						$ProductTable .= '<td>'.$product['prodprice'].'</td>'.PHP_EOL;
						$ProductTable .= '<td><input type="text" size=3 name=quantity['.$product['productid'].'] value="0" /></td>'.PHP_EOL;
						$ProductTable .= '<td><input type="text" size=60 name=observ['.$product['productid'].'] /></td>'.PHP_EOL;
						$ProductTable .= '</tr>'.PHP_EOL;
					}
		}

		return $ProductTable;
	}
	
	function ProcessNewOrderLot() {
		$sessionid = session_id();
		$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
		$customer = $GLOBALS['ISC_CLASS_CUSTOMER'];
		
			$keys = array_keys($_REQUEST['quantity']);
			
			$insertOrderValues = array(
				'sessionid'=>$sessionid,
				'user'=>$customer->GetCustomerId(),
			);
			
			$currency = GetDefaultCurrency();
			
			foreach($keys as $productID) {
				if (is_int($_REQUEST['quantity'][$productID]) || $_REQUEST['quantity'][$productID] > 0) {
					$quantity = $_REQUEST['quantity'][$productID];
					$observs = $_REQUEST['observ'][$productID];
					$product = new ISC_PRODUCT($productID);

					$commentFieldID_result = $GLOBALS['ISC_CLASS_DB']->Query("SELECT productfieldid FROM [|PREFIX|]product_configurable_fields WHERE fieldprodid = ".$productID." AND fieldname = 'Observaciones'");
					$commentFieldID_row = $GLOBALS['ISC_CLASS_DB']->Fetch($commentFieldID_result);
					$commentFieldID = $commentFieldID_row['productfieldid'];
					$observsFields = array();
					$observsFields[$commentFieldID] = $observs;
					
					try {
						$item = new ISC_QUOTE_ITEM;
						$item
							->setQuote($_SESSION['QUOTE'])
							->setProductId($productID)
							->setQuantity($quantity)
							->setVariation(0)
							->applyConfiguration($observsFields);	
			
						$_SESSION['QUOTE']->addItem($item);
					}
					catch(ISC_QUOTE_EXCEPTION $e) {
						flashMessage($e->getMessage(), MSG_ERROR, prodLink($product->GetProductName()));
					}
				}
			header('Location: cart.php');
		}
	}
}
