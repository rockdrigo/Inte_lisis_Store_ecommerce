<?php

class ISC_ACCOUNTING_GATEWAY
{
	public function HandlePage()
	{
		$action = "";
		if (isset($_GET["action"])) {
			$action = isc_strtolower($_GET["action"]);
		}

		switch($action) {
			case "showsupport": {
				$this->HandleSupport();
			}
			case "showsupportquickbooks": {
				$this->HandleSupport('quickbooks');
			}
			default: {
				$this->HandleGateway();
			}
		}
	}

	/**
	 * Handle the SOAP request
	 *
	 * Method will call and run the module SOAP handler
	 *
	 * @access private
	 * @return mixed The output from the SOAP handler
	 */
	private function HandleGateway()
	{
		$module = $this->getSelectedModule();

		/**
		 * If we stuff up then just exit here as we don't know what the provider was and so we can't return the error
		 */
		if (!$module) {
			$GLOBALS["ISC_CLASS_LOG"]->LogSystemError("accounting", "Unable to find accounting module for gateway", $_GET);
			exit;
		}

		return $module->handleGateway();
	}

	/**
	 * Handle the support request
	 *
	 * Method will redirct user to the support URL
	 *
	 * @access private
	 * @return void
	 */
	private function HandleSupport($modulename='')
	{
		if (isset($modulename) && $modulename == 'quickbooks') {
			header("Location: https://www.viewkb.com/questions/901/FAQ+and+Troubleshooting+Tips+for+the+Intuit+QuickBooks+Web+Connector+Sync");
		}

		$module = $this->getSelectedModule();

		if (!$module) {
			exit;
		}

		exit;
	}

	/**
	 * Get the selected module
	 *
	 * Method will return the selected module
	 *
	 * @access private
	 * @return object the selected module on success, FALSE if the module could not be found
	 */
	private function getSelectedModule()
	{
		$module = null;

		if (!array_key_exists("accounting", $_GET) || !GetModuleById("accounting", $module, $_GET["accounting"])) {
			return false;
		}

		return $module;
	}
}