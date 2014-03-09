<?php
/*
Copyright 2007 Amazon Technologies, Inc.  Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License. You may obtain a copy of the License at:

http://aws.amazon.com/apache2.0

This file is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and limitations under the License.
*/

 /*
 * Helper class for signing URLs for subway.
 */
 class CBUISignatureHelper{

 	/*
	 * sign the URL, given original URL and secret key
	 */
 	function signUrl($unsignedUrl, $secretKey){

 		$parsedUrl = parse_url($unsignedUrl);
 		$hmac_data = "";

 		parse_str($parsedUrl["query"],$output);
 		ksort($output,SORT_STRING);

 		$first = true;
 		$sortedQuery="";
		foreach ($output as $key => $value) {
			if ($first) {
				$first = false;
			} else {
				$sortedQuery .= "&";
			}
			$hmac_data .= $key . $value;
			$sortedQuery .= $key . "=" . urlencode($value);
		}
		$strToSign = $parsedUrl["path"]."?".$sortedQuery;
		//echo "<br> StrToSign: ".$strToSign;
		//get HMAC signature
		$hmac = new Crypt_HMAC($secretKey,"sha1");
 		$hmac_digest = $hmac->hash(trim($strToSign));
 		$binary_hmac = pack("H40",$hmac_digest);
 		$base64_hmac = base64_encode($binary_hmac);

 		return $unsignedUrl."&awsSignature=".urlencode($base64_hmac);
 	}

 }