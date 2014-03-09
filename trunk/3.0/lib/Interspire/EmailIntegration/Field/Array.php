<?php

class Interspire_EmailIntegration_Field_Array extends Interspire_EmailIntegration_Field implements Interspire_EmailIntegration_Field_StringInterface
{
	public function valueToString($value)
	{
		if (is_string($value)) {
			return $value;
		}

		if (is_array($value)) {
			return implode(', ', $value);
		}

		return '';
	}
}
