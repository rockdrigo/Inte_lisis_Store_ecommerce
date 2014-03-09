<?php

/**
* Note: this field type is named 'checkbox' according to IEM, but it's actually for display of multiple checkboxes.
*/
class Interspire_EmailIntegration_EmailMarketer_Field_Checkbox extends Interspire_EmailIntegration_EmailMarketer_Field
{
	public function fromSubscriptionToProvider (Interspire_EmailIntegration_Field $field, $value)
	{
		if ($field instanceof Interspire_EmailIntegration_Field_Array && is_array($value)) {
			return $value;
		}
		if ($field instanceof Interspire_EmailIntegration_Field_StringInterface) {
			return $field->valueToString($value);
		}
		return '';
	}
}
