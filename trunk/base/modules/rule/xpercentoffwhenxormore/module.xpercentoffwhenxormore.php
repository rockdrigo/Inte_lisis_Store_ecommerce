<?php

class RULE_XPERCENTOFFWHENXORMORE extends ISC_RULE
{
	private $amount;
	private $amount_off;

	public function __Construct()
	{
		parent::__construct();

		$this->setName('XPERCENTOFFWHENXORMORE');

		$currency = GetDefaultCurrency();
		if ($currency['currencystringposition'] == "LEFT") {
			$y = $currency['currencystring'] . "Y";
		}
		else {
			$y = "Y" . $currency['currencystring'];
		}
		$x = 'X%';

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

		$items = $quote->getItems();
		$total = 0;
		foreach ($items as $item) {
			$currentDiscount = $item->getDiscountedBaseTotal() * $this->amount_off / 100;
			$total += $currentDiscount;
			$item->addDiscount($this->getDbId(),$currentDiscount);
		}

		$quote->addDiscount($this->getDbId(), $total);

		$amountOff = currencyConvertFormatPrice($total);
		$amount = currencyConvertFormatPrice($this->amount);
		$this->banners[] = sprintf(getLang($this->getName().'DiscountMessage'), $amountOff, $amount);
		return true;
	}

	public function haltReset()
	{
		return false;
	}

}
