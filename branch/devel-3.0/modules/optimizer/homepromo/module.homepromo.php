<?php
class OPTIMIZER_HOMEPROMO extends ISC_OPTIMIZER
{
	public function __construct()
	{
		// Setup the required variables for the AmazonFps checkout module
		parent::__construct();
		$this->_name = GetLang('HomePromo');
		$this->_description = GetLang('HomePromoDescription');
		$this->_testPage = 'index';
		$this->_testType = 'Multivariate';

		$this->_help = GetLang('HomePromoHelp');

	}

	public function insertControlScript()
	{
		if(isset($GLOBALS['PathInfo'][0]) && $this->_testPage == strtolower($GLOBALS['PathInfo'][0])) {
			$GLOBALS['HomePromoControlScript'] = $this->getControlScriptForFrontStore();
			$GLOBALS['HomePromoOptimizerScriptTag'] = '<script>utmx_section("HomePromo")</script>';
			$GLOBALS['HomePromoOptimizerNoScriptTag'] = '</noscript>';
		}
	}

	public function insertTrackingScript()
	{
		if(isset($GLOBALS['PathInfo'][0]) && $this->_testPage == $GLOBALS['PathInfo'][0]) {
			$GLOBALS['OptimizerTrackingScript'] .= $this->getTrackingScriptForFrontStore();
		}

	}

	protected function getTestElement()
	{

		$query = "SELECT
						content
					FROM
						[|PREFIX|]banners
					WHERE
						page = 'home_page'
					Limit 1
					";

		$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
		$bannerContents = '';
		while($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$bannerContents = $row['content'];
		}

		return '
					<script>utmx_section("HomePromo")</script>
				'.
					$bannerContents
				.'
					</noscript>

				';
	}

}