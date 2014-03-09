<?php
/*
Copyright 2007 Amazon Technologies, Inc.  Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License. You may obtain a copy of the License at:

http://aws.amazon.com/apache2.0

This file is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and limitations under the License.
*/

class FPSSignatureHelper
{
	static function generateSignature($secret, $paramsArray)
	{
		$sorted_string_to_encode = FPSSignatureHelper::sortedParams($paramsArray, false);
		$signature = FPSSignatureHelper::generate_base64_hmac_sha1($secret, $sorted_string_to_encode);
		return $signature;
	}

	static function sortedParams($paramArray, $isUrl)
	{
		uksort($paramArray, "strcasecmp");

		$first = true;
		$sortedQuery="";
		$sorted_hmac_data="";
		foreach ($paramArray as $key => $value)
		{
			if ($first)
			{
				$first = false;
			}
			else
			{
				$sortedQuery .= "&";
			}

			$sorted_hmac_data .= $key . $value;
			$sortedQuery .= $key . "=" . urlencode($value);
		}

		if($isUrl)
			return $sortedQuery;
		else
			return $sorted_hmac_data;
	}

	static function generate_base64_hmac_sha1($secretkey, $strToSign)
	{
		$hmac = new Crypt_HMAC($secretkey,"sha1");
		$hmac_digest = $hmac->hash(trim($strToSign));
		$binary_hmac = pack("H40",$hmac_digest);
		return base64_encode($binary_hmac);
	}
}