	<?php
/**
* Implements panel functionality common to most product-listing panels
*
*/
class PRODUCTS_PANEL extends PANEL
{
	public function getProductQuery($where = '', $order = '', $limit = null, $start = null)
	{
		$additionalColumns = array(
			'FLOOR(prodratingtotal/prodnumratings) AS prodavgrating',
			getProdCustomerGroupPriceSQL()
		);

		$additionalJoins = array();
		$query = "
			SELECT p.*, pi.*, ".implode(', ', $additionalColumns)."
			FROM [|PREFIX|]products p
			LEFT JOIN [|PREFIX|]product_images pi ON (p.productid=pi.imageprodid AND pi.imageisthumb=1)
			".implode("\n", $additionalJoins)."
			WHERE p.prodvisible=1
		";

		if($where) {
			$query .= " AND ".$where;
		}
		$query .= getProdCustomerGroupPermissionsSQL();

		if($order) {
			$query .= " ORDER BY ".$order;
		}

		if($start !== null) {
			$query .= " OFFSET ".(int)$start;
		}

		if($limit !== null) {
			$query .= " LIMIT ".(int)$limit;
		}

		return $query;
	}

	public function setProductGlobals($row)
	{
		if($GLOBALS['AlternateClass'] == 'Odd') {
			$GLOBALS['AlternateClass'] = 'Even';
		}
		else {
			$GLOBALS['AlternateClass'] = 'Odd';
		}

		$GLOBALS['ProductCartQuantity'] = '';
		if(isset($GLOBALS['CartQuantity'.$row['productid']])) {
			$GLOBALS['ProductCartQuantity'] = (int)$GLOBALS['CartQuantity'.$row['productid']];
		}

		$GLOBALS['ProductId'] = (int)$row['productid'];
		$GLOBALS['ProductName'] = isc_html_escape($row['prodname']);
		$GLOBALS['ProductLink'] = ProdLink($row['prodname']);
		$GLOBALS['ProductRating'] = (int)$row['prodavgrating'];

		/*
		 * NES: Para Intelisis, meter en prodretailprice el precio sin descuentos, y en prodcalculatedprice el nuevo precio despues de descontado con Precios y Costos
		 */

		if(GetConfig('isIntelisis'))
		{
			$row['prodretailprice'] = $row['prodcalculatedprice']; // El original, que se va a tachar

			$return =  applyPyC($row);
			$saleprice = $return['Precio'];
			$currencyId = $return['Moneda'];
			
			$discountPrice = $saleprice * ((100 - $return['Descuento']) / 100);
			
			$defaultCurrency = GetDefaultCurrency();
			if($currencyId != $defaultCurrency['currencyid']){
				$currencyEx = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT currencyexchangerate FROM [|PREFIX|]currencies WHERE currencyid = "'.$currencyId.'"', 'currencyexchangerate');
				$row['prodretailprice'] = $row['prodretailprice']*$currencyEx;
			}
			
			if($saleprice != '') {
				$row['prodcalculatedprice'] = $discountPrice; //Precio a mostrar en la lista, regresado por PyC
				$row['prodsaleprice'] = $row['prodcalculatedprice']; //Precio para decidir si es Sale o no
			}
		}

		// Determine the price of this product
		//REQ11191 JIB: Verifica la condicion de ShowPriceGuest
		if(!isset($GLOBALS['ISC_CLASS_CUSTOMER'])) $GLOBALS['ISC_CLASS_CUSTOMER'] = GetClass('ISC_CUSTOMER');
		$custInfo = $GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerInfo();
		
		$GLOBALS['ProductPrice'] = '';
		if ((GetConfig('ShowProductPrice') && !$row['prodhideprice'] && $custInfo != NULL) || (GetConfig('ShowPriceGuest') && GetConfig('ShowProductPrice') && !$row['prodhideprice'] && $custInfo == NULL)) {
			$GLOBALS['ProductPrice'] = formatProductCatalogPrice($row, array(), $currencyId);
		}

		// Workout the product description
		$desc = strip_tags($row['proddesc']);

		if (isc_strlen($desc) < 120) {
			$GLOBALS['ProductSummary'] = $desc;
		} else {
			$GLOBALS['ProductSummary'] = isc_substr($desc, 0, 120) . "...";
		}

		$GLOBALS['ProductThumb'] = ImageThumb($row, ProdLink($row['prodname']));
		$GLOBALS['ProductDate'] = isc_date(GetConfig('DisplayDateFormat'), $row['proddateadded']);

		$GLOBALS['ProductPreOrder'] = false;
		$GLOBALS['ProductReleaseDate'] = '';
		$GLOBALS['HideProductReleaseDate'] = 'display:none';

		if ($row['prodpreorder']) {
			$GLOBALS['ProductPreOrder'] = true;
			if ($row['prodreleasedate'] && $row['prodreleasedateremove'] && time() >= (int)$row['prodreleasedate']) {
				$GLOBALS['ProductPreOrder'] = false;
			} else if ($row['prodreleasedate']) {
				$GLOBALS['ProductReleaseDate'] = GetLang('ProductListReleaseDate', array('releasedate' => isc_date(GetConfig('DisplayDateFormat'), (int)$row['prodreleasedate'])));
				$GLOBALS['HideProductReleaseDate'] = '';
			}
		}

		if (isId($row['prodvariationid']) || trim($row['prodconfigfields'])!='' || $row['prodeventdaterequired'] == 1) {
			$GLOBALS['ProductURL'] = ProdLink($row['prodname']);
			$GLOBALS['ProductAddText'] = GetLang('ProductChooseOptionLink');
		} else {
			$GLOBALS['ProductURL'] = CartLink($row['productid']);
			if ($GLOBALS['ProductPreOrder']) {
				$GLOBALS['ProductAddText'] = GetLang('ProductPreOrderCartLink');
			} else {
				$GLOBALS['ProductAddText'] = GetLang('ProductAddToCartLink');
			}
		}

		if (CanAddToCart($row) && GetConfig('ShowAddToCartLink')) {
			$GLOBALS['HideActionAdd'] = '';
		} else {
			$GLOBALS['HideActionAdd'] = 'none';
		}


		$GLOBALS['HideProductVendorName'] = 'display: none';
		$GLOBALS['ProductVendor'] = '';
		if(GetConfig('ShowProductVendorNames') && $row['prodvendorid'] > 0) {
			$vendorCache = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('Vendors');
			if(isset($vendorCache[$row['prodvendorid']])) {
				$GLOBALS['ProductVendor'] = '<a href="'.VendorLink($vendorCache[$row['prodvendorid']]).'">'.isc_html_escape($vendorCache[$row['prodvendorid']]['vendorname']).'</a>';
				$GLOBALS['HideProductVendorName'] = '';
			}
		}
	}
}