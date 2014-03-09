<?php
class SIMPLEPAY_CLIENT
{
	private  $_awsAccessKeyId = null;
	private  $_awsSecretAccessKey = null;
	private  $_url = '';

	public function __construct($awsAccessKeyId, $awsSecretAccessKey, $testmode)
	{
		$this->_awsAccessKeyId = $awsAccessKeyId;
		$this->_awsSecretAccessKey = $awsSecretAccessKey;

		if ($testmode) {
			$this->_url = 'https://authorize.payments-sandbox.amazon.com/pba/paypipeline';
		}
		else { // LIVE
			$this->_url = 'https://authorize.payments.amazon.com/pba/paypipeline';
		}
	}

	public function pay(SimplePay_Pay $spay)
	{
		$parameters = array();
		$parameters['amount'] = $spay->getAmount();
		$parameters['description'] = $spay->getDescription();
		$parameters['referenceId'] = $spay->getReferenceId();

		$paymentHash = md5($this->_awsAccessKeyId.$this->_awsSecretAccessKey.$spay->getReferenceId().$_COOKIE['SHOP_ORDER_TOKEN'].$spay->getAmount());

		$parameters['accessKey'] = $this->_awsAccessKeyId;
		$parameters['immediateReturn'] = 1;
		$parameters['returnUrl'] = $GLOBALS['ShopPathSSL'] . '/finishorder.php?sessionId='.$_COOKIE['SHOP_ORDER_TOKEN'].'&hash='.$paymentHash;
		$parameters['abandonUrl'] = $GLOBALS['ShopPathSSL'] . '/cart.php';

		$query = self::getSignedParamString($this->_url, $parameters, $this->_awsSecretAccessKey, 'GET');

		header('Location: '.$this->_url.'?'.$query);
	}

	private function _convertToString(array $parameters)
	{
		$queryParameters = array();
		foreach ($parameters as $key => $value) {
			$queryParameters[] = $key . '=' . urlencode($value);
		}
		return implode('&', $queryParameters);
	}

	/**
	 * Converts an array of parameters into a signed query string
	 */
	public static function getSignedParamString($url, array $parameters, $secret, $method)
	{
		$parameters[Amazon_FPS_SignatureUtils::SIGNATURE_VERSION_KEYNAME] = 2;
		$parameters[Amazon_FPS_SignatureUtils::SIGNATURE_METHOD_KEYNAME] = Amazon_FPS_SignatureUtils::HMAC_SHA256_ALGORITHM;

		$urlInfo = parse_url($url);
		$signature = Amazon_FPS_SignatureUtils::signParameters($parameters, $secret,
			$method, $urlInfo['host'], $urlInfo['path']);

		$parameters[Amazon_FPS_SignatureUtils::SIGNATURE_KEYNAME] = $signature;

		return Amazon_FPS_SignatureUtils::_getParametersAsString($parameters);
	}

	public function validateRequest($params, $urlEndpoint=null, $method=null)
	{
		$utils = new Amazon_FPS_SignatureUtilsForOutbound($this->_awsAccessKeyId, $this->_awsSecretAccessKey);

		try {
			return $utils->validateRequest($params, $urlEndpoint, $method);
		}
		catch (Amazon_FPS_SignatureException $e) {
			return false;
		}
	}
}
