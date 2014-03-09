<?php
class OPTIMIZER_SINGLEMULTICHECKOUT extends ISC_OPTIMIZER
{
	public function __construct()
	{
		// Setup the required variables for the AmazonFps checkout module
		parent::__construct();
		$this->_name = GetLang('SinglePageVsMultiPageCheckout');
		$this->_description = GetLang('SinglePageVsMultiPageCheckoutDescription');
		$this->_help = GetLang('SinglePageVsMultiPageCheckoutHelp');
		$this->_APage = 'checkout.php';
		$this->_BPage = 'checkout.php?action=multiple';
		$this->_testType = 'AB';
		$this->_testPage = 'checkout';

		$this->_help = GetLang('SinglePageVsMultiPageCheckoutHelp');
	}

	public function insertControlScript()
	{
		$currentPage = $_SERVER["REQUEST_URI"];
		if(!preg_match('/'.$this->_APage.'/', $currentPage) || isset($_GET['action'])) {
			return;
		}
		$GLOBALS['OptimizerControlScript'] = $this->getControlScriptForFrontStore();
		return $GLOBALS['OptimizerControlScript'];
	}

	public function insertTrackingScript()
	{
		$currentPage = $_SERVER["REQUEST_URI"];
		if(!preg_match('/'.$this->_APage.'/', $_SERVER["REQUEST_URI"]) && !preg_match('/'.$this->_BPage.'/', $_SERVER["REQUEST_URI"])) {
			return;
		}
		$GLOBALS['OptimizerTrackingScript'] = $this->getTrackingScriptForFrontStore();
		return $GLOBALS['OptimizerTrackingScript'];
	}

	public function getTestPageUrl()
	{
		return $GLOBALS['ShopPath'].'/'.$this->_APage.'?optimizer=singlemulticheckout';
	}

	public function getVariationPageUrl()
	{
		return $GLOBALS['ShopPath'].'/'.$this->_BPage.'&optimizer=singlemulticheckout';
	}

	public function getConversionValidateUrl()
	{
		$optimizerName = str_replace('optimizer_', '', $this->GetId());
		return $GLOBALS['ShopPath'].'/optimizervalidation.php?id='.$optimizerName;
	}
}