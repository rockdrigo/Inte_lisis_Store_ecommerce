<?php

	include(dirname(__FILE__)."/init.php");
	$GLOBALS['ISC_CLASS_STORECLOSED'] = GetClass('ISC_STORECLOSED');
	$GLOBALS['ISC_CLASS_STORECLOSED']->HandlePage();