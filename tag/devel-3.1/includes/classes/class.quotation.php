<?php

class ISC_QUOTATION
{
	public function HandlePage(){
		if(!isset($GLOBALS['ISC_CLASS_CUSTOMER'])){
			$GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
		}
		$custInfo = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerInfo();
		if($custInfo == NULL){
			header(sprintf("location:%s/account.php", $GLOBALS['ShopPath']));
		}else{
			if(isset($_REQUEST['ToDo'])){
				$todo = strtolower(trim($_REQUEST['ToDo']));
			}
			else {
				$todo = '';
			}
			
			switch ($todo) {
				case 'deletequotation':
					$this->deleteQuotation();
					break;
				case 'sendtocart':
					$this->sendToCart();
					break;
				case 'savequotation1':
					$this->saveQuotationStep1();
					break;
				case 'savequotation2':
					$this->saveQuotationStep2();
					break;
				case 'viewquotation':
					$this->viewQuotation();
					break;
				default:
					$this->showQuotations();
					break;
			}
		}
	}
	
	public function saveQuotationStep1(){
		echo 'save quotation 1';
	}
	
	public function saveQuotationStep2(){
		$quotation = serialize($_SESSION['QUOTE']);
		$customerId = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();
		if($_POST['quotationname'] != ''){
			$quotationName = $_POST['quotationname'];
		}else{
			$date = date('d-m-Y', time());
			$quotationName = 'Cotizacion '.$date;
		}
				
		$query = "INSERT INTO [|PREFIX|]quotations (customerid, quotationdate, quotation, quotationname) VALUES ('".$customerId."', '".time()."', '".$quotation."', '".$quotationName."')";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		if($result == '1'){
			FlashMessage('Cotizacion guardada con exito', MSG_SUCCESS);
			header(sprintf("location:%s/quotation.php?ToDo=", $GLOBALS['ShopPath']));
		}else{
			logAddError('Error al guardar cotizacion '.$quotationName.' del usuario '.$customerId.' con la cotizacion '.$quotation);
			FlashMessage('Error al guardar la cotizacion', MSG_ERROR, "/cart.php");
		}
		
	}
		
	
	public function viewQuotation(){
		$quoteId = $_GET['quotationid'];
		$customerId = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();
		if($quoteId == NULL){
			header(sprintf("location:%s/quotation.php?ToDo=", $GLOBALS['ShopPath']));
		}else{
			$query = sprintf("select * from [|PREFIX|]quotations where quotationid = '".$quoteId."' and customerid = '".$customerId."'");
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$quoteArray = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			
			if($quoteArray == NULL){
				header(sprintf("location:%s/quotation.php?ToDo=", $GLOBALS['ShopPath']));
			}else{
			}
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('quotation.view');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
		}
	}
	
	public function showQuotations(){
		$customerId = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();
		$fromLimit = 0;
		$toLimit = 20;
		$datefilter = 0;
		$tabla = '';
		
		if((isset($_POST['quotationfromdate'])) && $_POST['quotationfromdate'] != null && $_POST['quotationtodate'] != null){
			$fdate = $_POST['quotationfromdate'];
			$tdate = $_POST['quotationtodate'];
			$datefilter = 1;
		}else{
			$datefilter = 0;
		}
		
		//falta el link
		//falta el filtro de fechas y contatenarlo al where
		if($datefilter==1){
			$fdatex = strtotime($fdate);
			$tdatex = strtotime($tdate)+86400;
			$where = "customerid = '".$customerId."' and quotationdate >= '".$fdatex."' and quotationdate <= '".$tdatex."'";
		}else{
			$where = "customerid = '".$customerId."'";
		}
		$limit = $fromLimit.",".$toLimit;
		
		$query = sprintf("select * from [|PREFIX|]quotations where ".$where." limit ".$limit);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($quoteArray = $GLOBALS['ISC_CLASS_DB']->Fetch($result))
		{
			$quoteName = $quoteArray['quotationname'];
			$quoteDate = date('d-m-Y', $quoteArray['quotationdate']);
			$quoteId = $quoteArray['quotationid'];
			
			$GLOBALS['quotationName'] = $quoteName;
			$GLOBALS['quotationDate'] = $quoteDate;
			$GLOBALS['quotationId'] = $quoteId;
			$endDate = $quoteArray['quotationdate']+(GetConfig('QuotationDays')*86400);
			if(time() > $endDate){
				$GLOBALS['quotationCart'] = 'Cotizacion vencida';
			}else{
				$GLOBALS['quotationCart'] = '<a href="/quotation.php?ToDo=sendtocart&quotationid=%%GLOBAL_quotationId%%">Enviar a carrito</a>';
			}
			
			$tabla .= $GLOBALS['ISC_CLASS_TEMPLATE']->getSnippet('QuotationsListRow');
			
		}
		
		$GLOBALS['QuotationList'] = $tabla;
		
		$GLOBALS['ISC_CLASS_TEMPLATE']->SetTemplate('quotation.list');
		$GLOBALS['ISC_CLASS_TEMPLATE']->ParseTemplate();
	}
	
	public function sendToCart(){
		$quoteId = $_GET['quotationid'];
		$customerId = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();
		if($quoteId == NULL){
			header(sprintf("location:%s/quotation.php?ToDo=", $GLOBALS['ShopPath']));
		}else{
			$query = sprintf("select * from [|PREFIX|]quotations where quotationid = '".$quoteId."' and customerid = '".$customerId."'");
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$quoteArray = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
			if($quoteArray == NULL){
				header(sprintf("location:%s/quotation.php?ToDo=", $GLOBALS['ShopPath']));
			}else{
				$endDate = $quoteArray['quotationdate']+(GetConfig('QuotationDays')*86400);
				if(time() > $endDate){
					header(sprintf("location:%s/quotation.php?ToDo=", $GLOBALS['ShopPath']));
				}else{
					$_SESSION['QUOTE'] = new ISC_QUOTE();
				
					$quote = unserialize($quoteArray['quotation']);
					$items = $quote->getItems();
					$coupon = $quote->getAppliedCoupons();
					
	
					foreach($items as $item) {
						$item
							->setQuote($_SESSION['QUOTE'])
							->setProductId($item->getProductId())
							->setQuantity($item->getQuantity())
							->setBasePrice($item->getBasePrice(), true);
						//$item->deleteDiscounts();
							
						$_SESSION['QUOTE']->addItem($item);
						}
					foreach($coupon as $key => $value){
						$query = sprintf("select * from [|PREFIX|]coupons where couponcode = '".$key."'");
						$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
						$quoteArray = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
						$validCoupon = '';

						if($quoteArray['couponenabled'] == 1){
							if(($quoteArray['couponexpires'] != '0' && $quoteArray['couponexpires'] >= isc_mktime()) || $quoteArray['couponexpires'] == '0'){
								if($quoteArray['couponmaxuses'] == '0' || ($quoteArray['couponmaxuses'] != '0' && $quoteArray['couponnumuses'] < $quoteArray['couponmaxuses'])){
									$validCoupon = '1';
								} 
							}
						}
						if ($validCoupon == '1'){
							$_SESSION['QUOTE']->applyCoupon($key);
						}else{
							FlashMessage('El cupon esta espirado o ya no es valido', MSG_ERROR);
						}

					}
					header(sprintf("Location:%s/cart.php", $GLOBALS['ShopPath']));
				}
			}	
		}
	}
	
	public function deleteQuotation(){
		$quoteId = $_GET['quotationid'];
		$customerId = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId();
		$query = sprintf("delete from [|PREFIX|]quotations where quotationid = '".$quoteId."' and customerid = '".$customerId."'");
		$GLOBALS['ISC_CLASS_DB']->Query($query);
		FlashMessage('La cotizacion fue eliminada', MSG_INFO);
		header(sprintf("location:%s/quotation.php?ToDo=", $GLOBALS['ShopPath']));
	}
}