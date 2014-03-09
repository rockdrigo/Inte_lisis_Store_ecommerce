<?php
/*******************************************************************************
 *  Copyright 2008-2010 Amazon Technologies, Inc.
 *  Licensed under the Apache License, Version 2.0 (the 'License');
 *
 *  You may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at: http://aws.amazon.com/apache2.0
 *  This file is distributed on an 'AS IS' BASIS, WITHOUT WARRANTIES OR
 *  CONDITIONS OF ANY KIND, either express or implied. See the License for the
 *  specific language governing permissions and limitations under the License.
 ******************************************************************************/

class Amazon_FPS_SignatureUtils
{

	const SIGNATURE_KEYNAME = "Signature";
	const SIGNATURE_METHOD_KEYNAME = "SignatureMethod";
	const SIGNATURE_VERSION_KEYNAME = "SignatureVersion";
	const HMAC_SHA1_ALGORITHM = "HmacSHA1";
	const HMAC_SHA256_ALGORITHM = "HmacSHA256";

    /**
     * Computes RFC 2104-compliant HMAC signature for request parameters
     * Implements AWS Signature, as per following spec:
     *
     * If Signature Version is 1, it performs the following:
     *
     * Sorts all  parameters (including SignatureVersion and excluding Signature,
     * the value of which is being created), ignoring case.
     *
     * Iterate over the sorted list and append the parameter name (in original case)
     * and then its value. It will not URL-encode the parameter values before
     * constructing this string. There are no separators.
     *
     * If Signature Version is 2, string to sign is based on following:
     *
     *    1. The HTTP Request Method followed by an ASCII newline (%0A)
     *    2. The HTTP Host header in the form of lowercase host, followed by an ASCII newline.
     *    3. The URL encoded HTTP absolute path component of the URI
     *       (up to but not including the query string parameters);
     *       if this is empty use a forward '/'. This parameter is followed by an ASCII newline.
     *    4. The concatenation of all query string components (names and values)
     *       as UTF-8 characters which are URL encoded as per RFC 3986
     *       (hex characters MUST be uppercase), sorted using lexicographic byte ordering.
     *       Parameter names are separated from their values by the '=' character
     *       (ASCII character 61), even if the value is empty.
     *       Pairs of parameter and values are separated by the '&' character (ASCII code 38).
     *
     */
    public static function signParameters(array $parameters, $key, $httpMethod, $host, $requestURI)
	{
        $signatureVersion = $parameters[self::SIGNATURE_VERSION_KEYNAME];
        $algorithm = self::HMAC_SHA1_ALGORITHM;
        $stringToSign = null;
        if (1 === $signatureVersion) {
            $stringToSign = self::_calculateStringToSignV1($parameters);
        } else if (2 === $signatureVersion) {
            $algorithm = $parameters[self::SIGNATURE_METHOD_KEYNAME];
            $stringToSign = self::_calculateStringToSignV2($parameters, $httpMethod, $host, $requestURI);
        } else {
            throw new Exception("Invalid Signature Version specified");
        }
        return self::_sign($stringToSign, $key, $algorithm);
    }

    /**
     * Calculate String to Sign for SignatureVersion 1
     * @param array $parameters request parameters
     * @return String to Sign
     */
    private static function _calculateStringToSignV1(array $parameters)
	{
        $data = '';
        uksort($parameters, 'strcasecmp');
        foreach ($parameters as $parameterName => $parameterValue) {
            $data .= $parameterName . $parameterValue;
        }
        return $data;
    }

    /**
     * Calculate String to Sign for SignatureVersion 2
     * @param array $parameters request parameters
     * @return String to Sign
     */
    private static function _calculateStringToSignV2(array $parameters, $httpMethod, $hostHeader, $requestURI)
	{
        if ($httpMethod == null) {
        	throw new Exception("HttpMethod cannot be null");
        }
        $data = $httpMethod;
        $data .= "\n";

        if ($hostHeader == null) {
        	$hostHeader = "";
        }
        $data .= $hostHeader;
        $data .= "\n";

        if (!isset ($requestURI)) {
        	$requestURI = "/";
        }
		$uriencoded = implode("/", array_map(array("Amazon_FPS_SignatureUtils", "_urlencode"), explode("/", $requestURI)));
        $data .= $uriencoded;
        $data .= "\n";

        uksort($parameters, 'strcmp');
        $data .= self::_getParametersAsString($parameters);
        return $data;
    }

    private static function _urlencode($value)
	{
		return str_replace('%7E', '~', rawurlencode($value));
    }

    /**
     * Convert paremeters to Url encoded query string
     */
    public static function _getParametersAsString(array $parameters)
	{
        $queryParameters = array();
        foreach ($parameters as $key => $value) {
            $queryParameters[] = $key . '=' . self::_urlencode($value);
        }
        return implode('&', $queryParameters);
    }

    /**
     * Computes RFC 2104-compliant HMAC signature.
     */
    private static function _sign($data, $key, $algorithm)
	{
        if ($algorithm === 'HmacSHA1') {
            $hash = 'sha1';
        } else if ($algorithm === 'HmacSHA256') {
            $hash = 'sha256';
        } else {
            throw new Exception ("Non-supported signing method specified");
        }
        return base64_encode(
            hash_hmac($hash, $data, $key, true)
        );
    }
}

?>
