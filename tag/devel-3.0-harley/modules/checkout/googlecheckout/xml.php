<?php
	// Include the application initialization files
	include_once(dirname(__FILE__) . "/../../../init.php");

	// Retrieve the XML sent in the HTTP POST request to the ResponseHandler
	if (isset($HTTP_RAW_POST_DATA)) {
		$xml_response = $HTTP_RAW_POST_DATA;
	} else {
		$xml_response = file_get_contents("php://input");
	}
	if (get_magic_quotes_gpc()) {
		$xml_response = stripslashes($xml_response);
	}

	// If this is not a post request we don't need to do anything
	if (empty($xml_response)) {
		$GLOBALS['ISC_CLASS_LOG']->LogSystemError(array('payment', 'checkout_googlecheckout'), 'Invalid request recieved from '.isc_html_escape($_SERVER['REMOTE_ADDR']));
		die();
	}

	// Attempt to handle circumstances where PHP_AUTH_USER and PHP_AUTH_PW aren't available
	if(!isset($_SERVER['PHP_AUTH_USER']) && !isset($_SERVER['PHP_AUTH_PW'])) {
		$authVars = array(
			'REDIRECT_REMOTE_AUTHORIZATION',
			'REMOTE_AUTHORIZATION',
			'REDIRECT_HTTP_AUTHORIZATION',
			'HTTP_AUTHORIZATION',
			'REDIRECT_REMOTE_USER',
			'REMOTE_USER'
		);
		foreach($authVars as $var) {
			// Not set
			if(empty($_SERVER[$var])) {
				continue;
			}

			$authLine = $_SERVER[$var];
			if(substr($authLine, 0, 6) == 'Basic ') {
				$authLine = substr($authLine, 6);
			}

			$authLine = base64_decode($authLine);
			// Not a valid line
			if(strpos($authLine, ':') === false) {
				continue;
			}

			list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', $authLine, 2);
			break;
		}
	}

	require_once(dirname(__FILE__).'/class.handler.php');
	$handler = new GOOGLE_CHECKOUT_HANDLER($xml_response);