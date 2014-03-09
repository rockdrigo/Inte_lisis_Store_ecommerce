<?php

/**
 * This class represents a set of options to apply to an outgoing HTTP request.
 *
 * The intention of this class is to use this instead of constantly adding more arguments to the
 * PostToRemoteFileAndGetResponse function.
 */
class Interspire_Http_RequestOptions
{
	/** @var string the user-agent string to send */
	public $userAgent;

	/** @var array a set of header name=>value to send */
	public $headers = array();
}
