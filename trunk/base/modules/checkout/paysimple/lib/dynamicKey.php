<?php

require_once("paysimple.php");


class DynamicKey
{
	private $paymentModule;

	function DynamicKey($paymentModule, $gateway)
	{
		$this->paymentModule = $paymentModule;
		$dynamicKeyFile = $this->paymentModule->getMerchantKey();

		if ($dynamicKeyFile !== FALSE)
		{
			$dynamicKeyParts = explode("\n", $dynamicKeyFile);

			if (!isset($dymanicKeyParts[1]) || ($dynamicKeyParts[1] - mktime()) < 0)
			{
				$this->GenerateNewKey(10, $dynamicKeyParts, $gateway);
			}
			else
			{
				$this->key = trim($dynamicKeyParts[0]) . trim($dynamicKeyParts[2]);
			}
		}
	}

    function GenerateNewKey($daysToExpire, $dynamicKeyParts, $gateway)
    {
		$expiry = mktime() + ($daysToExpire * 86400);
		$staticHalf = trim($dynamicKeyParts[0]);
		$date = date("Y-m-d", $expiry).'T'.date("H:i:s", $expiry);
		$dynamicHalf = $gateway->GetDynamicKey($staticHalf, $date);
		$this->key = $staticHalf . $dynamicHalf;
		$keyParts = $staticHalf."\r\n".$expiry."\r\n".$dynamicHalf;

		$this->paymentModule->getMerchantKey($keyParts);

		return true;
	}
}
