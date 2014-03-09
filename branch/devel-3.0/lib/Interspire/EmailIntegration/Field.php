<?php

/**
* This class represents the base of Interspire_EmailIntegration_Field_* classes, which represent field types used in subscriptions for email integration.
*/
abstract class Interspire_EmailIntegration_Field
{
	/**
	* String that corresponds to id of field in subscription data.
	*
	* @var string
	*/
	public $id;

	/**
	* Language description of this field
	*
	* @var string
	*/
	public $description;

	/**
	* @param string $id
	* @param string $description
	* @return Interspire_EmailIntegration_Field
	*/
	public function __construct($id = null, $description = null)
	{
		$this->id = $id;
		$this->description = $description;
	}
}
