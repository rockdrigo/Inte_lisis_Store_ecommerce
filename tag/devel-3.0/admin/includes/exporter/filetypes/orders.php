<?php

require_once (dirname(__FILE__) . '/../class.exportfiletype.php');

class ISC_ADMIN_EXPORTFILETYPE_ORDERS extends ISC_ADMIN_EXPORTFILETYPE
{
	private $product_fields;
	private $combined_fields;

	protected $type_name = "orders";
	protected $type_icon = "order.gif";
	protected $type_idfield = "orderid";
	protected $type_viewlink = "index.php?ToDo=viewOrders";

	protected $handleproducts = false;
	protected $handlebillformfields = false;
	protected $handleshipformfields = false;

	protected $glaccount = "";

	private $lastOrderId = 0;
	private $multiAddressIndex = 1;

	public function GetFields()
	{
		$fields = array(
			"orderID"			=> array("dbfield" => "orderid"),
			"orderStatus"		=> array("dbfield" => "s.statusdesc"),
			"orderDate"			=> array("dbfield" => "orddate", "format" => "date"),
			"orderSubtotalInc"	=> array("dbfield" => "subtotal_inc_tax", "format" => "number"),
			"orderSubtotalEx"	=> array("dbfield" => "subtotal_ex_tax", "format" => "number"),
			"orderTaxtotal"		=> array("dbfield" => "total_tax", "format" => "number"),
			"orderShipCostInc"	=> array("dbfield" => "os.cost_inc_tax", "format" => "number"),
			"orderShipCostEx"	=> array("dbfield" => "os.cost_ex_tax", "format" => "number"),
			"orderHandlingCostInc"	=> array("dbfield" => "os.handling_cost_inc_tax"),
			"orderHandlingCostEx"	=> array("dbfield" => "os.handling_cost_ex_tax"),
			"orderTotalAmountInc"	=> array("dbfield" => "total_inc_tax", "format" => "number"),
			"orderTotalAmountEx"	=> array("dbfield" => "total_ex_tax", "format" => "number"),
			"orderCustomerID"	=> array("dbfield" => "ordcustid"),
			"orderCustomerName" => array("dbfield" => "CONCAT(custconfirstname, ' ', custconlastname)"),
			"orderCustomerEmail"=> array("dbfield" => "custconemail"),
			"orderCustomerPhone"=> array("dbfield" => "custconphone"),
			"orderShipMethod"	=> array("dbfield" => "os.method"),
			"orderPayMethod"	=> array("dbfield" => "orderpaymentmethod"),
			"orderTotalQty"		=> array("dbfield" => "ordtotalqty"),
			"orderTotalShipped"	=> array("dbfield" => "ordtotalshipped"),
			"orderDateShipped"	=> array("dbfield" => "orddateshipped", "format" => "date"),
			"orderCurrency"		=> array("dbfield" => "c.currencycode"),
			"orderExchangeRate"	=> array("dbfield" => "ordcurrencyexchangerate"),
			"orderNotes"		=> array("dbfield" => "ordnotes"),
			"orderCustMessage"	=> array("dbfield" => "ordcustmessage"),
			"billName"			=> array("dbfield" => "CONCAT(ordbillfirstname, ' ', ordbilllastname)"),
			"billFirstName"		=> array("dbfield" => "ordbillfirstname"),
			"billLastName"		=> array("dbfield" => "ordbilllastname"),
			"billCompany"		=> array("dbfield" => "ordbillcompany"),
			"billStreet1"		=> array("dbfield" => "ordbillstreet1"),
			"billStreet2"		=> array("dbfield" => "ordbillstreet2"),
			"billSuburb"		=> array("dbfield" => "ordbillsuburb"),
			"billState"			=> array("dbfield" => "ordbillstate"),
			"billStateAbbrv"	=> array("dbfield" => "billstate.stateabbrv"),
			"billZip"			=> array("dbfield" => "ordbillzip"),
			"billCountry"		=> array("dbfield" => "ordbillcountry"),
			"billSSC"			=> array("dbfield" => "CONCAT(ordbillsuburb, '  ', billstate.stateabbrv, '  ', ordbillzip)"),
			"billPhone"			=> array("dbfield" => "ordbillphone"),
			"billEmail"			=> array("dbfield" => "ordbillemail"),
			"billFormFields"	=> array(),
			"shipName"			=> array("dbfield" => "CONCAT(oa.first_name, ' ', oa.last_name)"),
			"shipFirstName"		=> array("dbfield" => "oa.first_name"),
			"shipLastName"		=> array("dbfield" => "oa.last_name"),
			"shipCompany"		=> array("dbfield" => "oa.company"),
			"shipStreet1"		=> array("dbfield" => "oa.address_1"),
			"shipStreet2"		=> array("dbfield" => "oa.address_2"),
			"shipSuburb"		=> array("dbfield" => "oa.city"),
			"shipState"			=> array("dbfield" => "oa.state"),
			"shipStateAbbrv"	=> array("dbfield" => "shipstate.stateabbrv"),
			"shipZip"			=> array("dbfield" => "oa.zip"),
			"shipCountry"		=> array("dbfield" => "oa.country"),
			"shipSSC"			=> array("dbfield" => "CONCAT(oa.city, '  ', shipstate.stateabbrv, '  ', oa.zip)"),
			"shipPhone"			=> array("dbfield" => "oa.phone"),
			"shipEmail"			=> array("dbfield" => "oa.email"),
			"shipFormFields"	=> array(),
			"orderProdDetails"	=> array(
										"help" => "This field displays either all the products from the order or the specified fields of an individiual product, depending on your chosen method above.",
										"fields" => array(
														"orderProdID"				=> array(),
														"orderProdQty"				=> array(),
														"orderProdSKU"				=> array(),
														"orderProdName"				=> array(),
														"orderProdVariationDetails" => array(),
														"orderProdPrice"			=> array("format" => "number"),
														"orderProdIndex"			=> array(),
														"orderProdWeight"			=> array(),
														"orderProdTotalPrice"		=> array("format" => "number"),
														"orderGLAccount"			=> array(),
														"orderPTTaxType" 			=> array()
												)
										),
			"orderProductCount" => array("dbfield" => "(SELECT COUNT(*) FROM [|PREFIX|]order_products WHERE orderorderid = o.orderid)"),
			"orderCombinedWeight" => array("dbfield" => "(SELECT SUM((ordprodweight*ordprodqty)) FROM [|PREFIX|]order_products WHERE orderorderid = o.orderid)"),
			"orderTodaysDate"	=> array("dbfield" => "UNIX_TIMESTAMP()", "format" => "date"),
			"orderAccountsReceivable"	=> array()
		);

		return $fields;
	}

	protected function PostFieldLoad()
	{
		$fields = $this->fields;

		if ($this->templateid) {
			$this->fields['orderAccountsReceivable']['dbfield'] = "'" . $GLOBALS['ISC_CLASS_DB']->Quote($this->template['peachtreereceivableaccount']) . "'";
			$this->glaccount = $this->template['peachtreeglaccount'];

			// determine if we need to do handling for products
			if ($fields['orderProdDetails']['used']) {
				$prodfieldsused = false;
				foreach ($fields['orderProdDetails']['fields'] as $id => $field) {
					if ($field['used']) {
						$prodfieldsused = true;
						break;
					}
				}

				$this->handleproducts = $prodfieldsused;

				// determine max amount of products in the orders
				if ($prodfieldsused) {

				}
			}

			if ($fields['billFormFields']['used']) {
				// the export fields to insert
				$this->fields = $this->InsertFormFields(FORMFIELDS_FORM_BILLING, "billFormFields", $this->fields, GetLang("billFormFieldsFormat"));

				// check if form fields were inserted, if they were then customerFormFields won't exist anymore
				if (isset($this->fields['billFormFields'])) {
					// no form fields, disable the column
					$this->fields['billFormFields']['used'] = false;
				}
				else {
					$this->handlebillformfields = true;
				}
			}

			if ($fields['shipFormFields']['used']) {
				// the export fields to insert
				$this->fields = $this->InsertFormFields(FORMFIELDS_FORM_SHIPPING, "shipFormFields", $this->fields, GetLang("shipFormFieldsFormat"));

				// check if form fields were inserted, if they were then customerFormFields won't exist anymore
				if (isset($this->fields['shipFormFields'])) {
					// no form fields, disable the column
					$this->fields['shipFormFields']['used'] = false;
				}
				else {
					$this->handleshipformfields = true;
				}
			}
		}
	}

	protected function GetQuery($columns, $where, $having)
	{
		if (!$where) {
			// for all non-incomplete orders
			$where = "o.ordstatus != 0 AND o.deleted = 0";
		}

		if ($where) {
			$where = " WHERE " . $where;
		}

		$query = "
			SELECT
				" . $columns . ",
				o.orderid AS ordid,
				o.ordformsessionid,
				o.shipping_address_count
			FROM
				[|PREFIX|]orders o
				LEFT JOIN [|PREFIX|]order_shipping os ON os.order_id = o.orderid
				LEFT JOIN [|PREFIX|]order_addresses oa ON oa.id = os.order_address_id
				LEFT JOIN [|PREFIX|]order_status s ON s.statusid = o.ordstatus
				LEFT JOIN [|PREFIX|]customers cu ON o.ordcustid = cu.customerid
				LEFT JOIN [|PREFIX|]currencies c ON c.currencyid = o.ordcurrencyid
				LEFT JOIN [|PREFIX|]country_states billstate ON billstate.stateid = o.ordbillstateid
				LEFT JOIN [|PREFIX|]country_states shipstate ON shipstate.stateid = oa.state_id
			" . $where . "
			ORDER BY
				o.orderid
		";

		return $query;
	}

	protected function HandleRow($row)
	{
		// for multi-address shipping orders, add an index onto the order ID
		if ($this->fields['orderID']['used'] && $row['shipping_address_count'] > 1) {
			if ($row['ordid'] != $this->lastOrderId) {
				$this->lastOrderId = $row['ordid'];
				$this->multiAddressIndex = 1;
			}
			else {
				$this->multiAddressIndex++;
			}

			$row['orderID'] = $row['ordid'] . '-' . $this->multiAddressIndex;
		}

		if ($this->handleproducts) {
			// determine if the product price to be exported to incl or excl tax.
			$productPriceField = 'price_ex_tax';
			if(GetConfig('taxDefaultTaxDisplayOrders') == TAX_PRICES_DISPLAY_INCLUSIVE) {
				$productPriceField = 'price_inc_tax';
			}
			// get the products for the order
			$query = "
				SELECT
					ordprodid AS orderProdID,
					ordprodsku AS orderProdSKU,
					ordprodname AS orderProdName,
					ordprodweight AS orderProdWeight,
					ordprodqty AS orderProdQty,
					$productPriceField AS orderProdPrice,
					($productPriceField * ordprodqty) AS orderProdTotalPrice,
					'' AS orderProdIndex,
					p.prodpeachtreegl AS orderGLAccount,
					'1' AS orderPTTaxType,
					ordprodoptions,
					ordprodvariationid
				FROM
					[|PREFIX|]order_products op
					LEFT JOIN [|PREFIX|]products p ON p.productid = op.ordprodid
				WHERE
					orderorderid = '" . $row['ordid'] . "'";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			$products = array();
			$x = 0;
			while ($product = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				$product['orderProdIndex'] = ++$x;

				if ($this->template['modifyforpeachtree']) {
					$product['orderProdTotalPrice'] *= -1;
				}

				if (is_null($product['orderGLAccount'])) {
					$product['orderGLAccount'] = "";
				}

				if ($product['orderGLAccount'] == "") {
					$product['orderGLAccount'] = $this->glaccount;
				}


				$prod_fields['orderProdVariationDetails'] = "";
				if ($product['ordprodvariationid']) {
					$options = @unserialize($product['ordprodoptions']);
					$option_str = "";
					foreach ($options as $optionName => $optionValue) {
						if ($option_str) {
							$option_str .= ", ";
						}
						$option_str .= $optionName . ": " . $optionValue;
					}

					$product['orderProdVariationDetails'] = $option_str;
				}

				//$new_row = $row;
				$products[] = $this->CreateSubItemArray($product, $this->fields['orderProdDetails']['fields']);
			}

			// add a shipping line for peachtree
			if ($this->template['modifyforpeachtree']) {
				$query = "SELECT shipping_cost_inc_tax FROM [|PREFIX|]orders WHERE orderid = " . $row['ordid'];
				$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
				$shipping = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

				$shipping_row = array(
					"orderProdID"			=> "",
					"orderProdQty"			=> "0",
					"orderProdSKU"			=> "",
					"orderProdName"			=> "Freight Amount",
					"orderProdPrice"		=> "0",
					"orderProdIndex"		=> "0",
					"orderProdWeight"		=> "0",
					"orderProdTotalPrice"	=> $shipping['shipping_cost_inc_tax'] * -1,
					"orderGLAccount"		=> $this->glaccount,
					"orderPTTaxType" 		=> "26"
				);

				$products[] = $this->CreateSubItemArray($shipping_row, $this->fields['orderProdDetails']['fields']);

				if ($this->fields['orderProductCount']['used']) {
					$row['orderProductCount']++;
				}
			}

			$row['orderProdDetails'] = $products;
		}

		if ($this->handlebillformfields) {
			// get the form fields with data for this customer
			$this->LoadFormFieldData(FORMFIELDS_FORM_BILLING, "billFormFields", $row, $row['ordformsessionid']);
		}

		if ($this->handleshipformfields) {
			// get the form fields with data for this customer
			$this->LoadFormFieldData(FORMFIELDS_FORM_SHIPPING, "shipFormFields", $row, $row['ordformsessionid']);
		}

		return $row;
	}

	public function GetListColumns()
	{
		$columns = array(
			"ID",
			"Customer",
			"Date",
			"Status",
			"Total"
		);

		return $columns;
	}

	public function GetListSortLinks()
	{
		$sortLinks = array(
			"ID" => "orderid",
			"Customer" => "custname",
			"Date" => "orddate",
			"Status" => "ordstatustext",
			"Total" => "total_inc_tax"
		);

		return $sortLinks;
	}

	public function GetListQuery($where, $having, $sortField, $sortOrder)
	{
		if (!$where) {
			// for all non-incomplete orders
			$where = "o.ordstatus != 0 AND o.deleted = 0";
		}

		$query = "
				SELECT
					o.orderid,
					o.orddate,
					o.total_inc_tax,
					o.orddefaultcurrencyid,
					o.ordstatus,
					s.statusdesc AS ordstatustext,
					CONCAT(custconfirstname, ' ', custconlastname) AS custname
				FROM
					[|PREFIX|]orders o
					LEFT JOIN [|PREFIX|]customers c ON (o.ordcustid = c.customerid)
					LEFT JOIN [|PREFIX|]order_status s ON (s.statusid = o.ordstatus)
				WHERE
					" . $where . "
				ORDER BY "
					. $sortField . " " . $sortOrder;

		return $query;
	}

	public function GetListCountQuery($where, $having)
	{
		if (!$where) {
			// for all non-incomplete orders
			$where = "o.ordstatus != 0 AND o.deleted = 0";
		}

		$query = "
				SELECT
					COUNT(*) AS ListCount
				FROM
					[|PREFIX|]orders o
					LEFT JOIN [|PREFIX|]customers c ON (o.ordcustid = c.customerid)
					LEFT JOIN [|PREFIX|]order_status s ON (s.statusid = o.ordstatus)
				WHERE
					" . $where;

		return $query;
	}


	public function GetListRow($row)
	{
		$new_row['ID'] = $row['orderid'];
		$new_row['Customer'] = $row['custname'];

		$new_row['Date'] = isc_date(GetConfig('DisplayDateFormat'), $row['orddate']);

		if ($row['ordstatus'] == 0) {
			$new_row['Status'] = GetLang('Incomplete');
		}
		else {
			$new_row['Status'] = $row['ordstatustext'];
		}

		$new_row['Total'] = FormatPriceInCurrency($row['total_inc_tax'], $row['orddefaultcurrencyid'], null, true);

		return $new_row;
	}

	public function BuildWhereFromFields($search_fields)
	{
		$class = GetClass('ISC_ADMIN_ORDERS');

		$res = $class->BuildWhereFromVars($search_fields);
		$where = $res['query'];
		// strip AND from beginning and end of statement
		$where = preg_replace("/^( ?AND )?|( AND ?)?$/i", "", $where);

		return $where;
	}

	public function HasPermission()
	{
		return $GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Export_Orders);
	}
}
