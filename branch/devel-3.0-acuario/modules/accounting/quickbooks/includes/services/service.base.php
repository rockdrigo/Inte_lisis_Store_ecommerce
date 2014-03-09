<?php

abstract class ACCOUNTING_QUICKBOOKS_SERVICE_BASE
{
	const errorHookPrefix = "handleError";
	const errorHookCatchAll = "handleErrorAll";

	protected $spool;
	protected $accounting;
	protected $isResponse;

	public $referenceDataSetup;
	public $referenceDataExternalKey;

	/**
	 * Constructor
	 *
	 * Base constructor
	 *
	 * @access public
	 * @param array $spool The formatted spool array that we are working with
	 * @param object $accounting The accounting object
	 * @param bool $isResponse TRUE to specify thatb this service is a response, FASLE for a request. Default is FALSE
	 * @return void
	 */
	public function __construct($spool, $accounting, $isResponse=false)
	{
		$this->spool = $spool;
		$this->accounting = $accounting;
		$this->isResponse = (bool)$isResponse;

		$this->referenceDataSetup = array(
						"ListID",
						"EditSequence",
						"TimeCreated",
						"TimeModified"
		);

		$this->referenceDataExternalKey = "ListID";
		$this->accounting->logDebug("Init SpoolID " . $this->spool["id"]);
	}

	/**
	 * Default method for handling the request service
	 *
	 * Method is the default method for handling the request service
	 *
	 * @access public
	 * @return string The request XML
	 */
	public function execRequest()
	{
		$entity = $this->entityObjectFactory();

		if ($this->spool["service"] == "query") {
			return $entity->buildQueryXML();
		} else {
			return $entity->buildXML();
		}
	}

	/**
	 * Default method for handling the response service
	 *
	 * Method is the default method for handling the response service
	 *
	 * @access public
	 * @return bool TRUE if the reference was create and the response handled, FALSE on error
	 */
	public function execResponse()
	{
		/**
		 * Do we have any errors? If so then look for a hook to handle it
		 */
		if ($this->spool["errNo"] > 0) {

			if (method_exists($this, self::errorHookPrefix . $this->spool["errNo"])) {
				$methodName = self::errorHookPrefix . $this->spool["errNo"];
				$this->$methodName();

			/**
			 * Is there a catch-all error hook?
			 */
			} else if (method_exists($this, self::errorHookCatchAll)) {
				$methodName = self::errorHookCatchAll;
				$this->$methodName();
			}

		/**
		 * No errors. Create the reference record based on the response but only if this is NOT a query request
		 */
		} else if ($this->spool["service"] !== "query") {

			/**
			 * Have we disabled automatically creating the reference data?
			 */
			if (isset($this->disableAutoRecordReference) && $this->disableAutoRecordReference) {
				return true;
			}

			/**
			 * If this spool has no nodeID then append the node data array to the response data
			 */
			if (!isId($this->spool["nodeId"])) {
				$responseData = $this->spool["response"];
				$responseData += $this->spool["nodeData"];

				$this->setReferenceData($responseData, '*');
			} else {
				$this->setReferenceData();
			}
		}

		return true;
	}

	/**
	 * Get the entity object
	 *
	 * Method is a factory method that will build the class specific entity object
	 *
	 * @access protected
	 * @return object the entity object
	 */
	protected function entityObjectFactory()
	{
		$entityName = $this->spool["nodeType"];
		return $this->getEntityClass($entityName);
	}

	/**
	 * Get the entity class
	 *
	 * Method will return the entity class that is specified in $this->entityName which should be defined in each
	 * service class
	 *
	 * @access protected
	 * @return object The entity object on success, throw a QBException on error
	 */
	protected function getEntityClass($entityName)
	{
		if (trim($entityName) == '') {
			throw new QBException("Missing entity class name in " . get_class($this), $spool);
		}

		$setup = $this->accounting->findModuleClass("entities", $entityName);

		if (!$setup) {
			throw new QBException("Unable to find entity class for " . get_class($this), $entityName);
		}

		$entityFile = $setup["file"];
		$entityClass = $setup["class"];

		if (!file_exists($entityFile) || !is_file($entityFile) || !is_readable($entityFile)) {
			throw new QBException("Unable to read entity class for " . get_class($this), $setup);
		}

		@include_once($entityFile);

		if (!class_exists($entityClass)) {
			throw new QBException("Undefined entity class for " . get_class($this), $setup);
		}

		$entity = new $entityClass($this->spool, $this->accounting);
		return $entity;
	}

	/**
	 * Create and execute a child service
	 *
	 * Method will create and execute a child service. Method will also set this service as the current service
	 *
	 * @access protected
	 * @param string $type The child spool type
	 * @param string $service The child spool service
	 * @param array $data The child spool data array
	 * @param bool $setAsCurrent TRUE to set the new spool as the current. Default is NULL (see $this->checkSetAsCurrect())
	 * @return string The output of the executed child service on success when sending XML, TRUE when not sending XML,
	 *                throw an Exception on failure
	 */
	protected function execChildService($type, $service, $data, $setAsCurrent=null)
	{
		$spoolId = $this->spool["id"];

		$childSpoolId = $this->accounting->setChildSpool($spoolId, $type, $service, $data);

		if (!isId($childSpoolId)) {
			throw new QBException("Unable to set child service for " . get_class($this), $this->accounting->getErrors());
		}

		$this->accounting->logDebug("Finding child service SpoolID: " . $childSpoolId);

		$childSpool = $this->accounting->getSpool($childSpoolId);

		if (!is_array($childSpool)) {
			$this->accounting->logDebug("Getting child service SpoolID: " . $childSpoolId . " FAILED!!!");
			throw new QBException("Unable to set child service for " . get_class($this), $this->accounting->getErrors());
		}

		$this->accounting->logDebug("Got child service SpoolID: " . $childSpoolId, $childSpool);

		$setup = $this->accounting->findModuleClass("services", $childSpool["storeService"]);

		if (!is_array($setup)) {
			$this->accounting->logDebug("Cannot find the service file for the child service SpoolID: " . $childSpoolId, $childSpool);
			throw new QBException("Unable to load child service for " . get_class($this), $childSpool);
		}

		$serviceFile = $setup["file"];
		$serviceClass = $setup["class"];

		if (!file_exists($serviceFile) || !is_file($serviceFile) || !is_readable($serviceFile)) {
			$this->accounting->logDebug("Unable to load service file for child service SpoolID: " . $childSpoolId, $setup);
			throw new QBException("Unable to find child service file for " . get_class($this), $setup);
		}

		@include_once($serviceFile);

		if (!class_exists($serviceClass)) {
			$this->accounting->logDebug("Unable to load service class for child service SpoolID: " . $childSpoolId, $setup);
			throw new QBException("Unable to find child service class for " . get_class($this), $setup);
		}

		/**
		 * Check to see if we should set this new spool as current. We do it here BEFORE we create the service and the new
		 * service might spark up its own children
		 */
		if (is_null($setAsCurrent)) {
			$setAsCurrent = $this->checkSetAsCurrect();
		}

		if ($setAsCurrent) {
			$this->accounting->logDebug("Setting child spool ID: " . $childSpoolId . " as current for spool ID: " . $this->spool["id"]);
		}

		if ($setAsCurrent && !$this->accounting->setNextSpool($this->spool, $childSpoolId)) {
			$this->accounting->logDebug("Unable to load service file for child service SpoolID: " . $childSpoolId, $setup);
			throw new QBException("Unable to exec child service class for " . get_class($this), $this->accounting->getErrors());
		}

		/**
		 * Now we run the child service if we are sending XML. Don't catch any errors here as it should be caught further up
		 */
		if (!$this->isResponse) {
			$service = new $serviceClass($childSpool, $this->accounting);
			$output = $service->execRequest();
		} else {
			$output = true;
		}

		return $output;
	}

	/**
	 * Execute the next sibling spool
	 *
	 * Method will create and execute the next sibling spool. Method will also escape this service and set the next
	 * sibling service as the current spool
	 *
	 * @access protected
	 * @param bool $setAsCurrent TRUE to set the next spool as the current. Default is NULL (See $this->checkSetAsCurrect())
	 * @param bool $setServiceAsSuccessful TRUE to set the current service as successful. Default is TRUE
	 * @return string The output of the next sibling service if found, NULL if no service could be found
	 */
	protected function execNextService($setAsCurrent=null, $setServiceAsSuccessful=true)
	{
		if ($setServiceAsSuccessful) {
			$this->setServiceAsSuccessful();
		}

		if (is_null($setAsCurrent)) {
			$setAsCurrent = $this->checkSetAsCurrect(true);
		}

		if ($setAsCurrent) {
			$nextSpool = $this->accounting->setNextSpool($this->spool);
		} else {
			$nextSpool = $this->accounting->getNextSpool($this->spool);
		}

		/**
		 * No more spools left
		 */
		if (is_null($nextSpool)) {
			return '';
		}

		return $this->accounting->runService($nextSpool);
	}

	/**
	 * Create the reference data record based on the response array
	 *
	 * Method will create the reference data record with the information in the response. Needs to be here as different
	 * services could have different responses
	 *
	 * @access public
	 * @param array $responseData The optional response data. Default is $this->spool->getResponse()
	 * @param array $refSetup The optional reference array setup. Default is $this->referenceDataSetup. If set to '*' then use
	 *                        everything in the $responseData array
	 * @param string $refExternalKey The optional reference external key. Default is $this->referenceDataExternalKey
	 * @return int The reference data record ID on success, throw a QBException on error
	 */
	protected function setReferenceData($responseData='', $refSetup='', $refExternalKey='')
	{
		if (!is_array($responseData)) {
			$responseData = $this->spool["response"];
		}

		$this->accounting->logDebug("Response data for SpoolID: " . $this->spool["id"], $responseData);

		if (!is_array($responseData)) {
			throw new QBException("Response array argument is not an array for " . get_class($this), $responseData);
		}

		if (trim($refExternalKey) == '') {
			$refExternalKey = $this->referenceDataExternalKey;
		}

		if ($refSetup !== '*') {
			if (!is_array($refSetup)) {
				$refSetup = $this->referenceDataSetup;
			}

			if (!is_array($refSetup)) {
				throw new QBException("Reference setup array argument is not an array for " . get_class($this), $refSetup);
			}

			if (trim($refExternalKey) !== '' && !in_array($refExternalKey, $refSetup)) {
				throw new QBException("Reference external key does not exist in the reference setup array argument for " . get_class($this), array("RefSetup" => $refSetup, "RefExternalKey" => $refExternalKey));
			}

			$refData = array();

			foreach ($refSetup as $key) {
				$key = trim($key);

				if ($key == '' || !array_key_exists($key, $responseData)) {
					continue;
				}

				$refData[$key] = $responseData[$key];
			}
		} else {
			$refData = $responseData;
		}

		$externalId = '';

		if (trim($refExternalKey) !== '' && array_key_exists($refExternalKey, $refData)) {
			$externalId = $refData[$refExternalKey];
		}

		$spoolType = $this->spool["nodeType"];
		$spoolNodeId = $this->spool["nodeId"];
		$spoolRefId = $this->spool["referenceId"];

		$refId = $this->accounting->setReference($spoolType, $refData, $spoolRefId, $externalId, $spoolNodeId);

		if (!isId($refId)) {
			throw new QBException("Error when trying to add reference for " . get_class($this), $this->accounting->getErrors());
		}

		return $refId;
	}

	/**
	 * Statically create the reference data record based on the response array
	 *
	 * Method will statically create the reference data record with the information in the response
	 *
	 * @access public
	 * @param string $type The spool type
	 * @param int $nodeId The node Id
	 * @param array $responseData The response data
	 * @param int $refId The optional reference Id. Default is blank (new reference record)
	 * @param array $refSetup The optional reference array setup. Default is $this->referenceDataSetup. If set to '*' then use
	 *                        everything in the $responseData array
	 * @param string $refExternalKey The optional reference external key. Default is $this->referenceDataExternalKey
	 * @return int The reference data record ID on success, throw a QBException on error
	 */
	protected function setReferenceDataStatically($type, $nodeId, $responseData, $refId='', $refSetup='', $refExternalKey='')
	{
		$savedType = $this->spool["nodeType"];
		$savedNodeId = $this->spool["nodeId"];
		$savedRefId = $this->spool["referenceId"];

		$this->spool["nodeType"] = $type;
		$this->spool["nodeId"] = $nodeId;
		$this->spool["referenceId"] = $refId;

		$referenceReturn = $this->setReferenceData($responseData, $refSetup, $refExternalKey);

		$this->spool["nodeType"] = $savedType;
		$this->spool["nodeId"] = $savedNodeId;
		$this->spool["referenceId"] = $savedRefId;

		return $referenceReturn;

	}

	/**
	 * Set the service as successful
	 *
	 * Method will remove the spool's ($this->spool or $spool) error messages so as to mark this service as succcessful
	 *
	 * @access protected
	 * @param object $spool The optional spool object. Default will work on $this->spool
	 * @return object The re-initialised spool object if spool object was passed, NULL if working on $this->spool
	 */
	protected function setServiceAsSuccessful($spool=null)
	{
		if (is_array($spool)) {
			$workOnSpool =& $spool;
		} else {
			$workOnSpool =& $this->spool;
		}

		if (!$this->accounting->unsetSpoolError($workOnSpool)) {
			throw new QBException("Unable to set a service as successful for " . get_class($this), $workOnSpool);
		}

		if (is_array($spool)) {
			return $this->accounting->getSpool($workOnSpool["id"]);
		} else {
			$this->spool = $this->accounting->getSpool($this->spool["id"]);
			return null;
		}
	}

	/**
	 * Should we set new services as current?
	 *
	 * Method will check to see if newly created services should be set as the current service
	 *
	 * @access private
	 * @param bool $ignoreChildren TRUE to ignore checking the children of this spool. Default is FALSE
	 * @return bool TRUE if the new service should be set as current, FALSE if not
	 */
	private function checkSetAsCurrect($ignoreChildren=false)
	{
		if ($this->isResponse) {
			return false;
		}

		return true;

		if ($ignoreChildren) {
			return true;
		}

		/**
		 * We need to check all the children to see if they have all been executed. If so then we can set
		 * this spool as the current
		 */
		$childIdx = $this->getChildList($this->spool);

		if (empty($childIdx)) {
			return true;
		}

		$completedSpoolIdx = $this->accounting->getImportSessionValue("CompletedSpools");

		foreach ($childIdx as $childId) {
			if (!in_array($childId, $completedSpoolIdx)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get a flat array of all the recursive children IDs
	 *
	 * Method will recursively loop over all the children of $spool and return an array of all the
	 * children spools IDs found
	 *
	 * @access private
	 * @param array $spool The spool to check in
	 * @return array An array of all the children spool IDs
	 */
	private function getChildList($spool)
	{
		if (!is_array($spool) || !array_key_exists("children", $spool) || !is_array($spool["children"])) {
			return array();
		}

		$childList = array();

		foreach ($spool["children"] as $child) {
			$childList[] = $child["id"];

			if (!empty($child["children"])) {
				$tmpChildList = $this->{__FUNCTION__}($child["children"]);

				if (is_array($tmpChildList)) {
					foreach ($tmpChildList as $tmpId) {
						$childList[] = $tmpId;
					}
				}
			}
		}

		return $childList;
	}
}