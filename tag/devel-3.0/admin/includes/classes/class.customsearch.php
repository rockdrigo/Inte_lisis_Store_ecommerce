<?php

class ISC_ADMIN_CUSTOMSEARCH extends ISC_ADMIN_BASE
{
	public $_searchType;

	public function __construct($searchType)
	{
		parent::__construct();
		$this->_searchType = $searchType;
	}
	public function SaveSearch($customName, $searchVars)
	{
		$search_params = '';

		// Does a view already exist with this name?
		$query = sprintf("select count(searchname) as num from [|PREFIX|]custom_searches where searchname='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($customName));
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		$row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);

		if ($row['num'] == 0) {
			foreach ($searchVars as $k => $v) {
				if ($k == "customName" || $k == "ToDo" || $k == "SubmitButton1" || (!is_array($v) && trim($v)=='')) {
					continue;
				}
				if (is_array($v)) {
					foreach ($v as $v2) {
						$search_params .= sprintf("%s[]=%s&", $k, urlencode($v2));
					}
				}
				else {
					$search_params .= sprintf("%s=%s&", $k, urlencode($v));
				}
			}
			$search_params = $GLOBALS['ISC_CLASS_DB']->Quote(trim($search_params, "&"));
			$customSearch = array(
				"searchtype" => $this->_searchType,
				"searchname" => $customName,
				"searchvars" => $search_params
			);
			return $GLOBALS['ISC_CLASS_DB']->InsertQuery("custom_searches", $customSearch);
		}
		else {
			return 0;
		}
	}

	public function LoadSearch($searchId)
	{
		$searchId = (int)$searchId;
		$query = sprintf("SELECT searchname, searchvars, searchlabel FROM [|PREFIX|]custom_searches WHERE searchtype='%s' AND searchid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($this->_searchType), $GLOBALS['ISC_CLASS_DB']->Quote($searchId));
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

		if ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
			$search_vars = array();
			parse_str(urldecode($row['searchvars']), $search_vars);
			$row['searchvars'] = $search_vars;
			return $row;
		}
		return false;
	}

	public function GetSearches ()
	{
		$query = "
			SELECT searchid, searchname, searchlabel
			FROM [|PREFIX|]custom_searches
			WHERE searchtype='" . $this->db->Quote($this->_searchType) . "'
			ORDER BY searchname ASC
		";

		$result = $this->db->Query($query);
		$rows = array();
		while ($row = $this->db->Fetch($result)) {
			if ($this->_searchType == 'orders' && ($row['searchname'] == 'Orders from eBay' || $row['searchlabel'] == 'ebayorders') && !($GLOBALS['ISC_CLASS_ADMIN_AUTH']->HasPermission(AUTH_Ebay_Selling) && gzte11(ISC_LARGEPRINT))) {
				continue;
			}

			if ($this->_searchType == 'orders' && $row['searchlabel'] == 'deletedorders' && GetConfig('DeletedOrdersAction') != 'delete') {
				// don't show deleted orders view if setting not enabled
				continue;
			}

			$rows[] = $row;
		}
		return $rows;
	}

	public function GetSearchesAsOptions($selected, &$NumSearches, $FirstText, $FirstAction, $DefaultAction)
	{
		// Add the default "All Orders" view
		$menu_text = GetLang($FirstText);

		if ($selected == "") {
			$menu_text = "<strong>" . $menu_text . "</strong>";
		}

		$output = sprintf("<li><a href=\"index.php?ToDo=%s&searchId=0\" style='background-image:url(\"images/view.gif\"); background-repeat:no-repeat; background-position:5px 5px; padding-left:28px'>%s</a></li>", $FirstAction, $menu_text);

		$searches = $this->GetSearches();
		$NumSearches = count($searches);

		foreach ($searches as $row) {
			$menu_text = isc_html_escape($row['searchname']);

			if ($selected == $row['searchid']) {
				$menu_text = "<strong>" . $menu_text . "</strong>";
			}

			$output .= sprintf("<li><a href=\"index.php?ToDo=%s&searchId=%d\" style='background-image:url(\"images/view.gif\"); background-repeat:no-repeat; background-position:5px 5px; padding-left:28px'>%s</a></li>", $DefaultAction, $row['searchid'], $menu_text);
		}
		return $output;
	}

	public function DeleteSearch($searchId)
	{
		$searchId = (int)$searchId;

		$query = sprintf("DELETE FROM [|PREFIX|]custom_searches WHERE searchtype='%s' AND searchid='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($this->_searchType), $GLOBALS['ISC_CLASS_DB']->Quote($searchId));

		if ($GLOBALS['ISC_CLASS_DB']->Query($query)) {
			return true;
		} else {
			return false;
		}
	}

	public function findBySearchLabel($searchLabel)
	{
		$db = $GLOBALS['ISC_CLASS_DB'];

		$searchLabel = $db->Quote($searchLabel);
		$query = "SELECT * FROM [|PREFIX|]custom_searches WHERE searchlabel='".$searchLabel."';";

		if($result = $db->Query($query)){
			return $db->Fetch($result);
		}

		return null;
	}

	/**
	 * Returns a view url for built-in custom searches.
	 */
	public function getViewUrlBySearchLabel($searchLabel)
	{
		if(!$search = $this->findBySearchLabel($searchLabel)){
			return false;
		}

		$type = $search['searchtype'];

		static $searchTypeTodos = array(
			'products'	=> 'customProductSearch',
			'orders'	=> 'customOrderSearch',
			'shipments'	=> 'customShipmentSearch',
			'customers'	=> 'customCustomerSearch',
			'returns'	=> 'customReturnSearch',
			'giftcertificates'	=> 'customGiftCertificateSearch',
		);

		if(empty($searchTypeTodos[$type])) {
			return null;
		}

		return 'index.php?ToDo='.$searchTypeTodos[$type].'&searchId='.$search['searchid'];
	}
}
