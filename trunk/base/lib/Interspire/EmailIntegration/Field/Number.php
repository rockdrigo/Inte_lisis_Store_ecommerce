<?php

class Interspire_EmailIntegration_Field_Number extends Interspire_EmailIntegration_Field_Scalar implements Interspire_EmailIntegration_Field_NumberInterface
{
	public function valueToNumber($value)
	{
		return $value;
	}
}
