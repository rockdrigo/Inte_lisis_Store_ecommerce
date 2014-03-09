<?php

class Interspire_EmailIntegration_MailChimp_Field_Date extends Interspire_EmailIntegration_MailChimp_Field
{
	public function fromSubscriptionToProvider(Interspire_EmailIntegration_Field $field, $value)
	{
		if ($field instanceof Interspire_EmailIntegration_Field_Date) {
			// for dates, the value /should/ be convertable to a timestamp, so we can date() it and send it through as mailchimp requires
			// note: if I sent a date formatted using isc_date_tz to mailchimp, they would convert it to local time (so 2010-04-28 became 2010-04-27) *even if the mailchimp account was set to +10* - so, I'm sending it through without a tz indicator and it seems to make more sense -ge
			return isc_date('Y-m-d H:i:s', $field->valueToNumber($value));
		}

		if ($field instanceof Interspire_EmailIntegration_Field_StringInterface) {
			// for other string-compatible fields, try sending that through and let mailchimp sort it out
			return $field->valueToString($value);
		}

		// other field types that won't map
		return '';
	}
}
