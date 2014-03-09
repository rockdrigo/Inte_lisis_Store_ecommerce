<?php

include(dirname(__FILE__) . "/init.php");
echo ' ';

if(!isset($_REQUEST['id'])) {
	exit;
}

$supportedPages = array('product', 'category', 'page');

$optimizerId = $_REQUEST['id'];
if(strpos($optimizerId, '_') !== false) {
	list($requestedPage, $itemId) = explode('_', $optimizerId);
	if(in_array($requestedPage, $supportedPages)) {

		$query = "Select optimizer_conversion_script
				From [|PREFIX|]optimizer_config
				Where optimizer_type='".$GLOBALS['ISC_CLASS_DB']->Quote($requestedPage)."'
					AND
					  optimizer_item_id='".$GLOBALS['ISC_CLASS_DB']->Quote($itemId)."'";

		$result=$GLOBALS['ISC_CLASS_DB']->Query($query);
		$conversionScript = '';
		if($row = $GLOBALS['ISC_CLASS_DB']->Fetch($result)) {
			$conversionScript = $row['optimizer_conversion_script'];
		}
		echo $conversionScript;
		exit;
	}
}

$optimizerData = $GLOBALS['ISC_CLASS_DATA_STORE']->Read('OptimizerData');
if(isset($optimizerData['optimizer_'.$optimizerId]['conversion_script']) && $optimizerData['optimizer_'.$optimizerId]['conversion_script'] != '') {
	echo $optimizerData['optimizer_'.$optimizerId]['conversion_script'];
	exit;
}

/*
$dirPath = ISC_CACHE_DIRECTORY.'optimizerfiles/';
$filePath = $dirPath.$_REQUEST['id'].'_'.$_REQUEST['page'].'.html';

if(!is_file($filePath)) {
	echo ' ';
	exit;
}

$fh = fopen($filePath, 'r');
$fileContents = fread($fh, filesize($filePath));
fclose($fh);
*/
echo ' ';
exit;