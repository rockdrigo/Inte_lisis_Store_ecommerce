<?php

class Interspire_EmailIntegration_MailChimp_Field_Number extends Interspire_EmailIntegration_MailChimp_Field
{
	public function fromSubscriptionToProvider(Interspire_EmailIntegration_Field $field, $value)
	{
		if ($field instanceof Interspire_EmailIntegration_Field_NumberInterface) {
			// anything here supports a ToNumber conversion
			return $field->valueToNumber($value);
		}

		if ($field instanceof Interspire_EmailIntegration_Field_StringInterface) {
			// anything else that can boil down to a string should be sent as is; mailchimp will handle conversion and rounding
			return $field->valueToString($value);
		}

		// other complex fields that won't map
		return '';
	}
}
