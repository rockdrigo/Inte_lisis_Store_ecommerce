<?php

class Interspire_EmailIntegration_MailChimp_Field_Email extends Interspire_EmailIntegration_MailChimp_Field
{
	public function fromSubscriptionToProvider(Interspire_EmailIntegration_Field $field, $value)
	{
		if ($field instanceof Interspire_EmailIntegration_Field_StringInterface) {
			return $field->valueToString($value);
		}

		return '';
	}
}
