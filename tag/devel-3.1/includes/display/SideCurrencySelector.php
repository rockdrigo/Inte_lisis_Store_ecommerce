<?php

	CLASS ISC_SIDECURRENCYSELECTOR_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			// Have we only got one currency? Don't show anything
			$currencyCache = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('Currencies');
			if(empty($currencyCache) || count($currencyCache) <= 2) {
				$this->DontDisplay = true;
				return;
			}

			$GLOBALS['SNIPPETS']['ChooseCurrencyList'] = '';
			$query = "
				SELECT cu.currencyid, cu.currencycode, cu.currencyname, IFNULL(co.countryname, cr.couregname) AS countryname, IFNULL(co.countryiso2, cr.couregiso2) AS countryflagname,
				       IF(co.countryid IS NOT NULL, 0, 1) AS currencyisregion
				FROM [|PREFIX|]currencies cu
				LEFT JOIN [|PREFIX|]countries co ON cu.currencycountryid = co.countryid
				LEFT JOIN [|PREFIX|]country_regions cr ON cu.currencycouregid = cr.couregid
				WHERE cu.currencystatus = 1
				ORDER BY cu.currencyisdefault DESC, cu.currencyname ASC
			";
			$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
			$total	= $GLOBALS['ISC_CLASS_DB']->CountResult($result);

			$currencyLink = GetCurrentLocation();
			$currencyLink = preg_replace("#setCurrencyId=[0-9]+#", "", $currencyLink);
			if(strpos($currencyLink, '?') === false) {
				$currencyLink .= '?';
			}
			else if(strpos($currencyLink, '?') != strlen($currencyLink)-1 && substr($currencyLink, -1) !== '&') {
				$currencyLink .= '&';
			}
			$currencyLink .= 'setCurrencyId=';

			for ($i=1; ($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)); $i++) {
				$GLOBALS['CurrencySelected'] = '';

				$GLOBALS['CurrencySwitchLink'] = isc_html_escape($currencyLink.$row['currencyid']);

				if ($row['currencyid'] == $GLOBALS['CurrentCurrency']) {
					$GLOBALS['CurrencySelected'] = 'Sel';
					$GLOBALS['SelectedCurrencyID'] = $row['currencyid'];
				}

				// This needs to be in a separate general function for getting the flag
				if ($row['currencyisregion']) {
					$parts = "regions/";
				} else {
					$parts = "";
				}

				if (file_exists(ISC_BASE_PATH . "/lib/flags/" . $parts . strtolower($row['countryflagname']) . ".gif")) {
					$GLOBALS['CurrencyFlag'] = '<img src="'. GetConfig("ShopPath") . '/lib/flags/' . $parts . strtolower($row['countryflagname']) . '.gif" border="0" alt="' . isc_html_escape($row['countryname']) . '" />';
				} else {
					$GLOBALS['CurrencyFlag'] = '';
				}

				$GLOBALS['CurrencyID'] = $row['currencyid'];
				$GLOBALS['CurrencyName'] = isc_html_escape($row['currencyname']);
				$GLOBALS['CurrencyFlagStyle'] = '';

				if ($i < $total) {
					$GLOBALS['CurrencyFlagStyle'] = 'border-bottom: 0px;';
				}

				$GLOBALS['SNIPPETS']['ChooseCurrencyList'] .= $GLOBALS['ISC_CLASS_TEMPLATE']->GetSnippet("SideCurrencySelectorCurrency");
			}
		}
	}