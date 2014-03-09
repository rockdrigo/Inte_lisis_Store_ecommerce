<?php

class Interspire_EmailIntegration_Field_Bool extends Interspire_EmailIntegration_Field_Scalar implements Interspire_EmailIntegration_Field_StringInterface, Interspire_EmailIntegration_Field_NumberInterface
{
	public function valueToString($value)
	{
		if ($value) {
			return 'true';
		} else {
			return 'false';
		}
	}

	public function valueToNumber($value)
	{
		if ($value) {
			return 1;
		} else {
			return 0;
		}
	}
}
