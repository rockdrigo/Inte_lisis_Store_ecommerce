<?php


require_once(dirname(__FILE__).'/class.module.php');

class ISC_OPTIMIZER extends ISC_MODULE
{
	protected $type = 'optimizer';

	public $conversionPages =  array(
					'NewsLetter'=>'subscribe.php',
					'AccountCreated' => 'login.php?action=save_new_account',
					'Cart' => 'cart.php',
					'Order' => 'finishorder.php',
					'Checkout' => 'checkout.php',
				);

	/**
	 * Check if this optimizer module is enabled or not.
	 *
	 * @return boolean True if enabled, false if not.
	 */
	public function checkEnabled()
	{

		$enableModules = GetConfig('OptimizerMethods');
		if(!empty($enableModules) && in_array($this->GetId(), array_keys($enableModules))) {
			return true;
		}
		else {
			return false;
		}
	}

	public function getModuleDetails()
	{
		$ConfiguredDetails=array();

		$query = "Select * from [|PREFIX|]module_vars where modulename='".$GLOBALS['ISC_CLASS_DB']->Quote($this->GetId())."'";
		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);

		while($var = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$ConfiguredDetails[$var['variablename']] = $var['variableval'];
		}

		return $ConfiguredDetails;
	}


	public function generateConfigFormHTML()
	{
		$FormHTML = "";
		$ConfigFields = $this->GetConfigFields();
		foreach($ConfigFields as $FieldId => $Field) {
			$FormHTML .= "<dt>";
			if($Field['required']) {
				$FormHTML .= "<span class='Required'>*</span> ";
			} else {
				$FormHTML .= "&nbsp;&nbsp;&nbsp;";
			}
			$FormHTML .= isc_html_escape($Field['name']).":";
			$FormHTML .= "</dt><dd>";
			$FormHTML .= $this->_buildformitem($FieldId, $Field, false);
			$FormHTML .= "</dd>";
		}
		return $FormHTML;
	}

	protected function getConfigFields()
	{
		$fields = array(
			'controlscript' => array(
				"name" => GetLang('ControlScript'),
				"type" => "textarea",
				"help" => GetLang('ControlScriptHelp'),
				"default" => '',
				"rows" => '5',
				"class" => 'Field300',
				"required" => true,
			),
			'trackingscript' => array(
				"name" => GetLang('TrackingScript'),
				"type" => "textarea",
				"help" => GetLang('TrackingScriptHelp'),
				"rows" => '5',
				"class" => 'Field300',
				"default" => '',
				"required" => true,
			),
			'conversionscript' => array(
				"name" => GetLang('ConversionScript'),
				"type" => "textarea",
				"help" => GetLang('ConversionScriptHelp'),
				"rows" => '5',
				"class" => 'Field300',
				"default" => '',
				"required" => true,
			),
		);

		$CustomFields = array();
		if(method_exists($this, 'GetCustomConfigFields')) {
			$CustomFields = $this->getCustomConfigFields();
		}

		$ModuleFields = array_merge($fields, $CustomFields);

		//loop through the each fields and set the default value to the data stored in database
		$ConfiguredDetails = $this->getConfiguredDetails();
		if (!empty($ConfiguredDetails)) {
			foreach($ModuleFields as $FieldName => $Field) {
				if(isset($ConfiguredDetails[$FieldName])) {
					$ModuleFields[$FieldName]['default'] = $ConfiguredDetails[$FieldName];
				}
			}
		}

		return $ModuleFields;
	}


	protected function getBuiltInConversionPages()
	{
		return $this->conversionPages;
	}


	protected function getControlScriptForFrontStore()
	{
		$optimizerData = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('OptimizerData');
		if(isset($optimizerData[$this->id]['control_script']) && $optimizerData[$this->id]['control_script'] != '') {
			return $optimizerData[$this->id]['control_script'];
		}
		return '';
	}


	protected function getTrackingScriptForFrontStore()
	{
		$optimizerData = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('OptimizerData');

		if(isset($optimizerData[$this->id]['tracking_script']) && $optimizerData[$this->id]['tracking_script'] != '') {
			return $optimizerData[$this->id]['tracking_script'];
		}
		return '';
	}

	public function insertConversionScript()
	{

		$optimizerData = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('OptimizerData');
		if(!isset($optimizerData[$this->id]['conversion_page']) && $optimizerData[$this->id]['conversion_page'] != '') {

			return;
		}

		$convertionPage = $optimizerData[$this->id]['conversion_page'];

		if(isc_strtolower($convertionPage) == 'custom') {
			return;
		}

		//built in conversion pages.
		$builtInConversionPages = $this->getBuiltInConversionPages();
		if(!isset($builtInConversionPages[$convertionPage])) {
			return;
		}

		$conversionURL = $builtInConversionPages[$convertionPage];

		// some configurations of IIS don't set REQUEST_URI so we fix it here, fixes ISC-537
		if (!isset($_SERVER['REQUEST_URI'])) {
			$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
			if (isset($_SERVER['QUERY_STRING'])) {
				$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
			}
		}


		if(strpos($_SERVER["REQUEST_URI"], $conversionURL) !== false) {

			//if this is not the cart page after product is added to cart,
			if($convertionPage == 'Cart') {
				if (!isset($_SESSION['JustAddedProduct']) || $_SESSION['JustAddedProduct'] =='') {
					return;
				}
			}

			if(isset($optimizerData[$this->id]['conversion_script']) && $optimizerData[$this->id]['conversion_script'] != '') {
				//$GLOBALS['OptimizerConversionScript'] .= $optimizerData[$this->id]['conversion_script'];

				$curScript = $optimizerData[$this->id]['conversion_script'];
				$scripts = $GLOBALS['OptimizerConversionScript'];
				if($scripts != '') {
					$scriptID = preg_replace("/\/goal(\s|.)*/", '', $curScript);
					$scriptID = preg_replace("/(\s|.)*trackPageview\(\"\//", '', $scriptID);

					$scriptPart = 'gwoTracker._trackPageview("/'.$scriptID.'/goal");
}catch(err){}</script>';

					$scripts = str_replace('}catch(err){}</script>',$scriptPart, $scripts);
				} else {
					$scripts = $curScript;
				}

				$GLOBALS['OptimizerConversionScript'] = $scripts;
			}

		}
	}

	public function createValidationFiles($fileType)
	{
		$moduleDetails = $this->getModuleDetails();
		$fileContent = '';
		switch($fileType) {
			case 'test':
				$fileContent .= $moduleDetails['control_script'];
				if(method_exists($this, 'getTestElement')) {
					$fileContent .= $this->getTestElement();
				}
				$fileContent .= $moduleDetails['tracking_script'];
				break;
			case 'variation':
				$fileContent .= $moduleDetails['tracking_script'];
				break;
			case 'conversion':
				$fileContent .= $moduleDetails['conversion_script'];
				break;
		}
		return $fileContent;
	}


	public function getTestPageUrl()
	{
		$optimizerName = str_replace('optimizer_', '', $this->GetId());
		return $GLOBALS['ShopPathNormal'].'/index.php?optimizer='.$optimizerName;
	}

	public function getVariationPageUrl()
	{
		return $GLOBALS['ShopPathNormal'];
	}

	public function getConversionValidateUrl()
	{
		$optimizerName = str_replace('optimizer_', '', $this->GetId());
		return $GLOBALS['ShopPathNormal'].'/optimizervalidation.php?id='.$optimizerName;
	}

	protected function getRandomProductUrl()
	{
		$query = "Select prodname
					From [|PREFIX|]products
					Where prodvisible=1
					limit 1";

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$row = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

		if(isset($row['prodname']) && $row['prodname'] != '') {
			return ProdLink($row['prodname']);
		} else {
			return GetLang('UseAnyProductUrl');
		}
	}

	public function insertControlScript()
	{
		return '';
	}


	public function getModuleDetailsByConversionPage($conversionPages)
	{
		$moduleDetails=array();
		$conversionPages = array_map('isc_strtolower', $conversionPages);

		$optimizerData = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('OptimizerData');
		foreach ($optimizerData as $optimizer) {
			if(in_array(isc_strtolower($optimizer['conversion_page']), $conversionPages)) {
				$moduleDetails[] = $optimizer;
			}
		}
		return $moduleDetails;
	}

	public function getLinkScriptForConversionPage($conversionPages)
	{
		$moduleDetails=array();
		$conversionPages = array_map('isc_strtolower', $conversionPages);
		$optimizerData = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('OptimizerData');
		foreach ($optimizerData as $optimizer) {
			if(in_array(isc_strtolower($optimizer['conversion_page']),$conversionPages)) {
				$trackingScript = $optimizer['tracking_script'];
				$linkScript = preg_replace('/gwoTracker\._trackPageview.*;/', '
					gwoTracker._setAllowLinker(true);
					gwoTracker._setDomainName("none");
				', $trackingScript);
				return $linkScript;
			}
		}
		return '';
	}
}
