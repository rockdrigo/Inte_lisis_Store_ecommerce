<?php

class RULE_XAMOUNTOFFWHENXORMORE extends ISC_RULE
{
	private $amount;
	private $amount_off;

	public function __Construct()
	{
		parent::__construct();

		$this->setName('XAMOUNTOFFWHENXORMORE');

		$currency = GetDefaultCurrency();
		if ($currency['currencystringposition'] == "LEFT") {
			$x = $currency['currencystring'] . "X";
			$y = $currency['currencystring'] . "Y";
		}
		else {
			$x = "X" . $currency['currencystring'];
			$y = "Y" . $currency['currencystring'];
		}
		$this->displayName = sprintf(GetLang($this->getName().'displayName'), $x, $y);

		$this->addJavascriptValidation('amount', 'int');
		$this->addJavascriptValidation('amount_off', 'int');

		$this->addActionType('orderdiscount');
		$this->ruleType = 'Order';
	}

	public function initialize($data)
	{

		parent::initialize($data);

		$tmp = unserialize($data['configdata']);

		$this->amount = $tmp['varn_amount'];
		$this->amount_off = $tmp['varn_amount_off'];
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
		$quote->addDiscount($this->getDbId(), 0);

		// Remove the discount amounts for this rule from all products
		foreach($quote->getItems() as $item) {
			$item->addDiscount($this->getDbId(), 0);
		}
	}

	public function applyRule(ISC_QUOTE $quote)
	{
		if($quote->getBaseSubTotal() < $this->amount) {
			return false;
		}

		$runningTotal = $this->amount_off;
		$items = $quote->getItems();
		foreach ($items as $item) {
			$discountedBase = $item->getDiscountedBaseTotal();
			if($discountedBase - $runningTotal < 0) {
				$item->addDiscount($this->getDbId(), $discountedBase);
				$runningTotal -= $discountedBase;
			}
			else {
				$item->addDiscount($this->getDbId(), $runningTotal);
				$runningTotal -= $runningTotal;
			}

			if($runningTotal <= 0) {
				break;
			}
		}

		$quote->addDiscount($this->getDbId(), $this->amount_off);

		$amountOff = currencyConvertFormatPrice($this->amount_off);
		$amount = currencyConvertFormatPrice($this->amount);
		$this->banners[] = sprintf(getLang($this->getName().'DiscountMessage'), $amountOff, $amount);
		return true;
	}

	public function haltReset()
	{
		return false;
	}

}