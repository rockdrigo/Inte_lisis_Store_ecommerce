<?php

GetLib('uploadhandler');

class ISC_UPLOADHANDLER extends UploadHandler {

}

// populate the upload handler language
foreach (UploadHandler::$i18n as $UploadHandlerIndex => $UploadHandlerValue) {
	UploadHandler::$i18n[$UploadHandlerIndex] = GetLang($UploadHandlerIndex);
}
