<?php

/*
 * Dummy file. Needs to be replaced with a real HOP.php file
 */


function php_hmacsha1($data, $key)
{
  return '';
}

function cybs_sha1($in)
{
 return 0;
}


function getmicrotime()
{
  list($usec, $sec) = explode(" ",microtime());
  $usec = (int)((float)$usec * 1000);
  while (strlen($usec) < 3) { $usec = "0" . $usec; }
  return $sec . $usec;
}


function hopHash($data, $key)
{
    return base64_encode(php_hmacsha1($data, $key));
}

function getMerchantID()
{
	return  "";
}
function getPublicKey()
{
	return "";
}
function getPrivateKey()
{
	return "";
	}
function getSerialNumber()
{
	return "";
}
