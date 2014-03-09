<?php

	include(dirname(__FILE__)."/init.php");
	$GLOBALS['ISC_CLASS_ORDERS_LOT'] = GetClass('ISC_ORDERS_LOT');
	$GLOBALS['ISC_CLASS_ORDERS_LOT']->HandlePage();