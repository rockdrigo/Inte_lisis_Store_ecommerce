<?php

/**
* This class represents a currency-type email subscription field. This is intended store and convert between money values when sending subscriptions to email providers.
*
* When creating a subscription, currency values must be stored as an array containing 'numeric' and 'formatted' values.
*/
class Interspire_EmailIntegration_Field_Currency extends Interspire_EmailIntegration_Field implements Interspire_EmailIntegration_Field_NumberInterface, Interspire_EmailIntegration_Field_StringInterface
{
	/**
	* Currency values should be unformatted when they're translated to a provider number field
	*
	* @param float $value
	*/
	public function valueToNumber($money)
	{
		if (!is_array($money) || !isset($money['numeric'])) {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError('emailintegration', 'EmailIntegration_Field_Currency->valueToNumber is being called with an invalid value (non array, or with no "numeric" element).');
			return '';
		}
		return $money['numeric'];
	}

	/**
	* Currency values should be formatted when they're translated to a provider text field
	*
	* @param float $value
	*/
	public function valueToString($money)
	{
		if (!is_array($money) || !isset($money['formatted'])) {
			$GLOBALS['ISC_CLASS_LOG']->LogSystemError('emailintegration', 'EmailIntegration_Field_Currency->valueToString is being called with an invalid value (non array, or with no "formatted" element).');
			return '';
		}
		return $money['formatted'];
	}
}
