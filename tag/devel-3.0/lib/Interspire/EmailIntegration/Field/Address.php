<?php

class Interspire_EmailIntegration_Field_Address extends Interspire_EmailIntegration_Field implements Interspire_EmailIntegration_Field_StringInterface
{
	public function valueToString($value)
	{
		if (is_array($value)) {
			// addresses /should/ be arrays of key=>value pairs that, when imploded, look sensible as a basic string
			return implode(' ', $value);
		}

		// otherwise, if for some reason the address is not a string, cast it
		return (string)$value;
	}
}
