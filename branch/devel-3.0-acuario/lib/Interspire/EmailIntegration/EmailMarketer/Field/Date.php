<?php

class Interspire_EmailIntegration_EmailMarketer_Field_Date extends Interspire_EmailIntegration_EmailMarketer_Field
{
	/**
	* Converts an IEM date 'setting' (being either "day", "month" or "year") into the equivalent PHP format code.
	*
	* @param string $setting
	* @return string date() format code or false if unrecognised
	*/
	public function dateSettingToFormat ($setting)
	{
		switch (strtolower($setting)) {
			case 'day':
				return 'j';

			case 'month':
				return 'n';

			case 'year':
				return 'Y';

			default:
				return false;
		}
	}

	public function fromSubscriptionToProvider (Interspire_EmailIntegration_Field $field, $value)
	{
		if ($field instanceof Interspire_EmailIntegration_Field_Date) {
			$timestamp = $field->valueToNumber($value);

			// the d/m/y order is defined in the Key returned by IEM when querying custom fields
			$format = 'j/n/Y'; // default/fallback
			if (isset($this->_settings['Key']) && count($this->_settings['Key']) >= 3) { // basic validation
				$key = $this->_settings['Key'];
				$format = $this->dateSettingToFormat($key[0]) . '/' . $this->dateSettingToFormat($key[1]) . '/' . $this->dateSettingToFormat($key[2]);
			}

			return date($format, $timestamp);
		}

		if ($field instanceof Interspire_EmailIntegration_Field_StringInterface) {
			// for other string-compatible fields, try sending that through and let IEM sort it out
			return $field->valueToString($value);
		}

		// other field types that won't map
		return '';
	}
}
