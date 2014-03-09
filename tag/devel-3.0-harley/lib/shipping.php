<?php
	require_once(dirname(__FILE__).'/module.php');
	require_once(dirname(__FILE__) . "/../includes/classes/class.shipping.php");

	/**
	*	Get a list of regions as an array
	*/
	function GetRegionListAsNameValuePairs()
	{
		static $regions = null;

		if (is_array($regions)) {
			return $regions;
		}

		$query = "SELECT couregid, couregname
			FROM [|PREFIX|]region_regions
			ORDER BY couregname ASC";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$regions = array();

		$regions["-- All Countries --"] = "all";

		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$regions[$row['couregname']] = $row['couregid'];
		}

		return $regions;
	}

	/**
	*	Get a list of regions as <option> tags
	*/
	function GetRegionList($SelectedRegion = 0, $IncludeFirst = true, $FirstText = "ChooseARegion", $FirstValue = "0", $FirstSelected = false)
	{

		$list = "";

		// Should we add a blank option?
		if($IncludeFirst) {
			if($FirstSelected) {
				$sel = 'selected="selected"';
			} else {
				$sel = "";
			}

			$list = sprintf("<option %s value='%s'>-- %s --</option>", $sel, $FirstValue, GetLang($FirstText));
		}

		$result = $GLOBALS['ISC_CLASS_DB']->Query("select couregid, couregname from [|PREFIX|]country_regions order by couregname asc");

		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			if(is_numeric($SelectedRegion)) {
				// Match $SelectedRegion by region id
				if($row['couregid'] == $SelectedRegion) {
					$sel = 'selected="selected"';
				} else {
					$sel = "";
				}
			}
			else {
				// Match selected region by name
				if(is_array($SelectedRegion)) {
					// A list has been passed in
					if(in_array($row['couregname'], $SelectedRegion)) {
						$sel = 'selected="selected"';
					} else {
						$sel = "";
					}
				}
				else {
					// Just one region has been passed in
					if($row['couregname'] == $SelectedRegion) {
						$sel = 'selected="selected"';
					} else {
						$sel = "";
					}
				}
			}

			$list .= sprintf("<option value='%d' %s>%s</option>", $row['couregid'], $sel, $row['couregname']);
		}

		return $list;
	}

	/**
	*	Get a list of countries as an array
	*/
	function GetCountryListAsNameValuePairs()
	{
		static $countries = null;

		if (is_array($countries)) {
			return $countries;
		}

		$query = "
			SELECT countryid, countryname
			FROM [|PREFIX|]countries
			ORDER BY countryname ASC
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$countries = array();

		$countries["-- All Countries --"] = "all";

		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$countries[$row['countryname']] = $row['countryid'];
		}

		return $countries;
	}

	/**
	*	Get a list of countries as an array
	*/
	function GetCountryListAsIdValuePairs()
	{
		static $countries = null;

		if (is_array($countries)) {
			return $countries;
		}

		$query = "
			SELECT countryid, countryname
			FROM [|PREFIX|]countries
			ORDER BY countryname ASC
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$countries = array();

		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$countries[$row['countryid']] = $row['countryname'];
		}

		return $countries;
	}

	/**
	 * Get a list of countries as option tags where the country name is the value and the option.
	 *
	 * @param string The selected country.
	 * @param boolean Set to true to include an additional item (value is empty) at the top of the select.
	 * @param string The language variable to use for the first option.
	 * @return string The select box.
	 */
	function GetCountryNameListAsOptions($selectedCountry=0, $includeFirst=true, $firstText='ChooseACountry')
	{
		$list = array();

		if($includeFirst) {
			$list[] = '<option value="">-- '.GetLang($firstText).' --</option>';
		}

		while($row = _GetCountryListViaDB()) {
			$sel = '';
			if(is_array($selectedCountry)) {
				if(in_array($row['countryname'], $selectedCountry) || in_array($row['countryid'], $selectedCountry)) {
					$sel = 'selected="selected"';
				}
			}
			else {
				if($selectedCountry != '' && ($row['countryname'] == $selectedCountry || $row['countryid'] == $selectedCountry)) {
					$sel = 'selected="selected"';
				}
			}

			$list[] = '<option value="'.$row['countryname'].'" '.$sel.'>'.isc_html_escape($row['countryname']).'</option>';
		}
		return implode('', $list);
	}

	/**
	*	Get a list of countries as <option> tags
	*/
	function GetCountryList($SelectedCountry = 0, $IncludeFirst = true, $FirstText = "ChooseACountry", $FirstValue = "0", $FirstSelected = false, $useDatabase = true)
	{

		$list = "";

		// Should we add a blank option?
		if($IncludeFirst) {
			if($FirstSelected) {
				$sel = 'selected="selected"';
			} else {
				$sel = "";
			}

			$list = sprintf("<option %s value='%s'>-- %s --</option>", $sel, $FirstValue, GetLang($FirstText));
		}

		/**
		 * Decide if we are using the database or the CSV file. Normally we use the database BUT for instances where we need to select a country in the installer BEFORE the
		 * database is created, we need to use the CSV method
		 */
		if ($useDatabase) {
			$func = "_GetCountryListViaDB";
		} else {
			$func = "_GetCountryListViaCSV";
		}

		while($row = call_user_func($func)) {
			$sel = '';
			if(is_array($SelectedCountry)) {
				if(in_array($row['countryname'], $SelectedCountry) || in_array($row['countryid'], $SelectedCountry)) {
					$sel = 'selected="selected"';
				}
			}
			else {
				if($SelectedCountry != '' && ($row['countryname'] == $SelectedCountry || $row['countryid'] == $SelectedCountry)) {
					$sel = 'selected="selected"';
				}
			}

			$list .= sprintf("<option value='%d' %s>%s</option>", $row['countryid'], $sel, $row['countryname']);
		}

		return $list;
	}

	function _GetCountryListViaDB()
	{
		static $_cacheResult = null;

		if (is_null($_cacheResult)) {
			$_cacheResult = $GLOBALS['ISC_CLASS_DB']->Query("select countryid, countryname from [|PREFIX|]countries order by countryname asc");
		}

		if (!($row = $GLOBALS['ISC_CLASS_DB']->Fetch($_cacheResult))) {
			$_cacheResult = null;
		}

		return $row;
	}

	function _GetCountryListViaCSV()
	{
		static $_cacheResult = null;

		if (is_null($_cacheResult)) {
			$template		= realpath(ISC_BASE_PATH . "/admin/templates/install.countries.csv.tpl");
			$_cacheResult	= fopen($template, "rb");
		}

		if (is_array($row = fgetcsv($_cacheResult, 8192))) {
			$row = array(
				"countryid"		=> $row[0],
				"countryname"	=> $row[1],
				"countryiso2"	=> $row[2],
				"countryiso3"	=> $row[3]
			);
		} else {
			$_cacheResult = null;
		}

		return $row;
	}

	/**
	 * Fetch the display name of a country from the CSV file of countries based on the passed
	 * country ID.
	 *
	 * @return string The name of the country.
	 */
	function GetCSVCountryNameById($countryId)
	{
		while($row = _GetCountryListViaCSV()) {
			if($countryId == $row['countryid']) {
				return $row['countryname'];
			}
		}
		return '';
	}

	/**
	 * Build a list of options for a multi-country state picker.
	 *
	 * @param array Array of selected countries and states. Key of the array is the country ID, value is an array of selected states (0 for all)
	 * @return string The option list.
	 */
	function GetMultiCountryStateOptions($selectedValues=array(), $showAllOption=true)
	{
		$countryIds = array_keys($selectedValues);
		$countryIds = implode(',', $countryIds);

		$query = "SELECT * FROM [|PREFIX|]countries WHERE countryid IN (".$countryIds.") ORDER BY countryname";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($country = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$countries[$country['countryid']] = $country['countryname'];
		}

		$states = array();

		// Load the states for the selected countries
		$query = "SELECT * FROM [|PREFIX|]country_states WHERE statecountry IN (".$countryIds.") ORDER BY statename";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($state = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$states[$state['statecountry']][$state['stateid']] = $state['statename'];
		}

		// Now build the select options
		$options = '';
		foreach($countries as $countryId => $countryName) {
			$options .= "<optgroup class=\"country".$countryId."\" label=\"".$countryName."\">\n";
			$stateIds = $selectedValues[$countryId];
			if(!is_array($stateIds)) {
				$stateIds = array();
			}
			if($showAllOption == true) {
				$allSelected = '';
				if(in_array('0', $stateIds)) {
					$allSelected = 'selected="selected"';
				}
				$options .= "<option value=\"".$countryId."-0\" ".$allSelected.">-- ".GetLang('AllStatesProvinces')." --</option>\n";
			}
			if(isset($states[$countryId])) {
				foreach($states[$countryId] as $stateId => $stateName) {
					$selected = '';
					if(in_array($stateId, $stateIds)) {
						$selected = 'selected="selected"';
					}
					$options .= "<option value=\"".$countryId."-".$stateId." \" ".$selected.">".isc_html_escape($stateName)."</option>\n";
				}
			}
			$options .= "</optgroup>\n";
		}
		return $options;
	}

	/**
	*	Get a list of states for use in JavaScript
	*/
	function GetStateList($country)
	{
		$output = "";
		$query = sprintf("select stateid, statename from [|PREFIX|]country_states where statecountry='%d' order by statename asc", $GLOBALS['ISC_CLASS_DB']->Quote($country));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$output .= sprintf("%s|%s~", $row['statename'], $row['stateid']);
		}

		return $output;
	}

	function GetStateListAsIdValuePairs($countryId)
	{
		static $states = null;

		if (!isId($countryId)) {
			return false;
		}

		if (isset($states[$countryId]) && is_array($states[$countryId])) {
			return $states[$countryId];
		}

		$query = "
			SELECT stateid, statename
			FROM [|PREFIX|]country_states
			WHERE statecountry=" . (int)$countryId . "
			ORDER BY statename ASC
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$states[$countryId] = array();

		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$states[$countryId][$row['stateid']] = $row['statename'];
		}

		return $states[$countryId];
	}
	/**
	*	Get a list of states as an array
	*/
	function GetStatesArray($country)
	{
		$output = "";
		$query = sprintf("SELECT stateid, statename FROM [|PREFIX|]country_states WHERE statecountry='%d' ORDER BY statename ASC", $GLOBALS['ISC_CLASS_DB']->Quote($country));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$states = array();

		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$states[$row['stateid']] = $row['statename'];
		}

		return $states;
	}

	/**
	 * Get the information about a state based on the passed state ID.
	 *
	 * @param int The state ID.
	 * @return array An array of information about the state.
	 */
	function GetStateInfoById($stateId)
	{
		static $cache = array();
		if(isset($cache[$stateId])) {
			return $cache[$stateId];
		}

		$query = "
			SELECT *
			FROM [|PREFIX|]country_states
			WHERE stateid='".(int)$stateId."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$cache[$stateId] = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		return $cache[$stateId];
	}

	/**
	 * Get the information about a state based on the passed state name.
	 *
	 * @param string The full text name of the state.
	 * @return array An array of information about the state.
	 */
	function GetStateInfoByName($stateName)
	{
		static $cache = array();
		$stateName = strtolower($stateName);
		if(isset($cache[$stateName])) {
			return $cache[$stateName];
		}

		$query = "
			SELECT *
			FROM [|PREFIX|]country_states
			WHERE statename='".$GLOBALS['ISC_CLASS_DB']->Quote($stateName)."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$cache[$stateName] = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		return $cache[$stateName];
	}

	/**
	*	Return a state's name based on its id
	*/
	function GetStateById($stateId)
	{
		$stateInfo = GetStateInfoById($stateId);
		if(isset($stateInfo['statename'])) {
			return $stateInfo['statename'];
		}
		else {
			return '';
		}
	}

	/**
	*	Return a state's id based on its name and country id
	*/
	function GetStateByName($stateName, $countryId)
	{
		$stateInfo = GetStateInfoByName($stateName);
		if(isset($stateInfo['stateid']) && $stateInfo['statecountry']== $countryId) {
			return $stateInfo['stateid'];
		}
		else {
			return false;
		}
	}

	/**
	*	Return a state's id based on its abbreviation and country id
	*/
	function GetStateByAbbrev($stateAbbrev, $countryId)
	{
		$query = sprintf("select stateid from [|PREFIX|]country_states where stateabbrv='%s' and statecountry='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($stateAbbrev), $GLOBALS['ISC_CLASS_DB']->Quote($countryId));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		if($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			return $row['stateid'];
		} else {
			return "";
		}
	}

	/**
	*	Return a state's name based on its abbreviation and country id
	*/
	function GetStateNameByAbbrev($stateAbbrev, $countryId)
	{
		$query = sprintf("select statename from [|PREFIX|]country_states where stateabbrv='%s' and statecountry='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($stateAbbrev), $GLOBALS['ISC_CLASS_DB']->Quote($countryId));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		if($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			return $row['statename'];
		} else {
			return "";
		}
	}

	/**
	*	Return a state's ISO code based on its id
	*/
	function GetStateISO2ById($stateId)
	{
		$stateInfo = GetStateInfoById($stateId);
		if(isset($stateInfo['stateabbrv'])) {
			return $stateInfo['stateabbrv'];
		}
		else {
			return '';
		}
	}

	/**
	*	Return a state's ISO code based on its name
	*/
	function GetStateISO2ByName($stateName)
	{
		$stateInfo = GetStateInfoByName($stateName);
		if(isset($stateInfo['stateid'])) {
			return $stateInfo['stateabbrv'];
		}
		else {
			return false;
		}
	}

	/**
	 * Get all of the information about a country in the database based on the passed country ID.
	 *
	 * @param int The country ID.
	 * @return array An array of information about the country.
	 */
	function GetCountryInfoById($countryId)
	{
		static $cache;
		if(isset($cache[$countryId])) {
			return $cache[$countryId];
		}

		$query = "
			SELECT *
			FROM [|PREFIX|]countries
			WHERE countryid='".(int)$countryId."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$cache[$countryId] = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		return $cache[$countryId];
	}

	/**
	 * Get all of the information about a country in the database based on the passed country name.
	 *
	 * @param int The country name.
	 * @return array An array of information about the country.
	 */
	function GetCountryInfoByName($countryName)
	{
		if(!$countryName) {
			return false;
		}

		static $cache;
		$countryName = strtolower($countryName);
		if(isset($cache[$countryName])) {
			return $cache[$countryName];
		}

		$query = "
			SELECT *
			FROM [|PREFIX|]countries
			WHERE countryname='".$GLOBALS['ISC_CLASS_DB']->Quote($countryName)."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$cache[$countryName] = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		return $cache[$countryName];
	}

	/**
	*	Return a country's name based on its id
	*/
	function GetCountryById($countryId)
	{
		$countryInfo = GetCountryInfoById($countryId);
		if(isset($countryInfo['countryname'])) {
			return $countryInfo['countryname'];
		}
		else {
			return '';
		}
	}

	/**
	*	Return a country's 2 digit ISO code based on its id
	*/
	function GetCountryISO2ById($countryId)
	{
		$countryInfo = GetCountryInfoById($countryId);
		if(isset($countryInfo['countryiso2'])) {
			return $countryInfo['countryiso2'];
		}
		else {
			return '';
		}
	}

	/**
	*	Return a country's 3 digit ISO code based on its id
	*/
	function GetCountryISO3ById($countryId)
	{
		$countryInfo = GetCountryInfoById($countryId);
		if(isset($countryInfo['countryiso3'])) {
			return $countryInfo['countryiso3'];
		}
		else {
			return '';
		}
	}

	/**
	*	Return a country's id based on its name
	*/
	function GetCountryByName($countryName)
	{
		$countryInfo = GetCountryInfoByName($countryName);
		if(isset($countryInfo['countryid'])) {
			return $countryInfo['countryid'];
		}
		else {
			return false;
		}
	}

	/**
	*	Return the name of a country from its ID
	*/
	function GetCountryIdByName($country)
	{
		return GetCountryByName($country);
	}

	/**
	*	Return a country's 2 digit ISO code based on its name
	*/
	function GetCountryISO2ByName($countryName)
	{
		$countryInfo = GetCountryInfoByName($countryName);
		if(isset($countryInfo['countryiso2'])) {
			return $countryInfo['countryiso2'];
		}
		else {
			return false;
		}
	}

	/**
	*	Return a country's 3 digit ISO code based on its name
	*/
	function GetCountryISO3ByName($countryName)
	{
		$countryInfo = GetCountryInfoByName($countryName);
		if(isset($countryInfo['countryiso3'])) {
			return $countryInfo['countryiso3'];
		}
		else {
			return false;
		}
	}

	/**
	*	Return a country's id based on its 2-digit ISO
	*/
	function GetCountryIdByISO2($countryISO2)
	{
		$query = sprintf("select countryid from [|PREFIX|]countries where countryiso2='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($countryISO2));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		if($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			return $row['countryid'];
		} else {
			return "";
		}
	}

	/**
	*	Return a country's id based on its 3-digit ISO
	*/
	function GetCountryIdByISO3($countryISO3)
	{
		$query = sprintf("select countryid from [|PREFIX|]countries where countryiso3='%s'", $GLOBALS['ISC_CLASS_DB']->Quote($countryISO3));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		if($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			return $row['countryid'];
		} else {
			return "";
		}
	}

	/**
	 * Generate a field (either a text box or select box) for entering/selecting a state.
	 * In the case that the supplied country does not have any states, a text box is returned.
	 * If the country does have states, a textbox asking for the country to be selected is returned.
	 *
	 * @param string The name of the text box/select box.
	 * @param string The ID of the text box/select box.
	 * @param string The name of the country to fetch the states for (can also be the country ID)
	 * @param string The name of the selected state.
	 * @param string Optionally a class name to be applied to the select box or text box.
	 * @return string The generated text/select box.
	 */
	function GenerateStateSelect($name, $id, $country='', $selectedState='', $className='')
	{
		// If no country was supplied use the store default
		if(empty($country)) {
			$country = GetConfig('CompanyCountry');
		}

		$stateList = GetStateListAsOptions($country, $selectedState, false, '', '', false, true);
		if(!$stateList) {
			return '<input type="text" name="'.$name.'" id="'.$id.'" class="'.$className.'" class="StateSelect" value="'.isc_html_escape($selectedState).'" />';
		}
		else {
			$select = '<select name="'.$name.'" id="'.$id.'" class="'.$className.'" class="StateSelect">';
			$select .= $stateList;
			$select .= '</select>';
			return $select;
		}
	}

	/**
	*	Get a list of states as <option> tags
	*/
	function GetStateListAsOptions($country, $selectedState = 0, $IncludeFirst = true, $FirstText = "ChooseAState", $FirstValue = "0", $FirstSelected = false, $useNamesAsValues = false)
	{
		if(!is_numeric($country)) {
			$country = GetCountryIdByName($country);
		}

		$list = "";
		$query = sprintf("select stateid, statename from [|PREFIX|]country_states where statecountry='%d' order by statename asc", $GLOBALS['ISC_CLASS_DB']->Quote($country));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		// Should we add a blank option?
		if($IncludeFirst) {
			if($FirstSelected) {
				$sel = 'selected="selected"';
			} else {
				$sel = "";
			}

			$list = sprintf("<option %s value='%s'>%s</option>", $sel, $FirstValue, GetLang($FirstText));
		}

		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			if(is_numeric($selectedState)) {
				// Match $selectedState by country id
				if($row['stateid'] == $selectedState) {
					$sel = 'selected="selected"';
				} else {
					$sel = "";
				}
			}
			else if(is_array($selectedState)) {
				// A list has been passed in
				if(in_array($row['stateid'], $selectedState) || in_array($row['statename'], $selectedState)) {
					$sel = 'selected="selected"';
				} else {
					$sel = "";
				}			}
			else {
				// Just one state has been passed in
				if(strtolower($row['statename']) == strtolower($selectedState)) {
					$sel = 'selected="selected"';
				} else {
					$sel = "";
				}
			}

			if($useNamesAsValues) {
				$value = isc_html_escape($row['statename']);
			}
			else {
				$value = $row['stateid'];
			}
			$list .= sprintf("<option value='%s' %s>%s</option>", $value, $sel, $row['statename']);
		}

		return $list;
	}

	function GetNumStatesInCountry($countryId)
	{
		$query = sprintf("select count(stateid) as num from [|PREFIX|]country_states where statecountry='%d'", $GLOBALS['ISC_CLASS_DB']->Quote($countryId));
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		return $row['num'];
	}

	/**
	 * Get a list of the available shipping methods for a particular shipping zone.
	 *
	 * @param int The zone id to fetch available shipping methods for.
	 * @return array Array of shipping methods available in this zone.
	 */
	function GetShippingMethodsByZone($zoneId)
	{
		$methods = array();

		$query = "
			SELECT *
			FROM [|PREFIX|]shipping_methods
			WHERE zoneid='".(int)$zoneId."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($method = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$methods[] = $method;
		}

		return $methods;
	}
	/**
	 * Determine the shipping zone id from a given address array. Will search the database to match first by post code, then by state, then by country.
	 *
	 * @param array Array of information regarding the address (shipzip, shipstateid, shipcountryid)
	 * @return int The matched shipping zone if any, 0 if not (0 explicitly means the default zone for "any other unmatched location")
	 */
	function GetShippingZoneIdByAddress($address)
	{
		static $zoneIdCache = array();

		// Do we have a cached result use that
		$cacheId = md5(strtolower(serialize($address)));

		if(isset($zoneIdCache[$cacheId])) {
			return $zoneIdCache[$cacheId];
		}

		// Fetch out the ID of the default zone
		$query = "
			SELECT zoneid
			FROM [|PREFIX|]shipping_zones
			WHERE zonedefault='1'
		";
		$zoneId = $GLOBALS['ISC_CLASS_DB']->FetchOne($query);
		$defaultZone = $zoneId;

		// Check if we have a shipping zone that matches based on the post code first
		if($address['shipzip']) {
			$query = "
				SELECT z.zoneid, locationvalueid, locationvalue
				FROM [|PREFIX|]shipping_zone_locations l
				INNER JOIN [|PREFIX|]shipping_zones z ON (z.zoneid=l.zoneid)
				WHERE z.zoneenabled='1' AND locationtype='zip' AND locationcountryid='".(int)$address['shipcountryid']."'
				AND (
				'".$GLOBALS['ISC_CLASS_DB']->Quote($address['shipzip'])."' REGEXP REPLACE(REPLACE(CONCAT('^', locationvalue, '$'), '*', '.{1,}'), '?', '.') 
					OR ('".$GLOBALS['ISC_CLASS_DB']->Quote($address['shipzip'])."' >= SUBSTRING(locationvalue, 1, LOCATE('-', locationvalue)-1)
					AND '".$GLOBALS['ISC_CLASS_DB']->Quote($address['shipzip'])."' <= SUBSTRING(locationvalue, LOCATE('-', locationvalue)+1))
				)";

			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			while($zone = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
				if($zone['zoneid'] == $address['shipzip']) {
					$zoneId = $zone['zoneId'];
					continue;
				}
				else {
					// Score the characters in the string
					$score = (substr_count($zone['locationvalue'], '*')*10)+(substr_count($zone['locationvalue'], '?'));

					// A lower score means a stronger match, so we use that zone ID
					if(!isset($lastScore) || $score < $lastScore) {
						$zoneId = $zone['zoneid'];
						$lastScore = $score;
					}
				}
			}

			if($zoneId != $defaultZone) {
				$zoneIdCache[$cacheId] = $zoneId;
				return $zoneId;
			}
		}

		// Try based on the shipping state and country for a zone
		$query = "
			SELECT z.zoneid
			FROM [|PREFIX|]shipping_zone_locations l
			INNER JOIN [|PREFIX|]shipping_zones z ON (z.zoneid=l.zoneid)
			WHERE z.zoneenabled='1' AND locationtype='state' AND locationcountryid='".(int)$address['shipcountryid']."' AND (locationvalueid='".(int)$address['shipstateid']."' OR locationvalueid='0')
			ORDER BY locationvalueid DESC
			LIMIT 1
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$zone = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if(isset($zone['zoneid'])) {
			$zoneIdCache[$cacheId] = $zone['zoneid'];
			return $zone['zoneid'];
		}

		// Otherwise, do we have a country level zone we can fall back on?
		$query = "
			SELECT z.zoneid
			FROM [|PREFIX|]shipping_zone_locations l
			INNER JOIN [|PREFIX|]shipping_zones z ON (z.zoneid=l.zoneid)
			WHERE z.zoneenabled='1' AND locationtype='country' AND locationvalueid='".(int)$address['shipcountryid']."'
			LIMIT 1
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$zone = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if(isset($zone['zoneid'])) {
			$zoneIdCache[$cacheId] = $zone['zoneid'];
			return $zone['zoneid'];
		}

		// If we're still here, we just return $zoneId which will be 1 - this means "every other location"
		$zoneIdCache[$cacheId] = $zoneId;
		return $zoneId;
	}

	/**
	 * Retrieve a shipping zone from the database based on the passed ID.
	 *
	 * @param int The ID of the shipping zone.
	 * @return array Array containing the shipping zone data.
	 */
	function GetShippingZoneById($zoneId)
	{
		static $zoneCache;

		if(isset($zoneCache[$zoneId])) {
			return $zoneCache[$zoneId];
		}

		// Otherwise, we need to query for it
		$query = "
			SELECT *
			FROM [|PREFIX|]shipping_zones
			WHERE zoneid='".(int)$zoneId."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$zoneCache[$zoneId] = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		return $zoneCache[$zoneId];
	}

	/**
	 * Retrieve a shipping method from the database based on the passed ID.
	 *
	 * @param int The ID of the shipping method.
	 * @return array Array containing the shipping method data.
	 */
	function GetShippingMethodById($methodId)
	{
		static $methodCache;

		if(isset($methodCache[$methodId])) {
			return $methodCache[$methodId];
		}

		// Otherwise, we need to query for it
		$query = "
			SELECT *
			FROM [|PREFIX|]shipping_methods
			WHERE methodid='".(int)$methodId."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$methodCache[$methodId] = $GLOBALS['ISC_CLASS_DB']->Fetch($result);
		return $methodCache[$methodId];
	}

	function GetShippingInfoByZoneId($zoneid, $enabledonly=true)
	{
		$query = "
			SELECT *
			FROM [|PREFIX|]shipping_zones z
			INNER JOIN [|PREFIX|]shipping_zone_locations l
			ON z.zoneid=l.zoneid
			WHERE z.zoneid = '".$GLOBALS['ISC_CLASS_DB']->Quote($zoneid)."'
		";

		if ($enabledonly) {
			$query .= " AND z.zoneenabled = 1 ";
		}

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		$zone = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if ($zone === false) {
			return false;
		}

		$query = "
			SELECT *
			FROM [|PREFIX|]shipping_methods m, [|PREFIX|]shipping_vars v
			WHERE m.zoneid = v.zoneid
			AND m.methodid= v.methodid
			AND m.zoneid = '".$GLOBALS['ISC_CLASS_DB']->Quote($zoneid)."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$zone['methods'][] = $row;
		}

		return $zone;
	}

	function GetShippingZoneInfo()
	{
		$query = "
			SELECT *
			FROM [|PREFIX|]shipping_zones z
			WHERE z.zoneenabled='1'
		";

		static $zones = null;

		if (is_array($zones)) {
			return $zones;
		}

		$zones = array();

		$zoneresult = $GLOBALS['ISC_CLASS_DB']->Query($query);

		while ($zone = $GLOBALS['ISC_CLASS_DB']->Fetch($zoneresult)) {
			$query = "SELECT *
			FROM [|PREFIX|]shipping_zone_locations l
			WHERE l.zoneid = '".$GLOBALS['ISC_CLASS_DB']->Quote($zone['zoneid'])."'";
			$locationresult = $GLOBALS['ISC_CLASS_DB']->Query($query);

			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($locationresult)) {
				$zone['locations'][] = $row;
				if (!isset($zone['locationtype'])) {
					$zone['locationtype'] = $row['locationtype'];
				}
			}

			$query = "SELECT *
			FROM [|PREFIX|]shipping_methods m
			WHERE m.methodenabled = 1
			AND m.zoneid = '".$GLOBALS['ISC_CLASS_DB']->Quote($zone['zoneid'])."'";
			$methodresult = $GLOBALS['ISC_CLASS_DB']->Query($query);

			while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($methodresult)) {
				$query = "SELECT variablename as name, variableval as val
				FROM [|PREFIX|]shipping_vars v
				WHERE v.zoneid = '".$GLOBALS['ISC_CLASS_DB']->Quote($row['zoneid'])."'
				AND v.methodid = '".$GLOBALS['ISC_CLASS_DB']->Quote($row['methodid'])."'";

				$var_result = $GLOBALS['ISC_CLASS_DB']->Query($query);

				while ($var_row = $GLOBALS['ISC_CLASS_DB']->Fetch($var_result)) {
					$row['vars'][$var_row['name']] = $var_row['val'];
				}

				$zone['methods'][] = $row;
			}

			$zones[] = $zone;
		}

		return $zones;
	}

	/**
	 * Get all of the module settings for a specific shipping method & module combination.
	 *
	 * @param int The shipping method ID.
	 * @param string The module name to load the shipping settings for.
	 */
	function LoadShippingVars($methodId, $moduleName)
	{
		static $cache = array();
		if(isset($cache[$methodId][$moduleName])) {
			return $cache[$methodId][$moduleName];
		}

		$query = "
			SELECT *
			FROM [|PREFIX|]shipping_vars
			WHERE methodid='".(int)$methodId."' AND modulename='".$GLOBALS['ISC_CLASS_DB']->Quote($moduleName)."'
		";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		while($var = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$cache[$methodId][$moduleName][] = $var;
		}
		if (isset($cache[$methodId][$moduleName])) {
			return $cache[$methodId][$moduleName];
		} else {
			return array();
		}
	}