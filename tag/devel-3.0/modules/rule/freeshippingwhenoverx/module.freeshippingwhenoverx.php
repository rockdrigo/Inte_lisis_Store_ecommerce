<?php

class RULE_FREESHIPPINGWHENOVERX extends ISC_RULE
{
	private $amount;
	protected $vendorSupport = true;

	public function __Construct()
	{
		parent::__construct();

		$this->setName('FREESHIPPINGWHENOVERX');

		$currency = GetDefaultCurrency();
		if ($currency['currencystringposition'] == "LEFT") {
			$x = $currency['currencystring'] . "X";
		}
		else {
			$x = "X" . $currency['currencystring'];
		}
		$this->displayName = sprintf(GetLang($this->getName().'displayName'), $x);

		$this->addJavascriptValidation('amount', 'int');
		$this->addActionType('freeshipping');

		$this->ruleType = 'Order';
	}

	public function initialize($data)
	{

		parent::initialize($data);

		$tmp = unserialize($data['configdata']);

		$this->amount = $tmp['varn_amount'];
	}

	public function initializeAdmin()
	{
		$currency = GetDefaultCurrency();
		if ($currency['currencystringposition'] == "LEFT") {
			$GLOBALS['CurrencyLeft'] = $currency['currencystring'];
		}
		else {
			$GLOBALS['CurrencyRight'] =  $currency['currencystring'];
		}
	}

	public function resetState(ISC_QUOTE $quote)
	{
		$quote->setHasFreeShipping(false);
	}

	public function applyRule(ISC_QUOTE $quote)
	{
		if($quote->getBaseSubTotal() >= $this->amount) {
			if (!$quote->getHasFreeShipping()) {
				$this->banners[] = getLang($this->getName().'DiscountMessage');
				$quote->setHasFreeShipping(true);
			}
			return true;
		}

		return false;
	}

	public function haltReset(ISC_QUOTE $quote)
	{
		return false;
	}

	/**
	 * This function will check against the rules if there is any remaining amount to the cart, in order to get the free shipping.
	 * @param ISC_QUOTE $quote The quote object that used to check the free shipping eligibility
	 * @return boolean Return true if there is we found free shipping eligibility. Otherwise, return false
	 */
	public function checkFreeShippingEligibility(ISC_QUOTE $quote)
	{
		foreach($quote->getItems() as $item) {
			if($quote->getBaseSubTotal() < $this->amount) {
				$remainingAmount = $this->amount - $quote->getBaseSubTotal();
				$productName = $item->getName();
				$placeHolders = array(
					'%%PRODUCT_NAME%%' => $productName,
					'%%REMAINING_QUANTITY%%' => '',
					'%%TOTAL_QUANTITY%%' => $this->amount,
					'%%CART_QUANTITY%%' => $item->getQuantity(),
					'%%REMAINING_AMOUNT%%' => CurrencyConvertFormatPrice($remainingAmount),
					'%%TOTAL_AMOUNT%%' => CurrencyConvertFormatPrice($this->amount),
					'%%CART_AMOUNT%%' => CurrencyConvertFormatPrice($quote->getBaseSubTotal()),
				);
				$this->freeShippingEligibilityData = array(
					'message' => str_replace(array_keys($placeHolders), array_values($placeHolders), $this->freeShippingMessage),
					'location' => $this->freeShippingMessageLocation,
					'name' => $this->getName(),
				);
				return true;
			}
		}
		return false;
	}
}