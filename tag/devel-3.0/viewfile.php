<?php

include(dirname(__FILE__) . "/init.php");

//if the field image path has already been saved to the database then read from the data base
if(isset($_GET['orderprodfield'])) {
	$query = "Select * From [|PREFIX|]order_configurable_fields Where orderfieldid = ".(int)$_GET['orderprodfield'];
	$result = $GLOBALS['ISC_CLASS_DB']->Query($query);
	$fieldFile = $GLOBALS['ISC_CLASS_DB']->Fetch($result);

	$fileName = ltrim(basename(' '.$fieldFile['filename']));
	$filePath = ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/configured_products/'.$fileName;
	$fileType = $fieldFile['filetype'];

	ViewFile($filePath, $fileType, $fileName);

}

//if the field image path is not saved in the database yet, read from session, this is the cart page and order confirmation page.
if(isset($_GET['prodfield']) && isset($_GET['cartitem'])) {
	$quote = getCustomerQuote();
	$item = $quote->getItemById($_GET['cartitem']);
	if(!$item) {
		header("HTTP/1.0 404 Not Found");
		exit;
	}

	$configuration = $item->getConfiguration();
	if(!isset($configuration[$_GET['prodfield']]['fileOriginalName'])) {
		header("HTTP/1.0 404 Not Found");
		exit;
	}

	$imagedir = ISC_BASE_PATH.'/'.GetConfig('ImageDirectory').'/configured_products_tmp/';
	$fileName = ltrim(basename(' '.$configuration[$_GET['prodfield']]['value']));
	$filePath = $imagedir.$fileName;
	$fileType = $configuration[$_GET['prodfield']]['fileType'];

	ViewFile($filePath, $fileType, $fileName);
}

function ViewFile($filePath='', $fileType='', $fileName='')
{
	if(!trim($filePath) || !trim($fileType) || !trim($fileName)) {
		header("HTTP/1.0 404 Not Found");
		exit ;
	}

	// check if it's valid mime type
	if(!preg_match('/^[a-z\.\/\-\_]*$/', $fileType)) {
		header("HTTP/1.0 404 Not Found");
		exit;
	}

	header("Content-type: " . $fileType);
	header('Pragma: public');
	header('Content-Length: ' . filesize($filePath));

	// if the file is a application file eg. zip,  then make it as a attachment to download,
	//otherwise image or text files display in browser
	if(stripos($fileType, 'image/') !== false && stripos($_SERVER['HTTP_USER_AGENT'], 'MSIE') === false) {
		header('Content-Disposition: inline; filename="'.$fileName.'"');
	}
	else {
		header('Content-Disposition: attachment; filename="'.$fileName.'"');
	}

	flush();
	ob_clean();
	@readfile($filePath);
	exit;
}