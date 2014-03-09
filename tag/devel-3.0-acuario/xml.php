<?php

	// Include the application initialization files
	include_once(dirname(__FILE__) . "/admin/init.php");

	// If this is a Google Base export, handle it now
	if(!empty($_GET['action']) && !empty($_GET['t']) && $_GET['action'] == 'AutoFroogleExport') {
		$exporter = GetClass('ISC_ADMIN_FROOGLE');
		$exporter->AutoExport();
		exit;
	}

	// Include the API classes
	include_once(dirname(__FILE__) . "/includes/classes/class.xmlapi.php");

	// Get the XML request data
	if(isset($_REQUEST["xml"])) {
		$request = $_REQUEST["xml"];
	}
	else {
		$request = file_get_contents('php://input');
	}

	// Instantiate the API which also takes care of validation
	$api = new XMLAPI($request);

	// Run the request
	$api->RunRequest();