<?php
class OPTIMIZER_NEWSLETTERBOX extends ISC_OPTIMIZER
{
	public function __construct()
	{
		// Setup the required variables for the AmazonFps checkout module
		parent::__construct();
		$this->_name = GetLang('NewsletterBox');
		$this->_description = GetLang('NewsletterBoxDescription');
		$this->_testPage = 'all';
		$this->_testType = 'Multivariate';

		$this->_help = GetLang('NewsletterBoxHelp');
	}

	public function insertControlScript()
	{
		$GLOBALS['NewsletterBoxControlScript'] = $this->getControlScriptForFrontStore();
		$GLOBALS['NewsletterHeaderOptimizerScriptTag'] = '<script>utmx_section("NewsletterHeader")</script>';

		$GLOBALS['NewsletterButtonOptimizerScriptTag'] = '<script>utmx_section("NewsletterButton")</script>';

		$GLOBALS['NewsletterBoxOptimizerNoScriptTag'] = '</noscript>';

	}

	public function insertTrackingScript()
	{
		$GLOBALS['OptimizerTrackingScript'] .= $this->getTrackingScriptForFrontStore();
	}

	protected function getTestElement()
	{
		return '
					<script>utmx_section("NewsletterBox")</script>
						Our Newsletter
					</noscript>

					<script>utmx_section("NewsletterBox")</script>
						<input type="image" src="{{ IMG_PATH|safe }}/{{ SiteColor|safe }}/NewsletterSubscribe.gif" value="{% lang Subscribe %}" class="Button" />
					</noscript>

				';
	}
}