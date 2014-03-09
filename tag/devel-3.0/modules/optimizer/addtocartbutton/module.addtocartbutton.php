<?php
class OPTIMIZER_ADDTOCARTBUTTON extends ISC_OPTIMIZER
{
	public function __construct()
	{
		// Setup the required variables for the AmazonFps checkout module
		parent::__construct();
		$this->_name = GetLang('AddToCartButton');
		$this->_description = GetLang('AddToCartButtonDescription');
		$this->_testPage = 'products';
		$this->_testType = 'Multivariate';

		$this->_help = GetLang('AddToCartButtonHelp');

	}

	public function insertControlScript()
	{
		$currentPage = $_SERVER["REQUEST_URI"];
		if(!preg_match('/\/'.$this->_testPage.'\//', $currentPage) && !preg_match('/\/'.$this->_testPage.'.php/', $currentPage)) {
			return;
		}

		$GLOBALS['AddToCartButtonControlScript'] = $this->getControlScriptForFrontStore();
		$GLOBALS['AddToCartButtonOptimizerScriptTag'] = '<script>utmx_section("AddToCartButton")</script>';
		$GLOBALS['AddToCartButtonOptimizerNoScriptTag'] = '</noscript>';
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
					<script>utmx_section("AddToCartButton")</script>
						<input type="image" src="{{ IMG_PATH|safe }}/{{ SiteColor|safe }}/AddCartButton.gif" alt="" />
					</noscript>

				';
	}

	public function getTestPageUrl()
	{
		$prodLink = $this->getRandomProductUrl();
		if ($GLOBALS['EnableSEOUrls'] == 1) {
			$prodLink = $prodLink.'?optimizer=addtocartbutton';
		} else {
			$prodLink = $prodLink.'&optimizer=addtocartbutton';
		}
		return $prodLink;
	}
}