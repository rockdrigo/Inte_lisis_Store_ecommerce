<?php
require_once dirname(__FILE__) . "/../class.exportfiletype.php";
require_once ISC_BASE_PATH . "/lib/class.redirects.php";
require_once ISC_BASE_PATH . "/lib/class.urls.php";

class ISC_ADMIN_EXPORTFILETYPE_REDIRECTS extends ISC_ADMIN_EXPORTFILETYPE
{
	protected $type_name = "redirects";
	protected $type_icon = "customer.gif";
	protected $type_idfield = "redirectid";
	protected $type_viewlink = "index.php?ToDo=viewRedirects";


	public function __construct()
	{
		parent::__construct();
	}

	public function GetFields()
	{
		 $fields = array(
			"redirectPath"			=> array("dbfield" => "redirectpath"),
			"redirectOldURL"		=> array("dbfield" => "CONCAT('" . GetConfig('ShopPath') . "', redirectpath)"),
			"redirectNewURL"		=> array(),
			"redirectAssocType"		=> array(),
			"redirectAssocId"		=> array("dbfield" => "redirectassocid"),
			"redirectNewURLOrType"	=> array()
		);

		return $fields;
	}

	protected function GetQuery($columns, $where, $having)
	{
		if ($where) {
			$where = " WHERE " . $where;
		}

		$query = "
			SELECT
				" . $columns . ",
				redirectassocid AS associd,
				redirectassoctype AS assoctype,
				redirectmanual AS manualurl
			FROM
				[|PREFIX|]redirects r
			" . $where;

		return $query;
	}

	protected function HandleRow($row)
	{
		switch ($row['assoctype']) {
			case ISC_REDIRECTS::REDIRECT_TYPE_PRODUCT:
				$newUrl = ISC_URLS::getProductUrl($row['associd']);
				$assocType = GetLang('Product');
				break;
			case ISC_REDIRECTS::REDIRECT_TYPE_CATEGORY:
				$newUrl = ISC_URLS::getCategoryUrl($row['associd']);
				$assocType = GetLang('Category');
				break;
			case ISC_REDIRECTS::REDIRECT_TYPE_BRAND:
				$newUrl = ISC_URLS::getBrandUrl($row['associd']);
				$assocType = GetLang('Brand');
				break;
			case ISC_REDIRECTS::REDIRECT_TYPE_PAGE:
				$newUrl = ISC_URLS::getPageUrl($row['associd']);
				$assocType = GetLang('Page');
				break;
			default:
				$newUrl = $row['manualurl'];
				$assocType = '';
				$row['redirectAssocId'] = '';
		}

		if ($this->fields['redirectNewURL']['used']) {
			$row['redirectNewURL'] = $newUrl;
		}

		if ($this->fields['redirectAssocType']['used']) {
			$row['redirectAssocType'] = $assocType;
		}

		if ($this->fields['redirectNewURLOrType']['used']) {
			if ($assocType) {
				$row['redirectNewURLOrType'] = $assocType;
			}
			else {
				$row['redirectNewURLOrType'] = $newUrl;
			}
		}

		return $row;
	}

	public function GetListColumns()
	{
		$columns = array(
			"ID",
			"Old URL",
			"New URL"
		);

		return $columns;
	}

	public function GetListSortLinks()
	{
		return array();
	}

	public function GetListQuery($where, $having, $sortField, $sortOrder)
	{
		if ($where) {
			$where = "WHERE " . $where;
		}

		$query = "
				SELECT
					*
				FROM
					[|PREFIX|]redirects r
				" . $where;

		return $query;
	}

	public function GetListCountQuery($where, $having)
	{
		if ($where) {
			$where = "WHERE " . $where;
		}

		$query = "
				SELECT
					COUNT(*) AS ListCount
				FROM
					[|PREFIX|]redirects r
				" . $where;

		return $query;
	}

	public function GetListRow($row)
	{
		switch($row['redirectassoctype']) {
			case ISC_REDIRECTS::REDIRECT_TYPE_PRODUCT:
				$urlInfo = ISC_URLS::getProductUrl($row['redirectassocid'], true);
				if(is_array($urlInfo)  && !empty($urlInfo['title'])) {
					$newUrl = '<a href="' . $urlInfo['url'] . '" target="_blank">' . GetLang('Product') . ': ' . $urlInfo['title'] . '</a>';
				}
				break;
			case ISC_REDIRECTS::REDIRECT_TYPE_CATEGORY:
				$urlInfo = ISC_URLS::getCategoryUrl($row['redirectassocid'], true);
				if(is_array($urlInfo)  && !empty($urlInfo['title'])) {
					$newUrl = '<a href="' . $urlInfo['url'] . '" target="_blank">' . GetLang('Category') . ': ' .  $urlInfo['title'] . '</a>';
				}
				break;
			case ISC_REDIRECTS::REDIRECT_TYPE_BRAND:
				$urlInfo = ISC_URLS::getBrandUrl($row['redirectassocid'], true);
				if(is_array($urlInfo)  && !empty($urlInfo['title'])) {
					$newUrl = '<a href="' . $urlInfo['url'] . '" target="_blank">' . GetLang('Brand') . ': ' . $urlInfo['title'] . '</a>';
				}
				break;
			case ISC_REDIRECTS::REDIRECT_TYPE_PAGE:
				$urlInfo = ISC_URLS::getPageUrl($row['redirectassocid'], true);
				if(is_array($urlInfo)  && !empty($urlInfo['title'])) {
					$newUrl = '<a href="' . $urlInfo['url'] . '" target="_blank">' . GetLang('Page') . ': ' . $urlInfo['title'] . '</a>';
				}
				break;
			default:
				$newUrl = '<a href="' . $row['redirectmanual'] . '" target="_blank">' . $row['redirectmanual'] . '</a>';
		}


		$new_row['ID'] = $row['redirectid'];
		$new_row['Old URL'] = '<a href="' . GetConfig('ShopPath') . $row['redirectpath'] . '" target="_blank">' . $row['redirectpath'] . '</a>';
		$new_row['New URL'] = $newUrl;

		return $new_row;
	}

	public function BuildWhereFromFields($search_fields)
	{
		return "";
	}

	public function HasPermission()
	{
		return $GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Manage_Redirects);
	}
}