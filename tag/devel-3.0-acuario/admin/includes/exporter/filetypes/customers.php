<?php
require_once dirname(__FILE__) . "/../class.exportfiletype.php";

class ISC_ADMIN_EXPORTFILETYPE_CUSTOMERS extends ISC_ADMIN_EXPORTFILETYPE
{
	protected $type_name = "customers";
	protected $type_icon = "customer.gif";
	protected $type_idfield = "customerid";
	protected $type_viewlink = "index.php?ToDo=viewCustomers";

	protected $handleaddresses = false;
	protected $handleaddressformfields = false;
	protected $handleformfields = false;

	public function GetFields()
	{
		 $fields = array(
			"customerID"			=> array("dbfield" => "customerid"),
			"customerName"			=> array("dbfield" => "CONCAT(custconfirstname, ' ', custconlastname)"),
			"customerFirstName"		=> array("dbfield" => "custconfirstname"),
			"customerLastName"		=> array("dbfield" => "custconlastname"),
			"customerCompany"		=> array("dbfield" => "custconcompany"),
			"customerEmail"			=> array("dbfield" => "custconemail"),
			"customerPhone"			=> array("dbfield" => "custconphone"),
			"customerNotes"			=> array("dbfield" => "custnotes"),
			"customerCredit"		=> array("dbfield" => "custstorecredit", "format" => "number"),
			"customerGroup"			=> array("dbfield" => "cg.groupname"),
			"customerDateJoined"		=> array("dbfield" => "custdatejoined", "format" => "date"),
			"customerAddresses"		=> array(
											"fields" => array(
															"addressID"				=> array(),
															"addressName"			=> array(),
															"addressFirstName"		=> array(),
															"addressLastName"		=> array(),
															"addressCompany"		=> array(),
															"addressLine1"			=> array(),
															"addressLine2"			=> array(),
															"addressSuburb"			=> array(),
															"addressState"			=> array(),
															"addressStateAbbrv"		=> array(),
															"addressPostcode"		=> array(),
															"addressCountry"		=> array(),
															"addressBuilding"		=> array(),
															"addressPhone"			=> array(),
															"addressFormFields"		=> array()
														)
											),
			"customerFormFields"	=> array()

		);

		return $fields;
	}

	protected function PostFieldLoad($where = '')
	{
		$fields = $this->fields;

		if ($this->templateid) {
			// is the categories fields used?
			if ($fields['customerAddresses']['used']) {
				// are any sub-fields ticked? let parent handle row output if none are
				$addrfieldsused = false;
				foreach ($fields['customerAddresses']['fields'] as $id => $field) {
					if ($field['used']) {
						$addrfieldsused = true;
						break;
					}
				}

				$this->handleaddresses = $addrfieldsused;

				if ($this->handleaddresses) {
					// address form fields used?
					if ($fields['customerAddresses']['fields']['addressFormFields']['used']) {
						$addressFields = $this->InsertFormFields(FORMFIELDS_FORM_ADDRESS, "addressFormFields", $this->fields['customerAddresses']['fields']);

						// check if form fields were inserted, if they were then addressFormFields won't exist anymore
						if (isset($addressFields['addressFormFields'])) {
							// no form fields, disable the column
							$addressFields['addressFormFields']['used'] = false;
						}
						else {
							$this->handleaddressformfields = true;
						}

						$this->fields['customerAddresses']['fields'] = $addressFields;
					}

					$query = "
						SELECT
							COUNT(shipid) AS maxaddresses
						FROM
							[|PREFIX|]customers c
							LEFT JOIN [|PREFIX|]customer_groups cg ON c.custgroupid = cg.customergroupid
							LEFT JOIN [|PREFIX|]shipping_addresses sa ON sa.shipcustomerid = c.customerid
						" . $where . "
						GROUP BY
							c.customerid
						ORDER BY
							maxaddresses DESC
						LIMIT 1
					";

					$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
					$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

					$this->fields['customerAddresses']['max_items'] = $row['maxaddresses'];
				}
			}

			// are form fields used?
			if ($fields['customerFormFields']['used']) {
				// the export fields to insert
				$this->fields = $this->InsertFormFields(FORMFIELDS_FORM_ACCOUNT, "customerFormFields", $this->fields);

				// check if form fields were inserted, if they were then customerFormFields won't exist anymore
				if (isset($this->fields['customerFormFields'])) {
					// no form fields, disable the column
					$this->fields['customerFormFields']['used'] = false;
				}
				else {
					$this->handleformfields = true;
				}
			}
		}
	}

	protected function GetQuery($columns, $where, $having)
	{
		if ($where) {
			$where = " WHERE " . $where;
		}

		if ($having) {
			$having = "HAVING " . $having;
		}

		$query = "
			SELECT
				" . $columns . ",
				customerID AS custID,
				custformsessionid,
				COUNT(o.orderid) AS numorders
			FROM
				[|PREFIX|]customers c
				LEFT JOIN [|PREFIX|]customer_groups cg ON cg.customergroupid = c.custgroupid
				LEFT JOIN [|PREFIX|]shipping_addresses sa ON sa.shipcustomerid = c.customerid
				LEFT JOIN [|PREFIX|]orders o ON ordcustid = customerid AND o.ordstatus != 0 AND o.deleted = 0
			" . $where . "
			GROUP BY
				customerid
			" . $having;

		return $query;
	}

	protected function HandleRow($row)
	{
		if ($this->handleaddresses) {
			// get the addresses for the customer
			$query = "
				SELECT
					shipid AS addressID,
					CONCAT(shipfirstname, ' ', shiplastname) AS addressName,
					shipfirstname AS addressFirstName,
					shiplastname AS addressLastName,
					shipcompany AS addressCompany,
					shipaddress1 AS addressLine1,
					shipaddress2 AS addressLine2,
					shipcity AS addressSuburb,
					shipstate AS addressState,
					stateabbrv AS addressStateAbbrv,
					shipzip AS addressPostcode,
					shipcountry AS addressCountry,
					shipphone AS addressPhone,
					shipdestination AS addressBuilding,
					shipformsessionid
				FROM
					[|PREFIX|]shipping_addresses sa
					LEFT JOIN [|PREFIX|]country_states cs ON cs.stateid = sa.shipstateid
				WHERE
					shipcustomerid = '" . $row['custID'] . "'";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

			if ($GLOBALS['ISC_CLASS_DB']->CountResult($result)) {
				$addresses = array();

				while ($address = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
					$newAddress = $this->CreateSubItemArray($address, $this->fields['customerAddresses']['fields']);

					// handle address form fields?
					if ($this->handleaddressformfields) {
						$this->LoadFormFieldData(FORMFIELDS_FORM_ADDRESS, 'addressFormFields', $newAddress, $address['shipformsessionid']);
					}

					//$new_row = $row;
					$addresses[] = $newAddress;
				}

				$row["customerAddresses"] = $addresses;
			}
			else {
				$row["customerAddresses"] = array();
			}
		}

		if ($this->handleformfields) {
			// get the form fields with data for this customer
			$this->LoadFormFieldData(FORMFIELDS_FORM_ACCOUNT, "customerFormFields", $row, $row['custformsessionid']);
		}

		return $row;
	}

	public function GetListColumns()
	{
		$columns = array(
			"ID",
			"Name",
			"Email",
			"Phone",
			"Group",
			"Date Joined",
		);

		return $columns;
	}

	public function GetListSortLinks()
	{
		$sortLinks = array(
			"ID" => "customerid",
			"Name" => "custname",
			"Email" => "custconemail",
			"Phone" => "custconphone",
			"Group" => "groupname",
			"Date" => "custdatejoined"
		);

		return $sortLinks;
	}

	public function GetListQuery($where, $having, $sortField, $sortOrder)
	{
		if ($where) {
			$where = "WHERE " . $where;
		}

		if ($having) {
			$having = "HAVING " . $having;
		}

		$query = "
				SELECT
					customerid,
					CONCAT(custconfirstname, ' ', custconlastname) AS custname,
					custconemail,
					custconphone,
					cg.groupname,
					custdatejoined,
					COUNT(o.orderid) AS numorders
				FROM
					[|PREFIX|]customers c
					LEFT JOIN [|PREFIX|]customer_groups cg ON cg.customergroupid = c.custgroupid
					LEFT JOIN [|PREFIX|]shipping_addresses sa ON sa.shipcustomerid = c.customerid
					LEFT JOIN [|PREFIX|]orders o ON ordcustid = customerid AND o.ordstatus != 0 AND o.deleted = 0
				" . $where . "
				GROUP BY
					customerid
				" . $having . "
				ORDER BY "
					. $sortField . " " . $sortOrder;

		return $query;
	}

	public function GetListCountQuery($where, $having)
	{
		if ($where) {
			$where = "WHERE " . $where;
		}

		if ($having) {
			$having = "HAVING " . $having;
		}

		$query = "
			SELECT
				COUNT(*) AS ListCount
			FROM
				(
					SELECT
						customerid,
						COUNT(orderid) AS numorders
					FROM
						[|PREFIX|]customers c
						LEFT JOIN [|PREFIX|]customer_groups cg ON cg.customergroupid = c.custgroupid
						LEFT JOIN [|PREFIX|]shipping_addresses sa ON sa.shipcustomerid = c.customerid
						LEFT JOIN [|PREFIX|]orders ON ordcustid = customerid AND ordstatus != 0 AND deleted = 0
					" . $where . "
					GROUP BY
						customerid
					" . $having . "
				) AS customercount
		";

		return $query;
	}

	public function GetListRow($row)
	{
		$new_row['ID'] = $row['customerid'];
		$new_row['Name'] = isc_html_escape($row['custname']);
		$new_row['Email'] = $row['custconemail'];
		$new_row['Phone'] = $row['custconphone'];
		$new_row['Group'] = $row['groupname'];
		$new_row['Date Joined'] = isc_date(GetConfig('ExportDateFormat'), $row['custdatejoined']);

		return $new_row;
	}

	public function BuildWhereFromFields($search_fields)
	{
		$class = GetClass('ISC_ADMIN_CUSTOMERS');

		$res = $class->BuildWhereFromVars($search_fields);
		$where = $res['query'];
		// strip AND from beginning and end of statement
		$where = preg_replace("/^( ?AND )?|( AND ?)?$/i", "", $where);

		$having = $res['having'];
		$having = preg_replace("/^( ?AND )?|( AND ?)?$/i", "", $having);

		$ret = array(
			'where' => $where,
			'having' => $having,
		);

		return $ret;
	}

	public function HasPermission()
	{
		return $GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Export_Customers);
	}
}