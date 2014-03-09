<?php

class RULE_XOFFFORREPEATCUSTOMERS extends ISC_RULE
{
	private $orders;
	private $amount;

	public function __Construct()
	{
		parent::__construct();

		$this->setName('XOFFFORREPEATCUSTOMERS');

		$currency = GetDefaultCurrency();
		if ($currency['currencystringposition'] == "LEFT") {
			$x = $currency['currencystring'] . "X";
		}
		else {
			$x = "X" . $currency['currencystring'];
		}
		$this->displayName = sprintf(GetLang($this->getName().'displayName'), $x);

		$this->addJavascriptValidation('orders', 'int');
		$this->addJavascriptValidation('amount', 'int');

		$this->addActionType('orderdiscount');
		$this->ruleType = 'Order';
	}

	public function initialize($data)
	{

		parent::initialize($data);

		$tmp = unserialize($data['configdata']);
		$this->amount = $tmp['varn_amount'];
		$this->orders = $tmp['var_orders'];
	}

	public function initializeAdmin()
	{
		$quantity = 1;

		if (isset($GLOBAL['var_orders'])) {
			$quantity = $GLOBAL['var_orders'];
		}

		// If we're using a cart quantity drop down, load that
		if (GetConfig('TagCartQuantityBoxes') == 'dropdown') {
			$GLOBALS['SelectId'] = "orders";
			$GLOBALS['Qty0'] = Interspire_Template::getInstance('admin')->render('Snippets/DiscountItemQtySelect.html');
		// Otherwise, load the textbox
		} else {
			$GLOBALS['SelectId'] = "orders";
			$GLOBALS['Qty0'] = Interspire_Template::getInstance('admin')->render('Snippets/DiscountItemQtyText.html');
		}

		if (!isset($GLOBALS['var_ps'])) {
			$GLOBALS['var_ps'] = GetLang('ChooseAProduct');
		}

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
		$customerId = getClass('ISC_CUSTOMER')->getCustomerId();
		$query = "
			SELECT COUNT(*)
			FROM [|PREFIX|]orders
			WHERE ordcustid='".$customerId."' AND ordstatus > 0 AND deleted = 0
			LIMIT 1
		";
		$orderCount = $GLOBALS['ISC_CLASS_DB']->fetchOne($query);

		// Discount does not apply
		if($orderCount <= $this->orders) {
			return false;
		}

		$runningTotal = $this->amount;
		$appliedAmount = 0;
		$items = $quote->getItems();
		foreach ($items as $item) {
			$discountedBase = $item->getDiscountedBaseTotal();
			if($discountedBase - $runningTotal < 0) {
				$item->addDiscount($this->getDbId(), $discountedBase);
				$appliedAmount += $discountedBase;
				$runningTotal -= $discountedBase;
			}
			else {
				$item->addDiscount($this->getDbId(), $runningTotal);
				$appliedAmount += $runningTotal;
				$runningTotal -= $runningTotal;
			}

			if($runningTotal <= 0) {
				break;
			}
		}

		$quote->addDiscount($this->getDbId(), $appliedAmount);

		$amount = currencyConvertFormatPrice($appliedAmount);
		$this->banners[] = sprintf(getLang($this->getName().'DiscountMessage'), $amount);
		return true;
	}

	public function haltReset()
	{
		return false;
	}

}
