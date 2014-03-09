<?php

abstract class ISC_ACCOUNTING extends ISC_MODULE
{
	const refTable = "[|PREFIX|]accountingref";
	const refTableSm = "accountingref";
	const defaultLockLease = 7200;
	const setLogDebug = false;

	protected $type;

	public function __construct()
	{
		$this->settingsJS = array(
			"OnLoad" => array(),
			"OnSubmit" => array(),
			"Exec" => array()
		);

		$this->type = "accounting";

		parent::__construct();
	}

	/**
	 * Logging function
	 *
	 * Method will handle all the logging functionality
	 *
	 * @access private
	 * @param string $type The log type ("debug", "error" or "success")
	 * @param string $summary The log summary
	 * @param string $message The optional log message. Can use "db" to read from the error message from the database class
	 * @return void
	 */
	final private function logMessage($type, $subject, $message='')
	{
		$validTypes = array (
			"success" => "LogSystemSuccess",
			"error" => "LogSystemError",
			"warning" => "LogSystemWarning",
			"debug" => "LogSystemDebug"
		);

		if (trim($type) == '' || !array_key_exists($type, $validTypes)) {
			return;
		}

		if (is_array($subject) && isset($subject[0]) && isset($subject[1])) {
			$message = $subject[1];
			$subject = $subject[0];
		}

		$logMethod = $validTypes[$type];

		if (is_string($message) && isc_strtolower(trim($message)) == "db") {
			$message = $GLOBALS["ISC_CLASS_DB"]->getErrors();
		} else if (!is_scalar($message)) {
			$message = print_r($message, true);
		}

		$GLOBALS["ISC_CLASS_LOG"]->$logMethod(array("accounting", $this->getName()), $subject, $message);

		/**
		 * If this is an error then also set the parent SetError() method
		 */
		if ($type == "error") {
			$this->SetError($message);
		}
	}

	/**
	 * Log an error message
	 *
	 * Method will log an error message
	 *
	 * @access public
	 * @param string $summary The log summary
	 * @param string $message The optional log message
	 * @return void
	 */
	final public function logError($subject, $message='')
	{
		return $this->logMessage("error", $subject, $message);
	}

	/**
	 * Log a warning message
	 *
	 * Method will log a warning message
	 *
	 * @access public
	 * @param string $summary The log summary
	 * @param string $message The optional log message
	 * @return void
	 */
	final public function logWarning($subject, $message='')
	{
		return $this->logMessage("warning", $subject, $message);
	}

	/**
	 * Log a success message
	 *
	 * Method will log a success message
	 *
	 * @access public
	 * @param string $summary The log summary
	 * @param string $message The optional log message
	 * @return void
	 */
	final public function logSuccess($subject, $message='')
	{
		return $this->logMessage("success", $subject, $message);
	}

	/**
	 * Log a debug message
	 *
	 * Method will log a debug message
	 *
	 * @access public
	 * @param string $summary The log summary
	 * @param string $message The optional log message
	 * @return void
	 */
	final public function logDebug($subject, $message='')
	{
		if (!self::setLogDebug) {
			return true;
		}

		return $this->logMessage("debug", $subject, $message);
	}

	/**
	 * Check to see if the module is enabled
	 *
	 * Method will to see if the module is enabled
	 *
	 * @access public
	 * @return bool TRUE if the module is enabled, FALSE if not
	 */
	public function CheckEnabled()
	{
		$accounting_methods = explode(',', GetConfig("AccountingMethods"));
		if (in_array($this->getid(), $accounting_methods)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Build the HTML property sheet
	 *
	 * Method will build and return the HTML property sheet for the module
	 *
	 * @access public
	 * @param int $tabId The tab ID that the property sheet will be displayed in
	 * @return string The HMTL property sheet
	 */
	public function GetPropertiesSheet($tabId, $customVars=array())
	{
		$this->PreparePropertiesSheet($tabId, 'ShipperId', 'AccountingJavaScript', 'accounting_selected', $customVars);

		// A hook for catching the validation script that the parent 'module' class defines
		if (isset($GLOBALS["ValidationJavascript"]) && $GLOBALS["ValidationJavascript"] !== '') {
			$this->setOnSubmitJS($GLOBALS["ValidationJavascript"]);
		}

		// Now parse all the JS hooks
		foreach ($this->settingsJS as $type => $js) {
			$GLOBALS["Accounting" . $type . "JavaScript"] = "";

			if (empty($js)) {
				continue;
			}

			$GLOBALS["Accounting" . $type . "JavaScript"] = implode("\n", $js);
		}

		return Interspire_Template::getInstance('admin')->render('module.propertysheet.tpl');
	}

	/**
	 * Set the accounting form exec javascript code
	 *
	 * Method will set/append the exec javascript code
	 *
	 * @access protected
	 * @param string $js The javascript code to set/append
	 * @param bool $append TRUE to append or FALSE to set. Default is TRUE
	 * @return bool TRUE if the js code was append/set, FALSE on error
	 */
	protected function setExecJS($js, $append=true)
	{
		return $this->setJS("Exec", $js, $append);
	}

	/**
	 * Set the accounting form onload javascript code
	 *
	 * Method will set/append the onload javascript code
	 *
	 * @access protected
	 * @param string $js The javascript code to set/append
	 * @param bool $append TRUE to append or FALSE to set. Default is TRUE
	 * @return bool TRUE if the js code was append/set, FALSE on error
	 */
	protected function setOnLoadJS($js, $append=true)
	{
		return $this->setJS("OnLoad", $js, $append);
	}

	/**
	 * Set the accounting form onsubmit javascript code
	 *
	 * Method will set/append the onsubmit javascript code
	 *
	 * @access protected
	 * @param string $js The javascript code to set/append
	 * @param bool $append TRUE to append or FALSE to set. Default is TRUE
	 * @return bool TRUE if the js code was append/set, FALSE on error
	 */
	protected function setOnSubmitJS($js, $append=true)
	{
		return $this->setJS("OnSubmit", $js, $append);
	}

	/**
	 * Set/Append the setting spage javascript code
	 *
	 * Method will set/append the settings page javascript code
	 *
	 * @access private
	 * @param string $type The type of js code ("exec", "onload" or "onsubmit")
	 * @param string $js The javascript code to set/append
	 * @param bool $append TRUE to append or FALSE to set. Default is TRUE
	 * @return bool TRUE if the js code was append/set, FALSE on error
	 */
	private function setJS($type, $js, $append=true)
	{
		if (trim($type) == '' || !array_key_exists($type, $this->settingsJS)) {
			return false;
		}

		if ($append) {
			$this->settingsJS[$type][] = $js;
		} else {
			$this->settingsJS[$type] = array($js);
		}

		return true;
	}

	/**
	 * Get a setup variable
	 *
	 * Method will return a setup variable that is stored in the database
	 *
	 * @access public
	 * @param string $name The setup variable name
	 * @param bool $fullRecord TRUE to return the record array, FALSE just for the value. Default is FALSE
	 * @return mixed The setup varaible value if found, NULL if not found
	 */
	public function getSetupVariable($name, $fullRecord=false)
	{
		if (trim($name) == '') {
			return null;
		}

		$query = "SELECT *
					FROM [|PREFIX|]module_vars
					WHERE modulename='" . $GLOBALS["ISC_CLASS_DB"]->Quote($this->getId()) . "'
						AND variablename='" . $GLOBALS["ISC_CLASS_DB"]->Quote("setup_" . $name) . "'";

		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

		if ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
			if (!$fullRecord) {
				return $row["variableval"];
			} else {
				return $row;
			}
		}

		return false;
	}

	/**
	 * Set a setup variable
	 *
	 * Method will set a setup variable that is stored in the database
	 *
	 * @access protected
	 * @param string $name The setup variable name
	 * @param mixed $val The value/array of values to store as the setup variable
	 * @return bool TRUE if the setup varaible was set successfully, FALSE otherwise
	 */
	protected function setSetupVariable($name, $val)
	{
		if (trim($name) == '') {
			return false;
		}

		$where = "WHERE modulename='" . $GLOBALS["ISC_CLASS_DB"]->Quote($this->getId()) . "'
					AND variablename='" . $GLOBALS["ISC_CLASS_DB"]->Quote("setup_" . $name) . "'";

		$GLOBALS["ISC_CLASS_DB"]->DeleteQuery("module_vars", $where);

		$savedata = array(
			"modulename" => $this->getId(),
			"variablename" => "setup_" . $name,
			"variableval" => $val,
		);

		$rtn = $GLOBALS["ISC_CLASS_DB"]->InsertQuery("module_vars", $savedata);

		return ($rtn !== false);
	}

	/**
	 * Set the import session
	 *
	 * Method will set/create the import session. Will use a cache file instead of a session as SOAP does some
	 * funny things will sessions, depending on how the server is setup
	 *
	 * @access public
	 * @param array $data The optional data array to set. Default is empty array
	 * @return bool TRUE if the sesison was created, FALSE on error
	 */
	public function setImportSession($data=array())
	{
		if (!is_array($data)) {
			$this->logError("Invalid session data sent to setImportSession", $data);
			return false;
		}

		$sessdata = $GLOBALS["ISC_CLASS_DATA_STORE"]->Read("AccountingImportSessData");
		$key = $this->getId();

		$sessdata[$key] = $data;

		if ($GLOBALS["ISC_CLASS_DATA_STORE"]->Save("AccountingImportSessData", $sessdata) === false) {
			return false;
		}

		$GLOBALS["ISC_CLASS_DATA_STORE"]->Reload("AccountingImportSessData");
		return true;
	}

	/**
	 * Get the import session data
	 *
	 * Method will return the import session data
	 *
	 * @access public
	 * @param bool $falseIfNotSet TRUE to return FALSE if the session is not set. Default is FALSE
	 * @return array The import session data if found, empty array if not set
	 */
	public function getImportSession($falseIfNotSet=false)
	{
		$sessdata = $GLOBALS["ISC_CLASS_DATA_STORE"]->Read("AccountingImportSessData");
		$key = $this->getId();

		if (is_array($sessdata) && array_key_exists($key, $sessdata)) {
			return $sessdata[$key];
		}

		if ($falseIfNotSet) {
			return false;
		}

		return array();
	}

	/**
	 * Unset the import session
	 *
	 * Method will unset the import session
	 *
	 * @access public
	 * @return bool TRUE if the session was unset OR if it did not exist, FALSE on error
	 */
	public function unsetImportSession()
	{
		$sessdata = $GLOBALS["ISC_CLASS_DATA_STORE"]->Read("AccountingImportSessData");
		$key = $this->getId();

		if (!is_array($sessdata) || !array_key_exists($key, $sessdata)) {
			return true;
		}

		unset($sessdata[$key]);

		if ($GLOBALS["ISC_CLASS_DATA_STORE"]->Save("AccountingImportSessData", $sessdata) === false) {
			return false;
		}

		$GLOBALS["ISC_CLASS_DATA_STORE"]->Reload("AccountingImportSessData");

		return true;
	}

	/**
	 * Set a value in the import session
	 *
	 * Method is basically a wrapper for getting the import session, adding in the $key => $value pair and then
	 * saving it
	 *
	 * @access public
	 * @param mixed $key The session key OR an associative array
	 * @param mixed $value The option session key value if $key is a string
	 * @return bool TRUE if the session $key=>$value was set, FALSE if not
	 */
	public function setImportSessionValue($key, $value='')
	{

		if (!is_array($key) && !is_string($key)) {
			return false;
		}

		if (is_string($key)) {
			if (trim($key) == '') {
				return false;
			}
			$key = array($key => $value);
		}

		/**
		 * Force the user to use $this->setImportSession() BEFORE calling this function
		 */
		$sessdata = $this->getImportSession(true);

		if (!is_array($sessdata)) {
			return false;
		}

		foreach ($key as $k => $v) {
			$sessdata[$k] = $v;
		}

		return $this->setImportSession($sessdata);
	}

	/**
	 * Get a value within the import session
	 *
	 * Method is basically a wrapper for getting the import session and returning the value that corresponds to
	 * aray key $key
	 *
	 * @access public
	 * @param string $key The session key
	 * @return mixed The selected value on success, NULL if not found, FALSE on error
	 */
	public function getImportSessionValue($key)
	{
		if (trim($key) == '') {
			return false;
		}

		/**
		 * Force the user to call $this->setImportSession() first
		 */
		$sessdata = $this->getImportSession(true);

		if (!is_array($sessdata)) {
			return false;
		}

		if (!array_key_exists($key, $sessdata)) {
			return null;
		}

		return $sessdata[$key];
	}

	/**
	 * Check to see if the lock lease entry exists and is still valid
	 *
	 * Method will check to see if the lease entry exists and is still valid. If $clean is TRUE, then also check if it is
	 * stale and return FALSE if it is
	 *
	 * @access public
	 * @param bool $clean TRUE to check if the lock is stale, FALSE not to. Default is FALSE
	 * @return bool TRUE if the lock lease entry exists and is still valid, FALSE otherwise
	 */
	public function checkImportSessionLockLease($clean=true)
	{
		$lease = $this->getImportSessionValue("LockLease");

		if (trim($lease) == '' || ($clean && $lease <= time())) {
			return false;
		}

		return true;
	}

	/**
	 * Set a lock lease entry
	 *
	 * Function will create a lock lease entry. If an entry already exists then function will return FALSE without modifying the
	 * session cache file. The second argument $expire is a timestamp of the expiry date. The default expiry timestame is 2 hours
	 *
	 * @access protected
	 * @param string $lockKey The lock key to set
	 * @param int $expireIn Optional seconds to expire in. Default is 2 hours (self::defaultLockLease)
	 * @return bool TRUE if the entry was created, FALSE otherwise
	 */
	protected function setImportSessionLockLease($lockKey, $expireIn='')
	{
		if (trim($lockKey) == '') {
			return false;
		}

		if (trim($expireIn) == '') {
			$expireIn = time() + self::defaultLockLease;
		}

		if (!isId($expireIn) || $expireIn <= time()) {
			return false;
		}

		$sessdata = array(
			"LockKey" => $lockKey,
			"LockLease" => $expireIn
		);

		if (!$this->setImportSessionValue($sessdata)) {
			return false;
		}

		return true;
	}

	/**
	 * Set the error for the session
	 *
	 * Method is a wrapper for setting the "LastError" valus in the import session. If $logAlso is TRUE then
	 * the error message $error will be passed to $this->logError() also
	 *
	 * @access public
	 * @param mixed $error The error message string/array
	 * @param bool $logAlso TRUE to also pass the error message to $this->logError(). Default is TRUE
	 * @return bool TRUE if the error message was successfully saved, FASLE on error
	 */
	public function setImportSessionError($error, $logAlso=true)
	{
		if (!is_array($error) && !is_string($error)) {
			return false;
		}

		if (is_array($error) && isset($error[0])) {
			$message = $error[0];
		} else if (is_string($error)) {
			$message = $error;
		} else {
			return false;
		}

		if (trim($message) == '') {
			return false;
		}

		$this->setImportSessionValue("LastError", $message);

		if ($logAlso) {
			$this->logError($error);
		}

		return true;
	}

	/**
	 * Get the saved session error message
	 *
	 * Method will return the saved session error message
	 *
	 * @access public
	 * @return string The error message on success, FASLE if no error message was set
	 */
	public function getImportSessionError()
	{
		$error = $this->getImportSessionValue("LastError");

		if (!$error || is_null($error) || trim($error) == '') {
			return false;
		}

		return $error;
	}

	/**
	 * Build the spool data record for saving
	 *
	 * Method will build the spool record for saving. This method should be extended for each accounting module
	 *
	 * @access protected
	 * @param int $spoolId The spool ID
	 * @param string $type The node type (customer, product, order, etc)
	 * @param string $service The node service. This is module dependent
	 * @param mixed $node The node ID/record array
	 * @param int $parentSpoolId The optional parent node ID. Default is 0 (no parent)
	 * @return array The spool data array
	 */
	protected function buildSpool2Set($spoolId, $type, $service, $node, $parentSpoolId=0)
	{
		if (!isId($spoolId) || trim($type) == '' || trim($service) == '' || (!isId($node) && !is_array($node))) {
			$xargs = func_get_args();
			$this->logError("Unable to build spool for saving", $xargs);
			return false;
		}

		$spool = array(
			"id" => $spoolId,
			"parentId" => $parentSpoolId,
			"service" => $service,
			"nodeType" => $type,
			"nodeId" => 0,
			"nodeData" => "",
			"response" => array(),
			"referenceId" => 0,
			"referenceData" => array(),
			"errNo" => 0,
			"errMsg" => "",
			"children" => array()
		);

		if (isId($node)) {
			$spool["nodeId"] = $node;
		} else {
			$spool["nodeData"] = $node;
		}

		return $spool;
	}

	/**
	 * Build the spool data record for retrieving
	 *
	 * Method will build the spool record for retrieving. This method should be extended for each accounting module
	 *
	 * @access protected
	 * @param int $spool The saved spool array
	 * @return array The spool data array
	 */
	protected function buildSpool2Get($spool)
	{
		if (!is_array($spool)) {
			$xargs = func_get_args();
			$this->logError("Failed to build spool for retrieving (arguments)", $xargs);
			return false;
		}

		if (isId($spool["nodeId"])) {
			$nodeData = $this->getSpoolNodeData($spool["nodeType"], $spool["nodeId"]);

			if (is_array($nodeData)) {
				$spool["nodeData"] = $nodeData;
			}
		} else if (is_array($spool["nodeData"])) {
			$spool["nodeId"] = $this->getSpoolNodeId($spool["nodeType"], $spool["nodeData"]);
		}

		if (isId($spool["nodeId"])) {
			$reference = $this->getReference($spool["nodeType"], '', '', $spool["nodeId"], false);
		} else {
			$reference = $this->getReference($spool["nodeType"], $spool["nodeData"], '', '', false);
		}

		if (is_array($reference)) {
			$spool["referenceId"] = $reference["accountingrefid"];
			$spool["referenceData"] = $reference["accountingrefvalue"];
		}

		/**
		 * Do we need to build the children?
		 */
		$children = $this->getSpoolChildren($spool["id"]);

		if (is_array($children) && !empty($children)) {
			$spool["children"] = $children;
		}

		return $spool;
	}

	/**
	 * Set an accounting spool record
	 *
	 * Method will create an accounting spool record
	 *
	 * @access protected
	 * @param string $type The node type (customer, product, order, etc)
	 * @param string $service The node service. This is module dependent
	 * @param mixed $node The node ID/record array
	 * @param int $parentSpoolId The optional parent node ID. Default is 0 (no parent)
	 * @return int The spool record ID on success, FALSE if failed
	 */
	protected function setSpool($type, $service, $node, $parentSpoolId=0)
	{
		if (trim($type) == '' || trim($service) == '' || (!isId($node) && !is_array($node))) {
			$xargs = func_get_args();
			$this->logError("Failed to set spool (arguments)", $xargs);
			return false;
		}

		$spoolRecords = $this->getImportSessionValue("SpoolRecords");
		$spoolKeys = array_keys($spoolRecords);

		if (empty($spoolKeys)) {
			$spoolId = 1;
		} else {
			$spoolId = (int)$spoolKeys[count($spoolKeys)-1] + 1;
		}

		$spool = $this->buildSpool2Set($spoolId, $type, $service, $node, $parentSpoolId);

		if (!is_array($spool)) {
			$xargs = func_get_args();
			$this->logError("Unable to set spool (being built)", $xargs);
			return false;
		}

		$spoolRecords[$spoolId] = $spool;

		if (!$this->setImportSessionValue("SpoolRecords", $spoolRecords)) {
			$this->logError("Unable to save spool data in " . __FUNCTION__, $spool);
			return false;
		}

		return $spoolId;
	}

	/**
	 * Set the spool response
	 *
	 * Method will set the spool response. This method is usually called when handling the response from a processed spool
	 *
	 * @access public
	 * @param mixed $spool The spool ID/array
	 * @param mixed $response The response from the processed spool. Response will be serialized
	 * @return bool TRUE if the response was saved, FALSE on error
	 */
	public function setSpoolResponse($spool, $response)
	{
		$spoolId = 0;

		if (is_array($spool)) {
			$spoolId = $spool["id"];
		} else {
			$spoolId = $spool;
		}

		if (!isId($spoolId)) {
			$this->logError("Unable to find spool ID", $spool);
			return false;
		}

		$spoolRecords = $this->getImportSessionValue("SpoolRecords");

		if (!array_key_exists($spoolId, $spoolRecords)) {
			return true;
		}

		$spoolRecords[$spoolId]["response"] = $response;

		if (!$this->setImportSessionValue("SpoolRecords", $spoolRecords)) {
			$this->logError("Unable to save spool data in " . __FUNCTION__, $spool);
			return false;
		}

		return true;
	}

	/**
	 * Set the spool error
	 *
	 * Method will set the spool error
	 *
	 * @access public
	 * @param mixed $spool The spool ID/array
	 * @param int $errno The error number
	 * @param string $errmsg The error message
	 * @return bool TRUE if the error was set, FALSE on error
	 */
	public function setSpoolError($spool, $errno, $errmsg)
	{
		$spoolId = 0;

		if (is_array($spool)) {
			$spoolId = $spool["id"];
		} else {
			$spoolId = $spool;
		}

		if (!isId($spoolId)) {
			$this->logError("Unable to find spool ID in " . __FUNCTION__, $spool);
			return false;
		}

		if (!isId($errno) || trim($errmsg) == '') {
			$xargs = func_get_args();
			$this->logError("Missing/Invalid error number or message", $xargs);
			return false;
		}

		$spoolRecords = $this->getImportSessionValue("SpoolRecords");

		if (!array_key_exists($spoolId, $spoolRecords)) {
			return true;
		}

		$spoolRecords[$spoolId]["errMsg"] = $errmsg;
		$spoolRecords[$spoolId]["errNo"] = (int)$errno;

		if (!$this->setImportSessionValue("SpoolRecords", $spoolRecords)) {
			$this->logError("Unable to save spool data in " . __FUNCTION__, $spool);
			return false;
		}

		return true;
	}

	/**
	 * Unset the spool error
	 *
	 * Method will unset the spool error message. This is used for parent spools that have failed but in the end their
	 * child spools have corrected to problem
	 *
	 * @access public
	 * @param mixed $spool The spool ID/array
	 * @return bool TRUE if the error message was removed, FALSE on error
	 */
	public function unsetSpoolError($spool)
	{
		$spoolId = 0;

		if (is_array($spool)) {
			$spoolId = $spool["id"];
		} else {
			$spoolId = $spool;
		}

		if (!isId($spoolId)) {
			$this->logError("Unable to find spool ID in " . __FUNCTION__, $spool);
			return false;
		}

		$spoolRecords = $this->getImportSessionValue("SpoolRecords");

		if (!array_key_exists($spoolId, $spoolRecords)) {
			return true;
		}

		$spoolRecords[$spoolId]["errMsg"] = "";
		$spoolRecords[$spoolId]["errNo"] = 0;

		if (!$this->setImportSessionValue("SpoolRecords", $spoolRecords)) {
			$this->logError("Unable to save spool data in " . __FUNCTION__, $spool);
			return false;
		}

		return true;
	}

	/**
	 * Get an accounting spool object
	 *
	 * Method will get an accounting spool object
	 *
	 * @access public
	 * @param mixed $spoolId The spoolid ID OR if $type is not empty then $id will be the spoolnodeid
	 *                       OR the record array that is kept in the SpoolRecords session data
	 * @param string $type The optional node type (customer, product, order, etc)
	 * @return array The spool array on success, FALSE if the record was not found
	 */
	public function getSpool($spoolId, $type="")
	{
		if (is_array($spoolId)) {
			$spoolId = $spoolId["id"];
		}

		if (!isId($spoolId)) {
			return false;
		}

		$spoolRecords = $this->getImportSessionValue("SpoolRecords");
		$spool = null;

		if (!is_array($spoolRecords) || empty($spoolRecords)) {
			return false;
		}

		if ($type == "") {
			if (!array_key_exists($spoolId, $spoolRecords)) {
				return false;
			} else {
				$spool = $spoolRecords[$spoolId];
			}
		} else {
			foreach ($spoolRecords as $record) {
				if ($record["nodeType"] == $spoolId && $record["nodeType"] == $type) {
					$spool = $record;
					break;
				}
			}
		}

		if (!is_array($spool)) {
			return false;
		}

		/**
		 * Build it up (add in the node data and reference data)
		 */
		return $this->buildSpool2Get($spool);
	}

	/**
	 * Get the spool node data
	 *
	 * Method will find the spool node data based upon the type $type and the node ID $nodeId
	 *
	 * @access protected
	 * @param string $type The node type (customer, product, order, etc)
	 * @param int $nodeId The node ID (customerid, productid, orderid, etc)
	 * @return array The node data array on success, FALSE if the node data could not be found
	 */
	protected function getSpoolNodeData($type, $nodeId)
	{
		$_entityCache = array();

		if (trim($type) == '' || !isId($nodeId)) {
			return false;
		}

		if (!array_key_exists(strtolower($type), $_entityCache)) {
			$className = "ISC_ENTITY_" . strtoupper($type);

			if (!class_exists($className)) {
				return false;
			}

			$_entityCache[strtolower($type)] = new $className();
		}

		$toReturn = $_entityCache[strtolower($type)]->get($nodeId);

		if(strtolower($type) == 'order'){
			$query = "
				SELECT *
				FROM [|PREFIX|]order_addresses a
				LEFT JOIN [|PREFIX|]order_shipping s ON (s.order_address_id=a.id)
				WHERE a.order_id=" . $nodeId . ' LIMIT 1';
			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			$row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result);
			foreach($row as $key=>$value){
				switch ($key) {
					case 'first_name' :
						$toReturn['ordshipfirstname'] = $value;
						break;
					case 'last_name' :
						$toReturn['ordshiplastname'] = $value;
						break;
					case 'company' :
						$toReturn['ordshipcompany'] = $value;
						break;
					case 'address_1' :
						$toReturn['ordshipstreet1'] = $value;
						break;
					case 'address_2' :
						$toReturn['ordshipstreet2'] = $value;
						break;
					case 'city' :
						$toReturn['ordshipcity'] = $value;
						break;
					case 'zip' :
						$toReturn['ordshipzip'] = $value;
						break;
					case 'country' :
						$toReturn['ordshipcountry'] = $value;
						break;
					case 'country_iso2' :
						$toReturn['ordshipcountrycode'] = $value;
						break;
					case 'country_id' :
						$toReturn['ordshipcountryid'] = $value;
						break;
					case 'state' :
						$toReturn['ordshipstate'] = $value;
						break;
					case 'state_id' :
						$toReturn['ordshipstateid'] = $value;
						break;
					case 'email' :
						$toReturn['ordshipemail'] = $value;
						break;
					case 'phone' :
						$toReturn['ordshipphone'] = $value;
						break;
					default:
						break;
				}
			}
		}
		return $toReturn;
	}

	/**
	 * Get the spool node ID
	 *
	 * Method will find the spool node ID based upon the type $type and the node data $node
	 *
	 * @access protected
	 * @param string $type The node type (customer, product, order, etc)
	 * @param array $node The node array data
	 * @return int The node ID on success, FALSE if the node ID could not be found
	 */
	protected function getSpoolNodeId($type, $node)
	{
		if (trim($type) == '' || !is_array($node)) {
			return false;
		}

		$key = '';

		switch (isc_strtolower(trim($type))) {
			case "customer":
				$key = "customerid";
				break;

			case "product":
				$key = "productid";
				break;

			case "productvariation":
				$key = "combinationid";
				break;

			case "order":
				$key = "orderid";
				break;

			default:
				return false;
				break;
		}

		if ($key == '' || !array_key_exists($key, $node)) {
			return false;
		}

		return $node[$key];
	}

	/**
	 * Get the current spool
	 *
	 * Method will return the current spool
	 *
	 * @access public
	 * @return array The current spool on success, NULL if not found, FALSE on error
	 */
	public function getCurrentSpool()
	{
		$currentSpoolId = $this->getImportSessionValue("CurrentSpool");

		if (isId($currentSpoolId) || is_array($currentSpoolId)) {
			return $this->getSpool($currentSpoolId);
		}

		return null;
	}

	/**
	 * Get the next spool
	 *
	 * Method will get the next spool
	 *
	 * @param array $spool The optional current spool. Default will call getCurrentSpool() to get the current spool
	 * @return mixed The next spool object if successfully found, NULL if there is not more spools, FALSE on error
	 */
	public function getNextSpool($spool=null)
	{
		if (!is_array($spool)) {
			$spool = $this->getCurrentSpool();
		}

		if (!is_array($spool)) {
			return false;
		}

		$this->logDebug("Looking for next spool for SpoolID: " . $spool["id"], $spool);

		$nextSpoolId = 0;

		/**
		 * If this spool has kids then check to see if the last kid has been executed or not
		 */
		if (is_array($spool["children"]) && !empty($spool["children"])) {
			$completedSpoolIdx = $this->getImportSessionValue("CompletedSpools");
			$lastKid = end($spool["children"]);

			if (!in_array($lastKid["id"], $completedSpoolIdx)) {
				$nextSpoolId = $lastKid["id"];
			}
		}

		/**
		 * Else if the current spool has a parent ID then use that as the next one
		 */
		if (!isId($nextSpoolId) && isId($spool["parentId"])) {
			$nextSpoolId = $spool["parentId"];
		}

		/**
		 * Last resort which is to find the next spool in the list
		 */
		if (!isId($nextSpoolId)) {
			$completedSpoolIdx = $this->getImportSessionValue("CompletedSpools");
			$spoolRecords = $this->getImportSessionValue("SpoolRecords");

			foreach (array_keys($spoolRecords) as $tmpSpoolId) {
				if ($tmpSpoolId > $spool["id"] && !in_array($tmpSpoolId, $completedSpoolIdx)) {
					$nextSpoolId = $tmpSpoolId;
					break;
				}
			}
		}

		$this->logDebug("The next spool ID for SpoolID: " . $spool["id"], $nextSpoolId);

		if (!isId($nextSpoolId)) {
			return null;
		}

		$nextSpool = $this->getSpool($nextSpoolId);

		$this->logDebug("The next spool for SpoolID: " . $spool["id"], $nextSpool);

		return $nextSpool;
	}

	/**
	 * Set the next current spool
	 *
	 * Method will set the next spool as the current spool
	 *
	 * @access public
	 * @param array $spool The optional current spool. Default will call getCurrentSpool() to get the current spool
	 * @param int $nextSpoolId The optional next spoolID. Default will calculate it based upon the current spool
	 * @return mixed The next spool array if successfully found and set, NULL if there is not more spools, FALSE on error
	 */
	public function setNextSpool($spool=null, $nextSpoolId=0)
	{
		if (!is_array($spool)) {
			$spool = $this->getCurrentSpool();
		}

		if (!is_array($spool)) {
			return false;
		}

		/**
		 * If we did not receive the next spool ID (most probably) then go find it
		 */
		if (!isId($nextSpoolId)) {
			$nextSpool = $this->getNextSpool($spool);
		} else {
			$nextSpool = $this->getSpool($nextSpoolId);
		}

		if (!is_array($nextSpool)) {
			return null;
		}

		/**
		 * Now update the records to set the next current record
		 */
		$this->setImportSessionValue("CurrentSpool", $nextSpool);

		$completedSpools = $this->getImportSessionValue("CompletedSpools");
		$completedSpools[] = $spool["id"];
		$completedSpools = array_unique($completedSpools);
		$this->setImportSessionValue("CompletedSpools", $completedSpools);

		return $nextSpool;
	}

	/**
	 * Get a list of spool records
	 *
	 * Method will return a list of accounting spool records
	 *
	 * @access proetcted
	 * @param int $parentSpoolId The optional parent node ID. Default is empty (no parent)
	 * @param int $start The optional starting offset. If minus number then reverse ordering. Default is 0
	 * @param int $limit The optional limit range. Default is -1 (unlimited)
	 * @return array An array of formatted accounting spool records
	 */
	protected function getSpoolList($parentSpoolId='', $start=0, $limit=-1)
	{
		if (isId($parentSpoolId)) {
			$selected = array();
			$spoolRecords = $this->getImportSessionValue("SpoolRecords");

			foreach ($spoolRecords as $spoolId => $record) {
				if ($record["parentId"] !== $parentSpoolId) {
					continue;
				}

				$selected[$spoolId] = $record;
			}
		} else {
			$selected = $this->getImportSessionValue("SpoolRecords");
		}

		if ($start !== 0 || $limit > 0) {
			$selected = array_slice($selected, $start, $limit, true);
		}

		foreach (array_keys($selected) as $spoolId) {
			$selected[$spoolId] = $this->getSpool($selected[$spoolId]);
		}

		return $selected;
	}

	/**
	 * Set a child spool
	 *
	 * Method will create a child spool record
	 *
	 * @access public
	 * @param int $parentSpoolId The parent spool ID
	 * @param string $type The node type (customer, product, order, etc)
	 * @param string $service The node service. This is module dependent
	 * @param array $node The node record array
	 * @return int The child spool record ID on success, FALSE if failed
	 */
	public function setChildSpool($parentSpoolId, $type, $service, $node)
	{
		if (!isId($parentSpoolId)) {
			$this->logError("Failed to set child spool", $parentSpoolId);
			return false;
		}

		return $this->setSpool($type, $service, $node, $parentSpoolId);
	}

	/**
	 * Get the current spool count
	 *
	 * Method will return the current spool count
	 *
	 * @access public
	 * @param int $parentSpoolId The option parent spool ID
	 * @param bool $validOnly TRUE to only count valid spools (non disabled and non errored). Default is TRUE
	 * @param bool $pendingOnly TRUE to only count the pending spools (status of 0). Default is TRUE
	 * @param bool $executedOnly TURE to only count the executed spools (status of 1). Default is FALSE
	 * @return int The total spool count on success, FALSE on error
	 */
	public function getSpoolCount($parentSpoolId='', $validOnly=true, $pendingOnly=true, $executedOnly=false)
	{
		$spoolRecords = $this->getImportSessionValue("SpoolRecords");

		if (isId($parentSpoolId)) {
			foreach (array_keys($spoolRecords) as $spoolId) {
				if ($spoolRecords[$spoolId]["parentId"] !== $parentSpoolId) {
					unset($spoolRecords[$spoolId]);
				}
			}
		}

		if ($validOnly) {
			foreach (array_keys($spoolRecords) as $spoolId) {
				if ((int)$spoolRecords[$spoolId]["errNo"] > 0) {
					unset($spoolRecords[$spoolId]);
				}
			}
		}

		if ($pendingOnly || $executedOnly) {
			$completedSpools = $this->getImportSessionValue("CompletedSpools");
			$currentSpool = $this->getImportSessionValue("CurrentSpool");

			if (isId($currentSpool)) {
				$currentSpoolId = $currentSpool;
			} else {
				$currentSpoolId = $currentSpool["id"];
			}

			foreach (array_keys($spoolRecords) as $spoolId) {
				if ($pendingOnly && array_key_exists($spoolId, $completedSpools)) {
					unset($spoolRecords[$spoolId]);
				}

				if ($executedOnly && $currentSpoolId !== $spoolId && !array_key_exists($spoolId, $completedSpools)) {
					unset($spoolRecords[$spoolId]);
				}
			}
		}

		return count($spoolRecords);
	}

	/**
	 * Unset (delete) an accounting spool record
	 *
	 * Method will delete an accounting spool record
	 *
	 * @access protected
	 * @param int $spoolId The spool ID
	 * @return bool TRUE if the record was deleted, FALSE otherwise
	 */
	protected function unsetSpool($spoolId)
	{
		if (!isId($spoolId)) {
			return false;
		}

		$spoolRecords = $this->getImportSessionValue("SpoolRecords");

		if (!array_key_exists($spoolId, $spoolRecords)) {
			return true;
		}

		unset($spoolRecords[$spoolId]);

		$this->setImportSessionValue("SpoolRecords", $spoolRecords);

		/**
		 * Recursively delete all the children
		 */
		foreach (array_keys($spoolRecords) as $tmpSpoolId) {
			if ($spoolRecords[$tmpSpoolId]["parentId"] == $spoolId) {
				$this->unsetSpool($tmpSpoolId);
			}
		}

		return true;
	}

	/**
	 * Get the records of all the spool children
	 *
	 * Method will return a list of all the spool children associated with the parent $spoolParentId
	 *
	 * @access protected
	 * @param int $parentSpoolId The spool parent ID
	 * @param bool $lastOneOnly TRUE to only get the last child, FALSE for all of them. Default is FALSE
	 * @return array An array of all the associated children spool records on success, FALSE on error
	 */
	 protected function getSpoolChildren($parentSpoolId, $lastOneOnly=false)
	 {
		if (!isId($parentSpoolId)) {
			return false;
		}

		if ($lastOneOnly) {
			return $this->getSpoolList($parentSpoolId, '', -1, 1);
		} else {
			return $this->getSpoolList($parentSpoolId);
		}
	}

	/**
	 * Get the accounting reference SQL
	 *
	 * Method will return the accounting reference SQL for getting an accounting reference record
	 *
	 * @access public
	 * @param string $type The node type ("customer", "product", "order", etc)
	 * @param int $nodeId The optional node ID
	 * @param string $nodeIdSQL The optional node ID SQL
	 * @param bool $negateNodeId TRUE to select all where $nodeId/$nodeIdSQL NOT equal to accountingrefnodeid. Default is FALSE
	 * @return string The accounting reference SQL on success, FALSE on error
	 */
	public function getReferenceSQL($type, $nodeId='', $nodeIdSQL='', $negateNodeId=false)
	{
		if (trim($type) == '' || (!isId($nodeId) && trim($nodeIdSQL) == '')) {
			return false;
		}

		$query = "SELECT ar.*
					FROM " . self::refTable . " ar
						WHERE ar.accountingrefmoduleid='" . $GLOBALS["ISC_CLASS_DB"]->Quote($this->getId()) . "'
							AND ar.accountingreftype='" . $GLOBALS["ISC_CLASS_DB"]->Quote(isc_strtolower($type)) . "' ";

		if (isId($nodeId)) {
			if ($negateNodeId) {
				$equate = " != ";
			} else {
				$equate = " = ";
			}

			$query .= " AND ar.accountingrefnodeid" . $equate . $nodeId;
		} else {
			if ($negateNodeId) {
				$equate = " NOT IN(";
			} else {
				$equate = " IN(";
			}

			$query .= " AND ar.accountingrefnodeid" . $equate . $nodeIdSQL . ")";
		}

		return $query;
	}

	/**
	 * Get an accounting reference
	 *
	 * Method will return an accounting reference record
	 *
	 * @access public
	 * @param string $type The node type ("customer", "product", "order", etc)
	 * @param mixed $refId The optional reference record ID OR the array of values to search in the accountingrefvalue
	 * @param int $externalId The optional external ID of the referenced node (the ID set by the module)
	 * @param int $nodeId The optional node ID of the referenced node
	 * @param bool $valueOnly TRUE to only return the unserialised accountingrefvalue, FALSE for the whole record. Default is TRUE
	 * @return array The accountingref record if one was found, FALSE otherwise
	 */
	public function getReference($type, $refId='', $externalId='', $nodeId='', $valueOnly=true)
	{
		/**
		 * Must have a type and atleat one search value
		 */
		if (trim($type) == '' || (!isId($refId) && !is_array($refId) && trim($externalId) == '' && !isId($nodeId))) {
			return false;
		}

		if (!is_array($refId)) {
			$query = "SELECT *
						FROM " . self::refTable . "
						WHERE accountingrefmoduleid='" . $GLOBALS["ISC_CLASS_DB"]->Quote($this->getId()) . "'
							AND accountingreftype='" . $GLOBALS["ISC_CLASS_DB"]->Quote(isc_strtolower($type)) . "' ";

			if (isId($refId)) {
				$query .= " AND accountingrefid=" . (int)$refId;
			} else if (trim($externalId) !== '') {
				$query .= " AND accountingrefexternalid='" . $GLOBALS["ISC_CLASS_DB"]->Quote($externalId) . "'";
			} else if (isId($nodeId)) {
				$query .= " AND accountingrefnodeid=" . (int)$nodeId;

			/**
			 * How did we get here?
			 */
			} else {
				return false;
			}

			$query .= " ORDER BY accountingrefid DESC
						LIMIT 1";

			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

			if ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
				$row["accountingrefvalue"] = @unserialize($row["accountingrefvalue"]);

				if ($valueOnly) {
					return $row["accountingrefvalue"];
				} else {
					return $row;
				}
			}

		/**
		 * Else we are searching through the accountingrefvalue column
		 */
		} else {

			$query = "SELECT *
						FROM " . self::refTable . "
						WHERE accountingrefmoduleid='" . $GLOBALS["ISC_CLASS_DB"]->Quote($this->getId()) . "'
							AND accountingreftype='" . $GLOBALS["ISC_CLASS_DB"]->Quote(isc_strtolower($type)) . "' ";

			$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
			while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
				$row["accountingrefvalue"] = @unserialize($row["accountingrefvalue"]);

				if (!is_array($row["accountingrefvalue"]) || empty($row["accountingrefvalue"])) {
					continue;
				}

				foreach ($refId as $key => $val) {
					if (!array_key_exists($key, $row["accountingrefvalue"])) {
						continue 2;
					}

					$source = isc_strtolower((string)$row["accountingrefvalue"][$key]);
					$target = isc_strtolower((string)$val);

					if ($source !== $target) {
						continue 2;
					}
				}

				if ($valueOnly) {
					return $row["accountingrefvalue"];
				} else {
					return $row;
				}
			}
		}

		return false;
	}

	/**
	 * Get the list of references by type
	 *
	 * Method will return an array of references based on the type $type
	 *
	 * @access public
	 * @param string $type The reference type to look for
	 * @return array The array of references on success, FALSE on error
	 */
	public function getReferencesByType($type)
	{
		if (trim($type) == '') {
			return false;
		}

		$references = array();

		$query = "SELECT *
					FROM " . self::refTable . "
					WHERE accountingrefmoduleid='" . $GLOBALS["ISC_CLASS_DB"]->Quote($this->getId()) . "'
						AND accountingreftype='" . $GLOBALS["ISC_CLASS_DB"]->Quote(isc_strtolower($type)) . "'
					ORDER BY accountingrefid DESC";

		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);

		while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {
			$row["accountingrefvalue"] = @unserialize($row["accountingrefvalue"]);
			$references[] = $row;
		}

		return $references;
	}

	/**
	 * Set an accounting reference
	 *
	 * Method will set an accounting reference record. The $type, $refId, $externalId and $nodeId will get passed
	 * to $this->getAccountingReference to see if we should be updating or inserting
	 *
	 * @access public
	 * @param string $type The node type ("customer", "product", "order", etc)
	 * @param string $reference The reference data to store
	 * @param int $refId The optional reference record ID OR the array of values to search in the accountingrefvalue
	 * @param int $externalId The optional external ID (the ID set by the module)
	 * @param int $nodeId The optional node ID
	 * @return int The accountingrefid ID if the record was inserted/updated, FALSE otherwise
	 */
	public function setReference($type, $reference, $refId='', $externalId='', $nodeId='')
	{
		if (trim($type) == '' || !is_array($reference)) {
			return false;
		}

		$savedata = array(
			"accountingrefmoduleid" => $this->getId(),
			"accountingreftype" => $type,
			"accountingrefvalue" => serialize($reference)
		);

		if (trim($externalId) !== '') {
			$savedata["accountingrefexternalid"] = trim($externalId);
		}

		if (isId($nodeId)) {
			$savedata["accountingrefnodeid"] = $nodeId;
		}

		$output = $this->getReference($type, $refId, $externalId, $nodeId, false);

		if (is_array($output)) {
			$rtn = $GLOBALS["ISC_CLASS_DB"]->UpdateQuery(self::refTableSm, $savedata, "accountingrefid=" . (int)$output['accountingrefid']);
			$id = $output["accountingrefid"];
		} else {
			$rtn = $GLOBALS["ISC_CLASS_DB"]->InsertQuery(self::refTableSm, $savedata);
			$id = $rtn;
		}

		if ($rtn !== false) {
			return $id;
		}

		return false;
	}

	/**
	 * Unset an accounting reference
	 *
	 * Method will unset an accounting reference record. Will call $this->getReference() to get the accountingrefid if
	 * $refId is an array
	 *
	 * @access public
	 * @param string $type The node type ("customer", "product", "order", etc)
	 * @param int $refId The optional reference record ID OR the array of values to search in the accountingrefvalue
	 * @param int $externalId The optional external ID (the ID set by the module)
	 * @param int $nodeId The optional node ID
	 * @return bool TRUE if the record was deleted, FALSE otherwise
	 */
	public function unsetReference($type, $refId='', $externalId='', $nodeId='')
	{
		if (is_array($refId)) {
			$output = $this->getReference($type, $refId, $externalId, $nodeId, false);

			if (!is_array($output) || !array_key_exists("accountingrefid", $output)) {
				return true;
			} else {
				$refId = $output["accountingrefid"];
			}
		}

		$where = "WHERE accountingrefmoduleid='" . $GLOBALS["ISC_CLASS_DB"]->Quote($this->getId()) . "'
					AND accountingreftype='" . $GLOBALS["ISC_CLASS_DB"]->Quote($type) . "' ";

		if (isId($refId)) {
			$where .= " AND accountingrefid=" . (int)$refId;
		} else if (trim($externalId) !== '') {
			$where .= " AND accountingrefexternalid='" . $GLOBALS["ISC_CLASS_DB"]->Quote($externalId) . "'";
		} else if (isId($nodeId)) {
			$where .= " AND accountingrefnodeid=" . (int)$nodeId;
		} else {
			return false;
		}

		if ($GLOBALS["ISC_CLASS_DB"]->DeleteQuery(self::refTableSm, $where) === false) {
			return false;
		}

		return true;
	}

	/**
	 * Find the description for this spool
	 *
	 * Method will find the description for this spool. Mainly used when displaying the spool list in the admin
	 *
	 * @access public
	 * @param string $type The spool type
	 * @param string $service The spool service (not the real service)
	 * @param array $nodeData The spool node data array
	 * @return string The spool description on success, FALSE on error
	 */
	public function findDescription($type, $service, $nodeData)
	{
		if (trim($type) == '' || trim($service) == '' || !is_array($nodeData)) {
			return false;
		}

		$name = "";

		switch (isc_strtolower($type)) {
			case "customer":
				if (array_key_exists("custconfirstname", $nodeData)) {
					$name .= $nodeData["custconfirstname"] . " ";
				}

				if (array_key_exists("custconlastname", $nodeData)) {
					$name .= $nodeData["custconlastname"];
				}

				break;

			case "product":
				if (array_key_exists("prodname", $nodeData)) {
					$name .= $nodeData["prodname"];
				}

				break;

			case "order":
				if (array_key_exists("orderid", $nodeData)) {
					$name = GetLang('OrderNo') . $nodeData["orderid"];
				}

				break;

			case "customergroup":
				if (array_key_exists("groupname", $nodeData)) {
					$name .= $nodeData["groupname"];
				}

				break;

			case "salestaxcode";
			case "account":
				if (array_key_exists("Name", $nodeData)) {
					$name = $nodeData["Name"];
				}

				break;
		}

		$name = trim($name);

		if ($name == '') {
			return false;
		}

		// Find the general parts of what we are doing and who are we doing it to
		$descAction = ucfirst(GetLang("AccountingAction" . ucfirst(isc_strtolower($service))));
		$descType = GetLang("AccountingType" . ucfirst(isc_strtolower($type)));

		return sprintf(GetLang("AccountingSpoolDesc"), $descAction, $descType, isc_html_escape($name));
	}

	/**
	 * Find the admin URL for this spool
	 *
	 * Method will find the admin URL for this spool. Mainly used when displaying the spool list in the admin
	 *
	 * @access public
	 * @param string $type The spool type
	 * @param int $nodeId The spool node ID
	 * @return string The spool admin URL
	 */
	public function findAdminURL($type, $nodeId)
	{
		if (trim($type) == '' || !isId($nodeId)) {
			return "#";
		}

		$url = "";

		switch (isc_strtolower($type)) {
			case "customer":
				$url = "index.php?ToDo=viewCustomers&searchQuery=" . $nodeId;
				break;

			case "customergroup":
				$url = "index.php?ToDo=editCustomerGroup&groupId=" . $nodeId;
				break;

			case "product":
				$url = "index.php?ToDo=viewProducts&searchQuery=" . $nodeId;
				break;

			case "order":
				$url = "index.php?ToDo=viewOrders&searchQuery=" . $nodeId;
				break;

			default:
				$url = "#";
				break;
		}

		return $url;
	}

	/**
	 * Initialise the import
	 *
	 * Method will run all the setup functionality needed when initialising the import
	 *
	 * @access public
	 * @param string $lock The lock string used in the import
	 * @return int The amount of locked spools if the initialisation was successful, FALSE if not
	 */
	public function initImport($lock)
	{
		if (trim($lock) == '') {
			$this->logError("Unable to initialise import", "Empty required 'lock' argument (1st arg)");
			return false;
		}

		/**
		 * Initialise our import session
		 */
		$importSessionArray = array(
			"LockKey" => '',
			"LockLease" => '',
			"SpoolRecords" => array(),
			"CurrentSpool" => array(),
			"CompletedSpools" => array(),
			"LastError" => "",
			"SyncedNodeIdx" => array("customer" => array(), "product" => array(), "order" => array()),
			"CompanyData" => array(),
		);

		if (!$this->setImportSession($importSessionArray)) {
			$this->logError("Unable to set import session cache file");
			return false;
		}

		/**
		 * Set the lock lease first before the pre-import hook. If we can't create a lock then don't
		 * do the import
		 */
		if (!$this->setImportSessionLockLease($lock)) {
			$this->logError("Unable to set lock file");
			return false;
		}

		try {

			/**
			 * Run the pre-import hook if we have one. It SHOULD record any errors
			 */
			if (method_exists($this, "initPreImport") && !$this->initPreImport($lock)) {
				$error = $this->getErrors();
				throw new Exception($error[count($error)-1]);
			}

			/**
			 * Run the post-import hook if we have one. It SHOULD record any errors
			 */
			if (method_exists($this, "initPostImport") && !$this->initPostImport($lock)) {
				$error = $this->getErrors();
				throw new Exception($error[count($error)-1]);
			}

			$total = $this->getSpoolCount();

			/**
			 * Set the first spool as the current spool if we have any
			 */
			if ($total > 0) {
				$spoolRecords = $this->getImportSessionValue("SpoolRecords");

				if (!is_array($spoolRecords)) {
					throw new Exception("Cannot set current spool on init import");
				}

				reset($spoolRecords);
				$currentSpoolId = key($spoolRecords);

				if (!isId($currentSpoolId)) {
					throw new Exception("Cannot find spool to set as current on init import");
				}

				if (!$this->setImportSessionValue("CurrentSpool", $currentSpoolId)) {
					throw new Exception("Failed to record current spool ID on init import");
				}
			}

		} catch (Exception $e) {

			/**
			 * Unset our import session if the init fails
			 */
			$this->unsetImportSession();
			$this->logError("Unable to initialise import", $e->getMessage());
			return false;
		}

		return $total;
	}

	/**
	 * Close (finish off) the import
	 *
	 * Method will run all the functionality needed to close off the import
	 *
	 * @access public
	 * @return bool TRUE if the import was successfully closed, FALSE if not
	 */
	public function closeImport()
	{
		/**
		 * Run the pre-close-import hook if we have one. It SHOULD record any errors
		 */
		if (method_exists($this, "closePreImport") && !$this->closePreImport()) {
			return false;
		}

		$this->unsetImportSession();

		/**
		 * Run the post-close-import hook if we have one. It SHOULD record any errors
		 */
		if (method_exists($this, "closePostImport") && !$this->closePostImport()) {
			return false;
		}

		return true;
	}
}