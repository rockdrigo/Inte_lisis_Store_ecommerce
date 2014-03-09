<?php
class OPTIMIZER_PRODUCTTABS extends ISC_OPTIMIZER
{
	public function __construct()
	{
		// Setup the required variables for the AmazonFps checkout module
		parent::__construct();
		$this->_name = GetLang('ProductTabs');
		$this->_description = GetLang('ProductTabsDescription');
		$this->_help = GetLang('ProductTabsHelp');
		$this->_testPage = 'products';
		$this->_testType = 'Multivariate';

		$this->_help = GetLang('ProductTabsHelp');

	}

	public function insertControlScript()
	{
		$currentPage = $_SERVER["REQUEST_URI"];
		if(!preg_match('/\/'.$this->_testPage.'\//', $currentPage) && !preg_match('/\/'.$this->_testPage.'.php/', $currentPage)) {
			return;
		}

		$GLOBALS['ProductTabsControlScript'] = $this->getControlScriptForFrontStore();
		$GLOBALS['ProductTabsOptimizerScriptTag'] = '<script>utmx_section("ProductTabs")</script>';
		$GLOBALS['ProductTabsOptimizerNoScriptTag'] = '</noscript>';

	}

	public function insertTrackingScript()
	{
		$currentPage = $_SERVER["REQUEST_URI"];
		if(!preg_match('/\/'.$this->_testPage.'\//', $currentPage) && !preg_match('/\/'.$this->_testPage.'.php/', $currentPage)) {
			return;
		}

		$GLOBALS['OptimizerTrackingScript'] .= $this->getTrackingScriptForFrontStore();
	}

	protected function getTestElement()
	{
		return '
					<script>utmx_section("ProductTabs")</script>
						<ul class="TabNav" id="ProductTabsList">
						</ul>
						<script type="text/javascript">
								var HideProductTabs = 0;
						</script>
					</noscript>

				';
	}

	public function getTestPageUrl()
	{
		$prodLink = $this->getRandomProductUrl();
		if ($GLOBALS['EnableSEOUrls'] == 1) {
			$prodLink = $prodLink.'?optimizer=producttabs';
		} else {
			$prodLink = $prodLink.'&optimizer=producttabs';
		}
		return $prodLink;
	}


}