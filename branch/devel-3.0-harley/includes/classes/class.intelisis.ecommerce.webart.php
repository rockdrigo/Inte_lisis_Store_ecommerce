<?php

class ISC_INTELISIS_ECOMMERCE_WEBART extends ISC_INTELISIS_ECOMMERCE
{
	public function ProcessData() {
		if($this->getXMLdom())
		{
			//printe($this->getAttribute('Estatus').": ".$this->getAttribute('Cliente'));
			switch ($this->getAttribute('Estatus')) {
				case 'ALTA':
					return $this->createProduct();
				break;
				case 'CAMBIO':
					return $this->updateProduct();
				break;
				case 'BAJA':
					return $this->deleteProduct();
				break;
				default:
					logAdd(LOG_SEVERITY_ERROR, 'Estatus de archivo no valido. '.get_class($this).'. Estatus: "'.$this->getAttribute('Estatus').'"', 'Archivo: "'.$this->getXMLfilename().'"');
					return false;
				break;
			}
		}
		else
		{
			logAdd(LOG_SEVERITY_WARNING, 'Se trato de procesar un objeto '.get_class($this).' sin XML DOM especificado', 'Archivo: "'.$this->getXMLfilename().'"');
		}
	}
	
	private function getProductId() {
		$query = "SELECT productid FROM [|PREFIX|]intelisis_products WHERE ArticuloID = '".$this->getAttribute('IDArticulo')."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		
		return $row['productid'] ? $row['productid'] : false;  
	}
	
	private function getCategoryIds() {
		$categorias = explode(',', $this->getData('CategoriaIDS'));
		
		$categoryIds = array();
		foreach($categorias as $categoriaID)
		{
			$query = "SELECT categoryid FROM [|PREFIX|]intelisis_categories WHERE IDCategoria = '".$categoriaID."'";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
	
			if($row['categoryid'])
			{
				 $categoryIds[] = $row['categoryid'];
			}
		}
		return $categoryIds;
	}
	
	private function getBrandId() {
		$brandId = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT brandid FROM [|PREFIX|]intelisis_brands WHERE IDMarca = "'.$this->getData('MarcaID').'"', 'brandid');
		
		return $brandId ? $brandId : 0; 
	}

	private function createProduct() {
		$productId = $this->getProductId();
		if($productId != ''){
			return $this->updateProduct();
			/*
			logAdd(LOG_SEVERITY_WARNING, 'El Articulo Web"'.$this->getData('Nombre').'" ya esta creado con el ID "'.$this->getAttribute('IDArticulo').'".<br />Archivo: "'.$this->getXMLfilename().'"');
			return false;
			*/
		}
		
		$query = sprintf("SELECT * FROM [|PREFIX|]products where prodname = '".$GLOBALS['ISC_CLASS_DB']->Quote($this->getData('Nombre'))."'");
		$productId = $GLOBALS["ISC_CLASS_DB"]->FetchOne($query, 'productid');
		if($productId != ''){
			$GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_products', array(
				'ArticuloID'=>$this->getAttribute('IDArticulo'),
				'Articulo' => $this->getData('Articulo'),
				'productid'=>$productId,
				'Situacion'=>$this->getData('Situacion')
			));
			return $this->updateProduct();
		}
		
		/*
		 * // REQ11421 - NES: Quito esto porque estas banderas fueron rediseñadas. DESCONINUADO ya no debe de poner el texto en el Precio
		// REQ10378
		if($this->getData('Descontinuado', '0') == '1') {
			$prodallowpurchases =  0;
			$prodhideprice = 1;
		}
		 else {
		 	$prodallowpurchases = 1;
		 	$prodhideprice = 0;
		 }

		 $prodallowpurchases = ($this->getData('PermiteCompra', '1') == '0') ? 0 : 1;
		 $prodhideprice = ($this->getData('OcultarPrecio', '0') == '0') ? 0 : 1;
		 */
		
		 if($this->getData('Descontinuado', '0') == '1' || $this->getData('PermiteCompra', '1') == '0') {
		 	$prodallowpurchases =  0;
		 }
		 else {
		 	$prodallowpurchases = 1;
		 }
		 
		 $prodhideprice = ($this->getData('OcultarPrecio', '0') == '0') ? 0 : 1;
		 
		 $variationid = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT variationid FROM [|PREFIX|]intelisis_variations WHERE VariacionID = "'.$this->getData('VariacionID').'"', 'variationid');
		 if($variationid == 0 || $variationid == '') {
		 	logAdd(LOG_SEVERITY_WARNING, 'Se intento crear el articulo "'.$this->getData('Nombre').'" con una variacionID "'.$this->getData('VariacionID').'" no valida');
		 }
		 
		$productData = array(
				"prodname" => $this->getData('Nombre'),
				"prodtype" => !$this->getData('EsDigital'),
				"prodcode" => $this->getData('SKU'),
				"prodfile" => $this->getData('Archivo'),
				"proddesc" => html_entity_decode(htmlspecialchars_decode(($this->getData('DescripcionHTML')))),
				"prodsearchkeywords" => $this->getData('PalabrasBusqueda'),
				"prodavailability" => $this->getData('Disponibilidad'),
				"prodprice" => DefaultPriceFormat($this->getData('Precio')),
				"prodcostprice" => DefaultPriceFormat($this->getData('Costo')),
				"prodretailprice" => DefaultPriceFormat($this->getData('PrecioMenudeo')),
				"prodsaleprice" => DefaultPriceFormat($this->getData('PrecioOferta')),
				"prodcalculatedprice" => DefaultPriceFormat($this->getData('PrecioCImpuesto')!='' ? $this->getData('PrecioCImpuesto') : $this->getData('Precio')),
				"prodsortorder" => $this->getData('Orden'),
				"prodvisible" => $this->getData('Visible'),
				"prodfeatured" => $this->getData('Destacado'),
				"prodvendorfeatured" => $this->getData('DestacadoProv'),
				"prodrelatedproducts" => $this->getData('ArtRelacionados'),
				"prodcurrentinv" => '',
				"prodlowinv" => '',
				"prodoptionsrequired" => $this->getData('OpcionesRequeridas', 1),
				"prodwarranty" => $this->getData('Garantia'),
				"prodweight" => $this->getData('Peso'),
				"prodwidth" => $this->getData('Ancho'),
				"prodheight" => $this->getData('Alto'),
				"proddepth" => $this->getData('Largo'),
				"prodfixedshippingcost" => $this->getData('CostoEnvioFijo'),
				"prodfreeshipping" => $this->getData('EnvioGratis'),
				"prodinvtrack" => '',
				/*"prodratingtotal" => '',
				"prodnumratings" => '',
				"prodnumsold" => '',*/
				"proddateadded" => $this->getData('FechaAlta'),
				"prodbrandid" => $this->getBrandId(),
				//"prodnumviews" => '',
				"prodpagetitle" => $this->getData('TituloPagina'),
				"prodmetakeywords" => $this->getData('MetaKeyWords'),
				"prodmetadesc" => $this->getData('Metadesc'),
				"prodlayoutfile" => $this->getData('Layout'),
				"prodvariationid" => $variationid,
				//NES - REQ10156
				"prodallowpurchases" => $prodallowpurchases,
				"prodhideprice" => $prodhideprice,
				"prodcallforpricinglabel" => $this->getData('TelefonoPrecios'),
				"prodcatids" => $this->getData('CategoriaIDS'), //calcular
				"prodlastmodified" => $this->getData('UltimoCambio'),
				"prodvendorid" => '',
				"prodhastags" => '',
				"prodwrapoptions" => '',
				"prodconfigfields" => '', //calcular
				"prodeventdaterequired" => '',
				"prodeventdatefieldname" => '',
				"prodeventdatelimited" => '',
				"prodeventdatelimitedtype" => '',
				"prodeventdatelimitedstartdate" => '',
				"prodeventdatelimitedenddate" => '',
				"prodmyobasset" => 'New',
				"prodmyobincome" => '',
				"prodmyobexpense" => '',
				"prodpeachtreegl" => '',
				"prodcondition" => '',
				"prodshowcondition" => '',
				"product_enable_optimizer" => '',
				"prodpreorder" => '',
				"prodreleasedate" => $this->getData('FechaLanzamiento'),
				"prodreleasedateremove" => '',
				"prodpreordermessage" => '',
				"prodminqty" => '',
				"prodmaxqty" => '',
				'tax_class_id' => '',
				"opengraph_type" => '',
				"opengraph_use_product_name" => '',
				"opengraph_title" => '',
				"opengraph_use_meta_description" => '',
				"opengraph_description" => '',
				"opengraph_use_image" => '',
				"upc" => $this->getData('UPC'),
				"disable_google_checkout" => $this->getData('DesHabilitarGoogle'),
				"last_import" => '',
		);
		
		$entity = new ISC_ENTITY_PRODUCT();
		$productId = $entity->add($productData);
		
		$prodsearch = array(
				0 => $this->getData('Articulo'),
		);
		if(trim($this->getData('PalabrasBusqueda')) != '')
			$prodsearch[1] = $this->getData('PalabrasBusqueda');
		
		$searchData = array(
			"productid" => $productId,
			"prodname" => $this->getData('Nombre'),
			"prodcode" => $this->getData('SKU'),
			"proddesc" => stripHTMLForSearchTable($this->getData('DescripcionHTML')),
			"prodsearchkeywords" => implode(', ', $prodsearch),
		);
		
		$GLOBALS['ISC_CLASS_DB']->InsertQuery("product_search", $searchData);
		
		if($productId){
			if(!($rtn_insert = $GLOBALS['ISC_CLASS_DB']->InsertQuery('intelisis_products', array(
				'ArticuloID'=>$this->getAttribute('IDArticulo'),
				'Articulo' => $this->getData('Articulo'),
				'productid'=>$productId,
				'Situacion'=>$this->getData('Situacion'),
			), true)))
			{
				logAdd(LOG_SEVERITY_ERROR, 'Error al relacionar al producto ID '.$productId.' con el Articulo Web "'.$this->getAttribute('Nombre').'".<br/>Archivo: '.$this->getXMLfilename());
				return false;
			}
			else {
				logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz con Intelisis creo el Producto "'.$this->getData('Nombre').'" con ID "'.$productId);
				return true;
			}
		}
		else {
			logAdd(LOG_SEVERITY_ERROR, 'Error al crear el Articulo Web "'.$this->getAttribute('Nombre').'".<br/>Archivo: '.$this->getXMLfilename());
			return false;
		}
	}
	
	private function updateProduct() {
		$productId = $this->getProductId();
		if($productId == ''){
			return $this->createProduct();
			/*
			logAdd(LOG_SEVERITY_WARNING, 'No se puede encontrar el id del  Articulo Web "'.$this->getData('Nombre').'".<br />Archivo: "'.$this->getXMLfilename().'"');
			return false;
			*/
		}
		
		$query = sprintf("SELECT * FROM [|PREFIX|]products where prodname = '".$GLOBALS['ISC_CLASS_DB']->Quote($this->getData('Nombre'))."' AND productid != '".$this->getProductId()."'");
		$prodId = $GLOBALS["ISC_CLASS_DB"]->FetchOne($query, 'productid');
		if($prodId != ''){
			//logAddError('Ya existe un producto con el nombre '.$this->getData('Nombre'));
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery('intelisis_products', array('productid' => $prodId), 'ArticuloID="'.$this->getAttribute('IDArticulo').'"');
		}
		
		
		$categoryIds = implode(',', $this->getCategoryIds());
		
		/*
		 * // REQ11421 - NES: Quito esto porque estas banderas fueron rediseñadas. DESCONINUADO ya no debe de poner el texto en el Precio
		// REQ10378
		if($this->getData('Descontinuado', '0') == '1') {
			$prodallowpurchases =  0;
			$prodhideprice = 1;
		}
		 else {
		 	$prodallowpurchases = 1;
		 	$prodhideprice = 0;
		 }

		 $prodallowpurchases = ($this->getData('PermiteCompra', '1') == '0') ? 0 : 1;
		 */
		
		if($this->getData('Descontinuado', '0') == '1' || $this->getData('PermiteCompra', '1') == '0') {
			$prodallowpurchases =  0;
		}
		else {
			$prodallowpurchases = 1;
		}
		$prodhideprice = ($this->getData('OcultarPrecio', '0') == '0') ? 0 : 1;
		
		$variationid = $GLOBALS['ISC_CLASS_DB']->FetchOne('SELECT variationid FROM [|PREFIX|]intelisis_variations WHERE VariacionID = "'.$this->getData('VariacionID').'"', 'variationid');
		 if($variationid == 0 || $variationid == '') {
		 	logAdd(LOG_SEVERITY_WARNING, 'Se intento editar el articulo "'.$this->getData('Nombre').'" con una variacionID "'.$this->getData('VariacionID').'" no valida');
		 }

		$productData = array(
				"prodname" => $this->getData('Nombre'),
				"prodtype" => !$this->getData('EsDigital'),
				"prodcode" => $this->getData('SKU'),
				"prodfile" => $this->getData('Archivo'),
				"proddesc" => html_entity_decode(htmlspecialchars_decode(($this->getData('DescripcionHTML')))),
				"prodsearchkeywords" => $this->getData('PalabrasBusqueda'),
				"prodavailability" => $this->getData('Disponibilidad'),
				"prodprice" => DefaultPriceFormat($this->getData('Precio')),
				"prodcostprice" => DefaultPriceFormat($this->getData('Costo')),
				"prodretailprice" => DefaultPriceFormat($this->getData('PrecioMenudeo')),
				"prodsaleprice" => DefaultPriceFormat($this->getData('PrecioOferta')),
				"prodcalculatedprice" => DefaultPriceFormat($this->getData('PrecioCImpuesto')!='' ? $this->getData('PrecioCImpuesto') : $this->getData('Precio')),
				"prodsortorder" => $this->getData('Orden'),
				"prodvisible" => $this->getData('Visible'),
				"prodfeatured" => $this->getData('Destacado'),
				"prodvendorfeatured" => $this->getData('DestacadoProv'),
				"prodrelatedproducts" => $this->getData('ArtRelacionados'),
				/*"prodcurrentinv" => '',
				"prodlowinv" => '',*/
				"prodoptionsrequired" => $this->getData('OpcionesRequeridas', 1),
				"prodwarranty" => $this->getData('Garantia'),
				"prodweight" => FormatNumber($this->getData('Peso')),
				"prodwidth" => FormatNumber($this->getData('Ancho')),
				"prodheight" => FormatNumber($this->getData('Alto')),
				"proddepth" => FormatNumber($this->getData('Largo')),
				"prodfixedshippingcost" => FormatNumber($this->getData('CostoEnvioFijo')),
				"prodfreeshipping" => FormatNumber($this->getData('EnvioGratis')),
				/*"prodinvtrack" => '',
				"prodratingtotal" => '',
				"prodnumratings" => '',
				"prodnumsold" => '',*/
				/*"proddateadded" => time(),*/
				"prodbrandid" => $this->getBrandId(),
				//"prodnumviews" => '',
				"prodpagetitle" => $this->getData('TituloPagina'),
				"prodmetakeywords" => $this->getData('MetaKeyWords'),
				"prodmetadesc" => $this->getData('Metadesc'),
				"prodlayoutfile" => $this->getData('Layout'),
				"prodvariationid" => $variationid,
				//NES - REQ10156
				"prodallowpurchases" => $prodallowpurchases,
				"prodhideprice" => $prodhideprice,
				"prodcallforpricinglabel" => $this->getData('TelefonoPrecios'),
				"prodcatids" => $categoryIds, //calcular
				"prodlastmodified" => time(),
				/*"prodvendorid" => '',
				"prodhastags" => '',
				"prodwrapoptions" => '',
				"prodconfigfields" => '', //calcular
				"prodeventdaterequired" => '',
				"prodeventdatefieldname" => '',
				"prodeventdatelimited" => '',
				"prodeventdatelimitedtype" => '',
				"prodeventdatelimitedstartdate" => '',
				"prodeventdatelimitedenddate" => '',
				"prodmyobasset" => 'New',
				"prodmyobincome" => '',
				"prodmyobexpense" => '',
				"prodpeachtreegl" => '',
				"prodcondition" => '',
				"prodshowcondition" => '',
				"product_enable_optimizer" => '',
				"prodpreorder" => '',*/
				"prodreleasedate" => FormatNumber($this->getData('FechaLanzamiento')), //convertir
				/*"prodreleasedateremove" => '',
				"prodpreordermessage" => '',
				"prodminqty" => '',
				"prodmaxqty" => '',
				'tax_class_id' => '',
				"opengraph_type" => '',
				"opengraph_use_product_name" => '',
				"opengraph_title" => '',
				"opengraph_use_meta_description" => '',
				"opengraph_description" => '',
				"opengraph_use_image" => '',*/
				"upc" => $this->getData('UPC'),
				"disable_google_checkout" => $this->getData('DesHabilitarGoogle'),
				//"last_import" => '',
		);
		
		$GLOBALS['ISC_CLASS_DB']->DeleteQuery('categoryassociations', 'WHERE productid="'.$this->getProductId().'"');
		
		if($this->getCategoryIds()) {
			foreach ($this->getCategoryIds() as $cat) {
				$newAssociation = array(
					"productid" => $this->getProductId(),
					"categoryid" => $cat
				);
				$GLOBALS['ISC_CLASS_DB']->InsertQuery("categoryassociations", $newAssociation);
			}
		}
		
		$prodsearch = array(
				0 => $this->getData('Articulo'),
		);
		if(trim($this->getData('PalabrasBusqueda')) != '')
			$prodsearch[1] = $this->getData('PalabrasBusqueda');
		
		$searchData = array(
			"prodname" => $this->getData('Nombre'),
			"prodcode" => $this->getData('SKU'),
			"proddesc" => stripHTMLForSearchTable($this->getData('DescripcionHTML')),
			"prodsearchkeywords" => implode(', ', $prodsearch),
		);
		
		$GLOBALS['ISC_CLASS_DB']->UpdateQuery("product_search", $searchData, "productid='".$GLOBALS['ISC_CLASS_DB']->Quote($productId)."'");
		
		if(!$GLOBALS['ISC_CLASS_DB']->UpdateQuery('products', $productData, 'productid = "'.$productId.'"'))
		{
			logAdd(LOG_SEVERITY_ERROR, 'Error al intentar editar el producto "'.$this->getData('Nombre').'" con ID "'.$productId.'".<br>Archivo: '.$this->getXMLfilename().'. '.$GLOBALS["ISC_CLASS_DB"]->Error());
			return false;
		}
		else
		{
			logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz Intelisis edito el producto "'.$this->getData('Nombre').'" con ID "'.$productId.'"');
			return true;
		}
	}
	
	public function DeleteVariationImagesForRow($row)
	{
		$GLOBALS["ISC_CLASS_LOG"]->LogSystemDebug('general', 'ISC_ADMIN_PRODUCT::DeleteVariationImagesForRow is running ', var_export($row, true) . '<br/><br/>' . trace());

		if (!empty($row['vcimage'])) {
			@unlink(ISC_BASE_PATH . '/' . GetConfig('ImageDirectory') . '/' . $row['vcimage']);
		}

		if (!empty($row['vcimagezoom'])) {
			@unlink(ISC_BASE_PATH . '/' . GetConfig('ImageDirectory') . '/' . $row['vcimagezoom']);
		}

		if (!empty($row['vcimagestd'])) {
			@unlink(ISC_BASE_PATH . '/' . GetConfig('ImageDirectory') . '/' . $row['vcimagestd']);
		}

		if (!empty($row['vcimagethumb'])) {
			@unlink(ISC_BASE_PATH . '/' . GetConfig('ImageDirectory') . '/' . $row['vcimagethumb']);
		}
	}
	
	private function _DeleteVariationCombinationsForProduct($ProductIds, $ReturnQueries=false)
	{
		$queries = array();
	
		// Delete the product combination images from the file system
		$query = "
		SELECT
		vcimage,
		vcimagezoom,
		vcimagestd,
		vcimagethumb
		FROM
		[|PREFIX|]product_variation_combinations
		WHERE
		vcproductid IN ('" . $ProductIds . "')";
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		while($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
			$this->DeleteVariationImagesForRow($row);
		}
	
		// Now delete the entries in the product_variation_combinations table
		$queries[] = "DELETE FROM [|PREFIX|]product_variation_combinations WHERE vcproductid IN ('" . $ProductIds . "')";
	
		if($ReturnQueries) {
			return $queries;
		}
		else {
			$GLOBALS["ISC_CLASS_DB"]->Query($queries[0]);
		}
	}
	
	private function deleteProduct() {
		$prodids = $this->getProductId();
		if(!$prodids)
		{
			logAdd(LOG_SEVERITY_WARNING, 'Se intento eliminar un producto con un ID "'.$prodids.'" invalido<br/Archivo: '.$this->getXMLfilename());
			return true;
		}
		
		//printe($this->getXMLfilename().". Eliminando producto ".$this->getAttribute('Articulo').". ".$this->getData('Descripcion1'));
		$queries[] = sprintf("delete from [|PREFIX|]categoryassociations where productid in ('%s')", $prodids);
		$queries[] = sprintf("delete from [|PREFIX|]product_customfields where fieldprodid in ('%s')", $prodids);
		$queries[] = sprintf("delete from [|PREFIX|]reviews where revproductid in ('%s')", $prodids);
		$queries[] = sprintf("delete from [|PREFIX|]product_search where productid in ('%s')", $prodids);
		$queries[] = sprintf("delete from [|PREFIX|]product_words where productid in ('%s')", $prodids);
		$queries[] = sprintf("delete from [|PREFIX|]product_downloads where productid in ('%s')", $prodids);
		$queries[] = sprintf("delete from [|PREFIX|]wishlist_items where productid in ('%s')", $prodids);
		$queries[] = sprintf("delete from [|PREFIX|]product_configurable_fields where fieldprodid in ('%s')", $prodids);
		$queries[] = sprintf("delete from [|PREFIX|]customer_group_discounts where discounttype='PRODUCT' AND catorprodid IN ('%s')", $prodids);
		$queries[] = sprintf("delete from [|PREFIX|]product_videos where video_product_id IN ('%s')", $prodids);
		$queries[] = sprintf("delete from [|PREFIX|]product_tagassociations where productid IN ('%s')", $prodids);
		
		// Delete the product downloads from the file system
		$query = sprintf("select downfile from [|PREFIX|]product_downloads where productid in ('%s')", $prodids);
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			@unlink(APP_ROOT."/../".GetConfig('DownloadDirectory')."/".$row['downfile']);
		}
		
		$vc_queries = $this->_DeleteVariationCombinationsForProduct($prodids, true);
		$queries = array_merge($vc_queries, $queries);
		
		// Delete the product record here so we can keep a record of what was deleted for the accounting modules
		$entity = new ISC_ENTITY_PRODUCT();
		$entity->multiDelete($prodids);
		
		foreach ($queries as $query) {
			$GLOBALS["ISC_CLASS_DB"]->Query($query);
		}
		$err = $GLOBALS["ISC_CLASS_DB"]->GetErrorMsg();
		
		if(!$err)
		{
			logAdd(LOG_SEVERITY_SUCCESS, 'Interfaz con Intelisis elimino el Producto ID: '.$prodids.' Articulo Web: '.$this->getAttribute('IDArticulo'));
			$GLOBALS["ISC_CLASS_DB"]->DeleteQuery('intelisis_products', 'WHERE productid = '.$prodids, 1);
			return true;
		}
		else {
			logAdd(LOG_SEVERITY_ERROR, 'Error al eliminar el Producto ID: '.$prodids.' Articulo Web: '.$this->getAttribute('IDArticulo').'<br/>Error SQL:'.$err);
			return false;
		}
	}
}
