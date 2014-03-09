<?php

class ISC_ORDERFAST {
	
	public function HandlePage(){
		if(isset($_REQUEST['ToDo'])){
			$todo = strtolower(trim($_REQUEST['ToDo'])); 
		}
		else {
			$todo = '';
		}

		switch ($todo) {
			case 'sendform':
				$this->verifyForm();
			break;
			case '':
				$this->showForm();
			break;
			default:
				$this->showForm();
			break;
		}
	}
	
	private function addProductsToCart() {
		$sessionid = session_id();
		$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
		$customer = $GLOBALS['ISC_CLASS_CUSTOMER'];
			
		$insertOrderValues = array(
				'sessionid'=>$sessionid,
				'user'=>$customer->GetCustomerId(),
		);
			
		$currency = GetDefaultCurrency();
		foreach($_REQUEST['orderfastProductSKU'] as $key => $SKU){
			$sku = $_REQUEST['orderfastProductSKU'][$key];
			$type = $_REQUEST['orderfastProductType'][$key];
			$prodID = $_REQUEST['orderfastProductId'][$key];
			
			if($type == 'comb'){
				$query = sprintf("select * from [|PREFIX|]product_variation_combinations where combinationid = '$prodID'");
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$prodCombArray = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
				
				$productID = $prodCombArray['vcproductid'];
				try{
					$item = new ISC_QUOTE_ITEM;
					$item
					->setQuote($_SESSION['QUOTE'])
					->setProductId($productID)
					->setQuantity($_REQUEST['orderfastProductQty'][$key])
					->setVariation($prodID)
					->applyConfiguration('')
					->afterAddedtoCart();
					
					$_SESSION['QUOTE']->addItem($item);
				}
				catch(ISC_QUOTE_EXCEPTION $e) {
					flashMessage($e->getMessage(), MSG_ERROR, '');
				}
				
			}elseif($type == 'prod'){
				$productID = $prodID;
				
				try{
					$item = new ISC_QUOTE_ITEM;
					$item
					->setQuote($_SESSION['QUOTE'])
					->setProductId($productID)
					->setQuantity($_REQUEST['orderfastProductQty'][$key])
					->setVariation(0)
					->applyConfiguration('')
					->afterAddedtoCart();
					
					$_SESSION['QUOTE']->addItem($item);
				}
				catch(ISC_QUOTE_EXCEPTION $e) {
					flashMessage($e->getMessage(), MSG_ERROR, '');
				}
			}

		}
		header('Location: cart.php');

	}

	private function convertPrice($price, $currencyId){
		$defaultCurrency = GetDefaultCurrency();
		if($currencyId == $defaultCurrency){
			return $price;
		}else{
			$currencyEx = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT currencyexchangerate FROM [|PREFIX|]currencies WHERE currencyid = "'.$currencyId.'"', 'currencyexchangerate');
			return $price * $currencyEx;
		}
		
	}
	
	private function verifyForm() {
		$GLOBALS['orderfastForm'] = '';
		$error = false;
		foreach($_REQUEST['orderfastProductSKU'] as $key => $SKU) {
			
			$query = sprintf("select pvc.* from [|PREFIX|]product_variation_combinations pvc
			JOIN [|PREFIX|]intelisis_variation_combinations ivc ON (pvc.combinationid=ivc.combinationid)
			where pvc.vcsku = '$SKU'
			OR ivc.Articulo = '$SKU'");
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$prodCombArray = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			if($prodCombArray == ''){
				$query = sprintf("select p.* from [|PREFIX|]products p 
				JOIN [|PREFIX|]intelisis_products ip ON (p.productid=ip.productid)
				where p.prodcode = '".$SKU."'
				OR ip.Articulo = '".$SKU."'");
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$productArray = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
				$prodID = $productArray['productid'];
				$GLOBALS['orderfastProductId'] = $prodID;
				$GLOBALS['orderfastProductType'] = 'prod';
			}else{
				$prodID = $prodCombArray['vcproductid'];
				$combID = $prodCombArray['combinationid'];
				$query = sprintf("select * from [|PREFIX|]products where productid = '".$prodID."'");
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$productArray = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
				$GLOBALS['orderfastProductId'] = $combID;
				$GLOBALS['orderfastProductType'] = 'comb'; 
			}

			//cantidad
			$prodQty = $_REQUEST['orderfastProductQty'][$key];
			
			if(ctype_digit($prodQty) != '1' || $prodQty == '0'){
				$productArray = '';
			}
			
			if($productArray == ''){
				$GLOBALS['orderfastProductName'] = 'No se encontro el producto o la cantidad no es reconocida';
				$error = true;
				$GLOBALS['orderfastProductQty'] = $prodQty;
				$GLOBALS['orderfastProductSKU'] = $SKU;
				$GLOBALS['orderfastProductPrice'] = '';
				$GLOBALS['orderfastProductDiscount'] = '';
				$GLOBALS['orderfastProductCurrency'] = '';
				$GLOBALS['orderfastProductStorage'] = '';
				$GLOBALS['orderfastForm'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->getSnippet('OrderfastFieldRow');
			}else{
				$GLOBALS['orderfastProductQty'] = $prodQty;
				$GLOBALS['orderfastProductSKU'] = $SKU;

				//Calculo de precio de variaciones
				if($GLOBALS['orderfastProductType'] == 'comb'){
					$diff = $prodCombArray['vcprice'];
					if($prodCombArray['vcpricediff'] == 'add'){
						$productArray['prodcalculatedprice'] += $diff;
					}elseif($prodCombArray['vcpricediff'] == 'subtract'){
						$productArray['prodcalculatedprice'] -= $diff;
					}elseif($prodCombArray['vcpricediff'] == 'fixed'){
						$productArray['prodcalculatedprice'] = $diff;
					}
				}
				
				//Calculo del precio con descuento
				//Falta aplicar el descuento del cliente
				$return = applyPyC($productArray, $prodQty);
				$pricePyC = $return['Precio'];
				$currencyId = $return['Moneda'];
				$discount = $return['Descuento'];
				
				$pricePyC -= $pricePyC * ($discount/100); 
				
				/*if($pricePyC == ''){
					$discount = '0';
				}else{
					$discount = (100 - ((100 * $pricePyC)/$productArray['prodcalculatedprice']));
				}*/

				$GLOBALS['orderfastProductName'] = $productArray['prodname'];
				if($GLOBALS['orderfastProductType'] == 'comb'){
					$item = new ISC_QUOTE_ITEM();
					$item
						->setQuote($_SESSION['QUOTE'])
						->setProductId($prodID)
						->setQuantity($_REQUEST['orderfastProductQty'][$key])
						->setVariation($combID)
						->applyConfiguration('');
					$options = $item->getVariationOptions();
					if(!empty($options)) {
						$GLOBALS['orderfastProductName'] .= "<br /><small>(";
						$comma = '';
						foreach($options as $name => $value) {
							if(!trim($name) || !trim($value)) {
								continue;
							}
							$GLOBALS['orderfastProductName'] .= $comma.isc_html_escape($name).": ".isc_html_escape($value);
							$comma = ', ';
						}
						$GLOBALS['orderfastProductName'] .= ")</small>";
					}
				}
				
				
				if($discount == 0){
					$GLOBALS['orderfastProductPrice'] = '$'.$this->convertPrice($productArray['prodcalculatedprice'], $currencyId);
					$GLOBALS['orderfastProductTotal'] = '$'.$this->convertPrice($productArray['prodcalculatedprice'] * $prodQty, $currencyId);
				}else{
					$GLOBALS['orderfastProductPrice'] = '<strike> $'.$this->convertPrice($productArray['prodcalculatedprice'], $currencyId).'</strike>  $'.$this->convertPrice($pricePyC, $currencyId);
					$GLOBALS['orderfastProductTotal'] = '$'.$this->convertPrice($pricePyC * $prodQty, $currencyId);
				}
				if($discount > '0'){				
					$GLOBALS['orderfastProductDiscount'] = number_format($discount).'%';
				}else{
					$GLOBALS['orderfastProductDiscount'] = '';
				}

				$currencyCode = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT currencycode FROM [|PREFIX|]currencies WHERE currencyid = "'.$currencyId.'"', 'currencycode');
				$GLOBALS['orderfastProductCurrency'] = $currencyCode;
				$GLOBALS['orderfastProductStorage'] = '1';
				
				// render Snippet OrderfastFieldRow
				$GLOBALS['orderfastForm'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->getSnippet('OrderfastFieldRow');
			}
		}
		if($error == false){
		$GLOBALS['orderfastAddToCartButton'] = '<input type="submit" name="SubmitAddToCartForm" value="Enviar a Carrito" />';
		}else{
			$GLOBALS['orderfastAddToCartButton'] = '';
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle('Pedido Rapido');
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('order.fast');
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
			exit;
		}
		
		if(isset($_REQUEST['SubmitAddToCartForm'])) {
			$this->addProductsToCart($productArray['prodcalculatedprice']);
		}
		else {
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle('Pedido Rapido');
			$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('order.fast');
			$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}
	}
	
	private function showForm() {

		$GLOBALS['orderfastFieldId'] = 0;
		$GLOBALS['orderfastForm'] = $GLOBALS['ISC_CLASS_TEMPLATE']->getSnippet('OrderfastFieldRow');
		
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetPageTitle('Pedido Rapido');
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('order.fast');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}
}