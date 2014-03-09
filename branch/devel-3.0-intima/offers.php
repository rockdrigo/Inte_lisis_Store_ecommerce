<?php

	include(dirname(__FILE__)."/init.php");
	$GLOBALS['ISC_CLASS_OFFERS'] = GetClass('ISC_OFFERS');
	$GLOBALS['ISC_CLASS_OFFERS']->HandlePage();