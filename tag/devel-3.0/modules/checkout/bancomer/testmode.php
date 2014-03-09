<?php

function logToFile($data){
	if(is_array($data))
		$data = serialize($data);
	file_put_contents('banlog.txt', $data.PHP_EOL, FILE_APPEND);
}

function ConnectToProvider($an_url, $an_pp_url, $an_data)
{
	$an_response = array();
	// Use Authorize.net's API to charge the credit card
	if(function_exists("curl_exec")) {
		// Use CURL if it's available
		$ch = @curl_init($an_url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $an_data);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$result = curl_exec($ch);

		if(curl_error($ch) != '') {
			logToFile(sprintf("Error: %s %s", $an_url, "(" . curl_errno($ch) . ") " . curl_error($ch)));
			return false;
		}
	}
	else if(function_exists("fsockopen")) {
		$header = "";
		$header .= "POST " . $an_url . " HTTP/1.0\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: " . strlen($an_data) . "\r\n\r\n";

		if($fp = @fsockopen("ssl://" . $an_pp_url, 443, $errno, $errstr, 30)) {
			@fputs($fp, $header . $an_data);

			// Read the body data
			$result = "";
			$headerdone = false;

			while(!@feof($fp)) {
				$line = @fgets($fp, 1024);

				if(@strcmp($line, "\r\n") == 0) {
					// Read the header
					$headerdone = true;
				}
				else if($headerdone) {
					// Header has been read, read the contents
					$result .= $line;
				}
			}
		}
		else {
			logToFile(sprintf("Error socket: %s", $an_pp_url));
			return false;
		}
	}
	else {
		logToFile("Error: no soportado");
		return false;
	}

	// Check to see the we got a response
	if ($result == "") {
		return true;
	}

	return $result;
	$an_response = explode("&", $result);
	foreach ($an_response as $value) {
		$temp = explode('=', $value);
		$an_response[$temp[0]] = $temp[1];
	}
	return $an_response;
}

$an_url = $_POST['Ds_Merchant_MerchantURL'];
$ok = $_POST['Ds_Merchant_UrlOK'];
$ko = $_POST['Ds_Merchant_UrlKO'];
$parts = parse_url($an_url);
$an_pp_url = $parts['scheme'].'://'.$parts['host'].':'.$parts['port'];

$postsent = $_POST;
$_POST = array();

$_POST['Ds_Date'] = date('d/m/Y');
$_POST['Ds_Hour'] = date('H/i');
$_POST['Ds_Amount'] = $_POST['Ds_Merchant_Amount'];
$_POST['Ds_Currency'] = $postsent['Ds_Merchant_Currency'];
$_POST['Ds_Order'] = $postsent['Ds_Merchant_Order'];
$_POST['Ds_MerchantCode'] = $postsent['Ds_Merchant_MerchantCode'];
$_POST['Ds_Terminal'] = $postsent['Ds_Merchant_Terminal'];
$_POST['Ds_Signature'] = $postsent['Ds_Merchant_MerchantSignature'];
$_POST['Ds_Response'] = '000';
$_POST['Ds_MerchantData'] = $postsent['Ds_Merchant_MerchantData'];
$_POST['Ds_SecurePayment'] = 0;
$_POST['Ds_TransactionType'] = $postsent['Ds_Merchant_TransactionType'];
$_POST['Ds_ConsumerLanguage'] = 0;
$_POST['Ds_ErrorCode'] = '00';
$_POST['Ds_ErrorMessage'] = 'Successful approval/completion';

if($res = ConnectToProvider($an_url, $an_pp_url, $_POST)){
	logToFile($res);
	
	header('Location: '.$ok);
}
else {
	header('Location: '.$ko);
}
