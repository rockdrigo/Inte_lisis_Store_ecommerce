<?php
class OPTIMIZER_CARTCHECKOUTBUTTON extends ISC_OPTIMIZER
{
	public function __construct()
	{
		// Setup the required variables for the AmazonFps checkout module
		parent::__construct();
		$this->_name = GetLang('CartCheckoutButton');
		$this->_description = GetLang('CartCheckoutButtonDescription');
		$this->_testPage = 'cart.php';
		$this->_testType = 'Multivariate';

		$this->_help = GetLang('CartCheckoutButtonHelp');

	}

	public function insertControlScript()
	{
		$currentPage = $_SERVER["REQUEST_URI"];
		if(!preg_match('/'.$this->_testPage.'/', $currentPage)) {
			return;
		}

		$GLOBALS['CartCheckoutButtonControlScript'] = $this->getControlScriptForFrontStore();
		$GLOBALS['CartCheckoutButtonOptimizerScriptTag'] = '<script>utmx_section("CartCheckoutButton")</script>';
		$GLOBALS['CartCheckoutButtonOptimizerNoScriptTag'] = '</noscript>';

	}

	public function insertTrackingScript()
	{
		$currentPage = $_SERVER["REQUEST_URI"];
		if(!preg_match('/'.$this->_testPage.'/', $currentPage)) {
			return;
		}

		$GLOBALS['OptimizerTrackingScript'] .= $this->getTrackingScriptForFrontStore();
	}

	protected function getTestElement()
	{
		return '
					<script>utmx_section("CartCheckoutButton")</script>
						<img src="{{ IMG_PATH|safe }}/{{ SiteColor|safe }}/CheckoutButton.gif" alt="" />
					</noscript>

				';
	}

	public function getTestPageUrl()
	{
		return $GLOBALS['ShopPathNormal'].'/'.$this->_testPage.'?optimizer=cartcheckoutbutton';
	}

}