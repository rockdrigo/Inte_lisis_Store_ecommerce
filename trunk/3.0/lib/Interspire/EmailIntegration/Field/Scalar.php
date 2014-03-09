<?php

abstract class Interspire_EmailIntegration_Field_Scalar extends Interspire_EmailIntegration_Field implements Interspire_EmailIntegration_Field_StringInterface
{
	public function valueToString($value)
	{
		return (string)$value;
	}
}
