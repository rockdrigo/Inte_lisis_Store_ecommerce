<?php

abstract class Interspire_EmailIntegration_SubscriberActionResult
{
	/**
	* module id of result
	*
	* @var string
	*/
	public $moduleId;

	/**
	* list id (as provider id) that was added to / attempted to add to
	*
	* @var string
	*/
	public $listId;

	/**
	* whether the subscription was accepted or not
	*
	* @var bool
	*/
	public $success;

	/**
	* whether or not the subscriber already existed before the request - not all APIs may be able to offer this info, in their case this value will be false
	*
	* @var bool
	*/
	public $existed;

	/**
	* whether or not the action resulted in a queued action
	*
	* @var bool
	*/
	public $pending;

	/**
	* If available, an error code from underlying API
	*
	* @var mixed
	*/
	public $apiErrorCode;

	/**
	* If available, an error message from underlying API
	*
	* @var mixed
	*/
	public $apiErrorMessage;

	/**
	* If available, the raw response body returned by the remote web service
	*
	* @var string
	*/
	public $apiResponseBody;

	/**
	* If available, the subscription which was actioned
	*
	* @var Interspire_EmailIntegration_Subscription
	*/
	public $subscription;

	/**
	* @param string $moduleId module id of result
	* @param string $listId list id (as provider id) that was added to / attempted to add to
	* @param bool $pending whether the subscription is pending or not (e.g. from an asynchronous add) -- if this is true, generally the rest of the data is not available because the server has not been contacted yet
	* @param bool $success whether the subscription was accepted or not
	* @param bool $existed whether or not the subscriber already existed before the request - not all APIs may be able to offer this info, in their case this value will be false
	* @return Interspire_EmailIntegration_AddSubscriberResult
	*/
	public function __construct($moduleId, $listId, $pending, $success = null, $existed = null)
	{
		$this->moduleId = $moduleId;
		$this->listId = $listId;
		$this->pending = $pending;
		$this->success = $success;
		$this->existed = $existed;
	}
}
