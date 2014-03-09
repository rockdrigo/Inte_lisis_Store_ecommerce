<?php

class Interspire_EmailIntegration_MailChimp_Field_Address extends Interspire_EmailIntegration_MailChimp_Field
{
	public function fromSubscriptionToProvider(Interspire_EmailIntegration_Field $field, $value)
	{
		if ($field instanceof Interspire_EmailIntegration_Field_Address) {
			// if the field is an address type, the data should already be formatted
			return $value;
		}

		if ($field instanceof Interspire_EmailIntegration_Field_StringInterface) {
			// cast to string if field supports it
			return $field->valueToString($value);
		}

		// other complex value that won't map properly
		return '';
	}
}
