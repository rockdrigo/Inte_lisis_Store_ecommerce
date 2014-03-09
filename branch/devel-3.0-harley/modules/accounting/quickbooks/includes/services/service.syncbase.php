<?php

/**
 * Defines the different states in a sync process
 *
 * @define int ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_* The different states in a sync process
 */
$i=0;
define("ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_DELETE_QUERY_QB", ++$i);
define("ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_INACTIVE_QUERY_QB", ++$i);
define("ACCOUNTING_QUICKBOOKS_SYNC_STATE_PARSE_DELETE_QUERY_QB", ++$i);
define("ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_DELETE_QUERY_STORE", ++$i);
define("ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_MODIFIED_QUERY_QB", ++$i);
define("ACCOUNTING_QUICKBOOKS_SYNC_STATE_PARSE_MODIFIED_QUERY_QB_FIRSTTIME", ++$i);
define("ACCOUNTING_QUICKBOOKS_SYNC_STATE_PARSE_MODIFIED_QUERY_QB", ++$i);
define("ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_MODIFIED_QUERY_STORE", ++$i);
define("ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_PRODLEVEL_QUERY", ++$i);
define("ACCOUNTING_QUICKBOOKS_SYNC_STATE_PARSE_PRODLEVEL_QUERY", ++$i);
unset($i);

abstract class ACCOUNTING_QUICKBOOKS_SERVICE_SYNCBASE extends ACCOUNTING_QUICKBOOKS_SERVICE_BASE
{
	const modifiedTimeFudgeFactor = 0;
	const syncStateKey = "SyncStates";
	const qbPendingDeletedKey = "QBPendingDeletedIdx";
	const qbPendingExternalKey = "QBPendingExternalIdx";

	/**
	 * Set the state of the current sync process
	 *
	 * Method will set the state of the current sync process
	 *
	 * @access protected
	 * @param int $state The optional state. Default is '' (stateless)
	 * @param int $defaultState The optional default state if $state is empty. Default is
	 *                          ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_DELETE_QUERY_QB
	 * @return bool TRUE if the state was set, FALSE on error
	 */
	protected function setSyncState($state='', $defaultState=ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_DELETE_QUERY_QB)
	{
		$states = $this->accounting->getImportSessionValue(self::syncStateKey);

		if (!is_array($states)) {
			$states = array();
		}

		if (trim($state) == '' && trim($defaultState) !== '') {
			$state = $defaultState;
		}

		$states[$this->type] = $state;

		return $this->accounting->setImportSessionValue(self::syncStateKey, $states);
	}

	/**
	 * Get the state of the current sync process
	 *
	 * Method will return the state of the current sync process
	 *
	 * @access protected
	 * @param int $defaultState The optional default state if state is not set. Default is
	 *                          ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_DELETE_QUERY_QB
	 * @return int The state for the current sync process, FALSE on error
	 */
	protected function getSyncState($defaultState=ACCOUNTING_QUICKBOOKS_SYNC_STATE_SEND_DELETE_QUERY_QB)
	{
		$states = $this->accounting->getImportSessionValue(self::syncStateKey);

		if (is_array($states) && array_key_exists($this->type, $states) && trim($states[$this->type]) !== '') {
			return $states[$this->type];
		}

		if (trim($defaultState) !== '') {
			return $defaultState;
		}

		return false;
	}

	/**
	 * Check to see if the QB record can be synced into the store
	 *
	 * Method will check to see if the QB record can be synced into the store
	 *
	 * @access protected
	 * @param array $response The response data array from QB
	 * @param array $node The optional existing corresponding node record. Default is FALSE (no record in store)
	 * @return bool TRUE if the QB record should be inserted/updated into the store, FALSE if not
	 */
	protected function canSyncToStore($response, $node=false)
	{
		if (!is_array($node) || empty($node)) {
			return true;
		}

		$dates = $this->getLastModifiedDates($node, $response);
		$pass = false;

		if ($dates) {

			/**
			 * We have to strip out the timezone settings from QB as we save the time in the database as GMT
			 */
			if (function_exists("date_default_timezone_set") && preg_match('#\+\d{2}:\d{2}$#', $dates["response"])) {
				$dates["response"] = substr($dates["response"], 0, -6);
			}

			if ($dates["response"] > $dates["node"]) {
				$pass = true;
			}
		} else {
			$this->accounting->logError("Unable to find last modified dates from records when syncing to store", array("Store" => $node, "QB" => $response));
		}

		return $pass;
	}

	/**
	 * Check to see if the QB record can be synced into the store
	 *
	 * Method will check to see if the QB record can be synced into the store
	 *
	 * @access protected
	 * @param array $node The existing corresponding node record
	 * @param array $reference The optional reference array. Default is FALSE (no reference so insert into QB)
	 * @return bool TRUE if the QB record should be inserted/updated into the store, FALSE if not
|	 */
	protected function canSyncToQB($node, $reference=false)
	{
		if (!is_array($reference) || empty($reference)) {
			return true;
		}

		$dates = $this->getLastModifiedDates($node, $reference);
		$pass = false;

		if ($dates) {

			/**
			 * We have to strip out the timezone settings from QB as we save the time in the database as GMT
			 */
			if (function_exists("date_default_timezone_set") && preg_match('#\+\d{2}:\d{2}$#', $dates["response"])) {
				$dates["response"] = substr($dates["response"], 0, -6);
			}

			/**
			 * Don't import anything that is self::modifiedTimeFudgeFactor seconds old, in case if we have just imported
			 * it from QB. This will not apply to productvariation as they cannot create any on QB
			 */
			if ($this->type !== "productvariation" && (time() - $dates["node"]) <= self::modifiedTimeFudgeFactor) {
				$pass = false;
			} else if ($dates["node"] > $dates["response"]) {
				$pass = true;
			}
		} else {
			$this->accounting->logError("Unable to find last modified dates from records when syncing to QB", array("Store" => $node, "QB" => $reference));
		}

		return $pass;
	}

	/**
	 * Set the deleted external ID list from QB
	 *
	 * Method will set (append) the deleted external ID list from QB
	 *
	 * @access protected
	 * @param string $response The response from the querydel/queryinactive
	 * @return bool TRUE if the query was saved, FALSE on error
	 */
	protected function saveDeleteQuery($response)
	{
		if (!is_array($response)) {
			return false;
		}

		if (!isset($response[0])) {
			$response = array($response);
		}

		$existingExternalIdx = $this->accounting->getImportSessionValue(self::qbPendingDeletedKey);

		if (!is_array($existingExternalIdx)) {
			$existingExternalIdx = array();
		}

		if (!array_key_exists($this->type, $existingExternalIdx) || !is_array($existingExternalIdx[$this->type])) {
			$existingExternalIdx[$this->type] = array();
		}

		foreach ($response as $qbRecord) {

			if (!is_array($qbRecord) || !array_key_exists($this->referenceDataExternalKey, $qbRecord) || trim($qbRecord[$this->referenceDataExternalKey]) == '') {
				continue;
			}

			$existingExternalIdx[$this->type][] = $qbRecord[$this->referenceDataExternalKey];
		}

		return $this->accounting->setImportSessionValue(self::qbPendingDeletedKey, $existingExternalIdx);
	}

	/**
	 * Get the saved deleted query external ID list
	 *
	 * Method will return the saved deleted query external ID list
	 *
	 * @access protected
	 * @return array The saved deleted query external ID list, FALSE if there is none
	 */
	protected function getDeleteQueryExternalIdx()
	{
		$existingExternalIdx = $this->accounting->getImportSessionValue(self::qbPendingDeletedKey);

		if (!is_array($existingExternalIdx) || !array_key_exists($this->type, $existingExternalIdx) || !is_array($existingExternalIdx[$this->type])) {
			return false;
		}

		return $existingExternalIdx[$this->type];
	}

	/**
	 * Delete all the nodes matching the $externalIdx array
	 *
	 * Method will loop through the external IDs in $externalIdx and delete the matching node
	 *
	 * @access protected
	 * @param array $externalIdx An array of deleted external ID's
	 * @return int The amount of database records deleted, FALSE on error
	 */
	protected function execDeleteListFromQB($externalIdx)
	{
		if (!is_array($externalIdx)) {
			return false;
		}

		$totalDeleted = 0;

		foreach ($externalIdx as $externalId) {

			/**
			 * If we don't have a reference for it then we probably don't have it in the first place
			 */
			$reference = $this->accounting->getReference($this->type, '', $externalId, '', false);

			if (!is_array($reference) || !array_key_exists("accountingrefnodeid", $reference) || !isId($reference["accountingrefnodeid"])) {
				continue;
			}

			/**
			 * We go the reference, now we just delete it
			 */
			if (GetConfig('DeletedOrdersAction') == 'purge') {
				if ($this->entityAPI->purge($reference["accountingrefnodeid"])) {
					/**
					 * Remove our reference too
					 */
					$this->accounting->unsetReference($this->type, $reference["accountingrefid"]);
					$totalDeleted++;
				}
			} else {
					if ($this->entityAPI->delete($reference["accountingrefnodeid"])) {
					/**
					 * Remove our reference too
					 */
					$this->accounting->unsetReference($this->type, $reference["accountingrefid"]);
					$totalDeleted++;
				}
			}
		}

		return $totalDeleted;
	}

	/**
	 * Construct and send the service for deleting records on QB
	 *
	 * Method will construct and send the service for deleting records on QB
	 *
	 * @access protected
	 * @return string The child service output on success, TRUE if no records to delete, FALSE on error
	 */
	protected function sendDeleteListToQB()
	{
		$query = $this->getDeletedNodesSQL();

		if (trim($query) == '') {
			return false;
		}

		$referenceList = array();
		$result = $GLOBALS["ISC_CLASS_DB"]->Query($query);
		while ($row = $GLOBALS["ISC_CLASS_DB"]->Fetch($result)) {

			$row["accountingrefvalue"] = @unserialize($row["accountingrefvalue"]);

			$referenceList[] = $row;

			/**
			 * Unset our reference while we are here
			 */
			$this->accounting->unsetReference($this->type, $row["accountingrefid"]);
		}

		if (empty($referenceList)) {
			return true;
		}

		/**
		 * OK, we have records, now create the delete child process and return the output
		 */
		return $this->execChildService($this->type, "del", array("ReferenceList" => $referenceList));
	}

	/**
	 * Check to see if the pending query external IDs array is set
	 *
	 * Method will check to see if the pending query external IDs array is set
	 *
	 * @access protected
	 * @return bool TRUE if the pending array was set, FALSE if not
	 */
	protected function issetQBPendingExternalIdx()
	{
		$externalIdx = $this->getQBPendingExternalIdx();

		if (!is_array($externalIdx)) {
			return false;
		}

		return true;
	}

	/**
	 * Set the pending query external IDs array from QB
	 *
	 * Method will save the pending query external IDs array from QB
	 *
	 * @access protected
	 * @param array $query The response query
	 * @param bool $alreadyParsed TRUE to mark this query as already parsed. Is used internally so DO NOT SET!!!
	 * @return bool TRUE if the pending query external ID array was set successfully, FALSE on error
	 */
	protected function setQBPendingExternalIdx($query, $alreadyParsed=false)
	{
		if (!$alreadyParsed) {
			$parsed = array();

			/**
			 * Sanatise the reponse data (it won't be an array of arrays if only one record is returned)
			 */
			if (!isset($query[0])) {
				$query = array($query);
			}

			foreach ($query as $record) {
				if (!isset($record[$this->referenceDataExternalKey])) {
					continue;
				}

				$parsed[$record[$this->referenceDataExternalKey]] = $record;
			}
		} else {
			$parsed = $query;
		}

		$externalIdx = $this->accounting->getImportSessionValue(self::qbPendingExternalKey);

		if (!is_array($externalIdx)) {
			$queries = array();
		}

		if (!array_key_exists($this->type, $externalIdx)) {
			$externalIdx[$this->type] = array();
		}

		$externalIdx[$this->type] = $parsed;

		return $this->accounting->setImportSessionValue(self::qbPendingExternalKey, $externalIdx);
	}

	/**
	 * Get the pending query external IDs array
	 *
	 * Method will return the pending query external IDs array
	 *
	 * @access protected
	 * @return array The pending query external IDs array, FALSE if not found
	 */
	protected function getQBPendingExternalIdx()
	{
		$externalIdx = $this->accounting->getImportSessionValue(self::qbPendingExternalKey);

		if (!is_array($externalIdx) || !array_key_exists($this->type, $externalIdx)) {
			return false;
		}

		return $externalIdx[$this->type];
	}

	/**
	 * Get the last saved pending query external ID
	 *
	 * Method will return the last saved pending query external ID
	 *
	 * @access protected
	 * @return array The last saved pending query external ID, FALSE if there is none
	 */
	protected function getnextQBPendingExternalIdx()
	{
		$externalIdx = $this->getQBPendingExternalIdx();

		if (!is_array($externalIdx) || empty($externalIdx)) {
			return false;
		}

		reset($externalIdx);

		return key($externalIdx);
	}

	/**
	 * Unset a saved pending query external ID
	 *
	 * Method will unset a saved pending query external ID
	 *
	 * @access protected
	 * @param string $externalId The external ID that points to that query record
	 * @return bool TRUE if the record was deleted, FALSE on error
	 */
	protected function unsetQBPendingExternalIdx($externalId)
	{
		if (trim($externalId) == '') {
			return false;
		}

		$externalIdx = $this->getQBPendingExternalIdx();

		if (!is_array($externalIdx)) {
			return true;
		}

		if (array_key_exists(trim($externalId), $externalIdx)) {
			unset($externalIdx[$externalId]);
			$this->setQBPendingExternalIdx($externalIdx, true);
		}

		return true;
	}

	/**
	 * Sync the response records
	 *
	 * Method will sync up (insert/update) the response records from QB. You must call $this->setQBPendingExternalIdx()
	 * BEFORE you run this method.
	 *
	 * @access protected
	 * @return mixed TRUE if the response records where synced (sunked?), FALSE on error, string if exec'ing a child service
	 */
	protected function syncResponseRecords2Store()
	{
		if (!$this->issetQBPendingExternalIdx()) {
			throw new QBException("Sync data was not set using setQBPendingExternalIdx()", array("type" => $this->type));
		}

		/**
		 * Get the saved response
		 */
		$response = $this->getQBPendingExternalIdx();

		foreach ($response as $externalId => $qbRecord) {

			$this->accounting->logDebug("Syncing " . $this->type. " data from QB into the store", $qbRecord);

			/**
			 * Do a search based on the reference external key
			 */
			$reference = null;
			$node = $this->searchNodeByReference($qbRecord, $reference);

			/**
			 * If that doesn't work then do a search on our node table
			 */
			if (!$node) {
				$node = $this->searchNodeByDB($qbRecord);
			}

			$pass = $this->canSyncToStore($qbRecord, $node);

			/**
			 * If we pass then insert/update the record
			 */
			if ($pass) {
				$this->accounting->logDebug("canSyncToStore passed");

				/**
				 * Wrap a try/catch around it so if we fail on one we can unset it from our sess data. If caught then throw
				 * if back up again
				 */
				try {
					$nodeId = $this->syncResponseRecord2Store($qbRecord, $node);
				} catch (QBException $e) {
					$this->unsetQBPendingExternalIdx($externalId);
					throw new QBException($e->getMessage());
				}

				/**
				 * Do we have a node ID?
				 */
				if (isId($nodeId)) {

					$this->accounting->logDebug("Created/Edited the " . $this->type. " node ID: " . $nodeId);

					$referenceId = '';
					if (is_array($reference)) {
						$referenceId = $reference["accountingrefid"];
					}

					$referenceReturn = $this->setReferenceDataStatically($this->type, $nodeId, $qbRecord, $referenceId);

					$this->accounting->logDebug("Setting the refernece array for the Created/Edited " . $this->type . " node ID: " . $nodeId, $referenceReturn);

				/**
				 * Else if this is a string then a child spool was executed, so return it
				 */
				} else if (is_string($nodeId)) {
					return $nodeId;
				}
			} else {
				$this->accounting->logDebug("canSyncToStore failed");
			}

			/**
			 * We need to unset this query record from our sess data
			 */
			$this->unsetQBPendingExternalIdx($externalId);
		}

		return true;
	}

	/**
	 * Get node data by reference search
	 *
	 * Method will search for reference to try and find the related node data
	 *
	 * @access protected
	 * @param array $response The response array
	 * @param mixed &$reference An referenced variable to store the reference data if found
	 * @param string $refExternalKey The optional reference external key. Default is $this->referenceDataExternalKey or "ListID" if not set
	 * @return array The node data on success, FALSE if not found
	 */
	protected function searchNodeByReference($response, &$reference, $refExternalKey="")
	{
		if (trim($refExternalKey) == '' && isset($this->referenceDataExternalKey)) {
			$refExternalKey = $this->referenceDataExternalKey;
		}

		if (trim($refExternalKey) == '') {
			$refExternalKey = "ListID";
		}

		if (!is_array($response) || trim($refExternalKey) == '' || !array_key_exists($refExternalKey, $response)) {
			return false;
		}

		$reference = false;
		$reference = $this->accounting->getReference($this->type, '', $response[$refExternalKey], '', false);

		if ($reference && isset($reference["accountingrefnodeid"]) && isId($reference["accountingrefnodeid"])) {
			return $this->entityAPI->get($reference["accountingrefnodeid"]);
		}

		return false;
	}

	/**
	 * Log the node ID of the node that has currently been synced across into QB
	 *
	 * Method will store the node ID of thre node that has currently been synced across into QB
	 *
	 * @access protected
	 * @param int $nodeId The node ID to store
	 * @return bool TRUE if the node ID has been stored, FALSE on error
	 */
	protected function storeInsertedNodeId($nodeId)
	{
		if (!isId($nodeId)) {
			return false;
		}

		$storedIdx = $this->accounting->getImportSessionValue("SyncedNodeIdx");

		if (!is_array($storedIdx)) {
			$storedIdx = array();
		}

		if (!array_key_exists($this->type, $storedIdx) || !is_array($storedIdx[$this->type])) {
			$storedIdx[$this->type] = array();
		}

		$storedIdx[$this->type][] = $nodeId;

		$storedIdx[$this->type] = array_unique($storedIdx[$this->type]);

		if (!$this->accounting->setImportSessionValue("SyncedNodeIdx", $storedIdx)) {
			return false;
		}

		return true;
	}

	/**
	 * Get the array of node IDs that have been synced across to QB
	 *
	 * Method will return the array of node IDs that have been synced across to QB
	 *
	 * @access protected
	 * @return array The array of stored node IDs
	 */
	protected function retrieveInsertedNodeIDs()
	{
		$storedIdx = $this->accounting->getImportSessionValue("SyncedNodeIdx");

		if (!is_array($storedIdx) || !array_key_exists($this->type, $storedIdx)) {
			return array();
		}

		return $storedIdx[$this->type];
	}

	/**
	 * Get the last stored node ID that has been synced across to QB
	 *
	 * Method will return the last stored node ID that has been synced across to QB
	 *
	 * @access protected
	 * @return int The last stored node ID, FALSE if none have been stored
	 */
	protected function retrieveLastInsertedNodeId()
	{
		$storedIdx = $this->retrieveInsertedNodeIDs();

		if (!is_array($storedIdx) || empty($storedIdx)) {
			return false;
		}

		return end($storedIdx);
	}

	/**
	 * Get the country ID
	 *
	 * Method will return the country ID if found
	 *
	 * @access protected
	 * @param string $countryName The country name / ISO3 name / ISO2 name
	 * @param string $properCountryName The referenced variable to set the proper country name if the record exists
	 * @return int The country ID on success, FALSE if not found
	 */
	protected function getCountryId($countryName, &$properCountryName=null)
	{
		if (trim($countryName) == '') {
			return false;
		}

		if (strlen($countryName) == 2) {
			$countryId = GetCountryIdByISO2($countryName);
		} else if (strlen($countryName) == 3) {
			$countryId = GetCountryIdByISO3($countryName);
		} else {
			$countryId = GetCountryIdByName($countryName);
		}

		if (!$countryId || trim($countryId) == '') {
			return false;
		}

		$properCountryName = GetCountryById($countryId);

		return (int)$countryId;
	}

	/**
	 * Get the state ID
	 *
	 * Method will return the state ID if found
	 *
	 * @access protected
	 * @param string $stateName The state name / abbrevation
	 * @param int $countryId The country ID
	 * @param string $properStateName The referenced variable to set the proper state name if the record exists
	 * @return int The state ID on success, FALSE if not found
	 */
	protected function getStateId($stateName, $countryId, &$properStateName=null)
	{
		if (trim($stateName) == '' || !isId($countryId)) {
			return false;
		}

		$stateId = GetStateByName($stateName, $countryId);

		if (!isId($stateId)) {
			$stateId = GetStateByAbbrev($stateName, $countryId);
		}

		if (!isId($stateId)) {
			return false;
		}

		$properStateName = GetStateById($stateId);

		return $stateId;
	}
}
