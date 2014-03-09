<?php

class ISC_ADMIN_INTELISIS_OFFERS{
	
	public function RecaulculateOffers() {
		$query = 'SELECT p.*, ip.Articulo
			FROM [|PREFIX|]products p
			JOIN [|PREFIX|]intelisis_products ip ON (p.productid=ip.productid)';
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		
		while($product = $GLOBALS['ISC_CLASS_DB']->Fetch($result)){
			
			$saleprice = applyPyC($product);
			$orig = $product['prodcalculatedprice']; // El original, que se va a tachar
			
			if($product['prodcalculatedprice'] < $product['prodprice']){
				$saleprice = $product['prodcalculatedprice'];
				$orig = $product['prodprice'];
			}
			
			if($saleprice != '') {
	
				$row = array(
					'origprice' => $orig,
					'newprice' => $saleprice,	
				);
				if($GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT productid FROM [|PREFIX|]intelisis_product_offers WHERE productid = "'.$product['productid'].'"', 'productid')){
					$GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_product_offers', $row, 'productid = "'.$product['productid'].'"');
				}
				else {
					$row['productid'] = $product['productid'];
					$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_product_offers', $row);
				}
				
				if($GLOBALS['ISC_CLASS_DB']->Error() != ''){
					logAddError('Ocurrio un error al ejecutar Cron de Ofertas. '.$GLOBALS['ISC_CLASS_DB']->Error());
					break;
				}
			}
		}
	}
}