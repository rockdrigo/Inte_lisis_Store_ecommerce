<?php
require_once dirname(__FILE__) . "/../class.exportfiletype.php";

class ISC_ADMIN_EXPORTFILETYPE_SALESTAX extends ISC_ADMIN_EXPORTFILETYPE
{
	private $startStamp;
	private $endStamp;
	private $lastStamp;
	private $dateField = '';
	private $taxDateFormat;
	private $addDay = 0;
	private $addMonth = 0;
	private $addYear = 0;

	protected $type_name = "salestax";
	protected $type_icon = "customer.gif";
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
			'salestaxDate' => array('format' => 'date'),
			'salestaxTaxName' => array(),
			'salestaxTaxRate' => array('format' => 'percent'),
			'salestaxNumOrders' => array(),
			'salestaxTaxAmount' => array('format' => 'number')
		);

		return $fields;
	}

	protected function PostFieldLoad()
	{
		$this->dateformat = $this->taxDateFormat;
	}

	protected function HandleRow($row)
	{
		$period = strtotime($row['formatteddate']);

		// is the last stamp less than the date for this row? then we need to fill in missing rows
		if ($this->lastStamp < $period) {
			$this->CreateBlankRecords($period);
			$this->lastStamp = $period;
		}

		// increment the last stamp to be the next expected period
		$this->lastStamp = mktime(0,0,0,date('m', $this->lastStamp) + $this->addMonth, date('j', $this->lastStamp) + $this->addDay, date('Y', $this->lastStamp) + $this->addYear);

		return $row;
	}

	protected function PostExport()
	{
		// is the last stamp less than the date for this row? then we need to fill in missing rows
		if ($this->lastStamp < $this->endStamp) {
			$this->CreateBlankRecords($this->endStamp, true);
		}
	}

	private function CreateBlankRecords($endStamp, $writeInclusive = false)
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('statistics');

		$blankRow = $this->CreateDummyRow($this->fields);
		if (isset($blankRow['salestaxTaxName'])) {
			$blankRow['salestaxTaxName'] = GetLang('NoTaxCollected');
		}
		if (isset($blankRow['salestaxNumOrders'])) {
			$blankRow['salestaxNumOrders'] = 0;
		}
		if (isset($blankRow['salestaxTaxAmount'])) {
			$blankRow['salestaxTaxAmount'] = 0;
		}

		$currentStamp = $this->lastStamp;

		// create blank rows until we reach the date for this row
		while (true) {
			if (isset($blankRow['salestaxDate'])) {
				$blankRow['salestaxDate'] = $currentStamp;
			}

			// format the data
			$this->FormatColumns($blankRow);

			// write the row using the export method
			$this->exportmethod->WriteRow($blankRow);

			// increment stamp
			$currentStamp = mktime(0,0,0,date('m', $currentStamp) + $this->addMonth, date('j', $currentStamp) + $this->addDay, date('Y', $currentStamp) + $this->addYear);

			if ($currentStamp >= $endStamp && !$writeInclusive) {
				break;
			}
			elseif ($currentStamp > $endStamp && $writeInclusive) {
				break;
			}
		}
	}

	protected function GetQuery($columns, $where, $having)
	{
		if ($where) {
			$where .= " AND ";
		}

		$query = "
			SELECT
				orddate AS salestaxDate,
				" . $this->dateField . " AS formatteddate,
				t.rate AS salestaxTaxRate,
				CONCAT(t.name, ' - ', t.class) AS salestaxTaxName,
				SUM(t.line_amount) AS salestaxTaxAmount,
				COUNT(DISTINCT t.order_id) AS salestaxNumOrders
			FROM [|PREFIX|]order_taxes t
			JOIN [|PREFIX|]orders o ON (o.orderid=t.order_id)
			WHERE
				" . $where . "
				t.line_amount > 0 AND
				o.ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND
				o.deleted = 0
			GROUP BY
				formatteddate,
				salestaxTaxName,
				salestaxTaxRate
			ORDER BY
				salestaxDate
		";

		return $query;
	}

	public function GetListColumns()
	{
		$columns = array(
			"Period",
			"Tax",
			"Rate",
			"Number of Orders",
			"Tax Amount"
		);

		return $columns;
	}

	public function GetListRow($row)
	{
		$new_row['Period'] = date($this->taxDateFormat, $row['orddate']);
		$new_row['Tax'] = $row['name'].' - '.$row['class'];
		$new_row['Rate'] = ($row['rate'] / 1) . '%';
		$new_row['Number of Orders"'] = $row['numorders'];
		$new_row['Tax Amount'] = FormatPrice($row['amount']);

		return $new_row;
	}

	public function GetListSortLinks()
	{
		return array();
	}

	public function GetListCountQuery($where, $having)
	{
		if ($where) {
			$where .= " AND ";
		}

		$query = "
			SELECT
				COUNT(TaxCount) AS ListCount
			FROM (
				SELECT COUNT(*) AS TaxCount
				FROM [|PREFIX|]order_taxes t
				JOIN [|PREFIX|]orders o ON (o.orderid=t.order_id)
				WHERE
					" . $where . "
					ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND
					o.deleted = 0 AND
					t.line_amount > 0
				GROUP BY
					" . $this->dateField . ",
					t.name,
					t.class,
					t.rate
			) AS taxquery
		";

		return $query;
	}

	public function GetListQuery($where, $having, $sortField, $sortOrder)
	{
		if ($where) {
			$where .= " AND ";
		}

		$query = "
			SELECT
				o.orddate,
				t.name,
				t.class,
				t.rate,
				SUM(t.line_amount) AS amount,
				COUNT(DISTINCT t.order_id) AS numorders,
				" . $this->dateField . " AS formatteddate
			FROM [|PREFIX|]order_taxes t
			JOIN [|PREFIX|]orders o ON (o.orderid=t.order_id)
			WHERE
				" . $where . "
				t.line_amount > 0 AND
				o.ordstatus IN (".implode(',', GetPaidOrderStatusArray()).") AND
				o.deleted = 0
			GROUP BY
				formatteddate,
				t.name,
				t.class,
				t.rate
			ORDER BY
				formatteddate
		";

		return $query;
	}


	public function BuildWhereFromFields($search_fields)
	{
		if (empty($search_fields['From'])) {
			$from_stamp = GetConfig('InstallDate');
		}
		else {
			$from_stamp = (int)$search_fields['From'];
		}

		if (empty($search_fields['To'])) {
			$to_stamp = isc_gmmktime(isc_date("H"), isc_date("i"), isc_date("s"), isc_date("m"), isc_date("d"), isc_date("Y"));
		}
		else {
			$to_stamp = (int)$search_fields['To'];
		}


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
		switch ($groupBy) {
			case 'Day':
				$fieldSQL = "DATE_FORMAT(FROM_UNIXTIME(orddate+".$timezoneAdjustment."), '%Y-%m-%d')";
				$this->addDay = 1;
				$this->taxDateFormat = GetConfig('ExportDateFormat');
				break;
			case 'Month':
				$fieldSQL = "DATE_FORMAT(FROM_UNIXTIME(orddate+".$timezoneAdjustment."), '%Y-%m-1')";
				$this->addMonth = 1;
				$this->taxDateFormat = 'F Y';
				break;
			case 'Year':
				$fieldSQL = "DATE_FORMAT(FROM_UNIXTIME(orddate+".$timezoneAdjustment."), '%Y')";
				$this->taxDateFormat = 'Y';
				$this->addYear = 1;
				break;
		}

		$this->startStamp = $from_stamp;
		$this->lastStamp = $from_stamp;
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
