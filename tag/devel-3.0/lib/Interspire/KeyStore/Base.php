<?php

if (!defined('INTERSPIRE_KEYSTORE_DRIVER')) {
	define('INTERSPIRE_KEYSTORE_DRIVER', 'Mysql');
}

switch (INTERSPIRE_KEYSTORE_DRIVER)
{
	case 'Redis':
		class Interspire_KeyStore_Base extends Interspire_KeyStore_Redis { }
		break;

	case 'Mysql':
		class Interspire_KeyStore_Base extends Interspire_KeyStore_Mysql { }
		break;
}
