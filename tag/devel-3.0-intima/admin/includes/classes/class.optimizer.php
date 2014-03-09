<?php
class ISC_ADMIN_OPTIMIZER extends ISC_ADMIN_BASE
{
	public function HandleToDo($Do)
	{
		$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->LoadLangFile('optimizer');
		$todo = isc_strtolower($Do);

		switch($todo) {
			default:
				if ($GLOBALS["ISC_CLASS_ADMIN_AUTH"]->HasPermission(AUTH_Website_Optimizer)) {

					$GLOBALS['BreadcrumEntries'] = array(GetLang('Home') => "index.php", GetLang('GoogleWebsiteOptimizer') => "index.php?ToDo=manageOptimizer");

					if(!isset($_REQUEST['ajax'])) {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintHeader();
					}

					$this->manageOptimizer();

					if(!isset($_REQUEST['ajax'])) {
						$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->PrintFooter();
					}
				} else {
					$GLOBALS['ISC_CLASS_ADMIN_ENGINE']->DoHomePage(GetLang('Unauthorized'), MSG_ERROR);
				}
			break;
		}
	}

	/**
	* Display the store-wide GWO tests list
	*
	*/
	private function manageOptimizer()
	{
		$Tests = GetAvailableModules('optimizer');
		$Output = "";
		$EnabledModules = array();

		$GLOBALS['Message'] = GetFlashMessageBoxes();

		$EnabledModules = GetConfig('OptimizerMethods');

		$GLOBALS['OptimizerRow'] = '';
		foreach ($Tests as $Test) {
			$GLOBALS['ModuleName'] = isc_html_escape($Test['name']);
			$GLOBALS['ModuleId'] = $Test['id'];
			$GLOBALS['ConfiguredIcon'] = 'cross';
			$GLOBALS['ConfiguredDate'] = 'N/A';
			$GLOBALS['ActiveReset'] = 'inactive';

			if($Test['enabled']) {
				$GLOBALS['ActiveReset'] = 'active';
				$GLOBALS['ConfiguredIcon'] = 'tick';
				if(isset($EnabledModules[$Test['id']]) && $EnabledModules[$Test['id']] != '') {
					$GLOBALS['ConfiguredDate'] = isc_date('jS M Y',$EnabledModules[$Test['id']]);
				}
			}
			$GLOBALS['OptimizerRow'] .= $this->template->render('Snippets/OptimizerRow.html');

		}

		$this->template->display('optimizer.manage.tpl');
	}


	/**
	* get the html codes for the GWO configure form for product, category or page based GWO test
	*
	* @param string $type, (product, category or page)
	* @param string $itemId,  the product, category or page id
	* @param string $testPageUrl, the test page url to be displayed on the GWO config form
	* @param string $testType, (multivariate, A/B)
	*
	* @return string the html code of the GWO form
	*/
	public function showPerItemConfigForm($type, $itemId, $testPageUrl='', $testType='multivariate')
	{
		$testUrl = '';

		if($itemId) {
			$GLOBALS['ValidateTestPageUrl'] = $testPageUrl;

			$GLOBALS['ValidateConversionPageUrl'] = $GLOBALS['ShopPathNormal'].'/optimizervalidation.php?id='.$type.'_'.$itemId;
		}
		$optimizerDetails = $this->getPerItemOptimizerDetails($type, $itemId);


		if(strtolower($testType) == 'multivariate') {
			$GLOBALS['OptimizerTestType'] = GetLang('MultivariateExperiment');
		} else {
			$GLOBALS['OptimizerTestType'] = GetLang('ABExperiment');
		}

		//show conversion page url and help for custom conversion pages
		$GLOBALS['HideCustomConversionHelp'] = 'display:none;';
		if(isset($optimizerDetails['optimizer_conversion_page'])) {
			if($optimizerDetails['optimizer_conversion_page'] == 'Custom') {
				$GLOBALS['HideCustomConversionHelp'] = '';
			}
			if(isset($optimizerDetails['optimizer_conversion_url'])) {
				$GLOBALS['ConversionPageURL'] = isc_html_escape($optimizerDetails['optimizer_conversion_url']);
			}
		}


		$GLOBALS['ConversionPageOptions'] = '';
		$optimizer = GetClass('ISC_OPTIMIZER');

		$conversionPages = $optimizer -> conversionPages;
		$conversionPages['Custom'] = '';
		foreach ($conversionPages as $page => $url) {

			$selectOption = '';
			if(isset($optimizerDetails['optimizer_conversion_page'])) {
				if($optimizerDetails['optimizer_conversion_page'] == $page) {
					$selectOption = 'Selected=Selected';
				}
			} elseif(strtolower($page) == 'order') {
				$selectOption = 'Selected=Selected';
			}

			$GLOBALS['ConversionPageOptions'] .= "<option value='".$page."' ".$selectOption.">".GetLang('ConversionPage'.$page)."</option>";
		}

		if(isset($optimizerDetails['optimizer_control_script'])) {
			$GLOBALS['ControlScript'] = isc_html_escape($optimizerDetails['optimizer_control_script']);
		}

		if(isset($optimizerDetails['optimizer_tracking_script'])) {
			$GLOBALS['TrackingScript'] = isc_html_escape($optimizerDetails['optimizer_tracking_script']);
		}

		if(isset($optimizerDetails['optimizer_conversion_script'])) {
			$GLOBALS['ConversionScript'] = isc_html_escape($optimizerDetails['optimizer_conversion_script']);
		}

		if(isset($optimizerDetails['optimizer_conversion_url'])) {
			$GLOBALS['ConversionPageURL'] = isc_html_escape($optimizerDetails['optimizer_conversion_url']);
		} else {
			$GLOBALS['ConversionPageURL'] = '';
		}

		return $this->template->render('Snippets/OptimizerConfigForm.html');
	}

	/**
	* Get the GWO config details for product/category/page test from database
	*
	* @param string $type, the test type(product, category or page)
	* @param int $itemId, the product/category/page id
	*
	* @return array
	*/
	public function getPerItemOptimizerDetails($type, $itemId)
	{
		if(!$type) {
			return array();
		}
		$row = array();
		if($itemId==0) {
			if(isset($_REQUEST['ConversionPage'])) {
				$row['optimizer_conversion_page'] = $_REQUEST['ConversionPage'];
			}
			if(isset($_REQUEST['ControlScript'])) {
				$row['optimizer_control_script'] = $_REQUEST['ControlScript'];
			}
			if(isset($_REQUEST['TrackingScript'])) {
				$row['optimizer_tracking_script'] = $_REQUEST['TrackingScript'];
			}
			if(isset($_REQUEST['ConversionScript'])) {
				$row['optimizer_conversion_script'] = $_REQUEST['ConversionScript'];
			}
			if(isset($_REQUEST['ConversionPageUrl'])) {
				$row['optimizer_conversion_url'] = $_REQUEST['ConversionPageUrl'];
			}
		} else {
			$query = "SELECT *
						FROM [|PREFIX|]optimizer_config
						WHERE optimizer_type='".$GLOBALS['ISC_CLASS_DB']->Quote($type)."'
								AND
								optimizer_item_id = '".$GLOBALS['ISC_CLASS_DB']->Quote($itemId)."'
						";
			$result=$GLOBALS['ISC_CLASS_DB']->Query($query);
			$optimizerDetails = array();
			if(!$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)){
				return array();
			}
		}
		return $row;
	}

	/**
	* Save the GWO config form for Per product/category/page to database
	*
	* @param string $type, the test type(product, category or page)
	* @param int $itemId, the product/category/page id
	*
	*/
	public function savePerItemOptimizerConfig($type, $itemId)
	{
		$configData = array(
			'optimizer_conversion_page'	=> $_REQUEST['ConversionPage'],
			'optimizer_tracking_script'	=> $_REQUEST['TrackingScript'],
			'optimizer_conversion_script' => $_REQUEST['ConversionScript'],
			'optimizer_control_script'	=> $_REQUEST['ControlScript'],
			'optimizer_conversion_url' => '',
		);

		if(isset($_REQUEST['ConversionPageUrl']) && $_REQUEST['ConversionPageUrl'] != '') {
			$configData['optimizer_conversion_url'] = $_REQUEST['ConversionPageUrl'];
		}
		$existingDetails = $this->getPerItemOptimizerDetails($type, $itemId);

		if(!empty($existingDetails) && isset($existingDetails['optimizer_id'])) {
			//update existing settings
			$GLOBALS['ISC_CLASS_DB']->UpdateQuery("optimizer_config", $configData, "optimizer_id='".$GLOBALS['ISC_CLASS_DB']->Quote($existingDetails['optimizer_id'])."'", true);

		} else {
			//insert new settings
			$newData = array(
				'optimizer_type' => $type,
				'optimizer_item_id' => $itemId,
				'optimizer_config_date'	=> time(),
			);
			$configData = array_merge($newData, $configData);

			$GLOBALS['ISC_CLASS_DB']->InsertQuery("optimizer_config", $configData, true);
		}
	}

	public function validateConfigForm()
	{
		if(!isset($_REQUEST['ConversionPage']) || $_REQUEST['ConversionPage'] == '') {
			return GetLang('ChooseConvertionpage');
		}

		if(!isset($_REQUEST['ControlScript']) || $_REQUEST['ControlScript'] == '') {
			return GetLang('EnterControlScript');
		}

		if(!isset($_REQUEST['TrackingScript']) || $_REQUEST['TrackingScript'] == '') {
			return GetLang('EnterTrackingScript');
		}

		if(!isset($_REQUEST['ConversionScript']) || $_REQUEST['ConversionScript'] == '') {
			return GetLang('EnterConversionScript');
		}
		return '';
	}


	/**
	* Delete the GWO config data for Per product/category/page from the database
	*
	* @param string $type, the test type(product, category or page)
	* @param int $itemId, the product/category/page id
	*
	*/
	public function deletePerItemOptimizerConfig($type, $itemIds)
	{
		$itemIdsStr = implode("','", array_map('intval', $itemIds));
		$query = "DELETE FROM [|PREFIX|]optimizer_config
					WHERE optimizer_type = '".$GLOBALS['ISC_CLASS_DB']->Quote($type)."'
					AND optimizer_item_id in ('".$itemIdsStr."')
					";
		$GLOBALS['ISC_CLASS_DB']->Query($query);
	}
}