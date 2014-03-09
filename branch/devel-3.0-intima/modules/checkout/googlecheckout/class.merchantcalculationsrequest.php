<?php

class GOOGLE_CHECKOUT_MERCHANT_CALCULATIONS_REQUEST {
	private $root = 'merchant-calculation-callback';
	public $data;
	public $couponCodes = array();
	public $certificateCodes = array();

	public function __construct($data)
	{
		$this->data = $data;
		$this->parseMerchantCodes();
	}

	private function parseMerchantCodes()
	{
		if(!isset($this->data[$this->root]['calculate']['merchant-code-strings']['merchant-code-string'])) {
			return;
		}

		$codes = $this->getArrayResult($this->data[$this->root]['calculate']['merchant-code-strings']['merchant-code-string']);

		foreach($codes as $code)
		{
			if($this->isGiftCertificateCode($code['code'])) {
				$this->certificateCodes[] = $code['code'];
			}
			else {
				$this->couponCodes[] = $code['code'];
			}
		}
	}

	/**
	 * Tests if a code is a gift certificate code.
	 *
	 * @param string the code to be tested
	 *
	 * @return array The gift certificate row data, or false on failure.
	 */
	private function isGiftCertificateCode($code)
	{
		return ISC_QUOTE::fetchGiftCertificate($code);
	}

	public function getCartSessionId()
	{
		if(!isset($this->data[$this->root]['shopping-cart']['merchant-private-data']['VALUE'])) {
			return null;
		}

		return $this->data[$this->root]['shopping-cart']['merchant-private-data']['VALUE'];
	}

	public function getAnonymousAddresses()
	{
		if(!isset($this->data[$this->root]['calculate']['addresses']['anonymous-address'])) {
			return array();
		}

		return $this->getArrayResult($this->data[$this->root]['calculate']['addresses']['anonymous-address']);
	}

	public function getShippingMethods()
	{
		if(!isset($this->data[$this->root]['calculate']['shipping']['method'])) {
			return array();
		}

		return $this->getArrayResult($this->data[$this->root]['calculate']['shipping']['method']);
	}

	public function getTax()
	{
		if(!isset($this->data[$this->root]['calculate']['tax']['VALUE'])) {
			return false;
		}

		return $this->data[$this->root]['calculate']['tax']['VALUE'] === "true";
	}

	private function getArrayResult($xmlNode)
	{
		if(isset($xmlNode[0])) {
			return $xmlNode;
		}

		return array($xmlNode);
	}
}