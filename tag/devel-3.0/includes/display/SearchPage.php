<?php

	CLASS ISC_SEARCHPAGE_PANEL extends PANEL
	{
		public function SetPanelSettings()
		{
			// generate url with all current GET params except page, ajax and section
			$url = array();
			foreach ($_GET as $key => $value) {
				if ($key == 'page' || $key == 'ajax' || $key == 'section') {
					continue;
				}
				if (is_array($value)) {
					foreach ($value as $subvalue) {
						$url[] = urlencode($key . '[]') . '=' . urlencode($subvalue);
					}
				} else {
					$url[] = urlencode($key) . '=' . urlencode($value);
				}
			}

			$url = 'search.php?' . implode('&', $url);

			$GLOBALS['ProductTabUrl'] = isc_html_escape($url . '&section=product#results');
			$GLOBALS['ContentTabUrl'] = isc_html_escape($url . '&section=content#results');

			$GLOBALS["SelectedSearchTab"] = "";
			$GLOBALS["HideSearchPage"] = "";

			if (!$GLOBALS["ISC_CLASS_SEARCH"]->searchIsLoaded()) {
				$GLOBALS["HideSearchPage"] = "none";
			} else {
				$GLOBALS["ProductContainerDisplay"] = "display:none;";
				$GLOBALS["ContentContainerDisplay"] = "display:none;";

				$section = "product";
				if ($GLOBALS["ISC_CLASS_SEARCH"]->GetNumResults("product", "brand", "category") === 0 && $GLOBALS["ISC_CLASS_SEARCH"]->GetNumResults("content") > 0) {
					$section = "content";
				}

				if (isset($_GET['section'])) {
					$section = $_GET['section'];
				}

				if ($section == 'content') {
					$GLOBALS["SelectedSearchTab"] = "content";
					$GLOBALS["ContentTabActive"] = "Active";
					$GLOBALS["ContentContainerDisplay"] = "";
				} else {
					$GLOBALS["SelectedSearchTab"] = "product";
					$GLOBALS["ProductTabActive"] = "Active";
					$GLOBALS["ProductContainerDisplay"] = "";
				}
			}
		}
	}