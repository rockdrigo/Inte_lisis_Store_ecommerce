<?php

require_once(dirname(__FILE__).'/init.php');

$TemplateName = Interspire_String::filterAlphaNumOnly($_GET['tpl']);
$TemplateColor = Interspire_String::filterAlphaNumExtendedOnly($_GET['color']); // (colors have an underscore)
$TemplateImageFile = ISC_BASE_PATH . '/templates/'.$TemplateName . '/Previews/'.$TemplateColor;

$CacheTemplateImageFile = ISC_BASE_PATH . '/cache/tplthumbs/'.$TemplateName.'_'.$TemplateColor;
$maxwidth = '200';
$maxheight = '200';

$expires = 86400; //60 * 60 * 24;

header("", true, 200);
header("Pragma: public");
header("Cache-control: public,maxage=" . $expires);
header("Expires: " . gmdate("r", time() + $expires));

// check cache first
if(file_exists($CacheTemplateImageFile)) {
	if((strtolower(substr($TemplateImageFile,-4)) == ".jpg" || strtolower(substr($TemplateImageFile,-5)) == ".jpeg")) {
		// jpeg image
		header("Content-type: image/jpeg");
	}elseif(strtolower(substr($TemplateImageFile,-4)) == ".gif" ) {
		// gif image
		header("Content-type: image/gif");
	}

	header("Last-Modified: " . gmdate("r", filemtime($CacheTemplateImageFile)));
	echo file_get_contents($CacheTemplateImageFile);
	die();
}elseif(file_exists($TemplateImageFile)) {
	if(!is_dir(ISC_BASE_PATH . '/cache/tplthumbs/')) {
		isc_mkdir(ISC_BASE_PATH . '/cache/tplthumbs/');
	}
	if((strtolower(substr($TemplateImageFile,-4)) == ".jpg" || strtolower(substr($TemplateImageFile,-5)) == ".jpeg") && function_exists('imagejpeg')) {
		// jpeg image
		header("Content-type: image/jpeg");
		$writeOptions = new ISC_IMAGE_WRITEOPTIONS_JPEG();
	}elseif(strtolower(substr($TemplateImageFile,-4)) == ".gif" && function_exists('imagegif') ) {
		// gif image
		header("Content-type: image/gif");
		$writeOptions = new ISC_IMAGE_WRITEOPTIONS_GIF();
	}
	header("Last-Modified: " . gmdate("r"));

	$image = ISC_IMAGE_LIBRARY_FACTORY::getImageLibraryInstance($TemplateImageFile);
	$image->loadImageFileToScratch();
	$image->resampleScratchToMaximumDimensions(200, 200);
	$image->saveScratchToFile($CacheTemplateImageFile, $writeOptions);
	unset($image);

	if(file_exists($CacheTemplateImageFile)) {
		echo file_get_contents($CacheTemplateImageFile);
	}
	else {
		OutputNoImage();
	}
	die();

}else {
	OutputNoImage();
}

function OutputNoImage()
{
	header("Content-type: image/gif");
	echo file_get_contents(ISC_BASE_PATH.'/admin/images/nopreview200.gif');
	die();
}