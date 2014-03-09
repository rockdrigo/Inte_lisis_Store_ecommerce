<?php

class Interspire_EmailIntegration_Field_Date extends Interspire_EmailIntegration_Field_Scalar implements Interspire_EmailIntegration_Field_NumberInterface
{
	public function valueToString($value)
	{
		if (!is_numeric($value)) {
			// attempt to parse what appears to be a date string
			$value = strtotime($value);
		}

		return date(GetConfig('ExportDateFormat'), $value);
	}

	public function valueToNumber($value)
	{
		if (!is_numeric($value)) {
			// attempt to parse what appears to be a date string
			$value = strtotime($value);
		}

		return $value;
	}
}
