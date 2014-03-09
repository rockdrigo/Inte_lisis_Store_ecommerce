<?php

/**
* This class is the base of Interspire_EmailIntegration_{provider}_Field_* classes, which represent field types coming in from email provider lists.
*/
abstract class Interspire_EmailIntegration_ProviderField
{
	protected $_settings = array();

	/**
	* @param array $data row data from email_provider_list_fields
	* @return Interspire_EmailIntegration_ProviderField
	*/
	public function __construct ($data = array())
	{
		$settings = @unserialize($data['settings']);
		if ($settings !== false) {
			$this->_settings = $settings;
		}
	}

	/**
	* Given a subscription field, return a translated version of $value for this provider field type which is then safe to send throug to the provider's API.
	*
	* @param Interspire_EmailIntegration_Field $field
	* @param mixed $value
	* @return mixed Translated $value or null if translation isn't possible
	*/
	abstract public function fromSubscriptionToProvider(Interspire_EmailIntegration_Field $field, $value);
}
