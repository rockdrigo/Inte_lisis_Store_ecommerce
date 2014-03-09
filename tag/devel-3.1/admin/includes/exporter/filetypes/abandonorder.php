<?php
require_once dirname(__FILE__) . "/../class.exportfiletype.php";

class ISC_ADMIN_EXPORTFILETYPE_ABANDONORDER extends ISC_ADMIN_EXPORTFILETYPE
{
	private $startStamp;
	private $endStamp;
	private $dateField;
	private $abandonorderDateFormat;

	protected $type_name = "abandonorder";
	protected $type_icon = "order.gif";
	protected $type_viewlink = "index.php?ToDo=viewOrdStats";

	public $ignore = false;

	public function __construct()
	{
		$timezoneAdjustment = GetConfig('StoreTimeZone');
		if(GetConfig('StoreDSTCorrection')) {
			++$timezoneAdjustment;
		}
		$timezoneAdjustment *= 3600;
		$this->dateField = "DATE_FORMAT(FROM_UNIXTIME(orddate+".$timezoneAdjustment."), '%Y-%m-%d')";
		$this->taxDateFormat = GetConfig('ExportDateFormat');

		parent::__construct();
	}

	public function GetFields()
	{
		$fields = array(
			'abandonorderOrderId' => array("dbfield" => "abandonorderOrderId"),
			'abandonorderCustomerName' => array("dbfield" => "abandonorderCustomerName"),
			'abandonorderCustomerEmail' => array("dbfield" => "abandonorderCustomerEmail"),
			'abandonorderCustomerPhone' => array("dbfield" => "abandonorderCustomerPhone"),
			'abandonorderDate' => array("dbfield" => "abandonorderDate", 'format' => 'date'),
			'abandonorderTotalOrderAmount' => array("dbfield" => "abandonorderTotalOrderAmount", 'format' => 'number')
		);

		return $fields;
	}

	protected function PostFieldLoad()
	{
		$this->dateformat = $this->abandonorderDateFormat;
	}

	protected function HandleRow($row)
	{
		return $row;
	}

	protected function GetQuery($columns, $where, $having)
	{
		if (trim($where) !== '') {
			$where = " AND " . $where;
		}

		$query = "
			SELECT
				orderid AS abandonorderOrderId,
				CONCAT(IFNULL(ordbillfirstname, ''), ' ', IFNULL(ordbilllastname, '')) AS abandonorderCustomerName,
				ordbillemail AS abandonorderCustomerEmail,
				ordbillphone AS abandonorderCustomerPhone,
				" . $this->dateField . " AS formatteddate,
				orddate AS abandonorderDate,
				total_inc_tax AS abandonorderTotalOrderAmount
			FROM
				[|PREFIX|]orders
			WHERE
				ordstatus = 0
				AND deleted = 0
				" . $where . "
			ORDER BY
				abandonorderDate
		";

		return $query;
	}

	public function GetListColumns()
	{
		$columns = array(
			"Order Id",
			"Customer Name",
			"Customer Email",
			"Customer Phone",
			"Date",
			"Total Order Amount"
		);

		return $columns;
	}

	public function GetListRow($row)
	{
		$new_row["Order Id"] = (int)$row["orderid"];
		$new_row["Customer Name"] = $row["ordcustomername"];
		$new_row["Customer Email"] = $row["ordbillemail"];
		$new_row["Customer Phone"] = $row["ordbillphone"];
		$new_row["Date"] = date($this->abandonorderDateFormat, $row['orddate']);
		$new_row["Total Order Amount"] = FormatPrice($row["total_inc_tax"]);

		return $new_row;
	}

	public function GetListSortLinks()
	{
		$columns = array(
			"OrderId",
			"CustomerName",
			"CustomerEmail",
			"Customer Phone",
			"Date",
			"TotalOrderAmount"
		);

		return $columns;
	}

	public function GetListCountQuery($where, $having)
	{
		if (trim($where) !== '') {
			$where = " AND " . $where;
		}

		$query = "
			SELECT
				COUNT(*) AS ListCount
			FROM
				[|PREFIX|]orders
			WHERE
				ordstatus = 0
				AND deleted = 0
				" . $where;

		return $query;
	}

	public function GetListQuery($where, $having, $sortField, $sortOrder)
	{
		if (trim($where) !== '') {
			$where = " AND " . $where;
		}

		$query = "
			SELECT
				orderid,
				CONCAT(IFNULL(ordbillfirstname, ''), ' ', IFNULL(ordbilllastname, '')) AS ordcustomername,
				ordbillemail,
				ordbillphone,
				orddate,
				total_inc_tax,
				" . $this->dateField . " AS formatteddate
			FROM
				[|PREFIX|]orders
			WHERE
				ordstatus = 0
				AND deleted = 0
				" . $where . "
			ORDER BY
				formatteddate
		";

		return $query;
	}


	public function BuildWhereFromFields($search_fields)
	{
		$from_stamp = (int)$search_fields['From'];
		$to_stamp = (int)$search_fields['To'];

		// Calculate the number of seconds from GMT +0 that we are in. We'll be adjusting
		// the orddate in the query below so that it becomes timezone specific (remember, MySQL thinks we're +0)
		$timezoneAdjustment = GetConfig('StoreTimeZone');
		if(GetConfig('StoreDSTCorrection')) {
			++$timezoneAdjustment;
		}
		$timezoneAdjustment *= 3600;

		if (empty($search_fields['TaxListBy'])) {
			$groupBy = 'Day';
		}
		else {
			$groupBy = $search_fields['TaxListBy'];
		}
		$fieldSQL = '';
		$addDay = 0;
		$addMonth = 0;
		$addYear = 0;
		switch ($groupBy) {
			case 'Day':
				$fieldSQL = "DATE_FORMAT(FROM_UNIXTIME(orddate+".$timezoneAdjustment."), '%Y-%m-%d')";
				$interval = ($to_stamp - $from_stamp) / 60 / 60 / 24;
				$this->abandonorderDateFormat = GetConfig('ExportDateFormat');
				break;
			case 'Month':
				$fieldSQL = "DATE_FORMAT(FROM_UNIXTIME(orddate+".$timezoneAdjustment."), '%Y-%m-1')";
				$this->abandonorderDateFormat = 'F Y';
				$interval = ((date('Y', $to_stamp) - date('Y', $from_stamp)) * 12) + date('m', $to_stamp) - date('m', $from_stamp) + 1;
				break;
			case 'Year':
				$fieldSQL = "DATE_FORMAT(FROM_UNIXTIME(orddate+".$timezoneAdjustment."), '%Y')";
				$this->abandonorderDateFormat = 'Y';
				$interval = date('Y', $to_stamp) - date('Y', $from_stamp) + 1;
				break;
		}

		$this->startStamp = $from_stamp;
		$this->endStamp = $to_stamp;
		$this->dateField = $fieldSQL;

		$where = "
			orddate >= '" . $from_stamp . "' AND
			orddate <= '" . $to_stamp . "'
		";

		return $where;
	}

	public function HasPermission()
	{
		return $GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Statistics_Orders);
	}
}
