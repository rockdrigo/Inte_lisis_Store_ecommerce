<?php

function checkIntelisisTables() {
	$return = array();
	
	$queries = array(
		'brands' => array (
			'field' => 'IDMarca',
			'query' => 'SELECT ib.IDMarca
					FROM [|PREFIX|]intelisis_brands ib
					LEFT OUTER JOIN [|PREFIX|]brands b ON (ib.brandid=b.brandid)
					WHERE b.brandid IS NULL',),
		'categories' => array(
			'field' => 'IDCategoria',
			'query' => 'select ic.IDCategoria from [|PREFIX|]intelisis_categories ic
						left outer join [|PREFIX|]categories c on (ic.categoryid=c.categoryid)
						where c.categoryid IS NULL',),
		'configurable_fields' => array(
			'field' => 'IDCampo',
			'query' => 'select icf.IDCampo from [|PREFIX|]intelisis_configurable_fields icf
						left outer join [|PREFIX|]product_configurable_fields cf on (icf.productfieldid=cf.productfieldid)
						where cf.productfieldid IS NULL',),
		'customers' => array(
			'field' => 'customerid',
			'query' => 'select ic.customerid from [|PREFIX|]intelisis_customers ic
						left outer join [|PREFIX|]customers c on (ic.customerid=c.customerid)
						where c.customerid IS NULL',
		),
		'customfields'  => array(
			'field' => 'AtributoID',
			'query' => 'select icf.AtributoID from [|PREFIX|]intelisis_customfields icf
						left outer join [|PREFIX|]product_customfields cf on (icf.fieldid=cf.fieldid)
						where cf.fieldid IS NULL',),
		'images' => array(
			'field' => 'ImagenID',
			'query' => 'select ii.ImagenID from [|PREFIX|]intelisis_images ii
						left outer join [|PREFIX|]product_images i on (ii.imageid=i.imageid)
						where i.imageid IS NULL',),
		'orders' => array(
			'field' => 'orderid',
			'query' => 'SELECT io.orderid FROM [|PREFIX|]intelisis_orders io
						LEFT OUTER JOIN [|PREFIX|]orders o ON (io.orderid=o.orderid)
						WHERE o.orderid IS NULL',
		),
		'products' => array(
			'field' => 'ArticuloID',
			'query' => 'select ip.ArticuloID from [|PREFIX|]intelisis_products ip
						left outer join [|PREFIX|]products p on (ip.productid=p.productid)
						where p.productid IS NULL',),
		'shipping_addresses' => array(
			'field' => 'shipid',
			'query' => 'SELECT isa.shipid
						FROM [|PREFIX|]intelisis_shipping_addresses isa
						LEFT OUTER JOIN [|PREFIX|]shipping_addresses sa ON (isa.shipid=sa.shipid)
						WHERE sa.shipid IS NULL',),
		'variations' => array(
			'field' => 'VariacionID',
			'query' => 'select iv.VariacionID from [|PREFIX|]intelisis_variations iv
						left outer join [|PREFIX|]product_variations v on (iv.variationid=v.variationid)
						where v.variationid IS NULL',),
		'variation_options' => array(
			'field' => 'OpcionID',
			'query' => 'select ivo.OpcionID from [|PREFIX|]intelisis_variation_options ivo
						left outer join [|PREFIX|]intelisis_variations iv on (ivo.VariacionID=iv.VariacionID)
						left outer join [|PREFIX|]product_variation_options vo on (iv.variationid=vo.vovariationid AND ivo.Nombre=vo.voname)
						where vo.voname IS NULL',),
		'variation_option_values' => array(
			'field' => 'ValorID',
			'query' => 'select ivov.ValorID from [|PREFIX|]intelisis_variation_option_values ivov
						left outer join [|PREFIX|]product_variation_options vo on (ivov.voptionid=vo.voptionid)
						where vo.voptionid IS NULL',),		
	);

	foreach($queries as $key => $value)
	{
		$result = $GLOBALS['ISC_CLASS_DB']->Query($value['query']);
		if($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0)
		{
			$return[$key]['field'] = $value['field'];
			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result))
			{
				$return[$key]['values'][] = $row[$value['field']];
			}
		}
	}

	return $return;
}

function purgeIntelisisTables($toDelete) {
	foreach($toDelete as $key => $value)
	{
		$values = implode(',', $value['values']);
		$field = $value['field'];
		
		printe('Eliminando registro de '.$values.'\'s "'.$values.'" en intelisis_'.$key.' que no existen');
		if($GLOBALS['ISC_CLASS_DB']->DeleteQuery('intelisis_'.$key, 'WHERE '.$field.' IN ('.$values.')')) logAddWarning('Se elimino el registro en "'.$GLOBALS['ISC_CLASS_DB']->TablePrefix.'intelisis_'.$key.'" de los '.$field.'\'s "'.$values.'" que no existian en "'.$GLOBALS['ISC_CLASS_DB']->TablePrefix.$key.'"');
		else logAddError('Error al eliminar el registro en "'.$GLOBALS['ISC_CLASS_DB']->TablePrefix.'intelisis_'.$key.'" de los '.$field.' "'.$values.'". '.$GLOBALS['ISC_CLASS_DB']->Error());
		
	}
}

function applyPyC($product, $quantity = 1) {
	
	$db = $GLOBALS['ISC_CLASS_DB'];
	$defaultCurrency = GetDefaultCurrency();

	$query_Articulo = 'SELECT ia.* FROM [|PREFIX|]intelisis_Art ia
	LEFT OUTER JOIN [|PREFIX|]intelisis_products ip ON (ia.Articulo=ip.Articulo)
	WHERE ip.productid = "'.$product['productid'].'"';
	$result_Articulo = $db->Query($query_Articulo);
	//return $product['prodcalculatedprice'];
	
	if($db->CountResult($result_Articulo) == 0)
	$Articulo = array(
		'Articulo' => '',
		'Unidad' => '',
		'MonedaPrecio' => $defaultCurrency['currencyname'],
	);
	else $Articulo = $db->Fetch($result_Articulo);

	
	
	$monedaId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT currencyid FROM [|PREFIX|]currencies WHERE currencyname = "'.$Articulo['MonedaPrecio'].'"', 'currencyid');
	
	//Obtener el customerid de la sesion
	$quote = getCustomerQuote();
	$customerId = $quote->getCustomerId();

	if($customerId != 0)
	{
		$query_Cte = 'SELECT ic.customerid, C.*
			FROM [|PREFIX|]intelisis_customers ic
			LEFT OUTER JOIN [|PREFIX|]intelisis_Cte C ON (ic.Cliente=C.Cliente)
			WHERE ic.customerid = "'.$customerId.'"';
		$Cliente = $db->Fetch($db->Query($query_Cte));
	}
	else
	{
		$Cliente = array(
			'Agente' => '',
			'Cliente' => '',
		);
	}

	$query_proc = 'CALL [|PREFIX|]spArtPrecio("'.$Articulo['Articulo'].'", '.$quantity.', "'.$Articulo['Unidad'].'", NULL,
	NULL, NULL, NULL, NULL, "'.$Cliente['Agente'].'", "'.$Articulo['MonedaPrecio'].'",
	NULL, NULL, NULL, NULL, NULL, NULL,
	NULL, NULL, NULL, NULL, NULL,
	NULL, "'.$Cliente['Cliente'].'", NULL, NULL)';
	$result_proc = $db->Query($query_proc);
	$Precio = $db->Fetch($result_proc);

	//$GLOBALS['ISC_CLASS_DB']->FreeResult($result_proc);
	$db->Disconnect();
	$db->Connect();

	/*$currentCurrencyName = $GLOBALS['CurrentCurrency']['currencyname'];
	if($Articulo['MonedaPrecio'] == $currentCurrencyName || $Articulo['MondedaPrecio'] == NULL){
		$monedaId = $currentCurrencyName['currencyid'];
	}*/

	/*$CurrentCurrency = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT currencyname FROM [|PREFIX|]currencies WHERE currencyid = "'.$GLOBALS['CurrentCurrency'].'"', 'currencyname');
	if($Articulo['MonedaPrecio'] == NULL){
		$monedaId = $GLOBALS['CurrentCurrency'];
	}*/
	
	/*if ($Precio['Descuento'] != '')
	{
		//checar si es posible que me regrese tambien un $Precio['Precio'] modificado y aplicar el descuento a ese
		$Precio['Precio'] = $product['prodcalculatedprice'] - ($product['prodcalculatedprice'] * $Precio['Descuento'] / 100);
	}*/
	
	$Array = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT currencyid FROM [|PREFIX|]currencies WHERE currencyname = "'.$Articulo['MonedaPrecio'].'"', 'currencyid');
	if($Array == NULL){
		$monedaId = $GLOBALS['CurrentCurrency'];
	}
	
	if($Articulo['MonedaPrecio'] == $defaultCurrency['currencyname'] || $Articulo['MonedaPrecio'] == NULL){
		$monedaId = $GLOBALS['CurrentCurrency'];
	}

	
	if($Precio['Precio'] != '')
	{
		return array('Precio' => $Precio['Precio'],
					'Moneda' => $monedaId,
					'Descuento' => $Precio['Descuento']);
	}
	else
	{
		return array('Precio' => '',
					'Moneda' => $monedaId,
					'Descuento' => '');
	}
}

function getXMLnode($sourcen, $node, $remove = 0)
{
	//Le quitamos todos los newlines y tabulaciones para encontrar en una sola linea
	//$sourcen = preg_replace('/\s\s/', '', $sourcen);
	$source = preg_replace(array('/\n/', '/\t/'), '', $sourcen);
	$return = array();
	$regex = '#&lt;'.preg_quote($node, '#').'( |&gt;).*&lt;\/'.preg_quote($node, '#').'&gt;#';
	preg_match($regex, $source, $return);

	if(empty($return))
	{
		$regex = '#\<'.$node.'.*\/\>#';
		preg_match($regex, $source, $return);
	}
	
	if ($remove == 1 && !empty($return)) {
		$return[0] = preg_replace('#&lt;[\/]*'.$node.'&gt;#', '', $return[0]);
	}
	
	return !empty($return) ? $return[0] : $sourcen;
}

function getCustomFieldId($formId, $label) {
	if($label != '') {
	$fieldid = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT formfieldid FROM [|PREFIX|]formfields WHERE formfieldlabel = "'.$label.'" AND formfieldformid = "'.$formId.'"', 'formfieldid');
	
	return $fieldid != '' ? $fieldid : false;
	}
	else return false;
}

function getCustomFieldIdByPrivate($formId, $privateid) {
	if($privateid != '') {
	$fieldid = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT formfieldid FROM [|PREFIX|]formfields WHERE formfieldprivateid = "'.$privateid.'" AND formfieldformid = "'.$formId.'"', 'formfieldid');
	
	return $fieldid != '' ? $fieldid : false;
	}
	else return false;
}

function getCustomFieldByLabel($customFields, $form, $label) {
	if($formFieldId = getCustomFieldId($form, $label)) {
		return $customFields[$formFieldId]->getValue();
	}
	else {
		logAddWarning('No se encontro el campo de Forma "'.$label.'" en la forma de cuenta. Agreguelo en Herramientas>Campos de Forma');
		return '';
	}
}

function gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

function separateName($name) {
	$nameparts = explode(" ", $name);
	if(count($nameparts) == 1) return array('firstname' => $name, 'lastname' => '');
	$count = floor(count($nameparts) / 2);
	
	$name = '';
	$last = '';
	$i = 0;
	while($i<$count) {
		$name .= $nameparts[$i] . " ";
		unset($nameparts[$i]);
		$i++;
	}
	
	$last = implode(" ", $nameparts);
	
	return array('firstname' => $name, 'lastname' => $last);	
}

function isFromCron($which) {
	$trace = debug_backtrace();
	foreach($trace as $instance) {
		if(preg_match('#.*cron-'.$which.'\.php.*#', $instance['file'])){
			return true;
		}
	}
	return false;
}

function addRFCValidation(&$fields) {
	if($RFCfieldId = getCustomFieldId(FORMFIELDS_FORM_ACCOUNT, 'RFC')) {
		$newRFC = preg_replace("/[^A-Za-z0-9]|[\s]/", '', $fields[$RFCfieldId]->getValue());
		$fields[$RFCfieldId]->setValue($newRFC);
		if(trim($newRFC) != '') {
			$query = "SELECT ffs.formfieldfieldlabel, ffs.formfieldfieldvalue
				FROM [|PREFIX|]customers c
				JOIN [|PREFIX|]formfieldsessions ffs ON (c.custformsessionid=ffs.formfieldsessioniformsessionid)
				WHERE ffs.formfieldfieldvalue = '".serialize($fields[$RFCfieldId]->getValue())."'
				AND c.customerid != '".$GLOBALS['ISC_CLASS_CUSTOMER']->GetCustomerId()."'
				AND ffs.formfieldformid = '".FORMFIELDS_FORM_ACCOUNT."' AND ffs.formfieldfieldid = '".$RFCfieldId."'";
	
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			if($GLOBALS['ISC_CLASS_DB']->CountResult($result) > 0) {
				$msg = "El RFC que ingreso ya existe";
				$fields[$RFCfieldId]->addValidation('regex', $msg, null, '\n');
				return;
			}
		
			$regex = '';
			$msg = '';
	
			$regex = '^[a-z|A-z]{3,4}[0-9]{6}[a-z|A-Z]{2}[0-9|a|A]+$';
			$msg = 'El RFC no es valido.<br/>
			Las validaciones hechas son:<br/>
			 * Longitud de 12 caracteres alfanumericos para Personas Morales<br/>
			 * Longitud de 13 caracteres alfanumericos para Personas Fisicas<br/>
			 * Los  primeros 3 caracteres deben ser letras para Personas Morales<br/>
			 * Los  primeros 4 caracteres deben ser letras para Personas Fisicas<br/>
			 * Los siguientes 6 caracteres deben ser numeros<br/>
			 * El antepenúltimo y penúltimo caracter tienen que ser letras<br/>
			 * El último caracter tiene que ser número o la letra "A"';
			$fields[$RFCfieldId]->addValidation('regex', $msg, null, $regex);
		}
		$_GET['FormField'][FORMFIELDS_FORM_ACCOUNT][$RFCfieldId] = $fields[$RFCfieldId]->getValue(); 
	}
}

/*
 * Funcion para obtener la existencia de un producto con el SKU (para que funcione con el producto maestro o con una combinacion de variacion
 */
function getProductStockDetail($productCode) {
	if(!$productCode || $productCode == '') return array();
	$query = 'SELECT ii.Sucursal, ii.Existencia, s.Nombre, s.Direccion, s.Telefonos, s.eCommerceSincroniza
		FROM [|PREFIX|]intelisis_inv ii
		LEFT OUTER JOIN [|PREFIX|]intelisis_Sucursal s ON (ii.Sucursal=s.Sucursal)
		WHERE ii.SKU = "'.$productCode.'"';
	$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
	
	$return = array();
	
	while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)){
		// ToDo: El check $row['Existencia'] == 0 hacerlo opcional ("Mostrar existencias en cero")
		if($row['eCommerceSincroniza'] == 0 || $row['Existencia'] == 0) continue;
		$sucursal = ($row['Nombre'] != '') ? $row['Nombre'] : $row['Sucursal'];
		$existencia = $row['Existencia'];
		$contacto = (trim($row['Direccion']) == '' && trim($row['Telefonos']) == '') ? 'No se encontraron los detalles de contacto de esta Sucursal' : $row['Direccion'] . "<br/>tel:" .$row['Telefonos'];
		
		$return[$row['Sucursal']] = array(
			'Nombre' => $sucursal,
			'Existencia' => $existencia,
			'Contacto' => $contacto,
		);
	}
	
	return $return;
}

/*
 * REQ11552: NES - Funcion para calcular la fecha de entrega a partir de la fecha actual, con los dias de entrega y el periodo de la semana 
 * que se hacen entregas, saltandose dias festivos
 */

function getDeliveryDate($DiasEntrega, $PeriodoEntrega){
	$PeriodoEntrega = strtolower($PeriodoEntrega);
	if(!in_array($PeriodoEntrega, array('lun-dom', 'lun-sab', 'lun-vie'))) {
		logAddNotice('El rango enviado ('.$PeriodoEntrega.') no es permitido');
		return false;
	}
	
	$result = $GLOBALS['ISC_CLASS_DB']->Query('SELECT Fecha FROM [|PREFIX|]intelisis_festivedays WHERE EsLaborable = "0"');
	$diasfestivos = array();
	while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)){
		$diasfestivos[] = $row['Fecha'];
	}
	
	$i = 0;
	$manana=time();
	$dia=date('D', $manana);
	while($i < $DiasEntrega){
		$manana = $manana + 86400;
		$mananaDia = date('D', $manana);
		$mananaFecha = date('Y-m-d 00:00:00', $manana);

		if(in_array($mananaFecha, $diasfestivos)) continue;
		
		if($PeriodoEntrega == 'lun-vie'){
			if($mananaDia != 'Sat' && $mananaDia != 'Sun'){
				$i++;
				continue;
				}
		}
		if($PeriodoEntrega == 'lun-sab'){
			if($mananaDia != 'Sun'){
				$i++;
				continue;
				}
		}
		if($PeriodoEntrega == 'lun-dom'){
			//if($mananaDia != 'Sun'){
				$i++;
				continue;
			//	}
		}
	}
	return $manana;
}

function convertDatetimeFromSQLSRV($date){
	$spanishMonths = array(
		'Ene',
		'Abr',
		'Ago',
		'Dic',	
	);
	
	$englishMonths = array(
		'Jan',
		'Apr',
		'Aug',
		'Dec',	
	);
	$date = str_replace($spanishMonths, $englishMonths, $date);

	$newdate = DateTime::createFromFormat('M d Y h:iA', $date);
	return $newdate->format('Y-m-d H:i:00');
}

function armarListaAnexos($claveProd){
		
	$generalPath = getConfig('ShopPath').'/'.GetConfig('ImageDirectory').'/'.'uploaded_images'.'/'.'Anexos'.'/'.'Cuenta'.'/'.'Articulo'.'/';
	$query = 'SELECT Articulo FROM [|PREFIX|]intelisis_products WHERE productid = "'.$claveProd.'"';
	$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
	$claveIntelisisProd = $GLOBALS['ISC_CLASS_DB']->FetchOne($result);

	$query = 'SELECT * FROM [|PREFIX|]intelisis_anexocta WHERE Cuenta = "'.$claveIntelisisProd.'"';
	$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
	while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)){
		$anexosArray[] = $row;
	}
	
	if(isset($anexosArray)){
		$GLOBALS['AnexosList'] = '<ul>';
		
		foreach($anexosArray as $key => $linea){
			$link = $generalPath.$linea['Cuenta'].'/'.$linea['Direccion'];
			$GLOBALS['AnexosList'] .= '<li><a href="'.$link.'" >'.$linea['Nombre'].'</a></li>';
		}
			
		$GLOBALS['AnexosList'] .= '</ul>';
	}else{
		$GLOBALS['AnexosList'] = '<p>Este articulo no cuenta con archivos anexos</p>';
	}
}